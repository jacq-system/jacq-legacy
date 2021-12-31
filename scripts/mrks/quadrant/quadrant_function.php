<?php 

####################################################################################
// ####### Quadranten+Koordinanten-Funktionen: Version 1.7.1 ## 2021-02-09 ############
####################################################################################


#header("Content-type: text/html; charset=UTF-8");

include("geo_utm.php");
#echo "CCCC";

# error_reporting(E_ALL); 




function gradminsec_trennung($trenn_daten) /* Hilfsfunktion für Funktion "gps_trennung" um Daten im Format "XX.XXXX_grad_" bzw. "XX_grad_XX_min_XX_sec_" in grad/min/sec auftrennen */
	{
	#echo "<pre>";
	#var_dump($trenn_daten);
	#if ($trenn_daten == 'CEERD_NNLAT_DEGREE_grad_LAT_MINUTE_min_LAT_') return null;
	if (!is_string($trenn_daten)) return null;
	unset($getrennt_daten);
	$getrennt_daten = 0;
	$temp_grad = 0;
	$temp_min = 0;
	$pos_grad = 0;
	$pos_min = 0;
	$pos_sec = 0;
	#$getrennt_daten['sec_komma'] = '';
	$pos_grad = strpos($trenn_daten,"_grad_");
	$pos_min = strpos($trenn_daten,"_min_");
	$pos_sec = strpos($trenn_daten,"_sec_");
	if (isset($pos_grad))
		{
		$getrennt_daten = array("grad" => substr($trenn_daten,0,$pos_grad)); /* Grad-Wert auslesen */
		$grad_komma_pos = strpos ($getrennt_daten['grad'],".");
		#echo "GR!\n";
		#echo "GRAD_POS";
		#var_dump($grad_komma_pos);
		#var_dump($getrennt_daten);
		/*$getrennt_daten['grad'] = substr($getrennt_daten['grad'],0,$grad_komma_pos); */
		if ($grad_komma_pos > 0)
			{
			$temp_grad = $getrennt_daten['grad'];
			$getrennt_daten['grad'] = substr($getrennt_daten['grad'],0,$grad_komma_pos);
			$getrennt_daten['min'] = ((($temp_grad-$getrennt_daten['grad'])*60)); /*grad-nachkommastellen in min umrechnen */
			$min_komma_pos = strpos ($getrennt_daten['min'],".");
			#echo "MIN_POS";
			#var_dump($min_komma_pos);
			#var_dump($getrennt_daten);
			if ($min_komma_pos > 0)
				{
				$temp_min = $getrennt_daten['min'];
				$getrennt_daten['min'] = substr($getrennt_daten['min'],0,$min_komma_pos);
				$getrennt_daten['sec'] = round((60*($temp_min-$getrennt_daten['min']))); /*min-nachkommastellen in sec umrechnen + runden*/
				$getrennt_daten['sec_komma'] = (round(((60*($temp_min-$getrennt_daten['min'])))*100)/100); /*min-nachkommastellen in sec umrechnen + runden*/
				$getrennt_daten['sec_komma1'] = (round(((60*($temp_min-$getrennt_daten['min'])))*10)/10); /*min-nachkommastellen in sec umrechnen + runden*/
				}
			else
				{
				$temp_min = $getrennt_daten['min'];
				$getrennt_daten['min'] = $getrennt_daten['min'];
				$getrennt_daten['sec'] = 0; /*min-nachkommastellen in sec umrechnen + runden*/
				$getrennt_daten['sec_komma'] = 0;; /*min-nachkommastellen in sec umrechnen + runden*/
				$getrennt_daten['sec_komma1'] = 0;; /*min-nachkommastellen in sec umrechnen + runden*/
				}
			}
		/*if (!is_numeric($getrennt_daten["grad"])) return null; */
		}
	else
		{
		return null;
		}
	if ($pos_min > 0)
		{
		/* if (isset($temp_grad)) return null; /* falls kommastellen bei gradangaben und zusätzlich min-angaben vorhanden ... */
		$getrennt_daten["min"]= substr($trenn_daten,$pos_grad+6,$pos_min-$pos_grad-6);
		$min_komma_pos = strpos ($getrennt_daten['min'],".");
		if ($min_komma_pos > 0)
			{
			$temp_min = $getrennt_daten['min'];
			$getrennt_daten['min'] = substr($getrennt_daten['min'],0,$min_komma_pos);
			$getrennt_daten['sec'] = round((60*($temp_min-$getrennt_daten['min']))); /*min-nachkommastellen in sec umrechnen + runden*/
			$getrennt_daten['sec_komma'] = (round(((60*($temp_min-$getrennt_daten['min'])))*100)/100); /*min-nachkommastellen in sec umrechnen + runden*/
			$getrennt_daten['sec_komma1'] = (round(((60*($temp_min-$getrennt_daten['min'])))*10)/10); /*min-nachkommastellen in sec umrechnen + runden*/
			}
		/* if (!(is_numeric($getrennt_daten["min"]))) return null; */
		}
	if ($pos_sec > 0)
		{
		$getrennt_daten["sec"] = substr($trenn_daten,$pos_min+5,$pos_sec-$pos_min-5);
		$getrennt_daten['sec_komma'] = $getrennt_daten["sec"];
		$getrennt_daten['sec_komma1'] = (round(($getrennt_daten["sec"]*10))/10);
		if ($getrennt_daten['sec_komma1'] >= 60) $getrennt_daten['sec_komma1'] = 59.9; #korrektur, damit nicht 60 sekunden durch aufrunden ...
		/*if (!is_numeric($getrennt_daten["sec"])) return null; */
		} 
		/* echo $getrennt_daten["grad"]."° ".$getrennt_daten["min"]."' ".$getrennt_daten["sec"]."\" "; */
	#echo "AAAA------------------------------\n\n";
	#var_dump ($getrennt_daten);
	#echo "EEEE------------------------------\n\n";
	#echo "</pre>";
	return $getrennt_daten;
	}

function gradminsec_fusion($grad,$min=0,$sec=0,$nsow='') /* Hilfsfunktion für Funktion "gps_fusion" um Grad/Min/Sec-angagben in Dezimalgradangaben zu verwandeln */
	{
	$nsow = strtolower($nsow);
	$dezimalausgabe = '';
	if (!isset($grad)) return null;
	$dezimalausgabe = $grad+($min/60)+(($sec/60)/60);	
	if (($nsow == "s") or ($nsow == "w"))
		{
		$dezimalausgabe = $dezimalausgabe*(-1);
		}

	return $dezimalausgabe;
	}





