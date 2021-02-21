<?php
session_start();
require("../inc/connect.php");
require __DIR__ . '/../vendor/autoload.php';

use Jaxon\Jaxon;
use Jaxon\Response\Response;

function checkChats() {
    $response = new Response();

    $text = "";

    $sql = "SELECT ID, firstname, surname, tbl_chat_priv.timestamp
            FROM tbl_chat_priv, herbarinput_log.tbl_herbardb_users
            WHERE herbarinput_log.tbl_herbardb_users.userID = tbl_chat_priv.uid
             AND tid = '" . $_SESSION['uid'] . "'
             AND tbl_chat_priv.timestamp > subtime(now(), '0:00:30')
            ORDER BY tbl_chat_priv.timestamp DESC
            LIMIT 1";
    $result = dbi_query($sql);

    if (!$_SESSION['chatPrivActive'] && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_array($result);
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
    $result = dbi_query($sql);

    if (!$_SESSION['chatActive'] && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_array($result);
        if (strpos($_SESSION['chatIDs'], $row['ID']) === false) {
            $_SESSION['chatIDs'] .= " " . $row['ID'];
            $text .= "New public message from " . $row['firstname'] . " " . $row['surname'] . " at " . $row['timestamp'];
        }
    }

    if ($text) {
        $response->alert($text);
    }

    return $response;
}


function checkJacqLogin () {
    $response = new Response();

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
        $response->assign("jacqThumb", "innerHTML", "<img src='webimages/jacqThumb.png'>");
    } else {
        $response->assign("jacqThumb", "innerHTML", "");
    }

    return $response;
}

/**
 * register all jaxon-functions in this file
 */
$jaxon = jaxon();
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "checkChats");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "checkJacqLogin");
$jaxon->processRequest();