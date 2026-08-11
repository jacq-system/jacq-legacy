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

}
