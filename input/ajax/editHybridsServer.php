<?php
session_start();
require("../inc/connect.php");
require("../inc/herbardb_input_functions.php");
require __DIR__ . '/../vendor/autoload.php';

use Jaxon\Jaxon;
use Jaxon\Response\Response;

$jaxon = jaxon();

$response = new Response();

function checkParents($taxonID, $parent1ID, $parent2ID)
{
    global $response;

    $row = dbi_query("SELECT * FROM tbl_tax_hybrids WHERE parent_1_ID = $parent1ID AND parent_2_ID = $parent2ID")->fetch_assoc();
    $rowMirror = dbi_query("SELECT * FROM tbl_tax_hybrids WHERE parent_2_ID = $parent1ID AND parent_1_ID = $parent2ID")->fetch_assoc();
    if (!empty($row['taxon_ID_fk']) && $row['taxon_ID_fk'] != $taxonID) {
        $response->assign('alertbox', 'innerHTML', "<a href='editSpecies.php?sel=<{$row['taxon_ID_fk']}>' target='Species'>Hybrid already exists with ID {$row['taxon_ID_fk']}</a>");
        $response->script('$("#alertbox").css("background-color", "OrangeRed");');
        $response->script("$(\"[name='submitUpdate']\").css('visibility', 'hidden');");
    } elseif (!empty($rowMirror['taxon_ID_fk'])) {
        $response->assign('alertbox', 'innerHTML', "<a href='editSpecies.php?sel=<{$rowMirror['taxon_ID_fk']}>' target='Species'>mirrored Hybrid already exists with ID {$rowMirror['taxon_ID_fk']}</a>");
        $response->script('$("#alertbox").css("background-color", "");');
        $response->script("$(\"[name='submitUpdate']\").css('visibility', 'visible');");
    } else {
        $response->assign('alertbox', 'innerHTML', "");
        $response->script('$("#alertbox").css("background-color", "");');
        $response->script("$(\"[name='submitUpdate']\").css('visibility', 'visible');");
    }

    return $response;
}


/**
 * register all jaxon-functions in this file
 */
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "checkParents");
$jaxon->processRequest();
