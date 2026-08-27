<?php
session_start();
require("inc/connect.php");
require __DIR__ . '/vendor/autoload.php';

use Jacq\Cssf;
use Jacq\Log;
use Jacq\Permission;

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - edit Family</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
</head>

<body>

<?php
if ($_POST['submitUpdate'] && intval($_POST['ID']) && Permission::has('specim')) {
  $sql = "UPDATE tbl_specimens ".
         "SET ncbi_accession='".dbi_escape_string($_POST['ncbi'])."' ".
         "WHERE specimen_ID=".intval($_POST['ID']);
  $result = dbi_query($sql);
  $id = ($_POST['ID']) ? intval($_POST['ID']) : dbi_insert_id();
  Log::specimen($id,1);

  echo "<script language=\"JavaScript\">\n";
  echo "  self.close()\n";
  echo "</script>\n";
}
else {
  echo "<form name=\"f\" Action=\"".$_SERVER['PHP_SELF']."\" Method=\"POST\">\n";

  $sql = "SELECT specimen_ID, ncbi_accession FROM tbl_specimens ".
         "WHERE specimen_ID='".dbi_escape_string(intval($_GET['id']))."'";
  $result = dbi_query($sql);
  $row = mysqli_fetch_array($result);

  $cf = new Cssf();

  echo "<input type=\"hidden\" name=\"ID\" value=\"".$row['specimen_ID']."\">\n";
  $cf->label(7,0.5,"ID");
  $cf->text(7,0.5,"&nbsp;".(($row['specimen_ID'])?$row['specimen_ID']:"new"));
  $cf->label(7,2,"NCBI");
  $cf->inputText(7,2,12,"ncbi",$row['ncbi_accession'],50);

  if (Permission::has('specim'))
    $cf->buttonSubmit(2,7,"submitUpdate"," Update ");

  echo "</form>\n";
}
?>

</body>
</html>
