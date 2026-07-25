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
            $dbLink = DbAccess::ConnectTo('INPUT');

            $sql = "SELECT DATE_FORMAT(timestamp,'%Y-%m-%d') as date
                    FROM herbarinput_log.log_specimens ";
            if (intval($id)) {
                $sql .= "WHERE userID='" . intval($id) . "' ";
            }
            $sql .= "GROUP BY date
                     ORDER BY date";
            $result = $dbLink->query($sql);
            $selectData = "";
            while ($row = $result->fetch_array()) {
                $selectData .= "  <option>" . htmlspecialchars($row['date']) . "</option>\n";
            }

            $this->response->assign("user_date", "innerHTML", $selectData);
        } catch (Exception $e) {
            error_log("ListObservationsServer.getUserDate: " . $e->__toString() . "\n");
        }

        return $this->response;
    }
}
