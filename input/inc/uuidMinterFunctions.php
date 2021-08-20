<?php
/**
 * Return a single entry of a single result row
 *
 * @global mysqli $dbLink link to database jacq_input
 * @param string $tableName the table to query
 * @param array $attributes array of constraints like 'column name' => 'value'
 * @return mixed result of query as object or NULL if anything went wrong
 */
function findRowByAttributes($tableName, $attributes)
{
    global $dbLink;

    $constraints = array();
    foreach ($attributes as $key => $value) {
        $constraints[] = $key . "=" . quoteString($value);
    }
    $sql = "SELECT * FROM `jacq_input`.`$tableName` WHERE " . implode(" AND ", $constraints);

    $result = $dbLink->query($sql);
    if (!$result) {
        return NULL;
    }
    return $result->fetch_object();
}

/**
 * Creates a new entry in the UUID minting table for the given type or fetch an existing one
 * derived from jacq_code (protected/component/services/UuidMinterComponent->mint
 *
 * @global mysqli $dbLink link to database jacq_input
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
        $dbLink->query("INSERT INTO `jacq_input`.`srvc_uuid_minter` SET `uuid_minter_type_id` = $type, `internal_id` = '$internal_id_filtered', `uuid` = UUID()");
        $uuidMinter = findRowByAttributes('srvc_uuid_minter', array('uuid_minter_id' => $dbLink->insert_id));
    }

    return $uuidMinter->uuid;
}
