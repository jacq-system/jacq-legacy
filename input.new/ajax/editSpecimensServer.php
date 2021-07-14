<?php
session_start();
require("../inc/connect.php");
require("../inc/herbardb_input_functions.php");
require __DIR__ . '/../vendor/autoload.php';

use Jaxon\Jaxon;
use Jaxon\Response\Response;

$jaxon = jaxon();

$response = new Response();

function dec2min($angle, $type = '', $print = true)
{
    $sign = ($angle < 0) ? -1 : 1;
    $angle = round(abs($angle) * 3600);

    $degrees = floor($angle / 3600);
    $minutes = floor(($angle / 60) % 60);
    $seconds = round($angle % 60);
    switch ($type) {
        case 'lat': $direction = ($sign >= 0) ? 'N' : 'S'; break;
        case 'lon': $direction = ($sign >= 0) ? 'E' : 'W'; break;
        default: $direction = '';
                 $degrees *= $sign;
    }

    if ($print) {
        return sprintf("%d° %d' %d\" %s", $degrees, $minutes, $seconds, $direction);
    } else {
        return array('deg' => $degrees, 'min' => $minutes, 'sec' => $seconds, 'dir' => $direction);
    }
}

function makeInstitutionDropdown($institution, $selected, $id)
{
    $dropdown = "<select class=\"cssf\" name=\"linkInstitution_$id\" id=\"linkInstitution_$id\">";
    for ($i = 0; $i < count($institution[0]); $i++) {
        $dropdown .= "<option value=\"" . $institution[0][$i] . "\"";
        if ($selected == $institution[0][$i]) {
            $dropdown .= " selected";
        }
        $dropdown .= ">" . htmlspecialchars($institution[1][$i]) . "</option>";
    }
    $dropdown .= "</select>\n";

    return $dropdown;
}

/**
 * jaxon-function toggleLanguage
 *
 * changes the apropriate Fields from local language to english
 *
 * @return Response
 */
function toggleLanguage($formData)
{
    global $response;

    $Fundort1 = $formData['Fundort2'];
    $Fundort2 = $formData['Fundort1'];
    if ($formData['toggleLanguage']) {
        $toggleLanguage = 0;
        $labelText = 'Locality';
    } else {
        $toggleLanguage = 1;
        $labelText = 'engl. Locality';
    }

    $response->assign('Fundort1', 'value', $Fundort1);
    $response->assign('Fundort2', 'value', $Fundort2);
    $response->assign('toggleLanguage', 'value', $toggleLanguage);
    $response->assign("labelLocality", "innerHTML", $labelText);

    return $response;
}

