<?php

use GuzzleHttp\Client;
use Jacq\Settings;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

require_once __DIR__ . '/vendor/autoload.php';

$config = Settings::Load();

// extend memory and timeout settings
$memoryLimit = $config->get('EXPORT', 'memory_limit');
if ($memoryLimit) {
    ini_set("memory_limit", $memoryLimit);
}
set_time_limit(0);

//http://localhost/develop.jacq/legacy/output.new/classificationBrowser_download.php?referenceType=citation&referenceId=31070&scientificNameId=363825

$client = new Client(['base_uri' => $config->get('JACQ_SERVICES')]);

$response = $client->request('GET',
                             "classification/download/" . urlencode($_GET['referenceType']) . "/" . intval($_GET['referenceId']),
                             ['query' => ["scientificNameId"          => intval($_GET['scientificNameId']),
                                          "hideScientificNameAuthors" => urlencode($_GET['hideScientificNameAuthors'])]])
            ->getBody()
            ->getContents();
$data = json_decode($response, true);

// SQLiteCache hält die Cell-Data nicht im Speicher
//if (!PHPExcel_Settings::setCacheStorageMethod(PHPExcel_CachedObjectStorageFactory::cache_in_memory_gzip)) {
//    die('Caching not available!');
//}

// Create new PhpSpreadsheet object
$spreadsheet = new Spreadsheet();

foreach ($data['header'] as $column => $cell) {
    // column starts with 1 (='A') instead of 0, row with 1
    $spreadsheet->getActiveSheet()->setCellValue([$column + 1, 1], $cell);
}
foreach ($data['body'] as $row => $line) {
    foreach ($line as $column => $cell) {
        $spreadsheet->getActiveSheet()->setCellValue([$column + 1, $row + 2], $cell);
    }
}

switch (filter_input(INPUT_GET, 'type')) {
    case 'csv':
        // Redirect output to a client’s web browser (CSV)
        header("Content-type: text/csv; charset=utf-8");
        header("Content-Disposition: attachment; filename=classification.csv");
        header('Cache-Control: max-age=0');
        $writer = IOFactory::createWriter($spreadsheet, 'Csv');
        $writer->save('php://output');
        break;
    case 'ods':
        // Redirect output to a client’s web browser (OpenDocument)
        header('Content-Type: application/vnd.oasis.opendocument.spreadsheet');
        header('Content-Disposition: attachment;filename="classification.ods"');
        header('Cache-Control: max-age=0');
        $writer = IOFactory::createWriter($spreadsheet, 'Ods');
        $writer->save('php://output');
        break;
    default:
        // Redirect output to a client’s web browser (Excel2007)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="classification.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
}
