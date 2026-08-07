<?php
session_start();
require("inc/connect.php");

if (empty($_GET['sel'])) die();

$specimenId = intval($_GET['sel']);

function renderLogCell($value)
{
    return htmlspecialchars((string)$value);
}

function renderLogUser($row, $hasRight)
{
    if (!$row) {
        return '';
    }

    $userId = isset($row['userID']) ? $row['userID'] : '';
    $fullName = trim((isset($row['firstname']) ? $row['firstname'] : '') . " " . (isset($row['surname']) ? $row['surname'] : ''));
    if ($fullName === '') {
        $fullName = $userId;
    }

    return ($hasRight || (isset($_SESSION['uid']) && $userId == $_SESSION['uid'])) ? $fullName : $userId;
}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - list Specimens</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <style type="text/css">
    td.changed-field {
      background-color: #fff3a6;
      font-weight: bold;
    }
  </style>
</head>

<body>
<h2>List log_specimens for ID <?php echo $specimenId; ?></h2>

<?php
$result = dbi_query("SELECT specimenID FROM herbarinput_log.log_specimens WHERE specimenID = '" . $specimenId . "'");
if (mysqli_num_rows($result) == 0):  // nothing found
?>
nothing in log
<?php
else:  // show results
?>
<table class="out" cellspacing="0">
<tr class="out">
  <th class="out">User</th>
  <th class="out">Timestamp</th>
  <th class="out">state</th>
<?php
$result = dbi_query("SHOW COLUMNS FROM herbarinput_log.log_specimens");
$fields = array();
while ($row = mysqli_fetch_array($result)) {
    if ($row['Field'] != 'log_specimensID' && $row['Field'] != 'specimenID' && $row['Field'] != 'userID' && $row['Field'] != 'updated' && $row['Field'] != 'timestamp') {
        $fields[] = $row['Field'];
        echo "  <th class=\"out\">" . renderLogCell($row['Field']) . "</th>\n";
    }
}
echo "</tr>\n";

$sql = "SELECT ls.*, hu.firstname, hu.surname
        FROM herbarinput_log.log_specimens ls
             LEFT JOIN herbarinput_log.tbl_herbardb_users hu ON ls.userID = hu.userID
        WHERE ls.specimenID = '" . $specimenId . "'
        ORDER BY ls.timestamp, ls.log_specimensID";
$result = dbi_query($sql);
$hasRight = checkRight('specimensHistory');

$previousEvent = null;
$stateRows = array();
while ($row = mysqli_fetch_array($result)) {
    if ($row['updated']) {
        $stateRows[] = array(
            'meta' => ($previousEvent) ? $previousEvent : $row,
            'values' => $row,
            'state' => 'stored state',
        );
    }
    $previousEvent = $row;
}

$result = dbi_query("SELECT * FROM tbl_specimens WHERE specimen_ID = '" . $specimenId . "'");
$activeRow = mysqli_fetch_array($result);
if ($activeRow) {
    $stateRows[] = array(
        'meta' => $previousEvent,
        'values' => $activeRow,
        'state' => 'active set',
    );
}

$previousValues = null;
foreach ($stateRows as $stateRow) {
    $meta = $stateRow['meta'];
    $values = $stateRow['values'];

    echo "<tr class=\"out\">\n"
       . "  <td class=\"out\">" . renderLogCell(renderLogUser($meta, $hasRight)) . "</td>\n"
       . "  <td class=\"out\">" . renderLogCell(($meta && isset($meta['timestamp'])) ? $meta['timestamp'] : '') . "</td>\n"
       . "  <td class=\"out\">" . renderLogCell($stateRow['state']) . "</td>\n";
    for ($i = 0; $i < count($fields); $i++) {
        $field = $fields[$i];
        $value = isset($values[$field]) ? $values[$field] : '';
        $previousValue = ($previousValues && isset($previousValues[$field])) ? $previousValues[$field] : '';
        $changedClass = ($previousValues && (string)$value !== (string)$previousValue) ? ' changed-field' : '';
        echo "  <td class=\"out" . $changedClass . "\">" . renderLogCell($value) . "</td>\n";
    }
    echo "</tr>\n";
    $previousValues = $values;
}
?>
</table>

<?php
endif;  // end show results
?>

</body>
</html>