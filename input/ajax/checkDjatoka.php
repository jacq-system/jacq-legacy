<?php
//$_POST=$_GET;
ob_start();  // intercept all output


require_once('../inc/jsonRPCClient.php');
require_once("../inc/init.php");

$checkDjatoka=new checkDjatoka();
$methodName = (isset($_POST['method'])) ? $_POST['method'] : "";

$ret=array();
if(method_exists($checkDjatoka, $methodName)){
	try{
		$params=$_POST['params'];
		$ret=array(
			'res'=>$checkDjatoka->$methodName($params)
		);
		if(($a=$checkDjatoka->getInfo())){
			$ret['info']=$a;
		}

	}catch (Exception $e){
		$ret=array(
			'error'=>$e->getMessage()
		);
	}
}else{
	$ret=array(
		'error'=>"Metod: '{$methodName}' doesn't exist.",
	);
}

$ob = ob_get_clean();
if(strlen($ob)>0){
	$ret['ob']=$ob;
}
echo json_encode($ret);


class checkDjatoka{
	private $service=false;
	private $db=false;
	private $sharedkey=false;
	private $wrong=false;
	private $info=false;
	
	public function getInfo(){
		return $this->info;
	}
	
	public function is_validIP($ip) { 
		return preg_match("/^([1-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])"."(\.([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3}$/", $ip); 
	}
	
	public function __construct (){
		
	}
	
	private function getdB(){
		if(!$this->db){
			$this->db=clsDbAccess::Connect('INPUT');
		}
		return $this->db;
	}
	
	private function getService($serverIP){
		$this->getServerKey($serverIP);
		
		if(!$this->service){
			$this->service=new jsonRPCClient("http://{$serverIP}/database/json_rpc_scanPictures.php");
		//	$this->service=new jsonRPCClient("http://localhost/f/jsonservice/json_rpc_taxamatchMdld.php");
		}
		return $this->service;
	}
	
