<?php
session_start();
require('inc/connect.php');
require __DIR__ . '/vendor/autoload.php';

use Jacq\Log;
use Jacq\Permission;
use Jacq\TaxonTokenizer;
use \org\jsonrpcphp\JsonRPCClient;

const UPDATE_SPECIMENS_MAX_FILE_SIZE = 8000000;
const UPDATE_PROCESS_SESSION_KEY = 'update_specimens_process';
const UPDATE_PENDING_INPUT_SESSION_KEY = 'update_specimens_pending_input';

function normalizeUpdateImportText($value)
{
    $value = (string)$value;
    if ($value === '' || mb_check_encoding($value, 'UTF-8')) {
        return $value;
    }

    $sourceEncoding = mb_detect_encoding($value, array('Windows-1252', 'ISO-8859-1'), true);
    return mb_convert_encoding($value, 'UTF-8', $sourceEncoding ?: 'Windows-1252');
}

function detectUpdateImportEncoding($inputPath)
{
    $content = @file_get_contents($inputPath);
    if ($content === false) {
        return '';
    }
    if (mb_check_encoding($content, 'UTF-8')) {
        return 'UTF-8';
    }

    return mb_detect_encoding($content, array('Windows-1252', 'ISO-8859-1'), true) ?: '';
}

function convertUpdateImportFileToUtf8($sourcePath, $sourceEncoding, &$targetPath, &$error = '')
{
    if (!in_array($sourceEncoding, array('Windows-1252', 'ISO-8859-1'), true)) {
        $error = 'Only Windows-1252 and ISO-8859-1 files can be converted automatically.';
        return false;
    }

    $content = @file_get_contents($sourcePath);
    if ($content === false) {
        $error = 'Could not read the file selected for conversion.';
        return false;
    }

    $targetPath = tempnam(sys_get_temp_dir(), 'upd_utf8_');
    if ($targetPath === false || @file_put_contents($targetPath, mb_convert_encoding($content, 'UTF-8', $sourceEncoding)) === false) {
        if ($targetPath !== false) {
            @unlink($targetPath);
        }
        $error = 'Could not create the UTF-8 converted file.';
        return false;
    }

    return true;
}
function parseUpdateLine($handle, $minNumOfParts = 1, $delimiter = ';', $enclosure = '"')
{
    $parts = fgetcsv($handle, 4096, $delimiter, $enclosure);
    if ($parts !== false) {
        $parts = array_map('normalizeUpdateImportText', $parts);
    }
    if ($parts !== false && count($parts) === 1 && trim((string)$parts[0]) === '') {
        return false;
    }
    if (!empty($parts) && count($parts) >= $minNumOfParts) {
        return $parts;
    }

    return false;
}

function isLocalImportEnvironment()
{
    $hostHeader = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
    $normalizedHost = strtolower(preg_replace('/:.*/', '', $hostHeader));
    $localHosts = array('localhost', '127.0.0.1', '::1');

    return in_array($normalizedHost, $localHosts, true);
}

function getAllowedImportDownloadHosts()
{
    $hosts = array(
        'jacq.org',
        'www.jacq.org',
        'digidash.bo.berlin',
        'services.jacq.org',
        'openrefine.bo.berlin',
    );

    if (!empty($_SERVER['HTTP_HOST'])) {
        $hosts[] = strtolower(preg_replace('/:\d+$/', '', trim($_SERVER['HTTP_HOST'])));
    }

    return array_values(array_unique(array_filter($hosts)));
}

function isAllowedImportDownloadUrl($url, &$error = '')
{
    $url = trim($url);
    if ($url === '') {
        $error = 'The download URL is empty.';
        return false;
    }

    if (filter_var($url, FILTER_VALIDATE_URL) === false) {
        $error = 'The download URL is invalid.';
        return false;
    }

    $parts = parse_url($url);
    if (empty($parts['scheme']) || strtolower($parts['scheme']) !== 'https') {
        $error = 'Only HTTPS download URLs are allowed.';
        return false;
    }
    if (empty($parts['host'])) {
        $error = 'The download URL does not contain a valid host.';
        return false;
    }
    if (!empty($parts['user']) || !empty($parts['pass'])) {
        $error = 'Credentials in download URLs are not allowed.';
        return false;
    }

    $host = strtolower($parts['host']);
    if (!in_array($host, getAllowedImportDownloadHosts(), true)) {
        $error = 'The download host is not on the allowlist.';
        return false;
    }

    return true;
}

function resolveImportDownloadUrl($baseUrl, $location, &$error = '')
{
    $location = trim($location);
    if ($location === '') {
        $error = 'Redirect response without Location header.';
        return false;
    }

    if (preg_match('~^https://~i', $location)) {
        return $location;
    }
    if (preg_match('~^http://~i', $location)) {
        $error = 'Only HTTPS redirects are allowed.';
        return false;
    }

    $base = parse_url($baseUrl);
    if (!$base || empty($base['scheme']) || empty($base['host'])) {
        $error = 'Failed to resolve redirect URL.';
        return false;
    }

    $scheme = strtolower($base['scheme']);
    $host = $base['host'];
    $port = isset($base['port']) ? ':' . $base['port'] : '';

    if (strpos($location, '//') === 0) {
        return $scheme . ':' . $location;
    }

    if ($location[0] === '/') {
        return $scheme . '://' . $host . $port . $location;
    }

    $basePath = isset($base['path']) ? $base['path'] : '/';
    $directory = preg_replace('~/[^/]*$~', '/', $basePath);

    return $scheme . '://' . $host . $port . $directory . $location;
}

function isAllowedImportDownloadResponse($contentType, $url)
{
    $contentType = strtolower(trim((string)$contentType));
    if (($pos = strpos($contentType, ';')) !== false) {
        $contentType = trim(substr($contentType, 0, $pos));
    }

    $path = (string)parse_url($url, PHP_URL_PATH);
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $allowedExtensions = array('txt', 'csv');
    $allowedContentTypes = array('text/plain', 'text/csv', 'application/csv');
    $binaryContentTypes = array('application/octet-stream', 'application/vnd.ms-excel');

    if (in_array($contentType, $allowedContentTypes, true)) {
        return true;
    }
    if (in_array($contentType, $binaryContentTypes, true) && in_array($extension, $allowedExtensions, true)) {
        return true;
    }
    if ($contentType === '' && in_array($extension, $allowedExtensions, true)) {
        return true;
    }

    return false;
}

function downloadImportInput($url, &$inputPath, &$inputName, &$error = '')
{
    $maxBytes = UPDATE_SPECIMENS_MAX_FILE_SIZE;
    $maxRedirects = 3;
    $currentUrl = trim($url);

    if (!function_exists('curl_init')) {
        $error = 'cURL is not available for URL imports.';
        return false;
    }

    for ($redirect = 0; $redirect <= $maxRedirects; $redirect++) {
        if (!isAllowedImportDownloadUrl($currentUrl, $error)) {
            return false;
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'upd_');
        if ($tempPath === false) {
            $error = 'Could not create a temporary file for the download.';
            return false;
        }

        $fp = fopen($tempPath, 'wb');
        if ($fp === false) {
            @unlink($tempPath);
            $error = 'Could not open a temporary file for the download.';
            return false;
        }

        $headers = array();
        $downloadedBytes = 0;
        $sizeLimitExceeded = false;

        $ch = curl_init($currentUrl);
        $verifySsl = !isLocalImportEnvironment();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verifySsl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, ($verifySsl) ? 2 : 0);
        curl_setopt($ch, CURLOPT_USERAGENT, 'JACQ updateSpecimens downloader');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: text/plain,text/csv,application/csv'));
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $headerLine) use (&$headers) {
            $length = strlen($headerLine);
            $trimmed = trim($headerLine);
            if ($trimmed === '') {
                return $length;
            }
            if (stripos($headerLine, 'HTTP/') === 0) {
                $headers = array();
                return $length;
            }

            $parts = explode(':', $headerLine, 2);
            if (count($parts) === 2) {
                $headers[strtolower(trim($parts[0]))] = trim($parts[1]);
            }

            return $length;
        });
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $chunk) use ($fp, $maxBytes, &$downloadedBytes, &$sizeLimitExceeded) {
            $length = strlen($chunk);
            $downloadedBytes += $length;
            if ($downloadedBytes > $maxBytes) {
                $sizeLimitExceeded = true;
                return 0;
            }

            return fwrite($fp, $chunk);
        });

        $execResult = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpStatus = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        fclose($fp);

        if ($sizeLimitExceeded) {
            @unlink($tempPath);
            $error = 'The downloaded file exceeds the maximum size of ' . UPDATE_SPECIMENS_MAX_FILE_SIZE . ' bytes.';
            return false;
        }

        if ($execResult === false) {
            @unlink($tempPath);
            $error = 'Download failed: ' . $curlError;
            return false;
        }

        if (isset($headers['content-length']) && (int)$headers['content-length'] > $maxBytes) {
            @unlink($tempPath);
            $error = 'The remote file exceeds the maximum size of ' . UPDATE_SPECIMENS_MAX_FILE_SIZE . ' bytes.';
            return false;
        }

        if ($httpStatus >= 300 && $httpStatus < 400) {
            @unlink($tempPath);
            $redirectUrl = resolveImportDownloadUrl($currentUrl, isset($headers['location']) ? $headers['location'] : '', $error);
            if ($redirectUrl === false) {
                return false;
            }
            $currentUrl = $redirectUrl;
            continue;
        }

        if ($httpStatus !== 200) {
            @unlink($tempPath);
            $error = 'Download failed with HTTP status ' . $httpStatus . '.';
            return false;
        }

        if ($downloadedBytes === 0) {
            @unlink($tempPath);
            $error = 'The downloaded file is empty.';
            return false;
        }

        $contentType = isset($headers['content-type']) ? $headers['content-type'] : '';
        if (!isAllowedImportDownloadResponse($contentType, $currentUrl)) {
            @unlink($tempPath);
            $error = 'The downloaded file is not an allowed text import file.';
            return false;
        }

        $inputPath = $tempPath;
        $inputName = $currentUrl;
        return true;
    }

    $error = 'Too many redirects while downloading the import file.';
    return false;
}
function detectUpdateRun()
{
    $hasResetRequest = isset($_POST['reset_update_process']);
    $hasCancelEncodingRequest = isset($_POST['cancel_update_input_encoding']);
    $hasArchiveRequest = isset($_POST['archive_update_process']);
    $hasEncodingConversionRequest = isset($_POST['convert_update_input_encoding']);
    $hasUploadedFile = isset($_FILES['userfile']) && is_uploaded_file($_FILES['userfile']['tmp_name']);
    $hasDownloadUrl = isset($_POST['download_url']) && trim((string)$_POST['download_url']) !== '';
    $hasUpdatePayload = isset($_POST['update_payload']) && trim((string)$_POST['update_payload']) !== '';

    if ($hasResetRequest || $hasCancelEncodingRequest) {
        return 1;
    }
    if ($hasUploadedFile || $hasDownloadUrl || $hasEncodingConversionRequest) {
        return 2;
    }
    if ($hasUpdatePayload || $hasArchiveRequest) {
        return 3;
    }

    return 1;
}

function cleanupStoredUpdateProcessFile($filePath)
{
    if ($filePath !== '' && is_file($filePath)) {
        @unlink($filePath);
    }
}

function clearUpdateProcessContext()
{
    if (isset($_SESSION[UPDATE_PROCESS_SESSION_KEY]) && is_array($_SESSION[UPDATE_PROCESS_SESSION_KEY])) {
        $context = $_SESSION[UPDATE_PROCESS_SESSION_KEY];
        if (!empty($context['input_file_path'])) {
            cleanupStoredUpdateProcessFile($context['input_file_path']);
        }
    }

    unset($_SESSION[UPDATE_PROCESS_SESSION_KEY]);
}

function clearPendingUpdateInput()
{
    if (isset($_SESSION[UPDATE_PENDING_INPUT_SESSION_KEY]) && is_array($_SESSION[UPDATE_PENDING_INPUT_SESSION_KEY])) {
        $pending = $_SESSION[UPDATE_PENDING_INPUT_SESSION_KEY];
        if (!empty($pending['input_file_path'])) {
            cleanupStoredUpdateProcessFile($pending['input_file_path']);
        }
    }

    unset($_SESSION[UPDATE_PENDING_INPUT_SESSION_KEY]);
}

function getPendingUpdateInput()
{
    return (isset($_SESSION[UPDATE_PENDING_INPUT_SESSION_KEY]) && is_array($_SESSION[UPDATE_PENDING_INPUT_SESSION_KEY]))
        ? $_SESSION[UPDATE_PENDING_INPUT_SESSION_KEY]
        : array();
}
function getUpdateProcessContext()
{
    if (isset($_SESSION[UPDATE_PROCESS_SESSION_KEY]) && is_array($_SESSION[UPDATE_PROCESS_SESSION_KEY])) {
        return $_SESSION[UPDATE_PROCESS_SESSION_KEY];
    }

    return array();
}

function sanitizeUpdateProcessFilename($inputName, $defaultName = 'update_input.txt')
{
    $filename = '';
    $parsedPath = parse_url((string)$inputName, PHP_URL_PATH);
    if (is_string($parsedPath) && $parsedPath !== '') {
        $filename = basename($parsedPath);
    }
    if ($filename === '') {
        $filename = basename((string)$inputName);
    }

    $filename = preg_replace('/[^A-Za-z0-9._-]+/', '_', (string)$filename);
    $filename = trim((string)$filename, '._');

    return ($filename !== '') ? $filename : $defaultName;
}

function isAllowedUploadedUpdateFilename($inputName)
{
    $extension = strtolower(pathinfo((string)$inputName, PATHINFO_EXTENSION));
    return in_array($extension, array('txt', 'csv'), true);
}

