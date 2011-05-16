<?php
session_start();
require("inc/connect.php");
no_magic();

$type = intval($_GET['typ']);

if ($type == 2) {
    $header = "Voucher";
    $tblName = "tbl_specimens_voucher";
    $tblID = "voucherID";
    $tblOrder = "voucherID";
    $tblMaxColumn = 1;
    $tblColumn[0] = "voucher";
    $tblColumnName[0] = "Voucher";
    $tblColumnSize[0] = 35;
} else {
    $header = "Series";
    $tblName = "tbl_specimens_series";
    $tblID = "seriesID";
    $tblOrder = "series";
    $tblMaxColumn = 1;
    $tblColumn[0] = "series";
    $tblColumnName[0] = "Series";
    $tblColumnSize[0] = 80;
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - edit Table</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta http-equiv="Content-Style-Type" content="text/css">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
</head>
<body>

<div align="center">

<h1><?php echo $header; ?></h1>

<?php
if ($_POST['submitUpdate']) {
    $updates = 0;
    $sql = "SELECT * FROM $tblName";
    $result = mysql_query($sql);
    if (mysql_num_rows($result)) {
        while ($row = mysql_fetch_array($result)) {
            $alter = false;
            for ($i = 0; $i < $tblMaxColumn; $i++) {
                $cName[$i] = "c" . $i . "_" . $row[$tblID];
                if ($row[$tblColumn[$i]] != $_POST[$cName[$i]]) {
                    $alter = true;
                }
            }
            if ($alter) {
                $sql = "UPDATE $tblName SET "
                     . $tblColumn[0] . "='" . mysql_escape_string($_POST[$cName[0]]) . "'";
                for ($i = 1; $i < $tblMaxColumn; $i++) {
                    $sql .= ", " . $tblColumn[$i] . "='" . mysql_escape_string($_POST[$c1Name]) . "'";
                }
                $sql .= " WHERE $tblID='" . $row[$tblID] . "'";
                mysql_query($sql);
                if (!mysql_errno()) $updates++;
            }
        }
    }
} elseif ($_POST['submitAdd']) {
    $result = mysql_query("SELECT max($tblID)+1 AS newID FROM $tblName");
    $row = mysql_fetch_array($result);
    $newID = $row['newID'];
    $sql = "INSERT INTO $tblName ($tblID) VALUES ('$newID')";
    mysql_query($sql);
}

if ($updates) echo "<p>$updates Update(s) done</p>\n";

echo "<form name=\"f\" Action=\"".$_SERVER['PHP_SELF']."?typ=$type\" Method=\"POST\">\n";
echo "<table><tr><td colspan=\"2\">\n";
echo "<table class=\"list\">\n";
echo "<tr>";
for ($i = 0; $i < $tblMaxColumn; $i++) {
    echo "<th class=\"list\">" . $tblColumnName[$i] . "</th>";
}
echo "</tr>\n";
$sql = "SELECT * FROM $tblName ORDER BY $tblOrder";
$result = mysql_query($sql);
if (mysql_num_rows($result)) {
    while ($row = mysql_fetch_array($result)) {
        echo "<tr>\n";
        for ($i = 0; $i < $tblMaxColumn; $i++)
            echo "<td class=\"list\"><div class=\"input\">"
               . "<input type=\"text\" name=\"c" . $i . "_" . $row[$tblID] . "\" value=\"" . $row[$tblColumn[$i]] . "\""
               . " size=\"" . $tblColumnSize[$i] . "\" maxlength=\"255\">"
               . "</div></td>\n";
        echo "</tr>\n";
    }
}
echo "</table></td></tr>\n";
echo "<tr><td colspan=\"2\">&nbsp;</td></tr>\n";
echo "<tr><td align=\"left\">";
echo "<input class=\"button\" type=\"submit\" name=\"submitUpdate\" value=\"Update\">";
echo "</td><td align=\"right\">";
echo "<input class=\"button\" type=\"submit\" name=\"submitAdd\" value=\"add new line\">";
echo "</td></tr>\n";
echo "</table>\n";
echo "</form>\n";
?>

</div>

</body>
</html>