<?php

use Jacq\DbAccess;

require_once 'inc/functions.php';
require_once __DIR__ . '/../vendor/autoload.php';

$dbLnk2 = DbAccess::ConnectTo('OUTPUT');

if (!empty($_POST['submit'])) {
    $sql_names = "s.specimen_ID, tg.genus, te.epithet, ta.author, c.Sammler, c2.Sammler_2, ss.series, s.Nummer, s.HerbNummer ";
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
                    LEFT JOIN herbar_pictures.phaidra_cache ph ON ph.specimenID = s.specimen_ID
                   WHERE ts.taxonID = s.taxonID
                    AND tg.genID = ts.genID
                    AND tf.familyID = tg.familyID
                    AND mc.collectionID = s.collectionID
                    AND tid.source_id_fk = mc.source_id
                    AND mc.source_ID = m.source_ID
                    AND s.accessible != '0' ";
    $sql_restrict_specimen = $sql_restrict_species = "";
    foreach ($_POST as $var => $value) {
        // echo "$var = $value<br>\n";
        if (trim($value) != "" && $var != "submit" && $var != "PHPSESSID") {
            if ($var != "type" && $var != "images" && $var != "synonym") {
                $varE   = $dbLnk2->real_escape_string(trim($var));
                $valueE = $dbLnk2->real_escape_string(trim($value));
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
                } elseif ($var == "collection" || $var == "source_name" || $var == "CollNummer") {
                    $sql_restrict_specimen .= "AND " . $var . " = '$valueE' ";
                } elseif ($var == "HerbNummer") {
                    // search for source-code at the beginning
                    if (ctype_alpha(substr($value, 0, 1))) {
                        $source_code = "";
                        for ($i = 0; $i < strlen($value); $i++) {
                            $next_character = substr($value, $i, 1);
                            if (ctype_alpha($next_character)) {
                                $source_code .= $next_character;
                            } else {
                                break;
                            }
                        }
                        $sql_restrict_specimen .= "AND source_code = '" . strtoupper($source_code) . "' ";
                        $remaining = trim(substr($value, $i));
                    } else {
                        $remaining = trim($value);
                    }
                    // is there still something left?
                    if (strlen($remaining) > 0) {
                        // search for trailing alphameric characters
                        if (ctype_alpha(substr($remaining, -1))) {
                            for ($i = strlen($remaining) - 2; $i >= 0; $i--) {
                                $check_char = substr($remaining, $i, 1);
                                if (!ctype_alpha($check_char) && $check_char !== "-") {
                                    break;
                                }
                            }
                            $trailing = substr($remaining, $i + 1);
                            $remaining = substr($remaining, 0, $i + 1);
                        } else {
                            $trailing = ""; // no trailing chars
                        }
                        if (strpos($remaining, '-') == 4) { // contents of search is ####-#... so, look also inside "CollNummer" (relevant for source-ID 6 = W)
                            $pre = substr($remaining, 0, 5);
                            $remaining = substr($remaining, 5);
                        } else {
                            $pre = "";
                        }
                        if (strlen($remaining) >= 6) {
                            $number = $remaining;   // at least 6 digits, so no padding with zeros
                        } else {
                            $number = sprintf("%06d", intval($remaining));
                        }
                        if ($pre) {
                            $sql_restrict_specimen .= "AND (HerbNummer LIKE '$pre%$number$trailing' OR CollNummer LIKE '$pre%$number$trailing') ";
                        } else {
                            $sql_restrict_specimen .= "AND HerbNummer LIKE '%$number$trailing' ";
                        }
                    }
                } elseif ($var == "SammlerNr") {
                    $sql_restrict_specimen .= "AND (s.Nummer='$valueE' OR s.alt_number LIKE '%$valueE%' OR s.series_number LIKE '%$valueE%') ";
                } elseif ($var == "CollDate") {
                    $sql_restrict_specimen .= "AND (s.Datum LIKE '%$valueE%') ";
                } elseif ($var == "Sammler") {
                    $sql_restrict_specimen .= "AND (s.SammlerID IN (";
                    // first search in tbl_collector for collector and similar entries
                    $results = array();
                    $rows = $dbLnk2->query("SELECT SammlerID, HUH_ID, VIAF_ID, WIKIDATA_ID, ORCID, Bloodhound_ID
                                            FROM tbl_collector
                                            WHERE Sammler LIKE '$valueE%'")
                                   ->fetch_all(MYSQLI_ASSOC);
                    foreach ($rows as $row) {
                        $results[] = $row['SammlerID'];
                        $othersFlag = false;
                        $sql = "SELECT SammlerID
                                FROM tbl_collector
                                WHERE SammlerID != " . $row['SammlerID'] . "
                                 AND  (";
                        foreach (array('HUH_ID', 'VIAF_ID', 'WIKIDATA_ID', 'ORCID', 'Bloodhound_ID') as $type) {
                            if ($row[$type]) {
                                $sql .= (($othersFlag) ? " OR " : '') . "$type = '" . $row[$type] . "'";
                                $othersFlag = true;
                            }
                        }
                        if ($othersFlag) {
                            $others = $dbLnk2->query($sql . ')')->fetch_all(MYSQLI_ASSOC);
                            foreach ($others as $item) {
                                $results[] = $item['SammlerID'];
                            }
                        }
                    }
                    $sql_restrict_specimen .= (($results) ? implode(', ', $results) : 'NULL') . ")";

                    // second search in tbl_collector_2
                    $rows2 = $dbLnk2->query("SELECT Sammler_2ID FROM tbl_collector_2 WHERE Sammler_2 LIKE '%$valueE%'")->fetch_all(MYSQLI_ASSOC);
                    if (!empty($rows2)) {
                        $sql_restrict_specimen .= " OR s.Sammler_2ID IN (" . implode(', ', array_column($rows2, 'Sammler_2ID')) . ")";
                    }
                    $sql_restrict_specimen .= ") ";
                } elseif ($var == "Fundort") {
                    $sql_restrict_specimen .= "AND (Fundort LIKE '%$valueE%' OR Fundort_engl LIKE '%$valueE%') ";
                } elseif ($var == "habitus") {
                    $sql_restrict_specimen .= "AND (habitus LIKE '%$valueE%') ";
                } elseif ($var == "habitat") {
                    $sql_restrict_specimen .= "AND (habitat LIKE '%$valueE%') ";
                } elseif ($var == "annotation") {
                    $sql_restrict_specimen .= "AND (Bemerkungen LIKE '%$valueE%') ";
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
        $res_sub = $dbLnk2->query($sql_sub);
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

     if ($_POST['synonym'] != 'all') {
        if (!empty($str_sub_taxonID)) {
            $_SESSION['s_query'] = "( SELECT " . $sql_names . $sql_tables . $sql_restrict_specimen . $sql_restrict_species . " GROUP BY specimen_ID)
                                    UNION
                                    ( SELECT " . $sql_names . $sql_tables . $sql_restrict_specimen . "
                                       AND ts.taxonID IN ($str_sub_taxonID) GROUP BY specimen_ID)
                                    UNION
                                    ( SELECT " . $sql_names . $sql_tables . $sql_restrict_specimen . "
                                       AND ts2.taxonID IN ($str_sub_taxonID) GROUP BY specimen_ID) ";
            $_SESSION['s_query_nrRows'] = "SELECT SQL_CALC_FOUND_ROWS * FROM (" . $_SESSION['s_query'] . ") AS union_tbl ";
        } else {
            $_SESSION['s_query'] = "SELECT " . $sql_names . $sql_tables . $sql_restrict_specimen . $sql_restrict_species . "
                                    GROUP BY specimen_ID ";
            $_SESSION['s_query_nrRows'] = "SELECT SQL_CALC_FOUND_ROWS " . $sql_names . $sql_tables . $sql_restrict_specimen . $sql_restrict_species . "
                                           GROUP BY specimen_ID ";
        }
    } else {
        if (!empty($str_sub_taxonID) || !empty($str_sub_basID) || !empty($str_sub_synID)) {
            $_SESSION['s_query'] = "( SELECT " . $sql_names . $sql_tables . $sql_restrict_specimen . $sql_restrict_species . " GROUP BY specimen_ID)
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
            $_SESSION['s_query'] .= ") GROUP BY specimen_ID) ";
            $_SESSION['s_query_nrRows'] = "SELECT SQL_CALC_FOUND_ROWS * FROM (" . $_SESSION['s_query'] . ") AS union_tbl ";
        } else {
            $_SESSION['s_query'] = "SELECT " . $sql_names . $sql_tables . $sql_restrict_specimen . $sql_restrict_species . "
                                    GROUP BY specimen_ID ";
            $_SESSION['s_query_nrRows'] = "SELECT SQL_CALC_FOUND_ROWS " . $sql_names . $sql_tables . $sql_restrict_specimen . $sql_restrict_species . "
                                           GROUP BY specimen_ID ";
        }
    }
    $_SESSION['s_nrRows'] = 0;
}
