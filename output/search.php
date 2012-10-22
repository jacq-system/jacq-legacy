<?php
session_start();
require("inc/functions.php");
require_once ("inc/xajax/xajax.inc.php");
$xajax = new xajax("ajax/searchServer.php");
$xajax->registerFunction("getCollection");
$xajax->registerFunction("getCountry");
$xajax->registerFunction("getProvince");

$family      = $_GET['family'];
$taxon       = $_GET['taxon'];
$HerbNummer  = $_GET['HerbNummer'];
$Sammler     = $_GET['Sammler'];
$geo_general = $_GET['geo_general'];
$geo_region  = $_GET['geo_region'];
$nation_engl = $_GET['nation_engl'];
$provinz     = $_GET['provinz'];
$fundort     = $_GET['Fundort'];
$source_name = $_GET['source_name'];
$collection  = $_GET['collection'];
$taxon_alt   = $_GET['taxon_alt'];

if ($_POST['family'])      $family      = $_POST['family'];
if ($_POST['taxon'])       $taxon       = $_POST['taxon'];
if ($_POST['HerbNummer'])  $HerbNummer  = $_POST['HerbNummer'];
if ($_POST['Sammler'])     $Sammler     = $_POST['Sammler'];
if ($_POST['SammlerNr'])   $SammlerNr   = $_POST['SammlerNr'];
if ($_POST['geo_general']) $geo_general = $_POST['geo_general'];
if ($_POST['geo_region'])  $geo_region  = $_POST['geo_region'];
if ($_POST['nation_engl']) $nation_engl = $_POST['nation_engl'];
if ($_POST['provinz'])     $provinz     = $_POST['provinz'];
if ($_POST['Fundort'])     $fundort     = $_POST['Fundort'];
if ($_POST['source_name']) $source_name = $_POST['source_name'];
if ($_POST['collection'])  $collection  = $_POST['collection'];
if ($_POST['taxon_alt'])   $taxon_alt   = $_POST['taxon_alt'];


$sql = "SELECT DATE_FORMAT(Eingabedatum,'%Y-%m-%d') AS date FROM tbl_specimens ORDER BY DATE DESC";
$row = mysql_fetch_array(mysql_query($sql));
$lastUpdate = $row['date'];

