<?php
session_start();
require("../inc/connect.php");
require("../inc/log_functions.php");
require_once('../inc/jsonRPCClient.php');

$authorMayBeEmpty = (!empty($_POST['authorEmpty'])) ? true : false;

/**
 * parses a line of a textfile and returns an array or false
 *
 * @param resource $handle
 * @param int[optional] $minNumOfParts minimum number of required columns (default: 2)
 * @param string[optional] $delimiter sets the field delimiter (default: ;)
 * @param string[optional] $enclosure sets the field enclosure character (default: ")
 * @return array|bool array of elements or "false" if too short
 */
function parseLine($handle, $minNumOfParts=2, $delimiter=';', $enclosure='"')
{
    $parts = fgetcsv($handle, 4096, $delimiter, $enclosure);
    if (count($parts) >= $minNumOfParts) {
        return $parts;
    } else {
        return false;
    }
}

/**
 * queries the database for a taxon with given ID
 *
 * @param int $id taxon-ID
 * @return string taxon-text
 */
function getTaxon($id)
{
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
             LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
            WHERE taxonID = '" . intval($id) . "'";
    $row = dbi_query($sql)->fetch_array();

    $text = $row['genus'];
    if ($row['epithet'])  $text .= " " . $row['epithet'] . " " . $row['author'];
    if ($row['epithet1']) $text .= " subsp. " . $row['epithet1'] . " " . $row['author1'];
    if ($row['epithet2']) $text .= " var. " . $row['epithet2'] . " " . $row['author2'];
    if ($row['epithet3']) $text .= " subvar. " . $row['epithet3'] . " " . $row['author3'];
    if ($row['epithet4']) $text .= " forma " . $row['epithet4'] . " " . $row['author4'];
    if ($row['epithet5']) $text .= " subforma " . $row['epithet5'] . " " . $row['author5'];

    return $text;
}

