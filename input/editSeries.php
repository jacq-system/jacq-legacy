<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");
require("inc/log_functions.php");

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - edit Series</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
</head>

<body>

<?php
if ($_POST['submitUpdate'] && ($_SESSION['editControl'] & 0x2000) != 0) {
    $sw = true;
    $sql = "SELECT seriesID, series
            FROM tbl_specimens_series
            WHERE series = " . quoteString($_POST['series']) . "
             AND seriesID != '" . intval($_POST['ID']) . "'";
    $result = dbi_query($sql);
    while (($row = mysqli_fetch_array($result)) && $sw) {
        if ($row['series'] == $_POST['series']) {
            echo "<script language=\"JavaScript\">\n";
            echo "alert('Series \"" . $row['series'] . "\" already present with ID " . $row['seriesID'] . "');\n";
            echo "</script>\n";
            $id = $_POST['ID'];
            $sw = false;
        }
    }
    if ($sw) {
        if (intval($_POST['ID'])) {
            $sql = "UPDATE tbl_specimens_series SET
                     series = '" . dbi_escape_string($_POST['series']) . "'
                    WHERE seriesID = " . intval($_POST['ID']);
            $updated = 1;
        } else {
            $sql = "INSERT INTO tbl_specimens_series (series)
                    VALUES ('" . dbi_escape_string($_POST['series']) . "')";
            $updated = 0;
        }
        $result = dbi_query($sql);
        $id = (intval($_POST['ID'])) ? intval($_POST['ID']) : dbi_insert_id();
        logSpecimensSeries($id, $updated);

        if ($result) {
            echo "<script language=\"JavaScript\">\n"
               . "  window.opener.document.f.reload.click()\n"
               . "  self.close()\n"
               . "</script>\n"
               . "</body>\n</html>\n";
            die();
        }
    }
}
else {
    $id = intval($_GET['sel']);
}
?>

<form name="f" Action="<?php echo $_SERVER['PHP_SELF']; ?>" Method="POST">
<?php
$sql = "SELECT seriesID, series
        FROM tbl_specimens_series
        WHERE seriesID = '" . dbi_escape_string($id) . "'";
$result = dbi_query($sql);
$row = mysqli_fetch_array($result);

$cf = new CSSF();

echo "<input type=\"hidden\" name=\"ID\" value=\"" . $row['seriesID'] . "\">\n";
$cf->label(6, 0.5, "ID");
$cf->text(6, 0.5, "&nbsp;" . (($row['seriesID']) ? $row['seriesID'] : "new"));
$cf->label(6, 2, "series");
$cf->inputText(6, 2, 25, "series", $row['series'], 255);

if (($_SESSION['editControl'] & 0x2000) != 0) {
    $text = ($row['seriesID']) ? " Update " : " Insert ";
    $cf->buttonSubmit(9, 7, "submitUpdate", $text);
    $cf->buttonJavaScript(21, 7, " New ", "self.location.href='editSeries.php?sel=0'");
}
?>
</form>

</body>
</html>