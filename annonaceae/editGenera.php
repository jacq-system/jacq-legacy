<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");
require("inc/herbardb_input_functions.php");
no_magic();


function makeAuthor($search,$x,$y) {
  global $cf;

  $pieces = explode(" <",$search);
  $results[] = "";
  if ($search && strlen($search)>1) {
    $sql = "SELECT author, authorID, Brummit_Powell_full ".
           "FROM tbl_tax_authors ".
           "WHERE author LIKE '".mysql_escape_string($pieces[0])."%' ".
           "ORDER BY author";
    if ($result = db_query($sql)) {
      $cf->text($x,$y,"<b>".mysql_num_rows($result)." records found</b>");
      if (mysql_num_rows($result)>0) {
        while ($row=mysql_fetch_array($result)) {
          $res = $row['author']." <".$row['authorID'].">";
          if ($row['Brummit_Powell_full']) $res .= " [".replaceNewline($row['Brummit_Powell_full'])."]";
//          if ($row['Brummit_Powell_full']) $res .= " [".strtr($row['Brummit_Powell_full'],"\r\n","  ")."]";
          $results[] = $res;
        }
      }
    }
  }
  return $results;
}


function makeFamily($search,$x,$y) {
  global $cf;

  $pieces = explode(" ",$search);
  $results[] = "";
  if ($search && strlen($search)>1) {
    $sql = "SELECT family, familyID, category ".
           "FROM tbl_tax_families tf ".
            "LEFT JOIN tbl_tax_systematic_categories tsc ON tsc.categoryID=tf.categoryID ".
           "WHERE family LIKE '".mysql_escape_string($pieces[0])."%' ".
           "ORDER BY family";
    if ($result = db_query($sql)) {
      $cf->text($x,$y,"<b>".mysql_num_rows($result)." records found</b>");
      if (mysql_num_rows($result)>0)
        while ($row=mysql_fetch_array($result))
          $results[] = $row['family']." ".$row['category']." <".$row['familyID'].">";
    }
  }
  return $results;
}


