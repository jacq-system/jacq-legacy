<?php
require_once( 'inc/variables.php' );

$secure = $_CONFIG['CONNECTION']['secure'];  // set to false if no secure server available

session_set_cookie_params(0, "/", "", $secure);
session_start();

if (isset($_SESSION['username']) && isset($_SESSION['password'])) {
    $dbLink = mysqli_connect($_CONFIG['DATABASE']['INPUT']['host'],
                             $_SESSION['username'],
                             $_SESSION['password'],
                             $_CONFIG['DATABASE']['INPUT']['name']);

    if ($dbLink) {
        $location = "Location: menu.php";
        if (SID) {
            $location = $location . "?" . SID;
        }
        header($location);
    }
}


function getUnamePw($username, $password)
{
    global $_CONFIG;

    /** @var mysqli $dbLink */
    $ident = new mysqli($_CONFIG['DATABASE']['LOG']['host'],
                        $_CONFIG['DATABASE']['LOG']['readonly']['user'],
                        $_CONFIG['DATABASE']['LOG']['readonly']['pass'],
                        $_CONFIG['DATABASE']['LOG']['name']);
    $ident->set_charset('utf8');
    $hash = $ident->query("SELECT pw FROM tbl_herbardb_users WHERE username = '" . $ident->real_escape_string($username) . "'")->fetch_assoc()['pw'];
    $ident->close();
    if (password_verify(trim($password), $hash)) {
        return $_CONFIG['DATABASE']['INPUT']['readonly']['user'] . "%%" . $_CONFIG['DATABASE']['INPUT']['readonly']['pass'];
    } else {
        return "%%";
    }

//    $ident = @mysql_connect("localhost", $_CONFIG['DATABASE']['LOG']['readonly']['user'], $_CONFIG['DATABASE']['LOG']['readonly']['pass']);
//    mysql_query("SET character set utf8");
//    $sql = "SELECT username, iv, secret
//            FROM " . $_CONFIG['DATABASE']['LOG']['name'] . ".tbl_herbardb_users
//            WHERE username='".mysql_escape_string($username)."'";
//    $result = mysql_query($sql);
//    $row = mysql_fetch_array($result);
//    mysql_close($ident);
//
//    if (strlen($row['iv'])>0 && strlen($row['secret'])>0) {
//        $td = mcrypt_module_open('rijndael-256', '', 'cfb', '');
//        mcrypt_generic_init($td, $row['username']." ".$password, base64_decode($row['iv']));
//        $decrypted_data = mdecrypt_generic($td, base64_decode($row['secret']));
//        mcrypt_generic_deinit($td);
//        mcrypt_module_close($td);
//        return $decrypted_data;
//    } else {
//        return "%%";
//    }
}

// Seite anzeigen
function show_page($text)
{
    $username = (isset($_POST['username'])) ? $_POST['username'] : '';

?><!DOCTYPE html>
<html lang="en">
<head>
  <title>JACQ - login</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
 <!-- <style type="text/css">
    body { background-color: #008000; font-family: sans-serif; }
  </style>-->
   <link rel="stylesheet" href="./css/w3.css"> 
  <link rel="stylesheet" href="./css/w3-theme-jacqgreen.css"> 
   <link rel="stylesheet" href="./css/jacqinput.css"> 
  <script>
    function setfocus() { document.f.username.focus(); }
  </script>
</head>

<body onLoad="setfocus()" class= "w3-theme-l5">


    <div class="w3-container jacq-center">
        <a class = "" href="http://jacq.org" ><img src="./webimages/JACQ_LOGO.png" alt="JACQ" style="width:150px" ></a>
    </div>
    
    <?php if ($text) echo "<div class=\"w3-panel w3-red\">".$text."</div>"; ?>
  
<div class="w3-container">
    <div class="w3-display-container w3-display-middle">
  <form action="<?php echo htmlspecialchars($_SERVER['SCRIPT_NAME']); ?>" method="post" name=f>
    <table>
      <tr>
        <td colspan="2" class="w3-xlarge" ><b>Please log in:</b></td>
      </tr>
      <tr>
        <td>Username:</td>
        <td><input class="w3-input w3-border" type="text" size="20" name="username" value="<?php echo $username; ?>"></td>
      </tr>
      <tr>
        <td>Password:</td>
        <td><input class="w3-input w3-border" type="password" size="20" name="password" value=""></td>
      </tr>
    </table>
    <input class="w3-button w3-theme jacq-center" type="submit" name="submit" value="Login">
  </form>
  </div>
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
            $data = getUnamePw(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING), filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING));
            $parts = explode('%%', $data);
            $dbLink = mysqli_connect($_CONFIG['DATABASE']['INPUT']['host'], $parts[0], $parts[1], $_CONFIG['DATABASE']['INPUT']['name']);
            if ($dbLink) {
                $dbLink->set_charset('utf8');
                session_regenerate_id();  // prevent session fixation
//                $sql = "UPDATE herbarinput_log.tbl_herbardb_users SET
//                         login=NOW(),
//                         pw = '" . password_hash(trim($_POST['password']), PASSWORD_DEFAULT) . "'
//                        WHERE username='".mysql_escape_string($_POST['username'])."'";
//                mysql_query($sql);
                $sql = "SELECT *
                        FROM herbarinput_log.tbl_herbardb_users, herbarinput_log.tbl_herbardb_groups
                        WHERE herbarinput_log.tbl_herbardb_users.groupID=herbarinput_log.tbl_herbardb_groups.groupID
                        AND username = '" . $dbLink->real_escape_string($_POST['username'])."'";
                $row = $dbLink->query($sql)->fetch_array();
                $_SESSION['username']    = $parts[0];
                $_SESSION['password']    = $parts[1];
                $_SESSION['uid']         = $row['userID'];
                $_SESSION['gid']         = $row['groupID'];
                $_SESSION['sid']         = intval($row['source_id']);
                $_SESSION['editFamily']  = $row['editFamily'];
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
                if (SID) {
                    $location = $location."?".SID;
                }
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
