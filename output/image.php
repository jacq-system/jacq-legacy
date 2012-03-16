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
        // JSON RPC
        $url = "{$picdetails['url']}/FReuD-Servlet/ImageScan?requestfilename={$picdetails['requestFileName']}&specimenID={$picdetails['specimenID']}";

        $request = json_encode(array(
            'filename' => $picdetails['filename'],
            'specimenID' => $picdetails['specimenID']
                ));

        // performs the HTTP POST
        $opts = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type: application/json',
                'content' => $request
            )
        );

        $context = stream_context_create($opts);
        if ($fp = fopen($url, 'r', false, $context)) {
            $response = '';
            while ($row = fgets($fp)) {
                $response.=trim($row) . "\n";
            }
            $response = json_decode($response, true);
            $response = array(
                $output => '',
                $pics => $response
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
        $url = "{$picdetails['url']}/viewer.html?requestfilename={$picdetails['requestFileName']}&specimenID={$picdetails['specimenID']}&herbarnumber={$picdetails['filename']}";
    } else {
        $url = "{$picdetails['url']}/img/imgBrowser.php?name={$picdetails['requestFileName']}{$q}";
    }
    if ($debug) {
        p($url);
        exit;
    }
    $url = cleanURL($url);
    if (url_exists($url)) {
        header("location: {$url}");
    } else {
        textError("couldn't find url: {$url}");
    }
}

function doRedirectDownloadPic($picdetails, $format, $thumb = 0) {
    global $q, $debug;

    if ($picdetails['is_djatoka'] == '1') {
        switch ($format) {
            default:case'':case 'jpeg':
                $format = 'image/jpeg';
                break;
            case 'jpeg2000':
                $format = 'image/jpeg';
                break;
            case'tiff':
                $format = 'image/tiff';
                break;
        }
        $scale = '1.0';

        if ($thumb != 0) {
            if ($thumb == 1) {
                $scale = '225'; //px??todo
            }
            if ($thumb == 1) {
                $scale = '1300';
            }
        }

        $url = "{$picdetails['url']}/resolver?url_ver=Z39.88-2004&rft_id={$picdetails['requestFileName']}&svc_id=info:lanl-repo/svc/getRegion&svc_val_fmt=info:ofi/fmt:kev:mtx:jpeg2000&svc.format={$format}&svc.level=1&svc.rotate=0&svc.scale={$scale}";
    } else {
        switch ($format) {
            default:case'':case 'jpeg2000':
                $format = '';
                break;
            case'tiff':
                $format = '&type=1';
                break;
        }
        $fileurl = 'downPic.php';
        if ($thumb != 0) {
            if ($thumb == 1) {
                $fileurl = 'mktn.php';
            }
            if ($thumb == 2) {
                $fileurl = 'mktn_kp.php';
            }
        }

        $url = "{$picdetails['url']}/img/{$fileurl}?name={$picdetails['requestFileName']}{$format}{$q}";
    }
    $url = cleanURL($url);
    if ($debug) {
        p($url);
        exit;
    }
    header("location: {$url}");
}

// request: can be specimen ID or filename
function getPicDetails($request) {
    global $debug, $_CONFIG;

    $specimenID = 0;

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
        $file = $request;
        $matches = array();
        if (preg_match('/([^\.]+)/', $request, $matches) > 0) {
            $file = $matches[1];
        }
        
        // Extract HerbNummer and coll_short_prj from filename and use it for finding the specimen_ID
        if( preg_match( '/(\S+)_(\S+)/', $file, $matches ) > 0 ) {
            // Find entry in specimens table and return specimen ID for it
            $sql = "
                SELECT
                s.`specimen_ID`
                FROM
                `" . $_CONFIG['DATABASES']['OUTPUT']['db'] . "`.`tbl_specimens` s
                LEFT JOIN `" . $_CONFIG['DATABASES']['OUTPUT']['db'] . "`.`tbl_management_collections` mc
                ON mc.`collectionID` = s.`collectionID`
                WHERE s.`HerbNummer` = '" . mysql_real_escape_string($matches[2]) . "' AND mc.`coll_short_prj` = '" . mysql_real_escape_string($matches[1]) . "'
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
        $url = "http://" . $row['imgserver_IP'] . "/" . $row['img_service_directory'] . "/";

        return array(
            'url' => $url,
            'requestFileName' => $request,
            'filename' => sprintf( "%s_%0" . $row['HerbNummerNrDigits'] . "d", $row['coll_short_prj'], $row['HerbNummer'] ),
            'specimenID' => $specimenID,
            'is_djatoka' => $row['is_djatoka']
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

function url_exists($url) {
    $opts = array(
        'http' => array(
            'method' => 'POST',
            'header' => 'Content-type: application/json',
            'timeout' => 20,
        )
            //timeout..
    );
    $context = stream_context_create($opts);
    if ($fp = @fopen($url, 'r', false, $context)) {
        return true;
    }

    return false;
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
