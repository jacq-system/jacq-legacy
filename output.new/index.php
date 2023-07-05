<?php

use Jacq\DbAccess;

define('INDEX_START', true);
if(!empty($_GET)) {
    // someone followed an external link to a direct search, so let the script click the search button automatically
    unset($_GET['search']);
    define('START_SEARCH', true);
}

session_start();
require_once "inc/functions.php";
require_once __DIR__ . '/vendor/autoload.php';

try {
    $dbLnk2 = DbAccess::ConnectTo('OUTPUT');
} catch (Exception $e) {
    echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">\n" .
        "<html lang='en'>\n" .
        "<head><title>Sorry, no connection ...</title></head>\n" .
        "<body><p>Sorry, no connection to database ... {$e->getMessage()}</p></body>\n" .
        "</html>\n";
    exit();
}

// if script was called from the outside with some search parameters already in place, put the in variables
// else leave these variables empty
$family      = (isset($_GET['family']))      ? filter_input(INPUT_GET, 'family',      FILTER_SANITIZE_STRING) : '';
$taxon       = (isset($_GET['taxon']))       ? filter_input(INPUT_GET, 'taxon',       FILTER_SANITIZE_STRING) : '';
$HerbNummer  = (isset($_GET['HerbNummer']))  ? filter_input(INPUT_GET, 'HerbNummer',  FILTER_SANITIZE_STRING) : '';
$Sammler     = (isset($_GET['Sammler']))     ? filter_input(INPUT_GET, 'Sammler',     FILTER_SANITIZE_STRING) : '';
$SammlerNr   = (isset($_GET['SammlerNr']))   ? filter_input(INPUT_GET, 'SammlerNr',   FILTER_SANITIZE_STRING) : '';
$geo_general = (isset($_GET['geo_general'])) ? filter_input(INPUT_GET, 'geo_general', FILTER_SANITIZE_STRING) : '';
$geo_region  = (isset($_GET['geo_region']))  ? filter_input(INPUT_GET, 'geo_region',  FILTER_SANITIZE_STRING) : '';
$nation_engl = (isset($_GET['nation_engl'])) ? filter_input(INPUT_GET, 'nation_engl', FILTER_SANITIZE_STRING) : '';
$source_name = (isset($_GET['source_name'])) ? filter_input(INPUT_GET, 'source_name', FILTER_SANITIZE_STRING) : '';
$collection  = (isset($_GET['collection']))  ? filter_input(INPUT_GET, 'collection',  FILTER_SANITIZE_STRING) : '';

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Cache-Control: post-check=0, pre-check=0", false);

?><!DOCTYPE html>
<html>
  <head>
    <title>JACQ - Virtual Herbaria</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="description" content="FW4 DW4 HTML">
    <link type="text/css" rel="stylesheet" href="assets/gfont/gfont.css">
    <link type="text/css" rel="stylesheet" href="assets/materialize/css/materialize.min.css"  media="screen"/>
    <link type="text/css" rel="stylesheet" href="assets/fontawesome/css/all.css">
    <link type="text/css" rel="stylesheet" href="assets/custom/styles/jacq.css"  media="screen"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel="shortcut icon" href="JACQ_LOGO.png"/>
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
  <body>
    <div id="navbar" class="navbar-fixed">
      <nav class="nav-extended">
        <div class="nav-wrapper">
          <a href="#" class="brand-logo center"><img src="assets/images/JACQ_LOGO.png" alt="JACQ Logo"></a>
        </div>
        <div class="nav-content">
          <ul class="tabs">
            <li class="tab"><a class="active" href="#home">Home</a></li>
            <li class="tab"><a href="#database">Database</a></li>
            <li class="tab"><a href="#collections">Collections</a></li>
            <li class="tab"><a href="#systems">Reference Systems</a></li>
          </ul>
        </div>
      </nav>
    </div>
    <div class="container">
      <div id="home" class="row">
        <?php include "inc/index_home.php"; ?>
      </div>
      <div id="database">
        <?php include "inc/index_database.php"; ?>
      </div>
      <div id="collections">
        <?php include "inc/index_collections.php"; ?>
      </div>
      <div id="systems" class="row">
          <?php include "inc/index_systems.php"; ?>
      </div>
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

    <?php
    if(defined('START_SEARCH') && START_SEARCH === true) {
        ?>
        <script type="text/javascript">
            $(document).ready(function() {
                $('#ajax_f').trigger('submit');
                $('.tabs').tabs('select', 'database');
            });
        </script>
        <?php
    }
    ?>

  </body>
</html>
