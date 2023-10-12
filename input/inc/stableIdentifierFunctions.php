<?php
/**
 * make a stable identifier by using the textes and patterns in meta_stblid
 *
 * if there are one or more table references in meta_stblid and one of them holds no data, the stable identifier is considered empty
 * if there is something in $stringInsteadColumn, this is used instead of the result of the query of the column stated in meta_stblid.table_column
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
        $result_meta_stblid = dbi_query("SELECT `text`, `table_column`, `pattern`, `replacement`, `alternative`
                                         FROM `meta_stblid`
                                         WHERE `source_id` = '$source_id'
                                          AND `collectionID` = '$collection_id'
                                         ORDER BY `sequence`");
    } else {
        $result_meta_stblid = false;
    }
    if ($result_meta_stblid && $result_meta_stblid->num_rows > 0) {
        $rows_meta_stblid = $result_meta_stblid->fetch_all(MYSQLI_ASSOC);
    } else {
        // no luck, so we search an entry which is valid for any collection of a given source_id
        $result_meta_stblid = dbi_query("SELECT `text`, `table_column`, `pattern`, `replacement`, `alternative`
                                         FROM `meta_stblid`
                                         WHERE `source_id` = '$source_id'
                                          AND `collectionID` IS NULL
                                         ORDER BY `sequence`");
        if ($result_meta_stblid && $result_meta_stblid->num_rows > 0) {
            $rows_meta_stblid = $result_meta_stblid->fetch_all(MYSQLI_ASSOC);
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

                $row = dbi_query("show index from $table where Key_name = 'PRIMARY'")->fetch_array();
                $primaryKey = $row['Column_name'];

                $result = dbi_query("SELECT $column
                                     FROM $table
                                     WHERE $primaryKey = '" . $constraints[$primaryKey] . "'");
                if ($result && $result->num_rows > 0) {
                    $row = $result->fetch_array();
                    if (trim($row[$column])) {
                        $stblid .= preg_replace($row_meta_stblid['pattern'], $row_meta_stblid['replacement'], $row[$column]);
                    } elseif (!empty($row_meta_stblid['alternative'])) {
                        $parts = _meta_stblid_parser($row_meta_stblid['alternative']);
                        foreach ($parts as $part) {
                            if ($part['token']) {
                                $tokenParts = explode(':', $part['text']);
                                $token = $tokenParts[0];
                                $subtoken = (isset($tokenParts[1])) ? $tokenParts[1] : '';  // for future add-on
                                switch ($token) {
                                    case 'specimenID':
                                        $stblid .= $constraints['specimen_ID'];
                                        break;
                                }
                            } else {
                                $stblid .= $part['text'];
                            }
                        }
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
    $result = dbi_query("SELECT stableIdentifier
                         FROM tbl_specimens_stblid
                         WHERE specimen_ID = '" . intval($specimenID) . "'
                          AND stableIdentifier IS NOT NULL
                         ORDER BY timestamp DESC
                         LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_array();
        return $row['stableIdentifier'];
    } else {
        return "";
    }
}


/**
 * parse text into parts and tokens (text within '<>')
 *
 * @param string $text text to tokenize
 * @return array found parts
 */
function _meta_stblid_parser (string $text): array
{
    $parts = explode('<', $text);
    $result = array(array('text' => $parts[0], 'token' => false));
    for ($i = 1; $i < count($parts); $i++) {
        $subparts = explode('>', $parts[$i]);
        $result[] = array('text' => $subparts[0], 'token' => true);
        if (!empty($subparts[1])) {
            $result[] = array('text' => $subparts[1], 'token' => false);
        }
    }
    return $result;
}
