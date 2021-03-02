<?php
error_reporting(0);
session_start();
require("inc/connect.php");
require("inc/herbardb_input_functions.php");
require("./inc/PHPExcel/PHPExcel.php");

function collection($Sammler, $Sammler_2, $series, $series_number, $Nummer, $alt_number, $Datum) {
    $text = $Sammler;
    if (strstr($Sammler_2, "&") || strstr($Sammler_2, "et al.")) {
        $text .= " et al.";
    } elseif ($Sammler_2) {
        $text .= " & " . $Sammler_2;
    }
    if ($series_number) {
        if ($Nummer)
            $text .= " " . $Nummer;
        if ($alt_number && $alt_number != "s.n.")
            $text .= " " . $alt_number;
        if ($series)
            $text .= " " . $series;
        $text .= " " . $series_number;
    } else {
        if ($series)
            $text .= " " . $series;
        if ($Nummer)
            $text .= " " . $Nummer;
        if ($alt_number)
            $text .= " " . $alt_number;
        if (strstr($alt_number, "s.n."))
            $text .= " [" . $Datum . "]";
    }

    return $text;
}

function makeTaxon($taxonID) {
    // prepare variables
    $taxonID = intval($taxonID);
    $scientificName = null;

    // prepare query
    $sql = "SELECT `herbar_view`.GetScientificName( $taxonID, 0 ) AS `scientificName`";
    $result = dbi_query($sql);

    // check if we found a result
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_array($result);
        $scientificName = $row['scientificName'];
    }

    return $scientificName;
}

function makeTypus($ID) {

    $sql = "SELECT typus_lat, tg.genus,
           ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
           ta4.author author4, ta5.author author5,
           te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
           te4.epithet epithet4, te5.epithet epithet5,
           ts.synID, ts.taxonID, ts.statusID
          FROM (tbl_specimens_types tst, tbl_typi tt, tbl_tax_species ts)
           LEFT JOIN tbl_tax_authors ta ON ta.authorID=ts.authorID
           LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID=ts.subspecies_authorID
           LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID=ts.variety_authorID
           LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID=ts.subvariety_authorID
           LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID=ts.forma_authorID
           LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID=ts.subforma_authorID
           LEFT JOIN tbl_tax_epithets te ON te.epithetID=ts.speciesID
           LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID=ts.subspeciesID
           LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID=ts.varietyID
           LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID=ts.subvarietyID
           LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID=ts.formaID
           LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID=ts.subformaID
           LEFT JOIN tbl_tax_genera tg ON tg.genID=ts.genID
          WHERE tst.typusID=tt.typusID
           AND tst.taxonID=ts.taxonID
           AND specimenID='" . intval($ID) . "'";
    $result = dbi_query($sql);

    $text = "";
    while ($row = mysqli_fetch_array($result)) {
        if ($row['synID']) {
            $sql3 = "SELECT ts.statusID, tg.genus,
                ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
                ta4.author author4, ta5.author author5,
                te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
                te4.epithet epithet4, te5.epithet epithet5
               FROM tbl_tax_species ts
                LEFT JOIN tbl_tax_authors ta ON ta.authorID=ts.authorID
                LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID=ts.subspecies_authorID
                LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID=ts.variety_authorID
                LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID=ts.subvariety_authorID
                LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID=ts.forma_authorID
                LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID=ts.subforma_authorID
                LEFT JOIN tbl_tax_epithets te ON te.epithetID=ts.speciesID
                LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID=ts.subspeciesID
                LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID=ts.varietyID
                LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID=ts.subvarietyID
                LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID=ts.formaID
                LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID=ts.subformaID
                LEFT JOIN tbl_tax_genera tg ON tg.genID=ts.genID
               WHERE taxonID=" . $row['synID'];
            $result3 = dbi_query($sql3);
            $row3 = mysqli_fetch_array($result3);
            $accName = taxonWithHybrids($row3);
        } else
            $accName = "";

        $sql2 = "SELECT l.suptitel, la.autor, l.periodicalID, lp.periodical, l.vol, l.part,
              ti.paginae, ti.figures, l.jahr
             FROM tbl_tax_index ti
              INNER JOIN tbl_lit l ON l.citationID=ti.citationID
              LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID=l.periodicalID
              LEFT JOIN tbl_lit_authors la ON la.autorID=l.editorsID
             WHERE ti.taxonID='" . $row['taxonID'] . "'";
        $result2 = dbi_query($sql2);

        $text .= $row['typus_lat'] . " for " . taxonWithHybrids($row) . " ";
        while ($row2 = mysqli_fetch_array($result2))
            $text .= protolog($row2) . " ";
        if (strlen($accName) > 0)
            $text .= "Current Name: $accName ";
    }

    return $text;
}
// extend memory and timeout settings

