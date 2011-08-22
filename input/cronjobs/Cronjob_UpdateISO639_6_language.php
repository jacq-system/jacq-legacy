<?php 

// Todo, 3.8.2011!
// ghomolka
require("../inc/variables.php");

if (!@mysql_connect($_CONFIG['DATABASE']['INPUT']['host'], $_CONFIG['DATABASE']['INPUT']['readonly']['user'],$_CONFIG['DATABASE']['INPUT']['readonly']['pass']) || !@mysql_select_db($_CONFIG['DATABASE']['INPUT']['name'])){
	echo 'no database connection';
	exit;
}
//mysql_query("SET character set utf8"); <= do not use it!

$q='';

for($i=ord('a');$i<=ord('z');$i++){
	$source=_get('www.geolang.com', '80', '/iso639-6/sortAlpha4.asp', array('selectA4letter'=>chr($i),'viewAlpha4'=>'View'));

	$table=strstr($source,'<strong>Language Reference Name</strong>');
	preg_match_all('/<div align="left">(.*)<\/div>/msU',$table,$parsed);
	$parsed=$parsed[1];

	$a=count($parsed);
	for($j=0;$j<$a;$j+=3){
		$q.="\n ('".
			mysql_real_escape_string($parsed[$j])  ."','".
			mysql_real_escape_string($parsed[$j+1])."','".
			mysql_real_escape_string($parsed[$j+2])."'),";
	}
}
$q="\n INSERT IGNORE INTO {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_languages (`iso639-6`,`parent_iso639-6`,name) VALUES"
  .substr($q,0,-1)
  ."\n ON DUPLICATE KEY UPDATE name = VALUES(name), `parent_iso639-6`=VALUES(`parent_iso639-6`)"; 
 
 
file_put_contents('logs/'.date('d.m.Y_H.i').'_azquery.sql',$q);

$res = mysql_query($q)
 or logerr();
 
 function logerr($val=''){
	$errf='logs/err.log';
	$e='';
	
	if(file_exists($errf)){
		$e=file_get_contents($errf);
	}
	if($val==''){
		$err=date('d.m.Y H:i').": Error: ". mysql_error(). "(". mysql_errno().")\n";
	}else{
		$err=date('d.m.Y H:i').": Successfully updated.\n";
	}
	file_put_contents($errf,$e.$err); 
	exit;
 }

 function _get($host,$port='80',$path='/',$data='') { 
	
	$d='';
	$str='';
	if(!empty($data)){
		foreach($data AS $k => $v){
			$str .= urlencode($k).'='.urlencode($v).'&';
		}
		$str = substr($str,0,-1); 
	}
	
	$fp = fsockopen($host,$port,$errno,$errstr,$timeout=30); 
	if($fp){
		fputs($fp, "POST $path HTTP/1.1\r\n"); 
		fputs($fp, "Host: $host\r\n"); 
		fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n"); 
		fputs($fp, "Content-length: ".strlen($str)."\r\n"); 
		fputs($fp, "Connection: close\r\n\r\n"); 
		fputs($fp, $str."\r\n\r\n"); 
		
		while(!feof($fp)){
			$d .= fgets($fp,4096);
		}
		fclose($fp);
	}
	return $d;
}

?>