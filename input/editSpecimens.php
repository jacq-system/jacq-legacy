<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");
require("inc/herbardb_input_functions.php");
require("inc/log_functions.php");
require_once ("inc/xajax/xajax_core/xajax.inc.php");
no_magic();

$xajax = new xajax();
$xajax->setRequestURI("ajax/editSpecimensServer.php");

$xajax->registerFunction("toggleLanguage");
$xajax->registerFunction("searchGeonames");
$xajax->registerFunction("useGeoname");
$xajax->registerFunction("makeLinktext");
$xajax->registerFunction("editLink");
$xajax->registerFunction("updateLink");
$xajax->registerFunction("deleteLink");
$xajax->registerFunction("editMultiTaxa");
$xajax->registerFunction("updateMultiTaxa");
$xajax->registerFunction("deleteMultiTaxa");

if (!isset($_SESSION['sPTID'])) $_SESSION['sPTID'] = 0;

if (isset($_GET['ptid'])) $_SESSION['sPTID'] = intval($_GET['ptid']);

$nr = isset($_GET['nr']) ? intval($_GET['nr']) : 0;
$linkList = $_SESSION['sLinkList'];
$swBatch = (checkRight('batch')) ? true : false; // nur user mit Recht "batch" kann Batches aendern


function makeTaxon2($search)
{
    global $cf;

    $results[] = "";
    if ($search && strlen($search) > 1) {
        $pieces = explode(chr(194) . chr(183), $search);
        $pieces = explode(" ", $pieces[0]);

        $sql = "SELECT taxonID, ts.synID
                FROM tbl_tax_species ts
                 LEFT JOIN tbl_tax_epithets te ON te.epithetID = ts.speciesID
                 LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
                WHERE ts.external = 0
                 AND tg.genus LIKE '" . mysql_escape_string($pieces[0]) . "%'\n";
                 //AND ts.statusID != 1
        if (!empty($pieces[1])) {
            $sql .= "AND te.epithet LIKE '" . mysql_escape_string($pieces[1]) . "%'\n";
        }
        $sql .= "ORDER BY tg.genus, te.epithet";
        $result = db_query($sql);
        if ($result && mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_array($result)) {
                $results[] = (($row['synID']) ? '-' : '') . getScientificName($row['taxonID']);
            }
        }

        $sql = "SELECT ts.taxonID, ts.synID, tg.genus,
                 ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
                 ta4.author author4, ta5.author author5,
                 te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
                 te4.epithet epithet4, te5.epithet epithet5,
                 th.parent_1_ID, th.parent_2_ID
                FROM (tbl_tax_species ts, tbl_tax_hybrids th)
                 LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
                 LEFT JOIN tbl_tax_species tsp1 ON tsp1.taxonID = th.parent_1_ID
                 LEFT JOIN tbl_tax_epithets tep1 ON tep1.epithetID = tsp1.speciesID
                 LEFT JOIN tbl_tax_genera tgp1 ON tgp1.genID = tsp1.genID
                 LEFT JOIN tbl_tax_species tsp2 ON tsp2.taxonID = th.parent_2_ID
                 LEFT JOIN tbl_tax_epithets tep2 ON tep2.epithetID = tsp2.speciesID
                 LEFT JOIN tbl_tax_genera tgp2 ON tgp2.genID = tsp2.genID
                 LEFT JOIN tbl_tax_authors ta ON ta.authorID = ts.authorID
                 LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID = ts.subspecies_authorID
                 LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID = ts.variety_authorID
                 LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID = ts.subvariety_authorID
                 LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID = ts.forma_authorID
                 LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID = ts.subforma_authorID
                 LEFT JOIN tbl_tax_epithets te ON te.epithetID = ts.speciesID
                 LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID = ts.subspeciesID
                 LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID = ts.varietyID
                 LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID = ts.subvarietyID
                 LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID = ts.formaID
                 LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID = ts.subformaID
                WHERE th.taxon_ID_fk = ts.taxonID
                 AND (tg.genus LIKE '" . mysql_escape_string($pieces[0]) . "%'
                  OR tgp1.genus LIKE '" . mysql_escape_string($pieces[0]) . "%'
                  OR tgp2.genus LIKE '" . mysql_escape_string($pieces[0]) . "%')\n";
        if (!empty($pieces[1])) {
            $sql .= "AND (tep1.epithet LIKE '" . mysql_escape_string($pieces[1]) . "%'
                      OR tep2.epithet LIKE '" . mysql_escape_string($pieces[1]) . "%')\n";
        }
        $sql .= "ORDER BY tg.genus, tep1.epithet, tgp2.genus, tep2.epithet";
        $result = db_query($sql);
        if ($result && mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_array($result)) {
                $results[] = (($row['synID']) ? '-' : '') . taxonWithHybrids($row);
            }
        }
        //sort($results);
    }
    return $results;
}

function makeSammler2($search, $nr)
{
    global $cf;

    $pieces = explode(" <", $search);
    $results[] = "";
    if ($search && strlen($search) > 1) {
        if ($nr == 2) {
            $sql = "SELECT Sammler_2, Sammler_2ID
                    FROM tbl_collector_2
                    WHERE Sammler_2 LIKE '" . mysql_escape_string($pieces[0]) . "%'
                    ORDER BY Sammler_2";
        } else {
            $sql = "SELECT Sammler, SammlerID
                    FROM tbl_collector
                    WHERE Sammler LIKE '".mysql_escape_string($pieces[0])."%'
                    ORDER BY Sammler";
        }
        $result = db_query($sql);
        if ($result && mysql_num_rows($result) > 0) {
            while ($row=mysql_fetch_array($result)) {
                if ($nr == 2) {
                    $res = $row['Sammler_2'] . " <" . $row['Sammler_2ID'] . ">";
                } else {
                    $res = $row['Sammler'] . " <" . $row['SammlerID'] . ">";
                }
                $results[] = $res;
            }
        }
    }
    return $results;
}

// main program
// As we have changed the buttons to JavaScript only, we cannot check automatically which button was pressed
// Therefor we use a hidden field and mirror it to the buttons name for now
if( isset($_POST['submit_type']) ) {
    if( !isset($_POST[$_POST['submit_type']]) ) {
        $_POST[$_POST['submit_type']] = true;
    }
}

