<?php
ini_set('memory_limit', '32M');
$check = $_SERVER['HTTP_USER_AGENT'];
if (strpos($check, "MSIE") && strrpos($check,")") == strlen($check) - 1) {
  session_cache_limiter('none');
}

session_start();
require("inc/connect.php");
require("inc/pdf_functions.php");
require_once 'inc/stableIdentifierFunctions.php';
no_magic();

define('TCPDF','1');
require_once('inc/tcpdf_6_3_2/tcpdf.php');

/**
 * make the text elements to show on the QR-Code label
 * if there is a stable identifier, get it as UnitID
 *
 * @param int $id the specimen-ID
 * @return array the three lines of text (abbr, Herbarium, UnitID)
 */
function makeText($id)
{
    $sql = "SELECT s.specimen_ID, m.source_code, m.source_abbr_engl
            FROM tbl_specimens s, tbl_management_collections mc, herbarinput.meta m
            WHERE s.collectionID = mc.collectionID
             AND mc.source_id = m.source_id
             AND s.specimen_ID = '$id'";
    $result = db_query($sql);
    $row = mysql_fetch_array($result);

    $text['abbr'] = $row['source_abbr_engl'];
    $text['Herbarium'] = 'Herbarium ' . $row['source_code'];
    $stblid = getStableIdentifier($row['specimen_ID']);
    $text['UnitID'] = ($stblid) ? $stblid : formatUnitID($row['specimen_ID']);   // needs connect.php

    return $text;
}

/**
 * make the text elements for a standard QR-Code label
 *
 * @param int $sourceID the source_id
 * @param int $number the number used for the UnitID
 * @return array the three lines of text (abbr, Herbarium, UnitID)
 */
function makePreText($sourceID, $number)
{
    $sql = "SELECT source_code, source_abbr_engl
            FROM herbarinput.meta
            WHERE source_id = '" . intval($sourceID) . "'";
    $result = db_query($sql);
    $row = mysql_fetch_array($result);

    $text['abbr'] = $row['source_abbr_engl'];
    $text['Herbarium'] = 'Herbarium ' . $row['source_code'];
    $text['UnitID'] = formatPreUnitID($sourceID, $number);   // needs connect.php

    return $text;
}

class LABEL extends TCPDF
{
    // size of QRCode
    protected $QRsize = 20;

    // border around QRCode
    protected $QRborder = 2;

    // number of colums
    protected $ncols = 2;

    // columns width
    protected $colwidth = 90;

    // current column
    protected $col = 0;

    // max y
    protected $ymax = 0;

    // set style for barcode
    protected $style = array(
                            'border' => 1,
                            'vpadding' => 'auto',
                            'hpadding' => 'auto',
                            'fgcolor' => array(0,0,0),
                            'bgcolor' => false, //array(255,255,255)
                            'module_width' => 1, // width of a single module in points
                            'module_height' => 1 // height of a single module in points
                            );

    /**
     * Set position at a given column
     *
     * @param int $col the column (starting at 0)
     */
    public function SetCol($col)
    {
        $this->col = $col;
        // space between columns
        if ($this->ncols > 1) {
            $column_space = round((float)($this->w - $this->original_lMargin - $this->original_rMargin - ($this->ncols * $this->colwidth)) / ($this->ncols - 1));
        } else {
            $column_space = 0;
        }

        // X position of the current column
        $x = $this->original_lMargin + ($col * ($this->colwidth + $column_space));
        $this->SetLeftMargin($x);
        $this->SetRightMargin($this->w - $x - $this->colwidth);
        $this->x = $x;

        if ($col > 0) {
            $this->SetY($this->tMargin);
        }
    }

    /**
     * overloaded function to fill 'ymax'
     *
     * @param string $orientation
     * @param mixed $format
     * @param bool $keepmargins
     * @param bool $tocpage
     */
    public function AddPage($orientation = '', $format = '', $keepmargins = false, $tocpage = false)
    {
        parent::AddPage($orientation, $format, $keepmargins, $tocpage);
        $this->ymax = $this->getPageHeight() - $this->QRsize - $this->QRborder;
    }

    /**
     * makes a Label at current position
     *
     * @param array $labelText Label test (abbr, Herbarium, UnitID)
     */
    public function makeLabel ($labelText)
    {
        if ($this->GetY() > $this->ymax) {
            if($this->col < ($this->ncols - 1)) {
                $this->SetCol($this->col + 1);  //Go to next column
            } else {
                $this->AddPage();
                $this->SetCol(0);               //Go back to first column
            }
        }
        $x = $this->GetX();
        $y = $this->GetY();
        $this->write2DBarcode($labelText['UnitID'], 'QRCODE,H', '', '', $this->QRsize, $this->QRsize, $this->style, 'N');
        $this->SetY($y);
        $this->SetX($x + $this->QRsize + $this->QRborder); $this->Cell(70, 0, $labelText['abbr'], 1, 1, 'L');
        $this->SetX($x + $this->QRsize + $this->QRborder); $this->Cell(70, 0, $labelText['Herbarium'], 1, 1, 'L');
        $this->SetX($x + $this->QRsize + $this->QRborder); $this->Cell(70, 0, $labelText['UnitID'], 1, 1, 'L');
        $this->SetY($y + $this->QRsize + $this->QRborder);
    }
}


$pdf = new LABEL();
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetAutoPageBreak(false);

$pdf->AddPage();

$pdf->SetFont('helvetica', '', 8);

if (empty($_POST['collection'])) {
    $sql = "SELECT s.specimen_ID, l.label
            FROM (tbl_specimens s, tbl_tax_species ts, tbl_tax_genera tg, tbl_management_collections mc)
             LEFT JOIN tbl_labels l ON (s.specimen_ID = l.specimen_ID AND l.userID = '".intval($_SESSION['uid'])."')
             LEFT JOIN tbl_specimens_series ss ON ss.seriesID = s.seriesID
             LEFT JOIN tbl_typi t ON t.typusID = s.typusID
             LEFT JOIN tbl_collector c ON c.SammlerID = s.SammlerID
             LEFT JOIN tbl_collector_2 c2 ON c2.Sammler_2ID = s.Sammler_2ID
             LEFT JOIN tbl_tax_authors ta ON ta.authorID = ts.authorID
             LEFT JOIN tbl_tax_epithets te ON te.epithetID = ts.speciesID
            WHERE ts.taxonID = s.taxonID
             AND tg.genID = ts.genID
             AND mc.collectionID = s.collectionID
             AND (l.label & 4) > '0'
             ORDER BY " . $_SESSION['labelOrder'];
    $result_ID = mysql_query($sql);
    //$result_ID = mysql_query("SELECT specimen_ID, label FROM tbl_labels WHERE (label&4)>'0' AND userID='".$_SESSION['uid']."'");
    while ($row_ID = mysql_fetch_array($result_ID)) {
        $labelText = makeText($row_ID['specimen_ID']);
        if (count($labelText) > 0) {
            $pdf->makeLabel($labelText);
        }
    }
} else {
    $sourceID    = intval(abs($_POST['collection']));
    $numberStart = intval($_POST['start']);
    $numberEnd   = intval($_POST['stop']);
    for ($i = $numberStart; $i <= $numberEnd; $i++) {
        $labelText = makePreText($sourceID, $i);
        if (count($labelText) > 0) {
            $pdf->makeLabel($labelText);
        }
    }
}

$pdf->Output();