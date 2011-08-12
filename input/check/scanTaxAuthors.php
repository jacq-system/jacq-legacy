<?php
session_start();
require("../inc/connect.php");
no_magic();

function parseAuthors ($text)
{
    $text = trim($text);
    while ($text[0] == '(') {
        $text = substr($text, 1);
    }
    $parts = split(') |, | & | ex | in ', trim($text));

    $authors = array();
    $skip == false;
    foreach ($parts as $k => $part) {
        if ($skip) {
            $skip = false;
        } else {
            $part = trim($part);
            if ($part) {
//                if ($part == 'Barbier' && !empty($parts[$k + 1]) && trim($parts[$k + 1]) == 'Cie') {
//                    $authors[] = 'Barbier & Cie';
//                    $skip = true;
//                } else {
                    $authors[] = $part;
//                }
            }
        }
    }

    return $authors;
}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - list unknown authors</title>
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
<h1>check Tax. Authors</h1>

<?php
/**
 * get addtl. Authors
 */
$authorsKnown = array();
$authorsUnknown = array();
$authorsUnknownID = array();
$ctrUnknown = 0;
$result = db_query("SELECT authorID, author FROM tbl_tax_authors");
while ($row = mysql_fetch_array($result)) {
    $authors = parseAuthors($row['author']);
    foreach ($authors as $author) {
        $author = trim($author);
        if (strlen($author) > 0) {
            $result2 = db_query("SELECT person_ID FROM tbl_person WHERE p_abbrev = '" . mysql_real_escape_string($author) . "'");
            if (mysql_num_rows($result2)) {
                $row2 = mysql_fetch_array($result2);
                $authorsKnown[$row2['person_ID']] = $author;
            } else {
                $authorsUnknown[$ctrUnknown] = $author;
                $authorsUnknownID[$ctrUnknown++] = $row['authorID'];
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
              echo "<a href=\"javascript:editAuthor('" . $authorsUnknownID[$i] . "')\">" . htmlspecialchars($authorsUnknown[$i]) . "</a><br>\n      ";
          }
      }
      ?>
    </td>
  </tr>
</table>

</body>
</html>