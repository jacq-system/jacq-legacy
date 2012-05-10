<?php
session_start();
require("inc/connect.php");
require("inc/herbardb_input_functions.php");
no_magic();

function displayButtons($type, $id)
{
    echo "<form Action=\"" . $_SERVER['PHP_SELF'] . "\" Method=\"GET\" name=\"f\">\n";
    if (($_SESSION['editControl'] & 0x200) != 0) {
        echo "<table><tr><td>\n";
        echo "<input class=\"cssfbutton\" type=\"button\" value=\" add new Line \" ".
             "onClick=\"editIndex($type,'<$id>',1)\">\n";
        echo "</td><td width=\"20\">&nbsp;</td><td>\n";
        echo "<input class=\"cssfbutton\" type=\"submit\" name=\"reload\" value=\"Reload\">\n";
        echo "</td><td width=\"20\">&nbsp;</td><td>\n";
        echo "<input class=\"cssfbutton\" type=\"button\" value=\" close \" onclick=\"self.close()\">\n";
        echo "</td></tr></table>\n";
    }
    echo "<input type=\"hidden\" name=\"" . (($type == 1) ? "t" : "c") . "\" value=\"1\">\n";
    echo "<input type=\"hidden\" name=\"r\" value=\"1\">\n";
    echo "<input type=\"hidden\" name=\"ID\" value=\"$id\">\n";
    echo "</form>\n";
} // end displayButtons()


function taxonWithFamily($row)
{
    $text = strtoupper($row['family'] . " " . $row['category']) . " "
          . $row['DallaTorreIDs'] . $row['DallaTorreZusatzIDs'] . " " . $row['genus'] . " " . $row['author_g'];
    if ($row['epithet']) {
        $text .= " " . $row['epithet'] . chr(194) . chr(183) . " " . $row['author'];
    } else {
        $text .= chr(194) . chr(183);
    }
    if ($row['epithet1']) $text .= " subsp. "   . $row['epithet1'] . " " . $row['author1'];
    if ($row['epithet2']) $text .= " var. "     . $row['epithet2'] . " " . $row['author2'];
    if ($row['epithet3']) $text .= " subvar. "  . $row['epithet3'] . " " . $row['author3'];
    if ($row['epithet4']) $text .= " forma "    . $row['epithet4'] . " " . $row['author4'];
    if ($row['epithet5']) $text .= " subforma " . $row['epithet5'] . " " . $row['author5'];

    $text .= " <" . $row['taxonID'] . ">";

    return $text;
} // end taxonWithFamily

