<?php
session_start();
require("../inc/connect7.php");
require_once("../inc/uuidMinterFunctions.php");
require_once("../inc/xajax/xajax_core/xajax.inc.php");

/** @var mysqli $dbLinkJacq */
$dbLinkJacq = new mysqli($_CONFIG['DATABASE']['JACQ']['host'],
                     $_CONFIG['DATABASE']['JACQ']['readonly']['user'],
                     $_CONFIG['DATABASE']['JACQ']['readonly']['pass'],
                     $_CONFIG['DATABASE']['JACQ']['name']);
if ($dbLinkJacq->connect_errno) {
    error_log("listTaxServer: Database jacq_input not available!");
}
$dbLinkJacq->set_charset('utf8');

/**
 * xajax-function toggleScientificNameLabel
 *
 * operates the switch for the name labels
 *
 * @param integer $id taxonID
 * @return xajaxResponse
 */
function toggleScientificNameLabel($id)
{
    /** @var mysqli $dbLink */
    global $dbLink;

    $id = intval($id);
    $constraint = "`taxonID` = $id AND `userID` = '" . $_SESSION['uid'] . "'";
    /** @var mysqli_result $result */
    $result = $dbLink->query("SELECT `uuid` FROM `tbl_labels_scientificName` WHERE $constraint");
    if ($result->num_rows > 0) {
        $dbLink->query("DELETE FROM `tbl_labels_scientificName` WHERE $constraint");
    } else  {
        $dbLink->query("INSERT INTO `tbl_labels_scientificName` SET
                         `uuid`    = '" . mint(1, $id) . "',
                         `taxonID` = $id,
                         `userID`  = '" . $_SESSION['uid'] . "'");
    }

    $objResponse = new xajaxResponse();
    return $objResponse;
}

/**
 * xajax-clearScientificNameLabels clearBarcodeLabels
 *
 * clears all switches for the name labels
 *
 * @return xajaxResponse
 */
function clearScientificNameLabels()
{
    /** @var mysqli $dbLink */
    global $dbLink;

    /** @var mysqli_result $result */
    $result = $dbLink->query("SELECT `taxonID`, `uuid` FROM `tbl_labels_scientificName` WHERE `userID` = '" . $_SESSION['uid'] . "'");
    $objResponse = new xajaxResponse();
    while ($row = $result->fetch_array()) {
        $id = $row['taxonID'];
        $objResponse->assign("cbScientificNameLabel_$id", 'checked', '');
        $dbLink->query("DELETE FROM `tbl_labels_scientificName` WHERE `taxonID` = '$id' AND `userID` = '".$_SESSION['uid']."'");
    }

    return $objResponse;
}


/**
 * register all xajax-functions in this file
 */
$xajax = new xajax();
$xajax->registerFunction("toggleScientificNameLabel");
$xajax->registerFunction("clearScientificNameLabels");
$xajax->processRequest();
