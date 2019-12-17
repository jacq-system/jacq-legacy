<?php
require_once( 'inc/variables.php' );
require_once 'inc/password_compat/lib/password.php';

$secure = $_CONFIG['CONNECTION']['secure'];  // set to false if no secure server available

session_set_cookie_params(0, "/", "", $secure);
session_start();

if (isset($_SESSION['username']) && isset($_SESSION['password'])) {
    if (@mysql_connect( $_CONFIG['DATABASE']['INPUT']['host'], $_SESSION['username'], $_SESSION['password'])) {
        if (@mysql_select_db($_CONFIG['DATABASE']['INPUT']['name'])) {
            $location="Location: menu.php";
            if (SID) $location = $location . "?" . SID;
            header($location);
        }
    }
}


function getUnamePw($username, $password)
{
    global $_CONFIG;

    $ident = @mysql_connect("localhost", $_CONFIG['DATABASE']['LOG']['readonly']['user'], $_CONFIG['DATABASE']['LOG']['readonly']['pass']);
    mysql_query("SET character set utf8");
    $sql = "SELECT username, iv, secret
            FROM " . $_CONFIG['DATABASE']['LOG']['name'] . ".tbl_herbardb_users
            WHERE username='".mysql_escape_string($username)."'";
    $result = mysql_query($sql);
    $row = mysql_fetch_array($result);
    @mysql_close($ident);

    $iv = $row['iv'];
    $secret = $row['secret'];
    if (strlen($row['iv'])>0 && strlen($row['secret'])>0) {
        $td = mcrypt_module_open('rijndael-256', '', 'cfb', '');
        mcrypt_generic_init($td, $row['username']." ".$password, base64_decode($row['iv']));
        $decrypted_data = mdecrypt_generic($td, base64_decode($row['secret']));
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        return $decrypted_data;
    } else {
        return "%%";
    }
}

// Seite anzeigen
function show_page($text)
{
    $username = (isset($_POST['username'])) ? $_POST['username'] : '';

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0//EN">

<html>
<head>
  <title>HerbarDB - login</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <style type="text/css">
    body { background-color: #008000; font-family: sans-serif; }
  </style>
  <script type="text/javascript" language="JavaScript">
    function setfocus() { document.f.username.focus(); }
  </script>
</head>

<body onLoad="setfocus()">

<div align="center">
  <?php if ($text) echo "<h3>".$text."</h3>"; ?>

  <form action="<?php echo htmlspecialchars($_SERVER['SCRIPT_NAME']); ?>" method="post" name=f>
    <table>
      <tr>
        <td colspan="2"><font face="Helvetica,Arial,sans-serif" size="+2"><b>Please log in:</b></font></td>
      </tr>
      <tr>
        <td><font face="Helvetica,Arial,sans-serif">Username:</font></td>
        <td><input type="text" size="20" name="username" value="<?php echo $username; ?>"></td>
      </tr>
      <tr>
        <td><font face="Helvetica,Arial,sans-serif">Password:</font></td>
        <td><input type="password" size="15" name="password" value=""></td>
      </tr>
    </table>
    <input type="submit" name="submit" value="Login">
  </form>
</div>

</body>
</html>

<?php
}

//
// Hauptprogramm
//

if (isset($_SERVER['SSL_PROTOCOL']) || !$secure) {
    if (isset($_POST['submit']) && $_POST['submit']) {
        if ($_POST['username'] && $_POST['password']) {
            $data = getUnamePw($_POST['username'], $_POST['password']);
            $parts = explode('%%', $data);
            if (@mysql_connect("localhost",$parts[0],$parts[1])) {
                mysql_query("SET character set utf8");
                session_regenerate_id();  // prevent session fixation
                $sql = "UPDATE herbarinput_log.tbl_herbardb_users SET
                         login=NOW(),
                         pw = '" . password_hash(trim($_POST['password']), PASSWORD_DEFAULT) . "'
                        WHERE username='".mysql_escape_string($_POST['username'])."'";
                mysql_query($sql);
                $sql = "SELECT *
                        FROM herbarinput_log.tbl_herbardb_users, herbarinput_log.tbl_herbardb_groups
                        WHERE herbarinput_log.tbl_herbardb_users.groupID=herbarinput_log.tbl_herbardb_groups.groupID
                        AND username='".mysql_escape_string($_POST['username'])."'";
                $row = mysql_fetch_array(mysql_query($sql));
                $_SESSION['username'] = $parts[0];
                $_SESSION['password'] = $parts[1];
                $_SESSION['uid'] = $row['userID'];
                $_SESSION['gid'] = $row['groupID'];
                $_SESSION['sid'] = intval($row['source_id']);
                $_SESSION['editFamily'] = $row['editFamily'];
                $_SESSION['editControl'] = $row['species'] +
                                           $row['author']    *    0x2 +
                                           $row['epithet']   *    0x4 +
                                           $row['genera']    *    0x8 +
                                           $row['family']    *   0x10 +
                                           $row['lit']       *   0x20 +
                                           $row['litAuthor'] *   0x40 +
                                           $row['litPer']    *   0x80 +
                                           $row['litPub']    *  0x100 +
                                           $row['index']     *  0x200 +
                                           $row['type']      *  0x400 +
                                           $row['collIns']   *  0x800 +
                                           $row['collUpd']   * 0x1000 +
                                           $row['specim']    * 0x2000 +
                                           $row['dt']        * 0x4000 +
                                           $row['specimensTypes'] * 0x8000+
										   $row['commonnameUpdate'] * 0x10000+
                                           $row['commonnameInsert'] * 0x20000;

                $_SESSION['linkControl'] = $row['linkTaxon'];
                $_SESSION['editorControl'] = $row['editor'];
                $location="Location: menu.php";
                if (SID) $location = $location."?".SID;
                Header($location);
            } else {
                show_page("Login failed!<br>\nPlease redo!");
            }
        } else {
            show_page("You must provide both, username and password!<br>\nPlease redo!");
        }
    } else {
        show_page("");
    }
} else {
    Header("Location: https://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'] );
}
?>