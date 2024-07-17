#!/usr/bin/php -qC
<?php
require_once __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Jacq\DbAccess;

/**
 * process commandline arguments
 */
$opt = getopt("hvrda", ["help", "verbose", "recheck", "deleted", "auto"], $restIndex);

$options = array(
    'help'    => (isset($opt['h']) || isset($opt['help']) || $argc == 1), // bool
    'verbose' => (isset($opt['v']) || isset($opt['verbose'])),            // bool
    'recheck' => (isset($opt['r']) || isset($opt['recheck'])),            // bool
    'deleted' => (isset($opt['d']) || isset($opt['deleted'])),            // bool
    'auto'    => (isset($opt['a']) || isset($opt['auto']))                // bool
);
$remainArgs = array_slice($argv, $restIndex);
$server_id = (empty(($remainArgs))) ? 0 : intval($remainArgs[0]);

if ($options['help'] || (!$server_id && !$options['auto'])) {
    echo $argv[0] . " [options] [x]   scan image Server [with ID x] for standalone images\n\n"
       . "Options:\n"
       . "  -h  --help     this explanation\n"
       . "  -v  --verbose  echo status messages\n"
       . "  -r  --recheck  recheck all linked files if herbNumber has changed\n"
       . "  -d  --deleted  show deleted files which are still in herbar_pictures.djatoka_images\n"
       . "  -a  --auto     cycle through all servers who were already scanned at least once\n\n";
    die();
}
if ($server_id == 1) {
    die("This server (wu) cannot be scanned\n");
}

// prepare global variables
try {
    $dbLnk = DbAccess::ConnectTo('INPUT');
} catch (Exception $e) {
    die($e->getMessage());
}
$cacheCollectionRules = array();

// do the scanning
if ($options['auto']) {
    $rows = $dbLnk->query("SELECT server_id FROM herbar_pictures.djatoka_images GROUP BY server_id")->fetch_all(MYSQLI_ASSOC);
    foreach ($rows as $row) {
        scanServer($row['server_id']);
    }
} elseif ($server_id) {
    scanServer($server_id);
} else {
    echo "nothing to do\n";
}


/**
 * make a correct picture filename for a given Herbarnumber and collection
 *
 * @param string $HerbNummerIn
 * @param int $collectionID
 * @return string
 */
