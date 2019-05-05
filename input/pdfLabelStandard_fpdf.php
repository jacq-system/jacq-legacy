<?php
$check = $_SERVER['HTTP_USER_AGENT'];
if (strpos($check,"MSIE") && strrpos($check,")")==strlen($check)-1)
  session_cache_limiter('none');

session_start();
require("inc/connect.php");
require("inc/pdf_functions.php");
no_magic();

define('FPDF_FONTPATH','inc/fpdf/font/');
require('inc/fpdf/fpdf.php');
require('inc/tagfpdf.php');

function makeText($id)  {

  $sql = "SELECT wu.specimen_ID, wu.HerbNummer, si.identification_status, wu.checked, wu.accessible,
           wu.taxonID, ss.series, wu.series_number, wu.Nummer, wu.alt_number, wu.Datum, wu.Datum2,
           wu.det, wu.typified, wu.taxon_alt, wu.Bezirk,
           wu.Coord_W, wu.W_Min, wu.W_Sec, wu.Coord_N, wu.N_Min, wu.N_Sec,
           wu.Coord_S, wu.S_Min, wu.S_Sec, wu.Coord_E, wu.E_Min, wu.E_Sec,
           wu.altitude_min, wu.altitude_max,
           wu.Fundort, wu.habitat, wu.habitus, wu.Bemerkungen,
           wu.garten, sv.voucher, wu.ncbi_accession,
           mc.collection, t.typus_lat, gn.nation_engl, gp.provinz,
           c.SammlerID, c.Sammler, c2.Sammler_2ID, c2.Sammler_2
          FROM (tbl_specimens wu, tbl_collector c)
           LEFT JOIN tbl_collector_2 c2 ON c2.Sammler_2ID=wu.Sammler_2ID
           LEFT JOIN tbl_management_collections mc ON mc.collectionID=wu.collectionID
           LEFT JOIN tbl_typi t ON t.typusID=wu.typusID
           LEFT JOIN tbl_specimens_identstatus si ON si.identstatusID=wu.identstatusID
           LEFT JOIN tbl_specimens_voucher sv ON sv.voucherID=wu.voucherID
           LEFT JOIN tbl_specimens_series ss ON ss.seriesID=wu.seriesID
           LEFT JOIN tbl_geo_nation gn ON gn.nationID=wu.nationID
           LEFT JOIN tbl_geo_province gp ON gp.provinceID=wu.provinceID
          WHERE wu.SammlerID=c.SammlerID
           AND specimen_ID='".intval($id)."'";
  $row = mysql_fetch_array(mysql_query($sql));

  $text['nation']      = utf8_decode($row['nation_engl']);
  $text['Fundort']     = utf8_decode($row['Fundort']);
  $text['habitat']     = utf8_decode($row['habitat']);
  $text['habitus']     = utf8_decode($row['habitus']);
  $text['Bemerkungen'] = utf8_decode($row['Bemerkungen']);
  $text['det']         = utf8_decode($row['det']);
  $text['coll1']       = utf8_decode($row['Sammler'])." ".$row['Nummer'];
  $text['coll2']       = ($row['Sammler_2']) ? utf8_decode($row['Sammler_2']) : "";

  if ($row['Coord_W'] || $row['W_Min'] || $row['W_Sec']) {
    $text['lonlat'] = intval($row['Coord_W']).chr(186);
    if ($row['W_Min'] || $row['W_Sec']) {
      $text['lonlat'] .= intval($row['W_Min'])."'";
      if ($row['W_Sec']) $text['lonlat'] .= $row['W_Sec']."\"";
    }
    $text['lonlat'] .= "W";
  }
  elseif ($row['Coord_E'] || $row['E_Min'] || $row['E_Sec']) {
    $text['lonlat'] = intval($row['Coord_E']).chr(186);
    if ($row['E_Min'] || $row['E_Sec']) {
      $text['lonlat'] .= intval($row['E_Min'])."'";
      if ($row['E_Sec']) $text['lonlat'] .= $row['E_Sec']."\"";
    }
    $text['lonlat'] .= "E";
  }
  else
    $text['lonlat'] = "";

  if ($row['Coord_S'] || $row['S_Min'] || $row['S_Sec']) {
    if (strlen($text['lonlat'])>0) $text['lonlat'] .= ", ";
    $text['lonlat'] .= intval($row['Coord_S']).chr(186);
    if ($row['S_Min'] || $row['S_Sec']) {
      $text['lonlat'] .= intval($row['S_Min'])."'";
      if ($row['S_Sec']) $text['lonlat'] .= $row['S_Sec']."\"";
    }
    $text['lonlat'] .= "S";
  }
  elseif ($row['Coord_N'] || $row['N_Min'] || $row['N_Sec']) {
    if (strlen($text['lonlat'])>0) $text['lonlat'] .= ", ";
    $text['lonlat'] .= intval($row['Coord_N']).chr(186);
    if ($row['N_Min'] || $row['N_Sec']) {
      $text['lonlat'] .= intval($row['N_Min'])."'";
      if ($row['N_Sec']) $text['lonlat'] .= $row['N_Sec']."\"";
    }
    $text['lonlat'] .= "N";
  }

  if ($row['altitude_min'] || $row['altitude_max']) {
    $text['alt'] = intval($row['altitude_min']);
    if ($row['altitude_max']) $text['alt'] .= "-".intval($row['altitude_max']);
    $text['alt'] .= " m";
  }

  if (trim($row['Datum'])) {
    $mon = array("Jan.", "Feb.", "Mar.", "Apr.", "May ", "Jun.", "Jul.", "Aug.", "Sep.", "Oct.", "Nov.", "Dec.");
    $pieces1 = explode("-",utf8_decode($row['Datum']));
    $pieces2 = explode("-",utf8_decode($row['Datum2']));
    if (trim($row['Datum2'])) {
      if (trim($pieces1[0])==trim($pieces2[0])) {
        if (trim($pieces1[1]) && trim($pieces2[1]) && trim($pieces1[1])==trim($pieces2[1])) {
          $text['date'] = trim($pieces1[2]).".-".
                          trim($pieces2[2]).".".
                          ((intval(trim($pieces1[1]))>0) ? $mon[intval(trim($pieces1[1]))-1] : trim($pieces1[1])).
                          $pieces1[0];
        }
        else {
          $text['date'] = "";
          if (trim($pieces1[2])) $text['date'] .= trim($pieces1[2]).".";
          $text['date'] .= ((intval(trim($pieces1[1]))>0) ? $mon[intval(trim($pieces1[1]))-1] : trim($pieces1[1]))."-";
          if (trim($pieces2[2])) $text['date'] .= trim($pieces2[2]).".";
          $text['date'] .= ((intval(trim($pieces2[1]))>0) ? $mon[intval(trim($pieces2[1]))-1] : trim($pieces2[1])).
                           $pieces1[0];
        }
      }
      else {
        $text['date'] = "";
        if (trim($pieces1[2])) $text['date'] .= trim($pieces1[2]).".";
        if (trim($pieces1[1])) $text['date'] .= ((intval(trim($pieces1[1]))>0) ? $mon[intval(trim($pieces1[1]))-1] : trim($pieces1[1]));
        $text['date'] .= $pieces1[0]."-";
        if (trim($pieces2[2])) $text['date'] .= trim($pieces2[2]).".";
        if (trim($pieces2[1])) $text['date'] .= ((intval(trim($pieces2[1]))>0) ? $mon[intval(trim($pieces2[1]))-1] : trim($pieces2[1]));
        $text['date'] .= $pieces2[0];
      }

    }
    else {
      $text['date'] = "";
      if (trim($pieces1[2])) $text['date'] .= trim($pieces1[2]).".";
      if (trim($pieces1[1])) $text['date'] .= ((intval(trim($pieces1[1]))>0) ? $mon[intval(trim($pieces1[1]))-1] : trim($pieces1[1]));
      $text['date'] .= $pieces1[0];
    }
    $concat = false;
  }

  $sql = "SELECT tg.genus, tf.family,
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
           LEFT JOIN tbl_tax_families tf ON tf.familyID=tg.familyID
          WHERE ts.taxonID='".mysql_escape_string($row['taxonID'])."'";
  $row = mysql_fetch_array(mysql_query($sql));

  $text['taxon'] = taxonWithHybrids($row, true);
  $text['family'] = $row['family'];

  return $text;
}


