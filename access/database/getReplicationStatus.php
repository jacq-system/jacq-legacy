<?php
include 'inc/variables.php';
$db = new mysqli($_CONFIG['DB']['REPLICATION']['host'], $_CONFIG['DB']['REPLICATION']['user'], $_CONFIG['DB']['REPLICATION']['pass'], "");

$result = $db->query("show slave status");
$row = $result->fetch_array();

$ret = array('Slave_IO_Running'      => $row['Slave_IO_Running'],
             'Slave_SQL_Running'     => $row['Slave_SQL_Running'],
             'Seconds_Behind_Master' => $row['Seconds_Behind_Master'],
             'Last_Error'            => $row['Last_Error'],
             'Read_Master_Log_Pos'   => $row['Read_Master_Log_Pos'],
             'Exec_Master_Log_Pos'   => $row['Exec_Master_Log_Pos']);

// Set the JSON header
header("Content-type: text/json");
echo json_encode($ret, JSON_NUMERIC_CHECK);
