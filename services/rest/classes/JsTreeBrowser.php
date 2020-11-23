<?php
class JsTreeBrowser
{
    /**
     *
     * @var ClassificationMapper
     */
    protected $classificationMapper;

    public function __construct($classificationMapper)
    {
        $this->classificationMapper = $classificationMapper;
    }

    /**
     * Returns the next classification-level below a given taxonID or top level (if taxonID=0)
     * @param type $referenceType Type of reference (periodical, citation, service, etc.)
     * @param type $referenceID ID of reference
     * @param type $taxonID optional ID of taxon
     * @return string result formatted for direct use with jsTree
     */
    public function getChildren ($referenceType, $referenceID, $taxonID = 0)
    {
        // only execute code if we have a valid reference ID
        if (intval($referenceID) <= 0) {
            return array();
        }

        $return = array();

        $infoLink = "&nbsp;<span class='infoBox'><img src='images/information.png'></span>";

        // check for synonyms
        $synonyms = $this->classificationMapper->getSynonyms($referenceType, $referenceID, $taxonID);
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

        // find all classification children
        $children = $this->classificationMapper->getChildren($referenceType, $referenceID, $taxonID);
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
}