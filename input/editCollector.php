<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");
require("inc/log_functions.php");

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - edit Collector</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <script type="text/javascript" language="JavaScript">
    function showExternal(sel) {
      MeinFenster = window.open(sel.value,"showHUH");
      MeinFenster.focus();
    }
    function checkCollector()
    {
        var collector = document.f.Sammler.value;
        if (collector.trim() == '') {
            alert("No empty collector names allowed");
            return false;
        } else {
            return true;
        }
    }
  </script>
</head>

<body>

<?php
if (!empty($_POST['submitUpdate']) && (($_SESSION['editControl'] & 0x1800) != 0)) {
    $sw = true;
    $sql = "SELECT SammlerID, Sammler
            FROM tbl_collector
            WHERE Sammler = " . quoteString($_POST['Sammler']) . "
             AND SammlerID != '" . intval($_POST['ID']) . "'";
    $result = dbi_query($sql);
    while (($row = mysqli_fetch_array($result)) && $sw) {
        if ($row['Sammler'] == $_POST['Sammler']) {
            echo "<script language=\"JavaScript\">\n";
            echo "alert('Collector \"" . $row['Sammler'] . "\" already present with ID " . $row['SammlerID'] . "');\n";
            echo "</script>\n";
            $id = intval($_POST['ID']);
            $sw = false;
        }
    }
    if (empty(trim($_POST['Sammler']))) {
        echo "<script language=\"JavaScript\">\n";
        echo "alert('No empty collector names allowed');\n";
        echo "</script>\n";
        $sw = false;
    }
    if ($sw) {
        if (intval($_POST['ID'])) {
            if (($_SESSION['editControl'] & 0x1000) != 0) {
                $sql = "UPDATE tbl_collector SET
                         Sammler = '"      . dbi_escape_string($_POST['Sammler']) . "',
                         HUH_ID = "        . quoteString($_POST['HUH_ID']) . ",
                         VIAF_ID = "       . quoteString($_POST['VIAF_ID']) . ",
                         WIKIDATA_ID = "   . quoteString($_POST['WIKIDATA_ID']) . ",
                         ORCID = "         . quoteString($_POST['ORCID']) . ",
                         Bloodhound_ID = " . quoteString($_POST['Bionomia']) . "
                        WHERE SammlerID = '" . intval($_POST['ID']) . "'";
            } else {
                $sql = "";
            }
            $updated = 1;
        } else {
            $sql = "INSERT INTO tbl_collector SET
                     Sammler = '"      . dbi_escape_string($_POST['Sammler']) . "',
                     HUH_ID = "        . quoteString($_POST['HUH_ID']) . ",
                     VIAF_ID = "       . quoteString($_POST['VIAF_ID']) . ",
                     WIKIDATA_ID = "   . quoteString($_POST['WIKIDATA_ID']) . ",
                     ORCID = "         . quoteString($_POST['ORCID']) . ",
                     Bloodhound_ID = " . quoteString($_POST['Bionomia']);
            $updated = 0;
        }
        $result = dbi_query($sql);
        $id = ($_POST['ID']) ? intval($_POST['ID']) : dbi_insert_id();
        if ($sql) {
            logCollector($id, $updated);
        }

        echo "<script language=\"JavaScript\">\n";
        echo "  window.opener.document.f.sammler.value = \"" . addslashes($_POST['Sammler']) . " <$id>\";\n";
    //    echo "  window.opener.document.f.reload.click()\n";
        echo "  self.close()\n";
        echo "</script>\n";
        echo "</body>\n</html>\n";
        die();
    }
} elseif (!empty($_GET['sel'])) {
    $pieces = explode("<", $_GET['sel']);
    $pieces = explode(">", $pieces[1]);
    $id = $pieces[0];
} else {
    $id = 0;
}

echo "<form onSubmit='return checkCollector()' name='f' Action='" . $_SERVER['PHP_SELF'] . "' Method='POST'>\n";

$sql = "SELECT Sammler, SammlerID, HUH_ID, VIAF_ID, WIKIDATA_ID, ORCID, Bloodhound_ID
        FROM tbl_collector WHERE SammlerID = '$id'";
$result = dbi_query($sql);
$row = mysqli_fetch_array($result);

$cf = new CSSF();

echo "<input type=\"hidden\" name=\"ID\" value=\"".($row['SammlerID'] ?? '')."\">\n";
$cf->label(7,0.5,"ID");
$cf->text(7,0.5,"&nbsp;".(($row['SammlerID']) ?? "new"));
$cf->label(7,2,"Collector");
$cf->inputText(7,2,15,"Sammler", ($row['Sammler'] ?? ''),50);
$cf->label(6.5,4.5,"HUH","javascript:showExternal(document.f.HUH_ID)");
$cf->inputText(7,4.5,50,"HUH_ID", ($row['HUH_ID'] ?? ''),200);
$cf->label(7,6.5,"VIAF","javascript:showExternal(document.f.VIAF_ID)");
$cf->inputText(7,6.5,50,"VIAF_ID", ($row['VIAF_ID'] ?? ''),200);
$cf->label(7,8.5,"WIKIDATA","javascript:showExternal(document.f.WIKIDATA_ID)");
$cf->inputText(7,8.5,50,"WIKIDATA_ID", ($row['WIKIDATA_ID'] ?? ''),200);
$cf->label(7,10.5,"ORCID","javascript:showExternal(document.f.ORCID)");
$cf->inputText(7,10.5,50,"ORCID", ($row['ORCID'] ?? ''),200);
$cf->label(7,12.5,"Bionomia","javascript:showExternal(document.f.Bionomia)");
$cf->inputText(7,12.5,50,"Bionomia", ($row['Bloodhound_ID'] ?? ''),200);

if (($_SESSION['editControl'] & 0x1800)!=0) {
  $text = (!empty($row['SammlerID'])) ? " Update " : " Insert ";
  $cf->buttonSubmit(2,16,"submitUpdate",$text);
  $cf->buttonJavaScript(12,16," New ","self.location.href='editCollector.php?sel=<0>'");
}

echo "</form>\n";
?>

</body>
</html>
