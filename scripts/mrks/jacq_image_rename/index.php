<?php
header("Content-type: text/html; charset=UTF-8");
require ('./config/config.php');

$insert = '';
$update = '';
$write = '';
$delete = '';
$html_formular = '';
$log_query = '';
$insert_query = '';
$update_query = '';
$eintraggesperrt = 1;

$ueberschrift = "JACQ Image Rename Tool"; 



function tabsort($spalte,$sortlink,$htmlid="",$sortparam='')
  {
  $sort = " <a href=\"".$sortlink."&amp;sort1=".$spalte."&amp;sort2=ASC".$sortparam.$htmlid."\">⬇</<a>&nbsp;<a href=\"".$sortlink."&amp;sort1=".$spalte."&amp;sort2=DESC".$sortparam.$htmlid."\">⬆</<a>";
  return $sort;
  }

function if_null_insertaufbereitung($post_content)
	{
	$post_content_return = '';
	$post_content = str_replace("'","\'",$post_content);
	$post_content = str_replace('"','&quot;',$post_content);

		if ($post_content == '')
					{
					$post_content_return = 'NULL';
					}
				else
					{
					$post_content_return = "'".$post_content."'";
					}
		return ($post_content_return);
	}



#echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \n";
#echo "\t  \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\"> \n\n";
echo "<!DOCTYPE html>\n\n";

#echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\n\n";

echo "<head>\n\n";

echo "\t<title id = \"title_id\">$ueberschrift</title>\n\n";

echo "\t<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";


echo "\t<link rel=\"stylesheet\" href=\"./css/imagerename.css\" type=\"text/css\" />\n";
echo "\t<link rel=\"stylesheet\" href=\"./css/neue_eingabemasken.css\" type=\"text/css\" />\n";

;

echo "</head>\n\n";




echo "<body id = \"body_id\">\n";



#echo "<p class=\"highlight\">ACHTUNG Testumgebung! Alle Änderungen werden NICHT in die eigentliche Datenbank geschrieben ...</p>"; 


$dblink = new mysqli($dbhost, $dbuser, $dbpwd, $dbname, 3306);
if ($dblink->connect_errno) {
    echo "Failed to connect to MySQL: (" . $dblink->connect_errno . ") " . $dblink->connect_error;
}
#mysqli_set_charset("UTF8", $dblink);  
if (!$dblink->set_charset("utf8")) {
    printf("Error loading character set utf8: %s\n", $dblink->error);
} else {
    #printf("Current character set: %s\n", $dblink->character_set_name());
}

$GLOBALS['dblink']=$dblink;

############################################################################
#holt post-daten usw...

if (isset($_GET["close"]))
	{
	$close = $_GET["close"];
	$closeparam = "&amp;close=".$close;
	if ($close == 3) $closeparam3 = $closeparam;
	else $closeparam3 = '';
	}
else
	{
	$close = '';
	$closeparam = '';
	$closeparam3 = '';
	}

	
if (isset($_GET["site"]))
	{
	$site = $_GET["site"];
	}
else
	{
	$site = '';
	}
	
if (isset($_REQUEST["weiterbearb"]))
	{
	$weiterbearb = $_REQUEST["weiterbearb"];
	}
else
	{
	$weiterbearb = '';
	}
	
	
if (isset($_REQUEST["write"]))
	{
	$write = $_REQUEST["write"];
	}
else
	{
	$write = '';
	}
	
if (isset($_REQUEST["newcopy"]))
	{
	$newcopy= $_REQUEST["newcopy"];
	}
else
	{
	$newcopy= '';
	}

#echo "WRITE: ".$write;
	
if (isset($_REQUEST["id"]))
	{
	$id = $_REQUEST["id"];
	}
else
	{
	$id = 0;
	}

if (isset($_SERVER["REMOTE_USER"]))
	{
	$user = $_SERVER["REMOTE_USER"]; #apache-benuztername auslesen
	}
else
	{
	$user = '';
	}

if (isset($_GET["sort1"]))
	{
	$sort1 = $_GET["sort1"];
	}
else
	{
	$sort1 = '';
	}
