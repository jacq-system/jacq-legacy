<?php
require_once( 'variables.php' );
require_once( 'tools.php' );
require_once( 'class.natID.php' );

/** @var array $_CONFIG */

if (!isset($_SESSION['username']) || !isset($_SESSION['password'])) {
    if (substr(getcwd(), -5) == "/ajax") {
        die();
    } else {
        header("Location: login.php");
        exit();
    }
}

mysqli_report(MYSQLI_REPORT_OFF);   // since PHP 8.1 an exception would be thrown

$dbLink = new mysqli($_CONFIG['DATABASE']['INPUT']['host'],
                     $_SESSION['username'],
                     $_SESSION['password'],
                     $_CONFIG['DATABASE']['INPUT']['name']);
if ($dbLink->connect_errno) {
    header("Location: login.php");
	exit();
}
$dbLink->set_charset('utf8');

// $mode=1 => from Post to escaped mysql
// $mode=2 => for formulars from escaped mysql
// $mode=3 => for formulars from not escaped mysql
// $mode=4 => from mysql to mysql
function doQuotes(&$obj, $mode)
{
    global $dbLink;

    if (!is_array($obj)) {
        $obj = array($obj);
    }
    foreach ($obj as &$val) {
        if (is_array($val)) {
            doQuotes($val, $mode);
        } else if (is_scalar($val)) {
            if ($mode == 1) {
                $val = $dbLink->real_escape_string(htmlspecialchars_decode($val));
            } else if($mode == 2) {
            	$val = stripslashes(htmlspecialchars($val, ENT_COMPAT, "UTF-8", 1));
            } else if($mode == 3) {
            	$val = htmlspecialchars($val, ENT_COMPAT, "UTF-8",1);
            } else if($mode == 4) {
            	$val = $dbLink->real_escape_string($val);
            }
        }
    }
}

/**
 * do a mysql query
 *
 * @param $sql
 * @param bool|FALSE $debug
 * @return mysqli_result
 */
function dbi_query($sql, $debug = false)
{
    global $_OPTIONS, $dbLink;

    if($debug || $_OPTIONS['debug'] == 1) {
        $debug = true;
    }

    $res = $dbLink->query($sql);

    if(!$res) {
        // log the error in php error log
        error_log("SEVERE SQL-ERROR IN SCRIPT. SQL = $sql --- Error = " . $dbLink->errno . ": " . $dbLink->error);
        if ($debug){
            // and show it additionally, if debug is on
            echo $sql . "<br>\n";
            echo $dbLink->errno . ": " . $dbLink->error . "<br>\n";
        }
    }

    return $res;
}

/**
 * @deprecated since mysql_* functions are deprecated
 *
 * @param $sql
 * @param bool|FALSE $debug
 * @return resource
 *
 */
//function db_query($sql, $debug=false)
//{
//    global $_OPTIONS;
//
//    if($debug || $_OPTIONS['debug'] == 1) {
//        $debug=true;
//    }
//
//    $res = mysql_query($sql);
//
//    if(!$res && $debug) {
//        echo $sql;
//        echo mysql_errno() . ": " . mysql_error() . "<br>\n";
//    }
//
//    return $res;
//}


/**
 * wrapper function to mysqli_real_escape_string
 * (makes it unnecessary to use global variable $dbLink
 *
 * @global mysqli $dbLink
 * @param string $text text to escape
 * @return string escaped text
 */
function dbi_escape_string($text)
{
    global $dbLink;

    return $dbLink->real_escape_string($text);
}

/**
 * wrapper function to mysqli_insert_id
 * (makes it unnecessary to use global variable $dbLink
 *
 * @global mysqli $dbLink
 * @return mixed value of the AUTO_INCREMENT field
 */
function dbi_insert_id()
{
    global $dbLink;

    return $dbLink->insert_id;
}

/**
 * quotes a string or returns NULL if string is empty or is NULL
 *
 * @global mysqli $dbLink link to mysql-db
 * @param string $text what to quote
 * @return string quoted string or NULL
 */
function quoteString($text)
{
    global $dbLink;

    if (strlen($text) > 0) {
        if (trim($text) == "0000-00-00 00:00:00") {
            return "NULL";
        } else {
            return "'" . $dbLink->real_escape_string($text) . "'";
        }
    } else {
        return "NULL";
    }
}

