#!/usr/bin/php -q
<?php
require 'inc/variables.php';

/**
 * included via inc/variables.php
 *
 * @var string $host hostname
 * @var string $user username
 * @var string $pass password
 * @var string $db   source database
 * @var array  $tbls which sources are to be checked
 * @var string $europeana_dir directory of europeana images
 */

ini_set("memory_limit", "256M");

/**
 * process commandline arguments
 */
$options = getopt("hvarn", ["help", "verbose", "all", "recheck", "new"], $restIndex);

$help    = (isset($options['h']) || isset($options['help']) || $argc == 1); // bool
$all     = (isset($options['a']) || isset($options['all']));                // bool
$recheck = (isset($options['r']) || isset($options['recheck']));            // bool
$new     = (isset($options['n']) || isset($options['new']));                // bool

$verbose = (isset($options['v']) || isset($options['verbose'])) ? ((is_array($options['v'])) ? 2 : 1) : 0;  // 0, 1 or 2

$remainArgs = array_slice($argv, $restIndex);
$source_id = (empty(($remainArgs))) ? 0 : intval($remainArgs[0]);

if ($help || (!$source_id && !$all)) {
    echo $argv[0] . " [options] [x]   create europeana files [for source-ID x]\n\n"
       . "Options:\n"
       . "  -h  --help     this explanation\n"
       . "  -v  --verbose  echo status messages\n"
       . "  -vv            echo processed filenames also\n"
       . "  -r  --recheck  recheck all image-files which are smaller than 1500 Bytes\n"
       . "  -n  --new      only check specimen which changed within the last two weeks\n"
       . "  -a  --all      use all predefined source-IDs\n\n";
    die();
}

$dbLink = new mysqli($host, $user, $pass, $db);

if ($source_id) {
    generateFiles($source_id);
} elseif ($all) {
    // use $tbls as defined in variables.php
    foreach ($tbls as $tbl) {
        if ($tbl['europeana_get']) {
            generateFiles($tbl['source_id']);
        }
    }
}

function generateFiles(int $source_id): void
{
    global $recheck, $verbose, $new, $europeana_dir, $dbLink;

    $sourceCode = $dbLink->query("SELECT source_code 
                                  FROM meta 
                                  WHERE source_id = $source_id")
                         ->fetch_array()['source_code'];
    if (!file_exists($europeana_dir . $sourceCode)) {
        mkdir($europeana_dir . $sourceCode, 0755);
    }
    $sql = "SELECT s.specimen_ID 
            FROM tbl_specimens s
             JOIN tbl_management_collections mc ON mc.collectionID = s.collectionID
            WHERE mc.source_id = $source_id 
             AND (   s.digital_image > 0 
                  OR s.digital_image_obs > 0)";
    if ($new) {
        $sql .= " AND s.aktualdatum >= DATE_SUB(NOW(), INTERVAL 15 DAY)";
    }
    $result = $dbLink->query($sql);
    if (!$result) {
        echo $source_id . ": " . $dbLink->error . "\n";
    } else {
        while ($row = $result->fetch_array()) {
            $filename = $europeana_dir . $sourceCode . '/' . $row['specimen_ID'] . ".jpg";
            if (!file_exists($filename) || ($recheck && filesize($filename) < 1500)) {
                for ($i = 0; $i < 3; $i++) {  // PI needs often longer to react...
                    $fh = fopen($filename, 'w');
                    $options = array(
                        CURLOPT_URL => "https://services.jacq.org/jacq-services/rest/images/europeana/{$row['specimen_ID']}",
                        CURLOPT_FILE => $fh,
                        CURLOPT_TIMEOUT => 60,
                        CURLOPT_CONNECTTIMEOUT => 10,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_SSL_VERIFYPEER => false,
                    );
                    $curl = curl_init();
                    curl_setopt_array($curl, $options);
                    $curl_result = curl_exec($curl);
                    if ($curl_result === false && $verbose) {
                        echo "$filename has error " . curl_error($curl) . "\n";
                    }
                    curl_close($curl);
                    fclose($fh);
                    if (filesize($filename) > 0) {
                        break;
                    }
                }
                if ($verbose > 1) {
                    echo "$sourceCode ($source_id): $filename\n";
                }
            }
        }
    }
    if ($verbose) {
        echo "---------- $sourceCode ($source_id) finished ----------\n";
    }
}


// probably all europeana-images with 908 Bytes are wrong in Phaidra
// find . -name '*.jpg' -size -2k -printf "%f\t%s\n" | sed 's/.jpg//'
// find . -name '*.jpg' -size -2k -printf "%f," | sed 's/.jpg//g'
// find . -name '*.jpg' -size -909c -size +1c -printf "%f," | sed 's/.jpg//g'     only with 908 Bytes
// find . -name '*.jpg' -size -1c -printf "%f," | sed 's/.jpg//g'                 only with 0 Bytes

// find WU -name '*.jpg' -size +1c -size -1500c -ok rm '{}' \;      delete files with size >0 Bytes and <1500 Bytes

/*
SELECT s.specimen_ID, s.collectionID, mc.collection, m.source_id, m.source_code , id.img_def_ID, id.imgserver_url, id.iiif_url
FROM tbl_specimens s
 JOIN tbl_management_collections mc ON mc.collectionID = s.collectionID
 JOIN meta m ON m.source_id = mc.source_id
 JOIN tbl_img_definition id ON id.source_id_fk = mc.source_id
WHERE s.specimen_ID IN (...)
ORDER BY m.source_id, s.collectionID, s.specimen_ID
*/
