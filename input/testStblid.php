<?php
session_start();
require("inc/connect.php");
no_magic();

$stblid = "";

$specimen_id = intval(filter_input(INPUT_POST, 'specimen_ID', FILTER_SANITIZE_NUMBER_INT));
if ($specimen_id) {
    $result_specimen = db_query("SELECT mc.source_id
                                 FROM tbl_specimens s
                                  LEFT JOIN tbl_management_collections mc ON mc.collectionID = s.collectionID
                                 WHERE specimen_ID = '$specimen_id'");
    if ($result_specimen && mysql_num_rows($result_specimen) > 0) {
        $row_specimen = mysql_fetch_array($result_specimen);
        $source_id = $row_specimen['source_id'];

        $result_meta_stblid = db_query("SELECT `text`, `table_column`, `pattern`, `replacement`
                                        FROM `meta_stblid`
                                        WHERE `source_id` = '$source_id'
                                        ORDER BY `sequence`");
        if ($result_meta_stblid && mysql_num_rows($result_meta_stblid) > 0) {
            while ($row_meta_stblid = mysql_fetch_array($result_meta_stblid)) {
                $stblid .= $row_meta_stblid['text'];
                if ($row_meta_stblid['table_column']) {
                    $parts = explode(".", $row_meta_stblid['table_column']);
                    $table = $parts[0];
                    $column = $parts[1];

                    $result = db_query("show index from $table where Key_name = 'PRIMARY'");
                    $row = mysql_fetch_array($result);
                    $primaryKey = $row['Column_name'];

                    $result = db_query("SELECT $column
                                        FROM $table
                                        WHERE $primaryKey = '" . intval(filter_input(INPUT_POST, $primaryKey, FILTER_SANITIZE_NUMBER_INT)) . "'");
                    if ($result && mysql_num_rows($result) > 0) {
                        $row = mysql_fetch_array($result);
                        $stblid .= preg_replace($row_meta_stblid['pattern'], $row_meta_stblid['replacement'], $row[$column]);
                    }
                }
            }
        }
    }
}






?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - test Stable IDs</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</head>

<body>

Enter Specimen ID
<form Action="<?php echo htmlspecialchars($_SERVER['SCRIPT_NAME']); ?>" Method="POST" name="f" id="f">
    <input type="text" name="specimen_ID" value="<?php echo $specimen_id; ?>">
    <input type="submit">
</form>
<p>
Stable ID: <br>
<?php echo $stblid; ?>

</body>
</html>