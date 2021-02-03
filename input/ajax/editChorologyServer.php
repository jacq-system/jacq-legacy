<?php
session_start();
require("../inc/connect.php");
require("../inc/herbardb_input_functions.php");
require __DIR__ . '/../vendor/autoload.php';

use Jaxon\Jaxon;
use Jaxon\Response\Response;

$jaxon = jaxon();

$response = new Response();


/*-------------------\
 *                   *
 *  local functions  *
 *                   *
 \------------------*/

/**
 * format a taxon string
 * local variation: taxon string with appended state (bold if acc),
 *                  htmlspecialchars is called within this function
 *
 * @param array $row holds the data, keys come from the last db-query
 * @return string formatted output, reday to send to browser
 */
function taxonLocal ($row)
{
    $text = $row['genus'];
    if ($row['epithet'])  $text .= " "          . $row['epithet']  . " " . $row['author'];
    if ($row['epithet1']) $text .= " subsp. "   . $row['epithet1'] . " " . $row['author1'];
    if ($row['epithet2']) $text .= " var. "     . $row['epithet2'] . " " . $row['author2'];
    if ($row['epithet3']) $text .= " subvar. "  . $row['epithet3'] . " " . $row['author3'];
    if ($row['epithet4']) $text .= " forma "    . $row['epithet4'] . " " . $row['author4'];
    if ($row['epithet5']) $text .= " subforma " . $row['epithet5'] . " " . $row['author5'];
    $text = htmlspecialchars($text);
    $text .= " (" . (($row['status'] == 'acc.') ? '<b>' : '') . htmlspecialchars($row['status']) . (($row['status'] == 'acc.') ? '</b>' : '') . ")";

    return $text;
}

/**
 * get the chorology ID, the statuses and the lock-state
 *
 * @param integer $taxonID taxon-ID
 * @param string $source used source
 * @param integer $nationID nation-ID
 * @param integer[optional] $provinceID province-ID, 0 means no province
 * @return array query result, empty if nothing found
 */
function getChorology ($taxonID, $source, $nationID, $provinceID = 0)
{
    $sql = "SELECT tax_chorol_status_ID AS ID, chorol_status AS status, status_debatable, province_debatable, locked
            FROM tbl_tax_chorol_status
            WHERE taxonID_fk = '" . intval($taxonID) . "'
             AND NationID_fk = '" . intval($nationID) . "'";
    if (substr($source, 0, 10) == 'literature') {
        $sql .= " AND citationID_fk = '" . intval(substr($source, 11)) . "'
                  AND personID_fk IS NULL
                  AND serviceID_fk IS NULL";
    } elseif (substr($source, 0, 6) == 'person') {
        $sql .= " AND citationID_fk IS NULL
                  AND personID_fk = '" . intval(substr($source, 7)) . "'
                  AND serviceID_fk IS NULL";
    } elseif (substr($source, 0, 7) == 'service') {
        $sql .= " AND citationID_fk IS NULL
                  AND personID_fk IS NULL
                  AND serviceID_fk = '" . intval(substr($source, 8)) . "'";
    } else {
        $sql .= " AND citationID_fk IS NULL
                  AND personID_fk IS NULL
                  AND serviceID_fk IS NULL";
    }
    if (intval($provinceID)) {
        $sql .= " AND provinceID_fk = '" . intval($provinceID) . "'";
    } else {
        $sql .= " AND provinceID_fk IS NULL";
    }

    $result = db_query($sql);
    if ($result && mysql_num_rows($result) > 0) {
        return mysql_fetch_array($result);
    } else {
        return array();
    }
}

/**
 * log a change in the status table
 *
 * @param integer $ID primary ID of original status
 */
