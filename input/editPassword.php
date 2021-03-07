<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");

$error = "";
if (!empty($_POST['submitUpdate'])) {
    if (!$_POST['password_1'] || !$_POST['password_2']) {
        $error = "Fill in both password-lines";
    } else if ($_POST['password_1'] != $_POST['password_2']) {
        $error = "Both Passwords must match!";
    } else {
        $sql = "SELECT *
                FROM herbarinput_log.tbl_herbardb_users
                WHERE userID='".intval($_SESSION['uid'])."'";
        $result = dbi_query($sql);
        if (mysqli_num_rows($result)>0) {
            $row = mysqli_fetch_assoc($result);

//            $key = $row['username'] . " " . $_POST['password_1'];
//            $input = $_SESSION['username'] . "%%" . $_SESSION['password'];
//            $td = mcrypt_module_open('rijndael-256', '', 'cfb', '');
//            $iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_DEV_RANDOM);
//            mcrypt_generic_init($td, $key, $iv);
//            $encrypted_data = mcrypt_generic($td, $input);
//            mcrypt_generic_deinit($td);
//            mcrypt_module_close($td);

            $sql = "UPDATE herbarinput_log.tbl_herbardb_users SET "
//                 . " iv=" . quoteString(base64_encode($iv)) . ", "
//                 . " secret=" . quoteString(base64_encode($encrypted_data)) . ", "
                 . " pw='" . password_hash(trim($_POST['password_1']), PASSWORD_DEFAULT) . "' "
                 . "WHERE userID = '" . intval($_SESSION['uid']) . "'";
            dbi_query($sql);

            echo "<html><head></head>\n<body>\n";
            echo "<script language=\"JavaScript\">\n";
            echo "  self.close()\n";
            echo "</script>\n";
            echo "</body>\n</html>\n";
        }
    }
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - change password</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
</head>

<body>

<h1>change Password</h1>

<form Action="<?php echo $_SERVER['PHP_SELF']; ?>" Method="POST" name="f">
<?php

$cf = new CSSF();

if ($error) {
    $cf->text(1, 4, '<span style="font-weight:bold; font-size:large; color: red;">' . $error . '</span>');
}

$cf->label(9, 7, "Password");
$cf->inputPassword(9, 7, 40, "password_1", 40);
$cf->label(9, 9, "repeat Password");
$cf->inputPassword(9, 9, 40, "password_2", 40);

$cf->buttonSubmit(9, 12, "submitUpdate", " Update ");
?>
</form>

</body>
</html>
