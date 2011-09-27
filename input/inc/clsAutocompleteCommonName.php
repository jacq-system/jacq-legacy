<?php

function p($val){

echo "<pre>".print_r($val,1)."</pre>";

}
	//	$dbst=0;return array(array('id'=>'1','label'=>($dbst?'Y':'N').$sql,'value'=>($dbst?'Y':'N').$sql));
				
		
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

private static $instance=null;

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
		self::$instance=new clsAutocompleteCommonName();
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
	
function getCacheOption(){
	global $_OPTIONS;
	
	if( in_array($_OPTIONS['TYPINGCACHE']['SETTING']['type'],array('MICROSECOND','SECOND','MINUTE','HOUR','DAY','WEEK','MONTH','QUARTER','YEAR')) 
		&& intval($_OPTIONS['TYPINGCACHE']['SETTING']['val'])!==0
	){
		return "and timestamp>TIMESTAMPADD({$_OPTIONS['TYPINGCACHE']['SETTING']['type']},-{$_OPTIONS['TYPINGCACHE']['SETTING']['val']},NOW())";
	}
	return '';
}


/** W
 * Common Names: Common Name
 * @param string $value text to search for
 * @param bool[optional] $noExternals only results for "external=0" (default no)
 * @return array data array ready to send to jQuery-autocomplete via json-encode
 */
