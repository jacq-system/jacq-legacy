<?php
/**
 * Biological Namestring parser singleton:
 * establishes a class that parses and atomizes a Namestring into single elements
 * (see also herbar_taxamatch RPC service)
 *
 * @author Johannes Schachner <joschach@ap4net.at>
 * @since 21.09.2009
 */

/**
 * Biological Namestring parser singleton
 *
 * @package clsTaxonTokenizer
 * @subpackage classes
 */
class clsTaxonTokenizer
{
/********************\
|                    |
|  static variables  |
|                    |
\********************/

private static $instance = null;


/********************\
|                    |
|  static functions  |
|                    |
\********************/

/**
 * instances the class clsTaxonTokenizer
 *
 * @return clsTaxonTokenizer new instance of that class
 */
public static function Load()
{
    if (self::$instance == null) {
        self::$instance = new clsTaxonTokenizer();
    }
    return self::$instance;
}


/*************\
|             |
|  variables  |
|             |
\*************/

/**
 * strings that should be ignored by parsing/atomizing functions
 * suffice genus or connect genus and epithet
 */
private $taxonExclude = array('aff', 'aff.', 'cf', 'cf.', 'cv', 'cv.', 'agg', 'agg.', 'sect', 'sect.', 'ser', 'ser.', 'grex');

/**
 * strings of rank are recognized as seperators between species- and infraspecific-epithet
 */
private $taxonRankTokens = array('1a' => 'subsp.',  '1b' => 'subsp',
                                 '2a' => 'var.',    '2b' => 'var',
                                 '3a' => 'subvar.', '3b' => 'subvar',
                                 '4a' => 'forma',
                                 '5a' => 'subf.',   '5b' => 'subf',   '5c' => 'subforma');  // forma may be f.


/***************\
|               |
|  constructor  |
|               |
\***************/

protected function __construct () {}


/*******************\
|                   |
|  public functions |
|                   |
\*******************/

/**
 * parses and atomizes the Namestring
 *
 * @param string $taxon taxon string to parse
 * @return array parts of the parsed string
 */
public function tokenize($taxon)
{
    $result = array('genus'      => '',
                    'subgenus'   => '',
                    'epithet'    => '',
                    'author'     => '',
                    'rank'       => 0,
                    'subepithet' => '',
                    'subauthor'  => '');

    $taxon = ' ' . trim($taxon);
    $atoms = $this->_atomizeString($taxon, ' ');
    $maxatoms = count($atoms);
    $pos = 0;

    // check for any noise at the beginning of the taxon
    if ($this->_isEqual($atoms[$pos]['sub'], $this->taxonExclude) !== false) $pos++;
    if ($pos >= $maxatoms) return $result;

    // get the genus
    $result['genus'] = $atoms[$pos++]['sub'];
    if ($pos >= $maxatoms) return $result;

    // check for any noise between genus and epithet
    if ($this->_isEqual($atoms[$pos]['sub'], $this->taxonExclude) !== false) $pos++;
    if ($pos >= $maxatoms) return $result;

    // get the subgenus (if it exists)
    if (substr($atoms[$pos]['sub'], 0, 1) == '(' && substr($atoms[$pos]['sub'], -1, 1) == ')') {
        $result['subgenus'] = substr($atoms[$pos]['sub'], 1, strlen($atoms[$pos]['sub']) - 2);
        $pos++;
        if ($pos >= $maxatoms) return $result;
    }

    // get the epithet
    $result['epithet'] = $atoms[$pos++]['sub'];
    if ($pos >= $maxatoms) return $result;

    $sub = $this->_findInAtomizedArray($atoms, $this->taxonRankTokens);
    if ($sub) {
        $result['rank'] = intval($sub['key']);
        $subpos  = $sub['pos'];
    } else {
        $result['rank'] = 0;
        $subpos = $maxatoms;
    }

    // check if the next word has a lowercase beginning and there is no rank -> infraspecies with missing keyword
    $checkLetter = mb_substr($atoms[$pos]['sub'], 0, 1);
    if (mb_strtoupper($checkLetter) != $checkLetter && $result['rank'] == 0) {
        $result['author'] = '';
        $result['rank'] = 1;
        $result['subepithet'] = $atoms[$pos++]['sub'];
        if ($pos >= $maxatoms) return $result;

        // subauthor auslesen
        while ($pos < $maxatoms) {
            $result['subauthor'] .= $atoms[$pos++]['sub'] . ' ';
        }
        $result['subauthor'] = trim($result['subauthor']);
    } else {  // normal operation
        // get the author
        while ($pos < $subpos) {
            $result['author'] .= $atoms[$pos++]['sub'] . ' ';
        }
        $result['author'] = trim($result['author']);
        if ($pos >= $maxatoms) return $result;

        if ($result['rank']) {
            $pos = $subpos + 1;
            if ($pos >= $maxatoms) return $result;

            // get the subepithet
            $result['subepithet'] = $atoms[$pos++]['sub'];
            if ($pos >= $maxatoms) return $result;

            // subauthor auslesen
            while ($pos < $maxatoms) {
                $result['subauthor'] .= $atoms[$pos++]['sub'] . ' ';
            }
            $result['subauthor'] = trim($result['subauthor']);
        }
    }

    return $result;
}


/***********************\
|                       |
|  protected functions  |
|                       |
\***********************/


/*********************\
|                     |
|  private functions  |
|                     |
\*********************/

private function __clone () {}


/**
 * localises a delimiter within a string and returns the positions
 *
 * Localises a delimiter within a string. Returns the positions of the
 * first character after the delimiter, the number of characters to the
 * next delimiter or to the end of the string and the substring. Skips
 * delimiters at the beginning (if desired) and at the end of the string.
 *
 * @param string $string string to atomize
 * @param string $delimiter delimiter to use
 * @param bool $trim skip delimiters at the beginning
 * @return array {'pos','len','sub'} of the atomized string
 */
private function _atomizeString($string, $delimiter, $trim = true)
{
    if (strlen($string) == 0) return array(array('pos' => 0, 'len' => 0, 'sub' => ''));

    $result = array();
    $pos1 = 0;
    $pos2 = strpos($string, $delimiter);
    if ($trim && $pos2 === 0) {
        do {
            $pos1 = $pos2 + strlen($delimiter);
            $pos2 = strpos($string, $delimiter, $pos1);
        } while ($pos1 == $pos2);
    }

    while ($pos2 !== false) {
        $result[] = array('pos' => $pos1, 'len' => $pos2 - $pos1, 'sub' => substr($string, $pos1, $pos2 - $pos1));
        do {
            $pos1 = $pos2 + strlen($delimiter);
            $pos2 = strpos($string, $delimiter, $pos1);
        } while ($pos1 == $pos2);
    }

    if ($pos1 < strlen($string)) {
        $result[] = array('pos' => $pos1, 'len' => strlen($string) - $pos1, 'sub' => substr($string, $pos1, strlen($string) - $pos1));
    }

    return $result;
}


/**
 * checks if a given text is equal with one item of an array
 *
 * Tests every array item with the text and returns the array-key
 * if they are euqal. If no match is found it returns "false".
 *
 * @param string $text to be compared with
 * @param array $needle items to compare
 * @return mixed|bool key of found match or false
 */
private function _isEqual($text, $needle)
{
    foreach ($needle as $key => $val) {
        if ($text == $val) return $key;
    }

    return false;
}


/**
 * compares a stack of needles with an array and returns the first match
 *
 * Compares each item of the needles array with each 'sub'-item of an atomized
 * string and returns the position of the first match ('pos') and the key
 * of the found needle or false if no match was found.
 *
 * @param array $haystack result of 'atomizeString'
 * @param array $needle stack of needles to search for
 * @return array|bool found match {'pos','key'} or false
 */
private function _findInAtomizedArray($haystack, $needle)
{
    foreach ($haystack as $hayKey => $hayVal) {
        foreach ($needle as $neeKey => $neeVal) {
            if ($neeVal == $hayVal['sub']) return array('pos' => $hayKey, 'key' => $neeKey);
        }
    }

    return false;
}

}