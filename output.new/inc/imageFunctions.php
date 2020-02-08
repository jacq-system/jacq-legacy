<?php
// can only be used if inc/functions.php is included beforehand

/**
 * get all details of a given picture
 *
 * @global array $_CONFIG all configuration parameters given in variables.php
 * @global mysqli $dbLink link to output-database
 * @param mixed $request either the specimen_ID or the wanted filename
 * @return array|bool either the wanted data or false if anything went wrong
 */
function getPicDetails($request)
{
    global $_CONFIG, $dbLink;

    $specimenID = 0;
    $originalFilename = null;

    //specimenid
    if (is_numeric($request)) {
        // request is numeric
        $specimenID = $request;
    } else if (strpos($request, 'tab_') !== false) {
        // request is a string and contains "tab_" at the beginning
        $result = preg_match('/tab_((?P<specimenID>\d+)[\._]*(.*))/', $request, $matches);
        if ($result == 1) {
            $specimenID = $matches['specimenID'];
        }
        $originalFilename = $request;
    } else if (strpos($request, 'obs_') !== false) {
        // request is a string and contains "obs_" at the beginning
        $result = preg_match('/obs_((?P<specimenID>\d+)[\._]*(.*))/', $request, $matches);
        if ($result == 1) {
            $specimenID = $matches['specimenID'];
        }
        $originalFilename = $request;
    } else {
        // anything else
        $originalFilename = $request;
        $matches = array();
        // Remove file-extension
        if (preg_match('/([^\.]+)/', $request, $matches) > 0) {
            $originalFilename = $matches[1];
        }

        // Extract HerbNummer and coll_short_prj from filename and use it for finding the specimen_ID
        if (preg_match('/^([^_]+)_([^_]+)/', $originalFilename, $matches) > 0) {
            // Extract HerbNummer and construct alternative version
            $HerbNummer = $matches[2];
            $HerbNummerAlternative = substr($HerbNummer, 0, 4) . '-' . substr($HerbNummer, 4);

            // Find entry in specimens table and return specimen ID for it
            $sql = "SELECT s.`specimen_ID`
                    FROM `" . $_CONFIG['DATABASES']['OUTPUT']['db'] . "`.`tbl_specimens` s
                     LEFT JOIN `" . $_CONFIG['DATABASES']['OUTPUT']['db'] . "`.`tbl_management_collections` mc ON mc.`collectionID` = s.`collectionID`
                    WHERE (s.`HerbNummer` = '" . $dbLink->real_escape_string($HerbNummer) . "' OR s.`HerbNummer` = '" . $dbLink->real_escape_string($HerbNummerAlternative) . "' )
                     AND mc.`coll_short_prj` = '" . $dbLink->real_escape_string($matches[1]) . "'";
            $result = $dbLink->query($sql);
            if ($result->num_rows > 0) {
                $row = $result->fetch_array(MYSQLI_ASSOC);
                $specimenID = $row['specimen_ID'];
            }
        }
    }

    $sql = "SELECT id.`imgserver_Prot`, id.`imgserver_IP`, id.`imgserver_type`, id.`img_service_directory`, id.`is_djatoka`, id.`HerbNummerNrDigits`, id.`key`,
                   mc.`coll_short_prj`,
                   s.`HerbNummer`, s.`Bemerkungen`
            FROM `" . $_CONFIG['DATABASES']['OUTPUT']['db'] . "`.`tbl_specimens` s
             LEFT JOIN `" . $_CONFIG['DATABASES']['OUTPUT']['db'] . "`.`tbl_management_collections` mc ON mc.`collectionID` = s.`collectionID`
             LEFT JOIN `" . $_CONFIG['DATABASES']['OUTPUT']['db'] . "`.`tbl_img_definition` id ON id.`source_id_fk` = mc.`source_id`
            WHERE s.`specimen_ID` = '" . $dbLink->real_escape_string($specimenID) . "'";

    // Fetch information for this image
    $result = $dbLink->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_array(MYSQLI_ASSOC);

        $url = ((!empty($row['imgserver_Prot'])) ? $row['imgserver_Prot'] : "http") . '://'
             . $row['imgserver_IP']
             . (($row['img_service_directory']) ? '/' . $row['img_service_directory'] . '/' : '/');

        // Remove hyphens
        $HerbNummer = str_replace('-', '', $row['HerbNummer']);



        // Construct clean filename
        if ($row['imgserver_type'] == 'bgbm') {
            // Remove spaces for B HerbNumber
            $HerbNummer = ($row['HerbNummer']) ? $row['HerbNummer'] : ('JACQID' . $specimenID);
            $HerbNummer = str_replace(' ', '', $HerbNummer);
            $filename = sprintf($HerbNummer);
            $key = $row['key'];
        } elseif ($row['imgserver_type'] == 'baku') {
            $html = $row['Bemerkungen'];
            // create new ImageQuery object
            $query = new ImageQuery();

            // fetch image uris
            try {
                $uris = $query->fetchUris($html);
            } catch (Exception $e) {
                echo 'an error occurred: ', $e->getMessage(), "\n";
                die();
            }

            // do something with uris
            foreach ($uris as $uriSubset) {
                $newHtmlCode = '<a href="' . $uriSubset["image"] . '" target="_blank"><img src="' . $uriSubset["preview"] . '"/></a>';
            }

            $url = $uriSubset["base"];
            #$url .= ($row['img_service_directory']) ? '/' . $row['img_service_directory'] . '/' : '';
            if (substr($url, -1) != '/') {
                $url .= '/';  // to ensure that $url ends with a slash
            }
            $filename = sprintf($uriSubset["filename"]);
            $originalFilename = sprintf($uriSubset["thumb"]);
            $key = sprintf($uriSubset["html"]);
        } else {
            $filename = sprintf("%s_%0" . $row['HerbNummerNrDigits'] . ".0f", $row['coll_short_prj'], $HerbNummer);
            $key = $row['key'];
        }

        // Set original file-name if we didn't pass one (required for djatoka)
        // (required for pictures with suffixes)
        if ($originalFilename == null) {
            $originalFilename = $filename;
        }

        return array(
            'url'              => $url,
            'requestFileName'  => $request,
            'originalFilename' => $originalFilename,
            'filename'         => $filename,
            'specimenID'       => $specimenID,
            'is_djatoka'       => $row['is_djatoka'],
            'imgserver_type'   => $row['imgserver_type'],
            'key'              => $key
        );
    } else {
        return array(
            'url'              => null,
            'requestFileName'  => null,
            'originalFilename' => null,
            'filename'         => null,
            'specimenID'       => null,
            'is_djatoka'       => null,
            'imgserver_type'   => null,
            'key'              => null
        );
    }
}


