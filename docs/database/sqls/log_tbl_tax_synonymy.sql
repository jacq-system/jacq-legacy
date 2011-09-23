-- phpMyAdmin SQL Dump
-- version 3.3.9
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 04. September 2011 um 22:16
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
-- Tabellenstruktur für Tabelle `log_tbl_tax_synonymy`
--

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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=12386 ;
