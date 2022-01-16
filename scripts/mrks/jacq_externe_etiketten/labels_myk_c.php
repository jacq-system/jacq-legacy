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



##################################################################################################################
$html_1 = '
<head>
<style>
table {
	page-break-inside:avoid;
	border-collapse: separate;
	#border: 4px solid #880000;
	padding: 0px;
	margin: 0px 0px;
// 	#empty-cells: hide;
//	background-color:#FFFFFF; /* neccesary for correct alignment of labels !!!!!!!!!!!!!!!! */
}
table.outer2 {
	border-collapse: separate;
	#border: 4px solid #088000;
	padding: 0px 0px;
//	margin: 0px 0px 5px 0px;
	margin: 0px;
	empty-cells: hide;
//	background-color: yellow;
}
table.outer2 td {
	font-family: Sans;
	font: Quivira;
}
table.inner {
	border-collapse: collapse;
//	border: 2px solid #000088;
	padding: 0px;
	margin: 8px 0px 0px 0px;
	empty-cells: show;
//	background-color:#FFCCFF;
}
td {
//	border: 1px solid #008800;
	padding: 0px;
//	background-color:#ECFFDF;
}
table.inner td {
//	#order: 1px solid #000088;
	padding: 0px;
//	font-family: monospace;
//	font-style: italic;
//	font-weight: bold;
//	color: #880000;
//	background-color:#FFECDF;
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


################################################################################################################################################
$pp = 0;
$html2_labels = "";
$herb_subtitle = "";
$herb_title = "INSTITUT FÜR BOTANIK DER UNIVERSITÄT WIEN";


foreach ($csv_neu as $herb_line)
  {
  $pp++;
   $herb_subtitle = "Herbarium WU Mycologicum";
  
  $taxon_arr = $name_aufgespalten = namensaufspaltung_hybrid($herb_line['Taxon']);
  $taxon_html = str_replace(" )",")",$taxon_arr['html']);

  $html_2_labels_temp = '<table cellSpacing="0" class="outer2"  style="page-break-inside:avoid"  width="100%">
  <tbody>
  <tr>
    <td>
      <table cellSpacing="0" class="inner" width="100%" >
	<tbody>
	  <tr style = "border-bottom:solid;">
	    <td style="text-align: center; font-size: 16pt; padding-bottom:0.15em">'.$herb_subtitle.'</td>
	  </tr>
	</tbody>
      </table>
      
    <table cellSpacing="0" class="inner" width="100%">
	<tbody>
	  <tr>
	    <td height="8.1em"></td>
	    <td style="text-align: left; font-size: 12pt; vertical-align:middle; padding-bottom:2.5em;">'.$taxon_html.'</td>
	  </tr>
	</tbody>
      </table>
    </td>
  </tr>
  </tbody>
</table>
<div class = "linebreakfix">&nbsp;</div>'; # diese zeile verhindert zeilenumbruch im etikett!!!

if ($pp == 12)
  {
  $html_2_labels_temp .= "<pagebreak />";
  $pp = 0;
  }

$html2_labels = $html2_labels.$html_2_labels_temp;
}


$html_3 = '

<p>&nbsp;</p>


</body>
';


$html =$html_1.$html2_labels.$html_3;
#echo "<pre>";
#var_dump($html);
#echo "</pre>";
//==============================================================
//==============================================================
//==============================================================

#$mpdf=new mPDF('utf8','A4','','',10,10,10,10,10,10); 
$mpdf = new \Mpdf\Mpdf([
       'mode' => 'utf-8',
       'format' => 'A4',
       'margin_left' => '10',
       'margin_right' => '10',
       'margin_top' => '10',
       'margin_bottom' => '10'
 ]); 

$mpdf->SetDisplayMode('fullpage');
$mpdf->shrink_tables_to_fit=0; # verhindert, daß schrift automatisch kleiner wird, wenn zu wenig platz
#$mpdf->SetColumns(2,'J');
#$mpdf->WriteHTML($loremH,2);

$mpdf->list_indent_first_level = 0;	// 1 or 0 - whether to indent the first level of a list

// LOAD a stylesheet
#$stylesheet = file_get_contents('mpdfstyletables.css');
#$mpdf->WriteHTML($stylesheet,1);	// The parameter 1 tells that this is css/style only and no body/html/text

$mpdf->WriteHTML($html);

$mpdf->Output('labels_myk_boegen.pdf','D');
exit;


?>
