<?php
session_start();
require("../inc/connect.php");
require("../inc/log_functions.php");
no_magic();


/**
 * parses a line of a textfile and returns an array or false
 *
 * @param resource $handle
 * @param int[optional] $minNumOfParts minimum number of required columns (default: 2)
 * @param string[optional] $delimiter sets the field delimiter (default: ;)
 * @param string[optional] $enclosure sets the field enclosure character (default: ")
 * @return array|bool array of elements or "false" if too short
 */
function parseLine($handle, $minNumOfParts = 2, $delimiter = ';', $enclosure = '"')
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
    if ($row['epithet4']) $text .= " f. " . $row['epithet4'] . " " . $row['author4'];
    if ($row['epithet5']) $text .= " subf. " . $row['epithet5'] . " " . $row['author5'];

    return $text;
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - check import Specimens</title>
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
        }
        fclose($handle);

        foreach ($import as $key => $row) {
            $first[$key]  = $row[0];
            $second[$key] = $row[2];
            $third[$key]  = $row[3];
        }
        array_multisort($first, SORT_ASC, SORT_STRING, $second, SORT_ASC, SORT_STRING, $third, SORT_ASC, SORT_STRING, $import);
    }

    $status = array();
    $exists = array();
    $importable = 0;
    $data = array();
    for ($i = 0; $i < count($import); $i++) {
        $OK = true;
        $status[$i] = "";

        /**
         * check if collection-ID exists
         */
        $result = dbi_query("SELECT collection FROM tbl_management_collections WHERE collectionID = '" . intval($import[$i][1]) . "'");
        if (mysqli_num_rows($result) == 0) {
            $OK = false;
            $status[$i] .= "no_collection ";
            $data[$i]['collectionID'] = 0;
        } else {
            $data[$i]['collectionID'] = intval($import[$i][1]);
        }

        /**
         * get HerbNummer and check if this number already exists for the same institution (source_id), if there is a HerbNummer
         */
        $pieces = explode('_', $import[$i][0]);
        if (count($pieces) > 1) {
            $data[$i]['HerbNummer'] = trim($pieces[1]);
        } else {
            $data[$i]['HerbNummer'] = "";
        }
        if ($data[$i]['HerbNummer']) {
            $sql = "SELECT source_id
                    FROM tbl_management_collections
                    WHERE collectionID = '" . $data[$i]['collectionID'] . "'";
            $row = mysqli_fetch_array(dbi_query($sql));
            $sql = "SELECT specimen_ID
                    FROM tbl_specimens, tbl_management_collections
                    WHERE tbl_specimens.collectionID = tbl_management_collections.collectionID
                     AND source_id = '" . $row['source_id'] . "'
                     AND HerbNummer = " . quoteString($data[$i]['HerbNummer']);
            $result = dbi_query($sql);
            if (mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_array($result);
                $OK = false;
                $status[$i] .= "exists ";
                $exists[$i] = $row['specimen_ID'];
            }
        }

        /**
         * check if identstatus exists
         */
        if (trim($import[$i][2])) {
            $result = dbi_query("SELECT identstatusID FROM tbl_specimens_identstatus WHERE identification_status = " . quoteString(trim($import[$i][2])));
            if (mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_array($result);
                $data[$i]['identstatusID'] = $row['identstatusID'];
            } else {
                $OK = false;
                $status[$i] .= "no_identstatus ";
            }
        } else {
            $data[$i]['identstatusID'] = "";
        }

        /**
         * check if taxon exists
         */
        $taxonOK = false;
        $pieces = explode(' ', $import[$i][3], 3);
        $result = dbi_query("SELECT genID FROM tbl_tax_genera WHERE genus = " . quoteString($pieces[0]));
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_array($result);
            $genID = $row['genID'];
            $result = dbi_query("SELECT epithetID FROM tbl_tax_epithets WHERE epithet = " . quoteString($pieces[1]));
            if (mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_array($result);
                $epithetID = $row['epithetID'];
                $result = dbi_query("SELECT taxonID FROM tbl_tax_species WHERE genID = '$genID' AND speciesID = '$epithetID'");
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_array($result)) {
                        if (strcmp(getTaxon($row['taxonID']), trim($import[$i][3])) == 0) {
                            $taxonOK = true;
                            $data[$i]['taxonID'] = $row['taxonID'];
                            break;
                        }
                    }
                }
            }
        }
        if (!$taxonOK) {
            $OK = false;
            $status[$i] .= "no_taxa ";
        }

        /**
         * check the collectors (first and additional)
         */
        $collectorsOK = false;
        if (substr(trim($import[$i][4]), -6) == 'et al.') {
            $collector = substr(trim($import[$i][4]), 0, -7);
            $collector2 = "et al.";
        } else {
            $collectors = trim(strtr($import[$i][4], '&', ','));
            $parts = explode(', ', $collectors);
            $collector = trim($parts[0]);
            $collector2 = trim(substr(trim($import[$i][4]), strlen($collector) + 2));
        }
        $result = dbi_query("SELECT SammlerID FROM tbl_collector WHERE Sammler = " . quoteString($collector));
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_array($result);
            $collectorID = $row['SammlerID'];
            if (strlen($collector2) > 0) {
                $result = dbi_query("SELECT Sammler_2ID FROM tbl_collector_2 WHERE Sammler_2 = " . quoteString($collector2));
                if (mysqli_num_rows($result) > 0) {
                    $row = mysqli_fetch_array($result);
                    $collectorsOK = true;
                    $data[$i]['SammlerID'] = $collectorID;
                    $data[$i]['Sammler_2ID'] = $row['Sammler_2ID'];
                }
            } else {
                $collectorsOK = true;
                $data[$i]['SammlerID'] = $collectorID;
                $data[$i]['Sammler_2ID'] = 0;
            }
        }
        if (!$collectorsOK) {
            $OK = false;
            $status[$i] .= "no_collector ";
        }

        /**
         * check if series exists
         */
        if (trim($import[$i][5])) {
            $result = dbi_query("SELECT seriesID FROM tbl_specimens_series WHERE series = " . quoteString(trim($import[$i][5])));
            if (mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_array($result);
                $data[$i]['seriesID'] = $row['seriesID'];
            } else {
                $OK = false;
                $status[$i] .= "no_series ";
            }
        } else {
            $data[$i]['seriesID'] = "";
        }

        /**
         * fill series_number
         */
        $data[$i]['series_number'] = $import[$i][6];

        /**
         * fill Nummer
         */
        $data[$i]['Nummer'] = $import[$i][7];

        /**
         * fill alt_number
         */
        $data[$i]['alt_number'] = $import[$i][8];

        /**
         * fill Datum
         */
        $data[$i]['Datum'] = $import[$i][9];

        /**
         * fill Datum2
         */
        $data[$i]['Datum2'] = $import[$i][10];

        /**
         * fill det
         */
        $data[$i]['det'] = $import[$i][11];

        /**
         * fill typified
         */
        $data[$i]['typified'] = $import[$i][12];

        /**
         * check if type exists
         */
        if (trim($import[$i][13])) {
            $result = dbi_query("SELECT typusID FROM tbl_typi WHERE typus_lat = " . quoteString(trim($import[$i][13])));
            if (mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_array($result);
                $data[$i]['typusID'] = $row['typusID'];
            } else {
                $OK = false;
                $status[$i] .= "no_type ";
            }
        } else {
            $data[$i]['typusID'] = "";
        }

        /**
         * fill taxon_alt
         */
        $data[$i]['taxon_alt'] = $import[$i][14];

        /**
         * check if nation exists
         */
        if (trim($import[$i][15])) {
            $result = dbi_query("SELECT nationID FROM tbl_geo_nation WHERE nation_engl = " . quoteString(trim($import[$i][15])));
            if (mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_array($result);
                $data[$i]['NationID'] = $row['nationID'];
            } else {
                $OK = false;
                $status[$i] .= "no_nation ";
            }
        } else {
            $data[$i]['NationID'] = "";
        }

        /**
         * check if province exists
         */
        if (trim($import[$i][16])) {
            $sql = "SELECT provinceID
                    FROM tbl_geo_province
                    WHERE provinz = " . quoteString(trim($import[$i][16]))."
                     AND nationID = '" . intval($data[$i]['NationID']) . "'";
            $result = dbi_query($sql);
            if (mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_array($result);
                $data[$i]['provinceID'] = $row['provinceID'];
            } else {
                $OK = false;
                $status[$i] .= "no_province ";
            }
        } else {
            $data[$i]['provinceID'] = "";
        }

        /**
         * fill Fundort
         */
        $data[$i]['Fundort'] = $import[$i][17];

        /**
         * fill Bemerkungen
         */
        $data[$i]['Bemerkungen'] = $import[$i][18];

        if ($OK) {
            $status[$i] = "OK";
            $importable++;
        }
    }
}
?>

