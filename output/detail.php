<?php
session_start();
require("inc/functions.php");
?>
<html>
<head>
<title>Virtual Herbaria / dataset - detailed view</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="description" content="FW4 DW4 HTML">
<!-- Fireworks 4.0  Dreamweaver 4.0 target.  Created Fri Nov 08 15:05:42 GMT+0100 (Westeurop�ische Normalzeit) 2002-->
<link rel="stylesheet" href="../herbarium.css" type="text/css">
  <script type="text/javascript" language="JavaScript"><!--
    function showPicture(url) {
      MeinFenster =
      window.open(url,
                  "Picture",
                  "width=700,height=500,top=100,left=100,resizable,scrollbars");
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
function protolog($row) {

  $text = "";
  if ($row['suptitel']) $text .= "in ".$row['autor'].": ".$row['suptitel']." ";
  if ($row['periodicalID']) $text .= $row['periodical'];
  $text .= " ".$row['vol'];
  if ($row['part']) $text .= " (".$row['part'].")";
  $text .= ": ".$row['paginae'];
  if ($row['figures']) $text .= "; ".$row['figures'];
  $text .= " (".$row['jahr'].")";

  return $text;
}

// Returns 'false' if we can't open a connection to the location
// OR if the result is empty.

function get_directory_match($url,$base) {
  $url = parse_url($url);
  $fp = fsockopen($url['host'],80,$errno,$errstr,30);

  if(!$fp)
    return false; // Can't open a connection
  else {
    set_socket_blocking($fp, true);
    fputs($fp, "GET ".$url['path']." HTTP/1.0\r\nHost: ".$url['host']."\r\n\r\n");
    for ($result = ""; !feof($fp); $result .= fgets($fp,128));
    fclose($fp);
  }

  if (preg_match_all("/>$base(\\w+).jpg<\\/a>/i", $result, $match))
    return $match[1];
  else
    return false;
}

function makeCell($text) {

  if ($text)
    echo nl2br($text);
  else
    echo "&nbsp;";
}

function makeTypus($ID) {

  $sql = "SELECT typus_lat, tg.genus,
           ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
           ta4.author author4, ta5.author author5,
           te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
           te4.epithet epithet4, te5.epithet epithet5,
           ts.synID, ts.taxonID, ts.statusID
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
           AND specimenID='".intval($ID)."'";
  $result = mysql_query($sql);

  $text = "<table cellspacing=\"0\" cellpadding=\"0\">\n";
  while ($row=mysql_fetch_array($result)) {
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
               WHERE taxonID=".$row['synID'];
      $result3 = mysql_query($sql3);
      $row3 = mysql_fetch_array($result3);
      $accName = taxonWithHybrids($row3);
    }
    else
      $accName = "";

    $sql2 = "SELECT l.suptitel, la.autor, l.periodicalID, lp.periodical, l.vol, l.part,
              ti.paginae, ti.figures, l.jahr
             FROM tbl_tax_index ti
              INNER JOIN tbl_lit l ON l.citationID=ti.citationID
              LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID=l.periodicalID
              LEFT JOIN tbl_lit_authors la ON la.autorID=l.editorsID
             WHERE ti.taxonID='".$row['taxonID']."'";
    $result2 = mysql_query($sql2);

    $text .= "<tr><td nowrap align=\"right\"><b>".$row['typus_lat']." for&nbsp;</b></td><td><b>".taxonWithHybrids($row)."</b></td></tr>\n";
    while ($row2=mysql_fetch_array($result2))
      $text .= "<tr><td></td><td>".protolog($row2)."</td></tr>\n";
    if (strlen($accName)>0)
      $text .= "<tr><td></td><td>Current Name: <i>$accName</i></td></tr>\n";
  }
  $text .= "</table>\n";

  return $text;
}
/*
F�r die Webabfrage brauchen wir nur(!!) die folgenden Tabellen:
- tbl_collector
- tbl_collector_2
- tbl_management_collections
- tbl_nation
tbl_province
tbl_tax_authors
tbl_tax_epithets
- tbl_tax_families
- tbl_tax_genera
- tbl_tax_species
tbl_tax_status
- tbl_tax_systematic_categories
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
      <td colspan="8"><img name="databasemenu_r1_c1" src="images/databasemenu_r1_c1.gif" width="800" height="93" border="0" alt="virtual herbaria austria"></td>
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
if (isset($_GET['ID']))
  $ID = intval($_GET['ID']);
else
  $ID = 0;

$query = "SELECT s.specimen_ID, tg.genus, c.Sammler, c2.Sammler_2, ss.series, s.series_number,
           s.Nummer, s.alt_number, s.Datum, s.Fundort, s.det, s.taxon_alt, s.Bemerkungen,
           n.nation_engl, p.provinz, s.Fundort, tf.family, tsc.cat_description,
           mc.collection, mc.collectionID, mc.coll_short, tid.imgserver_IP, s.typified,
           s.digital_image, s.digital_image_obs, s.herbNummer, s.ncbi_accession,
           s.Coord_W, s.W_Min, s.W_Sec, s.Coord_N, s.N_Min, s.N_Sec,
           s.Coord_S, s.S_Min, s.S_Sec, s.Coord_E, s.E_Min, s.E_Sec,
           ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
           ta4.author author4, ta5.author author5,
           te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
           te4.epithet epithet4, te5.epithet epithet5,
           ts.synID, ts.taxonID, ts.statusID
          FROM tbl_specimens s
           LEFT JOIN tbl_specimens_series ss ON ss.seriesID=s.seriesID
           LEFT JOIN tbl_management_collections mc ON mc.collectionID=s.collectionID
           LEFT JOIN tbl_img_definition tid ON tid.source_id_fk=mc.source_id
           LEFT JOIN tbl_geo_nation n ON n.NationID=s.NationID
           LEFT JOIN tbl_geo_province p ON p.provinceID=s.provinceID
           LEFT JOIN tbl_collector c ON c.SammlerID=s.SammlerID
           LEFT JOIN tbl_collector_2 c2 ON c2.Sammler_2ID=s.Sammler_2ID
           LEFT JOIN tbl_tax_species ts ON ts.taxonID=s.taxonID
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
           LEFT JOIN tbl_tax_families tf ON tf.familyID=tg.familyID
           LEFT JOIN tbl_tax_systematic_categories tsc ON tf.categoryID=tsc.categoryID
          WHERE specimen_ID='".intval($ID)."'";
$result = mysql_query($query);
if (!$result) {
  echo $query."<br>\n";
  echo mysql_error()."<br>\n";
}
$row=mysql_fetch_array($result);

$taxon = taxonWithHybrids($row);

/*if ($row['synID']) {
  $query2 = "SELECT tg.genus, ".
             "ta.author, ta1.author author1, ta2.author author2, ta3.author author3, ".
             "ta4.author author4, ta5.author author5, ".
             "te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3, ".
             "te4.epithet epithet4, te5.epithet epithet5 ".
            "FROM tbl_tax_species ts ".
             "LEFT JOIN tbl_tax_authors ta ON ta.authorID=ts.authorID ".
             "LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID=ts.subspecies_authorID ".
             "LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID=ts.variety_authorID ".
             "LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID=ts.subvariety_authorID ".
             "LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID=ts.forma_authorID ".
             "LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID=ts.subforma_authorID ".
             "LEFT JOIN tbl_tax_epithets te ON te.epithetID=ts.speciesID ".
             "LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID=ts.subspeciesID ".
             "LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID=ts.varietyID ".
             "LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID=ts.subvarietyID ".
             "LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID=ts.formaID ".
             "LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID=ts.subformaID ".
             "LEFT JOIN tbl_tax_genera tg ON tg.genID=ts.genID ".
            "WHERE taxonID=".$row['synID'];
  $result2 = mysql_query($query2);
  $row2=mysql_fetch_array($result2);
  $accName = taxon($row2);
}
else
  $accName = "";
*/
//$query2 = "SELECT l.suptitel, la.autor, l.periodicalID, lp.periodical, l.vol, l.part, ".
//           "ti.paginae, ti.figures, l.jahr ".
//          "FROM tbl_tax_index ti ".
//          "INNER JOIN tbl_lit l ON l.citationID=ti.citationID ".
//          "LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID=l.periodicalID ".
//          "LEFT JOIN tbl_lit_authors la ON la.autorID=l.editorsID ".
//          "WHERE ti.taxonID=".$row['taxonID'];
//$result2 = mysql_query($query2);
//$row2=mysql_fetch_array($result2);
//$protolog = protolog($row2);

