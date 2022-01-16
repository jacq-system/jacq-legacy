<?php
header("Content-type: text/html; charset=UTF-8");

$tablename = "jacq_image_rename";




	
	



# --- zählt anzahl der suchergebnisse .-....---------
/*$ergebnis_anzahl = '';
	$prot_query = "SELECT count(".$tablename.".id) AS count FROM ".$tablename." ".$where.$sort_query;
	#var_dump($prot_query);
	$prot_result = mysqli_query($GLOBALS['dblink'], $prot_query) or die("Anfrage fehlgeschlagen: " . mysqli_error($GLOBALS['dblink']));
	while ($prot_line = mysqli_fetch_array($prot_result, MYSQLI_ASSOC)) 
		{
		$ergebnis_anzahl = $prot_line['count'];
		}
	mysqli_free_result($prot_result);*/
# ---------------------------------------------------

$html_formular .= "<h1>Statistics - JACQ Image Rename</h1>";
#$html_formular .= "<p><a href=\"./index.php?site=protfp2\" target=\"pqebnrtz546346888xbp656fg8\"><b>Neuer Eintrag</b></a>&nbsp;&nbsp;&nbsp;--- <b>Hinweis</b>: Als Wildcard % (beliebig viele Zeichen) bzw. _ (1 unbekanntes Zeichen) verwenden (z.B. im Feld Datum:  \"2016%\" für das Jahr 2016, \"____-05%\" für den Monat Mai)</p>";

$html_formular .= "<form id=\"form1\" method=\"post\" action=\"./index.php?site=img_status&amp;sort1=".$sort1."&amp;sort2=".$sort2."\">";


#$html_formular .= "\t\t<br />\n";


