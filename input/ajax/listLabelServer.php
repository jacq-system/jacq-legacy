<?php
session_start();
require("../inc/connect.php");
require __DIR__ . '/../vendor/autoload.php';

use Jaxon\Jaxon;
use Jaxon\Response\Response;


function makeDropdownInstitution() {

    $selectData = "<select size=\"1\" name=\"collection\">\n"
                . "  <option value=\"0\"></option>\n";

    $sql = "SELECT source_id, source_code FROM herbarinput.meta ORDER BY source_code";
    $result = db_query($sql);
    while($row=mysql_fetch_array($result)) {
        $selectData .= "  <option value=\"-".htmlspecialchars($row['source_id'])."\"";
        if ($_SESSION['labelCollection'] == $row['source_id']) {
            $selectData .= " selected";
        }
        $selectData .= ">".htmlspecialchars($row['source_code'])."</option>\n";
    }

    $selectData .= "  </select>\n";

    $response = new Response();
    $response->assign("lblInstitutionCollection", "innerHTML", '&nbsp;<b>Institution:</b>');
    $response->assign("drpInstitutionCollection", "innerHTML", $selectData);
    return $response;
}

function makeDropdownCollection() {

    $selectData = "<select size=\"1\" name=\"collection\">\n"
                . "  <option value=\"0\"></option>\n";

    $sql = "SELECT collectionID, collection FROM tbl_management_collections ORDER BY collection";
    $result = db_query($sql);
    while($row=mysql_fetch_array($result)) {
        $selectData .= "  <option value=\"".htmlspecialchars($row['collectionID'])."\"";
        if ($_SESSION['labelCollection'] == $row['collectionID']) {
            $selectData .= " selected";
        }
        $selectData .= ">".htmlspecialchars($row['collection'])."</option>\n";
    }

    $selectData .= "  </select>\n";

    $response = new Response();
    $response->assign("lblInstitutionCollection", "innerHTML", '&nbsp;<b>Collection:</b>');
    $response->assign("drpInstitutionCollection", "innerHTML", $selectData);
    return $response;
}

function changeDropdownCollectionQR($source_id) {

    $selectData = "  <option value=\"0\"></option>\n";

    $sql = "SELECT collectionID, collection
            FROM tbl_management_collections
            WHERE source_id = '" . abs(intval($source_id)) . "'
            ORDER BY collection";
    $result = db_query($sql);
    while($row=mysql_fetch_array($result)) {
        $selectData .= "  <option value='" . htmlspecialchars($row['collectionID']) . "'>" . htmlspecialchars($row['collection']) . "</option>\n";
    }

    $response = new Response();
    $response->assign("collection_QR", "innerHTML", $selectData);
    return $response;
}

/**
 * jaxon-function toggleTypeLabelMap
 *
 * operates the switch for the map type labels
 *
 * @param integer $id specimen_ID
 * @return jaxonResponse
 */
function toggleTypeLabelMap($id) {
    $constraint = "specimen_ID=".intval($id)." AND userID='".$_SESSION['uid']."'";
    $sql = "SELECT label FROM tbl_labels WHERE $constraint";
    $result = mysql_query($sql);
    if (mysql_num_rows($result)>0) {
        $row = mysql_fetch_array($result);
        $newLabel = ($row['label'] & 0x1) ? ($row['label'] & 0xfffe) : ($row['label'] | 1);
        if ($newLabel) {
            mysql_query("UPDATE tbl_labels SET label='$newLabel' WHERE $constraint");
        } else {
            mysql_query("DELETE FROM tbl_labels WHERE $constraint");
        }
    } else {
        $newLabel = 1;
        mysql_query("INSERT INTO tbl_labels SET label='$newLabel', specimen_ID=".intval($id).", userID='".$_SESSION['uid']."'");
    }

    $response = new Response();
    $response->call("jaxon_checkTypeLabelMapPdfButton");
    return $response;
}

