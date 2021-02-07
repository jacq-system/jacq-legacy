<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");
require("inc/log_functions.php");
require("inc/herbardb_input_functions.php");
require __DIR__ . '/vendor/autoload.php';

use Jaxon\Jaxon;

$jaxon = jaxon();
$jaxon->setOption('core.request.uri', 'ajax/editLitTaxaServer.php');

$jaxon->register(Jaxon::CALLABLE_FUNCTION, "setSource");

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
    $p_taxon = $p_taxonAcc = $p_annotations = $p_lit_tax_ID = $p_taxonIndex = $p_taxonAccIndex = "";
    $p_source = "person";
    $p_sourcePers = "Anonymous <39269>";
    $p_sourcePersIndex = 39269;
    $p_sourceLit = $p_sourceLitIndex = $p_et_al = "";
    $p_timestamp = "";
    $p_user = "";
} elseif (isset($_GET['ID']) && extractID($_GET['ID']) !== "NULL") {
    $sql = "SELECT lt.lit_tax_ID, lt.citationID, lt.taxonID, lt.acc_taxon_ID, lt.annotations,
             lt.source, lt.source_citationID, lt.source_person_ID, lt.et_al, lt.timestamp,
             hu.firstname, hu.surname
            FROM tbl_lit_taxa lt
             LEFT JOIN herbarinput_log.tbl_herbardb_users hu ON lt.userID = hu.userID
            WHERE lit_tax_ID = " . extractID($_GET['ID']);
    $result = db_query($sql);
    if (mysql_num_rows($result) > 0) {
        $row = mysql_fetch_array($result);
        $p_lit_tax_ID  = $row['lit_tax_ID'];
        $p_annotations = $row['annotations'];
        $p_timestamp   = $row['timestamp'];
        $p_user        = $row['firstname'] . " " . $row['surname'];

        $sql ="SELECT citationID, suptitel, le.autor as editor, la.autor, l.periodicalID, lp.periodical, vol, part, jahr, pp
               FROM tbl_lit l
                LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID = l.periodicalID
                LEFT JOIN tbl_lit_authors le ON le.autorID = l.editorsID
                LEFT JOIN tbl_lit_authors la ON la.autorID = l.autorID
               WHERE citationID = '" . $row['citationID'] . "'";
        $result = db_query($sql);
        $p_citation = protolog(mysql_fetch_array($result));
        $p_citationIndex = $row['citationID'];

        $p_taxonIndex = $row['taxonID'];
        $p_taxon = getScientificName($p_taxonIndex);

        $p_taxonAccIndex = $row['acc_taxon_ID'];
        $p_taxonAcc = getScientificName($p_taxonAccIndex);

        $p_source = $row['source'];
        if ($p_source == "literature") {
            $sql = "SELECT citationID, suptitel, le.autor as editor, la.autor, l.periodicalID, lp.periodical, vol, part, jahr, pp
                    FROM tbl_lit l
                     LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID = l.periodicalID
                     LEFT JOIN tbl_lit_authors le ON le.autorID = l.editorsID
                     LEFT JOIN tbl_lit_authors la ON la.autorID = l.autorID
                    WHERE citationID = '" . $row['source_citationID'] . "'";
            $result = db_query($sql);
            $p_sourceLit = protolog(mysql_fetch_array($result));
            $p_sourceLitIndex = $row['source_citationID'];
            $p_sourcePers = $p_sourcePersIndex = $p_et_al = "";
        } else {
            $sql = "SELECT person_ID, p_familyname, p_firstname, p_birthdate, p_death
                    FROM tbl_person
                    WHERE person_ID = '" . $row['source_person_ID'] . "'";
            $row2 = mysql_fetch_array(db_query($sql));
            $p_sourcePers = $row2['p_familyname'] . ", " . $row2['p_firstname']
                          . " (" . $row2['p_birthdate'] . " - " . $row2['p_death'] . ") <" . $row2['person_ID'] . ">";
            $p_sourcePersIndex = $row['source_person_ID'];
            $p_et_al = $row['et_al'];
            $p_sourceLit = $p_sourceLitIndex = "";
        }
    }
    else {
        $p_citation = $p_taxon = $p_taxonAcc = $p_annotations = $p_lit_tax_ID = $p_taxonIndex = $p_taxonAccIndex = "";
        $p_source = "person";
        $p_sourcePers = "Anonymous <39269>";
        $p_sourcePersIndex = 39269;
        $p_sourceLit = $p_sourceLitIndex = $p_et_al = $p_timestamp = $p_user = "";
    }
} elseif (!empty($_POST['submitUpdate']) && (($_SESSION['editControl'] & 0x20) != 0)) {
    $annotations = $_POST['annotations'];
    $sqldata = "taxonID = '" . intval($_POST['taxonIndex']) . "',
                acc_taxon_ID = '" . intval($_POST['taxonAccIndex']) . "',
                citationID = " . extractID($_POST['citation']) . ",
                annotations = " . quoteString($annotations) . ",
                userID = '" . intval($_SESSION['uid']) . "'";
    if ($_POST['source'] == 'literature') {
        $sqldata .= ", source = 'literature',
                       source_citationID = '" . intval($_POST['sourceLitIndex']) . "',
                       source_person_ID = NULL,
                       et_al = '0'";
    } else {
        $sqldata .= ", source = 'person',
                       source_citationID = NULL,
                       source_person_ID = '" . intval($_POST['sourcePersIndex']) . "',
                       et_al = '" . ((!empty($_POST['et_al'])) ? 1 : 0) . "'";
    }
    if (intval($_POST['lit_tax_ID'])) {
        $sql = "UPDATE tbl_lit_taxa SET
                $sqldata
                WHERE lit_tax_ID = " . intval($_POST['lit_tax_ID']);
        $updated = 1;
    } else {
        $sql = "INSERT INTO tbl_lit_taxa SET
                $sqldata";
        $updated = 0;
    }
    $result = db_query($sql);
        $p_lit_tax_ID = (intval($_POST['lit_tax_ID'])) ? intval($_POST['lit_tax_ID']) : mysql_insert_id();
        logLitTax($p_lit_tax_ID, $updated);
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
    $p_taxon           = $_POST['taxon'];
    $p_taxonIndex      = $_POST['taxonIndex'];
    $p_taxonAcc        = $_POST['taxonAcc'];
    $p_taxonAccIndex   = $_POST['taxonAccIndex'];
    $p_citation        = $_POST['citation'];
    $p_annotations     = $_POST['annotations'];
    $p_user            = $_POST['user'];
    $p_timestamp       = $_POST['timestamp'];
    $p_lit_tax_ID      = $_POST['lit_tax_ID'];
    $p_source          = $_POST['source'];
    if ($p_source == 'literature') {
        $p_sourceLit       = $_POST['sourceLit'];
        $p_sourceLitIndex  = $_POST['sourceLitIndex'];
        $p_sourcePers      = "";
        $p_sourcePersIndex = "";
        $p_et_al           = "";
    } else {
        $p_sourceLit       = "";
        $p_sourceLitIndex  = "";
        $p_sourcePers      = $_POST['sourcePers'];
        $p_sourcePersIndex = $_POST['sourcePersIndex'];
        $p_et_al           = !empty($_POST['et_al']) ? 1 : 0 ;
    }
}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - edit Taxa</title>
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
  <?php echo $jaxon->getScript(true, true); ?>
  <script src="js/lib/jQuery/jquery.min.js" type="text/javascript"></script>
  <script src="js/lib/jQuery/jquery-ui.custom.min.js" type="text/javascript"></script>
  <script type="text/javascript" language="JavaScript">
    function hideParts() {
      var source = '<?php echo $p_source; ?>';
      if (source == 'literature') {
        document.getElementById('ajax_sourcePers').style.display = 'none';
        document.getElementById('lbl_et_al').style.display = 'none';
        document.getElementById('et_al').style.display = 'none';
      } else {
        document.getElementById('ajax_sourceLit').style.display = 'none';
      }
    }
    function setSource() {
      jaxon_setSource(jaxon.getFormValues('f'));
    }
  </script>
