<?php
session_start();
require("../../inc/connect.php");
require __DIR__ . '/../../vendor/autoload.php';

use Jaxon\Jaxon;
use Jaxon\Response\Response;


/*-------------------\
 *                   *
 *  jaxon functions  *
 *                   *
 \------------------*/

/**
 * react on a change in source selection and send a new list of collections
 *
 * @param integer $source_id source-ID
 * @return Response send response back to caller
 */
function changeDropdownCollection($source_id)
{
    $rows = dbi_query("SELECT collectionID, collection
                       FROM tbl_management_collections
                       WHERE source_id = '" . intval($source_id) . "'
                       ORDER BY collection")
            ->fetch_all(MYSQLI_ASSOC);
    $selectData = (count($rows) == 1) ? '' : "  <option value=\"0\">all collections</option>\n";
    foreach ($rows as $row) {
        $selectData .= "  <option value='" . htmlspecialchars($row['collectionID']) . "'>" . htmlspecialchars($row['collection']) . "</option>\n";
    }

    $response = new Response();
    $response->assign("collection", "innerHTML", $selectData);
    return $response;
}

/**
 * get a table of all duplicate Herbar Numbers for a given source and collection
 * only admins may choose any source, all other users are bound to their own source
 * 
 * @param integer $source_id source-ID
 * @param integer $collection_id collection-ID
 * @return Response send response back to caller
 */
function listDoubleHerbNr($source_id, $collection_id)
{
    $sql = "SELECT s.HerbNummer, s.collectionID, mc.collection, m.source_code, m.source_id, m.source_name, count(s.HerbNummer) as nr
            FROM tbl_specimens s
             LEFT JOIN tbl_management_collections mc ON mc.collectionID = s.collectionID
             LEFT JOIN meta m ON m.source_id = mc.source_id
            WHERE s.HerbNummer IS NOT NULL
             AND s.HerbNummer != ''
             AND s.HerbNummer != '0' ";
    if (checkRight('admin')) {
        if (!empty($source_id)) {
            $sql .= " AND mc.source_id = " . intval($source_id);
        }
    } else {
        $sql .= " AND mc.source_id = " . $_SESSION['sid'];
    }
    if (!empty($collection_id)) {
        $sql .= " AND s.collectionID = " . intval($collection_id);
    }
    $sql .= " GROUP BY s.HerbNummer, s.collectionID
              HAVING nr > 1
              ORDER BY m.source_code, mc.collection, nr DESC, s.HerbNummer";
    $result = dbi_query($sql);
    if ($result->num_rows > 0) {
        $data = "<table class='result'><tr><th>Herb.#</th><th>source</th><th>collection</th><th>count</th></tr>\n";
        while ($row = $result->fetch_array()) {
            $rows = dbi_query("SELECT s.specimen_ID
                               FROM tbl_specimens s
                               WHERE s.HerbNummer = '" . $row['HerbNummer'] . "'
                                AND s.collectionID = " . $row['collectionID'])
                    ->fetch_all(MYSQLI_ASSOC);
            $data .= "<tr>"
                   . "<td>" . $row['HerbNummer'] . "</td><td>" . $row['source_code'] . "</td><td>" . $row['collection'] . "</td><td>" . $row['nr'] . "</td>"
                   . "</tr><tr>"
                   . "<td colspan='4' class='links'>"
                   . "<a href='https://www.jacq.org/index.php?HerbNummer=" . $row['HerbNummer']
                   . (($source_id) ? "&source_name=" . $row['source_name'] : '')
                   . (($collection_id) ? "&collection=" . $row['collection'] : '')
                   . "' target='_blank'>"
                   . implode(", ", array_column($rows, 'specimen_ID'))
                   . "</a>"
                   . "</td>"
                   . "</tr>\n";
        }
        $data .= "</table>";
    } else {
        $data = '';
    }

    $response = new Response();
    $response->assign("list", "innerHTML", $data);
    return $response;
}


/**
 * register all jaxon-functions in this file
 */
$jaxon = jaxon();
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "changeDropdownCollection");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "listDoubleHerbNr");
$jaxon->processRequest();