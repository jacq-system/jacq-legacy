<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");
require("inc/herbardb_input_functions.php");
require("inc/log_functions.php");
require __DIR__ . '/vendor/autoload.php';

use Jaxon\Jaxon;

$jaxon = jaxon();
$jaxon->setOption('core.request.uri', 'ajax/editSpecimensServer.php');

$jaxon->register(Jaxon::CALLABLE_FUNCTION, "toggleLanguage");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "searchGeonames");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "useGeoname");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "makeLinktext");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "editLink");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "updateLink");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "deleteLink");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "editMultiTaxa");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "updateMultiTaxa");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "deleteMultiTaxa");

if (!empty($_POST['submitMakeSpecimen']) && isset($_POST['specimen_ID']) && intval($_POST['specimen_ID'])) {
    $location="Location: editSpecimens.php?sel=<" . intval($_POST['specimen_ID']) . ">";
    if (SID) $location .= "&" . SID;
    header($location);
    die();
}

$nr = isset($_GET['nr']) ? intval($_GET['nr']) : 0;
$linkList = $_SESSION['obsLinkList'];

function makeTaxon2($search)
{
    global $cf;

    $results[] = "";
    if ($search && strlen($search) > 1) {
        $pieces = explode(chr(194) . chr(183), $search);
        $pieces = explode(" ", $pieces[0]);

        $sql = "SELECT taxonID
                FROM tbl_tax_species ts
                 LEFT JOIN tbl_tax_epithets te ON te.epithetID = ts.speciesID
                 LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
                WHERE ts.external = 0
                 AND tg.genus LIKE '" . dbi_escape_string($pieces[0]) . "%'\n";
        if (!empty($pieces[1])) {
            $sql .= "AND te.epithet LIKE '" . dbi_escape_string($pieces[1]) . "%'\n";
        }
        $sql .= "ORDER BY tg.genus, te.epithet";
        if ($result = dbi_query($sql)) {
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_array($result)) {
                    $results[] = getScientificName($row['taxonID']);
                }
            }
        }

        $sql = "SELECT ts.taxonID, tg.genus,
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

        if ($result = dbi_query($sql)) {
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_array($result)) {
                    $results[] = taxonWithHybrids($row);
                }
            }
        }
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
                    WHERE Sammler LIKE '" . dbi_escape_string($pieces[0]) . "%'
                    ORDER BY Sammler";
        }
        if ($result = dbi_query($sql)) {
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_array($result)) {
                    if ($nr == 2)
                      $res = $row['Sammler_2'] . " <" . $row['Sammler_2ID'] . ">";
                    else
                      $res = $row['Sammler'] . " <" . $row['SammlerID'] . ">";
                    $results[] = $res;
                }
            }
        }
    }
    return $results;
}

// main program

