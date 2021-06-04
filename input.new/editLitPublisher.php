<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");
require("inc/log_functions.php");

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - edit Publisher</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
</head>

<body>

<?php
if ($_POST['submitUpdate'] && (($_SESSION['editControl'] & 0x100)!=0)) {
  $publisher = $_POST['publisher'];
  if (intval($_POST['ID'])) {
    $sql = "UPDATE tbl_lit_publishers SET ".
            "publisher=".quoteString($publisher)." ".
           "WHERE publisherID=".intval($_POST['ID']);
    $updated = 1;
  } else {
    $sql = "INSERT INTO tbl_lit_publishers (publisher) ".
           "VALUES (".quoteString($publisher).")";
    $updated = 0;
  }
  $result = dbi_query($sql);
  $id = ($_POST['ID']) ? intval($_POST['ID']) : dbi_insert_id();
  logLitPublishers($id,$updated);

  echo "<script language=\"JavaScript\">\n";
  echo "  window.opener.document.f.publisher.value = \"".addslashes($publisher)." <$id>\";\n";
  echo "  window.opener.document.f.reload.click()\n";
  echo "  self.close()\n";
  echo "</script>\n";
}
else {
  echo "<form name=\"f\" Action=\"".$_SERVER['PHP_SELF']."\" Method=\"POST\">\n";

  $pieces = explode("<",$HTTP_GET_VARS['sel']);
  $pieces = explode(">",$pieces[1]);
  $row = dbi_query("SELECT publisherID, publisher FROM tbl_lit_publishers WHERE publisherID = '" . dbi_escape_string($pieces[0]) . "'")->fetch_array();

  $cf = new CSSF();

  echo "<input type=\"hidden\" name=\"ID\" value=\"".$row['publisherID']."\">\n";
  $cf->label(8,0.5,"ID");
  $cf->text(8,0.5,"&nbsp;".(($row['publisherID'])?$row['publisherID']:"new"));
  $cf->label(8,2,"Publisher");
  $cf->textarea(8,2,25,4,"publisher",$row['publisher']);

  if (($_SESSION['editControl'] & 0x100)!=0) {
    $text = ($row['publisherID']) ? " Update " : " Insert ";
    $cf->buttonSubmit(9,9,"submitUpdate",$text);
    $cf->buttonJavaScript(21,9," New ","self.location.href='editLitPublisher.php?sel= '");
  }

  echo "</form>\n";
}
?>

</body>
</html>