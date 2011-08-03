<?php
/**
 * Autocomplete methods singleton - handling all autocomplete methods
 *
 * A singleton to supply various autocomplete methods
 *
 * @author Johannes Schachner
 * @version 1.0
 * @package clsAutocomplete
 */
function p($var,$exit=false){
	
	$a=print_r($var,1);
	echo <<<EOF
<pre>
$a
</pre>
EOF;
	if($exit){
		exit;
	}
}

function langsort($a, $b){
    return strcmp($a["frucht"], $b["frucht"]);
}

error_reporting(E_ALL^E_NOTICE);
/**
 * Autocomplete methods singleton - handling all autocomplete methods
 * @package clsAutocomplete
 * @subpackage classes
 */
class clsAutocompleteCommonName
{
/********************\
|					|
|  static variables  |
|					|
\********************/

private static $instance = null;

/********************\
|					|
|  static functions  |
|					|
\********************/

/**
 * instances the class clsAutocomplete
 *
 * @return clsAutocomplete new instance of that class
 */
public static function Load()
{
	if (self::$instance == null) {
		self::$instance = new clsAutocompleteCommonName();
	}
	return self::$instance;
}

/*************\
|			 |
|  variables  |
|			 |
\*************/
			

/***************\
|			   |
|  constructor  |
|			   |
\***************/

protected function __construct () {}

/********************\
|					|
|  public functions  |
|					|
\********************/

	function _get($host,$port='80',$path='/',$data='',$timeout=30) { 
		
		$d='';$str='';
		if(!empty($data)){
			foreach($data AS $k => $v){
				$str .= urlencode($k).'='.urlencode($v).'&';
			}
			$str = substr($str,0,-1); 
		}
		
		$fp = fsockopen($host,$port,$errno,$errstr,$timeout); 
		if($fp){
			fputs($fp, "POST $path HTTP/1.1\r\n"); 
			fputs($fp, "Host: $host\r\n"); 
			fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n"); 
			fputs($fp, "Content-length: ".strlen($str)."\r\n"); 
			fputs($fp, "Connection: close\r\n\r\n"); 
			fputs($fp, $str."\r\n\r\n"); 
			
			while(!feof($fp)){
				$d .= fgets($fp,4096);
			}
			fclose($fp);
		}
		return $d;
	}
	
