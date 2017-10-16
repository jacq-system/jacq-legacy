<?php
function StableIdentifier ($row)
{
    $HerbNummer = str_replace(' ', '', $row['herbNummer']);
    if ($row['source_id'] == '29') {
        if (strlen(trim($HerbNummer)) > 0){
            $HerbNummer = str_replace('-', '', $HerbNummer);
        }
        else {
            $HerbNummer = ($HerbNummer) ? $HerbNummer : ('JACQ-ID' . $row['specimen_ID']);
            $HerbNummer = str_replace('-', '', $HerbNummer);
        }
        $text = "http://herbarium.bgbm.org/object/" . $HerbNummer;
        $text = "<a href=\"" . $text. '" target="_blank">' . $text . '</a><br/>';
    }
    elseif ($source_id == '27') {
        $text = "https://lagu.jacq.org/object/".$HerbNummer;
        $text = "<a href=\"" . $text. '" target="_blank">' . $text . '</a><br/>';
    }
    else {
        $text = "http://herbarium.jacq.org/object/" . $row['collection'] . $row['herbNummer'];
        $text = "";
    }

    return $text;
}
