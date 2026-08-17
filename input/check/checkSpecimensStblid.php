<?php
session_start();
require("../inc/gatekeeper.php");
require __DIR__ . '/../vendor/autoload.php';

use Jacq\DbAccess;

$db = DbAccess::ConnectTo('INPUT');

$missing = array();

if (!empty($_POST['rescan']) || !empty($_GET['rescan'])) {
    $result_sources = $db->queryCatch("SELECT source_id FROM meta_stblid GROUP BY source_id ORDER BY source_id");
    while ($row_sources = $result_sources->fetch_array()) {
        $result_specimen = $db->queryCatch("SELECT mc.collectionID, mc.source_id, s.specimen_ID, s.HerbNummer
                                            FROM tbl_specimens s
                                             LEFT JOIN tbl_management_collections mc ON mc.collectionID = s.collectionID
                                             LEFT JOIN tbl_specimens_stblid ss ON ss.specimen_ID = s.specimen_ID 
                                            WHERE mc.source_id = {$row_sources['source_id']}
                                             AND s.HerbNummer IS NOT NULL
                                             AND s.HerbNummer != '0'
                                             AND ss.stableIdentifier IS NULL");
        while ($row_specimen = $result_specimen->fetch_array()) {
            $result_number = $db->queryCatch("SELECT count(s.HerbNummer) AS number, GROUP_CONCAT(s.specimen_ID ORDER BY s.specimen_ID) AS `keys`
                                              FROM tbl_specimens s
                                               LEFT JOIN tbl_management_collections mc ON mc.collectionID = s.collectionID
                                              WHERE HerbNummer = '" . $row_specimen['HerbNummer'] . "'
                                               AND mc.source_id = "  . $row_specimen['source_id'] . "
                                              GROUP BY HerbNummer");
            $row_number = $result_number->fetch_array();
            $missing[$row_sources['source_id']][] = array('specimen_ID'  => $row_specimen['specimen_ID'],
                                                          'collectionID' => $row_specimen['collectionID'],
                                                          'HerbNummer'   => $row_specimen['HerbNummer'],
                                                          'keys'         => $row_number['keys'],
                                                          'count'        => $row_number['number']);
        }
    }
    $db->queryCatch("TRUNCATE checkSpecimensStblid");
    foreach ($missing as $source_id => $missing_block) {
        foreach($missing_block as $row) {
            $db->queryCatch("INSERT INTO checkSpecimensStblid SET `source_id`    = $source_id,
                                                                         `collectionID` = {$row['collectionID']},
                                                                         `specimen_ID`  = {$row['specimen_ID']},
                                                                         `HerbNummer`   = '{$row['HerbNummer']}',
                                                                         `count`        = {$row['count']},
                                                                         `keys`         = '{$row['keys']}'");
        }
    }
}

$missing = array();
$result_missing = $db->queryCatch("SELECT * FROM checkSpecimensStblid ORDER BY source_id, collectionID, count DESC, specimen_ID");
while ($row_missing = $result_missing->fetch_array()) {
    $missing[$row_missing['source_id']][] = array('specimen_ID'  => $row_missing['specimen_ID'],
                                                  'collectionID' => $row_missing['collectionID'],
                                                  'HerbNummer'   => $row_missing['HerbNummer'],
                                                  'count'        => $row_missing['count'],
                                                  'keys'         => $row_missing['keys']);
}

$sources = array();
$result_sources = $db->queryCatch("SELECT source_id, source_code FROM meta");
while ($row_sources = $result_sources->fetch_array()) {
    $sources[$row_sources['source_id']] = $row_sources['source_code'];
}
$collections = array();
$result_collections = $db->queryCatch("SELECT collectionID, collection FROM tbl_management_collections");
while ($row_collections = $result_collections->fetch_array()) {
    $collections[$row_collections['collectionID']] = $row_collections['collection'];
}

?><!DOCTYPE html>
<html lang="en">
    <head>
        <title>herbardb - check stblids</title>
    </head>
    <body>
        <h3>Check tbl_specimens_stblid against tbl_specimens</h3>
        <p>
        <form action="checkSpecimensStblid.php" method="POST">
            <input type="submit" name="rescan" value=" Rescan (very slow) ">
        </form>
        <p>
<?php
    foreach($missing as $source_id => $missing_block) {
        echo "<a href='#$source_id'>" . $sources[$source_id] . " (" . $source_id . "): " . count($missing_block) . " items missing</a><br>\n";
    }
?>
        </p>
        <table>
            <tr><th>source&nbsp;</th><th>collection</th><th>specimen-ID&nbsp;</th><th>HerbNummer</th><th></th><th>specimen-IDs</th></tr>
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
               . "<td>" . $row['keys'] . "</td>"
               . "</tr>\n";
            $anchor = $sources[$source_id] . " ($source_id)";
        }
    }
?>
        </table>
    </body>
</html>
