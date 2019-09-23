<?php
function StableIdentifier($source_id, $HerbNummer, $specimen_ID, $addHtmlTags = true)
{
    $HerbNummer = str_replace(' ', '', $HerbNummer);
    $protocol   = ($_SERVER['HTTPS']) ? "https://" : "http://";

    if ($source_id == '29') {
        if (strlen(trim($HerbNummer)) > 0) {
            $HerbNummer = str_replace('-', '', $HerbNummer);
        } else {
            $HerbNummer = ($HerbNummer) ? $HerbNummer : ('JACQ-ID' . $specimen_ID);
            $HerbNummer = str_replace('-', '', $HerbNummer);
        }
        $text = $protocol . "herbarium.bgbm.org/object/" . $HerbNummer;

    } elseif ($source_id == '27') {
        $text = $protocol . "lagu.jacq.org/object/" . $HerbNummer;
    } elseif ($source_id == '48') {
        $text = $protocol . "tbi.jacq.org/object/" . $HerbNummer;
    } elseif ($source_id == '50') {
        if (strlen(trim($HerbNummer)) > 0) {
            $HerbNummer = str_replace('-', '', $HerbNummer);
        } else {
            $HerbNummer = ($HerbNummer) ? $HerbNummer : ('JACQ-ID' . $specimen_ID);
            $HerbNummer = str_replace('-', '', $HerbNummer);
        }
        $text = $protocol . "willing.jacq.org/object/" . $HerbNummer;
    } else {
        $text = $protocol . "herbarium.jacq.org/object/" . $HerbNummer;
        $text = "";
    }

    if ($addHtmlTags && $text) {
        $text = "<a href=\"" . $text . '" target="_blank">' . $text . '</a><br/>';
    }

    return $text;
}