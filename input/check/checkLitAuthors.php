<?php
session_start();
require("../inc/connect.php");

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
      target = "../editLitAuthor.php?sel=" + encodeURIComponent(sel);
      MeinFenster = window.open(target,"editLitAuthor","width=500,height=200,top=50,left=50,scrollbars=yes,resizable=yes");
      MeinFenster.focus();
    }
  </script>
</head>

<body>
<h1>check Lit. Authors</h1>

<?php
/**
 * get addtl. lit authors
 */
$result = dbi_query("SELECT autorID, autor FROM tbl_lit_authors");
$authorsKnown = array();
$authorsUnknown = array();
$authorsUnknownID = array();
$ctrUnknown = 0;
while ($row = mysqli_fetch_array($result)) {
    $parts = preg_split('\., |\. & ', $row['autor']);  // split() is depricated as of PHP 5.3.0
    if (count($parts) > 1) {
        foreach ($parts as $part) {
            if (strlen(trim($part)) > 0) {
                if ($part[strlen($part)-1] != '.') $part .= ".";
                $result2 = dbi_query("SELECT autorID FROM tbl_lit_authors WHERE autor = '" . dbi_escape_string(trim($part)) . "'");
                if (mysqli_num_rows($result2)) {
                    $row2 = mysqli_fetch_array($result2);
                    $authorsKnown[$row2['autorID']] = trim($part);
                } else {
                    $authorsUnknown[$ctrUnknown] = trim($part);
                    $authorsUnknownID[$ctrUnknown++] = $row['autorID'];
                }
            }
        }
    }
}
?>
<table align="center">
  <tr>
    <th><?php echo count($authorsKnown); ?> known Lit. Authors</th>
    <th width="20"></th>
    <th><?php echo count($authorsUnknown); ?> unknown Lit. Authors</th>
  </tr><tr>
    <td>
      <?php
      if (count($authorsKnown)) {
          asort($authorsKnown);
          reset($authorsKnown);
          foreach ($authorsKnown as $key => $value) {
              echo "<a href=\"javascript:editAuthor('<$key>')\">" . htmlspecialchars($value) . "</a><br>\n      ";
          }
      }
      ?>
    </td><td>
    </td><td>
      <?php
      if (count($authorsUnknown)) {
          array_multisort($authorsUnknown, $authorsUnknownID);
          for ($i = 0; $i < count($authorsUnknown); $i++) {
              echo "<a href=\"javascript:editAuthor('<" . $authorsUnknownID[$i] . ">')\">" . htmlspecialchars($authorsUnknown[$i]) . "</a><br>\n      ";
          }
      }
      ?>
    </td>
  </tr>
</table>

</body>
</html>