<?php
session_start();
ini_set("memory_limit","1G");

require_once __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;
use Jacq\DbAccess;

if (!isset($_SESSION['username']) || !isset($_SESSION['password'])) {
    die("Login required");
}

$dbLnk = DbAccess::ConnectTo('INPUT');
$client = new Client(['timeout' => 2]);

function formatLink($specimenID): string
{
    return  "<a href=\"javascript:editSpecimens('<$specimenID>')\">$specimenID</a>: ";
}

function checkBoundingBox($lat, $lon, $boundaries)
{
    foreach ($boundaries as $boundary) {
        if ($lat >= $boundary['bound_south'] && $lat <= $boundary['bound_north']
            && (($boundary['bound_east'] > $boundary['bound_west'] && ($lon >= $boundary['bound_west'] && $lon <= $boundary['bound_east']))
                || ($boundary['bound_east'] < $boundary['bound_west'] && ($lon <= $boundary['bound_west'] && $lon >= $boundary['bound_east'])))) {
            return true;
        }
    }
    return false;
}
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
    "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
    <title>herbardb - check Coordinates</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <script type="text/javascript" language="JavaScript">
        function editSpecimens(sel) {
            options = "width=";
            if (screen.availWidth<1380)
                options += (screen.availWidth - 10) + ",height=";
            else
                options += "1380, height=";
            if (screen.availHeight<710)
                options += (screen.availHeight - 10);
            else
                options += "710";
            options += ", top=10,left=10,scrollbars=yes,resizable=yes";

            target = "editSpecimens.php?sel=" + encodeURIComponent(sel);
            newWindow = window.open(target, "Specimens", options);
            newWindow.focus();
        }
    </script>
</head>

<body>

<?php
$answers = array();

