<?php

use Jacq\DbAccess;
use Jacq\Settings;

if (empty($_SESSION['s_query'])) { die(); } // nothing to do

require_once 'inc/functions.php';
require_once __DIR__ . '/../vendor/autoload.php';

// user wants to change order
if (isset($_GET['order'])) {
    if (intval($_GET['order']) == 2) {
        $_SESSION['order'] = 2;
    } else {
        $_SESSION['order'] = 1;
    }
}

// user wants to change items per page
if (isset($_GET['ITEMS_PER_PAGE'])) {
    switch (intval($_GET['ITEMS_PER_PAGE'])) {
    case 10:
        $_SESSION['ITEMS_PER_PAGE'] = 10;
        break;
    case 30:
        $_SESSION['ITEMS_PER_PAGE'] = 30;
        break;
    case 50:
        $_SESSION['ITEMS_PER_PAGE'] = 50;
        break;
    case 100:
        $_SESSION['ITEMS_PER_PAGE'] = 100;
        break;
    }
}

// user wants another page
if (isset($_GET['page'])) {
    $newpage = intval(filter_input(INPUT_GET, 'page', FILTER_SANITIZE_NUMBER_INT));
    $page = ($newpage >= 1) ? $newpage : 1;
} else {
    $page = 1;
}

$sql = (empty($_SESSION['s_nrRows'])) ? $_SESSION['s_query_nrRows'] : $_SESSION['s_query'];
// set order of query
if ($_SESSION['order'] == 2) {
    $sql .= "ORDER BY Sammler, Sammler_2, series, Nummer, HerbNummer"; }
else {
    $sql .= "ORDER BY genus, epithet, author, HerbNummer";
}

/**
 * pagination
 */
$dbLnk2 = DbAccess::ConnectTo('OUTPUT');
$sql .= " LIMIT " . ($_SESSION['ITEMS_PER_PAGE'] * ($page - 1)) . ", " . $_SESSION['ITEMS_PER_PAGE'];
$result = $dbLnk2->query($sql);                     // get the result of the query for further processing
if (empty($_SESSION['s_nrRows'])) {
    if ($result->num_rows < $_SESSION['ITEMS_PER_PAGE']) {
        $_SESSION['s_nrRows'] = $result->num_rows;
    } else {
        $res_count = $dbLnk2->query("select found_rows()"); // get the complete number of found rows
        if ($res_count) {
            $res_count_row = $res_count->fetch_row();
            $_SESSION['s_nrRows'] = intval($res_count_row[0]);
        }
    }
}
$nrRows = $_SESSION['s_nrRows'];

$a = paginate_three($page, ceil($nrRows / $_SESSION['ITEMS_PER_PAGE']), 2);
$b = "";
$limits=array(10, 30, 50, 100);
foreach ($limits as $f) {
    $b .= "<option value=\"$f\" " . (($f == $_SESSION['ITEMS_PER_PAGE']) ? 'selected' : '') . ">$f</option>";
}
$navigation = "<form name='page' method='get' align='center' class='col s12'>\n"
            . "<div class='input-field'>"
            . "<select name='ITEMS_PER_PAGE'>\n"
            . "{$b}\n"
            . "</select>\n"
            . "<label>Items per Page</label>\n"
            . "</div>"
            . "{$a}\n"
            . "</form>";
?>
<script type="text/javascript" language="javascript">
    let oldButtonText;
    function osMap(sid) {
        if (sid > 0) {
            MeinFenster = window.open('os_maps.php?sid=' + sid,'_blank',
                                      'width=820,height=620,top=50,left=50,resizable,scrollbars');
            MeinFenster.focus();
        } else {
            MeinFenster = window.open('os_maps.php','_blank',
                                      'width=820,height=620,top=50,left=50,resizable,scrollbars');
            MeinFenster.focus();
        }
    }
    $(document).ready(function(){
        $(".exportKML").click(function(){
            $(this).css("background-color", "#5D9F30");
        });
        $(".exportKML").focusout(function(){
            $(this).css("background-color", "#FFFFFF");
        });
        $(".exportContent").click(function(){
            oldButtonText = $(this).text();
            $(this).css("background-color", "#5D9F30");
            $(this).css("color", "#FFFF00");
            $(this).html("generating ...");
        });
        $(".exportContent").focusout(function(){
            $(this).css("background-color", "#FFFFFF");
            $(this).css("color", "#7F7F7F");
            $(this).html(oldButtonText);
        });
    });
