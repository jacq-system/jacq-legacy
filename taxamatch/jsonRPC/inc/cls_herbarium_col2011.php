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

 
class cls_herbarium_col2011 extends cls_herbarium_base {

	var $block_limit=2;
	var $limit=4;

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
	public function getMatches ($searchtext, $withNearMatch = false){
	
		global $options;
		
		// catch all output to the console
		ob_start();
		
		// base definition of the return array
		$matches = array('error'	   => '',
						 'result'	  => array());
		
		if (!@mysql_connect($options['col2011']['dbhost'], $options['col2011']['dbuser'], $options['col2011']['dbpass']) || !@mysql_select_db($options['col2011']['dbname'])) {
			$matches['error'] = 'no database connection';
			return $matches;
		}
		mysql_query("SET character set utf8");
		
		// split the input at newlines into several queries
		$searchItems = preg_split("[\n|\r]", $searchtext, -1, PREG_SPLIT_NO_EMPTY);
		$fam_dump=array();
		
		foreach ($searchItems as $searchItem) {
			$searchresult = array();
			$sort1 = $sort2 = $sort3 = array();
		//		$fullHit = false;
			$lev = array();
			$ctr = 0;  // how many checks did we do
			
			$j=0;
			
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
				
				$species=(isset($species))?",'species'":'';
				$uninomial=mysql_real_escape_string($uninomial);
				
				$query="
SELECT 
 sn.name_element as name,
 tne.taxon_id,
 tr.rank,
 tr.id as trid,
 mdld('{$uninomial}', sn.name_element, {$this->block_limit}, {$this->limit}) AS mdld

FROM
 scientific_name_element sn
 LEFT JOIN taxon_name_element tne ON tne.scientific_name_element_id=sn.id
 LEFT JOIN taxon t ON t.id=tne.taxon_id
 LEFT JOIN taxonomic_rank tr ON tr.id=t.taxonomic_rank_id
 
WHERE
 mdld('{$uninomial}', sn.name_element, {$this->block_limit}, {$this->limit}) <=  LEAST(CHAR_LENGTH(sn.name_element)/2,{$lenlim})
HAVING
 trid in (17,20,54,72,76,112,83)
";

	/*
17	family
20	genus
54	kingdom
72	order
76	phylum
112	superfamily
83	species
*/
	
	 /*
	$limit = min($lenUninomial, strlen($row['genus'])) / 2;	 // 1st limit of the search
	if ($row['mdld'] <= 3 && $row['mdld'] < $limit) {		   // 2nd limit of the search
	available:
	('kingdom','phylum','class','order','superfamily','family','genus','subgenus','species','infraspecies')
	group='Plantae'
	*/
				
				$res = mysql_query($query);

	
				/**
				* do the actual calculation of the distances
				* and decide if the result should be kept
				*/
				while ($row = mysql_fetch_array($res)) {
					
					$sr = array(
						'genus'	=> '',
						'taxon'	=> $row['name'],
						'distance' => $row['mdld'],
						'ratio'	=> 1 - $row['mdld'] / max(mb_strlen($row['name'], "UTF-8"), $lenUninomial), // todo: len in mysql...
						'ID'	   => $row['taxon_id'],
						'species'  => array()
					);
							
					switch($row['rank']){
						
						case 'genus':
							$sr = array_merge($sr,array(
								'genus'	=> $row['name'],
								'taxon'	=> $row['name'],
								'type'	=> $row['rank']
							));
							break;
							
						case 'family': 
							//$sr = array_merge($sr,array());
							break;
							
						case 'superfamily': 
							//$sr = array_merge($sr,array());
							break;
						
						case 'order': 
							//$sr = array_merge($sr,array());
							break;
						case 'class': 
							//$sr = array_merge($sr,array());
							break;
						case 'phylum': 
							//$sr = array_merge($sr,array());
							break;
						case 'kingdom': 
							//$sr = array_merge($sr,array());
							break;
						case 'species': 
							//$sr = array_merge($sr,array());
							break;
						default:
							break;
					}
						
					$searchresult[$row['taxon_id']] = $sr;
					$j++;
					$ctr++;
				}
				
				$this->_doFamily($searchresult,$fam_dump);
				$this->_doMultiSort($searchresult);
				
				
				
			/***********************************************
			 * MULTI
			 *
			 *
			 ***********************************************/
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
				$genus[0]	= strtolower($parts['genus']);
				$lenGenus[0] = mb_strlen($parts['genus'], "UTF-8");
				$genus[1]	= strtolower($parts['subgenus']);			  // subgenus (if any)
				$lenGenus[1] = mb_strlen($parts['subgenus'], "UTF-8");   // real length of subgenus
				$epithet	 = strtolower($parts['epithet']);
				$lenEpithet  = mb_strlen($parts['epithet'], "UTF-8");
				$rank		= $parts['rank'];
				$epithet2	= strtolower($parts['subepithet']);
				$lenEpithet2 = mb_strlen($parts['subepithet'], "UTF-8");

		
				/**
				 * first do the search for the genus and subgenus
				 * to speed things up we chekc first if there is a full hit
				 * (it may not be very likely but the penalty is quite low)
				 */
				for ($i = 0; $i < 2; $i++) {
		
					$uninomial=mysql_real_escape_string($genus[$i]);
					$lenlim=min((int)($lenGenus[$i]/2),$this->limit-1);
					
					$query="
SELECT 
 sn.name_element as name,
 tne.taxon_id,
 tr.rank,
 tr.id as trid,
 mdld('{$uninomial}', sn.name_element, {$this->block_limit}, {$this->limit}) AS mdld

FROM
 scientific_name_element sn
 LEFT JOIN taxon_name_element tne ON tne.scientific_name_element_id=sn.id
 LEFT JOIN taxon t ON t.id=tne.taxon_id
 LEFT JOIN taxonomic_rank tr ON tr.id=t.taxonomic_rank_id
 
WHERE
 mdld('{$uninomial}', sn.name_element, {$this->block_limit}, {$this->limit}) <=  LEAST(CHAR_LENGTH(sn.name_element)/2,{$lenlim})
HAVING
 trid in (20,96)
";

				
					$res = mysql_query($query);

					/**
					 * do the actual calculation of the distances
					 * and decide if the result should be kept
					 */
					while ($row = mysql_fetch_array($res)) {
						$searchresult[$row['taxon_id']] = array(
							'genus'	=> $row['name'],
							'taxon'	=> $row['name'],
							'distance' => $row['mdld'],
							'ratio'	=> 1 - $row['mdld'] / max(mb_strlen($row['name'], "UTF-8"), $lenGenus[$i]), // todo: len in mysql...
							'ID'	   => $row['taxon_id'],	
							'species'  => array()
						);
						$j++;
						$ctr++;
					}
					if (empty($genus[1])) break;	// no subgenus, we're finished here
				}
				$this->_doFamily2($searchresult);
					

			
				// if there's more than one hit, sort them (faster here than within the db)

				/**
				 * second do the search for the species and supspecies (if any)
				 * if neither species nor subspecies are given, all species are returned
				 * only genera which passed the first test will be used here
				 */
				$lev2 = array();	
				$where="";
				$col="";
				$win="";
				
				foreach ($searchresult as $key => $val) {
					$win.=",'{$key}'";
				}
				$win="(".substr($win,1).")";
				
				if ($epithet) {  // if an epithet was given, use it
					$s_eptithet=mysql_real_escape_string($epithet);
					$s_eptithet_len=min((int)($lenEpithet/2),$this->limit-1);
				
					$col.="
 mdld('{$s_eptithet}', s.species_name, {$this->block_limit}, {$this->limit}) AS mdld,
";
					$where.="
 and mdld('{$s_eptithet}', s.species_name, {$this->block_limit}, {$this->limit}) <=  LEAST(CHAR_LENGTH(s.species_name)/2,{$s_eptithet_len})
";
					if ($epithet2 && $rank) {  // if a subepithet was given, use it
						$s_eptithet2=mysql_real_escape_string($epithet2);
						
						$col.="
 mdld('{$s_eptithet2}', s.infraspecies_name, {$this->block_limit}, {$this->limit}) AS mdld2,
";
							
					}else{
						$where.="
 and (s.infraspecies_name IS NULL OR s.infraspecies_name = '')
";
					}
				}
					
				$query="
SELECT
 s.taxon_id,
 s.genus_id,
 s.species_id,
 s.species_name,
 s.infraspecies_name,
 {$col}
 s.infraspecific_marker,
 s.author
FROM
  _species_details s
WHERE
 s.genus_id in {$win}
 or s.subgenus_id in {$win}
{$where}
 ";
 //echo $query;
				$res = mysql_query($query);

				while($row = mysql_fetch_array($res)) {
						$name = trim($row['species_name']);
						$found = false;
						
						$val=$searchresult[$row['genus_id']];
						
						if ($epithet) {
							$distance = $row['mdld'];
							
							if (($distance + $val['distance']) <= $this->limit ) {   // 2nd limit of the search
								$found = true;  // we've hit something
								if ($epithet2 && $rank) {
									$limit2 = min($lenEpithet2/2, mb_strlen($row['infraspecies_name'], "UTF-8")/2,$this->limit);	// 3rd limit of the search
									
									if ($row['mdld2'] <= $limit2) {						 // 4th limit of the search
										$ratio =	1 - $distance / max(mb_strlen($row['species_name'], "UTF-8"), $lenEpithet)
													  - $row['mdld2'] / max(mb_strlen($row['infraspecies_name'], "UTF-8"), $lenEpithet2);
										$distance += $row['mdld2'];
									}
									
								} else {
									$ratio = 1 - $distance / max(mb_strlen($row['species_name'], "UTF-8"), $lenEpithet);
								}
							}
						} else {
							$found = true;  // no epithet, so we've hit something anyway
							$ratio = 1;
							$distance = 0;
						}
						//echo ($found)?'ja':'nein';
						
						// synonym_name_element
						// if we've found anything valuable, look for the synonyms and put everything together
						if ($found) {
							if (true || ($row['accepted_name_code'] && $row['accepted_name_code'] != $row['name_code'])) {
								
								$query="
SELECT
 s.species_name,
 s.infraspecies_name,
 s.infraspecific_marker,
 s.author
FROM
 synonym sy,
  _species_details s
WHERE
 sy.id='{$row['genus_id']}'
 and s.taxon_id=sy.taxon_id
 ";
 
								//echo $query;
								$result2 = mysql_query($query);
								$row2 = mysql_fetch_array($result2);
								$syn = trim(
									$row2['genus']
									 . " a" . $row2['species_name']
									 . " b" . $row2['infraspecies_marker']
									 . " c" . $row2['infraspecies_name']
									 . " d" . $row2['author']
								);
								$synID = $row2['taxonID'];
							} else {
								$syn = '';
								$synID = 0;
							}
		
							// format the taxon-output
							$taxon = trim(
								$val['genus']
								. " " . $row['species_name']
								. " " . $row['infraspecies_marker']
								. " " . $row['infraspecies_name']
								. " " . $row['author']
							);
							// put everything into the output-array
							$lev2 = array(
								'name'	 => $name,
								'distance' => $distance + $val['distance'],
								'ratio'	=> $ratio * $val['ratio'],
								'taxon'	=> $taxon,
								'taxonID'  => $row['taxonID'],
								'syn'	  => $syn,
								'synID'	=> $synID
							);
							//if ($distance == 0 && $val['distance'] == 0) $fullHit = true;  // we've hit everything direct
							
							$searchresult[$row['genus_id']]['species'][] = $lev2;
							
						}
						$ctr++;
				}
	
				// if there's more than one hit, sort them (faster here than within the db)
				//$this->_doMultiSort($lev2);
				
		
				// glue everything together
				/*if (count($lev2) > 0) {
					$lev[$key] = $lev2;
					
				}*/
			}
		
			$matches['result'][] = array(
				'searchtext'   => $searchItem,
				'searchtextNearmatch' => $searchItemNearmatch,
				// 'rowsChecked'		 => $ctr,
				'rowsChecked'		 =>  '510.897',//134399+376498,
				'type'				=> $type,
				'database'			=> 'col',
				'searchresult'		=> $searchresult
			);
		}
		$matches['error'] = ob_get_clean();
	
