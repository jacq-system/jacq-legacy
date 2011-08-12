<?php
session_start();
require("../inc/connect.php");
no_magic();

$scanIPNI = false;
$scanTblPerson = true;

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - scan tbl_person</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="../css/screen.css">
  <style type="text/css">
    th { font-weight: bold; font-size: medium }
    tr { vertical-align: top }
    td { vertical-align: top }
    .missing { margin: 0px; padding: 0px }
    td.missing { vertical-align: middle }
  </style>
</head>

<body>
<?php
if ($scanIPNI) {
    $letter = 'A';
    $found = $missing = 0;
    for ($i = 0; $i < 26; $i++, $letter++) {
        echo $letter . "<br>\n";
        $handle = fopen ("http://www.ipni.org/ipni/advAuthorSearch.do?find_abbreviation={$letter}*&output_format=delimited-extended", "r");
        $buffer = fgets($handle, 4096);
        while (!feof($handle)) {
            $buffer = fgets($handle, 4096);
            $parts = explode('%', $buffer);
            if (strpos($parts[6], '-') !== false) {
                $dates = explode('-', $parts[6]);
                if (strlen(trim($dates[0])) > 0) {
                    $date1 = trim($dates[0]);
                } else {
                    $date1 = NULL;
                }
                if (strlen(trim($dates[1])) > 0) {
                    $date2 = trim($dates[1]);
                } else {
                    $date2 = NULL;
                }
            } else {
                $date1 = $date2 = NULL;
            }
            $result = db_query("SELECT person_ID FROM tbl_person WHERE IPNIauthor_IDfk = '" . $parts[0] . "'");
            if (mysql_num_rows($result) == 0) {
                $id       = $parts[0];
                $version  = $parts[1];
                $abbrev   = strtr(strtr($parts[2], array("." => ". ")), array(". -" => ".-"));
                $forename = $parts[3];
                $surname  = $parts[4];

                $sql = "INSERT INTO tbl_person SET
                        IPNIauthor_IDfk = '" . mysql_real_escape_string($id) . "',
                        IPNI_version = '" . mysql_real_escape_string($version) . "',
                        p_abbrev = '" . mysql_real_escape_string($abbrev) . "',
                        p_firstname = '" . mysql_real_escape_string($forename) . "',
                        p_familyname = '" . mysql_real_escape_string($surname) . "'";
                if ($date1) {
                    $sql .= ", p_birthdate = '" . mysql_real_escape_string($date1) . "'";
                }
                if ($date2) {
                    $sql .= ", p_death = '" . mysql_real_escape_string($date2) . "'";
                }
                mysql_query($sql);
                $missing++;
            } else {
                $row = mysql_fetch_array($result);
                if ($date1 || $date2) {
                    $sql = "UPDATE tbl_person SET ";
                    if ($date1) {
                        $sql .= "p_birthdate = '" . mysql_real_escape_string($date1) . "'";
                        if ($date2) $sql .= ", ";
                    }
                    if ($date2) {
                        $sql .= "p_death = '" . mysql_real_escape_string($date2) . "'";
                    }
                    $sql .= " WHERE person_ID = " . $row['person_ID'];
                    mysql_query($sql);
                }
                $found++;
            }
        }
        fclose ($handle);
    }

    echo $found . " found<br>" . $missing . " missing";
}

if ($scanTblPerson) {
    mysql_query('DELETE FROM tbl_person_alternative');
    $result = mysql_query("SELECT person_ID, p_firstname, p_familyname FROM tbl_person");
    while ($row = mysql_fetch_array($result)) {
        $alternative = $row['p_familyname'];
        if (trim($row['p_firstname'])) {
            $text = trim($row['p_firstname']);
            $parts = explode(' ', $text);
            foreach ($parts as $k => $v) {
                if (strpos($v, '-') !== false) {
                    $subparts = explode('-', $part);
                    foreach ($subparts as $subk => $subv) {
                        $subparts[$subk] = substr($subv, 0, 1) . '.';
                    }
                    $parts[$k] = implode('-', $subparts);
                } elseif ($v != 'des' && $v != 'van' && $v != 'der' && $v != 'de' && $v != 'von') {
                    $parts[$k] = substr($v, 0, 1) . '.';
                }
            }
            $alternative .= ", " . implode(' ', $parts);
        }

        mysql_query("INSERT INTO tbl_person_alternative SET person_ID = " . $row['person_ID'] . ", p_alternative = '" . mysql_real_escape_string($alternative) . "'");
    }
}
?>

</body>
</html>