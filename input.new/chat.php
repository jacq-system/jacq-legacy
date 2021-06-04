<?php
session_start();
require("inc/connect.php");
require __DIR__ . '/vendor/autoload.php';

use Jaxon\Jaxon;

$jaxon = jaxon();
$jaxon->setOption('core.request.uri', 'ajax/chatServer.php');

$jaxon->register(Jaxon::CALLABLE_FUNCTION, "displaychat");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "insertchat");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "checklatest");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "chatIsOpen");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "chatIsClosed");

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - public shoutbox</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <?php echo $jaxon->getScript(true, true); ?>
  <SCRIPT LANGUAGE="JavaScript">
  <!--
  function timer(){
    jaxon_chatIsOpen();
    jaxon_displaychat();
    window.setInterval("jaxon_checklatest(jaxon.getFormValues('chatform'))",5000);//reload timer
  }
  function stopEverything() {
    jaxon_chatIsClosed();
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
          <button onclick="jaxon_insertchat(jaxon.getFormValues('chatform')); return false;">send</button>
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