</script>
<div class="divider"></div>

<div align="center">
  <table border="0" cellpadding="0" cellspacing="0" width="800">
    <tr>
      <td valign="top" colspan="9">
        <div align='center'>
          <table width='100%'>
            <tr>
              <td colspan='2'><b><?php echo $nrRows; ?> record<?php echo ($nrRows > 1) ? "s" : ""; ?> found</b></td>
              <td colspan="7" align="right">
                <?php if (!empty($_SESSION['s_query'])): ?>
                <form style="display:inline;" action="javascript:osMap();" method="post">
                   <button class="btn-flat waves-effect waves-light" type="button" name="action" value="Create map" onClick="osMap(-1)">Create map
                  </button>
                </form>
                &nbsp;&nbsp;
                <a href="exportKml.php" download><button class="btn-flat waves-effect waves-light exportKML">Download KML</button></a>
                <a href="exportCsv.php" download><button class="btn-flat waves-effect waves-light exportContent">Download XLSX</button></a>
                <a href="exportCsv.php?type=ods" download><button class="btn-flat waves-effect waves-light exportContent">Download ODS</button></a>
                <a href="exportCsv.php?type=csv" download><button class="btn-flat waves-effect waves-light exportContent">Download CSV</button></a>
                <?php endif; ?>
              </td>
            </tr>
            <tr>
              <td colspan="9" align="center" valign="center">
                <?php echo $navigation; ?>
                <div class='progress progress-paging'>
                  <div class='indeterminate'></div>
                </div>
              </td>
            </tr>
          </table>

          <table id="result-table" class="striped responsive-table">
            <tr>
              <th></th>
              <th class="resulttax">Taxon</th>
              <th class="resultcol">Collector</th>
              <th class="result">Date</th>
              <th class="result">Location</th>
              <th class="result">Typus</th>
              <th class="result">Collection Herb.#</th>
              <th class="result">Lat/Lon</th>
            </tr>
