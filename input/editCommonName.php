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
  <link rel="stylesheet" type="text/css" href="inc/jQuery/css/south-street/jquery-ui-1.8.14.custom.css">
   <link rel="stylesheet" href="inc/jQuery/jquery-autocomplete/jquery.autocomplete.css" type="text/css" />
   <link rel="stylesheet" href="inc/jQuery/css/blue/style_nhm.css" type="text/css" />
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
  <script src="inc/jQuery/jquery.min.js" type="text/javascript"></script>
  <script src="inc/jQuery/jquery-ui.custom.min.js" type="text/javascript"></script>
  <script src="inc/jQuery/jquery-autocomplete/jquery.autocomplete_nhm.js" type="text/javascript"></script>
  <script type="text/javascript" src="inc/jQuery/jquery.tablesorter_nhm.js"></script>

  <script type="text/javascript" language="JavaScript">

  </script>
</head>
<body>

<?php
$msg=array('result'=>'','err'=>'');
$dbprefix=$_CONFIG['DATABASE']['NAME']['name'].'.';


// dataVar
$_dvar=array(
	'common_nameIndex'		=> '',
	'new_common_name'		=> '',
	'locked'			=> '1',
);

$action=isset($_POST['submitUpdate'])?'doUpdate':(isset($_POST['action'])?$_POST['action']:'');


if( $action=='doUpdate'){
	$_dvar=array_merge($_dvar, array(
		'common_nameIndex'	=> $_POST['common_nameIndex'],
		'new_common_name'	=> $_POST['new_common_name'],
		'locked'			=> (isset($_POST['locked'])&&$_POST['locked']=='on')?1:$_dvar['locked'],
	));
	
	print_r($_POST);
	print_r($_dvar);
	// Insert/Update
	if($action=='doUpdate') {
		list($msg['err'],$msg['result'])=UpdateCommonName($_dvar);
	}
}else if(isset($_GET['common_nameIndex'])){
	$_dvar['common_nameIndex']=$_GET['common_nameIndex'];
	
	$result = doDBQuery("SELECT common_name FROM {$dbprefix}tbl_name_commons WHERE common_id='{$_dvar['common_nameIndex']}'");
	if($row=mysql_fetch_array($result)){
		$_dvar['common_name']=$row['common_name'];
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


$cf->label(11, 5, "New Common Name");
$cf->inputText(12, 5, 50, "new_common_name", $_dvar['new_common_name']);


if (($_SESSION['editControl'] & 0x200) != 0) {
	//$cf->buttonSubmit(12, 10, "reload", " Reload ");
	//$cf->buttonReset(18, 10, " Reset ");
	//$cf->buttonJavaScript(17, 34, " Reset ", "document.location.reload(true);");
	
	if(/* checkRight('commonnameUpdate') && */($unlock_tbl_name_commons || !$isLocked) ){
		$cf->buttonSubmit(12,8, "submitUpdate", " Update");
	}

}
$cf->buttonJavaScript(18, 8, " Close Window", "self.close()");



echo<<<EOF
<div style="position: absolute; left: 12em; top: 12em; width:672px;">
{$msgs}
</div>
EOF;
?>

</form>
<div style="display:none">

<div id="dialog-information" title="Information">
	<p><span class="ui-icon ui-icon-info" style="float:left; margin:0 7px 20px 0;"></span></p><div id="dinformation" style="float:left"> These items will be permanently deleted and cannot be recovered. Are you sure?</div>
</div>
<div id="dialog-warning" title="Warning">
	<p><span class="ui-icon ui-icon-notice" style="float:left; margin:0 7px 20px 0;"></span></p><div id="dwarning" style="float:left">These items will be permanently deleted and cannot be recovered. Are you sure?</div>
</div>
<div id="dialog-error" title="Error">
	<p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span></p><div id="derror" style="float:left">These items will be permanently deleted and cannot be recovered. Are you sure?</div>
</div>

</div>
</body>
</html>

<?

function UpdateCommonName(&$_dvar, $update=false){
	global $dbprefix;
	
	$msg=array();
	/*if(!$update && !checkRight('commonnameInsert')){
		return array("You have no Rights for Insert",0);
	}*/

	if($_dvar['common_nameIndex']==''){
		return array("Wrong original Common name",0);
	}
	if(strlen($_dvar['new_common_name'])<3){
		return array("Wrong New Common name",0);
	}
	// Already the same???
	$result = doDBQuery("SELECT common_id FROM {$dbprefix}tbl_name_commons WHERE common_name='{$_dvar['new_common_name']}'");
	if($row=mysql_fetch_array($result)){
		return array("The selected Common Name is already in the Database.",0);
	}
	
	$result = doDBQuery("UPDATE {$dbprefix}tbl_name_commons SET common_name='{$_dvar['new_common_name']}',locked='{$_dvar['locked']}' WHERE common_id='{$_dvar['common_nameIndex']}'");
	if($result){
		echo mysql_insert_id();
		
		// log it
		logCommonNamesCommonName($_dvar['common_nameIndex'],1);
		
		echo <<<EOF
<script>
window.opener.location.reload(true);
self.close()
</script>
EOF;
		return array(0,"Successfully updated");
	
	}
	return array("Error ".mysql_errno() . ": " . mysql_error() . "",0);
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