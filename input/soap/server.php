<?php
ini_set("soap.wsdl_cache_enabled", 0);  // switch turn off during development

require_once( '../inc/variables.php' );

class Herbar
{

/*************\
|             |
|  variables  |
|             |
\*************/

private $host = "";     // hostname
private $db   = "";   // database
private $user = "";
private $pass = "";


/***************\
|               |
|  constructor  |
|               |
\***************/

public function __construct ()
{
    global $_CONFIG;
    
    $this->host = $_CONFIG['DATABASES']['INPUT']['host'];
    $this->db   = $_CONFIG['DATABASES']['INPUT']['db'];
    $this->user = $_CONFIG['DATABASES']['INPUT']['readonly']['user'];
    $this->pass = $_CONFIG['DATABASES']['INPUT']['readonly']['pass'];

    @mysql_connect($this->host, $this->user, $this->pass);
    @mysql_select_db($this->db);
    @mysql_query("SET character set utf8");
}


/*******************\
|                   |
|  public functions |
|                   |
\*******************/

public function getTaxon($search)
{
    $parts = explode(' ', trim($search));
    $ctr = 0;
    $genus = $species = $other = '';
    foreach ($parts as $part) {
        if (strlen(trim($part)) > 0) {
            switch ($ctr) {
                case 0:
                    $genus = $part;
                    $ctr++;
                    break;
                case 1:
                    $species = $part;
                    $ctr++;
                    break;
                case 2:
                    $other = $part;
                    $ctr++;
                    break;
                case 3:
                    $other .= ' ' . $part;
                    break;
            }
        }
    }

    $sql = "SELECT ts.taxonID, tg.genus,
             ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
             ta4.author author4, ta5.author author5,
             te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
             te4.epithet epithet4, te5.epithet epithet5
            FROM tbl_tax_species ts
             LEFT JOIN tbl_tax_authors ta ON ta.authorID=ts.authorID
             LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID=ts.subspecies_authorID
             LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID=ts.variety_authorID
             LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID=ts.subvariety_authorID
             LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID=ts.forma_authorID
             LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID=ts.subforma_authorID
             LEFT JOIN tbl_tax_epithets te ON te.epithetID=ts.speciesID
             LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID=ts.subspeciesID
             LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID=ts.varietyID
             LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID=ts.subvarietyID
             LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID=ts.formaID
             LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID=ts.subformaID
             LEFT JOIN tbl_tax_status tst ON tst.statusID=ts.statusID
             LEFT JOIN tbl_tax_genera tg ON tg.genID=ts.genID
            WHERE genus LIKE '" . mysql_escape_string($genus) . "%'
             AND ts.external = 0";
    if (strlen($species) > 0) {
        $sql .= " AND te.epithet LIKE '" . mysql_escape_string($species) . "%'";
    }
    if (strlen($other) > 0) {
        $sql .= " AND (te1.epithet LIKE '" . mysql_escape_string($other) . "%'
                    OR te2.epithet LIKE '" . mysql_escape_string($other) . "%'
                    OR te3.epithet LIKE '" . mysql_escape_string($other) . "%'
                    OR te4.epithet LIKE '" . mysql_escape_string($other) . "%'
                    OR te5.epithet LIKE '" . mysql_escape_string($other) . "%')";
    }
    $sql .= "ORDER BY genus, epithet, author, epithet1, author1, epithet2, author2, epithet3, author3, epithet4, author4, epithet5, author5";
    $result = mysql_query($sql);
    $return = "";
    while ($row = mysql_fetch_array($result)) {
        $return .= $row['taxonID'] . " " . $this->_taxon($row) . "\n";
    }

    return $return;

    //if (isset($this->quotes[$symbol])) {
    //    return $this->quotes[$symbol];
    //} else {
    //    throw new SoapFault("Client","Unknown Symbol '$symbol'.");
    //}
}