class LABEL extends TAGFPDF {
  var $offset;
  var $pageX;
  var $pageY;
  var $sizeX;
  var $sizeY;
  var $offX;
  var $offY;
  var $labelID;

  function LABEL($orient='L') {
    parent::FPDF($orient);
    $this->offset = 5;
    $this->pageX = 297;
    $this->pageY = 210;
    $this->sizeX = 110;
    $this->sizeY = 105;
    $this->labelID = 3; // to force a new page at the Beginning
    $this->makeNewPage = true;
  }
  function AcceptPageBreak() {
    if ($this->labelID<3) {
      //Go to next label
      $this->NextLabel();
      return false;
    }
    else {
      //Go back to first label and issue page break
      $this->labelID = 0;
      $this->SetLabel();
      return true;
    }
  }
  function Header() {
    $this->Line($this->sizeX,$this->pageY/2-$this->offset,$this->sizeX,$this->pageY/2+$this->offset);
    $this->Line($this->pageX-$this->sizeX,$this->pageY/2-$this->offset,$this->pageX-$this->sizeX,$this->pageY/2+$this->offset);
  }
  function NextLabel() {
    $this->labelID++;
    if ($this->labelID>3) {
      $this->labelID = 0;
      $this->SetLabel();
      $this->AddPage('L');
    }
    else  {
      $this->SetLabel();
    }
  }
  function SetLabel() {
    switch ($this->labelID) {
      case 1:
        $this->offX = 0;
        $this->offY = $this->pageY / 2;
        break;
      case 2:
        $this->offX = $this->pageX - $this->sizeX;
        $this->offY = 0;
        break;
      case 3:
        $this->offX = $this->pageX - $this->sizeX;
        $this->offY = $this->pageY / 2;
        break;
      default:
        $this->offX = 0;
        $this->offY = 0;
    }
    $this->SetXYoff(0,0);
    $this->SetMargins($this->offX+$this->offset,$this->offY+$this->offset,$this->pageX-($this->offX+$this->sizeX)+$this->offset);
    if ($this->labelID==0 || $this->labelID==2)
      $this->SetAutoPageBreak(1,110);
    else
      $this->SetAutoPageBreak(1,5);
  }
  function SetXYoff($x, $y) {
    $this->SetXY($this->offX+$this->offset+$x, $this->offY+$this->offset+$y);
  }
  function SetLeftMarginOff($margin) {
    $this->SetLeftMargin($this->offX+$this->offset+$margin);
  }
}



