<?php
session_start();
require("inc/connect.php");
require("inc/herbardb_input_functions.php");
require_once ("inc/xajax/xajax_core/xajax.inc.php");
no_magic();

$xajax = new xajax();
$xajax->setRequestURI("ajax/listLabelServer.php");

$xajax->registerFunction("makeDropdownInstitution");
$xajax->registerFunction("makeDropdownCollection");
$xajax->registerFunction("toggleTypeLabelMap");
$xajax->registerFunction("toggleTypeLabelSpec");
$xajax->registerFunction("toggleBarcodeLabel");
$xajax->registerFunction("clearTypeLabelsMap");
$xajax->registerFunction("clearTypeLabelsSpec");
$xajax->registerFunction("clearBarcodeLabels");
$xajax->registerFunction("checkTypeLabelMapPdfButton");
$xajax->registerFunction("checkTypeLabelSpecPdfButton");
$xajax->registerFunction("checkBarcodeLabelPdfButton");
$xajax->registerFunction("updtStandardLabel");
$xajax->registerFunction("clearStandardLabels");
$xajax->registerFunction("checkStandardLabelPdfButton");
$xajax->registerFunction("setAll");
$xajax->registerFunction("clearAll");

if (!empty($_POST['select']) && !empty($_POST['specimen'])) {
    $location = "Location: editLabel.php?sel=<" . $_POST['specimen'] . ">";
    if (SID) $location .= "&" . SID;
    Header($location);
    die();
}

if (!isset($_SESSION['labelSQL']))        $_SESSION['labelSQL']        = '';
if (!isset($_SESSION['labelType']))       $_SESSION['labelType']       = 0;
if (!isset($_SESSION['labelCollection'])) $_SESSION['labelCollection'] = '';
if (!isset($_SESSION['labelNumber']))     $_SESSION['labelNumber']     = '';
if (!isset($_SESSION['labelSeries']))     $_SESSION['labelSeries']     = '';
if (!isset($_SESSION['labelFamily']))     $_SESSION['labelFamily']     = '';
if (!isset($_SESSION['labelTaxon']))      $_SESSION['labelTaxon']      = '';
if (!isset($_SESSION['labelTaxonAlt']))   $_SESSION['labelTaxonAlt']   = '';
if (!isset($_SESSION['labelCollector']))  $_SESSION['labelCollector']  = '';
if (!isset($_SESSION['labelNumberC']))    $_SESSION['labelNumberC']    = '';
if (!isset($_SESSION['labelDate']))       $_SESSION['labelDate']       = '';
if (!isset($_SESSION['labelGeoGeneral'])) $_SESSION['labelGeoGeneral'] = '';
if (!isset($_SESSION['labelGeoRegion']))  $_SESSION['labelGeoRegion']  = '';
if (!isset($_SESSION['labelCountry']))    $_SESSION['labelCountry']    = '';
if (!isset($_SESSION['labelCountry']))    $_SESSION['labelCountry']    = '';
if (!isset($_SESSION['labelProvince']))   $_SESSION['labelProvince']   = '';
if (!isset($_SESSION['labelLoc']))        $_SESSION['labelLoc']        = '';
if (!isset($_SESSION['labelTyp']))        $_SESSION['labelTyp']        = false;
if (!isset($_SESSION['labelImages']))     $_SESSION['labelImages']     = false;

$nrSel = (isset($_GET['nr'])) ? intval($_GET['nr']) : 0;

