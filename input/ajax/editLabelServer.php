<?php
session_start();
require("../inc/connect.php");
require __DIR__ . '/../vendor/autoload.php';

use Jaxon\Jaxon;
use Jaxon\Response\Response;

/**
 * jaxon-function toggleTypeLabelMap
 *
 * operates the switch for the map type labels
 *
 * @return Response
 */
function toggleTypeLabelMap()
{
    $constraint = "specimen_ID=" . $_SESSION['labelSpecimen_ID'] . " AND userID='" . $_SESSION['uid'] . "'";
    $sql = "SELECT label FROM tbl_labels WHERE $constraint";
    $result = dbi_query($sql);
    if (mysqli_num_rows($result)>0) {
        $row = mysqli_fetch_array($result);
        $newLabel = ($row['label'] & 0x1) ? ($row['label'] & 0xfffe) : ($row['label'] | 1);
        if ($newLabel) {
            dbi_query("UPDATE tbl_labels SET label='$newLabel' WHERE $constraint");
        } else {
            dbi_query("DELETE FROM tbl_labels WHERE $constraint");
        }
    } else {
        $newLabel = 1;
        dbi_query("INSERT INTO tbl_labels SET label='$newLabel', specimen_ID=" . $_SESSION['labelSpecimen_ID'] . ", userID='" . $_SESSION['uid'] . "'");
    }

    $response = new Response();
    $response->assign("typeLabelMap", "innerHTML", ($newLabel & 0x1) ? "&nbsp;&radic;" : "&nbsp;&ndash;");
    $response->call("jaxon_checkTypeLabelMapPdfButton");
	return $response;
}

/**
 * jaxon-function toggleTypeLabelSpec
 *
 * operates the switch for the spec type labels
 *
 * @return Response
 */
function toggleTypeLabelSpec()
{
    $constraint = "specimen_ID=" . $_SESSION['labelSpecimen_ID'] . " AND userID='" . $_SESSION['uid']."'";
    $sql = "SELECT label FROM tbl_labels WHERE $constraint";
    $result = dbi_query($sql);
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_array($result);
        $newLabel = ($row['label'] & 0x2) ? ($row['label'] & 0xfffd) : ($row['label'] | 2);
        if ($newLabel) {
            dbi_query("UPDATE tbl_labels SET label='$newLabel' WHERE $constraint");
        } else {
            dbi_query("DELETE FROM tbl_labels WHERE $constraint");
        }
    } else {
        $newLabel = 2;
        dbi_query("INSERT INTO tbl_labels SET label='$newLabel', specimen_ID=" . $_SESSION['labelSpecimen_ID'] . ", userID='" . $_SESSION['uid'] . "'");
    }

    $response = new Response();
    $response->assign("typeLabelSpec", "innerHTML", ($newLabel & 0x2) ? "&nbsp;&radic;" : "&nbsp;&ndash;");
    $response->call("jaxon_checkTypeLabelSpecPdfButton");
	return $response;
}

/**
 * jaxon-function toggleBarcodeLabel
 *
 * operates the switch for the barcode labels
 *
 * @return Response
 */
function toggleBarcodeLabel()
{
    $constraint = "specimen_ID=" . $_SESSION['labelSpecimen_ID'] . " AND userID='" . $_SESSION['uid'] . "'";
    $sql = "SELECT label FROM tbl_labels WHERE $constraint";
    $result = dbi_query($sql);
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_array($result);
        $newLabel = ($row['label'] & 0x4) ? ($row['label'] & 0xfffb) : ($row['label'] | 4);
        if ($newLabel) {
            dbi_query("UPDATE tbl_labels SET label='$newLabel' WHERE $constraint");
        } else {
            dbi_query("DELETE FROM tbl_labels WHERE $constraint");
        }
    } else {
        $newLabel = 4;
        dbi_query("INSERT INTO tbl_labels SET label='$newLabel', specimen_ID=" . $_SESSION['labelSpecimen_ID'] . ", userID='" . $_SESSION['uid'] . "'");
    }

    $response = new Response();
    $response->assign("barcodeLabel", "innerHTML", ($newLabel & 0x4) ? "&nbsp;&radic;" : "&nbsp;&ndash;");
    $response->call("jaxon_checkBarcodeLabelPdfButton");
    return $response;
}

/**
 * jaxon-function clearTypeLabelsMap
 *
 * clears all switches for the map type labels
 *
 * @return Response
 */
