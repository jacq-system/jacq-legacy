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
/* 
 INSERT IGNORE INTO fuzzy_fastsearch_taxon_faeu_v2

(GENUS_NAME)

SELECT GENUS_NAME  FROM taxon_faeu_v2

*/

error_reporting(0);
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
			/***********************************************
			 * Single
			 *
			 *
			 ***********************************************/
			if (strpos(trim($searchItem), ' ') === false) {
				$type = 'uni';								// we're asked for a uninomial
		
				if ($withNearMatch) {
					$searchItemNearmatch = $this->_near_match($searchItem, false, true); // use near match if desired
					$uninomial		   = strtolower(trim($searchItemNearmatch));
				} else {
					$searchItemNearmatch = '';
					$uninomial		   = strtolower(trim($searchItem));
				}
				

				$searchresult=$this->getUninomial($uninomial);
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
				
				$searchresult= $this->getMultinomal($parts);
			}
		
			$matches['result'][] = array(
				'searchtext'   => $searchItem,
				'searchtextNearmatch' => $searchItemNearmatch,
				// 'rowsChecked'		 => $ctr,
				'rowsChecked'		 =>  '',//134399+376498,
				'type'				=> $type,
				'database'			=> 'col2011ac',
				'searchresult'		=> $searchresult
			);
		}
		$matches['error'] = ob_get_clean();
	
		return $matches;
	}

	
	
	function getMultinomal($parts){
		$lev=array();
		$genus	= strtolower(trim($parts['genus'])); // Uniominal = genus
		$lenGenus=mb_strlen($genus);
		
		
		//$subgenus	= strtolower($parts['subgenus']);			  // subgenus (if any)
		
		$species	 = strtolower(trim($parts['epithet'])); // Binomial = species
		$lenSpecies=mb_strlen($species);
		
		$rank		= $parts['rank'];						// Trinomial
		$epithet	= strtolower(trim($parts['subepithet']));
		$lenEpithet=mb_strlen($epithet);
		
		// Search Uniominal FAST in Names (g=1 => this name refers to a genus
		
		
		// Genus Fetched (Uniomnial)
		$genusids=$this->getUninomial($genus,true);
		
		// Now: Fetch Species

		// Bi und Trinomial...
		$query="
SELECT
	taxon_id as 'taxon_id',
	genus_id  as 'g_id',
	genus_name  as 'g_name',
	species_id  as 's_id',
	species_name as 's_name',
	infraspecies_id  as 'i_id',
	infraspecies_name as 'i_name',
	infraspecific_marker as 'marker',
	status as 'status',
	author as 'author',
	family_name as 'f_name',
	mdld('{$genus}', genus_name, {$this->block_limit}, {$this->limit}) as 'mdld_g',
	mdld('{$species}', species_name, {$this->block_limit}, {$this->limit}) as 'mdld_s',
	mdld('{$epithet}', infraspecies_name, {$this->block_limit}, {$this->limit})  as 'mdld_i'
FROM
 `_species_details`
WHERE
 genus_id in ({$genusids})
";
		//echo $query;exit;
		$res = mysql_query($query);
		while ($row = mysql_fetch_array($res)) {
			// If no infraspecies was given but found... discard
			if(!($epithet && $rank) && $row['i_id']!="0" ){
				continue;
			}
			
			$found=false;
			$g_ratio=0;
			
			// if a species was given...
			if($species){
			
				$distance=$row['mdld_s'];
				// we've hit a species
				if( ($distance + $row['mdld_g']) < $this->limit && $row['mdld_s'] < min($lenSpecies, mb_strlen($row['s_name'], "UTF-8")) / 2 ){
					
					// if epithet
					if($epithet && $rank){
					
						// we've hit an epithet
						if ($row['mdld_i'] <= $this->limit && $row['mdld_i'] < min($lenEpithet, mb_strlen($row['i_name'], "UTF-8")) / 2 ) {                         // 4th limit of the search
							$found = true;  
							$ratio = 1
									- $row['mdld_s'] / max(mb_strlen($row['s_name'], "UTF-8"), $lenSpecies)
									- $row['mdld_i'] / max(mb_strlen($row['i_name'], "UTF-8"), $lenEpithet);
							$distance += $row['mdld_i'];
						}
					
					// if no epithet was given
					}else{
						$found = true;
						$ratio = 1 - $row['mdld_s'] / max(mb_strlen($row['s_name'], "UTF-8"), $lenEpithet);
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
				
				if(!isset($lev[$row['g_id']]['ID'])){
					$g_ratio=1 - $row['mdld_g'] / max(mb_strlen($row['g_name'], "UTF-8"), $lenGenus);
				
					$lev[$row['g_id']]=array(
						'genus'    => $row['g_name'],
						'distance' => $row['mdld_g'],
						'ratio'    => $g_ratio,
						'taxon'    => $row['g_name'] . ' (' . $row['f_name'] . ')',
						'ID'       => $row['g_id'],
						'species' => array()
					);
				}
				// Look for synonym!&& ( $row['status']==2 || $row['status']==2 ){
				if(false){
				//	$syn=trim($row2['g_name']." ".$row2['s_name']." ".$row2['infraspecific_marker']." ".$row2['i_name']." ".$row2['author']);
				//	$synID = $row2['g_id'];
				}else{
					$syn = '';
					$synID = 0;
				}
				$taxon = trim($row['g_name']." ".$row['s_name']." ".$row['marker']." ".$row['i_name']." ".$row['author']);         
					;
			
				if($g_ratio==0){
					$g_ratio=1 - $row['mdld_g'] / max(mb_strlen($row['g_name'], "UTF-8"), $lenGenus);
				}
				// put everything into the output-array
				$lev[$row['g_id']]['species'][]=array(
					'name'     => trim($row['s_name']),
					'distance' => $distance + $row['mdld_g'],
					'ratio'    => $g_ratio * $ratio,
					'taxon'    => $taxon,
					'taxonID'  => $row['taxon_id'],
					'syn'      => $syn,
					'synID'    => $synID
				);
			}
			
			
			//$ctr++;
		}
		return $lev;
		exit;
	}
			
	function getUninomial($uninomial,$getGenusIds=false){
		$j=0;
		$ctr=0;
		
		$lenUninomial=mb_strlen(trim($uninomial));
		$lenlim=min($lenUninomial/2,$this->limit);


		// getGenusIds out of genusNamesCache

		$query="
SELECT
 genusids
FROM
 fuzzy_fastsearch_scientific_name_element2
WHERE
 mdld('{$uninomial}', genus_name, {$this->block_limit}, {$this->limit}) <  LEAST(CHAR_LENGTH(genus_name)/2,{$lenlim})
 ";
//echo $query;exit;
		$res = mysql_query($query);
		
		$s="'0'";
		while ($row = mysql_fetch_array($res)) {
			$s.=",".$row['genusids']."";
		}
		
		// For Multinomials
		if($getGenusIds){
			return $s;
		}
		
		$uninomial=ucfirst($uninomial);
		// "Fill" Out Genus...
$query2="
SELECT
distinct
 genus_id,
 genus_name,
 family_name,
 'genus' as 'rank',
 mdld('{$uninomial}', genus_name, {$this->block_limit}, {$this->limit}) AS 'mdld'
FROM
 _species_details		
WHERE
genus_id in ({$s})
";
		$res = mysql_query($query2);
		while ($row = mysql_fetch_array($res)) {
			$sr = array(
				'genus'	=> $row['genus_name'],
				'taxon'	=> $row['genus_name'].' ('.$row['family_name'].') ',
				'distance' => $row['mdld'],
				'ratio'	=> 1 - $row['mdld'] / max(mb_strlen($row['genus_name'], "UTF-8"), $lenUninomial),
				'ID'	   => $row['genus_id'],
				'type'	=> $row['rank']
			);
			$searchresult[] = $sr;
			
			$j++;
			$ctr++;
		}
		
		// Search kingdom-genus from search Cache
$query2="
SELECT
 name_element,
 rank,
 mdld('{$uninomial}', name_element, {$this->block_limit}, {$this->limit}) AS 'mdld'
FROM
 fuzzy_fastsearch_scientific_name_element1		
WHERE
 mdld('{$uninomial}', name_element, {$this->block_limit}, {$this->limit}) < LEAST(CHAR_LENGTH(name_element)/2,{$lenlim})

";
//echo $query2;exit;
		$res = mysql_query($query2);
		while ($row = mysql_fetch_array($res)) {
			$sr = array(
				'genus'	=> '',
				'taxon'	=> $row['name_element'].' ('.$row['rank'].') ',
				'distance' => $row['mdld'],
				'ratio'	=> 1 - $row['mdld'] / max(mb_strlen($row['name_element'], "UTF-8"), $lenUninomial),
				'ID'	   => 0,
				'type'	=> $row['rank']
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
