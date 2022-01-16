<?php
#ob_start();
require_once __DIR__ . '/../external_tools/composer/vendor/autoload.php';

#include("../mpdf/mpdf.php");
#include("../mpdf_7_1_5/src/Mpdf.php");
#include("../mpdf-7.0/mpdf.php");
#include ('./namensaufspaltung_functions_neu.php');
include ('../functions/namensaufspaltung_functions_agg.php');
include ('../functions/read_csv_xlsx_functions.php');


##################################################################################################################
##################################################################################################################
$neue_seite = 0;
$x_koordinate = '';
$land_data = '';
$y_koordinate = '';
$kuerzel_laufnr = '';
$fl_nr = '';
$verunreinigungen = '';
$x_koordinate = '';
$rst_kuerzel = '';
$standardschriftart = 'Times New Roman';
$individuen_anmerkung ='';
$spp = '';
$land_result ='';
$html ='';
$html2_labels = '';


# ----------------- Parameter-definition [in mm] ----------------------------------
	$papierbreite = 209; # A4 
	$papierhoehe = 296; # A4 
	$druckerrandoben = 10;
	$druckerrandlinks = 10;
	$etikettbreite = 85;
	$etiketthoehe = 25; 
	$etikettabstandlinksrechts = 10;
	$etikettabstandobenunten = 5;	
	$anzahlhorizontal = floor(($papierbreite-(2*$druckerrandlinks)+$etikettabstandlinksrechts)/($etikettbreite+$etikettabstandlinksrechts));
	$anzahlvertikal = floor(($papierhoehe-(2*$druckerrandoben)+$etikettabstandobenunten)/($etiketthoehe+$etikettabstandobenunten));
	$innenabstandrand = 0; # Abstand vom Rand im Etikettfeld 
	$innenmaxbreite = $etikettbreite-(2*$innenabstandrand);
	$standardschriftart = "Times";
	$standardschriftgroesse = "13";

	$r=0; # Rahmen um Textfelder an/aus 
# -----------------------------------------------------------------------------------------


if (isset($_POST['rev_ett_type']))
	{
	$det_rev_conf = $_POST['rev_ett_type'];
	if ($det_rev_conf != '') $det_rev_conf = $det_rev_conf.": ";
	}
else
	{
	$det_rev_conf = '';
	}
$det_rev_conf_copy = $det_rev_conf;

if (isset($_POST['manuelles_datum']))
	{
	$manuelles_datum = $_POST['manuelles_datum'];
	}
else
	{
	$manuelles_datum = '';
	}
	
if (isset($_POST['person']))
	{
	$det_rev_person = $_POST['person'];
	}
else
	{
	$det_rev_person = '';
	}

if (isset($_POST['akt_datum']))
	{
	$akt_datum = $_POST['akt_datum'];
	}
else
	{
	$akt_datum = '';
	}

	
	
if (isset($_POST['pflanze']))
	{
	$pflanze = $_POST['pflanze'];
	}
else
	{
	$pflanze = '';
	}

if (isset($_POST['anzahl']))
	{
	$ett_anzahl = $_POST['anzahl'];
	}
else
	{
	$ett_anzahl = '';
	}

$allowedExts = array("txt", "csv", "xls", "xlsx");
$temp = explode(".", $_FILES["file"]["name"]);
$extension = end($temp);
####################################################### csv auslesen #######

#$csv_neu = csv_to_array("./export.csv", "\t");
$csv_neu = csv_to_array($_FILES['file']['tmp_name'], "\t",$extension);

#var_dump($csv_neu);
##########################################################################
$zelle = '';

if ($ett_anzahl >=1)  ### leeres array erstellen, damit foreach schleife durchlaufen wird ...
	{
	if ($ett_anzahl == 0 || $ett_anzahl == '')
		{
		$ett_anzahl = 18;
		}
	#echo "XXX ";
	$csv_neu = array($csv_neu);
	for ($xi = 1; $xi <= $ett_anzahl; $xi++)
		{
		#echo $xi." <br>";
		$csv_neu[$xi-1] = $xi;
		}
	}

