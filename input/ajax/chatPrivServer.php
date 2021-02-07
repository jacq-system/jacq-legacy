<?php
session_start();
require("../inc/connect.php");
require __DIR__ . '/../vendor/autoload.php';

use Jaxon\Jaxon;
use Jaxon\Response\Response;

/*********************\
|                     |
|  service functions  |
|                     |
\*********************/

function makeDropdownUsers($tid)
{
    $ret = "<select name=\"tid\" id=\"tid\" onchange=\"jaxon_changetid(jaxon.getFormValues('chatform'))\">"
         . "<option value=\"-1\">received (last hour, unseen)</option>\n";

    $sql = "SELECT hu.userID, hu.firstname, hu.surname, hu.login, m.`source_code`
            FROM herbarinput_log.tbl_herbardb_users hu
            LEFT JOIN `herbarinput`.`meta` m ON m.`source_id` = hu.`source_id`
            WHERE hu.active=1
             AND hu.login IS NOT NULL
             AND hu.login >= subtime(now(), '24:00:00')
             AND hu.userID != {$_SESSION['uid']}
            ORDER BY m.`source_code`, hu.surname, hu.firstname";
    $result = db_query($sql);
    while ($row = mysql_fetch_array($result)) {
        if (trim($row['firstname']) || trim($row['surname'])) {
            $sql = "SELECT ID
                    FROM tbl_chat_priv
                    WHERE uid = {$row['userID']}
                     AND tid = {$_SESSION['uid']}
                     AND seen = 0";
            $checkResult = db_query($sql);
            $ret .= "<option value=\"{$row['userID']}\""
                  . ((mysql_num_rows($checkResult) > 0) ? " style=\"font-weight:bold;\"" : "")
                  . (($row['userID'] == $tid) ? " selected" : "")
                  . ">"
                  . "{$row['firstname']} {$row['surname']} [{$row['source_code']}] ({$row['login']})"
                  . "</option>\n";
        }
    }

    $sql = "SELECT hu.userID, hu.firstname, hu.surname, hu.login, m.`source_code`
            FROM herbarinput_log.tbl_herbardb_users hu
            LEFT JOIN `herbarinput`.`meta` m ON m.`source_id` = hu.`source_id`
            WHERE hu.active=1
             AND (hu.login IS NULL OR hu.login < subtime(now(), '24:00:00'))
             AND hu.userID != {$_SESSION['uid']}
            ORDER BY m.`source_code`, hu.surname, hu.firstname";
    $result = db_query($sql);
    while ($row=mysql_fetch_array($result)) {
        if (trim($row['firstname']) || trim($row['surname'])) {
            $sql = "SELECT ID
                    FROM tbl_chat_priv
                    WHERE uid = {$row['userID']}
                     AND tid = {$_SESSION['uid']}
                     AND seen = 0";
            $checkResult = db_query($sql);
            $ret .= "<option value=\"{$row['userID']}\""
                  . ((mysql_num_rows($checkResult) > 0) ? " style=\"font-weight:bold;\"" : "")
                  . (($row['userID'] == $tid) ? " selected" : "")
                  . ">"
                  . "{$row['firstname']} {$row['surname']} [{$row['source_code']}] (" . (($row['login']) ? $row['login'] : "offline") . ")"
                  . "</option>\n";
        }
    }
    $ret .= "</select>\n";

    return $ret;
}

/*******************\
|                   |
|  jaxon functions  |
|                   |
\*******************/

/**
 * jaxon-function for ...
 *
 * @param integer $tid target-ID
 * @return Response
 */
