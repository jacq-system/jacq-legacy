-- phpMyAdmin SQL Dump
-- version 3.3.9
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 15. September 2011 um 15:21
-- Server Version: 5.0.41
-- PHP-Version: 5.3.4

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Datenbank: `herbarinput`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `meta`
--

DROP TABLE IF EXISTS `meta`;
CREATE TABLE IF NOT EXISTS `meta` (
  `source_id` int(11) NOT NULL default '0',
  `source_code` char(250) default NULL,
  `source_name` char(250) default NULL,
  `source_update` datetime default NULL,
  `source_version` char(250) default NULL,
  `source_url` char(250) default NULL,
  `source_expiry` datetime default NULL,
  `source_number_of_records` int(11) default NULL,
  `source_abbr_engl` varchar(255) default NULL,
  PRIMARY KEY  (`source_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `metadb`
--

DROP TABLE IF EXISTS `metadb`;
CREATE TABLE IF NOT EXISTS `metadb` (
  `db_id` int(11) NOT NULL default '0',
  `source_id_fk` int(11) default NULL,
  `supplier_supplied_when` datetime default NULL,
  `supplier_organisation` varchar(250) default NULL,
  `supplier_organisation_code` varchar(50) default NULL,
  `supplier_person` varchar(250) default NULL,
  `supplier_url` varchar(250) default NULL,
  `supplier_adress` varchar(50) default NULL,
  `supplier_telephone` varchar(50) default NULL,
  `supplier_email` varchar(250) default NULL,
  `legal_owner_organisation` varchar(250) default NULL,
  `legal_owner_organisation_code` varchar(50) default NULL,
  `legal_owner_person` varchar(250) default NULL,
  `legal_owner_adress` varchar(250) default NULL,
  `legal_owner_telephone` varchar(250) default NULL,
  `legal_owner_email` varchar(250) default NULL,
  `legal_owner_url` varchar(250) default NULL,
  `terms_of_use` text,
  `acknowledgement` text,
  `description` text,
  `disclaimer` text,
  `restrictions` text,
  `logo_url` varchar(250) default NULL,
  `statement_url` varchar(250) default NULL,
  `copyright` text,
  `ipr` text,
  `rights_url` varchar(250) default NULL,
  PRIMARY KEY  (`db_id`),
  KEY `source_id_fk` (`source_id_fk`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_chat`
--

DROP TABLE IF EXISTS `tbl_chat`;
CREATE TABLE IF NOT EXISTS `tbl_chat` (
  `ID` int(11) NOT NULL auto_increment,
  `uid` int(11) NOT NULL default '0',
  `chat` text NOT NULL,
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=44 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_chat_priv`
--

DROP TABLE IF EXISTS `tbl_chat_priv`;
CREATE TABLE IF NOT EXISTS `tbl_chat_priv` (
  `ID` int(11) NOT NULL auto_increment,
  `uid` int(11) NOT NULL default '0',
  `tid` int(11) NOT NULL default '0',
  `theme` varchar(255) default NULL,
  `chat` text NOT NULL,
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `seen` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  KEY `tid` (`tid`),
  KEY `timestamp` (`timestamp`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=7630 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_collector`
--

DROP TABLE IF EXISTS `tbl_collector`;
CREATE TABLE IF NOT EXISTS `tbl_collector` (
  `SammlerID` int(11) NOT NULL auto_increment,
  `Sammler` varchar(250) NOT NULL default '',
  `Sammler_FN_List` varchar(50) default NULL,
  `Sammler_FN_short` varchar(50) default NULL,
  `HUH_ID` int(11) default NULL,
  `locked` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`SammlerID`),
  KEY `Sammler` (`Sammler`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=20070 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_collector_2`
--

DROP TABLE IF EXISTS `tbl_collector_2`;
CREATE TABLE IF NOT EXISTS `tbl_collector_2` (
  `Sammler_2ID` int(11) NOT NULL auto_increment,
  `Sammler_2` varchar(120) NOT NULL default '',
  `Sammler_2_FN_list` varchar(250) default NULL,
  `Sammler_2_FN_short` varchar(50) default NULL,
  `locked` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`Sammler_2ID`),
  KEY `Sammler_2` (`Sammler_2`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6804 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_descriptions`
--

DROP TABLE IF EXISTS `tbl_descriptions`;
CREATE TABLE IF NOT EXISTS `tbl_descriptions` (
  `ID` int(11) NOT NULL auto_increment,
  `table` varchar(255) default NULL,
  `column` varchar(255) NOT NULL default '',
  `description` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_external_import`
--

DROP TABLE IF EXISTS `tbl_external_import`;
CREATE TABLE IF NOT EXISTS `tbl_external_import` (
  `externalID` int(11) NOT NULL auto_increment,
  `description` varchar(255) NOT NULL,
  `annotation` longtext,
  `startdate` date default NULL,
  `enddate` date default NULL,
  `used` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`externalID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=7 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_external_import_content`
--

DROP TABLE IF EXISTS `tbl_external_import_content`;
CREATE TABLE IF NOT EXISTS `tbl_external_import_content` (
  `contentID` int(11) NOT NULL auto_increment,
  `filename` varchar(255) NOT NULL,
  `linenumber` int(11) NOT NULL,
  `line` text NOT NULL,
  `processingError` text,
  `userID` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `specimen_ID` int(11) default NULL,
  `pending` tinyint(4) NOT NULL default '0',
  `externalID` int(11) default NULL,
  `taxonID` int(11) default NULL,
  PRIMARY KEY  (`contentID`),
  KEY `specimen_ID` (`specimen_ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=36358 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_geo_nation`
--

DROP TABLE IF EXISTS `tbl_geo_nation`;
CREATE TABLE IF NOT EXISTS `tbl_geo_nation` (
  `nationID` int(11) NOT NULL auto_increment,
  `nation` varchar(50) default NULL,
  `fnnumber` int(11) default NULL,
  `nation_engl` varchar(50) default NULL,
  `nation_deutsch` varchar(50) default NULL,
  `annotation` varchar(250) default NULL,
  `nation_code` varchar(10) default NULL,
  `usgs_code` varchar(5) default NULL,
  `iso_alpha_3_code` varchar(3) default NULL,
  `iso_alpha_2_code` varchar(2) default NULL,
  `regionID_fk` int(11) NOT NULL default '0',
  `language_variants` varchar(2000) default NULL COMMENT 'country names in numerous languages',
  PRIMARY KEY  (`nationID`),
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

DROP TABLE IF EXISTS `tbl_geo_province`;
CREATE TABLE IF NOT EXISTS `tbl_geo_province` (
  `provinceID` int(11) NOT NULL auto_increment,
  `provinz` varchar(100) default NULL,
  `provinz_local` varchar(150) default NULL,
  `provinz_code` varchar(5) character set utf8 collate utf8_unicode_ci default NULL,
  `nationID` int(11) NOT NULL default '0',
  `usgs_number` varchar(5) default NULL,
  PRIMARY KEY  (`provinceID`),
  KEY `nationID` (`nationID`),
  KEY `provinz` (`provinz`),
  KEY `provinz_local1` (`provinz_local`),
  KEY `provinz_code` (`provinz_code`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3554 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_geo_ref_geonames`
--

DROP TABLE IF EXISTS `tbl_geo_ref_geonames`;
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
  PRIMARY KEY  (`geonameid`),
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

DROP TABLE IF EXISTS `tbl_geo_region`;
CREATE TABLE IF NOT EXISTS `tbl_geo_region` (
  `regionID` int(11) NOT NULL auto_increment,
  `geo_region` varchar(255) NOT NULL default '',
  `geo_general` varchar(50) NOT NULL default '',
  PRIMARY KEY  (`regionID`),
  KEY `geographical_region` (`geo_region`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=14 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_herbaria`
--

DROP TABLE IF EXISTS `tbl_herbaria`;
CREATE TABLE IF NOT EXISTS `tbl_herbaria` (
  `herbariumID` int(11) NOT NULL auto_increment,
  `adressat` varchar(50) default NULL,
  `ihcode` varchar(255) default NULL,
  `Herbarium` varchar(255) default NULL,
  `Department` varchar(255) default NULL,
  `Institution` varchar(255) default NULL,
  `StreetAddress` varchar(255) default NULL,
  `City` varchar(255) default NULL,
  `Nation` varchar(255) default NULL,
  `Location` longtext,
  `Correspondent` longtext,
  `Phone` varchar(255) default NULL,
  `Fax` varchar(255) default NULL,
  `Url1` longtext,
  `Url2` longtext,
  `Updated` datetime default NULL,
  `Annotation1` longtext,
  `Annotation2` longtext,
  PRIMARY KEY  (`herbariumID`),
  UNIQUE KEY `ihcode` (`ihcode`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2141 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_herbaria_collectors`
--

DROP TABLE IF EXISTS `tbl_herbaria_collectors`;
CREATE TABLE IF NOT EXISTS `tbl_herbaria_collectors` (
  `person_IDfk` int(11) NOT NULL default '0',
  `herbarium_IDfk` int(11) NOT NULL default '0',
  `number_objects` double default NULL,
  `annotation` longtext,
  PRIMARY KEY  (`person_IDfk`,`herbarium_IDfk`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_img_definition`
--

DROP TABLE IF EXISTS `tbl_img_definition`;
CREATE TABLE IF NOT EXISTS `tbl_img_definition` (
  `img_def_ID` int(11) NOT NULL auto_increment,
  `source_id_fk` int(11) NOT NULL default '0',
  `img_coll_short` varchar(7) NOT NULL default '',
  `img_directory` varchar(255) NOT NULL default '',
  `img_obs_directory` varchar(255) NOT NULL default '',
  `img_tab_directory` varchar(255) default NULL,
  `imgserver_IP` varchar(15) NOT NULL default '',
  `HerbNummerNrDigits` tinyint(4) NOT NULL,
  `img_service_path` varchar(255) NOT NULL,
  `djatoka` tinyint(4) NOT NULL,
  PRIMARY KEY  (`img_def_ID`),
  UNIQUE KEY `source_id_fk` (`source_id_fk`),
  KEY `imgserver_IP` (`imgserver_IP`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=14 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_labels`
--

DROP TABLE IF EXISTS `tbl_labels`;
CREATE TABLE IF NOT EXISTS `tbl_labels` (
  `userID` int(11) NOT NULL default '0',
  `specimen_ID` int(11) NOT NULL default '0',
  `label` smallint(5) unsigned NOT NULL default '0',
  PRIMARY KEY  (`userID`,`specimen_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_labels_numbering`
--

DROP TABLE IF EXISTS `tbl_labels_numbering`;
CREATE TABLE IF NOT EXISTS `tbl_labels_numbering` (
  `labels_numbering_ID` int(11) NOT NULL auto_increment,
  `sourceID_fk` int(11) NOT NULL,
  `collectionID_fk` int(11) default NULL,
  `digits` int(11) NOT NULL,
  `replace_char` char(1) default NULL,
  PRIMARY KEY  (`labels_numbering_ID`),
  UNIQUE KEY `numbering` (`sourceID_fk`,`collectionID_fk`,`replace_char`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=8 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_languages`
--

DROP TABLE IF EXISTS `tbl_languages`;
CREATE TABLE IF NOT EXISTS `tbl_languages` (
  `languageID` int(11) NOT NULL auto_increment,
  `language_name` varchar(255) NOT NULL default '',
  `language_familyID_fk` int(11) default NULL,
  PRIMARY KEY  (`languageID`),
  KEY `language_name` (`language_name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=7 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_lit`
--

DROP TABLE IF EXISTS `tbl_lit`;
CREATE TABLE IF NOT EXISTS `tbl_lit` (
  `citationID` int(11) NOT NULL auto_increment,
  `lit_url` varchar(255) default NULL,
  `autorID` int(11) NOT NULL default '0',
  `jahr` varchar(50) default NULL,
  `code` varchar(25) default NULL,
  `titel` text,
  `suptitel` varchar(250) default NULL,
  `editorsID` int(11) default NULL,
  `periodicalID` int(11) default NULL,
  `vol` varchar(20) default NULL,
  `part` varchar(50) default NULL,
  `pp` varchar(150) default NULL,
  `ppSort` varchar(255) default NULL,
  `publisherID` int(11) default NULL,
  `verlagsort` varchar(100) default NULL,
  `keywords` varchar(100) default NULL,
  `annotation` longtext,
  `additions` longtext,
  `bestand` varchar(50) default NULL,
  `signature` varchar(50) default NULL,
  `publ` char(1) default NULL,
  `category` varchar(50) default NULL,
  `locked` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`citationID`),
  KEY `autorID` (`autorID`),
  KEY `category` (`category`),
  KEY `code` (`code`),
  KEY `editorsID` (`editorsID`),
  KEY `jahr` (`jahr`),
  KEY `periodicalID` (`periodicalID`),
  KEY `publisherID` (`publisherID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=16162 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_lit_authors`
--

DROP TABLE IF EXISTS `tbl_lit_authors`;
CREATE TABLE IF NOT EXISTS `tbl_lit_authors` (
  `autorID` int(11) NOT NULL auto_increment,
  `autor` varchar(150) NOT NULL default '',
  `autorsystbot` varchar(150) default NULL,
  `locked` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`autorID`),
  KEY `autor` (`autor`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5786 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_lit_container`
--

DROP TABLE IF EXISTS `tbl_lit_container`;
CREATE TABLE IF NOT EXISTS `tbl_lit_container` (
  `tbl_lit_containerID` int(11) NOT NULL auto_increment,
  `citation_parent_ID` int(11) NOT NULL,
  `citation_child_ID` int(11) NOT NULL,
  PRIMARY KEY  (`tbl_lit_containerID`),
  UNIQUE KEY `citation_parent_ID` (`citation_parent_ID`,`citation_child_ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=652 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_lit_libraries`
--

DROP TABLE IF EXISTS `tbl_lit_libraries`;
CREATE TABLE IF NOT EXISTS `tbl_lit_libraries` (
  `library_ID` int(11) NOT NULL auto_increment,
  `library` varchar(255) default NULL,
  PRIMARY KEY  (`library_ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=46 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_lit_lib_period`
--

DROP TABLE IF EXISTS `tbl_lit_lib_period`;
CREATE TABLE IF NOT EXISTS `tbl_lit_lib_period` (
  `lib_period_ID` int(11) NOT NULL auto_increment,
  `periodicalID` int(11) NOT NULL default '0',
  `library_ID` int(11) NOT NULL default '0',
  `signature` varchar(255) default NULL,
  `bestand` varchar(255) default NULL,
  `url` longtext,
  PRIMARY KEY  (`lib_period_ID`),
  KEY `library_ID` (`library_ID`),
  KEY `periodicalID` (`periodicalID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2210 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_lit_periodicals`
--

DROP TABLE IF EXISTS `tbl_lit_periodicals`;
CREATE TABLE IF NOT EXISTS `tbl_lit_periodicals` (
  `periodicalID` int(11) NOT NULL auto_increment,
  `periodical` varchar(250) default NULL COMMENT 'periodical or monograph abbreviated according to IPNI',
  `periodical_full` varchar(250) default NULL,
  `tl2_number` int(11) default NULL COMMENT 'Reference number for TL2',
  `bph_number` varchar(255) default NULL COMMENT 'Reference number for BPH',
  `ipni_ID` varchar(15) default NULL COMMENT 'Reference number for IPNI',
  `IPNI_version` varchar(25) default NULL COMMENT 'version of IPNI entry 200801',
  `successor_ID` int(11) default NULL,
  `predecessor_ID` int(11) default NULL,
  `locked` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`periodicalID`),
  KEY `periodical_full` (`periodical_full`),
  KEY `periodical` (`periodical`),
  KEY `successor_ID` (`successor_ID`,`predecessor_ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2920 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_lit_persons`
--

DROP TABLE IF EXISTS `tbl_lit_persons`;
CREATE TABLE IF NOT EXISTS `tbl_lit_persons` (
  `lit_persons_ID` int(11) NOT NULL auto_increment,
  `citationID_fk` int(11) NOT NULL,
  `personID_fk` int(11) NOT NULL,
  `annotations` longtext,
  `locked` tinyint(4) NOT NULL default '1',
  `userID` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`lit_persons_ID`),
  UNIQUE KEY `lit_person` (`citationID_fk`,`personID_fk`),
  KEY `userID` (`userID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=77 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_lit_publishers`
--

DROP TABLE IF EXISTS `tbl_lit_publishers`;
CREATE TABLE IF NOT EXISTS `tbl_lit_publishers` (
  `publisherID` int(11) NOT NULL auto_increment,
  `publisher` varchar(100) default NULL,
  `locked` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`publisherID`),
  KEY `publisher` (`publisher`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=701 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_lit_taxa`
--

DROP TABLE IF EXISTS `tbl_lit_taxa`;
CREATE TABLE IF NOT EXISTS `tbl_lit_taxa` (
  `lit_tax_ID` int(11) NOT NULL auto_increment,
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
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`lit_tax_ID`),
  UNIQUE KEY `revision` (`citationID`,`taxonID`,`acc_taxon_ID`,`source_citationID`,`source_person_ID`),
  KEY `uid` (`userID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=763 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_loans`
--

DROP TABLE IF EXISTS `tbl_loans`;
CREATE TABLE IF NOT EXISTS `tbl_loans` (
  `loan_id` int(11) NOT NULL auto_increment,
  `wu_reference` varchar(50) NOT NULL default 'WU-" & Format(Now(),"yy") & "-',
  `herbariumID` int(11) NOT NULL default '0',
  `foreign_reference` varchar(50) default NULL,
  `date_of_loan` datetime default NULL,
  `loantypeID` int(11) NOT NULL default '0',
  `number_of_sheets` int(11) default NULL,
  `received` datetime default NULL,
  `sent` datetime default NULL,
  `annotations` longtext,
  PRIMARY KEY  (`loan_id`),
  KEY `herbariumID` (`herbariumID`),
  KEY `wu_reference` (`wu_reference`),
  KEY `loantypeID` (`loantypeID`),
  KEY `received` (`received`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=970 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_loans_language`
--

DROP TABLE IF EXISTS `tbl_loans_language`;
CREATE TABLE IF NOT EXISTS `tbl_loans_language` (
  `languageID` int(11) NOT NULL auto_increment,
  `language` varchar(50) default NULL,
  PRIMARY KEY  (`languageID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_loans_specimens`
--

DROP TABLE IF EXISTS `tbl_loans_specimens`;
CREATE TABLE IF NOT EXISTS `tbl_loans_specimens` (
  `wu_reference` varchar(15) NOT NULL default '',
  `specimen_id` int(11) NOT NULL default '0',
  `transaction` varchar(50) default NULL,
  PRIMARY KEY  (`wu_reference`,`specimen_id`),
  KEY `specimen_id` (`specimen_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_loantype`
--

DROP TABLE IF EXISTS `tbl_loantype`;
CREATE TABLE IF NOT EXISTS `tbl_loantype` (
  `loantypeID` int(11) NOT NULL auto_increment,
  `loantype_english` varchar(50) default NULL,
  `loantype_espanol` varchar(50) default NULL,
  `loantype_deutsch` varchar(50) default NULL,
  PRIMARY KEY  (`loantypeID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=13 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_management_collections`
--

DROP TABLE IF EXISTS `tbl_management_collections`;
CREATE TABLE IF NOT EXISTS `tbl_management_collections` (
  `collectionID` int(11) NOT NULL auto_increment,
  `collection` varchar(50) default NULL,
  `coll_short` varchar(12) default NULL,
  `coll_short_prj` varchar(7) NOT NULL default '',
  `coll_gbif_pilot` varchar(255) NOT NULL default '',
  `coll_descr` longtext COMMENT 'description of the collection',
  `source_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`collectionID`),
  KEY `source_id` (`source_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=111 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_nom_common_names`
--

DROP TABLE IF EXISTS `tbl_nom_common_names`;
CREATE TABLE IF NOT EXISTS `tbl_nom_common_names` (
  `common_name_ID` int(11) NOT NULL auto_increment,
  `common_name` varchar(255) NOT NULL default '',
  `annotation` longtext,
  `locked` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`common_name_ID`),
  UNIQUE KEY `common_name` (`common_name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=62 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_nom_common_names_links`
--

DROP TABLE IF EXISTS `tbl_nom_common_names_links`;
CREATE TABLE IF NOT EXISTS `tbl_nom_common_names_links` (
  `common_name_links_ID` int(11) NOT NULL auto_increment,
  `common_name_ID_fk` int(11) NOT NULL,
  `taxonID_fk` int(11) NOT NULL default '0',
  `languageID_fk` int(11) NOT NULL default '0',
  `citationID_fk` int(11) default NULL COMMENT 'literature citing the common name',
  `annotation` longtext,
  `locked` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`common_name_links_ID`),
  UNIQUE KEY `common_names` (`common_name_ID_fk`,`taxonID_fk`,`languageID_fk`),
  KEY `citationID_fk` (`citationID_fk`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=60 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_nom_service`
--

DROP TABLE IF EXISTS `tbl_nom_service`;
CREATE TABLE IF NOT EXISTS `tbl_nom_service` (
  `serviceID` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  `url_head` varchar(255) NOT NULL,
  `url_middle` varchar(255) default NULL,
  `url_trail` varchar(255) default NULL,
  PRIMARY KEY  (`serviceID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=22 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_nom_service_names`
--

DROP TABLE IF EXISTS `tbl_nom_service_names`;
CREATE TABLE IF NOT EXISTS `tbl_nom_service_names` (
  `ID` int(11) NOT NULL auto_increment,
  `taxonID` int(11) NOT NULL,
  `serviceID` int(11) NOT NULL,
  `param1` varchar(255) NOT NULL default '0',
  `param2` varchar(255) default NULL,
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `taxon_service` (`taxonID`,`serviceID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5397 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_nom_status`
--

DROP TABLE IF EXISTS `tbl_nom_status`;
CREATE TABLE IF NOT EXISTS `tbl_nom_status` (
  `statusID` int(11) NOT NULL auto_increment,
  `status` varchar(50) default NULL,
  `status_description` varchar(50) default NULL,
  `status_sp2000` varchar(50) default NULL,
  PRIMARY KEY  (`statusID`),
  KEY `status` (`status`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=119 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_person`
--

DROP TABLE IF EXISTS `tbl_person`;
CREATE TABLE IF NOT EXISTS `tbl_person` (
  `person_ID` int(11) NOT NULL auto_increment,
  `collector_IDfk` int(11) default NULL,
  `litauthor_IDfk` int(11) default NULL,
  `taxauthor_IDfk` int(11) default NULL,
  `IPNIauthor_IDfk` varchar(50) default NULL,
  `IPNI_version` varchar(25) default NULL,
  `HUHbotanist_IDfk` varchar(50) default NULL,
  `p_abbrev` varchar(255) NOT NULL,
  `p_firstname` varchar(50) default NULL,
  `p_familyname` varchar(50) NOT NULL,
  `p_givenname` varchar(255) default NULL,
  `p_birthdate` varchar(11) default NULL,
  `p_birthplace` varchar(255) default NULL COMMENT 'place of birth',
  `p_death` varchar(11) default NULL,
  `p_deathplace` varchar(255) default NULL COMMENT 'place of death',
  `locked` tinyint(4) NOT NULL default '1',
  `p_annotation` longtext,
  `p_biography_short_eng` longtext,
  `p_biography_short_ger` longtext,
  PRIMARY KEY  (`person_ID`),
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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='complete persons list' AUTO_INCREMENT=90009 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_specimens`
--

DROP TABLE IF EXISTS `tbl_specimens`;
CREATE TABLE IF NOT EXISTS `tbl_specimens` (
  `specimen_ID` int(11) NOT NULL auto_increment,
  `HerbNummer` varchar(25) default NULL COMMENT 'hier muß ein wert drinnen stehen der als eindeutige Nummer innerhalb der Sammlung feststeht  ',
  `collectionID` int(11) NOT NULL default '0',
  `CollNummer` varchar(25) default NULL,
  `identstatusID` int(11) default NULL,
  `checked` tinyint(4) NOT NULL default '-1',
  `accessible` tinyint(4) NOT NULL default '-1',
  `taxonID` int(11) NOT NULL default '0',
  `SammlerID` int(11) NOT NULL default '0',
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
  `Fundort_engl` longtext,
  `habitat` longtext,
  `habitus` longtext,
  `Bemerkungen` longtext,
  `aktualdatum` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `eingabedatum` timestamp NOT NULL default '0000-00-00 00:00:00',
  `digital_image` tinyint(4) default NULL,
  `garten` varchar(50) default NULL,
  `voucherID` int(11) default NULL,
  `ncbi_accession` varchar(50) default NULL,
  `foreign_db_ID` text,
  `label` tinyint(4) NOT NULL default '0',
  `observation` tinyint(4) NOT NULL default '0' COMMENT 'assign status of dataset as "observation" vs. regular specimenspecimen',
  `digital_image_obs` tinyint(4) default NULL,
  `filename` varchar(255) default NULL,
  PRIMARY KEY  (`specimen_ID`),
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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=213578 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_specimens_identstatus`
--

DROP TABLE IF EXISTS `tbl_specimens_identstatus`;
CREATE TABLE IF NOT EXISTS `tbl_specimens_identstatus` (
  `identstatusID` int(11) NOT NULL auto_increment,
  `identification_status` varchar(255) NOT NULL default '""',
  PRIMARY KEY  (`identstatusID`),
  UNIQUE KEY `identification_status` (`identification_status`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=11 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_specimens_import`
--

DROP TABLE IF EXISTS `tbl_specimens_import`;
CREATE TABLE IF NOT EXISTS `tbl_specimens_import` (
  `specimen_ID` int(11) NOT NULL auto_increment,
  `HerbNummer` varchar(25) default NULL COMMENT 'hier muß ein wert drinnen stehen der als eindeutige Nummer innerhalb der Sammlung feststeht  ',
  `collectionID` int(11) NOT NULL default '0',
  `CollNummer` varchar(25) default NULL,
  `identstatusID` int(11) default NULL,
  `checked` tinyint(4) NOT NULL default '0',
  `accessible` tinyint(4) NOT NULL default '0',
  `taxonID` int(11) NOT NULL default '0',
  `SammlerID` int(11) NOT NULL default '0',
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
  `Fundort_engl` longtext,
  `habitat` longtext,
  `habitus` longtext,
  `Bemerkungen` longtext,
  `aktualdatum` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `eingabedatum` timestamp NOT NULL default '0000-00-00 00:00:00',
  `digital_image` tinyint(4) default NULL,
  `garten` varchar(50) default NULL,
  `voucherID` int(11) default NULL,
  `ncbi_accession` varchar(50) default NULL,
  `foreign_db_ID` text,
  `label` tinyint(4) NOT NULL default '0',
  `observation` tinyint(4) NOT NULL default '0' COMMENT 'assign status of dataset as "observation" vs. regular specimenspecimen',
  `digital_image_obs` tinyint(4) default NULL,
  `userID` int(11) NOT NULL default '0',
  PRIMARY KEY  (`specimen_ID`),
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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=12569 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_specimens_links`
--

DROP TABLE IF EXISTS `tbl_specimens_links`;
CREATE TABLE IF NOT EXISTS `tbl_specimens_links` (
  `specimens_linkID` int(11) NOT NULL auto_increment,
  `specimen1_ID` int(11) NOT NULL,
  `specimen2_ID` int(11) NOT NULL,
  PRIMARY KEY  (`specimens_linkID`),
  UNIQUE KEY `specimen1_ID` (`specimen1_ID`,`specimen2_ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4068 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_specimens_series`
--

DROP TABLE IF EXISTS `tbl_specimens_series`;
CREATE TABLE IF NOT EXISTS `tbl_specimens_series` (
  `seriesID` int(11) NOT NULL auto_increment,
  `series` varchar(255) NOT NULL default '""',
  `locked` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`seriesID`),
  UNIQUE KEY `series` (`series`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1429 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_specimens_types`
--

DROP TABLE IF EXISTS `tbl_specimens_types`;
CREATE TABLE IF NOT EXISTS `tbl_specimens_types` (
  `specimens_types_ID` int(11) NOT NULL auto_increment,
  `taxonID` int(11) NOT NULL default '0',
  `specimenID` int(11) NOT NULL default '0',
  `typusID` int(11) NOT NULL default '0',
  `typified_by_Person` varchar(255) NOT NULL default '',
  `typified_Date` varchar(10) NOT NULL default '',
  `annotations` longtext,
  `locked` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`specimens_types_ID`),
  UNIQUE KEY `typification` (`taxonID`,`specimenID`,`typusID`,`typified_by_Person`,`typified_Date`),
  KEY `specimenID` (`specimenID`),
  KEY `taxindID` (`taxonID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=52313 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_specimens_voucher`
--

DROP TABLE IF EXISTS `tbl_specimens_voucher`;
CREATE TABLE IF NOT EXISTS `tbl_specimens_voucher` (
  `voucherID` int(11) NOT NULL auto_increment,
  `voucher` varchar(255) NOT NULL default '""',
  PRIMARY KEY  (`voucherID`),
  UNIQUE KEY `voucher` (`voucher`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=12 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_tax_authors`
--

DROP TABLE IF EXISTS `tbl_tax_authors`;
CREATE TABLE IF NOT EXISTS `tbl_tax_authors` (
  `authorID` int(11) NOT NULL auto_increment,
  `author` varchar(255) NOT NULL default '',
  `Brummit_Powell_full` varchar(250) default NULL,
  `locked` tinyint(4) NOT NULL default '1',
  `external` tinyint(4) NOT NULL default '0',
  `externalID` int(11) default NULL,
  PRIMARY KEY  (`authorID`),
  KEY `Brummit_Powell_full` (`Brummit_Powell_full`),
  KEY `author` (`author`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=115682 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_tax_chorol_status`
--

DROP TABLE IF EXISTS `tbl_tax_chorol_status`;
CREATE TABLE IF NOT EXISTS `tbl_tax_chorol_status` (
  `tax_chorol_status_ID` int(11) NOT NULL auto_increment,
  `taxonID_fk` int(11) NOT NULL,
  `citationID_fk` int(11) default NULL,
  `personID_fk` int(11) default NULL,
  `serviceID_fk` tinyint(4) default NULL,
  `chorol_status` varchar(255) character set utf8 collate utf8_unicode_ci default NULL,
  `status_debatable` tinyint(4) NOT NULL default '0',
  `NationID_fk` int(11) NOT NULL,
  `provinceID_fk` int(11) default NULL,
  `province_debatable` tinyint(4) NOT NULL default '0',
  `dateLastEdited` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `locked` int(11) NOT NULL,
  PRIMARY KEY  (`tax_chorol_status_ID`),
  KEY `taxonID_fk` (`taxonID_fk`,`citationID_fk`,`personID_fk`,`serviceID_fk`,`chorol_status`,`locked`),
  KEY `NationID_fk` (`NationID_fk`,`provinceID_fk`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=166 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_tax_epithets`
--

DROP TABLE IF EXISTS `tbl_tax_epithets`;
CREATE TABLE IF NOT EXISTS `tbl_tax_epithets` (
  `epithet` varchar(50) NOT NULL default '',
  `epithetID` int(11) NOT NULL auto_increment,
  `locked` tinyint(4) NOT NULL default '1',
  `external` tinyint(4) NOT NULL default '0',
  `externalID` int(11) default NULL,
  PRIMARY KEY  (`epithetID`),
  UNIQUE KEY `epithet` (`epithet`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=134660 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_tax_families`
--

DROP TABLE IF EXISTS `tbl_tax_families`;
CREATE TABLE IF NOT EXISTS `tbl_tax_families` (
  `familyID` int(11) NOT NULL auto_increment,
  `family` varchar(50) NOT NULL default '',
  `authorID` int(11) NOT NULL default '0',
  `categoryID` int(11) NOT NULL default '0',
  `family_alt` varchar(100) default NULL,
  `locked` tinyint(4) NOT NULL default '1',
  `external` tinyint(4) NOT NULL default '0',
  `externalID` int(11) default NULL,
  PRIMARY KEY  (`familyID`),
  KEY `categoryID` (`categoryID`),
  KEY `family` (`family`),
  KEY `authorID` (`authorID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1208 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_tax_genera`
--

DROP TABLE IF EXISTS `tbl_tax_genera`;
CREATE TABLE IF NOT EXISTS `tbl_tax_genera` (
  `genID` int(11) NOT NULL auto_increment,
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
  `fk_taxonID` int(11) default NULL,
  `locked` tinyint(4) NOT NULL default '1',
  `external` tinyint(4) NOT NULL default '0',
  `externalID` int(11) default NULL,
  PRIMARY KEY  (`genID`),
  KEY `familyID` (`familyID`),
  KEY `authorID` (`authorID`),
  KEY `DallaTorreIDs` (`DallaTorreIDs`),
  KEY `hybrid` (`hybrid`),
  KEY `genus` (`genus`),
  KEY `fk_taxonID` (`fk_taxonID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=30350 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_tax_hybrids`
--

DROP TABLE IF EXISTS `tbl_tax_hybrids`;
CREATE TABLE IF NOT EXISTS `tbl_tax_hybrids` (
  `hybrid_ID` int(11) NOT NULL auto_increment,
  `taxon_ID_fk` int(11) NOT NULL default '0',
  `parent_1_ID` int(11) NOT NULL default '0',
  `parent_2_ID` int(11) NOT NULL default '0',
  `parent_3_ID` int(11) NOT NULL,
  PRIMARY KEY  (`hybrid_ID`),
  UNIQUE KEY `taxon_ID_fk` (`taxon_ID_fk`),
  UNIQUE KEY `parent_ID` (`parent_1_ID`,`parent_2_ID`,`parent_3_ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=619 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_tax_index`
--

DROP TABLE IF EXISTS `tbl_tax_index`;
CREATE TABLE IF NOT EXISTS `tbl_tax_index` (
  `taxindID` int(11) NOT NULL auto_increment,
  `taxonID` int(11) NOT NULL default '0',
  `citationID` int(11) NOT NULL default '0',
  `paginae` varchar(50) default NULL,
  `date_paginae` varchar(10) default NULL,
  `figures` varchar(255) default NULL,
  `date_figures` varchar(10) default NULL,
  `annotations` longtext,
  `locked` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`taxonID`,`citationID`),
  KEY `citationID` (`citationID`),
  KEY `taxindID` (`taxindID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=50363 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_tax_orders`
--

DROP TABLE IF EXISTS `tbl_tax_orders`;
CREATE TABLE IF NOT EXISTS `tbl_tax_orders` (
  `orderID` int(11) NOT NULL auto_increment,
  `order` varchar(50) NOT NULL default '',
  `authorID` int(11) default NULL,
  `categoryID` int(11) NOT NULL default '0',
  `locked` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`orderID`),
  KEY `categoryID` (`categoryID`),
  KEY `family` (`order`),
  KEY `authorID` (`authorID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_tax_rank`
--

DROP TABLE IF EXISTS `tbl_tax_rank`;
CREATE TABLE IF NOT EXISTS `tbl_tax_rank` (
  `tax_rankID` int(11) NOT NULL auto_increment,
  `rank` varchar(255) NOT NULL default '',
  `rank_latin` varchar(255) default NULL,
  `bot_rank_suffix` varchar(50) default NULL COMMENT 'botanical naming conventions',
  `zoo_rank_suffix` varchar(50) default NULL COMMENT 'zoological naming conventions',
  `rank_hierarchy` int(11) NOT NULL default '0',
  `rank_abbr` varchar(10) default NULL,
  PRIMARY KEY  (`tax_rankID`),
  UNIQUE KEY `zoo_rank_ending` (`zoo_rank_suffix`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=29 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_tax_relationships`
--

DROP TABLE IF EXISTS `tbl_tax_relationships`;
CREATE TABLE IF NOT EXISTS `tbl_tax_relationships` (
  `tax_relation_ID` int(11) NOT NULL auto_increment,
  `relation_term` varchar(50) NOT NULL,
  `explanation` text,
  `locked` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`tax_relation_ID`),
  UNIQUE KEY `relation_term` (`relation_term`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='lookup table for assining taxonomic relationship between two' AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_tax_scrutiny`
--

DROP TABLE IF EXISTS `tbl_tax_scrutiny`;
CREATE TABLE IF NOT EXISTS `tbl_tax_scrutiny` (
  `scrutiny_ID` int(11) NOT NULL auto_increment,
  `taxonID` int(11) NOT NULL default '0',
  `citationID` int(11) NOT NULL default '0',
  `scrutiny_person_ID` int(11) NOT NULL default '0',
  `date` varchar(25) default NULL,
  `annotation` longtext NOT NULL,
  PRIMARY KEY  (`scrutiny_ID`),
  KEY `taxonID` (`taxonID`),
  KEY `citationID` (`citationID`),
  KEY `scrutiny_person_ID` (`scrutiny_person_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_tax_species`
--

DROP TABLE IF EXISTS `tbl_tax_species`;
CREATE TABLE IF NOT EXISTS `tbl_tax_species` (
  `tax_rankID` int(11) NOT NULL default '1',
  `basID` int(11) default NULL,
  `taxonID` int(11) NOT NULL auto_increment,
  `synID` int(11) default NULL,
  `statusID` int(11) NOT NULL default '96',
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
  `IPNItax_IDfk` varchar(50) default NULL COMMENT 'foreign key to IPNI nameslist',
  `IPNI_version` varchar(25) default NULL,
  `API_taxID_fk` varchar(50) default NULL COMMENT 'foreign key to API nameslist',
  `tropicos_taxID_fk` varchar(50) default NULL COMMENT 'foreign key to TROPICOS nameslist',
  `linn_taxID_fk` varchar(50) default NULL COMMENT 'foreign key to linnean nameslist',
  `locked` tinyint(4) NOT NULL default '1',
  `external` tinyint(4) NOT NULL default '0',
  `externalID` int(11) default NULL,
  PRIMARY KEY  (`taxonID`),
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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=198558 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_tax_status`
--

DROP TABLE IF EXISTS `tbl_tax_status`;
CREATE TABLE IF NOT EXISTS `tbl_tax_status` (
  `statusID` int(11) NOT NULL auto_increment,
  `status` varchar(50) default NULL,
  `status_description` varchar(50) default NULL,
  `status_sp2000` varchar(50) default NULL,
  PRIMARY KEY  (`statusID`),
  KEY `status` (`status`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=122 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_tax_synonymy`
--

DROP TABLE IF EXISTS `tbl_tax_synonymy`;
CREATE TABLE IF NOT EXISTS `tbl_tax_synonymy` (
  `tax_syn_ID` int(11) NOT NULL auto_increment,
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
  PRIMARY KEY  (`tax_syn_ID`),
  UNIQUE KEY `unique_syn_tax_cit` (`taxonID`,`acc_taxon_ID`,`source_citationID`,`source_person_ID`),
  KEY `taxonID` (`taxonID`),
  KEY `acc_taxon_ID` (`acc_taxon_ID`),
  KEY `locked` (`locked`),
  KEY `userID` (`userID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=12436 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_tax_systematic_categories`
--

DROP TABLE IF EXISTS `tbl_tax_systematic_categories`;
CREATE TABLE IF NOT EXISTS `tbl_tax_systematic_categories` (
  `categoryID` int(11) NOT NULL default '0',
  `category` varchar(2) NOT NULL default '',
  `cat_description` varchar(50) default NULL,
  PRIMARY KEY  (`categoryID`),
  KEY `category` (`category`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_tax_typecollections`
--

DROP TABLE IF EXISTS `tbl_tax_typecollections`;
CREATE TABLE IF NOT EXISTS `tbl_tax_typecollections` (
  `typecollID` int(11) NOT NULL auto_increment,
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
  `locked` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`typecollID`),
  KEY `Sammler_2ID` (`Sammler_2ID`),
  KEY `SammlerID` (`SammlerID`),
  KEY `taxonID` (`taxonID`),
  KEY `typusID` (`typusID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=14568 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_typi`
--

DROP TABLE IF EXISTS `tbl_typi`;
CREATE TABLE IF NOT EXISTS `tbl_typi` (
  `typusID` int(11) NOT NULL auto_increment,
  `typus` varchar(10) NOT NULL default '',
  `typus_lat` varchar(255) NOT NULL default '',
  `typus_description` varchar(255) default NULL,
  `typus_engl` varchar(255) NOT NULL default '',
  `typus_api_standard` varchar(255) NOT NULL default '',
  `typus_icbn` varchar(255) default NULL,
  PRIMARY KEY  (`typusID`),
  KEY `typus` (`typus`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=35 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tmp_scrutiny_import`
--

DROP TABLE IF EXISTS `tmp_scrutiny_import`;
CREATE TABLE IF NOT EXISTS `tmp_scrutiny_import` (
  `taxonID` int(5) default NULL,
  `scrutiny` varchar(66) default NULL,
  `author` varchar(255) NOT NULL,
  `date` varchar(255) NOT NULL,
  UNIQUE KEY `taxonID` (`taxonID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Stellvertreter-Struktur des Views `view_acceptedinfraspecifictaxa`
--
DROP VIEW IF EXISTS `view_acceptedinfraspecifictaxa`;
CREATE TABLE IF NOT EXISTS `view_acceptedinfraspecifictaxa` (
`AcceptedTaxonID` int(11)
,`ParentSpeciesID` int(11)
,`InfraSpeciesEpithet` varchar(50)
,`InfraSpecificAuthorString` varchar(255)
,`InfraSpecificMarker` varchar(10)
,`GSDNameStatus` char(0)
,`Sp2000NameStatus` varchar(50)
,`IsFossil` varchar(2)
,`LifeZone` varchar(10)
,`AdditionalData` char(0)
,`LTSSpecialist` varchar(13)
,`LTSDate` varchar(7)
,`InfraSpeciesURL` varbinary(80)
,`GSDTaxonGUI` varchar(11)
,`GSDNameGUI` varchar(10)
);
-- --------------------------------------------------------

--
-- Stellvertreter-Struktur des Views `view_acceptedspecies`
--
DROP VIEW IF EXISTS `view_acceptedspecies`;
CREATE TABLE IF NOT EXISTS `view_acceptedspecies` (
`AcceptedTaxonID` int(11)
,`Kingdom` varchar(7)
,`Phylum` varchar(13)
,`Class` varchar(13)
,`Order` varchar(11)
,`Superfamily` char(0)
,`Family` varchar(50)
,`Genus` varchar(100)
,`SubGenusName` char(0)
,`Species` varchar(50)
,`AuthorString` varchar(307)
,`GSDNameStatus` char(0)
,`Sp2000NameStatus` varchar(50)
,`IsFossil` varchar(2)
,`LifeZone` varchar(10)
,`AdditionalData` char(0)
,`LTSSpecialist` varchar(13)
,`LTSDate` varchar(7)
,`SpeciesURL` varbinary(80)
,`GSDTaxonGUI` varchar(11)
,`GSDNameGUI` varchar(10)
);
-- --------------------------------------------------------

--
-- Stellvertreter-Struktur des Views `view_sourcedatabase`
--
DROP VIEW IF EXISTS `view_sourcedatabase`;
CREATE TABLE IF NOT EXISTS `view_sourcedatabase` (
`DatabaseFullName` char(250)
,`DatabaseShortName` char(250)
,`DatabaseVersion` char(250)
,`ReleaseDate` datetime
,`AuthorsEditors` varchar(250)
,`TaxonomicCoverage` varchar(17)
,`GroupNameInEnglish` varchar(255)
,`Abstract` text
,`Organisation` varchar(250)
,`HomeURL` varchar(250)
,`Coverage` char(0)
,`Completeness` char(0)
,`Confidence` text
,`LogoFileName` varchar(250)
,`ContactPerson` varchar(250)
);
-- --------------------------------------------------------

--
-- Struktur des Views `view_acceptedinfraspecifictaxa`
--
DROP TABLE IF EXISTS `view_acceptedinfraspecifictaxa`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_acceptedinfraspecifictaxa` AS select `ts`.`taxonID` AS `AcceptedTaxonID`,`acc`.`AcceptedTaxonID` AS `ParentSpeciesID`,(case `ts`.`tax_rankID` when 2 then `te1`.`epithet` when 3 then `te2`.`epithet` when 4 then `te3`.`epithet` when 5 then `te4`.`epithet` else `te5`.`epithet` end) AS `InfraSpeciesEpithet`,(case `ts`.`tax_rankID` when 2 then `ta1`.`author` when 3 then `ta2`.`author` when 4 then `ta3`.`author` when 5 then `ta4`.`author` else `ta5`.`author` end) AS `InfraSpecificAuthorString`,`ttr`.`rank_abbr` AS `InfraSpecificMarker`,_utf8'' AS `GSDNameStatus`,`tts`.`status_sp2000` AS `Sp2000NameStatus`,_utf8'No' AS `IsFossil`,_utf8'terrestial' AS `LifeZone`,_utf8'' AS `AdditionalData`,_utf8'LTSSpecialist' AS `LTSSpecialist`,_utf8'LTSDate' AS `LTSDate`,concat(_utf8'http://herbarium.botanik.univie.ac.at/annonaceae/listSynonyms.php?ID=',`ts`.`taxonID`) AS `InfraSpeciesURL`,_utf8'GSDTaxonGUI' AS `GSDTaxonGUI`,_utf8'GSDNameGUI' AS `GSDNameGUI` from ((((((((((((((`view_acceptedspecies` `acc` left join `tbl_tax_species` `tso` on((`tso`.`taxonID` = `acc`.`AcceptedTaxonID`))) left join `tbl_tax_species` `ts` on(((`ts`.`genID` = `tso`.`genID`) and (`ts`.`speciesID` = `tso`.`speciesID`)))) left join `tbl_tax_rank` `ttr` on((`ttr`.`tax_rankID` = `ts`.`tax_rankID`))) left join `tbl_tax_status` `tts` on((`tts`.`statusID` = `ts`.`statusID`))) left join `tbl_tax_authors` `ta1` on((`ta1`.`authorID` = `ts`.`subspecies_authorID`))) left join `tbl_tax_authors` `ta2` on((`ta2`.`authorID` = `ts`.`variety_authorID`))) left join `tbl_tax_authors` `ta3` on((`ta3`.`authorID` = `ts`.`subvariety_authorID`))) left join `tbl_tax_authors` `ta4` on((`ta4`.`authorID` = `ts`.`forma_authorID`))) left join `tbl_tax_authors` `ta5` on((`ta5`.`authorID` = `ts`.`subforma_authorID`))) left join `tbl_tax_epithets` `te1` on((`te1`.`epithetID` = `ts`.`subspeciesID`))) left join `tbl_tax_epithets` `te2` on((`te2`.`epithetID` = `ts`.`varietyID`))) left join `tbl_tax_epithets` `te3` on((`te3`.`epithetID` = `ts`.`subvarietyID`))) left join `tbl_tax_epithets` `te4` on((`te4`.`epithetID` = `ts`.`formaID`))) left join `tbl_tax_epithets` `te5` on((`te5`.`epithetID` = `ts`.`subformaID`))) where ((`ts`.`statusID` in (96,93,97,103)) and (`ts`.`tax_rankID` in (2,3,4,5,6)));

-- --------------------------------------------------------

--
-- Struktur des Views `view_acceptedspecies`
--
DROP TABLE IF EXISTS `view_acceptedspecies`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_acceptedspecies` AS select `ts`.`taxonID` AS `AcceptedTaxonID`,_utf8'Plantae' AS `Kingdom`,_utf8'Magnoliophyta' AS `Phylum`,_utf8'Magnoliopsida' AS `Class`,_utf8'Magnoliales' AS `Order`,_utf8'' AS `Superfamily`,`tf`.`family` AS `Family`,`tg`.`genus` AS `Genus`,_utf8'' AS `SubGenusName`,`te`.`epithet` AS `Species`,concat(if(`te`.`epithetID`,concat(_utf8' ',`te`.`epithet`,_utf8' ',`ta`.`author`),_utf8'')) AS `AuthorString`,_utf8'' AS `GSDNameStatus`,`tts`.`status_sp2000` AS `Sp2000NameStatus`,_utf8'No' AS `IsFossil`,_utf8'terrestial' AS `LifeZone`,_utf8'' AS `AdditionalData`,_utf8'LTSSpecialist' AS `LTSSpecialist`,_utf8'LTSDate' AS `LTSDate`,concat(_utf8'http://herbarium.botanik.univie.ac.at/annonaceae/listSynonyms.php?ID=',`ts`.`taxonID`) AS `SpeciesURL`,_utf8'GSDTaxonGUI' AS `GSDTaxonGUI`,_utf8'GSDNameGUI' AS `GSDNameGUI` from ((((((`tbl_tax_species` `ts` left join `tbl_tax_rank` `ttr` on((`ttr`.`tax_rankID` = `ts`.`tax_rankID`))) left join `tbl_tax_status` `tts` on((`tts`.`statusID` = `ts`.`statusID`))) left join `tbl_tax_genera` `tg` on((`tg`.`genID` = `ts`.`genID`))) left join `tbl_tax_families` `tf` on((`tf`.`familyID` = `tg`.`familyID`))) left join `tbl_tax_authors` `ta` on((`ta`.`authorID` = `ts`.`authorID`))) left join `tbl_tax_epithets` `te` on((`te`.`epithetID` = `ts`.`speciesID`))) where ((`tg`.`familyID` = _utf8'30') and (`ts`.`statusID` in (96,93,97,103)) and ((`ts`.`tax_rankID` = _utf8'1') or ((`ts`.`tax_rankID` = _utf8'7') and isnull(`ts`.`speciesID`))));

-- --------------------------------------------------------

--
-- Struktur des Views `view_sourcedatabase`
--
DROP TABLE IF EXISTS `view_sourcedatabase`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_sourcedatabase` AS select `m`.`source_name` AS `DatabaseFullName`,`m`.`source_code` AS `DatabaseShortName`,`m`.`source_version` AS `DatabaseVersion`,`m`.`source_update` AS `ReleaseDate`,`mdb`.`supplier_person` AS `AuthorsEditors`,_utf8'TaxonomicCoverage' AS `TaxonomicCoverage`,`m`.`source_abbr_engl` AS `GroupNameInEnglish`,`mdb`.`description` AS `Abstract`,`mdb`.`supplier_organisation` AS `Organisation`,`mdb`.`supplier_url` AS `HomeURL`,_utf8'' AS `Coverage`,_utf8'' AS `Completeness`,`mdb`.`disclaimer` AS `Confidence`,`mdb`.`logo_url` AS `LogoFileName`,`mdb`.`supplier_person` AS `ContactPerson` from (`meta` `m` left join `metadb` `mdb` on((`mdb`.`source_id_fk` = `m`.`source_id`)));
