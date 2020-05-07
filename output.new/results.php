<?php
die();
// don't use this file, as it is depricated
// will be erased in the future
// joschach@ap4net.at  4.5.2020

session_start();
if (empty($_SESSION['s_query'])) { header("location:search.php"); } // if no sessions -> forward to search page

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Cache-Control: post-check=0, pre-check=0", false);

require("inc/dev-functions.php");

function collectionItem($coll)
{
    if (strpos($coll, "-") !== false) {
        return substr($coll, 0, strpos($coll, "-"));
    } elseif (strpos($coll, " ") !== false) {
        return substr($coll, 0, strpos($coll, " "));
    } else {
        return($coll);
    }
}
/*
Fuer die Webabfrage brauchen wir nur(!!) die folgenden Tabellen:
- tbl_collector
- tbl_collector_2
- tbl_management_collections
- tbl_nation
- tbl_province
tbl_tax_authors
tbl_tax_epithets
- tbl_tax_families
- tbl_tax_genera
- tbl_tax_species
tbl_tax_status
tbl_tax_systematic_categories
- tbl_typi
- tbl_wu_generale
*/

?>
<script type="text/javascript" language="javascript"><!--
  function neuladen(url) {
    location.replace(url);
  }
  function osMap() {
    MeinFenster = window.open('os_maps.php','_blank',
                              'width=820,height=620,top=50,left=50,resizable,scrollbars');
    MeinFenster.focus();
  }
--></script>

<div class="divider"></div>

<div align="center">
  <table border="0" cellpadding="0" cellspacing="0" width="800">
    <tr>
      <td valign="top" colspan="9">

<?php
//Default Value setzen
if(!isset($_SESSION['order'])) {
    $_SESSION['order'] = 1;
}

//Wenn Order gesendet wird ggf. Updaten
if(isset($_GET['order'])) {
    if($_GET['order'] == 2) {
        $_SESSION['order'] = 2;
    }
    else {
        $_SESSION['order'] = 1;
    }
}

//Übernommen aus altem Code und GET durch SESSION ersetzt
if ($_SESSION['order'] == 2) {
    $sql = $_SESSION['s_query'] . "ORDER BY Sammler, Sammler_2, series, Nummer, HerbNummer"; }
else {
    $sql = $_SESSION['s_query'] . "ORDER BY genus, epithet, author, HerbNummer";
}


/**
 * pagination
 */
if (empty($_SESSION['ITEMS_PER_PAGE'])) {
    $_SESSION['ITEMS_PER_PAGE'] = 10;
}
$limits=array(10, 30, 50, 100);
if (!empty($_GET['ITEMS_PER_PAGE']) && intval($_GET['ITEMS_PER_PAGE']) != 0 && in_array(intval($_GET['ITEMS_PER_PAGE']), $limits) ){
	$_SESSION['ITEMS_PER_PAGE'] = intval($_GET['ITEMS_PER_PAGE']);
}
$page = (!empty($_GET['page'])) ? intval($_GET['page']) : 1;
if ($page < 1) {
    $page = 1;
}

$sql .= " LIMIT " . ($_SESSION['ITEMS_PER_PAGE'] * ($page - 1)) . ", " . $_SESSION['ITEMS_PER_PAGE'];
//echo $sql;
$result = $dbLink->query($sql);
if ($dbLink->errno) {
    echo $sql . "<br>\n";
    echo $dbLink->error . "<br>\n";
}
$res_count = $dbLink->query("select found_rows()");
if ($res_count) {
	$res_count_row = $res_count->fetch_row();
	$nrRows = intval($res_count_row[0]);
} else {
    $nrRows = 0;
}

$a = paginate_three($_SERVER['SCRIPT_NAME'].'?s=s&order=2', $page, ceil($nrRows / $_SESSION['ITEMS_PER_PAGE']), 2, 2);
$b = "";
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


//echo "<b>".mysql_num_rows($result)." records found</b>\n<p>\n";
?>
        <div align='center'>
          <table width='100%'>
            <tr>
              <td colspan='2'><b><?php echo $nrRows; ?> record<?php echo ($nrRows > 1) ? "s" : ""; ?> found</b></td>
              <td colspan="7" align="right">
                <form style="display:inline;" action="javascript:osMap();" method="post">
                   <button class="btn-flat waves-effect waves-light" type="button" name="action" value="Create map" onClick="osMap()">Create map
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
                $manifest = StableIdentifier($row['source_id'], $row['HerbNummer'], $row['specimen_ID'], false) . '/manifest.json';
            	echo "<a href='" . $protocol . $row['iiif_proxy'] . $row['iiif_dir'] . "/?manifest=$manifest' target='imgBrowser'>"
               		. "<img border='2' height='15' src='images/$image' width='15'></a>";
                echo "&nbsp;<a href='" . $protocol . $row['iiif_proxy'] . $row['iiif_dir'] . "/?manifest=$manifest' target='_blank'>"
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
    echo "<td class=\"result\" valign=\"top\"><a href=\"detail.php?ID=" . $row['specimen_ID'] . "\" target=\"_blank\">"
        . taxonWithHybrids($row)
        . "</a>". getTaxonAuth($row['taxid']) ."</td>";

    echo "<td class=\"result\" valign=\"top\">"
        . rdfcollection($row)
        . "</td>";

    echo "<td class=\"result\" valign=\"top\">"
        . htmlspecialchars($row['Datum'])
        . "</td>";

    echo "<td class=\"result\" valign=\"top\">";
    $switch = false;
    if ($row['nation_engl']) {
        echo "<img src=\"images/flags/" . strtolower($row['iso_alpha_2_code']) . ".png\"> " . $row['nation_engl'];
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


    if ($row['source_id'] == '29') {
        echo "<td class=\"result\" valign=\"top\" title=\"" . htmlspecialchars($row['collection']) . "\">" . htmlspecialchars($row['HerbNummer']) . "</td>";
    } else {
        echo "<td class=\"result\" valign=\"top\" title=\"" . htmlspecialchars($row['collection']) . "\">"
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
        echo "<td class=\"result\" style=\"text-align: center\" title=\"".round($lat,2)."&deg; / ".round($lon,2)."&deg;\">"
            . "<a href=\"http://www.mapquest.com/maps/map.adp?latlongtype=decimal&longitude=$lon&latitude=$lat&zoom=3\" "
            .  "target=\"_blank\"><img border=\"0\" height=\"15\" src=\"images/mapquest.png\" width=\"15\"></a>&nbsp;"
            //         "<a href=\"http://onearth.jpl.nasa.gov/landsat.cgi?zoom=0.0005556&x0=$lon&y0=$lat&action=zoomin".
            //          "&layer=modis%252Cglobal_mosaic&pwidth=800&pheight=600\" ".
            //          "target=\"_blank\"><img border=\"0\" height=\"15\" src=\"images/nasa.png\" width=\"15\"></a>".
            . "</td>\n";
    } else {
        echo "<td class=\"result\"></td>\n";
    }

  //  if ($row['ncbi_accession']) {
     //   echo "<td class=\"result\" style=\"text-align: center\" title=\"".$row['ncbi_accession']."\">"
    //        . "<a href=\"http://www.ncbi.nlm.nih.gov/entrez/query.fcgi?db=Nucleotide&cmd=search&term=".$row['ncbi_accession']."\" "
   //         .  "target=\"_blank\"><img border=\"0\" height=\"16\" src=\"images/ncbi.gif\" width=\"14\"></a></td>\n";
   // } else {
  //      echo "<td class=\"result\"></td>\n";
  //  }


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