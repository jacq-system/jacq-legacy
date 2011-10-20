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

if (!isset($_SESSION['wuCollection'])) $_SESSION['wuCollection'] = '';  //wird von listSpecimens und listSpecimensImport gemeinsam benutzt
if (!isset($_SESSION['siTyp'])) $_SESSION['siTyp'] = '';
if (!isset($_SESSION['siType'])) $_SESSION['siType'] = 0;
if (!isset($_SESSION['siImages'])) $_SESSION['siImages'] = '';
if (!isset($_SESSION['siLinkList'])) $_SESSION['siLinkList'] = array();

$nrSel = (isset($_GET['nr'])) ? intval($_GET['nr']) : 0;

if (isset($_POST['importNow']) && $_POST['importNow']) {
    $columns = array('HerbNummer', 'collectionID', 'CollNummer', 'identstatusID', 'checked', 'accessible', 'taxonID',
                     'SammlerID', 'Sammler_2ID', 'seriesID', 'series_number', 'Nummer', 'alt_number', 'Datum', 'Datum2',
                     'det', 'typified', 'typusID', 'taxon_alt', 'NationID', 'provinceID', 'Bezirk', 'Coord_W', 'W_Min',
                     'W_Sec', 'Coord_N', 'N_Min', 'N_Sec', 'Coord_S', 'S_Min', 'S_Sec', 'Coord_E', 'E_Min', 'E_Sec',
                     'quadrant', 'quadrant_sub', 'exactness', 'altitude_min', 'altitude_max', 'Fundort', 'Fundort_engl',
                     'habitat', 'habitus', 'Bemerkungen', 'digital_image', 'digital_image_obs', 'garten', 'voucherID');
    $result = db_query("SELECT * FROM tbl_specimens_import WHERE checked > 0 AND userID = '" . intval($_SESSION['uid']) . "'");
    while ($row = mysql_fetch_array($result)) {
        $sql = "SELECT specimen_ID FROM tbl_specimens WHERE 1 = 1";
        foreach ($columns as $column) {
            if (strlen($row[$column]) > 0) {
                $sql .= " AND `$column`=" . quoteString($row[$column]);
            } else {
                $sql .= " AND $column IS NULL";
            }
        }
        $resultCheck = db_query($sql);
        if (mysql_num_rows($resultCheck) == 0) {
            $sql = "INSERT INTO tbl_specimens SET ";
            foreach ($columns as $column) {
                $sql .= "`" . $column . "` = " . quoteString($row[$column]) . ", ";
            }
            $sql .= "observation = '0'";
            db_query($sql);
            $specimen_ID = mysql_insert_id();
            logSpecimen($specimen_ID, 0);
            db_query("UPDATE tbl_external_import_content SET
                       specimen_ID = $specimen_ID,
                       pending = 0
                      WHERE specimen_ID = " . quoteString($row['specimen_ID']) . "
                       AND pending = 1");
            db_query("DELETE FROM tbl_specimens_import
                      WHERE specimen_ID = " . quoteString($row['specimen_ID']) . "
                       AND checked > 0
                       AND userID = '" . intval($_SESSION['uid']) . "'");
        }
    }
} elseif (isset($_POST['deleteNow']) && $_POST['deleteNow']) {
    db_query("DELETE FROM tbl_specimens_import WHERE checked = 0 AND userID = '" . intval($_SESSION['uid']) . "'");
}

if (!empty($_POST['search']) || !empty($_POST['importNow']) || !empty($_POST['deleteNow'])) {
    $_SESSION['siType'] = 1;

    $_SESSION['wuCollection']  = $_POST['collection'];
    $_SESSION['siNumber']      = $_POST['number'];
    $_SESSION['siSeries']      = $_POST['series'];
    $_SESSION['siFamily']      = $_POST['family'];
    $_SESSION['siTaxon']       = $_POST['taxon'];
    $_SESSION['siTaxonAlt']    = $_POST['taxon_alt'];
    $_SESSION['siCollector']   = $_POST['collector'];
    $_SESSION['siNumberC']     = $_POST['numberC'];
    $_SESSION['siDate']        = $_POST['date'];
    $_SESSION['siGeoGeneral']  = $_POST['geo_general'];
    $_SESSION['siGeoRegion']   = $_POST['geo_region'];
    $_SESSION['siCountry']     = $_POST['country'];
    $_SESSION['siProvince']    = $_POST['province'];
    $_SESSION['siLoc']         = $_POST['loc'];
    $_SESSION['siBemerkungen'] = $_POST['annotations'];

    $_SESSION['siTyp']    = (($_POST['typ']=="only") ? true : false);
    $_SESSION['siImages'] = $_POST['images'];

    $_SESSION['siOrder'] = "genus, te.epithet, ta.author, "
                         . "Sammler, Sammler_2, series, Nummer, alt_number, Datum, "
                         . "typus_lat";
    $_SESSION['siOrTyp'] = 1;
} else if (isset($_GET['order'])) {
    if ($_GET['order'] == "b") {
        $_SESSION['siOrder'] = "Sammler, Sammler_2, series, Nummer, alt_number, Datum, "
                             . "genus, te.epithet, ta.author, "
                             . "typus_lat";
        if ($_SESSION['siOrTyp'] == 2) {
            $_SESSION['siOrTyp'] = -2;
        } else {
            $_SESSION['siOrTyp'] = 2;
        }
    }
    else if ($_GET['order'] == "d") {
        $_SESSION['siOrder'] = "typus_lat, genus, te.epithet, ta.author, "
                             . "Sammler, Sammler_2, series, Nummer, alt_number, Datum";
        if ($_SESSION['siOrTyp'] == 4) {
            $_SESSION['siOrTyp'] = -4;
        } else {
            $_SESSION['siOrTyp'] = 4;
        }
    }
    else if ($_GET['order'] == "e") {
        $_SESSION['siOrder'] = "collection, HerbNummer";
        if ($_SESSION['siOrTyp'] == 5) {
            $_SESSION['siOrTyp'] = -5;
        } else {
            $_SESSION['siOrTyp'] = 5;
        }
    }
    else {
        $_SESSION['siOrder'] = "genus, te.epithet, ta.author, "
                             . "Sammler, Sammler_2, series, Nummer, alt_number, Datum, "
                             . "typus_lat";
        if ($_SESSION['siOrTyp'] == 1) {
            $_SESSION['siOrTyp'] = -1;
        } else {
            $_SESSION['siOrTyp'] = 1;
        }
    }
    if ($_SESSION['siOrTyp'] < 0) $_SESSION['siOrder'] = implode(" DESC, ", explode(", ", $_SESSION['siOrder'])) . " DESC";
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
    $location = "Location: editSpecimensImport.php?sel=<" . $_POST['specimen'] . ">";
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
  <style type="text/css">
    body { background-color: #008080 }
  </style>
  <?php $xajax->printJavascript('inc/xajax'); ?>
  <script src="js/freudLib.js" type="text/javascript"></script>
  <script src="js/parameters.php" type="text/javascript"></script>
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
    <td><input type="text" name="number" value="<?php echoSpecial('siNumber', 'SESSION'); ?>"></td>
  <td align="right">&nbsp;<b>Series:</b></td>
    <td><input type="text" name="series" value="<?php echoSpecial('siSeries', 'SESSION'); ?>"></td>
</tr><tr>
  <td align="right">&nbsp;<b>Family:</b></td>
    <td><input type="text" name="family" value="<?php echoSpecial('siFamily', 'SESSION'); ?>"></td>
  <td align="right">&nbsp;<b>Taxon:</b></td>
    <td><input type="text" name="taxon" value="<?php echoSpecial('siTaxon', 'SESSION'); ?>"></td>
  <td align="right">&nbsp;<b>ident. history</b></td>
    <td><input type="text" name="taxon_alt" value="<?php echoSpecial('siTaxonAlt', 'SESSION'); ?>"></td>
</tr><tr>
  <td align="right">&nbsp;<b>Collector:</b></td>
    <td><input type="text" name="collector" value="<?php echoSpecial('siCollector', 'SESSION'); ?>"></td>
  <td align="right">&nbsp;<b>Number:</b></td>
    <td><input type="text" name="numberC" value="<?php echoSpecial('siNumberC', 'SESSION'); ?>"></td>
  <td align="right">&nbsp;<b>Date:</b></td>
    <td><input type="text" name="date" value="<?php echoSpecial('siDate', 'SESSION'); ?>"></td>
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
            if ($_SESSION['siGeoGeneral'] == $row['geo_general']) echo " selected";
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
            if ($_SESSION['siGeoRegion'] == $row['geo_region']) echo " selected";
            echo ">" . $row['geo_region'] . "</option>\n";
        }
      ?>
      </select>
    </td>
  <td align="right">&nbsp;<b>Loc.:</b></td>
    <td><input type="text" name="loc" value="<?php echoSpecial('siLoc', 'SESSION'); ?>"></td>
</tr><tr>
  <td align="right">&nbsp;<b>Country:</b></td>
    <td><input type="text" name="country" value="<?php echoSpecial('siCountry', 'SESSION'); ?>"></td>
  <td align="right">&nbsp;<b>State/Province:</b></td>
    <td><input type="text" name="province" value="<?php echoSpecial('siProvince', 'SESSION'); ?>"></td>
  <td align="right">&nbsp;<b>Annotation:</b></td>
    <td><input type="text" name="annotations" value="<?php echoSpecial('siBemerkungen', 'SESSION'); ?>"></td>
</tr><tr>
  <td colspan="2">
    <input type="radio" name="typ" value="all"<?php if(!$_SESSION['siTyp']) echo " checked"; ?>>
    <b>All records</b>
    <input type="radio" name="typ" value="only"<?php if($_SESSION['siTyp']) echo " checked"; ?>>
    <b>Type records only</b>
  </td><td colspan="4" align="right">
    <b>Images:</b>
    <input type="radio" name="images" value="only"<?php if($_SESSION['siImages'] == 'only') echo " checked"; ?>>
    <b>Yes</b>
    <input type="radio" name="images" value="no"<?php if($_SESSION['siImages'] == 'no') echo " checked"; ?>>
    <b>No</b>
    <input type="radio" name="images" value="all"<?php if($_SESSION['siImages'] != 'only' && $_SESSION['siImages'] != 'no') echo " checked"; ?>>
    <b>All</b>
  </td>
</tr><tr>
  <td colspan="2"><input class="button" type="submit" name="search" value=" search "></td>
  <td colspan="2" align="right">
    <input class="button" type="submit" name="importNow" value="import <?php echo getImportEntries(true); ?> now"> /
    <input class="button" type="submit" name="deleteNow" value="delete <?php echo getImportEntries(false); ?> now">
  </td>
  <td colspan="2" align="right">
    <?php if (checkRight('specim')): ?>
    <input class="button" type="button" value="new entry" onClick="self.location.href='editSpecimensImport.php?sel=<0>&new=1'">
    <?php endif; ?>
  </td>
</tr>
</table>
</form>

<hr>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" name="f">
<?php
if ($_SESSION['siType'] == 1) {
    $sql = "SELECT si.specimen_ID, tg.genus, si.digital_image,
             c.Sammler, c2.Sammler_2, ss.series, si.series_number,
             si.Nummer, si.alt_number, si.Datum, si.HerbNummer,
             n.nation_engl, p.provinz, si.Fundort, mc.collectionID, mc.collection, mc.coll_short, t.typus_lat,
             si.Coord_W, si.W_Min, si.W_Sec, si.Coord_N, si.N_Min, si.N_Sec,
             si.Coord_S, si.S_Min, si.S_Sec, si.Coord_E, si.E_Min, si.E_Sec, si.ncbi_accession,
             ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
             ta4.author author4, ta5.author author5,
             te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
             te4.epithet epithet4, te5.epithet epithet5
            FROM (tbl_specimens_import si, tbl_tax_species ts, tbl_tax_genera tg, tbl_tax_families tf, tbl_management_collections mc)
             LEFT JOIN tbl_specimens_series ss ON ss.seriesID = si.seriesID
             LEFT JOIN tbl_typi t ON t.typusID = si.typusID
             LEFT JOIN tbl_geo_province p ON p.provinceID = si.provinceID
             LEFT JOIN tbl_geo_nation n ON n.NationID = si.NationID
             LEFT JOIN tbl_geo_region r ON r.regionID = n.regionID_fk
             LEFT JOIN tbl_collector c ON c.SammlerID = si.SammlerID
             LEFT JOIN tbl_collector_2 c2 ON c2.Sammler_2ID = si.Sammler_2ID
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
            WHERE ts.taxonID = si.taxonID
             AND tg.genID = ts.genID
             AND tf.familyID = tg.familyID
             AND mc.collectionID = si.collectionID
             AND userID='" . $_SESSION['uid'] . "'";
    $sql2 = "";
    if (trim($_SESSION['siTaxon'])) {
        $pieces = explode(" ", trim($_SESSION['siTaxon']));
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
    if (trim($_SESSION['siSeries'])) {
        $sql2 .= " AND ss.series LIKE '%" . mysql_escape_string(trim($_SESSION['siSeries'])) . "%'";
    }
    if (trim($_SESSION['wuCollection'])) {
        if (trim($_SESSION['wuCollection']) > 0) {
            $sql2 .= " AND si.collectionID=" . quoteString(trim($_SESSION['wuCollection']));
        } else {
            $sql2 .= " AND mc.source_id=" . quoteString(abs(trim($_SESSION['wuCollection'])));
        }
    }
    if (trim($_SESSION['siNumber'])) {
        $sql2 .= " AND si.HerbNummer LIKE '%" . mysql_escape_string(trim($_SESSION['siNumber'])) . "%'";
    }
    if (trim($_SESSION['siFamily'])) {
        $sql2 .= " AND tf.family LIKE '" . mysql_escape_string(trim($_SESSION['siFamily'])) . "%'";
    }
    if (trim($_SESSION['siCollector'])) {
        $sql2 .= " AND (c.Sammler LIKE '" . mysql_escape_string(trim($_SESSION['siCollector'])) . "%' OR
                       c2.Sammler_2 LIKE '%" . mysql_escape_string(trim($_SESSION['siCollector'])) . "%')";
    }
    if (trim($_SESSION['siNumberC'])) {
        $sql2 .= " AND (si.Nummer LIKE '" . mysql_escape_string(trim($_SESSION['siNumberC'])) . "%' OR
                        si.alt_number LIKE '%" . mysql_escape_string(trim($_SESSION['siNumberC'])) . "%' OR
                        si.series_number LIKE '" . mysql_escape_string(trim($_SESSION['siNumberC'])) . "%') ";
    }
    if (trim($_SESSION['siDate'])) {
        $sql2 .= " AND si.Datum LIKE '" . mysql_escape_string(trim($_SESSION['siDate'])) . "%'";
    }
    if (trim($_SESSION['siGeoGeneral'])) {
        $sql2 .= " AND r.geo_general LIKE '" . mysql_escape_string(trim($_SESSION['siGeoGeneral'])) . "%'";
    }
    if (trim($_SESSION['siGeoRegion'])) {
        $sql2 .= " AND r.geo_region LIKE '" . mysql_escape_string(trim($_SESSION['siGeoRegion'])) . "%'";
    }
    if (trim($_SESSION['siCountry'])) {
        $sql2 .= " AND n.nation_engl LIKE '" . mysql_escape_string(trim($_SESSION['siCountry'])) . "%'";
    }
    if (trim($_SESSION['siProvince'])) {
        $sql2 .= " AND p.provinz LIKE '" . mysql_escape_string(trim($_SESSION['siProvince'])) . "%'";
    }
    if (trim($_SESSION['siLoc'])) {
        $sql2 .= " AND si.Fundort LIKE '%" . mysql_escape_string(trim($_SESSION['siLoc'])) . "%'";
    }
    if (trim($_SESSION['siBemerkungen'])) {
        $sql2 .= " AND si.Bemerkungen LIKE '%" . mysql_escape_string(trim($_SESSION['siBemerkungen'])) . "%'";
    }
    if (trim($_SESSION['siTaxonAlt'])) {
        $sql2 .= " AND si.taxon_alt LIKE '%" . mysql_escape_string(trim($_SESSION['siTaxonAlt'])) . "%'";
    }
    if ($_SESSION['siTyp']) {
        $sql2 .= " AND si.typusID != 0";
    }
    if ($_SESSION['siImages'] == 'only') {
        $sql2 .= " AND si.digital_image != 0";
    } else if ($_SESSION['siImages'] == 'no') {
        $sql2 .= " AND si.digital_image = 0";
    }

    $sql3 = " ORDER BY " . $_SESSION['siOrder'] . " LIMIT 1001";

    $result = db_query($sql . $sql2 . " ORDER BY " . $_SESSION['siOrder'] . " LIMIT 1001");
    if (mysql_num_rows($result) > 1000) {
        echo "<b>no more than 1000 results allowed</b>\n";
    } elseif (mysql_num_rows($result) > 0) {
        echo "<table class=\"out\" cellspacing=\"0\">\n";
        echo "<tr class=\"out\">";
        echo "<th class=\"out\"></th>";
        echo "<th class=\"out\">"
           . "<a href=\"" . $_SERVER['PHP_SELF'] . "?order=a\">Taxon</a>" . sortItem($_SESSION['siOrTyp'], 1) . "</th>";
        echo "<th class=\"out\">"
           . "<a href=\"" . $_SERVER['PHP_SELF'] . "?order=b\">Collector</a>" . sortItem($_SESSION['siOrTyp'], 2) . "</th>";
        echo "<th class=\"out\">Date</th>";
        echo "<th class=\"out\">X/Y</th>";
        echo "<th class=\"out\">Location</th>";
        echo "<th class=\"out\">"
           . "<a href=\"" . $_SERVER['PHP_SELF'] . "?order=d\">Typus</a>" . sortItem($_SESSION['siOrTyp'], 4) . "</th>";
        echo "<th class=\"out\">"
           . "<a href=\"" . $_SERVER['PHP_SELF'] . "?order=e\">Coll.</a>" . sortItem($_SESSION['siOrTyp'], 5) . "</th>";
        echo "</tr>\n";
        $nr = 1;
        while ($row = mysql_fetch_array($result)) {
            $linkList[$nr] = $row['specimen_ID'];

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
               .  "<a href=\"editSpecimensImport.php?sel=".htmlentities("<".$row['specimen_ID'].">")."&nr=$nr&ptid=0\">"
               .  htmlspecialchars(taxonItem($row))."</a></td>"
               . "<td class=\"out\">".htmlspecialchars(collectorItem($row))."</td>"
               . "<td class=\"outNobreak\">".htmlspecialchars($row['Datum'])."</td>"
               . $textLatLon
               . "<td class=\"out\">".locationItem($row)."</td>"
               . "<td class=\"out\">".htmlspecialchars($row['typus_lat'])."</td>"
               . "<td class=\"outCenter\" title=\"".htmlspecialchars($row['collection'])."\">"
               .  htmlspecialchars($row['coll_short'])." ".htmlspecialchars($row['HerbNummer'])."</td>";
            echo "</tr>\n";
            $nr++;
        }
        $linkList[0] = $nr - 1;
        $_SESSION['siLinkList'] = $linkList;
        echo "</table>\n";
    } else {
        echo "<b>nothing found!</b>\n";
    }
}
?>
</form>

</body>
</html>