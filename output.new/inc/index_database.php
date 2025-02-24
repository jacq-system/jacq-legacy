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
        echo '<input  value="' . htmlspecialchars($taxon) . '"
                          name="taxon" id="taxon" type="text">';
        ?>
        <label for="taxon">Scientific name</label>
    </div>
    <!-- Family -->
    <div class="input-field col s6">
        <?php
        echo '<input value="' . htmlspecialchars($family) . '"
                          name="family" id="family" type="text">';
        ?>
        <label for="family">Family</label>
    </div>
    <!-- Institution -->
    <div class="input-field col s6">
        <select id="ajax_source_name" name="source_name">
            <option value="" selected>all herbaria</option>
            <?php
            $result = $dbLnk2->query("SELECT CONCAT(`source_code`,' - ',`source_name`) herbname, `source_code`, `source_name`
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
                if ($source_name == $row['source_name'] || $source_code == $row['source_code']) {
                    echo " selected";
                }
                echo ">{$row['herbname']}</option>\n";
            }
            ?>
        </select>
        <label for="ajax_source_name">Search in</label>
    </div>
    <!-- Herbar Number -->
    <div class="input-field col s6">
        <?php
        echo '<input value="' . htmlspecialchars($HerbNummer) . '"
                         name="HerbNummer" id="HerbNummer" type="text">';
        ?>
        <label for="HerbNummer">Herbar #</label>
    </div>
    <!-- Collector -->
    <div class="input-field col s6">
        <?php
        echo '<input value="' . htmlspecialchars($Sammler) . '"
                         name="Sammler" id="Sammler" type="text">';
        ?>
        <label for="Sammler">Collector</label>
    </div>
    <!-- Collector Number -->
    <div class="input-field col s6">
        <?php
        echo '<input value="' . htmlspecialchars($SammlerNr) . '"
                         name="SammlerNr" id="SammlerNr" type="text">';
        ?>
        <label for="SammlerNr">Collector #</label>
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
                            <input name="taxon_alt" id="taxon_alt" type="text">
                            <label for="taxon_alt">Ident. History</label>
                        </div>
                        <!-- CollectionDate -->
                        <div class="input-field">
                            <input name="CollDate" id="CollDate" type="text">
                            <label for="CollDate">Collection date</label>
                        </div>
                        <!-- Collection -->
                        <div class="input-field">
                            <select id="ajax_collection" name="collection">
                                <option value="" selected>all subcollections</option>
                                <?php
                                $result_collection = $dbLnk2->query("SELECT `collection`
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
                            <label for="ajax_collection">Search in</label>
                        </div>
                        <!-- Collection Number -->
                        <div class="input-field">
                            <input name="CollNummer" id="CollNummer" type="text">
                            <label for="CollNummer">Collection #</label>
                        </div>
                        <!-- Series -->
                        <div class="input-field">
                            <input  name="series" id="series" type="text">
                            <label for="series">Series</label>
                        </div>
                        <!-- Locality -->
                        <div class="input-field">
                            <input name="Fundort" id="Fundort" type="text">
                            <label for="Fundort">Locality</label>
                        </div>
                        <!-- Country -->
                        <div id="ajax_nation_engl_div" class="input-field">
                            <input id="ajax_nation_engl" name="nation_engl" type="text" value="<?php echo htmlspecialchars($nation_engl); ?>">
                            <label for="ajax_nation_engl">Country</label>
                        </div>
                        <!-- State/Province -->
                        <div id="ajax_provinz_div" class="input-field">
                            <input id="ajax_provinz" name="provinz" type="text">
                            <label for="ajax_provinz">State/Province</label>
                        </div>
                        <!-- Habitat -->
                        <div class="input-field">
                            <input name="habitat" id="habitat" type="text">
                            <label for="habitat">Habitat</label>
                        </div>
                        <!-- Habitus -->
                        <div class="input-field">
                            <input name="habitus" id="habitus" type="text">
                            <label for="habitus">Habitus</label>
                        </div>
                        <div class="input-field">
                            <input name="annotation" id="annotation" type="text">
                            <label for="annotation">Annotation</label>
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
                    <input type="checkbox" id="checkbox_synonym">
                    <span class="lever"></span>
                </label>
            </div>
            <input type="hidden" name="synonym" value="only">
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
