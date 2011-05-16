<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");
require("inc/log_functions.php");
no_magic();

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - edit Periodical</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <link rel="stylesheet" type="text/css" href="inc/jQuery/css/ui-lightness/jquery-ui.custom.css">
  <style type="text/css">
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
  <script type="text/javascript" language="JavaScript">
    function showIPNI(sel) {
      target = "http://www.ipni.org/ipni/publicationsearch?id=" + encodeURIComponent(sel.value) +
               "&query_type=by_id&back_page=query_publications.html&output_format=object_view";
      MeinFenster = window.open(target,"showIPNI");
      MeinFenster.focus();
    }
    function editPeriodicalLib(sel) {
      target = "listPeriodicalLib.php?ID=" + encodeURIComponent(sel);
      MeinFenster = window.open(target,"listPeriodicalLib","width=800,height=400,top=60,left=60,scrollbars=yes,resizable=yes");
      MeinFenster.focus();
    }
  </script>
</head>

<body>

<?php
if (!empty($_POST['submitUpdate']) && (($_SESSION['editControl'] & 0x80) != 0)) {
    $sqldata = "periodical = " . quoteString($_POST['periodical']) . ",
                periodical_full = " . quoteString($_POST['periodical_full']) . ",
                tl2_number = " . quoteString($_POST['tl2_number']) . ",
                bph_number = " . quoteString($_POST['bph_number']) . ",
                ipni_ID = " . quoteString($_POST['ipni_ID']) . ",
                successor_ID = " . (($_POST['successor']) ? makeInt($_POST['successorIndex']) : 'NULL');
    if (intval($_POST['ID'])) {
        $sql = "UPDATE tbl_lit_periodicals SET
                 $sqldata
                WHERE periodicalID = " . makeInt($_POST['ID']);
        $updated = 1;
    } else {
        $sql = "INSERT INTO tbl_lit_periodicals SET
                 $sqldata";
        $updated = 0;
    }
    $result = mysql_query($sql);
    $id = (intval($_POST['ID'])) ? intval($_POST['ID']) : mysql_insert_id();
    logLitPeriodicals($id, $updated);

    echo "<script language=\"JavaScript\">\n";
    echo "  window.opener.document.f.periodical.value = \"" . addslashes($_POST['periodical']) . " <$id>\";\n";
    echo "  window.opener.document.f.reload.click();\n";
    echo "</script>\n";
} else {
    $pieces = explode("<", $_GET['sel']);
    $pieces = explode(">", $pieces[1]);
    $id = intval($pieces[0]);
}
echo "<form name=\"f\" Action=\"" . $_SERVER['PHP_SELF'] . "\" Method=\"POST\">\n";
$sql = "SELECT periodicalID, periodical, periodical_full, tl2_number, bph_number, ipni_ID, successor_ID
        FROM tbl_lit_periodicals
        WHERE periodicalID = '$id'";
$result = db_query($sql);
$row = mysql_fetch_array($result);

$p_successor = $p_successorIndex = '';
if ($row['successor_ID']) {
    $sql = "SELECT periodical, periodicalID
            FROM tbl_lit_periodicals
            WHERE periodicalID  = '" . intval($row['successor_ID']) . "'";
    $result = db_query($sql);
    if (mysql_num_rows($result) > 0) {
        $rowSuccessor = mysql_fetch_array($result);
        $p_successor      = $rowSuccessor['periodical'] . " <" . $rowSuccessor['periodicalID'] . ">";
        $p_successorIndex = $rowSuccessor['periodicalID'];
    }
}

$predecessors = array();
$preID = intval($row['periodicalID']);
do {
    $found = false;
    $result = db_query("SELECT periodicalID FROM tbl_lit_periodicals WHERE successor_ID  = '$preID'");
    if (mysql_num_rows($result) > 0) {
        $rowPre = mysql_fetch_array($result);
        $preID = intval($rowPre['periodicalID']);
        $result = db_query("SELECT periodical, periodicalID FROM tbl_lit_periodicals WHERE periodicalID  = '$preID'");
        $rowPre = mysql_fetch_array($result);
        $predecessors[] = array('text' => $rowPre['periodical'] . " <" . $rowPre['periodicalID'] . ">",
                                'id'   => $rowPre['periodicalID']);
        $found = true;
    }
} while ($found);

$cf = new CSSF();

echo "<input type=\"hidden\" name=\"ID\" value=\"" . $row['periodicalID'] . "\">\n";
$cf->label(8, 1, "ID");
$cf->text(8, 1, "&nbsp;" . (($row['periodicalID']) ? $row['periodicalID'] : "new"));
$cf->label(8, 3, "Periodical");
$cf->inputText(8, 3, 25, "periodical", $row['periodical'], 255);
$cf->label(8, 5, "Full name");
$cf->textarea(8, 5, 25, 4, "periodical_full", $row['periodical_full']);

$cf->label(8, 10, "TL2");
$cf->inputText(8, 10, 5, "tl2_number", $row['tl2_number'], 15);
$cf->label(18, 10, "BPH");
$cf->inputText(18, 10, 5, "bph_number", $row['bph_number'], 15);
$cf->label(28, 10, "IPNI", "javascript:showIPNI(document.f.ipni_ID)");
$cf->inputText(28, 10, 5, "ipni_ID", $row['ipni_ID'], 15);

if ($p_successorIndex) {
    $cf->label(8, 12, "Successor", "javascript:self.location.href='editLitPeriodical.php?sel=<" . $p_successorIndex . ">'");
} else {
    $cf->label(8, 12, "Successor");
}
$cf->inputJqAutocomplete(8, 12, 25, "successor", $p_successor, $p_successorIndex, "index_jq_autocomplete.php?field=periodical", 100, 2);

$y = 14;
if (!empty($predecessors)) {
    $cf->label(8, $y, "Predecessor(s)");
    foreach ($predecessors as $predecessor) {
        $cf->text(8, $y, "<a href=\"javascript:self.location.href='editLitPeriodical.php?sel=<" . $predecessor['id'] . ">'\">" . $predecessor['text'] . "</a>");
        $y += 2;
    }
}

if (($_SESSION['editControl'] & 0x80) != 0) {
    $text = ($row['periodicalID']) ? " Update " : " Insert ";
    $cf->buttonSubmit(9, $y + 1, "submitUpdate", $text);
    $cf->buttonJavaScript(21, $y + 1, " New ", "self.location.href='editLitPeriodical.php?sel= '");
}

if ($row['periodicalID']) {
    $cf->buttonJavaScript(30, 1, " Libraries ", "editPeriodicalLib('" . $row['periodicalID'] . "')");
}

echo "</form>\n";
?>

</body>
</html>