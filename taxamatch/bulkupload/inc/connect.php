<?php
if (!@mysql_connect($options['dbhost'], $options['dbuser'], $options['dbpass'])) {
    echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">\n"
       . "<html>\n"
       . "<head><titel>Sorry, no connection ...</title></head>\n"
       . "<body><p>Sorry, no connection to database server ...</p></body>\n"
       . "</html>\n";
    exit();
} else if (!@mysql_select_db($options['dbname'])) {
    echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">\n"
       . "<html>\n"
       . "<head><titel>Sorry, no connection ...</title></head>\n"
       . "<body><p>Sorry, no connection to database ...</p></body>\n"
       . "</html>\n";
    exit();
}

//mysql_query("SET character_set_results='utf8'");
mysql_query("SET character set utf8");

function no_magic()   // PHP >= 4.1
{
    if (get_magic_quotes_gpc()) {
        foreach($_GET as $k => $v)  $_GET["$k"] = stripslashes($v);
        foreach($_POST as $k => $v) $_POST["$k"] = stripslashes($v);
    }
}

function db_query($sql)
{
    $result = @mysql_query($sql);
    if (!$result) {
        echo $sql . "<br>\n";
        echo mysql_error() . "<br>\n";
    }

    return $result;
}

function extractID($text)
{
    $pos1 = strrpos($text, "<");
    $pos2 = strrpos($text, ">");
    if ($pos1!==false && $pos2 !== false) {
        if (intval(substr($text, $pos1 + 1, $pos2 - $pos1 - 1))) {
            return "'" . intval(substr($text, $pos1 + 1, $pos2 - $pos1 - 1)) . "'";
        } else {
            return "NULL";
        }
    } else {
        return "NULL";
    }
}

function quoteString($text)
{
    if (strlen($text) > 0) {
        return "'" . mysql_real_escape_string($text) . "'";
    } else {
        return "NULL";
    }
}

/**
 * checks an INT-value and returns NULL if zero
 *
 * @param integer $value
 * @return integer or NULL
 */
function makeInt($value)
{
    if (intval($value)) {
        return "'" . intval($value) . "'";
    } else {
        return "NULL";
    }
}

function replaceNewline($text)
{
    return strtr(str_replace("\r\n", "\n",$text), "\r\n", "  ");  //replaces \r\n with \n and then \r or \n with <space>
}

/**
 * checks if the variable of a given type is set and if it is echo it
 *
 * @param string $name name of variable
 * @param string $type type of variable (GET, POST, SESSION)
 */
function echoSpecial($name, $type)
{
    switch ($type) {
        case 'GET':     echo (isset($_GET[$name]))     ? htmlspecialchars($_GET[$name])     : ''; break;
        case 'POST':    echo (isset($_POST[$name]))    ? htmlspecialchars($_POST[$name])    : ''; break;
        case 'SESSION': echo (isset($_SESSION[$name])) ? htmlspecialchars($_SESSION[$name]) : ''; break;
    }
}