public function getSynonyms($id, $short)
{
    if ($short) {
        $short = true; // taxon and protolog on same line
    } else {
        $short = false; // taxon and protolog in 2 lines
    }

    $result = mysql_query("SELECT taxonID, synID FROM tbl_tax_species WHERE taxonID='".mysql_escape_string($id)."'");
    $row = mysql_fetch_array($result);
    if (!empty($row['synID'])) $id = $row['synID'];

    $order = " ORDER BY genus, epithet, author, epithet1, author1, epithet2, author2, epithet3, author3";

    $sql = "SELECT ts.taxonID, ts.basID, ts.synID, tg.genus, tg.DallaTorreIDs, tg.DallaTorreZusatzIDs, tst.status,
             ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
             ta4.author author4, ta5.author author5,
             te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
             te4.epithet epithet4, te5.epithet epithet5
            FROM tbl_tax_species ts
             LEFT JOIN tbl_tax_authors ta ON ta.authorID=ts.authorID
             LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID=ts.subspecies_authorID
             LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID=ts.variety_authorID
             LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID=ts.subvariety_authorID
             LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID=ts.forma_authorID
             LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID=ts.subforma_authorID
             LEFT JOIN tbl_tax_epithets te ON te.epithetID=ts.speciesID
             LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID=ts.subspeciesID
             LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID=ts.varietyID
             LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID=ts.subvarietyID
             LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID=ts.formaID
             LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID=ts.subformaID
             LEFT JOIN tbl_tax_status tst ON tst.statusID=ts.statusID
             LEFT JOIN tbl_tax_genera tg ON tg.genID=ts.genID
            WHERE taxonID='".mysql_escape_string($id)."'";
    $result = mysql_query($sql);

    $return = "";
    if (mysql_num_rows($result)>0) {
        $row = mysql_fetch_array($result);

        if ($short) {
            $return .= "<b>" . $this->_taxonList($row) . "</b>" . $this->_protologList($row['taxonID'],true) . "<br>\n";
        } else {
            $return .= "<b>" . $this->_taxonList($row) . "</b><br>\n" . $this->_protologList($row['taxonID']) . "<br>\n";
        }
        if (empty($row['synID']) && empty($row['basID'])) {
            $return .= $this->_typusList($row['taxonID'],false);
        }

        $tableStart = "<table cellspacing=\"0\" cellpadding=\"2\">";
        $sql = "SELECT ts.taxonID, tg.genus, tg.DallaTorreIDs, tg.DallaTorreZusatzIDs, tst.status,
                 ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
                 ta4.author author4, ta5.author author5,
                 te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
                 te4.epithet epithet4, te5.epithet epithet5
                FROM tbl_tax_species ts
                 LEFT JOIN tbl_tax_authors ta ON ta.authorID=ts.authorID
                 LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID=ts.subspecies_authorID
                 LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID=ts.variety_authorID
                 LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID=ts.subvariety_authorID
                 LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID=ts.forma_authorID
                 LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID=ts.subforma_authorID
                 LEFT JOIN tbl_tax_epithets te ON te.epithetID=ts.speciesID
                 LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID=ts.subspeciesID
                 LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID=ts.varietyID
                 LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID=ts.subvarietyID
                 LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID=ts.formaID
                 LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID=ts.subformaID
                 LEFT JOIN tbl_tax_status tst ON tst.statusID=ts.statusID
                 LEFT JOIN tbl_tax_genera tg ON tg.genID=ts.genID
                WHERE synID='".mysql_escape_string($id)."' ";
        if (empty($row['basID'])) {
            $result2 = mysql_query($sql."AND basID='".mysql_escape_string($id)."'");
        } else {
            $result2 = mysql_query($sql."AND (basID IS NULL OR basID='".mysql_escape_string($id)."') AND taxonID='".$row['basID']."'");
        }

        while ($row2 = mysql_fetch_array($result2)) {
            $return .= $tableStart;
            $return .= $this->_item(20,$row2,$short,"&equiv;");
            $return .= $this->_typusList($row2['taxonID'],true);
            $return .= "</table>\n";
            $result3 = mysql_query($sql."AND basID='".$row2['taxonID']."'".$order);
            while ($row3 = mysql_fetch_array($result3)) {
                $return .= $tableStart;
                $return .= $this->_item(40,$row3,$short,"&equiv;");
                $return .= "</table>\n";
            }
        }
        if (empty($row['basID'])) {
            $result2 = mysql_query($sql."AND basID IS NULL".$order);
        } else {
            $result2 = mysql_query($sql."AND (basID IS NULL OR basID='".mysql_escape_string($id)."') AND taxonID!='".$row['basID']."'".$order);
        }

        while ($row2 = mysql_fetch_array($result2)) {
            $return .= $tableStart;
            $return .= $this->_item(20,$row2,$short);
            $return .= $this->_typusList($row2['taxonID'],true);
            $return .= "</table>\n";
            $result3 = mysql_query($sql."AND basID='".$row2['taxonID']."'".$order);
            while ($row3 = mysql_fetch_array($result3)) {
                $return .= $tableStart;
                $return .= $this->_item(40,$row3,$short,"&equiv;");
                $return .= "</table>\n";
            }
        }
    } else {
        $return .= "<b>no data</b>\n";
    }

    return $return;
}


