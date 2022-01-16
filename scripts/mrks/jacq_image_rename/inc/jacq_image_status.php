<?php
header("Content-type: text/html; charset=UTF-8");

$tablename = "jacq_image_rename";



$where = 'WHERE ';
$where1 = ' ';
$search_param = "";


if (isset($_REQUEST["limit"]) && $_REQUEST["limit"] != '')
	{
	$limit = $_REQUEST["limit"];
	$limit = str_replace("-"," ",$limit);
	$limit_par = str_replace(" ","-",$limit);
	#$where .= $where1." (mgrs_1x1_km LIKE \"".$limit."\" OR mgrs_1x1_km LIKE \"34S ".$km_feld_suche."\") ";
	#$where1 = " AND ";
	$search_param .= "&amp;limit=".$limit;
	}
else
	{
	$limit = 500;
	}
	

if (isset($_REQUEST['orig_path']) && $_REQUEST['orig_path'] != '') 
	{ 
	$orig_path = $_REQUEST['orig_path'];
	$where .= $where1.'orig_path LIKE \'%'.$orig_path .'%\'';
	$where1 = ' AND ';
	$search_param .= '&amp;orig_path='.$orig_path;
	}
else $orig_path = '';

if (isset($_REQUEST['orig_filename']) && $_REQUEST['orig_filename'] !='') 
	{
	$orig_filename = $_REQUEST['orig_filename'];
	$where .= $where1.'orig_filename LIKE \'%'.$orig_filename.'%\'';
	$where1 = ' AND ';
	$search_param .= '&amp;orig_filename='.$orig_filename;
	}
else $orig_filename = '';

if (isset($_REQUEST['orig_time']) && $_REQUEST['orig_time'] !='') 
	{
	$orig_time = $_REQUEST['orig_time'];
	$where .= $where1.'orig_time LIKE \'%'.$orig_time.'%\'';
	$where1 = ' AND ';
	$search_param .= '&amp;orig_time='.$orig_time;
	}
else $orig_time = '';
	
if (isset($_REQUEST['new_path']) && $_REQUEST['new_path'] !='') 
	{
	$new_path = $_REQUEST['new_path'];
	$where .= $where1.'new_path LIKE \'%'.$new_path.'%\'';
	$where1 = ' AND ';
	$search_param .= '&amp;new_path='.$new_path;
	}
else $new_path = '';

if (isset($_REQUEST['new_filename']) && $_REQUEST['new_filename'] !='') 
	{
	$new_filename = $_REQUEST['new_filename'];
	$where .= $where1.'new_filename LIKE \'%'.$new_filename.'%\'';
	$where1 = ' AND ';
	$search_param .= '&amp;new_filename='.$new_filename;
	}
else $new_filename = '';

if (isset($_REQUEST['qr_code']) && $_REQUEST['qr_code'] !='') 
	{
	$qr_code = $_REQUEST['qr_code'];
	$where .= $where1.'qr_code LIKE \'%'.$qr_code.'%\'';
	$where1 = ' AND ';
	$search_param .= '&amp;qr_code='.$qr_code;
	}
else $qr_code = '';


if (isset($_REQUEST['base_url']) && $_REQUEST['base_url'] != '') 
	{
	$base_url = $_REQUEST['base_url'];
	$where .= $where1.'base_url LIKE \'%'.$base_url.'%\'';
	$where1 = ' AND ';
	$search_param .= '&amp;base_url='.$base_url;
	}
else $base_url = '';

if (isset($_REQUEST['acronym']) && $_REQUEST['acronym'] !='') 
	{
	$acronym = $_REQUEST['acronym'];
	$where .= $where1.'acronym LIKE \'%'.$acronym.'%\'';
	$where1 = ' AND ';
	$search_param .= '&amp;acronym='.$acronym;
	}
else $acronym = '';

if (isset($_REQUEST['number']) && $_REQUEST['number'] !='') 
	{
	$number = $_REQUEST['number'];
	$where .= $where1.'number LIKE \'%'.$number .'%\'';
	$where1 = ' AND ';
	$search_param .= '&amp;number='.$number;
	}
