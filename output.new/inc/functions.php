<?php
require_once('variables.php');
require_once('AnnotationQuery.inc.php');
require_once('ImagePreview.inc.php');
require_once('StableIdentifier.php');

// Connect to output DB by default

/** @var mysqli $dbLink */
$dbLink = new mysqli($_CONFIG['DATABASES']['OUTPUT']['host'], $_CONFIG['DATABASES']['OUTPUT']['readonly']['user'], $_CONFIG['DATABASES']['OUTPUT']['readonly']['pass'], $_CONFIG['DATABASES']['OUTPUT']['db']);
if ($dbLink->connect_errno) {
    echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">\n" .
    "<html>\n" .
    "<head><titel>Sorry, no connection ...</title></head>\n" .
    "<body><p>Sorry, no connection to database ...</p></body>\n" .
    "</html>\n";
    exit();
}
$dbLink->set_charset('utf8');

/** @var mysqli $dbLink2 */
$dbLink_pictures = new mysqli($_CONFIG['DATABASES']['PICTURES']['host'], $_CONFIG['DATABASES']['PICTURES']['readonly']['user'], $_CONFIG['DATABASES']['PICTURES']['readonly']['pass'], $_CONFIG['DATABASES']['PICTURES']['db']);
if ($dbLink_pictures->connect_errno) {
    echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">\n" .
    "<html>\n" .
    "<head><titel>Sorry, no connection ...</title></head>\n" .
    "<body><p>Sorry, no connection to database ...</p></body>\n" .
    "</html>\n";
    exit();
}
$dbLink_pictures->set_charset('utf8');

function collection($row)
{
    $text = $row['Sammler'];
    if (strstr($row['Sammler_2'], "&") || strstr($row['Sammler_2'], "et al.")) {
        $text .= " et al.";
    } else if ($row['Sammler_2']) {
        $text .= " & " . $row['Sammler_2'];
    }

    if ($row['series_number']) {
        if ($row['Nummer']) {
            $text .= " " . $row['Nummer'];
        }
        if ($row['alt_number'] && $row['alt_number'] != "s.n.") {
            $text .= " " . $row['alt_number'];
        }
        if ($row['series']) {
            $text .= " " . $row['series'];
        }
        $text .= " " . $row['series_number'];
    } else {
        if ($row['series']) {
            $text .= " " . $row['series'];
        }
        if ($row['Nummer']) {
            $text .= " " . $row['Nummer'];
        }
        if ($row['alt_number']) {
            $text .= " " . $row['alt_number'];
        }
//        if (strstr($row['alt_number'], "s.n.")) {
//            $text .= " [" . $row['Datum'] . "]";
//        }
    }

    return trim($text);
}

