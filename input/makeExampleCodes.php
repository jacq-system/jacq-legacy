<?php
session_start();
require("inc/connect.php");
require_once 'inc/stableIdentifierFunctions.php';

$specimenIDs = array();

$contents = file_get_contents("makeExampleCodes.txt");
$lines = explode("\n", $contents);
foreach ($lines as $line) {
    $parts = explode(",", $line);
    foreach ($parts as $part) {
        if (intval(trim($part)) > 0) {
            $specimenIDs[] = intval(trim($part));
        }
    }
}
$dbLink->query("TRUNCATE herbarinput.code_examples");

foreach ($specimenIDs as $specimenID) {
    $row = $dbLink->query("SELECT s.specimen_ID, mc.source_id, m.source_code, s.collectionID, mc.collection, s.HerbNummer
                           FROM tbl_specimens s
                            LEFT JOIN tbl_management_collections mc ON mc.collectionID = s.collectionID
                            LEFT JOIN meta m ON m.source_id = mc.source_id
                           WHERE s.specimen_ID = $specimenID")
                  ->fetch_assoc();
    $dbLink->query("INSERT INTO code_examples SET
                     specimen_ID  = "  . $row['specimen_ID']  . ",
                     source_id    = "  . $row['source_id']    . ",
                     source_code  = '" . $row['source_code']  . "',
                     collectionID = "  . $row['collectionID'] . ",
                     collection   = '" . $row['collection']   . "',
                     HerbNummer   = '" . $row['HerbNummer']   . "',
                     Barcode      = '" . formatUnitID($specimenID)        . "',
                     QRCode       = '" . getStableIdentifier($specimenID) . "'");
}
echo $dbLink->error;