function logChorologyStatus ($ID)
{
    db_query("INSERT INTO herbarinput_log.log_tax_chorol_status
              ( SELECT NULL, tax_chorol_status_ID, taxonID_fk, citationID_fk, personID_fk, serviceID_fk, chorol_status,
                       NationID_fk, provinceID_fk, dateLastEdited, locked, '" . $_SESSION['uid'] . "', NULL
                FROM tbl_tax_chorol_status
                WHERE tax_chorol_status_ID = '" . intval($ID) . "' )");
}

/**
 * get the iso-alpha-2 string of a nation for a given ID
 *
 * @param integer $nationID nation-ID
 * @return string iso-alpha-2-code
 */
function getNationCode ($nationID)
{
    $result = db_query("SELECT iso_alpha_2_code FROM tbl_geo_nation WHERE nationID = '" . intval($nationID) . "'");
    if ($result && mysql_num_rows($result) > 0) {
        $row = mysql_fetch_array($result);
        return $row['iso_alpha_2_code'];
    } else {
        return '';
    }

}

/**
 * get a list of all provinces for a given nation-ID
 *
 * @param integer $nationID nation-ID
 * @return array result of query
 */
function getProvincesCode ($nationID)
{
    $result = db_query("SELECT provinceID, provinz_code
                        FROM tbl_geo_province
                        WHERE nationID = '" . intval($nationID) . "'
                        ORDER BY provinz");
    $provinces = array();
    if ($result && mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_array($result)) {
            $provinces[$row['provinceID']] = $row['provinz_code'];
        }
    }

    return $provinces;
}

/**
 * form a query out of the sent form values
 *
 * @param array $formValues form values
 * @return string formatted query
 */
function makeSearchQuery ($formValues)
{
    // generate the query
    $sql = "SELECT ts.taxonID, tg.genus, tst.status,
             ta.author, ta1.author author1, ta2.author author2, ta3.author author3, ta4.author author4, ta5.author author5,
             te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3, te4.epithet epithet4, te5.epithet epithet5
            FROM (tbl_tax_species ts, tbl_tax_synonymy tsy)
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
             LEFT JOIN tbl_tax_families tf ON tf.familyID = tg.familyID
             LEFT JOIN tbl_tax_status tst ON tst.statusID = ts.statusID
            WHERE ts.taxonID = tsy.taxonID ";
    if (substr($formValues['projectSource'], 0, 10) == 'literature') {
        $sql .= "AND tsy.source_citationID = '" . intval(substr($formValues['projectSource'], 11)) . "' ";
    } elseif (substr($formValues['projectSource'], 0, 6) == 'person') {
        $sql .= "AND tsy.source_person_ID = '" . intval(substr($formValues['projectSource'], 7)) . "' ";
    } elseif (substr($formValues['projectSource'], 0, 7) == 'service') {
        $sql .= "AND tsy.source_serviceID = '" . intval(substr($formValues['projectSource'], 8)) . "' ";
    }
    if ($formValues['status'] != "everything") {
        if ($formValues['species']) {
            $sql .= "AND (    te.epithet LIKE '" . mysql_real_escape_string($formValues['species']) . "%'
                          OR te1.epithet LIKE '" . mysql_real_escape_string($formValues['species']) . "%'
                          OR te2.epithet LIKE '" . mysql_real_escape_string($formValues['species']) . "%'
                          OR te3.epithet LIKE '" . mysql_real_escape_string($formValues['species']) . "%'
                          OR te4.epithet LIKE '" . mysql_real_escape_string($formValues['species']) . "%'
                          OR te5.epithet LIKE '" . mysql_real_escape_string($formValues['species']) . "%') ";
        } else {
            $sql .= "AND te.epithet IS NULL ";
        }
        if ($formValues['status']) {
            $sql .= "AND ts.statusID = " . extractID($formValues['status']) . " ";
        }
    }
    if ($formValues['rank']) {
        $sql .= "AND ts.tax_rankID = " . extractID($formValues['rank']) . " ";
    }
    if (!empty($_SESSION['editFamily'])) {
        $sql .= "AND family LIKE '" . mysql_escape_string($_SESSION['editFamily']) . "%' ";
    } elseif ($formValues['family']) {
        $sql .= "AND family LIKE '" . mysql_escape_string($formValues['family']) . "%' ";
    }
    if ($formValues['genus']) {
        $sql .= "AND genus LIKE '" . mysql_escape_string($formValues['genus']) . "%' ";
    }
    if ($formValues['author']) {
        $sql .= "AND (    ta.author LIKE '%" . mysql_real_escape_string($formValues['author']) . "%'
                      OR ta1.author LIKE '%" . mysql_real_escape_string($formValues['author']) . "%'
                      OR ta2.author LIKE '%" . mysql_real_escape_string($formValues['author']) . "%'
                      OR ta3.author LIKE '%" . mysql_real_escape_string($formValues['author']) . "%'
                      OR ta4.author LIKE '%" . mysql_real_escape_string($formValues['author']) . "%'
                      OR ta5.author LIKE '%" . mysql_real_escape_string($formValues['author']) . "%') ";
    }
    if ($formValues['annotation']) {
        $sql .= "AND ts.annotation LIKE '%" . mysql_real_escape_string($formValues['annotation']) . "%' ";
    }
    $sql .= "ORDER BY genus, family, epithet, author, epithet1, author1, epithet2, author2,
                      epithet3, author3, epithet4, author4, epithet5, author5
             LIMIT 1001";
    return $sql;
}

