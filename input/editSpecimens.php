<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");
require("inc/herbardb_input_functions.php");
require("inc/log_functions.php");
require __DIR__ . '/vendor/autoload.php';

use Jaxon\Jaxon;
use Jacq\Settings;

$jaxon = jaxon();
$jaxon->setOption('core.request.uri', 'ajax/editSpecimensServer.php');

$jaxon->register(Jaxon::CALLABLE_FUNCTION, "toggleLanguage");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "searchGeonames");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "searchGeonamesService");   // search for label **
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "useGeoname");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "makeLinktext");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "editLink");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "updateLink");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "deleteLink");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "editMultiTaxa");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "updateMultiTaxa");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "deleteMultiTaxa");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "displayMultiTaxa");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "displayCollectorLinks");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "updateNomService");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "updateGgbnIdentifier");

if (!isset($_SESSION['sPTID'])) {
    $_SESSION['sPTID'] = 0;
}

if (isset($_GET['ptid'])) {
    $_SESSION['sPTID'] = intval(filter_input(INPUT_GET, 'ptid'));
}

$nr = isset($_GET['nr']) ? intval(filter_input(INPUT_GET, 'nr')) : 0;
$linkList = $_SESSION['sLinkList'] ?? array();
$swBatch = (checkRight('batch')) ? true : false; // nur user mit Recht "batch" kann Batches aendern
$p_gbif_id = $p_dissco_id = "";
$p_notes_internal = "";


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
                 AND tg.genus LIKE '" . dbi_escape_string($pieces[0]) . "%'\n";
                 //AND ts.statusID != 1
        if (!empty($pieces[1])) {
            $sql .= "AND te.epithet LIKE '" . dbi_escape_string($pieces[1]) . "%'\n";
        }
        $sql .= "ORDER BY tg.genus, te.epithet";
        $result = dbi_query($sql);
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_array($result)) {
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
                 AND (tg.genus LIKE '" . dbi_escape_string($pieces[0]) . "%'
                  OR tgp1.genus LIKE '" . dbi_escape_string($pieces[0]) . "%'
                  OR tgp2.genus LIKE '" . dbi_escape_string($pieces[0]) . "%')\n";
        if (!empty($pieces[1])) {
            $sql .= "AND (tep1.epithet LIKE '" . dbi_escape_string($pieces[1]) . "%'
                      OR tep2.epithet LIKE '" . dbi_escape_string($pieces[1]) . "%')\n";
        }
        $sql .= "ORDER BY tg.genus, tep1.epithet, tgp2.genus, tep2.epithet";
        $result = dbi_query($sql);
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_array($result)) {
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
                    WHERE Sammler_2 LIKE '" . dbi_escape_string($pieces[0]) . "%'
                    ORDER BY Sammler_2";
        } else {
            $sql = "SELECT Sammler, SammlerID
                    FROM tbl_collector
                    WHERE Sammler LIKE '".dbi_escape_string($pieces[0])."%'
                    ORDER BY Sammler";
        }
        $result = dbi_query($sql);
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row=mysqli_fetch_array($result)) {
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

function getSpecifiedHerbNummerLength(int $source_id)
{
    $result = dbi_query("SELECT HerbNummerNrDigits FROM tbl_img_definition WHERE source_id_fk = '$source_id'");
    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['HerbNummerNrDigits'];
    } else {
        return 0;
    }
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
                 s.GBIF_ID, s.DiSSCo_ID, s.notes_internal,
                 c.SammlerID, c.Sammler, c2.Sammler_2ID, c2.Sammler_2,
                 mc.source_id
                FROM tbl_specimens s
                 LEFT JOIN tbl_management_collections mc ON mc.collectionID = s.collectionID
                 LEFT JOIN tbl_collector c ON c.SammlerID = s.SammlerID
                 LEFT JOIN tbl_collector_2 c2 ON c2.Sammler_2ID = s.Sammler_2ID
                WHERE specimen_ID = " . extractID($_GET['sel']);
        $result = dbi_query($sql);
        $resultValid = mysqli_num_rows($result) > 0;
    } else {
        $resultValid = false;
    }

    if ($resultValid) {
        $row = mysqli_fetch_array($result);
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
        $p_gbif_id     = $row['GBIF_ID'];
        $p_dissco_id   = $row['DiSSCo_ID'];
        $p_notes_internal = $row['notes_internal'];

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
                    WHERE ts.taxonID = '" . dbi_escape_string($row['taxonID']) . "'";
            $result2 = dbi_query($sql);
            $row2 = mysqli_fetch_array($result2);
            $p_external = $row2['external'];
            $p_taxonIndex = $row['taxonID'];
            $p_taxon = getScientificName( $p_taxonIndex, false, true, true );
        } else {
            $p_taxon = "";
            $p_taxonIndex = 0;
            $p_external = null;
        }
    } else {
        $p_specimen_ID = $p_CollNummer = $p_identstatus = "";
        $p_checked = $p_accessible = "1";
        $p_series = $p_series_number = $p_Nummer = $p_alt_number = $p_Datum = $p_Datum2 = $p_det = "";
        $p_typified = $p_taxon_alt = $p_taxon = $p_Bezirk = "";
        $p_external = null;
        $p_lat_deg = $p_lat_min = $p_lat_sec = ""; $p_lat = "N";
        $p_lon_deg = $p_lon_min = $p_lon_sec = ""; $p_lon = "E";
        $p_quadrant = $p_quadrant_sub = $p_exactness = $p_altitude_min = $p_altitude_max = "";
        $p_Fundort = $p_Fundort_engl = $p_habitat = $p_habitus = $p_Bemerkungen = "";
        $p_digital_image_obs = $p_garten = $p_voucher = $p_ncbi = "";
        $p_typus = $p_nation = $p_province = "";
        $p_sammler = $p_sammler2 = "";
        $p_taxonIndex = $p_sammlerIndex = $p_sammler2Index = 0;
        $p_institution = $_SESSION['sid'];
        $p_gbif_id = $p_dissco_id = "";
        $p_notes_internal = "";
        if ($p_institution) {
            $sql = "SELECT collectionID FROM tbl_management_collections WHERE source_id = '$p_institution' ORDER BY collection";
            $row = mysqli_fetch_array(dbi_query($sql));
            $p_collection = $row['collectionID'];
        } else {
            $p_collection = "";
        }
        $p_HerbNummer = $_GET['HerbNummer'] ?? "";
        $p_digital_image = $_GET['digitalImage'] ?? "";
    }
    $edit = !empty($_GET['edit']);
    if ($swBatch) {
        // read tbl_api_specimens
        $result = dbi_query("SELECT specimen_ID FROM api.tbl_api_specimens WHERE specimen_ID = " . extractID(filter_input(INPUT_GET, 'sel')));
        $p_batch = (mysqli_num_rows($result)>0) ? 1 : 0;
    }
} else {
    $p_collection        = $_POST['collection'] ?? "";
    $p_institution       = intval($_POST['institution'] ?? 0);
    $p_HerbNummer        = $_POST['HerbNummer'] ?? "";
    $p_CollNummer        = $_POST['CollNummer'] ?? "";
    $p_identstatus       = $_POST['identstatus'] ?? "";
    $p_batch             = filter_input(INPUT_POST, 'batch');
    $p_checked           = $_POST['checked'] ?? 0;
    $p_accessible        = $_POST['accessible'] ?? 0;
    $p_series            = $_POST['seriesIndex'] ?? "";
    $p_series_number     = $_POST['series_number'] ?? "";
    $p_Nummer            = $_POST['Nummer'] ?? "";
    $p_alt_number        = $_POST['alt_number'] ?? "";
    $p_Datum             = $_POST['Datum'] ?? "";
    $p_Datum2            = $_POST['Datum2'] ?? "";
    $p_det               = $_POST['det'] ?? "";
    $p_typified          = $_POST['typified'] ?? "";
    $p_taxon_alt         = $_POST['taxon_alt'] ?? "";
    $p_Bezirk            = $_POST['Bezirk'] ?? "";
    $p_quadrant          = $_POST['quadrant'] ?? "";
    $p_quadrant_sub      = $_POST['quadrant_sub'] ?? "";
    $p_exactness         = $_POST['exactness'] ?? "";
    $p_altitude_min      = (isset($_POST['altitude_min']) && $_POST['altitude_min'] !== "") ? intval($_POST['altitude_min']) : "";           // integers only
    $p_altitude_max      = (isset($_POST['altitude_max']) && $_POST['altitude_max'] !== "") ? intval($_POST['altitude_max']) : "";           // integers only
    $p_Fundort           = (!empty($_POST['toggleLanguage'])) ? $_POST['Fundort2'] : $_POST['Fundort1'];
    $p_Fundort_engl      = (!empty($_POST['toggleLanguage'])) ? $_POST['Fundort1'] : $_POST['Fundort2'];
    $p_habitat           = $_POST['habitat'] ?? "";
    $p_habitus           = $_POST['habitus'] ?? "";
    $p_Bemerkungen       = $_POST['Bemerkungen'] ?? "";
    $p_notes_internal    = $_POST['notes_internal'] ?? "";
    $p_digital_image     = filter_input(INPUT_POST, 'digital_image');
    $p_digital_image_obs = filter_input(INPUT_POST, 'digital_image_obs');
    $p_garten            = $_POST['garten'] ?? "";
    $p_voucher           = $_POST['voucher'] ?? "";
    $p_ncbi              = $_POST['ncbi'] ?? "";
    $p_taxon             = $_POST['taxon'] ?? "";
    $p_taxonIndex        = (strlen(trim($_POST['taxon'] ?? "")) > 0) ? $_POST['taxonIndex'] : 0;
    $p_external          = $_POST['external'] ?? "";
    $p_typus             = $_POST['typus'] ?? "";
    $p_nation            = $_POST['nation'] ?? "";
    $p_province          = $_POST['province'] ?? "";
    $p_sammler           = $_POST['sammler'] ?? "";
    $p_sammlerIndex      = (strlen(trim($_POST['sammler'] ?? "")) > 0) ? $_POST['sammlerIndex'] : 0;
    $p_sammler2          = $_POST['sammler2'] ?? "";
    $p_sammler2Index     = (strlen(trim($_POST['sammler2'] ?? "")) > 0) ? $_POST['sammler2Index'] : 0;
    $p_gbif_id = $p_dissco_id = "";
    if (!empty($_POST['specimen_ID'])) {
        $gbifResult = dbi_query("SELECT GBIF_ID, DiSSCo_ID FROM tbl_specimens WHERE specimen_ID = '" . intval($_POST['specimen_ID']) . "'");
        if ($gbifResult && $gbifResult->num_rows > 0) {
            $gbifRow = $gbifResult->fetch_assoc();
            $p_gbif_id = $gbifRow['GBIF_ID'] ?? "";
            $p_dissco_id = $gbifRow['DiSSCo_ID'] ?? "";
        }
    }
    $p_lat               = $_POST['lat'] ?? "";
    $p_lat_deg           = (($_POST['lat_deg'] ?? "") != "") ? intval($_POST['lat_deg']) : "";           // integers only
    $p_lat_min           = (($_POST['lat_min'] ?? "") != "") ? intval($_POST['lat_min']) : "";           // integers only
    $p_lat_sec           = (($_POST['lat_sec'] ?? "") != "") ? strtr($_POST['lat_sec'], ",", ".") : "";  // convert comma to dot as decimal separator
    $p_lon               = $_POST['lon'] ?? "";
    $p_lon_deg           = (($_POST['lon_deg'] ?? "") != "") ? intval($_POST['lon_deg']) : "";           // integers only
    $p_lon_min           = (($_POST['lon_min'] ?? "") != "") ? intval($_POST['lon_min']) : "";           // integers only
    $p_lon_sec           = (($_POST['lon_sec'] ?? "") != "") ? strtr($_POST['lon_sec'], ",", ".") : "";  // convert comma to dot as decimal separator

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
                    identstatusID = " . makeInt($p_identstatus) . ",
                    checked = " . (($p_checked) ? "'1'" : "'0'") . ",
                    `accessible` = " . (($p_accessible) ? "'1'" : "'0'") . ",
                    taxonID = " . extractID($p_taxon) . ",
                    SammlerID = " . extractID($p_sammler) . ",
                    Sammler_2ID = " . extractID($p_sammler2) . ",
                    seriesID = " . makeInt($p_series) . ",
                    series_number = " . quoteString($p_series_number) . ",
                    Nummer = " . quoteString($p_Nummer) . ",
                    alt_number = " . quoteString($p_alt_number) . ",
                    Datum = " . quoteString($p_Datum) . ",
                    Datum2 = " . quoteString($p_Datum2) . ",
                    det = " . quoteString($p_det) . ",
                    typified = " . quoteString($p_typified) . ",
                    typusID = " . makeInt($p_typus) . ",
                    taxon_alt = " . quoteString($p_taxon_alt) . ",
                    NationID = " . makeInt($p_nation) . ",
                    provinceID = " . makeInt($p_province) . ",
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
                    notes_internal = " . quoteString($p_notes_internal) . ",
                    digital_image = " . (($p_digital_image) ? "'1'" : "'0'") . ",
                    digital_image_obs = " . (($p_digital_image_obs) ? "'1'" : "'0'") . ",
                    garten = " . quoteString($p_garten) . ",
                    voucherID = " . makeInt($p_voucher) . ",
                    observation = '0'";

        if (intval($_POST['specimen_ID'])) {
            // check if the user has access to the old collection
            $sql = "SELECT source_id
                    FROM tbl_specimens, tbl_management_collections
                    WHERE tbl_specimens.collectionID = tbl_management_collections.collectionID
                     AND specimen_ID = '" . intval($_POST['specimen_ID']) . "'";
            $dummy = dbi_query($sql)->fetch_array();
            $checkSource = $dummy['source_id'] == $_SESSION['sid'];

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
        // check if the user has access to the new collection
        $sqlCheck = "SELECT source_id FROM tbl_management_collections WHERE collectionID = '" . intval($p_collection) . "'";
        $rowCheck = dbi_query($sqlCheck)->fetch_array();
        $p_institution = intval($rowCheck['source_id']);
        // allow write access to database if user is editor or is granted for both old and new collection
        if (checkRight('admin') || ($_SESSION['sid'] == $rowCheck['source_id'] && $checkSource)) {
            $dummy = dbi_query("SELECT s.`specimen_ID`
                                FROM `tbl_specimens` s, `tbl_management_collections` mc
                                WHERE s.`collectionID` = mc.`collectionID`
                                 AND s.`HerbNummer` = " . quoteString($p_HerbNummer) . "
                                 AND mc.`allowDuplicateHerbNr` = 0
                                 AND (   (mc.findDuplicatesIn = 'source' AND mc.source_id = " . intval($p_institution) . ")
                                      OR (mc.findDuplicatesIn = 'collection' AND mc.collectionID = " . intval($p_collection) . "))
                                 AND s.`specimen_ID` != '" . intval($_POST['specimen_ID']) . "'");
            if (mysqli_num_rows($dummy) > 0) {
                $updateBlocked = true;
                $blockCause = 1;  // HerbNummer and source_id already in database
                $dummyRow = mysqli_fetch_array($dummy);
                $blockSource = $dummyRow['specimen_ID'];
                $edit = ($_POST['edit'] ?? 0) ? true : false;
                $p_specimen_ID = $_POST['specimen_ID'];
            } else {
                if ($updated) {
                    $p_specimen_ID = intval($_POST['specimen_ID']);
                    $dbLink->begin_transaction();
                    logSpecimen($p_specimen_ID, $updated);
                    $result = dbi_query($sql);
                    if (!$result) {
                        $errorEdited = $dbLink->errno . ": " . $dbLink->error;
                        $dbLink->rollback();
                    } else {
                        $dbLink->commit();
                    }
                } else {
                    $result = dbi_query($sql);
                    if ($result) {
                        $p_specimen_ID = dbi_insert_id();
                        logSpecimen($p_specimen_ID, $updated);
                    } else {
                        $errorEdited = $dbLink->errno . ": " . $dbLink->error;
                    }
                }

                if (!empty($_POST['submitUpdateNew'])) {
                    $location="Location: editSpecimens.php?sel=<0>&new=1";
                    if (SID) $location .= "&" . SID;
                    header($location);
                } elseif (!empty($_POST['submitUpdateCopy'])) {
                    $location="Location: editSpecimens.php?sel=<".$p_specimen_ID.">&new=1";
                    if (SID) $location .= "&" . SID;
                    header($location);
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
    } else if (!empty($_POST['submitNewCopy'])) {
        $p_specimen_ID = "";
        $copyBits = dbi_query("SELECT copy_bits FROM metadb WHERE source_id_fk = {$_SESSION['sid']}")->fetch_assoc()['copy_bits'];
        if (strpos($copyBits, "digital_image") === false) {
            $p_digital_image = $p_digital_image_obs = "";  // don't copy digital image checkmark
        }
        $edit = false;
    } else {
        $edit = (!empty($_POST['edit'])) ? true : false;
        $p_specimen_ID = $_POST['specimen_ID'];
    }
}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
        "http://www.w3.org/TR/html4/loose.dtd">
<html lang="en">
<head>
  <title>herbardb - edit Specimens</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <link rel="stylesheet" type="text/css" href="https://code.jquery.com/ui/1.13.2/themes/ui-lightness/jquery-ui.css">
<!--    <link rel="stylesheet" type="text/css" href="js/lib/jQuery/css/ui-lightness/jquery-ui.custom.css">-->
  <style type="text/css">
    html.waiting, html.waiting * {
      cursor: wait !important;
    }
    .important {
      background-color: lightgreen;
    }
    .lat_lon_dialog td {
      padding: 2px 1ex;
    }
    .lat_lon_dialog td span {
      font-size: x-large;
    }
    #open_latLonQuDialog, #del_latLon, #taxon_alt_toggle {
      padding: 0;
      margin: 2px;
    }
    #stblIDbox table tr th {
        border-bottom: 1px solid black;
        padding-left: 10px;
        padding-right: 10px;
    }
    #stblIDbox table tr td {
        padding-left: 10px;
        padding-right: 10px;
    }
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
    #displayCollectorLinks img {
        vertical-align: top;
    }
    .textarea-raise {
        position: relative !important;
        z-index: 99999 !important;
        box-shadow: 0 0 12px rgba(0, 0, 0, 0.35);
        background: #fff;
    }
  </style>
  <?php echo $jaxon->getScript(true, true); ?>
  <script src="js/lib/overlib/overlib.js" type="text/javascript"></script>
  <script src="https://code.jquery.com/jquery-3.7.0.min.js"
          integrity="sha256-2Pmvv0kuTBOenSvLm6bvfBSSHrUJ+3A7x6P5Ebd07/g="
          crossorigin="anonymous"></script>
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"
          integrity="sha256-VazP97ZCwtekAsvgPBSUwPFKdrwD3unUfSGVYrahUqU="
          crossorigin="anonymous"></script>
  <script src="js/jacqLatLonQuad.js" type="text/javascript"></script>
