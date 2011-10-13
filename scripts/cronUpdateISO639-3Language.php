<?php 

// Todo, 3.8.2011!
// ghomolka
require("inc/connect.php");

mysql_query("SET character set utf8");


//
// http://vocabularies.gbif.org/download/lang?view=1
// dc:modified	dc:created	dc:title	dc:description	dc:URI	dc:identifier	gbif-core-language	iso-639-level	iso-639-1-code	iso-639-2 bibliographic code
// 0            1           2           3               4       5               6                   7               8               9          
// iso 639-2 terminologic code	iso 639-3 code	language scope	language type	dc:issued	dc:source
// 10                           11              12              13              14          15
//
$Url = "http://vocabularies.gbif.org/download/lang?view=1";
//$Url = "./docs/lang-concepts.tsv";

$q='';
$handle = fopen($Url,"r");
$desc=fgetcsv ($handle, 1000, '	');
while ( ($row = fgetcsv ($handle, 1000, '	')) !== FALSE ) {
	$q.="\n ('".mysql_real_escape_string($row[11])  ."','".mysql_real_escape_string($row[2])."'),";
}
fclose ($handle);

$q="\n INSERT IGNORE INTO {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_languages (`iso639-3`,name) VALUES"
  .substr($q,0,-1)
  ."\n ON DUPLICATE KEY UPDATE name = VALUES(name)"; 
 


file_put_contents('logs/'.date('d.m.Y_H.i').'_azquery.sql',$q);

$res = mysql_query($q)
 or logerr("Error: ". mysql_error(). "(". mysql_errno().")");
 
logerr('Successfully updated.');

?>