$pdf=new LABEL();
$pdf->SetAutoPageBreak("off");
$pdf->Open();

$pdf->AddFont('PaddingtonSC','','PaddingtonSC.php');
$pdf->AddFont('PaddingtonSC','B','paddingtonscbold.php');

$pdf->SetStyle("p","Arial","B",12,"0,0,0",0);
$pdf->SetStyle("ii","Arial","BI",12,"0,0,0");
$pdf->SetStyle("sm","PaddingtonSC","B",12,"0,0,0");

$pdf->SetFont('Arial','',10);

$result_ID = mysql_query("SELECT specimen_ID, label FROM tbl_specimens WHERE (label&240)>'0'");
while ($row_ID=mysql_fetch_array($result_ID)) {
  $ctr = ($row_ID['label'] & 0xf0) / 16;
  for ($i=0; $i<$ctr; $i++) {
    $labelText = makeText($row_ID['specimen_ID']);

    $pdf->NextLabel();

    $pdf->SetFont('Arial','B',14);
    $pdf->SetXYoff(0,0);
    $pdf->Cell(100,5,"Flora of ".$labelText['nation'],0,0,'C');

    $pdf->SetFont('Arial','',10);
    $pdf->SetXYoff(70,6);
    $pdf->Cell(30,4,$labelText['family'],0,0,'R');

    $pdf->SetXYoff(0,10);
    $pdf->WriteTag(100,4.5,$labelText['taxon']);

    $pdf->Ln(3);
    $pdf->SetFont('Times','',12);
    $lengthColl1 = $pdf->GetStringWidth($labelText['coll1']);
    $lengthDate  = $pdf->GetStringWidth($labelText['date']);
    if (($lengthColl1 + $lengthDate)>85)
      $seperateColl1Date = true;
    else
      $seperateColl1Date = false;

    if ($labelText['Fundort'])  {
      $pdf->MultiCell(100,4.3,$labelText['Fundort']);
      $pdf->Ln(3);
    }

    if ($labelText['lonlat'] || $labelText['alt']) {
      $pdf->Cell(50,4.3,$labelText['lonlat']);
      $pdf->Cell(50,4.3,$labelText['alt'],0,1,'R');
      $pdf->Ln(3);
    }

    if ($labelText['habitat']) {
      $pdf->SetFont('Times','',10);
      $pdf->MultiCell(100,3.7,$labelText['habitat']);
      $pdf->Ln(2.5);
    }

    if ($labelText['habitus']) {
      $pdf->SetFont('Times','',10);
      $pdf->MultiCell(0,3.7,$labelText['habitus']);
      $pdf->Ln(2.5);
    }

    if ($seperateColl1Date) {
      $pdf->SetFont('Times','',12);
      $pdf->Cell(0,4.6,$labelText['date'],0,1,'R');
    }
    $pdf->SetFont('Times','B',12);
    $pdf->Cell(10,4.3,"Leg.:");
    $pdf->SetFont('Times','',12);
    $pdf->SetLeftMargin($pdf->GetX());
    $pdf->Cell($lengthColl1+4,4.3,$labelText['coll1']);
    if (!$seperateColl1Date)
      $pdf->Cell(0,4.3,$labelText['date'],0,1,'R');
    else
      $pdf->Ln();
    if ($labelText['coll2']) $pdf->MultiCell(0,4.3,$labelText['coll2']);
    $pdf->SetLeftMarginOff(0);

    if ($labelText['det']) {
      $pdf->Ln(3);
      $pdf->SetFont('Times','B',12);
      $pdf->Cell(10,4.3,"Det.:");
      $pdf->SetFont('Times','',12);
      $pdf->SetLeftMargin($pdf->GetX());
      $pdf->MultiCell(0,4.3,$labelText['det']);
      $pdf->SetLeftMarginOff(0);
    }

    if ($labelText['Bemerkungen']) {
      $pdf->Ln(2);
      $pdf->Cell(0,2,'','T',1);
      $pdf->MultiCell(100,4.3,$labelText['Bemerkungen']);
      $pdf->Ln();
    }
  }
}

$pdf->Output();
?>