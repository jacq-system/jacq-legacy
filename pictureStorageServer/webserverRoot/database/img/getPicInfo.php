<?php
require_once("../../inc/connect.php");

// Connect to database
db_connect( $_CONFIG['DATABASES']['INPUT'] );


if(isset($_GET['m']) && $_GET['m']=='info'){
	
	$key = $_GET['key'];
	if ($key!='DKsuuewwqsa32czucuwqdb576i12') die('');  // security
	
	$id = intval($_GET['specimenID']);
	
	$picture = new Picture();
	$transfer=$picture->getPicPathDetails($id);

	$transfer['output'] = ob_get_clean();
	
	echo json_encode($transfer);
}

class Picture {
	
	function getPicPathDetails($specimenID){
		
		$path='';
		$picFilename='';
		
		$sql="
SELECT
 s.HerbNummer,
 s.specimen_ID,
 s.collectionID,
 m.source_id,
 m.coll_short_prj,
 i.img_directory,
 i.img_obs_directory,
 i.img_tab_directory,
 i.HerbNummerNrDigits,
 i.imgserver_IP,
 i.img_service_path,
 i.img_def_ID
 
FROM
 tbl_specimens s,
 tbl_management_collections m,
 tbl_img_definition i

WHERE
     s.specimen_ID = '".mysql_real_escape_string($specimenID)."'
 AND m.collectionID = s.collectionID
 AND i.source_id_fk = m.source_id
";
/*
	
SELECT 
 filename,
 HerbNummer,
 COUNT(*)

FROM
 `tbl_specimens` s

GROUP BY
 filename

HAVING
 COUNT(*)>1
 
 
SELECT 
 CONCAT(m.coll_short_prj,'_',s.`HerbNummer`) as 't',
 HerbNummer,
 COUNT(*)
FROM
 `tbl_specimens` s
 LEFT JOIN tbl_management_collections m ON m.collectionID = s.collectionID
GROUP BY
 t
HAVING
 COUNT(*)>1
	*/
		$result=db_query($sql);
		if($row=mysql_fetch_array($result)){
			
			// ----- pictures of specimen -----
			$picFilename=$row['coll_short_prj']."_";
			if($row['HerbNummer']){
				if (strpos($row['HerbNummer'],"-")===false){
					if ($row['collectionID']==89){
						$picFilename.=sprintf("%08d",$row['HerbNummer']);
					}else{
						$picFilename.=sprintf("%0".$row['HerbNummerNrDigits']."d",$row['HerbNummer']);
				 	}
				}
			}else{
				$picFilename.=$row['specimen_ID'];
			}
			
			$picDefs=array(
				$row['img_directory']=>$picFilename,
				$row['img_obs_directory']=>"obs_".$row['specimen_ID'],
				$row['img_tab_directory']=>"tab_".$row['specimen_ID']
			);
			
			//$pics['pics']=$this->findShellPictures($picDefs);
			$pics['pics']=$this->findDjatokaPictures($picDefs);
			$pics['img_def_ID']=$row['img_def_ID'];
		}
		
		return $pics;
		
	}
	
	function findDjatokaPictures($picDefs){
		
		$ret=array();
		foreach($picDefs as $path=>$picFilename){
			
			$request=json_encode(array(
				'method' => 'list',
				'params' => '',
				'id' => ''
			));
			$service=array(
				'host'=>'localhost',
				'port'=>'8080',
				'path'=>'/FReuD-Servlet/ImageScan'
			);
			
			$response=http_request('GETRAW',$service['host'],$service['port'],$service['path'],'',$request,'','',1000,false,false);
			
			// todo
			
			
					
		}
		return $ret;
	}
	
	function findShellPictures($imageDef){
		$ret=array();
		foreach($imageDef as $path=>$picFilename){
			$filelist=shell_exec("find ".$path."/ -name '".basename($picFilename)."*'");
			$filelist=explode("\n",$filelist);
			foreach($filelist as $file){
				$file=trim($file);
				if($file!=''){
	     			$ret[]= basename($file);
				}
			}
		}
		return $ret;
	}

}


