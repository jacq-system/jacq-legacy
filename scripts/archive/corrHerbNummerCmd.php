#!/usr/bin/php -q
<?php
$host = "localhost";      // hostname
$user = "hdb_".$argv[1];  // username
$pass = $argv[2];         // password
$db   = "herbarinput";    // database

ini_set("max_execution_time","3600");

mysql_connect($host,$user,$pass) or die("Database not available!");
mysql_select_db($db) or die ("Access denied!");
mysql_query("SET character set utf8");

function db_query($sql) {
  $result = @mysql_query($sql);
  if (!$result) {
    echo $sql."\n";
    echo mysql_error()."\n";
  }
  return $result;
}
function quoteString($text) {

  if (strlen($text)>0)
    return "'".mysql_escape_string($text)."'";
  else
    return "NULL";
}


$sql = "SELECT specimen_ID, HerbNummer
        FROM tbl_specimens
        WHERE SUBSTRING(HerbNummer,1,1)='1'
         AND INSTR(HerbNummer,'-')!=0";
$result = db_query($sql);
while ($row=mysql_fetch_array($result)) {
  if (($pos=strpos($row['HerbNummer'],"-"))!==false) {
    for ($end=$pos+1;$end<strlen($row['HerbNummer']);$end++)
      if (!ctype_digit(substr($row['HerbNummer'],$end,1))) break;
    if (($end-$pos)!=8) {
      $newnum = substr($row['HerbNummer'],0,$pos)."-".sprintf("%07d",substr($row['HerbNummer'],$pos+1,$end-1)).substr($row['HerbNummer'],$end);
      $sql = "UPDATE tbl_specimens
              SET HerbNummer='$newnum'
              WHERE specimen_ID='".$row['specimen_ID']."'";
      db_query($sql);
      //echo sprintf("%10s - %20s - %20s\n",$row['specimen_ID'],$row['HerbNummer'],$newnum);
    }
  }
}
?>