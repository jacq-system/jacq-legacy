<?php
require_once './inc/variables.php';

/** @var mysqli $dbLink */
$dbLink = new mysqli($_CONFIG['DATABASE']['JACQ']['host'],
                     $_CONFIG['DATABASE']['JACQ']['readonly']['user'],
                     $_CONFIG['DATABASE']['JACQ']['readonly']['pass'],
                     $_CONFIG['DATABASE']['JACQ']['name']);
if ($dbLink->connect_errno) {
    die("Database not available!");
}
$dbLink->set_charset('utf8');

$uuid = $dbLink->real_escape_string(filter_input(INPUT_GET, 'uuid', FILTER_SANITIZE_STRING));
$type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);
/** @var mysqli_result $result */
$result = $dbLink->query("SELECT `uuid_minter_type`, `internal_id` FROM `uuid_replica` WHERE `uuid` = '$uuid'");
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if ($type == 'internal_id') {
        echo $row['internal_id'];
    } elseif ($type == 'type') {
        echo $row['uuid_minter_type'];
    } else {
        if ($row['uuid_minter_type'] == 'scientific_name') {
            $dbLink->query("CALL herbar_view.GetScientificNameComponents({$row['internal_id']},@genericEpithet,@specificEpithet,@infraspecificRank,@infraspecificEpithet,@author)");
            $res = $dbLink->query("SELECT @genericEpithet,@specificEpithet,@infraspecificRank,@infraspecificEpithet,@author");
            $row = $res->fetch_assoc();
            if ($row) {
                $scientificName = $row['@genericEpithet'] . " " . $row['@specificEpithet'] . (($row['@infraspecificEpithet']) ? "\n" . $row['@infraspecificRank'] . " " . $row['@infraspecificEpithet'] : "") . " " . $row['@author'];
            } else {
                $scientificName = '';
            }
            echo $scientificName;
        } elseif ($row['uuid_minter_type'] == 'citation') {
            $result = $dbLink->query("SELECT `herbar_view`.GetProtolog('{$row['internal_id']}') AS protolog");
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                echo($row['protolog']);
            }
        } elseif ($row['uuid_minter_type'] == 'specimen') {
            echo "specimen: " . $row['internal_id'];
        }
    }
}
