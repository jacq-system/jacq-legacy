<?php
/**
 * this script checks for missing pictures (both database and harddisk)
 */
require("inc/init.php");
require("inc/init_xajax.php");

try {
    /* @var $db clsDbAccess */
    $db = clsDbAccess::Connect('INPUT');

    $dbst = $db->query("SELECT imgserver_IP FROM tbl_img_definition GROUP BY imgserver_IP LIMIT 1");
    $row = $dbst->fetch();
    $pictureServerIP = $row['imgserver_IP'];
}
catch (Exception $e) {
    exit($e->getMessage());
}


?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - list Images</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <style type="text/css">
    th { font-weight: bold; font-size: medium }
    tr { vertical-align: top }
    td { vertical-align: top }
    .missing { margin: 0px; padding: 0px }
    td.missing { vertical-align: middle }
  </style>
  <?php $xajaxObject->printJavascript('inc/xajax'); ?>
  <script type="text/javascript" language="JavaScript">
    function openBrowser(sel, server) {
      target = "http://" + server + "/database/img/imgBrowser.php?name=" + encodeURIComponent(sel);
      MeinFenster = window.open(target,"imgBrowser");
      MeinFenster.focus();
    }
    function editSpecimens(sel) {
      target = "editSpecimens.php?sel=" + encodeURIComponent(sel);
      options = "width=";
      if (screen.availWidth<990)
        options += (screen.availWidth - 10) + ",height=";
      else
        options += "990, height=";
      if (screen.availHeight<710)
        options += (screen.availHeight - 10);
      else
        options += "710";
      options += ", top=10,left=10,scrollbars=yes,resizable=yes";

      newWindow = window.open(target,"Specimens",options);
      newWindow.focus();
    }
    xajax.callback.global.onRequest = function() {
      xajax. $('loadingMsg').style.display = 'block';
      xajax. $('checkResults').style.display = 'none';
    };
    xajax.callback.global.onComplete = function() {
      xajax. $('loadingMsg').style.display = 'none';
      xajax. $('checkResults').style.display = 'block';
    };
  </script>
</head>

<body onload="xajax_dispatch('checkPictures', 'getLastScan', xajax.getFormValues('f'));">
<h1>check Images</h1>

<form action="" method="POST" name="f" id="f">

<table><tr>
<td>
  Server:
  <select size="1" name="serverIP" id="serverIP" onchange="xajax_dispatch('checkPictures', 'listInstitutions', xajax.getFormValues('f')); xajax_dispatch('checkPictures', 'getLastScan', xajax.getFormValues('f'));">
    <?php
    try {
        /* @var $dbst PDOStatement */
        $dbst = $db->query("SELECT imgserver_IP FROM tbl_img_definition GROUP BY imgserver_IP");
        foreach ($dbst as $row) {
            echo "<option value=\"{$row['imgserver_IP']}\"";
            if ($pictureServerIP == $row['imgserver_IP']) {
                echo " selected";
            }
            echo ">{$row['imgserver_IP']}</option>\n";
        }
    }
    catch (Exception $e) {
        exit($e->getMessage());
    }
    ?>
  </select>
</td><td width="10">
  &nbsp;
</td><td>
  Institution:
  <select size="1" name="source_id" id="source_id" onchange="xajax_dispatch('checkPictures', 'getLastScan', xajax.getFormValues('f'));">
    <option value="0">--- all ---</option>
    <?php
    try {
        /* @var $dbst PDOStatement */
        $dbst = $db->prepare("SELECT source_name, tbl_management_collections.source_id
                              FROM tbl_management_collections, herbarinput.meta, tbl_img_definition
                              WHERE tbl_management_collections.source_id = herbarinput.meta.source_id
                               AND tbl_management_collections.source_id = tbl_img_definition.source_id_fk
                               AND imgserver_IP = :IP
                              GROUP BY source_name
                              ORDER BY source_name");
        $dbst->execute(array(":IP" => $pictureServerIP));
        foreach ($dbst as $row) {
            echo "<option value=\"{$row['source_id']}\">{$row['source_name']}</option>\n";
        }
    }
    catch (Exception $e) {
        exit($e->getMessage());
    }
    ?>
  </select>
</td><td width="10">
  &nbsp;
</td><td>
  Family: <input type="text" name="family">
</td><td width="10">
  &nbsp;
</td><td>
  <input type="submit" name="btnCheck" value="check" onclick="xajax_dispatch('checkPictures', 'checkPictures', xajax.getFormValues('f')); return false;">
</td>
</tr></table>

<span id="lastScan"></span>
&nbsp;
<input type="submit" name="btnRescan" id="btnRescan" value="rescan server" onclick="xajax_dispatch('checkPictures', 'rescanPictureServer', xajax.getFormValues('f')); return false;">
&nbsp;
</form>

<div id="loadingMsg" style="display: none;"><img alt="loading..." src="webimages/loader.gif"></div>
<div id="checkResults"></div>

</body>
</html>