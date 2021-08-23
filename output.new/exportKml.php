<?php
session_start();
require("inc/functions.php");

function replaceNewline($text)
{
    return strtr(str_replace("\r\n", "\n", $text), "\r\n", "  ");  //replaces \r\n with \n and then \r or \n with <space>
}


function formatCell($value)
{
    if(!isset($value) || $value == "") {
        $value = "<td></td>";
    } else {
        //$value = "<td>".str_replace('"', '""', $value); // escape quotes
        $value = '<td>'.$value."</td>";
    }

    return $value;
}


function addLine($value)
{
    return htmlspecialchars($value, ENT_NOQUOTES) . "<br>\n";
}


function protolog($row)
{
    $text = '';
    if ($row['suptitel']) {
        $text .= "in " . $row['autor'] . ": " . $row['suptitel'] . " ";
    }
    if ($row['periodicalID']) {
        $text .= $row['periodical'];
    }
    $text .= " " . $row['vol'];
    if ($row['part']) {
        $text .= " (" . $row['part'] . ")";
    }
    $text .= ": " . $row['paginae'];
    if ($row['figures']) {
        $text .= "; " . $row['figures'];
    }
    $text .= " (" . $row['jahr'] . ")";

    return $text;
}


function makeTypus($ID)
{
    global $dbLink;

    $sql = "SELECT typus_lat, tg.genus,
             ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
             ta4.author author4, ta5.author author5,
             te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
             te4.epithet epithet4, te5.epithet epithet5,
             ts.synID, ts.taxonID, ts.statusID
            FROM (tbl_specimens_types tst, tbl_typi tt, tbl_tax_species ts)
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
             LEFT JOIN tbl_tax_genera tg ON tg.genID=ts.genID
            WHERE tst.typusID=tt.typusID
             AND tst.taxonID=ts.taxonID
             AND specimenID='" . intval($ID) . "'";
    $result = $dbLink->query($sql);

    $text = "";
    while ($row = $result->fetch_array()) {
        if ($row['synID']) {
            $sql3 = "SELECT ts.statusID, tg.genus,
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
                      LEFT JOIN tbl_tax_genera tg ON tg.genID=ts.genID
                     WHERE taxonID=".$row['synID'];
            $result3 = $dbLink->query($sql3);
            $row3 = $result3->fetch_array();
            $accName = taxonWithHybrids($row3);
        } else {
            $accName = "";
        }

        $sql2 = "SELECT l.suptitel, la.autor, l.periodicalID, lp.periodical, l.vol, l.part,
                  ti.paginae, ti.figures, l.jahr
                 FROM tbl_tax_index ti
                  INNER JOIN tbl_lit l ON l.citationID=ti.citationID
                  LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID=l.periodicalID
                  LEFT JOIN tbl_lit_authors la ON la.autorID=l.editorsID
                 WHERE ti.taxonID='".$row['taxonID']."'";
        $result2 = $dbLink->query($sql2);

        $text .= $row['typus_lat']." for ".taxonWithHybrids($row)." ";
        while ($row2 = $result2->fetch_array()) {
            $text .= protolog($row2)." ";
        }
        if (strlen($accName)>0) {
            $text .= "Current Name: $accName ";
        }
    }

    return $text;
}

//---------- main ----------

$sql = $_SESSION['s_query'] . "ORDER BY genus, epithet, author";

$result = $dbLink->query($sql);
if (!$result) {
    echo $sql . "<br>\n";
    echo $dbLink->error . "<br>\n";
}

