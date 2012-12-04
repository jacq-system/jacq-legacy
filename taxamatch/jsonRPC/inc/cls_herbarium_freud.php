<?php
/**
 * Biological Namestring parser and comparison tool based on
 * TAXAMATCH developed by Tony Rees, November 2008 (Tony.Rees@csiro.au)
 *
 * establishes a class with the public function "getMatches" -
 * subsequently calculates the distance based on the MDLD algorithm implemented
 * as an UDF in a MYSQL environment.
 *
 * Input Namestrings seperated by LF are compared against a defined reference Nameslist
 * and results are provided in an array of matches.
 *
 * The data-structure used here is the one of the virtual herbaria
 *
 * @author Johannes Schachner <joschach@ap4net.at>
 * @since 23.03.2011
 */
require_once('inc/variables.php');

class cls_herbarium_freud extends cls_herbarium_base {

private $matches = array();    // result of getMatches() are stored here
private $currSynonyms20;       // holds temporary list of synonyms20 for current species
private $counterSyns20 = 0;    // counter20 for temporary list

	var $block_limit=2;
	var $limit=4;
/*******************\
|                   |
|  public functions |
|                   |
\*******************/

/**
 * get all possible matches against the virtual herbarium vienna
 *
 * @param String $searchtext taxon string(s) to search for
 * @param bool[optional] $withNearMatch use near_match if true
 * @param bool[optional] $includeCommonNames include the common names in the response
 * @return array result of all searches
 */
public function getMatches ($searchtext, $withNearMatch = false, $includeCommonNames = false)
{
    global $options;

    // catch all output to the console
    ob_start();

    // base definition of the return array
    $matches = array('error'  => '',
                     'result' => array());

    if (!@mysql_connect($options['hrdb']['dbhost'], $options['hrdb']['dbuser'], $options['hrdb']['dbpass']) || !@mysql_select_db($options['hrdb']['dbname'])) {
        $matches['error'] = 'no database connection';
        return $matches;
    }
    mysql_query("SET character set utf8");

    // split the input at newlines into several queries
    $searchItems = preg_split("[\n|\r]", $searchtext, -1, PREG_SPLIT_NO_EMPTY);

    foreach ($searchItems as $searchItem) {
        $searchresult = array();
        $sort1 = $sort2 = $sort3 = array();
//        $fullHit = false;
        $lev = array();
        $ctr = 0;  // how many checks did we do

        if (strpos(trim($searchItem), ' ') === false) {
            $type = 'uni';                                // we're asked for a uninomial

            if ($withNearMatch) {
                $searchItemNearmatch = $this->_near_match($searchItem, false, true); // use near match if desired
                $uninomial           = ucfirst(trim($searchItemNearmatch));
                $lenUninomial        = mb_strlen(trim($searchItemNearmatch), "UTF-8");
            } else {
                $searchItemNearmatch = '';
                $uninomial           = ucfirst(trim($searchItem));
                $lenUninomial        = mb_strlen(trim($searchItem), "UTF-8");
            }

//            $res = mysql_query("SELECT g.genus, f.family, genID, a.author
//                                FROM tbl_tax_genera g, tbl_tax_families f, tbl_tax_authors a
//                                WHERE g.genus = '" . mysql_real_escape_string($uninomial) . "'
//                                 AND g.familyID = f.familyID
//                                 AND g.authorID = a.authorID");
//            if (mysql_num_rows($res) > 0) {
//                $row = mysql_fetch_array($res);
//                $searchresult[] = array('genus'    => $row['genus'],
//                                        'distance' => 0,
//                                        'ratio'    => 1,
//                                        'taxon'    => $row['genus'] . ' ' . $row['author'] . ' (' . $row['family'] . ')',
//                                        'ID'       => $row['genID'],
//                                        'species'  => array());
//                $ctr++;
//            } else {
                // no full hit, so do just the normal search

                // first compare with the genera
                $res = mysql_query("SELECT g.genus, f.family, genID, a.author,
                                     mdld('" . mysql_real_escape_string($uninomial) . "', g.genus, 2, 4) AS mdld
                                    FROM tbl_tax_genera g, tbl_tax_families f, tbl_tax_authors a
                                    WHERE g.familyID = f.familyID
                                     AND g.authorID = a.authorID");
                /**
                 * do the actual calculation of the distances
                 * and decide if the result should be kept
                 */
                while ($row = mysql_fetch_array($res)) {
                    $limit = min($lenUninomial, strlen($row['genus'])) / 2;     // 1st limit of the search
                    if ($row['mdld'] <= 3 && $row['mdld'] < $limit) {           // 2nd limit of the search
                        $searchresult[] = array('genus'    => $row['genus'],
                                                'distance' => $row['mdld'],
                                                'ratio'    => 1 - $row['mdld'] / max(mb_strlen($row['genus'], "UTF-8"), $lenUninomial),
                                                'taxon'    => $row['genus'] . ' ' . $row['author'] . ' (' . $row['family'] . ')',
                                                'ID'       => $row['genID'],
                                                'species'  => array());
                    }
                    $ctr++;
                }

                // then with the families
                $res = mysql_query("SELECT family,  familyID,
                                     mdld('" . mysql_real_escape_string($uninomial) . "', family, 2, 4) AS mdld
                                    FROM tbl_tax_families");
                /**
                 * do the actual calculation of the distances
                 * and decide if the result should be kept
                 */
                while ($row = mysql_fetch_array($res)) {
                    $limit = min($lenUninomial, strlen($row['family'])) / 2;     // 1st limit of the search
                    if ($row['mdld'] <= 3 && $row['mdld'] < $limit) {            // 2nd limit of the search
                        $searchresult[] = array('genus'    => '',
                                                'distance' => $row['mdld'],
                                                'ratio'    => 1 - $row['mdld'] / max(mb_strlen($row['family'], "UTF-8"), $lenUninomial),
                                                'taxon'    => $row['family'],
                                                'ID'       => $row['familyID'],
                                                'species'  => array());
                    }
                    $ctr++;
                }

//            }

            // if there's more than one hit, sort them (faster here than within the db)
            if (count($searchresult) > 1) {
                foreach ($searchresult as $key => $row) {
                    $sort1[$key] = $row['distance'];
                    $sort2[$key] = $row['ratio'];
                    $sort3[$key] = $row['taxon'];
                }
                array_multisort($sort1, SORT_NUMERIC, $sort2, SORT_DESC, SORT_NUMERIC, $sort3, $searchresult);
            }
        } else {
            $type = 'multi';

            // parse the taxon string
            $parts = $this->_tokenizeTaxa($searchItem);

            // use near match if desired
            if ($withNearMatch) {
                $parts['genus']      = $this->_near_match($parts['genus'], false, true);
                $parts['subgenus']   = $this->_near_match($parts['subgenus'], false, true);
                $parts['epithet']    = $this->_near_match($parts['epithet'], true);
                $parts['subepithet'] = $this->_near_match($parts['subepithet'], true);
                $searchItemNearmatch = $this->_formatTaxon($parts);
            } else {
                $searchItemNearmatch = '';
            }

            // distribute the parsed string to different variables and calculate the (real) length
            $genus[0]    = ucfirst($parts['genus']);
            $lenGenus[0] = mb_strlen($parts['genus'], "UTF-8");
            $genus[1]    = ucfirst($parts['subgenus']);              // subgenus (if any)
            $lenGenus[1] = mb_strlen($parts['subgenus'], "UTF-8");   // real length of subgenus
            $epithet     = $parts['epithet'];
            $lenEpithet  = mb_strlen($parts['epithet'], "UTF-8");
            $rank        = $parts['rank'];
            $epithet2    = $parts['subepithet'];
            $lenEpithet2 = mb_strlen($parts['subepithet'], "UTF-8");

            /**
             * first do the search for the genus and subgenus
             * to speed things up we chekc first if there is a full hit
             * (it may not be very likely but the penalty is quite low)
             */
            for ($i = 0; $i < 2; $i++) {
                // first let's see if there is a full hit of the searched genus or subgenus
//                $res = mysql_query("SELECT g.genus, f.family, genID, a.author
//                                    FROM tbl_tax_genera g, tbl_tax_families f, tbl_tax_authors a
//                                    WHERE g.genus = '" . mysql_real_escape_string($genus[$i]) . "'
//                                     AND g.familyID = f.familyID
//                                     AND g.authorID = a.authorID");
//                if (mysql_num_rows($res) > 0) {
//                    $row = mysql_fetch_array($res);
//                    $lev[] = array('genus'    => $row['genus'],
//                                   'distance' => 0,
//                                   'ratio'    => 1,
//                                   'taxon'    => $row['genus'] . ' ' . $row['author'] . ' (' . $row['family'] . ')',
//                                   'ID'       => $row['genID']);
//                    $ctr++;
//                } else {
                    // no full hit, so do just the normal search
                    $res = mysql_query("SELECT g.genus, f.family, genID, a.author,
                                         mdld('" . mysql_real_escape_string($genus[$i]) . "', g.genus, 2, 4) AS mdld
                                        FROM tbl_tax_genera g, tbl_tax_families f, tbl_tax_authors a
                                        WHERE g.familyID = f.familyID
                                         AND g.authorID = a.authorID");

                    /**
                     * do the actual calculation of the distances
                     * and decide if the result should be kept
                     */
                    while ($row = mysql_fetch_array($res)) {
                        $limit = min($lenGenus[$i], strlen($row['genus'])) / 2;     // 1st limit of the search
                        if ($row['mdld'] <= 3 && $row['mdld'] < $limit) {           // 2nd limit of the search
                            $lev[] = array('genus'    => $row['genus'],
                                           'distance' => $row['mdld'],
                                           'ratio'    => 1 - $row['mdld'] / max(mb_strlen($row['genus'], "UTF-8"), $lenGenus[$i]),
                                           'taxon'    => $row['genus'] . ' ' . $row['author'] . ' (' . $row['family'] . ')',
                                           'ID'       => $row['genID']);
                        }
                        $ctr++;
                    }
//                }
                if (empty($genus[1])) break;    // no subgenus, we're finished here
            }

            // if there's more than one hit, sort them (faster here than within the db)
            if (count($lev) > 1) {
                foreach ($lev as $key => $row) {
                    $sort1[$key] = $row['distance'];
                    $sort2[$key] = $row['ratio'];
                    $sort3[$key] = $row['genus'];
                }
                array_multisort($sort1, SORT_NUMERIC, $sort2, SORT_DESC, SORT_NUMERIC, $sort3, $lev);
            }


            /**
             * second do the search for the species and supspecies (if any)
             * if neither species nor subspecies are given, all species are returned
             * only genera which passed the first test will be used here
             */
            foreach ($lev as $key => $val) {
                $lev2 = array();
                $sql = "SELECT ts.synID, ts.taxonID,
                         te0.epithet epithet0, te1.epithet epithet1, te2.epithet epithet2,
                         te3.epithet epithet3, te4.epithet epithet4, te5.epithet epithet5,
                         ta0.author author0, ta1.author author1, ta2.author author2,
                         ta3.author author3, ta4.author author4, ta5.author author5";
                if ($epithet) {  // if an epithet was given, use it
                    $sql .= ", mdld('" . mysql_real_escape_string($epithet) . "', te0.epithet, 4, 5)  as mdld";
                    if ($epithet2 && $rank) {  // if a subepithet was given, use it
                        $sql .= ", mdld('" . mysql_real_escape_string($epithet2) . "', te{$rank}.epithet, 4, 5) as mdld2";
                    }
                }
                $sql .= " FROM tbl_tax_species ts
                           LEFT JOIN tbl_tax_epithets te0 ON te0.epithetID = ts.speciesID
                           LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID = ts.subspeciesID
                           LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID = ts.varietyID
                           LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID = ts.subvarietyID
                           LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID = ts.formaID
                           LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID = ts.subformaID
                           LEFT JOIN tbl_tax_authors ta0 ON ta0.authorID = ts.authorID
                           LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID = ts.subspecies_authorID
                           LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID = ts.variety_authorID
                           LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID = ts.subvariety_authorID
                           LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID = ts.forma_authorID
                           LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID = ts.subforma_authorID
                          WHERE ts.genID = '" . $val['ID'] . "'";
                $res = mysql_query($sql);
                while ($row = mysql_fetch_array($res)) {
                    $name = trim($row['epithet0']);
                    $found = false;
                    if ($epithet) {
                        $distance = $row['mdld'];
                        $limit = min($lenEpithet, mb_strlen($row['epithet0'], "UTF-8")) / 2;                  // 1st limit of the search
                        if (($distance + $val['distance']) <= 4 && $distance <= 4 && $distance <= $limit) {   // 2nd limit of the search
                            if ($epithet2 && $rank) {
                                $limit2 = min($lenEpithet2, mb_strlen($row['epithet' . $rank], "UTF-8")) / 2; // 3rd limit of the search
                                if ($row['mdld2'] <= 4 && $row['mdld2'] <= $limit2) {                         // 4th limit of the search
                                    $found = true;  // we've hit something
                                    $ratio = 1
                                           - $distance / max(mb_strlen($row['epithet0'], "UTF-8"), $lenEpithet)
                                           - $row['mdld2'] / max(mb_strlen($row['epithet' . $rank], "UTF-8"), $lenEpithet2);
                                    $distance += $row['mdld2'];
                                }
                            } else {
                                $found = true;  // we've hit something
                                $ratio = 1 - $distance / max(mb_strlen($row['epithet0'], "UTF-8"), $lenEpithet);
                            }
                        }
                    } else {
                        $found = true;  // no epithet, so we've hit something anyway
                        $ratio = 1;
                        $distance = 0;
                    }

                    // if we've found anything valuable, look for the synonyms and put everything together
                    if ($found) {
                        if ($row['synID']) {
                            $sql = "SELECT ts.taxonID, tg.genus,
                                     te0.epithet epithet0, te1.epithet epithet1, te2.epithet epithet2,
                                     te3.epithet epithet3, te4.epithet epithet4, te5.epithet epithet5,
                                     ta0.author author0, ta1.author author1, ta2.author author2,
                                     ta3.author author3, ta4.author author4, ta5.author author5
                                    FROM tbl_tax_species ts
                                     LEFT JOIN tbl_tax_authors ta0 ON ta0.authorID = ts.authorID
                                     LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID = ts.subspecies_authorID
                                     LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID = ts.variety_authorID
                                     LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID = ts.subvariety_authorID
                                     LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID = ts.forma_authorID
                                     LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID = ts.subforma_authorID
                                     LEFT JOIN tbl_tax_epithets te0 ON te0.epithetID = ts.speciesID
                                     LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID = ts.subspeciesID
                                     LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID = ts.varietyID
                                     LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID = ts.subvarietyID
                                     LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID = ts.formaID
                                     LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID = ts.subformaID
                                     LEFT JOIN tbl_tax_genera tg ON tg.genID=ts.genID
                                    WHERE ts.taxonID = '".mysql_escape_string($row['synID'])."'";
                            $result2 = mysql_query($sql);
                            $row2 = mysql_fetch_array($result2);
                            $syn = $row2['genus'];
                            if ($row2['epithet0']) $syn .= " " .          $row2['epithet0'] . " " . $row2['author0'];
                            if ($row2['epithet1']) $syn .= " subsp. "   . $row2['epithet1'] . " " . $row2['author1'];
                            if ($row2['epithet2']) $syn .= " var. "     . $row2['epithet2'] . " " . $row2['author2'];
                            if ($row2['epithet3']) $syn .= " subvar. "  . $row2['epithet3'] . " " . $row2['author3'];
                            if ($row2['epithet4']) $syn .= " forma "    . $row2['epithet4'] . " " . $row2['author4'];
                            if ($row2['epithet5']) $syn .= " subforma " . $row2['epithet5'] . " " . $row2['author5'];
                            $synID = $row2['taxonID'];
                        } else {
                            $syn = '';
                            $synID = 0;
                        }

                        // format the taxon-output
                        $taxon = $val['genus'];
                        if ($row['epithet0']) $taxon .= " "          . $row['epithet0'] . " " . $row['author0'];
                        if ($row['epithet1']) $taxon .= " subsp. "   . $row['epithet1'] . " " . $row['author1'];
                        if ($row['epithet2']) $taxon .= " var. "     . $row['epithet2'] . " " . $row['author2'];
                        if ($row['epithet3']) $taxon .= " subvar. "  . $row['epithet3'] . " " . $row['author3'];
                        if ($row['epithet4']) $taxon .= " forma "    . $row['epithet4'] . " " . $row['author4'];
                        if ($row['epithet5']) $taxon .= " subforma " . $row['epithet5'] . " " . $row['author5'];
                        
                        $taxonID = $row['taxonID'];
                        $commonNames = array();
                        if( $includeCommonNames ) {
                            $sql_cn = "
                                SELECT nc.`common_id`, nc.`common_name`, nl.`iso639-6`, gc.`name`, np.`period`
                                FROM `herbar_names`.`tbl_name_taxa` nt
                                LEFT JOIN `herbar_names`.`tbl_name_applies_to` nat ON nat.`entity_id` = nt.`taxon_id`
                                LEFT JOIN `herbar_names`.`tbl_name_commons` nc ON nc.`common_id` = nat.`name_id`
                                LEFT JOIN `herbar_names`.`tbl_name_languages` nl ON nl.`language_id` = nat.`language_id`
                                LEFT JOIN `herbar_names`.`tbl_geonames_cache` gc ON gc.`geonameId` = nat.`geonameId`
                                LEFT JOIN `herbar_names`.`tbl_name_periods` np ON np.`period_id` = nat.`period_id`
                                WHERE
                                nt.`taxonID` = '$taxonID' AND nc.`common_name` IS NOT NULL
                            ";
                            $result_cn = mysql_query($sql_cn);
                            while( $row_cn = mysql_fetch_array($result_cn) ) {
                                $commonNames[] = array(
                                    'id' => $row_cn['common_id'],
                                    'name' => $row_cn['common_name'],
                                    'language' => $row_cn['iso639-6'],
                                    'geography' => $row_cn['name'],
                                    'period' => $row_cn['period']
                                );
                            }
                        }

                        // put everything into the output-array
                        $lev2[] = array('name'          => $name,
                                        'distance'      => $distance + $val['distance'],
                                        'ratio'         => $ratio * $val['ratio'],
                                        'taxon'         => $taxon,
                                        'taxonID'       => $taxonID,
                                        'syn'           => $syn,
                                        'synID'         => $synID,
                                        'commonNames'   => $commonNames
                         );
//                        if ($distance == 0 && $val['distance'] == 0) $fullHit = true;  // we've hit everything direct
                    }
                    $ctr++;
                }

                // if there's more than one hit, sort them (faster here than within the db)
                if (count($lev2) > 1) {
                    $sort1 = array();
                    $sort2 = array();
                    $sort3 = array();
                    foreach ($lev2 as $key2 => $row2) {
                        $sort1[$key2] = $row2['distance'];
                        $sort2[$key2] = $row2['ratio'];
                        $sort3[$key2] = $row2['name'];
                    }
                    array_multisort($sort1, SORT_NUMERIC, $sort2, SORT_DESC, SORT_NUMERIC, $sort3, $lev2);
                }

                // glue everything together
                if (count($lev2) > 0) {
                    $lev[$key]['species'] = $lev2;
                    $searchresult[] = $lev[$key];
                }
            }

//            if ($fullHit) {
//                // remove any entries with ratio < 100% if we have a full hit (ratio = 100%) anywhere
//                foreach ($searchresult as $srKey => $srVal) {
//                    foreach ($srVal['species'] as $spKey => $spVal) {
//                        if ($spVal['distance'] > 0) unset($searchresult[$srKey]['species'][$spKey]);
//                    }
//                }
//                foreach ($searchresult as $srKey => $srVal) {
//                    if (count($srVal['species']) == 0) unset($searchresult[$srKey]);
//                }
//            }
        }

        $matches['result'][] = array('searchtext'          => $searchItem,
                                     'searchtextNearmatch' => $searchItemNearmatch,
                                     'rowsChecked'         => $ctr,
                                     'type'                => $type,
                                     'database'            => 'freud',
                                     'includeCommonNames'  => $includeCommonNames,
                                     'searchresult'        => $searchresult);
    }
    $matches['error'] = ob_get_clean();

    return $matches;
}


/**
 * get all possible matches plus the synonyms
 *
 * @param String $searchtext taxon string(s) to search for
 * @return array result of all searches including synonyms
 */
public function getMatchesWithSynonyms($searchtext, $withNearMatch = false)
{
    // catch all output to the console
    ob_start();

    $this->matches = $this->getMatches($searchtext, $withNearMatch);    // call original MDLD-service for data without synonyms
    $this->_getSynonyms();                                              // add synonymy-information to the result-array

    $this->matches['error'] = ob_get_clean();

    return $this->matches;
}


public function getMatchesCommonNames($searchtext, $withNearMatch = false)
{
    global $options;

    // catch all output to the console
    ob_start();

    // base definition of the return array
    $matches = array('error'  => '',
                     'result' => array());

    if (!@mysql_connect($options['hrdb']['dbhost'], $options['hrdb']['dbuser'], $options['hrdb']['dbpass']) || !@mysql_select_db($options['hrdb']['dbname'])) {
        $matches['error'] = 'no database connection';
        return $matches;
    }
    mysql_query("SET character set utf8");

    // split the input at newlines into several queries
    $searchItems = preg_split("[\n|\r]", $searchtext, -1, PREG_SPLIT_NO_EMPTY);

    foreach ($searchItems as $searchItem) {
        $searchresult = array();
        $sort1 = $sort2 = $sort3 = array();
        $ctr = 0;  // how many checks did we do

        $type = 'common';                                // we're asked for a common name check

        if ($withNearMatch) {
            $searchItemNearmatch = $this->_near_match($searchItem, false, true); // use near match if desired
            $commonName           = strtolower(trim($searchItemNearmatch));
            $lenCommonName        = mb_strlen(trim($searchItemNearmatch), "UTF-8");
        } else {
            $searchItemNearmatch = '';
            $commonName           = strtolower(trim($searchItem));
            $lenCommonName        = mb_strlen(trim($searchItem), "UTF-8");
        }
		$this->limit=4;
		$lenlim=min($lenCommonName/2,$this->limit);
		
		$query="
SELECT
 c.common_id
FROM
  {$options['hrdb']['dbnameCommonNames']}.tbl_name_commons c
WHERE
  mdld('{$commonName}',common_name, {$this->block_limit}, {$this->limit}) <  LEAST(CHAR_LENGTH(common_name)/2,{$lenlim})
 ";
 //echo $query;exit;
 
		$res = mysql_query($query);
		$s="";
		while ($row = mysql_fetch_array($res)) {
			$s.=",".$row['common_id']."";
		}
		$s=substr($s,1);
		
		$query="
SELECT
 tax.taxonID as 'taxonID',
 com.common_name as 'common_name',
 mdld('{$commonName}', LOWER(com.common_name), {$this->block_limit}, {$this->limit}) as 'mdld'

FROM
 {$options['hrdb']['dbnameCommonNames']}.tbl_name_applies_to a
 LEFT JOIN {$options['hrdb']['dbnameCommonNames']}.tbl_name_entities ent ON ent.entity_id = a.entity_id
 LEFT JOIN {$options['hrdb']['dbnameCommonNames']}.tbl_name_taxa tax ON tax.taxon_id = ent.entity_id

 LEFT JOIN {$options['hrdb']['dbnameCommonNames']}.tbl_name_names nam ON nam.name_id = a.name_id
 LEFT JOIN {$options['hrdb']['dbnameCommonNames']}.tbl_name_commons com ON com.common_id = nam.name_id
WHERE
 com.common_id IN({$s})
 ";
 // echo $query;exit;
		
		$res = mysql_query($query);
        /**
         */
        while ($row = mysql_fetch_array($res)) {
           
			// we've found something, so let's put everything together
			// distances and ratios of genus and species are both set to the distance and ratio found for the common name
			$familyData  = $this->_getFamilyPartsOfSpecies($row['taxonID']);
			$genusData   = $this->_getGenusPartsOfSpecies($row['taxonID']);
			$speciesData = $this->_getTaxonPartsOfSpecies($row['taxonID']);
			
			$taxon = $genusData['genus'];
			if ($speciesData['epithet0']) $taxon .= " "          . $speciesData['epithet0'] . " " . $speciesData['author0'];
			if ($speciesData['epithet1']) $taxon .= " subsp. "   . $speciesData['epithet1'] . " " . $speciesData['author1'];
			if ($speciesData['epithet2']) $taxon .= " var. "     . $speciesData['epithet2'] . " " . $speciesData['author2'];
			if ($speciesData['epithet3']) $taxon .= " subvar. "  . $speciesData['epithet3'] . " " . $speciesData['author3'];
			if ($speciesData['epithet4']) $taxon .= " forma "    . $speciesData['epithet4'] . " " . $speciesData['author4'];
			if ($speciesData['epithet5']) $taxon .= " subforma " . $speciesData['epithet5'] . " " . $speciesData['author5'];

			if (isset($speciesData['synID']) && $speciesData['synID'] ) {
				$synData = $this->_getTaxonPartsOfSpecies($speciesData['synID']);
				$syn = $synData['genus'];
				if ($synData['epithet0']) $syn .= " " .          $synData['epithet0'] . " " . $synData['author0'];
				if ($synData['epithet1']) $syn .= " subsp. "   . $synData['epithet1'] . " " . $synData['author1'];
				if ($synData['epithet2']) $syn .= " var. "     . $synData['epithet2'] . " " . $synData['author2'];
				if ($synData['epithet3']) $syn .= " subvar. "  . $synData['epithet3'] . " " . $synData['author3'];
				if ($synData['epithet4']) $syn .= " forma "    . $synData['epithet4'] . " " . $synData['author4'];
				if ($synData['epithet5']) $syn .= " subforma " . $synData['epithet5'] . " " . $synData['author5'];
				$synID = $synData['taxonID'];
			} else {
				$syn = '';
				$synID = 0;
			}

			$searchresult[] = array(
				'genus'    => $genusData['genus'],
				'distance' => $row['mdld'],
				'ratio'    => 1 - $row['mdld'] / max(mb_strlen($row['common_name'], "UTF-8"), $lenCommonName),
				'taxon'    => $genusData['genus'] . ' ' . $genusData['author'] . ' (' . $familyData['family'] . ')',
				'ID'       => $genusData['genID'],
				'species'  => array(
					array(
						'name'       => trim($speciesData['epithet0']),
						'commonName' => $row['common_name'],
						'distance'   => $row['mdld'],
						'ratio'      => 1 - $row['mdld'] / max(mb_strlen($row['common_name'], "UTF-8"), $lenCommonName),
						'taxon'      => $taxon,
						'taxonID'    => $row['taxonID'],
						'syn'        => $syn,
						'synID'      => $synID
					)
				)
			);
            
            $ctr++;
        }


        // if there's more than one hit, sort them (faster here than within the db)
        if (count($searchresult) > 1) {
            foreach ($searchresult as $key => $row) {
                $sort1[$key] = $row['distance'];
                $sort2[$key] = $row['ratio'];
                $sort3[$key] = $row['taxon'];
            }
            array_multisort($sort1, SORT_NUMERIC, $sort2, SORT_DESC, SORT_NUMERIC, $sort3, $searchresult);
        }

        $matches['result'][] = array('searchtext'          => $searchItem,
                                     'searchtextNearmatch' => $searchItemNearmatch,
                                     'rowsChecked'         => $ctr,
                                     'type'                => $type,
                                     'database'            => 'freud',
                                     'searchresult'        => $searchresult);
    }

    $matches['error'] = ob_get_clean();

    return $matches;
}


public function getMatchesCommonNamesWithSynonyms($searchtext, $withNearMatch = false)
{
    // catch all output to the console
    ob_start();

    $this->matches = $this->getMatchesCommonNames($searchtext, $withNearMatch);     // call original MDLD-service for data without synonyms
    $this->_getSynonyms();                                                          // add synonymy-information to the result-array

    $this->matches['error'] = ob_get_clean();

    return $this->matches;
}


/********************\
|                    |
|  private functions |
|                    |
\********************/

/**
 * returns an array with the various parts of a taxon
 *
 * @param integer $taxonID
 * @return array result of query
 */
private function _getTaxonPartsOfSpecies ($taxonID)
{
    $sql = "SELECT ts.taxonID, tg.genus,
             te0.epithet epithet0, te1.epithet epithet1, te2.epithet epithet2,
             te3.epithet epithet3, te4.epithet epithet4, te5.epithet epithet5,
             ta0.author author0, ta1.author author1, ta2.author author2,
             ta3.author author3, ta4.author author4, ta5.author author5
            FROM tbl_tax_species ts
             LEFT JOIN tbl_tax_authors ta0 ON ta0.authorID = ts.authorID
             LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID = ts.subspecies_authorID
             LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID = ts.variety_authorID
             LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID = ts.subvariety_authorID
             LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID = ts.forma_authorID
             LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID = ts.subforma_authorID
             LEFT JOIN tbl_tax_epithets te0 ON te0.epithetID = ts.speciesID
             LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID = ts.subspeciesID
             LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID = ts.varietyID
             LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID = ts.subvarietyID
             LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID = ts.formaID
             LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID = ts.subformaID
             LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
            WHERE ts.taxonID = '" . mysql_escape_string($taxonID) . "'";
    $result = mysql_query($sql);
    if ($result) {
        return mysql_fetch_array($result);
    } else {
        return array();
    }
}

/**
 * returns an array with the genus data (genID, genus and author) of a given taxon-ID
 *
 * @param integer $taxonID
 * @return array result of query
 */
private function _getGenusPartsOfSpecies ($taxonID)
{
    $sql = "SELECT g.genID, g.genus, a.author
            FROM tbl_tax_species s, tbl_tax_genera g
			LEFT JOIN  tbl_tax_authors a   ON (a.authorID =g.authorID )
            WHERE s.genID = g.genID
             AND s.taxonID = '" . mysql_escape_string($taxonID) . "'";
    $result = mysql_query($sql);
	
    if ($result) {
        return mysql_fetch_array($result);
    } else {
        return array();
    }
}

/**
 * returns an array with the family data (familyID, family and author) of a given taxon-ID
 *
 * @param integer $taxonID
 * @return array result of query
 */
private function _getFamilyPartsOfSpecies ($taxonID)
{
    $sql = "SELECT f.familyID, f.family, a.author
            FROM tbl_tax_species s, tbl_tax_genera g, tbl_tax_families f
			LEFT JOIN tbl_tax_authors a ON (a.authorID = f.authorID )
            WHERE s.genID = g.genID
             AND g.familyID = f.familyID
             AND s.taxonID = '" . mysql_escape_string($taxonID) . "'";
			 
    $result = mysql_query($sql);
    if ($result) {
        return mysql_fetch_array($result);
    } else {
        return array();
    }
}

// BP, 07.2010: private functions for synonyms
/**
 * get all possible synonyms
 *
 * Loops through the complete $this->matches array
 * Retrieves synonymy-information from DB and writes it into
 * the array
 *
 * @param no parameters (makes all changes to $this->matches)
 * @return void (writes synonyms directly into $this->matches)
 */
private function _getSynonyms() {
    global $options;

    // connect to DB
    if (!@mysql_connect($options['hrdb']['dbhost'], $options['hrdb']['dbuser'], $options['hrdb']['dbpass']) || !@mysql_select_db($options['hrdb']['dbname'])) {
        $this->matches['error'] = 'no database connection';
        return;
    }
    mysql_query("SET character set utf8");

    // iterate through result-array
    for ($i = 0; $i < count($this->matches['result']); $i++) {   // better: foreach ($this->matches['result'] as $i => $resultArr)
        $resultArr = $this->matches['result'][$i];
        for ($j = 0; $j < count($resultArr['searchresult']); $j++) {
            $searchResultArray = $resultArr['searchresult'][$j];
            for ($k = 0; $k < count($searchResultArray['species']); $k++) {
                $speciesArray = $searchResultArray['species'][$k];

                $id = $speciesArray['taxonID'];     // this is the first guess for a starting point

                //error_log("taxonID[".$i."][".$j."][".$k."] = " . $speciesArray['taxonID'] . ", ID = " . $id,0);

                $order = " ORDER BY genus, epithet0, author0, epithet1, author1, epithet2, author2, epithet3, author3";
                $sql = "SELECT ts.taxonID, ts.basID, ts.synID, tg.genus, tg.DallaTorreIDs, tg.DallaTorreZusatzIDs, tst.status, tst.statusID,
                         ta0.author author0, ta1.author author1, ta2.author author2,
                         ta3.author author3, ta4.author author4, ta5.author author5,
                         te0.epithet epithet0, te1.epithet epithet1, te2.epithet epithet2,
                         te3.epithet epithet3, te4.epithet epithet4, te5.epithet epithet5,
                         ts.synID, ts.basID
                        FROM tbl_tax_species ts
                         LEFT JOIN tbl_tax_authors ta0 ON ta0.authorID = ts.authorID
                         LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID = ts.subspecies_authorID
                         LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID = ts.variety_authorID
                         LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID = ts.subvariety_authorID
                         LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID = ts.forma_authorID
                         LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID = ts.subforma_authorID
                         LEFT JOIN tbl_tax_epithets te0 ON te0.epithetID = ts.speciesID
                         LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID = ts.subspeciesID
                         LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID = ts.varietyID
                         LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID = ts.subvarietyID
                         LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID = ts.formaID
                         LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID = ts.subformaID
                         LEFT JOIN tbl_tax_status tst ON tst.statusID = ts.statusID
                         LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
                        WHERE taxonID = '" . mysql_escape_string($id) . "'";
                $result = mysql_query($sql);
                if (mysql_num_rows($result) > 0) {
                    $row = mysql_fetch_array($result);

                    $repeatCtr = 10;
                    // 1 = x (hybrid name), 96 = acc (accepted name), 97 = prov. acc. (provisionally accepted name), 103 = appl. incert. (application uncertain)
                    if ($row['statusID'] == 96 || $row['statusID'] == 97 || $row['statusID'] == 103 || $row['statusID'] == 1) {
                        $id = $row['taxonID'];
                    } else {
                        $id = $row['synID'];
                    }

                    do {
                        $result = mysql_query("SELECT ts.taxonID, ts.basID, ts.synID, tg.genus, tg.DallaTorreIDs, tg.DallaTorreZusatzIDs, tst.status, tst.statusID,
                                             ta0.author author0, ta1.author author1, ta2.author author2,
                                             ta3.author author3, ta4.author author4, ta5.author author5,
                                             te0.epithet epithet0, te1.epithet epithet1, te2.epithet epithet2,
                                             te3.epithet epithet3, te4.epithet epithet4, te5.epithet epithet5
                                            FROM tbl_tax_species ts
                                             LEFT JOIN tbl_tax_authors ta0 ON ta0.authorID = ts.authorID
                                             LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID = ts.subspecies_authorID
                                             LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID = ts.variety_authorID
                                             LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID = ts.subvariety_authorID
                                             LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID = ts.forma_authorID
                                             LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID = ts.subforma_authorID
                                             LEFT JOIN tbl_tax_epithets te0 ON te0.epithetID = ts.speciesID
                                             LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID = ts.subspeciesID
                                             LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID = ts.varietyID
                                             LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID = ts.subvarietyID
                                             LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID = ts.formaID
                                             LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID = ts.subformaID
                                             LEFT JOIN tbl_tax_status tst ON tst.statusID = ts.statusID
                                             LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
                                            WHERE taxonID = '" . intval($id) . "'");
                        if (mysql_num_rows($result) > 0) {
                            $row = mysql_fetch_array($result);

                            $repeat = false;
                            if (!empty($row['synID']) && $repeatCtr > 0) {
                                $repeatCtr--;
                                $repeat = true;
                            }

                            $sql = "SELECT ts.taxonID, tg.genus, tg.DallaTorreIDs, tg.DallaTorreZusatzIDs, tst.status,
                                     ta0.author author0, ta1.author author1, ta2.author author2,
                                     ta3.author author3, ta4.author author4, ta5.author author5,
                                     te0.epithet epithet0, te1.epithet epithet1, te2.epithet epithet2,
                                     te3.epithet epithet3, te4.epithet epithet4, te5.epithet epithet5,
                                     ts.synID, ts.basID
                                    FROM tbl_tax_species ts
                                     LEFT JOIN tbl_tax_authors ta0 ON ta0.authorID = ts.authorID
                                     LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID = ts.subspecies_authorID
                                     LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID = ts.variety_authorID
                                     LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID = ts.subvariety_authorID
                                     LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID = ts.forma_authorID
                                     LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID = ts.subforma_authorID
                                     LEFT JOIN tbl_tax_epithets te0 ON te0.epithetID = ts.speciesID
                                     LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID = ts.subspeciesID
                                     LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID = ts.varietyID
                                     LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID = ts.subvarietyID
                                     LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID = ts.formaID
                                     LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID = ts.subformaID
                                     LEFT JOIN tbl_tax_status tst ON tst.statusID = ts.statusID
                                     LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
                                    WHERE synID = '" . mysql_escape_string($id) . "' ";
                            if (empty($row['basID'])) {
                                $result2 = mysql_query($sql . "AND basID = '" . mysql_escape_string($id) . "'");
                            } else {
                                $result2 = mysql_query($sql . "AND (basID IS NULL OR basID = '" . mysql_escape_string($id) . "') AND taxonID = '" . $row['basID'] . "'");
                            }

                            $this->currSynonyms20 = array();
                            $this->counterSyns20 = 0;

                            $this->_appendSynDetails($result2, $sql, $order, "&equiv;");

                            if (empty($row['basID'])) {
                                $result2 = mysql_query($sql . "AND basID IS NULL" . $order);
                            } else {
                                $result2 = mysql_query($sql . "AND (basID IS NULL OR basID = '" . mysql_escape_string($id) . "') AND taxonID != '" . $row['basID'] . "'" . $order);
                            }

                            $this->_appendSynDetails($result2, $sql, $order);
                            // repeat the loop if the synID is set to anything
                            if (!empty($row['synID'])) {
                                $id = $row['synID'];
                                echo "\n";
                            } else {
                                $id = 0;
                            }
                        } else {
                            $id = 0;
                        }
                    } while ($id);

                    // add current synonyms-list to current 'species'-entry
                    // NOTE: need to quote full "path"
                    $this->matches['result'][$i]['searchresult'][$j]['species'][$k]['synonyms'] = $this->currSynonyms20;
                }
            }
        }
    }
}


/**
 * appends details of synonym into result-array $this->matches
 *
 * @param $result: result of first DB-query
 * @param $sql: SQL-query-string (for second query)
 * @param $order: SQL-order-by
 * @param $equalSign: either "=" or "&equiv;"
 *
 * @return void (writes synonyms directly into $this->currSynonyms20)
 */
private function _appendSynDetails($result, $sql, $order, $equalSign="=") {
    while ($row2 = mysql_fetch_array($result)) {
        $result3 = mysql_query($sql . "AND basID = '" . $row2['taxonID'] . "'". $order);
        $counter40 = 0;
        $synonyms40 = array();
        while ($row3 = mysql_fetch_array($result3)) {
            $synonyms40[$counter40++] = array(
                'equalsSign'    => "&equiv;",
                'name'          => $this->_taxon($row3),
                'status'        => $row3['status'],
                'taxonID'       => $row3['taxonID'],
                'basID'         => $row3['basID'],
                'synID'         => $row3['synID']
            );
        }
        $this->currSynonyms20[$this->counterSyns20++] = array(
            'equalsSign'        => $equalSign,
            'name'              => $this->_taxon($row2),
            'status'            => $row2['status'],
            'taxonID'           => $row2['taxonID'],
            'basID'             => $row2['basID'],
            'synID'             => $row2['synID'],
            'synonyms'          => $synonyms40
        );
    }
}


/**
 * constructs the taxon
 *
 * @param array $row row of the last query
 * @return string complete taxon
 */
private function _taxon ($row)
{
  $text = $row['genus'];
  if ($row['epithet0']) $text .= " "          . $row['epithet0'] . " " . $row['author0'];
  if ($row['epithet1']) $text .= " subsp. "   . $row['epithet1'] . " " . $row['author1'];
  if ($row['epithet2']) $text .= " var. "     . $row['epithet2'] . " " . $row['author2'];
  if ($row['epithet3']) $text .= " subvar. "  . $row['epithet3'] . " " . $row['author3'];
  if ($row['epithet4']) $text .= " forma "    . $row['epithet4'] . " " . $row['author4'];
  if ($row['epithet5']) $text .= " subforma " . $row['epithet5'] . " " . $row['author5'];

  return $text;
}


}