<!DOCTYPE html>
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
$output = shell_exec('./makeSpecimensStblidCmd.php');
if ($output) {
    echo "<pre>" . var_export($output, true) . "</pre>";
}
?>
</body>
</html>