<?php

namespace Jacq\Jaxon;

class ListObservationsServer extends \Jaxon\CallableClass
{

    /**
     * sets the Date-dropdown for a given user
     */
    function getUserDate($id)
    {
        $sql = "SELECT DATE_FORMAT(timestamp,'%Y-%m-%d') as date
                FROM herbarinput_log.log_specimens ";
        if (intval($id)) {
            $sql .= "WHERE userID='" . intval($id) . "' ";
        }
        $sql .= "GROUP BY date
                 ORDER BY date";
        $result = dbi_query($sql);
        $selectData = "";
        while ($row = mysqli_fetch_array($result)) {
            $selectData .= "  <option>" . htmlspecialchars($row['date']) . "</option>\n";
        }

        $this->response->assign("user_date", "innerHTML", $selectData);

        return $this->response;
    }
}
