<?php
session_start();
require("inc/connect.php");
require_once 'inc/stableIdentifierFunctions.php';
no_magic();


$stblid = "";
$specimen_id = intval(filter_input(INPUT_POST, 'specimen_ID', FILTER_SANITIZE_NUMBER_INT));
if ($specimen_id) { // did we get a valid specimen-ID?
    $result_specimen = db_query("SELECT mc.collectionID, mc.source_id
                                 FROM tbl_specimens s
                                  LEFT JOIN tbl_management_collections mc ON mc.collectionID = s.collectionID
                                 WHERE specimen_ID = '$specimen_id'");
    if ($result_specimen && mysql_num_rows($result_specimen) > 0) { // we've found a valid source_id and collectionID
        $row_specimen = mysql_fetch_array($result_specimen);

        $stblid = makeStableIdentifier($row_specimen['source_id'],
                                       array('specimen_ID' => intval(filter_input(INPUT_POST, $primaryKey, FILTER_SANITIZE_NUMBER_INT))),
                                       $row_specimen['collectionID']);
    }
}



?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <title>herbardb - test Stable IDs</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</head>

<body onLoad="document.f.specimen_ID.focus()">

Enter Specimen ID
<form Action="<?php echo htmlspecialchars($_SERVER['SCRIPT_NAME']); ?>" Method="POST" name="f" id="f">
    <input type="text" name="specimen_ID" value="<?php echo ($specimen_id) ? $specimen_id : ''; ?>">
    <input type="submit">
</form>
<p>
Stable ID: <br>
<?php echo $stblid; ?>

</body>
</html>