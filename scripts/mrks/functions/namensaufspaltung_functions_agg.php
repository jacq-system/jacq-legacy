<?php

############################################################
### aktuellste version der namensaufspaltungsfunktionen ####
### ver 3.2  - 2021-04-06                               ####
############################################################

function ersterbuchstabe_gross($input)
	{
	$input_gross = ucfirst($input);
	
	#$checkregx = '/(?:[A-Z])(?:.*)/';
	if($input == $input_gross)
		{
		$return_var = 'yes';
		}
	else
		{
		$return_var = 'no';
		}
	return $return_var;
	}



 function buchstaben_normalisierung($input_str)
  {
  if (!is_string($input_str)) return null;
  $output= '';
  $output= mb_strtolower($input_str, 'UTF-8');
  $output= ucfirst($output);
  $output = $output;
   return $output;
  }


 function cf($cf_string) # Hilfsfunktion für namensaufspalung
	{
	#echo "<p>".$cf_string."</p>";
	$cf_name = $cf_string;
	$cf = '';
	$cf_moeglichkeiten = array ('ex. aff.', 'ex.aff.', 'ex aff.', 'ex aff ', 'ex. aff ', 'ex.aff ', 'cf.', 'cf ');
	$cf_moeglichekiten_ausgabe = array ('ex aff.', 'ex aff.', 'ex aff.', 'ex aff.', 'ex aff.', 'ex aff.', 'cf.', 'cf.');
	
	 for($countx = 0; $countx <= 7 ; $countx++)
	 	{
	 	$cf_string_neu = str_replace($cf_moeglichkeiten[$countx], '', $cf_string);
		if (($cf_string_neu != $cf_string) and (!$cf))
			{
			$cf = $cf_moeglichekiten_ausgabe[$countx];
			$cf_name = $cf_string_neu;
			}
	 	}
	$cf = trim($cf); 	
	$cf_name = trim($cf_name);  	
	$cf_ausgabe = array($cf, $cf_name);	
	#var_dump($cf_ausgabe);
	return $cf_ausgabe;
	}


function hybrid_trennung($spaltprodukt)
	{
	$hybrid_arr = '';
	$hybrid_arr = str_replace (' x ', ' x ', $hybrid_arr);
	$hybrid_arr = str_replace (' × ', ' x ', $hybrid_arr);
	$hybrid_arr = explode(' x ',$spaltprodukt);
	$count_hybrid_arr = count($hybrid_arr);
	$hyb_pruef = trim($hybrid_arr[0]); # damit Hybrid x hybridetpeithet-Namen nicht gesplittet werden
	$hyb_pruef_pos = strpos($hyb_pruef, " ");
	if (($count_hybrid_arr >= 2) and ($hyb_pruef_pos !== false))
		{
		$erst_elter = $hybrid_arr[0];
		$zweit_elter = $hybrid_arr[1];
		#echo "hybrid";
		#var_dump($hybrid_arr);
		}
	else
		{
		$erst_elter = $spaltprodukt;
		$zweit_elter = '';
		#echo "nohyb\n";
		}
	$hybrid_arr_ausgabe = array('erst_elter' => $erst_elter, 'zweit_elter' => $zweit_elter);
	return $hybrid_arr_ausgabe;
	}
	
	
