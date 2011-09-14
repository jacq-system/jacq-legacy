<?php
require_once('jsonRPCServer.php');
require_once('../inc/variables.php');
require_once('../inc/functions.php');


error_reporting(E_ALL);
class internMDLDService {

	function getSQLResults($sql){
		$sc=false;
		$res=array();
		if(!is_array($sql)){
			$sql=array($sql);
			$sc=true;
		}
		foreach($sql as $k=>$v){
			$resdb=mysql_query($v);
			if($resdb){
				while($row=mysql_fetch_assoc($resdb)){
					$res[$k][]=$row;
				}
			}
		}
		if($sc){
			return $res[0];
		}
		return $res;
	}
	
	
}
/*
// log the request
if (@mysql_connect($options['log']['dbhost'], $options['log']['dbuser'], $options['log']['dbpass']) && @mysql_select_db($options['log']['dbname'])) {
	@mysql_query("SET character set utf8");
	@mysql_query("INSERT INTO tblrpclog SET
				   http_header = '" . mysql_real_escape_string(var_export(apache_request_headers(), true)) . "',
				   http_post_data = '" . mysql_real_escape_string(file_get_contents('php://input')) . "',
				   remote_host = '" . mysql_real_escape_string($_SERVER['REMOTE_ADDR']) . "'");
	@mysql_close();
}*/


/**
 * implementation of the json rpc functionality
 */
$service = new internMDLDService();
//$ret = jsonRPCServer::handle($service,$_OPTIONS['internMDLDService']['password']);
$ret = jsonRPCServer::handle($service,$_OPTIONS['internMDLDService']['password']);
if (!$ret) {
	echo "no request\n"
	   . "REQUEST_METHOD should be 'POST' but was: '" . $_SERVER['REQUEST_METHOD'] . "'\n"
	   . "CONTENT_TYPE should be 'application/json' but was: '" . $_SERVER['CONTENT_TYPE'] . "'";
}