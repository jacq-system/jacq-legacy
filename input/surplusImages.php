<?php
session_start();
require("inc/connect.php");
require __DIR__ . '/vendor/autoload.php';

use Jaxon\Jaxon;

$jaxon = jaxon();
$jaxon->setOption('core.request.uri', 'ajax/surplusImagesServer.php');

$jaxon->register(Jaxon::CALLABLE_FUNCTION, "init");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "listSurplusImages");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "showLatestUpdate");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "checkServer");

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
    "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
    <title>herbardb - list Images</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <link rel="stylesheet" type="text/css" href="css/screen.css">
    <style type="text/css">
        html.waiting, html.waiting * {
            cursor: wait !important;
        }
        .highlight {
            background-color: rgba(255, 233, 89, 0.6);
        }
    </style>
    <?php echo $jaxon->getScript(true, true); ?>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"
            integrity="sha256-2Pmvv0kuTBOenSvLm6bvfBSSHrUJ+3A7x6P5Ebd07/g="
            crossorigin="anonymous">
    </script>
    <script type="text/javascript" language="JavaScript">
        function activateHighlighting()
        {
            $("a").on("click", function() {
                $(".highlight").removeClass("highlight");
                $(this).addClass("highlight");
            });
        }
        function getOptions()
        {
            let options = "width=";
            if (screen.availWidth<1380)
                options += (screen.availWidth - 10) + ",height=";
            else
                options += "1380, height=";
            if (screen.availHeight<710)
                options += (screen.availHeight - 10);
            else
                options += "810";
            options += ", top=10,left=10,scrollbars=yes,resizable=yes";

            return options;
        }
        function openinput(imageUrl, HerbNummer)
        {
            window.open(imageUrl, "jacqImage", getOptions());
            setTimeout(openSecondWindow, 100, HerbNummer);
        }
        function openSecondWindow(HerbNummer)
        {
            let windowSpecimen;
            windowSpecimen = window.open("editSpecimens.php?sel=<0>&new=1&HerbNummer=" + HerbNummer, "Specimens", getOptions());
            windowSpecimen.focus();
        }
        function recheckServer(serverID)
        {
            $('html').addClass('waiting');
            jaxon_checkServer(serverID);
        }
        function endRecheckServer(serverID)
        {
            $('html').removeClass('waiting');
            jaxon_showLatestUpdate(serverID);
        }
        function listImages(serverID)
        {
            $('html').addClass('waiting');
            jaxon_listSurplusImages(serverID);
            $('html').removeClass('waiting');
        }
    </script>
</head>

<body onload="jaxon_init()">

<h1>show images without specimen</h1>
<div>
    Server&nbsp;&nbsp;<span id="drp_servers"></span>
    <button onclick="listImages($('#drp_servers select').val())">list image files</button>
    <span id="latestUpdate"></span>
</div>
<br>
<div id="totalSurplusImages"></div>
<div id="surplusImageList"></div>
<div id="cmdOutput"></div>
</body>
</html>