<!--  <script src="js/lib/jQuery/jquery.min.js" type="text/javascript"></script>-->
<!--  <script src="js/lib/jQuery/jquery-ui.custom.min.js" type="text/javascript"></script>-->
  <script src="js/freudLib.js" type="text/javascript"></script>
  <script src="js/parameters.php" type="text/javascript"></script>
  <script type="text/javascript">
      let reload = false;
      let linktext = '';
      let dialog_latLonQu;
      let geoname_user = "<?php echo $_OPTIONS['GEONAMES']['username']; ?>";
      let specifiedHerbNummerLength = <?php echo getSpecifiedHerbNummerLength($p_institution ?? 0); ?>;
      let oldHerbNumber = <?php echo (is_numeric($p_HerbNummer) && $edit) ? "'$p_HerbNummer'" : 0; ?>;
      let errorEdited = "<?php echo $errorEdited ?? ""; ?>";
      let institutionEditLocked = <?php echo (!empty($p_specimen_ID) && $edit) ? 'true' : 'false'; ?>;
      let canMoveAcrossInstitutions = <?php echo (checkRight('admin') ? 'true' : 'false'); ?>;
      let currentSpecimenId = <?php echo intval($p_specimen_ID); ?>;
      let currentListPage = <?php echo intval($_SESSION['sCurrentSpecimenPage'] ?? 0); ?>;
      let linkEditUnsaved = { tracking: false, initial: '' };

      jaxon_makeLinktext(currentSpecimenId);
      $.extend({ alert: function (message, title) {
              $("<div></div>").dialog( {
                  buttons: { "Ok": function () { $(this).dialog("close"); } },
                  close: function (event, ui) { $(this).remove(); },
                  resizable: false,
                  title: title,
                  modal: true,
                  height: 'auto',
                  width: 'auto',
                  position: { my: "right center", at: "center", of: window },
              }).html(message);
          }
      });

      if (errorEdited) {
          alert(errorEdited);
      }
  </script>
  <script src="js/editSpecimensJs.js" type="text/javascript"></script>