function clearTypeLabelsMap()
{
    $sql = "SELECT specimen_ID, label FROM tbl_labels WHERE (label&1)>'0' AND userID='" . $_SESSION['uid'] . "'";
    $result = dbi_query($sql);
    while ($row = mysqli_fetch_array($result)) {
        $value = $row['label'] & 0xfffe;
        if ($value) {
            dbi_query("UPDATE tbl_labels SET label='$value' WHERE specimen_ID='" . $row['specimen_ID'] . "' AND userID='" . $_SESSION['uid'] . "'");
        } else {
            dbi_query("DELETE FROM tbl_labels WHERE specimen_ID='" . $row['specimen_ID'] . "' AND userID='" . $_SESSION['uid'] . "'");
        }
    }
    $response = new Response();
    $response->assign("cbTypeLabelMap", "checked", "");
    $response->assign("typeLabelMap", "innerHTML", "&nbsp;&ndash;");
    $response->call("jaxon_checkTypeLabelMapPdfButton()");
    return $response;
}

/**
 * jaxon-function clearTypeLabelsSpec
 *
 * clears all switches for the spec type labels
 *
 * @return Response
 */
function clearTypeLabelsSpec()
{
    $sql = "SELECT specimen_ID, label FROM tbl_labels WHERE (label&2)>'0' AND userID='" . $_SESSION['uid'] . "'";
    $result = dbi_query($sql);
    while ($row = mysqli_fetch_array($result)) {
        $value = $row['label'] & 0xfffd;
        if ($value) {
            dbi_query("UPDATE tbl_labels SET label='$value' WHERE specimen_ID='" . $row['specimen_ID'] . "' AND userID='" . $_SESSION['uid'] . "'");
        } else {
            dbi_query("DELETE FROM tbl_labels WHERE specimen_ID='" . $row['specimen_ID'] . "' AND userID='" . $_SESSION['uid'] . "'");
        }
    }
    $response = new Response();
    $response->assign("cbTypeLabelSpec", "checked", "");
    $response->assign("typeLabelSpec", "innerHTML", "&nbsp;&ndash;");
    $response->call("jaxon_checkTypeLabelSpecPdfButton()");
    return $response;
}

/**
 * jaxon-function clearBarcodeLabels
 *
 * clears all switches for the barcode labels
 *
 * @return Response
 */
function clearBarcodeLabels()
{
    $sql = "SELECT specimen_ID, label FROM tbl_labels WHERE (label&4)>'0' AND userID='" . $_SESSION['uid'] . "'";
    $result = dbi_query($sql);
    while ($row = mysqli_fetch_array($result)) {
        $value = $row['label'] & 0xfffb;
        if ($value) {
            dbi_query("UPDATE tbl_labels SET label='$value' WHERE specimen_ID='" . $row['specimen_ID'] . "' AND userID='" . $_SESSION['uid'] . "'");
        } else {
            dbi_query("DELETE FROM tbl_labels WHERE specimen_ID='" . $row['specimen_ID'] . "' AND userID='" . $_SESSION['uid'] . "'");
        }
    }
    $response = new Response();
    $response->assign("cbBarcodeLabel", "checked", "");
    $response->assign("barcodeLabel", "innerHTML", "&nbsp;&ndash;");
    $response->call("jaxon_checkBarcodeLabelPdfButton()");
    return $response;
}

/**
 * jaxon-function checkTypeLabelMapPdfButton
 *
 * checks if any map type labels are to be printed and activates the apropriate button
 *
 * @return Response
 */
function checkTypeLabelMapPdfButton()
{
    $sql = "SELECT label FROM tbl_labels WHERE (label&1)>'0' AND userID='" . $_SESSION['uid'] . "'";
    $result = dbi_query($sql);
    if (mysqli_num_rows($result) > 0) {
        $disabled = false;
    } else {
        $disabled = true;
    }

    $response = new Response();
    $response->assign("btMakeTypeLabelMapPdf", "disabled", $disabled);
    return $response;
}

/**
 * jaxon-function checkTypeLabelSpecPdfButton
 *
 * checks if any spec type labels are to be printed and activates the apropriate button
 *
 * @return Response
 */
function checkTypeLabelSpecPdfButton()
{
    $sql = "SELECT label FROM tbl_labels WHERE (label&2)>'0' AND userID='" . $_SESSION['uid'] . "'";
    $result = dbi_query($sql);
    if (mysqli_num_rows($result) > 0) {
        $disabled = false;
    } else {
        $disabled = true;
    }

    $response = new Response();
    $response->assign("btMakeTypeLabelSpecPdf", "disabled", $disabled);
    return $response;
}

