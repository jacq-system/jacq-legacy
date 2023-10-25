<?php
require_once('variables.php');

function logNamesCommonName ($id, $updated)
{
	global $_CONFIG;

    $dbprefix = $_CONFIG['DATABASE']['NAME']['name'] . '.';

	$row = dbi_query("SELECT * FROM {$dbprefix}tbl_name_names WHERE name_id='{$id}' LIMIT 1")->fetch_array();
	dbi_query("INSERT INTO herbarinput_log.log_commonnames_tbl_names SET
                name_id            = " . quoteString($row['name_id']) . ",
                transliteration_id = " . quoteString($row['transliteration_id']) . ",
                userID             = " . quoteString($_SESSION['uid']) . ",
                updated            = " . quoteString($updated));
}


function logTbl_name_names_equals ($id1, $id2, $updated)
{
	global $_CONFIG;

	$dbprefix=$_CONFIG['DATABASE']['NAME']['name'] . '.';

	$row = dbi_query("SELECT * FROM  {$dbprefix}tbl_name_names_equals WHERE tbl_name_names_name_id='{$id1}' and tbl_name_names_name_id1='{$id2}' LIMIT 1")->fetch_array();
	dbi_query("INSERT INTO herbarinput_log.log_tbl_tax_synonymy SET
		        tbl_name_names_name_id  = " . quoteString($row['tbl_name_names_name_id']) . ",
                tbl_name_names_name_id1 = " . quoteString($row['tbl_name_names_name_id1']) . ",
                userID                  = " . quoteString($_SESSION['uid']) . ",
                updated                 = " . quoteString($updated));
}


function logTbl_tax_synonymy ($id, $updated)
{
	$row = dbi_query("SELECT * FROM herbarinput.tbl_tax_synonymy where tax_syn_ID ='{$id}' limit 1")->fetch_array();
	dbi_query("INSERT INTO herbarinput_log.log_tbl_tax_synonymy SET
                tax_syn_ID         = " . quoteString($row['tax_syn_ID']) . ",
                taxonID            = " . quoteString($row['taxonID']) . ",
                acc_taxon_ID       = " . quoteString($row['acc_taxon_ID']) . ",
                ref_date           = " . quoteString($row['ref_date']) . ",
                preferred_taxonomy = " . quoteString($row['preferred_taxonomy']) . ",
                annotations        = " . quoteString($row['annotations']) . ",
                locked             = " . quoteString($row['locked']) . ",
                source             = " . quoteString($row['source']) . ",
                source_citationID  = " . quoteString($row['source_citationID']) . ",
                source_person_ID   = " . quoteString($row['source_person_ID']) . ",
                source_serviceID   = " . quoteString($row['source_serviceID']) . ",
                source_specimenID  = " . quoteString($row['source_specimenID']) . ",
                userID             = " . quoteString($_SESSION['uid']) . ",
                updated            = " . quoteString($updated));
}


/*
at first, transfer all collectors from tbl_collector into log_collector
INSERT INTO herbarinput_log.log_collector
                     (SammlerID, Sammler, Sammler_FN_List, Sammler_FN_short, HUH_ID, VIAF_ID, WIKIDATA_ID, ORCID, locked, Bloodhound_ID, userID, updated)
               SELECT SammlerID, Sammler, Sammler_FN_List, Sammler_FN_short, HUH_ID, VIAF_ID, WIKIDATA_ID, ORCID, locked, Bloodhound_ID, 0,      0
               FROM herbarinput.tbl_collector
 */
function logCollector($id, $updated)
{
	global $_CONFIG;

    $dbprefix = $_CONFIG['DATABASE']['NAME']['name'] . '.';

    dbi_query("INSERT INTO herbarinput_log.log_collector
                     (SammlerID, Sammler, Sammler_FN_List, Sammler_FN_short, HUH_ID, VIAF_ID, WIKIDATA_ID, ORCID, locked, Bloodhound_ID,
                      userID, updated)
               SELECT SammlerID, Sammler, Sammler_FN_List, Sammler_FN_short, HUH_ID, VIAF_ID, WIKIDATA_ID, ORCID, locked, Bloodhound_ID,
                      " . quoteString($_SESSION['uid']) . ", ". quoteString($updated) . "
               FROM tbl_collector
               WHERE SammlerID = " . quoteString($id));
}


function logCommonNamesAppliesTo ($id, $updated, $old = '')
{
	global $_CONFIG;

    $dbprefix = $_CONFIG['DATABASE']['NAME']['name'] . '.';

    $row = dbi_query("SELECT * FROM {$dbprefix}tbl_name_applies_to WHERE " . $id->getWhere())->fetch_array();
    dbi_query("INSERT INTO herbarinput_log.log_commonnames_tbl_name_applies_to SET
                geonameId        = " . dbi_escape_string($row['geonameId']) . ",
                language_id      = " . dbi_escape_string($row['language_id']) . ",
                period_id        = " . dbi_escape_string($row['period_id']) . ",
                entity_id        = " . dbi_escape_string($row['entity_id']) . ",
                reference_id     = " . dbi_escape_string($row['reference_id']) . ",
                name_id          = " . dbi_escape_string($row['name_id']) . ",
                tribe_id         = " . dbi_escape_string($row['tribe_id']) . ",
                geospecification = " . quoteString($row['geospecification']) . ",
                annotations      = " . quoteString($row['annotations']) . ",
                locked           = " . dbi_escape_string($row['locked']) . ",
                oldid            = " . quoteString($old) . ",
                userID           = " . quoteString($_SESSION['uid']) . ",
                updated          = " . quoteString($updated));
}


function logCommonNamesCommonName ($id, $updated)
{
	global $_CONFIG;

    $dbprefix = $_CONFIG['DATABASE']['NAME']['name'] . '.';

	$row = dbi_query("SELECT * FROM {$dbprefix}tbl_name_commons WHERE common_id = '{$id}'")->fetch_array();
	dbi_query("INSERT INTO herbarinput_log.log_commonnames_tbl_name_commons SET
                common_id   = " . dbi_escape_string($row['common_id']) . ",
                common_name = " . quoteString($row['common_name']) . ",
                locked      = " . quoteString($row['locked']) . ",
                userID      = " . quoteString($_SESSION['uid']) . ",
                updated     = " . quoteString($updated));
}
/*
// Not lockable!
function logCommonNamesLanguage($id,$updated) {
	global $_CONFIG;
	$dbprefix=$_CONFIG['DATABASE']['NAME']['name'].'.';

	$sql = "SELECT * FROM {$dbprefix}tbl_name_languages WHERE language_id='{$id}'";
	$result = dbi_query($sql);
	$row = mysqli_fetch_array($result);
	$sql="INSERT INTO herbarinput_log.log_commonnames_tbl_name_languages ".
		 "(language_id, `iso639-6`, `parent_iso639-6`, name, userID, updated, timestamp) VALUES (".
		dbi_escape_string($row['language_id']).', '.
		"'".dbi_escape_string($row['iso639-6'])."', ".
		"'".dbi_escape_string($row['parent_iso639-6'])."', ".
		"'".dbi_escape_string($row['name'])."', ".

		$_SESSION['uid'].', '.
		$updated.',
		NULL)';

	dbi_query($sql);
}*/


function logSpecimen ($ID, $updated)
{
    if ($updated) {
        $row = dbi_query("SELECT * FROM tbl_specimens WHERE specimen_ID = " . quoteString($ID))->fetch_array();
        $sql = "INSERT INTO herbarinput_log.log_specimens SET
                 specimenID        = " . quoteString($ID) . ",
                 userID            = " . quoteString($_SESSION['uid']) . ",
                 updated           = " . quoteString($updated) . ",
                 timestamp         = NULL,
                 HerbNummer        = " . quoteString($row['HerbNummer']) . ",
                 collectionID      = " . quoteString($row['collectionID']) . ",
                 CollNummer        = " . quoteString($row['CollNummer']) . ",
                 identstatusID     = " . quoteString($row['identstatusID']) . ",
                 checked           = " . quoteString($row['checked']) . ",
                 `accessible`      = " . quoteString($row['accessible']) . ",
                 taxonID           = " . quoteString($row['taxonID']) . ",
                 SammlerID         = " . quoteString($row['SammlerID']) . ",
                 Sammler_2ID       = " . quoteString($row['Sammler_2ID']) . ",
                 seriesID          = " . quoteString($row['seriesID']) . ",
                 series_number     = " . quoteString($row['series_number']) . ",
                 Nummer            = " . quoteString($row['Nummer']) . ",
                 alt_number        = " . quoteString($row['alt_number']) . ",
                 Datum             = " . quoteString($row['Datum']) . ",
                 Datum2            = " . quoteString($row['Datum2']) . ",
                 det               = " . quoteString($row['det']) . ",
                 typified          = " . quoteString($row['typified']) . ",
                 typusID           = " . quoteString($row['typusID']) . ",
                 taxon_alt         = " . quoteString($row['taxon_alt']) . ",
                 NationID          = " . quoteString($row['NationID']) . ",
                 provinceID        = " . quoteString($row['provinceID']) . ",
                 Bezirk            = " . quoteString($row['Bezirk']) . ",
                 Coord_W           = " . quoteString($row['Coord_W']) . ",
                 W_Min             = " . quoteString($row['W_Min']) . ",
                 W_Sec             = " . quoteString($row['W_Sec']) . ",
                 Coord_N           = " . quoteString($row['Coord_N']) . ",
                 N_Min             = " . quoteString($row['N_Min']) . ",
                 N_Sec             = " . quoteString($row['N_Sec']) . ",
                 Coord_S           = " . quoteString($row['Coord_S']) . ",
                 S_Min             = " . quoteString($row['S_Min']) . ",
                 S_Sec             = " . quoteString($row['S_Sec']) . ",
                 Coord_E           = " . quoteString($row['Coord_E']) . ",
                 E_Min             = " . quoteString($row['E_Min']) . ",
                 E_Sec             = " . quoteString($row['E_Sec']) . ",
                 quadrant          = " . quoteString($row['quadrant']) . ",
                 quadrant_sub      = " . quoteString($row['quadrant_sub']) . ",
                 exactness         = " . quoteString($row['exactness']) . ",
                 altitude_min      = " . quoteString($row['altitude_min']) . ",
                 altitude_max      = " . quoteString($row['altitude_max']) . ",
                 Fundort           = " . quoteString($row['Fundort']) . ",
                 habitat           = " . quoteString($row['habitat']) . ",
                 habitus           = " . quoteString($row['habitus']) . ",
                 Bemerkungen       = " . quoteString($row['Bemerkungen']) . ",
                 aktualdatum       = " . quoteString($row['aktualdatum']) . ",
                 eingabedatum      = " . quoteString($row['eingabedatum']) . ",
                 digital_image     = " . quoteString($row['digital_image']) . ",
                 garten            = " . quoteString($row['garten']) . ",
                 voucherID         = " . quoteString($row['voucherID']) . ",
                 ncbi_accession    = " . quoteString($row['ncbi_accession']) . ",
                 foreign_db_ID     = " . quoteString($row['foreign_db_ID']) . ",
                 label             = " . quoteString($row['label']) . ",
                 observation       = " . quoteString($row['observation']) . ",
                 digital_image_obs = " . quoteString($row['digital_image_obs']);
    } else {
        $sql = "INSERT INTO herbarinput_log.log_specimens SET
                 specimenID = " . quoteString($ID) . ",
                 userID     = " . quoteString($_SESSION['uid']) . ",
                 updated    = " . quoteString($updated) . ",
                 timestamp  = NULL";
    }
    dbi_query($sql);
}


function logSpecimensTypes ($ID, $updated)
{
    $row = dbi_query("SELECT * FROM tbl_specimens_types WHERE specimens_types_ID = " . quoteString($ID))->fetch_array();
    dbi_query("INSERT INTO herbarinput_log.log_specimens_types SET
                specimens_types_ID = " . quoteString($ID) . ",
                taxonID            = " . quoteString($row['taxonID']) . ",
                specimenID         = " . quoteString($row['specimenID']) . ",
                typusID            = " . quoteString($row['typusID']) . ",
                annotations        = " . quoteString($row['annotations']) . ",
                userID             = " . quoteString($_SESSION['uid']) . ",
                updated            = " . quoteString($updated) . ",
                timestamp          = NULL");
}


function logSpecimensSeries ($ID, $updated)
{
    $row = dbi_query("SELECT * FROM tbl_specimens_series WHERE seriesID = " . quoteString($ID))->fetch_array();
    dbi_query("INSERT INTO herbarinput_log.log_specimens_series SET
                  seriesID  = " . quoteString($row['seriesID']) . ",
                  series    = " . quoteString($row['series'])   . ",
                  locked    = " . quoteString($row['locked'])   . ",
                  userID    = " . quoteString($_SESSION['uid']) . ",
                  updated   = " . quoteString($updated) . ",
                  timestamp = NULL");
}


function logAuthors ($ID, $updated)
{
    $row = dbi_query("SELECT * FROM tbl_tax_authors WHERE authorID = " . quoteString($ID))->fetch_array();
    dbi_query("INSERT INTO herbarinput_log.log_tax_authors SET
                authorID            = " . quoteString($ID) . ",
                author              = " . quoteString($row['author']) . ",
                Brummit_Powell_full = " . quoteString($row['Brummit_Powell_full']) . ",
                userID              = " . quoteString($_SESSION['uid']) . ",
                updated             = " . quoteString($updated) . ",
                timestamp           = NULL");
}


function logFamilies ($ID, $updated)
{
    $row = dbi_query("SELECT * FROM tbl_tax_families WHERE familyID = " . quoteString($ID))->fetch_array();
    dbi_query("INSERT INTO herbarinput_log.log_tax_families SET
                familyID   = " . quoteString($ID) . ",
                family     = " . quoteString($row['family']) . ",
                categoryID = " . quoteString($row['categoryID']) . ",
                userID     = " . quoteString($_SESSION['uid']) . ",
                updated    = " . quoteString($updated) . ",
                timestamp  = NULL");
}


function logGenera ($ID, $updated)
{
    $row = dbi_query("SELECT * FROM tbl_tax_genera WHERE genID = " . quoteString($ID))->fetch_array();
    dbi_query("INSERT INTO herbarinput_log.log_tax_genera SET
                genID               = " . quoteString($ID) . ",
                genID_old           = " . quoteString($row['genID_old']) . ",
                genus               = " . quoteString($row['genus']) . ",
                DallaTorreIDs       = " . quoteString($row['DallaTorreIDs']) . ",
                DallaTorreZusatzIDs = " . quoteString($row['DallaTorreZusatzIDs']) . ",
                genID_inc0406       = " . quoteString($row['genID_inc0406']) . ",
                hybrid              = " . quoteString($row['hybrid']) . ",
                familyID            = " . quoteString($row['familyID']) . ",
                remarks             = " . quoteString($row['remarks']) . ",
                accepted            = " . quoteString($row['accepted']) . ",
                userID              = " . quoteString($_SESSION['uid']) . ",
                updated             = " . quoteString($updated) . ",
                timestamp           = NULL");
}


function logIndex ($ID, $updated)
{
    $row = dbi_query("SELECT * FROM tbl_tax_index WHERE taxindID = " . quoteString($ID))->fetch_array();
    dbi_query("INSERT INTO herbarinput_log.log_tax_index SET
                taxindID    = " . quoteString($ID) . ",
                taxonID     = " . quoteString($row['taxonID']) . ",
                citationID  = " . quoteString($row['citationID']) . ",
                paginae     = " . quoteString($row['paginae']) . ",
                figures     = " . quoteString($row['figures']) . ",
                annotations = " . quoteString($row['annotations']) . ",
                userID      = " . quoteString($_SESSION['uid']) . ",
                updated     = " . quoteString($updated) . ",
                timestamp   = NULL");
}


function logSpecies ($ID, $updated)
{
    $row = dbi_query("SELECT * FROM tbl_tax_species WHERE taxonID = " . quoteString($ID))->fetch_array();
    dbi_query("INSERT INTO herbarinput_log.log_tax_species SET
                taxonID             = " . quoteString($ID) . ",
                tax_rankID          = " . quoteString($row['tax_rankID']) . ",
                basID               = " . quoteString($row['basID']) . ",
                synID               = " . quoteString($row['synID']) . ",
                statusID            = " . quoteString($row['statusID']) . ",
                genID               = " . quoteString($row['genID']) . ",
                speciesID           = " . quoteString($row['speciesID']) . ",
                authorID            = " . quoteString($row['authorID']) . ",
                subspeciesID        = " . quoteString($row['subspeciesID']) . ",
                subspecies_authorID = " . quoteString($row['subspecies_authorID']) . ",
                varietyID           = " . quoteString($row['varietyID']) . ",
                variety_authorID    = " . quoteString($row['variety_authorID']) . ",
                subvarietyID        = " . quoteString($row['subvarietyID']) . ",
                subvariety_authorID = " . quoteString($row['subvariety_authorID']) . ",
                formaID             = " . quoteString($row['formaID']) . ",
                forma_authorID      = " . quoteString($row['forma_authorID']) . ",
                subformaID          = " . quoteString($row['subformaID']) . ",
                subforma_authorID   = " . quoteString($row['subforma_authorID']) . ",
                annotation          = " . quoteString($row['annotation']) . ",
                userID              = " . quoteString($_SESSION['uid']) . ",
                updated             = " . quoteString($updated) . ",
                timestamp           = NULL");
}


function logTypecollections ($ID, $updated)
{
    $row = dbi_query("SELECT * FROM tbl_tax_typecollections WHERE typecollID = " . quoteString($ID))->fetch_array();
    dbi_query("INSERT INTO herbarinput_log.log_tax_typecollections SET
                typecollID       = " . quoteString($ID) . ",
                taxonID          = " . quoteString($row['taxonID']) . ",
                SammlerID        = " . quoteString($row['SammlerID']) . ",
                Sammler_2ID      = " . quoteString($row['Sammler_2ID']) . ",
                series           = " . quoteString($row['series']) . ",
                leg_nr           = " . quoteString($row['leg_nr']) . ",
                alternate_number = " . quoteString($row['alternate_number']) . ",
                date             = " . quoteString($row['date']) . ",
                duplicates       = " . quoteString($row['duplicates']) . ",
                annotation       = " . quoteString($row['annotation']) . ",
                userID           = " . quoteString($_SESSION['uid']) . ",
                updated          = " . quoteString($updated) . ",
                timestamp        = NULL");
}


function logLit ($ID, $updated)
{
    $row = dbi_query("SELECT * FROM tbl_lit WHERE citationID = " . quoteString($ID))->fetch_array();
    dbi_query("INSERT INTO herbarinput_log.log_lit SET
                citationID   = " . quoteString($ID) . ",
                lit_url      = " . quoteString($row['lit_url']) . ",
                autorID      = " . quoteString($row['autorID']) . ",
                jahr         = " . quoteString($row['jahr']) . ",
                code         = " . quoteString($row['code']) . ",
                titel        = " . quoteString($row['titel']) . ",
                suptitel     = " . quoteString($row['suptitel']) . ",
                editorsID    = " . quoteString($row['editorsID']) . ",
                periodicalID = " . quoteString($row['periodicalID']) . ",
                vol          = " . quoteString($row['vol']) . ",
                part         = " . quoteString($row['part']) . ",
                pp           = " . quoteString($row['pp']) . ",
                publisherID  = " . quoteString($row['publisherID']) . ",
                verlagsort   = " . quoteString($row['verlagsort']) . ",
                keywords     = " . quoteString($row['keywords']) . ",
                annotation   = " . quoteString($row['annotation']) . ",
                additions    = " . quoteString($row['additions']) . ",
                bestand      = " . quoteString($row['bestand']) . ",
                signature    = " . quoteString($row['signature']) . ",
                publ         = " . quoteString($row['publ']) . ",
                category     = " . quoteString($row['category']) . ",
                userID       = " . quoteString($_SESSION['uid']) . ",
                updated      = " . quoteString($updated) . ",
                timestamp    = NULL");
}


function logLitTax ($ID, $updated)
{
    $row = dbi_query("SELECT * FROM tbl_lit_taxa WHERE lit_tax_ID = " . quoteString($ID))->fetch_array();
    dbi_query("INSERT INTO herbarinput_log.log_lit_taxa SET
                lit_tax_ID        = " . quoteString($ID) . ",
                citationID        = " . quoteString($row['citationID']) . ",
                taxonID           = " . quoteString($row['taxonID']) . ",
                acc_taxon_ID      = " . quoteString($row['acc_taxon_ID']) . ",
                annotations       = " . quoteString($row['annotations']) . ",
                locked            = " . quoteString($row['locked']) . ",
                source            = " . quoteString($row['source']) . ",
                source_citationID = " . quoteString($row['source_citationID']) . ",
                source_person_ID  = " . quoteString($row['source_person_ID']) . ",
                et_al             = " . quoteString($row['et_al']) . ",
                userID            = " . quoteString($_SESSION['uid']) . ",
                updated           = " . quoteString($updated) . ",
                timestamp         = NULL");
}


function logLitAuthors ($ID, $updated)
{
    $row = dbi_query("SELECT * FROM tbl_lit_authors WHERE autorID = " . quoteString($ID))->fetch_array();
    dbi_query("INSERT INTO herbarinput_log.log_lit_authors SET
                autorID      = " . quoteString($ID) . ",
                autor        = " . quoteString($row['autor']) . ",
                autorsystbot = " . quoteString($row['autorsystbot']) . ",
                userID       = " . quoteString($_SESSION['uid']) . ",
                updated      = " . quoteString($updated) . ",
                timestamp    = NULL");
}


function logLitPeriodicals ($ID, $updated)
{
    $row = dbi_query("SELECT * FROM tbl_lit_periodicals WHERE periodicalID = " . quoteString($ID))->fetch_array();
    dbi_query("INSERT INTO herbarinput_log.log_lit_periodicals SET
                periodicalID    = " . quoteString($ID) . ",
                periodical      = " . quoteString($row['periodical']) . ",
                periodical_full = " . quoteString($row['periodical_full']) . ",
                tl2_number      = " . quoteString($row['tl2_number']) . ",
                bph_number      = " . quoteString($row['bph_number']) . ",
                ipni_ID         = " . quoteString($row['ipni_ID']) . ",
                userID          = " . quoteString($_SESSION['uid']) . ",
                updated         = " . quoteString($updated) . ",
                timestamp       = NULL");
}


function logLitPublishers ($ID, $updated)
{
    $row = dbi_query("SELECT * FROM tbl_lit_publishers WHERE publisherID = " . quoteString($ID))->fetch_array();
    dbi_query("INSERT INTO herbarinput_log.log_lit_publishers  SET
                publisherID = " . quoteString($ID) . ",
                publisher   = " . quoteString($row['publisher']) . ",
                userID      = " . quoteString($_SESSION['uid']) . ",
                updated     = " . quoteString($updated) . ",
                timestamp   = NULL");
}
