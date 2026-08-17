<?php
session_start();
require("../inc/gatekeeper.php");
require __DIR__ . '/../vendor/autoload.php';

if ($_SESSION['gid'] != 12) {   // only general administrators (Group ID 12) may access this page
    die();
}

//    <li><a href="javascript: openWindow('checkSpecimensStblidMulti.html', 'checkSpecimensStblidMulti');">checkSpecimensStblidMulti</a></li>
//    <li><a href="javascript: openWindow('checkScrutiny.php', 'checkScrutiny');">checkScrutiny</a></li>

?><!DOCTYPE HTML>
<html lang="en">
<head>
    <title>herbardb - checks</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <link rel="stylesheet" type="text/css" href="../css/screen.css">
    <script type="text/javascript" language="JavaScript">
        function openWindow(target, name) {
            let options = "width=";
            if (screen.availWidth<1380)
                options += (screen.availWidth - 10) + ",height=";
            else
                options += "1380, height=";
            if (screen.availHeight<810)
                options += (screen.availHeight - 10);
            else
                options += "860";
            options += ", top=10,left=10,scrollbars=yes,resizable=yes";

            let newWindow = window.open(target,name,options);
            newWindow.focus();
        }
    </script>
</head>

<body>
<h1>various database checks</h1>
<h2>Specimens and scientific names</h2>
<ul>
    <li><a href="javascript: openWindow('checkNomenclature.php', 'checkNomenclature');">check Nomenclature</a></li>
    <li><a href="javascript: openWindow('checkHerbarNr.php', 'checkHerbarNr');">List of specimens with identical Herb.#</a></li>
    <li><a href="javascript: openWindow('checkSyn.php', 'checkSyn');">mismatch in synonymy</a></li>
    <li><a href="javascript: openWindow('checkCoordinates.php', 'checkCoordinates');">check Coordinates</a></li>
    <li><a href="javascript: openWindow('checkQuadrant.php', 'checkQuadrant');">check Quadrant</a></li>
    <li><a href="javascript: openWindow('checkSpecimensStblid.php', 'checkSpecimensStblid');">Check tbl_specimens_stblid against tbl_specimens</a></li>
    <li><a href="javascript: openWindow('checkHybrids.php', 'checkHybrids');">incorrect Hybrids</a></li>
    <li><a href="javascript: openWindow('checkTaxa.php', 'checkTaxa');">incorrect Taxa</a></li>
</ul>

<h2>Imports</h2>
<ul>
    <li><a href="javascript: openWindow('checkimportSpecimens.php', 'checkimportSpecimens');">check Import Specimens</a></li>
    <li><a href="javascript: openWindow('checkimportTaxa.php', 'checkimportTaxa');">check Import Taxa</a></li>
</ul>

<h2>Pictures</h2>
<ul>
    <li><a href="javascript: openWindow('checkPictures.php', 'checkPictures');">check djatoka pictures</a></li>
</ul>

<h2>Authors, Collectors and Persons (2011)</h2>
<ul>
    <li><a href="javascript: openWindow('checkLitAuthors.php', 'checkLitAuthors');">checkLitAuthors</a></li>
    <li><a href="javascript: openWindow('checkTaxAuthors.php', 'checkTaxAuthors');">checkTaxAuthors</a></li>
    <li><a href="javascript: openWindow('scanLitAuthors.php', 'scanLitAuthors');">scanLitAuthors</a></li>
    <li><a href="javascript: openWindow('scanTaxAuthors.php', 'scanTaxAuthors');">scanTaxAuthors</a></li>
    <li><a href="javascript: openWindow('scanPerson.php', 'scanPerson');">scanPerson</a></li>
    <li><a href="javascript: openWindow('checkCollectors.php', 'checkCollectors');">checkCollectors</a></li>
    <li><a href="javascript: openWindow('scanCollectors.php', 'scanCollectors');">scanCollectors</a></li>
</ul>

</body>
</html>
