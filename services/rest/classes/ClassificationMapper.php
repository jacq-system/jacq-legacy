<?php
class ClassificationMapper extends Mapper
{

/**
 * Fetch a list of all references (which have a classification attached)
 * @param string $referenceType Type of references to return (citation, person, service, specimen, periodical)
 * @return array References information
 */
public function getReferences ($referenceType)
{
    $sql = "";
    switch(trim($referenceType)) {
        case 'person':
            break;
        case 'service':
            break;
        case 'specimen':
            break;
        case 'citation':
            $sql = "SELECT l.titel AS `name`, l.citationID AS `id`
                    FROM tbl_lit l
                     LEFT JOIN tbl_tax_synonymy ts ON ts.source_citationID = l.citationID
                     LEFT JOIN tbl_tax_classification tc ON tc.tax_syn_ID = ts.tax_syn_ID
                    WHERE l.category LIKE '%classification%'
                     AND ts.tax_syn_ID IS NOT NULL
                     AND tc.classification_id IS NOT NULL
                    GROUP BY ts.source_citationID
                    ORDER BY `name`";
            break;
        case 'periodical':
            $sql = "SELECT lp.periodical AS `name`, l.periodicalID AS `id`
                    FROM tbl_lit_periodicals lp
                     LEFT JOIN tbl_lit l ON l.periodicalID = lp.periodicalID
                     LEFT JOIN tbl_tax_synonymy ts ON ts.source_citationID = l.citationID
                     LEFT JOIN tbl_tax_classification tc ON tc.tax_syn_ID = ts.tax_syn_ID
                    WHERE l.category LIKE '%classification%'
                     AND ts.tax_syn_ID IS NOT NULL
                     AND tc.classification_id IS NOT NULL
                    GROUP BY l.periodicalID
                    ORDER BY `name`";
            break;
        default:
    }
    if ($sql) {
        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    } else {
        return array();
    }
}

/**
 * Get classification children of a given taxonID according to a given reference
 * @param string $referenceType Type of reference (periodical, citation, service, etc.)
 * @param int $referenceID ID of reference
 * @param int $taxonID optional ID of taxon
 * @return array structured array with classification information
 */
public function getChildren($referenceType, $referenceID, $taxonID = 0)
{
    $results = array();

    switch( $referenceType ) {
        case 'periodical':
            // get all citations which belong to the given periodical
            $sql = "SELECT `herbar_view`.GetProtolog(l.citationID) AS referenceName, l.citationID AS referenceID
                    FROM tbl_lit l
                     LEFT JOIN tbl_tax_synonymy ts ON ts.source_citationID = l.citationID
                     LEFT JOIN tbl_tax_classification tc ON tc.tax_syn_ID = ts.tax_syn_ID
                    WHERE ts.tax_syn_ID IS NOT NULL
                     AND tc.classification_id IS NOT NULL
                     AND l.periodicalID = " . intval($referenceID) . "
                    GROUP BY ts.source_citationID
                    ORDER BY referenceName";
            $dbRows = $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
            foreach ($dbRows as $dbRow) {
                $results[] = array(
                    "taxonID"       => 0,
                    "referenceId"   => $dbRow['referenceID'],
                    "referenceName" => $dbRow['referenceName'],
                    "referenceType" => "citation",
                    "hasChildren"   => true,
                    "hasType"       => false,
                    "hasSpecimen"   => false,
                );
            }
            break;
        case 'citation':
        default:
            // basic query
            $sql = "SELECT `herbar_view`.GetScientificName( ts.`taxonID`, 0 ) AS `scientificName`,
                           ts.taxonID,
                           ANY_VALUE(ts.tax_syn_ID) AS `tax_syn_ID`,
                           ANY_VALUE(tc.`number`) AS `number`,
                           ANY_VALUE(tc.`order`) AS `order`,
                           tr.rank_abbr,
                           tr.rank_hierarchy,
                           MAX(`has_children`.`tax_syn_ID` IS NOT NULL) AS `hasChildren`,
                           MAX(`has_synonyms`.`tax_syn_ID` IS NOT NULL) AS `hasSynonyms`,
                           (`has_basionym`.`basID`         IS NOT NULL) AS `hasBasionym`
                    FROM tbl_tax_synonymy ts
                     LEFT JOIN tbl_tax_species tsp ON ts.taxonID = tsp.taxonID
                     LEFT JOIN tbl_tax_rank tr ON tsp.tax_rankID = tr.tax_rankID
                     LEFT JOIN tbl_tax_classification tc ON ts.tax_syn_ID = tc.tax_syn_ID
                     LEFT JOIN tbl_tax_synonymy has_synonyms ON (has_synonyms.acc_taxon_ID = ts.taxonID
                                                                 AND has_synonyms.source_citationID = ts.source_citationID)
                     LEFT JOIN tbl_tax_classification has_children_clas ON has_children_clas.parent_taxonID = ts.taxonID
                     LEFT JOIN tbl_tax_synonymy has_children ON (has_children.tax_syn_ID = has_children_clas.tax_syn_ID
                                                                 AND has_children.source_citationID = ts.source_citationID)
                     LEFT JOIN tbl_tax_species has_basionym ON ts.taxonID = has_basionym.taxonID
                    WHERE ts.source_citationID = " . intval($referenceID) . "
                     AND ts.acc_taxon_ID IS NULL ";


            // check if we search for children of a specific taxon
            if ($taxonID > 0) {
                $sql .= "AND tc.parent_taxonID = " . intval($taxonID);
            }
            // .. if not make sure we only return entries which have at least one child
            else {
                $sql .= "AND tc.parent_taxonID IS NULL
                         AND has_children.tax_syn_ID IS NOT NULL";
            }
            $dbRows = $this->db->query($sql . " GROUP BY ts.taxonID ORDER BY `order`, `scientificName`")->fetch_all(MYSQLI_ASSOC);

            foreach( $dbRows as $dbRow ) {
                $results[] = array(
                    "taxonID"       => $dbRow['taxonID'],
                    "referenceId"   => intval($referenceID),
                    "referenceName" => $dbRow['scientificName'],
                    "referenceType" => "citation",
                    "hasChildren"   => ($dbRow['hasChildren'] > 0 || $dbRow['hasSynonyms'] > 0 || $dbRow['hasBasionym']),
                    "hasType"       => $this->hasType($dbRow['taxonID']),
                    "hasSpecimen"   => $this->hasSpecimen($dbRow['taxonID']),
                    "referenceInfo" => array(
                        "number"         => $dbRow['number'],
                        "order"          => $dbRow['order'],
                        "rank_abbr"      => $dbRow['rank_abbr'],
                        "rank_hierarchy" => $dbRow['rank_hierarchy'],
                        "tax_syn_ID"     => $dbRow['tax_syn_ID'],
                    )
                );
            }
            break;
    }

    return $results;
}

/**
 * Get number of classification children who have children themselves of a given taxonID according to a given reference of type citation
 * @param int $referenceID ID of reference (citation)
 * @param int $taxonID ID of taxon
 * @return int
 */
public function getNumberOfChildrenWithChildrenCitation ($referenceID, $taxonID = 0)
{
    $resultNumber = 0;
    $stack = array();

    $stack[] = intval($taxonID);
    do {
        $taxonID = array_pop($stack);

        // basic query
        $sql = "SELECT ts.taxonID,
                       max(`has_children`.`tax_syn_ID` IS NOT NULL) AS `hasChildren`,
                       max(`has_synonyms`.`tax_syn_ID` IS NOT NULL) AS `hasSynonyms`,
                       max(`has_basionym`.`basID` IS NOT NULL) AS `hasBasionym`
                FROM tbl_tax_synonymy ts
                 LEFT JOIN tbl_tax_species tsp ON ts.taxonID = tsp.taxonID
                 LEFT JOIN tbl_tax_rank tr ON tsp.tax_rankID = tr.tax_rankID
                 LEFT JOIN tbl_tax_classification tc ON ts.tax_syn_ID = tc.tax_syn_ID
                 LEFT JOIN tbl_tax_synonymy has_synonyms ON (has_synonyms.acc_taxon_ID = ts.taxonID AND has_synonyms.source_citationID = ts.source_citationID)
                 LEFT JOIN tbl_tax_classification has_children_clas ON has_children_clas.parent_taxonID = ts.taxonID
                 LEFT JOIN tbl_tax_synonymy has_children ON (has_children.tax_syn_ID = has_children_clas.tax_syn_ID AND has_children.source_citationID = ts.source_citationID)
                 LEFT JOIN tbl_tax_species has_basionym ON ts.taxonID = has_basionym.taxonID
                WHERE ts.source_citationID = " . intval($referenceID) . "
                 AND ts.acc_taxon_ID IS NULL ";

        // check if we search for children of a specific taxon
        if( $taxonID > 0 ) {
            $sql .= " AND tc.parent_taxonID = $taxonID ";
        }
        // .. if not make sure we only return entries which have at least one child
        else {
            $sql .= " AND tc.parent_taxonID IS NULL
                      AND has_children.tax_syn_ID IS NOT NULL ";
        }

        $dbRows = $this->db->query($sql . " GROUP BY ts.taxonID")->fetch_all(MYSQLI_ASSOC);

        // process all results and create response from it
        foreach( $dbRows as $dbRow ) {
            if ($dbRow['hasChildren'] > 0 || $dbRow['hasSynonyms'] > 0 || $dbRow['hasBasionym']) {
                $stack[] = $dbRow['taxonID'];
                $resultNumber++;
            }
        }
    } while (!empty($stack));

    return $resultNumber;
}

/**
 * fetch synonyms (and basionym) for a given taxonID, according to a given reference
 * @param string $referenceType type of reference (periodical, citation, service, etc.)
 * @param int $referenceID ID of reference
 * @param int $taxonID ID of taxon name
 * @return array List of synonyms including extra information
 */
public function getSynonyms($referenceType, $referenceID, $taxonID)
{
    $results = array();
    $basID = 0;
    $basionymResult = null;

    // make sure we have correct parameters
    $referenceIDfiltered = intval($referenceID);
    $taxonIDfiltered     = intval($taxonID);

    // check if we have a basionym
    $sql = "SELECT `herbar_view`.GetScientificName(`ts`.`basID`, 0) AS `scientificName`, ts.basID
            FROM tbl_tax_species ts
            WHERE ts.taxonID = $taxonIDfiltered
             AND ts.basID IS NOT NULL";
    $dbRows = $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    if (count($dbRows) > 0) {
        $basID = $dbRows[0]['basID'];

        $basionymResult = array(
            "taxonID"       => $basID,
            "referenceName" => $dbRows[0]['scientificName'],
            "referenceId"   => $referenceIDfiltered,
            "referenceType" => $referenceType,
            "hasType"       => $this->hasType($basID),
            "hasSpecimen"   => $this->hasSpecimen($basID),
            "referenceInfo" => array(
                "type"          => "homotype",
                "cited"         => false
            )
        );
    }

    switch( $referenceType ) {
        case 'citation':
            $sql = "SELECT `herbar_view`.GetScientificName( ts.taxonID, 0 ) AS scientificName, ts.taxonID, (tsp.basID = tsp_source.basID) AS homotype
                    FROM tbl_tax_synonymy ts
                     LEFT JOIN tbl_tax_species tsp ON tsp.taxonID = ts.taxonID
                     LEFT JOIN tbl_tax_species tsp_source ON tsp_source.taxonID = ts.acc_taxon_ID
                    WHERE ts.acc_taxon_ID = $taxonIDfiltered
                     AND source_citationID = $referenceIDfiltered";
            $dbRows = $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);

            foreach( $dbRows as $dbRow ) {
                // ignore if synonym is basionym
                if( $dbRow['taxonID'] == $basID ) {
                    $basionymResult["referenceInfo"]["cited"] = true;
                } else {
                    $results[] = array(
                        "taxonID"       => $dbRow['taxonID'],
                        "referenceName" => $dbRow['scientificName'],
                        "referenceId"   => $referenceIDfiltered,
                        "referenceType" => $referenceType,
                        "hasType"       => $this->hasType($dbRow['taxonID']),
                        "hasSpecimen"   => $this->hasSpecimen($dbRow['taxonID']),
                        "referenceInfo" => array(
                            "type"          => ($dbRow['homotype'] > 0) ? "homotype" : "heterotype",
                            'cited'         => true
                        )
                    );
                }
            }
            break;
    }

