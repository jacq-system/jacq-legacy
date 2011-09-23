<?php

$_CONFIG['DATABASE']=array(
	
	// Main database containing the data
	'INPUT' => array(
		'host' => 'localhost',
		'name' => '',
		'readonly' => array(
			'user' => '',
			'pass' => ''
		)
	),

	// Names databases (doesn't need a own user actually, but instead it uses the login user)
	'NAME' =>array(
		'name' => 'names',
	),
	
	// Log database containing journal + user data
	'LOG' => array(
		'host' => 'localhost',
		'name' => '',
		'readonly' => array(
			'user' => '',
			'pass' => ''
		)
	),
	
	// Log database containing journal + user data
	'VIEWS' => array(
		'name' => 'herbar_view',
	),
);


// Force HTTPS
$_CONFIG['CONNECTION']['secure'] = true;

// Mdld only on Outputserver => JSON RPC Service
$_OPTIONS['internMDLDService']=array(
	'url'=>'http://www.website.com/internMDLDService.php',
	'password'=>'geheim'
);

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

$_OPTIONS['GEONAMES']=array(
	'username'=>'demo',
	'password'=>'',
	'cookieFile'=>dirname(__FILE__)."/tmp_cookie.txt"
);

$_OPTIONS['TYPINGCACHE']['SETTING']=array(
	'type'=>'DAY', 	// Valid: MICROSECOND, SECOND, MINUTE, HOUR, DAY, WEEK, MONTH, QUARTER, YEAR
	'val'=> 3 		// e.g. type=Day, val=3 => max 3 days Caching
);
//$_OPTIONS['debug'] = 1;
$_OPTIONS['debug'] = 0;

//  BP, 08/2010: use TCPDF 4.5 or 5.8
$_OPTIONS['tcpdf_5_8'] = false;

define('FREUDDIR',str_replace('\\','/',dirname(__FILE__)).'/../');
define('FREUDABSDIR'   , '/'.str_replace( str_replace('\\','/',$_SERVER['DOCUMENT_ROOT']).'','',FREUDDIR) );
