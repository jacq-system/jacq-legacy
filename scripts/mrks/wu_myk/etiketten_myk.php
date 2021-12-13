<?php

if (isset($_POST['label_type']))
  {
  if ($_POST['label_type'] == "A") include('etiketten_myk_a.php');
  elseif ($_POST['label_type'] == "B") include('etiketten_myk_b.php');
  elseif ($_POST['label_type'] == "C") include('etiketten_myk_c.php');
  else echo "Label error 2. Please click <a href = \"./index.php\">here</a>.";
  }
else echo "Label error 1. Please click <a href = \"./index.php\">here</a>.";

?>