public function cname_commonname ($value){
    global $_CONFIG;
	
	$results=array();
	try{
	
		$db=clsDbAccess::Connect('INPUT');
		$sql="
SELECT
 com.common_name,
 com.common_id,
 trans.name as 'tranlit'
FROM
 {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_commons  com
 LEFT JOIN {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_names nam on nam.name_id=com.common_id
 LEFT JOIN {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_transliterations trans ON trans.transliteration_id=nam.transliteration_id
WHERE
";	
		if(isset($value['id'])){
			$sql.=" com.common_id ='{$value['id']}'";
		}else if(isset($value['exact'])){
			$sql.=" com.common_name='{$value['exact']}' LIMIT 2";	
		}else{
			$sql.=" com.common_name LIKE '{$value['search']}%' LIMIT 100";
		}
		
		$dbst=$db->query($sql);
		$rows=$dbst->fetchAll();
		
		if (count($rows) > 0) {
			foreach ($rows as $row) {
				$id=$row['common_id'];
				
				$value=$row['common_name'];
				$label="{$value} &nbsp;&nbsp;&nbsp;(<i>{$row['tranlit']}</i>)";
				if(isset($search['params']) && isset($search['params']['showtranslit'])){
					$value="{$value}     ({$row['tranlit']})";
				}
				$results[]=array(
					'id'	=> $id,
					'label' => $label,
					'value' => $value,
					'color' => ''
				);
			}
		}
	}catch (Exception $e){
		error_log($e->getMessage());
	}
	
	return $results;
}

public function cname_commonname_translit ($value){
    global $_CONFIG;
	
	$results=array();
	try{
	
		$db=clsDbAccess::Connect('INPUT');
		$sql="
SELECT
 com.common_name,
 com.common_id,
 trans.name as 'tranlit'
FROM
 {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_commons  com
 LEFT JOIN {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_names nam on nam.name_id=com.common_id
 LEFT JOIN {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_transliterations trans ON trans.transliteration_id=nam.transliteration_id
WHERE
";	
		if(isset($value['id'])){
			$sql.=" com.common_id ='{$value['id']}' or trans.transliteration_id ='{$value['id']}'";
		}else if(isset($value['exact'])){
			$sql.=" com.common_name='{$value['exact']}' or trans.name='{$value['exact']}' LIMIT 2";	
		}else{
			$sql.=" com.common_name LIKE '{$value['search']}%' or trans.name LIKE '{$value['search']}'  LIMIT 100";
		}
		
		$dbst=$db->query($sql);
		$rows=$dbst->fetchAll();
		
		if (count($rows) > 0) {
			foreach ($rows as $row) {
				$id=$row['common_id'];
				
				$value=$row['common_name'];
				$label="{$value} &nbsp;&nbsp;&nbsp;(<i>{$row['tranlit']}</i>)";
				//$value="{$value}     ({$row['tranlit']})";
				
				$results[]=array(
					'id'	=> $id,
					'label' => $label,
					'value' => $value,
					'color' => ''
				);
			}
		}
	}catch (Exception $e){
		error_log($e->getMessage());
	}
	
	return $results;
}


/** W
 * Common Names: Common Name
 * @param string $value text to search for
 * @param bool[optional] $noExternals only results for "external=0" (default no)
 * @return array data array ready to send to jQuery-autocomplete via json-encode
 */
public function cname_name($value){
    global $_CONFIG;
	
	$results=array();
	try{
	
		$db=clsDbAccess::Connect('INPUT');
		$sql="
SELECT
 com.common_name,
 com.common_id,
 trans.name as 'tranlit'
FROM
 {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_names nam 
 LEFT JOIN {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_commons com on com.common_id=nam.name_id
 LEFT JOIN {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_transliterations trans ON trans.transliteration_id=nam.transliteration_id
WHERE
";	
		if(isset($value['id'])){
			$sql.=" nam.name_id ='{$value['id']}'";
		}else if(isset($value['exact'])){
			$sql.=" com.common_name='{$value['exact']}' or trans.name='{$value['exact']}' LIMIT 2";	
		}else{
			$sql.=" com.common_name LIKE '{$value['search']}%' or trans.name LIKE '{$value['search']}%' ORDER BY  com.common_name,trans.name LIMIT 100";
		}
		
		$dbst=$db->query($sql);
		$rows=$dbst->fetchAll();
		
		if (count($rows) > 0) {
			foreach ($rows as $row) {
				$id=$row['common_id'];
				$label=$row['common_name'];
				
				$results[]=array(
					'id'	=> $id,
					'label' => "{$label} &nbsp;&nbsp;&nbsp;(<i>{$row['tranlit']}</i>) <{$id}>",
					'value' => "{$label}    ({$row['tranlit']}) <{$id}>",
					'color' => ''
				);
			}
		}
	}catch (Exception $e){
		error_log($e->getMessage());
	}
	
	return $results;
}


/** W
 * Common Names: Common Name
 * @param string $value text to search for
 * @param bool[optional] $noExternals only results for "external=0" (default no)
 * @return array data array ready to send to jQuery-autocomplete via json-encode
 */
public function cname_transliteration($value){
    global $_CONFIG;
	
	$results=array();
	try{
		$db=clsDbAccess::Connect('INPUT');
		
					
		$sql="SELECT transliteration_id, name FROM {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_transliterations WHERE ";
		
		if(isset($value['id'])){
			
			if(substr($value['id'],0,1)=='c'){
				$id=substr($value['id'],1);
				$sql="
SELECT  
 trans.transliteration_id,
 trans.name
FROM
 {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_commons  com
 LEFT JOIN {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_names nam on nam.name_id=com.common_id
 LEFT JOIN {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_transliterations trans ON trans.transliteration_id=nam.transliteration_id
WHERE
 com.common_id ='{$id}' LIMIT 1";
			
			}else{
				$sql.=" transliteration_id ='{$value['id']}' LIMIT 2";
			}
		}else if(isset($value['exact'])){
			$sql.="  name ='{$value['exact']}' LIMIT 2";
		}else{
			$sql.=" name LIKE '{$value['search']}%' LIMIT 100";
		}
			
		
		$dbst=$db->query($sql);
		$rows=$dbst->fetchAll();
		
		if (count($rows) > 0) {
			foreach ($rows as $row) {
				$id=$row['transliteration_id'];
				$label=$row['name'];
				
				$results[]=array(
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

	return $results;
}

/** W
 * Common Names: Common Name
 * @param string $value text to search for
 * @param bool[optional] $noExternals only results for "external=0" (default no)
 * @return array data array ready to send to jQuery-autocomplete via json-encode
 */
public function cname_tribe($value){
    global $_CONFIG;
	
	$results=array();
	try{
		$db=clsDbAccess::Connect('INPUT');
		
				
		$sql="SELECT tribe_id, tribe_name FROM {$_CONFIG['DATABASE']['NAME']['name']}. tbl_name_tribes WHERE ";
		
		if(isset($value['id'])){
			$sql.="  tribe_id='{$value['id']}'";
		}else if(isset($value['exact'])){
			$sql.=" tribe_name='{$value['exact']}' LIMIT 100";
		}else{
			$sql.=" tribe_name LIKE '{$value['search']}%' LIMIT 100";
		}

		$dbst=$db->query($sql);
		$rows=$dbst->fetchAll();
		
		if (count($rows) > 0) {
			foreach ($rows as $row) {
				$id=$row['tribe_id'];
				$label=$row['tribe_name'];
				
				$results[]=array(
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

	return $results;
}

/** exact todo...
 * Common Names: Geoname
 * @param string $value text to search for
 * @param bool[optional] $noExternals only results for "external=0" (default no)
 * @return array data array ready to send to jQuery-autocomplete via json-encode
 */
public function cname_geoname ($value){
	global $_OPTIONS, $_CONFIG;
	$results=array();
	$results_intern=array();
	$fetched=array();

	try{
		$db=clsDbAccess::Connect('INPUT');
			
		$sql="SELECT geonameId,name FROM {$_CONFIG['DATABASE']['NAME']['name']}.tbl_geonames_cache WHERE ";
			
		if(isset($value['id'])){
			$sql.=" geonameId ='{$value['id']}'";
		}else if(isset($value['exact'])){
			$sql.=" name='{$value['exact']}' LIMIT 2";
		}else{
			$sql.="name LIKE '{$value['search']}' LIMIT 100";
		}
			
		$dbst=$db->query($sql);
		
		$rows=$dbst->fetchAll();
		
		if (count($rows) > 0) {
			foreach($rows as $row) {
				$label=$row['name'];
				$id=$row['geonameId'];
					
				if(!isset($fetched[$id])){
						
					$results_intern[]=array(
						'id'	=> $id,
						'label' => "{$label} &lt;{$id}&gt;",
						'value' => $label,
						'color' => ''
					);
				}
			}
		
		}
		
		if(isset($value['id']) && count( $results_intern)>0){
			return $results_intern;
		}
		
		$v=isset($value['id'])?$value['id']:(isset($value['exact'])?$value['exact']:$value['search']);
		// Get TypeCache
		$cacheoption=$this->getCacheOption();
		$sql="SELECT result FROM {$_CONFIG['DATABASE']['NAME']['name']}. tbl_search_cache WHERE search_group='1' and search_val='{$v}' {$cacheoption} LIMIT 1";	
		
		$dbst=$db->query($sql);
		$row=$dbst->fetch();
		
		// If TypeCache
		if (isset($row['result']) && $row['result'] !='') {

			$results=json_decode($row['result'],1);
	
		// Else retrieve data from geonames.org
		}else{
			$url='http://api.geonames.org';
			
			if(isset($value['id'])){
				$url.="/getJSON?";
				$url.="style=full";
				$url.="&geonameId=".$value['id'];
			}else if(isset($value['exact'])){
				//todo...
				$url.="/getJSON?";
				$url.="style=full";
				$url.="&geonameId=".$value['exact'];
			}else{		
				$url.="/searchJSON?";
				$url.="maxRows=10";
				$url.="&q=".urlencode($value['search']);
			}
			$url.="&username=".$_OPTIONS['GEONAMES']['username'];
			
			$ctx=stream_context_create(  array( 'http' => array('timeout' => 2) )	); 
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
					
				if(isset($value['id'])){
					$json['geonames'][0]=$json;
				}
				if(isset($json['geonames']) && count($json['geonames']) > 0) {
					foreach($json['geonames'] as $row) {
						$zz=error_reporting(E_ALL^E_NOTICE);
						$label="{$row['toponymName']}, {$row['name']} ({$row['fcodeName']}: {$row['fclName']}), ({$row['continentCode']}, {$row['countryName']},{$row['countryCode']}, {$row['adminName1']}, {$row['adminCode1']})";
						error_reporting($zz);
						$id=$row['geonameId'];
						
						if(!isset($fetched[$id])){
					
						$results[]=array(
							'id'	=> $id,
							'label' => "{$label} &lt;{$id}&gt;",
							'value' => $label,
							'color' => ''
						);
						}
					}
				}
				
				// Insert Geonames Search Cache
				$sql="INSERT INTO {$_CONFIG['DATABASE']['NAME']['name']}. tbl_search_cache (search_group,search_val,result) VALUES ('1','{$v}',".$db->quote(json_encode($results)).")  ON DUPLICATE KEY UPDATE result=VALUES(result)" ;	
				$dbst=$db->query($sql);
				
			}
		}
		
		$results=array_merge($results_intern,$results);
		
	}catch (Exception $e){
		error_log($e->getMessage());
		exit;
	}
	return $results;
}


var $nc_id=array();
var $nc_name=array();
var $x=0;

/** W
 * Common Names: Language todo: bring it to life
 * @param string $value text to search for
 * @param bool[optional] $noExternals only results for "external=0" (default no)
 * @return array data array ready to send to jQuery-autocomplete via json-encode
 */
public function cname_language ($value){
    global $_CONFIG;
    $this->dbprefix=$_CONFIG['DATABASE']['NAME']['name'].".";
    $d=$this->dbprefix;
	
	$results=array();
	$fetched=array();
			

	try{
		$db=clsDbAccess::Connect('INPUT');
		
		if(isset($value['id']) || isset($value['exact']) ){
			$pebenen=3;
				
				$f1='';$j1='';
				for($i=$pebenen;$i>0;$i--){					
					$f1.="
 p{$i}.name as 'pn{$i}',
 p{$i}.`iso639-6` as 'pi{$i}',
 p{$i}.language_id as 'pii{$i}',
 ";
 					if($i==1){
 						$j1=" LEFT JOIN {$d}tbl_name_languages p1 ON p1.`iso639-6`=l.`parent_iso639-6`\n".$j1;				
 					}else{
 						$j1=" LEFT JOIN {$d}tbl_name_languages p{$i} ON p{$i}.`iso639-6`=p".($i-1).".`parent_iso639-6`\n".$j1;				
 					}
				}
					
				$sql="
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

";
			if(isset($value['exact'])){
				$sql .=" l.name='{$value['exact']}' or l.`iso639-6`='{$value['exact']}' ";
			}else{
				$sql .=" l.language_id='{$value['id']}' ";
			}
			$sql.=" LIMIT 100";
			
			$dbst=$db->query($sql);
			$row=$dbst->fetch();
			$res=array();
			if(isset($row['name']) && $row['name']!=''){
				$label="";
				for($i=1;$i<=$pebenen;$i++){
					if($row['pn'.$i]=='')continue;
					if($i==1){
					$label.="{$row['pn'.$i]} ({$row['pi'.$i]})";
					}else{
					$label.=", {$row['pn'.$i]} ({$row['pi'.$i]})";
					}
				}
				if($label!=""){
					$label=" [".$label."]";
				}
				if(isset($row['iso639-6'])){
					$label="({$row['iso639-6']}) $label";
				}
				$label="{$row['name']}";
				
				
				
				$id=$row['language_id'];
				
				$results[]=array(
					'id'	=> $id,
					'label' => $label,
					'value' => $label,
					'color' => ''
				);
			}
		}else{
				
			// Get TypingCache
			$v=$value['search'];
		
			$cacheoption=$this->getCacheOption();
			$sql="SELECT result FROM {$_CONFIG['DATABASE']['NAME']['name']}. tbl_search_cache WHERE search_group='2' and search_val='{$v}' {$cacheoption} LIMIT 1";	
			
			$db=clsDbAccess::Connect('INPUT');
			$dbst=$db->query($sql);
			$row=$dbst->fetch();
				
			// If TypingCache
			if(false && isset($row['result']) && $row['result'] !='') {

				$results=json_decode($row['result'],1);
			
			// Else generate
			}else{
				// Get Geolang out of database first
				$pebenen=3;
				$cebenen=3;
					
				$cebenen++;
				$f1='';$f2='';
				$j1='';$j2=''; 
				for($i=$pebenen;$i>0;$i--){					
					$f1.="
 p{$i}.name as 'pn{$i}',
 p{$i}.`iso639-6` as 'pi{$i}',
 p{$i}.language_id as 'pii{$i}',
 ";
					if($i==1){
 						$j1=" LEFT JOIN {$d}tbl_name_languages p1 ON p1.`iso639-6`=l.`parent_iso639-6`\n".$j1;				
 					}else{
						$j1=" LEFT JOIN {$d}tbl_name_languages p{$i} ON p{$i}.`iso639-6`=p".($i-1).".`parent_iso639-6`\n".$j1;				
 					}
				}
					
				for($i=1;$i<=$cebenen;$i++){					
					$f2.="
 s{$i}.name as 'sn{$i}',
 s{$i}.`iso639-6` as 'si{$i}',
 s{$i}.language_id as 'sii{$i}',
";
 					if($i==1){
						$j2.=" LEFT JOIN {$d}tbl_name_languages s1 ON s1.`parent_iso639-6`=l.`iso639-6`\n";			
 					}else{
 						$j2.=" LEFT JOIN {$d}tbl_name_languages s{$i} ON s{$i}.`parent_iso639-6`=s".($i-1).".`iso639-6`\n";						
 					}
				}
				
				$sql="
SELECT
{$f1}
 
 l.name as 'n',
 l.`iso639-6` as 'i',
 l.language_id as 'id',
 
{$f2}
 
 IF(l.`iso639-6`='$value',1,0) as 'sort',
 LOCATE('{$value['search']}',l.name) as 'sort2'
FROM
 {$d}tbl_name_languages l
{$j1}
{$j2}
 
WHERE
	l.name LIKE '%{$value['search']}%'
 or l.`iso639-6` LIKE '%{$value['search']}%'
ORDER BY
 sort desc,sort2, l.name
 LIMIT 100
 ";	
				
//p($sql);
				

				
				$dbst=$db->query($sql);
				$rows=$dbst->fetchAll();
				// Build Tree
				
				$r=array();
				foreach ($rows as $row) {
					$rp=&$r;
						
					for($i=$pebenen;$i>0;$i--){					
					$rp=&$rp[$row['pi'.$i]];
					
					$this->nc_name[$row['pi'.$i]]=$row['pn'.$i];
					$this->nc_id[$row['pi'.$i]]=$row['pii'.$i];
				}
					
				// If no ISO...
				if($row['i']=='')$row['i']=$row['id'];
						
				$rp=&$rp[$row['i']];

				$this->nc_name[$row['i']]=$row['n'];
				$this->nc_id[$row['i']]=$row['id'];
						
				for($i=1;$i<=$cebenen;$i++){
					if($row['si'.$i]=='')break;	

					$rp=&$rp[$row['si'.$i]];

					$this->nc_name[$row['si'.$i]]=$row['sn'.$i];
					$this->nc_id[$row['si'.$i]]=$row['sii'.$i];
					}
					$rp=1;
				}
				//	p($r);
				// Traverse Tree
				$t=$this->buildtree($r);
				
				//p($t,1);
				//	exit;
				$x=0;
				if(is_array($t) && count($t)>0){
					foreach($t as $resiso=>$val){
					if(count($val)>0){
						foreach($val as $row){
							
							$id=$row[0];
							$label=$row[1];
							$value=$row[2];

							$x++;
							if($x>100)break;
								$results[]=array(
									'id'	=> $id,
									'label' =>$label,
									'value' => $value,
									'color' => '',
									'sort'	=>''
								);
								
							}
						}
					}
				}
					
				// Todo: fetch all manual inserted languages...
				
				// Insert Geonames Search Cache
				$sql="INSERT INTO {$_CONFIG['DATABASE']['NAME']['name']}.tbl_search_cache (search_group,search_val,result) VALUES ('2','{$v}',".$db->quote(json_encode($results)).")  ON DUPLICATE KEY UPDATE result=VALUES(result)" ;	
				$dbst=$db->query($sql);
			}
		}
				
	}catch (Exception $e){
		error_log($e->getMessage());
		print_r($e);
		exit;
	}

	return $results;
}




function buildtree(&$r){

	$this->x=0;

	$res=array();

	$this->buildtree1($res,$r,-3,'',0,'');
	if($this->x>3000){
		echo "Too Many Suggestions";
	}
		
	return $res;
}

var $usedisos=array();
function buildtree1(&$res,&$el,$childebene,$keys,$akey,$t3){
	
	// to much recursion...
	$this->x++;
	if($this->x>3000){
		return;
	}
	
	// no more childs...
	if(!is_array($el) || count($el)==0){
		return;
	}
	
	$keyso='';
	if($childebene<0){
		$keyso=$keys;
	}
	$t5="";
	
	// get last key.
	$tt=$el;
	
	end($tt);
	$lastkey=key($tt);

	foreach($el as $key=>$tree){
		
		// <0 paarent, 0 element, >0 child
		if($childebene<0){
			if($key!=''){
				$keys=$this->nc_name[$key]." ({$key})";
				if($childebene>-3){
					$keys.=", ".$keyso;
				}
			}

		}else{
			
			if($childebene==0){
				$akey=$key;
			}
			
			$lastkey1=($key==$lastkey);
			
			
			$usedbefore=isset($this->usedisos[$key])?true:false;
			
			if($usedbefore && $childebene==0){
				continue;
			}
			
			$t6=($childebene==0)?' ['.$keys.']':'';
			$t7=(is_array($tree) && $usedbefore)?' (*) opened before':(($usedbefore)?' shown before':'');
			$t8=( is_array($tree) &&  $childebene==3 )?' (*)':''; //childebenene
			
			
			
			$res[$akey][]=array($this->nc_id[$key], $t3."".(($lastkey1)?'+':'|')."--".$this->nc_name[$key]." ({$key})".$t6.$t8.$t7, $this->nc_name[$key]." ({$key})");
			
			if($childebene==3 || $usedbefore ){ //childebenen
				continue;
			}
			
			$this->usedisos[$key]=1;

			$t5=$t3.(($lastkey1)?'':'|')."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
			
			$key=$akey;
		}

		$this->buildtree1($res,$tree,$childebene+1,$keys,$key,$t5);


	}
	return;
}


/** W
 * Common Names: Period
 * @param string $value text to search for
 * @param bool[optional] $noExternals only results for "external=0" (default no)
 * @return array data array ready to send to jQuery-autocomplete via json-encode
 */
public function cname_service ($value){
	$results=array();
	try{
		$db=clsDbAccess::Connect('INPUT');
			
		$sql="
SELECT
 serviceID,
 name,
 url_head
FROM
 tbl_nom_service
WHERE
";
		
		if(isset($value['id'])){
			$sql.=" serviceID='{$value['id']}' ";
		}else if(isset($value['exact'])){
			$sql.="name='{$value['exact']}' or url_head ='{$value['exact']}'  LIMIT 2";
		}else{
			$sql.=" name LIKE '{$value['search']}%' or url_head LIKE '{$value['search']}%' LIMIT 100";
		}
		
		$dbst=$db->query($sql);
		$rows=$dbst->fetchAll();
			
		if (count($rows) > 0) {
			foreach ($rows as $row) {
				
				$label="{$row['name']} ({$row['serviceID']}, {$row['url_head']})";
				$id=$row['serviceID'];
				
				$results[]=array(
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

	return $results;
}

/** W
 * Common Names: Period
 * @param string $value text to search for
 * @param bool[optional] $noExternals only results for "external=0" (default no)
 * @return array data array ready to send to jQuery-autocomplete via json-encode
 */
public function cname_period ($value){
    global $_CONFIG;
    
	$results=array();
	try{
		$db=clsDbAccess::Connect('INPUT');
			
		$sql="
SELECT
 period_id,
 period
FROM
 {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_periods
WHERE
";
		if(isset($value['id'])){
			$sql.=" period_id='{$value['id']}' LIMIT 2";
		}else if(isset($value['exact'])){
			$sql.=" period ='{$value['exact']}'";
		}else{
			$sql.=" period LIKE '{$value['search']}%' LIMIT 100";
		}
		
		$dbst=$db->query($sql);
		$rows=$dbst->fetchAll();	
		if (count($rows) > 0) {
			foreach ($rows as $row) {
				
				$label="{$row['period']}";
				$id=$row['period_id'];
				
				$results[]=array(
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

	return $results;
}


/** W
 * Common Names: Period
 * @param string $value text to search for
 * @param bool[optional] $noExternals only results for "external=0" (default no)
 * @return array data array ready to send to jQuery-autocomplete via json-encode
 */
public function cname_geospecification ($value){
    global $_CONFIG;
    
	$results=array();
	try{
		$db=clsDbAccess::Connect('INPUT');
				
		$sql="
SELECT
 DISTINCT geospecification
FROM
 {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_applies_to
WHERE
";
		if(isset($value['id'])){
			$sql.=" geospecification = '{$value['id']}' LIMIT 2";
		}else if(isset($value['exact'])){
			$sql.=" geospecification = '{$value['exact']}' LIMIT 2";
		}else{
			$sql.=" geospecification like '{$value['search']}%' LIMIT 100";
		}
			
		
		$dbst=$db->query($sql);
		$rows=$dbst->fetchAll();
		
		if (count($rows) > 0) {
			foreach ($rows as $row) {
				
				$label=$row['geospecification'];
				$id=$row['geospecification'];
				
				$results[]=array(
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

	return $results;
}





/***********************\
|						|
|  protected functions  |
|						|
\***********************/

/*********************\
|					 |
|  private functions  |
|					 |
\*********************/

private function __clone () {}


}