#!/usr/bin/php -qC
<?php
require_once __DIR__ . '/../inc/variables.php';

ini_set("max_execution_time","3600");

/** @var mysqli $dbLink */
$dbLink = new mysqli($_CONFIG['DATABASE']['INPUT']['host'],
                     $_CONFIG['DATABASE']['INPUT']['readonly']['user'],
                     $_CONFIG['DATABASE']['INPUT']['readonly']['pass'],
                     $_CONFIG['DATABASE']['INPUT']['name']);
if ($dbLink->connect_errno) {
    die("Database access denied!");
}
$dbLink->set_charset('utf8');

ob_start();

$dbLink->query("INSERT INTO `herbar_pictures`.`phaidra_status` SET start = NOW()");
$statusID = $dbLink->insert_id;

$items_start = $dbLink->query("SELECT count(*) AS number FROM `herbar_pictures`.`phaidra_cache`")->fetch_assoc()['number'];

$items = 0;
$result = $dbLink->query("SELECT s.specimen_ID, s.HerbNummer, tid.HerbNummerNrDigits
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
        if (!$dbLink->query("INSERT INTO `herbar_pictures`.`phaidra_cache` SET `specimenID` = '" . $row['specimen_ID'] . "'")) {
            echo $dbLink->error;
        }
        $items++;
    }
}

$items_end = $dbLink->query("SELECT count(*) AS number FROM `herbar_pictures`.`phaidra_cache`")->fetch_assoc()['number'];

$dbLink->query("UPDATE `herbar_pictures`.`phaidra_status` SET
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
