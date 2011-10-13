<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");
require("inc/herbardb_input_functions.php");
require("inc/log_functions.php");
require_once ("inc/xajax/xajax_core/xajax.inc.php");

no_magic();

if (isset($_GET['sel']) && extractID($_GET['sel'])!="NULL")
  $db_specimen_ID = extractID($_GET['sel']);
elseif (intval($_POST['specimen_ID']))
  $db_specimen_ID = "'".intval($_POST['specimen_ID'])."'";
else
  die('No valid dataset selected');

$xajax = new xajax();
$xajax->setRequestURI("ajax/editLabelServer.php");

$xajax->registerFunction("toggleTypeLabelMap");
$xajax->registerFunction("toggleTypeLabelSpec");
$xajax->registerFunction("toggleBarcodeLabel");
$xajax->registerFunction("clearTypeLabelsMap");
$xajax->registerFunction("clearTypeLabelsSpec");
$xajax->registerFunction("clearBarcodeLabels");
$xajax->registerFunction("checkTypeLabelMapPdfButton");
$xajax->registerFunction("checkTypeLabelSpecPdfButton");
$xajax->registerFunction("checkBarcodeLabelPdfButton");
$xajax->registerFunction("updtStandardLabel");
$xajax->registerFunction("clearStandardLabels");
$xajax->registerFunction("checkStandardLabelPdfButton");

$nr = intval($_GET['nr']);
$linkList = $_SESSION['labelLinkList'];

$sql = "SELECT wu.specimen_ID, wu.HerbNummer, si.identification_status, wu.checked, wu.accessible,
         wu.taxonID, ss.series, wu.series_number, wu.Nummer, wu.alt_number, wu.Datum, wu.Datum2,
         wu.det, wu.typified, wu.taxon_alt, wu.Bezirk,
         wu.Coord_W, wu.W_Min, wu.W_Sec, wu.Coord_N, wu.N_Min, wu.N_Sec,
         wu.Coord_S, wu.S_Min, wu.S_Sec, wu.Coord_E, wu.E_Min, wu.E_Sec,
         wu.quadrant, wu.quadrant_sub, wu.exactness, wu.altitude_min, wu.altitude_max,
         wu.digital_image, l.label,
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
         LEFT JOIN tbl_labels l ON (wu.specimen_ID=l.specimen_ID AND l.userID='{$_SESSION['uid']}')
        WHERE wu.SammlerID=c.SammlerID
         AND wu.specimen_ID=$db_specimen_ID";
