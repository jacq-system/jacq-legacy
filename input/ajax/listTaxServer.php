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
 * deprecated
 *
 * @param integer $id taxonID
 * @return xajaxResponse
 */
//function toggleScientificNameLabel($id)
//{
//    /** @var mysqli $dbLink */
//    global $dbLink;
//
//    $id = intval($id);
//    $constraint = "`taxonID` = $id AND `userID` = '" . $_SESSION['uid'] . "'";
//    /** @var mysqli_result $result */
//    $result = $dbLink->query("SELECT `uuid` FROM `tbl_labels_scientificName` WHERE $constraint");
//    if ($result->num_rows > 0) {
//        $dbLink->query("DELETE FROM `tbl_labels_scientificName` WHERE $constraint");
//    } else  {
//        $dbLink->query("INSERT INTO `tbl_labels_scientificName` SET
//                         `uuid`    = '" . mint(1, $id) . "',
//                         `taxonID` = $id,
//                         `userID`  = '" . $_SESSION['uid'] . "'");
//    }
//
//    $objResponse = new xajaxResponse();
//    return $objResponse;
//}
/**
 * xajax-function updateScientificNameLabel
 *
 * stores the number of labels to print for a given specimenID
 *
 * @param int $id taxonID
 * @param int $ctr
 * @return xajaxResponse
 */
function updateScientificNameLabel($id, $ctr)
{
    /** @var mysqli $dbLink */
    global $dbLink;

    $id  = intval($id);
    $ctr = intval($ctr);

    $constraint = "`taxonID` = $id AND `userID` = '" . $_SESSION['uid'] . "'";
    /** @var mysqli_result $result */
    $result = $dbLink->query("SELECT `uuid`, `nr` FROM `tbl_labels_scientificName` WHERE $constraint");
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if ($ctr) {
            $dbLink->query("UPDATE `tbl_labels_scientificName` SET `nr` = $ctr WHERE $constraint");
        } else {
            $dbLink->query("DELETE FROM `tbl_labels_scientificName` WHERE $constraint");
        }
    } else {
        $dbLink->query("INSERT INTO `tbl_labels_scientificName` SET
                         `uuid`    = '" . mint(1, $id) . "',
                         `taxonID` = $id,
                         `userID`  = '" . $_SESSION['uid'] . "',
                         `nr`      = $ctr");
    }
	$objResponse = new xajaxResponse();
	return $objResponse;
}

/**
 * xajax-clearScientificNameLabels clearBarcodeLabels
 *
 * clears all counters for the name labels
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
        $objResponse->assign("inpScientificNameLabel_$id", 'value', 0);
        $dbLink->query("DELETE FROM `tbl_labels_scientificName` WHERE `taxonID` = '$id' AND `userID` = '".$_SESSION['uid']."'");
    }

    return $objResponse;
}

/**
 * xajax-function set label counter of every shown line to 1
 *
 * @return xajaxResponse
 */
function setAll()
{
    /** @var mysqli $dbLink */
    global $dbLink;

    $objResponse = new xajaxResponse();
    if ($_SESSION['labelTaxSQL']) {
        /** @var mysqli_result $result */
        $result = $dbLink->query($_SESSION['labelTaxSQL']);
        while ($row = $result->fetch_array()) {
            $id = $row['taxonID'];
            $objResponse->assign("inpScientificNameLabel_$id", 'value', 1);

            $constraint = "`taxonID` = $id AND `userID` = '" . $_SESSION['uid'] . "'";
            /** @var mysqli_result $result2 */
            $result2 = $dbLink->query("SELECT `uuid`, `nr` FROM `tbl_labels_scientificName` WHERE $constraint");
            if ($result2->num_rows > 0) {
                $dbLink->query("UPDATE `tbl_labels_scientificName` SET `nr` = 1 WHERE $constraint");
            } else {
                $dbLink->query("INSERT INTO `tbl_labels_scientificName` SET
                                 `uuid`    = '" . mint(1, $id) . "',
                                 `taxonID` = $id,
                                 `userID`  = '" . $_SESSION['uid'] . "',
                                 `nr`      = 1");
            }
        }
    }
    return $objResponse;
}

/**
 * xajax-function clear label counter of every shown line
 *
 * @return xajaxResponse
 */
function clearAll()
{
    /** @var mysqli $dbLink */
    global $dbLink;

    $objResponse = new xajaxResponse();
    if ($_SESSION['labelTaxSQL']) {
        /** @var mysqli_result $result */
        $result = $dbLink->query($_SESSION['labelTaxSQL']);
        while ($row = $result->fetch_array()) {
            $id = intval($row['taxonID']);
            $objResponse->assign("inpScientificNameLabel_$id", 'value', 0);
            $dbLink->query("DELETE FROM `tbl_labels_scientificName` WHERE `taxonID` = $id AND `userID` = '" . $_SESSION['uid'] . "'");
        }

    }
    return $objResponse;
}


/**
 * register all xajax-functions in this file
 */
$xajax = new xajax();
$xajax->registerFunction("updateScientificNameLabel");
$xajax->registerFunction("clearScientificNameLabels");
$xajax->registerFunction("setAll");
$xajax->registerFunction("clearAll");
$xajax->processRequest();