/*$html_formular .= "<span  class=\"rahmen\">";
if ($hidden == "yes") $html_formular .= "<input class=\"rahmen\" type=\"checkbox\" name=\"hidden\" value=\"yes\" checked=\"checked\">";
else $html_formular .= "<input type=\"checkbox\" name=\"hidden\" value=\"yes\">";
$html_formular .= " <b>versteckt</b>&nbsp;";
$html_formular .= "</span>";

*/
$html_formular .= "\t\t<br />\n";









    echo "<script>
            document.getElementById(\"title_id\").innerHTML = 'Image statistics';
          </script>";




# ------------------------------------------------------------------------------------------
# ------------------------------------------------------------------------------------------
# ------------------------------------------------------------------------------------------
# ------------------------------------------------------------------------------------------
// 
#$html_formular .= "<h1>Fundpunkte suchen/editieren</h1>";
$html_formular .= "<table>";
$html_formular .= "<tr><th></th>
		       <th>Date</th>
		       <th>Count</th>
		    </tr>";

$first_date = '';
$last_timestamp_date = '';


		$html_formular .= "<tr>";
		$html_formular .= "<td><b>Total:</b></td>";
		$html_formular .= "<td></td>";
		$html_formular .= "<td></td>";
		$html_formular .= "</tr>";
		    
	#if ($limit == "") $limit = 20;	    
	#$prot_query = "SELECT ".$tablename.".* FROM ".$tablename." WHERE id != 7 ".$sort_query." LIMIT 20";
    $prot_query = "SELECT COUNT(DISTINCT(orig_filename)) AS anz,
                            DATE(min(orig_time)) AS min_time, 
                            DATE(max(timestamp)) AS max_timestamp, 
                            COUNT(DISTINCT(new_filename)) as new_anz,
                            COUNT(DISTINCT(qr_code)) as qr_anz,
                             COUNT(DISTINCT(number)) as h_no_anz,
                             COUNT(DISTINCT(DATE(orig_time))) AS day_anz 
                           FROM ".$tablename." WHERE comment IS NULL";
    #var_dump($prot_query);
	$prot_result = mysqli_query($GLOBALS['dblink'], $prot_query) or die("Anfrage fehlgeschlagen: " . mysqli_error($GLOBALS['dblink']));
	while ($prot_line = mysqli_fetch_array($prot_result, MYSQLI_ASSOC)) 
		{
		$first_date = $prot_line['min_time'];
		$last_timestamp_date = $prot_line['max_timestamp'];
		$effective_days = $prot_line['day_anz'];
		
		$html_formular .= "<tr>";
		$html_formular .= "<td>orig. images</td>";
		$html_formular .= "<td>since ".$prot_line['min_time']."</td>";
		$html_formular .= "<td>".$prot_line['anz']."</td>";
		$html_formular .= "</tr>";
		
		$html_formular .= "<tr>";
		$html_formular .= "<td>created images</td>";
		$html_formular .= "<td>since ".$prot_line['min_time']."</td>";
		$html_formular .= "<td>".$prot_line['new_anz']."</td>";
		$html_formular .= "</tr>";
		
		$html_formular .= "<tr>";
		$html_formular .= "<td>QR codes</td>";
		$html_formular .= "<td>since ".$prot_line['min_time']."</td>";
		$html_formular .= "<td>".$prot_line['qr_anz']."</td>";
		$html_formular .= "</tr>";
		
		$html_formular .= "<tr>";
		$html_formular .= "<td>Specimen No.</td>";
		$html_formular .= "<td>since ".$prot_line['min_time']."</td>";
		$html_formular .= "<td>".$prot_line['h_no_anz']."</td>";
		$html_formular .= "</tr>";
		
		$html_formular .= "<tr>";
		$html_formular .= "<td>Days (pictures taken) count</td>";
		$html_formular .= "<td>since ".$first_date."</td>";
		$html_formular .= "<td>".$prot_line['day_anz']."</td>";
		$html_formular .= "</tr>";
		
		$html_formular .= "<tr>";
		$html_formular .= "<td>Pictures/Day</td>";
		$html_formular .= "<td>since ".$first_date."</td>";
		$html_formular .= "<td>".round($prot_line['new_anz']/$prot_line['day_anz'])."</td>";
		$html_formular .= "</tr>";
		}
	mysqli_free_result($prot_result);
	
	
	
	
	
	$tt = 0;
	$last_time = '';
	$timediff = 0;
    $prot_query = "SELECT orig_time,
                        DATE(orig_time) as orig_date
                    FROM ".$tablename." WHERE comment IS NULL GROUP BY orig_time";
    #var_dump($prot_query);
	$prot_result = mysqli_query($GLOBALS['dblink'], $prot_query) or die("Anfrage fehlgeschlagen: " . mysqli_error($GLOBALS['dblink']));
	while ($prot_line = mysqli_fetch_array($prot_result, MYSQLI_ASSOC)) 
		{
        $tmp_timediff = -1;
        if ($last_time != '') 
            {
            $tmp_timediff = strtotime($prot_line['orig_time']) - $last_time;
           # echo "<p>".$tt." -- ".$prot_line['orig_time']." -- ".$tmp_timediff."</p>";
            }
            
        if ($tmp_timediff <= 600 && $tmp_timediff != -1) # wenn Zeitdifferenz weniger als 10 min ist 
            {
            $tt++;
            $timediff +=$tmp_timediff;
            }
        $last_time = strtotime($prot_line['orig_time']);
		}
	mysqli_free_result($prot_result);
	
	
        $html_formular .= "<tr>";
		$html_formular .= "<td>average time/picture</td>";
		$html_formular .= "<td>since ".$first_date."</td>";
		$html_formular .= "<td>".round($timediff/$tt)." s</td>";
		$html_formular .= "</tr>";

	
	
	
        $html_formular .= "<tr>";
		$html_formular .= "<td><b>Total Errors & Warnings:</b></td>";
		$html_formular .= "<td></td>";
		$html_formular .= "<td></td>";
		$html_formular .= "</tr>";
	

	
	
	
    $prot_query = "SELECT comment,
                        COUNT(comment) AS anz
                           FROM ".$tablename." WHERE comment IS NOT NULL GROUP BY comment ";
    #var_dump($prot_query);
	$prot_result = mysqli_query($GLOBALS['dblink'], $prot_query) or die("Anfrage fehlgeschlagen: " . mysqli_error($GLOBALS['dblink']));
	while ($prot_line = mysqli_fetch_array($prot_result, MYSQLI_ASSOC)) 
		{
		$html_formular .= "<tr>";
		$html_formular .= "<td>".$prot_line['comment']."</td>";
		$html_formular .= "<td>since ".$first_date."</td>";
		$html_formular .= "<td>".$prot_line['anz']."</td>";
		$html_formular .= "</tr>";
		}
	mysqli_free_result($prot_result);
	
	
	
	
		$html_formular .= "<tr>";
		$html_formular .= "<td><b>Recent scan (".$last_timestamp_date."):</b></td>";
		$html_formular .= "<td></td>";
		$html_formular .= "<td></td>";
		$html_formular .= "</tr>";

	
	
    $prot_query = "SELECT COUNT(DISTINCT(orig_filename)) AS anz,
                            DATE(min(orig_time)) AS min_time, 
                            DATE(max(orig_time)) AS max_time, 
                            DATE(max(timestamp)) AS max_timestamp, 
                            COUNT(DISTINCT(new_filename)) as new_anz,
                            COUNT(DISTINCT(qr_code)) as qr_anz,
                             COUNT(DISTINCT(number)) as h_no_anz
                           FROM ".$tablename." WHERE timestamp LIKE \"".$last_timestamp_date."%\" AND comment IS NULL";
    #var_dump($prot_query);
	$prot_result = mysqli_query($GLOBALS['dblink'], $prot_query) or die("Anfrage fehlgeschlagen: " . mysqli_error($GLOBALS['dblink']));
	while ($prot_line = mysqli_fetch_array($prot_result, MYSQLI_ASSOC)) 
		{
		$recent_date = $prot_line['min_time'];
		if ($prot_line['min_time'] != $prot_line['max_time']) $recent_date .= " – ".$prot_line['max_time'];
		
		$html_formular .= "<tr>";
		$html_formular .= "<td>orig. images</td>";
		$html_formular .= "<td>".$recent_date."</td>";
		$html_formular .= "<td>".$prot_line['anz']."</td>";
		$html_formular .= "</tr>";
		
		$html_formular .= "<tr>";
		$html_formular .= "<td>created images</td>";
		$html_formular .= "<td>".$recent_date."</td>";
		$html_formular .= "<td>".$prot_line['new_anz']."</td>";
		$html_formular .= "</tr>";
		
		$html_formular .= "<tr>";
		$html_formular .= "<td>QR codes</td>";
		$html_formular .= "<td>".$recent_date."</td>";
		$html_formular .= "<td>".$prot_line['qr_anz']."</td>";
		$html_formular .= "</tr>";
		
		$html_formular .= "<tr>";
		$html_formular .= "<td>Specimen No.</td>";
		$html_formular .= "<td>".$recent_date."</td>";
		$html_formular .= "<td>".$prot_line['h_no_anz']."</td>";
		$html_formular .= "</tr>";
		}
	mysqli_free_result($prot_result);
	$tt = 0;
	$last_time = '';
	$timediff = 0;
    $prot_query = "SELECT orig_time,
                        DATE(orig_time) as orig_date
                    FROM ".$tablename." WHERE timestamp LIKE \"".$last_timestamp_date."%\" AND comment IS NULL GROUP BY orig_time";
    #var_dump($prot_query);
	$prot_result = mysqli_query($GLOBALS['dblink'], $prot_query) or die("Anfrage fehlgeschlagen: " . mysqli_error($GLOBALS['dblink']));
	while ($prot_line = mysqli_fetch_array($prot_result, MYSQLI_ASSOC)) 
		{
        $tmp_timediff = -1;
        if ($last_time != '') 
            {
            $tmp_timediff = strtotime($prot_line['orig_time']) - $last_time;
           # echo "<p>".$tt." -- ".$prot_line['orig_time']." -- ".$tmp_timediff."</p>";
            }
            
        if ($tmp_timediff <= 600 && $tmp_timediff != -1) # wenn Zeitdifferenz weniger als 10 min ist 
            {
            $tt++;
            $timediff +=$tmp_timediff;
            }
        $last_time = strtotime($prot_line['orig_time']);
		}
	mysqli_free_result($prot_result);
	
	
        $html_formular .= "<tr>";
		$html_formular .= "<td>average time/picture</td>";
		$html_formular .= "<td>".$recent_date."</td>";
		$html_formular .= "<td>".round($timediff/$tt)." s</td>";
		$html_formular .= "</tr>";

        $html_formular .= "<tr>";
		$html_formular .= "<td><b>Recent scan (".$last_timestamp_date.") Errors & Warnings:</b></td>";
		$html_formular .= "<td></td>";
		$html_formular .= "<td></td>";
		$html_formular .= "</tr>";

	
    $prot_query = "SELECT comment,
                        COUNT(comment) AS anz
                           FROM ".$tablename." WHERE timestamp LIKE \"".$last_timestamp_date."%\" AND comment IS NOT NULL GROUP BY comment ";
    #var_dump($prot_query);
	$prot_result = mysqli_query($GLOBALS['dblink'], $prot_query) or die("Anfrage fehlgeschlagen: " . mysqli_error($GLOBALS['dblink']));
	while ($prot_line = mysqli_fetch_array($prot_result, MYSQLI_ASSOC)) 
		{
		$html_formular .= "<tr>";
		$html_formular .= "<td>".$prot_line['comment']."</td>";
		$html_formular .= "<td>".$recent_date."</td>";
		$html_formular .= "<td>".$prot_line['anz']."</td>";
		$html_formular .= "</tr>";
		}
	mysqli_free_result($prot_result);
	
	
	
$html_formular .= "</table>";


?>
