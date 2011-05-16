<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");
no_magic();

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - search Author</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <script language="JavaScript">
    function getIndex(data) {
      var pos1 = data.lastIndexOf(' <');
      var pos2 = data.lastIndexOf('>');
      return data.substring(pos1 + 2, pos2);
    }
    function getText(data) {
      var pos1 = data.lastIndexOf(' <');
      return data.substring(0,pos1);
    }
    function sendAuthor(sel) {
      window.opener.document.f.author.value = getText(sel);
      window.opener.document.f.authorIndex.value = getIndex(sel);
      window.opener.document.f.reload.click();
      self.close();
    }
  </script>
</head>

<body onload="document.f.autor.focus()">

<?php
echo "<form name=\"f\" Action=\"" . $_SERVER['PHP_SELF'] . "\" Method=\"POST\">\n";

$cf = new CSSF();

$cf->label(8, 2, "search Author");
$cf->inputText(8, 2, 25, "autor", isset($_POST['autor']) ? $_POST['autor'] : '', 255);
$cf->buttonSubmit(35, 2, "submit", " Search ");
$cf->buttonJavaScript(45, 2, " Cancel ", "self.close()");

echo "</form>\n";

if (!empty($_POST['submit'])) {
    echo "<div style=\"position: absolute; left: 2em; top: 5em;\">\n";

    $sql = "SELECT author, authorID, Brummit_Powell_full, IPNIauthor_IDfk
            FROM tbl_tax_authors
             LEFT JOIN tbl_person ON tbl_tax_authors.author = tbl_person.p_abbrev
            WHERE Brummit_Powell_full LIKE '%" . mysql_escape_string($_POST['autor']) . "%'
            ORDER BY author";
    if ($result = db_query($sql)) {
        if (mysql_num_rows($result) > 0) {
            echo "<table cellpadding=\"0\" cellspacing=\"0\">\n";
            while ($row = mysql_fetch_array($result)) {
                echo "<tr><td>";
                if ($row['IPNIauthor_IDfk']) echo "<a href=\"http://www.ipni.org/ipni/idAuthorSearch.do?id=" . $row['IPNIauthor_IDfk'] . "\" target=\"_blank\"><b><i>IPNI</i></b></a>&nbsp;&nbsp;&nbsp;";
                $show = $row['author'];
                if ($row['Brummit_Powell_full']) $show .= chr(194) . chr(183) . " [" . strtr($row['Brummit_Powell_full'], "\r\n\xa0", "   ") . "]";
                $show .= " <" . $row['authorID'] . ">";
                echo "</td><td><a href=\"\" onClick=\"sendAuthor('" . addslashes($show) . "')\">" . htmlspecialchars($show) . "</a></td></tr>\n";
            }
            echo "</table>\n";
        }
    }

    echo "</div>\n";
}
?>

</body>
</html>