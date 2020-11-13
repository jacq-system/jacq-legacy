<?php
session_start();
require("../inc/connect.php");
require_once ("../inc/xajax/xajax_core/xajax.inc.php");


function makeDropdownInstitution() {

  $selectData = "<select size=\"1\" name=\"collection\">\n"
              . "  <option value=\"0\"></option>\n";

  $sql = "SELECT source_id, source_code FROM herbarinput.meta ORDER BY source_code";
  $result = db_query($sql);
  while($row=mysql_fetch_array($result)) {
    $selectData .= "  <option value=\"-".htmlspecialchars($row['source_id'])."\"";
    if ($_SESSION['labelCollection']==$row['source_id']) $selectData .= " selected";
    $selectData .= ">".htmlspecialchars($row['source_code'])."</option>\n";
  }

  $selectData .= "  </select>\n";

  $objResponse = new xajaxResponse();
  $objResponse->assign("lblInstitutionCollection", "innerHTML", '&nbsp;<b>Institution:</b>');
  $objResponse->assign("drpInstitutionCollection", "innerHTML", $selectData);
  return $objResponse;
}

function makeDropdownCollection() {

  $selectData = "<select size=\"1\" name=\"collection\">\n"
              . "  <option value=\"0\"></option>\n";

  $sql = "SELECT collectionID, collection FROM tbl_management_collections ORDER BY collection";
  $result = db_query($sql);
  while($row=mysql_fetch_array($result)) {
    $selectData .= "  <option value=\"".htmlspecialchars($row['collectionID'])."\"";
    if ($_SESSION['labelCollection']==$row['collectionID']) $selectData .= " selected";
    $selectData .= ">".htmlspecialchars($row['collection'])."</option>\n";
  }

  $selectData .= "  </select>\n";

  $objResponse = new xajaxResponse();
  $objResponse->assign("lblInstitutionCollection", "innerHTML", '&nbsp;<b>Collection:</b>');
  $objResponse->assign("drpInstitutionCollection", "innerHTML", $selectData);
  return $objResponse;
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

  $objResponse = new xajaxResponse();
  $objResponse->assign("collection_QR", "innerHTML", $selectData);
  return $objResponse;
}

/**
 * xajax-function toggleTypeLabelMap
 *
 * operates the switch for the map type labels
 *
 * @param integer $id specimen_ID
 * @return xajaxResponse
 */
function toggleTypeLabelMap($id) {
  $constraint = "specimen_ID=".intval($id)." AND userID='".$_SESSION['uid']."'";
  $sql = "SELECT label FROM tbl_labels WHERE $constraint";
  $result = mysql_query($sql);
  if (mysql_num_rows($result)>0) {
    $row = mysql_fetch_array($result);
    $newLabel = ($row['label'] & 0x1) ? ($row['label'] & 0xfffe) : ($row['label'] | 1);
    if ($newLabel)
      mysql_query("UPDATE tbl_labels SET label='$newLabel' WHERE $constraint");
    else
      mysql_query("DELETE FROM tbl_labels WHERE $constraint");
	}
	else  {
	  $newLabel = 1;
    mysql_query("INSERT INTO tbl_labels SET label='$newLabel', specimen_ID=".intval($id).", userID='".$_SESSION['uid']."'");
	}

  $objResponse = new xajaxResponse();
  $objResponse->call("xajax_checkTypeLabelMapPdfButton");
	return $objResponse;
}

/**
 * xajax-function toggleTypeLabelSpec
 *
 * operates the switch for the spec type labels
 *
 * @param int $id specimen_ID
 * @return xajaxResponse
 */
