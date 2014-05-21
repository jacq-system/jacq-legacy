<?php
function taxon($row,$withDT=false) {

  $text = $row['genus'];
  if ($row['epithet'])
    $text .= " ".$row['epithet'].chr(194).chr(183)." ".$row['author'];
  else
    $text .= chr(194).chr(183);
  if ($row['epithet1']) $text .= " subsp. ".$row['epithet1']." ".$row['author1'];
  if ($row['epithet2']) $text .= " var. ".$row['epithet2']." ".$row['author2'];
  if ($row['epithet3']) $text .= " subvar. ".$row['epithet3']." ".$row['author3'];
  if ($row['epithet4']) $text .= " forma ".$row['epithet4']." ".$row['author4'];
  if ($row['epithet5']) $text .= " subforma ".$row['epithet5']." ".$row['author5'];

  if ($withDT) $text .= " ".$row['DallaTorreIDs'].$row['DallaTorreZusatzIDs'];

  return $text." <".$row['taxonID'].">";
}

function taxonAccepted($row) {

  $text = $row['genus_a'];
  if ($row['epithet_a'])
    $text .= " ".$row['epithet_a'].chr(194).chr(183)." ".$row['author_a'];
  else
    $text .= chr(194).chr(183);
  if ($row['epithet1_a']) $text .= " subsp. ".$row['epithet1_a']." ".$row['author1_a'];
  if ($row['epithet2_a']) $text .= " var. ".$row['epithet2_a']." ".$row['author2_a'];
  if ($row['epithet3_a']) $text .= " subvar. ".$row['epithet3_a']." ".$row['author3_a'];
  if ($row['epithet4_a']) $text .= " forma ".$row['epithet4_a']." ".$row['author4_a'];
  if ($row['epithet5_a']) $text .= " subforma ".$row['epithet5_a']." ".$row['author5_a'];

  return $text." <".$row['taxonID_a'].">";
}

function protolog($row) {

  $text = $row['autor']." (".$row['jahr'].")";
  if ($row['suptitel']) $text .= " in ".$row['editor'].": ".$row['suptitel'];
  if ($row['periodicalID']) $text .= " ".$row['periodical'];
  $text .= " ".$row['vol'];
  if ($row['part']) $text .= " (".$row['part'].")";
  $text .= ": ".$row['pp'].".";

  return $text." <".$row['citationID'].">";
}

function sortItem($typ,$id) {

  if ($typ==$id)
    return "&nbsp;&nbsp;v";
  else if ($typ==-$id)
    return "&nbsp;&nbsp;^";
}

function taxonItem($row) {

  $text = $row['genus'];
  if ($row['epithet'])  $text .= " ".$row['epithet']." ".$row['author'];
  if ($row['epithet1']) $text .= " subsp. ".$row['epithet1']." ".$row['author1'];
  if ($row['epithet2']) $text .= " var. ".$row['epithet2']." ".$row['author2'];
  if ($row['epithet3']) $text .= " subvar. ".$row['epithet3']." ".$row['author3'];
  if ($row['epithet4']) $text .= " forma ".$row['epithet4']." ".$row['author4'];
  if ($row['epithet5']) $text .= " subforma ".$row['epithet5']." ".$row['author5'];

  return $text;
}

function subTaxonItem($row) {

  $text = "";
  if ($row['epithet1']) $text .= " subsp. ".$row['epithet1']." ".$row['author1'];
  if ($row['epithet2']) $text .= " var. ".$row['epithet2']." ".$row['author2'];
  if ($row['epithet3']) $text .= " subvar. ".$row['epithet3']." ".$row['author3'];
  if ($row['epithet4']) $text .= " forma ".$row['epithet4']." ".$row['author4'];
  if ($row['epithet5']) $text .= " subforma ".$row['epithet5']." ".$row['author5'];

  return $text;
}
?>