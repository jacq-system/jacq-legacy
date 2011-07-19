<?php
session_start();
require("inc/connect.php");

$value='';
$JSinitGeonameId='';
$v2='';

if(isset($_GET['geonameID']) && $_GET['geonameID']!=''){
	$JSinitGeonameId="searchGeonameID('{$_GET['geonameID']}');";
}else if(isset($_GET['geoname']) && $_GET['geoname']!=''){
	$value=$_GET['geoname'];
	$v2="q=".urlencode($value);
}

$curl = curl_init(); 
curl_setopt($curl, CURLOPT_HEADER,0); 
curl_setopt($curl, CURLOPT_POST,true); 
curl_setopt($curl, CURLOPT_RETURNTRANSFER,true); 
curl_setopt($curl, CURLOPT_USERAGENT,'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.0.3705; .NET CLR 1.1.4322; Media Center PC 4.0)'); 
curl_setopt($curl, CURLOPT_COOKIEFILE,$_OPTIONS['GEONAMES']['cookieFile']); 
curl_setopt($curl, CURLOPT_COOKIEJAR,$_OPTIONS['GEONAMES']['cookieFile']);
curl_setopt($curl, CURLOPT_URL,'http://www.geonames.org/servlet/geonames'); 
curl_setopt($curl, CURLOPT_POSTFIELDS,"username={$_OPTIONS['GEONAMES']['username']}&password={$_OPTIONS['GEONAMES']['password']}&rememberme=1&srv=12");
$result = curl_exec($curl); 

curl_setopt($curl, CURLOPT_URL,"http://www.geonames.org/maps/showOnMap?{$v2}"); 
$src = curl_exec($curl);
curl_close ($curl); 
		

/*
FF
about:config
dom.disable_window_flip => auf false

opera
about:config
Allow script to lower window
Allow script to raise window	
*/

$js_head=<<<EOF
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

$searchextennsion=<<<EOF
<div style="font-size: 10px;left: 230px;position: absolute;text-align: center;top: 500px;width: 730px;">
   <form onSubmit="javascript:fulltextsearch2();return false;" name="searchForm2">

      
Suche: <input class="topmenu" name="q2" size="20" value="{$value}" type="text">

   </form> 
</div>

EOF;

$js_body=<<<EOF
<script>
username = 'gunthers';
{$JSinitGeonameId}
</script>
EOF;


$src=str_replace(
	array(
		"src=\"/",
		"src=/",
		"href=\"/",
	),
	array(
		"src=\"http://www.geonames.org/",
		"src=http://www.geonames.org/",
		"href=\"http://www.geonames.org/",
	)
,$src);

$src=str_replace(
	array(
		'src="http://www.geonames.org/maps/gmaps2.js"',
		'</head>',
		'</body>',
		'<div id="list">',
	),
	array(
		'src="geonames/gmaps2_nhm.js"',
		$js_head.'</head>',
		$js_body.'</body>',
		$searchextennsion.'<div id="list">',
	)
,$src);


echo $src;

function p($var){
	echo "<pre>";
	print_r($var);
	echo "</pre>";
}

 ?>