if (!empty($_POST['search'])) {
    $_SESSION['labelType'] = 1;

    $_SESSION['labelCollection'] = $_POST['collection'];
    $_SESSION['labelNumber']     = $_POST['number'];
    $_SESSION['labelSeries']     = $_POST['series'];
    $_SESSION['labelFamily']     = $_POST['family'];
    $_SESSION['labelTaxon']      = $_POST['taxon'];
    $_SESSION['labelTaxonAlt']   = $_POST['taxon_alt'];
    $_SESSION['labelCollector']  = $_POST['collector'];
    $_SESSION['labelNumberC']    = $_POST['numberC'];
    $_SESSION['labelDate']       = $_POST['date'];
    $_SESSION['labelGeoGeneral'] = $_POST['geo_general'];
    $_SESSION['labelGeoRegion']  = $_POST['geo_region'];
    $_SESSION['labelCountry']    = $_POST['country'];
    $_SESSION['labelProvince']   = $_POST['province'];
    $_SESSION['labelLoc']        = $_POST['loc'];

    $_SESSION['labelTyp']    = (($_POST['typ'] == "only") ? true : false);
    $_SESSION['labelImages'] = (($_POST['images'] == "only") ? true : false);

    $_SESSION['labelOrder'] = "genus, te.epithet, ta.author, "
                            . "Sammler, Sammler_2, series, Nummer, alt_number, Datum, "
                            . "typus_lat";
    $_SESSION['labelOrTyp'] = 1;
} else if (isset($_GET['order'])) {
    if ($_GET['order'] == "b") {
        $_SESSION['labelOrder'] = "Sammler, Sammler_2, series, Nummer, alt_number, Datum, "
                                . "genus, te.epithet, ta.author, "
                                . "typus_lat";
        if ($_SESSION['labelOrTyp'] == 2) {
            $_SESSION['labelOrTyp'] = -2;
        } else {
            $_SESSION['labelOrTyp'] = 2;
        }
    }
    else if ($_GET['order'] == "d") {
        $_SESSION['labelOrder'] = "typus_lat, genus, te.epithet, ta.author, "
                                . "Sammler, Sammler_2, series, Nummer, alt_number, Datum";
        if ($_SESSION['labelOrTyp'] == 4) {
            $_SESSION['labelOrTyp'] = -4;
        } else {
            $_SESSION['labelOrTyp'] = 4;
        }
    }
    else if ($_GET['order'] == "e") {
        $_SESSION['labelOrder'] = "collection, HerbNummer";
        if ($_SESSION['labelOrTyp'] == 5) {
            $_SESSION['labelOrTyp'] = -5;
        } else {
            $_SESSION['labelOrTyp'] = 5;
        }
    }
    else {
        $_SESSION['labelOrder'] = "genus, te.epithet, ta.author, ".
                               "Sammler, Sammler_2, series, Nummer, alt_number, Datum, ".
                               "typus_lat";
        if ($_SESSION['labelOrTyp'] == 1) {
            $_SESSION['labelOrTyp'] = -1;
        } else {
            $_SESSION['labelOrTyp'] = 1;
        }
    }
    if ($_SESSION['labelOrTyp'] < 0) $_SESSION['labelOrder'] = implode(" DESC, ", explode(", ", $_SESSION['labelOrder'])) . " DESC";
}

function makeDropdownInstitution()
{
    echo "<select size=\"1\" name=\"collection\">\n";
    echo "  <option value=\"0\"></option>\n";

    $sql = "SELECT source_id, source_code FROM herbarinput.meta ORDER BY source_code";
    $result = db_query($sql);
    while($row = mysql_fetch_array($result)) {
        echo "  <option value=\"-" . htmlspecialchars($row['source_id']) . "\"";
        if (-$_SESSION['labelCollection'] == $row['source_id']) echo " selected";
        echo ">" . htmlspecialchars($row['source_code']) . "</option>\n";
    }

    echo "  </select>\n";
}

function makeDropdownCollection()
{
    $sql =  "SELECT collectionID, collection FROM tbl_management_collections ORDER BY collection";
    $result = db_query($sql);
    echo "<select size=\"1\" name=\"collection\">\n";
    echo "  <option value=\"0\"></option>\n";
    while ($row = mysql_fetch_array($result)) {
        echo "  <option value=\"" . htmlspecialchars($row['collectionID']) . "\"";
        if ($_SESSION['labelCollection'] == $row['collectionID']) echo " selected";
        echo ">" . htmlspecialchars($row['collection']) . "</option>\n";
    }
    echo "  </select>\n";
}

