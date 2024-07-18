<?php

use Jacq\DbAccess;
use Jacq\Settings;

require_once( 'tools.php' );
require __DIR__ . '/../vendor/autoload.php';

/**
 * Return scientific name for a given taxon_id
 *
 * @param int|null $taxon_id Taxon-id to search for
 * @param bool $withDT Include dallatorre-id, defaults to no
 * @param bool $withID Include taxon-id, defaults to no
 * @param bool $p_bAvoidHybridFormula avoid hybrids, defaults to no
 * @return string
 */
function getScientificName (?int $taxon_id, bool $withDT = false, bool $withID = true, bool $p_bAvoidHybridFormula = false): string
{
    // wrong call with empty taxon-ID
    if (empty($taxon_id)) {
        return '';
    }

    // Translation between mysql boolean (tinyint) and php boolean
    if( $p_bAvoidHybridFormula ) {
        $p_bAvoidHybridFormula = 1;
    } else {
        $p_bAvoidHybridFormula = 0;
    }

    // Use stored procedure in order to fetch the scientific name
    $row = dbi_query("SELECT `herbar_view`.GetScientificName( $taxon_id, $p_bAvoidHybridFormula ) AS 'ScientificName'")->fetch_assoc();

    // Extend scientific name with additional information
    $scientificName = $row['ScientificName'];
    if( $withDT ) {
        $sql = "SELECT `tg`.`DallaTorreIDs`, `tg`.`DallaTorreZusatzIDs`
                FROM `tbl_tax_species` `ts`
                LEFT JOIN `tbl_tax_genera` `tg`
                ON `tg`.`genID` = `ts`.`genID`
                WHERE `ts`.`taxonID` = '$taxon_id'";

        $row = dbi_query($sql)->fetch_assoc();

        $scientificName .= " " . $row['DallaTorreIDs'] . $row['DallaTorreZusatzIDs'];

    }
    if( $withID ) {
        $scientificName .= " <$taxon_id>";
    }

    return $scientificName;
}

/**
 * constructs the link to the image on an IIIF-Server for a specimen, if iiif for this source is activated
 *
 * @param int $specimenID specimen-ID
 * @return string link to the image
 * @throws Exception
 */
