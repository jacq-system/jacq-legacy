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
if (isset($_OPTIONS['tcpdf'])) {
    require_once('inc/tcpdf_' . strtr($_OPTIONS['tcpdf'], '.', '_') . '/tcpdf.php');
} else {
    require_once('inc/tcpdf_6_3_2/tcpdf.php');
}
// BP, 08/2010
//if ($_OPTIONS['tcpdf_5_8']) {
//    require_once('inc/tcpdf_5_8_001/config/lang/eng.php');
//    require_once('inc/tcpdf_5_8_001/tcpdf.php');
//    //error_log("TCPDF 5.8",0);
//} else {
//    require_once('inc/tcpdf/config/lang/eng.php');
//    require_once('inc/tcpdf/tcpdf.php');
//    //error_log("TCPDF 4.5",0);
//}

function makeText($id, $sub)  {

  $sql = "SELECT tt.typus_lat, tg.genus,
           ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
           ta4.author author4, ta5.author author5,
           te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
           te4.epithet epithet4, te5.epithet epithet5,
           ts.synID, ts.taxonID,
           tg.DallaTorreIDs, tg.DallaTorreZusatzIDs,
           mc.coll_short_prj,
           md.supplier_organisation, md.supplier_adress, md.supplier_url
          FROM (tbl_specimens_types tst, tbl_typi tt, tbl_tax_species ts, tbl_specimens s, tbl_management_collections mc, herbarinput.metadb md)
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
           AND md.source_id_fk=mc.source_id
           AND tst.specimenID='".intval($id) . "'
           LIMIT $sub,1
  ";
  $result = dbi_query($sql);
  if (mysqli_num_rows($result)>0) {
    $row = mysqli_fetch_array($result);

    $text['typus_lat'] = utf8_decode($row['typus_lat']);
    $text['taxon'] = taxonWithHybrids($row, true);
    $text['DT'] = $row['DallaTorreIDs'].$row['DallaTorreZusatzIDs'];
    $text['coll_short'] = strtoupper($row['coll_short_prj']);

    $supplier_organisation_parts = explode( ", ", $row['supplier_organisation'] );  // split() is depricated as of PHP 5.3.0
    $text['supplier_organisation'] = "";
    foreach( $supplier_organisation_parts as $supplier_organisation_part ) {
        $text['supplier_organisation'] = $supplier_organisation_part . "\n" . $text['supplier_organisation'];
    }

    // Prepare the supplier URL for use in a label
    $matches = array();
    if( preg_match( '/http:\/\/(.*)\//i', $row['supplier_url'], $matches ) > 0 ) {
        $row['supplier_url'] = $matches[1];
    }

    //$text['supplier_organisation'] = $row['supplier_organisation'];
    $text['supplier_address'] = str_replace( ", ", "\n", $row['supplier_adress'] );
    $text['supplier_address'] .= "\n" . $row['supplier_url'];

    if ($row['synID']) {
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
               WHERE taxonID=".$row['synID'];
      $result3 = dbi_query($sql3);
      $row3 = mysqli_fetch_array($result3);
      // BP, 08/2010: TODO: taxonWithHybrids expects $row['statusID'], but $row does not contain it. Problem???
      $text['accName'] = "Annotationen: = ".taxonWithHybrids($row3);
    }
    else
      $text['accName'] = "Annotationen: ";

    $sql2 = "SELECT l.suptitel, la.autor, l.periodicalID, lp.periodical, l.vol, l.part,
              ti.paginae, ti.figures, l.jahr
             FROM tbl_tax_index ti
              INNER JOIN tbl_lit l ON l.citationID=ti.citationID
              LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID=l.periodicalID
              LEFT JOIN tbl_lit_authors la ON la.autorID=l.editorsID
             WHERE ti.taxonID='".$row['taxonID']."'";
    $result2 = dbi_query($sql2);
    $text['protolog'] = "";
    while ($row2=mysqli_fetch_array($result2))
      $text['protolog'] .= protolog($row2)."\n";
    $text['protolog'] = substr($text['protolog'],0,-1);
  }
  else
    $text = array();

  //error_log(var_export($text,true),0);
  return $text;
}

// BP, 08/2010: changes for TCPDF PHP 5
//class LABEL extends TAGFPDF {
class LABEL extends TCPDF {
  var $offset;
  var $pageX;
  var $pageY;
  var $sizeX;
  var $sizeY;
  var $offX;
  var $offY;
  var $labelID;
  var $makeNewPage;