<?php if ($blocked): ?>
<script type="text/javascript" language="JavaScript">
  alert('You have no sufficient rights for the desired operation');
</script>
<?php endif; ?>

<h1>check Import Specimens</h1>

<form enctype="multipart/form-data" Action="<?php echo $_SERVER['PHP_SELF']; ?>" Method="POST" name="f">

<input type="hidden" name="MAX_FILE_SIZE" value="8000000" />
Import this file: <input name="userfile" type="file" />
<input type="submit" value="check Import" />
<p>

<?php
if ($type == 1) {  // file uploaded
    echo "$importable / " . count($import) . " entries are ready to be imported<br>\n";
    echo "<table border=\"1\">\n";
    for ($block = 0; $block < 2; $block++) {
        for ($i = 0; $i < count($import); $i++) {
            if (($block == 0 && $status[$i] == "OK") || ($block == 1 && $status[$i] != "OK" )) {
                if ($status[$i] == "OK") {
                    echo "<tr style=\"background-color:#00FF00\">";
                } elseif (strpos($status[$i], "exists") !== false) {
                    echo "<tr style=\"background-color:#0000FF\">";
                } else {
                    echo "<tr>";
                }
                if (strpos($status[$i], "exists") !== false) {
                    echo "<td><a href=\"../editSpecimens.php?sel=" . htmlspecialchars("<" . $exists[$i] . ">")
                       . "\" target=\"Specimens\">" . htmlspecialchars($import[$i][0]) . "</a></td>";
                } else {
                    echo "<td>" . $import[$i][0] . "</td>";
                }
                echo "<td" . ((strpos($status[$i], "no_collection") !== false) ? " style=\"background-color:red\"" : "")
                   . ">" . htmlspecialchars($import[$i][1]) . "</td>";
                echo "<td" . ((strpos($status[$i], "no_identstatus") !== false) ? " style=\"background-color:red\"" : "")
                   . ">" . htmlspecialchars($import[$i][2]) . "</td>";
                echo "<td" . ((strpos($status[$i], "no_taxa") !== false) ? " style=\"background-color:red\"" : "")
                   . ">" . htmlspecialchars($import[$i][3]) . "</td>";
                echo "<td" . ((strpos($status[$i], "no_collector") !== false) ? " style=\"background-color:red\"" : "")
                   . ">" . htmlspecialchars($import[$i][4]) . "</td>";
                echo "<td" . ((strpos($status[$i], "no_series") !== false) ? " style=\"background-color:red\"" : "")
                   . ">" . htmlspecialchars($import[$i][5]) . "</td>";
                echo "<td>" . $import[$i][6] . "</td>";
                echo "<td>" . $import[$i][7] . "</td>";
                echo "<td>" . $import[$i][8] . "</td>";
                echo "<td>" . $import[$i][9] . "</td>";
                echo "<td>" . $import[$i][10] . "</td>";
                echo "<td>" . $import[$i][11] . "</td>";
                echo "<td>" . $import[$i][12] . "</td>";
                echo "<td" . ((strpos($status[$i],"no_type") !== false) ? " style=\"background-color:red\"" : "")
                   . ">" . htmlspecialchars($import[$i][13]) . "</td>";
                echo "<td>" . $import[$i][14] . "</td>";
                echo "<td" . ((strpos($status[$i],"no_nation") !== false) ? " style=\"background-color:red\"" : "")
                   . ">" . htmlspecialchars($import[$i][15]) . "</td>";
                echo "<td" . ((strpos($status[$i],"no_province") !== false) ? " style=\"background-color:red\"" : "")
                   . ">" . htmlspecialchars($import[$i][16]) . "</td>";
                echo "<td>" . $import[$i][17] . "</td>";
                echo "<td>" . $import[$i][18] . "</td>";
                echo "</tr>\n";
            }
        }
    }
    echo "</table>\n";
}
?>

</form>

</body>
</html>