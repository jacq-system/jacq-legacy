<?php
// this is the local pass through landing page for all ajax-operations of the classification browser

// require configuration
require('inc/variables.php');

header('Content-Type: application/json');

$type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);
switch ($type) {
    case 'referenceType':
        echo getWebService('classification/references/', filter_input(INPUT_GET, 'referenceType', FILTER_SANITIZE_STRING));
//        echo file_get_contents($_CONFIG['JACQ_URL'] . 'index.php?r=jSONClassification/japi&action=references'
//           . '&referenceType=' . filter_input(INPUT_GET, 'referenceType', FILTER_SANITIZE_STRING));
        break;
    case 'jstree':
        $referenceType = trim(filter_input(INPUT_GET, 'referenceType', FILTER_SANITIZE_STRING));
        $referenceID = filter_input(INPUT_GET, 'referenceId', FILTER_SANITIZE_NUMBER_INT);
        if (empty($referenceType) || empty($referenceID)) {
            echo json_encode(array());
        } else {
            $taxonID = filter_input(INPUT_GET, 'taxonID', FILTER_SANITIZE_NUMBER_INT);
            echo json_encode(getChildrenJsTree($referenceType, $referenceID, $taxonID));
//            echo file_get_contents($_CONFIG['JACQ_SERVICES'] . "classification/childrenJsTree/$referenceType/$referenceID" . (($taxonID) ? "?taxonID=$taxonID" : ''));
        }
//        echo file_get_contents($_CONFIG['JACQ_URL'] . 'index.php?r=jSONjsTree/japi&action=classificationBrowser'
//           . '&referenceType=' . filter_input(INPUT_GET, 'referenceType', FILTER_SANITIZE_STRING)
//           . '&referenceId=' . filter_input(INPUT_GET, 'referenceId', FILTER_SANITIZE_NUMBER_INT)
//           . '&taxonID=' . filter_input(INPUT_GET, 'taxonID', FILTER_SANITIZE_NUMBER_INT));
        break;
    case 'infoBox_references':
        $taxonID = intval(filter_input(INPUT_GET, 'taxonID', FILTER_SANITIZE_NUMBER_INT));
        $excludeReferenceId = intval(filter_input(INPUT_GET, 'excludeReferenceId', FILTER_SANITIZE_NUMBER_INT));
        if (empty($taxonID)) {
            echo json_encode(array());
        } else {
            echo getWebService("classification/nameReferences/", "$taxonID" . (($excludeReferenceId) ? "?excludeReferenceId=$excludeReferenceId" : ''));
        }
//        echo file_get_contents($_CONFIG['JACQ_URL'] . 'index.php?r=jSONClassification/japi&action=nameReferences'
//           . '&taxonID=' . filter_input(INPUT_GET, 'taxonID', FILTER_SANITIZE_NUMBER_INT)
//           . '&excludeReferenceId=' . filter_input(INPUT_GET, 'excludeReferenceId', FILTER_SANITIZE_NUMBER_INT));
        break;
    case 'scientificNameAc':
        echo getWebService('autocomplete/scientificNames/', filter_input(INPUT_GET, 'term', FILTER_SANITIZE_STRING));
//        echo file_get_contents($_CONFIG['JACQ_URL'] . 'index.php?r=autoComplete/scientificName'
//           . '&term=' . filter_input(INPUT_GET, 'term', FILTER_SANITIZE_STRING));
        break;
    case 'infoBox_statistics':
        echo getWebService('classification/periodicalStatistics/', filter_input(INPUT_GET, 'referenceID', FILTER_SANITIZE_STRING));
//        echo file_get_contents($_CONFIG['JACQ_URL'] . 'index.php?r=jSONClassification/japi&action=getPeriodicalStatistics'
//           . '&referenceID=' . filter_input(INPUT_GET, 'referenceID', FILTER_SANITIZE_NUMBER_INT));
        break;
    case 'open_all':
        echo getWebService('classification/numberOfChildrenWithChildrenCitation/', filter_input(INPUT_GET, 'referenceID', FILTER_SANITIZE_STRING)
                                                                                 . '?taxonID=' . filter_input(INPUT_GET, 'taxonID', FILTER_SANITIZE_NUMBER_INT));
//        echo file_get_contents($_CONFIG['JACQ_URL'] . 'index.php?r=jSONClassification/japi&action=numberOfChildrenWithChildrenCitation'
//           . '&referenceID=' . filter_input(INPUT_GET, 'referenceID', FILTER_SANITIZE_NUMBER_INT)
//           . '&taxonID=' . filter_input(INPUT_GET, 'taxonID', FILTER_SANITIZE_NUMBER_INT));
        break;
    case 'filter_button':
        echo json_encode(getFilteredJsTree(filter_input(INPUT_GET, 'referenceType', FILTER_SANITIZE_STRING),
                                           filter_input(INPUT_GET, 'referenceId', FILTER_SANITIZE_NUMBER_INT),
                                           filter_input(INPUT_GET, 'filterId', FILTER_SANITIZE_NUMBER_INT)));
//        echo file_get_contents($_CONFIG['JACQ_URL'] . 'index.php?r=jSONjsTree/japi&action=classificationBrowser'
//           . '&referenceType=' . filter_input(INPUT_GET, 'referenceType', FILTER_SANITIZE_STRING)
//           . '&referenceId=' . filter_input(INPUT_GET, 'referenceId', FILTER_SANITIZE_NUMBER_INT)
//           . '&filterId=' . filter_input(INPUT_GET, 'filterId', FILTER_SANITIZE_NUMBER_INT));
        break;
}

