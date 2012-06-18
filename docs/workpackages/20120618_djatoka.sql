ALTER TABLE `tbl_img_definition` CHANGE `is_djatoka` `is_djatoka` TINYINT( 1 ) NOT NULL ;

ALTER TABLE `tbl_img_definition` ADD `key` VARCHAR( 50 ) NOT NULL ;