/**
 * ask the picture server for information about pictures
 * in case of error an additional field "error" is filled in the array
 *
 * @param array $picdetails result of getPicDetails
 * @return array decoded response of the picture server
 */
function getPicInfo($picdetails)
{
    $return = array('output' => '',
                    'pics'   => '',
                    'error'  => '');

    if ($picdetails['imgserver_type'] == 'djatoka') {
        // Construct URL to servlet
        $url = $picdetails['url'] . 'jacq-servlet/ImageServer';

        // Prepare json-rpc conform request structure
        $jsonrpc_request = json_encode(array(
            'id' => 1234,
            'method' => 'listSpecimenImages',
            'params' => array($picdetails['key'], $picdetails['specimenID'], $picdetails['filename'])
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
        if (($fp = fopen($url, 'r', false, $context))) {
            $response = '';
            while ($row = fgets($fp)) {
                $response .= trim($row) . "\n";
            }
            $response_decoded = json_decode($response, true);

            $return['pics'] = $response_decoded['result'];
        } else {
            $return['error'] = 'Unable to connect to ' . $url;
        }
    } else if ($picdetails['imgserver_type'] == 'bgbm') {
        // Construct URL to servlet
        $HerbNummer = str_replace('-', '', $picdetails['filename']);

        $url = 'http://ww2.bgbm.org/rest/herb/thumb/' . $HerbNummer;

        $fp = fopen($url, "r");
        while ($row = fgets($fp)) {
            $response .= trim($row) . "\n";
        }

        $response_decoded = json_decode($response, true);

        $return['pics'] = $response_decoded['result'];
    } else if ($picdetails['imgserver_type'] == 'baku') {
        $return['pics'] = $picdetails['filename'];
    } else {  // old legacy
        $url = "{$picdetails['url']}/detail_server.php?key=DKsuuewwqsa32czucuwqdb576i12&ID={$picdetails['specimenID']}";

        $response = file_get_contents($url, "r");
        $response_decoded = unserialize($response);

        $return = array('output' => $response_decoded['output'],
                        'pics'   => $response_decoded['pics'],
                        'error'  => '');
    }

    return $return;
}