/**
 * make the chorology dropdown(s) for a given combination of chorologies
 *
 * @param string $chorology chorologies (separated by /)
 * @param integer $taxonID taxon-ID 8needed for a proper name of the select-tag)
 * @param integer $provinceID province-ID (needed for a proper name of the select-tag)
 * @return string the formated dropdown(s)
 */
function makeChorologyDropdowns ($chorology, $taxonID, $provinceID = 0)
{
    $sql = "SELECT chorol_status, chorol_stat_ID, combinations
            FROM chorology.tbl_chorol_status
            WHERE combinations IS NOT NULL
            ORDER BY chorol_stat_ID";
    $result = db_query($sql);
    unset($children);
    while ($row = mysql_fetch_array($result)) {
        $elements[$row['chorol_stat_ID']] = $row['chorol_status'];
        $parts = explode('/', $row['combinations']);
        foreach ($parts as $part) {
            $children[$part][] = $row['chorol_stat_ID'];
        }
    }

    $selectedChorologies = explode('/', $chorology);
    $dropdowns = '';
    $parent = 0;
    for ($level = 0; $level < 5; $level++) {
        if (!empty($children[$parent])) {
            $dropdowns .= "<select name='chorol_{$level}_{$taxonID}_{$provinceID}' onchange='chorologyChanged({$taxonID}, {$provinceID});'>\n"
                        . "<option></option>";
            $selected = (!empty($selectedChorologies[$level])) ? $selectedChorologies[$level] : '';
            $containsSelected = false;
            foreach ($children[$parent] as $child) {
                $dropdowns .= "<option value='$child'" . (($selected == $child) ? ' selected' : '') . ">"
                            . htmlspecialchars($elements[$child])
                            . "</option>";
                if ($selected == $child) $containsSelected = true;
            }
            $dropdowns .= "</select>\n";
            if ($containsSelected) {
                $parent = $selectedChorologies[$level];
            } else {
                break;
            }
        } else {
            break;
        }

    }

    /*
    $selected = (!empty($parts[$level])) ? $parts[$level] : '';

    $dropdown = "<select name='chorol_{$level}_{$taxonID}_{$provinceID}'>\n"
              . "<option></option>";
    foreach ($elements as $element) {
        $dropdown .= "<option value='" . $element['chorol_stat_ID'] . "'" . (($selected == $element['chorol_stat_ID']) ? ' selected' : '') . ">"
                   . htmlspecialchars($element['chorol_status'])
                   . "</option>";
    }
    $dropdown .= "</select>\n";
*/
    return $dropdowns;
}


/*-------------------\
 *                   *
 *  jaxon functions  *
 *                   *
 \------------------*/

/**
 * react on a changed project
 *
 * @global Response $response jaxon response object
 * @param array $formValues form values
 * @return Response send response back to caller
 */
