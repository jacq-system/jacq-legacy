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
	$('#ajax_geoname').searchID(geonameID);
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
			formatItem: function(row) { $('#'+nam+'Index').val(''); return row[0]; }
  		}).change(function() {
			if($('#'+nam+'Index').val()==''){
				$('#ajax_'+nam).addClass('wrongItem');
			}
		});
	}else{
		$('#ajax_'+nam).autocomplete("index_autocomplete_commoname.php",{
			extraParams:{field:'cname_'+nam},
  			loadingClass: 'working',
			selectFirst: true,
			delay:100,
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



$(document).ready(function() {
	// selection
	$("#ajax_literature").change(function() {$('input[name=source][value=literature]').attr('checked','checked');});
	$("#ajax_person").change(function() {$('input[name=source][value=person]').attr('checked','checked');});
	$("#ajax_service").change(function() {$('input[name=source][value=service]').attr('checked','checked');});
	
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
				buttons:[{
						text: "Cancel",
						click: function(){
							$(this).dialog("close"); 
						}
					},{
						text: "Delete",
						click: function(){
							$('#action').val("doDelete");
							$("#sendto").submit();
						}
					}]
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
				{text: doInsert?"Insert New anyway":"Update Anyway",
				click: function(){
					$('#action').val(doInsert?"doInsert":"doUpdate");
					$("#sendto").submit();
				}},
				{text: "Cancel",
				click: function(){
					$(this).dialog("close"); 
				}}
			]
		});
		return false;
	}
	
	return true;
}

  </script>
</head>

<body>

<?php
$search_result='';
$_dvar['searchorder']='';
$doSearch=isset($_GET['search']);
$source_sel=array('service'=>'','person'=>'','literature'=>'');

$msg=array();
	
	
// dataVar
$_dvar=array(
	'entityIndex'		=> '',
	'taxonIndex'		=> '',
	'taxon'				=> '',

	'referenceIndex'	=> '',
	'source'			=> '',
	
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

	'oldselection'		=> '',
);

if(isset($_POST['submitDelete']))$_POST['action']='doDelete';
if(isset($_POST['submitUpdate']))$_POST['action']='doUpdate';
if(isset($_POST['submitInsert']))$_POST['action']='doInsert';
if(isset($_POST['doSearch']))$_POST['action']='doSearch';
if(!isset($_POST['action']))$_POST['action']='';

