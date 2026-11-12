<?php

const INCERTAE_SEDIS_IMPORT = 3449;
const IMPORT_SPECIMENS_MAX_FILE_SIZE = 8000000;
const IMPORT_SPECIMENS_PENDING_INPUT_SESSION_KEY = 'import_specimens_pending_input';
const IMPORT_SPECIMENS_PENDING_HERB_SESSION_KEY = 'import_specimens_pending_herb_confirmation';
const IMPORT_SPECIMENS_PROCESS_SESSION_KEY = 'import_specimens_process';

session_start();
set_time_limit(0);
require("../inc/connect.php");
require_once __DIR__ . '/../vendor/autoload.php';

use Jacq\Autocomplete;
use Jacq\JsonRPCClient_v1;
use Jacq\Services\Log;
use Jacq\TaxonTokenizer;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;


/**
 * parses a line of a textfile and returns an array or false
 *
 * @param resource $handle
 * @param int[optional] $minNumOfParts minimum number of required columns (default: 2)
 * @param string[optional] $delimiter sets the field delimiter (default: ;)
 * @param string[optional] $enclosure sets the field enclosure character (default: ")
 * @return array|bool array of elements or "false" if too short
 */
function normalizeImportText($value)
{
    $value = (string)$value;
    if ($value === '' || mb_check_encoding($value, 'UTF-8')) {
        return $value;
    }

    $sourceEncoding = mb_detect_encoding($value, array('Windows-1252', 'ISO-8859-1'), true);
    return mb_convert_encoding($value, 'UTF-8', $sourceEncoding ?: 'Windows-1252');
}

function parseLine($handle, $minNumOfParts=2, $delimiter=';', $enclosure='"')
{
    $parts = fgetcsv($handle, 4096, $delimiter, $enclosure, '\\');
    if ($parts !== false) {
        $parts = array_map('normalizeImportText', $parts);
    }
    if (!empty($parts) && count($parts) >= $minNumOfParts) {
        return $parts;
    } else {
        return false;
    }
}

function detectImportEncoding($inputPath)
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

function convertImportFileToUtf8($sourcePath, $sourceEncoding, &$targetPath, &$error = '')
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

    $targetPath = tempnam(sys_get_temp_dir(), 'imp_utf8_');
    if ($targetPath === false || @file_put_contents($targetPath, mb_convert_encoding($content, 'UTF-8', $sourceEncoding)) === false) {
        if ($targetPath !== false) {
            @unlink($targetPath);
        }
        $error = 'Could not create the UTF-8 converted file.';
        return false;
    }

    return true;
}

function clearPendingImportInput()
{
    if (!empty($_SESSION[IMPORT_SPECIMENS_PENDING_INPUT_SESSION_KEY]['path'])) {
        $path = $_SESSION[IMPORT_SPECIMENS_PENDING_INPUT_SESSION_KEY]['path'];
        if (is_file($path)) {
            @unlink($path);
        }
    }
    unset($_SESSION[IMPORT_SPECIMENS_PENDING_INPUT_SESSION_KEY]);
}

function storePendingImportInput($sourcePath, $inputName, $encoding, $checkGeographicAssignments, &$error = '')
{
    $pendingPath = tempnam(sys_get_temp_dir(), 'imp_pending_');
    if ($pendingPath === false || !@copy($sourcePath, $pendingPath)) {
        if ($pendingPath !== false && is_file($pendingPath)) {
            @unlink($pendingPath);
        }
        $error = 'Could not retain the input file for UTF-8 conversion.';
        return false;
    }

    clearPendingImportInput();
    $_SESSION[IMPORT_SPECIMENS_PENDING_INPUT_SESSION_KEY] = array(
        'path' => $pendingPath,
        'name' => (string)$inputName,
        'encoding' => (string)$encoding,
        'check_geographic_assignments' => !empty($checkGeographicAssignments),
        'created_at' => time(),
    );

    return true;
}

function clearPendingImportHerbConfirmation()
{
    $pending = $_SESSION[IMPORT_SPECIMENS_PENDING_HERB_SESSION_KEY] ?? array();
    if (!empty($pending['path']) && is_file($pending['path'])) {
        @unlink($pending['path']);
    }
    unset($_SESSION[IMPORT_SPECIMENS_PENDING_HERB_SESSION_KEY]);
}

function storePendingImportHerbConfirmation($sourcePath, $inputName, $checkGeographicAssignments, &$error)
{
    $path = tempnam(sys_get_temp_dir(), 'imp_herb_');
    if ($path === false || !@copy($sourcePath, $path)) {
        if ($path !== false) @unlink($path);
        $error = 'Could not retain the file for herbarium number confirmation.';
        return false;
    }
    try {
        $token = bin2hex(random_bytes(16));
    } catch (Exception $e) {
        @unlink($path);
        $error = 'Could not start herbarium number confirmation.';
        return false;
    }
    clearPendingImportHerbConfirmation();
    $_SESSION[IMPORT_SPECIMENS_PENDING_HERB_SESSION_KEY] = array(
        'path' => $path,
        'name' => (string)$inputName,
        'geo_check' => (bool)$checkGeographicAssignments,
        'token' => $token,
    );
    return true;
}

function getImportHeaderAliases()
{
    return array(
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
}

function normalizeImportHeaderName($header)
{
    $header = trim((string)$header);
    if ($header === '') {
        return '';
    }

    $header = mb_strtolower($header, 'UTF-8');
    return preg_replace('/[^\p{L}\p{N}]+/u', '', $header);
}

function mapImportHeaderColumns(array $headerRow, &$error = '')
{
    $aliases = getImportHeaderAliases();
    $columnMap = array();
    $reservedHeaders = array('checked', 'accessible');

    foreach ($headerRow as $index => $headerText) {
        $normalizedHeader = normalizeImportHeaderName($headerText);
        if ($normalizedHeader === '') {
            $error = 'The header line contains an empty column name.';
            return array();
        }
        if (in_array($normalizedHeader, $reservedHeaders, true)) {
            $error = 'The header "' . trim((string)$headerText) . '" is not allowed. checked and accessible are set only after review in the specimen import area.';
            return array();
        }
        if (!isset($aliases[$normalizedHeader])) {
            $error = 'Unknown import header "' . trim((string)$headerText) . '".';
            return array();
        }

        $sourceKey = $aliases[$normalizedHeader];
        if (isset($columnMap[$sourceKey])) {
            $error = 'Duplicate header mapping for "' . trim((string)$headerText) . '".';
            return array();
        }
        $columnMap[$sourceKey] = $index;
    }

    $requiredColumns = array(
        'HerbNummer' => 'HerbNummer',
        'collectionID' => 'collectionID',
        'taxonID' => 'taxon',
        'collector' => 'Sammler',
    );
    foreach ($requiredColumns as $requiredColumn => $headerLabel) {
        if (!isset($columnMap[$requiredColumn])) {
            $error = 'The header line must contain ' . $headerLabel . '.';
            return array();
        }
    }

    return $columnMap;
}

function loadHeaderBasedImportRows($inputPath, &$error = '')
{
    $handle = @fopen($inputPath, 'r');
    if (!$handle) {
        $error = 'The import file could not be opened.';
        return array();
    }

    $headerRow = fgetcsv($handle, 4096, ';', '"', '\\');
    if ($headerRow === false) {
        fclose($handle);
        $error = 'The import file does not contain a readable header line.';
        return array();
    }
    $headerRow = array_map('normalizeImportText', $headerRow);
    $expectedColumnCount = count($headerRow);
    $columnMap = mapImportHeaderColumns($headerRow, $error);
    if ($columnMap === array()) {
        fclose($handle);
        return array();
    }

    $legacyColumns = array(
        'HerbNummer', 'collectionID', 'CollNummer', 'identstatusID', 'taxonID', 'collector', 'seriesID', 'series_number', 'Nummer', 'alt_number',
        'Datum', 'Datum2', 'det', 'typified', 'typusID', 'taxon_alt', 'NationID', 'provinceID', 'Fundort', 'Fundort_engl',
        'habitat', 'habitus', 'Bemerkungen', 'lat_cardinal', 'lat_deg', 'lat_min', 'lat_sec', 'lon_cardinal', 'lon_deg', 'lon_min',
        'lon_sec', 'quadrant', 'quadrant_sub', 'exactness', 'altitude_min', 'altitude_max', 'digital_image', 'digital_image_obs', 'observation', 'notes_internal',
    );

    $rows = array();
    $lineNumber = 2;
    while (($parts = fgetcsv($handle, 4096, ';', '"', '\\')) !== false) {
        $currentLineNumber = $lineNumber++;
        $parts = array_map('normalizeImportText', $parts);
        if (count($parts) === 1 && trim((string)$parts[0]) === '') {
            continue;
        }
        if (count($parts) !== $expectedColumnCount) {
            fclose($handle);
            $error = 'Header/data column count mismatch on line ' . $currentLineNumber . ': header has ' . $expectedColumnCount . ' column(s), row has ' . count($parts) . '. Processing was aborted.';
            return array();
        }

        $row = array_fill(0, count($legacyColumns), '');
        foreach ($legacyColumns as $legacyIndex => $sourceKey) {
            if (isset($columnMap[$sourceKey])) {
                $row[$legacyIndex] = trim((string)$parts[$columnMap[$sourceKey]]);
            }
        }
        $row['linenumber'] = $currentLineNumber;
        $rows[] = $row;
    }
    fclose($handle);

    return $rows;
}

function getImportHerbNumber($value)
{
    $pieces = explode('_', (string)$value);
    $number = count($pieces) > 1 ? trim($pieces[1]) : trim((string)$value);
    return preg_match('/^\s*$/u', $number) ? '' : $number;
}

/** Validate herbarium numbers, reject conflicts, and retain the first identical row. */
function checkImportFileDuplicates(array $rows, &$duplicates, &$error, &$allHerbNumbersMissing = null)
{
    $duplicates = array();
    $seen = array();
    $uniqueRows = array();
    $conflicts = array();
    $missingNumbers = array();
    $allHerbNumbersMissing = false;
    foreach ($rows as $row) {
        $herbNumber = getImportHerbNumber($row[0]);
        if ($herbNumber === '') {
            $missingNumbers[] = $row['linenumber'];
            $uniqueRows[] = $row;
            continue;
        }
        $key = mb_strtolower($herbNumber, 'UTF-8');
        if (!isset($seen[$key])) {
            $seen[$key] = $row;
            $uniqueRows[] = $row;
            continue;
        }
        $first = $seen[$key];
        $firstValues = $first;
        $rowValues = $row;
        unset($firstValues['linenumber'], $rowValues['linenumber']);
        if ($firstValues === $rowValues) {
            $duplicates[] = array('herbNumber' => $row[0], 'line' => $row['linenumber'], 'keptLine' => $first['linenumber']);
        } else {
            $conflicts[] = 'HerbNummer "' . $herbNumber . '" (lines ' . $first['linenumber'] . ' and ' . $row['linenumber'] . ')';
        }
    }
    $allHerbNumbersMissing = count($rows) > 0 && count($missingNumbers) === count($rows);
    if (($missingNumbers && !$allHerbNumbersMissing) || $conflicts) {
        $messages = array();
        if ($missingNumbers && !$allHerbNumbersMissing) {
            $messages[] = 'Some rows have a HerbNummer, but it is missing on line(s): ' . implode(', ', $missingNumbers) . '.';
        }
        if ($conflicts) {
            $messages[] = 'The same HerbNummer has different data in the file: ' . implode('; ', $conflicts) . '.';
        }
        $error = 'Import stopped: ' . implode(' ', $messages) . ' Correct the file and upload it again.';
        return array();
    }
    return $uniqueRows;
}

function getImportDecimalCoordinates(array $rowData)
{
    $latitude = null;
    $longitude = null;

    if ($rowData['Coord_N'] !== '') {
        $latitude = array($rowData['Coord_N'], $rowData['N_Min'], $rowData['N_Sec'], 1);
    } elseif ($rowData['Coord_S'] !== '') {
        $latitude = array($rowData['Coord_S'], $rowData['S_Min'], $rowData['S_Sec'], -1);
    }
    if ($rowData['Coord_E'] !== '') {
        $longitude = array($rowData['Coord_E'], $rowData['E_Min'], $rowData['E_Sec'], 1);
    } elseif ($rowData['Coord_W'] !== '') {
        $longitude = array($rowData['Coord_W'], $rowData['W_Min'], $rowData['W_Sec'], -1);
    }
    if ($latitude === null || $longitude === null) {
        return null;
    }

    foreach (array($latitude, $longitude) as $coordinate) {
        if (!is_numeric($coordinate[0])
            || ($coordinate[1] !== '' && !is_numeric($coordinate[1]))
            || ($coordinate[2] !== '' && !is_numeric($coordinate[2]))) {
            return null;
        }
    }

    $lat = $latitude[3] * (abs(floatval($latitude[0])) + abs(floatval($latitude[1])) / 60 + abs(floatval($latitude[2])) / 3600);
    $lon = $longitude[3] * (abs(floatval($longitude[0])) + abs(floatval($longitude[1])) / 60 + abs(floatval($longitude[2])) / 3600);
    if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
        return null;
    }

    return array('lat' => round($lat, 6), 'lon' => round($lon, 6));
}

function formatImportCoordinatesForValidation(array $rowData)
{
    $coordinates = getImportDecimalCoordinates($rowData);
    if ($coordinates === null) {
        return '';
    }

    return number_format($coordinates['lat'], 6, '.', '') . ', ' . number_format($coordinates['lon'], 6, '.', '');
}

function checkImportGeographicAssignment($nationId, $provinceId, array $rowData)
{
    static $cache = array();
    static $serviceUnavailable = false;

    $coordinates = getImportDecimalCoordinates($rowData);
    if ($coordinates === null || intval($nationId) <= 0) {
        return array();
    }

    $cacheKey = intval($nationId) . '|' . intval($provinceId) . '|' . $coordinates['lat'] . '|' . $coordinates['lon'];
    if (isset($cache[$cacheKey])) {
        return $cache[$cacheKey];
    }

    if ($serviceUnavailable) {
        $cache[$cacheKey] = array('coordinate_check_unavailable');
        return $cache[$cacheKey];
    }

    if (!function_exists('curl_init')) {
        $serviceUnavailable = true;
        $cache[$cacheKey] = array('coordinate_check_unavailable');
        return $cache[$cacheKey];
    }

    $query = http_build_query(array(
        'nationID' => intval($nationId),
        'provinceID' => intval($provinceId),
        'lat' => $coordinates['lat'],
        'lon' => $coordinates['lon'],
    ));
    $curl = curl_init('https://services.jacq.org/jacq-services/rest/geo/checkBoundaries?' . $query);
    if ($curl === false) {
        $cache[$cacheKey] = array('coordinate_check_unavailable');
        return $cache[$cacheKey];
    }

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($curl, CURLOPT_TIMEOUT, 4);
    $response = curl_exec($curl);
    $httpCode = intval(curl_getinfo($curl, CURLINFO_HTTP_CODE));
    curl_close($curl);
    if ($response === false || $httpCode < 200 || $httpCode >= 300) {
        $serviceUnavailable = true;
        $cache[$cacheKey] = array('coordinate_check_unavailable');
        return $cache[$cacheKey];
    }

    $result = json_decode($response, true);
    if (!is_array($result) || !empty($result['error'])) {
        $serviceUnavailable = true;
        $cache[$cacheKey] = array('coordinate_check_unavailable');
        return $cache[$cacheKey];
    }

    $warnings = array();
    if (isset($result['nation']['nrBoundaries']) && intval($result['nation']['nrBoundaries']) > 0 && empty($result['nation']['inside'])) {
        $warnings[] = 'coordinates_outside_nation';
    }
    if (intval($provinceId) > 0 && isset($result['province']['nrBoundaries']) && intval($result['province']['nrBoundaries']) > 0 && empty($result['province']['inside'])) {
        $warnings[] = 'coordinates_outside_province';
    }

    $cache[$cacheKey] = $warnings;
    return $cache[$cacheKey];
}