function collectorItem($row)
{
    $text = $row['Sammler'];
    if (strstr($row['Sammler_2'], "&") || strstr($row['Sammler_2'], "et al.")) {
        $text .= " et al.";
    } elseif ($row['Sammler_2']) {
        $text .= " & " . $row['Sammler_2'];
    }
    if ($row['series_number']) {
        if ($row['Nummer']) $text .= " " . $row['Nummer'];
        if ($row['alt_number'] && trim($row['alt_number']) != "s.n.") $text .= " " . $row['alt_number'];
        if ($row['series']) $text .= " " . $row['series'];
        $text .= " " . $row['series_number'];
    } else {
        if ($row['series']) $text .= " " . $row['series'];
        if ($row['Nummer']) $text .= " " . $row['Nummer'];
        if ($row['alt_number']) $text .= " " . $row['alt_number'];
        if (strstr($row['alt_number'],"s.n.")) $text .= " [" . $row['Datum'] . "]";
    }

    return $text;
}

function locationItem($row)
{
    $text = "";
    if (trim($row['nation_engl'])) {
        $text = trim($row['nation_engl']);
    }
    if (trim($row['provinz'])) {
        if (strlen($text) > 0) $text .= ". ";
        $text .= trim($row['provinz']);
    }
    if (trim($row['Fundort']) && $row['collectionID'] != 12) {
        if (strlen($text) > 0) $text .= ". ";
        $text .= trim($row['Fundort']);
    }

    return $text;
}

function collectionItem($coll)
{
    if (strpos($coll, "-") !== false) {
        return substr($coll, 0, strpos($coll, "-"));
    } elseif (strpos($coll, " ") !== false) {
        return substr($coll, 0, strpos($coll, " "));
    } else {
        return($coll);
    }
}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - list Labels</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <?php $xajax->printJavascript('inc/xajax'); ?>
  <script src="js/freudLib.js" type="text/javascript"></script>
  <script src="js/parameters.php" type="text/javascript"></script>
  <script type="text/javascript" language="JavaScript">
    var swInstitutionCollection = <?php echo ($_SESSION['labelCollection'] > 0) ? 1 : 0; ?>;

    function toggleInstitutionCollection() {
        if (swInstitutionCollection) {
            swInstitutionCollection = 0;
            xajax_makeDropdownInstitution();
        } else {
            swInstitutionCollection = 1;
            xajax_makeDropdownCollection();
        }
    }

    function toggleLabelWrapper(sel, id) {
      switch (sel) {
        case 1: xajax_toggleTypeLabelMap(id);
                break;
        case 2: xajax_toggleTypeLabelSpec(id);
                break;
        case 3: xajax_toggleBarcodeLabel(id);
                break;
      }
    }
    function updtLabelWrapper(id, data) {
      xajax_updtStandardLabel(id, data);
    }

    function check_all() {
      for (var i=0, n=document.f.elements.length; i<n; i++) {
        if (document.f.elements[i].name.substring(0,11)=='batch_spec_') {
          document.f.elements[i].checked = true;
        }
      }
    }
    function showPDF(sel) {
      switch (sel) {
        case 'typeMap':  target = "pdfLabelTypesMap.php";  label = "labelTypesMap"; break;
        case 'typeSpec': target = "pdfLabelTypesSpec.php"; label = "labelTypesSpec"; break;
        case 'std':      target = "pdfLabelStandard.php";  label = "labelStandard"; break;
        case 'barcode':  target = "pdfLabelBarcode.php";   label = "labelBarcode"; break;
        case 'QRCode':   target = "pdfLabelQRCode.php";    label = "labelQRCode"; break;
      }
      MeinFenster = window.open(target, label);
      MeinFenster.focus();
    }

    xajax_checkTypeLabelMapPdfButton();
    xajax_checkTypeLabelSpecPdfButton();
    xajax_checkStandardLabelPdfButton();
    xajax_checkBarcodeLabelPdfButton();
  </script>
</head>

<body>

