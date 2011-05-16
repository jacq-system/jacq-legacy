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
 * The data-structure used here is the one of the catalog of life
 *
 * @author Johannes Schachner <joschach@ap4net.at>
 * @since 23.03.2011
 */
require_once('inc/variables.php');

class cls_herbarium_col extends cls_herbarium_base {

/*******************\
|                   |
|  public functions |
|                   |
\*******************/

/**
 * get all possible matches against the catalogue of life
 *
 * @param String $searchtext taxon string(s) to search for
 * @param bool[optional] $withNearMatch use near_match if true
 * @return array result of all searches
 */
public function getMatches ($searchtext, $withNearMatch = false)
{
    global $options;

    // catch all output to the console
    ob_start();

    // base definition of the return array
    $matches = array('error'       => '',
                     'result'      => array());

    if (!@mysql_connect($options['col']['dbhost'], $options['col']['dbuser'], $options['col']['dbpass']) || !@mysql_select_db($options['col']['dbname'])) {
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

//            $res = mysql_query("SELECT g.ID AS genID, g.genus, f.family
//                                FROM genera g, families f
//                                WHERE g.genus = '" . mysql_real_escape_string($uninomial) . "'
//                                 AND g.family_id = f.record_id");
//            if (mysql_num_rows($res) > 0) {
//                $row = mysql_fetch_array($res);
//                $searchresult[] = array('genus'    => $row['genus'],
//                                        'distance' => 0,
//                                        'ratio'    => 1,
//                                        'taxon'    => $row['genus'] . ' (' . $row['family'] . ')',
//                                        'ID'       => $row['genID'],
//                                        'species'  => array());
//                $ctr++;
//            } else {
                // no full hit, so do just the normal search

                // first search the genera
                $res = mysql_query("SELECT g.ID AS genID, g.genus, f.family,
                                     mdld('" . mysql_real_escape_string($uninomial) . "', g.genus, 2, 4) AS mdld
                                    FROM genera g, families f
                                    WHERE g.family_id = f.record_id");
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
                                                'taxon'    => $row['genus'] . ' (' . $row['family'] . ')',
                                                'ID'       => $row['genID'],
                                                'species'  => array());
                    }
                    $ctr++;
                }

