<?php
/**
 * database access
 */
$options['dbhost'] = "localhost";        // hostname
$options['dbname'] = "herbar_taxamatch";      // database
$options['dbuser'] = "root";        // username
$options['dbpass'] = "toor";    // password

/**
 * JSON-RPC taxamatch service
 */
//$options['serviceTaxamatch'] = 'http://131.130.131.9/taxamatch/json_rpc_taxamatchMdld.php';               // NHM
$options['serviceTaxamatch'] = 'http://localhost/freud/trunk/taxamatch/jsonRPC/';       // BP