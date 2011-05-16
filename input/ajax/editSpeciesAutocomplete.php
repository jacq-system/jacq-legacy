<?php
session_start();
require("../inc/connect.php");
require("../inc/herbardb_input_functions.php");
//no_magic();   das funktioniert bei ajax NICHT!!!!!  Vorsicht bei Datenbankupdates!!


/**
 * get data for genera dropdown
 *
 * @param string $value genus to search for
 * @return array list of results
 */
function make_gen($value)
{
    $results = array();
    if ($value && strlen($value)>1) {
        $pieces = explode(" ",$value);
        $sql = "SELECT tg.genus, tg.genID, tg.DallaTorreIDs, tg.DallaTorreZusatzIDs,
                 ta.author, tf.family, tsc.category
                FROM tbl_tax_genera tg
                 LEFT JOIN tbl_tax_authors ta ON ta.authorID = tg.authorID
                 LEFT JOIN tbl_tax_families tf ON tg.familyID = tf.familyID
                 LEFT JOIN tbl_tax_systematic_categories tsc ON tf.categoryID = tsc.categoryID
                WHERE genus LIKE '" . mysql_escape_string($pieces[0]) . "%'
                ORDER BY tg.genus";
        $result = db_query($sql);
        while ($row = mysql_fetch_array($result)) {
            $text = $row['genus'] . " " . $row['author'] . " " . $row['family'] . " "
                  . $row['category'] . " " . $row['DallaTorreIDs'] . $row['DallaTorreZusatzIDs']
                  . " <" . $row['genID'] . ">";
            $results[] = array('id'    => $row['genID'],
                               'label' => $text,
                               'value' => $text);
        }
        foreach ($results as $k => $v) {
            $results[$k]['label'] = preg_replace("/ [\s]+/"," ",$v['label']);
            $results[$k]['value'] = preg_replace("/ [\s]+/"," ",$v['value']);
        }
    }
    
    return $results;
}


/**
 * get data for accepted taxon and basionym
 *
 * @param string $value taxon to search for
 * @return array list of results
 */
function make_syn($value)
{    
    $results = array();
    if ($value && strlen($value) > 1) {
        $pieces = explode(chr(194) . chr(183), $value);
        $pieces = explode(" ", $pieces[0]);
        $sql = "SELECT ts.taxonID, tg.genus, tg.DallaTorreIDs, tg.DallaTorreZusatzIDs,
                 ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
                 ta4.author author4, ta5.author author5,
                 te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
                 te4.epithet epithet4, te5.epithet epithet5
                FROM tbl_tax_species ts
                 LEFT JOIN tbl_tax_authors ta ON ta.authorID=ts.authorID
                 LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID=ts.subspecies_authorID
                 LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID=ts.variety_authorID
                 LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID=ts.subvariety_authorID
                 LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID=ts.forma_authorID
                 LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID=ts.subforma_authorID
                 LEFT JOIN tbl_tax_epithets te ON te.epithetID=ts.speciesID
                 LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID=ts.subspeciesID
                 LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID=ts.varietyID
                 LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID=ts.subvarietyID
                 LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID=ts.formaID
                 LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID=ts.subformaID
                 LEFT JOIN tbl_tax_status tst ON tst.statusID=ts.statusID
                 LEFT JOIN tbl_tax_genera tg ON tg.genID=ts.genID
                 LEFT JOIN tbl_tax_families tf ON tf.familyID=tg.familyID
                WHERE genus LIKE '" . mysql_escape_string($pieces[0]) . "%' ";
        if (!empty($pieces[1]))  {
            $sql .= "AND te.epithet LIKE '" . mysql_escape_string($pieces[1]) . "%' ";
        } else {
            $sql .= "AND te.epithet IS NULL ";
        }
        $sql .= "ORDER BY tg.genus, te.epithet";
        $result = db_query($sql);
        while ($row = mysql_fetch_array($result)) {
            $results[] = array('id'    => $row['taxonID'],
                               'label' => taxon($row,true),
                               'value' => taxon($row,true));
}
        foreach ($results as $k => $v) {
            $results[$k]['label'] = preg_replace("/ [\s]+/"," ",$v['label']);
            $results[$k]['value'] = preg_replace("/ [\s]+/"," ",$v['value']);
        }
    }
    
    return $results;
}


/**
 * get data for epithet dropdown
 *
 * @param string $value epithet to search for
 * @return array list of results
 */
