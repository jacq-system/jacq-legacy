<?php

namespace Jacq;

use Exception;

class Tools
{
    /**
     * Return the scientific name for a given taxon_id
     *
     * @param int|null $taxon_id Taxon-id to search for
     * @param bool $withDT Include dallatorre-id, defaults to no
     * @param bool $withID Include taxon-id, defaults to no
     * @param bool $bAvoidHybridFormula avoid hybrids, defaults to no
     * @return string
     */
    public static function getScientificName (?int $taxon_id, bool $withDT = false, bool $withID = true, bool $bAvoidHybridFormula = false): string
    {
        // wrong call with empty taxon-ID
        if (empty($taxon_id)) {
            return '';
        }

        $bAvoidHybridFormula = intval($bAvoidHybridFormula); // Translation between mysql boolean (tinyint) and php boolean

        try {
            $db = DbAccess::ConnectTo('INPUT');
        } catch (Exception) {
            return '';
        }

        // Use stored procedure to fetch the scientific name
        $row = $db->queryCatch("SELECT `herbar_view`.GetScientificName($taxon_id, $bAvoidHybridFormula) AS 'ScientificName'")->fetch_assoc();

        // Extend scientific name with additional information
        $scientificName = $row['ScientificName'] ?? "";
        if ($withDT) {
            $row = $db->queryCatch("SELECT `tg`.`DallaTorreIDs`, `tg`.`DallaTorreZusatzIDs`
                                    FROM `tbl_tax_species` `ts`
                                     LEFT JOIN `tbl_tax_genera` `tg` ON `tg`.`genID` = `ts`.`genID`
                                    WHERE `ts`.`taxonID` = '$taxon_id'")
                      ->fetch_assoc();

            $scientificName .= " " . ($row['DallaTorreIDs'] ?? '') . ($row['DallaTorreZusatzIDs'] ?? '');

        }
        if ($withID) {
            $scientificName .= " <$taxon_id>";
        }

        return $scientificName;
    }

    /**
     * constructs the link to the image on an IIIF-Server for a specimen if iiif for this source is activated
     *
     * @param int $specimenID specimen-ID
     * @return string link to the image
     */
    public static function getIiifLink(int $specimenID): string
    {
        try {
            $db = DbAccess::ConnectTo('INPUT');
        } catch (Exception) {
            return '';
        }

        $image = $db->queryCatch("SELECT tid.iiif_capable, tid.iiif_url, ph.specimenID AS phaidraID
                                  FROM tbl_specimens s
                                   LEFT JOIN herbar_pictures.phaidra_cache ph ON ph.specimenID = s.specimen_ID
                                   LEFT JOIN tbl_management_collections mc ON mc.collectionID = s.collectionID
                                   LEFT JOIN tbl_img_definition tid ON tid.source_id_fk = mc.source_id
                                  WHERE s.specimen_ID = '$specimenID'")
                        ->fetch_assoc();
        if ($image['iiif_capable'] || $image['phaidraID']) {
            $config = Settings::Load();
            $ch = curl_init($config->get('JACQ_SERVICES') . "iiif/manifestUri/$specimenID");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $curl_response = curl_exec($ch);
            if ($curl_response !== false) {
                $curl_result = json_decode($curl_response, true);
                $manifest = $curl_result['uri'];
            } else {
                $manifest = "";
            }
            curl_close($ch);

            return $image['iiif_url'] . "?manifest=$manifest";
        } else {
            return '';
        }
    }

    /**
     * extracts an ID from a string. ID must be enclosed in "<>" brackets and be positioned at the end
     *
     * @param string $text string to extract ID from
     * @return string ID enclosed in single quotes or the string "NULL" (without quotes)
     */
    public static function extractID (string $text): string
    {
        $pos1 = strrpos($text, "<");
        $pos2 = strpos($text, ">", $pos1);
        if ($pos1 !== false && $pos2 !== false) {
            if (intval(substr($text, $pos1 + 1, $pos2 - $pos1 - 1))) {
                return "'" . intval(substr($text, $pos1 + 1, $pos2 - $pos1 - 1)) . "'";
            } else {
                return "NULL"; // no ID found
            }
        } else {
            return "NULL"; // no ID found
        }
    }

    /**
     * Checks if the given right is available for the current user session.
     *
     * @param string $right The name of the right to be checked.
     * @return bool Returns true if the user has the specified right; otherwise, returns false.
     */
    public static function checkRight(string $right): bool
    {
        try {
            $db = DbAccess::ConnectTo('INPUT');
        } catch (Exception) {
            return false;
        }

        if (str_starts_with($right, 'unlock_')) {
            $result = $db->queryCatch("SELECT `table`
                                       FROM herbarinput_log.tbl_herbardb_unlock
                                       WHERE `table` = " . $db->quoteString(substr($right, 7)) . "
                                        AND groupID = '" . intval($_SESSION['gid']) . "'");
            return ($result->num_rows > 0);
        } else {
            $row = $db->queryCatch("SELECT *
                                    FROM herbarinput_log.tbl_herbardb_users, herbarinput_log.tbl_herbardb_groups
                                    WHERE herbarinput_log.tbl_herbardb_users.groupID = herbarinput_log.tbl_herbardb_groups.groupID
                                     AND userID = '" . intval($_SESSION['uid']) . "'")
                      ->fetch_array();
            if (isset($row[$right]) && $row[$right]) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Checks if a row with a given id from a given table is in the state "locked"
     *
     * @param string $table The name of the table to be checked.
     * @param object|int $id The ID of the record to be checked.
     * @return bool Returns true if the row is locked; otherwise, returns false.
     */
    public static function isLocked(string $table, object|int $id): bool
    {
        try {
            $db = DbAccess::ConnectTo('INPUT');
        } catch (Exception) {
            return false;
        }

        if (is_numeric($id)) {
            $PID = "";
            $result = $db->queryCatch("SHOW INDEX FROM " . $db->real_escape_string($table));
            while ($row = $result->fetch_array()) {
                if ($row['Key_name'] == 'PRIMARY') {
                    $PID = $row['Column_name'];
                    break;
                }
            }
            if ($PID) {
                $row = $db->queryCatch("SELECT locked FROM " . $db->real_escape_string($table) . " WHERE $PID = '" . intval($id) . "'")->fetch_array();
                return (!empty($row["locked"]));
            }
        } else if (is_object($id)) {
            $where = $id->getWhere();
            if (!$where) {
                return false;
            }
            $row = $db->queryCatch("SELECT locked FROM " . $db->real_escape_string($table) . " WHERE $where")->fetch_array();
            return (!empty($row["locked"]));
        }

        return false;
    }

    /**
     * format the unit-ID (HerbNummer) of a specimen according to tbl_labels_numbering
     *
     * @param int $specimenID Specimen ID
     * @return string formatted unit-ID
     */
    public static function formatUnitID(int $specimenID): string
    {
        try {
            $db = DbAccess::ConnectTo('INPUT');
        } catch (Exception) {
            return '';
        }

        $rowSpecimen = $db->queryCatch("SELECT s.HerbNummer, s.specimen_ID, s.collectionID, m.source_code, m.source_id
                                        FROM tbl_specimens s
                                         JOIN tbl_management_collections mc ON mc.collectionID = s.collectionID
                                         JOIN meta m ON m.source_id = mc.source_id
                                        WHERE s.specimen_ID = $specimenID")
                          ->fetch_array();

        $unitID = $rowSpecimen['source_code'];
        if (!empty($rowSpecimen['HerbNummer'])) {
            // first check on collectionID and presence of a replace char
            $result = $db->queryCatch("SELECT digits, replace_char
                                       FROM tbl_labels_numbering
                                       WHERE replace_char IS NOT NULL
                                        AND collectionID_fk = {$rowSpecimen['collectionID']}");
            $found = false;
            if ($result->num_rows > 0) {
                // check the presence of the replace char in HerbNummer
                $rows = $result->fetch_all(MYSQLI_ASSOC);
                foreach ($rows as $line) {
                    if (str_contains($rowSpecimen['HerbNummer'], $line['replace_char'])) {
                        $row = $line;
                        $found = true;
                        break;
                    }
                }
            }
            if (!$found) {
                // now check on collectionID and absence of a replace char
                $result = $db->queryCatch("SELECT digits, replace_char
                                           FROM tbl_labels_numbering
                                           WHERE replace_char IS NULL
                                            AND collectionID_fk = {$rowSpecimen['collectionID']}");
                if ($result->num_rows > 0) {
                    $row = $result->fetch_array();
                } else {
                    // now check on sourceID and presence of a replace char
                    $result = $db->queryCatch("SELECT digits, replace_char
                                               FROM tbl_labels_numbering
                                               WHERE collectionID_fk IS NULL
                                                AND replace_char IS NOT NULL
                                                AND sourceID_fk = {$rowSpecimen['source_id']}");
                    if ($result->num_rows > 0) {
                        // check the presence of the replace char in HerbNummer
                        $rows = $result->fetch_all(MYSQLI_ASSOC);
                        foreach ($rows as $line) {
                            if (str_contains($rowSpecimen['HerbNummer'], $line['replace_char'])) {
                                $row = $line;
                                $found = true;
                                break;
                            }
                        }
                    }
                    if (!$found) {
                        // now check on sourceID and absence of a replace char
                        $result = $db->queryCatch("SELECT digits, replace_char
                                                   FROM tbl_labels_numbering
                                                   WHERE collectionID_fk IS NULL
                                                    AND replace_char IS NULL
                                                    AND sourceID_fk = {$rowSpecimen['source_id']}");
                        if ($result->num_rows > 0) {
                            $row = $result->fetch_array();
                        } else {
                            // fallback
                            $row = ['digits' => 7, 'replace_char' => ''];
                        }
                    }
                }
            }
            $digits  = $row['digits'];
            $replace = $row['replace_char'];

            if ($replace) {
                $parts = explode($replace, $rowSpecimen['HerbNummer'], 2);
                $unitID .= $parts[0] . sprintf("%0{$digits}d", $parts[1]);
            } else {
                $unitID .= sprintf("%0{$digits}d", $rowSpecimen['HerbNummer']);
            }
        } else {
            $unitID .= sprintf("%07d", $rowSpecimen['specimen_ID']);
        }

        return $unitID;
    }

}
