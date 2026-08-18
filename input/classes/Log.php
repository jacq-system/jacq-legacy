<?php

namespace Jacq;

use Exception;

class Log
{
    public static function tbl_tax_synonymy (int|string $id, int $updated): void
    {
        $id = intval($id);

        try {
            $db = DbAccess::ConnectTo('INPUT');

            $row = $db->query("SELECT * FROM herbarinput.tbl_tax_synonymy where tax_syn_ID ='$id' limit 1")->fetch_array();
            $db->query("INSERT INTO herbarinput_log.log_tbl_tax_synonymy SET
                         tax_syn_ID         = " . $db->quoteString($row['tax_syn_ID']) . ",
                         taxonID            = " . $db->quoteString($row['taxonID']) . ",
                         acc_taxon_ID       = " . $db->quoteString($row['acc_taxon_ID']) . ",
                         ref_date           = " . $db->quoteString($row['ref_date']) . ",
                         preferred_taxonomy = " . $db->quoteString($row['preferred_taxonomy']) . ",
                         annotations        = " . $db->quoteString($row['annotations']) . ",
                         locked             = " . $db->quoteString($row['locked']) . ",
                         source             = " . $db->quoteString($row['source']) . ",
                         source_citationID  = " . $db->quoteString($row['source_citationID']) . ",
                         source_person_ID   = " . $db->quoteString($row['source_person_ID']) . ",
                         source_serviceID   = " . $db->quoteString($row['source_serviceID']) . ",
                         source_specimenID  = " . $db->quoteString($row['source_specimenID']) . ",
                         userID             = " . $db->quoteString($_SESSION['uid']) . ",
                         updated            = " . $db->quoteString($updated));
        } catch (Exception $e) {
            error_log("SEVERE SQL-ERROR IN CLASS. USER-ID = {$_SESSION['uid']}\n" . $e->__toString());
        }
    }


    public static function collector(int|string $id, int $updated): void
    {
        $id = intval($id);

        try {
            $db = DbAccess::ConnectTo('INPUT');

            $db->query("INSERT INTO herbarinput_log.log_collector
                         (SammlerID, Sammler, Sammler_FN_List, Sammler_FN_short, HUH_ID, VIAF_ID, WIKIDATA_ID, ORCID, locked, Bloodhound_ID, userID, updated)
                        SELECT SammlerID, Sammler, Sammler_FN_List, Sammler_FN_short, HUH_ID, VIAF_ID, WIKIDATA_ID, ORCID, locked, Bloodhound_ID, 
                         " . $db->quoteString($_SESSION['uid']) . ", $updated
                        FROM tbl_collector
                        WHERE SammlerID = $id");
        } catch (Exception $e) {
            error_log("SEVERE SQL-ERROR IN CLASS. USER-ID = {$_SESSION['uid']}\n" . $e->__toString());
        }
    }


    public static function specimen (int|string $ID, int $updated): void
    {
        $ID = intval($ID);

        try {
            $db = DbAccess::ConnectTo('INPUT');

            if ($updated) {
                $row = $db->query("SELECT * FROM tbl_specimens WHERE specimen_ID = $ID")->fetch_array();
                $sql = "INSERT INTO herbarinput_log.log_specimens SET
                         specimenID        = " . $db->quoteString($ID) . ",
                         userID            = " . $db->quoteString($_SESSION['uid']) . ",
                         updated           = " . $db->quoteString($updated) . ",
                         timestamp         = NULL,
                         HerbNummer        = " . $db->quoteString($row['HerbNummer']) . ",
                         collectionID      = " . $db->quoteString($row['collectionID']) . ",
                         CollNummer        = " . $db->quoteString($row['CollNummer']) . ",
                         identstatusID     = " . $db->quoteString($row['identstatusID']) . ",
                         checked           = " . $db->quoteString($row['checked']) . ",
                         `accessible`      = " . $db->quoteString($row['accessible']) . ",
                         taxonID           = " . $db->quoteString($row['taxonID']) . ",
                         SammlerID         = " . $db->quoteString($row['SammlerID']) . ",
                         Sammler_2ID       = " . $db->quoteString($row['Sammler_2ID']) . ",
                         seriesID          = " . $db->quoteString($row['seriesID']) . ",
                         series_number     = " . $db->quoteString($row['series_number']) . ",
                         Nummer            = " . $db->quoteString($row['Nummer']) . ",
                         alt_number        = " . $db->quoteString($row['alt_number']) . ",
                         Datum             = " . $db->quoteString($row['Datum']) . ",
                         Datum2            = " . $db->quoteString($row['Datum2']) . ",
                         det               = " . $db->quoteString($row['det']) . ",
                         typified          = " . $db->quoteString($row['typified']) . ",
                         typusID           = " . $db->quoteString($row['typusID']) . ",
                         taxon_alt         = " . $db->quoteString($row['taxon_alt']) . ",
                         NationID          = " . $db->quoteString($row['NationID']) . ",
                         provinceID        = " . $db->quoteString($row['provinceID']) . ",
                         Bezirk            = " . $db->quoteString($row['Bezirk']) . ",
                         Coord_W           = " . $db->quoteString($row['Coord_W']) . ",
                         W_Min             = " . $db->quoteString($row['W_Min']) . ",
                         W_Sec             = " . $db->quoteString($row['W_Sec']) . ",
                         Coord_N           = " . $db->quoteString($row['Coord_N']) . ",
                         N_Min             = " . $db->quoteString($row['N_Min']) . ",
                         N_Sec             = " . $db->quoteString($row['N_Sec']) . ",
                         Coord_S           = " . $db->quoteString($row['Coord_S']) . ",
                         S_Min             = " . $db->quoteString($row['S_Min']) . ",
                         S_Sec             = " . $db->quoteString($row['S_Sec']) . ",
                         Coord_E           = " . $db->quoteString($row['Coord_E']) . ",
                         E_Min             = " . $db->quoteString($row['E_Min']) . ",
                         E_Sec             = " . $db->quoteString($row['E_Sec']) . ",
                         quadrant          = " . $db->quoteString($row['quadrant']) . ",
                         quadrant_sub      = " . $db->quoteString($row['quadrant_sub']) . ",
                         exactness         = " . $db->quoteString($row['exactness']) . ",
                         altitude_min      = " . $db->quoteString($row['altitude_min']) . ",
                         altitude_max      = " . $db->quoteString($row['altitude_max']) . ",
                         Fundort           = " . $db->quoteString($row['Fundort']) . ",
                         habitat           = " . $db->quoteString($row['habitat']) . ",
                         habitus           = " . $db->quoteString($row['habitus']) . ",
                         Bemerkungen       = " . $db->quoteString($row['Bemerkungen']) . ",
                         notes_internal    = " . $db->quoteString($row['notes_internal']) . ",
                         aktualdatum       = " . $db->quoteString($row['aktualdatum']) . ",
                         eingabedatum      = " . $db->quoteString($row['eingabedatum']) . ",
                         digital_image     = " . $db->quoteString($row['digital_image']) . ",
                         garten            = " . $db->quoteString($row['garten']) . ",
                         voucherID         = " . $db->quoteString($row['voucherID']) . ",
                         ncbi_accession    = " . $db->quoteString($row['ncbi_accession']) . ",
                         foreign_db_ID     = " . $db->quoteString($row['foreign_db_ID']) . ",
                         label             = " . $db->quoteString($row['label']) . ",
                         observation       = " . $db->quoteString($row['observation']) . ",
                         digital_image_obs = " . $db->quoteString($row['digital_image_obs']);
            } else {
                $sql = "INSERT INTO herbarinput_log.log_specimens SET
                         specimenID = " . $db->quoteString($ID) . ",
                         userID     = " . $db->quoteString($_SESSION['uid']) . ",
                         updated    = " . $db->quoteString($updated) . ",
                         timestamp  = NULL";
            }
            $db->query($sql);
        } catch (Exception $e) {
            error_log("SEVERE SQL-ERROR IN CLASS. USER-ID = {$_SESSION['uid']}\n" . $e->__toString());
        }
    }


    public static function specimensTypes (int|string $ID, int $updated): void
    {
        $ID = intval($ID);

        try {
            $db = DbAccess::ConnectTo('INPUT');

            $row = $db->query("SELECT * FROM tbl_specimens_types WHERE specimens_types_ID = $ID")->fetch_array();
                $db->query("INSERT INTO herbarinput_log.log_specimens_types SET
                             specimens_types_ID = " . $db->quoteString($ID) . ",
                             taxonID            = " . $db->quoteString($row['taxonID']) . ",
                             specimenID         = " . $db->quoteString($row['specimenID']) . ",
                             typusID            = " . $db->quoteString($row['typusID']) . ",
                             annotations        = " . $db->quoteString($row['annotations']) . ",
                             userID             = " . $db->quoteString($_SESSION['uid']) . ",
                             updated            = " . $db->quoteString($updated) . ",
                             timestamp          = NULL");
        } catch (Exception $e) {
            error_log("SEVERE SQL-ERROR IN CLASS. USER-ID = {$_SESSION['uid']}\n" . $e->__toString());
        }
    }


    public static function specimensSeries (int|string $ID, int $updated): void
    {
        $ID = intval($ID);

        try {
            $db = DbAccess::ConnectTo('INPUT');

            $row = $db->query("SELECT * FROM tbl_specimens_series WHERE seriesID = $ID")->fetch_array();
            $db->query("INSERT INTO herbarinput_log.log_specimens_series SET
                         seriesID  = " . $db->quoteString($row['seriesID']) . ",
                         series    = " . $db->quoteString($row['series'])   . ",
                         locked    = " . $db->quoteString($row['locked'])   . ",
                         userID    = " . $db->quoteString($_SESSION['uid']) . ",
                         updated   = " . $db->quoteString($updated) . ",
                         timestamp = NULL");
        } catch (Exception $e) {
            error_log("SEVERE SQL-ERROR IN CLASS. USER-ID = {$_SESSION['uid']}\n" . $e->__toString());
        }
    }


    public static function authors (int|string $ID, int $updated): void
    {
        $ID = intval($ID);

        try {
            $db = DbAccess::ConnectTo('INPUT');

            $row = $db->query("SELECT * FROM tbl_tax_authors WHERE authorID = $ID")->fetch_array();
            $db->query("INSERT INTO herbarinput_log.log_tax_authors SET
                         authorID            = " . $db->quoteString($ID) . ",
                         author              = " . $db->quoteString($row['author']) . ",
                         Brummit_Powell_full = " . $db->quoteString($row['Brummit_Powell_full']) . ",
                         userID              = " . $db->quoteString($_SESSION['uid']) . ",
                         updated             = " . $db->quoteString($updated) . ",
                         timestamp           = NULL");
        } catch (Exception $e) {
            error_log("SEVERE SQL-ERROR IN CLASS. USER-ID = {$_SESSION['uid']}\n" . $e->__toString());
        }
    }


    public static function families (int|string $ID, int $updated): void
    {
        $ID = intval($ID);

        try {
            $db = DbAccess::ConnectTo('INPUT');

            $row = $db->query("SELECT * FROM tbl_tax_families WHERE familyID = $ID")->fetch_array();
            $db->query("INSERT INTO herbarinput_log.log_tax_families SET
                         familyID   = " . $db->quoteString($ID) . ",
                         family     = " . $db->quoteString($row['family']) . ",
                         categoryID = " . $db->quoteString($row['categoryID']) . ",
                         userID     = " . $db->quoteString($_SESSION['uid']) . ",
                         updated    = " . $db->quoteString($updated) . ",
                         timestamp  = NULL");
        } catch (Exception $e) {
            error_log("SEVERE SQL-ERROR IN CLASS. USER-ID = {$_SESSION['uid']}\n" . $e->__toString());
        }
    }


    public static function genera (int|string $ID, int $updated): void
    {
        $ID = intval($ID);

        try {
            $db = DbAccess::ConnectTo('INPUT');

            $row = $db->query("SELECT * FROM tbl_tax_genera WHERE genID = $ID")->fetch_array();
            $db->query("INSERT INTO herbarinput_log.log_tax_genera SET
                         genID               = " . $db->quoteString($ID) . ",
                         genID_old           = " . $db->quoteString($row['genID_old']) . ",
                         genus               = " . $db->quoteString($row['genus']) . ",
                         DallaTorreIDs       = " . $db->quoteString($row['DallaTorreIDs']) . ",
                         DallaTorreZusatzIDs = " . $db->quoteString($row['DallaTorreZusatzIDs']) . ",
                         genID_inc0406       = " . $db->quoteString($row['genID_inc0406']) . ",
                         hybrid              = " . $db->quoteString($row['hybrid']) . ",
                         familyID            = " . $db->quoteString($row['familyID']) . ",
                         remarks             = " . $db->quoteString($row['remarks']) . ",
                         accepted            = " . $db->quoteString($row['accepted']) . ",
                         userID              = " . $db->quoteString($_SESSION['uid']) . ",
                         updated             = " . $db->quoteString($updated) . ",
                         timestamp           = NULL");
        } catch (Exception $e) {
            error_log("SEVERE SQL-ERROR IN CLASS. USER-ID = {$_SESSION['uid']}\n" . $e->__toString());
        }
    }


    public static function index (int|string $ID, int $updated): void
    {
        $ID = intval($ID);

        try {
            $db = DbAccess::ConnectTo('INPUT');

            $row = $db->query("SELECT * FROM tbl_tax_index WHERE taxindID = $ID")->fetch_array();
            $db->query("INSERT INTO herbarinput_log.log_tax_index SET
                         taxindID    = " . $db->quoteString($ID) . ",
                         taxonID     = " . $db->quoteString($row['taxonID']) . ",
                         citationID  = " . $db->quoteString($row['citationID']) . ",
                         paginae     = " . $db->quoteString($row['paginae']) . ",
                         figures     = " . $db->quoteString($row['figures']) . ",
                         annotations = " . $db->quoteString($row['annotations']) . ",
                         userID      = " . $db->quoteString($_SESSION['uid']) . ",
                         updated     = " . $db->quoteString($updated) . ",
                         timestamp   = NULL");
        } catch (Exception $e) {
            error_log("SEVERE SQL-ERROR IN CLASS. USER-ID = {$_SESSION['uid']}\n" . $e->__toString());
        }
    }


    public static function species (int|string $ID, int $updated): void
    {
        $ID = intval($ID);

        try {
            $db = DbAccess::ConnectTo('INPUT');

            $row = $db->query("SELECT * FROM tbl_tax_species WHERE taxonID = $ID")->fetch_array();
            $db->query("INSERT INTO herbarinput_log.log_tax_species SET
                         taxonID             = " . $db->quoteString($ID) . ",
                         tax_rankID          = " . $db->quoteString($row['tax_rankID']) . ",
                         basID               = " . $db->quoteString($row['basID']) . ",
                         synID               = " . $db->quoteString($row['synID']) . ",
                         statusID            = " . $db->quoteString($row['statusID']) . ",
                         genID               = " . $db->quoteString($row['genID']) . ",
                         speciesID           = " . $db->quoteString($row['speciesID']) . ",
                         authorID            = " . $db->quoteString($row['authorID']) . ",
                         subspeciesID        = " . $db->quoteString($row['subspeciesID']) . ",
                         subspecies_authorID = " . $db->quoteString($row['subspecies_authorID']) . ",
                         varietyID           = " . $db->quoteString($row['varietyID']) . ",
                         variety_authorID    = " . $db->quoteString($row['variety_authorID']) . ",
                         subvarietyID        = " . $db->quoteString($row['subvarietyID']) . ",
                         subvariety_authorID = " . $db->quoteString($row['subvariety_authorID']) . ",
                         formaID             = " . $db->quoteString($row['formaID']) . ",
                         forma_authorID      = " . $db->quoteString($row['forma_authorID']) . ",
                         subformaID          = " . $db->quoteString($row['subformaID']) . ",
                         subforma_authorID   = " . $db->quoteString($row['subforma_authorID']) . ",
                         annotation          = " . $db->quoteString($row['annotation']) . ",
                         userID              = " . $db->quoteString($_SESSION['uid']) . ",
                         updated             = " . $db->quoteString($updated) . ",
                         timestamp           = NULL");
        } catch (Exception $e) {
            error_log("SEVERE SQL-ERROR IN CLASS. USER-ID = {$_SESSION['uid']}\n" . $e->__toString());
        }
    }


    public static function typecollections (int|string $ID, int $updated): void
    {
        $ID = intval($ID);

        try {
            $db = DbAccess::ConnectTo('INPUT');

            $row = $db->query("SELECT * FROM tbl_tax_typecollections WHERE typecollID = $ID")->fetch_array();
            $db->query("INSERT INTO herbarinput_log.log_tax_typecollections SET
                         typecollID       = " . $db->quoteString($ID) . ",
                         taxonID          = " . $db->quoteString($row['taxonID']) . ",
                         SammlerID        = " . $db->quoteString($row['SammlerID']) . ",
                         Sammler_2ID      = " . $db->quoteString($row['Sammler_2ID']) . ",
                         series           = " . $db->quoteString($row['series']) . ",
                         leg_nr           = " . $db->quoteString($row['leg_nr']) . ",
                         alternate_number = " . $db->quoteString($row['alternate_number']) . ",
                         date             = " . $db->quoteString($row['date']) . ",
                         duplicates       = " . $db->quoteString($row['duplicates']) . ",
                         annotation       = " . $db->quoteString($row['annotation']) . ",
                         userID           = " . $db->quoteString($_SESSION['uid']) . ",
                         updated          = " . $db->quoteString($updated) . ",
                         timestamp        = NULL");
        } catch (Exception $e) {
            error_log("SEVERE SQL-ERROR IN CLASS. USER-ID = {$_SESSION['uid']}\n" . $e->__toString());
        }
    }


    public static function lit (int|string $ID, int $updated): void
    {
        $ID = intval($ID);

        try {
            $db = DbAccess::ConnectTo('INPUT');

            $row = $db->query("SELECT * FROM tbl_lit WHERE citationID = $ID")->fetch_array();
            $db->query("INSERT INTO herbarinput_log.log_lit SET
                         citationID   = " . $db->quoteString($ID) . ",
                         lit_url      = " . $db->quoteString($row['lit_url']) . ",
                         autorID      = " . $db->quoteString($row['autorID']) . ",
                         jahr         = " . $db->quoteString($row['jahr']) . ",
                         code         = " . $db->quoteString($row['code']) . ",
                         titel        = " . $db->quoteString($row['titel']) . ",
                         suptitel     = " . $db->quoteString($row['suptitel']) . ",
                         editorsID    = " . $db->quoteString($row['editorsID']) . ",
                         periodicalID = " . $db->quoteString($row['periodicalID']) . ",
                         vol          = " . $db->quoteString($row['vol']) . ",
                         part         = " . $db->quoteString($row['part']) . ",
                         pp           = " . $db->quoteString($row['pp']) . ",
                         publisherID  = " . $db->quoteString($row['publisherID']) . ",
                         verlagsort   = " . $db->quoteString($row['verlagsort']) . ",
                         keywords     = " . $db->quoteString($row['keywords']) . ",
                         annotation   = " . $db->quoteString($row['annotation']) . ",
                         additions    = " . $db->quoteString($row['additions']) . ",
                         bestand      = " . $db->quoteString($row['bestand']) . ",
                         signature    = " . $db->quoteString($row['signature']) . ",
                         publ         = " . $db->quoteString($row['publ']) . ",
                         category     = " . $db->quoteString($row['category']) . ",
                         userID       = " . $db->quoteString($_SESSION['uid']) . ",
                         updated      = " . $db->quoteString($updated) . ",
                         timestamp    = NULL");
        } catch (Exception $e) {
            error_log("SEVERE SQL-ERROR IN CLASS. USER-ID = {$_SESSION['uid']}\n" . $e->__toString());
        }
    }


    public static function litTax (int|string $ID, int $updated): void
    {
        $ID = intval($ID);

        try {
            $db = DbAccess::ConnectTo('INPUT');

            $row = $db->query("SELECT * FROM tbl_lit_taxa WHERE lit_tax_ID = $ID")->fetch_array();
            $db->query("INSERT INTO herbarinput_log.log_lit_taxa SET
                         lit_tax_ID        = " . $db->quoteString($ID) . ",
                         citationID        = " . $db->quoteString($row['citationID']) . ",
                         taxonID           = " . $db->quoteString($row['taxonID']) . ",
                         acc_taxon_ID      = " . $db->quoteString($row['acc_taxon_ID']) . ",
                         annotations       = " . $db->quoteString($row['annotations']) . ",
                         locked            = " . $db->quoteString($row['locked']) . ",
                         source            = " . $db->quoteString($row['source']) . ",
                         source_citationID = " . $db->quoteString($row['source_citationID']) . ",
                         source_person_ID  = " . $db->quoteString($row['source_person_ID']) . ",
                         et_al             = " . $db->quoteString($row['et_al']) . ",
                         userID            = " . $db->quoteString($_SESSION['uid']) . ",
                         updated           = " . $db->quoteString($updated) . ",
                         timestamp         = NULL");
        } catch (Exception $e) {
            error_log("SEVERE SQL-ERROR IN CLASS. USER-ID = {$_SESSION['uid']}\n" . $e->__toString());
        }
    }


    public static function litAuthors (int|string $ID, int $updated): void
    {
        $ID = intval($ID);

        try {
            $db = DbAccess::ConnectTo('INPUT');

            $row = $db->query("SELECT * FROM tbl_lit_authors WHERE autorID = $ID")->fetch_array();
            $db->query("INSERT INTO herbarinput_log.log_lit_authors SET
                         autorID      = " . $db->quoteString($ID) . ",
                         autor        = " . $db->quoteString($row['autor']) . ",
                         autorsystbot = " . $db->quoteString($row['autorsystbot']) . ",
                         userID       = " . $db->quoteString($_SESSION['uid']) . ",
                         updated      = " . $db->quoteString($updated) . ",
                         timestamp    = NULL");
        } catch (Exception $e) {
            error_log("SEVERE SQL-ERROR IN CLASS. USER-ID = {$_SESSION['uid']}\n" . $e->__toString());
        }
    }


    public static function litPeriodicals (int|string $ID, int $updated): void
    {
        $ID = intval($ID);

        try {
            $db = DbAccess::ConnectTo('INPUT');

            $row = $db->query("SELECT * FROM tbl_lit_periodicals WHERE periodicalID = $ID")->fetch_array();
            $db->query("INSERT INTO herbarinput_log.log_lit_periodicals SET
                         periodicalID    = " . $db->quoteString($ID) . ",
                         periodical      = " . $db->quoteString($row['periodical']) . ",
                         periodical_full = " . $db->quoteString($row['periodical_full']) . ",
                         tl2_number      = " . $db->quoteString($row['tl2_number']) . ",
                         bph_number      = " . $db->quoteString($row['bph_number']) . ",
                         ipni_ID         = " . $db->quoteString($row['ipni_ID']) . ",
                         userID          = " . $db->quoteString($_SESSION['uid']) . ",
                         updated         = " . $db->quoteString($updated) . ",
                         timestamp       = NULL");
        } catch (Exception $e) {
            error_log("SEVERE SQL-ERROR IN CLASS. USER-ID = {$_SESSION['uid']}\n" . $e->__toString());
        }
    }


    public static function litPublishers (int|string $ID, int $updated): void
    {
        $ID = intval($ID);

        try {
            $db = DbAccess::ConnectTo('INPUT');

            $row = $db->query("SELECT * FROM tbl_lit_publishers WHERE publisherID = $ID")->fetch_array();
            $db->query("INSERT INTO herbarinput_log.log_lit_publishers  SET
                         publisherID = " . $db->quoteString($ID) . ",
                         publisher   = " . $db->quoteString($row['publisher']) . ",
                         userID      = " . $db->quoteString($_SESSION['uid']) . ",
                         updated     = " . $db->quoteString($updated) . ",
                         timestamp   = NULL");
        } catch (Exception $e) {
            error_log("SEVERE SQL-ERROR IN CLASS. USER-ID = {$_SESSION['uid']}\n" . $e->__toString());
        }
    }
}
