-- phpMyAdmin SQL Dump
-- version 3.3.9
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 19. September 2011 um 14:13
-- Server Version: 5.0.41
-- PHP-Version: 5.3.4

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Datenbank: `herbardb`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_img_definition`
--

CREATE TABLE IF NOT EXISTS `tbl_img_definition` (
  `img_def_ID` int(11) NOT NULL auto_increment,
  `source_id_fk` int(11) NOT NULL default '0',
  `img_coll_short` varchar(7) NOT NULL default '',
  `img_directory` varchar(255) NOT NULL default '',
  `img_obs_directory` varchar(255) NOT NULL default '',
  `img_tab_directory` varchar(255) default NULL,
  `imgserver_IP` varchar(15) NOT NULL default '',
  `HerbNummerNrDigits` tinyint(4) NOT NULL,
  `img_service_path` varchar(50) NOT NULL,
  `djatoka` tinyint(4) NOT NULL,
  PRIMARY KEY  (`img_def_ID`),
  UNIQUE KEY `source_id_fk` (`source_id_fk`),
  KEY `imgserver_IP` (`imgserver_IP`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=14 ;
