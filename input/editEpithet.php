<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - edit Epithet</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
</head>

<body>

<?php
if (!empty($_POST['submitUpdate']) && checkRight('epithet') && (checkRight('unlock_tbl_tax_epithets') || !isLocked('tbl_tax_epithets', $_POST['ID']))) {
    $sql = "SELECT epithetID, epithet, external
            FROM tbl_tax_epithets
            WHERE epithet = " . quoteString($_POST['epithet']) . "
             AND epithetID != " . intval($_POST['ID']);
    $result = dbi_query($sql);
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_array($result);
        echo "<script language=\"JavaScript\">\n";
        echo "alert('Epithet \"" . $row['epithet'] . "\" already present with ID " . $row['epithetID'] . "');\n";
        echo "</script>\n";
        $id = $_POST['ID'];
    } else {
        if (checkRight('unlock_tbl_tax_epithets')) {
            $lock = ", locked = " . (($_POST['locked']) ? "'1'" : "'0'");
        } else {
            $lock = "";
        }
        if (intval($_POST['ID'])) {
            $sql = "UPDATE tbl_tax_epithets SET
                     epithet = '" . dbi_escape_string($_POST['epithet']) . "',
                     external = " . ((!empty($_POST['external'])) ? 1 : 0) . "
                     $lock
                    WHERE epithetID = " . intval($_POST['ID']);
        } else {
            $sql = "INSERT INTO tbl_tax_epithets SET
                     epithet = '" . dbi_escape_string($_POST['epithet']) . "'
                     $lock";
        }
        $result = dbi_query($sql);
        $id = ($_POST['ID']) ? intval($_POST['ID']) : dbi_insert_id();

        echo "<script language=\"JavaScript\">\n";
        switch ($_REQUEST['typ']) {
            case 's':  echo "  window.opener.document.f.subspecies.value = \"" . addslashes($_POST['epithet']) . "\";\n";
                       echo "  window.opener.document.f.subspeciesIndex.value = $id;\n";
                       break;
            case 'v':  echo "  window.opener.document.f.variety.value = \"" . addslashes($_POST['epithet']) . "\";\n";
                       echo "  window.opener.document.f.varietyIndex.value = $id;\n";
                       break;
            case 'sv': echo "  window.opener.document.f.subvariety.value = \"" . addslashes($_POST['epithet']) . "\";\n";
                       echo "  window.opener.document.f.subvarietyIndex.value = $id;\n";
                       break;
            case 'f':  echo "  window.opener.document.f.forma.value = \"" . addslashes($_POST['epithet']) . "\";\n";
                       echo "  window.opener.document.f.formaIndex.value = $id;\n";
                       break;
            case 'sf': echo "  window.opener.document.f.subforma.value = \"" . addslashes($_POST['epithet']) . "\";\n";
                       echo "  window.opener.document.f.subformaIndex.value = $id;\n";
                       break;
            default:   echo "  window.opener.document.f.species.value = \"" . addslashes($_POST['epithet']) . "\";\n";
                       echo "  window.opener.document.f.speciesIndex.value = $id;\n";
        }
        echo "  window.opener.document.f.reload.click()\n";
        echo "  self.close()\n";
        echo "</script>\n";
        echo "</body>\n</html>\n";
        die();
    }
} else {
    $id = intval($_GET['sel']);
}

echo "<form name=\"f\" Action=\"" . $_SERVER['PHP_SELF'] . "\" Method=\"POST\">\n";

$sql = "SELECT epithet, epithetID, locked, external
        FROM tbl_tax_epithets
        WHERE epithetID = " . intval($id);
$result = dbi_query($sql);
$row = mysqli_fetch_array($result);

$cf = new CSSF();

echo "<input type=\"hidden\" name=\"ID\" value=\"" . $row['epithetID'] . "\">\n";
$cf->label(7, 0.5, "ID");
$cf->text(7, 0.5, "&nbsp;" . (($row['epithetID']) ? $row['epithetID'] : "new"));

if (checkRight('unlock_tbl_tax_epithets')) {
    $cf->label(18, 0.5, "locked");
    $cf->checkbox(18, 0.5, "locked", $row['locked']);
} elseif (isLocked('tbl_tax_epithets', $row['epithetID'])) {
    $cf->label(20, 0.5, "locked");
    echo "<input type=\"hidden\" name=\"locked\" value=\"" . $row['locked'] . "\">\n";
}

$cf->label(7, 2, "Epithet");
$cf->inputText(7, 2, 12, "epithet", $row['epithet'], 50);
if ($row['external']) {
    $cf->label(18, 4, "external");
    $cf->checkbox(18, 4, "external", $row['external']);
}
if (checkRight('epithet') && (!isLocked('tbl_tax_epithets', $row['epithetID']) || checkRight('unlock_tbl_tax_epithets'))) {
    $text = ($row['epithetID']) ? " Update " : " Insert ";
    $cf->buttonSubmit(2, 6, "submitUpdate",$text);
    $cf->buttonJavaScript(15, 6, " New ", "self.location.href='editEpithet.php?sel=0&typ=" . $_REQUEST['typ'] . "'");
}

echo "<input type=\"hidden\" name=\"typ\" value=\"".$_REQUEST['typ']."\">\n";

echo "</form>\n";
?>

</body>
</html>