ini_set("memory_limit", "512M");
set_time_limit(0);

// Create new PHPExcel object
$objPHPExcel = new PHPExcel();
$objPHPExcelWorksheet = $objPHPExcel->setActiveSheetIndex(0);

// add header info
$objPHPExcelWorksheet->setCellValue('A1', 'Specimen ID')
        ->setCellValue('B1', 'Herbarium-Number/BarCode')
        ->setCellValue('C1', 'Collection')
        ->setCellValue('D1', 'Collection Number')
        ->setCellValue('E1', 'Type information')
        ->setCellValue('F1', 'Typified by')
        ->setCellValue('G1', 'Taxon')
        ->setCellValue('H1', 'Genus')
        ->setCellValue('I1', 'Species')
        ->setCellValue('J1', 'Author')
        ->setCellValue('K1', 'Rank')
        ->setCellValue('L1', 'Infra_spec')
        ->setCellValue('M1', 'Infra_author')
        ->setCellValue('N1', 'Family')
        ->setCellValue('O1', 'Collection')
        ->setCellValue('P1', 'First_collector')
        ->setCellValue('Q1', 'First_collectors_number')
        ->setCellValue('R1', 'Add_collectors')
        ->setCellValue('S1', 'Alt_number')
        ->setCellValue('T1', 'Series')
        ->setCellValue('U1', 'Series_number')
        ->setCellValue('V1', 'Date')
        ->setCellValue('W1', 'Date_2')
        ->setCellValue('X1', 'Country')
        ->setCellValue('Y1', 'Province')
        ->setCellValue('Z1', 'Latitude')
        ->setCellValue('AA1', 'Latitude_DMS')
        ->setCellValue('AB1', 'Longitude')
        ->setCellValue('AC1', 'Longitude_DMS')
        ->setCellValue('AD1', 'Altitude lower')
        ->setCellValue('AE1', 'Altitude higher')
        ->setCellValue('AF1', 'Location')
        ->setCellValue('AG1', 'det./rev./conf./assigned')
        ->setCellValue('AH1', 'ident. history')
        ->setCellValue('AI1', 'annotations')
        ->setCellValue('AJ1', 'habitat')
        ->setCellValue('AK1', 'habitus');

if (isset($_SESSION['sLabelDate'])) {
    $searchDate = dbi_escape_string(trim($_SESSION['sLabelDate']));
} else {
    $searchDate = '2000-01-01';
}
$sql = "SELECT s.specimen_ID, tg.genus, c.Sammler, c2.Sammler_2, ss.series, s.series_number,
         s.Nummer, s.alt_number, s.Datum, s.Datum2, s.Fundort, s.det, s.taxon_alt, s.Bemerkungen,
         s.CollNummer, s.altitude_min, s.altitude_max,
         n.nation_engl, p.provinz, s.Fundort, tf.family, tsc.cat_description,
         mc.collection, mc.collectionID, mc.coll_short, s.typified, m.source_code,
         s.digital_image, s.digital_image_obs, s.HerbNummer, s.ncbi_accession,
         s.Coord_W, s.W_Min, s.W_Sec, s.Coord_N, s.N_Min, s.N_Sec,
         s.Coord_S, s.S_Min, s.S_Sec, s.Coord_E, s.E_Min, s.E_Sec,
         s.habitat, s.habitus,
         tr.rank_abbr,
         ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
         ta4.author author4, ta5.author author5,
         te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
         te4.epithet epithet4, te5.epithet epithet5,
         ts.synID, ts.taxonID, ts.statusID
        FROM (herbarinput_log.log_specimens ls, tbl_specimens s)
         LEFT JOIN tbl_typi                      t   ON t.typusID = s.typusID
         LEFT JOIN tbl_specimens_series          ss  ON ss.seriesID = s.seriesID
         LEFT JOIN tbl_management_collections    mc  ON mc.collectionID = s.collectionID
         LEFT JOIN meta                          m   ON m.source_id = mc.source_id
         LEFT JOIN tbl_geo_nation                n   ON n.NationID = s.NationID
         LEFT JOIN tbl_geo_province              p   ON p.provinceID = s.provinceID
         LEFT JOIN tbl_collector                 c   ON c.SammlerID = s.SammlerID
         LEFT JOIN tbl_collector_2               c2  ON c2.Sammler_2ID = s.Sammler_2ID
         LEFT JOIN tbl_tax_species               ts  ON ts.taxonID = s.taxonID
         LEFT JOIN tbl_tax_authors               ta  ON ta.authorID = ts.authorID
         LEFT JOIN tbl_tax_authors               ta1 ON ta1.authorID = ts.subspecies_authorID
         LEFT JOIN tbl_tax_authors               ta2 ON ta2.authorID = ts.variety_authorID
         LEFT JOIN tbl_tax_authors               ta3 ON ta3.authorID = ts.subvariety_authorID
         LEFT JOIN tbl_tax_authors               ta4 ON ta4.authorID = ts.forma_authorID
         LEFT JOIN tbl_tax_authors               ta5 ON ta5.authorID = ts.subforma_authorID
         LEFT JOIN tbl_tax_epithets              te  ON te.epithetID = ts.speciesID
         LEFT JOIN tbl_tax_epithets              te1 ON te1.epithetID = ts.subspeciesID
         LEFT JOIN tbl_tax_epithets              te2 ON te2.epithetID = ts.varietyID
         LEFT JOIN tbl_tax_epithets              te3 ON te3.epithetID = ts.subvarietyID
         LEFT JOIN tbl_tax_epithets              te4 ON te4.epithetID = ts.formaID
         LEFT JOIN tbl_tax_epithets              te5 ON te5.epithetID = ts.subformaID
         LEFT JOIN tbl_tax_rank                  tr  ON tr.tax_rankID = ts.tax_rankID
         LEFT JOIN tbl_tax_genera                tg  ON tg.genID = ts.genID
         LEFT JOIN tbl_tax_families              tf  ON tf.familyID = tg.familyID
         LEFT JOIN tbl_tax_systematic_categories tsc ON tf.categoryID = tsc.categoryID
        WHERE ls.specimenID = s.specimen_ID
         AND ls.userID = '" . intval($_SESSION['uid']) . "'
         AND ls.timestamp BETWEEN '$searchDate' AND ADDDATE('$searchDate', '1')
        GROUP BY ls.specimenID";