<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" name="f1">
<table cellspacing="5" cellpadding="0">
<tr>
  <td align="right">&nbsp;<b><a href="#" id="lblInstitutionCollection" onclick="toggleInstitutionCollection();"><?php echo ($_SESSION['labelCollection'] > 0) ? 'Collection:' : 'Institution:'; ?></a></b>
    </td>
    <td id="drpInstitutionCollection"><?php ($_SESSION['labelCollection'] > 0) ? makeDropdownCollection() : makeDropdownInstitution(); ?></td>
  <td align="right">&nbsp;<b>Collection Nr.:</b></td>
    <td><input type="text" name="number" value="<?php echo $_SESSION['labelNumber']; ?>"></td>
  <td align="right">&nbsp;<b>Series:</b></td>
    <td><input type="text" name="series" value="<?php echo $_SESSION['labelSeries']; ?>"></td>
</tr><tr>
  <td align="right">&nbsp;<b>Family:</b></td>
    <td><input type="text" name="family" value="<?php echo $_SESSION['labelFamily']; ?>"></td>
  <td align="right">&nbsp;<b>Taxon:</b></td>
    <td><input type="text" name="taxon" value="<?php echo $_SESSION['labelTaxon']; ?>"></td>
  <td align="right">&nbsp;<b>ident. history</b></td>
    <td><input type="text" name="taxon_alt" value="<?php echo $_SESSION['labelTaxonAlt']; ?>"></td>
</tr><tr>
  <td align="right">&nbsp;<b>Collector:</b></td>
    <td><input type="text" name="collector" value="<?php echo $_SESSION['labelCollector']; ?>"></td>
  <td align="right">&nbsp;<b>Number:</b></td>
    <td><input type="text" name="numberC" value="<?php echo $_SESSION['labelNumberC']; ?>"></td>
  <td align="right">&nbsp;<b>Date:</b></td>
    <td><input type="text" name="date" value="<?php echo $_SESSION['labelDate']; ?>"></td>
</tr><tr>
  <td align="right">&nbsp;<b>Continent:</b></td>
    <td>
      <select size="1" name="geo_general">
      <option></option>
      <?php
        $sql = "SELECT geo_general
                FROM tbl_geo_region
                GROUP BY geo_general ORDER BY geo_general";
        $result = mysql_query($sql);
        while ($row = mysql_fetch_array($result)) {
            echo "<option";
            if ($_SESSION['labelGeoGeneral'] == $row['geo_general']) echo " selected";
            echo ">" . $row['geo_general'] . "</option>\n";
        }
      ?>
      </select>
    </td>
  <td align="right">&nbsp;<b>Region:</b></td>
    <td>
      <select size="1" name="geo_region">
      <option></option>
      <?php
        $sql = "SELECT geo_region
                FROM tbl_geo_region
                ORDER BY geo_region";
        $result = mysql_query($sql);
        while ($row = mysql_fetch_array($result)) {
            echo "<option";
            if ($_SESSION['labelGeoRegion'] == $row['geo_region']) echo " selected";
            echo ">" . $row['geo_region'] . "</option>\n";
        }
      ?>
      </select>
    </td>
  <td colspan="2"></td>
</tr><tr>
  <td align="right">&nbsp;<b>Country:</b></td>
    <td><input type="text" name="country" value="<?php echo $_SESSION['labelCountry']; ?>"></td>
  <td align="right">&nbsp;<b>State/Province:</b></td>
    <td><input type="text" name="province" value="<?php echo $_SESSION['labelProvince']; ?>"></td>
  <td align="right">&nbsp;<b>Loc.:</b></td>
    <td><input type="text" name="loc" value="<?php echo $_SESSION['labelLoc']; ?>"></td>
