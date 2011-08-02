ALTER TABLE `tbl_tax_synonymy`
 ADD `ref_date` DATE NULL DEFAULT NULL AFTER `acc_taxon_ID`,
 ADD `source_specimenID` INT( 11 ) NULL DEFAULT NULL AFTER `source_serviceID`
 
 
ALTER TABLE `tbl_herbardb_groups`
 ADD `commonnameUpdate` INT( 4 ) NOT NULL ,
 ADD `commonnameInsert` INT( 4 ) NOT NULL

-- 2.8.2011

ALTER TABLE `log_commonnames_tbl_name_applies_to`
ADD `oldid` VARCHAR( 100 ) NOT NULL AFTER `name_id` ,
ADD `annotation` TEXT NULL AFTER `oldid` ,
ADD `locked` TINYINT NULL AFTER `annotation`
ADD `geospecification` TEXT NOT NULL AFTER `oldid`
 