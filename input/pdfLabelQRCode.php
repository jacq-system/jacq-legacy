<?php
//ini_set('memory_limit', '32M');
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
    $sql = "SELECT s.specimen_ID, s.HerbNummer, m.QR_code_header, mc.collection
            FROM tbl_specimens s, tbl_management_collections mc, herbarinput.metadata m
            WHERE s.collectionID = mc.collectionID
             AND mc.source_id = m.MetadataID
             AND s.specimen_ID = '$id'";
    $result = db_query($sql);
    $row = mysql_fetch_array($result);

    $text['Herbarium']  = $row['QR_code_header'];
    $text['Collection'] = ($row['collection']) ? 'Herbarium ' . $row['collection'] : "";
    $text['UnitID']     = $row['HerbNummer'];
    $text['StblID']     = getStableIdentifier($row['specimen_ID']);

    return $text;
}

/**
 * make the text elements for a standard QR-Code label
 *
 * @param int $sourceID the source_id
 * @param int $collectionID the collectionID
 * @param int $number the number used for the UnitID
 * @return array the three lines of text (abbr, Herbarium, UnitID)
 */
function makePreText($sourceID, $collectionID, $number)
{
    $result_source = db_query("SELECT QR_code_header FROM herbarinput.metadata WHERE MetadataID = '$sourceID'");
    $row_source = mysql_fetch_array($result_source);
    $text['Herbarium'] = $row_source['QR_code_header'];

    $result_coll = db_query("SELECT collection FROM herbarinput.tbl_management_collections WHERE collectionID = '$collectionID'");
    $row_coll = mysql_fetch_array($result_coll);
    $text['Collection'] = ($row_coll['collection']) ? 'Herbarium ' . $row_coll['collection'] : "";

    $text['UnitID'] = $number;
    $text['StblID'] = makeStableIdentifier($sourceID, array(), $collectionID, $number);

    return $text;
}

class LABEL extends TCPDF
{
    // size of QRCode
    protected $QRsize = 12;

    // border around QRCode
    protected $QRborder = 2;

    // number of colums
    protected $ncols = 3;

    // columns width
    protected $colwidth = 60;

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
        $this->ymax = $this->getPageHeight() - $this->QRsize - 2 * $this->QRborder;
    }

    /**
     * makes a Label at current position
     *
     * @param array $labelText Label test (Herbarium, Collection, UnitID)
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
        $x_top = $this->GetX();
        $y_top = $this->GetY();
        $this->Cell(48, 0, $labelText['Herbarium'], 0, 1, 'L');
        $this->Cell(48, 0, $labelText['Collection'], 0, 1, 'L');
        $this->Cell(48, 0, $labelText['UnitID'], 0, 1, 'L');
        $this->write2DBarcode($labelText['StblID'], 'QRCODE,H', $x_top + 46 + $this->QRborder, $y_top, $this->QRsize, $this->QRsize, $this->style, 'N');
        $this->Ln();
    }
}


$pdf = new LABEL();
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetAutoPageBreak(false);
$pdf->SetMargins(5, 5);

$pdf->AddPage();

$pdf->SetFont('helvetica', '', 9);

if (empty($_POST['institution_QR'])) {
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
    $sourceID     = intval(abs($_POST['institution_QR']));
    $collectionID = intval($_POST['collection_QR']);

    $sql = "SELECT digits
            FROM tbl_labels_numbering
            WHERE replace_char IS NULL
             AND collectionID_fk IS NULL
             AND sourceID_fk = '$sourceID'";
    $result = db_query($sql);
    if (mysql_num_rows($result) > 0) {
        $row = mysql_fetch_array($result);
        $digits = $row['digits'];
    } else {
        $digits = 0;
    }

    preg_match('/\D/', strrev($_POST['start']), $matches, PREG_OFFSET_CAPTURE);
    if ($matches) {
        $preamble    = substr($_POST['start'], 0, -$matches[0][1]);
        $numberStart = substr($_POST['start'], -$matches[0][1]);
        $numberEnd   = substr($_POST['stop'], strlen($_POST['start']) - $matches[0][1]);
        $digits      = ($digits) ? $digits : $matches[0][1];
    } else {
        $preamble    = "";
        $numberStart = intval($_POST['start']);
        $numberEnd   = intval($_POST['stop']);
        $digits      = ($digits) ? $digits : strlen($_POST['start']);
    }

    for ($i = $numberStart; $i <= $numberEnd; $i++) {
        $labelText = makePreText($sourceID, $collectionID, $preamble . sprintf("%0{$digits}d", $i));
        if (count($labelText) > 0) {
            $pdf->makeLabel($labelText);
        }
    }
}

$pdf->Output();