<?php
session_start();
require("inc/connect.php");
require("inc/herbardb_input_functions.php");
no_magic();

if (isset($_GET['order'])) {
    if ($_GET['order'] == "b") {
        $_SESSION['userOrder'] = "group_name, username";
        if ($_SESSION['userOrTyp'] == 2) {
            $_SESSION['userOrTyp'] = -2;
        } else {
            $_SESSION['userOrTyp'] = 2;
        }
    } else if ($_GET['order'] == "c") {
        $_SESSION['userOrder'] = "surname, firstname, username";
        if ($_SESSION['userOrTyp'] == 3) {
            $_SESSION['userOrTyp'] = -3;
        } else {
            $_SESSION['userOrTyp'] = 3;
        }
    } else {
        $_SESSION['userOrder'] = "username";
        if ($_SESSION['userOrTyp'] == 1) {
            $_SESSION['userOrTyp'] = -1;
        } else {
            $_SESSION['userOrTyp'] = 1;
        }
    }
    if ($_SESSION['userOrTyp'] < 0) $_SESSION['userOrder'] = implode(" DESC, ", explode(", ", $_SESSION['userOrder'])) . " DESC";
} elseif (!isset($_SESSION['userOrder'])) {
    $_SESSION['userOrder'] = "username";
    $_SESSION['userOrTyp'] = 1;
}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - list Users</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
</head>

<body>

<input class="button" type="button" value=" close window " onclick="self.close()" id="close">

<h1>Manage Users</h1>

<input class="button" type="button" value=" new User " onclick="self.location.href='editUser.php?sel=0'">
<p>

<table class="out" cellspacing="0">
<tr class="out">
  <th class="out"><a href="<?php echo $_SERVER['PHP_SELF']; ?>?order=a">User</a> <?php echo sortItem($_SESSION['userOrTyp'],1); ?></th>
  <th class="out"><a href="<?php echo $_SERVER['PHP_SELF']; ?>?order=b">Group</a> <?php echo sortItem($_SESSION['userOrTyp'],2); ?></th>
  <th class="out"><a href="<?php echo $_SERVER['PHP_SELF']; ?>?order=c">Name</a> <?php echo sortItem($_SESSION['userOrTyp'],3); ?></th>
  <th class="out">First Name</th>
  <th class="out">email</th>
  <th class="out">edit family</th>
  <th class="out">acc</th>
</tr>
<?php
$sql = "SELECT hu.*, hg.group_name, hg.group_description
        FROM herbarinput_log.tbl_herbardb_users hu
         LEFT JOIN herbarinput_log.tbl_herbardb_groups hg on hu.groupID = hg.groupID
        WHERE active = '1'
        ORDER BY " . $_SESSION['userOrder'];
$result = db_query($sql);
while ($row = mysql_fetch_array($result)) {
    echo "<tr class=\"out\">";
    echo "<td class=\"out\">"
       . "<a href=\"editUser.php?sel=" . $row['userID'] . "\">" . htmlspecialchars($row['username']) . "</a></td>";
    echo "<td class=\"out\" title=\"" . htmlspecialchars($row['group_description']) . "\">" . htmlspecialchars($row['group_name']) . "</td>";
    echo "<td class=\"out\">" . htmlspecialchars($row['surname']) . "</td>";
    echo "<td class=\"out\">" . htmlspecialchars($row['firstname']) . "</td>";
    echo "<td class=\"out\">" . htmlspecialchars($row['emailadress']) . "</td>";
    echo "<td class=\"out\">" . htmlspecialchars($row['editFamily']) . "</td>";
    echo "<td class=\"out\">" . (($row['use_access']) ? "&radic;" : "") . "</td>";
    echo "</tr>\n";
}

$sql = "SELECT hu.*, hg.group_name, hg.group_description
        FROM herbarinput_log.tbl_herbardb_users hu
         LEFT JOIN herbarinput_log.tbl_herbardb_groups hg on hu.groupID = hg.groupID
        WHERE active = '0'
        ORDER BY " . $_SESSION['userOrder'];
$result = db_query($sql);
while ($row = mysql_fetch_array($result)) {
    echo "<tr class=\"out\">";
    echo "<td class=\"out\">"
       . "<a href=\"editUser.php?sel=" . $row['userID'] . "\">" . htmlspecialchars($row['username']) . "</a></td>";
    echo "<td class=\"outInactive\" title=\"" . htmlspecialchars($row['group_description']) . "\">" . htmlspecialchars($row['group_name']) . "</td>";
    echo "<td class=\"outInactive\">" . htmlspecialchars($row['surname']) . "</td>";
    echo "<td class=\"outInactive\">" . htmlspecialchars($row['firstname']) . "</td>";
    echo "<td class=\"outInactive\">" . htmlspecialchars($row['emailadress']) . "</td>";
    echo "<td class=\"outInactive\">" . htmlspecialchars($row['editFamily']) . "</td>";
    echo "<td class=\"outInactive\">" . (($row['use_access']) ? "&radic;" : "") . "</td>";
    echo "</tr>\n";
}
?>
</table>

</body>
</html>