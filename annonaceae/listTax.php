<?php
session_start();
require("inc/connect.php");
require("inc/herbardb_input_functions.php");
no_magic();

$nrSel = intval($_GET['nr']);

if (isset($_POST['search'])) {
  if ($_POST['collector'] || $_POST['number'] || $_POST['date']) {
    $_SESSION['taxType']      = 4;
    $_SESSION['taxGenus']     = $_POST['genus'];
    $_SESSION['taxSpecies']   = $_POST['species'];
    $_SESSION['taxCollector'] = $_POST['collector'];
    $_SESSION['taxNumber']    = $_POST['number'];
    $_SESSION['taxDate']      = $_POST['date'];
    $_SESSION['taxAuthor']    = $_POST['author'];
    $_SESSION['taxOrder']     = "Sammler, Sammler_2, series, leg_nr, tt.date";
    $_SESSION['taxOrTyp']     = 41;
  }
  else if ($_POST['species']) {
    $_SESSION['taxType']      = 3;
    $_SESSION['taxGenus']     = $_POST['genus'];
    $_SESSION['taxSpecies']   = $_POST['species'];
    $_SESSION['taxCollector'] = "";
    $_SESSION['taxNumber']    = "";
    $_SESSION['taxDate']      = "";
    $_SESSION['taxAuthor']    = $_POST['author'];
    $_SESSION['taxOrder']     = "genus, auth_g, epithet, author, epithet1, author1, ".
                                "epithet2, author2, epithet3, author3, epithet4, author4, epithet5, author5";
    $_SESSION['taxOrTyp']     = 31;
  }
  else if ($_POST['genus']) {
    $_SESSION['taxType']      = 2;
    $_SESSION['taxGenus']     = $_POST['genus'];
    $_SESSION['taxSpecies']   = "";
    $_SESSION['taxCollector'] = "";
    $_SESSION['taxNumber']    = "";
    $_SESSION['taxDate']      = "";
    $_SESSION['taxAuthor']    = $_POST['author'];
    $_SESSION['taxOrder']     = "genus, auth_g";
    $_SESSION['taxOrTyp']     = 21;
  }
}
else if (isset($_GET['lgenus'])) {
  $_SESSION['taxType']      = 2;
  $_SESSION['taxGenus']     = $_GET['lgenus'];
  $_SESSION['taxSpecies']   = "";
  $_SESSION['taxCollector'] = "";
  $_SESSION['taxNumber']    = "";
  $_SESSION['taxDate']      = "";
  $_SESSION['taxAuthor']    = "";
  $_SESSION['taxOrder']     = "genus, auth_g";
  $_SESSION['taxOrTyp']     = 21;
}
else if (isset($_GET['order'])) {
  if ($_SESSION['taxType']==4) {
    if ($_GET['order']=="db") {
      $_SESSION['taxOrder'] = "epithet, genus, author, epithet1, author1, ".
                              "epithet2, author2, epithet3, author3, epithet4, author4, epithet5, author5";
      if ($_SESSION['taxOrTyp']==42)
        $_SESSION['taxOrTyp'] = -42;
      else
        $_SESSION['taxOrTyp'] = 42;
    }
    else {
      $_SESSION['taxOrder'] = "Sammler, Sammler_2, series, leg_nr, tt.date";
      if ($_SESSION['taxOrTyp']==41)
        $_SESSION['taxOrTyp'] = -41;
      else
        $_SESSION['taxOrTyp'] = 41;
    }
  }
  else if ($_SESSION['taxType']==3) {
    if ($_GET['order']=="cs") {
      $_SESSION['taxOrder'] = "epithet, genus, auth_g, author, epithet1, author1, ".
                              "epithet2, author2, epithet3, author3, epithet4, author4, epithet5, author5";
      if ($_SESSION['taxOrTyp']==33)
        $_SESSION['taxOrTyp'] = -33;
      else
        $_SESSION['taxOrTyp'] = 33;
    }
    else if ($_GET['order']=="cf") {
      $_SESSION['taxOrder'] = "genus, auth_g, epithet, author, epithet1, author1, ".
                              "epithet2, author2, epithet3, author3, epithet4, author4, epithet5, author5";
      if ($_SESSION['taxOrTyp']==32)
        $_SESSION['taxOrTyp'] = -32;
      else
        $_SESSION['taxOrTyp'] = 32;
    }
    else {
      $_SESSION['taxOrder'] = "genus, auth_g, epithet, author, epithet1, author1, ".
                              "epithet2, author2, epithet3, author3, epithet4, author4, epithet5, author5";
      if ($_SESSION['taxOrTyp']==31)
        $_SESSION['taxOrTyp'] = -31;
      else
        $_SESSION['taxOrTyp'] = 31;
    }
  }
  else if ($_SESSION['taxType']==2) {
    if ($_GET['order']=="bf") {
      $_SESSION['taxOrder'] = "genus, auth_g";
      if ($_SESSION['taxOrTyp']==22)
        $_SESSION['taxOrTyp'] = -22;
      else
        $_SESSION['taxOrTyp'] = 22;
    }
    else {
      $_SESSION['taxOrder'] = "genus, auth_g";
      if ($_SESSION['taxOrTyp']==21)
        $_SESSION['taxOrTyp'] = -21;
      else
        $_SESSION['taxOrTyp'] = 21;
    }
  }
  else {
    if ($_GET['order']=="af") {
      $_SESSION['taxOrder'] = "category";
      if ($_SESSION['taxOrTyp']==12)
        $_SESSION['taxOrTyp'] = -12;
      else
        $_SESSION['taxOrTyp'] = 12;
    }
    else {
      $_SESSION['taxOrder'] = "category";
      if ($_SESSION['taxOrTyp']==11)
        $_SESSION['taxOrTyp'] = -11;
      else
        $_SESSION['taxOrTyp'] = 11;
    }
  }
  if ($_SESSION['taxOrTyp']<0) $_SESSION['taxOrder'] = implode(" DESC, ",explode(", ",$_SESSION['taxOrder']))." DESC";
}

