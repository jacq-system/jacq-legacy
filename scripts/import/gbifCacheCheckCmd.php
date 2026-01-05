#!/usr/bin/php -qC
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Jacq\DbAccess;

ini_set("max_execution_time", "3600");
ini_set("memory_limit", "256M");

/**
 * process commandline arguments
 */
$opt = getopt("hv", ["help", "verbose"], $restIndex);

$options = array(
    'help'      => (isset($opt['h']) || isset($opt['help']) || $argc == 1),      // bool

    'verbose'   => ((isset($opt['v']) || isset($opt['verbose'])) ? ((is_array($opt['v'])) ? 2 : 1) : 0)  // 0, 1 or 2
);
$remainArgs = array_slice($argv, $restIndex);
$source_id = (empty(($remainArgs))) ? 0 : intval($remainArgs[0]);

if ($options['help'] || !$source_id) {
    echo $argv[0] . " [options] x   check gbif-cache-data for errors for source-ID x\n\n"
        . "Options:\n"
        . "  -h  --help         this explanation\n"
        . "  -v  --verbose      echo status messages\n\n";
    die();
}

// prepare global variables
try {
    $dbLink = DbAccess::ConnectTo('GBIF_CACHE');
} catch (Exception $e) {
    die($e->getMessage());
}

$source = $dbLink->queryCatch("SELECT source_id, datasetKey, OwnerOrganizationName, LicenseURI, LicensesDetails 
                               FROM sources 
                               WHERE source_id = $source_id")
                 ->fetch_assoc();
if (empty($source)) {
    die("Could not find source-ID $source_id\n");
}

$rows = $dbLink->queryCatch("SELECT specimen_ID 
                             FROM specimens 
                             WHERE source_id = $source_id
                              AND json IS NOT NULL")
               ->fetch_all(MYSQLI_ASSOC);
$missing = array('recordedBy' => 0, 'scientificName' => 0, 'eventDate' => 0);
foreach ($rows as $row) {
    $specimenJson = $dbLink->queryCatch("SELECT json from specimens where specimen_ID = {$row['specimen_ID']}")->fetch_assoc();
    $line = json_decode($specimenJson['json'], true);
    $dirty = false;
    $errors = array();
    if (empty($line['recordedBy'])) {
        $missing['recordedBy']++;
        $errors['missing'][] = 'recordedBy';
        if ($options['verbose']) {
            echo $row['specimen_ID'] . " has empty recordedBy";
            $dirty = true;
        }
    }
    if (empty($line['scientificName'])) {
        $missing['scientificName']++;
        $errors['missing'][] = 'scientificName';
        if ($options['verbose']) {
            echo ((!$dirty) ? $row['specimen_ID'] . " has empty " : ", ") . "scientificName";
            $dirty = true;
        }
    }
    if (empty($line['eventDate'])) {
        $missing['eventDate']++;
        $errors['missing'][] = 'eventDate';
        if ($options['verbose']) {
            echo ((!$dirty) ? $row['specimen_ID'] . " has empty " : ", ") . "eventDate";
            $dirty = true;
        }
    }
    if ($dirty) {
        echo "\n";
    }
    if (!empty($errors)) {
        $dbLink->queryCatch("UPDATE specimens SET errors = '" . $dbLink->real_escape_string(json_encode($errors)) . "' WHERE specimen_ID = {$row['specimen_ID']}");
    }
}
$rows = $dbLink->queryCatch("SELECT errors, COUNT(errors) AS count FROM specimens WHERE source_id = $source_id AND errors IS NOT NULL GROUP BY errors")->fetch_all(MYSQLI_ASSOC);
foreach ($rows as $row) {
    echo "{$row['count']}\t{$row['errors']}\n";
}
echo "missing: {$missing['recordedBy']} recordedBy, {$missing['scientificName']} scientificName, {$missing['eventDate']} eventDate\n";

//specimen_ID IN (5919616105,3011853692)

// 26.12.2025

// 10001 153580
// 10002 4040

// 10001 missing:
//  2340 recordedBy
//   753 scientificName
//  4452 eventDate
//     0 recordedBy AND scientificName
// 48652 recordedBy AND eventDate
//    42 scientificName AND eventDate
//     0 recordedBy AND scientificName AND eventDate
// 50992 recordedBy, 795 scientificName, 53146 eventDate

// 10002 missing:
//   1 recordedBy
// 214 eventDate