function makeServiceDropdown()
{
    $sql = "SELECT *
            FROM tbl_nom_service
            ORDER BY name";
    $result = dbi_query($sql);

    while ($row = mysqli_fetch_array($result)) {
        echo "<option value=\"" . $row['serviceID'] . "\">" . $row['name'] . "</option>\n";
    }
}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - import Taxa</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="../css/screen.css">
  <style type="text/css">
    a.import:link { font-weight:bold; color:#0000FF; text-decoration:none }
    a.import:visited { font-weight:bold; color:#CC00CC; text-decoration:none }
    a.import:hover { font-weight:bold; color:#E00000; text-decoration:none }
    a.import:active { font-weight:bold; color:#E00000; text-decoration:underline }
    a.import:focus { font-weight:bold; color:#00E000; text-decoration:underline }
  </style>
</head>

<body>
<?php
$blocked = false;

$import = array();
$type = 0;
if (isset($_FILES['userfile']) && is_uploaded_file($_FILES['userfile']['tmp_name'])) {
    $type = 1; // file uploaded
    $handle = @fopen($_FILES['userfile']['tmp_name'], "r");
    if ($handle) {
        while (!feof($handle)) {
            $parts = parseLine($handle, 6);
            if ($parts !== false) {
                array_push($import, $parts);
            }
            //$buffer = trim(fgets($handle, 4096));
            //$parts = explode(';', trim($buffer));
            //if (count($parts)>=6) {
            //  foreach ($parts as $k => $v) {
            //    $parts[$k] = substr($v, 1, -1);
            //  }
            //  array_push($import, $parts);
            //}
        }
        fclose($handle);

        foreach ($import as $key=>$row) {
            $first[$key]  = $row[0];
            $second[$key] = $row[1];
            $third[$key]  = $row[2];
            $fourth[$key] = $row[3];
            $fifth[$key]  = $row[4];
            $sixth[$key]  = $row[5];
        }
        array_multisort($first, SORT_ASC, SORT_STRING, $second, SORT_ASC, SORT_STRING,
                        $third, SORT_ASC, SORT_STRING, $fourth, SORT_ASC, SORT_STRING,
                        $fifth, SORT_ASC, SORT_STRING, $sixth,  SORT_ASC, SORT_STRING,
                        $import);

        /*
        for ($i=0;$i<count($import);$i++)
            foreach ($import[$i] as $k => $v)
                $buffer[$k][$i] = $v;
        array_multisort($buffer[0], SORT_ASC, SORT_STRING,
                        $buffer[1], SORT_ASC, SORT_STRING,
                        $buffer[2], SORT_ASC, SORT_STRING,
                        $buffer[3], SORT_ASC, SORT_STRING,
                        $buffer[4], SORT_ASC, SORT_STRING,
                        $buffer[5], SORT_ASC, SORT_STRING);
        for ($i=0;$i<count($buffer);$i++) {
            foreach ($buffer[$i] as $k => $v) {
                $import[$k][$i] = $v;
            }
        }
        */
    }

    $status = array();
    $exists = array();
    $hybrid = array();
    $importable = 0;
    $data = array();
    for ($i = 0; $i < count($import); $i++) {
        $OK = true;
        $taxamatch[$i] = array();

        // check if genus exists
        $result = dbi_query("SELECT genID FROM tbl_tax_genera WHERE genus=".quoteString($import[$i][0]));
        if (mysqli_num_rows($result) > 0) {
            if (mysqli_num_rows($result) == 1) {
                $row = mysqli_fetch_array($result);
                $genID = $row['genID'];
            } else {
                $status[$i] = "multiple_genera";
                $OK = false;
            }
        } else {
            $status[$i] = "no_genera";
            $OK = false;
        }

        if ($OK) {
            // check if species exists
            $result = dbi_query("SELECT epithetID FROM tbl_tax_epithets WHERE epithet=".quoteString($import[$i][1]));
            if (mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_array($result);
                $epithetID = $row['epithetID'];
            } else {
                $status[$i] = "no_species";
                $OK = false;
            }
        }

        if ($OK) {
            // check if author exists
            $result = dbi_query("SELECT authorID FROM tbl_tax_authors WHERE author=".quoteString($import[$i][2]));
            if (mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_array($result);
                $authorID = $row['authorID'];
            } elseif ($authorMayBeEmpty && !trim($import[$i][2])) {
                $authorID = 0;
            } else {
                $author = $import[$i][2];
                if ($author && ($author[0]=='(' || strpos($author, ',') !== false || strpos($author, '&') !== false || strpos($author, 'ex') !== false)) {
                    $subparts = array();
                    if (substr($author, 0, 1) == '(') {
                        $subauthor = trim(substr($author, 1, strpos($author, ')') - 1));
                        $author = trim(substr($author, strpos($author, ')') + 1));
                        $subparts = preg_split('/, |& |ex /',$subauthor);  // split() is depricated as of PHP 5.3.0
                    }
                    $parts = preg_split('/, |& |ex /',$author);  // split() is depricated as of PHP 5.3.0
                    $parts = array_merge($parts, $subparts);
                    $allPartsKnown = true;
                    foreach ($parts as $part) {
                        if (strlen(trim($part)) > 0) {
                            $result2 = dbi_query("SELECT authorID FROM tbl_tax_authors WHERE author = '" . dbi_escape_string(trim($part)) . "'");
                            if (mysqli_num_rows($result2) == 0) {
                                $allPartsKnown = false;
                            }
                        }
                    }
                    if ($allPartsKnown) {
                        $status[$i] = "no_complete_author";
                    } else {
                        $status[$i] = "no_author";
                    }
                } else {
                    $status[$i] = "no_author";
                }
                $OK = false;
            }
        }

        if ($OK && trim($import[$i][3])) {
            // check if sub/var/... species exists
            $result = dbi_query("SELECT epithetID FROM tbl_tax_epithets WHERE epithet = " . quoteString($import[$i][4]));
            if (mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_array($result);
                $subepithetID = $row['epithetID'];
            } else {
                $status[$i] = "no_subspecies";
                $OK = false;
            }
        }

        if ($OK && trim($import[$i][3])) {
            // check if sub/var/... author exists
            $result = dbi_query("SELECT authorID FROM tbl_tax_authors WHERE author = " . quoteString($import[$i][5]));
            if (mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_array($result);
                $subauthorID = $row['authorID'];
            } elseif ($authorMayBeEmpty && !trim($import[$i][5])) {
                $subauthorID = 0;
            } else {
                $author = $import[$i][5];
                if ($author && ($author[0]=='(' || strpos($author, ',') !== false || strpos($author, '&') !== false || strpos($author, 'ex') !== false)) {
                    $subparts = array();
                    if (substr($author,0,1) == '(') {
                        $subauthor = trim(substr($author, 1, strpos($author, ')') - 1));
                        $author = trim(substr($author, strpos($author, ')') + 1));
                        $subparts = preg_split('/, |& |ex /',$subauthor);  // split() is depricated as of PHP 5.3.0
                    }
                    $parts = preg_split('/, |& |ex /',$author);  // split() is depricated as of PHP 5.3.0
                    $parts = array_merge($parts, $subparts);
                    $allPartsKnown = true;
                    foreach ($parts as $part) {
                        if (strlen(trim($part)) > 0) {
                            $result2 = dbi_query("SELECT authorID FROM tbl_tax_authors WHERE author = '" . dbi_escape_string(trim($part)) . "'");
                            if (mysqli_num_rows($result2) == 0) {
                                $allPartsKnown = false;
                            }
                        }
                    }
                    if ($allPartsKnown) {
                        $status[$i] = "no_complete_subauthor";
                    } else {
                        $status[$i] = "no_subauthor";
                    }
                } else {
                    $status[$i] = "no_subauthor";
                }
                $OK = false;
            }
        }

        if ($OK) {
            $sql = "SELECT taxonID
                    FROM tbl_tax_species
                    WHERE genID = '$genID'
                     AND speciesID = '$epithetID' ";
            if ($authorID) {
                $sql .= "AND authorID = '$authorID' ";
            } else {
                $sql .= "AND authorID IS NOT NULL ";
            }
            switch (trim($import[$i][3])) {
                case "subsp.":  $infraName = "subspecies";
                                $sql2 = " AND varietyID IS NULL AND variety_authorID IS NULL
                                          AND subvarietyID IS NULL AND subvariety_authorID IS NULL
                                          AND formaID IS NULL AND forma_authorID IS NULL
                                          AND subformaID IS NULL AND subforma_authorID IS NULL";
                                break;
                case "var.":    $infraName = "variety";
                                $sql2 = " AND subspeciesID IS NULL AND subspecies_authorID IS NULL
                                          AND subvarietyID IS NULL AND subvariety_authorID IS NULL
                                          AND formaID IS NULL AND forma_authorID IS NULL
                                          AND subformaID IS NULL AND subforma_authorID IS NULL";
                                break;
                case "subvar.": $infraName = "subvariety";
                                $sql2 = " AND subspeciesID IS NULL AND subspecies_authorID IS NULL
                                          AND varietyID IS NULL AND variety_authorID IS NULL
                                          AND formaID IS NULL AND forma_authorID IS NULL
                                          AND subformaID IS NULL AND subforma_authorID IS NULL";
                                break;
                case "f.":      $infraName = "forma";
                                $sql2 = " AND subspeciesID IS NULL AND subspecies_authorID IS NULL
                                          AND varietyID IS NULL AND variety_authorID IS NULL
                                          AND subvarietyID IS NULL AND subvariety_authorID IS NULL
                                          AND subformaID IS NULL AND subforma_authorID IS NULL";
                                break;
                case "subf.":   $infraName = "subforma";
                                $sql2 = " AND subspeciesID IS NULL AND subspecies_authorID IS NULL
                                          AND varietyID IS NULL AND variety_authorID IS NULL
                                          AND subvarietyID IS NULL AND subvariety_authorID IS NULL
                                          AND formaID IS NULL AND forma_authorID IS NULL";

                                break;
                default: $infraName = "";
                         $sql2 = " AND subspeciesID IS NULL AND subspecies_authorID IS NULL
                                   AND varietyID IS NULL AND variety_authorID IS NULL
                                   AND subvarietyID IS NULL AND subvariety_authorID IS NULL
                                   AND formaID IS NULL AND forma_authorID IS NULL
                                   AND subformaID IS NULL AND subforma_authorID IS NULL";
            }
            if ($infraName) {
                $sql2 .= " AND {$infraName}ID = '$subepithetID'";
                if ($subauthorID) {
                    $sql2 .= " AND {$infraName}_authorID = '$subauthorID'";
                } else if (!$authorID) {
                    $sql2 .= " AND {$infraName}_authorID IS NOT NULL";
                } else {
                    $sql2 .= " AND {$infraName}_authorID IS NULL";
                }
            }
            $result = dbi_query($sql);
            if (mysqli_num_rows($result) > 0) {
                $parts = array();
                $result2 = dbi_query($sql . $sql2);
                if (mysqli_num_rows($result2) > 0) {
                    $status[$i] = "exists";
                    while ($row = mysqli_fetch_array($result2)) {
                        $parts[] = $row['taxonID'];
                    }
                } else {
                    $status[$i] = "collision";
                    while ($row = mysqli_fetch_array($result)) {
                        $parts[] = $row['taxonID'];
                    }
                }
                $exists[$i] = $parts;
            }
            else {
                $status[$i] = "OK";
                $importable++;
                $data[$i]['genID'] = $genID;
                $data[$i]['speciesID'] = $epithetID;
                $data[$i]['authorID'] = $authorID;
                $data[$i]['rank'] = $import[$i][3];
                $data[$i]['subspeciesID'] = $subepithetID;
                $data[$i]['subauthorID'] = $subauthorID;
            }

            // check if there are hybrids and store them (if any)
            $sql = "SELECT taxonID
                    FROM tbl_tax_species
                    WHERE genID = '$genID'
                     AND speciesID = '$epithetID'";
            $result = dbi_query($sql);
            $parts = array();
            while ($row = mysqli_fetch_array($result)) {
                $parts[] = $row['taxonID'];
            }
            if (count($parts)>0) {
                $hybrid[$i] = $parts;
            }
        }
        if ($status[$i] != "exists" && $status[$i] != "collision") {
            $searchtext = trim($import[$i][0] . ' '
                        .      $import[$i][1] . ' '
                        .      (trim($import[$i][3]) == 'f.' ? 'forma' : $import[$i][3])
                        .      $import[$i][4]);
            $service = new jsonRPCClient($_OPTIONS['serviceTaxamatch']);
            try {
                $matches = $service->getMatchesService('vienna',$searchtext,array('showSyn'=>false,'NearMatch'=>false));
                foreach ($matches['result'][0]['searchresult'] as $key => $val) {
                    for ($j = 0; $j < count($val['species']); $j++) {
                        $taxamatch[$i][] = $val['species'][$j];
                    }
                }
            }
            catch (Exception $e) {
                echo "JSON-Fehler " . nl2br($e);
            }

        }
    }
} elseif (isset($_POST['import_data']) && $_POST['import_data']) {
    $type = 2;  // data inserted
    $data = array();
    foreach ($_POST as $k=>$v) {
        $pieces = explode('_', $k);
        if ($pieces[0] == 'genID' || $pieces[0] == 'speciesID' || $pieces[0] == 'authorID' ||
            $pieces[0] == 'rank' || $pieces[0] == 'subspeciesID' || $pieces[0] == 'subauthorID' ||
            $pieces[0] == 'genText' || $pieces[0] == 'speciesText' || $pieces[0] == 'authorText' ||
            $pieces[0] == 'subspeciesText' || $pieces[0] == 'subauthorText' ||
            $pieces[0] == 'serviceTaxID' || $pieces[0] == 'serviceVersion') {

            $data[intval($pieces[1])][$pieces[0]] = $v;
        }
    }

    // the real database-insert happens down below to show what the DB returns
}
?>

<?php if ($blocked): ?>
<script type="text/javascript" language="JavaScript">
  alert('You have no sufficient rights for the desired operation');
</script>
<?php endif; ?>

<h1>Import Taxa</h1>

<form enctype="multipart/form-data" Action="<?php echo $_SERVER['PHP_SELF']; ?>" Method="POST" name="f">

<input type="hidden" name="MAX_FILE_SIZE" value="8000000" />
Import this file: <input name="userfile" type="file" />
&nbsp;&nbsp;
<input type="checkbox"<?php echo (!empty($_POST['authorEmpty'])) ? " checked" : ""; ?> name="authorEmpty">authors may be empty
&nbsp;&nbsp;
<input type="submit" value="check Import" />
<p>
<select name="service">
<option value="0">---</option>
<?php makeServiceDropdown(); ?>
</select>
<p>

<?php
if ($type==1 && !$blocked) {  // file uploaded
    echo "$importable / ".count($import)." entries are ready to be imported&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".
         "<input type=\"submit\" name=\"import_data\" value=\"import them now\"><br>\n";
    echo "<table border='1'>\n";
    $ctr = 0;
    for ($block = 0; $block < 5; $block++) {
        for ($i = 0; $i < count($import); $i++) {
            if (($block == 0 && $status[$i] == "OK") ||
                ($block == 1 && $status[$i] == "no_complete_author") ||
                ($block == 2 && $status[$i] == "no_complete_subauthor") ||
                ($block == 3 && $status[$i] != "OK" && $status[$i] != "no_complete_author" && $status[$i] != "no_complete_subauthor" && $status[$i]!="exists" && $status[$i] != "collision") ||
                ($block == 4 && ($status[$i] == "exists" || $status[$i] == "collision"))) {

                if ($status[$i] == "OK") {
                    echo "<tr style=\"background-color:#00FF00\">";
                } elseif ($status[$i] == "exists") {
                    echo "<tr style=\"background-color:#0000FF\">";
                } else {
                    echo "<tr>";
                }
                echo "<td".(($status[$i] == "no_genera" || $status[$i] == "multiple_genera") ? " style=\"background-color:red\"" : "") . ">" . htmlspecialchars($import[$i][0]) . "</td>";
                echo "<td".(($status[$i] == "no_species") ? " style=\"background-color:red\"" : "").">" . htmlspecialchars($import[$i][1]) . "</td>";
                if ($status[$i] == "no_complete_author") {
                    echo "<td style=\"background-color:yellow\">" . htmlspecialchars($import[$i][2]) . "</td>";
                } else if ($status[$i] == "no_author") {
                    echo "<td style=\"background-color:red\">" . htmlspecialchars($import[$i][2]) . "</td>";
                } else {
                    echo "<td>" . htmlspecialchars($import[$i][2]) . "</td>";
                }
                echo "<td>" . $import[$i][3] . "</td>";
                echo "<td" . (($status[$i] == "no_subspecies") ? " style=\"background-color:red\"" : "") . ">" . htmlspecialchars($import[$i][4]) . "</td>";
                if ($status[$i] == "no_complete_subauthor") {
                    echo "<td style=\"background-color:yellow\">" . htmlspecialchars($import[$i][5]) . "</td>";
                } else if ($status[$i] == "no_subauthor") {
                    echo "<td style=\"background-color:red\">" . htmlspecialchars($import[$i][5]) . "</td>";
                } else {
                  echo "<td>" . htmlspecialchars($import[$i][5]) . "</td>";
                }
                echo "<td>" . $import[$i][6] . "</td>";
                echo "<td>" . $import[$i][7] . "</td>";
                if ($status[$i] == "exists" || $status[$i] == "collision") {
                    echo "<td" . (($status[$i] == "collision" || count($exists[$i]) > 1) ? " style=\"background-color:red\"" : "") . ">";
                    $first = true;
                    foreach ($exists[$i] as $item) {
                        if ($first) {
                            $first = false;
                        } else {
                            echo ", ";
                        }
                        echo "<a href=\"../editSpecies.php?sel=" . htmlspecialchars("<$item>") . "\" target=\"Species\">" . htmlspecialchars($item) . "</a>";
                    }
                    echo "</td>";
                } else {
                    echo "<td></td>";
                }
                echo "</tr>\n";
                if ($status[$i] == "exists" && count($exists[$i]) == 1 && intval($_POST['service'])) {
                    $sqlService = "INSERT INTO tbl_nom_service_names SET
                                    taxonID='" . $exists[$i][0] . "',
                                    serviceID='" . intval($_POST['service']) . "',
                                    param1=" . quoteString($import[$i][6]) . ",
                                    param2=" . quoteString($import[$i][7]);
                    dbi_query($sqlService);
                }
                for ($j = 0; $j < count($hybrid[$i]); $j++) {
                    echo "<tr><td colspan=\"9\">"
                       . "<a href=\"../editSpecies.php?sel=" . htmlspecialchars("<" . $hybrid[$i][$j] . ">") . "\" target=\"Species\">"
                       . getTaxon($hybrid[$i][$j]) . "</a></td>";
                    echo "</tr>\n";
                }
                if (count($taxamatch[$i]) > 0) {
                    echo "<tr><td style=\"background-color:yellow\" colspan=\"9\">";
                    foreach ($taxamatch[$i] as $val) {
                        echo $val['taxon'] . "<br>";
                    }
                    echo "</td></tr>\n";
                }
            }
            if ($block == 0 && $status[$i] == "OK") {
                echo "<input type=\"hidden\" name=\"genID_$ctr\" value=\"" . $data[$i]['genID'] . "\">"
                   . "<input type=\"hidden\" name=\"speciesID_$ctr\" value=\"" . $data[$i]['speciesID'] . "\">"
                   . "<input type=\"hidden\" name=\"authorID_$ctr\" value=\"" . $data[$i]['authorID'] . "\">"
                   . "<input type=\"hidden\" name=\"rank_$ctr\" value=\"" . $data[$i]['rank'] . "\">"
                   . "<input type=\"hidden\" name=\"subspeciesID_$ctr\" value=\"" . $data[$i]['subspeciesID'] . "\">"
                   . "<input type=\"hidden\" name=\"subauthorID_$ctr\" value=\"" . $data[$i]['subauthorID'] . "\">"
                   . "<input type=\"hidden\" name=\"genText_$ctr\" value=\"" . htmlspecialchars($import[$i][0]) . "\">"
                   . "<input type=\"hidden\" name=\"speciesText_$ctr\" value=\"" . htmlspecialchars($import[$i][1]) . "\">"
                   . "<input type=\"hidden\" name=\"authorText_$ctr\" value=\"" . htmlspecialchars($import[$i][2]) . "\">"
                   . "<input type=\"hidden\" name=\"subspeciesText_$ctr\" value=\"" . htmlspecialchars($import[$i][4]) . "\">"
                   . "<input type=\"hidden\" name=\"subauthorText_$ctr\" value=\"" . htmlspecialchars($import[$i][5]) . "\">"
                   . "<input type=\"hidden\" name=\"serviceTaxID_$ctr\" value=\"" . htmlspecialchars($import[$i][6]) . "\">"
                   . "<input type=\"hidden\" name=\"serviceVersion_$ctr\" value=\"" . htmlspecialchars($import[$i][7]) . "\">\n";
                $ctr++;
            }
        }
    }
    echo "</table>\n";
} elseif ($type == 2 && !$blocked) {  // data inserted
    echo count($data) . " entries are to be imported<br>\n";
    echo "<table border='1'>\n";

    for ($i = 0; $i < count($data); $i++) {
        $sql1 = "SELECT taxonID
                 FROM tbl_tax_species
                 WHERE genID = '" . intval($data[$i]['genID']) . "'
                  AND speciesID = '" . intval($data[$i]['speciesID']) . "'
                  AND authorID = '" . intval($data[$i]['authorID']) . "' ";
        $sql2 = "INSERT INTO tbl_tax_species SET
                  genID = '" . intval($data[$i]['genID']) . "',
                  speciesID = '" . intval($data[$i]['speciesID']) . "',
                  authorID = '" . intval($data[$i]['authorID']) . "'";
        switch ($data[$i]['rank']) {
            case "subsp.":  $sql1 .= "AND subspeciesID = '" . intval($data[$i]['subspeciesID']) . "'
                                      AND varietyID IS NULL AND variety_authorID IS NULL
                                      AND subvarietyID IS NULL AND subvariety_authorID IS NULL
                                      AND formaID IS NULL AND forma_authorID IS NULL
                                      AND subformaID IS NULL AND subforma_authorID IS NULL";
                            $sql2 .= ", subspeciesID = '" . intval($data[$i]['subspeciesID']) . "'";
                            if (intval($data[$i]['subauthorID'])) {
                                $sql1 .= " AND subspecies_authorID = '" . intval($data[$i]['subauthorID']) . "'";
                                $sql2 .= ", subspecies_authorID = '" . intval($data[$i]['subauthorID']) . "'";
                            } else {
                                $sql1 .= " AND subspecies_authorID IS NULL";
                            }
                            break;
            case "var.":    $sql1 .= "AND varietyID = '" . intval($data[$i]['subspeciesID']) . "'
                                      AND subspeciesID IS NULL AND subspecies_authorID IS NULL
                                      AND subvarietyID IS NULL AND subvariety_authorID IS NULL
                                      AND formaID IS NULL AND forma_authorID IS NULL
                                      AND subformaID IS NULL AND subforma_authorID IS NULL";
                            $sql2 .= ", varietyID = '" . intval($data[$i]['subspeciesID']) . "'";
                            if (intval($data[$i]['subauthorID'])) {
                                $sql1 .= " AND variety_authorID = '" . intval($data[$i]['subauthorID']) . "'";
                                $sql2 .= ", variety_authorID = '" . intval($data[$i]['subauthorID']) . "'";
                            } else {
                                $sql1 .= " AND variety_authorID IS NULL";
                            }
                            break;
            case "subvar.": $sql1 .= "AND subvarietyID = '" . intval($data[$i]['subspeciesID']) . "'
                                      AND subspeciesID IS NULL AND subspecies_authorID IS NULL
                                      AND varietyID IS NULL AND variety_authorID IS NULL
                                      AND formaID IS NULL AND forma_authorID IS NULL
                                      AND subformaID IS NULL AND subforma_authorID IS NULL";
                            $sql2 .= ", subvarietyID = '" . intval($data[$i]['subspeciesID']) . "'";
                            if (intval($data[$i]['subauthorID'])) {
                                $sql1 .= " AND subvariety_authorID='" . intval($data[$i]['subauthorID']) . "'";
                                $sql2 .= ", subvariety_authorID='" . intval($data[$i]['subauthorID']) . "'";
                            } else {
                                $sql1 .= " AND subvariety_authorID IS NULL";
                            }
                            break;
            case "f.":      $sql1 .= "AND formaID = '" . intval($data[$i]['subspeciesID']) . "'
                                      AND subspeciesID IS NULL AND subspecies_authorID IS NULL
                                      AND varietyID IS NULL AND variety_authorID IS NULL
                                      AND subvarietyID IS NULL AND subvariety_authorID IS NULL
                                      AND subformaID IS NULL AND subforma_authorID IS NULL";
                            $sql2 .= ", formaID = '" . intval($data[$i]['subspeciesID']) . "'";
                            if (intval($data[$i]['subauthorID'])) {
                                $sql1 .= " AND forma_authorID = '" . intval($data[$i]['subauthorID']) . "'";
                                $sql2 .= ", forma_authorID = '" . intval($data[$i]['subauthorID']) . "'";
                            } else {
                                $sql1 .= " AND forma_authorID IS NULL";
                            }
                            break;
            case "subf.":   $sql1 .= "AND subformaID = '" . intval($data[$i]['subspeciesID']) . "'
                                      AND subspeciesID IS NULL AND subspecies_authorID IS NULL
                                      AND varietyID IS NULL AND variety_authorID IS NULL
                                      AND subvarietyID IS NULL AND subvariety_authorID IS NULL
                                      AND formaID IS NULL AND forma_authorID IS NULL";
                            $sql2 .= ", subformaID = '" . intval($data[$i]['subspeciesID']) . "'";
                            if (intval($data[$i]['subauthorID'])) {
                                $sql1 .= " AND subforma_authorID = '" . intval($data[$i]['subauthorID']) . "'";
                                $sql2 .= ", subforma_authorID = '" . intval($data[$i]['subauthorID']) . "'";
                            } else {
                                $sql1 .= " AND subforma_authorID IS NULL";
                            }
                            break;
            default: $sql1 .= "AND subspeciesID IS NULL AND subspecies_authorID IS NULL
                               AND varietyID IS NULL AND variety_authorID IS NULL
                               AND subvarietyID IS NULL AND subvariety_authorID IS NULL
                               AND formaID IS NULL AND forma_authorID IS NULL
                               AND subformaID IS NULL AND subforma_authorID IS NULL";
        }
        $taxonID = 0;
        $result = dbi_query($sql1);
        if (mysqli_num_rows($result) == 0) {
            $result = dbi_query($sql2);
            if ($result) {
                $taxonID = dbi_insert_id();
                logSpecies($taxonID, 0);
                if (intval($_POST['service'])) {
                    $sqlService = "INSERT INTO tbl_nom_service_names SET
                                    taxonID = '$taxonID',
                                    serviceID = '" . intval($_POST['service']) . "',
                                    param1 = " . quoteString($data[$i]['serviceTaxID']) . ",
                                    param2 = " . quoteString($data[$i]['serviceVersion']);
                    dbi_query($sqlService);
                }
            }
        }

        echo "<tr style=\"background-color:#00FF00\">";
        echo "<td><a href=\"../editSpecies.php?sel=" . htmlspecialchars("<$taxonID>") . "\" target=\"Species\">" . htmlspecialchars($taxonID) . "</a></td>";
        echo "<td>" . htmlspecialchars($data[$i]['genText']) . "</td>";
        echo "<td>" . htmlspecialchars($data[$i]['speciesText']) . "</td>";
        echo "<td>" . htmlspecialchars($data[$i]['authorText']) . "</td>";
        echo "<td>" . htmlspecialchars($data[$i]['rank']) . "</td>";
        echo "<td>" . htmlspecialchars($data[$i]['subspeciesText']) . "</td>";
        echo "<td>" . htmlspecialchars($data[$i]['subauthorText']) . "</td>";
        echo "<td>" . htmlspecialchars($data[$i]['serviceTaxID']) . "</td>";
        echo "<td>" . htmlspecialchars($data[$i]['serviceVersion']) . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
}
?>

</form>

</body>
</html>