$updateBlocked = false;
if (isset($_GET['sel'])) {
    if (extractID($_GET['sel']) != "NULL") {
        $sql = "SELECT s.specimen_ID, s.HerbNummer, s.identstatusID, s.checked, s.accessible,
                 s.taxonID, s.seriesID, s.Nummer, s.alt_number, s.Datum, s.Datum2,
                 s.det, s.taxon_alt, s.Bezirk,
                 s.Coord_W, s.W_Min, s.W_Sec, s.Coord_N, s.N_Min, s.N_Sec,
                 s.Coord_S, s.S_Min, s.S_Sec, s.Coord_E, s.E_Min, s.E_Sec,
                 s.quadrant, s.quadrant_sub, s.exactness, s.altitude_min, s.altitude_max,
                 s.Fundort, s.Fundort_engl, s.habitat, s.habitus, s.Bemerkungen, s.digital_image_obs,
                 s.ncbi_accession,
                 s.collectionID, s.NationID, s.provinceID,
                 c.SammlerID, c.Sammler, c2.Sammler_2ID, c2.Sammler_2
                FROM (tbl_specimens s, tbl_collector c)
                 LEFT JOIN tbl_collector_2 c2 ON c2.Sammler_2ID = s.Sammler_2ID
                WHERE s.SammlerID = c.SammlerID
                 AND specimen_ID = " . extractID($_GET['sel']);
        $result = dbi_query($sql);
        $resultValid = (mysqli_num_rows($result) > 0) ? true : false;
    } else {
        $resultValid = false;
    }

    if ($resultValid) {
        $row = mysqli_fetch_array($result);
        $p_specimen_ID       = $row['specimen_ID'];
        $p_HerbNummer        = $row['HerbNummer'];
        $p_identstatus       = $row['identstatusID'];
        $p_checked           = $row['checked'];
        $p_accessible        = $row['accessible'];
        $p_series            = $row['seriesID'];
        $p_Nummer            = $row['Nummer'];
        $p_alt_number        = $row['alt_number'];
        $p_Datum             = $row['Datum'];
        $p_Datum2            = $row['Datum2'];
        $p_det               = $row['det'];
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
        $p_digital_image_obs = $row['digital_image_obs'];
        $p_ncbi              = $row['ncbi_accession'];

        $p_collection  = $row['collectionID'];
        $p_nation      = $row['NationID'];
        $p_province    = $row['provinceID'];

        $p_sammler     = $row['Sammler'] . " <" . $row['SammlerID'] . ">";
        $p_sammler2    = ($row['Sammler_2']) ? $row['Sammler_2'] . " <" . $row['Sammler_2ID'] . ">" : "";

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
            $sql = "SELECT ts.taxonID, ts.external
                    FROM tbl_tax_species ts
                    WHERE ts.taxonID = '" . dbi_escape_string($row['taxonID']) . "'";
            $result2 = dbi_query($sql);
            $row2 = mysqli_fetch_array($result2);
            $p_taxon  = getScientificName($row2['taxonID']);
            $p_external = $row2['external'];
        } else {
            $p_taxon = "";
            $p_external = null;
        }
    } else {
        $p_specimen_ID = $p_HerbNummer = $p_identstatus = "";
        $p_checked = $p_accessible = "1";
        $p_series = $p_Nummer = $p_alt_number = $p_Datum = $p_Datum2 = $p_det = "";
        $p_taxon_alt = $p_taxon = $p_Bezirk = "";
        $p_external = null;
        $p_lat_deg = $p_lat_min = $p_lat_sec = ""; $p_lat = "N";
        $p_lon_deg = $p_lon_min = $p_lon_sec = ""; $p_lon = "E";
        $p_quadrant = $p_quadrant_sub = $p_exactness = $p_altitude_min = $p_altitude_max = "";
        $p_Fundort = $p_Fundort_engl = $p_habitat = $p_habitus = $p_Bemerkungen = "";
        $p_digital_image_obs = $p_ncbi = "";
        $p_nation = $p_province = "";
        $p_sammler = $p_sammler2 = "";
        if (!$_SESSION['editorControl']) {  // no editor, so the collectionID is fixed
            $sql = "SELECT collectionID FROM tbl_management_collections WHERE source_id = '" . $_SESSION['sid'] . "' ORDER BY collectionID ASC";
            $result = dbi_query($sql);
            $row = mysqli_fetch_array($result);
            $p_collection = $row['collectionID'];
        } else {
            $p_collection = "";
        }
    }
    if (isset($_GET['new']) && $_GET['new'] == 1) {
        $p_specimen_ID = "";
        if (!$_SESSION['editorControl']) {  // no editor, so the collectionID is fixed
            $sql = "SELECT collectionID FROM tbl_management_collections WHERE source_id = '" . $_SESSION['sid'] . "' ORDER BY collectionID ASC";
            $result = dbi_query($sql);
            $row = mysqli_fetch_array($result);
            $p_collection = $row['collectionID'];
        }
    }
    $edit = (!empty($_GET['edit'])) ? true : false;
} else {
    $p_collection        = $_POST['collection'];
    $p_HerbNummer        = $_POST['HerbNummer'];
    $p_identstatus       = $_POST['identstatus'];
    $p_checked           = $_POST['checked'];
    $p_accessible        = $_POST['accessible'];
    $p_series            = $_POST['series'];
    $p_Nummer            = $_POST['Nummer'];
    $p_alt_number        = $_POST['alt_number'];
    $p_Datum             = $_POST['Datum'];
    $p_Datum2            = $_POST['Datum2'];
    $p_det               = $_POST['det'];
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
    $p_digital_image_obs = $_POST['digital_image_obs'];
    $p_ncbi              = $_POST['ncbi'];
    $p_taxon             = $_POST['taxon'];
    $p_external          = $_POST['external'];
    $p_nation            = $_POST['nation'];
    $p_province          = $_POST['province'];
    $p_sammler           = $_POST['sammler'];
    $p_sammler2          = $_POST['sammler2'];
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
      $sqldata = "collectionID = '" . intval($p_collection) . "',
                  identstatusID = " . quoteString($p_identstatus) . ",
                  checked = " . (($p_checked) ? "'1'" : "'0'") . ",
                  `accessible` = " . (($p_accessible) ? "'1'" : "'0'") . ",
                  taxonID = " . extractID($p_taxon) . ",
                  SammlerID = " . extractID($p_sammler) . ",
                  Sammler_2ID = " . extractID($p_sammler2) . ",
                  seriesID = " . quoteString($p_series) . ",
                  Nummer = " . quoteString($p_Nummer) . ",
                  alt_number = " . quoteString($p_alt_number) . ",
                  Datum = " . quoteString($p_Datum) . ",
                  Datum2 = " . quoteString($p_Datum2) . ",
                  det = " . quoteString($p_det) . ",
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
                  S_Sec = " . quoteString($d_S_Sec) . ",
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
                  digital_image_obs = " . (($p_digital_image_obs) ? "'1'" : "'0'");
                  // HerbNummer = " . quoteString($p_HerbNummer) . ",
      if (intval($_POST['specimen_ID'])) {
          // check if user has access to the old collection
          $sql = "SELECT source_id
                  FROM tbl_specimens, tbl_management_collections
                  WHERE tbl_specimens.collectionID = tbl_management_collections.collectionID
                   AND specimen_ID = '" . intval($_POST['specimen_ID']) . "'";
          $dummy = dbi_query($sql)->fetch_array();
          $checkSource = ($dummy['source_id'] == $_SESSION['sid']) ? true : false;

          $sql = "UPDATE tbl_specimens SET
                  $sqldata
                  WHERE specimen_ID = '" . intval($_POST['specimen_ID']) . "'";
          $updated = 1;
      } else {
          // no check cause there is no old collection
          $checkSource = true;
          $sql = "INSERT INTO tbl_specimens SET
                  $sqldata,
                  observation = '1',
                  eingabedatum = NULL";
          $updated = 0;
      }
      // check if user has access to the new collection
      $sqlCheck = "SELECT source_id FROM tbl_management_collections WHERE collectionID='".intval($p_collection)."'";
      $rowCheck = dbi_query($sqlCheck)->fetch_array();
      // allow write access to database if user is editor or is granted for both old and new collection
      if ($_SESSION['editorControl'] || ($_SESSION['sid'] == $rowCheck['source_id'] && $checkSource)) {
          $sqlDummy = "SELECT specimen_ID
                       FROM tbl_specimens, tbl_management_collections
                       WHERE tbl_specimens.collectionID = tbl_management_collections.collectionID
                        AND HerbNummer = " . quoteString($p_HerbNummer) . "
                        AND (source_id = '1' OR source_id = '6')
                        AND source_id = '" . $rowCheck['source_id'] . "'
                        AND specimen_ID != '" . intval($_POST['specimen_ID']) . "'";
          $dummy = dbi_query($sqlDummy);
          if (mysqli_num_rows($dummy)>0) {
              $updateBlocked = true;
              $blockCause = 1;  // HerbNummer and source_id already in database
              $dummyRow = mysqli_fetch_array($dummy);
              $blockSource = $dummyRow['specimen_ID'];
              $edit = ($_POST['edit']) ? true : false;
              $p_specimen_ID = $_POST['specimen_ID'];
          }
          else {
              $result = dbi_query($sql);
              $p_specimen_ID = (intval($_POST['specimen_ID'])) ? intval($_POST['specimen_ID']) : dbi_insert_id();
              logSpecimen($p_specimen_ID, $updated);

              if ($_POST['submitUpdateNew']) {
                  $location="Location: editObservations.php?sel=<0>&new=1";
                  if (SID) $location .= "&" . SID;
                  header($location);
              } elseif ($_POST['submitUpdateCopy']) {
                  $location="Location: editObservations.php?sel=<" . $p_specimen_ID . ">&new=1";
                  if (SID) $location = "&" . SID;
                  header($location);
              }
              $edit = false;
          }
      } else {
          $updateBlocked = true;
          $blockCause = 2;  // no write access to the new collection
          $edit = ($_POST['edit']) ? true : false;
          $p_specimen_ID = $_POST['specimen_ID'];
      }
    } else if (!empty($_POST['submitNewCopy'])) {
        $p_specimen_ID = "";
        $edit = false;
    } else {
        $edit = (!empty($_POST['reload']) && !empty($_POST['edit'])) ? true : false;
        $p_specimen_ID = $_POST['specimen_ID'];
    }
}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - edit Observations</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <link rel="stylesheet" type="text/css" href="js/lib/jQuery/css/ui-lightness/jquery-ui.custom.css">
  <?php echo $jaxon->getScript(true, true); ?>
  <script src="js/lib/jQuery/jquery.min.js" type="text/javascript"></script>
  <script src="js/lib/jQuery/jquery-ui.custom.min.js" type="text/javascript"></script>
  <script src="js/freudLib.js" type="text/javascript"></script>
  <script src="js/parameters.php" type="text/javascript"></script>
  <script type="text/javascript" language="JavaScript">
    var reload = false;

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
      MeinFenster = window.open(target,"editCollector","width=350,height=130,top=50,left=50,scrollbars=yes,resizable=yes");
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
    function editSeries() {
      target = "editSeries.php?sel=" + document.f.series.options[document.f.series.selectedIndex].value;
      MeinFenster = window.open(target,"editSeries","width=500,height=150,top=50,left=50,scrollbars=yes,resizable=yes");
      MeinFenster.focus();
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

    function fillLocation(lon_deg, lon_min, lon_sec, lon_dir, lat_deg, lat_min, lat_sec, lat_dir, nationID) {
      if (document.f.lon_deg.value || document.f.lon_min.value || document.f.lon_sec.value || document.f.lat_deg.value || document.f.lat_min.value || document.f.lat_sec.value)    {
        alert('Coordinates have already been entered');
      }
      else {
        document.f.lon_deg.value = lon_deg;
        document.f.lon_min.value = lon_min;
        document.f.lon_sec.value = lon_sec;
        if (lon_dir == 'E')
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
      self.document.f.reload.click()
    }

    function editNCBI(sel) {Fundort
      target = "editNCBI.php?id=" + sel;
      MeinFenster = window.open(target,"editNCBI","width=350,height=130,top=50,left=50,scrollbars=yes,resizable=yes");
      MeinFenster.focus();
    }

    function goBack(sel,check,edit) {
      if (!check && checkMandatory(0))
        move = confirm("Are you sure you want to leave?\nDataset will not be inserted!");
      else if (check && edit)
        move = confirm("Are you sure you want to leave?\nDataset will not be updated!");
      else
        move = true;
      if (move) self.location.href = 'listObservations.php?nr=' + sel;
    }

    function call_toggleLanguage() {
      jaxon_toggleLanguage(jaxon.getFormValues('f'));
      return false;
    }

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
<div id="iBox_content" style="display:none;"></div>

<form onSubmit="return checkMandatory(1)" Action="<?php echo $_SERVER['PHP_SELF']; ?>" Method="POST" name="f" id="f">

<?php
unset($collection);
$collection[0][] = 0; $collection[1][] = "";
$sql = "SELECT collection, collectionID FROM tbl_management_collections ORDER BY collection";
if ($result = dbi_query($sql)) {
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_array($result)) {
            $collection[0][] = $row['collectionID'];
            $collection[1][] = $row['collection'];
        }
    }
}

