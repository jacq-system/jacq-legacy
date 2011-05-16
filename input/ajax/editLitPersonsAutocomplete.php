<?php
session_start();
require("../inc/connect.php");
require("../inc/herbardb_input_functions.php");
//no_magic();   das funktioniert bei ajax NICHT!!!!!  Vorsicht bei Datenbankupdates!!


function make_person($value)
{
    $results = array();
    if ($value && strlen($value) > 1) {
        $pieces = explode(", ", $value, 2);
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
                // BP
                /*$results[] = $row['p_familyname'] . ", " . $row['p_firstname']
                           . " (" . $row['p_birthdate'] . " - " . $row['p_death'] . ") <" . $row['person_ID'] . ">";*/
                $text = $row['p_familyname'] . ", " . $row['p_firstname']
                      . " (" . $row['p_birthdate'] . " - " . $row['p_death'] . ") <" . $row['person_ID'] . ">";
                $results[] = array('id'    => $row['person_ID'],
                                   'label' => $text,
                                   'value' => $text);
            }
        }
    }

    // BP
    /*if (!count($results)) {
        $results[] = "";
    }
    $results[] = "";*/

    return $results;
}


//********** main **********//

ob_start();  // intercept all output

//error_log("editLitPersonsAutocomplete.php: started...",0);

// BP
if (!empty($_GET["term"])) {
    $data = make_person($_GET["term"]);
} else {
    $data = '';
}

// BP
/*
if (isset($_POST['person'])) {
    $results = make_person($_POST['person']);
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
*/

$errors = ob_get_clean();

// BP
if ($errors) {
    $data = array(array('id'    => 0,
                        'label' => $errors,
                        'value' => $errors));
}
/*if ($errors) {
    $data = "<ul>\n"
          . "<li>" . $errors . "</li>\n"
          . "</ul>\n";
}

print $data;*/

print json_encode($data);