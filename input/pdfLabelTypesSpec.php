<?php
//ini_set('memory_limit', '32M');
$check = $_SERVER['HTTP_USER_AGENT'];
if (strpos($check, "MSIE") && strrpos($check,")") == strlen($check) - 1) {
  session_cache_limiter('none');
}

session_start();
require("inc/connect.php");
require("inc/pdf_functions.php");

define('TCPDF','1');
if (isset($_OPTIONS['tcpdf']) && $_OPTIONS['tcpdf'] == '6.4.2') {
    require_once('inc/tcpdf_6_4_2/tcpdf.php');
} else {
    require_once('inc/tcpdf_6_3_2/tcpdf.php');
}
//// BP, 08/2010
//if ($_OPTIONS['tcpdf_5_8']) {
//    require_once('inc/tcpdf_5_8_001/config/lang/eng.php');
//    require_once('inc/tcpdf_5_8_001/tcpdf.php');
//    //error_log("TCPDF 5.8",0);
//} else {
//    require_once('inc/tcpdf/config/lang/eng.php');
//    require_once('inc/tcpdf/tcpdf.php');
//    //error_log("TCPDF 4.5",0);
//}

/**
 * Checks if a value exists in a multidimensional array
 *
 * @param mixed $needle the searched value (no array!!)
 * @param array $haystack the array
 * @return bool returns TRUE if needle is found in the array, FALSE otherwise
 */
function in_array_multi($needle, $haystack)
{
    $found = false;
    foreach ($haystack as $value) {
        if ((is_array($value) && in_array_multi($needle, $value)) || $value == $needle) {
            $found = true;
        }
    }
    return $found;
}

function generateSynonymsList($id)
{
    $id = intval($id);
    $synonymsList = array();

    $result = dbi_query("SELECT synID FROM tbl_tax_species WHERE taxonID = '$id'");
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_array($result);
        if (empty($row['synID'])) {
            $parentID = intval($id);
        } else {
            $parentID = $row['synID'];
        }

        $parent = dbi_query("SELECT taxonID, basID, synID FROM tbl_tax_species WHERE taxonID='$parentID'")->fetch_array();
        $synonymsList[] = array('layer'   => 1,
                                'taxonID' => $parent['taxonID'],
                                'synID'   => $parent['synID'],
                                'basID'   => $parent['basID']);

        $ord = "ORDER BY genus, epithet, author, epithet1, author1, epithet2, author2, epithet3, author3, epithet4, author4, epithet5, author5";
        $sql = "SELECT ts.taxonID, ts.synID, ts.basID, tg.genus,
                 ta.author, ta1.author author1, ta2.author author2, ta3.author author3, ta4.author author4, ta5.author author5,
                 te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3, te4.epithet epithet4, te5.epithet epithet5
                FROM tbl_tax_species ts
                 LEFT JOIN tbl_tax_authors ta ON ta.authorID =ts.authorID
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
                WHERE synID = '$parentID'";

        if (empty($parent['basID'])) {
            $result2 = dbi_query($sql . " AND basID = '$parentID'");
        } else {
            $result2 = dbi_query($sql . " AND (basID IS NULL OR basID = '$parentID') AND taxonID = '{$parent['basID']}'");
        }
        while ($second = mysqli_fetch_array($result2)) {
            $synonymsList[] = array('layer'   => 2,
                                    'taxonID' => $second['taxonID'],
                                    'synID'   => $second['synID'],
                                    'basID'   => $second['basID']);
            $result3 = dbi_query($sql . " AND basID = '{$second['taxonID']}' $ord");
            while ($third = mysqli_fetch_array($result3)) {
                $synonymsList[] = array('layer'   => 3,
                                        'taxonID' => $third['taxonID'],
                                        'synID'   => $third['synID'],
                                        'basID'   => $third['basID']);
            }
        }

        if (empty($parent['basID'])) {
            $result2 = dbi_query($sql . " AND basID IS NULL $ord");
        } else {
            $result2 = dbi_query($sql . " AND (basID IS NULL OR basID = '$parentID') AND taxonID != '{$parent['basID']}' $ord");
        }
        while ($second = mysqli_fetch_array($result2)) {
            $synonymsList[] = array('layer'   => 2,
                                    'taxonID' => $second['taxonID'],
                                    'synID'   => $second['synID'],
                                    'basID'   => $second['basID']);
            $result3 = dbi_query($sql . " AND basID = '{$second['taxonID']}' $ord");
            while ($third = mysqli_fetch_array($result3)) {
                $synonymsList[] = array('layer'   => 3,
                                        'taxonID' => $third['taxonID'],
                                        'synID'   => $third['synID'],
                                        'basID'   => $third['basID']);
            }
        }
    }

    return $synonymsList;
}

