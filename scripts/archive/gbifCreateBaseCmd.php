#!/usr/bin/php -q
<?php
require 'inc/variables.php';
require 'inc/StableIdentifier.php';

/**
 * included via inc/variables.php
 *
 * @var string $host hostname
 * @var string $user username
 * @var string $pass password
 * @var string $db   source database
 * @var string $dbt  target database
 * @var array  $tbls which sources are to be checked
 */

ini_set("max_execution_time", "3600");
ini_set("memory_limit", "256M");

/**
 * process commandline arguments
 */
$opt = getopt("hva", ["help", "verbose", "all"], $restIndex);

$options = array(
    'help'    => (isset($opt['h']) || isset($opt['help']) || $argc == 1), // bool
    'all'     => (isset($opt['a']) || isset($opt['all'])),                // bool

    'verbose' => ((isset($opt['v']) || isset($opt['verbose'])) ? ((is_array($opt['v'])) ? 2 : 1) : 0)  // 0, 1 or 2
);
$remainArgs = array_slice($argv, $restIndex);
$source_id = (empty(($remainArgs))) ? 0 : intval($remainArgs[0]);

if ($options['help'] || (!$source_id && !$options['all'])) {
    echo $argv[0] . " [options] [x]   create europeana files [for source-ID x]\n\n"
        . "Options:\n"
        . "  -h  --help     this explanation\n"
        . "  -v  --verbose  echo status messages\n"
        . "  -a  --all      use all predefined source-IDs\n\n";
    die();
}

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

class DB extends mysqli {