  // BP, 08/2010: changes for TCPDF PHP 5
  //function LABEL($orient='L') {
  function __construct($orient='L') {
    //parent::FPDF($orient);
    parent::__construct($orient);
    $this->offset = 5;
    $this->pageX = 297;
    $this->pageY = 210;
    $this->sizeX = $this->pageX / 2;
    $this->sizeY = $this->pageY / 2;
    $this->labelID = 0;
    $this->makeNewPage = true;
  }
  function NewPage($orient='L') {
    $this->AddPage($orient);
    $this->Line($this->offset,$this->pageY/2,$this->offset+10,$this->pageY/2);
    $this->Line($this->pageX/2-$this->offset,$this->pageY/2,$this->pageX/2+$this->offset,$this->pageY/2);
    $this->Line($this->pageX-$this->offset-10,$this->pageY/2,$this->pageX-$this->offset,$this->pageY/2);
    $this->Line($this->pageX/2,$this->offset,$this->pageX/2,$this->offset+10);
    $this->Line($this->pageX/2,$this->pageY/2-$this->offset,$this->pageX/2,$this->pageY/2+$this->offset);
    $this->Line($this->pageX/2,$this->pageY-$this->offset-10,$this->pageX/2,$this->pageY-$this->offset);
  }
  function NextSubLabel() {
    $this->labelID++;
    if ($this->labelID>3) {
      $this->labelID = 0;
      $this->makeNewPage = true;
    }
    else {
      $this->SetLabel();
    }
  }
  function SetLabel() {
    switch ($this->labelID) {
      case 1:
        $this->offX = $this->pageX / 2;
        $this->offY = 0;
        break;
      case 2:
        $this->offX = 0;
        $this->offY = $this->pageY / 2;
        break;
      case 3:
        $this->offX = $this->pageX / 2;
        $this->offY = $this->pageY / 2;
        break;
      default:
        $this->offX = 0;
        $this->offY = 0;
    }
    $this->SetMargins($this->offX+$this->offset,$this->offY+$this->offset,$this->pageX/2-$this->offX+$this->offset);
    $this->SetXYoff(0,0);
  }
  function SetXYoff($x, $y) {
    $this->SetXY($this->offX+$this->offset+$x, $this->offY+$this->offset+$y);
  }
  function HLineOff($xa,$xe,$y) {
    $x1  = $this->offX+$this->offset+$xa;
    $x2  = $this->offX+$this->offset+$xe;
    $y12 = $this->offY+$this->offset+$y;
    $this->Line($x1,$y12,$x2,$y12);
  }
  function activateNewPage() {
    if ($this->makeNewPage) {
      $this->NewPage();
      $this->SetLabel();
      $this->makeNewPage = false;
    }
  }
  function WriteHeader($coll_short, $supplier_organisation, $supplier_address) {
    $this->SetFont('','',10);
    $this->SetXYoff(0,0);
    //$this->MultiCell(60,4.2,"Fakultätszentrum Botanik\nInstitut für Botanik\nUniversität Wien");
    $this->MultiCell(60,4.2,$supplier_organisation);
    $this->SetXYoff(80,0);
    //$this->MultiCell(58.5,4.2,"Rennweg 14\nA-1030 Wien, Österreich\nherbarium.univie.ac.at",0,'R');
    $this->MultiCell(58.5,4.2,$supplier_address,0,'R');
    $this->SetFont('','B',16);
    $this->SetXYoff(40,5);
    $this->MultiCell(58.5,4.2,"Herbarium ".$coll_short,0,'C');
    $this->SetFont('','',10);
    $this->HLineOff(0,138.5,15);
    $this->HLineOff(0,138.5,70);
  }

  // BP, 08/2010:
  function MultiCell($w, $h, $txt, $border=0, $align='L', $fill=false, $ln=1, $x = '', $y = '', $reseth = true, $stretch = 0, $ishtml = false, $autopadding = true, $maxh = 0, $valign = 'T', $fitcell = false) {
      // $autopadding needs to be false (default=true if $isHtml is false)
      /*if (!$this->abort)*/ parent::MultiCell($w, $h, $txt, $border, $align, $fill, $ln, '', '', true, 0, false, false, $maxh, $valign, $fitcell);
  }


  // BP, 08/2010: add methods to simulate TAGFPDF
  function WriteTag($w,$h,$txt,$border=0,$align="J") {
      // set $isHtml to true
    parent::MultiCell($w, $h, $txt, $border, $align, 0, 1,'','',true,0,true,false);
  }

  function SetStyle($tag,$family,$style,$size,$color,$indent=-1) {
      $this->SetFont($family,$style,$size);
  }

  public function SetFont($family, $style='', $size=NULL, $fontfile='', $subset = 'default', $out = true) {
    if (($family == 'Arial') || ($family == 'PaddingtonSC')) {
        // We haven't got these fonts --> use 'freesans' instead
        $family='freesans';
    } else if ($family == 'Times') {
        $family = 'freeserif';
    }
    parent::SetFont($family, $style, $size, $fontfile, $subset, $out);
  }

