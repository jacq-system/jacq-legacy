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

$constraintSource = '';
if (!empty($_GET['source'])) {
    if (is_numeric($_GET['source'])) {
        $constraintSource = " AND mc.source_id = " . intval($_GET['source']);
    } else {
        $stmt = $dbLnk->prepare("SELECT source_id
                                 FROM meta
                                 WHERE source_code LIKE ?");
        $stmt->bind_param('s', $_GET['source']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_array(MYSQLI_ASSOC);
        if (!empty($row['source_id'])) {
            $constraintSource = " AND mc.source_id = " . $row['source_id'];
        }
    }
}

function formatTableLine($specimenID, $text)
{
    return "<tr><td><a href=\"javascript:editSpecimens('<$specimenID>')\">$specimenID</a>: </td><td>$text</td></tr>\n";
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
    <style type="text/css">
        .highlight {
            background-color: rgba(255, 233, 89, 0.6);
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"
            integrity="sha256-2Pmvv0kuTBOenSvLm6bvfBSSHrUJ+3A7x6P5Ebd07/g="
            crossorigin="anonymous">
    </script>
    <script type="text/javascript" language="JavaScript">
        $(function () {
            $("a").on("click", function() {
                $(".highlight").removeClass("highlight");
                $(this).parent().addClass("highlight");
            });
        });
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
$correct = $dbLnk->query("SELECT count(correct) as cnt FROM tbl_specimens_geo WHERE correct = 1")
                 ->fetch_assoc()['cnt'];
$sql_correct = "INSERT INTO tbl_specimens_geo (specimen_ID, correct) VALUES ";
$specimens = $dbLnk->query("SELECT s.specimen_ID, s.NationID,
                         s.Coord_N, s.N_Min, s.N_Sec, s.Coord_S, s.S_Min, s.S_Sec,
                         s.Coord_W, s.W_Min, s.W_Sec, s.Coord_E, s.E_Min, s.E_Sec,
                         gng.name, gng.code,
                         gn.nation_engl
                        FROM tbl_specimens s
                         LEFT JOIN tbl_management_collections mc ON s.collectionID = mc.collectionID
                         LEFT JOIN tbl_geo_nation_geonames gng on s.NationID = gng.nationID
                         LEFT JOIN tbl_geo_nation gn on s.NationID = gn.nationID
                        WHERE (s.Coord_N > 0 OR s.Coord_S > 0)
                         AND  (s.Coord_E > 0 OR s.Coord_W > 0)
                         $constraintSource
                         AND s.specimen_ID NOT IN (SELECT specimen_ID FROM tbl_specimens_geo)
                        ORDER BY s.specimen_ID")
                   ->fetch_all(MYSQLI_ASSOC);
foreach ($specimens as $specimen) {
    if (!empty($specimen['NationID'])) {
        if (empty($specimen['nation_engl'])) {
            $answers['unknown'][] = formatTableLine($specimen['specimen_ID'], "nationID {$specimen['NationID']} unknown");
        } else {
            $malformed = false;
            if ($specimen['Coord_N']) {
                $lat  = $specimen['Coord_N'] + $specimen['N_Min'] / 60.0 + $specimen['N_Sec'] / 3600.0;
                $latX = $specimen['N_Min'] + $specimen['Coord_N'] / 60.0 + $specimen['N_Sec'] / 3600.0;
                if ($specimen['Coord_N'] > 359 || $specimen['N_Min'] > 59 || $specimen['N_Sec'] > 60) {
                    $malformed = true;
                }
            } else {
                $lat  = -1.0 * ($specimen['Coord_S'] + $specimen['S_Min'] / 60.0 + $specimen['S_Sec'] / 3600.0);
                $latX = -1.0 * ($specimen['S_Min'] + $specimen['Coord_S'] / 60.0 + $specimen['S_Sec'] / 3600.0);
                if ($specimen['Coord_S'] > 359 || $specimen['S_Min'] > 59 || $specimen['S_Sec'] > 60) {
                    $malformed = true;
                }
            }
            if ($specimen['Coord_E']) {
                $lon  = $specimen['Coord_E'] + $specimen['E_Min'] / 60.0 + $specimen['E_Sec'] / 3600.0;
                $lonX = $specimen['E_Min'] + $specimen['Coord_E'] / 60.0 + $specimen['E_Sec'] / 3600.0;
                if ($specimen['Coord_E'] > 359 || $specimen['E_Min'] > 59 || $specimen['E_Sec'] > 60) {
                    $malformed = true;
                }
            } else {
                $lon  = -1.0 * ($specimen['Coord_W'] + $specimen['W_Min'] / 60.0 + $specimen['W_Sec'] / 3600.0);
                $lonX = -1.0 * ($specimen['W_Min'] + $specimen['Coord_W'] / 60.0 + $specimen['W_Sec'] / 3600.0);
                if ($specimen['Coord_W'] > 359 || $specimen['W_Min'] > 59 || $specimen['W_Sec'] > 60) {
                    $malformed = true;
                }
            }
            if (empty($boundaries[$specimen['NationID']])) {
                $boundaries[$specimen['NationID']] = $dbLnk->query("SELECT * FROM tbl_geo_nation_geonames_boundaries WHERE nationID = {$specimen['NationID']}")
                                                           ->fetch_all(MYSQLI_ASSOC);
            }
            if ($malformed) {
                $askService = false;
                $answers['malformed'][] = formatTableLine($specimen['specimen_ID'], "has malformed coordinates");
            } elseif (empty($specimen['name'])) {
                $askService = true;
            } else {
                $askService = true;
                if (checkBoundingBox($lat, $lon, $boundaries[$specimen['NationID']])) {
                    $askService = false;
                    $correct++;
                    $sql_correct .= "({$specimen['specimen_ID']}, 1), ";
                } elseif (checkBoundingBox($lon, $lat, $boundaries[$specimen['NationID']])) {
                    $askService = false;
                    $answers['lat_X_lon'][] = formatTableLine($specimen['specimen_ID'], "exchanged Latitute with Longitude");
                } elseif (checkBoundingBox($lat, $lonX, $boundaries[$specimen['NationID']])) {
                    $askService = false;
                    $answers['lat_lonX'][] = formatTableLine($specimen['specimen_ID'], "exchanged Degrees with Minutes in Longitude");
                } elseif (checkBoundingBox($latX, $lon, $boundaries[$specimen['NationID']])) {
                    $askService = false;
                    $answers['latX_lon'][] = formatTableLine($specimen['specimen_ID'], "exchanged Degrees with Minutes in Latitude");
                } elseif (checkBoundingBox($latX, $lonX, $boundaries[$specimen['NationID']])) {
                    $askService = false;
                    $answers['latX_lonX'][] = formatTableLine($specimen['specimen_ID'], "exchanged Degrees with Minutes in Latitude and Longitude");
                } elseif (checkBoundingBox(-1 * $lat, $lon, $boundaries[$specimen['NationID']])) {
                    $askService = false;
                    $answers['-lat_lon'][] = formatTableLine($specimen['specimen_ID'], "exchanged North with South");
                } elseif (checkBoundingBox($lat, -1 * $lon, $boundaries[$specimen['NationID']])) {
                    $askService = false;
                    $answers['lat_-lon'][] = formatTableLine($specimen['specimen_ID'], "exchanged East with West");
                } elseif (checkBoundingBox(-1 * $lat, -1 * $lon, $boundaries[$specimen['NationID']])) {
                    $askService = false;
                    $answers['-lat_-lon'][] = formatTableLine($specimen['specimen_ID'], "exchanged North with South and East with West");
                } elseif (checkBoundingBox(-1 * $lon, $lat, $boundaries[$specimen['NationID']])) {
                    $askService = false;
                    $answers['lat_X_-lon'][] = formatTableLine($specimen['specimen_ID'], "exchanged Latitute with Longitude and East with West");
                }
            }
            if ($askService) {
                $answers['askService'][$specimen['nation_engl']][] = formatTableLine($specimen['specimen_ID'], "not in bounding box of {$specimen['nation_engl']}");
//                try {
//                    $url = "http://api.geonames.org/countrySubdivisionJSON?username=joschach&lat=$lat&lng=$lon&radius=10";
//                    $response = $client->request('POST', $url, [
//                        'verify' => false
//                    ]);
//                    $data = json_decode($response->getBody()->getContents(), true, 512, JSON_INVALID_UTF8_SUBSTITUTE);
////                    echo "<pre>" . var_export($data, true) . "\n</pre>";
////                    exit();
//                    if (empty($data['countryCode'])) {
//                        $answers['noCountry'][] = formatLink($specimen['specimen_ID']) . "not in {$specimen['nation_engl']} $url<br>\n";
//                    } else if ($data['countryCode'] != $specimen['code']) {
//                        $answers['otherCountry'][] = formatLink($specimen['specimen_ID']) . "not in {$specimen['nation_engl']} but in "
//                            . ($data['adminName1'] ?? '??')
//                            . " (" . ($data['countryName'] ?? $data['countryCode']) . ") $url<br>\n";
//                    }
//                } catch (Exception $e) {
//                    $answers['error'][] = formatLink($specimen['specimen_ID']) . $e->getMessage() . "<br>\n";
//                }
            }
        }
    }
}
if (strlen($sql_correct) > 65) {
    $dbLnk->query(substr($sql_correct, 0, -2));
}

ksort($answers['askService']);
//$types = array('error', 'unknownNationID', 'noCountry', 'otherCountry');
$types = array('error'      => "have transmission errors",
               'unknown'    => "have unknown Nation-IDs",
               'malformed'  => "have malformed coordinates",
               'lat_X_lon'  => "exchanged Latitute with Longitude",
               'lat_lonX'   => "exchanged Degrees with Minutes in Longitude",
               'latX_lon'   => "exchanged Degrees with Minutes in Latitude",
               'latX_lonX'  => "exchanged Degrees with Minutes in Latitude and Longitude",
               '-lat_lon'   => "exchanged North with South",
               'lat_-lon'   => "exchanged East with West",
               '-lat_-lon'  => "exchanged North with South and East with West",
               'lat_X_-lon' => "exchanged Latitute with Longitude and East with West",
               'askService' => "are not in bounding box of country",
              );
echo "<table>\n"
    . "<tr><td>$correct</td><td>(total) are correct</td></tr>\n";
foreach ($types as $type => $text) {
    if (!empty($answers[$type])) {
        if ($type != 'askService') {
            echo "<tr><td style='text-align: right'>" . count($answers[$type]) . "</td><td><a href='#$type'>$text</a></td></tr>\n";
        } else {
            $count = 0;
            foreach ($answers['askService'] as $answerNation) {
                $count += count($answerNation);
            }
            echo "<tr><td style='text-align: right'>$count</td><td><a href='#$type'>$text</a></td></tr>\n";
        }
    }
}
echo "</table>\n";
foreach ($types as $type => $text) {
    if (!empty($answers[$type])) {
        echo "<hr id='$type'>\n<table>\n";
        if ($type != 'askService') {
            foreach ($answers[$type] as $answer) {
                echo $answer;
            }
        } else {
            foreach ($answers['askService'] as $answerNation) {
                foreach ($answerNation as $answer) {
                    echo $answer;
                }
            }
        }
        echo "</table>\n";
    }
}

?>
</body>
</html>
