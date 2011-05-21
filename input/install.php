<?php
require_once( 'inc/variables.php' );

$p_username = $p_password = $p_db_username = $p_db_password = $message = "";
if (!empty ($_POST['createUser'])) {
    $error = array();
    if (empty ($_POST['username'])) {
        $error[] = "No username provided.";
    } else {
        $p_username = $_POST['username'];
    }
    if (empty ($_POST['password'])) {
        $error[] = "No password provided.";
    } else {
        $p_password = $_POST['password'];
    }
    if (empty ($_POST['confirm_password']) || $_POST['confirm_password'] != $p_password) {
        $error[] = "Password mismatch.";
    }
    if (empty ($_POST['db_username'])) {
        $error[] = "No database username provided.";
    } else {
        $p_db_username = $_POST['db_username'];
    }
    if (empty ($_POST['db_password'])) {
        $error[] = "No database password provided.";
    } else {
        $p_db_password = $_POST['db_password'];
    }
    if (!$error) {
        try {
            $db = new PDO('mysql:host=' . $_CONFIG['DATABASE']['LOG']['host'] . ';dbname=' . $_CONFIG['DATABASE']['LOG']['name'],
                          $p_db_username,
                          $p_db_password,
                          array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET character set utf8"));
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        catch (PDOException $e) {
            $error[] = "Sorry, no connection to database: " . $e->getMessage();
        }
        if (!$error) {
            try {
                $dbst = $db->prepare("SELECT userID FROM tbl_herbardb_users WHERE username = :username");
                $dbst->execute(array(":username" => $p_username));
                $rows = $dbst->fetchAll();
                if (count($rows) > 0) {
                    $error[] = "User already exists.";
                } else {
                    $columnsConstraint = array();
                    foreach ($db->query("SHOW COLUMNS FROM tbl_herbardb_groups") as $row) {
                        if (substr($row['Type'], 0, 7) == 'tinyint') {
                            $columnsConstraint[] = "`" . $row['Field'] . "` = 1";
                        }
                    }
                    $dbst = $db->query("SELECT groupID FROM tbl_herbardb_groups WHERE " . implode(' AND ', $columnsConstraint));
                    $rows = $dbst->fetchAll();
                    if (count($rows) > 0) {
                        $gid = $rows[0]['groupID'];
                    } else {
                        $db->query("INSERT INTO tbl_herbardb_groups SET `group_name` = 'general administrators', " . implode(', ', $columnsConstraint));
                        $gid = $db->lastInsertId();
                    }
                    if ($p_password == $_POST['confirm_password']) {
                        $key = $p_username . " " . $p_password;
                        $input = $p_db_username . "%%" . $p_db_password;
                        $td = mcrypt_module_open('rijndael-256', '', 'cfb', '');
                        $iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_DEV_RANDOM);
                        mcrypt_generic_init($td, $key, $iv);
                        $encrypted_data = mcrypt_generic($td, $input);
                        mcrypt_generic_deinit($td);
                        mcrypt_module_close($td);
                        $dbst = $db->prepare("INSERT INTO tbl_herbardb_users SET groupID = '$gid', username = :username, iv = :iv, secret = :secret");
                        $dbst->execute(array(":username" => $p_username, ':iv' => base64_encode($iv), ':secret' => base64_encode($encrypted_data)));
                    }
                }
            }
            catch (PDOException $e) {
                $error[] = $e->getMessage();
            }
        }
    }
    $errorMessage = implode("<br>\n", $error);
    if (!$error) $message = "Done";
}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
    <title>herbardb - installer</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <link rel="stylesheet" type="text/css" href="css/screen.css">
    <style type="text/css">
        #passwords_mismatch { background-color: red; color: black; }
        #error { background-color: darkgreen; color: red; font-weight: bold; }
        #result { background-color: darkgreen; color: lightgreen; font-weight: bold; }
    </style>
    <script src="inc/jQuery/jquery.min.js" type="text/javascript"></script>
    <script type="text/javascript" language="JavaScript">
        $(document).ready(function() {
            $("#passwords_mismatch").hide();
            $("#confirm_password").keyup(function() {
                if ($("#password").val() != $("#confirm_password").val()) {
                    $("#passwords_mismatch").show();
                } else {
                    $("#passwords_mismatch").hide();
                }
            });
        });
    </script>
</head>
<body>

<h1>Create new administrator</h1>
<h2>
    To create a new adnimistrator You need to enter the username and the password of this user and the username and password of a
    database user who has write access to the Log database which also contains the user data.
</h2>
<form Action="<?php echo $_SERVER['PHP_SELF']; ?>" Method="POST">
    <table cellspacing="5" cellpadding="0">
        <tr>
            <td align="right">&nbsp;<b>username:</b></td>
            <td><input type="text" name="username" value="<?php echo $p_username; ?>"></td>
        </tr><tr>
            <td align="right">&nbsp;<b>password:</b></td>
            <td><input type="password" name="password" id="password" value="<?php echo $p_password; ?>"></td>
        </tr><tr>
            <td align="right">&nbsp;<b>confirm password:</b></td>
            <td>
                <input type="password" name="confirm_password" id="confirm_password">
                <span id="passwords_mismatch">Passwords do not match.</span>
            </td>
        </tr><tr>
            <td align="right">&nbsp;<b>database user:</b></td>
            <td><input type="text" name="db_username" value="<?php echo $p_db_username; ?>"></td>
        </tr><tr>
            <td align="right">&nbsp;<b>database user password:</b></td>
            <td><input type="password" name="db_password"></td>
        </tr><tr>
            <td></td>
            <td><input class="button" type="submit" name="createUser" value=" create user "></td>
        </tr>
    </table>
</form>
<span id="error"><?php echo $errorMessage; ?></span>
<span id="result"><?php echo $message; ?></span>

</body>
</html>