function makePictureFilename(string $HerbNummerIn, int $collectionID): string
{
    global $dbLnk, $cacheCollectionRules;

    if (empty($cacheCollectionRules[$collectionID])) {
        $row = $dbLnk->query("SELECT mc.`coll_short_prj`, mc.`picture_filename`, id.`HerbNummerNrDigits`
                              FROM `tbl_management_collections` mc
                               LEFT JOIN `tbl_img_definition` id ON id.`source_id_fk` = mc.`source_id`
                              WHERE mc.collectionID = $collectionID")
                     ->fetch_assoc();
        if (empty($row)) {
            return "";      // unknown collection, missing entry in database
        }
        $row['picture_filename_parts'] = (!empty($row['picture_filename'])) ? pictureFilenameParser($row['picture_filename']) : null;
        $cacheCollectionRules[$collectionID] = $row;
    }

    $collectionRules = $cacheCollectionRules[$collectionID];
    $HerbNummer = str_replace('-', '', $HerbNummerIn);
    if ($collectionID == 90 || $collectionID == 92 || $collectionID == 123) { // w-krypt needs special treatment
        /* TODO
         * specimens of w-krypt are currently under transition from the old numbering system (w-krypt_1990-1234567) to the new
         * numbering system (w_1234567). During this time, new HerbNumbers are given to the specimens and the entries
         * in tbl_specimens are changed accordingly.
         * So, this script should first look for pictures, named after the new system before searching for pictures, named after the old system
         * When the transition is finished, this code-part (the whole elseif-block) should be removed
         * Johannes Schachner, 25.9.2021
         */
        $image = $dbLnk->query("SELECT filename 
                                FROM herbar_pictures.djatoka_images 
                                WHERE filename LIKE '" . (sprintf("w_%07.0f", $HerbNummer)) . "%'
                                 AND server_id = 2
                                LIMIT 1")
                        ->fetch_assoc();
        $filename = (!empty($image)) ? sprintf("w_%07.0f", $HerbNummer) : sprintf("w-krypt_%07.0f", $HerbNummer);
    } elseif (!empty($collectionRules['picture_filename'])) {   // special treatment for this collection is necessary
        $filename = '';
        foreach ($collectionRules['picture_filename_parts'] as $filename_part) {
            if ($filename_part['token']) {
                $tokenParts = explode(':', $filename_part['text']);
                $token = $tokenParts[0];
                switch ($token) {
                    case 'coll_short_prj':                                      // use contents of coll_short_prj
                        $filename .= $collectionRules['coll_short_prj'];
                        break;
                    case 'HerbNummer':                                          // use HerbNummer with removed hyphens, options are :num and :reformat
                        if (in_array('num', $tokenParts)) {                         // ignore text with digits within, only use the last number
                            if (preg_match("/\d+$/", $HerbNummer, $matches)) {  // there is a number at the tail of HerbNummer
                                $number = $matches[0];
                            } else {                                                       // HerbNummer ends with text
                                $number = 0;
                            }
                            $trailing = "";
                        } else {
                            preg_match("/(?P<number>\d+)(?P<trailing>\D*.*)/", $HerbNummer, $parts);
                            $number   = $parts['number'];   // use the complete HerbNummer
                            $trailing = $parts['trailing'];
                        }
                        if (in_array("reformat", $tokenParts)) {            // correct the number of digits with leading zeros
                            $filename .= sprintf("%0" . $collectionRules['HerbNummerNrDigits'] . ".0f", $number) . $trailing;
                        } else {                                                   // use it as it is
                            $filename .= $HerbNummer;
                        }
                        break;
                }
            } else {
                $filename .= $filename_part['text'];
            }
        }
    } else {    // standard filename, would be "<coll_short_prj>_<HerbNummer:reformat>"
        preg_match("/(?P<number>\d+)(?P<trailing>\D*.*)/", $HerbNummer, $parts);
        $filename = sprintf("%s_%0" . $collectionRules['HerbNummerNrDigits'] . ".0f", $collectionRules['coll_short_prj'], $parts['number']) . $parts['trailing'];
    }

    return $filename;
}

function extractHerbNumber($filename, $herbNumberNrDigits, $source_id): array
{
    global $dbLnk;

    preg_match("/(?P<leading>[^0-9_]*)(?P<underscore>_*)(?P<number>\d+)(?P<trailing>\D*.*)/", $filename, $parts);
    if (empty($parts['number'])) {
        $number = '';
    } elseif (strlen($parts['number']) > $herbNumberNrDigits) {
        $number = substr($parts['number'], 0, 4) . '-' . substr($parts['number'], 4);
    } else {
        $number = $parts['number'];
    }
    if (!empty($parts['trailing'])) {
        $first = substr($parts['trailing'], 0, 1);
        // the first letter after the number is A or B or C
        if (in_array($first, ['A', 'B', 'C', 'D', 'E'])) {
            // so check if there exists a specimen with a HerbNumber with a trailing A or B or C within this source
            $rows = $dbLnk->query("SELECT s.specimen_ID 
                                   FROM tbl_specimens s
                                    JOIN tbl_management_collections mc ON mc.collectionID = s.collectionID
                                   WHERE mc.source_id = $source_id
                                    AND HerbNummer = '$number$first'")
                          ->fetch_all(MYSQLI_ASSOC);
            // and if so, attach the letter to the already found HerbNumber
            if (!empty($rows)) {
                $number .= $first;
            }
        }
    }
    return array('sourceCode' => $parts['leading'] ?? '',
                 'herbNumber' => $number);
}

/**
 * parse text into parts and tokens (text within '<>')
 *
 * @param string $text text to tokenize
 * @return array found parts
 */
function pictureFilenameParser (string $text): array
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
 * scans a single picture server and update the table herbar_pictures.djatoka_images
 * at the moment only djatoka-servers are scanned
 *
 * @param int $server_id the id of the picture server
 * @return void
 */
function scanServer(int $server_id)
{
    global $dbLnk, $options;

    $imageDef = $dbLnk->query("SELECT id.source_id_fk, id.HerbNummerNrDigits, id.imgserver_type, id.imgserver_url, id.`key`, iiif.manifest_backend
                               FROM `tbl_img_definition` id
                                LEFT JOIN herbar_pictures.iiif_definition iiif ON iiif.source_id_fk = id.source_id_fk 
                               WHERE id.`img_def_ID` = $server_id")
                      ->fetch_assoc();
    if (empty($imageDef)) {
        die("unknown server-ID\n");
    }

// get all possible first parts of picture filenames
    $rows = $dbLnk->query("SELECT coll_short_prj, picture_filename 
                           FROM tbl_management_collections 
                           WHERE source_id = {$imageDef['source_id_fk']}")
                  ->fetch_all(MYSQLI_ASSOC);
    $searchpatterns = array();
    foreach ($rows as $row) {
        if (!empty($row['picture_filename'])) {
            $parts1 = explode('<', $row['picture_filename'], 2);
            $searchpattern = $parts1[0] . "%";
            $parts2 = explode('>', $parts1[1], 2);
            $searchpattern .= $parts2[1];
        } else {
            $searchpattern = $row['coll_short_prj'] . "_%";
        }
        if (!in_array($searchpattern, $searchpatterns)) {
            $searchpatterns[] = $searchpattern;
        }
    }

    $status = ['transferred' => 0, 'inserted' => 0, 'recheck' => 0, 'new' => 0, 'newwkrypt' => 0, 'offimages' => 0, 'linked' => 0];

    switch ($imageDef['imgserver_type']) {
        case "djatoka":
            echo "$server_id start\n";

            if (!empty($imageDef['manifest_backend']) && substr($imageDef['manifest_backend'],0,5) == 'POST:') {
                $url = substr($imageDef['manifest_backend'],5);
            } else {
                $url = $imageDef['imgserver_url'] . 'jacq-servlet/ImageServer';
            }

            $client = new Client(['timeout' => 4]);

            // cycle through all possible first parts of picture filenames, get possible pictures from the picture-server and process them
            foreach ($searchpatterns as $searchpattern) {
                try {
                    $response = $client->request('POST', $url, [
                        'json' => [
                                'method' => 'listResources',
                                'params' => [$imageDef['key'], [$searchpattern]],
                                'id'     => '1'
                        ],
                        'verify' => false
                    ]);
                    $data = json_decode($response->getBody()->getContents(), true, 512, JSON_INVALID_UTF8_SUBSTITUTE);
                } catch (GuzzleException $e) {
                    die($e->getMessage());
                }
                if ($options['verbose']) {
                    echo "transfer $searchpattern finished\n";
                }

                /* old variant
                        $cacheFilename = $counterFilename = array();
                        foreach ($data['result'] as $filename) {
                            $filename_upper = strtoupper($filename);
                            if (empty($cacheFilename[$filename_upper])) {
                                $cacheFilename[$filename_upper] = 1;
                            } else {
                                if (empty($counterFilename[$filename_upper])) {
                                    $counterFilename[$filename_upper] = 2;
                                } else {
                                    $counterFilename[$filename_upper]++;
                                }
                            }
                        }
                        var_export($counterFilename);

                        $sql_parts = array();
                        foreach ($data['result'] as $filename) {
                            $sql_parts[] = "($server_id, \"$filename\")";
                        }
                        $dbLnk->query("INSERT IGNORE INTO herbar_pictures.djatoka_images (server_id, filename) VALUES " . implode(',', $sql_parts));
                */
                if (!empty($data['result'])) {
                    $imagesOnServer = array();
                    foreach ($data['result'] as $filename) {
                        $filename_clean = $dbLnk->real_escape_string($filename);
                        $res = $dbLnk->query("SELECT id
                                              FROM herbar_pictures.djatoka_images
                                              WHERE filename = '$filename_clean'
                                               AND server_id = $server_id");
                        // if we receive any new pictures insert them into the database
                        if ($res->num_rows == 0) {
                            if ($dbLnk->query("INSERT IGNORE INTO herbar_pictures.djatoka_images (server_id, filename, source_id) 
                                                VALUES ($server_id, '$filename_clean', {$imageDef['source_id_fk']})")) {
                                $status['inserted']++;
                            }
                        }
                        $status['transferred']++;
                        $imagesOnServer[] = $filename_clean;
                    }
                    if ($options['deleted']) {
                        echo "looking for deleted image files:\n";
                        $deletedImages = $dbLnk->query("SELECT id, filename
                                                        FROM herbar_pictures.djatoka_images
                                                        WHERE server_id = $server_id
                                                         AND filename NOT IN ('" . implode("','", $imagesOnServer) . "')")
                                               ->fetch_all(MYSQLI_ASSOC);
                        foreach ($deletedImages as $deletedImage) {
                            echo "{$deletedImage['filename']} with ID {$deletedImage['id']} was deleted on server\n";
                        }
                    }
                    if ($options['verbose']) {
                        echo "db insert $searchpattern finished\n";
                    }
                } else {
                    if ($options['verbose']) {
                        echo "db insert $searchpattern finished, but no data from server\n";
                    }
                }
            }

            // try to extract the HerbNumber of the filename
            $images = $dbLnk->query("SELECT id, filename
                                             FROM herbar_pictures.djatoka_images
                                             WHERE server_id = $server_id
                                              AND extractedHerbNumber IS NULL")
                            ->fetch_all(MYSQLI_ASSOC);
            foreach ($images as $image) {
                $extract = extractHerbNumber($image['filename'], $imageDef['HerbNummerNrDigits'], $imageDef['source_id_fk']);
                if (!empty($extract['herbNumber'])) {
                    $dbLnk->query("UPDATE herbar_pictures.djatoka_images SET
                                          extractedHerbNumber = '{$extract['herbNumber']}'
                                         WHERE id = {$image['id']}");
                }
            }

            if ($options['recheck']) {
                // it's possible, that the herbNumber has changed since the last run. So check all filenames which are linked to a specimen if this link is still valid
                // but leave w-krypt alone, because of the special treatment it needs
                $specimens = $dbLnk->query("SELECT s.specimen_ID, s.HerbNummer, s.collectionID, di.id
                                            FROM tbl_specimens s
                                             JOIN tbl_management_collections mc ON mc.collectionID = s.collectionID
                                             JOIN herbar_pictures.djatoka_images di ON (di.specimen_ID = s.specimen_ID AND di.server_id = $server_id)
                                            WHERE mc.source_id = {$imageDef['source_id_fk']}
                                             AND s.collectionID NOT IN (90, 92, 123)
                                            ORDER BY s.HerbNummer")
                                   ->fetch_all(MYSQLI_ASSOC);
                foreach ($specimens as $specimen) {
                    if (!empty($specimen['HerbNummer'])) {
                        $filename = makePictureFilename($specimen['HerbNummer'], $specimen['collectionID']);  // make the correct filename
                        // and check if this filename still applies
                        $images = $dbLnk->query("SELECT id, filename
                                                 FROM herbar_pictures.djatoka_images
                                                 WHERE id = {$specimen['id']}
                                                  AND LOWER(filename) NOT LIKE LOWER('" . addcslashes($filename, '%_') . "%')")
                                        ->fetch_all(MYSQLI_ASSOC);
                        if (!empty($images)) {
                            foreach ($images as $image) {
                                $dbLnk->query("UPDATE herbar_pictures.djatoka_images SET
                                                specimen_ID = NULL
                                               WHERE id = {$image['id']}");
                                if ($options['verbose']) {
                                    echo "{$image['filename']} removed link to specimen\n";
                                }
                                $status['recheck']++;
                            }
                        }
                    }
                }
            }

            // first: get all specimens who should have images and are not mentioned in the table djatoka_images yet
            $specimens = $dbLnk->query("SELECT s.specimen_ID, s.HerbNummer, s.collectionID
                                        FROM tbl_specimens s
                                         JOIN tbl_management_collections mc ON mc.collectionID = s.collectionID
                                        WHERE mc.source_id = {$imageDef['source_id_fk']}
                                         AND s.digital_image = 1
                                         AND s.HerbNummer IS NOT NULL
                                         AND s.HerbNummer != '' 
                                         AND NOT EXISTS (SELECT 1
                                                         FROM herbar_pictures.djatoka_images di 
                                                         WHERE di.server_id = $server_id
                                                          AND s.specimen_ID = di.specimen_ID)
                                        ORDER BY s.HerbNummer")
                               ->fetch_all(MYSQLI_ASSOC);
            if (!empty($specimens)) {
                foreach ($specimens as $specimen) {
                    $filename = makePictureFilename($specimen['HerbNummer'], $specimen['collectionID']);  // make the correct filename
                    // and look for any images who are not already linked to a specimen
                    $images = $dbLnk->query("SELECT id, filename
                                             FROM herbar_pictures.djatoka_images
                                             WHERE server_id = $server_id
                                              AND LOWER(filename) LIKE LOWER('" . addcslashes($filename, '%_') . "%')
                                              AND specimen_ID IS NULL")
                                    ->fetch_all(MYSQLI_ASSOC);
                    if (!empty($images)) {
                        foreach ($images as $image) {
                            // and link them to the specimen
                            $dbLnk->query("UPDATE herbar_pictures.djatoka_images SET
                                            specimen_ID = {$specimen['specimen_ID']}
                                           WHERE id = {$image['id']}");
                            if ($options['verbose']) {
                                echo "{$image['filename']} ({$specimen['specimen_ID']})\n";
                            }
                            $status['new']++;
                        }
                    }
                }
            }

            // second: if server-ID is 2 (w), for now, we have to check for w-krypt entries which have the herbarnumber in CollNummer instead of HerbNummer
            //         and also for w entries which have the herbarnumber in CollNummer instead of HerbNummer
            /* TODO
             * specimens of w-krypt (and of w in general) are currently under transition from the old numbering system (w-krypt_1990-1234567) to the new
             * numbering system (w_1234567). During this time, new HerbNumbers are given to the specimens and the entries
             * in tbl_specimens are changed accordingly.
             * When the transition is finished, this code-part (the whole if-block) should be removed
             * Johannes Schachner, 4.11.2023
             */
            if ($server_id == 2) {
                if ($options['verbose']) {
                    echo "----\n";
                }
                // first find "w-krypt_....-......"
                // and then "w_....-......"
                $head = array('w-krypt\_',
                              'w\_');
                for ($i = 0; $i < 2; $i++) {
                    $images = $dbLnk->query("SELECT di.id, di.filename, s.specimen_ID  
                                             FROM herbar_pictures.djatoka_images di, tbl_specimens s, tbl_management_collections mc
                                             WHERE di.filename = CONCAT('$head[$i]', SUBSTRING(s.CollNummer, 1, 4), SUBSTRING(s.CollNummer, 6))
                                              AND di.server_id = 2
                                              AND di.specimen_ID IS NULL
                                              AND s.CollNummer IS NOT NULL
                                              AND s.collectionID = mc.collectionID
                                              AND mc.source_id = 6")
                                           // AND s.collectionID IN (90,92,123)
                                    ->fetch_all(MYSQLI_ASSOC);
                    if (!empty($images)) {
                        foreach ($images as $image) {
                            $dbLnk->query("UPDATE herbar_pictures.djatoka_images SET
                                            specimen_ID = {$image['specimen_ID']}
                                           WHERE id = {$image['id']}");
                            if ($options['verbose']) {
                                echo "{$image['filename']} ({$image['specimen_ID']})\n";
                            }
                            $status['newwkrypt']++;
                        }
                    }
                }
            }
            if ($options['verbose']) {
                echo "----\n";
            }

            // third: compare the extracted herbarium number from any pictures which are still not connected to a specimen_ID if any match can be found
            //        possible cause: digital_image in tbl_specimens is 0 but should be 1
            $images = $dbLnk->query("SELECT di.id, di.filename, s.specimen_ID  
                                     FROM herbar_pictures.djatoka_images di
                                      JOIN tbl_management_collections mc ON mc.source_id = di.source_id 
                                      JOIN tbl_specimens s ON s.collectionID = mc.collectionID 
                                     WHERE di.server_id = $server_id
                                      AND di.specimen_ID IS NULL
                                      AND di.extractedHerbNumber = s.HerbNummer")
                            ->fetch_all(MYSQLI_ASSOC);
            if (!empty($images)) {
                foreach ($images as $image) {
                    // and link them to the specimen
                    $dbLnk->query("UPDATE herbar_pictures.djatoka_images SET
                                            specimen_ID = {$image['specimen_ID']}
                                           WHERE id = {$image['id']}");
                    if ($options['verbose']) {
                        echo "{$image['filename']} ({$image['specimen_ID']})\n";
                    }
                    $status['offimages']++;
                }
            }
            if ($options['verbose']) {
                echo "----\n";
            }

            // fourth: look for any picture with extensions who still have no specimen connected.
            //         These are probably additional pictures of already linked ones
            $images = $dbLnk->query("SELECT id, filename
                                     FROM herbar_pictures.djatoka_images
                                     WHERE server_id = $server_id
                                      AND specimen_ID IS NULL")
                            ->fetch_all(MYSQLI_ASSOC);
            if (!empty($images)) {
                foreach ($images as $image) {
                    $parts = explode('_', $image['filename']);
                    if (count($parts) > 2) {                        // like w_0001234_extension
                        $searchFor = $parts[0] . "_" . $parts[1];   // strip the third part and search for the rest
                    } elseif (count($parts) == 2) {
                        // could be something like w_0001234 or like CHER0001234_extension
                        if (is_numeric(substr($parts[0], -1)) || is_numeric(substr($parts[0], -2, 1))) {  // like CHER0001234
                            $searchFor = $parts[0];                 // strip the second part and search for the rest
                        } else {                                    // like w_0001234 (no number in first part)
                            $searchFor = "";                        // no extension present, so we've nothing to do
                        }
                    } else {                                        // no "_", like CHER0001234
                        $searchFor = "";                            // no extension present, so we've nothing to do
                    }
                    if ($searchFor) {  // we've found an extension, so search for the master picture, if any
                        $knownImage = $dbLnk->query("SELECT id, filename, source_id, specimen_ID
                                                     FROM herbar_pictures.djatoka_images
                                                     WHERE server_id = $server_id
                                                      AND (   filename = '$searchFor'
                                                           OR LOWER(filename) LIKE LOWER('" . addcslashes($searchFor, '%_') . "\_%'))
                                                      AND specimen_ID IS NOT NULL")
                                            ->fetch_assoc();
                        if (!empty($knownImage)) {
                            $dbLnk->query("UPDATE herbar_pictures.djatoka_images SET
                                            specimen_ID = {$knownImage['specimen_ID']}
                                           WHERE id = {$image['id']}");
                            if ($options['verbose']) {
                                echo "{$image['filename']} => {$knownImage['filename']} ({$knownImage['specimen_ID']})\n";
                            }
                            $status['linked']++;
                        }
                    }
                }
            }



// second attempt: very slow
//        $filterpatterns = array();
//        foreach ($searchpatterns as $searchpattern) {
//            $parts = explode("%", $searchpattern);
//            $filterpatterns[] = array('pattern' => $parts[0],
//                                      'length'  => strlen($parts[0]));
//        }
//
//        $images = $dbLnk->query("SELECT id, filename
//                                       FROM herbar_pictures.djatoka_images
//                                       WHERE server_id = $server_id
//                                        AND specimen_ID IS NULL")
//                        ->fetch_all(MYSQLI_ASSOC);
//        foreach ($images as $iid=>$image) {
//            echo $iid . " - " . $image['filename'];
//            foreach ($filterpatterns as $filterpattern) {
//                if (substr($image['filename'], 0, $filterpattern['length']) == $filterpattern['pattern']) {  // filename starts with pattern
//                    $parts = explode('_', substr($image['filename'], $filterpattern['length']));  // strip everything after the first "_"
//                    if (strlen($parts[0]) > $imageDef['HerbNummerNrDigits'] && $imageDef['source_id_fk'] == 6) {
//                        // assuming a HerbNumber like "1982-01234567" and Herbarium W
//                        $searchname = substr($parts[0], 0, 4) . "-" . substr($parts[0], 5);
//                    } else {
//                        preg_match("/(?P<zeros>[0]*)(?P<rest>.*)/", $parts[0], $preg_parts);
//                        $searchname = "%" . $preg_parts['rest'];
//                    }
//                    $specimens = $dbLnk->query("SELECT s.specimen_ID, s.HerbNummer, s.collectionID
//                                                FROM tbl_specimens s
//                                                 JOIN tbl_management_collections mc ON s.collectionID = mc.collectionID
//                                                WHERE s.HerbNummer LIKE '$searchname'
//                                                 AND mc.source_id = {$imageDef['source_id_fk']}")
//                                       ->fetch_all(MYSQLI_ASSOC);
//                    if (!empty($specimens)) {
//                        echo "  " . count($specimens) . "\n";
//                        foreach ($specimens as $specimen) {
//                            $pictureFilename = "";//makePictureFilename($specimen['HerbNummer'], $specimen['collectionID']);
////                            echo $pictureFilename . "\n";
//                            if ($pictureFilename == substr($image['filename'], 0, strlen($pictureFilename))) {
//                                echo $image['filename'] . "  :  " . $specimen['specimen_ID'] . " - " . $specimen['HerbNummer'] . "\n";
//                                break;
//                            }
//                        }
//                    } else {
//                        echo "\n";
//                    }
//                }
//            }
//        }

// first attempt
//        $sourceCache = array();
//        foreach ($images as $image) {
//            $parts = explode('_', $image['filename']);
//            if (!empty($parts[1])) {
//                if (empty($sourceCache[$parts[0]])) {
//                    $row = $dbLnk->query("SELECT source_id FROM tbl_management_collections WHERE coll_short_prj LIKE '$parts[0]'")
//                                 ->fetch_assoc();
//                    if (!empty($row['source_id'])) {
//                        $sourceCache[$parts[0]] = $row['source_id'];
//                    }
//                }
//                if (!empty($sourceCache[$parts[0]])) {
//                    $specimen = $dbLnk->query("SELECT s.specimen_ID
//                                               FROM tbl_specimens s, tbl_management_collections mc
//                                               WHERE s.collectionID = mc.collectionID
//                                                AND mc.source_id = {$sourceCache[$parts[0]]}
//                                                AND HerbNummer = '$parts[1]'")
//                                        ->fetch_assoc();
//                    if (!empty($specimen['specimen_ID'])) {
//                        $dbLnk->query("UPDATE herbar_pictures.djatoka_images SET
//                                        source_id = {$sourceCache[$parts[0]]},
//                                        specimen_ID = {$specimen['specimen_ID']}
//                                       WHERE id = {$image['id']}");
//                    }
//                }
//            }
//        }
            echo $status['transferred'] . " images transferred from server\n"
               . $status['inserted'] . " new images inserted into database\n";
            if ($options['recheck']) {
                $status['recheck'] . " images changed due to recheck\n";
            }
            echo $status['new'] . " images newly connected with specimens\n";
            if ($status['newwkrypt']) {
                echo $status['newwkrypt'] . " new w-krypt images connected with specimens\n";
            }
            echo $status['offimages'] . " images linked to specimens with switched off 'digital_image'\n"
               . $status['linked'] . " images linked to already connected images\n";
            echo "server $server_id finished (" . date(DATE_RFC822) . ")\n";
            break;
        default:
            echo "wrong server type\n";
            break;
    }
}