function projectChanged ($formValues)
{
    global $response;

    ob_start();  // intercept all output

    //---
    // make sources-dropdown
    //---
    $selectSource = "<select name='projectSource' onchange=\"jaxon_projectDataChanged(jaxon.getFormValues('f'));\">\n"
                  . "  <option></option>\n";

    // first list all possible literatures
    $result = db_query("SELECT l.citationID, l.suptitel, l.periodicalID, l.vol, l.part, l.jahr, l.pp,
                         le.autor as editor, la.autor, lp.periodical
                        FROM projects.tbl_project_references pj, tbl_lit l
                         LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID = l.periodicalID
                         LEFT JOIN tbl_lit_authors le ON le.autorID = l.editorsID
                         LEFT JOIN tbl_lit_authors la ON la.autorID = l.autorID
                        WHERE pj.source_citationID = l.citationID
                         AND project_ID = '" . intval($formValues['project']) . "'
                        ORDER BY la.autor, jahr, lp.periodical, vol, part, pp");
    if ($result && mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_array($result)) {
            $selectSource .= "  <option value='literature_" . $row['citationID'] . "'>literature: "
                           . htmlspecialchars(protolog($row))
                           . "</option>\n";
        }
    }

    // next all possible persons
    $result = db_query("SELECT p.person_ID, p_familyname, p_firstname, p_birthdate, p_death
                        FROM projects.tbl_project_references pj, tbl_person p
                        WHERE pj.source_person_ID = p.person_ID
                         AND project_ID = '" . intval($formValues['project']) . "'
                        ORDER BY p_familyname, p_firstname, p_birthdate, p_death");
    if ($result && mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_array($result)) {
            $selectSource .= "  <option value='person_" . $row['person_ID'] . "'>person: "
                           . htmlspecialchars($row['p_familyname'] . ", " . $row['p_firstname']
                                            . " (" . $row['p_birthdate'] . " - " . $row['p_death'] . ") <" . $row['person_ID'] . ">")
                           . "</option>\n";
        }
    }

    // and finally all possible services
    $result = db_query("SELECT name, s.serviceID
                        FROM projects.tbl_project_references pj, tbl_nom_service s
                        WHERE pj.source_serviceID = s.serviceID
                         AND project_ID = '" . intval($formValues['project']) . "'
                        ORDER BY name");
    if ($result && mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_array($result)) {
            $selectSource .= "  <option value='service_" . $row['serviceID'] . "'>service: "
                           . htmlspecialchars($row['name'])
                           . "</option>\n";
        }
    }

    $selectSource .= "</select>\n";


    //---
    // make nations-dropdown
    //---
    $selectNation = "<select name='projectNation' onchange=\"jaxon_projectDataChanged(jaxon.getFormValues('f'));\">\n"
                  . "  <option></option>\n";

    $result = db_query("SELECT nation, iso_alpha_2_code, n.nationID
                        FROM projects.tbl_project_countries pc, tbl_geo_nation n
                        WHERE pc.nationID = n.nationID
                         AND project_ID = '" . intval($formValues['project']) . "'
                        ORDER BY nation");
    if ($result && mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_array($result)) {
            $selectNation .= "  <option value='" . $row['nationID'] . "'>"
                           . htmlspecialchars($row['nation'] . " (" . $row['iso_alpha_2_code'] . ")")
                           . "</option>\n";
        }
    }

    $selectNation .= "</select>\n";


    //---
    // send all results
    //---
    $output = ob_get_clean();
    if ($output) {
        $response->alert($output);
    } else {
        $response->assign('projectSource', 'innerHTML', $selectSource);
        $response->assign('projectNation', 'innerHTML', $selectNation);
        $response->assign('jaxonResult', 'innerHTML', '');
    }

    return $response;
}

/**
 * react on a change in project data (source or nation)
 *
 * @global Response $response jaxon response object
 * @param array $formValues form values
 * @return Response send response back to caller
 */
function projectDataChanged ($formValues)
{
    global $response;

    editDistribution($formValues);

    return $response;
}

/**
 * show the table for distribution editing
 *
 * @global Response $response jaxon response object
 * @param array $formValues form values
 * @return Response send response back to caller
 */
