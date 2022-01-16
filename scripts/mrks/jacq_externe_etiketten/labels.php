<?php

# get parameter if a specific label type should be preselected
if (isset($_REQUEST['label_type']) && preg_match("/^[0-9a-zA-Z_]+$/",$_REQUEST['label_type'])) $label_type = $_REQUEST['label_type'];
else $label_type = '';

#echo "<p>".$label_type."</p>";

if ($label_type == "myk_a") include('labels_myk_a.php');
elseif ($label_type == "myk_b") include('labels_myk_b.php');
elseif ($label_type == "myk_c") include('labels_myk_c.php');
elseif ($label_type == "speta") include('labels_speta.php');
elseif ($label_type == "wu_rev") include('labels_wu_rev.php');
elseif ($label_type == "jacq_import_csv") include('export_to_jacq_import_csv.php');
else echo "Label error – Please check input file and label type. <a href = \"./index.php\">[Back to input form]</a>.";


#include("./speta_etiketten.php");

?>
