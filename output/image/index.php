<?php
session_start();
require("../inc/functions.php");

/*
image/specimenID|obs_specimenID|tab_specimenID|img_coll_short_HerbNummer[/download|thumb|resized|thumbs|show]/format[tiff/jpc]
*/

$_OPTIONS['key']='DKsuuewwqsa32czucuwqdb576i12';
@list($filename,$method,$format)=explode('/',$_GET['imageQuery']);

$picinfo=getServer($filename);
$q=getQuery();
$debug=0;
error_reporting(E_ALL);
if($debug){
	print_r($picinfo);
}
if(isset($picinfo['url']) && $picinfo['url']!==false ){
	switch($method){
		default:
			doRedirectDownloadPic($picinfo,$method,0);
			break;
		case 'download':
			doRedirectDownloadPic($picinfo,$format,0);
			break;
		case 'thumb':
			doRedirectDownloadPic($picinfo,$format,1);
			break;
		case 'resized':
			doRedirectDownloadPic($picinfo,$format,2);
			break;
		case 'thumbs':
			doPicInfo($picinfo);
			break;
		case 'show':
			doRedirectShowPic($picinfo);
			break;
	}
	exit;
}else{
	switch($method){
		default:
		case 'download':
		case 'thumb':
			imgError('not found');
		case 'thumbs':
			jsonError('not found');
		case 'show':
			textError('not found');
	}
}



function doPicInfo($picinfo){
	global $q,$debug;
	if($picinfo['is_djatoka']=='1'){
		// JSON RPC
		$url="{$picinfo['url']}/FReuD-Servlet/ImageScan?requestfilename={$picinfo['requestFileName']}&specimenID={$picinfo['specimenID']}";
		
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
		if($fp=fopen($url, 'r', false, $context)){
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
			throw new Exception('Unable to connect to '.$url);
		}
		
	}else{
		global $_OPTIONS;
		$url="{$picinfo['url']}/detail_server.php?key={$_OPTIONS['key']}&ID={$picinfo['specimenID']}{$q}";
	
		$response=@file_get_contents($url,"r");
		$response=@unserialize($response);
	}
	if($debug){
		p($response);
		exit;
	}
	if(!is_array($response)){
		jsonError("couldn't get information");
	}
	
	header('Content-type: text/json');
	header('Content-type: application/json');
	echo json_encode($response);
}



function doRedirectShowPic($picinfo){
	global $q,$debug;
	
	if($picinfo['is_djatoka']=='1'){
		$url="{$picinfo['url']}/viewer.html?requestfilename={$picinfo['requestFileName']}&specimenID={$picinfo['specimenID']}";
	}else{
		$url="{$picinfo['url']}/img/imgBrowser.php?name={$picinfo['requestFileName']}{$q}";
	}
	if($debug){
		p($url);
		exit;
	}
	$url=cleanURL($url);
	if(url_exists($url)){
		header("location: {$url}");
	}else{
		textError("couldn't find url");
	}
}
	
function doRedirectDownloadPic($picinfo,$format,$thumb=0){
	global $q,$debug;
	
	if($picinfo['is_djatoka']=='1'){
		switch($format){
			default:case'':case 'jpeg':
				$format='image/jpeg';break;
			case'tiff':
				$format='image/tiff';break;
		}
		$scale='1.0';
	
		if($thumb!=0){
			if($thumb==1){
				$scale='225';//px??todo
			}
			if($thumb==1){
				$scale='1300';
			}
		}
		
		$url="{$picinfo['url']}/resolver?url_ver=Z39.88-2004&rft_id={$picinfo['requestFileName']}&svc_id=info:lanl-repo/svc/getRegion&svc_val_fmt=info:ofi/fmt:kev:mtx:jpeg2000&svc.format={$format}&svc.level=1&svc.rotate=0&svc.scale={$scale}";
	}else{
		switch($format){
			default:case'':case 'jpeg':
				$format='';break;
			case'tiff':
				$format='&type=1';break;
		}
		$fileurl='downPic.php';
		if($thumb!=0){
			if($thumb==1){
				$fileurl='mktn.php';
			}
			if($thumb==2){
				$fileurl='mktn_kp.php';
			}
		}
		
		$url="{$picinfo['url']}/img/{$fileurl}?name={$picinfo['requestFileName']}{$format}{$q}";
	}
	$url=cleanURL($url);
	if($debug){
		p($url);
		exit;
	}
	header("location: {$url}");
}

// request: can be specimen ID or filename
function getServer($request){
	global $debug;
	
	$requestFileName='';
	$where='';
	$specimenID=0;
	
	//specimenid
	if(is_numeric($request)){
		$specimenID=$request;
		$requestFileName=$specimenID;
	//tabs..
	}else if(strpos($request,'tab_')!==false){
		$result=preg_match('/tab_((?P<specimenID>\d+)[\._]*(.*))/',$request,$matches);
		if($result==1){
			$specimenID=$matches['specimenID'];
		}
		$requestFileName=$request;
	// obs digital_image_obs
	}else if(strpos($request,'obs_')!==false){
		$result=preg_match('/obs_((?P<specimenID>\d+)[\._]*(.*))/',$request,$matches);
		if($result==1){
			$specimenID=$matches['specimenID'];
		}
		$requestFileName=$request;
	
	// filename
	}else{
		
		$result=preg_match('/((?P<filename>.*)\.)/',$request,$matches);
		if($result==1){
			$request=$matches['filename'];
		}else{
			$request=$request;
		}
		print_r($matches);
		$where=" s.filename = '".mysql_real_escape_string($request)."'";
		$requestFileName=$request;
	}
	
	if($specimenID!=0){
		$where=" s.specimen_ID = '".mysql_real_escape_string($specimenID)."'";
	}
	
	$sql="
SELECT
 i.imgserver_IP,
 i.djatoka_path,
 i.is_djatoka,
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

	if($debug){
		print_r($sql);
	}
	$result=mysql_query($sql);
	if($result ){
		$row=mysql_fetch_array($result);
		if(count($row)>0){
			if($debug){
				//$row['is_djatoka']=1;
				print_r($row);
			}
			$url="http://{$row['imgserver_IP']}/{$row['djatoka_path']}/";
			return array('url'=>$url,'requestFileName'=>$requestFileName,'filename'=>$row['filename'],'specimenID'=>$row['specimen_ID'],'is_djatoka'=>$row['is_djatoka']);
		}
	}
	return false;
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
	switch($msg){
		default: case 'not found':$pic='../images/404.png';break;
	}
	Header('Content-Type: image/png');
	Header('Content-Length: '.filesize($pic));
    @readfile($pic);
	exit;
}

function url_exists($url){
	$opts=array(
		'http' => array(
			'method'  => 'POST',
			'header'  => 'Content-type: application/json',
			'timeout'=>20,
		)
		//timeout..
	);
	$context =stream_context_create($opts);
	if($fp=@fopen($url, 'r', false, $context)){
		return true;
	}
	
	return false;
}

function cleanURL($url){
	$url=preg_replace('/([^:])\/\//','$1/',$url);
	return $url;
}

function getQuery(){
	$qstr='';
	foreach($_GET as $k=>$v){
		if($k!='imageQuery'){
			$qstr.="&{$k}=".rawurlencode($v);
		}
	}
	return $qstr;
}

function p($var){
	echo "<pre>".print_r($var,1)."</pre>";
}

?>