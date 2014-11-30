#!/usr/bin/php -q
<?php
$host = "localhost";      // hostname
$user = "gbif";           // username
$pass = "gbif";           // password
$db   = "herbardb";       // database

ini_set("max_execution_time","3600");

mysql_connect($host,$user,$pass) or die("Database not available!");
mysql_select_db($db) or die ("Access denied!");
mysql_query("SET character set utf8");

function db_query($sql) {
  $result = @mysql_query($sql);
  if (!$result) {
    echo $sql."\n";
    echo mysql_error()."\n";
  }
  return $result;
}
function quoteString($text) {

  if (strlen($text)>0)
    return "'".mysql_escape_string($text)."'";
  else
    return "NULL";
}


//----------  Table sp2000.tbl_taxa  ----------

db_query("truncate sp2000.tbl_taxa");

$sql = "SELECT ts.taxonID, ts.synID, tg.genus, tag.author auth_g, tf.family,
         ta.author author, ta1.author author1, ta2.author author2, ta3.author author3,
         ta4.author author4, ta5.author author5,
         te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
         te4.epithet epithet4, te5.epithet epithet5,
         ttr.rank_abbr,
         tf.family,
         tts.status, tts.status_sp2000
        FROM tbl_tax_species ts, tbl_tax_rank ttr
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
         LEFT JOIN tbl_tax_authors tag ON tag.authorID=tg.authorID
         LEFT JOIN tbl_tax_families tf ON tf.familyID=tg.familyID
         LEFT JOIN tbl_tax_status tts ON tts.statusID=ts.statusID
        WHERE ts.tax_rankID=ttr.tax_rankID
         AND tf.familyID='30'
         AND ts.statusID!='2'";  // only Annonaceae
$result = db_query($sql);
while ($row=mysql_fetch_array($result)) {

  $taxonID = $row['taxonID'];

  $synID = $row['synID'];

  $NameAuthorYearString = $row['genus'];
  if ($row['epithet'])  $NameAuthorYearString .= " ".$row['epithet']." ".$row['author'];
  if ($row['epithet1']) $NameAuthorYearString .= " subsp. ".$row['epithet1']." ".$row['author1'];
  if ($row['epithet2']) $NameAuthorYearString .= " var. ".$row['epithet2']." ".$row['author2'];
  if ($row['epithet3']) $NameAuthorYearString .= " subvar. ".$row['epithet3']." ".$row['author3'];
  if ($row['epithet4']) $NameAuthorYearString .= " forma ".$row['epithet4']." ".$row['author4'];
  if ($row['epithet5']) $NameAuthorYearString .= " subforma ".$row['epithet5']." ".$row['author5'];

  $Family = $row['family'];

  $Genus = $row['genus'];

  $FirstEpithet = $row['epithet'];

  if (strlen($row['epithet5'])>0) {
    $AuthorTeam = $row['author5'];
    $SecondEpithet = $row['epithet5'];
  } elseif (strlen($row['epithet4'])>0) {
    $AuthorTeam = $row['author4'];
    $SecondEpithet = $row['epithet4'];
  } elseif (strlen($row['epithet3'])>0) {
    $AuthorTeam = $row['author3'];
    $SecondEpithet = $row['epithet3'];
  } elseif (strlen($row['epithet2'])>0) {
    $AuthorTeam = $row['author2'];
    $SecondEpithet = $row['epithet2'];
  } elseif (strlen($row['epithet1'])>0) {
    $AuthorTeam = $row['author1'];
    $SecondEpithet = $row['epithet1'];
  } else {
    $AuthorTeam = $row['author'];
    $SecondEpithet = "";
  }
  if (strlen($AuthorTeam)==0 && $SecondEpithet==$row['epithet']) $AuthorTeam = $row['author'];

  $Status = $row['status'];

  $Status_sp2000 = $row['status_sp2000'];

  $Rank_abbr = $row['rank_abbr'];

  $source_id_fk = 7;

  $sql = "INSERT INTO sp2000.tbl_taxa SET ".
         "taxonID=".quoteString($taxonID).", ".
         "synID=".quoteString($synID).", ".
         "NameAuthorYearString=".quoteString($NameAuthorYearString).", ".
         "Family=".quoteString($Family).", ".
         "Genus=".quoteString($Genus).", ".
         "FirstEpithet=".quoteString($FirstEpithet).", ".
         "AuthorTeam=".quoteString($AuthorTeam).", ".
         "SecondEpithet=".quoteString($SecondEpithet).", ".
         "Status=".quoteString($Status).", ".
         "Status_sp2000=".quoteString($Status_sp2000).", ".
         "Rank_abbr=".quoteString($Rank_abbr).", ".
         "source_id_fk=".quoteString($source_id_fk);
  db_query($sql);
}


//----------  Table sp2000.tbl_refs  ----------

db_query("truncate sp2000.tbl_refs");

$sql = "SELECT ti.taxonID, ti.citationID, ti.paginae, ti.figures,
         l.titel, l.suptitel, l.periodicalID, l.vol, l.part, l.jahr,
         la.autor, le.autor as editor, lp.periodical
        FROM tbl_tax_index ti, tbl_lit l, tbl_lit_authors la, sp2000.tbl_taxa
         LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID=l.periodicalID
         LEFT JOIN tbl_lit_authors le ON le.autorID=l.editorsID
        WHERE ti.citationID=l.citationID
         AND l.autorID=la.autorID
         AND ti.taxonID=sp2000.tbl_taxa.taxonID";
$result = db_query($sql);
while ($row=mysql_fetch_array($result)) {

  $citationID = $row['citationID'];

  $taxonID_fk = $row['taxonID'];

  $NomenclaturalReference = $row['autor']." (".$row['jahr'].")";
  if ($row['suptitel']) $NomenclaturalReference .= " in ".$row['editor'].": ".$row['suptitel'];
  if ($row['periodicalID']) $NomenclaturalReference .= " ".$row['periodical'];
  $NomenclaturalReference .= " ".$row['vol'];
  if ($row['part']) $NomenclaturalReference .= " (".$row['part'].")";
  $NomenclaturalReference .= ": ".$row['paginae'].". ".$row['figures'];

  $autor = $row['autor'];

  $jahr = $row['jahr'];

  $titel = $row['titel'];

  $paginae = $row['paginae'];

  $figures = $row['figures'];

  $sql = "INSERT INTO sp2000.tbl_refs SET ".
         "citationID=".quoteString($citationID).", ".
         "taxonID_fk=".quoteString($taxonID_fk).", ".
         "NomenclaturalReference=".quoteString($NomenclaturalReference).", ".
         "autor=".quoteString($autor).", ".
         "jahr=".quoteString($jahr).", ".
         "titel=".quoteString($titel).", ".
         "paginae=".quoteString($paginae).", ".
         "figures=".quoteString($figures);
  db_query($sql);
}
?>