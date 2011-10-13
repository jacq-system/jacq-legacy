<?php
session_start();
require("inc/connect.php");
require("inc/herbardb_input_functions.php");
require("inc/api_functions.php");
require("inc/log_functions.php");
//require_once ("inc/xajax/xajax_core/xajax.inc.php");
no_magic();

$nrSel = intval($_GET['nr']);
$id = intval($_GET['ID']);

if (isset($_GET['order'])) {
  if ($_GET['order']=="b") {
    $_SESSION['ltsOrder'] = "Sammler, Sammler_2, series, Nummer, alt_number, Datum, ".
                           "typus_lat";
    if ($_SESSION['ltsOrTyp']==2)
      $_SESSION['ltsOrTyp'] = -2;
    else
      $_SESSION['ltsOrTyp'] = 2;
  }
  else if ($_GET['order']=="d") {
    $_SESSION['ltsOrder'] = "typus_lat, ".
                           "Sammler, Sammler_2, series, Nummer, alt_number, Datum";
    if ($_SESSION['ltsOrTyp']==4)
      $_SESSION['ltsOrTyp'] = -4;
    else
      $_SESSION['ltsOrTyp'] = 4;
  }
  else if ($_GET['order']=="e") {
    $_SESSION['ltsOrder'] = "collection, HerbNummer";
    if ($_SESSION['ltsOrTyp']==5)
      $_SESSION['ltsOrTyp'] = -5;
    else
      $_SESSION['ltsOrTyp'] = 5;
  }
  else {
    $_SESSION['ltsOrder'] = "Sammler, Sammler_2, series, Nummer, alt_number, Datum, ".
                           "typus_lat";
    if ($_SESSION['ltsOrTyp']==1)
      $_SESSION['ltsOrTyp'] = -1;
    else
      $_SESSION['ltsOrTyp'] = 1;
  }
  if ($_SESSION['ltsOrTyp']<0) $_SESSION['ltsOrder'] = implode(" DESC, ",explode(", ",$_SESSION['ltsOrder']))." DESC";
}
else {
  $_SESSION['ltsOrder'] = " Sammler, Sammler_2, series, Nummer, alt_number, Datum, typus_lat";
  $_SESSION['ltsOrTyp'] = 1;
}

function collectorItem($row) {

  $text = $row['Sammler'];
  if (strstr($row['Sammler_2'],"&") || strstr($row['Sammler_2'],"et al."))
    $text .= " et al.";
  elseif ($row['Sammler_2'])
    $text .= " & ".$row['Sammler_2'];
  if ($row['series_number']) {
    if ($row['Nummer']) $text .= " ".$row['Nummer'];
    if ($row['alt_number'] && trim($row['alt_number'])!="s.n.") $text .= " ".$row['alt_number'];
    if ($row['series']) $text .= " ".$row['series'];
    $text .= " ".$row['series_number'];
  }
  else {
    if ($row['series']) $text .= " ".$row['series'];
    if ($row['Nummer']) $text .= " ".$row['Nummer'];
    if ($row['alt_number']) $text .= " ".$row['alt_number'];
    if (strstr($row['alt_number'],"s.n.")) $text .= " [".$row['Datum']."]";
  }

  return $text;
}

function locationItem($row) {

  $text = "";
  if (trim($row['nation_engl'])) {
    $text = "<span style=\"background-color:white;\">".htmlspecialchars(trim($row['nation_engl']))."</span>";
  }
  if (trim($row['provinz'])) {
    if (strlen($text)>0) $text .= ". ";
    $text .= "<span style=\"background-color:white;\">".htmlspecialchars(trim($row['provinz']))."</span>";
  }
  if (trim($row['Fundort']) && $row['collectionID']!=12) {
    if (strlen($text)>0) $text .= ". ";
    $text .= htmlspecialchars(trim($row['Fundort']));
  }

  return $text;
}

function collectionItem($coll) {

  if (strpos($coll,"-")!==false)
    return substr($coll,0,strpos($coll,"-"));
  elseif (strpos($coll," ")!==false)
    return substr($coll,0,strpos($coll," "));
  else return($coll);

}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - list Specimens</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <script type="text/javascript" language="JavaScript">
    function showImage(sel, server) {
      target = server+"/"+sel+"/show";
      MeinFenster = window.open(target,"imgBrowser");
      MeinFenster.focus();
    }
  </script>
</head>

<body>
<div style="background-color:white;">typified Name:
<?php
$sql = "SELECT tg.genus,
         ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
         ta4.author author4, ta5.author author5,
         te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
         te4.epithet epithet4, te5.epithet epithet5
        FROM (tbl_tax_species ts, tbl_tax_genera tg)
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
        WHERE ts.taxonID='$id'
         AND tg.genID=ts.genID";
$result = db_query($sql);
$row=mysql_fetch_array($result);
echo htmlspecialchars(taxonItem($row)) . "</div>\n<p>\n";