function editDistribution ($formValues)
{
    global $response;

    ob_start();  // intercept all output

    if (!empty($formValues['project']) && !empty($formValues['projectSource']) && !empty($formValues['projectNation'])) {

        if (   !empty($formValues['status']) || !empty($formValues['species']) || !empty($formValues['rank'])
            || !empty($formValues['family']) || !empty($formValues['genus'])   || !empty($formValues['author'])
            || !empty($formValues['annotation'])) {

            // execute the query and process the results
            $result = db_query(makeSearchQuery($formValues));
            if (mysql_num_rows($result) > 1000) {
                $ret = "<b>no more than 1000 results allowed</b>\n";
            } elseif (mysql_num_rows($result) > 0) {
                $nation    = getNationCode($formValues['projectNation']);
                $provinces = getProvincesCode($formValues['projectNation']);

                $ret = '';
                if (checkRight('chorol')) {
                    $ret .= "<input class='button' type='submit' name='update' value=' update ' onclick=\"jaxon_updateDistribution(jaxon.getFormValues('f')); return false;\">";
                }
                $ret .= "<input class='button' type='submit' name='editChorology' value=' edit chorology ' onclick=\"jaxon_editChorology(jaxon.getFormValues('f')); return false;\">"
                      . "<table class='out' id='tblDistribution' cellspacing='0'>\n"
                      . "<tr class='out'>"
                      . "<th class='out'>Taxon</th>"
                      . "<th class='out'>?</th>";
                if ($nation) {
                    $ret .= "<th class='out'>$nation</th>";
                    foreach ($provinces as $province) {
                        $ret .= "<th class='out'>$province</th>";
                    }
                }
                $ret .= "</tr>\n";
                while ($row = mysql_fetch_array($result)) {
                    $ret .= "<tr class='out'>"
                          . "<td class='out'>" . taxonLocal($row) . "<input type='hidden' name='ID[]' value='" . $row['taxonID'] . "'></td>";
                    if ($nation) {
                        $numChecked = 0;
                        $debatable = false;
                        $retP = '';
                        foreach ($provinces as $pNum => $province) {
                            $chorology = getChorology($row['taxonID'], $formValues['projectSource'], $formValues['projectNation'], $pNum);
                            $retP .= "<td class='out'><input type='checkbox' name='chorolp_" . $pNum . "_" . $row['taxonID'] . "'";
                            if ($chorology) {
                                $retP .= " checked";
                                $numChecked++;
                                if ($chorology['province_debatable']) {
                                    $debatable = true;
                                }
                            }
                            $retP .= " onchange=\"checkNation('" . $row['taxonID'] . "', this.checked);\"></td>";
                        }
                        $chorology = getChorology($row['taxonID'], $formValues['projectSource'], $formValues['projectNation']);
                        $retN = "<td class='out'><input type='checkbox' name='choroln_" . $row['taxonID'] . "'";
                        if ($chorology || $numChecked) {
                            $retN .= " checked";
                            if ($numChecked) {
                                $retN .= " disabled";
                            }
                            if (!empty($chorology['province_debatable'])) {
                                $debatable = true;
                            }
                        }
                        $retN .= "><input type='hidden' name='choroln_lock_" . $row['taxonID'] . "' value='$numChecked'></td>";
                    }
                    $ret .= "<td class='out'><input type='checkbox' name='debatable_" . $row['taxonID'] . "'" . (($debatable) ? " checked" : "") . "></td>"
                          . $retN
                          . $retP
                          . "</tr>\n";
                }
                $ret .= "</table>\n";
                if (checkRight('chorol')) {
                    $ret .= "<input class='button' type='submit' name='update' value=' update ' onclick=\"jaxon_updateDistribution(jaxon.getFormValues('f')); return false;\">";
                }
            } else {
                $ret = "<b>nothing found!</b>\n";
            }
        } else {
            $ret = '';
        }
    } else {
        $ret = "<b>missing project parameters</b>\n";
    }

    $output = ob_get_clean();
    if ($output) {
        $response->alert($output);
    } else {
        $response->assign('jaxonResult', 'innerHTML', $ret);
        $response->script('$("#tblDistribution").fixedtableheader();');
    }
    return $response;
}

/**
 * update any changes in the distribution
 *
 * @global Response $response jaxon response object
 * @param array $formValues form values
 * @return Response send response back to caller
 */