function gps_trennung($gps_daten) /* WGS84-Koordinaten in Grad min sec standardisiert auftrennen */
	{
 	#var_dump($gps_daten);
  # echo "\nGPS-DATEN__: ".$gps_daten."\n";
	$breite_daten = '';
	$laenge_daten = '';
	# print "<p>gps_trennung</p>";
	if (!is_string($gps_daten)) return null;
	#echo "\n???\n";
	unset($getrennt_daten);
	/* 16°41'43.9'' Ost,  47°54'49.4'' Nord => Purbach */
	$lage = "";
	$anfangsnr = 0;
	$zweitestellenr = 0;
	$grundzahl = 0;
	$dreivierzahl = 0;
	$feld = 0;
	$orig_eingabe = 0;

	$orig_eingabe = htmlentities($gps_daten);
	$orig_eingabe = str_replace("&Acirc;","",$orig_eingabe);
/* ++++++++++++++++++++++++++++++ Eingabe standardisieren ... ++++++++++++++++++++++++*/
	$gps_daten = strtoupper($gps_daten);
  #  echo "\nGPS-DATEN0: ".$gps_daten."\n";
	$gps_daten = str_replace("O","E",$gps_daten);
	$gps_daten = str_replace("EAST","E",$gps_daten);
	$gps_daten = str_replace("OST","E",$gps_daten);
	$gps_daten = str_replace("NORD","N",$gps_daten);
	$gps_daten = str_replace("NORTH","N",$gps_daten);
	$gps_daten = str_replace("SOUTH","S",$gps_daten);
	$gps_daten = str_replace("SÜD","S",$gps_daten);
	$gps_daten = str_replace("SUED","S",$gps_daten);
	$gps_daten = str_replace("WEST","W",$gps_daten);
	$gps_daten = str_replace("°","_grad_",$gps_daten);
	$gps_daten = str_replace("\"","_sec_",$gps_daten);
	$gps_daten = str_replace("″","_sec_",$gps_daten);
	$gps_daten = str_replace("´´","_sec_",$gps_daten);
	$gps_daten = str_replace("``","_sec_",$gps_daten);
    $gps_daten = str_replace("′′","_sec_",$gps_daten);
	$gps_daten = str_replace("'","_min_",$gps_daten);
	$gps_daten = str_replace("′","_min_",$gps_daten);
	$gps_daten = str_replace("´","_min_",$gps_daten);
	$gps_daten = str_replace("`","_min_",$gps_daten);
	$gps_daten = str_replace(",",".",$gps_daten);
	$gps_daten = str_replace(" ","",$gps_daten);
	$gps_daten = str_replace("O.","E",$gps_daten);
	$gps_daten = str_replace("E.","E",$gps_daten);
	$gps_daten = str_replace(".O","E",$gps_daten);
	$gps_daten = str_replace(".E","E",$gps_daten);
	$gps_daten = str_replace(".N","N",$gps_daten);
	$gps_daten = str_replace("N.","N",$gps_daten);
	$gps_daten = str_replace(".S","S",$gps_daten);
	$gps_daten = str_replace("S.","S",$gps_daten);
	$gps_daten = str_replace(".W","W",$gps_daten);
	$gps_daten = str_replace("W.","W",$gps_daten);
	$gps_daten = str_replace("_min__min_","_sec_",$gps_daten);

	/*$gps_daten = adresszeilenparameter_beschraenkung($gps_daten);*/
	$gps_daten = htmlentities($gps_daten);
  #  echo "\nGPS-DATEN: ".$gps_daten."\n";
/* ++++++++++++++ ermitteln ob zuerst N oder O eingegenben bzw. ob N/O vorangestellt oder nachgestellt angegeben ++++++++++++++++++++ */
	$pos_n = 0;
	$pos_o = 0;
	$pos_s = 0;
	$pos_w = 0;
	$vorangestellt = 0;
	$nordsued = 'N';
	$ostwest = 'E';
	$ersteszeichen = substr($gps_daten,0,1);
	$pos_n = strpos($gps_daten,"_N");
#	$pos_n1 = strpos($gps_daten,"N");
	$pos_o = strpos($gps_daten,"_E");
#	$pos_o1 = strpos($gps_daten,"O");
	$pos_s = strpos($gps_daten,"_S");
#	$pos_s1 = strpos($gps_daten,"S");
	$pos_w = strpos($gps_daten,"_W");
#	$pos_w1 = strpos($gps_daten,"W");

/* ++++ da ursprünglich nur für Mitteleuropa konzipiert => gepfuschte Erweiterung für Rest der Welt, wo auch S- und W-Koordinaten ...  */
if (($pos_s > 0) or ($ersteszeichen == "S"))
	{
	$nordsued = "S";
	$gps_daten = str_replace("S","N",$gps_daten);
	$pos_n = $pos_s;
	$pos_s = 0;
	if ($ersteszeichen == "S") {$ersteszeichen = "N";}
	}
if (($pos_w > 0) or ($ersteszeichen == "W"))
	{
	$ostwest = "W";
	$gps_daten = str_replace("W","E",$gps_daten);
	$pos_o = $pos_w;
	$pos_w = 0;
	if ($ersteszeichen == "W") {$ersteszeichen = "E";}
	}



	if (($pos_n == 0) or ($pos_o == 0)) /* wennn N/O am Anfang der Kooordinaten angebeben */
		{
		#$pos_n = 0;
		#$pos_o = 0;
		#$breite_daten = '';
		#$laenge_daten = '';
		$pos_n = strpos($gps_daten,"N");
		$pos_o = strpos($gps_daten,"E");
		$vorangestellt = 1;
		# echo "TTT";
		}
		# echo "<p>+++++".$pos_n."--".$pos_o."--".$vorangestellt."+++++</p>"; 

	if (($vorangestellt == 0) and (isset($pos_n)) and (isset($pos_o))) /* in längen- und breiten-daten aufspalten */
		{
		if ($pos_n < $pos_o)
			{
			$breite_daten = substr($gps_daten,0,$pos_n+1);
			$laenge_daten = substr($gps_daten,$pos_n+2,$pos_o-$pos_n-1);
			}
		if ($pos_n > $pos_o)
			{
			$laenge_daten = substr($gps_daten,0,$pos_o+1);
			$breite_daten = substr($gps_daten,$pos_o+2,$pos_n-$pos_o-1);
			}
	}
		
	if (($vorangestellt == 1))
		{
		# echo " EEE: = ".$ersteszeichen." !!! <br />";
		if (($pos_n < $pos_o) and ($ersteszeichen == "N"))
		#if (($ersteszeichen == "N"))
			{
			$breite_daten = substr($gps_daten,1,$pos_o-1);
			$laenge_daten = substr($gps_daten,$pos_o+1);
			# echo "NNNNN breite: ".$breite_daten." länge: ".$laenge_daten."<br />";			
			}
		if (($pos_n > $pos_o) and ($ersteszeichen == 'E'))
		#if (($ersteszeichen == 'O'))
			{
			$laenge_daten = substr($gps_daten,1,$pos_n-1);
			$breite_daten = substr($gps_daten,$pos_n+1);
			# echo "OOOOOO breite: ".$breite_daten." länge: ".$laenge_daten."<br />";
			}
		}
$gradminsec_breite = '';
$gradminsec_laenge = '';
	$gradminsec_breite = gradminsec_trennung($breite_daten);
	$gradminsec_laenge = gradminsec_trennung($laenge_daten);
	#echo "<pre>";
	#echo "\nGPStrennung Anfang\n%%%%%%%%%%%%%%%%%%%%%%%%%%%\n\n";
	
	#var_dump($gradminsec_breite);
	#var_dump($gradminsec_laenge);
	#echo "\nGPStrennung Ende\n%%%%%%%%%%%%%%%%%%%%%%%%%%%\n\n";
	#echo "</pre>";
	unset($koordinaten);
	if (isset($gradminsec_breite['grad']) and
		isset($gradminsec_breite['min']) and
		isset($gradminsec_breite['sec']) and
		isset($gradminsec_breite['sec_komma']) and
		isset($gradminsec_breite['sec_komma1']) and
		isset($gradminsec_breite) and
		isset($gradminsec_laenge['grad']) and
		isset($gradminsec_laenge['min']) and
		isset($gradminsec_laenge['sec']) and
		isset($gradminsec_laenge['sec_komma']) and
		isset($gradminsec_laenge['sec_komma1']) and
		isset($gradminsec_laenge))
		{
		$koordinaten = array(	"grad_laenge" => $gradminsec_laenge['grad'],
						"min_laenge" => $gradminsec_laenge['min'],
						"sec_laenge" => $gradminsec_laenge['sec'],
						"sec_laenge_komma" => $gradminsec_laenge['sec_komma'],
						"sec_laenge_komma1" => $gradminsec_laenge['sec_komma1'],
						"ausr_laenge" => $ostwest,
						"grad_breite" => $gradminsec_breite['grad'],
						"min_breite" => $gradminsec_breite['min'],
						"sec_breite" => $gradminsec_breite['sec'],
						"sec_breite_komma" => $gradminsec_breite['sec_komma'],
						"sec_breite_komma1" => $gradminsec_breite['sec_komma1'],
						"ausr_breite" => $nordsued);
		if (!($koordinaten["grad_laenge"]) and !($koordinaten["min_laenge"]) and !($koordinaten["sec_laenge"])) return 0;
		if (!($koordinaten["grad_breite"]) and !($koordinaten["min_breite"]) and !($koordinaten["sec_breite"])) return 0;
		#echo "<pre>";
		#var_dump ($koordinaten);
		#echo "</pre>";
		return $koordinaten;
		}
	else
		{
		#echo "TTTTTTTTTTTTTTTTTTTTTTTTTTTTTTTTTT";
		return null;
		}
	
/* echo "<p>";
 echo $koordinaten["grad_laenge"]." // ";
 echo $koordinaten["min_laenge"]." // ";
 echo $koordinaten["sec_laenge"]." // ";
 echo $koordinaten["ausr_laenge"]." // ";
 echo $koordinaten["grad_breite"]." // ";
 echo $koordinaten["min_breite"]." // ";
 echo $koordinaten["sec_breite"]." // "; 
 echo $koordinaten["ausr_breite"]." // ";
echo "</p>";
*/

	}