function storePendingUpdateInput($sourcePath, $inputName, $encoding, &$error = '')
{
    clearPendingUpdateInput();

    $storedPath = tempnam(sys_get_temp_dir(), 'upd_encoding_');
    if ($storedPath === false || !@copy($sourcePath, $storedPath)) {
        if ($storedPath !== false) {
            cleanupStoredUpdateProcessFile($storedPath);
        }
        $error = 'Could not persist the file for encoding conversion.';
        return false;
    }

    $_SESSION[UPDATE_PENDING_INPUT_SESSION_KEY] = array(
        'input_file_path' => $storedPath,
        'input_file_name' => sanitizeUpdateProcessFilename($inputName),
        'encoding' => $encoding,
    );

    return true;
}
function storeUpdateProcessInputFile($sourcePath, $inputName, &$error = '')
{
    clearUpdateProcessContext();
    clearPendingUpdateInput();

    $storedPath = tempnam(sys_get_temp_dir(), 'upd_proc_');
    if ($storedPath === false) {
        $error = 'Could not create a temporary file for the stored update process.';
        return false;
    }
    if (!@copy($sourcePath, $storedPath)) {
        cleanupStoredUpdateProcessFile($storedPath);
        $error = 'Could not persist the uploaded update file for later archiving.';
        return false;
    }

    $_SESSION[UPDATE_PROCESS_SESSION_KEY] = array(
        'input_file_path' => $storedPath,
        'input_file_name' => sanitizeUpdateProcessFilename($inputName),
        'report_name' => '',
        'report_content' => '',
        'results' => array(),
        'finished_at' => '',
    );

    return true;
}

function buildUpdateResultsReport(array $updateResults, array $context = array())
{
    $successCount = 0;
    foreach ($updateResults as $result) {
        if (!empty($result['success'])) {
            $successCount++;
        }
    }
    $errorCount = count($updateResults) - $successCount;

    $lines = array();
    $lines[] = 'Update Specimens Report';
    $lines[] = 'Generated: ' . date('Y-m-d H:i:s');
    if (!empty($context['input_file_name'])) {
        $lines[] = 'Input file: ' . $context['input_file_name'];
    }
    $lines[] = 'Processed rows: ' . count($updateResults);
    $lines[] = 'Successful rows: ' . $successCount;
    $lines[] = 'Failed rows: ' . $errorCount;
    $lines[] = '';
    $lines[] = "Row\tSpecimen\tSuccess\tMessage\tChanged Fields";

    foreach ($updateResults as $result) {
        $lines[] = implode("\t", array(
            (string)$result['rowKey'],
            (string)$result['specimen_ID'],
            !empty($result['success']) ? 'yes' : 'no',
            str_replace(array("\r", "\n", "\t"), ' ', (string)$result['message']),
            implode(', ', $result['changedFields']),
        ));
    }

    return implode("\n", $lines) . "\n";
}

function storeUpdateProcessResults(array $updateResults)
{
    $context = getUpdateProcessContext();
    if (empty($context)) {
        return;
    }

    $context['results'] = $updateResults;
    $context['report_name'] = 'report_' . date('Ymd_His') . '.txt';
    $context['report_content'] = buildUpdateResultsReport($updateResults, $context);
    $context['finished_at'] = date('Y-m-d H:i:s');
    $_SESSION[UPDATE_PROCESS_SESSION_KEY] = $context;
}

function loadStoredUpdateProcessResults()
{
    $context = getUpdateProcessContext();
    if (!empty($context['results']) && is_array($context['results'])) {
        return $context['results'];
    }

    return array();
}

function sendUpdateProcessArchive(&$error = '')
{
    $context = getUpdateProcessContext();
    if (empty($context) || empty($context['input_file_path']) || !is_file($context['input_file_path'])) {
        $error = 'No processed update file is available for archiving.';
        return false;
    }
    if (empty($context['report_content'])) {
        $error = 'No report is available for archiving.';
        return false;
    }
    if (!class_exists('ZipArchive')) {
        $error = 'ZipArchive is not available on this PHP installation.';
        return false;
    }

    $zipPath = tempnam(sys_get_temp_dir(), 'upd_zip_');
    if ($zipPath === false) {
        $error = 'Could not create a temporary ZIP file.';
        return false;
    }

    $zip = new ZipArchive();
    $openResult = $zip->open($zipPath, ZipArchive::OVERWRITE);
    if ($openResult !== true) {
        @unlink($zipPath);
        $error = 'Could not create the archive ZIP file.';
        return false;
    }

    $zip->addFile($context['input_file_path'], $context['input_file_name']);
    $reportName = !empty($context['report_name']) ? $context['report_name'] : 'report.txt';
    $zip->addFromString($reportName, $context['report_content']);
    $zip->close();

    $timestamp = !empty($context['finished_at']) ? strtotime($context['finished_at']) : time();
    if ($timestamp === false) {
        $timestamp = time();
    }
    $archiveName = 'import_' . date('Ymd_His', $timestamp) . '.zip';

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $archiveName . '"');
    header('Content-Length: ' . filesize($zipPath));
    header('Cache-Control: private, must-revalidate');
    header('Pragma: public');
    readfile($zipPath);
    @unlink($zipPath);

    return true;
}
function getUpdateFieldDefinitions()
{
    static $definitions = null;
    if ($definitions !== null) {
        return $definitions;
    }

    $definitions = array(
        'CollNummer' => array('label' => 'CollNummer', 'type' => 'string'),
        'identstatusID' => array('label' => 'Ident Status', 'type' => 'int'),
        'taxonID' => array('label' => 'Taxon', 'type' => 'int'),
        'SammlerID' => array('label' => 'Collector 1', 'type' => 'int'),
        'Sammler_2ID' => array('label' => 'Collector 2', 'type' => 'int'),
        'seriesID' => array('label' => 'Series', 'type' => 'int'),
        'series_number' => array('label' => 'Series No.', 'type' => 'string'),
        'Nummer' => array('label' => 'Nummer', 'type' => 'string'),
        'alt_number' => array('label' => 'Alt Number', 'type' => 'string'),
        'Datum' => array('label' => 'Date', 'type' => 'string'),
        'Datum2' => array('label' => 'Date 2', 'type' => 'string'),
        'det' => array('label' => 'det', 'type' => 'string'),
        'typified' => array('label' => 'Typified', 'type' => 'string'),
        'typusID' => array('label' => 'Type', 'type' => 'int'),
        'taxon_alt' => array('label' => 'Taxon Alt', 'type' => 'string'),
        'NationID' => array('label' => 'Nation', 'type' => 'int'),
        'provinceID' => array('label' => 'Province', 'type' => 'int'),
        'Fundort' => array('label' => 'Fundort', 'type' => 'string'),
        'Fundort_engl' => array('label' => 'Fundort Engl.', 'type' => 'string'),
        'habitat' => array('label' => 'Habitat', 'type' => 'string'),
        'habitus' => array('label' => 'Habitus', 'type' => 'string'),
        'Bemerkungen' => array('label' => 'Bemerkungen', 'type' => 'string'),
        'Coord_N' => array('label' => 'N Deg', 'type' => 'numeric'),
        'N_Min' => array('label' => 'N Min', 'type' => 'numeric'),
        'N_Sec' => array('label' => 'N Sec', 'type' => 'numeric'),
        'Coord_S' => array('label' => 'S Deg', 'type' => 'numeric'),
        'S_Min' => array('label' => 'S Min', 'type' => 'numeric'),
        'S_Sec' => array('label' => 'S Sec', 'type' => 'numeric'),
        'Coord_W' => array('label' => 'W Deg', 'type' => 'numeric'),
        'W_Min' => array('label' => 'W Min', 'type' => 'numeric'),
        'W_Sec' => array('label' => 'W Sec', 'type' => 'numeric'),
        'Coord_E' => array('label' => 'E Deg', 'type' => 'numeric'),
        'E_Min' => array('label' => 'E Min', 'type' => 'numeric'),
        'E_Sec' => array('label' => 'E Sec', 'type' => 'numeric'),
        'quadrant' => array('label' => 'Quadrant', 'type' => 'numeric'),
        'quadrant_sub' => array('label' => 'Quadrant Sub', 'type' => 'numeric'),
        'exactness' => array('label' => 'Exactness', 'type' => 'numeric'),
        'altitude_min' => array('label' => 'Alt Min', 'type' => 'numeric'),
        'altitude_max' => array('label' => 'Alt Max', 'type' => 'numeric'),
        'digital_image' => array('label' => 'Digital Image', 'type' => 'nullable_bool'),
        'digital_image_obs' => array('label' => 'Digital Image Obs', 'type' => 'nullable_bool'),
        'observation' => array('label' => 'Observation', 'type' => 'nullable_bool'),
        'notes_internal' => array('label' => 'Internal Notes', 'type' => 'string'),
    );

    return $definitions;
}

function initializeFieldArray($default = '')
{
    return array_fill_keys(array_keys(getUpdateFieldDefinitions()), $default);
}

function addStatusCode(&$statusCodes, $code)
{
    if (!in_array($code, $statusCodes, true)) {
        $statusCodes[] = $code;
    }
}

function getCsvValue(array $row, $index)
{
    return isset($row[$index]) ? trim((string)$row[$index]) : '';
}

function getDefaultUpdateImportColumnMap()
{
    return array(
        'HerbNummer' => 0,
        'collectionID' => 1,
        'CollNummer' => 2,
        'identstatusID' => 3,
        'taxonID' => 4,
        'collector' => 5,
        'seriesID' => 6,
        'series_number' => 7,
        'Nummer' => 8,
        'alt_number' => 9,
        'Datum' => 10,
        'Datum2' => 11,
        'det' => 12,
        'typified' => 13,
        'typusID' => 14,
        'taxon_alt' => 15,
        'NationID' => 16,
        'provinceID' => 17,
        'Fundort' => 18,
        'Fundort_engl' => 19,
        'habitat' => 20,
        'habitus' => 21,
        'Bemerkungen' => 22,
        'lat_cardinal' => 23,
        'lat_deg' => 24,
        'lat_min' => 25,
        'lat_sec' => 26,
        'lon_cardinal' => 27,
        'lon_deg' => 28,
        'lon_min' => 29,
        'lon_sec' => 30,
        'quadrant' => 31,
        'quadrant_sub' => 32,
        'exactness' => 33,
        'altitude_min' => 34,
        'altitude_max' => 35,
        'digital_image' => 36,
        'digital_image_obs' => 37,
        'observation' => 38,
        'notes_internal' => 39,
    );
}

function normalizeUpdateImportHeaderName($header)
{
    $header = trim((string)$header);
    if ($header === '') {
        return '';
    }

    $header = mb_strtolower($header, 'UTF-8');
    return preg_replace('/[^\p{L}\p{N}]+/u', '', $header);
}

function getUpdateImportHeaderSourceAliases()
{
    static $aliases = null;
    if ($aliases !== null) {
        return $aliases;
    }

    $aliases = array(
        'specimenid' => 'specimen_ID',
        'herbnummer' => 'HerbNummer',
        'collectionid' => 'collectionID',
        'collectionnumber' => 'CollNummer',
        'status' => 'identstatusID',
        'taxon' => 'taxonID',
        'sammler' => 'collector',
        'series' => 'seriesID',
        'seriesnumber' => 'series_number',
        'nummer' => 'Nummer',
        'altnumber' => 'alt_number',
        'datum' => 'Datum',
        'datum2' => 'Datum2',
        'det' => 'det',
        'typified' => 'typified',
        'typus' => 'typusID',
        'taxonalt' => 'taxon_alt',
        'nationengl' => 'NationID',
        'provinz' => 'provinceID',
        'fundort' => 'Fundort',
        'fundortengl' => 'Fundort_engl',
        'habitat' => 'habitat',
        'habitus' => 'habitus',
        'bemerkungen' => 'Bemerkungen',
        'coordns' => 'lat_cardinal',
        'latdegree' => 'lat_deg',
        'latminute' => 'lat_min',
        'latsecond' => 'lat_sec',
        'coordwe' => 'lon_cardinal',
        'longdegree' => 'lon_deg',
        'longminute' => 'lon_min',
        'longsecond' => 'lon_sec',
        'quadrant' => 'quadrant',
        'quadrantsub' => 'quadrant_sub',
        'exactness' => 'exactness',
        'altmin' => 'altitude_min',
        'altmax' => 'altitude_max',
        'digitalimage' => 'digital_image',
        'digitalimageobs' => 'digital_image_obs',
        'observation' => 'observation',
        'notesinternal' => 'notes_internal',
    );

    return $aliases;
}

function mapUpdateHeaderColumns(array $headerRow, &$error = '')
{
    $aliases = getUpdateImportHeaderSourceAliases();
    $columnMap = array();

    foreach ($headerRow as $index => $headerText) {
        $normalizedHeader = normalizeUpdateImportHeaderName($headerText);
        if ($normalizedHeader === '' || !isset($aliases[$normalizedHeader])) {
            continue;
        }
        $sourceKey = $aliases[$normalizedHeader];
        if (isset($columnMap[$sourceKey])) {
            $error = 'Duplicate header mapping for "' . trim((string)$headerText) . '".';
            return array();
        }
        $columnMap[$sourceKey] = $index;
    }

    foreach (array('specimen_ID') as $requiredColumn) {
        if (!isset($columnMap[$requiredColumn])) {
            $error = 'The header line must contain specimen_id.';
            return array();
        }
    }

    return $columnMap;
}

function getImportSourceValue(array $row, $sourceKey, array $columnMap)
{
    if (!isset($columnMap[$sourceKey])) {
        return '';
    }

    return getCsvValue($row, $columnMap[$sourceKey]);
}

function hasImportSourceColumn(array $columnMap, $sourceKey)
{
    return isset($columnMap[$sourceKey]);
}

function hasAnyImportSourceColumn(array $columnMap, array $sourceKeys)
{
    foreach ($sourceKeys as $sourceKey) {
        if (hasImportSourceColumn($columnMap, $sourceKey)) {
            return true;
        }
    }

    return false;
}

