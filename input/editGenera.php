<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");
require("inc/herbardb_input_functions.php");
require("inc/log_functions.php");
no_magic();

$get_update = (isset($_GET['update'])) ? intval($_GET['update']) : 0;
$get_new    = (isset($_GET['new'])) ? intval($_GET['new']) : 0;
$get_sel    = (isset($_GET['sel'])) ? intval($_GET['sel']) : 0;

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - edit Genera</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <link rel="stylesheet" type="text/css" href="js/lib/jQuery/css/ui-lightness/jquery-ui.custom.css">
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
  <script src="js/lib/jQuery/jquery.min.js" type="text/javascript"></script>
  <script src="js/lib/jQuery/jquery-ui.custom.min.js" type="text/javascript"></script>
  <script type="text/javascript" language="JavaScript">
    function editSpecies(sel) {
      target = "editSpecies.php?sel=<" + sel + ">";
      options = "width=";
      if (screen.availWidth<990)
        options += (screen.availWidth - 70) + ",height=";
      else
        options += "990, height=";
      if (screen.availHeight<740)
        options += (screen.availHeight - 70);
      else
        options += "740";
      options += ", top=70,left=70,scrollbars=yes,resizable=yes";
      MeinFenster = window.open(target,"Species",options);
      MeinFenster.focus();
    }
    function editAuthor() {
      target = "editAuthor.php?sel=" + encodeURIComponent(document.f.authorIndex.value) + "&typ='a'";
      MeinFenster = window.open(target,"editAuthor","width=500,height=200,top=50,left=50,scrollbars=yes,resizable=yes");
      MeinFenster.focus();
    }
    function searchAuthor() {
      MeinFenster = window.open("searchAuthor.php","searchAuthor","scrollbars=yes,resizable=yes");
      MeinFenster.focus();
    }
    function editFamily() {
      target = "editFamily.php?update=1&sel=" + encodeURIComponent(document.f.familyIndex.value);
      MeinFenster = window.open(target,"editFamily","width=300,height=150,top=60,left=60,scrollbars=yes,resizable=yes");
      MeinFenster.focus();
    }
  </script>
</head>

<body>

<?php
$blocked = false;
/**
 * Update-Button was clicked
 */