if (isset($_GET["sort2"]))
	{
	$sort2 = $_GET["sort2"];
	}
else
	{
	$sort2 = '';
	}
####################################################################################

#### ermittelt user-berechtigungen ####

$usergroup = 'admin';
$user_kurz = 'user';
$user_lang = 'user';
$user = 'user';
/*if ($user != '')
	{
	$prot_query = "SELECT * FROM users WHERE user = \"".$user."\" AND hidden IS NULL" ;
	#$prot_query = "SELECT ".$tablename.".* FROM ".$tablename." WHERE `mgrs_km_feld` IS NOT NULL AND `mgrs_km_id` IS NULL  ".$sort_query." LIMIT 20";
	$prot_result = mysqli_query($GLOBALS['dblink'], $prot_query) or die("Anfrage fehlgeschlagen: " . mysqli_error($GLOBALS['dblink']));
	while ($prot_line = mysqli_fetch_array($prot_result, MYSQLI_ASSOC)) 
		{
		if ($prot_line['superadmin'] == 'yes' && $prot_line['anmerkungen'] == 'admin') $usergroup = 'superadmin';
		else if ($prot_line['anmerkungen'] == 'admin') $usergroup = 'admin';
		else if ($prot_line['anmerkungen'] == 'user') $usergroup = 'user';
		$user_kurz = $prot_line['kurz'];
		$user_lang = $user;
		}
	mysqli_free_result($prot_result);
	}
else
	{
	echo "Keine Berechtigung ...";
	exit;
	}

if ($usergroup == '')
	{
	echo "Keine Berechtigung ...";
	exit;
	}
    */
#echo $usergroup;

###################################################################################

if ($sort1 != '' && ($sort2 == 'ASC' || $sort2 == 'DESC')) $sort_query = " ORDER BY `".$sort1."` ".$sort2;
else  $sort_query = '';

$sortlink = "./index.php?site=".$site;	
$sort['id'] = "<a href=\"".$sortlink."&amp;sort1=id&amp;sort2=ASC\">⬇</<a>
	       <a href=\"".$sortlink."&amp;sort1=id&amp;sort2=DESC\">⬆</<a>";
	       
$sort['user'] = "<a href=\"".$sortlink."&amp;sort1=user&amp;sort2=ASC\">⬇</<a>
		 <a href=\"".$sortlink."&amp;sort1=user&amp;sort2=DESC\">⬆</<a>";



	       
############################################################################	       
	       
if ($site == "img_status") require('./inc/jacq_image_status.php');	
else if ($site == "img_statistics") require('./inc/jacq_image_statistics.php');
else 
  {
  $html_formular .= "<div class =\"hauptspalten\">\n";
	  $html_formular .= "<h2>JACQ Image rename</h2>\n";
		$html_formular .= "<div class=\"subspalten\"\n>";
			$html_formular .= "<a href=\"./index.php?site=img_statistics\" target=\"345sdfg56h\">Statistics</a>\n"; 
		$html_formular .= "</div>\n";
		$html_formular .= "<div class=\"subspalten\"\n>";
			$html_formular .= "<a href=\"./index.php?site=img_status&amp;sort1=timestamp&amp;sort2=DESC\" target=\"3452345234df345463\">Status</a>\n";
		$html_formular .= "</div>\n";
	 /* $html_formular .= "<h2>Fundpunkte</h2>\n";
		$html_formular .= "<div class=\"subspalten\"\n>";
			$html_formular .= "<a href=\"./index.php?site=protfp2\" target=\"neuefupu43543\">Neu</a>\n";
		$html_formular .= "</div>\n";
		$html_formular .= "<div class=\"subspalten\"\n>";
			$html_formular .= "<a href=\"./index.php?site=protfp1&amp;sort1=aenderungsdatum&amp;sort2=DESC\" target=\"fupubearb93251\">auflisten/editieren</a>\n";
		$html_formular .= "</div>\n";
	  $html_formular .= "<h2>Exkursionen</h2>\n";
		$html_formular .= "<div class=\"subspalten\"\n>";
			$html_formular .= "<a href=\"./index.php?site=protex2\" target=\"neueexk34372\">Neu</a>\n";
		$html_formular .= "</div>\n";
		$html_formular .= "<div class=\"subspalten\"\n>";
			$html_formular .= "<a href=\"./index.php?site=protex1\" target=\"exkbearb76352\">auflisten/editieren</a>\n";
		$html_formular .= "</div>\n";
	$html_formular .= "<hr>\n";
	$html_formular .= "<h2>GPS-Daten</h2>\n";
		$html_formular .= "<div class=\"subspalten\"\n>";
			$html_formular .= "<a href=\"./gpx_import.php\" target=\"gpsimp63090\">importieren</a>\n";
		$html_formular .= "</div>\n\n";
 		$html_formular .= "<div class=\"subspalten\"\n>";
			$html_formular .= "<a href=\"./index.php?site=gps1&limit=250&user=".$user."\" target=\"gps54we345\">auflisten</a>\n";
		$html_formular .= "</div>\n\n";*/
  $html_formular .= "</div>\n\n";
  
   
  }
	
	
	