	function getCacheOption(){
		global $_OPTIONS;
		
		if( in_array($_OPTIONS['TYPINGCACHE']['SETTING']['type'],array('MICROSECOND','SECOND','MINUTE','HOUR','DAY','WEEK','MONTH','QUARTER','YEAR')) 
			&& intval($_OPTIONS['TYPINGCACHE']['SETTING']['val'])!==0
		){
			return "and timestamp>TIMESTAMPADD({$_OPTIONS['TYPINGCACHE']['SETTING']['type']},-{$_OPTIONS['TYPINGCACHE']['SETTING']['val']},NOW())";
		}
		return '';
	}

/**
 * autocomplete a person entry field
 * The various parts of a person field are identified and used (if present) as a search criteria
 *
 * @param string $value text to search for
 * @return array data array ready to send to jQuery-autocomplete via json-encode
 */
public function cname_person($value)
{
	$results = array();
	$where='';
	 try {
		/* @var $db clsDbAccess */
		$db = clsDbAccess::Connect('INPUT');
			
			
		if($id=extractID2($value)){
			$sql="WHERE person_ID='{$id}'";
		}else{
			if ($value && strlen($value) > 1) {
				$pieces = explode(", ", $value, 2);
				$p_familyname = $pieces[0];
				if (count($pieces) > 1) {
					$pieces = explode(" (", $pieces[1], 2);
					$p_firstname = $pieces[0];
					if (count($pieces) > 1) {
						$pieces = explode(" - ", $pieces[1], 2);
						$p_birthdate = $pieces[0];
						if (count($pieces) > 1) {
							$pieces = explode(")", $pieces[1], 2);
							$p_death = $pieces[0];
						} else {
							$p_death = '';
						}
					} else {
						$p_birthdate = $p_death = '';
					}
				} else {
					$p_firstname = $p_birthdate = $p_death = '';
				}

	   
				$sql = "
						WHERE p_familyname LIKE " . $db->quote ($p_familyname . '%');
				if ($p_firstname) $sql .= " AND p_firstname LIKE " . $db->quote ($p_firstname . '%');
				if ($p_birthdate) $sql .= " AND p_birthdate LIKE " . $db->quote ($p_birthdate . '%');
				if ($p_death)	 $sql .= " AND p_death LIKE " . $db->quote ($p_death . '%');
				$sql .= " ORDER BY p_familyname, p_firstname, p_birthdate, p_death";
			}
		}
		$sql="SELECT person_ID, p_familyname, p_firstname, p_birthdate, p_death
					FROM tbl_person {$sql}";

		/* @var $dbst PDOStatement */
		$dbst = $db->query($sql);
		$rows = $dbst->fetchAll();
		if (count($rows) > 0) {
				foreach ($rows as $row) {
					$text = $row['p_familyname'] . ", " . $row['p_firstname'] . " (" . $row['p_birthdate'] . " - " . $row['p_death'] . ") <" . $row['person_ID'] . ">";
					$results[] = array('id'	=> $row['person_ID'],
									   'label' => $text,
									   'value' => $text,
									   'color' => '');
				}
			}
	}catch (Exception $e) {
		error_log($e->getMessage());
		print_r($e->getMessage());
		exit;
	}


	return $results;
}

/**
 * autocomplete a taxon entry field
 * If the searchstring has only one part before the separator only taxa with empty species are presented.
 * If the searchstring consists of two parts the first one is used for genus, the second one for species
 *
 * @param string $value text to search for
 * @param bool[optional] $noExternals only results for "external=0" (default no)
 * @param bool[optional] $withDT adds the DallaTorre information (default no)
 * @return array data array ready to send to jQuery-autocomplete via json-encode
 */
public function cname_taxon ($value)
{
	$results = array();
	if ($value && strlen($value) > 1) {
		$pieces = explode(chr(194) . chr(183), $value);
		$pieces = explode(" ",$pieces[0]);
		try {
			$db = clsDbAccess::Connect('INPUT');

			/* @var $db clsDbAccess */
			$db = clsDbAccess::Connect('INPUT');
			$sql = "SELECT taxonID, ts.external
					FROM tbl_tax_species ts
					 LEFT JOIN tbl_tax_epithets te0 ON te0.epithetID = ts.speciesID
					 LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID = ts.subspeciesID
					 LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID = ts.varietyID
					 LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID = ts.subvarietyID
					 LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID = ts.formaID
					 LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID = ts.subformaID
					 LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
					WHERE ";
			
			if($id=extractID2($value)){
				$sql.=" ts.taxonID='{$id}'";
			}else{
				$sql.=" tg.genus LIKE " . $db->quote ($pieces[0] . '%');
				$sql .= " AND ts.external = 0";
			
				if (!empty($pieces[1])) {
					$sql .= " AND te0.epithet LIKE " . $db->quote ($pieces[1] . '%');
				} else {
					$sql .= " AND te0.epithet IS NULL";
				}
				$sql .= " ORDER BY tg.genus, te0.epithet, te1.epithet, te2.epithet, te3.epithet, te4.epithet, te5.epithet";
			}
			//echo $sql;
			/* @var $dbst PDOStatement */
			$dbst = $db->query($sql);
			$rows = $dbst->fetchAll();
			if (count($rows) > 0) {
				$display = clsDisplay::Load();
				foreach ($rows as $row) {
					$results[] = array('id'	=> $row['taxonID'],
									   'label' => $display->taxon($row['taxonID'], true, false, true),
									   'value' => $display->taxon($row['taxonID'], true, false, true),
									   'color' => ($row['external']) ? 'red' : '');
				}
				foreach ($results as $k => $v) {   // eliminate multiple whitespaces within the result
					$results[$k]['label'] = preg_replace("/ [\s]+/"," ",$v['label']);
					$results[$k]['value'] = preg_replace("/ [\s]+/"," ",$v['value']);
				}
			}
		}
		catch (Exception $e) {
			error_log($e->getMessage());
		}
	}

	return $results;
}
/**
 * Common Names: Common Name
 * @param string $value text to search for
 * @param bool[optional] $noExternals only results for "external=0" (default no)
 * @return array data array ready to send to jQuery-autocomplete via json-encode
 */
public function cname_common_name ($value){
    global $_CONFIG;
	
	$results = array();
	if ($value && strlen($value)>1){
		try{
			/* @var $db clsDbAccess */
			$db = clsDbAccess::Connect('INPUT');
			
			$where='';
			if($id=extractID2($value)){
				$where="common_id ='$id'";
			}else{
				$where="common_name LIKE " . $db->quote($value . '%');
			}
			
			$sql = "SELECT common_name,common_id FROM {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_commons WHERE {$where}";	
			
			/* @var $dbst PDOStatement */
			$dbst = $db->query($sql);
			$rows = $dbst->fetchAll();
			
			if (count($rows) > 0) {
				foreach ($rows as $row) {
					$id=$row['common_id'];
					$label=$row['common_name'];
					
					$results[] = array(
						'id'	=> $id,
						'label' => "{$label} &lt;{$id}&gt;",
						'value' => $label,
						'color' => ''
					);
				}
			}
		}catch (Exception $e){
			error_log($e->getMessage());
		}
	}

	return $results;
}

/**
 * Common Names: Geoname
 * @param string $value text to search for
 * @param bool[optional] $noExternals only results for "external=0" (default no)
 * @return array data array ready to send to jQuery-autocomplete via json-encode
 */
public function cname_geoname ($value){
	global $_OPTIONS, $_CONFIG;
	$results = array();
	$results_intern=array();
	$fetched=array();

	if ($value && strlen($value)>1){
		try{
			$db = clsDbAccess::Connect('INPUT');
			
			// Get Geonames out of database first
			$where='';
			if($id=extractID2($value)){
				$where="geonameId ='$id'";
			}else{
				$where="name LIKE ".$db->quote($value .'%');
			}
			
			$sql = "SELECT geonameId,name FROM {$_CONFIG['DATABASE']['NAME']['name']}.tbl_geonames_cache WHERE {$where}";	
			$dbst = $db->query($sql);
			$rows = $dbst->fetchAll();
			
			if (count($rows) > 0) {
				foreach($rows as $row) {
					$label=$row['name'];
					$id=$row['geonameId'];
						
					if(!isset($fetched[$id])){
								
						$results_intern[] = array(
							'id'	=> $id,
							'label' => "{$label} &lt;{$id}&gt;",
							'value' => $label,
							'color' => ''
						);
					}
				}
			
			}
			
			// Get TypeCache
			$cacheoption=$this->getCacheOption();
			$sql = "SELECT result FROM {$_CONFIG['DATABASE']['NAME']['name']}. tbl_search_cache WHERE search_group='1' and search_val=" . $db->quote($value)." {$cacheoption} LIMIT 1";	
			
			$dbst = $db->query($sql);
			$row = $dbst->fetch();
			
			// If TypeCache
			if (isset($row['result']) && $row['result'] !='') {

				$results=json_decode($row['result'],1);
			
			// Else retrieve data from geonames.org
			}else{
				$url='http://api.geonames.org';
				
				if($id){
					$url.="/getJSON?";
					$url.="style=full";
					$url.="&geonameId=".$id;
				}else{
					$url.="/searchJSON?";
					$url.="maxRows=10";
					$url.="&q=".urlencode($value);
				}
				$url.="&username=".$_OPTIONS['GEONAMES']['username'];
				
				$ctx = stream_context_create(  array( 'http' => array('timeout' => 2) )	); 
/*
http://www.geonames.org/export/JSON-webservices.html
http://www.geonames.org/export/geonames-search.html
		
http://api.geonames.org/searchJSON?username=demo&maxRows=10&q=
http://api.geonames.org/getJSON?geonameId=2768232&username=demo&style=full
http://api.geonames.org/search?q=london&maxRows=10&username=demo&type=json
				
 tbl_search_cache
search_val	result

london:
	[countryName] => United Kingdom
	[adminCode1] => ENG
	[fclName] => city, village,...
	[countryCode] => GB
	[lng] => -0.333333
	[fcodeName] => populated place
	[toponymName] => London Borough of Harrow
	[fcl] => P
	[name] => London Borough of Harrow
	[fcode] => PPL
	[geonameId] => 7535661
	[lat] => 51.566667
	[adminName1] => England
	[population] => 216200
*/
				if($json=@file_get_contents($url,0, $ctx)){
					
					$json=json_decode($json,1);
					
					if($id){
						$json['geonames'][0]=$json;
					}
					if (count($json['geonames']) > 0) {
						foreach($json['geonames'] as $row) {
							$label="{$row['toponymName']}, {$row['name']} ({$row['fcodeName']}: {$row['fclName']}), ({$row['continentCode']}, {$row['countryName']},{$row['countryCode']}, {$row['adminName1']}, {$row['adminCode1']})";
							$id=$row['geonameId'];
							
							if(!isset($fetched[$id])){
						
								$results[] = array(
									'id'	=> $id,
									'label' => "{$label} &lt;{$id}&gt;",
									'value' => $label,
									'color' => ''
								);
							}
						}
					}
					
					// Insert Geonames Search Cache
					$sql = "INSERT INTO {$_CONFIG['DATABASE']['NAME']['name']}. tbl_search_cache (search_group,search_val,result) VALUES ('1',".$db->quote($value).",".$db->quote(json_encode($results)).")  ON DUPLICATE KEY UPDATE result=VALUES(result)" ;	
					$dbst = $db->query($sql);
					
				}
			}
			
			$results=array_merge($results_intern,$results);
		
		}catch (Exception $e){
		echo $e->getMessage();
			error_log($e->getMessage());
			print_r($e);
			exit;
		}
	}

	return $results;
}

/**
 * autocomplete a citation entry field
 * If the searchstring has only one part only the author will be searched
 * If the searchstring consists of two parts the first one is used for author, the second one for year, title and periodical
 *
 * @param string $value text to search for
 * @return array data array ready to send to jQuery-autocomplete via json-encode
 */
public function cname_literature ($value)
{
	$results = array();
	if ($value && strlen($value) > 1) {
		$pieces = explode(" ", $value);
		$autor = $pieces[0];
		if (strlen($pieces[1]) > 2 || (strlen($pieces[1]) == 2 && substr($pieces[1], 1, 1) != '.')) {
			$second = $pieces[1];
		} else {
			$second = '';
		}
		try {
			/* @var $db clsDbAccess */
			$db = clsDbAccess::Connect('INPUT');
			if(!$id=extractID2($value)){
					
				$sql ="SELECT citationID
					   FROM tbl_lit l
						LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID = l.periodicalID
						LEFT JOIN tbl_lit_authors le ON le.autorID = l.editorsID
						LEFT JOIN tbl_lit_authors la ON la.autorID = l.autorID
					   WHERE (la.autor LIKE " . $db->quote ($autor . '%') . "
						   OR le.autor LIKE " . $db->quote ($autor . '%') . ")";
				if ($second) {
					$sql .= " AND (l.jahr LIKE " . $db->quote ($second . '%') . "
								OR l.titel LIKE " . $db->quote ($second . '%') . "
								OR lp.periodical LIKE " . $db->quote ($second . '%') . ")";
				}
				$sql .= " ORDER BY la.autor, jahr, lp.periodical, vol, part, pp";
				/* @var $dbst PDOStatement */
				$dbst = $db->query($sql);
				$rows = $dbst->fetchAll();
				if (count($rows) > 0) {
					$display = clsDisplay::Load();
					foreach ($rows as $row) {
						$results[] = array('id'	=> $row['citationID'],
										   'label' => $display->protolog($row['citationID'], true),
										   'value' => $display->protolog($row['citationID'], true),
										   'color' => '');
					}
				}
			}else{
				$display = clsDisplay::Load();
					
				$label= $display->protolog($id, true);
				$results[] = array(
					'id'	=> $id,
					'label' =>$label,
					'value' => $label,
					'color' => '');
			}
		}
		catch (Exception $e) {
			error_log($e->getMessage());
		}
	}

