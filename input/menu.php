<?php
session_start();
require("inc/connect.php");
require_once ("inc/xajax/xajax_core/xajax.inc.php");

$xajax = new xajax();
$xajax->setRequestURI("ajax/menuServer.php");

$xajax->registerFunction("checkChats");
$xajax->registerFunction("checkJacqLogin");

$_SESSION['litType'] = 0;
$_SESSION['taxType'] = 0;
if (!isset($_SESSION['chatPrivActive'])) {
    $_SESSION['chatPrivActive'] = 0;
}
if (!isset($_SESSION['chatActive'])) {
    $_SESSION['chatActive'] = 0;
}

$sql = "SELECT username, group_name
        FROM herbarinput_log.tbl_herbardb_users hu, herbarinput_log.tbl_herbardb_groups hg
        WHERE hu.groupID=hg.groupID
         AND hu.userID='".intval($_SESSION['uid'])."'";
$userdata = mysql_fetch_array(mysql_query($sql));

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - menu</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <style type="text/css">
    #logout { position:absolute; top:1em; right:1em; width:5em; }
    #info { position:absolute; top:1em; left:1em }
    #jacqThumb { position:absolute; top:15em; left:1em; }
  </style>
  <?php $xajax->printJavascript('inc/xajax'); ?>
  <script type="text/javascript" language="JavaScript">
    function timer(){
      window.setInterval("xajax_checkChats()",5000); //reload timer
      window.setInterval("xajax_checkJacqLogin()",5000); //reload timer
    }

    function openWindow(target,name) {
      options = "width=";
      if (screen.availWidth<1180)
        options += (screen.availWidth - 10) + ",height=";
      else
        options += "1180, height=";
      if (screen.availHeight<710)
        options += (screen.availHeight - 10);
      else
        options += "710";
      options += ", top=10,left=10,scrollbars=yes,resizable=yes";

      newWindow = window.open(target,name,options);
      newWindow.focus();
    }

    function openChat() {
      newWindow = window.open("chat.php", "chat", "width=520, height=500, bottom=10, right=10, scrollbars=yes, resizable=yes");
      newWindow.focus();
    }

    function openChatPriv() {
      newWindow = window.open("chatPriv.php", "chatPriv", "width=520, height=500, bottom=510, right=10, scrollbars=yes, resizable=yes");
      newWindow.focus();
    }

    function changePassword() {
      newWindow = window.open("editPassword.php", "password", "width=700, height=240, top=10, left=10, scrollbars=yes, resizeable=yes");
      newWindow .focus();
    }
</script>
</head>

<body onLoad="timer()">

<input class="button" type="button" value=" logout " onclick="self.location.href='logout.php'" id="logout">

<div id="info">
  <?php echo $userdata['username']; ?> / <?php echo $userdata['group_name']; ?><br>
  <input class="button" type="button" value="private chat" onClick="openChatPriv()"><br>
  <input class="button" type="button" value="public chat" onClick="openChat()">
</div>

<div align="center">
    <h1>Menu <a href="http://jacq.nhm-wien.ac.at/dokuwiki/doku.php?id=export_documentation" target="_blank"><img src="webimages/help.png" border="0" width="16" height="16" /></a></h1>
  <form Action="menu.php" Method="POST">
    <table>
      <tr align="left"><td >
<?php if (checkRight('btnTax')): ?>
        <input class="button" type="button" value="Taxonomy" onClick="openWindow('listTax.php','Species')">
<?php endif; ?>
      </td><td style="width:20px">&nbsp;</td><td>
<?php if (checkRight('chorol')): ?>
        <input class="button" type="button" value="Chorology" onClick="openWindow('editChorology.php','Chorology')">
<?php endif; ?>
      </td></tr>
      <tr align="left"><td>
<?php if (checkRight('btnLit')): ?>
        <input class="button" type="button" value="Literature" onClick="openWindow('listLit.php','Literature')">
<?php endif; ?>
      </td><td style="width:20px">&nbsp;</td><td></td></tr>
      <tr align="left"><td>
<?php if (checkRight('btnSpc')): ?>
        <input class="button" type="button" value="Specimens" onClick="openWindow('listSpecimens.php','Specimens')">
<?php endif; ?>
      </td><td style="width:20px">&nbsp;</td><td>
<?php if (checkRight('btnObs')): ?>
        <input class="button" type="button" value="Observations" onClick="openWindow('listObservations.php','Observations')">
<?php endif; ?>
      </td></tr>
      <tr align="left"><td>
<?php if (checkRight('btnImg')): ?>
        <input class="button" type="button" value="Images" onClick="openWindow('checkPictures.php','checkPictures')">
<?php endif; ?>
      </td><td style="width:20px">&nbsp;</td><td></td></tr>
      <tr align="left"><td>
<?php if (checkRight('btnNom')): ?>
        <input class="button" type="button" value="Nomenclature" onClick="openWindow('checkNomenclature.php','checkNomenclature')">
<?php endif; ?>
      </td><td style="width:20px">&nbsp;</td><td></td></tr>
      <tr align="left"><td>
<?php if (checkRight('batch')): ?>
        <input class="button" type="button" value="Batches" onclick="openWindow('manageBatch.php','manageBatch')">
<?php endif; ?>
      </td><td style="width:20px">&nbsp;</td><td>
<?php if (checkRight('batch')): ?>
        <input class="button" type="button" value="Batches file import" onclick="openWindow('fileImportBatch.php','fileImportBatch')">
<?php endif; ?>
      </td></tr>
      <tr align="left"><td>
<?php if (checkRight('btnSpc')): ?>
        <input class="button" type="button" value="Labels" onClick="openWindow('listLabel.php','Labels')">
<?php endif; ?>
      </td><td style="width:20px">&nbsp;</td><td></td></tr>
<?php if (checkRight('btnImport')): ?>
      <tr align="left"><td>
        <input class="button" type="button" value="Import" onClick="openWindow('listSpecimensImport.php','SpecimensImport')">
      </td><td style="width:20px">&nbsp;</td><td>
      </td></tr>
<?php endif; ?>
<?php if (checkRight('admin')): ?>
      <tr align="left"><td>
        <input class="button" type="button" value="edit Users" onClick="openWindow('listUsers.php','Users')">
      </td><td style="width:20px">&nbsp;</td><td>
        <input class="button" type="button" value="edit Groups" onClick="openWindow('listGroups.php','Groups')">
      </td></tr>
<?php endif; ?>
      <tr align="left"><td>
        <input class="button" type="button" value="change password" onclick="changePassword()">
      </td><td style="width:20px">&nbsp;</td><td></td></tr>
      </tr>
    </table>
  </form>
</div>

<div id="jacqThumb"></div>

</body>
</html>