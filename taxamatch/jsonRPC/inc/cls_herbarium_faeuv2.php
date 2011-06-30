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
 * The data-structure used here is the one of the fauna europaea
 *
 * @author Johannes Schachner <joschach@ap4net.at>
 * @since 23.03.2011
 */
error_reporting(E_ALL);

require_once('inc/variables.php');

class cls_herbarium_faeuv2 extends cls_herbarium_base {
	
	var $block_limit=2;
	var $limit=4;
	
	/*******************\
	|                   |
	|  public functions |
	|                   |
	\*******************/
	
	/**
	 * get all possible matches against the fauna europaea
	 *
	 * @param String $searchtext taxon string(s) to search for
	 * @param bool[optional] $withNearMatch use near_match if true
	 * @return array result of all searches
	 */
	public function getMatches ($searchtext, $withNearMatch = false){
		global $options;
		
		// catch all output to the console
		ob_start();
		
		// base definition of the return array
		$matches = array('error'	   => '',
						 'result'	  => array());
		
		if (!@mysql_connect($options['fev2']['dbhost'], $options['fev2']['dbuser'], $options['fev2']['dbpass']) || !@mysql_select_db($options['fev2']['dbname'])) {
			$matches['error'] = 'no database connection';
			return $matches;
		}
		mysql_query("SET character set utf8");
		
		// split the input at newlines into several queries
		$searchItems = preg_split("[\n|\r]", $searchtext, -1, PREG_SPLIT_NO_EMPTY);
		
		foreach ($searchItems as $searchItem) {
			$searchresult = array();
			$sort1 = $sort2 = $sort3 = array();
			$lev = array();
			$ctr = 0;  // how many checks did we do
		
			if (strpos(trim($searchItem), ' ') === false) {
				$type = 'uni';								// we're asked for a uninomial
		
				if ($withNearMatch) {
					$searchItemNearmatch = $this->_near_match($searchItem, false, true); // use near match if desired
					$uninomial		   = ucfirst(trim($searchItemNearmatch));
					$lenUninomial		= mb_strlen(trim($searchItemNearmatch), "UTF-8");
				} else {
					$searchItemNearmatch = '';
					$uninomial		   = ucfirst(trim($searchItem));
					$lenUninomial		= mb_strlen(trim($searchItem), "UTF-8");
				}

				$uninomial=strtolower($uninomial);
				$lenlim=min((int)($lenUninomial/2),$this->limit-1);
				
				$uninomial=mysql_real_escape_string($uninomial);
				
				$query="
SELECT 
 f.GENUS_NAME,
 f.FAEU_TAXON_ID,
 mdld('{$uninomial}', f.GENUS_NAME, {$this->block_limit}, {$this->limit}) AS mdld

FROM
 Taxon_FaEu_v2 f

WHERE
 mdld('{$uninomial}', f.GENUS_NAME, {$this->block_limit}, {$this->limit}) <=  LEAST(CHAR_LENGTH(f.GENUS_NAME)/2,{$lenlim})
";

				$res = mysql_query($query);
echo $query;

				
				/**
				* do the actual calculation of the distances
				* and decide if the result should be kept
				*/
				while ($row = mysql_fetch_array($res)) {
							
					$searchresult[] = array(
						'genus'	=> $row['GENUS_NAME'],
						'distance' => $row['mdld'],
						'ratio'	=> 1 - $row['mdld'] / max(mb_strlen($row['GENUS_NAME'], "UTF-8"), $lenUninomial),								'taxon'	=> $row['GENUS_NAME'],
						'ID'	   => $row['FAEU_TAXON_ID'],
						'species'  => array()
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
			} else {
				$type = 'multi';
				// parse the taxon string
				$parts = $this->_tokenizeTaxa($searchItem);
		
				// use near match if desired
				if ($withNearMatch) {
					$parts['genus']	  = $this->_near_match($parts['genus'], false, true);
					$parts['subgenus']   = $this->_near_match($parts['subgenus'], false, true);
					$parts['epithet']	= $this->_near_match($parts['epithet'], true);
					$parts['subepithet'] = $this->_near_match($parts['subepithet'], true);
					$searchItemNearmatch = $this->_formatTaxon($parts);
				} else {
					$searchItemNearmatch = '';
				}
		
				// distribute the parsed string to different variables and calculate the (real) length
				$genus[0]	= ucfirst($parts['genus']);
				$lenGenus[0] = mb_strlen($parts['genus'], "UTF-8");
				$genus[1]	= ucfirst($parts['subgenus']);			  // subgenus (if any)
				$lenGenus[1] = mb_strlen($parts['subgenus'], "UTF-8");   // real length of subgenus
				$epithet	 = $parts['epithet'];
				$lenEpithet  = mb_strlen($parts['epithet'], "UTF-8");
				$rank		= $parts['rank'];
				$epithet2	= $parts['subepithet'];
				$lenEpithet2 = mb_strlen($parts['subepithet'], "UTF-8");
		
				/**
				 * first do the search for the genus and subgenus
				 */
				for ($i = 0; $i < 2; $i++) {
					
					$uninomial=mysql_real_escape_string($genus[$i]);
					
					$query="
SELECT 
 f.GENUS_NAME,
 f.FAEU_TAXON_ID
 tr.rank,
 tr.id as trid,
 mdld('{$uninomial}', f.GENUS_NAME, {$this->block_limit}, {$this->limit}) AS mdld

FROM
 Taxon_FaEu_v2 f

WHERE
 mdld('{$uninomial}', f.GENUS_NAME, {$this->block_limit}, {$this->limit}) <=  LEAST(CHAR_LENGTH(f.GENUS_NAME)/2,{$lenlim})
";
		
					
					/**
					* do the actual calculation of the distances
					* and decide if the result should be kept
					*/
					while ($row = mysql_fetch_array($res)) {
								
						$lev[] = array(
							'genus'	=> $row['GENUS_NAME'],
							'distance' => $row['mdld'],
							'ratio'	=> 1 - $row['mdld'] / max(mb_strlen($row['GENUS_NAME'], "UTF-8"), $lenUninomial), // todo: len in mysql...
							'taxon'	=> $row['GENUS_NAME'] . ' (family: )',
							'ID'	   => $row['FAEU_TAXON_ID'],
							'species'  => array()
						);
						$ctr++;
					}
				
					if (empty($genus[1])) break;	// no subgenus, we're finished here
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
					$sql = "SELECT FULLNAMECACHE,SPECIES_EPITHET,INFRASPECIES_EPITHET ";
					if ($epithet) {  // if an epithet was given, use it
						$uninomial=mysql_real_escape_string($epithet);
						$sql .= ", mdld('{$uninomial}', SPECIES_EPITHET, {$this->block_limit}, {$this->limit})  as mdld";
						if ($epithet2 && $rank) {  // if a subepithet was given, use it
							$uninomial=mysql_real_escape_string($epithet2);
							$sql .= ", mdld('{$uninomial}', INFRASPECIES_EPITHET, {$this->block_limit}, {$this->limit}) as mdld2";
						}
					}
					$sql .= " FROM Taxon_FaEu_v2
							  WHERE (GENUS_NAME = '" . $val['genus'] . "' OR INFRAGENUS_NAME = '" . $val['genus'] . "')";
					if (!$epithet2 && $rank) {
						$sql .= " AND (INFRASPECIES_EPITHET IS NULL OR INFRASPECIES_EPITHET = '')";
					}
					$res = mysql_query($sql);
					while ($row = mysql_fetch_array($res)) {
						$name = trim($row['SPECIES_EPITHET']);
						$found = false;
						if ($epithet) {
							$distance = $row['mdld'];
							$limit = min($lenEpithet, mb_strlen($row['SPECIES_EPITHET'], "UTF-8")) / 2;				   // 1st limit of the search
							if (($distance + $val['distance']) <= 4 && $distance <= 4 && $distance <= $limit) {   // 2nd limit of the search
								if ($epithet2 && $rank) {
									$limit2 = min($lenEpithet2, mb_strlen($row['INFRASPECIES_EPITHET'], "UTF-8")) / 2;	// 3rd limit of the search
									if ($row['mdld2'] <= 4 && $row['mdld2'] <= $limit2) {						 // 4th limit of the search
										$found = true;  // we've hit something
										$ratio = 1
											   - $distance / max(mb_strlen($row['SPECIES_EPITHET'], "UTF-8"), $lenEpithet)
											   - $row['mdld2'] / max(mb_strlen($row['INFRASPECIES_EPITHET'], "UTF-8"), $lenEpithet2);
										$distance += $row['mdld2'];
									}
								} else {
									$found = true;  // we've hit something
									$ratio = 1 - $distance / max(mb_strlen($row['SPECIES_EPITHET'], "UTF-8"), $lenEpithet);
								}
							}
						} else {
							$found = true;  // no epithet, so we've hit something anyway
							$ratio = 1;
							$distance = 0;
						}
		
						// if we've found anything valuable, look for the synonyms and put everything together
						if ($found) {
							// put everything into the output-array
							$lev2[] = array(
								'name'	 => $name,
								'distance' => $distance + $val['distance'],
								'ratio'	=> $ratio * $val['ratio'],
								'taxon'	=> $row['FULLNAMECACHE'],
								'taxonID'  => ' ',
								'syn'	  => '',
								'synID'	=> 0
							);
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
			}
		
			$matches['result'][] = array('searchtext'		  => $searchItem,
										 'searchtextNearmatch' => $searchItemNearmatch,
										 'rowsChecked'		 => $ctr,
										 'type'				=> $type,
										 'database'			=> 'faeu',
										 'searchresult'		=> $searchresult);
		}
		$matches['error'] = ob_get_clean();
		
		return $matches;
	}


}

?>