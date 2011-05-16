<?php
session_start();
require("../inc/connect.php");
require("../inc/herbardb_input_functions.php");
require_once ("../inc/xajax/xajax_core/xajax.inc.php");

$xajax = new xajax();

$objResponse = new xajaxResponse();

// ajax-functions
/**
 * xajax-function react on a change of the source
 *
 * @param array $formData form-values
 * @return xajaxResponse
 */
function setSource($formData)
{
    global $objResponse;

    if ($formData['source'] == 'literature') {
        $objResponse->assign("ajax_sourcePers", "style.display", 'none');
        $objResponse->assign("lbl_et_al", "style.display", 'none');
        $objResponse->assign("et_al", "style.display", 'none');
        $objResponse->assign("ajax_sourceLit", "style.display", '');
    } else {
        $objResponse->assign("ajax_sourcePers", "style.display", '');
        $objResponse->assign("lbl_et_al", "style.display", '');
        $objResponse->assign("et_al", "style.display", '');
        $objResponse->assign("ajax_sourceLit", "style.display", 'none');
    }

    return $objResponse;
}


/**
 * register all xajax-functions in this file
 */
$xajax->registerFunction("setSource");
$xajax->processRequest();