unset($identstatus);
$identstatus[0][] = 0; $identstatus[1][] = "";
$sql = "SELECT identstatusID, identification_status FROM tbl_specimens_identstatus ORDER BY identification_status";
if ($result = dbi_query($sql)) {
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_array($result)) {
            $identstatus[0][] = $row['identstatusID'];
            $identstatus[1][] = $row['identification_status'];
        }
    }
}

unset($series);
$series[0][] = 0; $series[1][] = "";
$sql = "SELECT seriesID, series FROM tbl_specimens_series ORDER BY series";
if ($result = dbi_query($sql)) {
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_array($result)) {
            $series[0][] = $row['seriesID'];
            $series[1][] = (strlen($row['series']) > 50) ? substr($row['series'], 0, 50) . "..." : $row['series'];
        }
    }
}

unset($nation);
$nation[0][] = 0; $nation[1][] = "";
$sql = "SELECT nation_engl, nationID FROM tbl_geo_nation ORDER BY nation_engl";
if ($result = dbi_query($sql)) {
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_array($result)) {
            $nation[0][] = $row['nationID'];
            $nation[1][] = $row['nation_engl'];
        }
    }
}

unset($province);
$province[0][] = 0; $province[1][] = "";
$sql = "SELECT provinz, provinceID FROM tbl_geo_province
        WHERE nationID = '" . intval($p_nation) . "' ORDER BY provinz";
