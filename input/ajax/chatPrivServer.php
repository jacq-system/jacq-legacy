<?php
session_start();
require_once ("../inc/xajax/xajax_core/xajax.inc.php");
require("../inc/connect.php");

/*********************\
|                     |
|  service functions  |
|                     |
\*********************/

function makeDropdownUsers($tid)
{
    $ret = "<select name=\"tid\" id=\"tid\" onchange=\"xajax_changetid(xajax.getFormValues('chatform'))\">"
         . "<option value=\"-1\">received (last hour, unseen)</option>\n";

    $sql = "SELECT userID, firstname, surname, login
            FROM herbarinput_log.tbl_herbardb_users
            WHERE active=1
             AND login IS NOT NULL
             AND login >= subtime(now(), '24:00:00')
             AND userID != {$_SESSION['uid']}
            ORDER BY surname, firstname";
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
                  . "{$row['firstname']} {$row['surname']} ({$row['login']})"
                  . "</option>\n";
        }
    }

    $sql = "SELECT userID, firstname, surname, login
            FROM herbarinput_log.tbl_herbardb_users
            WHERE active=1
             AND (login IS NULL OR login < subtime(now(), '24:00:00'))
             AND userID != {$_SESSION['uid']}
            ORDER BY surname, firstname";
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
                  . "{$row['firstname']} {$row['surname']} (" . (($row['login']) ? $row['login'] : "offline") . ")"
                  . "</option>\n";
        }
    }
    $ret .= "</select>\n";

    return $ret;
}

/*******************\
|                   |
|  xajax functions  |
|                   |
\*******************/

/**
 * xajax-function for ...
 *
 * @param integer $tid target-ID
 * @return xajaxResponse
 */
function displaychat($tid)
{
    $objResponse = new xajaxResponse();
    ob_start();

    if ($tid > 0) {
        $sql = "SELECT ID, userID, firstname, surname, chat, tbl_chat_priv.timestamp, seen, tid
                FROM tbl_chat_priv, herbarinput_log.tbl_herbardb_users
                WHERE herbarinput_log.tbl_herbardb_users.userID=tbl_chat_priv.uid
                 AND (tid='" . intval($tid) . "' AND uid='" . $_SESSION['uid'] . "'
                   OR uid='" . intval($tid) . "' AND tid='" . $_SESSION['uid'] . "')
                ORDER BY tbl_chat_priv.timestamp DESC
                LIMIT 10";
    } else {
        $sql = "SELECT ID, userID, firstname, surname, chat, tbl_chat_priv.timestamp, seen, tid
                FROM tbl_chat_priv, herbarinput_log.tbl_herbardb_users
                WHERE herbarinput_log.tbl_herbardb_users.userID=tbl_chat_priv.uid
                 AND tid='" . $_SESSION['uid'] . "'
                 AND (tbl_chat_priv.timestamp>subtime(now(), '1:00:00') OR tbl_chat_priv.seen = 0)
                ORDER BY tbl_chat_priv.timestamp DESC
                LIMIT 10";
    }
    $r = db_query($sql);
    $chat = '<table width="500" dir=\"ltr\" summary=\"Shoutbox formating\" cellpadding=2 cellspacing=0 border=0>';

    $bgcolor='#c2c2c2';
    while($row=mysql_fetch_assoc($r)){
        $bold = ($row['seen'] == 0 && $row['tid'] == $_SESSION['uid']) ? 'style="font-weight:bold"' : '';
        $onclick = "onclick=\"xajax_changeStatus('{$row['userID']}', '{$row['ID']}');\"";
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
    $objResponse->assign('chatdiv', 'innerHTML', $chat);

    $latestTableId = (mysql_num_rows($r)>0) ? mysql_result($r,0,0) : 0;
    $objResponse->script("document.getElementById('latestid').value='".$latestTableId."'");

    $objResponse->assign('spn_tid', 'innerHTML', makeDropdownUsers($tid));

    $latestUserId = mysql_result(mysql_query("SELECT userID FROM herbarinput_log.tbl_herbardb_users ORDER BY timestamp DESC LIMIT 1"),0,0);
    $objResponse->script("document.getElementById('latestuid').value='".$latestUserId."'");

    if ($tid > 0) {
        $objResponse->call("enableSend");
    } else {
        $objResponse->call("disableSend");
    }

    //Any errors or debug can be displayed in the de-bug div
    $objResponse->assign('debugdiv', 'innerHTML', ob_get_clean());

    return $objResponse;
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
 * xajax-function for ...
 *
 * @param string $formdata form data
 * @return xajaxResponse
 */
function insertchat($formdata)
{
    $objResponse = new xajaxResponse();
    ob_start();

    //ignore blank entries
    if (trim($formdata['chat'])!="") {

        $formdata['name'] = htmlspecialchars($formdata['name'], ENT_QUOTES);
        $formdata['chat'] = htmlspecialchars($formdata['chat'], ENT_QUOTES);

        $sql = "INSERT INTO tbl_chat_priv SET
                uid='".$_SESSION['uid']."',
                tid='".$formdata['tid']."',
                chat='".mysql_real_escape_string($formdata['chat'])."'";
        db_query($sql);

        //Empty the textarea
        $objResponse->script("document.getElementById('chat').value=''");
    }

    //Any errors or debug can be displayed in the de-bug div
    $objResponse->assign('debugdiv', 'innerHTML', ob_get_clean());

    //reload the chat display div with new message.
    $objResponse->loadCommands(displaychat($formdata['tid']));

    return $objResponse;
}

/**
 * xajax-function for ...
 *
 * @param string $formdata form data
 * @return xajaxResponse
 */
function checklatest($formdata)
{
    $objResponse = new xajaxResponse();

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
        $objResponse->loadCommands(displaychat($formdata['tid'])); //reload the chat display div
    }

    return $objResponse;
}

/**
 * helper-function: chat-window is open
 *
 * @return xajaxResponse
 */
function chatIsOpen()
{
    $objResponse = new xajaxResponse();
    $_SESSION['chatPrivActive'] = 1;

    return $objResponse;
}

/**
 * helper-function: chat-window is closed
 *
 * @return xajaxResponse
 */
function chatIsClosed()
{
    $objResponse = new xajaxResponse();
    $_SESSION['chatPrivActive'] = 0;

    return $objResponse;
}

/**
 * register all xajax-functions in this file
 */
$xajax = new xajax();
$xajax->registerFunction("displaychat");
$xajax->registerFunction("changeStatus");
$xajax->registerFunction("changetid");
$xajax->registerFunction("insertchat");
$xajax->registerFunction("checklatest");
$xajax->registerFunction("chatIsOpen");
$xajax->registerFunction("chatIsClosed");
$xajax->processRequest();