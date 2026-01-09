<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");
require("inc/log_functions.php");

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - edit Lit Author</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
</head>

<body>

<?php
if ($_POST['submitUpdate'] && (($_SESSION['editControl'] & 0x40) != 0)) {
    $id = intval($_POST['ID']);
    $autor = $_POST['autor'];
    $autorsystbot = $_POST['autorsystbot'];

    $sw = true;
    $sql = "SELECT autorID, autor
            FROM tbl_lit_authors
            WHERE autor = " . quoteString($_POST['autor']) . "
             AND autorID != '" . intval($_POST['ID']) . "'";
    $result = dbi_query($sql);
    while (($row = mysqli_fetch_array($result)) && $sw) {
        if ($row['autor'] == $_POST['autor']) {
            echo "<script language=\"JavaScript\">\n";
            echo "alert('Author \"" . $row['autor'] . "\" already present with ID " . $row['autorID'] . "');\n";
            echo "</script>\n";
            $sw = false;
            break;
        }
    }

    if ($sw) {
        if ($id) {
            $sql = "UPDATE tbl_lit_authors SET
                     autor = " . quoteString($autor) . ",
                     autorsystbot = " . quoteString($autorsystbot) . "
                    WHERE autorID = $id";
            $updated = 1;
        } else {
            $sql = "INSERT INTO tbl_lit_authors SET
                     autor = " . quoteString($autor) . ",
                     autorsystbot = " . quoteString($autorsystbot);
            $updated = 0;
        }
        $result = dbi_query($sql);
        if ($result) {
            $id = ($id) ?: dbi_insert_id();
            logLitAuthors($id, $updated);
            echo "<script language=\"JavaScript\">\n";
            if ($_REQUEST['typ'] == "a") {
                echo "  window.opener.document.f.autorIndex.value = $id;\n";
                echo "  window.opener.document.f.reload.click()\n";
            } elseif ($_REQUEST['typ'] == "e") {
                echo "  window.opener.document.f.editorIndex.value = $id;\n";
                echo "  window.opener.document.f.reload.click()\n";
            }
            echo "  self.close()\n";
            echo "</script>\n";
            echo "</body>\n</html>\n";
            die();
        } else {
            echo "<script type='text/javascript' language='JavaScript'>\n"
                    . '  alert("' . $dbLink->errno . ': ' . $dbLink->error . '");' . "\n"
                    . "</script>\n";
        }
    }
} elseif ($_POST['submitUpdate'] && (($_SESSION['editControl'] & 0x40) != 0)) {
    $id = 0;
    $autor = $autorsystbot = "";
} else {
    $pieces = explode("<", $_GET['sel']);
    $pieces = explode(">", $pieces[1]);
    $result = dbi_query("SELECT autorID, autor, autorsystbot 
                         FROM tbl_lit_authors 
                         WHERE autorID = " . intval($pieces[0]));
    $row = mysqli_fetch_array($result);
    if (!empty($row)) {
        $id = $row['autorID'];
        $autor = $row['autor'];
        $autorsystbot = $row['autorsystbot'];
    } else {
        $id = 0;
        $autor = $autorsystbot = "";
    }
}

echo "<form name=\"f\" Action=\"" . $_SERVER['PHP_SELF'] . "\" Method=\"POST\">\n";

$cf = new CSSF();

echo "<input type=\"hidden\" name=\"ID\" value=\"" . $id . "\">\n";
$cf->label(8,0.5,"ID");
$cf->text(8,0.5,"&nbsp;" . (($id) ?: "new"));
$cf->label(8,2,"Autor");
$cf->inputText(8,2,25,"autor", $autor,255);
$cf->label(8,4,"Autorsystbot");
$cf->textarea(8,4,25,4,"autorsystbot", $autorsystbot);

if (($_SESSION['editControl'] & 0x40) != 0) {
    $cf->buttonSubmit(9,10,"submitUpdate", (($id) ? " Update " : " Insert "));
    $cf->buttonSubmit(21,10,"submitNew", " New ");
}

echo "<input type=\"hidden\" name=\"typ\" value=\"" . $_REQUEST['typ'] . "\">\n";

echo "</form>\n";
?>

</body>
</html>
