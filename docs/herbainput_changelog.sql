ALTER TABLE `tbl_tax_synonymy`
 ADD `ref_date` DATE NULL DEFAULT NULL AFTER `acc_taxon_ID`,
 ADD `source_specimenID` INT( 11 ) NULL DEFAULT NULL AFTER `source_serviceID`
 
 
ALTER TABLE `tbl_herbardb_groups`
 ADD `commonnameUpdate` INT( 4 ) NOT NULL ,
 ADD `commonnameInsert` INT( 4 ) NOT NULL

# 21.7.2011 16:44
ALTER TABLE `log_commonnames_name_applies_to` ADD `locked` TINYTEXT NOT NULL AFTER `reference_id`