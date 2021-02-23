<?php
session_name('herbarium_wu_taxamatch');
session_start();

include('inc/variables.php');
include('inc/connect.php');

if (empty($_SESSION['uid'])) {
    $_SESSION['uid']      = 0;
    $_SESSION['username'] = '';
}

$debug = isset($_GET['debug']);
$databases_cache = 'databases_cache.inc';

if(isset($_GET['update']) || !file_exists($databases_cache) || (time()-filemtime($databases_cache)>50*7*24*60*60) ) {
    require_once('inc/jsonRPCClient.php');

    $url = $options['serviceTaxamatch'] . "json_rpc_taxamatchMdld.php";

    try {
        $service = new jsonRPCClient($url);
        $services = $service->getDatabases();

        file_put_contents($databases_cache,serialize($services));

    } catch (Exception $e) {
        $out =  "Error " . nl2br($e);
    }
}

$services = unserialize(file_get_contents($databases_cache));

if (!empty($_POST['username'])) {
    $result = dbi_query("SELECT uid, username FROM tbluser WHERE username = " . quoteString($_POST['username']));
    if ($result->num_rows > 0) {
        $row = $result->fetch_array();
        session_regenerate_id();  // prevent session fixation
        $_SESSION['uid']      = $row['uid'];
        $_SESSION['username'] = $row['username'];
    } else {
        do {
            $user = $_POST['username'] . sprintf("%05d", mt_rand(100, 99999));
            $result = dbi_query("SELECT uid FROM tbluser WHERE username = " . quoteString($user));
        } while ($result->num_rows > 0);
        dbi_query("INSERT INTO tbluser SET username = " . quoteString($user));
        $id = $dbLink->insert_id;
        $result = dbi_query("SELECT uid, username FROM tbluser WHERE uid = '$id'");
        $row = $result->fetch_array();
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
    $result = dbi_query("SELECT * FROM tbljobs WHERE finish IS NULL AND uid = '" . $_SESSION['uid'] . "'");
    if ($result->num_rows == 0) {

        if ($_POST['database'] == 'extern') {
            $_POST['database'] = $_POST['database_extern'];
        }

        $database='';
        if ($_POST['showSyn'] == 'synonyms') {
            $database='s_';
        }
        $database.=$_POST['database'];

        dbi_query("INSERT INTO tbljobs SET
                   uid = '" . $_SESSION['uid'] . "',
                   filename = " . quoteString($_FILES['userfile']['name']) . ",
                   db = '$database'");
        $jobID = $dbLink->insert_id;
        $oldIniSetting = ini_get('auto_detect_line_endings');
        ini_set('auto_detect_line_endings', '1');
        $handle = @fopen($_FILES['userfile']['tmp_name'], "r");
        if ($handle) {
            $ctr = 1;
            while (!feof($handle)) {
                $line = ucfirst(trim(fgets($handle)));
                if (substr($line, 0, 3) == chr(0xef) . chr(0xbb) . chr(0xbf)) $line = substr($line, 3);
                if ($line) {
                    dbi_query("INSERT INTO tblqueries SET
                               jobID  = '$jobID',
                               lineNr = '$ctr',
                               query  = " . quoteString($line));
                    $ctr++;
                }
            }
        }
        fclose($handle);
        ini_set('auto_detect_line_endings', $oldIniSetting);
        dbi_query("INSERT INTO tblschedule SET jobID = '$jobID'");
    }
    //exec("./bulkprocessCmd.php > /dev/null 2>&1 &");
}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - taxamatch MDLD</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link type="text/css" href="css/screen.css" rel="stylesheet">
  <link type="text/css" href="css/south-street/jquery-ui-1.8.14.custom.css" rel="stylesheet" />
  <script type="text/javascript" src="js/jquery-1.5.1.min.js"></script>
  <script type="text/javascript" src="js/jquery-ui-1.8.13.custom.min.js"></script>

  <script>
var tims=0;
var timid=0;

$(function() {
    $( "#dialog-about").dialog({
        autoOpen: false,
        modal: true,
        width:700,
        buttons:{"OK": function() {$( this ).dialog( "close" );}}
    });
    $('#aboutb').click(function(){
        $( "#dialog-about").dialog('open');
        return false;
    });
    $("#database_vienna").change(function(){
        $("#database_extern").attr('selectedIndex', '-1');
    });
    $("#database_extern").change(function () {
        $('input[name=database][value=extern]').attr('checked','checked');
    })
});
    </script>
</head>

<body onload="document.f.searchtext.focus();">


<div id="dialog-about" title="MDLD taxamatch implementation">
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
  <a href="#"><img align="top" src="images/information.png" border="0" id="aboutb"></a>
</h1>
<p>
<?PHP
if (!$_SESSION['uid']) {
    echo "<form Action='" . $_SERVER['SCRIPT_NAME'] . "' Method='POST' name='f'>\n"
       . "username: <input type='text' name='username'> \n"
       . "<input type='submit' value='login'>\n"
       . "</form>\n";
} else {
    echo "<form enctype='multipart/form-data' Action='" . $_SERVER['SCRIPT_NAME'] . "' Method='POST' name='f'>\n"
       . "<big><b>username: " . $_SESSION['username'] . "</b></big> \n"
       . "<input type='submit' name='logout' value='logout'>\n"
       . "<p>\n";

    $result = dbi_query("SELECT * FROM tbljobs WHERE finish IS NULL AND uid = '" . $_SESSION['uid'] . "'");
    if ($result->num_rows > 0) {
        $row = $result->fetch_array();
    } else {
        $row = array();
        echo<<<EOF

		<input type='hidden' name='MAX_FILE_SIZE' value='8000000' />
          upload this file: <input name='userfile' type='file' />
           <input type='submit' value='upload' />

    <table>
      <tr>
        <td>
          <div id="dbext"><input type="radio" name="database" value="vienna" id="database_vienna" checked>
          <label for="database_vienna">Virtual Herbarium Vienna</label>
          <input type="radio" name="database"  value="extern" >
          <label for="database_col">Extern </label>
          <div id="loading" style="text-align:center;margin-top:7px;display:none"><img src="images/loader.gif" valign="middle"><br><strong>Processing... <span id="tim"></span></strong></div>

</div>
          <select name="database_extern" id="database_extern" size="5">

EOF;

foreach ($services as $k=>$v){
    if ($k != 'vienna'){
        echo  "<option value=\"{$k}\">{$v['name']}</option>";
    }
}
        echo<<<EOF
            </select>
        </td>
      </tr><tr>
        <td>
                   <input type="checkbox" name="showSyn" id="showSyn" value="synonyms"><label for="showSyn">show synonyms</label>
        </td>
      </tr><tr>
        <td valign="top">
          <input type="submit" value="upload" id="showMatchJsonRPC" name="searchSpecies">
        </td>
      </tr>
    </table>
  </form>
</p>
EOF;

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
        $database = $services[$row['db']]['name'];
        echo "<tr class='out'>"
           . "<td class='outCenter'><a href='bulkshow.php?id=" . $row['jobID'] . "' target='_blank'>" . htmlspecialchars($row['filename']) . "</a></td>"
           . "<td class='outCenter'>" . htmlspecialchars($database) . "</td>"
           . "<td class='outCenter'>" . htmlspecialchars(($row['start']) ? $row['start'] : '-') . "</td>"
           . "<td class='outCenter'>-</td>"
           . "<td class='outCenter'>" . (($row['start']) ? 'processing' : 'waiting') . "</td>"
           . "<td class='out'>" . nl2br(htmlspecialchars($row['errors'])) . "</td>"
           . "</tr>\n";
    }

    $result = dbi_query("SELECT * FROM tbljobs WHERE finish IS NOT NULL AND uid = '" . $_SESSION['uid'] . "' ORDER by start DESC");
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_array()) {
            $database = $services[$row['db']]['name'];
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