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
  <title>herbardb - search Collector</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <script language="JavaScript">
    function sendCollector(sel) {
      window.opener.document.f.sammler2.value = sel;
      window.opener.document.f.reload.click();
      self.close();
    }
  </script>
</head>

<body onload="document.f.author.focus()">

<?php
echo "<form name=\"f\" Action=\"".$_SERVER['PHP_SELF']."\" Method=\"POST\">\n";

$cf = new CSSF();

$cf->label(15,2,"search add. Collector(s)");
$cf->inputText(15,2,25,"sammler2",$_POST['sammler2'],120);
$cf->buttonSubmit(42,2,"submit"," Search ");
$cf->buttonJavaScript(52,2," Cancel ","self.close()");

echo "</form>\n";

if ($_POST['submit']) {
  echo "<div style=\"position: absolute; left: 2em; top: 5em;\">\n";

  $sql = "SELECT Sammler_2, Sammler_2ID ".
         "FROM tbl_collector_2 ".
         "WHERE Sammler_2 LIKE '%".mysql_escape_string($_POST['sammler2'])."%' ".
         "ORDER BY Sammler_2";
  if ($result = db_query($sql)) {
    if (mysql_num_rows($result)>0) {
      while ($row=mysql_fetch_array($result)) {
        $show = $row['Sammler_2']." <".$row['Sammler_2ID'].">";
        echo "<a href=\"\" onClick=\"sendCollector('".addslashes($show)."')\">".htmlspecialchars($show)."</a><br>\n";
      }
    }
  }

  echo "</div>\n";
}
?>

</body>
</html>