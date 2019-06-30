#!/usr/bin/php -q
<?php
require_once('inc/jsonRPCClient.php');
require_once('inc/variables.php');

ini_set("max_execution_time", "7200");

//ob_start();
$dbLink = new mysqli($options['dbhost'], $options['dbuser'], $options['dbpass'], $options['dbname']) or die ("Access denied!");
$dbLink->set_charset('utf8');
$error = '';

$result = $dbLink->query("SELECT jobID FROM tbljobs WHERE start IS NOT NULL AND finish IS NULL");
if ($result->num_rows > 0) {    // one bulkprocess ist still running
    die("h1");
}

$result = $dbLink->query("SELECT scheduleID, jobID FROM tblschedule ORDER BY timestamp LIMIT 1");
if ($result->num_rows == 0) {   // nothing is scheduled, so nothing to do
    die("h2");
}

$row = $result->fetch_array();
$scheduleID = $row['scheduleID'];
$jobID      = $row['jobID'];

$dbLink->query("UPDATE tbljobs SET start = NOW() WHERE jobID = '$jobID'");

$result = $dbLink->query("SELECT db FROM tbljobs WHERE jobID = '$jobID'");
$row = $result->fetch_array();

if (substr($row['db'],0,2)=='s_') {
    $database     = substr($row['db'],2);
    $withSynonyms = true;
} else {
    $database     = $row['db'];
    $withSynonyms = false;
}



 	//db	char(3)

$result = $dbLink->query("SELECT queryID, query FROM tblqueries WHERE jobID = '$jobID' AND result IS NULL ORDER BY lineNr");
while ($row = $result->fetch_array()) {
    $matches = getMatches($database, $row['query'], false, $withSynonyms, false);

    if ($matches['failure']) {
        $error =  $matches['failure'];
        break;
    }
    $matches = serialize($matches['matches']);

    $dbLink->query("UPDATE tblqueries SET result = '" . $dbLink->real_escape_string($matches) . "' WHERE queryID = '" . $row['queryID'] . "' ");
}


function getMatches($database, $searchtext, $useNearMatch=false, $showSynonyms=false, $debug=false){
    global $options;

    $start = microtime(true);

    $service = new jsonRPCClient($options['serviceTaxamatch'], $debug);
    $failure=false;
    try {
        $matchesNearMatch = array();
        $matches = array();

        $databases = $service->getDatabases();

        if (isset($databases[$database])) {
            $matches = $service->getMatchesService(
                    $database,
                    $searchtext,
                    array(
                        'showSyn' => $showSynonyms,
                        'NearMatch' => false
                    )
            );

            if ($useNearMatch) {
                $matchesNearMatch = $service->getMatchesService(
                        $database,
                        $searchtext,
                        array(
                            'showSyn' => false,
                            'NearMatch' => true
                        )
                );
            }
        }
    } catch (Exception $e) {
        $failure =  "Error " . nl2br($e);
    }
    $stop = microtime(true);
    return array('start'=>$start, 'stop'=>$stop, 'matches'=>$matches, 'matchesNearMatch'=>$matchesNearMatch, 'failure'=>$failure);
}

$error .= "\n" . ob_get_clean();

$dbLink->query("UPDATE tbljobs SET finish = NOW(), errors = '" . $dbLink->real_escape_string(trim($error)) . "' WHERE jobID = '$jobID'");
$dbLink->query("DELETE FROM tblschedule WHERE scheduleID = '$scheduleID'");

exec("./bulkprocessCmd.php > /dev/null 2>&1 &");   // to start the next query (if any)