if ($result = dbi_query($sql)) {
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_array($result)) {
            $province[0][] = $row['provinceID'];
            $province[1][] = $row['provinz'];
        }
    }
}

if ($nr) {
    echo "<div style=\"position: absolute; left: 15em; top: 0.4em;\">";
    if ($nr > 1) {
        echo "<a href=\"editObservations.php?sel=" . htmlentities("<" . $linkList[$nr - 1] . ">") . "&nr=" . ($nr - 1)."\">"
           . "<img border=\"0\" height=\"22\" src=\"webimages/left.gif\" width=\"20\">"
           . "</a>";
    } else {
        echo "<img border=\"0\" height=\"22\" src=\"webimages/left_gray.gif\" width=\"20\">";
    }
    echo "</div>\n";
    echo "<div style=\"position: absolute; left: 17em; top: 0.4em;\">";
    if ($nr < $linkList[0]) {
        echo "<a href=\"editObservations.php?sel=" . htmlentities("<" . $linkList[$nr + 1] . ">") . "&nr=" . ($nr + 1) . "\">"
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
        $text = "<span style=\"background-color: red\">&nbsp;<b>$p_specimen_ID</b>&nbsp;</span>";
    } else {
        $text = $p_specimen_ID;
    }
} else {
    $text = "<span style=\"background-color: red\">&nbsp;<b>new</b>&nbsp;</span>";
}
$cf->label(9, 0.5, "specimen_ID");
$cf->text(9, 0.5, "&nbsp;" . $text);

