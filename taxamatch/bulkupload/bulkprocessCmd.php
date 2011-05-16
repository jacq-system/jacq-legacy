#!/usr/bin/php -q
<?php
require_once('inc/jsonRPCClient.php');
require_once('inc/variables.php');

ini_set("max_execution_time", "7200");

ob_start();
mysql_connect($options['dbhost'], $options['dbuser'], $options['dbpass']) or die("Database not available!");
mysql_select_db($options['dbname']) or die ("Access denied!");
mysql_query("SET character set utf8");
$error = '';

$result = @mysql_query("SELECT jobID FROM tbljobs WHERE start IS NOT NULL AND finish IS NULL");
if (mysql_num_rows($result) > 0) die();

$result = mysql_query("SELECT scheduleID, jobID FROM tblschedule ORDER BY timestamp LIMIT 1");
if (mysql_num_rows($result) == 0) die();

$row = mysql_fetch_array($result);
$scheduleID = $row['scheduleID'];
$jobID      = $row['jobID'];

mysql_query("UPDATE tbljobs SET start = NOW() WHERE jobID = '$jobID'");

$service = new jsonRPCClient($options['serviceTaxamatch']);

$result = mysql_query("SELECT db FROM tbljobs WHERE jobID = '$jobID'");
$row = mysql_fetch_array($result);
$database = $row['db'];

$result = mysql_query("SELECT queryID, query FROM tblqueries WHERE jobID = '$jobID' AND result IS NULL ORDER BY lineNr");
while ($row = mysql_fetch_array($result)) {
    try {
        if ($database == 'col') {
            $matches = $service->getMatchesCol($row['query']);
        } elseif ($database == 'fe') {
            $matches = $service->getMatchesFaunaeuropea($row['query']);
        } elseif ($database == 'vhs') {     // BP 07.2010: synonyms!
            $matches = $service->getMatchesWithSynonyms($row['query']);
        } else {
            $matches = $service->getMatches($row['query']);
        }
        $error = $matches['error'];
        unset($matches['error']);
    }
    catch (Exception $e) {
        $error =  $e;
    }
    if ($error) break;

    @mysql_query("UPDATE tblqueries SET
                   result = '" . mysql_real_escape_string(serialize($matches)) . "'
                  WHERE queryID = '" . $row['queryID'] . "'");
}

$error .= "\n" . ob_get_clean();

@mysql_query("UPDATE tbljobs SET finish = NOW(), errors = '" . mysql_real_escape_string(trim($error)) . "' WHERE jobID = '$jobID'");
@mysql_query("DELETE FROM tblschedule WHERE scheduleID = '$scheduleID'");

exec("./bulkprocessCmd.php > /dev/null 2>&1 &");   // to start the next query (if any)