$continue = true;
$offset = 0;
while ($continue) {
    $specimens = $dbLnk->query("SELECT s.specimen_ID, s.NationID,
                             s.Coord_N, s.N_Min, s.N_Sec, s.Coord_S, s.S_Min, s.S_Sec,
                             s.Coord_W, s.W_Min, s.W_Sec, s.Coord_E, s.E_Min, s.E_Sec,
                             gng.name, gng.code,
                             gn.nation_engl
                            FROM tbl_specimens s
                             LEFT JOIN tbl_geo_nation_geonames gng on s.NationID = gng.nationID
                             LEFT JOIN tbl_geo_nation gn on s.NationID = gn.nationID
                            WHERE (s.Coord_N > 0 OR s.Coord_S > 0)
                             AND  (s.Coord_E > 0 OR s.Coord_W > 0)
                            ORDER BY s.specimen_ID
                            LIMIT $offset,100000")
                       ->fetch_all(MYSQLI_ASSOC);
    if (empty($specimens)) {
        $continue = false;
    }
    foreach ($specimens as $specimen) {
        if (!empty($specimen['NationID'])) {
            if (empty($specimen['nation_engl'])) {
                $answers['unknown'][] = formatLink($specimen['specimen_ID']) . "nationID {$specimen['NationID']} unknown<br>\n";
            } else {
                if ($specimen['Coord_N']) {
                    $lat  = $specimen['Coord_N'] + $specimen['N_Min'] / 60.0 + $specimen['N_Sec'] / 3600.0;
                    $latX = $specimen['N_Min'] + $specimen['Coord_N'] / 60.0 + $specimen['N_Sec'] / 3600.0;
                } else {
                    $lat  = -1.0 * ($specimen['Coord_S'] + $specimen['S_Min'] / 60.0 + $specimen['S_Sec'] / 3600.0);
                    $latX = -1.0 * ($specimen['S_Min'] + $specimen['Coord_S'] / 60.0 + $specimen['S_Sec'] / 3600.0);
                }
                if ($specimen['Coord_E']) {
                    $lon  = $specimen['Coord_E'] + $specimen['E_Min'] / 60.0 + $specimen['E_Sec'] / 3600.0;
                    $lonX = $specimen['E_Min'] + $specimen['Coord_E'] / 60.0 + $specimen['E_Sec'] / 3600.0;
                } else {
                    $lon  = -1.0 * ($specimen['Coord_W'] + $specimen['W_Min'] / 60.0 + $specimen['W_Sec'] / 3600.0);
                    $lonX = -1.0 * ($specimen['W_Min'] + $specimen['Coord_W'] / 60.0 + $specimen['W_Sec'] / 3600.0);
                }
                if (empty($boundaries[$specimen['NationID']])) {
                    $boundaries[$specimen['NationID']] = $dbLnk->query("SELECT * FROM tbl_geo_nation_geonames_boundaries WHERE nationID = {$specimen['NationID']}")
                                                               ->fetch_all(MYSQLI_ASSOC);
                }
                $askService = false;
                if (empty($specimen['name'])) {
                    $askService = true;
                } else {
                    $askService = true;
                    if (checkBoundingBox($lat, $lon, $boundaries[$specimen['NationID']])) {
                        $askService = false;
                        $askService = false;
                    } elseif (checkBoundingBox($lon, $lat, $boundaries[$specimen['NationID']])) {
                        $askService = false;
                        $answers['lat_X_lon'][] = formatLink($specimen['specimen_ID']) . "exchanged Latitute with Longitude<br>\n";
                    } elseif (checkBoundingBox($lat, $lonX, $boundaries[$specimen['NationID']])) {
                        $askService = false;
                        $answers['lat_lonX'][] = formatLink($specimen['specimen_ID']) . "exchanged Degrees with Minutes in Longitude<br>\n";
                    } elseif (checkBoundingBox($latX, $lon, $boundaries[$specimen['NationID']])) {
                        $askService = false;
                        $answers['latX_lon'][] = formatLink($specimen['specimen_ID']) . "exchanged Degrees with Minutes in Latitude<br>\n";
                    } elseif (checkBoundingBox($latX, $lonX, $boundaries[$specimen['NationID']])) {
                        $askService = false;
                        $answers['latX_lonX'][] = formatLink($specimen['specimen_ID']) . "exchanged Degrees with Minutes in Latitude and Longitude<br>\n";
                    } elseif (checkBoundingBox(-1 * $lat, $lon, $boundaries[$specimen['NationID']])) {
                        $askService = false;
                        $answers['-lat_lon'][] = formatLink($specimen['specimen_ID']) . "exchanged North with South<br>\n";
                    } elseif (checkBoundingBox($lat, -1 * $lon, $boundaries[$specimen['NationID']])) {
                        $askService = false;
                        $answers['lat_-lon'][] = formatLink($specimen['specimen_ID']) . "exchanged East with West<br>\n";
                    } elseif (checkBoundingBox(-1 * $lat, -1 * $lon, $boundaries[$specimen['NationID']])) {
                        $askService = false;
                        $answers['-lat_-lon'][] = formatLink($specimen['specimen_ID']) . "exchanged North with South and East with West<br>\n";
                    }
                }
                if ($askService) {
                    $answers['askService'][$specimen['NationID']][] = formatLink($specimen['specimen_ID'])
                                                                    . "not in {$specimen['nation_engl']}, has to ask geonames service<br>\n";
//                    try {
//                        $url = "http://api.geonames.org/countrySubdivisionJSON?username=joschach&lat=$lat&lng=$lon&radius=10";
//                        $response = $client->request('POST', $url, [
//                            'verify' => false
//                        ]);
//                        $data = json_decode($response->getBody()->getContents(), true, 512, JSON_INVALID_UTF8_SUBSTITUTE);
////                        echo "<pre>" . var_export($data, true) . "\n</pre>";
////                        exit();
//                        if (empty($data['countryCode'])) {
//                            $answers['noCountry'][] = formatLink($specimen['specimen_ID']) . "not in {$specimen['nation_engl']} $url<br>\n";
//                        } else if ($data['countryCode'] != $specimen['code']) {
//                            $answers['otherCountry'][] = formatLink($specimen['specimen_ID']) . "not in {$specimen['nation_engl']} but in "
//                                . ($data['adminName1'] ?? '??')
//                                . " (" . ($data['countryName'] ?? $data['countryCode']) . ") $url<br>\n";
//                        }
//                    } catch (Exception $e) {
//                        $answers['error'][] = formatLink($specimen['specimen_ID']) . $e->getMessage() . "<br>\n";
//                    }
                }
            }
        }
    }
    $offset += 100000;
}

//$types = array('error', 'unknownNationID', 'noCountry', 'otherCountry');
$types = array('error'      => "have transmission errors",
               'unknown'    => "have unknown Nation-IDs",
               'lat_X_lon'  => "exchanged Latitute with Longitude",
               'lat_lonX'   => "exchanged Degrees with Minutes in Longitude",
               'latX_lon'   => "exchanged Degrees with Minutes in Latitude",
               'latX_lonX'  => "exchanged Degrees with Minutes in Latitude and Longitude",
               '-lat_lon'   => "exchanged North with South",
               'lat_-lon'   => "exchanged East with West",
               '-lat_-lon'  => "exchanged North with South and East with West",
               'askService' => "have to ask geonames service",
              );
foreach ($types as $type => $text) {
    if (!empty($answers[$type])) {
        if ($type != 'askService') {
            echo count($answers[$type]) . " <a href='#$type'>$text</a><br>\n";
        } else {
            $count = 0;
            foreach ($answers[$type] as $answerNation) {
                $count += count($answerNation);
            }
            echo "$count <a href='#$type'>$text</a><br>\n";
        }
    }
}
foreach ($types as $type => $text) {
    if (!empty($answers[$type])) {
        echo "<hr id='$type'>\n";
        if ($type != 'askService') {
            foreach ($answers[$type] as $answer) {
                echo $answer;
            }
        } else {
            foreach ($answers[$type] as $answerNation) {
                foreach ($answerNation as $answer) {
                    echo $answer;
                }
            }
        }
    }
}

?>
</body>
</html>
