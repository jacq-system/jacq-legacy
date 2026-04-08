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
        $text = "<a href='editSpecies.php?sel=<{$row['taxon_ID_fk']}>' target='Species'>Hybrid formula already exists with ID {$row['taxon_ID_fk']}</a>";
        $alert = true;
    } elseif (!empty($rowMirror['taxon_ID_fk'])) {
        $text = "<a href='editSpecies.php?sel=<{$rowMirror['taxon_ID_fk']}>' target='Species'>Mirrored hybrid formula already exists with ID {$rowMirror['taxon_ID_fk']}</a>";
        $alert = false;
    } else {
        $text = "";
        $alert = false;
    }
    $response->assign('alertbox', 'innerHTML', $text);
    $response->script("$('#alertbox').css('background-color', '" . (($alert) ? 'OrangeRed' : '') . "');");
    if ($_SESSION['editorControl']) {
        $response->script("$(\"[name='submitUpdate']\").css('visibility', '" . (($alert) ? 'hidden' : 'visible') . "');");
    }

    return $response;
}


/**
 * register all jaxon-functions in this file
 */
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "checkParents");
$jaxon->processRequest();
