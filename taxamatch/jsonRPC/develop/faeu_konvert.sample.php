<?php
$host = 'host';
$user = 'user';
$pass = 'password';
$db   = 'dbname';

if (!isset($_GET['secret']) || $_GET['secret'] != '55AA') die();

mysql_connect($host, $user, $pass);
mysql_select_db($db);

$result = mysql_query("SELECT * FROM scientific_names");
while ($row = mysql_fetch_array($result)) {
    $offset  = strlen($row['GENUS_NAME']) + 1
             + (($row['INFRAGENUS_NAME']) ? strlen($row['INFRAGENUS_NAME']) + 3 : 0)
             + (($row['SPECIES_EPITHET']) ? strlen($row['SPECIES_EPITHET']) + 1 : 0)
             + (($row['INFRASPECIES_EPITHET']) ? strlen($row['INFRASPECIES_EPITHET']) + 1 : 0);
    $authorYear = trim(substr($row['FULLNAMECACHE'], $offset));

    if (substr($authorYear, 0, 1) == '(') {
        $brackets = 'Y';
        $authorYear = substr($authorYear, 1, -1);
    } else {
        $brackets = 'N';
    }
    $posComma = strrpos($authorYear, ',');
    if ($posComma === false) {
        $author = $authorYear;
        $year = null;
    } else {
        $author = trim(substr($authorYear, 0, $posComma));
        $year = trim(substr($authorYear, $posComma + 1));
    }

    mysql_query("UPDATE scientific_names SET
                  author = '" . mysql_real_escape_string($author) . "',
                  year = '" . mysql_real_escape_string($year) . "',
                  brackets = '$brackets'
                 WHERE pid = " . $row['pid']);
}

echo "done";