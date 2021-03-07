<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - edit Voucher</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
</head>

<body>

<?php
if ($_POST['submitUpdate'] && ($_SESSION['editControl'] & 0x2000) != 0) {
    $sw = true;
    $sql = "SELECT voucherID, voucher ".
           "FROM tbl_specimens_voucher ".
           "WHERE voucher=".quoteString($_POST['voucher'])." ".
            "AND voucherID!='".intval($_POST['ID'])."'";
    $result = dbi_query($sql);
    while (($row = mysqli_fetch_array($result)) && $sw) {
        if ($row['voucher'] == $_POST['voucher']) {
            echo "<script language=\"JavaScript\">\n"
               . "alert('Voucher \"" . $row['voucher'] . "\" already present with ID " . $row['voucherID'] . "');\n"
               . "</script>\n";
            $id = $_POST['ID'];
            $sw = false;
        }
    }
    if ($sw) {
        if (intval($_POST['ID'])) {
            $sql = "UPDATE tbl_specimens_voucher SET
                     voucher = '" . dbi_escape_string($_POST['voucher']) . "'
                    WHERE voucherID = " . intval($_POST['ID']);
        } else {
            $sql = "INSERT INTO tbl_specimens_voucher (voucher)
                    VALUES ('" . dbi_escape_string($_POST['voucher']) . "')";
        }
        $result = dbi_query($sql);

        echo "<script language=\"JavaScript\">\n"
           . "  window.opener.document.f.reload.click()\n"
           . "  self.close()\n"
           . "</script>\n"
           . "</body>\n</html>\n";
        die();
    }
} else {
    $id = intval($_GET['sel']);
}
?>

<form name="f" Action="<?php echo $_SERVER['PHP_SELF']; ?>" Method="POST">
<?php
$sql = "SELECT voucherID, voucher
        FROM tbl_specimens_voucher
        WHERE voucherID = '" . dbi_escape_string($id) . "'";
$result = dbi_query($sql);
$row = mysqli_fetch_array($result);

$cf = new CSSF();

echo "<input type=\"hidden\" name=\"ID\" value=\"" . $row['voucherID'] . "\">\n";
$cf->label(6, 0.5, "ID");
$cf->text(6, 0.5, "&nbsp;" . (($row['voucherID']) ? $row['voucherID'] : "new"));
$cf->label(6, 2, "voucher");
$cf->inputText(6, 2, 25, "voucher", $row['voucher'], 255);

if (($_SESSION['editControl'] & 0x2000) != 0) {
    $text = ($row['voucherID']) ? " Update " : " Insert ";
    $cf->buttonSubmit(9, 7, "submitUpdate", $text);
    $cf->buttonJavaScript(21, 7, " New ", "self.location.href='editVoucher.php?sel=0'");
}
?>
</form>

</body>
</html>