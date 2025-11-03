<?php
session_start();
error_reporting(0);
require("../inc/connect.php");
require("../inc/herbardb_input_functions.php");
require("../inc/cssf.php");
require __DIR__ . '/../vendor/autoload.php';

use Jaxon\Jaxon;
use Jaxon\Response\Response;

$jaxon = jaxon();

$response = new Response();

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
    $result = dbi_query($sql);
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_array($result);
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
    $result = dbi_query($sql);
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_array($result);
        $text = $row['autor'] . " (" . substr($row['jahr'], 0, 4) . "): " . $row['titel'];
        if ($row['periodicalID']) {
            $text .= " " . $row['periodical'];
        }
        $text .= " " . $row['vol'];
        if ($row['part']) {
            $text .= " (" . $row['part'] . ")";
        }
        $text .= ": " . $row['pp'] . ".";
        return $text . " <" . $row['citationID'] . ">";
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
    $result = dbi_query($sql);
    $ret = array();
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_array($result)) {
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
 * jaxon-function to produce a list of all libraries
 * a given periodical is in
 *
 * @param array $periodical form-value of "periodical"
 * @return Response
 */
function listLib($periodical)
{
    global $response;

    $text = "<tr class=\"out\"><td class=\"out\" colspan=\"4\">no entries</td></tr>\n";

    //$id = extractID($periodical['periodical']);
	$id = intval($periodical['periodicalIndex']);

    if ($id!='NULL') {
        $sql = "SELECT lib_period_ID, signature, bestand, url, library
                FROM tbl_lit_lib_period, tbl_lit_libraries
                WHERE tbl_lit_lib_period.library_ID = tbl_lit_libraries.library_ID
                 AND periodicalID = $id
                ORDER BY library";
        $result = dbi_query($sql);
        if (mysqli_num_rows($result) > 0) {
            $text = "";
            while ($row = mysqli_fetch_array($result)) {
                $url = '';
                if (trim($row['url'])) {
                    $parts = explode('http', $row['url']);
                    foreach ($parts as $part) {
                        if (trim($part)) {
                            if ($url) {
                                $url .= "<br>\n";
                            }
                            $url .= "<a href=\"http" . trim($part) . "\" target=\"_blank\">http" . trim($part) . "</a>\n";
                        }
                    }
                }
                $text .= "<tr class=\"out\">"
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
             . $text
             . "</table>\n";

    $response->assign("jaxon_listLibraries", "innerHTML", $libList);
    return $response;
}

/**
 * jaxon-function to produce a list of all literature-containers
 *
 * @param unknown_type $citationID
 */
function listContainer($citationID)
{
    global $response;

    $citationIDfiltered = intval($citationID);

    if ($citationIDfiltered) {
        $topID = $citationIDfiltered;
        for ($i = 0; $i < 1000; $i++) {
            $sql = "SELECT citation_parent_ID
                    FROM tbl_lit_container
                    WHERE citation_child_ID = '$topID'";
            $result = dbi_query($sql);
            if (mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_array($result);
                $topID = $row['citation_parent_ID'];
            } else {
                break;
            }
        }

        if ($i < 1000) {
            $tree = array();
            if ($topID == $citationIDfiltered) {
                $text = "<span title=\"" . makeProtologFromID($citationIDfiltered) . "\">" . makeTextShorter(makeProtologFromID($citationIDfiltered), 80, 20) . "</span>";
            } else {
                $text = "<a href=\"editLit.php?sel=<$topID>\" class=\"iBox\" title=\"" . makeProtologFromID($topID) . "\">"
                      . makeTextShorter(makeProtologFromID($topID), 80, 20)
                      . "</a>";
            }
            $tree[] = array('id'       => $topID,
                            'text'     => $text,
                            'children' => getChildrenOfParent($topID, $citationIDfiltered));
            $response->assign('iBox_content', 'innerHTML', showTree($tree));
        } else {
            $response->assign('iBox_content', 'innerHTML', 'there is an endless loop within your data');
        }
        $response->script('$("#iBox_content").dialog("option", "title", "list container");');
        $response->script('$("#iBox_content").dialog("open");');
    }
    return $response;
}

function editContainer($citationID)
{
    global $response;

    $citationIDfiltered = intval($citationID);

    if ($citationIDfiltered) {
        $ret = "<form id=\"f_iBox\">\n"
             . "<input type=\"hidden\" name=\"citationID\" id=\"citationID\" value=\"$citationIDfiltered\">\n"
             . "<table>\n";
        if (($_SESSION['editControl'] & 0x20) != 0) {
            $ret .= "<tr><td colspan=\"4\">"
                  . "<input type=\"submit\" class=\"cssfbutton\" value=\"update\" onClick=\"jaxon_updateContainer(jaxon.getFormValues('f_iBox')); return false;\">"
                  . "</td></tr>\n";
        }
        $ret .= "<tr><td colspan=\"4\"><b>" . makeHeaderFromID($citationIDfiltered) . "</b></td></tr>\n"
              . "<tr><th align=\"center\">is child of </th><th></th><th></th><th></th></tr>\n";

        $sql = "( SELECT 1 AS sortBlock, tbl_lit_containerID, citation_parent_ID, citation_child_ID
                  FROM tbl_lit_container
                  WHERE citation_child_ID = '" . intval($citationIDfiltered) . "' )
                UNION
                ( SELECT 2 as sortBlock, tbl_lit_containerID, citation_parent_ID, citation_child_ID
                  FROM tbl_lit_container
                  WHERE citation_parent_ID = '" . intval($citationIDfiltered) . "' )
                ORDER BY sortBlock, citation_parent_ID, citation_child_ID";
        $result = dbi_query($sql);
        while ($row = mysqli_fetch_array($result)) {
            $id = $row['tbl_lit_containerID'];
            if ($row['citation_child_ID'] == $citationIDfiltered) {
                $protolog = makeProtologFromID($row['citation_parent_ID']);
                $isChild = true;
            } else {
                $protolog = makeProtologFromID($row['citation_child_ID']);
                $isChild = false;
            }
            $ret .= "<tr><td align=\"center\">"
                  . "<input type='checkbox' class='cssfcheckbox' name='isChild_$id' id='isChild_$id' " . (($isChild) ? 'checked' : '') . ">"
                  . "</td><td width='10'></td><td>"
                  . "<input class='cssftextAutocomplete' style='width: 35em;' type='text' name='citation_$id' id='citation_$id' value='" . htmlspecialchars($protolog) . "'>"
                  . "</td><td align='center'>";
            if (($_SESSION['editControl'] & 0x20) != 0) {
                $ret .= "<img src=\"webimages/remove.png\" title=\"delete entry\" onclick=\"jaxon_deleteContainer('" . $row['tbl_lit_containerID'] . "', '$citationIDfiltered');\">";
            }
            $ret .= "</td></tr>\n";
           	$response->script("setTimeout(\"call_makeAutocompleter('citation_$id')\",100);");
        }
        $ret .= "<tr><td align='center'>"
              . "<input type='checkbox' class='cssfcheckbox' name='isChild_0' id='isChild_0'>"
              . "</td><td width='10'>&nbsp;</td><td>"
              . "<input class='cssftextAutocomplete' style='width: 35em;' type='text' name='citation_0' id='citation_0' value=''>"
              . "</td></tr>\n";
        $response->script("setTimeout(\"call_makeAutocompleter('citation_0')\",100);");

        $ret .= "</table>\n"
              . "</form>\n";

        $response->assign('iBox_content', 'innerHTML', $ret);
        $response->script('$("#iBox_content").dialog("option", "title", "edit container");');
        $response->script('$("#iBox_content").dialog("open");');
    }
    return $response;
}

function updateContainer($formData)
{
    global $response;

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
                    dbi_query($sql);
                }
            }
        }
    }

    //Hide the iBox module on return
    $response->script('$("#iBox_content").dialog("close");');

    return $response;
}

