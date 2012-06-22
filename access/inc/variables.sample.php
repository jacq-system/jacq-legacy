<?php
$_CONFIG['DATABASE']['INPUT'] = array(
    "host" => "localhost",
    "db" => "input",
    "readonly" => array(
        "user" => "",
        "pass" => ""
    )
);

$_CONFIG['DATABASE']['PICTURES'] = array(
    "host" => "localhost",
    "db" => "pictures",
    "readwrite" => array(
        "user" => "",
        "pass" => ""
    ),
    "readonly" => array(
        "user" => "",
        "pass" => ""
    )
);

$_CONFIG['FILESYSTEM']['BATCHEXPORT'] = '/api-batches/export';

$_OPTIONS['internMDLDService']['password']='geheim';
