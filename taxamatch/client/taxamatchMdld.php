<?PHP
$debug=1;
$databases_cache='databases_cache.inc';

if($_POST['update'] || (time()-filemtime($databases_cache)>50*7*24*60*60) ){
	require_once('inc/jsonRPCClient.php');
	require_once('inc/variables.php');   // BP, 07.2010

	$url = $options['hostAddr'] . "json_rpc_taxamatchMdld.php";

	try {
		$service = new jsonRPCClient($url);
		$services = $service->getDatabases();
		
		file_put_contents($databases_cache,serialize($services));
		
	}catch (Exception $e) {
		$out =  "Fehler " . nl2br($e);
	}
}

$services=unserialize(file_get_contents($databases_cache));

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - taxamatch MDLD</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link type="text/css" href="css/screen.css" rel="stylesheet">
  <link type="text/css" href="css/le-frog/jquery-ui-1.8.13.custom.css" rel="stylesheet" />	
  <script type="text/javascript" src="ajax/jquery-1.5.1.min.js"></script>
  <script type="text/javascript" src="ajax/jquery-ui-1.8.13.custom.min.js"></script>

  <script>
var tims=0;
var timid=0;

$(function() {
	$( "#dialog-about").dialog({
		autoOpen: false,
		modal: true,
		width:700,
		buttons:{"OK": function() {$( this ).dialog( "close" );}}
	});
	$('#aboutb').click(function(){
		$( "#dialog-about").dialog('open');
		return false;
	});
	$.ajaxSetup({
		error:function(x,e){
			if(x.status==0){
			alert('You are offline!!\n Please Check Your Network.');
			}else if(x.status==404){
			alert('Requested URL not found.');
			}else if(x.status==500){
			alert('Internel Server Error.');
			}else if(e=='parsererror'){
			alert('Error.\nParsing JSON Request failed.');
			}else if(e=='timeout'){
			alert('Request Time out.');
			}else {
			alert('Unknow Error.\n'+x.responseText);
			}
		}
	});
	$("#loading").hide().ajaxStart(function(){
		$(this).show();
		tims=0;timerA();
	}).ajaxStop(function(){
		$(this).hide();
		timerAC();
	}); 
 
	$('#showMatchJsonRPC').click(function(){
		$.ajax({
			type: "POST",
			url: "ajax/taxamatchMdldServer.php",
			data: $("#f").serialize()+"&function=showMatchJsonRPC&debug=<?PHP echo $debug; ?>",
			/*timeout: 1000,*/
			success: function(msg){
				$("#ajaxTarget").html(msg);
			}
		});
		return false;
	
	});
	
	$('#dumpMatchJsonRPC').click(function(){
		$.ajax({
			type: "POST",
			url: "ajax/taxamatchMdldServer.php",
			data: $("#f").serialize()+"&function=dumpMatchJsonRPC&debug=<?PHP echo $debug; ?>",
			/*timeout: 1000,*/
			success: function(msg){
				$("#ajaxTarget").html(msg);
			}
		});
		return false;
	
	});
	$("#database_vienna").change(function(){
		$("#database_extern").attr('selectedIndex', '-1');
	});
	$("#database_extern").change(function () {
		$('input[name=database][value=extern]').attr('checked','checked');
	})
	
});
function timerA(){
	$('#tim').html(tims+"s");
	tims++;
	timid= window.setTimeout(timerA, 1000);
}
function timerAC(){
	window.clearTimeout(timid);
}
	</script>
</head>

<body onload="document.f.searchtext.focus();">


<div id="dialog-about" title="MDLD taxamatch implementation">
(modified Damerau-Levenshtein algorithm, originally developed by tony rees at csiro dot org)<br>
php script for single (few line multiple) checks - (phonetic = near match not included so far)<br>
<br>
Functionality includes parsing of Names for <b>uninomials</b> (family and genus names), <b>binomials</b> and <b>trinomials</b> and includes a check for subgeneric names against the genus table in our reference list<br>
<br>
! content is mostly phanerogamic plants, to be complemented by the index fungorum and CoL names in october 2009!<br>
<br>
<br>
<b>Results</b> can be downloaded by clicking the <b>"export csv" </b>button<br>
<br>
<br>
For examples to show <b>full functionality</b> you might cut and paste the following strings:<br>
<br>
aceracees<br>
Johannesteismania<br>
Senecio (Cineraria) bicolor<br>
Ranunculus auricomus L. subsp. hevellus H&uuml;lsen ex Asch. & Graebn.<br>
Ranunculus aff. aquatilis<br>
cf.  Ranunculus aquatilis L. var. peltatus (Schrank) W.D.J.Koch, 1837<br>
Ranunculus auricomus forma subapetalus<br>
<br>
comment:<br>
In case we assume the resulting match to be a synonym in our locally adopted taxonomy a reference is given to the <b>"currently preferred accepted name"</b><br>
<br>
</div>

<h1>
  Taxamatch MDLD
  &nbsp;
  <a href="#"><img align="top" src="images/information.png" border="0" id="aboutb"></a>
</h1>
<p>
  <form name="f" id="f">
    <table>
      <tr>
        <td><textarea name="searchtext" style="width:50em; height:10em;"></textarea></td>
      </tr><tr>
        <td>
          <div id="dbext"><input type="radio" name="database" value="vienna" id="database_vienna" checked>
          <label for="database_vienna">Virtual Herbarium Vienna</label>
          <input type="radio" name="database"  value="extern" >
          <label for="database_col">Extern </label>
</div>
		  <select name="database_extern" id="database_extern" size="5">
<?PHP
foreach($services as $k=>$v){
	if($k!='vienna'){
		echo  "<option value=\"{$k}\">{$v['name']}</option>";
	}
}
?>		 
			</select>
        </td>
      </tr><tr>
        <td>
          <input type="checkbox" name="nearmatch" id="nearmatch"><label for="nearmatch">use near match</label>
          <!-- BP 07.2010: checkbox for synonyms in MDLD-result yes/no -->
          <input type="checkbox" name="showSyn" id="showSyn"><label for="showSyn">show synonyms</label>
        </td>
      </tr><tr>
        <td valign="top">
          <input type="submit" value="search" id="showMatchJsonRPC" name="searchSpecies">
        </td>
      </tr>
    </table>
  </form>
</p>
<div id="loading"><img src="images/loader.gif" valign="middle"><br><strong>Processing... <span id="tim"></span></strong></div>

<div id="ajaxTarget"></div>

<div style="color:lightgray; position:absolute; right:1px; bottom:1px;"><a href="#" id="dumpMatchJsonRPC"/>&pi;</div>
</body>
</html>