	return $results;
}

/**
 * Common Names: Language todo: bring it to life
 * @param string $value text to search for
 * @param bool[optional] $noExternals only results for "external=0" (default no)
 * @return array data array ready to send to jQuery-autocomplete via json-encode
 */
public function cname_language ($value){
    global $_CONFIG;
    $this->dbprefix=$_CONFIG['DATABASE']['NAME']['name'].".";
    $d=$this->dbprefix;
	
	$results = array();
	$fetched=array();
			
	if ($value && strlen($value)>1){
		try{
			/* @var $db clsDbAccess */
			$db = clsDbAccess::Connect('INPUT');

			if(!$id=extractID2($value)){
					
			
				// Get TypingCache
				$cacheoption=$this->getCacheOption();
				$sql = "SELECT result FROM {$_CONFIG['DATABASE']['NAME']['name']}. tbl_search_cache WHERE search_group='2' and search_val=" . $db->quote($value)." {$cacheoption} LIMIT 1";	
				
				$dbst = $db->query($sql);
				$row = $dbst->fetch();
				
				// If TypingCache
				if (false && isset($row['result']) && $row['result'] !='') {

					$results=json_decode($row['result'],1);
				
				// Else generate
				}else{

					
					
					// Get Geolang out of database first
					$pebenen=3;
					$cebenen=3;
				
					$f1='';$f2='';
					$j1='';$j2=''; 
					for($i=$pebenen;$i>0;$i--){					
						$f1.="
 p{$i}.name as 'pn{$i}',
 p{$i}.`iso639-6` as 'pi{$i}',
 p{$i}.language_id as 'pii{$i}',
 ";
 						if($i==1){
 							$j1=" LEFT JOIN {$d}tbl_name_languages p1 ON p1.`iso639-6` = l.`parent_iso639-6`\n".$j1;				
 						}else{
 							$j1=" LEFT JOIN {$d}tbl_name_languages p{$i} ON p{$i}.`iso639-6` = p".($i-1).".`parent_iso639-6`\n".$j1;				
 						}
					}
					
					for($i=1;$i<=$cebenen;$i++){					
						$f2.="
 s{$i}.name as 'sn{$i}',
 s{$i}.`iso639-6` as 'si{$i}',
 s{$i}.language_id as 'sii{$i}',
";
 						if($i==1){
 							$j2.=" LEFT JOIN {$d}tbl_name_languages s1 ON s1.`parent_iso639-6` = l.`iso639-6`\n";			
 						}else{
 							$j2.=" LEFT JOIN {$d}tbl_name_languages s{$i} ON s{$i}.`parent_iso639-6` = s".($i-1).".`iso639-6`\n";						
 						}
					}
					
					
					$sql = "
SELECT
{$f1}
 
 l.name as 'n',
 l.`iso639-6` as 'i',
 l.language_id as 'id',
 
{$f2}
 
 IF(l.`iso639-6`='$value',1,0) as 'sort',
 LOCATE('{$value}',l.name) as 'sort2'
FROM
 {$d}tbl_name_languages l
{$j1}
{$j2}
 
WHERE
	l.name LIKE ".$db->quote('%'.$value."%")."
 or l.`iso639-6` LIKE ".$db->quote($value."%")."
ORDER BY
 sort desc,sort2, l.name
 ";	
				
//p($sql);

					/* @var $dbst PDOStatement */
					$dbst = $db->query($sql);
					$rows = $dbst->fetchAll();
					// Build Tree
					$namcache=array();
					$namcache2=array();
					$r=array();
					foreach ($rows as $row) {
						$rp=&$r;
						
						for($i=$pebenen;$i>0;$i--){					
							$rp=&$rp[$row['pi'.$i]];
							$namcache[$row['pi'.$i]]=$row['pn'.$i];
							$namcache2[$row['pi'.$i]]=$row['pii'.$i];
						}
						
						// If no ISO...
						if($row['i']=='')$row['i']=$row['id'];
						
						$rp=&$rp[$row['i']];
						$namcache[$row['i']]=$row['n'];
						$namcache2[$row['i']]=$row['id'];
						
						for($i=1;$i<=$cebenen;$i++){
							if($row['si'.$i]=='')break;	

							$rp=&$rp[$row['si'.$i]];
							$namcache[$row['si'.$i]]=$row['sn'.$i];
							$namcache2[$row['si'.$i]]=$row['sii'.$i];
						}
						$rp=1;
					}
					//p($r);
					// Traverse Tree
					$t=$this->buildtree($r,$namcache,$namcache2);
					
					//p($t,1);
					$x=0;
					if(is_array($t) && count($t)>0){
						foreach($t as $resiso=>$val){
							if(count($val)>0){
								foreach($val as $row){
									$ebene=$row[0];
									$id=$row[1];
									$label=$row[2];
									
									/*if(isset($fetched[$id])){
										continue;
									}*/
									$fetched[$id]=$ebene;
									$pr='';
									if($ebene>0){
										$pr=str_repeat('&nbsp;&nbsp;',$ebene)."&#x21B3;";
									}
									$x++;
									if($x>150)break;
									$results[] = array(
										'id'	=> $id,
										'label' => $pr."".$label,
										'value' => $label,
										'color' => '',
										'sort'	=>''
									);
									
								}
							}
						}
					}
					
					// Insert Geonames Search Cache
					$sql = "INSERT INTO {$_CONFIG['DATABASE']['NAME']['name']}.tbl_search_cache (search_group,search_val,result) VALUES ('2',".$db->quote($value).",".$db->quote(json_encode($results)).")  ON DUPLICATE KEY UPDATE result=VALUES(result)" ;	
					$dbst = $db->query($sql);
				}
			}else{
				$pebenen=3;
				
				$f1='';$j1='';
				for($i=$pebenen;$i>0;$i--){					
					$f1.="
 p{$i}.name as 'pn{$i}',
 p{$i}.`iso639-6` as 'pi{$i}',
 p{$i}.language_id as 'pii{$i}',
 ";
 						if($i==1){
 							$j1=" LEFT JOIN {$d}tbl_name_languages p1 ON p1.`iso639-6` = l.`parent_iso639-6`\n".$j1;				
 						}else{
 							$j1=" LEFT JOIN {$d}tbl_name_languages p{$i} ON p{$i}.`iso639-6` = p".($i-1).".`parent_iso639-6`\n".$j1;				
 						}
					}
					
				$sql = "
SELECT
{$f1}
 l.language_id,
 l.name,
 l.`iso639-6`,
 l.`parent_iso639-6`

FROM
 {$d}tbl_name_languages l
{$j1}
WHERE
	l.language_id='$id'
 ";
// p($sql,1);
				/* @var $dbst PDOStatement */
				$dbst = $db->query($sql);
				$row = $dbst->fetch();
					
				$res=array();
				if($row){
					$label="";
					for($i=1;$i<=$pebenen;$i++){
						if($row['pn'.$i]=='')continue;
						if($i==1){
							$label.="{$row['pn'.$i]} ({$row['pi'.$i]})";
						}else{
							$label.=", {$row['pn'.$i]} ({$row['pi'.$i]})";
						}
					}
					$label="{$row['name']} ({$row['iso639-6']}) [".$label."]";
					
					$id=$row['language_id'];
							
					$results[] = array(
						'id'	=> $id,
						'label' => $label,
						'value' => $label,
						'color' => ''
					);
				}
			}
		}catch (Exception $e){
			error_log($e->getMessage());
			print_r($e);
			exit;
		}
	}

	return $results;
}

