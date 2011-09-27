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
  <title>herbardb - edit CommonNames</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <link rel="stylesheet" type="text/css" href="inc/jQuery/css/south-street/jquery-ui-1.8.14.custom.css">
   <link rel="stylesheet" href="inc/jQuery/jquery_autocompleter_freud.css" type="text/css" />
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
  <script type="text/javascript" src="inc/jQuery/jquery.tablesorter_nhm.js"></script>
  <script type="text/javascript" src="inc/jQuery/jquery_autocompleter_freud.js"></script>

  <script type="text/javascript" language="JavaScript">
  var geowin;
// windows...
function selectTaxon() {
	//taxonID=$('#taxonIndex').val();
	taxwin = window.open("listTaxCommonName.php", "selectTaxon", "width=600, height=500, top=50, right=50, scrollbars=yes, resizable=yes");
	taxwin.focus();
}
function UpdateTaxon(taxonID) {
	ACdoSearchID('taxon',taxonID);
}


function selectLiterature() {
	//literatureID=$('#literatureIndex').val();
	citwin = window.open("listLitCommonName.php", "selectliteratur", "width=600, height=500, top=50, right=50, scrollbars=yes, resizable=yes");
	citwin.focus();
}
function UpdateLiterature(literatureID) {
	ACdoSearchID('sourceval',literatureID);
}

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
	ACdoSearchID('geoname',geonameID);
/*	if(!data){
			$('#ajax_geoname').val('geonames.org databases not synced yet for ID '+geonameID+'. But you can store it anyway.');
			$('#geonameIndex').val(geonameID);
	}
*/
}

function editCommoname(){
	cid='?a=b';
	if($('#common_nameIndex').val()!='')cid+='&common_nameIndex='+$('#common_nameIndex').val();
	
	comwin = window.open("editCommonName.php"+cid, "editCommonName", "width=850 height=500, top=50, right=50, scrollbars=auto, resizable=yes");
	comwin.focus();
}


function changeSource(to){
	if(to=='service'){
		url='index_jq_autocomplete_commoname.php?field=cname_service';
	}else if(to=='person'){
		url='index_jq_autocomplete.php?field=person';
	}else/* if(to=='literature')*/{
		url='index_jq_autocomplete.php?field=citation';
	}
	
	$('#ajax_sourcevalue').autocomplete('option' , 'serverScript' ,url );
	$('#ajax_sourcevalue').val('');
	$('#sourcevalueIndex').val('');
	
}

