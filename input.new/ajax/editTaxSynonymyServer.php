<?php
session_start();
require("../inc/connect.php");
require("../inc/herbardb_input_functions.php");
require __DIR__ . '/../vendor/autoload.php';

use Jaxon\Jaxon;
use Jaxon\Response\Response;

$jaxon = jaxon();

$response = new Response();

// ajax-functions
/**
 * jaxon-function react on a change of the source
 *
 * @param array $formData form-values
 * @return Response
 */
function setSource($formData)
{
    global $response;

    if ($formData['source'] == 'literature') {
        $response->assign("ajax_sourcePers", "style.display", 'none');
        $response->assign("ajax_sourceLit", "style.display", '');
        $response->assign("sourceService", "style.display", 'none');
    } elseif ($formData['source'] == 'service') {
        $response->assign("ajax_sourcePers", "style.display", 'none');
        $response->assign("ajax_sourceLit", "style.display", 'none');
        $response->assign("sourceService", "style.display", '');
    } else {
        $response->assign("ajax_sourcePers", "style.display", '');
        $response->assign("ajax_sourceLit", "style.display", 'none');
        $response->assign("sourceService", "style.display", 'none');
    }

    return $response;
}


/**
 * register all jaxon-functions in this file
 */
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "setSource");
$jaxon->processRequest();