function deleteContainer($containerID, $citationID)
{
    global $response;

    $containerIDfiltered = intval($containerID);

    if ($containerIDfiltered && ($_SESSION['editControl'] & 0x20) != 0) {
        dbi_query("DELETE FROM tbl_lit_container WHERE tbl_lit_containerID = '" . $containerIDfiltered . "'");
    }

    editContainer($citationID);

    return $response;
}

/**
 * Add a classification for a given citation ID
 * @param int $citationID citation for which the classification should be added
 * @param int $child_taxonID child taxon name
 * @param int $parent_taxonID parent taxon name
 */
function addClassification($citationID, $number, $order, $child_taxonID, $parent_taxonID)
{
    global $response;

    // make sure passed arguments are clean
    $citationID_f = intval($citationID);
    $child_taxonID_f = intval($child_taxonID);
    $parent_taxonID_f = intval($parent_taxonID);

    if( $citationID_f > 0 && $child_taxonID_f > 0 && $parent_taxonID_f > 0 ) {
        // Find the fitting tax_synonymy entry
        $db = clsDbAccess::Connect('INPUT');
        $dbst = $db->query("
            SELECT `tax_syn_ID`
            FROM `tbl_tax_synonymy`
            WHERE `source_citationID` = $citationID_f AND `taxonID` = $child_taxonID_f
            ");
        $rows = $dbst->fetchAll();

        // Check if we found something
        if( count($rows) > 0 ) {
            $row = $rows[0];
            $tax_syn_ID = $row['tax_syn_ID'];

            // Add new entry to classification
            $db->query("INSERT INTO `tbl_tax_classification`
                        ( `tax_syn_ID`, `parent_taxonID`, `number`, `order` )
                        values
                        ( $tax_syn_ID, $parent_taxonID_f, " . $db->quote(trim($number)) . ", " . intval($order) . " )");

            $response->script( 'addClassificationDone();' );
        } else {
            $response->alert('No synonymy entry found');
        }
    } else {
        $response->alert('Invalid arguments passed');
    }

    return $response;
}

/**
 * Update the parent of a given classification entry
 * @global Response $response
 * @param int $p_classification_id classification entry to edit
 * @param int $p_parent_taxonID new parent taxon id
 * @return Response
 */
function updateClassification( $p_classification_id, $p_number, $p_order, $p_child_taxonID, $p_parent_taxonID )
{
    global $response;

    // escape & make parameters save
    $p_classification_id_f = intval($p_classification_id);
    $p_parent_taxonID_f = intval($p_parent_taxonID);

    // check if we have a valid entry to edit
    if( $p_classification_id_f > 0 && $p_parent_taxonID_f > 0 ) {
        $db = clsDbAccess::Connect('INPUT');
        $dbst = $db->query("
            UPDATE `tbl_tax_classification`
            SET
            `parent_taxonID` = $p_parent_taxonID_f,
            `number` = " . $db->quote(trim($p_number)) . ",
            `order` = " . intval($p_order) . "
            WHERE `classification_id` = $p_classification_id_f
            ");
        $dbst->execute();

        $response->script("updateClassificationDone();");
    } else {
        $response->alert('Invalid arguments passed');
    }

    return $response;
}

/**
 * Delete a given classification
 * @global Response $response
 * @param int $classification_id id of classification to delete
 * @return Response
 */
function deleteClassification( $p_classification_id )
{
    global $response;

    // Check if we have a valid classification id
    $p_classification_id_f = intval($p_classification_id);
    if( $p_classification_id_f > 0 ) {
        // Find citationID for this entry
        $db = clsDbAccess::Connect('INPUT');
        $dbst = $db->query("SELECT ts.`source_citationID`
                            FROM `tbl_tax_synonymy` ts
                            LEFT JOIN `tbl_tax_classification` tc ON tc.`tax_syn_ID` = ts.`tax_syn_ID`
                            WHERE tc.`classification_id` = $p_classification_id_f");
        $rows = $dbst->fetch();
        $citation_id = $rows['source_citationID'];

        // Delete entry from classification table
        $db->query("DELETE FROM `tbl_tax_classification`
                    WHERE `classification_id` = $p_classification_id_f");

        // Successfully done
        $response->alert('Deleted');
        listClassifications($citation_id, 0, 1);
    } else {
        $response->alert('Invalid classification_id');
    }

    return $response;
}

/**
 * List all classification entries for a given citation
 * @global Response $response
 * @param int $p_citationID citation to list the classifications for
 * @param int $page page the user is corrently on (for pagination)
 * @param int $bInitialize initialize the pagination (0 = no, else = yes)
 * @param int $p_search_taxonID
 * @return Response
 */
function listClassifications( $p_citationID, $page, $bInitialize, $p_search_taxonID = 0 )
{
    global $response;

    // Clean & prepare bassed parameters
    $p_citationID_f = intval($p_citationID);
    $start = intval($page) * 10;
    $p_search_taxonID_f = intval($p_search_taxonID);

    /**
     * Fetch all existing entries and show them
     */
    $db = clsDbAccess::Connect('INPUT');
    $dbst = $db->query("SELECT
                         SQL_CALC_FOUND_ROWS
                         tc.`classification_id`,
                         `herbar_view`.GetScientificName( tc.`parent_taxonID`, 0 ) AS `parent_taxon`,
                         tc.`parent_taxonID`,
                         `herbar_view`.GetScientificName( ts.`taxonID`, 0 ) AS `child_taxon`,
                         ts.`taxonID` AS `child_taxonID`,
                         tc.`number`,
                         tc.`order`
                        FROM `tbl_tax_classification` tc
                         LEFT JOIN `tbl_tax_synonymy` ts ON ts.`tax_syn_ID` = tc.`tax_syn_ID`
                        WHERE ts.`source_citationID` = $p_citationID_f "
                            . (($p_search_taxonID_f > 0) ? "AND (ts.`taxonID` = $p_search_taxonID_f OR tc.`parent_taxonID` = $p_search_taxonID_f) " : "") . "
                        ORDER BY `parent_taxon`, `order`, `child_taxon`
                        LIMIT " . $start . ", 10
        ");
    $rows = $dbst->fetchAll();

    $dbst = $db->query("SELECT FOUND_ROWS() AS `found_rows`");
    $found_rows = $dbst->fetch();
    $found_rows = $found_rows['found_rows'];

    // Check if we should initialize the pagination
    if( $bInitialize > 0 ) {
        $response->script("
            classification_page = 0;
            $('#classification_pagination').pagination( " . $found_rows . ", {
                items_per_page: 10,
                num_edge_entries: 1,
                callback: function(page, container) {
                    classification_page = page;

                    jaxon_listClassifications( " . $p_citationID . ", page, 0, " . $p_search_taxonID . " );

                    return false;
                }
            } );
        ");
    }

    // Create output and send it back
    ob_start();
    $cf = new CSSF();
    $cf->tabindex = 1000;
    foreach( $rows as $index => $row ) {
        $classification_id = $row['classification_id'];

        // hidden IDs for editing
        echo "<input type='hidden' name='classification_" . $classification_id . "_child_taxonID' value='" . $row['child_taxonID'] . "' />";
        echo "<input type='hidden' name='classification_" . $classification_id . "_parent_taxonID' value='" . $row['parent_taxonID'] . "' />";
        // numbering & sorting
        $cf->inputText(1, 8.5 + $index * 2.0, 2, "classification_" . $classification_id . "_number", $row['number'], 0, "", "", true);
        $cf->inputText(4, 8.5 + $index * 2.0, 2, "classification_" . $classification_id . "_order", $row['order'], 0, "", "", true);
        // scientific name fields
        $cf->inputText(7, 8.5 + $index * 2.0, 24, "classification_" . $classification_id . "_child_name", $row['child_taxon'], 0, "", "", true);
        $cf->inputText(32, 8.5 + $index * 2.0, 20, "classification_" . $classification_id . "_parent_name", $row['parent_taxon'], 0, "", "", true);
        // control buttons
        $cf->buttonLink(53.5, 8.5 + $index * 2.0, "Del", '#" onclick="jaxon_deleteClassification( ' . $classification_id . ' ); return false;', 0);
        $cf->buttonLink(57.5, 8.5 + $index * 2.0, "Edit", '#" onclick="editClassification(' . $classification_id . '); return false;"', 0);
    }
    $output = ob_get_clean();

    // Assign output to list
    $response->assign('classification_entries', 'innerHTML', $output);

    return $response;
}

/**
 * Simple wrapper for new search listing
 * @param int $p_citationID citation to search for
 * @param int $p_search_taxonID taxonID to search for
 * @return Response
 */
function searchClassifications( $p_citationID, $p_search_taxonID )
{
    return listClassifications($p_citationID, 0, true, intval($p_search_taxonID));
}

/**
 * register all jaxon-functions in this file
 */
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "listLib");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "listContainer");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "editContainer");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "updateContainer");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "deleteContainer");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "addClassification");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "deleteClassification");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "listClassifications");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "searchClassifications");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "updateClassification");
$jaxon->processRequest();