$kmlData = '';
while ($row = $result->fetch_array()) {
    $sql = "SELECT s.specimen_ID, tg.genus, c.Sammler, c2.Sammler_2, ss.series, s.series_number,
             s.Nummer, s.alt_number, s.Datum, s.Fundort, s.det, s.taxon_alt, s.Bemerkungen,
             n.nation_engl, p.provinz, s.Fundort, tf.family, tsc.cat_description,
             mc.collection, mc.collectionID, mc.coll_short, s.typified,
             s.digital_image, s.digital_image_obs, s.HerbNummer, s.ncbi_accession,
             s.Coord_W, s.W_Min, s.W_Sec, s.Coord_N, s.N_Min, s.N_Sec,
             s.Coord_S, s.S_Min, s.S_Sec, s.Coord_E, s.E_Min, s.E_Sec,
             ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
             ta4.author author4, ta5.author author5,
             te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
             te4.epithet epithet4, te5.epithet epithet5,
             ts.synID, ts.taxonID, ts.statusID
            FROM tbl_specimens s
             LEFT JOIN tbl_specimens_series ss ON ss.seriesID=s.seriesID
             LEFT JOIN tbl_management_collections mc ON mc.collectionID=s.collectionID
             LEFT JOIN tbl_geo_nation n ON n.NationID=s.NationID
             LEFT JOIN tbl_geo_province p ON p.provinceID=s.provinceID
             LEFT JOIN tbl_collector c ON c.SammlerID=s.SammlerID
             LEFT JOIN tbl_collector_2 c2 ON c2.Sammler_2ID=s.Sammler_2ID
             LEFT JOIN tbl_tax_species ts ON ts.taxonID=s.taxonID
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
             LEFT JOIN tbl_tax_genera tg ON tg.genID=ts.genID
             LEFT JOIN tbl_tax_families tf ON tf.familyID=tg.familyID
             LEFT JOIN tbl_tax_systematic_categories tsc ON tf.categoryID=tsc.categoryID
            WHERE specimen_ID='" . intval($row['specimen_ID']) . "'";
    $resultSpecimen = $dbLink->query($sql);
    if (!$resultSpecimen) {
        echo $sql."<br>\n";
        echo $dbLink->error . "<br>\n";
    }
    $rowSpecimen = $resultSpecimen->fetch_array();

    $sammler = collection($rowSpecimen);

    $location = $rowSpecimen['nation_engl'];
    if (strlen(trim($rowSpecimen['provinz']))>0) {
        $location .= " / " . trim($rowSpecimen['provinz']);
    }
    if ($rowSpecimen['Coord_S'] > 0 || $rowSpecimen['S_Min'] > 0 || $rowSpecimen['S_Sec'] > 0) {
        $lat = -($rowSpecimen['Coord_S'] + $rowSpecimen['S_Min'] / 60 + $rowSpecimen['S_Sec'] / 3600);
    } else if ($rowSpecimen['Coord_N'] > 0 || $rowSpecimen['N_Min'] > 0 || $rowSpecimen['N_Sec'] > 0) {
        $lat = $rowSpecimen['Coord_N'] + $rowSpecimen['N_Min'] / 60 + $rowSpecimen['N_Sec'] / 3600;
    } else {
        $lat = 0;
    }
    if ($rowSpecimen['Coord_W'] > 0 || $rowSpecimen['W_Min'] > 0 || $rowSpecimen['W_Sec'] > 0) {
        $lon = -($rowSpecimen['Coord_W'] + $rowSpecimen['W_Min'] / 60 + $rowSpecimen['W_Sec'] / 3600);
    } else if ($rowSpecimen['Coord_E'] > 0 || $rowSpecimen['E_Min'] > 0 || $rowSpecimen['E_Sec'] > 0) {
        $lon = $rowSpecimen['Coord_E'] + $rowSpecimen['E_Min'] / 60 + $rowSpecimen['E_Sec'] / 3600;
    } else {
        $lon = 0;
    }
    if ($lat!=0 || $lon!=0) {
        $location .= " / " . round($lat, 2) . "° / " . round($lon, 2) . "°";
    }

  if ($lat || $lon) {
    $kmlData .= "<Placemark>\n"
              . "  <name>" . htmlspecialchars(taxonWithHybrids($rowSpecimen), ENT_NOQUOTES) . "</name>\n"
              . "  <description>\n"
              . "    <![CDATA[\n"
              . "      " . addLine($rowSpecimen['collection'] . " " . $rowSpecimen['HerbNummer'] . " [dbID " . $rowSpecimen['specimen_ID'] . "]")
              . "      " . addLine($sammler)
              . "      " . addLine($rowSpecimen['Datum'])
              . "      " . addLine($location)
              . "      " . addLine($rowSpecimen['Fundort'])
              . "      " . addLine(getStableIdentifier($rowSpecimen['specimen_ID']))
              . "      <a href=\"http://herbarium.univie.ac.at/database/detail.php?ID=" . $row['specimen_ID'] . "\">link</a>\n"
              . "    ]]>\n"
              . "  </description>\n"
              . "  <Point>\n"
              . "    <coordinates>$lon,$lat</coordinates>\n"
              . "  </Point>\n"
              . "</Placemark>\n";
  }

  //$csvData .= "<tr>".
  //            formatCell($rowSpecimen['specimen_ID']).
  //            formatCell($rowSpecimen['collection']." ".$rowSpecimen['herbNummer']).
  //            formatCell(makeTypus(intval($rowSpecimen['specimen_ID']))).
  //            formatCell($rowSpecimen['typified']).
  //            formatCell(taxonWithHybrids($rowSpecimen)).
  //            formatCell($rowSpecimen['family']).
  //            formatCell($sammler).
  //            formatCell("'".$rowSpecimen['Datum']).
  //            formatCell($location).
  //            formatCell($rowSpecimen['Fundort']).
  //            formatCell($rowSpecimen['det']).
  //            formatCell($rowSpecimen['taxon_alt']).
  //            formatCell($rowSpecimen['Bemerkungen']).
  //            "</tr>";
}

$kmlData = str_replace("\r", "", $kmlData); // embedded returns have "\r"

//if ($kmlData == "") $kmlData = "no matching records found\n";

header("Content-type: application/vnd.google-earth.kml+xml charset=utf-8");
header("Content-Disposition: attachment; filename=results.kml");
header("Pragma: no-cache");
header("Expires: 0");
//echo chr(0xef).chr(0xbb).chr(0xbf)."<table>".$csvHeader.$csvData."</table>";
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
   . "<kml xmlns=\"http://www.opengis.net/kml/2.2\">\n"
   . "<Document>\n"
   . "<description>search results Virtual Herbaria</description>\n"
   . $kmlData
   . "</Document>\n"
   . "</kml>\n";