function displaychat($tid)
{
    $response = new Response();
    ob_start();

    if ($tid > 0) {
        $sql = "SELECT ID, userID, firstname, surname, chat, tbl_chat_priv.timestamp, seen, tid
                FROM tbl_chat_priv, herbarinput_log.tbl_herbardb_users
                WHERE herbarinput_log.tbl_herbardb_users.userID=tbl_chat_priv.uid
                 AND (tid='" . intval($tid) . "' AND uid='" . $_SESSION['uid'] . "'
                   OR uid='" . intval($tid) . "' AND tid='" . $_SESSION['uid'] . "')
                ORDER BY tbl_chat_priv.timestamp DESC
                LIMIT 100";
    } else {
        $sql = "SELECT ID, userID, firstname, surname, chat, tbl_chat_priv.timestamp, seen, tid
                FROM tbl_chat_priv, herbarinput_log.tbl_herbardb_users
                WHERE herbarinput_log.tbl_herbardb_users.userID=tbl_chat_priv.uid
                 AND tid='" . $_SESSION['uid'] . "'
                 AND (tbl_chat_priv.timestamp>subtime(now(), '1:00:00') OR tbl_chat_priv.seen = 0)
                ORDER BY tbl_chat_priv.timestamp DESC
                LIMIT 100";
    }
    $r = db_query($sql);
    $chat = '<table width="500" dir=\"ltr\" summary=\"Shoutbox formating\" cellpadding=2 cellspacing=0 border=0>';

    $bgcolor='#c2c2c2';
    while($row=mysql_fetch_assoc($r)){
        $bold = ($row['seen'] == 0 && $row['tid'] == $_SESSION['uid']) ? 'style="font-weight:bold"' : '';
        $onclick = "onclick=\"jaxon_changeStatus('{$row['userID']}', '{$row['ID']}');\"";
        $chat .= "<tr bgcolor=\"$bgcolor\">".
                 "<td nowrap $bold width=\"70\" valign=\"top\" $onclick>" . $row['firstname'] . " " . $row['surname'] . "<br>" . $row['timestamp'] . "</td>".
                 "<td width=\"10\">&nbsp;</td>".
                 "<td $bold valign=\"top\" $onclick>" . nl2br($row['chat']) . "</td></tr>\n";

        // alternate row color
        if ($bgcolor=="#cccccc") {
            $bgcolor='#c2c2c2';
        } else {
            $bgcolor='#cccccc';
        }
    }
    $chat .= '</table>';
    $response->assign('chatdiv', 'innerHTML', $chat);

    $latestTableId = (mysql_num_rows($r)>0) ? mysql_result($r,0,0) : 0;
    $response->script("document.getElementById('latestid').value='".$latestTableId."'");

    $response->assign('spn_tid', 'innerHTML', makeDropdownUsers($tid));

    $latestUserId = mysql_result(mysql_query("SELECT userID FROM herbarinput_log.tbl_herbardb_users ORDER BY timestamp DESC LIMIT 1"),0,0);
    $response->script("document.getElementById('latestuid').value='".$latestUserId."'");

    if ($tid > 0) {
        $response->call("enableSend");
    } else {
        $response->call("disableSend");
    }

    //Any errors or debug can be displayed in the de-bug div
    $response->assign('debugdiv', 'innerHTML', ob_get_clean());

    return $response;
}

function changeStatus($userID, $ID)
{
    $sql = "UPDATE tbl_chat_priv SET
             timestamp = timestamp,
             seen = 1
            WHERE ID = '" . intval($ID) . "'";
    db_query($sql);

    return displaychat($userID);
}

function changetid($formdata)
{
    return displaychat($formdata['tid']);
}

/**
 * jaxon-function for ...
 *
 * @param string $formdata form data
 * @return Response
 */
function insertchat($formdata)
{
    $response = new Response();
    ob_start();

    //ignore blank entries
    if (trim($formdata['chat'])!="") {

        $formdata['chat'] = htmlspecialchars($formdata['chat'], ENT_QUOTES);

        $sql = "INSERT INTO tbl_chat_priv SET
                uid='".$_SESSION['uid']."',
                tid='".$formdata['tid']."',
                chat='".mysql_real_escape_string($formdata['chat'])."'";
        db_query($sql);

        //Empty the textarea
        $response->script("document.getElementById('chat').value=''");
    }

    //Any errors or debug can be displayed in the de-bug div
    $response->assign('debugdiv', 'innerHTML', ob_get_clean());

    //reload the chat display div with new message.
    $response->appendResponse(displaychat($formdata['tid']));

    return $response;
}

/**
 * jaxon-function for ...
 *
 * @param string $formdata form data
 * @return Response
 */
function checklatest($formdata)
{
    $response = new Response();

    //get most recent id in table tbl_chat_priv
    if ($formdata['tid']>0) {
        $sql = "SELECT ID
                FROM tbl_chat_priv
                WHERE (tid='" . intval($formdata['tid']) . "' AND uid='" . $_SESSION['uid'] . "'
                 OR uid='" . intval($formdata['tid']) . "' AND tid='" . $_SESSION['uid'] . "')
                ORDER BY timestamp DESC
                LIMIT 1";
    } else {
        $sql = "SELECT ID
                FROM tbl_chat_priv
                WHERE tid='" . $_SESSION['uid'] . "'
                 AND timestamp>subtime(now(), '1:00:00')
                ORDER BY timestamp DESC
                LIMIT 1";
    }
    $r = mysql_query($sql);
    $latestTableId = (mysql_num_rows($r)>0) ? mysql_result($r,0,0) : 0;
    $latestUserId = mysql_result(mysql_query("SELECT userID FROM herbarinput_log.tbl_herbardb_users ORDER BY timestamp DESC LIMIT 1"),0,0);
    if($formdata['latestid']!=$latestTableId || $formdata['latestuid']!=$latestUserId){
        $response->appendResponse(displaychat($formdata['tid'])); //reload the chat display div
    }

    return $response;
}

/**
 * helper-function: chat-window is open
 *
 * @return Response
 */
function chatIsOpen()
{
    $response = new Response();
    $_SESSION['chatPrivActive'] = 1;

    return $response;
}

/**
 * helper-function: chat-window is closed
 *
 * @return Response
 */
function chatIsClosed()
{
    $response = new Response();
    $_SESSION['chatPrivActive'] = 0;

    return $response;
}

/**
 * register all jaxon-functions in this file
 */
$jaxon = jaxon();
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "displaychat");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "changeStatus");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "changetid");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "insertchat");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "checklatest");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "chatIsOpen");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "chatIsClosed");
$jaxon->processRequest();
