<?php
$host = "localhost";    // hostname
$db   = "herbarinput";  // database
$user = "";             // username
$pass = "";             // password

!@mysql_connect($host,$user,$pass);
if (!@mysql_select_db($db)) {
  echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">\n".
       "<html>\n".
       "<head><titel>Sorry, no connection ...</title></head>\n".
       "<body><p>Sorry, no connection to database ...</p></body>\n".
       "</html>\n";
  exit();
}
//mysql_query("SET character_set_results='utf8'");
mysql_query("SET character set utf8");

function no_magic() {  // PHP >= 4.1
  if (get_magic_quotes_gpc()) {
    foreach($_GET as $k=>$v)  $_GET["$k"] = stripslashes($v);
    foreach($_POST as $k=>$v) $_POST["$k"] = stripslashes($v);
  }
}

function db_query($sql) {
  $result = @mysql_query($sql);
  if (!$result) {
    echo $sql."<br>\n";
    echo mysql_error()."<br>\n";
  }
  return $result;
}

function extractID($text) {

  $pos1 = strpos($text,"<");
  $pos2 = strpos($text,">");
  if ($pos1!==false && $pos2!==false)
    return "'".intval(substr($text,$pos1+1,$pos2-$pos1-1))."'";
  else
    return "NULL";
}

function quoteString($text) {

  if (strlen($text)>0)
    return "'".mysql_escape_string($text)."'";
  else
    return "NULL";
}

function replaceNewline($text) {

  return strtr(str_replace("\r\n","\n",$text),"\r\n","  ");  //replaces \r\n with \n and then \r or \n with <space>
}