if (!empty($_GET['t'])) {
    $type = 1;  // taxonID ist die F�hrungs-ID
} else {
    $type = 2;  // citationID ist die F�hrungs-ID
}
$id = intval($_GET['ID']);
if ($type == 2) {
    if (isset($_GET['order'])) {
        if ($_GET['order'] == "p") {
            if ($_SESSION['indOrTyp'] == 2) {
                $_SESSION['indOrder'] = "pagsort DESC";
                $_SESSION['indOrTyp'] = 12;
            } else {
                $_SESSION['indOrder'] = "pagsort";
                $_SESSION['indOrTyp'] = 2;
            }
        } else {
            if ($_SESSION['indOrTyp'] == 1) {
                $_SESSION['indOrder'] = "family DESC, genus DESC, epithet DESC, author DESC, epithet1 DESC, author1 DESC, "
                                      . "epithet2 DESC, author2 DESC, epithet3 DESC, author3 DESC, "
                                      . "epithet4 DESC, author4 DESC, epithet5 DESC, author5 DESC";
                $_SESSION['indOrTyp'] = 11;
            } else {
                $_SESSION['indOrder'] = "family, genus, epithet, author, epithet1, author1, "
                                      . "epithet2, author2, epithet3, author3, epithet4, author4, epithet5, author5";
                $_SESSION['indOrTyp'] = 1;
            }
        }
    } else if (!isset($_GET['r'])){
        $_SESSION['indOrder'] = "tg.genus";
        $_SESSION['indOrTyp'] = 1;
    }
}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - list Index</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <style type="text/css">
    table.out { width: 100% }
    tr.out { }
    th.out { font-style: italic }
    td.out { background-color: #669999; }
  </style>
  <script type="text/javascript" language="JavaScript">
    function editIndex(t,id,n) {
      target = "editIndex.php?t=" + t + "&ID=" + id;
      if (n)
        target += "&new=1";
      MeinFenster = window.open(target,"editIndex","width=800,height=310,top=60,left=60,scrollbars=yes,resizable=yes");
      MeinFenster.focus();
    }
  </script>
</head>

<body>

<?php
if ($type == 1) {
    echo "<b>taxon:</b> " . getScientificName($id) . "\n<p>\n";

    displayButtons($type, $id);
    echo "<p>\n";

    /*
    $text = taxon($row);
    for ($i=0;$i<strlen($text);$i++)
      echo substr($text,$i,1)." - ".ord(substr($text,$i,1))."\n";
    */

    $sql ="SELECT l.citationID, taxindID, paginae, figures, annotations,
            l.suptitel, le.autor as editor, la.autor, l.periodicalID, lp.periodical,
            l.vol, l.part, l.jahr, l.pp
           FROM tbl_tax_index ti
            LEFT JOIN tbl_lit l ON l.citationID = ti.citationID
            LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID = l.periodicalID
            LEFT JOIN tbl_lit_authors le ON le.autorID = l.editorsID
            LEFT JOIN tbl_lit_authors la ON la.autorID = l.autorID
           WHERE taxonID = '$id' ORDER BY l.citationID";
    $result = db_query($sql);
    echo "<table class=\"out\" cellspacing=\"2\" cellpadding=\"2\">\n";
    echo "<tr class=\"out\">";
    echo "<th></th>";
    echo "<th class=\"out\">&nbsp;protolog&nbsp;</th>";
    echo "<th class=\"out\">&nbsp;paginae&nbsp;</th>";
    echo "<th class=\"out\">&nbsp;figures&nbsp;</th>";
    echo "<th class=\"out\">&nbsp;annotations&nbsp;</th>";
    echo "</tr>\n";
    if (mysql_num_rows($result)>0) {
        while ($row=mysql_fetch_array($result)) {
            echo "<tr class=\"out\">";
            echo "<td class=\"out\">"
               . "<a href=\"javascript:editIndex($type,'<" . $row['taxindID'] . ">',0)\">edit</a>"
               . "</td>";
            echo "<td class=\"out\"><a href=listIndex.php?c=1&ID=" . $row['citationID'] . ">"
               . protolog($row) . "</td>";
            echo "<td class=\"out\">" . $row['paginae'] . "</td>";
            echo "<td class=\"out\">" . $row['figures'] . "</td>";
            echo "<td class=\"out\">" . $row['annotations'] . "</td>";
            echo "</tr>\n";
        }
    } else {
        echo "<tr class=\"out\"><td class=\"out\" colspan=\"5\">no entries</td></tr>\n";
    }
    echo "</table>\n";
} else {
    $sql ="SELECT citationID, suptitel, le.autor as editor, la.autor, l.periodicalID, lp.periodical, vol, part, jahr, pp
           FROM tbl_lit l
            LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID = l.periodicalID
            LEFT JOIN tbl_lit_authors le ON le.autorID = l.editorsID
            LEFT JOIN tbl_lit_authors la ON la.autorID = l.autorID
           WHERE citationID = '$id'";
    $result = db_query($sql);
    $row = mysql_fetch_array($result);
    echo "<b>protolog:</b> " . protolog($row) . "\n<p>\n";

    displayButtons($type, $id);
    echo "<p>\n";

    $sql = "SELECT ts.taxonID, tg.genus, taxindID, paginae, (paginae + '0') AS pagsort,
             figures, annotations,
             tg.DallaTorreIDs, tg.DallaTorreZusatzIDs, tag.author author_g,
             tf.family, tsc.category,
             ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
             ta4.author author4, ta5.author author5,
             te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
             te4.epithet epithet4, te5.epithet epithet5
            FROM tbl_tax_index ti
             LEFT JOIN tbl_tax_species ts ON ts.taxonID = ti.taxonID
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
             LEFT JOIN tbl_tax_authors tag ON tag.authorID = tg.authorID
             LEFT JOIN tbl_tax_families tf ON tf.familyID = tg.familyID
             LEFT JOIN tbl_tax_systematic_categories tsc ON tf.categoryID = tsc.categoryID
            WHERE citationID = '$id'
            ORDER BY " . $_SESSION['indOrder'];
    $result = db_query($sql);
    echo "<table class=\"out\" cellspacing=\"2\" cellpadding=\"2\">\n";
    echo "<tr class=\"out\">";
    echo "<th></th>";
    echo "<th class=\"out\">&nbsp;<a href=\"" . $_SERVER['PHP_SELF'] . "?c=1&ID=$id&order=t\">taxon</a>";
    if ($_SESSION['indOrTyp'] == 1) {
        echo "&nbsp;&nbsp;v";
    } else if ($_SESSION['indOrTyp'] == 11) {
        echo "&nbsp;&nbsp;^";
    }
    echo "&nbsp;</th>";
    echo "<th class=\"out\">&nbsp;<a href=\"" . $_SERVER['PHP_SELF'] . "?c=1&ID=$id&order=p\">paginae</a>";
    if ($_SESSION['indOrTyp'] == 2) {
        echo "&nbsp;&nbsp;v";
    } else if ($_SESSION['indOrTyp'] == 12) {
        echo "&nbsp;&nbsp;^";
    }
    echo "&nbsp;</th>";
    echo "<th class=\"out\">&nbsp;figures&nbsp;</th>";
    echo "<th class=\"out\">&nbsp;annotations&nbsp;</th>";
    echo "</tr>\n";
    if (mysql_num_rows($result) > 0) {
        while ($row=mysql_fetch_array($result)) {
            echo "<tr class=\"out\">";
            echo "<td class=\"out\">"
               . "<a href=\"javascript:editIndex($type,'<" . $row['taxindID'] . ">',0)\">edit</a>"
               . "</td>";
            echo "<td class=\"out\"><a href=listIndex.php?t=1&ID=" . $row['taxonID'] . ">"
               . taxonWithFamily($row) . "</td>";
            echo "<td class=\"out\">" . $row['paginae'] . "</td>";
            echo "<td class=\"out\">" . $row['figures'] . "</td>";
            echo "<td class=\"out\">" . $row['annotations'] . "</td>";
            echo "</tr>\n";
        }
    } else {
        echo "<tr class=\"out\"><td class=\"out\" colspan=\"5\">no entries</td></tr>\n";
    }
    echo "</table>\n";
}
?>

</body>
</html>