<?php
session_start();
require("../inc/connect.php");
require("../inc/log_functions.php");


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

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - check import Taxa</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="../css/screen.css">
</head>

<body>
<?php
$blocked = false;

$import = array();
if (is_uploaded_file($_FILES['userfile']['tmp_name'])) {
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
        for ($i=0;$i<count($buffer);$i++)
          foreach ($buffer[$i] as $k => $v)
            $import[$k][$i] = $v;
        */
    }

    $status = array();
    $exists = array();
    $hybrid = array();
    $importable = 0;
    $data = array();
    for ($i=0;$i<count($import);$i++) {
        $OK = true;

        // check if genus exists
        $result = dbi_query("SELECT genID FROM tbl_tax_genera WHERE genus = " . quoteString($import[$i][0]));
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_array($result);
            $genID = $row['genID'];
        } else {
            $status[$i] = "no_genera";
            $OK = false;
        }

        if ($OK) {
            // check if species exists
            $result = dbi_query("SELECT epithetID FROM tbl_tax_epithets WHERE epithet = " . quoteString($import[$i][1]));
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
            $result = dbi_query("SELECT authorID FROM tbl_tax_authors WHERE author = " . quoteString($import[$i][2]));
            if (mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_array($result);
                $authorID = $row['authorID'];
            } else {
                $status[$i] = "no_author";
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
            } else {
                $status[$i] = "no_subauthor";
                $OK = false;
            }
        }

        if ($OK) {
            $sql = "SELECT taxonID
                    FROM tbl_tax_species
                    WHERE genID = $genID
                     AND speciesID = $epithetID
                     AND authorID = $authorID ";
            switch (trim($import[$i][3])) {
                case "subsp.":  $sql .= "AND subspeciesID = $subepithetID AND subspecies_authorID = $subauthorID";
                                $sql2 = " AND varietyID IS NULL AND variety_authorID IS NULL
                                          AND subvarietyID IS NULL AND subvariety_authorID IS NULL
                                          AND formaID IS NULL AND forma_authorID IS NULL
                                          AND subformaID IS NULL AND subforma_authorID IS NULL";
                                break;
                case "var.":    $sql .= "AND varietyID = $subepithetID AND variety_authorID = $subauthorID";
                                $sql2 = " AND subspeciesID IS NULL AND subspecies_authorID IS NULL
                                          AND subvarietyID IS NULL AND subvariety_authorID IS NULL
                                          AND formaID IS NULL AND forma_authorID IS NULL
                                          AND subformaID IS NULL AND subforma_authorID IS NULL";
                                break;
                case "subvar.": $sql .= "AND subvarietyID = $subepithetID AND subvariety_authorID = $subauthorID";
                                $sql2 = " AND subspeciesID IS NULL AND subspecies_authorID IS NULL
                                          AND varietyID IS NULL AND variety_authorID IS NULL
                                          AND formaID IS NULL AND forma_authorID IS NULL
                                          AND subformaID IS NULL AND subforma_authorID IS NULL";
                                break;
                case "f.":      $sql .= "AND formaID = $subepithetID AND forma_authorID = $subauthorID";
                                $sql2 = " AND subspeciesID IS NULL AND subspecies_authorID IS NULL
                                          AND varietyID IS NULL AND variety_authorID IS NULL
                                          AND subvarietyID IS NULL AND subvariety_authorID IS NULL
                                          AND subformaID IS NULL AND subforma_authorID IS NULL";
                                break;
                case "subf.":   $sql .= "AND subformaID = $subepithetID AND subforma_authorID = $subauthorID";
                                $sql2 = " AND subspeciesID IS NULL AND subspecies_authorID IS NULL
                                          AND varietyID IS NULL AND variety_authorID IS NULL
                                          AND subvarietyID IS NULL AND subvariety_authorID IS NULL
                                          AND formaID IS NULL AND forma_authorID IS NULL";

                                break;
                default: $sql2 = " AND subspeciesID IS NULL AND subspecies_authorID IS NULL
                                   AND varietyID IS NULL AND variety_authorID IS NULL
                                   AND subvarietyID IS NULL AND subvariety_authorID IS NULL
                                   AND formaID IS NULL AND forma_authorID IS NULL
                                   AND subformaID IS NULL AND subforma_authorID IS NULL";
            }
            $result = dbi_query($sql);
            if (mysqli_num_rows($result) > 0) {
                $result2 = dbi_query($sql.$sql2);
                if (mysqli_num_rows($result2) > 0) {
                    $status[$i] = "exists";
                } else {
                    $status[$i] = "collision";
                }
                $parts = array();
                while ($row = mysqli_fetch_array($result)) {
                    $parts[] = $row['taxonID'];
                }
                $exists[$i] = $parts;
            } else {
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
                    WHERE genID = $genID
                     AND speciesID = $epithetID";
            $result = dbi_query($sql);
            $parts = array();
            while ($row = mysqli_fetch_array($result)) {
              $parts[] = $row['taxonID'];
            }
            if (count($parts) > 0) {
              $hybrid[$i] = $parts;
            }
        }
    }
}
?>

