<?php
session_start();
require("inc/functions.php");
?>
<html>
<head>
<title>Virtual Herbaria / search results</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="description" content="FW4 DW4 HTML">
<!-- Fireworks 4.0  Dreamweaver 4.0 target.  Created Fri Nov 08 15:05:42 GMT+0100 (Westeurop�ische Normalzeit) 2002-->
<link rel="stylesheet" href="../herbarium.css" type="text/css">

<script type="text/javascript" language="javascript"><!--
  function neuladen(url) {
    location.replace(url);
  }
  function googleMap() {
    MeinFenster = window.open('google_maps.php','_blank',
                              'width=820,height=620,top=50,left=50,resizable,scrollbars');
    MeinFenster.focus();
  }
  --></script>
</head>
<body bgcolor="#ffffff">
<div align="center">
  <table border="0" cellpadding="0" cellspacing="0" width="800">
    <!-- fwtable fwsrc="databasemenu.png" fwbase="databasemenu.gif" fwstyle="Dreamweaver" fwdocid = "742308039" fwnested="0" -->
    <tr>
      <td height="50" valign="top" colspan="9">
        <?php
function collectionItem($coll) {

  if (strpos($coll,"-")!==false)
    return substr($coll,0,strpos($coll,"-"));
  elseif (strpos($coll," ")!==false)
    return substr($coll,0,strpos($coll," "));
  else return($coll);

}
/*
F�r die Webabfrage brauchen wir nur(!!) die folgenden Tabellen:
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
      </td>
    </tr>
    <tr>
      <!-- Shim row, height 1. -->
      <td><img src="images/spacer.gif" width="198" height="1" border="0"></td>
      <td><img src="images/spacer.gif" width="2" height="1" border="0"></td>
      <td><img src="images/spacer.gif" width="197" height="1" border="0"></td>
      <td><img src="images/spacer.gif" width="2" height="1" border="0"></td>
      <td><img src="images/spacer.gif" width="198" height="1" border="0"></td>
      <td><img src="images/spacer.gif" width="2" height="1" border="0"></td>
      <td><img src="images/spacer.gif" width="200" height="1" border="0"></td>
      <td><img src="images/spacer.gif" width="1" height="1" border="0"></td>
      <td><img src="images/spacer.gif" width="1" height="1" border="0"></td>
    </tr>
    <tr>
      <!-- row 1 -->
      <td colspan="8"><img name="databasemenu_r1_c1" src="images/databasemenu_r1_c1.gif" width="800" height="93" border="0" alt="virtual herbarium WU"></td>
      <td><img src="images/spacer.gif" width="1" height="93" border="0"></td>
    </tr>
    <tr>
      <!-- row 2 -->
      <td><a href="../index.htm"><img name="databasemenu_r2_c1" src="images/databasemenu_r2_c1.gif" width="198" height="37" border="0" alt="home"></a></td>
      <td><img name="databasemenu_r2_c2" src="images/databasemenu_r2_c2.gif" width="2" height="37" border="0" alt="herbarmenu"></td>
      <td><a href="index.php"><img name="databasemenu_r2_c3" src="images/databasemenu_r2_c3.gif" width="197" height="37" border="0" alt="general information"></a></td>
      <td><img name="databasemenu_r2_c4" src="images/databasemenu_r2_c4.gif" width="2" height="37" border="0" alt="herbarmenu"></td>
      <td><a href="collections.htm"><img name="databasemenu_r2_c5" src="images/databasemenu_r2_c5.gif" width="198" height="37" border="0" alt="collections"></a></td>
      <td><img name="databasemenu_r2_c6" src="images/databasemenu_r2_c6.gif" width="2" height="37" border="0" alt="herbarmenu"></td>
      <td><a href="refsystems.htm"><img name="databasemenu_r2_c7" src="images/databasemenu_r2_c7.gif" width="200" height="37" border="0" alt="reference systems"></a></td>
      <td><img name="databasemenu_r2_c8" src="images/databasemenu_r2_c8.gif" width="1" height="37" border="0" alt="herbarmenu"></td>
      <td><img src="images/spacer.gif" width="1" height="37" border="0"></td>
    </tr>
    <tr>
      <td height="40" valign="top" colspan="9">&nbsp;</td>
    </tr>
    <tr>
      <td valign="top" colspan="9">
        <?php
if (empty($_SESSION['s_query'])) die("The database is under maintenance and currently not available!"); // if no sessions -> stop script

if ($_GET['order']==2)
  $sql = $_SESSION['s_query']."ORDER BY Sammler, Sammler_2, series, Nummer";
else
  $sql = $_SESSION['s_query']."ORDER BY genus, epithet, author";

//echo $sql;
$result = mysql_query($sql);
if (!$result) {
  echo $sql."<br>\n";
  echo mysql_error()."<br>\n";
}
//echo "<b>".mysql_num_rows($result)." records found</b>\n<p>\n";
echo "<div align=\"center\"><table width=\"100%\">\n";
echo "<tr><td colspan=\"3\"><b>".mysql_num_rows($result)." records found</b></td>\n";

// Values f�r "Generic Point Mapper" der "Canadian Biological Information Facility" erzeugen
$xml_head = "%3C%3Fxml+version%3D%221.0%22+encoding%3D%22iso-8859-1%22%3F%3E";
$xml = "<response>".
       "  <header>".
       "    <author>Virtual Herbaria Austria</author>".
       "    <boundingbox>-180,-90,180,90,World Map</boundingbox>".
       "    <description>This Generic Point Mapper is a service provided by the Canadian Biological Information Facility</description>".
       "    <ifx></ifx>".
       "    <language>en</language>".
       "    <projection>latlong</projection>".
       "    <recordcount></recordcount>".
       "    <timestamp>2005-02-10T09:46:41Z</timestamp>".
       "    <title></title>".
       "    <url>http://www.cbif.gc.ca/mc/index_e.php</url>".
       "  </header>".
       "  <records>";
while ($row=mysql_fetch_array($result)) {
  if ($row['Coord_S']>0 || $row['S_Min']>0 || $row['S_Sec']>0)
    $lat = -($row['Coord_S'] + $row['S_Min'] / 60 + $row['S_Sec'] / 3600);
  else if ($row['Coord_N']>0 || $row['N_Min']>0 || $row['N_Sec']>0)
    $lat = $row['Coord_N'] + $row['N_Min'] / 60 + $row['N_Sec'] / 3600;
  else
    $lat = 0;
  if ($row['Coord_W']>0 || $row['W_Min']>0 || $row['W_Sec']>0)
    $lon = -($row['Coord_W'] + $row['W_Min'] / 60 + $row['W_Sec'] / 3600);
  else if ($row['Coord_E']>0 || $row['E_Min']>0 || $row['E_Sec']>0)
    $lon = $row['Coord_E'] + $row['E_Min'] / 60 + $row['E_Sec'] / 3600;
  else
    $lon = 0;
  if ($lat!=0 && $lon!=0)
    $xml .= "    <record><longitude>$lon</longitude><latitude>$lat</latitude>".
            "<recordurl>http://herbarium.univie.ac.at/database/detail.php?ID=".$row['specimen_ID']."</recordurl>".
            "</record>";
}
//if ($results)
@mysql_data_seek($result,0);
$xml .= "  </records>".
        "</response>";
?>
<td colspan="6" align="right">
<form style="display:inline;" action="http://linuxgurrl.agr.gc.ca/mapdata/itis/itisrosa.php" method="post" target="showlinuxgurrl">
  <input type="button" value="Create google-map" onclick="googleMap()">
  <input type="hidden" value="<?= $xml_head.urlencode($xml) ?>" name="xml">
  <input type="hidden" value="L" name="msize">
  <input type="submit" value="Create map" style="width:100px;">
</form>
</td></tr>
<tr><td colspan=\"3\"></td><td colspan="6" align="right">
<form style="display:inline;" action="exportKml.php" method="post" target="_blank">
  <input type="submit" value="download KML" style="width:120px;">
</form>
<form style="display:inline;" action="exportCsv.php" method="post" target="_blank">
  <input type="submit" value="download CSV" style="width:120px;">
</form>
</td></tr>

<?php
echo "<tr><td colspan=\"9\">&nbsp;</td></tr>\n";
echo "<tr bgcolor=\"#EEEEEE\">";
echo "<th></th>".
     "<th class=\"result\"><a href=\"javascript:neuladen('$PHP_SELF?order=1')\">Taxon</a></th>".
     "<th class=\"result\"><a href=\"javascript:neuladen('$PHP_SELF?order=2')\">Collector</a></th>".
     "<th class=\"result\">Date</th><th class=\"result\">Location</th>".
     "<th class=\"result\">Typus</th><th class=\"result\">Coll.</th>".
     "<th class=\"result\">Lat/Lon</th><th class=\"result\">NCBI</th></tr>\n";
$bgcolor = "#FFFFFF";
while ($row=mysql_fetch_array($result)) {
  echo "<tr bgcolor=\"$bgcolor\">\n";

  $link = true;
  if ($row['observation']) {
    if ($row['digital_image_obs'])
      $image = "obs.png";
    else {
      $image = "obs_bw.png";
      $link = false;
    }
  }
  else {
    if ($row['digital_image']) {
      if ($row['digital_image_obs'])
        $image = "spec_obs.png";
      else
        $image = "camera.png";
    }
    else {
      $image = "";
      $link = false;
    }
  }
  if (strlen($image)>0) {
    echo "<td class=\"result\">";
    if ($link)
      echo "<a href=\"image/{$row['specimen_ID']}/show\" target=\"imgBrowser\">".
           "<img border=\"2\" height=\"15\" src=\"images/$image\" width=\"15\"></a>";
    else
      echo "<img height=\"15\" src=\"images/$image\" width=\"15\">";
    echo "</td>\n";
  }
  else
    echo "<td class=\"result\"></td>\n";

  echo "<td class=\"result\" valign=\"top\"><a href=\"detail.php?ID=".$row['specimen_ID']."\" target=\"_blank\">";
  echo taxonWithHybrids($row);
  echo "</a></td>";

  echo "<td class=\"result\" valign=\"top\">";
  echo collection($row['Sammler'],$row['Sammler_2'],$row['series'],$row['series_number'],
                  $row['Nummer'],$row['alt_number'],$row['Datum']);
  echo "</td>";

  echo "<td class=\"result\" valign=\"top\">".$row['Datum']."</td>";

  echo "<td class=\"result\" valign=\"top\">";
  $switch = false;
  if ($row['nation_engl']) {
    echo "<img src=\"images/flags/".strtolower($row['iso_alpha_2_code']).".png\"> ".$row['nation_engl'];
    $switch = true;
  }
  if ($row['provinz']) {
    if ($switch) echo ". ";
    echo $row['provinz'];
    $switch = true;
  }
  echo "</td>";

  echo "<td class=\"result\" style=\"text-align: center\">".
       (($row['typusID']) ? "<font color=\"red\"><b>".$row['typus']."</b></font>" : "")."</td>\n";

  echo "<td class=\"result\" style=\"text-align: center\" title=\"".htmlspecialchars($row['collection'])."\">".
            htmlspecialchars(collectionItem($row['collection']))." ".htmlspecialchars($row['HerbNummer'])."</td>";

  if ($row['Coord_S']>0 || $row['S_Min']>0 || $row['S_Sec']>0)
    $lat = -($row['Coord_S'] + $row['S_Min'] / 60 + $row['S_Sec'] / 3600);
  else if ($row['Coord_N']>0 || $row['N_Min']>0 || $row['N_Sec']>0)
    $lat = $row['Coord_N'] + $row['N_Min'] / 60 + $row['N_Sec'] / 3600;
  else
    $lat = 0;
  if ($row['Coord_W']>0 || $row['W_Min']>0 || $row['W_Sec']>0)
    $lon = -($row['Coord_W'] + $row['W_Min'] / 60 + $row['W_Sec'] / 3600);
  else if ($row['Coord_E']>0 || $row['E_Min']>0 || $row['E_Sec']>0)
    $lon = $row['Coord_E'] + $row['E_Min'] / 60 + $row['E_Sec'] / 3600;
  else
    $lon = 0;
  if ($lat!=0 || $lon!=0)
    echo "<td class=\"result\" style=\"text-align: center\" title=\"".round($lat,2)."&deg; / ".round($lon,2)."&deg;\">".
         "<a href=\"http://www.mapquest.com/maps/map.adp?latlongtype=decimal&longitude=$lon&latitude=$lat&zoom=3\" ".
          "target=\"_blank\"><img border=\"0\" height=\"15\" src=\"images/mapquest.png\" width=\"15\"></a>&nbsp;".
//         "<a href=\"http://onearth.jpl.nasa.gov/landsat.cgi?zoom=0.0005556&x0=$lon&y0=$lat&action=zoomin".
//          "&layer=modis%252Cglobal_mosaic&pwidth=800&pheight=600\" ".
//          "target=\"_blank\"><img border=\"0\" height=\"15\" src=\"images/nasa.png\" width=\"15\"></a>".
         "</td>\n";
  else
    echo "<td class=\"result\"></td>\n";

  if ($row['ncbi_accession'])
    echo "<td class=\"result\" style=\"text-align: center\" title=\"".$row['ncbi_accession']."\">".
         "<a href=\"http://www.ncbi.nlm.nih.gov/entrez/query.fcgi?db=Nucleotide&cmd=search&term=".$row['ncbi_accession']."\" ".
          "target=\"_blank\"><img border=\"0\" height=\"16\" src=\"images/ncbi.gif\" width=\"14\"></a></td>\n";
  else
    echo "<td class=\"result\"></td>\n";


  echo "</tr>\n";
  $bgcolor = ($bgcolor=="#FFFFFF") ? "#EEEEEE" : "#FFFFFF";
}
echo "</table></div>\n";
?>
      </td>
    </tr>
    <tr>
      <td valign="top" colspan="9" align="center">
        <HR SIZE=1  width="800" NOSHADE>
        <p class="normal"><b>database management and digitizing</b> -- <a href="mailto:heimo.rainer@univie.ac.at">Heimo
          Rainer<br></a><br>
          <b>php-programming</b> -- <a href="mailto:joschach@EUnet.at">Johannes
          Schachner</a></p>
        <div class="normal" align="center">
          <!-- #BeginEditable "Datum" --><B>Last modified:</B> <EM>2006-Apr-11,
          HR</EM><!-- #EndEditable --> </div>
      </td>
    </tr>
  </table>
</div>
</body>
</html>
