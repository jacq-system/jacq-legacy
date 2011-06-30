<?php
session_start();
require("inc/connect.php");
require("inc/herbardb_input_functions.php");
no_magic();


$id = intval($_GET['ID']);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - list Type</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <style type="text/css">
    table.out { width: 100% }
    tr.out { }
    th.out { font-style: italic }
    td.out { background-color: #669999; }
  </style>
  <script type="text/javascript" language="JavaScript">
    function editType(id,n) {
      target = "editType.php?ID=" + id;
      if (n)
        target += "&new=1";
      MeinFenster = window.open(target,"editType","width=600,height=500,top=60,left=60,scrollbars=yes,resizable=yes");
      MeinFenster.focus();
    }
  </script>
</head>

<body>

<?php/*
$sql = "SELECT taxonID, tg.genus, ".
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
       "WHERE taxonID='$id'";
$result = db_query($sql);
$row = mysql_fetch_array($result);
echo "<b>taxon:</b> ".taxon($row)."\n<p>\n";
$sql ="SELECT typecollID, series, leg_nr, alternate_number, date, duplicates, annotation, ".
       "Sammler ".
      "FROM tbl_tax_typecollections tt ".
       "LEFT JOIN tbl_collector c ON c.SammlerID=tt.SammlerID ".
      "WHERE taxonID='$id' ORDER BY typecollID";
$result = db_query($sql);
echo "<table class=\"out\" cellspacing=\"2\" cellpadding=\"2\">\n";
echo "<tr class=\"out\">";
echo "<th></th>";
echo "<th class=\"out\">&nbsp;collector&nbsp;</th>";
echo "<th class=\"out\">&nbsp;series&nbsp;</th>";
echo "<th class=\"out\">&nbsp;number&nbsp;</th>";
echo "<th class=\"out\">&nbsp;alt.&nbsp;number&nbsp;</th>";
echo "<th class=\"out\">&nbsp;date&nbsp;</th>";
echo "<th class=\"out\">&nbsp;duplicates&nbsp;</th>";
echo "</tr>\n";
if (mysql_num_rows($result)>0) {
  while ($row=mysql_fetch_array($result)) {
    echo "<tr class=\"out\">";
    echo "<td class=\"out\">".
         "<a href=\"javascript:editType('<".$row['typecollID'].">',0)\">edit</a>".
         "</td>";
    echo "<td class=\"out\">".htmlspecialchars($row['Sammler'])."</td>";
    echo "<td class=\"out\">".htmlspecialchars($row['series'])."</td>";
    echo "<td class=\"out\">".htmlspecialchars($row['leg_nr'])."</td>";
    echo "<td class=\"out\">".htmlspecialchars($row['alternate_number'])."</td>";
    echo "<td class=\"out\">".htmlspecialchars($row['date'])."</td>";
    echo "<td class=\"out\">".htmlspecialchars(substr($row['duplicates'],0,10));
    if (strlen($row['duplicates'])>10)
      echo "...";
    echo "</td>";
    echo "</tr>\n";
  }
} else
  echo "<tr class=\"out\"><td class=\"out\" colspan=\"8\">no entries</td></tr>\n";
echo "</table>\n";

echo "<p>\n";
echo "<form Action=\"".$_SERVER['PHP_SELF']."\" Method=\"GET\" name=\"f\">\n";
if (($_SESSION['editControl'] & 0x400)!=0) {
  echo "<table><tr><td>\n";
  echo "<input class=\"cssfbutton\" type=\"button\" value=\" add new Line \" ".
       "onClick=\"editType('<$id>',1)\">\n";
  echo "</td><td width=\"20\">&nbsp;</td><td>\n";
  echo "<input class=\"cssfbutton\" type=\"submit\" name=\"reload\" value=\"Reload\">\n";
  echo "</td><td width=\"20\">&nbsp;</td><td>\n";
  echo "<input class=\"cssfbutton\" type=\"button\" value=\" close \" onclick=\"self.close()\">\n";
  echo "</td></tr></table>\n";
}
echo "<input type=\"hidden\" name=\"ID\" value=\"$id\">\n";
echo "</form>\n";
*/
?>

</body>
</html>