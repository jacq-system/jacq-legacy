<?php
require 'inc/variables.php'; // require configuration
require 'inc/RestClient.php';
require 'inc/PHPExcel/PHPExcel.php';

//http://localhost/develop.jacq/legacy/output.new/classificationBrowser_download.php?referenceType=citation&referenceId=31070&scientificNameId=363825

$rest = new RestClient($_CONFIG['JACQ_SERVICES']);

$data = $rest->jsonGet('classification/download', array(filter_input(INPUT_GET, 'referenceType', FILTER_SANITIZE_STRING),
                                                        intval(filter_input(INPUT_GET, 'referenceId', FILTER_SANITIZE_NUMBER_INT))),
                                                  array('scientificNameId' => intval(filter_input(INPUT_GET, 'scientificNameId', FILTER_SANITIZE_NUMBER_INT)),
                                                        'hideScientificNameAuthors' => filter_input(INPUT_GET, 'hideScientificNameAuthors', FILTER_SANITIZE_STRING)));

// SQLiteCache hält die Cell-Data nicht im Speicher
if (!PHPExcel_Settings::setCacheStorageMethod(PHPExcel_CachedObjectStorageFactory::cache_in_memory_gzip)) {
    die('Caching not available!');
}

// Create new PHPExcel object
$objPHPExcel = new PHPExcel();
$objPHPExcelWorksheet = $objPHPExcel->setActiveSheetIndex(0);

foreach ($data['header'] as $column => $cell) {
    // column starts with 0 (='A'), row with 1
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($column, 1, $cell);
}
foreach ($data['body'] as $row => $line) {
    foreach ($line as $column => $cell) {
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($column, $row + 2, $cell);
    }
}

switch (filter_input(INPUT_GET, 'type')) {
    case 'csv':
        // Redirect output to a client’s web browser (CSV)
        header("Content-type: text/csv; charset=utf-8");
        header("Content-Disposition: attachment; filename=classification.csv");
        header('Cache-Control: max-age=0');
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'CSV');
        $objWriter->save('php://output');
        break;
    case 'ods':
        // Redirect output to a client’s web browser (OpenDocument)
        header('Content-Type: application/vnd.oasis.opendocument.spreadsheet');
        header('Content-Disposition: attachment;filename="classification.ods"');
        header('Cache-Control: max-age=0');
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'OpenDocument');
        $objWriter->save('php://output');
        break;
    default:
        // Redirect output to a client’s web browser (Excel2007)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="classification.xlsx"');
        header('Cache-Control: max-age=0');
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
}
