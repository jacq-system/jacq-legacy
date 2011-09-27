<?PHP
session_start();
require_once('../inc/connect.php');
require_once('../inc/cssf.php');
require_once('../inc/log_functions.php');
require_once('mapLines.php');

no_magic();

foreach($_GET as $k=>$v){
	$params[$k]=$v;
}

class MapLines_editLit extends MapLines{
	var $pagination=5;
	var $dbprefix;

	function __construct(){
		global $_CONFIG;
		$this->dbprefix=$_CONFIG['DATABASE']['NAME']['name'].'.';
	}

	function RemoveMapLine($params){

		logTbl_name_names_equals($params['leftID'],$params['rightID'],2);
		$sql = "DELETE FROM {$this->dbprefix}tbl_name_names_equals WHERE tbl_name_names_name_id='{$params['leftID']}' and tbl_name_names_name_id1='{$params['rightID']}' LIMIT 1";
		$res = db_query($sql);
		$res=array('success'=>$res);
		return $res;
	}
	
	function LoadMapLines($params){
		
		$cid=$params['cid'];
		$page=$params['pageIndex'];
		$pbegin=$page*$this->pagination;
		
		$countAllSearch="
 (    eq.tbl_name_names_name_id='{$cid}'
  OR eq.tbl_name_names_name_id1='{$cid}'
)";
		
		//mdld => Service! auslagern.
		if(isset($params['mdldSearch']) && strlen($params['mdldSearch'])>0){
			global $_OPTIONS;
			
			// MDLD Search for commonnames.
			$this->limit=4;
			$this->block_limit=2;
			
			$uninomial=$params['mdldSearch'];
			$lenUninomial=mb_strlen(trim($uninomial));
			$lenlim=min($lenUninomial/2,$this->limit);
			$uninomial=strtolower($uninomial);
		
			$sqlMDLD="
SELECT
 com.common_id as 'i'
FROM
 {$this->dbprefix}tbl_name_names nam
 LEFT JOIN {$this->dbprefix}tbl_name_commons com ON com.common_id = nam.name_id
 LEFT JOIN {$this->dbprefix} tbl_name_transliterations translit ON translit.transliteration_id = nam.transliteration_id
WHERE
(
     mdld('{$uninomial}',com.common_name, {$this->block_limit}, {$this->limit}) <  LEAST(CHAR_LENGTH(com.common_name)/2,{$lenlim})
  OR mdld('{$uninomial}',translit.name, {$this->block_limit}, {$this->limit}) <  LEAST(CHAR_LENGTH(translit.name)/2,{$lenlim})
)
LIMIT 1000
";
			$service = clsInternMDLDService::Load($_OPTIONS['internMDLDService']['url'],$_OPTIONS['internMDLDService']['password']);
			
			try {
				$res1 = $service->getSQLResults($sqlMDLD);
			}catch (Exception $e) {
				echo "Fehler " . nl2br($e);
			}
			$lookup='';
			if(isset($res1['error']) ) {
				echo $res1['error'];
			}else{
				if(count($res1)>0){
					foreach($res1 as $row){
						$lookup.=",'{$row['i']}'";
					}
				}
				$lookup=substr($lookup,1);
			}
			$whereSearch="
   ( com0.common_id IN ($lookup) AND eq.tbl_name_names_name_id1='{$cid}')
OR ( com1.common_id IN ($lookup) AND eq.tbl_name_names_name_id='{$cid}')
";
		}else if(isset($params['mysqlSearch']) && strlen($params['mysqlSearch'])>0){
				$search="'".mysql_escape_string("%".$params['mysqlSearch']) . "%'";
				
				$whereSearch="
(
     ( com0.common_name LIKE {$search} or translit0.name LIKE {$search} )
 AND eq.tbl_name_names_name_id1='{$cid}'
)
OR
(
     ( com1.common_name LIKE {$search} or translit1.name LIKE {$search} )
 AND eq.tbl_name_names_name_id='{$cid}'
)
";
		}else{
			$whereSearch=$countAllSearch;
		}
		
		$tables="
 {$this->dbprefix}tbl_name_names_equals eq
 LEFT JOIN {$this->dbprefix}tbl_name_names nam0 ON nam0.name_id = eq.tbl_name_names_name_id
 LEFT JOIN {$this->dbprefix}tbl_name_commons com0 ON com0.common_id = nam0.name_id
 LEFT JOIN {$this->dbprefix} tbl_name_transliterations translit0 ON translit0.transliteration_id = nam0.transliteration_id
 
 LEFT JOIN {$this->dbprefix}tbl_name_names nam1 ON nam1.name_id = eq.tbl_name_names_name_id1
 LEFT JOIN {$this->dbprefix}tbl_name_commons com1 ON com1.common_id = nam1.name_id
 LEFT JOIN {$this->dbprefix} tbl_name_transliterations translit1 ON translit1.transliteration_id = nam1.transliteration_id
 ";
		
		$sqlSearch="
SELECT
 IF(eq.tbl_name_names_name_id='{$cid}',eq.tbl_name_names_name_id1,eq.tbl_name_names_name_id) as 'id',
 IF(eq.tbl_name_names_name_id='{$cid}',com1.common_name,com0.common_name) as 'name',
 IF(eq.tbl_name_names_name_id='{$cid}',translit1.name,translit0.name) as 'translit'
 
FROM 
 {$tables}
WHERE
 {$whereSearch}
ORDER BY
 name, translit
";
		
		// get count of results
		$sqlcountsearch="
SELECT 
 COUNT(*) as 'c'
FROM
 {$tables}
WHERE
 {$whereSearch}
 ";
			
		// get count of results
		$sqlcountall="
SELECT 
 COUNT(*) as 'c'
FROM
 {$tables}
WHERE
 {$countAllSearch}
 ";


		$c1=0;
		$c2=0;$res=array();
		$resdb=mysql_query($sqlcountsearch);
		if($resdb){
			$row=mysql_fetch_assoc($resdb);
			if(isset($row['c'])){
				$c1=$row['c'];
			}
		}

		$resdb=mysql_query($sqlcountall);
		if($resdb){
			$row=mysql_fetch_assoc($resdb);
			if(isset($row['c'])){
				$c2=$row['c'];
			}
		}
		
		$resdb=mysql_query($sqlSearch);
		if($resdb){
			while($row=mysql_fetch_assoc($resdb)){
				$res[]=array('',0,$row['id'],"{$row['name']}   ({$row['translit']})");
			}
		}
		//print_r($res);
		
		$res=array('cf'=>$c1,'ca'=>$c2,'syns'=>$res);

		return $res;
	}
	// Save new pairs...
	function SaveMapLines($params){
	
		$new=$this->getMapLines($params,1,1);
		
		$sql="
INSERT INTO {$this->dbprefix}tbl_name_names_equals
(tbl_name_names_name_id,tbl_name_names_name_id1)
VALUES 
";	
		$cid=$params['cid'];
		$id2=$cid;
		$notdone=array();
		$successx=array();
		foreach($new as $id1=>$x){
			if($id1=='error'){
				foreach($x as $err){
					$notdone[]=array($err[0],$err[1],$err[1],'Not an ID.');
				}
				continue;
			}
			// If not in database yet, add it
			$row2=array();
			$sql2="SELECT COUNT(*) as 'c' FROM {$this->dbprefix}tbl_name_names_equals WHERE (
			    (tbl_name_names_name_id ='{$id1}' and tbl_name_names_name_id1='{$id2}')
			 or (tbl_name_names_name_id ='{$id2}' and tbl_name_names_name_id1='{$id1}')
			)
			LIMIT 1";
			$result2=db_query($sql2);
			if($result2){
				$row2=mysql_fetch_array($result2);
				if($row2['c']==0){
					$sql2 = $sql." ('{$id1}','{$id2}') ";
					$result2 = db_query($sql2);
					if($result2){
						logTbl_name_names_equals($id1,$id2,0);
						$successx[]=array($x,$id1,$id2);
						continue;
					}else{
						$notdone[]=array($x,$id1,$id2,mysql_error());
					}
				}else{
					$existed=(isset($row2['c']) && $row2['c']>0);
					$notdone[]=array($x,$id1,$id2,$existed?1:'unknown');
				}
			}else{
				$notdone[]=array($x,$id1,$id2,mysql_error());
			}
		}

		if(count($notdone)>0){
			$res=array('success'=>0, 'error'=>$notdone,'successx'=>$successx);
		}else if(count($successx)>0){
			$res=array('success'=>1,'successx'=>$successx);
		}else{
			$res=array('success'=>0);
		}
		return $res;
	}

}

		
$mapLines=new MapLines_editLit();
$mapLines->execFunction($_POST['function'],$_POST);
