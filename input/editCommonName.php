<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");
require("inc/herbardb_input_functions.php");
require("inc/log_functions.php");
no_magic();

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - edit Index</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <link rel="stylesheet" type="text/css" href="inc/jQuery/css/ui-lightness/jquery-ui.custom.css">
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
  <script src="inc/jQuery/jquery.min.js" type="text/javascript"></script>
  <script src="inc/jQuery/jquery-ui.custom.min.js" type="text/javascript"></script>
</head>

<body>

<?php
if (isset($_GET['new'])) {
    if (intval($_GET['t']) == 1) {
        $p_type = 1;  // taxonID ist die Führungs-ID
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
        $row = mysql_fetch_array($result);
        $p_taxon = taxon($row);
        $p_taxonIndex = intval($row['taxonID']);
        $p_citation = "";
        $p_citationIndex = 0;
    } else {
        $p_type = 2;  // citationID ist die F�hrungs-ID
        $sql ="SELECT citationID, suptitel, le.autor as editor, la.autor,
                l.periodicalID, lp.periodical, vol, part, jahr, pp
               FROM tbl_lit l
               LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID = l.periodicalID
               LEFT JOIN tbl_lit_authors le ON le.autorID = l.editorsID
               LEFT JOIN tbl_lit_authors la ON la.autorID = l.autorID
               WHERE citationID = " . extractID($_GET['ID']);
        $result = db_query($sql);
        $row = mysql_fetch_array($result);
        $p_citation = protolog($row);
        $p_citationIndex = intval($row['citationID']);
        $p_taxon = "";
        $p_taxonIndex = 0;
    }
    $p_paginae = $p_figures = $p_annotations = $p_taxindID = "";
} elseif (isset($_GET['ID']) && extractID($_GET['ID']) !== "NULL") {
    if (intval($_GET['t']) == 1) {
        $p_type = 1;  // taxonID ist die F�hrungs-ID
    } else {
        $p_type = 2;  // citationID ist die F�hrungs-ID
    }

    $sql ="SELECT taxindID, taxonID, citationID, paginae, figures, annotations
           FROM tbl_tax_index
           WHERE taxindID = " . extractID($_GET['ID']);
    $result = db_query($sql);
    if (mysql_num_rows($result) > 0) {
        $row = mysql_fetch_array($result);
        $p_paginae     = $row['paginae'];
        $p_figures     = $row['figures'];
        $p_annotations = $row['annotations'];
        $p_taxindID    = $row['taxindID'];

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
        $result2 = db_query($sql);
        $row2 = mysql_fetch_array($result2);
        $p_taxon = taxon($row2);
        $p_taxonIndex = intval($row2['taxonID']);

        $sql ="SELECT citationID, suptitel, le.autor as editor, la.autor,
                l.periodicalID, lp.periodical, vol, part, jahr, pp
               FROM tbl_lit l
                LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID = l.periodicalID
                LEFT JOIN tbl_lit_authors le ON le.autorID = l.editorsID
                LEFT JOIN tbl_lit_authors la ON la.autorID = l.autorID
               WHERE citationID = '" . $row['citationID'] . "'";
        $result2 = db_query($sql);
        $row2 = mysql_fetch_array($result2);
        $p_citation = protolog($row2);
        $p_citationIndex = intval($row2['citationID']);
    } else {
        $p_taxonID = $p_citationID = $p_paginae = $p_figures = $p_annotations = "";
        $p_taxindID = "";
        $p_taxonIndex = $p_citationIndex = 0;
    }
} else {
    $p_type          = $_POST['type'];
    $p_taxon         = $_POST['taxon'];
    $p_taxonIndex    = $_POST['taxonIndex'];
    $p_citation      = $_POST['citation'];
    $p_citationIndex = $_POST['citationIndex'];
    $p_paginae       = $_POST['paginae'];
    $p_figures       = $_POST['figures'];
    $p_annotations   = $_POST['annotations'];
    $p_taxindID      = $_POST['taxindID'];

    if (isset($_POST['submitUpdate']) && $_POST['submitUpdate'] && (($_SESSION['editControl'] & 0x200) != 0)) {
        $taxonID    = (strlen(trim($_POST['taxon'])) > 0) ? intval($_POST['taxonIndex']) : 0;
        $citationID = (strlen(trim($_POST['citation'])) > 0) ? intval($_POST['citationIndex']) : 0;
        if ($taxonID && $citationID) {
            $sql_data = "taxonID = " . makeInt($taxonID) . ",
                         citationID = " . makeInt($citationID) . ",
                         paginae = " . quoteString($p_paginae) . ",
                         figures = " . quoteString($p_figures) . ",
                         annotations=" . quoteString($p_annotations);
            if (intval($p_taxindID)) {
                $sql = "UPDATE tbl_tax_index SET
                         $sql_data
                        WHERE taxindID = " . intval($p_taxindID);
                $updated = 1;
            } else {
                $sql = "INSERT INTO tbl_tax_index SET $sql_data";
                $updated = 0;
            }
            $result = db_query($sql);
            $id = (intval($p_taxindID)) ? intval($p_taxindID) : mysql_insert_id();
            logIndex($id, $updated);
            if ($result) {
                echo "<script language=\"JavaScript\">\n";
                echo "  window.opener.document.f.reload.click()\n";
                echo "  self.close()\n";
                echo "</script>\n";
            }
        } else {
            echo "<script language=\"JavaScript\">\n";
            echo "  alert('Bad formatted Taxon ID or Citation ID');\n";
            echo "</script>\n";
        }
    }
}
?>

