<?php
#include('./herbar_revisions_etiketten.php');

echo "<!DOCTYPE HTML>";
echo "<html>";
echo "<head>";
echo "<meta http-equiv=\"Content-Type\" content=\"text/html;charset=UTF-8\">";
echo "<title>WU-Revisionsetiketten</title>";
echo "</head>";
echo "<body>\n";
echo "<h1>Revisionsetiketten WU:</h1>";

##################################################################################################
echo "<form action=\"./herbar_revisions_etiketten_neu_mpdf.php\" method=\"post\" enctype=\"multipart/form-data\">
<label for=\"file\">Filename:</label>
<input type=\"file\" name=\"file\" id=\"file\"><br>
<input type=\"checkbox\" name=\"akt_datum\" value=\"yes\" checked=\"checked\"> aktuelles Datum verwenden, bzw. hier angeben (nur relvant, falls sonst keine Datumsangabe vorhanden): 
<input name=\"manuelles_datum\" type=\"text\" size=\"10\" maxlength=\"10\"><br>
det./rev./conf.-Feld: <select name=\"rev_ett_type\">
      <option value=\"\">[leer oder rev_ett_art-Feld]</option>
      <option value=\"det.\">det.</option>
      <option value=\"rev.\">rev.</option>
      <option value=\"conf.\">conf.</option>
      <option value=\"annot.\">annot.</option>
      <option value=\"det./rev./conf.\">det./rev./conf.</option>
      <option value=\"det./rev./conf./annot.\">det./rev./conf./annot.</option>
    </select>
<p>Die untenstehenden Felder sind nur relevant, wenn leere Revisionsetiketten oder größere Mengen gleicher Etiketten (ohne Datei-Upload) erstellt werden sollen:<br>
Person: <input name=\"person\" type=\"text\" size=\"60\" maxlength=\"250\"><br>
Pflanze (incl. Autor): <input name=\"pflanze\" type=\"text\" size=\"60\" maxlength=\"250\"><br>
Anzahl (optional; 18/Seite): <input name=\"anzahl\" type=\"text\" size=\"3\" maxlength=\"3\"><br>
</p>

<input type=\"submit\" name=\"submit\" value=\"Download csv\">
</form>";
		
echo "</body>\n";
echo "</html>\n";

?>
