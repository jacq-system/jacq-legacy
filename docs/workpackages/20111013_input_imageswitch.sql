ALTER TABLE `default_schema`.`tbl_img_definition`
 ADD COLUMN `djatoka_path` VARCHAR(255) NOT NULL  AFTER `HerbNummerNrDigits` ,
 ADD COLUMN `is_djatoka` TINYINT(4) NOT NULL  AFTER `djatoka_path` ;  