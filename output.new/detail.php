<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Cache-Control: post-check=0, pre-check=0", false);

require("inc/functions.php");
require_once('inc/imageFunctions.php');

if (isset($_GET['ID'])) {
    $ID = intval(filter_input(INPUT_GET, 'ID', FILTER_SANITIZE_NUMBER_INT));
} else {
    $ID = 0;
}


/********************************
 * functions
 ********************************/

function protolog($row)
{
    $text = "";
    if ($row['suptitel']) {
        $text .= "in " . $row['autor'] . ": " . $row['suptitel'] . " ";
    }
    if ($row['periodicalID']) {
        $text .= $row['periodical'];
    }
    $text .= " " . $row['vol'];
    if ($row['part']) {
        $text .= " (" . $row['part'] . ")";
    }
    $text .= ": " . $row['paginae'];
    if ($row['figures']) {
        $text .= "; " . $row['figures'];
    }
    $text .= " (" . $row['jahr'] . ")";

    return $text;
}



function makeCell($text)
{
    if ($text) {
        echo nl2br($text);
    } else {
        echo "&nbsp;";
    }
}

function makeCellWithLink($text)
{
    if ($text) {
        echo "<a href=\"" . $text . '" target="_blank">' . $text . '</a><br/>';
    } else {
        echo "&nbsp;";
    }
}

function makeTypus($ID) {
    global $dbLink;

    $sql = "SELECT typus_lat, tg.genus,
             ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
             ta4.author author4, ta5.author author5,
             te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
             te4.epithet epithet4, te5.epithet epithet5,
             ts.synID, ts.taxonID, ts.statusID, tst.typified_by_Person, tst.typified_Date
            FROM (tbl_specimens_types tst, tbl_typi tt, tbl_tax_species ts)
             LEFT JOIN tbl_tax_authors ta ON ta.authorID=ts.authorID
             LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID=ts.subspecies_authorID
             LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID=ts.variety_authorID
             LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID=ts.subvariety_authorID
             LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID=ts.forma_authorID
             LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID=ts.subforma_authorID
             LEFT JOIN tbl_tax_epithets te ON te.epithetID=ts.speciesID
             LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID=ts.subspeciesID
             LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID=ts.varietyID
             LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID=ts.subvarietyID
             LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID=ts.formaID
             LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID=ts.subformaID
             LEFT JOIN tbl_tax_genera tg ON tg.genID=ts.genID
            WHERE tst.typusID=tt.typusID
             AND tst.taxonID=ts.taxonID
             AND specimenID=" . intval($ID) . " ORDER by tst.typified_Date DESC";
    $result = $dbLink->query($sql);

    $text = "";
    while ($row = $result->fetch_array()) {
        if ($row['synID']) {
            $sql3 = "SELECT ts.statusID, tg.genus,
                      ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
                      ta4.author author4, ta5.author author5,
                      te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
                      te4.epithet epithet4, te5.epithet epithet5
                     FROM tbl_tax_species ts
                      LEFT JOIN tbl_tax_authors ta ON ta.authorID=ts.authorID
                      LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID=ts.subspecies_authorID
                      LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID=ts.variety_authorID
                      LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID=ts.subvariety_authorID
                      LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID=ts.forma_authorID
                      LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID=ts.subforma_authorID
                      LEFT JOIN tbl_tax_epithets te ON te.epithetID=ts.speciesID
                      LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID=ts.subspeciesID
                      LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID=ts.varietyID
                      LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID=ts.subvarietyID
                      LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID=ts.formaID
                      LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID=ts.subformaID
                      LEFT JOIN tbl_tax_genera tg ON tg.genID=ts.genID
                     WHERE taxonID=" . $row['synID'];
            $result3 = $dbLink->query($sql3);
            $row3 = $result3->fetch_array();
            $accName = taxonWithHybrids($row3);
        } else {
            $accName = "";
        }

        $sql2 = "SELECT l.suptitel, la.autor, l.periodicalID, lp.periodical, l.vol, l.part,
                  ti.paginae, ti.figures, l.jahr
                 FROM tbl_tax_index ti
                  INNER JOIN tbl_lit l ON l.citationID=ti.citationID
                  LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID=l.periodicalID
                  LEFT JOIN tbl_lit_authors la ON la.autorID=l.editorsID
                 WHERE ti.taxonID='" . $row['taxonID'] . "'";
        $result2 = $dbLink->query($sql2);

        $text .= "<tr><td nowrap align=\"right\">" . $row['typus_lat'] . " for&nbsp;</td><td><b>" . taxonWithHybrids($row) . "</b></td></tr>";
        $text .="<tr><td nowrap align=\"right\"></td><td>Typified by:&nbsp;<b>" . $row['typified_by_Person'] . "&nbsp;" . $row['typified_Date'] ."</b></td></tr>";
        while ($row2 = $result2->fetch_array()) {
            $text .= "<tr><td></td><td><b>" . protolog($row2) . "</b></td></tr>";
        }
        if (strlen($accName) > 0) {
            $text .= "<tr><td></td><td><b>Current Name: <i>$accName</i></b></td></tr>";
        }
    }
    $text .= "";

    return $text;
}