function getVisibleUpdateFields(array $columnMap, $hasHeaderLine = false)
{
    if (!$hasHeaderLine) {
        return array_keys(getUpdateFieldDefinitions());
    }

    $visible = array();
    foreach ($columnMap as $sourceKey => $columnIndex) {
        switch ($sourceKey) {
            case 'specimen_ID':
            case 'HerbNummer':
            case 'collectionID':
                break;
            case 'collector':
            case 'collector1':
            case 'collector2':
                $visible[] = 'SammlerID';
                $visible[] = 'Sammler_2ID';
                break;
            case 'lat_cardinal':
            case 'lat_deg':
            case 'lat_min':
            case 'lat_sec':
                $visible = array_merge($visible, array('Coord_N', 'N_Min', 'N_Sec', 'Coord_S', 'S_Min', 'S_Sec'));
                break;
            case 'lon_cardinal':
            case 'lon_deg':
            case 'lon_min':
            case 'lon_sec':
                $visible = array_merge($visible, array('Coord_W', 'W_Min', 'W_Sec', 'Coord_E', 'E_Min', 'E_Sec'));
                break;
            default:
                if (array_key_exists($sourceKey, getUpdateFieldDefinitions())) {
                    $visible[] = $sourceKey;
                }
                break;
        }
    }

    $visible = array_values(array_unique($visible));
    $ordered = array();
    foreach (getUpdateFieldDefinitions() as $field => $definition) {
        if (in_array($field, $visible, true)) {
            $ordered[] = $field;
        }
    }

    return $ordered;
}

function splitImportHerbNummer($rawValue)
{
    $pieces = explode('_', (string)$rawValue);
    if (count($pieces) > 1) {
        return trim($pieces[1]);
    }

    return trim((string)$rawValue);
}

function splitImportCollectors($collectorText)
{
    $collectorText = trim((string)$collectorText);
    if ($collectorText === '') {
        return array('', '');
    }
    if (substr($collectorText, -6) === 'et al.') {
        return array(trim(substr($collectorText, 0, -7)), 'et al.');
    }

    $collectors = trim(strtr($collectorText, '&', ','));
    $parts = explode(', ', $collectors);
    $collector1 = trim($parts[0]);
    $collector2 = trim(substr($collectorText, strlen($collector1) + 2));

    return array($collector1, $collector2);
}

function getTaxonText($id, $withAuthors = true)
{
    $sql = "SELECT tg.genus,
             ta0.author author0, ta1.author author1, ta2.author author2, ta3.author author3, ta4.author author4, ta5.author author5,
             te0.epithet epithet0, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3, te4.epithet epithet4, te5.epithet epithet5
            FROM tbl_tax_species ts
             LEFT JOIN tbl_tax_authors ta0 ON ta0.authorID = ts.authorID
             LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID = ts.subspecies_authorID
             LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID = ts.variety_authorID
             LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID = ts.subvariety_authorID
             LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID = ts.forma_authorID
             LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID = ts.subforma_authorID
             LEFT JOIN tbl_tax_epithets te0 ON te0.epithetID = ts.speciesID
             LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID = ts.subspeciesID
             LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID = ts.varietyID
             LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID = ts.subvarietyID
             LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID = ts.formaID
             LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID = ts.subformaID
             LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
            WHERE ts.taxonID = '" . intval($id) . "'";
    $result = dbi_query($sql);
    if (!$result || mysqli_num_rows($result) === 0) {
        return '';
    }

    $row = $result->fetch_array();
    $text = $row['genus'];
    if ($row['epithet0']) $text .= ' ' . $row['epithet0'] . (($withAuthors && !empty($row['author0'])) ? ' ' . $row['author0'] : '');
    if ($row['epithet1']) $text .= ' subsp. ' . $row['epithet1'] . (($withAuthors && !empty($row['author1'])) ? ' ' . $row['author1'] : '');
    if ($row['epithet2']) $text .= ' var. ' . $row['epithet2'] . (($withAuthors && !empty($row['author2'])) ? ' ' . $row['author2'] : '');
    if ($row['epithet3']) $text .= ' subvar. ' . $row['epithet3'] . (($withAuthors && !empty($row['author3'])) ? ' ' . $row['author3'] : '');
    if ($row['epithet4']) $text .= ' forma ' . $row['epithet4'] . (($withAuthors && !empty($row['author4'])) ? ' ' . $row['author4'] : '');
    if ($row['epithet5']) $text .= ' subf. ' . $row['epithet5'] . (($withAuthors && !empty($row['author5'])) ? ' ' . $row['author5'] : '');

    return trim($text);
}

function formatDisplayValue($key, $value)
{
    if ($key === 'digital_image' || $key === 'digital_image_obs' || $key === 'observation') {
        return (!empty($value)) ? 'yes' : '';
    }

    return (string)$value;
}

function normalizeValueForType($type, $value)
{
    if ($type === 'int') {
        return (intval($value) !== 0) ? intval($value) : '';
    }
    if ($type === 'numeric') {
        if ($value === null || trim((string)$value) === '') {
            return '';
        }
        $normalized = strtr(trim((string)$value), ',', '.');
        if (!is_numeric($normalized)) {
            return trim((string)$value);
        }
        return (string)(0 + $normalized);
    }
    if ($type === 'nullable_bool') {
        if ($value === null || $value === '' || $value === 0 || $value === '0') {
            return '';
        }
        return 1;
    }
    if ($type === 'bool') {
        return !empty($value) ? 1 : 0;
    }
    if ($value === null) {
        return '';
    }

    return (string)$value;
}

function normalizeComparableFieldValue($field, $type, $value)
{
    $normalized = normalizeValueForType($type, $value);
    $multilineComparableFields = array(
        'Fundort',
        'Fundort_engl',
        'habitat',
        'habitus',
        'Bemerkungen',
        'notes_internal',
    );

    if (in_array($field, $multilineComparableFields, true)) {
        $normalized = preg_replace('/\s+/u', ' ', str_replace(array("\r\n", "\r", "\n"), ' ', (string)$normalized));
        return trim((string)$normalized);
    }

    return $normalized;
}

function normalizeImportTaxonText($taxonText)
{
    $taxonText = trim((string)$taxonText);
    if ($taxonText === '') {
        return '';
    }

    $parts = preg_split('/\s+/u', $taxonText);
    if (is_array($parts) && count($parts) > 1 && preg_match('/^\p{Lu}/u', $parts[1])) {
        return trim((string)$parts[0]);
    }

    $parser = TaxonTokenizer::Load();
    $taxonParts = $parser->tokenize($taxonText);
    if (!empty($taxonParts['genus']) && empty($taxonParts['epithet']) && empty($taxonParts['subepithet'])) {
        return trim((string)$taxonParts['genus']);
    }

    return $taxonText;
}

function resolveImportTaxon($taxonText, &$statusCodes)
{
    $taxonText = trim((string)$taxonText);
    if ($taxonText === '') {
        addStatusCode($statusCodes, 'no_taxa');
        addStatusCode($statusCodes, 'no_genus');
        return '';
    }

    $matchTaxonText = normalizeImportTaxonText($taxonText);
    $pieces = explode(' ', $matchTaxonText, 3);
    $result = dbi_query('SELECT genID FROM tbl_tax_genera WHERE genus = ' . quoteString($pieces[0]));
    if (!$result || mysqli_num_rows($result) === 0) {
        addStatusCode($statusCodes, 'no_taxa');
        addStatusCode($statusCodes, 'no_genus');
        return '';
    }

    $genusRow = mysqli_fetch_array($result);
    $genId = $genusRow['genID'];

    if (count($pieces) > 1) {
        $result = dbi_query('SELECT epithetID FROM tbl_tax_epithets WHERE epithet = ' . quoteString($pieces[1]));
        if ($result && mysqli_num_rows($result) > 0) {
            $epithetRow = mysqli_fetch_array($result);
            $epithetId = $epithetRow['epithetID'];
            $candidates = dbi_query("SELECT taxonID FROM tbl_tax_species WHERE genID = '" . intval($genId) . "' AND speciesID = '" . intval($epithetId) . "'");
            if ($candidates && mysqli_num_rows($candidates) > 0) {
                while ($candidate = mysqli_fetch_array($candidates)) {
                    $taxonId = intval($candidate['taxonID']);
                    if (strcmp(trim(getTaxonText($taxonId, true)), $taxonText) === 0
                        || strcmp(trim(getTaxonText($taxonId, false)), $taxonText) === 0
                        || strcmp(trim(getTaxonText($taxonId, true)), $matchTaxonText) === 0
                        || strcmp(trim(getTaxonText($taxonId, false)), $matchTaxonText) === 0) {
                        return $taxonId;
                    }
                }
            }
        }
    } else {
        $sql = "SELECT taxonID
                FROM tbl_tax_species
                WHERE genID = '" . intval($genId) . "'
                 AND speciesID IS NULL
                 AND subspeciesID IS NULL
                 AND varietyID IS NULL
                 AND subvarietyID IS NULL
                 AND formaID IS NULL
                 AND subformaID IS NULL";
        $result = dbi_query($sql);
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_array($result);
            return intval($row['taxonID']);
        }
    }

    addStatusCode($statusCodes, 'no_taxa');
    return '';
}