if ($_POST['select'] && $_POST['taxon']) {
  $location="Location: editSpecies.php?sel=<".$_POST['taxon'].">";
  if (SID!="") $location = $location."?".SID;
  Header($location);
}


function typusItem($row) {

  $text = $row['Sammler'];
  if ($row['Sammler_2']) {
    if (strstr($row['Sammler_2'],"&")===false)
      $text .= " & ".$row['Sammler_2'];
    else
      $text .= " et al.";
  }
  if ($row['series']) $text .= " ".$row['series'];
  if ($row['leg_nr']) $text .= " ".$row['leg_nr'];
  if ($row['alternate_number']) {
    $text .= " ".$row['alternate_number'];
    if (strstr($row['alternate_number'],"s.n.")!==false)
      $text .= " [".$row['date']."]";
  }
  $text .= "; ".$row['duplicates'];

  return $text;
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>Annonaceae - taxonomic Index</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="herbardb_input.css">
  <style type="text/css">
    #close { position:absolute; top:1em; right:1em; width:12em; }
  </style>
  <script type="text/javascript" language="JavaScript">
    function editGenera(sel) {
      target = "editGenera.php?sel=" + encodeURIComponent(sel);
      MeinFenster = window.open(target,"editGenera","width=600,height=440,top=50,left=50,scrollbars=yes,resizable=yes");
      MeinFenster.focus();
    }
  </script>
</head>

<body>

<form Action="<?=$_SERVER['PHP_SELF'];?>" Method="POST" name="f">
  <input type="submit" name="reload" value="" id="close">
  <input type="hidden" name="gen" value="">
  <input class="button" type="button" value="close window" onclick="self.close()" id="close">
</form>

<form Action="<?=$_SERVER['PHP_SELF'];?>" Method="POST">
<table cellspacing="5" cellpadding="0">
<tr>
  <td align="right">&nbsp;<b>Genus:</b></td>
    <td><input type="text" name="genus" value="<?=$_SESSION['taxGenus']?>"></td>
  <td align="right">&nbsp;<b>Species:</b></td>
    <td><input type="text" name="species" value="<?=$_SESSION['taxSpecies']?>"></td>
  <td align="right">&nbsp;<b>Author:</b></td>
    <td><input type="text" name="author" value="<?=$_SESSION['taxAuthor']?>"</td>
</tr><tr>
  <td align="right">&nbsp;<b>Typecollection:</b></td>
    <td><input type="text" name="collector" value="<?=$_SESSION['taxCollector']?>"></td>
  <td align="right">&nbsp;<b>Number:</b></td>
    <td><input type="text" name="number" value="<?=$_SESSION['taxNumber']?>"></td>
  <td align="right">&nbsp;<b>Date:</b></td>
    <td><input type="text" name="date" value="<?=$_SESSION['taxDate']?>"></td>
</tr>
</table>
<input class="button" type="submit" name="search" value=" search ">
</form>

<p>

<?php

echo "<form Action=\"".$_SERVER['PHP_SELF']."\" Method=\"POST\">\n";
echo "<b>Genus:</b> ";
for ($i=0,$a='A';$i<26;$i++,$a++)
  echo "<input class=\"button\" type=\"button\" value=\"$a\" style=\"width: 1.6em\" ".
       "onClick=\"self.location.href='".$_SERVER['PHP_SELF']."?lgenus=$a'\"\n>";
echo "</form>\n<p>\n";

if ($_SESSION['taxType']==2) {
  $sql = "SELECT tg.genID, tg.genus, tag.author auth_g, tf.family, ".
          "tsc.cat_description category, tg.DallaTorreIDs, tg.DallaTorreZusatzIDs ".
         "FROM tbl_tax_genera tg ".
          "LEFT JOIN tbl_tax_authors tag ON tag.authorID=tg.authorID ".
          "LEFT JOIN tbl_tax_families tf ON tf.familyID=tg.familyID ".
          "LEFT JOIN tbl_tax_systematic_categories tsc ON tf.categoryID=tsc.categoryID ".
         "WHERE genus LIKE '".mysql_escape_string($_SESSION['taxGenus'])."%' ".
          "AND family LIKE 'Annonaceae' ";
  if ($_SESSION['taxAuthor'])
    $sql .= "AND tag.author LIKE '%".mysql_escape_string($_SESSION['taxAuthor'])."%' ";
  $sql .= "ORDER BY ".$_SESSION['taxOrder'];
  $result = db_query($sql);
  if (mysql_num_rows($result)>0) {
    echo "<table class=\"out\" cellspacing=\"0\">\n";
    echo "<tr class=\"out\">";
    echo "<th class=\"out\">".
         "<a href=\"".$_SERVER['PHP_SELF']."?order=bg\">Genus</a>".sortItem($_SESSION['taxOrTyp'],21)."</th>";
    echo "<th class=\"out\">Author</th>";
    echo "<th class=\"out\">RefNo</th>";
    echo "<th class=\"out\">".
         "<a href=\"".$_SERVER['PHP_SELF']."?order=bf\">Family</a>".sortItem($_SESSION['taxOrTyp'],22)."</th>";
    echo "<th class=\"out\">Category</th></tr>\n";
    while ($row=mysql_fetch_array($result)) {
      echo "<tr class=\"out\"><td class=\"out\">";
      echo "<a href=\"javascript:editGenera('<".$row['genID'].">')\">";
      echo $row['genus'];
      echo "</a></td><td class=\"out\">";
      echo "<a href=\"javascript:editGenera('<".$row['genID'].">')\">";
      echo $row['auth_g'];
      echo "</a></td><td class=\"out\">";
      echo $row['DallaTorreIDs'].$row['DallaTorreZusatzIDs'];
      echo "</td><td class=\"out\">";
      echo "<a href=\"javascript:editGenera('<".$row['genID'].">')\">";
      echo $row['family'];
      echo "</a></td><td class=\"out\">";
      echo "<a href=\"javascript:editGenera('<".$row['genID'].">')\">";
      echo $row['category'];
      echo "</a></td></tr>\n";
    }
    echo "</table>\n";
  }
  else
    echo "<b>nothing found!</b>\n";
}
else if ($_SESSION['taxType']==3) {
  $sql = "SELECT ts.taxonID, ts.statusID, tg.genus, tag.author auth_g, tf.family, ".
          "ta.author author, ta1.author author1, ta2.author author2, ta3.author author3, ".
          "ta4.author author4, ta5.author author5, ".
          "te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3, ".
          "te4.epithet epithet4, te5.epithet epithet5 ".
         "FROM tbl_tax_species ts ".
          "LEFT JOIN tbl_tax_authors ta ON ta.authorID=ts.authorID ".
          "LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID=ts.subspecies_authorID ".
          "LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID=ts.variety_authorID ".
          "LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID=ts.subvariety_authorID ".
          "LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID=ts.forma_authorID ".
          "LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID=ts.subforma_authorID ".
          "LEFT JOIN tbl_tax_epithets te ON te.epithetID=ts.speciesID ".
          "LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID=ts.subspeciesID ".
          "LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID=ts.varietyID ".
          "LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID=ts.subvarietyID ".
          "LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID=ts.formaID ".
          "LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID=ts.subformaID ".
          "LEFT JOIN tbl_tax_genera tg ON tg.genID=ts.genID ".
          "LEFT JOIN tbl_tax_authors tag ON tag.authorID=tg.authorID ".
          "LEFT JOIN tbl_tax_families tf ON tf.familyID=tg.familyID ".
         "WHERE te.epithet LIKE '".mysql_escape_string($_SESSION['taxSpecies'])."%' ".
          "AND family LIKE 'Annonaceae' ";
  if ($_SESSION['taxGenus'])
    $sql .= "AND genus LIKE '".mysql_escape_string($_SESSION['taxGenus'])."%' ";
  if ($_SESSION['taxAuthor'])
    $sql .= "AND (ta.author LIKE '%".mysql_escape_string($_SESSION['taxAuthor'])."%' ".
              "OR ta1.author LIKE '%".mysql_escape_string($_SESSION['taxAuthor'])."%' ".
              "OR ta2.author LIKE '%".mysql_escape_string($_SESSION['taxAuthor'])."%' ".
              "OR ta3.author LIKE '%".mysql_escape_string($_SESSION['taxAuthor'])."%' ".
              "OR ta4.author LIKE '%".mysql_escape_string($_SESSION['taxAuthor'])."%' ".
              "OR ta5.author LIKE '%".mysql_escape_string($_SESSION['taxAuthor'])."%') ";
  $sql .= "ORDER BY ".$_SESSION['taxOrder'];
  $result = db_query($sql);
  if (mysql_num_rows($result)>0) {
    echo "<table class=\"out\" cellspacing=\"0\">\n";
    echo "<tr class=\"out\">";
    echo "<th class=\"out\">".
         "<a href=\"".$_SERVER['PHP_SELF']."?order=cf\">Family</a>".sortItem($_SESSION['taxOrTyp'],32)."</th>";
    echo "<th class=\"out\">acc.</th>";
    echo "<th class=\"out\">".
         "<a href=\"".$_SERVER['PHP_SELF']."?order=cg\">Genus</a>".sortItem($_SESSION['taxOrTyp'],31)."</th>";
    echo "<th class=\"out\">Author</th>";
    echo "<th class=\"out\">".
         "<a href=\"".$_SERVER['PHP_SELF']."?order=cs\">Species</a>".sortItem($_SESSION['taxOrTyp'],33)."</th>";
    echo "<th class=\"out\">Author</th>";
    echo "<th class=\"out\">infraspecific Taxon</th>";
    echo "</tr>\n";
    $nr = 1;
    while ($row=mysql_fetch_array($result)) {
      $linkList[$nr] = $row['taxonID'];
      echo "<tr class=\"".(($nrSel==$nr)?"outMark":"out")."\"><td class=\"out\">";
      echo "<a href=\"editSpecies.php?sel=".htmlspecialchars("<".$row['taxonID'].">")."&nr=$nr\">";
      echo $row['family'];
      echo "</a></td><td style=\"text-align: center;\" class=\"out\">";
      echo (($row['statusID']==96) ? "&bull;" : "");
      echo "</td><td class=\"out\">";
      echo "<a href=\"editSpecies.php?sel=".htmlspecialchars("<".$row['taxonID'].">")."&nr=$nr\">";
      echo $row['genus'];
      echo "</a></td><td class=\"out\">";
      echo "<a href=\"editSpecies.php?sel=".htmlspecialchars("<".$row['taxonID'].">")."&nr=$nr\">";
      echo $row['auth_g'];
      echo "</a></td><td class=\"out\">";
      echo "<a href=\"editSpecies.php?sel=".htmlspecialchars("<".$row['taxonID'].">")."&nr=$nr\">";
      echo $row['epithet'];
      echo "</a></td><td class=\"out\">";
      echo "<a href=\"editSpecies.php?sel=".htmlspecialchars("<".$row['taxonID'].">")."&nr=$nr\">";
      echo $row['author'];
      echo "</a></td><td class=\"out\">";
      echo "<a href=\"editSpecies.php?sel=".htmlspecialchars("<".$row['taxonID'].">")."&nr=$nr\">";
      echo subTaxonItem($row);
      echo "</a></td></tr>\n";
      $nr++;
    }
    $linkList[0] = $nr - 1;
    $_SESSION['txLinkList'] = $linkList;
    echo "</table>\n";
  }
  else
    echo "<b>nothing found!</b>\n";
}
else if ($_SESSION['taxType']==4) {
  $sql = "SELECT ts.taxonID, tg.genus, ".
          "ta.author author, ta1.author author1, ta2.author author2, ta3.author author3, ".
          "ta4.author author4, ta5.author author5, ".
          "te.epithet epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3, ".
          "te4.epithet epithet4, te5.epithet epithet5, ".
          "Sammler, Sammler_2, series, leg_nr, alternate_number, date, duplicates ".
         "FROM tbl_tax_species ts ".
          "LEFT JOIN tbl_tax_authors ta ON ta.authorID=ts.authorID ".
          "LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID=ts.subspecies_authorID ".
          "LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID=ts.variety_authorID ".
          "LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID=ts.subvariety_authorID ".
          "LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID=ts.forma_authorID ".
          "LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID=ts.subforma_authorID ".
          "LEFT JOIN tbl_tax_epithets te ON te.epithetID=ts.speciesID ".
          "LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID=ts.subspeciesID ".
          "LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID=ts.varietyID ".
          "LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID=ts.subvarietyID ".
          "LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID=ts.formaID ".
          "LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID=ts.subformaID ".
          "LEFT JOIN tbl_tax_genera tg ON tg.genID=ts.genID ".
           "LEFT JOIN tbl_tax_families tf ON tf.familyID=tg.familyID ".
          "LEFT JOIN tbl_tax_typecollections tt ON tt.taxonID=ts.taxonID ".
           "LEFT JOIN tbl_collector tc ON tc.SammlerID=tt.SammlerID ".
           "LEFT JOIN tbl_collector_2 tc2 ON tc2.Sammler_2ID=tt.Sammler_2ID ";
  $preSQL = "WHERE ";
  if ($_SESSION['taxDate']) {
    $sql .= $preSQL."tt.date LIKE '".mysql_escape_string($_SESSION['taxDate'])."%' ";
    $preSQL = "AND ";
  }
  if ($_SESSION['taxNumber']) {
    $sql .= $preSQL."tt.leg_nr='".mysql_escape_string($_SESSION['taxNumber'])."' ";
    $preSQL = "AND ";
  }
  if ($_SESSION['taxCollector'])
    $sql .= $preSQL."(tc.Sammler LIKE '".mysql_escape_string($_SESSION['taxCollector'])."%' ".
                  "OR tc2.Sammler_2 LIKE '".mysql_escape_string($_SESSION['taxCollector'])."%') ";
  if ($_SESSION['taxSpecies'])
    $sql .= "AND te.epithet LIKE '".mysql_escape_string($_SESSION['taxSpecies'])."%' ";
  $sql .= "AND family LIKE 'Annonaceae' ";
  if ($_SESSION['taxGenus'])
    $sql .= "AND genus LIKE '".mysql_escape_string($_SESSION['taxGenus'])."%' ";
  $sql .= "ORDER BY ".$_SESSION['taxOrder'];
  $result = db_query($sql);
  if (mysql_num_rows($result)>0) {
    echo "<table class=\"out\" cellspacing=\"0\">\n";
    echo "<tr class=\"out\">";
    echo "<th class=\"out\">".
         "<a href=\"".$_SERVER['PHP_SELF']."?order=da\">Type</a>".sortItem($_SESSION['taxOrTyp'],41)."</th>";
    echo "<th class=\"out\">".
         "<a href=\"".$_SERVER['PHP_SELF']."?order=db\">Taxon</a>".sortItem($_SESSION['taxOrTyp'],42)."</th>";
    echo "</tr>\n";
    $nr = 1;
    while ($row=mysql_fetch_array($result)) {
      echo "<tr class=\"".(($nrSel==$nr)?"outMark":"out")."\"><td class=\"out\">";
      echo "<a href=\"editSpecies.php?sel=".htmlspecialchars("<".$row['taxonID'].">")."&nr=$nr\">";
      echo htmlspecialchars(typusItem($row));
      echo "</a></td><td class=\"out\">";
      echo "<a href=\"editSpecies.php?sel=".htmlspecialchars("<".$row['taxonID'].">")."&nr=$nr\">";
      echo htmlspecialchars(taxonItem($row));
      echo "</a></td></tr>\n";
      $nr++;
    }
    echo "</table>\n";
  }
  else
    echo "<b>nothing found!</b>\n";
}
?>

</body>
</html>