<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");
require("inc/api_functions.php");
require("inc/clsDbAccess.php");
require("inc/jacqServletJsonRPCClient.php");

no_magic();

//---------- check every input ----------
if (!checkRight('batch')) { // only user with right "api" can change API
    echo "<html><head></head><body>\n"
       . "<h1>Error</h1>\n"
       . "Access denied\n"
       . "</body></html>\n";
    die();
}
if (!checkRight('batchAdmin')) {
    $result = db_query("SELECT source_name FROM herbarinput.meta WHERE source_id = " . $_SESSION['sid']);
    if (mysql_num_rows($result) == 0) {
        echo "<html><head></head><body>\n"
           . "<h1>Error</h1>\n"
           . "Your institution is not in the batch database.\n"
           . "</body></html>\n";
        die();
    }
}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - import File into Batch data</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <style type="text/css">
    td.label { font-size: 100%; font-family: sans-serif; font-weight: bold; color: darkGray; text-align: right; white-space: nowrap; }
    td.input { padding-left: 0.3em; }
    input.text { font-size: 100%; font-family: sans-serif; padding-left: 0.3em; }
    input.button       { font-size: 100%; font-family: sans-serif; padding-left: 0; font-weight: bold; }
    input.button:hover { background-color: #abffab }
  </style>
</head>

<body>

<h1>File Import into unsent Batches</h1>
<strong>Caution: already existent Batch data will be deleted on file import</strong>

<form enctype="multipart/form-data" action='<?php echo $_SERVER['PHP_SELF']; ?>' method='POST' name='f'>
<?php
    $batchID = (isset($_POST['batch'])) ? $_POST['batch'] : 0;
    $batchValue = array();
    $batchText = array();
    $sql = "SELECT remarks, date_supplied, batchID, batchnumber, source_code
            FROM api.tbl_api_batches
             LEFT JOIN herbarinput.meta ON api.tbl_api_batches.sourceID_fk = herbarinput.meta.source_id
            WHERE sent = '0'";
    if (!checkRight('batchAdmin')) {
        $sql .= " AND api.tbl_api_batches.sourceID_fk = " . $_SESSION['sid'];  // check right and sourceID
    }
    $sql .= " ORDER BY source_code, batchnumber, date_supplied DESC";
    $result = db_query($sql);
    $selects = "";
    while ($row = mysql_fetch_array($result)) {
        $selects .= "  <option value='" . $row['batchID'] . "'" . (($batchID == $row['batchID']) ? " selected" : '') . ">"
                  . $row['date_supplied']
                  . htmlspecialchars(' <' . (($row['source_code']) ? $row['source_code'] . '-' : '') . $row['batchnumber'] . '> (' . trim($row['remarks']) . ')')
                  . "</option>\n";
    }
?>
    <p><select name="batch"><?php echo $selects; ?></select></p>
    <p><input type="hidden" name="MAX_FILE_SIZE" value="300000" /></p>
    <p>Import this File: <input name="importfile" type="file" /></p>
    <p><input class="button" type="submit" name="insertIntoBatch" value=" Delete Batch data and import file into selected batch "></p>
</form>

<?php
if (isset($_POST['insertIntoBatch']) && isset($_FILES['importfile']['tmp_name'])) {
    $buffer = file($_FILES['importfile']['tmp_name']);
    $lines = array();
    foreach ($buffer as $key => $val) {
        $columns = str_getcsv($val);
        $pos = strpos($columns[0], '_');
        if ($pos) {
            $line = substr($columns[0], 0, $pos);
        } else {
            $line = substr($columns[0], 0, strpos($columns[0], '.'));
        }
        $lines[$key] = $line;
    }
    $lines = array_unique($lines);
    foreach ($lines as $key => $val) {
        echo $key . ": " . $val . "<br>\n";
    }
}

?>

</body>
</html>