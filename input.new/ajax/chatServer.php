<?php
session_start();
require("../inc/connect.php");
require __DIR__ . '/../vendor/autoload.php';

use Jaxon\Jaxon;
use Jaxon\Response\Response;

/**
 * jaxon-function for ...
 *
 * @return Response
 */
function displaychat() {
	$response = new Response();
	ob_start();

	$sql = "SELECT ID, firstname, surname, chat, tbl_chat.timestamp
	        FROM tbl_chat, herbarinput_log.tbl_herbardb_users
	        WHERE uid=userID
	        ORDER BY tbl_chat.timestamp DESC
	        LIMIT 10";
	$r = dbi_query($sql);
	echo '<table width="500" dir=\"ltr\" summary=\"Shoutbox formating\" cellpadding=2 cellspacing=0 border=0>';

	$bgcolor='#c2c2c2';
    $latestTableId = 0;
	while($row = mysqli_fetch_assoc($r)) {
        if (!$latestTableId) {
            $latestTableId = $row['ID'];
        }
		//format how you want
		echo "<tr bgcolor=$bgcolor>".
		     "<td nowrap width=\"70\" valign=\"top\">" . $row['firstname'] . " " . $row['surname'] . "<br>" . $row['timestamp'] . "</td>".
		     "<td width=\"10\">&nbsp;</td>".
		     "<td valign=\top\>" . nl2br($row['chat']) . "</td></tr>\n";

		// alternate row color
		if ($bgcolor=="#cccccc") {
			$bgcolor='#c2c2c2';
        } else {
			$bgcolor='#cccccc';
        }
  }
  echo '</table>';

  $response->script("document.getElementById('latestid').value='".$latestTableId."'");

  $response->assign('chatdiv', 'innerHTML', ob_get_clean());

  return $response;
}

/**
 * jaxon-function for ...
 *
 * @param string $formdata form data
 * @return Response
 */
function insertchat($formdata) {
	$response = new Response();
	ob_start();

	//ignore blank entries
	if (trim($formdata['chat']) != "") {
		$formdata['chat'] = htmlspecialchars($formdata['chat'], ENT_QUOTES);

		$sql = "INSERT INTO tbl_chat SET
		         uid  = '" . $_SESSION['uid'] . "',
		         chat = '" . dbi_escape_string($formdata['chat']) . "'";
        dbi_query($sql);

        //Empty the textarea
        $response->script("document.getElementById('chat').value=''");
    }

    //Any errors or debug can be displayed in the de-bug div
    $response->assign('debugdiv', 'innerHTML', ob_get_clean());

    //reload the chat display div with new message.
    $response-> appendResponse(displaychat());

    return $response;
}

/**
 * jaxon-function for ...
 *
 * @param string $formdata form data
 * @return Response
 */
function checklatest($formdata) {
	$response = new Response();

	//get most recent id in table
	$latestTableId = dbi_query("SELECT ID FROM tbl_chat ORDER BY timestamp DESC LIMIT 1")->fetch_assoc()['ID'];

	if($formdata['latestid'] != $latestTableId){
		//reload the chat display div
		$response-> appendResponse(displaychat());
	}

  return $response;
}

function chatIsOpen() {
	$response = new Response();
    $_SESSION['chatActive'] = 1;
    return $response;
}

function chatIsClosed() {
	$response = new Response();
    $_SESSION['chatActive'] = 0;
    return $response;
}

/**
 * register all jaxon-functions in this file
 */
$jaxon = jaxon();
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "displaychat");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "insertchat");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "checklatest");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "chatIsOpen");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "chatIsClosed");
$jaxon->processRequest();
