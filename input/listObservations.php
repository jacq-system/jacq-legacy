<?php
session_start();
require("inc/connect.php");
require("inc/herbardb_input_functions.php");
require("inc/api_functions.php");
require_once ("inc/xajax/xajax_core/xajax.inc.php");
no_magic();

$xajax = new xajax();
$xajax->setRequestURI("ajax/listObservationsServer.php");

$xajax->registerFunction("getUserDate");

if (!isset($_SESSION['obsCollection'])) $_SESSION['obsCollection'] = '';
if (!isset($_SESSION['obsTyp'])) $_SESSION['obsTyp'] = '';
if (!isset($_SESSION['obsType'])) $_SESSION['obsType'] = 0;
if (!isset($_SESSION['obsImages'])) $_SESSION['obsImages'] = '';
if (!isset($_SESSION['obsUserID'])) $_SESSION['obsUserID'] = 0;
if (!isset($_SESSION['obsLinkList'])) $_SESSION['obsLinkList'] = array();

$nrSel = isset($_GET['nr']) ? intval($_GET['nr']) : 0;
$swBatch = (checkRight('batch')) ? true : false; // nur user mit Recht "batch" können Batches hinzufügen

if (isset($_POST['search'])) {
    $_SESSION['obsType'] = 1;

    $_SESSION['obsCollection'] = $_POST['collection'];
    $_SESSION['obsNumber']     = $_POST['number'];
    $_SESSION['obsSeries']     = $_POST['series'];
    $_SESSION['obsFamily']     = $_POST['family'];
    $_SESSION['obsTaxon']      = $_POST['taxon'];
    $_SESSION['obsTaxonAlt']   = $_POST['taxon_alt'];
    $_SESSION['obsCollector']  = $_POST['collector'];
    $_SESSION['obsNumberC']    = $_POST['numberC'];
    $_SESSION['obsDate']       = $_POST['date'];
    $_SESSION['obsGeoGeneral'] = $_POST['geo_general'];
    $_SESSION['obsGeoRegion']  = $_POST['geo_region'];
    $_SESSION['obsCountry']    = $_POST['country'];
    $_SESSION['obsProvince']   = $_POST['province'];
    $_SESSION['obsLoc']        = $_POST['loc'];

    $_SESSION['obsTyp']    = (($_POST['typ'] == "only") ? true : false);
    $_SESSION['obsImages'] = (($_POST['images'] == "only") ? true : false);

    $_SESSION['obsOrder'] = "genus, te.epithet, ta.author, "
                          . "Sammler, Sammler_2, series, Nummer, alt_number, Datum, "
                          . "typus_lat";
    $_SESSION['obsOrTyp'] = 1;
} else if (isset($_POST['selectUser'])) {
    $_SESSION['obsType'] = 2;
    $_SESSION['obsCollection'] = $_SESSION['obsNumber'] = $_SESSION['obsSeries'] = $_SESSION['obsFamily'] = "";
    $_SESSION['obsTaxon'] = $_SESSION['obsTaxonAlt'] = $_SESSION['obsCollector'] = $_SESSION['obsNumberC'] = "";
    $_SESSION['obsDate'] = $_SESSION['obsCountry'] = $_SESSION['obsProvince'] = $_SESSION['obsLoc'] = "";
    $_SESSION['obsTyp'] = $_SESSION['obsImages'] = $_SESSION['obsGeoGeneral'] = $_SESSION['obsGeoRegion'] = "";

    $_SESSION['obsUserID'] = $_POST['userID'];
    $_SESSION['obsUserDate'] = $_POST['user_date'];
} else if (isset($_GET['order'])) {
    if ($_GET['order'] == "b") {
        $_SESSION['obsOrder'] = "Sammler, Sammler_2, series, Nummer, alt_number, Datum, "
                              . "genus, te.epithet, ta.author, "
                              . "typus_lat";
        if ($_SESSION['obsOrTyp'] == 2) {
            $_SESSION['obsOrTyp'] = -2;
        } else {
            $_SESSION['obsOrTyp'] = 2;
        }
    }
    else if ($_GET['order'] == "d") {
        $_SESSION['obsOrder'] = "typus_lat, genus, te.epithet, ta.author, "
                              . "Sammler, Sammler_2, series, Nummer, alt_number, Datum";
        if ($_SESSION['obsOrTyp'] == 4) {
            $_SESSION['obsOrTyp'] = -4;
        } else {
            $_SESSION['obsOrTyp'] = 4;
        }
    }
    else if ($_GET['order'] == "e") {
        $_SESSION['obsOrder'] = "collection, HerbNummer";
        if ($_SESSION['obsOrTyp'] == 5) {
            $_SESSION['obsOrTyp'] = -5;
        } else {
            $_SESSION['obsOrTyp'] = 5;
        }
    }
    else {
        $_SESSION['obsOrder'] = "genus, te.epithet, ta.author, "
                              . "Sammler, Sammler_2, series, Nummer, alt_number, Datum, "
                              . "typus_lat";
        if ($_SESSION['obsOrTyp'] == 1) {
            $_SESSION['obsOrTyp'] = -1;
        } else {
            $_SESSION['obsOrTyp'] = 1;
        }
    }
    if ($_SESSION['obsOrTyp'] < 0) $_SESSION['obsOrder'] = implode(" DESC, ", explode(", ", $_SESSION['obsOrder'])) . " DESC";
}

