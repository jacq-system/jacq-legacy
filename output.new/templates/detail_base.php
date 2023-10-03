<!DOCTYPE html>
<html>
<head>
  <title>JACQ - Virtual Herbaria</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta name="description" content="FW4 DW4 HTML">
  <!--<link type="text/css" rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">-->
  <link type="text/css" rel="stylesheet" href="assets/materialize/css/materialize.min.css"  media="screen"/>
  <link type="text/css" rel="stylesheet" href="assets/fontawesome/css/all.css">
  <link type="text/css" rel="stylesheet" href="assets/custom/styles/jacq.css"  media="screen"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <link rel="shortcut icon" href="JACQ_LOGO.png"/>
  <script type="text/javascript">
    function showPicture(url) {
      MeinFenster =
      window.open(url,
                  "Picture",
                  "width=700,height=500,top=100,left=100,resizable,scrollbars");
      MeinFenster.focus();
    }
  </script>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.6.0/dist/leaflet.css"
        integrity="sha512-xwE/Az9zrjBIphAcBb3F6JVqxf46+CDLwfLMHloNu6KEQCAWi6HcDUbeOfBIptF7tcCzusKFjFw2yuvEpDL9wQ=="
        accesskey="" crossorigin=""/>
  <script src="https://unpkg.com/leaflet@1.6.0/dist/leaflet.js"
        integrity="sha512-gZwIG9x3wUXg2hdXF6+rVkLF/0Vi9U8D2Ntg4Ga5I5BZpVkVxlJWbSQtXPSiUTtC0TjtGOmxa1AJPuV0CPthew=="
        crossorigin="">
  </script>
  <!-- Matomo -->
  <script type="text/javascript">
    var _paq = window._paq || [];
    /* tracker methods like "setCustomDimension" should be called before "trackPageView" */
    _paq.push(['trackPageView']);
    _paq.push(['enableLinkTracking']);
    (function() {
      var u="//iiif.jacq.org/piwik/matomo/";
      _paq.push(['setTrackerUrl', u+'matomo.php']);
      _paq.push(['setSiteId', '1']);
      var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
      g.type='text/javascript'; g.async=true; g.defer=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
    })();
  </script>
  <!-- End Matomo Code -->
</head>
<body onload="domap()">
<div id="navbar" class="navbar-fixed">
  <nav class="nav-extended">
    <div class="nav-wrapper">
      <a href="https://www.jacq.org/#database" class="brand-logo center"><img src="assets/images/JACQ_LOGO.png" alt="JACQ Logo"></a>
    </div>
    <div class="nav-content">
      <ul class="tabs">
        <li class="tab"><a class="active" href="detail.php?ID=<?php echo $output['ID']; ?>">Detail</a></li>
      </ul>
    </div>
  </nav>
</div>

