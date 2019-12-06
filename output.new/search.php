<?php
session_start();
require("inc/dev-functions.php");
require_once ("inc/xajax/xajax.inc.php");
$xajax = new xajax("ajax/dev-searchServer.php");
$xajax->registerFunction("getCollection");
$xajax->registerFunction("getCountry");
$xajax->registerFunction("getProvince");

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Cache-Control: post-check=0, pre-check=0", false);

if (!empty($_POST['reset'])) {
    $family      = '';
    $taxon       = '';
    $HerbNummer  = '';
    $Sammler     = '';
    $SammlerNr   = '';
    $geo_general = '';
    $geo_region  = '';
    $nation_engl = '';
    $provinz     = '';
    $fundort     = '';
    $source_name = '';
    $collection  = '';
    $taxon_alt   = '';
    $CollNummer  = '';
    $series      = '';
} else {
    $family      = (!empty($_SESSION['o_family']))      ? $_SESSION['o_family']      : ((!empty($_POST['family']))      ? $_POST['family']      : ((isset($_GET['family']))      ? $_GET['family'] : ''));
    $taxon       = (!empty($_SESSION['o_taxon']))       ? $_SESSION['o_taxon']       : ((!empty($_POST['taxon']))       ? $_POST['taxon']       : ((isset($_GET['taxon']))       ? $_GET['taxon'] : ''));
    $HerbNummer  = (!empty($_SESSION['o_HerbNummer']))  ? $_SESSION['o_HerbNummer']  : ((!empty($_POST['HerbNummer']))  ? $_POST['HerbNummer']  : ((isset($_GET['HerbNummer']))  ? $_GET['HerbNummer'] : ''));
    $Sammler     = (!empty($_SESSION['o_Sammler']))     ? $_SESSION['o_Sammler']     : ((!empty($_POST['Sammler']))     ? $_POST['Sammler']     : ((isset($_GET['Sammler']))     ? $_GET['Sammler'] : ''));
    $SammlerNr   = (!empty($_SESSION['o_SammlerNr']))   ? $_SESSION['o_SammlerNr']   : ((!empty($_POST['SammlerNr']))   ? $_POST['SammlerNr']   : ((isset($_GET['SammlerNr']))   ? $_GET['SammlerNr'] : ''));
    $geo_general = (!empty($_SESSION['o_geo_general'])) ? $_SESSION['o_geo_general'] : ((!empty($_POST['geo_general'])) ? $_POST['geo_general'] : ((isset($_GET['geo_general'])) ? $_GET['geo_general'] : ''));
    $geo_region  = (!empty($_SESSION['o_geo_region']))  ? $_SESSION['o_geo_region']  : ((!empty($_POST['geo_region']))  ? $_POST['geo_region']  : ((isset($_GET['geo_region']))  ? $_GET['geo_region'] : ''));
    $nation_engl = (!empty($_SESSION['o_nation_engl'])) ? $_SESSION['o_nation_engl'] : ((!empty($_POST['nation_engl'])) ? $_POST['nation_engl'] : ((isset($_GET['nation_engl'])) ? $_GET['nation_engl'] : ''));
    $provinz     = (!empty($_SESSION['o_provinz']))     ? $_SESSION['o_provinz']     : ((!empty($_POST['provinz']))     ? $_POST['provinz']     : ((isset($_GET['provinz']))     ? $_GET['provinz'] : ''));
    $fundort     = (!empty($_SESSION['o_Fundort']))     ? $_SESSION['o_Fundort']     : ((!empty($_POST['Fundort']))     ? $_POST['Fundort']     : ((isset($_GET['Fundort']))     ? $_GET['Fundort'] : ''));
    $source_name = (!empty($_SESSION['o_source_name'])) ? $_SESSION['o_source_name'] : ((!empty($_POST['source_name'])) ? $_POST['source_name'] : ((isset($_GET['source_name'])) ? $_GET['source_name'] : ''));
    $collection  = (!empty($_SESSION['o_collection']))  ? $_SESSION['o_collection']  : ((!empty($_POST['collection']))  ? $_POST['collection']  : ((isset($_GET['collection']))  ? $_GET['collection'] : ''));
    $taxon_alt   = (!empty($_SESSION['o_taxon_alt']))   ? $_SESSION['o_taxon_alt']   : ((!empty($_POST['taxon_alt']))   ? $_POST['taxon_alt']   : ((isset($_GET['taxon_alt']))   ? $_GET['taxon_alt'] : ''));
    $CollNummer  = (!empty($_SESSION['o_CollNummer']))  ? $_SESSION['o_CollNummer']  : ((!empty($_POST['CollNummer']))  ? $_POST['CollNummer']  : ((isset($_GET['CollNummer']))  ? $_GET['CollNummer'] : ''));
    $series      = (!empty($_SESSION['o_series']))      ? $_SESSION['o_series']      : ((!empty($_POST['series']))      ? $_POST['series']      : ((isset($_GET['series']))      ? $_GET['series'] : ''));
}

