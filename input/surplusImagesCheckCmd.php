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
if ($server_id == 1) {
    die("This server (wu) cannot be scanned\n");
}

$dbLnk = DbAccess::ConnectTo('INPUT');
$cacheCollectionRules = array();

/**
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
    if (!empty($collectionRules['picture_filename'])) {   // special treatment for this collection is necessary
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


$imageDef = $dbLnk->query("SELECT source_id_fk, HerbNummerNrDigits, imgserver_type, imgserver_url, `key`
                           FROM `tbl_img_definition`
                           WHERE `img_def_ID` = $server_id")
                  ->fetch_assoc();  // TODO: do not use img_coll_short: sourceIDs 6 (w), 29 (b) and 54 (bpww) use different coll_short_prj for some collections
if (empty($imageDef)) {
    die("unknown server-ID\n");
}
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

switch ($imageDef['imgserver_type']) {
    case "djatoka":
        $client = new Client(['timeout' => 2]);

        foreach ($searchpatterns as $searchpattern) {
//            try {
//                $response = $client->request('POST', $imageDef['imgserver_url'] . 'jacq-servlet/ImageServer', [
//                                              'json' => ['method' => 'listResources',
//                                                         'params' => [$imageDef['key'], [$searchpattern]],
//                                                         'id' => '1'],
//                                              'verify' => false
//                                            ]);
//                $data = json_decode($response->getBody()->getContents(), true, 512, JSON_INVALID_UTF8_SUBSTITUTE);
//            } catch (Exception $e) {
//                die($e->getMessage());
//            }

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
            }
        }

        // first: get all specimens who should have images and are not mentioned in the table djatoka_images
        $specimens = $dbLnk->query("SELECT s.specimen_ID, s.HerbNummer, s.collectionID
                                    FROM tbl_specimens s
                                     JOIN tbl_management_collections mc ON mc.collectionID = s.collectionID
                                    WHERE mc.source_id = {$imageDef['source_id_fk']}
                                     AND s.digital_image = 1
                                     AND s.specimen_ID NOT IN (SELECT di.specimen_ID 
                                                               FROM herbar_pictures.djatoka_images di 
                                                               WHERE di.server_id = $server_id
                                                                AND di.specimen_ID IS NOT NULL)
                                    ORDER BY s.HerbNummer")
                           ->fetch_all(MYSQLI_ASSOC);
        if (!empty($specimens)) {
            foreach ($specimens as $specimen) {
                $filename = makePictureFilename($specimen['HerbNummer'], $specimen['collectionID']);  // make the correct filename
                // and look for any images who are not already linked to a specimen
                $images = $dbLnk->query("SELECT id, filename
                                         FROM herbar_pictures.djatoka_images
                                         WHERE server_id = $server_id
                                          AND filename LIKE '$filename%'
                                          AND specimen_ID IS NULL")
                                ->fetch_all(MYSQLI_ASSOC);
                if (!empty($images)) {
                    foreach ($images as $image) {
                        // and link them to the specimen
                        echo "{$image['filename']} ({$specimen['specimen_ID']})\n";
//                        $dbLnk->query("UPDATE herbar_pictures.djatoka_images SET
//                                        source_id = {$imageDef['source_id_fk']},
//                                        specimen_ID = {$specimen['specimen_ID']}
//                                       WHERE id = {$image['id']}");
                    }
                }
            }
        }
        echo "--\n";

        // second: look for any picture with extensions who still have no specimen connected.
        //         These are probably additional pictures of already linked ones without extension
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
                    $knownimage = $dbLnk->query("SELECT id, filename, source_id, specimen_ID
                                                 FROM herbar_pictures.djatoka_images
                                                 WHERE server_id = $server_id
                                                  AND filename = '$searchFor'
                                                  AND specimen_ID IS NOT NULL")
                                        ->fetch_assoc();
                    if (!empty($knownimage)) {
                        echo "{$image['filename']}: {$knownimage['filename']} ({$knownimage['specimen_ID']})\n";
//                        $dbLnk->query("UPDATE herbar_pictures.djatoka_images SET
//                                    source_id = {$knownimage['source_id']},
//                                    specimen_ID = {$knownimage['specimen_ID']}
//                                   WHERE id = {$image['id']}");
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
        echo "ok\n";
        break;
    default:
        echo "wrong server type\n";
        break;
}
