<?php
/**
 * A set of private functions is provided that parse and atomize the Namestrings into
 * single elements which are used for comparison against the reference list.
 *
 * @author Johannes Schachner <joschach@ap4net.at>
 * @since 23.03.2011
 */
class cls_herbarium_base {
    /**
     * NameParser instance for interfacing with GNA nameParser service
     * @var NameParser
     */
    protected $nameParser = NULL;
    
    /*******************\
    |                   |
    |  public functions |
    |                   |
    \*******************/
    public function __construct() {
        // create an instance of the nameParser class
        $this->nameParser = new NameParser();
    }

    /********************\
    |                    |
    |  private functions |
    |                    |
    \********************/
    /**
     * parses and atomizes the Namestring
     *
     * @param string $taxon taxon string to parse
     * @return array parts of the parsed string
     */
    protected function _tokenizeTaxa($taxon) {
        global $options;

        $result = array('genus' => '',
            'subgenus' => '',
            'epithet' => '',
            'author' => '',
            'rank' => 0,
            'subepithet' => '',
            'subauthor' => '');

        $taxon = ' ' . trim($taxon);
        $atoms = $this->_atomizeString($taxon, ' ');
        $maxatoms = count($atoms);
        $pos = 0;

        // check for any noise at the beginning of the taxon
        if ($this->_isEqual($atoms[$pos]['sub'], $options['taxonExclude']) !== false)
            $pos++;
        if ($pos >= $maxatoms)
            return $result;

        // get the genus
        $result['genus'] = $atoms[$pos++]['sub'];
        if ($pos >= $maxatoms)
            return $result;

        // check for any noise between genus and epithet
        if ($this->_isEqual($atoms[$pos]['sub'], $options['taxonExclude']) !== false)
            $pos++;
        if ($pos >= $maxatoms)
            return $result;

        // get the subgenus (if it exists)
        if (substr($atoms[$pos]['sub'], 0, 1) == '(' && substr($atoms[$pos]['sub'], -1, 1) == ')') {
            $result['subgenus'] = substr($atoms[$pos]['sub'], 1, strlen($atoms[$pos]['sub']) - 2);
            $pos++;
            if ($pos >= $maxatoms)
                return $result;
        }

        // get the epithet
        $result['epithet'] = $atoms[$pos++]['sub'];
        if ($pos >= $maxatoms)
            return $result;

        $sub = $this->_findInAtomizedArray($atoms, $options['taxonRankTokens']);
        if ($sub) {
            $result['rank'] = intval($sub['key']);
            $subpos = $sub['pos'];
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
            if ($pos >= $maxatoms)
                return $result;

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
            if ($pos >= $maxatoms)
                return $result;

            if ($result['rank']) {
                $pos = $subpos + 1;
                if ($pos >= $maxatoms)
                    return $result;

                // get the subepithet
                $result['subepithet'] = $atoms[$pos++]['sub'];
                if ($pos >= $maxatoms)
                    return $result;

                // subauthor auslesen
                while ($pos < $maxatoms) {
                    $result['subauthor'] .= $atoms[$pos++]['sub'] . ' ';
                }
                $result['subauthor'] = trim($result['subauthor']);
            }
        }

        return $result;
    }

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
    protected function _atomizeString($string, $delimiter, $trim = true) {
        if (strlen($string) == 0)
            return array(array('pos' => 0, 'len' => 0, 'sub' => ''));

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
    protected function _isEqual($text, $needle) {
        foreach ($needle as $key => $val) {
            if ($text == $val)
                return $key;
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
    protected function _findInAtomizedArray($haystack, $needle) {
        foreach ($haystack as $hayKey => $hayVal) {
            foreach ($needle as $neeKey => $neeVal) {
                if ($neeVal == $hayVal['sub'])
                    return array('pos' => $hayKey, 'key' => $neeKey);
            }
        }

        return false;
    }

    /**
     * implements the near-math function from tony rees
     *
     * This function replaces some of the leading characters with standardized ones, replaces
     * some characters within a string, drops repeating characters within a word and treats
     * (if wanted) the end of a word.
     *
     * @param string $text word to be treated
     * @param bool[optional] $stripEndings deal with variant endings if true
     * @param bool[optional] $ucFirst write the first character uppercase
     * @return string the treated word
     */
    protected function _near_match($text, $stripEndings = false, $ucFirst = false) {
        $text = strtolower(trim($text));

        if (!$text)
            return '';

        /**
         * first do a replacement of the leading characters
         */
        $change = array('ae' => 'e', 'cn' => 'n', 'ct' => 't', 'cz' => 'c', 'dj' => 'j',
            'ea' => 'e', 'eu' => 'u', 'gn' => 'n', 'kn' => 'n', 'mc' => 'mac',
            'mn' => 'n', 'oe' => 'e', 'qu' => 'q', 'ps' => 's', 'pt' => 't',
            'ts' => 's', 'wr' => 'r', 'x' => 'z');

        foreach ($change as $k => $v) {
            if (substr($text, 0, strlen($k)) == $k) {
                $text = $v . substr($text, strlen($k));
                break;
            }
        }

        /**
         * now keep the leading char and do "soundalike" replacements to the rest:
         * ae, oe, e, u, y -> i
         * ia, oi, o -> a
         * k -> c
         * sc, z -> s
         * h -> dropped
         */
        $change = array('ae' => 'i', 'oe' => 'i', 'ia' => 'a', 'oi' => 'a', 'sc' => 's', 'h' => '');
        foreach ($change as $k => $v) {
            $text = strtr($text, array($k => $v));
        }
        $text = strtr($text, 'euyokz', 'iiiacs');

        /**
         * now drop any repeated characters
         */
        $text2 = substr($text, 0, 1);
        for ($i = 1; $i < strlen($text); $i++) {
            if (substr($text2, -1) != substr($text, $i, 1))
                $text2 .= substr($text, $i, 1);
        }
        $text = $text2;

        /**
         * and finally deal with variant endings, which are:
         * -is (includes -us, -ys, -es), -im (was -um) and -as (-os)
         * translate them all to -a
         */
        if (strlen($text) > 4 && $stripEndings && (substr($text, -2) == 'is' || substr($text, -2) == 'im' || substr($text, -2) == 'as')) {
            $text = substr($text, 0, -2) . 'a';
        }

        if ($ucFirst)
            $text = ucfirst($text);

        return $text;
    }

    /**
     * format a taxon from its parts
     *
     * @param array $parts genus, subgenus, epithet, author, rank, subepithet, subauthor
     * @return string taxon
     */
    protected function _formatTaxon($parts) {
        global $options;

        $taxon = $parts['genus']
                . (($parts['subgenus']) ? " (" . $parts['subgenus'] . ")" : '')
                . (($parts['epithet']) ? " " . $parts['epithet'] : '')
                . (($parts['author']) ? " " . $parts['author'] : '');
        if ($parts['rank'] >= 1 && $parts['rank'] <= 5) {
            $taxon .= $options['taxonRankTokens'][$rank . 'a']
                    . (($parts['subepithet']) ? " " . $parts['subepithet'] : '')
                    . (($parts['subauthor']) ? " " . $parts['subauthor'] : '');
        }

        return $taxon;
    }
}
