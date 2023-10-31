<?php
// can only be used if inc/functions.php is included beforehand
use Jacq\DbAccess;
use Jacq\ImageQuery;
use JsonRPC\Client;

require_once __DIR__ . '/../vendor/autoload.php';

ini_set("default_socket_timeout", 5);

/**
 * get all details of a given picture
 *
 * @param mixed $request either the specimen_ID or the wanted filename
 * @param string $sid specimenID (optional, default=empty)
 * @return array return the wanted data if found or an empty array
 */
function getPicDetails($request, $sid = '')
{
    $dbLnk2 = DbAccess::ConnectTo('OUTPUT');

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

        if (!empty($sid) && intval($sid)) {
            // we've got a specimen-ID, so use it
            $specimenID = intval($sid);
        } else {
            // no specimen-ID included in call, so use old method and try to find one via HerbNummer
            if (substr($originalFilename, 0, 4) == 'KIEL') {
                // source_id 59 uses no "_" between coll_short_prj and HerbNummer (see also line 149)
                $coll_short_prj = 'KIEL';
                preg_match('/^([^_]+)/', substr($originalFilename, 4), $matches);
                $HerbNummer = $matches[1];
                $HerbNummerAlternative = substr($HerbNummer, 0, 4) . '-' . substr($HerbNummer, 4);
            } elseif (substr($originalFilename, 0, 2) == 'FT') {
                // source_id 47 uses no "_" between coll_short_prj and HerbNummer (see also line 149)
                $coll_short_prj = 'FT';
                preg_match('/^([^_]+)/', substr($originalFilename, 2), $matches);
                $HerbNummer = $matches[1];
                $HerbNummerAlternative = substr($HerbNummer, 0, 2) . '-' . substr($HerbNummer, 4);
            } else {
                // Extract HerbNummer and coll_short_prj from filename and use it for finding the specimen_ID
                if (preg_match('/^([^_]+)_([^_]+)/', $originalFilename, $matches) > 0) {
                    // Extract HerbNummer and construct alternative version
                    $coll_short_prj = $matches[1];
                    $HerbNummer = $matches[2];
                    $HerbNummerAlternative = substr($HerbNummer, 0, 4) . '-' . substr($HerbNummer, 4);
                } else {
                    $coll_short_prj = '';
                    $HerbNummer = $HerbNummerAlternative = 0;  // nothing found
                }
            }
            if ($HerbNummer) {
                // Find entry in specimens table and return specimen ID for it
                $sql = "SELECT s.`specimen_ID`
                        FROM `tbl_specimens` s
                         LEFT JOIN `tbl_management_collections` mc ON mc.`collectionID` = s.`collectionID`
                        WHERE (s.`HerbNummer` = '" . $dbLnk2->real_escape_string($HerbNummer) . "' OR s.`HerbNummer` = '" . $dbLnk2->real_escape_string($HerbNummerAlternative) . "' )
                         AND mc.`coll_short_prj` = '" . $dbLnk2->real_escape_string($coll_short_prj) . "'";
                $result = $dbLnk2->query($sql);
                if ($result->num_rows > 0) {
                    $row = $result->fetch_array(MYSQLI_ASSOC);
                    $specimenID = $row['specimen_ID'];
                }
            }
        }
    }

    $sql = "SELECT id.`imgserver_Prot`, id.`imgserver_IP`, id.`imgserver_type`, id.`img_service_directory`, id.`is_djatoka`, id.`HerbNummerNrDigits`, id.`key`,
                   mc.`coll_short_prj`, mc.`source_id`, mc.`collectionID`, mc.`picture_filename`,
                   s.`HerbNummer`, s.`Bemerkungen`
            FROM `tbl_specimens` s
             LEFT JOIN `tbl_management_collections` mc ON mc.`collectionID` = s.`collectionID`
             LEFT JOIN `tbl_img_definition` id ON id.`source_id_fk` = mc.`source_id`
            WHERE s.`specimen_ID` = '" . $dbLnk2->real_escape_string($specimenID) . "'";

    // Fetch information for this image
    $result = $dbLnk2->query($sql);
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
            $HerbNummer = ($row['HerbNummer']) ?: ('JACQID' . $specimenID);
            $HerbNummer = str_replace(' ', '', $HerbNummer);
            $filename = sprintf($HerbNummer);
            $key = $row['key'];
        } elseif ($row['imgserver_type'] == 'baku') {       // depricated
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
            $filename = $uriSubset["filename"];
            $originalFilename = $uriSubset["thumb"];
            $key = $uriSubset["html"];
        } else {
            if ($row['collectionID'] == 90 || $row['collectionID'] == 92 || $row['collectionID'] == 123) { // w-krypt needs special treatment
                /* TODO
                 * specimens of w-krypt are currently under transition from the old numbering system (w-krypt_1990-1234567) to the new
                 * numbering system (w_1234567). During this time, new HerbNumbers are given to the specimens and the entries
                 * in tbl_specimens are changed accordingly.
                 * So, this script should first look for pictures, named after the new system before searching for pictures, named after the old system
                 * When the transition is finished, this code-part (the whole elseif-block) should be removed
                 * Johannes Schachner, 25.9.2021
                 */
                $filename = sprintf("w_%0" . $row['HerbNummerNrDigits'] . ".0f", $HerbNummer);
                try {  // ask the picture server for a picture with the new filename
                    $service = new Client($url . 'jacq-servlet/ImageServer');
                    $pics = $service->execute('listResources',
                                                [
                                                    $row['key'],
                                                    [
                                                        $filename,
                                                        $filename . "_%",
                                                        $filename . "A",
                                                        $filename . "B",
                                                        "tab_" . $filename,
                                                        "obs_" . $filename,
                                                        "tab_" . $filename . "_%",
                                                        "obs_" . $filename . "_%"
                                                    ]
                                                ]);
                }
                catch( Exception $e ) {
                    $pics = array();  // something has gone wrong, so no picture can be found anyway
                }
                if (empty($pics)) {  // nothing found, so use the old filename
                    $filename = sprintf("w-krypt_%0" . $row['HerbNummerNrDigits'] . ".0f", $HerbNummer);
                }
            } elseif (!empty($row['picture_filename'])) {   // special treatment for this collection is necessary
                $parts = parser($row['picture_filename']);
                $filename = '';
                foreach ($parts as $part) {
                    if ($part['token']) {
                        $tokenParts = explode(':', $part['text']);
                        $token = $tokenParts[0];
                        switch ($token) {
                            case 'coll_short_prj':                                      // use contents of coll_short_prj
                                $filename .= $row['coll_short_prj'];
                                break;
                            case 'HerbNummer':                                          // use HerbNummer with removed hyphens, options are :num and :reformat
                                if (in_array('num', $tokenParts)) {                     // ignore text with digits within, only use the last number
                                    if (preg_match("/\d+$/", $HerbNummer, $matches)) {  // there is a number at the tail of HerbNummer
                                        $number = $matches[0];
                                    } else {                                            // HerbNummer ends with text
                                        $number = 0;
                                    }
                                } else {
                                    $number = $HerbNummer;                              // use the complete HerbNummer
                                }
                                if (in_array("reformat", $tokenParts)) {                // correct the number of digits with leading zeros
                                    $filename .= sprintf("%0" . $row['HerbNummerNrDigits'] . ".0f", $number);
                                } else {                                                // use it as it is
                                    $filename .= $number;
                                }
                                break;
                        }
                    } else {
                        $filename .= $part['text'];
                    }
                }
            } else {    // standard filename, would be "<coll_short_prj>_<HerbNummer:reformat>"
                $filename = sprintf("%s_%0" . $row['HerbNummerNrDigits'] . ".0f", $row['coll_short_prj'], $HerbNummer);
            }
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
                    'pics'   => array(),
                    'error'  => '');

    if ($picdetails['imgserver_type'] == 'djatoka') {
        // Construct URL to servlet
        $url = $picdetails['url'] . 'jacq-servlet/ImageServer';

        // Create a service instance and send requests to jacq-servlet
        try {
            $service = new Client($url);
            $return['pics'] = $service->execute('listResources',
                                                [
                                                    $picdetails['key'],
                                                    [
                                                        $picdetails['filename'],
                                                        $picdetails['filename'] . "_%",
                                                        $picdetails['filename'] . "A",
                                                        $picdetails['filename'] . "B",
                                                        "tab_" . $picdetails['specimenID'],
                                                        "obs_" . $picdetails['specimenID'],
                                                        "tab_" . $picdetails['specimenID'] . "_%",
                                                        "obs_" . $picdetails['specimenID'] . "_%"
                                                    ]
                                                ]);
        }
        catch( Exception $e ) {
            $return['error'] = 'Unable to connect to ' . $url . " with Error: " . $e->getMessage();
        }

        /*
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
        */
    } else if ($picdetails['imgserver_type'] == 'bgbm') {
        // Construct URL to servlet
        $HerbNummer = str_replace('-', '', $picdetails['filename']);

        $url = 'http://ww2.bgbm.org/rest/herb/thumb/' . $HerbNummer;

        $fp = fopen($url, "r");
        if ($fp) {
            $response = '';
            while ($row = fgets($fp)) {
                $response .= trim($row) . "\n";
            }
            $response_decoded = json_decode($response, true);
            $return['pics'] = $response_decoded['result'];
            fclose($fp);
        }
    } else if ($picdetails['imgserver_type'] == 'baku') {   // depricated
        $return['pics'] = $picdetails['filename'];
    } else {  // old legacy, depricated
        $url = "{$picdetails['url']}/detail_server.php?key=DKsuuewwqsa32czucuwqdb576i12&ID={$picdetails['specimenID']}";

        $response = file_get_contents($url, "r");
        $response_decoded = unserialize($response);

        $return = array('output' => $response_decoded['output'],
                        'pics'   => $response_decoded['pics'],
                        'error'  => '');
    }

    return $return;
}


/**
 * parse text into parts and tokens (text within '<>')
 *
 * @param string $text text to tokenize
 * @return array found parts
 */
function parser ($text)
{
    $parts = explode('<', $text);
    $result = array(array('text' => $parts[0], 'token' => false));
    for ($i = 1; $i < count($parts); $i++) {
        $subparts = explode('>', $parts[$i]);
        $result[] = array('text' => $subparts[0], 'token' => true);
        if (!empty($subparts[1])) {
            $result[] = array('text' => $subparts[1], 'token' => false);
        }
    }
    return $result;
}


/**
 * @param int|null $specimenID
 * @return bool
 * @throws Exception
 */
function checkPhaidra (?int $specimenID): bool
{
    $dbLnk2 = DbAccess::ConnectTo('OUTPUT');
    $result = $dbLnk2->query("SELECT specimenID FROM herbar_pictures.phaidra_cache WHERE specimenID = $specimenID");

    return ($result->num_rows > 0);
}
