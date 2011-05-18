<?php
// Main database containing the data
$_CONFIG['DATABASE']['INPUT'] = array(
    "host" => "localhost",
    "name" => "",
    "readonly" => array(
        "user" => "",
        "pass" => ""
    )
);

// Log database containing journal + user data
$_CONFIG['DATABASE']['LOG'] = array(
    "host" => "localhost",
    "name" => "",
    "readonly" => array(
        "user" => "",
        "pass" => ""
    )
);

// Force HTTPS
$_CONFIG['CONNECTION']['secure'] = true;

/*
 * Older settings start here (need to be changed to the new config structure)
 */
$_OPTIONS['DB']['INPUT']['HOST'] = $_CONFIG['DATABASE']['INPUT']['host'];    // hostname of herbarinput
$_OPTIONS['DB']['INPUT']['NAME'] = $_CONFIG['DATABASE']['INPUT']['name'];  // database of herbarinput

// Settings for Heimo Rainer's server: don't forget to activate before committing!!!
$_OPTIONS['serviceTaxamatch'] = 'http://131.130.131.9/taxamatch/json_rpc_taxamatchMdld.php';
// Settings for Barbara (openSUSE)
//$_OPTIONS['serviceTaxamatch'] = 'http://localhost/ta/json_rpc_taxamatchMdld.php';
// Settings for Barbara (Windows)
//$_OPTIONS['serviceTaxamatch'] = 'http://localhost/herbarium_taxa/jsonRPC/json_rpc_taxamatchMdld.php'

//$_OPTIONS['debug'] = 1;
$_OPTIONS['debug'] = 0;

//  BP, 08/2010: use TCPDF 4.5 or 5.8
$_OPTIONS['tcpdf_5_8'] = false;