function getSimilarTaxaSuggestions($taxonText)
{
    global $_OPTIONS;

    static $suggestionCache = array();
    static $lookupCount = 0;

    $taxonText = normalizeImportTaxonText($taxonText);
    if ($taxonText === '' || empty($_OPTIONS['serviceTaxamatch'])) {
        return array();
    }

    if (isset($suggestionCache[$taxonText])) {
        return $suggestionCache[$taxonText];
    }

    if ($lookupCount >= 3 || (isset($_SERVER['REQUEST_TIME_FLOAT']) && microtime(true) - floatval($_SERVER['REQUEST_TIME_FLOAT']) > 20)) {
        $suggestionCache[$taxonText] = array();
        return array();
    }

    $ranks = array('', ' subsp. ', ' var. ', ' subvar. ', ' forma ', ' subforma ');
    $parser = TaxonTokenizer::Load();
    $taxonParts = $parser->tokenize($taxonText);
    if (empty($taxonParts['genus'])) {
        $suggestionCache[$taxonText] = array();
        return array();
    }

    $rankIndex = isset($taxonParts['rank']) ? intval($taxonParts['rank']) : 0;
    $rankText = isset($ranks[$rankIndex]) ? $ranks[$rankIndex] : '';
    $searchText = trim(
        (isset($taxonParts['genus']) ? $taxonParts['genus'] : '') . ' ' .
        (isset($taxonParts['epithet']) ? $taxonParts['epithet'] : '') .
        $rankText .
        (isset($taxonParts['subepithet']) ? $taxonParts['subepithet'] : '')
    );
    if ($searchText === '') {
        $suggestionCache[$taxonText] = array();
        return array();
    }

    $suggestions = array();
    $lookupCount++;
    $service = new JsonRPCClient($_OPTIONS['serviceTaxamatch']);
    if (method_exists($service, 'setRPCTimeouts')) {
        $service->setRPCTimeouts(1, 2);
    }

    try {
        $matches = $service->getMatchesService('vienna', $searchText, array('showSyn' => false, 'NearMatch' => false));
        if (isset($matches['result'][0]['searchresult'])) {
            foreach ($matches['result'][0]['searchresult'] as $result) {
                if (empty($result['species']) || !is_array($result['species'])) {
                    continue;
                }
                foreach ($result['species'] as $species) {
                    $taxonId = isset($species['taxonID']) ? intval($species['taxonID']) : 0;
                    $taxonLabel = isset($species['taxon']) ? trim((string)$species['taxon']) : '';
                    if ($taxonId > 0 && $taxonLabel !== '') {
                        $suggestions[$taxonId] = array(
                            'taxonID' => $taxonId,
                            'taxon' => $taxonLabel,
                        );
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log('TaxaMatch suggestion failed in updateSpecimens.php: ' . $e->getMessage());
    }

    $suggestionCache[$taxonText] = array_values($suggestions);
    return $suggestionCache[$taxonText];
}


function buildParsedUpdateRow(array $rawRow, $lineNumber, array $columnMap)
{
    $normalized = initializeFieldArray();
    $display = initializeFieldArray();
    $statusCodes = array();

    $specimenId = 0;
    $specimenIdText = getImportSourceValue($rawRow, 'specimen_ID', $columnMap);
    if ($specimenIdText === '' || !ctype_digit($specimenIdText) || intval($specimenIdText) <= 0) {
        addStatusCode($statusCodes, 'no_specimen_id');
    } else {
        $specimenId = intval($specimenIdText);
    }

    $collectionId = hasImportSourceColumn($columnMap, 'collectionID') ? intval(getImportSourceValue($rawRow, 'collectionID', $columnMap)) : 0;

    $herbNummer = splitImportHerbNummer(getImportSourceValue($rawRow, 'HerbNummer', $columnMap));
    $normalized['CollNummer'] = getImportSourceValue($rawRow, 'CollNummer', $columnMap);
    $display['CollNummer'] = $normalized['CollNummer'];

    $identStatusText = getImportSourceValue($rawRow, 'identstatusID', $columnMap);
    $display['identstatusID'] = $identStatusText;
    if ($identStatusText !== '') {
        $result = dbi_query('SELECT identstatusID FROM tbl_specimens_identstatus WHERE identification_status = ' . quoteString($identStatusText));
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_array($result);
            $normalized['identstatusID'] = intval($row['identstatusID']);
        } else {
            addStatusCode($statusCodes, 'no_identstatus');
        }
    }

    $taxonText = getImportSourceValue($rawRow, 'taxonID', $columnMap);
    $display['taxonID'] = $taxonText;
    if (hasImportSourceColumn($columnMap, 'taxonID')) {
        $normalized['taxonID'] = resolveImportTaxon($taxonText, $statusCodes);
    }
    $taxonSuggestions = array();
    if ($normalized['taxonID'] === '' && in_array('no_taxa', $statusCodes, true) && !in_array('no_genus', $statusCodes, true)) {
        $taxonSuggestions = getSimilarTaxaSuggestions($taxonText);
        if (!empty($taxonSuggestions)) {
            addStatusCode($statusCodes, 'similar_taxa');
        }
    }

    $collector1 = '';
    $collector2 = '';
    if (hasImportSourceColumn($columnMap, 'collector')) {
        list($collector1, $collector2) = splitImportCollectors(getImportSourceValue($rawRow, 'collector', $columnMap));
    } else {
        $collector1 = getImportSourceValue($rawRow, 'collector1', $columnMap);
        $collector2 = getImportSourceValue($rawRow, 'collector2', $columnMap);
    }
    $display['SammlerID'] = $collector1;
    $display['Sammler_2ID'] = $collector2;
    $collectorsOk = false;
    if (!hasAnyImportSourceColumn($columnMap, array('collector', 'collector1', 'collector2'))) {
        $collectorsOk = true;
    } elseif ($collector1 !== '') {
        $result = dbi_query('SELECT SammlerID FROM tbl_collector WHERE Sammler = ' . quoteString($collector1));
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_array($result);
            $normalized['SammlerID'] = intval($row['SammlerID']);
            if ($collector2 !== '') {
                $result2 = dbi_query('SELECT Sammler_2ID FROM tbl_collector_2 WHERE Sammler_2 = ' . quoteString($collector2));
                if ($result2 && mysqli_num_rows($result2) > 0) {
                    $row2 = mysqli_fetch_array($result2);
                    $normalized['Sammler_2ID'] = intval($row2['Sammler_2ID']);
                    $collectorsOk = true;
                }
            } else {
                $normalized['Sammler_2ID'] = '';
                $collectorsOk = true;
            }
        }
    }
    if (!$collectorsOk) {
        addStatusCode($statusCodes, 'no_collector');
    }

    $seriesText = getImportSourceValue($rawRow, 'seriesID', $columnMap);
    $display['seriesID'] = $seriesText;
    if ($seriesText !== '') {
        $result = dbi_query('SELECT seriesID FROM tbl_specimens_series WHERE series = ' . quoteString($seriesText));
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_array($result);
            $normalized['seriesID'] = intval($row['seriesID']);
        } else {
            addStatusCode($statusCodes, 'no_series');
        }
    }

    $simpleMap = array(
        'series_number' => 'series_number',
        'Nummer' => 'Nummer',
        'alt_number' => 'alt_number',
        'Datum' => 'Datum',
        'Datum2' => 'Datum2',
        'det' => 'det',
        'typified' => 'typified',
        'taxon_alt' => 'taxon_alt',
        'Fundort' => 'Fundort',
        'Fundort_engl' => 'Fundort_engl',
        'habitat' => 'habitat',
        'habitus' => 'habitus',
        'Bemerkungen' => 'Bemerkungen',
        'notes_internal' => 'notes_internal',
    );
    foreach ($simpleMap as $field => $sourceKey) {
        $normalized[$field] = getImportSourceValue($rawRow, $sourceKey, $columnMap);
        $display[$field] = $normalized[$field];
    }

    $typeText = getImportSourceValue($rawRow, 'typusID', $columnMap);
    $display['typusID'] = $typeText;
    if ($typeText !== '') {
        $result = dbi_query('SELECT typusID FROM tbl_typi WHERE typus_lat = ' . quoteString($typeText));
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_array($result);
            $normalized['typusID'] = intval($row['typusID']);
        } else {
            addStatusCode($statusCodes, 'no_type');
        }
    }

    $nationText = getImportSourceValue($rawRow, 'NationID', $columnMap);
    $display['NationID'] = $nationText;
    if ($nationText !== '') {
        $result = dbi_query('SELECT nationID FROM tbl_geo_nation WHERE nation_engl = ' . quoteString($nationText));
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_array($result);
            $normalized['NationID'] = intval($row['nationID']);
        } else {
            addStatusCode($statusCodes, 'no_nation');
        }
    }

    $provinceText = getImportSourceValue($rawRow, 'provinceID', $columnMap);
    $display['provinceID'] = $provinceText;
    if ($provinceText !== '') {
        $sql = 'SELECT provinceID FROM tbl_geo_province WHERE provinz = ' . quoteString($provinceText) . ' AND nationID = ' . makeInt($normalized['NationID']);
        $result = dbi_query($sql);
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_array($result);
            $normalized['provinceID'] = intval($row['provinceID']);
        } else {
            addStatusCode($statusCodes, 'no_province');
        }
    }

    $hasDirectLatColumns = isset($columnMap['Coord_N']) || isset($columnMap['N_Min']) || isset($columnMap['N_Sec']) || isset($columnMap['Coord_S']) || isset($columnMap['S_Min']) || isset($columnMap['S_Sec']);
    if ($hasDirectLatColumns) {
        $normalized['Coord_N'] = getImportSourceValue($rawRow, 'Coord_N', $columnMap) !== '' ? intval(getImportSourceValue($rawRow, 'Coord_N', $columnMap)) : '';
        $normalized['N_Min'] = getImportSourceValue($rawRow, 'N_Min', $columnMap) !== '' ? intval(getImportSourceValue($rawRow, 'N_Min', $columnMap)) : '';
        $normalized['N_Sec'] = strtr(getImportSourceValue($rawRow, 'N_Sec', $columnMap), ',', '.');
        $normalized['Coord_S'] = getImportSourceValue($rawRow, 'Coord_S', $columnMap) !== '' ? intval(getImportSourceValue($rawRow, 'Coord_S', $columnMap)) : '';
        $normalized['S_Min'] = getImportSourceValue($rawRow, 'S_Min', $columnMap) !== '' ? intval(getImportSourceValue($rawRow, 'S_Min', $columnMap)) : '';
        $normalized['S_Sec'] = strtr(getImportSourceValue($rawRow, 'S_Sec', $columnMap), ',', '.');
        if (($normalized['N_Sec'] !== '' && !is_numeric($normalized['N_Sec'])) || ($normalized['S_Sec'] !== '' && !is_numeric($normalized['S_Sec']))) {
            addStatusCode($statusCodes, 'no_numeric_sec_lat');
        }
    } else {
        $latCardinal = getImportSourceValue($rawRow, 'lat_cardinal', $columnMap);
        $latDeg = getImportSourceValue($rawRow, 'lat_deg', $columnMap);
        $latMin = getImportSourceValue($rawRow, 'lat_min', $columnMap);
        $latSec = strtr(getImportSourceValue($rawRow, 'lat_sec', $columnMap), ',', '.');
        if ($latSec !== '' && !is_numeric($latSec)) {
            addStatusCode($statusCodes, 'no_numeric_sec_lat');
        }
        $normalized['Coord_N'] = ($latCardinal === 'N') ? (($latDeg !== '') ? intval($latDeg) : '') : '';
        $normalized['N_Min'] = ($latCardinal === 'N') ? (($latMin !== '') ? intval($latMin) : '') : '';
        $normalized['N_Sec'] = ($latCardinal === 'N') ? $latSec : '';
        $normalized['Coord_S'] = ($latCardinal === 'S') ? (($latDeg !== '') ? intval($latDeg) : '') : '';
        $normalized['S_Min'] = ($latCardinal === 'S') ? (($latMin !== '') ? intval($latMin) : '') : '';
        $normalized['S_Sec'] = ($latCardinal === 'S') ? $latSec : '';
    }

    $hasDirectLonColumns = isset($columnMap['Coord_W']) || isset($columnMap['W_Min']) || isset($columnMap['W_Sec']) || isset($columnMap['Coord_E']) || isset($columnMap['E_Min']) || isset($columnMap['E_Sec']);
    if ($hasDirectLonColumns) {
        $normalized['Coord_W'] = getImportSourceValue($rawRow, 'Coord_W', $columnMap) !== '' ? intval(getImportSourceValue($rawRow, 'Coord_W', $columnMap)) : '';
        $normalized['W_Min'] = getImportSourceValue($rawRow, 'W_Min', $columnMap) !== '' ? intval(getImportSourceValue($rawRow, 'W_Min', $columnMap)) : '';
        $normalized['W_Sec'] = strtr(getImportSourceValue($rawRow, 'W_Sec', $columnMap), ',', '.');
        $normalized['Coord_E'] = getImportSourceValue($rawRow, 'Coord_E', $columnMap) !== '' ? intval(getImportSourceValue($rawRow, 'Coord_E', $columnMap)) : '';
        $normalized['E_Min'] = getImportSourceValue($rawRow, 'E_Min', $columnMap) !== '' ? intval(getImportSourceValue($rawRow, 'E_Min', $columnMap)) : '';
        $normalized['E_Sec'] = strtr(getImportSourceValue($rawRow, 'E_Sec', $columnMap), ',', '.');
        if (($normalized['W_Sec'] !== '' && !is_numeric($normalized['W_Sec'])) || ($normalized['E_Sec'] !== '' && !is_numeric($normalized['E_Sec']))) {
            addStatusCode($statusCodes, 'no_numeric_sec_lon');
        }
    } else {
        $lonCardinal = getImportSourceValue($rawRow, 'lon_cardinal', $columnMap);
        $lonDeg = getImportSourceValue($rawRow, 'lon_deg', $columnMap);
        $lonMin = getImportSourceValue($rawRow, 'lon_min', $columnMap);
        $lonSec = strtr(getImportSourceValue($rawRow, 'lon_sec', $columnMap), ',', '.');
        if ($lonSec !== '' && !is_numeric($lonSec)) {
            addStatusCode($statusCodes, 'no_numeric_sec_lon');
        }
        $normalized['Coord_W'] = ($lonCardinal === 'W') ? (($lonDeg !== '') ? intval($lonDeg) : '') : '';
        $normalized['W_Min'] = ($lonCardinal === 'W') ? (($lonMin !== '') ? intval($lonMin) : '') : '';
        $normalized['W_Sec'] = ($lonCardinal === 'W') ? $lonSec : '';
        $normalized['Coord_E'] = ($lonCardinal === 'E') ? (($lonDeg !== '') ? intval($lonDeg) : '') : '';
        $normalized['E_Min'] = ($lonCardinal === 'E') ? (($lonMin !== '') ? intval($lonMin) : '') : '';
        $normalized['E_Sec'] = ($lonCardinal === 'E') ? $lonSec : '';
    }

    foreach (array('Coord_N', 'N_Min', 'N_Sec', 'Coord_S', 'S_Min', 'S_Sec', 'Coord_W', 'W_Min', 'W_Sec', 'Coord_E', 'E_Min', 'E_Sec') as $field) {
        $display[$field] = formatDisplayValue($field, $normalized[$field]);
    }

    $quadrant = getImportSourceValue($rawRow, 'quadrant', $columnMap);
    if ($quadrant !== '' && !is_numeric($quadrant)) {
        addStatusCode($statusCodes, 'no_numeric_quadrant');
    }
    $normalized['quadrant'] = $quadrant;
    $display['quadrant'] = $quadrant;

    $quadrantSub = getImportSourceValue($rawRow, 'quadrant_sub', $columnMap);
    if ($quadrantSub !== '' && !is_numeric($quadrantSub)) {
        addStatusCode($statusCodes, 'no_numeric_quadrant_sub');
    }
    $normalized['quadrant_sub'] = $quadrantSub;
    $display['quadrant_sub'] = $quadrantSub;

    $exactness = strtr(getImportSourceValue($rawRow, 'exactness', $columnMap), ',', '.');
    if ($exactness !== '' && !is_numeric($exactness)) {
        addStatusCode($statusCodes, 'no_numeric_exactness');
    }
    $normalized['exactness'] = $exactness;
    $display['exactness'] = $exactness;

    $normalized['altitude_min'] = getImportSourceValue($rawRow, 'altitude_min', $columnMap) !== '' ? intval(getImportSourceValue($rawRow, 'altitude_min', $columnMap)) : '';
    $normalized['altitude_max'] = getImportSourceValue($rawRow, 'altitude_max', $columnMap) !== '' ? intval(getImportSourceValue($rawRow, 'altitude_max', $columnMap)) : '';
    $display['altitude_min'] = formatDisplayValue('altitude_min', $normalized['altitude_min']);
    $display['altitude_max'] = formatDisplayValue('altitude_max', $normalized['altitude_max']);

    $normalized['digital_image'] = normalizeValueForType('nullable_bool', getImportSourceValue($rawRow, 'digital_image', $columnMap));
    $normalized['digital_image_obs'] = normalizeValueForType('nullable_bool', getImportSourceValue($rawRow, 'digital_image_obs', $columnMap));
    $normalized['observation'] = normalizeValueForType('nullable_bool', getImportSourceValue($rawRow, 'observation', $columnMap));
    $display['digital_image'] = formatDisplayValue('digital_image', $normalized['digital_image']);
    $display['digital_image_obs'] = formatDisplayValue('digital_image_obs', $normalized['digital_image_obs']);
    $display['observation'] = formatDisplayValue('observation', $normalized['observation']);

    return array(
        'lineNumber' => $lineNumber,
        'specimen_ID' => $specimenId,
        'collectionID' => $collectionId,
        'HerbNummer' => $herbNummer,
        'raw' => $rawRow,
        'normalized' => $normalized,
        'display' => $display,
        'statusCodes' => $statusCodes,
        'taxonSuggestions' => $taxonSuggestions,
    );
}

function loadUploadedUpdateRows($tmpPath, $hasHeaderLine = false, &$visibleFields = array(), &$error = '')
{
    $rows = array();
    if (!$hasHeaderLine) {
        $error = 'Only header-based update files are supported.';
        return $rows;
    }

    $handle = @fopen($tmpPath, 'r');
    if (!$handle) {
        return $rows;
    }

    $expectedColumnCount = 0;
    $lineNumber = 1;
    $headerRow = parseUpdateLine($handle, 1);
    if ($headerRow === false) {
        fclose($handle);
        $error = 'The uploaded file does not contain a readable header line.';
        return array();
    }
    $expectedColumnCount = count($headerRow);
    $columnMap = mapUpdateHeaderColumns($headerRow, $error);
    if ($columnMap === array()) {
        fclose($handle);
        return array();
    }
    $lineNumber = 2;
    $visibleFields = getVisibleUpdateFields($columnMap, $hasHeaderLine);

    while (!feof($handle)) {
        $parts = parseUpdateLine($handle, 1);
        if ($parts !== false) {
            if ($hasHeaderLine && count($parts) !== $expectedColumnCount) {
                fclose($handle);
                $error = 'Header/data column count mismatch on line ' . $lineNumber . ': header has ' . $expectedColumnCount . ' column(s), row has ' . count($parts) . '. Processing was aborted.';
                return array();
            }
            $rows[] = buildParsedUpdateRow($parts, $lineNumber, $columnMap);
            $lineNumber++;
        }
    }
    fclose($handle);

    return $rows;
}