function buildtree(&$r,$nc=array(),$nc2=array()){

	$this->x=0;
	$res='';

	$this->buildtree1($r,0,'',$res,$nc,0,$nc2);

	return $res;
}
var $x=0;
var $usedisos=array();

function buildtree1(&$el,$ebene,$keys,&$res,&$nc,$akey,&$nc2){

	$this->x++;
	if($this->x>3000){
		echo "Errore";
		exit;
	}
	
	if(!is_array($el) || count($el)==0){
		return;
	}
	
	if($ebene<3){
		
		$key=key($el);
		$ebene=$ebene+1;
		$keyso=$keys;
		foreach($el as $key=>$tree){
			if($key!=''){
				if(intval($ebene)==1){
					$keys="".$nc[$key]." ({$key})";
				
				}else{
					$keys="".$nc[$key]." ({$key}), ".$keyso;
				}
			}
			$this->buildtree1($tree,$ebene,$keys,$res,$nc,$key,$nc2);
		}
	
	}else if($ebene==3){

		$ebene=$ebene+1;
		$x=0;
		foreach($el as $key=>$tree){
			if(!isset($this->usedisos[$key])){
				$res[$key][]=array(0,$nc2[$key],$nc[$key]." ({$key}) [".$keys."]");
				$this->buildtree1($tree,$ebene,$keys,$res,$nc,$key,$nc2);
			}
		}

	}else if($ebene<3+3+1){

		$key=key($el);
		$ebene=$ebene+1;
		$this->usedisos[$key]=1;	
		foreach($el as $key=>$tree){
			$res[$akey][]=array($ebene-3-1,$nc2[$key],$nc[$key]." ({$key})");
			$this->buildtree1($tree,$ebene,$keys,$res,$nc,$akey,$nc2);
		}
	}
}

