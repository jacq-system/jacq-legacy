#!/usr/bin/php -qC
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Jacq\DbAccess;

ini_set("max_execution_time", "3600");
ini_set("memory_limit", "256M");

/**
 * process commandline arguments
 */
$opt = getopt("hvrn", ["help", "verbose", "replenish", "renew"], $restIndex);

$options = array(
    'help'      => (isset($opt['h']) || isset($opt['help']) || $argc == 1),      // bool
    'replenish' => (isset($opt['r']) || isset($opt['replenish']) || $argc == 1), // bool
    'renew'     => (isset($opt['n']) || isset($opt['renew']) || $argc == 1),     // bool

    'verbose'   => ((isset($opt['v']) || isset($opt['verbose'])) ? ((is_array($opt['v'])) ? 2 : 1) : 0)  // 0, 1 or 2
);
$remainArgs = array_slice($argv, $restIndex);
$source_id = (empty(($remainArgs))) ? 0 : intval($remainArgs[0]);

if ($options['help'] || !$source_id) {
    echo $argv[0] . " [options] x   get gbif-data and fill cache for source-ID x\n\n"
        . "Options:\n"
        . "  -h  --help         this explanation\n"
        . "  -v  --verbose      echo status messages\n"
        . "  -r  --replenish    replenish empty entries\n"
        . "  -n  --renew        renew all entries\n\n";
    die();
}

/**
 * as the server uses CEST and not UTC we have to convert the used timestamps
 *
 * @param string $dateString the date to convert
 * @param string $timeZoneSource from this timezone
 * @param string $timeZoneTarget to this one
 * @return string the converted date
 */
function changeTimeZone(string $dateString, string $timeZoneSource, string $timeZoneTarget): string
{
    try {
        $dt = new DateTime($dateString, new DateTimeZone($timeZoneSource));
        $dt->setTimezone(new DateTimeZone($timeZoneTarget));

        if ($timeZoneTarget == 'UTC') {
            $result = $dt->format("Y-m-d\TH:i:sp");
        } else {
            $result = $dt->format("Y-m-d H:i:s");
        }
    } catch (Exception $e) {
        $result = '';
        error_log($e->getMessage());
    }
    return $result;
}

// prepare global variables
try {
    $dbLink = DbAccess::ConnectTo('GBIF_CACHE');
} catch (Exception $e) {
    die($e->getMessage());
}

$source = $dbLink->queryCatch("SELECT source_id, datasetKey, OwnerOrganizationName, LicenseURI, LicensesDetails 
                               FROM sources 
                               WHERE source_id = $source_id")
                 ->fetch_assoc();
if (empty($source)) {
    die("Could not find source-ID $source_id\n");
}

if ($options['renew']) {
    // delete all entries when we fetch the complete dataset from gbif
    $dbLink->query("DELETE FROM specimens WHERE source_id = $source_id");

    $offset = 0;
    $limit = 100;
    do {
        $url = "https://api.gbif.org/v1/occurrence/search"
             . "?basisOfRecord=PRESERVED_SPECIMEN"
             . "&limit=$limit"
             . "&offset=$offset"
             . "&mediaType=StillImage"
            .  "&datasetKey=" . $source['datasetKey'];
        $response = readJsonCurl($url);

        foreach ($response['results'] as $result) {
            if (empty($result['modified'])) {
                $result['modified'] = $result['lastInterpreted'];
            }
            $dbLink->query("INSERT INTO specimens SET
                     specimen_ID = " . intval($result['key']) . ",
                     source_id = $source_id,
                     aktualdatum = '" . changeTimeZone($result['modified'], 'UTC', 'Europe/Vienna') . "',
                     json = '" . $dbLink->real_escape_string(json_encode($result)) . "'");
        }
        $offset += $limit;
    } while (!$response['endOfRecords'] && $offset < 10000);
} elseif ($options['replenish']) {
    $rows = $dbLink->queryCatch("SELECT specimen_ID FROM specimens WHERE source_id = $source_id AND aktualdatum IS NULL")->fetch_all(MYSQLI_ASSOC);
    if ($options['verbose']) {
        echo count($rows) . " to replenish\n";
    }
    $count = $updated = 0;
    $ids = array();
    foreach ($rows as $row) {
        if ($count < 20) {
            $ids[] = "gbifId={$row['specimen_ID']}";
            $count++;
        } else {
            $response = readJsonCurl("https://api.gbif.org/v1/occurrence/search?" . implode("&", $ids));
            if (empty($response['results'])) {
                sleep(10);
                $response = readJsonCurl("https://api.gbif.org/v1/occurrence/search?" . implode("&", $ids));
            }
            updateGbifCache($response['results'] ?? array());
            $updated += count($ids);
            sleep(4);
            $count = 0;
            $ids = array();
        }
    }
    if ($count) {
        $response = readJsonCurl("https://api.gbif.org/v1/occurrence/search?" . implode("&", $ids));
        updateGbifCache($response['results'] ?? array());
        $updated += count($ids);
    }
    if ($options['verbose']) {
        echo "$updated entries replenished\n";
    }
}

/**
 * use Curl to read JSON-data from url and return the decoded response
 *
 * @param $url string use this to get the data
 * @return mixed|void
 */
function readJsonCurl(string $url)
{
    global $options;

    $curl = curl_init($url);
    curl_setopt_array($curl, array(
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RETURNTRANSFER => 1,
    ));
    $curl_result = curl_exec($curl);
    if (!curl_errno($curl)) {
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($statusCode != 200 && $options['verbose']) {
            echo "HTTP status code $statusCode"
               . (($statusCode == 429) ? ", wait for " . curl_getinfo($curl, CURLINFO_RETRY_AFTER) . " seconds.\n" : "\n");
        }
        curl_close($curl);
    } else {
        die("Connection failed: " . curl_error($curl));  // bail out
    }

    return json_decode($curl_result, true);
}

/**
 * Update table specimens in gbif_cache with results of gbif query
 *
 * @param $results array response of the last query
 * @return void
 */
function updateGbifCache(array $results): void
{
    global $dbLink, $options;

    if (!empty($results)) {
        foreach ($results as $result) {
            if (empty($result['modified'])) {
                $result['modified'] = $result['lastInterpreted'];
            }
            $dbLink->query("UPDATE specimens SET
                             aktualdatum = '" . changeTimeZone($result['modified'], 'UTC', 'Europe/Vienna') . "',
                             json        = '" . $dbLink->real_escape_string(json_encode($result)) . "'
                            WHERE specimen_ID = " . intval($result['key']));
            if ($options['verbose'] > 1) {
                echo "{$result['key']} replenished\n";
            }
        }
    }
}
