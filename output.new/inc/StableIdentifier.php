<?php
function StableIdentifier($source_id, $HerbNummer, $specimen_ID, $addHtmlTags = true)
{
    $text = getStableIdentifier($specimen_ID);
    if (empty($text)) {
        $HerbNummer = str_replace(' ', '', $HerbNummer);
        $protocol   = (!empty($_SERVER['HTTPS'])) ? "https://" : "http://";

        if ($source_id == '29') { // B
            if (strlen(trim($HerbNummer)) > 0) {
                $HerbNummer = str_replace('-', '', $HerbNummer);
            } else {
                $HerbNummer = ($HerbNummer) ? $HerbNummer : ('JACQ-ID' . $specimen_ID);
                $HerbNummer = str_replace('-', '', $HerbNummer);
            }
            $text = $protocol . "herbarium.bgbm.org/object/" . $HerbNummer;

        } elseif ($source_id == '27') { // LAGU
            $text = $protocol . "lagu.jacq.org/object/" . $HerbNummer;
        } elseif ($source_id == '48') { // TBI
            $text = $protocol . "tbi.jacq.org/object/" . $HerbNummer;
        } elseif ($source_id == '50') { // HWilling
            if (strlen(trim($HerbNummer)) > 0) {
                $HerbNummer = str_replace('-', '', $HerbNummer);
            } else {
                $HerbNummer = ($HerbNummer) ? $HerbNummer : ('JACQ-ID' . $specimen_ID);
                $HerbNummer = str_replace('-', '', $HerbNummer);
            }
            $text = $protocol . "willing.jacq.org/object/" . $HerbNummer;
        } else { // nothing of the above -> empty string
//            $text = $protocol . "herbarium.jacq.org/object/" . $HerbNummer;
            $text = "";
        }
    }

    if ($addHtmlTags && $text) {
        $text = "<a href=\"" . $text . '" target="_blank">' . $text . '</a><br/>';
    }

    return $text;
}

/**
 * get the latest stable identifier from tbl_specimens_stblid
 *
 * @param int $specimenID the specimen-ID
 * @return string the stable identifier
 */
function getStableIdentifier($specimenID)
{
    global $dbLink;

    /** @var mysqli_result $result */
    $result = $dbLink->query("SELECT stableIdentifier
                              FROM tbl_specimens_stblid
                              WHERE specimen_ID = '" . intval($specimenID) . "'
                              ORDER BY timestamp DESC
                              LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_array();
        return $row['stableIdentifier'];
    } else {
        return "";
    }
}