////////////////////////////// service functions //////////////////////////////

/**
 * get data of a webservice
 *
 * @global array $_CONFIG configuration variable
 * @param string $service which service do we call
 * @param string $params parameters for the call, already formatted
 * @return string answer of the webservice
 */
function getWebService ($service, $params)
{
    global $_CONFIG;

    return file_get_contents($_CONFIG['JACQ_SERVICES'] . $service . $params);
}

/**
 * get data of a webservice and json-decode them
 *
 * @global array $_CONFIG configuration variable
 * @param string $service which service do we call
 * @param string $params parameters for the call, already formatted
 * @return mixed json-decoded answer of the webservice
 */
function jsonGetWebservice($service, $params)
{
    global $_CONFIG;

    return json_decode(file_get_contents($_CONFIG['JACQ_SERVICES'] . $service . $params), true);
}

/**
 * Returns the next classification-level below a given taxonID or top level (if taxonID=0)
 * @param type $referenceType Type of reference (periodical, citation, service, etc.)
 * @param type $referenceID ID of reference
 * @param type $taxonID optional ID of taxon
 * @return string result formatted for direct use with jsTree
 */
function getChildrenJsTree ($referenceType, $referenceID, $taxonID = 0)
{
    // only execute code if we have a valid reference ID
    if (intval($referenceID) <= 0) {
        return array();
    }

    $return = array();

    $infoLink = "&nbsp;<span class='infoBox'><img src='images/information.png'></span>";

    // check for synonyms
    if ($taxonID) {
        $synonyms = jsonGetWebservice("classification/synonyms/", "$referenceType/$referenceID/$taxonID");
        if (count($synonyms) > 0) {
            foreach ($synonyms as $synonym) {
                $typeLink = $synonym["hasType"] ? "<span class='typeBox'>&#x3C4;</span>" : '';
                $specimenLink = $synonym["hasSpecimen"] ? "<span class='specimenBox'>S</span>" : '';
                if ($synonym["hasType"] || $synonym["hasSpecimen"]) {
                    $parts = explode(' ', $synonym["referenceName"]);
                    $taxon = trim((count($parts) <= 2) ? $parts[0] : $synonym["referenceName"]);
                } else {
                    $taxon = '';
                }
                $return[] = array(
                    "data" => array(
                        "title" => (($synonym['referenceInfo']['cited']) ? $synonym["referenceName"] : '[' . $synonym["referenceName"] . ']') . $infoLink . $typeLink . $specimenLink, // uncited synonyms (i.e. basionym) are shown in brackets
                        "attr" => array(
                            "data-taxon-id" => $synonym["taxonID"],
                            "data-taxon" => $taxon,
                            "data-reference-id" => $synonym["referenceId"],
                            "data-reference-type" => $synonym["referenceType"]
                        )
                    ),
                    "icon" => ($synonym['referenceInfo']['type'] == 'homotype') ? "images/identical_to.png" : "images/equal_to.png"
                );
            }
        }
    }

    // find all classification children
    $children = jsonGetWebservice("classification/children/", "$referenceType/$referenceID" . (($taxonID) ? "?taxonID=$taxonID" : ''));
    foreach ($children as $child) {
        if (mb_strlen($child["referenceName"]) > 120) {
            $title = mb_substr($child["referenceName"], 0, 50) . ' ... ' . mb_substr($child["referenceName"], -70);
            $titleFull = $child["referenceName"];
        } else {
            $title = $child["referenceName"];
            $titleFull = '';
        }
        $typeLink = $child["hasType"] ? "<span class='typeBox'>&#x3C4;</span>" : '';
        $specimenLink = $child["hasSpecimen"] ? "<span class='specimenBox'>S</span>" : '';
        if ($child["hasType"] || $child["hasSpecimen"]) {
            $parts = explode(' ', $child["referenceName"]);
            $taxon = trim((count($parts) <= 2) ? $parts[0] : $child["referenceName"]);
        } else {
            $taxon = '';
        }
        $entry = array(
            "data" => array(
                "title" => $title .  $infoLink . $typeLink . $specimenLink,
                "attr" => array(
                    "data-taxon-id" => $child["taxonID"],
                    "data-taxon" => $taxon,
                    "data-reference-id" => $child["referenceId"],
                    "data-reference-type" => $child["referenceType"],
                )
            ),
        );
        if ($titleFull) {
            $entry['data']['attr']['title'] = $titleFull;
        }

        // change node icon based on various aspects
        switch ($child["referenceType"]) {
            case 'citation':
                $entry["icon"] = "images/book_open.png";
                break;
            default:
                break;
        }
        // if entry has a taxon id, it is a scientific name entry
        if ($child["taxonID"]) {
            $entry["icon"] = "images/spacer.gif";

            // check for rank display
            if ($child['referenceInfo']['rank_hierarchy'] > 15 && $child['referenceInfo']['rank_hierarchy'] < 21) {
                $entry['data']['title'] = $child['referenceInfo']['rank_abbr'] . ' ' . $entry['data']['title'];
            }

            // taxon entries do have some additional info
            if (!empty($child['referenceInfo']['number'])) {
                $entry['data']['title'] = '<i><b>' . $child['referenceInfo']['number'] . '</b></i>&nbsp;'. $entry['data']['title'];
            }
        }

        // check if we have further children
        if ($child['hasChildren']) {
            $entry['state'] = 'closed';
        }

        // save entry for return
        $return[] = $entry;
    }

    return $return;
}

