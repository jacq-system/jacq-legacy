<?php

use Jacq\AnnotationQuery;
use Jacq\DbAccess;
use Jacq\Settings;
use Jacq\StableIdentifier;
use Jacq\TripleID;

session_start();

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Cache-Control: post-check=0, pre-check=0", false);

require_once "inc/functions.php";
require_once 'inc/imageFunctions.php';
require_once __DIR__ . '/vendor/autoload.php';

if (isset($_GET['ID'])) {
    $ID = intval(filter_input(INPUT_GET, 'ID', FILTER_SANITIZE_NUMBER_INT));
} else {
    $ID = 0;
}

$config = Settings::Load();


/********************************
 * functions
 ********************************/

function protolog($row): string
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



function makeCell($text)
{
    if ($text) {
        echo nl2br($text);
    } else {
        echo "&nbsp;";
    }
}

function makeCellWithLink($text)
{
    if ($text) {
        echo "<a href=\"" . $text . '" target="_blank">' . $text . '</a><br/>';
    } else {
        echo "&nbsp;";
    }
}

function makeTypus($ID): string
{
    $dbLnk2 = DbAccess::ConnectTo('OUTPUT');

    $sql = "SELECT typus_lat, tg.genus,
             ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
             ta4.author author4, ta5.author author5,
             te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
             te4.epithet epithet4, te5.epithet epithet5,
             ts.synID, ts.taxonID, ts.statusID, tst.typified_by_Person, tst.typified_Date
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
             AND specimenID=" . intval($ID) . " ORDER by tst.typified_Date DESC";
    $result = $dbLnk2->query($sql);

    $text = "";
    while ($row = $result->fetch_array()) {
        if ($row['synID']) {
            $sql3 = "SELECT ts.statusID, ts.taxonID, tg.genus,
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
                     WHERE taxonID=" . $row['synID'];
            $result3 = $dbLnk2->query($sql3);
            $row3 = $result3->fetch_array();
            $accName = taxonWithHybrids($row3);
        } else {
            $accName = "";
        }

        $sql2 = "SELECT l.suptitel, la.autor, l.periodicalID, lp.periodical, l.vol, l.part, ti.paginae, ti.figures, l.jahr
                 FROM tbl_tax_index ti
                  INNER JOIN tbl_lit l ON l.citationID=ti.citationID
                  LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID=l.periodicalID
                  LEFT JOIN tbl_lit_authors la ON la.autorID=l.editorsID
                 WHERE ti.taxonID='" . $row['taxonID'] . "'";
        $result2 = $dbLnk2->query($sql2);

        $text .= "<tr>"
               .   "<td nowrap align=\"right\">" . $row['typus_lat'] . " of&nbsp;</td>"
               .   "<td><b>" . taxonWithHybrids($row) . "</b></td>"
               . "</tr>";
        while ($row2 = $result2->fetch_array()) {
            $text .= "<tr>"
                   .   "<td></td>"
                   .   "<td><b>" . protolog($row2) . "</b></td>"
                   . "</tr>";
        }
        $text .="<tr>"
               .   "<td nowrap align=\"right\"></td>"
               .   "<td>Typified by:&nbsp;<b>" . $row['typified_by_Person'] . "&nbsp;" . $row['typified_Date'] ."</b></td>"
               . "</tr>";
        if (strlen($accName) > 0) {
            $text .= "<tr>"
                   .   "<td></td>"
                   .   "<td><b>Current Name: <i>$accName</i></b></td>"
                   . "</tr>";
        }
    }
    $text .= "";

    return $text;
}

/**********************************
 * main query
 **********************************/
$dbLnk2 = DbAccess::ConnectTo('OUTPUT');

// check, if specimen is accessible
$access = $dbLnk2->query("SELECT `accessible` FROM tbl_specimens WHERE specimen_ID = '$ID'")->fetch_assoc();
if (!empty($access) && $access['accessible'] == 0) {
    include 'templates/detail_error_no_access.php';
    die();
}


