<?php

error_reporting(0);
session_start();
require("inc/connect.php");
require("inc/herbardb_input_functions.php");
require("inc/PHPExcel/PHPExcel.php");

$select = filter_input(INPUT_GET, 'select');
if ($select != 'labels' && $select != 'list') {
    die();
}

function collection($Sammler, $Sammler_2, $series, $series_number, $Nummer, $alt_number, $Datum) {
    $text = $Sammler;
    if (strstr($Sammler_2, "&") || strstr($Sammler_2, "et al.")) {
        $text .= " et al.";
    }
    elseif ($Sammler_2) {
        $text .= " & " . $Sammler_2;
    }
    if ($series_number) {
        if ($Nummer) {
            $text .= " " . $Nummer;
        }
        if ($alt_number && $alt_number != "s.n.") {
            $text .= " " . $alt_number;
        }
        if ($series) {
            $text .= " " . $series;
        }
        $text .= " " . $series_number;
    }
    else {
        if ($series) {
            $text .= " " . $series;
        }
        if ($Nummer) {
            $text .= " " . $Nummer;
        }
        if ($alt_number) {
            $text .= " " . $alt_number;
        }
        if (strstr($alt_number, "s.n.")) {
            $text .= " [" . $Datum . "]";
        }
    }

    return $text;
}

