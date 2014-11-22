<?php
session_start();
require_once ("../inc/xajax/xajax_core/xajax.inc.php");
require("../inc/connect.php");

function checkChats() {
    $objResponse = new xajaxResponse();

    $text = "";

    $sql = "SELECT ID, firstname, surname, tbl_chat_priv.timestamp
            FROM tbl_chat_priv, herbarinput_log.tbl_herbardb_users
            WHERE herbarinput_log.tbl_herbardb_users.userID = tbl_chat_priv.uid
             AND tid = '" . $_SESSION['uid'] . "'
             AND tbl_chat_priv.timestamp > subtime(now(), '0:00:30')
            ORDER BY tbl_chat_priv.timestamp DESC
            LIMIT 1";
    $result = mysql_query($sql);

    if (!$_SESSION['chatPrivActive'] && mysql_num_rows($result) > 0) {
        $row = mysql_fetch_array($result);
        if (strpos($_SESSION['chatPrivIDs'], $row['ID']) === false) {
            $_SESSION['chatPrivIDs'] .= " " . $row['ID'];
            $text .= "You got a private message from " . $row['firstname'] . " " . $row['surname'] . " at " . $row['timestamp'] . "\n\n";
        }
    }

    $sql = "SELECT ID, firstname, surname, tbl_chat.timestamp
            FROM tbl_chat, herbarinput_log.tbl_herbardb_users
            WHERE uid = userID
             AND tbl_chat.timestamp > subtime(now(), '0:00:30')
            ORDER BY tbl_chat.timestamp DESC
            LIMIT 1";
    $result = mysql_query($sql);

    if (!$_SESSION['chatActive'] && mysql_num_rows($result) > 0) {
        $row = mysql_fetch_array($result);
        if (strpos($_SESSION['chatIDs'], $row['ID']) === false) {
            $_SESSION['chatIDs'] .= " " . $row['ID'];
            $text .= "New public message from " . $row['firstname'] . " " . $row['surname'] . " at " . $row['timestamp'];
        }
    }

    if ($text) {
        $objResponse->alert($text);
    }

    return $objResponse;
}


function checkJacqLogin () {
    $objResponse = new xajaxResponse();

    $prefix_id = 'id';
    $prefix_name = 'name';
    $prefix_states = 'states';
    foreach ($_SESSION as $key => $val) {
        if (strpos($key, '__id')) {
            $prefix_id = substr($key, 0, -4);
        }
        if (strpos($key, '__name')) {
            $prefix_name = substr($key, 0, -6);
        }
        if (strpos($key, '__states')) {
            $prefix_states = substr($key, 0, -8);
        }
    }
    if ($prefix_id == $prefix_name && $prefix_id == $prefix_states && $prefix_name == $prefix_states) {
        $objResponse->assign("jacqThumb", "innerHTML", "<img src='webimages/jacqThumb.png'>");
    } else {
        $objResponse->assign("jacqThumb", "innerHTML", "");
    }

    return $objResponse;
}

/**
 * register all xajax-functions in this file
 */
$xajax = new xajax();
$xajax->registerFunction("checkChats");
$xajax->registerFunction("checkJacqLogin");
$xajax->processRequest();