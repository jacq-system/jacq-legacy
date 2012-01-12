#!/usr/bin/php -q
<?php
require_once('inc/jsonRPCClient.php');
require_once('inc/variables.php');

ini_set("max_execution_time", "7200");

//ob_start();
mysql_connect($options['dbhost'], $options['dbuser'], $options['dbpass']) or die("Database not available!");
mysql_select_db($options['dbname']) or die ("Access denied!");
mysql_query("SET character set utf8");
$error = '';

$result = mysql_query("SELECT jobID FROM tbljobs WHERE start IS NOT NULL AND finish IS NULL");

if (mysql_num_rows($result) > 0) die("h1");

$result = mysql_query("SELECT scheduleID, jobID FROM tblschedule ORDER BY timestamp LIMIT 1");
	echo mysql_error();
if (mysql_num_rows($result) == 0) die("h2");

$row = mysql_fetch_array($result);
$scheduleID = $row['scheduleID'];
$jobID      = $row['jobID'];


mysql_query("UPDATE tbljobs SET start = NOW() WHERE jobID = '$jobID'");


$result = mysql_query("SELECT db FROM tbljobs WHERE jobID = '$jobID'");
$row = mysql_fetch_array($result);


if(substr($row['db'],0,2)=='s_'){
	$database=substr($row['db'],2);
	$withSynonyms=true;
}else{
	$database=$row['db'];
	$withSynonyms=false;
}



 	//db	char(3)

$result = mysql_query("SELECT queryID, query FROM tblqueries WHERE jobID = '$jobID' AND result IS NULL ORDER BY lineNr");
while ($row = mysql_fetch_array($result)) {
	
	

	$matches=getMatches($database, $row['query'], false, $withSynonyms,false);
	
	if($matches['failure']){
		$error =  $matches['failure'];
		break;
	}
	$matches=serialize($matches['matches']);

    @mysql_query("UPDATE tblqueries SET result = '" . mysql_real_escape_string($matches) . "' 
	WHERE queryID = '" . $row['queryID'] . "' ");

}


function getMatches($database, $searchtext, $useNearMatch=false,$showSynonyms=false,$debug=false){
	global $options;
	
	$start = microtime(true);
	 
	$url = $options['serviceTaxamatch'] . "json_rpc_taxamatchMdld.php";
	$service = new jsonRPCClient($url,$debug);
	$failure=false;
	try {
		$matchesNearMatch = array();
		$matches=array();
		
		if ($database == 'vienna') {
			$matches = $service->getMatchesService('vienna',$searchtext,array('showSyn'=>$showSynonyms,'NearMatch'=>false));
			
			if ($useNearMatch) {
				$matchesNearMatch=$service->getMatchesService('vienna',$searchtext,array('showSyn'=>false,'NearMatch'=>true));
			}
			
		}else if ($database == 'vienna_common') {
			
			$matches = $service->getMatchesService('vienna_common',$searchtext,array('showSyn'=>$showSynonyms,'NearMatch'=>false));
			
			if ($useNearMatch) {
				$matchesNearMatch=$service->getMatchesService('vienna_common',$searchtext,array('showSyn'=>false,'NearMatch'=>true));
			}
			
		} else if ($database == 'col2010ac') {
			
			$matches = $service->getMatchesService('col2010ac',$searchtext,array('NearMatch'=>false));
			
			if ($useNearMatch) {
				$matchesNearMatch=$service->getMatchesService('col2010ac',$searchtext,array('NearMatch'=>true));
			}

		} else if ($database == 'col2011ac') {
			
			$matches = $service->getMatchesService('col2011ac',$searchtext,array('NearMatch'=>false));
			
			if ($useNearMatch) {
				$matchesNearMatch=$service->getMatchesService('col2011ac',$searchtext,array('NearMatch'=>true));
			}
			

		} else if ($database == 'fe') {
			
			$matches = $service->getMatchesService('fe',$searchtext,array('NearMatch'=>false));
			
			if ($useNearMatch) {
				$matchesNearMatch=$service->getMatchesService('fe',$searchtext,array('NearMatch'=>true));
			}
		
		} else if ($database == 'fev2') {
			
			$matches = $service->getMatchesService('fev2',$searchtext,array('NearMatch'=>false));
			
			if ($useNearMatch) {
				$matchesNearMatch=$service->getMatchesService('fev2',$searchtext,array('NearMatch'=>true));
			}

		} else{
			
			$matches = $service->getMatchesService($database,$searchtext,array('showSyn'=>false,'NearMatch'=>false));
			
			if ($useNearMatch) {
				$matchesNearMatch=$service->getMatchesService($database,$searchtext,array('showSyn'=>false,'NearMatch'=>true));
			}
		}
		
	}catch (Exception $e) {
		$failure =  "Fehler " . nl2br($e);
	}
	$stop = microtime(true);
	return array('start'=>$start,'stop'=>$stop,'matches'=>$matches,'matchesNearMatch'=>$matchesNearMatch,'failure'=>$failure);

}

$error .= "\n" . ob_get_clean();

@mysql_query("UPDATE tbljobs SET finish = NOW(), errors = '" . mysql_real_escape_string(trim($error)) . "' WHERE jobID = '$jobID'");
@mysql_query("DELETE FROM tblschedule WHERE scheduleID = '$scheduleID'");

exec("./bulkprocessCmd.php > /dev/null 2>&1 &");   // to start the next query (if any)