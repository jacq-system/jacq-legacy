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

        require_once "inc/functions.php";
        include 'ajax/search.php';
        include 'ajax/results.php';
        break;
    case 'results':
        // display the old results in another way
        require_once "inc/functions.php";
        include 'ajax/results.php';
        break;
    case 'getCollection':
        // get all collections for a given source ready to be inserted into a select-statement
        require_once "inc/functions.php";
        include 'ajax/searchFunctions.php';
        echo getCollection(filter_input(INPUT_GET, 'source_name', FILTER_SANITIZE_STRING));
        break;
    case 'getCountry':
        // get all countries for a given region ready to be inserted into a select-statement
        require_once "inc/functions.php";
        include 'ajax/searchFunctions.php';
        echo getCountry(filter_input(INPUT_GET, 'geo_general', FILTER_SANITIZE_STRING), filter_input(INPUT_GET, 'geo_region', FILTER_SANITIZE_STRING));
        break;
    case 'getProvince':
        // get all provinces for a given country ready to be inserted into a select-statement
        require_once "inc/functions.php";
        include 'ajax/searchFunctions.php';
        echo getProvince(filter_input(INPUT_GET, 'nation_engl', FILTER_SANITIZE_STRING));
        break;
}
