<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");
require("inc/log_functions.php");
require_once ("inc/xajax/xajax_core/xajax.inc.php");
no_magic();

$xajax = new xajax();
$xajax->setRequestURI("ajax/editLitServer.php");

$xajax->registerFunction("listLib");
$xajax->registerFunction("listContainer");
$xajax->registerFunction("editContainer");
$xajax->registerFunction("updateContainer");
$xajax->registerFunction("deleteContainer");

if (!isset($_SESSION['liLinkList'])) $_SESSION['liLinkList'] = '';

$nr = (isset($_GET['nr'])) ? intval($_GET['nr']) : 0;
$linkList = $_SESSION['liLinkList'];

function makeAuthor($search, $x, $y)
{
    global $cf;

    $pieces = explode(" <", $search);
    $results[] = "";
    if ($search && strlen($search) > 1) {
        $sql = "SELECT autor, autorID
                FROM tbl_lit_authors
                WHERE autor LIKE '" . mysql_escape_string(trim($pieces[0])) . "%'
                ORDER BY autor";
        if ($result = db_query($sql)) {
            //$cf->text($x, $y, "<b>" . mysql_num_rows($result) . " record" . ((mysql_num_rows($result) != 1) ? "s" : "") . " found</b>");
            if (mysql_num_rows($result) > 0) {
                while ($row = mysql_fetch_array($result)) {
                    $results[] = $row['autor'] . " <" . $row['autorID'] . ">";
                }
            }
        }
    }
    return $results;
}


function makePeriodical($search, $x, $y)
{
    global $cf;

    $pieces = explode(" <", $search);
    $results[] = "";
    if ($search && strlen($search) > 1) {
        $sql = "SELECT periodical, periodicalID
                FROM tbl_lit_periodicals
                WHERE periodical LIKE '" . mysql_escape_string($pieces[0]) . "%'
                 OR periodical_full LIKE '%". mysql_escape_string($pieces[0]) . "%'
                ORDER BY periodical";
        if ($result = db_query($sql)) {
            //$cf->text($x, $y, "<b>" . mysql_num_rows($result) . " record" . ((mysql_num_rows($result) != 1) ? "s" : "") . " found</b>");
            if (mysql_num_rows($result) > 0) {
                while ($row = mysql_fetch_array($result)) {
                    $results[] = $row['periodical'] . " <" . $row['periodicalID'] . ">";
                }
            }
        }
    }
    return $results;
}


