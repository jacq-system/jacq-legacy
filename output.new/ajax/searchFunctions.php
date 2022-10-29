<?php

use Jacq\DbAccess;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * generate the Collection-dropdown (called by ajax_lp.php)
 *
 * @param string $source_name form-value of 'source_name'
 * @return mixed select-list (json-encoded)
 */
function getCollection($source_name)
{
    $dbLnk2 = DbAccess::ConnectTo('OUTPUT');

    if (trim($source_name)) {
        $sql = "SELECT collection
                FROM tbl_management_collections, meta
                WHERE tbl_management_collections.source_id = meta.source_id
                 AND source_name = '" . $dbLnk2->real_escape_string($source_name) . "'
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
    $result = $dbLnk2->query($sql);
    $selectData = "<option value=''>Choose Collection</option>\n";
    while ($row = $result->fetch_array()) {
        $selectData .= "<option value='" . htmlspecialchars($row['collection']) . "'>" . htmlspecialchars($row['collection']) . "</option>\n";
    }

    return json_encode($selectData);
}

/**
 * generate the Country-dropdown (called by ajax_lp.php)
 *
 * @param string $geo_general form-value of 'geo_general'
 * @param string $geo_region form-value of 'geo_region'
 * @return mixed select-list (json-encoded)
 */
function getCountry($geo_general, $geo_region)
{
    $dbLnk2 = DbAccess::ConnectTo('OUTPUT');

    if (trim($geo_general) || trim($geo_region)) {
        $sql = "SELECT nation_engl
                FROM tbl_geo_nation, tbl_geo_region
                WHERE tbl_geo_nation.regionID_fk = tbl_geo_region.regionID ";
        if ($geo_general) {
            $sql .= "AND geo_general = '" . $dbLnk2->real_escape_string($geo_general) . "' ";
        }
        if ($geo_region) {
            $sql .= "AND geo_region = '" . $dbLnk2->real_escape_string($geo_region) . "' ";
        }
        $sql .= "ORDER BY nation_engl";
        $result = $dbLnk2->query($sql);
        $selectData = "<select size='1' id='ajax_nation_engl' name='nation_engl'>\n".
                      "<option value=''>Choose Country</option>\n";
        while ($row = $result->fetch_array()) {
            $selectData .= "<option value='" . htmlspecialchars($row['nation_engl']) . "'>".htmlspecialchars($row['nation_engl']) . "</option>\n";
        }
        $selectData .= "</select>";
    } else {
        $selectData = "";
    }

    return json_encode($selectData);
}

/**
 * generate the Province-dropdown (called by ajax_lp.php)
 *
 * @param string $nation_engl form-value of 'nation_engl'
 * @return mixed select-list (json-encoded)
 */
function getProvince($nation_engl)
{
    $dbLnk2 = DbAccess::ConnectTo('OUTPUT');

    if (trim($nation_engl)) {
        $sql = "SELECT provinz
                FROM tbl_geo_province, tbl_geo_nation
                WHERE tbl_geo_province.nationID = tbl_geo_nation.nationID
                 AND nation_engl = '".$dbLnk2->real_escape_string($nation_engl)."'
                ORDER BY provinz";
        $result = $dbLnk2->query($sql);
        $selectData = "<select placeholder='State/Province' id='ajax_provinz' name='provinz'>\n"
                    . "<option value=''>Choose State/Province</option>\n";
        while ($row = $result->fetch_array()) {
            $selectData .= "<option value='" . htmlspecialchars($row['provinz']) . "'>" . htmlspecialchars($row['provinz']) . "</option>\n";
        }
        $selectData .= "</select>";
    } else {
        $selectData = "";
    }

    return json_encode($selectData);
}