function rdfcollection($row, $isBotanyPilot = false)
{
    if ($row['WIKIDATA_ID'] || $row['HUH_ID'] || $row['VIAF_ID'] || $row['ORCID']){
        $text = "";
        if ($row['WIKIDATA_ID']) {
           $text .= "<a href=\"" . $row['WIKIDATA_ID'] . '" title="wikidata" alt="wikidata" target="_blank" class="leftnavi"><img src="assets/images/wikidata.png" width="20px"></a>&nbsp;';
        }
        if ($row['HUH_ID']) {
           $text .= "<a href=\"" . $row['HUH_ID'] . '" title="Index of Botanists (HUH)" alt="Index of Botanists (HUH)" target="_blank" class="leftnavi"><img src="assets/images/huh.png" height="20px"></a>&nbsp;';
        }
        if ($row['VIAF_ID']) {
           $text .= "<a href=\"" . $row['VIAF_ID'] . '" title="VIAF" alt="VIAF" target="_blank" class="leftnavi"><img src="assets/images/viaf.png" width="20px"></a>&nbsp;';
        }
        if ($row['ORCID']) {
           $text .= "<a href=\"" . $row['ORCID'] . '" title="ORCID" alt="ORCID" target="_blank" class="leftnavi"><img src="assets/images/orcid.logo.icon.svg" width="20px"></a>&nbsp;';
        }

        if (getBloodhoundID($row)) {
        $text .= getBloodhoundID($row);
        }
        $text .=  $row['Sammler'];
    } else {
        $text =  $row['Sammler'];
    }

    if (strstr($row['Sammler_2'], "&") || strstr($row['Sammler_2'], "et al.")) {
        $text .= " et al.";
    } else if ($row['Sammler_2']) {
        $text .= " & " . $row['Sammler_2'];
    }

    if ($row['series_number']) {
        if ($row['Nummer']) {
            $text .= " " . $row['Nummer'];
        }
        if ($row['alt_number'] && $row['alt_number'] != "s.n.") {
            $text .= " " . $row['alt_number'];
        }
        if ($row['series']) {
            $text .= " " . $row['series'];
        }
        $text .= " " . $row['series_number'];
    } else {
        if ($row['series']) {
            $text .= " " . $row['series'];
        }
        if ($row['Nummer']) {
            $text .= " " . $row['Nummer'];
        }
        if ($row['alt_number']) {
            $text .= " " . $row['alt_number'];
        }
    }

    if ($isBotanyPilot){
        if ($row['WIKIDATA_ID']) {
            $text .= "&nbsp;<a href=\"https://services.bgbm.org/botanypilot/person/q/" . basename($row['WIKIDATA_ID']) . '" target="_blank" class="leftnavi">(link to CETAF Botany Pilot)</a>&nbsp;';
        } elseif ($row['HUH_ID']) {
            $text .= "&nbsp;<a href=\"https://services.bgbm.org/botanypilot/person/h/" . basename($row['HUH_ID']) . '" target="_blank" class="leftnavi">(link to CETAF Botany Pilot)</a>&nbsp;';
        } elseif ($row['VIAF_ID']) {
            $text .= "&nbsp;<a href=\"https://services.bgbm.org/botanypilot/person/v/" . basename($row['VIAF_ID']) . '" target="_blank" class="leftnavi">(link to CETAF Botany Pilot)</a>&nbsp;';
        } elseif ($row['ORCID']) {
            $text .= "&nbsp;<a href=\"https://services.bgbm.org/botanypilot/person/o/" . basename($row['ORCID']) . '" target="_blank" class="leftnavi">(link to CETAF Botany Pilot)</a>&nbsp;';
        }
    }

    return trim($text);
}


// new triple id class
class MyTripleID extends TripleID
{
    public function __construct($id) {
        global $dbLink;

        // do some conversion stuff
        // ex.: database query for institution, source, object ...
        //      sql = "SELECT * FROM table WHERE id=" . $id
        // fill variables with data from database

        $query = "SELECT s.specimen_ID, mc.collection, mc.collectionID, s.HerbNummer, md.SourceInstitutionID, md.SourceID
                  FROM tbl_specimens s
                   LEFT JOIN tbl_management_collections mc ON mc.collectionID=s.collectionID
                   LEFT JOIN metadata md ON md.db_id=mc.source_id
                  WHERE HerbNummer like '" . ($id)."'";
        $result = $dbLink->query($query);

        if ($dbLink->connect_errno) {
            echo $query . "<br>\n";
            echo $dbLink->error . "<br>\n";
        }
        $row = $result->fetch_array();
        parent::__construct($row['SourceInstitutionID'], $row['SourceID'], $row['HerbNummer']);
    }
}


function generateAnnoTable($metadata)
{
    // table header
    $str = '<table width="100%"><tr><td align="left">'
         . '<strong>' . count($metadata) . ' annotation(s)</strong></td></tr>';
    // add annotations
    foreach ($metadata as $anno) {
        $str .= '<tr><td align="left">'
              . "<strong>Annotator:</strong> " . $anno['annotator'] . "; "
              . "<strong>Type of annotation:</strong> " . $anno['motivation'] . "; "
              .  "<strong>Date:</strong> " . date("d M Y", $anno['time'] / 1000) . "; "
              . "<a href=\"" . $anno['viewURI'] . '" target="_blank" class="leftnavi">View annotation</a><br/>'
              . "</td></tr>";
    }
    // close table
    $str .= "</table>";

    return $str;
}

function collectionID($row)
{
    if ($row['source_id'] == '29') {
        $text = ($row['HerbNummer']) ? $row['HerbNummer'] : ('B (JACQ-ID ' . $row['specimen_ID'] . ')');
    } elseif ($row['source_id'] == '50') {
        $text = ($row['HerbNummer']) ? $row['HerbNummer'] : ('Willing (JACQ-ID ' . $row['specimen_ID'] . ')');
    } else {
        $text = $row['collection'] . " " . $row['HerbNummer'];
    }

    return trim($text);
}

