<?php


require("../inc/connect.php");

$q='';

for($i=ord('a');$i<=ord('z');$i++){
	$source=_get('www.geolang.com', '80', '/iso639-6/sortAlpha4.asp', array('selectA4letter'=>chr($i),'viewAlpha4'=>'View'));

	$table=strstr($source,'<strong>Language Reference Name</strong>');
	preg_match_all('/<div align="left">(.*)<\/div>/msU',$table,$parsed);
	$parsed=$parsed[1];

	$a=count($parsed);
	for($j=0;$j<$a;$j+=3){
		$q.="\n ('".
			$dbLink->real_escape_string($parsed[$j])  ."','".
			$dbLink->real_escape_string($parsed[$j+1])."','".
			$dbLink->real_escape_string($parsed[$j+2])."'),";
	}
}
$q="\n INSERT IGNORE INTO {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_languages (`iso639-6`,`parent_iso639-6`,name) VALUES"
  .substr($q,0,-1)
  ."\n ON DUPLICATE KEY UPDATE name = VALUES(name), `parent_iso639-6`=VALUES(`parent_iso639-6`)";


file_put_contents('logs/'.date('d.m.Y_H.i').'_azquery.sql',$q);

$res = $dbLink->query($q) or logerr("Error: ". $dbLink->error. "(". $dbLink->errno.")");

logerr('Successfully updated.');
