<?php
session_start();
require("../inc/connect.php");
require_once ("../inc/xajax/xajax_core/xajax.inc.php");

/**
 * xajax-function toggleTypeLabelMap
 *
 * operates the switch for the map type labels
 *
 * @return xajaxResponse
 */
function toggleTypeLabelMap() {
  $constraint = "specimen_ID=".$_SESSION['labelSpecimen_ID']." AND userID='".$_SESSION['uid']."'";
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
    mysql_query("INSERT INTO tbl_labels SET label='$newLabel', specimen_ID=".$_SESSION['labelSpecimen_ID'].", userID='".$_SESSION['uid']."'");
	}

  $objResponse = new xajaxResponse();
  $objResponse->assign("typeLabelMap", "innerHTML", ($newLabel & 0x1) ? "&nbsp;&radic;" : "&nbsp;&ndash;");
  $objResponse->call("xajax_checkTypeLabelMapPdfButton");
	return $objResponse;
}

/**
 * xajax-function toggleTypeLabelSpec
 *
 * operates the switch for the spec type labels
 *
 * @return xajaxResponse
 */
function toggleTypeLabelSpec() {
  $constraint = "specimen_ID=".$_SESSION['labelSpecimen_ID']." AND userID='".$_SESSION['uid']."'";
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
    mysql_query("INSERT INTO tbl_labels SET label='$newLabel', specimen_ID=".$_SESSION['labelSpecimen_ID'].", userID='".$_SESSION['uid']."'");
	}

  $objResponse = new xajaxResponse();
  $objResponse->assign("typeLabelSpec", "innerHTML", ($newLabel & 0x2) ? "&nbsp;&radic;" : "&nbsp;&ndash;");
  $objResponse->call("xajax_checkTypeLabelSpecPdfButton");
	return $objResponse;
}

/**
 * xajax-function toggleBarcodeLabel
 *
 * operates the switch for the barcode labels
 *
 * @param integer $id specimen_ID
 * @return xajaxResponse
 */
function toggleBarcodeLabel() {
  $constraint = "specimen_ID=".$_SESSION['labelSpecimen_ID']." AND userID='".$_SESSION['uid']."'";
  $sql = "SELECT label FROM tbl_labels WHERE $constraint";
  $result = mysql_query($sql);
  if (mysql_num_rows($result)>0) {
    $row = mysql_fetch_array($result);
    $newLabel = ($row['label'] & 0x4) ? ($row['label'] & 0xfffb) : ($row['label'] | 4);
    if ($newLabel)
      mysql_query("UPDATE tbl_labels SET label='$newLabel' WHERE $constraint");
    else
      mysql_query("DELETE FROM tbl_labels WHERE $constraint");
  }
  else  {
    $newLabel = 4;
    mysql_query("INSERT INTO tbl_labels SET label='$newLabel', specimen_ID=".intval($id).", userID='".$_SESSION['uid']."'");
  }

  $objResponse = new xajaxResponse();
  $objResponse->assign("barcodeLabel", "innerHTML", ($newLabel & 0x4) ? "&nbsp;&radic;" : "&nbsp;&ndash;");
  $objResponse->call("xajax_checkBarcodeLabelPdfButton");
  return $objResponse;
}

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
  $objResponse->assign("cbTypeLabelMap", "checked", "");
  $objResponse->assign("typeLabelMap", "innerHTML", "&nbsp;&ndash;");
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
  $objResponse->assign("cbTypeLabelSpec", "checked", "");
  $objResponse->assign("typeLabelSpec", "innerHTML", "&nbsp;&ndash;");
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
  $sql = "SELECT specimen_ID, label FROM tbl_labels WHERE (label&4)>'0' AND userID='".$_SESSION['uid']."'";
  $result = mysql_query($sql);
  while ($row=mysql_fetch_array($result)) {
    $value = $row['label'] & 0xfffb;
    if ($value)
      mysql_query("UPDATE tbl_labels SET label='$value' WHERE specimen_ID='".$row['specimen_ID']."' AND userID='".$_SESSION['uid']."'");
    else
      mysql_query("DELETE FROM tbl_labels WHERE specimen_ID='".$row['specimen_ID']."' AND userID='".$_SESSION['uid']."'");
  }
  $objResponse = new xajaxResponse();
  $objResponse->assign("cbBarcodeLabel", "checked", "");
  $objResponse->assign("barcodeLabel", "innerHTML", "&nbsp;&ndash;");
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
function checkBarcodeLabelPdfButton() {
  $sql = "SELECT label FROM tbl_labels WHERE (label&4)>'0' AND userID='".$_SESSION['uid']."'";
  $result = mysql_query($sql);
  if (mysql_num_rows($result)>0)
    $disabled = false;
  else
    $disabled = true;

  $objResponse = new xajaxResponse();
  $objResponse->assign("btMakeBarcodeLabelPdf", "disabled", $disabled);
  return $objResponse;
}

/**
 * xajax-function updtStandardLabel
 *
 * stores the number of labels to print for a given specimenID
 *
 * @param int $ctr
 * @return xajaxResponse
 */
function updtStandardLabel($ctr) {
  $constraint = "specimen_ID=".$_SESSION['labelSpecimen_ID']." AND userID='".$_SESSION['uid']."'";
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
    mysql_query("INSERT INTO tbl_labels SET label='$newLabel', specimen_ID=".$_SESSION['labelSpecimen_ID'].", userID='".$_SESSION['uid']."'");
  }
  $objResponse = new xajaxResponse();
  $objResponse->assign("standardLabel", "innerHTML", ($newLabel & 0xf0) / 16);
  $objResponse->call("xajax_checkStandardLabelPdfButton");
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
  $objResponse->assign("inpStandardLabel", "value", "0");
  $objResponse->assign("standardLabel", "innerHTML", "&nbsp;&ndash;");
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
 * register all xajax-functions in this file
 */
$xajax = new xajax();
$xajax->registerFunction("toggleTypeLabelMap");
$xajax->registerFunction("toggleTypeLabelSpec");
$xajax->registerFunction("toggleBarcodeLabel");
$xajax->registerFunction("clearTypeLabelsMap");
$xajax->registerFunction("clearTypeLabelsSpec");
$xajax->registerFunction("clearBarcodeLabels");
$xajax->registerFunction("checkTypeLabelMapPdfButton");
$xajax->registerFunction("checkTypeLabelSpecPdfButton");
$xajax->registerFunction("checkBarcodeLabelPdfButton");
$xajax->registerFunction("updtStandardLabel");
$xajax->registerFunction("clearStandardLabels");
$xajax->registerFunction("checkStandardLabelPdfButton");
$xajax->processRequest();