function updateDistribution($formValues)
{
    global $response;

    ob_start();  // intercept all output

    if (checkRight('chorol')) {
        $provinces = getProvincesCode($formValues['projectNation']);
        $provinces[0] = '';
        foreach ($formValues['ID'] as $taxonID) {
            $cb_n = !empty($formValues['choroln_' . $taxonID]) || ($formValues['choroln_lock_' . $taxonID] > 0);
            foreach ($provinces as $province => $provinceCode) {
                $chorol    = getChorology($taxonID, $formValues['projectSource'], $formValues['projectNation'], $province);
                $cb_p      = !empty($formValues['chorolp_' . $province . '_' . $taxonID]);
                $debatable = (!empty($formValues["debatable_$taxonID"])) ? 1 : 0;
                if ($chorol && !$chorol['locked']) {
                    if ((!$province && !$cb_n) || ($province && !$cb_p)) {
                        logChorologyStatus($chorol['ID']);
                        db_query("DELETE FROM tbl_tax_chorol_status WHERE tax_chorol_status_ID = '" . $chorol['ID'] . "'");
                    } elseif ($chorol['province_debatable'] != $debatable) {
                        logChorologyStatus($chorol['ID']);
                        db_query("UPDATE tbl_tax_chorol_status SET province_debatable = '$debatable' WHERE tax_chorol_status_ID = '" . $chorol['ID'] . "'");
                    }
                } elseif ((!$province && $cb_n) || ($province && $cb_p)) {
                    $sql = "INSERT INTO tbl_tax_chorol_status SET
                             taxonID_fk = '" . intval($taxonID) . "',
                             province_debatable = '$debatable',
                             NationID_fk = '" . intval($formValues['projectNation']) . "',
                             locked = 0";
                    if (substr($formValues['projectSource'], 0, 10) == 'literature') {
                        $sql .= ", citationID_fk = '" . intval(substr($formValues['projectSource'], 11)) . "'";
                    } elseif (substr($formValues['projectSource'], 0, 6) == 'person') {
                        $sql .= ", personID_fk = '" . intval(substr($formValues['projectSource'], 7)) . "'";
                    } elseif (substr($formValues['projectSource'], 0, 7) == 'service') {
                        $sql .= ", serviceID_fk = '" . intval(substr($formValues['projectSource'], 8)) . "'";
                    }
                    if ($province) {
                        $sql .= ", provinceID_fk = '$province'";
                    }
                    db_query($sql);
                }
            }
        }

        editDistribution($formValues);
    }

    $output = ob_get_clean();
    if ($output) {
        $response->alert($output);
    }
    return $response;
}

/**
 * show the table for chorology editing
 *
 * @global Response $response jaxon response object
 * @param array $formValues form values
 * @return Response send response back to caller
 */
function editChorology($formValues)
{
    global $response;

    ob_start();  // intercept all output

    if (!empty($formValues['project']) && !empty($formValues['projectSource']) && !empty($formValues['projectNation'])) {

        if (   !empty($formValues['status']) || !empty($formValues['species']) || !empty($formValues['rank'])
            || !empty($formValues['family']) || !empty($formValues['genus'])   || !empty($formValues['author'])
            || !empty($formValues['annotation'])) {

            // execute the query and process the results
            $result = db_query(makeSearchQuery($formValues));
            if (mysql_num_rows($result) > 1000) {
                $ret = "<b>no more than 1000 results allowed</b>\n";
            } elseif (mysql_num_rows($result) > 0) {
                $nation    = getNationCode($formValues['projectNation']);
                $provinces = getProvincesCode($formValues['projectNation']);

                $ret = '';
                if (checkRight('chorol')) {
                    $ret .= "<input class='button' type='submit' name='update' value=' update ' onclick=\"jaxon_updateChorology(jaxon.getFormValues('f')); return false;\">";
                }
                $ret .= "<input class='button' type='submit' name='editDistribution' value=' edit distribution ' onclick=\"jaxon_editDistribution(jaxon.getFormValues('f')); return false;\">"
                      . "<table class='out' id='tblChorology' cellspacing='0'>\n"
                      . "<tr class='out'>"
                      . "<th class='out'>Taxon</th>"
                      . "<th class='out' colspan='3'>Chorology</th>"
                      . "</tr>\n";
                while ($row = mysql_fetch_array($result)) {
                    $chorology = getChorology($row['taxonID'], $formValues['projectSource'], $formValues['projectNation']);
                    if ($chorology) {
                        $retC = "<td class='out'>$nation</td>"
                              . "<td class='out'>?<input type='checkbox' name='debatable_" . $row['taxonID'] . "_0'" . (($chorology['status_debatable']) ? " checked" : "") . "></td>"
                              . "<td class='out' id='chorol_td_" . $row['taxonID'] . "_0'>" . makeChorologyDropdowns($chorology['status'], $row['taxonID'])
                              . "</td></tr>\n";
                        $lines = 1;
                        foreach ($provinces as $pNum => $province) {
                            $chorology = getChorology($row['taxonID'], $formValues['projectSource'], $formValues['projectNation'], $pNum);
                            if ($chorology) {
                                $retC .= "<tr><td class='out'>$province</td>"
                                       . "<td class='out'>?<input type='checkbox' name='debatable_" . $row['taxonID'] . "_{$pNum}'" . (($chorology['status_debatable']) ? " checked" : "") . "></td>"
                                       . "<td class='out' id='chorol_td_" . $row['taxonID'] . "_{$pNum}'>" . makeChorologyDropdowns($chorology['status'], $row['taxonID'], $pNum)
                                       . "</td></tr>\n";
                                $lines++;
                            }
                        }
                    } else {
                        $retC = "<td></td></tr>\n";
                        $lines = 1;
                    }
                    $ret .= "<tr class='out'>"
                          . "<td class='out' rowspan='$lines'>" . taxonLocal($row)
                          . "<input type='hidden' name='ID[]' value='" . $row['taxonID'] . "'>"
                          . "</td>" . $retC;
                }
                $ret .= "</table>\n";
                if (checkRight('chorol')) {
                    $ret .= "<input class='button' type='submit' name='update' value=' update ' onclick=\"jaxon_updateChorology(jaxon.getFormValues('f')); return false;\">";
                }
            } else {
                $ret = "<b>nothing found!</b>\n";
            }
        } else {
            $ret = '';
        }
    } else {
        $ret = "<b>missing project parameters</b>\n";
    }

    $output = ob_get_clean();
    if ($output) {
        $response->alert($output);
    } else {
        $response->assign('jaxonResult', 'innerHTML', $ret);
        $response->script('$("#tblChorology").fixedtableheader();');
    }
    return $response;
}

