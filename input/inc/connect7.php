<?php
require_once('variables.php');
require_once('tools.php');
require_once('class.natID.php');

if (!isset($_SESSION['username']) || !isset($_SESSION['password'])) {
    if (substr(getcwd(), -5) == "/ajax") {
        die();
    } else {
        header("Location: login.php");
        exit();
    }
}

/** @var mysqli $dbLink */
$dbLink = new mysqli($_CONFIG['DATABASE']['INPUT']['host'],
                     $_SESSION['username'],
                     $_SESSION['password'],
                     $_CONFIG['DATABASE']['INPUT']['name']);
if ($dbLink->connect_errno) {
    header("Location: login.php");
	exit();
}
$dbLink->set_charset('utf8');

function no_magic()   // PHP >= 4.1
{
    if (get_magic_quotes_gpc()) {
        foreach($_GET as $k => $v)  $_GET["$k"] = stripslashes($v);
        foreach($_POST as $k => $v) $_POST["$k"] = stripslashes($v);
    }
}

// mode=1=> From Post to escaped mysql
// $mode=2 => for formulars from escaped mysql
// $mode=3 => for formulars from not escaped mysql
// $mode=4 => from mysql to mysql
function doQuotes(&$obj,$mode){
    global $dbLink;

	if(!is_array($obj))$obj=array($obj);
	foreach($obj as &$val){
		if(is_array($val)){
				doQuotes($val,$mode);
		}else if(is_scalar($val)){
			if($mode==1){
				$val=htmlspecialchars_decode($val);
				$val=$dbLink->real_escape_string($val);
			}else if($mode==2){
				$val=htmlspecialchars($val, ENT_COMPAT, "UTF-8",1);
				$val=stripslashes($val);
			}else if($mode==3){
				$val=htmlspecialchars($val, ENT_COMPAT, "UTF-8",1);
			}else if($mode==4){
				$val=$dbLink->real_escape_string($val);
			}
		}
	}
}

/**
 * @param $sql
 * @param bool|FALSE $debug
 * @return resource
 *
 */
function db_query($sql,$debug=false){
  global $_OPTIONS, $dbLink;

  if($debug || $_OPTIONS['debug']==1){
    $debug=true;
  }

  $res = $dbLink->query($sql);

  if(!$res && $debug){
    echo $sql;
    echo $dbLink->errno . ": " . $dbLink->error . "<br>\n";
  }

  return $res;
}


function quoteString($text)
{
    global $dbLink;

    if (strlen($text) > 0) {
        return "'" . $dbLink->real_escape_string($text) . "'";
    } else {
        return "NULL";
    }
}

/**
 * checks an INT-value and returns NULL if zero
 *
 * @param integer $value
 * @return integer or NULL
 */
function makeInt($value)
{
    if (intval($value)) {
        return "'" . intval($value) . "'";
    } else {
        return "NULL";
    }
}

function checkRight($right)
{
    if (substr($right, 0, 7) == 'unlock_') {
        $sql = "SELECT `table`
                FROM herbarinput_log.tbl_herbardb_unlock
                WHERE `table` = ".quoteString(substr($right, 7))."
                 AND groupID = '" . intval($_SESSION['gid']) . "'";
        $result = db_query($sql);
        if ($result->num_rows > 0) {
            return true;
        } else {
            return false;
        }
    } else {
        $sql = "SELECT *
                FROM herbarinput_log.tbl_herbardb_users, herbarinput_log.tbl_herbardb_groups
                WHERE herbarinput_log.tbl_herbardb_users.groupID = herbarinput_log.tbl_herbardb_groups.groupID
                 AND userID = '" . intval($_SESSION['uid']) . "'";
        $result = db_query($sql);
        $row = $result->fetch_array();
        if (isset($row[$right]) && $row[$right]) {
            return true;
        } else {
            return false;
        }
    }
}

function isLocked($table, $id){
    global $dbLink;

	$lock = "locked";
	if (is_numeric($id)) {
        $PID = "";
        $result = $dbLink->query("SHOW INDEX FROM $table");
        while ($row = $result->fetch_array()) {
            if ($row['Key_name']=='PRIMARY') {
                $PID = $row['Column_name'];
                break;
            }
        }
        $sql = "SELECT $lock FROM $table WHERE $PID = '" . intval($id) . "'";
        $row = $dbLink->query($sql)->fetch_array();
        if ($row[$lock]) {
            return true;
        }
    } else if (is_object($id)){
		if (!($where = $id->getWhere())) {
            return false;
        }
		$res = $dbLink->query("SELECT {$lock} FROM {$table} WHERE {$where}");

		if($res && $row = $res->fetch_array($res)){
			if(isset($row[$lock]) && $row[$lock]){
				return true;
			}
		}
    }

    return false;
}

/**
 * deprecated
 * get the IP of the appropriate picture server
 *
 * @param integer $specimenID Specimen ID
 * @return string IP of picture server
 */
function getPictureServerIP($specimenID){
	global $_OPTIONS;

    return $_OPTIONS['HERBARIMAGEURL'];
}

