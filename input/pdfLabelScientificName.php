<?php
//ini_set('memory_limit', '32M');
ini_set("max_execution_time","3600");
$check = filter_input(INPUT_SERVER, 'HTTP_USER_AGENT');
if (strpos($check, "MSIE") && strrpos($check,")") == strlen($check) - 1) {
  session_cache_limiter('none');
}

session_start();
require("inc/connect7.php");

define('TCPDF','1');
require_once('inc/tcpdf_6_3_2/tcpdf.php');


/**
 * make the text elements to show on the scientific name label of a given taxon
 *
 * @param int $id the taxon-ID
 * @return array the lines of the scientific name and the uuid
 */
function makeText($id, $uuid)
{
    global $dbLink;

    $text['scientificName1'] = $text['scientificName2'] = "";
    $text['uuid'] = "https://resolv.jacq.org/$uuid";

    $dbLink->query("CALL herbar_view.GetScientificNameComponents($id,@genericEpithet,@specificEpithet,@infraspecificRank,@infraspecificEpithet,@author)");
    // execute the second query to get values from OUT parameter
    $res = $dbLink->query("SELECT @genericEpithet,@specificEpithet,@infraspecificRank,@infraspecificEpithet,@author");
    $row = $res->fetch_assoc();
    if ($row) {
        $text['scientificName1'] = $row['@genericEpithet'] . " " . $row['@specificEpithet'];
        if ($row['@infraspecificEpithet']) {
            $text['scientificName2'] = $row['@infraspecificRank'] . " " . $row['@infraspecificEpithet']  . " " . $row['@author'];
        } else {
            $text['scientificName1'] .= $row['@author'];
        }
    }

    return $text;
}


class LABEL extends TCPDF
{
    private $rowsPerPage;   // label rows per page
    private $colsPerPage;   // label colums per page
    private $labelsPerPage; // labels per page = $this->rowsPerPage * $this->colsPerPage
    private $colwidth;      // columns width

    private $QRsize;        // size of QRCode
    private $QRborder;      // border around QRCode
    private $QRstyle;       // style for QRCode

    private $col;           // current column
    private $ymax;          // max y

    /**
     * class constructor
     */
    public function __construct()
    {
        $this->setQRLabelSettings();
        $this->setQRStyle();

        parent::__construct();
    }

    /**
     * setter function for label page settings
     *
     * @param int $rowsPerPage label rows per page (default 18)
     * @param int $colsPerPage number of columns (default 3)
     * @param int $colwidth columns width (default 60)
     * @param int $QRsize size of QRCode (default 12)
     * @param int $QRborder border around QRCode (default 2)
     */
    public function setQRLabelSettings($rowsPerPage = 18, $colsPerPage = 2, $colwidth = 90, $QRsize = 12, $QRborder = 2)
    {
        $this->rowsPerPage   = $rowsPerPage;
        $this->colsPerPage   = $colsPerPage;
        $this->labelsPerPage = $this->rowsPerPage * $this->colsPerPage;
        $this->colwidth      = $colwidth;
        $this->QRsize        = $QRsize;
        $this->QRborder      = $QRborder;
    }

    /**
     * setter function for QRCode style attribute
     *
     * @param array $newstyle style for QRCode
     */
    public function setQRStyle($newstyle = null)
    {
        if (!empty($newstyle)) {
            $this->QRstyle = $newstyle;
        } else {
            $this->QRstyle = array('border' => 1,
                                   'vpadding' => 'auto',
                                   'hpadding' => 'auto',
                                   'fgcolor' => array(0,0,0),    //array(255,255,255)
                                   'bgcolor' => false,
                                   'module_width' => 1,          // width of a single module in points
                                   'module_height' => 1          // height of a single module in points
                                  );
        }
    }

    /**
     * getter function for labelsPerPage
     *
     * @return int labels per page
     */
    public function getLabelsPerPage()
    {
        return $this->labelsPerPage;
    }

    /**
     * Set position at a given column
     *
     * @param int $col the column (starting at 0)
     */
    public function SetCol($col)
    {
        $this->col = $col;
        // space between columns
        if ($this->colsPerPage > 1) {
            $column_space = round((float)($this->w - $this->original_lMargin - $this->original_rMargin - ($this->colsPerPage * $this->colwidth)) / ($this->colsPerPage - 1));
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
     * overloaded function to fill 'ymax' and start with leftmost column
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
        $this->SetCol(0);   //start at first column
    }

    /**
     * makes a Label at current position
     *
     * @param array $labelText Label test (Herbarium, Collection, UnitID)
     */
    public function makeLabel ($labelText)
    {
        if ($this->GetY() > $this->ymax) {
            if($this->col < ($this->colsPerPage - 1)) {
                $this->SetCol($this->col + 1);  //Go to next column
            } else {
                $this->AddPage();
            }
        }
        $x_top = $this->GetX();
        $y_top = $this->GetY();
        $this->Cell(78, 0, $labelText['scientificName1'], 0, 1, 'L');
        $this->Cell(78, 0, $labelText['scientificName2'], 0, 1, 'L');
        $this->write2DBarcode($labelText['uuid'], 'QRCODE,H', $x_top + 76 + $this->QRborder, $y_top, $this->QRsize, $this->QRsize, $this->QRstyle, 'N');
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

/** @var mysqli_result $result_ID */
$result_ID = $dbLink->query("SELECT `taxonID`, `uuid` FROM `tbl_labels_scientificName` WHERE `userID` = '" . $_SESSION['uid'] . "'");
while ($row_ID = $result_ID->fetch_array()) {
    $labelText = makeText($row_ID['taxonID'], $row_ID['uuid']);
    $pdf->makeLabel($labelText);
}

$pdf->Output();