<?php
// this is the local pass through landing page for all ajax-operations of the classification browser

// require configuration
require('inc/variables.php');

$type = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_STRING);
switch ($type) {
    case 'statistics':
        echo file_get_contents($_CONFIG['JACQ_URL'] . 'index.php?r=jSONStatistics/japi&action=showResults'
           . '&periodStart=' . filter_input(INPUT_GET, 'periodStart', FILTER_SANITIZE_STRING)
           . '&periodEnd=' . filter_input(INPUT_GET, 'periodEnd', FILTER_SANITIZE_STRING)
           . '&updated=' . filter_input(INPUT_GET, 'updated', FILTER_SANITIZE_NUMBER_INT)
           . '&type=' . filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING)
           . '&interval=' . filter_input(INPUT_GET, 'interval', FILTER_SANITIZE_STRING));
        break;
}