/*
function buildtree1(&$el,$ebene,$keys,&$res,&$nc,$akey,&$nc2){

	$this->x++;
	if($this->x>300){
		echo "Error";
		exit;
	}
	
	if($ebene<3){
		
		if($ebene==1){
			$keys=", ".$nc[$keys]." ({$keys})";
		}else{
			$el=current($el);
		}
		
		$key=key($el);
		$keys.=", ".$nc[$key]." ({$key})";

		$this->buildtree1($el,$ebene+1,$keys,$res,$nc,0,$nc2);
		
	}else if($ebene==3){
	
		$el=current($el);
		$key=key($el);
		
		$res[$key]['c'][]=array($nc2[$key],"".$nc[$key]."({$key}) [".$keys."]");
		
		$el=current($el);
		$this->buildtree1($el,$ebene+1,$keys,$res,$nc,$key,$nc2);
		
	}else if($ebene<2+3+3){
		$sp=$ebene-3;

		if(is_array($el) && count($el)>0){

			foreach($el as $key=>$tree){
				
				$keyn=str_repeat('.',$sp)."".$nc[$key]." ({$key})";

				$res[$akey]['c'][]=array($nc2[$key],$keyn);
				$this->buildtree1($tree,$ebene+1,$keys,$res,$nc,$akey,$nc2);
			}
		}
	}
}*/
/*
function buildtree2(&$el,$ebene,$keys,&$res,$nc){

	$this->x++;
	if($this->x>300){
		echo "Error";
		exit;
	}
	
	if($ebene<3){
		if($ebene>1){
			$el=current($el);
		}

		$keys[]=key($el);
		$this->buildtree2($el,$ebene+1,$keys,$res);
	}else{
	
		$el=current($el);
		$key=key($el);
	
		$res[$key]['p']=$keys;
		$res[$key]['c']=current($el);
	}
}*/