</head>

<body>

<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>
<div id="iBox_content" style="display:none;"></div>

<form Action="<?php echo $_SERVER['PHP_SELF']; ?>" Method="POST" name="f" id="f">
<input type="hidden" name="submit_type" id="submit_type" value="">

<?php
unset($institution);
$result = dbi_query("SELECT source_id, source_code FROM herbarinput.meta ORDER BY source_code");
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_array($result)) {
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
$result = dbi_query($sql);
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_array($result)) {
        $collection[0][] = $row['collectionID'];
        $collection[1][] = $row['collection'];
    }
}
if (!in_array($p_collection, $collection[0]) && count($collection[0]) > 1) {
    $p_collection = $collection[0][1];
}


unset($typus);
$typus[0][] = 0; $typus[1][] = "";
$result = dbi_query("SELECT typus_lat, typusID FROM tbl_typi ORDER BY typus_lat");
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_array($result)) {
        $typus[0][] = $row['typusID'];
        $typus[1][] = $row['typus_lat'];
    }
}

unset($identstatus);
$identstatus[0][] = 0; $identstatus[1][] = "";
$result = dbi_query("SELECT identstatusID, identification_status FROM tbl_specimens_identstatus ORDER BY identification_status");
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_array($result)) {
        $identstatus[0][] = $row['identstatusID'];
        $identstatus[1][] = $row['identification_status'];
    }
}

