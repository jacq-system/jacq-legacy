<?php
/**
 * tools and general functions
 *
 * Here come tools, functions and everything which is generally needed
 * @author Johannes Schachner <j.schachner@ddcs.at>
 * @version 14.07.2010
 */

/**
 * helper-function for ajax-calls with parameters
 *
 * looks for special patterns in a string or returns the original. Looks for <<foobar> (exact match is desired) or <foobar> (ID is given).
 * Returns array with 'type' (exact, id or search), 'value' (text between <<> or <> or original $value) and 'original' (original text)
 *
 * @param string $value text to parse
 * @return array result
 */
function AjaxParseValue ($value)
{
	if (preg_match('/\<\<(?P<exact>.*?)\>/', $value, $matches)) {  // group "exact" is returned, if <<foobar> is within searchtext
		$exact = $matches['exact'];
        return array('type'     => 'exact',
                     'value'    => $exact,
                     'original' => $value,
                     'exact'    => $exact);  // deprecated
	}

	if (preg_match('/\<(?P<ID>.*?)\>/', $value, $matches)) {       // group "id" is returend, if <foobar> is within searchtext
		$ID = $matches['ID'];
        return array('type'     => 'id',
                     'value'    => $ID,
                     'original' => $value,
                     'id'       => $ID);  // deprecated
	}

    // neither exact nor id, so return the original string
    return array('type'     => 'search',
                 'value'    => $value,  // no filtering neccessary, all occurrences of "<...>" would have been found already and the first one returned as "id"
                 'original' => $value,
                 'search'   => preg_replace('/\<.*\>/', '', $value));  // deprecated
}


/**
 * extracts an ID from a string. ID must be enclosed in "<>" brackets and be positioned at the end
 *
 * @param string $text string to extract ID from
 * @param boolean[optional] $bNoQuotes return plain ID without quotes
 * @return string ID enclosed in single quotes or the string "NULL" (without quotes)
 */
function extractID ($text, $bNoQuotes = false)
{
    $pos1 = strrpos($text, "<");
    $pos2 = strpos($text, ">", $pos1);
    if ($pos1 !== false && $pos2 !== false) {
        if (intval(substr($text, $pos1 + 1, $pos2 - $pos1 - 1))) {
            if ($bNoQuotes) {
                return intval(substr($text, $pos1 + 1, $pos2 - $pos1 - 1));
            } else {
                return "'" . intval(substr($text, $pos1 + 1, $pos2 - $pos1 - 1)) . "'";
            }
        } else {
            return "NULL"; // no ID found
        }
    } else {
        return "NULL"; // no ID found
    }
}


/**
 * replaces \r\n with \n and then \r or \n with <space>
 *
 * @param string $text text to scan
 * @return string result of replacements
 */
function replaceNewline($text)
{
	return strtr(str_replace("\r\n", "\n", $text), "\r\n", "  ");  //replaces \r\n with \n and then \r or \n with <space>
}

function extractID2($text)
{
    $pos1 = strrpos($text, "<");
    $pos2 = strrpos($text, ">");
    if ($pos1 !== false && $pos2 !== false) {
        if (intval(substr($text, $pos1 + 1, $pos2 - $pos1 - 1))) {
            return  intval(substr($text, $pos1 + 1, $pos2 - $pos1 - 1));
        } else {
            return substr($text, $pos1 + 1, $pos2 - $pos1 - 1);
        }
    } else {
        return null;
    }
}

/**
 * remove the ID from a string if present and returns the remaining part. ID must be enclosed in "<>" brackets and be positioned at the end
 *
 * @param tring $item string to parse
 * @return string string with removed ID (if any)
 */
function removeID ($item)
{
    $pos = strrpos($item, ' <');
    if ($pos !== false) {
        return substr($item, 0, $pos);
    } else {
        return $item;
    }
}





/**
 * cleans any user sent data with htmlentities and (on request) strip_quotes before sending it to a browser
 *
 * @param mixed $post data to clean
 * @param bool[optional] $withStrip use strip_tags (default=no)
 * @return mixed cleaned data
 */
function cleanData ($post, $withStrip = false)
{
    if ($withStrip) {
        return htmlentities(strip_tags($post), ENT_QUOTES, 'UTF-8');
    } else {
        return htmlentities($post, ENT_QUOTES, 'UTF-8');
    }
}


/**
 * function for automatic class loading
 *
 * @param string $class_name name of class and of file
 */
function jacq_autoload($class_name)
{
    if (preg_match('|^\w+$|', $class_name)) {
        $class_name = basename($class_name);
        if (substr($class_name, 0, 1) == 'x') {
            $path = 'ajax/modules/' . $class_name . '.php';
        } else {
            $path = 'inc/' . $class_name . '.php';
        }

        if (file_exists($path)) {
            include($path);
        } elseif (file_exists('../' . $path)) {
            include('../' . $path);
        } else {
            return false;
        }
    }
}

spl_autoload_register('jacq_autoload');
