<?php
require('inc/RestClient.php');

$rest = new RestClient("https://app05a.phaidra.org/");

header('Content-Type: application/json');

$type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);
switch ($type) {
    case 'manifests':
        echo $rest->get('manifests', array(filter_input(INPUT_GET, 'id', FILTER_SANITIZE_STRING)));
        break;
}