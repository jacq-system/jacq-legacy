<?php
session_start();
require("../inc/connect.php");
require("../inc/herbardb_input_functions.php");
//no_magic();   das funktioniert bei ajax NICHT!!!!!  Vorsicht bei Datenbankupdates!!

function make_taxon($value)
{
    $results = array();
    if ($value && strlen($value) > 1) {
        $pieces = explode(chr(194) . chr(183), $value);
        $pieces = explode(" ", $pieces[0]);
        $sql = "SELECT taxonID, tg.genus,
                 ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
                 ta4.author author4, ta5.author author5,
                 te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
                 te4.epithet epithet4, te5.epithet epithet5
                FROM tbl_tax_species ts
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
                WHERE ts.external = 0
                 AND tg.genus LIKE '" . mysql_escape_string($pieces[0]) . "%' ";
        if ($pieces[1])
            $sql .= "AND te.epithet LIKE '" . mysql_escape_string($pieces[1]) . "%' ";
        $sql .= "ORDER BY tg.genus, te.epithet, epithet1, epithet2, epithet3, epithet4, epithet5";
        $result = db_query($sql);
        if ($result && mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_array($result)) {
                $results[] = array('id'    => $row['taxonID'],
                                   'label' => taxon($row),
                                   'value' => taxon($row));
            }
        }
    }

    return $results;
}


function make_citation($value)
{
    $results = array();
    if ($value && strlen($value) > 1) {
        $pieces = explode(" ", $value);
        $autor = $pieces[0];
        if (count($pieces) > 1 && (strlen($pieces[1]) > 2 || (strlen($pieces[1]) == 2 && substr($pieces[1], 1, 1) != '.'))) {
            $second = $pieces[1];
        } else {
            $second = '';
        }
        $sql ="SELECT citationID, suptitel, le.autor as editor, la.autor,
                l.periodicalID, lp.periodical, vol, part, jahr, pp
               FROM tbl_lit l
                LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID = l.periodicalID
                LEFT JOIN tbl_lit_authors le ON le.autorID = l.editorsID
                LEFT JOIN tbl_lit_authors la ON la.autorID = l.autorID
               WHERE (la.autor LIKE '" . mysql_escape_string($autor) . "%'
                   OR le.autor LIKE '" . mysql_escape_string($autor) . "%')";
        if ($second) {
            $sql .= " AND (l.jahr LIKE '" . mysql_escape_string($second) . "%'
                        OR l.titel LIKE '" . mysql_escape_string($second) . "%'
                        OR lp.periodical LIKE '" . mysql_escape_string($second) . "%')";
        }
        $sql .= " ORDER BY la.autor, jahr, lp.periodical, vol, part, pp";
        $result = db_query($sql);
        if ($result && mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_array($result)) {
                $results[] = array('id'    => $row['citationID'],
                                   'label' => protolog($row),
                                   'value' => protolog($row));
            }
        }
    }

    //error_log("make_citation: results: " . var_export($results,true),0);
    return $results;
}


function make_person($value)
{
    $results = array();
    if ($value && strlen($value) > 1) {
        $pieces = explode(", ",$value, 2);
        $p_familyname = $pieces[0];
        if (count($pieces) > 1) {
            $pieces = explode(" (", $pieces[1], 2);
            $p_firstname = $pieces[0];
            if (count($pieces) > 1) {
                $pieces = explode(" - ", $pieces[1], 2);
                $p_birthdate = $pieces[0];
                if (count($pieces) > 1) {
                    $pieces = explode(") <", $pieces[1], 2);
                    $p_death = $pieces[0];
                } else {
                    $p_death = '';
                }
            } else {
                $p_birthdate = $p_death = '';
            }
        } else {
            $p_firstname = $p_birthdate = $p_death = '';
        }
        $sql = "SELECT person_ID, p_familyname, p_firstname, p_birthdate, p_death
                FROM tbl_person
                WHERE p_familyname LIKE '" . mysql_escape_string($p_familyname) . "%' ";
        if ($p_firstname) $sql .= "AND p_firstname LIKE '" . mysql_escape_string($p_firstname) . "%' ";
        if ($p_birthdate) $sql .= "AND p_birthdate LIKE '" . mysql_escape_string($p_birthdate) . "%' ";
        if ($p_death)     $sql .= "AND p_death LIKE '" . mysql_escape_string($p_death) . "%' ";
        $sql .= " ORDER BY p_familyname, p_firstname, p_birthdate, p_death";
        $result = db_query($sql);
        if ($result && mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_array($result)) {
                $text = $row['p_familyname'] . ", " . $row['p_firstname']
                      . " (" . $row['p_birthdate'] . " - " . $row['p_death'] . ") <" . $row['person_ID'] . ">";
                $results[] = array('id'    => $row['person_ID'],
                                   'label' => $text,
                                   'value' => $text);
            }
        }
    }

    return $results;
}


//********** main **********//

ob_start();  // intercept all output
//error_log("editLitTaxaAutocomplete.php: BEGIN",0);

$data  = '';
$field = '';
$term  = '';
if (isset($_GET['field'])) {
    $field = $_GET["field"];
}
if (isset($_GET['term'])) {
    $term = $_GET['term'];
}

//error_log("field = " . $field . ", term = " . $term,0);

if ((!empty($term)) && (!empty($field))) {
    if (($field == 'taxon') || ($field == 'taxonAcc')) {
        $name = 'taxon';
    } else if ($field == 'sourceLit') {
        $name = 'citation';
    } else if ($field == 'sourcePers') {
        $name = 'person';
    } else {
        $name = '';
    }
}

if ($name) {
    $func = 'make_'.$name;
    $data = $func(removeID($term));
}

$errors = ob_get_clean();

if ($errors) {
    $data = array(array('id'    => 0,
                        'label' => $errors,
                        'value' => $errors));
}

print json_encode($data);


/*if (isset($_POST['taxon'])) {
    $results = make_taxon($_POST['taxon']);
} elseif (isset($_POST['taxonAcc'])) {
    $results = make_taxon($_POST['taxonAcc']);
} elseif (isset($_POST['sourceLit'])) {
    $results = make_citation($_POST['sourceLit']);
} elseif (isset($_POST['sourcePers'])) {
    $results = make_person($_POST['sourcePers']);
} else {
    $results = "";
}
if ($results) {
    $data = "<ul>\n";
    foreach ($results as $result) {
        $data .= "<li>" . htmlspecialchars($result) . "</li>\n";
    }
    $data .= "</ul>\n";
} else {
    $data = "";
}

$errors = ob_get_clean();

if ($errors) {
    $data = "<ul>\n"
          . "<li>" . $errors . "</li>\n"
          . "</ul>\n";
}

print $data;
 * */