/**
 * format the unit-ID (HerbNummer) according to tbl_labels_numbering
 *
 * @param integer $specimenID Specimen ID
 * @return string formatted unit-ID
 */
function formatUnitID($specimenID)
{
    $sql = "SELECT s.HerbNummer, s.specimen_ID, s.collectionID, herbarinput.meta.source_code, herbarinput.meta.source_id
            FROM tbl_specimens s, tbl_management_collections mc, herbarinput.meta
            WHERE s.collectionID = mc.collectionID
             AND mc.source_id = herbarinput.meta.source_id
             AND specimen_ID='" . intval($specimenID) . "'";
    $resSpecimen = db_query($sql);
    $rowSpecimen = $resSpecimen->fetch_array();

    $unitID = $rowSpecimen['source_code'];
    if ($rowSpecimen['HerbNummer']) {
        $sql = "SELECT digits, replace_char
                FROM tbl_labels_numbering
                WHERE collectionID_fk = '" . $rowSpecimen['collectionID'] . "'";            // first check on collectionID
        $result = db_query($sql);
        if ($result->num_rows > 0) {
            $row1 = $result->fetch_array($result);
            $found = false;
            if ($result->num_rows > 1) {
                $sql = "SELECT digits, replace_char
                        FROM tbl_labels_numbering
                        WHERE replace_char IS NOT NULL
                         AND collectionID_fk = '" . $rowSpecimen['collectionID'] . "'";     // set replace char wins
                $result = db_query($sql);
                while ($row2 = $result->fetch_array()) {
                    if (strpos($rowSpecimen['HerbNummer'], $row2['replace_char']) !== false) {
                        $digits  = $row2['digits'];
                        $replace = $row2['replace_char'];
                        $found = true;
                        break;
                    }
                }
            }
            if (!$found) {
                $digits  = $row1['digits'];
                $replace = $row1['replace_char'];
            }
        } else {
            $sql = "SELECT digits, replace_char
                    FROM tbl_labels_numbering
                    WHERE collectionID_fk IS NULL
                     AND sourceID_fk = '" . $rowSpecimen['source_id'] . "'";               // second check on sourceID
            $result = db_query($sql);
            if ($result->num_rows > 0) {
                $row1 = $result->fetch_array($result);
                $found = false;
                if ($result->num_rows > 1) {
                    $sql = "SELECT digits, replace_char
                            FROM tbl_labels_numbering
                            WHERE replace_char IS NOT NULL
                             AND collectionID_fk IS NULL
                             AND sourceID_fk = '" . $rowSpecimen['source_id'] . "'";        // set replace char wins
                    $result = db_query($sql);
                    while ($row2 = $result->fetch_array()) {
                        if (strpos($rowSpecimen['HerbNummer'], $row2['replace_char']) !== false) {
                            $digits  = $row2['digits'];
                            $replace = $row2['replace_char'];
                            $found = true;
                            break;
                        }
                    }
                }
                if (!$found) {
                    $digits  = $row1['digits'];
                    $replace = $row1['replace_char'];
                }
            } else {
                $digits  = 7;                                                               // fallback
                $replace = '';
            }
        }

        if ($replace) {
            $parts = explode($replace, $rowSpecimen['HerbNummer'], 2);
            $unitID .= $parts[0] . sprintf("%s", $parts[1]);
        } else {
            $unitID .= sprintf("%s", $rowSpecimen['HerbNummer']);
        }
    } else {
        $unitID .= intval($specimenID);
    }

    return $unitID;
}

/**
 * format the unit-ID (HerbNummer) according to tbl_labels_numbering for a given source-ID
 *
 * @param integer $sourceID source ID
 * @param integer $number HerbNummer to format
 * @return string formatted unit-ID
 */
function formatPreUnitID($sourceID, $number)
{
    $sql = "SELECT source_code
            FROM herbarinput.meta
            WHERE source_id = '" . intval($sourceID) . "'";
    $res = db_query($sql);
    $row = $res->fetch_array();

    $unitID = $row['source_code'];

    $sql = "SELECT digits
            FROM tbl_labels_numbering
            WHERE replace_char IS NULL
             AND collectionID_fk IS NULL
             AND sourceID_fk = '" . intval($sourceID) . "'";
    $result = db_query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_array();
        $digits = $row['digits'];
    } else {
        $digits = 7;
    }

    $unitID .= sprintf("%0{$digits}d", $number);

    return $unitID;
}

/**
 * checks if the variable of a given type is set and if it is echo it
 *
 * @param string $name name of variable
 * @param string $type type of variable (GET, POST, SESSION)
 */
function echoSpecial($name, $type)
{
    switch ($type) {
        case 'GET':     echo (isset($_GET[$name]))     ? htmlspecialchars($_GET[$name])     : ''; break;
        case 'POST':    echo (isset($_POST[$name]))    ? htmlspecialchars($_POST[$name])    : ''; break;
        case 'SESSION': echo (isset($_SESSION[$name])) ? htmlspecialchars($_SESSION[$name]) : ''; break;
    }
}