if ($p_digital_image_obs && $p_specimen_ID) {
    $target = getIiifLink($p_specimen_ID);
    if ($target) {
        $cf->label(33.5, 0, "digital image", "javascript:showIiif('$target')");
    } else {
        $cf->label(33.5, 0, "digital image", "javascript:showImage('$p_specimen_ID')");
    }
} else {
    $cf->label(33.5, 0, "digital image");
}
$cf->checkbox(33.5, 0, "digital_image_obs", $p_digital_image_obs);
$cf->labelMandatory(42, 0, 5, "checked");
$cf->checkbox(42, 0, "checked", $p_checked);
$cf->labelMandatory(54.5, 0, 6, "accessible");
$cf->checkbox(54.5, 0, "accessible", $p_accessible);

$cf->labelMandatory(9, 2, 8, "Collection");
if ($_SESSION['editorControl']) {  // Editoren dürfen auf alle collections zugreifen
    $cf->dropdown(9, 2, "collection", $p_collection, $collection[0], $collection[1]);
} else {
    $collectionText = "";
    for ($i = 0; $i < count($collection[0]); $i++) {
        if ($collection[0][$i] == $p_collection) {
            $collectionText = $collection[1][$i];
            break;
        }
    }
    echo "<input type=\"hidden\" name=\"collection\" value=\"$p_collection\">\n";
    $cf->text(9, 2, "&nbsp;" . $collectionText);
}
//$cf->label(26,2,"Nr.");
//$cf->inputText(26,2,10,"HerbNummer",$p_HerbNummer,25);

$cf->label(9, 4, "Status");
$cf->dropdown(9, 4, "identstatus", $p_identstatus, $identstatus[0], $identstatus[1]);

