<?php
session_start();
require("inc/connect.php");
require __DIR__ . '/vendor/autoload.php';

if (!checkRight('admin')) {
    die("You don't have the right to do that.");
}
?><!DOCTYPE html>
<html>
<head>
  <title>herbardb - make StblID</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
</head>
<body>
<h2>Programmlauf von</h2>
<h1>makeSpecimensStblidCmd.php</h1>
<?php
$output = shell_exec('./scripts/makeSpecimensStblidCmd.php');
if ($output) {
    echo "<pre>" . var_export($output, true) . "</pre>";
}
?>
</body>
</html>