function searchGeonames($searchtext)
{
    global $response;

    if (trim($searchtext)) {
        $sql = "SELECT grg.geonameid, grg.name, grg.alternatenames, grg.latitude, grg.longitude,
                 grg.`admin1 code` AS admin1_code, grg.`country code` AS country_code,
                 gn.nation_engl, gn.iso_alpha_2_code
                FROM tbl_geo_ref_geonames grg
                 LEFT JOIN tbl_geo_nation gn ON gn.iso_alpha_2_code = grg.`country code`
                WHERE grg.name LIKE " . quoteString($searchtext) . "
                 OR grg.asciiname LIKE " . quoteString($searchtext) . "
                ORDER BY grg.geonameid";
        $result = dbi_query($sql);
        $num = mysqli_num_rows($result);
        if ($num > 0) {
            $ret = "<b>found " . $num . (($num > 1) ? " entries" : " entry") . "</b><br>\n";
            while ($row = mysqli_fetch_array($result)) {
                $admin1Code = $row['admin1_code'];
                if ($row['admin1_code'] && $row['admin1_code'] != '00') {
                    $sql = "SELECT name
                            FROM tbl_geo_ref_geonames
                            WHERE `country code` = " . quoteString($row['country_code']) . "
                             AND `admin1 code` = " . quoteString($row['admin1_code']) . "
                             AND `feature code` = 'ADM1'";
                    $result2 = dbi_query($sql);
                    $admin1Code .= '-';
                    if (mysqli_num_rows($result2) > 0) {
                        $row2 = mysqli_fetch_array($result2);
                        $admin1Code = $row2['name'] . " (" . $row['admin1_code'] . ")";
                    }
                }
                $ret .= "<hr>\n"
                      . "<b>geonameID:</b> " . $row['geonameid'] . "     "
                      .  "<input type=\"button\" value=\" use this \" onclick=\"jaxon_useGeoname('" . $row['geonameid'] . "');\"><br>\n"
                      . "<b>name:</b> " . $row['name'] . "<br>\n"
                      . (($row['alternatenames']) ? "<b>alternatenames:</b> " . $row['alternatenames'] . "<br>\n" : "")
                      . "<b>lat/lon:</b> " . dec2min($row['latitude'], 'lat') . " / " . dec2min($row['longitude'], 'lon') . "   "
                      .  "<a style=\"color:blue; margin-left:2em;\" href=\"http://www.geonames.org/maps/google_" . $row['latitude'] . "_" . $row['longitude'] . ".html\" target=\"_blank\">view in google-maps</a><br>\n"
                      . "<b>country:</b> " . $row['nation_engl'] . " (" . $row['iso_alpha_2_code'] . ")<br>\n"
                      . "<b>admin1 code:</b> $admin1Code\n";
            }
        } else {
            $ret = "nothing found";
        }

        $response->assign('iBox_content', 'innerHTML', $ret);
        $response->script('$("#iBox_content").dialog("option", "title", "search");');
        $response->script('$("#iBox_content").dialog("open");');
    }

    return $response;
}

function useGeoname($geonameid)
{
    global $response;

    $sql = "SELECT grg.geonameid, grg.name, grg.alternatenames, grg.latitude, grg.longitude, grg.`admin1 code`,
             gn.nationID, gn.nation_engl, gn.iso_alpha_2_code
            FROM tbl_geo_ref_geonames grg
             LEFT JOIN tbl_geo_nation gn ON gn.iso_alpha_2_code = grg.`country code`
            WHERE grg.geonameid = " . quoteString($geonameid);
    $result = dbi_query($sql);
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_array($result);
        $lat = dec2min($row['latitude'], 'lat', false);
        $lon = dec2min($row['longitude'], 'lon', false);
        $response->script("fillLocation('" . $lon['deg'] . "', '" . $lon['min'] . "', '" . $lon['sec'] . "', '" . $lon['dir'] . "', '"
                                                 . $lat['deg'] . "', '" . $lat['min'] . "', '" . $lat['sec'] . "', '" . $lat['dir'] . "', '"
                                                 . $row['nationID'] . "')");
    }

    //Hide the iBox module on return
    $response->script('$("#iBox_content").dialog("close");');

    return $response;
}

