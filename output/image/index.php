<?php
session_start();
require("../inc/functions.php");

@list($filename,$method)=explode('/',$_GET['id']);

/*

UPDATE tbl_img_definition SET img_service_path='/database'

=> downpic und imgBrowser (redirect), getPicInfo old, 
=> jdoka + view new (redirect), servlet JSONRPC new

*/

$picinfo=getServer($filename);

$size=0;
	
if(isset($picinfo['url']) &&$picinfo['url']!==false ){
	switch($method){
		default:
		case 'all':
			doPicInfo($filename);
			break;
		case 'download':
		case 'jpcdownload':
			doRedirectDownloadPic($filename,'jpc',$size);
			break;
		case 'tiffdownload':
			doRedirectDownloadPic($filename,'tiff',$size);
			break;
		case 'show':
			doRedirectShowPic($filename);
			break;
	}
}else{
	switch($method){
		default:
		case 'all':
			echo json_encode(array('error'=>'not found'));
			break;
		case 'download':
		case 'jpcdownload':
		case 'tiffdownload':
			// display error image
			//header("");
			break;
		case 'show':
			echo<<<EOF
	Not found
EOF;
			break;
	}
}
exit;

function doPicInfo($request){
	if($picinfo['djatoka']=='1'){
		// JSON RPC
		$a=array('filename'=>$picinfo['filename'],'specimenID'=>$picinfo['specimenID']);
		$url="{$picinfo['url']}/servlet.php";
		$a=@file_get_contents($url,"r"));
		$a=@json_decode($a,1);
	}else{
		$key='DKsuuewwqsa32czucuwqdb576i12';
		$url="{$picinfo['url']}/detail_server.php?key={$key}&ID={$picinfo['specimenID']}";
		$a=@file_get_contents($url,"r"));
		$a=@unserialize($a);
	}
	if(!is_array($a)){
		$a=array('error'=>"couldn't get information");
	}
	header('Content-type: text/json');
	header('Content-type: application/json');
	$a=json_encode($a);
	echo $a;
	exit;
}

function doRedirectShowPic($picinfo){
	if($picinfo['djatoka']=='1'){
		$url="{$picinfo['url']}/adore-djatoka-viewer-2.0/viewer.html?requestfilename{$picinfo['requestFileName']}&filename{$picinfo['filename']}&specimenID={$picinfo['specimenID']}";
	}else{
		$url="{$picinfo['url']}/img/imgBrowser.php?name{$picinfo['requestFileName']}";
	}
	header("location: {url}");
}

function doRedirectDownloadPic($picinfo,$type,$size=0){
	if($picinfo['djatoka']=='1'){
		$d='';
		if($type=='tiff'){
			$d='';
		}
		$url="{$picinfo['url']}/djatoka?filename={$picinfo['filename']}";
	}else{
		$d='';
		if($type=='tiff'){
			$d='&type=1';
		}
		$url="{$picinfo['url']}/img/downPic.php?name={$picinfo['requestFileName']}{$d}";
	}
	
	if(is_file($url)){
		header("location: {url}");
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