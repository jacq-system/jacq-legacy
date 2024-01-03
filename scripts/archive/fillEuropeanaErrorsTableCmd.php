#!/usr/bin/php -q
<?php
require 'inc/variables.php';

/**
 * included via inc/variables.php
 *
 * @var string $host hostname
 * @var string $user username
 * @var string $pass password
 * @var string $db   source database
 * @var array  $tbls which sources are to be checked
 * @var string $europeana_dir directory of europeana images
 */

ini_set("memory_limit", "256M");

/**
 * process commandline arguments
 */
$options = getopt("hva", ["help", "verbose", "all"], $restIndex);

$help    = (isset($options['h']) || isset($options['help']) || $argc == 1); // bool
$all     = (isset($options['a']) || isset($options['all']));                // bool

$verbose = (isset($options['v']) || isset($options['verbose'])) ? ((is_array($options['v'])) ? 2 : 1) : 0;  // 0, 1 or 2

$remainArgs = array_slice($argv, $restIndex);
$source_id = (empty(($remainArgs))) ? 0 : intval($remainArgs[0]);

if ($help || (!$source_id && !$all)) {
    echo $argv[0] . " [options] [x]   fill europeana error-table [for source-ID x]\n\n"
        . "Options:\n"
        . "  -h  --help     this explanation\n"
        . "  -v  --verbose  echo status messages\n"
        . "  -vv            echo processed filenames also\n"
        . "  -a  --all      use all predefined source-IDs\n\n";
    die();
}

$dbLink = new mysqli($host, $user, $pass, $db);

if ($source_id) {
    fillErrorTable($source_id);
} elseif ($all) {
    // use $tbls as defined in variables.php
    foreach ($tbls as $tbl) {
        if ($tbl['europeana_get']) {
            fillErrorTable($tbl['source_id']);
        }
    }
}

function fillErrorTable(int $source_id): void
{
    global $verbose, $europeana_dir, $dbLink;

    $sourceCode = $dbLink->query("SELECT source_code 
                                  FROM meta 
                                  WHERE source_id = $source_id")
                         ->fetch_array()['source_code'];
    $dbLink->query("DELETE FROM herbar_pictures.europeana_errors WHERE source_id = $source_id");
    $limit = ($source_id == 55) ? 43000 : 3501;   // files smaller than that are considered broken, DR needs special treatment
    foreach (glob($europeana_dir . $sourceCode . '/*.jpg') as $filename) {
        $filesize = filesize($filename);
        if ($filesize < $limit) {
            $specimenID = intval(basename($filename, '.jpg'));
            if ($specimenID) {
                $filectime = filectime($filename);
                $dbLink->query("INSERT INTO herbar_pictures.europeana_errors SET
                                 specimen_ID = $specimenID,
                                 filesize    = $filesize,
                                 filectime   = FROM_UNIXTIME($filectime),
                                 source_id   = $source_id,
                                 source_code = '$sourceCode'");
                if ($verbose > 1) {
                    echo $dbLink->error;
                    echo " $sourceCode ($source_id): $filename\n";
                }
            }
        }
    }
    if ($verbose) {
        echo "---------- $sourceCode ($source_id) finished ----------\n";
    }
}
