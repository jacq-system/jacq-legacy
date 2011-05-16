<?php
session_start();
require("inc/connect.php");
require_once ("inc/xajax/xajax_core/xajax.inc.php");
no_magic();

$xajax = new xajax();
$xajax->setRequestURI("ajax/chatServer.php");

$xajax->registerFunction("displaychat");
$xajax->registerFunction("insertchat");
$xajax->registerFunction("checklatest");
$xajax->registerFunction("chatIsOpen");
$xajax->registerFunction("chatIsClosed");

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - public shoutbox</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <?php $xajax->printJavascript('inc/xajax'); ?>
  <SCRIPT LANGUAGE="JavaScript">
  <!--
  function timer(){
    xajax_chatIsOpen();
    xajax_displaychat();
    window.setInterval("xajax_checklatest(xajax.getFormValues('chatform'))",5000);//reload timer
  }
  function stopEverything() {
    xajax_chatIsClosed();
  }
  //-->
  </SCRIPT>
  <STYLE TYPE="text/css">
    body,td,tr {
      font-weight: normal;
      font-size: 12px;
      line-height: 16px;
      font-family: helvetica;
      font-variant: normal;
      font-style: normal;
    }

    body {
      background:#d7d7d7;
    }

    H1 {
      font-weight: bold;
      font-size: 14px;
      line-height: 16px;
    }
  </style>
</head>

<body onLoad="timer()" onUnload="stopEverything()">
<div id=container style="border:1px solid #7a7a7a;width:500">
  <div id="chatinputdiv" id="chatinputdiv" style="width: 100%" >
    <form id="chatform" name="chatform" style="display:inline;">
      <table cellpadding=2 cellspacing=0 border=0 width="98%">
        <tr><td valign=top>
          <button onclick="xajax_insertchat(xajax.getFormValues('chatform')); return false;">send</button>
          <textarea id="chat" style="width: 100%" name="chat" rows="4"></textarea><br>
          <input type=hidden name="latestid" id="latestid" value="">
        </td></tr>
    </table>
  </form>
  </div>
  <div id="chatdiv" name="chatdiv" style="width: 100%"></div>
</div>
<br>
<div id="debugdiv" id="debugdiv">
</body>
</html>