/**
 * checks an INT-value and returns NULL if zero
 *
 * @param integer $value
 * @return string or NULL
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
        $result = dbi_query($sql);
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
        $row = dbi_query($sql)->fetch_array();
        if (isset($row[$right]) && $row[$right]) {
            return true;
        } else {
            return false;
        }
    }
}

function isLocked($table, $id)
{
	$lock = "locked";
	if (is_numeric($id)) {
        $PID = "";
        $result = dbi_query("SHOW INDEX FROM $table");
        while ($row = $result->fetch_array()) {
            if ($row['Key_name'] == 'PRIMARY') {
                $PID = $row['Column_name'];
                break;
            }
        }
        $sql = "SELECT $lock FROM $table WHERE $PID = '" . intval($id) . "'";
        $row = dbi_query($sql)->fetch_array();
        if ($row[$lock]) {
            return true;
        }
    } else if (is_object($id)) {
        $where = $id->getWhere();
		if (!$where) {
            return false;
        }
		$res = dbi_query("SELECT $lock FROM $table WHERE $where");

		if ($res && $row = $res->fetch_array()){
			if (isset($row[$lock]) && $row[$lock]){
				return true;
			}
		}
    }

    return false;
}

/**
 * update the table tbl_tax_sciname with the changed or newly entered scientific name
 *
 * @param integer $taxonID taxon-ID
 */
function updateTblTaxSciname($taxonID)
{
    $taxonIDfiltered = intval($taxonID);

    $result = dbi_query("SELECT scientificName FROM tbl_tax_sciname WHERE taxonID = $taxonIDfiltered");
    if ($result->num_rows > 0) {
        dbi_query("UPDATE tbl_tax_sciname SET
                    scientificName = herbar_view.GetScientificName($taxonIDfiltered, 0),
                    taxonName = herbar_view._buildScientificName($taxonIDfiltered)
                   WHERE taxonID = $taxonIDfiltered");
    } else {
        dbi_query("INSERT INTO tbl_tax_sciname SET
                    taxonID = $taxonIDfiltered,
                    scientificName = herbar_view.GetScientificName($taxonIDfiltered, 0),
                    taxonName = herbar_view._buildScientificName($taxonIDfiltered)");
    }
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
    $rowSpecimen = dbi_query($sql)->fetch_array();

    $unitID = $rowSpecimen['source_code'];
    if ($rowSpecimen['HerbNummer']) {
        $sql = "SELECT digits, replace_char
                FROM tbl_labels_numbering
                WHERE collectionID_fk = '" . $rowSpecimen['collectionID'] . "'";            // first check on collectionID
        $result = dbi_query($sql);
        if ($result->num_rows > 0) {
            $row1 = $result->fetch_array();
            $found = false;
            if ($result->num_rows > 1) {
                $sql2 = "SELECT digits, replace_char
                         FROM tbl_labels_numbering
                         WHERE replace_char IS NOT NULL
                          AND collectionID_fk = '" . $rowSpecimen['collectionID'] . "'";     // set replace char wins
                $result2 = dbi_query($sql2);
                while ($row2 = $result2->fetch_array()) {
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
            $result = dbi_query($sql);
            if ($result->num_rows > 0) {
                $row1 = $result->fetch_array();
                $found = false;
                if ($result->num_rows > 1) {
                    $sql = "SELECT digits, replace_char
                            FROM tbl_labels_numbering
                            WHERE replace_char IS NOT NULL
                             AND collectionID_fk IS NULL
                             AND sourceID_fk = '" . $rowSpecimen['source_id'] . "'";        // set replace char wins
                    $result = dbi_query($sql);
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
    $row = dbi_query($sql)->fetch_array();

    $unitID = $row['source_code'];

    $sql = "SELECT digits
            FROM tbl_labels_numbering
            WHERE replace_char IS NULL
             AND collectionID_fk IS NULL
             AND sourceID_fk = '" . intval($sourceID) . "'";
    $result = dbi_query($sql);
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
 * @param string $property
 * @return string
 */
function getCssProperty(string $property)
{
    if ($property == "body.background-color") {
        return "#008000";
    }
    return "";
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
        case 'GET':     echo htmlspecialchars(filter_input(INPUT_GET, $name));                    break;
        case 'POST':    echo htmlspecialchars(filter_input(INPUT_POST, $name));                   break;
        case 'SESSION': echo (isset($_SESSION[$name])) ? htmlspecialchars($_SESSION[$name]) : ''; break;
    }
}
