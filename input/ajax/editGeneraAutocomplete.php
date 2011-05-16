<?php
session_start();
require("../inc/connect.php");
require("../inc/herbardb_input_functions.php");
//no_magic();   das funktioniert bei ajax NICHT!!!!!  Vorsicht bei Datenbankupdates!!

function make_family ($value)
{
    $results = array();
    if ($value && strlen($value) > 1) {
        $pieces = explode(" ", $value);
        $sql = "SELECT family, familyID, category
                FROM tbl_tax_families tf
                 LEFT JOIN tbl_tax_systematic_categories tsc ON tsc.categoryID = tf.categoryID
                WHERE family LIKE '" . mysql_escape_string($pieces[0]) . "%'
                ORDER BY family";
        $result = db_query($sql);
        if ($result && mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_array($result)) {
                $results[] = array('id'    => $row['familyID'],
                                   'label' => $row['family'] . " " . $row['category'] . " <" . $row['familyID'] . ">",
                                   'value' => $row['family'] . " " . $row['category'] . " <" . $row['familyID'] . ">");
            }
        }
    }

    return $results;
}

function make_author ($value)
{
    $results = array();
    if ($value && strlen($value) > 1) {
        $pieces = explode(chr(194) . chr(183) . " [", $value);
        $sql = "SELECT author, authorID, Brummit_Powell_full
                FROM tbl_tax_authors
                WHERE author LIKE '" . mysql_escape_string($pieces[0]) . "%'
                 OR Brummit_Powell_full LIKE '" . mysql_escape_string($pieces[0]) . "%'
                ORDER BY author";
        $result = db_query($sql);
        if ($result && mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_array($result)) {
                $res = $row['author'];
                if ($row['Brummit_Powell_full']) $res .= chr(194) . chr(183) . " [" . replaceNewline($row['Brummit_Powell_full']) . "]";
                $results[] = array('id'    => $row['authorID'],
                                   'label' => $res . " <" . $row['authorID'] . ">",
                                   'value' => $res . " <" . $row['authorID'] . ">");
            }
        }
    }

    return $results;
}

function make_taxon ($value)
{
    $results = array();
    if ($value && strlen($value) > 1) {
        $pieces = explode(chr(194) . chr(183), $value);
        $pieces = explode(" ",$pieces[0]);
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
                WHERE tg.genus LIKE '" . mysql_escape_string($pieces[0]) . "%' ";
        if ($pieces[1]) $sql .= "AND te.epithet LIKE '" . mysql_escape_string($pieces[1]) . "%' ";
        $sql .= "ORDER BY tg.genus, te.epithet, epithet1, epithet2, epithet3";
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


//********** main **********//

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

if ((!empty($term)) && (!empty($field))) {
    if ($field == 'author') {
        $name = 'author';
    } else if ($field == 'family') {
        $name = 'family';
    } else if ($field == 'taxon') {
        $name = 'taxon';
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

/*if (isset($_GET['term'])) {
    $term = $_GET['term'];
}

//error_log("term = " . $term,0);

$data= "TestTestTest";
print json_encode($data); */

/*
if (isset($_POST['author']))
  $name = 'author';
elseif (isset($_POST['family']))
  $name = 'family';
elseif (isset($_POST['taxon']))
  $name = 'taxon';
else
  $name = "";

if ($name) {
  $func = 'make_'.$name;
  $results = $func(removeID($_POST[$name]));

  $data = "<ul>\n";
  foreach ($results as $result) {
  	$data .= "<li>".htmlspecialchars($result)."</li>\n";
  }
  $data .= "</ul>\n";

  print $data;
} */