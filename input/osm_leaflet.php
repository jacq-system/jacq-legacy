<?php
session_start();
require("inc/connect.php");
require_once("inc/herbardb_input_functions.php");

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

function collection($row): string
{
    $text = $row['Sammler'];
    if (strstr($row['Sammler_2'], "&") || strstr($row['Sammler_2'], "et al.")) {
        $text .= " et al.";
    } else if ($row['Sammler_2']) {
        $text .= " & " . $row['Sammler_2'];
    }

    if ($row['series_number']) {
        if ($row['Nummer']) {
            $text .= " " . $row['Nummer'];
        }
        if ($row['alt_number'] && $row['alt_number'] != "s.n.") {
            $text .= " " . $row['alt_number'];
        }
        if ($row['series']) {
            $text .= " " . $row['series'];
        }
        $text .= " " . $row['series_number'];
    } else {
        if ($row['series']) {
            $text .= " " . $row['series'];
        }
        if ($row['Nummer']) {
            $text .= " " . $row['Nummer'];
        }
        if ($row['alt_number']) {
            $text .= " " . $row['alt_number'];
        }
    }

    return trim($text);
}

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


$row = dbi_query("SELECT s.specimen_ID, s.series_number, s.Nummer, s.alt_number, s.Datum, s.HerbNummer,
                   s.Coord_W, s.W_Min, s.W_Sec, s.Coord_N, s.N_Min, s.N_Sec,
                   s.Coord_S, s.S_Min, s.S_Sec, s.Coord_E, s.E_Min, s.E_Sec,
                   c.Sammler, c2.Sammler_2,
                   ss.series,
                   mc.collection,
                   tst.typusID,
                   `herbar_view`.GetScientificName(s.taxonID, 0) AS `scientificName`
                  FROM (tbl_specimens s, tbl_management_collections mc)
                   LEFT JOIN tbl_specimens_types tst ON tst.specimenID = s.specimen_ID
                   LEFT JOIN tbl_specimens_series ss ON ss.seriesID = s.seriesID
                   LEFT JOIN tbl_collector c ON c.SammlerID = s.SammlerID
                   LEFT JOIN tbl_collector_2 c2 ON c2.Sammler_2ID = s.Sammler_2ID
                  WHERE mc.collectionID = s.collectionID
                   AND s.specimen_ID = '" . intval(filter_input(INPUT_GET, 'sid', FILTER_SANITIZE_NUMBER_INT)) . "'")
    ->fetch_assoc();
$lat = dms2sec($row['Coord_S'], $row['S_Min'], $row['S_Sec'], $row['Coord_N'], $row['N_Min'], $row['N_Sec']) / 3600.0;
$lng = dms2sec($row['Coord_W'], $row['W_Min'], $row['W_Sec'], $row['Coord_E'], $row['E_Min'], $row['E_Sec']) / 3600.0;
$url = "https://www.jacq.org/detail.php?ID=" . $row['specimen_ID'];
$txt = htmlentities(collection($row), ENT_QUOTES | ENT_HTML401) . " / "
     . $row['Datum'] . " / "
     . (($row['typusID']) ? htmlentities($row['typusID']) . " / " : '')
     . htmlentities(collectionItem($row['collection'])) . " "
     . htmlentities($row['HerbNummer']);
$txt = "<a href=\"$url\" target=\"_blank\">"
     . "<div style=\"font-family: Arial,sans-serif; font-weight: bold; font-size: medium;\">"
     . htmlentities($row['scientificName'], ENT_QUOTES | ENT_HTML401)
     . "</div>"
     . "<div style=\"font-family: Arial,sans-serif; font-size: small;\">"
     . strtr($txt, array("\r" => '', "\n" => ''))
     . "</div>"
     . "</a>";

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
        var jacq_map = L.map('map').setView({lon: <?php echo $lng; ?>, lat: <?php echo $lat; ?>}, 1);

        var corner1 = L.latLng(<?php echo $lat ?>, <?php echo $lng ?>),
            corner2 = L.latLng(<?php echo $lat ?>, <?php echo $lng ?>),
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

        // show the scale bar in the lower left corner
        L.control.scale().addTo(jacq_map);
        // show the marker on the map
        <?php
        echo "L.marker({lon: " . $lng . ", lat: " . $lat . "}).bindPopup('" . $txt . "').addTo(jacq_map);\n";
        ?>
    }
</script>
</body>
</html>