if ($_POST['submit'] || !empty($_GET['search'])) {
    if (!empty($_GET['search'])) {
        $_POST['taxon'] = (!empty($_GET['taxon'])) ? $_GET['taxon'] : '';

        if (!empty($_GET['source_id']))  $_POST['source_id']  = $_GET['source_id'];
        if (!empty($_GET['collector']))    $_POST['Sammler']    = $_GET['collector'];
        if (!empty($_GET['SammlerNr']))  $_POST['SammlerNr']  = $_GET['SammlerNr'];
        if (!empty($_GET['family']))     $_POST['family']     = $_GET['family'];
        if (!empty($_GET['HerbNummer'])) $_POST['HerbNummer'] = $_GET['HerbNummer'];
        if (!empty($_GET['synonym']))    $_POST['synonym']    = $_GET['synonym'];
        if (!empty($_GET['obs']))        $_POST['obs']        = $_GET['obs'];
        if (!empty($_GET['country']))    $_POST['nation_engl']= $_GET['country'];
        if (!empty($_GET['province']))   $_POST['provinz']    = $_GET['province'];
        if (!empty($_GET['location']))   $_POST['Fundort']    = $_GET['location'];
        if (!empty($_GET['collection'])) $_POST['collection'] = $_GET['collection'];
        if (!empty($_GET['source_name']))$_POST['source_name']= $_GET['source_name'];
        if (!empty($_GET['taxon_alt']))  $_POST['taxon_alt']  = $_GET['taxon_alt'];
    }
/*
Für die Webabfrage brauchen wir nur(!!) die folgenden Tabellen:
tbl_collector
tbl_collector_2
tbl_management_collections
tbl_nation
tbl_province
tbl_tax_authors
tbl_tax_epithets
tbl_tax_families
tbl_tax_genera
tbl_tax_species
tbl_tax_status
tbl_tax_systematic_categories
tbl_typi
tbl_wu_generale
*/

    /*$s_query = "SELECT wg.specimen_ID, tg.genus, c.Sammler, c2.Sammler_2, wg.Series, ".
                "wg.Nummer, wg.alt_number, wg.Datum, ".
                "n.nation_engl, p.provinz, wg.Fundort, wg.collectionID, t.typus_lat, ".
                "ta.author, ta1.author author1, ta2.author author2, ta3.author author3, ".
                "te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3 ".
               "FROM tbl_wu_generale wg ".
               "LEFT JOIN tbl_management_collections mc ON mc.collectionID=wg.collectionID ".
               "LEFT JOIN tbl_typi t ON t.typusID=wg.typusID ".
               "LEFT JOIN tbl_province p ON p.provinceID=wg.provinceID ".
               "LEFT JOIN tbl_nation n ON n.NationID=wg.NationID ".
               "LEFT JOIN tbl_collector c ON c.SammlerID=wg.SammlerID ".
               "LEFT JOIN tbl_collector_2 c2 ON c2.Sammler_2ID=wg.Sammler_2ID ".
               "LEFT JOIN tbl_tax_species ts ON ts.taxonID=wg.taxonID ".
               "LEFT JOIN tbl_tax_authors ta ON ta.authorID=ts.authorID ".
               "LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID=ts.subspecies_authorID ".
               "LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID=ts.variety_authorID ".
               "LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID=ts.forma_authorID ".
               "LEFT JOIN tbl_tax_epithets te ON te.epithetID=ts.speciesID ".
               "LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID=ts.subspeciesID ".
               "LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID=ts.varietyID ".
               "LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID=ts.formaID ".
               "LEFT JOIN tbl_tax_genera tg ON tg.genID=ts.genID ".
               "LEFT JOIN tbl_tax_families tf ON tf.familyID=tg.familyID ";
    $starter = "WHERE ";
    while (list($var, $value) = each($HTTP_POST_VARS)) {
        // echo "$var = $value<br>\n";
        if ($value != "" && $var != "submit" && $var != "PHPSESSID") {
            if ($var != "type" && $var != "images") {
                if ($var=="taxon") {
                    $pieces = explode(" ",$value);
                    $part1 = array_shift($pieces);
                    $part2 = array_shift($pieces);
                    $s_query .= $starter."genus LIKE '$part1%' ";
                    if ($part2) {
                        $s_query .= "AND (te.epithet LIKE '$part2%' ";
                        $s_query .= "OR te1.epithet LIKE '$part2%' ";
                        $s_query .= "OR te2.epithet LIKE '$part2%' ";
                        $s_query .= "OR te3.epithet LIKE '$part2%') ";
                    }
                }
                else
                    $s_query .= $starter.$var." LIKE '$value%' ";
                $starter = "AND ";
            }
            elseif ($var == "type" && $value == "only") {
                $s_query .= $starter."wg.typusID != 0 ";
                $starter = "AND ";
            }
            elseif ($var == "images" && $value == "only") {
                $s_query .= $starter."wg.digital_image = 1 ";
                $starter = "AND ";
            }
        }
    } */
    $sql_names = "s.specimen_ID, tg.genus, s.digital_image, s.digital_image_obs, s.observation,
                  c.Sammler, c2.Sammler_2, ss.series, s.series_number,
                  s.Nummer, s.alt_number, s.Datum, mc.collection, tid.imgserver_IP, s.HerbNummer,
                  n.nation_engl, n.iso_alpha_2_code, p.provinz, s.collectionID, tst.typusID, t.typus,
                  s.Coord_W, s.W_Min, s.W_Sec, s.Coord_N, s.N_Min, s.N_Sec,
                  s.Coord_S, s.S_Min, s.S_Sec, s.Coord_E, s.E_Min, s.E_Sec, s.ncbi_accession,
                  ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
                  ta4.author author4, ta5.author author5,
                  te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
                  te4.epithet epithet4, te5.epithet epithet5,
                  ts.taxonID, ts.statusID ";
    $sql_tables = "FROM (tbl_specimens s, tbl_tax_species ts, tbl_tax_genera tg, tbl_tax_families tf, tbl_management_collections mc, tbl_img_definition tid, meta m)
                    LEFT JOIN tbl_specimens_types tst ON tst.specimenID = s.specimen_ID
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
                    LEFT JOIN tbl_tax_species ts2 ON ts2.taxonID = tst.taxonID
                   WHERE ts.taxonID = s.taxonID
                    AND tg.genID = ts.genID
                    AND tf.familyID = tg.familyID
                    AND mc.collectionID = s.collectionID
                    AND tid.source_id_fk = mc.source_id
                    AND mc.source_ID = m.source_ID
                    AND s.accessible != '0' ";
    $sql_restrict_specimen = $sql_restrict_species = "";
    while (list($var, $value) = each($_POST)) {
        // echo "$var = $value<br>\n";
        if ($value != "" && $var != "submit" && $var != "PHPSESSID") {
            if ($var != "type" && $var != "images" && $var != "synonym") {
                if ($var == "taxon") {
                    $pieces = explode(" ",$value);
                    $part1 = array_shift($pieces);
                    $part2 = array_shift($pieces);
                    $sql_restrict_species = "AND tg.genus LIKE '$part1%' ";
                    if ($part2) {
                        $sql_restrict_species .= "AND (     te.epithet LIKE '$part2%'
                                                        OR te1.epithet LIKE '$part2%'
                                                        OR te2.epithet LIKE '$part2%'
                                                        OR te3.epithet LIKE '$part2%'
                                                        OR te4.epithet LIKE '$part2%'
                                                        OR te5.epithet LIKE '$part2%') ";
                    }
                } elseif ($var == "family") {
                    $sql_restrict_species .= "AND (tf.family LIKE '$value%' OR tf.family_alt LIKE '$value%') ";
                } elseif ($var == "series" || $var == "taxon_alt") {
                    $sql_restrict_specimen .= "AND " . $var . " LIKE '%$value%' ";
                } elseif ($var == "collection" || $var == "source_name" || $var == "HerbNummber" || $var == "CollNummer") {
                    $sql_restrict_specimen .= "AND " . $var . " = '$value' ";
                } elseif ($var == "SammlerNr") {
                    $sql_restrict_specimen .= "AND (s.Nummer='$value' OR s.alt_number LIKE '%$value%' OR s.series_number LIKE '%$value%') ";
                } elseif ($var == "Sammler") {
                    $sql_restrict_specimen .= "AND (Sammler LIKE '$value%' OR Sammler_2 LIKE '%$value%') ";
                } elseif ($var == "Fundort") {
                    $sql_restrict_specimen .= "AND (Fundort LIKE '%$value%' OR Fundort_engl LIKE '%$value%') ";
                } elseif ($var == "nation_engl") {
                    $sql_restrict_specimen .= "AND (nation_engl LIKE '$value%' OR nation LIKE '$value%'"
                                            . "     OR (language_variants LIKE '%$value%' AND language_variants NOT LIKE '%(%$value%)%')) ";
                } elseif ($var == "provinz") {
                    $sql_restrict_specimen .= "AND (provinz LIKE '$value%' OR provinz_local LIKE '$value%') ";
                } else {
                    $sql_restrict_specimen .= "AND " . $var . " LIKE '$value%' ";
                }
            } elseif ($var == "type" && $value == "only") {
                $sql_restrict_specimen .= "AND tst.typusID IS NOT NULL ";
            } elseif ($var == "images" && $value == "only") {
                $sql_restrict_specimen .= "AND s.digital_image = 1 ";
            }
        }
    }

    $str_sub_taxonID = $str_sub_basID = $str_sub_synID = '';
    if (!empty($sql_restrict_species)) {
        $sql_sub = "SELECT ts.taxonID, ts.basID, ts.synID
                    FROM tbl_tax_genera tg, tbl_tax_families tf, tbl_tax_species ts
                     LEFT JOIN tbl_tax_epithets te ON te.epithetID = ts.speciesID
                     LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID = ts.subspeciesID
                     LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID = ts.varietyID
                     LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID = ts.subvarietyID
                     LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID = ts.formaID
                     LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID = ts.subformaID
                    WHERE tg.genID = ts.genID
                     AND tf.familyID = tg.familyID " . $sql_restrict_species;
        $res_sub = mysql_query($sql_sub);
        $array_sub_taxonID = array();
        $array_sub_basID   = array();
        $array_sub_synID   = array();
        while ($row_sub = mysql_fetch_array($res_sub)) {
            if ($row_sub['taxonID']) $array_sub_taxonID[] = $row_sub['taxonID'];
            if ($row_sub['basID'])   $array_sub_basID[]   = $row_sub['basID'];
            if ($row_sub['synID'])   $array_sub_synID[]   = $row_sub['synID'];
        }
        if (!empty($array_sub_taxonID)) $str_sub_taxonID = implode(", ", array_unique($array_sub_taxonID));
        if (!empty($array_sub_basID))   $str_sub_basID   = implode(", ", array_unique($array_sub_basID));
        if (!empty($array_sub_synID))   $str_sub_synID   = implode(", ", array_unique($array_sub_synID));
    }

    if (!$_POST['synonym'] || strpos(trim($_POST['taxon']), " ") === false) {
        if (!empty($str_sub_taxonID)) {
            $_SESSION['s_query'] = "( SELECT SQL_CALC_FOUND_ROWS " . $sql_names . $sql_tables . $sql_restrict_specimen . "
                                       AND ts.taxonID IN ($str_sub_taxonID))
                                    UNION
                                    ( SELECT " . $sql_names . $sql_tables . $sql_restrict_specimen . "
                                       AND ts2.taxonID IN ($str_sub_taxonID)) ";
        } else {
            $_SESSION['s_query'] = "SELECT SQL_CALC_FOUND_ROWS " . $sql_names . $sql_tables . $sql_restrict_specimen . $sql_restrict_species . "
                                    GROUP BY specimen_ID ";
        }
    } else {
        if (!empty($str_sub_taxonID) || !empty($str_sub_basID) || !empty($str_sub_synID)) {
            $_SESSION['s_query'] = "( SELECT SQL_CALC_FOUND_ROWS " . $sql_names . $sql_tables . trim($sql_restrict_specimen . $sql_restrict_species) . ")
                                    UNION
                                    ( SELECT " . $sql_names . $sql_tables . $sql_restrict_specimen . "
                                       AND (0";
            if (!empty($str_sub_taxonID)) {
                $_SESSION['s_query'] .= " OR ts.taxonID IN ($str_sub_taxonID)
                                          OR ts.basID IN ($str_sub_taxonID)
                                          OR ts.synID IN ($str_sub_taxonID)";
            }
            if (!empty($str_sub_basID)) {
                $_SESSION['s_query'] .= " OR ts.taxonID IN ($str_sub_basID)
                                          OR ts.basID IN ($str_sub_basID)
                                          OR ts.synID IN ($str_sub_basID)";
            }
            if (!empty($str_sub_synID)) {
                $_SESSION['s_query'] .= " OR ts.taxonID IN ($str_sub_synID)
                                          OR ts.basID IN ($str_sub_synID)
                                          OR ts.synID IN ($str_sub_synID)";
            }
            $_SESSION['s_query'] .= "))
                                     UNION
                                     ( SELECT " . $sql_names . $sql_tables . $sql_restrict_specimen . "
                                        AND (0";
            if (!empty($str_sub_taxonID)) {
                $_SESSION['s_query'] .= " OR ts2.taxonID IN ($str_sub_taxonID)
                                          OR ts2.basID IN ($str_sub_taxonID)
                                          OR ts2.synID IN ($str_sub_taxonID)";
            }
            if (!empty($str_sub_basID)) {
                $_SESSION['s_query'] .= " OR ts2.taxonID IN ($str_sub_basID)
                                          OR ts2.basID IN ($str_sub_basID)
                                          OR ts2.synID IN ($str_sub_basID)";
            }
            if (!empty($str_sub_synID)) {
                $_SESSION['s_query'] .= " OR ts2.taxonID IN ($str_sub_synID)
                                          OR ts2.basID IN ($str_sub_synID)
                                          OR ts2.synID IN ($str_sub_synID)";
            }
            $_SESSION['s_query'] .= ")) ";
        } else {
            $_SESSION['s_query'] = "SELECT SQL_CALC_FOUND_ROWS " . $sql_names . $sql_tables . $sql_restrict_specimen . $sql_restrict_species . "
                                    GROUP BY specimen_ID ";
        }
    }

    $location="Location: results.php";
    if (SID!="") $location = $location."?".SID;
    header($location);
}

?><html><!-- #BeginTemplate "/Templates/database_script.dwt" -->
<head>
<title>Virtual Herbaria / search page</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="description" content="FW4 DW4 HTML">
<!-- Fireworks 4.0  Dreamweaver 4.0 target.  Created Fri Nov 08 15:05:42 GMT+0100 (Westeuropische Normalzeit) 2002-->
<link rel="stylesheet" href="css/herbarium.css" type="text/css">
<?php $xajax->printJavascript('inc/xajax'); ?>
<script type="text/javascript" language="javascript"><!--
  function isEmpty(s) {
    for (var i=0; i<s.length;i++) {
      var c = s.charAt(i);
      if ((c != ' ') && (c != '\n') && (c != '\t')) return false;
    }
    return true;
  }

  function check() {
    if (isEmpty(document.f.family.value) &&
        isEmpty(document.f.taxon.value) &&
        isEmpty(document.f.HerbNummer.value) &&
        isEmpty(document.f.Sammler.value) &&
        isEmpty(document.f.source_name.value) &&
        isEmpty(document.f.collection.value) &&
        isEmpty(document.f.taxon_alt.value) &&
        isEmpty(document.f.series.value) &&
        isEmpty(document.f.geo_general.value) &&
        isEmpty(document.f.geo_region.value) &&
        isEmpty(document.f.nation_engl.value) &&
        isEmpty(document.f.provinz.value) &&
        document.f.type[0].checked &&
        document.f.images[1].checked) {
      var msg = "You haven't stated any search criteria.\n" +
                "So the searching may need a while!\n" +
                "Search anyway?\n";
      return confirm(msg);
    }
    return true;
  }
--></script>
</head>

<body bgcolor="#ffffff">
<div align="center">
  <table border="0" cellpadding="0" cellspacing="0" width="800">
    <!-- fwtable fwsrc="databasemenu.png" fwbase="databasemenu.gif" fwstyle="Dreamweaver" fwdocid = "742308039" fwnested="0" -->
    <tr>
      <td height="10" valign="top" colspan="9"></td>
    </tr>
    <tr>
      <!-- Shim row, height 1. -->
      <td><img src="images/spacer.gif" width="198" height="1" border="0"></td>
      <td><img src="images/spacer.gif" width="2" height="1" border="0"></td>
      <td><img src="images/spacer.gif" width="197" height="1" border="0"></td>
      <td><img src="images/spacer.gif" width="2" height="1" border="0"></td>
      <td><img src="images/spacer.gif" width="198" height="1" border="0"></td>
      <td><img src="images/spacer.gif" width="2" height="1" border="0"></td>
      <td><img src="images/spacer.gif" width="200" height="1" border="0"></td>
      <td><img src="images/spacer.gif" width="1" height="1" border="0"></td>
      <td><img src="images/spacer.gif" width="1" height="1" border="0"></td>
    </tr>
    <tr>
      <!-- row 1 -->
      <td colspan="8"><img name="databasemenu_r1_c1" src="images/databasemenu_r1_c1.gif" width="800" height="93" border="0" alt="virtual herbaria austria"></td>
      <td><img src="images/spacer.gif" width="1" height="93" border="0"></td>
    </tr>
    <tr>
      <!-- row 2 -->
      <td><a href="../index.htm"><img name="databasemenu_r2_c1" src="images/databasemenu_r2_c1.gif" width="198" height="37" border="0" alt="home"></a></td>
      <td><img name="databasemenu_r2_c2" src="images/databasemenu_r2_c2.gif" width="2" height="37" border="0" alt="herbarmenu"></td>
      <td><a href="index.php"><img name="databasemenu_r2_c3" src="images/databasemenu_r2_c3.gif" width="197" height="37" border="0" alt="general information"></a></td>
      <td><img name="databasemenu_r2_c4" src="images/databasemenu_r2_c4.gif" width="2" height="37" border="0" alt="herbarmenu"></td>
      <td><a href="collections.htm"><img name="databasemenu_r2_c5" src="images/databasemenu_r2_c5.gif" width="198" height="37" border="0" alt="collections"></a></td>
      <td><img name="databasemenu_r2_c6" src="images/databasemenu_r2_c6.gif" width="2" height="37" border="0" alt="herbarmenu"></td>
      <td><a href="refsystems.htm"><img name="databasemenu_r2_c7" src="images/databasemenu_r2_c7.gif" width="200" height="37" border="0" alt="reference systems"></a></td>
      <td><img name="databasemenu_r2_c8" src="images/databasemenu_r2_c8.gif" width="1" height="37" border="0" alt="herbarmenu"></td>
      <td><img src="images/spacer.gif" width="1" height="37" border="0"></td>
    </tr>
    <tr>
      <td height="20" valign="top" colspan="9">&nbsp;</td>
    </tr>
    <tr>
      <td valign="top" colspan="9"><!-- #BeginEditable "Seiteninhalt" -->
        <form name="f" id="ajax_f" Action="search.php" Method="POST" onsubmit="return check()">
          <table align="center" bgcolor="#eeeeee" cellspacing="0" border="0" cellpadding="0">
            <tr>
              <td align="center">
                <p></p>
                <p class="normal" style="border: 1px solid #FF0000; font-weight: bold; font-color: #FF0000; margin-top: 5px; margin-bottom: 5px; padding: 5px;">We are currently updating to a new image server infrastructure. We apologize for any inconvenience this may cause for you.</p>
                <p>
                <table border="0" cellspacing="0" cellpadding="0" align="center">
                  <tr>
                    <td width="20">&nbsp;</td>
                    <td  class="normal" align="right">Institution:&nbsp;</td>
                    <td>
                    <select size="1" name="source_name" onchange="xajax_getCollection(xajax.getFormValues('ajax_f',0,'source_name'))">
                    <option value=""></option>
                    <?php
                      $sql = "
                          SELECT `source_name` 
                          FROM `meta` 
                          WHERE `source_id` 
                          IN (
                            SELECT `source_id`
                            FROM `tbl_management_collections`
                            WHERE `collectionID`
                            IN ( 
                              SELECT DISTINCT `collectionID`
                              FROM `tbl_specimens`
                            )
                          )
                          ORDER BY `source_name`
                          ";
                      $result = mysql_query($sql);
                      while ($row=mysql_fetch_array($result)) {
                        echo "<option value=\"{$row['source_name']}\"";
                        if ($source_name==$row['source_name']) echo " selected";
                        echo ">{$row['source_name']}</option>\n";
                      }
                    ?>
                    </select></td>
                    <td width="20"></td>
                    <td class="normal" align="right">Herbar #:&nbsp;</td>
                    <td><input type="text" name="HerbNummer" value="<?php echo $HerbNummer ?>" size="26"></td>
                    <td width="20"></td>
                  </tr>
                  <tr>
                    <td width="20"></td>
                    <td class="normal" align="right">Collection:&nbsp;</td>
                    <td id="ajax_collection">
                    <select size="1" name="collection">
                    <option value=""></option>
                    <?php
                      $sql = "
                          SELECT `collection`
                          FROM `tbl_management_collections`
                          WHERE `collectionID`
                          IN ( 
                            SELECT DISTINCT `collectionID`
                            FROM `tbl_specimens`
                          )
                          ORDER BY `collection`
                          ";
                      $result = mysql_query($sql);
                      while ($row=mysql_fetch_array($result)) {
                        echo "<option value=\"{$row['collection']}\"";
                        if ($collection==$row['collection']) echo " selected";
                        echo ">{$row['collection']}</option>\n";
                      }
                    ?>
                    </select></td>
                    <td width="20"></td>
                    <td class="normal" align="right">Collection #:&nbsp;</td>
                    <td><input type="text" name="CollNummer" value="<?php echo $CollNummer ?>" size="26"></td>
                    <td width="20"></td>
                  </tr>
                  <tr>
                    <td colspan="7" height="10"></td>
                  </tr>
                  <tr>
                    <td width="20"></td>
                    <td class="normal" align="right">Family:&nbsp;</td>
                    <td><input type="text" name="family" value="<?php echo $family ?>" size="26"></td>
                    <td width="20"></td>
                    <td class="normal" align="right">Taxon:&nbsp;</td>
                    <td><input type="text" name="taxon" value="<?php echo $taxon ?>" size="26"></td>
                    <td width="20"></td>
                  </tr>
                  <tr>
                    <td width="20"></td>
                    <td class="normal" align="right">ident. history:&nbsp;</td>
                    <td><input type="text" name="taxon_alt" value="<?php echo $taxon_alt ?>" size="26"></td>
                    <td width="20"></td>
                    <td class="normal" align="right">incl. syn.&nbsp;</td>
                    <td><input type="checkbox" checked name="synonym"></td>
                    <td width="20"></td>
                  </tr>
                  <tr>
                    <td colspan="7" height="10"></td>
                  </tr>
                  <tr>
                    <td width="20"></td>
                    <td class="normal" align="right">Collector:&nbsp;</td>
                    <td><input type="text" name="Sammler" value="<?php echo $Sammler ?>" size="26"></td>
                    <td width="20"></td>
                    <td class="normal" align="right">Series:&nbsp;</td>
                    <td><input type="text" name="series" value="<?php echo $series ?>" size="26"></td>
                    <td width="20"></td>
                  </tr>
                  <tr>
                    <td width="20"></td>
                    <td class="normal" align="right">Collector #:&nbsp;</td>
                    <td><input type="text" name="SammlerNr" value="<?php echo $SammlerNr ?>" size="26"></td>
                    <td width="20"></td>
                    <td colspan="2"></td>
                    <td width="20"></td>
                  </tr>
                  <tr>
                    <td colspan="7" height="10"></td>
                  </tr>
                  <tr>
                    <td width="20"></td>
                    <td class="normal" align="right">Continent:&nbsp;</td>
                    <td>
                    <select size="1" name="geo_general" onchange="xajax_getCountry(xajax.getFormValues('ajax_f',0,'geo_'))">
                    <option value=""></option>
                    <?php
                      $sql = "SELECT geo_general
                              FROM tbl_geo_region
                              GROUP BY geo_general ORDER BY geo_general";
                      $result = mysql_query($sql);
                      while ($row=mysql_fetch_array($result)) {
                        echo "<option value=\"{$row['geo_general']}\"";
                        if ($geo_general==$row['geo_general']) echo " selected";
                        echo ">{$row['geo_general']}</option>\n";
                      }
                    ?>
                    </select></td>
                    <td width="20"></td>
                    <td class="normal" align="right">Country:&nbsp;</td>
                    <td id="ajax_nation_engl"><input type="text" name="nation_engl" value="<?php echo $nation_engl ?>" size="26"></td>
                    <td width="20"></td>
                  </tr>
                  <tr>
                    <td width="20"></td>
                    <td class="normal" align="right">Region:&nbsp;</td>
                    <td>
                    <select size="1" name="geo_region" onchange="xajax_getCountry(xajax.getFormValues('ajax_f',0,'geo_'))">
                    <option value=""></option>
                    <?php
                      $sql = "SELECT geo_region
                              FROM tbl_geo_region
                              ORDER BY geo_region";
                      $result = mysql_query($sql);
                      while ($row=mysql_fetch_array($result)) {
                        echo "<option value=\"{$row['geo_region']}\"";
                        if ($geo_region==$row['geo_region']) echo " selected";
                        echo ">{$row['geo_region']}</option>\n";
                      }
                    ?>
                    </select></td>
                    <td width="20"></td>
                    <td class="normal" align="right">State/Province:&nbsp;</td>
                    <td id="ajax_provinz"><input type="text" name="provinz" value="<?php echo $provinz ?>" size="26"></td>
                    <td width="20"></td>
                  </tr>
                    <td width="20"></td>
                    <td></td>
                    <td></td>
                    <td width="20"></td>
                    <td class="normal" align="right">Locality:&nbsp;</td>
                    <td id="ajax_provinz"><input type="text" name="Fundort" value="<?php echo $fundort ?>" size="26"></td>
                    <td width="20"></td>
                  <tr>
                  </tr>
                </table>
                </p>
                <p class="normal">
                  <input type="radio" name="type" value="all" checked>
                  All records
                  <input type="radio" name="type" value="only">
                  Type records only
                </p>
                <p class="normal"> Display only records containing images:
                  <input type="radio" name="images" value="only">
                  Yes
                  <input type="radio" name="images" value="all" checked>
                  No
                </p>
                <p></p>
              </td>
            </tr>
          </table>
          <p style="text-align:center">
            <input type="submit" name="submit" value="Search">
            <input type="reset" value="Reset">
          </p>
          <p class="normal" style="text-align:center">
            <b>Last database update <?php echo  $lastUpdate ?></b>
          </p>
        </form>

        <div align="center">
          <table border="0">
            <tr>
          <p class="new">
              <br>
              <br>
          </p>

            </tr>
            <tr>
              <td class="new"><p align="left"><b>Search Tips</b></td>
              <td>&nbsp;</td>
            </tr>
            <tr>
              <td class="normal" valign="top">
                <b>general</b><br>
                search is not <b>case sensitive</b><br>
                fields are automatically linked by <b>AND</b><br>
                for partial strings the <b>%</b> sign can be used as a wildcard&nbsp;&nbsp;&nbsp;<br>
              </td>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
            </tr>
            <tr>
            </tr>
            <tr>
              <td class="normal">
                <p align="left">
                <b>taxon search</b><br>
                queries for a genus can be sent as "genus name" "blank space" and the "%" sign:<br>
		  &nbsp;&nbsp;searchstring <b>"Oncidum %"</b> yields all data for <b>Oncidium</b> plus all data for transferred names, e.g. <b>Cyrtochilum</b>, etc.</br>
		 </td>
            </tr>
            <tr>
              <td class="normal">
                typing the initial letters for "genus" and "epithet" are sufficient as search criteria:<br>
                <b>"p bad"</b> yields all taxa where genus starts with <b>&quot;p&quot;</b> and epithet starts with <b>&quot;bad&quot;</b> results include e.g. <b><i>Parmelia badia</i> Hepp</b>, <b><i>Peziza badia</i> Pers.</b> or <b><i>Poa badensis</i> Haenke ex Willd.</b><br>
                <br>
                search on synonymy has been implemented for nomenclatural &#38; taxonomic questions /
                for this purpose the "incl. syn." checkbox is activate as a standard; if you want to get data for the exact search string uncheck <b>"incl. syn."</b><p>
              </td>
            </tr>
            <tr>
            </tr>
            <tr>
              <td class="normal">
                <p align="left">
                <b>images</b>
		 </td>
            </tr>
            <tr>
		<td class="normal">
                <p align="left">
                <img src="images/obs.png" height="15" width="15"> image(s) provided for <b>living plant</b>
                &nbsp;&nbsp; <b>||</b> &nbsp;&nbsp;
                <img src="images/obs_bw.png" height="15" width="15"> <b>observational record</b>; without specimen or image</p>
              </td>
            </tr>
            <tr>
		<td class="normal">
                <p align="left">
                <img src="images/camera.png" height="15" width="15"> image(s) provided for <b>herbarium specimen</b>
                &nbsp;&nbsp; <b>||</b> &nbsp;&nbsp;
                <img src="images/spec_obs.png" height="15" width="15"> images provided for <b>specimen</b> and <b>living plant</b></p>
              </td>
            </tr>
          </table>
        </div>

      <!-- #EndEditable --></td>
    </tr>
    <tr>
      <td valign="top" colspan="9" align="center">
        <HR SIZE=1  width="800" NOSHADE>
        <p class="normal"><b>database management and digitizing</b> -- <a href="mailto:heimo.rainer@univie.ac.at">Heimo Rainer<br></a><br>
          <b>programming</b> -- <a href="mailto:joschach@ap4net.at">Johannes Schachner</a></p>
        <div class="normal" align="center">
          <!-- #BeginEditable "Datum" -->
          <B>Last modified:</B> <EM>2011-Oct-20, WK</EM>
          <!-- #EndEditable -->
        </div>
      </td>
    </tr>
  </table>
</div>
</body>
<!-- #EndTemplate --></html>