$_SESSION['o_family']      = $family;
$_SESSION['o_taxon']       = $taxon;
$_SESSION['o_HerbNummer']  = $HerbNummer;
$_SESSION['o_Sammler']     = $Sammler;
$_SESSION['o_SammlerNr']   = $SammlerNr;
$_SESSION['o_geo_general'] = $geo_general;
$_SESSION['o_geo_region']  = $geo_region;
$_SESSION['o_nation_engl'] = $nation_engl;
$_SESSION['o_provinz']     = $provinz;
$_SESSION['o_Fundort']     = $fundort;
$_SESSION['o_source_name'] = $source_name;
$_SESSION['o_collection']  = $collection;
$_SESSION['o_taxon_alt']   = $taxon_alt;
$_SESSION['o_CollNummer']  = $CollNummer;
$_SESSION['o_series']      = $series;

$result = $dbLink->query("SELECT DATE_FORMAT(Eingabedatum,'%Y-%m-%d') AS date FROM tbl_specimens ORDER BY DATE DESC");
$row = $result->fetch_array();
$lastUpdate = $row['date'];

if (!empty($_POST['submit']) || !empty($_GET['search'])) {
    if (!empty($_GET['search'])) {
        $_POST['taxon'] = (!empty($_GET['taxon'])) ? $_GET['taxon'] : '';

        if (!empty($_GET['source_id']))  { $_POST['source_id']   = $_GET['source_id']; }
        if (!empty($_GET['collector']))  { $_POST['Sammler']     = $_GET['collector']; }
        if (!empty($_GET['SammlerNr']))  { $_POST['SammlerNr']   = $_GET['SammlerNr']; }
        if (!empty($_GET['family']))     { $_POST['family']      = $_GET['family']; }
        if (!empty($_GET['HerbNummer'])) { $_POST['HerbNummer']  = $_GET['HerbNummer']; }
        if (!empty($_GET['synonym']))    { $_POST['synonym']     = $_GET['synonym']; }
        if (!empty($_GET['obs']))        { $_POST['obs']         = $_GET['obs']; }
        if (!empty($_GET['country']))    { $_POST['nation_engl'] = $_GET['country']; }
        if (!empty($_GET['province']))   { $_POST['provinz']     = $_GET['province']; }
        if (!empty($_GET['location']))   { $_POST['Fundort']     = $_GET['location']; }
        if (!empty($_GET['collection'])) { $_POST['collection']  = $_GET['collection']; }
        if (!empty($_GET['source_name'])){ $_POST['source_name'] = $_GET['source_name']; }
        if (!empty($_GET['taxon_alt']))  { $_POST['taxon_alt']   = $_GET['taxon_alt']; }
    }
    $_SESSION['o_family']      = $_POST['family'];
    $_SESSION['o_taxon']       = $_POST['taxon'];
    $_SESSION['o_HerbNummer']  = $_POST['HerbNummer'];
    $_SESSION['o_Sammler']     = $_POST['Sammler'];
    $_SESSION['o_SammlerNr']   = $_POST['SammlerNr'];
    $_SESSION['o_geo_general'] = $_POST['geo_general'];
    $_SESSION['o_geo_region']  = $_POST['geo_region'];
    $_SESSION['o_nation_engl'] = $_POST['nation_engl'];
    $_SESSION['o_provinz']     = $_POST['provinz'];
    $_SESSION['o_Fundort']     = $_POST['Fundort'];
    $_SESSION['o_source_name'] = $_POST['source_name'];
    $_SESSION['o_collection']  = $_POST['collection'];
    $_SESSION['o_taxon_alt']   = $_POST['taxon_alt'];
    $_SESSION['o_CollNummer']  = $_POST['CollNummer'];
    $_SESSION['o_series']      = $_POST['series'];

    $sql_names = "s.specimen_ID, tg.genus, s.digital_image, s.digital_image_obs, s.observation,
                  c.Sammler, c.HUH_ID, c.VIAF_ID, c.WIKIDATA_ID, c2.Sammler_2, ss.series, s.series_number,
                  s.Nummer, s.alt_number, s.Datum, mc.collection, mc.source_id, tid.imgserver_IP, tid.iiif_capable, tid.iiif_proxy, tid.iiif_dir, s.HerbNummer,
                  n.nation_engl, n.iso_alpha_2_code, p.provinz, s.collectionID, MIN(tst.typusID) AS typusID, t.typus,
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
                $varE   = $dbLink->real_escape_string($var);
                $valueE = $dbLink->real_escape_string($value);
                if ($var == "taxon") {
                    $pieces = explode(" ", $valueE);
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
                    $sql_restrict_species .= "AND (tf.family LIKE '$valueE%' OR tf.family_alt LIKE '$valueE%') ";
                } elseif ($var == "series" || $var == "taxon_alt") {
                    $sql_restrict_specimen .= "AND " . $var . " LIKE '%$valueE%' ";
                } elseif ($var == "collection" || $var == "source_name" || $var == "HerbNummber" || $var == "CollNummer") {
                    $sql_restrict_specimen .= "AND " . $var . " = '$valueE' ";
                } elseif ($var == "SammlerNr") {
                    $sql_restrict_specimen .= "AND (s.Nummer='$valueE' OR s.alt_number LIKE '%$valueE%' OR s.series_number LIKE '%$valueE%') ";
                } elseif ($var == "Sammler") {
                    $sql_restrict_specimen .= "AND (Sammler LIKE '$valueE%' OR Sammler_2 LIKE '%$valueE%') ";
                } elseif ($var == "Fundort") {
                    $sql_restrict_specimen .= "AND (Fundort LIKE '%$valueE%' OR Fundort_engl LIKE '%$valueE%') ";
                } elseif ($var == "nation_engl") {
                    $sql_restrict_specimen .= "AND (nation_engl LIKE '$valueE%' OR nation LIKE '$valueE%'"
                                            . "     OR (language_variants LIKE '%$valueE%' AND language_variants NOT LIKE '%(%$valueE%)%')) ";
                } elseif ($var == "provinz") {
                    $sql_restrict_specimen .= "AND (provinz LIKE '$valueE%' OR provinz_local LIKE '$valueE%') ";
                } else {
                    $sql_restrict_specimen .= "AND $varE LIKE '$valueE%' ";
                }
            } elseif ($var == "type" && $value == "only") {
                $sql_restrict_specimen .= "AND tst.typusID IS NOT NULL ";
            } elseif ($var == "images" && $value == "only") {
                $sql_restrict_specimen .= "AND (s.digital_image = 1 OR s.digital_image_obs = 1)";
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
        $res_sub = $dbLink->query($sql_sub);
        $array_sub_taxonID = array();
        $array_sub_basID   = array();
        $array_sub_synID   = array();
        while ($row_sub = $res_sub->fetch_array()) {
            if ($row_sub['taxonID']) { $array_sub_taxonID[] = $row_sub['taxonID']; }
            if ($row_sub['basID'])   { $array_sub_basID[]   = $row_sub['basID']; }
            if ($row_sub['synID'])   { $array_sub_synID[]   = $row_sub['synID']; }
        }
        if (!empty($array_sub_taxonID)) { $str_sub_taxonID = implode(", ", array_unique($array_sub_taxonID)); }
        if (!empty($array_sub_basID))   { $str_sub_basID   = implode(", ", array_unique($array_sub_basID)); }
        if (!empty($array_sub_synID))   { $str_sub_synID   = implode(", ", array_unique($array_sub_synID)); }
//        echo "<pre>" . var_export($str_sub_taxonID, true) . "<br>" . var_export($str_sub_basID, true) . "<br>" . var_export($str_sub_synID, true) . "</pre>"; die();
    }

    if (!$_POST['synonym']) {
        if (!empty($str_sub_taxonID)) {
            $_SESSION['s_query'] = "SELECT SQL_CALC_FOUND_ROWS * FROM (
                                    ( SELECT " . $sql_names . $sql_tables . $sql_restrict_specimen . $sql_restrict_species . " GROUP BY specimen_ID)
                                    UNION
                                    ( SELECT " . $sql_names . $sql_tables . $sql_restrict_specimen . "
                                       AND ts.taxonID IN ($str_sub_taxonID) GROUP BY specimen_ID)
                                    UNION
                                    ( SELECT " . $sql_names . $sql_tables . $sql_restrict_specimen . "
                                       AND ts2.taxonID IN ($str_sub_taxonID) GROUP BY specimen_ID)) AS union_tbl ";
        } else {
            $_SESSION['s_query'] = "SELECT SQL_CALC_FOUND_ROWS " . $sql_names . $sql_tables . $sql_restrict_specimen . $sql_restrict_species . "
                                    GROUP BY specimen_ID ";
        }
    } else {
        if (!empty($str_sub_taxonID) || !empty($str_sub_basID) || !empty($str_sub_synID)) {
            $_SESSION['s_query'] = "SELECT SQL_CALC_FOUND_ROWS * FROM (
                                    ( SELECT " . $sql_names . $sql_tables . $sql_restrict_specimen . $sql_restrict_species . " GROUP BY specimen_ID)
                                    UNION
                                    ( SELECT " . $sql_names . $sql_tables . $sql_restrict_specimen . "
                                       AND (";
            if (!empty($str_sub_taxonID)) {
                $_SESSION['s_query'] .= "ts.taxonID IN ($str_sub_taxonID)
                                          OR ts.basID IN ($str_sub_taxonID)
                                          OR ts.synID IN ($str_sub_taxonID)";
                $connector = " OR ";
            } else {
                $connector = "";
            }
            if (!empty($str_sub_basID)) {
                $_SESSION['s_query'] .=  $connector
                                      . "ts.taxonID IN ($str_sub_basID)
                                          OR ts.basID IN ($str_sub_basID)
                                          OR ts.synID IN ($str_sub_basID)";
                $connector = " OR ";
            }
            if (!empty($str_sub_synID)) {
                $_SESSION['s_query'] .=  $connector
                                      . "ts.taxonID IN ($str_sub_synID)
                                          OR ts.basID IN ($str_sub_synID)
                                          OR ts.synID IN ($str_sub_synID)";
            }
            $_SESSION['s_query'] .= ") GROUP BY specimen_ID)
                                     UNION
                                     ( SELECT " . $sql_names . $sql_tables . $sql_restrict_specimen . "
                                        AND (";
            if (!empty($str_sub_taxonID)) {
                $_SESSION['s_query'] .= "ts2.taxonID IN ($str_sub_taxonID)
                                          OR ts2.basID IN ($str_sub_taxonID)
                                          OR ts2.synID IN ($str_sub_taxonID)";
                $connector = " OR ";
            } else {
                $connector = "";
            }
            if (!empty($str_sub_basID)) {
                $_SESSION['s_query'] .= $connector
                                      . "ts2.taxonID IN ($str_sub_basID)
                                          OR ts2.basID IN ($str_sub_basID)
                                          OR ts2.synID IN ($str_sub_basID)";
                $connector = " OR ";
            }
            if (!empty($str_sub_synID)) {
                $_SESSION['s_query'] .= $connector
                                      . "ts2.taxonID IN ($str_sub_synID)
                                          OR ts2.basID IN ($str_sub_synID)
                                          OR ts2.synID IN ($str_sub_synID)";
            }
            $_SESSION['s_query'] .= ") GROUP BY specimen_ID)) AS union_tbl ";
        } else {
            $_SESSION['s_query'] = "SELECT SQL_CALC_FOUND_ROWS " . $sql_names . $sql_tables . $sql_restrict_specimen . $sql_restrict_species . "
                                    GROUP BY specimen_ID ";
        }
    }
    $location="Location: results.php";
    if (SID!="") { $location = $location."?".SID; }
    header($location);
}