/**
 * Common Names: Period
 * @param string $value text to search for
 * @param bool[optional] $noExternals only results for "external=0" (default no)
 * @return array data array ready to send to jQuery-autocomplete via json-encode
 */
public function cname_service ($value){
	$results = array();
	if ($value && strlen($value)>1){
		try{
			/* @var $db clsDbAccess */
			$db = clsDbAccess::Connect('INPUT');
			
			$where='';
			if($id=extractID2($value)){
				$where="serviceID ='$id'";
			}else{
				$where="name LIKE " . $db->quote ($value . '%').
						"or url_head LIKE " . $db->quote ($value . '%');
			}
			
			$sql = "
SELECT
 serviceID,
 name,
 url_head
FROM
 tbl_nom_service
WHERE
 {$where}
";
			/* @var $dbst PDOStatement */
			$dbst = $db->query($sql);
			$rows = $dbst->fetchAll();
			
			if (count($rows) > 0) {
				foreach ($rows as $row) {
					
					$label="{$row['name']} ({$row['serviceID']}, {$row['url_head']})";
					$id=$row['serviceID'];
					
					$results[] = array(
						'id'	=> $id,
						'label' => $label,
						'value' => $label,
						'color' => ''
					);
				}
			}
		}catch (Exception $e){
			error_log($e->getMessage());
		}
	}

	return $results;
}

