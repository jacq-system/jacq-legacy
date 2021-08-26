<?php
// this is the local pass through landing page for all ajax-operations of the classification browser

require('inc/variables.php'); // require configuration
require('inc/RestClient.php');
require('inc/classificationBrowser_jstreeFunctions.php');

$rest = new RestClient($_CONFIG['JACQ_SERVICES']);

header('Content-Type: application/json');

$type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);
switch ($type) {
    case 'jstree':
        $referenceType = trim(filter_input(INPUT_GET, 'referenceType', FILTER_SANITIZE_STRING));
        $referenceID = intval(filter_input(INPUT_GET, 'referenceId', FILTER_SANITIZE_NUMBER_INT));
        if (empty($referenceType) || empty($referenceID)) {
            echo json_encode(array());
        } else {
            $taxonID      = intval(filter_input(INPUT_GET, 'taxonID', FILTER_SANITIZE_NUMBER_INT));
            $filterID     = intval(filter_input(INPUT_GET, 'filterId', FILTER_SANITIZE_NUMBER_INT));
            $insertSeries = intval(filter_input(INPUT_GET, 'insertSeries', FILTER_SANITIZE_NUMBER_INT));
            // check if we are looking for a specific name
            if ($filterID) {
                echo json_encode(getFilteredJsTree($referenceType, $referenceID, $filterID, $insertSeries));
            }
            // .. if not, fetch the "normal" tree for this reference
            else {
                echo json_encode(getChildrenJsTree($referenceType, $referenceID, $taxonID, $insertSeries));
            }
        }
        break;
    case 'filter_button':
        echo json_encode(getFilteredJsTree(filter_input(INPUT_GET, 'referenceType', FILTER_SANITIZE_STRING),
                                           filter_input(INPUT_GET, 'referenceId', FILTER_SANITIZE_NUMBER_INT),
                                           filter_input(INPUT_GET, 'filterId', FILTER_SANITIZE_NUMBER_INT)));
        break;
    case 'infoBox_references':
        $taxonID = intval(filter_input(INPUT_GET, 'taxonID', FILTER_SANITIZE_NUMBER_INT));
        $excludeReferenceId = intval(filter_input(INPUT_GET, 'excludeReferenceId', FILTER_SANITIZE_NUMBER_INT));
        $insertSeries = intval(filter_input(INPUT_GET, 'insertSeries', FILTER_SANITIZE_NUMBER_INT));
        if (empty($taxonID)) {
            echo json_encode(array());
        } else {
            echo $rest->get("classification/nameReferences", array($taxonID), array("excludeReferenceId" => $excludeReferenceId, "insertSeries" => $insertSeries));
        }
        break;
    case 'scientificNameAc':
        echo $rest->get('autocomplete/scientificNames', array(filter_input(INPUT_GET, 'term', FILTER_SANITIZE_STRING)));
        break;
//    case 'referenceType':
//        echo $rest->get('classification/references', array(filter_input(INPUT_GET, 'referenceType', FILTER_SANITIZE_STRING)));
//        break;
//    case 'infoBox_statistics':
//        echo $rest->get('classification/periodicalStatistics', array(filter_input(INPUT_GET, 'referenceID', FILTER_SANITIZE_STRING)));
//        break;
//    case 'open_all':
//        echo $rest->get('classification/numberOfChildrenWithChildrenCitation',
//                   array(filter_input(INPUT_GET, 'referenceID', FILTER_SANITIZE_STRING)),
//                   array('taxonID' => filter_input(INPUT_GET, 'taxonID', FILTER_SANITIZE_NUMBER_INT)));
//        break;
}