###### eintrag in db schreiben ###############################
if ($insert == 1 && $insert_query!= '' && $log_query != '')
  {
  # neuer db-Eintrag
  $insertgetdata = mysqli_query($GLOBALS['dblink'], $insert_query);
  if (!$insertgetdata) {echo 'Abfrage konnte nicht ausgefuehrt werden: ' . mysqli_error($GLOBALS['dblink']); exit; }
  $new_id=mysqli_insert_id($GLOBALS['dblink']);
  echo $new_id." -- Eintrag gespeichert ---";

  # log-Eintrag
  $log_query = str_replace("__|ID|__", $new_id,$log_query);
  $insertgetdata = mysqli_query($GLOBALS['dblink'], $log_query);
  if (!$insertgetdata) {echo 'Abfrage konnte nicht ausgefuehrt werden: ' . mysqli_error($GLOBALS['dblink']); exit; }
  $new_id=mysqli_insert_id($GLOBALS['dblink']);
  echo $new_id." -- Log gespeichert ---";

  }
else if ($update == 1 && $update_query!= '' && $log_query != '')
  {
  # neuer db-Eintrag
  #echo $update_query;
  $insertgetdata = mysqli_query($GLOBALS['dblink'], $update_query);
  if (!$insertgetdata) {echo 'Abfrage konnte nicht ausgefuehrt werden: ' . mysqli_error($GLOBALS['dblink']); exit; }
  $new_id=mysqli_insert_id($GLOBALS['dblink']);
  echo $id." -- Eintrag aktualisert ---";

  # log-Eintrag
  $log_query = str_replace("__|ID|__", $id,$log_query);
  $insertgetdata = mysqli_query($GLOBALS['dblink'], $log_query);
  if (!$insertgetdata) {echo 'Abfrage konnte nicht ausgefuehrt werden: ' . mysqli_error($GLOBALS['dblink']); exit; }
  $new_id=mysqli_insert_id($GLOBALS['dblink']);
  echo $new_id." -- Log gespeichert ---";
  exit;
  }

###############################################################


echo $html_formular;

mysqli_close($dblink);


# ----------------------------------------------------------------------------------

#echo "<p>W: ".$write." -- C: ".$close."</p>";
if ($write >= 1 && $close == 1 && $weiterbearb != 1 && $newcopy != 1) # schließt nach Speichern das aktuelle Fenster
  {
  echo "<script>
      window.close( );
      </script>";
  }
  
if ($write >= 1 && $close == 2 && $weiterbearb != 1 && $newcopy != 1) # schließt nach Speichern das aktuelle Fenster und lädt das ursprungsfenster neu
  {
  echo "<script>
      opener.location.reload()
      window.close( );
      </script>";
  }
if (($write >= 1 || $delete==1) && $close == 3 && $weiterbearb != 1 && $newcopy != 1) #  lädt nur das ursprungsfenster neu
  {
  echo "<script>
      opener.location.reload()
      </script>";
  }

echo "</body>\n";

echo "</html>\n";

?>
