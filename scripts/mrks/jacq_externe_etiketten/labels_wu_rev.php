<?php
#ob_start();
require_once __DIR__ . '/../external_tools/composer/vendor/autoload.php'; # mpdf and phpoffice/phpspreadsheet

require_once ('../quadrant/quadrant_function.php'); # functions to transform and maipulate geographic coodinates and related stuff
require_once ('../functions/namensaufspaltung_functions_agg.php'); # functions to split and rebuild taxa names
require_once ('../functions/read_csv_xlsx_functions.php'); # function to read spreadsheets based on phpoffice/phpspreadsheet
require_once ('./etiketten_functions.php'); # function to read spreadsheets based on phpoffice/phpspreadsheet



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
$individuen_anmerkung ='';
$spp = '';
$land_result ='';
$html ='';
$html2_labels = '';

# function for preg_replace_callback (insert narrow space)
function leerzeichen($matches) {
  return $matches[0] . '&#8239;';
}



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


###################### read input file #################################
$temp = explode(".", $_FILES["file"]["name"]);
$extension = end($temp);
$csv_neu = csv_to_array($_FILES['file']['tmp_name'], ",",$extension);

########################################################################	

	
	
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




$mpdf->shrink_tables_to_fit=0; # verhindert, daß schrift automatisch kleiner wird, wenn zu wenig platz

$mpdf->SetColumns(2);

$html_1 = '
<head>
<link rel="stylesheet" href="./labels_wu_rev.css" >

</head>
<body>';
/* <columns column-count="2"> 
<!-- # ------------------------------------------------------------------------------------------------------------------------------------ # -->
';
*/

$mpdf->WriteHTML($html_1);


################################################################################################################################################
	
	
	
	
$herb_arr = nomalize_input_data($csv_neu);
	
	
	
##########################################################################################	
##########################################################################################

$aa = 0;

foreach ($herb_arr as $zelle)
	{

	$herb_nr = $zelle['institution_subcollection_val']." ".$zelle['herbarium_no']; # 2 => WU; 1 => WU-Nr.
	$pfla_name= $zelle['taxon'];	# 6 => Taxon
	$det = $zelle['det'];	# 17=> 'det./rev./conf./assigned'
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


$name_aufgespalten = $zelle['taxon_html'];

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
$plfa_html = $name_aufgespalten;
$plfa_html = str_replace(". )", ".)", $plfa_html); # ersetzt stoerende leerzeichen vor Klammern.

	$title = "Herbarium ".$zelle['institution_code'];
	$stable_url = $zelle['stable_url'];
	$stable_url_nr = preg_replace('/^((https)|(http)):\/\/.*\//i','',$stable_url);
	

$pattern = '/[a-z\-]+/i';
$stable_url_nr = preg_replace_callback($pattern, 'leerzeichen', $stable_url_nr);
	
	
	
/*	if ($herb_nr != '') $herb_nr_html = '	  <tr>
	    <td style="text-align: left; font-size: 4pt; vertical-align:top;">'.$herb_nr.'</td>
	  </tr>';
    else $herb_nr_html = ""; */

	
 $html_2_labels_temp = '<table cellSpacing="0" class="outer2"  style="page-break-inside:avoid;"  width="100%">
  
  <tbody  style="vertical-align:top;">
  <tr>
    <td style="height: 29.5mm; vertical-align:top; padding-left: 0em; padding-right: 0em;">
      <table cellSpacing="0" class="inner" width="100%" style=" vertical-align:top;" >
	<tbody style="vertical-align:top;">
	  <tr style = "border-bottom:1px solid black;">
        <td style = "border-bottom:1px solid black; font-size: 7pt;" width="25%">'.$stable_url_nr.'&nbsp;</td>
	    <td style="text-align: center; font-size: 11pt; padding-bottom:0.15em;  vertical-align:top; border-bottom:1px solid black;">'.$title.'</td>
        <td style = "border-bottom:1px solid black;" width="25%">&nbsp;</td>
	  </tr>
	  <tr>
	    <td colspan = "3" style="height: 2em; text-align: left; font-size: 12pt; vertical-align:middle; padding-top:0.5em;">'.$plfa_html.'</td>
	  </tr>
	  <tr>
	    <td colspan = "3" style="height: 1em; text-align: left; font-size: 10pt; vertical-align:middle;padding-top:0.5em">'.$datum."&nbsp;&nbsp;&nbsp; ".$det.'</td>
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
