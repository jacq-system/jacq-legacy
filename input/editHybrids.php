<?php
session_start();
require("inc/gatekeeper.php");
require( "inc/cssf.php");
require __DIR__ . '/vendor/autoload.php';

use Jacq\DbAccess;
use Jacq\Permission;
use Jacq\Tools;
use Jaxon\Jaxon;

$jaxon = jaxon();
$jaxon->app()->setup(__DIR__ . '/inc/jacqJaxonConfig.php');

$db = DbAccess::ConnectTo('INPUT');

if (isset($_GET['ID'])) {
    $id = intval($_GET['ID']);

    $row = $db->queryCatch("SELECT taxon_ID_fk, parent_1_ID, parent_2_ID
                            FROM tbl_tax_hybrids
                            WHERE taxon_ID_fk = '$id'")
              ->fetch_array();
    if (!empty($row)) {
        $newHybrid = empty($row['taxon_ID_fk']);

        if ($row['parent_1_ID']) {
            $p_parent_1Index = $row['parent_1_ID'];
            $p_parent_1 = Tools::getScientificName($p_parent_1Index);
        } else {
            $p_parent_1Index = 0;
            $p_parent_1 = "";
        }

        if ($row['parent_2_ID']) {
            $p_parent_2Index = $row['parent_2_ID'];
            $p_parent_2 = Tools::getScientificName($p_parent_2Index);
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
    $id = intval($_POST['ID'] ?? 0);
    $p_parent_1 = $_POST['parent_1'] ?? '';
    $p_parent_1Index = (strlen(trim($_POST['parent_1'] ?? "")) > 0) ? intval($_POST['parent_1Index']) : 0;
    $p_parent_2 = $_POST['parent_2'] ?? '';
    $p_parent_2Index = (strlen(trim($_POST['parent_2'] ?? "")) > 0) ? intval($_POST['parent_2Index']) : 0;

    $row = $db->queryCatch("SELECT taxon_ID_fk 
                            FROM tbl_tax_hybrids 
                            WHERE taxon_ID_fk = '$id'")
              ->fetch_array();
    $newHybrid = empty($row['taxon_ID_fk']);

    if (!empty($_POST['submitUpdate']) && Permission::has('editor')) {
        if (Tools::extractID($p_parent_1) != "NULL" && Tools::extractID($p_parent_2) != "NULL") {   // both parents must be set
            if ($newHybrid) {
                $sql = "INSERT INTO tbl_tax_hybrids SET
                         taxon_ID_fk = '$id',
                         parent_1_ID = " . Tools::extractID($p_parent_1) . ",
                         parent_2_ID = " . Tools::extractID($p_parent_2);
            } else {
                $sql = "UPDATE tbl_tax_hybrids SET
                         parent_1_ID = " . Tools::extractID($p_parent_1) . ",
                         parent_2_ID = " . Tools::extractID($p_parent_2) . "
                        WHERE taxon_ID_fk = '$id'";
            }
            $result = $db->queryCatch($sql);
        }

        echo "<html lang='en'><head></head>\n<body>\n"
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
    <link rel="icon" type="image/png" href="webimages/JACQ_LOGO.png">
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

            Jacq.Jaxon.EditHybridsServer.checkParents($('#ID').val(), parent1index.val(), parent2index.val())
            parent1index.change(function() {
                Jacq.Jaxon.EditHybridsServer.checkParents($('#ID').val(), parent1index.val(), parent2index.val())
            });
            parent2index.change(function() {
                Jacq.Jaxon.EditHybridsServer.checkParents($('#ID').val(), parent1index.val(), parent2index.val())
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
$cf->inputJqAutocomplete(8, 2.5, 51, "parent_1", $p_parent_1, $p_parent_1Index, "index_jq_autocomplete.php?field=taxon", 500, 2);

$cf->label(8, 6.5, "2nd Parent");
$cf->inputJqAutocomplete(8, 6.5, 51, "parent_2", $p_parent_2, $p_parent_2Index, "index_jq_autocomplete.php?field=taxon", 500, 2);

echo "<div style='position:absolute; left: 8em; top: 10.5em' id='alertbox'></div>";

$cf->buttonSubmit(10, 14, "reload", " Reload ");
$cf->buttonJavaScript(16, 14, " Reset ", "self.location.href='editHybrids.php?ID=$id'");

if (Permission::has('editor')) {
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