function makePublisher($search, $x, $y)
{
    global $cf;

    $pieces = explode(" <", $search);
    $results[] = "";
    if ($search && strlen($search) > 1) {
        $sql = "SELECT publisher, publisherID
                FROM tbl_lit_publishers
                WHERE publisher LIKE '" . mysql_escape_string($pieces[0]) . "%'
                ORDER BY publisher";
        if ($result = db_query($sql)) {
            $cf->text($x, $y, "<b>" . mysql_num_rows($result) . " record" . ((mysql_num_rows($result) != 1) ? "s" : "") . " found</b>");
            if (mysql_num_rows($result) > 0) {
                while ($row = mysql_fetch_array($result)) {
                    $results[] = $row['publisher'] . " <" . $row['publisherID'] . ">";
                }
            }
        }
    }
    return $results;
}

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
    } else {
        $p_citationID = $p_jahr = $p_code = $p_titel = $p_suptitel = $p_vol = $p_part = "";
        $p_pp = $p_verlagsort = $p_keywords = $p_annotation = $p_additions = $p_bestand = "";
        $p_signature = $p_publ = $p_category = $p_autor = $p_editor = $p_periodical = "";
        $p_publisher = $p_url = "";
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
    $p_bestand    = $_POST['bestand'];
    $p_signature  = $_POST['signature'];
    $p_publ       = (!empty($_POST['publ'])) ? 1 : 0;
    $p_category   = $_POST['category'];
    $p_url        = $_POST['url'];
    $p_autor      = $_POST['autor'];
    $p_editor     = $_POST['editor'];
    $p_periodical = $_POST['periodical'];
    $p_publisher  = $_POST['publisher'];

    if ((!empty($_POST['submitUpdate']) || !empty($_POST['submitUpdateNew']) || !empty($_POST['submitUpdateCopy'])) && (($_SESSION['editControl'] & 0x20) != 0)) {
        if (intval($_POST['citationID'])) {
            $sql = "UPDATE tbl_lit SET
                     lit_url = " . quoteString($p_url) . ",
                     autorID = " . extractID($p_autor) . ",
                     jahr = " . quoteString($p_jahr) . ",
                     code = " . quoteString($p_code) . ",
                     titel = " . quoteString($p_titel) . ",
                     suptitel = " . quoteString($p_suptitel) . ",
                     editorsID = " . extractID($p_editor) . ",
                     periodicalID = " . extractID($p_periodical) . ",
                     vol = " . quoteString($p_vol) . ",
                     part = " . quoteString($p_part) . ",
                     pp = " . quoteString($p_pp) . ",
                     ppSort = " . quoteString(parsePp($p_pp)) . ",
                     publisherID = " . extractID($p_publisher) . ",
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
                     autorID = " . extractID($p_autor) . ",
                     jahr = " . quoteString($p_jahr) . ",
                     code = " . quoteString($p_code) . ",
                     titel = " . quoteString($p_titel) . ",
                     suptitel = " . quoteString($p_suptitel) . ",
                     editorsID = " . extractID($p_editor) . ",
                     periodicalID = " . extractID($p_periodical) . ",
                     vol = " . quoteString($p_vol) . ",
                     part = " . quoteString($p_part) . ",
                     pp = " . quoteString($p_pp) . ",
                     ppSort = " . quoteString(parsePp($p_pp)) . ",
                     publisherID = " . extractID($p_publisher) . ",
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
  <?php $xajax->printJavascript('inc/xajax'); ?>
  <script src="inc/jQuery/jquery.min.js" type="text/javascript"></script>
  <script src="inc/jQuery/jquery-ui.custom.min.js" type="text/javascript"></script>
  <script type="text/javascript" language="JavaScript">
    var reload = false;

    function editAuthor(sel,typ) {
      target = "editLitAuthor.php?sel=" + encodeURIComponent(sel.value) + "&typ=" + typ;
      MeinFenster = window.open(target,"editLitAuthor","width=500,height=200,top=50,left=50,scrollbars=yes,resizable=yes");
      MeinFenster.focus();
    }
    function editPeriodical(sel) {
      target = "editLitPeriodical.php?sel=" + encodeURIComponent(sel.value);
      MeinFenster = window.open(target,"editLitPeriodical","width=600,height=550,top=50,left=50,scrollbars=yes,resizable=yes");
      MeinFenster.focus();
    }
    function editPublisher(sel) {
      target = "editLitPublisher.php?sel=" + encodeURIComponent(sel.value);
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
    function checkMandatory() {
      var missing = 0;
      var text = "";
      var outtext = "";

      if (reload==true) return true;

      if (document.f.jahr.value.length==0) {
        missing++; text += "year\n";
      }
      if (document.f.category.value.length==0) {
        missing++; text += "categories\n";
      }
      if (document.f.autor.value.indexOf("<")<0 || document.f.autor.value.indexOf(">")<0) {
        missing++; text += "author{s}\n";
      }
      if (document.f.periodical.value.indexOf("<")<0 || document.f.periodical.value.indexOf(">")<0) {
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
      xajax_listLib(xajax.getFormValues('f',0,'periodical'));
    }
    function call_makeAutocompleter(name) {
      $('#' + name).autocomplete ({
        source: 'index_jq_autocomplete.php?field=citation',
        minLength: 2
      });
    }
    $(function() {
        $('#iBox_content').dialog( {
          autoOpen: false,
          modal: true,
          bgiframe: true,
          width: 750,
          height: 600
        } );
    } );
  </script>
</head>

<body onload="call_listLib()">
<div id="iBox_content" style="display:none;"></div>

<form onSubmit="return checkMandatory()" name="f" id="f" target="_self" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" >

<?php
unset($bestand);
$sql = "SELECT bestand FROM tbl_lit GROUP BY bestand ORDER BY bestand";
if ($result = db_query($sql)) {
    if (mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_array($result)) {
            $bestand[] = $row['bestand'];
        }
    }
}
if ($bestand[0] != "") array_unshift($bestand, "");

unset($category);
$sql = "SELECT category FROM tbl_lit GROUP BY category ORDER BY category";
if ($result = db_query($sql)) {
    if (mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_array($result)) {
            $category[] = $row['category'];
        }
    }
}
if ($category[0] != "") array_unshift($category, "");

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

$cf = new CSSF();

echo "<input type=\"hidden\" name=\"citationID\" value=\"$p_citationID\">\n";
if ($p_citationID) {
    if ($edit) {
        echo "<input type=\"hidden\" name=\"edit\" value=\"$edit\">\n";
        $text = "<span style=\"background-color: #66FF66\">&nbsp;<b>$p_citationID</b>&nbsp;</span>";
    } else
        $text = $p_citationID;
} else {
    $text = "<span style=\"background-color: #66FF66\">&nbsp;<b>new</b>&nbsp;</span>";
}
$cf->label(7, 0.5, "citationID");
$cf->text(7, 0.5, "&nbsp;" . $text);

$cf->label(7, 2.5, "edit Index", "javascript:taxIndex('$p_citationID')");

$cf->label(13.5, 2.5, "cited taxa ", "javascript:taxa('$p_citationID')");
$cf->label(21, 2.5, "cited persons ", "javascript:persons('$p_citationID')");

$cf->label(28.5, 2.5, "list Container", "#\" onclick=\"xajax_listContainer('$p_citationID');");
$cf->label(37, 2.5, "edit Container", "#\" onclick=\"xajax_editContainer('$p_citationID');");

$cf->labelMandatory(19, 0.5, 3, "date");
$cf->inputText(19, 0.5, 5, "jahr", $p_jahr, 50);
$cf->inputText(25, 0.5, 5, "code", $p_code, 25);
$cf->labelMandatory(40, 0.5, 6, "categories");
$cf->editDropdown(40, 0.5, 25, "category", $p_category, $category, 50);

if ($p_url) {
    $cf->label(7, 4.5, "url", "http://" . $p_url . "\" target=\"_blank");
} else {
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

$cf->labelMandatory(7, 16, 6, "author{s}", "javascript:editAuthor(document.f.autor,'a')");
$cf->editDropdown(7, 16, 25, "autor", $p_autor, makeAuthor($p_autor, 7, 13), 200);
$cf->label(7, 17.7, "search", "javascript:searchAuthor()");
$cf->label(7, 21, "editor{s}", "javascript:editAuthor(document.f.editor,'e')");
//$cf->editDropdown(7, 21, 25, "editor", $p_editor, makeAuthor($p_editor, 7, 18.5), 200);
$cf->inputText(7, 21, 25, "editor", $p_editor, 200, '', '', true);
$cf->labelMandatory(7, 25.5, 6, "periodical", "javascript:editPeriodical(document.f.periodical)");
$cf->editDropdown(7, 25.5, 25, "periodical", $p_periodical, makePeriodical($p_periodical, 7, 24), 300, 0, "", "", "call_listLib()");
$cf->inputText(7, 29.5, 2.5, "vol", $p_vol, 20);
$cf->inputText(11, 29.5, 8, "part", $p_part, 50);
$cf->inputText(20.5, 29.5, 11.5, "pp", $p_pp, 150);
$cf->label(7, 33, "printer", "javascript:editPublisher(document.f.publisher)");
$cf->editDropdown(7, 33, 25, "publisher", $p_publisher, makePublisher($p_publisher, 7, 31.5), 120);
$cf->label(7, 37, "printing Loc.");
$cf->inputText(7, 37, 25, "verlagsort", $p_verlagsort, 100);

$cf->label(40, 37, "additions");
$cf->inputText(40, 37, 25, "additions", $p_additions, 500);

$cf->label(7, 39.5, "listing");
$cf->editDropdown(7, 39.5, 25, "bestand", $p_bestand, $bestand, 50);
$cf->label(44, 39.5, "recent publication");
$cf->checkbox(44, 39.5, "publ", $p_publ);
$cf->label(53, 39.5, "signature");
$cf->inputText(53, 39.5, 12, "signature", $p_signature, 50);

if (($_SESSION['editControl'] & 0x20) != 0) {
    $cf->buttonSubmit(16, 44, "reload", " Reload \" onclick=\"reloadButtonPressed()");
    if ($p_citationID) {
        if ($edit) {
            $cf->buttonJavaScript(22, 44, " Reset ", "self.location.href='editLit.php?sel=<" . $p_citationID . ">&edit=1'");
            $cf->buttonSubmit(31, 44, "submitUpdate", " Update ");
        } else {
            $cf->buttonJavaScript(22, 44, " Reset ", "self.location.href='editLit.php?sel=<" . $p_citationID . ">'");
            $cf->buttonJavaScript(31, 44, " Edit ", "self.location.href='editLit.php?sel=<" . $p_citationID . ">&edit=1'");
        }
        $cf->buttonSubmit(47, 44, "submitNewCopy", " New &amp; Copy");
    } else {
        $cf->buttonReset(22, 44, " Reset ");
        $cf->buttonSubmit(31, 44, "submitUpdate", " Insert ");
        $cf->buttonSubmit(37, 44, "submitUpdateCopy", " Insert &amp; Copy");
        $cf->buttonSubmit(47, 44, "submitUpdateNew", " Insert &amp; New");
    }
}
$cf->buttonJavaScript(2, 44, " < Literature ", "self.location.href='listLit.php?nr=$nr'");
?>
</form>

<div id="xajax_listLibraries" style="position: absolute; top: 47em; left: 0em;"></div>

</body>
</html>