-- phpMyAdmin SQL Dump
-- version 3.3.9
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 18. Mai 2011 um 10:39
-- Server Version: 5.1.42
-- PHP-Version: 5.3.2

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Datenbank: `herbarinput`
--
CREATE DATABASE `herbarinput` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `herbarinput`;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `meta`
--

CREATE TABLE IF NOT EXISTS `meta` (
  `source_id` int(11) NOT NULL DEFAULT '0',
  `source_code` char(250) DEFAULT NULL,
  `source_name` char(250) DEFAULT NULL,
  `source_update` datetime DEFAULT NULL,
  `source_version` char(250) DEFAULT NULL,
  `source_url` char(250) DEFAULT NULL,
  `source_expiry` datetime DEFAULT NULL,
  `source_number_of_records` int(11) DEFAULT NULL,
  `source_abbr_engl` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`source_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `metadb`
--

CREATE TABLE IF NOT EXISTS `metadb` (
  `db_id` int(11) NOT NULL DEFAULT '0',
  `source_id_fk` int(11) DEFAULT NULL,
  `supplier_supplied_when` datetime DEFAULT NULL,
  `supplier_organisation` varchar(250) DEFAULT NULL,
  `supplier_organisation_code` varchar(50) DEFAULT NULL,
  `supplier_person` varchar(250) DEFAULT NULL,
  `supplier_url` varchar(250) DEFAULT NULL,
  `supplier_adress` varchar(50) DEFAULT NULL,
  `supplier_telephone` varchar(50) DEFAULT NULL,
  `supplier_email` varchar(250) DEFAULT NULL,
  `legal_owner_organisation` varchar(250) DEFAULT NULL,
  `legal_owner_organisation_code` varchar(50) DEFAULT NULL,
  `legal_owner_person` varchar(250) DEFAULT NULL,
  `legal_owner_adress` varchar(250) DEFAULT NULL,
  `legal_owner_telephone` varchar(250) DEFAULT NULL,
  `legal_owner_email` varchar(250) DEFAULT NULL,
  `legal_owner_url` varchar(250) DEFAULT NULL,
  `terms_of_use` text,
  `acknowledgement` text,
  `description` text,
  `disclaimer` text,
  `restrictions` text,
  `logo_url` varchar(250) DEFAULT NULL,
  `statement_url` varchar(250) DEFAULT NULL,
  `copyright` text,
  `ipr` text,
  `rights_url` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`db_id`),
  KEY `source_id_fk` (`source_id_fk`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_chat`
--

CREATE TABLE IF NOT EXISTS `tbl_chat` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL DEFAULT '0',
  `chat` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=44 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_chat_priv`
--

CREATE TABLE IF NOT EXISTS `tbl_chat_priv` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL DEFAULT '0',
  `tid` int(11) NOT NULL DEFAULT '0',
  `theme` varchar(255) DEFAULT NULL,
  `chat` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `seen` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `tid` (`tid`),
  KEY `timestamp` (`timestamp`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=8497 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_collector`
--

CREATE TABLE IF NOT EXISTS `tbl_collector` (
  `SammlerID` int(11) NOT NULL AUTO_INCREMENT,
  `Sammler` varchar(250) NOT NULL DEFAULT '',
  `Sammler_FN_List` varchar(50) DEFAULT NULL,
  `Sammler_FN_short` varchar(50) DEFAULT NULL,
  `HUH_ID` int(11) DEFAULT NULL,
  `locked` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`SammlerID`),
  KEY `Sammler` (`Sammler`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=20347 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_collector_2`
--

CREATE TABLE IF NOT EXISTS `tbl_collector_2` (
  `Sammler_2ID` int(11) NOT NULL AUTO_INCREMENT,
  `Sammler_2` varchar(120) NOT NULL DEFAULT '',
  `Sammler_2_FN_list` varchar(250) DEFAULT NULL,
  `Sammler_2_FN_short` varchar(50) DEFAULT NULL,
  `locked` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`Sammler_2ID`),
  KEY `Sammler_2` (`Sammler_2`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6906 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_descriptions`
--

CREATE TABLE IF NOT EXISTS `tbl_descriptions` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `table` varchar(255) DEFAULT NULL,
  `column` varchar(255) NOT NULL DEFAULT '',
  `description` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_external_import`
--

CREATE TABLE IF NOT EXISTS `tbl_external_import` (
  `externalID` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(255) NOT NULL,
  `annotation` longtext,
  `startdate` date DEFAULT NULL,
  `enddate` date DEFAULT NULL,
  `used` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`externalID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=7 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_external_import_content`
--

CREATE TABLE IF NOT EXISTS `tbl_external_import_content` (
  `contentID` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `linenumber` int(11) NOT NULL,
  `line` text NOT NULL,
  `processingError` text,
  `userID` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `specimen_ID` int(11) DEFAULT NULL,
  `pending` tinyint(4) NOT NULL DEFAULT '0',
  `externalID` int(11) DEFAULT NULL,
  `taxonID` int(11) DEFAULT NULL,
  PRIMARY KEY (`contentID`),
  KEY `specimen_ID` (`specimen_ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=46351 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_geo_nation`
--

CREATE TABLE IF NOT EXISTS `tbl_geo_nation` (
  `nationID` int(11) NOT NULL AUTO_INCREMENT,
  `nation` varchar(50) DEFAULT NULL,
  `fnnumber` int(11) DEFAULT NULL,
  `nation_engl` varchar(50) DEFAULT NULL,
  `nation_deutsch` varchar(50) DEFAULT NULL,
  `annotation` varchar(250) DEFAULT NULL,
  `nation_code` varchar(10) DEFAULT NULL,
  `usgs_code` varchar(5) DEFAULT NULL,
  `iso_alpha_3_code` varchar(3) DEFAULT NULL,
  `iso_alpha_2_code` varchar(2) DEFAULT NULL,
  `regionID_fk` int(11) NOT NULL DEFAULT '0',
  `language_variants` varchar(2000) DEFAULT NULL COMMENT 'country names in numerous languages',
  PRIMARY KEY (`nationID`),
  KEY `iso_alpha_3_code` (`iso_alpha_3_code`),
  KEY `iso_alpha_2_code` (`iso_alpha_2_code`),
  KEY `nation` (`nation`),
  KEY `nation_deutsch` (`nation_deutsch`),
  KEY `nation_engl` (`nation_engl`),
  KEY `nation_code` (`nation_code`),
  KEY `usgs_code` (`usgs_code`),
  KEY `regionID` (`regionID_fk`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=267 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_geo_province`
--

CREATE TABLE IF NOT EXISTS `tbl_geo_province` (
  `provinceID` int(11) NOT NULL AUTO_INCREMENT,
  `provinz` varchar(100) DEFAULT NULL,
  `provinz_local` varchar(150) DEFAULT NULL,
  `provinz_code` varchar(5) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `nationID` int(11) NOT NULL DEFAULT '0',
  `usgs_number` varchar(5) DEFAULT NULL,
  PRIMARY KEY (`provinceID`),
  KEY `nationID` (`nationID`),
  KEY `provinz` (`provinz`),
  KEY `provinz_local1` (`provinz_local`),
  KEY `provinz_code` (`provinz_code`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3554 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_geo_ref_geonames`
--

CREATE TABLE IF NOT EXISTS `tbl_geo_ref_geonames` (
  `geonameid` int(11) NOT NULL COMMENT 'integer id of record in geonames database',
  `name` varchar(200) NOT NULL COMMENT 'name of geographical point (utf8) varchar(200)',
  `asciiname` varchar(200) NOT NULL COMMENT 'name of geographical point in plain ascii characters, varchar(200)',
  `alternatenames` varchar(5000) NOT NULL COMMENT 'alternatenames, comma separated varchar(4000) (varchar(5000) for SQL Server)',
  `latitude` double NOT NULL COMMENT 'latitude in decimal degrees (wgs84)',
  `longitude` double NOT NULL COMMENT 'longitude in decimal degrees (wgs84)',
  `feature_class` char(1) NOT NULL COMMENT 'see http://www.geonames.org/export/codes.html, char(1)',
  `feature code` varchar(10) NOT NULL COMMENT 'see http://www.geonames.org/export/codes.html, varchar(10)',
  `country code` char(2) NOT NULL COMMENT 'ISO-3166 2-letter country code, 2 characters',
  `cc2` varchar(60) NOT NULL COMMENT 'alternate country codes, comma separated, ISO-3166 2-letter country code, 60 characters',
  `admin1 code` varchar(20) NOT NULL COMMENT 'fipscode (subject to change to iso code), isocode for the us and ch, see file admin1Codes.txt for display names of this code; varchar(20)',
  `admin2 code` varchar(80) NOT NULL COMMENT 'code for the second administrative division, a county in the US, see file admin2Codes.txt; varchar(80)',
  `admin3 code` varchar(20) NOT NULL COMMENT 'code for third level administrative division, varchar(20)',
  `admin4 code` varchar(20) NOT NULL COMMENT 'code for fourth level administrative division, varchar(20)',
  `population` int(11) NOT NULL COMMENT 'integer',
  `elevation` int(11) NOT NULL COMMENT 'in meters, integer',
  `gtopo30` int(11) NOT NULL COMMENT 'average elevation of 30''x30'' (ca 900mx900m) area in meters, integer',
  `timezone` varchar(50) NOT NULL COMMENT 'the timezone id (see file timeZone.txt)',
  `modification date` date NOT NULL COMMENT 'date of last modification in yyyy-MM-dd format',
  PRIMARY KEY (`geonameid`),
  KEY `asciiname` (`asciiname`),
  KEY `country code` (`country code`),
  KEY `name` (`name`),
  KEY `admin1 code` (`admin1 code`),
  KEY `feature code` (`feature code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_geo_region`
--

CREATE TABLE IF NOT EXISTS `tbl_geo_region` (
  `regionID` int(11) NOT NULL AUTO_INCREMENT,
  `geo_region` varchar(255) NOT NULL DEFAULT '',
  `geo_general` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`regionID`),
  KEY `geographical_region` (`geo_region`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=14 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_herbaria`
--

CREATE TABLE IF NOT EXISTS `tbl_herbaria` (
  `herbariumID` int(11) NOT NULL AUTO_INCREMENT,
  `adressat` varchar(50) DEFAULT NULL,
  `ihcode` varchar(255) DEFAULT NULL,
  `Herbarium` varchar(255) DEFAULT NULL,
  `Department` varchar(255) DEFAULT NULL,
  `Institution` varchar(255) DEFAULT NULL,
  `StreetAddress` varchar(255) DEFAULT NULL,
  `City` varchar(255) DEFAULT NULL,
  `Nation` varchar(255) DEFAULT NULL,
  `Location` longtext,
  `Correspondent` longtext,
  `Phone` varchar(255) DEFAULT NULL,
  `Fax` varchar(255) DEFAULT NULL,
  `Url1` longtext,
  `Url2` longtext,
  `Updated` datetime DEFAULT NULL,
  `Annotation1` longtext,
  `Annotation2` longtext,
  PRIMARY KEY (`herbariumID`),
  UNIQUE KEY `ihcode` (`ihcode`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2141 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_herbaria_collectors`
--

CREATE TABLE IF NOT EXISTS `tbl_herbaria_collectors` (
  `person_IDfk` int(11) NOT NULL DEFAULT '0',
  `herbarium_IDfk` int(11) NOT NULL DEFAULT '0',
  `number_objects` double DEFAULT NULL,
  `annotation` longtext,
  PRIMARY KEY (`person_IDfk`,`herbarium_IDfk`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_img_definition`
--

CREATE TABLE IF NOT EXISTS `tbl_img_definition` (
  `img_def_ID` int(11) NOT NULL AUTO_INCREMENT,
  `source_id_fk` int(11) NOT NULL DEFAULT '0',
  `img_coll_short` varchar(7) NOT NULL DEFAULT '',
  `img_directory` varchar(255) NOT NULL DEFAULT '',
  `img_obs_directory` varchar(255) NOT NULL DEFAULT '',
  `img_tab_directory` varchar(255) DEFAULT NULL,
  `imgserver_IP` varchar(15) NOT NULL DEFAULT '',
  `HerbNummerNrDigits` tinyint(4) NOT NULL,
  PRIMARY KEY (`img_def_ID`),
  UNIQUE KEY `source_id_fk` (`source_id_fk`),
  KEY `imgserver_IP` (`imgserver_IP`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=14 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_labels`
--

CREATE TABLE IF NOT EXISTS `tbl_labels` (
  `userID` int(11) NOT NULL DEFAULT '0',
  `specimen_ID` int(11) NOT NULL DEFAULT '0',
  `label` smallint(5) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`userID`,`specimen_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_labels_numbering`
--

CREATE TABLE IF NOT EXISTS `tbl_labels_numbering` (
  `labels_numbering_ID` int(11) NOT NULL AUTO_INCREMENT,
  `sourceID_fk` int(11) NOT NULL,
  `collectionID_fk` int(11) DEFAULT NULL,
  `digits` int(11) NOT NULL,
  `replace_char` char(1) DEFAULT NULL,
  PRIMARY KEY (`labels_numbering_ID`),
  UNIQUE KEY `numbering` (`sourceID_fk`,`collectionID_fk`,`replace_char`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=8 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_languages`
--

CREATE TABLE IF NOT EXISTS `tbl_languages` (
  `languageID` int(11) NOT NULL AUTO_INCREMENT,
  `language_name` varchar(255) NOT NULL DEFAULT '',
  `language_familyID_fk` int(11) DEFAULT NULL,
  PRIMARY KEY (`languageID`),
  KEY `language_name` (`language_name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=7 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_lit`
--

CREATE TABLE IF NOT EXISTS `tbl_lit` (
  `citationID` int(11) NOT NULL AUTO_INCREMENT,
  `lit_url` varchar(255) DEFAULT NULL,
  `autorID` int(11) NOT NULL DEFAULT '0',
  `jahr` varchar(50) DEFAULT NULL,
  `code` varchar(25) DEFAULT NULL,
  `titel` text,
  `suptitel` varchar(250) DEFAULT NULL,
  `editorsID` int(11) DEFAULT NULL,
  `periodicalID` int(11) DEFAULT NULL,
  `vol` varchar(20) DEFAULT NULL,
  `part` varchar(50) DEFAULT NULL,
  `pp` varchar(150) DEFAULT NULL,
  `ppSort` varchar(255) DEFAULT NULL,
  `publisherID` int(11) DEFAULT NULL,
  `verlagsort` varchar(100) DEFAULT NULL,
  `keywords` varchar(100) DEFAULT NULL,
  `annotation` longtext,
  `additions` longtext,
  `bestand` varchar(50) DEFAULT NULL,
  `signature` varchar(50) DEFAULT NULL,
  `publ` char(1) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `locked` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`citationID`),
  KEY `autorID` (`autorID`),
  KEY `category` (`category`),
  KEY `code` (`code`),
  KEY `editorsID` (`editorsID`),
  KEY `jahr` (`jahr`),
  KEY `periodicalID` (`periodicalID`),
  KEY `publisherID` (`publisherID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=16923 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_lit_authors`
--

CREATE TABLE IF NOT EXISTS `tbl_lit_authors` (
  `autorID` int(11) NOT NULL AUTO_INCREMENT,
  `autor` varchar(150) NOT NULL DEFAULT '',
  `autorsystbot` varchar(150) DEFAULT NULL,
  `locked` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`autorID`),
  KEY `autor` (`autor`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5942 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_lit_container`
--

CREATE TABLE IF NOT EXISTS `tbl_lit_container` (
  `tbl_lit_containerID` int(11) NOT NULL AUTO_INCREMENT,
  `citation_parent_ID` int(11) NOT NULL,
  `citation_child_ID` int(11) NOT NULL,
  PRIMARY KEY (`tbl_lit_containerID`),
  UNIQUE KEY `citation_parent_ID` (`citation_parent_ID`,`citation_child_ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=775 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_lit_lib_period`
--

CREATE TABLE IF NOT EXISTS `tbl_lit_lib_period` (
  `lib_period_ID` int(11) NOT NULL AUTO_INCREMENT,
  `periodicalID` int(11) NOT NULL DEFAULT '0',
  `library_ID` int(11) NOT NULL DEFAULT '0',
  `signature` varchar(255) DEFAULT NULL,
  `bestand` varchar(255) DEFAULT NULL,
  `url` longtext,
  PRIMARY KEY (`lib_period_ID`),
  KEY `library_ID` (`library_ID`),
  KEY `periodicalID` (`periodicalID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2333 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_lit_libraries`
--

CREATE TABLE IF NOT EXISTS `tbl_lit_libraries` (
  `library_ID` int(11) NOT NULL AUTO_INCREMENT,
  `library` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`library_ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=46 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_lit_periodicals`
--

CREATE TABLE IF NOT EXISTS `tbl_lit_periodicals` (
  `periodicalID` int(11) NOT NULL AUTO_INCREMENT,
  `periodical` varchar(250) DEFAULT NULL COMMENT 'periodical or monograph abbreviated according to IPNI',
  `periodical_full` varchar(250) DEFAULT NULL,
  `tl2_number` int(11) DEFAULT NULL COMMENT 'Reference number for TL2',
  `bph_number` varchar(255) DEFAULT NULL COMMENT 'Reference number for BPH',
  `ipni_ID` varchar(15) DEFAULT NULL COMMENT 'Reference number for IPNI',
  `IPNI_version` varchar(25) DEFAULT NULL COMMENT 'version of IPNI entry 200801',
  `successor_ID` int(11) DEFAULT NULL,
  `predecessor_ID` int(11) DEFAULT NULL,
  `locked` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`periodicalID`),
  KEY `periodical_full` (`periodical_full`),
  KEY `periodical` (`periodical`),
  KEY `successor_ID` (`successor_ID`,`predecessor_ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3030 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_lit_persons`
--

CREATE TABLE IF NOT EXISTS `tbl_lit_persons` (
  `lit_persons_ID` int(11) NOT NULL AUTO_INCREMENT,
  `citationID_fk` int(11) NOT NULL,
  `personID_fk` int(11) NOT NULL,
  `annotations` longtext,
  `locked` tinyint(4) NOT NULL DEFAULT '1',
  `userID` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`lit_persons_ID`),
  UNIQUE KEY `lit_person` (`citationID_fk`,`personID_fk`),
  KEY `userID` (`userID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=78 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_lit_publishers`
--

CREATE TABLE IF NOT EXISTS `tbl_lit_publishers` (
  `publisherID` int(11) NOT NULL AUTO_INCREMENT,
  `publisher` varchar(100) DEFAULT NULL,
  `locked` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`publisherID`),
  KEY `publisher` (`publisher`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=723 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_lit_taxa`
--

CREATE TABLE IF NOT EXISTS `tbl_lit_taxa` (
  `lit_tax_ID` int(11) NOT NULL AUTO_INCREMENT,
  `citationID` int(11) NOT NULL DEFAULT '0',
  `taxonID` int(11) DEFAULT NULL,
  `acc_taxon_ID` int(11) NOT NULL DEFAULT '0',
  `annotations` longtext,
  `locked` tinyint(4) NOT NULL DEFAULT '1',
  `source` varchar(20) NOT NULL DEFAULT 'person',
  `source_citationID` int(11) DEFAULT NULL,
  `source_person_ID` int(11) DEFAULT '39269',
  `et_al` tinyint(4) NOT NULL DEFAULT '0',
  `userID` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`lit_tax_ID`),
  UNIQUE KEY `revision` (`citationID`,`taxonID`,`acc_taxon_ID`,`source_citationID`,`source_person_ID`),
  KEY `uid` (`userID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=833 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_loans`
--

CREATE TABLE IF NOT EXISTS `tbl_loans` (
  `loan_id` int(11) NOT NULL AUTO_INCREMENT,
  `wu_reference` varchar(50) NOT NULL DEFAULT 'WU-" & Format(Now(),"yy") & "-',
  `herbariumID` int(11) NOT NULL DEFAULT '0',
  `foreign_reference` varchar(50) DEFAULT NULL,
  `date_of_loan` datetime DEFAULT NULL,
  `loantypeID` int(11) NOT NULL DEFAULT '0',
  `number_of_sheets` int(11) DEFAULT NULL,
  `received` datetime DEFAULT NULL,
  `sent` datetime DEFAULT NULL,
  `annotations` longtext,
  PRIMARY KEY (`loan_id`),
  KEY `herbariumID` (`herbariumID`),
  KEY `wu_reference` (`wu_reference`),
  KEY `loantypeID` (`loantypeID`),
  KEY `received` (`received`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=970 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_loans_language`
--

CREATE TABLE IF NOT EXISTS `tbl_loans_language` (
  `languageID` int(11) NOT NULL AUTO_INCREMENT,
  `language` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`languageID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_loans_specimens`
--

CREATE TABLE IF NOT EXISTS `tbl_loans_specimens` (
  `wu_reference` varchar(15) NOT NULL DEFAULT '',
  `specimen_id` int(11) NOT NULL DEFAULT '0',
  `transaction` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`wu_reference`,`specimen_id`),
  KEY `specimen_id` (`specimen_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_loantype`
--

CREATE TABLE IF NOT EXISTS `tbl_loantype` (
  `loantypeID` int(11) NOT NULL AUTO_INCREMENT,
  `loantype_english` varchar(50) DEFAULT NULL,
  `loantype_espanol` varchar(50) DEFAULT NULL,
  `loantype_deutsch` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`loantypeID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=13 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_management_collections`
--

CREATE TABLE IF NOT EXISTS `tbl_management_collections` (
  `collectionID` int(11) NOT NULL AUTO_INCREMENT,
  `collection` varchar(50) DEFAULT NULL,
  `coll_short` varchar(12) DEFAULT NULL,
  `coll_short_prj` varchar(7) NOT NULL DEFAULT '',
  `coll_gbif_pilot` varchar(255) NOT NULL DEFAULT '',
  `coll_descr` longtext COMMENT 'description of the collection',
  `source_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`collectionID`),
  KEY `source_id` (`source_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=112 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_nom_common_names`
--

CREATE TABLE IF NOT EXISTS `tbl_nom_common_names` (
  `common_name_ID` int(11) NOT NULL AUTO_INCREMENT,
  `common_name` varchar(255) NOT NULL DEFAULT '',
  `annotation` longtext,
  `locked` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`common_name_ID`),
  UNIQUE KEY `common_name` (`common_name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=64 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_nom_common_names_links`
--

CREATE TABLE IF NOT EXISTS `tbl_nom_common_names_links` (
  `common_name_links_ID` int(11) NOT NULL AUTO_INCREMENT,
  `common_name_ID_fk` int(11) NOT NULL,
  `taxonID_fk` int(11) NOT NULL DEFAULT '0',
  `languageID_fk` int(11) NOT NULL DEFAULT '0',
  `citationID_fk` int(11) DEFAULT NULL COMMENT 'literature citing the common name',
  `annotation` longtext,
  `locked` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`common_name_links_ID`),
  UNIQUE KEY `common_names` (`common_name_ID_fk`,`taxonID_fk`,`languageID_fk`),
  KEY `citationID_fk` (`citationID_fk`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=62 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_nom_service`
--

CREATE TABLE IF NOT EXISTS `tbl_nom_service` (
  `serviceID` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `url_head` varchar(255) NOT NULL,
  `url_middle` varchar(255) DEFAULT NULL,
  `url_trail` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`serviceID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=22 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_nom_service_names`
--

CREATE TABLE IF NOT EXISTS `tbl_nom_service_names` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `taxonID` int(11) NOT NULL,
  `serviceID` int(11) NOT NULL,
  `param1` varchar(255) NOT NULL DEFAULT '0',
  `param2` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `taxon_service` (`taxonID`,`serviceID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5397 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_nom_status`
--

CREATE TABLE IF NOT EXISTS `tbl_nom_status` (
  `statusID` int(11) NOT NULL AUTO_INCREMENT,
  `status` varchar(50) DEFAULT NULL,
  `status_description` varchar(50) DEFAULT NULL,
  `status_sp2000` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`statusID`),
  KEY `status` (`status`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=122 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_person`
--

CREATE TABLE IF NOT EXISTS `tbl_person` (
  `person_ID` int(11) NOT NULL AUTO_INCREMENT,
  `collector_IDfk` int(11) DEFAULT NULL,
  `litauthor_IDfk` int(11) DEFAULT NULL,
  `taxauthor_IDfk` int(11) DEFAULT NULL,
  `IPNIauthor_IDfk` varchar(50) DEFAULT NULL,
  `IPNI_version` varchar(25) DEFAULT NULL,
  `HUHbotanist_IDfk` varchar(50) DEFAULT NULL,
  `p_abbrev` varchar(255) NOT NULL,
  `p_firstname` varchar(50) DEFAULT NULL,
  `p_familyname` varchar(50) NOT NULL,
  `p_givenname` varchar(255) DEFAULT NULL,
  `p_birthdate` varchar(11) DEFAULT NULL,
  `p_birthplace` varchar(255) DEFAULT NULL COMMENT 'place of birth',
  `p_death` varchar(11) DEFAULT NULL,
  `p_deathplace` varchar(255) DEFAULT NULL COMMENT 'place of death',
  `locked` tinyint(4) NOT NULL DEFAULT '1',
  `p_annotation` longtext,
  `p_biography_short_eng` longtext,
  `p_biography_short_ger` longtext,
  PRIMARY KEY (`person_ID`),
  UNIQUE KEY `collector_IDfk` (`collector_IDfk`),
  UNIQUE KEY `litauthor_IDfk` (`litauthor_IDfk`),
  UNIQUE KEY `taxauthor_IDfk` (`taxauthor_IDfk`),
  UNIQUE KEY `IPNIauthor_IDfk` (`IPNIauthor_IDfk`),
  UNIQUE KEY `HUHbotanist_IDfk` (`HUHbotanist_IDfk`),
  KEY `p_firstname` (`p_firstname`),
  KEY `p_givenname` (`p_givenname`),
  KEY `p_birthdate` (`p_birthdate`),
  KEY `p_death` (`p_death`),
  KEY `p_familyname` (`p_familyname`),
  KEY `p_abbrev` (`p_abbrev`),
  KEY `IPNI_version` (`IPNI_version`),
  KEY `locked` (`locked`),
  KEY `birthplace` (`p_birthplace`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='complete persons list' AUTO_INCREMENT=90011 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_specimens`
--

CREATE TABLE IF NOT EXISTS `tbl_specimens` (
  `specimen_ID` int(11) NOT NULL AUTO_INCREMENT,
  `HerbNummer` varchar(25) DEFAULT NULL COMMENT 'hier muß ein wert drinnen stehen der als eindeutige Nummer innerhalb der Sammlung feststeht  ',
  `collectionID` int(11) NOT NULL DEFAULT '0',
  `CollNummer` varchar(25) DEFAULT NULL,
  `identstatusID` int(11) DEFAULT NULL,
  `checked` tinyint(4) NOT NULL DEFAULT '-1',
  `accessible` tinyint(4) NOT NULL DEFAULT '-1',
  `taxonID` int(11) NOT NULL DEFAULT '0',
  `SammlerID` int(11) NOT NULL DEFAULT '0',
  `Sammler_2ID` int(11) DEFAULT NULL,
  `seriesID` int(11) DEFAULT NULL,
  `series_number` varchar(50) DEFAULT NULL,
  `Nummer` int(11) DEFAULT NULL,
  `alt_number` varchar(50) DEFAULT NULL,
  `Datum` varchar(25) DEFAULT NULL,
  `Datum2` varchar(25) DEFAULT NULL,
  `det` varchar(255) DEFAULT NULL,
  `typified` varchar(255) DEFAULT NULL,
  `typusID` int(11) DEFAULT NULL,
  `taxon_alt` text,
  `NationID` int(11) DEFAULT NULL,
  `provinceID` int(11) DEFAULT NULL,
  `Bezirk` varchar(50) DEFAULT NULL,
  `Coord_W` int(11) DEFAULT NULL,
  `W_Min` int(11) DEFAULT NULL,
  `W_Sec` double DEFAULT NULL,
  `Coord_N` int(11) DEFAULT NULL,
  `N_Min` int(11) DEFAULT NULL,
  `N_Sec` double DEFAULT NULL,
  `Coord_S` int(11) DEFAULT NULL,
  `S_Min` int(11) DEFAULT NULL,
  `S_Sec` double DEFAULT NULL,
  `Coord_E` int(11) DEFAULT NULL,
  `E_Min` int(11) DEFAULT NULL,
  `E_Sec` double DEFAULT NULL,
  `quadrant` int(11) DEFAULT NULL,
  `quadrant_sub` int(11) DEFAULT NULL,
  `exactness` double DEFAULT NULL,
  `altitude_min` int(11) DEFAULT NULL,
  `altitude_max` int(11) DEFAULT NULL,
  `Fundort` longtext,
  `Fundort_engl` longtext,
  `habitat` longtext,
  `habitus` longtext,
  `Bemerkungen` longtext,
  `aktualdatum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `eingabedatum` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `digital_image` tinyint(4) DEFAULT NULL,
  `garten` varchar(50) DEFAULT NULL,
  `voucherID` int(11) DEFAULT NULL,
  `ncbi_accession` varchar(50) DEFAULT NULL,
  `foreign_db_ID` text,
  `label` tinyint(4) NOT NULL DEFAULT '0',
  `observation` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'assign status of dataset as "observation" vs. regular specimenspecimen',
  `digital_image_obs` tinyint(4) DEFAULT NULL,
  `filename` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`specimen_ID`),
  KEY `aktualdatum` (`aktualdatum`),
  KEY `altitude_max` (`altitude_max`),
  KEY `altitude_min` (`altitude_min`),
  KEY `digital_image` (`digital_image`),
  KEY `eingabedatum` (`eingabedatum`),
  KEY `collectionID` (`collectionID`),
  KEY `taxonID` (`taxonID`),
  KEY `NationID` (`NationID`),
  KEY `HerbNummer` (`HerbNummer`),
  KEY `provinceID` (`provinceID`),
  KEY `quadrant` (`quadrant`),
  KEY `quadrant_sub` (`quadrant_sub`),
  KEY `Sammler_2ID` (`Sammler_2ID`),
  KEY `SammlerID` (`SammlerID`),
  KEY `typusID` (`typusID`),
  KEY `seriesID` (`seriesID`),
  KEY `CollNummer` (`CollNummer`),
  KEY `filename` (`filename`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=225159 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_specimens_identstatus`
--

CREATE TABLE IF NOT EXISTS `tbl_specimens_identstatus` (
  `identstatusID` int(11) NOT NULL AUTO_INCREMENT,
  `identification_status` varchar(255) NOT NULL DEFAULT '""',
  PRIMARY KEY (`identstatusID`),
  UNIQUE KEY `identification_status` (`identification_status`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=11 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_specimens_import`
--

CREATE TABLE IF NOT EXISTS `tbl_specimens_import` (
  `specimen_ID` int(11) NOT NULL AUTO_INCREMENT,
  `HerbNummer` varchar(25) DEFAULT NULL COMMENT 'hier muß ein wert drinnen stehen der als eindeutige Nummer innerhalb der Sammlung feststeht  ',
  `collectionID` int(11) NOT NULL DEFAULT '0',
  `CollNummer` varchar(25) DEFAULT NULL,
  `identstatusID` int(11) DEFAULT NULL,
  `checked` tinyint(4) NOT NULL DEFAULT '0',
  `accessible` tinyint(4) NOT NULL DEFAULT '0',
  `taxonID` int(11) NOT NULL DEFAULT '0',
  `SammlerID` int(11) NOT NULL DEFAULT '0',
  `Sammler_2ID` int(11) DEFAULT NULL,
  `seriesID` int(11) DEFAULT NULL,
  `series_number` varchar(50) DEFAULT NULL,
  `Nummer` int(11) DEFAULT NULL,
  `alt_number` varchar(50) DEFAULT NULL,
  `Datum` varchar(25) DEFAULT NULL,
  `Datum2` varchar(25) DEFAULT NULL,
  `det` varchar(255) DEFAULT NULL,
  `typified` varchar(255) DEFAULT NULL,
  `typusID` int(11) DEFAULT NULL,
  `taxon_alt` text,
  `NationID` int(11) DEFAULT NULL,
  `provinceID` int(11) DEFAULT NULL,
  `Bezirk` varchar(50) DEFAULT NULL,
  `Coord_W` int(11) DEFAULT NULL,
  `W_Min` int(11) DEFAULT NULL,
  `W_Sec` double DEFAULT NULL,
  `Coord_N` int(11) DEFAULT NULL,
  `N_Min` int(11) DEFAULT NULL,
  `N_Sec` double DEFAULT NULL,
  `Coord_S` int(11) DEFAULT NULL,
  `S_Min` int(11) DEFAULT NULL,
  `S_Sec` double DEFAULT NULL,
  `Coord_E` int(11) DEFAULT NULL,
  `E_Min` int(11) DEFAULT NULL,
  `E_Sec` double DEFAULT NULL,
  `quadrant` int(11) DEFAULT NULL,
  `quadrant_sub` int(11) DEFAULT NULL,
  `exactness` double DEFAULT NULL,
  `altitude_min` int(11) DEFAULT NULL,
  `altitude_max` int(11) DEFAULT NULL,
  `Fundort` longtext,
  `Fundort_engl` longtext,
  `habitat` longtext,
  `habitus` longtext,
  `Bemerkungen` longtext,
  `aktualdatum` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `eingabedatum` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `digital_image` tinyint(4) DEFAULT NULL,
  `garten` varchar(50) DEFAULT NULL,
  `voucherID` int(11) DEFAULT NULL,
  `ncbi_accession` varchar(50) DEFAULT NULL,
  `foreign_db_ID` text,
  `label` tinyint(4) NOT NULL DEFAULT '0',
  `observation` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'assign status of dataset as "observation" vs. regular specimenspecimen',
  `digital_image_obs` tinyint(4) DEFAULT NULL,
  `userID` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`specimen_ID`),
  KEY `aktualdatum` (`aktualdatum`),
  KEY `altitude_max` (`altitude_max`),
  KEY `altitude_min` (`altitude_min`),
  KEY `digital_image` (`digital_image`),
  KEY `eingabedatum` (`eingabedatum`),
  KEY `collectionID` (`collectionID`),
  KEY `taxonID` (`taxonID`),
  KEY `NationID` (`NationID`),
  KEY `HerbNummer` (`HerbNummer`),
  KEY `provinceID` (`provinceID`),
  KEY `quadrant` (`quadrant`),
  KEY `quadrant_sub` (`quadrant_sub`),
  KEY `Sammler_2ID` (`Sammler_2ID`),
  KEY `SammlerID` (`SammlerID`),
  KEY `typusID` (`typusID`),
  KEY `seriesID` (`seriesID`),
  KEY `CollNummer` (`CollNummer`),
  KEY `userID` (`userID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=13726 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_specimens_links`
--

CREATE TABLE IF NOT EXISTS `tbl_specimens_links` (
  `specimens_linkID` int(11) NOT NULL AUTO_INCREMENT,
  `specimen1_ID` int(11) NOT NULL,
  `specimen2_ID` int(11) NOT NULL,
  PRIMARY KEY (`specimens_linkID`),
  UNIQUE KEY `specimen1_ID` (`specimen1_ID`,`specimen2_ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4324 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_specimens_series`
--

CREATE TABLE IF NOT EXISTS `tbl_specimens_series` (
  `seriesID` int(11) NOT NULL AUTO_INCREMENT,
  `series` varchar(255) NOT NULL DEFAULT '""',
  `locked` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`seriesID`),
  UNIQUE KEY `series` (`series`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1486 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_specimens_taxa`
--

CREATE TABLE IF NOT EXISTS `tbl_specimens_taxa` (
  `specimens_tax_ID` int(11) NOT NULL AUTO_INCREMENT,
  `specimen_ID` int(11) NOT NULL,
  `taxonID` int(11) NOT NULL,
  PRIMARY KEY (`specimens_tax_ID`),
  UNIQUE KEY `specimen_ID_taxonID` (`specimen_ID`,`taxonID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=12 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_specimens_types`
--

CREATE TABLE IF NOT EXISTS `tbl_specimens_types` (
  `specimens_types_ID` int(11) NOT NULL AUTO_INCREMENT,
  `taxonID` int(11) NOT NULL DEFAULT '0',
  `specimenID` int(11) NOT NULL DEFAULT '0',
  `typusID` int(11) NOT NULL DEFAULT '0',
  `typified_by_Person` varchar(255) NOT NULL DEFAULT '',
  `typified_Date` varchar(10) NOT NULL DEFAULT '',
  `annotations` longtext,
  `locked` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`specimens_types_ID`),
  UNIQUE KEY `typification` (`taxonID`,`specimenID`,`typusID`,`typified_by_Person`,`typified_Date`),
  KEY `specimenID` (`specimenID`),
  KEY `taxindID` (`taxonID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=56142 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_specimens_voucher`
--

CREATE TABLE IF NOT EXISTS `tbl_specimens_voucher` (
  `voucherID` int(11) NOT NULL AUTO_INCREMENT,
  `voucher` varchar(255) NOT NULL DEFAULT '""',
  PRIMARY KEY (`voucherID`),
  UNIQUE KEY `voucher` (`voucher`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=12 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_tax_authors`
--

CREATE TABLE IF NOT EXISTS `tbl_tax_authors` (
  `authorID` int(11) NOT NULL AUTO_INCREMENT,
  `author` varchar(255) NOT NULL DEFAULT '',
  `Brummit_Powell_full` varchar(250) DEFAULT NULL,
  `locked` tinyint(4) NOT NULL DEFAULT '1',
  `external` tinyint(4) NOT NULL DEFAULT '0',
  `externalID` int(11) DEFAULT NULL,
  PRIMARY KEY (`authorID`),
  KEY `Brummit_Powell_full` (`Brummit_Powell_full`),
  KEY `author` (`author`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=116790 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_tax_chorol_status`
--

CREATE TABLE IF NOT EXISTS `tbl_tax_chorol_status` (
  `tax_chorol_status_ID` int(11) NOT NULL AUTO_INCREMENT,
  `taxonID_fk` int(11) NOT NULL,
  `citationID_fk` int(11) DEFAULT NULL,
  `personID_fk` int(11) DEFAULT NULL,
  `serviceID_fk` tinyint(4) DEFAULT NULL,
  `chorol_status` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `status_debatable` tinyint(4) NOT NULL DEFAULT '0',
  `NationID_fk` int(11) NOT NULL,
  `provinceID_fk` int(11) DEFAULT NULL,
  `province_debatable` tinyint(4) NOT NULL DEFAULT '0',
  `dateLastEdited` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `locked` int(11) NOT NULL,
  PRIMARY KEY (`tax_chorol_status_ID`),
  KEY `taxonID_fk` (`taxonID_fk`,`citationID_fk`,`personID_fk`,`serviceID_fk`,`chorol_status`,`locked`),
  KEY `NationID_fk` (`NationID_fk`,`provinceID_fk`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=166 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_tax_epithets`
--

CREATE TABLE IF NOT EXISTS `tbl_tax_epithets` (
  `epithet` varchar(50) NOT NULL DEFAULT '',
  `epithetID` int(11) NOT NULL AUTO_INCREMENT,
  `locked` tinyint(4) NOT NULL DEFAULT '1',
  `external` tinyint(4) NOT NULL DEFAULT '0',
  `externalID` int(11) DEFAULT NULL,
  PRIMARY KEY (`epithetID`),
  UNIQUE KEY `epithet` (`epithet`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=135370 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_tax_families`
--

CREATE TABLE IF NOT EXISTS `tbl_tax_families` (
  `familyID` int(11) NOT NULL AUTO_INCREMENT,
  `family` varchar(50) NOT NULL DEFAULT '',
  `authorID` int(11) NOT NULL DEFAULT '0',
  `categoryID` int(11) NOT NULL DEFAULT '0',
  `family_alt` varchar(100) DEFAULT NULL,
  `locked` tinyint(4) NOT NULL DEFAULT '1',
  `external` tinyint(4) NOT NULL DEFAULT '0',
  `externalID` int(11) DEFAULT NULL,
  PRIMARY KEY (`familyID`),
  KEY `categoryID` (`categoryID`),
  KEY `family` (`family`),
  KEY `authorID` (`authorID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1216 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_tax_genera`
--

CREATE TABLE IF NOT EXISTS `tbl_tax_genera` (
  `genID` int(11) NOT NULL AUTO_INCREMENT,
  `genID_old` int(11) DEFAULT NULL,
  `genus` varchar(100) NOT NULL DEFAULT '',
  `authorID` int(11) DEFAULT NULL,
  `DallaTorreIDs` int(11) DEFAULT NULL,
  `DallaTorreZusatzIDs` char(1) DEFAULT NULL,
  `genID_inc0406` int(11) DEFAULT NULL,
  `hybrid` varchar(10) DEFAULT NULL,
  `familyID` int(11) NOT NULL DEFAULT '0',
  `remarks` longtext,
  `accepted` tinyint(4) DEFAULT NULL,
  `fk_taxonID` int(11) DEFAULT NULL,
  `locked` tinyint(4) NOT NULL DEFAULT '1',
  `external` tinyint(4) NOT NULL DEFAULT '0',
  `externalID` int(11) DEFAULT NULL,
  PRIMARY KEY (`genID`),
  KEY `familyID` (`familyID`),
  KEY `authorID` (`authorID`),
  KEY `DallaTorreIDs` (`DallaTorreIDs`),
  KEY `hybrid` (`hybrid`),
  KEY `genus` (`genus`),
  KEY `fk_taxonID` (`fk_taxonID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=30451 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_tax_hybrids`
--

CREATE TABLE IF NOT EXISTS `tbl_tax_hybrids` (
  `hybrid_ID` int(11) NOT NULL AUTO_INCREMENT,
  `taxon_ID_fk` int(11) NOT NULL DEFAULT '0',
  `parent_1_ID` int(11) NOT NULL DEFAULT '0',
  `parent_2_ID` int(11) NOT NULL DEFAULT '0',
  `parent_3_ID` int(11) NOT NULL,
  PRIMARY KEY (`hybrid_ID`),
  UNIQUE KEY `taxon_ID_fk` (`taxon_ID_fk`),
  UNIQUE KEY `parent_ID` (`parent_1_ID`,`parent_2_ID`,`parent_3_ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=684 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_tax_index`
--

CREATE TABLE IF NOT EXISTS `tbl_tax_index` (
  `taxindID` int(11) NOT NULL AUTO_INCREMENT,
  `taxonID` int(11) NOT NULL DEFAULT '0',
  `citationID` int(11) NOT NULL DEFAULT '0',
  `paginae` varchar(50) DEFAULT NULL,
  `date_paginae` varchar(10) DEFAULT NULL,
  `figures` varchar(255) DEFAULT NULL,
  `date_figures` varchar(10) DEFAULT NULL,
  `annotations` longtext,
  `locked` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`taxonID`,`citationID`),
  KEY `citationID` (`citationID`),
  KEY `taxindID` (`taxindID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=53290 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_tax_orders`
--

CREATE TABLE IF NOT EXISTS `tbl_tax_orders` (
  `orderID` int(11) NOT NULL AUTO_INCREMENT,
  `order` varchar(50) NOT NULL DEFAULT '',
  `authorID` int(11) DEFAULT NULL,
  `categoryID` int(11) NOT NULL DEFAULT '0',
  `locked` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`orderID`),
  KEY `categoryID` (`categoryID`),
  KEY `family` (`order`),
  KEY `authorID` (`authorID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_tax_rank`
--

CREATE TABLE IF NOT EXISTS `tbl_tax_rank` (
  `tax_rankID` int(11) NOT NULL AUTO_INCREMENT,
  `rank` varchar(255) NOT NULL DEFAULT '',
  `rank_latin` varchar(255) DEFAULT NULL,
  `bot_rank_suffix` varchar(50) DEFAULT NULL COMMENT 'botanical naming conventions',
  `zoo_rank_suffix` varchar(50) DEFAULT NULL COMMENT 'zoological naming conventions',
  `rank_hierarchy` int(11) NOT NULL DEFAULT '0',
  `rank_abbr` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`tax_rankID`),
  UNIQUE KEY `zoo_rank_ending` (`zoo_rank_suffix`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=29 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_tax_relationships`
--

CREATE TABLE IF NOT EXISTS `tbl_tax_relationships` (
  `tax_relation_ID` int(11) NOT NULL AUTO_INCREMENT,
  `relation_term` varchar(50) NOT NULL,
  `explanation` text,
  `locked` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`tax_relation_ID`),
  UNIQUE KEY `relation_term` (`relation_term`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='lookup table for assining taxonomic relationship between two' AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_tax_scrutiny`
--

CREATE TABLE IF NOT EXISTS `tbl_tax_scrutiny` (
  `scrutiny_ID` int(11) NOT NULL AUTO_INCREMENT,
  `taxonID` int(11) NOT NULL DEFAULT '0',
  `citationID` int(11) NOT NULL DEFAULT '0',
  `scrutiny_person_ID` int(11) NOT NULL DEFAULT '0',
  `date` varchar(25) DEFAULT NULL,
  `annotation` longtext NOT NULL,
  PRIMARY KEY (`scrutiny_ID`),
  KEY `taxonID` (`taxonID`),
  KEY `citationID` (`citationID`),
  KEY `scrutiny_person_ID` (`scrutiny_person_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_tax_species`
--

CREATE TABLE IF NOT EXISTS `tbl_tax_species` (
  `tax_rankID` int(11) NOT NULL DEFAULT '1',
  `basID` int(11) DEFAULT NULL,
  `taxonID` int(11) NOT NULL AUTO_INCREMENT,
  `synID` int(11) DEFAULT NULL,
  `statusID` int(11) NOT NULL DEFAULT '96',
  `genID` int(11) NOT NULL DEFAULT '0',
  `speciesID` int(11) DEFAULT NULL,
  `authorID` int(11) DEFAULT NULL,
  `subspeciesID` int(11) DEFAULT NULL,
  `subspecies_authorID` int(11) DEFAULT NULL,
  `varietyID` int(11) DEFAULT NULL,
  `variety_authorID` int(11) DEFAULT NULL,
  `subvarietyID` int(11) DEFAULT NULL,
  `subvariety_authorID` int(11) DEFAULT NULL,
  `formaID` int(11) DEFAULT NULL,
  `forma_authorID` int(11) DEFAULT NULL,
  `subformaID` int(11) DEFAULT NULL,
  `subforma_authorID` int(11) DEFAULT NULL,
  `annotation` longtext,
  `IPNItax_IDfk` varchar(50) DEFAULT NULL COMMENT 'foreign key to IPNI nameslist',
  `IPNI_version` varchar(25) DEFAULT NULL,
  `API_taxID_fk` varchar(50) DEFAULT NULL COMMENT 'foreign key to API nameslist',
  `tropicos_taxID_fk` varchar(50) DEFAULT NULL COMMENT 'foreign key to TROPICOS nameslist',
  `linn_taxID_fk` varchar(50) DEFAULT NULL COMMENT 'foreign key to linnean nameslist',
  `locked` tinyint(4) NOT NULL DEFAULT '1',
  `external` tinyint(4) NOT NULL DEFAULT '0',
  `externalID` int(11) DEFAULT NULL,
  PRIMARY KEY (`taxonID`),
  UNIQUE KEY `API_taxID_fk` (`API_taxID_fk`,`tropicos_taxID_fk`,`linn_taxID_fk`),
  KEY `basID` (`basID`),
  KEY `forma_authorID` (`forma_authorID`),
  KEY `formaID` (`formaID`),
  KEY `genID` (`genID`),
  KEY `speciesID` (`speciesID`),
  KEY `statusID` (`statusID`),
  KEY `subspecies_authorID` (`subspecies_authorID`),
  KEY `subspeciesID` (`subspeciesID`),
  KEY `synID` (`synID`),
  KEY `authorID` (`authorID`),
  KEY `variety_authorID` (`variety_authorID`),
  KEY `varietyID` (`varietyID`),
  KEY `subvarietyID` (`subvarietyID`),
  KEY `subvariety_authorID` (`subvariety_authorID`),
  KEY `subformaID` (`subformaID`),
  KEY `subforma_authorID` (`subforma_authorID`),
  KEY `IPNItax_IDfk` (`IPNItax_IDfk`),
  KEY `IPNI_version` (`IPNI_version`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=204719 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_tax_status`
--

CREATE TABLE IF NOT EXISTS `tbl_tax_status` (
  `statusID` int(11) NOT NULL AUTO_INCREMENT,
  `status` varchar(50) DEFAULT NULL,
  `status_description` varchar(50) DEFAULT NULL,
  `status_sp2000` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`statusID`),
  KEY `status` (`status`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=122 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_tax_synonymy`
--

CREATE TABLE IF NOT EXISTS `tbl_tax_synonymy` (
  `tax_syn_ID` int(11) NOT NULL AUTO_INCREMENT,
  `taxonID` int(11) NOT NULL DEFAULT '0',
  `acc_taxon_ID` int(11) NOT NULL DEFAULT '0',
  `preferred_taxonomy` tinyint(4) NOT NULL DEFAULT '0',
  `annotations` longtext,
  `locked` tinyint(4) NOT NULL DEFAULT '1',
  `source` varchar(20) NOT NULL DEFAULT 'person',
  `source_citationID` int(11) DEFAULT NULL,
  `source_person_ID` int(11) DEFAULT '39269',
  `source_serviceID` int(11) DEFAULT NULL,
  `userID` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`tax_syn_ID`),
  UNIQUE KEY `unique_syn_tax_cit` (`taxonID`,`acc_taxon_ID`,`source_citationID`,`source_person_ID`),
  KEY `taxonID` (`taxonID`),
  KEY `acc_taxon_ID` (`acc_taxon_ID`),
  KEY `locked` (`locked`),
  KEY `userID` (`userID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=20312 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_tax_systematic_categories`
--

CREATE TABLE IF NOT EXISTS `tbl_tax_systematic_categories` (
  `categoryID` int(11) NOT NULL DEFAULT '0',
  `category` varchar(2) NOT NULL DEFAULT '',
  `cat_description` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`categoryID`),
  KEY `category` (`category`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_tax_typecollections`
--

CREATE TABLE IF NOT EXISTS `tbl_tax_typecollections` (
  `typecollID` int(11) NOT NULL AUTO_INCREMENT,
  `taxonID` int(11) NOT NULL DEFAULT '0',
  `SammlerID` int(11) NOT NULL DEFAULT '0',
  `Sammler_2ID` int(11) DEFAULT NULL,
  `typusID` int(11) NOT NULL DEFAULT '0',
  `series` varchar(250) DEFAULT NULL,
  `leg_nr` int(11) DEFAULT NULL,
  `alternate_number` varchar(250) DEFAULT NULL,
  `date` varchar(50) DEFAULT NULL,
  `duplicates` varchar(250) DEFAULT NULL,
  `annotation` longtext,
  `locked` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`typecollID`),
  KEY `Sammler_2ID` (`Sammler_2ID`),
  KEY `SammlerID` (`SammlerID`),
  KEY `taxonID` (`taxonID`),
  KEY `typusID` (`typusID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=15904 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_typi`
--

CREATE TABLE IF NOT EXISTS `tbl_typi` (
  `typusID` int(11) NOT NULL AUTO_INCREMENT,
  `typus` varchar(10) NOT NULL DEFAULT '',
  `typus_lat` varchar(255) NOT NULL DEFAULT '',
  `typus_description` varchar(255) DEFAULT NULL,
  `typus_engl` varchar(255) NOT NULL DEFAULT '',
  `typus_api_standard` varchar(255) NOT NULL DEFAULT '',
  `typus_icbn` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`typusID`),
  KEY `typus` (`typus`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=35 ;
--
-- Datenbank: `herbarinput_log`
--
CREATE DATABASE `herbarinput_log` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `herbarinput_log`;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_lit`
--

CREATE TABLE IF NOT EXISTS `log_lit` (
  `log_citationID` int(11) NOT NULL AUTO_INCREMENT,
  `citationID` int(11) NOT NULL DEFAULT '0',
  `lit_url` varchar(255) DEFAULT NULL,
  `autorID` int(11) NOT NULL DEFAULT '0',
  `jahr` varchar(50) DEFAULT NULL,
  `code` varchar(3) DEFAULT NULL,
  `titel` varchar(250) DEFAULT NULL,
  `suptitel` varchar(250) DEFAULT NULL,
  `editorsID` int(11) DEFAULT NULL,
  `periodicalID` int(11) DEFAULT NULL,
  `vol` varchar(20) DEFAULT NULL,
  `part` varchar(50) DEFAULT NULL,
  `pp` varchar(150) DEFAULT NULL,
  `publisherID` int(11) DEFAULT NULL,
  `verlagsort` varchar(100) DEFAULT NULL,
  `keywords` varchar(100) DEFAULT NULL,
  `annotation` longtext,
  `additions` longtext,
  `bestand` varchar(50) DEFAULT NULL,
  `signature` varchar(50) DEFAULT NULL,
  `publ` char(1) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `userID` int(11) NOT NULL DEFAULT '0',
  `updated` tinyint(4) NOT NULL DEFAULT '0',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_citationID`),
  KEY `autorID` (`autorID`),
  KEY `category` (`category`),
  KEY `citationID` (`citationID`),
  KEY `code` (`code`),
  KEY `editorsID` (`editorsID`),
  KEY `jahr` (`jahr`),
  KEY `periodicalID` (`periodicalID`),
  KEY `publisherID` (`publisherID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=26142 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_lit_authors`
--

CREATE TABLE IF NOT EXISTS `log_lit_authors` (
  `log_autorID` int(11) NOT NULL AUTO_INCREMENT,
  `autorID` int(11) NOT NULL DEFAULT '0',
  `autor` varchar(150) DEFAULT NULL,
  `autorsystbot` varchar(150) DEFAULT NULL,
  `userID` int(11) NOT NULL DEFAULT '0',
  `updated` tinyint(4) NOT NULL DEFAULT '0',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_autorID`),
  KEY `autor` (`autor`),
  KEY `autorID` (`autorID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3403 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_lit_periodicals`
--

CREATE TABLE IF NOT EXISTS `log_lit_periodicals` (
  `log_periodicalID` int(11) NOT NULL AUTO_INCREMENT,
  `periodicalID` int(11) NOT NULL DEFAULT '0',
  `periodical` varchar(250) DEFAULT NULL,
  `periodical_full` varchar(250) DEFAULT NULL,
  `tl2_number` int(11) DEFAULT '0',
  `bph_number` varchar(15) DEFAULT NULL,
  `ipni_ID` varchar(15) DEFAULT NULL,
  `userID` int(11) NOT NULL DEFAULT '0',
  `updated` tinyint(4) NOT NULL DEFAULT '0',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_periodicalID`),
  KEY `periodical_full` (`periodical_full`),
  KEY `periodical` (`periodical`),
  KEY `periodicalID` (`periodicalID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3953 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_lit_publishers`
--

CREATE TABLE IF NOT EXISTS `log_lit_publishers` (
  `log_publisherID` int(11) NOT NULL AUTO_INCREMENT,
  `publisherID` int(11) NOT NULL DEFAULT '0',
  `publisher` varchar(100) DEFAULT NULL,
  `userID` int(11) NOT NULL DEFAULT '0',
  `updated` tinyint(4) NOT NULL DEFAULT '0',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_publisherID`),
  KEY `publisher` (`publisher`),
  KEY `publisherID` (`publisherID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=512 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_lit_taxa`
--

CREATE TABLE IF NOT EXISTS `log_lit_taxa` (
  `log_lit_taxaID` int(11) NOT NULL AUTO_INCREMENT,
  `lit_tax_ID` int(11) NOT NULL,
  `citationID` int(11) NOT NULL DEFAULT '0',
  `taxonID` int(11) DEFAULT NULL,
  `acc_taxon_ID` int(11) NOT NULL DEFAULT '0',
  `annotations` longtext,
  `locked` tinyint(4) NOT NULL DEFAULT '1',
  `source` varchar(20) NOT NULL DEFAULT 'person',
  `source_citationID` int(11) DEFAULT NULL,
  `source_person_ID` int(11) DEFAULT '39269',
  `et_al` tinyint(4) NOT NULL DEFAULT '0',
  `userID` int(11) NOT NULL,
  `updated` tinyint(4) NOT NULL DEFAULT '0',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_lit_taxaID`),
  KEY `revision` (`citationID`,`taxonID`,`acc_taxon_ID`,`source_citationID`,`source_person_ID`),
  KEY `userID` (`userID`),
  KEY `lit_tax_ID` (`lit_tax_ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1671 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_lit_taxa_old`
--

CREATE TABLE IF NOT EXISTS `log_lit_taxa_old` (
  `log_lit_taxa_ID` int(11) NOT NULL AUTO_INCREMENT,
  `lit_tax_ID` int(11) NOT NULL DEFAULT '0',
  `citationID` int(11) NOT NULL DEFAULT '0',
  `taxonID` int(11) NOT NULL DEFAULT '0',
  `acc_taxon_ID` int(11) NOT NULL DEFAULT '0',
  `annotations` longtext,
  `userID` int(11) NOT NULL DEFAULT '0',
  `updated` tinyint(4) NOT NULL DEFAULT '0',
  `timestamp` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`log_lit_taxa_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_specimens`
--

CREATE TABLE IF NOT EXISTS `log_specimens` (
  `log_specimensID` int(11) NOT NULL AUTO_INCREMENT,
  `specimenID` int(11) NOT NULL DEFAULT '0',
  `userID` int(11) NOT NULL DEFAULT '0',
  `updated` tinyint(4) NOT NULL DEFAULT '0',
  `timestamp` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `HerbNummer` varchar(25) DEFAULT NULL,
  `collectionID` int(11) DEFAULT NULL,
  `CollNummer` varchar(25) DEFAULT NULL,
  `identstatusID` int(11) DEFAULT NULL,
  `checked` tinyint(4) DEFAULT NULL,
  `accessible` tinyint(4) DEFAULT NULL,
  `taxonID` int(11) DEFAULT NULL,
  `SammlerID` int(11) DEFAULT NULL,
  `Sammler_2ID` int(11) DEFAULT NULL,
  `seriesID` int(11) DEFAULT NULL,
  `series_number` varchar(50) DEFAULT NULL,
  `Nummer` int(11) DEFAULT NULL,
  `alt_number` varchar(50) DEFAULT NULL,
  `Datum` varchar(25) DEFAULT NULL,
  `Datum2` varchar(25) DEFAULT NULL,
  `det` varchar(255) DEFAULT NULL,
  `typified` varchar(255) DEFAULT NULL,
  `typusID` int(11) DEFAULT NULL,
  `taxon_alt` text,
  `NationID` int(11) DEFAULT NULL,
  `provinceID` int(11) DEFAULT NULL,
  `Bezirk` varchar(50) DEFAULT NULL,
  `Coord_W` int(11) DEFAULT NULL,
  `W_Min` int(11) DEFAULT NULL,
  `W_Sec` double DEFAULT NULL,
  `Coord_N` int(11) DEFAULT NULL,
  `N_Min` int(11) DEFAULT NULL,
  `N_Sec` double DEFAULT NULL,
  `Coord_S` int(11) DEFAULT NULL,
  `S_Min` int(11) DEFAULT NULL,
  `S_Sec` double DEFAULT NULL,
  `Coord_E` int(11) DEFAULT NULL,
  `E_Min` int(11) DEFAULT NULL,
  `E_Sec` double DEFAULT NULL,
  `quadrant` int(11) DEFAULT NULL,
  `quadrant_sub` int(11) DEFAULT NULL,
  `exactness` double DEFAULT NULL,
  `altitude_min` int(11) DEFAULT NULL,
  `altitude_max` int(11) DEFAULT NULL,
  `Fundort` longtext,
  `habitat` longtext,
  `habitus` longtext,
  `Bemerkungen` longtext,
  `aktualdatum` datetime DEFAULT NULL,
  `eingabedatum` datetime DEFAULT NULL,
  `digital_image` tinyint(4) DEFAULT NULL,
  `garten` varchar(50) DEFAULT NULL,
  `voucherID` int(11) DEFAULT NULL,
  `ncbi_accession` varchar(50) DEFAULT NULL,
  `foreign_db_ID` text,
  `label` tinyint(4) DEFAULT NULL,
  `observation` tinyint(4) DEFAULT NULL,
  `digital_image_obs` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`log_specimensID`),
  KEY `specimenID` (`specimenID`),
  KEY `timestamp` (`timestamp`),
  KEY `userID` (`userID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=368748 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_specimens_series`
--

CREATE TABLE IF NOT EXISTS `log_specimens_series` (
  `log_seriesID` int(11) NOT NULL AUTO_INCREMENT,
  `seriesID` int(11) NOT NULL,
  `series` varchar(255) NOT NULL,
  `locked` tinyint(4) NOT NULL,
  `userID` int(11) NOT NULL,
  `updated` tinyint(4) NOT NULL DEFAULT '0',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_seriesID`),
  KEY `seriesID` (`seriesID`),
  KEY `userID` (`userID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1527 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_specimens_types`
--

CREATE TABLE IF NOT EXISTS `log_specimens_types` (
  `log_specimens_typesID` int(11) NOT NULL AUTO_INCREMENT,
  `specimens_types_ID` int(11) NOT NULL DEFAULT '0',
  `specimenID` int(11) NOT NULL DEFAULT '0',
  `taxonID` int(11) NOT NULL DEFAULT '0',
  `typusID` int(11) NOT NULL DEFAULT '0',
  `annotations` longtext,
  `userID` int(11) NOT NULL DEFAULT '0',
  `updated` tinyint(4) NOT NULL DEFAULT '0',
  `timestamp` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`log_specimens_typesID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=63198 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_tax_authors`
--

CREATE TABLE IF NOT EXISTS `log_tax_authors` (
  `log_tax_authorID` int(11) NOT NULL AUTO_INCREMENT,
  `authorID` int(11) NOT NULL DEFAULT '0',
  `author` varchar(255) NOT NULL DEFAULT '',
  `Brummit_Powell_full` varchar(250) DEFAULT NULL,
  `userID` int(11) NOT NULL DEFAULT '0',
  `updated` tinyint(4) NOT NULL DEFAULT '0',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_tax_authorID`),
  KEY `author` (`author`),
  KEY `authorID` (`authorID`),
  KEY `Brummit_Powell_full` (`Brummit_Powell_full`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=45842 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_tax_chorol_status`
--

CREATE TABLE IF NOT EXISTS `log_tax_chorol_status` (
  `log_tax_chorol_status_ID` int(11) NOT NULL AUTO_INCREMENT,
  `tax_chorol_status_ID` int(11) NOT NULL,
  `taxonID_fk` int(11) NOT NULL,
  `citationID_fk` int(11) DEFAULT NULL,
  `personID_fk` int(11) DEFAULT NULL,
  `serviceID_fk` tinyint(4) DEFAULT NULL,
  `chorol_status` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `NationID_fk` int(11) NOT NULL,
  `provinceID_fk` int(11) DEFAULT NULL,
  `dateLastEdited` datetime NOT NULL,
  `locked` int(11) NOT NULL DEFAULT '0',
  `userID` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_tax_chorol_status_ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=26 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_tax_families`
--

CREATE TABLE IF NOT EXISTS `log_tax_families` (
  `log_tax_familyID` int(11) NOT NULL AUTO_INCREMENT,
  `familyID` int(11) NOT NULL DEFAULT '0',
  `family` varchar(50) NOT NULL DEFAULT '',
  `categoryID` int(11) NOT NULL DEFAULT '0',
  `userID` int(11) NOT NULL DEFAULT '0',
  `updated` tinyint(4) NOT NULL DEFAULT '0',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_tax_familyID`),
  KEY `categoryID` (`categoryID`),
  KEY `familyID` (`familyID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1232 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_tax_genera`
--

CREATE TABLE IF NOT EXISTS `log_tax_genera` (
  `log_tax_genID` int(11) NOT NULL AUTO_INCREMENT,
  `genID` int(11) NOT NULL DEFAULT '0',
  `genID_old` int(11) DEFAULT NULL,
  `genus` varchar(100) NOT NULL DEFAULT '',
  `authorID` int(11) DEFAULT NULL,
  `DallaTorreIDs` int(11) DEFAULT NULL,
  `DallaTorreZusatzIDs` char(1) DEFAULT NULL,
  `genID_inc0406` int(11) DEFAULT NULL,
  `hybrid` varchar(10) DEFAULT NULL,
  `familyID` int(11) NOT NULL DEFAULT '0',
  `remarks` longtext,
  `accepted` tinyint(4) DEFAULT NULL,
  `userID` int(11) NOT NULL DEFAULT '0',
  `updated` tinyint(4) NOT NULL DEFAULT '0',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_tax_genID`),
  KEY `familyID` (`familyID`),
  KEY `authorID` (`authorID`),
  KEY `DallaTorreIDs` (`DallaTorreIDs`),
  KEY `hybrid` (`hybrid`),
  KEY `genID` (`genID`),
  KEY `genus` (`genus`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=32741 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_tax_index`
--

CREATE TABLE IF NOT EXISTS `log_tax_index` (
  `log_tax_indexID` int(11) NOT NULL AUTO_INCREMENT,
  `taxindID` int(11) NOT NULL DEFAULT '0',
  `taxonID` int(11) NOT NULL DEFAULT '0',
  `citationID` int(11) NOT NULL DEFAULT '0',
  `paginae` varchar(50) DEFAULT NULL,
  `figures` varchar(255) DEFAULT NULL,
  `annotations` longtext,
  `userID` int(11) NOT NULL DEFAULT '0',
  `updated` tinyint(4) NOT NULL DEFAULT '0',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_tax_indexID`),
  KEY `citationID` (`citationID`),
  KEY `taxindID` (`taxindID`),
  KEY `taxonID` (`taxonID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=63509 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_tax_species`
--

CREATE TABLE IF NOT EXISTS `log_tax_species` (
  `log_tax_speciesID` int(11) NOT NULL AUTO_INCREMENT,
  `tax_rankID` int(11) NOT NULL DEFAULT '0',
  `basID` int(11) DEFAULT NULL,
  `taxonID` int(11) NOT NULL DEFAULT '0',
  `synID` int(11) DEFAULT NULL,
  `statusID` int(11) DEFAULT NULL,
  `genID` int(11) NOT NULL DEFAULT '0',
  `speciesID` int(11) DEFAULT NULL,
  `authorID` int(11) DEFAULT NULL,
  `subspeciesID` int(11) DEFAULT NULL,
  `subspecies_authorID` int(11) DEFAULT NULL,
  `varietyID` int(11) DEFAULT NULL,
  `variety_authorID` int(11) DEFAULT NULL,
  `subvarietyID` int(11) DEFAULT NULL,
  `subvariety_authorID` int(11) DEFAULT NULL,
  `formaID` int(11) DEFAULT NULL,
  `forma_authorID` int(11) DEFAULT NULL,
  `subformaID` int(11) DEFAULT NULL,
  `subforma_authorID` int(11) DEFAULT NULL,
  `annotation` longtext,
  `userID` int(11) NOT NULL DEFAULT '0',
  `updated` tinyint(4) NOT NULL DEFAULT '0',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_tax_speciesID`),
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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=253885 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_tax_typecollections`
--

CREATE TABLE IF NOT EXISTS `log_tax_typecollections` (
  `log_tax_typecollID` int(11) NOT NULL AUTO_INCREMENT,
  `typecollID` int(11) NOT NULL DEFAULT '0',
  `taxonID` int(11) NOT NULL DEFAULT '0',
  `SammlerID` int(11) NOT NULL DEFAULT '0',
  `Sammler_2ID` int(11) DEFAULT NULL,
  `typusID` int(11) NOT NULL DEFAULT '0',
  `series` varchar(250) DEFAULT NULL,
  `leg_nr` int(11) DEFAULT NULL,
  `alternate_number` varchar(250) DEFAULT NULL,
  `date` varchar(50) DEFAULT NULL,
  `duplicates` varchar(250) DEFAULT NULL,
  `annotation` longtext,
  `userID` int(11) NOT NULL DEFAULT '0',
  `updated` tinyint(4) NOT NULL DEFAULT '0',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_tax_typecollID`),
  KEY `Sammler_2ID` (`Sammler_2ID`),
  KEY `SammlerID` (`SammlerID`),
  KEY `taxonID` (`taxonID`),
  KEY `typusID` (`typusID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=21967 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `meta`
--

CREATE TABLE IF NOT EXISTS `meta` (
  `source_id` int(11) NOT NULL DEFAULT '0',
  `source_code` char(250) DEFAULT NULL,
  `source_name` char(250) DEFAULT NULL,
  `source_update` datetime DEFAULT NULL,
  `source_version` char(250) DEFAULT NULL,
  `source_url` char(250) DEFAULT NULL,
  `source_expiry` datetime DEFAULT NULL,
  `source_number_of_records` int(11) DEFAULT NULL,
  `source_abbr_engl` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`source_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `meta_old`
--

CREATE TABLE IF NOT EXISTS `meta_old` (
  `source_id` int(11) NOT NULL DEFAULT '0',
  `source_code` longtext,
  `source_name` longtext,
  `source_update` datetime DEFAULT NULL,
  `source_version` longtext,
  `source_url` longtext,
  `source_expiry` datetime DEFAULT NULL,
  `source_number_of_records` int(11) DEFAULT NULL,
  PRIMARY KEY (`source_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_herbardb_access`
--

CREATE TABLE IF NOT EXISTS `tbl_herbardb_access` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `userID` int(11) NOT NULL DEFAULT '0',
  `categoryID` int(11) DEFAULT NULL,
  `familyID` int(11) DEFAULT NULL,
  `genID` int(11) DEFAULT NULL,
  `update` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=10 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_herbardb_groups`
--

CREATE TABLE IF NOT EXISTS `tbl_herbardb_groups` (
  `groupID` int(11) NOT NULL AUTO_INCREMENT,
  `group_name` varchar(50) NOT NULL DEFAULT '""',
  `group_description` varchar(255) NOT NULL DEFAULT '""',
  `species` tinyint(4) NOT NULL DEFAULT '0',
  `author` tinyint(4) NOT NULL DEFAULT '0',
  `epithet` tinyint(4) NOT NULL DEFAULT '0',
  `genera` tinyint(4) NOT NULL DEFAULT '0',
  `family` tinyint(4) NOT NULL DEFAULT '0',
  `lit` tinyint(4) NOT NULL DEFAULT '0',
  `litAuthor` tinyint(4) NOT NULL DEFAULT '0',
  `litPer` tinyint(4) NOT NULL DEFAULT '0',
  `litPub` tinyint(4) NOT NULL DEFAULT '0',
  `index` tinyint(4) NOT NULL DEFAULT '0',
  `type` tinyint(4) NOT NULL DEFAULT '0',
  `specimensTypes` tinyint(4) NOT NULL DEFAULT '0',
  `collIns` tinyint(4) NOT NULL DEFAULT '0',
  `collUpd` tinyint(4) NOT NULL DEFAULT '0',
  `seriesIns` tinyint(4) NOT NULL DEFAULT '0',
  `seriesUpd` tinyint(4) NOT NULL DEFAULT '0',
  `specim` tinyint(4) NOT NULL DEFAULT '0',
  `dt` tinyint(4) NOT NULL DEFAULT '0',
  `chorol` tinyint(4) NOT NULL DEFAULT '0',
  `btnTax` tinyint(4) NOT NULL DEFAULT '0',
  `btnLit` tinyint(4) NOT NULL DEFAULT '0',
  `btnSpc` tinyint(4) NOT NULL DEFAULT '0',
  `btnObs` tinyint(4) NOT NULL DEFAULT '0',
  `btnImg` tinyint(4) NOT NULL DEFAULT '0',
  `btnNom` tinyint(4) NOT NULL DEFAULT '0',
  `btnImport` tinyint(4) NOT NULL DEFAULT '0',
  `linkTaxon` tinyint(4) NOT NULL DEFAULT '0',
  `batch` tinyint(4) NOT NULL DEFAULT '0',
  `batchAdmin` tinyint(4) NOT NULL DEFAULT '0',
  `admin` tinyint(4) NOT NULL DEFAULT '0',
  `editor` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`groupID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=19 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_herbardb_unlock`
--

CREATE TABLE IF NOT EXISTS `tbl_herbardb_unlock` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `groupID` int(11) NOT NULL DEFAULT '0',
  `table` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`ID`),
  KEY `groupID` (`groupID`),
  KEY `table` (`table`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=34 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_herbardb_users`
--

CREATE TABLE IF NOT EXISTS `tbl_herbardb_users` (
  `userID` int(11) NOT NULL AUTO_INCREMENT,
  `groupID` int(11) NOT NULL DEFAULT '0',
  `source_id` int(11) NOT NULL DEFAULT '0',
  `use_access` tinyint(4) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  `username` varchar(255) DEFAULT NULL,
  `firstname` varchar(255) NOT NULL DEFAULT '',
  `surname` varchar(255) NOT NULL DEFAULT '',
  `emailadress` varchar(255) NOT NULL DEFAULT '',
  `phone` varchar(255) DEFAULT NULL,
  `mobile` varchar(255) DEFAULT NULL,
  `editFamily` varchar(255) DEFAULT NULL,
  `login` datetime DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `iv` varchar(255) DEFAULT NULL,
  `secret` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`userID`),
  KEY `groupID` (`groupID`),
  KEY `source_id` (`source_id`),
  KEY `username` (`username`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=119 ;
