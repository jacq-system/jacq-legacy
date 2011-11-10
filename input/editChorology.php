<?php
session_start();
require("inc/connect.php");
require_once ("inc/xajax/xajax_core/xajax.inc.php");
no_magic();

$xajax = new xajax();
$xajax->setRequestURI("ajax/editChorologyServer.php");

$xajax->registerFunction("projectChanged");
$xajax->registerFunction("projectDataChanged");
$xajax->registerFunction("editDistribution");
$xajax->registerFunction("updateDistribution");
$xajax->registerFunction("editChorology");
$xajax->registerFunction("updateChorology");
$xajax->registerFunction("changeChorology");


//-------------------------------
//---------- functions ----------
//-------------------------------

function makeDropdown($name, $select, $value, $text, $onchange = '')
{
    echo "<select name='$name'";
    if ($onchange) echo " onchange=\"$onchange\"";
    echo ">\n";
    for ($i = 0; $i < count($value); $i++) {
        echo "  <option";
        if ($value[$i] != $text[$i]) echo " value=\"" . $value[$i] . "\"";
        if ($select == $value[$i]) print " selected";
        echo ">" . htmlspecialchars($text[$i]) . "</option>\n";
    }
    echo "</select>\n";
}


//--------------------------
//---------- main ----------
//--------------------------

$status = array('', 'everything');
$result = db_query("SELECT status, statusID FROM tbl_tax_status ORDER BY status");
if ($result && mysql_num_rows($result) > 0) {
    while ($row = mysql_fetch_array($result)) {
        $status[] = $row['status'] . " <" . $row['statusID'] . ">";
    }
}

$rank = array('');
$result = db_query("SELECT rank, tax_rankID FROM tbl_tax_rank ORDER BY rank");
if ($result && mysql_num_rows($result) > 0) {
    while ($row = mysql_fetch_array($result)) {
        $rank[] = $row['rank'] . " <" . $row['tax_rankID'] . ">";
    }
}

$projects = array('text' => array(''), 'value' => array(''));
$result = db_query("SELECT project_name, project_ID FROM projects.tbl_projects ORDER BY project_name");
if ($result && mysql_num_rows($result) > 0) {
    while ($row = mysql_fetch_array($result)) {
        $projects['text'][]  = $row['project_name'];
        $projects['value'][] = $row['project_ID'];
    }
}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - edit Chorology</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <?php $xajax->printJavascript('inc/xajax'); ?>
  <script src="js/lib/jQuery/jquery.min.js" type="text/javascript"></script>
  <script src="inc/jQuery/jquery.fixedtableheader.min.js" type="text/javascript"></script>
  <script type="text/javascript" language="JavaScript">
    function checkNation(taxonID, checked) {
        if (checked) {
            document.getElementsByName('choroln_lock_' + taxonID)[0].value++;
            document.getElementsByName('choroln_' + taxonID)[0].checked = true;
            document.getElementsByName('choroln_' + taxonID)[0].disabled = true;
        } else {
            document.getElementsByName('choroln_lock_' + taxonID)[0].value--;
            if (document.getElementsByName('choroln_lock_' + taxonID)[0].value == 0) {
                document.getElementsByName('choroln_' + taxonID)[0].disabled = false;
            }
        }
    }

    function chorologyChanged(taxonID, provinceID) {
        var parts = new Array();
        for (var i = 0; i < 5; i++) {
            if (typeof(document.f.elements['chorol_' + i + '_' + taxonID + '_' + provinceID]) != 'undefined') {
                parts[i] = document.f.elements['chorol_' + i + '_' + taxonID + '_' + provinceID].value;
            } else {
                break;
            }
        }
        xajax_changeChorology(parts, taxonID, provinceID);
    }
  </script>
</head>

<body>

<b>choose project parameters</b><br>
<form method="POST" name="f" id="f">
<div style="margin-left:2em;"><?php makeDropdown("project", '', $projects['value'], $projects['text'], "xajax_projectChanged(xajax.getFormValues('f'));"); ?></div>
<div style="margin-left:2em;" id="projectSource"></div>
<div style="margin-left:2em;" id="projectNation"></div>
<p></p>

<table cellspacing="5" cellpadding="0">
<tr>
  <td align="right">&nbsp;<b>Family:</b></td>
    <td><input type="text" name="family" <?php if ($_SESSION['editFamily']) echo "disabled"; ?>></td>
  <td align="right">&nbsp;<b>Genus:</b></td>
    <td><input type="text" name="genus"></td>
  <td align="right">&nbsp;<b>Species:</b></td>
    <td><input type="text" name="species"></td>
</tr><tr>
  <td align="right">&nbsp;<b>Status:</b></td>
    <td><?php makeDropdown("status",'',$status,$status); ?></td>
  <td align="right">&nbsp;<b>Rank:</b></td>
    <td><?php makeDropdown("rank",'',$rank,$rank); ?></td>
  <td align="right">&nbsp;<b>Author:</b></td>
    <td><input type="text" name="author"></td>
</tr><tr>
  <td align="right">&nbsp;<b>Annotation:</b></td>
    <td colspan="5"><input type="text" name="annotation" size="89"></td>
</tr>
</table>
<input class="button" type="submit" name="search" value=" search " onclick="xajax_editDistribution(xajax.getFormValues('f')); return false;">

<p></p>
<div id="xajaxResult"></div>
</form>