if( $_POST['action']=='doDelete' || $_POST['action']=='doSearch' || $_POST['action']=='doInsert' || $_POST['action']=='doUpdate'){
	
	$_dvar=array_merge($_dvar, array(
		'taxonIndex'=>$_POST['taxonIndex'],
		'taxon'=>$_POST['taxon'],

		'referenceIndex'	=>'',
		'source'			=> $_POST['source'],
	
		'literatureIndex'	=> $_POST['literatureIndex'],
		'literature'			=> $_POST['literature'],
		'serviceIndex'		=> $_POST['serviceIndex'],
		'service'			=> $_POST['service'],
		'personIndex'		=> $_POST['personIndex'],
		'person'			=> $_POST['person'],
		
		'geonameIndex'=>$_POST['geonameIndex'],
		'geoname'=>$_POST['geoname'],

		'languageIndex'=>$_POST['languageIndex'],
		'language'=>$_POST['language'],

		'periodIndex'=>$_POST['periodIndex'],
		'period'=>$_POST['period'],

		'nameIndex'=>$_POST['common_nameIndex'],
		'common_nameIndex'=>$_POST['common_nameIndex'],
		'common_name'=>$_POST['common_name'],

		'oldselection'=>$_POST['oldselection']
	));
	
	cleanPair('language');
	cleanPair('literature');
	cleanPair('service');
	cleanPair('person');
	cleanPair('language',1);
	cleanPair('geoname',1);
	
	// Delete action
	if ($_POST['action']=='doDelete'  ) {
		list($msg['err'],$msg['result'])=deleteCommonName($_dvar);
	// Insert/Update
	}else if($_POST['action']=='doInsert' || $_POST['action']=='doUpdate') {

		
		list($msg['err'],$msg['result'])=InsertUpdateCommonName($_dvar,$_POST['action']=='doUpdate');
		$doSearch=true;
	// Do Search
	}else if($_POST['action']=='doSearch'){
		$doSearch=true;	
	}

// Show a Common Name Set with given GET vars...
}else if(isset($_GET['show'])) {
	
	$_dvar=array_merge($_dvar, array(
		'taxonIndex'=>isset($_GET['taxonID'])?$_GET['taxonID']:'',
		'nameIndex'=>isset($_GET['name_id'])?$_GET['name_id']:'',
		'geonameIndex'=>isset($_GET['geoname_id'])?$_GET['geoname_id']:'',
		'languageIso'=>isset($_GET['iso639_6'])?$_GET['iso639_6']:'',
		'periodIndex'=>isset($_GET['period_id'])?$_GET['period_id']:'',
		'referenceIndex'=>isset($_GET['reference_id'])?$_GET['reference_id']:'',
		'source'=>'reference',
		'refIndex'=>isset($_GET['reference_id'])?$_GET['reference_id']:'',
	));
	$sql=getAppliesQuery($_dvar['taxonIndex'], '1', $_dvar['refIndex'], '1',$_dvar['source'], $_dvar['nameIndex'], '1', $_dvar['languageIso'], '1', $_dvar['geonameIndex'], '1', $_dvar['periodIndex'], '1');
	$result = doDBQuery($sql);
	$row = mysql_fetch_array($result);

	$_dvar=array_merge($_dvar, array(
		'entityIndex'=>$row['entity_id'],
		'referenceIndex'=>$row['reference_id'],
		
		'taxonIndex'=>isset($row['taxonID'])?$row['taxonID']:$_dvar['taxonIndex'],
		
		'geonameIndex'=>isset($row['geoname_id'])?$row['geoname_id']:$_dvar['geonameIndex'],
		'geoname'=>$row['geoname'],

		'languageIndex'=>$row['language_id'],
		'language'=>$row['language'],

		'periodIndex'=>isset($row['period_id'])?$row['period_id']:$_dvar['periodIndex'],
		'period'=>$row['period'],

		'nameIndex'=>isset($row['name_id'])?$row['name_id']:$_dvar['nameIndex'],
		'common_nameIndex'=>isset($row['name_id'])?$row['name_id']:$_dvar['common_nameIndex'],
		'common_name'=>$row['common_name'],
		'source'=>$row['source'],
		
		'literatureIndex'=>isset($row['literatureID'])?$row['literatureID']:$_dvar['literatureIndex'],
		'personIndex'=>isset($row['personID'])?$row['personID']:$_dvar['personIndex'],
		'serviceIndex'=>isset($row['serviceID'])?$row['serviceID']:$_dvar['serviceIndex'],
		
		'oldselection'=>'',
	));
}

$init="
var init={taxon:'{$_dvar['taxonIndex']}',geoname:'{$_dvar['geonameIndex']}',language:'{$_dvar['languageIndex']}',literature:'{$_dvar['literatureIndex']}',service:'{$_dvar['serviceIndex']}',person:'{$_dvar['personIndex']}'};
var init2={period:'',common_name:''};
";
	
$_dvar['enableClose']=((isset($_POST['enableClose'])&&$_POST['enableClose']==1)||(isset($_GET['enableClose'])&&$_GET['enableClose']==1))?1:0;

if(!isset($_dvar['source']))$_dvar['source']='literature';
$source_sel[$_dvar['source']]=' checked';

// Check if update is possible (selected Row is existing)
$_dvar['update']=checkRowExists($_dvar['entityIndex'],$_dvar['nameIndex'],$_dvar['geonameIndex'],$_dvar['languageIndex'],$_dvar['periodIndex'],$_dvar['referenceIndex']);
if($_dvar['update']){
	$_dvar['oldselection']="{$_dvar['entityIndex']}:{$_dvar['nameIndex']}:{$_dvar['geonameIndex']}:{$_dvar['languageIndex']}:{$_dvar['periodIndex']}:{$_dvar['referenceIndex']}";
}

if($doSearch){
	$search_result=doSearch($_dvar);
}
$msg=implode('<br>',$msg);
?>

<form Action="<?php echo $_SERVER['PHP_SELF']; ?>" Method="POST" name="f" id="sendto">

