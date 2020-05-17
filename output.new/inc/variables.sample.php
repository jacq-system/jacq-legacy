<?php
$_CONFIG['DATABASES']['OUTPUT'] = array(
    "host" => "localhost",
    "db" => "herbar",
    "readonly" => array(
        "user" => "",
        "pass" => ""
    )
);

$_CONFIG['DATABASE']['PICTURES'] = array(
    "host" => "localhost",
    "db" => "pictures",
    "readonly" => array(
        "user" => "",
        "pass" => ""
    )
);

$_CONFIG['ANNOSYS']['ACTIVE'] = false;  // switch to true if annosys-service is available for W or B herbaria

// URL to yii-based JACQ implementation (for JSON webservices)
$_CONFIG['JACQ_URL'] = "http://example.com/jacq-yii/";
