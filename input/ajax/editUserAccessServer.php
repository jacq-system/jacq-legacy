<?php
session_start();
require("../inc/connect.php");
require __DIR__ . '/../vendor/autoload.php';

use Jaxon\Jaxon;
use Jaxon\Response\Response;

/**
 * jaxon-function to produce the dropcown-contents for "Family" and
 * an empty "Genus"
 *
 * @param array $category form-value of "categoryID"
 * @return Response
 */
function getFamilyDropdown($category)
{
    $selectData = "  <option></option>\n";
    $result = db_query("SELECT familyID, family FROM tbl_tax_families WHERE categoryID='" . intval($category['categoryID']) . "' ORDER BY family");
    if ($result) {
        if (mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_array($result)) {
                $selectData .= "  <option value=\"" . $row['familyID'] . "\">" . htmlspecialchars($row['family']) . "</option>\n";
            }
        }
    }

    $response = new Response();
    $response->assign("familyID", "innerHTML", $selectData);
    $response->assign("genID", "innerHTML", "  <option></option>\n");
    return $response;
}

/**
 * jaxon-function to produce the dropcown-contents for "Genus"
 *
 * @param array $category form-value of "familyID"
 * @return Response
 */
function getGenusDropdown($family) {

  $selectData = "  <option></option>\n";
  $result = db_query("SELECT genID, genus FROM tbl_tax_genera WHERE familyID='" . intval($family['familyID']) . "' ORDER BY genus");
  if ($result) {
    if (mysql_num_rows($result) > 0) {
      while ($row = mysql_fetch_array($result)) {
        $selectData .= "  <option value=\"" . $row['genID'] . "\">" . htmlspecialchars($row['genus']) . "</option>\n";
      }
    }
  }

  $response = new Response();
  $response->assign("genID", "innerHTML", $selectData);
  return $response;
}

/**
 * register all jaxon-functions in this file
 */
$jaxon = jaxon();
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "getFamilyDropdown");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "getGenusDropdown");
$jaxon->processRequest();