<?php if ($blocked): ?>
<script type="text/javascript" language="JavaScript">
  alert('You have no sufficient rights for the desired operation');
</script>
<?php endif; ?>

<h1>check Import Taxa</h1>

<form enctype="multipart/form-data" Action="<?php echo $_SERVER['PHP_SELF']; ?>" Method="POST" name="f">

<input type="hidden" name="MAX_FILE_SIZE" value="8000000" />
Import this file: <input name="userfile" type="file" />
<input type="submit" value="check Import" />

<p>

<?php
if ($type == 1 && !$blocked) {  // file uploaded
    echo "$importable / " . count($import) . " entries are ready to be imported<br>\n";
    echo "<table>\n";
    for ($block = 0; $block < 3; $block++) {
        for ($i = 0; $i < count($import); $i++) {
            if (($block == 0 && $status[$i] == "OK") ||
                ($block == 1 && $status[$i] != "OK" && $status[$i] != "exists" && $status[$i] != "collision") ||
                ($block == 2 && ($status[$i] == "exists" || $status[$i] == "collision"))) {

                if ($status[$i] == "OK") {
                    echo "<tr style=\"background-color:#00FF00\">";
                } elseif ($status[$i] == "exists") {
                    echo "<tr style=\"background-color:#0000FF\">";
                } else {
                    echo "<tr>";
                }
                echo "<td" . (($status[$i] == "no_genera") ? " style=\"background-color:red\"" : "") . ">" . htmlspecialchars($import[$i][0]) . "</td>";
                echo "<td" . (($status[$i] == "no_species") ? " style=\"background-color:red\"" : "") . ">" . htmlspecialchars($import[$i][1]) . "</td>";
                echo "<td" . (($status[$i] == "no_author") ? " style=\"background-color:red\"" : "") . ">" . htmlspecialchars($import[$i][2]) . "</td>";
                echo "<td>" . $import[$i][3] . "</td>";
                echo "<td" . (($status[$i] == "no_subspecies") ? " style=\"background-color:red\"" : "") . ">" . htmlspecialchars($import[$i][4]) . "</td>";
                echo "<td" . (($status[$i] == "no_subauthor") ? " style=\"background-color:red\"" : "") . ">" . htmlspecialchars($import[$i][5]) . "</td>";
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
                for ($j = 0; $j < count($hybrid[$i]); $j++) {
                    if ($status[$i] == "OK") {
                        echo "<tr style=\"background-color:#00FF00\">";
                    } elseif ($status[$i] == "exists") {
                        echo "<tr style=\"background-color:#0000FF\">";
                    } else {
                        echo "<tr>";
                    }
                    echo "<td colspan=\"9\">"
                       . "<a href=\"../editSpecies.php?sel=" . htmlspecialchars("<" . $hybrid[$i][$j] . ">") . "\" target=\"Species\">"
                       . getTaxon($hybrid[$i][$j]) . "</a></td>";
                    echo "</tr>\n";
                }
            }
        }
    }
    echo "</table>\n";
}
?>

</form>

</body>
</html>