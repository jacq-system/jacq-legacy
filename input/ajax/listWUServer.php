<?php
session_start();
require("../inc/connect.php");
require __DIR__ . '/../vendor/autoload.php';

use Jaxon\Jaxon;
use Jaxon\Response\Response;

function makeDropdownInstitution()
{
    $selectData = "<select size=\"1\" name=\"collection\">\n"
                . "  <option value=\"0\"></option>\n";

    $sql = "SELECT source_id, source_code FROM herbarinput.meta ORDER BY source_code";
    $result = dbi_query($sql);
    while($row = mysqli_fetch_array($result)) {
        $selectData .= "  <option value=\"-".htmlspecialchars($row['source_id'])."\"";
        if ($_SESSION['wuCollection'] == $row['source_id']) {
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
  $result = dbi_query($sql);
  while($row=mysqli_fetch_array($result)) {
    $selectData .= "  <option value=\"".htmlspecialchars($row['collectionID'])."\"";
    if ($_SESSION['wuCollection']==$row['collectionID']) $selectData .= " selected";
    $selectData .= ">".htmlspecialchars($row['collection'])."</option>\n";
  }

  $selectData .= "  </select>\n";

  $response = new Response();
  $response->assign("lblInstitutionCollection", "innerHTML", '&nbsp;<b>Collection:</b>');
  $response->assign("drpInstitutionCollection", "innerHTML", $selectData);
  return $response;
}

/**
 * jaxon-function getUserDate
 *
 * sets the Date-dropdown for a given user
 *
 * @return Response
 */
function getUserDate($id) {

    $sql = "SELECT DATE_FORMAT(timestamp,'%Y-%m-%d') as date
            FROM herbarinput_log.log_specimens ";
    if (intval($id) > 0) {
        $sql .= "WHERE userID='" . intval($id) . "' ";
    }
    $sql .= "GROUP BY date
             ORDER BY date";
    $result = dbi_query($sql);
    $selectData = "";
    while($row = mysqli_fetch_array($result)) {
        $selectData .= "  <option>" . htmlspecialchars($row['date']) . "</option>\n";
    }

    $response = new Response();
    $response->assign("user_date", "innerHTML", $selectData);
    return $response;
}

/**
 * jaxon-function toggleTypeLabelMap
 *
 * operates the switch for the map type labels
 *
 * @param integer $id specimen_ID
 * @return Response
 */
function toggleTypeLabelMap($id) {
  $constraint = "specimen_ID=".intval($id)." AND userID='".$_SESSION['uid']."'";
  $sql = "SELECT label FROM tbl_labels WHERE $constraint";
  $result = dbi_query($sql);
  if (mysqli_num_rows($result)>0) {
    $row = mysqli_fetch_array($result);
    $newLabel = ($row['label'] & 0x1) ? ($row['label'] & 0xfffe) : ($row['label'] | 1);
    if ($newLabel)
      dbi_query("UPDATE tbl_labels SET label='$newLabel' WHERE $constraint");
    else
      dbi_query("DELETE FROM tbl_labels WHERE $constraint");
	}
	else  {
	  $newLabel = 1;
    dbi_query("INSERT INTO tbl_labels SET label='$newLabel', specimen_ID=".intval($id).", userID='".$_SESSION['uid']."'");
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
  $result = dbi_query($sql);
  if (mysqli_num_rows($result)>0) {
    $row = mysqli_fetch_array($result);
    $newLabel = ($row['label'] & 0x2) ? ($row['label'] & 0xfffd) : ($row['label'] | 2);
    if ($newLabel)
      dbi_query("UPDATE tbl_labels SET label='$newLabel' WHERE $constraint");
    else
      dbi_query("DELETE FROM tbl_labels WHERE $constraint");
	}
	else  {
	  $newLabel = 2;
    dbi_query("INSERT INTO tbl_labels SET label='$newLabel', specimen_ID=".intval($id).", userID='".$_SESSION['uid']."'");
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
  $result = dbi_query($sql);
  if (mysqli_num_rows($result)>0) {
    $row = mysqli_fetch_array($result);
    $newLabel = ($row['label'] & 0x4) ? ($row['label'] & 0xfffb) : ($row['label'] | 4);
    if ($newLabel)
      dbi_query("UPDATE tbl_labels SET label='$newLabel' WHERE $constraint");
    else
      dbi_query("DELETE FROM tbl_labels WHERE $constraint");
  }
  else  {
    $newLabel = 4;
    dbi_query("INSERT INTO tbl_labels SET label='$newLabel', specimen_ID=".intval($id).", userID='".$_SESSION['uid']."'");
  }

  $response = new Response();
  $response->call("jaxon_checkBarcodeLabelPdfButton");
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
  $result = dbi_query($sql);
  if (mysqli_num_rows($result)>0)
    $disabled = false;
  else
    $disabled = true;

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
  $result = dbi_query($sql);
  if (mysqli_num_rows($result)>0)
    $disabled = false;
  else
    $disabled = true;

  $response = new Response();
  $response->assign("btMakeTypeLabelSpecPdf", "disabled", $disabled);
  return $response;
}

/**
 * jaxon-function checkTypeLabelSpecPdfButton
 *
 * checks if any spec type labels are to be printed and activates the apropriate button
 *
 * @return Response
 */
function checkBarcodeLabelPdfButton() {
  $sql = "SELECT label FROM tbl_labels WHERE (label&4)>'0' AND userID='".$_SESSION['uid']."'";
  $result = dbi_query($sql);
  if (mysqli_num_rows($result)>0)
    $disabled = false;
  else
    $disabled = true;

  $response = new Response();
  $response->assign("btMakeBarcodeLabelPdf", "disabled", $disabled);
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
  $result = dbi_query($sql);
  if (mysqli_num_rows($result)>0) {
    $row = mysqli_fetch_array($result);
    $newLabel = ($row['label'] & 0xff0f) + intval($ctr) * 0x10;
    if ($newLabel)
      dbi_query("UPDATE tbl_labels SET label='$newLabel' WHERE $constraint");
    else
      dbi_query("DELETE FROM tbl_labels WHERE $constraint");
  }
  else {
    $newLabel = intval($ctr) * 0x10;
    dbi_query("INSERT INTO tbl_labels SET label='$newLabel', specimen_ID=".intval($id).", userID='".$_SESSION['uid']."'");
  }
	$response = new Response();
  $response->call("jaxon_checkStandardLabelPdfButton");
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
 * jaxon-function check everything, set standard labels to 1
 *
 * @return Response
 */
function setAll() {
  $response = new Response();
  $searchDate = dbi_escape_string(trim($_SESSION['sLabelDate']));
  $sql = "SELECT ls.specimenID, s.typusID, l.label
          FROM (herbarinput_log.log_specimens ls, tbl_specimens s)
           LEFT JOIN tbl_labels l ON (ls.specimenID=l.specimen_ID AND ls.userID=l.userID)
          WHERE ls.specimenID=s.specimen_ID
           AND ls.userID='".intval($_SESSION['uid'])."'
           AND ls.timestamp BETWEEN '$searchDate' AND ADDDATE('$searchDate','1')
          GROUP BY ls.specimenID
          ORDER BY ls.timestamp";
  $result = dbi_query($sql);
  while ($row=mysqli_fetch_array($result)) {
    $id = $row['specimenID'];
    $response->assign("inpSL_$id", 'value', 1);
    $response->assign("cbBarcodeLabel_$id", 'checked', 'checked');
    if ($row['typusID']) {
      $response->assign("cbTypeLabelMap_$id", 'checked', 'checked');
      $response->assign("cbTypeLabelSpec_$id", 'checked', 'checked');
      $newLabel = 0x17;
    }
    else
      $newLabel = 0x14;

    $constraint = "specimen_ID=".intval($id)." AND userID='".$_SESSION['uid']."'";
    $result2 = dbi_query("SELECT label FROM tbl_labels WHERE $constraint");
    if (mysqli_num_rows($result2)>0)
      dbi_query("UPDATE tbl_labels SET label='$newLabel' WHERE $constraint");
    else
      dbi_query("INSERT INTO tbl_labels SET label='$newLabel', specimen_ID=".intval($id).", userID='".$_SESSION['uid']."'");
  }

  $response->call("jaxon_checkTypeLabelMapPdfButton");
  $response->call("jaxon_checkTypeLabelSpecPdfButton");
  $response->call("jaxon_checkStandardLabelPdfButton");
  $response->call("jaxon_checkBarcodeLabelPdfButton");
  return $response;
}

/**
 * jaxon-function clear everything, set standard labels to 0
 *
 * @return Response
 */
function clearAll() {
  $response = new Response();
  $searchDate = dbi_escape_string(trim($_SESSION['sLabelDate']));
  $sql = "SELECT ls.specimenID, s.typusID, l.label
          FROM (herbarinput_log.log_specimens ls, tbl_specimens s)
           LEFT JOIN tbl_labels l ON (ls.specimenID=l.specimen_ID AND ls.userID=l.userID)
          WHERE ls.specimenID=s.specimen_ID
           AND ls.userID='".intval($_SESSION['uid'])."'
           AND ls.timestamp BETWEEN '$searchDate' AND ADDDATE('$searchDate','1')
          GROUP BY ls.specimenID
          ORDER BY ls.timestamp";
  $result = dbi_query($sql);
  while ($row=mysqli_fetch_array($result)) {
    $id = $row['specimenID'];
    $response->assign("inpSL_$id", 'value', 0);
    if ($row['typusID']) {
      $response->assign("cbTypeLabelMap_$id", 'checked', '');
      $response->assign("cbTypeLabelSpec_$id", 'checked', '');
    }

    dbi_query("DELETE FROM tbl_labels WHERE specimen_ID=".intval($id)." AND userID='".$_SESSION['uid']."'");
  }

  $response->call("jaxon_checkTypeLabelMapPdfButton");
  $response->call("jaxon_checkTypeLabelSpecPdfButton");
  $response->call("jaxon_checkStandardLabelPdfButton");
  $response->call("jaxon_checkBarcodeLabelPdfButton");
  return $response;
}

require("listSpecimensServer.php");

/**
 * register all jaxon-functions in this file
 */
$jaxon = jaxon();
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "makeDropdownInstitution");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "makeDropdownCollection");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "getUserDate");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "toggleTypeLabelMap");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "toggleTypeLabelSpec");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "toggleBarcodeLabel");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "checkTypeLabelMapPdfButton");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "checkTypeLabelSpecPdfButton");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "checkBarcodeLabelPdfButton");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "updtStandardLabel");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "checkStandardLabelPdfButton");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "setAll");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "clearAll");

$jaxon->register(Jaxon::CALLABLE_FUNCTION, "listSpecimens");

$jaxon->processRequest();