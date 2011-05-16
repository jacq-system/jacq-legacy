<?php
require_once( 'variables.php' );

function db_connect( $dbConfig, $dbAccess = "readonly" ) {
    $host = $dbConfig["host"];
    $db = $dbConfig["db"];
    $user = $dbConfig["user"];
    $pass = $dbConfig["pass"];

    if (!@mysql_connect($host,$user,$pass) || !@mysql_select_db($db)) {
      echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">\n".
           "<html>\n".
           "<head><titel>Sorry, no connection ...</title></head>\n".
           "<body><p>Sorry, no connection to database ...</p></body>\n".
           "</html>\n";
      exit();
    }
    //mysql_query("SET character_set_results='utf8'");
    mysql_query("SET character set utf8");
}

// Connect to output DB by default
db_connect( $_CONFIG['DATABASES']['OUTPUT'] );

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
        if ($alt_number && $alt_number != "s.n.") $text .= " " . $alt_number;
        if ($series) $text .= " " . $series;
        $text .= " " . $series_number;
    } else {
        if ($series) $text .= " " . $series;
        if ($Nummer) $text .= " " . $Nummer;
        if ($alt_number) $text .= " " . $alt_number;
        if (strstr($alt_number, "s.n.")) $text .= " [" . $Datum . "]";
    }

    return $text;
}

function taxon ($row)
{
    $text = $row['genus'];
    if ($row['epithet'])  $text .= " "          . $row['epithet']  . " " . $row['author'];
    if ($row['epithet1']) $text .= " subsp. "   . $row['epithet1'] . " " . $row['author1'];
    if ($row['epithet2']) $text .= " var. "     . $row['epithet2'] . " " . $row['author2'];
    if ($row['epithet3']) $text .= " subvar. "  . $row['epithet3'] . " " . $row['author3'];
    if ($row['epithet4']) $text .= " forma "    . $row['epithet4'] . " " . $row['author4'];
    if ($row['epithet5']) $text .= " subforma " . $row['epithet5'] . " " . $row['author5'];

    return $text;
}

function taxonWithHybrids ($row)
{
    if ($row['statusID']==1 && strlen($row['epithet'])==0 && strlen($row['author'])==0) {
        $rowHybrid = mysql_fetch_array(mysql_query("SELECT parent_1_ID, parent_2_ID
                                                    FROM tbl_tax_hybrids
                                                    WHERE taxon_ID_fk = '" . $row['taxonID'] . "'"));
        $row1 = mysql_fetch_array(mysql_query("SELECT tg.genus,
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
                                               WHERE taxonID = '" . $rowHybrid['parent_1_ID'] . "'"));
        $row2 = mysql_fetch_array(mysql_query("SELECT tg.genus,
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
                                               WHERE taxonID = '" . $rowHybrid['parent_2_ID'] . "'"));

        return taxon($row1) . " x " . taxon($row2);
    } else {
        return taxon($row);
    }
}