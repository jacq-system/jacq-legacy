<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");
require("inc/log_functions.php");

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - edit Author</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="css/screen.css">
</head>

<body>

<?php
if (!empty($_POST['submitUpdate']) && checkRight('author') && (checkRight('unlock_tbl_tax_authors') || !isLocked('tbl_tax_authors', $_POST['ID']))) {
    $sw = true;
    $sql = "SELECT authorID, author, external
            FROM tbl_tax_authors
            WHERE author = " . quoteString($_POST['author']) . "
             AND authorID != '" . intval($_POST['ID']) . "'";
    $result = dbi_query($sql);
    while (($row = mysqli_fetch_array($result)) && $sw) {
        if ($row['author'] == $_POST['author']) {
            echo "<script language=\"JavaScript\">\n";
            echo "alert('Author \"" . $row['author'] . "\" already present with ID " . $row['authorID'] . "');\n";
            echo "</script>\n";
            $id = $_POST['ID'];
            $sw = false;
        }
    }
    if ($sw) {
        $bpf = trim($_POST['Brummit_Powell_full']);
        if (checkRight('unlock_tbl_tax_authors')) {
            $lock = ", locked = " . (($_POST['locked']) ? "'1'" : "'0'");
        } else {
            $lock = "";
        }

        if (intval($_POST['ID'])) {
            $id = intval($_POST['ID']);
            if (strlen(trim($_POST['author'])) > 0) {
                $sql = "UPDATE tbl_tax_authors SET
                         author = " . quoteString($_POST['author']) . ",
                         Brummit_Powell_full = " . quoteString($bpf) . ",
                         external = " . ((!empty($_POST['external'])) ? 1 : 0) . "
                         $lock
                        WHERE authorID = " . intval($_POST['ID']);
                $result = dbi_query($sql);
                logAuthors($id, 1);
            }
        } else {
            $sql = "INSERT INTO tbl_tax_authors SET
                     author = " . quoteString($_POST['author']) . ",
                     Brummit_Powell_full = " . quoteString($bpf) . "
                     $lock";
            $result = dbi_query($sql);
            if ($result) {
                $id = dbi_insert_id();
                logAuthors($id, 0);
            } else {
                $id = 0;
            }
        }

        $ret = $_POST['author'];
        //  if ($bpf) $ret .= " [".preg_replace("/(\r\n|\r|\n)/","\\n",$bpf)."]";
        if ($bpf) $ret .= chr(194) . chr(183) . " [" . replaceNewline($bpf) . "]";

        echo "<script language=\"JavaScript\">\n";
        switch ($_REQUEST['typ']) {
            case 's':  echo "  window.opener.document.f.subauthor.value = \"" . addslashes($ret) . "\";\n";
                       echo "  window.opener.document.f.subauthorIndex.value = $id;\n";
                       break;
            case 'v':  echo "  window.opener.document.f.varauthor.value = \"" . addslashes($ret) . "\";\n";
                       echo "  window.opener.document.f.varauthorIndex.value = $id;\n";
                       break;
            case 'sv': echo "  window.opener.document.f.subvarauthor.value = \"" . addslashes($ret) . "\";\n";
                       echo "  window.opener.document.f.subvarauthorIndex.value = $id;\n";
                       break;
            case 'f':  echo "  window.opener.document.f.forauthor.value = \"" . addslashes($ret) . "\";\n";
                       echo "  window.opener.document.f.forauthorIndex.value = $id;\n";
                       break;
            case 'sf': echo "  window.opener.document.f.subforauthor.value = \"" . addslashes($ret) . "\";\n";
                       echo "  window.opener.document.f.subforauthorIndex.value = $id;\n";
                       break;
            default:   echo "  window.opener.document.f.author.value = \"" . addslashes($ret) . "\";\n";
                       echo "  window.opener.document.f.authorIndex.value = $id;\n";
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

$sql = "SELECT authorID, author, Brummit_Powell_full, locked, external
        FROM tbl_tax_authors
        WHERE authorID = " . intval($id);
$result = dbi_query($sql);
$row = mysqli_fetch_array($result);

$cf = new CSSF();

echo "<input type=\"hidden\" name=\"ID\" value=\"".$row['authorID']."\">\n";
$cf->label(8, 0.5, "ID");
$cf->text(8, 0.5, "&nbsp;" . (($row['authorID']) ? $row['authorID'] : "new"));

if (checkRight('unlock_tbl_tax_authors')) {
    $cf->label(32, 0.5, "locked");
    $cf->checkbox(32, 0.5, "locked", $row['locked']);
} elseif (isLocked('tbl_tax_authors', $row['authorID'])) {
    $cf->label(34, 0.5, "locked");
    echo "<input type=\"hidden\" name=\"locked\" value=\"" . $row['locked'] . "\">\n";
}

$cf->label(8, 2, "Author");
$cf->inputText(8, 2, 25, "author", $row['author'], 255);
$cf->label(8, 4, "Brummit");
$cf->textarea(8, 4, 25, 4, "Brummit_Powell_full", $row['Brummit_Powell_full']);
if ($row['external']) {
    $cf->label(32, 8.5, "external");
    $cf->checkbox(32, 8.5, "external", $row['external']);
}

if (checkRight('author') && (!isLocked('tbl_tax_authors', $row['authorID']) || checkRight('unlock_tbl_tax_authors'))) {
    $text = ($row['authorID']) ? " Update " : " Insert ";
    $cf->buttonSubmit(9, 10, "submitUpdate", $text);
    $cf->buttonJavaScript(21, 10, " New ", "self.location.href='editAuthor.php?sel=0&typ=" . $_REQUEST['typ'] . "'");
}

echo "<input type=\"hidden\" name=\"typ\" value=\"".$_REQUEST['typ']."\">\n";

echo "</form>\n";
?>

</body>
</html>