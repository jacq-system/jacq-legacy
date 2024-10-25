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
  <img src="images/access_denied.jpg" alt="access denied">
</div>

<div id="footer-wrapper">
  <div class="divider"></div>
  <div id="footer">
    <a href="imprint_citation_privacy.htm">Imprint | Citation | Privacy</a>
  </div>
</div>
</body>
</html>
