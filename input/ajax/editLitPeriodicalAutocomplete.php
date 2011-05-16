<?php
session_start();
require("../inc/connect.php");
require("../inc/herbardb_input_functions.php");
//no_magic();   das funktioniert bei ajax NICHT!!!!!  Vorsicht bei Datenbankupdates!!

function make_successor($value)
{
    $results = array();
    if ($value && strlen($value) > 1) {
        $pieces = explode(" <", $value);
        $sql = "SELECT periodical, periodicalID
                FROM tbl_lit_periodicals
                WHERE periodical LIKE '" . mysql_escape_string($pieces[0]) . "%'
                 OR periodical_full LIKE '%" . mysql_escape_string($pieces[0]) . "%'
                ORDER BY periodical";
        $result = db_query($sql);
        if ($result && mysql_num_rows($result) > 0) {
            while ($row = mysql_fetch_array($result)) {
                $results[] = array('id'    => $row['periodicalID'],
                                   'label' => $row['periodical'] . " <" . $row['periodicalID'] . ">",
                                   'value' => $row['periodical'] . " <" . $row['periodicalID'] . ">");
            }
        }
    }

    return $results;
}


//********** main **********//

ob_start();  // intercept all output

if (!empty($_GET["term"])) {
    $data = make_successor($_GET["term"]);
} else {
    $data = '';
}

$errors = ob_get_clean();

if ($errors) {
    $data = array(array('id'    => 0,
                        'label' => $errors,
                        'value' => $errors));
}

print json_encode($data);