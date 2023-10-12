<?php
session_start();
require("inc/connect.php");

if (empty($_GET['sel'])) die();


?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - list Specimens</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
</head>

<body>
<h2>List log_specimens for ID <?php echo intval($_GET['sel']); ?></h2>

<?php
$result = dbi_query("SELECT specimenID FROM herbarinput_log.log_specimens WHERE specimenID = '" . intval($_GET['sel']) . "'");
if (mysqli_num_rows($result) == 0):  // nothing found
?>
nothing in log
<?php
else:  // show results
?>
<table class="out" cellspacing="0">
<tr class="out">
  <th class="out">User</th>
  <th class="out">Timestamp</th>
  <th class="out">updated</th>
<?php
$result = dbi_query("SHOW COLUMNS FROM herbarinput_log.log_specimens");
$fields = array();
while ($row = mysqli_fetch_array($result)) {
    if ($row['Field'] != 'log_specimensID' && $row['Field'] != 'specimenID' && $row['Field'] != 'userID' && $row['Field'] != 'updated' && $row['Field'] != 'timestamp') {
        $fields[] = $row['Field'];
        echo "  <th class=\"out\">" . htmlspecialchars($row['Field']) . "</th>\n";
    }
}
echo "</tr>\n";

$sql = "SELECT ls.*, hu.firstname, hu.surname
        FROM herbarinput_log.log_specimens ls, herbarinput_log.tbl_herbardb_users hu
        WHERE ls.userID = hu.userID
         AND ls.specimenID = '" . intval($_GET['sel']) . "'";
$result = dbi_query($sql);
while ($row = mysqli_fetch_array($result)) {
    echo "<tr class=\"out\">\n"
       . "  <td class=\"out\">" . htmlspecialchars($row['firstname'] . " " . $row['surname']) . "</td>\n"
       . "  <td class=\"out\">" . htmlspecialchars($row['timestamp']) . "</td>\n"
       . "  <td class=\"out\">" . (($row['updated']) ? "updated" : "") . "</td>\n";
    for ($i = 0; $i < count($fields); $i++) {
        echo "  <td class=\"out\">" . htmlspecialchars($row[$fields[$i]]) . "</td>\n";
    }
    echo "</tr>\n";
}
echo "<tr class=\"out\"><td class=\"out\" colspan=\"" . (count($fields) + 3) . "\">&nbsp;</td></tr>\n";

$result = dbi_query("SELECT * FROM tbl_specimens WHERE specimen_ID = '" . intval($_GET['sel']) . "'");
$row = mysqli_fetch_array($result);
echo "<tr class=\"out\">\n"
   . "  <td class=\"out\">active set</td>\n"
   . "  <td class=\"out\"></td>\n"
   . "  <td class=\"out\"></td>\n";
for ($i = 0; $i < count($fields); $i++) {
    echo "  <td class=\"out\">" . htmlspecialchars($row[$fields[$i]]) . "</td>\n";
}
echo "</tr>\n";
?>
</table>

<?php
endif;  // end show results
?>

</body>
</html>