function makeDropdownCollection()
{
    $sql =  "SELECT collectionID, collection FROM tbl_management_collections ORDER BY collection";
    $result = db_query($sql);
    echo "<select size=\"1\" name=\"collection\">\n";
    echo "  <option value=\"0\"></option>\n";
    while ($row = mysql_fetch_array($result)) {
        echo "  <option value=\"" . htmlspecialchars($row['collectionID']) . "\"";
        if ($_SESSION['obsCollection'] == $row['collectionID']) echo " selected";
        echo ">" . htmlspecialchars($row['collection']) . "</option>\n";
    }
    echo "  </select>\n";
}

function makeDropdownUsername()
{
    $sql = "SELECT hu.userID, hu.firstname, hu.surname, hu.username
            FROM herbarinput_log.tbl_herbardb_users hu, herbarinput_log.log_specimens ls
            WHERE hu.userID = ls.userID
            GROUP BY hu.userID
            ORDER BY surname, firstname, username";
    $result = db_query($sql);
    echo "<select size=\"1\" name=\"userID\" onchange=\"xajax_getUserDate(document.fm2.userID.options[document.fm2.userID.selectedIndex].value)\">\n";
    echo "  <option value=\"0\"></option>";
    while ($row = mysql_fetch_array($result)) {
        echo "  <option value=\"" . htmlspecialchars($row['userID']) . "\"";
        if ($_SESSION['obsUserID'] == $row['userID']) echo " selected";
        echo ">";
        if (trim($row['firstname']) || trim($row['surname']))
            echo htmlspecialchars($row['firstname']) . " " . htmlspecialchars($row['surname']);
        else
            echo htmlspecialchars("<" . $row['username'] . ">");
        echo "</option>\n";
    }
    echo "  </select>\n";
}

function makeDropdownDate()
{
    $sql = "SELECT DATE_FORMAT(timestamp,'%Y-%m-%d') as date
            FROM herbarinput_log.log_specimens ";
    if (intval($_SESSION['obsUserID'])) $sql .= "WHERE userID = '" . intval($_SESSION['obsUserID']) . "' ";
    $sql .= "GROUP BY date
             ORDER BY date";
    $result = db_query($sql);
    echo "<select size=\"1\" name=\"user_date\" id=\"user_date\">\n";
    while ($row = mysql_fetch_array($result)) {
        echo "  <option ";
        if ($_SESSION['obsDate'] == $row['date']) echo " selected";
        echo ">" . htmlspecialchars($row['date']) . "</option>\n";
    }
    echo "  </select>\n";
}

function collectorItem($row)
{
    $text = $row['Sammler'];
    if (strstr($row['Sammler_2'],"&") || strstr($row['Sammler_2'], "et al.")) {
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
    } else{
        return($coll);
    }
}


if (!empty($_POST['select']) && !empty($_POST['specimen'])) {
    $location = "Location: editObservations.php?sel=<" . $_POST['specimen'] . ">";
    if (SID) $location .= "&" . SID;
    Header($location);
    die();
}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - list Observations</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <?php $xajax->printJavascript('inc/xajax'); ?>
  <script type="text/javascript" language="JavaScript">
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
  </script>
</head>

<body>

