<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");
require("inc/herbardb_input_functions.php");
require("inc/log_functions.php");
no_magic();


function makeTaxon($search,$x,$y)
{
    global $cf;

    $results[] = "";
    if ($search && strlen($search) > 1) {
        $pieces = explode(chr(194) . chr(183), $search);
        $pieces = explode(" ", $pieces[0]);
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
                WHERE ts.external = 0
                 AND tg.genus LIKE '" . mysql_escape_string($pieces[0]) . "%' ";
        if ($pieces[1]) {
            $sql .= "AND te.epithet LIKE '" . mysql_escape_string($pieces[1]) . "%' ";
        }
        $sql .= "ORDER BY tg.genus, te.epithet, epithet1, epithet2, epithet3, epithet4, epithet5";
        if ($result = db_query($sql)) {
            $cf->text($x, $y, "<b>" . mysql_num_rows($result) . " records found</b>");
            if (mysql_num_rows($result) > 0) {
                while ($row = mysql_fetch_array($result)) {
                    $results[] = taxon($row);
                }
            }
        }
    }
    return $results;
}

function makeCollector($row)
{
    $text = $row['Sammler'];
    if (strstr($row['Sammler_2'], "&") || strstr($row['Sammler_2'], "et al.")) {
        $text .= " et al.";
    } elseif ($row['Sammler_2']) {
        $text .= " & " . $row['Sammler_2'];
    }
    if ($row['series_number']) {
        if ($row['Nummer']) $text .= " " . $row['Nummer'];
        if ($row['alt_number'] && trim($row['alt_number']) != "s.n.") $text .= " " . $row['alt_number'];
        if ($row['series']) $text .= " " . $row['series'];
        $text .= " " . $row['series_number'];
    } else {
        if ($row['series']) $text .= " " . $row['series'];
        if ($row['Nummer']) $text .= " " . $row['Nummer'];
        if ($row['alt_number']) $text .= " " . $row['alt_number'];
        if (strstr($row['alt_number'], "s.n.")) $text .= " [" . $row['Datum'] . "]";
    }

    return $text . " <" . $row['specimen_ID'] . ">";
}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - edit Specimens Types</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <link rel="stylesheet" type="text/css" href="js/lib/jQuery/css/ui-lightness/jquery-ui.custom.css">
  <style type="text/css">
    table.out { width: 100% }
    tr.out { }
    th.out { font-style: italic }
    td.out { background-color: #669999; }
	.ui-autocomplete {
        font-size: 0.9em;  /* smaller size */
		max-height: 200px;
		overflow-y: auto;
		/* prevent horizontal scrollbar */
		overflow-x: hidden;
		/* add padding to account for vertical scrollbar */
		padding-right: 20px;
	}
	/* IE 6 doesn't support max-height
	 * we use height instead, but this forces the menu to always be this tall
	 */
	* html .ui-autocomplete {
		height: 200px;
	}
  </style>
  <script src="js/lib/jQuery/jquery.min.js" type="text/javascript"></script>
  <script src="js/lib/jQuery/jquery-ui.custom.min.js" type="text/javascript"></script>
</head>

<body>

<?php
if (isset($_GET['new'])) {
    $sql = "SELECT c.Sammler, c2.Sammler_2, ss.series, wg.series_number,
             wg.Nummer, wg.alt_number, wg.Datum, wg.HerbNummer, wg.specimen_ID
            FROM tbl_specimens wg
             LEFT JOIN tbl_specimens_series ss ON ss.seriesID = wg.seriesID
             LEFT JOIN tbl_collector c ON c.SammlerID = wg.SammlerID
             LEFT JOIN tbl_collector_2 c2 ON c2.Sammler_2ID = wg.Sammler_2ID
            WHERE specimen_ID = " . extractID($_GET['ID']);
    $result = db_query($sql);
    $p_specimen = makeCollector(mysql_fetch_array($result));
    $p_taxon = "";
    $p_typus = 7;
    $p_annotations = $p_specimens_types_ID = $p_typified_by = $p_typified_date = "";
    $p_taxonIndex = 0;
} elseif (extractID($_GET['ID']) !== "NULL") {
    $sql ="SELECT specimens_types_ID, taxonID, specimenID, typusID, annotations, typified_by_Person, typified_Date
           FROM tbl_specimens_types
           WHERE specimens_types_ID = " . extractID($_GET['ID']);
    $result = db_query($sql);
    if (mysql_num_rows($result) > 0) {
        $row = mysql_fetch_array($result);
        $p_typus = $row['typusID'];
        $p_annotations = $row['annotations'];
        $p_specimens_types_ID = $row['specimens_types_ID'];
        $p_typified_by = $row['typified_by_Person'];
        $p_typified_date = $row['typified_Date'];

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
        if (mysql_num_rows($result) > 0) {
            $p_taxon = taxon(mysql_fetch_array($result));
            $p_taxonIndex = $row['taxonID'];
        } else {
            $p_taxon = "";
            $p_taxonIndex = 0;
        }

        $sql = "SELECT c.Sammler, c2.Sammler_2, ss.series, wg.series_number,
                 wg.Nummer, wg.alt_number, wg.Datum, wg.HerbNummer, wg.specimen_ID
                FROM tbl_specimens wg
                 LEFT JOIN tbl_specimens_series ss ON ss.seriesID = wg.seriesID
                 LEFT JOIN tbl_collector c ON c.SammlerID = wg.SammlerID
                 LEFT JOIN tbl_collector_2 c2 ON c2.Sammler_2ID = wg.Sammler_2ID
                WHERE specimen_ID = '" . $row['specimenID'] . "'";
        $result = db_query($sql);
        $p_specimen = (mysql_num_rows($result) > 0) ? makeCollector(mysql_fetch_array($result)) : "";
    } else {
        $p_taxon = $p_specimen = $p_annotations = $p_specimens_types_ID = $p_typified_by = $p_typified_date = "";
        $p_typus = 7;
        $p_taxonIndex = 0;
    }
} else {
    $p_taxon              = $_POST['taxon'];
    $p_taxonIndex         = (strlen(trim($_POST['taxon']))>0) ? $_POST['taxonIndex'] : 0;
    $p_specimen           = $_POST['specimen'];
    $p_typus              = $_POST['typus'];
    $p_typified_by        = $_POST['typified_by'];
    $p_typified_date      = $_POST['typified_date'];
    $p_annotations        = $_POST['annotations'];
    $p_specimens_types_ID = $_POST['specimens_types_ID'];

    if ($_POST['submitUpdate'] && (($_SESSION['editControl'] & 0x8000) != 0)) {
        if (extractID($p_taxon) != "NULL" && extractID($p_specimen) != "NULL") {
            $sql_data = "taxonID = " . extractID($p_taxon) . ",
                         specimenID = " . extractID($p_specimen) . ",
                         typusID = " . quoteString($p_typus) . ",
                         typified_by_Person = '" . mysql_escape_string($p_typified_by) . "',
                         typified_Date = '" . mysql_escape_string($p_typified_date) . "',
                         annotations = " . quoteString($p_annotations);
            if (intval($p_specimens_types_ID)) {
                $sql = "UPDATE tbl_specimens_types SET
                         $sql_data
                        WHERE specimens_types_ID = " . intval($p_specimens_types_ID);
                $updated = 1;
            } else {
                $sql = "INSERT INTO tbl_specimens_types SET $sql_data";
                $updated = 0;
            }
            $result = db_query($sql);
            $id = (intval($p_specimens_types_ID)) ? intval($p_specimens_types_ID) : mysql_insert_id();
            logSpecimensTypes($id, $updated);
            if ($result) {
                echo "<script language=\"JavaScript\">\n"
                   . "  window.opener.document.f.reload.click()\n"
                   . "  self.close()\n"
                   . "</script>\n";
            }
        }
        else {
            echo "<script language=\"JavaScript\">\n"
               . "  alert('Bad formatted Taxon ID or Specimen ID');\n"
               . "</script>\n";
        }
    }
}
?>

<form Action="<?php echo $_SERVER['PHP_SELF']; ?>" Method="POST" name="f">

<?php
unset($typus);
$result = db_query("SELECT typus_lat, typusID FROM tbl_typi ORDER BY typus_lat");
if ($result && mysql_num_rows($result) > 0) {
    while ($row = mysql_fetch_array($result)) {
        $typus[0][] = $row['typusID'];
        $typus[1][] = $row['typus_lat'];
    }
}

$cf = new CSSF();

echo "<input type=\"hidden\" name=\"specimens_types_ID\" value=\"" . htmlspecialchars($p_specimens_types_ID) . "\">\n";
$cf->label(7, 0.5, "ID");
$cf->text(7, 0.5, "&nbsp;" . (($p_specimens_types_ID) ? $p_specimens_types_ID : "new"));

$cf->label(7, 2.5, "Specimen");
$cf->text(7, 2.5, "&nbsp;" . $p_specimen);
echo "<input type=\"hidden\" name=\"specimen\" value=\"" . htmlspecialchars($p_specimen) . "\">\n";

$cf->label(7, 4.5, "taxon");
//$cf->editDropdown(7, 5.5, 28, "taxon", $p_taxon, makeTaxon($p_taxon, 7, 4), 520);
$cf->inputJqAutocomplete(7, 4.5, 28, "taxon", $p_taxon, $p_taxonIndex, "index_jq_autocomplete.php?field=taxonNoExternals", 520, 2);

$cf->labelMandatory(7, 7, 3, "type");
$cf->dropdown(7, 7, "typus", $p_typus, $typus[0], $typus[1]);

$cf->label(7, 10, "typified by");
$cf->inputText(7, 10, 28, "typified_by", $p_typified_by, 255);

$cf->label(7, 12, "date");
$cf->inputText(7, 12, 10, "typified_date", $p_typified_date, 10);

$cf->label(7, 14, "annotations");
$cf->textarea(7, 14, 28, 4, "annotations", $p_annotations);

if (($_SESSION['editControl'] & 0x8000) != 0) {
    $text = ($p_specimens_types_ID) ? " Update " : " Insert ";
    $cf->buttonSubmit(2, 22, "reload", " Reload ");
    $cf->buttonReset(10, 22, " Reset ");
    $cf->buttonSubmit(20, 22, "submitUpdate", $text);
}
$cf->buttonJavaScript(28, 22, " Cancel ", "self.close()");
?>

</form>
</body>
</html>