/**
 * Common Names: Period
 * @param string $value text to search for
 * @param bool[optional] $noExternals only results for "external=0" (default no)
 * @return array data array ready to send to jQuery-autocomplete via json-encode
 */
public function cname_period ($value){
    global $_CONFIG;
    
	$results = array();
	if ($value && strlen($value)>1){
		try{
			/* @var $db clsDbAccess */
			$db = clsDbAccess::Connect('INPUT');
			
			$where='';
			if($id=extractID2($value)){
				$where="period_id ='$id'";
			}else{
				$where="period LIKE " . $db->quote ($value . '%');
			}
			
			$sql = "
SELECT
 period_id,
 period
FROM
 {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_periods
WHERE
 {$where}
";
			
			
			/* @var $dbst PDOStatement */
			$dbst = $db->query($sql);
			$rows = $dbst->fetchAll();
			
			if (count($rows) > 0) {
				foreach ($rows as $row) {
					
					$label="{$row['period']}";
					$id=$row['period_id'];
					
					$results[] = array(
						'id'	=> $id,
						'label' => "{$label} &lt;{$id}&gt;",
						'value' => $label,
						'color' => ''
					);
				}
			}
		}catch (Exception $e){
			error_log($e->getMessage());
		}
	}

	return $results;
}


/**
 * Common Names: Period
 * @param string $value text to search for
 * @param bool[optional] $noExternals only results for "external=0" (default no)
 * @return array data array ready to send to jQuery-autocomplete via json-encode
 */
public function cname_geospecification ($value){
    global $_CONFIG;
    
	$results = array();
	if ($value && strlen($value)>1){
		try{
			/* @var $db clsDbAccess */
			$db = clsDbAccess::Connect('INPUT');
			
			$sql = "
SELECT
 DISTINCT geospecification
FROM
 {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_applies_to
WHERE
 geospecification like " . $db->quote ($value . '%')."
";
			
			
			/* @var $dbst PDOStatement */
			$dbst = $db->query($sql);
			$rows = $dbst->fetchAll();
			
			if (count($rows) > 0) {
				foreach ($rows as $row) {
					
					$label=$row['geospecification'];
					$id=$row['geospecification'];
					
					$results[] = array(
						'id'	=> $id,
						'label' => $label,
						'value' => $label,
						'color' => ''
					);
				}
			}
		}catch (Exception $e){
			error_log($e->getMessage());
		}
	}

	return $results;
}




/***********************\
|					   |
|  protected functions  |
|					   |
\***********************/

/*********************\
|					 |
|  private functions  |
|					 |
\*********************/

private function __clone () {}


}