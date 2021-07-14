<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");
require("inc/log_functions.php");

$update = (isset($_GET['update'])) ? intval($_GET['update']) : 0;

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - edit Family</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
</head>

<body>

<?php
$blocked = false;
if (!empty($_POST['submitUpdate'])) {
    if (checkRight('use_access')) {
        if (intval($_POST['genID'])) {
            // check if user has update rights for the old categoryID
            $sql = "SELECT ac.update
                    FROM herbarinput_log.tbl_herbardb_access ac, tbl_tax_families tf
                    WHERE tf.familyID = '" . intval($_POST['ID']) . "'
                     AND ac.categoryID = tf.categoryID
                     AND ac.userID = '" . $_SESSION['uid'] . "'";
            $result = dbi_query($sql);
            if (mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_array($result);
                if (!$row['update']) $blocked = true;  // no update access
            } else {
                $blocked = true;                       // no access at all
            }
        }

        // check if user has access to the new categoryID
        $sql = "SELECT ac.update
                FROM herbarinput_log.tbl_herbardb_access ac
                WHERE ac.categoryID = '" . dbi_escape_string($_POST['category']) . "'
                 AND ac.userID = '" . $_SESSION['uid']."'";
        $result = dbi_query($sql);
        if (mysqli_num_rows($result) == 0) $blocked = true; // no access
    }

    if (!checkRight('unlock_tbl_tax_families') && isLocked('tbl_tax_families', $_POST['ID'])) $blocked = true;

    if (checkRight('family') && !$blocked) {
        if (checkRight('unlock_tbl_tax_families')) {
            $lock = ", locked=" . (($_POST['locked']) ? "'1'" : "'0'");
        } else {
            $lock = "";
        }
        if (intval($_POST['ID'])) {
            $id = intval($_POST['ID']);
            if (strlen(trim($_POST['family'])) > 0 && intval($_POST['category'])) {
                $sql = "UPDATE tbl_tax_families SET
                         family = " . quoteString($_POST['family']) . ",
                         categoryID = " . makeInt($_POST['category']) . "
                         $lock
                        WHERE familyID = '" . intval($_POST['ID']) . "'";
                $result = dbi_query($sql);
                logFamilies($id, 1);
            }
        } else {
            $sql = "INSERT INTO tbl_tax_families SET
                     family = " . quoteString($_POST['family']) . ",
                     categoryID = " . makeInt($_POST['category']) . "
                     $lock";
            $result = dbi_query($sql);
            if ($result) {
                $id = dbi_insert_id();
                logFamilies($id,0);
            } else {
                $id = 0;
            }
        }

        $sql = "SELECT  tf.family, tsc.category
                FROM tbl_tax_families tf
                 LEFT JOIN tbl_tax_systematic_categories tsc ON tsc.categoryID = tf.categoryID
                WHERE familyID = '$id'";
        $result = dbi_query($sql);
        $row = mysqli_fetch_array($result);
        $ret = $row['family'] . " " . $row['category'];

        echo "<script language=\"JavaScript\">\n";
        if ($update) {
            echo "  window.opener.document.f.family.value = \"" . addslashes($ret) . "\";\n";
            echo "  window.opener.document.f.familyIndex.value = $id;\n";
        }
        echo "  window.opener.document.f.reload.click()\n";
        echo "  self.close()\n";
        echo "</script>\n";
    }
}


/**
 * normal operation
 */
if (!empty($_GET['new'])) {
    $p_family = $p_category = $p_locked = "";
    $p_familyID = 0;
} elseif (isset($_GET['sel']) && intval($_GET['sel']) > 0) {
    $sql = "SELECT family, familyID, categoryID, locked
            FROM tbl_tax_families
            WHERE familyID = " . intval($_GET['sel']);
    $result = dbi_query($sql);
    $row = mysqli_fetch_array($result);

    $p_family = $row['family'];
    $p_familyID = $row['familyID'];
    $p_category = $row['categoryID'];
    $p_locked = $row['locked'];
} else {
    $p_family = (isset($_POST['family'])) ? $_POST['family'] : '';
    $p_familyID = (isset($_POST['ID'])) ? $_POST['ID'] : '';
    $p_category = (isset($_POST['category'])) ? $_POST['category'] : '';
    $p_locked = (isset($_POST['locked'])) ? $_POST['locked'] : '';
}

unset($category);
$sql = "SELECT category, categoryID, cat_description FROM tbl_tax_systematic_categories ORDER BY category";
if ($result = dbi_query($sql)) {
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_array($result)) {
            $category[0][] = $row['categoryID'];
            $category[1][] = $row['category'] . " (" . $row['cat_description'] . ")";
        }
    }
}

if ($blocked) {
    echo "<script type=\"text/javascript\" language=\"JavaScript\">\n";
    echo "  alert('You have no sufficient rights for the desired operation');\n";
    echo "</script>\n";
}

echo "<form name=\"f\" Action=\"" . $_SERVER['PHP_SELF'] . "?update=$update\" Method=\"POST\">\n";

$cf = new CSSF();

echo "<input type=\"hidden\" name=\"ID\" value=\"" . $p_familyID . "\">\n";
$cf->label(7, 0.5, "ID");
$cf->text(7, 0.5, "&nbsp;" . (($p_familyID) ? $p_familyID : "new"));

if (checkRight('unlock_tbl_tax_families')) {
    $cf->label(18, 0.5, "locked");
    $cf->checkbox(18, 0.5, "locked", $p_locked);
} elseif (isLocked('tbl_tax_families', $p_familyID)) {
    $cf->label(20, 0.5, "locked");
    echo "<input type=\"hidden\" name=\"locked\" value=\"$p_locked\">\n";
}

$cf->label(7, 2, "Family");
$cf->inputText(7, 2, 12, "family", $p_family, 50);
$cf->label(7, 4, "Category");
$cf->dropdown(7, 4, "category", $p_category, $category[0], $category[1]);

if (checkRight('family') && (!isLocked('tbl_tax_families', $p_familyID) || checkRight('unlock_tbl_tax_families'))) {
  $text = ($p_familyID) ? " Update " : " Insert ";
  $cf->buttonSubmit(2, 7, "submitUpdate", $text);
  $cf->buttonJavaScript(12, 7, " New ", "self.location.href='editFamily.php?new=1'");
}

echo "</form>\n";
?>

</body>
</html>