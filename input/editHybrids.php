<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");
require("inc/herbardb_input_functions.php");
require __DIR__ . '/vendor/autoload.php';

use Jaxon\Jaxon;
use Jacq\Settings;

$jaxon = jaxon();
$jaxon->setOption('core.request.uri', 'ajax/editHybridsServer.php');

$jaxon->register(Jaxon::CALLABLE_FUNCTION, "checkParents");


function makeParent($search)
{
    $results[] = "";
    if ($search && strlen($search) > 1) {
        $pieces = explode(chr(194) . chr(183), $search);
        $pieces = explode(" ", $pieces[0]);
        $sql = "SELECT ts.taxonID
                FROM tbl_tax_species ts
                 LEFT JOIN tbl_tax_epithets te ON te.epithetID = ts.speciesID
                 LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
                WHERE tg.genus LIKE '" . dbi_escape_string($pieces[0]) . "%'\n";
        if (!empty($pieces[1])) {
            $sql .= "AND te.epithet LIKE '" . dbi_escape_string($pieces[1]) . "%'\n";
        }
        $sql .= "ORDER BY tg.genus, te.epithet";
        if ($result = dbi_query($sql)) {
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_array($result)) {
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
    $row = dbi_query($sql)->fetch_array();
    if (!empty($row)) {
        $newHybrid = ($row['taxon_ID_fk']) ? false : true;

        if ($row['parent_1_ID']) {
            $p_parent_1Index = $row['parent_1_ID'];
            $p_parent_1 = getScientificName($p_parent_1Index);
        } else {
            $p_parent_1Index = 0;
            $p_parent_1 = "";
        }

        if ($row['parent_2_ID']) {
            $p_parent_2Index = $row['parent_2_ID'];
            $p_parent_2 = getScientificName($p_parent_2Index);
        } else {
            $p_parent_2Index = 0;
            $p_parent_2 = "";
        }
    } else {
        $newHybrid = true;
        $p_parent_1Index = $p_parent_2Index = 0;
        $p_parent_1 = $p_parent_2 = "";
    }
} else {
    // reload oder update
    $id = intval($_POST['ID']);
    $p_parent_1 = $_POST['parent_1'];
    $p_parent_1Index = (strlen(trim($_POST['parent_1'] ?? "")) > 0) ? intval($_POST['parent_1Index']) : 0;
    $p_parent_2 = $_POST['parent_2'];
    $p_parent_2Index = (strlen(trim($_POST['parent_2'] ?? "")) > 0) ? intval($_POST['parent_2Index']) : 0;

    $row = mysqli_fetch_array(dbi_query("SELECT taxon_ID_fk FROM tbl_tax_hybrids WHERE taxon_ID_fk = '$id'"));
    $newHybrid = (!empty($row['taxon_ID_fk'])) ? false : true;

    if (!empty($_POST['submitUpdate']) && $_SESSION['editorControl']) {
        if (!empty(extractID($p_parent_1)) && !empty(extractID($p_parent_2))) {   // both parents must be set
            if ($newHybrid) {
                $sql = "INSERT INTO tbl_tax_hybrids SET
                         taxon_ID_fk = '$id',
                         parent_1_ID = " . extractID($p_parent_1) . ",
                         parent_2_ID = " . extractID($p_parent_2);
            } else {
                $sql = "UPDATE tbl_tax_hybrids SET
                         parent_1_ID = " . extractID($p_parent_1) . ",
                         parent_2_ID = " . extractID($p_parent_2) . "
                        WHERE taxon_ID_fk = '$id'";
            }
            $result = dbi_query($sql);
        }

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
<html lang="en">
<head>
    <title>herbardb - edit Hybrids</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <link rel="stylesheet" type="text/css" href="css/screen.css">
    <link rel="stylesheet" type="text/css" href="js/lib/jQuery/css/ui-lightness/jquery-ui.custom.css">
    <style type="text/css">
        .ui-autocomplete {
            font-size: 0.9em;  /* smaller size */
            max-height: 200px;
            overflow-y: auto;
            /* prevent horizontal scrollbar */
            overflow-x: hidden;
            /* add padding to account for vertical scrollbar */
            padding-right: 20px;
        }
        /* IE 6 doesn't support max-height
         * we use height instead, but this forces the menu to always be this tall
         */
        * html .ui-autocomplete {
            height: 200px;
        }
    </style>
    <?php echo $jaxon->getScript(true, true); ?>
    <script src="js/lib/jQuery/jquery.min.js" type="text/javascript"></script>
    <script src="js/lib/jQuery/jquery-ui.custom.min.js" type="text/javascript"></script>
    <script type="text/javascript">
        $(function()
        {
            const parent1index = $('#parent_1Index');
            const parent2index = $('#parent_2Index');
            
            jaxon_checkParents($('#ID').val(), parent1index.val(), parent2index.val())
            parent1index.change(function() {
                jaxon_checkParents($('#ID').val(), parent1index.val(), parent2index.val())
            });
            parent2index.change(function() {
                jaxon_checkParents($('#ID').val(), parent1index.val(), parent2index.val())
            });
        });
    </script>
</head>

<body>

<form Action="<?php echo $_SERVER['PHP_SELF']; ?>" Method="POST" name="f">

<?php
$cf = new CSSF();

echo "<input type='hidden' id='ID' name='ID' value='$id'>\n";
$cf->label(8, 0.5, "taxonID");
$cf->text(8, 0.5, "&nbsp;$id");

$cf->label(8, 2.5, "1st Parent");
//$cf->editDropdown(8, 2.5, 51, "parent_1_ID", $p_parent_1_ID, makeParent($p_parent_1_ID), 500);
$cf->inputJqAutocomplete(8, 2.5, 51, "parent_1", $p_parent_1, $p_parent_1Index, "index_jq_autocomplete.php?field=taxon", 500, 2);

$cf->label(8, 6.5, "2nd Parent");
//$cf->editDropdown(8, 6.5, 51, "parent_2_ID", $p_parent_2_ID, makeParent($p_parent_2_ID), 500);
$cf->inputJqAutocomplete(8, 6.5, 51, "parent_2", $p_parent_2, $p_parent_2Index, "index_jq_autocomplete.php?field=taxon", 500, 2);

echo "<div style='position:absolute; left: 8em; top: 10.5em' id='alertbox'></div>";

$cf->buttonSubmit(10, 14, "reload", " Reload ");
$cf->buttonJavaScript(16, 14, " Reset ", "self.location.href='editHybrids.php?ID=$id'");

if ($_SESSION['editorControl']) {
    if ($newHybrid) {
        $cf->buttonSubmit(31, 14, "submitUpdate", " Insert ");
    } else {
        $cf->buttonSubmit(31, 14, "submitUpdate", " Update ");
    }
}
?>
</form>

</body>
</html>
