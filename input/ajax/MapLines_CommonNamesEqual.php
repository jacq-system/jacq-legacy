<?PHP
session_start();
require_once('../inc/connect.php');
require_once('../inc/cssf.php');
require_once('../inc/log_functions.php');
require_once('../inc/mapLines.php');
require_once('../inc/internMDLDService.php');
no_magic();

foreach($_GET as $k=>$v){
	$params[$k]=$v;
}

class MapLines_editLit extends MapLines{
	var $pagination=5;
	
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
		
		$cid=0;
		$page=$params['pageIndex'];
		$pbegin=$page*$this->pagination;
		
		$fields="
 eq.tbl_name_names_name_id as 'id0',
 eq.tbl_name_names_name_id1 as 'id1',
 com0.common_name as 'name0',
 com1.common_name as 'name1'
";
		$tables="
FROM
 {$this->dbprefix}tbl_name_names_equals eq
 LEFT JOIN {$this->dbprefix}tbl_name_names nam0 ON nam0.name_id = eq.tbl_name_names_name_id
 LEFT JOIN {$this->dbprefix}tbl_name_commons com0 ON com0.common_id = nam0.name_id
 
 LEFT JOIN {$this->dbprefix}tbl_name_names nam1 ON nam1.name_id = eq.tbl_name_names_name_id1
 LEFT JOIN {$this->dbprefix}tbl_name_commons com1 ON com1.common_id = nam1.name_id
 ";
		$whereCountAll="
   eq.tbl_name_names_name_id='{$cid}'
OR eq.tbl_name_names_name_id1='{$cid}'	
";
		// get all counts..
		$sqlcountall="
SELECT 
 COUNT(*) as 'c'
{$tables}
WHERE
 {$whereCountAll}
 ";
		//mdld => Service!
		if(isset($params['mdldSearch']) && strlen($params['mdldSearch'])>0){
			global $_OPTIONS;
			
			$this->limit=4;
			$this->block_limit=2;
			
			$uninomial=$params['mdldSearch'];
			$lenUninomial=mb_strlen(trim($uninomial));
			$lenlim=min($lenUninomial/2,$this->limit);
			$uninomial=strtolower($uninomial);
		
			$whereSearch="
(
     mdld('{$uninomial}',com0.common_name, {$this->block_limit}, {$this->limit}) <  LEAST(CHAR_LENGTH(com0.common_name)/2,{$lenlim})
 AND eq.tbl_name_names_name_id1='{$cid}'
)
OR
(
     mdld('{$uninomial}',com1.common_name, {$this->block_limit}, {$this->limit}) <  LEAST(CHAR_LENGTH(com1.common_name)/2,{$lenlim})
 AND eq.tbl_name_names_name_id='{$cid}'
)
LIMIT {$pbegin},{$this->pagination}
";
			

			$sqlSearch="
SELECT 
 {$fields}
{$tables}
WHERE
 {$whereSearch}
";
		// get count of results
		$sqlcountsearch="
SELECT 
 COUNT(*) as 'c'
{$tables}
WHERE
 {$whereSearch}
 ";
		
			$sql=array($sqlSearch,$sqlcountsearch,$sqlcountall);
			
			$service = new internMDLDService($_OPTIONS['internMDLDService']['url'],$_OPTIONS['internMDLDService']['password'],1);
			$sql=base64_encode($query);
			try {
				$res = $service->getSQLResults($sql);
			}catch (Exception $e) {
				echo "Fehler " . nl2br($e);
			}
		
		}else{
			
			if(isset($params['mysqlSearch']) && strlen($params['mysqlSearch'])>0){
				$search="'".mysql_escape_string("%".$params['mysqlSearch']) . "%'";
				
				$whereSearch="
(
     com0.common_name LIKE {$search}
 AND eq.tbl_name_names_name_id1='{$cid}'
)
OR
(
     com1.common_name LIKE {$search}
 AND eq.tbl_name_names_name_id='{$cid}'
)
";
				$sqlSearch="
SELECT 
 {$fields}
{$tables}
WHERE
 {$whereSearch}
";
				// get count of results
				$sqlcountsearch="
SELECT 
 COUNT(*) as 'c'
{$tables}
WHERE
 {$whereSearch}
 ";
			}else{
				$sqlSearch="
SELECT 
 {$fields}
{$tables}
WHERE
  {$whereCountAll}
";
				// get count of results
				$sqlcountsearch="
SELECT 
 COUNT(*) as 'c'
{$tables}
WHERE
  {$whereCountAll}
 ";
			}
			
			$sql=array($sqlSearch,$sqlcountsearch,$sqlcountall);
			
			$res=array();
			$res[0]='';
			foreach($sql as $k=>$v){
				$resdb=mysql_query($v);
				//echo $v;
				if($resdb){
					while($row=mysql_fetch_assoc($resdb)){
						$res[$k][]=$row;
					}
				}
			}

		

		}
		//print_r($res);
		
		$res=array('cf'=>$res[1][0]['c'],'ca'=>$res[2][0]['c'],'syns'=>$res[0]);
		
		
		
		return $res;
	}
	
	// Save new pairs...
	function SaveMapLines($params){
	
		$citid=$params['citationID'];
		$uid=$_SESSION['uid'];
		
		$new=$this->getMapLines($params,1);
		
		$sql = "DELETE FROM {$this->dbprefix}tbl_name_names_equals WHERE tbl_name_names_name_id='{$params['leftID']}' and tbl_name_names_name_id1='{$params['rightID']}' LIMIT 1";
		logTbl_name_names_equals($params['leftID'],$params['rightID'],2);
		
		// todo: review...
		$sql="
INSERT INTO {$this->dbprefix}tbl_name_names_equals
(tbl_name_names_name_id,tbl_name_names_name_id1)
VALUES 
";	
		$val='';
		$notdone=array();
		$successx=array();
		foreach($new as $taxonID=>$obj){
			foreach($obj as $acctaxonID=>$x){
				// If not in database yet, add it
				$row2=array();
				$sql2 = "SELECT COUNT(*) as 'c' FROM {$this->dbprefix}tbl_name_names_equals WHERE tbl_name_names_name_id='{$params['leftID']}' and tbl_name_names_name_id1='{$params['rightID']}' LIMIT 1";
				$result2 = db_query($sql2);
				if($result2 && $row2 = mysql_fetch_array($result2)){
					if($row2['c']==0){
						$sql2 = $sql." ('{$params['leftID']}','{$params['rightID']}') ";
						$result2 = db_query($sql2);
						if($result2){
							logTbl_name_names_equals($params['leftID'],$params['rightID'],2);
							$successx[]=$x;
							continue;
						}
					}
				}
				$existed=(isset($row2['c']) && $row2['c']>0);
				$notdone[]=array($x,$taxonID,$acctaxonID,$existed);
			}
		}
		if(count($notdone)>0){
			$res=array('success'=>0, 'error'=>$notdone,'successx'=>$successx);
		}else{
			$res=array('success'=>1,'successx'=>$successx);
		}
		return $res;
	}
}

		
$mapLines=new MapLines_editLit();
$mapLines->execFunction($_POST['function'],$_POST);
