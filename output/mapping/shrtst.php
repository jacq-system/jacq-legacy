<?php
$points_lat = array(47, 47.5, 48);
$points_lng = array(13, 13.5, 14);
$max_lat = max($points_lat);
$min_lat = min($points_lat);
$max_lng = max($points_lng);
$min_lng = min($points_lng);
$mean_lat = (max($points_lat) + min($points_lat)) / 2.0;
$mean_lng = (max($points_lng) + min($points_lng)) / 2.0;
$points_cnt = count($points_lat);

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <title>Virtual Herbaria Austria - specimen maps</title>
    <script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=ABQIAAAAO57Zq5Pkg_RlcgBv9Mho9RTQAh6tzN7yNdx6Xq51aKDpBXb7-BQWO7tHWQbjF9QutPY8kc_I_XygDw"
            type="text/javascript"></script>
    <script type="text/javascript">
    //<![CDATA[

    function load() {
      if (GBrowserIsCompatible()) {
        var map = new GMap2(document.getElementById("map"));
        var limit_sw = new GLatLng(<?php echo $min_lat; ?>,<?php echo $min_lng; ?>);
        var limit_ne = new GLatLng(<?php echo $max_lat; ?>,<?php echo $max_lng; ?>);
        var bounds = new GLatLngBounds(limit_sw, limit_ne);

        map.setCenter(new GLatLng(<?php echo $mean_lat; ?>, <?php echo $mean_lng; ?>), 1);
        //map.addControl(new GLargeMapControl()); // pan, zoom
        map.addControl(new GSmallMapControl()); // pan, zoom
        map.addControl(new GMapTypeControl()); // map, satellite, hybrid
        map.addControl(new GOverviewMapControl()); // small overview in corner

        <?php
        for ($i=0; $i<count($points_lat); $i++) {
          echo "        var point = new GLatLng(".$points_lat[$i].",".$points_lng[$i].");\n".
               "        map.addOverlay(new GMarker(point));\n";
        }

        bestLevel = map.getBoundsZoomLevel(bounds);
        if (bestLevel>7) bestLevel = 7;
        map.setZoom(bestLevel);
      }
    }

    //]]>
    </script>
  </head>
  <body onload="load()" onunload="GUnload()">
    <div id="map" style="width: 800px; height: 600px"></div>
  </body>
</html>