-- phpMyAdmin SQL Dump
-- version 3.3.9
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 23. September 2011 um 01:26
-- Server Version: 5.0.41
-- PHP-Version: 5.3.4

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Datenbank: `herbarinput_log`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_commonnames_tbl_names`
--

DROP TABLE IF EXISTS `log_commonnames_tbl_names`;
CREATE TABLE IF NOT EXISTS `log_commonnames_tbl_names` (
  `log_name_id` int(11) NOT NULL auto_increment,
  `name_id` int(11) NOT NULL,
  `transliteration_id` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `updated` int(4) NOT NULL,
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`log_name_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=7 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_commonnames_tbl_name_applies_to`
--

DROP TABLE IF EXISTS `log_commonnames_tbl_name_applies_to`;
CREATE TABLE IF NOT EXISTS `log_commonnames_tbl_name_applies_to` (
  `logID` int(11) NOT NULL auto_increment,
  `geonameId` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `period_id` int(11) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `reference_id` int(11) NOT NULL,
  `name_id` int(11) NOT NULL,
  `tribe_id` int(11) default NULL,
  `oldid` varchar(100) NOT NULL,
  `geospecification` text NOT NULL,
  `annotations` text,
  `locked` tinyint(4) default NULL,
  `userID` int(11) NOT NULL,
  `updated` int(4) NOT NULL,
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`logID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=304 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_commonnames_tbl_name_commons`
--

DROP TABLE IF EXISTS `log_commonnames_tbl_name_commons`;
CREATE TABLE IF NOT EXISTS `log_commonnames_tbl_name_commons` (
  `log_common_id` int(11) NOT NULL auto_increment,
  `common_id` int(11) NOT NULL,
  `common_name` varchar(255) NOT NULL,
  `locked` tinyint(4) NOT NULL,
  `userID` int(11) NOT NULL,
  `updated` int(4) NOT NULL,
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`log_common_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=57 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_commonnames_tbl_name_names_equals`
--

DROP TABLE IF EXISTS `log_commonnames_tbl_name_names_equals`;
CREATE TABLE IF NOT EXISTS `log_commonnames_tbl_name_names_equals` (
  `names_equals_id` int(11) NOT NULL auto_increment,
  `tbl_name_names_name_id` int(11) NOT NULL,
  `tbl_name_names_name_id1` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `updated` int(4) NOT NULL,
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`names_equals_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_lit`
--

DROP TABLE IF EXISTS `log_lit`;
CREATE TABLE IF NOT EXISTS `log_lit` (
  `log_citationID` int(11) NOT NULL auto_increment,
  `citationID` int(11) NOT NULL default '0',
  `lit_url` varchar(255) default NULL,
  `autorID` int(11) NOT NULL default '0',
  `jahr` varchar(50) default NULL,
  `code` varchar(3) default NULL,
  `titel` varchar(250) default NULL,
  `suptitel` varchar(250) default NULL,
  `editorsID` int(11) default NULL,
  `periodicalID` int(11) default NULL,
  `vol` varchar(20) default NULL,
  `part` varchar(50) default NULL,
  `pp` varchar(150) default NULL,
  `publisherID` int(11) default NULL,
  `verlagsort` varchar(100) default NULL,
  `keywords` varchar(100) default NULL,
  `annotation` longtext,
  `additions` longtext,
  `bestand` varchar(50) default NULL,
  `signature` varchar(50) default NULL,
  `publ` char(1) default NULL,
  `category` varchar(50) default NULL,
  `userID` int(11) NOT NULL default '0',
  `updated` tinyint(4) NOT NULL default '0',
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`log_citationID`),
  KEY `autorID` (`autorID`),
  KEY `category` (`category`),
  KEY `citationID` (`citationID`),
  KEY `code` (`code`),
  KEY `editorsID` (`editorsID`),
  KEY `jahr` (`jahr`),
  KEY `periodicalID` (`periodicalID`),
  KEY `publisherID` (`publisherID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=24699 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_lit_authors`
--

DROP TABLE IF EXISTS `log_lit_authors`;
CREATE TABLE IF NOT EXISTS `log_lit_authors` (
  `log_autorID` int(11) NOT NULL auto_increment,
  `autorID` int(11) NOT NULL default '0',
  `autor` varchar(150) default NULL,
  `autorsystbot` varchar(150) default NULL,
  `userID` int(11) NOT NULL default '0',
  `updated` tinyint(4) NOT NULL default '0',
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`log_autorID`),
  KEY `autor` (`autor`),
  KEY `autorID` (`autorID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3232 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_lit_periodicals`
--

DROP TABLE IF EXISTS `log_lit_periodicals`;
CREATE TABLE IF NOT EXISTS `log_lit_periodicals` (
  `log_periodicalID` int(11) NOT NULL auto_increment,
  `periodicalID` int(11) NOT NULL default '0',
  `periodical` varchar(250) default NULL,
  `periodical_full` varchar(250) default NULL,
  `tl2_number` int(11) default '0',
  `bph_number` varchar(15) default NULL,
  `ipni_ID` varchar(15) default NULL,
  `userID` int(11) NOT NULL default '0',
  `updated` tinyint(4) NOT NULL default '0',
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`log_periodicalID`),
  KEY `periodical_full` (`periodical_full`),
  KEY `periodical` (`periodical`),
  KEY `periodicalID` (`periodicalID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3713 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_lit_publishers`
--

DROP TABLE IF EXISTS `log_lit_publishers`;
CREATE TABLE IF NOT EXISTS `log_lit_publishers` (
  `log_publisherID` int(11) NOT NULL auto_increment,
  `publisherID` int(11) NOT NULL default '0',
  `publisher` varchar(100) default NULL,
  `userID` int(11) NOT NULL default '0',
  `updated` tinyint(4) NOT NULL default '0',
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`log_publisherID`),
  KEY `publisher` (`publisher`),
  KEY `publisherID` (`publisherID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=490 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_lit_taxa`
--

DROP TABLE IF EXISTS `log_lit_taxa`;
CREATE TABLE IF NOT EXISTS `log_lit_taxa` (
  `log_lit_taxaID` int(11) NOT NULL auto_increment,
  `lit_tax_ID` int(11) NOT NULL,
  `citationID` int(11) NOT NULL default '0',
  `taxonID` int(11) default NULL,
  `acc_taxon_ID` int(11) NOT NULL default '0',
  `annotations` longtext,
  `locked` tinyint(4) NOT NULL default '1',
  `source` varchar(20) NOT NULL default 'person',
  `source_citationID` int(11) default NULL,
  `source_person_ID` int(11) default '39269',
  `et_al` tinyint(4) NOT NULL default '0',
  `userID` int(11) NOT NULL,
  `updated` tinyint(4) NOT NULL default '0',
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`log_lit_taxaID`),
  KEY `revision` (`citationID`,`taxonID`,`acc_taxon_ID`,`source_citationID`,`source_person_ID`),
  KEY `userID` (`userID`),
  KEY `lit_tax_ID` (`lit_tax_ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1591 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_lit_taxa_old`
--

DROP TABLE IF EXISTS `log_lit_taxa_old`;
CREATE TABLE IF NOT EXISTS `log_lit_taxa_old` (
  `log_lit_taxa_ID` int(11) NOT NULL auto_increment,
  `lit_tax_ID` int(11) NOT NULL default '0',
  `citationID` int(11) NOT NULL default '0',
  `taxonID` int(11) NOT NULL default '0',
  `acc_taxon_ID` int(11) NOT NULL default '0',
  `annotations` longtext,
  `userID` int(11) NOT NULL default '0',
  `updated` tinyint(4) NOT NULL default '0',
  `timestamp` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`log_lit_taxa_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_specimens`
--

DROP TABLE IF EXISTS `log_specimens`;
CREATE TABLE IF NOT EXISTS `log_specimens` (
  `log_specimensID` int(11) NOT NULL auto_increment,
  `specimenID` int(11) NOT NULL default '0',
  `userID` int(11) NOT NULL default '0',
  `updated` tinyint(4) NOT NULL default '0',
  `timestamp` timestamp NOT NULL default '0000-00-00 00:00:00',
  `HerbNummer` varchar(25) default NULL,
  `collectionID` int(11) default NULL,
  `CollNummer` varchar(25) default NULL,
  `identstatusID` int(11) default NULL,
  `checked` tinyint(4) default NULL,
  `accessible` tinyint(4) default NULL,
  `taxonID` int(11) default NULL,
  `SammlerID` int(11) default NULL,
  `Sammler_2ID` int(11) default NULL,
  `seriesID` int(11) default NULL,
  `series_number` varchar(50) default NULL,
  `Nummer` int(11) default NULL,
  `alt_number` varchar(50) default NULL,
  `Datum` varchar(25) default NULL,
  `Datum2` varchar(25) default NULL,
  `det` varchar(255) default NULL,
  `typified` varchar(255) default NULL,
  `typusID` int(11) default NULL,
  `taxon_alt` text,
  `NationID` int(11) default NULL,
  `provinceID` int(11) default NULL,
  `Bezirk` varchar(50) default NULL,
  `Coord_W` int(11) default NULL,
  `W_Min` int(11) default NULL,
  `W_Sec` double default NULL,
  `Coord_N` int(11) default NULL,
  `N_Min` int(11) default NULL,
  `N_Sec` double default NULL,
  `Coord_S` int(11) default NULL,
  `S_Min` int(11) default NULL,
  `S_Sec` double default NULL,
  `Coord_E` int(11) default NULL,
  `E_Min` int(11) default NULL,
  `E_Sec` double default NULL,
  `quadrant` int(11) default NULL,
  `quadrant_sub` int(11) default NULL,
  `exactness` double default NULL,
  `altitude_min` int(11) default NULL,
  `altitude_max` int(11) default NULL,
  `Fundort` longtext,
  `habitat` longtext,
  `habitus` longtext,
  `Bemerkungen` longtext,
  `aktualdatum` datetime default NULL,
  `eingabedatum` datetime default NULL,
  `digital_image` tinyint(4) default NULL,
  `garten` varchar(50) default NULL,
  `voucherID` int(11) default NULL,
  `ncbi_accession` varchar(50) default NULL,
  `foreign_db_ID` text,
  `label` tinyint(4) default NULL,
  `observation` tinyint(4) default NULL,
  `digital_image_obs` tinyint(4) default NULL,
  PRIMARY KEY  (`log_specimensID`),
  KEY `specimenID` (`specimenID`),
  KEY `timestamp` (`timestamp`),
  KEY `userID` (`userID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=345249 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_specimens_series`
--

DROP TABLE IF EXISTS `log_specimens_series`;
CREATE TABLE IF NOT EXISTS `log_specimens_series` (
  `log_seriesID` int(11) NOT NULL auto_increment,
  `seriesID` int(11) NOT NULL,
  `series` varchar(255) NOT NULL,
  `locked` tinyint(4) NOT NULL,
  `userID` int(11) NOT NULL,
  `updated` tinyint(4) NOT NULL default '0',
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`log_seriesID`),
  KEY `seriesID` (`seriesID`),
  KEY `userID` (`userID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1452 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_specimens_types`
--

DROP TABLE IF EXISTS `log_specimens_types`;
CREATE TABLE IF NOT EXISTS `log_specimens_types` (
  `log_specimens_typesID` int(11) NOT NULL auto_increment,
  `specimens_types_ID` int(11) NOT NULL default '0',
  `specimenID` int(11) NOT NULL default '0',
  `taxonID` int(11) NOT NULL default '0',
  `typusID` int(11) NOT NULL default '0',
  `annotations` longtext,
  `userID` int(11) NOT NULL default '0',
  `updated` tinyint(4) NOT NULL default '0',
  `timestamp` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`log_specimens_typesID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=59075 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_tax_authors`
--

DROP TABLE IF EXISTS `log_tax_authors`;
CREATE TABLE IF NOT EXISTS `log_tax_authors` (
  `log_tax_authorID` int(11) NOT NULL auto_increment,
  `authorID` int(11) NOT NULL default '0',
  `author` varchar(255) NOT NULL default '',
  `Brummit_Powell_full` varchar(250) default NULL,
  `userID` int(11) NOT NULL default '0',
  `updated` tinyint(4) NOT NULL default '0',
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`log_tax_authorID`),
  KEY `author` (`author`),
  KEY `authorID` (`authorID`),
  KEY `Brummit_Powell_full` (`Brummit_Powell_full`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=44660 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_tax_chorol_status`