/**
 * Returns the whole classification tree filtered down to a given taxonID
 * @param type $referenceType
 * @param type $referenceId
 * @param type $taxonID
 * @return string
 */
function getFilteredJsTree($referenceType, $referenceId, $taxonID)
{
    $return = array();
    // collection of references to search for the taxonID in
    $references = array(
        array('referenceType' => $referenceType, 'referenceId' => $referenceId, 'taxonID' => $taxonID)
    );
    // optional citations which we look for (only for periodicals)
    $citations = null;

    // check if we have a periodical, since then we have to fetch all citations first
    if( $referenceType == 'periodical' ) {
        $citations = getChildrenJsTree($referenceType, $referenceId);

        // convert all fetched citations to references to look for
        $references = array();
        foreach( $citations as $i => $citation ) {
            $references[$i] = array(
                'referenceType' => $citation['data']['attr']['data-reference-type'],
                'referenceId' => $citation['data']['attr']['data-reference-id'],
                'taxonID' => $taxonID
            );
        }
    }

    // search children for all references
    foreach($references as $refIndex => $reference) {
        // helper variables for handling the structure
        $structure = array();
        $activeChild = null;
        $bParentFound = false;

        // virtual first parent
        $currParent = array(
            'referenceType' => $reference['referenceType'],
            'referenceId' => $reference['referenceId'],
            'taxonID' => $reference['taxonID']
        );

        // find chain of parents
        while (($currParent = jsonGetWebservice("classification/parent/", $currParent['referenceType'] . "/" . $currParent['referenceId'] . "/" . $currParent['taxonID'])) != null) {
            $currParentChildren = getChildrenJsTree(
                    $currParent['referenceType'],
                    $currParent['referenceId'],
                    $currParent['taxonID']
                    );

            // find active child among all children
            if( $activeChild != null ) {
                foreach( $currParentChildren as $i => $currParentChild ) {
                    if( $currParentChild['data']['attr']['data-reference-type'] == $activeChild['referenceType'] &&
                        $currParentChild['data']['attr']['data-reference-id'] == $activeChild['referenceId'] &&
                        $currParentChild['data']['attr']['data-taxon-id'] == $activeChild['taxonID'] ) {

                        $currParentChildren[$i]['state'] = 'open';
                        $currParentChildren[$i]['children'] = $structure;
                        break;
                    }
                }
            }
            // search for taxon we are looking for and highlight it
            else {
                foreach( $currParentChildren as $i => $currParentChild ) {
                    if( $currParentChild['data']['attr']['data-taxon-id'] == $taxonID ) {
                        $currParentChildren[$i]['data']['title'] =
                                '<img src="images/arrow_right.png">&nbsp;' .
                                $currParentChildren[$i]['data']['title'];
                        break;
                    }
                }
            }

            $structure = $currParentChildren;
            $activeChild = $currParent;

            if( $currParent['taxonID'] == 0 && $citations != null ) break;

            $bParentFound = true;
        }

        // check if we found something
        if( $bParentFound ) {
            // check if we have a periodical structure
            if( $citations != null ) {
                $citations[$refIndex]['children'] = $structure;
                $citations[$refIndex]['state'] = 'open';
                $return = $citations;
            }
            // if not just return the found single structure
            else {
//                error_log(var_export($structure,true));
                $return = $structure;
            }
        }
    }

    return $return;
}