$updateBlocked = false;
if (isset($_GET['sel'])) {
    if  (extractID($_GET['sel']) != "NULL") {
        $sql = "SELECT s.specimen_ID, s.HerbNummer, s.CollNummer, s.identstatusID, s.checked, s.accessible,
                 s.taxonID, s.seriesID, s.series_number, s.Nummer, s.alt_number, s.Datum, s.Datum2,
                 s.det, s.typified, s.taxon_alt, s.Bezirk,
                 s.Coord_W, s.W_Min, s.W_Sec, s.Coord_N, s.N_Min, s.N_Sec,
                 s.Coord_S, s.S_Min, s.S_Sec, s.Coord_E, s.E_Min, s.E_Sec,
                 s.quadrant, s.quadrant_sub, s.exactness, s.altitude_min, s.altitude_max,
                 s.Fundort, s.Fundort_engl, s.habitat, s.habitus, s.Bemerkungen, s.digital_image, s.digital_image_obs,
                 s.garten, s.voucherID, s.ncbi_accession,
                 s.collectionID, s.typusID, s.NationID, s.provinceID,
                 c.SammlerID, c.Sammler, c2.Sammler_2ID, c2.Sammler_2,
                 mc.source_id
                FROM tbl_specimens s
                 LEFT JOIN tbl_management_collections mc ON mc.collectionID = s.collectionID
                 LEFT JOIN tbl_collector c ON c.SammlerID = s.SammlerID
                 LEFT JOIN tbl_collector_2 c2 ON c2.Sammler_2ID = s.Sammler_2ID
                WHERE specimen_ID = " . extractID($_GET['sel']);
        $result = db_query($sql);
        $resultValid = (mysql_num_rows($result)>0) ? true : false;
    } else {
        $resultValid = false;
    }

    if ($resultValid) {
        $row = mysql_fetch_array($result);
        $p_specimen_ID       = $row['specimen_ID'];
        $p_HerbNummer        = $row['HerbNummer'];
        $p_CollNummer        = $row['CollNummer'];
        $p_identstatus       = $row['identstatusID'];
        $p_checked           = $row['checked'];
        $p_accessible        = $row['accessible'];
        $p_series            = $row['seriesID'];
        $p_series_number     = $row['series_number'];
        $p_Nummer            = $row['Nummer'];
        $p_alt_number        = $row['alt_number'];
        $p_Datum             = $row['Datum'];
        $p_Datum2            = $row['Datum2'];
        $p_det               = $row['det'];
        $p_typified          = $row['typified'];
        $p_taxon_alt         = $row['taxon_alt'];
        $p_Bezirk            = $row['Bezirk'];
        $p_quadrant          = $row['quadrant'];
        $p_quadrant_sub      = $row['quadrant_sub'];
        $p_exactness         = $row['exactness'];
        $p_altitude_min      = $row['altitude_min'];
        $p_altitude_max      = $row['altitude_max'];
        $p_Fundort           = $row['Fundort'];
        $p_Fundort_engl      = $row['Fundort_engl'];
        $p_habitat           = $row['habitat'];
        $p_habitus           = $row['habitus'];
        $p_Bemerkungen       = $row['Bemerkungen'];
        $p_digital_image     = $row['digital_image'];
        $p_digital_image_obs = $row['digital_image_obs'];
        $p_garten            = $row['garten'];
        $p_voucher           = $row['voucherID'];
        $p_ncbi              = $row['ncbi_accession'];

        $p_collection  = $row['collectionID'];
        $p_institution = $row['source_id'];
        $p_typus       = $row['typusID'];
        $p_nation      = $row['NationID'];
        $p_province    = $row['provinceID'];

        $p_sammler       = $row['Sammler'] . " <" . $row['SammlerID'] . ">";
        $p_sammlerIndex  = $row['SammlerID'];
        $p_sammler2      = ($row['Sammler_2']) ? $row['Sammler_2'] . " <" . $row['Sammler_2ID'] . ">" : "";
        $p_sammler2Index = $row['Sammler_2ID'];

        if ($row['Coord_S'] > 0 || $row['S_Min'] > 0 || $row['S_Sec'] > 0) {
            $p_lat_deg = $row['Coord_S'];
            $p_lat_min = $row['S_Min'];
            $p_lat_sec = $row['S_Sec'];
            $p_lat     = "S";
        } else {
            $p_lat_deg = $row['Coord_N'];
            $p_lat_min = $row['N_Min'];
            $p_lat_sec = $row['N_Sec'];
            $p_lat     = "N";
        }
        if ($row['Coord_W'] > 0 || $row['W_Min'] > 0 || $row['W_Sec'] > 0) {
            $p_lon_deg = $row['Coord_W'];
            $p_lon_min = $row['W_Min'];
            $p_lon_sec = $row['W_Sec'];
            $p_lon     = "W";
        } else {
            $p_lon_deg = $row['Coord_E'];
            $p_lon_min = $row['E_Min'];
            $p_lon_sec = $row['E_Sec'];
            $p_lon     = "E";
        }

        if ($row['taxonID']) {
            $sql = "SELECT ts.external
                    FROM tbl_tax_species ts
                    WHERE ts.taxonID = '" . mysql_escape_string($row['taxonID']) . "'";
            $result2 = db_query($sql);
            $row2 = mysql_fetch_array($result2);
            $p_external = $row2['external'];
            $p_taxonIndex = $row['taxonID'];
            $p_taxon = getScientificName( $p_taxonIndex, false, true, true );
        } else {
            $p_taxon = "";
            $p_taxonIndex = 0;
            $p_external = null;
        }
    } else {
        $p_specimen_ID = $p_HerbNummer = $p_CollNummer = $p_identstatus = "";
        $p_checked = $p_accessible = "1";
        $p_series = $p_series_number = $p_Nummer = $p_alt_number = $p_Datum = $p_Datum2 = $p_det = "";
        $p_typified = $p_taxon_alt = $p_taxon = $p_Bezirk = "";
        $p_external = null;
        $p_lat_deg = $p_lat_min = $p_lat_sec = ""; $p_lat = "N";
        $p_lon_deg = $p_lon_min = $p_lon_sec = ""; $p_lon = "E";
        $p_quadrant = $p_quadrant_sub = $p_exactness = $p_altitude_min = $p_altitude_max = "";
        $p_Fundort = $p_Fundort_engl = $p_habitat = $p_habitus = $p_Bemerkungen = "";
        $p_digital_image = $p_digital_image_obs = $p_garten = $p_voucher = $p_ncbi = "";
        $p_typus = $p_nation = $p_province = "";
        $p_sammler = $p_sammler2 = "";
        $p_taxonIndex = $p_sammlerIndex = $p_sammler2Index = 0;
        $p_institution = $_SESSION['sid'];
        if ($p_institution) {
            $sql = "SELECT collectionID FROM tbl_management_collections WHERE source_id = '$p_institution' ORDER BY collection";
            $row = mysql_fetch_array(db_query($sql));
            $p_collection = $row['collectionID'];
        } else {
            $p_collection = "";
        }
    }
    $edit = (!empty($_GET['edit'])) ? true : false;
    if ($swBatch) {
        // read tbl_api_specimens
        $result = db_query("SELECT specimen_ID FROM api.tbl_api_specimens WHERE specimen_ID = " . extractID($_GET['sel']));
        $p_batch = (mysql_num_rows($result)>0) ? 1 : 0;
    }
} else {
    $p_collection        = $_POST['collection'];
    $p_institution       = $_POST['institution'];
    $p_HerbNummer        = $_POST['HerbNummer'];
    $p_CollNummer        = $_POST['CollNummer'];
    $p_identstatus       = $_POST['identstatus'];
    $p_batch             = $_POST['batch'];
    $p_checked           = $_POST['checked'];
    $p_accessible        = $_POST['accessible'];
    $p_series            = $_POST['seriesIndex'];
    $p_series_number     = $_POST['series_number'];
    $p_Nummer            = $_POST['Nummer'];
    $p_alt_number        = $_POST['alt_number'];
    $p_Datum             = $_POST['Datum'];
    $p_Datum2            = $_POST['Datum2'];
    $p_det               = $_POST['det'];
    $p_typified          = $_POST['typified'];
    $p_taxon_alt         = $_POST['taxon_alt'];
    $p_Bezirk            = $_POST['Bezirk'];
    $p_quadrant          = $_POST['quadrant'];
    $p_quadrant_sub      = $_POST['quadrant_sub'];
    $p_exactness         = $_POST['exactness'];
    $p_altitude_min      = $_POST['altitude_min'];
    $p_altitude_max      = $_POST['altitude_max'];
    $p_Fundort           = ($_POST['toggleLanguage']) ? $_POST['Fundort2'] : $_POST['Fundort1'];
    $p_Fundort_engl      = ($_POST['toggleLanguage']) ? $_POST['Fundort1'] : $_POST['Fundort2'];
    $p_habitat           = $_POST['habitat'];
    $p_habitus           = $_POST['habitus'];
    $p_Bemerkungen       = $_POST['Bemerkungen'];
    $p_digital_image     = $_POST['digital_image'];
    $p_digital_image_obs = $_POST['digital_image_obs'];
    $p_garten            = $_POST['garten'];
    $p_voucher           = $_POST['voucher'];
    $p_ncbi              = $_POST['ncbi'];
    $p_taxon             = $_POST['taxon'];
    $p_taxonIndex        = (strlen(trim($_POST['taxon']))>0) ? $_POST['taxonIndex'] : 0;
    $p_external          = $_POST['external'];
    $p_typus             = $_POST['typus'];
    $p_nation            = $_POST['nation'];
    $p_province          = $_POST['province'];
    $p_sammler           = $_POST['sammler'];
    $p_sammlerIndex      = (strlen(trim($_POST['sammler']))>0) ? $_POST['sammlerIndex'] : 0;
    $p_sammler2          = $_POST['sammler2'];
    $p_sammler2Index     = (strlen(trim($_POST['sammler2']))>0) ? $_POST['sammler2Index'] : 0;
    $p_lat               = $_POST['lat'];
    $p_lat_deg           = $_POST['lat_deg'];
    $p_lat_min           = $_POST['lat_min'];
    $p_lat_sec           = $_POST['lat_sec'];
    $p_lon               = $_POST['lon'];
    $p_lon_deg           = $_POST['lon_deg'];
    $p_lon_min           = $_POST['lon_min'];
    $p_lon_sec           = $_POST['lon_sec'];

    $d_Coord_N = $d_N_Min = $d_N_Sec = $d_Coord_S = $d_S_Min = $d_S_Sec = "";
    if ($p_lat == "S") {
        $d_Coord_S = $p_lat_deg;
        $d_S_Min   = $p_lat_min;
        $d_S_Sec   = $p_lat_sec;
    } else {
        $d_Coord_N = $p_lat_deg;
        $d_N_Min   = $p_lat_min;
        $d_N_Sec   = $p_lat_sec;
    }
    $d_Coord_W = $d_W_Min = $d_W_Sec = $d_Coord_E = $d_E_Min = $d_E_Sec = "";
    if ($p_lon == "W") {
        $d_Coord_W = $p_lon_deg;
        $d_W_Min   = $p_lon_min;
        $d_W_Sec   = $p_lon_sec;
    } else {
        $d_Coord_E = $p_lon_deg;
        $d_E_Min   = $p_lon_min;
        $d_E_Sec   = $p_lon_sec;
    }

    if ((!empty($_POST['submitUpdate']) || !empty($_POST['submitUpdateNew']) || !empty($_POST['submitUpdateCopy'])) && (($_SESSION['editControl'] & 0x2000) != 0)) {
        $sqldata = "HerbNummer = " . quoteString($p_HerbNummer) . ",
                    collectionID = '" . intval($p_collection) . "',
                    CollNummer = " . quoteString($p_CollNummer) . ",
                    identstatusID = " . quoteString($p_identstatus) . ",
                    checked = " . (($p_checked) ? "'1'" : "'0'") . ",
                    `accessible` = " . (($p_accessible) ? "'1'" : "'0'") . ",
                    taxonID = " . extractID($p_taxon) . ",
                    SammlerID = " . extractID($p_sammler) . ",
                    Sammler_2ID = " . extractID($p_sammler2) . ",
                    seriesID = " . quoteString($p_series) . ",
                    series_number = " . quoteString($p_series_number) . ",
                    Nummer = " . quoteString($p_Nummer) . ",
                    alt_number = " . quoteString($p_alt_number) . ",
                    Datum = " . quoteString($p_Datum) . ",
                    Datum2 = " . quoteString($p_Datum2) . ",
                    det = " . quoteString($p_det) . ",
                    typified = " . quoteString($p_typified) . ",
                    typusID = " . quoteString($p_typus) . ",
                    taxon_alt = " . quoteString($p_taxon_alt) . ",
                    NationID = " . quoteString($p_nation) . ",
                    provinceID = " . quoteString($p_province) . ",
                    Bezirk = " . quoteString($p_Bezirk) . ",
                    Coord_W = " . quoteString($d_Coord_W) . ",
                    W_Min = " . quoteString($d_W_Min) . ",
                    W_Sec = " . quoteString($d_W_Sec) . ",
                    Coord_N = " . quoteString($d_Coord_N) . ",
                    N_Min = " . quoteString($d_N_Min) . ",
                    N_Sec = " . quoteString($d_N_Sec) . ",
                    Coord_S = " . quoteString($d_Coord_S) . ",
                    S_Min = " . quoteString($d_S_Min) . ",
                    S_Sec = " . quoteString($d_S_Sec).",
                    Coord_E = " . quoteString($d_Coord_E) . ",
                    E_Min = " . quoteString($d_E_Min) . ",
                    E_Sec = " . quoteString($d_E_Sec) . ",
                    quadrant = " . quoteString($p_quadrant) . ",
                    quadrant_sub = " . quoteString($p_quadrant_sub) . ",
                    exactness = " . quoteString($p_exactness) . ",
                    altitude_min = " . quoteString($p_altitude_min) . ",
                    altitude_max = " . quoteString($p_altitude_max) . ",
                    Fundort = " . quoteString($p_Fundort) . ",
                    Fundort_engl = " . quoteString($p_Fundort_engl) . ",
                    habitat = " . quoteString($p_habitat) . ",
                    habitus = " . quoteString($p_habitus) . ",
                    Bemerkungen = " . quoteString($p_Bemerkungen) . ",
                    digital_image = " . (($p_digital_image) ? "'1'" : "'0'") . ",
                    digital_image_obs = " . (($p_digital_image_obs) ? "'1'" : "'0'") . ",
                    garten = " . quoteString($p_garten) . ",
                    voucherID = " . quoteString($p_voucher) . ",
                    observation = '0'";

        if (intval($_POST['specimen_ID'])) {
            // check if user has access to the old collection
            $sql = "SELECT source_id
                    FROM tbl_specimens, tbl_management_collections
                    WHERE tbl_specimens.collectionID = tbl_management_collections.collectionID
                     AND specimen_ID = '" . intval($_POST['specimen_ID']) . "'";
            $dummy = mysql_fetch_array(mysql_query($sql));
            $checkSource = ($dummy['source_id']==$_SESSION['sid']) ? true : false;

            $sql = "UPDATE tbl_specimens SET "
                 .  $sqldata . " "
                 . "WHERE specimen_ID = '" . intval($_POST['specimen_ID']) . "'";
            $updated = 1;
        } else {
            // no check cause there is no old collection
            $checkSource = true;
            $sql = "INSERT INTO tbl_specimens SET "
                 .  $sqldata . ", "
                 .  "eingabedatum = NULL";
            $updated = 0;
        }
        // check if user has access to the new collection
        $sqlCheck = "SELECT source_id FROM tbl_management_collections WHERE collectionID = '" . intval($p_collection) . "'";
        $rowCheck = mysql_fetch_array(mysql_query($sqlCheck));
        // allow write access to database if user is editor or is granted for both old and new collection
        if ($_SESSION['editorControl'] || ($_SESSION['sid'] == $rowCheck['source_id'] && $checkSource)) {
            // TODO: add configuration switch for duplicate check
            $sqlDummy = "SELECT s.`specimen_ID`
                         FROM `tbl_specimens` s, `tbl_management_collections` mc
                         WHERE s.`collectionID` = mc.`collectionID`
                          AND s.`HerbNummer` = " . quoteString($p_HerbNummer) . "
                          AND (mc.`source_id` = '1' OR mc.`source_id` = '6' OR mc.`source_id` = '4' OR mc.`source_id` = '5')
                          AND mc.`coll_short_prj` = (SELECT `coll_short_prj` FROM `tbl_management_collections` WHERE `collectionID` = " . intval($p_collection) .")
                          AND s.`specimen_ID` != '" . intval($_POST['specimen_ID']) . "'";
            $dummy = db_query($sqlDummy);
            if (mysql_num_rows($dummy) > 0) {
                $updateBlocked = true;
                $blockCause = 1;  // HerbNummer and source_id already in database
                $dummyRow = mysql_fetch_array($dummy);
                $blockSource = $dummyRow['specimen_ID'];
                $edit = ($_POST['edit']) ? true : false;
                $p_specimen_ID = $_POST['specimen_ID'];
            } else {
                if ($updated) {
                    $p_specimen_ID = intval($_POST['specimen_ID']);
                    logSpecimen($p_specimen_ID, $updated);
                    $result = db_query($sql);
                } else {
                    $result = db_query($sql);
                    $p_specimen_ID = mysql_insert_id();
                    logSpecimen($p_specimen_ID, $updated);
                }

                if ($_POST['submitUpdateNew']) {
                    $location="Location: editSpecimens.php?sel=<0>&new=1";
                    if (SID) $location .= "&" . SID;
                    Header($location);
                } elseif ($_POST['submitUpdateCopy']) {
                    $location="Location: editSpecimens.php?sel=<".$p_specimen_ID.">&new=1";
                    if (SID) $location .= "&" . SID;
                    Header($location);
                }
                $edit = false;
            }
        }
        else {
            $updateBlocked = true;
            $blockCause = 2;  // no write access to the new collection
            $edit = ($_POST['edit']) ? true : false;
            $p_specimen_ID = $_POST['specimen_ID'];
        }
        if ($swBatch) {
            // read tbl_api_specimens
            $result = mysql_query("SELECT specimen_ID FROM api.tbl_api_specimens WHERE specimen_ID = " . extractID($_GET['sel']));
            $p_batch = (mysql_num_rows($result)>0) ? 1 : 0;
        }
    } else if (!empty($_POST['submitNewCopy'])) {
        $p_specimen_ID = "";
        $edit = false;
    } else {
        $edit = (!empty($_POST['edit'])) ? true : false;
        $p_specimen_ID = $_POST['specimen_ID'];
    }
}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - edit Specimens</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <link rel="stylesheet" type="text/css" href="js/lib/jQuery/css/ui-lightness/jquery-ui.custom.css">
  <style type="text/css">
    #log { position:absolute; bottom:1em; right:1em }
	.ui-autocomplete {
        font-size: 0.9em;  /* smaller size */
		max-height: 200px;
		overflow-y: auto;
		/* prevent horizontal scrollbar */
		overflow-x: hidden;
		/* add padding to account for vertical scrollbar */
		padding-right: 20px;
	}
	/* IE 6 doesn't support max-height
	 * we use height instead, but this forces the menu to always be this tall
	 */
	* html .ui-autocomplete {
		height: 200px;
	}
  </style>
  <?php $xajax->printJavascript('inc/xajax'); ?>
  <script type="text/javascript" src="inc/overlib/overlib.js"></script>
  <script src="js/lib/jQuery/jquery.min.js" type="text/javascript"></script>
  <script src="js/lib/jQuery/jquery-ui.custom.min.js" type="text/javascript"></script>
  <script src="js/freudLib.js" type="text/javascript"></script>
  <script src="js/parameters.php" type="text/javascript"></script>
  <script type="text/javascript" language="JavaScript">
    var reload = false;
    var linktext = '';//'<ul><li><a href="http://www.heise.de/">link1</a></li><li><a href="http://www.heise.de/">link2</a></li></ul>';

    function makeOptions() {
      options = "width=";
      if (screen.availWidth<990)
        options += (screen.availWidth - 10) + ",height=";
      else
        options += "990, height=";
      if (screen.availHeight<710)
        options += (screen.availHeight - 10);
      else
        options += "710";
      options += ", top=10,left=10,scrollbars=yes,resizable=yes";
      return options;
    }
    function editCollector(sel) {
      target = "editCollector.php?sel=" + encodeURIComponent(sel.value);
      MeinFenster = window.open(target,"editCollector","width=850,height=250,top=50,left=50,scrollbars=yes,resizable=yes");
      MeinFenster.focus();
    }
    function editCollector2(sel) {
      target = "editCollector2.php?sel=" + encodeURIComponent(sel.value);
      MeinFenster = window.open(target,"editCollector2","width=500,height=130,top=50,left=50,scrollbars=yes,resizable=yes");
      MeinFenster.focus();
    }
    function searchCollector() {
      MeinFenster = window.open("searchCollector.php","searchCollector","scrollbars=yes,resizable=yes");
      MeinFenster.focus();
    }
    function searchCollector2() {
      MeinFenster = window.open("searchCollector2.php","searchCollector2","scrollbars=yes,resizable=yes");
      MeinFenster.focus();
    }
    function editSpecies(sel) {
      target = "editSpecies.php?sel=" + encodeURIComponent(sel.value);
      MeinFenster = window.open(target,"Species",makeOptions());
      MeinFenster.focus();
    }
    function editVoucher() {
      target = "editVoucher.php?sel=" + document.f.voucher.options[document.f.voucher.selectedIndex].value;
      MeinFenster = window.open(target,"editVoucher","width=500,height=150,top=50,left=50,scrollbars=yes,resizable=yes");
      MeinFenster.focus();
    }
    function editSeries() {
        target = "editSeries.php?sel=" + $( '#seriesIndex' ).val();
      MeinFenster = window.open(target,"editSeries","width=500,height=150,top=50,left=50,scrollbars=yes,resizable=yes");
      MeinFenster.focus();
    }
    function editSpecimensTypes(sel) {
      target = "listSpecimensTypes.php?ID=" + encodeURIComponent(sel);
      MeinFenster = window.open(target,"listSpecimensTypes","width=800,height=400,top=50,left=50,scrollbars=yes,resizable=yes");
      MeinFenster.focus();
    }

    function editLabel(sel) {
      target = "editLabel.php?sel=<" + sel + ">";
      MeinFenster = window.open(target,"Labels",makeOptions());
      MeinFenster.focus();
    }

    function updateBatch(sel,sw) {
      if (document.f.batch.checked==true || sw)
        option2 = "&sw=2";
      else
        option2 = "&sw=1";
      target = "updateBatch.php?nr=" + encodeURIComponent(sel) + option2;
      MeinFenster = window.open(target,"updateBatch","width=800,height=400,top=50,left=50,scrollbars=yes,resizable=yes");
    }

    function reloadButtonPressed() {
      reload = true;
    }
    function checkMandatory(outText) {
      var missing = 0;
      var text = "";
      var outtext = "";

      if (reload==true) return true;

      if (document.f.collection.selectedIndex==0) {
        missing++; text += "Collection\n";
      }
      if (document.f.taxon.value.indexOf("<")<0 || document.f.taxon.value.indexOf(">")<0) {
        missing++; text += "taxon\n";
      }
      if (document.f.det.value.length==0) {
        missing++; text += "det / rev / conf\n";
      }
      if (document.f.taxon_alt.value.length==0) {
        missing++; text += "ident. history\n";
      }
      if (document.f.sammler.value.indexOf("<")<0 || document.f.sammler.value.indexOf(">")<0) {
        missing++; text += "collector\n";
      }
      if (document.f.Nummer.value.length==0 && document.f.alt_number.value.length==0) {
        missing++; text += "Number and alt.Nr.\n enter s.n. in alt.Nr. if no number is available";
      }
      if (document.f.Datum.value.length==0) {
        missing++; text += "Date\n";
      }
      if (document.f.Fundort1.value.length==0) {
        missing++; text += "Locality\n";
      }

      if (missing>0) {
        if (missing>1)
          outtext = "The following " + missing + " entries are missing or invalid:\n";
        else
          outtext = "The following entry is missing or invalid:\n";
        if (outText!=0) alert (outtext + text);
        return false;
      }
      else
        return true;
    }

    function doSubmit( p_type ) {
        // If all fields are set, trigger a submit
        if( checkMandatory(1) ) {
            $( '#submit_type' ).val( p_type );
            $( '#f' ).submit();
        }
    }

    function quadrant2LatLon(quadrant,quadrant_sub) {
      var xx = quadrant.substr(quadrant.length-2,2);
      var yy = quadrant.substr(0,quadrant.length-2);

      var xD = parseInt(((xx - 2) / 6) + 6);
      var xM = 0;
      var xS = Math.round((((((xx - 2) / 6) + 6) * 60) % 60) * 60);
      var yD = parseInt(((-yy / 10) + 56));
      var yM = 0;
      var yS = Math.round(((((-yy / 10) + 56) * 60) % 60) * 60);

      if (quadrant_sub==0 || quadrant_sub>4) {
        xM += 5;
        yM -= 3;
      }
      else {
        xS += ((quadrant_sub - 1) % 2) * (5 * 60);
        yS -= parseInt((quadrant_sub - 1) / 2) * (3 * 60);
        xS += (60 * 5) / 2;   // Verschiebung zum Quadranten-Zentrum in Sekunden
        yS -= (60 * 3) / 2;   // Verschiebung zum Quadranten-Zentrum in Sekunden
      }

      var latLon = new Array(2);
      latLon[1] = xD + (xM / 60) + (xS / 3600);
      latLon[0] = yD + (yM / 60) + (yS / 3600);

      return latLon;
    }

    function convert() {
      var latLon = quadrant2LatLon(document.f.quadrant.value,document.f.quadrant_sub.value);

      if (document.f.lon_deg.value || document.f.lon_min.value || document.f.lon_sec.value || document.f.lat_deg.value || document.f.lat_min.value || document.f.lat_sec.value)    {
        alert('Coordinates have already been entered');
      }
      else {
        document.f.lon_deg.value = Math.floor(Math.abs(latLon[1]));
        document.f.lon_min.value = Math.floor(Math.abs(latLon[1]) * 60 % 60);
        document.f.lon_sec.value = Math.floor(Math.abs(latLon[1]) * 3600 % 60);
        if (latLon[1]<0)
          document.f.lon.options.selectedIndex = 0;
        else
          document.f.lon.options.selectedIndex = 1;
        document.f.lat_deg.value = Math.floor(Math.abs(latLon[0]));
        document.f.lat_min.value = Math.floor(Math.abs(latLon[0]) * 60 % 60);
        document.f.lat_sec.value = Math.floor(Math.abs(latLon[0]) * 3600 % 60);
        if (latLon[0]>=0)
          document.f.lat.options.selectedIndex = 0;
        else
          document.f.lat.options.selectedIndex = 1;
      }
    }

    function fillLocation(lon_deg, lon_min, lon_sec, lon_dir, lat_deg, lat_min, lat_sec, lat_dir, nationID) {
      if (document.f.lon_deg.value || document.f.lon_min.value || document.f.lon_sec.value || document.f.lat_deg.value || document.f.lat_min.value || document.f.lat_sec.value)    {
        alert('Coordinates have already been entered');
      }
      else {
        document.f.lon_deg.value = lon_deg;
        document.f.lon_min.value = lon_min;
        document.f.lon_sec.value = lon_sec;
        if (lon_dir == 'W')
          document.f.lon.options.selectedIndex = 0;
        else
          document.f.lon.options.selectedIndex = 1;
        document.f.lat_deg.value = lat_deg;
        document.f.lat_min.value = lat_min;
        document.f.lat_sec.value = lat_sec;
        if (lat_dir == 'N')
          document.f.lat.options.selectedIndex = 0;
        else
          document.f.lat.options.selectedIndex = 1;
      }
      for (i=0; i<document.f.nation.length; i++) {
        if (document.f.nation.options[i].value == nationID) {
          document.f.nation.selectedIndex = i;
          break;
        }
      }
      reload=true;
      self.document.f.submit();
    }

    function editNCBI(sel) {
      target = "editNCBI.php?id=" + sel;
      MeinFenster = window.open(target,"editNCBI","width=350,height=130,top=50,left=50,scrollbars=yes,resizable=yes");
      MeinFenster.focus();
    }

    function goBack(sel,check,edit,pid) {
      if (!check && checkMandatory(0))
        move = confirm("Are you sure you want to leave?\nDataset will not be inserted!");
      else if (check && edit)
        move = confirm("Are you sure you want to leave?\nDataset will not be updated!");
      else
        move = true;
      if (move) {
        if (pid)
          self.location.href = 'listTypeSpecimens.php?ID=' + pid + '&nr=' + sel;
        else
          self.location.href = 'listSpecimens.php?nr=' + sel;
      }
    }

    function call_toggleLanguage() {
      xajax_toggleLanguage(xajax.getFormValues('f'));
      return false;
    }

    function call_makeAutocompleter(name) {
      $('#' + name).autocomplete ({
        source: 'index_jq_autocomplete.php?field=taxon',
        minLength: 2
      });
    }

    xajax_makeLinktext('<?php echo $p_specimen_ID; ?>');
    $(function() {
        $('#iBox_content').dialog( {
          autoOpen: false,
          modal: true,
          bgiframe: true,
          width: 750,
          height: 600
        } );
    } );
  </script>