function var_uart_ermitteln ($spaltprodukt,$taxatype = '40', $alt_trenn='') # gehört noch für subvar/forma/subforma erweitert! (ebenso alle untenstehenden funktionen ...)
	{
	$trenn_autor = '';

	if ($taxatype == 40) # uart
		{
		$rep_str_arr_vorher = array(' ssp. ',' ssp ', ' subsp ');
		$rep_str_arr_nachher = array(' subsp. ', ' subsp. ', ' subsp. ');
		$trenn_string = ' subsp.';
		}
	elseif ($taxatype == 50) # var
		{
		$rep_str_arr_vorher = array(' var ', ' VAR ', ' VAR.');
		$rep_str_arr_nachher = array(' var. ',' var. ',' var.');
		$trenn_string = ' var.';
		}
	elseif ($taxatype == 60) # subvar
		{
		$rep_str_arr_vorher = array(' subvar ', ' SUBVAR ', ' SUBVAR.');
		$rep_str_arr_nachher = array(' subvar. ',' subvar. ',' subvar.');
		$trenn_string = ' subvar.';
		}
	elseif ($taxatype == 70) # froma ##### Achtung Problematisch bei Autorennamen mit F. !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
		{
		$rep_str_arr_vorher = array(' forma ', ' FORMA ', ' f. ', " f ");
		$rep_str_arr_nachher = array(' forma ',' forma ',' forma ', " forma ");
		$trenn_string = ' forma ';
		}
	elseif ($taxatype == 80) #subforma
		{
		$rep_str_arr_vorher = array(' subforma ', ' SUBFORMA ', ' subf ', ' SUBF.');
		$rep_str_arr_nachher = array(' subf. ',' subf. ',' subf. ',' subf.');
		$trenn_string = ' subf.';
		}
	elseif ($taxatype == 24) #sect
		{
		$rep_str_arr_vorher = array(' sect ', ' Sect ', ' SECT ', ' sect.', ' Sect.', ' SECT.', '§');
		$rep_str_arr_nachher = array(' sect. ',' sect. ',' sect. ',' sect.',' sect.',' sect.',' sect.');
		$trenn_string = ' sect.';
		}
	elseif ($taxatype == 26) #agg
		{
		$rep_str_arr_vorher = array(' agg ', ' Agg ', ' AGG ', ' agg.', ' Agg.', ' AGG.');
		$rep_str_arr_nachher = array(' agg. ',' agg. ',' agg. ',' agg.',' agg.',' agg.');
		$trenn_string = ' agg.';
		}
	else
		{
		$rep_str_arr_vorher = array();
		$rep_str_arr_nachher = array();
		$trenn_string = $alt_trenn;
		}
	
	$rep_str_leerzeichen_arr_vorher = array('     ','    ','   ','  ');
	$rep_str_leerzeichen_arr_nachher = array(' ',' ',' ',' ');
	$spaltprodukt= str_replace ($rep_str_leerzeichen_arr_vorher, $rep_str_leerzeichen_arr_nachher, $spaltprodukt); # leerzeichen minimieren
	$spaltprodukt = str_replace ($rep_str_arr_vorher, $rep_str_arr_nachher, $spaltprodukt); # trennstring eingabefehler ausbessern
	$spaltprodukt= str_replace ($rep_str_leerzeichen_arr_vorher, $rep_str_leerzeichen_arr_nachher, $spaltprodukt); # leerzeichen minimieren

	$rep_str_cf_arr_vorher = array('cf. '.$trenn_string, 'cf.'.$trenn_string,' cf '.$trenn_string);
	$rep_str_cf_arr_nachher = array($trenn_string." cf. ", $trenn_string." cf. ", $trenn_string." cf. ");

	$spaltprodukt= str_replace ($rep_str_cf_arr_vorher, $rep_str_cf_arr_nachher, $spaltprodukt); # cf nach trennstring
	$spaltprodukt= str_replace ($rep_str_leerzeichen_arr_vorher, $rep_str_leerzeichen_arr_nachher, $spaltprodukt); # leerzeichen minimieren

	
		
	$trenn_arr = explode($trenn_string,$spaltprodukt,2);
	$spaltprodukt = $trenn_arr[0]; # pflanzenname ohne var
	$count_trenn_arr = count($trenn_arr);
	#echo "__ ".$count_trenn_arr." __";
	if ($count_trenn_arr == 2)
		{
		#echo "XXX";
		$trennung =$trenn_arr[1];
		$cf_trenn_arr = cf($trennung); 		# ---- cf (wenn nach var. steht ...) ermitteln ------------- 
		if (isset($cf_trenn_arr[1]))
			{
			$cf_trenn = $cf_trenn_arr[0];
			$trennung = $cf_trenn_arr[1];
			}
			$trenn_autor_arr = explode( ' ',$trennung,2); 		# ---- autor ermitteln ------------- 
		if (isset($trenn_autor_arr[1]))
			{
			$trennung = $trenn_autor_arr[0];
			$trenn_autor = $trenn_autor_arr[1];
			}
		}
	else
		{
		$trennung = '';
		$trenn_autor = '';
		$cf_trenn = '';
		$taxatype = '';
		$trenn_string = '';
		}
		
		$trennung = trim($trennung);
		$trenn_autor = trim($trenn_autor);
		$cf_trenn = trim($cf_trenn);
		$taxatype = trim($taxatype);
		$trenn_string = trim($trenn_string);
		
		/*	echo "<p><b>".
				$trennung." __ ".
		$trenn_autor." __ ".
		$cf_trenn." __ ".
		$taxatype." __ ".
		$trenn_string."</b></p>"; */

		
		
	$var_art_return_arr = array('restname' => $spaltprodukt,
															'cf_uart' => $cf_trenn,
															'uart' => $trennung,
															'uart_autor' => $trenn_autor,
															'taxatype' => $taxatype,
															'trenn_string'=> $trenn_string);
	return $var_art_return_arr;
	}



