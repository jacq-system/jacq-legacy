<?php

namespace Jacq;

use Exception;
use mysqli_result;

class Api
{
    /**
     * garbage collection: if tbl_api_specimens is empty -> delete tbl_api_units and tbl_api_units_identifications
     *
     * @param int $id specimen-ID to look for
     * @return void
     */
    public static function garbageCollection (int $id): void
    {
        try {
            $db = DbAccess::ConnectTo('INPUT');
        } catch (Exception) {
            return;
        }

        $result = $db->queryCatch("SELECT specimen_ID FROM api.tbl_api_specimens WHERE specimen_ID = '$id'");
        if ($result->num_rows == 0) {
            $db->queryCatch("DELETE FROM api.tbl_api_units WHERE specimenID = '$id'");
            $db->queryCatch("DELETE FROM api.tbl_api_units_identifications WHERE specimenID_fk = '$id'");
        }
    }

    /**
     * Updates or inserts data into the `api.tbl_api_units` table for a given specimen ID.
     *
     * This method retrieves specimen information from various related tables, processes
     * data (including formatting UnitID, collectors, collector numbers, latitude, and longitude),
     * and then writes the data into `api.tbl_api_units`. It decides whether to perform an insert
     * or update operation based on the presence of the specimen ID in the existing table.
     *
     * @param int $id The specimen ID for which the corresponding data should be updated or inserted.
     * @return bool|mysqli_result Returns the result of the database query, or false if the query failed.
     */
    public static function update_tbl_api_units (int $id): mysqli_result|bool
    {
        try {
            $db = DbAccess::ConnectTo('INPUT');
        } catch (Exception) {
            return false;
        }

        // gather information for the update of tbl_api_units
        $sql = "SELECT specimen_ID, s.aktualdatum, meta.source_id,
                 c.SammlerID, c.Sammler, c2.Sammler_2ID, c2.Sammler_2,
                 s.series_number, s.Nummer, s.alt_number, s.Datum, s.Datum2, ss.series,
                 gn.nation_engl, gn.iso_alpha_2_code,
                 s.Fundort, altitude_min, altitude_max,
                 s.Coord_W, s.W_Min, s.W_Sec, s.Coord_N, s.N_Min, s.N_Sec,
                 s.Coord_S, s.S_Min, s.S_Sec, s.Coord_E, s.E_Min, s.E_Sec,
                 s.Bemerkungen,
                 gp.provinz
                FROM (tbl_specimens s, tbl_collector c, tbl_management_collections mc, meta)
                 LEFT JOIN tbl_collector_2 c2 ON c2.Sammler_2ID = s.Sammler_2ID
                 LEFT JOIN tbl_specimens_series ss ON ss.seriesID = s.seriesID
                 LEFT JOIN tbl_geo_nation gn ON gn.nationID = s.NationID
                 LEFT JOIN tbl_geo_province gp ON gp.provinceID = s.provinceID
                WHERE s.SammlerID = c.SammlerID
                 AND s.collectionID = mc.collectionID
                 AND mc.source_id = meta.source_id
                 AND s.observation = '0'
                 AND specimen_ID = '$id'";
        $row = $db->queryCatch($sql)->fetch_array();

        // fill UnitID
        $UnitID = Tools::formatUnitID($row['specimen_ID']);

        // fill Collectors
        $Collectors = $row['Sammler'];
        if (strstr($row['Sammler_2'],"&") || strstr($row['Sammler_2'],"et al.")) {
            $Collectors .= " et al.";
        } elseif ($row['Sammler_2']) {
            $Collectors .= " & ".$row['Sammler_2'];
        }

        // fill CollectorNumber
        $CollectorNumber = "";
        if ($row['series_number']) {
            if ($row['Nummer']) {
                $CollectorNumber .= " " . $row['Nummer'];
            }
            if ($row['alt_number'] && trim($row['alt_number']) != "s.n.") {
                $CollectorNumber .= " " . $row['alt_number'];
            }
            if ($row['series']) {
                $CollectorNumber .= " " . $row['series'];
            }
            $CollectorNumber .= " " . $row['series_number'];
        } else {
            if ($row['series']) {
                $CollectorNumber .= " " . $row['series'];
            }
            if ($row['Nummer']) {
                $CollectorNumber .= " " . $row['Nummer'];
            }
            if ($row['alt_number']) {
                $CollectorNumber .= " " . $row['alt_number'];
            }
            if (str_contains($row['alt_number'], "s.n.")) {
                $CollectorNumber .= " [" . $row['Datum'] . "]";
            }
        }

        // fill Latitude with decimal numbers
        if ($row['Coord_S'] > 0 || $row['S_Min'] > 0 || $row['S_Sec'] > 0) {
            $lat = -($row['Coord_S'] + $row['S_Min'] / 60 + $row['S_Sec'] / 3600);
        } else if ($row['Coord_N'] > 0 || $row['N_Min'] > 0 || $row['N_Sec'] > 0) {
            $lat = $row['Coord_N'] + $row['N_Min'] / 60 + $row['N_Sec'] / 3600;
        } else {
            $lat = 0;
        }
        $LatitudeDecimal = ($lat) ? sprintf("%1.5f", $lat) : "";

        // fill Longitude with decimal numbers
        if ($row['Coord_W'] > 0 || $row['W_Min'] > 0 || $row['W_Sec'] > 0) {
            $lon = -($row['Coord_W'] + $row['W_Min'] / 60 + $row['W_Sec'] / 3600);
        } else if ($row['Coord_E'] > 0 || $row['E_Min'] > 0 || $row['E_Sec'] > 0) {
            $lon = $row['Coord_E'] + $row['E_Min'] / 60 + $row['E_Sec'] / 3600;
        } else {
            $lon = 0;
        }
        $LongitudeDecimal = ($lon) ? sprintf("%1.5f", $lon) : "";

        $result = $db->queryCatch("SELECT specimenID FROM api.tbl_api_units WHERE specimenID = '$id'");
        if ($result->num_rows == 0) {
            $sql = "INSERT api.tbl_api_units ";
            $sql_tail = ", specimenID = '$id'";
        } else {
            $sql = "UPDATE api.tbl_api_units ";
            $sql_tail = " WHERE specimenID = '$id'";
        }

        // insert or update tbl_api_units
        $sql .= "SET
                  UnitID              = '" . $db->real_escape_string($UnitID) . "',
                  DateLastModified    = '" . $db->real_escape_string($row['aktualdatum']) . "',
                  Collectors          = '" . $db->real_escape_string($Collectors) . "',
                  CollectorNumber     = '" . $db->real_escape_string(trim($CollectorNumber)) . "',
                  CollectionDateBegin = "  . $db->quoteString((trim($row['Datum']) == "s.d." || str_contains($row['Datum'], '#')) ? "" : $row['Datum']) . ",
                  CollectionDateEnd   = "  . $db->quoteString((str_contains($row['Datum2'], '#')) ? "" : $row['Datum2']) . ",
                  CountryName         = "  . $db->quoteString($row['nation_engl']) . ",
                  ISO2Letter          = "  . $db->quoteString($row['iso_alpha_2_code']) . ",
                  Locality            = '" . $db->real_escape_string($row['Fundort']) . "',
                  Altitude_min        = "  . $db->quoteString($row['altitude_min']) . ",
                  Altitude_max        = "  . $db->quoteString($row['altitude_max']) . ",
                  LatitudeDecimal     = "  . $db->quoteString($LatitudeDecimal) . ",
                  LongitudeDecimal    = "  . $db->quoteString($LongitudeDecimal) . ",
                  Notes               = "  . $db->quoteString($row['Bemerkungen']) . ",
                  source_id_fk        = "  . $db->quoteString($row['source_id']) . ",
                  ProvinceName        = "  . $db->quoteString($row['provinz']) .
                $sql_tail;

        return($db->queryCatch($sql));
    }

