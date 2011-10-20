ALTER TABLE `default_schema`.`tbl_img_definition`
 ADD COLUMN `djatoka_path` VARCHAR(255) NOT NULL  AFTER `HerbNummerNrDigits` ,
 ADD COLUMN `is_djatoka` TINYINT(4) NOT NULL  AFTER `djatoka_path` ;  
 
ALTER TABLE `tbl_img_definition`
 CHANGE `djatoka_path` `img_service_directory` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL