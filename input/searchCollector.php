<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - search Collector</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <script language="JavaScript">
    function sendCollector(sel) {
      window.opener.document.f.sammler.value = sel;
      self.close();
    }
  </script>
</head>

<body onload="document.f.author.focus()">

<?php
echo "<form name=\"f\" Action=\"".$_SERVER['PHP_SELF']."\" Method=\"POST\">\n";

$cf = new CSSF();

$cf->label(10,2,"search Collector");
$cf->inputText(10,2,25,"sammler",$_POST['sammler'],120);
$cf->buttonSubmit(37,2,"submit"," Search ");
$cf->buttonJavaScript(47,2," Cancel ","self.close()");

echo "</form>\n";

if ($_POST['submit']) {
  echo "<div style=\"position: absolute; left: 2em; top: 5em;\">\n";

  $sql = "SELECT Sammler, SammlerID ".
         "FROM tbl_collector ".
         "WHERE Sammler LIKE '%".dbi_escape_string($_POST['sammler'])."%' ".
         "ORDER BY sammler";
  if ($result = dbi_query($sql)) {
    if (mysqli_num_rows($result)>0) {
      while ($row=mysqli_fetch_array($result)) {
        $show = $row['Sammler']." <".$row['SammlerID'].">";
        echo "<a href=\"\" onClick=\"sendCollector('".addslashes($show)."')\">".htmlspecialchars($show)."</a><br>\n";
      }
    }
  }

  echo "</div>\n";
}
?>

</body>
</html>