<?php
require("inc/functions.php");
require_once('inc/imageFunctions.php');

/** @var mysqli $dbLink */

use GuzzleHttp\Client;

?><!DOCTYPE html>
<html lang="en">
<head>
  <title>JACQ - Virtual Herbaria</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <style>
    table, th, td {
      border: 1px solid;
    }
    th, td {
        text-align: center;
        padding: 2px 1ex;
    }
    th {
        white-space: nowrap;
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
  <h1>check all Djatoka installations at <?php echo date(DATE_RFC822); ?></h1>
<?php
$checks = array('ok' => array(), 'fail' => array(), 'noPicture' => array());
$client = new Client(['timeout' => 8]);

$constraint = ' AND source_id_fk != 1';
if (!empty($_GET['source'])) {
    if (is_numeric($_GET['source'])) {
        $constraint = " AND source_ID_fk = " . intval($_GET['source']);
    } else {
        $stmt = $dbLink->prepare("SELECT source_id
                                  FROM meta
                                  WHERE source_code LIKE ?");
        $stmt->bind_param('s', $_GET['source']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_array(MYSQLI_ASSOC);
        if (!empty($row['source_id'])) {
            $constraint = " AND source_ID_fk = " . $row['source_id'];
        }
    }
}
$rows = $dbLink->query("SELECT source_id_fk, img_coll_short
                        FROM tbl_img_definition
                        WHERE imgserver_type = 'djatoka'
                         $constraint
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
        $picdetails = getPicDetails($specimenID);
        $filename   = $picdetails['originalFilename'];

        try{
            $response1 = $client->request('POST', $picdetails['url'] . 'jacq-servlet/ImageServer', [
                'json'   => ['method' => 'listResources',
                             'params' => [$picdetails['key'],
                                            [ $picdetails['filename'],
                                              $picdetails['filename'] . "_%",
                                              $picdetails['filename'] . "A",
                                              $picdetails['filename'] . "B",
                                              "tab_" . $picdetails['specimenID'],
                                              "obs_" . $picdetails['specimenID'],
                                              "tab_" . $picdetails['specimenID'] . "_%",
                                              "obs_" . $picdetails['specimenID'] . "_%"
                                            ]
                                         ],
                             'id'     => 1
                            ],
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
                $filename = $data['result'][0];
            }
        }
        catch( Exception $e ) {
            $ok = false;
            $errorRPC = $e->getMessage();
        }

        try {
            // Construct URL to djatoka-resolver
            $url = preg_replace('/([^:])\/\//', '$1/', $picdetails['url'] . "adore-djatoka/resolver"
                                                     . "?url_ver=Z39.88-2004"
                                                     . "&rft_id=$filename"
                                                     . "&svc_id=info:lanl-repo/svc/getRegion"
                                                     . "&svc_val_fmt=info:ofi/fmt:kev:mtx:jpeg2000"
                                                     . "&svc.format=image/jpeg"
                                                     . "&svc.scale=0.1");
            $response2 = $client->request('GET', $url, [
                'verify'      => false,
                'http_errors' => false
            ]);
//            $data = json_decode($response2->getBody()->getContents(), true);
            $statusCode = $response2->getStatusCode();
            if ($statusCode != 200) {
                $ok = false;
                if ($statusCode == 404) {
                    $errorImage = "FAIL: <404> Image not found";
                } elseif ($statusCode == 500) {
                    $errorImage = "FAIL: <500> Server Error";
                } else {
                    $errorImage = "FAIL: Status Code <$statusCode>";
                }
            }
        }
        catch( Exception $e ) {
            $ok = false;
            $errorImage = htmlentities($e->getMessage());
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
    <tr><th>source (id)</th><th>specimen-id</th><th>RPC</th><th>image</th></tr>
<?php
    foreach ($checks['fail'] as $row) {
        echo "<tr><td>" . $row['source'] . " (" . $row['source_id'] . ")</td><td>" . $row['specimenID'] . "</td>";
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
    <tr><th>source (id)</th><th>specimen-id</th><th>RPC</th><th>image</th></tr>
<?php
    foreach ($checks['ok'] as $row) {
        echo "<tr><td>" . $row['source'] . " (" . $row['source_id'] . ")</td><td>" . $row['specimenID'] . "</td>"
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
    <tr><th>source (id)</th></tr>
<?php
    foreach ($checks['noPicture'] as $row) {
        echo "<tr><td>" . $row['source'] . " (" . $row['source_id'] . ")</td><tr>\n";
    }
?>
  </table>
<?php endif; ?>
<p>To scan just a single source, add either the parameter "?source=&lt;id&gt;" or "?source=&lt;source&gt;" to the URL.</p>
</body>
</html>
