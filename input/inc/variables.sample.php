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

    // Database for picture related information
    'PICTURES' => array(
        'host' => 'localhost',
        'name' => 'pictures',
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
    'JACQ' => array(
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

// URL to JACQ services
$_CONFIG['JACQ_SERVICES'] = "https://services.jacq.org/jacq-services/rest/";

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
$_OPTIONS['DB']['INPUT']['HOST'] = $_CONFIG['DATABASE']['INPUT']['host'];    // hostname of herbarinput
$_OPTIONS['DB']['INPUT']['NAME'] = $_CONFIG['DATABASE']['INPUT']['name'];  // database of herbarinput

$_OPTIONS['DB']['PICTURES']['HOST'] = $_CONFIG['DATABASE']['PICTURES']['host'];
$_OPTIONS['DB']['PICTURES']['NAME'] = $_CONFIG['DATABASE']['PICTURES']['name'];

$_OPTIONS['TYPINGCACHE']['SETTING'] = array(
    'type' => 'DAY', // Valid: MICROSECOND, SECOND, MINUTE, HOUR, DAY, WEEK, MONTH, QUARTER, YEAR
    'val' => 3   // e.g. type=Day, val=3 => max 3 days Caching
);

// Enable debug mode
$_OPTIONS['debug'] = 0;

//  Use which TCPDF version
$_OPTIONS['tcpdf'] = '6.4.2';

// Staging area
$_OPTIONS['staging_area'] = array(
    'enabled' => TRUE,
    'ignore_no_genus' => TRUE, // allows adding new taxa to the DB, even if no matching genus has been found
);
