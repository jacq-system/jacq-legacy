<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");
require("inc/log_functions.php");

no_magic();

require_once ("inc/xajax/xajax_core/xajax.inc.php");

$xajax = new xajax();
$xajax->setRequestURI("ajax/editLitServer.php");

$xajax->registerFunction("listLib");
$xajax->registerFunction("listContainer");
$xajax->registerFunction("editContainer");
$xajax->registerFunction("updateContainer");
$xajax->registerFunction("deleteContainer");
$xajax->registerFunction("addClassification");
$xajax->registerFunction("deleteClassification");
$xajax->registerFunction("listClassifications");
$xajax->registerFunction("searchClassifications");

if (!isset($_SESSION['liLinkList'])) $_SESSION['liLinkList'] = '';

$nr = (isset($_GET['nr'])) ? intval($_GET['nr']) : 0;
$linkList = $_SESSION['liLinkList'];



function rom2arab ($r)
{
    $r = strtolower($r);
    $f = "mdclxvi";

    if (strlen($r) == 1) {
        switch ($r) {
            case 'i': return    1; break;
            case 'v': return    5; break;
            case 'x': return   10; break;
            case 'l': return   50; break;
            case 'c': return  100; break;
            case 'd': return  500; break;
            case 'm': return 1000; break;
        }
    } elseif (strlen($r) == 0) {
        return 0;
    } else {
        for ($i = 0; $i < strlen($f); $i++) {
            for ($j = 0; $j < strlen($r); $j++) {
                if (substr($r, $j, 1) == substr($f, $i, 1)) {
                    $p = $j;
                    $z = substr($f, $i, 1);
                    break 2;
                }
            }
        }
        return rom2arab($z) - rom2arab(substr($r, 0, $p)) + rom2arab(substr($r, $p + 1));
    }
}

function parsePp ($pp)
{
    $exclude = "[],;. -";
    $roman   = "mdclxvi";

    $result = '';
    $part = '';
    for ($i = 0; $i < strlen($pp); $i++) {
        $needle = substr($pp, $i, 1);
        if (strpos($exclude, $needle) === false) {
            $part .= $needle;
        } else {
            if ($part) {
                if (strpos($roman, substr($part, 0, 1)) !== false) {
                    $result .= sprintf('-%04d', rom2arab($part));
                } else {
                    $result .= sprintf('%05d', $part);
                }
                $part = '';
            }
        }
    }
    if ($part) {
        if (strpos($roman, substr($part, 0, 1)) !== false) {
            $result .= sprintf('-%04d', rom2arab($part));
        } else {
            $result .= sprintf('%05d', $part);
        }
    }

    return $result;
}

// main program

