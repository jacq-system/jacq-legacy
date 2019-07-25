<?php
require("../inc/dev-functions.php");
require_once ("../inc/xajax/xajax.inc.php");

/**
 * connects to the used database
 */
function connect() {
    // Dummy function for compatibility reasons
}

/**
 * xajax-function to generate the Collection-dropdown
 *
 * @param array $data form-value of 'source_name'
 * @return xajaxResponse
 */
function getCollection($data)
{
    global $dbLink;

    connect();

    if (trim($data['source_name'])) {
        $sql = "SELECT collection
            FROM tbl_management_collections, meta
            WHERE tbl_management_collections.source_id=meta.source_id
             AND source_name='" . $dbLink->real_escape_string($data['source_name']) . "'
            ORDER BY collection";
    } else {
        $sql = "SELECT `collection`
                FROM `tbl_management_collections`
                WHERE `collectionID`
                IN (
                    SELECT DISTINCT `collectionID`
                    FROM `tbl_specimens`
                )
                ORDER BY `collection`";
    }
    $result = $dbLink->query($sql);
    $selectData = "<option value=\"\">Choose Collection</option>\n";
    while ($row = $result->fetch_array()) {
        $selectData .= "<option value=\"" . htmlspecialchars($row['collection']) . "\">" . htmlspecialchars($row['collection']) . "</option>\n";
    }

    $objResponse = new xajaxResponse();
    $objResponse->addAssign("ajax_collection", "innerHTML", $selectData);
    return $objResponse;
}

/**
 * xajax-function to generate the Country-dropdown
 *
 * @param array $data form-value of both 'geo_general' and 'geo_region'
 * @return xajaxResponse
 */
function getCountry($data) {
    global $dbLink;

    connect();

    if (trim($data['geo_general']) || trim($data['geo_region'])) {
        $sql = "SELECT nation_engl
                FROM tbl_geo_nation, tbl_geo_region
                WHERE tbl_geo_nation.regionID_fk=tbl_geo_region.regionID ";
        if ($data['geo_general']) {
            $sql .= "AND geo_general='" . $dbLink->real_escape_string($data['geo_general']) . "' ";
        }
        if ($data['geo_region']) {
            $sql .= "AND geo_region='" . $dbLink->real_escape_string($data['geo_region']) . "' ";
        }
        $sql .= "ORDER BY nation_engl";
        $result = $dbLink->query($sql);
        $selectData = "<select size=\"1\" name=\"nation_engl\">\n".
                      "<option value=\"\">Choose Country</option>\n";
        while ($row = $result->fetch_array()) {
            $selectData .= "<option value=\"" . htmlspecialchars($row['nation_engl']) . "\">".htmlspecialchars($row['nation_engl']) . "</option>\n";
        }
        $selectData .= "</select>\n";
        $selectData .= "<label for=\"nation_engl\">Country</label>\n";
    }
    else {
        $selectData = "<input placeholder=\"Country\" type=\"text\" name=\"nation_engl\">\n<label for=\"nation_engl\">Country</label>";
    }

    $objResponse = new xajaxResponse();
    $objResponse->addAssign("ajax_nation_engl", "innerHTML", $selectData);
    return $objResponse;
}

/**
 * xajax-function to generate the Province-dropdown
 *
 * @param array $data form-value of 'nation_engl'
 * @return xajaxResponse
 */
function getProvince($data) {
    global $dbLink;

    connect();

    if (trim($data['nation_engl'])) {
        $sql = "SELECT provinz
                FROM tbl_geo_province, tbl_geo_nation
                WHERE tbl_geo_province.nationID=tbl_geo_nation.nationID
                 AND nation_engl='".$dbLink->real_escape_string($data['nation_engl'])."'
                ORDER BY provinz";
        $result = $dbLink->query($sql);
        $selectData = "<select placeholder=\"State/Province\" name=\"provinz\">\n<option value=\"\">Choose State/Province</option>\n";
        while ($row = $result->fetch_array()) {
            $selectData .= "<option value=\"" . htmlspecialchars($row['provinz']) . "\">" . htmlspecialchars($row['provinz']) . "</option>\n";
        }
        $selectData .= "</select>\n";
        $selectData .= "<label for=\"provinz\">State/Province</label>";
    }
    else {
        $selectData = "<input placeholder=\"State/Province\" type=\"text\" name=\"provinz\">";
        $selectData .= "<label for=\"provinz\">State/Province</label>";
    }

    $objResponse = new xajaxResponse();
    $objResponse->addAssign("ajax_provinz", "innerHTML", $selectData);
    return $objResponse;
}

/**
 * register all xajax-functions in this file
 */
$xajax = new xajax();
$xajax->registerFunction("getCollection");
$xajax->registerFunction("getCountry");
$xajax->registerFunction("getProvince");
$xajax->processRequests();
