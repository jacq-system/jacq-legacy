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

function makeInstitutionDropdown($institutions, $selected, $id)
{
    $dropdown = "<select class='cssf' name='linkInstitution_$id' id='linkInstitution_$id'>\n";
    foreach ($institutions as $institution) {
        $dropdown .= "<option value='" . $institution[0] . "'";
        if ($selected == $institution[0]) {
            $dropdown .= " selected";
        }
        $dropdown .= ">" . htmlspecialchars($institution[1]) . "</option>\n";
    }
    $dropdown .= "</select>\n";

    return $dropdown;
}

function makeQualifierDropdown($qualifiers, $selected, $id)
{
    $dropdown = "<select class='cssf' name='linkQualifier_$id' id='linkQualifier_$id'>\n";
    $dropdown .= "<option value=''>" . htmlspecialchars('') . "</option>\n";
    foreach ($qualifiers as $qualifier) {
        $dropdown .= "<option value='" . $qualifier[0] . "'";
        if ($selected == $qualifier[0]) {
            $dropdown .= " selected";
        }
        $dropdown .= ">" . htmlspecialchars($qualifier[1]) . "</option>\n";
    }
    $dropdown .= "</select>\n";

    return $dropdown;
}

function jsonGetGeoNames($searchtext)
{
    global $_OPTIONS;

    $curl = curl_init("http://api.geonames.org/searchJSON?name_equals=" . urlencode($searchtext) . "&username=" . $_OPTIONS['GEONAMES']['username']);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $curl_response = curl_exec($curl);
    curl_close($curl);
    if ($curl_response === false) {
        return null;
    } else {
        return json_decode($curl_response, true);
    }
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

    // TODO: change to geonames service
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
                      . "<a style='color:blue; margin-left:2em;' href='http://www.geonames.org/" . $row['geonameid'] . "/' target='_blank'>view in google-maps</a><br>\n"
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

function searchGeonamesService($searchtext)
{
    global $response;

    if (trim($searchtext)) {
        $rows = jsonGetGeoNames($searchtext);
        if ($rows) {
            $ret = "<b>found " . $rows['totalResultsCount'] . (($rows['totalResultsCount'] > 1) ? " entries" : " entry") . "</b><br>\n";
            foreach ($rows['geonames'] as $row) {
                $admin1Code = $row['adminCode1'];
                $ret .= "<hr>\n"
                      . "<b>geonameID:</b> " . $row['geonameId'] . "     "
                      .  "<input type=\"button\" value=\" use this \" onclick=\"jaxon_useGeoname('" . $row['geonameId'] . "');\"><br>\n"
                      . "<b>name:</b> " . $row['name'] . "<br>\n"
                      . (($row['alternatenames']) ? "<b>alternatenames:</b> " . $row['alternatenames'] . "<br>\n" : "")
                      . "<b>lat/lon:</b> " . dec2min($row['lat'], 'lat') . " / " . dec2min($row['lng'], 'lon') . "   "
                      . "<a style='color:blue; margin-left:2em;' href='http://www.geonames.org/" . $row['geonameId'] . "/' target='_blank'>view in google-maps</a><br>\n"
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

    // TODO: change to geonames service
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

    $institution = array();
    $sql = "SELECT source_id, source_code FROM herbarinput.meta ORDER BY source_code";
    $result = dbi_query($sql);
    while ($row = mysqli_fetch_array($result)) {
        $institution[] = array($row['source_id'], $row['source_code']);
    }

    $defaultSourceID = 0;
    $sql = "SELECT mc.source_id
            FROM tbl_specimens s
             LEFT JOIN tbl_management_collections mc ON mc.collectionID = s.collectionID
            WHERE s.specimen_ID = '$specimenID'";
    $result = dbi_query($sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_array($result);
        if (!empty($row['source_id'])) {
            $defaultSourceID = intval($row['source_id']);
        }
    }

    $qualifiers = array();
    $sql = "SELECT link_qualifierID, SpecimenQualifier_engl
            FROM tbl_specimens_links_qualifiers
            ORDER BY SpecimenQualifier_engl";
    $result = dbi_query($sql);
    while ($row = mysqli_fetch_array($result)) {
        $qualifiers[] = array($row['link_qualifierID'], $row['SpecimenQualifier_engl']);
    }

    if ($specimenID) {
        $maxSourceLength = 0;
        foreach ($institution as $inst) {
            $label = $inst[1];
            $maxSourceLength = max($maxSourceLength, strlen($label));
        }
        $sourceWidthEm = max(12, $maxSourceLength * 0.8 + 5);

        $ret = "<form id='f_iBox'>\n"
             . "<input type='hidden' name='linkSpecimenID' id='linkSpecimenID' value='$specimenID'>\n"
             . "<div id='linkErrors' class='error'></div>\n"
             . "<table style='width:100%; table-layout:auto;'>\n"
             . "<tbody id='linkRows'>\n";
        if (($_SESSION['editControl'] & 0x2000) != 0) {
            $ret .= "<tr><td colspan='4'>"
                  . "<input type='submit' class='cssfbutton' value='update' onClick=\"jaxon_updateLink(jaxon.getFormValues('f_iBox')); return false;\">"
                  . "</td></tr>\n";
        }
        $sql = "( SELECT specimens_linkID, specimen1_ID AS specimenID, link_qualifierID
                  FROM tbl_specimens_links
                  WHERE specimen2_ID = '$specimenID' )
                UNION
                ( SELECT specimens_linkID, specimen2_ID AS specimenID, link_qualifierID
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
            $ret .= "<tr class='link-row' data-row-id='$id'><td align='center' style='width:25%;'>"
                  . makeQualifierDropdown($qualifiers, $row['link_qualifierID'], $id)
                  . "</td><td align='center' style='width:" . $sourceWidthEm . "em; white-space:nowrap;'>"
                  . makeInstitutionDropdown($institution, $row2['source_id'], $id)
                  . "</td><td style='width:25%; white-space:nowrap;'>"
                  . "<input class='cssftext' style='width: 12em;' type='text' name='linkSpecimen_$id' id='linkSpecimen_$id' value='" . htmlspecialchars($row2['HerbNummer']) . "'>"
                  . "</td><td align='center'>";
            if (($_SESSION['editControl'] & 0x2000) != 0) {
                $ret .= "<input type='hidden' name='linkDelete_$id' id='linkDelete_$id' value='0'>"
                      . "<span class='link-delete-btn' data-target='$id' title='Mark for deletion' style='cursor:pointer; display:inline-block;'><img src='webimages/remove.png' alt='delete'></span>";
            }
            $ret .= "</td></tr>\n";
        }
        $templateId = "new0";
        $ret .= "<tr class='link-row new-link-row' data-row-id='$templateId' data-template='1'><td align='center' style='width:25%;'>"
              . makeQualifierDropdown($qualifiers, '', $templateId)
              . "</td><td align='center' style='width:" . $sourceWidthEm . "em; white-space:nowrap;'>"
              . makeInstitutionDropdown($institution, $defaultSourceID, $templateId)
              . "</td><td style='width:25%; white-space:nowrap;'>"
              . "<input class='cssftext' style='width: 12em;' type='text' name='linkSpecimen_$templateId' id='linkSpecimen_$templateId' value=''>"
              . "</td><td align='center'>";
        if (($_SESSION['editControl'] & 0x2000) != 0) {
            $ret .= "<input type='hidden' name='linkDelete_$templateId' id='linkDelete_$templateId' value='0'>"
                  . "<span class='link-delete-btn' data-target='$templateId' title='Zeile löschen' style='cursor:pointer; display:inline-block;'><img src='webimages/remove.png' alt='delete'></span>";
        }
        $ret .= "</td></tr>\n";
        $ret .= "</tbody></table>\n";
        if (($_SESSION['editControl'] & 0x2000) != 0) {
            $ret .= "<div class='link-row-actions' style='margin-top:0.5em;'>"
                  . "<button type='button' id='addLinkRow' class='cssfbutton' style='width:2em;' title='Weitere Verknüpfung hinzufügen'>+</button>"
                  . "</div>\n";
        }
        $ret .= "<input type='hidden' id='linkRowNextIndex' value='1'>\n"
              . "</form>\n";

        $response->assign('iBox_content', 'innerHTML', $ret);
        $response->assign('linkErrors', 'innerHTML', '');
        $response->script('setupLinkEditForm();');
        $response->script('$("#iBox_content").dialog("option", "title", "edit links");');
        $response->script('$("#iBox_content").dialog("open");');
    }
    return $response;
}

function updateLink($formData)
{
    global $response;

    $specimenID = intval($formData['linkSpecimenID']);

    $errors = array();

    if ($specimenID && ($_SESSION['editControl'] & 0x2000) != 0) {
        $sourceCodes = array();
        $srcResult = dbi_query("SELECT source_id, source_code FROM herbarinput.meta");
        while ($srcRow = mysqli_fetch_array($srcResult)) {
            $sourceCodes[$srcRow['source_id']] = $srcRow['source_code'];
        }

        foreach ($formData as $key => $val) {
            if (substr($key, 0, 13) == 'linkSpecimen_') {
                $suffix = substr($key, 13);
                $specimenValue = trim($val);

                $qualifierKey = 'linkQualifier_' . $suffix;
                $deleteKey = 'linkDelete_' . $suffix;
                $institutionKey = 'linkInstitution_' . $suffix;

                $qualifierID = null;
                if (array_key_exists($qualifierKey, $formData) && strlen(trim($formData[$qualifierKey])) > 0) {
                    $qualifierID = intval($formData[$qualifierKey]);
                }

                $sourceID = isset($formData[$institutionKey]) ? intval($formData[$institutionKey]) : 0;
                $deleteRequested = !empty($formData[$deleteKey]);

                $isExisting = ctype_digit(ltrim($suffix, '-')) && $suffix !== '';
                $existingID = $isExisting ? intval($suffix) : null;

                if ($isExisting && $deleteRequested) {
                    dbi_query("DELETE FROM tbl_specimens_links WHERE specimens_linkID = '" . $existingID . "'");
                    continue;
                }

                if ($specimenValue === '') {
                    // Nothing to do for empty fields (unless marked for delete handled above)
                    continue;
                }

                if ($sourceID === 0) {
                    $herbNumberEsc = htmlspecialchars($specimenValue, ENT_QUOTES);
                    $errors[] = "Please select a source for herbarium number '" . $herbNumberEsc . "'";
                    continue;
                }

                $sql = "SELECT s.specimen_ID
                        FROM tbl_specimens s, tbl_management_collections mc
                        WHERE s.collectionID = mc.collectionID
                         AND s.HerbNummer = " . quoteString($specimenValue) . "
                         AND mc.source_id = '" . $sourceID . "'";
                $result = dbi_query($sql);
                if (mysqli_num_rows($result) > 0) {
                    $row = mysqli_fetch_array($result);
                    $targetID = $row['specimen_ID'];
                    if ($specimenID != $targetID) {
                        $sqldata = "specimen1_ID = '" . $specimenID . "',
                                    specimen2_ID = '" . $targetID . "',
                                    link_qualifierID = " . (($qualifierID !== null) ? "'" . $qualifierID . "'" : "NULL");
                        if ($isExisting) {
                            $sql = "UPDATE tbl_specimens_links SET
                                    $sqldata
                                    WHERE specimens_linkID = '" . $existingID . "'";
                        } else {
                            $sql = "INSERT INTO tbl_specimens_links SET
                                    $sqldata";
                        }
                        dbi_query($sql);
                    }
                } else {
                    $sourceLabel = isset($sourceCodes[$sourceID]) ? $sourceCodes[$sourceID] : $sourceID;
                    $herbNumberEsc = htmlspecialchars($specimenValue, ENT_QUOTES);
                    $sourceLabelEsc = htmlspecialchars((string)$sourceLabel, ENT_QUOTES);
                    $errors[] = "Herbarium number '" . $herbNumberEsc
                                . "' not found for source '" . $sourceLabelEsc . "'";
                }
            }
        }
    }

    if (!empty($errors)) {
        $response->assign('linkErrors', 'innerHTML', "<ul><li>" . implode("</li><li>", $errors) . "</li></ul>");
        return $response;
    }

    $response->assign('linkErrors', 'innerHTML', '');
    $response->script('iBoxMarkClean();');
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

function displayCollectorLinks($collectorID)
{
    global $response;

    $ret = array();
    $row = dbi_query("SELECT HUH_ID, VIAF_ID, WIKIDATA_ID, ORCID, Bloodhound_ID
                      FROM tbl_collector
                      WHERE SammlerID = '" . intval($collectorID) . "'")
           ->fetch_assoc();
    if (!empty($row['WIKIDATA_ID']) && substr(trim($row['WIKIDATA_ID']), 0, 4) == 'http') {
        $ret[] = "<a href='" . trim($row['WIKIDATA_ID']) . "' title='wikidata' alt='wikidata' target='_blank'>"
               . "<img src='webimages/wikidata.png' width='20px'></a>";
    }
    if (!empty($row['HUH_ID']) && substr(trim($row['HUH_ID']), 0, 4) == 'http') {
        $ret[] = "<a href='" . trim($row['HUH_ID']) . "' title='Index of Botanists (HUH)' alt='Index of Botanists (HUH)' target='_blank'>"
               . "<img src='webimages/huh.png' width='20px'></a>";
    }
    if (!empty($row['VIAF_ID']) && substr(trim($row['VIAF_ID']), 0, 4) == 'http') {
        $ret[] = "<a href='" . trim($row['VIAF_ID']) . "' title='VIAF' alt='VIAF' target='_blank'>"
               . "<img src='webimages/viaf.png' width='20px'></a>";
    }
    if (!empty($row['ORCID']) && substr(trim($row['ORCID']), 0, 4) == 'http') {
        $ret[] = "<a href='" . trim($row['ORCID']) . "' title='ORCID' alt='ORCID' target='_blank'>"
               . "<img src='webimages/orcid.logo.icon.svg' width='20px'></a>";
    }
    if (!empty($row['Bloodhound_ID']) && substr(trim($row['Bloodhound_ID']), 0, 4) == 'http') {
        $ret[] = "<a href='" . trim($row['Bloodhound_ID']) . "' title='Bionomia' alt='Bionomia' target='_blank'>"
               . "<img src='webimages/bionomia_logo.png' width='20px'></a>";
    }

    $response->assign('displayCollectorLinks', 'innerHTML', implode("&nbsp;", $ret));

    return $response;
}


/**
 * register all jaxon-functions in this file
 */
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "toggleLanguage");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "searchGeonames");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "searchGeonamesService");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "useGeoname");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "makeLinktext");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "editLink");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "updateLink");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "deleteLink");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "editMultiTaxa");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "updateMultiTaxa");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "deleteMultiTaxa");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "displayCollectorLinks");
$jaxon->processRequest();