function makeLinktext($specimenID)
{
    global $response;

    $specimenID = intval($specimenID);

    $ret = '';
    if ($specimenID) {
        $searchIDs = array($specimenID => true);
        $foundIDs = array();
        $checkedLinks = array();
        while ($searchIDs) {
            foreach ($searchIDs as $searchID => $v) {
                $sql = "( SELECT specimens_linkID, specimen1_ID AS specimenID
                          FROM tbl_specimens_links
                          WHERE specimen2_ID = '$searchID' )
                        UNION
                        ( SELECT specimens_linkID, specimen2_ID AS specimenID
                          FROM tbl_specimens_links
                          WHERE specimen1_ID = '$searchID' )
                        ORDER BY specimenID";
                $result = dbi_query($sql);
                while ($row = mysqli_fetch_array($result)) {
                    if (empty($checkedLinks[$row['specimens_linkID']]) && $row['specimenID'] != $specimenID) {
                        $foundIDs[$row['specimenID']] = true;
                        $searchIDs[$row['specimenID']] = true;
                        $checkedLinks[$row['specimens_linkID']] = true;
                    }
                }
                unset($searchIDs[$searchID]);
            }
        }

        if ($foundIDs) {
            $ret = "<ul>";
            foreach ($foundIDs as $foundID => $v) {
                $sql = "SELECT s.HerbNummer, s.taxonID, m.source_code
                        FROM tbl_specimens s, tbl_management_collections mc, herbarinput.meta m
                        WHERE s.collectionID = mc.collectionID
                         AND mc.source_id = m.source_id
                         AND s.specimen_ID = '$foundID'";
                $row2 = mysqli_fetch_array(dbi_query($sql));
                $ret .= "<li><a href=\"editSpecimens.php?sel=" . htmlentities("<$foundID>") . "&ptid=0\">"
                      . $row2['source_code'] . $row2['HerbNummer'] . ": " . getScientificName($row2['taxonID'], false, false) . "</a></li>";
            }
            $ret .= "</ul>";

            $response->script("linktext = '" . $ret . "';");
        }
    }

    return $response;
}

function editLink($specimenID)
{
    global $response;

    $specimenID = intval($specimenID);

    unset($institution);
    $sql = "SELECT source_id, source_code FROM herbarinput.meta ORDER BY source_code";
    $result = dbi_query($sql);
    while ($row = mysqli_fetch_array($result)) {
        $institution[0][] = $row['source_id'];
        $institution[1][] = substr($row['source_code'], 0, 3);
    }

    if ($specimenID) {
        $ret = "<form id=\"f_iBox\">\n"
             . "<input type=\"hidden\" name=\"linkSpecimenID\" id=\"linkSpecimenID\" value=\"$specimenID\">\n"
             . "<table>\n";
        if (($_SESSION['editControl'] & 0x2000) != 0) {
            $ret .= "<tr><td colspan=\"3\">"
                  . "<input type=\"submit\" class=\"cssfbutton\" value=\"update\" onClick=\"jaxon_updateLink(jaxon.getFormValues('f_iBox')); return false;\">"
                  . "</td></tr>\n";
        }
        $sql = "( SELECT specimens_linkID, specimen1_ID AS specimenID
                  FROM tbl_specimens_links
                  WHERE specimen2_ID = '$specimenID' )
                UNION
                ( SELECT specimens_linkID, specimen2_ID AS specimenID
                  FROM tbl_specimens_links
                  WHERE specimen1_ID = '$specimenID' )
                ORDER BY specimenID";
        $result = dbi_query($sql);
        while ($row = mysqli_fetch_array($result)) {
            $id = $row['specimens_linkID'];
            $sql = "SELECT s.HerbNummer, mc.source_id
                    FROM tbl_specimens s, tbl_management_collections mc
                    WHERE s.collectionID = mc.collectionID
                     AND s.specimen_ID = '" . $row['specimenID'] . "'";
            $row2 = mysqli_fetch_array(dbi_query($sql));
            $ret .= "<tr><td align=\"center\">"
                  . makeInstitutionDropdown($institution, $row2['source_id'], $id)
                  . "</td><td>"
                  . "<input class=\"cssftext\" style=\"width: 20em;\" type=\"text\" name=\"linkSpecimen_$id\" id=\"linkSpecimen_$id\" value=\"" . htmlspecialchars($row2['HerbNummer']) . "\">"
                  . "</td><td align=\"center\">";
            if (($_SESSION['editControl'] & 0x2000) != 0) {
                $ret .= "<img src=\"webimages/remove.png\" title=\"delete entry\" onclick=\"jaxon_deleteLink('" . $row['specimens_linkID'] . "', '$specimenID');\">";
            }
            $ret .= "</td></tr>\n";
        }
        $ret .= "<tr><td align=\"center\">"
              . makeInstitutionDropdown($institution, 0, 0)
              . "</td><td>"
              . "<input class=\"cssftext\" style=\"width: 20em;\" type=\"text\" name=\"linkSpecimen_0\" id=\"linkSpecimen_0\" value=\"\">"
              . "</td><td></td></tr>\n"
              . "</table>\n"
              . "</form>\n";

        $response->assign('iBox_content', 'innerHTML', $ret);
        $response->script('$("#iBox_content").dialog("option", "title", "edit links");');
        $response->script('$("#iBox_content").dialog("open");');
    }
    return $response;
}