<?PHP
echo <<<EOF
<script type="text/javascript" language="JavaScript">
{$init}

$(document).ready(function() {
	initAjaxVal(init,init2);
});

</script>
EOF;
?>

<?php
$cf = new CSSF();

echo "<input type=\"hidden\" name=\"enableClose\" value=\"{$_dvar['enableClose']}\">\n";
echo "<input type=\"hidden\" name=\"action\" id=\"action\" value=\"\">\n";
echo "<input type=\"hidden\" name=\"oldselection\" value=\"{$_dvar['oldselection']}\">\n";

if($_dvar['enableClose']){
	$cf->buttonJavaScript(11, 2, " Close Window", "window.opener.location.reload(true);self.close()");
}

$cf->label(10, 5, "Entity","javascript:selectTaxon()");
$cf->inputJqAutocomplete2(11, 5, 50, "taxon", "", $_dvar['taxonIndex'], "index_jq_autocomplete.php?field=taxon_commonname", 520, 2,0,"",true);

$cf->label(10, 8, "Common Name");
$cf->inputJqAutocomplete2(11, 8, 50, "common_name", $_dvar['common_name'], $_dvar['common_nameIndex'], "index_jq_autocomplete.php?field=cname_commonname", 520, 2,0,"",true);


$cf->label(10, 11, "Geography","javascript:selectGeoname()");
$cf->inputJqAutocomplete2(11, 11, 50, "geoname", "", $_dvar['geonameIndex'], "index_jq_autocomplete.php?field=cname_geoname", 520, 2,"",0,true);

/*
$cf->text(11, 11, "<input type=\"text\" style=\"width: 200px;\" value=\"\" id=\"ajax_geoname\" class=\"ac_input\"/>");
echo "<input type=\"hidden\" name=\"geonameIndex\" id=\"geonameIndex\"  value=\"0\">\n";
*/



$cf->label(10, 14, "Language");
$cf->inputJqAutocomplete2(11, 14, 50, "language", "", $_dvar['languageIndex'], "index_jq_autocomplete.php?field=cname_language", 520, 2,0,"",true);

$cf->label(10, 17, "Period");
$cf->inputJqAutocomplete2(11,17, 50, "period", $_dvar['period'], $_dvar['periodIndex'], "index_jq_autocomplete.php?field=cname_period", 520, 2,0,"",true);

$cf->label(10, 22, "Literature","javascript:selectLiterature()");
$cf->label(62, 22, "<input type=\"radio\" name=\"source\" value=\"literature\"{$source_sel['literature']}>");
$cf->inputJqAutocomplete2(11, 22, 48, "literature", "", $_dvar['literatureIndex'], "index_jq_autocomplete.php?field=cname_literature", 520, 2,0,"",true);

$cf->label(10, 25, "Service");
$cf->label(62, 25, "<input type=\"radio\" name=\"source\"  value=\"service\"{$source_sel['service']}>");
$cf->inputJqAutocomplete2(11, 25, 48, "service", "", $_dvar['serviceIndex'], "index_jq_autocomplete.php?field=cname_service", 520, 2,0,"",true);

$cf->label(10, 28, "Person");
$cf->label(62, 28, "<input type=\"radio\" name=\"source\"  value=\"person\"{$source_sel['person']}>");
$cf->inputJqAutocomplete2(11, 28, 48, "person", "", $_dvar['personIndex'], "index_jq_autocomplete.php?field=cname_person", 520, 2,0,"",true);

if(checkRight('commonnameInsert')){
	echo "<input style=\"display:none\" type=\"submit\" name=\"submitInsert\" value=\" Insert New\">";
}

if (($_SESSION['editControl'] & 0x200) != 0) {
	$cf->buttonSubmit(10, 31, "reload", " Reload ");
	$cf->buttonReset(16, 31, " Reset ");
	if($_dvar['update'] &&  checkRight('commonnameUpdate')){
		$cf->buttonSubmit(21, 31, "submitUpdate", " Update");
		$cf->buttonSubmit(27, 31, "submitDelete", " Delete");
	}
	if(checkRight('commonnameInsert')){
		$cf->buttonSubmit(32, 31, "submitInsert", " Insert New");
	}
}
$cf->buttonSubmit(39, 31, "submitSearch", " Search");

