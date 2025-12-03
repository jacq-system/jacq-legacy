<?php
function collection ($Sammler, $Sammler_2, $series, $series_number, $Nummer, $alt_number, $Datum)
{
    $text = $Sammler;
    if (strstr($Sammler_2, "&") || strstr($Sammler_2, "et al.")) {
        $text .= " et al.";
    } elseif ($Sammler_2) {
        $text .= " & " . $Sammler_2;
    }
    if ($series_number) {
        if ($Nummer) $text .= " " . $Nummer;
        if ($alt_number && trim($alt_number) != "s.n.") $text .= " " . $alt_number;
        if ($series) $text .= " " . $series;
        $text .= " " . $series_number;
    } else {
        if ($series) $text .= " " . $series;
        if ($Nummer) $text .= " " . $Nummer;
        if ($alt_number) $text .= " " . $alt_number;
        if (strstr($alt_number, "s.n.")) $text .= " [" . $Datum . "]";
    }

    if (defined('TCPDF'))
        return $text;
    else
        return utf8_decode($text);
}

function formatLabelDateRange($dateStart, $dateEnd)
{
    $dateStart = trim((string)$dateStart);
    $dateEnd = trim((string)$dateEnd);

    if ($dateStart && $dateEnd && $dateStart !== $dateEnd) {
        return $dateStart . " - " . $dateEnd;
    }

    return $dateStart ?: $dateEnd;
}

function smallCaps ($text)
{
    if (defined('TCPDF')) {
        $ucase = mb_strtoupper($text, 'UTF-8');
        $sc = false;
        $outtext = "";
        for ($i = 0; $i < mb_strlen($text, 'UTF-8'); $i++) {
            $ch_text  = mb_substr($text,$i, 1, 'UTF-8');
            $ch_ucase = mb_substr($ucase,$i, 1, 'UTF-8');
            if ($ch_text != $ch_ucase && !$sc) {
                $outtext .= "<small>";
                $sc = true;
            } elseif ($ch_text == $ch_ucase && $sc) {
                $outtext .= "</small>";
                $sc = false;
            }
            $outtext .= $ch_ucase;
        }
        if ($sc) $outtext .= "</small>";

        return $outtext;
    } else {
        //setlocale(LC_CTYPE, 'de_DE');
        //$dummy = strtoupper(utf8_decode($text));
        return "<sm>" . utf8_decode($text) . "</sm>";
    }
  /*
  $ret = "";
  $tagB = "<sm>";
  $tagE = "</sm>";
  $firstpassed = $tagWritten = false;
  for ($i=0;$i<strlen($dummy);$i++) {
    $letter = substr($dummy,$i,1);
    if (ctype_alpha($letter)) {
      if (!$firstpassed) {
        $firstpassed = true;
      }
      elseif (!$tagWritten) {
        $ret .= $tagB;
        $tagWritten = true;
      }
    }
    elseif (ctype_space($letter)) {
      if ($tagWritten) $ret .= $tagE;
      $firstpassed = $tagWritten = false;
    }
    $ret .= $letter;
  }
  if ($tagWritten) $ret .= $tagE;
  return $ret; */
}

function taxon ($row, $withNl = false)
{
    if (defined('TCPDF')) {
        $sep = ($withNl) ? "<br>" : " ";

        $text = "<i>" . $row['genus'] . "</i>";
        if ($row['epithet'])  $text .=              " <i>"   . $row['epithet']  . "</i> " . smallCaps($row['author']);
        if ($row['epithet1']) $text .= $sep . "subsp. <i>"   . $row['epithet1'] . "</i> " . smallCaps($row['author1']);
        if ($row['epithet2']) $text .= $sep . "var. <i>"     . $row['epithet2'] . "</i> " . smallCaps($row['author2']);
        if ($row['epithet3']) $text .= $sep . "subvar. <i>"  . $row['epithet3'] . "</i> " . smallCaps($row['author3']);
        if ($row['epithet4']) $text .= $sep . "forma <i>"    . $row['epithet4'] . "</i> " . smallCaps($row['author4']);
        if ($row['epithet5']) $text .= $sep . "subforma <i>" . $row['epithet5'] . "</i> " . smallCaps($row['author5']);

        return "$text";
    } else {
        $sep = ($withNl) ? "</p>\n<p>" : " ";

        $text = "<ii>" . utf8_decode($row['genus']) . "</ii>";
        if ($row['epithet'])  $text .=              " <ii>"   . utf8_decode($row['epithet'])  . "</ii> " . smallCaps($row['author']);
        if ($row['epithet1']) $text .= $sep . "subsp. <ii>"   . utf8_decode($row['epithet1']) . "</ii> " . smallCaps($row['author1']);
        if ($row['epithet2']) $text .= $sep . "var. <ii>"     . utf8_decode($row['epithet2']) . "</ii> " . smallCaps($row['author2']);
        if ($row['epithet3']) $text .= $sep . "subvar. <ii>"  . utf8_decode($row['epithet3']) . "</ii> " . smallCaps($row['author3']);
        if ($row['epithet4']) $text .= $sep . "forma <ii>"    . utf8_decode($row['epithet4']) . "</ii> " . smallCaps($row['author4']);
        if ($row['epithet5']) $text .= $sep . "subforma <ii>" . utf8_decode($row['epithet5']) . "</ii> " . smallCaps($row['author5']);

        return "<p>" . $text . "</p>";
    }
}

function taxonWithHybrids ($row, $withNl = false)
{
    if (isset($row['statusID']) && $row['statusID'] == 1 && strlen($row['epithet']) == 0 && strlen($row['author']) == 0) {
        $sql = "SELECT parent_1_ID, parent_2_ID
                FROM tbl_tax_hybrids
                WHERE taxon_ID_fk = '" . $row['taxonID'] . "'";
        $rowHybrid = dbi_query($sql)->fetch_array();
        $sql = "SELECT tg.genus,
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
                 LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
                WHERE taxonID = '" . $rowHybrid['parent_1_ID'] . "'";
        $row1 = dbi_query($sql)->fetch_array();
        $sql = "SELECT tg.genus,
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
                 LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
                WHERE taxonID = '" . $rowHybrid['parent_2_ID'] . "'";
        $row2 = dbi_query($sql)->fetch_array();

        return taxon($row1, $withNl) . " x " . taxon($row2, $withNl);
    } else {
        return taxon($row, $withNl);
    }
}

function protolog($row)
{
    $text = "";
    if ($row['suptitel']) $text .= "in " . $row['autor'] . ": " . $row['suptitel'] . " ";
    if ($row['periodicalID']) $text .= $row['periodical'];
    $text .= " " . $row['vol'];
    if ($row['part']) $text .= " (" . $row['part'] . ")";
    $text .= ": " . $row['paginae'];
    if ($row['figures']) $text .= "; " . $row['figures'];
    $text .= " (" . substr($row['jahr'], 0, 4) . ")";

    if (defined('TCPDF')) {
        return $text;
    } else {
        return utf8_decode($text);
    }
}