/**
 * jaxon-function toggleTypeLabelSpec
 *
 * operates the switch for the spec type labels
 *
 * @param int $id specimen_ID
 * @return Response
 */
function toggleTypeLabelSpec($id) {
    $constraint = "specimen_ID=".intval($id)." AND userID='".$_SESSION['uid']."'";
    $sql = "SELECT label FROM tbl_labels WHERE $constraint";
    $result = mysql_query($sql);
    if (mysql_num_rows($result)>0) {
        $row = mysql_fetch_array($result);
        $newLabel = ($row['label'] & 0x2) ? ($row['label'] & 0xfffd) : ($row['label'] | 2);
        if ($newLabel) {
            mysql_query("UPDATE tbl_labels SET label='$newLabel' WHERE $constraint");
        } else {
            mysql_query("DELETE FROM tbl_labels WHERE $constraint");
        }
    } else {
        $newLabel = 2;
        mysql_query("INSERT INTO tbl_labels SET label='$newLabel', specimen_ID=".intval($id).", userID='".$_SESSION['uid']."'");
    }

    $response = new Response();
    $response->call("jaxon_checkTypeLabelSpecPdfButton");
	return $response;
}

/**
 * jaxon-function toggleBarcodeLabel
 *
 * operates the switch for the barcode labels
 *
 * @param integer $id specimen_ID
 * @return Response
 */