function namensaufspaltung_neu($spaltprodukt,$taxatype_eingabe=0) #namensaufspaltung OHNE hybriden!!! + fam/gatt-erkennung anhand erster Buchstabe zweites Wort groß => Autor, wenn nicht, dann Epithet ...
	{
	#echo "<p>0: ".$spaltprodukt."</p>\n";
	$vergleichstring_orig = $spaltprodukt;
	$sect = '';
	$sect_autor = '';
	$cf_sect = '';
	$cf_agg = '';
	$agg = '';
	$agg_autor = '';
	$cf_art = '';
	$art = '';
	$autor = ''; 
	$cf_uart = ''; 
	$uart = ''; 
	$uart_autor = ''; 
	$cf_var = ''; 
	$var = ''; 
	$var_autor = ''; 
	$cf_subvar = ''; 
	$subvar = ''; 
	$subvar_autor = ''; 
	$cf_forma = ''; 
	$forma = ''; 
	$forma_autor = ''; 
	$cf_subforma = ''; 
	$subforma = ''; 
	$subforma_autor = ''; 
	$fam_gatt = '';
	$var_arr = array();
	$cf_var_arr = array();
	$var_autor_arr = array();
	$subvar_arr = array();
	$cf_subvar_arr = array();
	$subvar_autor_arr = array();
	$forma_arr = array();
	$cf_forma_arr = array();
	$forma_autor_arr = array();
	$subforma_arr = array();
	$cf_subforma_arr = array();
	$subforma_autor_arr = array();
	$cf_uart_arr = array();
	$uart_autor_arr = array();
	$uart_arr = array();
	$taxatype = 0;
	$cf_fam_gatt = '';
	$taxatype = 0;
	$ersterbuchstabegross = '';
	
	$spaltprodukt = str_replace(" f.)", " f_.)",$spaltprodukt); # falls autor f., maskieren damit nicht forma ...
	$spaltprodukt = str_replace(" f. )", " f_.)",$spaltprodukt); # falls autor f., maskieren damit nicht forma ...
	$spaltprodukt = str_replace(" f. ex ", " f_. ex ",$spaltprodukt); # falls autor f., maskieren damit nicht forma ...
	
	# noch zu tun:
	# - cf für gattung/fam einbauen bzw. art
	# - familien an ...ceae erkennen => taxatype 0
	# - gattungen entweder daran erkennen, daß kein Autor angegeben, oder 1 Buchstabe vom Autor groß geschieben


	#	$spaltprodukt = "ex aff. Art (Autor alt) Autor neu ssp. cf. Subspecies Autorssp var.ex.aff.Variation(Autorvar)Autorvarneu                                                      #12 Anmerkung";
	
	
# ------------ maskieren, Leerzeichen einfuegen und alle ueberschuessigen Leerzeichen entfernen und aehnliches --------------------------------------
		$spaltprodukt= str_replace ('\x0B', ' ', $spaltprodukt);

		$spaltprodukt= str_replace ('amp;', '', $spaltprodukt); # falls irgendwo z.B. &amp;amp;amp; 
		$spaltprodukt = htmlspecialchars($spaltprodukt, ENT_QUOTES, "UTF-8"); # Daten maskieren 

		$spaltprodukt= str_replace ('[', '', $spaltprodukt); #eventuell zeile deaktivieren?
		$spaltprodukt= str_replace (']', '', $spaltprodukt); #eventuell zeile deaktivieren?
		$spaltprodukt= str_replace ('(', ' (', $spaltprodukt);
		$spaltprodukt= str_replace (')', ') ', $spaltprodukt);
		$spaltprodukt= str_replace ('.', '. ', $spaltprodukt);
		$spaltprodukt= str_replace (',', ', ', $spaltprodukt);
		$spaltprodukt= str_replace ('\n', ' ', $spaltprodukt);
		$spaltprodukt= str_replace ('\r', ' ', $spaltprodukt);
		$spaltprodukt= str_replace ('\t', ' ', $spaltprodukt);
		$spaltprodukt= str_replace ('\v', ' ', $spaltprodukt);
		$spaltprodukt= str_replace ('\f', ' ', $spaltprodukt);
		$spaltprodukt= str_replace ('\x0B', ' ', $spaltprodukt);
		$spaltprodukt= str_replace ('  ', ' ', $spaltprodukt);
		$spaltprodukt= str_replace ('  ', ' ', $spaltprodukt); 
/*
		$spaltprodukt= str_replace (' var ', ' var. ', $spaltprodukt);
		$spaltprodukt= str_replace ('cf. var.', 'var. cf.', $spaltprodukt);
		$spaltprodukt= str_replace ('cf.var.', 'var. cf.', $spaltprodukt);
		$spaltprodukt= str_replace ('cf var.', 'var. cf.', $spaltprodukt);
		$spaltprodukt= str_replace ('cf. var ', 'var. cf.', $spaltprodukt);
		$spaltprodukt= str_replace ('cf var ', 'var. cf.', $spaltprodukt);
		$spaltprodukt= str_replace ('cf.var ', 'var. cf.', $spaltprodukt);

		$spaltprodukt= str_replace ('subsp.', 'ssp.', $spaltprodukt);
		$spaltprodukt= str_replace (' subsp ', ' ssp. ', $spaltprodukt);
		$spaltprodukt= str_replace ('ssp ', 'ssp.', $spaltprodukt);
		$spaltprodukt= str_replace ('cf. ssp.', 'ssp. cf.', $spaltprodukt);
		$spaltprodukt= str_replace ('cf.ssp.', 'ssp. cf.', $spaltprodukt);
		$spaltprodukt= str_replace ('cf ssp.', 'ssp. cf.', $spaltprodukt);
		$spaltprodukt= str_replace ('cf. ssp ', 'ssp. cf.', $spaltprodukt);
		$spaltprodukt= str_replace ('cf ssp ', 'ssp. cf.', $spaltprodukt);
		$spaltprodukt= str_replace ('cf.ssp ', 'ssp. cf.', $spaltprodukt);
		
*/ 
		
	#	$spaltprodukt= bin2hex ($spaltprodukt);	/* wandelt string in hex-zeichen um ... 
	#	$spaltprodukt= hex2bin ($spaltprodukt); /* wandelt hex in string zurück und entfernt dabei auch das unerwünschte zeichen '0b' 
 

		$spaltprodukt= str_replace ('     ', ' ', $spaltprodukt);
		$spaltprodukt= str_replace ('    ', ' ', $spaltprodukt);
		$spaltprodukt= str_replace ('    ', ' ', $spaltprodukt);
		$spaltprodukt= str_replace ('   ', ' ', $spaltprodukt);
		$spaltprodukt= str_replace ('   ', ' ', $spaltprodukt);
		$spaltprodukt= str_replace ('   ', ' ', $spaltprodukt);
		$spaltprodukt= str_replace ('   ', ' ', $spaltprodukt);
		$spaltprodukt= str_replace ('   ', ' ', $spaltprodukt);
		$spaltprodukt= str_replace ('  ', ' ', $spaltprodukt);
		$spaltprodukt= str_replace ('  ', ' ', $spaltprodukt);
		$spaltprodukt= str_replace ('  ', ' ', $spaltprodukt);
		$spaltprodukt= str_replace ('  ', ' ', $spaltprodukt);
		$spaltprodukt= str_replace ('  ', ' ', $spaltprodukt);
		$spaltprodukt= str_replace ('  ', ' ', $spaltprodukt);
		$spaltprodukt= str_replace ('  ', ' ', $spaltprodukt); 
		$spaltprodukt= trim($spaltprodukt);		
		
#			$delete = substr ($spaltprodukt, $spalt_position, 2)		/* liest den zu ersetzenden String aus
#			$produkt_laenge = strlen ($spaltprodukt);					/* Gesamtlaenge des ganzen Strings
#				$spaltprodukt = substr($spaltprodukt,0,$spalt_position)."20".substr($spaltprodukt,,)		
		
	#$buchstabenbereinigt = $spaltprodukt;


		
# subforma ------------------------------------------------------------------------------------------
		
			
	$subforma_arr = var_uart_ermitteln($spaltprodukt,80); # subforma abspalten
	$subforma = $subforma_arr['uart'];
	$cf_subforma = $subforma_arr['cf_uart'];
	$subforma_autor = $subforma_arr['uart_autor'];
	$subforma_trenn = $subforma_arr['trenn_string'];
	if ($subforma != '')
		{
		$taxatype = $subforma_arr['taxatype'];
		}
	$spaltprodukt = $subforma_arr['restname'];
	
# forma ------------------------------------------------------------------------------------------
		
			
	$forma_arr = var_uart_ermitteln($spaltprodukt,70); # forma abspalten; ACHTUNG: Probleme mit "f." bei Autoren!!!!!
	$forma = $forma_arr['uart'];
	$cf_forma = $forma_arr['cf_uart'];
	$forma_autor = $forma_arr['uart_autor'];
	$forma_trenn = $forma_arr['trenn_string'];
	if ($forma != '')
		{
		$taxatype = $forma_arr['taxatype'];
		}
	$spaltprodukt = $forma_arr['restname'];


# subvar ------------------------------------------------------------------------------------------
		
			
	$subvar_arr = var_uart_ermitteln($spaltprodukt,60); # subvar abspalten
	$subvar = $subvar_arr['uart'];
	$cf_subvar = $subvar_arr['cf_uart'];
	$subvar_autor = $subvar_arr['uart_autor'];
	$subvar_trenn = $subvar_arr['trenn_string'];
	if ($subvar != '')
		{
		$taxatype = $subvar_arr['taxatype'];
		}
	$spaltprodukt = $subvar_arr['restname'];
	
# var ------------------------------------------------------------------------------------------
		
			
	$var_arr = var_uart_ermitteln($spaltprodukt,50); # var abspalten
	$var = $var_arr['uart'];
	$cf_var = $var_arr['cf_uart'];
	$var_autor = $var_arr['uart_autor'];
	$var_trenn = $var_arr['trenn_string'];
	if ($var != '')
		{
		$taxatype = $var_arr['taxatype'];
		}
	$spaltprodukt = $var_arr['restname'];



	# uart ------------------------------------------------------------------------------------------ 
		
	$uart_arr = var_uart_ermitteln($spaltprodukt,40); # uart abspalten
	$uart = $uart_arr['uart'];
	$cf_uart = $uart_arr['cf_uart'];
	$uart_autor = $uart_arr['uart_autor'];
	$uart_trenn = $uart_arr['trenn_string'];
	if ($uart != '')
		{
		$taxatype = $uart_arr['taxatype'];
		}
	$spaltprodukt = $uart_arr['restname'];
	
	
	# agg ------------------------------------------------------------------------------------------ 
		# autoren gibt + funktionerien nicht!!!
	$agg_arr = var_uart_ermitteln($spaltprodukt,26); # agg abspalten
	$agg = $agg_arr['uart'];
	$cf_agg = $uart_arr['cf_uart'];
	$agg_hinten = $agg_arr['uart_autor'];
	$agg_trenn = $agg_arr['trenn_string'];
	if ($agg != '')
		{
		$taxatype = $agg_arr['taxatype'];
		}
	$spaltprodukt = $agg_arr['restname'].$agg_hinten;

	# sect ------------------------------------------------------------------------------------------ 
		# autoren funktionieren nicht!!!
	$sect_arr = var_uart_ermitteln($spaltprodukt,24); # sect abspalten
	$sect = $sect_arr['uart'];
	$cf_sect = $uart_arr['cf_uart'];
	$sect_hinten = $sect_arr['uart_autor'];
	$sect_autor = '';
	$sect_trenn = $sect_arr['trenn_string'];
	if ($sect != '')
		{
		$taxatype = $sect_arr['taxatype'];
		}
	$spaltprodukt = $sect_arr['restname'].$sect_hinten;


			
			
	# Art + Gattung ------------------------------------------------------------------------------------------ 
	
	$cf_fam_gatt_arr = cf($spaltprodukt); 		# ---- cf fam/gatt ermitteln ------------- 
		if (isset($cf_fam_gatt_arr[1]) && ("cf. " == substr($spaltprodukt,0,4))) # zweiter teil der if-abfrage überprüft ob cf eh am anfang steht ...
			{
 			#if ("cf. " == substr($spaltprodukt,0,4)) echo "xxxxx";
			#echo "<p>".$spaltprodukt."</p>";
			$cf_fam_gatt = $cf_fam_gatt_arr[0];
			$spaltprodukt = $cf_fam_gatt_arr[1];
			#echo "<p>".$spaltprodukt."</p>";
			}	
	
	#echo "<p>A: ".$spaltprodukt."</p>\n";
	$spaltprodukt = trim($spaltprodukt);
	$fam_gatt_art_arr = explode(' ',$spaltprodukt,2); 		# ---- autor + art + gattung ermitteln -------------
	#var_dump($autor_arr);
	$fam_gatt = $fam_gatt_art_arr[0];
		$count_fam_gatt_art_arr = count($fam_gatt_art_arr);
		
		$fam_check = substr($fam_gatt,-4);	# ------- wenn Familie ....
			if ($fam_check == 'ceae')
				{
				#echo " FAM ";
				$taxatype = 10; # Familie
				}


		if ($count_fam_gatt_art_arr == 1) # wennn kein autor angegeben ist und nur 1 Wort => Fam oder Gattung ohne Autor
			{
			$autor = '';
			$art = '';
			$cf_art = '';
			if ($fam_check != 'ceae') # Gattung ohne Autor
				{
				#echo " GATT ";
				$taxatype = 20; # Gattung
				}
			}
		else # Gattung mit Autor oder Art
			{
			$spaltprodukt = $fam_gatt_art_arr[1];
			$cf_art_arr = cf($spaltprodukt); 		# ---- cf art ermitteln ------------- 
			if (isset($cf_art_arr[1]))
				{
				$cf_art = $cf_art_arr[0];
				$spaltprodukt = $cf_art_arr[1];
				}
				
			trim($spaltprodukt);
			#echo "<p>B: ".$spaltprodukt."</p>\n";

			#echo "\\ <i>".$spaltprodukt."</i> \\";
			$fam_gatt_art_arr = explode(' ',$spaltprodukt,2); 		# ---- autor + art + gattung ermitteln -------------

			$ersterbuchstabegross = ersterbuchstabe_gross($spaltprodukt);
			#echo "ERST: ".$ersterbuchstabegross;
			if ($ersterbuchstabegross == 'yes') # Gattung mit Autor
				{
				#echo " GATT_AUTOR ";
				$taxatype = 20; # Gattung
				$autor = $spaltprodukt;
				}
			else
				{
				#echo "GATT_ART";
				if ($taxatype != 26) # wenn nach agg mit agg. hinten, dann art
					{
					$taxatype = 30; # Art
					}
					
				$art_arr = explode(' ',$spaltprodukt,2); 		# ---- autor + art  ermitteln -------------
				$art = $art_arr[0];
				$count_art_arr = count($art_arr);
				#$autor = $art_arr[1];
				$autor = trim($autor);
				#echo "<p>1: ".$autor."</p>\n";
				if ($count_art_arr == 2) # wennn kein autor angegeben ist und nur 1 Wort => Fam oder Gattung ohne Autor
					{
					$autor = $art_arr[1];
					}
					
				if ($autor == "sect.") # wenn irrtümlich art, aber eigentl.  sect.
					{
					$taxatype = 24; #sect.
					$sect = $art;
					$art = '';
					}
				$vergleichsstring = $fam_gatt." ".$art." agg.";
				if (($autor == "agg.") or ($autor == "agg") or ($taxatype == 26) or ($vergleichsstring == $vergleichstring_orig))
				#if (($autor == "agg.") or ($autor == "agg") or ($taxatype == 26))
					{
					#echo "YYYYYYYYYY".$autor."YYYYYYYY\n";
					$taxatype = 26; #agg. # wenn irrtümlich art, aber eigentlich agg.
					$agg = $art;
					$art = '';
					}
				#echo "<p>2: ".$autor."</p>\n\n";
				}
			}

	
	/*$count_autor_arr = count($autor_arr);
		if ($count_autor_arr >= 2)
			{
			#echo "xxCCCCCCCCCCCCCCxxxxxxxxx";
			$art = $autor_arr[1];
			$fam_gatt = $autor_arr[0];
			if ($count_autor_arr >= 3)
				{
				$autor = $autor_arr[2];
				}
			}
		else {$fam_gatt = $spaltprodukt;}				
															# ----- sp. bei Art vereinheitlichen ---------- 
		if ((!$art) or ($art == 'sp')  or ($art == 'spec.') or ($art == 'SP'))  
			{
			#$art = 'sp.';
			$art = '';
			} */
		/*$art= str_replace ('sp,', 'sp.', $art); 
		$art= str_replace ('sp ', 'sp. ', $art);
		$art= str_replace ('sp.', '', $art); */

	# ------------ Entfernt Leerzeichen am Anfang + Ende der Strings --------------- 
	$cf_art =trim($cf_art); 
	$art =trim($art);
	$fam_gatt =trim($fam_gatt);
	$autor =trim($autor);
	$cf_uart =trim($cf_uart);
	$uart =trim($uart);
	$uart_autor =trim($uart_autor);
	$cf_var =trim($cf_var);
	$var =trim($var);
	$var_autor =trim($var_autor);
	
	

	
	if ($subforma != '')
		{
		$taxatype = 80;	# varietät
		}
	else if ($forma != '')
		{
		$taxatype = 70;	# forma
		}
	else if ($subvar != '')
		{
		$taxatype = 60;	# subvarietät
		}
	else if ($var != '')
		{
		$taxatype = 50;	# varietät
		}
	else
		{
		if ($uart != '')
			{
			$taxatype = 40; # unterart
			}
		else
			{
			$fam_check = substr($fam_gatt,-4);
			#echo "## ".$fam_check." ##";;
			if ($fam_check == 'ceae')
				{
				$taxatype = 10; # Familie
				}
			else
				{
				if ($ersterbuchstabegross == 'yes')
					{
					$taxatype = 20;	# Gattung
					}
				else
					{
					$taxatype = 30; # art
					}
				}
			} 
		}
		
		
	if (($taxatype == 30) and ($agg != "") and ($art == '')) # bugfix für Gattung aggname agg. - Aufsplittung
	  {
	  $taxatype = 26;
	  }

	
	$html = '';	
	$html = "<b><i>".$fam_gatt;
	if ($cf_art != '')
		{
		$html = $html."</i></b> ".$cf_art."<b><i>";
		}
	if ($agg != '')
		{
		$html = $html." ".$agg."</b></i> agg.";
		}
	if ($art != '')
		{
		$html = $html." ".$art;
		}
	$html = $html."</i></b>";
	if ($autor != '')
		{
		$html = $html." ".$autor;
		}
	if ($cf_uart != '')
		{
		$html = $html." ".$cf_uart;
		}
	if ($uart != '')
		{
		$html = $html." subsp. <b><i> ".$uart."</i></b>";
		}
	if ($uart_autor != '')
		{
		$html = $html." ".$uart_autor;
		}
		
	if ($cf_var != '')
		{
		$html = $html." ".$cf_var;
		}
	if ($var != '')
		{
		$html = $html." var. <b><i> ".$var."</i></b>";
		}
	if ($var_autor != '')
		{
		$html = $html." ".$var_autor;
		}
		
	if ($cf_subvar != '')
		{
		$html = $html." ".$cf_subvar;
		}
	if ($subvar != '')
		{
		$html = $html." subvar. <b><i> ".$subvar."</i></b>";
		}
	if ($subvar_autor != '')
		{
		$html = $html." ".$subvar_autor;
		}
			
	if ($cf_forma != '')
		{
		$html = $html." ".$cf_forma;
		}
	if ($forma != '')
		{
		$html = $html." f. <b><i> ".$forma."</i></b>";
		}
	if ($forma_autor != '')
		{
		$html = $html." ".$forma_autor;
		}
		
	if ($cf_subforma != '')
		{
		$html = $html." ".$cf_subforma;
		}
	if ($subforma != '')
		{
		$html = $html." subf. <b><i> ".$subforma."</i></b>";
		}
	if ($subforma_autor != '')
		{
		$html = $html." ".$subforma_autor;
		}

	if ($cf_fam_gatt != '')	$html = $cf_fam_gatt." ".$html;
		
	# maskierter f.-Autorenname wird wieder demaskiert
	$html = str_replace("_","",$html);
	$autor = str_replace("_","",$autor);
	$uart_autor = str_replace("_","",$uart_autor);
	$var_autor = str_replace("_","",$var_autor);
	$subvar_autor = str_replace("_","",$subvar_autor);
	$forma_autor = str_replace("_","",$forma_autor);
	$subforma = str_replace("_","",$subforma);
			
	$spaltendprodukt = array("cf_fam_gatt" => $cf_fam_gatt,
							"fam_gatt" => $fam_gatt, 
							"cf_sect" => $cf_sect, 
							"sect" => $sect, 
							"sect_autor" => $sect_autor, 
							"cf_agg" => $cf_agg, 
							"agg" => $agg, 
							"cf_art" => $cf_art, 
							"art" => $art, 
							"autor" => $autor, 
							"cf_uart" => $cf_uart, 
							"uart" => $uart, 
							"uart_autor" => $uart_autor, 
							"cf_var" => $cf_var, 
							"var" => $var, 
							"var_autor" => $var_autor, 
							"cf_subvar" => $cf_subvar, 
							"subvar" => $subvar, 
							"subvar_autor" => $subvar_autor, 
							"cf_forma" => $cf_forma, 
							"forma" => $forma, 
							"forma_autor" => $forma_autor, 
							"cf_subforma" => $cf_subforma, 
							"subforma" => $subforma, 
							"subforma_autor" => $subforma_autor, 
							"taxatype" => $taxatype,
							"html" => $html);
	#var_dump($spaltendprodukt);
	return ($spaltendprodukt);
}