<form Action="<?php echo $_SERVER['PHP_SELF']; ?>" Method="POST" name="f">

<?php
$cf = new CSSF();
/*
$cf->label(7, 10, "paginae");
$cf->inputText(7, 10, 10, "paginae", $p_paginae, 50);
$cf->label(25, 10, "figures");
$cf->inputText(25, 10, 10, "figures", $p_figures, 50);
$cf->label(7, 12, "annotations");
$cf->textarea(7, 12, 28, 4, "annotations", $p_annotations);
*/




/*

echo "<input type=\"hidden\" name=\"taxindID\" value=\"$p_taxindID\">\n";
echo "<input type=\"hidden\" name=\"type\" value=\"$p_type\">\n";
$cf->label(7, 0.5, "ID");
$cf->text(7, 0.5, "&nbsp;" . (($p_taxindID) ? $p_taxindID : "new"));
if ($p_type == 1) {
    echo "<input type=\"hidden\" name=\"taxon\" value=\"$p_taxon\">\n";
    echo "<input type=\"hidden\" name=\"taxonIndex\" value=\"$p_taxonIndex\">\n";
    $cf->label(7, 2.5, "taxon");
    $cf->text(7, 2.5, $p_taxon);
    $cf->label(7, 6, "citation");
    $cf->inputJqAutocomplete(7, 6, 28, "citation", $p_citation, $p_citationIndex, "index_jq_autocomplete.php?field=citation", 520, 2);
} else {
    $cf->label(7, 2.5, "taxon");
    $cf->inputJqAutocomplete(7, 2.5, 28, "taxon", $p_taxon, $p_taxonIndex, "index_jq_autocomplete.php?field=taxon", 520, 2);
    $cf->label(7, 6, "citation");
    $cf->text(7, 6, "&nbsp;" . $p_citation);
    echo "<input type=\"hidden\" name=\"citation\" value=\"$p_citation\">\n";
    echo "<input type=\"hidden\" name=\"citationIndex\" value=\"$p_citationIndex\">\n";
}
$cf->label(7, 10, "paginae");
$cf->inputText(7, 10, 10, "paginae", $p_paginae, 50);
$cf->label(25, 10, "figures");
$cf->inputText(25, 10, 10, "figures", $p_figures, 50);
$cf->label(7, 12, "annotations");
$cf->textarea(7, 12, 28, 4, "annotations", $p_annotations);

if (($_SESSION['editControl'] & 0x200) != 0) {
    $text = ($p_taxindID) ? " Update " : " Insert ";
    $cf->buttonSubmit(2, 20, "reload", " Reload ");
    $cf->buttonReset(10, 20, " Reset ");
    $cf->buttonSubmit(20, 20, "submitUpdate", $text);
}
$cf->buttonJavaScript(28, 20, " Cancel ", "self.close()");
*/
?>

</form>
</body>
</html>