<?php
session_start();
require("inc/connect.php");

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - list missing Types</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <style type="text/css">
    th { font-weight: bold; font-size: medium }
    tr { vertical-align: top }
    td { vertical-align: top }
    .missing { margin: 0px; padding: 0px }
    td.missing { vertical-align: middle }
  </style>
  <script type="text/javascript" language="JavaScript">
    function getOptions() {
      options = "width=";
      if (screen.availWidth<990)
        options += (screen.availWidth - 10) + ",height=";
      else
        options += "990, height=";
      if (screen.availHeight<710)
        options += (screen.availHeight - 10);
      else
        options += "710";
      options += ", top=10,left=10,scrollbars=yes,resizable=yes";

      return options;
    }
    function editSpecimens(sel) {
      target = "editSpecimens.php?sel=" + encodeURIComponent(sel);
      newWindow = window.open(target,"Specimens",getOptions());
      newWindow.focus();
    }
    function editSpecies(sel) {
      target = "editSpecies.php?sel=" + encodeURIComponent(sel);
      newWindow = window.open(target,"Species",getOptions());
      newWindow.focus();
    }
  </script>
</head>

<body>
<h1>check Nomenclature</h1>

<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" name="f">

<table><tr>
<td>
  Institution:
  <select size="1" name="source_id">
  <option value="0">--- all ---</option>
<?php
  $sql = "SELECT source_name, tbl_management_collections.source_id
          FROM tbl_management_collections, herbarinput.meta
          WHERE tbl_management_collections.source_id = herbarinput.meta.source_id
          GROUP BY source_name ORDER BY source_name";
  $result = dbi_query($sql);
  while ($row = mysqli_fetch_array($result)) {
      echo "<option value=\"{$row['source_id']}\"";
      if ($_POST['source_id'] == $row['source_id']) echo " selected";
      echo ">{$row['source_name']}</option>\n";
  }
?>
  </select>
</td><td width="10">&nbsp;</td><td>
  Family: <input type="text" name="family" value="<?php echoSpecial('family', 'POST'); ?>">
</td><td width="10">&nbsp;</td><td>
  Genus: <input type="text" name="genus" value="<?php echoSpecial('genus', 'POST'); ?>">
</td><td width="10">&nbsp;</td><td>
  Author: <input type="text" name="author" value="<?php echoSpecial('author', 'POST'); ?>">
</td><td width="10">&nbsp;</td><td>
  Collector: <input type="text" name="collector" value="<?php echoSpecial('collector', 'POST'); ?>">
</td><td width="10">&nbsp;</td><td>
  <input type="submit" name="btnCheck" value="check">
</td>
</tr></table>
</form>

