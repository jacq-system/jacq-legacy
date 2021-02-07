<?php
session_start();
require("../inc/connect.php");
require __DIR__ . '/../vendor/autoload.php';

use Jaxon\Jaxon;
use Jaxon\Response\Response;

/**
 * jaxon-function getUserDate
 *
 * sets the Date-dropdown for a given user
 *
 * @return Response
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
    $result = db_query($sql);
    $selectData = "";
    while($row = mysql_fetch_array($result)) {
        $selectData .= "  <option>" . htmlspecialchars($row['date']) . "</option>\n";
    }

    $response = new Response();
    $response->assign("user_date", "innerHTML", $selectData);
    return $response;
}


/**
 * register all jaxon-functions in this file
 */
$jaxon = jaxon();
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "getUserDate");
$jaxon->processRequest();