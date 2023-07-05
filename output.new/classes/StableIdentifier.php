<?php

namespace Jacq;

use Exception;

class StableIdentifier
{

private string $stblID = '';

/**
 * makes the stable identifier. Tries to make one if none is found in database
 *
 * @param int $specimen_ID Specimen-ID
 * @param int $source_id optional Source-ID
 * @param string $HerbNummer optional Collection Herb.#
 * @return StableIdentifier new constructed class
 * @throws Exception
 */
public static function make(int $specimen_ID, int $source_id = 0,  $HerbNummer = ''): StableIdentifier
{
    return new StableIdentifier($specimen_ID, $source_id, $HerbNummer);
}

/**
 * constructs the stable identifier. Tries to make one if none is found in database
 *
 * @param int $specimen_ID Specimen-ID
 * @param int $source_id Source-ID
 * @param string $HerbNummer Collection Herb.#
 * @throws Exception
 */
private function __construct(int $specimen_ID, int $source_id = 0, $HerbNummer = '')
{
    $this->stblID = $this->getStableIdentifier($specimen_ID);   // get one from database
    if (empty($this->stblID) && !empty($source_id) && !empty($HerbNummer)) {    // if nothing found, try to construct one, if possible
        $HerbNummer = str_replace(' ', '', $HerbNummer);

        if ($source_id == '29') { // B
            if (strlen(trim($HerbNummer)) > 0) {
                $HerbNummer = str_replace('-', '', $HerbNummer);
            } else {
                $HerbNummer = 'JACQID' . $specimen_ID;
            }
            $this->stblID = "https://herbarium.bgbm.org/object/" . $HerbNummer;
        } elseif ($source_id == '27') { // LAGU
            $this->stblID = "https://lagu.jacq.org/object/" . $HerbNummer;
        } elseif ($source_id == '48') { // TBI
            $this->stblID = "https://tbi.jacq.org/object/" . $HerbNummer;
        } elseif ($source_id == '50') { // HWilling
            if (strlen(trim($HerbNummer)) > 0) {
                $HerbNummer = str_replace('-', '', $HerbNummer);
            } else {
                $HerbNummer = 'JACQID' . $specimen_ID;
            }
            $this->stblID = "https://willing.jacq.org/object/" . $HerbNummer;
        }
    }
}

/**
 * @return string
 */
public function getStblID(): string
{
    return $this->stblID;
}

/**
 * get the latest stable identifier from tbl_specimens_stblid
 *
 * @param int $specimenID the specimen-ID
 * @return string the stable identifier
 * @throws Exception
 */
private function getStableIdentifier(int $specimenID): string
{
    $dbLnk2 = DbAccess::ConnectTo('OUTPUT');

    $result = $dbLnk2->query("SELECT stableIdentifier
                              FROM tbl_specimens_stblid
                              WHERE specimen_ID = '$specimenID'
                              ORDER BY timestamp DESC
                              LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_array();
        return  $row['stableIdentifier'];
    } else {
        return '';
    }
}

}
