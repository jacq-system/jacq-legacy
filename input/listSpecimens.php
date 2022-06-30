<?php
session_start();
require("inc/connect.php");
require("inc/herbardb_input_functions.php");
require("inc/api_functions.php");
require("inc/log_functions.php");
require __DIR__ . '/vendor/autoload.php';

use Jaxon\Jaxon;

$jaxon = jaxon();
$jaxon->setOption('core.request.uri', 'ajax/listWUServer.php');

$jaxon->register(Jaxon::CALLABLE_FUNCTION, "makeDropdownInstitution");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "makeDropdownCollection");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "getUserDate");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "toggleTypeLabelMap");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "toggleTypeLabelSpec");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "toggleBarcodeLabel");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "checkTypeLabelMapPdfButton");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "checkTypeLabelSpecPdfButton");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "checkBarcodeLabelPdfButton");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "updtStandardLabel");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "checkStandardLabelPdfButton");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "setAll");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "clearAll");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "listSpecimens");

if (!isset($_SESSION['wuCollection']))  { $_SESSION['wuCollection'] = 0; }
if (!isset($_SESSION['sTyp']))          { $_SESSION['sTyp'] = ''; }
if (!isset($_SESSION['sType']))         { $_SESSION['sType'] = 0; }
if (!isset($_SESSION['sImages']))       { $_SESSION['sImages'] = ''; }
if (!isset($_SESSION['sLinkList']))     { $_SESSION['sLinkList'] = array(); }
if (!isset($_SESSION['sUserID']))       { $_SESSION['sUserID'] = -1; }
if (!isset($_SESSION['sUserDate']))     { $_SESSION['sUserDate'] = ''; }
if (!isset($_SESSION['sLabelDate']))    { $_SESSION['sLabelDate'] = ''; }
if (!isset($_SESSION['sGeoGeneral']))   { $_SESSION['sGeoGeneral'] = ''; }
if (!isset($_SESSION['sGeoRegion']))    { $_SESSION['sGeoRegion'] = ''; }
if (!isset($_SESSION['sItemsPerPage'])) { $_SESSION['sItemsPerPage'] = 10; }

$nrSel = (isset($_GET['nr'])) ? intval($_GET['nr']) : 0;
$_SESSION['sNr'] = $nrSel;
$swBatch = (checkRight('batch')) ? true : false; // nur user mit Recht "batch" können Batches hinzufügen

