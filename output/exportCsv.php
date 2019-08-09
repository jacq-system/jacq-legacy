<?php
session_start();
require("inc/functions.php");
require("inc/PHPExcel/PHPExcel.php");

function protolog($row)
{
    $text = "";
    if ($row['suptitel']) {
        $text .= "in ".$row['autor'].": ".$row['suptitel']." ";
    }
    if ($row['periodicalID']) {
        $text .= $row['periodical'];
    }
    $text .= " ".$row['vol'];
    if ($row['part']) {
        $text .= " (".$row['part'].")";
    }
    $text .= ": ".$row['paginae'];
    if ($row['figures']) {
        $text .= "; ".$row['figures'];
    }
    $text .= " (".$row['jahr'].")";

    return $text;
}

function makeTypus($ID)
{
    global $dbLink;

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
             AND specimenID='".intval($ID)."'";
    $result = $dbLink->query($sql);

    $text = "";
    while ($row = $result->fetch_array()) {
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
                     WHERE taxonID=".$row['synID'];
            $result3 = $dbLink->query($sql3);
            $row3 = $result3->fetch_array();
            $accName = taxonWithHybrids($row3);
        } else {
            $accName = "";
        }

        $sql2 = "SELECT l.suptitel, la.autor, l.periodicalID, lp.periodical, l.vol, l.part,
                  ti.paginae, ti.figures, l.jahr
                 FROM tbl_tax_index ti
                  INNER JOIN tbl_lit l ON l.citationID=ti.citationID
                  LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID=l.periodicalID
                  LEFT JOIN tbl_lit_authors la ON la.autorID=l.editorsID
                 WHERE ti.taxonID='".$row['taxonID']."'";
        $result2 = $dbLink->query($sql2);

        $text .= $row['typus_lat'] . " for " . taxonWithHybrids($row) . " ";
        while ($row2 = $result2->fetch_array()) {
            $text .= protolog($row2)." ";
        }
        if (strlen($accName)>0) {
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
$objPHPExcelWorksheet->setCellValue('A1', 'Specimen ID')
        ->setCellValue('B1', 'Herbarium-Number/BarCode')
        ->setCellValue('C1', 'Collection')
        ->setCellValue('D1', 'Collection Number')
        ->setCellValue('E1', 'Type information')
        ->setCellValue('F1', 'Typified by')
        ->setCellValue('G1', 'Taxon')
        ->setCellValue('H1', 'Family')
        ->setCellValue('I1', 'Collector')
        ->setCellValue('J1', 'Date')
        ->setCellValue('K1', 'Country')
        ->setCellValue('L1', 'Admin1')
        ->setCellValue('M1', 'Latitude')
        ->setCellValue('N1', 'Longitude')
        ->setCellValue('O1', 'Altitude lower')
        ->setCellValue('P1', 'Altitude higher')
        ->setCellValue('Q1', 'Label')
        ->setCellValue('R1', 'det./rev./conf./assigned')
        ->setCellValue('S1', 'ident. history')
        ->setCellValue('T1', 'annotations')
;

$sql = $_SESSION['s_query'] . "ORDER BY genus, epithet, author";

$result = $dbLink->query($sql);
if (!$result) {
    echo $sql."<br>\n";
    echo $dbLink->error . "<br>\n";
}

$i = 2;
while ($row = $result->fetch_array()) {
    $sql = "SELECT s.specimen_ID, tg.genus, c.Sammler, c2.Sammler_2, ss.series, s.series_number,
             s.Nummer, s.alt_number, s.Datum, s.Fundort, s.det, s.taxon_alt, s.Bemerkungen,
             s.CollNummer, s.altitude_min, s.altitude_max,
             n.nation_engl, p.provinz, s.Fundort, tf.family, tsc.cat_description,
             mc.collection, mc.collectionID, mc.coll_short, s.typified, m.source_code,
             s.digital_image, s.digital_image_obs, s.HerbNummer, s.ncbi_accession,
             s.Coord_W, s.W_Min, s.W_Sec, s.Coord_N, s.N_Min, s.N_Sec,
             s.Coord_S, s.S_Min, s.S_Sec, s.Coord_E, s.E_Min, s.E_Sec,
             ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
             ta4.author author4, ta5.author author5,
             te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
             te4.epithet epithet4, te5.epithet epithet5,
             ts.synID, ts.taxonID, ts.statusID
            FROM tbl_specimens s
             LEFT JOIN tbl_specimens_series ss ON ss.seriesID=s.seriesID
             LEFT JOIN tbl_management_collections mc ON mc.collectionID=s.collectionID
             LEFT JOIN meta m ON m.source_id = mc.source_id
             LEFT JOIN tbl_geo_nation n ON n.NationID=s.NationID
             LEFT JOIN tbl_geo_province p ON p.provinceID=s.provinceID
             LEFT JOIN tbl_collector c ON c.SammlerID=s.SammlerID
             LEFT JOIN tbl_collector_2 c2 ON c2.Sammler_2ID=s.Sammler_2ID
             LEFT JOIN tbl_tax_species ts ON ts.taxonID=s.taxonID
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
             LEFT JOIN tbl_tax_families tf ON tf.familyID=tg.familyID
             LEFT JOIN tbl_tax_systematic_categories tsc ON tf.categoryID=tsc.categoryID
            WHERE specimen_ID='" . intval($row['specimen_ID']) . "'";
    $resultSpecimen = $dbLink->query($sql);
    if (!$resultSpecimen) {
        echo $sql."<br>\n";
        echo $dbLink->error . "<br>\n";
    }
    $rowSpecimen = $resultSpecimen->fetch_array();

    $sammler = collection($rowSpecimen['Sammler'],$rowSpecimen['Sammler_2'],$rowSpecimen['series'],$rowSpecimen['series_number'],
                        $rowSpecimen['Nummer'],$rowSpecimen['alt_number'],$rowSpecimen['Datum']);

    $country = $rowSpecimen['nation_engl'];
    $province = $rowSpecimen['provinz'];

    $lon ='';$lat ='';
    if ($rowSpecimen['Coord_S']>0 || $rowSpecimen['S_Min']>0 || $rowSpecimen['S_Sec']>0) {
        $lat = -($rowSpecimen['Coord_S'] + $rowSpecimen['S_Min'] / 60 + $rowSpecimen['S_Sec'] / 3600);
    } else if ($rowSpecimen['Coord_N']>0 || $rowSpecimen['N_Min']>0 || $rowSpecimen['N_Sec']>0) {
        $lat = $rowSpecimen['Coord_N'] + $rowSpecimen['N_Min'] / 60 + $rowSpecimen['N_Sec'] / 3600;
    } else {
        $lat = '';
    }
    if(strlen($lat)>0){
        $lat="".number_format(round($lat,9), 9) ."° ";
    }

   if ($rowSpecimen['Coord_W']>0 || $rowSpecimen['W_Min']>0 || $rowSpecimen['W_Sec']>0) {
        $lon = -($rowSpecimen['Coord_W'] + $rowSpecimen['W_Min'] / 60 + $rowSpecimen['W_Sec'] / 3600);
   } else if ($rowSpecimen['Coord_E']>0 || $rowSpecimen['E_Min']>0 || $rowSpecimen['E_Sec']>0) {
        $lon = $rowSpecimen['Coord_E'] + $rowSpecimen['E_Min'] / 60 + $rowSpecimen['E_Sec'] / 3600;
   } else {
        $lon = '';
   }

    if(strlen($lon)>0){
        $lon= "".number_format(round($lon,9), 9)."° ";
    }

    $objPHPExcelWorksheet->fromArray(array(
        $rowSpecimen['specimen_ID'],
        $rowSpecimen['source_code'] . " " . $rowSpecimen['HerbNummer'],
        $rowSpecimen['coll_short'],
        $rowSpecimen['CollNummer'],
        makeTypus(intval($rowSpecimen['specimen_ID'])),
        $rowSpecimen['typified'],
        taxonWithHybrids($rowSpecimen),
        $rowSpecimen['family'],
        $sammler,
        "'" . $rowSpecimen['Datum'],
        $country,
        $province,
        $lat,
        $lon,
        $rowSpecimen['altitude_min'],
        $rowSpecimen['altitude_max'],
        $rowSpecimen['Fundort'],
        $rowSpecimen['det'],
        $rowSpecimen['taxon_alt'],
        $rowSpecimen['Bemerkungen']
    ), null, 'A' . $i);

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
