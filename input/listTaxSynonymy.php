<?php
session_start();
require("inc/connect.php");
require("inc/herbardb_input_functions.php");

$id = intval($_GET['ID']);
if (isset($_GET['order'])) {
    if ($_SESSION['taxSynOrTyp'] == 1) {
        $_SESSION['taxSynOrder'] = "genus DESC";
        $_SESSION['taxSynOrTyp'] = 11;
    } else {
        $_SESSION['taxSynOrder'] = "genus";
        $_SESSION['taxSynOrTyp'] = 1;
    }
} else if (!isset($_GET['r'])){
    $_SESSION['taxSynOrder'] = "genus";
    $_SESSION['taxSynOrTyp'] = 1;
}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - list Synonyms</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <style type="text/css">
    table.out { width: 100% }
    tr.out { }
    th.out { font-style: italic }
    td.out { background-color: #669999; }
  </style>
  <script type="text/javascript" language="JavaScript">
    function editTaxSynonymy(id,n) {
      target = "editTaxSynonymy.php?ID=" + id;
      if (n) target += "&new=1";
      MeinFenster = window.open(target,"editTaxSynonymy","width=800,height=550,top=70,left=70,scrollbars=yes,resizable=yes");
      MeinFenster.focus();
    }
  </script>
</head>

<body>

<?php

try {
	$db = clsDbAccess::Connect('INPUT');

	$dbst1 = $db->prepare("SELECT taxonID FROM {$_CONFIG['DATABASE']['VIEWS']['name']}.view_taxon WHERE taxonID=:taxonID");
	$dbst1->execute(array(":taxonID" => $id));

	$row = $dbst1->fetch();

	echo "<b>protolog:</b> " . getScientificName($row['taxonID'], true, false) . "\n<p>\n";

	$dbst2 = $db->prepare("SELECT taxon.taxonID, preferred_taxonomy, tax_syn_ID, annotations, tts.source, tts.source_citationID, tts.source_person_ID, tts.source_serviceID
                           FROM {$_CONFIG['DATABASE']['INPUT']['name']}.tbl_tax_synonymy tts
                            LEFT JOIN {$_CONFIG['DATABASE']['VIEWS']['name']}.view_taxon taxon ON taxon.taxonID = tts.acc_taxon_ID
                           WHERE tts.taxonID = :taxonID ORDER BY :orderby");
	$dbst2->execute(array(":taxonID" => $id, 'orderby'=>$_SESSION['taxSynOrder']));
	$rows = $dbst2->fetchAll(PDO::FETCH_ASSOC);

}catch (Exception $e) {
	exit($e->getMessage());
}


echo "<p>\n";
echo "<form Action=\"" . $_SERVER['PHP_SELF'] . "\" Method=\"GET\" name=\"f\">\n";
if (($_SESSION['editControl'] & 0x20) != 0) {
	echo<<<EOF
<table><tr><td>
<input class="cssfbutton" type="button" value=" add new Line " onClick="editTaxSynonymy('<{$id}>',1)">
</td><td width="20">&nbsp;</td><td>
<input class="cssfbutton" type="submit" name="reload" value="Reload">
</td><td width="20">&nbsp;</td><td>
<input class="cssfbutton" type="button" value=" close " onclick="self.close()">
</td></tr></table>
EOF;

}

$order="";
if ($_SESSION['taxSynOrTyp'] == 1) {
	$order="&nbsp;&nbsp;v";
} else if ($_SESSION['taxSynOrTyp'] == 11) {
	$order="&nbsp;&nbsp;^";
}

echo<<<EOF
<input type="hidden" name="r" value="1">
<input type="hidden" name="ID" value="{$id}">
</form><p>

<table class="out" cellspacing="2" cellpadding="2">
<tr class="out">
<th></th>
<th class="out">&nbsp;<a href="{$_SERVER['PHP_SELF']}?ID=$id&order=a">acc. Taxon</a>
{$order}
&nbsp;</th>
<th class="out">P</th>
<th class="out">&nbsp;annotations&nbsp;</th>
<th class="out">&nbsp;reference&nbsp;</th>

</tr>
EOF;

if(count($rows)>0){
	foreach($rows  as $row){
		$radic=(($row['preferred_taxonomy']) ? "&radic;" : "") ;
		$taxon=getScientificName($row['taxonID']);

		$display=clsDisplay::Load();
		$ref=$display->SynonymyReference(0,$row);

		echo<<<EOF
<tr class="out">
 <td class="out"><a href="javascript:editTaxSynonymy('<{$row['tax_syn_ID']}>',0)">edit</a></td>
 <td class="out">{$taxon}</td>
 <td class="out" align="center">{$radic}</td>
 <td class="out">{$row['annotations']}</td>
 <td class="out">{$ref}</td>
</tr>
EOF;


    }
} else {
    echo "<tr class=\"out\"><td class=\"out\" colspan=\"5\">no entries</td></tr>\n";
}
echo "</table>\n";

?>

</body>
</html>