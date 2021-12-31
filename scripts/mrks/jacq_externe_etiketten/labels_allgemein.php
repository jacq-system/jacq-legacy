<?php
header("Content-type: text/html; charset=UTF-8; X-Content-Type-Options: nosniff");

require_once __DIR__ . '/../external_tools/composer/vendor/autoload.php'; # mpdf and phpoffice/phpspreadsheet

require_once ('../quadrant/quadrant_function.php'); # functions to transform and maipulate geographic coodinates and related stuff
require_once ('../functions/namensaufspaltung_functions_agg.php'); # functions to split and rebuild taxa names
require_once ('../functions/read_csv_xlsx_functions.php'); # function to read spreadsheets based on phpoffice/phpspreadsheet
require_once ('./etiketten_functions.php'); # function to read spreadsheets based on phpoffice/phpspreadsheet


 

###################### read input file #################################
$temp = explode(".", $_FILES["file"]["name"]);
$extension = end($temp);
$csv_neu = csv_to_array($_FILES['file']['tmp_name'], ",",$extension);

########################################################################

/* $test = nomalize_input_data($csv_neu);
foreach($test as $test_line)
    {
    var_dump($test_line);
    echo "<p>".$test_line['locality']."</p>";
    echo "<p>".$test_line['date1']."</p>";
    echo "<p>".$test_line['date2']."</p>";
    }
exit; */

/*####################### hier zeug, um daten aus db zu holen ##############################
function validate_date($datum)
  {
  if (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$datum))
    {
        return $datum;
    }else{
        return 0;
    }
  }
*/  


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


  
  
$herb_arr = nomalize_input_data($csv_neu);
  
#$herb_arr = herb_ett_ionprot($where,'','',$preset,$ett_anz);
#var_dump($herb_arr);

####################### ende db-zeug ############################

##################################################################################################################
$html_1 = '
<head>
<link rel="stylesheet" href="./labels_speta.css" >


</head>
<body>
 <columns column-count="2"> 
<!-- # ------------------------------------------------------------------------------------------------------------------------------------ # -->
';
$mpdf->WriteHTML($html_1);


################################################################################################################################################

$html2_labels = "";
$pp = 0;