function formatImportGeographicWarning($warning)
{
    $messages = array(
        'coordinates_outside_nation' => 'Coordinates appear to be outside the selected country.',
        'coordinates_outside_province' => 'Coordinates appear to be outside the selected province.',
        'coordinate_check_unavailable' => 'Geographic assignment could not be checked because the boundary service is unavailable.',
    );

    return isset($messages[$warning]) ? $messages[$warning] : $warning;
}

function importStatusHasFlag($status, $flag)
{
    return preg_match('/(?:^|\s)' . preg_quote($flag, '/') . '(?:\s|$)/', (string)$status) === 1;
}

function importStatusHasCollectorIssue($status)
{
    return importStatusHasFlag($status, 'no_collector')
        || importStatusHasFlag($status, 'no_collector_primary')
        || importStatusHasFlag($status, 'no_collector_additional');
}

function parseImportCollectors($value)
{
    $value = trim((string)$value);

    if (substr($value, -6) == 'et al.') {
        return array(
            'collector' => trim(substr($value, 0, -7)),
            'collector2' => 'et al.',
        );
    }

    $collectors = trim(strtr($value, '&', ','));
    $parts = explode(', ', $collectors);
    $collector = trim($parts[0]);
    $collector2 = trim(substr($value, strlen($collector) + 2));

    return array(
        'collector' => $collector,
        'collector2' => $collector2,
    );
}

function renderCollectorValidationDisplay($rowData, $rawValue)
{
    if (empty($rowData['collector_validation_detail'])) {
        return htmlspecialchars((string)$rawValue);
    }

    $parts = array();
    $primaryStyle = !empty($rowData['collector_primary_found'])
        ? 'display:inline-block;background-color:#00FF00;padding:1px 4px;margin:1px 4px 1px 0;color:#000;'
        : 'display:inline-block;background-color:#ffb3b3;padding:1px 4px;margin:1px 4px 1px 0;color:#000;';
    $parts[] = '<span style="' . $primaryStyle . '">Collector: ' . htmlspecialchars((string)$rowData['collector_primary_raw']) . '</span>';

    if (!empty($rowData['collector_additional_checked']) || !empty($rowData['collector_additional_raw'])) {
        $additionalStyle = !empty($rowData['collector_additional_found'])
            ? 'display:inline-block;background-color:#00FF00;padding:1px 4px;margin:1px 4px 1px 0;color:#000;'
            : 'display:inline-block;background-color:#ffb3b3;padding:1px 4px;margin:1px 4px 1px 0;color:#000;';
        $parts[] = '<span style="' . $additionalStyle . '">Collector2: ' . htmlspecialchars((string)$rowData['collector_additional_raw']) . '</span>';
    }

    $html = '<div style="line-height:1.6">' . implode('<br>', $parts) . '</div>';

    return $html;
}

function addImportExportUniqueValue(&$values, $value)
{
    $value = trim((string)$value);
    if ($value !== '') {
        $values[$value] = $value;
    }
}

function normalizeImportExportList($values)
{
    if (!is_array($values)) {
        return array();
    }

    natcasesort($values);

    return array_values($values);
}

function buildImportValidationExportPayload($import, $status, $taxamatch, $data, $inputName, $geoWarnings = array())
{
    $errorColumns = array(
        'no_taxa' => 4,
        'no_collector_primary' => null,
        'no_collector_additional' => null,
        'no_series' => 6,
        'no_type' => 14,
        'no_nation' => 16,
        'no_province' => 17,
    );

    $payload = array(
        'inputName' => (string)$inputName,
        'errors' => array_fill_keys(array_keys($errorColumns), array()),
        'geoWarnings' => array_fill_keys(array('coordinates_outside_nation', 'coordinates_outside_province', 'coordinate_check_unavailable'), array()),
        'similar' => array(),
        'exists' => array(),
    );

    for ($i = 0; $i < count($import); $i++) {
        foreach ($errorColumns as $flag => $column) {
            if ($flag === 'no_collector_primary') {
                if (empty($data[$i]['collector_primary_found'])) {
                    addImportExportUniqueValue($payload['errors'][$flag], isset($data[$i]['collector_primary_raw']) ? $data[$i]['collector_primary_raw'] : '');
                }
                continue;
            }
            if ($flag === 'no_collector_additional') {
                if (!empty($data[$i]['collector_additional_checked']) && empty($data[$i]['collector_additional_found'])) {
                    addImportExportUniqueValue($payload['errors'][$flag], isset($data[$i]['collector_additional_raw']) ? $data[$i]['collector_additional_raw'] : '');
                }
                continue;
            }
            if (importStatusHasFlag($status[$i], $flag)) {
                if ($flag === 'no_taxa' && importStatusHasFlag($status[$i], 'similar_taxa')) {
                    continue;
                }
                if ($flag === 'no_province') {
                    $provinceValue = isset($import[$i][$column]) ? trim((string)$import[$i][$column]) : '';
                    $nationValue = isset($import[$i][16]) ? trim((string)$import[$i][16]) : '';
                    if ($provinceValue !== '' && $nationValue !== '') {
                        addImportExportUniqueValue($payload['errors'][$flag], $provinceValue . ' (' . $nationValue . ')');
                    } else {
                        addImportExportUniqueValue($payload['errors'][$flag], $provinceValue);
                    }
                    continue;
                }
                addImportExportUniqueValue($payload['errors'][$flag], isset($import[$i][$column]) ? $import[$i][$column] : '');
            }
        }

        if (importStatusHasFlag($status[$i], 'no_taxa') && importStatusHasFlag($status[$i], 'similar_taxa')) {
            $searchedTaxon = trim((string)$import[$i][4]);
            if ($searchedTaxon !== '') {
                if (!isset($payload['similar'][$searchedTaxon])) {
                    $payload['similar'][$searchedTaxon] = array();
                }
                if (!empty($taxamatch[$i]) && is_array($taxamatch[$i])) {
                    foreach ($taxamatch[$i] as $match) {
                        if (!empty($match['taxon'])) {
                            addImportExportUniqueValue($payload['similar'][$searchedTaxon], $match['taxon']);
                        }
                    }
                }
            }
        }

        if (importStatusHasFlag($status[$i], 'exists')) {
            addImportExportUniqueValue($payload['exists'], isset($data[$i]['HerbNummer']) ? $data[$i]['HerbNummer'] : '');
        }

        if (!empty($geoWarnings[$i]) && is_array($geoWarnings[$i])) {
            $coordinateValue = formatImportCoordinatesForValidation($data[$i]);
            foreach ($geoWarnings[$i] as $warning) {
                if (isset($payload['geoWarnings'][$warning])) {
                    addImportExportUniqueValue($payload['geoWarnings'][$warning], $coordinateValue);
                }
            }
        }
    }

    foreach ($payload['errors'] as $flag => $values) {
        $payload['errors'][$flag] = normalizeImportExportList($values);
    }
    foreach ($payload['geoWarnings'] as $flag => $values) {
        $payload['geoWarnings'][$flag] = normalizeImportExportList($values);
    }

    if (!empty($payload['similar'])) {
        uksort($payload['similar'], 'strnatcasecmp');
        foreach ($payload['similar'] as $searchedTaxon => $suggestions) {
            $payload['similar'][$searchedTaxon] = normalizeImportExportList($suggestions);
        }
    }

    $payload['exists'] = normalizeImportExportList($payload['exists']);

    return $payload;
}

function storeImportValidationExportPayload($payload)
{
    if (!isset($_SESSION['importSpecimensValidationExports']) || !is_array($_SESSION['importSpecimensValidationExports'])) {
        $_SESSION['importSpecimensValidationExports'] = array();
    }

    foreach ($_SESSION['importSpecimensValidationExports'] as $token => $entry) {
        if (empty($entry['created_at']) || $entry['created_at'] < time() - 3600) {
            unset($_SESSION['importSpecimensValidationExports'][$token]);
        }
    }

    try {
        $token = bin2hex(random_bytes(16));
    } catch (Exception $e) {
        $token = md5(uniqid('', true));
    }

    $_SESSION['importSpecimensValidationExports'][$token] = array(
        'created_at' => time(),
        'payload' => $payload,
    );

    return $token;
}

function loadImportValidationExportPayload($token)
{
    if (!isset($_SESSION['importSpecimensValidationExports'][$token]['payload'])) {
        return null;
    }

    return $_SESSION['importSpecimensValidationExports'][$token]['payload'];
}

function clearImportProcessContext()
{
    $context = $_SESSION[IMPORT_SPECIMENS_PROCESS_SESSION_KEY] ?? array();
    if (!empty($context['input_file_path']) && is_file($context['input_file_path'])) {
        @unlink($context['input_file_path']);
    }
    unset($_SESSION[IMPORT_SPECIMENS_PROCESS_SESSION_KEY]);
}

function storeImportProcessInputFile($sourcePath, $inputName, &$error)
{
    $path = tempnam(sys_get_temp_dir(), 'imp_proc_');
    if ($path === false || !@copy($sourcePath, $path)) {
        if ($path !== false) {
            @unlink($path);
        }
        $error = 'Could not retain the input file for the import archive.';
        return false;
    }
    try {
        $token = bin2hex(random_bytes(16));
    } catch (Exception $e) {
        @unlink($path);
        $error = 'Could not start the import process.';
        return false;
    }
    $parsedPath = parse_url((string)$inputName, PHP_URL_PATH);
    $filename = basename(is_string($parsedPath) && $parsedPath !== '' ? $parsedPath : (string)$inputName);
    $filename = trim((string)preg_replace('/[^A-Za-z0-9._-]+/', '_', $filename), '._');
    clearImportProcessContext();
    $_SESSION[IMPORT_SPECIMENS_PROCESS_SESSION_KEY] = array(
        'token' => $token,
        'input_file_path' => $path,
        'input_file_name' => $filename !== '' ? $filename : 'import_input.txt',
    );
    return true;
}

function isCurrentImportProcessToken($token)
{
    $context = $_SESSION[IMPORT_SPECIMENS_PROCESS_SESSION_KEY] ?? array();
    return is_string($token) && $token !== '' && !empty($context['token']) && hash_equals($context['token'], $token);
}

function buildImportResultsReport(array $outcomes, array $labels, array $context)
{
    $lines = array('Import Specimens Report', 'Generated: ' . date('Y-m-d H:i:s'));
    if (!empty($context['input_file_name'])) {
        $lines[] = 'Input file: ' . $context['input_file_name'];
    }
    if (!empty($context['without_herb_numbers'])) {
        $lines[] = 'Import without herbarium numbers: confirmed';
    }
    $lines[] = 'File rows: ' . array_sum(array_map('count', $outcomes));
    foreach ($labels as $key => $label) {
        $lines[] = $label . ': ' . count($outcomes[$key]);
    }
    $lines[] = '';
    $lines[] = "Line\tHerbNummer\tResult\tReason";
    foreach ($labels as $key => $label) {
        foreach ($outcomes[$key] as $item) {
            $lines[] = implode("\t", array_map(function ($value) {
                return str_replace(array("\r", "\n", "\t"), ' ', (string)$value);
            }, array($item['line'], $item['herbNumber'], $label, $item['reason'])));
        }
    }
    return implode("\n", $lines) . "\n";
}

function createImportProcessArchive(&$zipPath, &$error)
{
    $context = $_SESSION[IMPORT_SPECIMENS_PROCESS_SESSION_KEY] ?? array();
    if (empty($context['input_file_path']) || !is_file($context['input_file_path']) || empty($context['report_content'])) {
        $error = 'The input file or completed import report is no longer available.';
        return false;
    }
    if (!class_exists('ZipArchive')) {
        $error = 'ZipArchive is not available on this PHP installation.';
        return false;
    }
    $zipPath = tempnam(sys_get_temp_dir(), 'imp_zip_');
    if ($zipPath === false) {
        $error = 'Could not create a temporary ZIP file.';
        return false;
    }
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::OVERWRITE) !== true) {
        @unlink($zipPath);
        $error = 'Could not create the import archive.';
        return false;
    }
    $reportName = $context['input_file_name'] === 'import_report.txt' ? 'import_results.txt' : 'import_report.txt';
    $added = $zip->addFile($context['input_file_path'], $context['input_file_name'])
        && $zip->addFromString($reportName, $context['report_content']);
    $closed = $zip->close();
    if (!$added || !$closed) {
        @unlink($zipPath);
        $error = 'Could not write the import archive.';
        return false;
    }
    return true;
}

if (isset($_POST['archive_import_process'])) {
    $archiveError = '';
    $archivePath = '';
    if (!isCurrentImportProcessToken($_POST['import_process_token'] ?? '') || !createImportProcessArchive($archivePath, $archiveError)) {
        http_response_code(400);
        header('Content-Type: text/plain; charset=UTF-8');
        echo $archiveError !== '' ? $archiveError : 'The import report is no longer available. Start a new import.';
        exit;
    }
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="specimen_import_' . date('Ymd_His') . '.zip"');
    header('Content-Length: ' . filesize($archivePath));
    header('Cache-Control: private, must-revalidate');
    readfile($archivePath);
    @unlink($archivePath);
    exit;
}

