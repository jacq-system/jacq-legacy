<?php
ini_set( 'error_reporting', 'E_NONE' );
ini_set( 'display_errors', Off );

session_start();
require_once("inc/functions.php");
require_once('inc/imageFunctions.php');

function protolog($row)
{
    $text = "";
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

    $text = "";

    $sql = "SELECT typus_lat, ts.statusID, tg.genus,
             ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
             ta4.author author4, ta5.author author5,
             te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
             te4.epithet epithet4, te5.epithet epithet5,
             ts.synID, ts.taxonID
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

        $text = $row['typus_lat']." for " . taxonWithHybrids($row) . ": ";
        $protologs = array();
        while ($row2 = $result2->fetch_array()) {
            $protologs[] = protolog($row2);
        }

        $text .= join( ' / ', $protologs );

        if (strlen($accName) > 0) {
            $text .= " (Current Name: $accName)";
        }
    }

    return $text;
}


// Open the ID-List and Imagename-List files
$idfp = fopen( 'kulturpool_idlist_' . date( 'Ymd_His' ) . '.txt', 'w' );
$infp = fopen( 'kulturpool_imagelist_' . date( 'Ymd_His' ) . '.txt', 'w' );

Header( 'Content-Type: text/xml' );

// Startup the XML-Writer
$out = new XMLWriter();
//$out->openURI('php://output');
$out->openURI('kulturpool_xml_' . date( 'Ymd_His' ) . '.xml');
$out->setIndent( true );
$out->startDocument( "1.0", "UTF-8" );
$out->startElement( 'kulturpool' );

//Fetch all IDs (with pictures) and start to output the XML-Data
$query = "SELECT specimen_ID FROM tbl_specimens WHERE digital_image = 1 AND ( collectionID IN ( 96, 91, 31, 37, 106, 107, 34, 35, 28, 29, 38, 36, 20, 21 ) OR ( collectionID = 19 AND SammlerID IN ( 7079, 9209, 10745, 6066, 8060, 8311, 11364, 916, 10397, 11089, 10183, 1116, 9603, 8116, 9812, 9002, 9003, 9004, 6119, 17120 ) ) ) ORDER BY specimen_ID LIMIT 100";
//$query = "SELECT specimen_ID FROM tbl_specimens WHERE digital_image = 1 AND ( collectionID IN ( 96, 91, 31, 37, 106, 107, 34, 35, 38, 36 ) ) ORDER BY specimen_ID";
$data_result = $dbLink->query($query);
$num_rows = $data_result->num_rows;
$curr_row = 0;

