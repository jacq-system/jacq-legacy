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
  <link rel="stylesheet" type="text/css" href="inc/jQuery/css/ui-lightness/jquery-ui.custom.css">
  <link rel="stylesheet" href="inc/jQuery/css/blue/style_nhm.css" type="text/css" id="" media="print, projection, screen" />
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
  </style>
  <script src="inc/jQuery/jquery.min.js" type="text/javascript"></script>
  <script src="inc/jQuery/jquery-ui.custom.min.js" type="text/javascript"></script>
  <script type="text/javascript" src="inc/jQuery/jquery.tablesorter_nhm.js"></script>
		
  <script type="text/javascript" language="JavaScript">

  
var taxwin;
var citwin;
var geowin;
var selectFirstACItem=false;

function selectTaxon() {
	taxwin = window.open("listTaxCommonName.php", "selectTaxon", "width=600, height=500, top=50, right=50, scrollbars=yes, resizable=yes");
	taxwin.focus();
}
function UpdateTaxon(taxonID) {
	selectFirstACItem=true;
	$('#ajax_taxon').autocomplete( "search",'<'+taxonID+'>');
}

function selectCitation() {
	citationID=document.f.citationIndex;
	citwin = window.open("listLitCommonName.php", "selectCitation", "width=600, height=500, top=50, right=50, scrollbars=yes, resizable=yes");
	citwin.focus();
}
function UpdateCitation(citationID) {
	selectFirstACItem=true;
	$('#ajax_citation').autocomplete( "search",'<'+citationID+'>');
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
	selectFirstACItem=true;
	$('#ajax_geoname').autocomplete( "search",'<'+geonameID+'>');
}

$(document).ready(function() {
	
	$("#ajax_geoname").bind( "autocompletesearch", function(event, ui) {
		$('#ajax_geonameIndex').val('');
	}).bind( 'autocompleteopen', function(event, ui) {
		if(selectFirstACItem==true){
			c=$(this).data('autocomplete').menu;
			c._trigger('selected',event,{item:$(c.element[0].firstChild)});
		}
	}).bind( "autocompleteselect", function(event, ui) {
		selectFirstACItem=false;
	});
	
	
	$("#ajax_citation").bind( "autocompletesearch", function(event, ui) {
		$('#ajax_geonameIndex').val('');
	}).bind( 'autocompleteopen', function(event, ui) {
		if(selectFirstACItem==true){
			c=$(this).data('autocomplete').menu;
			c._trigger('selected',event,{item:$(c.element[0].firstChild)});
		}
	}).bind( "autocompleteselect", function(event, ui) {
		selectFirstACItem=false;
	});
	
	$("#ajax_taxon").bind( "autocompletesearch", function(event, ui) {
		$('#ajax_geonameIndex').val('');
	}).bind( 'autocompleteopen', function(event, ui) {
		if(selectFirstACItem==true){
			c=$(this).data('autocomplete').menu;
			c._trigger('selected',event,{item:$(c.element[0].firstChild)});
		}
	}).bind( "autocompleteselect", function(event, ui) {
		selectFirstACItem=false;
	});
	
	// Change the default select function of csf Autocmpleter for ISO-Code support
	$("#ajax_language").unbind("autocompleteselect").bind("autocompleteselect", function(event, ui) {
		a=ui.item.id.split(',');
		$('#languageIso').val(a[0]);
		$('#languageIndex').val(a[1]); 
	});
});


  </script>
</head>

<body>

<?php
$common_name_dB='names.';
$search_result='';
$p_searchorder='';
$doSearch=false;
$msg=array();
		

$p_entityIndex		= '';
$p_taxonIndex		= '';
$p_taxon			= '';

$p_referenceIndex	= '';
$p_citationIndex	= '';
$p_citation			= '';	

$p_geonameIndex		= '';
$p_geoname			= '';

$p_languageIndex	= '';
$p_languageIso		= '';
$p_language			= '';

