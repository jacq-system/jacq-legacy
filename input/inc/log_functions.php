<?php

require_once('variables.php');

function logCommonNamesAppliesTo($id,$updated,$old='') {
	global $_CONFIG;
	$dbprefix=$_CONFIG['DATABASE']['NAME']['name'].'.';
	
	$sql = "SELECT * FROM {$dbprefix}tbl_name_applies_to
WHERE 
".$id->getWhere();
	
	$result = mysql_query($sql);
	$row = mysql_fetch_array($result);
	$sql="INSERT INTO herbarinput_log.log_commonnames_tbl_name_applies_to ".
		"(geonameId,language_id,period_id,entity_id,reference_id,name_id,locked,oldid,geospecification,annotation,".
		"userID, updated, timestamp) VALUES (".
		$row['geonameId'].', '.
		$row['language_id'].', '.
		$row['period_id'].', '.
		$row['entity_id'].', '.
		$row['reference_id'].', '.
		$row['name_id'].', '.
		$row['geospecification'].', '.
		$row['annotation'].', '.
		$row['locked'].', '.
		'\''.$old.'\', '.
		
		$_SESSION['uid'].', '.
		$updated.',
		NULL)';
	mysql_query($sql);
}

function logCommonNamesCommonName($id,$updated) {
	global $_CONFIG;
	$dbprefix=$_CONFIG['DATABASE']['NAME']['name'].'.';
	
	$sql = "SELECT * FROM {$dbprefix}tbl_name_commons WHERE common_id='{$id}'";
	$result = mysql_query($sql);
	$row = mysql_fetch_array($result);

	$sql="INSERT INTO herbarinput_log.log_commonnames_tbl_name_commons ".
		 "(common_id, common_name, userID, updated, timestamp) VALUES (".
		$row['common_id'].', '.
		"'".$row['common_name']."', ".
			
		$_SESSION['uid'].', '.
		$updated.',
		NULL)';
	mysql_query($sql);
}

function logCommonNamesLanguage($id,$updated) {
	global $_CONFIG;
	$dbprefix=$_CONFIG['DATABASE']['NAME']['name'].'.';
	
	$sql = "SELECT * FROM {$dbprefix}tbl_name_languages WHERE language_id='{$id}'";
	$result = mysql_query($sql);
	$row = mysql_fetch_array($result);

	$sql="INSERT INTO herbarinput_log.log_commonnames_tbl_name_languages ".
		 "(language_id, `iso639-6`, `parent_iso639-6`, name, userID, updated, timestamp) VALUES (".
		$row['language_id'].', '.
		"'".$row['iso639-6']."', ".
		"'".$row['parent_iso639-6']."', ".
		"'".$row['name']."', ".
		
		$_SESSION['uid'].', '.
		$updated.',
		NULL)';

	mysql_query($sql);
}


function logSpecimen($ID,$updated) {

  if ($updated) {
    $sql = "SELECT * FROM tbl_specimens ".
           "WHERE specimen_ID='".mysql_escape_string($ID)."'";
    $result = mysql_query($sql);
    $row = mysql_fetch_array($result);

    $sql = "INSERT INTO herbarinput_log.log_specimens SET
            specimenID=".quoteString($ID).",
            userID=".quoteString($_SESSION['uid']).",
            updated=".quoteString($updated).",
            timestamp=NULL,
            HerbNummer=".quoteString($row['HerbNummer']).",
            collectionID=".quoteString($row['collectionID']).",
            CollNummer=".quoteString($row['CollNummer']).",
            identstatusID=".quoteString($row['identstatusID']).",
            checked=".quoteString($row['checked']).",
            `accessible`=".quoteString($row['accessible']).",
            taxonID=".quoteString($row['taxonID']).",
            SammlerID=".quoteString($row['SammlerID']).",
            Sammler_2ID=".quoteString($row['Sammler_2ID']).",
            seriesID=".quoteString($row['seriesID']).",
            series_number=".quoteString($row['series_number']).",
            Nummer=".quoteString($row['Nummer']).",
            alt_number=".quoteString($row['alt_number']).",
            Datum=".quoteString($row['Datum']).",
            Datum2=".quoteString($row['Datum2']).",
            det=".quoteString($row['det']).",
            typified=".quoteString($row['typified']).",
            typusID=".quoteString($row['typusID']).",
            taxon_alt=".quoteString($row['taxon_alt']).",
            NationID=".quoteString($row['NationID']).",
            provinceID=".quoteString($row['provinceID']).",
            Bezirk=".quoteString($row['Bezirk']).",
            Coord_W=".quoteString($row['Coord_W']).",
            W_Min=".quoteString($row['W_Min']).",
            W_Sec=".quoteString($row['W_Sec']).",
            Coord_N=".quoteString($row['Coord_N']).",
            N_Min=".quoteString($row['N_Min']).",
            N_Sec=".quoteString($row['N_Sec']).",
            Coord_S=".quoteString($row['Coord_S']).",
            S_Min=".quoteString($row['S_Min']).",
            S_Sec=".quoteString($row['S_Sec']).",
            Coord_E=".quoteString($row['Coord_E']).",
            E_Min=".quoteString($row['E_Min']).",
            E_Sec=".quoteString($row['E_Sec']).",
            quadrant=".quoteString($row['quadrant']).",
            quadrant_sub=".quoteString($row['quadrant_sub']).",
            exactness=".quoteString($row['exactness']).",
            altitude_min=".quoteString($row['altitude_min']).",
            altitude_max=".quoteString($row['altitude_max']).",
            Fundort=".quoteString($row['Fundort']).",
            habitat=".quoteString($row['habitat']).",
            habitus=".quoteString($row['habitus']).",
            Bemerkungen=".quoteString($row['Bemerkungen']).",
            aktualdatum=".quoteString($row['aktualdatum']).",
            eingabedatum=".quoteString($row['eingabedatum']).",
            digital_image=".quoteString($row['digital_image']).",
            garten=".quoteString($row['garten']).",
            voucherID=".quoteString($row['voucherID']).",
            ncbi_accession=".quoteString($row['ncbi_accession']).",
            foreign_db_ID=".quoteString($row['foreign_db_ID']).",
            label=".quoteString($row['label']).",
            observation=".quoteString($row['observation']).",
            digital_image_obs=".quoteString($row['digital_image_obs']);
  }
  else {
    $sql = "INSERT INTO herbarinput_log.log_specimens SET
            specimenID=".quoteString($ID).",
            userID=".quoteString($_SESSION['uid']).",
            updated=".quoteString($updated).",
            timestamp=NULL";
  }
  mysql_query($sql);
}