if (isset($_GET['sel']) && extractID($_GET['sel']) != "NULL") {
    $sql = "SELECT tl.citationID, tl.jahr, tl.code, tl.titel, tl.suptitel, tl.vol,
             tl.part, tl.pp, tl.verlagsort, tl.keywords, tl.annotation, tl.additions,
             tl.bestand, tl.signature, tl.publ, tl.category, tl.lit_url,
             ta.autor, ta.autorID ,te.autor editor, te.autorID editorID,
             tpe.periodical, tpe.periodicalID, tpu.publisher, tpu.publisherID
            FROM tbl_lit tl
             LEFT JOIN tbl_lit_authors ta ON ta.autorID = tl.autorID
             LEFT JOIN tbl_lit_authors te ON te.autorID = tl.editorsID
             LEFT JOIN tbl_lit_periodicals tpe ON tpe.periodicalID = tl.periodicalID
             LEFT JOIN tbl_lit_publishers tpu ON tpu.publisherID = tl.publisherID
            WHERE citationID = " . extractID($_GET['sel']);
    $result = db_query($sql);
    if (mysql_num_rows($result) > 0) {
        $row = mysql_fetch_array($result);
        $p_citationID = $row['citationID'];
        $p_jahr       = $row['jahr'];
        $p_code       = $row['code'];
        $p_titel      = $row['titel'];
        $p_suptitel   = $row['suptitel'];
        $p_vol        = $row['vol'];
        $p_part       = $row['part'];
        $p_pp         = $row['pp'];
        $p_verlagsort = $row['verlagsort'];
        $p_keywords   = $row['keywords'];
        $p_annotation = $row['annotation'];
        $p_additions  = $row['additions'];
        $p_bestand    = $row['bestand'];
        $p_signature  = $row['signature'];
        $p_publ       = $row['publ'];
        $p_category   = $row['category'];
        $p_url        = $row['lit_url'];
        $p_autor      = $row['autor'] . " <" . $row['autorID'] . ">";
        $p_editor     = ($row['editor']) ? $row['editor'] . " <" . $row['editorID'] . ">" : "";
        $p_periodical = ($row['periodical']) ? $row['periodical'] . " <" . $row['periodicalID'] . ">" : "";
        $p_publisher  = ($row['publisher']) ? $row['publisher'] . " <" . $row['publisherID'] . ">" : "";
		
		$p_autorIndex      =  $row['autorID'];
		$p_editorIndex     = $row['editorID'];
        $p_periodicalIndex = $row['periodicalID'];
        $p_publisherIndex  = $row['publisherID'];
    } else {
        $p_citationID = $p_jahr = $p_code = $p_titel = $p_suptitel = $p_vol = $p_part = "";
        $p_pp = $p_verlagsort = $p_keywords = $p_annotation = $p_additions = $p_bestand = "";
        $p_signature = $p_publ = $p_category = $p_autor = $p_editor = $p_periodical = "";
        $p_publisher = $p_url = "";
		$p_autorIndex =  $p_editorIndex =  $p_periodicalIndex = $p_publisherIndex  = 0;
    }
    if (isset($_GET['new']) && $_GET['new'] == 1) $p_citationID = "";
    $edit = (!empty($_GET['edit'])) ? true : false;
} else {
    $p_jahr       = $_POST['jahr'];
    $p_code       = $_POST['code'];
    $p_titel      = $_POST['titel'];
    $p_suptitel   = $_POST['suptitel'];
    $p_vol        = $_POST['vol'];
    $p_part       = $_POST['part'];
    $p_pp         = $_POST['pp'];
    $p_verlagsort = $_POST['verlagsort'];
    $p_keywords   = $_POST['keywords'];
    $p_annotation = $_POST['annotation'];
    $p_additions  = $_POST['additions'];
    $p_bestand    = $_POST['bestandIndex'];
    $p_signature  = $_POST['signature'];
    $p_publ       = (!empty($_POST['publ'])) ? 1 : 0;
    $p_category   = $_POST['categoryIndex'];
    $p_url        = $_POST['url'];
    $p_autor      = $_POST['autor'];
    $p_editor     = $_POST['editor'];
    $p_periodical = $_POST['periodical'];
    $p_publisher  = $_POST['publisher'];
	
	$p_autorIndex      = $_POST['autorIndex'];
	$p_editorIndex     = $_POST['editorIndex'];
	$p_periodicalIndex = $_POST['periodicalIndex'];
	$p_publisherIndex  = $_POST['publisherIndex'];
		
    if ((!empty($_POST['submitUpdate']) || !empty($_POST['submitUpdateNew']) || !empty($_POST['submitUpdateCopy'])) && (($_SESSION['editControl'] & 0x20) != 0)) {
        if (intval($_POST['citationID'])) {
            $sql = "UPDATE tbl_lit SET
                     lit_url = " . quoteString($p_url) . ",
                     autorID = " . quoteString($p_autorIndex) . ",
                     jahr = " . quoteString($p_jahr) . ",
                     code = " . quoteString($p_code) . ",
                     titel = " . quoteString($p_titel) . ",
                     suptitel = " . quoteString($p_suptitel) . ",
                     editorsID = " . quoteString($p_editorIndex) . ",
                     periodicalID = " .quoteString($p_periodicalIndex) . ",
                     vol = " . quoteString($p_vol) . ",
                     part = " . quoteString($p_part) . ",
                     pp = " . quoteString($p_pp) . ",
                     ppSort = " . quoteString(parsePp($p_pp)) . ",
                     publisherID = " . quoteString($p_publisherIndex) . ",
                     verlagsort = " . quoteString($p_verlagsort) . ",
                     keywords = " . quoteString($p_keywords) . ",
                     annotation = " . quoteString($p_annotation) . ",
                     additions = " . quoteString($p_additions) . ",
                     bestand = " . quoteString($p_bestand) . ",
                     signature = " . quoteString($p_signature) . ",
                     publ = " . (($p_publ) ? "'X'" : "NULL") . ",
                     category = " . quoteString($p_category) . "
                    WHERE citationID = '" . intval($_POST['citationID']) . "'";
            $updated = 1;
        } else {
            $sql = "INSERT INTO tbl_lit SET
                     lit_url = " . quoteString($p_url) . ",
                     autorID = " . quoteString($p_autorIndex) . ",
                     jahr = " . quoteString($p_jahr) . ",
                     code = " . quoteString($p_code) . ",
                     titel = " . quoteString($p_titel) . ",
                     suptitel = " . quoteString($p_suptitel) . ",
                     editorsID = " . quoteString($p_editorIndex) . ",
                     periodicalID = " . quoteString($p_periodicalIndex) . ",
                     vol = " . quoteString($p_vol) . ",
                     part = " . quoteString($p_part) . ",
                     pp = " . quoteString($p_pp) . ",
                     ppSort = " . quoteString(parsePp($p_pp)) . ",
                     publisherID = " . quoteString($p_publisherIndex) . ",
                     verlagsort = " . quoteString($p_verlagsort) . ",
                     keywords = " . quoteString($p_keywords) . ",
                     annotation = " . quoteString($p_annotation) . ",
                     additions = " . quoteString($p_additions) . ",
                     bestand = " . quoteString($p_bestand) . ",
                     signature = " . quoteString($p_signature) . ",
                     publ = " . (($p_publ) ? "'X'" : "NULL") . ",
                     category = " . quoteString($p_category);
            $updated = 0;
        }
        $result = db_query($sql);
        $p_citationID = (intval($_POST['citationID'])) ? intval($_POST['citationID']) : mysql_insert_id();
        logLit($p_citationID, $updated);
        if ($_POST['submitUpdateNew']) {
            $location = "Location: editLit.php?sel=<0>&new=1";
            if (SID) $location .= "&" . SID;
            Header($location);
        } elseif ($_POST['submitUpdateCopy']) {
            $location = "Location: editLit.php?sel=<" . $p_citationID . ">&new=1";
            if (SID) $location .= "&" . SID;
            Header($location);
        }
        $edit = false;
    } else if (!empty($_POST['submitNewCopy'])) {
        $p_citationID = "";
        $edit = false;
    } else {
        $edit = ($_POST['reload'] && !empty($_POST['edit'])) ? true : false;
        $p_citationID = $_POST['citationID'];
    }
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - edit Literature</title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <link rel="stylesheet" type="text/css" href="js/lib/jQuery/css/ui-lightness/jquery-ui.custom.css">
  <link rel="stylesheet" href="inc/jQuery/jquery_autocompleter_freud.css" type="text/css" />


  
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
  <?php $xajax->printJavascript('inc/xajax'); ?>
  <script src="js/lib/jQuery/jquery.min.js" type="text/javascript"></script>
  <script src="js/lib/jQuery/jquery-ui.custom.min.js" type="text/javascript"></script>
  <script type="text/javascript" src="js/jquery_autocompleter_freud.js"></script>
 
  <script type="text/javascript" language="JavaScript">
    var reload = false;

    function editAuthor(sel,typ) {
      target = "editLitAuthor.php?sel=" + encodeURIComponent(sel) + "&typ=" + typ;
      MeinFenster = window.open(target,"editLitAuthor","width=500,height=200,top=50,left=50,scrollbars=yes,resizable=yes");
      MeinFenster.focus();
    }
    function editPeriodical(sel) {
      target = "editLitPeriodical.php?sel=" + encodeURIComponent(sel);
      MeinFenster = window.open(target,"editLitPeriodical","width=600,height=550,top=50,left=50,scrollbars=yes,resizable=yes");
      MeinFenster.focus();
    }
    function editPublisher(sel) {
      target = "editLitPublisher.php?sel=" + encodeURIComponent(sel);
      MeinFenster = window.open(target,"editLitPublisher","width=500,height=200,top=50,left=50,scrollbars=yes,resizable=yes");
      MeinFenster.focus();
    }
    function searchAuthor() {
      MeinFenster = window.open("searchLitAuthor","searchLitAuthor","scrollbars=yes,resizable=yes");
      MeinFenster.focus();
    }
    function taxIndex(sel) {
      target  = "listIndex.php?c=1&ID=" + encodeURIComponent(sel);
      options = "width=800,height=400,top=50,left=50,scrollbars=yes,resizable=yes";
      MeinFenster = window.open(target,"listIndex",options);
      MeinFenster.focus();
    }
    function taxa(sel) {
      target  = "listLitTaxa.php?ID=" + encodeURIComponent(sel);
      options = "width=800,height=400,top=50,left=50,scrollbars=yes,resizable=yes";
      MeinFenster = window.open(target,"listLitTaxa",options);
      MeinFenster.focus();
    }
    function persons(sel) {
      target  = "listLitPersons.php?ID=" + encodeURIComponent(sel);
      options = "width=800,height=400,top=50,left=50,scrollbars=yes,resizable=yes";
      MeinFenster = window.open(target,"listLitPersons",options);
      MeinFenster.focus();
    }

    function reloadButtonPressed() {
      reload = true;
    }
	// todo: check it....
    function checkMandatory() {
      var missing = 0;
      var text = "";
      var outtext = "";

      if (reload==true) return true;

      if (document.f.jahr.value.length==0) {
        missing++; text += "year\n";
      }
      if (document.f.categoryIndex.value.length==0) {
        missing++; text += "categories\n";
      }
      if (document.f.autorIndex.value.length==0) {
        missing++; text += "author{s}\n";
      }
      if (document.f.periodicalIndex.value.length==0) {
        missing++; text += "periodical\n";
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
    function call_listLib() {
      xajax_listLib(xajax.getFormValues('f',0,'periodicalIndex'));
    }
    function call_makeAutocompleter(name) {
      $('#' + name).autocomplete ({
        source: 'index_jq_autocomplete.php?field=citation',
        minLength: 2
      });
    }
    $(function() {
        ACFreudInit();
        
        $('#iBox_content').dialog( {
          autoOpen: false,
          modal: true,
          bgiframe: true,
          width: 750,
          height: 600
        } );
        
        $('#edit_tax_classification').dialog( {
            autoOpen: false,
            modal: true,
            width: 750,
            height: 450,
            resizable: false
        } );
        
        <?php
        if( $p_citationID ) {
        ?>
        // Fetch classifications list
        xajax_listClassifications( <?php echo intval($p_citationID); ?> , 0, 1);
        <?php
        }
        ?>
    });
  </script>
</head>

<body onload="call_listLib()">
<div id="iBox_content" style="display:none;"></div>




<?PHP
$cf = new CSSF();

$display = clsDisplay::Load();
$title="Taxon Synonymy<br>".$display->protolog($p_citationID);
$serverParams="&citationID={$p_citationID}";

$searchjs=<<<EOF
function createMapSearchstring(){
	searchString='';
	if($('#ajax_speciesSearch').val().length>0)
		searchString='&genusSearch='+$('#ajax_genusSearch').val()+'&speciesSearch='+$('#ajax_speciesSearch').val();
	else if($('#ajax_genusSearch').val().length>0)
		searchString='&genusSearch='+$('#ajax_genusSearch').val();
	else
		searchString='&mdldSearch='+$('#mdldSearch').val();
	
	return searchString;
}
EOF;

$searchhtml=<<<EOF
<table>
<tr><td>MDLD Search:</td><td><input class="cssftext" style="width: 25em;" type="text" id="mdldSearch" value="" maxlength="200" ></td></tr>
<tr><td>genus Search:</td><td><input tabindex="2" class='cssftextAutocomplete' style='width: 25em;' type="text" value="" name="ajax_genusSearch" id="ajax_genusSearch" maxlength='520' /></td></tr>
<tr><td>Species:</td><td><input tabindex="2" class='cssftextAutocomplete' style='width: 25em;' type="text" value="" name="ajax_speciesSearch" id="ajax_speciesSearch" maxlength='520' /></td></tr>
</table>
<input type="hidden" name="speciesSearchIndex" id="common_nameIndex" value=""/>
<input type="hidden" name="genusSearchIndex" id="common_nameIndex" value=""/>
<script>
ACFreudConfig.push(['index_jq_autocomplete.php?field=taxon','speciesSearch','','0','0','0','2']);
ACFreudConfig.push(['index_jq_autocomplete.php?field=taxon2','genusSearch','','0','0','0','2']);
</script>
EOF;

// only show edit link if we have a valid citation
if( $p_citationID ) {
$cf->inputMapLines(48,2.5,1,'edit TaxSynonymy',$title,'index_jq_autocomplete.php?field=taxon2',
'index_jq_autocomplete.php?field=taxon2','ajax/MapLines_editLit.php',$serverParams,$searchjs,$searchhtml,2);
}



echo<<<EOF
<form onSubmit="return checkMandatory()" name="f" id="f" target="_self" action="{$_SERVER['PHP_SELF']}" method="POST" >
EOF;

if ($nr) {
    echo "<div style=\"position: absolute; left: 13em; top: 0.4em;\">";
    if ($nr>1) {
        echo "<a href=\"editLit.php?sel=" . htmlentities("<" . $linkList[$nr - 1] . ">") . "&nr=" . ($nr - 1) . "\">"
           . "<img border=\"0\" height=\"22\" src=\"webimages/left.gif\" width=\"20\">"
           . "</a>";
    } else {
        echo "<img border=\"0\" height=\"22\" src=\"webimages/left_gray.gif\" width=\"20\">";
    }
    echo "</div>\n";
    echo "<div style=\"position: absolute; left: 15em; top: 0.4em;\">";
    if ($nr<$linkList[0]) {
        echo "<a href=\"editLit.php?sel=" . htmlentities("<" . $linkList[$nr + 1] . ">") . "&nr=" . ($nr + 1) . "\">"
           . "<img border=\"0\" height=\"22\" src=\"webimages/right.gif\" width=\"20\">"
           . "</a>";
    } else {
        echo "<img border=\"0\" height=\"22\" src=\"webimages/right_gray.gif\" width=\"20\">";
    }
    echo "</div>\n";
}



echo "<input type=\"hidden\" name=\"citationID\" value=\"$p_citationID\">\n";
if ($p_citationID) {
    if ($edit) {
        echo "<input type=\"hidden\" name=\"edit\" value=\"$edit\">\n";
        $text = "<span style=\"background-color: #66FF66\">&nbsp;<b>$p_citationID</b>&nbsp;</span>";
    }
    else {
        $text = $p_citationID;
    }

    // only display edit buttons if we have a valid citation
    $cf->label(7, 2.5, "edit Index", "javascript:taxIndex('$p_citationID')");

    $cf->label(13.5, 2.5, "cited taxa ", "javascript:taxa('$p_citationID')");
    $cf->label(21, 2.5, "cited persons ", "javascript:persons('$p_citationID')");

    $cf->label(28.5, 2.5, "list Container", "#\" onclick=\"xajax_listContainer('$p_citationID');");
    $cf->label(37, 2.5, "edit Container", "#\" onclick=\"xajax_editContainer('$p_citationID');");

    // label for editing the classification
    $cf->label(62, 2.5, "edit tax classification", "#\" onclick=\"$('#edit_tax_classification').dialog( 'open' );");
}
else {
    $text = "<span style=\"background-color: #66FF66\">&nbsp;<b>new</b>&nbsp;</span>";
}
$cf->label(7, 0.5, "citationID");
$cf->text(7, 0.5, "&nbsp;" . $text);
		
$cf->labelMandatory(19, 0.5, 3, "date");
$cf->inputText(19, 0.5, 5, "jahr", $p_jahr, 50);
$cf->inputText(25, 0.5, 5, "code", $p_code, 25);
$cf->labelMandatory(40, 0.5, 6, "categories");
//$cf->editDropdown(40, 0.5, 25, "category", $p_category, $category, 50);
$cf->inputJqAutocomplete2(40, 0.5, 25, "category",$p_category,"index_jq_autocomplete.php?field=categories",50,2,'','',1,1);


if ($p_url) {
    $cf->label(7, 4.5, "url", "http://" . $p_url . "\" target=\"_blank");
}
else {
    $cf->label(7, 4.5, "url");
}
$cf->inputText(7, 4.5, 25, "url", $p_url, 255);

$cf->labelMandatory(7, 6.5, 6, "title");
$cf->textarea(7, 6.5, 25, 3.9, "titel", $p_titel);
$cf->label(7, 11.5, "suptitle");
$cf->textarea(7, 11.5, 25, 2.6, "suptitel", $p_suptitel, '', '', true);
$cf->label(40, 6.5, "keywords");
$cf->textarea(40, 6.5, 25, 3.9, "keywords", $p_keywords);
$cf->label(40, 11.5, "annotation");
$cf->textarea(40, 11.5, 25, 2.6, "annotation", $p_annotation);

//$cf->label(7, 12.5, "Geo<br> Specification");

//function inputJqAutocomplete2($x, $y, $w, $name, $index, $serverScript, $maxsize = 0, $minLength=1, $bgcol = "", $title = "",$mustmatch=0, $autoFocus=false,$textarea=0) {
$cf->labelMandatory(7, 16, 6, "author{s}", "javascript:editAuthor('<'+document.f.autorIndex.value+'>','a')");
//$cf->editDropdown(7, 16, 25, "autor", $p_autor, makeAuthor($p_autor, 7, 13), 200);
$cf->inputJqAutocomplete2(7, 16, 25, "autor",$p_autorIndex ,"index_jq_autocomplete.php?field=litAuthor",520,2,'','',1,1);

$cf->label(7, 14.7, "search", "javascript:searchAuthor()");
$cf->label(7, 18.5, "editor{s}", "javascript:editAuthor('<'+document.f.editorIndex.value+'>','e')");
//$cf->editDropdown(7, 21, 25, "editor", $p_editor, makeAuthor($p_editor, 7, 18.5), 200);
//$cf->inputText(7, 21, 25, "editor", $p_editorIndex, 200, '', '', true);
$cf->inputJqAutocomplete2(7, 18.5, 25, "editor",$p_editorIndex,"index_jq_autocomplete.php?field=litAuthor",520,2,'','',1,1);

$cf->labelMandatory(7, 21, 6, "periodical", "javascript:editPeriodical('<'+document.f.periodicalIndex.value+'>')");
//$cf->editDropdown(7, 25.5, 25, "periodical", $p_periodical, makePeriodical($p_periodical, 7, 24), 300, 0, "", "", "call_listLib()");
$cf->inputJqAutocomplete2(7, 21, 25, "periodical", $p_periodicalIndex,"index_jq_autocomplete.php?field=periodical",520,2,'','',1,1);

$cf->inputText(7, 23.5, 2.5, "vol", $p_vol, 20);
$cf->inputText(11, 23.5, 8, "part", $p_part, 50);
$cf->inputText(20.5, 23.5, 11.5, "pp", $p_pp, 150);
$cf->label(7, 26, "printer", "javascript:editPublisher('<'+document.f.publisherIndex.value+'>')");

//$cf->editDropdown(7, 33, 25, "publisher", $p_publisher, makePublisher($p_publisher, 7, 31.5), 120);
$cf->inputJqAutocomplete2(7,26, 25, "publisher",$p_publisherIndex,"index_jq_autocomplete.php?field=publisher",520,2,'','',1,1);

$cf->label(7, 28.5, "printing Loc.");
$cf->inputText(7, 28.5, 25, "verlagsort", $p_verlagsort, 100);

$cf->label(40, 28.5, "additions");
$cf->inputText(40, 28.5, 25, "additions", $p_additions, 500);

$cf->label(7, 31, "listing");
//$cf->editDropdown(7, 39.5, 25, "bestand", $p_bestand, $bestand, 50);
$cf->inputJqAutocomplete2(7, 31, 25, "bestand",$p_bestand,"index_jq_autocomplete.php?field=bestand",520,2,'','',1,1);

$cf->label(44, 31, "recent publication");
$cf->checkbox(44, 31, "publ", $p_publ);
$cf->label(53, 31, "signature");
$cf->inputText(53, 31, 12, "signature", $p_signature, 50);

if (($_SESSION['editControl'] & 0x20) != 0) {
    $cf->buttonSubmit(16, 36, "reload", " Reload \" onclick=\"reloadButtonPressed()");
    if ($p_citationID) {
        if ($edit) {
            $cf->buttonJavaScript(22, 36, " Reset ", "self.location.href='editLit.php?sel=<" . $p_citationID . ">&edit=1'");
            $cf->buttonSubmit(31, 36, "submitUpdate", " Update ");
        } else {
            $cf->buttonJavaScript(22, 36, " Reset ", "self.location.href='editLit.php?sel=<" . $p_citationID . ">'");
            $cf->buttonJavaScript(31, 36, " Edit ", "self.location.href='editLit.php?sel=<" . $p_citationID . ">&edit=1'");
        }
        $cf->buttonSubmit(47, 36, "submitNewCopy", " New &amp; Copy");
    } else {
        $cf->buttonReset(22, 36, " Reset ");
        $cf->buttonSubmit(31, 36, "submitUpdate", " Insert ");
        $cf->buttonSubmit(37, 36, "submitUpdateCopy", " Insert &amp; Copy");
        $cf->buttonSubmit(47, 36, "submitUpdateNew", " Insert &amp; New");
    }
}
$cf->buttonJavaScript(2, 36, " < Literature ", "self.location.href='listLit.php?nr=$nr'");
?>
</form>


<div id="xajax_listLibraries" style="position: absolute; top: 39em; left: 0em;"></div>

<div id="edit_tax_classification" style="display: none;" title="taxon classification">
    <?php
    $cf->label(4, 0.5, "child");
    $cf->inputJqAutocomplete2(1, 2.5, 24, "classification_child",0,"index_jq_autocomplete.php?field=taxonCitation&citationID=" . $p_citationID,50,2,'','',2,true);
    $cf->label(31, 0.5, "parent");
    $cf->inputJqAutocomplete2(27, 2.5, 24, "classification_parent",0,"index_jq_autocomplete.php?field=taxonCitation&includeParents=true&citationID=" . $p_citationID,50,2,'','',2,true);
    $cf->buttonLink(52.5, 2.5, "Add", '#" onclick="xajax_addClassification( ' . $p_citationID . ', $(\'#classification_childIndex\').val(), $(\'#classification_parentIndex\').val() ); return false;', 0);

    $cf->inputJqAutocomplete2(1, 6.5, 24, "classification_search",0,"index_jq_autocomplete.php?field=taxonCitation&citationID=" . $p_citationID,50,2,'','',2,true);
    $cf->buttonLink(27, 6.5, "Search", '#" onclick="xajax_searchClassifications( ' . $p_citationID . ', $(\'#classification_searchIndex\').val() ); return false;', 0);
    ?>
    <div id="classification_entries"></div>
    <?php
    $cf->text( 1, 28.5, "Pagination", "classification_pagination" );
    ?>
</div>

</body>
</html>