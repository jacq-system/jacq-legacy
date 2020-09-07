<?php
$db = new mysqli("localhost", "checkReplication", "jHGaufbDRdbwMxpD", "");
$result = $db->query("show slave status");
$row = $result->fetch_array();

$ret = array('Slave_IO_Running'      => $row['Slave_IO_Running'],
             'Slave_SQL_Running'     => $row['Slave_SQL_Running'],
             'Seconds_Behind_Master' => $row['Seconds_Behind_Master'],
             'Last_Error'            => $row['Last_Error']);

// Set the JSON header
header("Content-type: text/json");
echo json_encode($ret, JSON_NUMERIC_CHECK);