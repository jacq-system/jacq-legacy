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
        // create new entry in minter database as we didn't find one
        $this->db->query("INSERT INTO `jacq_input`.`srvc_uuid_minter` SET `uuid_minter_type_id` = $typeID, `internal_id` = '$id', `uuid` = UUID()");
        $uuid = $this->db->query("SELECT uuid
                                  FROM `jacq_Input`.`srvc_uuid_minter`
                                  WHERE uuid_minter_type_id = $typeID
                                   AND internal_id = $id")
                         ->fetch_assoc()['uuid'];
    }

    return $uuid;
}

}