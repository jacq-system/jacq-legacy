<?php
session_start();
require("inc/connect.php");
require("inc/api_functions.php");

no_magic();

//---------- check every input ----------
if (!checkRight('batch')) {                 // only user with right "api" can change API
    echo "<html><head></head><body>\n"
       . "<h1>Error</h1>\n"
       . "Access denied\n"
       . "</body></html>\n";
    die();
}
if (!checkRight('batchAdmin')) {
    $result = db_query("SELECT source_name FROM herbarinput.meta WHERE source_id = " . $_SESSION['sid']);
    if (mysql_num_rows($result) == 0) {
        echo "<html><head></head><body>\n"
           . "<h1>Error</h1>\n"
           . "Your institution is not in the batch database.\n"
           . "</body></html>\n";
        die();
    }
}

$batchID = (isset($_POST['batch'])) ? intval($_POST['batch']) : 0;

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - import File into Batch data</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <style type="text/css">
    input.button       { font-size: 100%; font-family: sans-serif; padding-left: 0; font-weight: bold; }
    input.button:hover { background-color: #abffab }
    .error { font-size: 100%; font-family: sans-serif; font-weight: bold; color: black; background-color: red; }
  </style>
</head>

<body>

<h1>File Import into unsent Batches</h1>
<strong>Caution: already existent Batch data will be deleted on file import</strong>

<form enctype="multipart/form-data" action='<?php echo $_SERVER['PHP_SELF']; ?>' method='POST' name='f'>
<?php
    $sql = "SELECT remarks, date_supplied, batchID, batchnumber, source_code
            FROM api.tbl_api_batches
             LEFT JOIN herbarinput.meta ON api.tbl_api_batches.sourceID_fk = herbarinput.meta.source_id
            WHERE sent = '0'";
    if (!checkRight('batchAdmin')) {
        $sql .= " AND api.tbl_api_batches.sourceID_fk = " . $_SESSION['sid'];  // check right and sourceID
    }
    $sql .= " ORDER BY source_code, batchnumber, date_supplied DESC";
    $result = db_query($sql);
    $selects = "";
    while ($row = mysql_fetch_array($result)) {
        $selects .= "  <option value='" . $row['batchID'] . "'" . (($batchID == $row['batchID']) ? " selected" : '') . ">"
                  . $row['date_supplied']
                  . htmlspecialchars(' <' . (($row['source_code']) ? $row['source_code'] . '-' : '') . $row['batchnumber'] . '> (' . trim($row['remarks']) . ')')
                  . "</option>\n";
    }
?>
    <p><select name="batch"><?php echo $selects; ?></select></p>
    <p><input type="hidden" name="MAX_FILE_SIZE" value="300000" /></p>
    <p>Import this File: <input name="importfile" type="file" /></p>
    <p><input class="button" type="submit" name="insertIntoBatch" value=" Delete Batch data and import file into selected batch "></p>
</form>

<?php
if (isset($_POST['insertIntoBatch']) && isset($_FILES['importfile']['tmp_name'])) {
    $buffer = file($_FILES['importfile']['tmp_name']);

    // split into lines, keep only first column, strip everything after "_" or "." if any
    $lines = array();
    foreach ($buffer as $key => $val) {
        $columns = str_getcsv($val);
        if ($columns) {
            $pos1 = strpos($columns[0], '_');
            $pos2 = strpos($columns[0], '.');
            if ($pos1) {
                $line = substr($columns[0], 0, $pos1);
            } elseif ($pos2) {
                $line = substr($columns[0], 0, $pos2);
            } else {
                $line = $columns[0];
            }
            $lines[$key] = $line;
        }
    }

    $lines = array_unique($lines);      // eliminate doubles

    // find the longest coll_short_prj possible, using first line in file
    $check = $lines[0];
    $coll_short_prj = '';
    for ($i = 1; $i <= strlen($check); $i++) {
        $result = db_query("SELECT collectionID FROM tbl_management_collections WHERE coll_short_prj LIKE '" . substr($check, 0, $i) . "%'");
        if (mysql_num_rows($result) > 0) {
            $coll_short_prj = substr($check, 0, $i);
        } else {
            break;
        }
    }
    $coll_short_prj = strtolower($coll_short_prj);

    // find specimen_ID for given HerbNummer, store any errors
    $import = array();
    $errors = 0;
    $linesToImport = 0;
    foreach ($lines as $key => $val) {
        $import[$key] = array('line' => $val, 'specimenID' => 0, 'error' => '');
        if (strtolower(substr($val, 0, strlen($coll_short_prj))) != $coll_short_prj) {
            $import[$key]['error'] = 'wrong or no collection';
            $errors++;
        } else {
            $herbNummer = substr($val, strlen($coll_short_prj));  // strip coll_short_prj at the beginning
            if ($coll_short_prj == 'W' && strlen($herbNummer) == 11) {
                $herbNummer = substr($herbNummer, 0, 4) . '-' . substr($herbNummer, 4);
            }
            $result = db_query("SELECT s.specimen_ID
                                FROM tbl_specimens s, tbl_management_collections mc
                                WHERE s.collectionID = mc.collectionID
                                 AND mc.coll_short_prj LIKE '$coll_short_prj'
                                 AND s.HerbNummer = '$herbNummer'");
            if (mysql_num_rows($result) == 0) {
                $import[$key]['error'] = 'specimen not found';
                $errors++;
            } elseif (mysql_num_rows($result) > 1) {
                $import[$key]['error'] = 'multi specimen found';
                $errors++;
            } else {
                $row = mysql_fetch_array($result);
                $import[$key]['specimenID'] = $row['specimen_ID'];
                $linesToImport++;
            }
        }
    }

    if (count($import) == 0 || !$batchID) {
        echo "<div class='error'>Error: nothing to import</div>";
    } else {
        echo "<div>Collection found: $coll_short_prj<br>$linesToImport lines imported<br>$errors errors found</div>\n";
        if ($errors) {
            echo "<div class='error'>";
            foreach ($import as $key => $val) {
                if ($val['error']) {
                    echo $key . ": " . $val['line'] . " - " . $val['error'] . "<br>\n";
                }
            }
            echo "</div>";
        }
        if ($linesToImport) {
            db_query("DELETE FROM api.tbl_api_specimens WHERE batchID_fk = '$batchID'");
            echo "<div>";
            foreach ($import as $key => $val) {
                if ($val['specimenID']) {
                    db_query("INSERT INTO api.tbl_api_specimens SET
                               specimen_ID = '" . $val['specimenID'] . "',
                               batchID_fk  = '$batchID'");
                    update_tbl_api_units($val['specimenID']);
                    update_tbl_api_units_identifications($val['specimenID']);
                    echo $key . ": " . $val['line'] . " - <" . $val['specimenID'] . "> imported<br>\n";
                }
            }
            echo "</div>";
        }
    }
}
?>

</body>
</html>