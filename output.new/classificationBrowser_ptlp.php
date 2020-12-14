<?php
// this is the local pass through landing page for all ajax-operations of the classification browser

// require configuration
require('inc/variables.php');
require('inc/RestClient.php');
require('inc/classificationBrowser_jstreeFunctions.php');

$rest = new RestClient($_CONFIG['JACQ_SERVICES']);

header('Content-Type: application/json');

$type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);
switch ($type) {
    case 'referenceType':
        echo $rest->get('classification/references', array(filter_input(INPUT_GET, 'referenceType', FILTER_SANITIZE_STRING)));
//        echo file_get_contents($_CONFIG['JACQ_URL'] . 'index.php?r=jSONClassification/japi&action=references'
//           . '&referenceType=' . filter_input(INPUT_GET, 'referenceType', FILTER_SANITIZE_STRING));
        break;
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
        // without filterID:
//        echo file_get_contents($_CONFIG['JACQ_URL'] . 'index.php?r=jSONjsTree/japi&action=classificationBrowser'
//           . '&referenceType=' . filter_input(INPUT_GET, 'referenceType', FILTER_SANITIZE_STRING)
//           . '&referenceId=' . filter_input(INPUT_GET, 'referenceId', FILTER_SANITIZE_NUMBER_INT)
//           . '&taxonID=' . filter_input(INPUT_GET, 'taxonID', FILTER_SANITIZE_NUMBER_INT));
        // with filterID:
//        echo file_get_contents($_CONFIG['JACQ_URL'] . 'index.php?r=jSONjsTree/japi&action=classificationBrowser'
//           . '&referenceType=' . filter_input(INPUT_GET, 'referenceType', FILTER_SANITIZE_STRING)
//           . '&referenceId=' . filter_input(INPUT_GET, 'referenceId', FILTER_SANITIZE_NUMBER_INT)
//           . '&filterId=' . filter_input(INPUT_GET, 'filterId', FILTER_SANITIZE_NUMBER_INT));
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
//        echo file_get_contents($_CONFIG['JACQ_URL'] . 'index.php?r=jSONClassification/japi&action=nameReferences'
//           . '&taxonID=' . filter_input(INPUT_GET, 'taxonID', FILTER_SANITIZE_NUMBER_INT)
//           . '&excludeReferenceId=' . filter_input(INPUT_GET, 'excludeReferenceId', FILTER_SANITIZE_NUMBER_INT));
        break;
    case 'scientificNameAc':
        echo $rest->get('autocomplete/scientificNames', array(filter_input(INPUT_GET, 'term', FILTER_SANITIZE_STRING)));
//        echo file_get_contents($_CONFIG['JACQ_URL'] . 'index.php?r=autoComplete/scientificName'
//           . '&term=' . filter_input(INPUT_GET, 'term', FILTER_SANITIZE_STRING));
        break;
    case 'infoBox_statistics':
        echo $rest->get('classification/periodicalStatistics', array(filter_input(INPUT_GET, 'referenceID', FILTER_SANITIZE_STRING)));
//        echo file_get_contents($_CONFIG['JACQ_URL'] . 'index.php?r=jSONClassification/japi&action=getPeriodicalStatistics'
//           . '&referenceID=' . filter_input(INPUT_GET, 'referenceID', FILTER_SANITIZE_NUMBER_INT));
        break;
    case 'open_all':
        echo $rest->get('classification/numberOfChildrenWithChildrenCitation',
                   array(filter_input(INPUT_GET, 'referenceID', FILTER_SANITIZE_STRING)),
                   array('taxonID' => filter_input(INPUT_GET, 'taxonID', FILTER_SANITIZE_NUMBER_INT)));
//        echo file_get_contents($_CONFIG['JACQ_URL'] . 'index.php?r=jSONClassification/japi&action=numberOfChildrenWithChildrenCitation'
//           . '&referenceID=' . filter_input(INPUT_GET, 'referenceID', FILTER_SANITIZE_NUMBER_INT)
//           . '&taxonID=' . filter_input(INPUT_GET, 'taxonID', FILTER_SANITIZE_NUMBER_INT));
        break;
}