$result = dbi_query("SELECT series FROM tbl_specimens_series WHERE seriesID = '$p_series'");
if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_array($result);
    $p_seriesName = $row['series'] . " <" . $p_series . ">";
} else {
    $p_seriesName = '';
}

unset($nation);
$nation[0][] = 0; $nation[1][] = "";
$result = dbi_query("SELECT nation_engl, nationID, iso_alpha_2_code FROM tbl_geo_nation ORDER BY nation_engl");
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_array($result)) {
        $nation[0][] = $row['nationID'];
        $nation[1][] = $row['nation_engl'] . " (" . $row['iso_alpha_2_code'] . ")";
    }
}

unset($province);
$province[0][] = 0; $province[1][] = "";
$result = dbi_query("SELECT provinz, provinceID, usgs_number FROM tbl_geo_province
                    WHERE nationID = '" . intval($p_nation) . "'
                    ORDER BY provinz");
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_array($result)) {
        $province[0][] = $row['provinceID'];
        $province[1][] = $row['provinz'] . (($row['usgs_number']) ? " ({$row['usgs_number']})" : '');
    }
}

unset($voucher);
$voucher[0][] = 0; $voucher[1][] = "";
$result = dbi_query("SELECT voucherID, voucher FROM tbl_specimens_voucher ORDER BY voucher");
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_array($result)) {
        $voucher[0][] = $row['voucherID'];
        $voucher[1][] = $row['voucher'];
    }
}

$stblID = array();
if (!empty($p_specimen_ID)) {
    $result = dbi_query("SELECT stableIdentifier, visible, timestamp, error, blockedBy FROM tbl_specimens_stblid WHERE specimen_ID = $p_specimen_ID ORDER BY timestamp DESC");
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_array($result)) {
            $stblID[] = array('stblID'    => $row['stableIdentifier'],
                              'visible'   => $row['visible'],
                              'timestamp' => $row['timestamp'],
                              'error'     => $row['error'],
                              'blockedBy' => $row['blockedBy']);
        }
    }
}