function quadrant_arr_neu($koordinaten) # Quadrantennummer aus Array (=Ausgabe von Funktion "gps-Trennung") ermitteln verbesserte version
	{
	$laenge_grad = $koordinaten['grad_laenge'];
	$laenge_min = $koordinaten['min_laenge'];
	$laenge_sec = $koordinaten['sec_laenge'];
	$laenge_orientierung = $koordinaten['ausr_laenge'];

	$breite_grad = $koordinaten['grad_breite'];
	$breite_min = $koordinaten['min_breite'];
	$breite_sec = $koordinaten['sec_breite']; 
	$breite_orientierung = $koordinaten['ausr_breite'];
	
	if ($breite_orientierung == "S") return 0; # weil nur für Mitteleuropa keine S und W Koordinaten erlaubt
	if ($laenge_orientierung == "W") return 0;

	$anfangsnr = 55-$breite_grad;

	$zweitestellenr = 10-round(($breite_min+3)/6);
	$zweitestellenr_genau = 10-(($breite_min+3)/6);
	$zweitestellenr_delta = $zweitestellenr-$zweitestellenr_genau;
	if ($zweitestellenr_delta < 0) { $lage = "S"; } 
	if ($zweitestellenr_delta >= 0) { $lage = "N"; } 
# echo $zweitestellenr_delta." - ".$lage;


	$grundzahl = (($laenge_grad-6)*6)+2;

	$dreivierzahl = $grundzahl+floor(($laenge_min)/10);
	$dreivierzahl_genau = $grundzahl+(($laenge_min)/10);
	$dreivierzahl_delta = $dreivierzahl_genau-$dreivierzahl;
	if ($dreivierzahl_delta < 0.5) { $lage = $lage."W"; } 
	if ($dreivierzahl_delta >= 0.5) { $lage = $lage."E"; } 



	if ($dreivierzahl < 10) {$grundfeldnr = $anfangsnr.$zweitestellenr."0".$dreivierzahl;}
	else {$grundfeldnr = $anfangsnr.$zweitestellenr.$dreivierzahl;}


	if ($lage == "NW") {$feld = 1;}
	if ($lage == "NE") {$feld = 2;}
	if ($lage == "SW") {$feld = 3;}
	if ($lage == "SE") {$feld = 4;}


/* Berechung der Quadrantengrenzen */
	$abrund_laenge_min = floor($laenge_min/5)*5;
	$abrund_breite_min = floor($breite_min/3)*3;
	$aufrund_laenge_min = $abrund_laenge_min+5;
	if ($aufrund_laenge_min == 60) {$aufrund_laenge_min = 0; $aufrund_laenge_grad = $laenge_grad+1;}
	else { $aufrund_laenge_grad = $laenge_grad;}
	$aufrund_breite_min = $abrund_breite_min+3;
	if ($aufrund_breite_min == 60) {$aufrund_breite_min = 0; $aufrund_breite_grad = $breite_grad+1;}
	else { $aufrund_breite_grad = $breite_grad;}

# ermittelt mittelpunt des quadranten
	$mitte_breite_grad = $breite_grad;
	$mitte_breite_min = $abrund_breite_min + 1;
	if ($mitte_breite_min == 60)
		{
		$mitte_breite_min = 0;
		$mitte_breite_grad = $mitte_breite_grad+1;
		}
	$mitte_laenge_min = $abrund_laenge_min + 2;



	unset ($quadrant_ausgabe);
	#In Zeile unterhalb werden koordinaten auf bestimmtes Gebiet eingeschränkt: ggf. ändern!!!
	if (($laenge_grad and $breite_grad) and ($laenge_grad >= 6) and ($laenge_grad <= 21) and ($breite_grad >= 44) and ($breite_grad <= 55))
		{
		$quadrant_ausgabe = array (	"grundfeld" => $grundfeldnr,
									"quadrant" => $feld,
									"nord_grad" => $aufrund_breite_grad,
									"nord_min" => $aufrund_breite_min,
									"sued_grad" => $breite_grad,
									"sued_min" => $abrund_breite_min,
									"west_grad" => $laenge_grad,
									"west_min" => $abrund_laenge_min,
									"ost_grad" => $aufrund_laenge_grad,
									"ost_min" => $aufrund_laenge_min,
									'mitte_breite_grad' => $mitte_breite_grad,
									'mitte_breite_min' => $mitte_breite_min,
									'mitte_breite_sec' => 30,
									'mitte_laenge_grad' => $laenge_grad,
									'mitte_laenge_min' => $mitte_laenge_min,
									'mitte_laenge_sec' => 30);
		}
	else 
		{
		return null;
		}
	return $quadrant_ausgabe;	
	}

	
	
	
	
	
	
	
	

