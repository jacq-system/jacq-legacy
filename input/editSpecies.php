<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");
require("inc/herbardb_input_functions.php");
require("inc/log_functions.php");
no_magic();

if (!isset($_SESSION['txLinkList'])) $_SESSION['txLinkList'] = '';

if (!empty($_GET['nr'])) {
    $nr = intval($_GET['nr']);
} else {
    $nr = 0;
}
$linkList = $_SESSION['txLinkList'];

/**
 * checks if the item with the given ID is still marked as "external" and clear this flag if set
 *
 * @param string $table epithets or authors
 * @param <type> $id primary-ID of the entry to check
 */
function clearExternal($table, $id)
{
    $id = intval($id);
    if (!$id) return;   // no ID => nothing to do

    switch ($table) {
        case "epithets": $table = "tbl_tax_epithets"; $pid = "epithetID"; break;
        case "authors":  $table = "tbl_tax_authors";  $pid = "authorID";  break;
        default: return;  // wrong keyword => nothing to do
    }

    $result = db_query("SELECT external FROM $table WHERE $pid = $id");
    if (mysql_num_rows($result) > 0) {
        $row = mysql_fetch_array($result);
        if ($row['external']) {
            db_query("UPDATE $table SET external = 0 WHERE $pid = $id");
        }
    }
}

