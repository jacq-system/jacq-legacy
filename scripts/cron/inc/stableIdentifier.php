<?php

use Jacq\DbAccess;

/**
 * Returns the stable identifier. Tries to make one if none is found in the database
 *
 * @param int $source_id Source-ID
 * @param string|null $HerbNummer Collection Herb.#
 * @param int $specimen_ID Specimen-ID
 * @return string the found or produced stable identifier
 */
function StableIdentifier(int $source_id, ?string $HerbNummer, int $specimen_ID): string
{
    $text = getStableIdentifier($specimen_ID);
    if (empty($text)) {
        $HerbNummer = str_replace(' ', '', $HerbNummer);

        if ($source_id == '29') { // B
            if (strlen(trim($HerbNummer)) > 0) {
                $HerbNummer = str_replace('-', '', $HerbNummer);
            } else {
                $HerbNummer = ($HerbNummer) ?: ('JACQ-ID' . $specimen_ID);
                $HerbNummer = str_replace('-', '', $HerbNummer);
            }
            $text = "https://herbarium.bgbm.org/object/" . $HerbNummer;

        } elseif ($source_id == '27') { // LAGU
            $text = "https://lagu.jacq.org/object/" . $HerbNummer;
        } elseif ($source_id == '48') { // TBI
            $text = "https://tbi.jacq.org/object/" . $HerbNummer;
        } elseif ($source_id == '50') { // HWilling
            if (strlen(trim($HerbNummer)) > 0) {
                $HerbNummer = str_replace('-', '', $HerbNummer);
            } else {
                $HerbNummer = ($HerbNummer) ?: ('JACQ-ID' . $specimen_ID);
                $HerbNummer = str_replace('-', '', $HerbNummer);
            }
            $text = "https://willing.jacq.org/object/" . $HerbNummer;
        } else { // nothing of the above -> empty string
            $text = "";
        }
    }

    return $text;
}

/**
 * get the latest stable identifier from tbl_specimens_stblid
 *
 * @param int $specimenID the specimen-ID
 * @return string the stable identifier
 */
function getStableIdentifier(int $specimenID): string
{
    try {
        $dbLink  = DbAccess::ConnectTo('INPUT');
    } catch (Exception $e) {
        echo $e->__toString() . "\n";
        die();
    }

    $result = $dbLink->query("SELECT stableIdentifier
                              FROM tbl_specimens_stblid
                              WHERE specimen_ID = '$specimenID'
                               AND stableIdentifier IS NOT NULL
                               AND visible = 1
                              ORDER BY timestamp DESC
                              LIMIT 1");
    if ($result && $result->num_rows > 0) {
        return $result->fetch_array()['stableIdentifier'];
    } else {
        return "";
    }
}
