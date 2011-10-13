<?php
session_start();
require("inc/connect.php");
require("inc/herbardb_input_functions.php");
require("inc/api_functions.php");
require("inc/log_functions.php");
require_once ("inc/xajax/xajax_core/xajax.inc.php");
no_magic();

$xajax = new xajax();
$xajax->setRequestURI("ajax/listWUServer.php");

$xajax->registerFunction("makeDropdownInstitution");
$xajax->registerFunction("makeDropdownCollection");
$xajax->registerFunction("getUserDate");
$xajax->registerFunction("toggleTypeLabelMap");
$xajax->registerFunction("toggleTypeLabelSpec");
$xajax->registerFunction("toggleBarcodeLabel");
$xajax->registerFunction("checkTypeLabelMapPdfButton");
$xajax->registerFunction("checkTypeLabelSpecPdfButton");
$xajax->registerFunction("checkBarcodeLabelPdfButton");
$xajax->registerFunction("updtStandardLabel");
$xajax->registerFunction("checkStandardLabelPdfButton");
$xajax->registerFunction("setAll");
$xajax->registerFunction("clearAll");

if (!isset($_SESSION['wuCollection'])) $_SESSION['wuCollection'] = '';
if (!isset($_SESSION['sTyp'])) $_SESSION['sTyp'] = '';
if (!isset($_SESSION['sType'])) $_SESSION['sType'] = 0;
if (!isset($_SESSION['sImages'])) $_SESSION['sImages'] = '';
if (!isset($_SESSION['sUserID'])) $_SESSION['sUserID'] = 0;
if (!isset($_SESSION['sLinkList'])) $_SESSION['sLinkList'] = array();

$nrSel = (isset($_GET['nr'])) ? intval($_GET['nr']) : 0;
$swBatch = (checkRight('batch')) ? true : false; // nur user mit Recht "batch" können Batches hinzufügen

if (isset($_POST['search'])) {
    $_SESSION['sType'] = 1;

    $_SESSION['wuCollection'] = $_POST['collection'];
    $_SESSION['sNumber']      = $_POST['number'];
    $_SESSION['sSeries']      = $_POST['series'];
    $_SESSION['sFamily']      = $_POST['family'];
    $_SESSION['sTaxon']       = $_POST['taxon'];
    $_SESSION['sTaxonAlt']    = $_POST['taxon_alt'];
    $_SESSION['sCollector']   = $_POST['collector'];
    $_SESSION['sNumberC']     = $_POST['numberC'];
    $_SESSION['sDate']        = $_POST['date'];
    $_SESSION['sGeoGeneral']  = $_POST['geo_general'];
    $_SESSION['sGeoRegion']   = $_POST['geo_region'];
    $_SESSION['sCountry']     = $_POST['country'];
    $_SESSION['sProvince']    = $_POST['province'];
    $_SESSION['sLoc']         = $_POST['loc'];
    $_SESSION['sBemerkungen'] = $_POST['annotations'];

    $_SESSION['sTyp']    = (($_POST['typ']=="only") ? true : false);
    $_SESSION['sImages'] = $_POST['images'];

    $_SESSION['sOrder'] = "genus, te.epithet, ta.author, "
                        . "Sammler, Sammler_2, series, Nummer, alt_number, Datum, "
                        . "typus_lat";
    $_SESSION['sOrTyp'] = 1;
} else if (isset($_POST['selectUser'])) {
    $_SESSION['sType'] = 2;
    $_SESSION['wuCollection'] = $_SESSION['sNumber'] = $_SESSION['sSeries'] = $_SESSION['sFamily'] = "";
    $_SESSION['sTaxon'] = $_SESSION['sTaxonAlt'] = $_SESSION['sCollector'] = $_SESSION['sNumberC'] = "";
    $_SESSION['sDate'] = $_SESSION['sCountry'] = $_SESSION['sProvince'] = $_SESSION['sLoc'] = "";
    $_SESSION['sTyp'] = $_SESSION['sImages'] = $_SESSION['sGeoGeneral'] = $_SESSION['sGeoRegion'] = "";
    $_SESSION['sBemerkungen'] = "";

    $_SESSION['sUserID'] = $_POST['userID'];
    $_SESSION['sUserDate'] = $_POST['user_date'];
} else if (isset($_POST['prepareLabels'])) {
    $_SESSION['sType'] = 3;
    $_SESSION['wuCollection'] = $_SESSION['sNumber'] = $_SESSION['sSeries'] = $_SESSION['sFamily'] = "";
    $_SESSION['sTaxon'] = $_SESSION['sTaxonAlt'] = $_SESSION['sCollector'] = $_SESSION['sNumberC'] = "";
    $_SESSION['sDate'] = $_SESSION['sCountry'] = $_SESSION['sProvince'] = $_SESSION['sLoc'] = "";
    $_SESSION['sTyp'] = $_SESSION['sImages'] = $_SESSION['sGeoGeneral'] = $_SESSION['sGeoRegion'] = "";
    $_SESSION['sBemerkungen'] = "";

    $_SESSION['sLabelDate'] = $_POST['label_date'];

    $_SESSION['sOrder'] = "genus, te.epithet, ta.author, "
                        . "Sammler, Sammler_2, series, Nummer, alt_number, Datum, "
                        . "typus_lat";
    $_SESSION['sOrTyp'] = 1;
} else if (isset($_GET['order'])) {
    if ($_GET['order'] == "b") {
        $_SESSION['sOrder'] = "Sammler, Sammler_2, series, Nummer, alt_number, Datum, "
                             . "genus, te.epithet, ta.author, "
                             . "typus_lat";
        if ($_SESSION['sOrTyp'] == 2) {
            $_SESSION['sOrTyp'] = -2;
        } else {
            $_SESSION['sOrTyp'] = 2;
        }
    }
    else if ($_GET['order'] == "d") {
        $_SESSION['sOrder'] = "typus_lat, genus, te.epithet, ta.author, "
                            . "Sammler, Sammler_2, series, Nummer, alt_number, Datum";
        if ($_SESSION['sOrTyp'] == 4) {
            $_SESSION['sOrTyp'] = -4;
        } else {
            $_SESSION['sOrTyp'] = 4;
        }
    }
    else if ($_GET['order'] == "e") {
        $_SESSION['sOrder'] = "collection, HerbNummer";
        if ($_SESSION['sOrTyp'] == 5) {
            $_SESSION['sOrTyp'] = -5;
        } else {
            $_SESSION['sOrTyp'] = 5;
        }
    }
    else {
        $_SESSION['sOrder'] = "genus, te.epithet, ta.author, "
                            . "Sammler, Sammler_2, series, Nummer, alt_number, Datum, "
                            . "typus_lat";
        if ($_SESSION['sOrTyp'] == 1) {
            $_SESSION['sOrTyp'] = -1;
        } else {
            $_SESSION['sOrTyp'] = 1;
        }
    }
    if ($_SESSION['sOrTyp'] < 0) $_SESSION['sOrder'] = implode(" DESC, ", explode(", ", $_SESSION['sOrder'])) . " DESC";
}

