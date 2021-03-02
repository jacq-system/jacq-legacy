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
if ($_POST['submitUpdate'] && (($_SESSION['editControl'] & 0x40)!=0)) {
  $sw = true;
  $sql = "SELECT autorID, autor ".
         "FROM tbl_lit_authors ".
         "WHERE autor=".quoteString($_POST['autor'])." ".
          "AND autorID!='".intval($_POST['ID'])."'";
  $result = dbi_query($sql);
  while (($row=mysqli_fetch_array($result)) && $sw) {
    if ($row['autor']==$_POST['autor']) {
      echo "<script language=\"JavaScript\">\n";
      echo "alert('Author \"".$row['autor']."\" already present with ID ".$row['autorID']."');\n";
      echo "</script>\n";
      $id = $_POST['ID'];
      $sw = false;
    }
  }
  if ($sw) {
    $autor = $_POST['autor'];
    $autorsystbot = $_POST['autorsystbot'];
    if (intval($_POST['ID'])) {
      $sql = "UPDATE tbl_lit_authors SET ".
              "autor=".quoteString($autor).", ".
              "autorsystbot=".quoteString($autorsystbot)." ".
             "WHERE autorID=".intval($_POST['ID']);
      $updated = 1;
    } else{
      $sql = "INSERT INTO tbl_lit_authors (autor, autorsystbot) ".
             "VALUES (".quoteString($autor).", ".
             quoteString($autorsystbot).")";
      $updated = 0;
    }
    $result = dbi_query($sql);
    $id = ($_POST['ID']) ? intval($_POST['ID']) : dbi_insert_id();
    logLitAuthors($id,$updated);

    echo "<script language=\"JavaScript\">\n";
    if ($_REQUEST['typ']=="a")
      echo "  window.opener.document.f.autor.value = \"".addslashes($autor)." <$id>\";\n";
    else
      echo "  window.opener.document.f.editor.value = \"".addslashes($autor)." <$id>\";\n";
    echo "  window.opener.document.f.reload.click()\n";
    echo "  self.close()\n";
    echo "</script>\n";
    echo "</body>\n</html>\n";
    die();
  }
}
else {
  $pieces = explode("<",$_GET['sel']);
  $pieces = explode(">",$pieces[1]);
  $id = $pieces[0];
}

echo "<form name=\"f\" Action=\"".$_SERVER['PHP_SELF']."\" Method=\"POST\">\n";

$sql = "SELECT autorID, autor, autorsystbot ".
       "FROM tbl_lit_authors WHERE autorID='".dbi_escape_string($id)."'";
$result = dbi_query($sql);
$row = mysqli_fetch_array($result);

$cf = new CSSF();

echo "<input type=\"hidden\" name=\"ID\" value=\"".$row['autorID']."\">\n";
$cf->label(8,0.5,"ID");
$cf->text(8,0.5,"&nbsp;".(($row['autorID'])?$row['autorID']:"new"));
$cf->label(8,2,"Autor");
$cf->inputText(8,2,25,"autor",$row['autor'],255);
$cf->label(8,4,"Autorsystbot");
$cf->textarea(8,4,25,4,"autorsystbot",$row['autorsystbot']);

if (($_SESSION['editControl'] & 0x40)!=0) {
  $text = ($row['autorID']) ? " Update " : " Insert ";
  $cf->buttonSubmit(9,10,"submitUpdate",$text);
  $cf->buttonJavaScript(21,10," New ","self.location.href='editLitAuthor.php?sel= '");
}

echo "<input type=\"hidden\" name=\"typ\" value=\"".$_REQUEST['typ']."\">\n";

echo "</form>\n";
?>

</body>
</html>