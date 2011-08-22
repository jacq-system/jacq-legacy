<?php
session_start();
require_once('../inc/tools.php');
require_once('../inc/variables.php');

$settings = clsSettings::Load();
// connect to the database or stop on any connect error
try {
    $db = new PDO('mysql:host=' . $settings->getSettings('DB', 'INPUT', 'HOST') . ';dbname=' . $settings->getSettings('DB', 'INPUT', 'NAME'),
                  $_SESSION['username'],
                  $_SESSION['password'],
                  array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET character set utf8"));
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    exit();
}

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
    function editGenera(sel) {
      target = "../editGenera.php?sel=" + encodeURIComponent(sel);
      MeinFenster = window.open(target,"editGenera","width=600,height=500,top=50,left=50,scrollbars=yes,resizable=yes");
      MeinFenster.focus();
    }
    function editSpecies(sel) {
      target = "../editSpecies.php?sel=<" + encodeURIComponent(sel) + ">";
      MeinFenster = window.open(target,"editSpecies","width=990,height=710,top=50,left=50,scrollbars=yes,resizable=yes");
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
try {
    $dbst = $db->query("SELECT authorID, author FROM tbl_tax_authors");
    foreach ($dbst as $row) {
        $authors = parseAuthors($row['author']);
        foreach ($authors as $author) {
            $author = trim($author);
            if (strlen($author) > 0) {
                $dbst2 = $db->prepare("SELECT person_ID FROM tbl_person WHERE p_abbrev = :p_abbrev");
                $dbst2->execute(array(':p_abbrev' => $author));
                $row2 = $dbst2->fetch();
                if ($row2) {
                    $authorsKnown[$row2['person_ID']] = $author;
                } else {
                    $authorsUnknown[$ctrUnknown] = $author;
                    $authorsUnknownID[$ctrUnknown++] = $row['authorID'];
                }
            }
        }
    }
}
catch (Exception $e) {
    echo $e->getMessage();
    exit();
}

$authorsUnknownUnused = array();
if (count($authorsUnknown)) {
    array_multisort($authorsUnknown, $authorsUnknownID);
    for ($i = 0; $i < count($authorsUnknown); $i++) {
        $used = array();
        $dbst = $db->prepare("SELECT taxonID
                              FROM tbl_tax_species
                              WHERE authorID = :aid
                               OR subspecies_authorID = :aid
                               OR variety_authorID = :aid
                               OR subvariety_authorID = :aid
                               OR forma_authorID = :aid
                               OR subforma_authorID = :aid");
        $dbst->execute(array(':aid' => $authorsUnknownID[$i]));
        foreach ($dbst as $row) {
            $used[] = array('type' => 'species', 'ID' => $row['taxonID']);
        }

        $dbst = $db->prepare("SELECT genID FROM tbl_tax_genera WHERE authorID = :aid");
        $dbst->execute(array(':aid' => $authorsUnknownID[$i]));
        foreach ($dbst as $row) {
            $used[] = array('type' => 'genera', 'ID' => $row['genID']);
        }

        if ($used) {
            $authorsUnknownUnused['unknown'][] = array('ID' => $authorsUnknownID[$i], 'name' => $authorsUnknown[$i], 'used' => $used);
        } else {
            $authorsUnknownUnused['unused'][]  = array('ID' => $authorsUnknownID[$i], 'name' => $authorsUnknown[$i]);
        }
    }
}
?>
<table align="center">
  <tr>
    <th><?php echo count($authorsKnown); ?> known Tax. Authors</th>
    <th width="20"></th>
    <th><?php echo count($authorsUnknownUnused['unknown']); ?> unknown Tax. Authors</th>
    <th width="20"></th>
    <th><?php echo count($authorsUnknownUnused['unused']); ?> unknown and unused Tax. Authors</th>
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
      if (count($authorsUnknownUnused['unknown'])) {
          foreach ($authorsUnknownUnused['unknown'] as $author) {
              echo "<a href=\"javascript:editAuthor('" . $author['ID'] . "')\">" . htmlspecialchars($author['name']) . "</a> (";
              $parts = array();
              foreach ($author['used'] as $used) {
                  switch ($used['type']) {
                      case 'species':
                          $parts[] = "<a href=\"javascript:editSpecies('" . $used['ID'] . "')\">s" . htmlspecialchars($used['ID']) . "</a>";
                          break;
                      case 'genera':
                          $parts[] = "<a href=\"javascript:editGenera('" . $used['ID'] . "')\">g" . htmlspecialchars($used['ID']) . "</a>";
                          break;
                  }
              }
              echo implode(', ', $parts) . ")<br>\n      ";
          }
      }
      ?>
    </td><td>
    </td><td>
      <?php
      if (count($authorsUnknownUnused['unused'])) {
          foreach ($authorsUnknownUnused['unused'] as $author) {
              echo "<a href=\"javascript:editAuthor('" . $author['ID'] . "')\">" . htmlspecialchars($author['name']) . "</a> (unused)<br>\n      ";
          }
      }
      ?>
    </td>
  </tr>
</table>

</body>
</html>