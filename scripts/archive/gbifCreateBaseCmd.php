#!/usr/bin/php -q
<?php
include 'inc/variables.php';

ini_set("max_execution_time", "3600");
ini_set("memory_limit", "256M");

class DB extends mysqli
{
    public function __construct($host, $user, $pass, $db) {
        parent::__construct($host, $user, $pass, $db);

        if (mysqli_connect_error()) {
            die('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
        }

        $this->query("SET character set utf8");
    }

    public function query($query, $resultmode = MYSQLI_STORE_RESULT) {
        $result = parent::query($query, $resultmode);
        if (!$result) {
            echo $query . "\n";
            echo $this->error . "\n";
        }

        return $result;
    }

    public function quoteString($text) {
        if (mb_strlen($text) > 0) {
            return "'" . $this->real_escape_string($text) . "'";
        } else {
            return "NULL";
        }
    }
}

$dbLink1 = new DB($host, $user, $pass, $db);
$dbLink2 = new DB($host, $user, $pass, $db);

$dbLink2->query("TRUNCATE $dbt.meta");
$dbLink2->query("INSERT INTO $dbt.meta
                         (source_id, source_code, source_name, source_update, source_version, source_url, source_expiry, source_number_of_records, source_abbr_engl)
                   SELECT source_id, source_code, source_name, source_update, source_version, source_url, source_expiry, source_number_of_records, source_abbr_engl
                   FROM meta");
$dbLink2->query("TRUNCATE $dbt.metadb");
$dbLink2->query("INSERT INTO $dbt.metadb
                         (db_id, source_id_fk, supplier_supplied_when, supplier_organisation, supplier_organisation_code, supplier_person, supplier_url, supplier_adress, supplier_telephone, supplier_email, legal_owner_organisation, legal_owner_organisation_code, legal_owner_person, legal_owner_adress, legal_owner_telephone, legal_owner_email, legal_owner_url, terms_of_use, acknowledgement, description, disclaimer, restrictions, logo_url, statement_url, copyright, ipr, rights_url)
                   SELECT db_id, source_id_fk, supplier_supplied_when, supplier_organisation, supplier_organisation_code, supplier_person, supplier_url, supplier_adress, supplier_telephone, supplier_email, legal_owner_organisation, legal_owner_organisation_code, legal_owner_person, legal_owner_adress, legal_owner_telephone, legal_owner_email, legal_owner_url, terms_of_use, acknowledgement, description, disclaimer, restrictions, logo_url, statement_url, copyright, ipr, rights_url
                   FROM metadb");

// use $tbls as defined in variables.php
foreach ($tbls as $tbl) {
    $dbLink2->query("TRUNCATE $dbt." . $tbl['name']);
    $dbLink2->query("INSERT INTO $dbt." . $tbl['name'] . "
                            (NameAuthorYearString,                        Genus,    FirstEpithet, Rank,     HigherTaxon, ISODateTimeEnd, LocalityText, LocalityDetailed, CountryName,    ISO3Letter,          MeasurmentLowerValue, MeasurmentUpperValue, exactness,   PrimaryCollector, IdentificationHistory, NamedCollection,    UnitIDNumeric, UnitDescription, source_id_fk, det)
                      SELECT herbar_view.GetScientificName(s.taxonID, 0), tg.genus, te.epithet,   ttr.rank, tf.family,   s.Datum2,       s.Fundort,    s.Fundort,        gn.nation_engl, gn.iso_alpha_3_code, s.altitude_min,       s.altitude_max,       s.exactness, c.Sammler,        s.taxon_alt,           mc.coll_gbif_pilot, s.specimen_ID,  s.Bemerkungen,   mc.source_id, s.det
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
                       AND mc.source_id = '" . $tbl['source_id'] . "'");

    $sql = "SELECT s.specimen_ID, s.taxonID, s.series_number, s.Nummer, s.alt_number, s.Datum,
             s.Coord_W, s.W_Min, s.W_Sec, s.Coord_N, s.N_Min, s.N_Sec,
             s.Coord_S, s.S_Min, s.S_Sec, s.Coord_E, s.E_Min, s.E_Sec,
             s.digital_image, s.observation, s.HerbNummer,
             c.Sammler, c2.Sammler_2,
             ts.taxonID, ts.statusID, tg.genus,
             ta.author author, ta1.author author1, ta2.author author2, ta3.author author3,
             ta4.author author4, ta5.author author5,
             te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
             te4.epithet epithet4, te5.epithet epithet5,
             gn.nation_engl, gp.provinz,
             ss.series
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
            WHERE s.SammlerID = c.SammlerID
             AND s.taxonID = ts.taxonID
             AND s.collectionID = mc.collectionID
             AND s.accessible > 0
             AND mc.source_id = '" . $tbl['source_id'] . "'";
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
            $GatheringAgentsText .= " & ".$row['Sammler_2'];
        }
        if ($row['series_number']) {
            if ($row['Nummer'])                     $GatheringAgentsText .= " "  . $row['Nummer'];
            if ($row['alt_number'])                 $GatheringAgentsText .= " "  . $row['alt_number'];
            if (strstr($row['alt_number'], "s.n.")) $GatheringAgentsText .= " [" . $row['Datum'] . "]";
            if ($row['series'])                     $GatheringAgentsText .= " "  . $row['series'];
            $GatheringAgentsText .= " " . $row['series_number'];
        } else {
            if ($row['series'])                     $GatheringAgentsText .= " "  . $row['series'];
            if ($row['Nummer'])                     $GatheringAgentsText .= " "  . $row['Nummer'];
            if ($row['alt_number'])                 $GatheringAgentsText .= " "  . $row['alt_number'];
            if (strstr($row['alt_number'], "s.n.")) $GatheringAgentsText .= " [" . $row['Datum'] . "]";
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
                $CollectorTeam .= ", ".$row['Sammler_2'];
            } else {
                $CollectorTeam .= " & ".$row['Sammler_2'];
            }
        }

        /**
         * image_url
         */
        if ($row['digital_image']) {
            $image_url = "http://herbarium.univie.ac.at/database/image.php?filename=" . $row['specimen_ID'] . "&method=show";
        } else {
           $image_url = "";
        }

        /**
         * LastEditor
         * DateLastEdited
         */
        $sql = "SELECT u.firstname, u.surname, u.timestamp
                FROM herbarinput_log.log_specimens ls, herbarinput_log.tbl_herbardb_users u
                WHERE ls.userID = u.userID
                 AND ls.specimenID = '" . $row['specimen_ID'] . "'
                ORDER BY u.timestamp DESC";
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
                 UnitID = "                . $dbLink2->quoteString(($row['HerbNummer']) ? $row['HerbNummer'] : ('JACQ-ID ' . $row['specimen_ID'])) . ",
                 AuthorTeam = "            . $dbLink2->quoteString($AuthorTeam) . ",
                 SecondEpithet = "         . $dbLink2->quoteString($SecondEpithet) . ",
                 HybridFlag = "            . (($row['statusID'] == 1) ? "1" : "NULL") . ",
                 ISODateTimeBegin = "      . $dbLink2->quoteString((trim($row['Datum']) == "s.d.") ? "" : $row['Datum']) . ",
                 NamedAreaName = "         . $dbLink2->quoteString(($row['nation_engl'] == "Austria") ? substr($row['provinz'], 0, 2) : $row['provinz']) . ",
                 NamedAreaClass = "        . (($row['nation_engl'] == "Austria") ? "'Bundesland'" : "NULL") . ",
                 LatitudeDecimal = "       . $dbLink2->quoteString($LatitudeDecimal) . ",
                 LongitudeDecimal = "      . $dbLink2->quoteString($LongitudeDecimal) . ",
                 SpatialDatum = "          . $dbLink2->quoteString($SpatialDatum) . ",
                 CollectorsFieldNumber = " . $dbLink2->quoteString(trim($row['Nummer'] . ' ' . $row['alt_number'])) . ",
                 GatheringAgentsText = "   . $dbLink2->quoteString($GatheringAgentsText) . ",
                 CollectorTeam = "         . $dbLink2->quoteString($CollectorTeam) . ",
                 image_url = "             . $dbLink2->quoteString($image_url) . ",
                 LastEditor = "            . $dbLink2->quoteString($LastEditor) . ",
                 DateLastEdited = "        . $dbLink2->quoteString($DateLastEdited) . ",
                 RecordBasis = "           . (($row['observation'] > 0) ? "'HumanObservation'" : "'PreservedSpecimen'") . "
                WHERE UnitIDNumeric = " . $row['specimen_ID'];
        $dbLink2->query($sql);
    }
    $result->free();
}

?>