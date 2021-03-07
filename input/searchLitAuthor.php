<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - search Lit Author</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-81">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <script language="JavaScript">
    function sendAuthor(sel) {
      window.opener.document.f.autor.value = sel;
      window.opener.document.f.reload.click();
      self.close();
    }
  </script>
</head>

<body onload="document.f.autor.focus()">

<?php
echo "<form name=\"f\" Action=\"".$_SERVER['PHP_SELF']."\" Method=\"POST\">\n";

$cf = new CSSF();

$cf->label(8,2,"search Author");
$cf->inputText(8,2,25,"autor",$_POST['autor'],255);
$cf->buttonSubmit(35,2,"submit"," Search ");
$cf->buttonJavaScript(45,2," Cancel ","self.close()");

echo "</form>\n";

if ($_POST['submit']) {
  echo "<div style=\"position: absolute; left: 2em; top: 5em;\">\n";

  $sql = "SELECT autor, autorID ".
         "FROM tbl_lit_authors ".
         "WHERE autor LIKE '%".dbi_escape_string($_POST['autor'])."%' ".
         "ORDER BY autor";
  if ($result = dbi_query($sql)) {
    if (mysqli_num_rows($result)>0) {
      while ($row=mysqli_fetch_array($result)) {
        $show = $row['autor']." <".$row['autorID'].">";
        echo "<a href=\"\" onClick=\"sendAuthor('".addslashes($show)."')\">".htmlentities($show)."</a><br>\n";
      }
    }
  }

  echo "</div>\n";
}
?>

</body>
</html>