<?php

namespace Jacq\Services;

use Jacq\Settings;

class SpecimenImportValidation
{
    public static function isValidFlag($value)
    {
        if ($value !== null && !is_scalar($value)) {
            return false;
        }
        return in_array(trim((string)$value), array('', '0', '1'), true);
    }

    public static function getDecimalCoordinates(array $rowData)
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

    public static function formatCoordinates(array $rowData)
    {
        $coordinates = self::getDecimalCoordinates($rowData);
        if ($coordinates === null) {
            return '';
        }

        return number_format($coordinates['lat'], 6, '.', '') . ', ' . number_format($coordinates['lon'], 6, '.', '');
    }

    public static function checkGeographicAssignment($nationId, $provinceId, array $rowData)
    {
        static $cache = array();
        static $serviceUnavailable = false;

        $coordinates = self::getDecimalCoordinates($rowData);
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
        $baseUrl = Settings::Load()->get('JACQ_SERVICES');
        if (!is_string($baseUrl) || trim($baseUrl) === '') {
            $baseUrl = 'https://services.jacq.org/jacq-services/rest/';
        }
        $curl = curl_init(rtrim(trim($baseUrl), '/') . '/geo/checkBoundaries?' . $query);
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

    public static function formatGeographicWarning($warning)
    {
        $messages = array(
            'coordinates_outside_nation' => 'Coordinates appear to be outside the selected country.',
            'coordinates_outside_province' => 'Coordinates appear to be outside the selected province.',
            'coordinate_check_unavailable' => 'Geographic assignment could not be checked because the boundary service is unavailable.',
        );

        return isset($messages[$warning]) ? $messages[$warning] : $warning;
    }
}
