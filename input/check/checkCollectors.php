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
    function editCollector(sel) {
      target = "../editCollector.php?sel=" + encodeURIComponent(sel);
      MeinFenster = window.open(target,"editCollector","width=350,height=130,top=50,left=50,scrollbars=yes,resizable=yes");
      MeinFenster.focus();
    }
    function editCollector2(sel) {
      target = "../editCollector2.php?sel=" + encodeURIComponent(sel);
      MeinFenster = window.open(target,"editCollector2","width=500,height=130,top=50,left=50,scrollbars=yes,resizable=yes");
      MeinFenster.focus();
    }
  </script>
</head>

<body>
<h1>check addtl. Collectors</h1>

<?php
/**
 * get addtl. Collectors
 */
$result = dbi_query("SELECT Sammler_2ID, Sammler_2 FROM tbl_collector_2");
$collectorsKnown = array();
$collectorsUnknown = array();
$collectorsUnknownID = array();
$ctrUnknown = 0;
while ($row = mysqli_fetch_array($result)) {
    $coll2 = trim(strtr($row['Sammler_2'], '&', ','));
    $parts = explode(', ', $coll2);
    foreach ($parts as $part) {
        if (strlen(trim($part)) > 0) {
            $result2 = dbi_query("SELECT SammlerID FROM tbl_collector WHERE Sammler = '" . dbi_escape_string(trim($part)) . "'");
            if (mysqli_num_rows($result2)) {
                $row2 = mysqli_fetch_array($result2);
                $collectorsKnown[$row2['SammlerID']] = trim($part);
            } else {
                $collectorsUnknown[$ctrUnknown] = trim($part);
                $collectorsUnknownID[$ctrUnknown++] = $row['Sammler_2ID'];
            }
        }
    }
}
?>
<table align="center">
  <tr>
    <th><?php echo count($collectorsKnown); ?> known 2nd Collectors</th>
    <th width="20"></th>
    <th><?php echo count($collectorsUnknown); ?> unknown 2nd Collectors</th>
  </tr><tr>
    <td>
      <?php
      if (count($collectorsKnown)) {
          asort($collectorsKnown);
          reset($collectorsKnown);
          foreach ($collectorsKnown as $key => $value) {
              echo "<a href=\"javascript:editCollector('<$key>')\">" . htmlspecialchars($value) . "</a><br>\n      ";
          }
      }
      ?>
    </td><td>
    </td><td>
      <?php
      if (count($collectorsUnknown)) {
          array_multisort($collectorsUnknown, $collectorsUnknownID);
          for ($i = 0; $i < count($collectorsUnknown); $i++) {
              echo "<a href=\"javascript:editCollector2('<" . $collectorsUnknownID[$i] . ">')\">" . htmlspecialchars($collectorsUnknown[$i]) . "</a><br>\n      ";
          }
      }
      ?>
    </td>
  </tr>
</table>

</body>
</html>