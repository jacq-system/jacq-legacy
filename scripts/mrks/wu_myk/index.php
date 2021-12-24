<?php

echo "<!DOCTYPE HTML>";
echo "<html>";
echo "<head>";
echo "<meta http-equiv=\"Content-Type\" content=\"text/html;charset=UTF-8\">";
echo "<title>WU-Etiketten</title>";
echo "</head>";
echo "<body style = \"background: #AABBff; font-family: sans;\">\n";
echo "<h2>Alternative Etiketten WU Mycologicum:</h2>";



##################################################################################################
echo "<form action=\"./etiketten_myk.php\" method=\"post\" enctype=\"multipart/form-data\">
<label for=\"file\">Filename:</label>
<input type=\"file\" name=\"file\" id=\"file\">
<input type=\"submit\" name=\"submit\" value=\"Download PDF\"><br>
Etiketten für: <select name=\"label_type\">
      <option value=\"A\">Schachteln</option>
      <option value=\"B\">Belege</option>
      <option value=\"C\">Bögen</option>
     </select>


</form>";

echo "<h3>Kurzanleitung:</h3><p>1. - In der <b><a href=\"https://input.jacq.org/herbarium-wu/login.php\" target=\"_blank\">JACQ-Datenbank</a></b> bei <b>\"Specimens\"</b> auf Institution klicken => nun sollte dort \"Collection\" stehen.</p>
      <p>2. - Als Collection <b>\"WU-Mykologicum\"</b> auswählen und nach weiteren Kriterien filtern (z.B. Date: 2015); anschließend auf <b>\"Search\"</b> klicken.</p>
      <p>3. - Sollte die Auswahl passen, die Etikettentexte mit <b>\"Download XLSX\"</b> herunterladen.</p>
      <p>4. - Hier auf <b>\"Durchsuchen...\"</b> klicken und das Etikettentext-File (\"specimens_download.xlsx\") auswählen.</p>
      <p>5. - Etiketten-Typ auswählen</p>
      <p>6. - <b>\"Download PDF\"</b> erzeugt die Etiketten als PDF.</p>
      <p><b>Hinweis:</b> Sollen von einem Etikettentext-File gleichzeitig mehrere Arten von Etiketten produziert werden, reicht es aus den nur Punkt 5 und 6 zu wiederholen.</p>
      <p>letzte Änderung: 07.03.2020</p>";



echo "</body>\n";
echo "</html>\n";

?>