function logSpecimensTypes($ID,$updated) {

  $sql = "SELECT * FROM tbl_specimens_types ".
         "WHERE specimens_types_ID='".mysql_escape_string($ID)."'";
  $result = mysql_query($sql);
  $row = mysql_fetch_array($result);

  $sql = "INSERT INTO herbarinput_log.log_specimens_types ".
         "(specimens_types_ID, taxonID, specimenID, typusID, annotations, ".
          "userID, updated, timestamp) VALUES (".
         quoteString($ID).", ".
         quoteString($row['taxonID']).", ".
         quoteString($row['specimenID']).", ".
         quoteString($row['typusID']).", ".
         quoteString($row['annotations']).", ".
         quoteString($_SESSION['uid']).", ".
         quoteString($updated).", ".
         "NULL)";
  mysql_query($sql);
}

function logSpecimensSeries($ID, $updated)
{
    $row = mysql_fetch_array(mysql_query("SELECT * FROM tbl_specimens_series
                                          WHERE seriesID = '" . mysql_escape_string($ID) . "'"));

    mysql_query("INSERT INTO herbarinput_log.log_specimens_series SET
                  seriesID  = " . quoteString($row['seriesID']) . ",
                  series    = " . quoteString($row['series'])   . ",
                  locked    = " . quoteString($row['locked'])   . ",
                  userID    = " . quoteString($_SESSION['uid']) . ",
                  updated   = " . quoteString($updated) . ",
                  timestamp = NULL");
}

function logAuthors($ID,$updated) {

  $sql = "SELECT * FROM tbl_tax_authors ".
         "WHERE authorID='".mysql_escape_string($ID)."'";
  $result = mysql_query($sql);
  $row = mysql_fetch_array($result);

  $sql = "INSERT INTO herbarinput_log.log_tax_authors ".
         "(authorID, author, Brummit_Powell_full, userID, updated, timestamp) VALUES (".
         quoteString($ID).", ".
         quoteString($row['author']).", ".
         quoteString($row['Brummit_Powell_full']).", ".
         quoteString($_SESSION['uid']).", ".
         quoteString($updated).", ".
         "NULL)";
  db_query($sql);
}

function logFamilies($ID,$updated) {

  $sql = "SELECT * FROM tbl_tax_families ".
         "WHERE familyID='".mysql_escape_string($ID)."'";
  $result = mysql_query($sql);
  $row = mysql_fetch_array($result);

  $sql = "INSERT INTO herbarinput_log.log_tax_families ".
         "(familyID, family, categoryID, userID, updated, timestamp) VALUES (".
         quoteString($ID).", ".
         quoteString($row['family']).", ".
         quoteString($row['categoryID']).", ".
         quoteString($_SESSION['uid']).", ".
         quoteString($updated).", ".
         "NULL)";
  db_query($sql);
}

function logGenera($ID,$updated) {

  $sql = "SELECT * FROM tbl_tax_genera ".
         "WHERE genID='".mysql_escape_string($ID)."'";
  $result = mysql_query($sql);
  $row = mysql_fetch_array($result);

  $sql = "INSERT INTO herbarinput_log.log_tax_genera ".
         "(genID, genID_old, genus, DallaTorreIDs, DallaTorreZusatzIDs, genID_inc0406, ".
          "hybrid, familyID, remarks, accepted, ".
          "userID, updated, timestamp) VALUES (".
         quoteString($ID).", ".
         quoteString($row['genID_old']).", ".
         quoteString($row['genus']).", ".
         quoteString($row['DallaTorreIDs']).", ".
         quoteString($row['DallaTorreZusatzIDs']).", ".
         quoteString($row['genID_inc0406']).", ".
         quoteString($row['hybrid']).", ".
         quoteString($row['familyID']).", ".
         quoteString($row['remarks']).", ".
         quoteString($row['accepted']).", ".
         quoteString($_SESSION['uid']).", ".
         quoteString($updated).", ".
         "NULL)";
  db_query($sql);
}

function logIndex($ID,$updated) {

  $sql = "SELECT * FROM tbl_tax_index ".
         "WHERE taxindID='".mysql_escape_string($ID)."'";
  $result = mysql_query($sql);
  $row = mysql_fetch_array($result);

  $sql = "INSERT INTO herbarinput_log.log_tax_index ".
         "(taxindID, taxonID, citationID, paginae, figures, annotations, ".
          "userID, updated, timestamp) VALUES (".
         quoteString($ID).", ".
         quoteString($row['taxonID']).", ".
         quoteString($row['citationID']).", ".
         quoteString($row['paginae']).", ".
         quoteString($row['figures']).", ".
         quoteString($row['annotations']).", ".
         quoteString($_SESSION['uid']).", ".
         quoteString($updated).", ".
         "NULL)";
  db_query($sql);
}

function logSpecies($ID,$updated) {

  $sql = "SELECT * FROM tbl_tax_species ".
         "WHERE taxonID='".mysql_escape_string($ID)."'";
  $result = mysql_query($sql);
  $row = mysql_fetch_array($result);

  $sql = "INSERT INTO herbarinput_log.log_tax_species ".
         "(taxonID, tax_rankID, basID, synID, statusID, genID, speciesID, ".
          "authorID, subspeciesID, subspecies_authorID, ".
          "varietyID, variety_authorID, subvarietyID, subvariety_authorID, ".
          "formaID, forma_authorID, subformaID, subforma_authorID, annotation, ".
          "userID, updated, timestamp) VALUES (".
         quoteString($ID).", ".
         quoteString($row['tax_rankID']).", ".
         quoteString($row['basID']).", ".
         quoteString($row['synID']).", ".
         quoteString($row['statusID']).", ".
         quoteString($row['genID']).", ".
         quoteString($row['speciesID']).", ".
         quoteString($row['authorID']).", ".
         quoteString($row['subspeciesID']).", ".
         quoteString($row['subspecies_authorID']).", ".
         quoteString($row['varietyID']).", ".
         quoteString($row['variety_authorID']).", ".
         quoteString($row['subvarietyID']).", ".
         quoteString($row['subvariety_authorID']).", ".
         quoteString($row['formaID']).", ".
         quoteString($row['forma_authorID']).", ".
         quoteString($row['subformaID']).", ".
         quoteString($row['subforma_authorID']).", ".
         quoteString($row['annotation']).", ".
         quoteString($_SESSION['uid']).", ".
         quoteString($updated).", ".
         "NULL)";
  db_query($sql);
}

function logTypecollections($ID,$updated) {

  $sql = "SELECT * FROM tbl_tax_typecollections ".
         "WHERE typecollID='".mysql_escape_string($ID)."'";
  $result = mysql_query($sql);
  $row = mysql_fetch_array($result);

  $sql = "INSERT INTO herbarinput_log.log_tax_typecollections ".
         "(typecollID, taxonID, SammlerID, Sammler_2ID, series, leg_nr, ".
          "alternate_number, date, duplicates, annotation, ".
          "userID, updated, timestamp) VALUES (".
         quoteString($ID).", ".
         quoteString($row['taxonID']).", ".
         quoteString($row['SammlerID']).", ".
         quoteString($row['Sammler_2ID']).", ".
         quoteString($row['series']).", ".
         quoteString($row['leg_nr']).", ".
         quoteString($row['alternate_number']).", ".
         quoteString($row['date']).", ".
         quoteString($row['duplicates']).", ".
         quoteString($row['annotation']).", ".
         quoteString($_SESSION['uid']).", ".
         quoteString($updated).", ".
         "NULL)";
  db_query($sql);
}

function logLit($ID,$updated) {

  $sql = "SELECT * FROM tbl_lit ".
         "WHERE citationID='".mysql_escape_string($ID)."'";
  $result = mysql_query($sql);
  $row = mysql_fetch_array($result);

  $sql = "INSERT INTO herbarinput_log.log_lit ".
         "(citationID, lit_url, autorID, jahr, code, titel, suptitel, editorsID, ".
          "periodicalID, vol, part, pp, publisherID, verlagsort, ".
          "keywords, annotation, additions, bestand, signature, ".
          "publ, category, ".
          "userID, updated, timestamp) VALUES (".
         quoteString($ID).", ".
         quoteString($row['lit_url']).", ".
         quoteString($row['autorID']).", ".
         quoteString($row['jahr']).", ".
         quoteString($row['code']).", ".
         quoteString($row['titel']).", ".
         quoteString($row['suptitel']).", ".
         quoteString($row['editorsID']).", ".
         quoteString($row['periodicalID']).", ".
         quoteString($row['vol']).", ".
         quoteString($row['part']).", ".
         quoteString($row['pp']).", ".
         quoteString($row['publisherID']).", ".
         quoteString($row['verlagsort']).", ".
         quoteString($row['keywords']).", ".
         quoteString($row['annotation']).", ".
         quoteString($row['additions']).", ".
         quoteString($row['bestand']).", ".
         quoteString($row['signature']).", ".
         quoteString($row['publ']).", ".
         quoteString($row['category']).", ".
         quoteString($_SESSION['uid']).", ".
         quoteString($updated).", ".
         "NULL)";
  db_query($sql);
}

function logLitTax($ID,$updated) {

  $sql = "SELECT *
          FROM tbl_lit_taxa
          WHERE lit_tax_ID = '" . mysql_escape_string($ID) . "'";
  $result = mysql_query($sql);
  $row = mysql_fetch_array($result);

  $sql = "INSERT INTO herbarinput_log.log_lit_taxa SET
           lit_tax_ID = " . quoteString($ID) . ",
           citationID = " . quoteString($row['citationID']) . ",
           taxonID = " . quoteString($row['taxonID']) . ",
           acc_taxon_ID = " . quoteString($row['acc_taxon_ID']) . ",
           annotations = " . quoteString($row['annotations']) . ",
           locked = " . quoteString($row['locked']) . ",
           source = " . quoteString($row['source']) . ",
           source_citationID = " . quoteString($row['source_citationID']) . ",
           source_person_ID = " . quoteString($row['source_person_ID']) . ",
           et_al = " . quoteString($row['et_al']) . ",
           userID = " . quoteString($_SESSION['uid']) . ",
           updated = " . quoteString($updated) . ",
           timestamp = NULL";
  db_query($sql);
}

function logLitAuthors($ID,$updated) {

  $sql = "SELECT * FROM tbl_lit_authors ".
         "WHERE autorID='".mysql_escape_string($ID)."'";
  $result = mysql_query($sql);
  $row = mysql_fetch_array($result);

  $sql = "INSERT INTO herbarinput_log.log_lit_authors ".
         "(autorID, autor, autorsystbot, userID, updated, timestamp) VALUES (".
         quoteString($ID).", ".
         quoteString($row['autor']).", ".
         quoteString($row['autorsystbot']).", ".
         quoteString($_SESSION['uid']).", ".
         quoteString($updated).", ".
         "NULL)";
  db_query($sql);
}

function logLitPeriodicals($ID,$updated) {

  $sql = "SELECT * FROM tbl_lit_periodicals ".
         "WHERE periodicalID='".mysql_escape_string($ID)."'";
  $result = mysql_query($sql);
  $row = mysql_fetch_array($result);

  $sql = "INSERT INTO herbarinput_log.log_lit_periodicals ".
         "(periodicalID, periodical, periodical_full, tl2_number, bph_number, ipni_ID, ".
         "userID, updated, timestamp) VALUES (".
         quoteString($ID).", ".
         quoteString($row['periodical']).", ".
         quoteString($row['periodical_full']).", ".
         quoteString($row['tl2_number']).", ".
         quoteString($row['bph_number']).", ".
         quoteString($row['ipni_ID']).", ".
         quoteString($_SESSION['uid']).", ".
         quoteString($updated).", ".
         "NULL)";
  db_query($sql);
}

function logLitPublishers($ID,$updated) {

  $sql = "SELECT * FROM tbl_lit_publishers ".
         "WHERE publisherID='".mysql_escape_string($ID)."'";
  $result = mysql_query($sql);
  $row = mysql_fetch_array($result);

  $sql = "INSERT INTO herbarinput_log.log_lit_publishers ".
         "(publisherID, publisher, userID, updated, timestamp) VALUES (".
         quoteString($ID).", ".
         quoteString($row['publisher']).", ".
         quoteString($_SESSION['uid']).", ".
         quoteString($updated).", ".
         "NULL)";
  db_query($sql);
}
?>