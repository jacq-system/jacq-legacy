<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");
require __DIR__ . '/vendor/autoload.php';

use Jaxon\Jaxon;

$jaxon = jaxon();
$jaxon->setOption('core.request.uri', 'ajax/editUserAccessServer.php');

$jaxon->register(Jaxon::CALLABLE_FUNCTION, "getFamilyDropdown");
$jaxon->register(Jaxon::CALLABLE_FUNCTION, "getGenusDropdown");

if (isset($_GET['id']) && isset($_GET['sel'])) {
    $p_userID = intval($_GET['id']);
    $p_accessID = intval($_GET['sel']);

    $sql = "SELECT *
            FROM herbarinput_log.tbl_herbardb_access
            WHERE ID = '$p_accessID'";
    $result = db_query($sql);
    if (mysql_num_rows($result) > 0) {
        $row = mysql_fetch_array($result);
        $p_categoryID = $row['categoryID'];
        $p_familyID   = $row['familyID'];
        $p_genID      = $row['genID'];
        $p_update     = $row['update'];
    } else {
        $p_categoryID = $p_familyID = $p_genID = $p_update  = 0;
    }
} else {
    $p_userID      = $_POST['userID'];
    $p_accessID    = $_POST['accessID'];
    $p_categoryID  = $_POST['categoryID'];
    $p_familyID    = $_POST['familyID'];
    $p_genID       = $_POST['genID'];
    $p_update      = $_POST['update'];

    if ($_POST['submitUpdate'] && checkRight('admin')) {
        $sqldata = "userID = '" . intval($p_userID) . "',
                    categoryID = " . ((intval($p_categoryID)) ? "'" . intval($p_categoryID) . "'" : "NULL") . ",
                    familyID = " . ((intval($p_familyID)) ? "'" . intval($p_familyID)."'" : "NULL") . ",
                    genID = " . ((intval($p_genID)) ? "'" . intval($p_genID) . "'" : "NULL") . ",
                    `update` = '" . (($p_update) ? 1 : 0) . "'";
        if (intval($p_accessID))  {
            $sql = "UPDATE herbarinput_log.tbl_herbardb_access SET " . $sqldata . " WHERE ID = '" . intval($p_accessID) . "'";
            db_query($sql);
        } else {
            $sql = "INSERT INTO herbarinput_log.tbl_herbardb_access SET " . $sqldata;
            db_query($sql);
            $p_accessID = mysql_insert_id();
        }
    }
}

$row = mysql_fetch_array(db_query("SELECT username FROM herbarinput_log.tbl_herbardb_users WHERE userID = '$p_userID'"));
$username = $row['username'];

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - edit User access</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <?php echo $jaxon->getScript(true, true); ?>
  <script type="text/javascript" language="JavaScript">
    function call_getFamilyDropdown() {
      jaxon_getFamilyDropdown(jaxon.getFormValues('f',0,'categoryID'));
    }
    function call_getGenusDropdown() {
      jaxon_getGenusDropdown(jaxon.getFormValues('f',0,'familyID'));
    }
  </script>
</head>

<body>

<form Action="<?php echo $_SERVER['PHP_SELF']; ?>" Method="POST" name="f" id="f">
<?php
unset($category);
$category[0][] = 0; $category[1][] = "";
$sql = "SELECT categoryID, category, cat_description FROM tbl_tax_systematic_categories ORDER BY category";
if ($result = db_query($sql)) {
    if (mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_array($result)) {
            $category[0][] = $row['categoryID'];
            $category[1][] = $row['category'] . " (" . $row['cat_description'] . ")";
        }
    }
}

unset($family);
$family[0][] = 0; $family[1][] = "";
$sql = "SELECT familyID, family FROM tbl_tax_families WHERE categoryID = '" . intval($p_categoryID) . "' ORDER BY family";
if ($result = db_query($sql)) {
    if (mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_array($result)) {
            $family[0][] = $row['familyID'];
            $family[1][] = $row['family'];
        }
    }
}

unset($genus);
$genus[0][] = 0; $genus[1][] = "";
$sql = "SELECT genID, genus FROM tbl_tax_genera WHERE familyID = '" . intval($p_familyID) . "' ORDER BY genus";
if ($result = db_query($sql)) {
    if (mysql_num_rows($result) > 0) {
        while ($row = mysql_fetch_array($result)) {
            $genus[0][] = $row['genID'];
            $genus[1][] = $row['genus'];
        }
    }
}

$cf = new CSSF();

echo "<input type=\"hidden\" name=\"userID\" value=\"$p_userID\">\n";
echo "<input type=\"hidden\" name=\"accessID\" value=\"$p_accessID\">\n";
$cf->label(9, 0.5, "Username");
$cf->text(9, 0.5, "&nbsp;" . $username);
$cf->label(9, 2, "accessID");
$cf->text(9, 2, "&nbsp;" . (($p_accessID) ? $p_accessID : "<span style=\"background-color: red\">&nbsp;<b>new</b>&nbsp;</span>"));

$cf->label(9, 4, "Category");
$cf->dropdown(9, 4, "categoryID\" onchange=\"call_getFamilyDropdown()", $p_categoryID, $category[0], $category[1]);
$cf->label(9, 6, "Family");
$cf->dropdown(9, 6, "familyID\" id=\"familyID\" onchange=\"call_getGenusDropdown()", $p_familyID, $family[0], $family[1]);
$cf->label(9, 8, "Genus");
$cf->dropdown(9, 8, "genID\" id=\"genID", $p_genID, $genus[0], $genus[1]);
$cf->label(9, 10, "update");
$cf->checkbox(9, 10, "update", $p_update);

if (checkRight('admin')) {
    if ($p_userID) {
        $cf->buttonJavaScript(12, 14, " Reload ", "self.location.href='editUserAccess.php?id=$p_userID&sel=$p_accessID'");
        $cf->buttonSubmit(20, 14, "submitUpdate", " Update ");
    } else {
        $cf->buttonReset(12, 14, " Reset ");
        $cf->buttonSubmit(20, 14, "submitUpdate", " Insert ");
    }
}
$cf->buttonJavaScript(2, 14, " < List ", "self.location.href='listUserAccess.php?sel=$p_userID'");
?>

</body>
</html>