/**
 * generates the text-entries for a type-label
 *
 * @param int $id specimen-ID
 * @param int $sub sublabel counter
 * @return array generated texts
 */
function makeText($id, $sub)
{
    $sql = "SELECT tt.typus_lat, tt.typusID, tg.genus,
             ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
             ta4.author author4, ta5.author author5,
             te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
             te4.epithet epithet4, te5.epithet epithet5,
             ts.synID, ts.taxonID,
             tst.typified_Date, tst.typified_by_Person,
             mc.coll_short_prj,
             s.HerbNummer, s.series_number, s.Nummer, s.alt_number, s.Datum, s.taxonID AS taxonIDspecimens
            FROM (tbl_specimens_types tst, tbl_typi tt, tbl_tax_species ts, tbl_specimens s, tbl_management_collections mc)
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
             LEFT JOIN tbl_tax_genera tg ON tg.genID=ts.genID
            WHERE tst.typusID=tt.typusID
             AND tst.taxonID=ts.taxonID
             AND tst.specimenID=s.specimen_ID
             AND s.collectionID=mc.collectionID
             AND tst.specimenID='".intval($id)."'
            LIMIT $sub,1";
    $result = dbi_query($sql);
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_array($result);

        $text['typus_lat']  = $row['typus_lat'];
        // BP, 08/2010: TODO: taxonWithHybrids expects $row['statusID'], but $row does not contain it. Problem???
        $text['taxon']      = taxonWithHybrids($row, true);
        $text['coll_short'] = mb_strtoupper($row['coll_short_prj'], 'UTF-8');
        $text['HerbNummer'] = $row['HerbNummer'];

        // Check if typus is "no typus" (ID 34), then we need "annotavit"!
        ( $row['typusID'] == 34 ) ? $text['typified']   = "annotavit " : $text['typified']   = "typificavit ";
        $text['typified'] .= $row['typified_by_Person'] . " " . $row['typified_Date'];

        if ($row['synID']) {
            $synonyms = generateSynonymsList($row['synID']);
            if (in_array_multi($row['taxonIDspecimens'], $synonyms)) {
                $synID = $row['synID'];                 // taxonID in specimen points anywhere in the synonyms-cloud
            } else if (checkRight('specimensTypes')) {
                $synID = $row['taxonIDspecimens'];      // taxonID in specimen points somewhere else, user has right to print this label
            } else {
                return array('locked' => true);         // user has NOT the right to print this label -> abort
            }
            $sql3 = "SELECT tg.genus,
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
                      LEFT JOIN tbl_tax_genera tg ON tg.genID=ts.genID
                     WHERE taxonID = '$synID'";
            $row3 = dbi_query($sql3)->fetch_array();
            $text['accName'] = " = " . taxonWithHybrids($row3);
        } else {
            $text['accName'] = "";
        }

        $sql2 = "SELECT l.suptitel, la.autor, l.periodicalID, lp.periodical, l.vol, l.part,
                  ti.paginae, ti.figures, l.jahr
                 FROM tbl_tax_index ti
                  INNER JOIN tbl_lit l ON l.citationID=ti.citationID
                  LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID=l.periodicalID
                  LEFT JOIN tbl_lit_authors la ON la.autorID=l.editorsID
                 WHERE ti.taxonID = '" . $row['taxonID'] . "'";
        $result2 = dbi_query($sql2);
        $text['protolog'] = "";
        while ($row2 = mysqli_fetch_array($result2)) {
            $text['protolog'] .= protolog($row2) . "\n";
        }
        $text['protolog'] = substr($text['protolog'],0,-1);

        $sql2 = "SELECT c.Sammler, c2.Sammler_2, ss.series
                 FROM tbl_specimens s
                  LEFT JOIN tbl_specimens_series ss ON ss.seriesID=s.seriesID
                  LEFT JOIN tbl_collector c ON c.SammlerID=s.SammlerID
                  LEFT JOIN tbl_collector_2 c2 ON c2.Sammler_2ID=s.Sammler_2ID
                 WHERE specimen_ID = '".intval($id)."'";
        $row2 = dbi_query($sql2)->fetch_array();
        $text['collector'] = $row2['Sammler'];
        if (strstr($row2['Sammler_2'], "&") || strstr($row2['Sammler_2'], "et al.")) {
            $text['collector'] .= " et al.";
        } elseif ($row2['Sammler_2']) {
            $text['collector'] .= " & " . $row2['Sammler_2'];
        }
        if ($row['series_number']) {
            if ($row['Nummer']) $text['collector'] .= " " . $row['Nummer'];
            if ($row['alt_number'] && trim($row['alt_number']) != "s.n.") $text['collector'] .= " " . $row['alt_number'];
            if ($row2['series']) $text['collector'] .= " " . $row2['series'];
            $text['collector'] .= " " . $row['series_number'];
        } else {
            if ($row2['series']) $text['collector'] .= " " . $row2['series'];
            if ($row['Nummer']) $text['collector'] .= " " . $row['Nummer'];
            if ($row['alt_number']) $text['collector'] .= " " . $row['alt_number'];
            if (strstr($row['alt_number'],"s.n.")) $text['collector'] .= " [" . $row['Datum'] . "]";
        }
        $text['locked'] = false;
    } else {
        $text = array();
    }

    return $text;
}


