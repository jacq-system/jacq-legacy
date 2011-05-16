<?php
session_name('herbarium_wu_taxamatch');
session_start();

include('inc/variables.php');
include('inc/connect.php');

if (empty($_SESSION['uid'])) {
    $_SESSION['uid']      = 0;
    $_SESSION['username'] = '';
}

if (!empty($_POST['username'])) {
    $result = db_query("SELECT uid, username FROM tbluser WHERE username = " . quoteString($_POST['username']));
    if (mysql_num_rows($result) > 0) {
        $row = mysql_fetch_array($result);
        session_regenerate_id();  // prevent session fixation
        $_SESSION['uid']      = $row['uid'];
        $_SESSION['username'] = $row['username'];
    } else {
        do {
            $user = $_POST['username'] . sprintf("%05d", mt_rand(100, 99999));
            $result = db_query("SELECT uid FROM tbluser WHERE username = " . quoteString($user));
        } while (mysql_num_rows($result) > 0);
        db_query("INSERT INTO tbluser SET username = " . quoteString($user));
        $id = mysql_insert_id();
        $row = mysql_fetch_array(db_query("SELECT uid, username FROM tbluser WHERE uid = '$id'"));
        session_regenerate_id();  // prevent session fixation
        $_SESSION['uid']      = $row['uid'];
        $_SESSION['username'] = $row['username'];
    }
} elseif (!empty($_POST['logout'])) {
    $_SESSION = array();  // Unset all of the session variables.
    session_destroy();
    $_SESSION['uid']      = 0;
    $_SESSION['username'] = '';
} elseif (isset($_FILES['userfile']) && is_uploaded_file($_FILES['userfile']['tmp_name'])) {
    $result = db_query("SELECT * FROM tbljobs WHERE finish IS NULL AND uid = '" . $_SESSION['uid'] . "'");
    if (mysql_num_rows($result) == 0) {
        switch ($_POST['database']) {
            case 'col': $database = 'col'; break;
            case 'fe': $database = 'fe'; break;
            default: 
                $database = 'vhv';
                // BP, 07.2010: if synonyms are checked, mark it in DB ('vhs' instead of 'vhv')
                if ($_POST['showSyn'] == 'synonyms') {
                    $database = "vhs";
                }
                // BP: END
        }
        db_query("INSERT INTO tbljobs SET
                   uid = '" . $_SESSION['uid'] . "',
                   filename = " . quoteString($_FILES['userfile']['name']) . ",
                   db = '$database'");
        $jobID = mysql_insert_id();
        $oldIniSetting = ini_get('auto_detect_line_endings');
        ini_set('auto_detect_line_endings', '1');
        $handle = @fopen($_FILES['userfile']['tmp_name'], "r");
        if ($handle) {
            $ctr = 1;
            while (!feof($handle)) {
                $line = ucfirst(trim(fgets($handle)));
                if (substr($line, 0, 3) == chr(0xef) . chr(0xbb) . chr(0xbf)) $line = substr($line, 3);
                if ($line) {
                    db_query("INSERT INTO tblqueries SET
                               jobID  = '$jobID',
                               lineNr = '$ctr',
                               query  = " . quoteString($line));
                    $ctr++;
                }
            }
        }
        fclose($handle);
        ini_set('auto_detect_line_endings', $oldIniSetting);
        db_query("INSERT INTO tblschedule SET jobID = '$jobID'");
    }
    //exec("./bulkprocessCmd.php > /dev/null 2>&1 &");
}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>taxamatch - bulk upload</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <script type="text/javascript" src="inc/iBox/ibox.js"></script>
  <script type="text/javascript" language="JavaScript">
    iBox.setPath('inc/iBox/');
    iBox.tags_to_hide = ['embed', 'object'];
  </script>
</head>

<body>

<div id="iBox_content" style="display:none;">
<br>
This is a bulkupload routine utilizing the <b>MDLD taxamatch implementation</b> (modified Damerau-Levenshtein algorithm, originally developed by tony rees at csiro dot org)<br>
(phonetic = near match not included so far)<br>
<br>
Functionality includes parsing of Names for <b>uninomials</b> (family and genus names), <b>binomials</b> and <b>trinomials</b> and includes a check for subgeneric names against the genus table in our reference list<br>
<br>
! content is mostly phanerogamic plants, to be complemented by the index fungorum and CoL names in october 2009!<br>
<br>
type in a user name and select a file for upload<br>
<br>
the format of the file should be any text file "abcd.txt" / UTF-8 encoding / one Name per line<br>
<br>
If you test, please at the moment do not run more than 1000 names at a time - jobs are processed consecutively - and use the refresh button for refreshing the result screen, (bear in mind that our db contains mostly plant names)<br>
<br>
<b>Results can be downloaded as a csv-file</b><br>
</div>

<h1>
  Taxamatch bulk upload
  &nbsp;
  <img align="top" src="images/information.png" onclick="iBox.showURL('#iBox_content', 'info', iBox.parseQuery('width=520')); return false;">
</h1>
<?php
if (!$_SESSION['uid']) {
    echo "<form Action='" . $_SERVER['PHP_SELF'] . "' Method='POST' name='f'>\n"
       . "username: <input type='text' name='username'> \n"
       . "<input type='submit' value='login'>\n"
       . "</form>\n";
} else {
    echo "<form enctype='multipart/form-data' Action='" . $_SERVER['PHP_SELF'] . "' Method='POST' name='f'>\n"
       . "<big><b>username: " . $_SESSION['username'] . "</b></big> \n"
       . "<input type='submit' name='logout' value='logout'>\n"
       . "<p>\n";

    $result = db_query("SELECT * FROM tbljobs WHERE finish IS NULL AND uid = '" . $_SESSION['uid'] . "'");
    if (mysql_num_rows($result) > 0) {
        $row = mysql_fetch_array($result);
    } else {
        $row = array();
        echo "<input type='radio' name='database' id='database_vienna' value='vienna' checked>\n"
           . "<label for='database_vienna'>Virtual Herbarium Vienna</label>\n"
           . "<input type='radio' name='database' id='database_col' value='col'>\n"
           . "<label for='database_col'>Catalogue of Life</label>\n"
           . "<input type='radio' name='database' id='database_fe' value='fe'>\n"
           . "<label for='database_fe'>Fauna Europea</label>\n"
           . "<input type='checkbox' name='showSyn' id='showSyn' value='synonyms'>\n"
           . "<label for='syn'>show synonyms</label><br>\n"
           . "<input type='hidden' name='MAX_FILE_SIZE' value='8000000' />\n"
           . "upload this file: <input name='userfile' type='file' />\n"
           . "<input type='submit' value='upload' />\n";

    }
    echo "<div style='font-size:large; font-weight:bold;'><input type='submit' name='refresh' value='refresh list'></div>\n"
       . "</form>\n";

    echo "<table class='out'>\n"
       . "<tr class='out'>"
       . "<th class='out'>filename</th>"
       . "<th class='out'>database</th>"
       . "<th class='out'>start</th>"
       . "<th class='out'>finish</th>"
       . "<th class='out'>status</th>"
       . "<th class='out'>errors</th></tr>";

    if ($row) {
        switch ($row['db']) {
            case 'col': $database = "Catalogue of Life"; break;
            case 'fe': $database = "Fauna Europea"; break;
            default: $database = "Virtual Herbarium Vienna";
        }
        echo "<tr class='out'>"
           . "<td class='outCenter'><a href='bulkshow.php?id=" . $row['jobID'] . "' target='_blank'>" . htmlspecialchars($row['filename']) . "</a></td>"
           . "<td class='outCenter'>" . htmlspecialchars($database) . "</td>"
           . "<td class='outCenter'>" . htmlspecialchars(($row['start']) ? $row['start'] : '-') . "</td>"
           . "<td class='outCenter'>-</td>"
           . "<td class='outCenter'>" . (($row['start']) ? 'processing' : 'waiting') . "</td>"
           . "<td class='out'>" . nl2br(htmlspecialchars($row['errors'])) . "</td>"
           . "</tr>\n";
    }

    $result = db_query("SELECT * FROM tbljobs WHERE finish IS NOT NULL AND uid = '" . $_SESSION['uid'] . "' ORDER by start DESC");
    if (mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_array($result)) {
            switch ($row['db']) {
                case 'col': $database = "Catalogue of Life"; break;
                case 'fe': $database = "Fauna Europea"; break;
                default: $database = "Virtual Herbarium Vienna";
            }
            echo "<tr class='out'>"
               . "<td class='outCenter'><a href='bulkshow.php?id=" . $row['jobID'] . "' target='_blank'>" . htmlspecialchars($row['filename']) . "</a></td>"
               . "<td class='outCenter'>" . htmlspecialchars($database) . "</td>"
               . "<td class='outCenter'>" . htmlspecialchars($row['start']) . "</td>"
               . "<td class='outCenter'>" . htmlspecialchars($row['finish']) . "</td>"
               . "<td class='outCenter'>finished</td>"
               . "<td class='out'>" . nl2br(htmlspecialchars($row['errors'])) . "</td>"
               . "</tr>\n";
        }
    }
}
?>

</body>
</html>