if (isset($_POST['submitUpdate']) && $_POST['submitUpdate']) {
    if (checkRight('use_access')) {
        if (intval($_POST['genID'])) {
            // check if user has update rights for the old familyID
            $sql = "SELECT ac.update
                    FROM (herbarinput_log.tbl_herbardb_access ac, tbl_tax_genera tg)
                     INNER JOIN tbl_tax_families tf USING (familyID)
                    WHERE tg.genID = '".intval($_POST['genID'])."'
                     AND (ac.familyID = tf.familyID OR ac.categoryID = tf.categoryID)
                     AND ac.userID = '".$_SESSION['uid']."'";
            $result = db_query($sql);
            if (mysql_num_rows($result)>0) {
                $row = mysql_fetch_array($result);
                if (!$row['update']) $blocked = true;  // no update access
            } else {
                $blocked = true;                       // no access at all
            }
        }

        // check if user has access to the new familyID
        $sql = "SELECT ac.update
                FROM herbarinput_log.tbl_herbardb_access ac, tbl_tax_families tf
                WHERE tf.familyID = ".intval($_POST['familyIndex'])."
                 AND (ac.familyID = tf.familyID OR ac.categoryID = tf.categoryID)
                 AND ac.userID = '".$_SESSION['uid']."'";
        $result = db_query($sql);
        if (mysql_num_rows($result)==0) $blocked = true; // no access
    }

    if (!checkRight('unlock_tbl_tax_genera') && isLocked('tbl_tax_genera', $_POST['genID'])) $blocked = true;

    $familyID = (strlen(trim($_POST['family']))>0) ? $_POST['familyIndex'] : 0;
    if (!$blocked && intval($familyID)) {
        $authorID = (strlen(trim($_POST['author']))>0) ? $_POST['authorIndex'] : 0;
        $taxonID  = (strlen(trim($_POST['taxon']))>0) ? $_POST['taxonIndex'] : 0;
        if (checkRight('genera')) {
            $dtid = $_POST['DTID'];
            $dtzid = $_POST['DTZID'];
            $remarks = $_POST['remarks'];
            if (checkRight('unlock_tbl_tax_genera')) {
                $lock = ", locked=".(($_POST['locked']) ? "'1'" : "'0'");
            } else {
                $lock = "";
            }
            if (intval($_POST['genID'])) {
                $id = intval($_POST['genID']);
                if (strlen(trim($_POST['genus']))>0 && intval($familyID)) {
                    $sql = "UPDATE tbl_tax_genera SET
                             genus = ".quoteString($_POST['genus']).",
                             authorID = ".makeInt($authorID).",
                             DallaTorreIDs = ".quoteString($dtid).",
                             DallaTorreZusatzIDs = ".quoteString($dtzid).",
                             hybrid = ".(($_POST['hybrid']) ? "'X'" : "NULL").",
                             accepted = ".(($_POST['accepted']) ? "'1'" : "'0'").",
                             familyID = ".makeInt($familyID).",
                             fk_taxonID = ".makeInt($taxonID).",
                             remarks = ".quoteString($remarks)."
                             $lock
                            WHERE genID = '".intval($_POST['genID'])."'";
                    $result = db_query($sql);
                    logGenera($id,1);
                }
            } else {
                $sql = "INSERT INTO tbl_tax_genera SET
                         genus = ".quoteString($_POST['genus']).",
                         authorID = ".makeInt($authorID).",
                         DallaTorreIDs = ".quoteString($dtid).",
                         DallaTorreZusatzIDs = ".quoteString($dtzid).",
                         hybrid = ".(($_POST['hybrid']) ? "'X'" : "NULL").",
                         accepted = ".(($_POST['accepted']) ? "'1'" : "'0'").",
                         familyID = ".makeInt($familyID).",
                         fk_taxonID = ".makeInt($taxonID).",
                         remarks = ".quoteString($remarks)."
                         $lock";
                $result = db_query($sql);
                if ($result) {
                    $id = mysql_insert_id();
                    logGenera($id,0);
                } else {
                    $id = 0;
                }
            }

            $sql = "SELECT tg.genus, tg.DallaTorreIDs, tg.DallaTorreZusatzIDs, ta.author, tf.family, tsc.category
                    FROM tbl_tax_genera tg
                     LEFT JOIN tbl_tax_authors ta ON ta.authorID = tg.authorID
                     LEFT JOIN tbl_tax_families tf ON tf.familyID = tg.familyID
                     LEFT JOIN tbl_tax_systematic_categories tsc ON tsc.categoryID = tf.categoryID
                    WHERE genID = '$id'";
            $result = db_query($sql);
            $row = mysql_fetch_array($result);
            $res = $row['genus'] . " "
                 . $row['author'] . " "
                 . $row['family'] . " "
                 . $row['category'] . " "
                 . $row['DallaTorreIDs']
                 . $row['DallaTorreZusatzIDs'];

            echo "<script language=\"JavaScript\">\n";
            if ($result) {
                if ($get_update) {
                    echo "  window.opener.document.f.gen.value = \"" . addslashes($res) . "\";\n";
                    echo "  window.opener.document.f.genIndex.value = $id;\n";
                }
                echo "  window.opener.document.f.reload.click()\n";
            }
            echo "  self.close()\n";
            echo "</script>\n";
        } elseif (checkRight('dt')) {
            if (intval($_POST['genID'])) {
                $sql = "UPDATE tbl_tax_genera SET
                         DallaTorreIDs = ".quoteString($_POST['DTID']).",
                         DallaTorreZusatzIDs = ".quoteString($_POST['DTZID']).",
                         accepted = ".(($_POST['accepted']) ? "'1'" : "'0'")."
                        WHERE genID = ".intval($_POST['genID']);
                $result = db_query($sql);
                $id = intval($_POST['genID']);
                logGenera($id,1);
            }

            $sql = "SELECT tg.genus, tg.DallaTorreIDs, tg.DallaTorreZusatzIDs, ta.author, tf.family, tsc.category
                    FROM tbl_tax_genera tg
                     LEFT JOIN tbl_tax_authors ta ON ta.authorID = tg.authorID
                     LEFT JOIN tbl_tax_families tf ON tf.familyID = tg.familyID
                     LEFT JOIN tbl_tax_systematic_categories tsc ON tsc.categoryID = tf.categoryID
                    WHERE genID = '$id'";
            $result = db_query($sql);
            $row = mysql_fetch_array($result);
            $res = $row['genus'] . " "
                 . $row['author'] . " "
                 . $row['family'] . " "
                 . $row['category'] . " "
                 . $row['DallaTorreIDs']
                 . $row['DallaTorreZusatzIDs'];

            if ($result) {
                echo "<script language=\"JavaScript\">\n";
                if ($get_update) {
                    echo "  window.opener.document.f.gen.value = \"" . addslashes($res) . "\";\n";
                    echo "  window.opener.document.f.genIndex.value = $id;\n";
                }
                echo "  window.opener.document.f.reload.click()\n";
                echo "  self.close()\n";
                echo "</script>\n";
            }
        }
    }
}

