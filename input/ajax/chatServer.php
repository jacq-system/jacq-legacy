<?php
session_start();
require_once ("../inc/xajax/xajax_core/xajax.inc.php");
require("../inc/connect.php");

/**
 * xajax-function for ...
 *
 * @return xajaxResponse
 */
function displaychat() {
	$objResponse = new xajaxResponse();
	ob_start();

	$sql = "SELECT firstname, surname, chat, tbl_chat.timestamp
	        FROM tbl_chat, herbarinput_log.tbl_herbardb_users
	        WHERE uid=userID
	        ORDER BY tbl_chat.timestamp DESC
	        LIMIT 10";
	$r = db_query($sql);
	echo '<table width="500" dir=\"ltr\" summary=\"Shoutbox formating\" cellpadding=2 cellspacing=0 border=0>';

	$bgcolor='#c2c2c2';
	while($row=mysql_fetch_assoc($r)){
		//format how you want
		echo "<tr bgcolor=$bgcolor>".
		     "<td nowrap width=\"70\" valign=\"top\">" . $row['firstname'] . " " . $row['surname'] . "<br>" . $row['timestamp'] . "</td>".
		     "<td width=\"10\">&nbsp;</td>".
		     "<td valign=\top\>" . nl2br($row['chat']) . "</td></tr>\n";

		// alternate row color
		if ($bgcolor=="#cccccc")
			$bgcolor='#c2c2c2';
    else
			$bgcolor='#cccccc';
  }
  echo '</table>';

  $latestTableId = (mysql_num_rows($r)>0) ? mysql_result($r,0,0) : 0;
  $objResponse->script("document.getElementById('latestid').value='".$latestTableId."'");

  $objResponse->assign('chatdiv', 'innerHTML', ob_get_clean());

  return $objResponse;
}

/**
 * xajax-function for ...
 *
 * @param string $formdata form data
 * @return xajaxResponse
 */
function insertchat($formdata) {
	$objResponse = new xajaxResponse();
	ob_start();

	//ignore blank entries
	if (trim($formdata['chat'])!="") {

		$formdata['name'] = htmlspecialchars($formdata['name'], ENT_QUOTES);
		$formdata['chat'] = htmlspecialchars($formdata['chat'], ENT_QUOTES);

		$sql = "INSERT INTO tbl_chat SET
		        uid='".$_SESSION['uid']."',
		        chat='".mysql_real_escape_string($formdata['chat'])."'";
  	db_query($sql);

  	//Empty the textarea
    $objResponse->script("document.getElementById('chat').value=''");
  }

  //Any errors or debug can be displayed in the de-bug div
  $objResponse->assign('debugdiv', 'innerHTML', ob_get_clean());

  //reload the chat display div with new message.
  $objResponse->loadCommands(displaychat());

  return $objResponse;
}

/**
 * xajax-function for ...
 *
 * @param string $formdata form data
 * @return xajaxResponse
 */
function checklatest($formdata) {
	$objResponse = new xajaxResponse();

	//get most recent id in table
	$latestTableId=mysql_result(mysql_query("SELECT * FROM tbl_chat ORDER BY timestamp DESC LIMIT 1"),0,0);

	if($formdata['latestid']!=$latestTableId){
		//reload the chat display div
		$objResponse->loadCommands(displaychat());
	}

  return $objResponse;
}

function chatIsOpen() {
	$objResponse = new xajaxResponse();
  $_SESSION['chatActive'] = 1;
  return $objResponse;
}
function chatIsClosed() {
	$objResponse = new xajaxResponse();
  $_SESSION['chatActive'] = 0;
  return $objResponse;
}

/**
 * register all xajax-functions in this file
 */
$xajax = new xajax();
$xajax->registerFunction("displaychat");
$xajax->registerFunction("insertchat");
$xajax->registerFunction("checklatest");
$xajax->registerFunction("chatIsOpen");
$xajax->registerFunction("chatIsClosed");
$xajax->processRequest();