$p_periodIndex		= '';
$p_period			= '';

$p_nameIndex		= '';
$p_common_nameIndex	= '';
$p_common_name		= '';

$p_oldselection		= '';

$p_timestamp		= '';
$p_userID			= '';

if(isset($_POST['submitUpdate']) || isset($_POST['submitSearch'])){
	
	
	$p_taxonIndex		=	$_POST['taxonIndex'];
	$p_taxon			=	$_POST['taxon'];
	
	$p_referenceIndex	=	'';
	$p_citationIndex	=	$_POST['citationIndex'];
	$p_citation			=	$_POST['citation'];
	
	$p_geonameIndex		=	$_POST['geonameIndex'];
	$p_geoname			=	$_POST['geoname'];
	
	$p_languageIndex	=	$_POST['languageIndex'];
	$p_languageIso		=	$_POST['languageIso'];
	$p_language			=	$_POST['language'];
	
	$p_periodIndex		=	$_POST['periodIndex'];
	$p_period			=	$_POST['period'];
	
	$p_nameIndex		=	$_POST['common_nameIndex'];
	$p_common_nameIndex	=	$_POST['common_nameIndex'];
	$p_common_name		=	$_POST['common_name'];
	
	$p_oldselection		=	$_POST['oldselection'];
	
	$p_userID			=	$_POST['userID'];
	$p_timestamp		= 	$_POST['timestamp'];

	
	// Delete action
	if (isset($_POST['submitUpdate']) && $_POST['submitUpdate']==' Delete' && (($_SESSION['editControl'] & 0x200) != 0)) {
	
		//$p_entityIndex:$p_nameIndex:$p_geonameIndex:$p_languageIndex:$p_periodIndex:$p_referenceIndex
		$ids=explode(':',$p_oldselection);
		$p_update=checkRowExists($ids[0],$ids[1],$ids[2],$ids[3],$ids[4],$ids[5]);
		if($p_update){
			$sql="
DELETE FROM {$common_name_dB}tbl_name_applies_to
WHERE
 entity_id = '{$ids[0]}'
 and name_id = '{$ids[1]}'
 and geonameId ='{$ids[2]}' 
 and language_id = '{$ids[3]}'
 and period_id = '{$ids[4]}'
 and reference_id = '{$ids[5]}'
 ";	
			$result = db_query($sql);
			if($result){
				$msg['result']="Successfull deleted<br>The deleted Set is inserted in the fields above and can be inserted new.";
			}else{
				if(mysql_errno()=='1062'){
					$msg['result']="already in database";
				}else{
					$msg['result']="Error ".mysql_errno() . ": " . mysql_error() . "";
				}
			}
		}else{
			$msg['result']="No Set for deletion choosen";
		}
	// Insert/Update
	}else if (isset($_POST['submitUpdate']) && $_POST['submitUpdate'] && (($_SESSION['editControl'] & 0x200) != 0)) {
		if(intval($p_taxonIndex)==0)$msg['tax']="Please insert correct Taxon";
		if(strlen($p_taxon)==0)$msg['tax']="Please insert Taxon";
		if(intval($p_citationIndex)==0)$msg['cit']="Please insert correct Citation";
		if(strlen($p_citation)==0)$msg['cit']="Please insert Citation";
		
		if(intval($p_geonameIndex)==0)$msg['geo']="Please insert correct Geography";
		if(strlen($p_geoname)==0)$msg['geo']="Please insert Geography";
		
		if(strlen($p_languageIso)<2)$msg['lang']="Please insert correct Language";
		if(strlen($p_language)==0)$msg['lang']="Please insert Language";
		
		if(strlen($p_period)<3)$msg['per']="Please insert correct Period";
		if(strlen($p_common_name)<3)$msg['com']="Please insert correct Common Name";
		
		if (count($msg)==0){
            
			//Cache geoname
			$sql="INSERT INTO {$common_name_dB}tbl_geonames_cache(geonameId, name) VALUES ('{$p_geonameIndex}','{$p_geoname}') ON DUPLICATE KEY UPDATE  name=VALUES(name)";
			$result = db_query($sql);
			
			// Language
			$sql="INSERT INTO {$common_name_dB}tbl_name_languages (iso639_6,namecache) VALUES ('{$p_languageIso}','".mysql_real_escape_string($p_language)."') ON DUPLICATE KEY UPDATE language_id=LAST_INSERT_ID(language_id)";
			$result = db_query($sql);
			$p_languageIndex=mysql_insert_id();
			
			//period: todo: think about update; delete old one??
			$sql="INSERT INTO {$common_name_dB}tbl_name_periods (period) VALUES ('{$p_period}') ON DUPLICATE KEY UPDATE period_id=LAST_INSERT_ID(period_id)";
			$result = db_query($sql);
			$p_periodIndex=mysql_insert_id();
			
			//NAMES
			//commonname
			$sql="SELECT common_id FROM {$common_name_dB}tbl_name_commons WHERE common_name='{$p_common_name}'";
			$result = db_query($sql);
			$p_nameIndex=0;
			if($result){
				$row=mysql_fetch_array($result);
				if(isset($row['common_id'])){
					$p_nameIndex=$row['common_id'];
				}
			}
			
			if($p_nameIndex==0){
				$sql="INSERT INTO {$common_name_dB}tbl_name_names (name_id) VALUES (NULL)";
				$result = db_query($sql);
				$p_nameIndex=mysql_insert_id();
				
				$sql="INSERT INTO {$common_name_dB}tbl_name_commons (common_id, common_name) VALUES ('{$p_nameIndex}','{$p_common_name}')";
				$result = db_query($sql);
			}
			$p_common_nameIndex=$p_nameIndex;
			
			// ENTITY
			// taxon
			$sql="SELECT taxon_id FROM {$common_name_dB}tbl_name_taxon WHERE taxonID='{$p_taxonIndex}'";
			$result = db_query($sql);
			$p_entityIndex=0;
			if($result){
				$row=mysql_fetch_array($result);
				if(isset($row['taxon_id'])){
					$p_entityIndex=$row['taxon_id'];
				}
			}
			if($p_entityIndex==0){
				$sql="INSERT INTO {$common_name_dB}tbl_name_entities (entity_id) VALUES (NULL)";
				$result = db_query($sql);
				$p_entityIndex=mysql_insert_id();
				// todo: autoincrement entity_id
				$sql="INSERT INTO {$common_name_dB}tbl_name_taxon (taxon_id, taxonID) VALUES ('{$p_entityIndex}','{$p_taxonIndex}')";
				$result = db_query($sql);
			}
			
			// Literature
			// taxon
			$sql="SELECT literature_id FROM {$common_name_dB}tbl_name_literature WHERE CitationID='{$p_citationIndex}'";
			$result = db_query($sql);
			$p_referenceIndex=0;
			if($result){
				$row=mysql_fetch_array($result);
				if(isset($row['literature_id'])){
					$p_referenceIndex=$row['literature_id'];
				}
			}
			if($p_referenceIndex==0){
				$sql="INSERT INTO {$common_name_dB}tbl_name_references (reference_id) VALUES (NULL)";
				$result = db_query($sql);
				$p_referenceIndex=mysql_insert_id();
				// todo: autoincrement reference_id
				$sql="INSERT INTO {$common_name_dB}tbl_name_literature (literature_id,citationId) VALUES ('{$p_referenceIndex}','{$p_citationIndex}')";
				$result = db_query($sql);
			}
			
			// LINK IT
			$sql = "
 entity_id =  " . makeInt( $p_entityIndex ) . ",
 name_id =  " . makeInt( $p_nameIndex ) . ",
 language_id =  " . makeInt( $p_languageIndex ) . ",
 geonameId =  " . makeInt( $p_geonameIndex ) . ",
 period_id =  " . makeInt( $p_periodIndex ) . ",
 reference_id =  " . makeInt( $p_referenceIndex ) . "";
			
			// If Update: update old dataset
			if($_POST['submitUpdate']==' Update'){
				//$p_entityIndex:$p_nameIndex:$p_geonameIndex:$p_languageIndex:$p_periodIndex:$p_referenceIndex
				$ids=explode(':',$p_oldselection);
				$p_update=checkRowExists($ids[0],$ids[1],$ids[2],$ids[3],$ids[4],$ids[5]);
				if($p_update){
					$sql="
UPDATE {$common_name_dB}tbl_name_applies_to SET
 {$sql}
WHERE
 entity_id = '{$ids[0]}'
 and name_id = '{$ids[1]}'
 and geonameId ='{$ids[2]}' 
 and language_id = '{$ids[3]}'
 and period_id = '{$ids[4]}'
 and reference_id = '{$ids[5]}'
 ";			
					
					$result = db_query($sql);
					if($result){
						$msg['result']="Successfully updated";
					}
				}else{
					$msg['result']="No Set for update choosen";
				}
			
			// else insert new dataset
			}else if($_POST['submitUpdate']==' Insert New'){
				$sql="
INSERT INTO {$common_name_dB}tbl_name_applies_to SET
 {$sql}
";
				$result = db_query($sql);
				if($result){
					$msg['result']="Successfully inserted";
				}
			}
			$doSearch=true;
			// If no update/insertion: Print Error Message
			if(!$result){
				if(mysql_errno()=='1062'){
					$msg['result']="already in database";
				}else{
					$msg['result']="Error ".mysql_errno() . ": " . mysql_error() . "";
				}
			}
        } else {
			$msg['result']="Some Fields are not correct";
		}
	
	// Do Search
	}else if(isset($_POST['submitSearch'])){
		$doSearch=true;	
	}

// Show a Common Name Set with given GET vars...
}else if(isset($_GET['show'])) {

	$p_taxonIndex=isset($_GET['taxonID'])?$_GET['taxonID']:'';
	$p_nameIndex=isset($_GET['name_id'])?$_GET['name_id']:'';
	$p_geonameIndex=isset($_GET['geoname_id'])?$_GET['geoname_id']:'';
	$p_languageIso=isset($_GET['iso639_6'])?$_GET['iso639_6']:'';
	$p_periodIndex=isset($_GET['period_id'])?$_GET['period_id']:'';
	$p_citationIndex=isset($_GET['citationID'])?$_GET['citationID']:'';
	
	$sql=getAppliesQuery($p_taxonIndex, '1', $p_citationIndex, '1', $p_common_nameIndex, '1', $p_languageIso, '1', $p_geonameIndex, '1', $p_periodIndex, '1');
	
	$result = db_query($sql);
	$row = mysql_fetch_array($result);

	$p_entityIndex		=	$row['entity_id'];
	$p_taxonIndex		=	isset($row['taxonID'])?$row['taxonID']:$p_taxonIndex;
	$p_taxon			=	(intval($p_taxonIndex)!=0)?getTaxon($p_taxonIndex):'';
	
	$p_referenceIndex	=	$row['reference_id'];
	$p_citationIndex	=	isset($row['citationID'])?$row['citationID']:$p_citationIndex;
	$p_citation			=	(intval($p_citationIndex)!=0)?getCitation($p_citationIndex):'';
	
	$p_geonameIndex		=	isset($row['geoname_id'])?$row['geoname_id']:$p_geonameIndex;
	$p_geoname			=	$row['geoname'];
	
	$p_languageIndex	=	$row['language_id'];
	$p_languageIso		=	$row['iso639_6'];
	$p_language			=	$row['language'];
	
	$p_periodIndex		=	isset($row['period_id'])?$row['period_id']:$p_periodIndex;
	$p_period			=	$row['period'];
	
	$p_nameIndex	=	isset($row['name_id'])?$row['name_id']:$p_common_nameIndex;
	$p_common_nameIndex	=	isset($row['name_id'])?$row['name_id']:$p_common_nameIndex;
	$p_common_name		=	$row['common_name'];
	
	$p_oldselection		=	'';
	
	$p_timestamp		=	$row['timestamp'];
	$p_userID			=	$row['userID'];
	
	
}