/**
 * normal operation
 */
if ($get_new) {
    $p_genus = $p_DTID = $p_DTZID = $p_hybrid = $p_accepted = $p_remarks = $p_locked = "";
    $p_author = $p_family = $p_taxon = "";
    $p_authorIndex = $p_familyIndex = $p_taxonIndex = $p_genID = 0;
} elseif ($get_sel > 0) {
    $sql = "SELECT tg.genID, tg.genus, tg.DallaTorreIDs, tg.DallaTorreZusatzIDs,
             tg.hybrid, tg.accepted, tg.fk_taxonID, tg.remarks, tg.locked,
             ta.author, ta.authorID, ta.Brummit_Powell_full,
             tf.family, tf.familyID, tsc.category
            FROM tbl_tax_genera tg
             LEFT JOIN tbl_tax_authors ta ON ta.authorID = tg.authorID
             LEFT JOIN tbl_tax_families tf ON tf.familyID = tg.familyID
             LEFT JOIN tbl_tax_systematic_categories tsc ON tsc.categoryID = tf.categoryID
            WHERE genID = '$get_sel'";
    $result = db_query($sql);
    if (mysql_num_rows($result) > 0) {
        $row = mysql_fetch_array($result);
        $p_genus    = $row['genus'];
        $p_genID    = $row['genID'];
        $p_DTID     = $row['DallaTorreIDs'];
        $p_DTZID    = $row['DallaTorreZusatzIDs'];
        $p_hybrid   = $row['hybrid'];
        $p_accepted = $row['accepted'];
        $p_remarks  = $row['remarks'];
        $p_locked   = $row['locked'];
        if ($row['author']) {
            $p_author = $row['author'];
            if ($row['Brummit_Powell_full']) {
                $p_author .= chr(194) . chr(183) . " [" . strtr($row['Brummit_Powell_full'], "\r\n\xa0", "   ") . "]";
            }
            $p_authorIndex = $row['authorID'];
        } else {
            $p_author     = "";
            $p_taxonIndex = 0;
        }
        $p_family      = $row['family'] . " " . $row['category'];
        $p_familyIndex = $row['familyID'];
        if ($row['fk_taxonID']) {
            $p_taxonIndex = $row['fk_taxonID'];
            $p_taxon      = getScientificName($p_taxonIndex, false, false);
        } else {
            $p_taxon      = "";
            $p_taxonIndex = 0;
        }
    } else {
        $p_genus = $p_DTID = $p_DTZID = $p_hybrid = $p_accepted = $p_remarks = $p_locked = "";
        $p_author = $p_family = $p_taxon = "";
        $p_authorIndex = $p_familyIndex = $p_taxonIndex = $p_genID = 0;
    }
} else {
    $p_genus       = $_POST['genus'];
    $p_DTID        = $_POST['DTID'];
    $p_DTZID       = $_POST['DTZID'];
    $p_hybrid      = isset($_POST['hybrid']) ? $_POST['hybrid'] : '';
    $p_accepted    = isset($_POST['accepted']) ? $_POST['accepted'] : '';
    $p_taxon       = $_POST['taxon'];
    $p_taxonIndex  = (strlen(trim($_POST['taxon']))>0) ? $_POST['taxonIndex'] : 0;
    $p_remarks     = $_POST['remarks'];
    $p_locked      = $_POST['locked'];
    $p_author      = $_POST['author'];
    $p_authorIndex = (strlen(trim($_POST['author']))>0) ? $_POST['authorIndex'] : 0;
    $p_family      = $_POST['family'];
    $p_familyIndex = (strlen(trim($_POST['family']))>0) ? $_POST['familyIndex'] : 0;
    $p_genID       = $_POST['genID'];
}
?>