else $number = '';



	
if (isset($_REQUEST['comment']) && $_REQUEST['comment'] !='') 
	{
	$comment = $_REQUEST['comment'];
	$where .= $where1.' comment LIKE \'%'.$comment.'%\'';
	$where1 = ' AND ';
	$search_param .= '&amp;comment='.$comment;
	}
else $comment = '';



if (isset($_REQUEST['timestamp']) && $_REQUEST['timestamp'] !='') 
	{
	$timestamp = $_REQUEST['timestamp'];
	$where .= $where1.'timestamp LIKE \'%'.$timestamp.'%\'';
	$where1 = ' AND ';
	$search_param .= '&amp;timestamp='.$timestamp;
	}
else $timestamp = '';


	
	


$search_param = str_replace(" ","+",$search_param);
$sortlink = "./index.php?site=".$site.$search_param;	
if ($where1 != ' AND ') $where = ' ';

# --- zählt anzahl der suchergebnisse .-....---------
$ergebnis_anzahl = '';
	$prot_query = "SELECT count(".$tablename.".id) AS count FROM ".$tablename." ".$where.$sort_query;
	#var_dump($prot_query);
	$prot_result = mysqli_query($GLOBALS['dblink'], $prot_query) or die("Anfrage fehlgeschlagen: " . mysqli_error($GLOBALS['dblink']));
	while ($prot_line = mysqli_fetch_array($prot_result, MYSQLI_ASSOC)) 
		{
		$ergebnis_anzahl = $prot_line['count'];
		}
	mysqli_free_result($prot_result);
# ---------------------------------------------------

$html_formular .= "<h1>Status - JACQ Image Rename</h1>";
#$html_formular .= "<p><a href=\"./index.php?site=protfp2\" target=\"pqebnrtz546346888xbp656fg8\"><b>Neuer Eintrag</b></a>&nbsp;&nbsp;&nbsp;--- <b>Hinweis</b>: Als Wildcard % (beliebig viele Zeichen) bzw. _ (1 unbekanntes Zeichen) verwenden (z.B. im Feld Datum:  \"2016%\" für das Jahr 2016, \"____-05%\" für den Monat Mai)</p>";

$html_formular .= "<form id=\"form1\" method=\"post\" action=\"./index.php?site=img_status&amp;sort1=".$sort1."&amp;sort2=".$sort2."\">";

$html_formular .= "<b>Anzahl:</b> <select name=\"limit\" style = \"direction: rtl;\">\n";
    $html_formular .= "<option value = \"10\" ";
    if ($limit == 10)  $html_formular .= " selected = \"selected\" ";
    $html_formular .= ">10</option>";

    $html_formular .= "<option value = \"25\" ";
    if ($limit == 25)  $html_formular .= " selected = \"selected\" ";
    $html_formular .= ">25</option>";
    
    
    $html_formular .= "<option value = \"50\" ";
    if ($limit == 50)  $html_formular .= " selected = \"selected\" ";
    $html_formular .= ">50</option>";

    $html_formular .= "<option value = \"100\" ";
    if ($limit == 100)  $html_formular .= " selected = \"selected\" ";
    $html_formular .= ">100</option>";
    
    $html_formular .= "<option value = \"250\" ";
    if ($limit == 250)  $html_formular .= " selected = \"selected\" ";
    $html_formular .= ">250</option>";

    $html_formular .= "<option value = \"500\" ";
    if ($limit == 500)  $html_formular .= " selected = \"selected\" ";
    $html_formular .= ">500</option>";

    $html_formular .= "<option value = \"1000\" ";
    if ($limit == 1000)  $html_formular .= " selected = \"selected\" ";
    $html_formular .= ">1.000</option>";
    
    $html_formular .= "<option value = \"2000\" ";
    if ($limit == 2000)  $html_formular .= " selected = \"selected\" ";
    $html_formular .= ">2.000</option>";

    $html_formular .= "<option value = \"10000\" ";
    if ($limit == 10000)  $html_formular .= " selected = \"selected\" ";
    $html_formular .= ">10.000</option>";
    
    
  $html_formular .= "</select> ";
