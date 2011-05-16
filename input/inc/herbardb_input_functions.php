<?php
function taxon($row,$withDT=false,$withID=true) {

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

  if ($withID) $text .= " <".$row['taxonID'].">";

  return $text;
}

function taxonWithHybrids($row) {

  if ($row['parent_1_ID'] && $row['parent_2_ID']) {
    $sql = "SELECT tg.genus,
             ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
             ta4.author author4, ta5.author author5,
             te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
             te4.epithet epithet4, te5.epithet epithet5
            FROM tbl_tax_species ts
             LEFT JOIN tbl_tax_authors ta ON ta.authorID=ts.authorID
             LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID=ts.subspecies_authorID
             LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID=ts.variety_authorID
             LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID=ts.subvariety_authorID
             LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID=ts.forma_authorID
             LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID=ts.subforma_authorID
             LEFT JOIN tbl_tax_epithets te ON te.epithetID=ts.speciesID
             LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID=ts.subspeciesID
             LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID=ts.varietyID
             LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID=ts.subvarietyID
             LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID=ts.formaID
             LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID=ts.subformaID
             LEFT JOIN tbl_tax_genera tg ON tg.genID=ts.genID
            WHERE taxonID='".$row['parent_1_ID']."'";
    $row1 = mysql_fetch_array(db_query($sql));
    $sql = "SELECT tg.genus,
             ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
             ta4.author author4, ta5.author author5,
             te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
             te4.epithet epithet4, te5.epithet epithet5
            FROM tbl_tax_species ts
             LEFT JOIN tbl_tax_authors ta ON ta.authorID=ts.authorID
             LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID=ts.subspecies_authorID
             LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID=ts.variety_authorID
             LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID=ts.subvariety_authorID
             LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID=ts.forma_authorID
             LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID=ts.subforma_authorID
             LEFT JOIN tbl_tax_epithets te ON te.epithetID=ts.speciesID
             LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID=ts.subspeciesID
             LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID=ts.varietyID
             LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID=ts.subvarietyID
             LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID=ts.formaID
             LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID=ts.subformaID
             LEFT JOIN tbl_tax_genera tg ON tg.genID=ts.genID
            WHERE taxonID='".$row['parent_2_ID']."'";
    $row2 = mysql_fetch_array(db_query($sql));

    $text = $row1['genus'];
    if ($row1['epithet'])
      $text .= " ".$row1['epithet'].chr(194).chr(183)." ".$row1['author'];
    else
      $text .= chr(194).chr(183);
    $text .= subTaxonItem($row1)." x ".taxonItem($row2)." <".$row['taxonID'].">";
    return $text;
  }
  else
    return taxon($row);
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

  $text = $row['autor']." (".substr($row['jahr'], 0, 4).")";
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

function removeID($item) {

    $pos = strrpos($item, ' <');
    if ($pos !== false) {
        return substr($item, 0, $pos);
    } else {
        return $item;
    }
}