function toggleTypeLabelSpec($id) {
  $constraint = "specimen_ID=".intval($id)." AND userID='".$_SESSION['uid']."'";
  $sql = "SELECT label FROM tbl_labels WHERE $constraint";
  $result = mysql_query($sql);
  if (mysql_num_rows($result)>0) {
    $row = mysql_fetch_array($result);
    $newLabel = ($row['label'] & 0x2) ? ($row['label'] & 0xfffd) : ($row['label'] | 2);
    if ($newLabel)
      mysql_query("UPDATE tbl_labels SET label='$newLabel' WHERE $constraint");
    else
      mysql_query("DELETE FROM tbl_labels WHERE $constraint");
	}
	else  {
	  $newLabel = 2;
    mysql_query("INSERT INTO tbl_labels SET label='$newLabel', specimen_ID=".intval($id).", userID='".$_SESSION['uid']."'");
	}

  $objResponse = new xajaxResponse();
  $objResponse->call("xajax_checkTypeLabelSpecPdfButton");
	return $objResponse;
}

/**
 * xajax-function toggleBarcodeLabel
 *
 * operates the switch for the barcode labels
 * deprecated
 *
 * @param integer $id specimen_ID
 * @return xajaxResponse
 */
//function toggleBarcodeLabel($id) {
//  $constraint = "specimen_ID=".intval($id)." AND userID='".$_SESSION['uid']."'";
//  $sql = "SELECT label FROM tbl_labels WHERE $constraint";
//  $result = mysql_query($sql);
//  if (mysql_num_rows($result)>0) {
//    $row = mysql_fetch_array($result);
//    $newLabel = ($row['label'] & 0x4) ? ($row['label'] & 0xfffb) : ($row['label'] | 4);
//    if ($newLabel)
//      mysql_query("UPDATE tbl_labels SET label='$newLabel' WHERE $constraint");
//    else
//      mysql_query("DELETE FROM tbl_labels WHERE $constraint");
//  }
//  else  {
//    $newLabel = 4;
//    mysql_query("INSERT INTO tbl_labels SET label='$newLabel', specimen_ID=".intval($id).", userID='".$_SESSION['uid']."'");
//  }
//
//  $objResponse = new xajaxResponse();
//  $objResponse->call("xajax_checkBarcodeLabelPdfButton");
//  return $objResponse;
//}

/**
 * xajax-function clearTypeLabelsMap
 *
 * clears all switches for the map type labels
 *
 * @return xajaxResponse
 */
function clearTypeLabelsMap() {
  $sql = "SELECT specimen_ID, label FROM tbl_labels WHERE (label&1)>'0' AND userID='".$_SESSION['uid']."'";
  $result = mysql_query($sql);
  while ($row=mysql_fetch_array($result)) {
    $value = $row['label'] & 0xfffe;
    if ($value)
      mysql_query("UPDATE tbl_labels SET label='$value' WHERE specimen_ID='".$row['specimen_ID']."' AND userID='".$_SESSION['uid']."'");
    else
      mysql_query("DELETE FROM tbl_labels WHERE specimen_ID='".$row['specimen_ID']."' AND userID='".$_SESSION['uid']."'");
  }

  $objResponse = new xajaxResponse();
  if ($_SESSION['labelSQL']) {
    $result = db_query($_SESSION['labelSQL']);
    while ($row=mysql_fetch_array($result)) {
      $id = $row['specimen_ID'];
      if ($row['typusID']) {
        $objResponse->assign("cbTypeLabelMap_$id", 'checked', '');
      }
    }
  }
  $objResponse->call("xajax_checkTypeLabelMapPdfButton()");
  return $objResponse;
}

/**
 * xajax-function clearTypeLabelsSpec
 *
 * clears all switches for the spec type labels
 *
 * @return xajaxResponse
 */
function clearTypeLabelsSpec() {
  $sql = "SELECT specimen_ID, label FROM tbl_labels WHERE (label&2)>'0' AND userID='".$_SESSION['uid']."'";
  $result = mysql_query($sql);
  while ($row=mysql_fetch_array($result)) {
    $value = $row['label'] & 0xfffd;
    if ($value)
      mysql_query("UPDATE tbl_labels SET label='$value' WHERE specimen_ID='".$row['specimen_ID']."' AND userID='".$_SESSION['uid']."'");
    else
      mysql_query("DELETE FROM tbl_labels WHERE specimen_ID='".$row['specimen_ID']."' AND userID='".$_SESSION['uid']."'");
  }

  $objResponse = new xajaxResponse();
  if ($_SESSION['labelSQL']) {
    $result = db_query($_SESSION['labelSQL']);
    while ($row=mysql_fetch_array($result)) {
      $id = $row['specimen_ID'];
      if ($row['typusID']) {
        $objResponse->assign("cbTypeLabelSpec_$id", 'checked', '');
      }
    }
  }
  $objResponse->call("xajax_checkTypeLabelSpecPdfButton()");
  return $objResponse;
}

