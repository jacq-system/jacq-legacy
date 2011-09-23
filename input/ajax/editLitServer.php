<?php
session_start();
error_reporting(0);
require("../inc/connect.php");
require("../inc/herbardb_input_functions.php");
require_once ("../inc/xajax/xajax_core/xajax.inc.php");

$xajax = new xajax();

$objResponse = new xajaxResponse();

// service functions

function makeProtologFromID($citationID)
{
    $sql ="SELECT citationID, suptitel, le.autor as editor, la.autor,
          l.periodicalID, lp.periodical, vol, part, jahr, pp
         FROM tbl_lit l
          LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID=l.periodicalID
          LEFT JOIN tbl_lit_authors le ON le.autorID=l.editorsID
          LEFT JOIN tbl_lit_authors la ON la.autorID=l.autorID
         WHERE citationID = '" . intval($citationID) . "'";
    $result = db_query($sql);
    if (mysql_num_rows($result) > 0) {
        $row = mysql_fetch_array($result);
        return protolog($row);
    } else {
        return '';
    }
}

function makeHeaderFromID($citationID)
{
    $sql ="SELECT citationID, titel, la.autor, l.periodicalID, lp.periodical, vol, part, jahr, pp
         FROM tbl_lit l
          LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID = l.periodicalID
          LEFT JOIN tbl_lit_authors la ON la.autorID = l.autorID
         WHERE citationID = '" . intval($citationID) . "'";
    $result = db_query($sql);
    if (mysql_num_rows($result) > 0) {
      $row = mysql_fetch_array($result);
      $text = $row['autor']." (".substr($row['jahr'], 0, 4)."): " . $row['titel'];
      if ($row['periodicalID']) $text .= " ".$row['periodical'];
      $text .= " ".$row['vol'];
      if ($row['part']) $text .= " (".$row['part'].")";
      $text .= ": ".$row['pp'].".";
      return $text." <".$row['citationID'].">";
    } else {
        return '';
    }
}

function makeTextShorter($text, $limit, $tail = 10)
{
    if (strlen($text) > $limit - 3) {
        return substr($text, 0, $limit - $tail - 3) . '...' . substr($text, -$tail);
    } else {
        return $text;
    }
}

function getChildrenOfParent($parentID, $citationID)
{
    $sql = "SELECT citation_child_ID
            FROM tbl_lit_container lc
             LEFT JOIN tbl_lit l ON l.citationID = lc.citation_child_ID
            WHERE citation_parent_ID = '" . intval($parentID) . "'
            ORDER BY l.ppSort";
    $result = db_query($sql);
    $ret = array();
    if (mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_array($result)) {
            if ($row['citation_child_ID'] == $citationID) {
                $text = "<span title=\"" . makeProtologFromID($citationID) . "\">" . makeTextShorter(makeProtologFromID($citationID), 80, 20) . "</span>";
            } else {
                $text = "<a href=\"editLit.php?sel=<" . $row['citation_child_ID'] . ">\" class=\"iBox\" title=\""
                      . makeProtologFromID($row['citation_child_ID']) . "\">"
                      . makeTextShorter(makeProtologFromID($row['citation_child_ID']), 80, 20)
                      . "</a>";
            }
            $ret[] = array('id'       => $row['citation_child_ID'],
                           'text'     => $text,
                           'children' => getChildrenOfParent($row['citation_child_ID'], $citationID));
        }
    }
    return $ret;
}

function showTree($tree, $layer = 0, $leftString = '')
{
    if ($layer == 0) {
        $ret = $tree[0]['text'] . "<br>\n"
             . showTree($tree[0]['children'], 1);
    } else {
        $ret = '';
        for ($i = 0; $i < count($tree); $i++) {
            if ($i < count($tree) - 1) {
                $sign   = "<img src=\"webimages/tree_line.gif\">";
                $branch = "<img src=\"webimages/tree_branchLine.gif\">";
            } else {
                $sign = "<img src=\"webimages/tree_empty.gif\">";
                $branch = "<img src=\"webimages/tree_branch.gif\">";
            }
            $ret .= $leftString . $branch . ' ' . $tree[$i]['text'] . "<br>\n"
                  . showTree($tree[$i]['children'], $layer + 1, $leftString . $sign);
        }
    }
    return $ret;
}

// ajax-functions
/**
 * xajax-function to produce a list of all libraries
 * a given periodical is in
 *
 * @param array $periodical form-value of "periodical"
 * @return xajaxResponse
 */