$sammler = collection($row['Sammler'],$row['Sammler_2'],$row['series'],$row['series_number'],
                      $row['Nummer'],$row['alt_number'],$row['Datum']);
if ($row['ncbi_accession'])
  $sammler .=  " &mdash; ".$row['ncbi_accession'].
               " <a href=\"http://www.ncbi.nlm.nih.gov/entrez/query.fcgi?db=Nucleotide&cmd=search&term=".
               $row['ncbi_accession']."\" target=\"_blank\">".
               "<img border=\"0\" height=\"16\" src=\"images/ncbi.gif\" width=\"14\"></a>";

?>
        <div align="center">
            <table width="80%" border="0">
                <tr>
                    <td align="right">
                        <a href="http://www.tropicos.org/NameSearch.aspx?name=<?php echo urlencode($row['genus'] . " "  . $row['epithet']); ?>&exact=true" title="Search in tropicos" target="_blank"><img alt="tropicos" src="images/tropicos.png" border="0" width="16" height="16"></a>
                    </td>
                </tr>
            </table>
          <table border="2" cellpadding="2" width="80%">
            <tr>
              <td align="right">Collection</td>
              <td><b>
                <?php makeCell($row['collection']." ".$row['herbNummer']); ?>
                </b></td>
            </tr>
<?php
$typusText = makeTypus($ID);
if (strlen($typusText)>0):
?>
            <tr>
              <td align="right">Type information</td>
              <td>
                <?php makeCell($typusText); ?>
                </td>
            </tr>
            <tr>
              <td align="right">Typified by</td>
              <td><b>
                <?php makeCell($row['typified']); ?>
                </b></td>
            </tr>
