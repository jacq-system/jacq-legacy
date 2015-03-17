
use herbarinput;


--
-- adding a fk to the user to tbl_tax_species
--
ALTER TABLE `tbl_tax_species`
	ADD COLUMN `user_ID_fk` INT NULL DEFAULT NULL COMMENT 'foreignkey to herbarinput_log.tbl_herbardb_users' AFTER `API_taxID_fk`; 


-- 
-- tbl_specimens_import_users
-- 
CREATE TABLE `tbl_specimens_import_users` (
	`specimen_ID` INT NOT NULL,
	`user_ID` INT NOT NULL,
	PRIMARY KEY (`specimen_ID`, `user_ID`)
)
COMMENT='relationsship table which allows to assign specimens in the staging area to be assigned to users in herbarinput_log.tbl_herbardb_users'
COLLATE='utf8_general_ci'
ENGINE=InnoDB;