</tr><tr>
  <td colspan="2">
    <input type="radio" name="typ" value="all"<?php if(!$_SESSION['labelTyp']) echo " checked"; ?>>
    <b>All records</b>
    <input type="radio" name="typ" value="only"<?php if($_SESSION['labelTyp']) echo " checked"; ?>>
    <b>Type records only</b>
  </td><td colspan="4" align="right">
    <b>Display only records containing images:</b>
    <input type="radio" name="images" value="only"<?php if($_SESSION['labelImages']) echo " checked"; ?>>
    <b>Yes</b>
    <input type="radio" name="images" value="all"<?php if(!$_SESSION['labelImages']) echo " checked"; ?>>
    <b>No</b>
  </td>
</tr>
</table>
<input class="button" type="submit" name="search" value=" search ">
</form>

<p>
<form action="pdfLabelBarcode.php" target="_blank" method="POST" name="f2">
  <table cellspacing="2" cellpadding="0"><tr><td>
    <b>Institution:</b> <?php makeDropdownInstitution(); ?>&nbsp;
    <b>start number:</b> <input type="text" name="start" size="10">&nbsp;
    <b>end number:</b> <input type="text" name="stop" size="10">&nbsp;
    <input class="button" type="submit" name="select" value=" make standard barcode Labels ">
  </td></tr></table>
</form>
<form action="pdfLabelQRCode.php" target="_blank" method="POST" name="f2">
  <table cellspacing="2" cellpadding="0"><tr><td>
    <b>Institution:</b> <?php makeDropdownInstitution(); ?>&nbsp;
    <b>start number:</b> <input type="text" name="start" size="10">&nbsp;
    <b>end number:</b> <input type="text" name="stop" size="10">&nbsp;
    <input class="button" type="submit" name="select_qr" value=" make standard QR-Code Labels ">
  </td></tr></table>
</form>

<p>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" name="f3">
<table cellspacing="0" cellpadding="0"><tr>
<td>
  <b>SpecimenID:</b> <input type="text" name="specimen" value="">
  <input class="button" type="submit" name="select" value=" Edit ">
</td>
</tr></table>
</form>

