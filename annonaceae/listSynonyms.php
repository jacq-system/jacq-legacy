<?php
session_start();
require("inc/connect.php");
require("inc/herbardb_input_functions.php");
no_magic();

$id = intval($_GET['ID']);

if (isset($_GET['short']) && intval($_GET['short']))
  $short = true; // taxon and protolog on same line
else
  $short = false; // taxon and protolog in 2 lines

function smallCaps($text) {
  return "<span style=\"font-variant: small-caps\">".htmlspecialchars($text)."</span>";
}

function italics($text) {
  return "<span style=\"font-style:italic\">".htmlspecialchars($text)."</span>";
}

function taxonList($row) {

  $text = italics($row['genus']);
  if ($row['epithet'])
    $text .= " ".italics($row['epithet']).htmlspecialchars(chr(194).chr(183))." ".smallCaps($row['author']);
  else
    $text .= htmlspecialchars(chr(194).chr(183));
  if ($row['epithet1']) $text .= " subsp. ".italics($row['epithet1'])." ".smallCaps($row['author1']);
  if ($row['epithet2']) $text .= " var. ".italics($row['epithet2'])." ".smallCaps($row['author2']);
  if ($row['epithet3']) $text .= " subvar. ".italics($row['epithet3'])." ".smallCaps($row['author3']);
  if ($row['epithet4']) $text .= " forma ".italics($row['epithet4'])." ".smallCaps($row['author4']);
  if ($row['epithet5']) $text .= " subforma ".italics($row['epithet5'])." ".smallCaps($row['author5']);

  return $text;
}

function protologList($taxon,$short=false) {
  $sql ="SELECT paginae, figures,
          l.suptitel, le.autor as editor, la.autor, l.periodicalID, lp.periodical,
          l.vol, l.part, l.jahr
         FROM tbl_tax_index ti
          LEFT JOIN tbl_lit l ON l.citationID = ti.citationID
          LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID = l.periodicalID
          LEFT JOIN tbl_lit_authors le ON le.autorID = l.editorsID
          LEFT JOIN tbl_lit_authors la ON la.autorID = l.autorID
         WHERE taxonID = '" . mysql_escape_string($taxon) . "'";
  $result = db_query($sql);
  $display = "";
  if (mysql_num_rows($result)>0) {
    while ($row=mysql_fetch_array($result)) {
      $display = ($short) ? "" : smallCaps($row['autor'])." (".htmlspecialchars($row['jahr']).")";
      if ($row['suptitel']) $display .= " in ".htmlspecialchars($row['editor']).": ".htmlspecialchars($row['suptitel']);
      if ($row['periodicalID']) $display .= " ".htmlspecialchars($row['periodical']);
      $display .= " ".htmlspecialchars($row['vol']);
      if ($row['part']) $display .= " (".htmlspecialchars($row['part']).")";
      $display .= ": ".htmlspecialchars($row['paginae']).". ".htmlspecialchars($row['figures']);
      if ($short) $display .= " (".htmlspecialchars($row['jahr']).")";
    }
  }
  elseif (!$short)
    $display = "&mdash;";

  return $display;
}

function typusList($taxon,$sw) {
  $sql ="SELECT Sammler, Sammler_2, series, leg_nr, alternate_number, date, duplicates
         FROM tbl_tax_typecollections tt, tbl_collector c
          LEFT JOIN tbl_collector_2 c2 ON tt.Sammler_2ID = c2.Sammler_2ID
         WHERE tt.SammlerID = c.SammlerID
          AND taxonID = '" . mysql_escape_string($taxon) . "'";
  $result = db_query($sql);
  if (mysql_num_rows($result)>0) {
    while ($row=mysql_fetch_array($result)) {
      $display = $row['Sammler'];
      if ($row['Sammler_2']) {
        if (strstr($row['Sammler_2'],"&")===false)
          $display .= " & ".$row['Sammler_2'];
        else
          $display .= " et al.";
      }
      if ($row['series']) $display .= " ".$row['series'];
      if ($row['leg_nr']) $display .= " ".$row['leg_nr'];
      if ($row['alternate_number']) {
        $display .= " ".$row['alternate_number'];
        if (strstr($row['alternate_number'],"s.n.")!==false)
          $display .= " [".$row['date']."]";
      }
      $display .= "; ".$row['duplicates'];
      if ($sw) echo "<tr><td colspan=\"2\"></td><td>";
      echo htmlspecialchars($display);
      if ($sw)
        echo "</td></tr>\n";
      else
        echo "<br>\n";
    }
  }
  else {
    if ($sw)
      echo "<tr><td colspan=\"2\"></td><td>&mdash;</td></tr>\n";
    else
      echo "&mdash;<br>\n";
  }
}

