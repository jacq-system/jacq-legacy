CREATE TABLE IF NOT EXISTS `tbl_person_alternative` (
  `person_alternative_ID` int(11) NOT NULL AUTO_INCREMENT,
  `person_ID` int(11) NOT NULL,
  `p_alternative` varchar(255) NOT NULL,
  PRIMARY KEY (`person_alternative_ID`),
  UNIQUE KEY `person_ID` (`person_ID`,`p_alternative`),
  KEY `p_alternative` (`p_alternative`),
  KEY `person_ID_2` (`person_ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

-- 12.8.2011

ALTER TABLE `tbl_tax_synonymy`
 ADD `ref_date` DATE NULL DEFAULT NULL AFTER `acc_taxon_ID`,
 ADD `source_specimenID` INT( 11 ) NULL DEFAULT NULL AFTER `source_serviceID`


ALTER TABLE `tbl_herbardb_groups`
 ADD `commonnameUpdate` INT( 4 ) NOT NULL ,
 ADD `commonnameInsert` INT( 4 ) NOT NULL

-- 4.8.2011

CREATE TABLE IF NOT EXISTS `log_commonnames_tbl_name_applies_to` (
  `logID` int(11) NOT NULL auto_increment,
  `geonameId` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `period_id` int(11) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `reference_id` int(11) NOT NULL,
  `name_id` int(11) NOT NULL,
  `oldid` varchar(100) NOT NULL,
  `geospecification` text NOT NULL,
  `annotation` text,
  `locked` tinyint(4) default NULL,
  `userID` int(11) NOT NULL,
  `updated` int(4) NOT NULL,
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`logID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=178 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_commonnames_tbl_name_commons`
--

CREATE TABLE IF NOT EXISTS `log_commonnames_tbl_name_commons` (
  `log_common_id` int(11) NOT NULL auto_increment,
  `common_id` int(11) NOT NULL,
  `common_name` varchar(255) NOT NULL,
  `locked` tinyint(4) NOT NULL,
  `userID` int(11) NOT NULL,
  `updated` int(4) NOT NULL,
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`log_common_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=33 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `log_commonnames_tbl_name_languages`
--

CREATE TABLE IF NOT EXISTS `log_commonnames_tbl_name_languages` (
  `language_id` int(11) NOT NULL,
  `iso639-6` varchar(4) NOT NULL,
  `parent_iso639-6` varchar(4) default NULL,
  `name` varchar(50) default NULL,
  `userID` int(11) NOT NULL,
  `updated` int(4) NOT NULL,
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

