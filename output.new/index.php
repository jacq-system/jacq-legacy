<?php include 'search.php'; ?>

<!DOCTYPE html>
<html>
  <head>
    <title>JACQ</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="description" content="FW4 DW4 HTML">
    <meta http-equiv="“cache-control“" content="“no-cache“">
    <meta http-equiv="“pragma“" content="“no-cache“">
    <meta http-equiv="“expires“" content="“0″">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link type="text/css" rel="stylesheet" href="assets/materialize/css/materialize.min.css"  media="screen,projection"/>
    <link href="assets/fontawesome/css/all.css" rel="stylesheet">
    <link type="text/css" rel="stylesheet" href="assets/custom/styles/jacq.css"  media="screen,projection"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
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
          <p>JACQ is the jointly administered herbarium management system and specimen database of the following herbaria: ADMONT, B, BAK, BRNU, CBH, CHER, DR, ERE, FT, GAT, GJO, GZU, HAL, HERZ, JE, KFTA, KUFS, LAGU, LECB, LW, LWKS, LWS, LZ, MJG, NBSI, OLD, PI, PRC, TBI, TGU, TMRC, UBT, W, WU and WUP. Listed Acronyms follow the <a href="http://sweetgum.nybg.org/science/ih/" target="_blank">Index Herbariorum Abbreviations</a> . For requests and comments please contact the corresponding Director/Curator listed in the Index Herbariorum  directly.</p>

          <h5>Other Herbaria / Botanical Specimen Databases</h5>
          <div class="divider"></div>
            <ul class="collapsible">
            <li>
              <div class="collapsible-header"><i class="fas fa-angle-down"></i>Other Herbaria / Botanical Specimen Databases</div>
              <div class="collapsible-body">
                <h6><strong>Europe</strong></h6>
                <ul>
                  <li>AAU / <a href="http://herbarium.au.dk/database/">Aarhus University - Aarhus, Denmark</a></li>
                  <li>E / <a href="http://elmer.rbge.org.uk/bgbase/vherb/bgbasevherb.php">Royal Botanic Garden Edinburgh - Edinburgh, Scotland</a></li>
                  <li>K / <a href="http://www.kew.org/herbcat/gotoHomePage.do">Royal Botanic Garden Kew - Richmond, England</a></li>
                  <li>L, U, WAG / <a href="http://vstbol.leidenuniv.nl/">Nationaal Herbarium Nederland - Universities Leiden, Utrecht, Wageningen, The Netherlands</a></li>
                  <li>MPU, NCY, P / <a href="http://coldb.mnhn.fr/colweb/form.do?model=SONNERAT.wwwsonnerat.wwwsonnerat.wwwsonnerat">MNHN - Paris, France</a></li>
                  <li>Z, ZT / <a href="http://www.herbarien.uzh.ch/index.html">Zürcher Herbarien - Zurich, Switzerland</a></li>
                </ul>

                <h6><strong>Northamerica</strong></h6>
                <ul>
                  <li>A, ECON, GH / <a href="http://kiki.huh.harvard.edu/databases/specimen_index.html">Harvard University Herbaria - Cambridge, MA</a></li>
                  <li>F / <a href="http://emuweb.fieldmuseum.org/botany/detailed.php">FMNH Botany Collections - Field Museum, Chicago, IL</a></li>
                  <li>MO / <a href="http://www.tropicos.org/">Tropicos Specimens Database - Missouri Botanical Garden, St. Louis, MO</a></li>
                  <li>NY / <a href="http://sciweb.nybg.org/science2/vii2.asp">Virtual Herbarium NY Botanical Garden - New York, NY</a></li>
                </ul>

                <h6><strong>Southamerica</strong></h6>
                <ul>
                  <li>COL / <a href="http://www.biovirtual.unal.edu.co/ICN/">Herbario Nacional - Bogota, Colombia</a></li>
                  <li><a href="http://splink.cria.org.br/">Species link - CRIA, Brasil</a></li>
                </ul>

                <h6><strong>Asia</strong></h6>
                <ul>
                  <li>Chinese Virtual Herbarium / <a href="http://www.cvh.org.cn/">Chinese Academy of Sciences Inst. Botany - Beijing, China</a></li>
                  <li>PE / <a href="http://pe.ibcas.ac.cn/herbs/herbariumsearch.aspx">Chinese Academy of Sciences Inst. Botany - Beijing, China</a></li>
                </ul>
              </div>
            </li>
          </ul>

          <h5>Acknowledgements</h5>
          <div class="divider"></div>
          <div id="partners"> 
            <div class="partnerlogo"><img src="assets/images/biocase.gif" alt="Biocase Logo"></div>
            <div class="partnerlogo"><img src="assets/images/enbi.gif" alt="ENBI Logo"></div>
            <div class="partnerlogo"><img src="assets/images/gbif.jpg" alt="GBIF Logo"></div>
            <div class="partnerlogo"><img src="assets/images/PESI_logo_small.gif" alt="PESI Logo"></div>
            <div class="partnerlogo"><img src="assets/images/sp2keur.png" alt="sp2keur Logo"></div>
            <div class="partnerlogo"><img src="assets/images/tdwg.gif" alt="TDWG Logo"></div>
            <div class="partnerlogo"><img src="assets/images/synthesys.gif" alt="SYNTHESYS Logo"></div>
          </div>
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
          <form id="ajax_f" name="f" class="row" action="index.php" method="post">
              <!-- Institution -->
              <div class="input-field col s6">
                  <select name="source_name">
                      <option value="" selected>Search all</option>
                      <?php

                      $result = $dbLink->query("SELECT `source_name`
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
                                          ORDER BY `source_name`");
                      while ($row = $result->fetch_array()) {
                          echo "<option value=\"{$row['source_name']}\"";
                          if ($source_name == $row['source_name']) {
                              echo " selected";
                          }
                          echo ">{$row['source_name']}</option>\n";
                      }
                      ?>
                  </select>

              </div>
              <!-- Herbar Number -->
              <div class="input-field col s6">
                  <input class="searchinput" placeholder="Herbar #" name="HerbNummer" type="text":not(.browser-default)>

              </div>
              <!-- Family -->
              <div class="input-field col s6">
                  <input class="searchinput" placeholder="Family" name="family" type="text":not(.browser-default)>

              </div>
              <!-- Taxon -->
              <div class="input-field col s6">
                  <input class="searchinput" placeholder="Scientific name" name="taxon" type="text":not(.browser-default)>

              </div>
              <!-- Collector -->
              <div class="input-field col s6">
                  <input class="searchinput" placeholder="Collector" name="Sammler" type="text":not(.browser-default)>

              </div>
              <!-- Collector Number -->
              <div class="input-field col s6">
                  <input class="searchinput" placeholder="Collector #" name="SammlerNr" type="text":not(.browser-default)>

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
                                  <!-- Synonym -->
                                  <div class="input-field">
                                      <label>
                                          <input type="checkbox" name="synonym" checked="true" class="searchinput">
                                          <span>incl. syn.</span>
                                      </label>
                                  </div>
                                  <!-- Collection -->
                                  <div class="input-field">
                                      <select id="ajax_collection" name="collection">
                                          <option value="" selected>Search subcollection</option>
                                          <?php
                                          $result = $dbLink->query("SELECT `collection`
                                          FROM `tbl_management_collections`
                                          WHERE `collectionID`
                                          IN (
                                            SELECT DISTINCT `collectionID`
                                            FROM `tbl_specimens`
                                          )
                                          ORDER BY `collection`");
                                          while ($row = $result->fetch_array()) {
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
                                      <input class="searchinput" placeholder="Collection #" name="CollNummer" type="text":not(:chekced)>

                                  </div>
                                  <!-- Series -->
                                  <div class="input-field">
                                      <input class="searchinput" placeholder="Series" name="series" type="text":not(:chekced)>

                                  </div>
                                  <!-- Locality -->
                                  <div class="input-field">
                                      <input class="searchinput" placeholder="Locality" name="Fundort" type="text":not(:chekced)>

                                  </div>
                                  <!-- Continent -->
                                  <div class="input-field">
                                      <select name="geo_general">
                                          <option value="" selected>Search continent</option>
                                          <?php
                                          $result = $dbLink->query("SELECT geo_general
                                                    FROM tbl_geo_region
                                                    GROUP BY geo_general ORDER BY geo_general");
                                          while ($row = $result->fetch_array()) {
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
                                      <input class="searchinput" placeholder="Country" name="nation_engl" type="text":not(:chekced) value="<?php echo htmlspecialchars($nation_engl); ?>">

                                  </div>
                                  <!-- Region -->
                                  <div class="input-field">
                                      <select name="geo_region">
                                          <option value="" selected>Search region</option>
                                          <?php
                                          $result = $dbLink->query("SELECT geo_region
                                                    FROM tbl_geo_region
                                                    ORDER BY geo_region");
                                          while ($row = $result->fetch_array()) {
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
                                      <input class="searchinput" placeholder="State/Province" name="provinz" type="text":not(:chekced)>

                                  </div>
                                  <!-- Placeholder -->
                                  <div></div>

                              </div>
                          </div>
                      </li>
                  </ul>
              </div>

              <!-- All Records/Type Records -->
              <div class="input-field col s6">
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
              <!-- Synonym -->
              <div class="input-field col s6">
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
                <iframe class="center-align pushpin" data-target="institutions" src="https://mapsengine.google.com/map/embed?mid=zoTvNNgxY3Nw.kBUg9fgI9XCg" width="100%" height="300px"></iframe>
            </div>
            <div id="institutions" class="col s6">
                <ul class="collapsible">
                    <li>
                        <div class="collapsible-header"><i class="fas fa-angle-down"></i>Afghanistan</div>
                        <div class="collapsible-body">
                            <ul>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=163626">KUFS // Kabul University</a></li>
                            </ul>
                        </div>
                    </li>
                    <li>
                        <div class="collapsible-header"><i class="fas fa-angle-down"></i>Armenia</div>
                        <div class="collapsible-body">
                            <ul>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=124850">ERE // Institute of Botany of the National Academy of Sciences of Armenia</a></li>
                            </ul>
                        </div>
                    </li>
                    <li>
                        <div class="collapsible-header"><i class="fas fa-angle-down"></i>Azerbaijan</div>
                        <div class="collapsible-body">
                            <ul>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=123883">BAK // Academy of Sciences of Azerbaijan</a></li>
                            </ul>
                        </div>
                    </li>
                    <li>
                        <div class="collapsible-header"><i class="fas fa-angle-down"></i>Austria (Herbaria)</div>
                        <div class="collapsible-body">
                            <ul>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=124041">ADMONT // Benediktinerstift Admont, Naturhistorisches Museum</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=126059">GJO // Center of Natural History, Botany, Universalmuseum Joanneum, Graz</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=125039">GZU // Karl Franzes University of Graz</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=124050">NBSI // Biologisches Forschungsinstitut für Burgenland,Biologische Station Neusiedler See,Illmitz</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=125500">W //   Natural history Museum Vienna</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=126513">WU //   University of Vienna, [former] Institute for Botany</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=151017">WUP // Department of Pharmacognosy, Universität Wien</a></li>
                            </ul>
                        </div>
                    </li>
                    <li>
                        <div class="collapsible-header"><i class="fas fa-angle-down"></i>Austria (Living collections)</div>
                        <div class="collapsible-body">
                            <ul>
                                <li><a href="http://www.botanik.univie.ac.at/hbv/">HBV // Hortus Boptanicus Vindobonensis</a></li>
                                <li><a href="https://www.bmlfuw.gv.at/ministerium/bundesgaerten">Bundesgärten Schönbrunn</a></li>
                                <li><a href="http://uni-salzburg.at/index.php?id=40251">Botanischer Garten der Universität Salzburg</a></li>
                            </ul>
                        </div>
                    </li>
                    <li>
                        <div class="collapsible-header"><i class="fas fa-angle-down"></i>Czech Republic</div>
                        <div class="collapsible-body">
                            <ul>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=125227">BRNU // Masaryk University; Brno</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=124248">PRC // Charles University; Prague</a></li>
                            </ul>
                        </div>
                    </li>
                    <li>
                        <div class="collapsible-header"><i class="fas fa-angle-down"></i>Georgia</div>
                        <div class="collapsible-body">
                            <ul>
                                <li><a href="http://sweetgum.nybg.org/ih/herbarium.php?irn=124619">TBI // Georgian Academy of Sciences</a></li>
                            </ul>
                        </div>
                    </li>
                    <li>
                        <div class="collapsible-header"><i class="fas fa-angle-down"></i>Germany</div>
                        <div class="collapsible-body">
                            <ul>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=125591">UBT // University of Bayreuth</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=124103">B // Botanischer Garten und Botanisches Museum Berlin-Dahlem</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=126128">DR // Institut für Botanik; Technische Universität Dresden</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=124869">GAT // Leibniz Institute of Plant Genetics and Crop Plant Research (IPK); Gatersleben</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=125224">HAL // Martin-Luther-Universität; Halle</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=124582">JE   //  - Friedrich Schiller University; Jena</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=126506">LZ // Universität Leipzig</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=125020">MJG // Johannes Gutenberg-Universität; Mainz</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=126507">OLD // Universität Oldenburg</a></li>
                            </ul>
                        </div>
                    </li>
                    <li>
                        <div class="collapsible-header"><i class="fas fa-angle-down"></i>Greece</div>
                        <div class="collapsible-body">
                            <ul>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=255445">CBH // Cephalonia Botanica, Focas Cosmetatos Foundation</a></li>
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
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=124484">FT // Centro Studi Erbario Tropicale, Università degli Studi di Firenze</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=126469">PI // Herbarium Horti Pisani, Università di Pisa</a></li>
                            </ul>
                        </div>
                    </li>
                    <li>
                        <div class="collapsible-header"><i class="fas fa-angle-down"></i>Montenegro</div>
                        <div class="collapsible-body">
                            <ul>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=156228">TGU // University of Montenegro; Podgorica</a></li>
                            </ul>
                        </div>
                    </li>
                    <li>
                        <div class="collapsible-header"><i class="fas fa-angle-down"></i>Russia</div>
                        <div class="collapsible-body">
                            <ul>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=124398">SARAT // Herbarium Saratov State University</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=124746">HERZ // Alexander Herzen Pedagogical University (St. Petersburg)</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=125848">KFTA // Saint Petersburg S. M. Kirov Forestry Academy</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=125849">LECB // Saint Petersburg University</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=124216">NS // Central Siberian Botanical Garden (Novosibirsk)</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=125943">NSK // Siberian Central Botanical Garden (Novosibirsk)</a></li>
                            </ul>
                        </div>
                    </li>
                    <li>
                        <div class="collapsible-header"><i class="fas fa-angle-down"></i>El Salvador</div>
                        <div class="collapsible-body">
                            <ul>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=123996">LAGU // Asociación Jardín Botánico La Laguna, Urbanización Plan de La Laguna</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=145204">MHES // Herbarium Botánica, Museo de Historia Natural de El Salvador</a></li>
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
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=127094">CHER // Yu. Fedcovich Chernivtsi State University</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=124970">LW // Ivan Franko National University of Lviv</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=124856">LWKS // Institute of Ecology of the Carpathians; Lviv</a></li>
                                <li><a href="http://sweetgum.nybg.org/science/ih/herbarium-details/?irn=125387">LWS // Museum of Natural History (Lviv)</a></li>
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
                                <li><a href="http://www.drogistenmuseum.at/">Österreichisches Pharma- und Drogistenmuseum im Stiftungshaus für Drogisten" Herbarium des Drogistenmuseums (Wie, AT)</a></li>
                            </ul>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
      <div id="systems" class="row"
        <div class="col s12">
          <h5>Reference Systems</h5>
          <div class="divider"></div>
          <ul class="collapsible">
            <li>
              <div class="collapsible-header"><i class="fas fa-angle-down fa-sm"></i>Nomenclature / Taxonomy / Phylogeny / Floras</div>
              <div class="collapsible-body">
                <ul>
                  <li><a href="http://www.bgbm.org/iapt/nomenclature/code/default.htm">International Code of Botanical Nomenclature - ICBN</a></li>
                  <li><a href="http://www.ipni.org/ipni/plantnamesearchpage.do">International Plant Names Index - IPNI</a></li>
                  <li><a href="http://mobot.mobot.org/W3T/Search/vast.html">Wï¿½Tropicos</a></li>
                  <li><a href="http://www.rbgkew.org.uk/data/vascplnt.html">Vascular Plant Families and Genera - Brummit</a></li>
                  <li><a href="http://www.bgbm.fu-berlin.de/iapt/ncu/genera/NCUGQuery.htm">Names in Current Use - NCU</a></li>
                  <li><a href="http://botany.si.edu/ing/">Index Nominum Genericorum - ING</a> @ <a href="http://www.nmnh.si.edu/botany/">US National Museum of Natural History - Smithsonian Institution - Botany Department</a>; U.S.A.</li>
                  <li><a href="http://www.nhm.ac.uk/jdsml/research-curation/projects/linnaean-typification/">Linnaean Plant Names DB</a> @ <a href="http://www.nhm.ac.uk/">NHM London, UK</a></li>
                  <li><a href="http://www.algaebase.org/">AlgaeBase</a></li>
                  <li><a href="http://mobot.mobot.org/W3T/Search/most.html">W³MOST - Index to Mosses</a></li>
                  <li><a href="http://homepages.caverock.net.nz/~bj/fern/">World Checklist of Ferns</a></li>
                  <li><a href="http://www.speciesfungorum.org/Names/Names.asp">Index Fungorum - CABI</a></li>
                  <li><a href="http://www.omnisterra.com/bot/pp_home.cgi">Parasitic Plants Database</a></li>
                  <li><a href="http://www.omnisterra.com/bot/cp_home.cgi">Carnivorous Plants Database</a></li>
                  <li><a href="http://www.mobot.org/MOBOT/Research/APweb/welcome.html">Angiosperm Phylogeny</a> @ <a href="http://www.mobot.org/">MO Botanical Garden</a></li>
                  <li><a href="http://ww2.bgbm.org/EuroPlusMed/query.asp">Euro+Med PlantBase</a> @ <a href="http://www.bgbm.org/">BG Berlin-Dahlem; Germany</a></li>
                  <li><a href="http://rbg-web2.rbge.org.uk/FE/fe.html">Flora Europaea</a> @ <a href="http://www.rbge.org.uk/">RBG Edinburgh; UK</a></li>
                  <li>Standardliste der Farn und Blütenpflanzen Deutschlands - <a href="http://www.ulmer.de/T9pd3GIR3EZJ1G1x0JIB6GIExAH+lCGNBGIExAn6lA+++.HTML">Wisskirchen & Haeupler 1998</a></li>
                  <li>Liste der Gefäßpflanzen Mitteleuropas - Ehrendorfer 1973</li>
                </ul>
              </div>
            </li>
            <li>
              <div class="collapsible-header"><i class="fas fa-angle-down"></i>Authors, Botanists, Collectors</div>
              <div class="collapsible-body">
                <ul>
                  <li><a href="http://www.ipni.org/ipni/authorsearchpage.do">IPNI - Brummitt & Powell / Authors of Plant Names</a></li>
                  <li><a href="http://asaweb.huh.harvard.edu:8080/databases/botanist_index.html">Index to Botanists</a> @ <a href="http://www.huh.harvard.edu/">Harvard University Herbaria</a>; U.S.A.</li>
                  <li>Taxonomic Literature ed. 2 - <a href="http://tl2.idcpublishers.info/">online</a> @ <a href="http://www.idc.nl/">IDC Publishers</a></li>
                  <li>
                    <a href="http://www.botanik.univie.ac.at/iapt/s_regnumvegetabile.php">Regnum Vegetabile</a> @ <a href="http://www.iapt-taxon.org/">International Association of Plant Taxonomists</a>
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
                  <li><a href="http://huntbot.andrew.cmu.edu/HIBD/Publications/HI-Pubs/Pub-BPHS.shtml">Botanico Periodicum Huntianum</a> @ <a href="http://huntbot.andrew.cmu.edu/">Hunt Institute for Botanical Documentation</a>; U.S.A.</li>
                  <li><a href="http://www.ipni.org/ipni/publicationsearchpage.do">IPNI - Publications</a></li>
                  <li><a href="http://www.ubka.uni-karlsruhe.de/hylib/en/kvk.html">KVK — Karlsruher Virtueller Katalog</a> @ <a href="http://www.uni-karlsruhe.de/">Karlsruhe Universität</a>; Germany</li>
                  <li><a href="http://kbd.kew.org/kbd/searchpage.do">KBD - Kew Bibliographic Databases</a> @ <a href="http://www.kew.org/">RBG Kew</a>; UK</li>
                  <li><a href="http://cameo.library.cmu.edu/">Carnegie Mellon OPAC, incl. Hunt Library</a> @ <a href="http://www.cmu.edu/">Carnegie Mellon University</a>; U.S.A.</li>
                  <li><a href="http://www.botanicus.org/">Botanicus - digitized botanical literature</a> @ <a href="http://www.mobot.org/">Missouri Botanical Garden</a>; U.S.A.</li>
                  <li><a href="http://www.biodiversitylibrary.org/">Biodiversity Heritage Library - digitized biodiversity literature</a></li>
                </ul>
              </div>
            </li>
            <li>
              <div class="collapsible-header"><i class="fas fa-angle-down"></i>Geography</div>
              <div class="collapsible-body">
                <ul>
                  <li><a href="http://earth-info.nga.mil/gns/html/index.html">GeoNet Names Server</a> - geographical names gazetteer worldwide @ <a href="http://www.usgs.gov/">US Geological Survey</a>; U.S.A.</li>
                  <li><a href="http://geonames.usgs.gov/pls/gnispublic/">Geographical Names Information System GNIS</a> - geographical gazetteer United States and territories @ <a href="http://www.nga.mil/">National Geospatial-Intelligence Agency</a>; U.S.A.</li>
                  <li><a href="http://www.austrianmap.at/">Austrian Map</a> - geographical names of Austria and online map @ <a href="http://www.bev.gv.at/">Bundesamt für Eich- & Vermessungswesen</a>; Austria</li>
                </ul>
              </div>
            </li>
            <li>
              <div class="collapsible-header"><i class="fas fa-angle-down"></i>Herbaria</div>
              <div class="collapsible-body">
                <ul>
                  <li><a href="http://sciweb.nybg.org/science2/IndexHerbariorum.asp">Index Herbariorum</a> @ <a href="http://www.nybg.org/">NY Botanical Garden</a>; U.S.A.</li>
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
        <a href="https://www.bgbm.org/en/imprint">Imprint</a>
      </div>
    </div>
    <script type="text/javascript" src="assets/jquery/jquery.min.js"></script>
    <script type="text/javascript" src="inc/xajax/xajax_js/xajax.js"></script>
    <script type="text/javascript" src="assets/materialize/js/materialize.min.js"></script>
    <script type="text/javascript" src="assets/custom/scripts/jacq.js"></script>
  </body>
</html>