<?php
/**
 * this script checks for missing pictures (both database and harddisk)
 */
require("inc/init.php");
require __DIR__ . '/vendor/autoload.php';

use Jacq\Display;

$display = Display::Load();

try {
    /* @var $db clsDbAccess */
    $db = clsDbAccess::Connect('INPUT');

    $cntSyn = 0;
    $dbst = $db->query("SELECT taxonID, synID
                        FROM tbl_tax_species
                        WHERE synID IS NOT NULL
                         AND synID != 0");
    foreach ($dbst as $row) {
        $cntSyn++;
        // do the conversion here
    }
}
catch (Exception $e) {
    exit($e->getMessage());
}

$mismatch = '';
$cntMismatch = 0;
try {
    /*$dbst = $db->query("SELECT ts.taxonID, ts.synID, tsy.acc_taxon_ID
                        FROM (tbl_tax_species ts, tbl_tax_synonymy tsy)
                         LEFT JOIN tbl_tax_epithets te ON te.epithetID = ts.speciesID
                         LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
                        WHERE ts.taxonID = tsy.taxonID
                         AND ts.synID IS NOT NULL
                         AND tsy.acc_taxon_ID != 0
                         AND ts.synID != tsy.acc_taxon_ID
                        ORDER BY tg.genus, te.epithet");*/
    $dbst = $db->query("SELECT ts.taxonID, ts.synID, tsy.acc_taxon_ID
                        FROM (tbl_tax_species ts, tbl_tax_synonymy tsy)
                         LEFT JOIN tbl_tax_epithets te ON te.epithetID = ts.speciesID
                         LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
                        WHERE ts.taxonID = tsy.taxonID
                         AND ts.synID IS NOT NULL
                         AND tsy.acc_taxon_ID != 0
                         AND ts.synID NOT IN ( SELECT tsy.acc_taxon_ID FROM tbl_tax_synonymy tsy WHERE tsy.taxonID = ts.taxonID )
                        ORDER BY tg.genus, te.epithet");
    foreach ($dbst as $row) {
        $mismatch .= "<a href=\"javascript:editSpecies('<" . $row['taxonID'] . ">')\">" . $display->taxonWithHybrids($row['taxonID']) . "</a><br>\n";
        $cntMismatch++;
    }

}
catch (Exception $e) {
    exit($e->getMessage());
}


$accError = '';
$cntAccError = 0;
try {
    $dbst = $db->query("SELECT ts.taxonID
                        FROM tbl_tax_species ts
                         LEFT JOIN tbl_tax_epithets te ON te.epithetID = ts.speciesID
                         LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
                        WHERE ts.statusID = 96
                         AND ts.synID IS NOT NULL
                        ORDER BY tg.genus, te.epithet");
    foreach ($dbst as $row) {
        $accError .= "<a href=\"javascript:editSpecies('<" . $row['taxonID'] . ">')\">" . $display->taxonWithHybrids($row['taxonID']) . "</a><br>\n";
        $cntAccError++;
    }
}
catch (Exception $e) {
    exit($e->getMessage());
}

/* forget it
$missingSingle = $missingMulti = '';
$cntMissingSingle = $cntMissingMulti = 0;
try {
    $dbst = $db->query("SELECT tsy.taxonID, count(tsy.taxonID) as cnt
                        FROM tbl_tax_synonymy tsy, tbl_tax_species ts
                         LEFT JOIN tbl_tax_epithets te ON te.epithetID = ts.speciesID
                         LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
                        WHERE tsy.taxonID = ts.taxonID
                         AND preferred_taxonomy = 0
                         AND tsy.acc_taxon_ID != 0
                        GROUP BY tsy.taxonID
                        ORDER BY tg.genus, te.epithet");
    foreach ($dbst as $row) {
        $dbst2 = $db->prepare("SELECT taxonID
                               FROM tbl_tax_synonymy
                               WHERE taxonID = :taxonID
                                AND preferred_taxonomy > 0");
        $rows = $dbst2->fetchAll();
        if (count($rows) == 0) {
            if ($row['cnt'] > 1) {
                $missingMulti .= "<a href=\"javascript:editSpecies('<" . $row['taxonID'] . ">')\">" . $display->taxonWithHybrids($row['taxonID']) . "</a><br>\n";
                $cntMissingMulti++;
            } else {
                $missingSingle .= "<a href=\"javascript:editSpecies('<" . $row['taxonID'] . ">')\">" . $display->taxonWithHybrids($row['taxonID']) . "</a><br>\n";
                $cntMissingSingle++;
            }
        }
    }

}
catch (Exception $e) {
    exit($e->getMessage());
}*/

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
    <title>herbardb - list synonyms</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <link rel="stylesheet" type="text/css" href="css/screen.css">
    <script type="text/javascript" language="JavaScript">
        function editSpecies(sel) {
            target = "editSpecies.php?sel=" + encodeURIComponent(sel);
            options = "width=";
            if (screen.availWidth < 990) {
                options += (screen.availWidth - 10) + ",height=";
            } else {
                options += "990, height=";
            }
            if (screen.availHeight < 710) {
                options += (screen.availHeight - 10);
            } else {
                options += "710";
            }
            options += ", top=10,left=10,scrollbars=yes,resizable=yes";

            newWindow = window.open(target,"Specimens",options);
            newWindow.focus();
        }
    </script>
</head>

<body>
<h1>Mismatch in synonymy (<?php echo $cntMismatch; ?>):</h1>
<?php echo $mismatch; ?>

<h1>Status acc but synID set (<?php echo $cntAccError; ?>):</h1>
<?php echo $accError; ?>

<!--<h1>missing "preferred taxonomy" with multiple acc. taxa (<?php echo $cntMissingMulti; ?>)</h1>-->

<!--<h1>missing "preferred taxonomy" with only one acc. taxa (<?php echo $cntMissingSingle; ?>)</h1>-->

</body>
</html>
