<?php
require("inc/functions.php");
require_once('inc/imageFunctions.php');

require __DIR__ . '/vendor/autoload.php';
use GuzzleHttp\Client;

?><!DOCTYPE html>
<html>
<head>
  <title>JACQ - Virtual Herbaria</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <style type="text/css">
    table, th, td {
      border: 1px solid;
    }
    th, td {
        text-align: center;
        padding: 2px 1ex;
    }
    td.ok {
        color: green;
    }
    td.fail {
        color: red;
    }
  </style>
</head>
<body>
  <h1>check all Djatoka installations</h1>
<?php
$checks = array('ok' => array(), 'fail' => array(), 'noPicture' => array());
$client = new Client(['timeout' => 2]);

$rows = $dbLink->query("SELECT source_id_fk, img_coll_short
                        FROM tbl_img_definition
                        WHERE imgserver_type = 'djatoka'
                         AND source_id_fk != 1
                        ORDER BY img_coll_short")
               ->fetch_all(MYSQLI_ASSOC);
foreach ($rows as $row) {

    $ok = true;
    $errorRPC = $errorImage = "";

    $result = $dbLink->query("SELECT s.specimen_ID
                              FROM tbl_specimens s, tbl_management_collections mc
                              WHERE s.collectionID = mc.collectionID
                               AND s.accessible = 1
                               AND s.digital_image = 1
                               AND mc.source_id = " . $row['source_id_fk'] . "
                              ORDER BY s.specimen_ID
                              LIMIT 1");
    if ($result->num_rows > 0) {
        $specimenID = $result->fetch_assoc()['specimen_ID'];

//        $picdetails = getPicDetails($specimenID);
//        $picinfo = getPicInfo($picdetails);
//        if (!empty($picinfo['error'])) {
//            $ok = false;
//            $errorRPC = $picinfo['error'];
//        }

        try{
            $picdetails = getPicDetails($specimenID);
            $response1 = $client->request('POST', $picdetails['url'] . 'jacq-servlet/ImageServer', [
                'json'   => ['method' => 'listResources', 'params' => [$picdetails['key'], [$picdetails['filename']]], 'id' => 1],
                'verify' => false
            ]);
            $data = json_decode($response1->getBody()->getContents(), true);
            if (!empty($data['error'])) {
                $ok = false;
                $errorRPC = $data['error'];
            } elseif (empty($data['result'][0])) {
                $ok = false;
                $errorRPC = "FAIL: called '" . $picdetails['filename'] . "', returned empty result";
            } elseif ($data['result'][0] != $picdetails['filename']) {
                $ok = false;
                $errorRPC = "FAIL: called '" . $picdetails['filename'] . "', returned '" . $data['result'][0] . "'";
            }
        }
        catch( Exception $e ) {
            $ok = false;
            $errorRPC = $e->getMessage();
        }

        try {
            $response2 = $client->request('GET', "https://www.jacq.org/image.php?filename=$specimenID&method=thumbs", [
                'verify' => false
            ]);
            $data = json_decode($response2->getBody()->getContents(), true);
            if ($response2->getStatusCode() != 200) {
                $ok = false;
                $errorImage = "FAIL";
            } elseif (empty($data['pics'])) {
                $ok = false;
                $errorImage = "FAIL: no image";
            }
        }
        catch( Exception $e ) {
            $ok = false;
            $errorImage = $e->getMessage();
        }
        if ($ok) {
            $checks['ok'][] = ['source_id'  => $row['source_id_fk'],
                               'source'     => $row['img_coll_short'],
                               'specimenID' => $specimenID
                              ];
        } else {
            $checks['fail'][] = ['source_id'  => $row['source_id_fk'],
                                 'source'     => $row['img_coll_short'],
                                 'specimenID' => $specimenID,
                                 'errorRPC'   => $errorRPC,
                                 'errorImage' => $errorImage
                                ];
        }
    } else {
        $checks['noPicture'][] = ['source_id' => $row['source_id_fk'],
                                  'source'    => $row['img_coll_short']
                                 ];
    }
}
?>
<?php if (!empty($checks['fail'])): ?>
  <h3>Servers with errors</h3>
  <table>
    <tr><th>source-id</th><th>source</th><th>specimen-id</th><th>RPC</th><th>image</th></tr>
<?php
    foreach ($checks['fail'] as $row) {
        echo "<tr><td>" . $row['source_id'] . "</td><td>" . $row['source'] . "</td><td>" . $row['specimenID'] . "</td>";
        if (!empty($row['errorRPC'])) {
            echo "<td class='fail'>" . $row['errorRPC'] . "</td>";
        } else {
            echo "<td class='ok'>OK</td>";
        }
        if (!empty($row['errorImage'])) {
            echo "<td class='fail'>" . $row['errorImage'] . "</td>";
        } else {
            echo "<td class='ok'>OK</td>";
        }
        echo "<tr>\n";
    }
?>
  </table>
  <hr>
<?php endif; ?>
<?php if (!empty($checks['ok'])): ?>
  <h3>Servers without errors</h3>
  <table>
    <tr><th>source-id</th><th>source</th><th>specimen-id</th><th>RPC</th><th>image</th></tr>
<?php
    foreach ($checks['ok'] as $row) {
        echo "<tr><td>" . $row['source_id'] . "</td><td>" . $row['source'] . "</td><td>" . $row['specimenID'] . "</td>"
           . "<td class='ok'>OK</td>"
           . "<td class='ok'>OK</td>"
           . "<tr>\n";
    }
?>
  </table>
  <hr>
<?php endif; ?>
<?php if (!empty($checks['noPicture'])): ?>
  <h3>Servers with no available pictures</h3>
  <table>
    <tr><th>source-id</th><th>source</th></tr>
<?php
    foreach ($checks['noPicture'] as $row) {
        echo "<tr><td>" . $row['source_id'] . "</td><td>" . $row['source'] . "</td><tr>\n";
    }
?>
  </table>
<?php endif; ?>
</body>
</html>
