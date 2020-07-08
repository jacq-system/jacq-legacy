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
/** @var mysqli_result $result */
$result = $dbLink->query("SELECT `uuid_minter_type_id`, `internal_id` FROM `srvc_uuid_minter` WHERE `uuid` = '$uuid'");
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if ($row['uuid_minter_type_id'] == 2) {
        header("Location: http://legacy-living.jacq.org/index.php?r=dataBrowser/classificationBrowser&referenceType=citation&referenceId=" . $row['internal_id']);
        exit();
    }
}