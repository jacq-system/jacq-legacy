<?php
session_start();
require("../inc/connect.php");
require_once ("../inc/xajax/xajax_core/xajax.inc.php");

/**
 * xajax-function to produce the dropcown-contents for "Family" and
 * an empty "Genus"
 *
 * @param array $category form-value of "categoryID"
 * @return xajaxResponse
 */
function getFamilyDropdown($category) {

  $selectData = "  <option></option>\n";
  $sql = "SELECT familyID, family FROM tbl_tax_families WHERE categoryID='".intval($category['categoryID'])."' ORDER BY family";
  if ($result = db_query($sql)) {
    if (mysql_num_rows($result)>0) {
      while ($row=mysql_fetch_array($result)) {
        $selectData .= "  <option value=\"".$row['familyID']."\">".htmlspecialchars($row['family'])."</option>\n";
      }
    }
  }

  $objResponse = new xajaxResponse();
  $objResponse->assign("familyID", "innerHTML", $selectData);
  $objResponse->assign("genID", "innerHTML", "  <option></option>\n");
  return $objResponse;
}

/**
 * xajax-function to produce the dropcown-contents for "Genus"
 *
 * @param array $category form-value of "familyID"
 * @return xajaxResponse
 */
function getGenusDropdown($family) {

  $selectData = "  <option></option>\n";
  $sql = "SELECT genID, genus FROM tbl_tax_genera WHERE familyID='".intval($family['familyID'])."' ORDER BY genus";
  if ($result = db_query($sql)) {
    if (mysql_num_rows($result)>0) {
      while ($row=mysql_fetch_array($result)) {
        $selectData .= "  <option value=\"".$row['genID']."\">".htmlspecialchars($row['genus'])."</option>\n";
      }
    }
  }

  $objResponse = new xajaxResponse();
  $objResponse->assign("genID", "innerHTML", $selectData);
  return $objResponse;
}

/**
 * register all xajax-functions in this file
 */
$xajax = new xajax();
$xajax->registerFunction("getFamilyDropdown");
$xajax->registerFunction("getGenusDropdown");
$xajax->processRequest();