<?php 

// Todo, 3.8.2011!
// ghomolka
if (!@mysql_connect('localhost', 'uder','pass') || !@mysql_select_db('names')){
	echo 'no database connection';
	exit;
}
//mysql_query("SET character set utf8"); <= no!

$results=array();
$q="INSERT IGNORE INTO tbl_name_languages (iso639_6,parent_id,name) VALUES"; 
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
$q = substr($q,0,-1);
file_put_contents('a_z_query.txt',$q);

$res = mysql_query($q)
 or die("A MySQL error has occurred.<br />Your Query: " . $your_query . "<br /> Error: (" . mysql_errno() . ") " . mysql_error());

 
 function _get($host,$port='80',$path='/',$data='') { 
	
	$d='';$str='';
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