function quadrant_arr($koordinaten) /* Quadrantennummer aus Array (=Ausgabe von Funktion "gps-Trennung") ermitteln*/
	{
	$grundzahl = '';
	$anfangsnr = '';
	# print "QQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQ";
	# if (!is_array($koordinaten)) return null; 

/* echo "<p>";
 echo $koordinaten["grad_laenge"]." // ";
 echo $koordinaten["min_laenge"]." // ";
 echo $koordinaten["sec_laenge"]." // ";
 echo $koordinaten["ausr_laenge"]." // ";
 echo $koordinaten["grad_breite"]." // ";
 echo $koordinaten["min_breite"]." // ";
 echo $koordinaten["sec_breite"]." // "; 
 echo $koordinaten["ausr_breite"]." // ";
echo "</p>";
*/

	$laenge_grad = $koordinaten['grad_laenge'];
	$laenge_min = $koordinaten['min_laenge'];
	$laenge_sec = $koordinaten['sec_laenge'];
	$laenge_orientierung = $koordinaten['ausr_laenge'];

	$breite_grad = $koordinaten['grad_breite'];
	$breite_min = $koordinaten['min_breite'];
	$breite_sec = $koordinaten['sec_breite']; 
	$breite_orientierung = $koordinaten['ausr_breite'];

	if ($breite_orientierung == "S") return 0;
	if ($laenge_orientierung == "W") return 0;

	if ($breite_grad == 44) {$anfangsnr = 11;}
	if ($breite_grad == 45) {$anfangsnr = 10;}
	if ($breite_grad == 46) {$anfangsnr = 9;}
	if ($breite_grad == 47) {$anfangsnr = 8;}
	if ($breite_grad == 48) {$anfangsnr = 7;}
	if ($breite_grad == 49) {$anfangsnr = 6;}
	if ($breite_grad == 50) {$anfangsnr = 5;}
	if ($breite_grad == 51) {$anfangsnr = 4;}
	if ($breite_grad == 52) {$anfangsnr = 3;}
	if ($breite_grad == 53) {$anfangsnr = 2;}
	if ($breite_grad == 54) {$anfangsnr = 1;}
	if ($breite_grad == 55) {$anfangsnr = 0;}


	if ($breite_min >= 0 && $breite_min < 3) {($zweitestellenr = 9); $lage="S";}
	if ($breite_min >= 3 && $breite_min < 6) {($zweitestellenr = 9); $lage="N";}
	if ($breite_min >= 6 && $breite_min < 9) {($zweitestellenr = 8); $lage="S";}
	if ($breite_min >= 9 && $breite_min < 12) {($zweitestellenr = 8); $lage="N";}
	if ($breite_min >= 12 && $breite_min < 15) {($zweitestellenr = 7); $lage="S";}
	if ($breite_min >= 15 && $breite_min < 18) {($zweitestellenr = 7); $lage="N";}
	if ($breite_min >= 18 && $breite_min < 21) {($zweitestellenr = 6); $lage="S";}
	if ($breite_min >= 21 && $breite_min < 24) {($zweitestellenr = 6); $lage="N";}
	if ($breite_min >= 24 && $breite_min < 27) {($zweitestellenr = 5); $lage="S";}
	if ($breite_min >= 27 && $breite_min < 30) {($zweitestellenr = 5); $lage="N";}
	if ($breite_min >= 30 && $breite_min < 33) {($zweitestellenr = 4); $lage="S";}
	if ($breite_min >= 33 && $breite_min < 36) {($zweitestellenr = 4); $lage="N";}
	if ($breite_min >= 36 && $breite_min < 39) {($zweitestellenr = 3); $lage="S";}
	if ($breite_min >= 39 && $breite_min < 42) {($zweitestellenr = 3); $lage="N";}
	if ($breite_min >= 42 && $breite_min < 45) {($zweitestellenr = 2); $lage="S";}
	if ($breite_min >= 45 && $breite_min < 48) {($zweitestellenr = 2); $lage="N";}
	if ($breite_min >= 48 && $breite_min < 51) {($zweitestellenr = 1); $lage="S";}
	if ($breite_min >= 51 && $breite_min < 54) {($zweitestellenr = 1); $lage="N";}
	if ($breite_min >= 54 && $breite_min < 57) {($zweitestellenr = 0); $lage="S";}
	if ($breite_min >= 57 && $breite_min < 60) {($zweitestellenr = 0); $lage="N";}

	if ($laenge_grad == 6) {$grundzahl = 2;}
	if ($laenge_grad == 7) {$grundzahl = 8;}
	if ($laenge_grad == 8) {$grundzahl = 14;}
	if ($laenge_grad == 9) {$grundzahl = 20;}
	if ($laenge_grad == 10) {$grundzahl = 26;}
	if ($laenge_grad == 11) {$grundzahl = 32;}
	if ($laenge_grad == 12) {$grundzahl = 38;}
	if ($laenge_grad == 13) {$grundzahl = 44;}
	if ($laenge_grad == 14) {$grundzahl = 50;}
	if ($laenge_grad == 15) {$grundzahl = 56;}
	if ($laenge_grad == 16) {$grundzahl = 62;}
	if ($laenge_grad == 17) {$grundzahl = 68;}
	if ($laenge_grad == 18) {$grundzahl = 74;}
	if ($laenge_grad == 19) {$grundzahl = 80;}
	if ($laenge_grad == 20) {$grundzahl = 86;}
	if ($laenge_grad == 21) {$grundzahl = 92;}



	if ($laenge_min >= 0 && $laenge_min < 5) {($dreivierzahl = $grundzahl+0);  $lage=$lage."W";}
	if ($laenge_min >= 5 && $laenge_min < 10) {($dreivierzahl = $grundzahl+0); $lage=$lage."E";}
	if ($laenge_min >= 10 && $laenge_min < 15) {($dreivierzahl = $grundzahl+1); $lage=$lage."W";}
	if ($laenge_min >= 15 && $laenge_min < 20) {($dreivierzahl = $grundzahl+1); $lage=$lage."E";}
	if ($laenge_min >= 20 && $laenge_min < 25) {($dreivierzahl = $grundzahl+2); $lage=$lage."W";}
	if ($laenge_min >= 25 && $laenge_min < 30) {($dreivierzahl = $grundzahl+2); $lage=$lage."E";}
	if ($laenge_min >= 30 && $laenge_min < 35) {($dreivierzahl = $grundzahl+3); $lage=$lage."W";}
	if ($laenge_min >= 35 && $laenge_min < 40) {($dreivierzahl = $grundzahl+3); $lage=$lage."E";}
	if ($laenge_min >= 40 && $laenge_min < 45) {($dreivierzahl = $grundzahl+4); $lage=$lage."W";}
	if ($laenge_min >= 45 && $laenge_min < 50) {($dreivierzahl = $grundzahl+4); $lage=$lage."E";}
	if ($laenge_min >= 50 && $laenge_min < 55) {($dreivierzahl = $grundzahl+5); $lage=$lage."W";}
	if ($laenge_min >= 55 && $laenge_min < 60) {($dreivierzahl = $grundzahl+5); $lage=$lage."E";}

	if ($dreivierzahl < 10) {$grundfeldnr = $anfangsnr.$zweitestellenr."0".$dreivierzahl;}
	else {$grundfeldnr = $anfangsnr.$zweitestellenr.$dreivierzahl;}


	if ($lage == "NW") {$feld = 1;}
	if ($lage == "NE") {$feld = 2;}
	if ($lage == "SW") {$feld = 3;}
	if ($lage == "SE") {$feld = 4;}



	$abrund_laenge_min = floor($laenge_min/5)*5;
	$abrund_breite_min = floor($breite_min/3)*3;
	$aufrund_laenge_min = $abrund_laenge_min+5;
	if ($aufrund_laenge_min == 60) {$aufrund_laenge_min = 0; $aufrund_laenge_grad = $laenge_grad+1;}
	else { $aufrund_laenge_grad = $laenge_grad;}
	$aufrund_breite_min = $abrund_breite_min+3;
	if ($aufrund_breite_min == 60) {$aufrund_breite_min = 0; $aufrund_breite_grad = $breite_grad+1;}
	else { $aufrund_breite_grad = $breite_grad;}


	unset ($quadrant_ausgabe);
	if (($laenge_grad and $breite_grad) and ($laenge_grad >= 6) and ($laenge_grad <= 21) and ($breite_grad >= 44) and ($breite_grad <= 55))
		{
		$quadrant_ausgabe = array (	"grundfeld" => $grundfeldnr,
									"quadrant" => $feld,
									"nord_grad" => $aufrund_breite_grad,
									"nord_min" => $aufrund_breite_min,
									"sued_grad" => $breite_grad,
									"sued_min" => $abrund_breite_min,
									"west_grad" => $laenge_grad,
									"west_min" => $abrund_laenge_min,
									"ost_grad" => $aufrund_laenge_grad,
									"ost_min" => $aufrund_laenge_min);
		}
	else 
		{
		return null;
		}

	return $quadrant_ausgabe;
	}


