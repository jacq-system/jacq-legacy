<?php
session_start();
require("../inc/connect.php");
require("../inc/herbardb_input_functions.php");
require __DIR__ . '/../vendor/autoload.php';

use Jaxon\Jaxon;
use Jaxon\Response\Response;

$jaxon = jaxon();

$response = new Response();

function updateNomService($taxonID)
{
    global $response, $_CONFIG;

    $taxonID = intval($taxonID);
    $labels = array();

    $rows = dbi_query("SELECT param1, provider, url_head, serviceID
                       FROM herbar_view.view_taxon_link_service         
                       WHERE taxonID = $taxonID")
        ->fetch_all(MYSQLI_ASSOC);
    foreach ($rows as $row) {
        $labels[$row['serviceID']] = "<a href='{$row['url_head']}{$row['param1']}' title='{$row['provider']}' target='_blank'>"
            . "<img src='webimages/nomService/serviceID{$row['serviceID']}_logo.png' alt='{$row['provider']}' height='30px'>"
            . "</a>";
    }

    $sciname = getScientificName($taxonID, false, false, false);
    $curl = curl_init($_CONFIG['JACQ_SERVICES'] . "externalScinames/find/" . rawurlencode($sciname));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $curl_response = curl_exec($curl);
    curl_close($curl);
    if ($curl_response !== false) {
        $res = json_decode($curl_response, true);
        if (!empty($res['results'])) {
            foreach ($res['results'] as $result) {
                if (empty($result['error']) && !empty($result['serviceID']) && empty($labels[$result['serviceID']])) {
                    if (!empty($result['match']['id'])) {
                        dbi_query("INSERT INTO tbl_nom_service_names SET 
                                    taxonID   = $taxonID,
                                    serviceID = " . intval($result['serviceID']) . ",
                                    param1    = '" . dbi_escape_string($result['match']['id']) . "',
                                    auto      = 1");
                        $row = dbi_query("SELECT name, url_head, serviceID 
                                          FROM tbl_nom_service 
                                          WHERE serviceID = " . intval($result['serviceID']))
                               ->fetch_assoc();
                        if (!empty($row)) {
                            $labels[$row['serviceID']] = "<a href='{$row['url_head']}{$result['match']['id']}' title='{$row['name']}' target='_blank'>"
                                                       . "<img src='webimages/nomService/serviceID{$row['serviceID']}_logo.png' alt='{$row['name']}' height='30px'>"
                                                       . "</a>";
                        }
                    } elseif (!empty($result['candidates'])) {
                        dbi_query("INSERT INTO tbl_nom_service_log SET 
                                    taxonID   = $taxonID,
                                    serviceID = " . intval($result['serviceID']) . ",
                                    error     = '" . dbi_escape_string($sciname) . ": No match but multiple candidates found.'");
                    }
                }
            }
        }
    }
    $response->assign('nomService', 'innerHTML', implode("&nbsp;", $labels));

    return $response;
}


/**
 * register all jaxon-functions in this file
 */
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "updateNomService");
$jaxon->processRequest();