echo<<<EOF
<div style="position: absolute; left: 10em; top: 36em; width:672px;">
{$msg}
</div>

<div style="position: absolute; left: 10em; top: 38em; width:672px;">
{$search_result}
</div>
EOF;
?>

</form>
<div style="display:none">

<div id="dialog-information" title="Information">
	<p><span class="ui-icon ui-icon-info" style="float:left; margin:0 7px 20px 0;"></span><div id="dinformation" style="float:left"> These items will be permanently deleted and cannot be recovered. Are you sure?</div></p>
</div>
<div id="dialog-warning" title="Warning">
	<p><span class="ui-icon ui-icon-notice" style="float:left; margin:0 7px 20px 0;"></span><div id="dwarning" style="float:left">These items will be permanently deleted and cannot be recovered. Are you sure?</div></p>
</div>
<div id="dialog-error" title="Error">
	<p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span><div id="derror" style="float:left">These items will be permanently deleted and cannot be recovered. Are you sure?</div></p>
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
 * @param string $value text to search for
 * @param bool[optional] $noExternals only results for "external=0" (default no)
 * @return sql string 
 */
function getAppliesQuery($p_taxonIndex, $p_taxon, $p_refIndex, $p_refVal, $p_source, $p_common_nameIndex, $p_common_name, $p_languageIndex, $p_language, $p_geonameIndex, $p_geoname, $p_periodIndex, $p_period){
	global $_OPTIONS;

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
 ser.serviceID as 'serviceID'

FROM
 {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_applies_to a
 LEFT JOIN {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_entities ent ON ent.entity_id = a.entity_id
 LEFT JOIN {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_taxa tax ON tax.taxon_id = ent.entity_id
 
 LEFT JOIN {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_names nam ON nam.name_id = a.name_id
 LEFT JOIN {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_commons com ON com.common_id = nam.name_id
 
 LEFT JOIN {$_CONFIG['DATABASE']['NAME']['name']}.tbl_geonames_cache geo ON geo.geonameId = a.geonameId
 LEFT JOIN {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_languages lan ON  lan.language_id = a.language_id
 LEFT JOIN {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_periods per ON per.period_id= a.period_id

 LEFT JOIN {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_references ref ON ref.reference_id = a.reference_id

 LEFT JOIN {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_persons pers ON pers.person_id = ref.reference_id
 LEFT JOIN {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_literature lit ON lit.literature_id = ref.reference_id
 LEFT JOIN {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_webservices ser ON ser.webservice_id = ref.reference_id

WHERE
 1=1
";
	if(intval($p_refIndex)>0 && $p_refVal!=''){
		switch($p_source){
			default: case 'literature':$sql.="\n and a.reference_id = ref.reference_id and ref.reference_id = lit.literature_id and lit.citationID ='{$p_refIndex}'";break;
			case 'service':$sql.="\n and a.reference_id = ref.reference_id and ref.reference_id = ser.webservice_id and ser.serviceID ='{$p_refIndex}'";break;
			case 'person':$sql.="\n and a.reference_id = ref.reference_id and ref.reference_id = pers.person_id and pers.personID ='{$p_refIndex}'";break;
			case 'reference':$sql.="\n and a.reference_id = '{$p_refIndex}'";break;
	
		}
	}
	if(intval($p_taxonIndex)>0 && $p_taxon!='')$sql.="\n and a.entity_id = ent.entity_id and ent.entity_id = tax.taxon_id  and tax.taxonID='{$p_taxonIndex}'";
	if(intval($p_common_nameIndex)>0 && $p_common_name!='')$sql.="\n and a.name_id = nam.name_id and nam.name_id = com.common_id and com.common_id = '{$p_common_nameIndex}'";
	if(strlen($p_languageIndex)>0 && $p_language!='')$sql.="\n and a.language_id ='{$p_languageIndex}'";
	if(intval($p_geonameIndex)>0 && $p_geoname!='')$sql.="\n and a.geonameId = geo.geonameID and geo.geonameID = '{$p_geonameIndex}'";
	if(intval($p_periodIndex)>0 && $p_period!='')$sql.="\n and a.period_id = per.period_id and per.period_id = '{$p_periodIndex}'";
	$sql.="\n LIMIT 101";
	return $sql;
}

/**
 * checkRowExists: Check, if a dataset is existing
 * @param string $value text to search for
 * @param bool[optional] $noExternals only results for "external=0" (default no)
 * @return sql string 
 */
function checkRowExists($p_entityIndex,$p_common_nameIndex,$p_geonameIndex,$p_languageIndex,$p_periodIndex,$p_referenceIndex){
	global $_OPTIONS;
	
	$result=doDBQuery("
SELECT
 COUNT(*) as 'count'
FROM
 {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_applies_to
WHERE
      entity_id = '{$p_entityIndex}'
 and reference_id = '{$p_referenceIndex}'
 and name_id = '{$p_common_nameIndex}' 
 and language_id = '{$p_languageIndex}'
 and geonameId ='{$p_geonameIndex}'
 and period_id = '{$p_periodIndex}'
 
");

	$row = mysql_fetch_array($result);

	return ($row['count']==1);
}


function InsertUpdateCommonName(&$_dvar, $update=false){	
	global $_OPTIONS;
	$msg=array();
	if(!$update && !checkRight('commonnameInsert')){
		return array("You have no Rights for Insert",0);
	}
	
	if($update && !checkRight('commonnameUpdate')){
		return array("You have no Rights for Update",0);
	}
	
	if(intval($_dvar['taxonIndex'])==0)$msg['tax']="Please insert valid Taxon";
	if(strlen($_dvar['taxon'])==0)$msg['tax']="Please insert Taxon";
	if(strlen($_dvar['common_name'])<3)$msg['cname']="Please insert valid Common Name";

	if (count($msg)!=0){
		$msg=implode("<br>",$msg);
		return array($msg,0);
	}
	
	// Literature
	$_dvar2=array(
		'literatureIndex'	=> '',
		'literature'		=> '',
		'serviceIndex'		=> '',
		'service'			=> '',
		'personIndex'		=> '',
		'person'			=> '',
	);
	
	// reference
	$_dvar['referenceIndex']=0;
	switch($_dvar['source']){
		default:
		case 'literature':
			$_dvar2['literatureIndex']=$_dvar['literatureIndex'];
			$_dvar2['literature']=$_dvar['literature'];
			
			$result = doDBQuery("SELECT literature_id FROM {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_literature WHERE citationID='{$_dvar['literatureIndex']}'");
			if($row=mysql_fetch_array($result)){
				$_dvar['referenceIndex']=$row['literature_id'];
			}
			if($_dvar['referenceIndex']==0){
				$result = doDBQuery("INSERT INTO {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_references (reference_id) VALUES (NULL)");
				$_dvar['referenceIndex']=mysql_insert_id();
				$result = doDBQuery("INSERT INTO {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_literature (literature_id,citationID) VALUES ('{$_dvar['referenceIndex']}','{$_dvar['literatureIndex']}')");
			}
			break;
		
		case 'person':
			$_dvar2['personIndex']=$_dvar['personIndex'];
			$_dvar2['person']=$_dvar['person'];
			
			$result = doDBQuery("SELECT person_id FROM {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_persons WHERE personID='{$_dvar['personIndex']}'");
			if($row=mysql_fetch_array($result)){
				$_dvar['referenceIndex']=$row['person_id'];
			}
			if($_dvar['referenceIndex']==0){
				$result = doDBQuery("INSERT INTO {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_references (reference_id) VALUES (NULL)");
				$_dvar['referenceIndex']=mysql_insert_id();
				$result = doDBQuery("INSERT INTO {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_persons (person_id,personId) VALUES ('{$_dvar['referenceIndex']}','{$_dvar['personIndex']}')");
			}
			break;
		
		case 'service':
			$_dvar2['serviceIndex']=$_dvar['serviceIndex'];
			$_dvar2['service']=$_dvar['service'];
			
			$result = doDBQuery("SELECT webservice_id FROM {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_webservices WHERE serviceID='{$_dvar['serviceIndex']}'");
			if($row=mysql_fetch_array($result)){
				$_dvar['referenceIndex']=$row['webservice_id'];
			}
			if($_dvar['referenceIndex']==0){
				$result = doDBQuery("INSERT INTO {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_references (reference_id) VALUES (NULL)");
				$_dvar['referenceIndex']=mysql_insert_id();
				$result = doDBQuery("INSERT INTO {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_webservices (webservice_id,serviceId) VALUES ('{$_dvar['referenceIndex']}','{$_dvar['serviceIndex']}')");
			}
			break;
	}
	
	$_dvar=array_merge($_dvar,$_dvar2);

	//Cache geoname
	$result = doDBQuery("INSERT INTO {$_CONFIG['DATABASE']['NAME']['name']}.tbl_geonames_cache (geonameId, name) VALUES ('{$_dvar['geonameIndex']}','{$_dvar['geoname']}') ON DUPLICATE KEY UPDATE  geonameId=VALUES(geonameId)");
	
	// Language
	// is already in the database by autocompleter.
		
	//period
	$sql="INSERT INTO {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_periods (period) VALUES ('{$_dvar['period']}') ON DUPLICATE KEY UPDATE period_id=LAST_INSERT_ID(period_id)";
	$result = doDBQuery($sql);
	$_dvar['periodIndex']=mysql_insert_id();
	
	//NAMES
	//commonname
	$_dvar['nameIndex']=0;
	$result = doDBQuery("SELECT common_id FROM {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_commons WHERE common_name='{$_dvar['common_name']}'");
	if($row=mysql_fetch_array($result)){
		$_dvar['nameIndex']=$row['common_id'];
	}
	
	if($_dvar['nameIndex']==0){
		$result = doDBQuery("INSERT INTO {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_names (name_id) VALUES (NULL)");
		$_dvar['nameIndex']=mysql_insert_id();
		$result = doDBQuery("INSERT INTO {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_commons (common_id, common_name) VALUES ('{$_dvar['nameIndex']}','{$_dvar['common_name']}')");
	}
	$_dvar['common_nameIndex']=$_dvar['nameIndex'];
	
	// ENTITY
	// taxon
	$_dvar['entityIndex']=0;
	$result = doDBQuery("SELECT taxon_id FROM {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_taxa WHERE taxonID='{$_dvar['taxonIndex']}'");
	if($row=mysql_fetch_array($result)){
		$_dvar['entityIndex']=$row['taxon_id'];
	}
	if($_dvar['entityIndex']==0){
		$result = doDBQuery("INSERT INTO {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_entities (entity_id) VALUES (NULL)");
		$_dvar['entityIndex']=mysql_insert_id();
		$result = doDBQuery("INSERT INTO {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_taxa (taxon_id, taxonID) VALUES ('{$_dvar['entityIndex']}','{$_dvar['taxonIndex']}')");
	}
	
	// Insert Array
	$var=array(
		'entity_id'=> makeInt( $_dvar['entityIndex'] ),
		'name_id'=> makeInt( $_dvar['nameIndex'] ),
		'language_id'=> makeInt( $_dvar['languageIndex'] ),
		'geonameID'=> makeInt( $_dvar['geonameIndex'] ),
		'period_id'=> makeInt( $_dvar['periodIndex'] ),
		'reference_id'=> makeInt( $_dvar['referenceIndex'] )
	);
	
	// LINK IT
	$sql = "
entity_id =  {$var['entity_id']},
name_id =  {$var['name_id']},
language_id =  {$var['language_id']},
geonameId =  {$var['geonameID']},
period_id =  {$var['period_id']},
reference_id =  {$var['reference_id']}";
	
	// insert new dataset
	if(!$update){
		$sql="
INSERT INTO {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_applies_to SET
 {$sql}
";
		$result = @doDBQuery($sql);
		if($result){
			// Log it
			logCommonNamesAppliesTo($var,0);
			
			return array(0,"Successfully inserted");
		}
	// If Update: update old dataset
	}else{
	
		//$_dvar['entityIndex']:$_dvar['nameIndex']:$_dvar['geonameIndex']:$_dvar['languageIndex']:$_dvar['periodIndex']:$_dvar['referenceIndex']
		$ids=explode(':',$_dvar['oldselection']);
		$_dvar['update']=checkRowExists($ids[0],$ids[1],$ids[2],$ids[3],$ids[4],$ids[5]);
		if(!$_dvar['update']){
			return array("No Set for update choosen",0);
		}
		
		$sql="
UPDATE {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_applies_to SET
 {$sql}
WHERE
     entity_id = '{$ids[0]}'
 and name_id = '{$ids[1]}'
 and geonameId ='{$ids[2]}' 
 and language_id = '{$ids[3]}'
 and period_id = '{$ids[4]}'
 and reference_id = '{$ids[5]}'
 ";		
			
		$result = doDBQuery($sql);
		if($result){
			// Log it
			logCommonNamesAppliesTo($var,1);
			
			return array(0,"Successfully updated");
		}
	
	}
	
	// If no insertion because of already there: Print Error Message
	if(mysql_errno()=='1062'){
		return array("already in database",0);
	}
		
	return array("Error ".mysql_errno() . ": " . mysql_error() . "",0);
}
function deleteCommonName($_dvar){
	global $_OPTIONS;
	
	if(!checkRight('admin')){
		return array("You have to be admin for deletation",0);
	}

	//$_dvar['entityIndex']:$_dvar['nameIndex']:$_dvar['geonameIndex']:$_dvar['languageIndex']:$_dvar['periodIndex']:$_dvar['referenceIndex']
	$ids=explode(':',$_dvar['oldselection']);
	$_dvar['update']=checkRowExists($ids[0],$ids[1],$ids[2],$ids[3],$ids[4],$ids[5]);
	
	
	if(!$_dvar['update']){
		return array("No correct Set for deletion choosen",0);
	}

	$sql="
DELETE FROM {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_applies_to
WHERE
     entity_id = '{$ids[0]}'
 and name_id = '{$ids[1]}'
 and geonameId ='{$ids[2]}' 
 and language_id = '{$ids[3]}'
 and period_id = '{$ids[4]}'
 and reference_id = '{$ids[5]}'
 ";	
	$result = doDBQuery($sql);
	
	if($result){
		return array(0, "Successfull deleted<br>The deleted Set is inserted in the fields above and can be inserted new.");
	}else{
		if(mysql_errno()=='1062'){
			return array("already in database",0);
		}
	}
	
	return array("Error ".mysql_errno() . ": " . mysql_error() . "",0);
}

function doSearch($_dvar){
	global $_OPTIONS;
	
	// Mark collums that was searched...
	$class=array();
	$a='ac"';
	$b='';
	$class=array(
		'ent'=>($_dvar['taxon']!='')?$a:$b,
		'geo'=>($_dvar['geoname']!='')?$a:$b,
		'lang'=>($_dvar['language']!='')?$a:$b,
		'com'=>($_dvar['common_name']!='')?$a:$b,
		'per'=>($_dvar['period']!='')?$a:$b,
		'ref'=>($_dvar['literature']!=''||$_dvar['person']!=''||$_dvar['service']!='')?$a:$b,
	);
	if($_dvar['person']!=''){
		$refType='person';
		$refVar=$_dvar['person'];
		$refIndex=$_dvar['personIndex'];
	}else if($_dvar['service']!=''){
		$refType='service';
		$refVar=$_dvar['service'];
		$refIndex=$_dvar['serviceIndex'];
	}else{
		$refType='literature';
		$refVar=$_dvar['literature'];
		$refIndex=$_dvar['literatureIndex'];
	}
	
	
	// get search query
	$sql=getAppliesQuery($_dvar['taxonIndex'], $_dvar['taxon'], $refIndex, $refVar, $refType, $_dvar['common_nameIndex'], $_dvar['common_name'], $_dvar['languageIndex'], $_dvar['language'], $_dvar['geonameIndex'], $_dvar['geoname'], $_dvar['periodIndex'], $_dvar['period']);
	$result = doDBQuery($sql);
	
	$i=0;$search_result='';
	while($row = mysql_fetch_array($result)){
		$taxon			=	getTaxon($row['taxonID']);
		
		$literature='';
		if($row['source']=='service'){
			if($row['serviceID']!=''){
				$literature	=getService($row['serviceID']);
			}
		}else if($row['source']=='person'){
			if($row['personID']!=''){		
				$literature	=getPerson($row['personID']);
			}
		}else{
			if($row['literatureID']!=''){
				$literature	=getLiterature($row['literatureID']);
			}
		}
		$lan=($row['iso639-6']!="")?"{$row['iso639-6']} ({$row['language']})":"";
		$geo=($row['geoname']!='')?"{$row['geoname']} &lt;{$row['geoname_id']}&gt;":"";
		
		$trclass=($i%2)?'odd':'even';
		$eo=($i%2)?'o':'e';
/*
//dbeug
<td class="{$eo}{$class['ent']}">{$taxon}</td><td class="{$eo}{$class['com']}">{$row['common_name']}</td><td class="{$eo}{$class['geo']}">{$row['geoname']} &lt;{$row['geoname_id']}&gt;</td><td class="{$eo}{$class['lang']}"> {$row['iso639-6']} ({$row['language']})</td><td class="{$eo}{$class['per']}">{$row['period']}</td><td class="{$eo}{$class['ref']}">{$literature}</td>
*/
		
		
		$search_result.=<<<EOF

<tr onclick="selectA('{$row['taxonID']}','{$row['name_id']}','{$row['geoname_id']}','{$row['language_id']}','{$row['period_id']}','{$row['reference_id']}')" >

<td class="{$eo}{$class['ent']}">{$taxon}</td><td class="{$eo}{$class['com']}">{$row['common_name']}</td><td class="{$eo}{$class['geo']}">{$geo}</td><td class="{$eo}{$class['lang']}">{$lan}</td><td class="{$eo}{$class['per']}">{$row['period']}</td><td class="{$eo}{$class['ref']}">{$literature}</td>
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
<style>
.oac{ background-color:#DAEEDD;}
.eac{ background-color:#e7fae6;}
.o{ background-color:#f0f0f6;}
.e{ background-color:#fff;}
.lac{

}
.l{
  background-color:#ffff99 !important;
}
</style>
<table id="sorttable" cellspacing="0" cellpadding="0" class="tablesorter">
<colgroup><col width="16%"><col width="16%"><col width="16%"><col width="16%"><col width="16%"><col width="16%"></colgroup>
<thead>
<tr>
 <th class="l{$class['ent']}"><span>Entity </span></th><th class="l{$class['com']}"><span>Common Name</span></th><th class="l{$class['geo']}"><span>Geography</span></th><th class="l{$class['lang']}"><span>Language</span></th><th class="l{$class['per']}"><span>Period</span></th><th class="l{$class['ref']}"><span>Reference</span></th>
</tr>
</thead>
<tbody>

{$search_result}

</tbody>
</table>
<script>
$(function(){
	$("#sorttable tbody tr").hover(
		function () {
			$(this).find('td').css('background-color', '#ffff99');
		}, 
		function () {
			$(this).find('td').css('background-color', '');
		}
	);
  
	$("#sorttable").tablesorter();
});
		
function selectA(taxonID,name_id,geoname_id,iso639_6,period_id,reference_id){
	document.location.href='editCommonName.php?show=1&taxonID='+taxonID+'&name_id='+name_id+'&geoname_id='+geoname_id+'&iso639_6='+iso639_6+'&period_id='+period_id+'&reference_id='+reference_id;
}
</script>
EOF;
	return $search_result;
}

function cleanPair($name,$def=0){
	global $_dvar;
	if(intval($_dvar[$name.'Index'])==0 || strlen($_dvar[$name])==0){
		$_dvar[$name.'Index']=$def;
		$_dvar[$name]='';
	}
}

function doDBQuery($sql,$debug=false){
	if($debug){
		echo $sql;
	}
	return db_query($sql);
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

function getLanguage($languageIndex){
	global $_OPTIONS;
	
	$sql = "
SELECT
 l.`iso639-6`,
 l.`parent_iso639-6`,
 l.name,
 p.name as 'pname'
  
FROM
 {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_languages l
 LEFT JOIN {$_CONFIG['DATABASE']['NAME']['name']}.tbl_name_languages p ON p.`iso639-6`=l.`parent_iso639-6`
WHERE
 l.language_id='{$languageIndex}'
 ";
	$result = doDBQuery($sql);
	$row = mysql_fetch_array($result);
	if($row['iso639-6']=='')return '';
	return "{$row['iso639-6']}, {$row['name']} (-> {$row['parent_iso639-6']}, {$row['pname']})";
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