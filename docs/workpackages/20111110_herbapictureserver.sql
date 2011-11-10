CREATE TABLE IF NOT EXISTS `djatoka_files` (
  `ID` int(11) NOT NULL auto_increment,
  `scan_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `inconsistency` tinyint(4) default NULL,
  PRIMARY KEY  (`ID`),
  KEY `file` (`filename`),
  KEY `source_id` (`scan_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0 ;


CREATE TABLE IF NOT EXISTS `djatoka_scans` (
  `scan_id` int(11) NOT NULL auto_increment,
  `thread_id` int(11) default NULL,
  `IP` varchar(40) NOT NULL,
  `start` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `finish` timestamp NULL default NULL,
  `errors` text character set utf8 collate utf8_unicode_ci,
  PRIMARY KEY  (`scan_id`),
  KEY `start` (`start`),
  KEY `finish` (`finish`),
  KEY `IP` (`IP`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0 ;

ALTER TABLE `tbl_img_definition` ADD `key` VARCHAR( 50 ) NULL ;