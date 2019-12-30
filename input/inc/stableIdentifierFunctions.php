<?php
/**
 * make a stable identifier by using the textes and patterns in meta_stblid
 *
 * if there are one or more table references in meta_stblid and one of them holds no data, the stable identifier is considered empty
 * if there is somethin in $stringInsteadColumn, this is used instead of the result of the query of the column stated in meta_stblid.table_column
 *
 * @param int $source_id source-ID
 * @param array $constraints holds the primary key used in the referenced table (if any)
 * @param int $collection_id = null collection-ID (optional)
 * @param string $stringInsteadColumn = null use this string instead of the stated column in meta_stblid.table_column (optional)
 * @return string the stable identifier
 */
function makeStableIdentifier($source_id, $constraints, $collection_id = null, $stringInsteadColumn = null)
{
    $stblid = "";   // holds the stable identifier
    $valid = TRUE;  // is the stable identifier valid?

    // first find a specific entry with source_id and collectionID if there is one
    if (!empty($collection_id)) {
        $result_meta_stblid = db_query("SELECT `text`, `table_column`, `pattern`, `replacement`
                                        FROM `meta_stblid`
                                        WHERE `source_id` = '$source_id'
                                         AND `collectionID` = '$collection_id'
                                        ORDER BY `sequence`");
    } else {
        $result_meta_stblid = false;
    }
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
            if (!$stringInsteadColumn) {
                $parts = explode(".", $row_meta_stblid['table_column']);
                $table = $parts[0];
                $column = $parts[1];

                $result = db_query("show index from $table where Key_name = 'PRIMARY'");
                $row = mysql_fetch_array($result);
                $primaryKey = $row['Column_name'];

                $result = db_query("SELECT $column
                                    FROM $table
                                    WHERE $primaryKey = '" . $constraints[$primaryKey] . "'");
                if ($result && mysql_num_rows($result) > 0) {
                    $row = mysql_fetch_array($result);
                    if (trim($row[$column])) {
                        $stblid .= preg_replace($row_meta_stblid['pattern'], $row_meta_stblid['replacement'], $row[$column]);
                    } else {
                        $valid = FALSE; // we found a column, but it is empty, therefore the stable id is invalidated
                    }
                } else {
                    $valid = FALSE;     // we didn't find a column, therefore the stable id is invalidated
                }
            } else {
                $stblid .= preg_replace($row_meta_stblid['pattern'], $row_meta_stblid['replacement'], $stringInsteadColumn);
            }
        }
    }

    if ($valid) {
        return $stblid;
    } else {
        return "";
    }
}


/**
 * get the latest stable identifier from tbl_specimens_stblid
 *
 * @param int $specimenID the specimen-ID
 * @return string the stable identifier
 */
function getStableIdentifier($specimenID)
{
    $result = db_query("SELECT stableIdentifier
                        FROM tbl_specimens_stblid
                        WHERE specimen_ID = '" . intval($specimenID) . "'
                        ORDER BY timestamp DESC
                        LIMIT 1");
    if ($result && mysql_num_rows($result) > 0) {
        $row = mysql_fetch_array($result);
        return $row['stableIdentifier'];
    } else {
        return "";
    }
}
