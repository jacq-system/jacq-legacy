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

    $rows = dbi_query("SELECT nsn.param1, ns.name, ns.url_head, ns.api_code, ns.serviceID
                       FROM tbl_nom_service_names nsn
                        INNER JOIN tbl_nom_service ns ON ns.serviceID = nsn.serviceID
                       WHERE nsn.taxonID = $taxonID
                        AND ns.api_code IS NOT NULL")
        ->fetch_all(MYSQLI_ASSOC);
    foreach ($rows as $row) {
        $labels[$row['serviceID']] = "<a href='{$row['url_head']}{$row['param1']}' title='{$row['name']}' target='_blank'>"
            . "<img src='webimages/nomService/{$row['api_code']}.png' alt='{$row['api_code']}' height='30px'>"
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
                        $row = dbi_query("SELECT ID
                                          FROM tbl_nom_service_log
                                          WHERE taxonID  = $taxonID 
                                           AND serviceID = '" . dbi_escape_string($result['serviceID']) . "'
                                           AND param     = '" . dbi_escape_string($result['match']['id']) . "'")
                               ->fetch_assoc();
                        if (empty($row)) {
                            dbi_query("INSERT INTO tbl_nom_service_log SET 
                                        taxonID   = $taxonID,
                                        serviceID = '" . dbi_escape_string($result['serviceID']) . "',
                                        param     = '" . dbi_escape_string($result['match']['id']) . "'");
                        }
                        $row = dbi_query("SELECT name, url_head, api_code, serviceID 
                                          FROM tbl_nom_service 
                                          WHERE serviceID = '" . dbi_escape_string($result['serviceID']) . "'")
                               ->fetch_assoc();
                        if (!empty($row)) {
                            $labels[$row['serviceID']] = "<a href='{$row['url_head']}{$result['match']['id']}' title='{$row['name']}' target='_blank'>"
                                                       . "<img src='webimages/nomService/{$row['api_code']}.png' alt='{$row['api_code']}' height='30px'>"
                                                       . "</a>";
                        }
                    } elseif (!empty($result['candidates'])) {
                        dbi_query("INSERT INTO tbl_nom_service_log SET 
                                    taxonID   = $taxonID,
                                    serviceID = '" . $result['serviceID'] . "',
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
