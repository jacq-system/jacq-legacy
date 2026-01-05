#!/usr/bin/php -qC
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Jacq\DbAccess;

ini_set("max_execution_time", "3600");
ini_set("memory_limit", "256M");

/**
 * process commandline arguments
 */
$opt = getopt("hva", ["help", "verbose", "all"], $restIndex);

$options = array(
        'help'    => (isset($opt['h']) || isset($opt['help']) || $argc == 1),   // bool
        'all'     => (isset($opt['a']) || isset($opt['all']) || $argc == 1),    // bool

        'verbose' => ((isset($opt['v']) || isset($opt['verbose'])) ? ((is_array($opt['v'])) ? 2 : 1) : 0)  // 0, 1 or 2
);
$remainArgs = array_slice($argv, $restIndex);
$filename =  (empty(($remainArgs))) ? "" : $remainArgs[0];
$source_id = intval($remainArgs[1]);

if ($options['help'] || empty($source_id) || empty($filename)) {
    echo $argv[0] . " [options] name x   import gbif-SQL-Export file 'name' into cache for source-ID x.\n\n"
            . "Options:\n"
            . "  -h  --help         this explanation\n"
            . "  -v  --verbose      echo status messages\n"
            . "  -a  --all          import all entries, even if they already exist in cache\n\n";
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

if (($handle = fopen(basename($filename), "r")) !== false) {
    $data = fgetcsv($handle, 1000, "\t");   // skip the first line
    while (($data = fgetcsv($handle, 1000, "\t")) !== false) {
        $specimenID = intval($data[0]);
        if ($specimenID) {
            $row = $dbLink->queryCatch("SELECT specimen_ID, source_ID, UNIX_TIMESTAMP(aktualdatum) as modified
                                        FROM specimens 
                                        WHERE specimen_ID = $specimenID")
                          ->fetch_assoc();
            if (empty($row)) {
                $dbLink->queryCatch("INSERT INTO specimens SET
                                      specimen_ID = $specimenID,
                                      source_id   = $source_id");
            } elseif ($options['all'] || $row['modified'] < $data[1]) {
                $dbLink->queryCatch("UPDATE specimens SET
                                      aktualdatum = NULL,
                                      json        = NULL
                                     WHERE specimen_ID = $specimenID");
            }
        }
    }
    fclose($handle);
}