while( $data_row = $data_result->fetch_array() ) {
    $curr_row++;

    //if( $curr_row % 100 == 0 ) echo "Progress: " . round( 100 / $num_rows * $curr_row, 2 ) . "%\n";
    //echo "\rProgress: " . round( 100 / $num_rows * $curr_row, 2 ) . "% ( $curr_row / $num_rows ) ";
    printf( "\rProgress: %6.2f %% ( %d / %d )", 100 / $num_rows * $curr_row, $curr_row, $num_rows );

    $ID = $data_row['specimen_ID'];

    $query = "SELECT s.specimen_ID, tg.genus, c.Sammler, c2.Sammler_2, ss.series, s.series_number,
               s.Nummer, s.alt_number, s.Datum, s.Datum2, s.Fundort, s.det, s.taxon_alt, s.Bemerkungen,
               n.nation_engl, p.provinz, s.Fundort, tf.family, tsc.cat_description,
               mc.collection, mc.collectionID, mc.coll_short, mc.coll_descr, tid.imgserver_IP, s.typified,
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
               LEFT JOIN tbl_img_definition tid ON tid.source_id_fk=mc.source_id
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
              WHERE specimen_ID='".intval($ID)."'";
    $result = $dbLink->query($query);

    $row = $result->fetch_array();

    $taxon = taxonWithHybrids($row);

    $sammler = collection($row);

    $typusText = makeTypus($ID);

    $URI = '';
    if ($row['digital_image'] || $row['digital_image_obs']) {


        $picdetails = getPicDetails($row['specimen_ID']);
        $transfer   = getPicInfo($picdetails);

        if( count($transfer['pics']) > 0 ) {
            // Correct the name if necessary
            $fileName = basename($transfer['pics'][0]);
            if( preg_match( '/(w_\d+)(_\D|-\d)\.tif/', $fileName, $treffer ) ) {
                echo "Warning: Correcting " . $fileName . " to ";
                $fileName = $treffer[1] . '.tif';
                echo $fileName . "\n";
            }

            $URI='http'.(isset($_SERVER['HTTPS'])?'s':'').'://'.$_SERVER['SERVER_NAME'].'/'.dirname($_SERVER['PHP_SELF']).'/image.php?filename='.$row['specimen_ID'].'&method=resized';
            $inventarnummer = basename( $fileName, '.tif' );

            //Write Image-Filename into file
            fwrite( $infp, $fileName . "\n" );
        } else {
            echo "Warning: " . $row['specimen_ID'] . " has no Image! ... Skipping\n";
            continue;
        }
    }

    //Write ID into file
    fwrite( $idfp, $ID . "\n" );

    //Start the ITEM
    $out->startElement( 'item' );

    // Start with the XML Data
    // Title
    $out->writeElement( 'titel', $taxon );
    // Beschreibung
    $out->writeElement( 'beschreibung', $typusText );
    // Beitragender
    $out->writeElement( 'beitragender', $sammler );
    // Datierung
    $out->writeElement( 'datierung', $row['Datum'] . ( !empty($row['Datum_2']) ? ' / ' . $row['Datum_2'] : '' ) );
    // URI
    //$out->writeElement( 'URI1', $URI );// => htmlentities, makes & to &amp;
    $out->writeRaw ("  <URI>{$URI}</URI>\n");
    // WebsiteLink
    $out->writeElement( 'websiteLink', 'http://herbarium.univie.ac.at/database/detail.php?ID=' . $row['specimen_ID'] );

    // Objektklasse
    $out->writeElement( 'objektklasse', 'Herbarium Beleg' );
    // Objekttyp
    $out->writeElement( 'objekttyp', 'image' );
    // Ort
    $out->writeElement( 'ort', 'NHM Wien - Botanische Abteilung' );
    // Inventarnummer
    $out->writeElement( 'inventarnummer', $inventarnummer ?? '' );
    // Sammlung
    if( empty($row['coll_descr'] ) ) {
        echo "ERROR: Empty Description!";
    }
    $out->writeElement( 'sammlung', $row['collection'] . " - " . $row['coll_descr'] );

    // Fundort
    $text = $row['nation_engl'];
    if (strlen(trim($row['provinz']))>0) {
        $text .= " / ".trim($row['provinz']);
    }
    if( strlen( $text ) > 0 ) {
        $text .= " - ";
    }
    $text .= trim($row['Fundort']);
    $out->writeElement( 'fundort', $text );

    // Kontributor
    $out->writeElement( 'kontributor', 'Naturhistorisches Museum - Wien' );
    // Institutsart
    $out->writeElement( 'institutsart', 'Museum' );
    // technischer Bereitsteller
    $out->writeElement( 'technischer_bereitsteller', 'Wolfgang Koller' );
    // technischer Bereitsteller
    $out->writeElement( 'kuratorkontakt', 'Ernst Vitek' );

    // Rechteinhaber
    $out->writeElement( 'rechteinhaber', 'Naturhistorisches Museum - Wien' );

    // Unterrichtsfach
    $out->writeElement( 'unterrichtsfach', 'Biologie / Botanik' );

    $out->endElement();

// end of while loop
}


$out->endElement();
$out->endDocument();