//echo "<img border=\"1\" height=\"16\" src=\"webimages/ncbi.gif\" width=\"14\" ".
//     "style=\"position:absolute; left:36em; top:4.2em\"";
//if ($p_ncbi) echo " title=\"$p_ncbi\"";
//echo " onclick=\"editNCBI($p_specimen_ID)\">\n";

if (($_SESSION['editControl'] & 0x1) != 0 || ($_SESSION['linkControl'] & 0x1) != 0) {
    $cf->labelMandatory(9, 6, 8, "taxon", "javascript:editSpecies(document.f.taxon)");
} else {
    $cf->labelMandatory(9, 6, 8, "taxon");
}
$cf->editDropdown(9, 6, 46, "taxon", $p_taxon, makeTaxon2($p_taxon), 520, 0, ($p_external) ? 'red' : '');
echo "<input type=\"hidden\" name=\"external\" value=\"$p_external\">\n";
$cf->labelMandatory(9, 10, 8, "det / rev / conf");
$cf->inputText(9, 10, 46, "det", $p_det, 255);
$cf->labelMandatory(9, 12, 8, "ident. history");
$cf->inputText(9, 12, 46, "taxon_alt", $p_taxon_alt, 255);

$cf->label(9, 14, "Series", "javascript:editSeries()");
$cf->dropdown(9, 14, "series", $p_series, $series[0], $series[1]);

$cf->labelMandatory(9, 16, 8, "first observer", "javascript:editCollector(document.f.sammler)");
$cf->editDropdown(9, 16, 46, "sammler", $p_sammler, makeSammler2($p_sammler, 1), 270);
$cf->label(9, 17.7, "search","javascript:searchCollector()");

$cf->labelMandatory(9, 20, 8, "Number");
$cf->inputText(9, 20, 4, "Nummer", $p_Nummer, 10);
$cf->label(18, 20, "alt.Nr.");
$cf->inputText(18, 20, 17, "alt_number", $p_alt_number, 50);
$cf->labelMandatory(42, 20, 3, "Date");
$cf->inputText(42, 20, 5.5, "Datum", $p_Datum, 25);
$cf->text(48.5, 19.7, "<font size=\"+1\">&ndash;</font>");
$cf->inputText(49.5, 20, 5.5, "Datum2", $p_Datum2, 25);

$cf->label(9, 22, "add. Observer(s)", "javascript:editCollector2(document.f.sammler2)");
$cf->editDropdown(9, 22, 46, "sammler2", $p_sammler2, makeSammler2($p_sammler2, 2), 270);
$cf->label(9, 23.7, "search","javascript:searchCollector2()");

echo "<div style=\"position: absolute; left: 1em; top: 25.75em; width: 54.5em;\"><hr></div>\n";

$cf->labelMandatory(9, 27, 8, "Country");
if (($_SESSION['editControl'] & 0x2000) != 0) {
    $cf->dropdown(9, 27, "nation\" onchange=\"reload=true; self.document.f.reload.click()", $p_nation, $nation[0], $nation[1]);
} else {
    $cf->dropdown(9, 27, "nation", $p_nation, $nation[0], $nation[1]);
}
$cf->label(40, 27, "Province");
$cf->dropdown(40, 27, "province", $p_province, $province[0], $province[1]);
$cf->label(9, 29, "geonames","#\" onclick=\"jaxon_searchGeonames(document.f.Bezirk.value);");
$cf->inputText(9, 29, 20, "Bezirk", $p_Bezirk, 255);

$cf->label(9, 31, "Altitude");
$cf->inputText(9, 31, 5, "altitude_min", $p_altitude_min, 10);
$cf->text(15, 30.7, "<font size=\"+1\">&ndash;</font>");
$cf->inputText(16, 31, 5, "altitude_max", $p_altitude_max, 10);

$cf->label(40, 31, "Quadrant");
$cf->inputText(40, 31, 3, "quadrant", $p_quadrant, 10);
$cf->inputText(44, 31, 1, "quadrant_sub", $p_quadrant_sub, 10);

echo "<img border=\"0\" height=\"16\" src=\"webimages/convert.gif\" width=\"16\" "
   . "style=\"position:absolute; left:46.5em; top:31.1em\" onclick=\"convert()\">\n";

