<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");
require("inc/herbardb_input_functions.php");
require("inc/log_functions.php");
no_magic();
error_reporting(E_ALL);
//http://docs.jquery.com/Plugins/Autocomplete/autocomplete#url_or_dataoptions
$debuger=0;
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

.eac{background-color:#DAEEDD;}
.oac{background-color:#e7fae6;}
.e{background-color:#f0f0f6;}
.o{background-color:#fff;}
.lac{background-color:#e7fae6 !important;}
.l{background-color:#fff !important;}
table.tablesorter thead tr th{border: 1px solid #CCC !important;}
table.tablesorter tbody td {border: 1px solid #CCC !important;}
  </style>
  <script src="inc/jQuery/jquery.min.js" type="text/javascript"></script>
  <script src="inc/jQuery/jquery-ui.custom.min.js" type="text/javascript"></script>
  <script src="inc/jQuery/jquery-autocomplete/jquery.autocomplete_nhm.js" type="text/javascript"></script>
  <script type="text/javascript" src="inc/jQuery/jquery.tablesorter_nhm.js"></script>

  <script type="text/javascript" language="JavaScript">
var geowin;
// windows...
function selectTaxon() {
	//taxonID=$('#taxonIndex').val();
	taxwin = window.open("listTaxCommonName.php", "selectTaxon", "width=600, height=500, top=50, right=50, scrollbars=yes, resizable=yes");
	taxwin.focus();
}
function UpdateTaxon(taxonID) {
	$('#ajax_taxon').searchID(taxonID);
}


function selectLiterature() {
	//literatureID=$('#literatureIndex').val();
	citwin = window.open("listLitCommonName.php", "selectliteratur", "width=600, height=500, top=50, right=50, scrollbars=yes, resizable=yes");
	citwin.focus();
}
function UpdateLiterature(literatureID) {
	$('#ajax_literature').searchID(literatureID);
}

/*
function selectService() {
	//personID=$('#personIndex').val();
	serwin = window.open("listServiceCommonName.php", "selectservice", "width=600, height=500, top=50, right=50, scrollbars=yes, resizable=yes");
	serwin.focus();
}
function UpdateService(serviceID) {
	$('#ajax_service').searchID(serviceID);
}

function selectPerson() {
	//personID=$('#personIndex').val();
	perwin = window.open("listPersonsCommonName.php", "selectperson", "width=600, height=500, top=50, right=50, scrollbars=yes, resizable=yes");
	perwin.focus();
}
function UpdatePerson(personID) {
	$('#ajax_person').searchID(personID);
}
*/

function selectGeoname() {
	gn=$('#ajax_geoname').val();
	gi=$('#geonameIndex').val();

	if(!geowin || geowin.closed){
		geowin = window.open("selectGeoname.php?geoname="+encodeURIComponent(gn)+"&geonameID="+gi, "SelectGeoname", "width="+screen.width+", height="+screen.height+", top=0, left=0, scrollbars=yes, resizable=yes");
	}else{
		geowin.UpdateGeography(gn,gi);
	}
	geowin.focus();
}
function UpdateGeoname(geonameID) {
	$('#ajax_geoname').searchID(geonameID).result(function(event, data, formatted) {
		if(!data){
				$('#ajax_geoname').val('geonames.org databases not synced yet for ID '+geonameID+'. But you can store it anyway.');
				$('#geonameIndex').val(geonameID);
		}
	});
}


function editCommoname(){
	cid='?a=b';
	if($('#common_nameIndex').val()!='')cid+='&common_nameIndex='+$('#common_nameIndex').val();
	
	comwin = window.open("editCommonName.php"+cid, "editCommonName", "width=850 height=150, top=50, right=50, scrollbars=auto, resizable=yes");
	comwin.focus();
}

// Autocompleter
function prepareWithID(nam,startval,mustMatch1){
	if(mustMatch1){
		$('#ajax_'+nam).autocomplete("index_autocomplete_commoname.php",{
			extraParams:{field:'cname_'+nam},
  			autoFill:1,
			loadingClass: 'working',
			selectFirst: true,
			delay:100,
			LoadingAction: function() { $('#'+nam+'Index').val('');  },
			scroll: true
  		}).change(function() {
			if($('#'+nam+'Index').val()==''){
				$('#ajax_'+nam).addClass('wrongItem');
			}
		});

		if($('#ajax_'+nam).val()!='' && startval!='')$('#ajax_'+nam).addClass('wrongItem');
	}else{
		$('#ajax_'+nam).autocomplete("index_autocomplete_commoname.php",{
			extraParams:{field:'cname_'+nam},
  			loadingClass: 'working',
			selectFirst: true,
			LoadingAction: function() { $('#'+nam+'Index').val(''); },
			delay:100,
			scroll:true,
			matchSubset:false,
  		});
	}

	$('#ajax_'+nam).result(function(event, data, formatted) {
		if(data){
			$('#'+nam+'Index').val(data[2]);
			$('#ajax_'+nam).val(data[1]).removeClass('wrongItem');
		}
	});

	if(startval!='' && startval!='0'){
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



$(document).ready(function() {
	// selection
	$("#ajax_literature").change(function() {$('input[name=source][value=literature]').attr('checked','checked');});
	$("#ajax_person").change(function() {$('input[name=source][value=person]').attr('checked','checked');});
	$("#ajax_service").change(function() {$('input[name=source][value=service]').attr('checked','checked');});

	$( "#ajax_geospecification").resizable({handles:"se"});//.css('z-index','10000');
	
	// Validation
	$('input[type=submit]').bind('click', function(){

		n=$(this).val();
		if(n==' Update'){
			return doCheck(false);
		}
		if(n==' Delete'){
			$("#dwarning").html("Do you really want to delete?");
			$("#dialog-warning").dialog({
				resizable: false,
				modal: false,
				width:400,
				buttons:[
					{text: "Cancel",click: function(){$(this).dialog("close");}},
					{text: "Delete",click: function(){$('#action').val("doDelete");$("#sendto").submit();}}
				]
			});
			return false;
		}
		if(n==' Insert New'){
			return doCheck(true);
		}
		return true;
	});
});


function doCheck(doInsert){
	var msg='';
	var t='';

	t="";
	if($("#ajax_taxon").val()=='')t+="<br> - Missing Taxon";
	if($("#taxonIndex").val()=='')t+="<br> - Invalid Taxon";
	if($("#ajax_common_name").val()=='')t+="<br> - Invalid Common Name";
	if(t!="")msg="Critical Error:"+t;

	if(msg!=""){
		$("#derror").html(msg);
		$("#dialog-error").dialog({
			resizable: false,
			modal: false,
			buttons: {"OK": function() {$( this ).dialog( "close" );}}
		});
		return false;
	}

	t="";
	if($("#ajax_period").val()=='')t+="<br> - Missing Period";
	if($("#ajax_geoname").val()=='')t+="<br> - Missing Geoname";
	if($("#ajax_language").val()=='')t+="<br> - Missing Language";
	if(t!="")msg+="Warnings:"+t;

	t="";
	source=$('input:radio[name=source]:checked').val();
	if(source=='literature'){
		if($("#ajax_literature").val()=='')t+="<br>&nbsp  - Missing Literatur Reference";
		if($("#literatureIndex").val()=='')t+="<br>&nbsp  - Invalid Literatur Reference";
		if($("#ajax_person").val()!='')t+="<br>&nbsp  - Person Reference will not be considered";
		if($("#ajax_service").val()!='')t+="<br>&nbsp  - Service Reference will not be considered";
		if(t!=""){if(msg!='')msg+="<br>";msg+=" - Reference-Type: Literatur"+t;}

	}else if(source=='service'){
		if($("#ajax_service").val()=='')t+="<br>&nbsp - Missing Service Reference";
		if($("#serviceIndex").val()=='')t+="<br>&nbsp - Invalid Service Reference";
		if($("#ajax_literature").val()!='')t+="<br>&nbsp - Literatur Reference will not be considered";
		if($("#ajax_person").val()!='')t+="<br>&nbsp - Person Reference will not be considered";
		if(t!=""){if(msg!='')msg+="<br>";msg+=" - Reference-Type: Service"+t;}

	}else if(source=='person'){
		if($("#ajax_person").val()=='')t+="<br>&nbsp - Missing Person Reference";
		if($("#personIndex").val()=='')t+="<br>&nbsp - Invalid Person Reference";
		if($("#ajax_literature").val()!='')t+="<br>&nbsp - Literatur Reference will not be considered";
		if($("#ajax_service").val()!='')t+="<br>&nbsp - Service Reference will not be considered";
		if(t!=""){if(msg!='')msg+="<br>";msg+=" - Reference-Type: Person"+t;}
	}

	if(msg!=""){
		$("#dinformation").html(msg);
		$("#dialog-information").dialog({
			resizable: false,
			modal: false,
			width:400,
			buttons:[
				{text: doInsert?"Insert New anyway":"Update Anyway",click: function(){$('#action').val(doInsert?"doInsert":"doUpdate");$("#sendto").submit();}},
				{text: "Cancel",click: function(){$(this).dialog("close");}}
			]
		});
		return false;
	}

	return true;
}

function showGeonameInfo(){
	$("#dinformation").html("For best window-handling please change<br>your Browser configuration.<br><br><b>Firefox:</b><br>	url: about:config<br>	dom.disable_window_flip => set to false<br><br><b>Opera:</b><br>url: about:config<br>Allow script to lower window<br>Allow script to raise window");
	
	$("#dialog-information").dialog({
		resizable: false,
		modal: false,
		width:400,
		buttons: {"OK": function() {$( this ).dialog( "close" );}}
	});
	
	
}

 </script>
</head>

<body>
<form Action="<?PHP echo $_SERVER['PHP_SELF'];?>" Method="POST" name="f" id="sendto">

<?php
$doSearch=false;
$search_result='';
$source_sel=array('service'=>'','person'=>'','literature'=>'');
$msg=array('result'=>'','err'=>'');
$strict=!isset($_GET['search']);
$dbprefix=$_CONFIG['DATABASE']['NAME']['name'].'.';
	

// dataVar
$_dvar=array(
	'entityIndex'		=> '',
	'taxonIndex'		=> '',
	'taxon'				=> '',

	'referenceIndex'	=> '',
	'source'			=> 'literature',

	'literatureIndex'	=> '',
	'literature'		=> '',
	'serviceIndex'		=> '',
	'service'			=> '',
	'personIndex'		=> '',
	'person'			=> '',

	'geonameIndex'		=> '',
	'geoname'			=> '',

	'languageIndex'		=> '',
	'language'			=> '',

	'periodIndex'		=> '',
	'period'			=> '',

	'nameIndex'			=> '',
	'common_nameIndex'	=> '',
	'common_name'		=> '',
	
	'geospecification'=> '',
	'annotation'		=> '',
	'locked'			=> '1',
	'active_id'	=>	new natID(array('entity_id','name_id','geonameId','language_id','period_id','reference_id')),

	'enableClose'	=> ((isset($_POST['enableClose'])&&$_POST['enableClose']==1)||(isset($_GET['enableClose'])&&$_GET['enableClose']==1))?1:0

);



if(isset($_GET['search'])){
	foreach($_GET as $k=>$v){
		$_POST[$k]=$v;
	}
}

$action=isset($_POST['submitDelete'])?'doDelete':(isset($_POST['submitUpdate'])?'doUpdate':(isset($_POST['submitInsert'])?'doInsert':(
		(isset($_POST['submitSearch'])||isset($_GET['search']))?'doSearch':(isset($_GET['show'])?'doShow':(isset($_POST['action'])?$_POST['action']:'')))));
//echo $action;
//print_r($_POST);

if( in_array($action,array('doDelete' ,'doSearch','doInsert','doUpdate'))!==0 ){
	
	$errr=error_reporting($debuger);
	$_dvar=array_merge($_dvar, array(
		'taxonIndex' => $_POST['taxonIndex'],
		'taxon' => $_POST['taxon'],

		'referenceIndex'	=>'',
		'source'			=> $_POST['source'],

		'literatureIndex'	=> $_POST['literatureIndex'],
		'literature'		=> $_POST['literature'],
		'serviceIndex'		=> $_POST['serviceIndex'],
		'service'			=> $_POST['service'],
		'personIndex'		=> $_POST['personIndex'],
		'person'			=> $_POST['person'],

		'geonameIndex'		=> $_POST['geonameIndex'],
		'geoname'			=> $_POST['geoname'],

		'languageIndex'		=> $_POST['languageIndex'],
		'language'			=> $_POST['language'],

		'periodIndex'		=> $_POST['periodIndex'],
		'period'			=> $_POST['period'],

		'nameIndex'			=> $_POST['common_nameIndex'],
		'common_nameIndex'	=> $_POST['common_nameIndex'],
		'common_name'		=> $_POST['common_name'],
		
		'geospecification'	=> $_POST['geospecification'],
		'annotation'		=> $_POST['annotation'],
		'locked'			=> (isset($_POST['locked'])&&$_POST['locked']=='on')?1:$_dvar['locked'],

		
	));
	
	if($action!='doSearch' && $_POST['active_id']!=''){
		$_dvar['active_id']->setNatIDFromString($_POST['active_id']);
	}
	
	if( $strict ){
		cleanPair('taxon');
		//cleanPair('common_name');
		cleanPair('geoname');
		cleanPair('language');
		//cleanPair('period');
		
		cleanPair('service');
		cleanPair('person');
		cleanPair('literature');
		
	}	

	error_reporting($errr);
}
//print_r($_dvar);

// Delete action
if ($action=='doDelete' ) {
	list($msg['err'],$msg['result'])=deleteCommonName($_dvar);

	// Insert/Update
}else if($action=='doInsert' || $action=='doUpdate') {

	list($msg['err'],$msg['result'])=InsertUpdateCommonName($_dvar,$action=='doUpdate');
	$doSearch=true;

// Show a Common Name Set with given GET vars...
}else if(isset($_GET['show'])) {
	
	$_dvar['active_id']->setNatIDFromString($_GET['id']);
	$sql=getAppliesQuery()." and ".$_dvar['active_id']->getWhere(array(),'a.');
	$result = doDBQuery($sql);
	

	if($result && ($row = mysql_fetch_array($result) ) && mysql_num_rows($result)==1 ){
		$_dvar=array_merge($_dvar, array(
			'entityIndex'=>$row['entity_id'],
			'referenceIndex'=>$row['reference_id'],

			'taxonIndex'=>$row['taxonID'],

			'geonameIndex'=>$row['geoname_id'],
			'geoname'=>$row['geoname'],

			'languageIndex'=>$row['language_id'],
			'language'=>$row['language'],

			'periodIndex'=>$row['period_id'],
			'period'=>$row['period'],

			'nameIndex'=>$row['name_id'],
			'common_nameIndex'=>$row['name_id'],
			'common_name'=>$row['common_name'],
			'source'=>$row['source'],

			'literatureIndex'=>isset($row['literatureID'])?$row['literatureID']:0,
			'personIndex'=>isset($row['personID'])?$row['personID']:0,
			'serviceIndex'=>isset($row['serviceID'])?$row['serviceID']:0,
			
			'geospecification'=> isset($row['geospecification'])?$row['geospecification']:$_dvar['geospecification'],
			'annotation'=>isset($row['annotation'])?$row['annotation']:$_dvar['annotation'],
			'locked'=>isset($row['locked'])?$row['locked']:$_dvar['locked'],
		));

	// If no row found, prepare for search vars;
	}else{
		$doSearch=true;
	}
	
// Do Search
}else if($action=='doSearch'){
	$doSearch=true;
}


if($doSearch){
	$search_result=doSearch($_dvar,$strict);
}
//print_r($_dvar);

$init="
var init={taxon:'{$_dvar['taxonIndex']}',geoname:'{$_dvar['geonameIndex']}',literature:'{$_dvar['literatureIndex']}',service:'{$_dvar['serviceIndex']}',person:'{$_dvar['personIndex']}'};
var init2={period:'',common_name:'',language:'{$_dvar['languageIndex']}',geospecification:''};
";


$source_sel[$_dvar['source']]=' checked';


// Check if update is possible (selected Row is existing)
$_dvar['update']=checkRowExists($_dvar['active_id']);
$_dvar['active_id']=$_dvar['active_id'];

$msgs='';
//$msgs.=print_r($msg,1);

if($msg['result']=="0"){
	$msgs.=" Error:<br>{$msg['err']} ";
}else if(strlen($msg['result'])>0){
	$msgs.=" Success:<br>{$msg['result']} ";
}

echo <<<EOF

<script type="text/javascript" language="JavaScript">
{$init}
var a='{$_dvar['source']}';

$(document).ready(function() {
	initAjaxVal(init,init2);
	
	switch(a){
		case 'person':$('#ajax_service, #ajax_literature').removeClass('wrongItem');break;
		case 'service':$('#ajax_person, #ajax_literature').removeClass('wrongItem');break;
		case 'literature':default:$('#ajax_person, #ajax_service').removeClass('wrongItem');break;
	}
});
</script>
EOF;

$isLocked=isLocked($dbprefix.'tbl_name_applies_to', $_dvar['active_id']);
$unlock_tbl_name_applies_to=checkRight('unlock_tbl_name_applies_to');
$cf = new CSSF();
if($_dvar['enableClose']){
	$cf->buttonJavaScript(11, 2, " Close Window", "window.opener.location.reload(true);self.close()");
}

echo "<input type=\"hidden\" name=\"enableClose\" value=\"{$_dvar['enableClose']}\">\n";
echo "<input type=\"hidden\" name=\"action\" id=\"action\" value=\"\">\n";
echo "<input type=\"hidden\" name=\"active_id\" value=\"".$_dvar['active_id']->toString()."\">\n";

if($unlock_tbl_name_applies_to) {
    $cf->label(60.5,2,"locked");
    $cf->checkbox(60.5,2,"locked",$_dvar['locked']);
}else if($isLocked){
    $cf->label(60.5,2,"locked");
    echo "<input type=\"hidden\" name=\"locked\" value=\"{$_dvar['locked']}\">\n";
}

$cf->label(10, 5, "Scientific Name","javascript:selectTaxon()");
$cf->inputJqAutocomplete2(11, 5, 50, "taxon", "", $_dvar['taxonIndex'], "index_jq_autocomplete.php?field=taxon_commonname", 520, 2,0,"",true);

$cf->label(10, 7.5, "Common Name","javascript:editCommoname()");
$cf->inputJqAutocomplete2(11, 7.5, 50, "common_name", $_dvar['common_name'], $_dvar['common_nameIndex'], "index_jq_autocomplete.php?field=cname_commonname", 520, 2,0,"",true);


$cf->label(10, 10, "Geography","javascript:selectGeoname()");
$cf->label(10.5, 10, "*","javascript:showGeonameInfo()");
$cf->inputJqAutocomplete2(11, 10, 50, "geoname", "", $_dvar['geonameIndex'], "index_jq_autocomplete.php?field=cname_geoname", 520, 2,"",0,true);

$cf->label(10, 12.5, "Geo Specification");
$cf->inputJqAutocomplete2(14, 12.5, 47, "geospecification", $_dvar['geospecification'],"", "index_jq_autocomplete.php?field=cname_geospecification", 520, 2,0,"",true,true,1);

$cf->label(10, 15, "Language");
$cf->inputJqAutocomplete2(11, 15, 50, "language", "", $_dvar['languageIndex'], "index_jq_autocomplete.php?field=cname_language", 520, 2,0,"",true);

$cf->label(10, 17.5, "Period");
$cf->inputJqAutocomplete2(11,17.5, 50, "period", $_dvar['period'], $_dvar['periodIndex'], "index_jq_autocomplete.php?field=cname_period", 520, 2,0,"",true);

$cf->label(10, 21, "Literature","javascript:selectLiterature()");
$cf->label(62, 21, "<input type=\"radio\" name=\"source\" value=\"literature\"{$source_sel['literature']}>");
$cf->inputJqAutocomplete2(11, 21, 48, "literature", "", $_dvar['literatureIndex'], "index_jq_autocomplete.php?field=cname_literature", 520, 2,0,"",true);

$cf->label(10, 23.5, "Service");
$cf->label(62, 23.5, "<input type=\"radio\" name=\"source\"  value=\"service\"{$source_sel['service']}>");
$cf->inputJqAutocomplete2(11, 23.5, 48, "service", "", $_dvar['serviceIndex'], "index_jq_autocomplete.php?field=cname_service", 520, 2,0,"",true);

$cf->label(10, 26, "Person");
$cf->label(62, 26, "<input type=\"radio\" name=\"source\"  value=\"person\"{$source_sel['person']}>");
$cf->inputJqAutocomplete2(11, 26, 48, "person", "", $_dvar['personIndex'], "index_jq_autocomplete.php?field=cname_person", 520, 2,0,"",true);


$cf->label(10, 29.5, "annotation");
$cf->textarea(11, 29.5, 50,2.5, "annotation", $_dvar['annotation'], "", "", "");

if(checkRight('commonnameInsert')){
	echo "<input style=\"display:none\" type=\"submit\" name=\"submitInsert\" value=\" Insert New\">";
}

if (($_SESSION['editControl'] & 0x200) != 0) {
	//$cf->buttonSubmit(11, 34, "reload", " Reload ");
	
	//$cf->buttonReset(17, 34, " Reset ");
	$cf->buttonJavaScript(17, 34, " Reset ", "document.location.reload(true);");
	if($_dvar['update'] &&  checkRight('commonnameUpdate') && ($unlock_tbl_name_applies_to || !$isLocked) ){
		$cf->buttonSubmit(22, 34, "submitUpdate", " Update");
		$cf->buttonSubmit(28, 34, "submitDelete", " Delete");
	}
	if(checkRight('commonnameInsert')){
		$cf->buttonSubmit(33, 34, "submitInsert", " Insert New");
	}
}
$cf->buttonSubmit(20, 2, "submitSearch", " Search");



echo<<<EOF
<div style="position: absolute; left: 11em; top: 36em; width:672px;">
{$msgs}
</div>
<div style="position: absolute; left: 11em; top: 38em; width:660px;">
{$search_result}
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
/*
  FUNCTION SECTION

*/


/**
 * getAppliesQuery:
 * @param 
 * @param 
 * @return sql string
 */
function getAppliesQuery($_dvar=array(),$get=false){
	global $dbprefix;
	
	$sql="
SELECT
 a.entity_id as 'entity_id',
 tax.taxonID as 'taxonID',

 a.name_id as 'name_id',
 com.common_name as 'common_name',

 a.language_id as 'language_id',
 lan.`iso639-6` as 'iso639-6',
 lan.name as 'language',
 lan.`parent_iso639-6` as 'parent_iso639-6',
 a.geonameId as 'geoname_id',
 geo.name as 'geoname',

 a.period_id as 'period_id',
 per.period as 'period',

 a.reference_id as 'reference_id',

 CASE
  WHEN pers.personID THEN 'person'
  WHEN ser.serviceID THEN 'service'
  ELSE 'literature'
 END as 'source',

 lit.citationID as 'literatureID',
 pers.personID as 'personID',
 ser.serviceID as 'serviceID',
 
 a.geospecification as 'geospecification',
 a.annotation as 'annotation',
 
 a.locked as 'locked'
FROM
 {$dbprefix}tbl_name_applies_to a
 LEFT JOIN {$dbprefix}tbl_name_entities ent ON ent.entity_id = a.entity_id
 LEFT JOIN {$dbprefix}tbl_name_taxa tax ON tax.taxon_id = ent.entity_id

 LEFT JOIN {$dbprefix}tbl_name_names nam ON nam.name_id = a.name_id
 LEFT JOIN {$dbprefix}tbl_name_commons com ON com.common_id = nam.name_id

 LEFT JOIN {$dbprefix}tbl_geonames_cache geo ON geo.geonameId = a.geonameId
 LEFT JOIN {$dbprefix}tbl_name_languages lan ON  lan.language_id = a.language_id
 LEFT JOIN {$dbprefix}tbl_name_periods per ON per.period_id= a.period_id

 LEFT JOIN {$dbprefix}tbl_name_references ref ON ref.reference_id = a.reference_id

 LEFT JOIN {$dbprefix}tbl_name_persons pers ON pers.person_id = ref.reference_id
 LEFT JOIN {$dbprefix}tbl_name_literature lit ON lit.literature_id = ref.reference_id
 LEFT JOIN {$dbprefix}tbl_name_webservices ser ON ser.webservice_id = ref.reference_id

WHERE
 1=1 
";
 	if(count($_dvar)==0)return $sql;

 	$_dvar['source']=isset($_dvar['source'])?$_dvar['source']:'literature';
 	

 	switch($_dvar['source']){
		default: case 'literature':if(!checkempty($_dvar,$get,'literature'))$sql.="\n and a.reference_id = ref.reference_id and ref.reference_id = lit.literature_id and lit.citationID ='{$_dvar['literatureIndex']}'";break;
		case 'service':if(!checkempty($_dvar,$get,'service'))$sql.="\n and a.reference_id = ref.reference_id and ref.reference_id = ser.webservice_id and ser.serviceID ='{$_dvar['serviceIndex']}'";break;
		case 'person':if(!checkempty($_dvar,$get,'person'))$sql.="\n and a.reference_id = ref.reference_id and ref.reference_id = pers.person_id and pers.personID ='{$_dvar['personIndex']}'";break;
	}

	if(!checkempty($_dvar,$get,'taxon'))$sql.="\n and a.entity_id = ent.entity_id and ent.entity_id = tax.taxon_id  and tax.taxonID='{$_dvar['taxonIndex']}'";
	if(!checkempty($_dvar,$get,'common_name'))$sql.="\n and a.name_id = nam.name_id and nam.name_id = com.common_id and com.common_id = '{$_dvar['common_nameIndex']}'";
	if(!checkempty($_dvar,$get,'language',1))$sql.="\n and a.language_id ='{$_dvar['languageIndex']}'";
	if(!checkempty($_dvar,$get,'geoname',1))$sql.="\n and a.geonameId = geo.geonameID and geo.geonameID = '{$_dvar['geonameIndex']}'";
	if(!checkempty($_dvar,$get,'period'))$sql.="\n and a.period_id = per.period_id and per.period_id = '{$_dvar['periodIndex']}'";
	return $sql;
}


/**
 * checkRowExists: Check, if a dataset is existing
 * @param 
 * @param 
 * @return boolean
 */
function checkRowExists($id){
	global $dbprefix;
	
	if(!is_object($id) || !$id->checkID() )return false;
	$whereID=$id->getWhere();
	$result=doDBQuery("SELECT COUNT(*) as 'count' FROM {$dbprefix}tbl_name_applies_to WHERE {$whereID}");
	$row = mysql_fetch_array($result);

	return ($row['count']==1);
}

/**
 * getAppliesQuery:
 * @param 
 * @param 
 * @return sql string
 */
function InsertUpdateCommonName(&$_dvar, $update=false){
	global $dbprefix;
	$msg=array();
	if(!$update && !checkRight('commonnameInsert')){
		return array("You have no Rights for Insert",0);
	}

	if($update && !checkRight('commonnameUpdate')){
		return array("You have no Rights for Update",0);
	}
	
	if ($update && !checkRight('unlock_tbl_name_applies_to') && isLocked($dbprefix.'tbl_name_applies_to', $_dvar['active_id'])){
		return array("You have no Rights for Update locked items",0);
	}
	
	if($update){
		$_dvar['update']=checkRowExists($_dvar['active_id']);
		
		// No Old Row...
		if(!$_dvar['update']){
			return array("No Set for update choosen",0);
		}
	}
	
	if(intval($_dvar['taxonIndex'])==0)$msg['tax']="Please insert valid Taxon";
	if(strlen($_dvar['taxon'])==0)$msg['tax']="Please insert Taxon";
	if(strlen($_dvar['common_name'])<3)$msg['cname']="Please insert valid Common Name";

	if (count($msg)!=0){
		$msg=implode("<br>",$msg);
		return array($msg,0);
	}

	// reference
	$_dvar['referenceIndex']=0;
	switch($_dvar['source']){
		default:
		case 'literature':
			clearFromDvar($_dvar,array('person','service'));
			
			$result = doDBQuery("SELECT literature_id FROM {$dbprefix}tbl_name_literature WHERE citationID='{$_dvar['literatureIndex']}'");
			if($result && $row=mysql_fetch_array($result)){
				$_dvar['referenceIndex']=$row['literature_id'];
			}else{
				$result = doDBQuery("INSERT INTO {$dbprefix}tbl_name_references (reference_id) VALUES (NULL)");
				$_dvar['referenceIndex']=mysql_insert_id();
				$result = doDBQuery("INSERT INTO {$dbprefix}tbl_name_literature (literature_id,citationID) VALUES ('{$_dvar['referenceIndex']}','{$_dvar['literatureIndex']}')");
			}
			break;

		case 'person':
			clearFromDvar($_dvar,array('literature','service'));
			
			$result = doDBQuery("SELECT person_id FROM {$dbprefix}tbl_name_persons WHERE personID='{$_dvar['personIndex']}'");
			if($result && $row=mysql_fetch_array($result)){
				$_dvar['referenceIndex']=$row['person_id'];
			}else{
				$result = doDBQuery("INSERT INTO {$dbprefix}tbl_name_references (reference_id) VALUES (NULL)");
				$_dvar['referenceIndex']=mysql_insert_id();
				$result = doDBQuery("INSERT INTO {$dbprefix}tbl_name_persons (person_id,personId) VALUES ('{$_dvar['referenceIndex']}','{$_dvar['personIndex']}')");
			}
			break;

		case 'service':
			clearFromDvar($_dvar,array('person','literature'));
			
			$result = doDBQuery("SELECT webservice_id FROM {$dbprefix}tbl_name_webservices WHERE serviceID='{$_dvar['serviceIndex']}'");
			if($result && $row=mysql_fetch_array($result)){
				$_dvar['referenceIndex']=$row['webservice_id'];
			}else{$result = doDBQuery("INSERT INTO {$dbprefix}tbl_name_references (reference_id) VALUES (NULL)");
				$_dvar['referenceIndex']=mysql_insert_id();
				$result = doDBQuery("INSERT INTO {$dbprefix}tbl_name_webservices (webservice_id,serviceId) VALUES ('{$_dvar['referenceIndex']}','{$_dvar['serviceIndex']}')");
			}
			break;
	}

	//Cache geoname
	$result = doDBQuery("INSERT INTO {$dbprefix}tbl_geonames_cache (geonameId, name) VALUES ('{$_dvar['geonameIndex']}','{$_dvar['geoname']}') ON DUPLICATE KEY UPDATE  geonameId=VALUES(geonameId)");

	// Language
	if($_dvar['languageIndex']==0 || $_dvar['languageIndex']==''){
		$result = doDBQuery("SELECT language_id FROM {$dbprefix}tbl_name_languages WHERE name='{$_dvar['language']}'");
		if($result && $row=mysql_fetch_array($result)){
			$_dvar['languageIndex']=$row['language_id'];
		}else{
			$result = doDBQuery("INSERT INTO {$dbprefix}tbl_name_languages (name,locked) VALUES ('{$_dvar['language']}','1')");
			$_dvar['languageIndex']=mysql_insert_id();
		}
	}
	
	//period
	$result = doDBQuery("INSERT INTO {$dbprefix}tbl_name_periods (period) VALUES ('{$_dvar['period']}') ON DUPLICATE KEY UPDATE period_id=LAST_INSERT_ID(period_id)");
	$_dvar['periodIndex']=mysql_insert_id();

	
	//NAMES
	//commonname
	$_dvar['nameIndex']=0;
	$result = doDBQuery("SELECT common_id FROM {$dbprefix}tbl_name_commons WHERE common_name='{$_dvar['common_name']}'");
	if($result && $row=mysql_fetch_array($result)){
		$_dvar['nameIndex']=$row['common_id'];
	}else{
		$result = doDBQuery("INSERT INTO {$dbprefix}tbl_name_names (name_id) VALUES (NULL)");
		$_dvar['nameIndex']=mysql_insert_id();
		$result = doDBQuery("INSERT INTO {$dbprefix}tbl_name_commons (common_id, common_name) VALUES ('{$_dvar['nameIndex']}','{$_dvar['common_name']}')");
		
		// log it
		logCommonNamesCommonName($_dvar['nameIndex'],0);
	}
	$_dvar['common_nameIndex']=$_dvar['nameIndex'];

	// ENTITY
	// taxon
	$_dvar['entityIndex']=0;
	$result = doDBQuery("SELECT taxon_id FROM {$dbprefix}tbl_name_taxa WHERE taxonID='{$_dvar['taxonIndex']}'");
	if($result && $row=mysql_fetch_array($result)){
		$_dvar['entityIndex']=$row['taxon_id'];
	}else{
		$result = doDBQuery("INSERT INTO {$dbprefix}tbl_name_entities (entity_id) VALUES (NULL)");
		$_dvar['entityIndex']=mysql_insert_id();
		$result = doDBQuery("INSERT INTO {$dbprefix}tbl_name_taxa (taxon_id, taxonID) VALUES ('{$_dvar['entityIndex']}','{$_dvar['taxonIndex']}')");
	}
	
	// save old id
	$where='';$old='';
	if($update){
		$where=$_dvar['active_id']->getWhere();
		$old=$_dvar['active_id']->toString();
	}
	
	// NEW ID
	$_dvar['active_id']->setNatID(array(
		'entity_id'=>$_dvar['entityIndex'],
		'name_id'=>$_dvar['nameIndex'],
		'geonameId'=>$_dvar['geonameIndex'],
		'language_id'=>$_dvar['languageIndex'],
		'period_id'=>$_dvar['periodIndex'],
		'reference_id'=>$_dvar['referenceIndex']
	));
	
	// Update fields
	$sql = $_dvar['active_id']->getIDFields();
	
	$sql.=", annotation='".doQuote($_dvar['annotation'])."'"
		 .", geospecification='".doQuote($_dvar['geospecification'])."'";

	if(checkRight('unlock_tbl_name_applies_to')){
		$sql .= ", locked = '{$_dvar['locked']}'";
	}

	
	// insert new dataset
	if($update){
		$sql="UPDATE {$dbprefix}tbl_name_applies_to SET {$sql} WHERE {$where} ";
		if(doDBQuery($sql)){
			// Log it
			logCommonNamesAppliesTo($_dvar['active_id'],1,$old);
			return array(0,"Successfully updated");
		}
	
	}else{
		$sql="INSERT INTO {$dbprefix}tbl_name_applies_to SET {$sql}";
		if(@doDBQuery($sql)){
			// Log it
			logCommonNamesAppliesTo($_dvar['active_id'],0);

			return array(0,"Successfully inserted");
		}
	}

	// If no insertion because of already there: Print Error Message
	if(mysql_errno()=='1062'){
		return array("already in database",0);
	}

	return array("Error ".mysql_errno() . ": " . mysql_error() . "",0);
}
/**
 * getAppliesQuery:
 * @param 
 * @param 
 * @return sql string
 */
function deleteCommonName($_dvar){
	global $dbprefix;
	
	if(!checkRight('admin')){
		return array("You have to be admin for deletation",0);
	}

	$_dvar['update']=checkRowExists($_dvar['active_id']);

	if(!$_dvar['update']){
		return array("No correct Set for deletion choosen",0);
	}
	
	$whereID=$_dvar['active_id']->getWhere();
	$sql="DELETE FROM {$dbprefix}tbl_name_applies_to WHERE {$whereID}";
	$result = doDBQuery($sql);

	if($result){
		return array(0, "Successfull deleted<br>The deleted Set is inserted in the fields above and can be inserted new.");
	}

	return array("Error ".mysql_errno() . ": " . mysql_error() . "",0);
}

/**
 * getAppliesQuery:
 * @param 
 * @param 
 * @return sql string
 */
function doSearch($_dvar,$get=false){
	global $_OPTIONS;

	// Mark collums that was searched...
	$class=array();
	$a='ac"';
	$b='';
	$class=array(
		'lock'=>'',
		'ent'=>(!checkempty($_dvar,$get,'taxon'))?$a:$b,
		'geo'=>(!checkempty($_dvar,$get,'geoname',1))?$a:$b,
		'lang'=>(!checkempty($_dvar,$get,'language',1))?$a:$b,
		'com'=>(!checkempty($_dvar,$get,'common_name'))?$a:$b,
		'per'=>(!checkempty($_dvar,$get,'period'))?$a:$b,
		'ref'=>(!checkempty($_dvar,$get,'literature')||!checkempty($_dvar,$get,'person')||!checkempty($_dvar,$get,'service'))?$a:$b,
		'ann'=>(!checkempty($_dvar,$get,'annotation'))?$a:$b,
	);

	// get search query
	$sql=getAppliesQuery($_dvar,$get);
	$sql.="\n LIMIT 101";
	
	$result = doDBQuery($sql);

	$i=0;$search_result='';
	while($row = mysql_fetch_array($result)){
		$taxon=getTaxon($row['taxonID']);
		
		$literature='';
		switch($row['source']){
			default: case 'literature':if($row['literatureID']!='0' && $row['literatureID']!='')$literature	=getLiterature($row['literatureID']);break;
			case 'service':if($row['serviceID']!='')$literature=getService($row['serviceID']);break;
			case 'person':if($row['personID']!='')$literature	=getPerson($row['personID']);break;
		}

		$lan=$row['iso639-6']!=""?"{$row['iso639-6']} ({$row['language']})":($row['language']!=""?"{$row['language']}":"");
		
		$geo="";
		if($row['geoname']!='' || $row['geospecification']!=''){
			//$geo=strstr($row['geoname'],',',true);
			$geo=substr($row['geoname'],0,strpos($row['geoname'],','));
			if($row['geospecification']){
				$geo.="<br><em> ({$row['geospecification']})</em>";
			}
		}
		
		$trclass=($i%2)?'odd':'even';
		$eo=($i%2)?'o':'e';
/*
//dbeug
<td class="{$eo}{$class['ent']}">{$taxon}</td><td class="{$eo}{$class['com']}">{$row['common_name']}</td><td class="{$eo}{$class['geo']}">{$geo}</td><td class="{$eo}{$class['lang']}">{$lan}</td><td class="{$eo}{$class['per']}">{$row['period']}</td><td class="{$eo}{$class['ref']}">{$literature}</td>

// orig
<td class="{$eo}{$class['ent']}">{$taxon}</td><td class="{$eo}{$class['com']}">{$row['common_name']}</td><td class="{$eo}{$class['geo']}">{$geo}</td><td class="{$eo}{$class['lang']}">{$lan}</td><td class="{$eo}{$class['per']}">{$row['period']}</td><td class="{$eo}{$class['ref']}">{$literature}</td>
*/

		$locked=$row['locked']?'<b>&lt;locked&gt;</b><br>':'';
		$search_result.=<<<EOF
<tr onclick="selectID('{$row['entity_id']}:{$row['name_id']}:{$row['geoname_id']}:{$row['language_id']}:{$row['period_id']}:{$row['reference_id']}')" >
<td class="{$eo}{$class['lock']}">{$locked}</td><td class="{$eo}{$class['ent']}">{$taxon}</td><td class="{$eo}{$class['com']}">{$row['common_name']}</td><td class="{$eo}{$class['geo']}">{$geo}</td><td class="{$eo}{$class['lang']}">{$lan}</td><td class="{$eo}{$class['per']}">{$row['period']}</td><td class="{$eo}{$class['ref']}">{$literature}</td><td class="{$eo}{$class['ann']}">{$row['annotation']}</td></tr>
EOF;
		$i++;
	}
	$msg2='';

	if($i>=100){
		$msg2="<br>more than 100 sets matched, but onyl 100 shown. Sorting only in the loaded sets possible.";
	}
	// Make Table
	$search_result=<<<EOF
{$msg2}
<table id="sorttable" cellspacing="0" cellpadding="0" class="tablesorter" border="1" style="border: 1px solid #000;border-collapse:collapse" width="700">
<colgroup><col width="10px"><col width="16%"><col width="16%"><col width="16%"><col width="16%"><col width="16%"><col width="16%"><col width="16%"></colgroup>
<thead>
<tr>
 <th class="l{$class['lock']}"><span>Locked</span></th><th class="l{$class['ent']}"><span>Entity</span></th><th class="l{$class['com']}"><span>Common Name</span></th><th class="l{$class['geo']}"><span>Geography</span></th><th class="l{$class['lang']}"><span>Language</span></th><th class="l{$class['per']}"><span>Period</span></th><th class="l{$class['ref']}"><span>Reference</span></th><th class="l{$class['ann']}"><span>Annotation </span></th>
</tr>
</thead>
<tbody>

{$search_result}

</tbody>
</table>
<script>
$(function(){
	$("#sorttable tbody tr").hover(
		function(){ $(this).find('td').css('background-color','#ffff99');},
		function(){ $(this).find('td').css('background-color','');}
	);
	$("#sorttable").tablesorter();
});

function selectID(active_id){
	document.location.href='{$_SERVER['PHP_SELF']}?show=1&id='+active_id+'&enableClose={$_dvar['enableClose']}';
}

</script>
EOF;
	return $search_result;
}

/**
 * getAppliesQuery:
 * @param 
 * @param 
 * @return sql string
 */
function cleanPair($name,$def=0){
	global $_dvar;
	if(strlen($_dvar[$name])==0){
		$_dvar[$name.'Index']=$def;
		$_dvar[$name]='';
	}else if(intval($_dvar[$name.'Index'])==0){
		$_dvar[$name.'Index']='';
	}
}

/**
 * getAppliesQuery:
 * @param 
 * @param 
 * @return sql string
 */
function checkempty(&$_dvar,$strict=true,$name='',$not=''){

	if(isset($_dvar[$name.'Index']) && intval($_dvar[$name.'Index'])>0 && ($_dvar[$name]!='' || !$strict)){
		if($not=='' || ($not!='' && $_dvar[$name.'Index']!=$not))
			return false;
	}
	return true;

}

/**
 * getAppliesQuery:
 * @param 
 * @param 
 * @return sql string
 */
function clearFromDvar(&$_dvar,$ent){
	foreach($ent as $val){
		$_dvar[$val]='';
		$_dvar[$val.'Index']='';
	}
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

/**
 * doDBQuery:
 * @param 
 * @param 
 * @return sql string
 */
function doQuote($var){
	return mysql_real_escape_string($var);
}

// Natural ID
class natID{
	var $natID;
	var $map;
	var $token=':';
	function __construct($map=array()){
		$this->map=array_flip($map);
	}
	
	function setNatID($natID){
		$this->natID=array();
		foreach($natID as $id=>$val){
			if(isset($this->map[$id])){
				$this->natID[$id]=$val;
			}
		}
	}
	function setNatIDFromString($natID){
		$order=array_flip($this->map);
		$natID=explode($this->token,$natID);
		$this->natID=array();
		foreach($order as $ord=>$id){
			$this->natID[$id]=$natID[$ord];
		}
	}
	function toString($natID=array()){
		$order=array_flip($this->map);
		$str='';
		$natID=(count($natID)>0)?$natID:$this->natID;
		foreach($order as $id){
			if(isset($natID[$id])){
				$str.=$this->token.$natID[$id];
			}
		}
		return substr($str,1);
	}
	
	function checkID($natID=array()){
		$order=array_flip($this->map);
		$natID=(count($natID)>0)?$natID:$this->natID;
		foreach($order as $ord=>$id){
			if($natID[$id]==''){
				return false;
			}
		}
		return true;
	}
	
	function getWhere($natID=array(),$alias=''){
		$where='';
		$natID=(count($natID)>0)?$natID:$this->natID;
		if(count($natID)==0)return false;
		foreach($natID as $col=>$val){
			$where.=" and {$alias}{$col}='{$val}'";
		}
		$where=substr($where,4);
		return $where;
	}
	
	function getIDFields($natID=array(),$alias=''){
		$insert='';
		$natID=(count($natID)>0)?$natID:$this->natID;
		foreach($natID as $col=>$val){
			$insert.=" {$alias}{$col}='{$val}',";
		}

		$insert=substr($insert,0,-1);
		return $insert;
	}	
}



// Helper... to be moved or reorganized...


function getService($serviceID){
	$sql = "
SELECT
 serviceID,
 name,
 url_head
FROM
 tbl_nom_service
WHERE
  serviceID='{$serviceID}'
";

	$result = doDBQuery($sql);
	$row = mysql_fetch_array($result);

	return "{$row['name']} ({$row['serviceID']}, {$row['url_head']})";
}


function getPerson($personID){
	$sql="
SELECT person_ID, p_familyname, p_firstname, p_birthdate, p_death
FROM
 tbl_person
WHERE
 person_ID='{$personID}'
";
    $result = doDBQuery($sql);
	$row = mysql_fetch_array($result);
	return $row['p_familyname'] . ", " . $row['p_firstname'] . " (" . $row['p_birthdate'] . " - " . $row['p_death'] . ") <" . $row['person_ID'] . ">";
}






// Copied from ??
function getTaxon($taxon_id){
	$sql = "
SELECT
 taxonID, tg.genus,
 ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
 ta4.author author4, ta5.author author5,
 te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
 te4.epithet epithet4, te5.epithet epithet5
                FROM tbl_tax_species ts
 LEFT JOIN tbl_tax_authors ta ON ta.authorID = ts.authorID
 LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID = ts.subspecies_authorID
 LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID = ts.variety_authorID
 LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID = ts.subvariety_authorID
 LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID = ts.forma_authorID
 LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID = ts.subforma_authorID
 LEFT JOIN tbl_tax_epithets te ON te.epithetID = ts.speciesID
 LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID = ts.subspeciesID
 LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID = ts.varietyID
 LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID = ts.subvarietyID
 LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID = ts.formaID
 LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID = ts.subformaID
 LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
WHERE
 taxonID = '" . $taxon_id . "'";

	$result = doDBQuery($sql);
	$row = mysql_fetch_array($result);
	return taxon($row);
}

// Copied From ??
function getliterature($literatur_id){
	 $sql = "
SELECT
 citationID, suptitel, le.autor as editor, la.autor, l.periodicalID, lp.periodical, vol, part, jahr, pp
FROM
 tbl_lit l
 LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID = l.periodicalID
 LEFT JOIN tbl_lit_authors le ON le.autorID = l.editorsID
 LEFT JOIN tbl_lit_authors la ON la.autorID = l.autorID
WHERE
 citationID = '" . $literatur_id . "'";
	$result = doDBQuery($sql);
	return protolog(mysql_fetch_array($result));
}
?>