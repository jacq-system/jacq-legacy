<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");
require("inc/log_functions.php");
require("inc/herbardb_input_functions.php");
require __DIR__ . '/vendor/autoload.php';

use Jaxon\Jaxon;

$jaxon = jaxon();
$jaxon->setOption('core.request.uri', 'ajax/editTaxSynonymyServer.php');

$jaxon->register(Jaxon::CALLABLE_FUNCTION, "setSource");

function dateconvert($date,$tomysql=false){
	/*if($tomysql){
		$date=explode('/',$date);
		return $date[2].'-'.$date[1].'-'.$date[0];
	}else{
		$date=explode('-',$date);
		return $date[2].'/'.$date[1].'/'.$date[0];
	}*/
	return $date;
}


if (isset($_GET['new'])) {
    $sql = "SELECT taxonID, genus, DallaTorreIDs, DallaTorreZusatzIDs,
         author, author1, author2, author3, author4, author5,
         epithet,epithet1,epithet2,epithet3,epithet4,epithet5
        FROM {$_CONFIG['DATABASE']['VIEWS']['name']}.view_taxon
        WHERE taxonID = " . extractID($_GET['ID']);
    $p_taxon = taxon(dbi_query($sql)->fetch_array());
    $p_taxonAcc = $p_annotations = $p_tax_syn_ID = $p_taxonAccIndex = "";
    $p_preferred = 0;
    $p_source = "person";
    $p_sourcePers = "Anonymous <39269>";
    $p_sourcePersIndex = 39269;
    $p_sourceLit = $p_sourceLitIndex = "";
    $p_sourceService = "";
    $p_timestamp = "";
    $p_user = "";
    $p_ref_date="";
    $p_source_specimen="";
    $p_source_specimenIndex="";


} elseif (isset($_GET['ID']) && extractID($_GET['ID']) !== "NULL") {
    $sql = "SELECT ts.tax_syn_ID, ts.taxonID, ts.acc_taxon_ID, ts.annotations, ts.preferred_taxonomy,
             ts.source, ts.source_citationID, ts.source_person_ID, ts.source_serviceID,ts.ref_date,ts.source_specimenID, ts.timestamp,
             hu.firstname, hu.surname
            FROM tbl_tax_synonymy ts
             LEFT JOIN herbarinput_log.tbl_herbardb_users hu ON ts.userID = hu.userID
            WHERE tax_syn_ID = " . extractID($_GET['ID']);
    $result = dbi_query($sql);
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_array($result);
        $p_tax_syn_ID  = $row['tax_syn_ID'];
        $p_preferred = ($row['preferred_taxonomy']) ? 1 : 0;
        $p_annotations = $row['annotations'];
        $p_timestamp   = $row['timestamp'];
        $p_user        = $row['firstname'] . " " . $row['surname'];
        $p_ref_date=dateconvert($row['ref_date']);

        $sql = "SELECT taxonID, genus, DallaTorreIDs, DallaTorreZusatzIDs,
                 author,  author1,  author2,  author3,  author4,  author5,
                 epithet, epithet1, epithet2, epithet3, epithet4, epithet5
                FROM {$_CONFIG['DATABASE']['VIEWS']['name']}.view_taxon
                WHERE taxonID = '" . $row['taxonID'] . "'";
        $p_taxon = taxon(dbi_query($sql)->fetch_array());

        $sql = "SELECT taxonID, genus, DallaTorreIDs, DallaTorreZusatzIDs,
                 author, author1, author2, author3, author4, author5,
                 epithet,epithet1,epithet2,epithet3,epithet4,epithet5
                FROM {$_CONFIG['DATABASE']['VIEWS']['name']}.view_taxon
                WHERE taxonID = '" . $row['acc_taxon_ID'] . "'";
        $p_taxonAcc = taxon(dbi_query($sql)->fetch_array());
        $p_taxonAccIndex = $row['acc_taxon_ID'];

 	   $sql = "SELECT taxonID, genus, DallaTorreIDs, DallaTorreZusatzIDs,
                author, author1, author2, author3, author4, author5,
                epithet,epithet1,epithet2,epithet3,epithet4,epithet5
               FROM {$_CONFIG['DATABASE']['VIEWS']['name']}.view_taxon
               WHERE taxonID = '" . $row['source_specimenID'] . "'";
        $p_source_specimen = taxon(dbi_query($sql)->fetch_array());
        $p_source_specimenIndex=$row['source_specimenID'];

        $p_source = $row['source'];
        if ($p_source == "literature") {
            $sql = "SELECT citationID, suptitel, le.autor as editor, la.autor, l.periodicalID, lp.periodical, vol, part, jahr, pp
                    FROM tbl_lit l
                     LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID = l.periodicalID
                     LEFT JOIN tbl_lit_authors le ON le.autorID = l.editorsID
                     LEFT JOIN tbl_lit_authors la ON la.autorID = l.autorID
                    WHERE citationID = '" . $row['source_citationID'] . "'";
            $p_sourceLit = protolog(dbi_query($sql)->fetch_array());
            $p_sourceLitIndex = $row['source_citationID'];
            $p_sourcePers = $p_sourcePersIndex = $p_et_al = $p_sourceService = "";
        } elseif ($p_source == "service") {
            $p_sourcePers = $p_sourcePersIndex = $p_et_al = $p_sourceLit = $p_sourceLitIndex = "";
            $p_sourceService = $row['source_serviceID'];
        } else {
            $sql = "SELECT person_ID, p_familyname, p_firstname, p_birthdate, p_death
                    FROM tbl_person
                    WHERE person_ID = '" . $row['source_person_ID'] . "'";
            $row2 = mysqli_fetch_array(dbi_query($sql));
            $p_sourcePers = $row2['p_familyname'] . ", " . $row2['p_firstname']
                          . " (" . $row2['p_birthdate'] . " - " . $row2['p_death'] . ") <" . $row2['person_ID'] . ">";
            $p_sourcePersIndex = $row['source_person_ID'];
            $p_sourceLit = $p_sourceLitIndex = $p_sourceService = "";
        }

    }
    else {
        $p_taxon = $p_taxonAcc = $p_annotations = $p_tax_syn_ID = $p_taxonAccIndex = "";
        $p_preferred = 0;
        $p_source = "person";
        $p_sourcePers = "Anonymous <39269>";
        $p_sourcePersIndex = 39269;
        $p_sourceLit = $p_sourceLitIndex = $p_sourceService = $p_timestamp = $p_user = "";
    }
} elseif (!empty($_POST['submitUpdate']) && (($_SESSION['editControl'] & 0x20) != 0)) {

   if (!empty($_POST['preferred'])) {
        dbi_query("UPDATE tbl_tax_synonymy SET
                   preferred_taxonomy = 0
                  WHERE taxonID = " . extractID($_POST['taxon']));
    }
    $annotations = $_POST['annotations'];
    $sqldata = "taxonID = " . extractID($_POST['taxon']) . ",
                acc_taxon_ID = " . ((intval($_POST['taxonAccIndex']) == 0 || strlen($_POST['taxonAcc']) == 0 || $_POST['taxonAcc'] == '0' || $_POST['taxonAcc'] == chr(183) . ' <>') ? 'NULL' : "'" . intval($_POST['taxonAccIndex']) . "'" ) . ",
                preferred_taxonomy = " . ((!empty($_POST['preferred'])) ? 1 : 0) . ",
                annotations = " . quoteString($annotations) . ",
                ref_date = '" . dateconvert($_POST['ref_date'],true) . "',
                source_specimenID = '" . intval($_POST['source_specimenIndex']) . "',
                userID = '" . intval($_SESSION['uid']) . "'";
    if ($_POST['source'] == 'literature') {
        $sqldata .= ", source = 'literature',
                       source_citationID = '" . intval($_POST['sourceLitIndex']) . "',
                       source_person_ID = NULL,
                       source_serviceID = NULL";
    } elseif ($_POST['source'] == 'service') {
        $sqldata .= ", source = 'service',
                       source_citationID = NULL,
                       source_person_ID = NULL,
                       source_serviceID = '" . intval($_POST['sourceService']) . "'";
    } else {
        $sqldata .= ", source = 'person',
                       source_citationID = NULL,
                       source_person_ID = '" . intval($_POST['sourcePersIndex']) . "',
                       source_serviceID = NULL";
    }
    if (intval($_POST['tax_syn_ID'])) {
        $sql = "UPDATE tbl_tax_synonymy SET
                $sqldata
                WHERE tax_syn_ID = " . intval($_POST['tax_syn_ID']);
        $updated = 1;
    } else {
        $sql = "INSERT INTO tbl_tax_synonymy SET
                $sqldata";
        $updated = 0;
    }//echo $sql;exit;
    $result = dbi_query($sql);
    $p_tax_syn_ID = (intval($_POST['tax_syn_ID'])) ? intval($_POST['tax_syn_ID']) : dbi_insert_id();
    logTbl_tax_synonymy($p_tax_syn_ID, $updated);
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
    $p_taxonAcc        = $_POST['taxonAcc'];
    $p_taxonAccIndex   = $_POST['taxonAccIndex'];
    $p_preferred       = $_POST['preferred'];
    $p_annotations     = $_POST['annotations'];
    $p_user            = $_POST['user'];
    $p_timestamp       = $_POST['timestamp'];
    $p_tax_syn_ID      = $_POST['tax_syn_ID'];
    $p_source          = $_POST['source'];
    $p_ref_date        = $_POST['ref_date'];
    $p_source_specimen = $_POST['source_specimen'];
    $p_source_specimenIndex= $_POST['source_specimenIndex'];
    if ($p_source == 'literature') {
        $p_sourceLit       = $_POST['sourceLit'];
        $p_sourceLitIndex  = $_POST['sourceLitIndex'];
        $p_sourcePers      = "";
        $p_sourcePersIndex = "";
        $p_sourceService   = "";
    } elseif ($p_source == 'service') {
        $p_sourceLit       = "";
        $p_sourceLitIndex  = "";
        $p_sourcePers      = "";
        $p_sourcePersIndex = "";
        $p_sourceService   = $_POST['sourceService'];
    } else {
        $p_sourceLit       = "";
        $p_sourceLitIndex  = "";
        $p_sourcePers      = $_POST['sourcePers'];
        $p_sourcePersIndex = $_POST['sourcePersIndex'];
        $p_sourceService   = "";
    }
}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - edit Synonymy</title>
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
  <script src="js/lib/jQuery/jquery.inputmask.js" type="text/javascript"></script>

  <script type="text/javascript" language="JavaScript">

	$(document).ready(function() {
		$("#ref_date").inputmask("y-m-d");
		var source = '<?php echo $p_source; ?>';
		if (source == 'literature') {
			document.getElementById('ajax_sourcePers').style.display = 'none';
			document.getElementById('sourceService').style.display = 'none';
		} else if (source == 'service') {
			document.getElementById('ajax_sourcePers').style.display = 'none';
			document.getElementById('ajax_sourceLit').style.display = 'none';
		} else {
			document.getElementById('ajax_sourceLit').style.display = 'none';
			document.getElementById('sourceService').style.display = 'none';
		}
	});
	function checkdate() {
		val=$("#ref_date").val();
		if(!val.match(/^\d\d\d\d?-\d\d?-\d\d$/) && !val.match(/^YYYY-MM-DD$/) && val!=''){
			alert("\nMistake in Reference Date.\n\nPlease insert blank or correct Date.\n");
			$("#ref_date").focus();
		}
    }
	function hideParts() {

    }




    function setSource() {
      jaxon_setSource(jaxon.getFormValues('f'));
    }
  </script>
</head>

<body onload="hideParts();">

<form Action="<?php echo $_SERVER['PHP_SELF']; ?>" Method="POST" name="f" id="f">

<?php
unset($service);
$service[0][] = 0; $service[1][] = "";
$sql = "SELECT name, serviceID FROM tbl_nom_service ORDER BY name";
if ($result = dbi_query($sql)) {
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_array($result)) {
            $service[0][] = $row['serviceID'];
            $service[1][] = $row['name'];
        }
    }
}


