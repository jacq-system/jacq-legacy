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
$opt = getopt("hvarn", ["help", "verbose", "all", "recheck", "new"], $restIndex);

$options = array(
    'help'    => (isset($opt['h']) || isset($opt['help']) || $argc == 1), // bool
    'all'     => (isset($opt['a']) || isset($opt['all'])),                // bool
    'recheck' => (isset($opt['r']) || isset($opt['recheck'])),            // bool
    'new'     => (isset($opt['n']) || isset($opt['new'])),                // bool

    'verbose' => ((isset($opt['v']) || isset($opt['verbose'])) ? ((is_array($opt['v'])) ? 2 : 1) : 0)  // 0, 1 or 2
);
$remainArgs = array_slice($argv, $restIndex);
$source_id = (empty(($remainArgs))) ? 0 : intval($remainArgs[0]);

if ($options['help'] || (!$source_id && !$options['all'])) {
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
} elseif ($options['all']) {
    // use $tbls as defined in variables.php
    foreach ($tbls as $tbl) {
        if ($tbl['europeana_get']) {
            generateFiles($tbl['source_id']);
        }
    }
}

function generateFiles(int $source_id): void
{
    global $options, $europeana_dir, $dbLink;

    $sourceCode = $dbLink->query("SELECT source_code 
                                  FROM meta 
                                  WHERE source_id = $source_id")
                         ->fetch_array()['source_code'];
    if (!file_exists($europeana_dir . $sourceCode)) {
        mkdir($europeana_dir . $sourceCode, 0755);
    }
    $sql = "SELECT s.specimen_ID, ei.filesize
            FROM tbl_specimens s
             JOIN tbl_management_collections mc ON mc.collectionID = s.collectionID
             LEFT JOIN gbif_pilot.europeana_images ei ON ei.specimen_ID = s.specimen_ID 
            WHERE mc.source_id = $source_id 
             AND (   s.digital_image > 0 
                  OR s.digital_image_obs > 0)"
        . (($options['new'])     ? " AND s.aktualdatum >= DATE_SUB(NOW(), INTERVAL 15 DAY)" : '')
        . (($options['recheck']) ? " AND (ei.filesize < 1500 OR ei.filesize IS NULL)"       : " AND ei.filesize IS NULL");
    $result = $dbLink->query($sql);
    if (!$result) {
        echo $source_id . ": " . $dbLink->error . "\n";
    } else {
        while ($row = $result->fetch_array()) {
            $filename = $europeana_dir . $sourceCode . '/' . $row['specimen_ID'] . ".jpg";
            for ($i = 0; $i < 3; $i++) {  // PI needs often longer to react...
                $fh = fopen($filename, 'w');
                $curlOptions = array(
                    CURLOPT_URL => "https://services.jacq.org/jacq-services/rest/images/europeana/{$row['specimen_ID']}" . "?withredirect=1",
                    CURLOPT_FILE => $fh,
                    CURLOPT_TIMEOUT => 60,
                    CURLOPT_CONNECTTIMEOUT => 10,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                );
                $curl = curl_init();
                curl_setopt_array($curl, $curlOptions);
                $curl_result = curl_exec($curl);
                if ($curl_result === false && $options['verbose']) {
                    echo "$filename has error " . curl_error($curl) . "\n";
                }
                curl_close($curl);
                fclose($fh);
                if (filesize($filename) > 0) {
                    break;
                }
            }
            $dbLink->query("INSERT INTO gbif_pilot.europeana_images SET
                             specimen_ID = {$row['specimen_ID']},
                             filesize    = " . filesize($filename) . ",
                             filectime   = FROM_UNIXTIME(" . filectime($filename) . "),
                             source_id   = $source_id,
                             source_code = '$sourceCode'
                            ON DUPLICATE KEY UPDATE
                             filesize    = " . filesize($filename) . ",
                             filectime   = FROM_UNIXTIME(" . filectime($filename) . "),
                             source_id   = $source_id,
                             source_code = '$sourceCode'");
            if ($options['verbose'] > 1) {
                echo "$sourceCode ($source_id): $filename\n";
            }
        }
    }
    if ($options['verbose']) {
        echo "---------- $sourceCode ($source_id) finished (" . date(DATE_RFC822) . ") ----------\n";
    }
}

/*
evaluation of results:

SELECT ei.source_id, ei.sizegroup, count(*) AS `number of files`
FROM (
  SELECT source_id,
  CASE
    WHEN filesize < 1500 THEN 'empty'
    ELSE 'ok'
  END AS sizegroup
  FROM gbif_pilot.europeana_images) ei
GROUP BY ei.source_id, ei.sizegroup
ORDER BY ei.source_id, ei.sizegroup DESC
*/

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
