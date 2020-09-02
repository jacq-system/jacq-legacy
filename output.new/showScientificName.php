<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Cache-Control: post-check=0, pre-check=0", false);

require("inc/functions.php");

$scientificName = "";
if (isset($_GET['ID'])) {
    $ID = intval(filter_input(INPUT_GET, 'ID', FILTER_SANITIZE_NUMBER_INT));
    $dbLink->query("CALL herbar_view.GetScientificNameComponents($ID,@genericEpithet,@specificEpithet,@infraspecificRank,@infraspecificEpithet,@author)");
    // execute the second query to get values from OUT parameter
    $res = $dbLink->query("SELECT @genericEpithet,@specificEpithet,@infraspecificRank,@infraspecificEpithet,@author");
    $row = $res->fetch_assoc();
    if ($row) {
        $scientificName = $row['@genericEpithet'] . " " . $row['@specificEpithet'] . (($row['@infraspecificEpithet']) ? "\n" . $row['@infraspecificRank'] . " " . $row['@infraspecificEpithet'] : "") . " " . $row['@author'];
    }
}

?><!DOCTYPE html>
<html>
<head>
  <title>JACQ - Virtual Herbaria</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta name="description" content="FW4 DW4 HTML">
  <link type="text/css" rel="stylesheet" href="assets/custom/styles/jacq.css"  media="screen"/>
  <link rel="shortcut icon" href="JACQ_LOGO.png"/>
</head>
<body>
    <?php echo nl2br($scientificName); ?>
</body>
</html>