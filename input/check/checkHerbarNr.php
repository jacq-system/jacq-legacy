<?php
session_start();
require("../inc/connect.php");
require __DIR__ . '/../vendor/autoload.php';

use Jaxon\Jaxon;

$jaxon = jaxon();
$jaxon->setOption('core.request.uri', 'ajax/checkHerbarNrServer.php');

$jaxon->register(Jaxon::CALLABLE_FUNCTION, "changeDropdownCollection");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "listDoubleHerbNr");

function makeDropdownSource()
{
    if (checkRight('admin')) {
        $data = "  <option value=\"0\">all institutions</option>\n";

        // only sources with valid collections can be listed
        $rows = dbi_query("SELECT m.source_id, m.source_code, m.source_name
                           FROM herbarinput.`meta` m, herbarinput.tbl_management_collections mc
                           WHERE m.source_id = mc.source_id
                           GROUP BY m.source_id
                           ORDER BY source_code")
                ->fetch_all(MYSQLI_ASSOC);
        foreach ($rows as $row) {
            $data .= "  <option value=\"" . htmlspecialchars($row['source_id']) . "\">"
                   . htmlspecialchars($row['source_code']) . " - " . htmlspecialchars($row['source_name'])
                   . "</option>\n";
        }
    } else {
        $row = dbi_query("SELECT m.source_id, m.source_code, m.source_name
                          FROM herbarinput.`meta` m
                          WHERE m.source_id = {$_SESSION['sid']}")
               ->fetch_assoc();
        $data = "  <option value=\"" . htmlspecialchars($row['source_id']) . "\">"
              . htmlspecialchars($row['source_code']) . " - " . htmlspecialchars($row['source_name'])
              . "</option>\n";
    }

    return $data;
}

?><!DOCTYPE HTML>
<html>
<head>
  <title>herbardb - list duplicate herbar numbers</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="../css/screen.css">
  <style type="text/css">
    #list {
        margin-top: 2em;
        min-height: 400px;
    }
    table.result {
        border: 1px solid black;
    }
    table.result tr:nth-child(even) {
        background-color: #00e000;
    }
    table.result tr:nth-child(odd) {
        background-color: #00c000;
    }
    table.result th {
        border: 1px solid black;
        font-weight: bold;
        font-size: medium;
        background-color: #a0a0a0;
    }
    table.result td {
        border: 1px solid black;
        text-align: center;
    }
    table.result td.links {
        text-align: left;
    }
    table.result a:focus {
        font-weight:bold;
        color:#ff0000;
        text-decoration:underline;
    }
    html.waiting, html.waiting *
    {
        cursor: wait !important;
    }
  </style>
  <?php echo $jaxon->getScript(true, true); ?>
  <script src="https://code.jquery.com/jquery-3.6.0.slim.min.js"></script>
  <script type="text/javascript" language="JavaScript">
    function listDoubles()
    {
      $('html').addClass('waiting');
      jaxon_listDoubleHerbNr(document.getElementById('source').value, document.getElementById('collection').value);
      $('html').removeClass('waiting');

      return false;
    }
    $( document ).ready(function() {
      jaxon_changeDropdownCollection(document.getElementById('source').value);
    });
  </script>
</head>

<body>
<h1>List of specimens with identical Herb.#</h1>

<form name="f">
  <table><tr><td>
    <b>Institution:</b>
        <select size="1" id="source" onchange="jaxon_changeDropdownCollection(document.getElementById('source').value); return false;">
          <?php echo makeDropdownSource(); ?>
        </select>&nbsp;
    <b>Collection:</b> <select size="1" id="collection"><option value="0">all collections</option></select>&nbsp;
    <input class="button" type="submit" value=" list " onclick="return listDoubles();">
  </td></tr></table>
</form>

<div id="list"></div>

</body>
</html>