                // then the families
                $res = mysql_query("SELECT family, record_id,
                                     mdld('" . mysql_real_escape_string($uninomial) . "', family, 2, 4) AS mdld
                                    FROM families
                                    GROUP BY family");
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
                                                'ID'       => $row['record_id'],
                                                'species'  => array());
                    }
                    $ctr++;
                }

                // then the superfamilies
                $res = mysql_query("SELECT superfamily,
                                     mdld('" . mysql_real_escape_string($uninomial) . "', superfamily, 2, 4) AS mdld
                                    FROM families
                                    GROUP BY superfamily");
                /**
                 * do the actual calculation of the distances
                 * and decide if the result should be kept
                 */
                while ($row = mysql_fetch_array($res)) {
                    $limit = min($lenUninomial, strlen($row['superfamily'])) / 2;     // 1st limit of the search
                    if ($row['mdld'] <= 3 && $row['mdld'] < $limit) {                 // 2nd limit of the search
                        $searchresult[] = array('genus'    => '',
                                                'distance' => $row['mdld'],
                                                'ratio'    => 1 - $row['mdld'] / max(mb_strlen($row['superfamily'], "UTF-8"), $lenUninomial),
                                                'taxon'    => $row['superfamily'],
                                                'ID'       => 0,
                                                'species'  => array());
                    }
                    $ctr++;
                }

                // then the order
                $res = mysql_query("SELECT order,
                                     mdld('" . mysql_real_escape_string($uninomial) . "', order, 2, 4) AS mdld
                                    FROM families
                                    GROUP BY order");
                /**
                 * do the actual calculation of the distances
                 * and decide if the result should be kept
                 */
                while ($row = mysql_fetch_array($res)) {
                    $limit = min($lenUninomial, strlen($row['order'])) / 2;     // 1st limit of the search
                    if ($row['mdld'] <= 3 && $row['mdld'] < $limit) {           // 2nd limit of the search
                        $searchresult[] = array('genus'    => '',
                                                'distance' => $row['mdld'],
                                                'ratio'    => 1 - $row['mdld'] / max(mb_strlen($row['order'], "UTF-8"), $lenUninomial),
                                                'taxon'    => $row['order'],
                                                'ID'       => 0,
                                                'species'  => array());
                    }
                    $ctr++;
                }

                // then the class
                $res = mysql_query("SELECT class,
                                     mdld('" . mysql_real_escape_string($uninomial) . "', class, 2, 4) AS mdld
                                    FROM families
                                    GROUP BY class");
                /**
                 * do the actual calculation of the distances
                 * and decide if the result should be kept
                 */
                while ($row = mysql_fetch_array($res)) {
                    $limit = min($lenUninomial, strlen($row['class'])) / 2;     // 1st limit of the search
                    if ($row['mdld'] <= 3 && $row['mdld'] < $limit) {           // 2nd limit of the search
                        $searchresult[] = array('genus'    => '',
                                                'distance' => $row['mdld'],
                                                'ratio'    => 1 - $row['mdld'] / max(mb_strlen($row['class'], "UTF-8"), $lenUninomial),
                                                'taxon'    => $row['class'],
                                                'ID'       => 0,
                                                'species'  => array());
                    }
                    $ctr++;
                }

                // then the phylum
                $res = mysql_query("SELECT phylum,
                                     mdld('" . mysql_real_escape_string($uninomial) . "', phylum, 2, 4) AS mdld
                                    FROM families
                                    GROUP BY phylum");
                /**
                 * do the actual calculation of the distances
                 * and decide if the result should be kept
                 */
                while ($row = mysql_fetch_array($res)) {
                    $limit = min($lenUninomial, strlen($row['phylum'])) / 2;     // 1st limit of the search
                    if ($row['mdld'] <= 3 && $row['mdld'] < $limit) {            // 2nd limit of the search
                        $searchresult[] = array('genus'    => '',
                                                'distance' => $row['mdld'],
                                                'ratio'    => 1 - $row['mdld'] / max(mb_strlen($row['phylum'], "UTF-8"), $lenUninomial),
                                                'taxon'    => $row['phylum'],
                                                'ID'       => 0,
                                                'species'  => array());
                    }
                    $ctr++;
                }

                // and finally the kingdom
                $res = mysql_query("SELECT kingdom,
                                     mdld('" . mysql_real_escape_string($uninomial) . "', kingdom, 2, 4) AS mdld
                                    FROM families
                                    GROUP BY kingdom");
                /**
                 * do the actual calculation of the distances
                 * and decide if the result should be kept
                 */
                while ($row = mysql_fetch_array($res)) {
                    $limit = min($lenUninomial, strlen($row['kingdom'])) / 2;     // 1st limit of the search
                    if ($row['mdld'] <= 3 && $row['mdld'] < $limit) {            // 2nd limit of the search
                        $searchresult[] = array('genus'    => '',
                                                'distance' => $row['mdld'],
                                                'ratio'    => 1 - $row['mdld'] / max(mb_strlen($row['kingdom'], "UTF-8"), $lenUninomial),
                                                'taxon'    => $row['kingdom'],
                                                'ID'       => 0,
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
//                $res = mysql_query("SELECT g.ID AS genID, g.genus, f.family
//                                    FROM genera g, families f
//                                    WHERE g.genus = '" . mysql_real_escape_string($genus[$i]) . "'
//                                     AND g.family_id = f.record_id");
//                if (mysql_num_rows($res) > 0) {
//                    $row = mysql_fetch_array($res);
//                    $lev[] = array('genus'    => $row['genus'],
//                                   'distance' => 0,
//                                   'ratio'    => 1,
//                                   'taxon'    => $row['genus'] . ' (' . $row['family'] . ')',
//                                   'ID'       => $row['genID']);
//                    $ctr++;
//                } else {
                    // no full hit, so do just the normal search
                    $res = mysql_query("SELECT g.ID AS genID, g.genus, f.family,
                                         mdld('" . mysql_real_escape_string($genus[$i]) . "', g.genus, 2, 4) AS mdld
                                        FROM genera g, families f
                                        WHERE g.family_id = f.record_id");

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
                                           'taxon'    => $row['genus'] . ' (' . $row['family'] . ')',
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
                $sql = "SELECT record_id AS taxonID, species, infraspecies, infraspecies_marker, author, name_code, accepted_name_code";
                if ($epithet) {  // if an epithet was given, use it
                    $sql .= ", mdld('" . mysql_real_escape_string($epithet) . "', species, 4, 5)  as mdld";
                    if ($epithet2 && $rank) {  // if a subepithet was given, use it
                        $sql .= ", mdld('" . mysql_real_escape_string($epithet2) . "', infraspecies, 4, 5) as mdld2";
                    }
                }
                $sql .= " FROM scientific_names
                          WHERE genus = '" . $val['genus'] . "'";
                if (!($epithet2 && $rank)) {
                    $sql .= " AND (infraspecies IS NULL OR infraspecies = '')";
                }
                $res = mysql_query($sql);
                while ($row = mysql_fetch_array($res)) {
                    $name = trim($row['species']);
                    $found = false;
                    if ($epithet) {
                        $distance = $row['mdld'];
                        $limit = min($lenEpithet, mb_strlen($row['species'], "UTF-8")) / 2;                   // 1st limit of the search
                        if (($distance + $val['distance']) <= 4 && $distance <= 4 && $distance <= $limit) {   // 2nd limit of the search
                            if ($epithet2 && $rank) {
                                $limit2 = min($lenEpithet2, mb_strlen($row['infraspecies'], "UTF-8")) / 2;    // 3rd limit of the search
                                if ($row['mdld2'] <= 4 && $row['mdld2'] <= $limit2) {                         // 4th limit of the search
                                    $found = true;  // we've hit something
                                    $ratio = 1
                                           - $distance / max(mb_strlen($row['species'], "UTF-8"), $lenEpithet)
                                           - $row['mdld2'] / max(mb_strlen($row['infraspecies'], "UTF-8"), $lenEpithet2);
                                    $distance += $row['mdld2'];
                                }
                            } else {
                                $found = true;  // we've hit something
                                $ratio = 1 - $distance / max(mb_strlen($row['species'], "UTF-8"), $lenEpithet);
                            }
                        }
                    } else {
                        $found = true;  // no epithet, so we've hit something anyway
                        $ratio = 1;
                        $distance = 0;
                    }

                    // if we've found anything valuable, look for the synonyms and put everything together
                    if ($found) {
                        if ($row['accepted_name_code'] && $row['accepted_name_code'] != $row['name_code']) {
                            $sql = "SELECT record_id AS taxonID, genus, species, infraspecies, infraspecies_marker, author
                                    FROM scientific_names
                                    WHERE name_code = '" . mysql_real_escape_string($row['accepted_name_code']) . "'";
                            $result2 = mysql_query($sql);
                            $row2 = mysql_fetch_array($result2);
                            $syn = trim($row2['genus']
                                      . " " . $row2['species']
                                      . " " . $row2['infraspecies_marker']
                                      . " " . $row2['infraspecies']
                                      . " " . $row2['author']);
                            $synID = $row2['taxonID'];
                        } else {
                            $syn = '';
                            $synID = 0;
                        }

                        // format the taxon-output
                        $taxon = trim($val['genus']
                                    . " " . $row['species']
                                    . " " . $row['infraspecies_marker']
                                    . " " . $row['infraspecies']
                                    . " " . $row['author']);
                        // put everything into the output-array
                        $lev2[] = array('name'     => $name,
                                        'distance' => $distance + $val['distance'],
                                        'ratio'    => $ratio * $val['ratio'],
                                        'taxon'    => $taxon,
                                        'taxonID'  => $row['taxonID'],
                                        'syn'      => $syn,
                                        'synID'    => $synID);
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
                                     'database'            => 'col',
                                     'searchresult'        => $searchresult);
    }
    $matches['error'] = ob_get_clean();

    return $matches;
}


}