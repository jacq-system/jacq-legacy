<?php
/**
 * Display methods singleton - formatting display
 *
 * A singleton to supply various display helper methods
 *
 * @author Johannes Schachner
 * @version 1.0
 * @package clsDisplay
 */


/**
 * Display methods singleton - formatting display
 * @package clsDisplay
 * @subpackage classes
 */
class clsDisplay
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
 * instances the class clsDisplay
 *
 * @return clsDisplay new instance of that class
 */
public static function Load()
{
    if (self::$instance == null) {
        self::$instance = new clsDisplay();
    }
    return self::$instance;
}

/*************\
|             |
|  variables  |
|             |
\*************/


/***************\
|               |
|  constructor  |
|               |
\***************/

protected function __construct () {}

/********************\
|                    |
|  public functions  |
|                    |
\********************/


/**
 * returns a formatted protolog-string when given a valid citation-ID
 *
 * @param int $citationID citation-ID
 * @param bool[optional] adds the citationID between brackets at the end (default no)
 * @return string formatted protolog-string
 */
public function protolog ($citationID, $withID = false)
{
    try {
        /* @var $db clsDbAccess */
        $db = clsDbAccess::Connect('INPUT');

        /* @var $dbst PDOStatement */
        $dbst = $db->prepare("SELECT citationID, suptitel, le.autor as editor, la.autor, l.periodicalID, lp.periodical, vol, part, jahr, pp
                              FROM tbl_lit l
                               LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID = l.periodicalID
                               LEFT JOIN tbl_lit_authors le ON le.autorID = l.editorsID
                               LEFT JOIN tbl_lit_authors la ON la.autorID = l.autorID
                              WHERE citationID = :citationID");
        $dbst->execute(array(":citationID" => $citationID));
        $row = $dbst->fetch();

        $ret = $row['autor'] . " (" . substr($row['jahr'], 0, 4) . ")";
        if ($row['suptitel'])     $ret .= " in " . $row['editor'] . ": " . $row['suptitel'];
        if ($row['periodicalID']) $ret .= " " . $row['periodical'];
        $ret .= " " . $row['vol'];
        if ($row['part']) $ret .= " (" . $row['part'] . ")";
        $ret .= ": " . $row['pp'] . ".";
        if ($withID) $ret .= " <" . $row['citationID'] . ">";

        return $ret;
    }
    catch (Exception $e) {
        exit($e->getMessage());
    }
}


/**
 * returns a formatted taxon-string when given a valid taxon-ID
 *
 * @param int $taxonID taxon-ID
 * @param bool[optional] $withSeperator adds a seperator after genus and epithet (default no)
 * @param bool[optional] $withDT adds the DallaTorre information (default no)
 * @param bool[optional] $withID adds the taxonID between brackets at the end (default no)
 * @return string formatted taxon-string
 */
public function taxon ($taxonID, $withSeperator = false, $withDT = false, $withID = false)
{
    try {
        /* @var $db clsDbAccess */
        $db = clsDbAccess::Connect('INPUT');

        /* @var $dbst PDOStatement */
        $dbst = $db->prepare("SELECT taxonID, tg.genus, tg.DallaTorreIDs, tg.DallaTorreZusatzIDs,
                               ta.author  author0,  ta1.author  author1,  ta2.author  author2,  ta3.author  author3,  ta4.author  author4,  ta5.author  author5,
                               te.epithet epithet0, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3, te4.epithet epithet4, te5.epithet epithet5
                              FROM tbl_tax_species ts
                               LEFT JOIN tbl_tax_authors ta ON ta.authorID = ts.authorID
                               LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID = ts.subspecies_authorID
                               LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID = ts.variety_authorID
                               LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID = ts.subvariety_authorID
                               LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID = ts.forma_authorID
                               LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID = ts.subforma_authorID
                               LEFT JOIN tbl_tax_epithets te ON te.epithetID = ts.speciesID
                               LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID = ts.subspeciesID
                               LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID = ts.varietyID
                               LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID = ts.subvarietyID
                               LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID = ts.formaID
                               LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID = ts.subformaID
                               LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
                              WHERE taxonID = :taxonID");
        $dbst->execute(array(":taxonID" => $taxonID));
        $row = $dbst->fetch();

        $ret = $row['genus'];
        if ($row['epithet0']) $ret .= " "          .$row['epithet0'] . (($withSeperator) ? chr(194) . chr(183) : "") . " " . $row['author0'];
        if ($row['epithet1']) $ret .= " subsp. "   .$row['epithet1'] . " " . $row['author1'];
        if ($row['epithet2']) $ret .= " var. "     .$row['epithet2'] . " " . $row['author2'];
        if ($row['epithet3']) $ret .= " subvar. "  .$row['epithet3'] . " " . $row['author3'];
        if ($row['epithet4']) $ret .= " forma "    .$row['epithet4'] . " " . $row['author4'];
        if ($row['epithet5']) $ret .= " subforma " .$row['epithet5'] . " " . $row['author5'];

        if ($withDT) $ret .= " " . $row['DallaTorreIDs'] . $row['DallaTorreZusatzIDs'];
        if ($withID) $ret .= " <" . $row['taxonID'] . ">";

        return $ret;
    }
    catch (Exception $e) {
        exit($e->getMessage());
    }
}

/**
 * returns either a formatted hybrid-taxon-string (if taxon is a hybrid)
 * or a normal taxon-string (if taxon is'nt a hybrid) when given a taxon-ID
 *
 * @param int $taxonID taxon-ID
 * @param bool[optional] adds a seperator after genus and epithet (default no)
 * @param bool[optional] adds the taxonID between brackets at the end (default no)
 * @return string formatted taxon-string
 */
public function taxonWithHybrids ($taxonID, $withSeperator = false, $withID = false)
{
    try {
        /* @var $db clsDbAccess */
        $db = clsDbAccess::Connect('INPUT');

        /* @var $dbst PDOStatement */
        $dbst = $db->prepare("SELECT taxon_ID_fk, parent_1_ID, parent_2_ID
                              FROM tbl_tax_hybrids
                              WHERE taxon_ID_fk = :taxonID");
        $dbst->execute(array(":taxonID" => $taxonID));
        $rows = $dbst->fetchAll();
        if (count($rows) > 0) {
            return $this->taxon($rows[0]['parent_1_ID'], $withSeperator) . " x " . $this->taxon($rows[0]['parent_2_ID']) . (($withID) ? " <" . $rows[0]['taxon_ID_fk'] . ">" : "");
        } else {
            return $this->taxon($taxonID, $withSeperator, false, $withID);
        }
    }
    catch (Exception $e) {
        exit($e->getMessage());
    }
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


}