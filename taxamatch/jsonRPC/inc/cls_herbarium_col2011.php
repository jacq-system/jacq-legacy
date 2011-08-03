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
				'rowsChecked'		 =>  '134906',//134399+376498,
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
		
		$lenlim=min($lenGenus/2,$this->limit);

		
		//$subgenus	= strtolower($parts['subgenus']);			  // subgenus (if any)
		
		$species	 = strtolower(trim($parts['epithet'])); // Binomial = species
		$lenSpecies=mb_strlen($species);
		
		$rank		= $parts['rank'];						// Trinomial
		$epithet	= strtolower(trim($parts['subepithet']));
		$lenEpithet=mb_strlen($epithet);
		
		// Search Uniominal FAST in Names (g=1 => this name refers to a genus
		
		
		// Genus Fetched (Uniomnial)
		$query="
SELECT
 genusids
FROM
 fuzzy_fastsearch_scientific_name_element
WHERE
 rank in ('genus')
 and 
 mdld('{$genus}',genus_name, {$this->block_limit}, {$this->limit}) <  LEAST(CHAR_LENGTH(genus_name)/2,{$lenlim})
 ";

 //echo $query;exit;
 
		$res = mysql_query($query);
		$s="";
		while ($row = mysql_fetch_array($res)) {
			$s.=",".$row['genusids']."";
		}
		$s=substr($s,1);

		// Now: Fetch Species

		// Bi und Trinomial...
		$query="
SELECT
 s2.id as 'taxon_id',
 s2.genus as 'g_name',
 s2.species as 's_name',
 s2.infraspecies as 'i_name',
 s2.infraspecific_marker as 'marker',
 s2.status as 'status',
 s2.author as 'author',
 s2.family as 'f_name',
 s2.accepted_species_id as 'accepted_species_id',
 s2.accepted_species_name 	as 'accepted_species_name',
 s2.accepted_species_author as 'accepted_species_author',
 
 
 mdld('{$genus}', LOWER(s2.genus), {$this->block_limit}, {$this->limit}) as 'mdld_g',
 mdld('{$species}', LOWER(s2.species), {$this->block_limit}, {$this->limit}) as 'mdld_s',
 mdld('{$epithet}', LOWER(s2.infraspecies), {$this->block_limit}, {$this->limit})  as 'mdld_i'
FROM
 _search_scientific s1
 LEFT JOIN _search_scientific s2 ON s2.genus = s1.genus
 
WHERE
 s1.id in ({$s})
";

		//echo $query;exit;
		$res = mysql_query($query);
		while ($row = mysql_fetch_array($res)) {
			// If no infraspecies was given but found... discard
			if(!($epithet && $rank) && $row['i_name']!="" ){
				continue;
			}
			$found=false;
			$g_ratio=0;
			
			
			// if a species was given...
			if($species){
				$distance=$row['mdld_s'];
			
				// we've hit a species
				if( (($distance + $row['mdld_g']) < $this->limit )&& ($row['mdld_s'] <= min($lenSpecies, mb_strlen($row['s_name'], "UTF-8")) / 2 )){
					// if epithet
					if($epithet && $rank){
					
						// we've hit an epithet
						if ($row['mdld_i'] <= $this->limit && $row['mdld_i'] <= min($lenEpithet, mb_strlen($row['i_name'], "UTF-8")) / 2 ) {                         // 4th limit of the search
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
				
				if(!isset($lev[$row['g_name']]['ID'])){
					$g_ratio=1 - $row['mdld_g'] / max(mb_strlen($row['g_name'], "UTF-8"), $lenGenus);
				
					$lev[$row['g_name']]=array(
						'genus'    => $row['g_name'],
						'distance' => $row['mdld_g'],
						'ratio'    => $g_ratio,
						'taxon'    => $row['g_name'] . ' (' . $row['f_name'] . ')',
						'ID'       => $row['g_name'],
						'species' => array()
					);
				}
				
				if($row['accepted_species_id']!=''){
					$syn=trim($row['accepted_species_name']." ".$row['accepted_species_author']);
					$synID = $row['accepted_species_id'];
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
				$lev[$row['g_name']]['species'][]=array(
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

		foreach($lev as $k=>$obj){
			usort($obj['species'],'col2011sort_a');
			$lev[$k]=$obj;
		}
		usort($lev,'col2011sort_a');
		
		
		return $lev;
	}
			
	function getUninomial($uninomial,$getGenusIds=false){
		$j=0;
		$ctr=0;
		
		$lenUninomial=mb_strlen(trim($uninomial));
		$lenlim=min($lenUninomial/2,$this->limit);

		$uninomial=strtolower($uninomial);
		// getGenusIds out of genusNamesCache

		$query="
SELECT
 genusids
FROM
 fuzzy_fastsearch_scientific_name_element
WHERE
 rank in ('genus','kingdom','phylum','class','order','superfamily','family')
 and 
 mdld('{$uninomial}',genus_name, {$this->block_limit}, {$this->limit}) <  LEAST(CHAR_LENGTH(genus_name)/2,{$lenlim})
 ";

 //echo $query;exit;
 
		$res = mysql_query($query);
		$s="";
		while ($row = mysql_fetch_array($res)) {
			$s.=",".$row['genusids']."";
		}
		$s=substr($s,1);

		// "Fill" Out Genus...
		$query2="
SELECT
 s.id,
 s.genus,
 s.family,
 tr.rank as 'rank',
 mdld('{$uninomial}', LOWER(s.genus), {$this->block_limit}, {$this->limit}) AS 'mdld'
FROM
 _search_scientific s
 LEFT JOIN taxon t on t.id=s.id 
 LEFT JOIN taxonomic_rank tr on tr.id=t.taxonomic_rank_id
WHERE
 s.id in ({$s})
";
//echo "$query2 s";exit;
		$res = mysql_query($query2);
	
		while ($row = mysql_fetch_array($res)) {
			$sr = array(
				'genus'	=> $row['genus'],
				'taxon'	=> $row['genus'].' ('.$row['family'].') ',
				'distance' => $row['mdld'],
				'ratio'	=> 1 - $row['mdld'] / max(mb_strlen($row['genus'], "UTF-8"), $lenUninomial),
				'ID'	   => $row['id'],
				'type'	=> $row['rank']
			);
			$searchresult[] = $sr;
			
			$j++;
			$ctr++;
		}
		
		usort($searchresult,'col2011sort_a');
		return $searchresult;
	}

				

}

function col2011sort_a($a,$b){
    if($a['distance']==$b['distance']) {
		if($a['ratio']==$b['ratio']){
			return strcmp($a['taxon'],$b['taxon']);
		}
		return($a['ratio']<$b['ratio'])?-1:1;		
    }
    return($a['distance']<$b['distance'])?-1:1;
}