--

DROP TABLE IF EXISTS `log_tax_chorol_status`;
CREATE TABLE IF NOT EXISTS `log_tax_chorol_status` (
  `log_tax_chorol_status_ID` int(11) NOT NULL auto_increment,
  `tax_chorol_status_ID` int(11) NOT NULL,
  `taxonID_fk` int(11) NOT NULL,
  `citationID_fk` int(11) default NULL,
  `personID_fk` int(11) default NULL,
  `serviceID_fk` tinyint(4) default NULL,
  `chorol_status` varchar(255) character set utf8 collate utf8_unicode_ci default NULL,
  `NationID_fk` int(11) NOT NULL,
  `provinceID_fk` int(11) default NULL,
  `dateLastEdited` datetime NOT NULL,
  `locked` int(11) NOT NULL default '0',
  `userID` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`log_tax_chorol_status_ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=26 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_tax_families`
--

DROP TABLE IF EXISTS `log_tax_families`;
CREATE TABLE IF NOT EXISTS `log_tax_families` (
  `log_tax_familyID` int(11) NOT NULL auto_increment,
  `familyID` int(11) NOT NULL default '0',
  `family` varchar(50) NOT NULL default '',
  `categoryID` int(11) NOT NULL default '0',
  `userID` int(11) NOT NULL default '0',
  `updated` tinyint(4) NOT NULL default '0',
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`log_tax_familyID`),
  KEY `categoryID` (`categoryID`),
  KEY `familyID` (`familyID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1224 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_tax_genera`
--

DROP TABLE IF EXISTS `log_tax_genera`;
CREATE TABLE IF NOT EXISTS `log_tax_genera` (
  `log_tax_genID` int(11) NOT NULL auto_increment,
  `genID` int(11) NOT NULL default '0',
  `genID_old` int(11) default NULL,
  `genus` varchar(100) NOT NULL default '',
  `authorID` int(11) default NULL,
  `DallaTorreIDs` int(11) default NULL,
  `DallaTorreZusatzIDs` char(1) default NULL,
  `genID_inc0406` int(11) default NULL,
  `hybrid` varchar(10) default NULL,
  `familyID` int(11) NOT NULL default '0',
  `remarks` longtext,
  `accepted` tinyint(4) default NULL,
  `userID` int(11) NOT NULL default '0',
  `updated` tinyint(4) NOT NULL default '0',
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`log_tax_genID`),
  KEY `familyID` (`familyID`),
  KEY `authorID` (`authorID`),
  KEY `DallaTorreIDs` (`DallaTorreIDs`),
  KEY `hybrid` (`hybrid`),
  KEY `genID` (`genID`),
  KEY `genus` (`genus`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=32599 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_tax_index`
--

DROP TABLE IF EXISTS `log_tax_index`;
CREATE TABLE IF NOT EXISTS `log_tax_index` (
  `log_tax_indexID` int(11) NOT NULL auto_increment,
  `taxindID` int(11) NOT NULL default '0',
  `taxonID` int(11) NOT NULL default '0',
  `citationID` int(11) NOT NULL default '0',
  `paginae` varchar(50) default NULL,
  `figures` varchar(255) default NULL,
  `annotations` longtext,
  `userID` int(11) NOT NULL default '0',
  `updated` tinyint(4) NOT NULL default '0',
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`log_tax_indexID`),
  KEY `citationID` (`citationID`),
  KEY `taxindID` (`taxindID`),
  KEY `taxonID` (`taxonID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=59922 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_tax_species`
--

DROP TABLE IF EXISTS `log_tax_species`;
CREATE TABLE IF NOT EXISTS `log_tax_species` (
  `log_tax_speciesID` int(11) NOT NULL auto_increment,
  `tax_rankID` int(11) NOT NULL default '0',
  `basID` int(11) default NULL,
  `taxonID` int(11) NOT NULL default '0',
  `synID` int(11) default NULL,
  `statusID` int(11) default NULL,
  `genID` int(11) NOT NULL default '0',
  `speciesID` int(11) default NULL,
  `authorID` int(11) default NULL,
  `subspeciesID` int(11) default NULL,
  `subspecies_authorID` int(11) default NULL,
  `varietyID` int(11) default NULL,
  `variety_authorID` int(11) default NULL,
  `subvarietyID` int(11) default NULL,
  `subvariety_authorID` int(11) default NULL,
  `formaID` int(11) default NULL,
  `forma_authorID` int(11) default NULL,
  `subformaID` int(11) default NULL,
  `subforma_authorID` int(11) default NULL,
  `annotation` longtext,
  `userID` int(11) NOT NULL default '0',
  `updated` tinyint(4) NOT NULL default '0',
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`log_tax_speciesID`),
  KEY `basID` (`basID`),
  KEY `forma_authorID` (`forma_authorID`),
  KEY `formaID` (`formaID`),
  KEY `genID` (`genID`),
  KEY `taxonID` (`taxonID`),
  KEY `speciesID` (`speciesID`),
  KEY `statusID` (`statusID`),
  KEY `subspecies_authorID` (`subspecies_authorID`),
  KEY `subspeciesID` (`subspeciesID`),
  KEY `synID` (`synID`),
  KEY `authorID` (`authorID`),
  KEY `variety_authorID` (`variety_authorID`),
  KEY `varietyID` (`varietyID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=241216 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_tax_typecollections`
--

DROP TABLE IF EXISTS `log_tax_typecollections`;
CREATE TABLE IF NOT EXISTS `log_tax_typecollections` (
  `log_tax_typecollID` int(11) NOT NULL auto_increment,
  `typecollID` int(11) NOT NULL default '0',
  `taxonID` int(11) NOT NULL default '0',
  `SammlerID` int(11) NOT NULL default '0',
  `Sammler_2ID` int(11) default NULL,
  `typusID` int(11) NOT NULL default '0',
  `series` varchar(250) default NULL,
  `leg_nr` int(11) default NULL,
  `alternate_number` varchar(250) default NULL,
  `date` varchar(50) default NULL,
  `duplicates` varchar(250) default NULL,
  `annotation` longtext,
  `userID` int(11) NOT NULL default '0',
  `updated` tinyint(4) NOT NULL default '0',
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`log_tax_typecollID`),
  KEY `Sammler_2ID` (`Sammler_2ID`),
  KEY `SammlerID` (`SammlerID`),
  KEY `taxonID` (`taxonID`),
  KEY `typusID` (`typusID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=20254 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_tbl_tax_synonymy`
--

DROP TABLE IF EXISTS `log_tbl_tax_synonymy`;
CREATE TABLE IF NOT EXISTS `log_tbl_tax_synonymy` (
  `log_tax_syn_ID` int(11) NOT NULL auto_increment,
  `updated` tinyint(4) NOT NULL,
  `tax_syn_ID` int(11) NOT NULL,
  `taxonID` int(11) NOT NULL default '0',
  `acc_taxon_ID` int(11) NOT NULL default '0',
  `ref_date` date default NULL,
  `preferred_taxonomy` tinyint(4) NOT NULL default '0',
  `annotations` longtext,
  `locked` tinyint(4) NOT NULL default '1',
  `source` varchar(20) NOT NULL default 'person',
  `source_citationID` int(11) default NULL,
  `source_person_ID` int(11) default '39269',
  `source_serviceID` int(11) default NULL,
  `source_specimenID` int(11) default NULL,
  `userID` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`log_tax_syn_ID`),
  KEY `taxonID` (`taxonID`),
  KEY `acc_taxon_ID` (`acc_taxon_ID`),
  KEY `locked` (`locked`),
  KEY `userID` (`userID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=12457 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `meta`
--

DROP TABLE IF EXISTS `meta`;
CREATE TABLE IF NOT EXISTS `meta` (
  `source_id` int(11) NOT NULL default '0',
  `source_code` longtext,
  `source_name` longtext,
  `source_update` datetime default NULL,
  `source_version` longtext,
  `source_url` longtext,
  `source_expiry` datetime default NULL,
  `source_number_of_records` int(11) default NULL,
  PRIMARY KEY  (`source_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_herbardb_access`
--

DROP TABLE IF EXISTS `tbl_herbardb_access`;
CREATE TABLE IF NOT EXISTS `tbl_herbardb_access` (
  `ID` int(11) NOT NULL auto_increment,
  `userID` int(11) NOT NULL default '0',
  `categoryID` int(11) default NULL,
  `familyID` int(11) default NULL,
  `genID` int(11) default NULL,
  `update` tinyint(4) default NULL,
  PRIMARY KEY  (`ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=10 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_herbardb_groups`
--

DROP TABLE IF EXISTS `tbl_herbardb_groups`;
CREATE TABLE IF NOT EXISTS `tbl_herbardb_groups` (
  `groupID` int(11) NOT NULL auto_increment,
  `group_name` varchar(50) NOT NULL default '""',
  `group_description` varchar(255) NOT NULL default '""',
  `species` tinyint(4) NOT NULL default '0',
  `author` tinyint(4) NOT NULL default '0',
  `epithet` tinyint(4) NOT NULL default '0',
  `genera` tinyint(4) NOT NULL default '0',
  `family` tinyint(4) NOT NULL default '0',
  `lit` tinyint(4) NOT NULL default '0',
  `litAuthor` tinyint(4) NOT NULL default '0',
  `litPer` tinyint(4) NOT NULL default '0',
  `litPub` tinyint(4) NOT NULL default '0',
  `index` tinyint(4) NOT NULL default '0',
  `type` tinyint(4) NOT NULL default '0',
  `specimensTypes` tinyint(4) NOT NULL default '0',
  `collIns` tinyint(4) NOT NULL default '0',
  `collUpd` tinyint(4) NOT NULL default '0',
  `seriesIns` tinyint(4) NOT NULL default '0',
  `seriesUpd` tinyint(4) NOT NULL default '0',
  `specim` tinyint(4) NOT NULL default '0',
  `dt` tinyint(4) NOT NULL default '0',
  `chorol` tinyint(4) NOT NULL default '0',
  `btnTax` tinyint(4) NOT NULL default '0',
  `btnLit` tinyint(4) NOT NULL default '0',
  `btnSpc` tinyint(4) NOT NULL default '0',
  `btnObs` tinyint(4) NOT NULL default '0',
  `btnImg` tinyint(4) NOT NULL default '0',
  `btnNom` tinyint(4) NOT NULL default '0',
  `btnImport` tinyint(4) NOT NULL default '0',
  `linkTaxon` tinyint(4) NOT NULL default '0',
  `batch` tinyint(4) NOT NULL default '0',
  `batchAdmin` tinyint(4) NOT NULL default '0',
  `admin` tinyint(4) NOT NULL default '0',
  `editor` tinyint(4) NOT NULL default '0',
  `commonnameUpdate` int(4) NOT NULL,
  `commonnameInsert` int(4) NOT NULL,
  PRIMARY KEY  (`groupID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=19 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_herbardb_unlock`
--

DROP TABLE IF EXISTS `tbl_herbardb_unlock`;
CREATE TABLE IF NOT EXISTS `tbl_herbardb_unlock` (
  `ID` int(11) NOT NULL auto_increment,
  `groupID` int(11) NOT NULL default '0',
  `table` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`ID`),
  KEY `groupID` (`groupID`),
  KEY `table` (`table`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=49 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_herbardb_users`
--

DROP TABLE IF EXISTS `tbl_herbardb_users`;
CREATE TABLE IF NOT EXISTS `tbl_herbardb_users` (
  `userID` int(11) NOT NULL auto_increment,
  `groupID` int(11) NOT NULL default '0',
  `source_id` int(11) NOT NULL default '0',
  `use_access` tinyint(4) default NULL,
  `active` tinyint(4) NOT NULL default '1',
  `username` varchar(255) default NULL,
  `firstname` varchar(255) NOT NULL default '',
  `surname` varchar(255) NOT NULL default '',
  `emailadress` varchar(255) NOT NULL default '',
  `phone` varchar(255) default NULL,
  `mobile` varchar(255) default NULL,
  `editFamily` varchar(255) default NULL,
  `login` datetime default NULL,
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `iv` varchar(255) default NULL,
  `secret` varchar(255) default NULL,
  PRIMARY KEY  (`userID`),
  KEY `groupID` (`groupID`),
  KEY `source_id` (`source_id`),
  KEY `username` (`username`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=110 ;
