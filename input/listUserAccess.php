<?php
session_start();
require("inc/connect.php");

if (empty($_GET['sel'])) die();

$userID = intval($_GET['sel']);
$row = dbi_query("SELECT username FROM herbarinput_log.tbl_herbardb_users WHERE userID = '$userID'")->fetch_array();
$username = $row['username'];

if (isset($_GET['del']) && intval($_GET['del']) && checkRight('admin')) {
    $sql = "DELETE FROM herbarinput_log.tbl_herbardb_access
            WHERE ID = '" . intval($_GET['del']) . "'";
    dbi_query($sql);
}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - list User access</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
</head>

<body>

<h2>Manage User access for user <?php echo $username; ?></h2>

<form Action="<?php echo $_SERVER['PHP_SELF']; ?>" Method="POST" name="f">
<table><tr>
  <td><input class="cssfbutton" type="button" value=" add new Line " onClick="self.location.href='editUserAccess.php?id=<?php echo $userID; ?>&sel=0'"></td>
  <td width=\"20\">&nbsp;</td>
  <td><input class="cssfbutton" type="button" value="Reload" onclick="self.location.href='listUserAccess.php?sel=<?php echo $userID; ?>'"></td>
  <td width=\"20\">&nbsp;</td>
  <td><input class="cssfbutton" type="button" value=" close " onclick="self.close()"></td>
</tr></table>
</form>
<p>

<table class="out" cellspacing="0">
<tr class="out">
  <th class="out"></th>
  <th class="out"></th>
  <th class="out">Category</th>
  <th class="out">Family</th>
  <th class="out">Genus</th>
  <th class="out">update</th>
</tr>
<?php
$sql = "SELECT ha.ID, ha.update, sc.category, sc.cat_description, f.family, g.genus
        FROM herbarinput_log.tbl_herbardb_access ha
         LEFT JOIN tbl_tax_systematic_categories sc ON sc.categoryID = ha.categoryID
         LEFT JOIN tbl_tax_families f ON f.familyID = ha.familyID
         LEFT JOIN tbl_tax_genera g ON g.genID = ha.genID
        WHERE ha.userID = '$userID'";
$result = dbi_query($sql);
while ($row = mysqli_fetch_array($result)) {
    $id = $row['userID'];

    echo "<tr class=\"out\">"
       . "<td class=\"out\"><a href=\"listUserAccess.php?sel=$userID&del=" . $row['ID'] . "\" style=\"background-color:red\">del</a></td>"
       . "<td class=\"out\"><a href=\"editUserAccess.php?id=$userID&sel=" . $row['ID'] . "\">edit</a></td>"
       . "<td class=\"out\">" . htmlspecialchars($row['category']) . " (" . htmlspecialchars($row['cat_description']) . ")</td>"
       . "<td class=\"out\">" . htmlspecialchars($row['family']) . "</td>"
       . "<td class=\"out\">" . htmlspecialchars($row['genus']) . "</td>"
       . "<td class=\"out\">" . (($row['update']) ? "&radic;" : "") . "</td>"
       . "</tr>\n";
}
?>
</table>

</body>
</html>