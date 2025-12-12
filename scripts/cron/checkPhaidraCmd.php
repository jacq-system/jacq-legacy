#!/usr/bin/php -qC
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Jacq\DbAccess;

ini_set("max_execution_time","3600");

/**
 * process commandline arguments
 */
$opt = getopt("h", ["help"], $restIndex);

$options = array(
        'help'    => (isset($opt['h']) || isset($opt['help']))  // bool
);

if ($options['help']) {
    echo $argv[0] . " [options]   check phaidra for new pictures of source wu (id 1)\n\n"
            . "Options:\n"
            . "  -h  --help     this explanation\n\n";
    die();
}

$dbLink = DbAccess::ConnectTo('INPUT');

ob_start();

$dbLink->queryCatch("INSERT INTO `herbar_pictures`.`phaidra_status` SET start = NOW()");
$statusID = $dbLink->insert_id;

$items_start = $dbLink->queryCatch("SELECT count(*) AS number FROM `herbar_pictures`.`phaidra_cache`")->fetch_assoc()['number'];

$items = 0;
$result = $dbLink->queryCatch("SELECT s.specimen_ID, s.HerbNummer, tid.HerbNummerNrDigits
                               FROM tbl_specimens s
                                LEFT JOIN tbl_management_collections mc ON mc.collectionID = s.collectionID
                                LEFT JOIN tbl_img_definition tid        ON tid.source_id_fk = mc.source_id
                               WHERE mc.source_id = 1
                                AND (s.digital_image = 1 OR s.digital_image_obs = 1)
                                AND s.specimen_ID NOT IN (SELECT specimenID FROM herbar_pictures.phaidra_cache)");
while ($row = $result->fetch_array()) {
    $phaidra = false;

    // ask phaidra server if it has the desired picture.
    $ch = curl_init("https://app05a.phaidra.org/viewer/" . sprintf("WU%0" . $row['HerbNummerNrDigits'] . ".0f", str_replace('-', '', $row['HerbNummer'])));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $curl_response = curl_exec($ch);
    if ($curl_response) {
        $info = curl_getinfo($ch);
        if ($info['http_code'] == 200) {
            $phaidra = true;
        }
    }
    curl_close($ch);

    if ($phaidra) {
        try {
            $dbLink->query("INSERT INTO `herbar_pictures`.`phaidra_cache` SET `specimenID` = '" . $row['specimen_ID'] . "'");
        }
        catch (Exception $e) {
            echo $e->getMessage();
        }
        $items++;
    }
}

$items_end = $dbLink->queryCatch("SELECT count(*) AS number FROM `herbar_pictures`.`phaidra_cache`")->fetch_assoc()['number'];

$dbLink->queryCatch("UPDATE `herbar_pictures`.`phaidra_status` SET
                      end         = NOW(),
                      items_start = '$items_start',
                      items_end   = '$items_end',
                      items_added = '$items'
                     WHERE id = $statusID");

ob_end_flush();

/*
SELECT s.*
FROM herbarinput.tbl_specimens s
 LEFT JOIN herbarinput.tbl_management_collections mc ON mc.collectionID = s.collectionID
 LEFT JOIN herbar_pictures.phaidra_cache pc ON pc.specimenID = s.specimen_ID
WHERE mc.source_id = 1
 AND s.digital_image = 1
 AND pc.specimenID IS NULL

9.8.2023: 9174 Items
*/
