<?php
require_once './inc/variables.php';

/** @var mysqli $dbLink */
$dbLink = new mysqli($host, $user, $pass, $db);
$dbLink = new mysqli($_CONFIG['DATABASE']['INPUT']['host'],
                     $_CONFIG['DATABASE']['INPUT']['readonly']['user'],
                     $_CONFIG['DATABASE']['INPUT']['readonly']['pass'],
                     $_CONFIG['DATABASE']['INPUT']['name']);
if ($dbLink->connect_errno) {
    die("Database not available!");
}
$dbLink->set_charset('utf8');


/**
 * do a mysql query
 *
 * @global mysqli $dbLink link to database
 * @param string $sql query string
 * @return mixed mysqli_result or false if error
 */
function db_query($sql)
{
  global $dbLink;

  $res = $dbLink->query($sql);

  if(!$res){
    echo $sql . "\n"
       . $dbLink->errno . ": " . $dbLink->error . "\n";
  }

  return $res;
}


/**
 * encase text with quotes or return NULL if string is empty
 *
 * @global mysqli $dbLink link to database
 * @param string $text text to quote
 * @return string result
 */
function quoteString($text)
{
    global $dbLink;

    if (strlen($text) > 0) {
        return "'" . $dbLink->real_escape_string($text) . "'";
    } else {
        return "NULL";
    }
}

$missing = array();

/** @var mysqli_result $result_sources */
$result_sources = db_query("SELECT source_id FROM meta_stblid GROUP BY source_id ORDER BY source_id");
while ($row_sources = $result_sources->fetch_array()) {
    /** @var mysqli_result $result_specimen */
    $result_specimen = db_query("SELECT mc.collectionID, mc.source_id, s.specimen_ID, s.HerbNummer
                                 FROM tbl_specimens s
                                  LEFT JOIN tbl_management_collections mc ON mc.collectionID = s.collectionID
                                 WHERE mc.source_id = " . $row_sources['source_id'] . "
                                  AND s.HerbNummer IS NOT NULL
                                  AND s.HerbNummer != 0");
    while ($row_specimen = $result_specimen->fetch_array()) {
        /** @var mysqli_result $result_test */
        $result_test = db_query("SELECT id FROM tbl_specimens_stblid WHERE specimen_ID = '" . $row_specimen['specimen_ID'] . "'");
        if ($result_test->num_rows == 0) {
            $missing[$row_sources['source_id']][] = array('specimen_ID' => $row_specimen['specimen_ID'], 'HerbNummer' => $row_specimen['HerbNummer']);
        }
    }
}



//SELECT  specimen_ID, COUNT(`specimen_ID`) as cnt
//FROM `tbl_specimens_stblid`
//GROUP BY`specimen_ID` having cnt > 0

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
    <head></head>
    <body>
        <h3>Check tbl_specimens_stblid against tbl_specimens</h3>
        <p>
<?php
    foreach($missing as $source_id => $missing_block) {
        echo "<a href='#$source_id'>source-ID " . $source_id . ": " . count($missing_block) . " items missing</a><br>\n";
    }
?>
        </p>
        <table>
            <tr><th>source-ID&nbsp;</th><th>specimen-ID&nbsp;</th><th>HerbNummer</th></tr>
<?php
    foreach ($missing as $source_id => $missing_block) {
        $anchor = "<a name='$source_id'>$source_id</a>";
        foreach($missing_block as $row) {
            echo "<tr>"
               . "<td align='center'>$anchor</td>"
               . "<td align='center'>" . $row['specimen_ID'] . "</td>"
               . "<td align='center'>" . $row['HerbNummer'] . "</td>"
               . "</tr>\n";
            $anchor = $source_id;
        }
    }
?>
        </table>
    </body>
</html>