<?php
$_CONFIG['DATABASES']['OUTPUT'] = array(
    "host" => "localhost",
    "db" => "herbar",
    "readonly" => array(
        "user" => "",
        "pass" => ""
    )
);

$_CONFIG['DATABASES']['PICTURES'] = array(
    "host" => "localhost",
    "db" => "pictures",
    "readonly" => array(
        "user" => "",
        "pass" => ""
    )
);

$_CONFIG['ANNOSYS']['ACTIVE'] = false;  // switch to true if annosys-service is available for W or B herbaria

// URL to JACQ services
$_CONFIG['JACQ_SERVICES'] = "https://services.jacq.org/jacq-services/rest/";