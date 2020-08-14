<?php
session_start();
require("inc/functions.php");

$specimen_ID = intval(filter_input(INPUT_GET, 'sid', FILTER_SANITIZE_NUMBER_INT));

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
if (empty($specimen_ID)) {
    $result = $dbLink->query($_SESSION['s_query'] . "ORDER BY genus, epithet, author");
} else {
    $sql = "SELECT s.specimen_ID, s.series_number, s.Nummer, s.alt_number, s.Datum, s.HerbNummer,
                   s.Coord_W, s.W_Min, s.W_Sec, s.Coord_N, s.N_Min, s.N_Sec,
                   s.Coord_S, s.S_Min, s.S_Sec, s.Coord_E, s.E_Min, s.E_Sec,
                   tg.genus,
                   c.Sammler, c2.Sammler_2,
                   ss.series,
                   mc.collection,
                   tst.typusID,
                   ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
                   ta4.author author4, ta5.author author5,
                   te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
                   te4.epithet epithet4, te5.epithet epithet5,
                   ts.taxonID, ts.statusID
                  FROM (tbl_specimens s, tbl_tax_species ts, tbl_tax_genera tg, tbl_management_collections mc)
                   LEFT JOIN tbl_specimens_types tst ON tst.specimenID = s.specimen_ID
                   LEFT JOIN tbl_specimens_series ss ON ss.seriesID = s.seriesID
                   LEFT JOIN tbl_collector c ON c.SammlerID = s.SammlerID
                   LEFT JOIN tbl_collector_2 c2 ON c2.Sammler_2ID = s.Sammler_2ID
                   LEFT JOIN tbl_tax_authors ta ON ta.authorID = ts.authorID
                   LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID = ts.subspecies_authorID
                   LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID = ts.variety_authorID
                   LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID = ts.subvariety_authorID
                   LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID = ts.forma_authorID
                   LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID = ts.subforma_authorID
                   LEFT JOIN tbl_tax_epithets te ON te.epithetID = ts.speciesID
                   LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID = ts.subspeciesID
                   LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID = ts.varietyID
                   LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID = ts.subvarietyID
                   LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID = ts.formaID
                   LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID = ts.subformaID
                  WHERE ts.taxonID = s.taxonID
                   AND tg.genID = ts.genID
                   AND mc.collectionID = s.collectionID
                   AND s.specimen_ID = '$specimen_ID'";
    $result = $dbLink->query($sql);
}
while ($row = $result->fetch_array()) {
    $lat = dms2sec($row['Coord_S'], $row['S_Min'], $row['S_Sec'], $row['Coord_N'], $row['N_Min'], $row['N_Sec']);
    $lng = dms2sec($row['Coord_W'], $row['W_Min'], $row['W_Sec'], $row['Coord_E'], $row['E_Min'], $row['E_Sec']);
    if ($lat != 0 || $lng != 0) {
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
  <body onload="domap()">
    <div id="map"></div>
    <script type="text/javascript">
      function domap()
      {
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
      }
    </script>
  </body>
</html>