function http_request(
	$verb = 'GET',			/* HTTP Request Method (GET and POST supported) */ 
	$ip,					/* Target IP/Hostname */ 
	$port = 80,				/* Target TCP port */ 
	$uri = '/',				/* Target URI */ 
	$getdata = array(),		/* HTTP GET Data ie. array('var1' => 'val1', 'var2' => 'val2') */ 
	$postdata = array(),	/* HTTP POST Data ie. array('var1' => 'val1', 'var2' => 'val2') */ 
	$cookie = array(),		/* HTTP Cookie Data ie. array('var1' => 'val1', 'var2' => 'val2') */ 
	$custom_headers = array(),/* Custom HTTP headers ie. array('Referer: http://localhost/ */ 
	$timeout = 1000,		/* Socket timeout in milliseconds */ 
	$req_hdr = false,		/* Include HTTP request headers */ 
	$res_hdr = false		/* Include HTTP response headers */
	){
	
	$ret='';
	$verb=strtoupper($verb);
	
	$cookie_str='';
	$getdata_str=count($getdata)?'?':'';
	$postdata_str='';
	
	if(is_array($getdata))
		foreach($getdata as $k=>$v)
			$getdata_str.=urlencode($k).'='.urlencode($v).'&';
	
	if(is_array($postdata))
		foreach($postdata as $k=>$v)
			$postdata_str.=urlencode($k).'='.urlencode($v).'&';

	if(is_array($cookie))
		foreach($cookie as $k=>$v)
			$cookie_str.=urlencode($k).'='.urlencode($v).'; ';
	$crlf="\r\n";
	
	$verba=($verb=='POSTRAW')?'POST':(($verb=='GETRAW')?'GET':$verb);
	
	$req =$verba.' '.$uri. $getdata_str.' HTTP/1.1'.$crlf; 
	$req.='Host: '.$ip.$crlf; 
	$req.='User-Agent: Mozilla/5.0 Firefox/3.6.12'.$crlf; 
	$req.='Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*'.'/*;q=0.8'.$crlf; 
	$req.='Accept-Language: en-us,en;q=0.5'.$crlf; 
	$req.='Accept-Encoding: deflate'.$crlf; 
	$req.='Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7'.$crlf;
	
	if(is_array($cookie))
		foreach($custom_headers as $k=>$v)
			$req.=$k.':'.$v.$crlf;
	

	if(strlen($cookie_str)>0)
		$req.='Cookie:'.substr($cookie_str,0,-2).$crlf;

	if( ($verb=='POSTRAW' && strlen($postdata)>0) || ($verb=='GETRAW' && strlen($getdata)>0)){

		$postdata_str=(strlen($postdata)>0)?$postdata:$getdata;
		
		$req.='Content-Length:'.strlen($postdata_str).$crlf.$crlf;
		$req.=$postdata_str;
	
	}else if($verb=='POST' && !empty($postdata_str)){

		$postdata_str=substr($postdata_str,0,-1);
		$req.='Content-Type:application/x-www-form-urlencoded'.$crlf;
		$req.='Content-Length:'.strlen($postdata_str).$crlf.$crlf;
		$req.=$postdata_str;
	
	}else{

		$req.=$crlf;
	}
	
	if($req_hdr){
		$ret.=$req;
	}
	
	if(($fp=@fsockopen($ip,$port,$errno,$errstr))==false)
		return "Error $errno: $errstr\n";
	
	stream_set_timeout($fp,0,$timeout*1000);
	//echo "-.$req.-";
	fputs($fp,$req);
	while($line=fgets($fp))
		$ret.=$line;
	fclose($fp);
	
	if(!$res_hdr){
		$ret=substr($ret,strpos($ret,"\r\n\r\n")+4);
	}
	
	return $ret;
}

?>