<?php endif; ?>
            <tr>
              <td align="right">Taxon</td>
              <td><b>
                <?php makeCell($taxon); ?>
                </b>
              </td>
            </tr>
            <?php if ($accName): ?>
            <tr>
              <td align="right">Accepted Name</td>
              <td><b>
                <?php makeCell($accName); ?>
                </b></td>
            </tr>
            <?php endif; ?>
            <tr>
              <td align="right">Family</td>
              <td><b>
                <?php makeCell($row['family']); ?>
                </b></td>
            </tr>
            <tr>
              <td align="right">Collector</td>
              <td><b>
                <?php makeCell($sammler);?>
                </b></td>
            </tr>
            <tr>
              <td align="right">Date</td>
              <td align="left"><b>
                <?php makeCell($row['Datum']); ?>
                </b></td>
            </tr>
            <tr>
              <td align="right">Location</td>
              <td><b>
                <?php
                  $text = $row['nation_engl'];
                  if (strlen(trim($row['provinz']))>0)
                    $text .= " / ".trim($row['provinz']);
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
                    $text .= " &mdash; ".round($lat,2)."&deg; / ".round($lon,2)."&deg; ".
                             "<a href=\"http://www.mapquest.com/maps/map.adp?".
                              "latlongtype=decimal&longitude=$lon&latitude=$lat&zoom=3\" target=\"_blank\">".
                             "<img border=\"0\" height=\"15\" src=\"images/mapquest.png\" width=\"15\"></a>&nbsp;".
                             "<a href=\"http://onearth.jpl.nasa.gov/landsat.cgi?".
                              "zoom=0.0005556&x0=$lon&y0=$lat&action=zoomin&layer=modis%252Cglobal_mosaic&".
                              "pwidth=800&pheight=600\" target=\"_blank\">".
                             "<img border=\"0\" height=\"15\" src=\"images/nasa.png\" width=\"15\"></a>";

                  if (strlen($text)>0)
                    echo $text;
                  else
                    echo "&nbsp;";
                ?>
                </b></td>
            </tr>
            <tr>
              <td align="right">Label</td>
              <td><b>
                <?php makeCell($row['Fundort']); ?>
                </b></td>
            </tr>
            <tr>
              <td align="right">det./rev./conf./assigned</td>
              <td><b>
                <?php makeCell($row['det']); ?>
                </b></td>
            </tr>
            <tr>
              <td align="right">ident. history</td>
              <td><b>
                <?php makeCell($row['taxon_alt']); ?>
                </b></td>
            </tr>
            <tr>
              <td align="right">annotations</td>
              <td><b>
                <?php makeCell($row['Bemerkungen']); ?>
                </b></td>
            </tr>
          </table>
        </div>
        <div align="center">
          <table width="80%" border="0">
            <tr>
              <td align="left">
              <table border='0'>
              <tr>
                <?php