function applyActiveFieldMask(array $importNormalized, array $importDisplay, array $databaseNormalized, array $databaseDisplay, array $activeFields)
{
    foreach (getUpdateFieldDefinitions() as $field => $definition) {
        if (in_array($field, $activeFields, true)) {
            continue;
        }
        $importNormalized[$field] = $databaseNormalized[$field];
        $importDisplay[$field] = $databaseDisplay[$field];
    }

    return array($importNormalized, $importDisplay);
}

function findMatchingSpecimenId($specimenId)
{
    if (intval($specimenId) <= 0) {
        return array('specimen_ID' => null, 'error' => 'no_specimen_id');
    }

    $sql = "SELECT specimen_ID FROM tbl_specimens WHERE specimen_ID = '" . intval($specimenId) . "'";
    $result = dbi_query($sql);
    if (!$result || mysqli_num_rows($result) === 0) {
        return array('specimen_ID' => null, 'error' => 'no_specimen');
    }
    if (mysqli_num_rows($result) > 1) {
        return array('specimen_ID' => null, 'error' => 'multiple_specimens');
    }

    $row = mysqli_fetch_array($result);
    return array('specimen_ID' => intval($row['specimen_ID']), 'error' => '');
}

function fetchExistingSpecimenData($specimenId)
{
    $sql = "SELECT s.specimen_ID, s.HerbNummer, s.collectionID, s.CollNummer, s.identstatusID, sis.identification_status,
                   s.taxonID, s.SammlerID, c.Sammler, s.Sammler_2ID, c2.Sammler_2,
                   s.seriesID, ss.series, s.series_number, s.Nummer, s.alt_number,
                   s.Datum, s.Datum2, s.det, s.typified, s.typusID, ty.typus_lat,
                   s.taxon_alt, s.NationID, n.nation_engl, s.provinceID, p.provinz,
                   s.Fundort, s.Fundort_engl, s.habitat, s.habitus, s.Bemerkungen,
                   s.Coord_N, s.N_Min, s.N_Sec, s.Coord_S, s.S_Min, s.S_Sec,
                   s.Coord_W, s.W_Min, s.W_Sec, s.Coord_E, s.E_Min, s.E_Sec,
                   s.quadrant, s.quadrant_sub, s.exactness, s.altitude_min, s.altitude_max,
                   s.digital_image, s.digital_image_obs, s.observation, s.notes_internal
            FROM tbl_specimens s
             LEFT JOIN tbl_specimens_identstatus sis ON sis.identstatusID = s.identstatusID
             LEFT JOIN tbl_collector c ON c.SammlerID = s.SammlerID
             LEFT JOIN tbl_collector_2 c2 ON c2.Sammler_2ID = s.Sammler_2ID
             LEFT JOIN tbl_specimens_series ss ON ss.seriesID = s.seriesID
             LEFT JOIN tbl_typi ty ON ty.typusID = s.typusID
             LEFT JOIN tbl_geo_nation n ON n.nationID = s.NationID
             LEFT JOIN tbl_geo_province p ON p.provinceID = s.provinceID
            WHERE s.specimen_ID = '" . intval($specimenId) . "'";
    $result = dbi_query($sql);
    if (!$result || mysqli_num_rows($result) === 0) {
        return null;
    }

    $row = mysqli_fetch_array($result);
    $normalized = initializeFieldArray();
    $display = initializeFieldArray();
    $definitions = getUpdateFieldDefinitions();

    foreach ($definitions as $field => $definition) {
        if (array_key_exists($field, $row)) {
            $normalized[$field] = normalizeValueForType($definition['type'], $row[$field]);
            $display[$field] = formatDisplayValue($field, $normalized[$field]);
        }
    }

    $display['identstatusID'] = $row['identification_status'] ?: '';
    $display['taxonID'] = !empty($row['taxonID']) ? getTaxonText($row['taxonID'], true) : '';
    $display['SammlerID'] = $row['Sammler'] ?: '';
    $display['Sammler_2ID'] = $row['Sammler_2'] ?: '';
    $display['seriesID'] = $row['series'] ?: '';
    $display['typusID'] = $row['typus_lat'] ?: '';
    $display['NationID'] = $row['nation_engl'] ?: '';
    $display['provinceID'] = $row['provinz'] ?: '';
    $display['digital_image'] = formatDisplayValue('digital_image', $normalized['digital_image']);
    $display['digital_image_obs'] = formatDisplayValue('digital_image_obs', $normalized['digital_image_obs']);
    $display['observation'] = formatDisplayValue('observation', $normalized['observation']);

    return array(
        'normalized' => $normalized,
        'display' => $display,
        'meta' => array(
            'HerbNummer' => isset($row['HerbNummer']) ? trim((string)$row['HerbNummer']) : '',
            'collectionID' => isset($row['collectionID']) ? intval($row['collectionID']) : 0,
        ),
    );
}

function userCanUpdateSpecimen($specimenId)
{
    if (Permission::has('admin') || Permission::has('editor')) {
        return true;
    }

    $sql = "SELECT mc.source_id
            FROM tbl_specimens s
             INNER JOIN tbl_management_collections mc ON mc.collectionID = s.collectionID
            WHERE s.specimen_ID = '" . intval($specimenId) . "'";
    $result = dbi_query($sql);
    if (!$result || mysqli_num_rows($result) === 0) {
        return false;
    }

    $row = mysqli_fetch_array($result);
    return intval($row['source_id']) === intval($_SESSION['sid']);
}

function decodeUpdatePayload($json)
{
    $decoded = json_decode((string)$json, true);
    return is_array($decoded) ? $decoded : array();
}

function buildSqlValue($type, $value)
{
    if ($type === 'int') {
        return makeInt($value);
    }
    if ($type === 'numeric') {
        return quoteString((string)$value);
    }
    if ($type === 'nullable_bool') {
        return !empty($value) ? "'1'" : 'NULL';
    }
    if ($type === 'bool') {
        return !empty($value) ? "'1'" : "'0'";
    }

    return quoteString((string)$value);
}

function buildSpecimenUpdateAssignments(array $selectedData, array $currentData)
{
    $definitions = getUpdateFieldDefinitions();
    $assignments = array();
    $changedFields = array();

    foreach ($definitions as $field => $definition) {
        $selectedValue = array_key_exists($field, $selectedData) ? normalizeValueForType($definition['type'], $selectedData[$field]) : '';
        $currentValue = array_key_exists($field, $currentData) ? normalizeValueForType($definition['type'], $currentData[$field]) : '';
        $selectedComparable = normalizeComparableFieldValue($field, $definition['type'], $selectedData[$field] ?? '');
        $currentComparable = normalizeComparableFieldValue($field, $definition['type'], $currentData[$field] ?? '');
        if ($selectedComparable !== $currentComparable) {
            $assignments[] = $field . ' = ' . buildSqlValue($definition['type'], $selectedValue);
            $changedFields[] = $field;
        }
    }

    return array($assignments, $changedFields);
}

function updateSpecimenRow($specimenId, array $selectedData)
{
    global $dbLink;

    $current = fetchExistingSpecimenData($specimenId);
    if ($current === null) {
        return array('success' => false, 'message' => 'Specimen not found.', 'changedFields' => array());
    }
    if (!userCanUpdateSpecimen($specimenId)) {
        return array('success' => false, 'message' => 'No write access for this specimen.', 'changedFields' => array());
    }

    list($assignments, $changedFields) = buildSpecimenUpdateAssignments($selectedData, $current['normalized']);
    if (empty($assignments)) {
        return array('success' => true, 'message' => 'No changes selected.', 'changedFields' => array());
    }

    $sql = 'UPDATE tbl_specimens SET ' . implode(', ', $assignments) . " WHERE specimen_ID = '" . intval($specimenId) . "'";

    $dbLink->begin_transaction();
    Log::specimen($specimenId, 1);
    $result = dbi_query($sql);
    if (!$result) {
        $message = $dbLink->errno . ': ' . $dbLink->error;
        $dbLink->rollback();
        return array('success' => false, 'message' => $message, 'changedFields' => array());
    }

    $dbLink->commit();
    return array('success' => true, 'message' => 'Updated.', 'changedFields' => $changedFields);
}
function renderUpdateInputForm($downloadUrl = '')
{
    echo "<input type='hidden' name='MAX_FILE_SIZE' value='" . UPDATE_SPECIMENS_MAX_FILE_SIZE . "' />\n"
       . "<div class='panel form-panel'>\n"
       . "  <div class='panel-heading'>Import Source</div>\n"
       . "  <div class='form-layout'>\n"
       . "    <div class='form-field'>\n"
       . "      <label class='form-label' for='update_userfile'>Import file</label>\n"
       . "      <div class='form-control-wrap'>\n"
       . "        <input id='update_userfile' name='userfile' type='file' class='form-control form-control-file'>\n"
       . "      </div>\n"
       . "    </div>\n"
       . "    <div class='form-field'>\n"
       . "      <label class='form-label' for='update_download_url'>Download URL</label>\n"
       . "      <div class='form-control-wrap'>\n"
       . "        <input id='update_download_url' name='download_url' type='url' class='form-control' value='" . htmlspecialchars($downloadUrl, ENT_QUOTES) . "' placeholder='https://...'>\n"
       . "      </div>\n"
       . "    </div>\n"
       . "    <div class='form-field'>\n"
       . "      <div class='form-label'>Allowed HTTPS hosts</div>\n"
       . "      <div class='form-control-wrap form-static-text'>" . htmlspecialchars(implode(', ', getAllowedImportDownloadHosts())) . "</div>\n"
       . "    </div>\n"
       . "  </div>\n"
       . "  <div class='form-actions'>\n"
       . "    <input type='submit' value='check Update' class='button-primary'>\n"
       . "  </div>\n"
       . "</div>\n"
       . "<div class='panel info-panel'>\n"
       . "  <div class='panel-heading'>Update Rules</div>\n"
       . "  <div class='notice form-notice'>\n"
       . "    <p>This tool only accepts semicolon-separated <strong>.txt</strong> or <strong>.csv</strong> files with a header line. Files can be uploaded directly or downloaded from an HTTPS URL.</p>\n"
       . "    <p>The header line must contain <strong>specimen_id</strong>. This is the central key for all updates. <strong>HerbNummer</strong> is optional; if it is present and differs from the current JACQ value for the same specimen, the row is flagged with a warning.</p>\n"
       . "    <p>Only the provided header fields are shown in the preview and considered for update. Allowed header attributes are exactly: <strong>specimen_id</strong>, <strong>HerbNummer</strong>, <strong>collectionID</strong>, <strong>CollectionNumber</strong>, <strong>status</strong>, <strong>taxon</strong>, <strong>Sammler</strong>, <strong>series</strong>, <strong>series_number</strong>, <strong>Nummer</strong>, <strong>alt_number</strong>, <strong>Datum</strong>, <strong>Datum2</strong>, <strong>det</strong>, <strong>typified</strong>, <strong>typus</strong>, <strong>taxon_alt</strong>, <strong>nation_engl</strong>, <strong>provinz</strong>, <strong>Fundort</strong>, <strong>Fundort_engl</strong>, <strong>Habitat</strong>, <strong>Habitus</strong>, <strong>Bemerkungen</strong>, <strong>coord_NS</strong>, <strong>lat_degree</strong>, <strong>lat_minute</strong>, <strong>lat_second</strong>, <strong>coord_WE</strong>, <strong>long_degree</strong>, <strong>long_minute</strong>, <strong>long_second</strong>, <strong>quadrant</strong>, <strong>quadrant_sub</strong>, <strong>exactness</strong>, <strong>alt_min</strong>, <strong>alt_max</strong>, <strong>digital_image</strong>, <strong>digital_image_obs</strong>, <strong>observation</strong> and <strong>notes_internal</strong>.</p>\n"
       . "  </div>\n"
       . "</div>\n";
}
function buildStatusText(array $statusCodes)
{
    return empty($statusCodes) ? 'OK' : implode(' ', $statusCodes);
}

function getBlockingStatusCodes()
{
    return array(
        'no_specimen_id',
        'no_collection',
        'no_specimen',
        'multiple_specimens',
        'duplicate_specimen',
    );
}

function getFallbackFieldsForStatus($statusCode)
{
    switch ($statusCode) {
        case 'no_identstatus':
            return array('identstatusID');
        case 'no_taxa':
        case 'no_genus':
            return array('taxonID');
        case 'no_collector':
            return array('SammlerID', 'Sammler_2ID');
        case 'no_series':
            return array('seriesID');
        case 'no_type':
            return array('typusID');
        case 'no_nation':
            return array('NationID');
        case 'no_province':
            return array('provinceID');
        case 'no_numeric_sec_lat':
            return array('Coord_N', 'N_Min', 'N_Sec', 'Coord_S', 'S_Min', 'S_Sec');
        case 'no_numeric_sec_lon':
            return array('Coord_W', 'W_Min', 'W_Sec', 'Coord_E', 'E_Min', 'E_Sec');
        case 'no_numeric_quadrant':
            return array('quadrant');
        case 'no_numeric_quadrant_sub':
            return array('quadrant_sub');
        case 'no_numeric_exactness':
            return array('exactness');
        default:
            return array();
    }
}

function hasHerbNummerMismatch($importHerbNummer, $databaseHerbNummer)
{
    $importHerbNummer = trim((string)$importHerbNummer);
    if ($importHerbNummer === '') {
        return false;
    }

    return strcmp($importHerbNummer, trim((string)$databaseHerbNummer)) !== 0;
}

