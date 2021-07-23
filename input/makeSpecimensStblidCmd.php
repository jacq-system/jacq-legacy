#!/usr/bin/php -qC
<?php
require_once './inc/variables.php';

ini_set("max_execution_time","3600");

/** @var mysqli $dbLink */
$dbLink = new mysqli($_CONFIG['DATABASE']['INPUT']['host'],
                     $_CONFIG['DATABASE']['INPUT']['readonly']['user'],
                     $_CONFIG['DATABASE']['INPUT']['readonly']['pass'],
                     $_CONFIG['DATABASE']['INPUT']['name']);
if ($dbLink->connect_errno) {
    die("Database access denied!");
}
$dbLink->set_charset('utf8');


/**
 * @param $sql
 * @param bool|FALSE $debug
 * @return mysqli_result
 */
function dbi_query($sql, $debug=false)
{
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


require_once 'inc/stableIdentifierFunctions.php';

ob_start();

$row_start = dbi_query("SELECT DATE(`starttime`) AS startdate FROM statusSpecimensStblid ORDER BY id DESC LIMIT 1")->fetch_array();
$startdate = $row_start['startdate'];

dbi_query("INSERT INTO statusSpecimensStblid SET starttime = NOW()");
$startID = $dbLink->insert_id;

$count_changed = $count_new = 0;
$count = array();
$result_specimen = dbi_query("SELECT s.`specimen_ID`, mc.`collectionID`, mc.`source_id`, s.`aktualdatum`
                              FROM tbl_specimens s, tbl_management_collections mc
                              WHERE s.`collectionID` = mc.`collectionID`
                               AND TIMESTAMPDIFF(DAY, '$startdate', s.`aktualdatum`) >= 0");
while ($row_specimen = $result_specimen->fetch_array()) {
    $stblid = makeStableIdentifier($row_specimen['source_id'], array('specimen_ID' => $row_specimen['specimen_ID']), $row_specimen['collectionID']);
    if ($stblid) {
        $result_test_spcId  = dbi_query("SELECT id FROM tbl_specimens_stblid WHERE specimen_ID = " . $row_specimen['specimen_ID']);
        $result_test_stblId = dbi_query("SELECT id FROM tbl_specimens_stblid WHERE stableIdentifier = '$stblid'");
        if ($result_test_stblId->num_rows == 0) {
            dbi_query("INSERT INTO tbl_specimens_stblid SET specimen_ID = '" . $row_specimen['specimen_ID'] . "', stableIdentifier = '$stblid'");
            if ($result_test_spcId->num_rows > 0) {
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
    $row_source = dbi_query("SELECT source_code FROM meta WHERE source_id = $key")->fetch_array();
    $details[$key] = array('source'  => $row_source['source_code'],
                           'new'     => (isset($value['new'])) ? $value['new'] : 0,
                           'changed' => (isset($value['changed'])) ? $value['changed'] : 0);
}
dbi_query("UPDATE statusSpecimensStblid SET
            new = $count_new,
            changed = $count_changed,
            details = '" . $dbLink->real_escape_string(json_encode($details)) . "',
            stoptime = NOW(),
            output = '" . $dbLink->real_escape_string(ob_get_contents()) . "'
           WHERE id = $startID");

ob_end_flush();