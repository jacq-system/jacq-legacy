<?php
session_start();
require_once "inc/functions.php";
require_once 'inc/imageFunctions.php';

/*
  image/specimenID|obs_specimenID|tab_specimenID|img_coll_short_HerbNummer[/download|thumb|resized|thumbs|show]/format[tiff/jpc]
 */

$filename   = filter_input(INPUT_GET, 'filename', FILTER_SANITIZE_STRING);
$specimenID = filter_input(INPUT_GET, 'sid', FILTER_SANITIZE_STRING);
$method     = filter_input(INPUT_GET, 'method', FILTER_SANITIZE_STRING);
$format     = filter_input(INPUT_GET, 'format', FILTER_SANITIZE_STRING);

$picdetails = getPicDetails($filename, $specimenID);

error_reporting(E_ALL);
if (!empty($picdetails['url'])) {
    switch ($method) {
        default:
            doRedirectDownloadPic($picdetails, $method, 0);
            break;
        case 'download':    // detail
            doRedirectDownloadPic($picdetails, $format, 0);
            break;
        case 'thumb':       // detail
            doRedirectDownloadPic($picdetails, $format, 1);
            break;
        case 'resized':     // create_xml.php
            doRedirectDownloadPic($picdetails, $format, 2);
            break;
        case 'europeana':   // NOTE: not supported on non-djatoka servers (yet)
            if (strtolower(substr($picdetails['requestFileName'], 0, 3)) == 'wu_' && checkPhaidra($picdetails['specimenID'])) {
                // Phaidra (only WU)
                $picdetails['imgserver_type'] = 'phaidra';
            } else {
                // Djatoka
                $picinfo = getPicInfo($picdetails);
                if (!empty($picinfo['pics'][0]) && !in_array($picdetails['originalFilename'], $picinfo['pics']))  {
                    $picdetails['originalFilename'] = $picinfo['pics'][0];
                }
            }
            doRedirectDownloadPic($picdetails, $format, 3);
            break;
        case 'nhmwthumb':   // NOTE: not supported on legacy image server scripts
            doRedirectDownloadPic($picdetails, $format, 4);
            break;
        case 'thumbs':      // unused
            header('Content-type: text/json');
            header('Content-type: application/json');
            echo json_encode(getPicInfo($picdetails));
            break;
        case 'show':        // detail, ajax/results.php
            doRedirectShowPic($picdetails);
            break;
    }
    exit;
} else {
    switch ($method) {
        default:
        case 'download':
        case 'thumb':
            $pic = 'images/404.png';
            header('Content-Type: image/png');
            header('Content-Length: ' . filesize($pic));
            readfile($pic);
            break;
        case 'thumbs':
            header('Content-type: text/json');
            header('Content-type: application/json');
            echo json_encode(array('error' => 'not found'));
            break;
        case 'show':
            echo 'not found';
            break;
    }
}


////////// functions //////////

function doRedirectShowPic($picdetails)
{
    if ($picdetails['imgserver_type'] == 'djatoka') {
        // Get additional identifiers (if available)
        $picinfo = getPicInfo($picdetails);
        $identifiers = implode(',', $picinfo['pics']);

        // Construct URL to viewer
        if (in_array($picdetails['originalFilename'], $picinfo['pics'])) {
            // the filename is in the list returend by the picture-server
            $url = $picdetails['url'] . '/jacq-viewer/viewer.html?rft_id=' . $picdetails['originalFilename'] . '&identifiers=' . $identifiers;
        } elseif (!empty($identifiers)) {
            // the filename is not in the list, but there is a list
            $url = $picdetails['url'] . '/jacq-viewer/viewer.html?rft_id=' . $picinfo['pics'][0] . '&identifiers=' . $identifiers;
        } else {
            // the picture-server didn't respond or the returned list is empty, so we guess a name...
            $url = $picdetails['url'] . '/jacq-viewer/viewer.html?rft_id=' . $picdetails['originalFilename'] . '&identifiers=' . $picdetails['originalFilename'];
        }
    } else if ($picdetails['imgserver_type'] == 'bgbm') {
        // Construct URL to viewer
        $url = $picdetails['url'] . '/jacq_image.cfm?Barcode=' . $picdetails['originalFilename'];
    } else if ($picdetails['imgserver_type'] == 'baku') {  // depricated
        // Get additional identifiers (if available)
        //$picinfo = getPicInfo($picdetails);
        //$identifiers = implode($picinfo['pics'], ',');
        // Construct URL to viewer

        $url = $picdetails['key'];
    } else {                                               // depricated
        $q = '';
        foreach ($_GET as $k => $v) {
            if (in_array($k, array('method', 'filename', 'format')) === false) {
                $q .= "&{$k}=" . rawurlencode($v);
            }
        }
        $url = $picdetails['url'] . 'img/imgBrowser.php?name=' . $picdetails['requestFileName'] . $q;
    }

    // Redirect to new location
    header("location: " . cleanURL($url));
}