</head>

<body>

<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>
<div id="iBox_content" style="display:none;"></div>

<form Action="<?php echo $_SERVER['PHP_SELF']; ?>" Method="POST" name="f" id="f">
<input type="hidden" name="submit_type" id="submit_type" value="">

<?php
unset($institution);
$result = db_query("SELECT source_id, source_code FROM herbarinput.meta ORDER BY source_code");
if ($result && mysql_num_rows($result) > 0) {
    while ($row = mysql_fetch_array($result)) {
        $institution[0][] = $row['source_id'];
        $institution[1][] = substr($row['source_code'], 0, 4);
    }
}

unset($collection);
if ($p_institution) {
    $sql = "SELECT collection, collectionID
            FROM tbl_management_collections
            WHERE source_id = '" . intval($p_institution) . "'
            ORDER BY collection";
} else {
    $sql = "SELECT collection, collectionID
            FROM tbl_management_collections
            ORDER BY collection";
}
$collection[0][] = 0; $collection[1][] = "";
$result = db_query($sql);
if ($result && mysql_num_rows($result) > 0) {
    while ($row = mysql_fetch_array($result)) {
        $collection[0][] = $row['collectionID'];
        $collection[1][] = $row['collection'];
    }
}

unset($typus);
$typus[0][] = 0; $typus[1][] = "";
$result = db_query("SELECT typus_lat, typusID FROM tbl_typi ORDER BY typus_lat");
if ($result && mysql_num_rows($result) > 0) {
    while ($row = mysql_fetch_array($result)) {
        $typus[0][] = $row['typusID'];
        $typus[1][] = $row['typus_lat'];
    }
}

