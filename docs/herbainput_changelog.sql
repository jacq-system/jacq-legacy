ALTER TABLE `tbl_tax_synonymy`
 ADD `ref_date` DATE NULL DEFAULT NULL AFTER `acc_taxon_ID`,
 ADD `source_specimenID` INT( 11 ) NULL DEFAULT NULL AFTER `source_serviceID`
