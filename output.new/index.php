<?php
define('INDEX_START', true);
if(!empty($_GET)) {
    // someone followed an external link to a direct search, so let the script click the search button automatically
    unset($_GET['search']);
    define('START_SEARCH', true);
}

session_start();
require("inc/functions.php");
require_once ("inc/xajax/xajax.inc.php");
$xajax = new xajax("ajax/searchServer.php");
$xajax->registerFunction("getCollection");
$xajax->registerFunction("getCountry");
$xajax->registerFunction("getProvince");

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
    <link type="text/css" rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
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
        <div class="col s12">
          <div class="divider"></div>
          <p>JACQ is the jointly administered herbarium management system and specimen database of the following herbaria: ADMONT, B, BAK, BATU, BRNU, CBH, CHER, DR, ERE, FT, GAT, GJO, GZU, HAL, HERZ, JE, KIEL, KFTA, KUFS, LAGU, LECB, LW, LWKS, LWS, LZ, MJG, NBSI, OLD, PI, PIAGR, PRC, TBI, TGU, TMRC, TUB, UBT, W, WU and WUP.</p>
          <p>Listed Acronyms follow the <a href="http://sweetgum.nybg.org/science/ih/" target="_blank">Index Herbariorum Abbreviations</a>. For requests and comments on specimens like identifications, typification, and comments please contact the corresponding Director/Curator listed in the Index Herbariorum.</p>


          <div class="divider"></div>
            <ul class="collapsible">
            <li>
              <div class="collapsible-header"><i class="fas fa-angle-down"></i>External Resources Identifiers and Links</div>
              <div class="collapsible-body">
                <p>The JACQ system has been populated with identifiers from external resources for people and scientific names. On the basis of these identifiers external portals can be reached directly by clicking on the respective icon(s) for a given entity.</p>
                <h6><b>Scientific Names</b></h6>
                <ul>
                  <li><a href="https://ipni.org/" target="_blank"><img src="assets/images/serviceID1_logo.png" height="20" alt="IPNI Logo"></a> IPNI - International Plant Names Index / Royal Botanic Gardens Kew - Richmond, Enland</li>
                  <li><a href="http://powo.science.kew.org/" target="_blank"><img src="assets/images/serviceID49_logo.png" height="20" alt="IPNI Logo"></a> Plants of the World Online / Royal Botanic Gardens Kew - Richmond, England</li>
                  <li><a href="http://www.indexfungorum.org/" target="_blank"><img src="assets/images/serviceID3_logo.png" height="20" alt="IPNI Logo"></a> Index Fungorum / Royal Botanic Gardens Kew - Richmond, England</li>
                  <li><a href="https://www.europlusmed.org/" target="_blank"><img src="assets/images/serviceID10_logo.png" height="20" alt="Euro+Med Logo"></a> Euro+Med PlantBase / Botanischer Garten und Botanisches Museum - Berlin, Germany</li>
                  <li><a href="https://www.tropicos.org/nameSearch" target="_blank"><img src="assets/images/serviceID2_logo.png" height="20" alt="Western Australia Flora Logo"></a> Tropicos / Missouri Botanical Garden - Saint Louis, MO, USA</li>
                  <li><a href="http://reflora.jbrj.gov.br/reflora/listaBrasil/ConsultaPublicaUC/ConsultaPublicaUC.do#CondicaoTaxonCP" target="_blank"><img src="assets/images/serviceID21_logo.png" height="20" alt="REFLORA Logo"></a> REFLORA Flora do Brasil 2020 / Jardim Botânico do Rio de Janeiro - Rio de Janeiro, Brasil</li>
                  <li><a href="https://www.gbif.org/species/search?q=" target="_blank"><img src="assets/images/serviceID51_logo.png" height="20" alt="Tropicos Logo"></a> GBIF / Global Biodiversity Information Facility - Copenhagen, Denmark</li>
                  <li><a href="http://portal.cybertaxonomy.org/flora-cuba/" target="_blank"><img src="assets/images/serviceID45_logo.png" height="20" alt="Flora Cuba Logo"></a> Spermatophyta and Pteridophyta of Cuba / Botanischer Garten und Botanisches Museum - Berlin, Germany</li>
                  <li><a href="https://florabase.dpaw.wa.gov.au/search/advanced" target="_blank"><img src="assets/images/serviceID11_logo.png" height="20" alt="Western Australia Flora Logo"></a> FloraBase the Western Australia Flora / Western Australian Herbarium - Kensington, Australia</li>
                </ul>

                <h6><b>Persons</b></h6>
                <ul>
                  <li><a href="https://www.wikidata.org/wiki/Wikidata:Main_Page" target="_blank"><img src="assets/images/wikidata.png" width="20" alt="WIKIDATA Logo"></a> WIKIDATA / WIKIMEDIA Foundation - San Francisco, CA, USA</li>
                  <li><a href="https://kiki.huh.harvard.edu/databases/botanist_index.html" target="_blank"><img src="assets/images/huh.png" height="20"></a> Harvard University Herbaria - Botanists / Harvard University Herbaria - Cambridge, MA, USA</li>
                  <li><a href="https://viaf.org/" target="_blank"><img src="assets/images/viaf.png" width="20" alt="VIAF Logo"></a> Virtual International Authority File - VIAF / OCLC, Dublin, OH, USA</li>
                  <li><a href="https://orcid.org/" target="_blank"><img src="assets/images/orcid.logo.icon.svg" width="20" alt="ORCID Logo"></a> ORCID / Washington, DC, & Columbus, OH, USA</li>
                  <li><a href="https://bionomia.net/" target="_blank"><img src="assets/images/bionomia_logo.png" width="20" alt="Bionomia Logo"></a> Bionomia / David Shorthouse - Ottawa, ON, Canada</li>
                </ul>
              </div>
            </li>
          </ul>

            <ul class="collapsible">
            <li>
              <div class="collapsible-header"><i class="fas fa-angle-down"></i>Other Virtual Herbaria & Aggregators</div>
              <div class="collapsible-body">
                <h6><strong>Europe</strong></h6>
                <ul>
                  <li>AAU / <a href="https://www.aubot.dk/search_form.php" target="_blank">Aarhus University - Aarhus, Denmark</a></li>
                  <li>BM / <a href="https://data.nhm.ac.uk/dataset/56e711e6-c847-4f99-915a-6894bb5c5dea/resource/05ff2255-c38a-40c9-b657-4ccb55ab2feb?view_id=6b611d29-1dcf-4c60-b6b5-4cbb69fdf4fe&filters=collectionCode%3ABOT" target="_blank">NHM - London, England</a></li>
                  <li>BP / <a href="https://gallery.hungaricana.hu/en/Herbarium/" target="_blank">Hungarian Natural History Museum - Budapest, Hungary</a></li>
                  <li>BR / <a href="http://www.br.fgov.be/research/COLLECTIONS/HERBARIUM/advancedsearch.php" target="_blank">Botanic Garden - Meise, Belgium</a></li>
                  <li>E / <a href="https://data.rbge.org.uk/search/herbarium/" target="_blank">Royal Botanic Garden Edinburgh - Edinburgh, Scotland</a></li>
                  <li>K / <a href="http://apps.kew.org/herbcat/navigator.do" target="_blank">Royal Botanic Garden Kew - Richmond, England</a></li>
                  <li>L, U, WAG / <a href="https://bioportal.naturalis.nl/" target="_blank">Bioportal Naturalis - The Netherlands</a></li>
                  <li>P & PC / <a href="https://science.mnhn.fr/institution/mnhn/item/search/form" target="_blank">MNHN - Paris, France</a></li>
                  <li>Z, ZT / <a href="https://www.herbarien.uzh.ch/de/belegsuche.html" target="_blank">Zürcher Herbarien - Zurich, Switzerland</a></li>
                </ul>

                <h6><strong>North America</strong></h6>
                <ul>
                  <li>A, AMES, ECON, GH / <a href="https://kiki.huh.harvard.edu/databases/specimen_index.html" target="_blank">Harvard University Herbaria - Cambridge, MA, USA</a></li>
                  <li>F / <a href="https://collections-botany.fieldmuseum.org/list" target="_blank">Field Museum - Botany Collections, Chicago, IL, USA</a></li>
                  <li>MO / <a href="http://www.tropicos.org/SpecimenSearch.aspx" target="_blank">Missouri Botanical Garden, St. Louis, MO, USA</a></li>
                  <li>NY / <a href="http://sweetgum.nybg.org/science/vh/" target="_blank">New York Botanical Gardens - New York, NY, USA</a></li>
                  <li>US / <a href="https://collections.nmnh.si.edu/search/botany/" target="_blank">Smithsonian Institution - Washington, DC, USA</a></li>
                  <li></li>
                </ul>

                <h6><strong>South America</strong></h6>
                <ul>
                  <li>COL / <a href="http://www.biovirtual.unal.edu.co/en/collections/search/plants/" target="_blank">Herbario Nacional - Bogota, Colombia</a></li>
                </ul>

                <h6><strong>Asia</strong></h6>
                <ul>
                  <li>HK / <a href="https://www.herbarium.gov.hk/search_form.aspx" target="_blank">Hong Kong Agriculture, Fisheries, and Conservation Department - Hong Kong, PR China</a></li>
                  <li>PE / <a href="http://pe.ibcas.ac.cn/en/" target="_blank">Chinese Academy of Sciences Inst. Botany - Beijing, PR China</a></li>
                </ul>

                <h6><strong>║→ Aggregators ←║</strong></h6>
                <ul>
                  <li>Brasil / <a href="http://inct.splink.org.br/" target="_blank">INCT - Herbário Virtual da Flora e dos Fungos - CRIA</a></li>
                  <li>Brasil / <a href="http://reflora.jbrj.gov.br/reflora/herbarioVirtual/ConsultaPublicoHVUC/ConsultaPublicoHVUC.do" target="_blank">REFLORA - JBRJ</a></li>
                  <li>PR China / <a href="http://www.cvh.ac.cn/en" target="_blank">Chinese Virtual Herbarium of China</a></li>
                  <li>Germany / <a href="http://vh.gbif.de/vh/static/en_startpage.html" target="_blank">Virtual Herbarium Germany</a></li>
                  <li>Sweden / <a href="http://herbarium.emg.umu.se/" target="_blank">Sweden's Virtual Herbarium</a></li>
                  <li>USA / <a href="https://portal.idigbio.org/" target="_blank">Integrated Digitized Biocollections (iDigBio)</a></li>
                </ul>
              </div>
            </li>
          </ul>

            <ul class="collapsible">
            <li>
              <div class="collapsible-header"><i class="fas fa-angle-down"></i>Acknowledgements</div>
              <div class="collapsible-body">
                  <div id="partners">
                  <div class="partnerlogo"><a href="https://www.univie.ac.at/" target="_blank"><img src="assets/images/univie.png" alt="UNIVIE Logo"></a></div>
                  <div class="partnerlogo"><a href="https://www.oeaw.ac.at/" target="_blank"><img src="assets/images/oeaw.png" alt="OEAW Logo"></a></div>
                  <div class="partnerlogo"><a href="https://www.nhm-wien.ac.at/" target="_blank"><img src="assets/images/nhm_wien.png" alt="NHM Wien Logo"></a></div>
                  <div class="partnerlogo"><a href="https://www.bgbm.org/" target="_blank"><img src="assets/images/logo_bgbm_rgb.png" alt="BGBM Logo"></a></div>
                  <div class="partnerlogo"><a href="https://www.cetaf.org" target="_blank"><img src="assets/images/cetaf_logo_cmyk.png" alt="CETAF Logo"></a></div>
                </div>
                <div id="partners">
                  <div class="partnerlogo"><a href="http://www.gbif.org" target="_blank"><img src="assets/images/GBIF-2015-dotorg-stacked.png" alt="GBIF Logo"></a></div>
                  <div class="partnerlogo"><a href="http://www.biocase.org" target="_blank"><img src="assets/images/biocase_logo.png" alt="Biocase Logo"></a></div>
                  <div class="partnerlogo"><a href="http://www.tdwg.org" target="_blank"><img src="assets/images/tdwg.png" alt="TDWG Logo"></a></div>
                  <div class="partnerlogo"><a href="http://www.eu-nomen.eu/portal/" target="_blank"><img src="assets/images/PESI_logo_small.gif" alt="PESI Logo"></a></div>
                  <div class="partnerlogo"><a href="https://www.sp2000.org/home" target="_blank"><img src="assets/images/sp2000.png" alt="Species 2000 Logo"></a></div>
                  </div>
                <div id="partners">
                  <div class="partnerlogo"><a href="https://mellon.org/" target="_blank"><img src="assets/images/mellon_foundation_logo.png" alt="Mellon Foundation Logo"></a></div>
                  <div class="partnerlogo"><img src="assets/images/eu_ictpsp.png" alt="EU ICT PSP"></div>
                  <div class="partnerlogo"><a href="https://www.synthesys.info/" target="_blank"><img src="assets/images/synthesys-plus-logo.png" alt="SYNTHESYS+ Logo"></a></div>
                  <div class="partnerlogo"><a href="https://www.dissco.eu/" target="_blank"><img src="assets/images/dissco-logo.png" alt="DiSSCo Logo"></a></div>
                  <div class="partnerlogo"><img src="assets/images/dissco-prepare-logo.png" alt="DiSSCo Prepare Logo"></div>
                  <div class="partnerlogo"><a href="https://www.mobilise-action.eu/" target="_blank"><img src="assets/images/cropped-mobilise-logo-1.png" alt="MOBILISE Logo"></a></div>
                </div>
                <div id="partners">
                  <div class="partnerlogo"><a href="https://www.bundeskanzleramt.gv.at/" target="_blank"><img src="assets/images/BKA_Logo.png" alt="BKA Logo"></a></div>
                  <div class="partnerlogo"><a href="https://www.bmlrt.gv.at/" target="_blank"><img src="assets/images/BMLRT_Logo.png" alt="BMLRT Logo"></a></div>
                  <div class="partnerlogo"><a href="https://www.bmbwf.gv.at/" target="_blank"><img src="assets/images/BMBWF_Logo.png" alt="BMBWF Logo"></a></div>
                </div>
           </div>
          </li>
        </ul>
      </div>
    </div>
      <div id="database">
        <div class="row">
          <div class="col s12">
            <h5 class="tooltipped" data-position="bottom" data-tooltip="#info">Database Search <a class="modal-trigger" href="#search-info"><i class="far fa-question-circle fa-sm"></i></a></h5>
            <!-- Search Info Modal -->
            <div id="search-info" class="modal">
              <div class="modal-content">
                <h4>Search Tips</h4>
                <blockquote>
                  <p>The Search is <strong>not case sensitive</strong>.</p>
                  <p>Fields are automatically <strong>linked by AND</strong></p>
                  <p>For partial strings the <strong>% sign can be used as a wildcard</strong></p>
                  <p>Queries for a Genus can be sent as "genus name" "blank space" and the "%" sign:
                  Searchstring "Oncidum %" yields all data for Oncidium.</p>
                  <p>Typing the initial Letters for "genus" and "epithet" are sufficient as Search Criteria:
                  "p bad" yields all Taxa where genus starts with "p" and Epithet starts with "bad". Results include e.g. p badia Hepp, Peziza badia Pers. or Poa badensis Haenke ex Willd.</p>
                  <p>Search on Synonymy has been implemented for nomenclatural and taxonomic questions. If the "incl. syn." checkbox is activated (default), known nomenclatural and taxonomic synonyms will be returned with the search result</p>
                </blockquote>
              </div>
              <div class="modal-footer">
                <a href="#!" class="modal-close waves-effect waves-green btn-flat">Close</a>
              </div>
            </div>
            <div class="divider"></div>
          </div>
        </div>
        <!-- Search Form -->
          <form id="ajax_f" name="f" class="row">
               <!-- Taxon -->
              <div class="input-field col s6">
                  <?php
                  echo '<input class="searchinput" value="' . htmlspecialchars($taxon) . '"
                         placeholder="Scientific name" name="taxon" type="text">';
                  ?>
              </div>
              <!-- Family -->
              <div class="input-field col s6">
                  <?php
                  echo '<input class="searchinput" value="' . htmlspecialchars($family) . '"
                         placeholder="Family" name="family" type="text">';
                  ?>
              </div>
              <!-- Institution -->
              <div class="input-field col s6">
                  <select name="source_name">
                      <option value="" selected>Search all</option>
                      <?php
                      $result = $dbLink->query("SELECT CONCAT(`source_code`,' - ',`source_name`) herbname,`source_name`
                                                FROM `meta`
                                                WHERE `source_id`
                                                IN (
                                                  SELECT `source_id`
                                                  FROM `tbl_management_collections`
                                                  WHERE `collectionID`
                                                  IN (
                                                    SELECT DISTINCT `collectionID`
                                                    FROM `tbl_specimens`
                                                  )
                                                )
                                                ORDER BY herbname");
                      while ($row = $result->fetch_array()) {
                          echo "<option value=\"{$row['source_name']}\"";
                          if ($source_name == $row['source_name']) {
                              echo " selected";
                          }
                          echo ">{$row['herbname']}</option>\n";
                      }
                      ?>
                  </select>
              </div>
              <!-- Herbar Number -->
              <div class="input-field col s6">
                  <?php
                  echo '<input class="searchinput" value="' . htmlspecialchars($HerbNummer) . '"
                         placeholder="Herbar #" name="HerbNummer" type="text">';
                  ?>
              </div>
              <!-- Collector -->
              <div class="input-field col s6">
                  <?php
                  echo '<input class="searchinput" value="' . htmlspecialchars($Sammler) . '"
                         placeholder="Collector" name="Sammler" type="text">';
                  ?>
              </div>
              <!-- Collector Number -->
              <div class="input-field col s6">
                  <?php
                  echo '<input class="searchinput" value="' . htmlspecialchars($SammlerNr) . '"
                         placeholder="Collector #" name="SammlerNr" type="text">';
                  ?>
              </div>

              <!-- Extended Search -->
              <div class="col s12">
                  <ul class="collapsible">
                      <li>
                          <div class="collapsible-header"><i class="fas fa-angle-down fa-sm"></i>Extended Search</div>
                          <div class="collapsible-body">
                              <div class="flex-wrapper">
                                  <!-- Ident. History -->
                                  <div class="input-field">
                                      <input class="searchinput" placeholder="Ident. History" name="taxon_alt" type="text">
                                  </div>
                                  <!-- CollectionDate -->
                                  <div class="input-field">
                                      <input class="searchinput" placeholder="Collection date" name="CollDate" type="text">
                                  </div>
                                  <!-- Collection -->
                                  <div class="input-field">
                                      <select id="ajax_collection" name="collection">
                                          <option value="" selected>Search subcollection</option>
                                          <?php
                                          $result_collection = $dbLink->query("SELECT `collection`
                                                                               FROM `tbl_management_collections`
                                                                               WHERE `collectionID`
                                                                               IN (
                                                                                 SELECT DISTINCT `collectionID`
                                                                                 FROM `tbl_specimens`
                                                                               )
                                                                               ORDER BY `collection`");
                                          while ($row = $result_collection->fetch_array()) {
                                              echo "<option value=\"{$row['collection']}\"";
                                              if ($collection == $row['collection']) {
                                                  echo " selected";
                                              }
                                              echo ">{$row['collection']}</option>\n";
                                          }
                                          ?>
                                      </select>
                                  </div>
                                  <!-- Collection Number -->
                                  <div class="input-field">
                                      <input class="searchinput" placeholder="Collection #" name="CollNummer" type="text">
                                  </div>
                                  <!-- Series -->
                                  <div class="input-field">
                                      <input class="searchinput" placeholder="Series" name="series" type="text">
                                  </div>
                                  <!-- Locality -->
                                  <div class="input-field">
                                      <input class="searchinput" placeholder="Locality" name="Fundort" type="text">
                                  </div>
                                  <!-- Continent -->
                                  <div class="input-field">
                                      <select name="geo_general">
                                          <option value="" selected>Search continent</option>
                                          <?php
                                          $result_geo_general = $dbLink->query("SELECT geo_general
                                                                                FROM tbl_geo_region
                                                                                GROUP BY geo_general
                                                                                ORDER BY geo_general");
                                          while ($row = $result_geo_general->fetch_array()) {
                                              echo "<option value=\"{$row['geo_general']}\"";
                                              if ($geo_general == $row['geo_general']) {
                                                  echo " selected";
                                              }
                                              echo ">{$row['geo_general']}</option>\n";
                                          }
                                          ?>
                                      </select>
                                  </div>
                                  <!-- Series -->
                                  <div id="ajax_nation_engl" class="input-field">
                                      <input class="searchinput" placeholder="Country" name="nation_engl" type="text" value="<?php echo htmlspecialchars($nation_engl); ?>">
                                  </div>
                                  <!-- Region -->
                                  <div class="input-field">
                                      <select name="geo_region">
                                          <option value="" selected>Search region</option>
                                          <?php
                                          $result_geo_region = $dbLink->query("SELECT geo_region
                                                                               FROM tbl_geo_region
                                                                               ORDER BY geo_region");
                                          while ($row = $result_geo_region->fetch_array()) {
                                              echo "<option value=\"{$row['geo_region']}\"";
                                              if ($geo_region == $row['geo_region']) {
                                                  echo " selected";
                                              }
                                              echo ">{$row['geo_region']}</option>\n";
                                          }
                                          ?>
                                      </select>
                                  </div>
                                  <!-- State/Province -->
                                  <div id="ajax_provinz" class="input-field">
                                      <input class="searchinput" placeholder="State/Province" name="provinz" type="text">
                                  </div>
                                  <!-- Placeholder -->
                                  <div></div>
                              </div>
                          </div>
                      </li>
                  </ul>
              </div>
              <!-- All Records/Type Records -->
              <div class="input-field col s4">
                  <div class="center-align">
                      <div class="switch">
                          <label>
                              Only display Type Records
                              <input type="checkbox" id="checkbox_type">
                              <span class="lever"></span>
                          </label>
                      </div>
                      <input type="hidden" name="type" value="all">
                  </div>
              </div>
              <!-- Images -->
              <div class="input-field col s4">
                  <div class="center-align">
                      <div class="switch">
                          <label>
                              Only display Records with Images
                              <input type="checkbox" id="checkbox_images">
                              <span class="lever"></span>
                          </label>
                      </div>
                      <input type="hidden" name="images" value="all">
                  </div>
              </div>
             <!-- Synonym -->
              <div class="input-field col s4">
                  <div class="center-align">
                      <div class="switch">
                          <label>
                              Incl. synonym search
                              <input type="checkbox" id="checkbox_synoynm">
                              <span class="lever"></span>
                          </label>
                      </div>
                      <input type="hidden" name="synonym" value="all">
                  </div>
              </div>
              <!-- Submission -->
              <div class="col s12">
                  <div class="center-align">
                      <button id="ajax_f_submit" class="waves-effect waves-green btn-flat" type="submit" name="submit">Search</button>
                      <a id="ajax_f_reset" class="waves-effect waves-green btn-flat">Reset</a>
                  </div>
              </div>
          </form>
        <div class="progress progress-search">
          <div class="indeterminate"></div>
        </div>
        <div id="results">
        </div>
      </div>
        <div id="collections" class="row">
            <div class="col s12">
                <h5>Participating Collections</h5>
                <div class="divider"></div>
            </div>
            <div id="jacq-map" class="col s6">
                <iframe class="center-align pushpin" style="width:100%; height: 300px" data-target="institutions" src="https://mapsengine.google.com/map/embed?mid=zoTvNNgxY3Nw.kBUg9fgI9XCg"></iframe>
            </div>
            <div id="institutions" class="col s6">
                <ul class="collapsible">
                    <li>
                        <div class="collapsible-header"><i class="fas fa-angle-down"></i>Afghanistan</div>
                        <div class="collapsible-body">
                            <ul>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=163626" target="_blank">KUFS // Kabul University</a></li>
                            </ul>
                        </div>
                    </li>
                    <li>
                        <div class="collapsible-header"><i class="fas fa-angle-down"></i>Armenia</div>
                        <div class="collapsible-body">
                            <ul>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=124850" target="_blank">ERE // Institute of Botany of the National Academy of Sciences of Armenia</a></li>
                            </ul>
                        </div>
                    </li>
                    <li>
                        <div class="collapsible-header"><i class="fas fa-angle-down"></i>Azerbaijan</div>
                        <div class="collapsible-body">
                            <ul>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=123883" target="_blank">BAK // Academy of Sciences of Azerbaijan</a></li>
                            </ul>
                        </div>
                    </li>
                    <li>
                        <div class="collapsible-header"><i class="fas fa-angle-down"></i>Austria - Herbaria</div>
                        <div class="collapsible-body">
                            <ul>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=124041" target="_blank">ADMONT // Benediktinerstift Admont, Naturhistorisches Museum</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=126059" target="_blank">GJO // Center of Natural History, Botany, Universalmuseum Joanneum, Graz</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=125039" target="_blank">GZU // Karl Franzes University of Graz</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=124050" target="_blank">NBSI // Biologisches Forschungsinstitut für Burgenland,Biologische Station Neusiedler See,Illmitz</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=125500" target="_blank">W //   Natural history Museum Vienna</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=126513" target="_blank">WU //   University of Vienna, [former] Institute for Botany</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=151017" target="_blank">WUP // Department of Pharmacognosy, Universität Wien</a></li>
                            </ul>
                        </div>
                    </li>
                    <li>
                        <div class="collapsible-header"><i class="fas fa-angle-down"></i>Austria - Botanical Gardens</div>
                        <div class="collapsible-body">
                            <ul>
                                <li><a href="http://www.botanik.univie.ac.at/hbv/" target="_blank">HBV - Hortus Botanicus Vindobonensis</a></li>
                                <li><a href="http://www.bundesgaerten.at/" target="_blank">Bundesgärten Schönbrunn</a></li>
                                <li><a href="https://www.uni-salzburg.at/index.php?id=210019&no_cache=1&L=0" target="_blank">Botanischer Garten der Universität Salzburg</a></li>
                            </ul>
                        </div>
                    </li>
                    <li>
                        <div class="collapsible-header"><i class="fas fa-angle-down"></i>Czech Republic</div>
                        <div class="collapsible-body">
                            <ul>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=125227" target="_blank">BRNU // Masaryk University; Brno</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=124248" target="_blank">PRC // Charles University; Prague</a></li>
                            </ul>
                        </div>
                    </li>
                    <li>
                        <div class="collapsible-header"><i class="fas fa-angle-down"></i>Georgia</div>
                        <div class="collapsible-body">
                            <ul>
                                <li>! NEW ! <a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=124020" target="_blank">BATU // Batumi Botanical Garden</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=124619" target="_blank">TBI // Georgian Academy of Sciences</a></li>
                            </ul>
                        </div>
                    </li>
                    <li>
                        <div class="collapsible-header"><i class="fas fa-angle-down"></i>Germany</div>
                        <div class="collapsible-body">
                            <ul>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=124103" target="_blank">B // Botanischer Garten und Botanisches Museum Berlin-Dahlem</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=126128" target="_blank">DR // Institut für Botanik, Technische Universität Dresden</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=124869" target="_blank">GAT // Leibniz-Institut für Pflanzengenetik und Kulturpflanzenforschung (IPK), Gatersleben</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=125224" target="_blank">HAL // Martin-Luther-Universität Halle-Wittenberg</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=124582" target="_blank">JE // Friedrich-Schiller-Universität Jena</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=126504" target="_blank">KIEL // Christian-Albrechts-Universität zu Kiel</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=126506" target="_blank">LZ // Universität Leipzig</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=125020" target="_blank">MJG // Johannes-Gutenberg-Universität Mainz</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=126507" target="_blank">OLD // Carl von Ossietzky Universität Oldenburg</a></li>
                                <li>! NEW ! <a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=124452" target="_blank">TUB // Eberhard Karls Universität Tübingen</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=125591" target="_blank">UBT // Universität Bayreuth</a></li>
                            </ul>
                        </div>
                    </li>
                    <li>
                        <div class="collapsible-header"><i class="fas fa-angle-down"></i>Greece</div>
                        <div class="collapsible-body">
                            <ul>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=255445" target="_blank">CBH // Cephalonia Botanica, Focas Cosmetatos Foundation</a></li>
                                <li>! NEW ! <a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=126785" target="_blank">UPA // Βοτανικό Μουσείο Τμήματος Βιολογίας, University of Patras</a></li>
                            </ul>
                        </div>
                    </li>
                    <li>
                        <div class="collapsible-header"><i class="fas fa-angle-down"></i>Iran</div>
                        <div class="collapsible-body">
                            <ul>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=170152">TMRC   //  Department in Traditional Medicine and Materia Medica research Center affiliated to Shahid Beheshti University of Medical Sciences</a></li>
                            </ul>
                        </div>
                    </li>
                    <li>
                        <div class="collapsible-header"><i class="fas fa-angle-down"></i>Italy</div>
                        <div class="collapsible-body">
                            <ul>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=124484" target="_blank">FT // Centro Studi Erbario Tropicale, Università degli Studi di Firenze</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=126469" target="_blank">PI // Herbarium Horti Pisani, Università di Pisa</a></li>
                                <li>! NEW ! <a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=165749" target="_blank">PIAGR // Scienze Agrarie, Alimentari e Agroambientali, Università di Pisa</a></li>
                            </ul>
                        </div>
                    </li>
                    <li>
                        <div class="collapsible-header"><i class="fas fa-angle-down"></i>Montenegro</div>
                        <div class="collapsible-body">
                            <ul>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=156228" target="_blank">TGU // University of Montenegro; Podgorica</a></li>
                            </ul>
                        </div>
                    </li>
                    <li>
                        <div class="collapsible-header"><i class="fas fa-angle-down"></i>Russia</div>
                        <div class="collapsible-body">
                            <ul>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=124746" target="_blank">HERZ // Alexander Herzen Pedagogical University (St. Petersburg)</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=125848" target="_blank">KFTA // Saint Petersburg S. M. Kirov Forestry Academy</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=125849" target="_blank">LECB // Saint Petersburg University</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=124216" target="_blank">NS // Central Siberian Botanical Garden (Novosibirsk)</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=125943" target="_blank">NSK // Siberian Central Botanical Garden (Novosibirsk)</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=124398" target="_blank">SARAT // Herbarium Saratov State University</a></li>
                            </ul>
                        </div>
                    </li>
                    <li>
                        <div class="collapsible-header"><i class="fas fa-angle-down"></i>El Salvador</div>
                        <div class="collapsible-body">
                            <ul>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=123996" target="_blank">LAGU // Asociación Jardín Botánico La Laguna, Urbanización Plan de La Laguna</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=145204" target="_blank">MHES // Herbarium Botánica, Museo de Historia Natural de El Salvador</a></li>
                            </ul>
                        </div>
                    </li>
                    <li>
                        <div class="collapsible-header"><i class="fas fa-angle-down"></i>Turkey</div>
                        <div class="collapsible-body">
                            <ul>
                                <li>University Tunceli</li>
                            </ul>
                        </div>
                    </li>
                    <li>
                        <div class="collapsible-header"><i class="fas fa-angle-down"></i>Ukraine</div>
                        <div class="collapsible-body">
                            <ul>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=127094" target="_blank">CHER // Yu. Fedcovich Chernivtsi State University</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=124970" target="_blank">LW // Ivan Franko National University of Lviv</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=124856" target="_blank">LWKS // Institute of Ecology of the Carpathians; Lviv</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=125387" target="_blank">LWS // Museum of Natural History (Lviv)</a></li>
                            </ul>
                        </div>
                    </li>
                    <li>
                        <div class="collapsible-header"><i class="fas fa-angle-down"></i>Personal herbaria</div>
                        <div class="collapsible-body">
                            <ul>
                                <li>Herbarium Walter Gutermann (Wien, AT)</li>
                                <li>Herbarium Peter Pilsl (Salzburg, AT)</li>
                                <li>Herbarium Norbert Sauberer (Niederösterreich, AT)</li>
                                <li>Herbarium Eckehard Willing (Brandenburg, DE)</li>
                            </ul>
                        </div>
                    </li>
                    <li>
                        <div class="collapsible-header"><i class="fas fa-angle-down"></i>Herbaria of Society</div>
                        <div class="collapsible-body">
                            <ul>
                                <li><a href="http://www.drogistenmuseum.at/" target="_blank">Österreichisches Pharma- und Drogistenmuseum im Stiftungshaus für Drogisten" Herbarium des Drogistenmuseums (Wie, AT)</a></li>
                            </ul>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
      <div id="systems" class="row">
        <div class="col s12">
          <h5>Reference Systems</h5>
          <div class="divider"></div>
          <ul class="collapsible">
            <li>
              <div class="collapsible-header"><i class="fas fa-angle-down fa-sm"></i>Nikolaus Joseph von Jacquin (* 1727-02-16 Leiden / † 1817-10-26 Wien)</div>
              <div class="collapsible-body">
                <ul>
                  <li><b><a href="https://www.ipni.org/a/12576-1" target="_blank">IPNI author JACQ.</a></b></li>
                  <li><a href="http://viaf.org/viaf/59120694" target="_blank">VIAF Author</a> / <a href="https://www.wikidata.org/wiki/Q84497" target="_blank">WIKIDATA Person</a> / <a href="http://d-nb.info/gnd/118556452" target="_blank">GND - Deutsche National Bibliothek Normdatensatz</a> / <a href="https://kiki.huh.harvard.edu/databases/botanist_search.php?mode=details&id=4626" target="_blank">HUH Botanist</a></li>
                  <li><b><a href="https://bloodhound-tracker.net/Q84497" target="_blank">Blood Hound Tracker profile</a></b></li>
                  <li><a href="http://www.biographien.ac.at/oebl/oebl_J/Jacquin_Nicolaus-Joseph_1727_1817.xml?frames=yes" target="_blank">Österreichisches Biographisches Lexikon (german)</a> / <a href="https://geschichte.univie.ac.at/en/persons/nikolaus-joseph-freiherr-von-jacquin-dr-med" target="_blank">Biography due to his role as rector of the University of Vienna (german)</a></li>
                </ul>
              </div>
            </li>
            <li>
              <div class="collapsible-header"><i class="fas fa-angle-down fa-sm"></i>Nomenclature / Taxonomy / Phylogeny / Floras</div>
              <div class="collapsible-body">
                <ul>
                  <li><a href="https://www.iapt-taxon.org/nomen/main.php" target="_blank">International Code of Nomenclature for algae, fungi, and plants - ICN</a></li>
                  <li><a href="https://www.ishs.org/scripta-horticulturae/international-code-nomenclature-cultivated-plants-ninth-edition" target="_blank">International Code of Nomenclature for Cultivated Plants (ICNCP), 9th ed., 2016</a></li>
                  <li><a href="http://www.ipni.org/" target="_blank">International Plant Names Index - IPNI</a></li>
                </ul>
              <div class="divider"></div>
                <ul>
                  <li><a href="http://www.tropicos.org/" target="_blank">W³Tropicos</a></li>
                  <li><a href="http://data.kew.org/vpfg1992/vascplnt.html" target="_blank">Vascular Plant Families and Genera - Brummit</a></li>
                  <li><a href="https://naturalhistory2.si.edu/botany/ing/" target="_blank">Index Nominum Genericorum - ING</a> @ <a href="https://naturalhistory.si.edu/research/botany" target="_blank">US National Museum of Natural History - Smithsonian Institution - Botany Department</a>; U.S.A.</li>
                  <li><a href="https://www.nhm.ac.uk/our-science/data/linnaean-typification/search/" target="_blank">Linnaean Plant Names DB</a> @ <a href="http://www.nhm.ac.uk/" target="_blank">NHM London, UK</a></li>
                </ul>
              <div class="divider"></div>
                <ul>
                  <li><a href="http://www.algaebase.org/" target="_blank">AlgaeBase</a></li>
                  <li><a href="http://worldplants.webarchiv.kit.edu/ferns/index.php" target="_blank">World Ferns</a></li>
                  <li><a href="http://www.indexfungorum.org/Names/Names.asp" target="_blank">Index Fungorum - CABI / Kew</a></li>
                  <li><a href="http://www.mycobank.org/" target="_blank">Mycobank</a></li>
                </ul>
              <div class="divider"></div>
                <ul>
                  <li><a href="http://www.mobot.org/MOBOT/Research/APweb/welcome.html" target="_blank">Angiosperm Phylogeny</a> @ <a href="http://www.missouribotanicalgarden.org/">MO Botanical Garden</a></li>
                </ul>
              <div class="divider"></div>
                <ul>
                  <li><a href="http://ww2.bgbm.org/EuroPlusMed/query.asp" target="_blank">Euro+Med PlantBase</a> @ <a href="http://www.bgbm.org/" target="_blank">BG Berlin-Dahlem; Germany</a></li>
                  <li><a href="https://www.kp-buttler.de/florenliste/" target="_blank">Florenliste von Deutschland - K.P. Buttler et al, DE</a></li>
                  <li><a href="https://www.tela-botanica.org/" target="_blank">Tela Botanica, FR</a></li>
                  <li><a href="https://www.infoflora.ch/de/" target="_blank">Infoflora, CH</a></li>
                  <li><a href="https://pladias.cz/" target="_blank">PLADIAS - Flora and Vegetation, CZ</a></li>
                  <li><a href="http://www.anthos.es/" target="_blank">Anthos, ES & PT</a></li>
                  <li><a href="https://flora-on.pt/" target="_blank">flora • on, PT</a></li>
                  <li><a href="https://floraionica.univie.ac.at/" target="_blank">Flora Ionica, GR</a></li>
                  <li><a href="https://www.greekmountainflora.info/" target="_blank">Mountain Flora of Greece, GR</a></li>
                  <li>Liste der Gefäßpflanzen Mitteleuropas - Ehrendorfer 1973</li>
                  <li><a href="http://conosur.floraargentina.edu.ar/" target="_blank">Flora del Cono Sur</a></li>
                </ul>
              </div>
            </li>
            <li>
              <div class="collapsible-header"><i class="fas fa-angle-down"></i>Authors, Botanists, Collectors</div>
              <div class="collapsible-body">
                <ul>
                  <li><a href="https://viaf.org/ " target="_blank">Virtual Authority File - VIAF</a></li>
                  <li><a href="https://kiki.huh.harvard.edu/databases/botanist_index.html" target="_blank">Index to Botanists</a> @ <a href="https://huh.harvard.edu/" target="_blank">Harvard University Herbaria</a>; U.S.A.</li>
                  <li>Taxonomic Literature ed. 2 - <a href="https://www.sil.si.edu/DigitalCollections/tl-2/search.cfm" target="_blank">online</a></li>
                  <li>
                    <a href="https://www.iaptglobal.org/regnum-vegetabile" target="_blank">Regnum Vegetabile</a> @ <a href="https://www.iaptglobal.org/" target="_blank">International Association of Plant Taxonomists</a>
                    <br>Stafleu & Cowan 1976 ff. - vols. 94, 98, 105, 110, 112, 115, 116;
                    <br>Stafleu & Mennega 1992 ff. RV vols. 125, 130, 132, 134, 135, 137
                  </li>
                </ul>
              </div>
            </li>
            <li>
              <div class="collapsible-header"><i class="fas fa-angle-down"></i>Literature</div>
              <div class="collapsible-body">
                <ul>
                  <li><a href="https://huntbot.org/bph/" target="_blank">Botanico Periodicum Huntianum</a> @ <a href="http://www.huntbotanical.org" target="_blank">Hunt Institute for Botanical Documentation</a>; U.S.A.</li>
                  <li><a href="https://kvk.bibliothek.kit.edu/index.html?lang=en" target="_blank">KVK — Karlsruher Virtueller Katalog</a> @ <a href="http://www.kit.edu/" target="_blank">Karlsruher Institut für Technologie</a>; Germany</li>
                  <li><a href="https://www.biodiversitylibrary.org/" target="_blank">Biodiversity Heritage Library - digitized biodiversity literature</a></li>
                  <li><a href="https://bibdigital.rjb.csic.es/" target="_blank">Biblioteca Digital del Real Jardín Botánico Madrid</a></li>
                </ul>
              </div>
            </li>
            <li>
              <div class="collapsible-header"><i class="fas fa-angle-down"></i>Geography</div>
              <div class="collapsible-body">
                <ul>
                  <li><a href="http://geonames.nga.mil/gns/html/" target="_blank">GeoNet Names Server</a> @ <a href="http://www.usgs.gov/" target="_blank">US Geological Survey</a></li>
                  <li><a href="http://www.geonames.org/" target="_blank">GeoNames</a></li>
                  <li><a href="http://www.austrianmap.at/" target="_blank">Austrian Map</a> @ <a href="http://www.bev.gv.at/" target="_blank">Bundesamt für Eich- & Vermessungswesen</a></li>
                </ul>
              </div>
            </li>
            <li>
              <div class="collapsible-header"><i class="fas fa-angle-down"></i>Herbaria</div>
              <div class="collapsible-body">
                <ul>
                  <li><a href="http://sweetgum.nybg.org/science/ih/" target="_blank">Index Herbariorum</a> @ <a href="http://www.nybg.org/" target="_blank">NY Botanical Garden</a>; U.S.A.</li>
                </ul>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </div>
    <div id="footer-wrapper">
      <div class="divider"></div>
      <div id="footer">
        <a href="imprint_citation_privacy.htm">Imprint | Citation | Privacy</a>
      </div>
    </div>
    <script type="text/javascript" src="assets/jquery/jquery.min.js"></script>
    <script type="text/javascript" src="inc/xajax/xajax_js/xajax.js"></script>
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