  // This is an attempt to somehow emulate the SetStyle()-function
  // by encapsulating all <i></i> and <small></small>-tags with <b></b>
  // Still, the result is not exactly the same as with SetStyle(). Example:
  // "Annotationen: = Acaena fuscescens BITTER":
  //         with FPDF, "Annotation" is with serif, the rest without serif (Arial).
  //         I did not simulate this behaviour, it seemed too complicated...
  //         If it turns out to be necessary, I'll do it...
  public function AddB2Tags($str) {
      $strOut = "";
      $i = 0;
      while ($i < strlen($str)) {
          if (substr($str,$i,3) == "<i>") {
              $strOut .= "<i><b>";
              $i += 3;
          } else if (substr($str,$i,4) == "</i>") {
              $strOut .= "</b></i>";
              $i += 4;
          } else if (substr($str,$i,7) == "<small>") {
              $strOut .= "<small><b>";
              $i += 7;
          } else if (substr($str,$i,8) == "</small>") {
              $strOut .= "</b></small>";
              $i += 8;
          } else {
              $strOut .= $str[$i];
              $i++;
          }
      }
      //error_log("strOut = " . $strOut,0);
      return $strOut;
  }
}

$pdf=new LABEL();
$pdf->SetAutoPageBreak("off");
$pdf->SetPrintHeader(false);            // BP, 08/2010: we don't want a line on top of the page
$pdf->Open();

// BP, 08/2010: we don't have PaddingtonSC for tcpdf --> don't add, we will use Arial instead
//$pdf->AddFont('PaddingtonSC','','PaddingtonSC.php');
//$pdf->AddFont('PaddingtonSC','B','paddingtonscbold.php');

$pdf->SetFont('Arial','',10);
$pdf->SetCellHeightRatio(1.0);     // BP, 08/2010: there was too much space between the lines (? default changed to 1.25 ?)

$result_ID = dbi_query("SELECT specimen_ID, label FROM tbl_labels WHERE (label&1)>'0' AND userID='".$_SESSION['uid']."'");
while ($row_ID=mysqli_fetch_array($result_ID)) {
  $subCounter = 0;
  while ($subCounter >= 0) {
      $labelText = makeText($row_ID['specimen_ID'],$subCounter);
      if (count($labelText)>0) {
        $pdf->activateNewPage();

        $pdf->WriteHeader($labelText['coll_short'], $labelText['supplier_organisation'], $labelText['supplier_address']);

        $pdf->SetFont('','B',14);
        $pdf->SetXYoff(0,25);
        $pdf->SetTextColor(255,0,0);
        $pdf->Cell(138.5,0,$labelText['typus_lat'],0,0,'C');
        $pdf->SetTextColor(0);

        // BP, 08/2010: <ii> and <sm> should not occur when 'TCPDF' is set
        //     so I guess I can ignore them. (see "pdf_functions.php" and search for "ii" or "sm")
        //     What I do need: when something is between <i></i> and <small></small>,
        //     it should be printed bold ==> add <b></b>!
        /* $pdf->SetStyle("p","Arial","B",14,"0,0,0",0);
        $pdf->SetStyle("ii","Arial","BI",14,"0,0,0");
        $pdf->SetStyle("sm","PaddingtonSC","B",14,"0,0,0");
        $pdf->SetStyle("sm","Arial","B",14,"0,0,0");*/
        // BP 08/2010: I admit it: it doesn't seem to make sense to add <b></b> tags
        //             when we choose a bold font anyway. But I think this is the way
        //             it was done previously (see SetStyle()-statements above: everything is bold)
        //             so let's keep it for a while. In case of performance problems, we can
        //             always remove it...
        $str = $pdf->AddB2Tags($labelText['taxon']);
        $pdf->SetFont('Arial','B',14);
        $pdf->SetXYoff(0,35);
        //$pdf->WriteTag(138.5,4.0,$labelText['taxon'],0,'C');
        $pdf->WriteTag(138.5,4.0,$str,0,'C');

        $pdf->SetFont('Times','',12);
        $pdf->SetXYoff(8,52.5);
        $pdf->MultiCell(122.5,5,$labelText['protolog']);

        // BP, 08/2010: <ii> and <sm> should not occur when 'TCPDF' is set
        //     so I guess I can ignore them. (see "pdf_functions.php" and search for "ii" or "sm")
        //     What I do need: when something is between <i></i> and <small></small>,
        //     it should be printed bold ==> add <b></b>!
        //$pdf->SetStyle("p","Arial","B",10,"0,0,0",0);
        //$pdf->SetStyle("ii","Arial","BI",10,"0,0,0");
        //$pdf->SetStyle("sm","PaddingtonSC","B",10,"0,0,0");
        $str = $pdf->AddB2Tags($labelText['accName']);      // BP 08/2010: add <b></b> where needed...
        $pdf->SetFont('Arial','',10);
        $pdf->SetXYoff(0,71);
        //$pdf->WriteTag(138.5,4.2,$labelText['accName'],0,'L');
        $pdf->WriteTag(138.5,4.2,$str,0,'L');

        // 2011-02-08: We don't want the extra numbers on herbarium W, dirty hack for now...
        if( $labelText['coll_short'] != 'W' ) {
            $pdf->SetFont('Arial','',10);
            $pdf->SetXYoff(0,90);
            $pdf->Cell(138.5,4.2,$labelText['DT']);
        }

        $pdf->NextSubLabel();
        $subCounter++;
      }
      else {
          break;
      }
  }
}

$pdf->Output();
?>