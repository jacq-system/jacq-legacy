<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");
no_magic();

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - edit Specimens Types</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <style type="text/css">
    table.out { width: 100% }
    tr.out { }
    th.out { font-style: italic }
    td.out { background-color: #669999; }
  </style>
</head>

<body>

<?php
if (isset($_GET['new'])) {
    $sql = "SELECT periodical, periodicalID
            FROM tbl_lit_periodicals
            WHERE periodicalID = " . extractID($_GET['ID']);
    $result = db_query($sql);
    $row = mysql_fetch_array($result);
    $p_periodical = $row['periodical'] . " <" . $row['periodicalID'] . ">";
    $p_library = $p_signature = $p_bestand = $p_url = $p_lib_period_ID = "";
} elseif (extractID($_GET['ID']) !== "NULL") {
    $sql = "SELECT lib_period_ID, signature, bestand, url, library_ID, periodical, tbl_lit_lib_period.periodicalID
            FROM tbl_lit_lib_period, tbl_lit_periodicals
            WHERE tbl_lit_lib_period.periodicalID = tbl_lit_periodicals.periodicalID
             AND lib_period_ID = " . extractID($_GET['ID']);
    $result = db_query($sql);
    if (mysql_num_rows($result)>0) {
        $row = mysql_fetch_array($result);
        $p_lib_period_ID = $row['lib_period_ID'];
        $p_library       = $row['library_ID'];
        $p_periodical    = $row['periodical'] . " <" . $row['periodicalID'] . ">";
        $p_signature     = $row['signature'];
        $p_bestand       = $row['bestand'];
        $p_url           = $row['url'];
    } else {
        $p_signature = $p_bestand = $p_url = $p_lib_period_ID = $p_library = $p_periodical = "";
    }
} elseif ($_POST['submitUpdate'] && (($_SESSION['editControl'] & 0x80) != 0)) {
    $signature = $_POST['signature'];
    $bestand   = $_POST['bestand'];
    $url       = $_POST['url'];
    if (intval($_POST['lib_period_ID'])) {
        $sql = "UPDATE tbl_lit_lib_period SET
                 periodicalID = " . extractID($_POST['periodical']) . ",
                 library_ID = " . intval($_POST['library']) . ",
                 signature = " . quoteString($signature) . ",
                 bestand = " . quoteString($bestand) . ",
                 url = " . quoteString($url) . "
                WHERE lib_period_ID = " . intval($_POST['lib_period_ID']);
    } else {
        $sql = "INSERT INTO tbl_lit_lib_period SET
                 periodicalID = " . extractID($_POST['periodical']).",
                 library_ID = " . intval($_POST['library']).",
                 signature = " . quoteString($signature).",
                 bestand = " . quoteString($bestand).",
                 url = " . quoteString($url);
    }
    $result = db_query($sql);
    if ($result) {
        echo "<script language=\"JavaScript\">\n"
           . "  window.opener.document.f.reload.click()\n"
           . "  self.close()\n"
           . "</script>\n";
    }
} else {
    $p_periodical    = $_POST['periodical'];
    $p_library       = $_POST['library'];
    $p_signature     = $_POST['signature'];
    $p_bestand       = $_POST['bestand'];
    $p_url           = $_POST['url'];
    $p_lib_period_ID = $_POST['lib_period_ID'];
}
?>

<form Action="<?php echo $_SERVER['PHP_SELF']; ?>" Method="POST" name="f">

<?php
unset($library);
$sql = "SELECT library, library_ID FROM tbl_lit_libraries ORDER BY library";
if ($result = db_query($sql)) {
    if (mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_array($result)) {
            $library[0][] = $row['library_ID'];
            $library[1][] = $row['library'];
        }
    }
}

$cf = new CSSF();

echo "<input type=\"hidden\" name=\"lib_period_ID\" value=\"$p_lib_period_ID\">\n";
$cf->label(7, 0.5, "ID");
$cf->text(7, 0.5, "&nbsp;" . (($p_lib_period_ID) ? $p_lib_period_ID : "new"));

$cf->label(7, 2, "Periodical");
$cf->text(7, 2, "&nbsp;" . $p_periodical);
echo "<input type=\"hidden\" name=\"periodical\" value=\"$p_periodical\">\n";

$cf->label(7, 4, "Library");
$cf->dropdown(7, 4, "library", $p_library, $library[0], $library[1]);

$cf->label(7, 6, "Signature");
$cf->inputText(7, 6, 10, "signature", $p_signature, 50);
$cf->label(7, 8, "Stock");
$cf->inputText(7, 8, 28, "bestand", $p_bestand, 255);
$cf->label(7, 10, "url");
$cf->textarea(7, 10, 28, 4, "url", $p_url);

if (($_SESSION['editControl'] & 0x80) != 0) {
    $text = ($p_lib_period_ID) ? " Update " : " Insert ";
    $cf->buttonSubmit(2, 18, "reload", " Reload ");
    $cf->buttonReset(10, 18, " Reset ");
    $cf->buttonSubmit(20, 18, "submitUpdate", $text);
}
$cf->buttonJavaScript(28, 18, " Cancel ", "self.close()");
?>

</form>
</body>
</html>