<?php
/**
 * This is the landing page for all ajax operations of JACQ output
 */
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Cache-Control: post-check=0, pre-check=0", false);

// if no further action is defined, just quit
if (!isset($_GET['type'])) {
    die();
}

// select what to do
switch ($_GET['type']) {
    case 'search':
        // do a new search and display the results
        // set any default values
        $_SESSION['s_query'] = '';
        $_SESSION['order'] = 1;
        if (!isset($_SESSION['ITEMS_PER_PAGE'])) {
            $_SESSION['ITEMS_PER_PAGE'] = 10;
        }

        require("inc/functions.php");
        include 'ajax/search.php';
        include 'ajax/results.php';
        break;
    case 'results':
        // display the old results in another way
        require("inc/functions.php");
        include 'ajax/results.php';
        break;
}