if ($row['digital_image'] || $row['digital_image_obs']) {
/*  $url_base = "http://www.univie.ac.at/herbarium/images/";
  $url_pict = sprintf("%s_%07d",$row['coll_short'],$row['herbNummer']);
  $url_orig = $url_base."orig/".$url_pict;
  $url_tn   = $url_base."tn/".$url_pict;
  echo "<p>\n";
  echo "<a href=\"javascript:showPicture('$url_orig.jpg')\">".
       "<img src=\"$url_tn.jpg\" border=\"2\"></a>";

  $row =  get_directory_match($url_base."orig/",$url_pict);
  if ($row)
    for ($i=0;$i<count($row);$i++)
      echo "<a href=\"javascript:showPicture('".$url_orig.$row[$i].".jpg')\">".
           "<img src=\"".$url_tn.$row[$i].".jpg\" border=\"2\"></a>"; */
  echo "<p>\n";

/*  $sql = "SELECT HerbNummer, specimen_ID, coll_short_prj, img_directory, tbl_specimens.collectionID ".
         "FROM tbl_specimens, tbl_management_collections, tbl_img_definition ".
         "WHERE tbl_specimens.collectionID=tbl_management_collections.collectionID ".
          "AND tbl_management_collections.source_id=tbl_img_definition.source_id_fk ".
          "AND specimen_ID='".$row['specimen_ID']."'";
  $result = mysql_query($sql);
  $row = mysql_fetch_array($result);
  $path = $row['img_directory']."/";
  $pic = $row['coll_short_prj']."_";
  if ($row['HerbNummer']) {
    if (strpos($row['HerbNummer'],"-")===false) {
      if ($row['collectionID']==89)
        $pic .= sprintf("%08d",$row['HerbNummer']);
      else
        $pic .= sprintf("%07d",$row['HerbNummer']);
    } else
      $pic .= str_replace("-","",$row['HerbNummer']);
  } else
    $pic .= $row['specimen_ID']; */

  //$pics = glob($path.$pic."*");
  //foreach(glob($path.$pic."*") as $v)

  //$v = $path.$pic;

  //$pics = array();
  //$handle = fopen("http://".$row['imgserver_IP']."/database/detail_server.php?key=DKsuuewwqsa32czucuwqdb576i12&ID=".$row['specimen_ID'],"r");
  $transfer = unserialize(@file_get_contents("http://".$row['imgserver_IP']."/database/detail_server.php?key=DKsuuewwqsa32czucuwqdb576i12&ID=".$row['specimen_ID'],"r"));
  //if ($handle) {
  //  while (!feof($handle)) {
  //    $buffer = trim(fgets($handle, 4096));
  //    if ($buffer) $pics[] = $buffer;
  //  }
  //  fclose($handle);
  //}
  if ($transfer) {
    if (count($transfer['pics'])>0) {
      foreach ($transfer['pics'] as $v) {
        echo "<td valign='top' align='center'><a href=\"http://".$row['imgserver_IP']."/database/img/imgBrowser.php?name=".basename($v)."&ID=".$row['specimen_ID']."\" target=\"imgBrowser\">".
             "<img src=\"http://".$row['imgserver_IP']."/database/img/mktn.php?name=".basename($v)."\" border=\"2\"></a>\n" . 
             "<br>( <a href='http://".$row['imgserver_IP']."/database/img/downPic.php?name=".basename($v)."'>JPEG2000</a>, <a href='http://".$row['imgserver_IP']."/database/img/downPic.php?name=".basename($v)."&type=1'>TIFF</A> )</td>\n";
      }
    }
    else
      echo "no pictures found\n";
    if (trim($transfer['output']))
      echo nl2br("\n" . $transfer['output'] . "\n");
  }
  else
    echo "transmission error\n";
}
?>
              </tr>
              </table>
              </td>
            </tr>
          </table>
        </div>
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
