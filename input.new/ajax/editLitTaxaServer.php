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
        $response->assign("lbl_et_al", "style.display", 'none');
        $response->assign("et_al", "style.display", 'none');
        $response->assign("ajax_sourceLit", "style.display", '');
    } else {
        $response->assign("ajax_sourcePers", "style.display", '');
        $response->assign("lbl_et_al", "style.display", '');
        $response->assign("et_al", "style.display", '');
        $response->assign("ajax_sourceLit", "style.display", 'none');
    }

    return $response;
}


/**
 * register all jaxon-functions in this file
 */
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "setSource");
$jaxon->processRequest();