function quadrant($laenge_grad,$laenge_min, $breite_grad, $breite_min) /* Quadrantennummer aus getrennten WGS84-Koordinaten ermitteln*/
	{
	if (!is_int($laenge_grad)) return null;
	if (!is_int($laenge_min)) return null;
	if (!is_int($breite_grad)) return null;
	if (!is_int($breite_min)) return null;
	
	unset($koordinaten);
	$koordinaten = array(	'grad_laenge' => $laenge_grad,
						'min_laenge' => $laenge_min,
						'sec_laenge' => 0,
						'grad_breite' => $breite_grad,
						'min_breite' => $breite_min,
						'sec_breite' => 0);
						
	unset($quadrant_ausgabe);
	$quadrant_ausgabe = quadrant_arr($koordinaten);
	
	return $quadrant_ausgabe;
	}


function koord_in_dezimalgrad($gps_daten) # Koodridonaten in Dezimalgrad ausgeben
	{
	if (!is_string($gps_daten)) return null;
	
	unset($utm_ausgabe);
	unset($koordinaten);
	$koordinaten = gps_trennung($gps_daten); # Eingabedaten standarisieren und auftrennen 
	#var_dump($koordinaten);
	$breite = $koordinaten['grad_breite'] + ($koordinaten['min_breite']/60) + (($koordinaten['sec_breite_komma']/60)/60);
	$laenge = $koordinaten['grad_laenge'] + ($koordinaten['min_laenge']/60) + (($koordinaten['sec_laenge_komma']/60)/60);
	if ($koordinaten['ausr_breite'] == 'S')
		{
		$breite = $breite * (-1);
		}
	if ($koordinaten['ausr_laenge'] == 'W')
		{
		$laenge = $laenge * (-1);
		}
	#echo $laenge." ";
	#echo $breite." ";
	 
	#unset($quadrant_ausgabe);
	#$quadrant_ausgabe = quadrant_arr_neu($koordinaten); # quadrantennr. ermitteln 
	#$utm_ausgabe = geo2utm($laenge, $breite);
	#echo "XXXX ";
	#var_dump($utm_ausgabe);
	
	$dez_ausgabe = array ('laenge_dez' => $laenge, 'breite_dez' => $breite);
	return $dez_ausgabe;
	}



#include ("./geo_utm.php"); # Function für UTM-Konvertierung
function koord_in_utm_umwandlung($gps_daten) # Koodridonaten in UTM konvertieren mittels function "geo_utm"
	{
	if (!is_string($gps_daten)) return null;
	
	unset($utm_ausgabe);
	unset($koordinaten);
	$koordinaten = gps_trennung($gps_daten); # Eingabedaten standarisieren und auftrennen 
	#var_dump($koordinaten);
	$breite = $koordinaten['grad_breite'] + ($koordinaten['min_breite']/60) + (($koordinaten['sec_breite_komma']/60)/60);
	$laenge = $koordinaten['grad_laenge'] + ($koordinaten['min_laenge']/60) + (($koordinaten['sec_laenge_komma']/60)/60);
	if ($koordinaten['ausr_breite'] == 'S')
		{
		$breite = $breite * (-1);
		}
	if ($koordinaten['ausr_laenge'] == 'W')
		{
		$laenge = $laenge * (-1);
		}
	#echo $laenge." ";
	#echo $breite." ";
	 
	#unset($quadrant_ausgabe);
	#$quadrant_ausgabe = quadrant_arr_neu($koordinaten); # quadrantennr. ermitteln 
	$utm_ausgabe = geo2utm($laenge, $breite);
	#echo "XXXX ";
	#var_dump($utm_ausgabe);
	return $utm_ausgabe;
	}

function koord_in_mgr_umwandlung($gps_daten) # Koodridonaten in UTM konvertieren mittels function "geo_utm"
	{
	if (!is_string($gps_daten)) return null;
	
	unset($mgr_ausgabe);
	unset($utm_ausgabe);
	$utm_ausgabe = koord_in_utm_umwandlung($gps_daten);

	$mgr_ausgabe = utm2mgr($utm_ausgabe['zone'], $utm_ausgabe['ew'], $utm_ausgabe['nw']);
	#echo "XXXX ";
	#var_dump($utm_ausgabe);
	return $mgr_ausgabe;
	}