<?php
if ($nrRows) {
// process results and show table
    $specimenIDs = array();
    while ($row = $result->fetch_array()) {
        $specimenIDs[] = intval($row['specimen_ID']);
    }
    $sqlSpecimen = "SELECT s.specimen_ID, tg.genus, s.digital_image, s.digital_image_obs, s.observation,
                 c.Sammler, c.SammlerID, c.HUH_ID, c.VIAF_ID, c.WIKIDATA_ID,c.ORCID, c2.Sammler_2, ss.series, s.series_number, s.taxonID taxid,
                 s.Nummer, s.alt_number, s.Datum, mc.collection, mc.coll_short_prj, mc.source_id, tid.imgserver_IP, tid.iiif_capable, tid.iiif_url, s.HerbNummer,
                 ph.specimenID AS phaidraID,
                 n.nation_engl, n.iso_alpha_2_code, p.provinz, s.Fundort, s.collectionID, tst.typusID, t.typus,
                 s.Coord_W, s.W_Min, s.W_Sec, s.Coord_N, s.N_Min, s.N_Sec,
                 s.Coord_S, s.S_Min, s.S_Sec, s.Coord_E, s.E_Min, s.E_Sec, s.ncbi_accession,
                 ta.author, ta1.author author1, ta2.author author2, ta3.author author3, ta4.author author4, ta5.author author5,
                 te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3, te4.epithet epithet4, te5.epithet epithet5,
                 ts.taxonID, ts.statusID,
                 `herbar_view`.GetScientificName(ts.taxonID, 0) AS `scientificName`
                FROM tbl_specimens s
                 JOIN tbl_tax_species ts            ON ts.taxonID = s.taxonID
                 JOIN tbl_tax_genera tg             ON tg.genID = ts.genID
                 JOIN tbl_tax_families tf           ON tf.familyID = tg.familyID
                 JOIN tbl_management_collections mc ON mc.collectionID = s.collectionID
                 JOIN tbl_img_definition tid        ON tid.source_id_fk = mc.source_id
                 JOIN meta m                        ON mc.source_ID = m.source_ID 
                 LEFT JOIN tbl_specimens_types tst          ON tst.specimenID = s.specimen_ID
                 LEFT JOIN tbl_specimens_series ss          ON ss.seriesID = s.seriesID
                 LEFT JOIN tbl_typi t                       ON t.typusID = s.typusID
                 LEFT JOIN tbl_geo_province p               ON p.provinceID = s.provinceID
                 LEFT JOIN tbl_geo_nation n                 ON n.NationID = s.NationID
                 LEFT JOIN tbl_geo_region r                 ON r.regionID = n.regionID_fk
                 LEFT JOIN tbl_collector c                  ON c.SammlerID = s.SammlerID
                 LEFT JOIN tbl_collector_2 c2               ON c2.Sammler_2ID = s.Sammler_2ID
                 LEFT JOIN tbl_tax_authors ta               ON ta.authorID = ts.authorID
                 LEFT JOIN tbl_tax_authors ta1              ON ta1.authorID = ts.subspecies_authorID
                 LEFT JOIN tbl_tax_authors ta2              ON ta2.authorID = ts.variety_authorID
                 LEFT JOIN tbl_tax_authors ta3              ON ta3.authorID = ts.subvariety_authorID
                 LEFT JOIN tbl_tax_authors ta4              ON ta4.authorID = ts.forma_authorID
                 LEFT JOIN tbl_tax_authors ta5              ON ta5.authorID = ts.subforma_authorID
                 LEFT JOIN tbl_tax_epithets te              ON te.epithetID = ts.speciesID
                 LEFT JOIN tbl_tax_epithets te1             ON te1.epithetID = ts.subspeciesID
                 LEFT JOIN tbl_tax_epithets te2             ON te2.epithetID = ts.varietyID
                 LEFT JOIN tbl_tax_epithets te3             ON te3.epithetID = ts.subvarietyID
                 LEFT JOIN tbl_tax_epithets te4             ON te4.epithetID = ts.formaID
                 LEFT JOIN tbl_tax_epithets te5             ON te5.epithetID = ts.subformaID
                 LEFT JOIN herbar_pictures.phaidra_cache ph ON ph.specimenID = s.specimen_ID
                WHERE specimen_ID IN (" . implode(', ', $specimenIDs) . ")";
    $resultSpecimen = $dbLnk2->query($sqlSpecimen);

    while ($specimen = $resultSpecimen->fetch_assoc()) {
        echo "<tr>\n";

        $link = true;
        if ($specimen['observation']) {
            if ($specimen['digital_image_obs']) {
                $image = "obs.png";
            } else {
                $image = "obs_bw.png";
                $link = false;
            }
        } else {
            if ($specimen['digital_image'] || $specimen['digital_image_obs']) {
                if ($specimen['digital_image_obs'] && $specimen['digital_image']) {
                    $image = "spec_obs.png";
                } elseif ($specimen['digital_image_obs'] && !$specimen['digital_image']) {
                    $image = "obs.png";
                } else {
                    $image = "camera.png";
                }
            } else {
                $image = "";
                $link = false;
            }
        }
        if (strlen($image) > 0) {
            echo "<td class=\"result\">";
            if ($link) {
                if ($specimen['iiif_capable'] || $specimen['phaidraID']) {
                    $config = Settings::Load();
                    $ch = curl_init($config->get('JACQ_SERVICES') . "iiif/manifestUri/" . $specimen['specimen_ID']);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $curl_response = curl_exec($ch);
                    if ($curl_response !== false) {
                        $curl_result = json_decode($curl_response, true);
                        $manifest = $curl_result['uri'];
                    } else {
                        $manifest = "";
                    }
                    curl_close($ch);
                    echo "<a href='" . $specimen['iiif_url'] . "?manifest=$manifest' target='imgBrowser'>"
                        . "<img border='2' height='15' src='images/$image' width='15'>"
                        . "</a>&nbsp;"
                        . "<a href='" . $specimen['iiif_url'] . "?manifest=$manifest' target='_blank'>"
                        . "<img border='2' height='15' src='images/logo-iiif.png' width='15'>"
                        . "</a>";
                } else {
                    echo "<a href='image.php?filename={$specimen['specimen_ID']}&method=show' target='imgBrowser'>"
                        . "<img border='2' height='15' src='images/$image' width='15'></a>";
                }
            } else {
                echo "<img height=\"15\" src=\"images/$image\" width=\"15\">";
            }
            echo "</td>\n";
        } else {
            echo "<td class=\"result\"></td>\n";
        }
        echo "<td class='result' valign='top'>"
            . "<a href='detail.php?ID=" . $specimen['specimen_ID'] . "' target='_blank'>" . $specimen['scientificName'] . "</a>" . getTaxonAuth($specimen['taxid'] ?? 0)
            . "</td>"
            . "<td class=\"result\" valign=\"top\">"
            . rdfcollection($specimen)
            . "</td>"
            . "<td class=\"result\" valign=\"top\">"
            . htmlspecialchars($specimen['Datum'])
            . "</td>";

        echo "<td class=\"result\" valign=\"top\">";
        $switch = false;
        if ($specimen['nation_engl']) {
            echo "<img src='images/flags/" . strtolower($specimen['iso_alpha_2_code']) . ".png'> " . $specimen['nation_engl'];
            $switch = true;
        }
        if ($specimen['provinz']) {
            if ($switch) {
                echo ". ";
            }
            echo $specimen['provinz'];
            $switch = true;
        }
        if (trim($specimen['Fundort'])) {
            if ($switch) {
                echo ". ";
            }
            if (strlen(trim($specimen['Fundort'])) > 200) {
                echo substr(trim($specimen['Fundort']), 0, 200) . "...";
            } else {
                echo trim($specimen['Fundort']);
            }
            $switch = true;
        }
        echo "</td>";

        echo "<td class=\"result\" valign=\"top\">"
            . (($specimen['typusID']) ? "<font color=\"red\"><b>" . $specimen['typus'] . "</b></font>" : "") . "</td>\n";

        // do special threatment for source 29 (B)
        if ($specimen['source_id'] == '29') {
            echo "<td class='result' valign='top' title='" . htmlspecialchars($specimen['collection']) . "'>" . htmlspecialchars($specimen['HerbNummer']) . "</td>";
        } else {
            echo "<td class='result' valign='top' title='" . htmlspecialchars($specimen['collection']) . "'>"
                . htmlspecialchars(mb_strtoupper($specimen['coll_short_prj'])) . " " . htmlspecialchars($specimen['HerbNummer']) . "</td>";
            //. htmlspecialchars(collectionItem($specimen['collection'])) . " " . htmlspecialchars($specimen['HerbNummer']) . "</td>";
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
            echo "<td class='result' style='text-align: center' title='" . round($lat, 5) . "&deg; / " . round($lon, 5) . "&deg;'>"
//            . "<a href='https://opentopomap.org/#marker=12/$lat/$lon' target='_blank'>"
                . "<a href='#' onClick='osMap(" . $specimen['specimen_ID'] . "); return false;'>"
                . "<img border='0' height='15' src='assets/images/OpenStreetMap.png' width='15'></a></td>";
        } else {
            echo "<td class='result'></td>\n";
        }

//    if ($specimen['ncbi_accession']) {
//        echo "<td class='result' style='text-align: center' title='" . $specimen['ncbi_accession'] . "'>"
//            . "<a href='http://www.ncbi.nlm.nih.gov/entrez/query.fcgi?db=Nucleotide&cmd=search&term=" . $specimen['ncbi_accession'] . "' target='_blank'>"
//            . "<img border='0' height='16' src='images/ncbi.gif' width='14'></a></td>\n";
//    } else {
//        echo "<td class='result'></td>\n";
//    }

        echo "</tr>\n";
    }
}
?>
          </table>
        </div>
      </td>
    </tr>
    <tr>
      <td valign="top" colspan="9" align="center">
        <?php echo $navigation; ?>
      </td>
    </tr>
  </table>
</div>
