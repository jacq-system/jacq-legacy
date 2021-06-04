<?PHP
session_start();
require("../inc/connect.php");

$query_url=$_SERVER['QUERY_STRING'];
$query_url[strpos($query_url,'&')]='?';
$query_url="http://www.geonames.org/servlet/".$query_url;
//echo $query_url;exit;

$cookieFile=dirname(__FILE__)."/tmp_cookie.txt";
$curl = curl_init(); 
curl_setopt($curl, CURLOPT_HEADER,0); 
curl_setopt($curl, CURLOPT_RETURNTRANSFER,true); 
curl_setopt($curl, CURLOPT_USERAGENT,'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.0.3705; .NET CLR 1.1.4322; Media Center PC 4.0)'); 
curl_setopt($curl, CURLOPT_COOKIEFILE,$_OPTIONS['GEONAMES']['cookieFile']); 
curl_setopt($curl, CURLOPT_COOKIEJAR,$_OPTIONS['GEONAMES']['cookieFile']);
curl_setopt($curl, CURLOPT_URL,$query_url); 
$src = curl_exec($curl); 

header("Content-type: text/xml");
echo $src;
?>