/**
 * xajax-function clearBarcodeLabels
 *
 * clears all switches for the barcode labels
 *
 * @return xajaxResponse
 */
function clearBarcodeLabels() {
    $sql = "SELECT specimen_ID, label FROM tbl_labels WHERE (label & 3840) > '0' AND userID = '" . $_SESSION['uid'] . "'";
    $result = mysql_query($sql);
    while ($row = mysql_fetch_array($result)) {
        if ($row['label'] & 0xf00) {
            $value = $row['label'] & 0xf0ff;
            if ($value) {
                mysql_query("UPDATE tbl_labels SET label = '$value' WHERE specimen_ID = '" . $row['specimen_ID'] . "' AND userID = '" . $_SESSION['uid'] . "'");
            } else {
                mysql_query("DELETE FROM tbl_labels WHERE specimen_ID = '" . $row['specimen_ID'] . "' AND userID = '" . $_SESSION['uid'] . "'");
            }
        }
    }

    $objResponse = new xajaxResponse();
    if ($_SESSION['labelSQL']) {
        $result = db_query($_SESSION['labelSQL']);
        while ($row = mysql_fetch_array($result)) {
            $id = $row['specimen_ID'];
            $objResponse->assign("inpBarcodeLabel_$id", 'value', 0);
        }
    }
    $objResponse->call("xajax_checkBarcodeLabelPdfButton()");
    return $objResponse;
}

/**
 * xajax-function checkTypeLabelMapPdfButton
 *
 * checks if any map type labels are to be printed and activates the apropriate button
 *
 * @return xajaxResponse
 */
function checkTypeLabelMapPdfButton() {
  $sql = "SELECT label FROM tbl_labels WHERE (label&1)>'0' AND userID='".$_SESSION['uid']."'";
  $result = mysql_query($sql);
  if (mysql_num_rows($result)>0)
    $disabled = false;
  else
    $disabled = true;

  $objResponse = new xajaxResponse();
  $objResponse->assign("btMakeTypeLabelMapPdf", "disabled", $disabled);
  return $objResponse;
}

/**
 * xajax-function checkTypeLabelSpecPdfButton
 *
 * checks if any spec type labels are to be printed and activates the apropriate button
 *
 * @return xajaxResponse
 */
function checkTypeLabelSpecPdfButton() {
  $sql = "SELECT label FROM tbl_labels WHERE (label&2)>'0' AND userID='".$_SESSION['uid']."'";
  $result = mysql_query($sql);
  if (mysql_num_rows($result)>0)
    $disabled = false;
  else
    $disabled = true;

  $objResponse = new xajaxResponse();
  $objResponse->assign("btMakeTypeLabelSpecPdf", "disabled", $disabled);
  return $objResponse;
}

/**
 * xajax-function checkBarcodeLabelPdfButton
 *
 * checks if any spec type labels are to be printed and activates the apropriate button
 *
 * @return xajaxResponse
 */
function checkBarcodeLabelPdfButton()
{
    $sql = "SELECT label FROM tbl_labels WHERE (label&3840) > '0' AND userID = '" . $_SESSION['uid'] . "'";
    $result = mysql_query($sql);
    $value = true;
    while ($row = mysql_fetch_array($result)) {
        if ($row['label'] & 0xf00) {
            $value = false;
            break;
        }
    }

    $objResponse = new xajaxResponse();
    $objResponse->assign("btMakeBarcodeLabelPdf", "disabled", $value);
    return $objResponse;
}