function quadrant_gps($gps_daten) # Quadrantennummer aus diversen WGS84-Koordinaten ermitteln
	{
	if (!is_string($gps_daten)) return null;
	
	unset($koordinaten);
	$koordinaten = gps_trennung($gps_daten); # Eingabedaten standarisieren und auftrennen 

	unset($quadrant_ausgabe);
	$quadrant_ausgabe = quadrant_arr($koordinaten); # quadrantennr. ermitteln 

	return $quadrant_ausgabe;
	}



function quadrant_gps_neu($gps_daten) # Quadrantennummer aus diversen WGS84-Koordinaten ermitteln mittels neuer Berechnungsmethode
	{
	if (!is_string($gps_daten)) return null;
	
	unset($koordinaten);
	$koordinaten = gps_trennung($gps_daten); # Eingabedaten standarisieren und auftrennen 
	 
	unset($quadrant_ausgabe);
	$quadrant_ausgabe = quadrant_arr_neu($koordinaten); # quadrantennr. ermitteln 

	return $quadrant_ausgabe;
	}

function koord_von_mgr_umrechnen($raster, $ew2, $nw2) # konvertiert mgrs-koordinaten ("utm-raster-koordinaten walter") in normale utm- und gps-koordinaten
	{
	$utm_arr = mgr2utm($raster, $ew2, $nw2);
	#	$utm_array = array('zone' => $zone, 'ew' => $ew, 'nw' => $nw);
	#var_dump($utm_arr);

	$koord_arr = utm2geo($utm_arr['zone'],$utm_arr['ew'], $utm_arr['nw']);
	#var_dump($koord_arr);
	#$return_koord = array("lw" => $lw, "bw" => $bw);
	if ($koord_arr['lw'] >= 0)
		{
		$laenge = $koord_arr['lw']."° E";
		}
	else
		{
		$laenge = $koord_arr['lw']."° W";
		}
		
	if ($koord_arr['bw'] >= 0)
		{
		$breite = $koord_arr['bw']."° N";
		}
	else
		{
		$breite = $koord_arr['bw']."° S";
		}
	# mrks am 13. 8. 2015: E durch N und W durch S (und umgekehrt) ersetzt => überprüfen, ob alles stimmt!!!!)

/* 	$koordinaten_laenge = gps_trennung($laenge);
	$koordinaten_breite = gps_trennung($breite);
var_dump($koordinaten_laenge);
var_dump($koordinaten_breite);
	*/
	#var_dump($laenge);
	#var_dump($breite);
	
	$koordinaten = gps_trennung($laenge.", ".$breite);
#var_dump($koordinaten);
	
	
	$return_arr = array('utm_zone' => $utm_arr['zone'],
						'utm_ost' => $utm_arr['ew'],
						'utm_nord' => $utm_arr['nw'],
						'laenge_ges' => $breite = $koord_arr['lw'],
						'breite_ges' => $breite = $koord_arr['bw'],
						"grad_laenge" => $koordinaten['grad_laenge'],
						"min_laenge" => $koordinaten['min_laenge'],
						"sec_laenge" => $koordinaten['sec_laenge'],
						"sec_laenge_komma" => $koordinaten['sec_laenge_komma'],
						"sec_laenge_komma1" => $koordinaten['sec_laenge_komma1'],
						"ausr_laenge" => $koordinaten['ausr_laenge'],
						"grad_breite" => $koordinaten['grad_breite'],
						"min_breite" => $koordinaten['min_breite'],
						"sec_breite" => $koordinaten['sec_breite'],
						"sec_breite_komma" => $koordinaten['sec_breite_komma'],
						"sec_breite_komma1" => $koordinaten['sec_breite_komma1'],
						"ausr_breite" => $koordinaten['ausr_breite']);
	#var_dump($return_arr);
	return $return_arr;
	}

function koord_von_utm_umrechnen($raster, $ew2, $nw2) # konvertiert utm-raster-koordinaten gps-koordinaten
	{
	
	$koord_arr = utm2geo($raster,$ew2, $nw2);
	#var_dump($koord_arr);
	#$return_koord = array("lw" => $lw, "bw" => $bw);
	if ($koord_arr['lw'] >= 0)
		{
		$laenge = $koord_arr['lw']."° N";
		}
	else
		{
		$laenge = $koord_arr['lw']."° S";
		}
		
	if ($koord_arr['bw'] >= 0)
		{
		$breite = $koord_arr['bw']."° E";
		}
	else
		{
		$breite = $koord_arr['bw']."° W";
		}

/* 	$koordinaten_laenge = gps_trennung($laenge);
	$koordinaten_breite = gps_trennung($breite);
var_dump($koordinaten_laenge);
var_dump($koordinaten_breite);
	*/
	#var_dump($laenge);
	#var_dump($breite);
	
	$koordinaten = gps_trennung($laenge.", ".$breite);
#var_dump($koordinaten);
	
	
	$return_arr = array('utm_zone' => $raster,
						'utm_ost' => $ew2,
						'utm_nord' => $nw2,
						'laenge_ges' => $breite = $koord_arr['lw'],
						'breite_ges' => $breite = $koord_arr['bw'],
						"grad_laenge" => $koordinaten['grad_laenge'],
						"min_laenge" => $koordinaten['min_laenge'],
						"sec_laenge" => $koordinaten['sec_laenge'],
						"sec_laenge_komma" => $koordinaten['sec_laenge_komma'],
						"sec_laenge_komma1" => $koordinaten['sec_laenge_komma1'],
						"ausr_laenge" => $koordinaten['ausr_laenge'],
						"grad_breite" => $koordinaten['grad_breite'],
						"min_breite" => $koordinaten['min_breite'],
						"sec_breite" => $koordinaten['sec_breite'],
						"sec_breite_komma" => $koordinaten['sec_breite_komma'],
						"sec_breite_komma1" => $koordinaten['sec_breite_komma1'],
						"ausr_breite" => $koordinaten['ausr_breite']);
	#var_dump($return_arr);
	return $return_arr;
	}

	/*
# funktioniert nicht ... bzw. rechnet falsch ...	
# http://mathforum.org/library/drmath/view/51832.html	
function koord_in_ecef_xx($dez_breite,$dez_laenge,$hoehe = 0)
    {
    $a = 6378137; # m (the equatorial earth radius)
    $f = 1/298.257223563; #the "flattening" parameter ( = (a-b)/a ,the ratio of the  difference between the equatorial and polar radii to a; this is a measure of how "elliptical" a polar cross-section is).

    $C = 1/(sqrt((cos($dez_breite) * (cos($dez_breite)) + ((1-$f) * (1-$f)) * (sin($dez_breite) * sin($dez_breite)))));
    $S = ((1-$f) * (1-$f)) * $C;
    
    $h = $hoehe;
    
    $x = ($a*$C + $h) * cos($dez_breite) * cos($dez_laenge);
    $y = ($a*$C + $h) * cos($dez_breite) * sin($dez_laenge);
    $z = ($a*$S + $h) * sin($dez_breite);
    
    $return_arr = array('ecef_x' => $x,
						'ecef_y' => $y,
						'ecef_z' => $z);
						
    return $return_arr;
    }
*/
    
