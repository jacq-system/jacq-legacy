<?php
session_start();
require("inc/functions.php");

function collectionItem ($coll)
{
    if (strpos($coll, "-") !== false) {
        return substr($coll, 0, strpos($coll, "-"));
    } elseif (strpos($coll, " ") !== false) {
        return substr($coll, 0, strpos($coll, " "));
    } else {
        return $coll;
    }
}


function dms2sec ($degN, $minN, $secN, $degP, $minP, $secP)
{
    if ($degN > 0 || $minN > 0 || $secN > 0) {
        $sec = -($degN * 3600 + $minN * 60 + $secN);
    } else if ($degP > 0 || $minP > 0 || $secP > 0) {
        $sec = $degP * 3600 + $minP * 60 + $secP;
    } else {
        $sec = 0;
    }
    return $sec;
}


function max2 ($a, $key)
{
    $m = $a[0][$key];
    foreach ($a as $val) {
        if ($m < $val[$key]) $m = $val[$key];
    }
    return $m;
}


function min2 ($a, $key)
{
    $m = $a[0][$key];
    foreach ($a as $val) {
        if ($m > $val[$key]) $m = $val[$key];
    }
    return $m;
}


function contains ($points, $point, $limit = 6)
{
    if (is_array($points)) {
        foreach ($points as $key => $val) {
            if (abs($val['lat'] - $point['lat']) < $limit && abs($val['lng'] - $point['lng']) < $limit) {
                return $key;
            }
        }
    }
    return false;
}


unset($points);
$result = mysql_query($_SESSION['s_query'] . "ORDER BY genus, epithet, author");
while ($row = mysql_fetch_array($result)) {
    $lat = dms2sec($row['Coord_S'], $row['S_Min'], $row['S_Sec'], $row['Coord_N'], $row['N_Min'], $row['N_Sec']);
    $lng = dms2sec($row['Coord_W'], $row['W_Min'], $row['W_Sec'], $row['Coord_E'], $row['E_Min'], $row['E_Sec']);
    if ($lat != 0 && $lng != 0) {
        $point['lat'] = $lat;
        $point['lng'] = $lng;

        $url = "http://herbarium.univie.ac.at/database/detail.php?ID=" . $row['specimen_ID'];

        $txt = "<div style=\"font-family: Arial,sans-serif; font-weight: bold; font-size: medium;\">"
             . htmlspecialchars(taxonWithHybrids($row))
             . "</div>"
             . "<div style=\"font-family: Arial,sans-serif; font-size: small;\">"
             . htmlentities(collection($row['Sammler'], $row['Sammler_2'], $row['series'], $row['series_number'], $row['Nummer'], $row['alt_number'], $row['Datum'])) . " / "
             . $row['Datum'] . " / ";
        if ($row['typusID']) $txt .= htmlspecialchars($row['typusID']) . " / ";
        $txt .= htmlspecialchars(collectionItem($row['collection'])) . " " . htmlspecialchars($row['HerbNummer']) . "</div>";
        $txt = strtr($txt, array("\r" => '', "\n" => ''));

        $point['txt'] = "<a href=\"$url\" target=\"_blank\">$txt</a>";
        $key = contains($points, $point);
        if ($key === false) {
            $points[] = $point;
        } else {
            $points[$key]['txt'] .= $point['txt'];
        }
    }
}

$max_lat = max2($points,'lat') / 3600.0;
$min_lat = min2($points,'lat') / 3600.0;
$max_lng = max2($points,'lng') / 3600.0;
$min_lng = min2($points,'lng') / 3600.0;
$mean_lat = ($max_lat + $min_lat) / 2.0;
$mean_lng = ($max_lng + $min_lng) / 2.0;

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <title>Virtual Herbaria - specimen maps</title>
    <script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=ABQIAAAAO57Zq5Pkg_RlcgBv9Mho9RTQAh6tzN7yNdx6Xq51aKDpBXb7-BQWO7tHWQbjF9QutPY8kc_I_XygDw"
            type="text/javascript"></script>
    <script type="text/javascript">
    //<![CDATA[

    function load() {
      if (GBrowserIsCompatible()) {
        var map = new GMap2(document.getElementById("map"));
        var limit_sw = new GLatLng(<?php echo $min_lat ?>,<?php echo $min_lng ?>);
        var limit_ne = new GLatLng(<?php echo $max_lat ?>,<?php echo $max_lng ?>);
        var bounds = new GLatLngBounds(limit_sw, limit_ne);

        map.setCenter(new GLatLng(<?php echo $mean_lat ?>, <?php echo $mean_lng ?>), 1);
        //map.addControl(new GLargeMapControl()); // pan, zoom
        map.addControl(new GSmallMapControl()); // pan, zoom
        map.addControl(new GMapTypeControl()); // map, satellite, hybrid
        map.addMapType(G_PHYSICAL_MAP);
        map.addControl(new GOverviewMapControl()); // small overview in corner
        map.addControl(new GScaleControl());
        map.setMapType(G_HYBRID_MAP)

        bestLevel = map.getBoundsZoomLevel(bounds);
        if (bestLevel>12) bestLevel = 12;
        map.setZoom(bestLevel);

        function createMarker(latitude, longitude, text) {
          var marker = new GMarker(new GLatLng(latitude, longitude));

          GEvent.addListener(marker, "click", function() {
            //window.open(url,'_blank');
            marker.openInfoWindowHtml(text);
          });
          map.addOverlay(marker);
        }

        <?php
        for ($i = 0; $i < count($points); $i++) {
            echo "        createMarker(" . ($points[$i]['lat'] / 3600.0) . "," . ($points[$i]['lng'] / 3600.0) . ",'" . $points[$i]['txt'] . "');\n";
        }
        ?>

      }
    }

    //]]>
    </script>
  </head>
  <body onload="load()" onunload="GUnload()">
    <div id="map" style="width: 800px; height: 600px"></div>
  </body>
</html>