$(document).ready(function() {
	// selection

	$('#sourceType').change(function() {
		changeSource($('#sourceType').val());
	});
	ACFreudInit();
	
	$('#ajax_taxon').focus();

	$( '#ajax_geospecification').resizable({handles:'se'});//.css('z-index','10000');
	
	$('#ajax_common_name').change(function(){$(this).trigger('blur');}).blur(function(){
		$('#ajax_transliteration').attr('disabled', true);
		
		ACdoSearchExact('common_name', $('#ajax_common_name').val());
	});
	
	$('#ajax_common_name').bind('afterACchangetrigger',function(){
		
		if($('#common_nameIndex').val()!=''){
			ACdoSearchID('transliteration', 'c'+$('#common_nameIndex').val());
		}else{
			$('#ajax_transliteration').removeAttr('disabled');
			$('#ajax_common_name').addClass('newItem');
		}
	});
	
	$('#ajax_common_name').trigger('change');
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
	if($("#sourcevalue").val()=='')t+="<br>&nbsp  - Missing "+$('#source').val()+" Reference";
	if($("#sourcevalueIndex").val()=='')t+="<br>&nbsp  - Invalid "+$('#source').val()+" Reference";
	if(t!=""){if(msg!='')msg+="<br>";msg+=" - Reference-Type: Literatur"+t;}
	
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
$quotesdone=0;
	

// dataVar
$_dvar=array(
	'entityIndex'		=> '',
	'entityType'		=> 'taxon',
	'taxonIndex'		=> '',
	
	'nameIndex'			=> '',
	'common_nameIndex'	=> '',
	'common_name'		=> '',
	
	'transliteration'	=> '',
	'transliterationIndex'=> '',
	
	'geonameIndex'		=> '',
	'geoname'			=> '',
	
	'geospecification'=> '',
	
	'languageIndex'		=> '',
	'language'			=> '',
	
	'tribeIndex'		=> '',
	'tribe_name'		=> '',
	
	'periodIndex'		=> '',
	'period'			=> '',

	'referenceIndex'	=> '',
	'sourceType'		=> 'literature',
	'sourcevalueIndex'	=> '',

	'annotations'		=> '',
	
	'locked'			=> '1',
	'active_id'	=>	new natID(array('entity_id','name_id','geonameId','language_id','period_id','reference_id','tribe_id')),

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

if( in_array($action,array('doDelete' ,'doSearch','doInsert','doUpdate'))!==false ){
	
	// clean pairs...
	foreach($_POST as $k=>$v){
		if(preg_match('/ajax_(?P<name>.+)/', $k, $matches)==1){
			if(strlen($v)==0 && $v!='0'){
				$_POST[ $matches['name'].'Index' ]='';
			}
		}
	}
	
	$errr=error_reporting($debuger);
	$_dvar=array_merge($_dvar, array(
		'taxonIndex' => $_POST['taxonIndex'],
		
		'nameIndex'			=> $_POST['common_nameIndex'],
		'common_nameIndex'	=> $_POST['common_nameIndex'],
		'common_name'		=> $_POST['ajax_common_name'],
		
		'transliteration'	=>  $_POST['ajax_transliteration'],
		'transliterationIndex'=>$_POST['transliterationIndex'],
	
		'geonameIndex'		=> $_POST['geonameIndex'],
		'geoname'			=> $_POST['ajax_geoname'],

		'geospecification'	=> $_POST['ajax_geospecification'],
		
		'tribe_name'		=> $_POST['ajax_tribe'],
		'tribeIndex'		=> $_POST['tribeIndex'],
		
		'languageIndex'		=> $_POST['languageIndex'],
		'language'			=> $_POST['ajax_language'],

		'periodIndex'		=> $_POST['periodIndex'],
		'period'			=> $_POST['ajax_period'],

		'sourceType'		=> isset($_POST['sourceType'])?$_POST['sourceType']:$_dvar['sourceType'],
		'sourcevalueIndex'	=> $_POST['sourcevalueIndex'],

		'annotations'		=> $_POST['annotations'],
		'locked'			=> (isset($_POST['locked'])&&$_POST['locked']=='on')?1:$_dvar['locked'],
	));
	error_reporting($errr);
	if($action!='doSearch' && $_POST['active_id']!=''){
		$_dvar['active_id']->setNatIDFromString($_POST['active_id']);
	}
	
	doQuotes($_dvar,1);
	$quotesdone=1;

		
	//print_r($_dvar);
	
}


// Delete action
if ($action=='doDelete' ) {
	list($msg['err'],$msg['result'])=deleteCommonName($_dvar);

	// Insert/Update
}else if($action=='doInsert' || $action=='doUpdate') {

	list($msg['err'],$msg['result'])=InsertUpdateCommonName($_dvar,$action=='doUpdate');
	$doSearch=true;
	$searchNameNotEmpty=false;
// Show a Common Name Set with given GET vars...
}else if(isset($_GET['show'])) {
	
	$_dvar['active_id']->setNatIDFromString($_GET['id']);
	$sql=getAppliesQuery()." and ".$_dvar['active_id']->getWhere(array(),'a.');
	$result = doDBQuery($sql);
	

	if($result && ($row = mysql_fetch_assoc($result) ) && mysql_num_rows($result)==1 ){
		$_dvar=array_merge($_dvar, array(
			'entityIndex'=>$row['entity_id'],
			'entityType'		=> 'taxon',
			'taxonIndex'=>$row['taxonID'],
			
			'nameIndex'=>$row['name_id'],
			'common_nameIndex'=>$row['name_id'],
			'common_name'=>$row['common_name'],
			
			'transliteration'	=>  $row['transliteration'],
			'transliterationIndex'	=>  $row['transliterationIndex'],
	
	
			'geonameIndex'=>$row['geoname_id'],
			'geoname'=>$row['geoname'],
			
			'geospecification'=> $row['geospecification'],
			
			'languageIndex'=>$row['language_id'],
			'language'=>$row['language'],

			'tribe_name'		=>  $row['tribe_name'],
			'tribeIndex'		=> $row['tribe_id'],
			
			'periodIndex'=>$row['period_id'],
			'period'=>$row['period'],
			
			'referenceIndex'=>$row['reference_id'],
			'sourceType'		=> $row['sourceType'],
			'sourcevalueIndex'	=> $row['sourcevalueIndex'],
			
			'annotations'=>isset($row['annotations'])?$row['annotations']:$_dvar['annotations'],
			
			'locked'=>isset($row['locked'])?$row['locked']:$_dvar['locked'],
		));
		echo<<<EOF
<script>
$(document).ready(function() {
	$('#ajax_transliteration').attr('disabled', true);
});
</script>
EOF;
		$_POST['ACREALUPDATE']=1;
		
	// If no row found, prepare for search vars;
	}else{
		$doSearch=true;
		$searchNameNotEmpty=true;
	}

// Do Search
}else if($action=='doSearch'){
	$doSearch=true;
	$searchNameNotEmpty=false;
}
doQuotes($_dvar,($quotesdone==1)?2:3);

if($doSearch){
	$search_result=doSearch($_dvar,$searchNameNotEmpty);
}
//print_r($_dvar);


$source_sel[$_dvar['sourceType']]=' checked';


// Check if update is possible (selected Row is existing)
$_dvar['update']=checkRowExists($_dvar['active_id']);
$_dvar['active_id']=$_dvar['active_id'];

$msgs='';

if($msg['result']=="0"){
	$msgs.=" Error:<br>{$msg['err']} ";
}else if(strlen($msg['result'])>0){
	$msgs.=" Success:<br>{$msg['result']} ";
}

echo <<<EOF

<script type="text/javascript" language="JavaScript">

$(document).ready(function() {

});
</script>
EOF;

$isLocked=isLocked($dbprefix.'tbl_name_applies_to', $_dvar['active_id']);
$unlock_tbl_name_applies_to=checkRight('unlock_tbl_name_applies_to');
$cf = new CSSF();
$cf->setYRelative(true);


echo "<input type=\"hidden\" name=\"enableClose\" value=\"{$_dvar['enableClose']}\">\n";
echo "<input type=\"hidden\" name=\"action\" id=\"action\" value=\"\">\n";
echo "<input type=\"hidden\" name=\"active_id\" value=\"".$_dvar['active_id']->toString()."\">\n";

$cf->label(60.5,1,"locked");
if($unlock_tbl_name_applies_to) {
    $cf->checkbox(60.5,0,"locked",$_dvar['locked']);
}else if($isLocked){
    echo "<input type=\"hidden\" name=\"locked\" value=\"{$_dvar['locked']}\">\n";
}
if($_dvar['enableClose']){
	$cf->buttonJavaScript(11, 0, " Close Window", "window.opener.location.reload(true);self.close()");
}


$cf->label(10, 2.5, "Scientific Name","javascript:selectTaxon()");
$cf->inputJqAutocomplete2(11, 0, 50, "taxon", $_dvar['taxonIndex'], "index_jq_autocomplete.php?field=taxon",520,2,'','',1,1,0);

$cf->label(10, 2.5, "Common Name","javascript:editCommoname()");
$cf->inputJqAutocomplete2(11, 0, 50, "common_name",$_dvar['common_nameIndex'], "index_jq_autocomplete_commoname.php?field=cname_commonname",520,2,'','',0,0,0);

$cf->label(10, 2.5, "transliteration","javascript:editCommoname()");
$cf->inputJqAutocomplete2(11, 0, 50, "transliteration",$_dvar['transliterationIndex'], "index_jq_autocomplete_commoname.php?field=cname_transliteration",520,2,'','',0,0,0);

$cf->label(10, 2.5, "Geography","javascript:selectGeoname()");
$cf->label(10.5, 0, "*","javascript:showGeonameInfo()");
$cf->inputJqAutocomplete2(11, 0, 50, "geoname", $_dvar['geonameIndex'], "index_jq_autocomplete_commoname.php?field=cname_geoname",520,2,'','',1,1,0);

$cf->label(10, 2.5, "Geo Specification");
$cf->inputJqAutocomplete2(14, 0, 47, "geospecification", $_dvar['geospecification'],"index_jq_autocomplete_commoname.php?field=cname_geospecification",520,2,'','',0,0,1);

$cf->label(10, 2.5, "Tribe");
$cf->inputJqAutocomplete2(11, 0, 50, "tribe", $_dvar['tribeIndex'],"index_jq_autocomplete_commoname.php?field=cname_tribe",520,2,'','',0,0,0);


$cf->label(10, 2.5, "Language");
$cf->inputJqAutocomplete2(11, 0, 50, "language", $_dvar['languageIndex'], "index_jq_autocomplete_commoname.php?field=cname_language",520,2,'','',0,1,0);

$cf->label(10, 2.5, "Period");
$cf->inputJqAutocomplete2(11,0, 50, "period", $_dvar['periodIndex'], "index_jq_autocomplete_commoname.php?field=cname_period",520,2,'','',0,0,0);

$ac_url=array(
	'literature'=>'index_jq_autocomplete.php?field=citation',
	'service'=>'index_jq_autocomplete_commoname.php?field=cname_service',
	'person'=>'index_jq_autocomplete.php?field=person'
);

$cf->nameIsID=true;
$cf->dropdown(4,2.5,"sourceType",$_dvar['sourceType'],array('literature','service','person'),array('Literature','Service','Person'));
$cf->inputJqAutocomplete2(11,0, 50, "sourcevalue", $_dvar['sourcevalueIndex'], $ac_url[ $_dvar['sourceType'] ],520,2,'','',1,1,0);

$cf->label(10, 2.5, "annotations");
$cf->textarea(11, 0, 50,2.5, "annotations", $_dvar['annotations'], "", "", "");


if(($_SESSION['editControl'] & 0x20000) != 0){
	echo "<input style=\"display:none\" type=\"submit\" name=\"submitInsert\" value=\" Insert New\">";
}

$cf->buttonJavaScript(17, 4, " Reset ", "document.location.reload(true);");


if($_dvar['update'] &&  ($_SESSION['editControl'] & 0x10000) != 0  && ($unlock_tbl_name_applies_to || !$isLocked) ){
	$cf->buttonSubmit(22, 0, "submitUpdate", " Update");
	$cf->buttonSubmit(28, 0, "submitDelete", " Delete");
}

if(($_SESSION['editControl'] & 0x20000) != 0 ){
	$cf->buttonSubmit(33, 0, "submitInsert", " Insert New");
}

$cf->setYRelative(false);
$cf->buttonSubmit(20, 1, "submitSearch", " Search");

echo<<<EOF
<div style="position: absolute; left: 11em; top: 33em; width:672px;">
{$msgs}
</div>
<div style="position: absolute; left: 2em; top: 36em; width:2024px; ">
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

<?PHP
/*
  FUNCTION SECTION

*/


/**
 * getAppliesQuery:
 * @param 
 * @param 
 * @return sql string
 */

function getAppliesQuery($_dvar=array(),$strict=false){
	global $dbprefix;
	
	$sql="
SELECT
 a.entity_id as 'entity_id',
 tax.taxonID as 'taxonID',

 a.name_id as 'name_id',
 com.common_name as 'common_name',

 translit.transliteration_id as 'transliterationIndex',
 translit.name as 'transliteration',
 
 a.geonameId as 'geoname_id',
 geo.name as 'geoname',
 
 a.geospecification as 'geospecification',
 
 a.language_id as 'language_id',
 lan.`iso639-6` as 'iso639-6',
 lan.name as 'language',
 lan.`parent_iso639-6` as 'parent_iso639-6',
 
 trib.tribe_id as 'tribe_id',
 trib.tribe_name as 'tribe_name',
 
 a.period_id as 'period_id',
 per.period as 'period',

 a.reference_id as 'reference_id',
 CASE
  WHEN pers.personID THEN 'person'
  WHEN ser.serviceID THEN 'service'
  ELSE 'literature'
 END as 'sourceType',
 
 CASE
  WHEN pers.personID THEN pers.personID
  WHEN ser.serviceID THEN ser.serviceID
  ELSE lit.citationID
 END as 'sourcevalueIndex',

 a.annotations as 'annotations',
 a.locked as 'locked'
FROM
 {$dbprefix}tbl_name_applies_to a
 LEFT JOIN {$dbprefix}tbl_name_entities ent ON ent.entity_id = a.entity_id
 LEFT JOIN {$dbprefix}tbl_name_taxa tax ON tax.taxon_id = ent.entity_id

 LEFT JOIN {$dbprefix}tbl_name_names nam ON nam.name_id = a.name_id
 LEFT JOIN {$dbprefix}tbl_name_commons com ON com.common_id = nam.name_id
 LEFT JOIN {$dbprefix}tbl_name_transliterations translit ON translit.transliteration_id=nam.transliteration_id
 
 
 LEFT JOIN {$dbprefix}tbl_geonames_cache geo ON geo.geonameId = a.geonameId
 LEFT JOIN {$dbprefix}tbl_name_languages lan ON  lan.language_id = a.language_id
 LEFT JOIN {$dbprefix}tbl_name_periods per ON per.period_id= a.period_id

 LEFT JOIN {$dbprefix}tbl_name_references ref ON ref.reference_id = a.reference_id
 LEFT JOIN {$dbprefix}tbl_name_persons pers ON pers.person_id = ref.reference_id
 LEFT JOIN {$dbprefix}tbl_name_literature lit ON lit.literature_id = ref.reference_id
 LEFT JOIN {$dbprefix}tbl_name_webservices ser ON ser.webservice_id = ref.reference_id
 
 LEFT JOIN {$dbprefix}tbl_name_tribes trib ON trib.tribe_id=a.tribe_id
 
 
WHERE
 1=1 
";
 	if(count($_dvar)==0)return $sql;
	
	if(!checkempty($_dvar,$strict,'taxon'))$sql.="\n and tax.taxonID='{$_dvar['taxonIndex']}'";
	if(!checkempty($_dvar,$strict,'common_name'))$sql.="\n and com.common_id = '{$_dvar['common_nameIndex']}'";
	if(!checkempty($_dvar,$strict,'transliteration'))$sql.="\n and translit.transliteration_id = '{$_dvar['transliterationIndex']}'";
	if(!checkempty($_dvar,$strict,'geoname'))$sql.="\n and geo.geonameID = '{$_dvar['geonameIndex']}'";
	if(!checkempty($_dvar,$strict,'geospecification'))$sql.="\n and a.geospecification = '{$_dvar['geospecification']}'";
	if(!checkempty($_dvar,$strict,'tribe'))$sql.="\n and trib.tribe_id = '{$_dvar['tribeIndex']}'";
	if(!checkempty($_dvar,$strict,'language'))$sql.="\n and a.language_id ='{$_dvar['languageIndex']}'";
	if(!checkempty($_dvar,$strict,'period'))$sql.="\n and per.period_id = '{$_dvar['periodIndex']}'";
	
 	switch($_dvar['sourceType']){
		default: case 'literature':if(!checkempty($_dvar,$strict,'literature'))$sql.="\n and lit.citationID ='{$_dvar['sourcevalueIndex']}'";break;
		case 'service':if(!checkempty($_dvar,$strict,'service'))$sql.="\n and ser.serviceID ='{$_dvar['sourcevalueIndex']}'";break;
		case 'person':if(!checkempty($_dvar,$strict,'person'))$sql.="\n and pers.personID ='{$_dvar['sourcevalueIndex']}'";break;
	}
	if(!checkempty($_dvar,$strict,'annotations'))$sql.="\n and a.annotations= '{$_dvar['annotations']}'";
	
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
	$row = mysql_fetch_assoc($result);

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
	if(strlen($_dvar['common_name'])<1)$msg['cname']="Please insert valid Common Name";

	if (count($msg)!=0){
		$msg=implode("<br>",$msg);
		return array($msg,0);
	}
	
	// reference
	$_dvar['referenceIndex']=0;
	switch($_dvar['sourceType']){
		default:
		case 'literature':
			
			$result = doDBQuery("SELECT literature_id FROM {$dbprefix}tbl_name_literature WHERE citationID='{$_dvar['sourcevalueIndex']}'");
			if($result && $row=mysql_fetch_assoc($result)){
				$_dvar['referenceIndex']=$row['literature_id'];
			}else{
				$result = doDBQuery("INSERT INTO {$dbprefix}tbl_name_references (reference_id) VALUES (NULL)");
				$_dvar['referenceIndex']=mysql_insert_id();
				$result = doDBQuery("INSERT INTO {$dbprefix}tbl_name_literature (literature_id,citationID) VALUES ('{$_dvar['referenceIndex']}','{$_dvar['sourcevalueIndex']}')");
			}
			break;

		case 'person':
			$result = doDBQuery("SELECT person_id FROM {$dbprefix}tbl_name_persons WHERE personID='{$_dvar['sourcevalueIndex']}'");
			if($result && $row=mysql_fetch_assoc($result)){
				$_dvar['referenceIndex']=$row['person_id'];
			}else{
				$result = doDBQuery("INSERT INTO {$dbprefix}tbl_name_references (reference_id) VALUES (NULL)");
				$_dvar['referenceIndex']=mysql_insert_id();
				$result = doDBQuery("INSERT INTO {$dbprefix}tbl_name_persons (person_id,personId) VALUES ('{$_dvar['referenceIndex']}','{$_dvar['sourcevalueIndex']}')");
			}
			break;

		case 'service':
			$result = doDBQuery("SELECT webservice_id FROM {$dbprefix}tbl_name_webservices WHERE serviceID='{$_dvar['sourcevalueIndex']}'");
			if($result && $row=mysql_fetch_assoc($result)){
				$_dvar['referenceIndex']=$row['webservice_id'];
			}else{$result = doDBQuery("INSERT INTO {$dbprefix}tbl_name_references (reference_id) VALUES (NULL)");
				$_dvar['referenceIndex']=mysql_insert_id();
				$result = doDBQuery("INSERT INTO {$dbprefix}tbl_name_webservices (webservice_id,serviceId) VALUES ('{$_dvar['referenceIndex']}','{$_dvar['sourcevalueIndex']}')");
			}
			break;
	}

	//Cache geoname
	$result = doDBQuery("INSERT INTO {$dbprefix}tbl_geonames_cache (geonameId, name) VALUES ('{$_dvar['geonameIndex']}','{$_dvar['geoname']}') ON DUPLICATE KEY UPDATE  geonameId=VALUES(geonameId)");

	// Language
	if($_dvar['languageIndex']==0 || $_dvar['languageIndex']==''){
		$result = doDBQuery("SELECT language_id FROM {$dbprefix}tbl_name_languages WHERE name='{$_dvar['language']}'");
		if($result && $row=mysql_fetch_assoc($result)){
			$_dvar['languageIndex']=$row['language_id'];
		}else{
			$result = doDBQuery("INSERT INTO {$dbprefix}tbl_name_languages (name) VALUES ('{$_dvar['language']}')");
			$_dvar['languageIndex']=mysql_insert_id();
		}
	}
	
	//period
	$result = doDBQuery("INSERT INTO {$dbprefix}tbl_name_periods (period) VALUES ('{$_dvar['period']}') ON DUPLICATE KEY UPDATE period_id=LAST_INSERT_ID(period_id)");
	$_dvar['periodIndex']=mysql_insert_id();
	
	//tribe
	$result = doDBQuery("INSERT INTO {$dbprefix} tbl_name_tribes (tribe_name) VALUES ('{$_dvar['tribe_name']}') ON DUPLICATE KEY UPDATE tribe_id=LAST_INSERT_ID(tribe_id)");
	$_dvar['tribeIndex']=mysql_insert_id();

	
	//NAMES
	//commonname
	$_dvar['nameIndex']=0;
	$result = doDBQuery("SELECT common_id FROM {$dbprefix}tbl_name_commons WHERE common_name='{$_dvar['common_name']}'");
	if($result && $row=mysql_fetch_assoc($result)){
		$_dvar['common_nameIndex']=$row['common_id'];
		$_dvar['nameIndex']=$_dvar['common_nameIndex'];
	}else{
		$result = doDBQuery("INSERT INTO {$dbprefix} tbl_name_transliterations (name) VALUES ('{$_dvar['transliteration']}') ON DUPLICATE KEY UPDATE transliteration_id=LAST_INSERT_ID(transliteration_id)");
		$_dvar['transliterationIndex']=mysql_insert_id();
		
		$result = doDBQuery("INSERT INTO {$dbprefix}tbl_name_names (name_id,transliteration_id) VALUES (NULL,'{$_dvar['transliterationIndex']}')");
		$_dvar['nameIndex']=mysql_insert_id();
		$_dvar['common_nameIndex']=$_dvar['nameIndex'];
		$result = doDBQuery("INSERT INTO {$dbprefix}tbl_name_commons (common_id, common_name,locked) VALUES ('{$_dvar['common_nameIndex']}','{$_dvar['common_name']}','1')");
		
		// log it
		logCommonNamesCommonName($_dvar['common_nameIndex'],0);
	}
	
	// ENTITY
	// taxon
	$_dvar['entityIndex']=0;
	$result = doDBQuery("SELECT taxon_id FROM {$dbprefix}tbl_name_taxa WHERE taxonID='{$_dvar['taxonIndex']}'");
	if($result && $row=mysql_fetch_assoc($result)){
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
		'reference_id'=>$_dvar['referenceIndex'],
		'tribe_id'=>$_dvar['tribeIndex']
	));
	
	// Update fields
	$sql = $_dvar['active_id']->getIDFields();
	
	$sql.=", annotations='{$_dvar['annotations']}'"
		 .", geospecification='{$_dvar['geospecification']}'";

	if(checkRight('unlock_tbl_name_applies_to')){
		$sql .= ", locked = '{$_dvar['locked']}'";
	}

	
	// insert new dataset
	if($update){
		$sql="UPDATE {$dbprefix}tbl_name_applies_to SET {$sql} WHERE {$where} ";
		if(doDBQuery($sql)){
			// Log it
			logCommonNamesAppliesTo($_dvar['active_id'],1,$old);
			$_POST['ACREALUPDATE']=1;
			return array(0,"Successfully updated");
		}
	
	}else{
		$sql="INSERT INTO {$dbprefix}tbl_name_applies_to SET {$sql}";
		if(doDBQuery($sql)){
			// Log it
			logCommonNamesAppliesTo($_dvar['active_id'],0);
			$_POST['ACREALUPDATE']=1;
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
function doSearch($_dvar,$strict=false){
	global $_OPTIONS;

	// Mark collums that was searched...
	$class=array();
	$a='';
	$b='ac"';
	
	//todo...
	$class=array(
		'lock'=>'',
		'ent'=>(checkempty($_dvar,$strict,'taxon'))?$a:$b,
		'ges'=>$a,
		'com'=>(checkempty($_dvar,$strict,'common_name'))?$a:$b,
		'trans'=>(checkempty($_dvar,$strict,'transliteration'))?$a:$b,
		'geo'=>(checkempty($_dvar,$strict,'geoname'))?$a:$b,
		'geospec'=>(checkempty($_dvar,$strict,'geospecification'))?$a:$b,
		'tribe'=>(checkempty($_dvar,$strict,'tribe'))?$a:$b,
		'lang'=>(checkempty($_dvar,$strict,'language'))?$a:$b,
		'per'=>(checkempty($_dvar,$strict,'period'))?$a:$b,
		'ref'=>(checkempty($_dvar,$strict,'sourcevalue'))?$a:$b,
		'ann'=>(checkempty($_dvar,$strict,'annotations'))?$a:$b,
	);

	// get search query
	$sql=getAppliesQuery($_dvar,$strict);
	$sql.="\n LIMIT 101";
	$result = doDBQuery($sql);

	$i=0;$search_result='';
	while($row = mysql_fetch_assoc($result)){
		
		doQuotes($row,3);
		
		$taxon=getTaxon($row['taxonID']);
		
		$literature='';
		if($row['sourcevalueIndex']!=''){
			switch($row['sourceType']){
				default: case 'literature':$literature	=getLiterature($row['sourcevalueIndex']);break;
				case 'service':$literature=getService($row['sourcevalueIndex']);break;
				case 'person':$literature=getPerson($row['sourcevalueIndex']);break;
			}
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
		$locked=$row['locked']?'<b>&lt;locked&gt;</b><br>':'';
		
		$ganz=<<<EOF
	{$taxon}-{$row['common_name']}<br>{$row['transliteration']}<br>{$geo}<br>{$row['tribe_name']}<br>{$lan}<br>{$row['period']}<br>{$literature}<br>{$row['annotations']}	
EOF;
		
		$search_result.=<<<EOF
<tr onclick="selectID('{$row['entity_id']}:{$row['name_id']}:{$row['geoname_id']}:{$row['language_id']}:{$row['period_id']}:{$row['reference_id']}:{$row['tribe_id']}')" >
<td class="{$eo}{$class['lock']}">{$locked}</td><td class="{$eo}{$class['ent']}">{$ganz}</td><td class="{$eo}{$class['ent']}">{$taxon}</td><td class="{$eo}{$class['com']}">{$row['common_name']}</td>
<td class="{$eo}{$class['trans']}">{$row['transliteration']}</td><td class="{$eo}{$class['geo']}">{$row['geoname']}</td><td class="{$eo}{$class['geospec']}">{$row['geospecification']}</td>
<td class="{$eo}{$class['tribe']}">{$row['tribe_name']}</td><td class="{$eo}{$class['lang']}">{$lan}</td><td class="{$eo}{$class['per']}">{$row['period']}</td>
<td class="{$eo}{$class['ref']}">{$literature}</td><td class="{$eo}{$class['ann']}">{$row['annotations']}</td>

</tr>
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
<table id="sorttable" cellspacing="0" cellpadding="0" class="tablesorter" border="1" style="border: 1px solid #000;border-collapse:collapse">
<colgroup><col width="10px"><col width="500px"><col width="200px"><col width="200px"><col width="200px"><col width="200px"><col width="200px"><col width="200px"><col width="200px"><col width="200px"><col width="200px"><col width="200px"></colgroup>
<thead>
<tr>
 <th class="l{$class['lock']}"><span>Locked</span></th><th class="l{$class['ges']}"><span>Name</span></th><th class="l{$class['ent']}"><span>Scientific Name</span></th><th class="l{$class['com']}"><span>Common Name</span></th>
 <th class="l{$class['trans']}"><span>Transliteration</span></th><th class="l{$class['geo']}"><span>Geography</span></th><th class="l{$class['geospec']}"><span>Geospec</span></th><th class="l{$class['tribe']}"><span>Tribe</span></th>
 <th class="l{$class['lang']}"><span>Language</span></th><th class="l{$class['per']}"><span>Period</span></th><th class="l{$class['ref']}"><span>Reference</span></th>
 <th class="l{$class['ann']}"><span>annotations </span></th>
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
function checkempty($_dvar,$strict,$name){

	if(isset($_dvar[$name.'Index']) && intval($_dvar[$name.'Index'])>0){
		if(!$strict || strlen($_POST['ajax_'.$name])>0){
			return false;
		}
	}
	return true;

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
	return db_query($sql,$debug);
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
	$row = mysql_fetch_assoc($result);

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
	$row = mysql_fetch_assoc($result);
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
	$row = mysql_fetch_assoc($result);
	return taxon($row);
}

// Copied From ??
function getliterature($literatur_id){
	if($literatur_id=='0')return '';
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
	return protolog(mysql_fetch_assoc($result));
}
?>