<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");
require("inc/log_functions.php");
require("inc/herbardb_input_functions.php");
no_magic();


if (isset($_GET['new'])) {
    $sql ="SELECT citationID, suptitel, le.autor as editor, la.autor, l.periodicalID, lp.periodical, vol, part, jahr, pp
           FROM tbl_lit l
            LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID = l.periodicalID
            LEFT JOIN tbl_lit_authors le ON le.autorID = l.editorsID
            LEFT JOIN tbl_lit_authors la ON la.autorID = l.autorID
           WHERE citationID = " . extractID($_GET['ID']);
    $result = db_query($sql);
    $p_citation = protolog(mysql_fetch_array($result));
    $p_citationIndex = extractID($_GET['ID']);
    $p_person = $p_annotations = $p_lit_persons_ID = $p_personIndex = "";
    $p_timestamp = "";
    $p_user = "";
} elseif (isset($_GET['ID']) && extractID($_GET['ID']) !== "NULL") {
    $sql = "SELECT lp.lit_persons_ID, lp.citationID_fk, lp.personID_fk, lp.annotations, lp.timestamp, hu.firstname, hu.surname
            FROM tbl_lit_persons lp
             LEFT JOIN herbarinput_log.tbl_herbardb_users hu ON lp.userID = hu.userID
            WHERE lit_persons_ID = " . extractID($_GET['ID']);
    $result = db_query($sql);
    if (mysql_num_rows($result) > 0) {
        $row = mysql_fetch_array($result);
        $p_lit_persons_ID  = $row['lit_persons_ID'];
        $p_annotations     = $row['annotations'];
        $p_timestamp       = $row['timestamp'];
        $p_user            = $row['firstname'] . " " . $row['surname'];

        $sql ="SELECT citationID, suptitel, le.autor as editor, la.autor, l.periodicalID, lp.periodical, vol, part, jahr, pp
               FROM tbl_lit l
                LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID = l.periodicalID
                LEFT JOIN tbl_lit_authors le ON le.autorID = l.editorsID
                LEFT JOIN tbl_lit_authors la ON la.autorID = l.autorID
               WHERE citationID = '" . $row['citationID_fk'] . "'";
        $result = db_query($sql);
        $p_citation = protolog(mysql_fetch_array($result));
        $p_citationIndex = $row['citationID_fk'];

        $sql = "SELECT person_ID, p_familyname, p_firstname, p_birthdate, p_death
                FROM tbl_person
                WHERE person_ID = '" . $row['personID_fk'] . "'";
        $row2 = mysql_fetch_array(db_query($sql));
        $p_person = $row2['p_familyname'] . ", " . $row2['p_firstname']
                  . " (" . $row2['p_birthdate'] . " - " . $row2['p_death'] . ") <" . $row2['person_ID'] . ">";
        $p_personIndex = $row['personID_fk'];
    }
    else {
        $p_citation = $p_citationIndex = $p_annotations = $p_lit_persons_ID = "";
        $p_source = "person";
        $p_person = "Anonymous <39269>";
        $p_personIndex = 39269;
        $p_timestamp = $p_user = "";
    }
} elseif (!empty($_POST['submitUpdate']) && (($_SESSION['editControl'] & 0x20) != 0)) {
    $annotations = $_POST['annotations'];
    $sqldata = "citationID_fk = " . extractID($_POST['citation']) . ",
                personID_fk = '" . intval($_POST['personIndex']) . "',
                annotations = " . quoteString($annotations) . ",
                userID = '" . intval($_SESSION['uid']) . "'";
    if (intval($_POST['lit_persons_ID'])) {
        $sql = "UPDATE tbl_lit_persons SET
                $sqldata
                WHERE lit_persons_ID = " . intval($_POST['lit_persons_ID']);
        $updated = 1;
    } else {
        $sql = "INSERT INTO tbl_lit_persons SET
                $sqldata";
        $updated = 0;
    }
    $result = db_query($sql);
        $p_lit_persons_ID = (intval($_POST['lit_persons_ID'])) ? intval($_POST['lit_persons_ID']) : mysql_insert_id();
        logLitTax($p_lit_persons_ID, $updated);
    if ($result) {
        echo "<html><head>\n"
           . "<script language=\"JavaScript\">\n"
           . "  window.opener.document.f.reload.click()\n"
           . "  self.close()\n"
           . "</script>\n"
           . "</head><body></body></html>\n";
        die();
    }
} else {
    $p_citation       = $_POST['citation'];
    $p_annotations    = $_POST['annotations'];
    $p_user           = $_POST['user'];
    $p_timestamp      = $_POST['timestamp'];
    $p_lit_persons_ID = $_POST['lit_persons_ID'];
    $p_person         = $_POST['person'];
    $p_personIndex    = $_POST['personIndex'];
}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - edit cited persons</title>
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

<form Action="<?php echo $_SERVER['PHP_SELF']; ?>" Method="POST" name="f" id="f">

<?php
$cf = new CSSF();
$cf->nameIsID = true;

echo "<input type=\"hidden\" name=\"lit_persons_ID\" value=\"$p_lit_persons_ID\">\n";
$cf->label(7, 0.5, "ID");
$cf->text(7, 0.5, "&nbsp;" . (($p_lit_persons_ID) ? $p_lit_persons_ID : "new"));

echo "<input type=\"hidden\" name=\"timestamp\" value=\"$p_timestamp\">\n";
echo "<input type=\"hidden\" name=\"user\" value=\"$p_user\">\n";
$cf->label(20, 0.5, "last update:");
$cf->text(20, 0.5, "&nbsp;" . $p_timestamp . "&nbsp;by&nbsp;" . $p_user);

$cf->label(7, 2, "citation");
$cf->text(7, 2, "&nbsp;" . $p_citation);
echo "<input type=\"hidden\" name=\"citation\" value=\"$p_citation\">\n";

$cf->label(7, 4, "person");
$cf->inputJqAutocomplete(7, 4, 28, "person", $p_person, $p_personIndex, "index_jq_autocomplete.php?field=person", 100, 2);

$cf->label(7, 6, "annotations");
$cf->textarea(7, 6, 28, 6, "annotations", $p_annotations);

if (($_SESSION['editControl'] & 0x20) != 0) {
    $cf->buttonSubmit(2, 14, "reload", " Reload ");
    $cf->buttonReset(10, 14, " Reset ");
    $cf->buttonSubmit(20, 14, "submitUpdate", ($p_lit_persons_ID) ? " Update " : " Insert ");
}
$cf->buttonJavaScript(28, 14, " Cancel ", "self.close()");
?>

</form>
</body>
</html>