function getIiifLink(int $specimenID): string
{
    $specimenID_filtered = intval($specimenID);

    $dbLink = DbAccess::ConnectTo('INPUT');
    $image = $dbLink->query("SELECT tid.iiif_capable, tid.iiif_url, ph.specimenID AS phaidraID
                             FROM tbl_specimens s
                              LEFT JOIN herbar_pictures.phaidra_cache ph ON ph.specimenID = s.specimen_ID
                              LEFT JOIN tbl_management_collections mc ON mc.collectionID = s.collectionID
                              LEFT JOIN tbl_img_definition tid ON tid.source_id_fk = mc.source_id
                             WHERE s.specimen_ID = '$specimenID_filtered'")
                    ->fetch_assoc();
    if ($image['iiif_capable'] || $image['phaidraID']) {
        $config = Settings::Load();
        $ch = curl_init($config->get('JACQ_SERVICES') . "iiif/manifestUri/$specimenID_filtered");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $curl_response = curl_exec($ch);
        if ($curl_response !== false) {
            $curl_result = json_decode($curl_response, true);
            $manifest = $curl_result['uri'];
        } else {
            $manifest = "";
        }
        curl_close($ch);

        return $image['iiif_url'] . "?manifest=$manifest";
    } else {
        return '';
    }
}

function taxon($row, $withDT = false, $withID = true)
{
    $text = $row['genus'];
    if ($row['epithet']) {
        $text .= " ".$row['epithet'].chr(194).chr(183)." ".$row['author'];
    } else {
        $text .= chr(194).chr(183);
    }
    if ($row['epithet1']) { $text .= " subsp. "   . $row['epithet1'] . " " . $row['author1']; }
    if ($row['epithet2']) { $text .= " var. "     . $row['epithet2'] . " " . $row['author2']; }
    if ($row['epithet3']) { $text .= " subvar. "  . $row['epithet3'] . " " . $row['author3']; }
    if ($row['epithet4']) { $text .= " forma "    . $row['epithet4'] . " " . $row['author4']; }
    if ($row['epithet5']) { $text .= " subforma " . $row['epithet5'] . " " . $row['author5']; }

    if ($withDT) {
        $text .= " " . $row['DallaTorreIDs'] . $row['DallaTorreZusatzIDs'];
    }

    if ($withID) {
        $text .= " <" . $row['taxonID'] . ">";
    }

    return $text;
}

function taxonWithHybrids($row)
{
    if ($row['parent_1_ID'] && $row['parent_2_ID']) {
        $sql = "SELECT tg.genus,
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
                WHERE taxonID='".$row['parent_1_ID']."'";
        $row1 = dbi_query($sql)->fetch_array();
        $sql = "SELECT tg.genus,
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
                WHERE taxonID='".$row['parent_2_ID']."'";
        $row2 = dbi_query($sql)->fetch_array();

        $text = $row1['genus'];
        if ($row1['epithet']) {
            $text .= " " . $row1['epithet'] . chr(194) . chr(183) . " " . $row1['author'];
        } else {
            $text .= chr(194).chr(183);
        }
        $text .= subTaxonItem($row1) . " x " . taxonItem($row2) . " <" . $row['taxonID'].">";
        return $text;
    } else {
        return taxon($row);
    }
}

function taxonAccepted($row)
{
    $text = $row['genus_a'];
    if ($row['epithet_a']) {
        $text .= " ".$row['epithet_a'].chr(194).chr(183)." ".$row['author_a'];
    } else {
        $text .= chr(194).chr(183);
    }
    if ($row['epithet1_a']) { $text .= " subsp. "   . $row['epithet1_a'] . " " . $row['author1_a']; }
    if ($row['epithet2_a']) { $text .= " var. "     . $row['epithet2_a'] . " " . $row['author2_a']; }
    if ($row['epithet3_a']) { $text .= " subvar. "  . $row['epithet3_a'] . " " . $row['author3_a']; }
    if ($row['epithet4_a']) { $text .= " forma "    . $row['epithet4_a'] . " " . $row['author4_a']; }
    if ($row['epithet5_a']) { $text .= " subforma " . $row['epithet5_a'] . " " . $row['author5_a']; }

    return $text . " <" . $row['taxonID_a'] . ">";
}

function protolog($row)
{
    $text = $row['autor'] . " (" . substr($row['jahr'], 0, 4) . ")";
    if ($row['suptitel']) {
        $text .= " in " . $row['editor'] . ": " . $row['suptitel'];
    }
    if ($row['periodicalID']) {
        $text .= " " . $row['periodical'];
    }
    $text .= " " . $row['vol'];
    if ($row['part']) {
        $text .= " (" . $row['part'] . ")";
    }
    $text .= ": " . $row['pp'] . ".";

    return $text . " <" . $row['citationID'] . ">";
}

function sortItem($typ,$id)
{
    if ($typ == $id) {
        return "&nbsp;&nbsp;v";
    } else if ($typ == -$id) {
        return "&nbsp;&nbsp;^";
    }
}

function taxonItem($row)
{
    $text = $row['genus'];
    if ($row['epithet'])  { $text .= " "          . $row['epithet']  . " " . $row['author']; }
    if ($row['epithet1']) { $text .= " subsp. "   . $row['epithet1'] . " " . $row['author1']; }
    if ($row['epithet2']) { $text .= " var. "     . $row['epithet2'] . " " . $row['author2']; }
    if ($row['epithet3']) { $text .= " subvar. "  . $row['epithet3'] . " " . $row['author3']; }
    if ($row['epithet4']) { $text .= " forma "    . $row['epithet4'] . " " . $row['author4']; }
    if ($row['epithet5']) { $text .= " subforma " . $row['epithet5'] . " " . $row['author5']; }

    return $text;
}

function subTaxonItem($row)
{
    $text = "";
    if ($row['epithet1']) { $text .= " subsp. "   . $row['epithet1'] . " " . $row['author1']; }
    if ($row['epithet2']) { $text .= " var. "     . $row['epithet2'] . " " . $row['author2']; }
    if ($row['epithet3']) { $text .= " subvar. "  . $row['epithet3'] . " " . $row['author3']; }
    if ($row['epithet4']) { $text .= " forma "    . $row['epithet4'] . " " . $row['author4']; }
    if ($row['epithet5']) { $text .= " subforma " . $row['epithet5'] . " " . $row['author5']; }

    return $text;
}

/**
 * @param $genus_name
 * @param $authorID
 * @param $dtid
 * @param $dtzid
 * @param $is_hybrid
 * @param $is_accepted
 * @param $familyID
 * @param $taxonID
 * @param $remarks
 * @param $lock
 * @return int the id of the created genus or 0 in case of an error
 */
function insertGenus($genus_name, $authorID, $dtid, $dtzid, $is_hybrid, $is_accepted, $familyID, $taxonID, $remarks,
                     $lock = '', $external = 0, $externalID = NULL)
{
    $sql = "INSERT INTO tbl_tax_genera SET
                         genus = "               . quoteString($genus_name) . ",
                         authorID = "            . makeInt($authorID) . ",
                         DallaTorreIDs = "       . quoteString($dtid) . ",
                         DallaTorreZusatzIDs = " . quoteString($dtzid) . ",
                         hybrid = "              . (($is_hybrid) ? "'X'" : "NULL") . ",
                         accepted = "            . (($is_accepted) ? "'1'" : "'0'") . ",
                         familyID = "            . makeInt($familyID) . ",".
                         (is_numeric($taxonID) ?  "fk_taxonID = " . makeInt($taxonID) . ",":"") .
                         "external = "           . quoteString($external) . ",
                         externalID = "          . quoteString($externalID) . ",
                         remarks = "             . quoteString($remarks) . "
                         $lock";
    $result = dbi_query($sql);
    if ($result) {
        $id = dbi_insert_id();
        logGenera($id, 0);
    } else {
        $id = 0;
    }
    return $id;
}
