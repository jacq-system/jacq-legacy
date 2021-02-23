<?php
$host = "localhost";  // hostname
$db   = "";   // database
$user = "";
$pass = "";

/** @var mysqli $dbLink */
$dbLink = new mysqli($host, $user, $pass, $db);
if ($dbLink->connect_errno) {
    echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">\n"
       . "<html>\n"
       . "<head><titel>Sorry, no connection ...</title></head>\n"
       . "<body><p>Sorry, no connection to database ...</p></body>\n"
       . "</html>\n";
}
$dbLink->set_charset('utf8');

/**
 * @param $sql
 * @return mysqli_result
 */
function dbi_query($sql)
{
    global $dbLink;

    $res = $dbLink->query($sql);

    if(!$res){
        echo $sql . "<br>\n";
        echo $dbLink->errno . ": " . $dbLink->error . "<br>\n";
    }

    return $res;
}

function extractID($text)
{
    $pos1 = strpos($text, "<");
    $pos2 = strpos($text, ">");
    if ($pos1 !== false && $pos2 !== false) {
        return "'".intval(substr($text,$pos1+1,$pos2-$pos1-1))."'";
    } else {
        return "NULL";
    }
}

/**
 * quotes a string or returns NULL if string is empty
 *
 * @global mysqli $dbLink link to mysql-db
 * @param string $text what to quote
 * @return string quoted string or NULL
 */
function quoteString($text)
{
    global $dbLink;

    if (strlen($text) > 0) {
        return "'" . $dbLink->real_escape_string($text) . "'";
    } else {
        return "NULL";
    }
}

function replaceNewline($text)
{
    return strtr(str_replace("\r\n", "\n", $text), "\r\n", "  ");  //replaces \r\n with \n and then \r or \n with <space>
}
