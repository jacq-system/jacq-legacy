#!/usr/bin/php -qC
<?php
require_once './inc/variables.php';

/** @var mysqli $dbLink */
$dbLink = new mysqli($_CONFIG['DATABASE']['JACQ']['host'],
                     $_CONFIG['DATABASE']['JACQ']['readonly']['user'],
                     $_CONFIG['DATABASE']['JACQ']['readonly']['pass'],
                     $_CONFIG['DATABASE']['JACQ']['name']);
if ($dbLink->connect_errno) {
    die("Database not available!");
}
$dbLink->set_charset('utf8');


/**
 * do a mysql query
 *
 * @global mysqli $dbLink link to database
 * @param string $sql query string
 * @return mixed mysqli_result or false if error
 */
function db_query($sql)
{
  global $dbLink;

  $res = $dbLink->query($sql);

  if(!$res){
    echo $sql . "\n"
       . $dbLink->errno . ": " . $dbLink->error . "\n";
  }

  return $res;
}


/**
 * encase text with quotes or return NULL if string is empty
 *
 * @global mysqli $dbLink link to database
 * @param string $text text to quote
 * @return string result
 */
function quoteString($text)
{
    global $dbLink;

    if (strlen($text) > 0) {
        return "'" . $dbLink->real_escape_string($text) . "'";
    } else {
        return "NULL";
    }
}


/**
 * Return a single entry of a single result row
 *
 * @param string $tableName the table to query
 * @param array $attributes array of constraints like 'column name' => 'value'
 * @return mixed result of query as object or NULL if anything went wrong
 */
function findRowByAttributes($tableName, $attributes)
{
    $constraints = array();
    foreach ($attributes as $key => $value) {
        $constraints[] = $key . "=" . quoteString($value);
    }
    $sql = "SELECT * FROM $tableName WHERE " . implode(" AND ", $constraints);

    $result = db_query($sql);
    if (!$result) {
        return NULL;
    }
    return $result->fetch_object();
}

/**
 * Creates a new entry in the UUID minting table for the given type or fetch an existing one
 * derived from jacq_code (protected/component/services/UuidMinterComponent->mint
 *
 * @global mysqli $dbLink link to database
 * @param int|string $type Type of UUID to mint, either the uuid_minter_type_id or the description as string
 * @param int $internal_id Internal ID of object to mint the UUID for
 * @return string generated or fetched uuid
 * @throws Exception
 */
function mint($type, $internal_id)
{
    global $dbLink;

    $internal_id_filtered = intval($internal_id);

    // check internal id for validity
    if( $internal_id_filtered <= 0) {
        throw new Exception("Invalid internal_id '" . $internal_id_filtered . "' passed");
    }

    // if we do not get passed an id, treat it as description string
    if( !is_int($type) ) {
        $uuidMinterType = findRowByAttributes("srvc_uuid_minter_type", array("description" => $type));

        // check if we found a valid entry
        if( $uuidMinterType == NULL ) {
            throw new Exception("Invalid UUID type '" . $type . "' requested");
        }

        // remember actual integer id
        $type = $uuidMinterType->uuid_minter_type_id;
    }

    // check if there is a previously minted UUID for this object
    $uuidMinter = findRowByAttributes('srvc_uuid_minter', array(
        'internal_id' => $internal_id_filtered,
        'uuid_minter_type_id' => $type
    ));
    if( $uuidMinter == NULL ) {
        // create new entry in minter database as we didn't find one
        $sql = "INSERT INTO srvc_uuid_minter SET uuid_minter_type_id = $type, internal_id = '$internal_id_filtered', uuid = UUID()";
        db_query($sql);
        $uuidMinter = findRowByAttributes('srvc_uuid_minter', array('uuid_minter_id' => $dbLink->insert_id));
    }

    return $uuidMinter->uuid;
}

$res_scname = db_query("SELECT taxonID
                        FROM herbarinput.tbl_tax_species
                        WHERE taxonID NOT IN (SELECT internal_id FROM srvc_uuid_minter WHERE uuid_minter_type_id = 1)");
while ($row = $res_scname->fetch_array()) {
    mint(1, $row['taxonID']);
}
$res_citation = db_query("SELECT citationID
                          FROM herbarinput.tbl_lit
                          WHERE citationID NOT IN (SELECT internal_id FROM srvc_uuid_minter WHERE uuid_minter_type_id = 2)");
while ($row = $res_scname->fetch_array()) {
    mint(2, $row['citationID']);
}
$res_specimen = db_query("SELECT specimen_ID
                          FROM herbarinput.tbl_specimens
                          WHERE specimen_ID NOT IN (SELECT internal_id FROM srvc_uuid_minter WHERE uuid_minter_type_id = 3)");
while ($row = $res_scname->fetch_array()) {
    mint(3, $row['specimen_ID']);
}