$cf = new CSSF();
$cf->nameIsID = true;

echo "<input type=\"hidden\" name=\"tax_syn_ID\" value=\"$p_tax_syn_ID\">\n";
$cf->label(7, 0.5, "ID");
$cf->text(7, 0.5, "&nbsp;" . (($p_tax_syn_ID) ? $p_tax_syn_ID : "new"));

echo "<input type=\"hidden\" name=\"timestamp\" value=\"$p_timestamp\">\n";
echo "<input type=\"hidden\" name=\"user\" value=\"$p_user\">\n";
$cf->label(20, 0.5, "last update:");
$cf->text(20, 0.5, "&nbsp;" . $p_timestamp . "&nbsp;by&nbsp;" . $p_user);

$cf->label(7, 2, "citation");
$cf->text(7, 2, "&nbsp;" . $p_taxon);
echo "<input type=\"hidden\" name=\"taxon\" value=\"$p_taxon\">\n";

$cf->label(7, 4, "acc. taxon", "editSpecies.php?sel=<$p_taxonAccIndex>\" target=\"Species");
$cf->inputJqAutocomplete(7, 4, 28, "taxonAcc", $p_taxonAcc, $p_taxonAccIndex, "index_jq_autocomplete.php?field=taxonNoExternals", 100, 2);
$cf->label(12, 6, "preferred taxonomy");
$cf->checkbox(12, 6, "preferred", $p_preferred);

