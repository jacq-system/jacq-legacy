<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");
require("inc/herbardb_input_functions.php");
require("inc/log_functions.php");
no_magic();


function makeSammler($search, $x, $y, $nr)
{
    global $cf;

    $pieces = explode(" <", $search);
    $results[] = "";
    if ($search && strlen($search) > 1) {
        if ($nr == 2) {
            $sql = "SELECT Sammler_2, Sammler_2ID
                    FROM tbl_collector_2
                    WHERE Sammler_2 LIKE '" . mysql_escape_string($pieces[0]) . "%'
                    ORDER BY Sammler_2";
        } else {
            $sql = "SELECT Sammler, SammlerID
                    FROM tbl_collector
                    WHERE Sammler LIKE '" . mysql_escape_string($pieces[0]) . "%'
                    ORDER BY Sammler";
        }
        if ($result = db_query($sql)) {
            $cf->text($x, $y, "<b>" . mysql_num_rows($result) . " record" . ((mysql_num_rows($result) != 1) ? "s" : "") . " found</b>");
            if (mysql_num_rows($result) > 0) {
                while ($row = mysql_fetch_array($result)) {
                    if ($nr == 2) {
                        $res = $row['Sammler_2'] . " <" . $row['Sammler_2ID'] . ">";
                    } else {
                        $res = $row['Sammler'] . " <" . $row['SammlerID'] . ">";
                    }
                    $results[] = $res;
                }
            }
        }
    }
    return $results;
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - edit Type</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <style type="text/css">
    table.out { width: 100% }
    tr.out { }
    th.out { font-style: italic }
    td.out { background-color: #669999; }
  </style>
  <script type="text/javascript" language="JavaScript">
    function editCollector(sel) {
      target = "editCollector.php?sel=" + encodeURIComponent(sel.value);
      MeinFenster = window.open(target,"editCollector","width=350,height=130,top=50,left=50,scrollbars=yes,resizable=yes");
      MeinFenster.focus();
    }
    function editCollector2(sel) {
      target = "editCollector2.php?sel=" + encodeURIComponent(sel.value);
      MeinFenster = window.open(target,"editCollector2","width=500,height=130,top=50,left=50,scrollbars=yes,resizable=yes");
      MeinFenster.focus();
    }
    function searchCollector() {
      MeinFenster = window.open("searchCollector","searchCollector","scrollbars=yes,resizable=yes");
      MeinFenster.focus();
    }
  </script>
</head>

<body>

<?php
if (isset($_GET['new'])) {
    $sql = "SELECT taxonID, tg.genus,
             ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
             ta4.author author4, ta5.author author5,
             te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
             te4.epithet epithet4, te5.epithet epithet5
            FROM tbl_tax_species ts
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
            WHERE taxonID = " . extractID($_GET['ID']);
    $result = db_query($sql);
    $p_taxon = taxon(mysql_fetch_array($result));
    $p_series = $p_leg_nr = $p_alternate_number = $p_date = $p_duplicates = $p_annotation = "";
    $p_typecollID = $p_sammler = $p_sammler2 ="";
} elseif (extractID($_GET['ID']) !== "NULL") {
    $sql ="SELECT typecollID, taxonID, series, leg_nr, alternate_number, date, duplicates, annotation,
            tt.SammlerID, Sammler, tt.Sammler_2ID, Sammler_2
           FROM (tbl_tax_typecollections tt, tbl_collector c)
            LEFT JOIN tbl_collector_2 c2 ON c2.Sammler_2ID = tt.Sammler_2ID
           WHERE c.SammlerID = tt.SammlerID
            AND typecollID = " . extractID($_GET['ID']);
    $result = db_query($sql);
    if (mysql_num_rows($result) > 0) {
        $row = mysql_fetch_array($result);
        $p_typecollID       = $row['typecollID'];
        $p_series           = $row['series'];
        $p_leg_nr           = $row['leg_nr'];
        $p_alternate_number = $row['alternate_number'];
        $p_date             = $row['date'];
        $p_duplicates       = $row['duplicates'];
        $p_annotation       = $row['annotation'];

        $p_sammler   = $row['Sammler'] . " <" . $row['SammlerID'] . ">";
        $p_sammler2  = ($row['Sammler_2']) ? $row['Sammler_2'] . " <" . $row['Sammler_2ID'] . ">" : "";

        $sql = "SELECT taxonID, tg.genus,
                 ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
                 ta4.author author4, ta5.author author5,
                 te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
                 te4.epithet epithet4, te5.epithet epithet5
                FROM tbl_tax_species ts
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
                WHERE taxonID = '" . $row['taxonID'] . "'";
          $result = db_query($sql);
          $p_taxon = taxon(mysql_fetch_array($result));
    } else {
        $p_taxon = $p_series = $p_leg_nr = $p_alternate_number = $p_date = $p_duplicates = $p_annotation = "";
        $p_typecollID = $p_sammler = $p_sammler2 ="";
    }
} elseif ($_POST['submitUpdate'] && (($_SESSION['editControl'] & 0x400) != 0)) {
    $series = $_POST['series'];
    $leg_nr = $_POST['leg_nr'];
    $alternate_number = $_POST['alternate_number'];
    $date = $_POST['date'];
    $duplicates = $_POST['duplicates'];
    $annotation = $_POST['annotation'];
    if (intval($_POST['typecollID'])) {
        $sql = "UPDATE tbl_tax_typecollections SET
                 taxonID = " . extractID($_POST['taxon']) . ",
                 SammlerID = " . extractID($_POST['sammler']) . ",
                 Sammler_2ID = " . extractID($_POST['sammler2']) . ",
                 series = " . quoteString($series) . ",
                 leg_nr = " . quoteString($leg_nr) . ",
                 alternate_number = " . quoteString($alternate_number) . ",
                 date = " . quoteString($date) . ",
                 duplicates = " . quoteString($duplicates) . ",
                 annotation = " . quoteString($annotation) . "
                WHERE typecollID = " . intval($_POST['typecollID']);
        $updated = 1;
    } else {
        $sql = "INSERT INTO tbl_tax_typecollections SET
                 taxonID = " . extractID($_POST['taxon']) . ",
                 SammlerID = " . extractID($_POST['sammler']) . ",
                 Sammler_2ID = " . extractID($_POST['sammler2']) . ",
                 series = " . quoteString($series) . ",
                 leg_nr = " . quoteString($leg_nr) . ",
                 alternate_number = " . quoteString($alternate_number) . ",
                 date = " . quoteString($date) . ",
                 duplicates = " . quoteString($duplicates) . ",
                 annotation = " . quoteString($annotation);
        $updated = 0;
    }
    $result = db_query($sql);
    $id = ($_POST['typecollID']) ? intval($_POST['typecollID']) : mysql_insert_id();
    logTypecollections($id,$updated);
    if ($result) {
        echo "<script language=\"JavaScript\">\n";
        echo "  window.opener.document.f.reload.click()\n";
        echo "  self.close()\n";
        echo "</script>\n";
    }
} else {
    $p_taxon            = $_POST['taxon'];
    $p_series           = $_POST['series'];
    $p_leg_nr           = $_POST['leg_nr'];
    $p_alternate_number = $_POST['alternate_number'];
    $p_date             = $_POST['date'];
    $p_duplicates       = $_POST['duplicates'];
    $p_annotation       = $_POST['annotation'];
    $p_sammler          = $_POST['sammler'];
    $p_sammler2         = $_POST['sammler2'];
    $p_typecollID       = $_POST['typecollID'];
}
?>

<form Action="<?php echo $_SERVER['PHP_SELF']; ?>" Method="POST" name="f">

<?php

$cf = new CSSF();

echo "<input type=\"hidden\" name=\"typecollID\" value=\"$p_typecollID\">\n";
$cf->label(9, 0.5, "ID");
$cf->text(9, 0.5, "&nbsp;" . (($p_typecollID) ? $p_typecollID : "new"));
echo "<input type=\"hidden\" name=\"taxon\" value=\"$p_taxon\">\n";
$cf->label(9, 2, "taxon");
$cf->text(9, 2, "&nbsp;" . $p_taxon);
$cf->label(9, 7.5, "first collector", "javascript:editCollector(document.f.sammler)");
$cf->editDropdown(9, 7.5, 28, "sammler", $p_sammler, makeSammler($p_sammler, 9, 6, 1), 270);
$cf->label(9, 9.2, "search", "javascript:searchCollector()");
$cf->label(9, 13, "add. collector(s)", "javascript:editCollector2(document.f.sammler2)");
$cf->editDropdown(9, 13, 28, "sammler2", $p_sammler2, makeSammler($p_sammler2, 9, 11.5, 2), 270);
$cf->label(9, 17, "series");
$cf->inputText(9, 17, 28, "series", $p_series, 250);
$cf->label(9, 19, "number");
$cf->inputText(9, 19, 10, "leg_nr", $p_leg_nr, 50);
$cf->label(9, 21, "alt. number");
$cf->inputText(9, 21, 28, "alternate_number", $p_alternate_number, 250);
$cf->label(9, 23, "date");
$cf->inputText(9, 23, 28, "date", $p_date, 50);
$cf->label(9, 25, "duplicates");
$cf->inputText(9, 25, 28, "duplicates", $p_duplicates, 250);
$cf->label(9, 27, "annotations");
$cf->textarea(9, 27, 28, 4, "annotation", $p_annotation);

if (($_SESSION['editControl'] & 0x400) != 0) {
    $text = ($p_typecollID) ? " Update " : " Insert ";
    $cf->buttonSubmit(2, 34, "reload", " Reload ");
    $cf->buttonReset(10, 34, " Reset ");
    $cf->buttonSubmit(20, 34, "submitUpdate", $text);
}
$cf->buttonJavaScript(28, 34, " Cancel ", "self.close()");
?>

</form>
</body>
</html>