$blocked = false;
if (isset($_GET['sel']) && extractID($_GET['sel']) != "NULL") {
    $sql = "SELECT ts.taxonID, ts.synID, ts.basID, ts.genID, ts.annotation, ts.external,
             tg.genus, tg.DallaTorreIDs, tg.DallaTorreZusatzIDs, tag.author author_g,
             tf.family, tsc.category, tst.status, tst.statusID, tr.rank, tr.tax_rankID,
             ta.author, ta.authorID, ta.Brummit_Powell_full,
             ta1.author author1, ta1.authorID authorID1, ta1.Brummit_Powell_full bpf1,
             ta2.author author2, ta2.authorID authorID2, ta2.Brummit_Powell_full bpf2,
             ta3.author author3, ta3.authorID authorID3, ta3.Brummit_Powell_full bpf3,
             ta4.author author4, ta4.authorID authorID4, ta4.Brummit_Powell_full bpf4,
             ta5.author author5, ta5.authorID authorID5, ta5.Brummit_Powell_full bpf5,
             te.epithet, te.epithetID,
             te1.epithet epithet1, te1.epithetID epithetID1,
             te2.epithet epithet2, te2.epithetID epithetID2,
             te3.epithet epithet3, te3.epithetID epithetID3,
             te4.epithet epithet4, te4.epithetID epithetID4,
             te5.epithet epithet5, te5.epithetID epithetID5
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
             LEFT JOIN tbl_tax_status tst ON tst.statusID = ts.statusID
             LEFT JOIN tbl_tax_rank tr ON tr.tax_rankID = ts.tax_rankID
             LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
             LEFT JOIN tbl_tax_authors tag ON tag.authorID = tg.authorID
             LEFT JOIN tbl_tax_families tf ON tf.familyID = tg.familyID
             LEFT JOIN tbl_tax_systematic_categories tsc ON tf.categoryID = tsc.categoryID
            WHERE taxonID = " . extractID($_GET['sel']);
    $result = db_query($sql);
    if (mysql_num_rows($result) > 0) {
        $row = mysql_fetch_array($result);
        $p_taxonID      = $row['taxonID'];

        $p_species      = ($row['epithet']) ? $row['epithet'] : "";
        $p_speciesIndex = intval($row['epithetID']);
        $p_author       = ($row['author']) ? $row['author'] : "";
        $p_authorIndex  = intval($row['authorID']);
        if ($row['Brummit_Powell_full']) $p_author .= chr(194) . chr(183) . " [" . replaceNewline($row['Brummit_Powell_full']) . "]";

        $p_subspecies      = ($row['epithet1']) ? $row['epithet1'] : "";
        $p_subspeciesIndex = intval($row['epithetID1']);
        $p_subauthor       = ($row['author1']) ? $row['author1'] : "";
        $p_subauthorIndex  = intval($row['authorID1']);
        if ($row['bpf1']) $p_subauthor .= chr(194) . chr(183) . " [" . replaceNewline($row['bpf1']) . "]";

        $p_variety        = ($row['epithet2']) ? $row['epithet2'] : "";
        $p_varietyIndex   = intval($row['epithetID2']);
        $p_varauthor      = ($row['author2']) ? $row['author2'] : "";
        $p_varauthorIndex = intval($row['authorID2']);
        if ($row['bpf2']) $p_varauthor .= chr(194) . chr(183) . " [" . replaceNewline($row['bpf2']) . "]";

        $p_subvariety        = ($row['epithet3']) ? $row['epithet3'] : "";
        $p_subvarietyIndex   = intval($row['epithetID3']);
        $p_subvarauthor      = ($row['author3']) ? $row['author3'] : "";
        $p_subvarauthorIndex = intval($row['authorID3']);
        if ($row['bpf3']) $p_subvarauthor .= chr(194) . chr(183) . " [" . replaceNewline($row['bpf3']) . "]";

        $p_forma          = ($row['epithet4']) ? $row['epithet4'] : "";
        $p_formaIndex     = intval($row['epithetID4']);
        $p_forauthor      = ($row['author4']) ? $row['author4'] : "";
        $p_forauthorIndex = intval($row['authorID4']);
        if ($row['bpf4']) $p_forauthor .= chr(194) . chr(183) . " [" . replaceNewline($row['bpf4']) . "]";

        $p_subforma          = ($row['epithet5']) ? $row['epithet5'] : "";
        $p_subformaIndex     = intval($row['epithetID5']);
        $p_subforauthor      = ($row['author5']) ? $row['author5'] : "";
        $p_subforauthorIndex = intval($row['authorID5']);
        if ($row['bpf5']) $p_subforauthor .= chr(194) . chr(183) . " [" . replaceNewline($row['bpf5']) . "]";

        $p_gen         = $row['genus'] . " " . $row['author_g'] . " " . $row['family'] . " "
                       . $row['category'] . " " . $row['DallaTorreIDs'] . $row['DallaTorreZusatzIDs'];
        $p_genIndex    = intval($row['genID']);
        $p_statusIndex = intval($row['statusID']);
        $p_rankIndex   = intval($row['tax_rankID']);
        $p_annotation  = $row['annotation'];

        $p_external = $row['external'];

        if ($row['synID']) {
            $sql = "SELECT ts.taxonID, tg.genus, tg.DallaTorreIDs, tg.DallaTorreZusatzIDs,
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
                     LEFT JOIN tbl_tax_status tst ON tst.statusID = ts.statusID
                     LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
                     LEFT JOIN tbl_tax_families tf ON tf.familyID = tg.familyID
                    WHERE ts.taxonID = '" . mysql_escape_string($row['synID']) . "'";
            $result2 = db_query($sql);
            $row2 = mysql_fetch_array($result2);
            $p_syn = taxon($row2, true, false);
            $p_synIndex = $row2['taxonID'];
        } else {
            $p_syn = "";
            $p_synIndex = 0;
        }

        if ($row['basID']) {
            $sql = "SELECT ts.taxonID, tg.genus, tg.DallaTorreIDs, tg.DallaTorreZusatzIDs,
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
                     LEFT JOIN tbl_tax_status tst ON tst.statusID = ts.statusID
                     LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
                     LEFT JOIN tbl_tax_families tf ON tf.familyID = tg.familyID
                    WHERE ts.taxonID = '" . mysql_escape_string($row['basID']) . "'";
            $result2 = db_query($sql);
            $row2 = mysql_fetch_array($result2);
            $p_bas = taxon($row2, true, false);
            $p_basIndex = $row2['taxonID'];
        } else {
            $p_bas = "";
            $p_basIndex = 0;
        }
    } else {
        $p_taxonID = "";
        $p_gen = $p_syn = $p_bas = "";
        $p_genIndex = $p_synIndex = $p_basIndex = 0;
        $p_annotation = "";
        $p_external = "";
        $p_author = $p_subauthor = $p_varauthor = $p_subvarauthor = $p_forauthor = $p_subforauthor = "";
        $p_authorIndex = $p_subauthorIndex = $p_varauthorIndex = $p_subvarauthorIndex = $p_forauthorIndex = $p_subforauthorIndex = 0;
        $p_species = $p_subspecies = $p_variety = $p_subvariety = $p_forma = $p_subforma = "";
        $p_speciesIndex = $p_subspeciesIndex = $p_varietyIndex = $p_subvarietyIndex = $p_formaIndex = $p_subformaIndex = 0;
        $p_rankIndex = 1;
        $p_statusIndex = 96;
    }
    if (isset($_GET['new']) && $_GET['new'] == 1) $p_taxonID = "";
    $edit = (!empty($_GET['edit'])) ? true : false;
} else {
    $p_species            = $_POST['species'];
    $p_speciesIndex       = (strlen(trim($_POST['species'])) > 0) ? $_POST['speciesIndex'] : 0;
    $p_author             = $_POST['author'];
    $p_authorIndex        = (strlen(trim($_POST['author'])) > 0) ? $_POST['authorIndex'] : 0;
    $p_subspecies         = $_POST['subspecies'];
    $p_subspeciesIndex    = (strlen(trim($_POST['subspecies'])) > 0) ? $_POST['subspeciesIndex'] : 0;
    $p_subauthor          = $_POST['subauthor'];
    $p_subauthorIndex     = (strlen(trim($_POST['subauthor'])) > 0) ? $_POST['subauthorIndex'] : 0;
    $p_variety            = $_POST['variety'];
    $p_varietyIndex       = (strlen(trim($_POST['variety'])) > 0) ? $_POST['varietyIndex'] : 0;
    $p_varauthor          = $_POST['varauthor'];
    $p_varauthorIndex     = (strlen(trim($_POST['varauthor'])) > 0) ? $_POST['varauthorIndex'] : 0;
    $p_subvariety         = $_POST['subvariety'];
    $p_subvarietyIndex    = (strlen(trim($_POST['subvariety'])) > 0) ? $_POST['subvarietyIndex'] : 0;
    $p_subvarauthor       = $_POST['subvarauthor'];
    $p_subvarauthorIndex  = (strlen(trim($_POST['subvarauthor'])) > 0) ? $_POST['subvarauthorIndex'] : 0;
    $p_forma              = $_POST['forma'];
    $p_formaIndex         = (strlen(trim($_POST['forma'])) > 0) ? $_POST['formaIndex'] : 0;
    $p_forauthor          = $_POST['forauthor'];
    $p_forauthorIndex     = (strlen(trim($_POST['forauthor'])) > 0) ? $_POST['forauthorIndex'] : 0;
    $p_subforma           = $_POST['subforma'];
    $p_subformaIndex      = (strlen(trim($_POST['subforma'])) > 0) ? $_POST['subformaIndex'] : 0;
    $p_subforauthor       = $_POST['subforauthor'];
    $p_subforauthorIndex  = (strlen(trim($_POST['subforauthor'])) > 0) ? $_POST['subforauthorIndex'] : 0;
    $p_gen        = $_POST['gen'];
    $p_genIndex   = (strlen(trim($_POST['gen'])) > 0) ? $_POST['genIndex'] : 0;
    $p_syn        = $_POST['syn'];
    $p_synIndex   = (strlen(trim($_POST['syn'])) > 0) ? $_POST['synIndex'] : 0;
    $p_bas        = $_POST['bas'];
    $p_basIndex   = (strlen(trim($_POST['bas'])) > 0) ? $_POST['basIndex'] : 0;
    $p_annotation = $_POST['annotation'];
    $p_external   = $_POST['external'];

    if ($_POST['rankIndex']) {
        $p_rankIndex = $_POST['rankIndex'];
    } else  {
        $p_rankIndex = 1;
    }

    if ($_POST['statusIndex']) {
        $p_statusIndex = $_POST['statusIndex'];
    } else {
        $p_statusIndex = 96;
    }

    if ((!empty($_POST['submitUpdate']) || !empty($_POST['submitUpdateNew']) || !empty($_POST['submitUpdateCopy'])) && (($_SESSION['editControl'] & 0x1) != 0)) {
        if (checkRight('use_access')) {
            if (intval($_POST['taxonID'])) {
                // check if user has update rights for the old genID
                $sql = "SELECT ac.update
                        FROM (herbarinput_log.tbl_herbardb_access ac, tbl_tax_species ts)
                         INNER JOIN tbl_tax_genera tg USING (genID)
                         INNER JOIN tbl_tax_families tf USING (familyID)
                        WHERE ts.taxonID = '" . intval($_POST['taxonID']) . "'
                         AND (ac.genID = tg.genID
                           OR ac.familyID = tf.familyID
                           OR ac.categoryID = tf.categoryID)
                         AND ac.userID = '" . $_SESSION['uid'] . "'";
                $result = db_query($sql);
                if (mysql_num_rows($result) > 0) {
                    $row = mysql_fetch_array($result);
                    if (!$row['update']) $blocked = true;  // no update access
                } else {
                    $blocked = true;                       // no access at all
                }
            }

            // check if user has access to the new genID
            $sql = "SELECT ac.update
                    FROM (herbarinput_log.tbl_herbardb_access ac, tbl_tax_genera tg)
                     INNER JOIN tbl_tax_families tf USING (familyID)
                    WHERE tg.genID = " . makeInt($p_genIndex) . "
                     AND (ac.genID = tg.genID
                       OR ac.familyID = tf.familyID
                       OR ac.categoryID = tf.categoryID)
                     AND ac.userID = '" . $_SESSION['uid'] . "'";
            $result = db_query($sql);
            if (mysql_num_rows($result) == 0) $blocked = true; // no access
        }

        if (!$blocked) {
            if (intval($_POST['taxonID'])) {
              $p_taxonID = intval($_POST['taxonID']);
              if (intval($p_rankIndex) && intval($p_genIndex)) {
                  $sql = "UPDATE tbl_tax_species SET
                           synID = "               . makeInt($p_synIndex) . ",
                           basID = "               . makeInt($p_basIndex) . ",
                           tax_rankID = "          . makeInt($p_rankIndex) . ",
                           statusID = "            . makeInt($p_statusIndex) . ",
                           genID = "               . makeInt($p_genIndex) . ",
                           speciesID = "           . makeInt($p_speciesIndex) . ",
                           authorID = "            . makeInt($p_authorIndex) . ",
                           subspeciesID = "        . makeInt($p_subspeciesIndex) . ",
                           subspecies_authorID = " . makeInt($p_subauthorIndex) . ",
                           varietyID = "           . makeInt($p_varietyIndex) . ",
                           variety_authorID = "    . makeInt($p_varauthorIndex) . ",
                           subvarietyID = "        . makeInt($p_subvarietyIndex) . ",
                           subvariety_authorID = " . makeInt($p_subvarauthorIndex) . ",
                           formaID = "             . makeInt($p_formaIndex) . ",
                           forma_authorID = "      . makeInt($p_forauthorIndex) . ",
                           subformaID = "          . makeInt($p_subformaIndex) . ",
                           subforma_authorID = "   . makeInt($p_subforauthorIndex) . ",
                           annotation = "          . quoteString($_POST['annotation']) . ",
                           external = "            . (($p_external) ? 1 : 0) . "
                          WHERE taxonID = '" . intval($_POST['taxonID']) . "'";
                  $result = db_query($sql);
                  logSpecies($p_taxonID,1);
                  if (!$p_external) {
                      // check any used epitheta and authors and make them internal if still external
                      clearExternal('epithets', $p_speciesIndex);
                      clearExternal('authors',  $p_authorIndex);
                      clearExternal('epithets', $p_subspeciesIndex);
                      clearExternal('authors',  $p_subauthorIndex);
                      clearExternal('epithets', $p_varietyIndex);
                      clearExternal('authors',  $p_varauthorIndex);
                      clearExternal('epithets', $p_subvarietyIndex);
                      clearExternal('authors',  $p_subvarauthorIndex);
                      clearExternal('epithets', $p_formaIndex);
                      clearExternal('authors',  $p_forauthorIndex);
                      clearExternal('epithets', $p_subformaIndex);
                      clearExternal('authors',  $p_subforauthorIndex);
                  }
                }
            } else {
                $sql = "INSERT INTO tbl_tax_species SET
                         synID = " . makeInt($p_synIndex) . ",
                         basID = " . makeInt($p_basIndex) . ",
                         tax_rankID = " . makeInt($p_rankIndex) . ",
                         statusID = " . makeInt($p_statusIndex) . ",
                         genID = " . makeInt($p_genIndex) . ",
                         speciesID = " . makeInt($p_speciesIndex) . ",
                         authorID = " . makeInt($p_authorIndex) . ",
                         subspeciesID = " . makeInt($p_subspeciesIndex) . ",
                         subspecies_authorID = " . makeInt($p_subauthorIndex) . ",
                         varietyID = " . makeInt($p_varietyIndex) . ",
                         variety_authorID = " . makeInt($p_varauthorIndex) . ",
                         subvarietyID = " . makeInt($p_subvarietyIndex) . ",
                         subvariety_authorID = " . makeInt($p_subvarauthorIndex) . ",
                         formaID = " . makeInt($p_formaIndex) . ",
                         forma_authorID = " . makeInt($p_forauthorIndex) . ",
                         subformaID = " . makeInt($p_subformaIndex) . ",
                         subforma_authorID = " . makeInt($p_subforauthorIndex) . ",
                         annotation = " . quoteString($_POST['annotation']);
                $result = db_query($sql);
                if ($result) {
                    $p_taxonID = mysql_insert_id();
                    logSpecies($p_taxonID, 0);
                } else {
                    $taxonID = 0;
                }
            }

            if (!empty($_POST['submitUpdateNew'])) {
                $location = "Location: editSpecies.php?sel=<0>&new=1";
                if (SID) $location = $location . "&" . SID;
                Header($location);
            } elseif (!empty($_POST['submitUpdateCopy'])) {
                $location = "Location: editSpecies.php?sel=<" . $p_taxonID . ">&new=1";
                if (SID) $location = $location . "&" . SID;
                Header($location);
            }
            $edit = false;
        } else {
            $edit = ($_POST['edit']) ? true : false;
            $p_taxonID = $_POST['taxonID'];
        }
    } else if (!empty($_POST['submitNewCopy'])) {
        $p_taxonID = "";
        $edit = false;
    } else {
        $edit = (!empty($_POST['reload']) && !empty($_POST['edit'])) ? true : false;
        $p_taxonID = $_POST['taxonID'];
    }
}

