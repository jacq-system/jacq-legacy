#!/usr/bin/php -qC
<?php
require_once './inc/variables.php';

ini_set("max_execution_time","3600");

mysql_connect($_CONFIG['DATABASE']['INPUT']['host'], $_CONFIG['DATABASE']['INPUT']['readonly']['user'],$_CONFIG['DATABASE']['INPUT']['readonly']['pass']) or die("Database not available!");
mysql_select_db($_CONFIG['DATABASE']['INPUT']['name']) or die ("Access denied!");
mysql_query("SET character set utf8");

function db_query ($sql)
{
    $result = mysql_query($sql);
    if (!$result) {
        echo $sql . "\n";
        echo mysql_error() . "\n";
    }
    return $result;
}
function quoteString ($text)
{
    if (strlen($text) > 0) {
        return "'" . mysql_escape_string($text) . "'";
    } else {
        return "NULL";
    }
}

/**
 * wrapper for the depricated mysql interface
 *
 * @param resource $result
 */
function mysql_fetch_all ($result)
{
    $rows = array();
    while ($row = mysql_fetch_assoc($result)) {
        $rows[] = $row;
    }

    return $rows;
}

/**
 * do a mysql query
 *
 * @global mysqli $dbLink link to database
 * @param string $sql query string
 * @return mixed mysqli_result or false if error
 */
//function db_query($sql)
//{
//  global $dbLink;
//
//  $res = $dbLink->query($sql);
//
//  if(!$res){
//    echo $sql . "\n"
//       . $dbLink->errno . ": " . $dbLink->error . "\n";
//  }
//
//  return $res;
//}


/**
 * encase text with quotes or return NULL if string is empty
 *
 * @global mysqli $dbLink link to database
 * @param string $text text to quote
 * @return string result
 */
//function quoteString($text)
//{
//    global $dbLink;
//
//    if (strlen($text) > 0) {
//        return "'" . $dbLink->real_escape_string($text) . "'";
//    } else {
//        return "NULL";
//    }
//}

/**
 * wrapper functions for depricated mysql-interface
 */
//function mysql_num_rows($result)
//{
//    return $result->num_rows;
//}
//
//function mysql_fetch_array($result)
//{
//    return $result->fetch_array();
//}
//
//function mysql_fetch_all($result)
//{
//    return $result->fetch_all();
//}

require_once 'inc/stableIdentifierFunctions.php';

ob_start();

$result_start = db_query("SELECT DATE(`starttime`) AS startdate FROM statusSpecimensStblid ORDER BY starttime DESC LIMIT 1");
$row_start = mysql_fetch_array($result_start);
$startdate = $row_start['startdate'];

db_query("INSERT INTO statusSpecimensStblid SET starttime = NOW()");
$startID = mysql_insert_id();

$count_changed = $count_new = 0;
$count = array();
$result_specimen = db_query("SELECT s.`specimen_ID`, mc.`collectionID`, mc.`source_id`, s.`aktualdatum`
                             FROM tbl_specimens s, tbl_management_collections mc
                             WHERE s.`collectionID` = mc.`collectionID`
                              AND TIMESTAMPDIFF(DAY, '$startdate', s.`aktualdatum`) >= 0");
while ($row_specimen = mysql_fetch_array($result_specimen)) {
    $stblid = makeStableIdentifier($row_specimen['source_id'], array('specimen_ID' => $row_specimen['specimen_ID']), $row_specimen['collectionID']);
    if ($stblid) {
        $result_test_spcId  = db_query("SELECT id FROM tbl_specimens_stblid WHERE specimen_ID = " . $row_specimen['specimen_ID']);
        $result_test_stblId = db_query("SELECT id FROM tbl_specimens_stblid WHERE stableIdentifier = '$stblid'");
        if (mysql_num_rows($result_test_stblId) == 0) {
            db_query("INSERT INTO tbl_specimens_stblid SET specimen_ID = '" . $row_specimen['specimen_ID'] . "', stableIdentifier = '$stblid'");
            if (mysql_num_rows($result_test_spcId) > 0) {
                $count_changed++;
                if (isset($count[$row_specimen['source_id']]['changed'])) {
                    $count[$row_specimen['source_id']]['changed']++;
                } else {
                    $count[$row_specimen['source_id']]['changed'] = 1;
                }
            } else {
                $count_new++;
                if (isset($count[$row_specimen['source_id']]['new'])) {
                    $count[$row_specimen['source_id']]['new']++;
                } else {
                    $count[$row_specimen['source_id']]['new'] = 1;
                }
            }
        }
    }
}
$details = array();
ksort($count);
foreach ($count as $key => $value) {
    $result_source = db_query("SELECT source_code FROM meta WHERE source_id = $key");
    $row_source = mysql_fetch_array($result_source);
    $details[$key] = array('source'  => $row_source['source_code'],
                           'new'     => (isset($value['new'])) ? $value['new'] : 0,
                           'changed' => (isset($value['changed'])) ? $value['changed'] : 0);
}
db_query("UPDATE statusSpecimensStblid SET
            new = $count_new,
            changed = $count_changed,
            details = '" . mysql_real_escape_string(json_encode($details)) . "',
            stoptime = NOW(),
            output = '" . mysql_real_escape_string(ob_get_contents()) . "'
           WHERE id = $startID");

ob_end_flush();