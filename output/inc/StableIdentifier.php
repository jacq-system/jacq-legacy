<?php
function StableIdentifier ($row)
{
    $HerbNummer = str_replace(' ', '',$row['herbNummer']);
    if ($row['source_id'] == '29') {
        if (strlen(trim($row['herbNummer']))>0){
            $HerbNummer = str_replace('-', '', $HerbNummer);
        }
        else {
            $HerbNummer = ($row['herbNummer']) ? $row['herbNummer'] : ('JACQ-ID' . $row['specimen_ID']);
            $HerbNummer = str_replace('-', '', $HerbNummer);
        }
        $text = "http://herbarium.bgbm.org/object/".$HerbNummer;
    }
    elseif ($row['source_id'] == '27') {
        $text = "http://lagu.jacq.org/object/".$HerbNummer;
    }
    else {
        $text = "http://herbarium.jacq.org/object/".$row['collection'].$row['herbNummer'];
        $text = "";
    }
    $text = "<a href=\"" . $text. '" target="_blank">'.$text.'</a><br/>';
    return $text;

}