##################################################################################################################

$mpdf = new \Mpdf\Mpdf([
       'mode' => 'utf-8',
       'format' => 'A4',
       'margin_left' => '7',
       'margin_right' => '7',
       'margin_top' => '7',
       'margin_bottom' => '7'
 ]); 



#mpdf=new mPDF('utf8','A4','','',10,10,10,10,10,10); 

#$mpdf->SetDisplayMode('fullpage');
$mpdf->shrink_tables_to_fit=0; # verhindert, daß schrift automatisch kleiner wird, wenn zu wenig platz
#$mpdf->SetColumns(2,'J');
#$mpdf->WriteHTML($loremH,2);

#$mpdf->list_indent_first_level = 0;	// 1 or 0 - whether to indent the first level of a list

$mpdf->SetColumns(2);

$html_1 = '
<head>
<style>

body {
	font-family: Roman,"Times New Roman","Times,serif";
	font: Roman;
}


table {
	page-break-inside:avoid;
	border-collapse: separate;
// 	border: 4px solid #880000;
	padding: 0px;
	margin: 0px 0px;
// 	#empty-cells: hide;
	background-color:#FFFFFF; /* neccesary for correct alignment of labels !!!!!!!!!!!!!!!! */
}
table.outer2 {
	border-collapse: separate;
//	border: 4px solid #088000;
	padding: 0px 0px;
//	margin: 0px 0px 5px 0px;
	margin: 0px;
	empty-cells: hide;
//	background-color: yellow;
}
table.outer2 td {
//	font-family: Sans;
//	font: Quivira;
}
table.inner {
	border-collapse: collapse;
//	border: 2px solid #000088;
	padding: 0px;
//	margin: 8px 0px 0px 0px;
	empty-cells: show;
//	background-color:#FFCCFF;
}
td {
//	border: 1px solid #008800;
	padding: 0px;
//	background-color:#ECFFDF;
}
table.inner td {
// 	border: 1px solid #000088;
	padding: 0px;
//	font-family: monospace;
//	font-style: italic;
//	font-weight: bold;
//	color: #880000;
//	background-color:#FFECDF;
//	top 0px;
}
table.collapsed {
	border-collapse: collapse;
}
table.collapsed td {
//	background-color:#EDFCFF;
}


</style>
</head>
<body>';
/* <columns column-count="2"> 
<!-- # ------------------------------------------------------------------------------------------------------------------------------------ # -->
';
*/

$mpdf->WriteHTML($html_1);


################################################################################################################################################
	
	
	
	
	
	
	
##########################################################################################	
##########################################################################################
/*$mpdf=new mPDF(); # pdf erstellen
$mpdf->AddPage();
$y=0;
$x=0;
$z=0;
*/

$aa = 0;

