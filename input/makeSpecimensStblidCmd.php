#!/usr/bin/php -qC
<?php
require_once './inc/variables.php';

ini_set("max_execution_time","3600");

mysql_connect($_CONFIG['DATABASE']['INPUT']['host'], $_CONFIG['DATABASE']['INPUT']['readonly']['user'],$_CONFIG['DATABASE']['INPUT']['readonly']['pass']) or die("Database not available!");
mysql_select_db($_CONFIG['DATABASE']['INPUT']['name']) or die ("Access denied!");
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

$numStblIds = 0;
$result_specimen = db_query("SELECT mc.`collectionID`, mc.`source_id`, s.`specimen_ID`
                             FROM tbl_specimens s
                              LEFT JOIN tbl_management_collections mc USING (`collectionID`)
                             WHERE s.`specimen_ID` NOT IN (SELECT `specimen_ID` FROM `tbl_specimens_stblid`)"); // fast version
while ($row_specimen = mysql_fetch_array($result_specimen)) {
    $stblid = makeStableIdentifier($row_specimen['source_id'], array('specimen_ID' => $row_specimen['specimen_ID']), $row_specimen['collectionID']);
    if ($stblid) {
        $result_test = db_query("SELECT id FROM tbl_specimens_stblid WHERE stableIdentifier = '$stblid'");
        if (mysql_num_rows($result_test) == 0) {
            db_query("INSERT INTO tbl_specimens_stblid SET specimen_ID = '" . $row_specimen['specimen_ID'] . "', stableIdentifier = '$stblid'");
            $numStblIds++;
        }
    }
}

db_query("INSERT INTO herbarinput_log.log_make_stblid SET num_created = '$numStblIds', output = '" . ob_get_contents() . "'");

ob_end_flush();