function makeTaxon2($search) {
  global $cf;

  $results[] = "";
  if ($search && strlen($search)>1) {
    $pieces = explode(chr(194).chr(183),$search);
    $pieces = explode(" ",$pieces[0]);
    $sql = "SELECT taxonID, tg.genus, ".
            "ta.author, ta1.author author1, ta2.author author2, ta3.author author3, ".
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
           "WHERE tg.genus LIKE '".mysql_escape_string($pieces[0])."%' ";
    if ($pieces[1])
      $sql .= "AND te.epithet LIKE '".mysql_escape_string($pieces[1])."%' ";
    $sql .= "ORDER BY tg.genus, te.epithet, epithet1, epithet2, epithet3";
    if ($result = db_query($sql)) {
      if (mysql_num_rows($result)>0)
        while ($row=mysql_fetch_array($result))
          $results[] = taxon($row);
    }
  }
  return $results;
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - edit Genera</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="herbardb_input.css">
  <script type="text/javascript" language="JavaScript">
    function editSpecies(sel) {
      target = "editSpecies.php?sel=<" + sel + ">";
      options = "width=";
      if (screen.availWidth<990)
        options += (screen.availWidth - 70) + ",height=";
      else
        options += "990, height=";
      if (screen.availHeight<740)
        options += (screen.availHeight - 70);
      else
        options += "740";
      options += ", top=70,left=70,scrollbars=yes,resizable=yes";
      MeinFenster = window.open(target,"Species",options);
      MeinFenster.focus();
    }
  </script>
</head>

<body>

<?php
$blocked = false;
if ($_POST['submitUpdate'] && $_SESSION['acc']) {
  if (intval($_POST['genID'])) {
    // check if user has update rights for the old familyID
    $sql = "SELECT ac.update
            FROM herbarinput_log.tbl_herbardb_access ac, tbl_tax_genera tg
             INNER JOIN tbl_tax_families tf USING (familyID)
            WHERE tg.genID='".intval($_POST['genID'])."'
             AND (ac.familyID=tf.familyID
               OR ac.categoryID=tf.categoryID)
             AND ac.userID='".$_SESSION['uid']."'";
    $result = db_query($sql);
    if (mysql_num_rows($result)>0) {
      $row = mysql_fetch_array($result);
      if (!$row['update']) $blocked = true;  // no update access
    }
    else
      $blocked = true;                       // no access at all
  }

  // check if user has access to the new familyID
  $sql = "SELECT ac.update
          FROM herbarinput_log.tbl_herbardb_access ac, tbl_tax_families tf
          WHERE tf.familyID=".extractID($_POST['family'])."
           AND (ac.familyID=tf.familyID
             OR ac.categoryID=tf.categoryID)
           AND ac.userID='".$_SESSION['uid']."'";
  $result = db_query($sql);
  if (mysql_num_rows($result)==0) $blocked = true; // no access
}

if ($_POST['submitUpdate'] && (($_SESSION['editControl'] & 0x8)!=0) && !$blocked) {
  $dtid = $_POST['DTID'];
  $dtzid = $_POST['DTZID'];
  $remarks = $_POST['remarks'];
  if (intval($_POST['genID'])) {
    $sql = "UPDATE tbl_tax_genera SET ".
            "genus='".mysql_escape_string($_POST['genus'])."', ".
            "authorID=".extractID($_POST['author']).", ".
            "DallaTorreIDs=".quoteString($dtid).", ".
            "DallaTorreZusatzIDs=".quoteString($dtzid).", ".
            "hybrid=".(($_POST['hybrid']) ? "'X'" : "NULL").", ".
            "accepted=".(($_POST['accepted']) ? "'1'" : "'0'").", ".
            "familyID=".extractID($_POST['family']).", ".
            "fk_taxonID=".extractID($_POST['taxon']).", ".
            "remarks=".quoteString($remarks)." ".
           "WHERE genID=".intval($_POST['genID']);
    $updated = 1;
  } else {
    $sql = "INSERT INTO tbl_tax_genera (genus, authorID, DallaTorreIDs, DallaTorreZusatzIDs, ".
            "hybrid, accepted, familyID, fk_taxonID, remarks) ".
           "VALUES ('".mysql_escape_string($_POST['genus'])."', ".
            extractID($_POST['author']).", ".
            quoteString($dtid).", ".
            quoteString($dtzid).", ".
            (($_POST['hybrid']) ? "'X'" : "NULL").", ".
            (($_POST['accepted']) ? "'1'" : "'0'").", ".
            extractID($_POST['family']).", ".
            extractID($_POST['taxon']).", ".
            quoteString($remarks).")";
    $updated = 0;
  }
  $result = db_query($sql);
  $id = ($_POST['genID']) ? intval($_POST['genID']) : mysql_insert_id();
  logGenera($id,$updated);

  $sql = "SELECT tg.genus, tg.DallaTorreIDs, tg.DallaTorreZusatzIDs, ".
          "ta.author, tf.family, tsc.category ".
         "FROM tbl_tax_genera tg ".
          "LEFT JOIN tbl_tax_authors ta ON ta.authorID=tg.authorID ".
          "LEFT JOIN tbl_tax_families tf ON tf.familyID=tg.familyID ".
          "LEFT JOIN tbl_tax_systematic_categories tsc ON tsc.categoryID=tf.categoryID ".
         "WHERE genID='$id'";
  $result = db_query($sql);
  $row = mysql_fetch_array($result);
  $res = $row['genus']." ".$row['author']." ".$row['family']." ".$row['category']." ".
         $row['DallaTorreIDs'].$row['DallaTorreZusatzIDs']." <$id>";

  if ($result) {
    echo "<script language=\"JavaScript\">\n";
    echo "  window.opener.document.f.gen.value = \"".addslashes($res)."\";\n";
    echo "  window.opener.document.f.reload.click()\n";
    echo "  self.close()\n";
    echo "</script>\n";
  }
}
elseif ($_POST['submitUpdate'] && (($_SESSION['editControl'] & 0x4000)!=0) && !$blocked) {
  if (intval($_POST['genID'])) {
    $sql = "UPDATE tbl_tax_genera SET ".
            "DallaTorreIDs=".quoteString($_POST['DTID']).", ".
            "DallaTorreZusatzIDs=".quoteString($_POST['DTZID']).", ".
            "accepted=".(($_POST['accepted']) ? "'1'" : "'0'")." ".
           "WHERE genID=".intval($_POST['genID']);
    $result = db_query($sql);
    $id = intval($_POST['genID']);
    logGenera($id,1);
  }

  $sql = "SELECT tg.genus, tg.DallaTorreIDs, tg.DallaTorreZusatzIDs, ".
          "ta.author, tf.family, tsc.category ".
         "FROM tbl_tax_genera tg ".
          "LEFT JOIN tbl_tax_authors ta ON ta.authorID=tg.authorID ".
          "LEFT JOIN tbl_tax_families tf ON tf.familyID=tg.familyID ".
          "LEFT JOIN tbl_tax_systematic_categories tsc ON tsc.categoryID=tf.categoryID ".
         "WHERE genID='$id'";
  $result = db_query($sql);
  $row = mysql_fetch_array($result);
  $res = $row['genus']." ".$row['author']." ".$row['family']." ".$row['category']." ".
         $row['DallaTorreIDs'].$row['DallaTorreZusatzIDs']." <$id>";

  if ($result) {
    echo "<script language=\"JavaScript\">\n";
    echo "  window.opener.document.f.gen.value = \"".addslashes($res)."\";\n";
    echo "  window.opener.document.f.reload.click()\n";
    echo "  self.close()\n";
    echo "</script>\n";
  }
}
elseif (extractID($_GET['sel'])!=="NULL") {
  $sql = "SELECT tg.genID, tg.genus, tg.DallaTorreIDs, tg.DallaTorreZusatzIDs, ".
          "tg.hybrid, tg.accepted, tg.fk_taxonID, tg.remarks, ".
          "ta.author, ta.authorID, ta.Brummit_Powell_full, ".
          "tf.family, tf.familyID, tsc.category ".
         "FROM tbl_tax_genera tg ".
          "LEFT JOIN tbl_tax_authors ta ON ta.authorID=tg.authorID ".
          "LEFT JOIN tbl_tax_families tf ON tf.familyID=tg.familyID ".
          "LEFT JOIN tbl_tax_systematic_categories tsc ON tsc.categoryID=tf.categoryID ".
         "WHERE genID=".extractID($_GET['sel']);
  $result = db_query($sql);
  if (mysql_num_rows($result)>0) {
    $row = mysql_fetch_array($result);
    $p_genus    = $row['genus'];
    $p_genID    = $row['genID'];
    $p_DTID     = $row['DallaTorreIDs'];
    $p_DTZID    = $row['DallaTorreZusatzIDs'];
    $p_hybrid   = $row['hybrid'];
    $p_accepted = $row['accepted'];
    $p_remarks  = $row['remarks'];
    $p_author   = ($row['author']) ? $row['author']." <".$row['authorID'].">" : "";
    if ($row['Brummit_Powell_full']) $p_author .= " [".strtr($row['Brummit_Powell_full'],"\r\n\xa0","   ")."]";
    $p_family   = $row['family']." ".$row['category']." <".$row['familyID'].">";
    if ($row['fk_taxonID']) {
      $sql = "SELECT ts.taxonID, tg.genus, ".
              "ta.author, ta1.author author1, ta2.author author2, ta3.author author3, ".
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
             "WHERE ts.taxonID='".mysql_escape_string($row['fk_taxonID'])."'";
      $result2 = db_query($sql);
      $row2 = mysql_fetch_array($result2);
      $p_taxon  = taxon($row2);
    } else
      $p_taxon = "";
  }
  else {
    $p_genus = $p_DTID = $p_DTZID = $p_hybrid = $p_accepted = $p_taxon = $p_remarks = $p_author = "";
    $p_family = $p_genID = "";
  }
}
else {
  $p_genus    = $_POST['genus'];
  $p_DTID     = $_POST['DTID'];
  $p_DTZID    = $_POST['DTZID'];
  $p_hybrid   = $_POST['hybrid'];
  $p_accepted = $_POST['accepted'];
  $p_taxon    = $_POST['taxon'];
  $p_remarks  = $_POST['remarks'];
  $p_author   = $_POST['author'];
  $p_family   = $_POST['family'];
  $p_genID    = $_POST['genID'];
}
?>

<?php if ($blocked): ?>
<script type="text/javascript" language="JavaScript">
  alert('You have no sufficient rights for the desired operation');
</script>
<?php endif; ?>

<form Action="<?php echo $_SERVER['PHP_SELF'];?>" Method="POST" name="f">

<?php
$cf = new CSSF();

echo "<input type=\"hidden\" name=\"genID\" value=\"$p_genID\">\n";
$cf->label(8,0.5,"ID");
$cf->text(8,0.5,"&nbsp;".(($p_genID)?$p_genID:"new"));
if ($p_genID) {
  $sql = "SELECT taxonID ".
         "FROM tbl_tax_species ".
         "WHERE speciesID IS NULL".
         " AND subspeciesID IS NULL AND subspecies_authorID IS NULL".
         " AND varietyID IS NULL AND variety_authorID IS NULL".
         " AND subvarietyID IS NULL AND subvariety_authorID IS NULL".
         " AND formaID IS NULL AND forma_authorID IS NULL".
         " AND subformaID IS NULL AND subforma_authorID IS NULL".
         " AND genID='".intval($p_genID)."'";
  $result = db_query($sql);
  $row = mysql_fetch_array($result);
  $cf->label(8,2,"edit Species","javascript:editSpecies('".$row['taxonID']."')");
}
$cf->label(8,4,"Genus");
$cf->inputText(8,4,25,"genus",$p_genus,100);
$cf->label(8,7.5,"Author");
#$cf->label(8,7.5,"Author"),"javascript:editAuthor(document.f.author,'a')");
$cf->inputText(8,7.5,25,"author",$p_author,makeAuthor($p_author,8,6),520);
#$cf->editDropdown(8,7.5,25,"author",$p_author,makeAuthor($p_author,8,6),520);
#$cf->label(8,9.3,"search","javascript:searchAuthor()");
$cf->label(8,11.5,"Ref No.");
$cf->inputText(8,11.5,7,"DTID",$p_DTID,11);
$cf->label(22,11.5,"Addition");
$cf->inputText(22,11.5,1,"DTZID",$p_DTZID,1);
$cf->label(32,11.5,"Hybrid");
$cf->checkbox(32,11.5,"hybrid",$p_hybrid);
$cf->label(32,13.5,"Accepted");
$cf->checkbox(32,13.5,"accepted",$p_accepted);
$cf->label(8,16,"Family");
#$cf->label(8,16,"Family","javascript:editFamily(document.f.family)");
$cf->inputText(8,16,25,"family",$p_family,makeFamily($p_family,8,14.5),100);
#$cf->editDropdown(8,16,25,"family",$p_family,makeFamily($p_family,8,14.5),100);
$cf->label(8,21,"type");
$cf->inputText(8,21,25,"taxon",$p_taxon,makeTaxon2($p_taxon),520);
#$cf->editDropdown(8,21,25,"taxon",$p_taxon,makeTaxon2($p_taxon),520);
$cf->label(8,26,"Remarks");
$cf->textarea(8,26,25,4,"remarks",$p_remarks);

?>
</form>

</body>
</html>