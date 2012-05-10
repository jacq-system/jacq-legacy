<?php
session_start();
require("inc/connect.php");
require("inc/herbardb_input_functions.php");
no_magic();

$id = intval($_GET['ID']);
if (isset($_GET['order'])) {
    if ($_GET['order'] == "a") {
        if ($_SESSION['taxaOrTyp']==2) {
            $_SESSION['taxaOrder'] = "tg_a.genus DESC";
            $_SESSION['taxaOrTyp'] = 12;
        } else {
            $_SESSION['taxaOrder'] = "tg_a.genus";
            $_SESSION['taxaOrTyp'] = 2;
        }
    }
    else {
        if ($_SESSION['taxaOrTyp'] == 1) {
            $_SESSION['taxaOrder'] = "tg.genus DESC";
            $_SESSION['taxaOrTyp'] = 11;
        } else {
            $_SESSION['taxaOrder'] = "tg.genus";
            $_SESSION['taxaOrTyp'] = 1;
        }
    }
} else if (!isset($_GET['r'])){
    $_SESSION['taxaOrder'] = "tg.genus";
    $_SESSION['taxaOrTyp'] = 1;
}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - list Taxa</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <style type="text/css">
    table.out { width: 100% }
    tr.out { }
    th.out { font-style: italic }
    td.out { background-color: #669999; }
  </style>
  <script type="text/javascript" language="JavaScript">
    function editLitTaxa(id,n) {
      target = "editLitTaxa.php?ID=" + id;
      if (n) target += "&new=1";
      MeinFenster = window.open(target,"editLitTaxa","width=800,height=550,top=70,left=70,scrollbars=yes,resizable=yes");
      MeinFenster.focus();
    }
  </script>
</head>

<body>

<?php
$sql ="SELECT citationID, suptitel, le.autor as editor, la.autor, l.periodicalID,
        lp.periodical, vol, part, jahr, pp
       FROM tbl_lit l
       LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID = l.periodicalID
       LEFT JOIN tbl_lit_authors le ON le.autorID = l.editorsID
       LEFT JOIN tbl_lit_authors la ON la.autorID = l.autorID
       WHERE citationID = '$id'";
$result = db_query($sql);
$row = mysql_fetch_array($result);
echo "<b>protolog:</b> " . protolog($row) . "\n<p>\n";
$sql = "SELECT lit_tax_ID, annotations,
         ts.taxonID, tg.genus, ta.author, ta1.author author1, ta2.author author2,
         ta3.author author3, ta4.author author4, ta5.author author5,
         te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
         te4.epithet epithet4, te5.epithet epithet5,
         ts_a.taxonID taxonID_a, tg_a.genus genus_a, ta_a.author author_a,
         ta1_a.author author1_a, ta2_a.author author2_a, ta3_a.author author3_a,
         ta4_a.author author4_a, ta5_a.author author5_a,
         te_a.epithet epithet_a, te1_a.epithet epithet1_a, te2_a.epithet epithet2_a,
         te3_a.epithet epithet3_a, te4_a.epithet epithet4_a, te5_a.epithet epithet5_a
        FROM tbl_lit_taxa tlt
         LEFT JOIN tbl_tax_species ts ON ts.taxonID = tlt.taxonID
         LEFT JOIN tbl_tax_authors ta ON ta.authorID = ts.authorID
         LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID = ts.subspecies_authorID
         LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID = ts.variety_authorID
         LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID = ts.subvariety_authorID
         LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID = ts.forma_authorID
         LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID = ts.subforma_authorID
         LEFT JOIN tbl_tax_epithets te ON te.epithetID = ts.speciesID
         LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID = ts.subspeciesID
         LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID = ts.varietyID
         LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID = ts.subvarietyID
         LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID = ts.formaID
         LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID = ts.subformaID
         LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
         LEFT JOIN tbl_tax_species ts_a ON ts_a.taxonID = tlt.acc_taxon_ID
         LEFT JOIN tbl_tax_authors ta_a ON ta_a.authorID = ts_a.authorID
         LEFT JOIN tbl_tax_authors ta1_a ON ta1_a.authorID = ts_a.subspecies_authorID
         LEFT JOIN tbl_tax_authors ta2_a ON ta2_a.authorID = ts_a.variety_authorID
         LEFT JOIN tbl_tax_authors ta3_a ON ta3_a.authorID = ts_a.subvariety_authorID
         LEFT JOIN tbl_tax_authors ta4_a ON ta4_a.authorID = ts_a.forma_authorID
         LEFT JOIN tbl_tax_authors ta5_a ON ta5_a.authorID = ts_a.subforma_authorID
         LEFT JOIN tbl_tax_epithets te_a ON te_a.epithetID = ts_a.speciesID
         LEFT JOIN tbl_tax_epithets te1_a ON te1_a.epithetID = ts_a.subspeciesID
         LEFT JOIN tbl_tax_epithets te2_a ON te2_a.epithetID = ts_a.varietyID
         LEFT JOIN tbl_tax_epithets te3_a ON te3_a.epithetID = ts_a.subvarietyID
         LEFT JOIN tbl_tax_epithets te4_a ON te4_a.epithetID = ts_a.formaID
         LEFT JOIN tbl_tax_epithets te5_a ON te5_a.epithetID = ts_a.subformaID
         LEFT JOIN tbl_tax_genera tg_a ON tg_a.genID = ts_a.genID
        WHERE citationID = '$id' ORDER BY " . $_SESSION['taxaOrder'];
$result = db_query($sql);

echo "<p>\n";
echo "<form Action=\"" . $_SERVER['PHP_SELF'] . "\" Method=\"GET\" name=\"f\">\n";
if (($_SESSION['editControl'] & 0x20) != 0) {
    echo "<table><tr><td>\n";
    echo "<input class=\"cssfbutton\" type=\"button\" value=\" add new Line \" "
       . "onClick=\"editLitTaxa('<$id>',1)\">\n";
    echo "</td><td width=\"20\">&nbsp;</td><td>\n";
    echo "<input class=\"cssfbutton\" type=\"submit\" name=\"reload\" value=\"Reload\">\n";
    echo "</td><td width=\"20\">&nbsp;</td><td>\n";
    echo "<input class=\"cssfbutton\" type=\"button\" value=\" close \" onclick=\"self.close()\">\n";
    echo "</td></tr></table>\n";
}
echo "<input type=\"hidden\" name=\"r\" value=\"1\">\n";
echo "<input type=\"hidden\" name=\"ID\" value=\"$id\">\n";
echo "</form><p>\n";

echo "<table class=\"out\" cellspacing=\"2\" cellpadding=\"2\">\n";
echo "<tr class=\"out\">";
echo "<th></th>";
echo "<th class=\"out\">&nbsp;<a href=\"" . $_SERVER['PHP_SELF'] . "?ID=$id&order=t\">Taxon</a>";
if ($_SESSION['taxaOrTyp'] == 1) {
    echo "&nbsp;&nbsp;v";
} else if ($_SESSION['taxaOrTyp'] == 11) {
    echo "&nbsp;&nbsp;^";
}
echo "&nbsp;</th>";
echo "<th class=\"out\">&nbsp;<a href=\"" . $_SERVER['PHP_SELF'] . "?ID=$id&order=a\">acc. Taxon</a>";
if ($_SESSION['taxaOrTyp'] == 2) {
    echo "&nbsp;&nbsp;v";
} else if ($_SESSION['taxaOrTyp'] == 12) {
    echo "&nbsp;&nbsp;^";
}
echo "&nbsp;</th>";
echo "<th class=\"out\">&nbsp;annotations&nbsp;</th>";
echo "</tr>\n";
if (mysql_num_rows($result) > 0) {
    while ($row = mysql_fetch_array($result)) {
        echo "<tr class=\"out\">";
        echo "<td class=\"out\">"
           . "<a href=\"javascript:editLitTaxa('<" . $row['lit_tax_ID'] . ">',0)\">edit</a>"
           . "</td>";
        echo "<td class=\"out\">" . getScientificName($row['taxonID']) . "</td>";
        echo "<td class=\"out\">" . taxonAccepted($row) . "</td>";
        echo "<td class=\"out\">" . $row['annotations'] . "</td>";
        echo "</tr>\n";
    }
} else {
    echo "<tr class=\"out\"><td class=\"out\" colspan=\"4\">no entries</td></tr>\n";
}
echo "</table>\n";

?>

</body>
</html>