class LABEL extends TCPDF
{
    var $offset;
    var $pageX;
    var $pageY;
    var $offX;
    var $offY;
    var $column;
    var $abort;

    // BP, 08/2010: changes for TCPDF PHP 5
    //function LABEL($orient='P')
    public function __construct($orient='P')
    {
        $this->offset = 5;
        $this->pageX = 210;
        $this->pageY = 297;
        $this->column = 0;
        $this->offX = 0;
        $this->offY = 0;
        $this->abort = false;
        // BP, 08/2010: changes for TCPDF PHP 5
        //parent::TCPDF($orient);
        parent::__construct($orient);
    }

    function SetFont($family, $style='', $size=0)
    {
       if (!$this->abort) parent::SetFont($family, $style, $size);
    }

    function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=0, $link='')
    {
        if (!$this->abort) parent::Cell($w, $h, $txt, $border, $ln, $align, $fill, $link);
    }

    // BP, 08/2010: changes for TCPDF PHP 5: the parameters of MultiCell have changed...
    function MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1)
    {
        //if (!$this->abort) parent::MultiCell($w, $h, $txt, $border, $align, $fill, $ln);
        /*if (!$this->abort)*/ parent::MultiCell($w, $h, $txt, $border, $align, $fill, $ln,'','',true,0,false,false);
    }

    function writeHTML($html, $ln=true, $fill=0)
    {
        if (!$this->abort) parent::writeHTML($html, $ln, $fill);
    }

    function SetColumn($col)
    {
        $this->column = $col;
        $this->offX = $col * $this->pageX / 2;
        $this->offY = 0;
        $this->SetMargins($this->offX + $this->offset, $this->offY + $this->offset, $this->pageX / 2 - $this->offX + $this->offset);
        $this->SetXYoff(0, 0);
    }

    function AcceptPageBreak()
    {
        $this->abort = true;
        return false;
    }

    function Header()
    {
        $this->Line($this->pageX / 2, $this->offset, $this->pageX / 2, $this->offset + 10);
        $this->Line($this->pageX / 2, $this->pageY / 2 - $this->offset, $this->pageX / 2, $this->pageY / 2 + $this->offset);
        $this->Line($this->pageX / 2, $this->pageY - $this->offset - 10, $this->pageX / 2, $this->pageY - $this->offset);
    }

    function NextLabel()
    {
        if ($this->GetY() > $this->pageY - (3 * $this->offset + 10)) {
            if ($this->column < 1) {
                // switch to the right column
                $this->SetColumn(1);
            } else {
                // switch to the left column
                $this->SetColumn(0);
                $this->AddPage();
            }
        } else {
            $this->offY = $this->GetY() + $this->offset;
        }
        $this->abort = false;
        $this->SetXYoff(0, 0);
    }

    function SetXYoff($x, $y)
    {
        $this->SetXY($this->offX + $this->offset + $x, $this->offY + $this->offset + $y);
    }

    // BP: when is this function called ?
    function HLineOff($xa, $xe, $y)
    {
        $x1  = $this->offX + $this->offset + $xa;
        $x2  = $this->offX + $this->offset + $xe;
        $y12 = $this->offY + $this->offset + $y;
        $this->Line($x1, $y12, $x2, $y12);
    }
}


