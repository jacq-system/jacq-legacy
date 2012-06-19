<?php

@session_start();
require_once("./inc/functions.php");

/*
  image/specimenID|obs_specimenID|tab_specimenID|img_coll_short_HerbNummer[/download|thumb|resized|thumbs|show]/format[tiff/jpc]
 */

$_OPTIONS['key'] = 'DKsuuewwqsa32czucuwqdb576i12';

$q = array();

//isIncluded: set in detail.php
if (!isset($image_isIncluded)) {
    #if($_SERVER['PATH_INFO'][0]=='/')$_SERVER['PATH_INFO']=substr($_SERVER['PATH_INFO'],1);
    #@list($filename,$method,$format)=explode('/',$_SERVER['PATH_INFO']);
    $filename = isset($_GET['filename']) ? $_GET['filename'] : '';
    $method = isset($_GET['method']) ? $_GET['method'] : '';
    $format = isset($_GET['format']) ? $_GET['format'] : '';
    $q = getQuery();
    getResult($filename, $method, $format);
}

function getResult($filename, $method, $format) {
    $picdetails = getPicDetails($filename);

    $debug = 0;
    error_reporting(E_ALL);
    if ($debug) {
        print_r($picdetails);
    }
    if (isset($picdetails['url']) && $picdetails['url'] !== false) {
        switch ($method) {
            default:
                doRedirectDownloadPic($picdetails, $method, 0);
                break;
            case 'download':
                doRedirectDownloadPic($picdetails, $format, 0);
                break;
            case 'thumb':
                doRedirectDownloadPic($picdetails, $format, 1);
                break;
            case 'resized':
                doRedirectDownloadPic($picdetails, $format, 2);
                break;
            case 'thumbs':
                header('Content-type: text/json');
                header('Content-type: application/json');
                echo json_encode(getPicInfo($picdetails));
                break;
            case 'show':
                doRedirectShowPic($picdetails);
                break;
        }
        exit;
    } else {
        switch ($method) {
            default:
            case 'download':
            case 'thumb':
                imgError('not found');
            case 'thumbs':
                header('Content-type: text/json');
                header('Content-type: application/json');
                echo json_encode(jsonError('not found'));
            case 'show':
                textError('not found');
        }
    }
}

function getPicInfo($picdetails) {
    global $q, $debug;

    if ($picdetails['is_djatoka'] == '1') {
        // Construct URL to servlet
        $url = $picdetails['url'] . '/jacq-servlet/ImageServer';
        
        // Prepare json-rpc conform request structure
        $jsonrpc_request = json_encode(array(
            'id' => 1234,
            'method' => 'listSpecimenImages',
            'params' => array( $picdetails['key'], $picdetails['specimenID'], $picdetails['filename'] )
        ));
        
        // Prepare the context for the HTTP request
        $opts = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type: application/json',
                'content' => $jsonrpc_request
            )
        );
        $context = stream_context_create($opts);
        
        // Finally try to reach the djatoka server and ask for details
        if( ($fp = fopen($url, 'r', false, $context)) ) {
            $response = '';
            while ($row = fgets($fp)) {
                $response.=trim($row) . "\n";
            }
            
            $response = json_decode($response, true);
            $response = array(
                'output' => '',
                'pics' => $response['result']
            );
        } else {
            return jsonError('Unable to connect to ' . $url);
        }
    } else {
        global $_OPTIONS;
        $url = "{$picdetails['url']}/detail_server.php?key={$_OPTIONS['key']}&ID={$picdetails['specimenID']}{$q}";

        $response = @file_get_contents($url, "r");
        $response = @unserialize($response);
    }
    if ($debug) {
        p($response);
        exit;
    }
    if (!is_array($response)) {
        return jsonError("couldn't get information");
    }
    return $response;
}