    public function __construct($host, $user, $pass, $db) {
        parent::__construct($host, $user, $pass, $db);

        if (mysqli_connect_error()) {
            die('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
        }

        $this->query("SET character set utf8");
    }

    public function query($query, $result_mode = MYSQLI_STORE_RESULT) {
        $result = parent::query($query, $result_mode);
        if (!$result) {
            echo $query . "\n";
            echo $this->error . "\n";
        }

        return $result;
    }

    public function quoteString($text) {
        if (mb_strlen($text) > 0) {
            return "'" . $this->real_escape_string($text) . "'";
        }
        else {
            return "NULL";
        }
    }

}
$dbLink  = new DB($host, $user, $pass, $db);
$dbLink1 = new DB($host, $user, $pass, $db);
$dbLink2 = new DB($host, $user, $pass, $db);

$dbLink2->query("DROP TABLE IF EXISTS $dbt.metadata");
$dbLink2->query("CREATE TABLE $dbt.metadata LIKE metadata");
$dbLink2->query("INSERT $dbt.metadata SELECT * FROM metadata");
if ($options['verbose']) {
    echo "---------- Table $dbt.metadata created (" . date(DATE_RFC822) . ") ----------\n";
}
//$dbLink2->query("DROP TABLE $dbt.metadb");
//$dbLink2->query("CREATE TABLE $dbt.metadb LIKE metadb");
//$dbLink2->query("INSERT $dbt.metadb SELECT * FROM metadb");

$dbLink2->query("TRUNCATE $dbt.tbl_specimens_types_mv");
$dbLink2->query("INSERT $dbt.tbl_specimens_types_mv
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


// use $tbls as defined in variables.php
foreach ($tbls as $tbl) {
    if ($options['all'] || $source_id == $tbl['source_id']) {
        $dbLink2->query("TRUNCATE $dbt." . $tbl['name']);
        $sourceCode = $dbLink2->query("SELECT source_code 
                                       FROM meta 
                                       WHERE source_id = {$tbl['source_id']}")
                              ->fetch_array()['source_code'];
        if ($tbl['source_id'] == 29) {  // B needs to find duplicates on collection level
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
        $dbLink2->query("INSERT INTO $dbt." . $tbl['name'] . "
                            (NameAuthorYearString,                        Genus,    FirstEpithet, Rank,     HigherTaxon, ISODateTimeEnd, LocalityText, LocalityDetailed, habitat,   habitus,   CountryName,    ISO3Letter,          MeasurmentLowerValue, MeasurmentUpperValue, exactness,   PrimaryCollector, CollectorTeam, IdentificationHistory, NamedCollection,    UnitIDNumeric, UnitDescription, source_id_fk, det,   RecordBasis)
                      SELECT herbar_view.GetScientificName(s.taxonID, 0), tg.genus, te.epithet,   ttr.rank, tf.family,   s.Datum2,       s.Fundort,    s.Fundort,        s.habitat, s.habitus, gn.nation_engl, gn.iso_alpha_3_code, s.altitude_min,       s.altitude_max,       s.exactness, c.Sammler,        ' ',           s.taxon_alt,           mc.coll_gbif_pilot, s.specimen_ID, s.Bemerkungen,   mc.source_id, s.det, ' '
                      FROM (tbl_specimens s, tbl_collector c, tbl_tax_species ts, tbl_tax_rank ttr, tbl_management_collections mc)
                       LEFT JOIN tbl_tax_epithets te  ON te.epithetID  = ts.speciesID
                       LEFT JOIN tbl_tax_genera tg ON tg.genID = ts.genID
                       LEFT JOIN tbl_tax_families tf ON tf.familyID = tg.familyID
                       LEFT JOIN tbl_geo_nation gn ON gn.nationID = s.NationID
                      WHERE s.SammlerID = c.SammlerID
                       AND s.taxonID = ts.taxonID
                       AND ts.tax_rankID = ttr.tax_rankID
                       AND s.collectionID = mc.collectionID
                       AND s.accessible > 0
                       AND mc.source_id = '" . $tbl['source_id'] . "'
                       AND (s.specimen_ID IN ($sql_IN) OR s.HerbNummer IS NULL)");

        $sql = "SELECT s.specimen_ID, s.taxonID, s.series_number, s.Nummer, s.alt_number, s.Datum, s.det, s.Bemerkungen,
                 s.Coord_W, s.W_Min, s.W_Sec, s.Coord_N, s.N_Min, s.N_Sec,
                 s.Coord_S, s.S_Min, s.S_Sec, s.Coord_E, s.E_Min, s.E_Sec,
                 s.digital_image, s.observation, s.digital_image_obs, s.HerbNummer,
                 c.Sammler, c2.Sammler_2,
                 ts.taxonID, ts.statusID, tg.genus,
                 ta.author author, ta1.author author1, ta2.author author2, ta3.author author3,
                 ta4.author author4, ta5.author author5,
                 te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
                 te4.epithet epithet4, te5.epithet epithet5,
                 gn.nation_engl, gp.provinz,
                 ss.series,
                 md.copyright, md.ipr, md.rights_url,md.multimedia_object_format,
                 uim.uuid, mc.source_id, mc.collection,
                 ei.filesize
                FROM (tbl_specimens s, tbl_collector c, tbl_tax_species ts, tbl_management_collections mc)
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
                 LEFT JOIN tbl_geo_nation gn ON gn.nationID = s.NationID
                 LEFT JOIN tbl_geo_province gp ON gp.provinceID = s.provinceID
                 LEFT JOIN tbl_specimens_series ss ON ss.seriesID = s.seriesID
                 LEFT JOIN metadb md ON md.source_id_fk = mc.source_id
                 LEFT JOIN jacq_input.srvc_uuid_minter uim ON (uim.internal_id = s.specimen_ID AND uim.uuid_minter_type_id = 3)
                 LEFT JOIN gbif_pilot.europeana_images ei ON ei.specimen_ID = s.specimen_ID 
                WHERE s.SammlerID = c.SammlerID
                 AND s.taxonID = ts.taxonID
                 AND s.collectionID = mc.collectionID
                 AND s.accessible > 0
                 AND mc.source_id = '" . $tbl['source_id'] . "'
                 AND (s.specimen_ID IN ($sql_IN) OR s.HerbNummer IS NULL)";
        $result = $dbLink1->query($sql, MYSQLI_USE_RESULT);
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
                if (strstr($row['alt_number'], "s.n.")) {
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
                if (strstr($row['alt_number'], "s.n.")) {
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
                $image_url = "https://services.jacq.org/jacq-services/rest/images/show/" . $row['specimen_ID'] . "?withredirect=1";
//            $image_url = "http://www.jacq.org/image.php?filename=" . $row['specimen_ID'] . "&method=show";
                if ($tbl['europeana_cache'] && ($row['filesize'] ?? 0) > 1500) {  // use europeana-cache only for images without errors
                    $thumb_url = "https://object.jacq.org/europeana/$sourceCode/{$row['specimen_ID']}.jpg";

                } else {
                    $thumb_url = "https://services.jacq.org/jacq-services/rest/images/europeana/" . $row['specimen_ID'] . "?withredirect=1";
//                $thumb_url = "http://www.jacq.org/image.php?filename=" . $row['specimen_ID'] . "&method=europeana";
                }
            } else {
                $image_url = "";
                $thumb_url = "";
            }

//       $MultimediaIPR = "Image parts provided by this server with the given resolution have been released under Creative Commons CC-BY-SA 3.0 DE licence.";

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
            $rowLog = $dbLink2->query($sql)->fetch_array();
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
            $sql = "UPDATE $dbt." . $tbl['name'] . " SET
                 UnitID                   = " . $dbLink2->quoteString(($row['HerbNummer']) ?: ('JACQ-ID ' . $row['specimen_ID'])) . ",
                 AuthorTeam               = " . $dbLink2->quoteString($AuthorTeam) . ",
                 SecondEpithet            = " . $dbLink2->quoteString($SecondEpithet) . ",
                 HybridFlag               = " . (($row['statusID'] == 1) ? "1" : "NULL") . ",
                 ISODateTimeBegin         = " . $dbLink2->quoteString((trim($row['Datum']) == "s.d.") ? "" : $row['Datum']) . ",
                 NamedAreaName            = " . $dbLink2->quoteString(($row['nation_engl'] == "Austria") ? mb_substr($row['provinz'], 0, 2) : $row['provinz']) . ",
                 NamedAreaClass           = " . (($row['nation_engl'] == "Austria") ? "'Bundesland'" : "NULL") . ",
                 LatitudeDecimal          = " . $dbLink2->quoteString($LatitudeDecimal) . ",
                 LongitudeDecimal         = " . $dbLink2->quoteString($LongitudeDecimal) . ",
                 SpatialDatum             = " . $dbLink2->quoteString($SpatialDatum) . ",
                 CollectorsFieldNumber    = " . $dbLink2->quoteString(trim($row['Nummer'] . ' ' . $row['alt_number'])) . ",
                 GatheringAgentsText      = " . $dbLink2->quoteString($GatheringAgentsText) . ",
                 CollectorTeam            = '" . $dbLink2->real_escape_string($CollectorTeam) . "',
                 IdentificationDate       = " . $dbLink2->quoteString($IdentificationDate) . ",
                 image_url                = " . $dbLink2->quoteString($image_url) . ",
                 thumb_url                = " . $dbLink2->quoteString($thumb_url) . ",
                 MultimediaIPR            = " . (($image_url) ? $dbLink2->quoteString($row['ipr']) : "NULL") . ",
                 copyright                = " . (($image_url) ? $dbLink2->quoteString($row['copyright']) : "NULL") . ",
                 rights_url               = " . (($image_url) ? $dbLink2->quoteString($row['rights_url']) : "NULL") . ",
                 multimedia_object_format = " . (($image_url) ? $dbLink2->quoteString($row['multimedia_object_format']) : "NULL") . ",
                 recordURI                = " . $dbLink2->quoteString($recordURI) . ",
                 LastEditor               = " . $dbLink2->quoteString($LastEditor) . ",
                 DateLastEdited           = " . $dbLink2->quoteString($DateLastEdited) . ",
                 RecordBasis              = " . (($row['observation'] > 0) ? "'HumanObservation'" : "'PreservedSpecimen'") . ",
                 Notes                    = " . ((false) ? $dbLink2->quoteString($row['Bemerkungen']) : "NULL") . "
                WHERE UnitIDNumeric = " . $row['specimen_ID'];
            // TODO: add field "Notes" to fill conditionally with tbl_specimens.Bemerkungen if a flag in "meta" is set
            $dbLink2->query($sql);
        }
        $result->free();
        if ($options['verbose']) {
            echo "---------- $sourceCode ({$tbl['source_id']}) finished (" . date(DATE_RFC822) . ") ----------\n";
        }
    }
}
//                 recordURI = "  . ($row['uuid'] ? $dbLink2->quoteString("http://resolv.jacq.org/" . $row['uuid']) : $dbLink2->quoteString($recordURI)) . ",
