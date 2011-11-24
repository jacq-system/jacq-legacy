<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
 <title>herbardb - list Images</title>
 <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
 <link rel="stylesheet" type="text/css" href="css/screen.css">
 <link rel="stylesheet" type="text/css" href="js/lib/jQuery/css/south-street/jquery-ui-1.8.14.custom.css">
 <link rel="stylesheet" href="inc/jQuery/css/blue/style_nhm.css" type="text/css" />
 <link rel="stylesheet" href="inc/jQuery/jquery_autocompleter_freud.css" type="text/css" />
 <link rel="stylesheet" href="js/lib/jQuery/css/pagination.css" type="text/css" />
 <style type="text/css">
 th { font-weight: bold; font-size: medium }
 tr { vertical-align: top }
 td { vertical-align: top }
 .missing { margin: 0px; padding: 0px }
 td.missing { vertical-align: middle }
 #tabs li .ui-icon-close { float: left; margin: 0.4em 0.2em 0 0; cursor: pointer; }
	
 </style>
 <script src="js/lib/jQuery/jquery.min.js" type="text/javascript"></script>
 <script src="js/lib/jQuery/jquery-ui.custom.min.js" type="text/javascript"></script>
 <script type="text/javascript" src="js/jquery_autocompleter_freud.js"></script>
 <script type="text/javascript" src="js/lib/jQuery/jquery.pagination.js"></script>
 <script src="js/freudLib.js" type="text/javascript"></script>
 <script src="js/parameters.php" type="text/javascript"></script>
</head>

<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");

$db = clsDbAccess::Connect('INPUT');


$_dvar=array(
	'serverIP'=>false,
	'family'=>false,
	'source_id'=>false,
);

if(isset($_SESSION['checkPictures'])){
	$_dvar=array_merge($_dvar,$_SESSION['checkPictures']);
}

	
	
if(!$_dvar['serverIP']){
	/*$dbst = $db->query("SELECT imgserver_IP FROM tbl_img_definition LIMIT 1");
	$row = $dbst->fetch();
	$_dvar['serverIP']=$row['imgserver_IP'];*/
	$_dvar['serverIP']='131.130.131.9';
}
	
	
// get imageserver...
$server="";
$dbst = $db->query("SELECT imgserver_IP FROM tbl_img_definition GROUP BY imgserver_IP");
foreach ($dbst as $row) {
   $server.="<option value=\"{$row['imgserver_IP']}\"";
    if($_dvar['serverIP']==$row['imgserver_IP']){
		$server.=" selected";
    }
   $server.=">{$row['imgserver_IP']}</option>\n";
}

$cf = new CSSF();
$cf->setEcho(false);
$family='<div style="float:left">'.$cf->inputJqAutocomplete2(0, 0, 20, "family", $_dvar['family'], "index_jq_autocomplete.php?field=family",70,2,'','',0,0,0);

echo<<<EOF
<script>
var dinit={'serverIP':'{$_dvar['serverIP']}','source_id':'{$_dvar['source_id']}','djatokaAjaxUrl':'ajax/checkDjatoka.php'};
</script>
EOF;
?>
<body>
<h1>check Images</h1>

<script>
function makeOptions() {
	options = "width=";
	if (screen.availWidth<990)
		options += (screen.availWidth - 10) + ",height=";
	else
		options += "990, height=";
	if (screen.availHeight<710)
		options += (screen.availHeight - 10);
	 else
		 options += "710";
	 options += ", top=10,left=10,scrollbars=yes,resizable=yes";
	return options;
}
function editSpecimens(sel) {
	target = "editSpecimens.php?sel=" + encodeURIComponent(sel);
	MeinFenster = window.open(target,"editSpecies",makeOptions());
	MeinFenster.focus();
}
function editSpecimensSimple(filename) {
	target = "editSpecimensSimple.php?filename="+encodeURIComponent(filename);
	MeinFenster = window.open(target,"editSpecimensSimple",makeOptions());
	MeinFenster.focus();
}
function getImageServerIP(){
	return $('#serverIP').val()
}


