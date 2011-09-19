<?php
session_start();
require("../inc/functions.php");

$_OPTIONS['key']='DKsuuewwqsa32czucuwqdb576i12';
@list($filename,$method)=explode('/',$_GET['id']);



/*

UPDATE tbl_img_definition SET img_service_path='/database'

=> downpic und imgBrowser (redirect), getPicInfo old, 
=> jdoka + view new (redirect), servlet JSONRPC new

*/
error_reporting(E_ALL);
$picinfo=getServer($filename);

$size=0;
	
if(isset($picinfo['url']) && $picinfo['url']!==false ){
	switch($method){
		default:
		case 'show':
			doRedirectShowPic($picinfo);
			break;
		case 'thumbs':
			doPicInfo($picinfo);
			break;
		case 'download':
		case 'jpcdownload':
			doRedirectDownloadPic($picinfo,'jpc',$size);
			break;
		case 'tiffdownload':
			doRedirectDownloadPic($picinfo,'tiff',$size);
			break;
	}
	exit;
}else{
	switch($method){
		default:
		case 'thumbs':
			jsonError('not found');
		case 'download':
		case 'jpcdownload':
		case 'tiffdownload':
			imgError('not found');
		case 'show':
			textError('not found');
	}
}

function jsonError($msg=''){
	header('Content-type: text/json');
	header('Content-type: application/json');
	echo json_encode(array('error'=>$msg));
	exit;
}
function textError($msg=''){
	echo "{$msg}";
	exit;
}
function imgError($msg=''){
	//header("");
	//mkimage
	exit;
}

function doPicInfo($picinfo){

	if($picinfo['djatoka']=='1'){
		// JSON RPC
		$url="{$picinfo['url']}/FReuD-Servlet/ImageScan";
		
		$request=json_encode(array(
			'filename'=>$picinfo['filename'],
			'specimenID'=>$picinfo['specimenID']
		));
		
		// performs the HTTP POST
		$opts=array(
			'http' => array(
				'method'  => 'POST',
				'header'  => 'Content-type: application/json',
				'content' => $request
			)
		);
		
		$context =stream_context_create($opts);
		if($fp=fopen($this->url, 'r', false, $context)){
			$response='';
			while($row=fgets($fp)){
				$response.=trim($row)."\n";
			}
			$response=json_decode($response,true);
			$response=array(
				$output=>'',
				$pics=>$response
			);
		}else{
			throw new Exception('Unable to connect to '.$this->url);
		}
		
	}else{
		global $_OPTIONS;
		$url="{$picinfo['url']}/detail_server.php?key={$_OPTIONS['key']}&ID={$picinfo['specimenID']}";
		$response=@file_get_contents($url,"r");
		$response=@unserialize($response);
	}
	
	if(!is_array($response)){
		jsonError("couldn't get information");
	}
	header('Content-type: text/json');
	header('Content-type: application/json');
	echo json_encode($response);
}

function url_exists($url){
	$opts=array(
		'http' => array(
			'method'  => 'POST',
			'header'  => 'Content-type: application/json',
			'timeout'=>10,
		)
		//timeout..
	);
	$context =stream_context_create($opts);
	if($fp=fopen($url, 'r', false, $context)){
		return true;
	}
	
	return false;
}

function doRedirectShowPic($picinfo){
	if($picinfo['djatoka']=='1'){
		$url="{$picinfo['url']}/viewer.html?requestfilename={$picinfo['requestFileName']}&filename={$picinfo['filename']}&specimenID={$picinfo['specimenID']}";
	}else{
		$url="{$picinfo['url']}/img/imgBrowser.php?name={$picinfo['requestFileName']}";
	}
	if(url_exists($url)){
		header("location: {$url}");
	}else{
		textError("couldn't find url");
	}
}
	
function doRedirectDownloadPic($picinfo,$type,$size=0){
	if($picinfo['djatoka']=='1'){
		switch($type){
			default:case'':case 'jpeg':
				$format='image/jpeg';break;
			case'tiff':
				$format='image/tiff';break;
		}
		$scale='1.0';
		$url="{$picinfo['url']}/resolver?url_ver=Z39.88-2004&rft_id={$picinfo['requestFileName']}&svc_id=info:lanl-repo/svc/getRegion&svc_val_fmt=info:ofi/fmt:kev:mtx:jpeg2000&svc.format={$format}&svc.level=1&svc.rotate=0&svc.scale={$scale}";
	}else{
		switch($type){
			default:case'':case 'jpeg':
				$format='';break;
			case'tiff':
				$format='&type=1';break;
		}
		$url="{$picinfo['url']}/img/downPic.php?name={$picinfo['requestFileName']}{$format}";
	}
	
	if(url_exists($url)){
		header("location: {$url}");
	}else{
		imgError("couldn't find url");
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
 s.specimen_ID,
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

	$result=mysql_query($sql);
	if($result ){
		$row=mysql_fetch_array($result);
		if(count($row)>0){
			$url="http://{$row['imgserver_IP']}/{$row['img_service_path']}/";
			return array('url'=>$url,'requestFileName'=>$picFilename,'filename'=>$row['filename'],'specimenID'=>$row['specimen_ID'],'djatoka'=>$row['djatoka']);
		}
	}
	return false;
}

function p($var){
	echo "<pre>";
	print_r($var);
	echo "</pre>";
}

?>