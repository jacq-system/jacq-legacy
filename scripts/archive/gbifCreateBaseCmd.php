#!/usr/bin/php -q
<?php
$host = "localhost";  // hostname
$user = "gbif";    // username
$pass = "gbif";           // password
$db   = "herbardb";   // database

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


function makeTaxon($row) {
  $text = $row['genus'];
  if ($row['epithet'])  $text .= " ".$row['epithet']." ".$row['author'];
  if ($row['epithet1']) $text .= " subsp. ".$row['epithet1']." ".$row['author1'];
  if ($row['epithet2']) $text .= " var. ".$row['epithet2']." ".$row['author2'];
  if ($row['epithet3']) $text .= " subvar. ".$row['epithet3']." ".$row['author3'];
  if ($row['epithet4']) $text .= " forma ".$row['epithet4']." ".$row['author4'];
  if ($row['epithet5']) $text .= " subforma ".$row['epithet5']." ".$row['author5'];

  return $text;
}

function makeHybrid($taxonID) {
  $sql = "SELECT parent_1_ID, parent_2_ID
          FROM tbl_tax_hybrids
          WHERE taxon_ID_fk='$taxonID'";
  $row = mysql_fetch_array(db_query($sql));

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

  return makeTaxon($row1)." x ".makeTaxon($row2);
}

