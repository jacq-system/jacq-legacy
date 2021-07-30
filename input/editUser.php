<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");

if (isset($_GET['sel'])) {
    $sql = "SELECT *
            FROM herbarinput_log.tbl_herbardb_users
            WHERE userID = '" . intval($_GET['sel']) . "'";
    $result = dbi_query($sql);
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);

        $p_userID      = $row['userID'];
        $p_groupID     = $row['groupID'];
        $p_source_id   = $row['source_id'];
        $p_active      = $row['active'];
        $p_use_access  = $row['use_access'];
        $p_firstname   = $row['firstname'];
        $p_surname     = $row['surname'];
        $p_emailadress = $row['emailadress'];
        $p_phone       = $row['phone'];
        $p_mobile      = $row['mobile'];
        $p_editFamily  = $row['editFamily'];
        $p_username    = $row['username'];
    } else {
        $p_userID = $p_groupID = $p_source_id = $p_use_access  = 0;
        $p_active = 1;
        $p_username = $p_firstname = $p_surname = $p_emailadress = $p_phone = $p_mobile = $p_editFamily  = "";
    }
} else {
    $p_userID      = $_POST['userID'];
    $p_groupID     = $_POST['groupID'];
    $p_source_id   = $_POST['source_id'];
    $p_active      = isset($_POST['active']) ? $_POST['active'] : 0;
    $p_use_access  = isset($_POST['use_access']) ? $_POST['use_access'] : 0;
    $p_username    = $_POST['username'];
    $p_firstname   = $_POST['firstname'];
    $p_surname     = $_POST['surname'];
    $p_emailadress = $_POST['emailadress'];
    $p_phone       = $_POST['phone'];
    $p_mobile      = $_POST['mobile'];
    $p_editFamily  = $_POST['editFamily'];

    if (!empy($_POST['submitUpdate']) && checkRight('admin')) {
        $sqldata = "groupID = '"    . intval($p_groupID) . "',
                    source_id = '"  . intval($p_source_id) . "',
                    use_access = '" . (($p_use_access) ? 1 : 0) . "',
                    active = '"     . (($p_active) ? 1 : 0) . "',
                    username = "    . quoteString($p_username) . ",
                    firstname = "   . quoteString($p_firstname) . ",
                    surname = "     . quoteString($p_surname) . ",
                    emailadress = " . quoteString($p_emailadress) . ",
                    phone = "       . quoteString($p_phone) . ",
                    mobile = "      . quoteString($p_mobile) . ",
                    editFamily = "  . quoteString($p_editFamily);
        if (intval($p_userID)) {
            $sql = "UPDATE herbarinput_log.tbl_herbardb_users SET " . $sqldata . " WHERE userID = '" . intval($p_userID) . "'";
            dbi_query($sql);
        } else {
            $sql = "INSERT INTO herbarinput_log.tbl_herbardb_users SET " . $sqldata;
            dbi_query($sql);
            $p_userID = dbi_insert_id();
        }

        if ($_POST['password_1'] && $_POST['password_2'] && $_POST['password_1'] == $_POST['password_2']) {
//            $key = $p_username . " " . $_POST['password_1'];
//            $input = $_SESSION['username'] . "%%" . $_SESSION['password'];
//            $td = mcrypt_module_open('rijndael-256', '', 'cfb', '');
//            $iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_DEV_RANDOM);
//            mcrypt_generic_init($td, $key, $iv);
//            $encrypted_data = mcrypt_generic($td, $input);
//            mcrypt_generic_deinit($td);
//            mcrypt_module_close($td);

            $sql = "UPDATE herbarinput_log.tbl_herbardb_users SET "
//                 . " iv = " . quoteString(base64_encode($iv)) . ", "
//                 . " secret = " . quoteString(base64_encode($encrypted_data)) . ", "
                 . " pw='" . password_hash(trim($_POST['password_1']), PASSWORD_DEFAULT) . "' "
                 . "WHERE username = " . quoteString($p_username);
            dbi_query($sql);
        }

        //$location = "Location: listUsers.php";
        //if (SID) $location = $location . "?" . SID;
        //header($location);
    }
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - edit Users</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
  <script type="text/javascript" language="JavaScript">
    function openEditAccess(sel) {
      options = "width=";
      if (screen.availWidth<990)
        options += (screen.availWidth - 20) + ",height=";
      else
        options += "990, height=";
      if (screen.availHeight<710)
        options += (screen.availHeight - 20);
      else
        options += "710";
      options += ", top=20,left=20,scrollbars=yes,resizable=yes";

      newWindow = window.open("listUserAccess.php?sel="+sel,"editUserAccess",options);
      newWindow.focus();
    }
  </script>
</head>

<body>

<form Action="<?php echo $_SERVER['PHP_SELF']; ?>" Method="POST" name="f">
<?php
unset($group);
$sql = "SELECT groupID, group_name, group_description FROM herbarinput_log.tbl_herbardb_groups ORDER BY group_name";
if ($result = dbi_query($sql)) {
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_array($result)) {
            $group[0][] = $row['groupID'];
            $group[1][] = $row['group_name'] . " (" . $row['group_description'] . ")";
        }
    }
}