foreach ($herb_arr as $herb_line)
{
  $pp++;
  
  # label language
  if (strtolower($herb_line['language']) == 'deutsch' || strtolower($herb_line['language']) == 'german')
    {
    $floraof = "Flora von ";
    $ca = "ca. ";
    }
  else
    {
    $floraof = "Flora of ";
    $ca = "c. ";
    }
    
  
  # label title
  $title = '';
  if ($herb_line['series'] != '') 
    {
    if ($herb_line['series_number'] != '') $title = $herb_line['series_number'].". ";
    $title .= $herb_line['series'];
    }
  elseif ($herb_line['country'] != '') $title = $floraof.$herb_line['country'];
  elseif ($herb_line['province'] != '') $title = $floraof.$herb_line['province'];
  else $title = '';
  
  
  
  # coll. no
  if ($herb_line['coll_no'] != '' && $herb_line['coll_no2'] != '') $coll_no = $herb_line['coll_no']." / ".$herb_line['coll_no2'];
  if ($herb_line['coll_no'] != '') $coll_no = $herb_line['coll_no'];
  else $coll_no = $herb_line['coll_no2'];
  
  
  # define date output
  $date = $herb_line['date1'];
  if ($herb_line['date2'] != '') $date .= " &ndash; ".$herb_line['date2'];
  
  #family
  if ($herb_line['family'] != '') $family = $herb_line['family'];
  else $family = '';
  
  #taxon
  if ($herb_line['taxon_html'] != '') $taxon = $herb_line['taxon_html'];
  else $taxon = '';
  
  #locality
  $locality = '';
  if ($herb_line['locality'] != '' && $herb_line['locality_en'] != '') $locality = $herb_line['locality']."<br>".$locality = $herb_line['locality_en'];
  elseif ($herb_line['locality'] != '') $locality = $herb_line['locality'];
  elseif ($herb_line['locality_en'] != '') $locality = $herb_line['localtiy_en'];
  else $locality = '';
  
  
  # coordinates
  if ($herb_line['coordinates_dms'] != '')
    {
    $coordinates = $herb_line['coordinates_dms'];
    if($herb_line['exactness'] != '') $coordinates." [±".$herb_line['exactness']."&thinsp;m]";
    }
  else $coordinates = '';
  
  # altitude
  $altitude = '';
  if ($herb_line['alt_min'] != '')
    {
    if ($herb_line['alt_approx'] == '1') $altitude = $ca; # if alitude is not accurate
    $altitude .= $herb_line['alt_min'];
    if ($herb_line['alt_max'] != '') $altitude .= "&#x200A;&ndash;&#x200A;".$herb_line['alt_max'];
    $altitude .= "&thinsp;m";
    }
  
  # qudadrant
  if ($herb_line['quadrant'] != '') $quadrant = $herb_line['quadrant'];
  else $quadrant = '';
  
  # habitat
  if ($herb_line['habitat'] != '') $habitat = $herb_line['habitat'];
  else $habitat = '';
  
  # habitus
  if ($herb_line['habitus'] != '') $habitus = $herb_line['habitus'];
  else $habitus = '';
  
  # annotations
  $annotations = '';
  if ($herb_line['annotations'] != '') $annotations = $herb_line['annotations'];
  
  # leg (collectors)
  if ($herb_line['collectors'] != '') $leg = $herb_line['collectors'];
  else $leg = '';
  
  
  
  # det
  if ($herb_line['det'] != '') $det = $herb_line['det'];
  else $det = '';
  

$html_2_labels_temp = '
<table class="outer2" style="page-break-inside:avoid"  >
  <tbody>
  <tr>
    <td>
      <table class="inner" >
	<tbody>
	  <tr style="text-align: right; font-size: 10px;">
	    <td>Nr. <b>'.$coll_no.'</b></td>
	    <td class="width33" style="text-align: right;"><b>'.$date.'</b></td>
	  </tr>
	</tbody>
      </table>
   
      <table class="inner" >
	<tbody>
	  <tr>
	    <td style="text-align: center; font-size: 13px;"><u>'.$title.'</u></td>
	  </tr>
	</tbody>
      </table>
      
      <table class="inner" >
	<tbody>
	  <tr>
	    <td style="text-align: right; font-size: 10px;">'.$family.'</td>
	  </tr>
	</tbody>
      </table>
 
       <table class="inner" style="margin: 6px 0px">
	<tbody>
	  <tr>
	    <td style="text-align: left; font-size: 14.5px;">'.$taxon.'</td>
	  </tr>
	</tbody>
      </table>
    
      <table class="inner" >
	<tbody>
	  <tr>
	    <td style="text-align: justify; font-size: 12px;">'.$locality.'</td>
	  </tr>
	</tbody>
      </table>
 
      <table class="inner" style="margin: 8px 0px">
	<tbody>
	  <tr>
	    <td class="width65" style="text-align: left; font-size: 10px;">'.$coordinates.'</td>
	    <td class="width35" style="text-align: right; font-size: 10px;">'.$altitude.'</td>
	  </tr>
	  <tr>
	    <td style="text-align: left; font-size: 10px;">'.$quadrant.'</td>
	    <td style="text-align: right; font-size: 10px;"></td>
	  </tr>
	</tbody>
      </table>

      <table class="inner" style="margin: 0px 0px 6px 0px;">
	<tbody>
	  <tr>
	    <td style="text-align: justify; font-size: 10px;">'.$habitat.'</td>
	  </tr>
	</tbody>
      </table>

      <table class="inner" style="margin: 0px 0px 6px 0px;">
	<tbody>
	  <tr>
	    <td style="text-align: justify; font-size: 10px;">'.$habitus.'</td>
	  </tr>
	</tbody>
      </table>
      
      <table class="inner" >
	<tbody>
	  <tr>
	    <td class="width8_5" style="text-align: left; font-size: 11px;vertical-align:top;"><b>Leg.:</b></td>
	    <td class="width91_5" style="text-align: justify; font-size: 11px;">'.$leg.'</td>
	  </tr>
	  <tr>
	    <td class="width8_5" style="text-align: left; font-size: 11px; vertical-align:top;"><b>Det.:</b></td>
	    <td class="width91_5" style="text-align: justify; font-size: 11px;">'.$det.'</td>
	  </tr>
	</tbody>
      </table>';

   #  if ($herb_line['anmerkungen'] != '')
   #   {
      $html_2_labels_temp .= '
      <table class="inner" style="border-top: 1px solid; margin: 6px 0px;">
	 <tbody>
	  <tr>
	    <td style="text-align: justify; font-size: 10px; padding: 6px 0px 0px 0px;">'.$annotations.'</td>
	  </tr>
	</tbody>
      </table>';
   #   }

 $html_2_labels_temp .= '
    </td>
  </tr>
  <tr style="height: 10px;"><td>&nbsp;</td></tr>
  </tbody>
</table>
<div class = "pagebreakdiv">&nbsp;</div>'; # diese zeile verhindert zeilenumbruch im etikett!!!


    /* if ($pp == 8)
      {
      $html_2_labels_temp .= "<pagebreak />";
      $pp = 0;
      }*/
    $mpdf->WriteHTML($html_2_labels_temp);
#  echo "<pre>".$html_2_labels_temp."</pre>";
    $html2_labels .= $html_2_labels_temp;
   # echo $html2_labels;
    
}


$html_3 = '

<p>&nbsp;</p>


</body>
';

#echo $html_2_labels_temp;
$mpdf->WriteHTML($html_3);

#$html = $html_1.$html2_labels.$html_3;
#$html = "<h1>XXX</h1>";
#echo "XX";
# var_dump ($html);
#echo $html;
#exit;
//==============================================================
//==============================================================
//==============================================================



#$mpdf->WriteHTML($html);

$mpdf->Output('labels_speta.pdf','D');
exit;

?>
