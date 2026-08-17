<?php

namespace Jacq;

class Permission
{
    /**
     * Determines if the given permission exists for the current user.
     *
     * @param string $permission The name of the permission to check.
     * @return bool Returns true if the permission exists, false otherwise.
     */
    public static function has(string $permission): bool
    {
        try {
            $db = DbAccess::ConnectTo('INPUT');

            $row = $db->queryCatch("SELECT *
                                    FROM herbarinput_log.tbl_herbardb_users, herbarinput_log.tbl_herbardb_groups
                                    WHERE herbarinput_log.tbl_herbardb_users.groupID = herbarinput_log.tbl_herbardb_groups.groupID
                                     AND userID = '" . intval($_SESSION['uid']) . "'")
                      ->fetch_array();
            return (!empty($row[$permission]));
        } catch (Exception $e) {
            error_log("SEVERE SQL-ERROR IN CLASS. USER-ID = {$_SESSION['uid']}\n" . $e->__toString());
        }
        return false;
    }

    /**
     * Checks if the specified table may be unlocked for the current user session.
     *
     * @param string $table The name of the table to check for unlocking permission.
     * @return bool Returns true if the table may be unlocked, false otherwise
     */
    public static function mayUnlock(string $table): bool
    {
        try {
            $db = DbAccess::ConnectTo('INPUT');

            $result = $db->queryCatch("SELECT `table`
                                       FROM herbarinput_log.tbl_herbardb_unlock
                                       WHERE `table` = " . $db->quoteString($table) . "
                                        AND groupID = '" . intval($_SESSION['gid']) . "'");
            return ($result->num_rows > 0);
        } catch (Exception $e) {
            error_log("SEVERE SQL-ERROR IN CLASS. USER-ID = {$_SESSION['uid']}\n" . $e->__toString());
        }
        return false;
    }

}