$result = db_query($sql);
if (mysql_num_rows($result)>0) {
  $row = mysql_fetch_array($result);
  $p_specimen_ID   = $row['specimen_ID'];
  $p_HerbNummer    = $row['HerbNummer'];
  $p_identstatus   = $row['identification_status'];
  $p_checked       = $row['checked'];
  $p_accessible    = $row['accessible'];
  $p_series        = $row['series'];
  $p_series_number = $row['series_number'];
  $p_Nummer        = $row['Nummer'];
  $p_alt_number    = $row['alt_number'];
  $p_Datum         = $row['Datum'];
  $p_Datum2        = $row['Datum2'];
  $p_det           = $row['det'];
  $p_typified      = $row['typified'];
  $p_taxon_alt     = $row['taxon_alt'];
  $p_Bezirk        = $row['Bezirk'];
  $p_quadrant      = $row['quadrant'];
  $p_quadrant_sub  = $row['quadrant_sub'];
  $p_exactness     = $row['exactness'];
  $p_altitude_min  = $row['altitude_min'];
  $p_altitude_max  = $row['altitude_max'];
  $p_digital_image = $row['digital_image'];
  $p_garten        = $row['garten'];
  $p_voucher       = $row['voucher'];
  $p_ncbi          = $row['ncbi_accession'];

  $p_collection  = $row['collection'];
  $p_typus       = $row['typus_lat'];
  $p_nation      = $row['nation_engl'];
  $p_province    = $row['provinz'];

  $p_label       = $row['label'];

  $p_sammler     = $row['Sammler']." <".$row['SammlerID'].">";
  $p_sammler2    = ($row['Sammler_2']) ? $row['Sammler_2']." <".$row['Sammler_2ID'].">" : "";

  if ($row['Coord_S']>0 || $row['S_Min']>0 || $row['S_Sec']>0) {
    $p_lat_deg       = $row['Coord_S'];
    $p_lat_min       = $row['S_Min'];
    $p_lat_sec       = $row['S_Sec'];
    $p_lat           = "S";
  } else {
    $p_lat_deg       = $row['Coord_N'];
    $p_lat_min       = $row['N_Min'];
    $p_lat_sec       = $row['N_Sec'];
    $p_lat           = "N";
  }
  if ($row['Coord_W']>0 || $row['W_Min']>0 || $row['W_Sec']>0) {
    $p_lon_deg       = $row['Coord_W'];
    $p_lon_min       = $row['W_Min'];
    $p_lon_sec       = $row['W_Sec'];
    $p_lon           = "W";
  } else {
    $p_lon_deg       = $row['Coord_E'];
    $p_lon_min       = $row['E_Min'];
    $p_lon_sec       = $row['E_Sec'];
    $p_lon           = "E";
  }

  if ($row['taxonID']) {
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
             LEFT JOIN tbl_tax_genera tg ON tg.genID=ts.genID
            WHERE ts.taxonID='".mysql_escape_string($row['taxonID'])."'";
    $result2 = db_query($sql);
    $row2 = mysql_fetch_array($result2);
    $p_taxon  = taxon($row2);
  } else
    $p_taxon = "";
  $_SESSION['labelSpecimen_ID'] = $db_specimen_ID;
} else
  die('No valid dataset selected');
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - edit Labels</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <style type="text/css">
    div.cssftext { font-weight: bold; }
  </style>
  <?php $xajax->printJavascript('inc/xajax'); ?>
  <script type="text/javascript" language="JavaScript">
    var inpSLvalue;
    function toggleLabelWrapper(sel) {
      switch (sel) {
        case 1: xajax_toggleTypeLabelMap();
                break;
        case 2: xajax_toggleTypeLabelSpec();
                break;
        case 3: xajax_toggleBarcodeLabel();
                break;
      }
    }
    function updtLabelWrapper(sel,data) {
      switch (sel) {
        case 10: if (data!=inpSLvalue) {
                  inpSLvalue = data;
                  xajax_updtStandardLabel(data);
                }
                break;
      }
    }
    function showImage(sel, server) {
      target = server+"/"+sel+"/show";
	  MeinFenster = window.open(target,"imgBrowser");
      MeinFenster.focus();
    }
    function showPDF(sel) {
      switch (sel) {
        case 'typeMap':  target = "pdfLabelTypesMap.php"; label = "labelTypesMap"; break;
        case 'typeSpec': target = "pdfLabelTypesSpec.php"; label = "labelTypesSpec"; break;
        case 'std':      target = "pdfLabelStandard.php"; label = "labelStandard"; break;
        case 'barcode':  target = "pdfLabelBarcode.php"; label = "labelBarcode"; break;
      }
      MeinFenster = window.open(target, label);
      MeinFenster.focus();
    }

    function goBack(sel) {
      self.location.href = 'listLabel.php?nr=' + sel;
    }

    xajax_checkTypeLabelMapPdfButton();
    xajax_checkTypeLabelSpecPdfButton();
    xajax_checkStandardLabelPdfButton();
    xajax_checkBarcodeLabelPdfButton();
  </script>
</head>

<body>

<form name="f" onsubmit="return false;">

<?php
if ($nr) {
  echo "<div style=\"position: absolute; left: 15em; top: 0.4em;\">";
  if ($nr>1)
    echo "<a href=\"editLabel.php?sel=".htmlentities("<".$linkList[$nr-1].">")."&nr=".($nr-1)."\">".
         "<img border=\"0\" height=\"22\" src=\"webimages/left.gif\" width=\"20\">".
         "</a>";
  else
    echo "<img border=\"0\" height=\"22\" src=\"webimages/left_gray.gif\" width=\"20\">";
  echo "</div>\n";
  echo "<div style=\"position: absolute; left: 17em; top: 0.4em;\">";
  if ($nr<$linkList[0])
    echo "<a href=\"editLabel.php?sel=".htmlentities("<".$linkList[$nr+1].">")."&nr=".($nr+1)."\">".
         "<img border=\"0\" height=\"22\" src=\"webimages/right.gif\" width=\"20\">".
         "</a>";
  else
    echo "<img border=\"0\" height=\"22\" src=\"webimages/right_gray.gif\" width=\"20\">";
  echo "</div>\n";
}