if (isset($_POST['search']) || isset($_GET['taxonID'])  ) {
    $_SESSION['sType'] = 1;
	if(isset($_GET['taxonID'])){
		$_SESSION['taxonID'] = intval($_GET['taxonID']);
		$_SESSION['wuCollection']=0; // = $_POST['collection'];
		$_SESSION['sNumber']='';//      = $_POST['number'];
		$_SESSION['sSeries']='' ;//     = $_POST['series'];
		$_SESSION['sFamily']='' ;//     = $_POST['family'];
		$_SESSION['sTaxon'] ='' ;//     = $_POST['taxon'];
		$_SESSION['sTaxonAlt'] ='';//   = $_POST['taxon_alt'];
		$_SESSION['sCollector'] ='';//  = $_POST['collector'];
		$_SESSION['sNumberC'] ='' ;//   = $_POST['numberC'];
		$_SESSION['sDate']   =''  ;//   = $_POST['date'];
		$_SESSION['sGeoGeneral']='';// = $_POST['geo_general'];
		$_SESSION['sGeoRegion']=''  ;// = $_POST['geo_region'];
		$_SESSION['sCountry']='' ;//    = $_POST['country'];
		$_SESSION['sProvince']='' ;//   = $_POST['province'];
		$_SESSION['sLoc']   =''   ;//   = $_POST['loc'];
        $_SESSION['sHabitat'] = '';//   = $_POST['habitat'];
        $_SESSION['sHabitus'] = '';//   = $_POST['habitus'];
		$_SESSION['sBemerkungen'] ='';//= $_POST['annotations'];
		$_SESSION['sTyp'] ='' ;//  = (($_POST['typ']=="only"='' ? true : false='';
		$_SESSION['sImages']='';// = $_POST['images'];
	}else{
		unset($_SESSION['taxonID']);
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
        $_SESSION['sHabitat']     = $_POST['habitat'];
        $_SESSION['sHabitus']     = $_POST['habitus'];
		$_SESSION['sBemerkungen'] = $_POST['annotations'];

		$_SESSION['sTyp']    = (($_POST['typ']=="only") ? true : false);
		$_SESSION['sImages'] = $_POST['images'];
	}

    $_SESSION['sOrder'] = "genus, te.epithet, ta.author, "
                        . "Sammler, Sammler_2, series, Nummer, alt_number, Datum, "
                        . "typus_lat";
    $_SESSION['sOrTyp'] = 1;
    $_SESSION['labelOrder'] = $_SESSION['sOrder'];
} else if (isset($_POST['selectUser'])) {
    $_SESSION['sType'] = 2;
    $_SESSION['wuCollection'] = 0;
    $_SESSION['sNumber'] = $_SESSION['sSeries'] = $_SESSION['sFamily'] = "";
    $_SESSION['sTaxon'] = $_SESSION['sTaxonAlt'] = $_SESSION['sCollector'] = $_SESSION['sNumberC'] = "";
    $_SESSION['sDate'] = $_SESSION['sCountry'] = $_SESSION['sProvince'] = $_SESSION['sLoc'] = "";
    $_SESSION['sTyp'] = $_SESSION['sImages'] = $_SESSION['sGeoGeneral'] = $_SESSION['sGeoRegion'] = "";
    $_SESSION['sHabitat'] = $_SESSION['sHabitus'] = "";
    $_SESSION['sBemerkungen'] = "";

    $_SESSION['sUserID'] = $_POST['userID'];
    $_SESSION['sUserDate'] = $_POST['user_date'];
} else if (isset($_POST['prepareLabels'])) {
    $_SESSION['sType'] = 3;
    $_SESSION['wuCollection'] = 0;
    $_SESSION['sNumber'] = $_SESSION['sSeries'] = $_SESSION['sFamily'] = "";
    $_SESSION['sTaxon'] = $_SESSION['sTaxonAlt'] = $_SESSION['sCollector'] = $_SESSION['sNumberC'] = "";
    $_SESSION['sDate'] = $_SESSION['sCountry'] = $_SESSION['sProvince'] = $_SESSION['sLoc'] = "";
    $_SESSION['sTyp'] = $_SESSION['sImages'] = $_SESSION['sGeoGeneral'] = $_SESSION['sGeoRegion'] = "";
    $_SESSION['sHabitat'] = $_SESSION['sHabitus'] = "";
    $_SESSION['sBemerkungen'] = "";

    $_SESSION['sLabelDate'] = $_POST['label_date'];

    $_SESSION['sOrder'] = "genus, te.epithet, ta.author, "
                        . "Sammler, Sammler_2, series, Nummer, alt_number, Datum, "
                        . "typus_lat";
    $_SESSION['sOrTyp'] = 1;
    $_SESSION['labelOrder'] = $_SESSION['sOrder'];
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
    if ($_SESSION['sOrTyp'] < 0) {
        $_SESSION['sOrder'] = implode(" DESC, ", explode(", ", $_SESSION['sOrder'])) . " DESC";
    }
    $_SESSION['labelOrder'] = $_SESSION['sOrder'];
}

function makeDropdownInstitution()
{
    echo "<select size=\"1\" name=\"collection\">\n";
    echo "  <option value=\"0\"></option>\n";

    $sql = "SELECT source_id, source_code FROM herbarinput.meta ORDER BY source_code";
    $result = dbi_query($sql);
    while ($row = mysqli_fetch_array($result)) {
        echo "  <option value=\"-" . htmlspecialchars($row['source_id']) . "\"";
        if (-$_SESSION['wuCollection'] == $row['source_id']) {
            echo " selected";
        }
        echo ">" . htmlspecialchars($row['source_code']) . "</option>\n";
    }

    echo "  </select>\n";
}

function makeDropdownCollection()
{
    echo "<select size=\"1\" name=\"collection\">\n";
    echo "  <option value=\"0\"></option>\n";

    $sql = "SELECT collectionID, collection FROM tbl_management_collections ORDER BY collection";
    $result = dbi_query($sql);
    while ($row = mysqli_fetch_array($result)) {
        echo "  <option value=\"" . htmlspecialchars($row['collectionID']) . "\"";
        if ($_SESSION['wuCollection'] == $row['collectionID']) echo " selected";
        echo ">" . htmlspecialchars($row['collection']) . "</option>\n";
    }

    echo "  </select>\n";
}