//create Databases "tbl_prj_gbif_pilot*"
$dbs[0]['name'] = "tbl_prj_gbif_pilot_wu";
$dbs[0]['limit'] = "mc.source_id='1'";
$dbs[1]['name'] = "tbl_prj_gbif_pilot_w";
$dbs[1]['limit'] = "mc.source_id='6'";
$dbs[2]['name'] = "tbl_prj_gbif_pilot_gzu";
$dbs[2]['limit'] = "mc.source_id='4'";
$dbs[3]['name'] = "tbl_prj_gbif_pilot_gjo";
$dbs[3]['limit'] = "mc.source_id='5'";
for ($i=0;$i<4;$i++) {
  db_query("truncate gbif_pilot.".$dbs[$i]['name']);

  $sql = "SELECT wu.specimen_ID, wu.HerbNummer, wu.identstatusID, wu.checked, wu.accessible, ".
          "wu.taxonID, wu.series_number, wu.Nummer, wu.alt_number, wu.Datum, wu.Datum2, ".
          "wu.det, wu.typified, wu.taxon_alt, wu.Bezirk, ".
          "wu.Coord_W, wu.W_Min, wu.W_Sec, wu.Coord_N, wu.N_Min, wu.N_Sec, ".
          "wu.Coord_S, wu.S_Min, wu.S_Sec, wu.Coord_E, wu.E_Min, wu.E_Sec, ".
          "wu.quadrant, wu.quadrant_sub, wu.exactness, wu.altitude_min, wu.altitude_max, ".
          "wu.Fundort, wu.habitat, wu.habitus, wu.Bemerkungen, wu.digital_image, ".
          "wu.garten, wu.voucherID, wu.ncbi_accession, ".
          "wu.collectionID, wu.typusID, wu.NationID, wu.provinceID, ".
          "c.SammlerID, c.Sammler, c2.Sammler_2ID, c2.Sammler_2, ".
          "ts.taxonID, ts.statusID, tg.genus, tag.author auth_g, tf.family, ".
          "ta.author author, ta1.author author1, ta2.author author2, ta3.author author3, ".
          "ta4.author author4, ta5.author author5, ".
          "te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3, ".
          "te4.epithet epithet4, te5.epithet epithet5, ".
          "ttr.rank, ".
          "gn.nation_engl, gn.iso_alpha_3_code, gp.provinz, ss.series, mc.source_id, mc.coll_gbif_pilot, ".
          "gbif_pilot.meta.source_name ".
         "FROM tbl_specimens wu, tbl_collector c, tbl_tax_species ts, tbl_tax_rank ttr, ".
          "tbl_management_collections mc, gbif_pilot.meta ".
          "LEFT JOIN tbl_collector_2 c2 ON c2.Sammler_2ID=wu.Sammler_2ID ".
          "LEFT JOIN tbl_tax_authors ta ON ta.authorID=ts.authorID ".
          "LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID=ts.subspecies_authorID ".
          "LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID=ts.variety_authorID ".
          "LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID=ts.subvariety_authorID ".
          "LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID=ts.forma_authorID ".
          "LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID=ts.subforma_authorID ".
          "LEFT JOIN tbl_tax_epithets te ON te.epithetID=ts.speciesID ".
          "LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID=ts.subspeciesID ".
          "LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID=ts.varietyID ".
          "LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID=ts.subvarietyID ".
          "LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID=ts.formaID ".
          "LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID=ts.subformaID ".
          "LEFT JOIN tbl_tax_genera tg ON tg.genID=ts.genID ".
          "LEFT JOIN tbl_tax_authors tag ON tag.authorID=tg.authorID ".
          "LEFT JOIN tbl_tax_families tf ON tf.familyID=tg.familyID ".
          "LEFT JOIN tbl_geo_nation gn ON gn.nationID=wu.NationID ".
          "LEFT JOIN tbl_geo_province gp ON gp.provinceID=wu.provinceID ".
          "LEFT JOIN tbl_specimens_series ss ON ss.seriesID=wu.seriesID ".
         "WHERE wu.SammlerID=c.SammlerID ".
          "AND wu.taxonID=ts.taxonID ".
          "AND ts.tax_rankID=ttr.tax_rankID ".
          "AND wu.collectionID=mc.collectionID ".
          "AND mc.source_id=gbif_pilot.meta.source_id ".
          "AND ".$dbs[$i]['limit'];
  $result = db_query($sql);
  while ($row=mysql_fetch_array($result)) {

    $UnitID = $row['specimen_ID'];

    if ($row['statusID']==1 && strlen($row['epithet'])==0 && strlen($row['author'])==0) 
      $NameAuthorYearString = makeHybrid($row['taxonID']);
    else
      $NameAuthorYearString = makeTaxon($row);

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

    $HybridFlag = ($row['statusID']==1) ? "1" : "";

    $Rank = $row['rank'];

    $HigherTaxon = $row['family'];

    $ISODateTimeBegin = (trim($row['Datum'])=="s.d.") ? "" : $row['Datum'];

    $ISODateTimeEnd = $row['Datum2'];

    $LocalityText = $row['Fundort'];

    $LocalityDetailed = $row['Fundort'];

    $CountryName = $row['nation_engl'];

    $ISO3Letter = $row['iso_alpha_3_code'];

    $NamedAreaName = ($row['nation_engl']=="Austria") ? substr($row['provinz'],0,2) : $row['provinz'];

    $NamedAreaClass = ($row['nation_engl']=="Austria") ? "Bundesland" : "";

    $MeasurmentLowerValue = $row['altitude_min'];

    $MeasurmentUpperValue = $row['altitude_max'];

    if ($row['Coord_S']>0 || $row['S_Min']>0 || $row['S_Sec']>0)
      $lat = -($row['Coord_S'] + $row['S_Min'] / 60 + $row['S_Sec'] / 3600);
    else if ($row['Coord_N']>0 || $row['N_Min']>0 || $row['N_Sec']>0)
      $lat = $row['Coord_N'] + $row['N_Min'] / 60 + $row['N_Sec'] / 3600;
    else
      $lat = 0;
    $LatitudeDecimal = ($lat) ? sprintf("%1.5f",$lat) : "";

    if ($row['Coord_W']>0 || $row['W_Min']>0 || $row['W_Sec']>0)
      $lon = -($row['Coord_W'] + $row['W_Min'] / 60 + $row['W_Sec'] / 3600);
    else if ($row['Coord_E']>0 || $row['E_Min']>0 || $row['E_Sec']>0)
      $lon = $row['Coord_E'] + $row['E_Min'] / 60 + $row['E_Sec'] / 3600;
    else
      $lon = 0;
    $LongitudeDecimal = ($lon) ? sprintf("%1.5f",$lon) : "";

    $SpatialDatum = ($lat || $lon) ? "WGS84" : "";

    $exactness = $row['exactness'];

    $GatheringAgentsText = $row['Sammler'];
    if (strstr($row['Sammler_2'],"&") || strstr($row['Sammler_2'],"et al."))
      $GatheringAgentsText .= " et al.";
    elseif ($row['Sammler_2'])
      $GatheringAgentsText .= " & ".$row['Sammler_2'];
    if ($row['series_number']) {
      if ($row['Nummer']) $GatheringAgentsText .= " ".$row['Nummer'];
      if ($row['alt_number']) $GatheringAgentsText .= " ".$row['alt_number'];
      if (strstr($row['alt_number'],"s.n.")) $GatheringAgentsText .= " [".$row['Datum']."]";
      if ($row['series']) $GatheringAgentsText .= " ".$row['series'];
      $GatheringAgentsText .= " ".$row['series_number'];
    }
    else {
      if ($row['series']) $GatheringAgentsText .= " ".$row['series'];
      if ($row['Nummer']) $GatheringAgentsText .= " ".$row['Nummer'];
      if ($row['alt_number']) $GatheringAgentsText .= " ".$row['alt_number'];
      if (strstr($row['alt_number'],"s.n.")) $GatheringAgentsText .= " [".$row['Datum']."]";
    }

    $IdentificationHistory = $row['taxon_alt'];

    $NamedCollection = $row['coll_gbif_pilot'];

    $UnitIDNumeric = $row['HerbNummer'];

    $UnitDescription = $row['Bemerkungen'];

    $source_id_fk = $row['source_id'];

    $det = $row['det'];

    if ($row['digital_image'])
      $image_url = "http://herbarium.botanik.univie.ac.at/database/img/imgBrowser.php?name=".$row['specimen_ID'];
    else
      $image_url = "";

    $sql = "SELECT firstname, surname, timestamp ".
           "FROM herbarinput_log.log_specimens, herbarinput_log.tbl_herbardb_users ".
           "WHERE herbarinput_log.log_specimens.userID=herbarinput_log.tbl_herbardb_users.userID ".
            "AND specimenID='".$row['specimen_ID']."' ".
           "ORDER BY timestamp DESC";
    $resultLog = db_query($sql);
    $rowLog = mysql_fetch_array($resultLog);
    if ($rowLog) {
      $LastEditor = $rowLog['surname'].", ".$rowLog['firstname'];
      $DateLastEdited = $rowLog['timestamp'];
    } else {
      $LastEditor = "Rainer, Heimo";
      $DateLastEdited = "2004-11-26 19:20:22";
    }

    $sql = "INSERT INTO gbif_pilot.".$dbs[$i]['name']." (".
           "UnitID, NameAuthorYearString, Genus, FirstEpithet, AuthorTeam, SecondEpithet, HybridFlag, Rank, HigherTaxon, ".
           "ISODateTimeBegin, ISODateTimeEnd, LocalityText, LocalityDetailed, CountryName, ISO3Letter, ".
           "NamedAreaName, NamedAreaClass, MeasurmentLowerValue, MeasurmentUpperValue, ".
           "LatitudeDecimal, LongitudeDecimal, SpatialDatum, exactness, GatheringAgentsText, ".
           "IdentificationHistory, NamedCollection, UnitIDNumeric, UnitDescription, source_id_fk, det, image_url, ".
           "LastEditor, DateLastEdited".
           ") VALUES (".
           quoteString($UnitID).", ".
           quoteString($NameAuthorYearString).", ".
           quoteString($Genus).", ".
           quoteString($FirstEpithet).", ".
           quoteString($AuthorTeam).", ".
           quoteString($SecondEpithet).", ".
           quoteString($HybridFlag).", ".
           quoteString($Rank).", ".
           quoteString($HigherTaxon).", ".
           quoteString($ISODateTimeBegin).", ".
           quoteString($ISODateTimeEnd).", ".
           quoteString($LocalityText).", ".
           quoteString($LocalityDetailed).", ".
           quoteString($CountryName).", ".
           quoteString($ISO3Letter).", ".
           quoteString($NamedAreaName).", ".
           quoteString($NamedAreaClass).", ".
           quoteString($MeasurmentLowerValue).", ".
           quoteString($MeasurmentUpperValue).", ".
           quoteString($LatitudeDecimal).", ".
           quoteString($LongitudeDecimal).", ".
           quoteString($SpatialDatum).", ".
           quoteString($exactness).", ".
           quoteString($GatheringAgentsText).", ".
           quoteString($IdentificationHistory).", ".
           quoteString($NamedCollection).", ".
           quoteString($UnitIDNumeric).", ".
           quoteString($UnitDescription).", ".
           quoteString($source_id_fk).", ".
           quoteString($det).", ".
           quoteString($image_url).", ".
           quoteString($LastEditor).", ".
           quoteString($DateLastEdited).
           ")";
    db_query($sql);
  }
}

?>