    /**
     * Updates the 'tbl_api_units_identifications' table based on the provided specimen ID.
     * Deletes old entries, processes data from various related tables, and inserts new entries accordingly.
     *
     * @param int $id The specimen ID used to identify the relevant data for processing.
     * @return void
     */
    public static function update_tbl_api_units_identifications (int $id): void
    {
        try {
            $db = DbAccess::ConnectTo('INPUT');
        } catch (Exception) {
            return;
        }

        // delete old entries
        $db->queryCatch("DELETE FROM api.tbl_api_units_identifications WHERE specimenID_fk = '$id'");

        // gather information for the first insert into tbl_api_units_identifications
        $sql = "SELECT s.taxonID, s.det, s.typified, s.taxon_alt, ts.basID,
                 tg.genus, tf.family, ty.typus_api_standard,
                 ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
                 ta4.author author4, ta5.author author5,
                 te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
                 te4.epithet epithet4, te5.epithet epithet5,
                 tr.rank
                FROM (tbl_specimens s, tbl_tax_species ts, tbl_tax_genera tg, tbl_tax_families tf)
                 LEFT JOIN tbl_typi ty ON s.typusID = ty.typusID
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
                 LEFT JOIN tbl_tax_rank tr ON tr.tax_rankID = ts.tax_rankID
                WHERE s.taxonID = ts.taxonID
                 AND ts.genID = tg.genID
                 AND tg.familyID = tf.familyID
                 AND s.observation = '0'
                 AND specimen_ID = '$id'";
        $row = $db->queryCatch($sql)->fetch_array();

        $Typestatus = $row['typus_api_standard'];
        $result = $db->queryCatch("SELECT specimens_types_ID
                                   FROM tbl_specimens_types
                                   WHERE taxonID = '" . $row['taxonID'] . "'
                                    AND specimenID = '$id'");
        if ($result->num_rows == 0) {
            $result = $db->queryCatch("SELECT specimens_types_ID
                                       FROM tbl_specimens_types
                                       WHERE taxonID = '" . $row['basID'] . "'
                                        AND specimenID = '$id'");
            if ($result->num_rows == 0) {
                $Typestatus = "";
                $exist_in_specimens_types = 'false';
            } else {
                $exist_in_specimens_types = 'true';
            }
        } else {
            $exist_in_specimens_types = 'true';
        }

        $work = $row['det'];
        if (intval(substr($work, -2, 2)) != 0) {
            if (substr($work, -3, 1) != "-") {
                $date = substr($work, -4, 4);
                $cut = -5;
            } else if (substr($work, -6, 1) != "-") {
                $date = substr($work, -7, 7);
                $cut = -8;
            } else {
                $date = substr($work, -10, 10);
                $cut = -11;
            }
            $det = substr($row['det'], 0, $cut);
        } else {
            $date = "";
            $det = $row['det'];
        }


        // do the first insert
        self::helper_tbl_api_units_identifications($id, $row, $det, $date, $row['taxon_alt'], $Typestatus, 'true', $exist_in_specimens_types);

        // gather information for the other inserts into tbl_api_units_identifications
        $sql = "SELECT st.taxonID, st.typified_by_Person, st.typified_Date,
                 tg.genus, tf.family, ty.typus_api_standard,
                 ta.author, ta1.author author1, ta2.author author2, ta3.author author3,
                 ta4.author author4, ta5.author author5,
                 te.epithet, te1.epithet epithet1, te2.epithet epithet2, te3.epithet epithet3,
                 te4.epithet epithet4, te5.epithet epithet5,
                 tr.rank
                FROM (tbl_specimens_types st, tbl_tax_species ts, tbl_tax_genera tg, tbl_tax_families tf)
                 LEFT JOIN tbl_typi ty ON st.typusID=ty.typusID
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
                 LEFT JOIN tbl_tax_rank tr ON tr.tax_rankID=ts.tax_rankID
                WHERE st.taxonID=ts.taxonID
                 AND ts.genID=tg.genID
                 AND tg.familyID=tf.familyID
                 AND specimenID='$id'
                 AND st.taxonID!='".$row['taxonID']."'";
        $result2 = $db->queryCatch($sql);
        while ($row = $result2->fetch_array()) {
            self::helper_tbl_api_units_identifications(
                $id,
                $row,
                $row['typified_by_Person'],
                $row['typified_Date'],
                "",
                $row['typus_api_standard'],
                'false',
                'true');  // do the other inserts (if any)
        }
    }

    /*********************\
    |                     |
    |  private functions  |
    |                     |
    \*********************/

    /**
     * Inserts a new record into the `tbl_api_units_identifications` table with the given specimen and taxonomic details.
     *
     * @param int $id The specimen ID to associate with the identification record.
     * @param array $row An associative array containing taxonomic and bibliographic data for the specimen.
     * @param string $det The identifier (e.g., person or source) responsible for the identification.
     * @param string $date The date of identification in a valid date format.
     * @param string $identHistory The history of other scientific names or identifications associated with the specimen.
     * @param string $Typestatus The type status of the specimen (e.g., holotype, syntype).
     * @param string $StoredUnderName The name under which the specimen is stored.
     * @param string $TypifiedName The typified name of the specimen.
     * @return void
     */
    private static function helper_tbl_api_units_identifications (int $id, array $row, string $det, string $date, string $identHistory, string $Typestatus, string $StoredUnderName, string $TypifiedName): void
    {
        try {
            $db = DbAccess::ConnectTo('INPUT');
        } catch (Exception) {
            return;
        }

        $rank = $row['rank'];
        if (strlen($row['epithet5']) > 0) {
            $Infra_specificAuthor = $row['author5'];
            $Infra_specificEpithet = $row['epithet5'];
        } elseif (strlen($row['epithet4']) > 0) {
            $Infra_specificAuthor = $row['author4'];
            $Infra_specificEpithet = $row['epithet4'];
        } elseif (strlen($row['epithet3']) > 0) {
            $Infra_specificAuthor = $row['author3'];
            $Infra_specificEpithet = $row['epithet3'];
        } elseif (strlen($row['epithet2']) > 0) {
            $Infra_specificAuthor = $row['author2'];
            $Infra_specificEpithet = $row['epithet2'];
        } elseif (strlen($row['epithet1']) > 0) {
            $Infra_specificAuthor = $row['author1'];
            $Infra_specificEpithet = $row['epithet1'];
        } else {
            if (strlen($row['genus']) > 0 && strlen($row['epithet']) > 0 && strlen($row['author']) > 0) {
                $rank = "";
            }
            $Infra_specificAuthor = $Infra_specificEpithet = "";
        }

        $result = $db->queryCatch("SELECT paginae, figures,
                                    l.suptitel, le.autor as editor, la.autor, l.periodicalID, lp.periodical,
                                    l.vol, l.part, l.jahr, l.bestand
                                   FROM tbl_tax_index ti
                                    LEFT JOIN tbl_lit l ON l.citationID = ti.citationID
                                    LEFT JOIN tbl_lit_periodicals lp ON lp.periodicalID = l.periodicalID
                                    LEFT JOIN tbl_lit_authors le ON le.autorID = l.editorsID
                                    LEFT JOIN tbl_lit_authors la ON la.autorID = l.autorID
                                   WHERE taxonID = '" . $db->real_escape_string($row['taxonID']) . "'");
        $row_citation = $result->fetch_array();
        if ($row_citation) {
            $citation = $row_citation['autor'] . " (" . substr($row_citation['jahr'], 0, 4) . ")";
            if ($row_citation['suptitel']) {
                $citation .= " in " . $row_citation['editor'] . ": " . $row_citation['suptitel'];
            }
            if ($row_citation['periodicalID']) {
                $citation .= " " . $row_citation['periodical'];
            }
            $citation .= " " . $row_citation['vol'];
            if ($row_citation['part']) {
                $citation .= " (" . $row_citation['part'] . ")";
            }
            $citation .= ": " . $row_citation['paginae'] . ". " . $row_citation['figures'];

            if (strlen($row_citation['bestand']) == 0 || $row_citation['bestand'] == 'missing' || $row_citation['bestand'] == 'open') {
                $checked = 'false';
            } else {
                $checked = 'true';
            }
        } else {
            $citation = '';
            $checked = 'false';
        }

        // insert into tbl_api_units_identifications
        $db->queryCatch("INSERT INTO api.tbl_api_units_identifications SET
                          specimenID_fk         = '$id',
                          Family                = '" . $db->real_escape_string($row['family']) . "',
                          Genus                 = '" . $db->real_escape_string($row['genus']) . "',
                          Species               = '" . $db->real_escape_string($row['epithet']) . "',
                          Author                = '" . $db->real_escape_string($row['author']) . "',
                          Infra_specificRank    = '" . $db->real_escape_string($rank) . "',
                          Infra_specificEpithet = '" . $db->real_escape_string($Infra_specificEpithet) . "',
                          Infra_specificAuthor  = '" . $db->real_escape_string($Infra_specificAuthor) . "',
                          Identifier            = '" . $db->real_escape_string($det) . "',
                          IdentificationDate    = '" . $db->real_escape_string($date) . "',
                          Typestatus            = '" . $db->real_escape_string($Typestatus) . "',
                          OtherScientificNames  = '" . $db->real_escape_string($identHistory) . "',
                          Protologue_citation   = '" . $db->real_escape_string($citation) . "',
                          Protologue_checked    = '$checked',
                          StoredUnderName       = '$StoredUnderName',
                          TypifiedName          = '$TypifiedName'");
    }

}