$cf = new CSSF();

echo "<input type=\"hidden\" name=\"specimen_ID\" value=\"$p_specimen_ID\">\n";
echo "<input type=\"hidden\" name=\"ncbi\" value=\"$p_ncbi\">\n";
$cf->label(9,0.5,"specimen_ID");
$cf->text(9,0.5,"&nbsp;".$p_specimen_ID);

if ($p_digital_image && $p_specimen_ID) {
  $cf->label(33.5,0,"digital image","javascript:showImage('$p_specimen_ID', '".getPictureServerIP($p_specimen_ID)."')");
  $cf->text(33.5,0,"&nbsp;&radic;");
}
if ($p_checked) {
  $cf->label(42,0,"checked");
  $cf->text(42,0,"&nbsp;&radic;");
}
if ($p_accessible) {
  $cf->label(54.5,0,"accessible");
  $cf->text(54.5,0,"&nbsp;&radic;");
}

$cf->label(9,2,"Collection");
$cf->text(9,2,"&nbsp;".$p_collection);
$cf->label(26,2,"Nr.");
$cf->text(26,2,"&nbsp;".$p_HerbNummer);
$cf->label(42,2,"type");
$cf->text(42,2,"&nbsp;".$p_typus);

$cf->label(9,4,"Status");
$cf->text(9,4,"&nbsp;".$p_identstatus);

$cf->label(23,4,"Garden");
$cf->text(23,4,"&nbsp;".$p_garten);

$cf->label(42,4,"voucher");
$cf->text(42,4,"&nbsp;".$p_voucher);

$cf->label(9,6,"taxon");
$cf->text(9,6,"&nbsp;".$p_taxon);
$cf->label(9,8,"det / rev / conf");
$cf->text(9,8,"&nbsp;".$p_det);
$cf->label(9,10,"ident. history");
$cf->text(9,10,"&nbsp;".$p_taxon_alt);
$cf->label(9,12,"typified by");
$cf->text(9,12,"&nbsp;".$p_typified);

$cf->label(9,14,"Series");
$cf->text(9,14,"&nbsp;".$p_series);
$cf->label(49.5,14,"ser.Nr.");
$cf->text(49.5,14,"&nbsp;".$p_series_number);

$cf->label(9,16,"first collector");
$cf->text(9,16,"&nbsp;".$p_sammler);

$cf->label(9,18,"Number");
$cf->text(9,18,"&nbsp;".$p_Nummer);
$cf->label(18,18,"alt.Nr.");
$cf->text(18,18,"&nbsp;".$p_alt_number);
$cf->label(42,18,"Date");
$txt = (strlen(trim($p_Datum2))>0) ? " &ndash; ".$p_Datum2 : "";
$cf->text(42,18,"&nbsp;".$p_Datum.$txt);

$cf->label(9,20,"add. collector(s)");
$cf->text(9,20,"&nbsp;".$p_sammler2);

echo "<div style=\"position: absolute; left: 1em; top: 21.75em; width: 54.5em;\"><hr></div>\n";

$cf->label(9,23,"Nation");
$cf->text(9,23,"&nbsp;".$p_nation);
$cf->label(40,23,"Province");
$cf->text(40,23,"&nbsp;".$p_province);
$cf->label(9,25,"Region");
$cf->text(9,25,"&nbsp;".$p_Bezirk);

$cf->label(9,27,"Altitude");
$txt = (strlen(trim($p_altitude_max))) ? " &ndash; ".$p_altitude_max : "";
$cf->text(9,27,"&nbsp;".$p_altitude_min.$txt);

$cf->label(40,27,"Quadrant");
$cf->text(40,27,"&nbsp;".$p_quadrant);
$cf->text(44,27,"&nbsp;".$p_quadrant_sub);

$cf->label(9,29,"Lat");
$txt = (strlen(trim($p_lat_deg))) ? sprintf("%d&deg; %02d&prime; %02d&Prime; %s",$p_lat_deg,$p_lat_min,$p_lat_sec,$p_lat) : "";
$cf->text(9,29,"&nbsp;".$txt);

