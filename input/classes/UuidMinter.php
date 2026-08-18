<?php

namespace Jacq;

use Exception;

class UuidMinter
{
    /**
     * Creates a new entry in the UUID minting table for the given type or fetch an existing one
     * derived from jacq_code (protected/component/services/UuidMinterComponent->mint
     *
     * @param int|string $type Type of UUID to mint, either the uuid_minter_type_id or the description as string
     * @param int $internal_id Internal ID of object to mint the UUID for
     * @return string generated or fetched uuid
     * @throws Exception
     */
    public function mint(int|string $type, int|string $internal_id): string
    {
        $internal_id = intval($internal_id);

        try {
            $db  = DbAccess::ConnectTo('INPUT');

            // if we do not get passed an id, treat it as description string
            if (is_numeric($type)) {
                // make sure the type exists in the database
                $typeID = $db->query("SELECT uuid_minter_type_id
                                      FROM `jacq_input`.`srvc_uuid_minter_type`
                                      WHERE uuid_minter_type_id = " . intval($type))
                             ->fetch_assoc()['uuid_minter_type_id'];
            } else {
                $typeID = $db->query("SELECT uuid_minter_type_id
                                      FROM `jacq_input`.`srvc_uuid_minter_type`
                                      WHERE description = '" . $db->real_escape_string($type) . "'")
                             ->fetch_assoc()['uuid_minter_type_id'];
            }
            // check if we got a valid typeID and internal_id
            if (!empty($typeID) && $internal_id > 0) {
                // check if there is a previously minted UUID for this object
                $uuid = $db->query("SELECT uuid
                                    FROM `jacq_input`.`srvc_uuid_minter`
                                    WHERE uuid_minter_type_id = $typeID
                                     AND internal_id = $internal_id")
                           ->fetch_assoc()['uuid'];
                if (empty($uuid)) {
                    // check if internal_id exists in database
                    switch ($typeID) {
                        case 1:  // scientific name
                            $row = $db->query("SELECT taxonID FROM tbl_tax_species WHERE taxonID = $internal_id")->fetch_assoc();
                            break;
                        case 2:  // citation
                            $row = $db->query("SELECT citationID FROM tbl_lit tl WHERE citationID = $internal_id")->fetch_assoc();
                            break;
                        case 3: // specimen
                            $row = $db->query("SELECT specimen_ID FROM tbl_specimens ts WHERE specimen_ID = $internal_id")->fetch_assoc();
                            break;
                        default:
                            $row = null;  // no internal ID exists, so no uuid will be generated
                            break;
                    }
                    if (!empty($row)) {
                        // create new entry in minter database as we didn't find one
                        $db->query("INSERT INTO `jacq_input`.`srvc_uuid_minter` SET `uuid_minter_type_id` = $typeID, `internal_id` = '$internal_id', `uuid` = UUID()");
                        $uuid = $db->query("SELECT uuid
                                            FROM `jacq_Input`.`srvc_uuid_minter`
                                            WHERE uuid_minter_type_id = $typeID
                                             AND internal_id = $internal_id")
                                   ->fetch_assoc()['uuid'];
                    }
                }
            }
        } catch (Exception $e) {
            error_log("SEVERE SQL-ERROR IN CLASS. USER-ID = {$_SESSION['uid']}\n" . $e->__toString());
        }

        return $uuid ?? "";
    }

    /**
     * Asks the minter for the UUID of a given taxon
     *
     * @param int|string $taxonID ID of Taxon to get the UUID for
     * @return string fetched uuid
     * @throws Exception
     */
    public function getUUIDfromTaxonID(int|string $taxonID): string
    {
        return $this->mint(1, $taxonID);
    }
}
