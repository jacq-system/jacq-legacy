<?php
session_start();
require("inc/connect.php");
require("inc/herbardb_input_functions.php");
no_magic();

$id = intval($_GET['ID']);

function collector($row) {

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

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - list Specimens Types</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <style type="text/css">
    table.out { width: 100% }
    tr.out { }
    th.out { font-style: italic }
    td.out { background-color: #669999; }
  </style>
  <script type="text/javascript" language="JavaScript">
    function editSpecimensTypes(id,n) {
      target = "editSpecimensTypes.php?ID=" + id;
      if (n)
        target += "&new=1";
      MeinFenster = window.open(target,"editSpecimensTypes","width=800,height=400,top=60,left=60,scrollbars=yes,resizable=yes");
      MeinFenster.focus();
    }
    function editSpecies(sel) {
      options = "width=";
      if (screen.availWidth<990)
        options += (screen.availWidth - 10) + ",height=";
      else
        options += "990, height=";
      if (screen.availHeight<710)
        options += (screen.availHeight - 10);
      else
        options += "710";
      options += ", top=10,left=10,scrollbars=yes,resizable=yes";

      newWindow = window.open("editSpecies.php?sel=" + sel,"Species",options);
      newWindow.focus();
    }
  </script>
</head>

<body>

<?php
$sql = "SELECT c.Sammler, c2.Sammler_2, ss.series, wg.series_number, ".
        "wg.Nummer, wg.alt_number, wg.Datum, wg.HerbNummer ".
       "FROM tbl_specimens wg  ".
        "LEFT JOIN tbl_specimens_series ss ON ss.seriesID=wg.seriesID ".
        "LEFT JOIN tbl_collector c ON c.SammlerID=wg.SammlerID ".
        "LEFT JOIN tbl_collector_2 c2 ON c2.Sammler_2ID=wg.Sammler_2ID ".
       "WHERE specimen_ID='$id'";
$result = db_query($sql);
$row = mysql_fetch_array($result);
echo "<b>Specimen:</b> ".collector($row)."\n<p>\n";
$sql = "SELECT ts.taxonID, tg.genus, specimens_types_ID, annotations, tt.typus, ".
        "ta.author, ta1.author author1, ta2.author author2, ta3.author author3, ".
        "ta4.author author4, ta5.author author5, ".
        "te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3, ".
        "te4.epithet epithet4, te5.epithet epithet5 ".
       "FROM (tbl_specimens_types tst, tbl_typi tt) ".
        "LEFT JOIN tbl_tax_species ts ON ts.taxonID=tst.taxonID ".
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
       "WHERE tt.typusID=tst.typusID ".
        "AND specimenID='$id' ORDER BY tg.genus";
$result = db_query($sql);
echo "<table class=\"out\" cellspacing=\"2\" cellpadding=\"2\">\n";
echo "<tr class=\"out\">";
echo "<th></th>";
echo "<th class=\"out\">&nbsp;status&nbsp;</th>";
echo "<th class=\"out\">&nbsp;taxon&nbsp;</th>";
echo "<th class=\"out\">&nbsp;annotations&nbsp;</th>";
echo "</tr>\n";
if (mysql_num_rows($result)>0) {
  while ($row=mysql_fetch_array($result)) {
    echo "<tr class=\"out\">";
    echo "<td class=\"out\">".
         "<a href=\"javascript:editSpecimensTypes('<".$row['specimens_types_ID'].">',0)\">edit</a>".
         "</td>";
    echo "<td class=\"out\"><font color=\"red\"><b>".$row['typus']."</b></font></td>";
    echo "<td class=\"out\"><a href=\"javascript:editSpecies('<".$row['taxonID'].">')\">".taxon($row)."</a></td>";
    echo "<td class=\"out\">".$row['annotations']."</td>";
    echo "</tr>\n";
  }
} else
  echo "<tr class=\"out\"><td class=\"out\" colspan=\"4\">no entries</td></tr>\n";
echo "</table>\n";

echo "<p>\n";
echo "<form Action=\"".$_SERVER['PHP_SELF']."\" Method=\"GET\" name=\"f\">\n";
if (($_SESSION['editControl'] & 0x8000)!=0) {
  echo "<table><tr><td>\n";
  echo "<input class=\"cssfbutton\" type=\"button\" value=\" add new Line \" ".
       "onClick=\"editSpecimensTypes('<$id>',1)\">\n";
  echo "</td><td width=\"20\">&nbsp;</td><td>\n";
  echo "<input class=\"cssfbutton\" type=\"submit\" name=\"reload\" value=\"Reload\">\n";
  echo "</td><td width=\"20\">&nbsp;</td><td>\n";
  echo "<input class=\"cssfbutton\" type=\"button\" value=\" close \" onclick=\"self.close()\">\n";
  echo "</td></tr></table>\n";
}
echo "<input type=\"hidden\" name=\"ID\" value=\"$id\">\n";
echo "</form>\n";
?>

</body>
</html>