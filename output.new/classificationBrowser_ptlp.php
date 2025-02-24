<?php
// this is the local pass through landing page for all ajax-operations of the classification browser

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Jacq\Settings;

require_once __DIR__ . '/vendor/autoload.php';

$config = Settings::Load();
$client = new Client(['base_uri' => $config->get('JACQ_SERVICES')]);

header('Content-Type: application/json');

$type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);
switch ($type) {
    case 'jstree':
        $referenceType = trim($_GET['referenceType'] ?? '');
        $referenceID = intval(filter_input(INPUT_GET, 'referenceId', FILTER_SANITIZE_NUMBER_INT));
        if (empty($referenceType) || empty($referenceID) || !in_array($referenceType, ['citation', 'person', 'service', 'specimen', 'periodical'])) {
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
            echo $client->request('GET', $config->get('JACQ_SERVICES') . "classification/nameReferences/$taxonID",
                                   ['query' => ["excludeReferenceId" => $excludeReferenceId,
                                                "insertSeries"       => $insertSeries]])
                        ->getBody()
                        ->getContents();
        }
        break;
    case 'scientificNameAc':
        echo $client->request('GET', $config->get('JACQ_SERVICES')
                                           . 'autocomplete/scientificNames/'
                                           . urlencode($_GET['term'] ?? ''))
                    ->getBody()
                    ->getContents();
        break;
}

////////////////////////////// service functions //////////////////////////////
///////////////////////// for classification browser //////////////////////////


/**
 * Returns the next classification-level below a given taxonID or top level (if taxonID=0)
 *
 * @param string $referenceType Type of reference (periodical, citation, service, etc.)
 * @param int $referenceID ID of reference
 * @param int $taxonID optional ID of taxon
 * @param int $insertSeries optional ID of cication-Series to insert
 * @return array result formatted for direct use with jsTree
 * @throws GuzzleException
 */
function getChildrenJsTree(string $referenceType, int $referenceID, int $taxonID = 0, int $insertSeries = 0): array
{
    // only execute code if we have a valid reference ID
    if ($referenceID <= 0) {
        return array();
    }

    $return = array();

    $infoLink = "&nbsp;<span class='infoBox'><img src='images/information.png' alt='info'></span>";

    // check for synonyms
    if ($taxonID) {
        $synonyms = getJson("classification/synonyms/", array($referenceType, $referenceID, $taxonID), array('insertSeries' => $insertSeries));
        if (count($synonyms) > 0) {
            foreach ($synonyms as $synonym) {
                if (empty($synonym['insertedCitation'])) {
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
                } else {
                    $entry = array(
                        "data" => array(
                            "title" => $synonym["referenceName"],
                            "attr" => array(
                                "data-taxon-id" => $synonym["taxonID"],
                                "data-reference-id" => $synonym["referenceId"],
                                "data-reference-type" => $synonym["referenceType"],
                            )
                        ),
                        'icon' => 'images/book_open.png',
                    );
                    // check if we have further children
                    if ($synonym['hasChildren']) {
                        $entry['state'] = 'closed';
                    }
                    $return[] = $entry;
                }
            }
        }
    }

    // find all classification children
    $children = getJson("classification/children/", array($referenceType, $referenceID), array("taxonID" => $taxonID, "insertSeries" => $insertSeries));
    foreach ($children as $child) {
        if (empty($child['insertedCitation'])) {
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
                    "title" => $title . $infoLink . $typeLink . $specimenLink,
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
                    $entry['data']['title'] = '<i><b>' . $child['referenceInfo']['number'] . '</b></i>&nbsp;' . $entry['data']['title'];
                }
            }

            // check if we have further children
            if ($child['hasChildren']) {
                $entry['state'] = 'closed';
            }

            // save entry for return
            $return[] = $entry;
        } else {
            $entry = array(
                "data" => array(
                    "title" => $child["referenceName"],
                    "attr" => array(
                        "data-taxon-id" => $child["taxonID"],
                        "data-reference-id" => $child["referenceId"],
                        "data-reference-type" => $child["referenceType"],
                    )
                ),
                'icon' => 'images/book_open.png',
            );
            // check if we have further children
            if ($child['hasChildren']) {
                $entry['state'] = 'closed';
            }
            $return[] = $entry;
        }
    }

    return $return;
}

