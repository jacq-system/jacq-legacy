<?php
session_start();
require("inc/connect.php");
require_once ("inc/xajax/xajax.inc.php");

$xajax = new xajax("ajax/taxamatch2compareServer.php");
$xajax->registerFunction("dispatcher");


// make dropdown for text endings
$sql = "SELECT bot_rank_suffix, zoo_rank_suffix
        FROM tbl_tax_rank
        WHERE rank = 'family'";
$res = db_query($sql);
$dropdownEnding = "<select name='searchtextEnding'>\n"
                . "<option value=''>-</option>\n";
while ($row = mysql_fetch_array($res)) {
    $dropdownEnding .= "<option value='" . substr($row['bot_rank_suffix'], 1) . "'>"
                     . $row['bot_rank_suffix'] . "</option>\n";
    $dropdownEnding .= "<option value='" . substr($row['zoo_rank_suffix'], 1) . "'>"
                     . $row['zoo_rank_suffix'] . "</option>\n";
}
$dropdownEnding .= "</select>\n";

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - taxamatch</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <style>
    body { background-color:lightgreen; }
  </style>
  <?php $xajax->printJavascript('inc/xajax'); ?>
</head>

<body>
<p>
  <form Action="<?php echo $_SERVER['PHP_SELF']; ?>" Method="POST" name="f" id="f">
    <table>
      <tr><td></td><td>family suffix</td></tr>
      <tr>
        <td><input type="text" name="searchtext" style="width:30em;"></td>
        <td><?php echo $dropdownEnding; ?></td>
      </tr><tr>
        <td valign="top">
          <input type="submit" value="search species" name="searchSpecies" onclick="xajax_dispatcher('showLev', 'species', xajax.getFormValues('f')); return false;">
          <input type="submit" value="search genus" name="searchGenus" onclick="xajax_dispatcher('showLev', 'genus', xajax.getFormValues('f')); return false;">
          <input type="submit" value="search family" name="searchFamily" onclick="xajax_dispatcher('showLev', 'family', xajax.getFormValues('f')); return false;">
        </td>
        <td>
          <fieldset>
            <input type="radio" name="method" value="lev" checked> Levenshtein included in PHP<br>
            <input type="radio" name="method" value="sp"> Levenshtein programmed as stored Procedure (<b>very slow!!</b>)<br>
          </fieldset><fieldset>
            <input type="radio" name="method" value="php"> Damerau-Levenshtein programmed in PHP<br>
            <input type="radio" name="method" value="udf"> Damerau-Levenshtein as UDF<br>
            <input type="radio" name="method" value="mdld1"> MDLD(1) as UDF (genus and family only)<br>
            <input type="radio" name="method" value="mdld2"> MDLD(2) as UDF (genus and family only)<br>
            <input type="radio" name="method" value="mdld4"> MDLD(4) as UDF (genus and family only)
          </fieldset>
        </td>
      </tr>
    </table>
  </form>
</p>

<p>
<div id="ajaxTarget"></div>
</p>

</body>
</html>