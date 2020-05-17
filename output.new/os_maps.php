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
        if ($m < $val[$key]) {
            $m = $val[$key];
        }
    }
    return $m;
}


function min2 ($a, $key)
{
    $m = $a[0][$key];
    foreach ($a as $val) {
        if ($m > $val[$key]) {
            $m = $val[$key];
        }
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


$points = null;
$result = $dbLink->query($_SESSION['s_query'] . "ORDER BY genus, epithet, author");
while ($row = $result->fetch_array()) {
    $lat = dms2sec($row['Coord_S'], $row['S_Min'], $row['S_Sec'], $row['Coord_N'], $row['N_Min'], $row['N_Sec']);
    $lng = dms2sec($row['Coord_W'], $row['W_Min'], $row['W_Sec'], $row['Coord_E'], $row['E_Min'], $row['E_Sec']);
    if ($lat != 0 && $lng != 0) {
        $point['lat'] = $lat;
        $point['lng'] = $lng;

        $url = "https://www.jacq.org/detail.php?ID=" . $row['specimen_ID'];

        $txt = "<div style=\"font-family: Arial,sans-serif; font-weight: bold; font-size: medium;\">"
             . htmlspecialchars(taxonWithHybrids($row))
             . "</div>"
             . "<div style=\"font-family: Arial,sans-serif; font-size: small;\">"
             . htmlentities(collection($row['Sammler'], $row['Sammler_2'], $row['series'], $row['series_number'], $row['Nummer'], $row['alt_number'], $row['Datum']), ENT_QUOTES | ENT_HTML401) . " / "
             . $row['Datum'] . " / ";
        if ($row['typusID']) {
            $txt .= htmlspecialchars($row['typusID']) . " / ";
        }
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

?><!DOCTYPE HTML>
<html lang="en">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <title>Virtual Herbaria - specimen maps</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <style>
      html, body {
        height: 100%;
        padding: 0;
        margin: 0;
      }
      #map {
        height: 100%;
        width: 100%;
      }
    </style>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.6.0/dist/leaflet.css"
        integrity="sha512-xwE/Az9zrjBIphAcBb3F6JVqxf46+CDLwfLMHloNu6KEQCAWi6HcDUbeOfBIptF7tcCzusKFjFw2yuvEpDL9wQ=="
        accesskey="" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.6.0/dist/leaflet.js"
        integrity="sha512-gZwIG9x3wUXg2hdXF6+rVkLF/0Vi9U8D2Ntg4Ga5I5BZpVkVxlJWbSQtXPSiUTtC0TjtGOmxa1AJPuV0CPthew=="
        crossorigin=""></script>
  </head>
  <body>
    <div id="map"></div>
    <script type="text/javascript">
      // initialize Leaflet
      var jacq_map = L.map('map').setView({lon: <?php echo $mean_lng; ?>, lat: <?php echo $mean_lat; ?>}, 1);

      var corner1 = L.latLng(<?php echo $min_lat ?>, <?php echo $min_lng ?>),
          corner2 = L.latLng(<?php echo $max_lat ?>, <?php echo $max_lng ?>),
          bounds = L.latLngBounds(corner1, corner2);
      var bestLevel = jacq_map.getBoundsZoom(bounds);
      if (bestLevel>12) bestLevel = 12;
      jacq_map.setZoom(bestLevel);

      // add the OpenTopoMap tiles
      var topoUrl = 'https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png',
          topoAttribution = 'Map data: &copy; <a href="https://openstreetmap.org/copyright">OpenStreetMap</a>-contributors, <a href="http://viewfinderpanoramas.org">SRTM</a> | Map display: &copy; <a href="https://opentopomap.org">OpenTopoMap</a> (<a href="https://creativecommons.org/licenses/by-sa/3.0/">CC-BY-SA</a>)',
          topo = new L.TileLayer(topoUrl, {minZoom: 1, maxZoom: 17, detectRetina: false, attribution: topoAttribution});

      // add the OpenStreetMap tiles
      var osmUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
          osmAttribution = 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>',
          osm = new L.TileLayer(osmUrl, {maxZoom: 19, detectRetina: false, attribution: osmAttribution});
      var baseMaps = {
            "OpenTopoMap": topo,
            "OpenStreetMap": osm
          };
      jacq_map.addLayer(topo);
      var layersControl = new L.Control.Layers(baseMaps);
      jacq_map.addControl(layersControl);

      // show the scale bar on the lower left corner
      L.control.scale().addTo(jacq_map);
      // show the markers on the map
      <?php
        for ($i = 0; $i < count($points); $i++) {
            echo "L.marker({lon: " . ($points[$i]['lng'] / 3600.0) . ", lat: " . ($points[$i]['lat'] / 3600.0) . "}).bindPopup('" . $points[$i]['txt'] . "').addTo(jacq_map);\n";
        }
      ?>
    </script>
  </body>
</html>