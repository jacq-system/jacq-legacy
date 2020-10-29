<?php
// this is the local pass through landing page for all ajax-operations of the classification browser

// require configuration
require('inc/variables.php');

$type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);
switch ($type) {
    case 'referenceType':
        echo file_get_contents($_CONFIG['JACQ_URL'] . 'index.php?r=jSONClassification/japi&action=references'
           . '&referenceType=' . filter_input(INPUT_GET, 'referenceType', FILTER_SANITIZE_STRING));
        break;
    case 'open_all':
        echo file_get_contents($_CONFIG['JACQ_URL'] . 'index.php?r=jSONClassification/japi&action=numberOfChildrenWithChildrenCitation'
           . '&referenceID=' . filter_input(INPUT_GET, 'referenceID', FILTER_SANITIZE_NUMBER_INT)
           . '&taxonID=' . filter_input(INPUT_GET, 'taxonID', FILTER_SANITIZE_NUMBER_INT));
        break;
    case 'infoBox_references':
        echo file_get_contents($_CONFIG['JACQ_URL'] . 'index.php?r=jSONClassification/japi&action=nameReferences'
           . '&taxonID=' . filter_input(INPUT_GET, 'taxonID', FILTER_SANITIZE_NUMBER_INT)
           . '&excludeReferenceId=' . filter_input(INPUT_GET, 'excludeReferenceId', FILTER_SANITIZE_NUMBER_INT));
        break;
    case 'infoBox_statistics':
        echo file_get_contents($_CONFIG['JACQ_URL'] . 'index.php?r=jSONClassification/japi&action=getPeriodicalStatistics'
           . '&referenceID=' . filter_input(INPUT_GET, 'referenceID', FILTER_SANITIZE_NUMBER_INT));
        break;
    case 'scientificNameAc':
        echo file_get_contents($_CONFIG['JACQ_URL'] . 'index.php?r=autoComplete/scientificName'
           . '&term=' . filter_input(INPUT_GET, 'term', FILTER_SANITIZE_STRING));
        break;
    case 'filter_button':
        echo file_get_contents($_CONFIG['JACQ_URL'] . 'index.php?r=jSONjsTree/japi&action=classificationBrowser'
           . '&referenceType=' . filter_input(INPUT_GET, 'referenceType', FILTER_SANITIZE_STRING)
           . '&referenceId=' . filter_input(INPUT_GET, 'referenceId', FILTER_SANITIZE_NUMBER_INT)
           . '&filterId=' . filter_input(INPUT_GET, 'filterId', FILTER_SANITIZE_NUMBER_INT));
        break;
    case 'jstree':
        echo file_get_contents($_CONFIG['JACQ_URL'] . 'index.php?r=jSONjsTree/japi&action=classificationBrowser'
           . '&referenceType=' . filter_input(INPUT_GET, 'referenceType', FILTER_SANITIZE_STRING)
           . '&referenceId=' . filter_input(INPUT_GET, 'referenceId', FILTER_SANITIZE_NUMBER_INT)
           . '&taxonID=' . filter_input(INPUT_GET, 'taxonID', FILTER_SANITIZE_NUMBER_INT));
        break;
}

