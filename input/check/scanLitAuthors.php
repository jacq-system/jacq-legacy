<?php
session_start();
require("../inc/connect.php");

function parseAuthors ($text)
{
    $text = strtr(trim($text), ';', ',');

    $parts = explode('&', $text);
    if (count($parts) > 1) {
        $last = trim($parts[1]);
        $text = trim($parts[0]);
    } else {
        $last = NULL;
    }
    if (substr($text, -1) == ',') $text = substr($text, 0, -1);
    $parts = explode(', ', $text);

    $authors = array();
    $skip = false;
    foreach ($parts as $k => $part) {
        if ($skip) {
            $skip = false;
        } else {
            $author = trim($part);
            if (!empty($parts[$k + 1])) {
                $c  = trim($parts[$k + 1]);
                $l1 = substr($c, -1);
                $l3 = substr($c, -3);
                if ($l1 == '.' || $l3 == 'des' || $l3 == 'van' || $l3 == 'der' || $l3 == ' de' || $l3 == 'von' ) {
                    $author .= ', ' . $c;
                    $skip = true;
                }
            }
            $authors[] = $author;
        }
    }

    return $authors;
}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - list unknown Authors</title>
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
    $authors = parseAuthors($row['autor']);
    foreach ($authors as $author) {
        $result2 = dbi_query("SELECT person_ID FROM tbl_person_alternative WHERE p_alternative = '" . dbi_escape_string($author) . "'");
        if (mysqli_num_rows($result2)) {
            $row2 = mysqli_fetch_array($result2);
            $authorsKnown[$row2['person_ID']] = $author;
        } else {
            $authorsUnknown[$ctrUnknown] = ($author) ? $author : '--';
            $authorsUnknownID[$ctrUnknown++] = $row['autorID'];
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
              echo htmlspecialchars($value) . "<br>\n      ";
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