foreach ($csv_neu as $zelle)
	{
/*	if ($neue_seite == 1) 
		{	
		$mpdf->AddPage(); ### überprüfen ob Befehl für mpdf aktuell +++++++++++++++++++++++++++++++++++++++
		$neue_seite=0;
		}

	$det_rev_conf = $det_rev_conf_copy;
	if (isset($zelle['rev_ett_art']))
		{
		$rev_ett_art = $zelle['rev_ett_art'];
		#$rev_ett_art = iconv("UTF-8", "Windows-1252//TRANSLIT", $rev_ett_art);
		if ($rev_ett_art != '')
			{
			$det_rev_conf = $rev_ett_art;
			}
		}
	else
		{
		$rev_ett_art = '';
		}
	
*/

	$herb_nr = $zelle['Institution_Code']." ".$zelle['HerbariumNr_BarCode']; # 2 => WU; 1 => WU-Nr.
	$pfla_name= $zelle['Taxon'];	# 6 => Taxon
	$det = $zelle['det_rev_conf'];	# 17=> 'det./rev./conf./assigned'
	#echo $herb_nr;
	$herb_nr = trim($herb_nr);
	$pfla_name = trim($pfla_name);
	$det = trim($det);

	$det_zerlegt = explode(" ",$det);
	$det_anzahl = count($det_zerlegt)-1;
	$datum = $det_zerlegt[$det_anzahl];

	$det_neu = str_replace(" ".$datum,"",$det);
	if ((((strlen($datum) == 4) and (is_numeric($datum)))or 
		((strlen($datum) == 7) and (substr_count($datum, "-") ==1)) or 
		(((strlen($datum) == 9) or (strlen($datum) == 10)) and (substr_count($datum, "-") ==2)) and (strlen($det_neu) >=3)))
		{
		$det = $det_neu;
		}
	else
		{
		if ($akt_datum == 'yes')
			{
			$jahr = date("Y"); # aktuelles Datum
			$monat = date("n"); # aktuelles Datum
			$tag = date("j"); # aktuelles Datum
			if ($monat <=9) 
				{
				$monat = "0".$monat;
				}
			if ($tag <=9) 
				{
				$tag = "0".$tag;
				}
			$datum = $jahr."-".$monat."-".$tag;
			}
		else
			{
			if ($manuelles_datum != '')
				{
				$datum = $manuelles_datum;
				}
			else
				{
				$datum = '';
				}
			}
		}
		
		/*
# oben-unten-positionierung 
			$rahmeny=($y*($etiketthoehe+$etikettabstandobenunten))+$druckerrandoben;
			$erstezeiley=$rahmeny+$innenabstandrand; #Reinstoffk¸rzel und Reinheit 
			$liniey=$rahmeny+$innenabstandrand+5; # Trivialname, Pflanze, Organ usw. ... 
			$zweitezeiley=$rahmeny+$innenabstandrand+7; # Trivialname, Pflanze, Organ usw. ... 
			$mengey=$rahmeny+$innenabstandrand+15; # Menge 
			$aufbewahrungy=$rahmeny+$innenabstandrand+16; # Lˆsungsmittel und Leergewicht 
			$letzezeiley=$rahmeny+$innenabstandrand+18; /# Datum und Schmelzpunkt 
# ---------------------------		
		
# links-rechts-positionierung 
			$rahmenx=($x*($etikettbreite+$etikettabstandlinksrechts))+$druckerrandlinks;
			$linienendx = $rahmenx+$etikettbreite;
			$kuerzelx=$rahmenx+$innenabstandrand;
			$reinheitx=$rahmenx+$innenabstandrand+18;
			$trivialx=$rahmenx+$innenabstandrand;
			$pflanzex=$rahmenx+$innenabstandrand;
			$organx=$rahmenx+$innenabstandrand;
			$mengex=$rahmenx+$innenabstandrand; 
			$datumx=$rahmenx+$innenabstandrand; 
			$detx = $rahmenx+$innenabstandrand+30;
			$aufbewahrungx=$rahmenx+$innenabstandrand+12; 
			$tarax=$rahmenx+$innenabstandrand+22; 
			$schmelzpunktx=$rahmenx+$innenabstandrand+12; 
			$abstand_papierrand_rechts = $papierbreite-$rahmenx-$innenmaxbreite;
			# 209.9-$rahmenx-96 
# --------------------------- 
*/
/*	if ($det_rev_conf != '')
		{
		$det_rev_conf = $det_rev_conf.": ";
		} */
	if ($det == '')
		{
		$det = $det_rev_person;
		}
	$det = $det_rev_conf.$det;
	$plfa_html = $pfla_name;
	if ($plfa_html == '')
		{
		$plfa_html = $pflanze;
		}
	$html_orig = $plfa_html;
	#	$html = str_replace("amp;", "", $html); # ACHTUNG: saemtliche &amp; bzw. &amp;amp;amp; (usw. ...) werden als unmaskiertes & ausgegeben !!!!!!!!!
	#	$html = str_replace("amp;", "", $html); # ACHTUNG: saemtliche &amp; bzw. &amp;amp;amp; (usw. ...) werden als unmaskiertes & ausgegeben !!!!!!!!!
	# 	$html = str_replace("&quot;", "\"", $html); #ACHTUNG: saemtliche &amp; bzw. &amp;amp;amp; (usw. ...) werden als unmaskiertes & ausgegeben !!!!!!!!!
	# 	$html = str_replace("&gt;", ">", $html); # &qt; als unmaskiertes > ausgegeben !!!!!!!!!
	# 	$html = str_replace("&lt;", "<", $html); # &lt; als unmaskiertes < ausgegeben !!!!!!!!!


$name_aufgespalten = namensaufspaltung_hybrid($plfa_html);
#$html = $name_aufgespalten['html'];
#$html_pos_oben = $rahmeny + 10;
#$html = "<div style=\"position: absolute; top: ".$html_pos_oben."mm; left: ".$rahmenx."mm; width: ".$etikettbreite."mm; \">
#			".$name_aufgespalten['html']."Pos.: ".$rahmeny." -- ".$rahmenx."
#			</div>"; 

$schriftgroesse_html = 12;

if (strlen($html_orig) >= 30)
	{
	$schriftgroesse_html = 11;
	}
if (strlen($html_orig) >= 60)
	{
	$schriftgroesse_html = 10;
	}
if (strlen($html_orig) >= 90)
	{
	$schriftgroesse_html = 9;
	}
if (strlen($html_orig) >= 120)
	{
	$schriftgroesse_html = 8;
	}


/*$plfa_html = "<div style=\"position: absolute; left: ".$rahmenx."mm; width: ".$etikettbreite."mm; font-size: ".$schriftgroesse_html."pt; font-family:\"'Times New Roman',Times,serif\"; \">
			".$name_aufgespalten['html']."
			</div>"; */
$plfa_html = $name_aufgespalten['html'];
$plfa_html = str_replace(". )", ".)", $plfa_html); # ersetzt stoerende leerzeichen vor Klammern.

#$html  = '';
/*			#$pdf->Rect($rahmenx,$rahmeny, $etikettbreite, $etiketthoehe); # zeichnet aeusserern Rahmen 

			$mpdf->SetFont($standardschriftart,'', 4); #Schriftgroesse fuer Stoffkuerzel definieren 
			$mpdf->SetXY($kuerzelx, $erstezeiley);
			#$mpdf->MultiCell(85, 4, $herb_nr,$r,'L');						
			$mpdf->Line($rahmenx, $liniey, $linienendx, $liniey);
			
			$mpdf->SetFont($standardschriftart,'', 11); # Schriftgroesse fuer Stoffkuerzel definieren 
			$mpdf->SetXY($kuerzelx, $erstezeiley);
			#$mpdf->MultiCell(85, 4, 'Herbarium WU',$r,'C');	### Herbarium WU
			$mpdf->Line($rahmenx, $liniey, $linienendx, $liniey);  ### Linie unter Herbarium WU
			
			$mpdf->SetFont($standardschriftart,'', 11);  
			$mpdf->SetXY($schmelzpunktx, $letzezeiley);

			$mpdf->SetFont($standardschriftart,'','10'); # Schriftgroesse zuruecksetzen #
			$mpdf->SetXY($datumx, $letzezeiley);
			#$mpdf->MultiCell(30, 4,$datum,$r,'L');	### Datum
			
			$mpdf->SetFont($standardschriftart,'', '10');  

			$mpdf->SetXY($detx, $letzezeiley);
			#$mpdf->MultiCell(55, 4,$det,$r,'L');		### det/rev/conf.
			  

			$mpdf->SetFont($standardschriftart,'',$standardschriftgroesse); # Schriftgroesse zuruecksetzen 
			
			#$html = utf8_ersetzung($html);
			
			$mpdf->SetLeftMargin($pflanzex);
			$mpdf->SetRightMargin($abstand_papierrand_rechts);
			$mpdf->SetY($zweitezeiley);
			$mpdf->WriteHTML($html);		### Name

	
	#$druckerrandoben = $druckerrandoben + $etiketthoehe + $etikettabstandobenunten;
	
	$x++;
	if ($y == $anzahlvertikal) {$y =0;} # Anzahl der Zeilen #
	if ($x == $anzahlhorizontal) {$x =0; $y++;} # anzahl der Spalten #
	$z++;
	if ($z == ($anzahlhorizontal*$anzahlvertikal)) # anzahl aller etiketten pro seite #
		{
		$x =0;
		$y =0;
		$z =0;
		$neue_seite=1;
		} */
	$title = "Herbarium WU";
	
	if ($herb_nr != '') $herb_nr_html = '	  <tr>
	    <td style="text-align: left; font-size: 4pt; vertical-align:top;">'.$herb_nr.'</td>
	  </tr>';
    else $herb_nr_html = "";

	
 $html_2_labels_temp = '<table cellSpacing="0" class="outer2"  style="page-break-inside:avoid;"  width="100%">
  
  <tbody  style="vertical-align:top;">
  <tr>
    <td style="height: 29.5mm; vertical-align:top; padding-left: 0em; padding-right: 0em;">
      <table cellSpacing="0" class="inner" width="100%" style=" vertical-align:top;" >
	<tbody style="vertical-align:top;">
	'.$herb_nr_html.'
	  <tr style = "border-bottom:solid;">
	    <td style="text-align: center; font-size: 11pt; padding-bottom:0.15em;  vertical-align:top; border-bottom:1px solid black;">'.$title.'</td>
	  </tr>
	  <tr>
	    <td style="height: 2em; text-align: left; font-size: 12pt; vertical-align:middle; padding-top:0.5em;">'.$plfa_html.'</td>
	  </tr>
	  <tr>
	    <td style="height: 1em; text-align: left; font-size: 10pt; vertical-align:middle;padding-top:0.5em">'.$datum."&nbsp;&nbsp;&nbsp; ".$det.'</td>
	  </tr>
	</tbody>
      </table>
    </td>
  </tr>
  </tbody>
</table>
<div style = "font-size: 0.001pt; ">&nbsp;</div>'; # diese zeile verhindert zeilenumbruch im etikett!!!
#<div style = "font-size: 0.001pt; page-break-after:auto; page-break-before:auto;">&nbsp;</div>'; # diese zeile verhindert zeilenumbruch im etikett!!!
#echo $html_2_labels_temp;
  if  ($title != '')
    {
    $html2_labels = $html2_labels.$html_2_labels_temp;
    }
 $aa++;
if ($aa > 17)
    {
    $aa = 0;
    $html2_labels .= "<pagebreak>";
    }
$mpdf->WriteHTML($html2_labels);
#echo $html2_labels;
$html2_labels = '';

#echo $aa;
#echo $html2_labels; 
unset($zelle);
}