function makeTaxon($taxonID) {
    // prepare variables
    $taxonID = intval($taxonID);
    $scientificName = null;

    // prepare query
    $sql = "SELECT `herbar_view`.GetScientificName( $taxonID, 0 ) AS `scientificName`";
    $result = mysql_query($sql);

    // check if we found a result
    if (mysql_num_rows($result) > 0) {
        $row = mysql_fetch_array($result);
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
           ts.synID, ts.taxonID, ts.statusID, `herbar_view`.GetScientificName(ts.taxonID, 0) AS `scientificName`
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
    $result = mysql_query($sql);

    $text = "";
    while ($row = mysql_fetch_array($result)) {
        if ($row['synID']) {
            /*            $sql3 = "SELECT ts.statusID, tg.genus,
              ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
              ta4.author author4, ta5.author author5,
              te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
              te4.epithet epithet4, te5.epithet epithet5, taxonID
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
              $result3 = mysql_query($sql3);
              $row3 = mysql_fetch_array($result3);
              $accName = taxonWithHybrids($row3); */
            $accName = makeTaxon($row['synID']);
        }
        else {
            $accName = "";
        }

        $sql2 = "SELECT l.suptitel, la.autor, l.periodicalID, lp.periodical, l.vol, l.part,
              ti.paginae, ti.figures, l.jahr, l.citationID, l.pp, ti.taxonID, le.autor as editor
             FROM tbl_tax_index ti
              INNER JOIN tbl_lit l ON l.citationID=ti.citationID
              LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID=l.periodicalID
              LEFT JOIN tbl_lit_authors la ON la.autorID=l.autorID
              LEFT JOIN tbl_lit_authors le ON le.autorID=l.editorsID
             WHERE ti.taxonID='" . $row['taxonID'] . "'";
        $result2 = mysql_query($sql2);

        //$text .= $row['typus_lat'] . " for " . taxonWithHybrids($row) . " ";
        $text .= $row['typus_lat'] . " for " . $row['scientificName'] . " ";
        while ($row2 = mysql_fetch_array($result2)) {
            $text .= protolog($row2) . " ";
        }
        if (strlen($accName) > 0) {
            $text .= "Current Name: $accName ";
        }
    }

    return $text;
}

// extend memory and timeout settings
ini_set("memory_limit", "4096M");
set_time_limit(0);

// SQLiteCache hält die Cell-Data nicht im Speicher
if (!PHPExcel_Settings::setCacheStorageMethod(PHPExcel_CachedObjectStorageFactory::cache_in_memory_gzip)) {
    die('Caching not available!');
}

// Create new PHPExcel object
$objPHPExcel = new PHPExcel();
$objPHPExcelWorksheet = $objPHPExcel->setActiveSheetIndex(0);

// add header info
$objPHPExcelWorksheet->setCellValue('A1', 'Specimen_ID')
        ->setCellValue('B1', 'observation')
        ->setCellValue('C1', 'API')
        ->setCellValue('D1', 'dig_image')
        ->setCellValue('E1', 'dig_img_obs')
        ->setCellValue('F1', 'checked')
        ->setCellValue('G1', 'accessible')
        ->setCellValue('H1', 'Institution_Code')
        ->setCellValue('I1', 'HerbariumNr_BarCode')
        ->setCellValue('J1', 'institution_subcollection')
        ->setCellValue('K1', 'Collection_Number')
        ->setCellValue('L1', 'Type_information')
        ->setCellValue('M1', 'Typified_by')
        ->setCellValue('N1', 'Taxon')
        ->setCellValue('O1', 'status')
        ->setCellValue('P1', 'Genus')
        ->setCellValue('Q1', 'Species')
        ->setCellValue('R1', 'Author')
        ->setCellValue('S1', 'Rank')
        ->setCellValue('T1', 'Infra_spec')
        ->setCellValue('U1', 'Infra_author')
        ->setCellValue('V1', 'Family')
        ->setCellValue('W1', 'Garden')
        ->setCellValue('X1', 'voucher')
        ->setCellValue('Y1', 'Collection')
        ->setCellValue('Z1', 'First_collector')
        ->setCellValue('AA1', 'First_collectors_number')
        ->setCellValue('AB1', 'Add_collectors')
        ->setCellValue('AC1', 'Alt_number')
        ->setCellValue('AD1', 'Series')
        ->setCellValue('AE1', 'Series_number')
        ->setCellValue('AF1', 'Coll_Date')
        ->setCellValue('AG1', 'Coll_Date_2')
        ->setCellValue('AH1', 'Country')
        ->setCellValue('AI1', 'Province')
        ->setCellValue('AJ1', 'geonames')
        ->setCellValue('AK1', 'Latitude')
        ->setCellValue('AL1', 'Latitude_DMS')
        ->setCellValue('AM1', 'Lat_Hemisphere')
        ->setCellValue('AN1', 'Lat_degree')
        ->setCellValue('AO1', 'Lat_minute')
        ->setCellValue('AP1', 'Lat_second')
        ->setCellValue('AQ1', 'Longitude')
        ->setCellValue('AR1', 'Longitude_DMS')
        ->setCellValue('AS1', 'Long_Hemisphere')
        ->setCellValue('AT1', 'Long_degree')
        ->setCellValue('AU1', 'Long_minute')
        ->setCellValue('AV1', 'Long_second')
        ->setCellValue('AW1', 'exactness')
        ->setCellValue('AX1', 'Altitude_lower')
        ->setCellValue('AY1', 'Altitude_higher')
        ->setCellValue('AZ1', 'Quadrant')
        ->setCellValue('BA1', 'Quadrant_sub')
        ->setCellValue('BB1', 'Location')
        ->setCellValue('BC1', 'det_rev_conf')
        ->setCellValue('BD1', 'ident_history')
        ->setCellValue('BE1', 'annotations')
        ->setCellValue('BF1', 'habitat')
        ->setCellValue('BG1', 'habitus')
        ->setCellValue('BH1', 'aktualdatum')
        ->setCellValue('BI1', 'eingabedatum')
;

$sqlSelect = "SELECT s.specimen_ID, tg.genus, c.Sammler, c2.Sammler_2, ss.series, s.series_number,
               s.Nummer, s.alt_number, s.Datum, s.Datum2, s.Fundort, s.det, s.taxon_alt, s.Bemerkungen,
               s.CollNummer, s.altitude_min, s.altitude_max,
               n.nation_engl, p.provinz, s.Bezirk, s.Fundort, tf.family, tsc.cat_description, si.identification_status, sv.voucher,
               mc.collection, mc.collectionID, mc.coll_short, s.typified, m.source_code,
               s.digital_image, s.digital_image_obs, s.HerbNummer, s.ncbi_accession, s.checked, s.accessible,
               s.Coord_W, s.W_Min, s.W_Sec, s.Coord_N, s.N_Min, s.N_Sec,
               s.Coord_S, s.S_Min, s.S_Sec, s.Coord_E, s.E_Min, s.E_Sec,
               s.quadrant, s.quadrant_sub, s.exactness,
               s.habitat, s.habitus, s.garten,
               s.observation,
               atb.date_supplied, atb.remarks,
               tr.rank_abbr,
               ta.author, ta1.author author1, ta2.author author2, ta3.author author3, ta4.author author4, ta5.author author5,
               te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3, te4.epithet epithet4, te5.epithet epithet5,
               ts.synID, ts.taxonID, ts.statusID, s.aktualdatum, s.eingabedatum ";
$sqlJoin = " LEFT JOIN tbl_specimens_series          ss  ON ss.seriesID = s.seriesID
             LEFT JOIN tbl_specimens_identstatus     si  ON si.identstatusID = s.identstatusID
             LEFT JOIN tbl_specimens_voucher         sv  ON sv.voucherID = s.voucherID
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
             LEFT JOIN `api`.tbl_api_specimens       ats ON ats.specimen_ID = s.specimen_ID
             LEFT JOIN `api`.tbl_api_batches         atb ON atb.batchID = ats.batchID_fk ";

if ($select == 'labels') {
    if (isset($_SESSION['sLabelDate'])) {
        $searchDate = mysql_escape_string(trim($_SESSION['sLabelDate']));
    }
    else {
        $searchDate = '2000-01-01';
    }
    $sqlFrom = " FROM (herbarinput_log.log_specimens ls, tbl_specimens s) ";
    $sqlWhere = " WHERE ls.specimenID = s.specimen_ID
                   AND ls.userID = '" . intval($_SESSION['uid']) . "'
                   AND ls.timestamp BETWEEN '$searchDate' AND ADDDATE('$searchDate', '1')
                  GROUP BY ls.specimenID ";
//    if (isset($_SESSION['sOrder'])) {
//        $sqlWhere .= " ORDER BY " . $_SESSION['sOrder'];
//    }
}
else {
    $sqlFrom = " FROM tbl_specimens s ";
    $sqlWhere = " WHERE 1 ";

    if (empty($_SESSION['sSQLCondition'])) {
        $sqlWhere .= " AND 0 = 1";
    }
    else {
        $sqlWhere .= $_SESSION['sSQLCondition'];
    }
}

//error_log("listSpecimensExport: Running query: " . $sqlSelect . $sqlFrom . $sqlJoin . $sqlWhere);

$resultSpecimens = mysql_query($sqlSelect . $sqlFrom . $sqlJoin . $sqlWhere);

$i = 2;
while (($rowSpecimen = mysql_fetch_array($resultSpecimens)) !== false) {
    $sammler = collection($rowSpecimen['Sammler'], $rowSpecimen['Sammler_2'], $rowSpecimen['series'], $rowSpecimen['series_number'], $rowSpecimen['Nummer'], $rowSpecimen['alt_number'], $rowSpecimen['Datum']);

    if ($rowSpecimen['epithet5']) {
        $infra_spec = $rowSpecimen['epithet5'];
        $infra_author = $rowSpecimen['author5'];
    }
    elseif ($rowSpecimen['epithet4']) {
        $infra_spec = $rowSpecimen['epithet4'];
        $infra_author = $rowSpecimen['author4'];
    }
    elseif ($rowSpecimen['epithet3']) {
        $infra_spec = $rowSpecimen['epithet3'];
        $infra_author = $rowSpecimen['author3'];
    }
    elseif ($rowSpecimen['epithet2']) {
        $infra_spec = $rowSpecimen['epithet2'];
        $infra_author = $rowSpecimen['author2'];
    }
    elseif ($rowSpecimen['epithet1']) {
        $infra_spec = $rowSpecimen['epithet1'];
        $infra_author = $rowSpecimen['author1'];
    }
    else {
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
        $latHemisphere = 'S';
    }
    else if ($rowSpecimen['Coord_N'] > 0 || $rowSpecimen['N_Min'] > 0 || $rowSpecimen['N_Sec'] > 0) {
        $lat = $rowSpecimen['Coord_N'] + $rowSpecimen['N_Min'] / 60 + $rowSpecimen['N_Sec'] / 3600;
        $latDMS = $rowSpecimen['Coord_N'] . "°";
        if (!empty($rowSpecimen['N_Min'])) {
            $latDMS .= ' ' . $rowSpecimen['N_Min'] . "'";
        }
        if (!empty($rowSpecimen['N_Sec'])) {
            $latDMS .= ' ' . $rowSpecimen['N_Sec'] . '"';
        }
        $latDMS .= ' N';
        $latHemisphere = 'N';
    }
    else {
        $lat = $latDMS = $latHemisphere = '';
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
        $lonHemisphere = 'W';
    }
    else if ($rowSpecimen['Coord_E'] > 0 || $rowSpecimen['E_Min'] > 0 || $rowSpecimen['E_Sec'] > 0) {
        $lon = $rowSpecimen['Coord_E'] + $rowSpecimen['E_Min'] / 60 + $rowSpecimen['E_Sec'] / 3600;
        $lonDMS = $rowSpecimen['Coord_E'] . "°";
        if (!empty($rowSpecimen['E_Min'])) {
            $lonDMS .= ' ' . $rowSpecimen['E_Min'] . "'";
        }
        if (!empty($rowSpecimen['E_Sec'])) {
            $lonDMS .= ' ' . $rowSpecimen['E_Sec'] . '"';
        }
        $lonDMS .= ' E';
        $lonHemisphere = 'E';
    }
    else {
        $lon = $lonDMS = $lonHemisphere = '';
    }

    if (strlen($lon) > 0) {
        $lon = "" . number_format(round($lon, 9), 9) . "° ";
    }

    if ($rowSpecimen['date_supplied']) {
        $apibatch = $rowSpecimen['date_supplied'];
        if ($rowSpecimen['remarks']) {
            $apibatch .= " (" . $rowSpecimen['remarks'] . ")";
        }
    }
    else {
        $apibatch = "";
    }

    $objPHPExcelWorksheet->fromArray(array(
        $rowSpecimen['specimen_ID'],
        $rowSpecimen['observation'],
        $apibatch,
        ($rowSpecimen['digital_image']) ? '1' : '',
        ($rowSpecimen['digital_image_obs']) ? '1' : '',
        ($rowSpecimen['checked']) ? '1' : '',
        ($rowSpecimen['accessible']) ? '1' : '',
        $rowSpecimen['source_code'],
        $rowSpecimen['HerbNummer'],
        $rowSpecimen['coll_short'],
        $rowSpecimen['CollNummer'],
        makeTypus(intval($rowSpecimen['specimen_ID'])),
        $rowSpecimen['typified'],
        makeTaxon($rowSpecimen['taxonID']),
        $rowSpecimen['identification_status'],
        $rowSpecimen['genus'],
        $rowSpecimen['epithet'],
        $rowSpecimen['author'],
        $rowSpecimen['rank_abbr'],
        $infra_spec,
        $infra_author,
        $rowSpecimen['family'],
        $rowSpecimen['garten'],
        $rowSpecimen['voucher'],
        $sammler,
        $rowSpecimen['Sammler'],
        $rowSpecimen['Nummer'],
        $rowSpecimen['Sammler_2'],
        $rowSpecimen['alt_number'],
        $rowSpecimen['series'],
        $rowSpecimen['series_number'],
        $rowSpecimen['Datum'],
        $rowSpecimen['Datum2'],
        $rowSpecimen['nation_engl'],
        $rowSpecimen['provinz'],
        $rowSpecimen['Bezirk'],
        $lat,
        $latDMS,
        $latHemisphere,
        ($latHemisphere == 'N') ? $rowSpecimen['Coord_N'] : $rowSpecimen['Coord_S'],
        ($latHemisphere == 'N') ? $rowSpecimen['N_Min'] : $rowSpecimen['S_Min'],
        ($latHemisphere == 'N') ? $rowSpecimen['N_Sec'] : $rowSpecimen['S_Sec'],
        $lon,
        $lonDMS,
        $lonHemisphere,
        ($lonHemisphere == 'E') ? $rowSpecimen['Coord_E'] : $rowSpecimen['Coord_W'],
        ($lonHemisphere == 'E') ? $rowSpecimen['E_Min'] : $rowSpecimen['W_Min'],
        ($lonHemisphere == 'E') ? $rowSpecimen['E_Sec'] : $rowSpecimen['W_Sec'],
        $rowSpecimen['exactness'],
        $rowSpecimen['altitude_min'],
        $rowSpecimen['altitude_max'],
        $rowSpecimen['quadrant'],
        $rowSpecimen['quadrant_sub'],
        $rowSpecimen['Fundort'],
        $rowSpecimen['det'],
        $rowSpecimen['taxon_alt'],
        $rowSpecimen['Bemerkungen'],
        $rowSpecimen['habitat'],
        $rowSpecimen['habitus'],
        $rowSpecimen['aktualdatum'],
        $rowSpecimen['eingabedatum']
            ), null, 'A' . $i);


    /*
      $objPHPExcelWorksheet->setCellValue('A' . $i, $rowSpecimen['specimen_ID'])
      ->setCellValue('B' . $i, $rowSpecimen['observation'])
      ->setCellValue('C' . $i, $apibatch)
      ->setCellValue('D' . $i, ($rowSpecimen['digital_image']) ? '1' : '')
      ->setCellValue('E' . $i, ($rowSpecimen['digital_image_obs']) ? '1' : '')
      ->setCellValue('F' . $i, ($rowSpecimen['checked']) ? '1' : '')
      ->setCellValue('G' . $i, ($rowSpecimen['accessible']) ? '1' : '')
      ->setCellValue('H' . $i, $rowSpecimen['source_code'])
      ->setCellValue('I' . $i, $rowSpecimen['HerbNummer'])
      ->setCellValue('J' . $i, $rowSpecimen['coll_short'])
      ->setCellValue('K' . $i, $rowSpecimen['CollNummer'])
      ->setCellValue('L' . $i, makeTypus(intval($rowSpecimen['specimen_ID'])))
      ->setCellValue('M' . $i, $rowSpecimen['typified'])
      ->setCellValue('N' . $i, makeTaxon($rowSpecimen['taxonID']))
      ->setCellValue('O' . $i, $rowSpecimen['identification_status'])
      ->setCellValue('P' . $i, $rowSpecimen['genus'])
      ->setCellValue('Q' . $i, $rowSpecimen['epithet'])
      ->setCellValue('R' . $i, $rowSpecimen['author'])
      ->setCellValue('S' . $i, $rowSpecimen['rank_abbr'])
      ->setCellValue('T' . $i, $infra_spec)
      ->setCellValue('U' . $i, $infra_author)
      ->setCellValue('V' . $i, $rowSpecimen['family'])
      ->setCellValue('W' . $i, $rowSpecimen['garten'])
      ->setCellValue('X' . $i, $rowSpecimen['voucher'])
      ->setCellValue('Y' . $i, $sammler)
      ->setCellValue('Z' . $i, $rowSpecimen['Sammler'])
      ->setCellValue('AA' . $i, $rowSpecimen['Nummer'])
      ->setCellValue('AB' . $i, $rowSpecimen['Sammler_2'])
      ->setCellValue('AC' . $i, $rowSpecimen['alt_number'])
      ->setCellValue('AD' . $i, $rowSpecimen['series'])
      ->setCellValue('AE' . $i, $rowSpecimen['series_number'])
      ->setCellValue('AF' . $i, $rowSpecimen['Datum'])
      ->setCellValue('AG' . $i, $rowSpecimen['Datum2'])
      ->setCellValue('AH' . $i, $rowSpecimen['nation_engl'])
      ->setCellValue('AI' . $i, $rowSpecimen['provinz'])
      ->setCellValue('AJ' . $i, $rowSpecimen['Bezirk'])
      ->setCellValue('AK' . $i, $lat)
      ->setCellValue('AL' . $i, $latDMS)
      ->setCellValue('AM' . $i, $latHemisphere)
      ->setCellValue('AN' . $i, ($latHemisphere == 'N') ? $rowSpecimen['Coord_N'] : $rowSpecimen['Coord_S'])
      ->setCellValue('AO' . $i, ($latHemisphere == 'N') ? $rowSpecimen['N_Min'] : $rowSpecimen['S_Min'])
      ->setCellValue('AP' . $i, ($latHemisphere == 'N') ? $rowSpecimen['N_Sec'] : $rowSpecimen['S_Sec'])
      ->setCellValue('AQ' . $i, $lon)
      ->setCellValue('AR' . $i, $lonDMS)
      ->setCellValue('AS' . $i, $lonHemisphere)
      ->setCellValue('AT' . $i, ($lonHemisphere == 'E') ? $rowSpecimen['Coord_E'] : $rowSpecimen['Coord_W'])
      ->setCellValue('AU' . $i, ($lonHemisphere == 'E') ? $rowSpecimen['E_Min'] : $rowSpecimen['W_Min'])
      ->setCellValue('AV' . $i, ($lonHemisphere == 'E') ? $rowSpecimen['E_Sec'] : $rowSpecimen['W_Sec'])
      ->setCellValue('AW' . $i, $rowSpecimen['exactness'])
      ->setCellValue('AX' . $i, $rowSpecimen['altitude_min'])
      ->setCellValue('AY' . $i, $rowSpecimen['altitude_max'])
      ->setCellValue('AZ' . $i, $rowSpecimen['quadrant'])
      ->setCellValue('BA' . $i, $rowSpecimen['quadrant_sub'])
      ->setCellValue('BB' . $i, $rowSpecimen['Fundort'])
      ->setCellValue('BC' . $i, $rowSpecimen['det'])
      ->setCellValue('BD' . $i, $rowSpecimen['taxon_alt'])
      ->setCellValue('BE' . $i, $rowSpecimen['Bemerkungen'])
      ->setCellValue('BF' . $i, $rowSpecimen['habitat'])
      ->setCellValue('BG' . $i, $rowSpecimen['habitus']);
     */
    $i++;
}

switch (filter_input(INPUT_GET, 'type')) {
    case 'csv':
        // Redirect output to a client’s web browser (CSV)
        header("Content-type: text/csv; charset=utf-8");
        header("Content-Disposition: attachment; filename=specimens_download.csv");
        header('Cache-Control: max-age=0');
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'CSV');
        $objWriter->save('php://output');
        break;
    case 'ods':
        // Redirect output to a client’s web browser (OpenDocument)
        header('Content-Type: application/vnd.oasis.opendocument.spreadsheet');
        header('Content-Disposition: attachment;filename="specimens_download.ods"');
        header('Cache-Control: max-age=0');
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'OpenDocument');
        $objWriter->save('php://output');
        break;
    default:
        // Redirect output to a client’s web browser (Excel2007)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="specimens_download.xlsx"');
        header('Cache-Control: max-age=0');
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
}
exit;