$comnames='';
$sql="
SELECT
 com.common_name as 'common_name'
FROM
 {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_applies_to a
 LEFT JOIN {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_entities ent ON ent.entity_id = a.entity_id
 LEFT JOIN {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_taxon tax ON tax.taxon_id = ent.entity_id
 
 LEFT JOIN {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_names nam ON  nam.name_id = a.name_id
 LEFT JOIN {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_commons com ON  com.common_id = nam.name_id
WHERE
 a.entity_id = ent.entity_id and ent.entity_id = tax.taxon_id  and tax.taxonID='{$p_taxonID}'
LIMIT 5 
";

$result = db_query($sql);
while($row = mysql_fetch_array($result)){
	$comnames.=", ".$row['common_name'];
}
$comnames="&nbsp;".substr($comnames,2);

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - edit Species</title>
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
    reload = false;

    function editGenera(sel) {
      target = "editGenera.php?update=1&sel=" + encodeURIComponent(sel.value);
      MeinFenster = window.open(target,"editGenera","width=600,height=500,top=50,left=50,scrollbars=yes,resizable=yes");
      MeinFenster.focus();
    }
    function editEpithet(sel,typ) {
      target = "editEpithet.php?sel=" + encodeURIComponent(sel.value) + "&typ=" + typ;
      MeinFenster = window.open(target,"editEpithet","width=300,height=130,top=50,left=50,scrollbars=yes,resizable=yes");
      MeinFenster.focus();
    }
    function editAuthor(sel,typ) {
      target = "editAuthor.php?sel=" + encodeURIComponent(sel.value) + "&typ=" + typ;
      MeinFenster = window.open(target,"editAuthor","width=500,height=200,top=50,left=50,scrollbars=yes,resizable=yes");
      MeinFenster.focus();
    }
    function editHybrids(sel) {
      target  = "editHybrids.php?ID=" + encodeURIComponent(sel);
      MeinFenster = window.open(target,"editHybrids","width=900,height=220,top=50,left=50,scrollbars=yes,resizable=yes");
      MeinFenster.focus();
    }
    function searchAuthor() {
      MeinFenster = window.open("searchAuthor.php","searchAuthor","scrollbars=yes,resizable=yes");
      MeinFenster.focus();
    }
    function taxIndex(sel) {
      target  = "listIndex.php?t=1&ID=" + encodeURIComponent(sel);
      options = "width=800,height=400,top=50,left=50,scrollbars=yes,resizable=yes";
      MeinFenster = window.open(target,"listIndex",options);
      MeinFenster.focus();
    }
    function taxType(sel) {
      target  = "listType.php?ID=" + encodeURIComponent(sel);
      options = "width=800,height=400,top=50,left=50,scrollbars=yes,resizable=yes";
      MeinFenster = window.open(target,"listType",options);
      MeinFenster.focus();
    }
    function listSynonyms(sel) {
      target  = "listSynonyms.php?ID=" + encodeURIComponent(sel);
      options = "width=800,height=400,top=50,left=50,scrollbars=yes,resizable=yes";
      MeinFenster = window.open(target,"listSynonyms",options);
      MeinFenster.focus();
    }
    function listTaxSynonymy(sel) {
      target  = "listTaxSynonymy.php?ID=" + encodeURIComponent(sel);
      options = "width=800,height=400,top=50,left=50,scrollbars=yes,resizable=yes";
      MeinFenster = window.open(target,"listTaxSynonymy",options);
      MeinFenster.focus();
    }
    function listTypeSpecimens(sel) {
      target  = "listTypeSpecimens.php?ID=" + encodeURIComponent(sel);
      options = "width=";
      if (screen.availWidth<990)
        options += (screen.availWidth - 10) + ",height=";
      else
        options += "990, height=";
      if (screen.availHeight<710)
        options += (screen.availHeight - 10);
      else
        options += "710";
      options += ", top=10,left=10,scrollbars=yes,resizable=yes";
      MeinFenster = window.open(target,"Specimens",options);
      MeinFenster.focus();
    }

    function reloadButtonPressed() {
      reload = true;
    }
    function checkMandatory() {
      var missing = 0;
      var text = "";
      var outtext = "";

      if (reload==true) return true;

      if (parseInt(document.f.genIndex.value)==0) {
        missing++; text += "Genus\n";
      }
      if (parseInt(document.f.authorIndex.value)==0) {
        if (document.f.statusIndex.value!=1) {
          missing++; text += "Author\n";
        }
      }

      if (missing>0) {
        if (missing>1)
          outtext = "The following " + missing + " entries are missing or invalid:\n";
        else
          outtext = "The following entry is missing or invalid:\n";
        alert (outtext + text);
        return false;
      }
      else
        return true;
    }
	function editCommonNames(sel) {
		target  = "editCommonName.php?enableClose=1&search=1&show=1&taxonID="+sel;
		options = "width=900,height=700,top=50,left=50,scrollbars=yes,resizable=yes";
		MeinFenster = window.open(target,"edit Common Names",options);
		MeinFenster.focus();
	}	
	
  </script>
</head>

<body>

<?php if ($blocked): ?>
<script type="text/javascript" language="JavaScript">
  alert('You have no sufficient rights for the desired operation');
</script>
<?php endif; ?>

<form onSubmit="return checkMandatory()" Action="<?php echo $_SERVER['PHP_SELF']; ?>" Method="POST" name="f">

<?php
/*
SELECT tbl_tax_species.taxonID,
  [genus]
  & IIf([speciesID] Like "*"," " & [tbl_tax_epithets].[epithet] & " " &
        [tbl_tax_authors].[author])
  & IIf([subspeciesID] Like "*"," subsp. " & [tbl_tax_epithets_1].[epithet] & " " &
        [tbl_tax_authors_1].[author])
  & IIf([varietyID] Like "*"," var. " & [tbl_tax_epithets_2].[epithet] & " " &
        [tbl_tax_authors_2].[author])
  & IIf([formaID] Like "*"," forma " & [tbl_tax_epithets_3].[epithet] & " " &
        [tbl_tax_authors_3].[author])
  AS taxon,
  tbl_tax_status.status,
  tbl_tax_species.genID,
  tbl_tax_species.synID,
  tbl_tax_families.family,
  tbl_tax_genera.[DallaTorreIDs] & [DallaTorreZusatzIDs] AS dtnumber
FROM tbl_tax_epithets
  RIGHT JOIN (tbl_tax_authors
    RIGHT JOIN (tbl_tax_families
      RIGHT JOIN (tbl_tax_genera
        RIGHT JOIN ((tbl_tax_authors AS tbl_tax_authors_3
          RIGHT JOIN (tbl_tax_authors AS tbl_tax_authors_2
            RIGHT JOIN (tbl_tax_authors AS tbl_tax_authors_1
              RIGHT JOIN (tbl_tax_epithets AS tbl_tax_epithets_3
                RIGHT JOIN (tbl_tax_epithets AS tbl_tax_epithets_2
                  RIGHT JOIN (tbl_tax_epithets AS tbl_tax_epithets_1
                    RIGHT JOIN tbl_tax_species ON
                      tbl_tax_epithets_1.epithetID = tbl_tax_species.subspeciesID)
                  ON tbl_tax_epithets_2.epithetID = tbl_tax_species.varietyID)
                ON tbl_tax_epithets_3.epithetID = tbl_tax_species.formaID)
              ON tbl_tax_authors_1.authorID = tbl_tax_species.subspecies_authorID)
            ON tbl_tax_authors_2.authorID = tbl_tax_species.variety_authorID)
          ON tbl_tax_authors_3.authorID = tbl_tax_species.forma_authorID)
          LEFT JOIN tbl_tax_status ON tbl_tax_species.statusID = tbl_tax_status.statusID)
        ON tbl_tax_genera.genID = tbl_tax_species.genID)
      ON tbl_tax_families.familyID = tbl_tax_genera.familyID)
    ON tbl_tax_authors.authorID = tbl_tax_species.authorID)
  ON tbl_tax_epithets.epithetID = tbl_tax_species.speciesID
ORDER BY
[genus]
& IIf([speciesID] Like "*"," " & [tbl_tax_epithets].[epithet] & " " &
      [tbl_tax_authors].[author])
& IIf([subspeciesID] Like "*"," subsp. " & [tbl_tax_epithets_1].[epithet] & " " &
      [tbl_tax_authors_1].[author])
& IIf([varietyID] Like "*"," var. " & [tbl_tax_epithets_2].[epithet] & " " &
      [tbl_tax_authors_2].[author])
& IIf([formaID] Like "*"," forma " & [tbl_tax_epithets_3].[epithet] & " " &
      [tbl_tax_authors_3].[author]);
*/

unset($rank);
$sql = "SELECT rank, tax_rankID FROM tbl_tax_rank ORDER BY rank";
if ($result = db_query($sql)) {
    if (mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_array($result)) {
            $rank[0][] = intval($row['tax_rankID']);
            $rank[1][] = $row['rank'];
        }
    }
}

unset($status);
$sql = "SELECT status, statusID FROM tbl_tax_status ORDER BY status";
if ($result = db_query($sql)) {
    if (mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_array($result)) {
            $status[0][] = intval($row['statusID']);
            $status[1][] = $row['status'];
        }
    }
}

// Header 1: Status und Taxon
$sql = "SELECT ts.taxonID, tg.genus, tg.DallaTorreIDs, tg.DallaTorreZusatzIDs, tst.status,
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
         LEFT JOIN tbl_tax_status tst ON tst.statusID = ts.statusID
         LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
        WHERE taxonID = '" . mysql_escape_string($p_taxonID) . "'";
$result = db_query($sql);
if (mysql_num_rows($result) > 0) {
    $row = mysql_fetch_array($result);
    $display_head1 = $row['status'] . "&nbsp;&nbsp;" . taxon($row, false, false);
} else {
    $display_head1 = "";
}

// Header 2: Literaturverweis, wenn nur einer vorhanden
$sql ="SELECT paginae, figures,
        l.suptitel, le.autor as editor, la.autor, l.periodicalID, lp.periodical,
        l.vol, l.part, l.jahr
       FROM tbl_tax_index ti
        LEFT JOIN tbl_lit l ON l.citationID = ti.citationID
        LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID = l.periodicalID
        LEFT JOIN tbl_lit_authors le ON le.autorID = l.editorsID
        LEFT JOIN tbl_lit_authors la ON la.autorID = l.autorID
       WHERE taxonID = '" . mysql_escape_string($p_taxonID) . "'";
$result = db_query($sql);
if (mysql_num_rows($result) > 0) {
    if (mysql_num_rows($result) == 1) {
        $row = mysql_fetch_array($result);
        $display_head2 = $row['autor'] . " (" . substr($row['jahr'], 0, 4) . ")";
        if ($row['suptitel']) $display_head2 .= " in " . $row['editor'] . ": " . $row['suptitel'];
        if ($row['periodicalID']) $display_head2 .= " " . $row['periodical'];
        $display_head2 .= " " . $row['vol'];
        if ($row['part']) $display_head2 .= " (" . $row['part'] . ")";
        $display_head2 .= ": ".$row['paginae'] . ". " . $row['figures'];
    } else {
        $display_head2 = "multi";
    }
} else {
    $display_head2 = "&mdash;";
}

// Header 3: Typus, wenn nur einer vorhanden
$sql ="SELECT Sammler, Sammler_2, series, leg_nr, alternate_number, date, duplicates
       FROM (tbl_tax_typecollections tt, tbl_collector c)
        LEFT JOIN tbl_collector_2 c2 ON tt.Sammler_2ID = c2.Sammler_2ID
       WHERE tt.SammlerID = c.SammlerID
        AND taxonID = '" . mysql_escape_string($p_taxonID) . "'";
$result = db_query($sql);
if (mysql_num_rows($result) > 0) {
    if (mysql_num_rows($result) == 1) {
        $row=mysql_fetch_array($result);
        $display_head3 = $row['Sammler'];
        if ($row['Sammler_2']) {
            if (strstr($row['Sammler_2'], "&") === false) {
                $display_head3 .= " & " . $row['Sammler_2'];
            } else {
                $display_head3 .= " et al.";
            }
        }
        if ($row['series']) $display_head3 .= " " . $row['series'];
        if ($row['leg_nr']) $display_head3 .= " " . $row['leg_nr'];
        if ($row['alternate_number']) {
            $display_head3 .= " " . $row['alternate_number'];
            if (strstr($row['alternate_number'],"s.n.") !== false) {
                $display_head3 .= " [" . $row['date'] . "]";
            }
        }
        $display_head3 .= "; " . $row['duplicates'];
    } else {
        $display_head3 = "multi";
    }
} else {
    if (intval($p_basIndex)!="NULL") {
        $sql ="SELECT Sammler, Sammler_2, series, leg_nr, alternate_number, date, duplicates
               FROM (tbl_tax_typecollections tt, tbl_collector c)
                LEFT JOIN tbl_collector_2 c2 ON tt.Sammler_2ID=c2.Sammler_2ID
               WHERE tt.SammlerID = c.SammlerID
                AND taxonID = " . intval($p_basIndex);
        $result = db_query($sql);
        if (mysql_num_rows($result) > 0) {
            $display_head3 = "&rarr; Basionym"; // "-> Basionym" wenn im Basionym mindestens ein Typus eingetragen ist
        } else {
            $display_head3 = "&mdash;";
        }
    } else {
        $display_head3 = "&mdash;";
    }
}

if ($nr) {
    echo "<div style=\"position: absolute; left: 1em; top: 0.4em;\">";
    if ($nr > 1) {
        echo "<a href=\"editSpecies.php?sel=" . htmlentities("<" . $linkList[$nr-1] . ">") . "&nr=" . ($nr-1) . "\">".
             "<img border=\"0\" height=\"22\" src=\"webimages/left.gif\" width=\"20\">".
             "</a>";
    } else {
        echo "<img border=\"0\" height=\"22\" src=\"webimages/left_gray.gif\" width=\"20\">";
    }
    echo "</div>\n";
    echo "<div style=\"position: absolute; left: 2.5em; top: 0.4em;\">";
    if ($nr<$linkList[0]) {
        echo "<a href=\"editSpecies.php?sel=" . htmlentities("<" . $linkList[$nr+1] . ">") . "&nr=" . ($nr+1) . "\">".
             "<img border=\"0\" height=\"22\" src=\"webimages/right.gif\" width=\"20\">".
             "</a>";
    } else {
        echo "<img border=\"0\" height=\"22\" src=\"webimages/right_gray.gif\" width=\"20\">";
    }
    echo "</div>\n";
}

$cf = new CSSF();

echo "<input type=\"hidden\" name=\"taxonID\" value=\"$p_taxonID\">\n";
if ($p_taxonID) {
    if ($edit) {
        echo "<input type=\"hidden\" name=\"edit\" value=\"$edit\">\n";
        $text = "<span style=\"background-color: #66FF66\">&nbsp;<b>$p_taxonID</b>&nbsp;</span>";
    } else {
        $text = $p_taxonID;
    }
} else {
    $text = "<span style=\"background-color: #66FF66\">&nbsp;<b>new</b>&nbsp;</span>";
}
$cf->label(9, 0.5, "taxonID");
$cf->text(9, 0.5, "&nbsp;" . $text);

if ($p_taxonID) {
    $cf->text(9 + strlen($p_taxonID), 0.5, $display_head1);
    $cf->text(9 + strlen($p_taxonID), 2, $display_head2);
    $cf->text(9 + strlen($p_taxonID), 3.5, $display_head3);
}
$cf->label(9, 2, "edit Index", "javascript:taxIndex('$p_taxonID')");
$cf->label(9, 3.5, "edit Type", "javascript:taxType('$p_taxonID')");
$cf->label(9, 5, "Common Names", "javascript:editCommonNames('$p_taxonID')");

$cf->text(9, 5, $comnames);

$res = mysql_query("SELECT specimens_types_ID FROM tbl_specimens_types WHERE taxonID = '$p_taxonID'");
if (mysql_num_rows($res) > 0) {
    $cf->label(22.5, 5.5, 'type specimens', "javascript:listTypeSpecimens('$p_taxonID')");
}

if ($p_external) {
    $cf->label(59, 5.5, "external");
    $cf->checkbox(59, 5.5, "external", $p_external);
}

$cf->labelMandatory(9, 7.5, 6, "Genus", "javascript:editGenera(document.f.genIndex)");
$cf->inputJqAutocomplete(9, 7.5, 51, "gen", $p_gen, $p_genIndex, "index_jq_autocomplete.php?field=genus", 650, 2);

$cf->labelMandatory(9, 10.5, 6, "Rank");
$cf->dropdown(9, 10.5, "rankIndex", $p_rankIndex, $rank[0], $rank[1]);

if ($p_statusIndex == 1) {
    $cf->labelMandatory(36, 10.5, 6, "parents", "javascript:editHybrids($p_taxonID)");
} else {
    $cf->labelMandatory(36, 10.5, 6, "tax. Status");
}
$cf->dropdown(36, 10.5, "statusIndex", $p_statusIndex, $status[0], $status[1]);
//if ($p_statusIndex == 96 || $p_statusIndex == 97 || $p_statusIndex == 103 || $p_statusIndex == 1) {
    $cf->label(30, 10.5, "list synonyms", "javascript:listSynonyms($p_taxonID)");
//}

$cf->labelMandatory(9, 13.5, 6, "Species", "javascript:editEpithet(document.f.speciesIndex,'e')");
$cf->inputJqAutocomplete(9, 13.5, 20, "species", $p_species, $p_speciesIndex, "index_jq_autocomplete.php?field=epithetNoExternals", 650, 2);
$cf->labelMandatory(40, 13.5, 6, "Author", "javascript:editAuthor(document.f.authorIndex,'a')");
$cf->inputJqAutocomplete(40, 13.5, 20, "author", $p_author, $p_authorIndex, "index_jq_autocomplete.php?field=taxAuthorNoExternals",650, 2);
$cf->label(40, 15, "search", "javascript:searchAuthor()");

$cf->label(9, 17, "Subspecies","javascript:editEpithet(document.f.subspeciesIndex,'s')");
$cf->inputJqAutocomplete(9, 17, 20, "subspecies", $p_subspecies, $p_subspeciesIndex, "index_jq_autocomplete.php?field=epithetNoExternals", 650, 2);
$cf->label(40, 17, "Author","javascript:editAuthor(document.f.subauthorIndex,'s')");
$cf->inputJqAutocomplete(40, 17, 20, "subauthor", $p_subauthor, $p_subauthorIndex, "index_jq_autocomplete.php?field=taxAuthorNoExternals", 650, 2);

$cf->label(9, 20, "Variety","javascript:editEpithet(document.f.varietyIndex,'v')");
$cf->inputJqAutocomplete(9, 20, 20, "variety", $p_variety, $p_varietyIndex, "index_jq_autocomplete.php?field=epithetNoExternals", 650, 2);
$cf->label(40, 20, "Author","javascript:editAuthor(document.f.varauthorIndex,'v')");
$cf->inputJqAutocomplete(40, 20, 20, "varauthor", $p_varauthor, $p_varauthorIndex, "index_jq_autocomplete.php?field=taxAuthorNoExternals", 650, 2);

$cf->label(9, 23, "Subvariety","javascript:editEpithet(document.f.subvarietyIndex,'sv')");
$cf->inputJqAutocomplete(9, 23, 20, "subvariety", $p_subvariety, $p_subvarietyIndex, "index_jq_autocomplete.php?field=epithetNoExternals", 650, 2);
$cf->label(40 ,23, "Author","javascript:editAuthor(document.f.subvarauthorIndex,'sv')");
$cf->inputJqAutocomplete(40, 23, 20, "subvarauthor", $p_subvarauthor, $p_subvarauthorIndex, "index_jq_autocomplete.php?field=taxAuthorNoExternals", 650, 2);

$cf->label(9, 26, "Forma","javascript:editEpithet(document.f.formaIndex,'f')");
$cf->inputJqAutocomplete(9, 26, 20, "forma", $p_forma, $p_formaIndex, "index_jq_autocomplete.php?field=epithetNoExternals", 650, 2);
$cf->label(40, 26, "Author","javascript:editAuthor(document.f.forauthorIndex,'f')");
$cf->inputJqAutocomplete(40, 26, 20, "forauthor", $p_forauthor, $p_forauthorIndex, "index_jq_autocomplete.php?field=taxAuthorNoExternals", 650, 2);

$cf->label(9, 29, "Subforma", "javascript:editEpithet(document.f.subformaIndex,'sf')");
$cf->inputJqAutocomplete(9, 29, 20, "subforma", $p_subforma, $p_subformaIndex, "index_jq_autocomplete.php?field=epithetNoExternals", 650, 2);
$cf->label(40, 29, "Author","javascript:editAuthor(document.f.subforauthorIndex,'sf')");
$cf->inputJqAutocomplete(40, 29, 20, "subforauthor", $p_subforauthor, $p_subforauthorIndex, "index_jq_autocomplete.php?field=taxAuthorNoExternals", 650, 2);

$cf->label(9, 32, "accepted Taxon", "javascript:listTaxSynonymy('$p_taxonID');");
$cf->inputJqAutocomplete(9, 32, 51, "syn", $p_syn, $p_synIndex, "index_jq_autocomplete.php?field=taxonWithDT", 650, 2);
$cf->label(5, 33.2, "<font size=\"+1\"><b>&laquo;</b></font>", "javascript:history.back()");
if ($p_synIndex) {
    $cf->label(9, 33.5, "link","editSpecies.php?sel=" . htmlspecialchars("<$p_synIndex>"));
}

$cf->label(9, 35.5, "Basionym");
$cf->inputJqAutocomplete(9, 35.5, 51, "bas", $p_bas, $p_basIndex, "index_jq_autocomplete.php?field=taxonWithDT", 650, 2);
$cf->label(5, 36.7, "<font size=\"+1\"><b>&laquo;</b></font>", "javascript:history.back()");
if ($p_basIndex) {
    $cf->label(9, 37, "link","editSpecies.php?sel=" . htmlspecialchars("<$p_basIndex>"));
}

$cf->label(9, 39, "annotations");
$cf->textarea(9, 39, 51, 9.6, "annotation", $p_annotation);

$cf->buttonSubmit(16, 50, "reload", " Reload \" onclick=\"reloadButtonPressed()");

if (($_SESSION['editControl'] & 0x1) != 0) {
    if ($p_taxonID) {
        if ($edit) {
            $cf->buttonJavaScript(22, 50, " Reset ", "self.location.href='editSpecies.php?sel=<" . $p_taxonID . ">&edit=1'");
            $cf->buttonSubmit(31, 5, "submitUpdate", " Update ");
        } else {
            $cf->buttonJavaScript(22, 50, " Reset ", "self.location.href='editSpecies.php?sel=<" . $p_taxonID . ">'");
            $cf->buttonJavaScript(31, 50, " Edit ", "self.location.href='editSpecies.php?sel=<" . $p_taxonID . ">&edit=1'");
        }
        $cf->buttonSubmit(47, 50, "submitNewCopy", " New &amp; Copy");
    } else {
        $cf->buttonReset(22, 50, " Reset ");
        $cf->buttonSubmit(31, 50, "submitUpdate", " Insert ");
        $cf->buttonSubmit(37, 50, "submitUpdateCopy", " Insert &amp; Copy");
        $cf->buttonSubmit(47, 50, "submitUpdateNew", " Insert &amp; New");
    }
}
$cf->buttonJavaScript(2, 50, " < Taxonomy ", "self.location.href='listTax.php?nr=$nr'");
?>
</form>

</div>
</body>
</html>