<?php if ($blocked): ?>
<script type="text/javascript" language="JavaScript">
  alert('You have no sufficient rights for the desired operation');
</script>
<?php endif; ?>

<form Action="<?php echo $_SERVER['PHP_SELF']; ?>?update=<?php echo $get_update; ?>" Method="POST" name="f">

<?php
$cf = new CSSF();

echo "<input type=\"hidden\" name=\"genID\" value=\"$p_genID\">\n";
$cf->label(8,0.5,"ID");
$cf->text(8,0.5,"&nbsp;".(($p_genID)?$p_genID:"new"));
if ($p_genID) {
    $sql = "SELECT taxonID
            FROM tbl_tax_species
            WHERE speciesID IS NULL
             AND subspeciesID IS NULL AND subspecies_authorID IS NULL
             AND varietyID IS NULL AND variety_authorID IS NULL
             AND subvarietyID IS NULL AND subvariety_authorID IS NULL
             AND formaID IS NULL AND forma_authorID IS NULL
             AND subformaID IS NULL AND subforma_authorID IS NULL
             AND genID = '".intval($p_genID)."'";
    $result = db_query($sql);
    $row = mysql_fetch_array($result);
    $cf->label(8,2,"edit Species","javascript:editSpecies('".$row['taxonID']."')");
}

if (checkRight('unlock_tbl_tax_genera')) {
    $cf->label(32,0.5,"locked");
    $cf->checkbox(32,0.5,"locked",$p_locked);
} elseif (isLocked('tbl_tax_genera', $p_genID)) {
    $cf->label(32,0.5,"locked");
    echo "<input type=\"hidden\" name=\"locked\" value=\"$p_locked\">\n";
}

$cf->labelMandatory(8,4,4,"Genus");
$cf->inputText(8,4,25,"genus",$p_genus,100);
$cf->label(8,6.5,"Author","javascript:editAuthor(document.f.author,'a')");
$cf->inputJqAutocomplete(8,6.5,25,"author",$p_author,$p_authorIndex,"index_jq_autocomplete.php?field=taxAuthor",520,2);
$cf->label(8,8.0,"search","javascript:searchAuthor()");
$cf->label(8,10.5,"Ref No.");
$cf->inputText(8,10.5,7,"DTID",$p_DTID,11);
$cf->label(22,10.5,"Addition");
$cf->inputText(22,10.5,1,"DTZID",$p_DTZID,1);
$cf->label(32,10.5,"Hybrid");
$cf->checkbox(32,10.5,"hybrid",$p_hybrid);
$cf->label(32,12.5,"Accepted");
$cf->checkbox(32,12.5,"accepted",$p_accepted);
$cf->labelMandatory(8,15,4,"Family","javascript:editFamily()");
$cf->inputJqAutocomplete(8,15,25,"family",$p_family,$p_familyIndex,"index_jq_autocomplete.php?field=family",100,2);
$cf->label(8,18,"taxon");
$cf->inputJqAutocomplete(8,18,25,"taxon",$p_taxon,$p_taxonIndex,"index_jq_autocomplete.php?field=taxon",520,2);
$cf->label(8,21,"Remarks");
$cf->textarea(8,21,25,6,"remarks",$p_remarks);

$cf->buttonSubmit(2,34,"reload"," Reload ");
$cf->buttonReset(10,34," Reset ");
if (!isLocked('tbl_tax_genera', $p_genID) || checkRight('unlock_tbl_tax_genera')) {
    $text = ($p_genID) ? " Update " : " Insert ";
    $cf->buttonSubmit(20,34,"submitUpdate",$text);
}
$cf->buttonJavaScript(28,34," New ","self.location.href='editGenera.php?new=1'");
?>
</form>

</body>
</html>