$pdf=new LABEL();
$pdf->Open();
$pdf->setPrintHeader(true);        // BP, 08/2010: set to true, so that trim marks are shown
$pdf->setPrintFooter(false);
$pdf->SetAutoPageBreak(1, 5);
$pdf->column = 1; // to force a new
$pdf->SetY(290);  // page at the Beginning

$pdf->SetFont('freesans','',10);
$pdf->SetCellHeightRatio(1.2);     // BP, 08/2010: there was too much space between the lines (? default changed to 1.25 ?)

$result_ID = dbi_query("SELECT specimen_ID, label FROM tbl_labels WHERE (label&2)>'0' AND userID='".$_SESSION['uid']."'");
while ($row_ID=mysqli_fetch_array($result_ID)) {
    $subCounter = 0;
    while ($subCounter >= 0) {
        $labelText = makeText($row_ID['specimen_ID'], $subCounter);
        if (count($labelText) > 0) {
            if (!$labelText['locked']) {
                do {
                    $pdf->NextLabel();

                    $pdf->SetFont('freesans', '', 10);
                    $pdf->SetXYoff(0, 0);
                    $pdf->SetTextColor(255, 0, 0);
                    $pdf->Cell(40, 4.4, $labelText['typus_lat']);
                    $pdf->SetTextColor(0);
                    $pdf->SetFont('freeserif', '', 10);
                    $pdf->Cell(0, 4.4, $labelText['coll_short'] . " " . $labelText['HerbNummer'], 0, 0, 'R');

                    $pdf->SetFont('freesans', '', 10);
                    $pdf->SetXYoff(0, 9);
                    $pdf->writeHTML("<b>" . $labelText['taxon'] . "</b>");
                    $pdf->Ln(0.5);

                    if ($labelText['protolog']) {
                        $pdf->SetFont('freeserif', '', 10);
                        $pdf->MultiCell(0, 4, $labelText['protolog'], 0, 'L');
                        $pdf->Ln(0.5);
                    }

                    if ($labelText['accName']) {
                        $pdf->Ln(2);
                        $pdf->SetFont('freesans', '', 9);
                        $pdf->writeHTML($labelText['accName']);
                    }

                    if ($labelText['typified']) {
                        $pdf->Ln(4);
                        $pdf->SetFont('freeserif', '', 10);
                        $pdf->MultiCell(0, 4, $labelText['typified'], 0, 'L');
                        $pdf->Ln(0.1);
                    }

                    $pdf->MultiCell(0, 4, $labelText['collector'], 0, 'R');
                } while ($pdf->abort);
            }
            $subCounter++;
        } else {
            $subCounter = -1;
        }
    }
}

$pdf->Output();