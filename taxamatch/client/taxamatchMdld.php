<?php
require_once ("inc/xajax/xajax.inc.php");

$xajax = new xajax("ajax/taxamatchMdldServer.php");
$xajax->registerFunction("dispatcher");

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - taxamatch MDLD</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <?php $xajax->printJavascript('inc/xajax'); ?>
  <script type="text/javascript" src="inc/iBox/ibox.js"></script>
  <script type="text/javascript" language="JavaScript">
    iBox.setPath('inc/iBox/');
    iBox.tags_to_hide = ['embed', 'object'];
  </script>
</head>

<body onload="document.f.searchtext.focus();">

<div id="iBox_content" style="display:none;">
<b>MDLD taxamatch implementation</b> (modified Damerau-Levenshtein algorithm, originally developed by tony rees at csiro dot org)<br>
php script for single (few line multiple) checks - (phonetic = near match not included so far)<br>
<br>
Functionality includes parsing of Names for <b>uninomials</b> (family and genus names), <b>binomials</b> and <b>trinomials</b> and includes a check for subgeneric names against the genus table in our reference list<br>
<br>
! content is mostly phanerogamic plants, to be complemented by the index fungorum and CoL names in october 2009!<br>
<br>
<br>
<b>Results</b> can be downloaded by clicking the <b>"export csv" </b>button<br>
<br>
<br>
For examples to show <b>full functionality</b> you might cut and paste the following strings:<br>
<br>
aceracees<br>
Johannesteismania<br>
Senecio (Cineraria) bicolor<br>
Ranunculus auricomus L. subsp. hevellus H&uuml;lsen ex Asch. & Graebn.<br>
Ranunculus aff. aquatilis<br>
cf.  Ranunculus aquatilis L. var. peltatus (Schrank) W.D.J.Koch, 1837<br>
Ranunculus auricomus forma subapetalus<br>
<br>
comment:<br>
In case we assume the resulting match to be a synonym in our locally adopted taxonomy a reference is given to the <b>"currently preferred accepted name"</b><br>
<br>
</div>

<h1>
  Taxamatch MDLD
  &nbsp;
  <img align="top" src="images/information.png" onclick="iBox.showURL('#iBox_content', 'info', iBox.parseQuery('width=520')); return false;">
</h1>
<p>
  <form Action="<?php echo $_SERVER['PHP_SELF']; ?>" Method="POST" name="f" id="f">
    <table>
      <tr>
        <td><textarea name="searchtext" style="width:50em; height:10em;"></textarea></td>
      </tr><tr>
        <td>
          <input type="radio" name="database" id="database_vienna" value="vienna" checked>
          <label for="database_vienna">Virtual Herbarium Vienna</label>
          <input type="radio" name="database" id="database_col" value="col">
          <label for="database_col">Catalogue of Life</label>
          <input type="radio" name="database" id="database_fe" value="fe">
          <label for="database_fe">Fauna Europea</label>
          <input type="radio" name="database" id="database_vienna_common" value="vienna_common">
          <label for="database_fe">Virtual Herbarium Vienna common names</label>
        </td>
      </tr><tr>
        <td>
          <input type="checkbox" name="nearmatch" id="nearmatch"><label for="nearmatch">use near match</label>
          <!-- BP 07.2010: checkbox for synonyms in MDLD-result yes/no -->
          <input type="checkbox" name="showSyn" id="showSyn"><label for="showSyn">show synonyms</label>
        </td>
      </tr><tr>
        <td valign="top">
          <input type="submit" value="search" name="searchSpecies" onclick="xajax_dispatcher('showMatchJsonRPC', xajax.getFormValues('f')); return false;">
        </td>
      </tr>
    </table>
  </form>
</p>

<p>
<div id="ajaxTarget"></div>
</p>

<div style="color:lightgray; position:absolute; right:1px; bottom:1px;"><a href="#" onclick="xajax_dispatcher('dumpMatchJsonRPC', xajax.getFormValues('f')); return false;"/>&pi;</div>
</body>
</html>