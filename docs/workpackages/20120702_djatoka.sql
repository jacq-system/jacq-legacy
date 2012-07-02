ALTER TABLE `djatoka_files` ADD `specimen_ID` INT( 11 ) DEFAULT NULL;

ALTER TABLE `djatoka_files` ADD `faulty` TINYINT( 1 ) NOT NULL DEFAULT '0';
