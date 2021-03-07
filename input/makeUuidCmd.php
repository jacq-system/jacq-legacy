#!/usr/bin/php -qC
<?php
require_once './inc/variables.php';
require_once './inc/uuidMinterFunctions.php';

/** @var mysqli $dbLinkJacq */
$dbLinkJacq = new mysqli($_CONFIG['DATABASE']['JACQ']['host'],
                     $_CONFIG['DATABASE']['JACQ']['readonly']['user'],
                     $_CONFIG['DATABASE']['JACQ']['readonly']['pass'],
                     $_CONFIG['DATABASE']['JACQ']['name']);
if ($dbLinkJacq->connect_errno) {
    die("Database not available!");
}
$dbLinkJacq->set_charset('utf8');


/**
 * do a mysql query
 *
 * @global mysqli $dbLinkJacq link to database
 * @param string $sql query string
 * @return mysqli_result
 */
function dbi_query($sql)
{
  global $dbLinkJacq;

  $res = $dbLinkJacq->query($sql);

  if(!$res){
    echo $sql . "\n"
       . $dbLinkJacq->errno . ": " . $dbLinkJacq->error . "\n";
  }

  return $res;
}


/**
 * encase text with quotes or return NULL if string is empty
 *
 * @global mysqli $dbLinkJacq link to database
 * @param string $text text to quote
 * @return string result
 */
function quoteString($text)
{
    global $dbLinkJacq;

    if (strlen($text) > 0) {
        return "'" . $dbLinkJacq->real_escape_string($text) . "'";
    } else {
        return "NULL";
    }
}


$res_scname = dbi_query("SELECT taxonID
                         FROM herbarinput.tbl_tax_species
                         WHERE taxonID NOT IN (SELECT internal_id FROM srvc_uuid_minter WHERE uuid_minter_type_id = 1)");
while ($row = $res_scname->fetch_array()) {
    mint(1, $row['taxonID']);
}
$res_citation = dbi_query("SELECT citationID
                           FROM herbarinput.tbl_lit
                           WHERE citationID NOT IN (SELECT internal_id FROM srvc_uuid_minter WHERE uuid_minter_type_id = 2)");
while ($row = $res_citation->fetch_array()) {
    mint(2, $row['citationID']);
}
$res_specimen = dbi_query("SELECT specimen_ID
                           FROM herbarinput.tbl_specimens
                           WHERE specimen_ID NOT IN (SELECT internal_id FROM srvc_uuid_minter WHERE uuid_minter_type_id = 3)");
while ($row = $res_specimen->fetch_array()) {
    mint(3, $row['specimen_ID']);
}
