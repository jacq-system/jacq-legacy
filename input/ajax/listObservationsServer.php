<?php
session_start();
require("../inc/connect.php");
require_once ("../inc/xajax/xajax_core/xajax.inc.php");

/**
 * xajax-function getUserDate
 *
 * sets the Date-dropdown for a given user
 *
 * @return xajaxResponse
 */
function getUserDate($id) {

  $sql = "SELECT DATE_FORMAT(timestamp,'%Y-%m-%d') as date
          FROM herbarinput_log.log_specimens ";
  if (intval($id)) $sql .= "WHERE userID='".intval($id)."' ";
  $sql .= "GROUP BY date
           ORDER BY date";
  $result = db_query($sql);
  $selectData = "";
  while($row=mysql_fetch_array($result)) {
    $selectData .= "  <option>".htmlspecialchars($row['date'])."</option>\n";
  }

	$objResponse = new xajaxResponse();
  $objResponse->assign("user_date", "innerHTML", $selectData);
  return $objResponse;
}


/**
 * register all xajax-functions in this file
 */
$xajax = new xajax();
$xajax->registerFunction("getUserDate");
$xajax->processRequest();