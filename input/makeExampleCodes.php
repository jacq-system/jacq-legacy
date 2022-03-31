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

$found = $new = 0;
foreach ($specimenIDs as $specimenID) {
    $result = $dbLink->query("SELECT specimen_ID FROM code_examples WHERE specimen_ID = $specimenID");
    if ($result->num_rows > 0) {
        $found++;
    } else {
        $row = $dbLink->query("SELECT s.specimen_ID, mc.source_id, m.source_code, s.collectionID, mc.collection, s.HerbNummer
                               FROM tbl_specimens s
                                LEFT JOIN tbl_management_collections mc ON mc.collectionID = s.collectionID
                                LEFT JOIN meta m ON m.source_id = mc.source_id
                               WHERE s.specimen_ID = $specimenID")
                      ->fetch_assoc();
        if ($row) {
            $new++;
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
    }
}
echo "<html><head></head><body><pre>" . $dbLink->error . "\n" . $found . " items found\n" . $new . " new items</pre></body></html>";