$cf->label(27,29,"Lon");
$txt = (strlen(trim($p_lon_deg))) ? sprintf("%d&deg; %02d&prime; %02d&Prime; %s",$p_lon_deg,$p_lon_min,$p_lon_sec,$p_lon) : "";
$cf->text(27,29,"&nbsp;".$txt);

$cf->label(48,29,"exactn.");
$cf->text(48,29,"&nbsp;".$p_exactness);

echo "<div style=\"position: absolute; left: 1em; top: 30.75em; width: 54.5em;\"><hr></div>\n";


// Type Labels
if ($p_typus) {
  $cf->label(9,32,"Type map Label");
  $cf->checkboxJavaScript(9,32,"cbTLmap\" id=\"cbTypeLabelMap",($p_label & 0x1),"toggleLabelWrapper(1)");
  $txt = ($p_label & 0x1) ? "&nbsp;&radic;" : "&nbsp;&ndash;";
  echo "<div class=\"cssftext\" style=\"position: absolute; left: 10em; top: 32.2em;\" id=\"typeLabelMap\">$txt</div>";
  $cf->label(9,34,"Type spec Label");
  $cf->checkboxJavaScript(9,34,"cbTLspec\" id=\"cbTypeLabelSpec",($p_label & 0x2),"toggleLabelWrapper(2)");
  $txt = ($p_label & 0x2) ? "&nbsp;&radic;" : "&nbsp;&ndash;";
  echo "<div class=\"cssftext\" style=\"position: absolute; left: 10em; top: 34.2em;\" id=\"typeLabelSpec\">$txt</div>";
}
else {
  $cf->label(9,32,"Type map Label");
  echo "<div class=\"cssftext\" style=\"position: absolute; left: 10em; top: 32.2em;\" id=\"typeLabelMap\">&nbsp;&ndash;</div>";
  $cf->label(9,34,"Type spec Label");
  echo "<div class=\"cssftext\" style=\"position: absolute; left: 10em; top: 34.2em;\" id=\"typeLabelSpec\">&nbsp;&ndash;</div>";
}
$cf->buttonJavaScript(16,32,"make PDF (Type map Labels)\" id=\"btMakeTypeLabelMapPdf","showPDF('typeMap')");
$cf->buttonJavaScript(35,32,"clear all Type map Labels\" id=\"btClearTypeMapLabels","xajax_clearTypeLabelsMap()");
$cf->buttonJavaScript(16,34,"make PDF (Type spec Labels)\" id=\"btMakeTypeLabelSpecPdf","showPDF('typeSpec')");
$cf->buttonJavaScript(35,34,"clear all Type spec Labels\" id=\"btClearTypeSpecLabels","xajax_clearTypeLabelsSpec()");

// barcode labels
$cf->label(9,36,"barcode Label");
$cf->checkboxJavaScript(9,36,"cbBL\" id=\"cbBarcodeLabel",($p_label & 0x4),"toggleLabelWrapper(3)");
$txt = ($p_label & 0x4) ? "&nbsp;&radic;" : "&nbsp;&ndash;";
echo "<div class=\"cssftext\" style=\"position: absolute; left: 10em; top: 36.2em;\" id=\"barcodeLabel\">$txt</div>";
$cf->buttonJavaScript(16,36,"make PDF (Barcode Labels)\" id=\"btMakeBarcodeLabelPdf","showPDF('barcode')");
$cf->buttonJavaScript(35,36,"clear all Barcode Labels\" id=\"btClearBarcodeLabels","xajax_clearBarcodeLabels()");

// standard Labels
$cf->label(9,38,"standard Label");
$cf->inputText(9,38,1,"inpSL\" id=\"inpStandardLabel\" onkeyup=\"updtLabelWrapper(10,document.f.inpSL.value)",($p_label & 0xf0) / 16,1);
echo "<div class=\"cssftext\" style=\"position: absolute; left: 11em; top: 38.2em;\" id=\"standardLabel\">".(($p_label & 0xf0) / 16)."</div>";
$cf->buttonJavaScript(16,38,"make PDF (standard Labels)\" id=\"btMakeStandardLabelPdf","showPDF('std')");
$cf->buttonJavaScript(35,38,"clear all standard Labels\" id=\"btClearStandardLabels","xajax_clearStandardLabels()");


$cf->buttonJavaScript(2,50," < Specimens ","goBack($nr)");
?>
</form>

</body>
</html>