function make_epithet($value)
{
    $results = array();
    if ($value && strlen($value)>1) {
        $sql = "SELECT epithet, epithetID
                FROM tbl_tax_epithets
                WHERE epithet LIKE '" . mysql_escape_string($value) . "%'
                 AND external = 0
                ORDER BY epithet";
        $result = db_query($sql);
        while ($row = mysql_fetch_array($result)) {
            $results[] = array('id'    => $row['epithetID'],
                               'label' => $row['epithet'] . " <" . $row['epithetID'] . ">",
                               'value' => $row['epithet'] . " <" . $row['epithetID'] . ">");
        }
    }

    return $results;
}


/**
 * get data for author dropdown
 *
 * @param string $value author to search for
 * @return array list of results
 */
function make_author($value)
{
    $results = array();
    if ($value && strlen($value)>1) {
        $pieces = explode(chr(194) . chr(183) . " [", $value);
        $sql = "SELECT author, authorID, Brummit_Powell_full
                FROM tbl_tax_authors
                WHERE (   author LIKE '" . mysql_escape_string($pieces[0]) . "%'
                       OR Brummit_Powell_full LIKE '" . mysql_escape_string($pieces[0]) . "%')
                 AND external = 0
                ORDER BY author";
        $result = db_query($sql);
        while ($row = mysql_fetch_array($result)) {
            $text = $row['author']
                  . (($row['Brummit_Powell_full']) ? chr(194) . chr(183) . " [" . replaceNewline($row['Brummit_Powell_full']) . "]" : '')
                  . " <" . $row['authorID'] . ">";
            $results[] = array('id'    => $row['authorID'],
                               'label' => $text,
                               'value' => $text);
        }
    }

    return $results;
}


/**
 * ********** main **********
 */

// BP

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
    if (($field == 'species') ||
        ($field == 'subspecies') ||
        ($field == 'variety') ||
        ($field == 'subvariety') ||
        ($field == 'forma') ||
        ($field == 'subforma')) {
        $name = 'epithet';
    } elseif (($field == 'author') ||
            ($field == 'subauthor') ||
            ($field == 'varauthor') ||
            ($field == 'subvarauthor') ||
            ($field == 'forauthor') ||
            ($field == 'subforauthor')) {
        $name = "author";
    } elseif ($field == 'gen') {
        $name = "gen";
    } elseif (($field == 'syn') || ($field == 'bas')) {
        $name = "syn";
    } else {
        $name = "";
    }
    //error_log("name = " . $name . ", data = " . $data,0);
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

//print json_encode($data);
//error_log("RESULT: " . var_export($data,true),0);
print json_encode($data);

/*
if (isset($_POST['species'])) {
    $name = 'epithet';
    $data = $_POST['species'];
} elseif (isset($_POST['subspecies'])) {
    $name = 'epithet';
    //error_log("subspecies",0);
    $data = $_POST['subspecies'];
} elseif (isset($_POST['variety'])) {
    $name = 'epithet';
    $data = $_POST['variety'];
} elseif (isset($_POST['subvariety'])) {
    $name = 'epithet';
    $data = $_POST['subvariety'];
} elseif (isset($_POST['forma'])) {
    $name = 'epithet';
    $data = $_POST['forma'];
} elseif (isset($_POST['subforma'])) {
    $name = 'epithet';
    $data = $_POST['subforma'];
} elseif (isset($_POST['author'])) {
    $name = 'author';
    $data = $_POST['author'];
} elseif (isset($_POST['subauthor'])) {
    $name = 'author';
    $data = $_POST['subauthor'];
} elseif (isset($_POST['varauthor'])) {
    $name = 'author';
    $data = $_POST['varauthor'];
} elseif (isset($_POST['subvarauthor'])) {
    $name = 'author';
    $data = $_POST['subvarauthor'];
} elseif (isset($_POST['forauthor'])) {
    $name = 'author';
    $data = $_POST['forauthor'];
} elseif (isset($_POST['subforauthor'])) {
    $name = 'author';
    $data = $_POST['subforauthor'];
} elseif (isset($_POST['gen'])) {
    $name = 'gen';
    $data = $_POST['gen'];
} elseif (isset($_POST['syn'])) {
    $name = 'syn';
    $data = $_POST['syn'];
} elseif (isset($_POST['bas'])) {
    $name = 'syn';
    $data = $_POST['bas'];
} else {
    $name = $data = "";
}

//error_log("data = " . $data . ", name = " . $name,0);

if ($name) {
    $func = 'make_'.$name;
    //error_log("func = " . $func,0);
    $results = $func(removeID($data));
    $numresults = count($results);

    $data = "<ul>\n";
    foreach ($results as $result) {
    	$data .= "<li>".htmlspecialchars($result)."</li>\n";
    }
    $data .= "</ul>\n";

    print $data;
} else {
    //error_log("no name?",0);
    print "<ul>\n<li>no</li>\n<li>data</li>\n</ul>\n";
}
*/