unset($source_id);
$sql = "SELECT source_id, source_code, source_name FROM herbarinput.meta ORDER BY source_name";
if ($result = dbi_query($sql)) {
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_array($result)) {
            $source_id[0][] = $row['source_id'];
            $source_id[1][] = $row['source_name'] . " (" . $row['source_code'] . ")";
        }
    }
}

$cf = new CSSF();

echo "<input type=\"hidden\" name=\"userID\" value=\"$p_userID\">\n";
$cf->label(9, 0.5, "userID");
$cf->text(9, 0.5, "&nbsp;" . (($p_userID) ? $p_userID : "<span style=\"background-color: red\">&nbsp;<b>new</b>&nbsp;</span>"));
$cf->labelMandatory(9, 2, 6, "Username");
$cf->inputText(9, 2, 40, "username", $p_username, 40);
$cf->labelMandatory(9, 4, 6, "Group");
$cf->dropdown(9, 4, "groupID", $p_groupID, $group[0], $group[1]);
$cf->labelMandatory(9, 6, 6, "Active");
$cf->checkbox(9, 6, "active", $p_active);
$cf->labelMandatory(9, 8, 6, "First name");
$cf->inputText(9, 8, 40, "firstname", $p_firstname, 40);
$cf->labelMandatory(9, 10, 6, "Name");
$cf->inputText(9, 10, 40, "surname", $p_surname, 40);
$cf->labelMandatory(9, 12, 6, "Email");
$cf->inputText(9, 12, 40, "emailadress", $p_emailadress, 40);
$cf->label(9, 14, "Phone");
$cf->inputText(9, 14, 40, "phone", $p_phone, 40);
$cf->label(9, 16, "Mobile");
$cf->inputText(9, 16, 40, "mobile", $p_mobile, 40);
$cf->label(9, 18, "Edit family");
$cf->inputText(9, 18, 40, "editFamily", $p_editFamily, 40);
$cf->label(9, 20, "Use access", "javascript:openEditAccess('$p_userID')");
$cf->checkbox(9, 20, "use_access", $p_use_access);
$cf->label(9, 22, "Source ID");
$cf->dropdown(9, 22, "source_id", $p_source_id, $source_id[0], $source_id[1]);

$cf->label(9, 26, "Password");
$cf->inputPassword(9, 26, 40, "password_1", 40);
$cf->label(9, 28, "repeat Password");
$cf->inputPassword(9, 28, 40, "password_2", 40);

if (checkRight('admin')) {
    if ($p_userID) {
        $cf->buttonJavaScript(12, 40, " Reload ", "self.location.href='editUser.php?sel=" . $p_userID . "'");
        $cf->buttonSubmit(20, 40, "submitUpdate", " Update ");
    } else {
        $cf->buttonReset(12, 40, " Reset ");
        $cf->buttonSubmit(20, 40, "submitUpdate", " Insert ");
    }
}
$cf->buttonJavaScript(2, 40, " < List ", "self.location.href='listUsers.php'");
?>
</form>

</body>
</html>
