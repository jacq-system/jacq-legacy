<?php

/**
 * Autocomplete methods singleton - handling all autocomplete methods
 *
 * A singleton to supply various autocomplete methods
 *
 * @author Johannes Schachner
 * @version 1.0
 * @package clsAutocomplete
 */

/**
 * Autocomplete methods singleton - handling all autocomplete methods
 * @package clsAutocomplete
 * @subpackage classes
 */
class clsAutocomplete {
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
     * instances the class clsAutocomplete
     *
     * @return clsAutocomplete new instance of that class
     */
    public static function Load()
    {
        if (self::$instance == null) {
            self::$instance = new clsAutocomplete();
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

    protected function __construct()
    {
    }

    /********************\
    |                    |
    |  public functions  |
    |                    |
    \********************/

    /** W
     * autocomplete a taxonomy author entry field
     *
     * @param string $value text to search for
     * @param bool[optional] $noExternals only results for "external=0"(default no)
     * @return array data array ready to send to jQuery-autocomplete via json-encode
     */
    public function taxAuthor($value, $noExternals = false)
    {
        $results = array();
        try {
            $db = clsDbAccess::Connect('INPUT');
            $sql = "SELECT author, authorID, Brummit_Powell_full
                    FROM tbl_tax_authors
                    WHERE ";
            if (isset($value['id'])) {
                $dbst = $db->prepare($sql . " authorID = ?");
                $dbst->execute(array($value['id']));
            } else {
                if (isset($value['exact'])) {
                    $value['search'] = $value['exact'];
                    $equ = '=';
                } else {
                    $value['search'] = $value['search'] . '%';
                    $equ = 'LIKE';
                }
                $sql .= "(   author {$equ} ?
                          OR Brummit_Powell_full {$equ} ?)";
                if ($noExternals) {
                    $sql .=" AND external = 0";
                }
                $sql.=" ORDER BY author, Brummit_Powell_full";
                $dbst = $db->prepare($sql);
                $dbst->execute(array($value['search'], $value['search']));
            }

            $rows = $dbst->fetchAll();
            if (count($rows) > 0) {

                foreach ($rows as $row) {
                    $res = $row['author'];
                    if ($row['Brummit_Powell_full'])
                        $res .=chr(194) . chr(183) . " [" . replaceNewline($row['Brummit_Powell_full']) . "]";
                    $results[] = array(
                        'id' => $row['authorID'],
                        'label' => $res . " <" . $row['authorID'] . ">",
                        'value' => $res . " <" . $row['authorID'] . ">",
                        'color' => '');
                }
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
        }

        return $results;
    }

    /** W
     * autocomplete a taxonomy author entry field
     *
     * @param string $value text to search for
     * @param bool[optional] $noExternals only results for "external=0"(default no)
     * @return array data array ready to send to jQuery-autocomplete via json-encode
     */
    public function litAuthor($value, $noExternals = false)
    {
        $results = array();
        try {
            $db = clsDbAccess::Connect('INPUT');
            $sql = "SELECT autor, autorID
                    FROM tbl_lit_authors
            		WHERE ";

            if (isset($value['id'])) {
                $dbst = $db->prepare($sql . " autorID = ?");
                $dbst->execute(array($value['id']));
            } else if (isset($value['exact'])) {
                $dbst = $db->prepare($sql . " autor = ?");
                $dbst->execute(array($value['exact']));
            } else {
                $dbst = $db->prepare($sql . " autor LIKE ?");
                $dbst->execute(array($value['search'] . '%'));
            }

            $rows = $dbst->fetchAll();
            if (count($rows) > 0) {
                foreach ($rows as $row) {
                    $res = $row['autor'];
                    $results[] = array(
                        'id' => $row['autorID'],
                        'label' => $res . " <" . $row['autorID'] . ">",
                        'value' => $res . " <" . $row['autorID'] . ">",
                        'color' => '');
                }
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
        }

        return $results;
    }

    /**
     * autocomplete an author entry field without external entries(external=0)
     *
     * @param string $value text to search for
     * @return array data array ready to send to jQuery-autocomplete via json-encode
     */
    public function taxAuthorNoExternals($value)
    {
        return $this->taxAuthor($value, true);
    }

    /** W
     * autocomplete a collector entry field
     *
     * @param string $value text to search for
     * @param bool[optional] $second if true use tbl_collector2(default=false)
     * @return array data array ready to send to jQuery-autocomplete via json-encode
     */
    public function collector($value, $second = false)
    {
        $results = array();
        try {
            $db = clsDbAccess::Connect('INPUT');
            if ($second) {
                $sql = "SELECT Sammler_2 AS Sammler, Sammler_2ID AS SammlerID
                        FROM tbl_collector_2
                        WHERE ";
                if (isset($value['id'])) {
                    $dbst = $db->prepare($sql . " Sammler_2ID = ?");
                    $dbst->execute(array($value['id']));
                } else if (isset($value['exact'])) {
                    $dbst = $db->prepare($sql . " Sammler_2 = ?");
                    $dbst->execute(array($value['exact']));
                } else {
                    $dbst = $db->prepare($sql . " Sammler_2 LIKE ?");
                    $dbst->execute(array($value['search'] . '%'));
                }
            } else {
                $sql = "SELECT Sammler, SammlerID
                        FROM tbl_collector
                        WHERE ";
                if (isset($value['id'])) {
                    $dbst = $db->prepare($sql . " SammlerID = ?");
                    $dbst->execute(array($value['id']));
                } else if (isset($value['exact'])) {
                    $dbst = $db->prepare($sql . " Sammler = ?");
                    $dbst->execute(array($value['exact']));
                } else {
                    $dbst = $db->prepare($sql . " Sammler LIKE ?");
                    $dbst->execute(array($value['search'] . '%'));
                }
            }

            $rows = $dbst->fetchAll();
            if (count($rows) > 0) {
                foreach ($rows as $row) {
                    $results[] = array(
                        'id' => $row['SammlerID'],
                        'label' => $row['Sammler'] . " <" . $row['SammlerID'] . ">",
                        'value' => $row['Sammler'] . " <" . $row['SammlerID'] . ">",
                        'color' => '');
                }
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
        }

        return $results;
    }

    /**
     * autocomplete a second collector entry field(tbl_collector_2)
     *
     * @param string $value text to search for
     * @return array data array ready to send to jQuery-autocomplete via json-encode
     */
    public function collector2($value)
    {
        return $this->collector($value, true);
    }

    /** W
     * autocomplete a person entry field
     * The various parts of a person field are identified and used (if present) as a search criteria
     *
     * @param string $value text to search for
     * @return array data array ready to send to jQuery-autocomplete via json-encode
     */
    public function person($value)
    {
        $results = array();
        try {
            $db = clsDbAccess::Connect('INPUT');

            $sql = "SELECT person_ID, p_familyname, p_firstname, p_birthdate, p_death
                    FROM tbl_person
                    WHERE ";

            if (isset($value['id'])) {
                $dbst = $db->prepare($sql . " person_ID = ?");
                $dbst->execute(array($value['id']));
            } else {
                $v = isset($value['exact']) ? $value['exact'] : $value['search'];
                $pieces = explode(", ", $v, 2);
                $p_familyname = $pieces[0];
                if (count($pieces) > 1) {
                    $pieces = explode("(", $pieces[1], 2);
                    $p_firstname = $pieces[0];
                    if (count($pieces) > 1) {
                        $pieces = explode(" - ", $pieces[1], 2);
                        $p_birthdate = $pieces[0];
                        if (count($pieces) > 1) {
                            $pieces = explode(")", $pieces[1], 2);
                            $p_death = $pieces[0];
                        } else {
                            $p_death = '';
                        }
                    } else {
                        $p_birthdate = $p_death = '';
                    }
                } else {
                    $p_firstname = $p_birthdate = $p_death = '';
                }

                if (isset($value['exact'])) {
                    $equ = '=';
                } else {
                    if (!empty($p_familyname)) {
                        $p_familyname .= '%';
                    }
                    if (!empty($p_firstname)) {
                        $p_firstname .= '%';
                    }
                    if (!empty($p_birthdate)) {
                        $p_birthdate .= '%';
                    }
                    if (!empty($p_death)) {
                        $p_death .= '%';
                    }
                    $equ = 'LIKE';
                }

                $sql .= " p_familyname {$equ} ?";
                $param = array($p_familyname);
                if ($p_firstname) {
                    $sql .= " AND p_firstname {$equ} ?";
                    $param[] = $p_firstname;
                }
                if ($p_birthdate) {
                    $sql .= " AND p_birthdate {$equ} ?";
                    $param[] = $p_birthdate;
                }
                if ($p_death) {
                    $sql .= " AND p_death {$equ} ?";
                    $param[] = $p_death;
                }

                $dbst = $db->prepare($sql . " ORDER BY p_familyname, p_firstname, p_birthdate, p_death");
                $dbst->execute($param);
            }
            //return array('id'	=> 's','label'=> $sql,'value'=> $sql,'color'=> '');

            $rows = $dbst->fetchAll();
            if (count($rows) > 0) {
                foreach ($rows as $row) {
                    $text = $row['p_familyname'] . ", " . $row['p_firstname'] . "(" . $row['p_birthdate'] . " - " . $row['p_death'] . ") <" . $row['person_ID'] . ">";
                    $results[] = array(
                        'id' => $row['person_ID'],
                        'label' => $text,
                        'value' => $text,
                        'color' => ''
                    );
                }
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
        }

        return $results;
    }

    /** W
     * autocomplete a citation entry field
     * If the searchstring has only one part only the author will be searched
     * If the searchstring consists of two parts the first one is used for author, the second one for year, title and periodical
     *
     * @param string $value text to search for
     * @return array data array ready to send to jQuery-autocomplete via json-encode
     */
    public function citation($value)
    {
        $results = array();
        try {
            $db = clsDbAccess::Connect('INPUT');

            if (isset($value['id'])) {
                if ($value['id'] == '' || $value['id'] == '0' || $value['id'] == 0) {
                    return array();
                }
                $display = clsDisplay::Load();

                $label = $display->protolog($value['id'], true);
                $results[] = array(
                    'id' => $value['id'],
                    'label' => $label,
                    'value' => "$label <{$value['id']}>",
                    'color' => ''
                );
            } else {
                $v = isset($value['exact']) ? $value['exact'] : $value['search'];
                $pieces = explode(" ", $v);
                $autor = $pieces[0];
                if (!empty($pieces[1]) && (strlen($pieces[1]) > 2 || (strlen($pieces[1]) == 2 && substr($pieces[1], 1, 1) != '.'))) {
                    $second = $pieces[1];
                } else {
                    $second = '';
                }

                $sql = "SELECT citationID
                        FROM tbl_lit l
                        LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID=l.periodicalID
                        LEFT JOIN tbl_lit_authors le ON le.autorID=l.editorsID
                        LEFT JOIN tbl_lit_authors la ON la.autorID=l.autorID
                        WHERE ";
                if (isset($value['exact'])) {
                    $equ = '=';
                } else {
                    if (!empty($autor)) {
                        $autor .= '%';
                    }
                    if (!empty($second)) {
                        $second .= '%';
                    }
                    $equ = 'LIKE';
                }


                $sql .= "(   la.autor {$equ} ?
                          OR le.autor {$equ} ?) ";
                $param = array($autor, $autor);

                if ($second) {
                    $sql.=" AND (l.jahr {$equ} ?
                            OR l.titel {$equ} ?
                            OR lp.periodical {$equ} ? )";
                    $param[] = $second;
                    $param[] = $second;
                    $param[] = $second;
                }
                $dbst = $db->prepare($sql . " ORDER BY la.autor, jahr, lp.periodical, vol, part, pp");
                $dbst->execute($param);

                $rows = $dbst->fetchAll();
                if (count($rows) > 0) {
                    $display = clsDisplay::Load();
                    foreach ($rows as $row) {
                        $results[] = array(
                            'id' => $row['citationID'],
                            'label' => $display->protolog($row['citationID'], true),
                            'value' => $display->protolog($row['citationID'], true),
                            'color' => ''
                        );
                    }
                }
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
        }

        return $results;
    }

    /** W
     * autocomplete a periodical entry field
     *
     * @param string $value text to search for
     * @return array data array ready to send to jQuery-autocomplete via json-encode
     */
    public function periodical($value)
    {
        $results = array();
        try {
            $db = clsDbAccess::Connect('INPUT');

            $sql = "SELECT periodical, periodicalID
                    FROM tbl_lit_periodicals
                    WHERE ";
            if (isset($value['id'])) {
                $dbst = $db->prepare($sql . " periodicalID = ?");
                $dbst->execute(array($value['id']));
            } else if (isset($value['exact'])) {
                $dbst = $db->prepare($sql . " periodical = ?");
                $dbst->execute(array($value['exact']));
            } else {
                $dbst = $db->prepare($sql . " periodical LIKE ? OR periodical_full LIKE ? ORDER BY periodical");
                $dbst->execute(array($value['search'] . '%', '%' . $value['search'] . '%'));
            }

            $rows = $dbst->fetchAll();
            if (count($rows) > 0) {
                foreach ($rows as $row) {
                    $results[] = array('id' => $row['periodicalID'],
                        'label' => $row['periodical'] . " <" . $row['periodicalID'] . ">",
                        'value' => $row['periodical'] . " <" . $row['periodicalID'] . ">",
                        'color' => '');
                }
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
        }

        return $results;
    }

    /** W
     * autocomplete a periodical entry field
     *
     * @param string $value text to search for
     * @return array data array ready to send to jQuery-autocomplete via json-encode
     */
    public function bestand($value)
    {
        $results = array();
        try {
            $db = clsDbAccess::Connect('INPUT');

            $sql = "SELECT DISTINCT bestand
                    FROM tbl_lit
                    WHERE ";
            if (isset($value['id'])) {
                $dbst = $db->prepare($sql . " bestand = ?");
                $dbst->execute(array($value['id']));
            } else if (isset($value['exact'])) {
                $dbst = $db->prepare($sql . " bestand = ?");
                $dbst->execute(array($value['exact']));
            } else {
                $dbst = $db->prepare($sql . " bestand LIKE ? ORDER BY bestand");
                $dbst->execute(array($value['search'] . '%'));
            }

            $rows = $dbst->fetchAll();
            if (count($rows) > 0) {
                foreach ($rows as $row) {
                    $results[] = array(
                        'id' => $row['bestand'],
                        'label' => $row['bestand'],
                        'value' => $row['bestand'],
                        'color' => '');
                }
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
        }

        return $results;
    }

    /**
     * autocomplete a periodical entry field
     *
     * @param string $value text to search for
     * @return array data array ready to send to jQuery-autocomplete via json-encode
     */
    public function categories($value)
    {
        $results = array();
        try {
            $db = clsDbAccess::Connect('INPUT');

            $sql = "SELECT DISTINCT category
                    FROM tbl_lit
                    WHERE ";
            if (isset($value['id'])) {
                $dbst = $db->prepare($sql . " category = ?");
                $dbst->execute(array($value['id']));
            } else if (isset($value['exact'])) {
                $dbst = $db->prepare($sql . " category = ?");
                $dbst->execute(array($value['exact']));
            } else {
                $dbst = $db->prepare($sql . " category LIKE ? ORDER BY category");
                $dbst->execute(array($value['search'] . '%'));
            }

            $rows = $dbst->fetchAll();
            if (count($rows) > 0) {
                foreach ($rows as $row) {
                    $results[] = array(
                        'id' => $row['category'],
                        'label' => $row['category'],
                        'value' => $row['category'],
                        'color' => '');
                }
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
        }

        return $results;
    }

    /** W
     * autocomplete a periodical entry field
     *
     * @param string $value text to search for
     * @return array data array ready to send to jQuery-autocomplete via json-encode
     */
    public function publisher($value)
    {
        $results = array();
        try {
            $db = clsDbAccess::Connect('INPUT');

            $sql = "SELECT publisher, publisherID
                    FROM tbl_lit_publishers
                    WHERE";
            if (isset($value['id'])) {
                $dbst = $db->prepare($sql . " publisherID = ?");
                $dbst->execute(array($value['id']));
            } else if (isset($value['exact'])) {
                $dbst = $db->prepare($sql . " publisher = ?");
                $dbst->execute(array($value['exact']));
            } else {
                $dbst = $db->prepare($sql . " publisher LIKE ? ORDER BY publisher");
                $dbst->execute(array($value['search'] . '%'));
            }

            $rows = $dbst->fetchAll();
            if (count($rows) > 0) {
                foreach ($rows as $row) {
                    $results[] = array('id' => $row['publisherID'],
                        'label' => $row['publisher'] . " <" . $row['publisherID'] . ">",
                        'value' => $row['publisher'] . " <" . $row['publisherID'] . ">",
                        'color' => '');
                }
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
        }

        return $results;
    }

    /** W
     * autocomplete a family entry field
     *
     * @param string $value text to search for
     * @return array data array ready to send to jQuery-autocomplete via json-encode
     */
    public function family($value)
    {
        $results = array();
        try {
            $db = clsDbAccess::Connect('INPUT');

            $sql = "SELECT family, familyID, category
                    FROM tbl_tax_families tf
                     LEFT JOIN tbl_tax_systematic_categories tsc ON tsc.categoryID=tf.categoryID
                    WHERE ";
            if (isset($value['id'])) {
                $dbst = $db->prepare($sql . " familyID = ?");
                $dbst->execute(array($value['id']));
            } else if (isset($value['exact'])) {
                $dbst = $db->prepare($sql . " family = ?");
                $dbst->execute(array($value['exact']));
            } else {
                $dbst = $db->prepare($sql . " family LIKE ? ORDER BY family");
                $dbst->execute(array($value['search'] . '%'));
            }

            $rows = $dbst->fetchAll();
            if (count($rows) > 0) {
                foreach ($rows as $row) {
                    $results[] = array(
                        'id' => $row['familyID'],
                        'label' => $row['family'] . " " . $row['category'] . " <" . $row['familyID'] . ">",
                        'value' => $row['family'] . " " . $row['category'] . " <" . $row['familyID'] . ">",
                        'color' => '');
                }
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
        }

        return $results;
    }

    /** W
     * autocomplete a genus entry field
     *
     * @param string $value text to search for
     * @return array data array ready to send to jQuery-autocomplete via json-encode
     */
    public function genus($value)
    {
        $results = array();
        try {
            $db = clsDbAccess::Connect('INPUT');

            $sql = "SELECT tg.genus, tg.genID, tg.DallaTorreIDs, tg.DallaTorreZusatzIDs, ta.author, tf.family, tsc.category
                    FROM tbl_tax_genera tg
                     LEFT JOIN tbl_tax_authors ta                ON ta.authorID   = tg.authorID
                     LEFT JOIN tbl_tax_families tf               ON tg.familyID   = tf.familyID
                     LEFT JOIN tbl_tax_systematic_categories tsc ON tf.categoryID = tsc.categoryID
                    WHERE ";
            if (isset($value['id'])) {
                $dbst = $db->prepare($sql . " tg.genID = ?");
                $dbst->execute(array($value['id']));
            } else if (isset($value['exact'])) {
                $dbst = $db->prepare($sql . " tg.genus = ?");
                $dbst->execute(array($value['exact']));
            } else {
                $dbst = $db->prepare($sql . " tg.genus LIKE ? ORDER BY tg.genus");
                $dbst->execute(array($value['search'] . '%'));
            }

            $rows = $dbst->fetchAll();
            if (count($rows) > 0) {
                foreach ($rows as $row) {
                    $text = $row['genus'] . " " . $row['author'] . " " . $row['family'] . " "
                            . $row['category'] . " " . $row['DallaTorreIDs'] . $row['DallaTorreZusatzIDs']
                            . " <" . $row['genID'] . ">";
                    $results[] = array('id' => $row['genID'],
                        'label' => $text,
                        'value' => $text,
                        'color' => '');
                }
                foreach ($results as $k => $v) {
                    $results[$k]['label'] = preg_replace("/ [\s]+/", " ", $v['label']);
                    $results[$k]['value'] = preg_replace("/ [\s]+/", " ", $v['value']);
                }
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
        }

        return $results;
    }

    /** W
     * autocomplete an epithet entry field
     *
     * @param string $value text to search for
     * @param bool[optional] $noExternals only results for "external=0"(default no)
     * @return array data array ready to send to jQuery-autocomplete via json-encode
     */
    public function epithet($value, $noExternals = false)
    {
        $results = array();
        try {
            $db = clsDbAccess::Connect('INPUT');
            $sql = "SELECT epithet, epithetID
    				FROM tbl_tax_epithets
        			WHERE ";
            if (isset($value['id'])) {
                $dbst = $db->prepare($sql . " epithetID = ?");
                $dbst->execute(array($value['id']));
            } else if (isset($value['exact'])) {
                $dbst = $db->prepare($sql . " epithet = ?" . (($noExternals) ? " AND external = 0" : ''));
                $dbst->execute(array($value['exact']));
            } else {
                $dbst = $db->prepare($sql . " epithet LIKE ?" . (($noExternals) ? " AND external = 0" : '') . " ORDER BY epithet");
                $dbst->execute(array($value['search'] . '%'));
            }

            $rows = $dbst->fetchAll();
            if (count($rows) > 0) {
                foreach ($rows as $row) {
                    $results[] = array('id' => $row['epithetID'],
                        'label' => $row['epithet'] . " <" . $row['epithetID'] . ">",
                        'value' => $row['epithet'] . " <" . $row['epithetID'] . ">",
                        'color' => '');
                }
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
        }

        return $results;
    }

    /**
     * autocomplete an epithet entry field without external entries(external=0)
     *
     * @param string $value text to search for
     * @return array data array ready to send to jQuery-autocomplete via json-encode
     */
    public function epithetNoExternals($value)
    {
        return $this->epithet($value, true);
    }

    /** W
     * autocomplete a taxon entry field
     * If the searchstring has only one part before the separator only taxa with empty species are presented.
     * If the searchstring consists of two parts the first one is used for genus, the second one for species
     *
     * @param string $value text to search for
     * @param bool[optional] $noExternals only results for "external=0"(default no)
     * @param bool[optional] $withDT adds the DallaTorre information(default no)
     * @return array data array ready to send to jQuery-autocomplete via json-encode
     */
    public function taxon2($value, $noExternals = false, $withDT = false) {

        return $this->taxon($value, $noExternals, $withDT, 2);
    }

    /** W
     * autocomplete a taxon entry field
     * If the searchstring has only one part before the separator only taxa with empty species are presented.
     * If the searchstring consists of two parts the first one is used for genus, the second one for species
     *
     * @param string $value text to search for
     * @param bool[optional] $noExternals only results for "external=0"(default no)
     * @param bool[optional] $withDT adds the DallaTorre information(default no)
     * @param string[optional] $extraCondition extra condition to be used with the sql statement (SQL where condition)
     * @return array data array ready to send to jQuery-autocomplete via json-encode
     */
    public function taxon($value, $noExternals = false, $withDT = false, $withID = true, $extraCondition = "")
    {
        $results = array();
        try {
            $db = clsDbAccess::Connect('INPUT');
            $sql = "SELECT taxonID, ts.external
                    FROM tbl_tax_species ts
                     LEFT JOIN tbl_tax_epithets te0 ON te0.epithetID = ts.speciesID
                     LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID = ts.subspeciesID
                     LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID = ts.varietyID
                     LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID = ts.subvarietyID
                     LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID = ts.formaID
                     LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID = ts.subformaID
                     LEFT JOIN tbl_tax_genera tg ON tg.genID=ts.genID
                    WHERE ";
            if (isset($value['id'])) {
                $dbst = $db->prepare($sql . " ts.taxonID = ?" . (($noExternals) ? " AND ts.external = 0" : ''));
                $dbst->execute(array($value['id']));
            } else {
                $v = isset($value['exact']) ? $value['exact'] : $value['search'];
                $pieces1 = explode(chr(194) . chr(183), $v);
                $pieces = explode(" ", $pieces1[0]);

                if (isset($value['exact'])) {
                    $equ = '=';
                } else {
                    if (!empty($pieces[0])) {
                        $pieces[0] .= '%';
                    }
                    if (!empty($pieces[1])) {
                        $pieces[1] .= '%';
                    }
                    $equ = 'LIKE';
                }

                $sql .= " tg.genus {$equ} ? ";
                $param = array($pieces[0]);
                if ($noExternals) {
                    $sql .= " AND ts.external = 0 ";
                }
                if (!empty($pieces[1])) {
                    $sql .= " AND te0.epithet {$equ} ? ";
                    $param[] = $pieces[1];
                } else {
                    $sql .= " AND te0.epithet IS NULL";
                }
                // Check if we filter the resulting taxons using some extra condition(s)
                if(!empty($extraCondition)) {
                    $sql .= $extraCondition;
                }
                if (empty($value['exact'])) {
                    $sql .= " ORDER BY tg.genus, te0.epithet, te1.epithet, te2.epithet, te3.epithet, te4.epithet, te5.epithet";
                }
                $dbst = $db->prepare($sql);
                $dbst->execute($param);
            }

            $rows = $dbst->fetchAll();
            if (count($rows) > 0) {
                $display = clsDisplay::Load();
                foreach ($rows as $row) {
                    $results[] = array('id' => $row['taxonID'],
                        'label' => $display->taxon($row['taxonID'], true, $withDT, true),
                        'value' => $display->taxon($row['taxonID'], true, $withDT, $withID),
                        'color' => ($row['external']) ? 'red' : '');
                }
                foreach ($results as $k => $v) {   // eliminate multiple whitespaces within the result
                    $results[$k]['label'] = preg_replace("/ [\s]+/", " ", $v['label']);
                    $results[$k]['value'] = preg_replace("/ [\s]+/", " ", $v['value']);
                }
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
        }

        return $results;
    }

    /**
     * autocomplete a taxon entry field without external entries(external=0)
     * If the searchstring has only one part before the separator only taxa with empty species are presented.
     * If the searchstring consists of two parts the first one is used for genus, the second one for species
     *
     * @param string $value text to search for
     * @param bool[optional] $noExternals only results for "external=0"(default no)
     * @return array data array ready to send to jQuery-autocomplete via json-encode
     */
    public function taxonNoExternals($value)
    {
        return $this->taxon($value, true, false);
    }

    /** W
     * autocomplete a taxon entry field and include the DallaTorre information
     * If the searchstring has only one part before the separator only taxa with empty species are presented.
     * If the searchstring consists of two parts the first one is used for genus, the second one for species
     *
     * @param string $value text to search for
     * @param bool[optional] $noExternals only results for "external=0"(default no)
     * @return array data array ready to send to jQuery-autocomplete via json-encode
     */
    public function taxonWithDT($value)
    {
        return $this->taxon($value, false, true);
    }

    /** to be checked...
     * autocomplete a taxon entry field with hybrid at the end of the list
     * If the searchstring has only one part before the separator only taxa with empty species are presented.
     * If the searchstring consists of two parts the first one is used for genus, the second one for species
     *
     * @param string $value text to search for
     * @param bool[optional] $noExternals only results for "external=0"(default no)
     * @return array data array ready to send to jQuery-autocomplete via json-encode
     */
    public function taxonWithHybrids($value, $noExternals = false)
    {
        $results = array();
        try {
            $display = clsDisplay::Load();
            /* @var $db clsDbAccess */
            $db = clsDbAccess::Connect('INPUT');
            $sql = "SELECT taxonID, ts.synID, ts.external
                    FROM tbl_tax_species ts
                     LEFT JOIN tbl_tax_epithets te0 ON te0.epithetID = ts.speciesID
                     LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID = ts.subspeciesID
                     LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID = ts.varietyID
                     LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID = ts.subvarietyID
                     LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID = ts.formaID
                     LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID = ts.subformaID
                     LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
                    WHERE ";

            if (isset($value['id'])) {
                $dbst = $db->prepare($sql . " taxonID = ?");
                $dbst->execute(array($value['id']));
            } else {
                $v = isset($value['exact']) ? $value['exact'] : $value['search'];
                $pieces1 = explode(chr(194) . chr(183), $v);
                $pieces = explode(" ", $pieces1[0]);

                if (isset($value['exact'])) {
                    $equ = '=';
                } else {
                    if (!empty($pieces[0])) {
                        $pieces[0] .= '%';
                    }
                    if (!empty($pieces[1])) {
                        $pieces[1] .= '%';
                    }
                    $equ = 'LIKE';
                }

                $sql .= " tg.genus {$equ} ? ";
                $param = array($pieces[0]);
                if ($noExternals) {
                    $sql .= " AND ts.external = 0 ";
                }
                if (!empty($pieces[1])) {
                    $sql .= " AND te0.epithet {$equ} ? ";
                    $param[] = $pieces[1];
                } else {
                    $sql .= " AND te0.epithet IS NULL";
                }
                if (empty($value['exact'])) {
                    $sql .= " ORDER BY tg.genus, te0.epithet, te1.epithet, te2.epithet, te3.epithet, te4.epithet, te5.epithet";
                }
                $dbst = $db->prepare($sql);
                $dbst->execute($param);
            }

            $rows = $dbst->fetchAll();
            if (count($rows) > 0) {
                foreach ($rows as $row) {

                    $color = '';
                    if(is_numeric($row['synID'])){
                        $color = 'red';
                    } else if ($row['external']){
                        $color = 'darkorange'; // see also .taxon_external in screen.css
                    }

                    $results[] = array(
                        'id' => $row['taxonID'],
                        'label' => $display->taxon($row['taxonID'], true, false, true),
                        'value' => $display->taxon($row['taxonID'], true, false, true),
                        'color' => $color,
                    );
                }
            }
            // works up to here
            // ab hier: muss geprüft werden.
            if (!isset($value['id'])) {

                // how to test??
                $sql = "SELECT ts.taxonID, ts.synID
                        FROM (tbl_tax_species ts, tbl_tax_hybrids th)
                         LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
                         LEFT JOIN tbl_tax_species tsp1 ON tsp1.taxonID = th.parent_1_ID
                         LEFT JOIN tbl_tax_epithets tep1 ON tep1.epithetID = tsp1.speciesID
                         LEFT JOIN tbl_tax_genera tgp1 ON tgp1.genID = tsp1.genID
                         LEFT JOIN tbl_tax_species tsp2 ON tsp2.taxonID = th.parent_2_ID
                         LEFT JOIN tbl_tax_epithets tep2 ON tep2.epithetID = tsp2.speciesID
                         LEFT JOIN tbl_tax_genera tgp2 ON tgp2.genID = tsp2.genID
                         LEFT JOIN tbl_tax_epithets te ON te.epithetID = ts.speciesID
                         LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID = ts.subspeciesID
                         LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID = ts.varietyID
                         LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID = ts.subvarietyID
                         LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID = ts.formaID
                         LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID = ts.subformaID
                        WHERE th.taxon_ID_fk = ts.taxonID
                         AND (   tg.genus {$equ} ?
                              OR tgp1.genus {$equ} ?
                              OR tgp2.genus {$equ} ? ) ";
                $param = array($pieces[0], $pieces[0], $pieces[0]);


                if ($noExternals) {
                    $sql.=" AND ts.external = 0 ";
                }
                if (!empty($pieces[1])) {
                    $sql .= " AND (   tep1.epithet {$equ} ?
						           OR tep2.epithet {$equ} ? )";
                    $param[] = $pieces[1];
                    $param[] = $pieces[1];
                }
                $sql .= "ORDER BY tg.genus, tep1.epithet, tgp2.genus, tep2.epithet";
                /* @var $dbst PDOStatement */
                //$dbst=0;return array(array('id'=>'1','label'=>($dbst?'Y':'N').$sql,'value'=>($dbst?'Y':'N').$sql));

                $dbst = $db->prepare($sql);
                $dbst->execute($param);
                $rows = $dbst->fetchAll();
                if (count($rows) > 0) {
                    foreach ($rows as $row) {
                        $results[] = array(
                            'id' => $row['taxonID'],
                            'label' => $display->taxon($row['taxonID'], true, false, true),
                            'value' => $display->taxon($row['taxonID'], true, false, true),
                            'color' => ($row['synID']) ? 'red' : ''
                        );
                    }
                }
                foreach ($results as $k => $v) {   // eliminate multiple whitespaces within the result
                    $results[$k]['label'] = preg_replace("/ [\s]+/", " ", $v['label']);
                    $results[$k]['value'] = preg_replace("/ [\s]+/", " ", $v['value']);
                }
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
        }

        return $results;
    }

    /** W
     * autocomplete a taxon entry field without external entries(external=0)
     * If the searchstring has only one part before the separator only taxa with empty species are presented.
     * If the searchstring consists of two parts the first one is used for genus, the second one for species
     *
     * @param string $value text to search for
     * @param bool[optional] $noExternals only results for "external=0"(default no)
     * @return array data array ready to send to jQuery-autocomplete via json-encode
     */
    public function taxonWithHybridsNoExternals($value)
    {
        return $this->taxonWithHybrids($value, true);
    }

    /** W
     * autocomplete a series entry field
     *
     * @param string $value text to search for
     * @return array data array ready to send to jQuery-autocomplete via json-encode
     */
    public function series($value)
    {
        $results = array();
        try {
            $db = clsDbAccess::Connect('INPUT');
            $sql = "SELECT series, seriesID
                    FROM tbl_specimens_series
                    WHERE ";
            if (isset($value['id'])) {
                $dbst = $db->prepare($sql . " seriesID = ?");
                $dbst->execute(array($value['id']));
            } else if (isset($value['exact'])) {
                $dbst = $db->prepare($sql . " series = ?");
                $dbst->execute(array($value['exact']));
            } else {
                $dbst = $db->prepare($sql . " series LIKE ? ORDER BY series");
                $dbst->execute(array($value['search'] . '%'));
            }

            $rows = $dbst->fetchAll();
            if (count($rows) > 0) {
                foreach ($rows as $row) {
                    $results[] = array(
                        'id' => $row['seriesID'],
                        'label' => $row['series'] . " <" . $row['seriesID'] . ">",
                        'value' => $row['series'] . " <" . $row['seriesID'] . ">",
                        'color' => '');
                }
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
        }

        return $results;
    }

    /**
     * Auto-completer for taxon but limited to a certain citation
     * @param string $value taxon name to search for
     */
    public function taxonCitation($value)
    {
        $citationID = intval($_GET['citationID']);
        $results = array();
        $citationIDs = (isset($_GET['includeParents'])) ? $this->findParents($citationID) : array($citationID);
        $bChild = (isset($_GET['child'])) ? true : false;

        // Check if a valid citation was passed
        if ($citationID > 0) {
            $db = clsDbAccess::Connect('INPUT');

            // Find all taxon name IDs for the current citation
            // but do not use already assigned ones
            $extraCondition = "
                AND ts.`taxonID` IN (
                SELECT ts.`taxonID`
                FROM `tbl_tax_synonymy` ts"
                .(($bChild) ? " LEFT JOIN `tbl_tax_classification` tc ON tc.`tax_syn_ID` = ts.`tax_syn_ID`" : "").
                " WHERE
                ts.`acc_taxon_ID` IS NULL"
                .(($bChild) ? " AND tc.`classification_id` IS NULL" : "").
                " AND
                ts.`source_citationID` IN ( " . implode(', ', $citationIDs) . " )
                )
            ";

            // Do the actual taxon name matching
            $results = $this->taxon($value,false,false,true,$extraCondition);
        }

        // Return all matching entries
        return $results;
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

    private function __clone()
    {
    }


    /**
     * Find all parent citations for a given citationID
     * @param int $p_citationID ID of citation to look for
     * @return array list of citation IDs (including the passed one)
     */
    private function findParents ($p_citationID)
    {
        $p_citationID_filter = intval($p_citationID);
        $results = array($p_citationID_filter);

        $db = clsDbAccess::Connect('INPUT');

        // Find the parent(s) for a citation
        $dbst = $db->query("SELECT `citation_parent_ID`
                            FROM `tbl_lit_container`
                            WHERE `citation_child_ID` = '$p_citationID_filter'");
        $rows = $dbst->fetchAll();

        // Check if we found something and fetch the results
        if( count($rows) > 0 ) {
            foreach( $rows as $row ) {
                $results = array_merge($results,$this->findParents( $row['citation_parent_ID'] ));
            }
        }

        return $results;
    }
}