$sql = "SELECT s.specimen_ID, tg.genus, s.digital_image,
         c.Sammler, c2.Sammler_2, ss.series, s.series_number,
         s.Nummer, s.alt_number, s.Datum, s.HerbNummer,
         n.nation_engl, p.provinz, s.Fundort, mc.collection, mc.coll_short, t.typus_lat,
         s.Coord_W, s.W_Min, s.W_Sec, s.Coord_N, s.N_Min, s.N_Sec,
         s.Coord_S, s.S_Min, s.S_Sec, s.Coord_E, s.E_Min, s.E_Sec, s.ncbi_accession,
         ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
         ta4.author author4, ta5.author author5,
         te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
         te4.epithet epithet4, te5.epithet epithet5
        FROM (tbl_specimens s, tbl_tax_species ts, tbl_tax_genera tg, tbl_specimens_types st, tbl_management_collections mc)
         LEFT JOIN tbl_specimens_series ss ON ss.seriesID=s.seriesID
         LEFT JOIN tbl_typi t ON t.typusID=s.typusID
         LEFT JOIN tbl_geo_province p ON p.provinceID=s.provinceID
         LEFT JOIN tbl_geo_nation n ON n.NationID=s.NationID
         LEFT JOIN tbl_geo_region r ON r.regionID=n.regionID_fk
         LEFT JOIN tbl_collector c ON c.SammlerID=s.SammlerID
         LEFT JOIN tbl_collector_2 c2 ON c2.Sammler_2ID=s.Sammler_2ID
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
        WHERE st.specimenID=s.specimen_ID
         AND ts.taxonID=s.taxonID
         AND mc.collectionID=s.collectionID
         AND st.taxonID='$id'
         AND tg.genID=ts.genID";
$result = db_query($sql." GROUP BY s.specimen_ID ORDER BY ".$_SESSION['ltsOrder']);
if (mysql_num_rows($result)>0) {
  echo "<table class=\"out\" cellspacing=\"0\">\n";
  echo "<tr class=\"out\">";
  echo "<th class=\"out\"></th>";
  echo "<th class=\"out\">".
       "<a href=\"".$_SERVER['PHP_SELF']."?ID=$id&order=a\">filed Name</a>".sortItem($_SESSION['ltsOrTyp'],1)."</th>";
  echo "<th class=\"out\">".
       "<a href=\"".$_SERVER['PHP_SELF']."?ID=$id&order=b\">Collector</a>".sortItem($_SESSION['ltsOrTyp'],2)."</th>";
  echo "<th class=\"out\">Date</th>";
  echo "<th class=\"out\">X/Y</th>";
  echo "<th class=\"out\">Location</th>";
  echo "<th class=\"out\">".
       "<a href=\"".$_SERVER['PHP_SELF']."?ID=$id&order=d\">Typus</a>".sortItem($_SESSION['ltsOrTyp'],4)."</th>";
  echo "<th class=\"out\">".
       "<a href=\"".$_SERVER['PHP_SELF']."?ID=$id&order=e\">Coll.</a>".sortItem($_SESSION['ltsOrTyp'],5)."</th>";
  if ($swBatch) echo "<th class=\"out\">Batch</th>";
  echo "</tr>\n";
  $nr = 1;
  while ($row=mysql_fetch_array($result)) {
    $linkList[$nr] = $row['specimen_ID'];

    if ($row['digital_image'])
      $digitalImage = "<a href=\"javascript:showImage('".$row['specimen_ID']."', '".getPictureServerIP($row['specimen_ID'])."')\">".
                       "<img border=\"0\" height=\"15\" src=\"webimages/camera.png\" width=\"15\">".
                      "</a>";
     else
      $digitalImage = "";

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
      $textLatLon = "<td class=\"out\" style=\"text-align: center\" title=\"".round($lat,2)."&deg; / ".round($lon,2)."&deg;\">".
                     "<a href=\"http://www.mapquest.com/maps/map.adp?latlongtype=decimal&longitude=$lon&latitude=$lat&zoom=3\" ".
                      "target=\"_blank\"><img border=\"0\" height=\"15\" src=\"webimages/mapquest.png\" width=\"15\">".
                     "</a>".
                    "</td>";
    else
      $textLatLon = "<td class=\"out\"></td>";

    echo "<tr class=\"".(($nrSel==$nr)?"outMark":"out")."\">".
         "<td class=\"out\">$digitalImage</td>".
         "<td class=\"out\">".
          "<a href=\"editSpecimens.php?sel=".htmlentities("<".$row['specimen_ID'].">")."&nr=$nr&ptid=$id\" target=\"Specimens\">".
          htmlspecialchars(taxonItem($row))."</a></td>".
         "<td class=\"out\">".htmlspecialchars(collectorItem($row))."</td>".
         "<td class=\"outNobreak\">".htmlspecialchars($row['Datum'])."</td>".
         $textLatLon.
         "<td class=\"out\">".locationItem($row)."</td>".
         "<td class=\"out\">".htmlspecialchars($row['typus_lat'])."</td>".
         "<td class=\"outCenter\" title=\"".htmlspecialchars($row['collection'])."\">".
          htmlspecialchars($row['coll_short'])." ".htmlspecialchars($row['HerbNummer'])."</td></tr>\n";
    $nr++;
  }
  $linkList[0] = $nr - 1;
  $_SESSION['ltsLinkList'] = $linkList;
  echo "</table>\n";
}
else
  echo "<b>nothing found!</b>\n";
?>
</body>
</html>