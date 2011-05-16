<?php
ini_set('memory_limit', '32M');
$check = $_SERVER['HTTP_USER_AGENT'];
if (strpos($check,"MSIE") && strrpos($check,")")==strlen($check)-1)
  session_cache_limiter('none');

session_start();
require("inc/connect.php");
require("inc/pdf_functions.php");

require_once("inc/variables.php");      // BP, 08/2010
global $_OPTIONS;

no_magic();

define('TCPDF','1');
// BP, 08/2010
if ($_OPTIONS['tcpdf_5_8']) {
    require_once('inc/tcpdf_5_8_001/config/lang/eng.php');
    require_once('inc/tcpdf_5_8_001/tcpdf.php');
    //error_log("TCPDF 5.8",0);
} else {
    require_once('inc/tcpdf/config/lang/eng.php');
    require_once('inc/tcpdf/tcpdf.php');
    //error_log("TCPDF 4.5",0);
}

function makeText($id)
{
    $sql = "SELECT s.specimen_ID, m.source_code, m.source_abbr_engl
            FROM tbl_specimens s, tbl_management_collections mc, herbarinput.meta m
            WHERE s.collectionID = mc.collectionID
             AND mc.source_id = m.source_id
             AND s.specimen_ID = '$id'";
    $result = db_query($sql);
    $row = mysql_fetch_array($result);

    $text['UnitID'] = formatUnitID($row['specimen_ID']);   // needs connect.php
    $text['abbr'] = $row['source_abbr_engl'];
    $text['Herbarium'] = 'Herbarium ' . $row['source_code'];

    return $text;
}

function makePreText($sourceID, $number)
{
    $sql = "SELECT source_code, source_abbr_engl
            FROM herbarinput.meta
            WHERE source_id = '" . intval($sourceID) . "'";
    $result = db_query($sql);
    $row = mysql_fetch_array($result);

    $text['UnitID'] = formatPreUnitID($sourceID, $number);   // needs connect.php
    $text['abbr'] = $row['source_abbr_engl'];
    $text['Herbarium'] = 'Herbarium ' . $row['source_code'];

    return $text;
}

class LABEL extends TCPDF
{
    //number of colums
    protected $ncols = 3;

    // columns width
    protected $colwidth = 57;

    //Current column
    protected $col = 0;

    //Ordinate of column start
    protected $y0;

    //Set position at a given column
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

    //Method accepting or not automatic page break
    public function AcceptPageBreak() {
        if($this->col < ($this->ncols - 1)) {
            //Go to next column
            $this->SetCol($this->col + 1);
            //Keep on page
            return false;
        } else {
            $this->AddPage();
            //Go back to first column
            $this->SetCol(0);
            //Page break
            return false;
        }
    }
}


$pdf = new LABEL();
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

//set margins
$pdf->SetMargins(10, PDF_MARGIN_TOP, 10);

//set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

$pdf->AddPage();

$pdf->SetFont('helvetica', '', 8);

$style = array(
    'position'    => 'S',
    'border'      => false,
    'padding'     => 0,
    'fgcolor'     => array(0, 0, 0),
    'bgcolor'     => false, //array(255,255,255),
    'text'        => true,
    'font'        => 'helvetica',
    'fontsize'    => 6,
    'stretchtext' => 4
);

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
    while ($row_ID=mysql_fetch_array($result_ID)) {
        $labelText = makeText($row_ID['specimen_ID']);
        if (count($labelText)>0) {
            $pdf->Cell(55, 0, $labelText['abbr'], 0, 1, 'C');
            $pdf->Cell(55, 0, $labelText['Herbarium'], 0, 1, 'C');
            $pdf->write1DBarcode($labelText['UnitID'], 'C39', '', '', 55, 15, 0.4, $style, 'N');
            $pdf->Ln();
        }
    }
} else {
    $sourceID    = intval(abs($_POST['collection']));
    $numberStart = intval($_POST['start']);
    $numberEnd   = intval($_POST['stop']);
    for ($i = $numberStart; $i <= $numberEnd; $i++) {
        $labelText = makePreText($sourceID, $i);
        if (count($labelText)>0) {
            $pdf->Cell(55, 0, $labelText['abbr'], 0, 1, 'C');
            $pdf->Cell(55, 0, $labelText['Herbarium'], 0, 1, 'C');
            $pdf->write1DBarcode($labelText['UnitID'], 'C39', '', '', 55, 15, 0.4, $style, 'N');
            $pdf->Ln();
        }
    }
}

$pdf->Output();