/**
 * jaxon-function checkBarcodeLabelPdfButton
 *
 * checks if any spec type labels are to be printed and activates the apropriate button
 *
 * @return Response
 */
function checkBarcodeLabelPdfButton()
{
    $sql = "SELECT label FROM tbl_labels WHERE (label&4)>'0' AND userID='" . $_SESSION['uid'] . "'";
    $result = dbi_query($sql);
    if (mysqli_num_rows($result) > 0) {
        $disabled = false;
    } else {
        $disabled = true;
    }

    $response = new Response();
    $response->assign("btMakeBarcodeLabelPdf", "disabled", $disabled);
    return $response;
}

/**
 * jaxon-function updtStandardLabel
 *
 * stores the number of labels to print for a given specimenID
 *
 * @param int $ctr
 * @return Response
 */
function updtStandardLabel($ctr)
{
    $constraint = "specimen_ID=" . $_SESSION['labelSpecimen_ID'] . " AND userID='" . $_SESSION['uid'] . "'";
    $sql = "SELECT label FROM tbl_labels WHERE $constraint";
    $result = dbi_query($sql);
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_array($result);
        $newLabel = ($row['label'] & 0xff0f) + intval($ctr) * 0x10;
        if ($newLabel) {
            dbi_query("UPDATE tbl_labels SET label='$newLabel' WHERE $constraint");
        } else {
            dbi_query("DELETE FROM tbl_labels WHERE $constraint");
        }
    } else {
        $newLabel = intval($ctr) * 0x10;
        dbi_query("INSERT INTO tbl_labels SET label='$newLabel', specimen_ID=" . $_SESSION['labelSpecimen_ID'] . ", userID='" . $_SESSION['uid'] . "'");
    }
    $response = new Response();
    $response->assign("standardLabel", "innerHTML", ($newLabel & 0xf0) / 16);
    $response->call("jaxon_checkStandardLabelPdfButton");
    return $response;
}

/**
 * jaxon-function clearStandardLabels
 *
 * clears all switches for the standard labels
 *
 * @return Response
 */
function clearStandardLabels()
{
    $sql = "SELECT specimen_ID, label FROM tbl_labels WHERE (label&240)>'0' AND userID='" . $_SESSION['uid'] . "'";
    $result = dbi_query($sql);
    while ($row = mysqli_fetch_array($result)) {
        if ($row['label'] & 0xf0) {
            $value = $row['label'] & 0xff0f;
            if ($value) {
                dbi_query("UPDATE tbl_labels SET label='$value' WHERE specimen_ID='" . $row['specimen_ID'] . "' AND userID='" . $_SESSION['uid'] . "'");
            } else {
                dbi_query("DELETE FROM tbl_labels WHERE specimen_ID='" . $row['specimen_ID'] . "' AND userID='" . $_SESSION['uid'] . "'");
            }
        }
    }
    $response = new Response();
    $response->assign("inpStandardLabel", "value", "0");
    $response->assign("standardLabel", "innerHTML", "&nbsp;&ndash;");
    $response->call("jaxon_checkStandardLabelPdfButton()");
    return $response;
}

/**
 * jaxon-function checkStandardLabelPdfButton
 *
 * checks if any standard labels are to be printed and activates the apropriate button
 *
 * @return Response
 */
function checkStandardLabelPdfButton()
{
    $sql = "SELECT label FROM tbl_labels WHERE (label&240)>'0' AND userID='" . $_SESSION['uid'] . "'";
    $result = dbi_query($sql);
    $value = true;
    while ($row=mysqli_fetch_array($result)) {
        if ($row['label'] & 0xf0) {
            $value = false;
            break;
        }
    }
    $response = new Response();
    $response->assign("btMakeStandardLabelPdf", "disabled", $value);
    return $response;
}

/**
 * register all jaxon-functions in this file
 */
$jaxon = jaxon();
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "toggleTypeLabelMap");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "toggleTypeLabelSpec");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "toggleBarcodeLabel");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "clearTypeLabelsMap");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "clearTypeLabelsSpec");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "clearBarcodeLabels");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "checkTypeLabelMapPdfButton");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "checkTypeLabelSpecPdfButton");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "checkBarcodeLabelPdfButton");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "updtStandardLabel");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "clearStandardLabels");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "checkStandardLabelPdfButton");
$jaxon->processRequest();