$specimen = $dbLnk2->query("SELECT s.specimen_ID, tg.genus, c.Sammler, c.SammlerID, c.HUH_ID, c.VIAF_ID, c.WIKIDATA_ID,c.ORCID, c2.Sammler_2, ss.series, s.series_number,
                             s.Nummer, s.alt_number, s.Datum, s.Fundort, s.det, s.taxon_alt, s.Bemerkungen, s.typified, s.typusID,
                             s.digital_image, s.digital_image_obs, s.HerbNummer, s.CollNummer, s.ncbi_accession, s.observation,
                             s.Coord_W, s.W_Min, s.W_Sec, s.Coord_N, s.N_Min, s.N_Sec,
                             s.Coord_S, s.S_Min, s.S_Sec, s.Coord_E, s.E_Min, s.E_Sec, s.habitat, s.habitus, s.altitude_min, s.altitude_max,
                             n.nation_engl, p.provinz, s.Fundort, tf.family, tsc.cat_description, s.taxonID taxid,
                             mc.collection, mc.collectionID, mc.source_id, mc.coll_short, mc.coll_gbif_pilot,
                             m.source_code, m.source_name,
                             tid.imgserver_type, tid.imgserver_IP, tid.iiif_capable, tid.iiif_url, tid.HerbNummerNrDigits,
                             ta.author, ta1.author author1, ta2.author author2, ta3.author author3, ta4.author author4, ta5.author author5,
                             te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3, te4.epithet epithet4, te5.epithet epithet5,
                             ts.synID, ts.taxonID, ts.statusID
                            FROM tbl_specimens s
                             LEFT JOIN tbl_specimens_series ss           ON ss.seriesID = s.seriesID
                             LEFT JOIN tbl_management_collections mc     ON mc.collectionID = s.collectionID
                             LEFT JOIN meta m                            ON m.source_id = mc.source_id
                             LEFT JOIN tbl_img_definition tid            ON tid.source_id_fk = mc.source_id
                             LEFT JOIN tbl_geo_nation n                  ON n.NationID = s.NationID
                             LEFT JOIN tbl_geo_province p                ON p.provinceID = s.provinceID
                             LEFT JOIN tbl_collector c                   ON c.SammlerID = s.SammlerID
                             LEFT JOIN tbl_collector_2 c2                ON c2.Sammler_2ID = s.Sammler_2ID
                             LEFT JOIN tbl_tax_species ts                ON ts.taxonID = s.taxonID
                             LEFT JOIN tbl_tax_authors ta                ON ta.authorID = ts.authorID
                             LEFT JOIN tbl_tax_authors ta1               ON ta1.authorID = ts.subspecies_authorID
                             LEFT JOIN tbl_tax_authors ta2               ON ta2.authorID = ts.variety_authorID
                             LEFT JOIN tbl_tax_authors ta3               ON ta3.authorID = ts.subvariety_authorID
                             LEFT JOIN tbl_tax_authors ta4               ON ta4.authorID = ts.forma_authorID
                             LEFT JOIN tbl_tax_authors ta5               ON ta5.authorID = ts.subforma_authorID
                             LEFT JOIN tbl_tax_epithets te               ON te.epithetID = ts.speciesID
                             LEFT JOIN tbl_tax_epithets te1              ON te1.epithetID = ts.subspeciesID
                             LEFT JOIN tbl_tax_epithets te2              ON te2.epithetID = ts.varietyID
                             LEFT JOIN tbl_tax_epithets te3              ON te3.epithetID = ts.subvarietyID
                             LEFT JOIN tbl_tax_epithets te4              ON te4.epithetID = ts.formaID
                             LEFT JOIN tbl_tax_epithets te5              ON te5.epithetID = ts.subformaID
                             LEFT JOIN tbl_tax_genera tg                 ON tg.genID = ts.genID
                             LEFT JOIN tbl_tax_families tf               ON tf.familyID = tg.familyID
                             LEFT JOIN tbl_tax_systematic_categories tsc ON tf.categoryID = tsc.categoryID
                            WHERE s.accessible != '0'
                             AND s.specimen_ID = '$ID'")
                   ->fetch_array();

if (empty($specimen)) {
    include 'templates/detail_empty.php';
    die();

//    $specimen = array(
//        'specimen_ID'    => 0,
//        'source_id'      => 0,
//        'HerbNummer'     => '',
//        'source_code'    => '',
//        'collection'     => '',
//        'CollNummer'     => '',
//        'statusID'       => 0,
//        'taxid'          => 0,
//        'ncbi_accession' => '',
//        'genus'          => '',
//        'epithet'        => '',
//        'family'         => '',
//        'det'            => '',
//        'taxon_alt'      => '',
//        'Datum'          => '',
//        'Fundort'        => '',
//        'habitat'        => '',
//        'habitus'        => ''
//    );
}

$output['ID'] = $ID;

$output['stblids'] = StableIdentifier::make($specimen['specimen_ID'], $specimen['source_id'], $specimen['HerbNummer'])->getAllStblIDs();

$output['HerbariumNr'] = HerbariumNr($specimen);

$output['collectionNr'] = trim($specimen['collection'] . " " . $specimen['CollNummer']);

$output['taxon'] = taxonWithHybrids($specimen);

$output['taxonAuth'] = getTaxonAuth($specimen['taxid']);

$output['typusText'] = makeTypus($ID);

//$sammler = collection($specimen);
$output['sammler'] = rdfcollection($specimen, true);

if ($specimen['ncbi_accession']) {
    $output['sammler'] .= " &mdash; " . $specimen['ncbi_accession']
        . " <a href='http://www.ncbi.nlm.nih.gov/entrez/query.fcgi?db=Nucleotide&cmd=search&term=" . $specimen['ncbi_accession'] . "' target='_blank'>"
        . "<img border='0' height='16' src='images/ncbi.gif' width='14'></a>";
}

$output['location'] = $specimen['nation_engl'] ?? '';
if (strlen(trim($specimen['provinz'] ?? '')) > 0) {
    $output['location'] .= " / " . trim($specimen['provinz']);
}
if (($specimen['Coord_S'] ?? 0) > 0 || ($specimen['S_Min'] ?? 0) > 0 || ($specimen['S_Sec'] ?? 0) > 0) {
    $lat = -(intval($specimen['Coord_S']) + intval($specimen['S_Min']) / 60.0 + doubleval($specimen['S_Sec']) / 3600.0);
} else if (($specimen['Coord_N'] ?? 0) > 0 || ($specimen['N_Min'] ?? 0) > 0 || ($specimen['N_Sec'] ?? 0) > 0) {
    $lat = intval($specimen['Coord_N']) + intval($specimen['N_Min']) / 60.0 + doubleval($specimen['N_Sec']) / 3600.0;
} else {
    $lat = 0;
}
if (($specimen['Coord_W'] ?? 0) > 0 || ($specimen['W_Min'] ?? 0) > 0 || ($specimen['W_Sec'] ?? 0) > 0) {
    $lon = -(intval($specimen['Coord_W']) + intval($specimen['W_Min']) / 60.0 + doubleval($specimen['W_Sec']) / 3600.0);
} else if (($specimen['Coord_E'] ?? 0) > 0 || ($specimen['E_Min'] ?? 0) > 0 || ($specimen['E_Sec'] ?? 0) > 0) {
    $lon = intval($specimen['Coord_E']) + intval($specimen['E_Min']) / 60.0 + doubleval($specimen['E_Sec']) / 3600.0;
} else {
    $lon = 0;
}
if ($lat != 0 || $lon != 0) {
    $output['location'] .= " &mdash; " . round($lat, 5) . "&deg; / " . round($lon, 5) . "&deg; ";

    $point['lat'] = dms2sec($specimen['Coord_S'], $specimen['S_Min'], $specimen['S_Sec'], $specimen['Coord_N'], $specimen['N_Min'], $specimen['N_Sec']) / 3600.0;
    $point['lng'] = dms2sec($specimen['Coord_W'], $specimen['W_Min'], $specimen['W_Sec'], $specimen['Coord_E'], $specimen['E_Min'], $specimen['E_Sec']) / 3600.0;
    $url = "https://www.jacq.org/detail.php?ID=" . $specimen['specimen_ID'];
    $txt = "<div style=\"font-family: Arial,sans-serif; font-weight: bold; font-size: medium;\">"
        . htmlspecialchars(taxonWithHybrids($specimen))
        . "</div>"
        . "<div style=\"font-family: Arial,sans-serif; font-size: small;\">"
        . htmlentities(collection($specimen), ENT_QUOTES | ENT_HTML401) . " / "
        . $specimen['Datum'] . " / ";
    if ($specimen['typusID']) {
        $txt .= htmlspecialchars($specimen['typusID']) . " / ";
    }
    $txt .= htmlspecialchars(collectionItem($specimen['collection'])) . " " . htmlspecialchars($specimen['HerbNummer']) . "</div>";
    $txt = strtr($txt, array("\r" => '', "\n" => ''));
    $point['txt'] = "<a href=\"$url\" target=\"_blank\">$txt</a>";
}

if ($specimen['source_id'] == '35') {
    $output['annotations'] = (preg_replace("#<a .*a>#", "", $specimen['Bemerkungen']));
} else {
    $output['annotations'] = $specimen['Bemerkungen'] ?? '';
}

if (($specimen['source_id'] == '29' || $specimen['source_id'] == '6') && $config->get('ANNOSYS', 'ACTIVE')) {
    $output['newAnno'] = true;
    // create new id object
    $id = new TripleID($specimen['source_code'], $specimen['source_name'], $specimen['HerbNummer']);
    // create new AnnotationQuery object
    $query = new AnnotationQuery("https://annosys.bgbm.fu-berlin.de/AnnoSys");
    // fetch annotation metadata
    $annotations = $query->getAnnotationMetadata($id);
    // build URI for new annotation
    if ($specimen['source_id'] == '29') {
        $output['newAnnoUri'] = $query->newAnnotationUri("http://ww3.bgbm.org/biocase/pywrapper.cgi?dsa=Herbar&", $id);
    } else { //$specimen['source_id'] == '6'
        $output['newAnnoUri'] = $query->newAnnotationUri("http://131.130.131.9/biocase/pywrapper.cgi?dsa=gbif_w&", $id);
    }
    $output['newAnnoTable'] = generateAnnoTable($annotations);
} else {
    $output['newAnno'] = false;
}

if (!empty($specimen['digital_image']) || !empty($specimen['digital_image_obs'])) {
    $phaidra = false;
    if ($specimen['source_id'] == '1') {
        // for now, special treatment for phaidra is needed when wu has images
        $output['phaidraUrl'] = "";

        // ask phaidra server if it has the desired picture. If not, use old method
        $picname = sprintf("WU%0" . $specimen['HerbNummerNrDigits'] . ".0f", str_replace('-', '', $specimen['HerbNummer']));
        $ch = curl_init("https://app05a.phaidra.org/viewer/" . $picname);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $curl_response = curl_exec($ch);
        if ($curl_response) {
            $info = curl_getinfo($ch);
            if ($info['http_code'] == 200) {
                $phaidra = true;
                $phaidraManifest = $config->get('JACQ_SERVICES') . 'iiif/manifest/' . $specimen['specimen_ID'];
            }
        }
        curl_close($ch);
    }
    if ($phaidra) {  // phaidra picture found, use iiif
        $output['manifest'] = $phaidraManifest;
        $output['picture_include'] = 'templates/detail_inc_iiif.php';
    } elseif ($specimen['iiif_capable']) {
        $ch = curl_init($config->get('JACQ_SERVICES') . "iiif/manifestUri/" . $specimen['specimen_ID']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $curl_response = curl_exec($ch);
        if ($curl_response !== false) {
            $curl_result = json_decode($curl_response, true);
            $output['manifest'] = $curl_result['uri'];
        } else {
            $output['manifest'] = "";
        }
        curl_close($ch);
        $output['picture_include'] = 'templates/detail_inc_iiif.php';
    } elseif ($specimen['imgserver_type'] == 'bgbm') {  // but not iiif_capable
        $output['bgbm_options'] = '?filename=' . rawurlencode(basename($specimen['specimen_ID'])) . '&sid=' . $specimen['specimen_ID'];
        $output['picture_include'] = 'templates/detail_inc_bgbm.php';
    //    'baku' is depricated and no loner used
    //    } elseif ($specimen['imgserver_type'] == 'baku') {
    //        $options = 'filename=' . rawurlencode(basename($specimen['specimen_ID'])) . '&sid=' . $specimen['specimen_ID'];
    //        echo "<td valign='top' align='center'>"
    //           . "<a href='image.php?{$options}&method=show' target='imgBrowser'><img src='image.php?{$options}&method=thumb border='2'></a><br>"
    //           . "(<a href='image.php?{$options}&method=show' target='imgBrowser'>Open viewer</a>)"
    //           . "</td>";
    } elseif ($specimen['imgserver_type'] == 'djatoka') {   // but not iiif_capable, so the original one
        $picdetails = getPicDetails($specimen['specimen_ID']);
        $transfer = getPicInfo($picdetails);
        $output['djatoka_options'] = array();
        if ($transfer) {
            if (!empty($transfer['error'])) {
                $output['djatoka']['error'] = "Picture server list error. Falling back to original image name";
                $output['djatoka_options'][] = 'filename=' . rawurlencode(basename($picdetails['filename'])) . '&sid=' . $specimen['specimen_ID'];
                error_log($transfer['error']);
            } else {
                if (count($transfer['pics'] ?? array()) > 0) {
                    foreach ($transfer['pics'] as $v) {
                        $output['djatoka_options'][] = 'filename=' . rawurlencode(basename($v)) . '&sid=' . $specimen['specimen_ID'];
                    }
                    $output['djatoka']['error'] = "";
                } else {
                    $output['djatoka']['error'] = "no pictures found";
                }
                if (trim($transfer['output'])) {
                    $output['djatoka_transfer_output'] = "\n" . $transfer['output'] . "\n";
                }
            }
        } else {
            $output['djatoka']['error'] = "transmission error";
        }
        $output['picture_include'] = 'templates/detail_inc_djatoka.php';
    } else {
        $output['picture_include'] = 'templates/detail_inc_noPictures.php';
    }
} else {
    $output['picture_include'] = '';
}

include 'templates/detail_base.php';
