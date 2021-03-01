<?php
session_start();
require("../inc/connect.php");
no_magic();

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - list missing Types</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="../css/screen.css">
  <style type="text/css">
    th { font-weight: bold; font-size: medium }
    tr { vertical-align: top }
    td { vertical-align: top }
    .missing { margin: 0px; padding: 0px }
    td.missing { vertical-align: middle }
  </style>
  <script type="text/javascript" language="JavaScript">
    function editAuthor(sel) {
      target = "../editAuthor.php?sel=" + encodeURIComponent(sel);
      MeinFenster = window.open(target,"editAuthor","width=500,height=200,top=50,left=50,scrollbars=yes,resizable=yes");
      MeinFenster.focus();
    }
  </script>
</head>

<body>
<h1>check addtl. Tax. Authors</h1>

<?php
/**
 * get addtl. Authors
 */
$result = dbi_query("SELECT authorID, author FROM tbl_tax_authors");
$authorsKnown = array();
$authorsUnknown = array();
$authorsUnknownID = array();
$ctrUnknown = 0;
while ($row = mysqli_fetch_array($result)) {
    $author = $row['author'];
    if ($author[0] == '(' || strpos($author, ',') !== false || strpos($author, '&') !== false || strpos($author, 'ex') !== false) {
        $subparts = array();
        if (substr($author,0,1) == '(') {
            $subauthor = trim(substr($author, 1, strpos($author, ')') - 1));
            $author = trim(substr($author, strpos($author, ')') + 1));
            $subparts = split(', |& |ex ', $subauthor);
        }
        $parts = split(', |& |ex ', $author);
        $parts = array_merge($parts, $subparts);
        foreach ($parts as $part) {
            if (strlen(trim($part)) > 0) {
                $result2 = dbi_query("SELECT authorID FROM tbl_tax_authors WHERE author = '" . dbi_escape_string(trim($part)) . "'");
                if (mysqli_num_rows($result2)) {
                    $row2 = mysqli_fetch_array($result2);
                    $authorsKnown[$row2['authorID']] = trim($part);
                } else {
                    $authorsUnknown[$ctrUnknown] = trim($part);
                    $authorsUnknownID[$ctrUnknown++] = $row['authorID'];
                }
            }
        }
    }
}
?>
<table align="center">
  <tr>
    <th><?php echo count($authorsKnown); ?> known Tax. Authors</th>
    <th width="20"></th>
    <th><?php echo count($authorsUnknown); ?> unknown Tax. Authors</th>
  </tr><tr>
    <td>
      <?php
      if (count($authorsKnown)) {
          asort($authorsKnown);
          reset($authorsKnown);
          foreach ($authorsKnown as $key => $value) {
              echo "<a href=\"javascript:editAuthor('$key')\">" . htmlspecialchars($value) . "</a><br>\n      ";
          }
      }
      ?>
    </td><td>
    </td><td>
      <?php
      if (count($authorsUnknown)) {
          array_multisort($authorsUnknown, $authorsUnknownID);
          for ($i = 0; $i < count($authorsUnknown); $i++) {
              echo "<a href=\"javascript:editAuthor('" . $authorsUnknownID[$i] . "')\">" . htmlspecialchars($authorsUnknown[$i]) . "</a><br>\n      ";
          }
      }
      ?>
    </td>
  </tr>
</table>

</body>
</html>