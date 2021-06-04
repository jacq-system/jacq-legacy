<?php
session_start();
require("inc/connect.php");

$groupID = intval($_GET['sel']);
$row = dbi_query("SELECT group_name FROM herbarinput_log.tbl_herbardb_groups WHERE groupID='$groupID'")->fetch_array();
$groupname = $row['group_name'];

if (isset($_GET['del']) && intval($_GET['del']) && checkRight('admin')) {
    $sql = "DELETE FROM herbarinput_log.tbl_herbardb_unlock
            WHERE ID = '" . intval($_GET['del']) . "'";
    dbi_query($sql);
}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - list Table Unlock</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
</head>

<body>

<h2>Manage Table Unlock for group <?php echo $groupname; ?></h2>

<form Action="<?php echo $_SERVER['PHP_SELF']; ?>" Method="POST" name="f">
<table><tr>
  <td><input class="cssfbutton" type="button" value=" add new Line " onClick="self.location.href='editGroupUnlock.php?id=<?php echo $groupID; ?>&sel=0'"></td>
  <td width=\"20\">&nbsp;</td>
  <td><input class="cssfbutton" type="button" value="Reload" onclick="self.location.href='listGroupUnlock.php?sel=<?php echo $groupID; ?>'"></td>
  <td width=\"20\">&nbsp;</td>
  <td><input class="cssfbutton" type="button" value=" close " onclick="self.close()"></td>
</tr></table>
</form>
<p>

<table class="out" cellspacing="0">
<tr class="out">
  <th class="out"></th>
  <th class="out"></th>
  <th class="out">Table</th>
</tr>
<?php
$sql = "SELECT ID, `table`
        FROM herbarinput_log.tbl_herbardb_unlock
        WHERE groupID = '$groupID'";
$result = dbi_query($sql);
while ($row=mysqli_fetch_array($result)) {
    echo "<tr class=\"out\">";
    echo "<td class=\"out\"><a href=\"listGroupUnlock.php?sel=$groupID&del=" . $row['ID'] . "\" style=\"background-color:red\">del {$row['table']}</a></td>";
    echo "<td class=\"out\"><a href=\"editGroupUnlock.php?id=$groupID&sel=" . $row['ID'] . "\">edit {$row['table']}</a></td>";
    echo "</tr>\n";
}
?>
</table>

</body>
</html>