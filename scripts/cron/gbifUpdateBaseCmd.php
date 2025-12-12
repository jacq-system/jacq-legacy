#!/usr/bin/php -q
<?php
require 'inc/stableIdentifier.php';

require_once __DIR__ . '/vendor/autoload.php';

use Jacq\DbAccess;
use Jacq\Settings;

ini_set("max_execution_time", "3600");
ini_set("memory_limit", "256M");

/**
 * process commandline arguments
 */
$opt = getopt("hnva", ["help", "nometa", "verbose", "all"], $restIndex);

$options = array(
    'help'    => (isset($opt['h']) || isset($opt['help']) || $argc == 1), // bool
    'all'     => (isset($opt['a']) || isset($opt['all'])),                // bool
    'nometa'  => (isset($opt['n']) || isset($opt['nometa'])),             // bool

    'verbose' => ((isset($opt['v']) || isset($opt['verbose'])) ? ((is_array($opt['v'])) ? 2 : 1) : 0)  // 0, 1 or 2
);
$remainArgs = array_slice($argv, $restIndex);
$source_id = (empty(($remainArgs))) ? 0 : intval($remainArgs[0]);

if ($options['help'] || (!$source_id && !$options['all'])) {
    echo $argv[0] . " [options] [x]   create gbif-Tables [for source-ID x]\n\n"
        . "Options:\n"
        . "  -h  --help     this explanation\n"
        . "  -n  --nometa   don't recreate metadata, tbl_specimens_types_mv and tbl_prj_gbif_pilot_total\n"
        . "  -v  --verbose  echo status messages\n"
        . "  -a  --all      use all predefined source-IDs\n\n";
    die();
}

// import all neccessary settings
$settings = Settings::Load();
$dbt = $settings->get('DATABASE', 'GBIF_PILOT')['name'];
$tbls = $settings->get('GBIF_TABLES');

// check if source_id is in the list of predefined sources
if ($source_id) {
    $sourceIdInList = false;
    foreach ($tbls as $tbl) {
        if ($source_id == $tbl['source_id']) {
            $sourceIdInList = true;
        }
    }
    if (!$sourceIdInList) {
        die("Error: source not in list\n");
    }
}

/* activate reporting */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $dbLink  = DbAccess::ConnectTo('INPUT', 1);
    $dbLink1 = DbAccess::ConnectTo('INPUT', 2);
    $dbLink2 = DbAccess::ConnectTo('INPUT', 3);
} catch (Exception $e) {
    echo $e->__toString() . "\n";
    die();
}

