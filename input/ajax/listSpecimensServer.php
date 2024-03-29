<?php

/**
 * This file is included from listWUServer.php
 * function is separated for cleaner code only
 */
require_once("../inc/herbardb_input_functions.php");
require_once('../inc/variables.php');

use Jaxon\Response\Response;

/**
 * Specimens list/searching function
 * @param int $page Pagination parameter
 * @param bool $bInitialize init pagination
 * @param int $itemsPerPage Items per page
 * @return Response
 * @throws Exception
 */
function listSpecimens($page, $bInitialize = false, $itemsPerPage = 0 ) {
    ob_start();

    // check value of items per page
    $itemsPerPage = intval($itemsPerPage);
    $itemsPerPage = ( $itemsPerPage > 0 ) ? $itemsPerPage : (($_SESSION['sItemsPerPage'] > 0) ? $_SESSION['sItemsPerPage'] : 10);
    $_SESSION['sItemsPerPage'] = $itemsPerPage;

    $response = new Response();

    $start = intval($page) * $itemsPerPage;
    $swBatch = (checkRight('batch')) ? true : false; // nur user mit Recht "batch" können Batches hinzufügen
    $nrSel = (isset($_SESSION['sNr'])) ? intval($_SESSION['sNr']) : 0;

    $sql_names =  " s.specimen_ID, tg.genus, s.digital_image,
                    c.Sammler, c2.Sammler_2, ss.series, s.series_number,
                    s.Nummer, s.alt_number, s.Datum, s.HerbNummer,
                    n.nation_engl, p.provinz, s.Fundort, mc.collectionID, mc.collection, mc.source_id, mc.coll_short, t.typus_lat,
                    s.Coord_W, s.W_Min, s.W_Sec, s.Coord_N, s.N_Min, s.N_Sec,
                    s.Coord_S, s.S_Min, s.S_Sec, s.Coord_E, s.E_Min, s.E_Sec, s.ncbi_accession,
                    ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
                    ta4.author author4, ta5.author author5,
                    te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
                    te4.epithet epithet4, te5.epithet epithet5 ";
    $sql_tables = "FROM tbl_specimens s
                    JOIN tbl_tax_species ts            ON ts.taxonID      = s.taxonID
                    JOIN tbl_tax_genera tg             ON tg.genID        = ts.genID
                    JOIN tbl_tax_families tf           ON tf.familyID     = tg.familyID
                    JOIN tbl_management_collections mc ON mc.collectionID = s.collectionID
                    LEFT JOIN tbl_specimens_types tst  ON tst.specimenID  = s.specimen_ID
                    LEFT JOIN tbl_specimens_series ss  ON ss.seriesID     = s.seriesID
                    LEFT JOIN tbl_typi t               ON t.typusID       = s.typusID
                    LEFT JOIN tbl_geo_province p       ON p.provinceID    = s.provinceID
                    LEFT JOIN tbl_geo_nation n         ON n.NationID      = s.NationID
                    LEFT JOIN tbl_geo_region r         ON r.regionID      = n.regionID_fk
                    LEFT JOIN tbl_collector c          ON c.SammlerID     = s.SammlerID
                    LEFT JOIN tbl_collector_2 c2       ON c2.Sammler_2ID  = s.Sammler_2ID
                    LEFT JOIN tbl_tax_authors  ta      ON ta.authorID     = ts.authorID
                    LEFT JOIN tbl_tax_authors  ta1     ON ta1.authorID    = ts.subspecies_authorID
                    LEFT JOIN tbl_tax_authors  ta2     ON ta2.authorID    = ts.variety_authorID
                    LEFT JOIN tbl_tax_authors  ta3     ON ta3.authorID    = ts.subvariety_authorID
                    LEFT JOIN tbl_tax_authors  ta4     ON ta4.authorID    = ts.forma_authorID
                    LEFT JOIN tbl_tax_authors  ta5     ON ta5.authorID    = ts.subforma_authorID
                    LEFT JOIN tbl_tax_epithets te      ON te.epithetID    = ts.speciesID
                    LEFT JOIN tbl_tax_epithets te1     ON te1.epithetID   = ts.subspeciesID
                    LEFT JOIN tbl_tax_epithets te2     ON te2.epithetID   = ts.varietyID
                    LEFT JOIN tbl_tax_epithets te3     ON te3.epithetID   = ts.subvarietyID
                    LEFT JOIN tbl_tax_epithets te4     ON te4.epithetID   = ts.formaID
                    LEFT JOIN tbl_tax_epithets te5     ON te5.epithetID   = ts.subformaID
                    LEFT JOIN tbl_tax_species ts2      ON ts2.taxonID     = tst.taxonID
                   WHERE 1 = 1";  // to have a WHERE
    $sql_restrict_specimen = $sql_restrict_species = "";
    if (isset($_SESSION['taxonID']) && trim($_SESSION['taxonID'])) {
        $sql_restrict_specimen .= " AND ts.taxonID='" . intval($_SESSION['taxonID']) . "'";
    } else {
        if (trim($_SESSION['sTaxon'])) {
            $pieces = explode(" ", trim($_SESSION['sTaxon']));
            $part1 = array_shift($pieces);
            $part2 = array_shift($pieces);
            $sql_restrict_species .= " AND tg.genus LIKE '" . dbi_escape_string($part1) . "%'";
            if ($part2) {
                $sql_restrict_species .= " AND (    te.epithet LIKE '" . dbi_escape_string($part2) . "%' 
                                                OR te1.epithet LIKE '" . dbi_escape_string($part2) . "%' 
                                                OR te2.epithet LIKE '" . dbi_escape_string($part2) . "%' 
                                                OR te3.epithet LIKE '" . dbi_escape_string($part2) . "%')";
            }
        }
        if (trim($_SESSION['sFamily'])) {
            $sql_restrict_species .= " AND (   tf.family LIKE '" . dbi_escape_string(trim($_SESSION['sFamily'])) . "%'
                                            OR tf.family_alt LIKE '" . dbi_escape_string(trim($_SESSION['sFamily'])) . "%')";
        }
        if (trim($_SESSION['sSeries'])) {
            $sql_restrict_specimen .= " AND ss.series LIKE '%" . dbi_escape_string(trim($_SESSION['sSeries'])) . "%'";
        }
        if (trim($_SESSION['wuCollection'])) {
            if (trim($_SESSION['wuCollection']) > 0) {
                $sql_restrict_specimen .= " AND s.collectionID=" . quoteString(trim($_SESSION['wuCollection']));
            }
            else {
                $sql_restrict_specimen .= " AND mc.source_id=" . quoteString(abs(trim($_SESSION['wuCollection'])));
            }
        }
        if (trim($_SESSION['sNumber'])) {
            if (strpos($_SESSION['sNumber'], ' - ') !== false) {  // search for a range of herb-numbers
                $parts = explode(' - ', $_SESSION['sNumber']);
                $sql_restrict_specimen .= " AND (s.HerbNummer >= " . intval(trim($parts[0])) . " AND s.HerbNummer <= " . intval(trim($parts[1])) . ")";
            } else {
                $sql_restrict_specimen .= " AND s.HerbNummer LIKE '%" . dbi_escape_string(trim($_SESSION['sNumber'])) . "%'";
            }
        }
        if (trim($_SESSION['sCollector'])) {
            $sql_restrict_specimen .= " AND (c.Sammler LIKE '" . dbi_escape_string(trim($_SESSION['sCollector'])) . "%' OR
						   c2.Sammler_2 LIKE '%" . dbi_escape_string(trim($_SESSION['sCollector'])) . "%')";
        }
        if (trim($_SESSION['sNumberCollector'])) {
            if (strpos($_SESSION['sNumberCollector'], ' - ') !== false) {  // search for a range of collector-numbers
                $parts = explode(' - ', $_SESSION['sNumberCollector']);
                $sql_restrict_specimen .= " AND ((s.Nummer >= " . intval(trim($parts[0])) . " AND s.Nummer <= " . intval(trim($parts[1])) . ") OR
                                (s.series_number >= " . intval(trim($parts[0])) . " AND s.series_number <= " . intval(trim($parts[1])) . ")) ";
                                // alt_number is not possible, ' - ' is often part of the normal contents
            } else {
                $sql_restrict_specimen .= " AND (s.Nummer LIKE '" . dbi_escape_string(trim($_SESSION['sNumberCollector'])) . "%' OR
							s.alt_number LIKE '%" . dbi_escape_string(trim($_SESSION['sNumberCollector'])) . "%' OR
							s.series_number LIKE '" . dbi_escape_string(trim($_SESSION['sNumberCollector'])) . "%') ";
            }
        }
        if (trim($_SESSION['sNumberCollection'])) {
            if (strpos($_SESSION['sNumberCollection'], ' - ') !== false) {  // search for a range of collection-numbers
                $parts = explode(' - ', $_SESSION['sNumberCollection']);
                $sql_restrict_specimen .= " AND (s.CollNummer >= " . intval(trim($parts[0])) . " AND s.CollNummer <= " . intval(trim($parts[1])) . ")";
            } else {
                $sql_restrict_specimen .= " AND s.CollNummer LIKE '%" . dbi_escape_string(trim($_SESSION['sNumberCollection'])) . "%'";
            }
        }
        if (trim($_SESSION['sDate'])) {
            $occurrences = substr_count($_SESSION['sDate'], '-');
            if ($occurrences == 5) {
                $parts = explode('-', $_SESSION['sDate']);
                $dateStart = dbi_escape_string(sprintf("%4d-%02d-%02d", trim($parts[0]), trim($parts[1]), trim($parts[2])));
                $dateEnd   = dbi_escape_string(sprintf("%4d-%02d-%02d", trim($parts[3]), trim($parts[4]), trim($parts[5])));
                $sql_restrict_specimen .= " AND (s.Datum >= '$dateStart' AND s.Datum <= '$dateEnd')";
            } else {
                $sql_restrict_specimen .= " AND s.Datum LIKE '" . dbi_escape_string(trim($_SESSION['sDate'])) . "%'";
            }
        }
        if (trim($_SESSION['sGeoGeneral'])) {
            $sql_restrict_specimen .= " AND r.geo_general LIKE '" . dbi_escape_string(trim($_SESSION['sGeoGeneral'])) . "%'";
        }
        if (trim($_SESSION['sGeoRegion'])) {
            $sql_restrict_specimen .= " AND r.geo_region LIKE '" . dbi_escape_string(trim($_SESSION['sGeoRegion'])) . "%'";
        }
        if (trim($_SESSION['sCountry'])) {
            $sql_restrict_specimen .= " AND n.nation_engl LIKE '" . dbi_escape_string(trim($_SESSION['sCountry'])) . "%'";
        }
        if (trim($_SESSION['sProvince'])) {
            $sql_restrict_specimen .= " AND p.provinz LIKE '" . dbi_escape_string(trim($_SESSION['sProvince'])) . "%'";
        }
        if (trim($_SESSION['sLoc'])) {
            $sql_restrict_specimen .= " AND (   s.Fundort LIKE '%" . dbi_escape_string(trim($_SESSION['sLoc'])) . "%' 
                                             OR s.Fundort_engl LIKE '%" . dbi_escape_string(trim($_SESSION['sLoc'])) . "%')";
        }
        if (trim($_SESSION['sHabitat'])) {
            $sql_restrict_specimen .= " AND s.habitat LIKE '%" . dbi_escape_string(trim($_SESSION['sHabitat'])) . "%'";
        }
        if (trim($_SESSION['sHabitus'])) {
            $sql_restrict_specimen .= " AND s.habitus LIKE '%" . dbi_escape_string(trim($_SESSION['sHabitus'])) . "%'";
        }
        if (trim($_SESSION['sBemerkungen'])) {
            $sql_restrict_specimen .= " AND s.Bemerkungen LIKE '%" . dbi_escape_string(trim($_SESSION['sBemerkungen'])) . "%'";
        }
        if (trim($_SESSION['sTaxonAlt'])) {
            $sql_restrict_specimen .= " AND s.taxon_alt LIKE '%" . dbi_escape_string(trim($_SESSION['sTaxonAlt'])) . "%'";
        }
        if ($_SESSION['sTyp']) {
            $sql_restrict_specimen .= " AND s.typusID != 0";
        }

        if ($_SESSION['sImages'] == 'only') {
            $sql_restrict_specimen .= " AND s.digital_image != 0";
        } else if ($_SESSION['sImages'] == 'no') {
            $sql_restrict_specimen .= " AND s.digital_image = 0";
        }
    }

    $str_sub_taxonID = $str_sub_basID = $str_sub_synID = '';
    if (!empty($sql_restrict_species)) {
        $res_sub = dbi_query("SELECT ts.taxonID, ts.basID, ts.synID
                              FROM tbl_tax_genera tg
                               JOIN tbl_tax_families tf       ON tf.familyID   = tg.familyID
                               JOIN tbl_tax_species ts        ON ts.genID      = tg.genID
                               LEFT JOIN tbl_tax_epithets te  ON te.epithetID  = ts.speciesID
                               LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID = ts.subspeciesID
                               LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID = ts.varietyID
                               LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID = ts.subvarietyID
                               LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID = ts.formaID
                               LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID = ts.subformaID
                              WHERE 1=1 "
                 . $sql_restrict_species);
        if ($res_sub->num_rows > 0) {
            while ($row_sub = $res_sub->fetch_array()) {
                if ($row_sub['taxonID']) {
                    $array_sub_taxonID[] = $row_sub['taxonID'];
                }
                if ($row_sub['basID']) {
                    $array_sub_basID[] = $row_sub['basID'];
                }
                if ($row_sub['synID']) {
                    $array_sub_synID[] = $row_sub['synID'];
                }
            }
        }
        $str_sub_taxonID = (!empty($array_sub_taxonID)) ? implode(", ", array_unique($array_sub_taxonID)) : array();
        $str_sub_basID   = (!empty($array_sub_basID))   ? implode(", ", array_unique($array_sub_basID))   : array();
        $str_sub_synID   = (!empty($array_sub_synID))   ? implode(", ", array_unique($array_sub_synID))   : array();
    }

    $found_rows = 0;
    if (strlen($sql_restrict_specimen . $sql_restrict_species) == 0) {
        echo "<b>empty search criteria are not allowed</b>\n";
    }
    else {
        $_SESSION['sSQLCondition'] = $sql_restrict_specimen . $sql_restrict_species;  // depricated

        if (empty($_SESSION['sSynonyms'])) {
            if (!empty($str_sub_taxonID)) {
                $_SESSION['sSQLquery'] = "SELECT SQL_CALC_FOUND_ROWS * FROM (
                                          ( SELECT " . $sql_names . $sql_tables . $sql_restrict_specimen . $sql_restrict_species . " GROUP BY specimen_ID)
                                          UNION
                                          ( SELECT " . $sql_names . $sql_tables . $sql_restrict_specimen . "
                                             AND ts.taxonID IN ($str_sub_taxonID) GROUP BY specimen_ID)
                                          UNION
                                          ( SELECT " . $sql_names . $sql_tables . $sql_restrict_specimen . "
                                             AND ts2.taxonID IN ($str_sub_taxonID) GROUP BY specimen_ID)) AS union_tbl ";
            } else {
                $_SESSION['sSQLquery'] = "SELECT SQL_CALC_FOUND_ROWS " . $sql_names . $sql_tables . $sql_restrict_specimen . $sql_restrict_species . "
                                         GROUP BY specimen_ID ";
            }
        } else {
            if (!empty($str_sub_taxonID) || !empty($str_sub_basID) || !empty($str_sub_synID)) {
                $_SESSION['sSQLquery'] = "SELECT SQL_CALC_FOUND_ROWS * FROM (
                                          ( SELECT " . $sql_names . $sql_tables . $sql_restrict_specimen . $sql_restrict_species . " GROUP BY specimen_ID)
                                          UNION
                                          ( SELECT " . $sql_names . $sql_tables . $sql_restrict_specimen . "
                                             AND (";
                if (!empty($str_sub_taxonID)) {
                    $_SESSION['sSQLquery'] .= "ts.taxonID IN ($str_sub_taxonID)
                                                OR ts.basID IN ($str_sub_taxonID)
                                                OR ts.synID IN ($str_sub_taxonID)";
                    $connector = " OR ";
                } else {
                    $connector = "";
                }
                if (!empty($str_sub_basID)) {
                    $_SESSION['sSQLquery'] .=  $connector
                                            . "ts.taxonID IN ($str_sub_basID)
                                                OR ts.basID IN ($str_sub_basID)
                                                OR ts.synID IN ($str_sub_basID)";
                    $connector = " OR ";
                }
                if (!empty($str_sub_synID)) {
                    $_SESSION['sSQLquery'] .=  $connector
                                            . "ts.taxonID IN ($str_sub_synID)
                                                OR ts.basID IN ($str_sub_synID)
                                                OR ts.synID IN ($str_sub_synID)";
                }
                $_SESSION['sSQLquery'] .= ") GROUP BY specimen_ID)
                                           UNION
                                           ( SELECT " . $sql_names . $sql_tables . $sql_restrict_specimen . "
                                              AND (";
                if (!empty($str_sub_taxonID)) {
                    $_SESSION['sSQLquery'] .= "ts2.taxonID IN ($str_sub_taxonID)
                                                OR ts2.basID IN ($str_sub_taxonID)
                                                OR ts2.synID IN ($str_sub_taxonID)";
                    $connector = " OR ";
                } else {
                    $connector = "";
                }
                if (!empty($str_sub_basID)) {
                    $_SESSION['sSQLquery'] .= $connector
                                            . "ts2.taxonID IN ($str_sub_basID)
                                                OR ts2.basID IN ($str_sub_basID)
                                                OR ts2.synID IN ($str_sub_basID)";
                    $connector = " OR ";
                }
                if (!empty($str_sub_synID)) {
                    $_SESSION['sSQLquery'] .= $connector
                                            . "ts2.taxonID IN ($str_sub_synID)
                                                OR ts2.basID IN ($str_sub_synID)
                                                OR ts2.synID IN ($str_sub_synID)";
                }
                $_SESSION['sSQLquery'] .= ") GROUP BY specimen_ID)) AS union_tbl ";
            } else {
                $_SESSION['sSQLquery'] = "SELECT SQL_CALC_FOUND_ROWS " . $sql_names . $sql_tables . $sql_restrict_specimen . $sql_restrict_species . "
                                          GROUP BY specimen_ID ";
            }
        }

        $result = dbi_query($_SESSION['sSQLquery'] . " ORDER BY " . $_SESSION['sOrder'] . " LIMIT $start, $itemsPerPage");
        $fr_result = dbi_query("SELECT FOUND_ROWS() AS `found_rows`");
        $fr_row = mysqli_fetch_array($fr_result);
        $found_rows = $fr_row['found_rows'];

        if (mysqli_num_rows($result) > 0) {
            echo "<table class=\"out\" cellspacing=\"0\">\n"
               . "<tr class=\"out\">"
               . "<th class=\"out\"></th>"
               . "<th class=\"out\">"
               . "<a href=\"listSpecimens.php?order=a\">Taxon</a>" . sortItem($_SESSION['sOrTyp'], 1) . "</th>"
               . "<th class=\"out\">"
               . "<a href=\"listSpecimens.php?order=b\">Collector</a>" . sortItem($_SESSION['sOrTyp'], 2) . "</th>"
               . "<th class=\"out\">Date</th>"
               . "<th class=\"out\">X/Y</th>"
               . "<th class=\"out\">Location</th>"
               . "<th class=\"out\">"
               . "<a href=\"listSpecimens.php?order=d\">Typus</a>" . sortItem($_SESSION['sOrTyp'], 4) . "</th>"
               . "<th class=\"out\">"
               . "<a href=\"listSpecimens.php?order=e\">Coll.</a>" . sortItem($_SESSION['sOrTyp'], 5) . "</th>";
            if ($swBatch) {
                echo "<th class=\"out\">Batch</th>";
            }
            echo "</tr>\n";
            $nr = 1;
            while ($row = mysqli_fetch_array($result)) {
                $linkList[$nr] = $row['specimen_ID'];

                if ($row['digital_image']) {
                    $target = getIiifLink($row['specimen_ID']);
                    if ($target) {
                        $digitalImage = "<a href=\"javascript:showIiif('$target')\">"
                                      . "<img border=\"0\" height=\"15\" src=\"webimages/logo-iiif.png\" width=\"15\">"
                                      . "</a>";
                    } else {
                        $digitalImage = "<a href=\"javascript:showImage('" . $row['specimen_ID'] . "')\">"
                                      . "<img border=\"0\" height=\"15\" src=\"webimages/camera.png\" width=\"15\">"
                                      . "</a>";
                    }
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
                    $textLatLon = "<td class=\"out\" style=\"text-align: center\" title=\"" . round($lat, 5) . "&deg; / " . round($lon, 5) . "&deg;\">"
//                            . "<a href=\"http://www.mapquest.com/maps/map.adp?latlongtype=decimal&longitude=$lon&latitude=$lat&zoom=3\" "
//                            . "target=\"_blank\"><img border=\"0\" height=\"15\" src=\"webimages/mapquest.png\" width=\"15\">"
//                            . "</a>"
//                            . "<a href='osm_leaflet.php?sid=" . $row['specimen_ID'] . "' target='_blank'>"
                            . "<a href='#' onClick='osMap(" . $row['specimen_ID'] . "); return false;'>"
                            . "<img border='0' height='15' width='15' src='webimages/OpenStreetMap.png'"
                            . "</a>"
                            . "</td>";
                } else {
                    $textLatLon = "<td class=\"out\"></td>";
                }

				if ($row['source_id'] == '29') {
                    $textColl = "<td class=\"outCenter\" title=\"" . htmlspecialchars($row['collection']) . "\">"
                              . htmlspecialchars($row['HerbNummer']) . "</td>";
				} else {
                    $textColl = "<td class=\"outCenter\" title=\"" . htmlspecialchars($row['collection']) . "\">"
                              . htmlspecialchars($row['coll_short']) . " " . htmlspecialchars($row['HerbNummer']) . "</td>";
				};

                echo "<tr class=\"" . (($nrSel == $nr) ? "outMark" : "out") . "\">"
                   . "<td class=\"out\">$digitalImage</td>"
                   . "<td class=\"out\">"
                   . "<a href=\"editSpecimens.php?sel=" . htmlentities("<" . $row['specimen_ID'] . ">") . "&nr=$nr&ptid=0\">"
                   . htmlspecialchars(taxonItem($row)) . "</a></td>"
                   . "<td class=\"out\">" . htmlspecialchars(collectorItem($row)) . "</td>"
                   . "<td class=\"outNobreak\">" . htmlspecialchars($row['Datum']) . "</td>"
                   . $textLatLon
                   . "<td class=\"out\">" . locationItem($row) . "</td>"
                   . "<td class=\"out\">" . htmlspecialchars($row['typus_lat']) . "</td>"
               	   . $textColl;
                if ($swBatch) {
                    echo "<td class=\"out\" style=\"text-align: center\">";
                    $resultDummy = dbi_query("SELECT t1.remarks FROM api.tbl_api_batches AS t1, api.tbl_api_specimens AS t2 WHERE t2.specimen_ID = '" . $row['specimen_ID'] . "' AND t1.batchID = t2.batchID_fk");
                    if (mysqli_num_rows($resultDummy) > 0) {
                        //echo "&radic;";
                        $rowDummy = mysqli_fetch_array($resultDummy);
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

    $output = ob_get_clean();

    if ($bInitialize) {
        $response->script("
            $('.specimen_pagination').pagination( " . $found_rows . ", {
                items_per_page: $itemsPerPage,
                num_edge_entries: 1,
                callback: function(page, container) {
                    jaxon_listSpecimens( page, 0, $itemsPerPage );

                    return false;
                }
            } );
        ");
    }

    $response->assign('specimen_entries', 'innerHTML', $output);

    return $response;
}

function collectorItem($row) {
    $text = $row['Sammler'];
    if (strstr($row['Sammler_2'], "&") || strstr($row['Sammler_2'], "et al.")) {
        $text .= " et al.";
    }
    elseif ($row['Sammler_2']) {
        $text .= " & " . $row['Sammler_2'];
    }
    if ($row['series_number']) {
        if ($row['Nummer']) {
            $text .= " " . $row['Nummer'];
        }
        if ($row['alt_number'] && trim($row['alt_number']) != "s.n.") {
            $text .= " " . $row['alt_number'];
        }
        if ($row['series']) {
            $text .= " " . $row['series'];
        }
        $text .= " " . $row['series_number'];
    } else {
        if ($row['series']) {
            $text .= " " . $row['series'];
        }
        if ($row['Nummer']) {
            $text .= " " . $row['Nummer'];
        }
        if ($row['alt_number']) {
            $text .= " " . $row['alt_number'];
        }
        if (strstr($row['alt_number'], "s.n.")) {
            $text .= " [" . $row['Datum'] . "]";
        }
    }

    return $text;
}

function locationItem($row) {
    $text = "";
    if (trim($row['nation_engl'])) {
        $text = "<span style=\"background-color:white;\">" . htmlspecialchars(trim($row['nation_engl'])) . "</span>";
    }
    if (trim($row['provinz'])) {
        if (strlen($text) > 0) {
            $text .= ". ";
        }
        $text .= "<span style=\"background-color:white;\">" . htmlspecialchars(trim($row['provinz'])) . "</span>";
    }
    if (trim($row['Fundort']) && $row['collectionID'] != 12) {
        if (strlen($text) > 0) {
            $text .= ". ";
        }
        $text .= htmlspecialchars(trim($row['Fundort']));
    }

    return $text;
}