/**********************************
 * main query
 **********************************/
$specimen = $dbLink->query("SELECT s.specimen_ID, tg.genus, c.Sammler, c.SammlerID, c.HUH_ID, c.VIAF_ID, c.WIKIDATA_ID,c.ORCID, c2.Sammler_2, ss.series, s.series_number,
                             s.Nummer, s.alt_number, s.Datum, s.Fundort, s.det, s.taxon_alt, s.Bemerkungen, s.typified,
                             s.digital_image, s.digital_image_obs, s.HerbNummer, s.ncbi_accession, s.observation,
                             s.Coord_W, s.W_Min, s.W_Sec, s.Coord_N, s.N_Min, s.N_Sec,
                             s.Coord_S, s.S_Min, s.S_Sec, s.Coord_E, s.E_Min, s.E_Sec,
                             n.nation_engl, p.provinz, s.Fundort, tf.family, tsc.cat_description, s.taxonID taxid,
                             mc.collection, mc.collectionID, mc.source_id, mc.coll_short, mc.coll_gbif_pilot,
                             tid.imgserver_type, tid.imgserver_IP, tid.iiif_capable, tid.iiif_proxy, tid.iiif_dir, tid.HerbNummerNrDigits,
                             ta.author, ta1.author author1, ta2.author author2, ta3.author author3, ta4.author author4, ta5.author author5,
                             te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3, te4.epithet epithet4, te5.epithet epithet5,
                             ts.synID, ts.taxonID, ts.statusID
                            FROM tbl_specimens s
                             LEFT JOIN tbl_specimens_series ss           ON ss.seriesID = s.seriesID
                             LEFT JOIN tbl_management_collections mc     ON mc.collectionID = s.collectionID
                             LEFT JOIN tbl_img_definition tid            ON tid.source_id_fk = mc.source_id
                             LEFT JOIN tbl_geo_nation n                  ON n.NationID = s.NationID
                             LEFT JOIN tbl_geo_province p                ON p.provinceID = s.provinceID
                             LEFT JOIN tbl_collector c                   ON c.SammlerID = s.SammlerID
                             LEFT JOIN tbl_collector_2 c2                ON c2.Sammler_2ID = s.Sammler_2ID
                             LEFT JOIN tbl_tax_species ts                ON ts.taxonID = s.taxonID
                             LEFT JOIN tbl_tax_authors ta                ON ta.authorID = ts.authorID
                             LEFT JOIN tbl_tax_authors ta1               ON ta1.authorID = ts.subspecies_authorID
                             LEFT JOIN tbl_tax_authors ta2               ON ta2.authorID = ts.variety_authorID
                             LEFT JOIN tbl_tax_authors ta3               ON ta3.authorID = ts.subvariety_authorID
                             LEFT JOIN tbl_tax_authors ta4               ON ta4.authorID = ts.forma_authorID
                             LEFT JOIN tbl_tax_authors ta5               ON ta5.authorID = ts.subforma_authorID
                             LEFT JOIN tbl_tax_epithets te               ON te.epithetID = ts.speciesID
                             LEFT JOIN tbl_tax_epithets te1              ON te1.epithetID = ts.subspeciesID
                             LEFT JOIN tbl_tax_epithets te2              ON te2.epithetID = ts.varietyID
                             LEFT JOIN tbl_tax_epithets te3              ON te3.epithetID = ts.subvarietyID
                             LEFT JOIN tbl_tax_epithets te4              ON te4.epithetID = ts.formaID
                             LEFT JOIN tbl_tax_epithets te5              ON te5.epithetID = ts.subformaID
                             LEFT JOIN tbl_tax_genera tg                 ON tg.genID = ts.genID
                             LEFT JOIN tbl_tax_families tf               ON tf.familyID = tg.familyID
                             LEFT JOIN tbl_tax_systematic_categories tsc ON tf.categoryID = tsc.categoryID
                            WHERE specimen_ID = '" . intval($ID) . "'")
                   ->fetch_array();
    if (($specimen['digital_image'] || $specimen['digital_image_obs']) && $specimen['source_id'] == '1') {
        // for now, special treatment for pheidra is needed when wu has images
        $pheidra = false;
        $pheidraUrl = "";

        $picname = sprintf("WU%0" . $specimen['HerbNummerNrDigits'] . ".0f", str_replace('-', '', $specimen['HerbNummer']));
        $ch = curl_init("https://app05a.phaidra.org/viewer/" . $picname);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $curl_response = curl_exec($ch);
        if ($curl_response) {
            $info = curl_getinfo($ch);
            if ($info['http_code'] == 200) {
                $pheidra = true;
                $pheidraUrl = "phaidra_ptlp.php?type=manifests&id={$picname}";
            }
        }
        curl_close($ch);
    }

?><!DOCTYPE html>
<html>
<head>
  <title>JACQ - Virtual Herbaria</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta name="description" content="FW4 DW4 HTML">
  <!--<link type="text/css" rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">-->
  <link type="text/css" rel="stylesheet" href="assets/materialize/css/materialize.min.css"  media="screen"/>
  <link type="text/css" rel="stylesheet" href="assets/fontawesome/css/all.css">
  <link type="text/css" rel="stylesheet" href="assets/custom/styles/jacq.css"  media="screen"/>
  <?php if ($pheidra): ?>
      <link rel='stylesheet' href='https://fonts.googleapis.com/css?family=Roboto:300,400,500'>
      <script type='text/javascript' src='https://app05a.phaidra.org/mirador.min.js'></script>
  <?php endif; ?>
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
  </nav>
</div>

<?php

$taxon = taxonWithHybrids($specimen);

//$sammler = collection($specimen['Sammler'], $specimen['Sammler_2'], $specimen['series'], $specimen['series_number'], $specimen['Nummer'], $specimen['alt_number'], $specimen['Datum']);
$sammler = rdfcollection($specimen,true);

if ($specimen['ncbi_accession']) {
    $sammler .=  " &mdash; " . $specimen['ncbi_accession']
              .  " <a href='http://www.ncbi.nlm.nih.gov/entrez/query.fcgi?db=Nucleotide&cmd=search&term=" .  $specimen['ncbi_accession'] . "' target='_blank'>"
              .  "<img border='0' height='16' src='images/ncbi.gif' width='14'></a>";
}
?>
<div id="details" class="container">
  <table class="striped">
    <tr>
      <td align="right" width="30%">Stable identifier</td>
      <td>
        <b><?php makeCellWithLink(StableIdentifier($specimen['source_id'], $specimen['HerbNummer'], $specimen['specimen_ID'])); ?></b>
      </td>
    </tr>
    <tr>
      <td align="right" width="30%">Collection Herb.#</td>
      <td><b>
        <?php makeCell(collectionID($specimen)); ?>
         </b>
        </td>
    </tr>
    <tr>
      <td align="right">Stored under taxonname</td>
      <td><b>
        <?php makeCell($taxon); ?>
        </b>&nbsp;<a href="http://www.tropicos.org/NameSearch.aspx?name=<?php echo urlencode($specimen['genus'] . " "  . $specimen['epithet']); ?>&exact=true" title="Search in tropicos" target="_blank"><img alt="tropicos" src="images/tropicos.png" border="0" width="16" height="16"></a>
        <?php makeCell(getTaxonAuth($specimen['taxid'])); ?>
      </td>
    </tr>
    <tr>
      <td align="right">Family</td>
      <td><b>
        <?php makeCell($specimen['family']); ?>
        </b></td>
    </tr>
    <tr>
      <td align="right">Det./rev./conf./assigned</td>
      <td>
        <b><?php makeCell($specimen['det']); ?></b>
      </td>
    </tr>
    <tr>
      <td align="right">Ident. history</td>
      <td>
          <b><?php makeCell($specimen['taxon_alt']); ?></b>
      </td>
    </tr>
    <?php
    $typusText = makeTypus($ID);
    if (strlen($typusText) > 0) {
      makeCell($typusText);
    }
    ?>
    <tr>
      <td align="right">Collector</td>
      <td>
        <b><?php makeCell($sammler);?></b>
      </td>
    </tr>
    <tr>
      <td align="right">Date</td>
      <td align="left">
        <b><?php makeCell($specimen['Datum']); ?></b>
      </td>
    </tr>
    <tr>
      <td align="right">Location</td>
      <td><b>
        <?php
        $text = $specimen['nation_engl'];
        if (strlen(trim($specimen['provinz'])) > 0) {
            $text .= " / " . trim($specimen['provinz']);
        }
        if ($specimen['Coord_S'] > 0 || $specimen['S_Min'] > 0 || $specimen['S_Sec'] > 0) {
            $lat = -($specimen['Coord_S'] + $specimen['S_Min'] / 60 + $specimen['S_Sec'] / 3600);
        } else if ($specimen['Coord_N'] > 0 || $specimen['N_Min'] > 0 || $specimen['N_Sec'] > 0) {
            $lat = $specimen['Coord_N'] + $specimen['N_Min'] / 60 + $specimen['N_Sec'] / 3600;
        } else {
            $lat = 0;
        }
        if ($specimen['Coord_W'] > 0 || $specimen['W_Min'] > 0 || $specimen['W_Sec'] > 0) {
            $lon = -($specimen['Coord_W'] + $specimen['W_Min'] / 60 + $specimen['W_Sec'] / 3600);
        } else if ($specimen['Coord_E'] > 0 || $specimen['E_Min'] > 0 || $specimen['E_Sec'] > 0) {
            $lon = $specimen['Coord_E'] + $specimen['E_Min'] / 60 + $specimen['E_Sec'] / 3600;
        } else {
            $lon = 0;
        }
        if ($lat != 0 || $lon != 0) {
            $text .= " &mdash; " . round($lat,2) . "&deg; / " . round($lon,2) . "&deg; ";

            $point['lat'] = dms2sec($specimen['Coord_S'], $specimen['S_Min'], $specimen['S_Sec'], $specimen['Coord_N'], $specimen['N_Min'], $specimen['N_Sec']) / 3600.0;
            $point['lng'] = dms2sec($specimen['Coord_W'], $specimen['W_Min'], $specimen['W_Sec'], $specimen['Coord_E'], $specimen['E_Min'], $specimen['E_Sec']) / 3600.0;
            $url = "https://www.jacq.org/detail.php?ID=" . $specimen['specimen_ID'];
            $txt = "<div style=\"font-family: Arial,sans-serif; font-weight: bold; font-size: medium;\">"
                 . htmlspecialchars(taxonWithHybrids($specimen))
                 . "</div>"
                 . "<div style=\"font-family: Arial,sans-serif; font-size: small;\">"
                 . htmlentities(collection($specimen['Sammler'], $specimen['Sammler_2'], $specimen['series'], $specimen['series_number'], $specimen['Nummer'], $specimen['alt_number'], $specimen['Datum']), ENT_QUOTES | ENT_HTML401) . " / "
                 . $specimen['Datum'] . " / ";
            if ($specimen['typusID']) {
                $txt .= htmlspecialchars($specimen['typusID']) . " / ";
            }
            $txt .= htmlspecialchars(collectionItem($specimen['collection'])) . " " . htmlspecialchars($specimen['HerbNummer']) . "</div>";
            $txt = strtr($txt, array("\r" => '', "\n" => ''));
            $point['txt'] = "<a href=\"$url\" target=\"_blank\">$txt</a>";
            echo "<div id='map'></div>";
            ?>
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
            <?php
        }
        if (strlen($text) > 0) {
            echo $text;
        } else {
            echo "&nbsp;";
        }
        ?>
      </b>
    </td>
    </tr>
    <tr>
      <td align="right">Label</td>
      <td><b>
        <?php makeCell($specimen['Fundort']); ?>
      </b></td>
    </tr>
    <tr>
      <td align="right">Annotations</td>
      <td><b>
        <?php
            if ($specimen['source_id'] == '35'){
                makeCell((preg_replace("#<a .*a>#", "", $specimen['Bemerkungen'])));
            } else {
                makeCell($specimen['Bemerkungen']);
            }
        ?>
      </b></td>
    </tr>
    <?php
        if (($specimen['source_id'] == '29' || $specimen['source_id'] == '6') && $_CONFIG['ANNOSYS']['ACTIVE'] ){
            echo "<tr>";
            // create new id object
            $id = new MyTripleID($specimen['HerbNummer']);
            // create new AnnotationQuery object
            $query = new AnnotationQuery($serviceUri);
            // fetch annotation metadata
            $annotations = $query->getAnnotationMetadata($id);
            // build URI for new annotation
            if ($specimen['source_id'] == '29') {
                $newAnnoUri = $query->newAnnotationUri("http://ww3.bgbm.org/biocase/pywrapper.cgi?dsa=Herbar&", $id);
            } else { //$specimen['source_id'] == '6'
                $newAnnoUri = $query->newAnnotationUri("http://131.130.131.9/biocase/pywrapper.cgi?dsa=gbif_w&", $id);
            }
            echo "<td align='right'>Annosys annotations<br/>"
               . "<a href='". $newAnnoUri . "' target='_blank'>Add annotation</a>"
               . "<br/>"
               . "</td>"
               . "<td><b>";
            // generate table
            $table = generateAnnoTable($annotations);
            // output
            echo $table;
            echo "</b></td>";
            echo "</tr>";
        }
    ?>

    <tr>
      <td align="left" colspan="2">
        <table border='0'>
          <tr>

<?php
if (($specimen['digital_image'] || $specimen['digital_image_obs']) && !$pheidra) {
    if ($specimen['imgserver_type'] == 'bgbm') {
        echo "<td valign='top' align='center'>\n";
        if ($specimen['iiif_capable']) {
            $manifest = '';
            if ($specimen['source_id'] == '32'){
                $manifest = getManifestURI(getStableIdentifier($specimen['specimen_ID']));
            } else {
                // force https to always call iiif images with https
                $manifest = str_replace('http:', 'https:', StableIdentifier($specimen['source_id'], $specimen['HerbNummer'], $specimen['specimen_ID'])) . '/manifest.json';
            }
            echo "<iframe title='Mirador' width='100%' height='800px' "
               . "src='https://" . $specimen['iiif_proxy'] . $specimen['iiif_dir'] . "/?manifest=$manifest' "
               . "allowfullscreen='true' webkitallowfullscreen='true' mozallowfullscreen='true'></iframe>";
        } else {
            $options = 'filename=' . rawurlencode(basename($specimen['specimen_ID'])) . '&sid=' . $specimen['specimen_ID'];
            echo "<a href='image.php?{$options}&method=show' target='imgBrowser'><img src='image.php?{$options}&method=thumb border='2'></a><br>"
               . "(<a href='image.php{$options}&method=show'>Open viewer</a>)";
        }
        echo "</td>\n";
//    'baku' is depricated and no loner used
//    } elseif ($specimen['imgserver_type'] == 'baku') {
//        $options = 'filename=' . rawurlencode(basename($specimen['specimen_ID'])) . '&sid=' . $specimen['specimen_ID'];
//        echo "<td valign='top' align='center'>"
//           . "<a href='image.php?{$options}&method=show' target='imgBrowser'><img src='image.php?{$options}&method=thumb border='2'></a><br>"
//           . "(<a href='image.php?{$options}&method=show' target='imgBrowser'>Open viewer</a>)"
//           . "</td>";
    } elseif ($specimen['imgserver_type'] == 'djatoka') {
        $picdetails = getPicDetails($specimen['specimen_ID']);
        $transfer   = getPicInfo($picdetails);
        if ($transfer) {
            if (count($transfer['pics']) > 0) {
                foreach ($transfer['pics'] as $v) {
                    $options = 'filename=' . rawurlencode(basename($v)) . '&sid=' . $specimen['specimen_ID'];
                    echo "<td valign='top' align='center'>\n"
                       . "<a href='image.php?{$options}&method=show' target='imgBrowser'><img src='image.php?{$options}&method=thumb' border='2'></a>\n"
                       . "<br>\n"
                       . "(<a href='image.php?{$options}&method=download&format=jpeg2000'>JPEG2000</a>, <a href='image.php?{$options}&method=download&format=tiff'>TIFF</a>)\n"
                       . "</td>\n";
                }
            } else {
                echo "no pictures found\n";
            }
            if (trim($transfer['output'])) {
                echo nl2br("\n" . $transfer['output'] . "\n");
            }
        } else {
            echo "transmission error\n";
        }
    } else {
        echo "no pictures available\n";
    }
}
?>
          </tr>
        </table>
      </td>
    </tr>
  </table>
  <div class="divider"></div>
  <div id="footer" style="position: absolute;">
    <a href="imprint_citation_privacy.htm">Imprint | Citation | Privacy</a>
  </div>
</div>
<?php if ($pheidra): ?>

<div id="jacq_mirador" style="position: relative; height: 800px;"></div>
<script type="text/javascript">
 var miradorInstance = Mirador.viewer({
   id: 'jacq_mirador',
   windows: [{
     manifestId: '<?php echo $pheidraUrl; ?>',
     canvasId: 'https://app05a.phaidra.org/manifests/<?php echo $picname; ?>/c/<?php echo $picname; ?>-01',
     thumbnailNavigationPosition: 'far-right',
   }],
   window: {
    allowClose: false,
    allowMaximize: false,
    allowFullscreen: true,
    allowTopMenuButton: true,
    defaultSideBarPanel: 'info',
    sideBarOpenByDefault: false,
    views: [
      { key: 'single' },
      { key: 'gallery' }
    ]
   },
   workspace: {
    showZoomControls: true,
    type: 'mosaic'
   },
   workspaceControlPanel: {
    enabled: false
   }
 });
</script>

<?php endif; ?>
<script type="text/javascript" src="assets/jquery/jquery.min.js"></script>
<script type="text/javascript" src="assets/materialize/js/materialize.min.js"></script>
<script type="text/javascript" src="assets/custom/scripts/jacq.js"></script>
</body>
</html>