function doRedirectShowPic($picdetails) {
    global $q, $debug;

    if ($picdetails['is_djatoka'] == '1') {
        // Get additional identifiers (if available)
        $picinfo = getPicInfo($picdetails);
        $identifiers = implode($picinfo['pics'], ',');
        
        // Construct URL to viewer
        $url = $picdetails['url'] . '/jacq-viewer/viewer.html?rft_id=' . $picdetails['originalFilename'] . '&identifiers=' . $identifiers;
    } else {
        $url = $picdetails['url'] . '/img/imgBrowser.php?name=' . $picdetails['requestFileName'] . $q;
    }
    if ($debug) {
        p($url);
        exit;
    }
    $url = cleanURL($url);
    
    // Redirect to new location
    header("location: {$url}");
}

function doRedirectDownloadPic($picdetails, $format, $thumb = 0) {
    global $q, $debug;
    // Setup default mime-type & file-extension
    $mime = 'image/jpeg';
    $fileExt = 'jpg';

    // Check if we are using djatoka
    if ($picdetails['is_djatoka'] == '1') {
        // Check requested format
        switch ($format) {
            case 'jpeg2000':
                $format = 'image/jp2';
                $fileExt = 'jp2';
                break;
            case'tiff':
                $format = 'image/tiff';
                $fileExt = 'tif';
                break;
            default:
                $format = 'image/jpeg';
                $fileExt = 'jpg';
                break;
        }
        // Default scaling is 50%
        $scale = '0.5';
        $mime = $format;

        // Check if we need a thumbnail
        if ($thumb != 0) {
            // Thumbnail for kulturpool
            if( $thumb == 2 ) {
                $scale = '0,1300';
            }
            // Default thumbnail
            else {
                $scale = '160,0';
            }
        }

        // Construct URL to djatoka-resolver
        $url = $picdetails['url'] . "/adore-djatoka/resolver?url_ver=Z39.88-2004&rft_id={$picdetails['requestFileName']}&svc_id=info:lanl-repo/svc/getRegion&svc_val_fmt=info:ofi/fmt:kev:mtx:jpeg2000&svc.format={$format}&svc.scale={$scale}";
    }
    // ... if not fall back to old system
    else {
        switch ($format) {
            case'tiff':
                $format = '&type=1';
                $mime = 'image/tiff';
                $fileExt = 'tif';
                break;
            default:
                $mime = 'image/jp2';
                $fileExt = 'jp2';
                $format = '';
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

        $url = "{$picdetails['url']}/img/{$fileurl}?name={$picdetails['requestFileName']}{$format}{$q}";
    }
    $url = cleanURL($url);
    if ($debug) {
        p($url);
        exit;
    }
    
    // Send correct headers
    header( 'Content-Type: ' . $mime );
    header( 'Content-Disposition: attachment; filename="' . $picdetails['requestFileName'] . '.' . $fileExt . '"' );
    readfile($url);
    
    // Redirect to image download
    //header("location: {$url}");
}

// request: can be specimen ID or filename
function getPicDetails($request) {
    global $debug, $_CONFIG;

    $specimenID = 0;
    $originalFilename = null;

    //specimenid
    if (is_numeric($request)) {
        $specimenID = $request;
        //tabs..
    } else if (strpos($request, 'tab_') !== false) {
        $result = preg_match('/tab_((?P<specimenID>\d+)[\._]*(.*))/', $request, $matches);
        if ($result == 1) {
            $specimenID = $matches['specimenID'];
        }
        // obs digital_image_obs
    } else if (strpos($request, 'obs_') !== false) {
        $result = preg_match('/obs_((?P<specimenID>\d+)[\._]*(.*))/', $request, $matches);
        if ($result == 1) {
            $specimenID = $matches['specimenID'];
        }
        // filename
    } else {
        $originalFilename = $request;
        $matches = array();
        // Remove file-extension
        if (preg_match('/([^\.]+)/', $request, $matches) > 0) {
            $originalFilename = $matches[1];
        }
        
        // Extract HerbNummer and coll_short_prj from filename and use it for finding the specimen_ID
        if( preg_match( '/^([^_]+)_([^_]+)/', $originalFilename, $matches ) > 0 ) {
            // Extract HerbNummer and construct alternative version
            $HerbNummer = $matches[2];
            $HerbNummerAlternative = substr($HerbNummer, 0, 4) . '-' . substr($HerbNummer, 4);
            
            // Find entry in specimens table and return specimen ID for it
            $sql = "
                SELECT
                s.`specimen_ID`
                FROM
                `" . $_CONFIG['DATABASES']['OUTPUT']['db'] . "`.`tbl_specimens` s
                LEFT JOIN `" . $_CONFIG['DATABASES']['OUTPUT']['db'] . "`.`tbl_management_collections` mc
                ON mc.`collectionID` = s.`collectionID`
                WHERE (s.`HerbNummer` = '" . mysql_real_escape_string($HerbNummer) . "' OR s.`HerbNummer` = '" . mysql_real_escape_string($HerbNummerAlternative) . "' ) AND mc.`coll_short_prj` = '" . mysql_real_escape_string($matches[1]) . "'
                ";

            $result = mysql_query($sql);
            if (mysql_num_rows($result) > 0) {
                $row = mysql_fetch_array($result, MYSQL_ASSOC);
                $specimenID = $row['specimen_ID'];
            }
        }
    }

    $sql = "
            SELECT
            id.`imgserver_IP`,
            id.`img_service_directory`,
            id.`is_djatoka`,
            id.`HerbNummerNrDigits`,
            id.`key`,
            mc.`coll_short_prj`,
            s.`HerbNummer`
            FROM
            `" . $_CONFIG['DATABASES']['OUTPUT']['db'] . "`.`tbl_specimens` s
            LEFT JOIN `" . $_CONFIG['DATABASES']['OUTPUT']['db'] . "`.`tbl_management_collections` mc
            ON mc.`collectionID` = s.`collectionID`
            LEFT JOIN `" . $_CONFIG['DATABASES']['OUTPUT']['db'] . "`.`tbl_img_definition` id
            ON id.`source_id_fk` = mc.`source_id`
            WHERE s.`specimen_ID` = '" . mysql_real_escape_string($specimenID) . "'
            ";

    if ($debug) {
        print_r($sql);
    }
    
    // Fetch information for this image
    $result = mysql_query($sql);
    if (mysql_num_rows($result) > 0) {
        $row = mysql_fetch_array($result, MYSQL_ASSOC);
        if ($debug) {
            print_r($row);
        }
        $url = 'http://' . $row['imgserver_IP'];
        $url .= ($row['img_service_directory']) ? '/' . $row['img_service_directory'] . '/' : '';
        
        // Remove hyphens
        $HerbNummer = str_replace('-', '', $row['HerbNummer']);

        // Construct clean filename
        $filename = sprintf( "%s_%0" . $row['HerbNummerNrDigits'] . ".0f", $row['coll_short_prj'], $HerbNummer );
        
        // Set original file-name if we didn't pass one (required for djatoka)
        // (required for pictures with suffixes)
        if( $originalFilename == null ) $originalFilename = $filename;

        return array(
            'url' => $url,
            'requestFileName' => $request,
            'originalFilename' => $originalFilename,
            'filename' => $filename,
            'specimenID' => $specimenID,
            'is_djatoka' => $row['is_djatoka'],
            'key' => $row['key']
        );
    }
    return false;
}

function jsonError($msg = '') {
    return array('error' => $msg);
}

function textError($msg = '') {
    echo "{$msg}";
    exit;
}

function imgError($msg = '') {
    switch ($msg) {
        default: case 'not found':$pic = 'images/404.png';
            break;
    }
    Header('Content-Type: image/png');
    Header('Content-Length: ' . filesize($pic));
    @readfile($pic);
    exit;
}

function cleanURL($url) {
    $url = preg_replace('/([^:])\/\//', '$1/', $url);
    return $url;
}

function getQuery() {
    $qstr = '';
    foreach ($_GET as $k => $v) {
        if (in_array($k, array('method', 'filename', 'format')) === false) {
            $qstr.="&{$k}=" . rawurlencode($v);
        }
    }
    return $qstr;
}

function p($var) {
    echo "<pre>" . print_r($var, 1) . "</pre>";
}