function item($offset, $row, $short, $sign="=") {
  if ($short) {
    echo "<tr><td width=\"$offset\">&nbsp;</td>".
         "<td>$sign ".$row['status']."&nbsp;&nbsp;</td>".
         "<td>".taxonList($row).protologList($row['taxonID'],true)."</td></tr>\n";

  }
  else {
    echo "<tr><td width=\"$offset\">&nbsp;</td>".
         "<td>$sign ".$row['status']."&nbsp;&nbsp;</td>".
         "<td>".taxonList($row)."</td></tr>\n";
    echo "<tr><td colspan=\"2\"></td><td>".protologList($row['taxonID'])."</td></tr>\n";
  }
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/transitional.dtd">
<html>
<head>
  <title>herbardb - list synonyms</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link rel="stylesheet" type="text/css" href="herbardb_input.css">
</head>

<body>

<?php
$result = db_query("SELECT taxonID, synID FROM tbl_tax_species WHERE taxonID='".mysql_escape_string($id)."'");
$row = mysql_fetch_array($result);
if (!empty($row['synID'])) $id = $row['synID'];

$order = " ORDER BY genus, epithet, author, epithet1, author1, epithet2, author2, epithet3, author3";

$sql = "SELECT ts.taxonID, ts.basID, ts.synID, tg.genus, tg.DallaTorreIDs, tg.DallaTorreZusatzIDs, tst.status,
         ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
         ta4.author author4, ta5.author author5,
         te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
         te4.epithet epithet4, te5.epithet epithet5
        FROM tbl_tax_species ts
         LEFT JOIN tbl_tax_authors ta ON ta.authorID=ts.authorID
         LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID=ts.subspecies_authorID
         LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID=ts.variety_authorID
         LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID=ts.subvariety_authorID
         LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID=ts.forma_authorID
         LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID=ts.subforma_authorID
         LEFT JOIN tbl_tax_epithets te ON te.epithetID=ts.speciesID
         LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID=ts.subspeciesID
         LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID=ts.varietyID
         LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID=ts.subvarietyID
         LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID=ts.formaID
         LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID=ts.subformaID
         LEFT JOIN tbl_tax_status tst ON tst.statusID=ts.statusID
         LEFT JOIN tbl_tax_genera tg ON tg.genID=ts.genID
        WHERE taxonID='".mysql_escape_string($id)."'";
$result = db_query($sql);
if (mysql_num_rows($result)>0) {
  $row = mysql_fetch_array($result);

  if ($short) {
    echo "<b>".taxonList($row)."</b>".protologList($row['taxonID'],true)."<br>\n";
  }
  else {
    echo "<b>".taxonList($row)."</b><br>\n".
         protologList($row['taxonID'])."<br>\n";
  }
  if (empty($row['synID']) && empty($row['basID'])) typusList($row['taxonID'],false);

  $tableStart = "<table cellspacing=\"0\" cellpadding=\"2\">";
  $sql = "SELECT ts.taxonID, tg.genus, tg.DallaTorreIDs, tg.DallaTorreZusatzIDs, tst.status,
           ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
           ta4.author author4, ta5.author author5,
           te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
           te4.epithet epithet4, te5.epithet epithet5
          FROM tbl_tax_species ts
           LEFT JOIN tbl_tax_authors ta ON ta.authorID = ts.authorID
           LEFT JOIN tbl_tax_authors ta1 ON ta1.authorID = ts.subspecies_authorID
           LEFT JOIN tbl_tax_authors ta2 ON ta2.authorID = ts.variety_authorID
           LEFT JOIN tbl_tax_authors ta3 ON ta3.authorID = ts.subvariety_authorID
           LEFT JOIN tbl_tax_authors ta4 ON ta4.authorID = ts.forma_authorID
           LEFT JOIN tbl_tax_authors ta5 ON ta5.authorID = ts.subforma_authorID
           LEFT JOIN tbl_tax_epithets te ON te.epithetID = ts.speciesID
           LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID = ts.subspeciesID
           LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID = ts.varietyID
           LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID = ts.subvarietyID
           LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID = ts.formaID
           LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID = ts.subformaID
           LEFT JOIN tbl_tax_status tst ON tst.statusID = ts.statusID
           LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
          WHERE synID = '" . mysql_escape_string($id) . "' ";
  if (empty($row['basID']))
    $result2 = db_query($sql."AND basID='".mysql_escape_string($id)."'");
  else
    $result2 = db_query($sql."AND (basID IS NULL OR basID='".mysql_escape_string($id)."') AND taxonID='".$row['basID']."'");

  while ($row2 = mysql_fetch_array($result2)) {
    echo $tableStart;
    echo item(20,$row2,$short,"&equiv;");
    typusList($row2['taxonID'],true);
    echo "</table>\n";
    $result3 = db_query($sql."AND basID='".$row2['taxonID']."'".$order);
    while ($row3 = mysql_fetch_array($result3)) {
      echo $tableStart;
      echo item(40,$row3,$short,"&equiv;");
      echo "</table>\n";
    }
  }
  if (empty($row['basID']))
    $result2 = db_query($sql."AND basID IS NULL".$order);
  else
    $result2 = db_query($sql."AND (basID IS NULL OR basID='".mysql_escape_string($id)."') AND taxonID!='".$row['basID']."'".$order);

  while ($row2 = mysql_fetch_array($result2)) {
    echo $tableStart;
    echo item(20,$row2,$short);
    typusList($row2['taxonID'],true);
    echo "</table>\n";
    $result3 = db_query($sql."AND basID='".$row2['taxonID']."'".$order);
    while ($row3 = mysql_fetch_array($result3)) {
      echo $tableStart;
      echo item(40,$row3,$short,"&equiv;");
      echo "</table>\n";
    }
  }
}
else
  echo "<b>no data</b>\n";
?>
</body>
</html>