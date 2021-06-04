<?php
require_once './inc/variables.php';

/** @var mysqli $dbLink */
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
function dbi_query($sql)
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

if (!empty($_POST['rescan']) || !empty($_GET['rescan'])) {
    /** @var mysqli_result $result_sources */
    $result_sources = dbi_query("SELECT source_id FROM meta_stblid GROUP BY source_id ORDER BY source_id");
    while ($row_sources = $result_sources->fetch_array()) {
        /** @var mysqli_result $result_specimen */
        $result_specimen = dbi_query("SELECT mc.collectionID, mc.source_id, s.specimen_ID, s.HerbNummer
                                      FROM tbl_specimens s
                                       LEFT JOIN tbl_management_collections mc ON mc.collectionID = s.collectionID
                                      WHERE mc.source_id = " . $row_sources['source_id'] . "
                                       AND s.HerbNummer IS NOT NULL
                                       AND s.HerbNummer != 0
                                       AND s.specimen_ID NOT IN (SELECT specimen_ID FROM tbl_specimens_stblid)
                                      ORDER BY mc.collectionID, s.specimen_ID");
        while ($row_specimen = $result_specimen->fetch_array()) {
            $result_number = dbi_query("SELECT count(s.HerbNummer) AS number
                                        FROM tbl_specimens s
                                         LEFT JOIN tbl_management_collections mc ON mc.collectionID = s.collectionID
                                        WHERE HerbNummer = '" . $row_specimen['HerbNummer'] . "'
                                         AND mc.source_id = "  . $row_specimen['source_id'] . "
                                        GROUP BY HerbNummer");
            $row_number = $result_number->fetch_array();
            $missing[$row_sources['source_id']][] = array('specimen_ID'  => $row_specimen['specimen_ID'],
                                                          'collectionID' => $row_specimen['collectionID'],
                                                          'HerbNummer'   => $row_specimen['HerbNummer'],
                                                          'count'        => $row_number['number']);
        }
    }
    dbi_query("TRUNCATE checkSpecimensStblid");
    foreach ($missing as $source_id => $missing_block) {
        foreach($missing_block as $row) {
            dbi_query("INSERT INTO checkSpecimensStblid SET `source_id`    = $source_id,
                                                            `collectionID` = " . $row['collectionID'] . ",
                                                            `specimen_ID`  = " . $row['specimen_ID'] . ",
                                                            `HerbNummer`   = '" . $row['HerbNummer'] . "',
                                                            `count`        = " . $row['count']);
        }
    }
} else {
    $result_missing = dbi_query("SELECT * FROM checkSpecimensStblid ORDER BY id");
    while ($row_missing = $result_missing->fetch_array()) {
        $missing[$row_missing['source_id']][] = array('specimen_ID'  => $row_missing['specimen_ID'],
                                                      'collectionID' => $row_missing['collectionID'],
                                                      'HerbNummer'   => $row_missing['HerbNummer'],
                                                      'count'        => $row_missing['count']);
    }
}

$sources = array();
$result_sources = dbi_query("SELECT source_id, source_code FROM meta");
while ($row_sources = $result_sources->fetch_array()) {
    $sources[$row_sources['source_id']] = $row_sources['source_code'];
}
$collections = array();
$result_collections = dbi_query("SELECT collectionID, collection FROM tbl_management_collections");
while ($row_collections = $result_collections->fetch_array()) {
    $collections[$row_collections['collectionID']] = $row_collections['collection'];
}

?><!DOCTYPE html>
<html>
    <head></head>
    <body>
        <h3>Check tbl_specimens_stblid against tbl_specimens</h3>
        <p>
        <form action="checkSpecimensStblid.php" method="POST">
            <input type="submit" name="rescan" value=" Rescan ">
        </form>
        <p>
<?php
    foreach($missing as $source_id => $missing_block) {
        echo "<a href='#$source_id'>" . $sources[$source_id] . " (" . $source_id . "): " . count($missing_block) . " items missing</a><br>\n";
    }
?>
        </p>
        <table>
            <tr><th>source&nbsp;</th><th>collection</th><th>specimen-ID&nbsp;</th><th>HerbNummer</th><th></th></tr>
<?php
    foreach ($missing as $source_id => $missing_block) {
        $anchor = "<a name='$source_id'>" . $sources[$source_id] . " ($source_id)</a>";
        foreach($missing_block as $row) {
            echo "<tr>"
               . "<td align='center'>$anchor</td>"
               . "<td align='center'>" . $collections[$row['collectionID']] . " (" . $row['collectionID'] . ")</td>"
               . "<td align='center'>" . $row['specimen_ID'] . "</td>"
               . "<td align='center'>" . $row['HerbNummer'] . "</td>"
               . "<td align='center'>(" . $row['count'] . ")</td>"
               . "</tr>\n";
            $anchor = $sources[$source_id] . " ($source_id)";
        }
    }
?>
        </table>
    </body>
</html>