function updateLink($formData)
{
    global $response;

    $specimenID = intval($formData['linkSpecimenID']);

    if ($specimenID && ($_SESSION['editControl'] & 0x2000) != 0) {
        foreach ($formData as $key => $val) {
            if (substr($key, 0, 13) == 'linkSpecimen_' && trim($val)) {
                $linkID = intval(substr($key, 13));
                $sql = "SELECT s.specimen_ID
                        FROM tbl_specimens s, tbl_management_collections mc
                        WHERE s.collectionID = mc.collectionID
                         AND s.HerbNummer = " . quoteString($formData['linkSpecimen_' . $linkID]) . "
                         AND mc.source_id = '" . intval($formData['linkInstitution_' . $linkID]) . "'";
                $result = dbi_query($sql);
                if (mysqli_num_rows($result) > 0) {
                    $row = mysqli_fetch_array($result);
                    $targetID = $row['specimen_ID'];
                    if ($specimenID != $targetID) {
                        $sqldata = "specimen1_ID = '" . $specimenID . "',
                                    specimen2_ID = '" . $targetID . "'";
                        if ($linkID > 0) {
                            $sql = "UPDATE tbl_specimens_links SET
                                    $sqldata
                                    WHERE specimens_linkID = '" . $linkID . "'";
                        } else {
                            $sql = "INSERT INTO tbl_specimens_links SET
                                    $sqldata";
                        }
                        dbi_query($sql);
                    }
                }
            }
        }
    }

    makeLinktext($specimenID);

    //Hide the iBox module on return
    $response->script('$("#iBox_content").dialog("close");');

    return $response;
}

function deleteLink($linkID, $specimenID)
{
    global $response;

    $linkID = intval($linkID);

    if ($specimenID && ($_SESSION['editControl'] & 0x2000) != 0) {
        dbi_query("DELETE FROM tbl_specimens_links WHERE specimens_linkID = '" . $linkID . "'");
    }

    makeLinktext($specimenID);
    editLink($specimenID);

    return $response;
}