/********************\
|                    |
|  private functions |
|                    |
\********************/

private function _smallCaps($text)
{
    return "<span style=\"font-variant: small-caps\">".htmlspecialchars($text)."</span>";
}

private function _italics($text)
{
    return "<span style=\"font-style:italic\">".htmlspecialchars($text)."</span>";
}

private function _taxon($row)
{
    $text = $row['genus'];
    if ($row['epithet']) $text .= " " . $row['epithet'] . " " . $row['author'];
    if ($row['epithet1']) $text .= " subsp. " . $row['epithet1'] . " " . $row['author1'];
    if ($row['epithet2']) $text .= " var. " . $row['epithet2'] . " " . $row['author2'];
    if ($row['epithet3']) $text .= " subvar. " . $row['epithet3'] . " " . $row['author3'];
    if ($row['epithet4']) $text .= " forma " . $row['epithet4'] . " " . $row['author4'];
    if ($row['epithet5']) $text .= " subforma " . $row['epithet5'] . " " . $row['author5'];

    return $text;
}

private function _taxonList($row)
{
    $text = $this->_italics($row['genus']);
    if ($row['epithet']) {
        $text .= " ".$this->_italics($row['epithet']).htmlspecialchars(chr(194).chr(183))." ".$this->_smallCaps($row['author']);
    } else {
        $text .= htmlspecialchars(chr(194).chr(183));
    }
    if ($row['epithet1']) $text .= " subsp. ".$this->_italics($row['epithet1'])." ".$this->_smallCaps($row['author1']);
    if ($row['epithet2']) $text .= " var. ".$this->_italics($row['epithet2'])." ".$this->_smallCaps($row['author2']);
    if ($row['epithet3']) $text .= " subvar. ".$this->_italics($row['epithet3'])." ".$this->_smallCaps($row['author3']);
    if ($row['epithet4']) $text .= " forma ".$this->_italics($row['epithet4'])." ".$this->_smallCaps($row['author4']);
    if ($row['epithet5']) $text .= " subforma ".$this->_italics($row['epithet5'])." ".$this->_smallCaps($row['author5']);

    return $text;
}