# https://www.oc.nps.edu/oc2902w/coord/llhxyz.htm?source=post_page---------------------------    
function koord_in_ecef($dez_breite,$dez_laenge,$hoehe = 0)
    {
    $a = 6378137; # m (the equatorial earth radius)
    $f = 1/298.257223563; #the "flattening" parameter ( = (a-b)/a ,the ratio of the  difference between the equatorial and polar radii to a; this is a measure of how "elliptical" a polar cross-section is).
    $b = $a * (1 - $f);
    
    $f2 = 1 - $b/$a;
    $eccsq = 1 - $b*$b/($a * $a);
    $ecc = sqrt($eccsq);
    
    $dtr = pi()/180;
    
    $clat = cos($dtr * $dez_breite);
    $slat = sin($dtr * $dez_breite);
    $clon = cos($dtr * $dez_laenge);
    $slon = sin($dtr * $dez_laenge);
    
    $asq = $a*$a;
    $bsq = $b*$b;
    
    $dsq = 1 - $eccsq * $slat * $slat;
    $d = sqrt($dsq);
    
    $rn = $a/$d;
    $rm = $rn * (1 - $eccsq) / $dsq;
    
    $rho = $rn * $clat;
    $z = (1 - $eccsq) * $rn * $slat;
    $rsq = $rho * $rho + $z * $z;
    $r = sqrt($rsq);
    
    #0 r
    #1 rn
    #2 rm
    
    $re = $r;
    $esq = $eccsq;
    
    $x = ($rn + $hoehe) * $clat * $clon;
    $y = ($rn + $hoehe) * $clat * $slon;
    $z = ( (1 - $esq)*$rn + $hoehe) * $slat;
    
  /*  $C = 1/(sqrt(pow(2,cos($dez_breite)) + pow(2,(1-$f)) * pow(2,sin($dez_breite))));
    $S = pow(2,(1-$f)) * $C;
    
    $h = $hoehe;
    
    $x = ($a*$C + $h) * cos($dez_breite) * cos($dez_laenge);
    $y = ($a*$C + $h) * cos($dez_breite) * sin($dez_laenge);
    $z = ($a*$S + $h) * sin($dez_breite);*/
    
    $return_arr = array('ecef_x' => $x,
						'ecef_y' => $y,
						'ecef_z' => $z);
						
    return $return_arr;
    }

#kugel
# https://www.oc.nps.edu/oc2902w/coord/llhxyz.htm?source=post_page---------------------------    
function koord_in_ecef_2($dez_breite,$dez_laenge,$hoehe = 0)
    {
    $a = 6378137; # m (the equatorial earth radius)
    $f = 1/298.257223563; #the "flattening" parameter ( = (a-b)/a ,the ratio of the  difference between the equatorial and polar radii to a; this is a measure of how "elliptical" a polar cross-section is).
   # $b = $a * (1 - $f);
    $b = $a ; # kugel??
    
    $f2 = 1 - $b/$a;
    $eccsq = 1 - $b*$b/($a * $a);
    $ecc = sqrt($eccsq);
    
    $dtr = pi()/180;
    
    $clat = cos($dtr * $dez_breite);
    $slat = sin($dtr * $dez_breite);
    $clon = cos($dtr * $dez_laenge);
    $slon = sin($dtr * $dez_laenge);
    
    $asq = $a*$a;
    $bsq = $b*$b;
    
    $dsq = 1 - $eccsq * $slat * $slat;
    $d = sqrt($dsq);
    
    $rn = $a/$d;
    $rm = $rn * (1 - $eccsq) / $dsq;
    
    $rho = $rn * $clat;
    $z = (1 - $eccsq) * $rn * $slat;
    $rsq = $rho * $rho + $z * $z;
    $r = sqrt($rsq);
    
    #0 r
    #1 rn
    #2 rm
    
    $re = $r;
    $esq = $eccsq;
    
    $x = ($rn + $hoehe) * $clat * $clon;
    $y = ($rn + $hoehe) * $clat * $slon;
    $z = ( (1 - $esq)*$rn + $hoehe) * $slat;
    
  /*  $C = 1/(sqrt(pow(2,cos($dez_breite)) + pow(2,(1-$f)) * pow(2,sin($dez_breite))));
    $S = pow(2,(1-$f)) * $C;
    
    $h = $hoehe;
    
    $x = ($a*$C + $h) * cos($dez_breite) * cos($dez_laenge);
    $y = ($a*$C + $h) * cos($dez_breite) * sin($dez_laenge);
    $z = ($a*$S + $h) * sin($dez_breite);*/
    
    $return_arr = array('ecef_x' => $x,
						'ecef_y' => $y,
						'ecef_z' => $z);
						
    return $return_arr;
    }
	