if(isset($_GET['search'])){
	$doSearch=true;
}

if($doSearch){
	// Mark collums that was searched...
	$class=array();
	$a='';
	$b=' class="hh"';
	$class=array(
		'ent1'=>($p_taxonIndex!='')?$a:$b,
		'geo1'=>($p_geonameIndex!='')?$a:$b,
		'lang1'=>($p_languageIso!='')?$a:$b,
		'com1'=>($p_common_nameIndex!='')?$a:$b,
		'per1'=>($p_periodIndex!='')?$a:$b,
		'ref1'=>($p_citationIndex!='')?$a:$b,
	);
		
	// get search query
	$sql=getAppliesQuery($p_taxonIndex, $p_taxon, $p_citationIndex, $p_citation, $p_common_nameIndex, $p_common_name, $p_languageIso, $p_language, $p_geonameIndex, $p_geoname, $p_periodIndex, $p_period);
	
	$result = db_query($sql);
	
	$i=0;$search_result='';
	while($row = mysql_fetch_array($result)){
		$taxon			=	getTaxon($row['taxonID']);
		$citation			=	getCitation($row['citationID']);
		
		$trclass=($i%2)?'odd':'even';
		$search_result.=<<<EOF

<tr onclick="selectA('{$row['taxonID']}','{$row['name_id']}','{$row['geoname_id']}','{$row['iso639_6']}','{$row['period_id']}','{$row['citationID']}')" class="{$trclass}">
<td{$class['ent1']}>{$taxon} {$row['taxonID']}</td><td{$class['com1']}>{$row['common_name']} {$row['name_id']}</td><td{$class['geo1']}>{$row['geoname']} {$row['geoname_id']}</td>
<td{$class['lang1']}>{$row['iso639_6']} {$row['language']} {$row['language_id']}</td><td{$class['per1']}>{$row['period']} {$row['period_id']}</td><td{$class['ref1']}>{$citation} {$row['citationID']}</td>
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
<table id="sorttable" cellspacing="0" cellpadding="0" class="tablesorter">
<colgroup><col width="16%"><col width="16%"><col width="16%"><col width="16%"><col width="16%"><col width="16%"></colgroup>
<thead>
<tr>
<th><span>Entity </span></th>
<th><span>Common Name</span></th>
<th><span>Geography</span></th>
<th><span>Languager</span></th>
<th><span>Period</span></th>
<th><span>Reference</span></th>
</tr>
</thead>
<tbody>

{$search_result}

</tbody>
</table>
<script>
$(function(){
	$("#sorttable tbody tr").hover(function(){
		$(this).addClass("thover");
	},
	function(){
		$(this).removeClass("thover");
	})

	$("#sorttable").tablesorter();
});
		
function selectA(taxonID,name_id,geoname_id,iso639_6,period_id,citationID){
	document.location.href='editCommonName.php?show=1&taxonID='+taxonID+'&name_id='+name_id+'&geoname_id='+geoname_id+'&iso639_6='+iso639_6+'&period_id='+period_id+'&citationID='+citationID;
}
</script>
EOF;



}

echo "$p_entityIndex,$p_nameIndex,$p_geonameIndex,$p_languageIndex,$p_periodIndex,$p_referenceIndex";
// Check if selected Row is existing
$p_update=checkRowExists($p_entityIndex,$p_nameIndex,$p_geonameIndex,$p_languageIndex,$p_periodIndex,$p_referenceIndex);
$p_enableClose=((isset($_POST['enableClose'])&&$_POST['enableClose']==1)||(isset($_GET['enableClose'])&&$_GET['enableClose']==1))?1:0;

if($p_update){
	$p_oldselection="$p_entityIndex:$p_nameIndex:$p_geonameIndex:$p_languageIndex:$p_periodIndex:$p_referenceIndex";
}

$msg=implode('<br>',$msg);
	
?>

<form Action="<?php echo $_SERVER['PHP_SELF']; ?>" Method="POST" name="f">

<?php
 $cf = new CSSF();
 
 
 echo "<input type=\"hidden\" name=\"timestamp\" value=\"$p_timestamp\">\n";
 echo "<input type=\"hidden\" name=\"userID\" value=\"$p_userID\">\n";

 echo "<input type=\"hidden\" name=\"enableClose\" value=\"$p_enableClose\">\n";

 echo "<input type=\"hidden\" name=\"languageIso\" id=\"languageIso\" value=\"$p_languageIso\">\n";

 echo "<input type=\"hidden\" name=\"oldselection\" value=\"$p_oldselection\">\n";
 //echo "<input type=\"hidden\" name=\"searchorder\" value=\"$p_searchorder\">\n";
  
  if($p_enableClose){
		$cf->buttonJavaScript(11, 2, " Close Window", "window.opener.location.reload(true);self.close()");
  }
 
 $cf->label(10, 5, "Entity","javascript:selectTaxon()");
 $cf->inputJqAutocomplete(11, 5, 50, "taxon", $p_taxon, $p_taxonIndex, "index_jq_autocomplete.php?field=taxon_commonname", 520, 2);
 
 $cf->label(10, 8, "Common Name");
 $cf->inputJqAutocomplete(11, 8, 50, "common_name", $p_common_name, $p_common_nameIndex, "index_jq_autocomplete.php?field=cname_commonname", 520, 2);
 
 
 $cf->label(10, 11, "Geography","javascript:selectGeoname()");
 $cf->inputJqAutocomplete(11, 11, 50, "geoname", $p_geoname, $p_geonameIndex, "index_jq_autocomplete.php?field=cname_geoname", 520, 2);

 $cf->label(10, 14, "Language");
 $cf->inputJqAutocomplete(11, 14, 50, "language", $p_language, $p_languageIndex, "index_jq_autocomplete.php?field=cname_language", 520, 2);

 $cf->label(10, 17, "Period");
 $cf->inputJqAutocompleteTextarea(11,17, 50, 6, "period", $p_period, $p_periodIndex, "index_jq_autocomplete.php?field=cname_period", 520, 2);

 $cf->label(10, 25, "Reference","javascript:selectCitation()");
 $cf->inputJqAutocomplete(11, 25, 50, "citation", $p_citation, $p_citationIndex, "index_jq_autocomplete.php?field=cname_citation", 520, 2);

if (($_SESSION['editControl'] & 0x200) != 0) {
	$cf->buttonSubmit(10, 28, "reload", " Reload ");
	$cf->buttonReset(16, 28, " Reset ");
	if($p_update){
		$cf->buttonSubmit(21, 28, "submitUpdate", " Update");
		$cf->buttonSubmit(27, 28, "submitUpdate", " Delete");
	}
	$cf->buttonSubmit(32, 28, "submitUpdate", " Insert New");

}
  $cf->buttonSubmit(39, 28, "submitSearch", " Search");

echo<<<EOF
<div style="position: absolute; left: 10em; top: 30em; width:672px;">
{$msg}
</div>

<div style="position: absolute; left: 10em; top: 32em; width:672px;">

{$search_result}
</div>
EOF;
?>

</form>
</body>
</html>

<?

/**
 * getAppliesQuery: 
 * @param string $value text to search for
 * @param bool[optional] $noExternals only results for "external=0" (default no)
 * @return sql string 
 */
function getAppliesQuery($p_taxonIndex, $p_taxon, $p_citationIndex, $p_citation, $p_common_nameIndex, $p_common_name, $p_languageIso, $p_language, $p_geonameIndex, $p_geoname, $p_periodIndex, $p_period){
	global $common_name_dB;
	
	$sql="
SELECT
 a.entity_id as 'entity_id',
 tax.taxonID as 'taxonID',
 
 a.name_id as 'name_id',
 com.common_name as 'common_name',
 
 a.language_id as 'language_id',
 lan.iso639_6 as 'iso639_6',
 lan.namecache as 'language',
 
 a.geonameId as 'geoname_id',
 geo.name as 'geoname',
 
 a.period_id as 'period_id',
 per.period as 'period',
 
 a.reference_id as 'reference_id',
 lit.citationID as 'citationID'
 
FROM
 {$common_name_dB}tbl_name_applies_to a
 LEFT JOIN {$common_name_dB}tbl_name_entities ent ON ent.entity_id = a.entity_id
 LEFT JOIN {$common_name_dB}tbl_name_taxon tax ON tax.taxon_id = ent.entity_id
 
 LEFT JOIN {$common_name_dB}tbl_name_names nam ON  nam.name_id = a.name_id
 LEFT JOIN {$common_name_dB}tbl_name_commons com ON  com.common_id = nam.name_id
 
 LEFT JOIN {$common_name_dB}tbl_geonames_cache geo ON geo.geonameId = a.geonameId
 LEFT JOIN {$common_name_dB}tbl_name_languages lan ON  lan.language_id = a.language_id
 LEFT JOIN {$common_name_dB}tbl_name_periods per ON per.period_id= a.period_id

 LEFT JOIN {$common_name_dB}tbl_name_references ref ON ref.reference_id = a.reference_id
 LEFT JOIN {$common_name_dB}tbl_name_literature lit ON  lit.literature_id = ref.reference_id
WHERE
 1=1
";


	if(intval($p_taxonIndex)>0 && $p_taxon!='')$sql.="\n and a.entity_id = ent.entity_id and ent.entity_id = tax.taxon_id  and tax.taxonID='{$p_taxonIndex}'";
	if(intval($p_citationIndex)>0 && $p_citation!='')$sql.="\n and a.reference_id = ref.reference_id and ref.reference_id = lit.literature_id and lit.citationID='{$p_citationIndex}'";
	if(intval($p_common_nameIndex)>0 && $p_common_name!='')$sql.="\n and a.name_id = nam.name_id and nam.name_id = com.common_id and com.common_id = '$p_common_nameIndex'";
	if(strlen($p_languageIso)>0 && $p_language!='')$sql.="\n and a.language_id = lan.language_id and lan.iso639_6='$p_languageIso'";
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
	global $common_name_dB;
	
	$sql="
SELECT
 COUNT(*) as 'count'
FROM
 {$common_name_dB}tbl_name_applies_to a
WHERE
 a.entity_id = '{$p_entityIndex}'
 and a.reference_id = '{$p_referenceIndex}'
 and a.name_id = '{$p_common_nameIndex}' 
 and a.language_id = '{$p_languageIndex}'
 and a.geonameId ='{$p_geonameIndex}'
 and a.period_id = '{$p_periodIndex}'
 
";
 	$result = db_query($sql);
	$row = mysql_fetch_array($result);

	if($row['count']==1){
		return true;
	}
	return false;
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
	
	$result = db_query($sql);
	$row = mysql_fetch_array($result);
	return taxon($row);
}

// Copied From ??
function getCitation($citation_id){
	 $sql = "
SELECT
 citationID, suptitel, le.autor as editor, la.autor, l.periodicalID, lp.periodical, vol, part, jahr, pp
FROM
 tbl_lit l
 LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID = l.periodicalID
 LEFT JOIN tbl_lit_authors le ON le.autorID = l.editorsID
 LEFT JOIN tbl_lit_authors la ON la.autorID = l.autorID
WHERE
 citationID = '" . $citation_id . "'";
	$result = db_query($sql);
	return protolog(mysql_fetch_array($result));
}
?>