function namensaufspaltung_hybrid($spaltprodukt) #namensaufspaltung INCL hybriden!!!
	{
	$html = '';
	$zweit_ausgabe_arr = '';
	$zweit_ausgabe_arr = array();
	$zweit_ausgabe_arr['fam_gatt'] = '';
	$zweit_ausgabe_arr['cf_art'] = '';
	$zweit_ausgabe_arr['art'] = '';
	$zweit_ausgabe_arr['autor'] = '';
	$zweit_ausgabe_arr['cf_uart'] = '';
	$zweit_ausgabe_arr['uart'] = '';
	$zweit_ausgabe_arr['uart_autor'] = '';
	$zweit_ausgabe_arr['cf_var'] = '';
	$zweit_ausgabe_arr['var'] = '';
	$zweit_ausgabe_arr['var_autor'] = ''; 
	$zweit_ausgabe_arr['cf_subvar'] = '';
	$zweit_ausgabe_arr['subvar'] = '';
	$zweit_ausgabe_arr['subvar_autor'] = ''; 
	$zweit_ausgabe_arr['cf_forma'] = '';
	$zweit_ausgabe_arr['forma'] = '';
	$zweit_ausgabe_arr['forma_autor'] = ''; 
	$zweit_ausgabe_arr['cf_subforma'] = '';
	$zweit_ausgabe_arr['subforma'] = '';
	$zweit_ausgabe_arr['subforma_autor'] = ''; 

	$hybrid_arr = hybrid_trennung($spaltprodukt);
	$erst_ausgabe_arr = namensaufspaltung_neu($hybrid_arr['erst_elter']);
	$taxatype_hyb = $erst_ausgabe_arr['taxatype'];

	if ($hybrid_arr['zweit_elter'] != '')
		{
		$zweit_ausgabe_arr = namensaufspaltung_neu($hybrid_arr['zweit_elter']);
		$taxatype = 110;
		}
	else
		{
		$taxatype = $erst_ausgabe_arr['taxatype'];
		$html = $erst_ausgabe_arr['html'];
		}
	if ($html == '') # hybrid-html-ausgabe generieren
		{
		$html = $erst_ausgabe_arr['html'];	
		
		if ($erst_ausgabe_arr['fam_gatt'] == $zweit_ausgabe_arr['fam_gatt'])
			{
			$zweit_gattung = '';
			}
		else
			{
			$zweit_gattung = $zweit_ausgabe_arr['fam_gatt'];
			}
		
		if ($erst_ausgabe_arr['cf_art'] == $zweit_ausgabe_arr['cf_art'])
			{
			$zweit_cf_art = '';
			}
		else
			{
			$zweit_cf_art = " ".$zweit_ausgabe_arr['cf_art'];
			}
			
		if ($erst_ausgabe_arr['art'] == $zweit_ausgabe_arr['art'])
			{
			$zweit_art = '';
			}
		else
			{
			$zweit_art = " ".$zweit_ausgabe_arr['art'];
			}
			
		if ($zweit_art != '')
			{
			$zweit_autor = " ".$zweit_ausgabe_arr['autor'];
			$zweit_autor = str_replace(". )",".)",$zweit_autor);
			}
		else
			{
			$zweit_autor = '';
			} 
			
		$html = $html." × <b><i>".$zweit_gattung.$zweit_cf_art.$zweit_art."</i></b>".$zweit_autor;
		
		if ($zweit_ausgabe_arr['cf_uart'] != '')
			{
			$html = $html." ".$zweit_ausgabe_arr['cf_uart'];
			}
		if ($zweit_ausgabe_arr['uart'] != '')
			{
			$html = $html." subsp. <b><i> ".$zweit_ausgabe_arr['uart']."</i></b>";
			}
		if ($zweit_ausgabe_arr['uart_autor'] != '')
			{
			$html = $html." ".$zweit_ausgabe_arr['uart_autor'];
			} 
			
		if ($zweit_ausgabe_arr['cf_var'] != '')
				{
			$html = $html." ".$zweit_ausgabe_arr['cf_var'];
			}
		if ($zweit_ausgabe_arr['var'] != '')
			{
			$html = $html." var. <b><i> ".$zweit_ausgabe_arr['var']."</i></b>";
			}
		if ($zweit_ausgabe_arr['var_autor'] != '')
			{
			$html = $html." ".$zweit_ausgabe_arr['var_autor'];
			} 

		if ($zweit_ausgabe_arr['cf_subvar'] != '')
				{
			$html = $html." ".$zweit_ausgabe_arr['cf_subvar'];
			}
		if ($zweit_ausgabe_arr['subvar'] != '')
			{
			$html = $html." subvar. <b><i> ".$zweit_ausgabe_arr['subvar']."</i></b>";
			}
		if ($zweit_ausgabe_arr['subvar_autor'] != '')
			{
			$html = $html." ".$zweit_ausgabe_arr['subvar_autor'];
			} 

		if ($zweit_ausgabe_arr['cf_forma'] != '')
				{
			$html = $html." ".$zweit_ausgabe_arr['cf_forma'];
			}
		if ($zweit_ausgabe_arr['forma'] != '')
			{
			$html = $html." f. <b><i> ".$zweit_ausgabe_arr['forma']."</i></b>";
			}
		if ($zweit_ausgabe_arr['forma_autor'] != '')
			{
			$html = $html." ".$zweit_ausgabe_arr['forma_autor'];
			} 

		if ($zweit_ausgabe_arr['cf_subforma'] != '')
				{
			$html = $html." ".$zweit_ausgabe_arr['cf_subforma'];
			}
		if ($zweit_ausgabe_arr['subforma'] != '')
			{
			$html = $html." subf. <b><i> ".$zweit_ausgabe_arr['subforma']."</i></b>";
			}
		if ($zweit_ausgabe_arr['subforma_autor'] != '')
			{
			$html = $html." ".$zweit_ausgabe_arr['subforma_autor'];
			} 


			$erst_ausgabe_arr['autor'] = str_replace(". )",".)",$erst_ausgabe_arr['autor']);
			$erst_ausgabe_arr['uart_autor'] = str_replace(". )",".)",$erst_ausgabe_arr['uart_autor']);
			$erst_ausgabe_arr['var_autor'] = str_replace(". )",".)",$erst_ausgabe_arr['var_autor']);
			$zweit_ausgabe_arr['autor'] = str_replace(". )",".)",$zweit_ausgabe_arr['autor']);
			$zweit_ausgabe_arr['uart_autor'] = str_replace(". )",".)",$zweit_ausgabe_arr['uart_autor']);
			$zweit_ausgabe_arr['var_autor'] = str_replace(". )",".)",$zweit_ausgabe_arr['var_autor']);
			$zweit_ausgabe_arr['subvar_autor'] = str_replace(". )",".)",$zweit_ausgabe_arr['subvar_autor']);
			$zweit_ausgabe_arr['forma_autor'] = str_replace(". )",".)",$zweit_ausgabe_arr['forma_autor']);
			$zweit_ausgabe_arr['subforma_autor'] = str_replace(". )",".)",$zweit_ausgabe_arr['subforma_autor']);
			$html = str_replace(". )",".)",$html);



		#$html = $erst_ausgabe_arr['html']." x ".$zweit_ausgabe_arr['html'];
		} 
		$spaltendprodukt = array("cf_fam_gatt" => $erst_ausgabe_arr['cf_fam_gatt'],
							"fam_gatt" => $erst_ausgabe_arr['fam_gatt'], 
							"sect" => $erst_ausgabe_arr['sect'], 
							"sect_autor" => $erst_ausgabe_arr['sect_autor'], 
							"agg" => $erst_ausgabe_arr['agg'], 
							"cf_art" => $erst_ausgabe_arr['cf_art'], 
							"art" => $erst_ausgabe_arr['art'], 
							"autor" => $erst_ausgabe_arr['autor'], 
							"cf_uart" => $erst_ausgabe_arr['cf_uart'], 
							"uart" => $erst_ausgabe_arr['uart'], 
							"uart_autor" => $erst_ausgabe_arr['uart_autor'], 
							"cf_var" => $erst_ausgabe_arr['cf_var'], 
							"var" => $erst_ausgabe_arr['var'], 
							"var_autor" => $erst_ausgabe_arr['var_autor'], 
							"cf_subvar" => $erst_ausgabe_arr['cf_subvar'], 
							"subvar" => $erst_ausgabe_arr['subvar'], 
							"subvar_autor" => $erst_ausgabe_arr['subvar_autor'], 
							"cf_forma" => $erst_ausgabe_arr['cf_forma'], 
							"forma" => $erst_ausgabe_arr['forma'], 
							"forma_autor" => $erst_ausgabe_arr['forma_autor'], 
							"cf_subforma" => $erst_ausgabe_arr['cf_subforma'], 
							"subforma" => $erst_ausgabe_arr['subforma'], 
							"subforma_autor" => $erst_ausgabe_arr['subforma_autor'], 
							"gattung_hyb" => $zweit_ausgabe_arr['fam_gatt'], 
							"cf_art_hyb" => $zweit_ausgabe_arr['cf_art'], 
							"art_hyb" => $zweit_ausgabe_arr['art'], 
							"autor_hyb" => $zweit_ausgabe_arr['autor'], 
							"cf_uart_hyb" => $zweit_ausgabe_arr['cf_uart'], 
							"uart_hyb" => $zweit_ausgabe_arr['uart'], 
							"uart_autor_hyb" => $zweit_ausgabe_arr['uart_autor'], 
							"cf_var_hyb" => $zweit_ausgabe_arr['cf_var'], 
							"var_hyb" => $zweit_ausgabe_arr['var'], 
							"var_autor_hyb" => $zweit_ausgabe_arr['subvar_autor'], 
							"cf_subvar_hyb" => $zweit_ausgabe_arr['cf_subvar'], 
							"subvar_hyb" => $zweit_ausgabe_arr['subvar'], 
							"subvar_autor_hyb" => $zweit_ausgabe_arr['subvar_autor'], 
							"cf_forma_hyb" => $zweit_ausgabe_arr['cf_forma'], 
							"forma_hyb" => $zweit_ausgabe_arr['forma'], 
							"forma_autor_hyb" => $zweit_ausgabe_arr['subforma_autor'], 
							"cf_subforma_hyb" => $zweit_ausgabe_arr['cf_subforma'], 
							"subforma_hyb" => $zweit_ausgabe_arr['subforma'], 
							"subforma_autor_hyb" => $zweit_ausgabe_arr['subforma_autor'], 
							"taxatype_hyb" => $taxatype_hyb,
							"taxatype" => $taxatype,
							"html" => $html); 
	#var_dump($spaltendprodukt);

	return $spaltendprodukt;
	}



?>
