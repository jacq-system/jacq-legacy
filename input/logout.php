<?php
session_start();
require("inc/connect.php");
$sql = "UPDATE herbarinput_log.tbl_herbardb_users SET
        login=NULL
        WHERE userID='" . $_SESSION['uid'] . "'";
mysql_query($sql);

$_SESSION = array();  // Unset all of the session variables.
session_destroy();
Header("Location: login.php");
?>