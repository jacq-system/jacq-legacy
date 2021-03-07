<?php
// Database(s) configuration
$_CONFIG['DATABASE'] = array(
    // Main database containing the data
    'INPUT' => array(
        'host' => 'localhost',
        'name' => 'input',
        'readonly' => array(
            'user' => '',
            'pass' => ''
        )
    ),

    // Names databases (doesn't need a own user actually, but instead it uses the login user)
    'NAME' => array(
        'name' => 'names',
    ),

    // Log database containing journal + user data
    'LOG' => array(
        'host' => 'localhost',
        'name' => 'log',
        'readonly' => array(
            'user' => '',
            'pass' => ''
        )
    ),

    // Database containing the views
    'VIEWS' => array(
        'name' => 'view',
    ),
);

// Force HTTPS
$_CONFIG['CONNECTION']['secure'] = false;

// Mdld only on Outputserver => JSON RPC Service
$_OPTIONS['internMDLDService'] = array(
    'url' => 'http://www.website.com/internMDLDService.php',
    'password' => 'geheim'
);

/*
 * Older settings start here (need to be changed to the new config structure)
 */
// Taxamatch JSON-RPC service
$_OPTIONS['serviceTaxamatch'] = 'http://www.website.com/taxamatch/json_rpc_taxamatchMdld.php';

// Geonames user / password
$_OPTIONS['GEONAMES'] = array(
    'username' => 'demo',
    'password' => '',
    'cookieFile' => dirname(__FILE__) . "/tmp_cookie.txt"
);

$_OPTIONS['TYPINGCACHE']['SETTING'] = array(
    'type' => 'DAY', // Valid: MICROSECOND, SECOND, MINUTE, HOUR, DAY, WEEK, MONTH, QUARTER, YEAR
    'val' => 3   // e.g. type=Day, val=3 => max 3 days Caching
);

// Enable debug mode
$_OPTIONS['debug'] = 0;

$_OPTIONS['HERBAROUTPUTURL'] = "http://herbarium.univie.ac.at";

// Define our base locations
define('FREUDDIR', str_replace('\\', '/', dirname(__FILE__)) . '/../');
define('FREUDABSDIR', '/' . str_replace(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']) . '', '', FREUDDIR));
