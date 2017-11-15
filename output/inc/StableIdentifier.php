<?php

function StableIdentifier($source_id,$HerbNummer,$specimen_ID, $addHtmlTags = true) {
    $HerbNummer = str_replace(' ', '', $HerbNummer);
    if ($source_id == '29') {
        if (strlen(trim($HerbNummer)) > 0) {
            $HerbNummer = str_replace('-', '', $HerbNummer);
        }
        else {
            $HerbNummer = ($HerbNummer) ? $HerbNummer : ('JACQ-ID' . $specimen_ID);
            $HerbNummer = str_replace('-', '', $HerbNummer);
        }
        $text = "http://herbarium.bgbm.org/object/" . $HerbNummer;
    }
    elseif ($source_id == '27') {
        $text = "https://lagu.jacq.org/object/" . $HerbNummer;
    }
    else {
        $text = "http://herbarium.jacq.org/object/" . $HerbNummer;
        $text = "";
    }

    if ($addHtmlTags && $text) {
        $text = "<a href=\"" . $text . '" target="_blank">' . $text . '</a><br/>';
    }

    return $text;
}
