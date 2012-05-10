<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");
require("inc/herbardb_input_functions.php");
no_magic();


function makeParent($search) {
    $results[] = "";
    if ($search && strlen($search) > 1) {
        $pieces = explode(chr(194) . chr(183), $search);
        $pieces = explode(" ", $pieces[0]);
        $sql = "SELECT ts.taxonID
                FROM tbl_tax_species ts
                 LEFT JOIN tbl_tax_epithets te ON te.epithetID = ts.speciesID
                 LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
                WHERE tg.genus LIKE '" . mysql_escape_string($pieces[0]) . "%'\n";
        if ($pieces[1]) {
            $sql .= "AND te.epithet LIKE '" . mysql_escape_string($pieces[1]) . "%'\n";
        }
        $sql .= "ORDER BY tg.genus, te.epithet";
        if ($result = db_query($sql)) {
            if (mysql_num_rows($result) > 0) {
                while ($row = mysql_fetch_array($result)) {
                    $results[] = getScientificName( $row['taxonID'] );
                }
            }
        }
        foreach ($results as $k => $v) {
            $results[$k] = preg_replace("/ [\s]+/", " ", $v);
        }
    }
    return $results;
}

//
// Hauptprogramm
//

if (isset($_GET['ID'])) {
    // neu aufgerufen
    $id = intval($_GET['ID']);

    $sql = "SELECT taxon_ID_fk, parent_1_ID, parent_2_ID
            FROM tbl_tax_hybrids
            WHERE taxon_ID_fk = '$id'";
    $row = mysql_fetch_array(db_query($sql));
    $newHybrid = ($row['taxon_ID_fk']) ? false : true;

    $sql = "SELECT ts.taxonID, tg.genus,
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
             LEFT JOIN tbl_tax_status tst ON tst.statusID = ts.statusID
             LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
             LEFT JOIN tbl_tax_families tf ON tf.familyID = tg.familyID\n";
    if ($row['parent_1_ID']) {
        $p_parent_1_ID = getScientificName( $row['parent_1_ID'] );
    } else {
        $p_parent_1_ID = "";
    }

    if ($row['parent_2_ID']) {
        $p_parent_2_ID = getScientificName( $row['parent_2_ID'] );
    } else {
        $p_parent_2_ID = "";
    }
} else {
    // reload oder update
    $id            = intval($_POST['ID']);
    $p_parent_1_ID = $_POST['parent_1_ID'];
    $p_parent_2_ID = $_POST['parent_2_ID'];

    $row = mysql_fetch_array(db_query("SELECT taxon_ID_fk FROM tbl_tax_hybrids WHERE taxon_ID_fk = '$id'"));
    $newHybrid = ($row['taxon_ID_fk']) ? false : true;

    if ($_POST['submitUpdate'] && $_SESSION['editorControl']) {
        if ($newHybrid) {
            $sql = "INSERT INTO tbl_tax_hybrids SET
                     taxon_ID_fk = '$id',
                     parent_1_ID = " . extractID($p_parent_1_ID) . ",
                     parent_2_ID = " . extractID($p_parent_2_ID);
        } else {
            $sql = "UPDATE tbl_tax_hybrids SET
                     parent_1_ID = " . extractID($p_parent_1_ID) . ",
                     parent_2_ID = ".extractID($p_parent_2_ID) . "
                    WHERE taxon_ID_fk = '$id'";
        }
        $result = db_query($sql);

        echo "<html><head></head>\n<body>\n"
           . "<script language=\"JavaScript\">\n"
           . "  self.close()\n"
           . "</script>\n"
           . "</body>\n</html>\n";
        die();
    }
}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - edit Hybrids</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
</head>

<body>

<form Action="<?php echo $_SERVER['PHP_SELF']; ?>" Method="POST" name="f">

<?php
$cf = new CSSF();

echo "<input type=\"hidden\" name=\"ID\" value=\"$id\">\n";
$cf->label(8, 0.5, "taxonID");
$cf->text(8, 0.5, "&nbsp;$id");

$cf->label(8, 2.5, "1st Parent");
$cf->editDropdown(8, 2.5, 51, "parent_1_ID", $p_parent_1_ID, makeParent($p_parent_1_ID), 500);

$cf->label(8, 6.5, "2nd Parent");
$cf->editDropdown(8, 6.5, 51, "parent_2_ID", $p_parent_2_ID, makeParent($p_parent_2_ID), 500);

if ($_SESSION['editorControl']) {
    $cf->buttonSubmit(16, 12, "reload", " Reload ");
    if ($newHybrid) {
        $cf->buttonSubmit(31, 12, "submitUpdate", " Insert ");
    } else {
        $cf->buttonSubmit(31, 12, "submitUpdate", " Update ");
    }
}
?>
</form>

</div>
</body>
</html>