if (isset($_SESSION['sOrder'])) {
    $sql .= " ORDER BY " . $_SESSION['sOrder'];
}

$resultSpecimens = dbi_query($sql);

$i = 2;
while (($rowSpecimen = mysqli_fetch_array($resultSpecimens)) !== false) {
    $sammler = collection($rowSpecimen['Sammler'], $rowSpecimen['Sammler_2'], $rowSpecimen['series'], $rowSpecimen['series_number'], $rowSpecimen['Nummer'], $rowSpecimen['alt_number'], $rowSpecimen['Datum']);

    if ($rowSpecimen['epithet5']) {
        $infra_spec = $rowSpecimen['epithet5'];
        $infra_author = $rowSpecimen['author5'];
    } elseif ($rowSpecimen['epithet4']) {
        $infra_spec = $rowSpecimen['epithet4'];
        $infra_author = $rowSpecimen['author4'];
    } elseif ($rowSpecimen['epithet3']) {
        $infra_spec = $rowSpecimen['epithet3'];
        $infra_author = $rowSpecimen['author3'];
    } elseif ($rowSpecimen['epithet2']) {
        $infra_spec = $rowSpecimen['epithet2'];
        $infra_author = $rowSpecimen['author2'];
    } elseif ($rowSpecimen['epithet1']) {
        $infra_spec = $rowSpecimen['epithet1'];
        $infra_author = $rowSpecimen['author1'];
    } else {
        $infra_spec = '';
        $infra_author = '';
    }

    if ($rowSpecimen['Coord_S'] > 0 || $rowSpecimen['S_Min'] > 0 || $rowSpecimen['S_Sec'] > 0) {
        $lat = -($rowSpecimen['Coord_S'] + $rowSpecimen['S_Min'] / 60 + $rowSpecimen['S_Sec'] / 3600);
        $latDMS = $rowSpecimen['Coord_S'] . "°";
        if (!empty($rowSpecimen['S_Min'])) {
            $latDMS .= ' ' . $rowSpecimen['S_Min'] . "'";
        }
        if (!empty($rowSpecimen['S_Sec'])) {
            $latDMS .= ' ' . $rowSpecimen['S_Sec'] . '"';
        }
        $latDMS .= ' S';
    } else if ($rowSpecimen['Coord_N'] > 0 || $rowSpecimen['N_Min'] > 0 || $rowSpecimen['N_Sec'] > 0) {
        $lat = $rowSpecimen['Coord_N'] + $rowSpecimen['N_Min'] / 60 + $rowSpecimen['N_Sec'] / 3600;
        $latDMS = $rowSpecimen['Coord_N'] . "°";
        if (!empty($rowSpecimen['N_Min'])) {
            $latDMS .= ' ' . $rowSpecimen['N_Min'] . "'";
        }
        if (!empty($rowSpecimen['N_Sec'])) {
            $latDMS .= ' ' . $rowSpecimen['N_Sec'] . '"';
        }
        $latDMS .= ' N';
    } else {
        $lat = $latDMS = '';
    }
    if (strlen($lat) > 0) {
        $lat = "" . number_format(round($lat, 9), 9) . "° ";
    }

    if ($rowSpecimen['Coord_W'] > 0 || $rowSpecimen['W_Min'] > 0 || $rowSpecimen['W_Sec'] > 0) {
        $lon = -($rowSpecimen['Coord_W'] + $rowSpecimen['W_Min'] / 60 + $rowSpecimen['W_Sec'] / 3600);
        $lonDMS = $rowSpecimen['Coord_W'] . "°";
        if (!empty($rowSpecimen['W_Min'])) {
            $lonDMS .= ' ' . $rowSpecimen['W_Min'] . "'";
        }
        if (!empty($rowSpecimen['W_Sec'])) {
            $lonDMS .= ' ' . $rowSpecimen['W_Sec'] . '"';
        }
        $lonDMS .= ' W';
    } else if ($rowSpecimen['Coord_E'] > 0 || $rowSpecimen['E_Min'] > 0 || $rowSpecimen['E_Sec'] > 0) {
        $lon = $rowSpecimen['Coord_E'] + $rowSpecimen['E_Min'] / 60 + $rowSpecimen['E_Sec'] / 3600;
        $lonDMS = $rowSpecimen['Coord_E'] . "°";
        if (!empty($rowSpecimen['E_Min'])) {
            $lonDMS .= ' ' . $rowSpecimen['E_Min'] . "'";
        }
        if (!empty($rowSpecimen['E_Sec'])) {
            $lonDMS .= ' ' . $rowSpecimen['E_Sec'] . '"';
        }
        $lonDMS .= ' E';
    } else {
        $lon = $lonDMS = '';
    }

    if (strlen($lon) > 0) {
        $lon = "" . number_format(round($lon, 9), 9) . "° ";
    }

    $objPHPExcelWorksheet->setCellValue('A' . $i, $rowSpecimen['specimen_ID'])
        ->setCellValue('B' . $i, $rowSpecimen['HerbNummer'])
        ->setCellValue('C' . $i, $rowSpecimen['coll_short'])
        ->setCellValue('D' . $i, $rowSpecimen['CollNummer'])
        ->setCellValue('E' . $i, makeTypus(intval($rowSpecimen['specimen_ID'])))
        ->setCellValue('F' . $i, $rowSpecimen['typified'])
        ->setCellValue('G' . $i, makeTaxon($rowSpecimen['taxonID']))
        ->setCellValue('H' . $i, $rowSpecimen['genus'])
        ->setCellValue('I' . $i, $rowSpecimen['epithet'])
        ->setCellValue('J' . $i, $rowSpecimen['author'])
        ->setCellValue('K' . $i, $rowSpecimen['rank_abbr'])
        ->setCellValue('L' . $i, $infra_spec)
        ->setCellValue('M' . $i, $infra_author)
        ->setCellValue('N' . $i, $rowSpecimen['family'])
        ->setCellValue('O' . $i, $sammler)
        ->setCellValue('P' . $i, $rowSpecimen['Sammler'])
        ->setCellValue('Q' . $i, $rowSpecimen['Nummer'])
        ->setCellValue('R' . $i, $rowSpecimen['Sammler_2'])
        ->setCellValue('S' . $i, $rowSpecimen['alt_number'])
        ->setCellValue('T' . $i, $rowSpecimen['series'])
        ->setCellValue('U' . $i, $rowSpecimen['series_number'])
        ->setCellValue('V' . $i, $rowSpecimen['Datum'])
        ->setCellValue('W' . $i, $rowSpecimen['Datum2'])
        ->setCellValue('X' . $i, $rowSpecimen['nation_engl'])
        ->setCellValue('Y' . $i, $rowSpecimen['provinz'])
        ->setCellValue('Z' . $i, $lat)
        ->setCellValue('AA' . $i, $latDMS)
        ->setCellValue('AB' . $i, $lon)
        ->setCellValue('AC' . $i, $lonDMS)
        ->setCellValue('AD' . $i, $rowSpecimen['altitude_min'])
        ->setCellValue('AE' . $i, $rowSpecimen['altitude_max'])
        ->setCellValue('AF' . $i, $rowSpecimen['Fundort'])
        ->setCellValue('AG' . $i, $rowSpecimen['det'])
        ->setCellValue('AH' . $i, $rowSpecimen['taxon_alt'])
        ->setCellValue('AI' . $i, $rowSpecimen['Bemerkungen'])
        ->setCellValue('AJ' . $i, $rowSpecimen['habitat'])
        ->setCellValue('AK' . $i, $rowSpecimen['habitus']);

    $i++;
}

//error_log(var_export($i, true));

// Redirect output to a client’s web browser (Excel2007)
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="specimens_labels_download.xlsx"');
header('Cache-Control: max-age=0');

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save('php://output');