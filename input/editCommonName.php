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
//Autocompleter
function prepareWithID(nam,startval,mustMatch1){
	if(mustMatch1){
		$('#ajax_'+nam).autocomplete("index_autocomplete_commoname.php",{
			extraParams:{field:'cname_'+nam},
  			autoFill:1,
			loadingClass: 'working',
			selectFirst: true,
			delay:100,
			LoadingAction: function() { $('#'+nam+'Index').val(''); }
  		}).change(function() {
			if($('#'+nam+'Index').val()==''){
				$('#ajax_'+nam).addClass('wrongItem');
			}
		});
		if($('#ajax_'+nam)!='' && startval!='')$('#ajax_'+nam).addClass('wrongItem');
	}else{
		$('#ajax_'+nam).autocomplete("index_autocomplete_commoname.php",{
			extraParams:{field:'cname_'+nam},
  			loadingClass: 'working',
			selectFirst: true,
			LoadingAction: function() { $('#'+nam+'Index').val(''); },
			delay:100
  		});
	}

	$('#ajax_'+nam).result(function(event, data, formatted) {
		if(data){
			$('#'+nam+'Index').val(data[1]);
			$('#ajax_'+nam).val(data[0]).removeClass('wrongItem');
		}
	});

	if(startval!=''){
		$('#ajax_'+nam).searchID(startval);
	}
}

function initAjaxVal(initObj,initObj2){
	jQuery.each(initObj, function(key, val) {
		prepareWithID(key, val,1);
    });
	jQuery.each(initObj2, function(key, val) {
		prepareWithID(key, val,0);
    });
	$("#ajax_taxon").focus();
}
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

	'new_common_nameIndex'	=> '',
	'new_common_name'		=> '',
);

$action=isset($_POST['submitUpdate'])?'doUpdate':(isset($_POST['action'])?$_POST['action']:'');


if( $action=='doUpdate'){
	$_dvar=array_merge($_dvar, array(
		'common_nameIndex'	=>$_POST['common_nameIndex'],
		'common_name'		=>$_POST['common_name'],
		
		'new_common_nameIndex'	=>$_POST['new_common_nameIndex'],
		'new_common_name'		=>$_POST['new_common_name'],
	));

	// Insert/Update
	if($action=='doUpdate') {
		list($msg['err'],$msg['result'])=UpdateCommonName($_dvar);
	}
}else if(isset($_GET['common_nameIndex'])){
	$_dvar['common_nameIndex']=$_GET['common_nameIndex'];
}
//print_r($_dvar);
?>

<form Action="<?php echo $_SERVER['PHP_SELF']; ?>" Method="POST" name="f" id="sendto">



<?php
$_dvar['enableClose']=((isset($_POST['enableClose'])&&$_POST['enableClose']==1)||(isset($_GET['enableClose'])&&$_GET['enableClose']==1))?1:0;

$msgs='';
if($msg['result']=="0"){
	$msgs=" Error:<br>{$msg['err']} ";
}else if(strlen($msg['result'])>0){
	$msgs=" Success:<br>{$msg['result']} ";
}

$init="
	var init={common_name:'{$_dvar['common_nameIndex']}'};
	var init2={new_common_name:'{$_dvar['new_common_nameIndex']}'};
";



echo <<<EOF

	<script type="text/javascript" language="JavaScript">
	{$init}

	$(document).ready(function() {
		initAjaxVal(init,init2);
	});
	</script>
EOF;
	
$cf = new CSSF();

echo "<input type=\"hidden\" name=\"action\" id=\"action\" value=\"\">\n";

$cf->buttonJavaScript(12, 2, " Close Window", "window.opener.location.reload(true);self.close()");

$cf->label(11, 5, "Common Name");
$cf->inputJqAutocomplete2(12, 5, 50, "common_name", $_dvar['common_name'], $_dvar['common_nameIndex'], "index_jq_autocomplete.php?field=cname_commonname", 520, 2,0,"",true);

$cf->label(11, 7, "New Common Name");
$cf->inputJqAutocomplete2(12, 7, 50, "new_common_name", $_dvar['new_common_name'], $_dvar['new_common_nameIndex'], "index_jq_autocomplete.php?field=cname_commonname", 520, 2,0,"",true);



if (($_SESSION['editControl'] & 0x200) != 0) {
	$cf->buttonSubmit(12, 10, "reload", " Reload ");
	$cf->buttonReset(18, 10, " Reset ");
	//if(checkRight('commonnameUpdate')){
		$cf->buttonSubmit(23,10, "submitUpdate", " Update");
	//}
}

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
	// Already the same???
	$result = doDBQuery("SELECT common_id FROM {$dbprefix}tbl_name_commons WHERE common_name='{$_dvar['new_common_name']}' and common_id='{$_dvar['common_nameIndex']}'");
	if($row=mysql_fetch_array($result)){
		return array("The selected Common Name has already the typed in new common Name.",0);
	}
	
	$result = doDBQuery("UPDATE {$dbprefix}tbl_name_commons SET common_name='{$_dvar['new_common_name']}' WHERE common_id='{$_dvar['common_nameIndex']}'");
	if($result){
		echo mysql_insert_id();
		
		// log it
		logCommonNamesCommonName($_dvar['common_nameIndex'],1);
		
		// add info:
		$result = doDBQuery("SELECT common_id FROM {$dbprefix}tbl_name_commons WHERE common_name='{$_dvar['new_common_name']}' and common_id<>'{$_dvar['common_nameIndex']}'");
		
		$s='';
		while($row=mysql_fetch_array($result)){
			$s.="<br>{$row['common_id']}";
		}
		if($s!=''){
			$s="IDs with same CommonNames like {$_dvar['new_common_name']}:{$s}";
		}
		echo $s;
		
		return array(0,"Successfully updated");
	
	}
	// If no insertion because of already there: Print Error Message
	if(mysql_errno()=='1062'){
		return array("already in database",0);
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