<?php
session_start();
require("inc/connect.php");
no_magic();

/**
 * make a stable identifier by using the textes and patterns in meta_stblid
 *
 * if there are one or more table references in meta_stblid and one of them holds no data, the stable identifier is considered empty
 *
 * @param int $source_id source-ID
 * @param int $collection_id collection-ID
 * @return string the stable identifier
 */
function makeStableIdentifier($source_id, $collection_id)
{
    $stblid = "";   // holds the stable identifier
    $valid = TRUE;  // is the stable identifier valid?

    // first find a specific entry with source_id and collectionID
    $result_meta_stblid = db_query("SELECT `text`, `table_column`, `pattern`, `replacement`
                                    FROM `meta_stblid`
                                    WHERE `source_id` = '$source_id'
                                     AND `collectionID` = '$collection_id'
                                    ORDER BY `sequence`");
    if ($result_meta_stblid && mysql_num_rows($result_meta_stblid) > 0) {
        $rows_meta_stblid = mysql_fetch_all($result_meta_stblid);
    } else {
        // no luck, so we search an entry which is valid for any collection of a given source_id
        $result_meta_stblid = db_query("SELECT `text`, `table_column`, `pattern`, `replacement`
                                        FROM `meta_stblid`
                                        WHERE `source_id` = '$source_id'
                                         AND `collectionID` IS NULL
                                        ORDER BY `sequence`");
        if ($result_meta_stblid && mysql_num_rows($result_meta_stblid) > 0) {
            $rows_meta_stblid = mysql_fetch_all($result_meta_stblid);
        } else {
            // still nothing found, so there's nothing to do
            $rows_meta_stblid = array();
        }
    }
    foreach ($rows_meta_stblid as $row_meta_stblid) {
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
                if (trim($row[$column])) {
                    $stblid .= preg_replace($row_meta_stblid['pattern'], $row_meta_stblid['replacement'], $row[$column]);
                } else {
                    $valid = FALSE; // we found a column, but it is empty, therefore the stable id is invalidated
                }
            } else {
                $valid = FALSE;     // we didn't find a aolumn, therefore the stable id is invalidated
            }
        }
    }

    if ($valid) {
        return $stblid;
    } else {
        return "";
    }
}



$stblid = "";
$specimen_id = intval(filter_input(INPUT_POST, 'specimen_ID', FILTER_SANITIZE_NUMBER_INT));
if ($specimen_id) { // did we get a valid specimen-ID?
    $result_specimen = db_query("SELECT mc.collectionID, mc.source_id
                                 FROM tbl_specimens s
                                  LEFT JOIN tbl_management_collections mc ON mc.collectionID = s.collectionID
                                 WHERE specimen_ID = '$specimen_id'");
    if ($result_specimen && mysql_num_rows($result_specimen) > 0) { // we've found a valid source_id and collectionID
        $row_specimen = mysql_fetch_array($result_specimen);

        $stblid = makeStableIdentifier($row_specimen['source_id'], $row_specimen['collectionID']);
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