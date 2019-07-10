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
  <title>herbardb - edit Collector 2</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
</head>

<body>

<?php
if ($_POST['submitUpdate'] && (($_SESSION['editControl'] & 0x1800)!=0)) {
  $sw = true;
  $sql = "SELECT Sammler_2ID, Sammler_2 ".
         "FROM tbl_collector_2 ".
         "WHERE Sammler_2=".quoteString($_POST['Sammler_2']).
          "AND Sammler_2ID!='".intval($_POST['ID'])."'";
  $result = db_query($sql);
  while (($row=mysql_fetch_array($result)) && $sw) {
    if ($row['Sammler_2']==$_POST['Sammler_2']) {
      echo "<script language=\"JavaScript\">\n";
      echo "alert('Collector \"".$row['Sammler_2']."\" already present with ID ".$row['Sammler_2ID']."');\n";
      echo "</script>\n";
      $id = $_POST['ID'];
      $sw = false;
    }
  }
  if ($sw) {
    if (intval($_POST['ID'])) {
      if (($_SESSION['editControl'] & 0x1000)!=0)
        $sql = "UPDATE tbl_collector_2 SET ".
               "Sammler_2='".mysql_escape_string($_POST['Sammler_2'])."' ".
               "WHERE Sammler_2ID='".intval($_POST['ID'])."'";
      else
        $sql = "";
    }
    else
      $sql = "INSERT INTO tbl_collector_2 (Sammler_2) ".
              "VALUES ('".mysql_escape_string($_POST['Sammler_2'])."')";
    $result = db_query($sql);
    $id = ($_POST['ID']) ? intval($_POST['ID']) : mysql_insert_id();

    echo "<script language=\"JavaScript\">\n";
    echo "  window.opener.document.f.sammler2.value = \"".addslashes($_POST['Sammler_2'])." <$id>\";\n";
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

$sql = "SELECT Sammler_2, Sammler_2ID FROM tbl_collector_2 WHERE Sammler_2ID='".mysql_escape_string($id)."'";
$result = db_query($sql);
$row = mysql_fetch_array($result);

$cf = new CSSF();

echo "<input type=\"hidden\" name=\"ID\" value=\"".$row['Sammler_2ID']."\">\n";
$cf->label(9,0.5,"ID");
$cf->text(9,0.5,"&nbsp;".(($row['Sammler_2ID'])?$row['Sammler_2ID']:"new"));
$cf->label(9,2,"add. Collector(s)");
$cf->inputText(9,2,25,"Sammler_2",$row['Sammler_2'],250);

if (($_SESSION['editControl'] & 0x1800)!=0) {
  $text = ($row['Sammler_2ID']) ? " Update " : " Insert ";
  $cf->buttonSubmit(2,7,"submitUpdate",$text);
  $cf->buttonJavaScript(12,7," New ","self.location.href='editCollector2.php?sel=<0>'");
}

echo "</form>\n";
?>

</body>
</html>