#$html_formular .= "\t\t<br />\n";


/*$html_formular .= "<span  class=\"rahmen\">";
if ($hidden == "yes") $html_formular .= "<input class=\"rahmen\" type=\"checkbox\" name=\"hidden\" value=\"yes\" checked=\"checked\">";
else $html_formular .= "<input type=\"checkbox\" name=\"hidden\" value=\"yes\">";
$html_formular .= " <b>versteckt</b>&nbsp;";
$html_formular .= "</span>";

*/
$html_formular .= "\t\t<br />\n";






$html_formular .= "\t\t<br />\n";

$html_formular .= "<b>orig. path:</b> <input type=\"text\" id=\"orig_path\"  name=\"orig_path\" value=\"".$orig_path."\" size=\"25\" maxlength=\"100\"> ";
$html_formular .= "<b>orig. file name:</b> <input type=\"text\" id=\"orig_filename\" name=\"orig_filename\" value=\"".$orig_filename."\" size=\"25\" maxlength=\"100\"> ";
$html_formular .= "<b>date/time picture taken:</b> <input type=\"text\" id=\"orig_time\" name=\"orig_time\" value=\"".$orig_time."\" size=\"10\" maxlength=\"100\"> ";
$html_formular .= "\t\t<br />\n";


$html_formular .= "<b>new path:</b> <input type=\"text\" id=\"new_path\"  name=\"new_path\" value=\"".$new_path."\" size=\"25\" maxlength=\"100\"> ";
$html_formular .= "<b>new file name:</b> <input type=\"text\" id=\"new_filename\" name=\"new_filename\" value=\"".$new_filename."\" size=\"25\" maxlength=\"100\"> ";
$html_formular .= "<b>date/time picture renamed:</b> <input type=\"text\" id=\"timestamp\" name=\"timestamp\" value=\"".$timestamp."\" size=\"10\" maxlength=\"100\"> ";
$html_formular .= "\t\t<br />\n";




$html_formular .= "<b>QR-Code:</b> <input type=\"qr_code\" name=\"qr_code\"  value=\"".$qr_code."\" size=\"15\" maxlength=\"50\">";
$html_formular .= "<b>Base URL (QR-Code):</b> <input type=\"base_url\" name=\"base_url\"  value=\"".$base_url."\" size=\"40\" maxlength=\"200\">";
$html_formular .= "<b>Herb. Acronym:</b> <input type=\"acronym\" name=\"acronym\"  value=\"".$acronym."\" size=\"5\" maxlength=\"10\">";

$html_formular .= "<b>Comment:</b> <input type=\"text\" name=\"comment\"  value=\"".$comment."\" size=\"40\" maxlength=\"200\">";


#$html_formular .= "\t\t<br />\n";

#if ($ergebnis_anzahl >= 1000) $ergebnis_anzahl = $ergebnis_anzahl/1000;
$ergebnis_anzahl = number_format($ergebnis_anzahl  , 0 ,  "," , "." ); # fügt 1000er-Trennpunkt ein ...

$html_formular .= "\n<br>\n";
$html_formular .= "\n<br>\n";


$html_formular .= "\n<input type=\"submit\" value=\"filtern\" /> --- ".$ergebnis_anzahl." Einträge gefunden.\n";
#$html_formular .= "\n&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<button form=\"form1\" type=\"reset\">Formular zurücksetzen</button>\n</form>\n";

$html_formular .= "\n</form id=\"form1\">\n";