function buildDefaultSelectedData(array $importNormalized, array $databaseNormalized, array $statusCodes)
{
    $selected = $importNormalized;

    foreach ($statusCodes as $statusCode) {
        foreach (getFallbackFieldsForStatus($statusCode) as $field) {
            if (array_key_exists($field, $databaseNormalized)) {
                $selected[$field] = $databaseNormalized[$field];
            }
        }
    }

    return $selected;
}

function buildLockedImportFields(array $statusCodes)
{
    $lockedFields = array();

    foreach ($statusCodes as $statusCode) {
        foreach (getFallbackFieldsForStatus($statusCode) as $field) {
            if (!in_array($field, $lockedFields, true)) {
                $lockedFields[] = $field;
            }
        }
    }

    return $lockedFields;
}

function buildWarningFieldStyles(array $statusCodes)
{
    $styles = array();

    foreach ($statusCodes as $statusCode) {
        if ($statusCode === 'similar_taxa') {
            $styles['taxonID'] = 'warning-similar';
            continue;
        }

        foreach (getFallbackFieldsForStatus($statusCode) as $field) {
            if (!isset($styles[$field])) {
                $styles[$field] = 'warning-invalid';
            }
        }
    }

    if (in_array('similar_taxa', $statusCodes, true)) {
        $styles['taxonID'] = 'warning-similar';
    }

    return $styles;
}

function getDifferingFields(array $importNormalized, array $databaseNormalized)
{
    $differingFields = array();

    foreach (getUpdateFieldDefinitions() as $field => $definition) {
        $importValue = array_key_exists($field, $importNormalized) ? normalizeComparableFieldValue($field, $definition['type'], $importNormalized[$field]) : '';
        $databaseValue = array_key_exists($field, $databaseNormalized) ? normalizeComparableFieldValue($field, $definition['type'], $databaseNormalized[$field]) : '';
        if ($importValue !== $databaseValue) {
            $differingFields[] = $field;
        }
    }

    return $differingFields;
}

$preRunPageError = '';
if (isset($_POST['reset_update_process']) || isset($_POST['cancel_update_input_encoding'])) {
    clearUpdateProcessContext();
    clearPendingUpdateInput();
    $_POST = array();
    $_FILES = array();
}
if (isset($_POST['archive_update_process'])) {
    $archiveError = '';
    if (sendUpdateProcessArchive($archiveError)) {
        exit;
    }
    $preRunPageError = $archiveError;
}

$downloadUrl = isset($_POST['download_url']) ? trim((string)$_POST['download_url']) : '';
$inputHeaderMode = 'with_header_line';
$run = detectUpdateRun();
$readyRows = array();
$issueRows = array();
$updateResults = array();
$pageError = $preRunPageError;
$seenSpecimens = array();
$changedColumns = array();
$displayFields = array_keys(getUpdateFieldDefinitions());
$encodingConfirmation = array();

