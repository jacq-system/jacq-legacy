<?php
function StableIdentifier ($row)
{
    $HerbNummer = str_replace(' ', '',$row['herbNummer']);
if ($row['source_id'] == '29') {
    $HerbNummer = str_replace('-', '', $HerbNummer);

$text = "http://herbarium.bgbm.org/object/".$HerbNummer;
}
else {
$text = "http://herbarium.jacq.org/object/".$row['collection'].$row['herbNummer'];
}
$text = "<a href=\"" . $text. '" target="_blank">'.$text.'</a><br/>';
return $text;

}
