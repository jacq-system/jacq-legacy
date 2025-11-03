#!/usr/bin/php -q
<?php
require 'inc/variables.php';

/**
 * included via inc/variables.php
 *
 * @var string $host hostname
 * @var string $user username
 * @var string $pass password
 * @var string $dbc  database
 */

ini_set("max_execution_time", "3600");
ini_set("memory_limit", "256M");

/**
 * process commandline arguments
 */
$opt = getopt("hv", ["help", "verbose"], $restIndex);

$options = array(
    'help'    => (isset($opt['h']) || isset($opt['help']) || $argc == 1), // bool

    'verbose' => ((isset($opt['v']) || isset($opt['verbose'])) ? ((is_array($opt['v'])) ? 2 : 1) : 0)  // 0, 1 or 2
);
$remainArgs = array_slice($argv, $restIndex);
$source_id = (empty(($remainArgs))) ? 0 : intval($remainArgs[0]);

if ($options['help'] || (!$source_id && !$options['all'])) {
    echo $argv[0] . " [options] [x]   get gbif-data and fill cache for source-ID x\n\n"
        . "Options:\n"
        . "  -h  --help     this explanation\n"
        . "  -v  --verbose  echo status messages\n\n";
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

$dbLink  = new mysqli($host, $user, $pass, $dbc);

$source = $dbLink->query("SELECT source_id, datasetKey, OwnerOrganizationName, LicenseURI, LicensesDetails 
                          FROM sources 
                          WHERE source_id = $source_id")
                 ->fetch_assoc();
if (empty($source)) {
    die("Could not find source-ID $source_id\n");
}

// delete all entries, as we always fetch the complete dataset from gbif
$dbLink->query("DELETE FROM specimens WHERE source_id = $source_id");

$offset = 0;
$limit = 100;
do {
    $url = "https://api.gbif.org/v1/occurrence/search"
        . "?basisOfRecord=PRESERVED_SPECIMEN"
        . "&limit=$limit"
        . "&offset=$offset"
        . "&mediaType=StillImage"
        . "&datasetKey=" . $source['datasetKey'];
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
        curl_close($curl);
    } else {
        die("Connection failed: " . curl_error($curl));
    }

    $response = json_decode($curl_result, true);
    foreach ($response['results'] as $result) {
        if (empty($result['modified'])) {
            $result['modified'] = $result['lastInterpreted'];
        }
        $dbLink->query("INSERT INTO specimens SET
                     specimen_ID = {$result['key']},
                     source_id = $source_id,
                     aktualdatum = '" . changeTimeZone($result['modified'], 'UTC', 'Europe/Vienna') . "',
                     json = '" . $dbLink->real_escape_string(json_encode($result)) . "'");
    }
    $offset += $limit;
} while (!$response['endOfRecords'] && $offset < 10000);
