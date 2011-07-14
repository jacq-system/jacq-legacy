<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");
no_magic();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - edit Collector</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <style>
  .r{padding:5px 0 5px 1px}.ld{padding:0 0 10px 1px}.c, .c a{color:#282}hh div{display: block;}.hh{font-size: 13px;}
  .p, .t{font-size: 118%;line-height: 105%;}.p{color:#2200c1;text-decoration:underline}
  .kd b{color:#ffff00;}
  </style>
</head>

<body>

<?php
$url="http://www.google.com/pda?q=site%3Akiki.huh.harvard.edu%2Fdatabases%2Fbotanist_search.php+%22ASA+Botanist+ID%22+{$_GET['id']}";
$source=file_get_contents($url);

// get only searchresults
preg_match('/<div class="edewpi" id="universal">(.*)<a href="\/pda/msU',$source,$result);

// If available...
$result=preg_replace('/(botanistid=|id=)(\d+)(\D*)(>)(.*)(<)/msU', '\\1\\2\\3\\4 New Botanist ID: \\2 \\6', $result[1]);

// hide url if available
$result=preg_replace('/<span class="c">.*<\/span>/msU', '', $result);
// sometimes the link change, so make it absolute...
$result=str_replace('href="/','href="http://www.google.com/',$result);
// echo content:
$result='Google Suggestions:<br><br><div class="hh">'.$result.'</div>';

echo $result;
?>

</body>
</html>
