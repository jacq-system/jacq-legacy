<?php
/**
 * the complete file is no longer needed
 * kept just for the records :-)
 */
/**
 * this script checks for missing pictures (both database and harddisk)
 */
require("inc/init.php");
require("inc/init_xajax.php");
require("inc/cssf.php");

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
  <title>herbardb - check imgBrowser pictures</title>
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
<?php
$cf = new CSSF();
$cf->setEcho(false);

// Output helper links to switch between different checkPictures versions
echo $cf->label(6, 0, 'Djatoka', 'checkPictures.php' );
echo $cf->label(15, 0, 'ImageBrowser' );
?>

<h1>check imgBrowser pictures</h1>

<form action="" method="POST" name="f" id="f">

<table><tr>
<td>
  Server:
  <select size="1" name="serverIP" id="serverIP" onchange="xajax_dispatch('checkPictures', 'listInstitutions', xajax.getFormValues('f')); xajax_dispatch('checkPictures', 'getLastScan', xajax.getFormValues('f'));">
    <?php
    try {
        /* @var $dbst PDOStatement */
        $dbst = $db->query("SELECT imgserver_IP FROM tbl_img_definition WHERE `is_djatoka` = 0 GROUP BY imgserver_IP");
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