$html_3 = '

<p>&nbsp;</p>


</body>
';


$mpdf->WriteHTML($html_3);


$html =$html_1.$html2_labels.$html_3;

$html =$html_1.$html2_labels.$html_3;
/*echo "<pre>";
var_dump($html);
echo "</pre>";
exit; */
//==============================================================
//==============================================================
//==============================================================

/*$mpdf = new \Mpdf\Mpdf([
       'mode' => 'utf-8',
       'format' => 'A4',
       'margin_left' => '10',
       'margin_right' => '10',
       'margin_top' => '10',
       'margin_bottom' => '10'
 ]); 



#mpdf=new mPDF('utf8','A4','','',10,10,10,10,10,10); 

$mpdf->SetDisplayMode('fullpage');
$mpdf->shrink_tables_to_fit=0; # verhindert, daß schrift automatisch kleiner wird, wenn zu wenig platz
#$mpdf->SetColumns(2,'J');
#$mpdf->WriteHTML($loremH,2);

$mpdf->list_indent_first_level = 0;	// 1 or 0 - whether to indent the first level of a list

// LOAD a stylesheet
#$stylesheet = file_get_contents('mpdfstyletables.css');
#$mpdf->WriteHTML($stylesheet,1);	// The parameter 1 tells that this is css/style only and no body/html/text

#echo $html;
#exit;

#ob_end_flush();


$mpdf->WriteHTML($html); */

$mpdf->Output('wu_revisionetiketten.pdf','D');
exit;
    

    
    
    
/*
unset($zelle);
		
		
	}

echo $html;
#$mpdf->Output();
exit;
*/

?>