</head>

<body onload="hideParts();">

<form Action="<?php echo $_SERVER['PHP_SELF']; ?>" Method="POST" name="f" id="f">

<?php
$cf = new CSSF();
$cf->nameIsID = true;

echo "<input type=\"hidden\" name=\"lit_tax_ID\" value=\"$p_lit_tax_ID\">\n";
$cf->label(7, 0.5, "ID");
$cf->text(7, 0.5, "&nbsp;" . (($p_lit_tax_ID) ? $p_lit_tax_ID : "new"));

echo "<input type=\"hidden\" name=\"timestamp\" value=\"$p_timestamp\">\n";
echo "<input type=\"hidden\" name=\"user\" value=\"$p_user\">\n";
$cf->label(20, 0.5, "last update:");
$cf->text(20, 0.5, "&nbsp;" . $p_timestamp . "&nbsp;by&nbsp;" . $p_user);

$cf->label(7, 2, "citation");
$cf->text(7, 2, "&nbsp;" . $p_citation);
echo "<input type=\"hidden\" name=\"citation\" value=\"$p_citation\">\n";

$cf->label(7, 4.5, "taxon", "editSpecies.php?sel=<$p_taxonIndex>\" target=\"Species");
$cf->inputJqAutocomplete(7, 4.5, 28, "taxon", $p_taxon, $p_taxonIndex, "index_jq_autocomplete.php?field=taxonNoExternals", 100, 2);
$cf->label(7, 7, "acc. taxon", "editSpecies.php?sel=<$p_taxonAccIndex>\" target=\"Species");
$cf->inputJqAutocomplete(7, 7, 28, "taxonAcc", $p_taxonAcc, $p_taxonAccIndex, "index_jq_autocomplete.php?field=taxonNoExternals", 100, 2);
$cf->label(7, 9.5, "source");

$cf->dropdown(7, 9.5, "source\" onchange=\"setSource()", $p_source, array("literature", "person"), array("literature", "person"));
$cf->inputJqAutocomplete(7, 12, 28, "sourceLit", $p_sourceLit, $p_sourceLitIndex, "index_jq_autocomplete.php?field=citation", 100, 2);
$cf->inputJqAutocomplete(7, 12, 28, "sourcePers", $p_sourcePers, $p_sourcePersIndex, "index_jq_autocomplete.php?field=person", 100, 2);
$cf->label(7, 14, "et al.", "", "lbl_et_al");
$cf->checkbox(7, 14, "et_al", $p_et_al);

$cf->label(7, 16, "annotations");
$cf->textarea(7, 16, 28, 4, "annotations", $p_annotations);

if (($_SESSION['editControl'] & 0x20) != 0) {
    $text = ($p_lit_tax_ID) ? " Update " : " Insert ";
    $cf->buttonSubmit(2, 22, "reload", " Reload ");
    $cf->buttonReset(10, 22, " Reset ");
    $cf->buttonSubmit(20, 22, "submitUpdate", $text);
}
$cf->buttonJavaScript(28, 22, " Cancel ", "self.close()");
?>

</form>
</body>
</html>