if (!$options['nometa']) {
    $dbLink2->queryCatch("DROP TABLE IF EXISTS $dbt.metadata");
    $dbLink2->queryCatch("CREATE TABLE $dbt.metadata LIKE metadata");
    $dbLink2->queryCatch("INSERT $dbt.metadata SELECT * FROM metadata");
    if ($options['verbose']) {
        echo "---------- Table $dbt.metadata created (" . date(DATE_RFC822) . ") ----------\n";
    }
    //$dbLink2->queryCatch("DROP TABLE $dbt.metadb");
    //$dbLink2->queryCatch("CREATE TABLE $dbt.metadb LIKE metadb");
    //$dbLink2->queryCatch("INSERT $dbt.metadb SELECT * FROM metadb");

    $dbLink2->queryCatch("TRUNCATE $dbt.tbl_specimens_types_mv");
    $dbLink2->queryCatch("INSERT $dbt.tbl_specimens_types_mv
                        SELECT NULL,
                               `herbar_view`.`GetScientificName`(`tbl_specimens_types`.`taxonID`, 0),
                               `specimenID`,
                               `tbl_typi`.`typus_engl`,
                               `typified_by_Person`,
                               `typified_Date`,
                               `annotations`
                        FROM `tbl_specimens_types`
                         LEFT JOIN `tbl_typi` ON `tbl_specimens_types`.`typusID` = `tbl_typi`.`typusID`");
    if ($options['verbose']) {
        echo "---------- Table $dbt.tbl_specimens_types_mv created (" . date(DATE_RFC822) . ") ----------\n";
    }
}


// use $tbls as defined in variables.php
foreach ($tbls as $tbl) {
    if ($options['all'] || $source_id == $tbl['source_id']) {
        $sourceCode = $dbLink2->queryCatch("SELECT source_code 
                                       FROM meta 
                                       WHERE source_id = {$tbl['source_id']}")
                              ->fetch_array()['source_code'];
        if ($tbl['source_id'] == 29) {  // B needs to find duplicates on the collection level
            $sql_IN = "SELECT s2.specimen_ID 
                       FROM tbl_specimens s2, tbl_management_collections mc2
                       WHERE s2.collectionID = mc2.collectionID
                        AND  mc2.source_id = 29
                        AND  s2.`accessible` > 0
                        AND  s2.HerbNummer IS NOT NULL
                       GROUP by s2.HerbNummer, s2.collectionID  
                       HAVING count(*) = 1";
        } else {                        // anyone else on source level
            $sql_IN = "SELECT s2.specimen_ID 
                       FROM tbl_specimens s2, tbl_management_collections mc2
                       WHERE s2.collectionID = mc2.collectionID
                        AND  mc2.source_id = '" . $tbl['source_id'] . "'
                        AND  s2.`accessible` > 0
                        AND  s2.HerbNummer IS NOT NULL
                       GROUP by s2.HerbNummer 
                       HAVING count(*) = 1";
        }
        $sql = "SELECT s.specimen_ID, s.taxonID,
                 s.series_number, s.Nummer, s.alt_number, s.Datum, s.Datum2, s.det, s.Bemerkungen,
                 s.altitude_min, s.altitude_max, s.exactness, s.taxon_alt,
                 s.Coord_W, s.W_Min, s.W_Sec, s.Coord_N, s.N_Min, s.N_Sec,
                 s.Coord_S, s.S_Min, s.S_Sec, s.Coord_E, s.E_Min, s.E_Sec,
                 s.Fundort, s.habitat, s.habitus,
                 s.digital_image, s.observation, s.digital_image_obs, s.HerbNummer,
                 c.Sammler, c.HUH_ID, c.VIAF_ID, c.WIKIDATA_ID, c.ORCID, c.Bloodhound_ID, 
                 c2.Sammler_2,
                 herbar_view.GetScientificName(s.taxonID, 0) AS sciname,
                 ts.taxonID, ts.statusID, tg.genus, tf.family, ttr.rank,
                 ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
                 ta4.author author4, ta5.author author5,
                 te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
                 te4.epithet epithet4, te5.epithet epithet5,
                 gn.nation_engl, gn.iso_alpha_3_code, gp.provinz,
                 ss.series,
                 md.copyright, md.ipr, md.rights_url,md.multimedia_object_format,
                 mc.source_id, mc.collection, mc.coll_gbif_pilot,
                 ei.filesize
                FROM (tbl_specimens s, tbl_collector c, tbl_tax_species ts, tbl_tax_rank ttr, tbl_management_collections mc)
                 LEFT JOIN tbl_collector_2 c2 ON c2.Sammler_2ID = s.Sammler_2ID
                 LEFT JOIN tbl_tax_authors ta   ON ta.authorID   = ts.authorID
                 LEFT JOIN tbl_tax_authors ta1  ON ta1.authorID  = ts.subspecies_authorID
                 LEFT JOIN tbl_tax_authors ta2  ON ta2.authorID  = ts.variety_authorID
                 LEFT JOIN tbl_tax_authors ta3  ON ta3.authorID  = ts.subvariety_authorID
                 LEFT JOIN tbl_tax_authors ta4  ON ta4.authorID  = ts.forma_authorID
                 LEFT JOIN tbl_tax_authors ta5  ON ta5.authorID  = ts.subforma_authorID
                 LEFT JOIN tbl_tax_epithets te  ON te.epithetID  = ts.speciesID
                 LEFT JOIN tbl_tax_epithets te1 ON te1.epithetID = ts.subspeciesID
                 LEFT JOIN tbl_tax_epithets te2 ON te2.epithetID = ts.varietyID
                 LEFT JOIN tbl_tax_epithets te3 ON te3.epithetID = ts.subvarietyID
                 LEFT JOIN tbl_tax_epithets te4 ON te4.epithetID = ts.formaID
                 LEFT JOIN tbl_tax_epithets te5 ON te5.epithetID = ts.subformaID
                 LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
                 LEFT JOIN tbl_tax_families tf ON tf.familyID = tg.familyID
                 LEFT JOIN tbl_geo_nation gn ON gn.nationID = s.NationID
                 LEFT JOIN tbl_geo_province gp ON gp.provinceID = s.provinceID
                 LEFT JOIN tbl_specimens_series ss ON ss.seriesID = s.seriesID
                 LEFT JOIN metadb md ON md.source_id_fk = mc.source_id
                 LEFT JOIN gbif_pilot.europeana_images ei ON ei.specimen_ID = s.specimen_ID 
                WHERE s.SammlerID = c.SammlerID
                 AND s.taxonID = ts.taxonID
                 AND ts.tax_rankID = ttr.tax_rankID
                 AND s.collectionID = mc.collectionID
                 AND s.accessible > 0
                 AND mc.source_id = '" . $tbl['source_id'] . "'
                 AND (s.specimen_ID IN ($sql_IN) OR s.HerbNummer IS NULL)";
        $result = $dbLink1->queryCatch($sql, MYSQLI_USE_RESULT);
        while ($row = $result->fetch_array()) {

            /**
             * AuthorTeam
             * SecondEpithet
             */
            if ($row['epithet5']) {
                $AuthorTeam = $row['author5'];
                $SecondEpithet = $row['epithet5'];
            } elseif ($row['epithet4']) {
                $AuthorTeam = $row['author4'];
                $SecondEpithet = $row['epithet4'];
            } elseif ($row['epithet3']) {
                $AuthorTeam = $row['author3'];
                $SecondEpithet = $row['epithet3'];
            } elseif ($row['epithet2']) {
                $AuthorTeam = $row['author2'];
                $SecondEpithet = $row['epithet2'];
            } elseif ($row['epithet1']) {
                $AuthorTeam = $row['author1'];
                $SecondEpithet = $row['epithet1'];
            } else {
                $AuthorTeam = $row['author'];
                $SecondEpithet = "";
            }

            /**
             * NomService_*
             */
            $nomServiceUrls = array(1 => "", 3 => "", 19 => "", 21 => "", 57 => "");
            $nomServiceRows = $dbLink->queryCatch("SELECT nsn.serviceID, nsn.param1, ns.url_head, ns.url_middle, ns.url_trail
                                              FROM tbl_nom_service_names nsn 
                                               LEFT JOIN tbl_nom_service ns ON ns.serviceID = nsn.serviceID
                                              WHERE nsn.taxonID = " . $row['taxonID'] . "
                                               AND nsn.serviceID IN (1, 3, 19, 21, 57)")
                                      ->fetch_all(MYSQLI_ASSOC);
            foreach ($nomServiceRows as $nomServiceRow) {
                $nomServiceUrls[$nomServiceRow['serviceID']] = $nomServiceRow['url_head']
                                                             . $nomServiceRow['param1']
                                                             . $nomServiceRow['url_middle']
                                                             . $nomServiceRow['url_trail'];
            }

            /**
             * LatitudeDecimal
             * LongitudeDecimal
             * SpatialDatum
             */
            if ($row['Coord_S'] > 0 || $row['S_Min'] > 0 || $row['S_Sec'] > 0) {
                $lat = -($row['Coord_S'] + $row['S_Min'] / 60 + $row['S_Sec'] / 3600);
            } else if ($row['Coord_N'] > 0 || $row['N_Min'] > 0 || $row['N_Sec'] > 0) {
                $lat = $row['Coord_N'] + $row['N_Min'] / 60 + $row['N_Sec'] / 3600;
            } else {
                $lat = 0;
            }
            $LatitudeDecimal = ($lat) ? sprintf("%1.5f", $lat) : "";

            if ($row['Coord_W'] > 0 || $row['W_Min'] > 0 || $row['W_Sec'] > 0) {
                $lon = -($row['Coord_W'] + $row['W_Min'] / 60 + $row['W_Sec'] / 3600);
            } else if ($row['Coord_E'] > 0 || $row['E_Min'] > 0 || $row['E_Sec'] > 0) {
                $lon = $row['Coord_E'] + $row['E_Min'] / 60 + $row['E_Sec'] / 3600;
            } else {
                $lon = 0;
            }
            $LongitudeDecimal = ($lon) ? sprintf("%1.5f", $lon) : "";

            $SpatialDatum = ($lat || $lon) ? "WGS84" : "";

            /**
             * GatheringAgentsText
             */
            $GatheringAgentsText = $row['Sammler'];
            if (strstr($row['Sammler_2'], "&") || strstr($row['Sammler_2'], "et al.")) {
                $GatheringAgentsText .= " et al.";
            } elseif ($row['Sammler_2']) {
                $GatheringAgentsText .= " & " . $row['Sammler_2'];
            }
            if ($row['series_number']) {
                if ($row['Nummer']) {
                    $GatheringAgentsText .= " " . $row['Nummer'];
                }
                if ($row['alt_number']) {
                    $GatheringAgentsText .= " " . $row['alt_number'];
                }
                if (str_contains($row['alt_number'], "s.n.")) {
                    $GatheringAgentsText .= " [" . $row['Datum'] . "]";
                }
                if ($row['series']) {
                    $GatheringAgentsText .= " " . $row['series'];
                }
                $GatheringAgentsText .= " " . $row['series_number'];
            } else {
                if ($row['series']) {
                    $GatheringAgentsText .= " " . $row['series'];
                }
                if ($row['Nummer']) {
                    $GatheringAgentsText .= " " . $row['Nummer'];
                }
                if ($row['alt_number']) {
                    $GatheringAgentsText .= " " . $row['alt_number'];
                }
                if (str_contains($row['alt_number'], "s.n.")) {
                    $GatheringAgentsText .= " [" . $row['Datum'] . "]";
                }
            }

            /**
             * CollectorTeam
             */
            $CollectorTeam = $row['Sammler'];
            if (strstr($row['Sammler_2'], "et al.") || strstr($row['Sammler_2'], "alii")) {
                $CollectorTeam .= " et al.";
            } elseif ($row['Sammler_2']) {
                $parts = explode(',', $row['Sammler_2']);           // some people forget the final "&"
                if (count($parts) > 2) {                            // so we have to use an alternative way
                    $CollectorTeam .= ", " . $row['Sammler_2'];
                } else {
                    $CollectorTeam .= " & " . $row['Sammler_2'];
                }
            }

            /**
             * IdentificationDate
             */
            if (intval(substr($row['det'], -2, 2)) != 0) {
                if (substr($row['det'], -3, 1) != "-") {
                    $IdentificationDate = substr($row['det'], -4, 4);
                } else if (substr($row['det'], -6, 1) != "-") {
                    $IdentificationDate = substr($row['det'], -7, 7);
                } else {
                    $IdentificationDate = substr($row['det'], -10, 10);
                }
            } else {
                $IdentificationDate = "";
            }

            /**
             * image_url
             */
            if ($row['digital_image'] || $row['digital_image_obs']) {
                $image_url = "https://api.jacq.org/v1/images/show/" . $row['specimen_ID'] . "?withredirect=1";
//              $image_url = "https://services.jacq.org/jacq-services/rest/images/show/" . $row['specimen_ID'] . "?withredirect=1";
//              $image_url = "http://www.jacq.org/image.php?filename=" . $row['specimen_ID'] . "&method=show";
                if ($tbl['europeana_cache'] && ($row['filesize'] ?? 0) > 1500) {  // use europeana-cache only for images without errors
                    $thumb_url = "https://object.jacq.org/europeana/$sourceCode/{$row['specimen_ID']}.jpg";

                } else {
                    $thumb_url = "https://api.jacq.org/v1/images/europeana/" . $row['specimen_ID'] . "?withredirect=1";
//                  $thumb_url = "https://services.jacq.org/jacq-services/rest/images/europeana/" . $row['specimen_ID'] . "?withredirect=1";
//                  $thumb_url = "http://www.jacq.org/image.php?filename=" . $row['specimen_ID'] . "&method=europeana";
                }
            } else {
                $image_url = "";
                $thumb_url = "";
            }

            /**
             * recordURI
             */
            $recordURI = StableIdentifier($tbl['source_id'], $row['HerbNummer'], $row['specimen_ID']);
            /**
             * LastEditor
             * DateLastEdited
             */
            $sql = "SELECT u.firstname, u.surname, ls.timestamp
                FROM herbarinput_log.log_specimens ls, herbarinput_log.tbl_herbardb_users u
                WHERE ls.userID = u.userID
                 AND ls.specimenID = '" . $row['specimen_ID'] . "'
                ORDER BY ls.timestamp DESC";
            $rowLog = $dbLink2->queryCatch($sql)->fetch_array();
            if ($rowLog) {
                $LastEditor = $rowLog['surname'] . ", " . $rowLog['firstname'];
                $DateLastEdited = $rowLog['timestamp'];
            } else {
                $LastEditor = "Rainer, Heimo";
                $DateLastEdited = "2004-11-26 19:20:22";
            }

            /**
             * UPDATE database
             */
            $sql = "UnitID                = " . $dbLink2->quoteString(($row['HerbNummer']) ?: ('JACQ-ID ' . $row['specimen_ID'])) . ",
                 NameAuthorYearString     = " . $dbLink2->quoteString($row['sciname']) . ",
                 Genus                    = " . $dbLink2->quoteString($row['genus']) . ",
                 FirstEpithet             = " . $dbLink2->quoteString($row['epithet']) . ",
                 AuthorTeam               = " . $dbLink2->quoteString($AuthorTeam) . ",
                 SecondEpithet            = " . $dbLink2->quoteString($SecondEpithet) . ",
                 HybridFlag               = " . (($row['statusID'] == 1) ? "1" : "NULL") . ",
                 Rank                     = " . $dbLink2->quoteString($row['rank']) . ",
                 HigherTaxon              = " . $dbLink2->quoteString($row['family']) . ",
                 NomService_IPNI          = " . $dbLink2->quoteString($nomServiceUrls[1]) . ",
                 NomService_IF            = " . $dbLink2->quoteString($nomServiceUrls[3]) . ",
                 NomService_Algaebase     = " . $dbLink2->quoteString($nomServiceUrls[19]) . ",
                 NomService_reflora       = " . $dbLink2->quoteString($nomServiceUrls[21]) . ",
                 NomService_wfo           = " . $dbLink2->quoteString($nomServiceUrls[57]) . ",
                 ISODateTimeBegin         = " . $dbLink2->quoteString((trim($row['Datum']) == "s.d.") ? "" : $row['Datum']) . ",
                 ISODateTimeEnd           = " . $dbLink2->quoteString($row['Datum2']) . ",
                 LocalityText             = " . $dbLink2->quoteString($row['Fundort']) . ",
                 LocalityDetailed         = " . $dbLink2->quoteString($row['Fundort']) . ",
                 habitat                  = " . $dbLink2->quoteString($row['habitat']) . ",
                 habitus                  = " . $dbLink2->quoteString($row['habitus']) . ",
                 CountryName              = " . $dbLink2->quoteString($row['nation_engl']) . ",
                 ISO3Letter               = " . $dbLink2->quoteString($row['iso_alpha_3_code']) . ",
                 NamedAreaName            = " . $dbLink2->quoteString(($row['nation_engl'] == "Austria") ? mb_substr($row['provinz'], 0, 2) : $row['provinz']) . ",
                 NamedAreaClass           = " . (($row['nation_engl'] == "Austria") ? "'Bundesland'" : "NULL") . ",
                 MeasurmentLowerValue     = " . $dbLink2->quoteString($row['altitude_min']) . ",
                 MeasurmentUpperValue     = " . $dbLink2->quoteString($row['altitude_max']) . ",
                 LatitudeDecimal          = " . $dbLink2->quoteString($LatitudeDecimal) . ",
                 LongitudeDecimal         = " . $dbLink2->quoteString($LongitudeDecimal) . ",
                 SpatialDatum             = " . $dbLink2->quoteString($SpatialDatum) . ",
                 exactness                = " . $dbLink2->quoteString($row['exactness']) . ",
                 CollectorsFieldNumber    = " . $dbLink2->quoteString(trim($row['Nummer'] . ' ' . $row['alt_number'])) . ",
                 GatheringAgentsText      = " . $dbLink2->quoteString($GatheringAgentsText) . ",
                 PrimaryCollector         = '" . $dbLink2->real_escape_string($row['Sammler']) . "',
                 PrimaryCollector_HUH_ID        = " . ((str_starts_with($row['HUH_ID'], 'http')) ? $dbLink2->quoteString($row['HUH_ID']) : "NULL") . ",
                 PrimaryCollector_VIAF_ID       = " . ((str_starts_with($row['VIAF_ID'], 'http')) ? $dbLink2->quoteString($row['VIAF_ID']) : "NULL") . ",
                 PrimaryCollector_WIKIDATA_ID   = " . ((str_starts_with($row['WIKIDATA_ID'], 'http')) ? $dbLink2->quoteString($row['WIKIDATA_ID']) : "NULL") . ",
                 PrimaryCollector_ORCID         = " . ((str_starts_with($row['ORCID'], 'http')) ? $dbLink2->quoteString($row['ORCID']) : "NULL") . ",
                 PrimaryCollector_Bloodhound_ID = " . ((str_starts_with($row['Bloodhound_ID'], 'http')) ? $dbLink2->quoteString($row['Bloodhound_ID']) : "NULL") . ",
                 CollectorTeam            = '" . $dbLink2->real_escape_string($CollectorTeam) . "',
                 IdentificationHistory    = " . $dbLink2->quoteString(substr($row['taxon_alt'], 0, 255)) . ",
                 IdentificationDate       = " . $dbLink2->quoteString($IdentificationDate) . ",
                 NamedCollection          = " . $dbLink2->quoteString($row['coll_gbif_pilot']) . ",
                 UnitIDNumeric            = {$row['specimen_ID']},
                 UnitDescription          = " . $dbLink2->quoteString($row['Bemerkungen']) . ",
                 source_id_fk             = {$row['source_id']},
                 det                      = " . $dbLink2->quoteString($row['det']) . ",
                 image_url                = " . $dbLink2->quoteString($image_url) . ",
                 thumb_url                = " . $dbLink2->quoteString($thumb_url) . ",
                 MultimediaIPR            = " . (($image_url) ? $dbLink2->quoteString($row['ipr']) : "NULL") . ",
                 copyright                = " . (($image_url) ? $dbLink2->quoteString($row['copyright']) : "NULL") . ",
                 rights_url               = " . (($image_url) ? $dbLink2->quoteString($row['rights_url']) : "NULL") . ",
                 multimedia_object_format = " . (($image_url) ? $dbLink2->quoteString($row['multimedia_object_format']) : "NULL") . ",
                 recordURI                = " . $dbLink2->quoteString($recordURI) . ",
                 LastEditor               = " . $dbLink2->quoteString($LastEditor) . ",
                 DateLastEdited           = '" . $dbLink2->real_escape_string($DateLastEdited) . "',
                 RecordBasis              = " . (($row['observation'] > 0) ? "'HumanObservation'" : "'PreservedSpecimen'") . ",
                 Notes                    = " . ((false) ? $dbLink2->quoteString($row['Bemerkungen']) : "NULL");
            // TODO: add field "Notes" to fill conditionally with tbl_specimens.Bemerkungen if a flag in "meta" is set
            $hash = hash('md5', $sql);
            $unit = $dbLink2->queryCatch("SELECT UnitID, hash FROM $dbt.{$tbl['name']} WHERE UnitIDNumeric = {$row['specimen_ID']}")->fetch_assoc();
            if (empty($unit)) {
                $dbLink2->queryCatch("INSERT INTO $dbt.{$tbl['name']} SET 
                                  hash = '$hash',
                                  $sql");
            } elseif ($unit['hash'] != $hash) {
                $dbLink2->queryCatch("UPDATE $dbt.{$tbl['name']} SET 
                                  hash = '$hash',
                                  $sql 
                                 WHERE UnitIDNumeric = {$row['specimen_ID']}");
            }
        }
        $result->free();
        $rows = $dbLink1->queryCatch("SELECT gp.UnitIDNumeric
                                 FROM $dbt.{$tbl['name']} gp
                                  LEFT JOIN tbl_specimens s ON s.specimen_ID = gp.UnitIDNumeric
                                 WHERE s.specimen_ID IS NULL")
                        ->fetch_all(MYSQLI_ASSOC);
        foreach ($rows as $row) {
            $dbLink2->queryCatch("DELETE FROM $dbt.{$tbl['name']} WHERE UnitIDNumeric = {$row['UnitIDNumeric']}");
            if ($options['verbose']) {
                echo "$sourceCode ({$tbl['source_id']}) deleted {$row['UnitIDNumeric']}\n";
            }
        }
        if ($options['verbose']) {
            echo "---------- $sourceCode ({$tbl['source_id']}) finished (" . date(DATE_RFC822) . ") ----------\n";
        }
    }
}

if (!$options['nometa']) {
    // recreate tbl_prj_gbif_pilot_total with data of all gbif-pilot-tables
    $dbLink2->queryCatch("TRUNCATE $dbt.tbl_prj_gbif_pilot_total");
    // use $tbls as defined in variables.php
    foreach ($tbls as $tbl) {
        $dbLink2->queryCatch("INSERT INTO $dbt.tbl_prj_gbif_pilot_total 
                          SELECT UnitIDNumeric, UnitID, recordURI, {$tbl['source_id']}, '{$tbl['name']}'
                          FROM $dbt.{$tbl['name']}");
    }
    if ($options['verbose']) {
        echo "---------- tbl_prj_gbif_pilot_total finished (" . date(DATE_RFC822) . ") ----------\n";
    }
}
