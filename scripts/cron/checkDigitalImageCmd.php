#!/usr/bin/php -qC
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Jacq\DbAccess;
use Jacq\Settings;

/**
 * process commandline arguments
 */
$opt = getopt("hvsa", ["help", "verbose", "set", "auto"], $restIndex);

$options = array(
        'help'    => (isset($opt['h']) || isset($opt['help']) || $argc == 1), // bool
        'verbose' => (isset($opt['v']) || isset($opt['verbose'])),            // bool
        'set'     => (isset($opt['s']) || isset($opt['set'])),                // bool
        'auto'    => (isset($opt['a']) || isset($opt['auto']))                // bool
);
$remainArgs = array_slice($argv, $restIndex);
$source_id = (empty(($remainArgs))) ? 0 : intval($remainArgs[0]);

if ($options['help'] || (!$source_id && !$options['auto'])) {
    echo $argv[0] . " [options] [x]   scan source [with ID x] for images and optionally activate 'digital_image' accordingly\n\n"
            . "Options:\n"
            . "  -h  --help     this explanation\n"
            . "  -v  --verbose  echo status messages\n"
            . "  -s  --set      set digital_image in tbl_specimens if image is found\n"
            . "  -a  --auto     cycle through all flagged sources\n\n";
    die();
}

// prepare global variables
try {
    $dbLnk = DbAccess::ConnectTo('INPUT');
} catch (Exception $e) {
    die($e->getMessage());
}

// do the scanning
if ($options['auto']) {
    $rows = $dbLnk->queryCatch("SELECT source_id_fk FROM tbl_img_definition WHERE autoscan = 1")->fetch_all(MYSQLI_ASSOC);
    foreach ($rows as $row) {
        scanSource($row['source_id_fk']);
        echo "\n";
    }
} elseif ($source_id) {
    $row = $dbLnk->queryCatch("SELECT img_def_ID FROM tbl_img_definition WHERE source_id_fk = $source_id")->fetch_assoc();
    if (!empty($row)) {
        scanSource($source_id);
    } else {
        echo "wrong source-ID\n";
    }
} else {
    echo "nothing to do\n";
}


/**
 * scan a source for existing images with digital_image set to 0
 *
 * @param int $source_id ID of the source to check
 * @return void
 */
function scanSource(int $source_id): void
{
    global $dbLnk, $options;

    $source = $dbLnk->queryCatch("SELECT m.source_code, m.source_name, id.iiif_capable, id.imgserver_type, pid.extension
                             FROM meta m
                              JOIN tbl_img_definition id ON id.source_id_fk = m.source_id
                              LEFT JOIN herbar_pictures.iiif_definition pid ON pid.source_id_fk = m.source_id
                             WHERE m.source_id = $source_id")
                    ->fetch_assoc();

    // check all specimens with digital_image == 0 if the image is now available and set digital_image = 1 if yes
    $specimens = $dbLnk->queryCatch("SELECT s.specimen_ID, s.HerbNummer 
                            FROM tbl_specimens s
                             LEFT JOIN tbl_management_collections mc ON mc.collectionID = s.collectionID
                            WHERE mc.source_id = $source_id
                             AND s.HerbNummer IS NOT NULL
                             AND s.digital_image = 0")
                       ->fetch_all(MYSQLI_ASSOC);
    $new = 0;
    echo "Looking for new pictures of {$source['source_name']}\n";
    foreach ($specimens as $specimen) {
        if ($source['iiif_capable']) {
            if ($source['extension'] == 'djatoka') {
                $imageExists = queryDjatoka($specimen['specimen_ID']);
            } else {
                $imageExists = queryIiif($specimen['specimen_ID']);
            }
        } else {
            $imageExists = queryDjatoka($specimen['specimen_ID']);
        }
        if ($imageExists) {
            if ($options['set']) {
                $dbLnk->queryCatch("UPDATE tbl_specimens SET digital_image = 1 WHERE specimen_ID = {$specimen['specimen_ID']}");
            }
            if ($options["verbose"]) {
                echo "found pictures for {$source['source_code']} {$specimen['HerbNummer']} (specimen-ID {$specimen['specimen_ID']})\n";
            }
            $new++;
        }
    }
    if ($new > 0 && $options["verbose"]) {
        echo "found $new specimens with new pictures ";
        if ($options['set']) {
            echo "and set digital_image ";
        }
        echo "(" . date(DATE_RFC822) . ")\n\n";
    }
}

/**
 * checks an IIIF-Server for a manifest and an image for a given specimen-ID
 *
 * @param int $specimenID specimen-ID
 * @return bool true if at least one image exists
 */
function queryIiif(int $specimenID): bool
{
    $manifest = getManifest($specimenID);
    if (!empty($manifest)) {
        $version = 2;
        foreach ($manifest['@context'] as $context) {
            if ($context == "http://iiif.io/api/presentation/3/context.json") {
                $version = 3;
                break;
            }
        }
        $fileLink = "";
        if ($version == 2) {
            foreach ($manifest['sequences'] as $sequence) {
                foreach ($sequence['canvases'] as $canvas) {
                    foreach ($canvas['images'] as $image) {
                        $fileLink = $image['resource']['service']['@id'] . "/full/max/0/default.jpg";
                        break 3;    // use just the first image
                    }
                }
            }
        } else {
            foreach ($manifest['thumbnail'] as $thumbnail) {
                foreach ($thumbnail['service'] as $service) {
                    $fileLink = $service['id'] . "/full/max/0/default.jpg";
                    break 2;  // use just the first image
                }
            }
        }
        if (!empty($fileLink) && imageExists($fileLink)) {
            return true;
        }
    }
    return false;
}

/**
 * checks the database (herbar_pictures.djatoka_images) for an image for a given specimen-ID
 *
 * @param int $specimenID specimen-ID
 * @return bool true if at least one image exists
 */
function queryDjatoka(int $specimenID): bool
{
    global $dbLnk;

    $rows = $dbLnk->queryCatch("SELECT id FROM herbar_pictures.djatoka_images WHERE specimen_ID = $specimenID")->fetch_all(MYSQLI_ASSOC);
    return !empty($rows);
}

/**
 * Asks the JACQ-service for the manifest of a given specimen
 *
 * @param int $specimenID ID of specimen to get the manifest for
 * @return array fetched manifest
 */
function getManifest(int $specimenID): array
{
    $config = Settings::Load();
    $ch = curl_init($config->get('JACQ_SERVICES') . "iiif/manifest/$specimenID");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $curl_response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($curl_response !== false && $httpCode == 200) {
        $manifest = json_decode($curl_response, true);
    } else {
        $manifest = array();
    }
    curl_close($ch);

    return $manifest;
}

/**
 * checks if the image from $url exists
 *
 * @param string $url image-url
 * @return bool true if the image exists
 */
function imageExists(string $url): bool
{
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD-request only
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $curl_response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $httpCode == 200;
}