<input class="button" type="button" value=" close window " onclick="self.close()" id="close">

<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" name="fm1">
<table cellspacing="5" cellpadding="0">
<tr>
  <td align="right">&nbsp;<b>Collection:</b></td>
    <td><?php makeDropdownCollection(); ?></td>
  <td align="right">&nbsp;<b>Collection Nr.:</b></td>
    <td><input type="text" name="number" value="<?php echoSpecial('obsNumber', 'SESSION'); ?>"></td>
  <td align="right">&nbsp;<b>Series:</b></td>
    <td><input type="text" name="series" value="<?php echoSpecial('obsSeries', 'SESSION'); ?>"></td>
</tr><tr>
  <td align="right">&nbsp;<b>Family:</b></td>
    <td><input type="text" name="family" value="<?php echoSpecial('obsFamily', 'SESSION'); ?>"></td>
  <td align="right">&nbsp;<b>Taxon:</b></td>
    <td><input type="text" name="taxon" value="<?php echoSpecial('obsTaxon', 'SESSION'); ?>"></td>
  <td align="right">&nbsp;<b>ident. history</b></td>
    <td><input type="text" name="taxon_alt" value="<?php echoSpecial('obsTaxonAlt', 'SESSION'); ?>"></td>
</tr><tr>
  <td align="right">&nbsp;<b>Collector:</b></td>
    <td><input type="text" name="collector" value="<?php echoSpecial('obsCollector', 'SESSION'); ?>"></td>
  <td align="right">&nbsp;<b>Number:</b></td>
    <td><input type="text" name="numberC" value="<?php echoSpecial('obsNumberC', 'SESSION'); ?>"></td>
  <td align="right">&nbsp;<b>Date:</b></td>
    <td><input type="text" name="date" value="<?php echoSpecial('obsDate', 'SESSION'); ?>"></td>
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
            if ($_SESSION['obsGeoGeneral'] == $row['geo_general']) echo " selected";
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
            if ($_SESSION['obsGeoRegion'] == $row['geo_region']) echo " selected";
            echo ">" . $row['geo_region'] . "</option>\n";
        }
      ?>
      </select>
    </td>
  <td colspan="2"></td>
</tr><tr>
  <td align="right">&nbsp;<b>Country:</b></td>
    <td><input type="text" name="country" value="<?php echoSpecial('obsCountry', 'SESSION'); ?>"></td>
  <td align="right">&nbsp;<b>State/Province:</b></td>
    <td><input type="text" name="province" value="<?php echoSpecial('obsProvince', 'SESSION'); ?>"></td>
  <td align="right">&nbsp;<b>Loc.:</b></td>
    <td><input type="text" name="loc" value="<?php echoSpecial('obsLoc', 'SESSION'); ?>"></td>
</tr><tr>
  <td colspan="2">
    <input type="radio" name="typ" value="all"<?php if(!$_SESSION['obsTyp']) echo " checked"; ?>>
    <b>All records</b>
    <input type="radio" name="typ" value="only"<?php if($_SESSION['obsTyp']) echo " checked"; ?>>
    <b>Type records only</b>
  </td><td colspan="4" align="right">
    <b>Display only records containing images:</b>
    <input type="radio" name="images" value="only"<?php if($_SESSION['obsImages']) echo " checked"; ?>>
    <b>Yes</b>
    <input type="radio" name="images" value="all"<?php if(!$_SESSION['obsImages']) echo " checked"; ?>>
    <b>No</b>
  </td>
</tr>
</table>
<input class="button" type="submit" name="search" value=" search ">
</form>

<p>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" name="fm2">
<table cellspacing="0" cellpadding="0"><tr>
<?php if (($_SESSION['editControl'] & 0x2000)!=0): ?>
<td>
  <input class="button" type="button" value="new entry" onClick="self.location.href='editObservations.php?sel=<0>&new=1'">
</td><td style="width: 3em">&nbsp;</td>
<?php endif; ?>
<td>
  <b>SpecimenID:</b> <input type="text" name="specimen" value="<?php echoSpecial('specimen', 'POST'); ?>">
  <input class="button" type="submit" name="select" value=" Edit ">
