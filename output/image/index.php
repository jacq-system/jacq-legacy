<?php
session_start();
require("../inc/functions.php");



@list($id,$img_def_ID,$method,$key)=explode('-',$_GET['id']);

if($method=='allpics'){
	JsonPicInfo($id,$key);
}else if(isIPv4Adress($server_adress) && !isSpecimenID($id) ){
	redirectPicName($id,$server_adress);
}else if(isSpecimenID($id) ){
	redirectPicSpecimenID($id);
}

  
function getPicInfo($request,$key=''){
	$server=getServer($request);

	if($server && isset($server['imgserver_IP']) && isset($server['img_service_path'])){
		header('Content-type: text/json');
		header('Content-type: application/json');
		$iurl="http://{$server['imgserver_IP']}/{$server['img_service_path']}/getPicInfo.php?specimenID={$specimenID}&m=info&key={$key}";

		echo file_get_contents($iurl);
		exit;
	}else{
		//header("");
		echo<<<EOF
		Not found
EOF;
		exit;
	}
	
}



function redirectPic($request){
	
	if($server && isset($server['imgserver_IP']) && isset($server['img_service_path'])){
		header("location: http://{$imgserver}/showPic.php?name={$specimenID}");
	
	}else{
		
		//header("");
		
		echo<<<EOF
		Not found
EOF;
	}
	
}

// request: can be specimen ID or filename
function getServer($request){
	$path='';
	$picFilename='';
	
	$search=mysql_real_escape_string($request);
	
	$where='';

	//tabs..
	if(strpos($request,'')!==false){
		
		$where="";
	// obs digital_image_obs
	}else if(strpos($request,'')!==false){
	
	// specimenID
	}else if(is_numeric($request)){
	
	//"normal" image digital_image 	
	}else{
	
	}
	
	$sql="
SELECT
 i.imgserver_IP,
 i.img_service_path,
 img_def_ID
 
FROM
 tbl_specimens s,
 tbl_management_collections m,
 tbl_img_definition i

WHERE
(    s.specimen_ID = '".mysql_real_escape_string($specimenID)."'
  OR s.filename=	 = '".mysql_real_escape_string($specimenID)."'
)
 AND 
 AND m.collectionID = s.collectionID
 AND i.source_id_fk = m.source_id
";

	$result=mysql_query($sql);
	if($result && $row=mysql_fetch_array($result)){
		$server=$row;
		return $server;
	}
	return false;
}


function p($var){
	echo "<pre>";
	print_r($var);
	echo "</pre>";
}

?>