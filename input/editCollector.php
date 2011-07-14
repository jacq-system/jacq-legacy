<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");
no_magic();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - edit Collector</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <script type="text/javascript" language="JavaScript">
    function showHUH(sel) {
      target = "selectCollector.php?id=" + encodeURIComponent(sel.value);
      MeinFenster = window.open(target,"showHUH");
      MeinFenster.focus();
    }
  </script>
</head>

<body>

<?php
if ($_POST['submitUpdate'] && (($_SESSION['editControl'] & 0x1800)!=0)) {
  $sw = true;
  $sql = "SELECT SammlerID, Sammler ".
         "FROM tbl_collector ".
         "WHERE Sammler=".quoteString($_POST['Sammler']).
          "AND SammlerID!='".intval($_POST['ID'])."'";
  $result = db_query($sql);
  while (($row=mysql_fetch_array($result)) && $sw) {
    if ($row['Sammler']==$_POST['Sammler']) {
      echo "<script language=\"JavaScript\">\n";
      echo "alert('Collector \"".$row['Sammler']."\" already present with ID ".$row['SammlerID']."');\n";
      echo "</script>\n";
      $id = $_POST['ID'];
      $sw = false;
    }
  }
  if ($sw) {
    if (intval($_POST['ID'])) {
      if (($_SESSION['editControl'] & 0x1000)!=0) {
        $sql = "UPDATE tbl_collector SET ".
                "Sammler='".mysql_escape_string($_POST['Sammler'])."', ".
                "HUH_ID=".quoteString($_POST['HUH_ID'])." ".
               "WHERE SammlerID='".intval($_POST['ID'])."'";
      } else
        $sql = "";
    } else {
      $sql = "INSERT INTO tbl_collector (Sammler, HUH_ID) ".
             "VALUES ('".mysql_escape_string($_POST['Sammler'])."', ".
              quoteString($_POST['HUH_ID']).")";
    }
    $result = db_query($sql);
    $id = ($_POST['ID']) ? intval($_POST['ID']) : mysql_insert_id();

    echo "<script language=\"JavaScript\">\n";
    echo "  window.opener.document.f.sammler.value = \"".addslashes($_POST['Sammler'])." <$id>\";\n";
    echo "  window.opener.document.f.reload.click()\n";
    echo "  self.close()\n";
    echo "</script>\n";
    echo "</body>\n</html>\n";
    die();
  }
}
else {
  $pieces = explode("<",$_GET['sel']);
  $pieces = explode(">",$pieces[1]);
  $id = $pieces[0];
}

echo "<form name=\"f\" Action=\"".$_SERVER['PHP_SELF']."\" Method=\"POST\">\n";

$sql = "SELECT Sammler, SammlerID, HUH_ID ".
       "FROM tbl_collector WHERE SammlerID='".mysql_escape_string($id)."'";
$result = db_query($sql);
$row = mysql_fetch_array($result);

$cf = new CSSF();

echo "<input type=\"hidden\" name=\"ID\" value=\"".$row['SammlerID']."\">\n";
$cf->label(6,0.5,"ID");
$cf->text(6,0.5,"&nbsp;".(($row['SammlerID'])?$row['SammlerID']:"new"));
$cf->label(6,2,"Collector");
$cf->inputText(6,2,15,"Sammler",$row['Sammler'],50);
$cf->label(6,4,"HUH","javascript:showHUH(document.f.HUH_ID)");
$cf->inputText(6,4,5,"HUH_ID",$row['HUH_ID'],15);

if (($_SESSION['editControl'] & 0x1800)!=0) {
  $text = ($row['SammlerID']) ? " Update " : " Insert ";
  $cf->buttonSubmit(2,7,"submitUpdate",$text);
  $cf->buttonJavaScript(12,7," New ","self.location.href='editCollector.php?sel=<0>'");
}

echo "</form>\n";
?>

</body>
</html>
