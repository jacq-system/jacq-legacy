<?php
if (empty($_SESSION['username']) || empty($_SESSION['uid'])) {
    if (str_ends_with(getcwd(), "/ajax")) {
        die();
    } else {
        header("Location: login.php");
        exit();
    }
}