if ($nr) {
    echo "<div style=\"position: absolute; left: 16em; top: 0.4em;\">";
    if ($nr > 1) {
        echo "<a href=\"editSpecimens.php?sel=" . htmlentities("<" . $linkList[$nr - 1] . ">") . "&nr=" . ($nr - 1) . "\">"
           . "<img border='0' height='22' src='webimages/left.gif' width='20' alt='<'>"
           . "</a>";
    } else {
        echo "<img border='0' height='22' src='webimages/left_gray.gif' width='20' alt='<'>";
    }
    echo "</div>\n";
    echo "<div style=\"position: absolute; left: 17.5em; top: 0.4em;\">";
    if ($nr < $linkList[0]) {
        echo "<a href=\"editSpecimens.php?sel=" . htmlentities("<" . $linkList[$nr + 1] . ">") . "&nr=" . ($nr + 1) . "\">"
           . "<img border='0' height='22' src='webimages/right.gif' width='20' alt='>'>"
           . "</a>";
    } else {
        echo "<img border='0' height='22' src='webimages/right_gray.gif' width='20' alt='>'>";
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

// if specimen-ID is valid and there are any pictures, check if they are on an iiif-server
$target = (($p_digital_image || $p_digital_image_obs) && $p_specimen_ID) ? getIiifLink($p_specimen_ID) : '';
if ($p_digital_image && $p_specimen_ID) {
    if ($target) {
        $cf->label(32, $y, "dig.image", "javascript:showIiif('$target')");
    } else {
        $cf->label(32, $y, "dig.image", "javascript:showImage('$p_specimen_ID')");
    }
} else {
    $cf->label(32, $y, "dig.image");
}
$cf->checkbox(32, $y, "digital_image", $p_digital_image);
if ($p_digital_image_obs && $p_specimen_ID) {
    if ($target) {
        $cf->label(42, $y, "dig.im.obs.", "javascript:showIiif('$target')");
    } else {
        $cf->label(42, $y, "dig.im.obs.", "javascript:showImageObs('$p_specimen_ID')");
    }
} else {
    $cf->label(42, $y, "dig.im.obs.");
}
$cf->checkbox(42, $y, "digital_image_obs", $p_digital_image_obs);
$cf->labelMandatory(50.5, $y, 5, "checked");
$cf->checkbox(50.5, $y, "checked", $p_checked);
$cf->labelMandatory(60.5, $y, 6, "accessible");
$cf->checkbox(60.5, $y, "accessible", $p_accessible);
if ($p_specimen_ID && !$edit) {
    $cf->text(63, $y+0.3, "<a href='https://www.jacq.org/detail.php?ID=$p_specimen_ID' title='JACQ detail' target='_blank'>"
                        . "<img src='webimages/JACQ_LOGO.png' alt='JACQ'></a>");
}

if (!empty($stblID)) {
    if (count($stblID) > 1) {
        $cf->text(67, $y + 0.3, "<a href='#' onclick='open_stblIDbox();'>multiple entries</a>");
    } else {
        if (empty($stblID[0]['error'])) {
            $cf->text(67, $y + 0.3, "<a href='{$stblID[0]['stblID']}' title='JACQ stable identifier' target='_blank'>"
                        . $stblID[0]['stblID'] . "</a>");
        } else {
            $cf->text(67, $y + 0.3, "<a href='editSpecimens.php?sel=" . htmlentities("<" . $stblID[0]['blockedBy'] . ">") . "'>{$stblID[0]['error']}</a>");
        }
    }
}

$identifierIcons = array();
if (!empty($p_gbif_id)) {
    $gbifValue = htmlspecialchars($p_gbif_id, ENT_QUOTES, 'UTF-8');
    $identifierIcons[] = "<a href='{$gbifValue}' target='_blank' rel='noopener' title='GBIF'>"
                       . "<img src='https://jacq.org/logo/services/serviceID51_logo.png' alt='GBIF' height='30px'>"
                       . "</a>";
}
if (!empty($p_dissco_id)) {
    $disscoValue = htmlspecialchars($p_dissco_id, ENT_QUOTES, 'UTF-8');
    $identifierIcons[] = "<a href='{$disscoValue}' target='_blank' rel='noopener' title='DiSSCo'>"
                       . "<img src='https://jacq.org/logo/institutions/dissco-logo.png' alt='DiSSCo' height='30px'>"
                       . "</a>";
}
$identifierIcons[] = "<span id='ggbnIdentifier'></span>";
$cf->text(67, $y + 1.6, implode("&nbsp;", $identifierIcons));


$y += 2;
//$institution = mysqli_fetch_array(dbi_query("SELECT coll_short_prj FROM tbl_management_collections WHERE collectionID='$p_collection'"));
$cf->labelMandatory(11, $y, 9, "Institution");
//$cf->text(9,$y,"&nbsp;".strtoupper($institution['coll_short_prj']));
$institutionDropdownAttr = (!empty($p_specimen_ID) && $edit)
    ? "institution"
    : "institution\" onchange=\"reload=true; self.document.f.submit();";
$cf->dropdown(11, $y, $institutionDropdownAttr, $p_institution, $institution[0], $institution[1]);

$cf->labelMandatory(23, $y, 6, "HerbarNr.");
$cf->inputText(23, $y, 10, "HerbNummer", $p_HerbNummer, 100);

$cf->labelMandatory(40.5, $y, 6, "Collection");
$cf->dropdown(40.5, $y, "collection", $p_collection, $collection[0], $collection[1]);
$cf->label(59, $y, "Nr.");
$cf->inputText(59, $y, 5.7, "CollNummer", $p_CollNummer, 25);

$y += 2;
$cf->label(11, $y, "links", "#\" onclick=\"jaxon_editLink('$p_specimen_ID');\" onmouseover=\"return overlib(linktext, STICKY, CAPTION, 'Links to', MOUSEOFF, FGCOLOR, '#008000', DELAY, 500);\" onmouseout=\"return nd();");
$cf->label(44, $y, "T", "javascript:editSpecimensTypes('$p_specimen_ID')");
$cf->label(47, $y, "type");
$typusOptions = '';
for ($i = 0; $i < count($typus[0]); $i++) {
    $value = htmlspecialchars($typus[0][$i], ENT_QUOTES, 'UTF-8');
    $label = htmlspecialchars($typus[1][$i], ENT_QUOTES, 'UTF-8');
    $selected = ($typus[0][$i] == $p_typus) ? ' selected' : '';
    $typusOptions .= "<option value=\"{$value}\"{$selected}>{$label}</option>";
}
echo "<div style='position:absolute; left: 44em; top: {$y}em; width: 21.5em; text-align: right; white-space: nowrap;'>";
echo "  <select name=\"typus\" id=\"typus\" style=\"width: 100%; max-width: 21.5em;\">{$typusOptions}</select>";
echo "</div>\n";

$y += 2;
$cf->label(11, $y,"Status");
$cf->dropdown(11, $y, "identstatus", $p_identstatus, $identstatus[0], $identstatus[1]);

$cf->label(25, $y, "Garden");
$cf->inputText(25, $y, 11, "garten", $p_garten, 50);

echo "<img border=\"1\" height=\"16\" src=\"webimages/ncbi.gif\" width=\"14\" ".
     "style=\"position:absolute; left:38em; top:" . ($y + 0.2) . "em\"";
if ($p_ncbi) echo " title=\"$p_ncbi\"";
echo " onclick=\"editNCBI($p_specimen_ID)\">\n";
$cf->label(44, $y, "voucher","javascript:editVoucher()");
$voucherOptions = '';
for ($i = 0; $i < count($voucher[0]); $i++) {
    $value = htmlspecialchars($voucher[0][$i], ENT_QUOTES, 'UTF-8');
    $label = htmlspecialchars($voucher[1][$i], ENT_QUOTES, 'UTF-8');
    $selected = ($voucher[0][$i] == $p_voucher) ? ' selected' : '';
    $voucherOptions .= "<option value=\"{$value}\"{$selected}>{$label}</option>";
}
echo "<div style='position:absolute; left: 44em; top: {$y}em; width: 21.5em; text-align: right; white-space: nowrap;'>";
echo "  <select name=\"voucher\" id=\"voucher\" style=\"width: 100%; max-width: 21.5em;\">$voucherOptions</select>";
echo "</div>\n";

$y += 2;
if (($_SESSION['editControl'] & 0x1) != 0 || ($_SESSION['linkControl'] & 0x1) != 0) {
    $cf->labelMandatory(11, $y, 9, "taxon", "javascript:editSpecies(document.f.taxon)");
} else {
    $cf->labelMandatory(11, $y, 9, "taxon");
}
//$cf->editDropdown(9, $y, 46, "taxon", $p_taxon, makeTaxon2($p_taxon), 520, 0, ($p_external) ? 'red' : '');
$cf->inputJqAutocomplete(11, $y, 54, "taxon", $p_taxon, $p_taxonIndex, "index_jq_autocomplete.php?field=taxonWithHybridsNew", 520, 2, ($p_external) ? 'red' : '');
echo "<input type=\"hidden\" name=\"external\" value=\"$p_external\">\n";
$cf->label(11, $y + 1.5, "multi", "#\" onclick=\"jaxon_editMultiTaxa('$p_specimen_ID');");
$cf->text(11.5, $y + 1.7, "", "multiTaxaText");

$cf->text(67, $y + 0.3, "", "nomService");  // will be filled asynchronously by jaxon_updateNomService

$y += 4;
$cf->labelMandatory(11, $y, 9, "det / rev / conf");
$cf->inputText(11, $y, 54, "det", $p_det, 255);

$y += 2;
$cf->labelMandatory(11, $y, 9, "ident. history");
$cf->textarea(11, $y, 54, 2.4, "taxon_alt_ta", $p_taxon_alt);
$cf->inputText(11, $y, 54, "taxon_alt", $p_taxon_alt);
echo "<div style='position:absolute; left: 63.4em; top: {$y}em'><button id='taxon_alt_toggle'></button></div>";

$y += 3.5;
$cf->label(11, $y, "typified by");
$cf->inputText(11, $y, 54, "typified", $p_typified, 255);

$y += 2;
//$cf->label(9, $y, "Series", "javascript:editSeries()");
//$cf->dropdown(9, $y, "series", $p_series, $series[0], $series[1]);
//$cf->label(49.5, $y, "ser.Nr.");
//$cf->inputText(49.5, $y, 5.5, "series_number", $p_series_number, 50);
$cf->label(11, $y, "Series", "javascript:editSeries()");
$cf->inputJqAutocomplete(11, $y, 35, "series", $p_seriesName, $p_series, "index_jq_autocomplete.php?field=series", 520, 2, "", "", false, true );
$cf->label(58.5, $y, "ser.Nr.");
$cf->inputText(58.5, $y, 6.5, "series_number", $p_series_number, 50);

$y += 2;
$cf->labelMandatory(11, $y, 9, "first collector", "javascript:editCollector(document.f.sammler)");
//$cf->editDropdown(9, $y, 46, "sammler", $p_sammler, makeSammler2($p_sammler, 1), 270);
$cf->inputJqAutocomplete(11, $y, 46, "sammler", $p_sammler, $p_sammlerIndex, "index_jq_autocomplete.php?field=collector", 520, 2);
$displayCollectorLinksTop = $y; // same row as collector field, aligned to ser.Nr. column
echo "<div style='position: absolute; left: 58.5em; top: {$displayCollectorLinksTop}em; width: 6.5em; text-align: right; white-space: nowrap;' id='displayCollectorLinks'></div>\n";
$cf->label(11, $y + 1.7, "search", "javascript:searchCollector()");

$y += 4;
$cf->labelMandatory(11, $y, 9, "Number");
$cf->inputText(11, $y, 4, "Nummer", $p_Nummer, 10);
$cf->labelMandatory(22, $y, 5, "alt.Nr.");
$cf->inputText(22, $y, 18, "alt_number", $p_alt_number, 50);
$cf->labelMandatory(48, $y, 4, "Date");
$cf->inputText(48, $y, 6.5, "Datum", $p_Datum, 25);
$cf->text(56.5, $y - 0.3, "<span style='font-size: large'>&ndash;</span>");
$cf->inputText(58.5, $y, 6.5, "Datum2", $p_Datum2, 25);

$y += 2;
$cf->label(11, $y, "add. collector(s)", "javascript:editCollector2(document.f.sammler2)");
//$cf->editDropdown(9, $y, 46, "sammler2", $p_sammler2, makeSammler2($p_sammler2, 2), 270);
$cf->inputJqAutocomplete(11, $y, 54, "sammler2", $p_sammler2, $p_sammler2Index, "index_jq_autocomplete.php?field=collector2", 520, 2);
$cf->label(11, $y + 1.7, "search", "javascript:searchCollector2()");

$y += 3.25;
echo "<div style=\"position: absolute; left: 1em; top: {$y}em; width: 63.5em;\"><hr></div>\n";

$y += 1.25;
$cf->label(11, $y, "Country");
if (($_SESSION['editControl'] & 0x2000) != 0) {
    $cf->dropdown(11, $y, "nation\" onchange=\"reload=true; self.document.f.submit();", $p_nation, $nation[0], $nation[1]);
} else {
    $cf->dropdown(11, $y, "nation", $p_nation, $nation[0], $nation[1]);
}
$cf->label(49, $y, "Province");
$provinceOptions = '';
for ($i = 0; $i < count($province[0]); $i++) {
    $value = htmlspecialchars($province[0][$i], ENT_QUOTES, 'UTF-8');
    $label = htmlspecialchars($province[1][$i], ENT_QUOTES, 'UTF-8');
    $selected = ($province[0][$i] == $p_province) ? ' selected' : '';
    $provinceOptions .= "<option value=\"{$value}\"{$selected}>{$label}</option>";
}
echo "<div style='position:absolute; left: 49em; top: {$y}em; width: 16.5em; text-align: right; white-space: nowrap;'>"
   . "<select name=\"province\" id=\"province\" style=\"width: 100%; max-width: 16.5em;\">{$provinceOptions}</select>"
   . "</div>\n";

$y += 2;
$cf->label(10, $y, "geonames","#\" onclick=\"jaxon_searchGeonames(document.f.Bezirk.value);");
//$cf->label(35, $y, "**","#\" onclick=\"jaxon_searchGeonamesService(document.f.Bezirk.value);");
$cf->inputText(11, $y, 20, "Bezirk", $p_Bezirk, 255);  //TODO: Bezirk seems to be unused???

$y += 2;
$cf->label(11, $y, "Altitude");
$cf->inputText(11, $y, 5, "altitude_min", $p_altitude_min, 10);
$cf->text(17, $y - 0.3, "<span style='font-size: large'>&ndash;</span>");
$cf->inputText(18, $y, 5, "altitude_max", $p_altitude_max, 10);

$cf->label(57, $y, "Quadrant");
$cf->inputText(57, $y, 5, "quadrant", $p_quadrant, 10);
$cf->inputText(63, $y, 2, "quadrant_sub", $p_quadrant_sub, 10);
//echo "<img id=\"open_latLonQuDialog\" border=\"0\" height=\"16\" src=\"webimages/convert.gif\" width=\"16\" "
//    . "style=\"position:absolute; left:63.5em; top:" . ($y + .1) . "em\">\n";

$y += 2;
$cf->label(11, $y, "Lat");
$cf->inputText(11, $y, 2, "lat_deg", $p_lat_deg, 5);
$cf->text(14, $y - 0.3, "<span style='font-size: larger; '>&deg;</span>");
$cf->inputText(15, $y, 1.5, "lat_min", $p_lat_min, 5);
$cf->text(17.5, $y - 0.3, "<span style='font-size: larger; '>&prime;</span>");
$cf->inputText(18.5, $y, 1.5, "lat_sec", $p_lat_sec, 5);
$cf->text(21, $y - 0.3, "<span style='font-size: larger; '>&Prime;</span>");
$cf->dropdown(22, $y, "lat", $p_lat, array("N", "S"), array("N", "S"));

$cf->label(28.5, $y, "Lon");
$cf->inputText(28.5, $y, 2, "lon_deg", $p_lon_deg, 5);
$cf->text(31.5, $y - 0.3, "<span style='font-size: larger; '>&deg;</span>");
$cf->inputText(32.5, $y, 1.5, "lon_min", $p_lon_min, 5);
$cf->text(35, $y - 0.3, "<span style='font-size: larger; '>&prime;</span>");
$cf->inputText(36, $y, 1.5, "lon_sec", $p_lon_sec, 5);
$cf->text(38.5, $y - 0.3, "<span style='font-size: larger; '>&Prime;</span>");
$cf->dropdown(39.5, $y, "lon", $p_lon, array("W", "E"), array("W", "E"), '');

echo "<div style='position:absolute; left: 43.5em; top: {$y}em'><button id='del_latLon'></button></div>";
echo "<div style='position:absolute; left: 46.5em; top: {$y}em'><button id='open_latLonQuDialog'></button></div>";

$cf->label(57, $y, "exactn. (m)");
$cf->inputText(57, $y, 8, "exactness", $p_exactness, 30);
//$cf->dropdown(48,$y,"exactness",$p_exactness,$exactness[0],$exactness[1]);

$y += 1.75;
echo "<div style=\"position: absolute; left: 1em; top: {$y}em; width: 63.5em;\"><hr></div>\n";
//38.75

$y += 1.05;
$cf->labelMandatory(11, $y, 9, "Locality","#\" onclick=\"call_toggleLanguage();\" id=\"labelLocality");
$cf->textarea(11, $y, 54, 3.6, "Fundort1\" id=\"Fundort1", $p_Fundort);
$cf->text(65, $y - 1.6, "<div class=\"cssflabel\" style=\"width: 9.375em;\">notes internal&nbsp;</div>");
$cf->textarea(67, $y, 30, 10, "notes_internal", $p_notes_internal);
echo "<input type=\"hidden\" name=\"Fundort2\" id=\"Fundort2\" value=\"$p_Fundort_engl\">\n";
echo "<input type=\"hidden\" name=\"toggleLanguage\" id=\"toggleLanguage\" value=\"0\">\n";

$y += 4.4;
$cf->label(11, $y, "habitat");
$cf->label(11, $y + 1, "phorophyte");
$cf->textarea(11, $y, 23.5, 2.4, "habitat", $p_habitat);
$cf->label(41, $y, "habitus");
$cf->textarea(41, $y, 24, 2.4, "habitus", $p_habitus);

$y += 3.3;
$cf->label(11, $y, "annotations");
$cf->textarea(11, $y, 54, 2.4, "Bemerkungen", $p_Bemerkungen);

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
        $cf->buttonJavaScript(58.5, $y, " New &amp; Copy", "doSubmit( 'submitNewCopy' );", "", "submitNewCopy" );
    } else {
        $cf->buttonReset(22, $y, " Reset ");
//        $cf->buttonSubmit(31, $y, "submitUpdate", " Insert ", "", "doSubmit();");
        $cf->buttonJavaScript( 31, $y, " Insert ", "doSubmit( 'submitUpdate' );", "", "submitUpdate" );
//        $cf->buttonSubmit(37, $y, "submitUpdateCopy", " Insert &amp; Copy", "", "doSubmit();");
        $cf->buttonJavaScript(37, $y, " Insert &amp; Copy", "doSubmit( 'submitUpdateCopy' );", "", "submitUpdateCopy" );
//        $cf->buttonSubmit(47, $y, "submitUpdateNew", " Insert &amp; New", "", "doSubmit();");
        $cf->buttonJavaScript(58.5, $y, " Insert &amp; New", "doSubmit( 'submitUpdateNew' );", "", "submitUpdateNew" );
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
<div style="display:none" id="latLonQuDialog" title="Edit Lat/Lon and Quadrant">
    <form action="javascript:void(0);">
        <table class="lat_lon_dialog">
            <tr><td></td><th>Latitude (+N/-S)</th><th>Longitude (+E/-W)</th><td colspan="2"></td></tr>
            <tr>
                <td></td>
                <td><input class="dialog_sint important" style="width: 2em;" type="text" name="lat_dms_d"><span>&deg;</span>
                    <input class="dialog_int important" style="width: 2em;" type="text" name="lat_dms_m"><span>&prime;</span>
                    <input class="dialog_float important" style="width: 3em;" type="text" name="lat_dms_s"><span>&Prime;</span></td>
                <td><input class="dialog_sint important" style="width: 2em;" type="text" name="lon_dms_d"><span>&deg;</span>
                    <input class="dialog_int important" style="width: 2em;" type="text" name="lon_dms_m"><span>&prime;</span>
                    <input class="dialog_float important" style="width: 3em;" type="text" name="lon_dms_s"><span>&Prime;</span></td>
                <td><button id="d_btn_dms_convert">convert</button></td>
                <td></td>
            </tr><tr>
                <td style="font-weight: bold">OR</td>
                <td><input class="dialog_sint" style="width: 2em;" type="text" name="lat_dmm_d"><span>&deg;</span>
                    <input class="dialog_float" style="width: 4em;" type="text" name="lat_dmm_m"><span>&prime;</span></td>
                <td><input class="dialog_sint" style="width: 2em;" type="text" name="lon_dmm_d"><span>&deg;</span>
                    <input class="dialog_float" style="width: 4em;" type="text" name="lon_dmm_m"><span>&prime;</span></td>
                <td><button id="d_btn_dmm_convert">convert</button></td>
                <td></td>
            </tr><tr>
                <td style="font-weight: bold">OR</td>
                <td><input class="dialog_sfloat" style="width: 5em;" type="text" name="lat_ddd"><span>&deg;</span></td>
                <td><input class="dialog_sfloat" style="width: 5em;" type="text" name="lon_ddd"><span>&deg;</span></td>
                <td><button id="d_btn_ddd_convert">convert</button></td>
                <td><button id="d_btn_check">check with geonames</button></td>
            </tr><tr>
                <td colspan="4">&nbsp;</td>
            </tr><tr>
                <td></td>
                <td style="text-align: right; font-weight: bold;">Quadrant </td>
                <td><input class="dialog_int important" style="width: 5em;" type="text" name="quad"> <span>/</span>
                    <input class="dialog_int important" style="width: 2em;" type="text" name="quad_sub">
                </td>
                <td colspan="2"><button id="d_btn_quad_convert">convert to Lat/Lon</button></td>
            </tr><tr>
                <td></td>
                <td style="text-align: right; font-weight: bold;"><label for="latLonQuDialog_utm">UTM </label></td>
                <td><input style="width: 12em;" type="text" name="utm" id="latLonQuDialog_utm" placeholder="eg. 33 N 601779 5340548">
                </td>
                <td colspan="2"><button id="d_btn_utm_convert">convert to Lat/Lon</button></td>
            </tr><tr>
                <td></td>
                <td style="text-align: right; font-weight: bold;"><label for="latLonQuDialog_mgrs">MGRS </label></td>
                <td><input style="width: 12em;" type="text" name="mgrs" id="latLonQuDialog_mgrs" placeholder="eg. 33UXP0177940548">
                </td>
                <td colspan="2"><button id="d_btn_mgrs_convert">convert to Lat/Lon + UTM</button></td>
            </tr>
        </table>
    </form>
</div>
<div style="display:none" id="institutionChangeDialog" title="Institution change not allowed">
    <p>It is not foreseen to change the source Institution of a specimen.</p>
    <p>For new specimens based on existing data please use "New &amp; Copy" instead of "Edit/Update".</p>
    <p>For existing specimens with a wrong institution, please contact one of the admins: Heimo / Dominik / Johannes</p>
</div>
<div style="display:none" id="stblIDbox">
    <?php
    if (!empty($stblID) && count($stblID) > 1) {
        echo "<table><tr><th>stblID</th><th>timestamp</th><th>visible</th></tr>";
        foreach ($stblID as $item) {
            echo "<tr>";
            if (empty($item['error'])) {
                echo "<td><a href='{$item['stblID']}' title='JACQ stable identifier' target='_blank'>"  . $item['stblID'] . "</a></td>"
                   . "<td>{$item['timestamp']}</td>"
                   . "<td style='text-align: center'>" . (($item['visible']) ? "true" : "false") . "</td>";
            } else {
                echo "<td><a href='editSpecimens.php?sel=" . htmlentities("<" . $item['blockedBy'] . ">") . "'>error: {$item['error']}</a></td>"
                   . "<td>{$item['timestamp']}</td>"
                   . "<td style='text-align: center'>-</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    ?>
</div>

</body>
</html>