/**
 * xajax-function updtStandardLabel
 *
 * stores the number of labels to print for a given specimenID
 *
 * @param int $id specimen_ID
 * @param int $ctr
 * @return xajaxResponse
 */
function updtStandardLabel($id, $ctr) {
  $constraint = "specimen_ID=".intval($id)." AND userID='".$_SESSION['uid']."'";
  $sql = "SELECT label FROM tbl_labels WHERE $constraint";
  $result = mysql_query($sql);
  if (mysql_num_rows($result)>0) {
    $row = mysql_fetch_array($result);
    $newLabel = ($row['label'] & 0xff0f) + intval($ctr) * 0x10;
    if ($newLabel)
      mysql_query("UPDATE tbl_labels SET label='$newLabel' WHERE $constraint");
    else
      mysql_query("DELETE FROM tbl_labels WHERE $constraint");
  }
  else {
    $newLabel = intval($ctr) * 0x10;
    mysql_query("INSERT INTO tbl_labels SET label='$newLabel', specimen_ID=".intval($id).", userID='".$_SESSION['uid']."'");
  }
	$objResponse = new xajaxResponse();
  $objResponse->call("xajax_checkStandardLabelPdfButton");
	return $objResponse;
}

/**
 * xajax-function updtBarcodeLabel
 *
 * stores the number of barcode labels to print for a given specimenID
 *
 * @param int $id specimen_ID
 * @param int $ctr
 * @return xajaxResponse
 */
function updtBarcodeLabel($id, $ctr) {
    $constraint = "specimen_ID = " . intval($id) . " AND userID = '" . $_SESSION['uid']."'";
    $sql = "SELECT label FROM tbl_labels WHERE $constraint";
    $result = mysql_query($sql);
    if (mysql_num_rows($result) > 0) {
        $row = mysql_fetch_array($result);
        $newLabel = ($row['label'] & 0xf0ff) + intval($ctr) * 0x100;
        if ($newLabel) {
            mysql_query("UPDATE tbl_labels SET label = '$newLabel' WHERE $constraint");
        } else {
           mysql_query("DELETE FROM tbl_labels WHERE $constraint");
        }
    } else {
        $newLabel = intval($ctr) * 0x100;
        mysql_query("INSERT INTO tbl_labels SET label = '$newLabel', specimen_ID = " . intval($id) . ", userID = '" . $_SESSION['uid'] . "'");
    }
    $objResponse = new xajaxResponse();
    $objResponse->call("xajax_checkBarcodeLabelPdfButton");
    return $objResponse;
}

/**
 * xajax-function clearStandardLabels
 *
 * clears all switches for the standard labels
 *
 * @return xajaxResponse
 */
function clearStandardLabels() {

  $sql = "SELECT specimen_ID, label FROM tbl_labels WHERE (label&240)>'0' AND userID='".$_SESSION['uid']."'";
  $result = mysql_query($sql);
  while ($row=mysql_fetch_array($result)) {
    if ($row['label'] & 0xf0) {
      $value = $row['label'] & 0xff0f;
      if ($value)
        mysql_query("UPDATE tbl_labels SET label='$value' WHERE specimen_ID='".$row['specimen_ID']."' AND userID='".$_SESSION['uid']."'");
      else
        mysql_query("DELETE FROM tbl_labels WHERE specimen_ID='".$row['specimen_ID']."' AND userID='".$_SESSION['uid']."'");
    }
  }

  $objResponse = new xajaxResponse();
  if ($_SESSION['labelSQL']) {
    $result = db_query($_SESSION['labelSQL']);
    while ($row=mysql_fetch_array($result)) {
      $id = $row['specimen_ID'];
      $objResponse->assign("inpSL_$id", 'value', 0);
    }
  }
  $objResponse->call("xajax_checkStandardLabelPdfButton()");
  return $objResponse;
}

/**
 * xajax-function checkStandardLabelPdfButton
 *
 * checks if any standard labels are to be printed and activates the apropriate button
 *
 * @return xajaxResponse
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
	$objResponse = new xajaxResponse();
  $objResponse->assign("btMakeStandardLabelPdf", "disabled", $value);
  return $objResponse;
}

/**
 * xajax-function check everything, set standard labels to 1
 *
 * @return xajaxResponse
 */
