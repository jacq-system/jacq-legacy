<?php

// Todo, 3.8.2011!
// ghomolka
require("variables.php");

/** @var mysqli $dbLink */
$dbLink = new mysqli($_CONFIG['DATABASE']['INPUT']['host'],
                     $_CONFIG['DATABASE']['INPUT']['readonly']['user'],
                     $_CONFIG['DATABASE']['INPUT']['readonly']['pass'],
                     $_CONFIG['DATABASE']['INPUT']['name']);
if ($dbLink->connect_errno) {
    echo 'no database connection';
	exit();
}
$dbLink->set_charset('utf8');



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

function logerr($val=''){
	$errf='logs/err.log';
	$e=file_exists($errf)?file_get_contents($errf):'';
	$err=date('d.m.Y H:i').": {$val}\n";
	file_put_contents($errf,$err.$e);
	exit;
 }

?>