<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");
require("inc/herbardb_input_functions.php");
require("inc/log_functions.php");

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - edit Index</title>
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
    if (intval($_GET['t']) == 1) {
        $p_type = 1;  // taxonID ist die Führungs-ID
        $p_taxonIndex = intval(extractID($_GET['ID'], true));
        $p_taxon = getScientificName($p_taxonIndex);
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
        $row = dbi_query($sql)->fetch_array();
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
    $result = dbi_query($sql);
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_array($result);
        $p_paginae     = $row['paginae'];
        $p_figures     = $row['figures'];
        $p_annotations = $row['annotations'];
        $p_taxindID    = $row['taxindID'];

        $p_taxon = getScientificName($row['taxonID']);
        $p_taxonIndex = intval($row['taxonID']);

        $sql ="SELECT citationID, suptitel, le.autor as editor, la.autor,
                l.periodicalID, lp.periodical, vol, part, jahr, pp
               FROM tbl_lit l
                LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID = l.periodicalID
                LEFT JOIN tbl_lit_authors le ON le.autorID = l.editorsID
                LEFT JOIN tbl_lit_authors la ON la.autorID = l.autorID
               WHERE citationID = '" . $row['citationID'] . "'";
        $row2 = dbi_query($sql)->fetch_array();
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
            $result = dbi_query($sql);
            $id = (intval($p_taxindID)) ? intval($p_taxindID) : dbi_insert_id();
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
?>

</form>
</body>
</html>