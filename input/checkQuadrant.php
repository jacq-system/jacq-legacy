<?php
session_start();
ini_set("memory_limit","1G");

require_once __DIR__ . '/vendor/autoload.php';

use Jacq\DbAccess;

if (!isset($_SESSION['username']) || !isset($_SESSION['password'])) {
    die("Login required");
}

$dbLnk = DbAccess::ConnectTo('INPUT');

function formatLink($specimenID): string
{
    return  "<a href=\"javascript:editSpecimens('<$specimenID>')\">$specimenID</a>: ";
}

function formatTableLine($specimen, $lat, $lon, $text)
{
    return "<tr><td >" . formatLink($specimen['specimen_ID']) . "</td>"
         . "<td>" . dms($lat) . " / " . dms($lon) . "</td>"
         . "<td>" . $specimen['quadrant'] . " / " . ($specimen['quadrant_sub'] ?? '??') . "</td>"
         . "<td>$text</td>\n";
}

function quadrant2LatLon($quadrant, $quadrant_sub)
{
    $xx = intval(substr($quadrant, -2));
    $yy = intval(substr($quadrant, 0, -2));

    $xD = floor((($xx - 2) / 6) + 6);
    $xM = 0;
    $xS = round(((((($xx - 2) / 6) + 6) * 60) % 60) * 60);
    $yD = floor(($yy / -10) + 56);
    $yM = 0;
    $yS = round((((($yy / -10) + 56) * 60) % 60) * 60);

    if ($quadrant_sub == 0 || $quadrant_sub > 4) {
        $xM += 5;
        $yM -= 3;
    } else {
        $xS += (($quadrant_sub - 1) % 2) * (5 * 60);
        $yS -= floor(($quadrant_sub - 1) / 2) * (3 * 60);
        $xS += (60 * 5) / 2;   // Verschiebung zum Quadranten-Zentrum in Sekunden
        $yS -= (60 * 3) / 2;   // Verschiebung zum Quadranten-Zentrum in Sekunden
    }

    return ['lat' => $yD + ($yM / 60) + ($yS / 3600),
            'lon' => $xD + ($xM / 60) + ($xS / 3600)];
}

function latLon2quadrant($lat, $lon)
{
    $lat *= 60;
    $lon *= 60;
    if ($lon < 340 || $lon >= 1340 || $lat > 3360) {
        return array('error' => 'Lat/Lon out of bounds');
    }
    $xq = floor(($lon - 340) / 10);
    $yq = floor((3360 - $lat) / 6);
    $x_off = ($lon - 340) - $xq * 10;
    $y_off = (3360 - $lat) - $yq * 6;
    if ($x_off < 5) {
        if ($y_off < 3) {
            $sub = 1;
        } else {
            $sub = 3;
        }
    } else {
        if ($y_off < 3) {
            $sub = 2;
        } else {
            $sub = 4;
        }
    }
    return array('quadrant'     => sprintf("%d%02d", $yq, $xq),
                 'quadrant_sub' => $sub,
                 'error'        => '');
}

function distance($lat1, $lon1, $lat2, $lon2)
{
    $lat1 *= pi() / 180.0;
    $lon1 *= pi() / 180.0;
    $lat2 *= pi() / 180.0;
    $lon2 *= pi() / 180.0;

    return 6372.795477598 * acos(sin($lat1) * sin($lat2) + cos($lat1) * cos($lat2) * cos($lon1 - $lon2));
}

function dms($ddd)
{
    $deg = floor($ddd);
    $min = floor(($ddd - $deg) * 60);
    $sec = round((($ddd - $deg) * 60 - $min) * 60);

    return "{$deg}° {$min}' {$sec}\"";
}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
    "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
    <title>herbardb - check Quadrants</title>
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
$correct = 0;