/**
 * update any changes in the chorology
 *
 * @global Response $response jaxon response object
 * @param array $formValues form values
 * @return Response send response back to caller
 */
function updateChorology($formValues)
{
    global $response;

    ob_start();  // intercept all output

    if (checkRight('chorol')) {
        $provinces = getProvincesCode($formValues['projectNation']);
        $provinces[0] = '';
        foreach ($formValues['ID'] as $taxonID) {
            foreach ($provinces as $province => $provinceCode) {
                $chorol = getChorology($taxonID, $formValues['projectSource'], $formValues['projectNation'], $province);
                if ($chorol) {
                    $newChorolStatus = '';
                    for ($i = 0; $i < 5 && !empty($formValues["chorol_{$i}_{$taxonID}_{$province}"]); $i++) {
                        $newChorolStatus .= $formValues["chorol_{$i}_{$taxonID}_{$province}"] . '/';
                    }
                    $newChorolStatus = substr($newChorolStatus, 0, -1);
                    $newDebatable = (!empty($formValues["debatable_{$taxonID}_{$province}"])) ? 1 : 0;
                    if (($chorol['status'] != $newChorolStatus || $chorol['status_debatable'] != $newDebatable) && !$chorol['locked']) {
                        logChorologyStatus($chorol['ID']);
                        db_query("UPDATE tbl_tax_chorol_status SET
                                   chorol_status = " . (($newChorolStatus) ? "'$newChorolStatus'" : "NULL") . ",
                                   status_debatable = '$newDebatable'
                                  WHERE tax_chorol_status_ID = '" . $chorol['ID'] . "'");
                    }
                }
            }
        }

        editChorology($formValues);
    }

    $output = ob_get_clean();
    if ($output) {
        $response->alert($output);
    }
    return $response;
}

/**
 * helper function to react on any changes in the chorology dropdowns and open a new dropdown field if neccessary
 *
 * @global Response $response jaxon response object
 * @param array $newChorology new chorologies
 * @param integer $taxonID nation-ID
 * @param integer $provinceID province-ID
 * @return Response send response back to caller
 */
function changeChorology($newChorology, $taxonID, $provinceID)
{
    global $response;

    $response->assign("chorol_td_{$taxonID}_{$provinceID}",
                            'innerHTML',
                            makeChorologyDropdowns(implode('/', $newChorology), $taxonID, $provinceID));
    return $response;
}












/**
 * register all jaxon-functions in this file
 */
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "projectChanged");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "projectDataChanged");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "editDistribution");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "updateDistribution");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "editChorology");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "updateChorology");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "changeChorology");
$jaxon->processRequest();