	public function getServerKey($serverIP){
		$db=$this->getdB();
		$dbst = $db->query("SELECT `key`, is_djatoka FROM herbarinput.tbl_img_definition WHERE imgserver_IP = " . $db->quote($serverIP)." GROUP BY imgserver_IP");
		
		$row=$dbst->fetch();
		$this->sharedkey=$row['key'];
		//throw new Exception(print_r($row,1));
		
		if(!$this->is_validIP($serverIP)){
			throw new Exception("No valid IP: {$serverIP}");
		}
		if ($row['is_djatoka']==0){
			throw new Exception("No Djatoka Server Configured for  IP: {$serverIP}");
		}
		if($this->sharedkey==''){
			throw new Exception("No shared KeyConfigured for  IP {$serverIP}");
		}
	}
	/*
	$_SESSION['checkPictures']['serverIP']=0;
	$_SESSION['checkPictures']['family']=0;
	$_SESSION['checkPictures']['source_id']=0;
	*/
	public function x_listInstitutions($params){
		$serverIP=$params['serverIP'];
		$source_id=isset($params['source_id'])?$params['source_id']:false;
		
		$db=$this->getdB();
		$dbst = $db->prepare("
SELECT 
  source_name,
  tbl_management_collections.source_id 
FROM
  tbl_management_collections,
  herbarinput.meta,
  tbl_img_definition 
WHERE tbl_management_collections.source_id = herbarinput.meta.source_id 
  AND tbl_management_collections.source_id = tbl_img_definition.source_id_fk 
  AND imgserver_IP = :imgserver_IP -- AND tbl_img_definition.is_djatoka=1
GROUP BY source_name 
ORDER BY source_name
	");
		$res='';
		$x=0;
		$dbst->execute(array(":imgserver_IP"=>$serverIP));
		foreach($dbst as $row) {
			$res .= "<option value=\"{$row['source_id']}\">{$row['source_name']}</option>\n";
			$x++;
		}
		if($x>1){
			$res = "<option value=\"0\">--- all ---</option>\n".$res;
		}
		$res=str_replace("<option value=\"{$source_id}\"","<option value=\"{$source_id}\" selected",$res);
		return $res;
	}


	// triggers an image import process
	public function x_ImportImages($params){
		$serverIP=$params['serverIP'];
		
		$service = &$this->getService($serverIP);
			
		$result = $service->importImages($serverIP, $this->sharedkey);
			
		if($result==1){
			$message="Import was successfully triggered";
		}else{
			$message="Thread Already running.";
		}
		return $message;
	}
	
	public function x_listImportLogs($params){
		$serverIP=$params['serverIP'];
		$thread_id=$params['thread_id'];
		
		$service = &$this->getService($serverIP);
		$logs = $service->listImportLogs($this->sharedkey,$thread_id);
		
		$result="";
		foreach($logs as $logmsg){
			$result.="<a href=\"javascript:processItem()\">{$logmsg}</a><br>";
		}
		return $result;
	}


	public function x_listImportThreads($params){


		$serverIP=$params['serverIP'];
		$starttime=$params['starttime'];
		
		$timestamp=strtotime($starttime); // 2011/1/19
		$d=date('d.m.Y H:i',$timestamp);
		
		$service = &$this->getService($serverIP);
		$ImportThreads = $service->listImportThreads($this->sharedkey,$timestamp);
		
		$result="";
		foreach($ImportThreads as $threadid=>$timestamp){
			$d=date('d.m.Y H:i',$timestamp);
			$result.="<a href=\"javascript:loadImportLog('{$threadid}')\">{$threadid},{$d}</a><br>";
		}
		
		return $result;
	}
	
	
	public function x_pictures_check($params){
		$serverIP=$params['serverIP'];
		$family=isset($params['family'])?$params['family']:false;
		$source_id=isset($params['source_id'])?$params['source_id']:false;
		$faulty=isset($params['faulty'])?$params['faulty']:false;
		$db=$this->getdB();
		
		$limit=' LIMIT 20'; 
		$scan_id=$this->getLatestScanId($serverIP);
		
		if($scan_id==0){
			throw new Exception("A Scan is already running on '{$serverIP}'. refresh in a few seconds.");
		}
		
		$c=array(0,0,0,0);
		$tab1='';
		
		// inDBchecked_butnotinArchive
		$sql="
SELECT
 sp.specimen_ID,
 sp.filename2
FROM
 herbar_view.view_tbl_specimens sp
 LEFT JOIN herbar_pictures.djatoka_files dj ON ( dj.filename=sp.filename2 and dj.scan_id='{$scan_id}')
";
		if($family){
			$sql.="LEFT JOIN tbl_tax_species ts ON ts.taxonID = sp.taxonID
 LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
 LEFT JOIN tbl_tax_families tf ON tf.familyID = tg.familyID
";
		}

		if($source_id){
			$sql.="
 LEFT JOIN herbarinput.tbl_management_collections mc ON mc.collectionID=sp.collectionID
";
		}
		
		$sql.="
WHERE
  sp.filename IS NOT NULL
 AND sp.filename not LIKE 'error%'
 AND digital_image != 0
 AND dj.ID is null

";
		if ($source_id){
			$sql.= " AND mc.source_id = ".$db->quote($source_id)."";
		}
		if ($family){
			$sql .= " AND tf.family LIKE " . $db->quote($family . '%');
		}
		$sql.=" {$limit}";

		$dbst = $db->query($sql);
		foreach($dbst as $row){
			$c[0]++;
			$value=htmlspecialchars($row['filename2']);
			if(!empty($row['specimen_ID'])){
				$specLink=" href=\"javascript:editSpecimens('<{$row['specimen_ID']}>')>\"";
			}else{
				$specLink="";
			}
			$tab1.=<<<EOF
<a href="javascript:editSpecimens('<{$row['specimen_ID']}>')">{$value}</a><br>
EOF;
		}
		$tab1.=<<<EOF
</td><td width='20'>&nbsp;</td><td>
EOF;
		// inArchive_butnotinDB
		// todo: filter with prefix (wu_) at institution
		$sql="
SELECT
 dj.filename,
 dj.inconsistency,
 sp.specimen_ID
FROM
 herbar_pictures.djatoka_files dj
 LEFT JOIN  herbar_view.view_tbl_specimens sp ON (sp.filename2=dj.filename)
WHERE
 dj.scan_id='{$scan_id}'
 and sp.specimen_ID is null
{$limit}
";
		$dbst = $db->query($sql);
		foreach($dbst as $row){
			$c[1]++;
			$val=htmlspecialchars($row['filename']);
			$inc='';
			if($row['inconsistency']!=0){
				if($row['inconsistency']==1){
					$inc=" (not in djatoka)";
				}else if($row['inconsistency']==2){
					$inc=" (not in archive!!!)";
				}
			}
			$specLink=" href=\"javascript:editSpecimensSimple('{$row['filename']}')\"";
			
			$tab1.=<<<EOF
<a{$specLink}">{$val}{$inc}</a><br>
EOF;
		}
		
		$tab1.=<<<EOF
</td><td width='20'>&nbsp;</td><td>
EOF;
		
		
		// inArchive_butnotCheckedinDB
		$sql="
SELECT
 dj.filename,
 sp.specimen_ID,
 dj.inconsistency
 
FROM
 herbar_pictures.djatoka_files dj
 LEFT JOIN  herbar_view.view_tbl_specimens sp ON (sp.filename2=dj.filename)
 LEFT JOIN herbarinput.tbl_management_collections mc ON mc.collectionID=sp.collectionID
 LEFT JOIN herbarinput.tbl_img_definition img ON img.source_id_fk=mc.source_id
";
		if($family){
			$sql.="LEFT JOIN tbl_tax_species ts ON ts.taxonID = sp.taxonID
 LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
 LEFT JOIN tbl_tax_families tf ON tf.familyID = tg.familyID
";
		}
		$sql.="
WHERE
 dj.scan_id='{$scan_id}'
 and sp.specimen_ID is not null
 and sp.digital_image = 0
 
";
		if ($source_id){
			$sql.= " AND mc.source_id = $source_id";
		}
		
		if ($family){
			$sql .= " AND tf.family LIKE " . $db->quote ($family . '%');
		}
		$sql.=" {$limit} ";

		$dbst = $db->query($sql);
		foreach($dbst as $row){
			$c[2]++;
			$val=htmlspecialchars($row['filename']);
			$inc='';
			if($row['inconsistency']!=0){
				if($row['inconsistency']==1){
					$inc=" (not in djatoka)";
				}else if($row['inconsistency']==2){
					$inc=" (not in archive!!!)";
				}
			}
			if(!empty($row['specimen_ID'])){
				$specLink=" href=\"javascript:editSpecimens('<{$row['specimen_ID']}>')>\"";
			}else{
				$specLink="";
			}
			$tab1.=<<<EOF
<a{$specLink}>{$row['filename']} {$inc}</a><br>
EOF;
		}
	
		
		$tab1.=<<<EOF
</td><td width='20'>&nbsp;</td><td>
EOF;
		
		
		
		if($faulty){
			// herbanumber_Fault
			$sql="
SELECT
 sp.specimen_ID,
 sp.herbNummer
FROM
 herbar_view.view_tbl_specimens sp
 LEFT JOIN herbarinput.tbl_management_collections mc ON mc.collectionID=sp.collectionID
 LEFT JOIN herbarinput.tbl_img_definition img ON img.source_id_fk=mc.source_id
";
		
			if($family){
				$sql.="LEFT JOIN tbl_tax_species ts ON ts.taxonID = sp.taxonID
 LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
 LEFT JOIN tbl_tax_families tf ON tf.familyID = tg.familyID
";
			}
			
			$sql.="
WHERE
 img.imgserver_IP = ".$db->quote($serverIP)."
 and sp.digital_image != 0
 and sp.filename2 LIKE 'error%'
";
			if ($source_id){
				$sql.= " AND mc.source_id = '{$source_id}'";
			}
			if ($family){
				$sql .= " AND tf.family LIKE " . $db->quote ($family . '%');
			}
			$sql.=" {$limit}";
			$dbst = $db->query($sql);
			foreach($dbst as $row){
				$c[3]++;
				$val=htmlspecialchars($row['HerbNummer']);
				if(!empty($row['specimen_ID'])){
					$specLink=" href=\"javascript:editSpecimens('<{$row['specimen_ID']}>')\"";
				}else{
					$specLink="";
				}
				$tab1.=<<<EOF
<a{$specLink}>{$val}</a><br>
EOF;
			}
		}else{
			$tab1.=<<<EOF
<a>Not processed.</a><br>
EOF;
		
		}
	
	
		
		$tab1=<<<EOF
<table align='center'>
<tr>
<th>{$c[0]} missing Pics</th><th></th>
<th>{$c[1]} missing database entries</th><th></th>
<th>{$c[2]} missing database checks</th><th></th>
<th>{$c[3]} faulty database entries</th><th></th>
</tr>
<tr>
 <td>
 {$tab1}
 </td>
</tr>
</table>
EOF;
		return $tab1;

	}


	public function x_djatoka_consistency_check($params){
		$db=$this->getdB();
		$serverIP=$params['serverIP'];
		$scan_id=$this->getLatestScanId($serverIP);
		
		$limit=' LIMIT 20'; 
		
		if($scan_id==0){
			return "A Scan is already running. refresh in a few seconds.";
		}
		$c=array(0,0);
		$tab1='';
		
		// Consistency: In Djatoka Not in Archive
		$sql="
SELECT
 dj.filename,
 sp.specimen_ID
FROM
 herbar_pictures.djatoka_files dj
 LEFT JOIN  herbar_view.view_tbl_specimens sp ON (sp.filename2=dj.filename)
WHERE
 dj.scan_id='{$scan_id}'
 and dj.inconsistency ='2'
 
{$limit}
";
		$dbst = $db->query($sql);
		foreach($dbst as $row){
			$c[0]++;
			if(!empty($row['specimen_ID'])){
				$specLink=" href=\"javascript:editSpecimens('<{$row['specimen_ID']}>')\"";
			}else{
				$specLink="";
			}
			$tab1.=<<<EOF
<a{$specLink}>{$row['filename']}</a><br>
EOF;
		}
		
		$tab1.=<<<EOF
</td><td width='20'>&nbsp;</td><td>
EOF;
		// Consistency: in Archive NOT in Djatoka
		$sql="
SELECT
 dj.filename,
 sp.specimen_ID
FROM
 herbar_pictures.djatoka_files dj
 LEFT JOIN  herbar_view.view_tbl_specimens sp ON (sp.filename2=dj.filename)
WHERE
 dj.scan_id='{$scan_id}'
 and dj.inconsistency ='1'
{$limit}
";
		$dbst = $db->query($sql);
		foreach($dbst as $row){
			$c[1]++;
			if(!empty($row['specimen_ID'])){
				$specLink=" href=\"javascript:editSpecimens('<{$row['specimen_ID']}>')\"";
			}else{
				$specLink="";
			}
			$tab1.=<<<EOF
<a{$specLink}>{$row['filename']}</a><br>
EOF;
		}
		
		
		$tab1=<<<EOF
<table align='center'>
<tr>
<th>{$c[0]} In Djatoka, not in Archive</th><th></th>
<th>{$c[1]} In Archive, not in Djatoka</th><th></th>
</tr>
<tr>
 <td>
 $tab1
 </td>
</tr>
</table>
EOF;
		return $tab1;
	}
	
	public function getLatestScanId($serverIP){
		$db=$this->getdB();
		$serverIPd=$db->quote($serverIP);
		
		$dbst2 = $db->query("SELECT scan_id FROM herbar_pictures.djatoka_scans WHERE finish IS NOT NULL AND errors is null and IP ={$serverIPd} LIMIT 1");
		
		$scan_id=false;
		if (($row=$dbst2->fetchColumn()) > 0) {
			$scan_id= $row['scan_id'];
		}
		
		// If there are any jobs within 600s => wait for it.
		$dbst = $db->query("SELECT count(scan_id) FROM herbar_pictures.djatoka_scans WHERE TIME_TO_SEC(TIMEDIFF(NOW(), start)) < 600 AND finish IS NULL AND IP ={$serverIPd}");
		
		if ($dbst->fetchColumn() > 0) {
			$this->info="A Scan is already running on '{$serverIP}'. refresh in a few seconds.";
		}
		
		return $scan_id;
	}
	
	//scan_id 	thread_id 	IP 	start 	finish 	errors
	public function x_importDjatokaListIntoDB($params){
		$serverIP=$params['serverIP'];
		
		$db=$this->getdB();
		$serverIPd=$db->quote($serverIP);
		
		// all jobs older than 600s will be marked as error
		$dbst = $db->query("SELECT count(scan_id) FROM herbar_pictures.djatoka_scans WHERE TIME_TO_SEC(TIMEDIFF(NOW(), start)) > 600 AND finish IS NULL AND IP ={$serverIPd}");
		if ($dbst->fetchColumn() > 0) {
			$db->query("UPDATE herbar_pictures.djatoka_scans SET finish = NOW(), errors = 'script terminated, entry corrected' WHERE finish IS NULL AND IP = {$serverIPd}");
		}
		
		// If there are any jobs within 600s => wait for it.
		$dbst = $db->query("SELECT count(scan_id) FROM herbar_pictures.djatoka_scans WHERE TIME_TO_SEC(TIMEDIFF(NOW(), start)) < 600 AND finish IS NULL AND IP ={$serverIPd}");
		
		if ($dbst->fetchColumn() > 0) {
			throw new Exception("A Scan is already running on '{$serverIP}'. refresh in a few seconds.");
		}
		// Begin
		
		// mark the beginning
		$db->query("INSERT INTO herbar_pictures.djatoka_scans SET IP ={$serverIPd}, start = NOW()");
		$scanid = $db->lastInsertId();
		
		ignore_user_abort(true);
		set_time_limit(0);
		
		$service = &$this->getService($serverIP);
			
		$filesArchive = $service->listArchiveImages($this->sharedkey);
		$filesDjatoka = $service->listDjatokaImages($this->sharedkey);
		
		if($filesArchive==-1 || $filesDjatoka==-1){
			throw new Exception("Key not accepted {$serverIP}");
		}
		
		$inArchive_notinDjatoka=array_diff($filesArchive,$filesDjatoka);
		
		$inArchive_notinDjatoka=array_flip($inArchive_notinDjatoka);
		
		$x=0;
		// inconsistency: 0=> no errors, 1: not in djatoka, 2 not in archive
		$sql="INSERT INTO herbar_pictures.djatoka_files (scan_id,filename,inconsistency) VALUES ";
		foreach($filesArchive as $filename){
			$inconsistency=(isset($inArchive_notinDjatoka[$filename]))?1:0;
			$sql.="\n('{$scanid}',".$db->quote($filename).",'{$inconsistency}'),";
			$x++;
		}
		
		$inDjatoko_notinArchive=array_diff($filesDjatoka,$filesArchive);
		foreach($inDjatoko_notinArchive as $filename){
			$sql.="\n('{$scanid}',".$db->quote($filename).",'2'),";
		}
		
		$sql=substr($sql,0,-1);
		if($x>0){
			$db->query($sql);
		}
		
		$db->query("UPDATE herbar_pictures.djatoka_scans SET finish = NOW() WHERE scan_id={$scanid}");
		set_time_limit(ini_get('max_execution_time')); 
		
		return $scanid;
	}
	

}