$cf->label(9, 33, "Lat");
$cf->inputText(9, 33, 2, "lat_deg", $p_lat_deg, 5);
$cf->text(12, 32.7, "<font size=\"+1\">&deg;</font>");
$cf->inputText(13, 33, 1.5, "lat_min", $p_lat_min, 5);
$cf->text(15.5, 32.7, "<font size=\"+1\">&prime;</font>");
$cf->inputText(16.5, 33, 1.5, "lat_sec", $p_lat_sec, 5);
$cf->text(19, 32.7, "<font size=\"+1\">&Prime;</font>");
$cf->dropdown(20, 33, "lat", $p_lat, array("N", "S"), array("N", "S"));

$cf->label(27, 33, "Lon");
$cf->inputText(27, 33, 2, "lon_deg", $p_lon_deg, 5);
$cf->text(30, 32.7, "<font size=\"+1\">&deg;</font>");
$cf->inputText(31, 33, 1.5, "lon_min", $p_lon_min, 5);
$cf->text(33.5, 32.7, "<font size=\"+1\">&prime;</font>");
$cf->inputText(34.5, 33, 1.5, "lon_sec", $p_lon_sec, 5);
$cf->text(37, 32.7, "<font size=\"+1\">&Prime;</font>");
$cf->dropdown(38, 33, "lon", $p_lon, array("W", "E"), array("W", "E"));

$cf->label(48, 33, "exactn.");
$cf->inputText(48, 33, 7, "exactness", $p_exactness, 30);
//$cf->dropdown(48,33,"exactness",$p_exactness,$exactness[0],$exactness[1]);

echo "<div style=\"position: absolute; left: 1em; top: 34.75em; width: 54.5em;\"><hr></div>\n";

$cf->labelMandatory(9, 36, 8, "Locality", "#\" onclick=\"call_toggleLanguage();\" id=\"labelLocality");
$cf->textarea(9, 36, 46, 3.8, "Fundort1\" id=\"Fundort1", $p_Fundort);
echo "<input type=\"hidden\" name=\"Fundort2\" id=\"Fundort2\" value=\"$p_Fundort_engl\">\n";
echo "<input type=\"hidden\" name=\"toggleLanguage\" id=\"toggleLanguage\" value=\"0\">\n";

$cf->label(9, 40.5, "habitat");
$cf->label(9, 41.5, "phorophyte");
$cf->textarea(9, 40.5, 20, 2.6, "habitat", $p_habitat);
$cf->label(35, 40.5, "habitus");
$cf->textarea(35, 40.5, 20, 2.6, "habitus", $p_habitus);

$cf->label(9, 44, "annotations");
$cf->textarea(9, 44, 46, 2.6, "Bemerkungen", $p_Bemerkungen);

if (($_SESSION['editControl'] & 0x2000) != 0) {
    $cf->buttonSubmit(16, 50, "reload", " Reload \" onclick=\"reloadButtonPressed()");
    if ($p_specimen_ID) {
        if ($edit) {
            $cf->buttonJavaScript(22, 50, " Reset ", "self.location.href='editObservations.php?sel=<" . $p_specimen_ID . ">&edit=1'");
            $cf->buttonSubmit(31, 50, "submitUpdate", " Update ");
        } else {
            $cf->buttonJavaScript(22, 50, " Reset ", "self.location.href='editObservations.php?sel=<" . $p_specimen_ID . ">'");
            $cf->buttonJavaScript(31, 50, " Edit ", "self.location.href='editObservations.php?sel=<" . $p_specimen_ID . ">&edit=1'");
        }
        $cf->buttonSubmit(37, 50, "submitMakeSpecimen", " make Specimen ");
        $cf->buttonSubmit(48, 50, "submitNewCopy", " New &amp; Copy ");
    }
    else {
        $cf->buttonReset(22, 50, " Reset ");
        $cf->buttonSubmit(31, 50, "submitUpdate", " Insert ");
        $cf->buttonSubmit(37, 50, "submitUpdateCopy", " Insert &amp; Copy ");
        $cf->buttonSubmit(48, 50, "submitUpdateNew", " Insert &amp; New ");
    }
}
$cf->buttonJavaScript(2, 50, " < Observations ", "goBack($nr," . intval($p_specimen_ID) . "," . intval($edit) . ")");
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