<div id="details" class="container">
  <table class="striped">
    <tr>
      <td align="right" width="30%">Stable identifier<?php echo (count($output['stblids']) > 0) ? 's' : ''; ?></td>
      <td>
        <?php
            if ($output['stblids']) {
                foreach ($output['stblids'] as $line) {?>
                    <b><a href="<?php echo $line['link']; ?>" target="_blank"><?php echo $line['stblid']; ?></a></b>
                    <?php echo ($line['timestamp']) ? "({$line['timestamp']})" : ''; ?><br>
          <?php }
            } ?>
      </td>
    </tr>
    <tr>
      <td align="right" width="30%">Herbarium #</td>
      <td>
        <b><?php echo $output['HerbariumNr']; ?></b>
      </td>
    </tr>
    <tr>
      <td align="right" width="30%">Collection #</td>
      <td>
        <b><?php echo $output['collectionNr']; ?></b>
      </td>
    </tr>
    <tr>
      <td align="right">Stored under taxonname</td>
      <td>
        <b><?php echo $output['taxon']; ?></b>
        &nbsp;
        <a href="http://www.tropicos.org/NameSearch.aspx?name=<?php echo urlencode($specimen['genus'] . " "  . $specimen['epithet']); ?>&exact=true" title="Search in tropicos" target="_blank">
          <img alt="tropicos" src="images/tropicos.png" border="0" width="16" height="16">
        </a>
        <?php echo nl2br($output['taxonAuth'], true); ?>
      </td>
    </tr>
    <tr>
      <td align="right">Family</td>
      <td>
        <b><?php echo $specimen['family']; ?></b>
      </td>
    </tr>
    <tr>
      <td align="right">Det./rev./conf./assigned</td>
      <td>
        <b><?php echo $specimen['det']; ?></b>
      </td>
    </tr>
    <tr>
      <td align="right">Ident. history</td>
      <td>
        <b><?php echo $specimen['taxon_alt']; ?></b>
      </td>
    </tr>
    <?php
        if (strlen($output['typusText']) > 0) {
            echo nl2br($output['typusText'], true);
        }
    ?>
    <tr>
      <td align="right">Collector</td>
      <td>
        <b><?php echo $output['sammler']; ?></b>
      </td>
    </tr>
    <tr>
      <td align="right">Date</td>
      <td align="left">
        <b><?php echo $specimen['Datum']; ?></b>
      </td>
    </tr>
    <tr>
      <td align="right">Location</td>
      <td>
        <?php if (!empty($point)): ?>
          <div id='map'></div>
          <script type="text/javascript">
            function domap()
            {
              // initialize Leaflet
              var jacq_map = L.map('map').setView({lon: <?php echo $point['lng']; ?>, lat: <?php echo $point['lat']; ?>}, 1);

              jacq_map.setZoom(12);

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
              L.marker({lon: <?php echo $point['lng']; ?> , lat: <?php echo $point['lat']; ?>}).bindPopup('<?php echo $point['txt']; ?>').addTo(jacq_map);
            }
          </script>
        <?php endif; ?>
        <b><?php echo $output['location']; ?></b>
      </td>
    </tr>
    <tr>
      <td align="right">Label</td>
      <td>
        <b><?php echo nl2br($specimen['Fundort'], true); ?>
            <?php if (!empty($specimen['altitude_min'])): ?>
                ;&nbsp;Alt. <?php echo nl2br($specimen['altitude_min'], true); ?>&nbsp;m
                <?php if (!empty($specimen['altitude_max'])): ?>
                    &nbsp; - &nbsp; <?php echo nl2br($specimen['altitude_max'], true); ?>&nbsp;m
                <?php endif; ?>
            <?php endif; ?>
        </b>
      </td>
    </tr>
    <tr>
      <td align="right">Habitat</td>
      <td>
        <b><?php echo nl2br($specimen['habitat'], true); ?></b>
      </td>
    </tr>
    <tr>
      <td align="right">Habitus</td>
      <td>
        <b><?php echo nl2br($specimen['habitus'], true); ?></b>
      </td>
    </tr>
    <tr>
      <td align="right">Annotations</td>
      <td>
        <b><?php echo nl2br($output['annotations'], true); ?></b>
      </td>
    </tr>
    <?php if ($output['newAnno']) : ?>
      <tr>
        <td align='right'>Annosys annotations<br/><a href='<?php echo $output['newAnnoUri']; ?>' target='_blank'>Add annotation</a></td>
        <td><b>
          <?php echo $output['newAnnoTable']; ?>
        </b></td>
      </tr>
    <?php endif; ?>
    <tr>
      <td align="left" colspan="2">
        <?php if (!empty($output['picture_include'])) { include $output['picture_include']; } ?>
      </td>
    </tr>
  </table>
</div>
<div id="footer-wrapper">
  <div class="divider"></div>
  <div id="footer">
    <a href="imprint_citation_privacy.htm">Imprint | Citation | Privacy</a>
  </div>
</div>
<script type="text/javascript" src="assets/jquery/jquery.min.js"></script>
<script type="text/javascript" src="assets/materialize/js/materialize.min.js"></script>
<script type="text/javascript" src="assets/custom/scripts/jacq.js"></script>
</body>
</html>
