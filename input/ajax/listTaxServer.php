<?php
session_start();
require("../inc/connect.php");
require_once("../inc/uuidMinterFunctions.php");
require __DIR__ . '/../vendor/autoload.php';

use Jaxon\Jaxon;
use Jaxon\Response\Response;

/**
 * jaxon-function toggleScientificNameLabel
 *
 * operates the switch for the name labels
 * deprecated
 *
 * @param integer $id taxonID
 * @return Response
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
//    $response = new Response();
//    return $response;
//}
/**
 * jaxon-function updateScientificNameLabel
 *
 * stores the number of labels to print for a given specimenID
 *
 * @param int $id taxonID
 * @param int $ctr
 * @return Response
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
        if ($ctr) {
            $dbLink->query("UPDATE `tbl_labels_scientificName` SET `nr` = $ctr WHERE $constraint");
        } else {
            $dbLink->query("DELETE FROM `tbl_labels_scientificName` WHERE $constraint");
        }
    } else {
        $dbLink->query("INSERT INTO `tbl_labels_scientificName` SET
                         `uuid`    = '" . getUUIDfromTaxonID($id) . "',
                         `taxonID` = $id,
                         `userID`  = '" . $_SESSION['uid'] . "',
                         `nr`      = $ctr");
    }
	$response = new Response();
	return $response;
}

/**
 * jaxon-clearScientificNameLabels clearBarcodeLabels
 *
 * clears all counters for the name labels
 *
 * @return Response
 */
function clearScientificNameLabels()
{
    /** @var mysqli $dbLink */
    global $dbLink;

    /** @var mysqli_result $result */
    $result = $dbLink->query("SELECT `taxonID`, `uuid` FROM `tbl_labels_scientificName` WHERE `userID` = '" . $_SESSION['uid'] . "'");
    $response = new Response();
    while ($row = $result->fetch_array()) {
        $id = $row['taxonID'];
        $response->assign("inpScientificNameLabel_$id", 'value', 0);
        $dbLink->query("DELETE FROM `tbl_labels_scientificName` WHERE `taxonID` = '$id' AND `userID` = '".$_SESSION['uid']."'");
    }

    return $response;
}

/**
 * jaxon-function set label counter of every shown line to 1
 *
 * @return Response
 */
function setAll()
{
    /** @var mysqli $dbLink */
    global $dbLink;

    $response = new Response();
    if ($_SESSION['labelTaxSQL']) {
        /** @var mysqli_result $result */
        $result = $dbLink->query($_SESSION['labelTaxSQL']);
        while ($row = $result->fetch_array()) {
            $id = $row['taxonID'];
            $response->assign("inpScientificNameLabel_$id", 'value', 1);

            $constraint = "`taxonID` = $id AND `userID` = '" . $_SESSION['uid'] . "'";
            /** @var mysqli_result $result2 */
            $result2 = $dbLink->query("SELECT `uuid`, `nr` FROM `tbl_labels_scientificName` WHERE $constraint");
            if ($result2->num_rows > 0) {
                $dbLink->query("UPDATE `tbl_labels_scientificName` SET `nr` = 1 WHERE $constraint");
            } else {
                $dbLink->query("INSERT INTO `tbl_labels_scientificName` SET
                                 `uuid`    = '" . getUUIDfromTaxonID($id) . "',
                                 `taxonID` = $id,
                                 `userID`  = '" . $_SESSION['uid'] . "',
                                 `nr`      = 1");
            }
        }
    }
    return $response;
}

/**
 * jaxon-function clear label counter of every shown line
 *
 * @return Response
 */
function clearAll()
{
    /** @var mysqli $dbLink */
    global $dbLink;

    $response = new Response();
    if ($_SESSION['labelTaxSQL']) {
        /** @var mysqli_result $result */
        $result = $dbLink->query($_SESSION['labelTaxSQL']);
        while ($row = $result->fetch_array()) {
            $id = intval($row['taxonID']);
            $response->assign("inpScientificNameLabel_$id", 'value', 0);
            $dbLink->query("DELETE FROM `tbl_labels_scientificName` WHERE `taxonID` = $id AND `userID` = '" . $_SESSION['uid'] . "'");
        }

    }
    return $response;
}


/**
 * register all jaxon-functions in this file
 */
$jaxon = jaxon();
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "updateScientificNameLabel");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "clearScientificNameLabels");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "setAll");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "clearAll");
$jaxon->processRequest();
