<?php
header("Content-type: text/html; charset=UTF-8; X-Content-Type-Options: nosniff");

require_once __DIR__ . '/../external_tools/composer/vendor/autoload.php'; # für mpdf und phpoffice/phpspreadsheet

include('../quadrant/quadrant_function.php');
include ('../functions/namensaufspaltung_functions_agg.php');
include ('../functions/read_csv_xlsx_functions.php');

    
 

###################### datei auslesen #################################	
$temp = explode(".", $_FILES["file"]["name"]);
$extension = end($temp);
$csv_neu = csv_to_array($_FILES['file']['tmp_name'], ",",$extension);

######################## datei auslesen ##########################





$mpdf = new \Mpdf\Mpdf([
       'mode' => 'utf-8',
       'format' => 'A4',
       'margin_left' => '9',
       'margin_right' => '9',
       'margin_top' => '10',
       'margin_bottom' => '10'
 ]); 

$mpdf->SetDisplayMode('fullpage');
$mpdf->shrink_tables_to_fit=0; # verhindert, daß schrift automatisch kleiner wird, wenn zu wenig platz
#$mpdf->SetColumns(2,'J');
#$mpdf->WriteHTML($loremH,2);

$mpdf->list_indent_first_level = 0;	// 1 or 0 - whether to indent the first level of a list




##################################################################################################################
$html_1 = '
<head>
<style>

body {
	font-family: sans;
	font: freesans,Quivira,FreeSans,Arial,Helvetica;
}