    // if we have a basionym, prepend it to list
    if( $basionymResult != null ) {
        array_unshift($results, $basionymResult);
    }

    return $results;
}

/**
 * Return (other) references for this name which include them in their classification
 * @param int $taxonID ID of name to look for
 * @param int $excludeReferenceId optional Reference-ID to exclude (to avoid returning the "active" reference)
 * @return array List of references which do include this name
 */
public function getNameReferences($taxonID, $excludeReferenceId = 0)
{
    $taxonIDfiltered = intval($taxonID);
    $excludeReferenceIdfiltered = intval($excludeReferenceId);
    // check for valid parameter
    if ($taxonIDfiltered <= 0) {
        return array();
    }

    $results = array();
    // direct integration of tbl_lit_... for (much) faster sorting whe using ORDER BY
    // only select entries which are part of a classification, so either tc.tax_syn_ID or has_children_syn.tax_syn_ID must not be NULL
    //ONLY_FULL_GROUP_BY,
    $sql = "SELECT ts.source_citationID AS referenceId, `herbar_view`.GetProtolog(`ts`.`source_citationID`) AS `referenceName`
            FROM tbl_tax_synonymy ts
             LEFT JOIN tbl_tax_classification tc ON tc.tax_syn_ID = ts.tax_syn_ID
             LEFT JOIN tbl_tax_classification has_children ON has_children.parent_taxonID = ts.taxonID
             LEFT JOIN tbl_tax_synonymy has_children_syn ON (    has_children_syn.tax_syn_ID = has_children.tax_syn_ID
                                                             AND has_children_syn.source_citationID = ts.source_citationID)
             LEFT JOIN tbl_lit l ON l.citationID = ts.source_citationID
             LEFT JOIN tbl_lit_authors le ON le.autorID = l.editorsID
             LEFT JOIN tbl_lit_authors la ON la.autorID = l.autorID
             LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID = l.periodicalID
            WHERE ts.source_citationID IS NOT NULL
             AND ts.acc_taxon_ID IS NULL
             AND ts.taxonID = $taxonIDfiltered
             AND (tc.tax_syn_ID IS NOT NULL OR has_children_syn.tax_syn_ID IS NOT NULL)
            GROUP BY ts.source_citationID
            ORDER BY la.autor, l.jahr, le.autor, l.suptitel, lp.periodical, l.vol, l.part, l.pp";
    $dbRows = $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    foreach ($dbRows as $dbRow) {
        // check for exclude id
        if ($dbRow['referenceId'] != $excludeReferenceIdfiltered) {

            // check if there any classification children of the taxonID according to this reference?
            $child = $this->db->query("SELECT ts.taxonID
                                       FROM tbl_tax_synonymy ts
                                        LEFT JOIN tbl_tax_classification tc ON ts.tax_syn_ID = tc.tax_syn_ID
                                       WHERE ts.source_citationID = " . $dbRow['referenceId'] . "
                                        AND ts.acc_taxon_ID IS NULL
                                        AND tc.parent_taxonID = $taxonIDfiltered")
                        ->fetch_assoc();
            if ($child) {
                $hasChildren = true;
            } else {
                $child = $this->db->query("SELECT ts.taxonID
                                           FROM tbl_tax_synonymy ts
                                           WHERE ts.source_citationID = " . $dbRow['referenceId'] . "
                                            AND ts.acc_taxon_ID = $taxonIDfiltered")
                            ->fetch_assoc();
                $hasChildren = ($child) ? true : false;
            }

            $results[] = array(
                "referenceName" => $dbRow['referenceName'],
                "referenceId"   => $dbRow['referenceId'],
                "referenceType" => "citation",
                "taxonID"       => $taxonIDfiltered,
                "hasChildren"   => $hasChildren,
                "hasType"       => false,
                "hasSpecimen"   => false
            );
        }
    }

    // Fetch all synonym rows (if any)
    // direct integration of tbl_lit_... for (much) faster sorting whe using ORDER BY
    // ONLY_FULL_GROUP_BY,
    $sqlSyns = "SELECT ts.source_citationID AS referenceId,
                       `herbar_view`.GetProtolog(`ts`.`source_citationID`) AS `referenceName`,
                       ANY_VALUE(ts.acc_taxon_ID) AS acceptedId
                FROM tbl_tax_synonymy ts
                 LEFT JOIN tbl_lit l ON l.citationID = ts.source_citationID
                 LEFT JOIN tbl_lit_authors le ON le.autorID = l.editorsID
                 LEFT JOIN tbl_lit_authors la ON la.autorID = l.autorID
                 LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID = l.periodicalID
                WHERE ts.source_citationID IS NOT NULL
                 AND ts.source_citationID != $excludeReferenceIdfiltered
                 AND ts.acc_taxon_ID IS NOT NULL
                 AND ts.taxonID = $taxonIDfiltered
                GROUP BY ts.source_citationID
                ORDER BY la.autor, l.jahr, le.autor, l.suptitel, lp.periodical, l.vol, l.part, l.pp";
    $dbSyns = $this->db->query($sqlSyns)->fetch_all(MYSQLI_ASSOC);
    foreach ($dbSyns as $dbSyn) {
        // check if the accepted taxon is part of a classification
        // only select entries which are part of a classification, so either tc.tax_syn_ID or has_children_syn.tax_syn_ID must not be NULL
        $result = $this->db->query("SELECT ts.source_citationID AS referenceId
                                    FROM tbl_tax_synonymy ts
                                     LEFT JOIN tbl_tax_classification tc ON ts.tax_syn_ID = tc.tax_syn_ID
                                     LEFT JOIN tbl_tax_classification has_children ON has_children.parent_taxonID = ts.taxonID
                                     LEFT JOIN tbl_tax_synonymy has_children_syn ON (    has_children_syn.tax_syn_ID = has_children.tax_syn_ID
                                                                                     AND has_children_syn.source_citationID = ts.source_citationID)
                                    WHERE ts.source_citationID = " . $dbSyn['referenceId'] . "
                                     AND ts.acc_taxon_ID IS NULL
                                     AND ts.taxonID = " . $dbSyn['acceptedId'] . "
                                     AND (tc.tax_syn_ID IS NOT NULL OR has_children_syn.tax_syn_ID IS NOT NULL)");
        // and add the entry only if the accepted taxon is part of a classification
        if ($result->num_rows > 0) {
            $results[] = array(
                "referenceName" => '= ' . $dbSyn['referenceName'],  //  mark the reference Name as synonym
                "referenceId"   => $dbSyn['referenceId'],
                "referenceType" => "citation",
                "taxonID"       => $taxonIDfiltered,
                "hasChildren"   => false,
                "hasType"       => false,
                "hasSpecimen"   => false,
            );
        }
    }

    return $results;
}

/**
 * Get the parent entry of a given reference
 * @param string $referenceType type of reference (periodical, citation, service, etc.)
 * @param int $referenceID ID of reference
 * @param int $taxonID ID of taxon name
 * @return array data of the parent
 */
public function getParent($referenceType, $referenceId, $taxonID)
{
    $parent = null;
    $referenceIDfiltered = intval($referenceId);
    $taxonIDfiltered = intval($taxonID);

    switch( $referenceType ) {
        case 'periodical':
            // periodical is a top level element, so no parent
            break;
        case 'citation':
        default:
            // only necessary if taxonID is not null
            if( $taxonIDfiltered > 0 ) {
                $dbRows = $this->db->query("SELECT `herbar_view`.GetScientificName( ts.`taxonID`, 0 ) AS `referenceName`, tc.number, tc.order, ts.taxonID
                                            FROM tbl_tax_synonymy ts
                                             LEFT JOIN tbl_tax_classification tc ON ts.tax_syn_ID = tc.tax_syn_ID
                                             LEFT JOIN tbl_tax_classification tcchild ON ts.taxonID = tcchild.parent_taxonID
                                             LEFT JOIN tbl_tax_synonymy tschild ON (    tschild.source_citationID = ts.source_citationID
                                                                                    AND tcchild.tax_syn_ID = tschild.tax_syn_ID)
                                            WHERE ts.source_citationID = $referenceIDfiltered
                                             AND ts.acc_taxon_ID IS NULL
                                             AND tschild.taxonID = $taxonIDfiltered")->fetch_all(MYSQLI_ASSOC);
                // check if we found a parent
                if (count($dbRows) > 0) {
                    $dbRow = $dbRows[0];
                    $parent = array(
                        "taxonID" => $dbRow['taxonID'],
                        "referenceId" => $referenceIDfiltered,
                        "referenceName" => $dbRow['referenceName'],
                        "referenceType" => "citation",
                        "hasType" => $this->hasType($dbRow['taxonID']),
                        "hasSpecimen" => $this->hasSpecimen($dbRow['taxonID']),
                        "referenceInfo" => array(
                            "number" => $dbRow['number'],
                            "order" => $dbRow['order']
                        )
                    );
                }
                // if not we either have a synonym and have to search for an accepted taxon or have to return the citation entry
                else {
                    $accTaxon = $this->db->query("SELECT `herbar_view`.GetScientificName( taxonID, 0 ) AS referenceName, acc_taxon_ID
                                                  FROM tbl_tax_synonymy
                                                  WHERE taxonID = $taxonIDfiltered
                                                   AND source_citationID = $referenceIDfiltered
                                                   AND acc_taxon_ID IS NOT NULL")
                                         ->fetch_assoc();
                    // if we have found an accepted taxon for our synonym then return it
                    if ($accTaxon) {
                        $parent = array(
                            "taxonID" => $accTaxon['acc_taxon_ID'],
                            "referenceId" => $referenceIDfiltered,
                            "referenceName" => $accTaxon['referenceName'],
                            "referenceType" => "citation",
                            "hasType" => $this->hasType($accTaxon['acc_taxon_ID']),
                            "hasSpecimen" => $this->hasSpecimen($accTaxon['acc_taxon_ID'])
                        );
                    }
                    // if not we have to return the citation entry
                    else {
                        $dbRows = $this->db->query("SELECT `herbar_view`.GetProtolog(l.citationID) AS referenceName, l.citationID AS referenceId
                                                    FROM tbl_lit l
                                                    WHERE l.citationID = $referenceIDfiltered")->fetch_all(MYSQLI_ASSOC);
                        if (count($dbRows) > 0) {
                            $dbRow = $dbRows[0];
                            $parent = array(
                                "taxonID" => 0,
                                "referenceId" => $dbRow['referenceId'],
                                "referenceName" => $dbRow['referenceName'],
                                "referenceType" => "citation",
                                "hasType" => false,
                                "hasSpecimen" => false
                            );
                        }
                    }
                }
            }
            // find the top-level periodical entry
            else {
                $dbRows = $this->db->query("SELECT lp.periodical AS referenceName, l.periodicalID AS referenceId
                                            FROM tbl_lit_periodicals lp
                                             LEFT JOIN tbl_lit l ON l.periodicalID = lp.periodicalID
                                            WHERE l.citationID = $referenceIDfiltered")->fetch_all(MYSQLI_ASSOC);
                if (count($dbRows) > 0) {
                    $dbRow = $dbRows[0];
                    $parent = array(
                        "taxonID" => 0,
                        "referenceId" => $dbRow['referenceId'],
                        "referenceName" => $dbRow['referenceName'],
                        "referenceType" => "periodical",
                        "hasType" => false,
                        "hasSpecimen" => false
                    );
                }
            }
            break;
    }

    // return results
    return $parent;
}

/**
 * Get statistics information of a given reference
 * @param int $referenceID ID of reference
 * @return array structured array with statistics information
 */
public function getPeriodicalStatistics($referenceID)
{
    $referenceIDfiltered = intval($referenceID);
    $results = array();

    $results["nrAccTaxa"] = $this->db->query("SELECT count(*) AS number
                                              FROM tbl_tax_synonymy
                                              WHERE source_citationID = $referenceIDfiltered
                                               AND acc_taxon_ID IS NULL")
                                 ->fetch_assoc()['number'];

    $results["nrSynonyms"] = $this->db->query("SELECT count(*) AS number
                                               FROM tbl_tax_synonymy
                                               WHERE source_citationID = $referenceIDfiltered
                                                AND acc_taxon_ID IS NOT NULL")
                                  ->fetch_assoc()['number'];

    $dbRowsAcc = $this->db->query("SELECT tr.rank_plural, tr.rank_hierarchy, count(tr.tax_rankID) AS number
                                   FROM tbl_tax_synonymy ts
                                    LEFT JOIN tbl_tax_species tsp ON ts.taxonID = tsp.taxonID
                                    LEFT JOIN tbl_tax_rank tr ON tsp.tax_rankID = tr.tax_rankID
                                   WHERE source_citationID = $referenceIDfiltered
                                    AND acc_taxon_ID IS NULL
                                   GROUP BY tr.tax_rankID
                                   ORDER BY tr.rank_hierarchy")
                          ->fetch_all(MYSQLI_ASSOC);
    $dbRowsSyn = $this->db->query("SELECT tr.rank_plural, tr.rank_hierarchy, count(tr.tax_rankID) AS number
                                   FROM tbl_tax_synonymy ts
                                    LEFT JOIN tbl_tax_species tsp ON ts.taxonID = tsp.taxonID
                                    LEFT JOIN tbl_tax_rank tr ON tsp.tax_rankID = tr.tax_rankID
                                   WHERE source_citationID = $referenceIDfiltered
                                    AND acc_taxon_ID IS NOT NULL
                                   GROUP BY tr.tax_rankID
                                   ORDER BY tr.rank_hierarchy")
                          ->fetch_all(MYSQLI_ASSOC);

    $rows = array();
    foreach( $dbRowsAcc as $dbRow ) {
        $rows[$dbRow['rank_hierarchy']]['acc'] = array("rank" => $dbRow['rank_plural'], "number" => $dbRow['number']);
    }
    foreach( $dbRowsSyn as $dbRow ) {
        $rows[$dbRow['rank_hierarchy']]['syn'] = array("rank" => $dbRow['rank_plural'], "number" => $dbRow['number']);
    }

    $results["ranks"] = array();
    foreach ($rows as $row) {
        if (isset($row['acc'])) {
            $buffer['rank'] = $row['acc']['rank'];
            $buffer['nrAccTaxa'] = $row['acc']['number'];
        } else {
            $buffer['nrAccTaxa'] = 0;
        }
        if (isset($row['syn'])) {
            $buffer['rank'] = $row['syn']['rank'];
            $buffer['nrSynTaxa'] = $row['syn']['number'];
        } else {
            $buffer['nrSynTaxa'] = 0;
        }
        $results["ranks"][] = $buffer;
    }

    return $results;
}



////////////////////////////// private functions //////////////////////////////

/**
 * Are there any specimen records of a given taxonID?
 * @param int $taxonID ID of taxon
 * @return bool specimen record(s) present?
 */
private function hasSpecimen ($taxonID)
{
    $result = $this->db->query("SELECT specimen_ID FROM tbl_specimens WHERE taxonID = " . intval($taxonID));
    return ($result->num_rows > 0);
}

/**
 * Are there any type records of a given taxonID?
 * @param int $taxonID ID of taxon
 * @return bool type record(s) present?
 */
private function hasType ($taxonID)
{
    $result = $this->db->query("SELECT s.specimen_ID
                                FROM tbl_specimens s
                                 LEFT JOIN tbl_specimens_types tst ON tst.specimenID = s.specimen_ID
                                WHERE tst.typusID IS NOT NULL
                                 AND tst.taxonID = " . intval($taxonID));
    return ($result->num_rows > 0);
}

}