/**
 * Returns the whole classification tree filtered down to a given taxonID
 *
 * @param string $referenceType Type of reference (periodical, citation, service, etc.)
 * @param int $referenceId ID of reference
 * @param int $taxonID ID of taxon
 * @param int $insertSeries optional ID of cication-Series to insert
 * @return array result formatted for direct use with jsTree
 * @throws GuzzleException
 */
function getFilteredJsTree(string $referenceType, int $referenceId, int $taxonID, int $insertSeries = 0): array
{
    $return = array();
    // collection of references to search for the taxonID in
    $references = array(
        array('referenceType' => $referenceType, 'referenceId' => $referenceId, 'taxonID' => $taxonID)
    );
    // optional citations which we look for (only for periodicals)
    $citations = null;

    // check if we have a periodical, since then we have to fetch all citations first
    if ($referenceType == 'periodical') {
        $citations = getChildrenJsTree($referenceType, $referenceId, $taxonID, $insertSeries);

        // convert all fetched citations to references to look for
        $references = array();
        foreach ($citations as $i => $citation) {
            $references[$i] = array(
                'referenceType' => $citation['data']['attr']['data-reference-type'],
                'referenceId' => $citation['data']['attr']['data-reference-id'],
                'taxonID' => $taxonID
            );
        }
    }

    // search children for all references
    foreach ($references as $refIndex => $reference) {
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
        while (($currParent = getJson("classification/parent/", array($currParent['referenceType'], $currParent['referenceId'], $currParent['taxonID']))) != null) {
            $currParentChildren = getChildrenJsTree(
                $currParent['referenceType'],
                $currParent['referenceId'],
                $currParent['taxonID'],
                $insertSeries
            );

            // find active child among all children
            if ($activeChild != null) {
                foreach ($currParentChildren as $i => $currParentChild) {
                    if ($currParentChild['data']['attr']['data-reference-type'] == $activeChild['referenceType'] &&
                        $currParentChild['data']['attr']['data-reference-id'] == $activeChild['referenceId'] &&
                        $currParentChild['data']['attr']['data-taxon-id'] == $activeChild['taxonID']) {

                        $currParentChildren[$i]['state'] = 'open';
                        $currParentChildren[$i]['children'] = $structure;
                        break;
                    }
                }
            } // search for taxon we are looking for and highlight it
            else {
                foreach ($currParentChildren as $i => $currParentChild) {
                    if ($currParentChild['data']['attr']['data-taxon-id'] == $taxonID) {
                        $currParentChildren[$i]['data']['title'] =
                            '<img src="images/arrow_right.png" alt="->">&nbsp;' .
                            $currParentChildren[$i]['data']['title'];
                        break;
                    }
                }
            }

            $structure = $currParentChildren;
            $activeChild = $currParent;

            if ($currParent['taxonID'] == 0 && $citations != null) {
                break;
            }

            $bParentFound = true;
        }

        // check if we found something
        if ($bParentFound) {
            // check if we have a periodical structure
            if ($citations != null) {
                $citations[$refIndex]['children'] = $structure;
                $citations[$refIndex]['state'] = 'open';
                $return = $citations;
            } // if not just return the found single structure
            else {
                $return = $structure;
            }
        }
    }

    return $return;
}

/**
 * Returns the json-decoded response of a JACQ-services resource
 *
 * @param string $resource resource-part of the url
 * @param array $pathParams path parameters
 * @param array|null $queryParams optional query parameters
 * @return array the response
 * @throws GuzzleException
 * @global Client $client guzzle client for REST-operations
 */
function getJson(string $resource, array $pathParams, ?array $queryParams = array()): array
{
    global $client;

    if (!empty($queryParams)) {
        $response = $client->request('GET', $resource . implode('/', $pathParams), ['query' => $queryParams])
                           ->getBody()
                           ->getContents();
    } else {
        $response = $client->request('GET', $resource . implode('/', $pathParams))
                           ->getBody()
                           ->getContents();
    }
    return json_decode($response, true);
}
