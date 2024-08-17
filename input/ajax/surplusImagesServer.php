<?php
session_start();
require("../inc/connect.php");
require __DIR__ . '/../vendor/autoload.php';

use Jaxon\Jaxon;
use Jaxon\Response\Response;
use GuzzleHttp\Client;

function init()
{
    $allServers = checkRight("admin");

    $selectData = "<select size='1' id='collection' onchange=\"jaxon_showLatestUpdate(jaxon.$('collection').value)\">\n"
                . "  <option value='0'></option>\n";

    $rows = dbi_query("SELECT id.imgserver_url, m.source_code, id.img_def_ID, id.source_id_fk 
                       FROM tbl_img_definition id
                        LEFT JOIN meta m ON m.source_id = id.source_id_fk 
                       WHERE id.img_def_ID IN 
                          (
                            SELECT server_id 
                            FROM herbar_pictures.djatoka_images 
                            GROUP BY server_id
                          )
                       ORDER BY m.source_code")
            ->fetch_all(MYSQLI_ASSOC);
    foreach ($rows as $row) {
        if ($row['source_id_fk'] == $_SESSION['sid'] || $allServers) {
            $selectData .= "  <option value='{$row['img_def_ID']}'>{$row['source_code']}, ID {$row['img_def_ID']} ({$row['imgserver_url']})</option>\n";
        }
    }

    $selectData .= "</select>\n";

    $response = new Response();
    $response->assign("drp_servers", "innerHTML", $selectData);
    $response->assign("totalSurplusImages", "innerHTML", '');
    $response->assign("surplusImageList", "innerHTML", '');
    $response->assign("cmdOutput", "innerHTML", '');
    return $response;
}

function listSurplusImages($server_id)
{
    global $_CONFIG;

    $server_id = intval($server_id);

    if (!$server_id) {  // received garbage
        return null;
    }
    $imgServer = dbi_query("SELECT imgserver_type, imgserver_url, iiif_capable, iiif_url FROM tbl_img_definition WHERE img_def_ID = '$server_id'")
                 ->fetch_assoc();
    $listData = array();
    $rows = dbi_query("SELECT filename 
                       FROM herbar_pictures.djatoka_images 
                       WHERE djatoka_images.server_id = $server_id 
                        AND specimen_ID IS NULL
                        AND hide = 0
                       ORDER BY filename")
            ->fetch_all(MYSQLI_ASSOC);
    if (!empty($rows)) {
        foreach ($rows as $row) {
            switch ($imgServer['imgserver_type']) {
                case 'djatoka':
                    $parts = explode("_", $row['filename']);
                    $HerbNummer = $parts[1] ?? '';
                    if ($imgServer['iiif_capable']) {
                        $url = "{$imgServer['iiif_url']}?manifest={$_CONFIG['JACQ_SERVICES']}iiif/createManifest/$server_id/{$row['filename']}";
                    } else {
                        $url = "{$imgServer['imgserver_url']}jacq-viewer/viewer.html?rft_id={$row['filename']}&identifiers={$row['filename']}";
                    }
                    $listData[] = "<a href='#' onclick='openinput(\"$url\", \"$HerbNummer\"); return false;'>{$row['filename']}</a>";
                    break;
            }
        }
    }

    $response = new Response();
    $response->assign("totalSurplusImages", "innerHTML", count($listData) . " image files");
    $response->assign("surplusImageList", "innerHTML", implode("<br>", $listData));
    $response->assign("cmdOutput", "innerHTML", '');
    $response->call("activateHighlighting()");
    return $response;
}

function showLatestUpdate($serverID)
{
    $serverID = intval($serverID);

    $latest = dbi_query("SELECT created_at 
                         FROM herbar_pictures.djatoka_images 
                         WHERE server_id = '$serverID' 
                         ORDER BY id DESC 
                         LIMIT 1")
              ->fetch_assoc();
    if (!empty($latest['created_at'])) {
        $text = "latest new file at {$latest['created_at']} "
              . "<button onclick='recheckServer($serverID)'>recheck server (slow)</button>";
    } else {
        $text = '';
    }

    $response = new Response();
    $response->assign("latestUpdate", "innerHTML", $text);
    $response->assign("totalSurplusImages", "innerHTML", '');
    $response->assign("surplusImageList", "innerHTML", '');
    return $response;
}

function checkServer($serverID)
{
    $response = new Response();

    exec("../surplusImagesCheckCmd.php -v " . intval($serverID), $output);

    $response->assign("cmdOutput", "innerHTML", implode("<br>", $output));
    $response->call("endRecheckServer($serverID)");
    return $response;

//    $serverID = intval($serverID);
//
//    $client   = new Client(['timeout' => 2]);
//
//    $imageDef = dbi_query("SELECT img_coll_short, imgserver_type, imgserver_url, `key`
//                           FROM `tbl_img_definition`
//                           WHERE `img_def_ID` = '$serverID'")
//                      ->fetch_assoc();
//    switch ($imageDef['imgserver_type']) {
//        case "djatoka":
//            try {
//                $clResponse = $client->request('POST', $imageDef['imgserver_url'] . 'jacq-servlet/ImageServer', [
//                                                'json' => ['method' => 'listResources',
//                                                           'params' => [$imageDef['key'], [$imageDef['img_coll_short'] . '%']],
//                                                           'id'     => '1'],
//                                                'verify' => false
//                                                ]);
//                $data = json_decode($clResponse->getBody()->getContents(), true, 512, JSON_INVALID_UTF8_SUBSTITUTE);
//                $ok = true;
//            } catch (Exception $e) {
//                $ok = false;
//                $errorRPC = $e->getMessage();
//            }
//            if (!$ok) {
//                $response->assign("totalSurplusImages", "innerHTML", $errorRPC);
//                $response->call("endRecheckServer($serverID)");
//                return $response;
//            }
//            foreach ($data['result'] as $filename) {
//                $filename_clean = dbi_escape_string($filename);
//                $res = dbi_query("SELECT id
//                                  FROM herbar_pictures.djatoka_images
//                                  WHERE filename = '$filename_clean'
//                                   AND server_id = $serverID");
//                if ($res->num_rows == 0) {
//                    dbi_query("INSERT IGNORE INTO herbar_pictures.djatoka_images (server_id, filename) VALUES ($serverID, '$filename_clean')");
//                }
//            }
//            $images = dbi_query("SELECT id, filename
//                                 FROM herbar_pictures.djatoka_images
//                                 WHERE server_id = $serverID
//                                  AND specimen_ID IS NULL")
//                      ->fetch_all(MYSQLI_ASSOC);
//            $sourceCache = array();
//            foreach ($images as $image) {
//                $parts = explode('_', $image['filename']);
//                if (!empty($parts[1])) {
//                    if (empty($sourceCache[$parts[0]])) {
//                        $row = dbi_query("SELECT source_id
//                                          FROM tbl_management_collections
//                                          WHERE coll_short_prj LIKE '{$parts[0]}'")
//                               ->fetch_assoc();
//                        if (!empty($row['source_id'])) {
//                            $sourceCache[$parts[0]] = $row['source_id'];
//                        }
//                    }
//
//                    if (!empty($sourceCache[$parts[0]])) {
//                        $specimen = dbi_query("SELECT s.specimen_ID
//                                               FROM tbl_specimens s, tbl_management_collections mc
//                                               WHERE s.collectionID = mc.collectionID
//                                                AND mc.source_id = {$sourceCache[$parts[0]]}
//                                                AND HerbNummer = '$parts[1]'")
//                                    ->fetch_assoc();
//                        if (!empty($specimen['specimen_ID'])) {
//                            dbi_query("UPDATE herbar_pictures.djatoka_images SET
//                                        source_id   = {$sourceCache[$parts[0]]},
//                                        specimen_ID = {$specimen['specimen_ID']}
//                                       WHERE id = {$image['id']}");
//                        }
//                    }
//                }
//            }
//            break;
//    }
//
//    $response->assign("totalSurplusImages", "innerHTML", '');
//    $response->assign("surplusImageList", "innerHTML", '');
//    $response->call("endRecheckServer($serverID)");
//    return $response;
}

/**
 * register all jaxon-functions in this file
 */
$jaxon = jaxon();
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "init");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "listSurplusImages");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "showLatestUpdate");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "checkServer");
$jaxon->processRequest();