// load..
function checkConsisty(page_index, jq, newsearch){
	$('#ConsitencyLoading').css('visibility','visible');
	PostIt(
		'x_djatoka_consistency_check',
		{'serverIP':getImageServerIP(),'page_index':page_index,'limit':ITEMSPERPAGE},
		function(data){
			$('#ConsitencyLoading').css('visibility','hidden');
			if(newsearch!=undefined){
				$("#PaginationConsitency").pagination(data.maxc, {
					num_edge_entries: 2,
					num_display_entries: 8,
					callback: checkConsisty,
					items_per_page:ITEMSPERPAGE
				});
			}
			$('#res_tabs2').html(data.html);
		}
	);
}
var ITEMSPERPAGE=20;
			
var $tabs;
var tab_counter = 4;
$(function() {
	// close icon: removing the tab on click
	// note: closable tabs gonna be an option in the future - see http://dev.jqueryui.com/ticket/3924
	$( "#tabs span.ui-icon-close" ).live( "click", function() {
		var index = $( "li", $tabs ).index( $( this ).parent() );
		$tabs.tabs( "remove", index );
	});
	$tabs =$('#tabs').tabs({
		tabTemplate: "<li><a href='#{href}'>#{label}</a> <span class='ui-icon ui-icon-close'>Remove Tab</span></li>",
		add: function( event, ui ) {
			var tab_content = "Tab " +  + " content.";
			$( ui.panel ).append( "<div id=\"loadingtabres"+tab_counter +"\" style=\"display: none;\">Loading... <img alt=\"loading...\" src=\"webimages/loader.gif\"></div><div id=\"tab_res"+tab_counter +"\"></div><div style=\"height:30px;margin-top:20px;\" id=\"PaginationTabres"+tab_counter +"\"></div>" );

		},
		select: function(event, ui) {
			if(ui.index==1){
				checkConsisty(0,0,1);
			}
		}
	});
	
		
	$('#datepicker').datepicker({
		showOn: "both",
		//buttonImage: "images/calendar.gif",
		constrainInput: true,
		
	});
	$('#datepicker').datepicker( "setDate" , new Date() )
	$('#format').change(function() {
		$('#datepicker').datepicker('option','dateFormat','yy-mm-dd' );
	});
	
	updateInstitutions(dinit['serverIP'],dinit['source_id']);
	ACFreudInit();
	
	$('#serverIP').change(function(){
		updateInstitutions( getImageServerIP(),0 );
	});
	$('#filterChecks').click(function() {
		filterChecks(0,0,1,0);
	});


	$('#filterChecksFaulty').click(function() {
		filterChecks(0,0,1,1);
	});
	
	
	$('#ImportPictures').click(function() {
		PostIt(
			'x_ImportImages',
			{'serverIP':getImageServerIP()},
			function(data){
				$("#dinformation").html(data);
				$("#dialog-information").dialog({
					resizable: false,
					modal: false,
					buttons: {"OK": function() {$( this ).dialog( "close" );}}
				});
				return;
			}
		);
	});

	$('#RescanServer').click(function() {
		$('#RescanServerLoading').css('visibility','visible');
		PostIt(
			'x_importDjatokaListIntoDB',
			{'serverIP':getImageServerIP()},
			function(data){
				$('#RescanServerLoading').css('visibility','hidden');
				$("#dinformation").html(data);
				$("#dialog-information").dialog({
					resizable: false,
					modal: false,
					buttons: {"OK": function() {$( this ).dialog( "close" );}}
				});
				return;
			}
		);
	});

	$('#ListThreads').click(function() {
		ListThreads(0, 0, 1)
	});
	
	
});