private function _protologList($taxon,$short=false)
{
    $sql ="SELECT paginae, figures,
           l.suptitel, le.autor as editor, la.autor, l.periodicalID, lp.periodical,
           l.vol, l.part, l.jahr
          FROM tbl_tax_index ti
           LEFT JOIN tbl_lit l ON l.citationID=ti.citationID
           LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID=l.periodicalID
           LEFT JOIN tbl_lit_authors le ON le.autorID=l.editorsID
           LEFT JOIN tbl_lit_authors la ON la.autorID=l.autorID
          WHERE taxonID='".mysql_escape_string($taxon)."'";
    $result = mysql_query($sql);
    $display = "";
    if (mysql_num_rows($result)>0) {
        while ($row=mysql_fetch_array($result)) {
            $display = ($short) ? "" : $this->_smallCaps($row['autor'])." (".htmlspecialchars(substr($row['jahr'], 0, 4)).")";
            if ($row['suptitel']) $display .= " in ".htmlspecialchars($row['editor']).": ".htmlspecialchars($row['suptitel']);
            if ($row['periodicalID']) $display .= " ".htmlspecialchars($row['periodical']);
            $display .= " ".htmlspecialchars($row['vol']);
            if ($row['part']) $display .= " (".htmlspecialchars($row['part']).")";
            $display .= ": ".htmlspecialchars($row['paginae']).". ".htmlspecialchars($row['figures']);
            if ($short) $display .= " (".htmlspecialchars(substr($row['jahr'], 0, 4)).")";
        }
    } else if (!$short) {
        $display = "&mdash;";
    }

    return $display;
}

private function _typusList($taxon,$sw)
{
    $sql ="SELECT Sammler, Sammler_2, series, leg_nr, alternate_number, date, duplicates
           FROM (tbl_tax_typecollections tt, tbl_collector c)
            LEFT JOIN tbl_collector_2 c2 ON tt.Sammler_2ID=c2.Sammler_2ID
           WHERE tt.SammlerID=c.SammlerID
            AND taxonID='".mysql_escape_string($taxon)."'";
    $result = mysql_query($sql);
    $return = "";
    if (mysql_num_rows($result)>0) {
        while ($row=mysql_fetch_array($result)) {
            $display = $row['Sammler'];
            if ($row['Sammler_2']) {
                if (strstr($row['Sammler_2'],"&")===false) {
                    $display .= " & ".$row['Sammler_2'];
                } else {
                    $display .= " et al.";
                }
            }
            if ($row['series']) $display .= " ".$row['series'];
            if ($row['leg_nr']) $display .= " ".$row['leg_nr'];
            if ($row['alternate_number']) {
                $display .= " ".$row['alternate_number'];
                if (strstr($row['alternate_number'],"s.n.")!==false) {
                    $display .= " [".$row['date']."]";
                }
            }
            $display .= "; ".$row['duplicates'];
            if ($sw) $return .= "<tr><td colspan=\"2\"></td><td>";
            $return .= htmlspecialchars($display);
            if ($sw) {
                $return .= "</td></tr>\n";
            } else {
                $return .= "<br>\n";
            }
        }
    } else {
        if ($sw) {
            $return .= "<tr><td colspan=\"2\"></td><td>&mdash;</td></tr>\n";
        } else {
            $return .= "&mdash;<br>\n";
        }
    }

    return $return;
}

private function _item($offset, $row, $short, $sign="=")
{
    if ($short) {
        return "<tr><td width=\"$offset\">&nbsp;</td>"
             . "<td>$sign " . $row['status'] . "&nbsp;&nbsp;</td>"
             . "<td>" . $this->_taxonList($row) . $this->_protologList($row['taxonID'],true) . "</td></tr>\n";

    } else {
        return "<tr><td width=\"$offset\">&nbsp;</td>"
             . "<td>$sign " . $row['status'] . "&nbsp;&nbsp;</td>"
             . "<td>" . $this->_taxonList($row) . "</td></tr>\n"
             . "<tr><td colspan=\"2\"></td><td>" . $this->_protologList($row['taxonID']) . "</td></tr>\n";
    }
}

}

$server = new SoapServer("herbar.wsdl");
$server->setClass("Herbar");
$server->handle();