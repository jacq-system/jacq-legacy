<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");

if (isset($_GET['id']) && isset($_GET['sel'])) {
    $p_groupID = intval($_GET['id']);
    $p_accessID = intval($_GET['sel']);

    $sql = "SELECT *
            FROM herbarinput_log.tbl_herbardb_unlock
            WHERE ID = '$p_accessID'";
    $result = dbi_query($sql);
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_array($result);
        $p_table = $row['table'];
    } else {
        $p_table = "";
    }
} else {
    $p_groupID  = $_POST['groupID'];
    $p_accessID = $_POST['accessID'];
    $p_table    = $_POST['table'];

    if ($_POST['submitUpdate'] && checkRight('admin')) {
        $sqldata = "groupID = '" . intval($p_groupID) . "',
                    `table` = " . quoteString($p_table);
        if (intval($p_accessID))  {
            $sql = "UPDATE herbarinput_log.tbl_herbardb_unlock SET " . $sqldata . " WHERE ID = '" . intval($p_accessID) . "'";
            dbi_query($sql);
        } else {
            $sql = "INSERT INTO herbarinput_log.tbl_herbardb_unlock SET " . $sqldata;
            dbi_query($sql);
            $p_accessID = dbi_insert_id();
        }
    }
}

$row = dbi_query("SELECT group_name FROM herbarinput_log.tbl_herbardb_groups WHERE groupID = '$p_groupID'")->fetch_array();
$groupname = $row['group_name'];

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - edit Table Unlock</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
</head>

<body>

<form Action="<?php echo $_SERVER['PHP_SELF']; ?>" Method="POST" name="f" id="f">
<?php
unset($table);
$table[] = 'tbl_collector';
$table[] = 'tbl_collector_2';
$table[] = 'tbl_lit';
$table[] = 'tbl_lit_authors';
$table[] = 'tbl_lit_periodicals';
$table[] = 'tbl_lit_publishers';
$table[] = 'tbl_lit_taxa';
$table[] = 'tbl_specimens';
$table[] = 'tbl_specimens_series';
$table[] = 'tbl_specimens_types';
$table[] = 'tbl_tax_authors';
$table[] = 'tbl_tax_epithets';
$table[] = 'tbl_tax_families';
$table[] = 'tbl_tax_genera';
$table[] = 'tbl_tax_index';
$table[] = 'tbl_tax_species';
$table[] = 'tbl_tax_typecollections';
$table[] = 'tbl_name_applies_to';
$table[] = 'tbl_name_commons';


$cf = new CSSF();

echo "<input type=\"hidden\" name=\"groupID\" value=\"$p_groupID\">\n";
echo "<input type=\"hidden\" name=\"accessID\" value=\"$p_accessID\">\n";
$cf->label(9, 0.5, "Groupname");
$cf->text(9, 0.5, "&nbsp;" . $groupname);
$cf->label(9, 2, "accessID");
$cf->text(9, 2, "&nbsp;" . (($p_accessID) ? $p_accessID : "<span style=\"background-color: red\">&nbsp;<b>new</b>&nbsp;</span>"));

$cf->label(9, 4, "Table");
$cf->dropdown(9, 4, "table", $p_table, $table, $table);

if (checkRight('admin')) {
    if ($p_groupID) {
        $cf->buttonJavaScript(12, 14, " Reload ", "self.location.href='editGroupUnlock.php?id=$p_groupID&sel=$p_accessID'");
        $cf->buttonSubmit(20, 14, "submitUpdate", " Update ");
    } else {
        $cf->buttonReset(12, 14, " Reset ");
        $cf->buttonSubmit(20, 14, "submitUpdate", " Insert ");
    }
}
$cf->buttonJavaScript(2, 14, " < List ", "self.location.href='listGroupUnlock.php?sel=$p_groupID'");
?>

</body>
</html>