#!/usr/bin/php -qC
<?php
require_once __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;
use Jacq\DbAccess;

if (in_array("-h", $argv) || in_array("--help", $argv) || count($argv) == 1) {
    echo $argv[0] . " x             scan image Server with ID x for standalone images\n"
       . $argv[0] . " -h  --help    this explanation\n";
    die();
}

$server_id = intval($argv[1]);

$dbLnk = DbAccess::ConnectTo('INPUT');

$imageDef = $dbLnk->query("SELECT imgserver_type, imgserver_url, `key`
                           FROM `tbl_img_definition`
                           WHERE `img_def_ID` = $server_id")
                  ->fetch_assoc();
if (empty($imageDef)) {
    echo "unknown server-ID\n";
    die();
}
switch ($imageDef['imgserver_type']) {
    case "djatoka":
        $client = new Client(['timeout' => 2]);

        try {
            $response = $client->request('POST', $imageDef['imgserver_url'] . 'jacq-servlet/ImageServer', [
                                            'json' => ['method' => 'listDjatokaImages', 'params' => [$imageDef['key']], 'id' => '1'],
                                            'verify' => false
                                        ]);
            $data = json_decode($response->getBody()->getContents(), true, 512, JSON_INVALID_UTF8_SUBSTITUTE);
            $ok = true;
        } catch (Exception $e) {
            $ok = false;
            $errorRPC = $e->getMessage();
        }

        if (!$ok) {
            echo $errorRPC;
            die();
        }

//        $cacheFilename = $counterFilename = array();
//        foreach ($data['result'] as $filename) {
//            $filename_upper = strtoupper($filename);
//            if (empty($cacheFilename[$filename_upper])) {
//                $cacheFilename[$filename_upper] = 1;
//            } else {
//                if (empty($counterFilename[$filename_upper])) {
//                    $counterFilename[$filename_upper] = 2;
//                } else {
//                    $counterFilename[$filename_upper]++;
//                }
//            }
//        }
//        var_export($counterFilename);

//        $sql_parts = array();
//        foreach ($data['result'] as $filename) {
//            $sql_parts[] = "($server_id, \"$filename\")";
//        }
//        $dbLnk->query("INSERT IGNORE INTO herbar_pictures.djatoka_images (server_id, filename) VALUES " . implode(',', $sql_parts));

        foreach ($data['result'] as $filename) {
            $filename_clean = $dbLnk->real_escape_string($filename);
            $res = $dbLnk->query("SELECT id
                                  FROM herbar_pictures.djatoka_images
                                  WHERE filename = '$filename_clean'
                                   AND server_id = $server_id");
            if ($res->num_rows == 0) {
                $dbLnk->query("INSERT IGNORE INTO herbar_pictures.djatoka_images (server_id, filename) VALUES ($server_id, '$filename_clean')");
            }
        }

        $images = $dbLnk->query("SELECT id, filename FROM herbar_pictures.djatoka_images WHERE server_id = $server_id AND specimen_ID IS NULL")
                        ->fetch_all(MYSQLI_ASSOC);
        $sourceCache = array();
        foreach ($images as $image) {
            $parts = explode('_', $image['filename']);
            if (!empty($parts[1])) {
                if (empty($sourceCache[$parts[0]])) {
                    $row = $dbLnk->query("SELECT source_id FROM tbl_management_collections WHERE coll_short_prj LIKE '$parts[0]'")
                                 ->fetch_assoc();
                    if (!empty($row['source_id'])) {
                        $sourceCache[$parts[0]] = $row['source_id'];
                    }
                }
                if (!empty($sourceCache[$parts[0]])) {
                    $specimen = $dbLnk->query("SELECT s.specimen_ID 
                                               FROM tbl_specimens s, tbl_management_collections mc
                                               WHERE s.collectionID = mc.collectionID
                                                AND mc.source_id = {$sourceCache[$parts[0]]}
                                                AND HerbNummer = '$parts[1]'")
                                        ->fetch_assoc();
                    if (!empty($specimen['specimen_ID'])) {
                        $dbLnk->query("UPDATE herbar_pictures.djatoka_images SET 
                                        source_id = {$sourceCache[$parts[0]]},
                                        specimen_ID = {$specimen['specimen_ID']} 
                                       WHERE id = {$image['id']}");
                    }
                }
            }
        }
        echo "ok\n";
        break;
    default:
        echo "wrong server type\n";
        break;
}