function styleImportExportHeader($sheet, $cellRange, $rgb)
{
    $sheet->getStyle($cellRange)->getFont()->setBold(true)->getColor()->setARGB(Color::COLOR_WHITE);
    $sheet->getStyle($cellRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($rgb);
}

function autosizeImportExportColumns($sheet, $highestColumn)
{
    $maxIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
    for ($columnIndex = 1; $columnIndex <= $maxIndex; $columnIndex++) {
        $sheet->getColumnDimensionByColumn($columnIndex)->setAutoSize(true);
    }
}

function streamImportValidationExportWorkbook($payload)
{
    $spreadsheet = new Spreadsheet();

    $errorSheet = $spreadsheet->getActiveSheet();
    $errorSheet->setTitle('errors');
    $errorHeaders = array(
        'A1' => 'no_taxa',
        'B1' => 'no_collector_primary',
        'C1' => 'no_collector_additional',
        'D1' => 'no_series',
        'E1' => 'no_type',
        'F1' => 'no_nation',
        'G1' => 'no_province',
    );
    foreach ($errorHeaders as $cell => $label) {
        $errorSheet->setCellValue($cell, $label);
    }
    styleImportExportHeader($errorSheet, 'A1:G1', 'FFFF0000');
    $errorColumns = array(
        'A' => 'no_taxa',
        'B' => 'no_collector_primary',
        'C' => 'no_collector_additional',
        'D' => 'no_series',
        'E' => 'no_type',
        'F' => 'no_nation',
        'G' => 'no_province',
    );
    foreach ($errorColumns as $column => $flag) {
        $row = 2;
        if (!empty($payload['errors'][$flag])) {
            foreach ($payload['errors'][$flag] as $value) {
                $errorSheet->setCellValue($column . $row, $value);
                $row++;
            }
        }
    }
    $errorSheet->freezePane('A2');
    $errorSheet->getTabColor()->setRGB('FF0000');
    autosizeImportExportColumns($errorSheet, 'G');

    $geoWarningSheet = $spreadsheet->createSheet();
    $geoWarningSheet->setTitle('geographic warnings');
    $geoWarningHeaders = array(
        'A1' => 'coordinates_outside_nation',
        'B1' => 'coordinates_outside_province',
        'C1' => 'coordinate_check_unavailable',
    );
    foreach ($geoWarningHeaders as $cell => $label) {
        $geoWarningSheet->setCellValue($cell, $label);
    }
    styleImportExportHeader($geoWarningSheet, 'A1:C1', 'FFF1C232');
    $geoWarningColumns = array(
        'A' => 'coordinates_outside_nation',
        'B' => 'coordinates_outside_province',
        'C' => 'coordinate_check_unavailable',
    );
    foreach ($geoWarningColumns as $column => $flag) {
        $row = 2;
        if (!empty($payload['geoWarnings'][$flag])) {
            foreach ($payload['geoWarnings'][$flag] as $value) {
                $geoWarningSheet->setCellValue($column . $row, $value);
                $row++;
            }
        }
    }
    $geoWarningSheet->freezePane('A2');
    $geoWarningSheet->getTabColor()->setRGB('F1C232');
    autosizeImportExportColumns($geoWarningSheet, 'C');

    $similarSheet = $spreadsheet->createSheet();
    $similarSheet->setTitle('Taxon not found but similar');
    $similarSheet->setCellValue('A1', 'searched taxon');
    $maxSuggestionCount = 0;
    if (!empty($payload['similar'])) {
        foreach ($payload['similar'] as $suggestions) {
            $maxSuggestionCount = max($maxSuggestionCount, count($suggestions));
        }
    }
    for ($index = 1; $index <= max(1, $maxSuggestionCount); $index++) {
        $similarSheet->setCellValueByColumnAndRow($index + 1, 1, 'alternative ' . $index);
    }
    $similarHighestColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(max(2, $maxSuggestionCount + 1));
    styleImportExportHeader($similarSheet, 'A1:' . $similarHighestColumn . '1', 'FFF1C232');
    $row = 2;
    foreach ($payload['similar'] as $searchedTaxon => $suggestions) {
        $similarSheet->setCellValue('A' . $row, $searchedTaxon);
        $column = 2;
        foreach ($suggestions as $suggestion) {
            $similarSheet->setCellValueByColumnAndRow($column, $row, $suggestion);
            $column++;
        }
        $row++;
    }
    $similarSheet->freezePane('A2');
    $similarSheet->getTabColor()->setRGB('F1C232');
    autosizeImportExportColumns($similarSheet, $similarHighestColumn);

    $existsSheet = $spreadsheet->createSheet();
    $existsSheet->setTitle('exists');
    $existsSheet->setCellValue('A1', 'HerbNummer');
    styleImportExportHeader($existsSheet, 'A1', 'FF4F81BD');
    $row = 2;
    foreach ($payload['exists'] as $herbNummer) {
        $existsSheet->setCellValue('A' . $row, $herbNummer);
        $row++;
    }
    $existsSheet->freezePane('A2');
    $existsSheet->getTabColor()->setRGB('4F81BD');
    autosizeImportExportColumns($existsSheet, 'A');

    $baseName = trim((string)$payload['inputName']);
    if ($baseName === '') {
        $baseName = 'importSpecimens';
    } else {
        $baseName = pathinfo($baseName, PATHINFO_FILENAME);
    }
    $baseName = preg_replace('/[^A-Za-z0-9._-]+/', '_', $baseName);
    $fileName = $baseName . '_validation_export.xlsx';

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');

    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save('php://output');
    exit;
}

if (!empty($_POST['export_validation'])) {
    $exportToken = isset($_POST['export_token']) ? $_POST['export_token'] : '';
    $exportPayload = loadImportValidationExportPayload($exportToken);
    if (is_array($exportPayload)) {
        streamImportValidationExportWorkbook($exportPayload);
    }

    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Export payload not found or expired.';
    exit;
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
    // Domain allowlist is intentionally disabled for now.
    // Keep the helper functions in place so host restrictions can be re-enabled later.

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
    $maxBytes = 8000000;
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

        $tempPath = tempnam(sys_get_temp_dir(), 'imp_');
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
        curl_setopt($ch, CURLOPT_USERAGENT, 'JACQ importSpecimens downloader');
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
            $error = 'The downloaded file exceeds the maximum size of 8000000 bytes.';
            return false;
        }

        if ($execResult === false) {
            @unlink($tempPath);
            $error = 'Download failed: ' . $curlError;
            return false;
        }

        if (isset($headers['content-length']) && (int)$headers['content-length'] > $maxBytes) {
            @unlink($tempPath);
            $error = 'The remote file exceeds the maximum size of 8000000 bytes.';
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

function renderImportInputForm($downloadUrl = '', $checkGeographicAssignments = false)
{
    echo "<input type='hidden' name='MAX_FILE_SIZE' value='" . IMPORT_SPECIMENS_MAX_FILE_SIZE . "' />\n"
       . "<div class='panel form-panel'>\n"
       . "  <div class='panel-heading'>Import Source</div>\n"
       . "  <div class='form-layout'>\n"
       . "    <div class='form-field'><label class='form-label' for='import_userfile'>Import file</label><div class='form-control-wrap'><input id='import_userfile' name='userfile' type='file' accept='.txt,.csv,text/plain,text/csv' class='form-control form-control-file'></div></div>\n"
       . "    <div class='form-field'><label class='form-label' for='import_download_url'>Download URL</label><div class='form-control-wrap'><input id='import_download_url' name='download_url' type='url' class='form-control' value='" . htmlspecialchars($downloadUrl, ENT_QUOTES) . "' placeholder='https://...'></div></div>\n"
       . "    <div class='form-field'><div class='form-label'>Allowed HTTPS hosts</div><div class='form-control-wrap form-static-text'>All hosts (.txt and .csv only)</div></div>\n"
       . "    <div class='form-field'><label class='form-label' for='import_geo_check'>Geographic validation</label><div class='form-control-wrap'><label><input id='import_geo_check' type='checkbox' name='geo_check' value='1'" . ($checkGeographicAssignments ? " checked='checked'" : '') . " /> Check geographic assignment of coordinates to country and province</label><div class='form-static-text' style='margin-top:6px;'>Uses the JACQ boundary service. Mismatches are warnings that can be reviewed before import.</div></div></div>\n"
       . "  </div>\n"
       . "  <div class='form-actions'><input type='submit' value='Check import' class='button-primary'></div>\n"
       . "</div>\n"
       . "<div class='panel info-panel'>\n"
       . "  <div class='panel-heading'>Import Rules</div>\n"
       . "  <div class='notice form-notice'>\n"
       . "    <p>This tool accepts semicolon-separated <strong>.txt</strong> or <strong>.csv</strong> files with a UTF-8 header line. Files can be uploaded directly or downloaded from an HTTPS URL.</p>\n"
       . "    <p><a href='importSpecimens_template.txt'>Download the current import template</a>. \n"
       . "    <p><a href='tbl_import_specimens_template_v2.3_112026.xlsx'>Download the Excel field list and examples</a>.</p>\n"
       . "    <p>Required headers: <strong>HerbNummer</strong>, <strong>collectionID</strong>, <strong>taxon</strong> and <strong>Sammler</strong>. Header order does not matter. <strong>checked</strong> and <strong>accessible</strong> must not be included; they are set only after review in the specimen import area.</p>\n"
       . "    <p>The HerbNummer column is required. If any row has a HerbNummer, all rows must have one. If every HerbNummer is empty, you must confirm a warning before continuing. Identical rows with the same HerbNummer are processed once; conflicting rows with the same HerbNummer stop the import.</p>\n"
       . "    <p>Allowed header attributes are exactly: <strong>HerbNummer</strong>, <strong>collectionID</strong>, <strong>CollectionNumber</strong>, <strong>status</strong>, <strong>taxon</strong>, <strong>Sammler</strong>, <strong>series</strong>, <strong>series_number</strong>, <strong>Nummer</strong>, <strong>alt_number</strong>, <strong>Datum</strong>, <strong>Datum2</strong>, <strong>det</strong>, <strong>typified</strong>, <strong>typus</strong>, <strong>taxon_alt</strong>, <strong>nation_engl</strong>, <strong>provinz</strong>, <strong>Fundort</strong>, <strong>Fundort_engl</strong>, <strong>Habitat</strong>, <strong>Habitus</strong>, <strong>Bemerkungen</strong>, <strong>coord_NS</strong>, <strong>lat_degree</strong>, <strong>lat_minute</strong>, <strong>lat_second</strong>, <strong>coord_WE</strong>, <strong>long_degree</strong>, <strong>long_minute</strong>, <strong>long_second</strong>, <strong>quadrant</strong>, <strong>quadrant_sub</strong>, <strong>exactness</strong>, <strong>alt_min</strong>, <strong>alt_max</strong>, <strong>digital_image</strong>, <strong>digital_image_obs</strong>, <strong>observation</strong> and <strong>notes_internal</strong>.</p>\n"
       . "    <p>UTF-8 files are processed directly. Windows-1252 and ISO-8859-1 files can be converted to UTF-8 after confirmation.</p>\n"
       . "  </div>\n"
       . "</div>\n";
}
/**
 * queries the database for a taxon with given ID
 *
 * @param int $id taxon-ID
 * @param bool[optional] $withAuthors check authors also
 * @return string taxon-text
 */
function getTaxon($id, $withAuthors = true) {
  $sql = "SELECT tg.genus,
           ta0.author  author0,  ta1.author  author1,  ta2.author  author2,  ta3.author  author3,  ta4.author  author4,  ta5.author  author5,
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
  $row = dbi_query($sql)->fetch_array();

  $text = $row['genus'];
  if ($row['epithet0']) $text .= " "         . $row['epithet0'] . (($withAuthors) ? " " . $row['author0'] : '');
  if ($row['epithet1']) $text .= " subsp. "  . $row['epithet1'] . (($withAuthors) ? " " . $row['author1'] : '');
  if ($row['epithet2']) $text .= " var. "    . $row['epithet2'] . (($withAuthors) ? " " . $row['author2'] : '');
  if ($row['epithet3']) $text .= " subvar. " . $row['epithet3'] . (($withAuthors) ? " " . $row['author3'] : '');
  if ($row['epithet4']) $text .= " forma "   . $row['epithet4'] . (($withAuthors) ? " " . $row['author4'] : '');
  if ($row['epithet5']) $text .= " subf. "   . $row['epithet5'] . (($withAuthors) ? " " . $row['author5'] : '');

  return $text;
}

/** Describe differences between a TaxaMatch suggestion and the local taxon record. */
function getImportTaxaMatchWarning($taxonId, $suggestedName)
{
    static $localTaxa = array();
    $taxonId = intval($taxonId);
    if ($taxonId <= 0) {
        return 'TaxaMatch did not provide a valid taxon ID.';
    }
    if (!array_key_exists($taxonId, $localTaxa)) {
        $result = dbi_query('SELECT taxonID FROM tbl_tax_species WHERE taxonID = ' . $taxonId);
        $localTaxa[$taxonId] = mysqli_num_rows($result) ? getTaxon($taxonId) : null;
    }
    if ($localTaxa[$taxonId] === null) {
        return 'TaxaMatch ID ' . $taxonId . ' is not present in the local database. The TaxaMatch and local databases may contain different taxa; do not select this suggestion until the data are synchronized.';
    }
    $suggestedName = html_entity_decode(strip_tags((string)$suggestedName), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $localName = html_entity_decode(strip_tags((string)$localTaxa[$taxonId]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $suggestedName = preg_replace('/\s+/u', ' ', trim($suggestedName));
    $localName = preg_replace('/\s+/u', ' ', trim($localName));
    if ($suggestedName !== $localName) {
        return 'TaxaMatch ID ' . $taxonId . ' has a different local name: ' . $localTaxa[$taxonId] . '. Check the data sources before selecting this suggestion.';
    }
    return '';
}

/**
 * split the received taxonomic string into its subparts
 * first part is supposed to be the genus
 * if there is a second part is's supposed to be the species
 * if there's more, the parser searches for a rank keyword and the next part has to be the infraspecies
 * if the parser cannot find any rank keyword (subsp, var, subvar, forma or subf) the rest is ignored
 *
 * @param string $text the taxonomic string to parse
 * @return array parts of the parsed string -> genus, species, infra, subspecies, searchtext
 */
function splitTaxon($text)
{
    $pieces = explode(' ', $text, 3);
    $taxon['genus'] = trim($pieces[0]);
    if (count($pieces) > 1) {
        $taxon['species'] = trim($pieces[1]);
        if (count($pieces) > 2) {
            if (strpos($pieces[2], 'subsp') !== false) {
                $pos = strpos($pieces[2], 'subsp');
                $taxon['infra'] = " subsp. ";
            } elseif (strpos($pieces[2], 'var') !== false) {
                $pos = strpos($pieces[2], 'var');
                $taxon['infra'] = " var. ";
            } elseif (strpos($pieces[2], 'subvar') !== false) {
                $pos = strpos($pieces[2], 'subvar');
                $taxon['infra'] = " subvar. ";
            } elseif (strpos($pieces[2], 'forma') !== false) {
                $pos = strpos($pieces[2], 'forma');
                $taxon['infra'] = " forma ";
            } elseif (strpos($pieces[2], 'subf') !== false) {
                $pos = strpos($pieces[2], 'subf');
                $taxon['infra'] = " subforma ";
            } else {
                $pos = false;
                $taxon['infra'] = $taxon['subspecies'] = '';
            }
            if ($pos !== false) {
                $pieces2 = explode(' ', substr($pieces[2], $pos));
                $taxon['subspecies'] = (count($pieces2) > 1) ? $pieces2[1] : '';
            }
        } else {
            $taxon['infra'] = $taxon['subspecies'] = '';
        }
    } else {
        $taxon['species'] = $taxon['infra'] = $taxon['subspecies'] = '';
    }
    $taxon['searchtext'] = trim($taxon['genus'] . ' ' . $taxon['species'] . $taxon['infra'] . $taxon['subspecies']);

    return $taxon;
}

/**
 * inserts a new taxon into tbl_tax_species if the genus already exists
 * inserts all necessary epithets and authors into the appropriate tables
 *
 * @param string $taxon taxon to insert
 * @param integer $externalID external-ID, must be >0
 * @param integer $contentID primary ID of tbl_external_import_content
 * @param boolean $insert_new_genera New taxa with a genus part which is not
 *      yet in te database can be inserted if this option is turned ons. A
 *      new genus entry flagged as external will be created. It will be associated
 *      with a 'incertae sedis' family
 * @return array inserted "taxonID" or happened "error"
 */
function insertTaxon($taxon, $externalID, $contentID, $insert_new_genera = FALSE)
{
    $externalID = intval($externalID);
    $contentID  = intval($contentID);

    $ret = array('error' => '');

    if ($externalID == 0) {  // external-ID = 0 is not allowed since this is the value of the empty default
        $ret['error'] = 'Import of this new taxon has been skipped, you need to choose an externalID in the "Import Specimens" dialog first';
        return $ret;
    }

    $parser = TaxonTokenizer::Load();
    $taxonParts = $parser->tokenize($taxon);

    $result = dbi_query("SELECT genID FROM tbl_tax_genera WHERE genus = " . quoteString($taxonParts['genus']));
    if (mysqli_num_rows($result) == 0) {
        if($insert_new_genera){
            // add nevertheles
            $sql = "INSERT INTO tbl_tax_genera SET
                         genus = "    . quoteString($taxonParts['genus']) . ",
                         accepted = 1,
                         familyID = " . makeInt(INCERTAE_SEDIS_IMPORT);
            $result = dbi_query($sql);
            if ($result) {
                $genID = dbi_insert_id();
                Log::genera($genID, 0);
            } else {
                $genID = 0;
            }
        } else {
            // genus not found -> abort
            $ret['error'] = 'genus not found';
            return $ret;
        }
    } else {
        $row = mysqli_fetch_array($result);
        $genID = $row['genID'];
    }

  // find or insert the epithet if present
    if ($taxonParts['epithet']) {
        $result = dbi_query("SELECT epithetID FROM tbl_tax_epithets WHERE epithet = " . quoteString($taxonParts['epithet']));
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_array($result);
            $epithetID = $row['epithetID'];
        } else {
            dbi_query("INSERT INTO tbl_tax_epithets SET
                        epithet = " . quoteString($taxonParts['epithet']) . ",
                        locked = 0,
                        external = 1,
                        externalID = " . quoteString($externalID));
            $epithetID = dbi_insert_id();
        }
    } else {
        $epithetID = null;
    }

    // find or insert the author if present
    if ($taxonParts['author']) {
        $result = dbi_query("SELECT authorID FROM tbl_tax_authors WHERE author = " . quoteString($taxonParts['author']));
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_array($result);
            $authorID = $row['authorID'];
        } else {
            dbi_query("INSERT INTO tbl_tax_authors SET
                        author = " . quoteString($taxonParts['author']) . ",
                        locked = 0,
                        external = 1,
                        externalID = " . quoteString($externalID));
            $authorID = dbi_insert_id();
            Log::authors($authorID, 0);
        }
    } else {
        $authorID = null;
    }

    // find or insert the subepithet if present
    if ($taxonParts['subepithet']) {
        $result = dbi_query("SELECT epithetID FROM tbl_tax_epithets WHERE epithet = " . quoteString($taxonParts['subepithet']));
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_array($result);
            $subepithetID = $row['epithetID'];
        } else {
            dbi_query("INSERT INTO tbl_tax_epithets SET
                        epithet = " . quoteString($taxonParts['subepithet']) . ",
                        locked = 0,
                        external = 1,
                        externalID = " . quoteString($externalID));
            $subepithetID = dbi_insert_id();
        }
    } else {
        $subepithetID = null;
    }

    // find or insert the subauthor if present
    if ($taxonParts['subauthor']) {
        $result = dbi_query("SELECT authorID FROM tbl_tax_authors WHERE author = " . quoteString($taxonParts['subauthor']));
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_array($result);
            $subauthorID = $row['authorID'];
        } else {
            dbi_query("INSERT INTO tbl_tax_authors SET
                        author = " . quoteString($taxonParts['subauthor']) . ",
                        locked = 0,
                        external = 1,
                        externalID = " . quoteString($externalID));
            $subauthorID = dbi_insert_id();
            Log::authors($subauthorID, 0);
        }
    } else {
        $subauthorID = null;
    }

    $sql = "INSERT INTO tbl_tax_species SET
             genID = $genID,
             speciesID = " . (($epithetID) ? $epithetID : "NULL") . ",
             authorID = " . (($authorID) ? $authorID : "NULL") . ",
             tax_rankID = " . makeInt($taxonParts['rank'] + 1) . ",
             locked = 0,
             external = 1,
             externalID = " . makeInt($externalID);
    switch ($taxonParts['rank']) {
        case 1:
            if ($subepithetID) $sql .= ", subspeciesID = $subepithetID";
            if ($subauthorID)  $sql .= ", subspecies_authorID = $subauthorID";
            break;
        case 2:
            if ($subepithetID) $sql .= ", varietyID = $subepithetID";
            if ($subauthorID)  $sql .= ", variety_authorID = $subauthorID";
            break;
        case 3:
            if ($subepithetID) $sql .= ", subvarietyID = $subepithetID";
            if ($subauthorID)  $sql .= ", subvariety_authorID = $subauthorID";
            break;
        case 4:
            if ($subepithetID) $sql .= ", formaID = $subepithetID";
            if ($subauthorID)  $sql .= ", forma_authorID = $subauthorID";
            break;
        case 5:
            if ($subepithetID) $sql .= ", subformaID = $subepithetID";
            if ($subauthorID)  $sql .= ", subforma_authorID = $subauthorID";
            break;
    }
    dbi_query($sql);
    $ret['taxonID'] = dbi_insert_id();
    Log::species($ret['taxonID'], 0);

    dbi_query("UPDATE tbl_external_import_content SET
                externalID = $externalID,
                taxonID    = " . $ret['taxonID'] . "
               WHERE contentID = $contentID");

    return $ret;
}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - import Specimens</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="../css/screen.css">
  <style type="text/css">
      :root { --page-bg:#cfd6d0; --panel-bg:#f7f8f5; --panel-border:#b5beb5; --panel-shadow:rgba(37,58,39,.08); --text-main:#213126; --text-muted:#5a6a60; --accent:#5b7c4d; --accent-dark:#3f5b36; --accent-soft:#dde7d6; --error-soft:#f2d2d2; --table-head:#e4eadf; }
      * { box-sizing: border-box; }
      body { margin:0; font-family:Arial,sans-serif; font-size:13px; line-height:1.45; color:var(--text-main); background:linear-gradient(180deg,#bfc8c1 0%,var(--page-bg) 100%); }
      h1,h2 { margin:0; } p { margin:0 0 10px; }
      a:link, a:visited, a:hover, a:active, a:focus { color:#000; }
      .page-shell { max-width:1680px; margin:0 auto; padding:28px 24px 40px; }
      .page-header { margin-bottom:20px; padding:18px 22px; border:1px solid var(--panel-border); background:linear-gradient(135deg,#f5f7f2 0%,#e6ece1 100%); box-shadow:0 12px 24px var(--panel-shadow); }
      .page-title { font-size:28px; line-height:1.1; color:#2f4636; }
      .page-subtitle { margin-top:6px; color:var(--text-muted); font-size:14px; }
      .panel { margin:0 0 18px; padding:18px 20px; border:1px solid var(--panel-border); background:var(--panel-bg); box-shadow:0 10px 24px var(--panel-shadow); }
      .panel-heading { margin-bottom:14px; font-size:15px; font-weight:bold; letter-spacing:.02em; color:#2f4636; text-transform:uppercase; }
      .form-panel,.info-panel { max-width:1120px; }
      .form-layout { display:grid; grid-template-columns:minmax(170px,220px) minmax(0,1fr); gap:14px 18px; align-items:start; }
      .form-field { display:contents; } .form-label { padding-top:10px; font-weight:bold; color:#35503d; } .form-control-wrap { min-width:0; }
      .form-control { width:100%; min-height:38px; padding:8px 10px; border:1px solid #aeb8b1; background:#fff; color:var(--text-main); font:inherit; }
      .form-control:focus { outline:none; border-color:var(--accent); box-shadow:0 0 0 3px rgba(91,124,77,.16); }
      .form-control-file { padding:6px 8px; } .form-static-text { min-height:38px; padding:9px 10px; border:1px solid #d0d7d1; background:#eef2ec; color:#435248; }
      .form-actions,.action-buttons { margin:18px 0; display:flex; gap:10px; flex-wrap:wrap; }
      input[type="submit"],button { min-height:36px; padding:8px 14px; border:1px solid #889687; background:linear-gradient(180deg,#fff 0%,#ecefe8 100%); color:var(--text-main); font:inherit; cursor:pointer; }
      input[type="submit"]:hover,button:hover { background:linear-gradient(180deg,#fff 0%,#dfe7d8 100%); }
      .button-primary { border-color:var(--accent-dark); background:linear-gradient(180deg,#6d915d 0%,#4f7143 100%); color:#fff; font-weight:bold; }
      .button-primary:hover { background:linear-gradient(180deg,#769c65 0%,#46663b 100%); }
      .form-notice p:last-child { margin-bottom:0; }
      .error,#import_errors { margin:0 0 18px; padding:12px 14px; border:1px solid #c87f7f; background:var(--error-soft); color:#8b1f1f; box-shadow:0 8px 18px rgba(128,46,46,.08); }
      .summary,#import_tasks,#import_success { margin:0 0 18px; padding:14px 18px; border:1px solid #b8c7b2; background:var(--accent-soft); color:#2d4730; box-shadow:0 8px 18px var(--panel-shadow); }
      .notice { margin:0; color:var(--text-main); }
      .import-preview { overflow-x:auto; margin:0 0 18px; border:1px solid var(--panel-border); background:var(--panel-bg); box-shadow:0 10px 24px var(--panel-shadow); }
      .import-preview table { width:max-content; min-width:100%; border-collapse:collapse; background:#fff; }
      .import-preview th { background:var(--table-head); } .import-preview th,.import-preview td { border:1px solid #888; padding:7px 9px; vertical-align:top; }
      @media (max-width:860px) { .page-shell { padding:18px 14px 28px; } .page-title { font-size:24px; } .form-layout { grid-template-columns:1fr; gap:8px; } .form-label { padding-top:0; } }
  </style>
  <script src="../js/lib/jQuery/jquery.min.js" type="text/javascript"></script>
  <script type="text/javascript" language="JavaScript">
      $(document).ready(function() {
          $( 'input[name^="similarTaxa_"]' ).change( function() {
              if( !$(this).attr( 'checked' ) ) return;

              // Find compare string
              var origString = $( 'input[name="' + $(this).attr( 'name' ) + '_orig"]' ).val();
              var selectVal = $(this).val();

              // Now find all other entries with the same value
              $( 'input[value="' + origString + '"]' ).each( function() {
                  var currName = $(this).attr( 'name' );
                  currName = currName.replace( '_orig', '' );

                  $( 'input[name="' + currName + '"][value="' + selectVal + '"]' ).attr( 'checked', true );
              } );
          } );
      });
  </script>
</head>

<body>
<?php
$blocked = false;  // for now, anybody may insert anything   TODO: check the access rights!

?>

<?php if ($blocked): ?>
<script type="text/javascript" language="JavaScript">
  alert('You have no sufficient rights for the desired operation');
</script>
<?php endif; ?>

<?php
$downloadUrl = isset($_POST['download_url']) ? trim($_POST['download_url']) : '';
$hasUploadedFile = isset($_FILES['userfile']) && is_uploaded_file($_FILES['userfile']['tmp_name']);
$hasDownloadUrl = (strlen($downloadUrl) > 0);
$inputPath = '';
$inputName = '';
$inputCleanupNeeded = false;
$inputSource = '';
$inputError = '';
$pageNotice = '';
$encodingConfirmation = null;
$herbConfirmation = null;
$hasConvertedPendingInput = false;
$hasConfirmedHerbInput = false;
$checkGeographicAssignments = !empty($_POST['geo_check']);
$encodingAction = isset($_POST['import_encoding_action']) ? $_POST['import_encoding_action'] : '';
$herbAction = isset($_POST['import_herb_action']) ? $_POST['import_herb_action'] : '';

if ($herbAction === 'cancel') {
    clearPendingImportHerbConfirmation();
    $pageNotice = 'The import without herbarium numbers was cancelled.';
} elseif ($herbAction === 'confirm') {
    $pendingHerb = $_SESSION[IMPORT_SPECIMENS_PENDING_HERB_SESSION_KEY] ?? null;
    $submittedToken = $_POST['herb_confirmation_token'] ?? '';
    if (!is_array($pendingHerb) || empty($pendingHerb['path']) || !is_file($pendingHerb['path'])
        || !is_string($submittedToken) || empty($pendingHerb['token']) || !hash_equals($pendingHerb['token'], $submittedToken)) {
        $inputError = 'The pending herbarium number confirmation is no longer available. Please upload the file again.';
    } else {
        $inputPath = $pendingHerb['path'];
        $inputName = $pendingHerb['name'];
        $checkGeographicAssignments = !empty($pendingHerb['geo_check']);
        $inputSource = 'confirmed_no_herb_numbers';
        $inputCleanupNeeded = true;
        $hasConfirmedHerbInput = true;
        unset($_SESSION[IMPORT_SPECIMENS_PENDING_HERB_SESSION_KEY]);
    }
    $hasUploadedFile = false;
    $hasDownloadUrl = false;
} elseif ($encodingAction === 'cancel') {
    clearPendingImportInput();
    $pageNotice = 'The import file conversion was cancelled.';
} elseif ($encodingAction === 'convert') {
    $pendingInput = isset($_SESSION[IMPORT_SPECIMENS_PENDING_INPUT_SESSION_KEY]) ? $_SESSION[IMPORT_SPECIMENS_PENDING_INPUT_SESSION_KEY] : null;
    if (!is_array($pendingInput) || empty($pendingInput['path']) || !is_file($pendingInput['path'])) {
        $inputError = 'The pending import file is no longer available. Please upload it again.';
        clearPendingImportInput();
    } elseif (!convertImportFileToUtf8($pendingInput['path'], isset($pendingInput['encoding']) ? $pendingInput['encoding'] : '', $inputPath, $inputError)) {
        clearPendingImportInput();
    } else {
        $inputName = isset($pendingInput['name']) ? $pendingInput['name'] : 'importSpecimens.txt';
        $checkGeographicAssignments = !empty($pendingInput['check_geographic_assignments']);
        $inputSource = 'converted_pending_input';
        $inputCleanupNeeded = true;
        $hasConvertedPendingInput = true;
        clearPendingImportInput();
    }
} elseif ($hasUploadedFile || $hasDownloadUrl) {
    clearPendingImportHerbConfirmation();
}
?>
<?php
if ($hasUploadedFile || $hasDownloadUrl || $hasConvertedPendingInput || $hasConfirmedHerbInput || $encodingAction === 'convert' || $herbAction === 'confirm') {
    /**
     * a file was provided and is ready to be parsed
     */
    $run = 2; // input provided => 2nd run
} elseif (!empty($_POST['import_data'])) {
    /**
     * data is ready to be inserted into the database
     */
    $run = 3;  // insert data => 3rd run
} else {
    /**
     *  do nothing special, just show the basic form.
     */
    $run = 1; // 1st run
}
?>
<div class="page-shell">
<div class="page-header">
    <h1 class="page-title">Import Specimens</h1>
    <div class="page-subtitle"><?php echo ($run === 2) ? 'Review the imported data, correct warnings and select the records to be staged for import.' : (($run === 3) ? 'Import results for the selected specimen records.' : 'Upload a header-based .txt or .csv file, or provide a download URL, to start an import run.'); ?></div>
</div>

<form enctype="multipart/form-data" Action="<?php echo htmlspecialchars($_SERVER['SCRIPT_NAME']);?>" Method="POST" name="f">

<?php
if ($run == 2) {  // file provided
    $import = array();
    $duplicateRows = array();

    if ($hasConvertedPendingInput || $hasConfirmedHerbInput) {
        // Input path was restored from a confirmation above.
    } elseif ($hasUploadedFile) {
        $inputPath = $_FILES['userfile']['tmp_name'];
        $inputName = $_FILES['userfile']['name'];
        $inputSource = 'upload';
    } elseif ($hasDownloadUrl) {
        $inputSource = 'download_url';
        $inputCleanupNeeded = true;
        if (!downloadImportInput($downloadUrl, $inputPath, $inputName, $inputError)) {
            $inputPath = '';
        }
    }

    if (!$inputError && $inputPath !== '') {
        $inputEncoding = detectImportEncoding($inputPath);
        if ($inputEncoding === '') {
            $inputError = 'The file encoding could not be determined. Only UTF-8, Windows-1252 and ISO-8859-1 files are supported.';
        } elseif ($inputEncoding !== 'UTF-8' && !$hasConvertedPendingInput) {
            if (storePendingImportInput($inputPath, $inputName, $inputEncoding, $checkGeographicAssignments, $inputError)) {
                $encodingConfirmation = array('filename' => $inputName, 'encoding' => $inputEncoding);
                if ($inputCleanupNeeded && is_file($inputPath)) {
                    @unlink($inputPath);
                    $inputCleanupNeeded = false;
                }
            }
        } elseif ($inputEncoding !== 'UTF-8') {
            $inputError = 'The converted file is still not valid UTF-8.';
        } else {
            $import = loadHeaderBasedImportRows($inputPath, $inputError);
            $allHerbNumbersMissing = false;
            if (!$inputError) {
                $import = checkImportFileDuplicates($import, $duplicateRows, $inputError, $allHerbNumbersMissing);
            }
            if (!$inputError && $allHerbNumbersMissing && !$hasConfirmedHerbInput) {
                if (storePendingImportHerbConfirmation($inputPath, $inputName, $checkGeographicAssignments, $inputError)) {
                    $herbConfirmation = $_SESSION[IMPORT_SPECIMENS_PENDING_HERB_SESSION_KEY];
                }
            }
            if (!$inputError && $herbConfirmation === null) {
                if (storeImportProcessInputFile($inputPath, $inputName, $inputError)) {
                    $_SESSION[IMPORT_SPECIMENS_PROCESS_SESSION_KEY]['without_herb_numbers'] = $allHerbNumbersMissing && $hasConfirmedHerbInput;
                }
            }
            if (!$inputError && $herbConfirmation === null) {
                $first = $second = $third = array();
                foreach ($import as $key => $row) {
                    $first[$key]  = $row[0];
                    $second[$key] = $row[3];
                    $third[$key]  = $row[4];
                }
                if (!empty($first) && !empty($second) && !empty($third)) {
                    array_multisort($first, SORT_ASC, SORT_STRING, $second, SORT_ASC, SORT_STRING, $third, SORT_ASC, SORT_STRING, $import);
                }
            }
        }
    }

    if ($encodingConfirmation !== null) {
        echo "<div class=\"panel info-panel\"><div class=\"panel-heading\">File Encoding</div><div class=\"notice form-notice\"><p>The file <strong>" . htmlspecialchars($encodingConfirmation['filename']) . "</strong> is encoded as <strong>" . htmlspecialchars($encodingConfirmation['encoding']) . "</strong>, not UTF-8.</p>";
        echo "<p>Convert a temporary copy to UTF-8 before checking the import data, or cancel this upload.</p>";
        echo "</div><div class=\"action-buttons\"><button type=\"submit\" name=\"import_encoding_action\" value=\"convert\" class=\"button-primary\">Convert to UTF-8 and continue</button> ";
        echo "<button type=\"submit\" name=\"import_encoding_action\" value=\"cancel\">Cancel</button></div></div>\n";
    } elseif ($inputError) {
        echo "<div class=\"error\">" . htmlspecialchars($inputError) . "</div>\n";
        renderImportInputForm($downloadUrl, $checkGeographicAssignments);
    } elseif ($herbConfirmation !== null) {
        echo '<div class="panel info-panel"><div class="panel-heading">Herbarium numbers missing</div>'
           . '<div class="notice form-notice"><p>No data row in <strong>' . htmlspecialchars($herbConfirmation['name'], ENT_QUOTES, 'UTF-8')
           . '</strong> has a HerbNummer. Confirm that you want to continue without herbarium numbers, or cancel and correct the file.</p></div>'
           . '<input type="hidden" name="herb_confirmation_token" value="' . htmlspecialchars($herbConfirmation['token'], ENT_QUOTES, 'UTF-8') . '">'
           . '<div class="action-buttons"><button type="submit" name="import_herb_action" value="confirm" class="button-primary">Continue without herbarium numbers</button>'
           . '<button type="submit" name="import_herb_action" value="cancel">Cancel</button></div></div>';
    } else {
        if ($hasConfirmedHerbInput) {
            echo '<div class="summary">Import without herbarium numbers confirmed. Review the records below before importing.</div>';
        }
        if ($duplicateRows) {
            echo '<div class="panel info-panel"><div class="panel-heading">Identical rows omitted</div><p>The following rows duplicate an identical row in this file. Only the first occurrence will be processed.</p><ul>';
            foreach ($duplicateRows as $index => $duplicate) {
                echo '<li>Line ' . intval($duplicate['line']) . ': ' . htmlspecialchars($duplicate['herbNumber'], ENT_QUOTES, 'UTF-8')
                   . ' (same as line ' . intval($duplicate['keptLine']) . ')'
                   . '<input type="hidden" name="duplicateRows[' . intval($index) . '][herbNumber]" value="' . htmlspecialchars($duplicate['herbNumber'], ENT_QUOTES, 'UTF-8') . '">'
                   . '<input type="hidden" name="duplicateRows[' . intval($index) . '][line]" value="' . intval($duplicate['line']) . '">'
                   . '<input type="hidden" name="duplicateRows[' . intval($index) . '][keptLine]" value="' . intval($duplicate['keptLine']) . '"></li>';
            }
            echo '</ul></div>';
        }
        dbi_query("DELETE FROM tbl_external_import_content
               WHERE specimen_ID IS NULL
                AND externalID IS NULL
                AND taxonID IS NULL");

    $ranks = array('', " subsp. ", " var. ", " subvar. ", " forma ", " subforma ");
    $status = array();
    $geoWarnings = array();
    $exists = array();
    $importable = 0;
    $importableTaxaPresent = false;
    $data = array();
    for ($i = 0; $i < count($import); $i++) {
        $OK = true;
        $status[$i] = "";
        $geoWarnings[$i] = array();

        /**
         * check if collection-ID exists
         */
        $result = dbi_query("SELECT collection FROM tbl_management_collections WHERE collectionID = '" . intval($import[$i][1]) . "'");
        if (mysqli_num_rows($result) == 0) {
            $OK = false;
            $status[$i] .= "no_collection ";
            $data[$i]['collectionID'] = 0;
        } else {
            $data[$i]['collectionID'] = intval($import[$i][1]);
        }

        /**
         * get HerbNummer and check if this number already exists for the same institution (source_id), if there is a HerbNummer
         */
        $data[$i]['HerbNummer'] = getImportHerbNumber($import[$i][0]);
        if ($data[$i]['HerbNummer']) {
//            $sql = "SELECT source_id
//                    FROM tbl_management_collections
//                    WHERE collectionID = '" . $data[$i]['collectionID'] . "'";
//            $row = mysqli_fetch_array(dbi_query($sql));
//            $sql = "SELECT specimen_ID
//                    FROM tbl_specimens, tbl_management_collections
//                    WHERE tbl_specimens.collectionID = tbl_management_collections.collectionID
//                     AND source_id = '" . $row['source_id'] . "'
//                     AND HerbNummer = " . quoteString($data[$i]['HerbNummer']);
//            $result = dbi_query($sql);
            $result = dbi_query("SELECT s.`specimen_ID`
                                 FROM `tbl_specimens` s, `tbl_management_collections` mc
                                 WHERE s.`collectionID` = mc.`collectionID`
                                  AND s.`HerbNummer` = " . quoteString($data[$i]['HerbNummer']) . "
                                  AND mc.`collectionID` = " . quoteString($data[$i]['collectionID']) . "
                                  AND mc.`allowDuplicateHerbNr` = 0");
            if (mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_array($result);
                $OK = false;
                $status[$i] .= "exists ";
                $exists[$i] = $row['specimen_ID'];
            }
        }

        /**
         * fill collection number CollNummer
         */
        $data[$i]['CollNummer'] = (!empty($import[$i][2])) ? $import[$i][2] : '';

        /**
         * check if identstatus exists
         */
        if (trim($import[$i][3])) {
            $result = dbi_query("SELECT identstatusID FROM tbl_specimens_identstatus WHERE identification_status = " . quoteString(trim($import[$i][3])));
            if (mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_array($result);
                $data[$i]['identstatusID'] = $row['identstatusID'];
            } else {
                $OK = false;
                $status[$i] .= "no_identstatus ";
            }
        } else {
            $data[$i]['identstatusID'] = "";
        }

        /**
         * check if taxon exists
         */
        $taxonOK = false;
        $genusOK = false;
        $pieces = explode(' ', $import[$i][4], 3);
        $result = dbi_query("SELECT genID FROM tbl_tax_genera WHERE genus = " . quoteString($pieces[0]));
        if (mysqli_num_rows($result) > 0) {
            $genusOK = true;
            $row = mysqli_fetch_array($result);
            $genID = $row['genID'];
            if (count($pieces) > 1) {
                $result = dbi_query("SELECT epithetID FROM tbl_tax_epithets WHERE epithet = " . quoteString($pieces[1]));
                if (mysqli_num_rows($result) > 0) {
                    $row = mysqli_fetch_array($result);
                    $epithetID = $row['epithetID'];
                    $result = dbi_query("SELECT taxonID FROM tbl_tax_species WHERE genID = '$genID' AND speciesID = '$epithetID'");
                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_array($result)) {
                          if (strcmp(trim(getTaxon($row['taxonID'])), trim($import[$i][4])) == 0) {
                              $taxonOK = true;
                              $data[$i]['taxonID'] = $row['taxonID'];
                              break;
                          } else if (strcmp(trim(getTaxon($row['taxonID'], false)), trim($import[$i][4])) == 0) {
                              $taxonOK = true;
                              $data[$i]['taxonID'] = $row['taxonID'];
                              break;
                          }
                        }
                    }
                }
            } else {
                $sql = "SELECT taxonID
                        FROM tbl_tax_species
                        WHERE genID = '$genID'
                         AND speciesID IS NULL
                         AND subspeciesID IS NULL
                         AND varietyID IS NULL
                         AND subvarietyID IS NULL
                         AND formaID IS NULL
                         AND subformaID IS NULL";
                $result = dbi_query($sql);
                if (mysqli_num_rows($result) > 0) {
                    $row = mysqli_fetch_array($result);
                    $taxonOK = true;
                    $data[$i]['taxonID'] = $row['taxonID'];
                    //break;
                }
            }
        }
//        error_log("check");

       /**
        * obtain suggestions for a taxon name by TaxaMatch
        */
        if (!$taxonOK) {
//            if(!$_OPTIONS['staging_area']){
//              // when using the staging area it is ok if the taxon names are not matching
//            }
            $OK = false;
            $status[$i] .= "no_taxa ";
            $data[$i]['taxonID'] = 0;

            $parser = TaxonTokenizer::Load();
            $taxonParts = $parser->tokenize($import[$i][4]);

            $taxamatch[$i] = array();
            $service = new JsonRPCClient_v1($_OPTIONS['serviceTaxamatch']);
            try {
                //getMatchesService('vienna',$searchtext,array('showSyn'=>$showSynonyms,'NearMatch'=>false))
                $matches = $service->getMatchesService('vienna',$taxonParts['genus'] . ' ' . $taxonParts['epithet'] . $ranks[$taxonParts['rank']] . $taxonParts['subepithet'],array('showSyn'=>false,'NearMatch'=>false));
                if (isset($matches['result'][0]['searchresult'])) {
                    foreach ($matches['result'][0]['searchresult'] as $key => $val) {
                        for ($j = 0; $j < count($val['species']); $j++) {
                            $taxamatch[$i][] = $val['species'][$j];
                        }
                    }
                }
            }
            catch (Exception $e) {
                echo "JSON-Fehler " . nl2br($e);
            }
            if (!empty($taxamatch[$i])) {
                $status[$i] .= "similar_taxa ";
            } elseif (!$genusOK) {
                $status[$i] .= "no_genus ";
            }
        }

        /**
         * check the collectors (first and additional)
         */
        $collectorsOK = false;
        $collectorParts = parseImportCollectors($import[$i][5]);
        $collector = $collectorParts['collector'];
        $collector2 = $collectorParts['collector2'];
        $data[$i]['collector_validation_detail'] = false;
        $data[$i]['collector_primary_raw'] = $collector;
        $data[$i]['collector_additional_raw'] = $collector2;
        $data[$i]['collector_primary_found'] = false;
        $data[$i]['collector_additional_found'] = false;
        $data[$i]['collector_additional_checked'] = (strlen($collector2) > 0);
        $data[$i]['SammlerID'] = "";
        $data[$i]['Sammler_2ID'] = "";

        $result = dbi_query("SELECT SammlerID FROM tbl_collector WHERE Sammler = " . quoteString($collector));
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_array($result);
            $data[$i]['collector_primary_found'] = true;
            $data[$i]['SammlerID'] = $row['SammlerID'];
        }

        if (strlen($collector2) > 0) {
            $result = dbi_query("SELECT Sammler_2ID FROM tbl_collector_2 WHERE Sammler_2 = " . quoteString($collector2));
            if (mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_array($result);
                $data[$i]['collector_additional_found'] = true;
                $data[$i]['Sammler_2ID'] = $row['Sammler_2ID'];
            }
        }

        if ($data[$i]['collector_primary_found'] && (strlen($collector2) === 0 || $data[$i]['collector_additional_found'])) {
            $collectorsOK = true;
        }
        if (!$collectorsOK) {
            $OK = false;
            $data[$i]['collector_validation_detail'] = true;
            if (!$data[$i]['collector_primary_found'] && (!$data[$i]['collector_additional_checked'] || !$data[$i]['collector_additional_found'])) {
                $status[$i] .= "no_collector ";
            } else {
                if (!$data[$i]['collector_primary_found']) {
                    $status[$i] .= "no_collector_primary ";
                }
                if ($data[$i]['collector_additional_checked'] && !$data[$i]['collector_additional_found']) {
                    $status[$i] .= "no_collector_additional ";
                }
            }
        }

        /**
         * check if series exists
         */
        if (trim($import[$i][6])) {
          $result = dbi_query("SELECT seriesID FROM tbl_specimens_series WHERE series = " . quoteString(trim($import[$i][6])));
          if (mysqli_num_rows($result) > 0) {
              $row = mysqli_fetch_array($result);
              $data[$i]['seriesID'] = $row['seriesID'];
          } else {
              $OK = false;
              $status[$i] .= "no_series ";
          }
        } else {
            $data[$i]['seriesID'] = "";
        }

        /**
         * fill series_number
         */
        $data[$i]['series_number'] = (!empty($import[$i][7])) ? $import[$i][7] : '';

        /**
         * fill Nummer
         */
        $data[$i]['Nummer'] = (!empty($import[$i][8])) ? $import[$i][8] : '';

        /**
         * fill alt_number
         */
        $data[$i]['alt_number'] = (!empty($import[$i][9])) ? $import[$i][9] : '';

        /**
         * fill Datum
         */
        $data[$i]['Datum'] = (!empty($import[$i][10])) ? $import[$i][10] : '';

        /**
         * fill Datum2
         */
        $data[$i]['Datum2'] = (!empty($import[$i][11])) ? $import[$i][11] : '';

        /**
         * fill det
         */
        $data[$i]['det'] = (!empty($import[$i][12])) ? $import[$i][12] : '';

        /**
         * fill typified
         */
        $data[$i]['typified'] = (!empty($import[$i][13])) ? $import[$i][13] : '';

        /**
         * check if type exists
         */
        if (isset($import[$i][14]) && trim($import[$i][14])) {
            $result = dbi_query("SELECT typusID FROM tbl_typi WHERE typus_lat = " . quoteString(trim($import[$i][14])));
            if (mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_array($result);
                $data[$i]['typusID'] = $row['typusID'];
            } else {
                $OK = false;
                $status[$i] .= "no_type ";
            }
        } else {
            $data[$i]['typusID'] = "";
        }

        /**
         * fill taxon_alt
         */
        $data[$i]['taxon_alt'] = (!empty($import[$i][15])) ? $import[$i][15] : '';

        /**
         * check if nation exists
         */
        if (isset($import[$i][16]) && trim($import[$i][16])) {
            $result = dbi_query("SELECT nationID FROM tbl_geo_nation WHERE nation_engl = " . quoteString(trim($import[$i][16])));
            if (mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_array($result);
                $data[$i]['NationID'] = $row['nationID'];
            } else {
                $OK = false;
                $status[$i] .= "no_nation ";
                $data[$i]['NationID'] = "";
            }
        } else {
            $data[$i]['NationID'] = "";
        }

        /**
         * check if province exists
         */
        if (isset($import[$i][17]) && trim($import[$i][17])) {
            $sql = "SELECT provinceID
                    FROM tbl_geo_province
                    WHERE provinz = " . quoteString(trim($import[$i][17])) . "
                     AND nationID = '" . intval($data[$i]['NationID']) . "'";
            $result = dbi_query($sql);
            if (mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_array($result);
                $data[$i]['provinceID'] = $row['provinceID'];
            } else {
                $OK = false;
                $status[$i] .= "no_province ";
            }
        } else {
            $data[$i]['provinceID'] = "";
        }

        /**
         * fill Fundort
         */
        $data[$i]['Fundort'] = (!empty($import[$i][18])) ? $import[$i][18] : '';
        $data[$i]['Fundort_engl'] = (!empty($import[$i][19])) ? $import[$i][19] : '';

        /**
         * fill habitat & habitus
         */
        $data[$i]['Habitat'] = (!empty($import[$i][20])) ? $import[$i][20] : '';
        $data[$i]['Habitus'] = (!empty($import[$i][21])) ? $import[$i][21] : '';

        /**
         * fill Bemerkungen
         */
        $data[$i]['Bemerkungen'] = (!empty($import[$i][22])) ? $import[$i][22] : '';

        /**
         * fill geografical coordinates
         */
        if (isset($import[$i][23]) && isset($import[$i][24]) && isset($import[$i][25]) && isset($import[$i][26])) {
            if (!empty($import[$i][26]) && !is_numeric(strtr($import[$i][26], ",", "."))) {
                $OK = false;
                $status[$i] .= "no_numeric_sec_lat ";
            }
            $data[$i]['Coord_N'] = ($import[$i][23]=='N') ? (($import[$i][24] != "") ? intval($import[$i][24]) : "") : "";  // integers only
            $data[$i]['N_Min']   = ($import[$i][23]=='N') ? (($import[$i][25] != "") ? intval($import[$i][25]) : "") : "";  // integers only
            $data[$i]['N_Sec']   = ($import[$i][23]=='N') ? strtr($import[$i][26], ",", ".") : "";
            $data[$i]['Coord_S'] = ($import[$i][23]=='S') ? (($import[$i][24] != "") ? intval($import[$i][24]) : "") : "";  // integers only
            $data[$i]['S_Min']   = ($import[$i][23]=='S') ? (($import[$i][25] != "") ? intval($import[$i][25]) : "") : "";  // integers only
            $data[$i]['S_Sec']   = ($import[$i][23]=='S') ? strtr($import[$i][26], ",", ".") : "";
        } else {
            $data[$i]['Coord_N'] = $data[$i]['N_Min'] = $data[$i]['N_Sec'] = $data[$i]['Coord_S'] = $data[$i]['S_Min'] = $data[$i]['S_Sec'] = '';
        }
        if (isset($import[$i][27]) && isset($import[$i][28]) && isset($import[$i][29]) && isset($import[$i][30])) {
            if (!empty($import[$i][30]) && !is_numeric(strtr($import[$i][30], ",", "."))) {
                $OK = false;
                $status[$i] .= "no_numeric_sec_lon ";
            }
            $data[$i]['Coord_W'] = ($import[$i][27]=='W') ? (($import[$i][28] != "") ? intval($import[$i][28]) : "") : "";  // integers only
            $data[$i]['W_Min']   = ($import[$i][27]=='W') ? (($import[$i][29] != "") ? intval($import[$i][29]) : "") : "";  // integers only
            $data[$i]['W_Sec']   = ($import[$i][27]=='W') ? strtr($import[$i][30], ",", ".") : "";
            $data[$i]['Coord_E'] = ($import[$i][27]=='E') ? (($import[$i][28] != "") ? intval($import[$i][28]) : "") : "";  // integers only
            $data[$i]['E_Min']   = ($import[$i][27]=='E') ? (($import[$i][29] != "") ? intval($import[$i][29]) : "") : "";  // integers only
            $data[$i]['E_Sec']   = ($import[$i][27]=='E') ? strtr($import[$i][30], ",", ".") : "";
        } else {
            $data[$i]['Coord_W'] = $data[$i]['W_Min'] = $data[$i]['W_Sec'] = $data[$i]['Coord_E'] = $data[$i]['E_Min'] = $data[$i]['E_Sec'] = '';
        }
        /**
         * Fill in quadrant info
         */
        if (!empty($import[$i][31]) && !is_numeric($import[$i][31])) {
            $OK = false;
            $status[$i] .= "no_numeric_quadrant ";
        }
        $data[$i]['quadrant'] = (isset($import[$i][31])) ? $import[$i][31] : '';
        if (!empty($import[$i][32]) && !is_numeric($import[$i][32])) {
            $OK = false;
            $status[$i] .= "no_numeric_quadrant_sub ";
        }
        $data[$i]['quadrant_sub'] = (isset($import[$i][32])) ? $import[$i][32] : '';
        if (!empty($import[$i][33]) && !is_numeric($import[$i][33])) {
            $OK = false;
            $status[$i] .= "no_numeric_exactness ";
        }
        $data[$i]['exactness'] = (isset($import[$i][33])) ? strtr($import[$i][33], ",", ".") : '';

        /**
         * fill altitude
         */
        $data[$i]['altitude_min'] = (!empty($import[$i][34])) ? intval($import[$i][34]) : '';   // integers only
        $data[$i]['altitude_max'] = (!empty($import[$i][35])) ? intval($import[$i][35]) : '';   // integers only

        /**
         * fill switch: digital image
         */
        $data[$i]['digital_image'] = (!empty($import[$i][36])) ? 1 : 0;

        /**
         * fill switch: digital image obs
         */
        $data[$i]['digital_image_obs'] = (!empty($import[$i][37])) ? 1 : 0;

        /**
         * fill switch: observation
         */
        $data[$i]['observation'] = (!empty($import[$i][38])) ? 1 : 0;

        /**
         * fill notes_internal
         */
        $data[$i]['notes_internal'] = (isset($import[$i][39]) && trim($import[$i][39]) !== '') ? $import[$i][39] : '';

        if ($checkGeographicAssignments) {
            $geoWarnings[$i] = checkImportGeographicAssignment(
                $data[$i]['NationID'],
                isset($data[$i]['provinceID']) ? $data[$i]['provinceID'] : '',
                $data[$i]
            );
        }

        /**
         * finished -> log the file contents and processing errors (if any)
         */
        dbi_query("INSERT INTO tbl_external_import_content SET
                    filename = " . quoteString($inputName) . ",
                    linenumber = " . makeInt($import[$i]['linenumber']) . ",
                    line = " . quoteString(var_export($import[$i], true)) . ",
                    processingError = " . quoteString(trim($status[$i] . ' ' . implode(' ', $geoWarnings[$i]))) . ",
                    userID = " . quoteString($_SESSION['uid']));
        $data[$i]['contentID'] = dbi_insert_id();

        if ($OK) {
            $status[$i] = "OK";
            $importable++;
        }

        if ($status[$i] == "OK") {                              // everything is OK -> data can be imported
            $position[$i] = 0;
        } elseif ($status[$i] == "no_taxa similar_taxa ") {     // taxon not found but similar taxon found
            $position[$i] = 1;
            $importableTaxaPresent = true;
        } elseif ($status[$i] == "no_taxa ") {                  // taxon not found at all, but genus is in database
            $position[$i] = 2;
            $importableTaxaPresent = TRUE;
        } elseif ($_OPTIONS['staging_area']['ignore_no_genus']  // taxon not found and genus unknown, but to be imported
            && $status[$i] == "no_taxa no_genus ") {             // due to staging area option
            $position[$i] = 2;
            $importableTaxaPresent = TRUE;
        } elseif (strpos($status[$i], "exists") !== false) {    // HerbNummer exists already in database
            $position[$i] = 4;
        } else {                                                // nothing of the above, something else went wrong
            $position[$i] = 3;
        }
    }

    $validationExportToken = storeImportValidationExportPayload(
        buildImportValidationExportPayload($import, $status, $taxamatch, $data, $inputName, $geoWarnings)
    );

    if ($importableTaxaPresent) {
        echo "If taxa will be imported use this external-ID: "
           . "<select name='externalID'>\n"
           . "<option value='0'></option>\n";
        $result = dbi_query("SELECT externalID, description FROM tbl_external_import WHERE used = 0 ORDER BY externalID");
        while ($row = mysqli_fetch_array($result)) {
            echo "<option value='" . $row['externalID'] . "'>" . htmlspecialchars($row['description']) . "&nbsp;&lt;" . $row['externalID'] . "&gt;</option>\n";
        }
        echo "</select><br>\n";
    }
    echo "<input type=\"hidden\" name=\"export_token\" value=\"" . htmlspecialchars($validationExportToken, ENT_QUOTES) . "\">\n";
    echo "<input type=\"hidden\" name=\"import_process_token\" value=\"" . htmlspecialchars($_SESSION[IMPORT_SPECIMENS_PROCESS_SESSION_KEY]['token'], ENT_QUOTES, 'UTF-8') . "\">\n";
    echo "<div class=\"summary\">$importable / " . count($import) . " entries are ready to be imported"
       . "<div class=\"action-buttons\"><input type=\"submit\" name=\"import_data\" value=\"Import selected records\" class=\"button-primary\">"
       . "<input type=\"submit\" name=\"export_validation\" value=\"Export errors and hints\"></div></div>\n";
    $ctr = 0;
    for ($block = 0; $block < 5; $block++) {
        switch ($block) {
            case 0: echo "<hr>Ready to be imported:<br>\n"; break;
            case 1: echo "<hr>Taxon not found but similar taxon found: (choose action)<br>\n"; break;
            case 2: echo "<hr>Taxon not found: (choose action)&nbsp;&nbsp;&nbsp;&nbsp;"
                       . "<button onclick=\"$('.importTaxaOn').attr('checked','checked'); return false;\">select all new taxa</button>"
                       . "<br>\n"; break;
            case 3: echo "<hr>One or more errors occurred:<br>\n"; break;
            case 4: echo "<hr>HerbNummer exists already in database:<br>\n"; break;
        }
        echo "<div class=\"import-preview\"><table>\n";
        for ($i = 0; $i < count($import); $i++) {
            if ($block == $position[$i]) {
                if ($status[$i] == "OK") {
                    echo !empty($geoWarnings[$i]) ? "<tr style=\"background-color:#fff3cd\">" : "<tr style=\"background-color:#00FF00\">";
                } elseif (strpos($status[$i], "exists") !== false) {
                    echo "<tr style=\"background-color:#0000FF\">";
                } else {
                    echo "<tr>";
                }

                //echo "<td>" . $status[$i] . "</td>";

                if (strpos($status[$i], "exists") !== false) {
                    echo "<td><a href=\"../editSpecimens.php?sel=" . htmlspecialchars("<" . $exists[$i] . ">") . "\" target=\"Specimens\">" . htmlspecialchars($import[$i][0]) . "</a></td>";
                } else {
                    echo "<td>" . $import[$i][0] . "</td>";
                }
                echo "<td" . ((strpos($status[$i], "no_collection") !== false) ? " style=\"background-color:red\"" : "") . ">" . htmlspecialchars($import[$i][1]) . "</td>";
                echo "<td>" . $import[$i][2] . "</td>";
                echo "<td" . ((strpos($status[$i], "no_identstatus") !== false) ? " style=\"background-color:red\"" : "") . ">" . htmlspecialchars($import[$i][3]) . "</td>";
                if (strpos($status[$i], "similar_taxa") !== false) {
                    echo "<td style=\"background-color:yellow\" title=\"similar taxa found, choose in row below\">";
                } elseif (strpos($status[$i], "no_taxa") !== false) {
                    echo "<td style=\"background-color:red\" title=\"" . $status[$i] . "\">";
                } else {
                    echo "<td>";
                }
                echo htmlspecialchars($import[$i][4]) . "</td>";
                echo "<td" . ((importStatusHasCollectorIssue($status[$i])) ? " style=\"background-color:red\"" : "") . ">" . renderCollectorValidationDisplay($data[$i], $import[$i][5]) . "</td>";
                echo "<td" . ((strpos($status[$i], "no_series") !== false) ? " style=\"background-color:red\"" : "") . ">" . htmlspecialchars($import[$i][6]) . "</td>";
                echo "<td>" . $import[$i][7] . "</td>";
                echo "<td>" . $import[$i][8] . "</td>";
                echo "<td>" . $import[$i][9] . "</td>";
                echo "<td>" . $import[$i][10] . "</td>";
                echo "<td>" . $import[$i][11] . "</td>";
                echo "<td>" . $import[$i][12] . "</td>";
                echo "<td>" . $import[$i][13] . "</td>";
                echo "<td" . ((strpos($status[$i], "no_type") !== false) ? " style=\"background-color:red\"" : "") . ">" . htmlspecialchars($import[$i][14]) . "</td>";
                echo "<td>" . $import[$i][15] . "</td>";
                echo "<td" . ((strpos($status[$i], "no_nation") !== false) ? " style=\"background-color:red\"" : "") . ">" . htmlspecialchars($import[$i][16]) . "</td>";
                echo "<td" . ((strpos($status[$i], "no_province") !== false) ? " style=\"background-color:red\"" : "") . ">" . htmlspecialchars($import[$i][17]) . "</td>";
                echo "<td>" . $import[$i][18] . "</td>";
                echo "<td>" . $import[$i][19] . "</td>";
                echo "<td>" . $import[$i][20] . "</td>";
                echo "<td>" . $import[$i][21] . "</td>";
                echo "<td>" . $import[$i][22] . "</td>";
                echo "<td>" . $import[$i][23] . "</td>";
                echo "<td>" . $import[$i][24] . "</td>";
                echo "<td>" . ($import[$i][25] ?? '') . "</td>";
                echo "<td" . ((strpos($status[$i], "no_numeric_sec_lat") !== false) ? " style=\"background-color:red\"" : "") . ">" . ($import[$i][26] ?? '') . "</td>";
                echo "<td>" . ($import[$i][27] ?? '') . "</td>";
                echo "<td>" . ($import[$i][28] ?? '') . "</td>";
                echo "<td>" . ($import[$i][29] ?? '') . "</td>";
                echo "<td" . ((strpos($status[$i], "no_numeric_sec_lon") !== false) ? " style=\"background-color:red\"" : "") . ">" . ($import[$i][30] ?? '') . "</td>";
                echo "<td" . ((strpos($status[$i], "no_numeric_quadrant") !== false) ? " style=\"background-color:red\"" : "") . ">" . ($import[$i][31] ?? '') . "</td>";
                echo "<td" . ((strpos($status[$i], "no_numeric_quadrant_sub") !== false) ? " style=\"background-color:red\"" : "") . ">" . ($import[$i][32] ?? '') . "</td>";
                echo "<td" . ((strpos($status[$i], "no_numeric_exactness") !== false) ? " style=\"background-color:red\"" : "") . ">" . ($import[$i][33] ?? '') . "</td>";
                echo "<td>" . ($import[$i][34] ?? '') . "</td>";
                echo "<td>" . ($import[$i][35] ?? '') . "</td>";
                echo "<td>" . ($import[$i][36] ?? '') . "</td>";
                echo "<td>" . ($import[$i][37] ?? '') . "</td>";
                echo "<td>" . ($import[$i][38] ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($import[$i][39] ?? '') . "</td>";
                echo "</tr>\n";

                if (!empty($geoWarnings[$i])) {
                    $warningText = array();
                    foreach ($geoWarnings[$i] as $warning) {
                        $warningText[] = formatImportGeographicWarning($warning);
                    }
                    $coordinates = formatImportCoordinatesForValidation($data[$i]);
                    echo "<tr><td colspan=\"40\" style=\"background-color:#fff3cd\"><strong>Geographic assignment warning:</strong> "
                       . htmlspecialchars(implode(' ', $warningText))
                       . (($coordinates !== '') ? ' Coordinates: ' . htmlspecialchars($coordinates) . '.' : '')
                       . "</td></tr>\n";
                }

                if (strpos($status[$i], "similar_taxa") !== false) {
                    echo "<tr><td></td><td></td><td></td><td colspan=\"28\" style=\"background-color:yellow\">";
                    if ($block == 1) {
                        echo "<input type='hidden' name='similarTaxa_{$ctr}_orig' value='" . htmlspecialchars($import[$i][4]) . "' />";
                        echo "<input type=\"radio\" name=\"similarTaxa_$ctr\" value=\"0\" checked>no import<br>";
                        foreach ($taxamatch[$i] as $val) {
                            $taxonWarning = getImportTaxaMatchWarning($val['taxonID'] ?? 0, $val['taxon'] ?? '');
                            echo "<input type=\"radio\" name=\"similarTaxa_$ctr\" value=\"" . htmlspecialchars($val['taxonID']) . "\">"
                               . $val['taxon']
                               . ($taxonWarning !== '' ? ' <strong>Warning:</strong> ' . htmlspecialchars($taxonWarning, ENT_QUOTES, 'UTF-8') : '') . "<br>";
                        }
                        if (strpos($status[$i], "no_genus") === false) {
                            echo "<input type=\"radio\" name=\"similarTaxa_$ctr\" value=\"ID" . htmlspecialchars($import[$i][4]) . "\">Import \""
                               . htmlspecialchars($import[$i][4])
                               . "\" first<br>";
                        }
                    } else {
                        foreach ($taxamatch[$i] as $val) {
                            $taxonWarning = getImportTaxaMatchWarning($val['taxonID'] ?? 0, $val['taxon'] ?? '');
                            echo $val['taxon']
                               . ($taxonWarning !== '' ? ' <strong>Warning:</strong> ' . htmlspecialchars($taxonWarning, ENT_QUOTES, 'UTF-8') : '') . "<br>";
                        }
                    }
                    echo "</td></tr>\n";
                } elseif ($block == 2) {
                    echo "<tr><td></td><td></td><td></td><td colspan=\"28\" style=\"background-color:red\">"
                       . "<input type=\"radio\" name=\"importTaxa_$ctr\" value=\"0\" checked>no import<br>"
                       . "<input type=\"radio\" name=\"importTaxa_$ctr\" class=\"importTaxaOn\" value=\"" . htmlspecialchars($import[$i][4]) . "\">Import \""
                       . htmlspecialchars($import[$i][4])
                       . "\" first</td></tr>\n";
                }
                if ($block < 3) {
                    echo "<input type=\"hidden\" name=\"HerbNummer_$ctr\" value=\""    . htmlspecialchars($data[$i]['HerbNummer']) . "\">"
                       . "<input type=\"hidden\" name=\"collectionID_$ctr\" value=\""  . htmlspecialchars($data[$i]['collectionID']) . "\">"
                       . "<input type=\"hidden\" name=\"CollNummer_$ctr\" value=\""    . htmlspecialchars($data[$i]['CollNummer']) . "\">"
                       . "<input type=\"hidden\" name=\"identstatusID_$ctr\" value=\"" . htmlspecialchars($data[$i]['identstatusID']) . "\">"
                       . "<input type=\"hidden\" name=\"taxonID_$ctr\" value=\""       . htmlspecialchars($data[$i]['taxonID']) . "\">"
                       . "<input type=\"hidden\" name=\"SammlerID_$ctr\" value=\""     . htmlspecialchars($data[$i]['SammlerID']) . "\">"
                       . "<input type=\"hidden\" name=\"Sammler2ID_$ctr\" value=\""    . htmlspecialchars($data[$i]['Sammler_2ID']) . "\">"
                       . "<input type=\"hidden\" name=\"seriesID_$ctr\" value=\""      . htmlspecialchars($data[$i]['seriesID']) . "\">"
                       . "<input type=\"hidden\" name=\"seriesnumber_$ctr\" value=\""  . htmlspecialchars($data[$i]['series_number']) . "\">"
                       . "<input type=\"hidden\" name=\"Nummer_$ctr\" value=\""        . htmlspecialchars($data[$i]['Nummer']) . "\">"
                       . "<input type=\"hidden\" name=\"altnumber_$ctr\" value=\""     . htmlspecialchars($data[$i]['alt_number']) . "\">"
                       . "<input type=\"hidden\" name=\"Datum_$ctr\" value=\""         . htmlspecialchars($data[$i]['Datum']) . "\">"
                       . "<input type=\"hidden\" name=\"Datum2_$ctr\" value=\""        . htmlspecialchars($data[$i]['Datum2']) . "\">"
                       . "<input type=\"hidden\" name=\"det_$ctr\" value=\""           . htmlspecialchars($data[$i]['det']) . "\">"
                       . "<input type=\"hidden\" name=\"typified_$ctr\" value=\""      . htmlspecialchars($data[$i]['typified']) . "\">"
                       . "<input type=\"hidden\" name=\"typusID_$ctr\" value=\""       . htmlspecialchars($data[$i]['typusID']) . "\">"
                       . "<input type=\"hidden\" name=\"taxonalt_$ctr\" value=\""      . htmlspecialchars($data[$i]['taxon_alt']) . "\">"
                       . "<input type=\"hidden\" name=\"NationID_$ctr\" value=\""      . htmlspecialchars($data[$i]['NationID']) . "\">"
                       . "<input type=\"hidden\" name=\"provinceID_$ctr\" value=\""    . htmlspecialchars($data[$i]['provinceID']) . "\">"
                       . "<input type=\"hidden\" name=\"Fundort_$ctr\" value=\""       . htmlspecialchars($data[$i]['Fundort']) . "\">"
                       . "<input type=\"hidden\" name=\"Fundortengl_$ctr\" value=\""   . htmlspecialchars($data[$i]['Fundort_engl']) . "\">"
                       . "<input type=\"hidden\" name=\"Habitat_$ctr\" value=\""       . htmlspecialchars($data[$i]['Habitat']) . "\">"
                       . "<input type=\"hidden\" name=\"Habitus_$ctr\" value=\""       . htmlspecialchars($data[$i]['Habitus']) . "\">"
                       . "<input type=\"hidden\" name=\"Bemerkungen_$ctr\" value=\""   . htmlspecialchars($data[$i]['Bemerkungen']) . "\">"
                       . "<input type=\"hidden\" name=\"CoordW_$ctr\" value=\""        . htmlspecialchars($data[$i]['Coord_W']) . "\">"
                       . "<input type=\"hidden\" name=\"WMin_$ctr\" value=\""          . htmlspecialchars($data[$i]['W_Min']) . "\">"
                       . "<input type=\"hidden\" name=\"WSec_$ctr\" value=\""          . htmlspecialchars($data[$i]['W_Sec']) . "\">"
                       . "<input type=\"hidden\" name=\"CoordN_$ctr\" value=\""        . htmlspecialchars($data[$i]['Coord_N']) . "\">"
                       . "<input type=\"hidden\" name=\"NMin_$ctr\" value=\""          . htmlspecialchars($data[$i]['N_Min']) . "\">"
                       . "<input type=\"hidden\" name=\"NSec_$ctr\" value=\""          . htmlspecialchars($data[$i]['N_Sec']) . "\">"
                       . "<input type=\"hidden\" name=\"CoordS_$ctr\" value=\""        . htmlspecialchars($data[$i]['Coord_S']) . "\">"
                       . "<input type=\"hidden\" name=\"SMin_$ctr\" value=\""          . htmlspecialchars($data[$i]['S_Min']) . "\">"
                       . "<input type=\"hidden\" name=\"SSec_$ctr\" value=\""          . htmlspecialchars($data[$i]['S_Sec']) . "\">"
                       . "<input type=\"hidden\" name=\"CoordE_$ctr\" value=\""        . htmlspecialchars($data[$i]['Coord_E']) . "\">"
                       . "<input type=\"hidden\" name=\"EMin_$ctr\" value=\""          . htmlspecialchars($data[$i]['E_Min']) . "\">"
                       . "<input type=\"hidden\" name=\"ESec_$ctr\" value=\""          . htmlspecialchars($data[$i]['E_Sec']) . "\">"
                       . "<input type=\"hidden\" name=\"exactness_$ctr\" value=\""     . htmlspecialchars($data[$i]['exactness']) . "\">"
                       . "<input type=\"hidden\" name=\"quadrant_$ctr\" value=\""      . htmlspecialchars($data[$i]['quadrant']) . "\">"
                       . "<input type=\"hidden\" name=\"quadrantsub_$ctr\" value=\""   . htmlspecialchars($data[$i]['quadrant_sub']) . "\">"
                       . "<input type=\"hidden\" name=\"altitudemin_$ctr\" value=\""   . htmlspecialchars($data[$i]['altitude_min']) . "\">"
                       . "<input type=\"hidden\" name=\"altitudemax_$ctr\" value=\""   . htmlspecialchars($data[$i]['altitude_max']) . "\">"
                       . "<input type=\"hidden\" name=\"digitalimage_$ctr\" value=\""  . htmlspecialchars($data[$i]['digital_image']) . "\">"
                       . "<input type=\"hidden\" name=\"digitalimageobs_$ctr\" value=\""  . htmlspecialchars($data[$i]['digital_image_obs']) . "\">"
                       . "<input type=\"hidden\" name=\"observation_$ctr\" value=\""   . htmlspecialchars($data[$i]['observation']) . "\">"
                       . "<input type=\"hidden\" name=\"notesinternal_$ctr\" value=\"" . htmlspecialchars($data[$i]['notes_internal']) . "\">"
                       . "<input type=\"hidden\" name=\"contentid_$ctr\" value=\""     . htmlspecialchars($data[$i]['contentID']) . "\">"
                       . "<input type=\"hidden\" name=\"importline_$ctr\" value=\""     . intval($import[$i]['linenumber']) . "\">"
                       . "<input type=\"hidden\" name=\"position_$ctr\" value=\""      . htmlspecialchars($position[$i]) . "\">\n";

                    $ctr++;
                } else {
                    $excludedIndex = intval($i);
                    echo "<input type=\"hidden\" name=\"excludedRows[$excludedIndex][herbNumber]\" value=\"" . htmlspecialchars($import[$i][0], ENT_QUOTES, 'UTF-8') . "\">"
                       . "<input type=\"hidden\" name=\"excludedRows[$excludedIndex][line]\" value=\"" . intval($import[$i]['linenumber']) . "\">"
                       . "<input type=\"hidden\" name=\"excludedRows[$excludedIndex][reason]\" value=\"" . htmlspecialchars(trim($status[$i]), ENT_QUOTES, 'UTF-8') . "\">\n";
                }
            }
        }
        echo "</table></div>\n";
    }
    }

    if ($inputCleanupNeeded && $inputPath && file_exists($inputPath)) {
        @unlink($inputPath);
    }
} elseif ($run == 3 && !isCurrentImportProcessToken($_POST['import_process_token'] ?? '')) {
    echo '<div class="error">The import preview is no longer current. Please upload and check the file again.</div>';
    renderImportInputForm();
} elseif ($run == 3) {  // insert data
    $data = array();
    foreach ($_POST as $k => $v) {
        $pieces = explode('_', $k);
        if ($pieces[0] == 'position') {
            $data[intval($pieces[1])]['position'] = $v;
        } elseif ($pieces[0] == 'similarTaxa') {
            if (substr($v, 0, 2) == 'ID') {
                $data[intval($pieces[1])]['importTaxa'] = substr($v, 2);
            } else {
                $data[intval($pieces[1])]['similarID'] = $v;
            }
        } elseif ($pieces[0] == 'Sammler2ID') {
            $data[intval($pieces[1])]['Sammler_2ID'] = $v;
        } elseif ($pieces[0] == 'seriesnumber') {
            $data[intval($pieces[1])]['series_number'] = $v;
        } elseif ($pieces[0] == 'altnumber') {
            $data[intval($pieces[1])]['alt_number'] = $v;
        } elseif ($pieces[0] == 'taxonalt') {
            $data[intval($pieces[1])]['taxon_alt'] = $v;
        } elseif ($pieces[0] == 'CoordN') {
            $data[intval($pieces[1])]['Coord_N'] = $v;
        } elseif ($pieces[0] == 'NMin') {
            $data[intval($pieces[1])]['N_Min'] = $v;
        } elseif ($pieces[0] == 'NSec') {
            $data[intval($pieces[1])]['N_Sec'] = $v;
        } elseif ($pieces[0] == 'CoordS') {
            $data[intval($pieces[1])]['Coord_S'] = $v;
        } elseif ($pieces[0] == 'SMin') {
            $data[intval($pieces[1])]['S_Min'] = $v;
        } elseif ($pieces[0] == 'SSec') {
            $data[intval($pieces[1])]['S_Sec'] = $v;
        } elseif ($pieces[0] == 'CoordW') {
            $data[intval($pieces[1])]['Coord_W'] = $v;
        } elseif ($pieces[0] == 'WMin') {
            $data[intval($pieces[1])]['W_Min'] = $v;
        } elseif ($pieces[0] == 'WSec') {
            $data[intval($pieces[1])]['W_Sec'] = $v;
        } elseif ($pieces[0] == 'CoordE') {
            $data[intval($pieces[1])]['Coord_E'] = $v;
        } elseif ($pieces[0] == 'EMin') {
            $data[intval($pieces[1])]['E_Min'] = $v;
        } elseif ($pieces[0] == 'ESec') {
            $data[intval($pieces[1])]['E_Sec'] = $v;
        } elseif ($pieces[0] == 'altitudemin') {
            $data[intval($pieces[1])]['altitude_min'] = $v;
        } elseif ($pieces[0] == 'altitudemax') {
            $data[intval($pieces[1])]['altitude_max'] = $v;
        } elseif ($pieces[0] == 'digitalimage') {
            $data[intval($pieces[1])]['digital_image'] = $v;
        } elseif ($pieces[0] == 'digitalimageobs') {
            $data[intval($pieces[1])]['digital_image_obs'] = $v;
        } elseif ($pieces[0] == 'observation') {
            $data[intval($pieces[1])]['observation'] = $v;
        } elseif ($pieces[0] == 'notesinternal') {
            $data[intval($pieces[1])]['notes_internal'] = $v;
        } elseif ($pieces[0] == 'Fundortengl') {
            $data[intval($pieces[1])]['Fundort_engl'] = $v;
        } elseif ($pieces[0] == 'quadrantsub') {
            $data[intval($pieces[1])]['quadrant_sub'] = $v;
        } elseif ($pieces[0] == 'importline') {
            $data[intval($pieces[1])]['importLine'] = intval($v);
        } elseif (    $pieces[0] == 'HerbNummer'    || $pieces[0] == 'seriesID'   || $pieces[0] == 'taxonID' || $pieces[0] == 'SammlerID'
                   || $pieces[0] == 'CollNummer'
                   || $pieces[0] == 'collectionID'  || $pieces[0] == 'Nummer'     || $pieces[0] == 'Datum'   || $pieces[0] == 'Datum2'
                   || $pieces[0] == 'Bemerkungen'   || $pieces[0] == 'typified'   || $pieces[0] == 'typusID' || $pieces[0] == 'NationID'
                   || $pieces[0] == 'provinceID'    || $pieces[0] == 'Fundort'    || $pieces[0] == 'det'
                   || $pieces[0] == 'exactness'     || $pieces[0] == 'Habitat'    || $pieces[0] == 'Habitus' || $pieces[0] == 'quadrant'
                   || $pieces[0] == 'identstatusID' || $pieces[0] == 'importTaxa' || $pieces[0] == 'contentid') {
            $data[intval($pieces[1])][$pieces[0]] = $v;
        }
    }

    // the real database-insert happens down below to show what the DB returns

    $autocomplete = Autocomplete::Load();
    $outcomes = array('imported' => array(), 'not_selected' => array(), 'already_present' => array(), 'duplicates' => array(), 'errors' => array(), 'excluded' => array());
    if (!empty($_POST['duplicateRows']) && is_array($_POST['duplicateRows'])) {
        foreach ($_POST['duplicateRows'] as $duplicate) {
            if (is_array($duplicate)) {
                $outcomes['duplicates'][] = array('herbNumber' => $duplicate['herbNumber'] ?? '', 'line' => intval($duplicate['line'] ?? 0), 'reason' => 'Identical to line ' . intval($duplicate['keptLine'] ?? 0) . '; processed only once.');
            }
        }
    }
    if (!empty($_POST['excludedRows']) && is_array($_POST['excludedRows'])) {
        foreach ($_POST['excludedRows'] as $excluded) {
            if (is_array($excluded)) {
                $outcomes['excluded'][] = array('herbNumber' => $excluded['herbNumber'] ?? '', 'line' => intval($excluded['line'] ?? 0), 'reason' => $excluded['reason'] ?? '');
            }
        }
    }
    foreach ($data as $i => $row) {
        $outcome = array('herbNumber' => $row['HerbNummer'] ?? '', 'line' => $row['importLine'] ?? 0, 'reason' => '');
        if (   $data[$i]['position'] == 0
            || ($data[$i]['position'] == 1 && !empty($data[$i]['similarID']))
            || (!empty($data[$i]['importTaxa']) && ($data[$i]['position'] == 1 || $data[$i]['position'] == 2))) {

          if ($data[$i]['position'] == 1 && empty($data[$i]['importTaxa'])) {
                $data[$i]['taxonID'] = $data[$i]['similarID'];
            } elseif (!empty($data[$i]['importTaxa']) && ($data[$i]['position'] == 1 || $data[$i]['position'] == 2)) {
                // this taxon was not yet in the db on CheckImport
                // check again if taxon name is not existing in database
                $results = $autocomplete->taxonWithHybrids(array('exact' => $data[$i]['importTaxa']));
                if(count($results) > 1){
                  $outcome['reason'] = count($results) . ' identical entries of the taxon name found: ' . $data[$i]['importTaxa'];
                  $outcomes['errors'][] = $outcome;
                  continue;
                } else
                if(count($results) == 0) {
                  $result = insertTaxon($data[$i]['importTaxa'], $_POST['externalID'], $data[$i]['contentid'], $_OPTIONS['staging_area']['ignore_no_genus']);
                  if (!$result['error']) {
                    $data[$i]['taxonID'] = $result['taxonID'];
                  }
                  else {
                    $outcome['reason'] = $data[$i]['importTaxa'] . ': ' . $result['error'];
                    $outcomes['errors'][] = $outcome;
                    continue;  // abort the insertion of this taxon and the specimen because something went very wrong
                  }
                } else {
                  $data[$i]['taxonID'] = $results[0]['id'];
                }
            }
            $sql = "SELECT specimen_ID FROM tbl_specimens_import WHERE 1 = 1";
            foreach ($data[$i] as $k => $v) {
                if ($k != 'position' && $k != 'similarID' && $k != 'importTaxa' && $k != 'contentid' && $k != 'importLine') {
                    if (strlen($v) > 0) {
                        $sql .= " AND $k = " . quoteString($v);
                    } else {
                        $sql .= " AND $k IS NULL";
                    }
                }
            }
            $result = dbi_query($sql);
            if (mysqli_num_rows($result) == 0) {
                $sqlInsert = "INSERT INTO tbl_specimens_import SET
                               HerbNummer = "    . quoteString($data[$i]['HerbNummer'])    . ",
                               collectionID = "  . quoteString($data[$i]['collectionID'])  . ",
                               CollNummer = "    . quoteString($data[$i]['CollNummer'])    . ",
                               identstatusID = " . makeInt($data[$i]['identstatusID']) . ",
                               taxonID = "       . quoteString($data[$i]['taxonID'])       . ",
                               SammlerID = "     . quoteString($data[$i]['SammlerID'])     . ",
                               Sammler_2ID = "   . quoteString($data[$i]['Sammler_2ID'])   . ",
                               seriesID = "      . makeInt($data[$i]['seriesID'])      . ",
                               series_number = " . quoteString($data[$i]['series_number']) . ",
                               Nummer = "        . quoteString($data[$i]['Nummer'])        . ",
                               alt_number = "    . quoteString($data[$i]['alt_number'])    . ",
                               Datum = "         . quoteString($data[$i]['Datum'])         . ",
                               Datum2 = "        . quoteString($data[$i]['Datum2'])        . ",
                               det = "           . quoteString($data[$i]['det'])           . ",
                               typified = "      . quoteString($data[$i]['typified'])      . ",
                               typusID = "       . makeInt($data[$i]['typusID'])       . ",
                               taxon_alt = "     . quoteString($data[$i]['taxon_alt'])     . ",
                               NationID = "      . makeInt($data[$i]['NationID'])      . ",
                               provinceID = "    . makeInt($data[$i]['provinceID'])    . ",
                               Fundort = "       . quoteString($data[$i]['Fundort'])       . ",
                               Fundort_engl = "  . quoteString($data[$i]['Fundort_engl'])  . ",
                               Habitat = "       . quoteString($data[$i]['Habitat'])       . ",
                               Habitus = "       . quoteString($data[$i]['Habitus'])       . ",
                               Bemerkungen = "   . quoteString($data[$i]['Bemerkungen'])   . ",
                               Coord_W = "       . quoteString($data[$i]['Coord_W'])       . ",
                               W_Min = "         . quoteString($data[$i]['W_Min'])         . ",
                               W_Sec = "         . quoteString($data[$i]['W_Sec'])         . ",
                               Coord_N = "       . quoteString($data[$i]['Coord_N'])       . ",
                               N_Min = "         . quoteString($data[$i]['N_Min'])         . ",
                               N_Sec = "         . quoteString($data[$i]['N_Sec'])         . ",
                               Coord_S = "       . quoteString($data[$i]['Coord_S'])       . ",
                               S_Min = "         . quoteString($data[$i]['S_Min'])         . ",
                               S_Sec = "         . quoteString($data[$i]['S_Sec'])         . ",
                               Coord_E = "       . quoteString($data[$i]['Coord_E'])       . ",
                               E_Min = "         . quoteString($data[$i]['E_Min'])         . ",
                               E_Sec = "         . quoteString($data[$i]['E_Sec'])         . ",
                               exactness = "     . quoteString($data[$i]['exactness'])     . ",
                               quadrant = "      . quoteString($data[$i]['quadrant'])      . ",
                               quadrant_sub = "  . quoteString($data[$i]['quadrant_sub'])  . ",
                               altitude_min = "  . quoteString($data[$i]['altitude_min'])  . ",
                               altitude_max = "  . quoteString($data[$i]['altitude_max'])  . ",
                               digital_image = " . quoteString($data[$i]['digital_image']) . ",
                               digital_image_obs = " . quoteString($data[$i]['digital_image_obs']) . ",
                               observation = "   . quoteString($data[$i]['observation'])   . ",
                               notes_internal = " . ((strlen($data[$i]['notes_internal']) > 0) ? quoteString($data[$i]['notes_internal']) : "NULL") . ",
                               userID = "        . quoteString($_SESSION['uid']);
                dbi_query($sqlInsert);
                $specimen_ID = dbi_insert_id();
                dbi_query("UPDATE tbl_external_import_content SET
                            specimen_ID = $specimen_ID,
                            pending = 1
                           WHERE contentID = " . makeInt($data[$i]['contentid']));
                $outcomes['imported'][] = $outcome;
            } else {
                $outcomes['already_present'][] = $outcome;
            }
        } else {
            $outcome['reason'] = 'No taxon action selected.';
            $outcomes['not_selected'][] = $outcome;
        }
    }
    $outcomeLabels = array(
        'imported' => 'Imported into the specimen import area',
        'not_selected' => 'Not selected for import',
        'already_present' => 'Already present in the specimen import area',
        'duplicates' => 'Identical duplicate rows omitted',
        'excluded' => 'Excluded during validation',
        'errors' => 'Import errors',
    );
    $processContext = $_SESSION[IMPORT_SPECIMENS_PROCESS_SESSION_KEY];
    $processContext['report_content'] = buildImportResultsReport($outcomes, $outcomeLabels, $processContext);
    $_SESSION[IMPORT_SPECIMENS_PROCESS_SESSION_KEY] = $processContext;
    $totalOutcomes = array_sum(array_map('count', $outcomes));
    echo '<div class="summary">' . $totalOutcomes . ' file row(s) accounted for. ';
    foreach ($outcomeLabels as $key => $label) {
        echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . ': <strong>' . count($outcomes[$key]) . '</strong>. ';
    }
    echo "</div>\n";
    echo '<div class="action-buttons"><input type="hidden" name="import_process_token" value="'
       . htmlspecialchars($processContext['token'], ENT_QUOTES, 'UTF-8') . '">'
       . '<button type="submit" name="archive_import_process" value="1">Download import archive</button>'
       . '<a href="' . htmlspecialchars($_SERVER['SCRIPT_NAME'], ENT_QUOTES, 'UTF-8') . '">Start new import process</a></div>';
    foreach ($outcomeLabels as $key => $label) {
        if (empty($outcomes[$key])) {
            continue;
        }
        echo '<div class="panel' . ($key === 'errors' ? ' error' : '') . '"><div class="panel-heading">'
           . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . ' (' . count($outcomes[$key]) . ')</div><ul>';
        foreach ($outcomes[$key] as $item) {
            $herbNumber = trim((string)$item['herbNumber']);
            echo '<li>' . (($item['line'] > 0) ? 'Line ' . intval($item['line']) . ': ' : '')
               . (($herbNumber !== '') ? htmlspecialchars($herbNumber, ENT_QUOTES, 'UTF-8') : '(no HerbNummer)')
               . (($item['reason'] !== '') ? ' — ' . htmlspecialchars((string)$item['reason'], ENT_QUOTES, 'UTF-8') : '') . '</li>';
        }
        echo "</ul></div>\n";
    }
} else {    // show upload form
    if ($pageNotice !== '') {
        echo '<div class="notice">' . htmlspecialchars($pageNotice) . "</div>\n";
    }
    renderImportInputForm($downloadUrl, $checkGeographicAssignments);
}

?></form>
</div>

</body>
</html>
