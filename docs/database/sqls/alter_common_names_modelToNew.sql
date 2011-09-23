SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';

ALTER TABLE `tbl_name_languages` 
ADD UNIQUE INDEX `iso639-6_UNIQUE` (`iso639-6` ASC) 
, DROP INDEX `iso639-6` ;

CREATE  TABLE IF NOT EXISTS `tbl_name_transliterations` (
  `transliteration_id` INT(11) NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(255) NULL DEFAULT NULL ,
  PRIMARY KEY (`transliteration_id`) ,
  UNIQUE INDEX `name_UNIQUE` (`name` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = latin1
;

ALTER TABLE `tbl_name_names` ADD COLUMN `transliteration_id` INT(11) NOT NULL  AFTER `name_id` , 
  ADD CONSTRAINT `fk_tbl_name_names_tbl_name_transliterations1`
  FOREIGN KEY (`transliteration_id` )
  REFERENCES `tbl_name_transliterations` (`transliteration_id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION
, ADD INDEX `fk_tbl_name_names_tbl_name_transliterations1` (`transliteration_id` ASC) ;

CREATE  TABLE IF NOT EXISTS `tbl_name_tribes` (
  `tribe_id` INT(11) NOT NULL ,
  `tribe_name` VARCHAR(45) NULL DEFAULT NULL ,
  PRIMARY KEY (`tribe_id`) ,
  UNIQUE INDEX `tribe_name_UNIQUE` (`tribe_name` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
;

ALTER TABLE `tbl_name_applies_to` DROP COLUMN `annotation` , ADD COLUMN `tribe_id` INT(11) NOT NULL  AFTER `name_id` , ADD COLUMN `annotations` TEXT NULL DEFAULT NULL  AFTER `geospecification` , DROP FOREIGN KEY `fk_tbl_name_applies_to_tbl_geonames_cache1` , DROP FOREIGN KEY `fk_tbl_name_appliesTo_tbl_name_entities1` , DROP FOREIGN KEY `fk_tbl_name_appliesTo_tbl_name_names1` ;

ALTER TABLE `tbl_name_applies_to` 
  ADD CONSTRAINT `fk_tbl_name_applies_to_tbl_geonames_cache1`
  FOREIGN KEY (`geonameId` )
  REFERENCES `tbl_geonames_cache` (`geonameId` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION, 
  ADD CONSTRAINT `fk_tbl_name_appliesTo_tbl_name_entities1`
  FOREIGN KEY (`entity_id` )
  REFERENCES `tbl_name_entities` (`entity_id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION, 
  ADD CONSTRAINT `fk_tbl_name_appliesTo_tbl_name_names1`
  FOREIGN KEY (`name_id` )
  REFERENCES `tbl_name_names` (`name_id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION, 
  ADD CONSTRAINT `fk_tbl_name_appliesTo_tbl_name_tribes1`
  FOREIGN KEY (`tribe_id` )
  REFERENCES `tbl_name_tribes` (`tribe_id` )
  ON DELETE NO ACTION
  ON UPDATE NO ACTION
, ADD INDEX `fk_tbl_name_appliesTo_tbl_name_tribes1` (`tribe_id` ASC) 
, ADD PRIMARY KEY (`geonameId`, `language_id`, `period_id`, `entity_id`, `reference_id`, `name_id`, `tribe_id`) 
, DROP INDEX `tbl_name_applies_to_UNIQUE` ;

ALTER TABLE `tbl_name_commons` CHANGE COLUMN `locked` `locked` TINYINT(4) NULL DEFAULT '1'  ;

ALTER TABLE `tbl_search_cache` ENGINE = InnoDB ;

CREATE  TABLE IF NOT EXISTS `tbl_name_names_equals` (
  `tbl_name_names_name_id` INT(11) NOT NULL ,
  `tbl_name_names_name_id1` INT(11) NOT NULL ,
  PRIMARY KEY (`tbl_name_names_name_id`, `tbl_name_names_name_id1`) ,
  INDEX `fk_tbl_name_names_has_tbl_name_names_tbl_name_names2` (`tbl_name_names_name_id1` ASC) ,
  INDEX `fk_tbl_name_names_has_tbl_name_names_tbl_name_names1` (`tbl_name_names_name_id` ASC) ,
  CONSTRAINT `fk_tbl_name_names_has_tbl_name_names_tbl_name_names1`
    FOREIGN KEY (`tbl_name_names_name_id` )
    REFERENCES `tbl_name_names` (`name_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_tbl_name_names_has_tbl_name_names_tbl_name_names2`
    FOREIGN KEY (`tbl_name_names_name_id1` )
    REFERENCES `tbl_name_names` (`name_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
