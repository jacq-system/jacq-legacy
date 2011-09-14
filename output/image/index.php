<?php
session_start();
require("../inc/functions.php");

@list($filename,$method)=explode('/',$_GET['id']);

if($method=='allpics'){
	doPicInfo($id);
}else{
	doRedirectPic($id);
}
  
function doPicInfo($request){
	$key='';
	$picinfo=getServer($request);
	
	if(isset($picinfo['url']) &&$picinfo['url']!==false ){
		header('Content-type: text/json');
		header('Content-type: application/json');
		$a=array('filename'=>$picinfo['filename'],'specimenID'=>$picinfo['specimenID']);
		
		if($picinfo['djatoka']=='1'){
			// JSON RPC
			$url=$picinfo['url']."/getPicInfo/";

		}else{
			
		}
		
		exit;
	}else{
		//header("");
		echo<<<EOF
		Not found
EOF;
		exit;
	}
}

function doRedirectPic($request){
	$picinfo=getServer($request);
	
	if(isset($picinfo['url']) &&$picinfo['url']!==false ){
		if($picinfo['djatoka']=='1'){
			$url=$picinfo['url']."/showPic/?filename=".=$picinfo['filename']."&specimenID=".$picinfo['specimenID'];
			header("location: {url}");
			// viewer.html?url=http://memory.loc.gov/gmd/gmd433/g4330/g4330/np000066.jp2
			// viewer.html?specimenID=ID&collection=filename&show=$requestedFilename
		}else{	
			$url=$picinfo['url']."/showPic/?filename=".=$picinfo['filename']."&specimenID=".$picinfo['specimenID'];
			header("location: {url}");
			// viewer.html?url=http://memory.loc.gov/gmd/gmd433/g4330/g4330/np000066.jp2
			// viewer.html?specimenID=ID&collection=filename&show=$requestedFilename
		}
	}else{
		echo<<<EOF
		Not found
EOF;
	}
}

// request: can be specimen ID or filename
function getServer($request){
	$picFilename='';
	$searchpicFilename=false;
	$where='';
	$specimenID=0;
	//specimenid
	if(is_numeric($request)){
		$specimenID=$request;
		$searchpicFilename=true;
	//tabs..
	}else if(strpos($request,'tab_')!==false){
		$result=preg_match('/tab_(?P<specimenID>\d+\.(.*))/',$request,$matches);
		if($result==1){
			$specimenID=$result['specimenID'];
		}
		$picFilename=$request;
	// obs digital_image_obs
	}else if(strpos($request,'obs_')!==false){
		$result=preg_match('/obs_(?P<specimenID>\d+\.(.*))/',$request,$matches);
		if($result==1){
			$specimenID=$result['specimenID'];
		}
		$picFilename=$request;
	// filename
	}else{
		$where=" s.filename = '".mysql_real_escape_string($request)."'";
		$picFilename=$request;
	}
	
	if($specimenID!=0){
		$where=" s.specimen_ID = '".mysql_real_escape_string($specimenID)."'";
	}
	
	$sql="
SELECT
 i.imgserver_IP,
 i.img_service_path,
 i.djatoka,
 s.specimenID,
 s.filename
 
FROM
 tbl_specimens s,
 tbl_management_collections m,
 tbl_img_definition i

WHERE
 {$where}
 AND m.collectionID = s.collectionID
 AND i.source_id_fk = m.source_id
";
	if($searchpicFilename){
		$picFilename=$row['filename'];
	}
	$result=mysql_query($sql);
	if($result && $row=mysql_fetch_array($result)){
		$url="http://{$row['imgserver_IP']}/{$row['img_service_path']}/";
		return array('url'=>$url,'requestFileName'=>$picFilename,'filename'=>$row['filename'],'specimenID'=>$row['specimenID'],'djatoka'=>$row['djatoka']);
	}
	return false;
}

function p($var){
	echo "<pre>";
	print_r($var);
	echo "</pre>";
}

?>