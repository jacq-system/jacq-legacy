<?php
session_start();
require_once "inc/functions.php";
require_once __DIR__ . '/vendor/autoload.php';

use Jacq\DbAccess;
use Jacq\StableIdentifier;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

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
    $dbLnk2 = DbAccess::ConnectTo('OUTPUT');

    $sql = "SELECT typus_lat, tg.genus,
             ta.author, ta1.author author1, ta2.author author2, ta3.author author3, ta4.author author4, ta5.author author5,
             te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3, te4.epithet epithet4, te5.epithet epithet5,
             ts.synID, ts.taxonID, ts.statusID
            FROM (tbl_specimens_types tst, tbl_typi tt, tbl_tax_species ts)
             LEFT JOIN tbl_tax_authors  ta  ON ta.authorID   = ts.authorID
             LEFT JOIN tbl_tax_authors  ta1 ON ta1.authorID  = ts.subspecies_authorID
             LEFT JOIN tbl_tax_authors  ta2 ON ta2.authorID  = ts.variety_authorID
             LEFT JOIN tbl_tax_authors  ta3 ON ta3.authorID  = ts.subvariety_authorID
             LEFT JOIN tbl_tax_authors  ta4 ON ta4.authorID  = ts.forma_authorID
             LEFT JOIN tbl_tax_authors  ta5 ON ta5.authorID  = ts.subforma_authorID
             LEFT JOIN tbl_tax_epithets te  ON te.epithetID  = ts.speciesID
             LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID = ts.subspeciesID
             LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID = ts.varietyID
             LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID = ts.subvarietyID
             LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID = ts.formaID
             LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID = ts.subformaID
             LEFT JOIN tbl_tax_genera   tg  ON tg.genID      = ts.genID
            WHERE tst.typusID = tt.typusID
             AND tst.taxonID = ts.taxonID
             AND specimenID = '" . intval($ID) . "'";
    $result = $dbLnk2->query($sql);

    $text = "";
    while ($row = $result->fetch_array()) {
        if ($row['synID']) {
            $sql3 = "SELECT ts.statusID, tg.genus,
                      ta.author, ta1.author author1, ta2.author author2, ta3.author author3, ta4.author author4, ta5.author author5,
                      te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3, te4.epithet epithet4, te5.epithet epithet5
                     FROM tbl_tax_species ts
                      LEFT JOIN tbl_tax_authors  ta  ON ta.authorID   = ts.authorID
                      LEFT JOIN tbl_tax_authors  ta1 ON ta1.authorID  = ts.subspecies_authorID
                      LEFT JOIN tbl_tax_authors  ta2 ON ta2.authorID  = ts.variety_authorID
                      LEFT JOIN tbl_tax_authors  ta3 ON ta3.authorID  = ts.subvariety_authorID
                      LEFT JOIN tbl_tax_authors  ta4 ON ta4.authorID  = ts.forma_authorID
                      LEFT JOIN tbl_tax_authors  ta5 ON ta5.authorID  = ts.subforma_authorID
                      LEFT JOIN tbl_tax_epithets te  ON te.epithetID  = ts.speciesID
                      LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID = ts.subspeciesID
                      LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID = ts.varietyID
                      LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID = ts.subvarietyID
                      LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID = ts.formaID
                      LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID = ts.subformaID
                      LEFT JOIN tbl_tax_genera   tg  ON tg.genID      = ts.genID
                     WHERE taxonID = " . $row['synID'];
            $result3 = $dbLnk2->query($sql3);
            $row3 = $result3->fetch_array();
            $accName = taxonWithHybrids($row3);
        } else {
            $accName = "";
        }

        $sql2 = "SELECT l.suptitel, la.autor, l.periodicalID, lp.periodical, l.vol, l.part, ti.paginae, ti.figures, l.jahr
                 FROM tbl_tax_index ti
                  INNER JOIN tbl_lit            l  ON l.citationID    = ti.citationID
                  LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID = l.periodicalID
                  LEFT JOIN tbl_lit_authors     la ON la.autorID      = l.editorsID
                 WHERE ti.taxonID = '" . $row['taxonID'] . "'";
        $result2 = $dbLnk2->query($sql2);

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
$config = \Jacq\Settings::Load();
$memoryLimit = $config->get('EXPORT', 'memory_limit');
if ($memoryLimit) {
    ini_set("memory_limit", $memoryLimit);
}
set_time_limit(0);

// SQLiteCache hält die Cell-Data nicht im Speicher
//if (!PHPExcel_Settings::setCacheStorageMethod(PHPExcel_CachedObjectStorageFactory::cache_in_memory_gzip)) {
//    die('Caching not available!');
//}

// Create new PhpSpreadsheet object
$spreadsheet = new Spreadsheet();

// add header info
$spreadsheet->getActiveSheet()
        ->setCellValue('A1', 'Specimen ID')
        ->setCellValue('B1', 'observation')
        ->setCellValue('C1', 'dig_image')
        ->setCellValue('D1', 'dig_img_obs')
        ->setCellValue('E1', 'Institution_Code')
        ->setCellValue('F1', 'Herbarium-Number/BarCode')
        ->setCellValue('G1', 'institution_subcollection')
        ->setCellValue('H1', 'Collection Number')
        ->setCellValue('I1', 'Type information')
        ->setCellValue('J1', 'Typified by')
        ->setCellValue('K1', 'Taxon')
        ->setCellValue('L1', 'status')
        ->setCellValue('M1', 'Genus')
        ->setCellValue('N1', 'Species')
        ->setCellValue('O1', 'Author')
        ->setCellValue('P1', 'Rank')
        ->setCellValue('Q1', 'Infra_spec')
        ->setCellValue('R1', 'Infra_author')
        ->setCellValue('S1', 'Family')
        ->setCellValue('T1', 'Garden')
        ->setCellValue('U1', 'voucher')
        ->setCellValue('V1', 'Collection')
        ->setCellValue('W1', 'First_collector')
        ->setCellValue('X1', 'First_collectors_number')
        ->setCellValue('Y1', 'Add_collectors')
        ->setCellValue('Z1', 'Alt_number')
        ->setCellValue('AA1', 'Series')
        ->setCellValue('AB1', 'Series_number')
        ->setCellValue('AC1', 'Coll_Date')
        ->setCellValue('AD1', 'Coll_Date_2')
        ->setCellValue('AE1', 'Country')
        ->setCellValue('AF1', 'Province')
        ->setCellValue('AG1', 'geonames')
        ->setCellValue('AH1', 'Latitude')
        ->setCellValue('AI1', 'Latitude_DMS')
        ->setCellValue('AJ1', 'Lat_Hemisphere')
        ->setCellValue('AK1', 'Lat_degree')
        ->setCellValue('AL1', 'Lat_minute')
        ->setCellValue('AM1', 'Lat_second')
        ->setCellValue('AN1', 'Longitude')
        ->setCellValue('AO1', 'Longitude_DMS')
        ->setCellValue('AP1', 'Long_Hemisphere')
        ->setCellValue('AQ1', 'Long_degree')
        ->setCellValue('AR1', 'Long_minute')
        ->setCellValue('AS1', 'Long_second')
        ->setCellValue('AT1', 'exactness')
        ->setCellValue('AU1', 'Altitude lower')
        ->setCellValue('AV1', 'Altitude higher')
        ->setCellValue('AW1', 'Quadrant')
        ->setCellValue('AX1', 'Quadrant_sub')
        ->setCellValue('AY1', 'Location')
        ->setCellValue('AZ1', 'det./rev./conf./assigned')
        ->setCellValue('BA1', 'ident. history')
        ->setCellValue('BB1', 'annotations')
        ->setCellValue('BC1', 'habitat')
        ->setCellValue('BD1', 'habitus')
        ->setCellValue('BE1', 'stable identifier')
;

$dbLnk2 = DbAccess::ConnectTo('OUTPUT');
$sql = $_SESSION['s_query'] . "ORDER BY genus, epithet, author";
$result = $dbLnk2->query($sql);
if (!$result) {
    error_log($sql . "\n" . $dbLnk2->error . "\n");
    die();
}

$specimenIDs = array();
while ($row = $result->fetch_array()) {
    $specimenIDs[] = intval($row['specimen_ID']);
}
$sqlSpecimen = "SELECT s.specimen_ID, tg.genus, c.Sammler, c2.Sammler_2, ss.series, s.series_number,
                 s.Nummer, s.alt_number, s.Datum, s.Datum2, s.Fundort, s.det, s.taxon_alt, s.Bemerkungen,
                 s.CollNummer, s.altitude_min, s.altitude_max,
                 n.nation_engl, p.provinz, s.Bezirk, s.Fundort, tf.family, tsc.cat_description, si.identification_status, sv.voucher,
                 mc.collection, mc.collectionID, mc.coll_short, s.typified, m.source_code,
                 s.digital_image, s.digital_image_obs, s.HerbNummer, s.ncbi_accession,
                 s.Coord_W, s.W_Min, s.W_Sec, s.Coord_N, s.N_Min, s.N_Sec,
                 s.Coord_S, s.S_Min, s.S_Sec, s.Coord_E, s.E_Min, s.E_Sec,
                 s.quadrant, s.quadrant_sub, s.exactness,
                 s.habitat, s.habitus, s.garten,
                 s.observation,
                 tr.rank_abbr,
                 ta.author, ta1.author author1, ta2.author author2, ta3.author author3, ta4.author author4, ta5.author author5,
                 te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3, te4.epithet epithet4, te5.epithet epithet5,
                 ts.synID, ts.taxonID, ts.statusID,
                 `herbar_view`.GetScientificName(ts.taxonID, 0) AS `scientificName`
                FROM tbl_specimens s
                 LEFT JOIN tbl_specimens_series          ss  ON ss.seriesID = s.seriesID
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
                WHERE specimen_ID IN (" . implode(', ', $specimenIDs) . ")";
$resultSpecimen = $dbLnk2->query($sqlSpecimen);
if (!$resultSpecimen) {
    error_log($sql . "\n" . $dbLnk2->error . "\n");
    die();
}

$i = 2;
while ($rowSpecimen = $resultSpecimen->fetch_array()) {
    $sammler = collection($rowSpecimen);

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

    $spreadsheet->getActiveSheet()->fromArray(array(
        $rowSpecimen['specimen_ID'],
        $rowSpecimen['observation'],
        ($rowSpecimen['digital_image']) ? '1' : '',
        ($rowSpecimen['digital_image_obs']) ? '1' : '',
        $rowSpecimen['source_code'],
        $rowSpecimen['HerbNummer'],
        $rowSpecimen['coll_short'],
        $rowSpecimen['CollNummer'],
        makeTypus(intval($rowSpecimen['specimen_ID'])),
        $rowSpecimen['typified'],
        $rowSpecimen['scientificName'],
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
        ((substr($rowSpecimen['Bemerkungen'], 0, 1) == '=') ? " " : "") . $rowSpecimen['Bemerkungen'],  // to prevent a starting "=" (would be interpreted as a formula)
        ((substr($rowSpecimen['habitat'], 0, 1) == '=') ? " " : "") . $rowSpecimen['habitat'],          // to prevent a starting "=" (would be interpreted as a formula)
        ((substr($rowSpecimen['habitus'], 0, 1) == '=') ? " " : "") . $rowSpecimen['habitus'],          // to prevent a starting "=" (would be interpreted as a formula)
        StableIdentifier::make($rowSpecimen['specimen_ID'])->getStblID()
    ), null, 'A' . $i);

    $i++;
}

switch (filter_input(INPUT_GET, 'type')) {
    case 'csv':
        // Redirect output to a client’s web browser (CSV)
        header("Content-type: text/csv; charset=utf-8");
        header("Content-Disposition: attachment; filename=specimens_download.csv");
        header('Cache-Control: max-age=0');
        $writer = IOFactory::createWriter($spreadsheet, 'Csv');
        $writer->save('php://output');
        break;
    case 'ods':
        // Redirect output to a client’s web browser (OpenDocument)
        header('Content-Type: application/vnd.oasis.opendocument.spreadsheet');
        header('Content-Disposition: attachment;filename="specimens_download.ods"');
        header('Cache-Control: max-age=0');
        $writer = IOFactory::createWriter($spreadsheet, 'Ods');
        $writer->save('php://output');
        break;
    default:
        // Redirect output to a client’s web browser (Excel2007)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="specimens_download.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
}