$specimens = $dbLnk->query("SELECT s.specimen_ID, s.quadrant, s.quadrant_sub, s.exactness,
                             s.Coord_N, s.N_Min, s.N_Sec, s.Coord_S, s.S_Min, s.S_Sec,
                             s.Coord_W, s.W_Min, s.W_Sec, s.Coord_E, s.E_Min, s.E_Sec
                            FROM tbl_specimens s
                            WHERE (s.Coord_N > 0 OR s.Coord_S > 0)
                             AND  (s.Coord_E > 0 OR s.Coord_W > 0)
                             AND s.quadrant > 0")
                   ->fetch_all(MYSQLI_ASSOC);
foreach ($specimens as $specimen) {
    if ($specimen['Coord_N']) {
        $lat = $specimen['Coord_N'] + $specimen['N_Min'] / 60.0 + $specimen['N_Sec'] / 3600.0;
    } else {
        $lat = -1.0 * ($specimen['Coord_S'] + $specimen['S_Min'] / 60.0 + $specimen['S_Sec'] / 3600.0);
    }
    if ($specimen['Coord_E']) {
        $lon = $specimen['Coord_E'] + $specimen['E_Min'] / 60.0 + $specimen['E_Sec'] / 3600.0;
    } else {
        $lon = -1.0 * ($specimen['Coord_W'] + $specimen['W_Min'] / 60.0 + $specimen['W_Sec'] / 3600.0);
    }
    $quadrant = latLon2quadrant($lat, $lon);

    if ($quadrant['error']) {
        $qu_ex = latLon2quadrant($lon, $lat);
        $fullDigits = floor(log10($specimen['quadrant']));
        if (empty($qu_ex['error']) && $qu_ex['quadrant'] == $specimen['quadrant'] && ($qu_ex['quadrant_sub'] == $specimen['quadrant_sub'] || $specimen['quadrant_sub'] == 0)) {
            $answers['latXlon'][] = formatTableLine($specimen, $lat, $lon, "has swapped latitude and longitude");
        } elseif ($fullDigits > 0 && empty($specimen['exactness'])
                                  && ((   $specimen['quadrant'] % (10 ** $fullDigits)) == 0
                                       || ($fullDigits == 2 && ($specimen['quadrant'] % 100) == 50)
                                       || ($fullDigits > 2 && ($specimen['quadrant'] % (10 ** ($fullDigits - 1))) == 0))) {
            $answers['quXex'][] = formatTableLine($specimen, $lat, $lon, "has presumably exactness in quadrant");
        } else {
            $answers['error'][] = formatTableLine($specimen, $lat, $lon, $quadrant['error']);
        }
    } elseif ($quadrant['quadrant'] == $specimen['quadrant'] && ($quadrant['quadrant_sub'] == $specimen['quadrant_sub'] || $specimen['quadrant_sub'] == 0)) {
        $correct++;
    } elseif ($quadrant['quadrant'] == $specimen['quadrant'] && $specimen['quadrant_sub'] == null) {
        $answers['ok_but_sub_null'][] = formatTableLine($specimen, $lat, $lon, "should be " . $quadrant['quadrant'] . " / " . $quadrant['quadrant_sub']);
    } elseif ($quadrant['quadrant'] == $specimen['quadrant'] && $quadrant['quadrant_sub'] != $specimen['quadrant_sub']) {
        $answers['ok_but_sub_wrong'][] = formatTableLine($specimen, $lat, $lon, "should be " . $quadrant['quadrant'] . " / " . $quadrant['quadrant_sub']);
    } elseif ($specimen['quadrant_sub'] == null) {
        if ($specimen['exactness'] == $quadrant['quadrant']) {
            if ($specimen['quadrant'] == $quadrant['quadrant_sub']) {
                $answers['qu_in_ex'][] = formatTableLine($specimen, $lat, $lon, "should be " . $quadrant['quadrant'] . " / " . $quadrant['quadrant_sub']);
            } else {
                $answers['qu_in_ex_sub_wrong'][] = formatTableLine($specimen, $lat, $lon, "should be " . $quadrant['quadrant'] . " / " . $quadrant['quadrant_sub']);
            }
        } elseif (abs($quadrant['quadrant'] - $specimen['exactness']) == 100) {
            $answers['qu_in_ex_adjacentNS'][] = formatTableLine($specimen, $lat, $lon, "should be " . $quadrant['quadrant'] . " / " . $quadrant['quadrant_sub']);
        } elseif (abs($quadrant['quadrant'] - $specimen['exactness']) == 1) {
            $answers['qu_in_ex_adjacentEW'][] = formatTableLine($specimen, $lat, $lon, "should be " . $quadrant['quadrant'] . " / " . $quadrant['quadrant_sub']);
        } elseif ($specimen['quadrant'] % 10 == $quadrant['quadrant_sub'] && floor($specimen['quadrant'] / 10) == $quadrant['quadrant']) {
            $answers['sub_in_qu'][] = formatTableLine($specimen, $lat, $lon, "should be " . $quadrant['quadrant'] . " / " . $quadrant['quadrant_sub']);
        } elseif (!empty($specimen['exactness'])) {
            $answers['sub_null_ex'][] = formatTableLine($specimen, $lat, $lon,
                                                   "should be {$quadrant['quadrant']} / {$quadrant['quadrant_sub']} (exactness is {$specimen['exactness']})");
        } else {
            $answers['sub_null'][] = formatTableLine($specimen, $lat, $lon, "should be " . $quadrant['quadrant'] . " / " . $quadrant['quadrant_sub']);
        }
    } elseif ($specimen['quadrant_sub'] > 4) {
        $answers['sub_large'][] = formatTableLine($specimen, $lat, $lon, "should be " . $quadrant['quadrant'] . " / " . $quadrant['quadrant_sub']);
    } elseif ($quadrant['quadrant'] != $specimen['quadrant']) {
        if (abs($quadrant['quadrant'] - $specimen['quadrant']) == 100) {
            $answers['adjacentNS'][] = formatTableLine($specimen, $lat, $lon, "should be " . $quadrant['quadrant'] . " / " . $quadrant['quadrant_sub']);
        } elseif (abs($quadrant['quadrant'] - $specimen['quadrant']) == 1) {
            $answers['adjacentEW'][] = formatTableLine($specimen, $lat, $lon, "should be " . $quadrant['quadrant'] . " / " . $quadrant['quadrant_sub']);
        } else {
            $answers['wrong_quadrant'][] = formatTableLine($specimen, $lat, $lon, "should be " . $quadrant['quadrant'] . " / " . $quadrant['quadrant_sub']);
        }
    } else {
        $answers['other'][] = formatTableLine($specimen, $lat, $lon, "has other problems");
    }
//    $coord = quadrant2LatLon($specimen['quadrant'], $specimen['quadrant_sub']);
//    $distance = distance($lat, $lon, $coord['lat'], $coord['lon']);
//    if ($distance > 10) {
//        echo formatLink($specimen['specimen_ID'])
//            . $specimen['quadrant'] . " = "
//            . dms($coord['lat']) . " / " . dms($coord['lon']) . " => "
//            . $distance . " km<br>\n";
//    }
}

$types = array('ok_but_sub_null'     => "are in the correkt quadrant but have an empty sub",
               'ok_but_sub_wrong'    => "are in the correkt quadrant but have a wrong sub",
               'qu_in_ex'            => "wrong fields: quadrant is in exactness and quadrant_sub is in quadrant",
               'qu_in_ex_sub_wrong'  => "wrong fields: quadrant is in exactness and quadrant_sub is wrong",
               'qu_in_ex_adjacentNS' => "wrong fields: quadrant is in exactness but in the northern or southern adjacent quadrant",
               'qu_in_ex_adjacentEW' => "wrong fields: quadrant is in exactness but in the eastern or western adjacent quadrant",
               'sub_in_qu'           => "wrong fields: sub is in units digit of quadrant",
               'sub_null_ex'         => "have empty sub but non empty exacness",
               'sub_null'            => "have empty sub and empty exactness",
               'sub_large'           => "have sub > 4",
               'adjacentNS'          => "are in the northern or southern adjacent quadrant",
               'adjacentEW'          => "are in the eastern or western adjacent quadrant",
               'wrong_quadrant'      => "are in the wrong quadrant",
               'latXlon'             => "have latitude and longitude swapped",
               'quXex'               => "have presumably exactness in quadrant",
               'error'               => "have Lat/Lon out of bounds",
               'other'               => "have other problems",
);
echo "<table>\n"
   . "<tr><td>$correct</td><td>are correct</td></tr>\n";
foreach ($types as $type => $text) {
    if (!empty($answers[$type])) {
        echo "<tr><td style='text-align: right'>" . count($answers[$type]) . "</td><td><a href='#$type'>$text</a></td></tr>\n";
    }
}
echo "</table>\n";
foreach ($types as $type => $text) {
    if (!empty($answers[$type])) {
        echo "<hr id='$type'>\n<table>\n";
        foreach ($answers[$type] as $answer) {
            echo $answer;
        }
        echo "</table>\n";
    }
}

?>
</body>
</html>
