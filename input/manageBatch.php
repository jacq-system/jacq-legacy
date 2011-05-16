<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");
require("inc/api_functions.php");
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

$type = (isset($_GET['type'])) ? intval($_GET['type']) : 0;
$batchID = (isset($_GET['ID'])) ? intval($_GET['ID']) : 0;

//---------- local functions ----------
function showList($link, $withID=true)
{
    echo "<ul>\n";
    $sql = "SELECT remarks, date_supplied, batchID, batchnumber, source_code
            FROM api.tbl_api_batches
             LEFT JOIN herbarinput.meta ON api.tbl_api_batches.sourceID_fk = herbarinput.meta.source_id
            WHERE sent = '0'";
    if (!checkRight('batchAdmin')) $sql .= " AND api.tbl_api_batches.sourceID_fk = " . $_SESSION['sid'];  // check right and sourceID
    $sql .= " ORDER BY source_code, batchnumber, date_supplied DESC";
    $result = db_query($sql);
    while ($row = mysql_fetch_array($result)) {
        $batchNr = " &lt;" . (($row['source_code']) ? $row['source_code'] . "-" : "") . $row['batchnumber'] . "&gt; ";
        echo "<li><a href=\"$link" . (($withID) ? $row['batchID'] : "") . "\">"
           . $row['date_supplied'] . "$batchNr (" . htmlspecialchars(trim($row['remarks'])) . ")</a>";
        if ($batchID == $row['batchID']) echo "&nbsp;<b>processed</b>";
        echo "</li>\n";
    }
    echo "</ul>\n";
}

function showEditFields($date_supplied, $remarks = "", $batchID = 0)
{
    if (!$batchID) {
        $pre = "new_";
        $btn = "insert";
        if (checkRight('batchAdmin')) {
            $chooseInstitution = "<select name=\"{$pre}sourceID_fk\" size=\"1\">"
                               . "<option value=\"0\" selected>General use</option>";
            $result = db_query("SELECT source_id, source_name FROM herbarinput.meta ORDER BY source_name");
            while ($row = mysql_fetch_array($result)) {
                $chooseInstitution .= "<option value=\"" . $row['source_id'] . "\">" . $row['source_name'] . "</option>";
            }
            $chooseInstitution .= "</select>\n";
        } else {
            $row = mysql_fetch_array(db_query("SELECT source_name FROM herbarinput.meta WHERE source_id = " . $_SESSION['sid']));
            $chooseInstitution = $row['source_name'];
        }
    }
    else {
        $pre = "";
        $btn = "update";
        $row = mysql_fetch_array(db_query("SELECT sourceID_fk FROM api.tbl_api_batches WHERE batchID = $batchID"));
        if ($row['sourceID_fk']) {
            $row = mysql_fetch_array(db_query("SELECT source_name FROM herbarinput.meta WHERE source_id = " . $row['sourceID_fk']));
            $chooseInstitution = $row['source_name'];
        } else {
            $chooseInstitution = "General use";
        }
    }
    echo "<table cellpadding=\"0\" cellspacing=\"0\">\n"
       . "  <tr>\n"
       . "     <td class=\"label\">Institution:</td>\n"
       . "     <td class=\"input\">$chooseInstitution</td>\n"
       . "  </tr><tr>\n"
       . "    <td class=\"label\">Supply date:</td>\n"
       . "    <td class=\"input\"><input type=\"text\" class=\"text\" style=\"width: 6em;\" name=\"{$pre}date_supplied\" value=\"$date_supplied\"></td>\n"
       . "  </tr><tr>\n"
       . "    <td class=\"label\">Remarks:</td>\n"
       . "    <td class=\"input\"><input type=\"text\" class=\"text\" style=\"width: 50em;\" name=\"{$pre}remarks\" value=\"$remarks\"></td>\n"
       . "  </tr><tr>\n"
       . "    <td align=\"right\"><input type=\"submit\" name=\"$pre$btn\" class=\"button\" style=\"margin-top: 2px;\" value=\"$btn\"></td>\n"
       . "    <td></td>\n"
       . "  </tr>\n"
       . "</table>\n";
}
//********** END local functions **********

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - renew Batch data</title>
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

<h1>Batch data</h1>

<h3>Make new Batch</h3>
<form Action="<?php echo $_SERVER['PHP_SELF']; ?>?type=2" Method="POST" name="f">
<?php showEditFields(date('Y-m-d')); ?>
</form>
<?php
if ($type == 2 && isset($_POST['new_insert']) && $_POST['new_insert']) {
    if (!checkRight('batchAdmin')) {
        $institutionID = $_SESSION['sid'];
    } else {
        $institutionID = intval($_POST['new_sourceID_fk']);
    }
    $sql = "INSERT INTO api.tbl_api_batches (batchnumber)
              SELECT MAX(batchnumber)+1
              FROM api.tbl_api_batches
              WHERE sourceID_fk = $institutionID";
    db_query($sql);
    $id = mysql_insert_id();
    $sql = "UPDATE api.tbl_api_batches SET
             sourceID_fk = '$institutionID',
             date_supplied = " . quoteString($_POST['new_date_supplied']) . ",
             remarks = " . quoteString($_POST['new_remarks']) . "
            WHERE batchID = $id";
    db_query($sql);
}
?>

<h3>Edit unsent Batches:</h3>
<?php
if ($type == 4 && $batchID) { // update an unsent batch
    $sql = "UPDATE api.tbl_api_batches SET ";
    //if (checkRight('batchAdmin')) $sql .= "sourceID_fk = '" . intval($_POST['sourceID_fk']) . "', ";
    $sql .= " date_supplied = " . quoteString($_POST['date_supplied']) . ",
              remarks = ".quoteString($_POST['remarks']) . "
             WHERE batchID = $batchID";
    db_query($sql);
}
if ($type == 3 && $batchID) { // edit the unsent batch
    echo "<form Action=\"" . $_SERVER['PHP_SELF'] . "?type=4&ID=$batchID\" Method=\"POST\" name=\"f2\">\n";
    $result = db_query("SELECT * FROM api.tbl_api_batches WHERE batchID = $batchID");
    $row = mysql_fetch_array($result);
    showEditFields($row['date_supplied'], $row['remarks'], $batchID);
    echo "</form>\n";
} else {
    showList($_SERVER['PHP_SELF'] . "?type=3&ID=", true);
}
?>

<h3>Update database for unsent Batches:</h3>
<?php
showList($_SERVER['PHP_SELF'] . "?type=1&ID=", true);

if ($type == 1 && $batchID) {  // update database
    $sql = "SELECT *
            FROM api.tbl_api_batches
            WHERE (sent = '0' OR sent IS NULL)
             AND batchID = " . quoteString($batchID);
    if (!checkRight('batchAdmin')) $sql .= " AND sourceID_fk = " . $_SESSION['sid'];  // check right and sourceID
    $result = db_query($sql);
    if (mysql_num_rows($result) > 0) {  // only unsent batches may be processed
        $result = db_query("SELECT specimen_ID FROM api.tbl_api_specimens WHERE batchID_fk = " . quoteString($batchID));
        while ($row = mysql_fetch_array($result)) {
            update_tbl_api_units($row['specimen_ID']);
            update_tbl_api_units_identifications($row['specimen_ID']);
        }
    }
}
?>

<h3>Make XML-Files for unsent Batches:</h3>
<?php
showList("http://131.130.131.9/api/copyImagesWithXML.php?ID=", true);
?>

<h3>Make Webimage-Links for unsent Batches:</h3>
<?php
showList("http://131.130.131.9/api/copyWebImages.php?ID=", true);
?>

</body>
</html>