		return $matches;
	}

	function _doFamily(&$searchresult){
		if (count($searchresult) > 0) {
			$id_s=" ('".implode("','",array_keys($searchresult))."')";
			$query="SELECT DISTINCT d.family_name, d.taxon_id FROM _species_details d where d.taxon_id IN  ".$id_s." ";
			//echo $query;
			$res = mysql_query($query);
			while($row = mysql_fetch_array($res)) {
				$searchresult[$row['taxon_id']]['taxon'].= ' (' . $row['family_name'] . ')';
			}
		}
	}
	
	function _doFamily2(&$searchresult){
		if (count($searchresult) > 0) {
			$id_s=" ('".implode("','",array_keys($searchresult))."')";
			$query="SELECT DISTINCT d.family_name, d.genus_id FROM _species_details d where d.genus_id IN  ".$id_s." ";
			//echo $query;
			$res = mysql_query($query);
			while($row = mysql_fetch_array($res)) {
				$searchresult[$row['genus_id']]['taxon'].= ' (' . $row['family_name'] . ')';
			}
		}
	}
	
	function _doMultiSort(&$searchresult){
				
		// if there's more than one hit, sort them (faster here than within the db)
		if (count($searchresult) > 1) {
			foreach ($searchresult as $key => $row) {
				$sort1[$key] = $row['distance'];
				$sort2[$key] = $row['ratio'];
				$sort3[$key] = $row['taxon'];
			}
			array_multisort($sort1, SORT_NUMERIC, $sort2, SORT_DESC, SORT_NUMERIC, $sort3, $searchresult);
		}
	}
				

}
