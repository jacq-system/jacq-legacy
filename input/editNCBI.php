<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");
require("inc/log_functions.php");
no_magic();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - edit Family</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
</head>

<body>

<?php
if ($_POST['submitUpdate'] && intval($_POST['ID']) && (($_SESSION['editControl'] & 0x2000)!=0)) {
  $sql = "UPDATE tbl_specimens ".
         "SET ncbi_accession='".mysql_escape_string($_POST['ncbi'])."' ".
         "WHERE specimen_ID=".intval($_POST['ID']);
  $result = mysql_query($sql);
  $id = ($_POST['ID']) ? intval($_POST['ID']) : mysql_insert_id();
  logSpecimen($id,1);

  echo "<script language=\"JavaScript\">\n";
  echo "  self.close()\n";
  echo "</script>\n";
}
else {
  echo "<form name=\"f\" Action=\"".$_SERVER['PHP_SELF']."\" Method=\"POST\">\n";

  $sql = "SELECT specimen_ID, ncbi_accession FROM tbl_specimens ".
         "WHERE specimen_ID='".mysql_escape_string(intval($_GET['id']))."'";
  $result = db_query($sql);
  $row = mysql_fetch_array($result);

  $cf = new CSSF();

  echo "<input type=\"hidden\" name=\"ID\" value=\"".$row['specimen_ID']."\">\n";
  $cf->label(7,0.5,"ID");
  $cf->text(7,0.5,"&nbsp;".(($row['specimen_ID'])?$row['specimen_ID']:"new"));
  $cf->label(7,2,"NCBI");
  $cf->inputText(7,2,12,"ncbi",$row['ncbi_accession'],50);

  if (($_SESSION['editControl'] & 0x2000)!=0)
    $cf->buttonSubmit(2,7,"submitUpdate"," Update ");

  echo "</form>\n";
}
?>

</body>
</html>