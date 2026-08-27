<?php
session_start();
require("../inc/gatekeeper.php");
require __DIR__ . '/../vendor/autoload.php';

use Jacq\DbAccess;

$db = DbAccess::ConnectTo('INPUT');

$ctr = 1;
$rows[$ctr] = $db->queryCatch("SELECT herbar_view.GetScientificName(ts.taxonID, 0) as scientificName, ts.taxonID
                               FROM tbl_tax_species ts
                                LEFT JOIN tbl_tax_hybrids th ON th.taxon_ID_fk = ts.taxonID 
                               WHERE ts.statusID = 1
                                AND ts.speciesID IS NULL
                                AND th.taxon_ID_fk IS NULL
                               ORDER BY scientificName")
                 ->fetch_all(MYSQLI_ASSOC);
$texts[$ctr] = "Hybrids with empty epithet and no parents (empty tbl_tax_hybrids) (" . count($rows[$ctr]) . ")";

$ctr++;
$rows[$ctr] = $db->queryCatch("SELECT herbar_view.GetScientificName(ts.taxonID, 0) as scientificName, ts.taxonID
                               FROM tbl_tax_species ts
                               WHERE ts.statusID = 1
                                AND (   ts.speciesID IS NOT NULL 
                                     OR ts.subspeciesID IS NOT NULL 
                                     OR ts.varietyID IS NOT NULL 
                                     OR ts.subvarietyID IS NOT NULL 
                                     OR ts.formaID IS NOT NULL 
                                     OR ts.subformaID IS NOT NULL)
                                AND ts.synID IS NULL
                               ORDER BY scientificName")
                 ->fetch_all(MYSQLI_ASSOC);
$texts[$ctr] = "Hybrids with any populated epithet and empty accepted Taxon (" . count($rows[$ctr]) . ")";

$ctr++;
$rows[$ctr] = $db->queryCatch("SELECT herbar_view.GetScientificName(ts.taxonID, 0) as scientificName, ts.taxonID
                               FROM tbl_tax_species ts
                                LEFT JOIN tbl_tax_hybrids th ON th.taxon_ID_fk = ts.taxonID 
                               WHERE ts.statusID = 1
                                AND (   ts.speciesID IS NOT NULL 
                                     OR ts.subspeciesID IS NOT NULL 
                                     OR ts.varietyID IS NOT NULL 
                                     OR ts.subvarietyID IS NOT NULL 
                                     OR ts.formaID IS NOT NULL 
                                     OR ts.subformaID IS NOT NULL)
                                AND th.taxon_ID_fk IS NOT NULL
                               ORDER BY scientificName")
                 ->fetch_all(MYSQLI_ASSOC);
$texts[$ctr] = "Hybrids with parents but any populated epithet (" . count($rows[$ctr]) . ")";

$ctr++;
$rows[$ctr] = $db->queryCatch("SELECT herbar_view.GetScientificName(ts.taxonID, 0) as scientificName, ts.taxonID, th.taxon_ID_fk
                               FROM tbl_tax_hybrids th
                               LEFT JOIN tbl_tax_species ts ON ts.taxonID = th.taxon_ID_fk
                               WHERE ts.statusID != 1
                               ORDER BY scientificName")
                 ->fetch_all(MYSQLI_ASSOC);
$texts[$ctr] = "Hybrids with parents but wrong statusID (not 1) (" . count($rows[$ctr]) . ")";



?><!DOCTYPE HTML>
<html lang="en">
<head>
    <title>herbardb - check Hybrids</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <link rel="stylesheet" type="text/css" href="../css/screen.css">
    <script type="text/javascript" language="JavaScript">
        function openWindow(target, name) {
            let options = "width=";
            if (screen.availWidth<1380)
                options += (screen.availWidth - 10) + ",height=";
            else
                options += "1380, height=";
            if (screen.availHeight<810)
                options += (screen.availHeight - 10);
            else
                options += "860";
            options += ", top=10,left=10,scrollbars=yes,resizable=yes";

            let newWindow = window.open(target,name,options);
            newWindow.focus();
        }

        function editHybrids(sel) {
            let target  = "../editHybrids.php?ID=" + encodeURIComponent(sel);
            let MeinFenster = window.open(target, "editHybrids", "width=900,height=260,top=50,left=50,scrollbars=yes,resizable=yes");
            MeinFenster.focus();
        }

    </script>
</head>

<body>
<h1>Check Hybrids</h1>
<?php
foreach ($texts as $key => $text) {
    echo "<a href='#section$key'>$text</a><br>\n";
}

foreach ($texts as $key => $text) {
    echo "<h2 id='section$key'>$text</h2>\n";
    foreach ($rows[$key] as $row) {
        echo "<a href=\"javascript: openWindow('../editSpecies.php?sel=" . htmlspecialchars("<" . $row['taxonID'] . ">") . "', 'Species');\">"
            . "{$row['scientificName']} ({$row['taxonID']})</a>"
            . ((!empty($row['taxon_ID_fk'])) ? "&nbsp;&nbsp;&nbsp;-><a href=\"javascript: editHybrids('{$row['taxon_ID_fk']}');\">edit Hybrids</a>" : "")
            . "<br>\n";
    }
}
?>

</body>
</html>
