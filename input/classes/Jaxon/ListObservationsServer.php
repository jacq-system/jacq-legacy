<?php

namespace Jacq\Jaxon;

use Jacq\DbAccess;
use Exception;

class ListObservationsServer extends \Jaxon\CallableClass
{

    /**
     * sets the Date-dropdown for a given user
     */
    function getUserDate($id)
    {
        try {
            $db = DbAccess::ConnectTo('INPUT');

            $sql = "SELECT DATE_FORMAT(timestamp,'%Y-%m-%d') as date
                    FROM herbarinput_log.log_specimens ";
            if (intval($id)) {
                $sql .= "WHERE userID='" . intval($id) . "' ";
            }
            $sql .= "GROUP BY date
                     ORDER BY date";
            $result = $db->query($sql);
            $selectData = "";
            while ($row = $result->fetch_array()) {
                $selectData .= "  <option>" . htmlspecialchars($row['date']) . "</option>\n";
            }

            $this->response->assign("user_date", "innerHTML", $selectData);
        } catch (Exception $e) {
            error_log("SEVERE SQL-ERROR IN CLASS. USER-ID = {$_SESSION['uid']}\n" . $e->__toString());
        }

        return $this->response;
    }
}