function ListThreads(page_index, jq, newsearch){
		$('#ThreadsLoading').css('visibility','visible');
		PostIt(
			'x_listImportThreads',
			{'serverIP':getImageServerIP() , 'starttime':$('#datepicker').val(), 'page_index':page_index,'limit':ITEMSPERPAGE},
			function(data){
				
				$('#ThreadsLoading').css('visibility','hidden');
				if(newsearch!=undefined){
					$("#PaginationThreads").pagination(data.maxc, {
						num_edge_entries: 2,
						num_display_entries: 8,
						callback: ListThreads,
						items_per_page:ITEMSPERPAGE
					});
				}
				$('#res_tabs3').html(data.html);
			}
		);
}
var faulty_pers=0;
function filterChecks(page_index, jq, newsearch, faulty){
	if(newsearch!=undefined){
		faulty_pers=faulty;
	}
	$('#LastScanLoading').css('visibility','visible');
	PostIt(
		'x_pictures_check',
		{'serverIP':getImageServerIP(), 'family':$('#ajax_family').val(), 'source_id':$('#source_id').val(),'faulty':faulty_pers,'page_index':page_index,'limit':ITEMSPERPAGE},
		function(data){
			$('#LastScanLoading').css('visibility','hidden');
			if(newsearch!=undefined){
				$("#PaginationLastScan").pagination(data.maxc, {
					num_edge_entries: 2,
					num_display_entries: 8,
					callback: filterChecks,
					items_per_page:ITEMSPERPAGE
				});
			}
			$('#lastScan').html(data.html);
		}
	);
}
var threadids={};
function loadImportLog(threadid, times){
	
	$tabs.tabs( "add", "#tabs-" + tab_counter,"Log of "+times);
	$tabs.tabs( "select" , $tabs.tabs( "length" )-1 );
	
	threadids[tab_counter]=threadid;
	
	loadImportLogIntoTab(0, 0, 1, tab_counter);
	tab_counter++;
}


function loadImportLogIntoTab(page_index, jq, newsearch, tabres){
	
	if(jq[0] != undefined){
		tabres=1*(jq[0].id.replace(/PaginationTabres/,''));
	}
	threadid=threadids[tabres];
	$('#loadingtabres'+tabres).css('visibility','visible');

	PostIt(
		'x_listImportLogs',
		{'serverIP':getImageServerIP() , 'thread_id':threadid, 'page_index':page_index,'limit':ITEMSPERPAGE},
		function(data){
			$('#loadingtabres'+tabres).css('visibility','hidden');
			
			
			if(newsearch!=undefined){
				$("#PaginationTabres"+tabres).pagination(data.maxc, {
					num_edge_entries: 2,
					num_display_entries: 8,
					callback: loadImportLogIntoTab,
					items_per_page:ITEMSPERPAGE
				});
			}
			$('#tab_res'+tabres).html(data.html);
		}
	);
}


function updateInstitutions(imgserverIP,source_id){
	PostIt(
		'x_listInstitutions',
		{'serverIP':imgserverIP , 'source_id':source_id},
		function(data){
			$('#source_id').html(data.inst);
			$('#lastScan2').html("Last Scan: "+data.lastscan);
			$('#lastScan').html("");
			if($tabs.tabs('option', 'selected')==1){
				checkConsisty();
			}
		}
	);
}

function processItem(itemname){
	//alert('dosomething');
}

