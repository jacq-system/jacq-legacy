<?php
session_start();
require("inc/connect.php");
require("inc/herbardb_input_functions.php");

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - list Groups</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
</head>

<body>

<h1>Manage Groups</h1>

<input class="button" type="button" value=" new Group " onclick="self.location.href='editGroup.php?sel=0'">
<p>

<table class="out" cellspacing="0">
<tr class="out">
  <th class="out">Group</th>
  <th class="out">Description</th>
</tr>
<?php
$sql = "SELECT groupID, group_name, group_description
        FROM herbarinput_log.tbl_herbardb_groups
        ORDER BY group_name";
$result = dbi_query($sql);
while ($row = mysqli_fetch_array($result)) {
  echo "<tr class=\"out\">";
  echo "<td class=\"out\">".
       "<a href=\"editGroup.php?sel=".$row['groupID']."\">" . htmlspecialchars($row['group_name']) . "</a></td>";
  echo "<td class=\"out\">" . htmlspecialchars($row['group_description']) . "</td>";
  echo "</tr>\n";
}
?>
</table>

</body>
</html>
