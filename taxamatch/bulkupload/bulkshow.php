<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<?php
session_name('herbarium_wu_taxamatch');
session_start();

include('inc/variables.php');
include('inc/connect.php');
/*
if (empty($_SESSION['uid'])) die();

$result = db_query("SELECT * FROM tbljobs WHERE jobID = '" . intval($_GET['id']) . "' AND uid = '" . $_SESSION['uid'] . "'");
if (mysql_num_rows($result) == 0) die();
$row = mysql_fetch_array($result);
$jobID = $row['jobID'];
*/
$jobID=10;
$result = db_query("SELECT * FROM tbljobs WHERE jobID = '" . intval($jobID) . "' ");
if (mysql_num_rows($result) == 0) die();
$row = mysql_fetch_array($result);
echo<<<EOF
<script>
var dinit={'AjaxUrl':'bulkshow_ajax.php','jobID':$jobID};
</script>
EOF;

?>
<html>
<head>
  <title>taxamatch - bulk upload</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <link rel="stylesheet" type="text/css" href="js/south-street/jquery-ui-1.8.14.custom.css">
<link rel="stylesheet" href="css/pagination.css" type="text/css" />
<script src="js/jquery-1.5.1.min.js" type="text/javascript"></script>
<script src="js/jquery-ui-1.8.13.custom.min.js" type="text/javascript"></script>
<script type="text/javascript" src="js/jquery.pagination.js"></script> 
<script type="text/javascript" src="js/freud_postit.js"></script> 
</head>

<body>
<script type="text/javascript" language="JavaScript">

var ITEMSPERPAGE=20;
var displayOnlyParts=0;
$(function() {
	showResults(0, 0, 1);	
	
	$('#longButton').click(function() {
		
		if(displayOnlyParts){
			displayOnlyParts=0;
			$('#longButton').val('display only < 100%');
			
		}else{
			displayOnlyParts=1;
			$('#longButton').val('display everything');
		}
		showResults(0, 0, 1);
	});
	
	$('#ExportButton').click(function() {
		window.open("bulkexport.php?id=" + dinit['jobID'] + "&short=" + displayOnlyParts, "bulkexport", "width=100, height=100, top=10, left=10");
		return false;
	});
	
	

});
var blocked=false;
function showResults(page_index, jq, newsearch){
	if(jq[0]!=undefined  && blocked){
		return;
	}
	if(jq[0]!=undefined){
		if(jq[0].id=='displayResultPagination2'){
			blocked=true;
			 $("#displayResultPagination").trigger('setPage', page_index);
			 blocked=false;
		}else if(jq[0].id=='displayResultPagination'){
			blocked=true;
			$("#displayResultPagination2").trigger('setPage', page_index);
			blocked=false;
		}
	}
	
	
	$('#displayResultLoading').css('visibility','visible');
	PostIt(
		'x_showResult',
		{'jobID':dinit['jobID'],'page_index':page_index,'limit':ITEMSPERPAGE,'displayOnlyParts':displayOnlyParts},
		function(data){
			
			if(newsearch!=undefined){
				$("#displayResultPagination").pagination(data.maxc, {
					num_edge_entries: 2,
					num_display_entries: 8,
					callback: showResults,
					items_per_page:ITEMSPERPAGE
				});
				$("#displayResultPagination2").pagination(data.maxc, {
					num_edge_entries: 2,
					num_display_entries: 8,
					callback: showResults,
					items_per_page:ITEMSPERPAGE
				});
			}
			$('#displayResult').html(data.html);
			$('#displayResultLoading').css('visibility','hidden');
		});
}


</script>
<?php



echo "<h1>" . $row['filename'] . "</h1>\n"
   . "starttime: " . $row['start'] . "<br>\n"
   . "endtime: " . $row['finish'] . "\n"
   . "<p>\n";



$displayOnlyParts =1;



?>

<form name='f'>
<input type='button' value='display only < 100%' name='long' id="longButton" >&nbsp;&nbsp <input type='button' id="ExportButton" value='export csv' name='export'>
</form><p>


<div id="displayResultLoading" style="visibility:hidden">Loading... <img alt="loading..." src="images/loader.gif"></div>
<div style="height:30px;margin-top:20px;" id="displayResultPagination"></div>
<div id="displayResult"></div>
<div style="height:30px;margin-top:20px;" id="displayResultPagination2"></div>


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