<hr>
<b>Labels</b>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" name="f">
<p>
<?php
if ($_SESSION['labelType'] == 1) { ?>
  <table cellpadding="0" cellspacing="4">
    <tr>
      <td align="center"><input type="button" class="button" value=" set all " onclick="xajax_setAll()"></td>
      <td><input type="button" class="button" value="make PDF (Type map)" id="btMakeTypeLabelMapPdf" onClick="showPDF('typeMap')"></td>
      <td><input type="button" class="button" value="make PDF (Type spec)" id="btMakeTypeLabelSpecPdf" onClick="showPDF('typeSpec')"></td>
      <td><input type="button" class="button" value="make PDF (barcode)" id="btMakeBarcodeLabelPdf" onClick="showPDF('barcode')"></td>
      <td><input type="button" class="button" value="make PDF (QRCode)" id="btMakeQRCodeLabelPdf" onClick="showPDF('QRCode')"></td>
      <td><input type="button" class="button" value="make PDF (standard)" id="btMakeStandardLabelPdf" onClick="showPDF('std')"></td>
    </tr>
    <tr>
      <td><input type="button" class="button" value=" clear all " onclick="xajax_clearAll()"></td>
      <td align="center"><input type="button" class="button" value="clear all Type map" id="btClearTypeMapLabels" onClick="xajax_clearTypeLabelsMap(); return false;"></td>
      <td align="center"><input type="button" class="button" value="clear all Type spec" id="btClearTypeSpecLabels" onClick="xajax_clearTypeLabelsSpec(); return false;"></td>
      <td align="center" colspan="2"><input type="button" class="button" value="clear all Barcode" id="btClearBarcodeLabels" onClick="xajax_clearBarcodeLabels(); return false;"></td>
      <td align="center"><input type="button" class="button" value="clear all standard" id="btClearStandardLabels" onClick="xajax_clearStandardLabels(); return false;"></td>
    </tr>
  </table>
  <p>
<?php
    $sql = "SELECT s.specimen_ID, tg.genus, s.digital_image, s.typusID, l.label,
             c.Sammler, c2.Sammler_2, ss.series, s.series_number,
             s.Nummer, s.alt_number, s.Datum, s.HerbNummer,
             n.nation_engl, p.provinz, s.Fundort, mc.collectionID, mc.collection, mc.coll_short, t.typus_lat,
             s.Coord_W, s.W_Min, s.W_Sec, s.Coord_N, s.N_Min, s.N_Sec,
             s.Coord_S, s.S_Min, s.S_Sec, s.Coord_E, s.E_Min, s.E_Sec, s.ncbi_accession,
             ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
             ta4.author author4, ta5.author author5,
             te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
             te4.epithet epithet4, te5.epithet epithet5
            FROM (tbl_specimens s, tbl_tax_species ts, tbl_tax_genera tg, tbl_tax_families tf, tbl_management_collections mc)
             LEFT JOIN tbl_labels l ON (s.specimen_ID = l.specimen_ID AND l.userID = '" . intval($_SESSION['uid']) . "')
             LEFT JOIN tbl_specimens_series ss ON ss.seriesID = s.seriesID
             LEFT JOIN tbl_typi t ON t.typusID = s.typusID
             LEFT JOIN tbl_geo_province p ON p.provinceID = s.provinceID
             LEFT JOIN tbl_geo_nation n ON n.NationID = s.NationID
             LEFT JOIN tbl_geo_region r ON r.regionID = n.regionID_fk
             LEFT JOIN tbl_collector c ON c.SammlerID = s.SammlerID
             LEFT JOIN tbl_collector_2 c2 ON c2.Sammler_2ID = s.Sammler_2ID
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
            WHERE ts.taxonID = s.taxonID
             AND tg.genID = ts.genID
             AND tf.familyID = tg.familyID
             AND mc.collectionID = s.collectionID";
    if (trim($_SESSION['labelTaxon'])) {
        $pieces = explode(" ", trim($_SESSION['labelTaxon']));
        $part1 = array_shift($pieces);
        $part2 = array_shift($pieces);
        $sql .= " AND tg.genus LIKE '" . mysql_escape_string($part1) . "%'";
        if ($part2) {
            $sql .= " AND (te.epithet LIKE '". mysql_escape_string($part2) . "%' "
                  .  "OR te1.epithet LIKE '" . mysql_escape_string($part2) . "%' "
                  .  "OR te2.epithet LIKE '" . mysql_escape_string($part2) . "%' "
                  .  "OR te3.epithet LIKE '" . mysql_escape_string($part2) . "%')";
        }
    }
    if (trim($_SESSION['labelSeries'])) {
        $sql .= " AND ss.series LIKE '%" . mysql_escape_string(trim($_SESSION['labelSeries'])) . "%'";
    }
    if (trim($_SESSION['labelCollection'])) {
        if (trim($_SESSION['labelCollection']) > 0) {
            $sql .= " AND s.collectionID = " . quoteString(trim($_SESSION['labelCollection']));
        } else {
            $sql .= " AND mc.source_id = " . quoteString(abs(trim($_SESSION['labelCollection'])));
        }
    }
    if (trim($_SESSION['labelNumber'])) {
        $sql .= " AND s.HerbNummer LIKE '%" . mysql_escape_string(trim($_SESSION['labelNumber'])) . "%'";
    }
    if (trim($_SESSION['labelFamily'])) {
        $sql .= " AND tf.family LIKE '" . mysql_escape_string(trim($_SESSION['labelFamily'])) . "%'";
    }
    if (trim($_SESSION['labelCollector'])) {
        $sql .= " AND c.Sammler LIKE '" . mysql_escape_string(trim($_SESSION['labelCollector'])) . "%'";
    }
    if (trim($_SESSION['labelNumberC'])) {
        $sql .= " AND s.Nummer LIKE '" . mysql_escape_string(trim($_SESSION['labelNumberC'])) . "%'";
    }
    if (trim($_SESSION['labelDate'])) {
        $sql .= " AND s.Datum LIKE '" . mysql_escape_string(trim($_SESSION['labelDate'])) . "%'";
    }
    if (trim($_SESSION['labelGeoGeneral'])) {
        $sql .= " AND r.geo_general LIKE '" . mysql_escape_string(trim($_SESSION['labelGeoGeneral'])) . "%'";
    }
    if (trim($_SESSION['labelGeoRegion'])) {
        $sql .= " AND r.geo_region LIKE '" . mysql_escape_string(trim($_SESSION['labelGeoRegion'])) . "%'";
    }
    if (trim($_SESSION['labelCountry'])) {
        $sql .= " AND n.nation_engl LIKE '" . mysql_escape_string(trim($_SESSION['labelCountry'])) . "%'";
    }
    if (trim($_SESSION['labelProvince'])) {
        $sql .= " AND p.provinz LIKE '" . mysql_escape_string(trim($_SESSION['labelProvince'])) . "%'";
    }
    if (trim($_SESSION['labelLoc'])) {
        $sql .= " AND s.Fundort LIKE '%" . mysql_escape_string(trim($_SESSION['labelLoc'])) . "%'";
    }
    if (trim($_SESSION['labelTaxonAlt'])) {
        $sql .= " AND s.taxon_alt LIKE '%" . mysql_escape_string(trim($_SESSION['labelTaxonAlt'])) . "%'";
    }
    if ($_SESSION['labelTyp']) {
        $sql .= " AND s.typusID != 0";
    }
    if ($_SESSION['labelImages']) {
        $sql .= " AND s.digital_image != 0";
    }

    $sql .= " ORDER BY " . $_SESSION['labelOrder'];
    $_SESSION['labelSQL'] = $sql;

    $result = db_query($sql);
    if (mysql_num_rows($result) > 0) {
        echo "<table class='out' cellspacing='0'>\n"
           . "<tr class='out'>"
           . "<th class='out'></th>"
           . "<th class='out'>"
           . "<a href='" . $_SERVER['PHP_SELF'] . "?order=a'>Taxon</a>" . sortItem($_SESSION['labelOrTyp'], 1) . "</th>"
           . "<th class='out'>"
           . "<a href'" . $_SERVER['PHP_SELF'] . "?order=b'>Collector</a>" . sortItem($_SESSION['labelOrTyp'], 2) . "</th>"
           . "<th class='out'>Date</th>"
           . "<th class='out'>X/Y</th>"
           . "<th class='out'>Location</th>"
           . "<th class='out'>"
           . "<a href='" . $_SERVER['PHP_SELF'] . "?order=d'>Typus</a>" . sortItem($_SESSION['labelOrTyp'], 4) . "</th>"
           . "<th class='out'>"
           . "<a href='" . $_SERVER['PHP_SELF'] . "?order=e'>Coll.</a>" . sortItem($_SESSION['labelOrTyp'], 5) . "</th>"
           . "<th class='out'>Type map Label</th>"
           . "<th class='out'>Type spec Label</th>"
           . "<th class='out'>Barcode Label</th>"
           . "<th class='out'>Standard Label</th>"
           . "</tr>\n";
        $nr = 1;
        while ($row = mysql_fetch_array($result)) {
            $linkList[$nr] = $id = $row['specimen_ID'];

            if ($row['digital_image']) {
                $digitalImage = "<a href=\"javascript:showImage('" . $row['specimen_ID'] . "')\">"
                              .  "<img border=\"0\" height=\"15\" src=\"webimages/camera.png\" width=\"15\">"
                              . "</a>";
            } else {
                $digitalImage = "";
            }

            if ($row['Coord_S'] > 0 || $row['S_Min'] > 0 || $row['S_Sec'] > 0) {
                $lat = -($row['Coord_S'] + $row['S_Min'] / 60 + $row['S_Sec'] / 3600);
            } else if ($row['Coord_N'] > 0 || $row['N_Min'] > 0 || $row['N_Sec'] > 0) {
                $lat = $row['Coord_N'] + $row['N_Min'] / 60 + $row['N_Sec'] / 3600;
            } else {
                $lat = 0;
            }
            if ($row['Coord_W'] > 0 || $row['W_Min'] > 0 || $row['W_Sec'] > 0) {
                $lon = -($row['Coord_W'] + $row['W_Min'] / 60 + $row['W_Sec'] / 3600);
            } else if ($row['Coord_E'] > 0 || $row['E_Min'] > 0 || $row['E_Sec'] > 0) {
                $lon = $row['Coord_E'] + $row['E_Min'] / 60 + $row['E_Sec'] / 3600;
            } else {
                $lon = 0;
            }
            if ($lat != 0 && $lon != 0) {
                $textLatLon = "<td class=\"out\" style=\"text-align: center\" title=\"" . round($lat, 2) . "&deg; / " . round($lon, 2)."&deg;\">"
                            .  "<a href=\"http://www.mapquest.com/maps/map.adp?latlongtype=decimal&longitude=$lon&latitude=$lat&zoom=3\" "
                            .   "target=\"_blank\"><img border=\"0\" height=\"15\" src=\"webimages/mapquest.png\" width=\"15\">"
                            .  "</a>"
                            . "</td>";
            } else {
                $textLatLon = "<td class=\"out\"></td>";
            }

            echo "<tr class=\"" . (($nrSel == $nr) ? "outMark" : "out") . "\">"
               . "<td class=\"out\">$digitalImage</td>"
               . "<td class=\"out\">"
               .  "<a href=\"editLabel.php?sel=" . htmlentities("<" . $row['specimen_ID'] . ">") . "&nr=$nr\">"
               .  htmlspecialchars(taxonItem($row)) . "</a></td>"
               . "<td class=\"out\">" . htmlspecialchars(collectorItem($row)) . "</td>"
               . "<td class=\"outNobreak\">" . htmlspecialchars($row['Datum']) . "</td>"
               . $textLatLon
               . "<td class=\"out\">" . htmlspecialchars(locationItem($row)) . "</td>"
               . "<td class=\"out\">" . htmlspecialchars($row['typus_lat']) . "</td>"
               . "<td class=\"outCenter\" title=\"" . htmlspecialchars($row['collection']) . "\">"
               .  htmlspecialchars($row['coll_short']) . " " . htmlspecialchars($row['HerbNummer']) . "</td>";
            if ($row['typusID']) {
                echo "<td class=\"outCenter\">"
                   .   "<input type=\"checkbox\" id=\"cbTypeLabelMap_$id\"" . (($row['label'] & 0x1) ? " checked" : "")
                   .     " onChange=\"toggleLabelWrapper(1,'$id')\">"
                   . "</td>\n";
                echo "<td class=\"outCenter\">"
                   .   "<input type=\"checkbox\" id=\"cbTypeLabelSpec_$id\"" . (($row['label'] & 0x2) ? " checked" : "")
                   .     " onChange=\"toggleLabelWrapper(2,'$id')\">"
                   . "</td>\n";
            } else {
                echo "<td class=\"out\"></td>\n";
                echo "<td class=\"out\"></td> \n";
            }
            echo "<td class=\"outCenter\">"
               .   "<input type=\"checkbox\" id=\"cbBarcodeLabel_$id\"" . (($row['label'] & 0x4) ? " checked" : "")
               .     " onChange=\"toggleLabelWrapper(3,'$id')\">"
               . "</td>\n";
            echo "<td class=\"outCenter\">"
               .   "<input style=\"width: 1em;\" type=\"text\" name=\"inpSL_$id\" id=\"inpSL_$id\" maxlength=\"1\" "
               .     "value=\"" . (($row['label'] & 0xf0) / 16) . "\" onkeyup=\"updtLabelWrapper('$id',document.f.inpSL_$id.value)\">"
               . "</td>";
            echo "</tr>\n";
            $nr++;
        }
        $linkList[0] = $nr - 1;
        $_SESSION['labelLinkList'] = $linkList;
        echo "</table>\n";
    } else {
        echo "<b>nothing found!</b>\n";
    }
}
?>
</form>

</body>
</html>