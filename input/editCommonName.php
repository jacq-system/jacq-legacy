<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");
require("inc/herbardb_input_functions.php");
require("inc/log_functions.php");
no_magic();
error_reporting(E_ALL);
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - edit Index</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <link rel="stylesheet" type="text/css" href="js/lib/jQuery/css/south-street/jquery-ui-1.8.14.custom.css">
   <link rel="stylesheet" href="js/lib/jQuery/css/blue/style_nhm.css" type="text/css" />
   <link rel="stylesheet" href="js/jquery_autocompleter_freud.css" type="text/css" />
  <style type="text/css">
    table.out { width: 100% }
    tr.out { }
    th.out { font-style: italic }
    td.out { background-color: #669999; }
	.ui-autocomplete {
        font-size: 0.9em;  /* smaller size */
		max-height: 200px;
		overflow-y: auto;
		/* prevent horizontal scrollbar */
		overflow-x: hidden;
		/* add padding to account for vertical scrollbar */
		padding-right: 20px;
	}
	/* IE 6 doesn't support max-height
	 * we use height instead, but this forces the menu to always be this tall
	 */
	* html .ui-autocomplete {
		height: 200px;
	}
.working{background:url('css/loading.gif') no-repeat right center;}
.wrongItem{background:url('css/wrong.gif') no-repeat right center; background-color:rgb(255, 185,79) !important;}

.thover1 td{
 background-color:rgb(255, 185,79) !important;
}
  </style>
  <script src="js/lib/jQuery/jquery.min.js" type="text/javascript"></script>
  <script src="js/lib/jQuery/jquery-ui.custom.min.js" type="text/javascript"></script>
  <script type="text/javascript" src="js/jquery_autocompleter_freud.js"></script>
  <script type="text/javascript" language="JavaScript">
	$(document).ready(function() {
	  $("#new_common_name").select();
	});
  </script>
</head>
<body>

<?php
$msg=array('result'=>'','err'=>'');
$dbprefix=$_CONFIG['DATABASE']['NAME']['name'].'.';


// dataVar
$_dvar=array(
	'common_nameIndex'		=> '',
	'common_name'			=> '',
	'new_common_name'		=> '',
	'transliteration'		=> '',
	'locked'				=> '1',
);

$action=isset($_POST['submitUpdate'])?'doUpdate':(isset($_POST['action'])?$_POST['action']:'');


if( $action=='doUpdate'){
	$_dvar=array_merge($_dvar, array(
		'common_nameIndex'	=> $_POST['common_nameIndex'],
		'new_common_name'	=> $_POST['new_common_name'],
		'transliteration'	=> $_POST['transliteration'],
		'locked'			=> (isset($_POST['locked'])&&$_POST['locked']=='on')?1:$_dvar['locked'],
	));

	// Insert/Update
	if($action=='doUpdate') {
		list($msg['err'],$msg['result'])=UpdateCommonName($_dvar);
	}

}else if(isset($_GET['common_nameIndex'])){
	$_dvar['common_nameIndex']=$_GET['common_nameIndex'];

	$sql="
SELECT
 com.common_name as 'common_name',
 trans.name as 'tranlit'
FROM
 {$dbprefix}tbl_name_commons  com
 LEFT JOIN {$dbprefix}tbl_name_names nam on nam.name_id=com.common_id
 LEFT JOIN {$dbprefix}tbl_name_transliterations trans ON trans.transliteration_id=nam.transliteration_id
WHERE
 com.common_id='{$_dvar['common_nameIndex']}'";

	$result = doDBQuery($sql);
	if($row=mysql_fetch_array($result)){
		$_dvar['common_name']=$row['common_name'];
		$_dvar['new_common_name']=$row['common_name'];
		$_dvar['transliteration']=$row['tranlit'];
	}
}
//print_r($_dvar);
?>

<form Action="<?php echo $_SERVER['PHP_SELF']; ?>" Method="POST" name="f" id="sendto">

<?php
$dbprefix=$_CONFIG['DATABASE']['NAME']['name'].'.';

$_dvar['enableClose']=((isset($_POST['enableClose'])&&$_POST['enableClose']==1)||(isset($_GET['enableClose'])&&$_GET['enableClose']==1))?1:0;

$msgs='';
if($msg['result']=="0"){
	$msgs=" Error:<br>{$msg['err']} ";
}else if(strlen($msg['result'])>0){
	$msgs=" Success:<br>{$msg['result']} ";
}

$cf = new CSSF();
//editCommonNamesEquals.php
echo "<input type=\"hidden\" name=\"action\" id=\"action\" value=\"\">\n";
echo "<input type=\"hidden\" name=\"common_nameIndex\" id=\"common_nameIndex\" value=\"{$_dvar['common_nameIndex']}\">\n";


$isLocked=isLocked($dbprefix.'tbl_name_commons', $_dvar['common_nameIndex']);
$unlock_tbl_name_commons=checkRight('unlock_tbl_name_commons');

if($unlock_tbl_name_commons){
    $cf->label(11,1,"locked");
    $cf->checkbox(12,1,"locked",$_dvar['locked']);
}else if($isLocked){
    $cf->label(23,2,"locked");
    echo "<input type=\"hidden\" name=\"locked\" value=\"{$_dvar['locked']}\">\n";
}

$cf->label(11, 3, "Common Name");
$cf->text(12, 3, "{$_dvar['common_name']}");


$cf->label(11, 5, "Common Name");
$cf->inputText(12, 5, 50, "new_common_name", $_dvar['new_common_name'],"0","","\" id=\"new_common_name");

$cf->label(11, 8, "Transliteration");
$cf->inputText(12, 8, 50, "transliteration", $_dvar['transliteration'],"0","","\" id=\"new_common_name");


if( ($_SESSION['editControl'] & 0x10000) != 0  && ($unlock_tbl_name_commons || !$isLocked) ){
	$cf->buttonSubmit(12,11, "submitUpdate", " Update");
}

$cf->buttonJavaScript(18, 11, " Close Window", "self.close()");

echo<<<EOF
<div style="position: absolute; left: 12em; top: 12em; width:672px;">
{$msgs}
</div>
</form>
EOF;

$title="Common Names equals";
$serverParams="&cid={$_dvar['common_nameIndex']}";

$searchjs=<<<EOF
function createMapSearchstring(){
	searchString='';
	if($('#ajax_mysqlSearch').val().length>0)
		searchString='&mysqlSearch='+$('#ajax_mysqlSearch').val();
	else
		searchString='&mdldSearch='+$('#mdldSearch').val();

	return searchString;
}
EOF;
		$searchhtml=<<<EOF
<table>
<tr><td>MDLD Search:</td><td><input class="cssftext" style="width: 25em;" type="text" id="mdldSearch" value="" maxlength="200" ></td></tr>
<tr><td>mysql Search:</td><td><input tabindex="2" class='cssftextAutocomplete' style='width: 25em;' type="text" value="" name="ajax_mysqlSearch" id="ajax_mysqlSearch" maxlength='520' /></td></tr>
</table>
<input type="hidden" name="mysqlSearchIndex" id="mysqlSearchIndex" value="">
<script>ACFreudConfig.push(['index_jq_autocomplete_commoname.php?field=cname_commonname_translit','mysqlSearch','','0','0','0','2']);</script>
EOF;

$cf->inputMapLines(12,14,0,'edit CommonNames Equal',$title,'index_jq_autocomplete_commoname.php?field=cname_name',
'index_jq_autocomplete_commoname.php?field=cname_name','ajax/MapLines_CommonNamesEqual.php',$serverParams,$searchjs,$searchhtml,1);


?>

</body>
</html>

<?php

function UpdateCommonName(&$_dvar){
	global $dbprefix;

	$msg=array();
	if(($_SESSION['editControl'] & 0x10000) == 0){
		return array("You have no Rights for Update",0);
	}

	if($_dvar['common_nameIndex']==''){
		return array("Wrong original Common name",0);
	}

	if(strlen($_dvar['new_common_name'])<2){
		return array("Wrong New Common name",0);
	}

	// Already the same???
	$result=doDBQuery("SELECT common_id, common_name FROM {$dbprefix}tbl_name_commons WHERE common_name='{$_dvar['new_common_name']}'");
	if($result && $row=mysql_fetch_array($result)){

		if($_dvar['common_nameIndex']!=$row['common_id']){
			return array("The typed in Common Name is already in the Database.",0);
		}
	}else{
		$result = doDBQuery("UPDATE {$dbprefix}tbl_name_commons SET common_name='{$_dvar['new_common_name']}', locked='{$_dvar['locked']}' WHERE common_id='{$_dvar['common_nameIndex']}'");
		if(!$result){
			return array("mysql error: Update CommonName".mysql_error(),0);
		}
		// log it
		logCommonNamesCommonName($_dvar['common_nameIndex'],1);
	}

	if(strlen($_dvar['transliteration'])<2){
		return array("Ttransliteration too short, not considert.",0);
	}

	$result = doDBQuery("SELECT transliteration_id FROM {$dbprefix}tbl_name_transliterations WHERE name='{$_dvar['transliteration']}'");
	if($result && $row=mysql_fetch_assoc($result)){
		$_dvar['transliterationIndex']=$row['transliteration_id'];
	}else{
		$result = doDBQuery("INSERT INTO {$dbprefix}tbl_name_transliterations (name) VALUES ('{$_dvar['transliteration']}')");
		$_dvar['transliterationIndex']=mysql_insert_id();
	}

	$result=doDBQuery("SELECT nam.name_id FROM {$dbprefix}tbl_name_commons  com LEFT JOIN {$dbprefix}tbl_name_names nam on nam.name_id=com.common_id WHERE com.common_id='{$_dvar['common_nameIndex']}'");

	if($result && $row=mysql_fetch_array($result)){
		$_dvar['nameIndex']=$row['name_id'];
		$result = doDBQuery("UPDATE {$dbprefix}tbl_name_names SET transliteration_id='{$_dvar['transliterationIndex']}' WHERE name_id='{$_dvar['nameIndex']}'");
		if(!$result){
			return array("mysql error Update Transliteration: ".mysql_error(),0);
		}
		logNamesCommonName($_dvar['nameIndex'],1);
	}
	return array(0,"Successfully updated");
}

/**
 * doDBQuery:
 * @param
 * @param
 * @return sql string
 */
function doDBQuery($sql,$debug=false){
	if($debug){
		echo $sql;
	}
	$res=db_query($sql);

	if(!$res){
		echo mysql_errno() . ": " . mysql_error() . "\n";
	}
	return $res;
}
?>