<?php
session_start();
require_once ("../inc/xajax/xajax_core/xajax.inc.php");
require("../inc/connect.php");

$xajax = new xajax();

$objResponse = new xajaxResponse();

function listInstitutions($formData)
{
    global $objResponse;

    $text = "<option value=\"0\">--- all ---</option>\n";

    $sql = "SELECT source_name, tbl_management_collections.source_id
            FROM tbl_management_collections, herbarinput.meta, tbl_img_definition
            WHERE tbl_management_collections.source_id = herbarinput.meta.source_id
             AND tbl_management_collections.source_id = tbl_img_definition.source_id_fk
             AND imgserver_IP = " . quoteString($formData['serverIP']) . "
            GROUP BY source_name ORDER BY source_name";
    $result = db_query($sql);
    while ($row = mysql_fetch_array($result)) {
        $text .= "<option value=\"{$row['source_id']}\"";
        if ($_POST['source_id'] == $row['source_id']) $text .=  " selected";
        $text .=  ">{$row['source_name']}</option>\n";
    }

    $objResponse->assign('source_id', 'innerHTML', $text);

    return $objResponse;
}

function rescanPictureServer($formData)
{
    global $objResponse;

    if ($formData['serverIP'] == "131.130.131.9") {
        $url = "http://" . $formData['serverIP'] . "/database/scanPicturesStart.php?key=DKsuuewwqsa32czucuwqdb576i12";
        $dummy = @file_get_contents($url, "r");

        $sql = "SELECT ID, start
                FROM herbar_pictures.scans
                WHERE finish IS NULL
                 AND IP = '" . mysql_real_escape_string($formData['serverIP']) . "'";
        $result = mysql_query($sql);
        $bla = mysql_error();
        if (mysql_num_rows($result) > 0) {
            $row = mysql_fetch_array($result);
            $objResponse->alert("scan in progress (started " . $row['start'] . " UTC)");
        }
    } else {
        $objResponse->alert("scanning is not implemented on this server");
    }

    getLastScan($formData);

    return $objResponse;
}

function getLastScan($formData)
{
    global $objResponse;

    if ($formData['serverIP'] == "131.130.131.9") {
        $sql = "SELECT start, finish
                FROM herbar_pictures.scans
                WHERE IP = '" . mysql_real_escape_string($formData['serverIP']) . "'
                ORDER BY start DESC
                LIMIT 1";
        $result = mysql_query($sql);
        if (mysql_num_rows($result) > 0) {
            $row = mysql_fetch_array($result);
            if (!$row['finish']) {
                $response = "scan in progress (started " . $row['start'] . " UTC)";
            } else {
                $response = "last scan " . $row['start'] . " UTC";
            }

        } else {
            $response = "no scan yet";
        }
    } else {
        $response = "scanning is not implemented on this server";
    }

    $objResponse->assign('lastScan', 'innerHTML', $response);

    return $objResponse;
}
/**
 * register all xajax-functions in this file
 */
$xajax->registerFunction("listInstitutions");
$xajax->registerFunction("rescanPictureServer");
$xajax->registerFunction("getLastScan");
$xajax->processRequest();