<?php
if (empty($_SESSION['s_query'])) { die(); } // nothing to do

require_once 'inc/functions.php';

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

// set order of query
if ($_SESSION['order'] == 2) {
    $sql = $_SESSION['s_query'] . "ORDER BY Sammler, Sammler_2, series, Nummer, HerbNummer"; }
else {
    $sql = $_SESSION['s_query'] . "ORDER BY genus, epithet, author, HerbNummer";
}

/**
 * pagination
 */
$sql .= " LIMIT " . ($_SESSION['ITEMS_PER_PAGE'] * ($page - 1)) . ", " . $_SESSION['ITEMS_PER_PAGE'];
$result = $dbLink->query($sql);                     // get the result of the query for further processing
$res_count = $dbLink->query("select found_rows()"); // and the complete number of found rows
if ($res_count) {
	$res_count_row = $res_count->fetch_row();
	$nrRows = intval($res_count_row[0]);
} else {
    $nrRows = 0;
}

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
                <form style="display:inline;" action="javascript:osMap();" method="post">
                   <button class="btn-flat waves-effect waves-light" type="button" name="action" value="Create map" onClick="osMap(-1)">Create map
                  </button>
                </form>
                <form style="display:inline;" action="exportKml.php" method="post" target="_blank">
                  <button class="btn-flat waves-effect waves-light" type="submit" name="action" value="download KML">Download KML</button>
                </form>
                <form style="display:inline;" action="exportCsv.php" method="post" target="_blank">
                    <button class="btn-flat waves-effect waves-light" type="submit" name="action" value="download CSV">Download CSV</button>
                </form>
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
// process results and show table
while ($row = $result->fetch_array()) {
    echo "<tr>\n";

    $link = true;
    if ($row['observation']) {
        if ($row['digital_image_obs']) {
            $image = "obs.png";
        } else {
            $image = "obs_bw.png";
            $link = false;
        }
    } else {
        if ($row['digital_image'] || $row['digital_image_obs']) {
            if ($row['digital_image_obs'] && $row['digital_image']) {
                $image = "spec_obs.png";
            } elseif ($row['digital_image_obs'] && !$row['digital_image']) {
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
            if ($row['iiif_capable']) {
			    $protocol = ($_SERVER['HTTPS']) ? "https://" : "http://";
                $manifest = StableIdentifier($row['source_id'], $row['HerbNummer'], $row['specimen_ID'], false, true) . '/manifest.json';
            	echo "<a href='" . $protocol . $row['iiif_proxy'] . $row['iiif_dir'] . "/?manifest=$manifest' target='imgBrowser'>"
                   . "<img border='2' height='15' src='images/$image' width='15'></a>"
                   . "&nbsp;<a href='" . $protocol . $row['iiif_proxy'] . $row['iiif_dir'] . "/?manifest=$manifest' target='_blank'>"
                   . "<img border='2' height='15' src='images/logo-iiif.png' width='15'></a>";
            } else {
				echo "<a href='image.php?filename={$row['specimen_ID']}&method=show' target='imgBrowser'>"
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
        . "<a href='detail.php?ID=" . $row['specimen_ID'] . "' target='_blank'>" . taxonWithHybrids($row) . "</a>". getTaxonAuth($row['taxid'])
        ."</td>"
        . "<td class=\"result\" valign=\"top\">"
        . rdfcollection($row)
        . "</td>"
        . "<td class=\"result\" valign=\"top\">"
        . htmlspecialchars($row['Datum'])
        . "</td>";

    echo "<td class=\"result\" valign=\"top\">";
    $switch = false;
    if ($row['nation_engl']) {
        echo "<img src='images/flags/" . strtolower($row['iso_alpha_2_code']) . ".png'> " . $row['nation_engl'];
        $switch = true;
    }
    if ($row['provinz']) {
        if ($switch) {
            echo ". ";
        }
        echo $row['provinz'];
        $switch = true;
    }
    echo "</td>";

    echo "<td class=\"result\" valign=\"top\">"
        . (($row['typusID']) ? "<font color=\"red\"><b>" . $row['typus'] . "</b></font>" : "") . "</td>\n";

    // do special threatment for source 29 (B)
    if ($row['source_id'] == '29') {
        echo "<td class='result' valign='top' title='" . htmlspecialchars($row['collection']) . "'>" . htmlspecialchars($row['HerbNummer']) . "</td>";
    } else {
        echo "<td class='result' valign='top' title='" . htmlspecialchars($row['collection']) . "'>"
           . htmlspecialchars(collectionItem($row['collection'])) . " " . htmlspecialchars($row['HerbNummer']) . "</td>";
    }

    if ($row['Coord_S'] > 0 || $row['S_Min'] > 0 || $row['S_Sec'] > 0) {
        $lat = -($row['Coord_S'] + $row['S_Min'] / 60 + $row['S_Sec'] / 3600);
    } else if ($row['Coord_N'] > 0 || $row['N_Min'] > 0 || $row['N_Sec'] > 0) {
        $lat = $row['Coord_N'] + $row['N_Min'] / 60 + $row['N_Sec'] / 3600;
    } else {
        $lat = 0;
    }
    if ($row['Coord_W'] > 0 || $row['W_Min'] > 0 || $row['W_Sec'] > 0) {
        $lon = -($row['Coord_W'] + $row['W_Min'] / 60 + $row['W_Sec'] / 3600);
    } else if ($row['Coord_E'] > 0 || $row['E_Min'] > 0 || $row['E_Sec'] > 0) {
        $lon = $row['Coord_E'] + $row['E_Min'] / 60 + $row['E_Sec'] / 3600;
    } else {
        $lon = 0;
    }
    if ($lat != 0 || $lon != 0) {
        echo "<td class='result' style='text-align: center' title='" . round($lat, 2) . "&deg; / " . round($lon,2) . "&deg;'>"
//            . "<a href='https://opentopomap.org/#marker=12/$lat/$lon' target='_blank'>"
           . "<a href='#' onClick='osMap(" . $row['specimen_ID'] . "); return false;'>"
           . "<img border='0' height='15' src='assets/images/OpenStreetMap.png' width='15'></a></td>";
    } else {
        echo "<td class='result'></td>\n";
    }

//    if ($row['ncbi_accession']) {
//        echo "<td class='result' style='text-align: center' title='" . $row['ncbi_accession'] . "'>"
//            . "<a href='http://www.ncbi.nlm.nih.gov/entrez/query.fcgi?db=Nucleotide&cmd=search&term=" . $row['ncbi_accession'] . "' target='_blank'>"
//            . "<img border='0' height='16' src='images/ncbi.gif' width='14'></a></td>\n";
//    } else {
//        echo "<td class='result'></td>\n";
//    }

    echo "</tr>\n";
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