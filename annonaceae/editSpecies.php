<?php
session_start();
require("inc/connect.php");
require("inc/cssf.php");
require("inc/herbardb_input_functions.php");
no_magic();

$nr = intval($_GET['nr']);
$linkList = $_SESSION['txLinkList'];

function makeEpithet($search,$x,$y,$top) {
  global $cf;

  $pieces = explode(" <",$search);
  $results[] = "";
  if ($search && strlen($search)>1) {
    $sql = "SELECT epithet, epithetID ".
           "FROM tbl_tax_epithets ".
           "WHERE epithet LIKE '".mysql_escape_string($pieces[0])."%' ".
           "ORDER BY epithet";
    if ($result = db_query($sql)) {
      if ($top)
        $cf->text($x,$y,"<b>".mysql_num_rows($result)." record".((mysql_num_rows($result)!=1)?"s":"")." found</b>");
      else
        $cf->label($x,$y,mysql_num_rows($result)." rec.");
      if (mysql_num_rows($result)>0) {
        while ($row=mysql_fetch_array($result)) {
          $results[] = $row['epithet']." <".$row['epithetID'].">";
        }
      }
    }
  }
  return $results;
}


function makeAuthor($search,$x,$y,$top) {
  global $cf;

  $pieces = explode(" <",$search);
  $results[] = "";
  if ($search && strlen($search)>1) {
    $sql = "SELECT author, authorID, Brummit_Powell_full ".
           "FROM tbl_tax_authors ".
           "WHERE author LIKE '".mysql_escape_string($pieces[0])."%' ".
           "ORDER BY author";
    if ($result = db_query($sql)) {
      if ($top)
        $cf->text($x,$y,"<b>".mysql_num_rows($result)." record".((mysql_num_rows($result)!=1)?"s":"")." found</b>");
      else
        $cf->label($x,$y,mysql_num_rows($result)." rec.");
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


function makeGen($search,$x,$y) {
  global $cf;

  $results[] = "";
  if ($search && strlen($search)>1) {
    $pieces = explode(" ",$search);
    $sql = "SELECT tg.genus, tg.genID, tg.DallaTorreIDs, tg.DallaTorreZusatzIDs, ".
            "ta.author, tf.family, tsc.category ".
           "FROM tbl_tax_genera tg ".
            "LEFT JOIN tbl_tax_authors ta ON ta.authorID = tg.authorID ".
            "LEFT JOIN tbl_tax_families tf ON tg.familyID = tf.familyID ".
            "LEFT JOIN tbl_tax_systematic_categories tsc ON tf.categoryID = tsc.categoryID ".
           "WHERE genus LIKE '".mysql_escape_string($pieces[0])."%' ".
           "ORDER BY tg.genus";
    if ($result = db_query($sql)) {
      $cf->text($x,$y,"<b>".mysql_num_rows($result)." record".((mysql_num_rows($result)!=1)?"s":"")." found</b>");
      if (mysql_num_rows($result)>0) {
        while ($row=mysql_fetch_array($result)) {
          $results[] = $row['genus']." ".$row['author']." ".$row['family']." ".
                       $row['category']." ".$row['DallaTorreIDs'].$row['DallaTorreZusatzIDs'].
                       " <".$row['genID'].">";
        }
      }
    }
    foreach ($results as $k => $v)
      $results[$k] = preg_replace("/ [\s]+/"," ",$v);
  }
  return $results;
}


function makeSyn($search) {

  $results[] = "";
  if ($search && strlen($search)>1) {
    $pieces = explode(chr(194).chr(183),$search);
    $pieces = explode(" ",$pieces[0]);
    $sql = "SELECT ts.taxonID, tg.genus, tg.DallaTorreIDs, tg.DallaTorreZusatzIDs, ".
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
            "LEFT JOIN tbl_tax_status tst ON tst.statusID=ts.statusID ".
            "LEFT JOIN tbl_tax_genera tg ON tg.genID=ts.genID ".
            "LEFT JOIN tbl_tax_families tf ON tf.familyID=tg.familyID ".
           "WHERE genus LIKE '".mysql_escape_string($pieces[0])."%' ";
    if ($pieces[1])
      $sql .= "AND te.epithet LIKE '".mysql_escape_string($pieces[1])."%' ";
    $sql .= "ORDER BY tg.genus, te.epithet";
    if ($result = db_query($sql)) {
      if (mysql_num_rows($result)>0) {
        while ($row=mysql_fetch_array($result))
          $results[] = taxon($row,true);
      }
    }
    foreach ($results as $k => $v)
      $results[$k] = preg_replace("/ [\s]+/"," ",$v);
  }
  return $results;
}

// main program

if (isset($_GET['sel']) && extractID($_GET['sel'])!="NULL") {
  $sql = "SELECT ts.taxonID, ts.synID, ts.basID, ts.genID, ts.annotation, ".
          "tg.genus, tg.DallaTorreIDs, tg.DallaTorreZusatzIDs, tag.author author_g, ".
          "tf.family, tsc.category, tst.status, tst.statusID, tr.rank, tr.tax_rankID, ".
          "ta.author, ta.authorID, ta.Brummit_Powell_full, ".
          "ta1.author author1, ta1.authorID authorID1, ta1.Brummit_Powell_full bpf1, ".
          "ta2.author author2, ta2.authorID authorID2, ta2.Brummit_Powell_full bpf2, ".
          "ta3.author author3, ta3.authorID authorID3, ta3.Brummit_Powell_full bpf3, ".
          "ta4.author author4, ta4.authorID authorID4, ta4.Brummit_Powell_full bpf4, ".
          "ta5.author author5, ta5.authorID authorID5, ta5.Brummit_Powell_full bpf5, ".
          "te.epithet, te.epithetID, ".
          "te1.epithet epithet1, te1.epithetID epithetID1, ".
          "te2.epithet epithet2, te2.epithetID epithetID2, ".
          "te3.epithet epithet3, te3.epithetID epithetID3, ".
          "te4.epithet epithet4, te4.epithetID epithetID4, ".
          "te5.epithet epithet5, te5.epithetID epithetID5 ".
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
          "LEFT JOIN tbl_tax_status tst ON tst.statusID=ts.statusID ".
          "LEFT JOIN tbl_tax_rank tr ON tr.tax_rankID=ts.tax_rankID ".
          "LEFT JOIN tbl_tax_genera tg ON tg.genID=ts.genID ".
          "LEFT JOIN tbl_tax_authors tag ON tag.authorID = tg.authorID ".
          "LEFT JOIN tbl_tax_families tf ON tf.familyID=tg.familyID ".
          "LEFT JOIN tbl_tax_systematic_categories tsc ON tf.categoryID = tsc.categoryID ".
         "WHERE taxonID=".extractID($_GET['sel']);
  $result = db_query($sql);
  if (mysql_num_rows($result)>0) {
    $row = mysql_fetch_array($result);
    $p_taxonID    = $row['taxonID'];
    $p_species    = ($row['epithet']) ? $row['epithet']." <".$row['epithetID'].">" : "";
    $p_author     = ($row['author']) ? $row['author']." <".$row['authorID'].">" : "";
    if ($row['Brummit_Powell_full']) $p_author .= " [".replaceNewline($row['Brummit_Powell_full'])."]";
    $p_subspecies = ($row['epithet1']) ? $row['epithet1']." <".$row['epithetID1'].">" : "";
    $p_subauthor  = ($row['author1']) ? $row['author1']." <".$row['authorID1'].">" : "";
    if ($row['bpf1']) $p_subauthor .= " [".replaceNewline($row['bpf1'])."]";
    $p_variety    = ($row['epithet2']) ? $row['epithet2']." <".$row['epithetID2'].">" : "";
    $p_varauthor  = ($row['author2']) ? $row['author2']." <".$row['authorID2'].">" : "";
    if ($row['bpf2']) $p_varauthor .= " [".replaceNewline($row['bpf2'])."]";
    $p_subvariety   = ($row['epithet3']) ? $row['epithet3']." <".$row['epithetID3'].">" : "";
    $p_subvarauthor = ($row['author3']) ? $row['author3']." <".$row['authorID3'].">" : "";
    if ($row['bpf3']) $p_subvarauthor .= " [".replaceNewline($row['bpf3'])."]";
    $p_forma      = ($row['epithet4']) ? $row['epithet4']." <".$row['epithetID4'].">" : "";
    $p_forauthor  = ($row['author4']) ? $row['author4']." <".$row['authorID4'].">" : "";
    if ($row['bpf4']) $p_forauthor .= " [".replaceNewline($row['bpf4'])."]";
    $p_subforma     = ($row['epithet5']) ? $row['epithet5']." <".$row['epithetID5'].">" : "";
    $p_subforauthor = ($row['author5']) ? $row['author5']." <".$row['authorID5'].">" : "";
    if ($row['bpf5']) $p_subforauthor .= " [".replaceNewline($row['bpf5'])."]";
    $p_gen        = $row['genus']." ".$row['author_g']." ".$row['family']." ".
                    $row['category']." ".$row['DallaTorreIDs'].$row['DallaTorreZusatzIDs'].
                    " <".$row['genID'].">";
    $p_status     = $row['status']." <".$row['statusID'].">";
    $p_rank       = $row['rank']." <".$row['tax_rankID'].">";
    $p_annotation = $row['annotation'];

    if ($row['synID']) {
      $sql = "SELECT ts.taxonID, tg.genus, tg.DallaTorreIDs, tg.DallaTorreZusatzIDs, ".
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
              "LEFT JOIN tbl_tax_status tst ON tst.statusID=ts.statusID ".
              "LEFT JOIN tbl_tax_genera tg ON tg.genID=ts.genID ".
              "LEFT JOIN tbl_tax_families tf ON tf.familyID=tg.familyID ".
             "WHERE ts.taxonID='".mysql_escape_string($row['synID'])."'";
      $result2 = db_query($sql);
      $row2 = mysql_fetch_array($result2);
      $p_syn   = taxon($row2,true);
    }
    else
      $p_syn = "";

    if ($row['basID']) {
      $sql = "SELECT ts.taxonID, tg.genus, tg.DallaTorreIDs, tg.DallaTorreZusatzIDs, ".
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
              "LEFT JOIN tbl_tax_status tst ON tst.statusID=ts.statusID ".
              "LEFT JOIN tbl_tax_genera tg ON tg.genID=ts.genID ".
              "LEFT JOIN tbl_tax_families tf ON tf.familyID=tg.familyID ".
             "WHERE ts.taxonID='".mysql_escape_string($row['basID'])."'";
      $result2 = db_query($sql);
      $row2 = mysql_fetch_array($result2);
      $p_bas   = taxon($row2,true);
    }
    else
      $p_bas = "";
  }
  else {
    $p_taxonID = $p_species = $p_author = $p_subspecies = $p_subauthor = "";
    $p_variety = $p_varauthor = $p_subvariety = $p_subvarauthor = "";
    $p_forma = $p_forauthor = $p_subforma = $p_subforauthor = $p_gen = "";
    $p_syn = $p_bas = $p_annotation = "";
    // Rank
    $result = db_query("SELECT rank FROM tbl_tax_rank WHERE tax_rankID=1");
    $dummy = mysql_fetch_array($result);
    $p_rank = $dummy['rank']." <1>";
    // Status
    $result = db_query("SELECT status FROM tbl_tax_status WHERE statusID=96");
    $dummy = mysql_fetch_array($result);
    $p_status = $dummy['status']." <96>";
  }
  if ($_GET['new']==1) $p_taxonID = "";
  if ($_GET['edit']) $edit = true;
}
else {
  $p_species      = $_POST['species'];
  $p_author       = $_POST['author'];
  $p_subspecies   = $_POST['subspecies'];
  $p_subauthor    = $_POST['subauthor'];
  $p_variety      = $_POST['variety'];
  $p_varauthor    = $_POST['varauthor'];
  $p_subvariety   = $_POST['subvariety'];
  $p_subvarauthor = $_POST['subvarauthor'];
  $p_forma        = $_POST['forma'];
  $p_forauthor    = $_POST['forauthor'];
  $p_subforma     = $_POST['subforma'];
  $p_subforauthor = $_POST['subforauthor'];
  $p_gen          = $_POST['gen'];
  $p_syn          = $_POST['syn'];
  $p_bas          = $_POST['bas'];
  $p_annotation   = $_POST['annotation'];

  if ($_POST['rank'])
    $p_rank = $_POST['rank'];
  else {
    $result = db_query("SELECT rank FROM tbl_tax_rank WHERE tax_rankID=1");
    $dummy = mysql_fetch_array($result);
    $p_rank = $dummy['rank']." <1>";
  }
  if ($_POST['status'])
    $p_status = $_POST['status'];
  else {
    $result = db_query("SELECT status FROM tbl_tax_status WHERE statusID=96");
    $dummy = mysql_fetch_array($result);
    $p_status = $dummy['status']." <96>";
  }

}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - edit Species</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="herbardb_input.css">
  <script type="text/javascript" language="JavaScript">
    function listSynonyms(sel) {
      target  = "listSynonyms.php?ID=" + encodeURIComponent(sel);
      options = "width=800,height=400,top=50,left=50,scrollbars=yes,resizable=yes";
      MeinFenster = window.open(target,"listSynonyms",options);
      MeinFenster.focus();
    }
  </script>
</head>

<body>

<form Action="<?=$_SERVER['PHP_SELF'];?>" Method="POST" name="f">

<?php

unset($rank);
$sql = "SELECT rank, tax_rankID FROM tbl_tax_rank ORDER BY rank";
if ($result = db_query($sql)) {
  if (mysql_num_rows($result)>0) {
    while ($row=mysql_fetch_array($result)) {
      $rank[] = $row['rank']." <".$row['tax_rankID'].">";
    }
  }
}

unset($status);
$sql = "SELECT status, statusID FROM tbl_tax_status ORDER BY status";
if ($result = db_query($sql)) {
  if (mysql_num_rows($result)>0) {
    while ($row=mysql_fetch_array($result)) {
      $status[] = $row['status']." <".$row['statusID'].">";
    }
  }
}

// Header 1: Status und Taxon
$sql = "SELECT ts.taxonID, tg.genus, tg.DallaTorreIDs, tg.DallaTorreZusatzIDs, tst.status, ".
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
        "LEFT JOIN tbl_tax_status tst ON tst.statusID=ts.statusID ".
        "LEFT JOIN tbl_tax_genera tg ON tg.genID=ts.genID ".
       "WHERE taxonID='".mysql_escape_string($p_taxonID)."'";
$result = db_query($sql);
if (mysql_num_rows($result)>0) {
  $row = mysql_fetch_array($result);
  $display_head1 = $row['status']."&nbsp;&nbsp;".taxon($row,true);
}
else
  $display_head1 = "";

// Header 2: Literaturverweis, wenn nur einer vorhanden
$sql ="SELECT paginae, figures, ".
       "l.suptitel, le.autor as editor, la.autor, l.periodicalID, lp.periodical, ".
       "l.vol, l.part, l.jahr ".
      "FROM tbl_tax_index ti ".
       "LEFT JOIN tbl_lit l ON l.citationID=ti.citationID ".
       "LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID=l.periodicalID ".
       "LEFT JOIN tbl_lit_authors le ON le.autorID=l.editorsID ".
       "LEFT JOIN tbl_lit_authors la ON la.autorID=l.autorID ".
      "WHERE taxonID='".mysql_escape_string($p_taxonID)."'";
$result = db_query($sql);
if (mysql_num_rows($result)>0) {
  if (mysql_num_rows($result)==1) {
    $row=mysql_fetch_array($result);
    $display_head2 = $row['autor']." (".$row['jahr'].")";
    if ($row['suptitel']) $display_head2 .= " in ".$row['editor'].": ".$row['suptitel'];
    if ($row['periodicalID']) $display_head2 .= " ".$row['periodical'];
    $display_head2 .= " ".$row['vol'];
    if ($row['part']) $display_head2 .= " (".$row['part'].")";
    $display_head2 .= ": ".$row['paginae'].". ".$row['figures'];
  }
  else
    $display_head2 = "multi";
}
else
  $display_head2 = "&mdash;";

// Header 3: Typus, wenn nur einer vorhanden
$sql ="SELECT Sammler, Sammler_2, series, leg_nr, alternate_number, date, duplicates ".
      "FROM tbl_tax_typecollections tt, tbl_collector c ".
       "LEFT JOIN tbl_collector_2 c2 ON tt.Sammler_2ID=c2.Sammler_2ID ".
      "WHERE tt.SammlerID=c.SammlerID ".
       "AND taxonID='".mysql_escape_string($p_taxonID)."'";
$result = db_query($sql);
if (mysql_num_rows($result)>0) {
  if (mysql_num_rows($result)==1) {
    $row=mysql_fetch_array($result);
    $display_head3 = $row['Sammler'];
    if ($row['Sammler_2']) {
      if (strstr($row['Sammler_2'],"&")===false)
        $display_head3 .= " & ".$row['Sammler_2'];
      else
        $display_head3 .= " et al.";
    }
    if ($row['series']) $display_head3 .= " ".$row['series'];
    if ($row['leg_nr']) $display_head3 .= " ".$row['leg_nr'];
    if ($row['alternate_number']) {
      $display_head3 .= " ".$row['alternate_number'];
      if (strstr($row['alternate_number'],"s.n.")!==false)
        $display_head3 .= " [".$row['date']."]";
    }
    $display_head3 .= "; ".$row['duplicates'];
  }
  else
    $display_head3 = "multi";
}
else {
  if (extractID($p_bas)!="NULL") {
    $sql ="SELECT Sammler, Sammler_2, series, leg_nr, alternate_number, date, duplicates ".
          "FROM tbl_tax_typecollections tt, tbl_collector c ".
           "LEFT JOIN tbl_collector_2 c2 ON tt.Sammler_2ID=c2.Sammler_2ID ".
          "WHERE tt.SammlerID=c.SammlerID ".
           "AND taxonID=".extractID($p_bas);
    $result = db_query($sql);
    if (mysql_num_rows($result)>0)
      $display_head3 = "&rarr; Basionym"; // "-> Basionym" wenn im Basionym mindestens ein Typus eingetragen ist
    else
      $display_head3 = "&mdash;";
  }
  else
    $display_head3 = "&mdash;";
}

if ($nr) {
  echo "<div style=\"position: absolute; left: 1em; top: 0.4em;\">";
  if ($nr>1)
    echo "<a href=\"editSpecies.php?sel=".htmlentities("<".$linkList[$nr-1].">")."&nr=".($nr-1)."\">".
         "<img border=\"0\" height=\"22\" src=\"left.gif\" width=\"20\">".
         "</a>";
  else
    echo "<img border=\"0\" height=\"22\" src=\"left_gray.gif\" width=\"20\">";
  echo "</div>\n";
  echo "<div style=\"position: absolute; left: 2.5em; top: 0.4em;\">";
  if ($nr<$linkList[0])
    echo "<a href=\"editSpecies.php?sel=".htmlentities("<".$linkList[$nr+1].">")."&nr=".($nr+1)."\">".
         "<img border=\"0\" height=\"22\" src=\"right.gif\" width=\"20\">".
         "</a>";
  else
    echo "<img border=\"0\" height=\"22\" src=\"right_gray.gif\" width=\"20\">";
  echo "</div>\n";
}

$cf = new CSSF();

echo "<input type=\"hidden\" name=\"taxonID\" value=\"$p_taxonID\">\n";
if ($p_taxonID) {
  if ($edit) {
    echo "<input type=\"hidden\" name=\"edit\" value=\"$edit\">\n";
    $text = "<span style=\"background-color: red\">&nbsp;<b>$p_taxonID</b>&nbsp;</span>";
  }
  else
    $text = $p_taxonID;
}
else
  $text = "<span style=\"background-color: red\">&nbsp;<b>new</b>&nbsp;</span>";
$cf->label(9,0.5,"taxonID");
$cf->text(9,0.5,"&nbsp;".$text);

if ($p_taxonID) {
  $cf->text(9+strlen($p_taxonID),0.5,$display_head1);
  $cf->text(9+strlen($p_taxonID),2,$display_head2);
  $cf->text(9+strlen($p_taxonID),3.5,$display_head3);
}

$cf->labelMandatory(9,6.5,6,"Genus");
$cf->editDropdown(9,6.5,51,"gen",$p_gen,makeGen($p_gen,9,5),500);

$cf->labelMandatory(9,10.5,6,"Rank");
$cf->dropdown(9,10.5,"rank",$p_rank,$rank,$rank);

$cf->labelMandatory(36,10.5,6,"Status");
$cf->dropdown(36,10.5,"status",$p_status,$status,$status);
if (substr(extractID($p_status),1,-1)==96)
  $cf->label(30,10.5,"list synonyms","javascript:listSynonyms($p_taxonID)");

$cf->labelMandatory(9,14,6,"Species");
$cf->editDropdown(9,14,20,"species",$p_species,makeEpithet($p_species,9,12.5,true),65);
$cf->labelMandatory(40,14,6,"Author");
$cf->editDropdown(40,14,20,"author",$p_author,makeAuthor($p_author,40,12.5,true),520);

$cf->label(9,18,"Subspecies");
$cf->editDropdown(9,18,20,"subspecies",$p_subspecies,makeEpithet($p_subspecies,9,19.7,false),65);
$cf->label(40,18,"Author");
$cf->editDropdown(40,18,20,"subauthor",$p_subauthor,makeAuthor($p_subauthor,40,19.7,false),520);

$cf->label(9,22,"Variety");
$cf->editDropdown(9,22,20,"variety",$p_variety,makeEpithet($p_variety,9,23.7,false),65);
$cf->label(40,22,"Author");
$cf->editDropdown(40,22,20,"varauthor",$p_varauthor,makeAuthor($p_varauthor,40,23.7,false),520);

$cf->label(9,26,"Subvariety");
$cf->editDropdown(9,26,20,"subvariety",$p_subvariety,makeEpithet($p_subvariety,9,27.7,false),65);
$cf->label(40,26,"Author");
$cf->editDropdown(40,26,20,"subvarauthor",$p_subvarauthor,makeAuthor($p_subvarauthor,40,27.7,false),520);

$cf->label(9,30,"Forma");
$cf->editDropdown(9,30,20,"forma",$p_forma,makeEpithet($p_forma,9,31.7,false),65);
$cf->label(40,30,"Author");
$cf->editDropdown(40,30,20,"forauthor",$p_forauthor,makeAuthor($p_forauthor,40,31.7,false),520);

$cf->label(9,34,"Subforma");
$cf->editDropdown(9,34,20,"subforma",$p_subforma,makeEpithet($p_subforma,9,35.7,false),65);
$cf->label(40,34,"Author");
$cf->editDropdown(40,34,20,"subforauthor",$p_subforauthor,makeAuthor($p_subforauthor,40,35.7,false),520);

$cf->label(9,38,"accepted Taxon");
$cf->editDropdown(9,38,51,"syn",$p_syn,makeSyn($p_syn),500);
$cf->label(5,39.2,"<font size=\"+1\"><b>&laquo;</b></font>","javascript:history.back()");
if (extractID($p_syn)!="NULL")
  $cf->label(9,39.7,"link","editSpecies.php?sel=".htmlspecialchars("<".substr(extractID($p_syn),1,-1).">"));

$cf->label(9,41.5,"Basionym");
$cf->editDropdown(9,41.5,51,"bas",$p_bas,makeSyn($p_bas),500);
$cf->label(5,42.8,"<font size=\"+1\"><b>&laquo;</b></font>","javascript:history.back()");
if (extractID($p_bas)!="NULL")
  $cf->label(9,43.2,"link","editSpecies.php?sel=".htmlspecialchars("<".substr(extractID($p_bas),1,-1).">"));

$cf->label(9,45,"annotations");
$cf->textarea(9,45,51,2.6,"annotation",$p_annotation);

$cf->buttonJavaScript(2,49," < Taxonomy ","self.location.href='listTax.php?nr=$nr'");
?>
</form>

</div>
</body>
</html>