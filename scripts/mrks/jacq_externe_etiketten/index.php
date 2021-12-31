<?php

echo "<!DOCTYPE HTML>";
echo "<html>";
echo "<head>";
echo "<meta http-equiv=\"Content-Type\" content=\"text/html;charset=UTF-8\">";
echo "<title>Labels</title>";
echo "</head>";
echo "<body style = \"background: #AABBff; font-family: sans;\">\n";
echo "<h2>Labels:</h2>";

# get parameter if a specific label type should be preselected
if (isset($_REQUEST['label_type']) && preg_match("/^[0-9a-zA-Z_]+$/",$_REQUEST['label_type'])) $label_type = $_REQUEST['label_type'];
else $label_type = '';

# array of possible label types
$label_types = array();
$label_types[] = array('value' => 'speta', 'txt' => 'W Herb. Speta');
$label_types[] = array('value' => 'myk_a', 'txt' => 'WU-Mykologicum Schachteln');
$label_types[] = array('value' => 'myk_b', 'txt' => 'WU-Mykologicum Belege');
$label_types[] = array('value' => 'myk_c', 'txt' => 'WU-Mykologicum Bögen');
$label_types[] = array('value' => 'wu_rev', 'txt' => 'WU revision labels');
$label_types[] = array('value' => 'jacq_import_csv', 'txt' => 'CSV für JACQ-Import');



echo "<form action=\"./labels.php\" method=\"post\" enctype=\"multipart/form-data\">
<label for=\"file\">File:</label>
<input type=\"file\" name=\"file\" id=\"file\">
<input type=\"submit\" name=\"submit\" value=\"Download\"><br>
Etiketten für: <select name=\"label_type\">";
foreach ($label_types AS $label_opt)
    {
    if ($label_opt['value'] == $label_type) $select_txt = ' selected'; #if a specific label type shoud be preselected
    else $select_txt = '';
    echo "<option value=\"".$label_opt['value']."\"".$select_txt.">".$label_opt['txt']."</option>";
    }
echo "</select>


</form>";

/* echo "<h3>Kurzanleitung:</h3><p>1. - In der <b><a href=\"https://input.jacq.org/herbarium-wu/login.php\" target=\"_blank\">JACQ-Datenbank</a></b> bei <b>\"Specimens\"</b> auf Institution klicken => nun sollte dort \"Collection\" stehen.</p>
      <p>2. - Als Collection <b>\"WU-Mykologicum\"</b> auswählen und nach weiteren Kriterien filtern (z.B. Date: 2015); anschließend auf <b>\"Search\"</b> klicken.</p>
      <p>3. - Sollte die Auswahl passen, die Etikettentexte mit <b>\"Download XLSX\"</b> herunterladen.</p>
      <p>4. - Hier auf <b>\"Durchsuchen...\"</b> klicken und das Etikettentext-File (\"specimens_download.xlsx\") auswählen.</p>
      <p>5. - Etiketten-Typ auswählen</p>
      <p>6. - <b>\"Download PDF\"</b> erzeugt die Etiketten als PDF.</p>
      <p><b>Hinweis:</b> Sollen von einem Etikettentext-File gleichzeitig mehrere Arten von Etiketten produziert werden, reicht es aus den nur Punkt 5 und 6 zu wiederholen.</p>
      <p>letzte Änderung: 07.03.2020</p>";

*/

echo "</body>\n";
echo "</html>\n";

?>