function HerbariumNr($row)
{
    if ($row['source_id'] == '29') {
        $text = ($row['HerbNummer']) ? $row['HerbNummer'] : ('B (JACQ-ID ' . $row['specimen_ID'] . ')');
    } elseif ($row['source_id'] == '50') {
        $text = ($row['HerbNummer']) ? $row['HerbNummer'] : ('Willing (JACQ-ID ' . $row['specimen_ID'] . ')');
    } else {
        $text = $row['source_code'] . " " . $row['HerbNummer'];
    }

    return trim($text);
}

function taxon($row)
{
    $text = $row['genus'];
    if ($row['epithet']) {
        $text .= " " . $row['epithet'] . " " . $row['author'];
    }
    if ($row['epithet1']) {
        $text .= " subsp. " . $row['epithet1'] . " " . $row['author1'];
    }
    if ($row['epithet2']) {
        $text .= " var. " . $row['epithet2'] . " " . $row['author2'];
    }
    if ($row['epithet3']) {
        $text .= " subvar. " . $row['epithet3'] . " " . $row['author3'];
    }
    if ($row['epithet4']) {
        $text .= " forma " . $row['epithet4'] . " " . $row['author4'];
    }
    if ($row['epithet5']) {
        $text .= " subforma " . $row['epithet5'] . " " . $row['author5'];
    }

    return $text;
}

