<?php
session_start();
require("../inc/connect.php");
require("../inc/herbardb_input_functions.php");
//no_magic();   das funktioniert bei ajax NICHT!!!!!  Vorsicht bei Datenbankupdates!!

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

    return $results;
}


//********** main **********//

ob_start();  // intercept all output

//error_log("editLitAutocomplete.php",0);

if (!empty($_GET["term"])) {
    $data = make_citation($_GET["term"]);
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