function listLib($periodical)
{
    global $objResponse;

    $response = "<tr class=\"out\"><td class=\"out\" colspan=\"4\">no entries</td></tr>\n";

    //$id = extractID($periodical['periodical']);
	$id = $periodical['periodicalIndex'];

    if ($id!='NULL') {
        $sql = "SELECT lib_period_ID, signature, bestand, url, library
                FROM tbl_lit_lib_period, tbl_lit_libraries
                WHERE tbl_lit_lib_period.library_ID = tbl_lit_libraries.library_ID
                 AND periodicalID = $id
                ORDER BY library";
        $result = db_query($sql);
        if (mysql_num_rows($result) > 0) {
            $response = "";
            while ($row = mysql_fetch_array($result)) {
                $url = '';
                if (trim($row['url'])) {
                    $parts = explode('http', $row['url']);
                    foreach ($parts as $part) {
                        if (trim($part)) {
                            if ($url) $url .= "<br>\n";
                            $url .= "<a href=\"http" . trim($part) . "\" target=\"_blank\">http" . trim($part) . "</a>\n";
                        }
                    }
                }
                $response .= "<tr class=\"out\">"
                           . "<td class=\"out\">" . $row['library'] . "</td>"
                           . "<td class=\"out\">" . $row['signature'] . "</td>"
                           . "<td class=\"out\">" . $row['bestand'] . "</td>"
                           . "<td class=\"out\">" . trim($url) . "</td>"
                           . "</tr>\n";
            }
        }
    }

    $libList = "<table class=\"out\" cellspacing=\"2\" cellpadding=\"2\">\n"
             . "<tr class=\"out\">"
             . "<th class=\"out\">&nbsp;library&nbsp;</th>"
             . "<th class=\"out\">&nbsp;signature&nbsp;</th>"
             . "<th class=\"out\">&nbsp;stock&nbsp;</th>"
             . "<th class=\"out\">&nbsp;URL&nbsp;</th>"
             . "</tr>\n"
             . $response
             . "</table>\n";

    $objResponse->assign("xajax_listLibraries", "innerHTML", $libList);
    return $objResponse;
}

/**
 * xajax-function to produce a list of all literature-containers
 *
 * @param unknown_type $citationID
 */
function listContainer($citationID)
{
    global $objResponse;

    $citationID = intval($citationID);

    if ($citationID) {
      $topID = $citationID;
      for ($i = 0; $i < 1000; $i++) {
          $sql = "SELECT citation_parent_ID
                  FROM tbl_lit_container
                  WHERE citation_child_ID = '$topID'";
          $result = db_query($sql);
          if (mysql_num_rows($result) > 0) {
              $row = mysql_fetch_array($result);
              $topID = $row['citation_parent_ID'];
          } else {
              break;
          }
      }

      if ($i < 1000) {
          $tree = array();
          if ($topID == $citationID) {
              $text = "<span title=\"" . makeProtologFromID($citationID) . "\">" . makeTextShorter(makeProtologFromID($citationID), 80, 20) . "</span>";
          } else {
              $text = "<a href=\"editLit.php?sel=<$topID>\" class=\"iBox\" title=\"" . makeProtologFromID($topID) . "\">"
                    . makeTextShorter(makeProtologFromID($topID), 80, 20)
                    . "</a>";
          }
          $tree[] = array('id'       => $topID,
                          'text'     => $text,
                          'children' => getChildrenOfParent($topID, $citationID));
          $objResponse->assign('iBox_content', 'innerHTML', showTree($tree));
      } else {
          $objResponse->assign('iBox_content', 'innerHTML', 'there is an endless loop within your data');
      }
      $objResponse->script('$("#iBox_content").dialog("option", "title", "list container");');
      $objResponse->script('$("#iBox_content").dialog("open");');
    }
    return $objResponse;
}

