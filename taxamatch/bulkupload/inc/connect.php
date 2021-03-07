<?php
// Connect to DB

/** @var mysqli $dbLink */
$dbLink = new mysqli($options['dbhost'], $options['dbuser'], $options['dbpass'], $options['dbname']);
if ($dbLink->connect_errno) {
    echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">\n" .
    "<html>\n" .
    "<head><titel>Sorry, no connection ...</title></head>\n" .
    "<body><p>Sorry, no connection to database ...</p></body>\n" .
    "</html>\n";
    exit();
}
$dbLink->set_charset('utf8');

function dbi_query($sql)
{
    global $dbLink;

    $result = $dbLink->query($sql);
    if ($dbLink->connect_errno) {
        echo $sql . "<br>\n";
        echo $dbLink->error . "<br>\n";
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
    global $dbLink;

    if (strlen($text) > 0) {
        return "'" . $dbLink->real_escape_string($text) . "'";
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