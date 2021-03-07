<?php
class IdentifierMapper extends Mapper
{

public function getUuid($type, $id)
{
    if (is_numeric($type)) {
        $typeID = intval($type);
    } else {
        $typeID = $this->db->query("SELECT uuid_minter_type_id
                                    FROM `jacq_input`.`srvc_uuid_minter_type`
                                    WHERE description = '" . $this->db->real_escape_string($type) . "'")
                           ->fetch_assoc()['uuid_minter_type_id'];
    }
    $uuid = $this->db->query("SELECT uuid
                              FROM `jacq_input`.`srvc_uuid_minter`
                              WHERE uuid_minter_type_id = $typeID
                               AND internal_id = $id")
                     ->fetch_assoc()['uuid'];
    if (empty($uuid) && $typeID > 0) {
        // check if internal_id exists in database
        switch ($typeID) {
            case 1:  // scientific name
                $row = $this->db->query("SELECT taxonID FROM tbl_tax_species WHERE taxonID = $id")->fetch_assoc();
                break;
            case 2:  // citation
                $row = $this->db->query("SELECT citationID FROM tbl_lit tl WHERE citationID = $id")->fetch_assoc();
                break;
            case 3: // specimen
                $row = $this->db->query("SELECT specimen_ID FROM tbl_specimens ts WHERE specimen_ID = $id")->fetch_assoc();
                break;
            default:
                $row = null;  // no internal ID exists, so no uuid will be generated
                break;
        }
        if (!empty($row)) {
            // create new entry in minter database as we didn't find one
            $this->db->query("INSERT INTO `jacq_input`.`srvc_uuid_minter` SET `uuid_minter_type_id` = $typeID, `internal_id` = '$id', `uuid` = UUID()");
            $uuid = $this->db->query("SELECT uuid
                                      FROM `jacq_Input`.`srvc_uuid_minter`
                                      WHERE uuid_minter_type_id = $typeID
                                       AND internal_id = $id")
                             ->fetch_assoc()['uuid'];
        }
    }

    return $uuid;
}

public function getIDs($uuid)
{
    return $this->db->query("SELECT m.`internal_id`, mt.`uuid_minter_type_id` AS type_id, mt.`description` AS type
                             FROM `jacq_input`.`srvc_uuid_minter` m, `jacq_input`.`srvc_uuid_minter_type` mt
                             WHERE m.`uuid_minter_type_id` = mt.`uuid_minter_type_id`
                              AND m.`uuid` = '$uuid'")
                    ->fetch_assoc();
}

}