function taxonWithHybrids($row)
{
    global $dbLink;

    if ($row['statusID'] == 1 && strlen($row['epithet']) == 0 && strlen($row['author']) == 0) {
        $resultHybrid = $dbLink->query("SELECT parent_1_ID, parent_2_ID
                                        FROM tbl_tax_hybrids
                                        WHERE taxon_ID_fk = '" . $row['taxonID'] . "'");
        $rowHybrid = $resultHybrid->fetch_array(MYSQLI_ASSOC);
        $result1 = $dbLink->query("SELECT tg.genus,
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
                                   WHERE taxonID = '" . $rowHybrid['parent_1_ID'] . "'");
        $row1 = $result1->fetch_array(MYSQLI_ASSOC);
        $result2 = $dbLink->query("SELECT tg.genus,
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
                                   WHERE taxonID = '" . $rowHybrid['parent_2_ID'] . "'");
        $row2 = $result2->fetch_array(MYSQLI_ASSOC);

        return taxon($row1) . " x " . taxon($row2);
    }
    else {
        return taxon($row);
    }
}

function getTaxonAuth($taxid)
{
    global $dbLink;

    $result = $dbLink->query("SELECT serviceID, hyper FROM herbar_view.view_taxon_link_service WHERE taxonID = $taxid");
    $text = '';
    if ($result && $result->num_rows > 0) {
    // output data of each row
        while($rowtax = $result->fetch_assoc()) {
            $text .= '<br/>';
            if ($rowtax['serviceID'] == 1) {
                $text .=  $rowtax["hyper"]."&nbsp;";
                $text .= str_replace("IPNI (K)","Plants of the World Online / POWO (K)",str_replace("serviceID1_logo","serviceID49_logo",str_replace("http://ipni.org/ipni/idPlantNameSearch.do?id=", "http://powo.science.kew.org/taxon/urn:lsid:ipni.org:names:", $rowtax["hyper"])));
            } else {
                $text .= $rowtax["hyper"];
            }
        }
    }
    return $text;
}

function getGeonamesID($HerbNummer)
{
    global $dbLink;

    $result = $dbLink->query("SELECT GeonamesID FROM lagu_pilot.geonames_data WHERE GeonamesID like 'h%' AND kBarcode LIKE '$HerbNummer'");
     $text = '';
    if ($result && $result->num_rows > 0) {
    // output data of each row
        while($row = $result->fetch_assoc()) {
            $text = "<br> Reference in: <a href='" . $row["GeonamesID"]. "' target='_blank' title='Geonames' alt='Geonames'>Geonames</a>; ";
        }
       // $text = '';
    }
    return $text;
}

function getBloodhoundID($row)
{
    global $dbLink;

    $result = $dbLink->query("SELECT Bloodhound_ID FROM herbarinput.tbl_collector WHERE Bloodhound_ID like 'h%' AND SammlerID like '" . ($row['SammlerID']) . "'");
    $text = '';
    if ($result && $result->num_rows > 0) {
    // output data of each row
        while($row = $result->fetch_assoc()) {
            $text = "<a href='" . $row["Bloodhound_ID"]. "' target='_blank' title='Bionomia' alt='Bionomia'><img src='assets/images/bionomia_logo.png' width='20px'></a>&nbsp;";
        }
    }
    return $text;
}

function collectionItem ($coll)
{
    if (strpos($coll, "-") !== false) {
        return substr($coll, 0, strpos($coll, "-"));
    } elseif (strpos($coll, " ") !== false) {
        return substr($coll, 0, strpos($coll, " "));
    } else {
        return $coll;
    }
}

function dms2sec ($degN, $minN, $secN, $degP, $minP, $secP)
{
    if ($degN > 0 || $minN > 0 || $secN > 0) {
        $sec = -($degN * 3600 + $minN * 60 + $secN);
    } else if ($degP > 0 || $minP > 0 || $secP > 0) {
        $sec = $degP * 3600 + $minP * 60 + $secP;
    } else {
        $sec = 0;
    }
    return $sec;
}

/* * ********************************************************************************
  php easy :: pagination scripts set - Version Three, changed by Dominik and Johannes
  ===================================================================================
  Author:      php easy code, www.phpeasycode.com
  Web Site:    http://www.phpeasycode.com
  Contact:     webmaster@phpeasycode.com
 * ******************************************************************************** */

function paginate_three($page, $tpages, $adjacents)
{
    $prevlabel = "<i class='material-icons'>chevron_left</i>";
    $nextlabel = "<i class='material-icons'>chevron_right</i>";

    $out = "<ul class='pagination'>\n";

    // previous
    if ($page == 1) {
        $out .= "<li><a>$prevlabel</a></li>\n";
    } else {
        $out .= "<li class='waves-effect' data-value='" . ($page - 1) . "'><a>$prevlabel</a></li>\n";
    }

    if ($tpages < 4 + $adjacents * 2 + 2) {
        $pmin = 1;
        $pmax = $tpages;
    } else {
        $prev = 0;
        $post = 0;
        // first
        if ($page > ($adjacents + 2)) {
            $prev++;
            $out .= "<li class='waves-effect' data-value='1'><a>1</a></li>\n";
        }

        // interval
        if ($page > ($adjacents + 3)) {
            $prev++;
            $out .= "<span class=\"pot\">...</span>";
        }

        // interval
        if ($page < ($tpages - $adjacents - 2)) {
            $post++;
        }

        // last
        if ($page < ($tpages - $adjacents - 2)) {
            $post++;
        }
        $pmin = $page - $adjacents - (2 - $prev);
        if ($pmin < 1) {
            $pmin = 1;
        }
        $diff = $adjacents - ($page - $pmin);
        $pmax = $page + $adjacents + $diff + 2 - $prev + 2 - $post;
        if ($pmax > $tpages) {
            $pmax = $tpages;
            $pmin = $pmax - 2 * $adjacents - 2;
            if ($pmin < 1) {
                $pmin = 1;
            }
        }
    }

    for ($i = $pmin; $i <= $pmax; $i++) {
        if ($i == $page) {
            $out .= "<li class='active'><a>" . htmlspecialchars($i) . "</a></li>\n";
        } elseif ($i == 1) {
            $out .= "<li class='waves-effect' data-value='$i'><a>" . htmlspecialchars($i) . "</a></li>\n";
        } else {
            $out .= "<li class='waves-effect' data-value='$i'><a>" . htmlspecialchars($i) . "</a></li>\n";
        }
    }
    if (!($tpages < 4 + $adjacents * 2 + 2)) {
        // interval
        if ($page < ($tpages - $adjacents - 2)) {
            $out .= "<span class=\"pot\">...</span>";
        }

        // last
        if ($page < ($tpages - $adjacents - 2)) {
            $out .= "<li class='waves-effect' data-value='$tpages'><a>" . htmlspecialchars($tpages) . "</a></li>\n";
        }
    }

    // next
    if ($page < $tpages) {
        $out .= "<li class='waves-effect' data-value='".($page+1)."'><a>$nextlabel</a></li>\n";
    } else {
        $out .= "<li><a>$nextlabel</a></li>\n";
    }

    $out .= "</ul>";

    return $out;
}
