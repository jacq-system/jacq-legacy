<?php
session_start();
require("inc/connect.php");
$sql = "UPDATE herbarinput_log.tbl_herbardb_users SET login=NULL
        WHERE userID='" . $_SESSION['uid'] . "'";
dbi_query($sql);

// Unset all of the session variables.
unset($_SESSION['username']);
unset($_SESSION['password']);
unset($_SESSION['uid']);
unset($_SESSION['gid']);
unset($_SESSION['sid']);
unset($_SESSION['editFamily']);
unset($_SESSION['editControl']);
unset($_SESSION['linkControl']);
unset($_SESSION['editorControl']);

header("Location: login.php");
