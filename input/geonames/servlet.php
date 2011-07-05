<?PHP



$query_url=$_SERVER['QUERY_STRING'];
$query_url[strpos($query_url,'&')]='?';
$query_url="http://www.geonames.org/servlet/".$query_url;
$src=file_get_contents($query_url);
header("Content-type: text/xml");
echo $src;
?>