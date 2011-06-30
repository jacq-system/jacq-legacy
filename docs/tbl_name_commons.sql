-- phpMyAdmin SQL Dump
-- version 3.4.2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 29. Jun 2011 um 14:17
-- Server Version: 5.1.45
-- PHP-Version: 5.2.8

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Datenbank: `herbar_names`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tbl_name_commons`
--

CREATE TABLE IF NOT EXISTS `tbl_name_commons` (
  `common_id` int(11) NOT NULL,
  `common_name` varchar(255) NOT NULL,
  PRIMARY KEY (`common_id`),
  UNIQUE KEY `common_name_UNIQUE` (`common_name`),
  KEY `fk_tbl_names_common_tbl_names_name` (`common_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `tbl_name_commons`
--
ALTER TABLE `tbl_name_commons`
  ADD CONSTRAINT `fk_tbl_names_common_tbl_names_name` FOREIGN KEY (`common_id`) REFERENCES `tbl_name_names` (`name_id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