if ($run === 2) {
    $rows = array();
    $inputPath = '';
    $inputName = '';
    $inputError = '';
    $inputCleanupNeeded = false;
    $hasUploadedFile = isset($_FILES['userfile']) && is_uploaded_file($_FILES['userfile']['tmp_name']);
    $hasDownloadUrl = ($downloadUrl !== '');

    $hasEncodingConversionRequest = isset($_POST['convert_update_input_encoding']);
    if (!$hasEncodingConversionRequest) {
        clearPendingUpdateInput();
    }

    if ($hasEncodingConversionRequest) {
        $pendingInput = getPendingUpdateInput();
        if (empty($pendingInput['input_file_path']) || !is_file($pendingInput['input_file_path'])) {
            $inputError = 'The file selected for conversion is no longer available. Please upload it again.';
        } elseif (!convertUpdateImportFileToUtf8($pendingInput['input_file_path'], $pendingInput['encoding'] ?? '', $inputPath, $inputError)) {
            // The conversion helper supplies the user-facing error message.
        } else {
            $inputName = $pendingInput['input_file_name'];
            $inputCleanupNeeded = true;
        }
    } elseif ($hasUploadedFile) {
        $inputPath = $_FILES['userfile']['tmp_name'];
        $inputName = isset($_FILES['userfile']['name']) ? (string)$_FILES['userfile']['name'] : '';
        if (!isAllowedUploadedUpdateFilename($inputName)) {
            $pageError = 'Only .txt or .csv update files with a header line are supported.';
            $inputPath = '';
        }
    } elseif ($hasDownloadUrl) {
        if (downloadImportInput($downloadUrl, $inputPath, $inputName, $inputError)) {
            $inputCleanupNeeded = true;
        }
    }

    if (isset($_POST['input_header_mode']) && $_POST['input_header_mode'] !== '' && $_POST['input_header_mode'] !== 'with_header_line') {
        $pageError = 'Only .txt or .csv update files with a header line are supported.';
    } elseif ($inputError !== '') {
        $pageError = $inputError;
    } elseif ($inputPath === '') {
        $pageError = 'No update file was provided.';
    } else {
        $inputEncoding = detectUpdateImportEncoding($inputPath);
        if ($inputEncoding === '') {
            $pageError = 'The file encoding could not be determined. Only UTF-8, Windows-1252 and ISO-8859-1 files are supported.';
        } elseif ($inputEncoding !== 'UTF-8') {
            if ($hasEncodingConversionRequest) {
                $pageError = 'The converted file is still not valid UTF-8.';
            } else {
                $pendingError = '';
                if (!storePendingUpdateInput($inputPath, $inputName, $inputEncoding, $pendingError)) {
                    $pageError = $pendingError;
                } else {
                    $encodingConfirmation = array(
                        'encoding' => $inputEncoding,
                        'filename' => sanitizeUpdateProcessFilename($inputName),
                    );
                }
            }
        } else {
            $headerError = '';
            $rows = loadUploadedUpdateRows($inputPath, true, $displayFields, $headerError);
        if ($headerError !== '') {
            $pageError = $headerError;
            clearUpdateProcessContext();
            clearPendingUpdateInput();
        } elseif (empty($rows)) {
            $pageError = 'The uploaded file could not be read or did not contain any usable data rows.';
            clearUpdateProcessContext();
            clearPendingUpdateInput();
        } elseif (!storeUpdateProcessInputFile($inputPath, $inputName, $inputError)) {
            $pageError = $inputError;
        } else {
            clearPendingUpdateInput();
            foreach ($rows as $row) {
                $statusCodes = $row['statusCodes'];
                $match = findMatchingSpecimenId($row['specimen_ID']);
                if ($match['error'] !== '') {
                    addStatusCode($statusCodes, $match['error']);
                }

                $blockingStatusCodes = array_values(array_intersect($statusCodes, getBlockingStatusCodes()));
                $warningStatusCodes = array_values(array_diff($statusCodes, getBlockingStatusCodes()));

                $specimenId = $match['specimen_ID'];
                if ($specimenId && isset($seenSpecimens[$specimenId])) {
                    addStatusCode($statusCodes, 'duplicate_specimen');
                    $specimenId = null;
                    $blockingStatusCodes = array_values(array_unique(array_merge($blockingStatusCodes, array('duplicate_specimen'))));
                    $warningStatusCodes = array_values(array_diff($statusCodes, getBlockingStatusCodes()));
                }

                $rowKey = 'line_' . $row['lineNumber'];
                $rowSummary = array(
                    'rowKey' => $rowKey,
                    'lineNumber' => $row['lineNumber'],
                    'fileSpecimenID' => $row['specimen_ID'],
                    'collectionID' => $row['collectionID'],
                    'HerbNummer' => $row['HerbNummer'],
                    'specimen_ID' => $specimenId,
                    'statusText' => buildStatusText($statusCodes),
                    'warningStatusText' => buildStatusText($warningStatusCodes),
                    'importDisplay' => $row['display'],
                    'importNormalized' => $row['normalized'],
                    'taxonSuggestions' => isset($row['taxonSuggestions']) ? $row['taxonSuggestions'] : array(),
                );

                if (!empty($blockingStatusCodes) || !$specimenId) {
                    $issueRows[] = $rowSummary;
                    continue;
                }

                $databaseRow = fetchExistingSpecimenData($specimenId);
                if ($databaseRow === null) {
                    $rowSummary['statusText'] = 'no_specimen';
                    $issueRows[] = $rowSummary;
                    continue;
                }

                $seenSpecimens[$specimenId] = true;
                list($maskedImportNormalized, $maskedImportDisplay) = applyActiveFieldMask($row['normalized'], $row['display'], $databaseRow['normalized'], $databaseRow['display'], $displayFields);
                $rowSummary['importNormalized'] = $maskedImportNormalized;
                $rowSummary['importDisplay'] = $maskedImportDisplay;
                $rowSummary['databaseDisplay'] = $databaseRow['display'];
                $rowSummary['databaseNormalized'] = $databaseRow['normalized'];
                $rowSummary['databaseMeta'] = isset($databaseRow['meta']) ? $databaseRow['meta'] : array();
                if (hasHerbNummerMismatch($row['HerbNummer'], $rowSummary['databaseMeta']['HerbNummer'] ?? '')) {
                    addStatusCode($warningStatusCodes, 'herbnummer_mismatch');
                }
                $rowSummary['warningStatusText'] = buildStatusText($warningStatusCodes);
                $rowSummary['selectedNormalized'] = buildDefaultSelectedData($maskedImportNormalized, $databaseRow['normalized'], $warningStatusCodes);
                $rowSummary['lockedImportFields'] = buildLockedImportFields($warningStatusCodes);
                $rowSummary['warningFieldStyles'] = buildWarningFieldStyles($warningStatusCodes);
                $rowSummary['differingFields'] = getDifferingFields($maskedImportNormalized, $databaseRow['normalized']);
                foreach ($rowSummary['differingFields'] as $field) {
                    $changedColumns[$field] = true;
                }
                $readyRows[] = $rowSummary;
            }
        }
        }

        if ($inputCleanupNeeded && is_file($inputPath)) {
            @unlink($inputPath);
        }
    }
} elseif ($run === 3) {
    if (isset($_POST['archive_update_process'])) {
        $updateResults = loadStoredUpdateProcessResults();
        if (empty($updateResults) && $pageError === '') {
            $pageError = 'No finished update process is available for archiving.';
        }
    } else {
        $payload = decodeUpdatePayload($_POST['update_payload']);
        if (empty($payload)) {
            $pageError = 'The submitted selection payload is empty or invalid.';
        } else {
            foreach ($payload as $rowKey => $rowData) {
                $specimenId = isset($rowData['specimen_ID']) ? intval($rowData['specimen_ID']) : 0;
                $selected = (isset($rowData['selected']) && is_array($rowData['selected'])) ? $rowData['selected'] : array();
                $result = updateSpecimenRow($specimenId, $selected);
                $updateResults[] = array(
                    'rowKey' => $rowKey,
                    'specimen_ID' => $specimenId,
                    'success' => $result['success'],
                    'message' => $result['message'],
                    'changedFields' => $result['changedFields'],
                );
            }
            storeUpdateProcessResults($updateResults);
        }
    }
}
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
        "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Update Specimens</title>
    <style type="text/css">
        :root {
            --page-bg: #cfd6d0;
            --panel-bg: #f7f8f5;
            --panel-border: #b5beb5;
            --panel-shadow: rgba(37, 58, 39, 0.08);
            --text-main: #213126;
            --text-muted: #5a6a60;
            --accent: #5b7c4d;
            --accent-dark: #3f5b36;
            --accent-soft: #dde7d6;
            --line: #9ca89c;
            --warning-soft: #f4ebc8;
            --error-soft: #f2d2d2;
            --table-head: #e4eadf;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            font-size: 13px;
            line-height: 1.45;
            color: var(--text-main);
            background: linear-gradient(180deg, #bfc8c1 0%, var(--page-bg) 100%);
        }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #888; padding: 7px 9px; vertical-align: top; }
        h1, h2 { margin: 0; }
        p { margin: 0 0 10px; }
        a { color: #2a4d7d; }
        .page-shell {
            max-width: 1680px;
            margin: 0 auto;
            padding: 28px 24px 40px;
        }
        .page-header {
            margin-bottom: 20px;
            padding: 18px 22px;
            border: 1px solid var(--panel-border);
            background: linear-gradient(135deg, #f5f7f2 0%, #e6ece1 100%);
            box-shadow: 0 12px 24px var(--panel-shadow);
        }
        .page-title {
            font-size: 28px;
            line-height: 1.1;
            color: #2f4636;
        }
        .page-subtitle {
            margin-top: 6px;
            color: var(--text-muted);
            font-size: 14px;
        }
        .panel {
            margin: 0 0 18px;
            padding: 18px 20px;
            border: 1px solid var(--panel-border);
            background: var(--panel-bg);
            box-shadow: 0 10px 24px var(--panel-shadow);
        }
        .panel-heading {
            margin-bottom: 14px;
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 0.02em;
            color: #2f4636;
            text-transform: uppercase;
        }
        .summary {
            margin: 0 0 18px;
            padding: 14px 18px;
            border: 1px solid #b8c7b2;
            background: var(--accent-soft);
            color: #2d4730;
            box-shadow: 0 8px 18px var(--panel-shadow);
        }
        .error {
            margin: 0 0 18px;
            padding: 12px 14px;
            border: 1px solid #c87f7f;
            background: var(--error-soft);
            color: #8b1f1f;
            box-shadow: 0 8px 18px rgba(128, 46, 46, 0.08);
        }
        .notice {
            margin: 0;
            color: var(--text-main);
        }
        .form-panel,
        .info-panel {
            max-width: 1120px;
        }
        .form-layout {
            display: grid;
            grid-template-columns: minmax(170px, 220px) minmax(0, 1fr);
            gap: 14px 18px;
            align-items: start;
        }
        .form-field {
            display: contents;
        }
        .form-label {
            padding-top: 10px;
            font-weight: bold;
            color: #35503d;
        }
        .form-control-wrap {
            min-width: 0;
        }
        .form-control {
            width: 100%;
            min-height: 38px;
            padding: 8px 10px;
            border: 1px solid #aeb8b1;
            background: #fff;
            color: var(--text-main);
            font: inherit;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(91, 124, 77, 0.16);
        }
        .form-control-file {
            padding: 6px 8px;
        }
        .form-static-text {
            min-height: 38px;
            padding: 9px 10px;
            border: 1px solid #d0d7d1;
            background: #eef2ec;
            color: #435248;
        }
        .form-actions {
            margin-top: 18px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        input[type="submit"],
        button {
            min-height: 36px;
            padding: 8px 14px;
            border: 1px solid #889687;
            background: linear-gradient(180deg, #ffffff 0%, #ecefe8 100%);
            color: var(--text-main);
            font: inherit;
            cursor: pointer;
        }
        input[type="submit"]:hover,
        button:hover {
            background: linear-gradient(180deg, #ffffff 0%, #dfe7d8 100%);
        }
        .button-primary {
            border-color: var(--accent-dark);
            background: linear-gradient(180deg, #6d915d 0%, #4f7143 100%);
            color: #fff;
            font-weight: bold;
        }
        .button-primary:hover {
            background: linear-gradient(180deg, #769c65 0%, #46663b 100%);
        }
        .form-notice p:last-child {
            margin-bottom: 0;
        }
        .action-buttons {
            margin: 0 0 18px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .action-buttons form {
            margin: 0;
        }
        .table-panel {
            padding: 16px 18px;
        }
        .table-panel table th {
            background: var(--table-head);
            text-align: left;
        }
        .compare-toolbar {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }
        .compare-container { overflow: auto; max-height: 75vh; max-width: 100%; margin: 0 0 15px; border: 1px solid #999; background: #fff; }
        .compare-table { border-collapse: separate; border-spacing: 0; width: max-content; }
        .compare-table thead th { position: sticky; top: 0; z-index: 3; }
        .compare-table thead th:first-child { left: 0; z-index: 5; background: #dfe7ef; }
        .compare-table th.compare-header { background: #e7eef7; min-width: 110px; }
        .compare-table td.compare-cell { min-width: 110px; }
        .compare-table td.selected, .compare-table th.selected { background: #d7e7c8; }
        .compare-table td.unselected, .compare-table th.unselected { background: #f7f7f7; }
        .compare-table tr.db-row td, .compare-table tr.db-row th { border-bottom: 2px solid #333; }
        .compare-cell.warning-field.warning-invalid { border-color: #c35757; }
        .compare-cell.warning-field.warning-similar { border-color: #d3ab2f; }
        .compare-cell.locked-import.warning-invalid { background: #f5d3d3; color: #7a1f1f; }
        .compare-cell.locked-import.warning-similar { background: #f3e7c2; color: #6b5d1a; }
        .compare-cell.selected.warning-field.warning-invalid { background: linear-gradient(135deg, #f5d3d3 0%, #f5d3d3 16%, #d7e7c8 16%, #d7e7c8 100%); }
        .compare-cell.selected.warning-field.warning-similar { background: linear-gradient(135deg, #f3e7c2 0%, #f3e7c2 16%, #d7e7c8 16%, #d7e7c8 100%); }
        .compare-cell.unselected.warning-field.warning-invalid { background: #f5d3d3; color: #7a1f1f; }
        .compare-cell.unselected.warning-field.warning-similar { background: #f3e7c2; color: #6b5d1a; }
        .compare-cell.identical, .compare-header.identical { background: #e2e2e2; color: #666; cursor: default; }
        .compare-cell.clickable, .compare-row-label.clickable, .compare-header.clickable { cursor: pointer; }
        .compare-table.changed-only .column-globally-unchanged { display: none; }
        .compare-row-label { position: sticky; left: 0; z-index: 2; white-space: nowrap; background: #eef1f4; }
        .compare-row-label.mixed { background: #f3e7c2; }
        .compare-header-label { font-weight: bold; margin-bottom: 4px; }
        .compare-header-actions { display: flex; flex-direction: column; gap: 4px; }
        .column-action-button { width: 100%; font-size: 11px; line-height: 1.2; padding: 3px 4px; white-space: normal; }
        .compare-value { white-space: normal; }
        .taxon-suggestion-box { margin-top: 6px; padding-top: 6px; border-top: 1px dashed #d3ab2f; font-size: 12px; }
        .taxon-suggestion { width: 100%; margin-top: 4px; }
        .result-ok { color: #1a5e20; }
        .result-error { color: #a40000; }
        @media (max-width: 860px) {
            .page-shell {
                padding: 18px 14px 28px;
            }
            .page-title {
                font-size: 24px;
            }
            .form-layout {
                grid-template-columns: 1fr;
                gap: 8px;
            }
            .form-label {
                padding-top: 0;
            }
        }
    </style>
</head>
<body>
<div class="page-shell">
<div class="page-header">
    <h1 class="page-title">Update Specimens</h1>
    <div class="page-subtitle"><?php echo ($run === 2) ? 'Review imported values against JACQ and choose what should be updated.' : (($run === 3) ? 'Update results and archive options.' : 'Upload a header-based .txt or .csv file, or provide a download URL, to start an update run.'); ?></div>
</div>

<?php if ($pageError !== '') { ?>
    <div class="error"><?php echo htmlspecialchars($pageError); ?></div>
<?php } ?>

<?php if (!empty($encodingConfirmation)) { ?>
    <div class="panel info-panel">
        <div class="panel-heading">File Encoding</div>
        <div class="notice form-notice">
            <p>The file <strong><?php echo htmlspecialchars($encodingConfirmation['filename']); ?></strong> is encoded as <strong><?php echo htmlspecialchars($encodingConfirmation['encoding']); ?></strong>, not UTF-8.</p>
            <p>Convert the temporary copy to UTF-8 before checking the update data, or cancel this upload.</p>
        </div>
        <div class="action-buttons" style="margin-top:18px;">
            <form action="<?php echo htmlspecialchars($_SERVER['SCRIPT_NAME']); ?>" method="POST">
                <input type="hidden" name="convert_update_input_encoding" value="1">
                <input type="submit" value="Convert to UTF-8 and continue" class="button-primary">
            </form>
            <form action="<?php echo htmlspecialchars($_SERVER['SCRIPT_NAME']); ?>" method="POST">
                <input type="submit" name="cancel_update_input_encoding" value="Cancel">
            </form>
        </div>
    </div>
<?php } elseif ($run === 1) { ?>
    <form enctype="multipart/form-data" action="<?php echo htmlspecialchars($_SERVER['SCRIPT_NAME']); ?>" method="POST" name="f">
        <?php renderUpdateInputForm($downloadUrl); ?>
    </form>
<?php } elseif ($run === 2) { ?>
    <div class="summary">
        <?php echo count($readyRows); ?> specimen_id matched row(s) are ready to be compared and updated.<br>
        <?php echo count($issueRows); ?> row(s) need manual correction before they can be updated.
    </div>
    <div class="action-buttons">
        <form action="<?php echo htmlspecialchars($_SERVER['SCRIPT_NAME']); ?>" method="POST">
            <input type="submit" name="reset_update_process" value="Start new update process">
        </form>
    </div>

    <?php if (!empty($readyRows)) {
        $payloadSeed = array();
        foreach ($readyRows as $row) {
            $payloadSeed[$row['rowKey']] = array(
                'specimen_ID' => $row['specimen_ID'],
                'import' => $row['importNormalized'],
                'db' => $row['databaseNormalized'],
                'selected' => $row['selectedNormalized'],
                'lockedImportFields' => $row['lockedImportFields'],
                'warningFieldStyles' => isset($row['warningFieldStyles']) ? $row['warningFieldStyles'] : array(),
                'differingFields' => $row['differingFields'],
                'taxonSuggestions' => $row['taxonSuggestions'],
                'suggestedTaxonID' => '',
            );
        }
    ?>
        <div class="panel">
            <div class="notice">Click a cell to switch between import and database values. Use the buttons in each header to select all file or all JACQ values for a column. Click the row label to select the full import or database row.</div>
        </div>
        <form action="<?php echo htmlspecialchars($_SERVER['SCRIPT_NAME']); ?>" method="POST" name="update_form">
            <input type="hidden" name="update_payload" id="update_payload" value="">
            <div class="panel">
                <div class="compare-toolbar">
                    <button type="button" id="toggle_columns_button">Show changed columns only</button>
                </div>
                <div class="compare-container">
                    <table class="compare-table" id="compare_table">
                        <thead>
                        <tr>
                            <th>Line / Record</th>
                            <?php foreach ($displayFields as $field) { $definition = getUpdateFieldDefinitions()[$field]; ?>
                                <th class="compare-header <?php echo !empty($changedColumns[$field]) ? '' : 'identical column-globally-unchanged'; ?>" data-field-key="<?php echo htmlspecialchars($field); ?>" data-selected-source="import">
                                    <div class="compare-header-label"><?php echo htmlspecialchars($definition['label']); ?></div>
                                    <?php if (!empty($changedColumns[$field])) { ?>
                                        <div class="compare-header-actions">
                                            <button type="button" class="column-action-button" data-field-key="<?php echo htmlspecialchars($field); ?>" data-source="db">select all JACQ entries</button>
                                            <button type="button" class="column-action-button" data-field-key="<?php echo htmlspecialchars($field); ?>" data-source="import">select all file entries</button>
                                        </div>
                                    <?php } ?>
                                </th>
                            <?php } ?>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($readyRows as $row) { ?>
                            <tr class="import-row">
                                <th class="compare-row-label selected clickable" data-row-key="<?php echo htmlspecialchars($row['rowKey']); ?>" data-source="import">
                                    Import line <?php echo intval($row['lineNumber']); ?><br>
                                    specimen_id <?php echo intval($row['fileSpecimenID']); ?>
                                    <?php if ($row['HerbNummer'] !== '') { ?><br><?php echo htmlspecialchars($row['HerbNummer']); ?><?php } ?>
                                    <?php if (!empty($row['collectionID'])) { ?> / collection <?php echo intval($row['collectionID']); ?><?php } ?>
                                    <?php if ($row['warningStatusText'] !== 'OK') { ?><br><span title="These imported fields were not resolved and therefore default to the database value."><?php echo htmlspecialchars($row['warningStatusText']); ?></span><?php } ?>
                                </th>
                                <?php foreach ($displayFields as $field) { $definition = getUpdateFieldDefinitions()[$field]; ?>
                                    <?php $fieldDiffers = in_array($field, $row['differingFields'], true); $fieldLocked = in_array($field, $row['lockedImportFields'], true); $fieldWarningClass = isset($row['warningFieldStyles'][$field]) ? $row['warningFieldStyles'][$field] : ''; ?>
                                    <td class="compare-cell <?php
                                        if (!$fieldDiffers) {
                                            echo 'identical';
                                        } else {
                                            if ($fieldLocked) {
                                                echo 'unselected locked-import warning-field ' . $fieldWarningClass;
                                            } else {
                                                echo 'selected clickable';
                                            }
                                        }
                                        if (empty($changedColumns[$field])) {
                                            echo ' column-globally-unchanged';
                                        }
                                    ?>" data-row-key="<?php echo htmlspecialchars($row['rowKey']); ?>" data-field-key="<?php echo htmlspecialchars($field); ?>" data-source="import"<?php if ($fieldLocked) { ?> title="Import value for this field could not be resolved and therefore stays on the database value by default."<?php } ?>>
                                        <div class="compare-value"><?php echo htmlspecialchars($row['importDisplay'][$field]); ?></div>
                                        <?php if ($field === 'taxonID' && !empty($row['taxonSuggestions'])) { ?>
                                            <div class="taxon-suggestion-box">
                                                <label>Suggestion:
                                                    <select class="taxon-suggestion" data-row-key="<?php echo htmlspecialchars($row['rowKey']); ?>" data-field-key="taxonID">
                                                        <option value="">Keep database value</option>
                                                        <?php foreach ($row['taxonSuggestions'] as $suggestion) { ?>
                                                            <option value="<?php echo intval($suggestion['taxonID']); ?>"><?php echo htmlspecialchars($suggestion['taxon']); ?></option>
                                                        <?php } ?>
                                                    </select>
                                                </label>
                                            </div>
                                        <?php } ?>
                                    </td>
                                <?php } ?>
                            </tr>
                            <tr class="db-row">
                                <th class="compare-row-label unselected clickable" data-row-key="<?php echo htmlspecialchars($row['rowKey']); ?>" data-source="db">
                                    Database<br>
                                    <a href="editSpecimens.php?sel=<?php echo htmlspecialchars('<' . $row['specimen_ID'] . '>'); ?>" target="Specimens">specimen <?php echo intval($row['specimen_ID']); ?></a>
                                </th>
                                <?php foreach ($displayFields as $field) { $definition = getUpdateFieldDefinitions()[$field]; ?>
                                    <?php $fieldDiffers = in_array($field, $row['differingFields'], true); $fieldLocked = in_array($field, $row['lockedImportFields'], true); $fieldWarningClass = isset($row['warningFieldStyles'][$field]) ? $row['warningFieldStyles'][$field] : ''; ?>
                                    <td class="compare-cell <?php
                                        if ($fieldDiffers) {
                                            echo $fieldLocked ? 'selected clickable warning-field ' . $fieldWarningClass : 'unselected clickable';
                                        } else {
                                            echo 'identical';
                                        }
                                        echo empty($changedColumns[$field]) ? ' column-globally-unchanged' : '';
                                    ?>" data-row-key="<?php echo htmlspecialchars($row['rowKey']); ?>" data-field-key="<?php echo htmlspecialchars($field); ?>" data-source="db"<?php if ($fieldLocked) { ?> title="Database value is preselected because the import value for this field could not be resolved."<?php } ?>><?php echo htmlspecialchars($row['databaseDisplay'][$field]); ?></td>
                                <?php } ?>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
                <div class="form-actions">
                    <input type="submit" name="update_data" value="update selected values" class="button-primary">
                </div>
            </div>
        </form>
        <script type="text/javascript">
            (function () {
                var comparisonData = <?php echo json_encode($payloadSeed, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
                var payloadInput = document.getElementById('update_payload');
                var compareTable = document.getElementById('compare_table');
                var toggleColumnsButton = document.getElementById('toggle_columns_button');
                var changedOnlyMode = false;

                function updatePayload() {
                    payloadInput.value = JSON.stringify(comparisonData);
                }

                function fieldDiffers(rowKey, fieldKey) {
                    if (!comparisonData[rowKey] || !comparisonData[rowKey].differingFields) {
                        return false;
                    }
                    return comparisonData[rowKey].differingFields.indexOf(fieldKey) !== -1;
                }

                function canUseSource(rowKey, fieldKey, source) {
                    if (!fieldDiffers(rowKey, fieldKey)) {
                        return false;
                    }
                    if (source !== 'import') {
                        return true;
                    }
                    return !isLockedImportField(rowKey, fieldKey);
                }

                function isLockedImportField(rowKey, fieldKey) {
                    if (fieldKey === 'taxonID' && comparisonData[rowKey] && comparisonData[rowKey].suggestedTaxonID !== '') {
                        return false;
                    }
                    return !!(comparisonData[rowKey] && comparisonData[rowKey].lockedImportFields && comparisonData[rowKey].lockedImportFields.indexOf(fieldKey) !== -1);
                }

                function getWarningFieldClass(rowKey, fieldKey) {
                    if (!comparisonData[rowKey] || !comparisonData[rowKey].warningFieldStyles) {
                        return '';
                    }
                    return comparisonData[rowKey].warningFieldStyles[fieldKey] || '';
                }

                function getSelectedSource(rowKey, fieldKey) {
                    if (!comparisonData[rowKey]) {
                        return 'import';
                    }
                    var selectedValue = comparisonData[rowKey].selected[fieldKey];
                    if (selectedValue === comparisonData[rowKey].db[fieldKey] && selectedValue !== comparisonData[rowKey].import[fieldKey]) {
                        return 'db';
                    }
                    return 'import';
                }

                function updateHeaderSelectionState(fieldKey) {
                    var header = document.querySelector('.compare-header[data-field-key="' + fieldKey + '"]');
                    if (!header || header.className.indexOf('identical') !== -1) {
                        return;
                    }
                    var hasDiffering = false;
                    var allImport = true;
                    var allDb = true;
                    for (var rowKey in comparisonData) {
                        if (!comparisonData.hasOwnProperty(rowKey) || !fieldDiffers(rowKey, fieldKey)) {
                            continue;
                        }
                        hasDiffering = true;
                        var selectedSource = getSelectedSource(rowKey, fieldKey);
                        if (selectedSource !== 'import') {
                            allImport = false;
                        }
                        if (selectedSource !== 'db') {
                            allDb = false;
                        }
                    }
                    if (!hasDiffering) {
                        header.setAttribute('data-selected-source', 'import');
                    } else if (allDb) {
                        header.setAttribute('data-selected-source', 'db');
                    } else if (allImport) {
                        header.setAttribute('data-selected-source', 'import');
                    } else {
                        header.setAttribute('data-selected-source', 'mixed');
                    }
                }

                function applySelectionClasses(rowKey, fieldKey) {
                    var selector = '.compare-cell[data-row-key="' + rowKey + '"][data-field-key="' + fieldKey + '"]';
                    var cells = document.querySelectorAll(selector);
                    var selectedSource = getSelectedSource(rowKey, fieldKey);
                    var lockedImport = isLockedImportField(rowKey, fieldKey);
                    var warningFieldClass = getWarningFieldClass(rowKey, fieldKey);
                    for (var i = 0; i < cells.length; i++) {
                        var cell = cells[i];
                        if (!fieldDiffers(rowKey, fieldKey)) {
                            cell.className = 'compare-cell identical';
                            if (document.querySelector('.compare-header[data-field-key="' + fieldKey + '"]').className.indexOf('column-globally-unchanged') !== -1) {
                                cell.className += ' column-globally-unchanged';
                            }
                            continue;
                        }
                        var cellSource = cell.getAttribute('data-source');
                        var selected = cellSource === selectedSource;
                        var className = 'compare-cell ' + (selected ? 'selected' : 'unselected');
                        if (!lockedImport || cellSource === 'db') {
                            className += ' clickable';
                        }
                        if (lockedImport && warningFieldClass !== '') {
                            className += ' warning-field ' + warningFieldClass;
                            if (cellSource === 'import') {
                                className += ' locked-import';
                            }
                        }
                        cell.className = className;
                    }
                    updateHeaderSelectionState(fieldKey);
                }

                function updateRowSelectionState(rowKey) {
                    var labels = document.querySelectorAll('.compare-row-label[data-row-key="' + rowKey + '"]');
                    var hasDiffering = false;
                    var allImport = true;
                    var allDb = true;
                    for (var fieldKey in comparisonData[rowKey].selected) {
                        if (!comparisonData[rowKey].selected.hasOwnProperty(fieldKey) || !fieldDiffers(rowKey, fieldKey)) {
                            continue;
                        }
                        hasDiffering = true;
                        var selectedSource = getSelectedSource(rowKey, fieldKey);
                        if (selectedSource !== 'import') {
                            allImport = false;
                        }
                        if (selectedSource !== 'db') {
                            allDb = false;
                        }
                    }
                    for (var i = 0; i < labels.length; i++) {
                        var label = labels[i];
                        var className = 'compare-row-label clickable';
                        if (!hasDiffering) {
                            className += (label.getAttribute('data-source') === 'import') ? ' selected' : ' unselected';
                        } else if (allImport || allDb) {
                            className += (label.getAttribute('data-source') === (allDb ? 'db' : 'import')) ? ' selected' : ' unselected';
                        } else {
                            className += ' mixed';
                        }
                        label.className = className;
                    }
                }

                function selectField(rowKey, fieldKey, source) {
                    if (!comparisonData[rowKey]) {
                        return;
                    }
                    if (!canUseSource(rowKey, fieldKey, source)) {
                        return;
                    }
                    comparisonData[rowKey].selected[fieldKey] = comparisonData[rowKey][source][fieldKey];
                    applySelectionClasses(rowKey, fieldKey);
                    updateRowSelectionState(rowKey);
                    updatePayload();
                }

                function applyTaxonSuggestion(rowKey, suggestionId) {
                    if (!comparisonData[rowKey]) {
                        return;
                    }
                    var parsedSuggestionId = suggestionId === '' ? '' : parseInt(suggestionId, 10);
                    if (parsedSuggestionId !== '' && isNaN(parsedSuggestionId)) {
                        parsedSuggestionId = '';
                    }
                    comparisonData[rowKey].suggestedTaxonID = parsedSuggestionId;
                    comparisonData[rowKey].import.taxonID = parsedSuggestionId;
                    if (parsedSuggestionId === '') {
                        comparisonData[rowKey].selected.taxonID = comparisonData[rowKey].db.taxonID;
                    } else {
                        comparisonData[rowKey].selected.taxonID = comparisonData[rowKey].import.taxonID;
                    }
                    applySelectionClasses(rowKey, 'taxonID');
                    updateRowSelectionState(rowKey);
                    updatePayload();
                }

                function selectRow(rowKey, source) {
                    if (!comparisonData[rowKey]) {
                        return;
                    }
                    for (var fieldKey in comparisonData[rowKey][source]) {
                        if (comparisonData[rowKey][source].hasOwnProperty(fieldKey) && canUseSource(rowKey, fieldKey, source)) {
                            comparisonData[rowKey].selected[fieldKey] = comparisonData[rowKey][source][fieldKey];
                            applySelectionClasses(rowKey, fieldKey);
                        }
                    }
                    updateRowSelectionState(rowKey);
                    updatePayload();
                }

                function selectColumn(fieldKey, source) {
                    var header = document.querySelector('.compare-header[data-field-key="' + fieldKey + '"]');
                    if (header.className.indexOf('identical') !== -1) {
                        return;
                    }
                    for (var rowKey in comparisonData) {
                        if (comparisonData.hasOwnProperty(rowKey)) {
                            selectField(rowKey, fieldKey, source);
                        }
                    }
                    updateHeaderSelectionState(fieldKey);
                }

                function updateColumnMode() {
                    var unchangedColumns = compareTable.querySelectorAll('.column-globally-unchanged');
                    if (changedOnlyMode) {
                        compareTable.classList.add('changed-only');
                        toggleColumnsButton.textContent = 'Show all columns';
                    } else {
                        compareTable.classList.remove('changed-only');
                        toggleColumnsButton.textContent = 'Show changed columns only';
                    }
                    for (var i = 0; i < unchangedColumns.length; i++) {
                        unchangedColumns[i].style.display = changedOnlyMode ? 'none' : '';
                    }
                }

                var cells = document.querySelectorAll('.compare-cell');
                for (var i = 0; i < cells.length; i++) {
                    cells[i].onclick = function () {
                        selectField(this.getAttribute('data-row-key'), this.getAttribute('data-field-key'), this.getAttribute('data-source'));
                    };
                }

                var rowLabels = document.querySelectorAll('.compare-row-label');
                for (var j = 0; j < rowLabels.length; j++) {
                    rowLabels[j].onclick = function (event) {
                        if (event.target && event.target.tagName && event.target.tagName.toLowerCase() === 'a') {
                            return;
                        }
                        selectRow(this.getAttribute('data-row-key'), this.getAttribute('data-source'));
                    };
                }

                var columnActionButtons = document.querySelectorAll('.column-action-button');
                for (var k = 0; k < columnActionButtons.length; k++) {
                    columnActionButtons[k].onclick = function (event) {
                        if (event) {
                            event.stopPropagation();
                        }
                        selectColumn(this.getAttribute('data-field-key'), this.getAttribute('data-source'));
                    };
                }

                var taxonSuggestionControls = document.querySelectorAll('.taxon-suggestion');
                for (var l = 0; l < taxonSuggestionControls.length; l++) {
                    taxonSuggestionControls[l].onclick = function (event) {
                        if (event) {
                            event.stopPropagation();
                        }
                    };
                    taxonSuggestionControls[l].onchange = function (event) {
                        if (event) {
                            event.stopPropagation();
                        }
                        applyTaxonSuggestion(this.getAttribute('data-row-key'), this.value);
                    };
                }

                toggleColumnsButton.onclick = function () {
                    changedOnlyMode = !changedOnlyMode;
                    updateColumnMode();
                };

                for (var rowKey in comparisonData) {
                    if (!comparisonData.hasOwnProperty(rowKey)) {
                        continue;
                    }
                    for (var fieldKey in comparisonData[rowKey].selected) {
                        if (comparisonData[rowKey].selected.hasOwnProperty(fieldKey)) {
                            applySelectionClasses(rowKey, fieldKey);
                        }
                    }
                    updateRowSelectionState(rowKey);
                }
                updatePayload();
                updateColumnMode();
            })();
        </script>
    <?php } ?>

    <?php if (!empty($issueRows)) { ?>
        <div class="panel table-panel">
            <h2>Rows With Issues</h2>
            <table>
                <tr>
                    <th>Line</th>
                    <th>File specimen_id</th>
                    <th>HerbNummer</th>
                    <th>Collection</th>
                    <th>Specimen</th>
                    <th>Status</th>
                </tr>
                <?php foreach ($issueRows as $row) { ?>
                    <tr>
                        <td><?php echo intval($row['lineNumber']); ?></td>
                        <td><?php echo !empty($row['fileSpecimenID']) ? intval($row['fileSpecimenID']) : ''; ?></td>
                        <td><?php echo htmlspecialchars($row['HerbNummer']); ?></td>
                        <td><?php echo intval($row['collectionID']); ?></td>
                        <td><?php echo !empty($row['specimen_ID']) ? intval($row['specimen_ID']) : ''; ?></td>
                        <td><?php echo htmlspecialchars($row['statusText']); ?></td>
                    </tr>
                <?php } ?>
            </table>
            <div class="notice" style="margin-top:12px;">Rows with invalid reference data or without a valid specimen_id match are not updated.</div>
        </div>
    <?php } ?>

    <?php if ($pageError !== '' && empty($readyRows) && empty($issueRows)) { ?>
        <hr>
        <form enctype="multipart/form-data" action="<?php echo htmlspecialchars($_SERVER['SCRIPT_NAME']); ?>" method="POST" name="f">
            <?php renderUpdateInputForm($downloadUrl, $inputHeaderMode); ?>
        </form>
    <?php } ?>
<?php } elseif ($run === 3) { ?>
    <?php
        $successCount = 0;
        foreach ($updateResults as $result) {
            if ($result['success']) {
                $successCount++;
            }
        }
        $errorCount = count($updateResults) - $successCount;
    ?>
    <div class="summary"><?php echo count($updateResults); ?> row(s) processed. <?php echo $successCount; ?> succeeded, <?php echo $errorCount; ?> failed.</div>
    <div class="action-buttons">
        <form action="<?php echo htmlspecialchars($_SERVER['SCRIPT_NAME']); ?>" method="POST">
            <input type="submit" name="archive_update_process" value="Archive">
        </form>
        <form action="<?php echo htmlspecialchars($_SERVER['SCRIPT_NAME']); ?>" method="POST">
            <input type="submit" name="reset_update_process" value="Start new update process">
        </form>
    </div>
    <div class="panel table-panel">
        <table>
            <tr>
                <th>Row</th>
                <th>Specimen</th>
                <th>Result</th>
                <th>Changed Fields</th>
            </tr>
            <?php foreach ($updateResults as $result) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($result['rowKey']); ?></td>
                    <td><?php echo intval($result['specimen_ID']); ?></td>
                    <td class="<?php echo $result['success'] ? 'result-ok' : 'result-error'; ?>"><?php echo htmlspecialchars($result['message']); ?></td>
                    <td><?php echo htmlspecialchars(implode(', ', $result['changedFields'])); ?></td>
                </tr>
            <?php } ?>
        </table>
    </div>
<?php } ?>
</div>
</body>
</html>
