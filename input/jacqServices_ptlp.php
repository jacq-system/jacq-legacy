<?php
// this is the local pass through landing page for all ajax-operations regarding JACQ-Services

require('inc/variables.php'); // require configuration

header('Content-Type: application/json');

if (filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING) == "raw") {
    $call = filter_input(INPUT_GET, 'resource', FILTER_SANITIZE_STRING);
} else {
    $call = $_CONFIG['JACQ_SERVICES'] . filter_input(INPUT_GET, 'resource', FILTER_SANITIZE_STRING);
}

switch (strtoupper(filter_input(INPUT_GET, 'method', FILTER_SANITIZE_STRING))) {
    case 'GET':
    default:
        echo getREST($call);
}


/**
 * call a service
 *
 * @param string $service_url
 * @return mixed answer of the service
 */
function getREST($service_url)
{
    $curl = curl_init($service_url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    $curl_response = curl_exec($curl);
    if ($curl_response === false) {
//        $info = curl_getinfo($curl);
        curl_close($curl);
        return null;
    }
    curl_close($curl);
    return $curl_response;
}