function setAll() {
    $objResponse = new xajaxResponse();
    if ($_SESSION['labelSQL']) {
        $result = db_query($_SESSION['labelSQL']);
        while ($row=mysql_fetch_array($result)) {
            $id = $row['specimen_ID'];
            $objResponse->assign("inpSL_$id", 'value', 1);
            $objResponse->assign("inpBarcodeLabel_$id", 'value', 1);
            if ($row['typusID']) {
                $objResponse->assign("cbTypeLabelMap_$id", 'checked', 'checked');
                $objResponse->assign("cbTypeLabelSpec_$id", 'checked', 'checked');
                $newLabel = 0x113;
            } else {
                $newLabel = 0x110;
            }

            $constraint = "specimen_ID = " . intval($id) . " AND userID = '" . $_SESSION['uid'] . "'";
            $result2 = mysql_query("SELECT label FROM tbl_labels WHERE $constraint");
            if (mysql_num_rows($result2) > 0) {
                mysql_query("UPDATE tbl_labels SET label = '$newLabel' WHERE $constraint");
            } else {
                mysql_query("INSERT INTO tbl_labels SET label = '$newLabel', specimen_ID = ".intval($id).", userID = '".$_SESSION['uid']."'");
            }
        }

        $objResponse->call("xajax_checkTypeLabelMapPdfButton");
        $objResponse->call("xajax_checkTypeLabelSpecPdfButton");
        $objResponse->call("xajax_checkStandardLabelPdfButton");
        $objResponse->call("xajax_checkBarcodeLabelPdfButton");
    }
    return $objResponse;
}

/**
 * xajax-function clear everything, set standard labels to 0
 *
 * @return xajaxResponse
 */
function clearAll() {
    $objResponse = new xajaxResponse();
    if ($_SESSION['labelSQL']) {
        $result = db_query($_SESSION['labelSQL']);
        while ($row = mysql_fetch_array($result)) {
            $id = $row['specimen_ID'];
            $objResponse->assign("inpSL_$id", 'value', 0);
            $objResponse->assign("inpBarcodeLabel_$id", 'value', 0);
            if ($row['typusID']) {
                $objResponse->assign("cbTypeLabelMap_$id", 'checked', '');
                $objResponse->assign("cbTypeLabelSpec_$id", 'checked', '');
            }

          mysql_query("DELETE FROM tbl_labels WHERE specimen_ID = " . intval($id) . " AND userID = '" . $_SESSION['uid'] . "'");
        }

        $objResponse->call("xajax_checkTypeLabelMapPdfButton");
        $objResponse->call("xajax_checkTypeLabelSpecPdfButton");
        $objResponse->call("xajax_checkStandardLabelPdfButton");
        $objResponse->call("xajax_checkBarcodeLabelPdfButton");
    }
    return $objResponse;
}


/**
 * register all xajax-functions in this file
 */
$xajax = new xajax();
$xajax->registerFunction("makeDropdownInstitution");
$xajax->registerFunction("makeDropdownCollection");
$xajax->registerFunction("changeDropdownCollectionQR");
$xajax->registerFunction("toggleTypeLabelMap");
$xajax->registerFunction("toggleTypeLabelSpec");
$xajax->registerFunction("clearTypeLabelsMap");
$xajax->registerFunction("clearTypeLabelsSpec");
$xajax->registerFunction("clearBarcodeLabels");
$xajax->registerFunction("checkTypeLabelMapPdfButton");
$xajax->registerFunction("checkTypeLabelSpecPdfButton");
$xajax->registerFunction("checkBarcodeLabelPdfButton");
$xajax->registerFunction("updtStandardLabel");
$xajax->registerFunction("updtBarcodeLabel");
$xajax->registerFunction("clearStandardLabels");
$xajax->registerFunction("checkStandardLabelPdfButton");
$xajax->registerFunction("setAll");
$xajax->registerFunction("clearAll");
$xajax->processRequest();