table {
	page-break-inside:avoid;
	border-collapse: separate;
	padding: 0px;
	margin: 0px 0px;
// 	#empty-cells: hide;
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
//	border: 1px solid #000088;
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

.linebreakfix { /* prevents page/columnbrake within label */
font-size: 0.01pt;
}

</style>
</head>
<body>
 <columns column-count="2"> 
<!-- # ------------------------------------------------------------------------------------------------------------------------------------ # -->
';
$mpdf->WriteHTML($html_1);

################################################################################################################################################
$pp = 0;
$html2_labels = "";
$herb_subtitle = "";
$herb_title = "INSTITUT FÜR BOTANIK DER UNIVERSITÄT WIEN";

foreach ($csv_neu as $herb_line)
  {
  if (isset($herb_line['Province']))
    {
    if (isset($herb_line['Admin1'])) $herb_line['Admin1'] .= trim($herb_line['Province']);
    else $herb_line['Admin1'] = trim($herb_line['Province']);
    }

  if (isset($herb_line['Location']))
    {
    if (isset($herb_line['Label'])) $herb_line['Label'] .= $herb_line['Location'];
    else $herb_line['Label'] = $herb_line['Location'];
    }
  #echo "<pre>";
  #var_dump($herb_line);
  #echo "</pre>";
  $pp++;
  # herb-nr ...
  #if ($herb_line['Collection'] && $herb_line['Collection'] != "WU") $herb_nr= $herb_line['Collection']." ".str_replace("WU ","",$herb_line['Herbarium-Number/BarCode']);
  #else $herb_nr= $herb_line['Herbarium-Number/BarCode'];
  $herb_nr = '';
  if (isset($herb_line['HerbariumNr_BarCode'])) $herb_nr = str_replace("WU ","",$herb_line['HerbariumNr_BarCode']);
  if (isset($herb_line['Herbarium-Number/BarCode'])) $herb_nr = str_replace("WU ","",$herb_line['Herbarium-Number/BarCode']);
  $herb_subtitle = "<b>HERBARIUM&nbsp;WU-MYC&#x202F;".$herb_nr."&nbsp;MYCOLOGICUM</b>";
  
  
  $taxon_arr = array();
  $taxon_html = '';
  if (isset($herb_line['Taxon']) && ($herb_line['Taxon']) != '') 
    {
    $taxon_arr = namensaufspaltung_hybrid($herb_line['Taxon']);
    $taxon_html = str_replace(" )",")",$taxon_arr['html']);
    }
  
  $type = '';
  $type_info = '';
  $type_taxa = '';
  $type_acc_taxa = '';
  if (isset($herb_line['Type_information']) && ($herb_line['Type_information']) != '') $herb_line['Type information'] = $herb_line['Type_information'];
  if (isset($herb_line['Type information']) && ($herb_line['Type information']) != '') 
    {
    $type_info_test = str_replace(" for ","|||for|||",$herb_line['Type information']);
    $type_info_test = str_replace("<","|||&lt;",$type_info_test);
    $type_info_test = str_replace(">","&gt;<br />|||",$type_info_test);
    $type_info_test = str_replace("Current Name: ","Current Name:|||",$type_info_test);
    $type_info_test = str_replace("Current Name: ","Current Name:|||",$type_info_test);
    $type_info_arr = explode("|||",$type_info_test);
    $type_info_arr_count = count($type_info_arr);
    
    if (isset($type_info_arr[0]) && ($type_info_arr[0]) != '') $type = $type_info_arr[0];
    if (isset($type_info_arr[1]) && ($type_info_arr[1]) == 'for' && isset($type_info_arr[2]) && ($type_info_arr[2]) != '')
      {
      $type_taxa = namensaufspaltung_hybrid($type_info_arr[2]);
      $type_info_arr[2] = "<span style=\"color: red;\">".$type_taxa['html']."</span>";
      }
    if (isset($type_info_arr[6]) && ($type_info_arr[6]) == 'Current Name:' && isset($type_info_arr[7]) && ($type_info_arr[7]) != '')
      {
      $type_acc_taxa = namensaufspaltung_hybrid($type_info_arr[7]);
      $type_info_arr[7] = $type_acc_taxa['html'];
      }
      
     unset($type_info_arr[0]); # damit $type nicht nochmals in $type_info enthalten ist
    foreach ($type_info_arr as $type_info_line)
      {
      $type_info .= $type_info_line." ";
      }
     $type_info = str_replace("  "," ",$type_info);
     $type_info = str_replace(" )",")",$type_info);
     $type_info = trim($type_info);
    }
  

  
  $fundort = '';
  $land = '';
  $provinz = '';
  $vergleichs_fundort = '';
  $vergleichslaenge = '';
  if (isset($herb_line['Label']) && ($herb_line['Label']) != '')
    {
    $fundort = $herb_line['Label'];
    if (isset($herb_line['Country']) && ($herb_line['Country']) != '')
      {
      $vergleichs_fundort = $fundort;
      $vergleichs_fundort = str_replace(" ","",$vergleichs_fundort);
      $vergleichs_fundort = str_replace(".","",$vergleichs_fundort);
      $vergleichs_fundort = str_replace(",","",$vergleichs_fundort);
      $vergleichs_fundort = str_replace(";","",$vergleichs_fundort);
      $vergleichs_fundort = str_replace("-","",$vergleichs_fundort);
      $vergleichs_fundort = str_replace("Österreich","Austria",$vergleichs_fundort);
      $vergleichslaenge = strlen($herb_line['Country']);
      #var_dump("1: ".substr($vergleichs_fundort,0,$vergleichslaenge));
      #var_dump("2: ".$herb_line['Country']);
      if (substr($vergleichs_fundort,0,$vergleichslaenge) != $herb_line['Country'])  $land = $herb_line['Country'].". "; # wenn nicht schon im fundort-string enthalten, land ergänzen
      }
    if (isset($herb_line['Country']) && ($herb_line['Country']) != '' && isset($herb_line['Admin1']) && ($herb_line['Admin1']) != '')
      {
      $vergleichs_fundort = $fundort;
      $vergleichs_fundort = str_replace(" ","",$vergleichs_fundort);
      $vergleichs_fundort = str_replace(".","",$vergleichs_fundort);
      $vergleichs_fundort = str_replace(",","",$vergleichs_fundort);
      $vergleichs_fundort = str_replace(";","",$vergleichs_fundort);
      $vergleichs_fundort = str_replace("-","",$vergleichs_fundort);
      $vergleichs_fundort = str_replace("Österreich","Austria",$vergleichs_fundort);
      $vergleichs_fundort = str_replace("LowerAustria","Niederösterreich",$vergleichs_fundort);
      $vergleichs_fundort = str_replace("UpperAustria","Oberösterreich",$vergleichs_fundort);
      $vergleichs_fundort = str_replace("NiederAustria","Niederösterreich",$vergleichs_fundort);
      $vergleichs_fundort = str_replace("OberAustria","Oberösterreich",$vergleichs_fundort);
      $vergleichs_fundort = str_replace("Tyrol","Tirol",$vergleichs_fundort);
      $vergleichs_fundort = str_replace("Carinthia","Kärnten",$vergleichs_fundort);
      $vergleichs_fundort = str_replace("Vienna","Wien",$vergleichs_fundort);
      $vergleichslaenge = strlen($herb_line['Country'].$herb_line['Admin1']);
      #var_dump("3: ".substr($vergleichs_fundort,0,$vergleichslaenge));
      #var_dump("4: ".$herb_line['Country'].$herb_line['Admin1']);
      if (substr($vergleichs_fundort,0,$vergleichslaenge) != $herb_line['Country'].$herb_line['Admin1']) # wenn nicht schon im fundort-string enthalten, land+bundesland ergänzen
	  {
	  $bundeslandlaenge = strlen($herb_line['Admin1']);
	  if (substr($vergleichs_fundort,0,$bundeslandlaenge) == $herb_line['Admin1'])
	    {
	    $land = $herb_line['Country'].", ";
	    }
	  else
	    {
	    $land = $herb_line['Country'].", ";
	    $provinz = $herb_line['Admin1'].": ";
	    }
	  }
      }
    }

  
  
  
  $gps = '';
  #if (isset($herb_line['Latitude']) && ($herb_line['Latitude']) != '') $gps .= $herb_line['Latitude'];
  #if (isset($herb_line['Longitude']) && ($herb_line['Longitude']) != '') $gps .= $herb_line['Longitude'];
  if (isset($herb_line['Latitude']) && ($herb_line['Latitude']) != '' && isset($herb_line['Longitude']) && ($herb_line['Longitude']) != '')
    {
    if (substr($herb_line['Latitude'],0,1) == "-") { $nordsued = "S"; $herb_line['Latitude'] = str_replace("-","",$herb_line['Latitude']); }
    else $nordsued = "N";
    if (substr($herb_line['Longitude'],0,1) == "-") { $ostwest = "W"; $herb_line['Longitude'] = str_replace("-","",$herb_line['Longitude']); }
    else $ostwest = "E";
    $koord_arr = gps_gesamt_ausgabe($herb_line['Latitude'].$nordsued.", ".$herb_line['Longitude'].$ostwest);
    $gps .= "Koordinaten: ".$koord_arr['gradminsec'];
    }
  if (isset($herb_line['Exactness']) && ($herb_line['Exactness']) != '') $gps .= " [±".$herb_line['Exactness']."&#x202F;m]";
  if (isset($herb_line['exactness']) && ($herb_line['exactness']) != '') $gps .= " [±".$herb_line['exactness']."&#x202F;m]";

    
  $hoehe = '';
  if (isset($herb_line['Altitude lower']) && ($herb_line['Altitude lower']) != '') $hoehe = $herb_line['Altitude lower'];
  if (isset($herb_line['Altitude_lower']) && ($herb_line['Altitude_lower']) != '') $hoehe = $herb_line['Altitude_lower'];
  if (isset($herb_line['Altitude higher']) && $herb_line['Altitude higher'] != '') $hoehe .= " - ".$herb_line['Altitude higher']; 
  if (isset($herb_line['Altitude_higher']) && $herb_line['Altitude_higher'] != '') $hoehe .= " - ".$herb_line['Altitude_higher']; 
  if ($hoehe == '') $hoehe = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
  $hoehe = "Meereshöhe: ".$hoehe." m. s. m.";

  $quadrant = '';
  if (isset($herb_line['Quadrant']) && ($herb_line['Quadrant']) != '') 
    {
    if (strpos($herb_line['Quadrant'],"/") >= 1) $quadrant = "Grundfeld: ".str_replace("/"," - Quadrant: ",$herb_line['Quadrant']);
    else if (isset($herb_line['Quadrant_sub']) && $herb_line['Quadrant_sub'] != '') $quadrant = "Grundfeld: ".$herb_line['Quadrant']." - Quadrant: ".$herb_line['Quadrant_sub'];
    else $quadrant = "Grundfeld: ".$herb_line['Quadrant']."- Quadrant:";
    }
    
  $habitat = 'Standort (Substrat): ';
  if (isset($herb_line['habitat']) && ($herb_line['habitat']) != '') $habitat .= $herb_line['habitat'];
  
  $habitus = '';
 # if (isset($herb_line['habitus ']) && ($herb_line['habitus ']) != '') $habitus = "Habitus: ".$herb_line['habitus '];
  if (isset($herb_line['habitus']) && ($herb_line['habitus']) != '') $habitus = "Habitus: ".$herb_line['habitus'];

  
  $leg = 'Leg.: ';
  if (isset($herb_line['First_collector'])) 
	{
	if (!$herb_line['Add_collectors']) $leg .= $herb_line['First_collector'];
	else
	  {
	  $pos_und = strpos($herb_line['Add_collectors'], "&");
	  if ($pos_und <= 1) $leg .= $herb_line['First_collector']." & ".$herb_line['Add_collectors'];
	  else $leg .= $herb_line['First_collector'].", ".$herb_line['Add_collectors'];
	  }
	}
  else if (isset($herb_line['Collector all']) && ($herb_line['Collector all']) != '')    $leg .= $herb_line['Collector all'];
  else if (isset($herb_line['Collector']) && ($herb_line['Collector']) != '') $leg .= $herb_line['Collector'];
    
  $tmp_dat_1 = '';
  $tmp_dat_2 = '';
  $datum = 'Leg. Datum: ';
  if (isset($herb_line['Date']) && ($herb_line['Date']) != '') $datum .= date("d.m.Y", strtotime($herb_line['Date']));
  if (isset($herb_line['Coll_Date']) && ($herb_line['Coll_Date']) != '') $tmp_dat_1 = date("d.m.Y", strtotime($herb_line['Coll_Date']));
  if (isset($herb_line['Coll_Date_2']) && ($herb_line['Coll_Date_2']) != '') $tmp_dat_2 = date("d.m.Y", strtotime($herb_line['Coll_Date_2']));
 
 if ($tmp_dat_1 != "01.01.1970" && $herb_line['Coll_Date'] != "1970-01-01") $datum .= $tmp_dat_1;
 else $datum .= $herb_line['Coll_Date'];
 
 if ($tmp_dat_2 != "01.01.1970" && $herb_line['Coll_Date_2'] != "1970-01-01" && $herb_line['Coll_Date_2'] != '') $datum .= "&ndash;".$tmp_dat_2;
 else if ( $herb_line['Coll_Date_2'] != '') $datum .= "&ndash;".$herb_line['Coll_Date_2'];
  
  $det_datum = 'Det. Datum: ';
  $det = 'Det.: ';
  if (isset($herb_line['det_rev_conf']) && ($herb_line['det_rev_conf']) != '') $herb_line['det./rev./conf./assigned'] = $herb_line['det_rev_conf'];  
  if (isset($herb_line['det./rev./conf./assigned']) && ($herb_line['det./rev./conf./assigned']) != '') 
    {
    $det_temp = trim($herb_line['det./rev./conf./assigned']);
    $det_zerlegt = explode(" ",$det_temp);
    $det_anzahl = count($det_zerlegt)-1;
    $det_datum_temp = $det_zerlegt[$det_anzahl];

    $det_neu = str_replace(" ".$det_datum_temp,"",$det_temp);
	if ((((strlen($det_datum_temp) == 4) and (is_numeric($det_datum_temp)))or 
		((strlen($det_datum_temp) == 7) and (substr_count($det_datum_temp, "-") ==1)) or 
		(((strlen($det_datum_temp) == 9) or (strlen($det_datum_temp) == 10)) and (substr_count($det_datum_temp, "-") ==2)) and (strlen($det_neu) >=3)))
		{
		$det .= $det_neu;
		$det_datum .= $det_datum_temp;
		} 
	else
	  {
	  $det .= $det_temp;
	  }
    }
 
  $rev = 'Rev.: ';
  $rev_datum = 'Rev. Datum: ';
  
  $sammelnr = "";
  if (isset($herb_line['First_collectors_number']) && ($herb_line['First_collectors_number']) != '') $sammelnr .= "Sammel-Nr.: ".$herb_line['First_collectors_number'];
  if (isset($herb_line['Alt_number']) && ($herb_line['Alt_number']) != '') $sammelnr .= "Sammel-Nr.: ".$herb_line['Alt_number'];
  if (isset($herb_line['CollNo']) && ($herb_line['CollNo']) != '') $sammelnr .= "Sammel-Nr.: ".$herb_line['CollNo'];

  $serie = "";
  if (isset($herb_line['Series']) && ($herb_line['Series']) != '') 
    {
    $serie .= "Serie: ".$herb_line['Series'];
    if (isset($herb_line['Series_number']) && ($herb_line['Series_number']) != '') $sammelnr .= " Nr.: ".$herb_line['Series_number'];
    }
  
  $anmerkungen = '';
  if (isset($herb_line['annotations']) && ($herb_line['annotations']) != '') $anmerkungen = $herb_line['annotations'];

    if ($herb_nr != '' && $herb_nr != 0 && isset($herb_line['stable identifier']) && $herb_line['stable identifier'] != '') 
        {
        #$qr_txt = "https://wu.jacq.org/WU-MYC".$herb_nr;
        $qr_txt = $herb_line['stable identifier'];
        $qrcode_img = "http://api.qrserver.com/v1/create-qr-code/?data=".$qr_txt."&size=100x100";
        $qrcode = "<img style=\"width: 8mm; padding-left: 1mm;\" src=\"".$qrcode_img."\">";
        }
    elseif ($herb_nr != '' && $herb_nr != 0) 
        {
        $qr_txt = "https://wu.jacq.org/WU-MYC".$herb_nr;
        #$qr_txt = $herb_line['stable identifier'];
        $qrcode_img = "http://api.qrserver.com/v1/create-qr-code/?data=".$qr_txt."&size=100x100";
        $qrcode = "<img style=\"width: 8mm; padding-left: 1mm;\" src=\"".$qrcode_img."\">";
        }
    else $qrcode = '';

  
  
  $html_2_labels_temp = '<table cellSpacing="0" class="outer2"  style="page-break-inside:avoid;"  width="100%">
  
  <tbody  style="vertical-align:top;">
  <tr>
    <td style="height: 130mm; vertical-align:top; padding-left: 1em; padding-right: 1em;">
      <table cellSpacing="0" class="inner" width="100%" style=" vertical-align:top;" >
	<tbody style="vertical-align:top;">
	  <tr>
	    <td style="text-align: center; font-size: 9.8pt; vertical-align:top;">'.$herb_title.'</td>
	    <td rowspan = "2" style="text-align: center; vertical-align:top; padding-bottom:0.15em; border-bottom:1px solid black;">'.$qrcode.'</td>
	  </tr>
	  <tr style = "border-bottom:solid;">
	    <td style="text-align: center; font-size: 9.7pt; padding-bottom:0.15em;  vertical-align:top; border-bottom:1px solid black;">'.$herb_subtitle.'</td>
	  </tr>
	  <tr>
	    <td style="height: 2em; text-align: center; font-size: 10pt; vertical-align:top; padding-top:0.5em;">'.$taxon_html.'</td>
	  </tr>
	  <tr>
	    <td style="height: 2em; text-align: center; font-size: 10pt; color: red; vertical-align:middle;">'.$type.'</td>
	  </tr>
	  <tr>
	    <td style="height: 3em; text-align: center; font-size: 7pt; vertical-align:top;">'.$type_info.'</td>
	  </tr>
	  <tr>
	    <td style="height: 4em;text-align: left; font-size: 10pt; vertical-align:top;">'.$land.$provinz.$fundort.'</td>
	  </tr>
	  <tr>
	    <td style="text-align: left; font-size: 10pt; vertical-align:top;">'.$gps.'</td>
	  </tr>
	  <tr>
	    <td style="height: 1em; text-align: left; font-size: 10pt; vertical-align:top;">'.$hoehe.'</td>
	  </tr>
	  <tr>
	    <td style="height: 1em; text-align: left; font-size: 10pt; vertical-align:top;">'.$quadrant.'</td>
	  </tr>
	  <tr>
	    <td style="height: 3em; text-align: left; font-size: 10pt; vertical-align:top;">'.$habitat.'</td>
	  </tr>
	  <tr>
	    <td style="text-align: left; font-size: 10pt; vertical-align:top;">'.$habitus.'</td>
	  </tr>
	  <tr>
	    <td style="height: 1em; text-align: left; font-size: 10pt; vertical-align:top; padding-top:0.4em;">'.$leg.'</td>
	  </tr>
	  <tr>
	    <td style="height: 1em; text-align: left; font-size: 10pt; vertical-align:top; padding-bottom:0.3em;">'.$datum.'</td>
	  </tr>
	  <tr>
	    <td style="height: 1em; text-align: left; font-size: 10pt; vertical-align:top; padding-top:0.3em;">'.$det.'</td>
	  </tr>
	  <tr>
	    <td style="height: 1em; text-align: left; font-size: 10pt; vertical-align:top; padding-bottom:0.3em;">'.$det_datum.'</td>
	  </tr>
	  <tr>
	    <td style="height: 1em; text-align: left; font-size: 10pt; vertical-align:top; padding-top:0.3em;">'.$rev.'</td>
	  </tr>
	  <tr>
	    <td style="height: 1em; text-align: left; font-size: 10pt; vertical-align:top; padding-bottom:0.4em;">'.$rev_datum.'</td>
	  </tr>
	  <tr>
	    <td style="height: 1em; text-align: left; font-size: 10pt; vertical-align:top;">'.$sammelnr.'</td>
	  </tr>
	  <tr>
	    <td style=" text-align: left; font-size: 10pt; vertical-align:top;">'.$serie.'</td>
	  </tr>
	  <tr>
	    <td style="height: 1em; text-align: left; font-size: 10pt; vertical-align:top;">'.$anmerkungen.'</td>
	  </tr>
	</tbody>
      </table>
    </td>
  </tr>
  </tbody>
</table>
<div class = "linebreakfix">&nbsp;</div>'; # diese zeile verhindert zeilenumbruch im etikett!!!

  if  ($taxon_html != '')
    {
    if ($pp == 4)
      {
      $html_2_labels_temp .= "<pagebreak />";
      $pp = 0;
      }
    $html2_labels = $html2_labels.$html_2_labels_temp;
    $mpdf->WriteHTML($html2_labels);   
    $html2_labels = '';
    }
$herb_line['Admin1'] = '';
$herb_line['Label'] = '';
unset($herb_line);
}


$html_3 = '

<p>&nbsp;</p>


</body>
';

$mpdf->WriteHTML($html_3);

#$html =$html_1.$html2_labels.$html_3;
#echo "<pre>";
#var_dump($html);
#echo "</pre>";
//==============================================================
//==============================================================
//==============================================================

#$mpdf=new mPDF('utf8','A4','','',10,10,10,10,10,10); 
/*$mpdf = new \Mpdf\Mpdf([
       'mode' => 'utf-8',
       'format' => 'A4',
       'margin_left' => '9',
       'margin_right' => '9',
       'margin_top' => '10',
       'margin_bottom' => '10'
 ]); 

$mpdf->SetDisplayMode('fullpage');
$mpdf->shrink_tables_to_fit=0; # verhindert, daß schrift automatisch kleiner wird, wenn zu wenig platz
#$mpdf->SetColumns(2,'J');
#$mpdf->WriteHTML($loremH,2);

$mpdf->list_indent_first_level = 0;	// 1 or 0 - whether to indent the first level of a list
*/
// LOAD a stylesheet
#$stylesheet = file_get_contents('mpdfstyletables.css');
#$mpdf->WriteHTML($stylesheet,1);	// The parameter 1 tells that this is css/style only and no body/html/text

#$mpdf->WriteHTML($html);

$mpdf->Output('labels_myk_belege.pdf','D');
exit;


?>