<?php
if (isset($_POST['btnCheck']) && $_POST['btnCheck']) {
    /**
     * check missing type information
     */
    $sql = "SELECT specimen_ID, coll_short
            FROM (tbl_specimens s, tbl_management_collections mc)
             LEFT JOIN tbl_specimens_types st ON s.specimen_ID = st.specimenID
             LEFT JOIN tbl_tax_species ts ON s.taxonID = ts.taxonID
             LEFT JOIN tbl_tax_authors ta ON ta.authorID = ts.authorID
             LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID = ts.subspecies_authorID
             LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID = ts.variety_authorID
             LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID = ts.subvariety_authorID
             LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID = ts.forma_authorID
             LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID = ts.subforma_authorID
             LEFT JOIN tbl_tax_genera tg ON ts.genID = tg.genID
             LEFT JOIN tbl_tax_families tf ON tg.familyID = tf.familyID
             LEFT JOIN tbl_collector tc ON s.SammlerID = tc.SammlerID
            WHERE mc.collectionID = s.collectionID
             AND s.typusID IS NOT NULL
             AND st.specimens_types_ID IS NULL";
    if (intval($_POST['source_id'])) $sql .= " AND mc.source_id = '" . intval($_POST['source_id']) . "'";
    if (trim($_POST['family'])) $sql .= " AND tf.family LIKE '" . dbi_escape_string(trim($_POST['family'])) . "%'";
    if (trim($_POST['genus'])) $sql .= " AND tg.genus LIKE '" . dbi_escape_string(trim($_POST['genus'])) . "%'";
    if (trim($_POST['author'])) {
        $sql .= " AND (   ta.author LIKE '%" . dbi_escape_string(trim($_POST['author'])) . "%'
                       OR ta1.author LIKE '%" . dbi_escape_string(trim($_POST['author'])) . "%'
                       OR ta2.author LIKE '%" . dbi_escape_string(trim($_POST['author'])) . "%'
                       OR ta3.author LIKE '%" . dbi_escape_string(trim($_POST['author'])) . "%'
                       OR ta4.author LIKE '%" . dbi_escape_string(trim($_POST['author'])) . "%'
                       OR ta5.author LIKE '%" . dbi_escape_string(trim($_POST['author'])) . "%' )";
    }
    if (trim($_POST['collector'])) $sql .= " AND tc.Sammler LIKE '" . dbi_escape_string(trim($_POST['collector'])) . "%'";
    $sql .= " ORDER BY coll_short, specimen_ID";
    $result = dbi_query($sql);
    unset($themesMissing);
    while ($row = mysqli_fetch_array($result)) {
        $themesMissing[$row['specimen_ID']] = htmlspecialchars($row['coll_short'])
                                            . " &mdash; "
                                            . htmlspecialchars($row['specimen_ID']);
    }

    /**
     * check missing protologs
     */
    $sql = "select st.taxonID, count(st.taxonID) AS cnt
            FROM (tbl_specimens_types st, tbl_specimens s, tbl_management_collections mc)
             LEFT JOIN tbl_tax_index ti ON st.taxonID = ti.taxonID
             LEFT JOIN tbl_tax_species ts ON st.taxonID = ts.taxonID
             LEFT JOIN tbl_tax_authors ta ON ta.authorID = ts.authorID
             LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID = ts.subspecies_authorID
             LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID = ts.variety_authorID
             LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID = ts.subvariety_authorID
             LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID = ts.forma_authorID
             LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID = ts.subforma_authorID
             LEFT JOIN tbl_tax_genera tg ON ts.genID = tg.genID
             LEFT JOIN tbl_tax_families tf ON tg.familyID = tf.familyID
             LEFT JOIN tbl_collector tc ON s.SammlerID = tc.SammlerID
            WHERE s.specimen_ID = st.specimenID
             AND mc.collectionID = s.collectionID
             AND ti.citationID IS NULL";
    if (intval($_POST['source_id'])) $sql .= " AND mc.source_id = '" . intval($_POST['source_id']) . "'";
    if (trim($_POST['family'])) $sql .= " AND tf.family LIKE '%" . dbi_escape_string(trim($_POST['family'])) . "%'";
    if (trim($_POST['genus'])) $sql .= " AND tg.genus LIKE '" . dbi_escape_string(trim($_POST['genus'])) . "%'";
    if (trim($_POST['author'])) {
        $sql .= " AND (   ta.author LIKE '%" . dbi_escape_string(trim($_POST['author'])) . "%'
                       OR ta1.author LIKE '%" . dbi_escape_string(trim($_POST['author'])) . "%'
                       OR ta2.author LIKE '%" . dbi_escape_string(trim($_POST['author'])) . "%'
                       OR ta3.author LIKE '%" . dbi_escape_string(trim($_POST['author'])) . "%'
                       OR ta4.author LIKE '%" . dbi_escape_string(trim($_POST['author'])) . "%'
                       OR ta5.author LIKE '%" . dbi_escape_string(trim($_POST['author'])) . "%' )";
    }
    if (trim($_POST['collector'])) $sql .= " AND tc.Sammler LIKE '" . dbi_escape_string(trim($_POST['collector'])) . "%'";
    $sql .= " GROUP BY st.taxonID ORDER BY st.taxonID";
    $result = dbi_query($sql);
    unset($protologMissing);
    while ($row = mysqli_fetch_array($result)) {
        $protologMissing[$row['taxonID']] = htmlspecialchars($row['taxonID'] . " (" . $row['cnt'] . " specimens)");
    }
?>
<table align="center">
  <tr>
    <th><?php echo count($themesMissing); ?> type information missing</th>
    <th width="20"></th>
    <th><?php echo count($protologMissing); ?> protolog information missing</th>
  </tr><tr>
    <td>
      <?php
      if (count($themesMissing)) {
          foreach ($themesMissing as $key => $value) {
              echo "<a href=\"javascript:editSpecimens('<$key>')\">$value</a><br>\n      ";
          }
      }
      ?>
    </td><td>
    </td><td>
      <?php
      if (count($protologMissing)) {
          foreach ($protologMissing as $key => $value) {
              echo "<a href=\"javascript:editSpecies('<$key>')\">$value</a><br>\n      ";
          }
      }
      ?>
    </td>
  </tr>
</table>
<?php } ?>

</body>
</html>