function PostIt(method, params, callback){
	$.post(
		dinit['djatokaAjaxUrl'],
		{'method': method, 'params':params},
		function(data){
			//p(data,3);
			if(data.ob!=undefined){
				$("#dwarning").html("Some not fetched error occured: "+data.ob);
				$("#dialog-warning").dialog({
					resizable: false,
					modal: false,
					buttons: {"OK": function() {$( this ).dialog( "close" );}}
				});
			}
			
			if(data.info!=undefined){
				$("#dinformation").html("Some info: "+data.info);
				$("#dialog-information").dialog({
					resizable: false,
					modal: false,
					buttons: {"OK": function() {$( this ).dialog( "close" );}}
				});
			}
			
			if(data.error!=undefined || data.res==undefined ){
				if(data.error!=undefined){
					$("#derror").html(data.error);
				}else if(data.res==undefined){
					$("#derror").html("res undefined");
				}else{
					$("#derror").html("error");
				}
				
				$("#dialog-error").dialog({
					resizable: false,
					modal: false,
					buttons: {"OK": function() {$( this ).dialog( "close" );}}
				});
				return;
				
			}
			
			callback(data.res);
		}, 
		'json'
	);
}
</script>

  Server:
 <select size="1" name="serverIP" id="serverIP">
<?PHP echo $server; ?>
</select> &nbsp;<input type="button" name="ImportPictures" id="ImportPictures" value="ImportPictures">&nbsp;<input type="button" name="RescanServer" id="RescanServer" value="Rescan Server"><p>
<div id="RescanServerLoading" style="visibility:hidden">Loading... <img alt="loading..." src="webimages/loader.gif"></div>

<div id="tabs">
<ul>
 <li><a href="#tabs-1">Check Images against Server</a></li>
 <li><a href="#tabs-2">Check Consistency at Server</a></li>
 <li><a href="#tabs-3">Image Logs</a></li>
</ul>

  <div id="tabs-1">
<form action="" method="POST" name="f" id="f">
<table><tr><td>
  Institution:
  <select size="1" name="source_id" id="source_id">
  </select>
</td><td width="10">
  &nbsp;
</td><td>
  <div style="float:left">Family:&nbsp;</div> <?PHP echo $family; ?>
</td><td width="10">
 
</td><td>
  <input type="button" name="filterChecks" id="filterChecks" value="Get Last Scan"> <input type="button" name="filterChecksFaulty" id="filterChecksFaulty" value="Get last Scan With Faulty HerbNumbers">
</td>
</tr></table>

<div id="LastScanLoading" style="visibility:hidden">Loading... <img alt="loading..." src="webimages/loader.gif"></div>
<div id="lastScan2"></div>
<div id="lastScan"></div>
<div style="height:30px;margin-top:20px;" id="PaginationLastScan"></div>

</form>
</div>
<div id="tabs-2">
<div id="ConsitencyLoading" style="visibility:hidden">Loading... <img alt="loading..." src="webimages/loader.gif"></div>
<div id="res_tabs2"></div>
<div style="height:30px;margin-top:20px;" id="PaginationConsitency"></div>

</div>
<div id="tabs-3">
Date: <input type="text" id="datepicker" name="datepicker" size="30"/>&nbsp;<input type="button" name="ListThreads" id="ListThreads" value="List Threads above this time (empty for all)">
<div id="ThreadsLoading" style="visibility:hidden">Loading... <img alt="loading..." src="webimages/loader.gif"></div>
<div id="res_tabs3"></div>
<div style="height:30px;margin-top:20px;" id="PaginationThreads"></div>

</div>
</div>

</div>


<div style="display:none">

<div id="dialog-information" title="Information">
	<div style="float:left"><span class="ui-icon ui-icon-info" style="float:left; margin:0 7px 20px 0;"></div><div id="dinformation" style="float:left;width:90%"> These items will be permanently deleted and cannot be recovered. Are you sure?</div>
</div>
<div id="dialog-warning" title="Warning">
	<div style="float:left"><span class="ui-icon ui-icon-notice" style="float:left; margin:0 7px 20px 0;"></div><div id="dwarning" style="float:left;width:90%">These items will be permanently deleted and cannot be recovered. Are you sure?</div>
</div>
<div id="dialog-error" title="Error">
	<div style="float:left;height:100%;"><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></div><div id="derror" style="float:left;width:90%">These items will be permanently deleted and cannot be recovered. Are you sure?</div>
</div>

</div>





</body>
</html>