unset($identstatus);
$identstatus[0][] = 0; $identstatus[1][] = "";
$result = db_query("SELECT identstatusID, identification_status FROM tbl_specimens_identstatus ORDER BY identification_status");
if ($result && mysql_num_rows($result) > 0) {
    while ($row = mysql_fetch_array($result)) {
        $identstatus[0][] = $row['identstatusID'];
        $identstatus[1][] = $row['identification_status'];
    }
}

$result = db_query("SELECT series FROM tbl_specimens_series WHERE seriesID = '$p_series'");
if ($result && mysql_num_rows($result) > 0) {
    $row = mysql_fetch_array($result);
    $p_seriesName = $row['series'] . " <" . $p_series . ">";
}

unset($nation);
$nation[0][] = 0; $nation[1][] = "";
$result = db_query("SELECT nation_engl, nationID FROM tbl_geo_nation ORDER BY nation_engl");
if ($result && mysql_num_rows($result) > 0) {
    while ($row = mysql_fetch_array($result)) {
        $nation[0][] = $row['nationID'];
        $nation[1][] = $row['nation_engl'];
    }
}

unset($province);
$province[0][] = 0; $province[1][] = "";
$result = db_query("SELECT provinz, provinceID FROM tbl_geo_province
                    WHERE nationID = '" . intval($p_nation) . "'
                    ORDER BY provinz");
if ($result && mysql_num_rows($result) > 0) {
    while ($row = mysql_fetch_array($result)) {
        $province[0][] = $row['provinceID'];
        $province[1][] = $row['provinz'];
    }
}

unset($voucher);
$voucher[0][] = 0; $voucher[1][] = "";
$result = db_query("SELECT voucherID, voucher FROM tbl_specimens_voucher ORDER BY voucher");
if ($result && mysql_num_rows($result) > 0) {
    while ($row = mysql_fetch_array($result)) {
        $voucher[0][] = $row['voucherID'];
        $voucher[1][] = $row['voucher'];
    }
}

if ($nr) {
    echo "<div style=\"position: absolute; left: 16em; top: 0.4em;\">";
    if ($nr > 1) {
        echo "<a href=\"editSpecimens.php?sel=" . htmlentities("<" . $linkList[$nr - 1] . ">") . "&nr=" . ($nr - 1) . "\">"
           . "<img border=\"0\" height=\"22\" src=\"webimages/left.gif\" width=\"20\">"
           . "</a>";
    } else {
        echo "<img border=\"0\" height=\"22\" src=\"webimages/left_gray.gif\" width=\"20\">";
    }
    echo "</div>\n";
    echo "<div style=\"position: absolute; left: 17.5em; top: 0.4em;\">";
    if ($nr < $linkList[0]) {
        echo "<a href=\"editSpecimens.php?sel=" . htmlentities("<" . $linkList[$nr + 1] . ">") . "&nr=" . ($nr + 1) . "\">"
           . "<img border=\"0\" height=\"22\" src=\"webimages/right.gif\" width=\"20\">"
           . "</a>";
    } else {
        echo "<img border=\"0\" height=\"22\" src=\"webimages/right_gray.gif\" width=\"20\">";
    }
    echo "</div>\n";
}

$cf = new CSSF();

echo "<input type=\"hidden\" name=\"specimen_ID\" value=\"$p_specimen_ID\">\n";
echo "<input type=\"hidden\" name=\"ncbi\" value=\"$p_ncbi\">\n";
if ($p_specimen_ID) {
    if ($edit) {
        echo "<input type=\"hidden\" name=\"edit\" value=\"$edit\">\n";
        $text = "<span style=\"background-color: #66FF66\">&nbsp;<b>$p_specimen_ID</b>&nbsp;</span>";
    } else {
        $text = "<a href=\"javascript:editLabel('$p_specimen_ID');\" title=\"Label\">$p_specimen_ID</a>";
    }
} else {
    $text = "<span style=\"background-color: #66FF66\">&nbsp;<b>new</b>&nbsp;</span>";
}
$y = 0.5;
$cf->label(2, $y, "&lowast;", "list_log_specimens.php?sel=" . $p_specimen_ID . "\" target=\"_blank", "log");
$cf->label(11, $y, "specimen_ID");
$cf->text(11, $y, "&nbsp;" . $text);

if ($swBatch) {
    if ($p_batch) {
        $cf->label(22.5, $y, "API", "javascript:updateBatch('$p_specimen_ID',1)");
    } else {
        $cf->label(22.5, $y, "API");
    }
    $cf->checkbox(22.5, $y, "batch\" onchange=\"updateBatch('$p_specimen_ID',0);", $p_batch);
}

if ($p_digital_image && $p_specimen_ID) {
    $cf->label(32, $y, "dig.image", "javascript:showImage('$p_specimen_ID')");
} else {
    $cf->label(32, $y, "dig.image");
}
$cf->checkbox(32, $y, "digital_image", $p_digital_image);
if ($p_digital_image_obs && $p_specimen_ID) {
    $cf->label(41, $y, "dig.im.obs.", "javascript:showImageObs('$p_specimen_ID')");
} else {
    $cf->label(41, $y, "dig.im.obs.");
}
$cf->checkbox(41, $y, "digital_image_obs", $p_digital_image_obs);
$cf->labelMandatory(50.5, $y, 5, "checked");
$cf->checkbox(50.5, $y, "checked", $p_checked);
$cf->labelMandatory(60.5, $y, 6, "accessible");
$cf->checkbox(60.5, $y, "accessible", $p_accessible);

$y += 2;
//$institution = mysql_fetch_array(mysql_query("SELECT coll_short_prj FROM tbl_management_collections WHERE collectionID='$p_collection'"));
$cf->label(11, $y, "Institution");
//$cf->text(9,$y,"&nbsp;".strtoupper($institution['coll_short_prj']));
$cf->dropdown(11, $y, "institution\" onchange=\"reload=true; self.document.f.submit();", $p_institution, $institution[0], $institution[1]);
$cf->label(23, $y, "HerbarNr.");
$cf->inputText(23, $y, 7, "HerbNummer", $p_HerbNummer, 25);
$cf->labelMandatory(37, $y, 5.5, "Collection");
$cf->dropdown(37.5, $y, "collection", $p_collection, $collection[0], $collection[1]);
$cf->label(55, $y, "Nr.");
$cf->inputText(55, $y, 6, "CollNummer", $p_CollNummer, 25);

$y += 2;
$cf->label(11, $y, "links", "#\" onclick=\"xajax_editLink('$p_specimen_ID');\" onmouseover=\"return overlib(linktext, STICKY, CAPTION, 'Links to', MOUSEOFF, FGCOLOR, '#008000', DELAY, 500);\" onmouseout=\"return nd();");
$cf->label(44, $y, "T", "javascript:editSpecimensTypes('$p_specimen_ID')");
$cf->label(47, $y, "type");
$cf->dropdown(47, $y, "typus", $p_typus, $typus[0], $typus[1]);

$y += 2;
$cf->label(11, $y,"Status");
$cf->dropdown(11, $y, "identstatus", $p_identstatus, $identstatus[0], $identstatus[1]);

$cf->label(25, $y, "Garden");
$cf->inputText(25, $y, 11, "garten", $p_garten, 50);

echo "<img border=\"1\" height=\"16\" src=\"webimages/ncbi.gif\" width=\"14\" ".
     "style=\"position:absolute; left:38em; top:" . ($y + 0.2) . "em\"";
if ($p_ncbi) echo " title=\"$p_ncbi\"";
echo " onclick=\"editNCBI($p_specimen_ID)\">\n";
$cf->label(48, $y, "voucher","javascript:editVoucher()");
$cf->dropdown(48, $y, "voucher", $p_voucher, $voucher[0], $voucher[1]);

$y += 2;
if (($_SESSION['editControl'] & 0x1) != 0 || ($_SESSION['linkControl'] & 0x1) != 0) {
    $cf->labelMandatory(11, $y, 8, "taxon", "javascript:editSpecies(document.f.taxon)");
} else {
    $cf->labelMandatory(11, $y, 8, "taxon");
}
//$cf->editDropdown(9, $y, 46, "taxon", $p_taxon, makeTaxon2($p_taxon), 520, 0, ($p_external) ? 'red' : '');
$cf->inputJqAutocomplete(11, $y, 50, "taxon", $p_taxon, $p_taxonIndex, "index_jq_autocomplete.php?field=taxonWithHybrids", 520, 2, ($p_external) ? 'red' : '');
echo "<input type=\"hidden\" name=\"external\" value=\"$p_external\">\n";
$cf->label(11, $y + 1.5, "multi", "#\" onclick=\"xajax_editMultiTaxa('$p_specimen_ID');");

$y += 4;
$cf->labelMandatory(10, $y, 8, "det / rev / conf");
$cf->inputText(11, $y, 50, "det", $p_det, 255);

$y += 2;
$cf->labelMandatory(11, $y, 8, "ident. history");
$cf->inputText(11, $y, 50, "taxon_alt", $p_taxon_alt, 255);

$y += 2;
$cf->label(11, $y, "typified by");
$cf->inputText(11, $y, 50, "typified", $p_typified, 255);

$y += 2;
//$cf->label(9, $y, "Series", "javascript:editSeries()");
//$cf->dropdown(9, $y, "series", $p_series, $series[0], $series[1]);
//$cf->label(49.5, $y, "ser.Nr.");
//$cf->inputText(49.5, $y, 5.5, "series_number", $p_series_number, 50);
$cf->label(11, $y, "Series", "javascript:editSeries()");
$cf->inputJqAutocomplete(11, $y, 32, "series", $p_seriesName, $p_series, "index_jq_autocomplete.php?field=series", 520, 2, "", "", false, true );
$cf->label(55.5, $y, "ser.Nr.");
$cf->inputText(55.5, $y, 5.5, "series_number", $p_series_number, 50);

$y += 2;
$cf->labelMandatory(11, $y, 8, "first collector", "javascript:editCollector(document.f.sammler)");
//$cf->editDropdown(9, $y, 46, "sammler", $p_sammler, makeSammler2($p_sammler, 1), 270);
$cf->inputJqAutocomplete(11, $y, 50, "sammler", $p_sammler, $p_sammlerIndex, "index_jq_autocomplete.php?field=collector", 520, 2);
$cf->label(11, $y + 1.7, "search", "javascript:searchCollector()");

$y += 4;
$cf->labelMandatory(11, $y, 8, "Number");
$cf->inputText(11, $y, 4, "Nummer", $p_Nummer, 10);
$cf->label(22, $y, "alt.Nr.");
$cf->inputText(22, $y, 17, "alt_number", $p_alt_number, 50);
$cf->labelMandatory(46, $y, 3, "Date");
$cf->inputText(46, $y, 5.5, "Datum", $p_Datum, 25);
$cf->text(53.5, $y - 0.3, "<font size=\"+1\">&ndash;</font>");
$cf->inputText(55.5, $y, 5.5, "Datum2", $p_Datum2, 25);

$y += 2;
$cf->label(11, $y, "add. collector(s)", "javascript:editCollector2(document.f.sammler2)");
//$cf->editDropdown(9, $y, 46, "sammler2", $p_sammler2, makeSammler2($p_sammler2, 2), 270);
$cf->inputJqAutocomplete(11, $y, 50, "sammler2", $p_sammler2, $p_sammler2Index, "index_jq_autocomplete.php?field=collector2", 520, 2);
$cf->label(11, $y + 1.7, "search", "javascript:searchCollector2()");

$y += 3.25;
echo "<div style=\"position: absolute; left: 1em; top: {$y}em; width: 60.5em;\"><hr></div>\n";

$y += 1.25;
$cf->labelMandatory(11, $y, 8, "Country");
if (($_SESSION['editControl'] & 0x2000) != 0) {
    $cf->dropdown(11, $y, "nation\" onchange=\"reload=true; self.document.f.submit();", $p_nation, $nation[0], $nation[1]);
} else {
    $cf->dropdown(11, $y, "nation", $p_nation, $nation[0], $nation[1]);
}
$cf->label(46, $y, "Province");
$cf->dropdown(46, $y, "province", $p_province, $province[0], $province[1]);

$y += 2;
$cf->label(10, $y, "geonames","#\" onclick=\"xajax_searchGeonames(document.f.Bezirk.value);");
$cf->inputText(11, $y, 20, "Bezirk", $p_Bezirk, 255);

$y += 2;
$cf->label(11, $y, "Altitude");
$cf->inputText(11, $y, 5, "altitude_min", $p_altitude_min, 10);
$cf->text(17, $y - 0.3, "<font size=\"+1\">&ndash;</font>");
$cf->inputText(18, $y, 5, "altitude_max", $p_altitude_max, 10);

$cf->label(54, $y, "Quadrant");
$cf->inputText(54, $y, 3, "quadrant", $p_quadrant, 10);
$cf->inputText(58, $y, 1, "quadrant_sub", $p_quadrant_sub, 10);
echo "<img border=\"0\" height=\"16\" src=\"webimages/convert.gif\" width=\"16\" "
   . "style=\"position:absolute; left:60.5em; top:" . ($y + .1) . "em\" onclick=\"convert()\">\n";

$y += 2;
$cf->label(11, $y, "Lat");
$cf->inputText(11, $y, 2, "lat_deg", $p_lat_deg, 5);
$cf->text(14, $y - 0.3, "<font size=\"+1\">&deg;</font>");
$cf->inputText(15, $y, 1.5, "lat_min", $p_lat_min, 5);
$cf->text(17.5, $y - 0.3, "<font size=\"+1\">&prime;</font>");
$cf->inputText(18.5, $y, 1.5, "lat_sec", $p_lat_sec, 5);
$cf->text(21, $y - 0.3, "<font size=\"+1\">&Prime;</font>");
$cf->dropdown(22, $y, "lat", $p_lat, array("N", "S"), array("N", "S"));

$cf->label(31, $y, "Lon");
$cf->inputText(31, $y, 2, "lon_deg", $p_lon_deg, 5);
$cf->text(34, $y - 0.3, "<font size=\"+1\">&deg;</font>");
$cf->inputText(35, $y, 1.5, "lon_min", $p_lon_min, 5);
$cf->text(37.5, $y - 0.3, "<font size=\"+1\">&prime;</font>");
$cf->inputText(38.5, $y, 1.5, "lon_sec", $p_lon_sec, 5);
$cf->text(41, $y - 0.3, "<font size=\"+1\">&Prime;</font>");
$cf->dropdown(42, $y, "lon", $p_lon, array("W", "E"), array("W", "E"));

$cf->label(54, $y, "exactn. (m)");
$cf->inputText(54, $y, 7, "exactness", $p_exactness, 30);
//$cf->dropdown(48,$y,"exactness",$p_exactness,$exactness[0],$exactness[1]);

$y += 1.75;
echo "<div style=\"position: absolute; left: 1em; top: {$y}em; width: 60.5em;\"><hr></div>\n";
//38.75

$y += 1.05;
$cf->labelMandatory(11, $y, 8, "Locality","#\" onclick=\"call_toggleLanguage();\" id=\"labelLocality");
$cf->textarea(11, $y, 50, 3.6, "Fundort1\" id=\"Fundort1", $p_Fundort);
echo "<input type=\"hidden\" name=\"Fundort2\" id=\"Fundort2\" value=\"$p_Fundort_engl\">\n";
echo "<input type=\"hidden\" name=\"toggleLanguage\" id=\"toggleLanguage\" value=\"0\">\n";

$y += 4.4;
$cf->label(11, $y, "habitat");
$cf->label(11, $y + 1, "phorophyte");
$cf->textarea(11, $y, 22, 2.4, "habitat", $p_habitat);
$cf->label(39, $y, "habitus");
$cf->textarea(39, $y, 22, 2.4, "habitus", $p_habitus);

$y += 3.3;
$cf->label(11, $y, "annotations");
$cf->textarea(11, $y, 50, 2.4, "Bemerkungen", $p_Bemerkungen);

$y += 3.5; // in Summe 50.5
if (($_SESSION['editControl'] & 0x2000) != 0) {
    //$cf->buttonSubmit(16, $y, "reload", " Reload \" onclick=\"reloadButtonPressed()");
    if ($p_specimen_ID) {
        if ($edit) {
            $cf->buttonJavaScript(16, $y, " Reset ", "self.location.href='editSpecimens.php?sel=<" . $p_specimen_ID . ">&edit=1'");
            //$cf->buttonSubmit(31, $y, "submitUpdate", " Update ", "");
            $cf->buttonJavaScript(31, $y, " Update ", "doSubmit( 'submitUpdate' );", "", "submitUpdate");
        } else {
            $cf->buttonJavaScript(16, $y, " Reset ", "self.location.href='editSpecimens.php?sel=<" . $p_specimen_ID . ">'");
            $cf->buttonJavaScript(31, $y, " Edit ", "self.location.href='editSpecimens.php?sel=<" . $p_specimen_ID . ">&edit=1'");
        }
        //$cf->buttonSubmit(47, $y, "submitNewCopy", " New &amp; Copy");
        $cf->buttonJavaScript(53, $y, " New &amp; Copy", "doSubmit( 'submitNewCopy' );", "", "submitNewCopy" );
    } else {
        $cf->buttonReset(22, $y, " Reset ");
//        $cf->buttonSubmit(31, $y, "submitUpdate", " Insert ", "", "doSubmit();");
        $cf->buttonJavaScript( 31, $y, " Insert ", "doSubmit( 'submitUpdate' );", "", "submitUpdate" );
//        $cf->buttonSubmit(37, $y, "submitUpdateCopy", " Insert &amp; Copy", "", "doSubmit();");
        $cf->buttonJavaScript(37, $y, " Insert &amp; Copy", "doSubmit( 'submitUpdateCopy' );", "", "submitUpdateCopy" );
//        $cf->buttonSubmit(47, $y, "submitUpdateNew", " Insert &amp; New", "", "doSubmit();");
        $cf->buttonJavaScript(53, $y, " Insert &amp; New", "doSubmit( 'submitUpdateNew' );", "", "submitUpdateNew" );
    }
}
$cf->buttonJavaScript(2, $y, " < Specimens ", "goBack($nr," . intval($p_specimen_ID) . "," . intval($edit) . "," . $_SESSION['sPTID'] . ")");
?>
</form>

<?php
if ($updateBlocked) {
    switch ($blockCause) {
        case 2:  // no write access to the new collection
?>
<script type="text/javascript" language="JavaScript">
  alert('Update/Insert blocked due to wrong Collection');
</script>
<?php
        break;
        case 1:  // HerbNummer and source_id already in database
?>
<script type="text/javascript" language="JavaScript">
  alert('Update/Insert blocked. Number already in database with specimenID <?php echo $blockSource; ?>');
</script>
<?php
        break;
    }
}
?>

</body>
</html>