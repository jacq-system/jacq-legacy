#!/usr/bin/php -qC
<?php

require __DIR__ . '/../vendor/autoload.php';

use Jacq\DbAccess;
use Jacq\UuidMinter;

$db = DbAccess::ConnectTo('JACQ');
$minter = new UuidMinter();

$res_scname = $db->queryCatch("SELECT taxonID
                               FROM herbarinput.tbl_tax_species
                               WHERE taxonID NOT IN (SELECT internal_id FROM srvc_uuid_minter WHERE uuid_minter_type_id = 1)");
while ($row = $res_scname->fetch_array()) {
    $minter->mint(1, $row['taxonID']);
}
$res_citation = $db->queryCatch("SELECT citationID
                                 FROM herbarinput.tbl_lit
                                 WHERE citationID NOT IN (SELECT internal_id FROM srvc_uuid_minter WHERE uuid_minter_type_id = 2)");
while ($row = $res_citation->fetch_array()) {
    $minter->mint(2, $row['citationID']);
}
$res_specimen = $db->queryCatch("SELECT specimen_ID
                                 FROM herbarinput.tbl_specimens
                                 WHERE specimen_ID NOT IN (SELECT internal_id FROM srvc_uuid_minter WHERE uuid_minter_type_id = 3)");
while ($row = $res_specimen->fetch_array()) {
    $minter->mint(3, $row['specimen_ID']);
}
