<?php

$value='';
$JSinitGeonameId='';

if($_GET['geonameID']!=''){
	$JSinitGeonameId="searchGeonameID('{$_GET['geonameID']}');";
}else if($_GET['geoname']!=''){
	$value=$_GET['geoname'];
}

$src=file_get_contents("http://www.geonames.org/maps/showOnMap?q=".$value);


$js1=<<<EOF
<script>
function selectGeoname(geonameID){
	window.opener.UpdateGeoname(geonameID);
	window.opener.focus();
}
function fulltextsearch2(){
	 val=document.searchForm2.q2.value;
	 fulltextsearch3(val);
}
function fulltextsearch3(val){
	 document.searchForm.q.value = val;
	 document.searchForm2.q2.value=val;
	 fulltextsearch();
}
function searchGeonameID(geonameID){
	reset();
	setDatasource(1000,geonameID,'Geonames',0);
	mapHandler();

}
function UpdateGeography(gn,gi){

	if(gi!=''){
		searchGeonameID(gi);
	}else if(gn!=''){
		fulltextsearch3(gn)
	}
}

</script>
<style>
#list {
top:530px;
}
.geonameid1{
text-decoration:underline;
text-weight:bold;
color:#00F;
}
</style>
EOF;

$js2=<<<EOF
<div style="font-size: 10px;left: 230px;position: absolute;text-align: center;top: 500px;width: 730px;">
   <form onSubmit="javascript:fulltextsearch2();return false;" name="searchForm2">

      
Suche: <input class="topmenu" name="q2" size="20" value="{$value}" type="text">

   </form> 
</div>

EOF;

$js3=<<<EOF
<script>
{$JSinitGeonameId}
</script>
EOF;

$src=str_replace(
	array(
		"src=\"/maps/gmaps2.js\"",
		"src=\"/",
		"src=/",
		"href=\"/",
		"</head>",
		"<div id=\"list\">",
		"</body>"
	),
	array(
		"src=\"geonames/gmaps2_nhm.js\"",
		"src=\"http://www.geonames.org/",
		"src=http://www.geonames.org/",
		"href=\"http://www.geonames.org/",
		$js1."</head>",
		$js2."<div id=\"list\">",
		$js3."</body>",
	)
,$src);


echo $src;

function p($var){
	echo "<pre>";
	print_r($var);
	echo "</pre>";
}

 ?>