function makeDropdownInstitution()
{
    echo "<select size=\"1\" name=\"collection\">\n";
    echo "  <option value=\"0\"></option>\n";

    $sql = "SELECT source_id, source_code FROM herbarinput.meta ORDER BY source_code";
    $result = db_query($sql);
    while ($row = mysql_fetch_array($result)) {
        echo "  <option value=\"-" . htmlspecialchars($row['source_id']) . "\"";
        if (-$_SESSION['wuCollection'] == $row['source_id']) echo " selected";
        echo ">" . htmlspecialchars($row['source_code']) . "</option>\n";
    }

    echo "  </select>\n";
}

function makeDropdownCollection()
{
    echo "<select size=\"1\" name=\"collection\">\n";
    echo "  <option value=\"0\"></option>\n";

    $sql = "SELECT collectionID, collection FROM tbl_management_collections ORDER BY collection";
    $result = db_query($sql);
    while ($row = mysql_fetch_array($result)) {
        echo "  <option value=\"" . htmlspecialchars($row['collectionID']) . "\"";
        if ($_SESSION['wuCollection'] == $row['collectionID']) echo " selected";
        echo ">" . htmlspecialchars($row['collection']) . "</option>\n";
    }

    echo "  </select>\n";
}

function makeDropdownUsername()
{
    $sql = "SELECT hu.userID, hu.firstname, hu.surname, hu.username
            FROM herbarinput_log.tbl_herbardb_users hu, herbarinput_log.log_specimens ls
            WHERE hu.userID=ls.userID
            GROUP BY hu.userID
            ORDER BY surname, firstname, username";
    $result = db_query($sql);
    echo "<select size=\"1\" name=\"userID\" onchange=\"xajax_getUserDate(document.fm2.userID.options[document.fm2.userID.selectedIndex].value)\">\n";
    echo "  <option value=\"0\"></option>";
    while ($row = mysql_fetch_array($result)) {
        echo "  <option value=\"" . htmlspecialchars($row['userID']) . "\"";
        if ($_SESSION['sUserID'] == $row['userID']) echo " selected";
        echo ">";
        if (trim($row['firstname']) || trim($row['surname'])) {
            echo htmlspecialchars($row['firstname']) . " " . htmlspecialchars($row['surname']);
        } else {
            echo htmlspecialchars("<" . $row['username'] . ">");
        }
        echo "</option>\n";
    }
    echo "  </select>\n";
}