</td>
<?php if ($_SESSION['editorControl']):    // only editors may check logged in users ?>
<td style="width: 3em">&nbsp;</td><td>
&nbsp;<b>User:</b>&nbsp;&nbsp;<?php makeDropdownUsername(); ?>
&nbsp;<b>Date:</b>&nbsp;&nbsp;<?php makeDropdownDate(); ?>
&nbsp;&nbsp;<input class="button" type="submit" name="selectUser" value=" search ">
</td>
<?php endif; ?>
</tr></table>
</form>
<p>

<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" name="f">
<?php
$error = false;
if ($_SESSION['obsType']==1) {
    if ($swBatch) {
        $batchValue = array();
        $batchText = array();
        $sql = "SELECT remarks, date_supplied, batchID
                FROM api.tbl_api_batches
                WHERE sent = '0'
                ORDER BY date_supplied DESC";
        $result = db_query($sql);
        while ($row=mysql_fetch_array($result)) {
            $batchValue[] = $row['batchID'];
            $batchText[] = $row['date_supplied'] . " (" . trim($row['remarks']) . ")";
        }
        if (isset($_POST['selectBatch'])) {
            $batch_id = intval($_POST['batch']);
            $idList = array();
            foreach ($_POST as $key => $value) {
                if (substr($key,0,11) == "batch_spec_" && $value) {
                    $id = substr($key, 11);
                    $sql = "INSERT INTO api.tbl_api_specimens SET
                            specimen_ID = '$id',
                            batchID_fk = '$batch_id'";
                    db_query($sql);
                    // update or insert into update_tbl_api_units
                    $res = update_tbl_api_units($id);
                    update_tbl_api_units_identifications($id);
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

    $sql = "SELECT wg.specimen_ID, tg.genus, wg.digital_image,
             c.Sammler, c2.Sammler_2, ss.series, wg.series_number,
             wg.Nummer, wg.alt_number, wg.Datum, wg.HerbNummer,
             n.nation_engl, p.provinz, wg.Fundort, mc.collectionID, mc.collection, mc.coll_short, t.typus_lat,
             wg.Coord_W, wg.W_Min, wg.W_Sec, wg.Coord_N, wg.N_Min, wg.N_Sec,
             wg.Coord_S, wg.S_Min, wg.S_Sec, wg.Coord_E, wg.E_Min, wg.E_Sec, wg.ncbi_accession,
             ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
             ta4.author author4, ta5.author author5,
             te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
             te4.epithet epithet4, te5.epithet epithet5
            FROM (tbl_specimens wg, tbl_tax_species ts, tbl_tax_genera tg, tbl_tax_families tf,
             tbl_management_collections mc)
             LEFT JOIN tbl_specimens_series ss ON ss.seriesID = wg.seriesID
             LEFT JOIN tbl_typi t ON t.typusID = wg.typusID
             LEFT JOIN tbl_geo_province p ON p.provinceID = wg.provinceID
             LEFT JOIN tbl_geo_nation n ON n.NationID = wg.NationID
             LEFT JOIN tbl_geo_region r ON r.regionID = n.regionID_fk
             LEFT JOIN tbl_collector c ON c.SammlerID = wg.SammlerID
             LEFT JOIN tbl_collector_2 c2 ON c2.Sammler_2ID = wg.Sammler_2ID
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
            WHERE ts.taxonID = wg.taxonID
             AND tg.genID = ts.genID
             AND tf.familyID = tg.familyID
             AND mc.collectionID = wg.collectionID";
    if (trim($_SESSION['obsTaxon'])) {
        $pieces = explode(" ", trim($_SESSION['obsTaxon']));
        $part1 = array_shift($pieces);
        $part2 = array_shift($pieces);
        $sql .= " AND tg.genus LIKE '" . mysql_escape_string($part1) . "%'";
        if ($part2) {
            $sql .= " AND (te.epithet LIKE '" . mysql_escape_string($part2) . "%' "
                  .   "OR te1.epithet LIKE '" . mysql_escape_string($part2) . "%' "
                  .   "OR te2.epithet LIKE '" . mysql_escape_string($part2) . "%' "
                  .   "OR te3.epithet LIKE '" . mysql_escape_string($part2) . "%')";
        }
    }
    if (trim($_SESSION['obsSeries'])) {
        $sql .= " AND ss.series LIKE '%" . mysql_escape_string(trim($_SESSION['obsSeries'])) . "%'";
    }
    if (trim($_SESSION['obsCollection'])) {
        $sql .= " AND wg.collectionID=" . quoteString(trim($_SESSION['obsCollection']));
    }
    if (trim($_SESSION['obsNumber'])) {
        $sql .= " AND wg.HerbNummer LIKE '%" . mysql_escape_string(trim($_SESSION['obsNumber'])) . "%'";
    }
    if (trim($_SESSION['obsFamily'])) {
        $sql .= " AND tf.family LIKE '" . mysql_escape_string(trim($_SESSION['obsFamily'])) . "%'";
    }
    if (trim($_SESSION['obsCollector'])) {
        $sql .= " AND (c.Sammler LIKE '" . mysql_escape_string(trim($_SESSION['obsCollector'])) . "%' OR
                       c2.Sammler_2 LIKE '%" . mysql_escape_string(trim($_SESSION['obsCollector'])) . "%')";
    }
    if (trim($_SESSION['obsNumberC'])) {
        $sql .= " AND wg.Nummer LIKE '" . mysql_escape_string(trim($_SESSION['obsNumberC'])) . "%'";
    }
    if (trim($_SESSION['obsDate'])) {
        $sql .= " AND wg.Datum LIKE '" . mysql_escape_string(trim($_SESSION['obsDate'])) . "%'";
    }
    if (trim($_SESSION['obsGeoGeneral'])) {
        $sql .= " AND r.geo_general LIKE '" . mysql_escape_string(trim($_SESSION['obsGeoGeneral'])) . "%'";
    }
    if (trim($_SESSION['obsGeoRegion'])) {
        $sql .= " AND r.geo_region LIKE '" . mysql_escape_string(trim($_SESSION['obsGeoRegion'])) . "%'";
    }
    if (trim($_SESSION['obsCountry'])) {
        $sql .= " AND n.nation_engl LIKE '" . mysql_escape_string(trim($_SESSION['obsCountry'])) . "%'";
    }
    if (trim($_SESSION['obsProvince'])) {
        $sql .= " AND p.provinz LIKE '" . mysql_escape_string(trim($_SESSION['obsProvince'])) . "%'";
    }
    if (trim($_SESSION['obsLoc'])) {
        $sql .= " AND wg.Fundort LIKE '%" . mysql_escape_string(trim($_SESSION['obsLoc'])) . "%'";
    }
    if (trim($_SESSION['obsTaxonAlt'])) {
        $sql .= " AND wg.taxon_alt LIKE '%" . mysql_escape_string(trim($_SESSION['obsTaxonAlt'])) . "%'";
    }
    if ($_SESSION['obsTyp']) {
        $sql .= " AND wg.typusID != 0";
    }
    if ($_SESSION['obsImages']) {
        $sql .= " AND wg.digital_image != 0";
    }

    $sql .= " ORDER BY " . $_SESSION['obsOrder'];

    $result = db_query($sql);
    if (mysql_num_rows($result) > 0) {
        echo "<table class=\"out\" cellspacing=\"0\">\n";
        echo "<tr class=\"out\">";
        echo "<th class=\"out\"></th>";
        echo "<th class=\"out\">"
           . "<a href=\"" . $_SERVER['PHP_SELF'] . "?order=a\">Taxon</a>" . sortItem($_SESSION['obsOrTyp'], 1) . "</th>";
        echo "<th class=\"out\">"
           . "<a href=\"" . $_SERVER['PHP_SELF'] . "?order=b\">Collector</a>" . sortItem($_SESSION['obsOrTyp'], 2) . "</th>";
        echo "<th class=\"out\">Date</th>";
        echo "<th class=\"out\">X/Y</th>";
        echo "<th class=\"out\">Location</th>";
        echo "<th class=\"out\">"
           . "<a href=\"" . $_SERVER['PHP_SELF'] . "?order=d\">Typus</a>" . sortItem($_SESSION['obsOrTyp'], 4) . "</th>";
        echo "<th class=\"out\">"
           . "<a href=\"" . $_SERVER['PHP_SELF'] . "?order=e\">Coll.</a>" . sortItem($_SESSION['obsOrTyp'], 5) . "</th>";
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
                $textLatLon = "<td class=\"out\" style=\"text-align: center\" title=\"" . round($lat, 2) . "&deg; / " . round($lon, 2) . "&deg;\">".
                               "<a href=\"http://www.mapquest.com/maps/map.adp?latlongtype=decimal&longitude=$lon&latitude=$lat&zoom=3\" ".
                                "target=\"_blank\"><img border=\"0\" height=\"15\" src=\"webimages/mapquest.png\" width=\"15\">".
                               "</a>".
                              "</td>";
            } else {
                $textLatLon = "<td class=\"out\"></td>";
            }

            echo "<tr class=\"" . (($nrSel == $nr) ? "outMark" : "out") . "\">"
               . "<td class=\"out\">$digitalImage</td>"
               . "<td class=\"out\">"
               .  "<a href=\"editObservations.php?sel=" . htmlentities("<" . $row['specimen_ID'] . ">") . "&nr=$nr\">"
               .  htmlspecialchars(taxonItem($row)) . "</a></td>"
               . "<td class=\"out\">" . htmlspecialchars(collectorItem($row)) . "</td>"
               . "<td class=\"outNobreak\">" . htmlspecialchars($row['Datum']) . "</td>"
               . $textLatLon
               . "<td class=\"out\">" . htmlspecialchars(locationItem($row)) . "</td>"
               . "<td class=\"out\">" . htmlspecialchars($row['typus_lat']) . "</td>"
               . "<td class=\"outCenter\" title=\"" . htmlspecialchars($row['collection']) . "\">"
               .  htmlspecialchars($row['coll_short']) . " " . htmlspecialchars($row['HerbNummer']) . "</td>";
            if ($swBatch) {
                echo "<td class=\"out\" style=\"text-align: center\">";
                $resultDummy = db_query("SELECT batchID_fk FROM api.tbl_api_specimens WHERE specimen_ID = '" . $row['specimen_ID'] . "'");
                if (mysql_num_rows($resultDummy) > 0) {
                    echo "&radic;";
                } else {
                    echo "<input type=\"checkbox\" name=\"batch_spec_" . $row['specimen_ID'] . "\">";
                }
                echo "</td>";
            }
            echo "</tr>\n";
            $nr++;
        }
        $linkList[0] = $nr - 1;
        $_SESSION['obsLinkList'] = $linkList;
        echo "</table>\n";
    } else {
        echo "<b>nothing found!</b>\n";
    }
} else if ($_SESSION['obsType'] == 2) {
    $searchDate = mysql_escape_string(trim($_SESSION['obsUserDate']));
    $sql = "SELECT *
            FROM herbarinput_log.log_specimens
            WHERE userID = '" . mysql_escape_string($_SESSION['obsUserID']) . "'
             AND timestamp BETWEEN '$searchDate' AND ADDDATE('$searchDate','1')
            ORDER BY timestamp";
    $result = db_query($sql);
    if (mysql_num_rows($result) > 0) {
        echo "<table class=\"out\" cellspacing=\"0\">\n";
        echo "<tr class=\"out\">";
        echo "<th class=\"out\">Timestamp</th>";
        echo "<th class=\"out\">specimenID</th>";
        echo "<th class=\"out\">updated</th>";
        echo "</tr>\n";
        $nr = 1;
        while ($row = mysql_fetch_array($result)) {
            $linkList[$nr] = $row['specimenID'];
            echo "<tr class=\"" . (($nrSel == $nr) ? "outMark" : "out") . "\">"
               . "<td class=\"out\">" . htmlspecialchars($row['timestamp']) . "</td>"
               . "<td class=\"out\">"
               . "<a href=\"editObservations.php?sel=" . htmlentities("<" . $row['specimenID'] . ">") . "&nr=$nr\">"
               . htmlspecialchars($row['specimenID']) . "</a></td>"
               . "<td class=\"out\">" . (($row['updated']) ? "updated" : "") . "</td>"
               . "</tr>\n";
            $nr++;
        }
        $linkList[0] = $nr - 1;
        $_SESSION['obsLinkList'] = $linkList;
        echo "</table>\n";
    } else {
        echo "<b>nothing found!</b>\n";
    }
}
?>

<?php
if ($error) {
    echo "<script type=\"text/javascript\" language=\"JavaScript\">\n"
       . "  alert('Update/Insert of the following specimenIDs blocked:\\n" . implode(", ", $idList) . "');\n"
       . "</script>\n";
}
?>
</form>

</body>
</html>