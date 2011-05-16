<?php
session_start();
require("inc/connect.php");
require("inc/herbardb_input_functions.php");
no_magic();

$id = intval($_GET['ID']);

function makePeriodical($row) {

  $results = $row['periodical']." <".$row['periodicalID'].">";
  return $results;
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - list Periodicals Libraries</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <style type="text/css">
    table.out { width: 100% }
    tr.out { }
    th.out { font-style: italic }
    td.out { background-color: #669999; }
  </style>
  <script type="text/javascript" language="JavaScript">
    function editPeriodicalLib(id,n) {
      target = "editPeriodicalLib.php?ID=" + id;
      if (n)
        target += "&new=1";
      MeinFenster = window.open(target,"editPeriodicalLib","width=800,height=310,top=70,left=70,scrollbars=yes,resizable=yes");
      MeinFenster.focus();
    }
  </script>
</head>

<body>

<?php
$sql = "SELECT periodical, periodicalID ".
       "FROM tbl_lit_periodicals ".
       "WHERE periodicalID='$id'";
$result = db_query($sql);
$row = mysql_fetch_array($result);
echo "<b>Periodical:</b> ".$row['periodical']." <".$row['periodicalID'].">\n<p>\n";
$sql = "SELECT lib_period_ID, signature, bestand, url, library ".
       "FROM tbl_lit_lib_period, tbl_lit_libraries ".
       "WHERE tbl_lit_lib_period.library_ID=tbl_lit_libraries.library_ID ".
        "AND periodicalID='$id' ORDER BY library";
$result = db_query($sql);
echo "<table class=\"out\" cellspacing=\"2\" cellpadding=\"2\">\n";
echo "<tr class=\"out\">";
echo "<th></th>";
echo "<th class=\"out\">&nbsp;library&nbsp;</th>";
echo "<th class=\"out\">&nbsp;signature&nbsp;</th>";
echo "<th class=\"out\">&nbsp;stock&nbsp;</th>";
echo "</tr>\n";
if (mysql_num_rows($result)>0) {
  while ($row=mysql_fetch_array($result)) {
    echo "<tr class=\"out\">";
    echo "<td class=\"out\">".
         "<a href=\"javascript:editPeriodicalLib('<".$row['lib_period_ID'].">',0)\">edit</a>".
         "</td>";
    echo "<td class=\"out\">".$row['library']."</td>";
    echo "<td class=\"out\">".$row['signature']."</td>";
    echo "<td class=\"out\">".$row['bestand']."</td>";
    echo "</tr>\n";
  }
} else
  echo "<tr class=\"out\"><td class=\"out\" colspan=\"4\">no entries</td></tr>\n";
echo "</table>\n";

echo "<p>\n";
echo "<form Action=\"".$_SERVER['PHP_SELF']."\" Method=\"GET\" name=\"f\">\n";
if (($_SESSION['editControl'] & 0x80)!=0) {
  echo "<table><tr><td>\n";
  echo "<input class=\"cssfbutton\" type=\"button\" value=\" add new Line \" ".
       "onClick=\"editPeriodicalLib('<$id>',1)\">\n";
  echo "</td><td width=\"20\">&nbsp;</td><td>\n";
  echo "<input class=\"cssfbutton\" type=\"submit\" name=\"reload\" value=\"Reload\">\n";
  echo "</td><td width=\"20\">&nbsp;</td><td>\n";
  echo "<input class=\"cssfbutton\" type=\"button\" value=\" close \" onclick=\"self.close()\">\n";
  echo "</td></tr></table>\n";
}
echo "<input type=\"hidden\" name=\"ID\" value=\"$id\">\n";
echo "</form>\n";
?>

</body>
</html>