function editMultiTaxa ($specimenID)
{
    global $response;

    $specimenID = intval($specimenID);

    if ($specimenID) {
        $ret = "<form id='f_iBox'>\n"
             . "<input type='hidden' name='multiTaxa_specimen_ID' id='multiTaxa_specimen_ID' value='$specimenID'>\n"
             . "<table>\n";
        if (($_SESSION['editControl'] & 0x2000) != 0) {
            $ret .= "<tr><td colspan='4'>"
                  . "<input type='submit' class='cssfbutton' value='update' onClick=\"jaxon_updateMultiTaxa(jaxon.getFormValues('f_iBox')); return false;\">"
                  . "</td></tr>\n";
        }
        $sql = "SELECT tst.specimens_tax_ID, tst.taxonID, tg.genus,
                 ta.author,  ta1.author  author1,  ta2.author  author2,  ta3.author  author3,  ta4.author  author4,  ta5.author  author5,
                 te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3, te4.epithet epithet4, te5.epithet epithet5
                FROM (tbl_specimens_taxa tst, tbl_tax_species ts)
                 LEFT JOIN tbl_tax_authors ta ON ta.authorID = ts.authorID
                 LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID = ts.subspecies_authorID
                 LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID = ts.variety_authorID
                 LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID = ts.subvariety_authorID
                 LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID = ts.forma_authorID
                 LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID = ts.subforma_authorID
                 LEFT JOIN tbl_tax_epithets te ON te.epithetID = ts.speciesID
                 LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID = ts.subspeciesID
                 LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID = ts.varietyID
                 LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID = ts.subvarietyID
                 LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID = ts.formaID
                 LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID = ts.subformaID
                 LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
                WHERE tst.taxonID = ts.taxonID
                 AND specimen_ID = '$specimenID'
                ORDER BY tg.genus, te.epithet, te1.epithet, te2.epithet, te3.epithet, te4.epithet, te5.epithet";
        $result = dbi_query($sql);
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_array($result)) {
                $id = $row['specimens_tax_ID'];
                $ret .= "<tr><td>"
                      . "<input class='cssftextAutocomplete' style='width: 35em;' type='text' name='multiTaxaData_$id' id='multiTaxaData_$id' "
                      . "value='" . taxon($row) . "'>"
                      . "</td><td align='center'>";
                if (($_SESSION['editControl'] & 0x2000) != 0) {
                    $ret .= "<img src='webimages/remove.png' title='delete entry' onclick=\"jaxon_deleteMultiTaxa('" . $row['specimens_tax_ID'] . "', '$specimenID');\">";
                }
                $ret .= "</td></tr>\n";
                $response->script("setTimeout(\"call_makeAutocompleter('multiTaxaData_$id')\",100);");
            }
        }
        $ret .= "<tr><td>"
              . "<input class='cssftextAutocomplete' style='width: 35em;' type='text' name='multiTaxaData_0' id='multiTaxaData_0' value=''>"
              . "</td></tr>\n";
        $response->script("setTimeout(\"call_makeAutocompleter('multiTaxaData_0')\",100);");

        $ret .= "</table>\n"
              . "</form>\n";

        $response->assign('iBox_content', 'innerHTML', $ret);
        $response->script('$("#iBox_content").dialog("option", "title", "edit multiple taxa");');
        $response->script('$("#iBox_content").dialog("open");');
    }
    return $response;
}

function updateMultiTaxa ($formData)
{
    global $response;

    $specimenID = intval($formData['multiTaxa_specimen_ID']);

    if ($specimenID && ($_SESSION['editControl'] & 0x2000) != 0) {
        foreach ($formData as $key => $val) {
            if (substr($key, 0, 14) == 'multiTaxaData_' && trim($val)) {
                $specimens_tax_ID = intval(substr($key, 14));
                $taxonID = extractID($val);
                if ($taxonID != "NULL") {
                    if ($specimens_tax_ID > 0) {
                        $sql = "UPDATE tbl_specimens_taxa SET
                                 specimen_ID = '$specimenID',
                                 taxonID = $taxonID
                                WHERE specimens_tax_ID = '" . $specimens_tax_ID . "'";
                    } else {
                        $sql = "INSERT INTO tbl_specimens_taxa SET
                                 specimen_ID = '$specimenID',
                                 taxonID = $taxonID";
                    }
                    dbi_query($sql);
                }
            }
        }
    }

    editMultiTaxa($specimenID);

    return $response;
}

function deleteMultiTaxa ($specimens_tax_ID, $specimenID)
{
    global $response;

    $specimens_tax_ID = intval($specimens_tax_ID);

    if ($specimenID && ($_SESSION['editControl'] & 0x2000) != 0) {
        dbi_query("DELETE FROM tbl_specimens_taxa WHERE specimens_tax_ID = '" . $specimens_tax_ID . "'");
    }

    editMultiTaxa($specimenID);

    return $response;
}


/**
 * register all jaxon-functions in this file
 */
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "toggleLanguage");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "searchGeonames");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "useGeoname");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "makeLinktext");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "editLink");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "updateLink");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "deleteLink");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "editMultiTaxa");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "updateMultiTaxa");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "deleteMultiTaxa");
$jaxon->processRequest();