$html_formular .= "<form id=\"form2\" method=\"post\" action=\"./index.php?site=img_status\">";
#$html_formular .= "\n<button id=\"form2\" type=\"submit\" style=\"float:right;\">Felder zurücksetzen</button>\n";
$html_formular .= "\n</form id=\"form2\"><br>\n";



    echo "<script>
            document.getElementById(\"title_id\").innerHTML = 'Image status';
          </script>";




# ------------------------------------------------------------------------------------------
# ------------------------------------------------------------------------------------------
# ------------------------------------------------------------------------------------------
# ------------------------------------------------------------------------------------------
// 
#$html_formular .= "<h1>Fundpunkte suchen/editieren</h1>";
$html_formular .= "<table>";
$html_formular .= "<tr><th>ID".tabsort('id',$sortlink,"")."</th>
		       <th>Orig. path".tabsort('orig_path',$sortlink,"")."</th>
		       <th>Orig. file name".tabsort('orig_filename',$sortlink,"")."</th>
		       <th>Orig. time".tabsort('orig_time',$sortlink,"")."</th>
		       <th>New path".tabsort('new_path',$sortlink,"")."</th>
		       <th>New file name".tabsort('new_filename',$sortlink,"")."</th>
		       <th>QR code".tabsort('qr_code',$sortlink,"")."</th>
		       <th>Base URL (QR code)".tabsort('base_url',$sortlink,"")."</th>
		       <th>H. acronym".tabsort('acronym',$sortlink,"")."</th>
		       <th>H. No.".tabsort('number',$sortlink,"")."</th>
		       <th>Comment".tabsort('comment',$sortlink,"")."</th>
		       <th>Timestamp".tabsort('timestamp',$sortlink,"")."</th>
		    </tr>";

	#if ($limit == "") $limit = 20;	    
	#$prot_query = "SELECT ".$tablename.".* FROM ".$tablename." WHERE id != 7 ".$sort_query." LIMIT 20";
	$prot_query = "SELECT ".$tablename.".* FROM ".$tablename." ".$where.$sort_query." LIMIT ".$limit;
    #var_dump($prot_query);
	$prot_result = mysqli_query($GLOBALS['dblink'], $prot_query) or die("Anfrage fehlgeschlagen: " . mysqli_error($GLOBALS['dblink']));
	while ($prot_line = mysqli_fetch_array($prot_result, MYSQLI_ASSOC)) 
		{
		$det_anz = '';
		if (substr($prot_line['qr_code'],0,4) == 'http') 
            {
            $qr_code_link = "<a href=\"".$prot_line['qr_code']."\" target=\"345rgwe4356sde\">";
            $qr_code_link2 = "</a>";
            if ($prot_line['comment'] == '') $bg_class = "class = \"bg_green\"";
            elseif ($prot_line['comment'] == 'File rename/copy Error') $bg_class = "class = \"bg_red\"";
            else $bg_class = "class = \"bg_yellow\"";
            }
		else 
            {
            $qr_code_link = "";
            $qr_code_link2 = "";
            $bg_class = "class = \"bg_red\"";
            }
 		
		#$link = "<a href=\"./index.php?site=XXXXXXX&amp;id=".$prot_line['id']."&amp;close=2\" target=\"b44g3jmm4523456aa\">";
		$html_formular .= "<tr>";
		$html_formular .= "<td>".$prot_line['id']."</td>";
		$html_formular .= "<td>".$prot_line['orig_path']."</td>";
		$html_formular .= "<td>".$prot_line['orig_filename']."</td>";
		$html_formular .= "<td>".$prot_line['orig_time']."</td>";
		$html_formular .= "<td>".$prot_line['new_path']."</td>";
		$html_formular .= "<td>".$prot_line['new_filename']."</td>";
		$html_formular .= "<td ".$bg_class.">".$qr_code_link.$prot_line['qr_code'].$qr_code_link2."</td>";
		$html_formular .= "<td>".$prot_line['base_url']."</td>";
		$html_formular .= "<td>".$prot_line['acronym']."</td>";
		$html_formular .= "<td>".$prot_line['number']."</td>";
		$html_formular .= "<td ".$bg_class.">".$prot_line['comment']."</td>";
		#$html_formular .= "<td>".$link.$prot_line['user']."</a></td>";
		$html_formular .= "<td>".$prot_line['timestamp']."</td>";
		$html_formular .= "</tr>";
		}
	mysqli_free_result($prot_result);

	
$html_formular .= "</table>";


?>