function gps_gesamt_ausgabe($gps_koordinaten_input,$input='gradminsec')
  {
#echo "\n\n-------GPS function START ---\n";  
 # echo "\nInput: ".$gps_koordinaten_input."\n";
 # echo "\n".$input."\n";
  #var_dump($input); 
  if ($input == 'gradminsec')
    {
   # $rep_str = "37°56'15\" N, 23°56'39\" O";
   $gps_koordinaten_input = str_replace("&#039;","'",$gps_koordinaten_input);
   #$gps_koordinaten_input = str_replace("\"","''",$gps_koordinaten_input);
   #$gps_koordinaten_input = str_replace($rep_str,"37°56'15''N23°56'39''E",$gps_koordinaten_input);
 #  $gps_koordinaten_input = str_replace('"',"\'\'",$gps_koordinaten_input);
  # $gps_koordinaten_input = str_replace('"',"\'\'",$gps_koordinaten_input);
   $gps_koordinaten_input = str_replace("&quot;","''",$gps_koordinaten_input);
  # echo "\nvor trennung: ".$gps_koordinaten_input."\n";
    $gps_koordinaten = gps_trennung($gps_koordinaten_input);
    #var_dump($gps_koordinaten);
    }
  elseif ($input == 'mgrs')
    {
    $mgrs_input = explode(" ",$gps_koordinaten_input); 
    $gps_koordinaten = koord_von_mgr_umrechnen($mgrs_input[0],$mgrs_input[1],$mgrs_input[2]);
    }
  elseif ($input == 'mgrs_km')
    {
    $mgrs_input = str_replace(" ","",$gps_koordinaten_input); 
    $mgrs_input1 = substr($mgrs_input,0,-4);
    $mgrs_input2 = (substr($mgrs_input,-4,-2))."500";
    $mgrs_input3 = (substr($mgrs_input,-2))."500";
   # var_dump($mgrs_input);
   # var_dump($mgrs_input1);
    #var_dump($mgrs_input2);
   # var_dump($mgrs_input3);
    $gps_koordinaten = koord_von_mgr_umrechnen($mgrs_input1,$mgrs_input2,$mgrs_input3);
   # var_dump ($gps_koordinaten_input);
    #var_dump ($gps_koordinaten);
    }
  elseif ($input == 'utm')
    {
    $utm_input = explode(" ",$gps_koordinaten_input); 
    $gps_koordinaten = koord_von_utm_umrechnen($utm_input[0],$utm_input[1],$utm_input[2]);
    }
 $ausgabe_gradminsec = $gps_koordinaten['grad_breite']."° ".$gps_koordinaten['min_breite']."' ".$gps_koordinaten['sec_breite_komma']."\" ".$gps_koordinaten['ausr_breite'].", ".$gps_koordinaten['grad_laenge']."° ".$gps_koordinaten['min_laenge']."' ".$gps_koordinaten['sec_laenge_komma']."\" ".$gps_koordinaten['ausr_laenge'];
 $ausgabe_gradminsec1 = $gps_koordinaten['grad_breite']."° ".$gps_koordinaten['min_breite']."′ ".$gps_koordinaten['sec_breite_komma1']."″ ".$gps_koordinaten['ausr_breite'].", ".$gps_koordinaten['grad_laenge']."° ".$gps_koordinaten['min_laenge']."′ ".$gps_koordinaten['sec_laenge_komma1']."″ ".$gps_koordinaten['ausr_laenge'];
 #echo "\n".$ausgabe_gradminsec."\n\n";
 $dezimalgrad_breite = gradminsec_fusion($gps_koordinaten['grad_breite'],$gps_koordinaten['min_breite'],$gps_koordinaten['sec_breite_komma'],$gps_koordinaten['ausr_breite']);
 $dezimalgrad_laenge = gradminsec_fusion($gps_koordinaten['grad_laenge'],$gps_koordinaten['min_laenge'],$gps_koordinaten['sec_laenge_komma'],$gps_koordinaten['ausr_laenge']);
 $ausgabe_dezimalgrad =  $dezimalgrad_breite."° ".$gps_koordinaten['ausr_breite'].", ".$dezimalgrad_laenge."° ".$gps_koordinaten['ausr_laenge'];
 
  $utm_arr = koord_in_utm_umwandlung($ausgabe_gradminsec);
  $ausgabe_utm = $utm_arr['zone']." ".$utm_arr['ew']." ".$utm_arr['nw'];
 $mgrs_arr = koord_in_mgr_umwandlung($ausgabe_gradminsec);
 #var_dump($ausgabe_mgrs);
 #$mgrs_arr = explode(" ",$ausgabe_mgrs);
 
 $ausgabe_mgrs = $mgrs_arr['raster']." ".$mgrs_arr['ew']." ".$mgrs_arr['nw'];
 $ausg_mgrs_1 = floor($mgrs_arr['ew']/1000);
 $ausg_mgrs_2 = floor($mgrs_arr['nw']/1000);
 
 if ($ausg_mgrs_1 <= 9) { $ausg_mgrs_1 = "0".$ausg_mgrs_1; }
 if ($ausg_mgrs_2 <= 9) { $ausg_mgrs_2 = "0".$ausg_mgrs_2; }
 $ausgabe_mgrs_km_feld = $mgrs_arr['raster']." ".$ausg_mgrs_1.$ausg_mgrs_2;
 
 $quadrant_arr = quadrant_gps_neu($ausgabe_gradminsec);
 $ausgabe_quadrant = $quadrant_arr['grundfeld']."/".$quadrant_arr['quadrant'];
 
 $ausgabe_gradminsec1 = str_replace("O","E", $ausgabe_gradminsec1);
 
 $ausgabe = array ('gradminsec' =>  $ausgabe_gradminsec,
		  'gradminsec1' =>  $ausgabe_gradminsec1,
		  'dezimalgrad' => $ausgabe_dezimalgrad,
		  'dezimalgrad_breite' => $dezimalgrad_breite,
		  'dezimalgrad_laenge' => $dezimalgrad_laenge,
		  'utm'	=> $ausgabe_utm,
		  'mgrs' => $ausgabe_mgrs,
		  'mgrs_km' => $ausgabe_mgrs_km_feld,
		  'quadrant' => $ausgabe_quadrant);
 #echo "\nAusgabe: #################################\n";
 #var_dump($ausgabe);
 #echo "\nENdeAusgabe #########################\n";
 #var_dump($dezimalgrad_breite);
 if ((($dezimalgrad_breite != 0) and ($dezimalgrad_breite != '')) and (($dezimalgrad_laenge != 0) and ($dezimalgrad_laenge != '')))
  {
#  echo "\nXXXXX\n";
  return $ausgabe;
  }
 else
  {
 # echo "\nNNNN\n";
 # return 0;
  #return $ausgabe;
  }
 }
 
function gps_gesamt_ausgabe2($gps_txt) ### koord ohne °N und °E usw. als Input auch möglich ...
  {
  if ($gps_txt != '')
     {
	  $koord_arr = '';
	  $kmfeld_arr = '';
	  $gps_txt = str_replace("/"," ",$gps_txt);
	  $gps_txt = str_replace("'","'",$gps_txt);
	  $gps_txt = str_replace("“","\"",$gps_txt);
	  
	  $gps_txt = str_replace("′","'",$gps_txt);
	  $gps_txt = str_replace("″","\"",$gps_txt);
	  #echo $gps_genauigkeit."\n";
	  #echo $gps_txt."\n";
	  $koord_tmp = str_replace(" ","",$gps_txt);
	  if (strpos($koord_tmp, '°') >= 1)
		  {
		  $koord_arr = gps_gesamt_ausgabe($gps_txt);
		  }
	  elseif (strpos("a".$koord_tmp, '34SC') == 1 || strpos("a".$koord_tmp, '34SD') == 1 || strpos("a".$koord_tmp, '34SE') == 1)
		  {
		  $gps_txt = str_replace("  "," ",$gps_txt);
		  $gps_txt = str_replace("34S ","34S",$gps_txt);
		  $koord_arr = gps_gesamt_ausgabe($gps_txt,'mgrs');
		  }
	  elseif (strpos("a".$koord_tmp, '34S') == 1)
		  {
		  $koord_arr = gps_gesamt_ausgabe($gps_txt,'utm');
		  }
	  elseif ((strpos($koord_tmp, '.') >= 1  || strpos($koord_tmp, ',') >= 1))
		  {
		  $koord_tmp2 = str_replace(".",",",$gps_txt);
		  $koord_tmp2 = str_replace(", ",",",$koord_tmp2);
		  $koord_tmp2 = str_replace("  "," ",$koord_tmp2);
		  $koord_tmp2 = str_replace(" ",",",$koord_tmp2);
		  $koord_tmp_arr = explode(",",$koord_tmp2);
		  $arr_count = count($koord_tmp_arr);
		  #var_dump($koord_tmp_arr);
		  #echo "<p>arr_count: ".$arr_count."</p>\n";
		  if ($arr_count == 4)
		    {
		    $koord_arr = gps_gesamt_ausgabe($koord_tmp_arr[0].".".$koord_tmp_arr[1]."°N ".$koord_tmp_arr[2].".".$koord_tmp_arr[3]."°E");
		    }
		  }

    $ausgabe = $koord_arr;
    return $ausgabe;
    }
  }
 
 
 ?>
