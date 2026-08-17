<?php
session_start();
require("../inc/gatekeeper.php");
require __DIR__ . '/../vendor/autoload.php';

use Jacq\DbAccess;

$db = DbAccess::ConnectTo('INPUT');

$ctr = 1;
$rows[$ctr] = $db->queryCatch("SELECT scientific_name, COUNT(scientific_name_id) AS cnt, GROUP_CONCAT(scientific_name_id ORDER BY scientific_name_id) AS `keys`
                               FROM herbar_view.view_scientificName_mtrlzd
                               GROUP BY scientific_name 
                               HAVING cnt > 1
                               ORDER BY cnt DESC, scientific_name")
                 ->fetch_all(MYSQLI_ASSOC);
$texts[$ctr] = "Taxa with identical scientific names (" . count($rows[$ctr]) . ")";
$type[$ctr]  = "scientificName";

$ctr++;
$rows[$ctr] = $db->queryCatch("SELECT genus, COUNT(genID) AS cnt, GROUP_CONCAT(genID ORDER BY genID) AS `keys`
                               FROM tbl_tax_genera
                               GROUP BY genus, familyID, authorID 
                               HAVING cnt > 1
                               ORDER BY cnt DESC, genus")
                 ->fetch_all(MYSQLI_ASSOC);
$texts[$ctr] = "identical entries in tbl_tax_genera (" . count($rows[$ctr]) . ")";
$type[$ctr]  = "genus";

$ctr++;
$rows[$ctr] = $db->queryCatch("SELECT family, COUNT(familyID) AS cnt, GROUP_CONCAT(familyID ORDER BY familyID) AS `keys`
                               FROM tbl_tax_families
                               GROUP BY family, categoryID 
                               HAVING cnt > 1
                               ORDER BY cnt DESC, family")
                 ->fetch_all(MYSQLI_ASSOC);
$texts[$ctr] = "identical entries in tbl_tax_families (" . count($rows[$ctr]) . ")";
$type[$ctr]  = "family";


?><!DOCTYPE HTML>
<html lang="en">
<head>
    <title>herbardb - check Taxa</title>
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

            let newWindow = window.open(target, name, options);
            newWindow.focus();
        }
    </script>
</head>

<body>
<h1>Check Taxa</h1>
<?php
foreach ($texts as $key => $text) {
    echo "<a href='#section$key'>$text</a><br>\n";
}

foreach ($texts as $key => $text) {
    echo "<h2 id='section$key'>$text</h2>\n";
    foreach ($rows[$key] as $row) {
        switch ($type[$key]) {
            case "scientificName":
                echo "<a href=\"javascript: openWindow('../listTax.php?taxon_list={$row['keys']}', 'Species');\">"
                   . "{$row['scientific_name']} ({$row['cnt']})</a>"
                   . "<br>\n";
                break;
            case "genus":
                echo "<a href=\"javascript: openWindow('../listTax.php?genus=" . htmlentities($row['genus']) . "', 'Species');\">"
                        . "{$row['genus']} ({$row['cnt']}) (genIDs {$row['keys']})</a>"
                        . "<br>\n";
                break;
            case "family":
                echo "<a href=\"javascript: openWindow('../listTax.php?family=" . htmlentities($row['family']) . "', 'Species');\">"
                        . "{$row['family']} ({$row['cnt']}) (familyIDs {$row['keys']})</a>"
                        . "<br>\n";
                break;
        }
    }
}
?>

</body>
</html>