function editContainer($citationID)
{
    global $objResponse;

    $citationID = intval($citationID);

    if ($citationID) {
        $ret = "<form id=\"f_iBox\">\n"
             . "<input type=\"hidden\" name=\"citationID\" id=\"citationID\" value=\"$citationID\">\n"
             . "<table>\n";
        if (($_SESSION['editControl'] & 0x20) != 0) {
            $ret .= "<tr><td colspan=\"4\">"
                  . "<input type=\"submit\" class=\"cssfbutton\" value=\"update\" onClick=\"xajax_updateContainer(xajax.getFormValues('f_iBox')); return false;\">"
                  . "</td></tr>\n";
        }
        $ret .= "<tr><td colspan=\"4\"><b>" . makeHeaderFromID($citationID) . "</b></td></tr>\n"
              . "<tr><th align=\"center\">is child of </th><th></th><th></th><th></th></tr>\n";

        $sql = "( SELECT 1 AS sortBlock, tbl_lit_containerID, citation_parent_ID, citation_child_ID
                  FROM tbl_lit_container
                  WHERE citation_child_ID = '" . intval($citationID) . "' )
                UNION
                ( SELECT 2 as sortBlock, tbl_lit_containerID, citation_parent_ID, citation_child_ID
                  FROM tbl_lit_container
                  WHERE citation_parent_ID = '" . intval($citationID) . "' )
                ORDER BY sortBlock, citation_parent_ID, citation_child_ID";
        $result = db_query($sql);
        while ($row = mysql_fetch_array($result)) {
            $id = $row['tbl_lit_containerID'];
            if ($row['citation_child_ID'] == $citationID) {
                $protolog = makeProtologFromID($row['citation_parent_ID']);
                $isChild = true;
            } else {
                $protolog = makeProtologFromID($row['citation_child_ID']);
                $isChild = false;
            }
            $row2 = mysql_fetch_array(db_query($sql));
            $ret .= "<tr><td align=\"center\">"
                  . "<input type='checkbox' class='cssfcheckbox' name='isChild_$id' id='isChild_$id' " . (($isChild) ? 'checked' : '') . ">"
                  . "</td><td width='10'></td><td>"
                  . "<input class='cssftextAutocomplete' style='width: 35em;' type='text' name='citation_$id' id='citation_$id' value='" . htmlspecialchars($protolog) . "'>"
                  . "</td><td align='center'>";
            if (($_SESSION['editControl'] & 0x20) != 0) {
                $ret .= "<img src=\"webimages/remove.png\" title=\"delete entry\" onclick=\"xajax_deleteContainer('" . $row['tbl_lit_containerID'] . "', '$citationID');\">";
            }
            $ret .= "</td></tr>\n";
           	$objResponse->script("setTimeout(\"call_makeAutocompleter('citation_$id')\",100);");
        }
        $ret .= "<tr><td align='center'>"
              . "<input type='checkbox' class='cssfcheckbox' name='isChild_0' id='isChild_0'>"
              . "</td><td width='10'>&nbsp;</td><td>"
              . "<input class='cssftextAutocomplete' style='width: 35em;' type='text' name='citation_0' id='citation_0' value=''>"
              . "</td></tr>\n";
        $objResponse->script("setTimeout(\"call_makeAutocompleter('citation_0')\",100);");

        $ret .= "</table>\n"
              . "</form>\n";

        $objResponse->assign('iBox_content', 'innerHTML', $ret);
        $objResponse->script('$("#iBox_content").dialog("option", "title", "edit container");');
        $objResponse->script('$("#iBox_content").dialog("open");');
    }
    return $objResponse;
}

function updateContainer($formData)
{
    global $objResponse;

    $citationID = intval($formData['citationID']);

    if ($citationID && ($_SESSION['editControl'] & 0x20) != 0) {
        foreach ($formData as $key => $val) {
            if (substr($key, 0, 9) == 'citation_' && extractID($val) != "NULL") {
                $containerID = intval(substr($key, 9));
                if ($citationID != extractID($val)) {
                    if (!empty($formData['isChild_' . $containerID])) {
                        $sqldata = "citation_parent_ID = " . extractID($val) . ",
                                    citation_child_ID  = '" . $citationID . "'";
                    } else {
                        $sqldata = "citation_parent_ID = '" . $citationID . "',
                                    citation_child_ID  = " . extractID($val) . "";
                    }
                    if ($containerID > 0) {
                        $sql = "UPDATE tbl_lit_container SET
                                $sqldata
                                WHERE tbl_lit_containerID = '" . $containerID . "'";
                    } else {
                        $sql = "INSERT INTO tbl_lit_container SET
                                $sqldata";
                    }
                    db_query($sql);
                }
            }
        }
    }

    //Hide the iBox module on return
    $objResponse->script('$("#iBox_content").dialog("close");');

    return $objResponse;
}

function deleteContainer($containerID, $citationID)
{
    global $objResponse;

    $containerID = intval($containerID);

    if ($containerID && ($_SESSION['editControl'] & 0x20) != 0) {
        db_query("DELETE FROM tbl_lit_container WHERE tbl_lit_containerID = '" . $containerID . "'");
    }

    editContainer($citationID);

    return $objResponse;
}

/**
 * register all xajax-functions in this file
 */
$xajax->registerFunction("listLib");
$xajax->registerFunction("listContainer");
$xajax->registerFunction("editContainer");
$xajax->registerFunction("updateContainer");
$xajax->registerFunction("deleteContainer");
$xajax->processRequest();