function doRedirectDownloadPic($picdetails, $format, $thumb = 0)
{
    // Setup default mime-type & file-extension
    $mime = 'image/jpeg';
    $fileExt = 'jpg';
    $downloadPic = true;

    // Check if we are using djatoka
    if ($picdetails['imgserver_type'] == 'djatoka') {
        // Check requested format
        switch ($format) {
            case 'jpeg2000':
                $mime = 'image/jp2';  $fileExt = 'jp2'; break;
            case'tiff':
                $mime = 'image/tiff'; $fileExt = 'tif'; break;
            default:
                $mime = 'image/jpeg'; $fileExt = 'jpg'; break;
        }
        // Default scaling is 50%
        $scale = '0.5';

        // Check if we need a thumbnail
        if ($thumb != 0) {

            if ($thumb == 2) {          // Thumbnail for kulturpool
                $scale = '0,1300';
            } else if ($thumb == 3) {   // thumbnail for europeana
//                $downloadPic = false;
                $scale = '1200,0';
            } else if ($thumb == 4) {   // thumbnail for nhmw digitization project
                $scale = '160,0';
            } else {                    // Default thumbnail
                $scale = '160,0';
            }
        }

        // Construct URL to djatoka-resolver
        $url = cleanURL($picdetails['url']
             .          "adore-djatoka/resolver?url_ver=Z39.88-2004&rft_id={$picdetails['originalFilename']}"
             .          "&svc_id=info:lanl-repo/svc/getRegion&svc_val_fmt=info:ofi/fmt:kev:mtx:jpeg2000&svc.format={$mime}&svc.scale={$scale}");

    } else if ($picdetails['imgserver_type'] == 'phaidra') {  // special treatment for PHAIDRA (WU only), for europeana only
        $ch = curl_init("https://app05a.phaidra.org/manifests/WU" . substr($picdetails['requestFileName'], 3));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $curl_response = curl_exec($ch);
        curl_close($ch);
        $decoded = json_decode($curl_response, true);
        $phaidraImages = array();
        foreach ($decoded['sequences'] as $sequence) {
            foreach ($sequence['canvases'] as $canvas) {
                foreach ($canvas['images'] as $image) {
                    $phaidraImages[] = $image['resource']['service']['@id'];
                }
            }
        }
        if (!empty($phaidraImages)) {
            switch ($thumb) {
                case 0:
                    $scale = "pct:25";  // about 50%
                    break;
                case 3:
                    $scale = "1200,";   // europeana
                    break;
                default:
                    $scale = "160,";    // default thumbnail
            }
            $url = $phaidraImages[0] . "/full/$scale/0/default.jpg";
        } else {
            $url = "";
        }
    } else if ($picdetails['imgserver_type'] == 'bgbm') {
        //... Check if we are using djatoka = 2 (Berlin image server)
        // Check requested format
        switch ($format) {
            case 'jpeg2000':
                $mime = 'image/jp2';  $fileExt = 'jp2'; break;
            case'tiff':
                $mime = 'image/tiff'; $fileExt = 'tif'; break;
            default:
                $mime = 'image/jpeg'; $fileExt = 'jpg'; break;
        }

        // Construct URL to Berlin Server
        // Remove hyphens
        $fp = fopen('http://ww2.bgbm.org/rest/herb/thumb/' . $picdetails['filename'], "r");
        $response = "";
        while ($row = fgets($fp)) {
            $response .= trim($row) . "\n";
        }
        $response_decoded = json_decode($response, true);
        //$url = $picdetails['url'].'images'.$response_decoded['value'];
        $url = cleanURL('https://image.bgbm.org/images/herbarium/' . $response_decoded['value']);

    } else if ($picdetails['imgserver_type'] == 'baku') {           // depricated
    //... Check if we are using djatoka = 3 (Baku image server)
        // Check requested format
        switch ($format) {
            case 'jpeg2000':
                $mime = 'image/jp2';  $fileExt = 'jp2'; break;
            case'tiff':
                $mime = 'image/tiff'; $fileExt = 'tif'; break;
            default:
                $mime = 'image/jpeg'; $fileExt = 'jpg'; break;
        }

        $url = cleanURL($picdetails['url'] . $picdetails['originalFilename']);

    } else {                                                        // depricated
        // ... if not fall back to old system
        switch ($format) {
            case'tiff':
                $urlExt = '&type=1';
                $mime = 'image/tiff';
                $fileExt = 'tif';
                break;
            default:
                $urlExt = '';
                $mime = 'image/jp2';
                $fileExt = 'jp2';
                break;
        }
        $fileurl = 'downPic.php';
        if ($thumb != 0) {
            $mime = 'image/jpeg';
            $fileExt = 'jpg';
            if ($thumb == 2) {
                $fileurl = 'mktn_kp.php';
            }
            else {
                $fileurl = 'mktn.php';
            }
        }

        $q = '';
        foreach ($_GET as $k => $v) {
            if (in_array($k, array('method', 'filename', 'format')) === false) {
                $q .= "&{$k}=" . rawurlencode($v);
            }
        }
        $url = cleanURL("{$picdetails['url']}/img/{$fileurl}?name={$picdetails['requestFileName']}{$urlExt}{$q}");
    }

    // Send correct headers
    header('Content-Type: ' . $mime);
    if ($downloadPic) {
        header('Content-Disposition: attachment; filename="' . $picdetails['requestFileName'] . '.' . $fileExt . '"');
    }
    readfile($url);
}

function cleanURL($url)
{
    return preg_replace('/([^:])\/\//', '$1/', $url);
}