function toggleBarcodeLabel($id) {
    $constraint = "specimen_ID=".intval($id)." AND userID='".$_SESSION['uid']."'";
    $sql = "SELECT label FROM tbl_labels WHERE $constraint";
    $result = mysql_query($sql);
    if (mysql_num_rows($result)>0) {
        $row = mysql_fetch_array($result);
        $newLabel = ($row['label'] & 0x4) ? ($row['label'] & 0xfffb) : ($row['label'] | 4);
        if ($newLabel) {
            mysql_query("UPDATE tbl_labels SET label='$newLabel' WHERE $constraint");
        } else {
            mysql_query("DELETE FROM tbl_labels WHERE $constraint");
        }
    } else {
        $newLabel = 4;
        mysql_query("INSERT INTO tbl_labels SET label='$newLabel', specimen_ID=".intval($id).", userID='".$_SESSION['uid']."'");
    }

    $response = new Response();
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
function clearTypeLabelsMap() {
    $sql = "SELECT specimen_ID, label FROM tbl_labels WHERE (label&1)>'0' AND userID='".$_SESSION['uid']."'";
    $result = mysql_query($sql);
    while ($row=mysql_fetch_array($result)) {
        $value = $row['label'] & 0xfffe;
        if ($value) {
            mysql_query("UPDATE tbl_labels SET label='$value' WHERE specimen_ID='".$row['specimen_ID']."' AND userID='".$_SESSION['uid']."'");
        } else {
            mysql_query("DELETE FROM tbl_labels WHERE specimen_ID='".$row['specimen_ID']."' AND userID='".$_SESSION['uid']."'");
        }
    }

    $response = new Response();
    if ($_SESSION['labelSQL']) {
        $result = db_query($_SESSION['labelSQL']);
        while ($row=mysql_fetch_array($result)) {
            $id = $row['specimen_ID'];
            if ($row['typusID']) {
                $response->assign("cbTypeLabelMap_$id", 'checked', '');
            }
        }
    }
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
function clearTypeLabelsSpec() {
    $sql = "SELECT specimen_ID, label FROM tbl_labels WHERE (label&2)>'0' AND userID='".$_SESSION['uid']."'";
    $result = mysql_query($sql);
    while ($row=mysql_fetch_array($result)) {
        $value = $row['label'] & 0xfffd;
        if ($value) {
            mysql_query("UPDATE tbl_labels SET label='$value' WHERE specimen_ID='".$row['specimen_ID']."' AND userID='".$_SESSION['uid']."'");
        } else {
            mysql_query("DELETE FROM tbl_labels WHERE specimen_ID='".$row['specimen_ID']."' AND userID='".$_SESSION['uid']."'");
        }
    }

    $response = new Response();
    if ($_SESSION['labelSQL']) {
        $result = db_query($_SESSION['labelSQL']);
        while ($row=mysql_fetch_array($result)) {
            $id = $row['specimen_ID'];
            if ($row['typusID']) {
                $response->assign("cbTypeLabelSpec_$id", 'checked', '');
            }
        }
    }
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
function clearBarcodeLabels() {
    $sql = "SELECT specimen_ID, label FROM tbl_labels WHERE (label&4)>'0' AND userID='".$_SESSION['uid']."'";
    $result = mysql_query($sql);
    while ($row=mysql_fetch_array($result)) {
        $value = $row['label'] & 0xfffb;
        if ($value) {
            mysql_query("UPDATE tbl_labels SET label='$value' WHERE specimen_ID='".$row['specimen_ID']."' AND userID='".$_SESSION['uid']."'");
        } else {
            mysql_query("DELETE FROM tbl_labels WHERE specimen_ID='".$row['specimen_ID']."' AND userID='".$_SESSION['uid']."'");
        }
    }

    $response = new Response();
    if ($_SESSION['labelSQL']) {
        $result = db_query($_SESSION['labelSQL']);
        while ($row=mysql_fetch_array($result)) {
            $id = $row['specimen_ID'];
            $response->assign("cbBarcodeLabel_$id", 'checked', '');
        }
    }
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
function checkTypeLabelMapPdfButton() {
    $sql = "SELECT label FROM tbl_labels WHERE (label&1)>'0' AND userID='".$_SESSION['uid']."'";
    $result = mysql_query($sql);
    if (mysql_num_rows($result) > 0) {
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
function checkTypeLabelSpecPdfButton() {
    $sql = "SELECT label FROM tbl_labels WHERE (label&2)>'0' AND userID='".$_SESSION['uid']."'";
    $result = mysql_query($sql);
    if (mysql_num_rows($result) > 0) {
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
function checkBarcodeLabelPdfButton() {
    $sql = "SELECT label FROM tbl_labels WHERE (label&4)>'0' AND userID='".$_SESSION['uid']."'";
    $result = mysql_query($sql);
    if (mysql_num_rows($result) > 0) {
        $disabled = false;
    } else {
        $disabled = true;
    }

    $response = new Response();
    $response->assign("btMakeBarcodeLabelPdf", "disabled", $disabled);
    $response->assign("btMakeQRCodeLabelPdf", "disabled", $disabled);
    return $response;
}

/**
 * jaxon-function updtStandardLabel
 *
 * stores the number of labels to print for a given specimenID
 *
 * @param int $id specimen_ID
 * @param int $ctr
 * @return Response
 */
function updtStandardLabel($id, $ctr) {
    $constraint = "specimen_ID=".intval($id)." AND userID='".$_SESSION['uid']."'";
    $sql = "SELECT label FROM tbl_labels WHERE $constraint";
    $result = mysql_query($sql);
    if (mysql_num_rows($result) > 0) {
        $row = mysql_fetch_array($result);
        $newLabel = ($row['label'] & 0xff0f) + intval($ctr) * 0x10;
        if ($newLabel) {
            mysql_query("UPDATE tbl_labels SET label='$newLabel' WHERE $constraint");
        } else {
            mysql_query("DELETE FROM tbl_labels WHERE $constraint");
        }
    } else {
        $newLabel = intval($ctr) * 0x10;
        mysql_query("INSERT INTO tbl_labels SET label='$newLabel', specimen_ID=".intval($id).", userID='".$_SESSION['uid']."'");
    }
	$response = new Response();
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
function clearStandardLabels() {

    $sql = "SELECT specimen_ID, label FROM tbl_labels WHERE (label&240)>'0' AND userID='".$_SESSION['uid']."'";
    $result = mysql_query($sql);
    while ($row=mysql_fetch_array($result)) {
        if ($row['label'] & 0xf0) {
            $value = $row['label'] & 0xff0f;
            if ($value) {
                mysql_query("UPDATE tbl_labels SET label='$value' WHERE specimen_ID='".$row['specimen_ID']."' AND userID='".$_SESSION['uid']."'");
            } else {
                mysql_query("DELETE FROM tbl_labels WHERE specimen_ID='".$row['specimen_ID']."' AND userID='".$_SESSION['uid']."'");
            }
        }
    }

    $response = new Response();
    if ($_SESSION['labelSQL']) {
        $result = db_query($_SESSION['labelSQL']);
        while ($row=mysql_fetch_array($result)) {
            $id = $row['specimen_ID'];
            $response->assign("inpSL_$id", 'value', 0);
        }
    }
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
function checkStandardLabelPdfButton() {

    $sql = "SELECT label FROM tbl_labels WHERE (label&240)>'0' AND userID='".$_SESSION['uid']."'";
    $result = mysql_query($sql);
    $value = true;
    while ($row=mysql_fetch_array($result)) {
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
 * jaxon-function check everything, set standard labels to 1
 *
 * @return Response
 */
function setAll() {
    $response = new Response();
    if ($_SESSION['labelSQL']) {
        $result = db_query($_SESSION['labelSQL']);
        while ($row=mysql_fetch_array($result)) {
            $id = $row['specimen_ID'];
            $response->assign("inpSL_$id", 'value', 1);
            $response->assign("cbBarcodeLabel_$id", 'checked', 'checked');
            if ($row['typusID']) {
                $response->assign("cbTypeLabelMap_$id", 'checked', 'checked');
                $response->assign("cbTypeLabelSpec_$id", 'checked', 'checked');
                $newLabel = 0x17;
            } else {
                $newLabel = 0x14;
            }

            $constraint = "specimen_ID=".intval($id)." AND userID='".$_SESSION['uid']."'";
            $result2 = mysql_query("SELECT label FROM tbl_labels WHERE $constraint");
            if (mysql_num_rows($result2) > 0) {
                mysql_query("UPDATE tbl_labels SET label='$newLabel' WHERE $constraint");
            } else {
                mysql_query("INSERT INTO tbl_labels SET label='$newLabel', specimen_ID=".intval($id).", userID='".$_SESSION['uid']."'");
            }
        }

        $response->call("jaxon_checkTypeLabelMapPdfButton");
        $response->call("jaxon_checkTypeLabelSpecPdfButton");
        $response->call("jaxon_checkStandardLabelPdfButton");
        $response->call("jaxon_checkBarcodeLabelPdfButton");
    }
    return $response;
}

/**
 * jaxon-function clear everything, set standard labels to 0
 *
 * @return Response
 */
function clearAll() {
    $response = new Response();
    if ($_SESSION['labelSQL']) {
        $result = db_query($_SESSION['labelSQL']);
        while ($row=mysql_fetch_array($result)) {
            $id = $row['specimen_ID'];
            $response->assign("inpSL_$id", 'value', 0);
            $response->assign("cbBarcodeLabel_$id", 'checked', '');
            if ($row['typusID']) {
                $response->assign("cbTypeLabelMap_$id", 'checked', '');
                $response->assign("cbTypeLabelSpec_$id", 'checked', '');
            }

            mysql_query("DELETE FROM tbl_labels WHERE specimen_ID=".intval($id)." AND userID='".$_SESSION['uid']."'");
        }

        $response->call("jaxon_checkTypeLabelMapPdfButton");
        $response->call("jaxon_checkTypeLabelSpecPdfButton");
        $response->call("jaxon_checkStandardLabelPdfButton");
        $response->call("jaxon_checkBarcodeLabelPdfButton");
    }
    return $response;
}


/**
 * register all jaxon-functions in this file
 */
$jaxon = jaxon();
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "makeDropdownInstitution");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "makeDropdownCollection");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "changeDropdownCollectionQR");
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
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "setAll");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "clearAll");
$jaxon->processRequest();