function makeDropdownDate($label = false)
{
    $sql = "SELECT DATE(timestamp) as date
            FROM herbarinput_log.log_specimens
            WHERE TIMESTAMPDIFF(MONTH, timestamp, NOW()) < 12 ";
    if (intval($label)) {
        $sql .= "AND userID='" . intval($_SESSION['uid']) . "' ";
    } elseif (intval($_SESSION['sUserID'])) {
        $sql .= "AND userID='" . intval($_SESSION['sUserID']) . "' ";
    }
    $sql .= "GROUP BY date
             ORDER BY date DESC";
    $result = db_query($sql);
    echo "<select size=\"1\" ";
    if ($label) {
        echo "name=\"label_date\" id=\"label_date\">\n";
    } else {
        echo "name=\"user_date\" id=\"user_date\">\n";
    }
    while($row=mysql_fetch_array($result)) {
        echo "  <option ";
        if ((!$label && $_SESSION['sUserDate'] == $row['date']) || ($label && $_SESSION['sLabelDate'] == $row['date'])) echo " selected";
        echo ">" . htmlspecialchars($row['date']) . "</option>\n";
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
        if (strstr($row['alt_number'], "s.n.")) $text .= " [" . $row['Datum'] . "]";
    }

    return $text;
}

function locationItem($row)
{
    $text = "";
    if (trim($row['nation_engl'])) {
        $text = "<span style=\"background-color:white;\">" . htmlspecialchars(trim($row['nation_engl'])) . "</span>";
    }
    if (trim($row['provinz'])) {
        if (strlen($text) > 0) $text .= ". ";
        $text .= "<span style=\"background-color:white;\">" . htmlspecialchars(trim($row['provinz'])) . "</span>";
    }
    if (trim($row['Fundort']) && $row['collectionID'] != 12) {
        if (strlen($text) > 0) $text .= ". ";
        $text .= htmlspecialchars(trim($row['Fundort']));
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

function getImportEntries($checked)
{
    $sql = "SELECT specimen_ID
            FROM tbl_specimens_import
            WHERE userID = '" . intval($_SESSION['uid']) . "'
             AND " . (($checked) ? "checked > 0" : "checked = 0");
    $result = db_query($sql);

    return mysql_num_rows($result);
}


if (isset($_POST['select']) && $_POST['select'] && isset($_POST['specimen']) && $_POST['specimen']) {
    $location = "Location: editSpecimens.php?sel=<" . $_POST['specimen'] . ">";
    if (SID) $location .= "&" . SID;
    Header($location);
    die();
}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - list Specimens</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <?php $xajax->printJavascript('inc/xajax'); ?>
  <script type="text/javascript" language="JavaScript">
    var swInstitutionCollection = <?php echo ($_SESSION['wuCollection'] > 0) ? 1 : 0; ?>;

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
    function showImage(sel, server) {
      target = server+"/"+sel+"/show";
      MeinFenster = window.open(target,"imgBrowser");
      MeinFenster.focus();
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
        case 'typeMap':  target = "pdfLabelTypesMap.php"; label = "labelTypesMap"; break;
        case 'typeSpec': target = "pdfLabelTypesSpec.php"; label = "labelTypesSpec"; break;
        case 'std':      target = "pdfLabelStandard.php"; label = "labelStandard"; break;
        case 'barcode':  target = "pdfLabelBarcode.php"; label = "labelBarcode"; break;
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

<input class="button" type="button" value=" close window " onclick="self.close()" id="close">

<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" name="fm1" id="fm1">
<table cellspacing="5" cellpadding="0">
<tr>
  <td align="right">&nbsp;<b><a href="#" id="lblInstitutionCollection" onclick="toggleInstitutionCollection();"><?php echo ($_SESSION['wuCollection'] > 0) ? 'Collection:' : 'Institution:'; ?></a></b>
    </td>
    <td id="drpInstitutionCollection"><?php ($_SESSION['wuCollection'] > 0) ? makeDropdownCollection() : makeDropdownInstitution(); ?></td>
  <td align="right">&nbsp;<b>Herbar Nr.:</b></td>
    <td><input type="text" name="number" value="<?php echoSpecial('sNumber', 'SESSION'); ?>"></td>
  <td align="right">&nbsp;<b>Series:</b></td>
    <td><input type="text" name="series" value="<?php echoSpecial('sSeries', 'SESSION'); ?>"></td>
</tr><tr>
  <td align="right">&nbsp;<b>Family:</b></td>
    <td><input type="text" name="family" value="<?php echoSpecial('sFamily', 'SESSION'); ?>"></td>
  <td align="right">&nbsp;<b>Taxon:</b></td>
    <td><input type="text" name="taxon" value="<?php echoSpecial('sTaxon', 'SESSION'); ?>"></td>
  <td align="right">&nbsp;<b>ident. history</b></td>
    <td><input type="text" name="taxon_alt" value="<?php echoSpecial('sTaxonAlt', 'SESSION'); ?>"></td>
</tr><tr>
  <td align="right">&nbsp;<b>Collector:</b></td>
    <td><input type="text" name="collector" value="<?php echoSpecial('sCollector', 'SESSION'); ?>"></td>
  <td align="right">&nbsp;<b>Number:</b></td>
    <td><input type="text" name="numberC" value="<?php echoSpecial('sNumberC', 'SESSION'); ?>"></td>
  <td align="right">&nbsp;<b>Date:</b></td>
    <td><input type="text" name="date" value="<?php echoSpecial('sDate', 'SESSION'); ?>"></td>
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
        while ($row=mysql_fetch_array($result)) {
            echo "<option";
            if ($_SESSION['sGeoGeneral'] == $row['geo_general']) echo " selected";
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
        while ($row=mysql_fetch_array($result)) {
            echo "<option";
            if ($_SESSION['sGeoRegion'] == $row['geo_region']) echo " selected";
            echo ">" . $row['geo_region'] . "</option>\n";
        }
      ?>
      </select>
    </td>
  <td align="right">&nbsp;<b>Loc.:</b></td>
    <td><input type="text" name="loc" value="<?php echoSpecial('sLoc', 'SESSION'); ?>"></td>
</tr><tr>
  <td align="right">&nbsp;<b>Country:</b></td>
    <td><input type="text" name="country" value="<?php echoSpecial('sCountry', 'SESSION'); ?>"></td>
  <td align="right">&nbsp;<b>State/Province:</b></td>
    <td><input type="text" name="province" value="<?php echoSpecial('sProvince', 'SESSION'); ?>"></td>
  <td align="right">&nbsp;<b>Annotation:</b></td>
    <td><input type="text" name="annotations" value="<?php echoSpecial('sBemerkungen', 'SESSION'); ?>"></td>
</tr><tr>
  <td colspan="2">
    <input type="radio" name="typ" value="all"<?php if(!$_SESSION['sTyp']) echo " checked"; ?>>
    <b>All records</b>
    <input type="radio" name="typ" value="only"<?php if($_SESSION['sTyp']) echo " checked"; ?>>
    <b>Type records only</b>
  </td><td colspan="4" align="right">
    <b>Images:</b>
    <input type="radio" name="images" value="only"<?php if($_SESSION['sImages'] == 'only') echo " checked"; ?>>
    <b>Yes</b>
    <input type="radio" name="images" value="no"<?php if($_SESSION['sImages'] == 'no') echo " checked"; ?>>
    <b>No</b>
    <input type="radio" name="images" value="all"<?php if($_SESSION['sImages'] != 'only' && $_SESSION['sImages'] != 'no') echo " checked"; ?>>
    <b>All</b>
  </td>
</tr><tr>
  <td colspan="2"><input class="button" type="submit" name="search" value=" search "></td>
  <td colspan="2" align="right">
  </td>
  <td colspan="2" align="right">
    <?php if (checkRight('specim')): ?>
    <input class="button" type="button" value="new entry" onClick="self.location.href='editSpecimens.php?sel=<0>&new=1'">
    <?php endif; ?>
  </td>
</tr>
</table>
</form>

<p>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" name="fm2">
<table cellspacing="0" cellpadding="0"><tr>
<td>
  <b>SpecimenID:</b> <input type="text" name="specimen" value="<?php echoSpecial('specimen', 'POST'); ?>">
  <input class="button" type="submit" name="select" value=" Edit ">
</td><td style="width: 2em">&nbsp;</td><td>
  <?php makeDropdownDate(true); ?>
  <input class="button" type="submit" name="prepareLabels" value=" Labels ">
</td>
<?php if (checkRight('editor')):    // only editors may check logged in users ?>
<td style="width: 2em">&nbsp;</td><td>
  <b>User:</b> <?php makeDropdownUsername(); ?> <?php makeDropdownDate(); ?>
  <input class="button" type="submit" name="selectUser" value=" search ">
</td>
<?php endif; ?>
</tr></table>
</form>

<hr>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" name="f">
<?php
$error = false;
if ($_SESSION['sType'] == 1) {
    if ($swBatch) {
        $batchValue = array();
        $batchText = array();
        $sql = "SELECT remarks, date_supplied, batchID, batchnumber, source_code
                FROM api.tbl_api_batches
                 LEFT JOIN herbarinput.meta ON api.tbl_api_batches.sourceID_fk = herbarinput.meta.source_id
                WHERE sent = '0'";
        if (!checkRight('batchAdmin')) $sql .= " AND api.tbl_api_batches.sourceID_fk = " . $_SESSION['sid'];  // check right and sourceID
        $sql .= " ORDER BY source_code, batchnumber, date_supplied DESC";
        $result = db_query($sql);
        while ($row = mysql_fetch_array($result)) {
            $batchValue[] = $row['batchID'];
            $batchNr = " <" . (($row['source_code']) ? $row['source_code'] . "-" : "") . $row['batchnumber'] . "> ";
            $batchText[] = $newbatchText[] = $row['date_supplied'] . "$batchNr (" . htmlspecialchars(trim($row['remarks'])) . ")";
        }
        if (isset($_POST['selectBatch'])) {
            $batch_id = intval($_POST['batch']);
            $idList = array();
            foreach ($_POST as $key => $value) {
                if (substr($key, 0, 11) == "batch_spec_" && $value) {
                    $id = substr($key, 11);

                    $blocked = false;
                    if (!checkRight('batchAdmin')) {
                        $sql = "SELECT source_id
                                FROM tbl_specimens, tbl_management_collections
                                WHERE tbl_specimens.collectionID = tbl_management_collections.collectionID
                                 AND specimen_ID = '$id'";
                        $row = mysql_fetch_array(db_query($sql));
                        if ($row['source_id'] != $_SESSION['sid']) {
                            $blocked = true;
                        }
                    }

                    if (!$blocked) {
                        $sql = "INSERT INTO api.tbl_api_specimens SET
                                 specimen_ID = '$id',
                                 batchID_fk = '$batch_id'";
                        db_query($sql);
                    }

                    // update or insert into update_tbl_api_units
                    $res = update_tbl_api_units($id);
                    update_tbl_api_units_identifications($id);
                    garbageCollection($id);
                    if (!$res) {
                        $error = true;
                        array_push($idList, $id);
                    }
      	        }
            }
        }
        echo "<table><tr><td>\n<select name=\"batch\">\n";
        for ($i = 0; $i < count($batchValue); $i++) {
            echo "  <option value=\"" . $batchValue[$i] . "\">" . htmlspecialchars($batchText[$i]) . "</option>\n";
        }
        echo "</select>\n</td><td>\n"
           . "<input class=\"button\" type=\"submit\" name=\"selectBatch\" value=\" insert selected specimen \">\n"
           . "</td><td style=\"width: 3em\">&nbsp;</td><td>\n"
           . "<input class=\"button\" type=\"button\" value=\" check all \" onclick=\"check_all()\">\n"
           . "</td></tr></table>\n<p>\n";
    }

    $sql = "SELECT s.specimen_ID, tg.genus, s.digital_image,
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
    $sql2 = "";
    if (trim($_SESSION['sTaxon'])) {
        $pieces = explode(" ", trim($_SESSION['sTaxon']));
        $part1 = array_shift($pieces);
        $part2 = array_shift($pieces);
        $sql2 .= " AND tg.genus LIKE '" . mysql_escape_string($part1) . "%'";
        if ($part2) {
            $sql2 .= " AND (te.epithet LIKE '" . mysql_escape_string($part2) . "%' ".
                      "OR te1.epithet LIKE '" . mysql_escape_string($part2) . "%' ".
                      "OR te2.epithet LIKE '" . mysql_escape_string($part2) . "%' ".
                      "OR te3.epithet LIKE '" . mysql_escape_string($part2) . "%')";
        }
    }
    if (trim($_SESSION['sSeries'])) {
        $sql2 .= " AND ss.series LIKE '%" . mysql_escape_string(trim($_SESSION['sSeries'])) . "%'";
    }
    if (trim($_SESSION['wuCollection'])) {
        if (trim($_SESSION['wuCollection']) > 0) {
            $sql2 .= " AND s.collectionID=" . quoteString(trim($_SESSION['wuCollection']));
        } else {
            $sql2 .= " AND mc.source_id=" . quoteString(abs(trim($_SESSION['wuCollection'])));
        }
    }
    if (trim($_SESSION['sNumber'])) {
        $sql2 .= " AND s.HerbNummer LIKE '%" . mysql_escape_string(trim($_SESSION['sNumber'])) . "%'";
    }
    if (trim($_SESSION['sFamily'])) {
        $sql2 .= " AND tf.family LIKE '" . mysql_escape_string(trim($_SESSION['sFamily'])) . "%'";
    }
    if (trim($_SESSION['sCollector'])) {
        $sql2 .= " AND (c.Sammler LIKE '" . mysql_escape_string(trim($_SESSION['sCollector'])) . "%' OR
                       c2.Sammler_2 LIKE '%" . mysql_escape_string(trim($_SESSION['sCollector'])) . "%')";
    }
    if (trim($_SESSION['sNumberC'])) {
        $sql2 .= " AND (s.Nummer LIKE '" . mysql_escape_string(trim($_SESSION['sNumberC'])) . "%' OR
                        s.alt_number LIKE '%" . mysql_escape_string(trim($_SESSION['sNumberC'])) . "%' OR
                        s.series_number LIKE '" . mysql_escape_string(trim($_SESSION['sNumberC'])) . "%') ";
    }
    if (trim($_SESSION['sDate'])) {
        $sql2 .= " AND s.Datum LIKE '" . mysql_escape_string(trim($_SESSION['sDate'])) . "%'";
    }
    if (trim($_SESSION['sGeoGeneral'])) {
        $sql2 .= " AND r.geo_general LIKE '" . mysql_escape_string(trim($_SESSION['sGeoGeneral'])) . "%'";
    }
    if (trim($_SESSION['sGeoRegion'])) {
        $sql2 .= " AND r.geo_region LIKE '" . mysql_escape_string(trim($_SESSION['sGeoRegion'])) . "%'";
    }
    if (trim($_SESSION['sCountry'])) {
        $sql2 .= " AND n.nation_engl LIKE '" . mysql_escape_string(trim($_SESSION['sCountry'])) . "%'";
    }
    if (trim($_SESSION['sProvince'])) {
        $sql2 .= " AND p.provinz LIKE '" . mysql_escape_string(trim($_SESSION['sProvince'])) . "%'";
    }
    if (trim($_SESSION['sLoc'])) {
        $sql2 .= " AND s.Fundort LIKE '%" . mysql_escape_string(trim($_SESSION['sLoc'])) . "%'";
    }
    if (trim($_SESSION['sBemerkungen'])) {
        $sql2 .= " AND s.Bemerkungen LIKE '%" . mysql_escape_string(trim($_SESSION['sBemerkungen'])) . "%'";
    }
    if (trim($_SESSION['sTaxonAlt'])) {
        $sql2 .= " AND s.taxon_alt LIKE '%" . mysql_escape_string(trim($_SESSION['sTaxonAlt'])) . "%'";
    }
    if ($_SESSION['sTyp']) {
        $sql2 .= " AND s.typusID != 0";
    }
    if ($_SESSION['sImages'] == 'only') {
        $sql2 .= " AND s.digital_image != 0";
    } else if ($_SESSION['sImages'] == 'no') {
        $sql2 .= " AND s.digital_image = 0";
    }

    $sql3 = " ORDER BY " . $_SESSION['sOrder'] . " LIMIT 1001";

    if (strlen($sql2) == 0) {
        echo "<b>empty search criteria are not allowed</b>\n";
    } else {
        $result = db_query($sql . $sql2 . " ORDER BY " . $_SESSION['sOrder'] . " LIMIT 1001");
        if (mysql_num_rows($result) > 1000) {
            echo "<b>no more than 1000 results allowed</b>\n";
        } elseif (mysql_num_rows($result) > 0) {
            echo "<table class=\"out\" cellspacing=\"0\">\n";
            echo "<tr class=\"out\">";
            echo "<th class=\"out\"></th>";
            echo "<th class=\"out\">"
               . "<a href=\"" . $_SERVER['PHP_SELF'] . "?order=a\">Taxon</a>" . sortItem($_SESSION['sOrTyp'], 1) . "</th>";
            echo "<th class=\"out\">"
               . "<a href=\"" . $_SERVER['PHP_SELF'] . "?order=b\">Collector</a>" . sortItem($_SESSION['sOrTyp'], 2) . "</th>";
            echo "<th class=\"out\">Date</th>";
            echo "<th class=\"out\">X/Y</th>";
            echo "<th class=\"out\">Location</th>";
            echo "<th class=\"out\">"
               . "<a href=\"" . $_SERVER['PHP_SELF'] . "?order=d\">Typus</a>" . sortItem($_SESSION['sOrTyp'], 4) . "</th>";
            echo "<th class=\"out\">"
               . "<a href=\"" . $_SERVER['PHP_SELF'] . "?order=e\">Coll.</a>" . sortItem($_SESSION['sOrTyp'], 5) . "</th>";
            if ($swBatch) echo "<th class=\"out\">Batch</th>";
            echo "</tr>\n";
            $nr = 1;
            while ($row = mysql_fetch_array($result)) {
                $linkList[$nr] = $row['specimen_ID'];

                if ($row['digital_image']) {
                    $digitalImage = "<a href=\"javascript:showImage('" . $row['specimen_ID'] . "', '" . getPictureServerIP($row['specimen_ID']) . "')\">"
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
                    $textLatLon = "<td class=\"out\" style=\"text-align: center\" title=\"" . round($lat, 2) . "&deg; / " . round($lon, 2) . "&deg;\">"
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
                   .  "<a href=\"editSpecimens.php?sel=".htmlentities("<".$row['specimen_ID'].">")."&nr=$nr&ptid=0\">"
                   .  htmlspecialchars(taxonItem($row))."</a></td>"
                   . "<td class=\"out\">".htmlspecialchars(collectorItem($row))."</td>"
                   . "<td class=\"outNobreak\">".htmlspecialchars($row['Datum'])."</td>"
                   . $textLatLon
                   . "<td class=\"out\">".locationItem($row)."</td>"
                   . "<td class=\"out\">".htmlspecialchars($row['typus_lat'])."</td>"
                   . "<td class=\"outCenter\" title=\"".htmlspecialchars($row['collection'])."\">"
                   .  htmlspecialchars($row['coll_short'])." ".htmlspecialchars($row['HerbNummer'])."</td>";
                if ($swBatch) {
                    echo "<td class=\"out\" style=\"text-align: center\">";
                    $resultDummy = db_query("SELECT t1.remarks FROM api.tbl_api_batches AS t1, api.tbl_api_specimens AS t2 WHERE t2.specimen_ID = '" . $row['specimen_ID'] . "' AND t1.batchID = t2.batchID_fk");
                    if (mysql_num_rows($resultDummy) > 0) {
                        //echo "&radic;";
                        $rowDummy = mysql_fetch_array($resultDummy);
                        echo $rowDummy['remarks'];
                    } else {
                        echo "<input type=\"checkbox\" name=\"batch_spec_" . $row['specimen_ID'] . "\">";
                    }
                    echo "</td>";
                }
                echo "</tr>\n";
                $nr++;
            }
            $linkList[0] = $nr - 1;
            $_SESSION['sLinkList'] = $linkList;
            echo "</table>\n";
        } else {
            echo "<b>nothing found!</b>\n";
        }
    }
} else if ($_SESSION['sType'] == 2) {
    if (intval($_SESSION['sUserID']) || strlen(trim($_SESSION['sUserDate'])) > 0) {
        $sql = "SELECT ls.specimenID, ls.updated, ls.timestamp, hu.firstname, hu.surname
                FROM herbarinput_log.log_specimens ls, herbarinput_log.tbl_herbardb_users hu
                WHERE ls.userID = hu.userID ";
        if (intval($_SESSION['sUserID'])) $sql .= "AND ls.userID = '" . intval($_SESSION['sUserID']) . "' ";
        if (strlen(trim($_SESSION['sUserDate']))) {
            $searchDate = mysql_escape_string(trim($_SESSION['sUserDate']));
            $sql .= "AND ls.timestamp BETWEEN '$searchDate' AND ADDDATE('$searchDate','1') ";
        }
        $sql .= "ORDER BY ls.timestamp, hu.surname, hu.firstname";
        $result = db_query($sql);
        if (mysql_num_rows($result) > 0) {
            echo "<table class=\"out\" cellspacing=\"0\">\n";
            echo "<tr class=\"out\">";
            echo "<th class=\"out\">User</th>";
            echo "<th class=\"out\">Timestamp</th>";
            echo "<th class=\"out\">specimenID</th>";
            echo "<th class=\"out\">updated</th>";
            echo "</tr>\n";
            $nr = 1;
            while ($row = mysql_fetch_array($result)) {
                $linkList[$nr] = $row['specimenID'];
                echo "<tr class=\"" . (($nrSel == $nr) ? "outMark" : "out") . "\">"
                   . "<td class=\"out\">" . htmlspecialchars($row['firstname'] . " " . $row['surname']) . "</td>"
                   . "<td class=\"out\">" . htmlspecialchars($row['timestamp']) . "</td>"
                   . "<td class=\"out\">"
                   .  "<a href=\"editSpecimens.php?sel=" . htmlentities("<" . $row['specimenID'] . ">") . "&nr=$nr\">"
                   .  htmlspecialchars($row['specimenID']) . "</a></td>"
                   . "<td class=\"out\">" . (($row['updated']) ? "updated" : "") . "</td>"
                   . "</tr>\n";
                $nr++;
            }
            $linkList[0] = $nr - 1;
            $_SESSION['sLinkList'] = $linkList;
            echo "</table>\n";
        } else {
            echo "<b>nothing found!</b>\n";
        }
    } else {
        echo "<b>select either user or date or both!!</b>\n";
    }
} else if ($_SESSION['sType'] == 3) {
    if (strlen(trim($_SESSION['sLabelDate'])) > 0) {
        echo "<input type=\"button\" class=\"button\" value=\" set all \" onclick=\"xajax_setAll()\"> ".
             "<input type=\"button\" class=\"button\" value=\" clear all \" onclick=\"xajax_clearAll()\"> ".
             "<input type=\"button\" class=\"button\" value=\"make PDF (Type map Labels)\" id=\"btMakeTypeLabelMapPdf\" onClick=\"showPDF('typeMap')\"> ".
             "<input type=\"button\" class=\"button\" value=\"make PDF (Type spec Labels)\" id=\"btMakeTypeLabelSpecPdf\" onClick=\"showPDF('typeSpec')\"> ".
             "<input type=\"button\" class=\"button\" value=\"make PDF (barcode Labels)\" id=\"btMakeBarcodeLabelPdf\" onClick=\"showPDF('barcode')\" >".
             "<input type=\"button\" class=\"button\" value=\"make PDF (standard Labels)\" id=\"btMakeStandardLabelPdf\" onClick=\"showPDF('std')\"\n>";
        echo "<p>\n";

        $searchDate = mysql_escape_string(trim($_SESSION['sLabelDate']));
        $sql = "SELECT ls.specimenID, s.typusID, l.label,
                 tg.genus,
                 ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
                 ta4.author author4, ta5.author author5,
                 te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
                 te4.epithet epithet4, te5.epithet epithet5,
                 c.Sammler, c2.Sammler_2, ss.series, s.series_number, s.Nummer, s.alt_number, s.Datum,
                 s.HerbNummer, mc.collection, mc.coll_short
                FROM (herbarinput_log.log_specimens ls, tbl_tax_species ts, tbl_tax_genera tg, tbl_management_collections mc, tbl_specimens s)
                 LEFT JOIN tbl_labels l ON (ls.specimenID = l.specimen_ID AND ls.userID = l.userID)
                 LEFT JOIN tbl_typi t ON t.typusID = s.typusID
                 LEFT JOIN tbl_specimens_series ss ON ss.seriesID = s.seriesID
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
                WHERE ls.specimenID = s.specimen_ID
                 AND ts.taxonID = s.taxonID
                 AND tg.genID = ts.genID
                 AND mc.collectionID = s.collectionID
                 AND ls.userID = '" . intval($_SESSION['uid']) . "'
                 AND ls.timestamp BETWEEN '$searchDate' AND ADDDATE('$searchDate','1')";
        $sql .= " GROUP BY ls.specimenID
                  ORDER BY ".$_SESSION['sOrder'];
        $result = db_query($sql);
        if (mysql_num_rows($result) > 0) {
            echo "<table class=\"out\" cellspacing=\"0\">\n";
            echo "<tr class=\"out\">";
            echo "<th class=\"out\">"
               . "<a href=\"" . $_SERVER['PHP_SELF'] . "?order=a\">Taxon</a>" . sortItem($_SESSION['sOrTyp'], 1) . "</th>";
            echo "<th class=\"out\">"
               . "<a href=\"" . $_SERVER['PHP_SELF'] . "?order=b\">Collector</a>" . sortItem($_SESSION['sOrTyp'], 2) . "</th>";
            echo "<th class=\"out\">"
               . "<a href=\"" . $_SERVER['PHP_SELF'] . "?order=e\">Coll.</a>" . sortItem($_SESSION['sOrTyp'], 5) . "</th>";
            echo "<th class=\"out\">Type map Label</th>";
            echo "<th class=\"out\">Type spec Label</th>";
            echo "<th class=\"out\">Barcode Label</th>";
            echo "<th class=\"out\">Standard Label</th>";
            echo "</tr>\n";
            $nr = 1;
            while ($row = mysql_fetch_array($result)) {
                $linkList[$nr] = $id = $row['specimenID'];
                echo "<tr class=\"" . (($nrSel == $nr) ? "outMark" : "out") . "\">\n";
                echo "<td class=\"out\"><a href=\"editSpecimens.php?sel=" . htmlentities("<$id>") . "&nr=$nr\">" . htmlspecialchars(taxonItem($row)) . "</a></td>\n";
                echo "<td class=\"out\">" . htmlspecialchars(collectorItem($row)) . "</td>\n";
                echo "<td class=\"outCenter\" title=\"" . htmlspecialchars($row['collection']) . "\">"
                   . htmlspecialchars($row['coll_short']) . " " . htmlspecialchars($row['HerbNummer']) . "</td>\n";
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
                   . "</td>"
                   . "</tr>\n";
                $nr++;
            }
            $linkList[0] = $nr - 1;
            $_SESSION['sLinkList'] = $linkList;
            echo "</table>\n";
        } else {
            echo "<b>nothing found!</b>\n";
        }
    } else {
        echo "<b>select date!!</b>\n";
    }
}


if ($error) {
    echo "<script type=\"text/javascript\" language=\"JavaScript\">\n"
       . "  alert('Update/Insert of the following specimenIDs blocked:\\n" . implode(", ", $idList) . "');\n"
       . "</script>\n";
}
?>
</form>

</body>
</html>