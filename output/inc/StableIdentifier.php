<?php

function StableIdentifier($row, $addHtmlTags = true) {
    $HerbNummer = str_replace(' ', '', $row['HerbNummer']);
    if ($row['source_id'] == '29') {
        if (strlen(trim($HerbNummer)) > 0) {
            $HerbNummer = str_replace('-', '', $HerbNummer);
        }
        else {
            $HerbNummer = ($HerbNummer) ? $HerbNummer : ('JACQ-ID' . $row['specimen_ID']);
            $HerbNummer = str_replace('-', '', $HerbNummer);
        }
        $text = "http://herbarium.bgbm.org/object/" . $HerbNummer;
    }
    elseif ($row['source_id'] == '27') {
        $text = "https://lagu.jacq.org/object/" . $HerbNummer;
    }
    else {
        $text = "http://herbarium.jacq.org/object/" . $row['collection'] . $row['HerbNummer'];
        $text = "";
    }

    if ($addHtmlTags && $text) {
        $text = "<a href=\"" . $text . '" target="_blank">' . $text . '</a><br/>';
    }

    return $text;
}
