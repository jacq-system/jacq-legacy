<?php
session_start();
require("inc/connect.php");
require("inc/herbardb_input_functions.php");
no_magic();

$id = intval($_GET['ID']);

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - list Taxa</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <style type="text/css">
    table.out { width: 100% }
    tr.out { }
    th.out { font-style: italic }
    td.out { background-color: #669999; }
  </style>
  <script type="text/javascript" language="JavaScript">
    function editLitPersons(id,n) {
      target = "editLitPersons.php?ID=" + id;
      if (n) target += "&new=1";
      MeinFenster = window.open(target,"editLitPersons","width=800,height=550,top=70,left=70,scrollbars=yes,resizable=yes");
      MeinFenster.focus();
    }
  </script>
</head>

<body>

<?php
$sql ="SELECT citationID, suptitel, le.autor as editor, la.autor, l.periodicalID, lp.periodical, vol, part, jahr, pp
       FROM tbl_lit l
        LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID = l.periodicalID
        LEFT JOIN tbl_lit_authors le ON le.autorID = l.editorsID
        LEFT JOIN tbl_lit_authors la ON la.autorID = l.autorID
       WHERE citationID = '$id'";
$result = db_query($sql);
$row = mysql_fetch_array($result);
echo "<b>protolog:</b> " . protolog($row) . "\n<p>\n";
$sql = "SELECT lp.lit_persons_ID, lp.annotations, p.person_ID, p.p_firstname, p.p_familyname, p.p_birthdate, p.p_death
        FROM tbl_lit_persons lp, tbl_person p
        WHERE lp.personID_fk = p.person_ID
         AND lp.citationID_fk = '$id'
        ORDER BY p.p_familyname";
$result = db_query($sql);

echo "<p>\n";
echo "<form Action=\"" . $_SERVER['PHP_SELF'] . "\" Method=\"GET\" name=\"f\">\n";
if (($_SESSION['editControl'] & 0x20) != 0) {
    echo "<table><tr><td>\n"
       . "<input class=\"cssfbutton\" type=\"button\" value=\" add new Line \" onClick=\"editLitPersons('<$id>',1)\">\n"
       . "</td><td width=\"20\">&nbsp;</td><td>\n"
       . "<input class=\"cssfbutton\" type=\"submit\" name=\"reload\" value=\"Reload\">\n"
       . "</td><td width=\"20\">&nbsp;</td><td>\n"
       . "<input class=\"cssfbutton\" type=\"button\" value=\" close \" onclick=\"self.close()\">\n"
       . "</td></tr></table>\n";
}
echo "<input type=\"hidden\" name=\"r\" value=\"1\">\n";
echo "<input type=\"hidden\" name=\"ID\" value=\"$id\">\n";
echo "</form><p>\n";

echo "<table class=\"out\" cellspacing=\"2\" cellpadding=\"2\">\n";
echo "<tr class=\"out\">"
   . "<th></th>"
   . "<th class=\"out\">&nbsp;Name&nbsp;</th>"
   . "<th class=\"out\">&nbsp;annotations&nbsp;</th>"
   . "</tr>\n";
if (mysql_num_rows($result) > 0) {
    while ($row = mysql_fetch_array($result)) {
        echo "<tr class=\"out\">"
           . "<td class=\"out\">"
           . "<a href=\"javascript:editLitPersons('<" . $row['lit_persons_ID'] . ">',0)\">edit</a>"
           . "</td>"
           . "<td class=\"out\">"
           . $row['p_familyname'] . ", " . $row['p_firstname']
           . (($row['p_birthdate'] || $row['p_death']) ? " (" . $row['p_birthdate'] . " - " . $row['p_death'] . ")" : "")
           . " <" . $row['person_ID'] . ">"
           . "</td>"
           . "<td class=\"out\">" . $row['annotations'] . "</td>"
           . "</tr>\n";
    }
} else {
    echo "<tr class=\"out\"><td class=\"out\" colspan=\"4\">no entries</td></tr>\n";
}
echo "</table>\n";

?>

</body>
</html>