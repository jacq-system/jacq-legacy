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

    // New database containing data
    'INPUT' => array(
        'host' => 'localhost',
        'name' => 'jacq_input',
        'readonly' => array(
            'user' => '',
            'pass' => ''
        )
    ),
);

// Force HTTPS
$_CONFIG['CONNECTION']['secure'] = false;

// JSON-RPC Service for internal mdld
$_OPTIONS['internMDLDService'] = array(
    'url' => 'http://website.com/access/internMDLDService.php',
    'password' => 'geheim'
);
// Taxamatch JSON-RPC service
$_OPTIONS['serviceTaxamatch'] = 'http://website.com/taxamatch/jsonRPC/json_rpc_taxamatchMdld.php';

// Geonames user / password
$_OPTIONS['GEONAMES'] = array(
    'username' => 'demo',
    'password' => '',
    'cookieFile' => dirname(__FILE__) . "/tmp_cookie.txt"
);

// Url to herbarium
$_OPTIONS['HERBARIMAGEURL'] = "http://website.com/output/";
$_CONFIG['URL']['ACCESS'] = 'http://website.com/access/';




/*
 * Older settings start here (need to be changed to the new config structure)
 */
$_OPTIONS['TYPINGCACHE']['SETTING'] = array(
    'type' => 'DAY', // Valid: MICROSECOND, SECOND, MINUTE, HOUR, DAY, WEEK, MONTH, QUARTER, YEAR
    'val' => 3   // e.g. type=Day, val=3 => max 3 days Caching
);

// Enable debug mode
$_OPTIONS['debug'] = 0;

// Staging area
$_OPTIONS['staging_area'] = array(
    'enabled' => TRUE,
    'ignore_no_genus' => TRUE, // allows adding new taxa to the DB, even if no matching genus has been found
);