function makeDropdownUsername()
{
    $sql = "SELECT userID, firstname, surname, username
            FROM herbarinput_log.tbl_herbardb_users
            WHERE userID IN
             (SELECT userID FROM herbarinput_log.log_specimens GROUP BY userID)
            ORDER BY surname, firstname, username";
    $result = dbi_query($sql);
    echo "<select size='1' name='userID' onchange='jaxon_getUserDate(document.fm2.userID.options[document.fm2.userID.selectedIndex].value)'>\n";
    echo "  <option value='-1'></option>\n";
    echo "  <option value='0'" . (($_SESSION['sUserID'] == 0) ? " selected" : '') . ">--- all users ---</option>\n";
    while ($row = mysqli_fetch_array($result)) {
        echo "  <option value='" . htmlspecialchars($row['userID']) . "'";
        if ($_SESSION['sUserID'] == $row['userID']) {
            echo " selected";
        }
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
    if ($label || intval($_SESSION['sUserID']) >= 0) {
        $sql = "SELECT DATE(timestamp) as date
                FROM herbarinput_log.log_specimens
                WHERE TIMESTAMPDIFF(MONTH, timestamp, NOW()) < 7120 ";
        if ($label) {
            $sql .= "AND userID='" . intval($_SESSION['uid']) . "' ";
        } elseif (intval($_SESSION['sUserID']) > 0) {
            $sql .= "AND userID='" . intval($_SESSION['sUserID']) . "' ";
        }
        $sql .= "GROUP BY date
                 ORDER BY date DESC";
        $rows = dbi_query($sql)->fetch_all(MYSQLI_ASSOC);
    } else {
        $rows = array();
    }
    echo "<select size='1' ";
    if ($label) {
        echo "name='label_date' id='label_date'>\n";
    } else {
        echo "name='user_date' id='user_date'>\n";
    }
    foreach ($rows as $row) {
        echo "  <option ";
        if ((!$label && $_SESSION['sUserDate'] == $row['date']) || ($label && $_SESSION['sLabelDate'] == $row['date'])) {
            echo " selected";
        }
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
    $result = dbi_query($sql);

    return mysqli_num_rows($result);
}


if (isset($_POST['select']) && $_POST['select'] && isset($_POST['specimen']) && $_POST['specimen']) {
    $location = "Location: editSpecimens.php?sel=<" . $_POST['specimen'] . ">";
    if (SID) $location .= "&" . SID;
    header($location);
    die();
}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - list Specimens</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <?php echo $jaxon->getScript(true, true); ?>
  <script src="js/freudLib.js" type="text/javascript"></script>
  <script src="js/parameters.php" type="text/javascript"></script>
  <script src="js/lib/jQuery/jquery-1.4.2.min.js" type="text/javascript"></script>
  <script src="js/lib/jQuery/jquery.pagination.js" type="text/javascript"></script>
  <link rel="stylesheet" type="text/css" href="js/lib/jQuery/css/pagination.css">
  <style>
      .pagination a {
        color: #FFFF00;
        border: 1px solid #000000;
      }

      .pagination .current {
          background: none repeat scroll 0 0 #AA0000;
          color: #FFFF00;
      }
  </style>
  <script type="text/javascript" language="JavaScript">
    var swInstitutionCollection = <?php echo ($_SESSION['wuCollection'] > 0) ? 1 : 0; ?>;

    function toggleInstitutionCollection() {
        if (swInstitutionCollection) {
            swInstitutionCollection = 0;
            jaxon_makeDropdownInstitution();
        } else {
            swInstitutionCollection = 1;
            jaxon_makeDropdownCollection();
        }
    }

    function toggleLabelWrapper(sel, id) {
      switch (sel) {
        case 1: jaxon_toggleTypeLabelMap(id);
                break;
        case 2: jaxon_toggleTypeLabelSpec(id);
                break;
        case 3: jaxon_toggleBarcodeLabel(id);
                break;
      }
    }
    function updtLabelWrapper(id, data) {
      jaxon_updtStandardLabel(id, data);
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

    jaxon_checkTypeLabelMapPdfButton();
    jaxon_checkTypeLabelSpecPdfButton();
    jaxon_checkStandardLabelPdfButton();
    jaxon_checkBarcodeLabelPdfButton();
  </script>
</head>

<body>

<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" name="fm1" id="fm1">
<table cellspacing="5" cellpadding="0">
<tr>
  <td align="right">&nbsp;<b><a href="#" id="lblInstitutionCollection" onclick="toggleInstitutionCollection();"><?php echo ($_SESSION['wuCollection'] > 0) ? 'Collection&nbsp;' : 'Institution&nbsp;'; ?></a></b>
    </td>
    <td id="drpInstitutionCollection"><?php ($_SESSION['wuCollection'] > 0) ? makeDropdownCollection() : makeDropdownInstitution(); ?></td>
  <td align="right">&nbsp;<b>Herbar Nr.&nbsp;</b></td>
    <td><input type="text" name="number" value="<?php echoSpecial('sNumber', 'SESSION'); ?>"></td>
  <td align="right">&nbsp;<b>Series&nbsp;</b></td>
    <td><input type="text" name="series" value="<?php echoSpecial('sSeries', 'SESSION'); ?>"></td>
  <td></td><td></td>
</tr><tr>
  <td align="right">&nbsp;<b>Family&nbsp;</b></td>
    <td><input type="text" name="family" value="<?php echoSpecial('sFamily', 'SESSION'); ?>"></td>
  <td align="right">&nbsp;<b>Taxon&nbsp;</b></td>
    <td><input type="text" name="taxon" value="<?php echoSpecial('sTaxon', 'SESSION'); ?>"></td>
  <td align="right">&nbsp;<b>ident. history</b></td>
    <td><input type="text" name="taxon_alt" value="<?php echoSpecial('sTaxonAlt', 'SESSION'); ?>"></td>
  <td></td><td></td>
</tr><tr>
  <td align="right">&nbsp;<b>Collector&nbsp;</b></td>
    <td><input type="text" name="collector" value="<?php echoSpecial('sCollector', 'SESSION'); ?>"></td>
  <td align="right">&nbsp;<b>Collector #&nbsp;</b></td>
    <td><input type="text" name="numberC" value="<?php echoSpecial('sNumberC', 'SESSION'); ?>"></td>
  <td align="right">&nbsp;<b>Date&nbsp;</b></td>
    <td><input type="text" name="date" value="<?php echoSpecial('sDate', 'SESSION'); ?>"></td>
  <td align="right">&nbsp;<b>Habitat&nbsp;</b></td>
    <td><input type="text" name="habitat" value="<?php echoSpecial('sHabitat', 'SESSION'); ?>"></td>
</tr><tr>
  <td align="right">&nbsp;<b>Continent&nbsp;</b></td>
    <td>
      <select size="1" name="geo_general">
      <option></option>
      <?php
        $sql = "SELECT geo_general
                FROM tbl_geo_region
                GROUP BY geo_general ORDER BY geo_general";
        $result = dbi_query($sql);
        while ($row=mysqli_fetch_array($result)) {
            echo "<option";
            if ($_SESSION['sGeoGeneral'] == $row['geo_general']) echo " selected";
            echo ">" . $row['geo_general'] . "</option>\n";
        }
      ?>
      </select>
    </td>
  <td align="right">&nbsp;<b>Region&nbsp;</b></td>
    <td>
      <select size="1" name="geo_region">
      <option></option>
      <?php
        $sql = "SELECT geo_region
                FROM tbl_geo_region
                ORDER BY geo_region";
        $result = dbi_query($sql);
        while ($row=mysqli_fetch_array($result)) {
            echo "<option";
            if ($_SESSION['sGeoRegion'] == $row['geo_region']) echo " selected";
            echo ">" . $row['geo_region'] . "</option>\n";
        }
      ?>
      </select>
    </td>
  <td align="right">&nbsp;<b>Loc.&nbsp;</b></td>
    <td><input type="text" name="loc" value="<?php echoSpecial('sLoc', 'SESSION'); ?>"></td>
  <td align="right">&nbsp;<b>Habitus&nbsp;</b></td>
    <td><input type="text" name="habitus" value="<?php echoSpecial('sHabitus', 'SESSION'); ?>"></td>
</tr><tr>
  <td align="right">&nbsp;<b>Country&nbsp;</b></td>
    <td><input type="text" name="country" value="<?php echoSpecial('sCountry', 'SESSION'); ?>"></td>
  <td align="right">&nbsp;<b>State/Province&nbsp;</b></td>
    <td><input type="text" name="province" value="<?php echoSpecial('sProvince', 'SESSION'); ?>"></td>
  <td align="right">&nbsp;<b>Annotation&nbsp;</b></td>
    <td><input type="text" name="annotations" value="<?php echoSpecial('sBemerkungen', 'SESSION'); ?>"></td>
  <td></td><td></td>
</tr><tr>
  <td colspan="2">
    <input type="radio" name="typ" value="all"<?php if(!$_SESSION['sTyp']) echo " checked"; ?>>
    <b>All records</b>
    <input type="radio" name="typ" value="only"<?php if($_SESSION['sTyp']) echo " checked"; ?>>
    <b>Type records only</b>
  </td><td colspan="4" align="right">
    <b>Images&nbsp;</b>
    <input type="radio" name="images" value="only"<?php if($_SESSION['sImages'] == 'only') echo " checked"; ?>>
    <b>Yes</b>
    <input type="radio" name="images" value="no"<?php if($_SESSION['sImages'] == 'no') echo " checked"; ?>>
    <b>No</b>
    <input type="radio" name="images" value="all"<?php if($_SESSION['sImages'] != 'only' && $_SESSION['sImages'] != 'no') echo " checked"; ?>>
    <b>All</b>
  </td><td colspan="2">
  </td>
</tr><tr>
  <td colspan="3">
      <input class="button" type="submit" name="search" value=" search ">
      <input class="button" type="button" onclick="document.location.href='listSpecimensExport.php?select=list&type=csv';return false;" name="downloadCSV" value=" download CSV ">
      <input class="button" type="button" onclick="document.location.href='listSpecimensExport.php?select=list&type=xslx';return false;" name="downloadXLSX" value=" download XLSX ">
      <input class="button" type="button" onclick="document.location.href='listSpecimensExport.php?select=list&type=ods';return false;" name="downloadODS" value=" download ODS ">
  </td>
  <td colspan="1" align="right">
  </td>
  <td colspan="2" align="right">
    <?php if (checkRight('specim')): ?>
    <input class="button" type="button" value="new entry" onClick="self.location.href='editSpecimens.php?sel=<0>&new=1'">
    <?php endif; ?>
  </td><td colspan="2">
  </td>
</tr>
</table>
</form>

<p>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" name="fm2">
<table cellspacing="0" cellpadding="0"><tr>
<td>
  <b>SpecimenID&nbsp;</b> <input type="text" name="specimen" value="<?php echoSpecial('specimen', 'POST'); ?>">
  <input class="button" type="submit" name="select" value=" Edit ">
</td><td style="width: 2em">&nbsp;</td><td>
  <?php makeDropdownDate(true); ?>
  <input class="button" type="submit" name="prepareLabels" value=" Labels ">
  <input class="button" type="image" onclick="document.location.href='listSpecimensExport.php?select=labels&type=xlsx';return false;" name="labelsXLSX" src="webimages/disk.png" title="download Labels XLS">
</td>
<?php if (checkRight('editor')):    // only editors may check logged in users ?>
<td style="width: 2em">&nbsp;</td><td>
  <b>User&nbsp;</b> <?php makeDropdownUsername(); ?> <?php makeDropdownDate(); ?>
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
        $result = dbi_query($sql);
        while ($row = mysqli_fetch_array($result)) {
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
                        $row = dbi_query($sql)->fetch_array();
                        if ($row['source_id'] != $_SESSION['sid']) {
                            $blocked = true;
                        }
                    }

                    if (!$blocked) {
                        $sql = "INSERT INTO api.tbl_api_specimens SET
                                 specimen_ID = '$id',
                                 batchID_fk = '$batch_id'";
                        dbi_query($sql);
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
    ?>
    <div style="width: 100%;">
        <div style="float: right;">
            <select id="items_per_page" onchange="listSpecimens();">
                <option value="10" <?php echo ($_SESSION['sItemsPerPage'] == 10) ? 'selected' : ''; ?>>10</option>
                <option value="30" <?php echo ($_SESSION['sItemsPerPage'] == 30) ? 'selected' : ''; ?>>30</option>
                <option value="50" <?php echo ($_SESSION['sItemsPerPage'] == 50) ? 'selected' : ''; ?>>50</option>
                <option value="100" <?php echo ($_SESSION['sItemsPerPage'] == 100) ? 'selected' : ''; ?>>100</option>
            </select>
        </div>
        <div class='specimen_pagination'></div>
        <div id='specimen_entries' style='padding-top: 15px; padding-bottom: 15px;'><div style="text-align: center;"><img src="webimages/loader.gif"></div></div>
        <div class='specimen_pagination'></div>
    </div>
    <script type="text/javascript">
        function listSpecimens() {
            jaxon_listSpecimens( 0, true, $('#items_per_page').val() );
        }

    // init pagination
    $(function() {
        listSpecimens();
    });
    </script>
    <?php
} else if ($_SESSION['sType'] == 2) {
    if (intval($_SESSION['sUserID']) >= 0 || strlen(trim($_SESSION['sUserDate'])) > 0) {
        $sql = "SELECT ls.specimenID, ls.updated, ls.timestamp, hu.firstname, hu.surname
                FROM herbarinput_log.log_specimens ls, herbarinput_log.tbl_herbardb_users hu
                WHERE ls.userID = hu.userID ";
        if (intval($_SESSION['sUserID']) > 0) {
            $sql .= "AND ls.userID = '" . intval($_SESSION['sUserID']) . "' ";
        }
        if (strlen(trim($_SESSION['sUserDate']))) {
            $searchDate = dbi_escape_string(trim($_SESSION['sUserDate']));
            $sql .= "AND ls.timestamp BETWEEN '$searchDate' AND ADDDATE('$searchDate','1') ";
        }
        $sql .= "ORDER BY ls.timestamp, hu.surname, hu.firstname";
        $result = dbi_query($sql);
        if (mysqli_num_rows($result) > 0) {
            echo "<table class=\"out\" cellspacing=\"0\">\n";
            echo "<tr class=\"out\">";
            echo "<th class=\"out\">User</th>";
            echo "<th class=\"out\">Timestamp</th>";
            echo "<th class=\"out\">specimenID</th>";
            echo "<th class=\"out\">updated</th>";
            echo "</tr>\n";
            $nr = 1;
            while ($row = mysqli_fetch_array($result)) {
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
        echo "<input type=\"button\" class=\"button\" value=\" set all \" onclick=\"jaxon_setAll()\"> ".
             "<input type=\"button\" class=\"button\" value=\" clear all \" onclick=\"jaxon_clearAll()\"> ".
             "<input type=\"button\" class=\"button\" value=\"make PDF (Type map Labels)\" id=\"btMakeTypeLabelMapPdf\" onClick=\"showPDF('typeMap')\"> ".
             "<input type=\"button\" class=\"button\" value=\"make PDF (Type spec Labels)\" id=\"btMakeTypeLabelSpecPdf\" onClick=\"showPDF('typeSpec')\"> ".
             "<input type=\"button\" class=\"button\" value=\"make PDF (barcode Labels)\" id=\"btMakeBarcodeLabelPdf\" onClick=\"showPDF('barcode')\" >".
             "<input type=\"button\" class=\"button\" value=\"make PDF (standard Labels)\" id=\"btMakeStandardLabelPdf\" onClick=\"showPDF('std')\"\n>";
        echo "<p>\n";

        $searchDate = dbi_escape_string(trim($_SESSION['sLabelDate']));
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
                 AND ls.timestamp BETWEEN '$searchDate' AND ADDDATE('$searchDate','1')
                GROUP BY ls.specimenID
                ORDER BY ".$_SESSION['sOrder'];
        $result = dbi_query($sql);
        if (mysqli_num_rows($result) > 0) {
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
            while ($row = mysqli_fetch_array($result)) {
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
<script type="text/javascript">
    // added trim for HerbNummer to prevent spaces and tabs
    $(document).ready(function() {
        $('[name="number"]').blur(function() {
            this.value = this.value.trim();
            var number = this.value;
            var r = /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/ // Regex Pattern
            if (r.test(number)) { // Yes, a valid url
                $.ajax({
                    url: "ajax/convStabURItoHerbnummer.php",
                    data: {stableuri: number},
                    type: 'post',
                    success: function (data) {
                        document.getElementsByName("number")[0].value = data;
                        console.log("Success, you submit your form" + data);
                    }
                });   // Do your $.ajax({}); request here
                var number = this.value;
           }
        })
    });
</script>
</body>
</html>