$cf->label(7, 9.5, "source");

$cf->dropdown(7, 9.5, "source\" onchange=\"setSource()", $p_source, array("literature", "person", "service"), array("literature", "person", "service"));
$cf->inputJqAutocomplete(7, 12, 28, "sourceLit", $p_sourceLit, $p_sourceLitIndex, "index_jq_autocomplete.php?field=citation", 100, 2);
$cf->inputJqAutocomplete(7, 12, 28, "sourcePers", $p_sourcePers, $p_sourcePersIndex, "index_jq_autocomplete.php?field=person", 100, 2);
$cf->dropdown(7, 12, "sourceService", $p_sourceService, $service[0], $service[1]);

$cf->label(7, 16, "Ref Date");
$cf->inputText(7, 16, 8,  "ref_date\" onBlur=\"checkdate()\"", $p_ref_date,10);

$cf->label(7, 19, "annotations");
$cf->textarea(7, 19, 28, 4, "annotations", $p_annotations);

$cf->label(7, 25, "source specimen", "editSpecies.php?sel=<$p_source_specimenIndex>\" target=\"Species");
$cf->inputJqAutocomplete(7, 25, 28, "source_specimen", $p_source_specimen, $p_source_specimenIndex, "index_jq_autocomplete.php?field=taxonNoExternals", 100, 2);


if (($_SESSION['editControl'] & 0x20) != 0) {
    $text = ($p_tax_syn_ID) ? " Update " : " Insert ";
    $cf->buttonSubmit(2, 31, "reload", " Reload ");
    $cf->buttonReset(10, 31, " Reset ");
    $cf->buttonSubmit(20, 31, "submitUpdate", $text);
}
$cf->buttonJavaScript(28, 31, " Cancel ", "self.close()");
?>

</form>
</body>
</html>