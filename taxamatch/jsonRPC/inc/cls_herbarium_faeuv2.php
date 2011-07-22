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
require_once('inc/variables.php');

class cls_herbarium_faeuv2 extends cls_herbarium_base {

	var $block_limit=2;
	var $limit=4;
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
				} else {
					$searchItemNearmatch = '';
					$uninomial		   = ucfirst(trim($searchItem));
				}
				
				$searchresult=$this->getUninomial($uninomial);
				
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
				
				$searchresult=array_merge($searchresult, $this->getMultinomal($parts));
				

			}

			$matches['result'][] = array('searchtext'		  => $searchItem,
										'searchtextNearmatch' => $searchItemNearmatch,
										// 'rowsChecked'		 => $ctr,
										'rowsChecked'		 => '28289',
										 'type'				=> $type,
										 'database'			=> 'faeuv2',
										 'searchresult'		=> $searchresult);
		}
		$matches['error'] = ob_get_clean();

		return $matches;
	}


	function getMultinomal($parts){
		
		$lev=array();
		// distribute the parsed string to different variables and calculate the (real) length
		$genus[0]	= ucfirst($parts['genus']);
		$lenGenus[0] = mb_strlen($parts['genus'], "UTF-8");
		$lenGenuslim[0]=min((int)( $lenGenus[0]/2),$this->limit-1);
		
		$genus[1]	= ucfirst($parts['subgenus']);			  // subgenus (if any)
		$lenGenus[1] = mb_strlen($parts['subgenus'], "UTF-8");   // real length of subgenus
		$lenGenuslim[1]=min((int)( $lenGenus[1]/2),$this->limit-1);

		$epithet	 = $parts['epithet'];
		$lenEpithet  = mb_strlen($parts['epithet'], "UTF-8");
		$rank		= $parts['rank'];
		$epithet2	= $parts['subepithet'];
		$lenEpithet2 = mb_strlen($parts['subepithet'], "UTF-8");
				
		
		$query="
SELECT
 taxonids
FROM
 fuzzy_fastsearch_name_element1 f
WHERE
 mdld('{$genus[0]}', genus_name, {$this->block_limit}, {$this->limit}) <  LEAST(CHAR_LENGTH(genus_name)/2,{$lenGenuslim[0]})

UNION ALL

SELECT
 taxonids
FROM
 fuzzy_fastsearch_name_element2 f
WHERE
 mdld('{$genus[1]}', subgenus_name, {$this->block_limit}, {$this->limit}) <  LEAST(CHAR_LENGTH(subgenus_name)/2,{$lenGenuslim[1]})
 ";
		$res = mysql_query($query);
		
		$s="";
		while ($row = mysql_fetch_array($res)) {
			$s.=",".$row['taxonids']."";
		}
		$s=substr($s,1);

/*
id 	FULLNAMECACHE 	GENUS_NAME 	INFRAGENUS_NAME 	SPECIES_EPITHET 	INFRASPECIES_EPITHET 	AUTHOR_NAME 	YEAR 	BRACKETS 	FAEU_TAXON_ID 	TAXON_ID_SPECIES_PARENT 	TAXON_ID_GENUS_PARENT 	TAXON_ID_FAMILY 	FAEU_TAXON_LSID 	FAEU_URL 	PESI_URL 	STATUS

*/		// Bi und Trinomial...
		$query="
SELECT
	id,
	GENUS_NAME,
	INFRAGENUS_NAME,
	SPECIES_EPITHET,
	INFRASPECIES_EPITHET,
	AUTHOR_NAME,
	YEAR,
	FULLNAMECACHE,
	mdld('{$genus[0]}', GENUS_NAME, {$this->block_limit}, {$this->limit}) as 'mdld_g',
	mdld('{$genus[1]}', INFRAGENUS_NAME, {$this->block_limit}, {$this->limit}) as 'mdld_sg',
	mdld('{$epithet}', SPECIES_EPITHET, {$this->block_limit}, {$this->limit}) as 'mdld_e',
	mdld('{$epithet2}', INFRASPECIES_EPITHET, {$this->block_limit}, {$this->limit})  as 'mdld_i'
FROM
 Taxon_FaEu_v2
WHERE
 id in ({$s})
";
	
		$res = mysql_query($query);
		while ($row = mysql_fetch_array($res)) {
			
			$found=false;
			$g_ratio=0;
			
			// if a epithet was given...
			if($epithet){
			
				$distance=$row['mdld_e'];
				// we've hit a species
				if( ($distance + $row['mdld_g']) < $this->limit && $distance < min($lenEpithet, mb_strlen($row['SPECIES_EPITHET'], "UTF-8")) / 2 ){
					
					// if epithet
					if($epithet && $rank){
					
						// we've hit an epithet
						if ($row['mdld_i'] < $this->limit && $row['mdld_i'] < min($lenEpithet2, mb_strlen($row['INFRASPECIES_EPITHET'], "UTF-8")) / 2 ) {                         // 4th limit of the search
							$found = true;  
							$ratio = 1
									- $row['mdld_e'] / max(mb_strlen($row['SPECIES_EPITHET'], "UTF-8"), $lenEpithet)
									- $row['mdld_i'] / max(mb_strlen($row['INFRASPECIES_EPITHET'], "UTF-8"), $lenEpithet2);
							$distance += $row['mdld_i'];
						}
					
					// if no epithet was given
					}else{
						$found = true;
						$ratio = 1 - $row['mdld_e'] / max(mb_strlen($row['SPECIES_EPITHET'], "UTF-8"), $lenEpithet);
					}

				}
			// only "genus" and "subgenus" => not implemented...
			}else{
				$found = true;  // no epithet, so we've hit something anyway
				$ratio = 1;
				$distance = 0;
			}
			
			// of found and synonynm accepted code
			if($found){
				
				if(!isset($lev[$row['id']]['ID'])){
					$g_ratio=1 - $row['mdld_g'] / max(mb_strlen($row['GENUS_NAME'], "UTF-8"), $lenGenus[1]);
					$lev[$row['id']]=array(
						'genus'    => $row['GENUS_NAME'],
						'distance' => $row['mdld_g'],
						'ratio'    => $g_ratio,
						'taxon'    => $row['GENUS_NAME'] . ' (family: )',
						'ID'       => $row['id'],
						'species' => array()
					);
				}
				
				if($g_ratio==0){
					$g_ratio=1 - $row['mdld_g'] / max(mb_strlen($row['GENUS_NAME'], "UTF-8"), $lenGenus);
				}
				// put everything into the output-array
				$lev[$row['id']]['species'][]=array(
					'name'     => trim($row['SPECIES_EPITHET']),
					'distance' => $distance + $row['mdld_g'],
					'ratio'    => $g_ratio * $ratio,
					'taxon'    => $row['FULLNAMECACHE'],
					'taxonID'  => $row['id'],
					'syn'      => '',
					'synID'    => 0
				);
			}

			//$ctr++;
		}
		return $lev;
	}

				
	function getUninomial($uninomial,$getGenusIds=false){
		$j=0;
		$ctr=0;
		
		$lenUninomial=mb_strlen(trim($uninomial));
		$lenlim=min( $lenUninomial/2,$this->limit);

		
		$query="
SELECT
 genus_name,
 mdld('{$uninomial}', genus_name, {$this->block_limit}, {$this->limit}) AS 'mdld',
 h.TAXON_NAME as 'family_name'
FROM
 fuzzy_fastsearch_name_element1 f
 LEFT JOIN Hierarchy_FaEu_v2 h on h.TAXONID=f.familyids
WHERE
 mdld('{$uninomial}', genus_name, {$this->block_limit}, {$this->limit}) <  LEAST(CHAR_LENGTH(genus_name)/2,{$lenlim})

UNION ALL

SELECT
 subgenus_name as 'genus_name',
 mdld('{$uninomial}', subgenus_name, {$this->block_limit}, {$this->limit}) AS 'mdld',
 h.TAXON_NAME as 'family_name'
FROM
 fuzzy_fastsearch_name_element2 f
 LEFT JOIN Hierarchy_FaEu_v2 h on h.TAXONID=f.familyids
WHERE
 mdld('{$uninomial}', subgenus_name, {$this->block_limit}, {$this->limit}) <  LEAST(CHAR_LENGTH(subgenus_name)/2,{$lenlim})
 ";
 //echo $query;exit;
		$res = mysql_query($query);
		$tmp=array();
		while ($row = mysql_fetch_array($res)) {
			if(isset($tmp[$row['genus_name']]))continue;
			$tmp[$row['genus_name']]=1;
			$sr = array(
				'genus'	=> $row['genus_name'],
				'taxon'	=> $row['genus_name'].' ('.$row['family_name'].') ',
				'distance' => $row['mdld'],
				'ratio'	=> 1 - $row['mdld'] / max(mb_strlen($row['genus_name'], "UTF-8"), $lenUninomial),
				'ID'	   => 0,
				'type'	=> 'genus'
			);
			$searchresult[] = $sr;
			
			$j++;
			$ctr++;
		}
		
		
		$this->_doMultiSort($searchresult);
		
		
		return $searchresult;
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