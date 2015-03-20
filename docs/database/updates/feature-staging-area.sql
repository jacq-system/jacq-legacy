
use herbarinput;


--
-- new incertae sedis family for the staging area
--

INSERT INTO `tbl_tax_families` (`familyID`, `family`, `authorID`, `categoryID`, `family_alt`, `locked`, `external`, `externalID`) VALUES (3449, 'incertae sedis', 0, 0, NULL, 1, 0, NULL);



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

