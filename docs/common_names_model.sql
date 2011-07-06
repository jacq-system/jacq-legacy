-- phpMyAdmin SQL Dump
-- version 3.3.9
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 06. Juli 2011 um 15:13
-- Server Version: 5.0.41
-- PHP-Version: 5.3.4

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Datenbank: `names`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_geonames_cache`
--

CREATE TABLE IF NOT EXISTS `tbl_geonames_cache` (
  `geonameId` int(11) NOT NULL,
  `name` text,
  PRIMARY KEY  (`geonameId`),
  UNIQUE KEY `geonameId_UNIQUE` (`geonameId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_language_cache`
--

CREATE TABLE IF NOT EXISTS `tbl_language_cache` (
  `iso639_6` varchar(4) NOT NULL,
  `parent_id` varchar(4) default NULL,
  `name` varchar(50) default NULL,
  UNIQUE KEY `iso639-6_UNIQUE` (`iso639_6`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_name_applies_to`
--

CREATE TABLE IF NOT EXISTS `tbl_name_applies_to` (
  `geonameId` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `period_id` int(11) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `reference_id` int(11) NOT NULL,
  `name_id` int(11) NOT NULL,
  UNIQUE KEY `tbl_name_applies_to_UNIQUE` (`language_id`,`period_id`,`entity_id`,`reference_id`,`name_id`,`geonameId`),
  KEY `fk_tbl_name_appliesTo_tbl_name_languages1` (`language_id`),
  KEY `fk_tbl_name_appliesTo_tbl_name_periods1` (`period_id`),
  KEY `fk_tbl_name_appliesTo_tbl_name_entities1` (`entity_id`),
  KEY `fk_tbl_name_appliesTo_tbl_name_references1` (`reference_id`),
  KEY `fk_tbl_name_appliesTo_tbl_name_names1` (`name_id`),
  KEY `fk_tbl_name_applies_to_tbl_geonames_cache1` (`geonameId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_name_commons`
--

CREATE TABLE IF NOT EXISTS `tbl_name_commons` (
  `common_id` int(11) NOT NULL,
  `common_name` varchar(255) NOT NULL,
  PRIMARY KEY  (`common_id`),
  UNIQUE KEY `common_name_UNIQUE` (`common_name`),
  KEY `fk_tbl_names_common_tbl_names_name` (`common_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_name_entities`
--

CREATE TABLE IF NOT EXISTS `tbl_name_entities` (
  `entity_id` int(11) NOT NULL auto_increment,
  PRIMARY KEY  (`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_name_languages`
--

CREATE TABLE IF NOT EXISTS `tbl_name_languages` (
  `language_id` int(11) NOT NULL auto_increment,
  `iso639_6` varchar(4) NOT NULL,
  `namecache` varchar(50) NOT NULL,
  PRIMARY KEY  (`language_id`),
  UNIQUE KEY `iso639-6_UNIQUE` (`iso639_6`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_name_literature`
--

CREATE TABLE IF NOT EXISTS `tbl_name_literature` (
  `literature_id` int(11) NOT NULL,
  `citationID` int(11) NOT NULL,
  PRIMARY KEY  (`literature_id`),
  UNIQUE KEY `citationID_UNIQUE` (`citationID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_name_names`
--

CREATE TABLE IF NOT EXISTS `tbl_name_names` (
  `name_id` int(11) NOT NULL auto_increment,
  PRIMARY KEY  (`name_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_name_periods`
--

CREATE TABLE IF NOT EXISTS `tbl_name_periods` (
  `period_id` int(11) NOT NULL auto_increment,
  `period` varchar(255) NOT NULL,
  PRIMARY KEY  (`period_id`),
  UNIQUE KEY `period_UNIQUE` (`period`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_name_references`
--

CREATE TABLE IF NOT EXISTS `tbl_name_references` (
  `reference_id` int(11) NOT NULL auto_increment,
  PRIMARY KEY  (`reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_name_taxon`
--

CREATE TABLE IF NOT EXISTS `tbl_name_taxon` (
  `taxon_id` int(11) NOT NULL,
  `taxonID` int(11) NOT NULL,
  PRIMARY KEY  (`taxon_id`),
  UNIQUE KEY `taxonID_UNIQUE` (`taxonID`),
  KEY `fk_tbl_name_taxon_tbl_name_entities1` (`taxon_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_search_cache`
--

CREATE TABLE IF NOT EXISTS `tbl_search_cache` (
  `search_val` varchar(20) NOT NULL,
  `search_group` int(2) NOT NULL,
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `result` text,
  PRIMARY KEY  (`search_val`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `tbl_name_applies_to`
--
ALTER TABLE `tbl_name_applies_to`
  ADD CONSTRAINT `fk_tbl_name_appliesTo_tbl_name_languages1` FOREIGN KEY (`language_id`) REFERENCES `tbl_name_languages` (`language_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_tbl_name_appliesTo_tbl_name_periods1` FOREIGN KEY (`period_id`) REFERENCES `tbl_name_periods` (`period_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_tbl_name_appliesTo_tbl_name_entities1` FOREIGN KEY (`entity_id`) REFERENCES `tbl_name_entities` (`entity_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_tbl_name_appliesTo_tbl_name_references1` FOREIGN KEY (`reference_id`) REFERENCES `tbl_name_references` (`reference_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_tbl_name_appliesTo_tbl_name_names1` FOREIGN KEY (`name_id`) REFERENCES `tbl_name_names` (`name_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_tbl_name_applies_to_tbl_geonames_cache1` FOREIGN KEY (`geonameId`) REFERENCES `tbl_geonames_cache` (`geonameId`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints der Tabelle `tbl_name_commons`
--
ALTER TABLE `tbl_name_commons`
  ADD CONSTRAINT `fk_tbl_names_common_tbl_names_name` FOREIGN KEY (`common_id`) REFERENCES `tbl_name_names` (`name_id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints der Tabelle `tbl_name_literature`
--
ALTER TABLE `tbl_name_literature`
  ADD CONSTRAINT `fk_tbl_name_literature_tbl_name_references1` FOREIGN KEY (`literature_id`) REFERENCES `tbl_name_references` (`reference_id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints der Tabelle `tbl_name_taxon`
--
ALTER TABLE `tbl_name_taxon`
  ADD CONSTRAINT `fk_tbl_name_taxon_tbl_name_entities1` FOREIGN KEY (`taxon_id`) REFERENCES `tbl_name_entities` (`entity_id`) ON DELETE NO ACTION ON UPDATE NO ACTION;
