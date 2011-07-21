SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';


-- -----------------------------------------------------
-- Table `tbl_geonames_cache`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tbl_geonames_cache` (
  `geonameId` INT(11) NOT NULL ,
  `name` TEXT NULL DEFAULT NULL ,
  PRIMARY KEY (`geonameId`) ,
  UNIQUE INDEX `geonameId_UNIQUE` (`geonameId` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `tbl_name_languages`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tbl_name_languages` (
  `language_id` INT(11) NOT NULL AUTO_INCREMENT ,
  `iso639-6` VARCHAR(4) NOT NULL ,
  `parent_iso639-6` VARCHAR(4) NULL ,
  `name` VARCHAR(50) NULL ,
  PRIMARY KEY (`language_id`) ,
  UNIQUE INDEX `iso639-6_UNIQUE` (`iso639-6` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `tbl_name_periods`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tbl_name_periods` (
  `period_id` INT(11) NOT NULL AUTO_INCREMENT ,
  `period` VARCHAR(255) NOT NULL ,
  PRIMARY KEY (`period_id`) ,
  UNIQUE INDEX `period_UNIQUE` (`period` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `tbl_name_entities`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tbl_name_entities` (
  `entity_id` INT(11) NOT NULL AUTO_INCREMENT ,
  PRIMARY KEY (`entity_id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `tbl_name_references`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tbl_name_references` (
  `reference_id` INT(11) NOT NULL AUTO_INCREMENT ,
  PRIMARY KEY (`reference_id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `tbl_name_names`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tbl_name_names` (
  `name_id` INT(11) NOT NULL AUTO_INCREMENT ,
  PRIMARY KEY (`name_id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `tbl_name_applies_to`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tbl_name_applies_to` (
  `geonameId` INT(11) NOT NULL ,
  `language_id` INT(11) NOT NULL ,
  `period_id` INT(11) NOT NULL ,
  `entity_id` INT(11) NOT NULL ,
  `reference_id` INT(11) NOT NULL ,
  `name_id` INT(11) NOT NULL ,
  UNIQUE INDEX `tbl_name_applies_to_UNIQUE` (`language_id` ASC, `period_id` ASC, `entity_id` ASC, `reference_id` ASC, `name_id` ASC, `geonameId` ASC) ,
  INDEX `fk_tbl_name_appliesTo_tbl_name_languages1` (`language_id` ASC) ,
  INDEX `fk_tbl_name_appliesTo_tbl_name_periods1` (`period_id` ASC) ,
  INDEX `fk_tbl_name_appliesTo_tbl_name_entities1` (`entity_id` ASC) ,
  INDEX `fk_tbl_name_appliesTo_tbl_name_references1` (`reference_id` ASC) ,
  INDEX `fk_tbl_name_appliesTo_tbl_name_names1` (`name_id` ASC) ,
  INDEX `fk_tbl_name_applies_to_tbl_geonames_cache1` (`geonameId` ASC) ,
  CONSTRAINT `fk_tbl_name_appliesTo_tbl_name_languages1`
    FOREIGN KEY (`language_id` )
    REFERENCES `tbl_name_languages` (`language_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_tbl_name_appliesTo_tbl_name_periods1`
    FOREIGN KEY (`period_id` )
    REFERENCES `tbl_name_periods` (`period_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_tbl_name_appliesTo_tbl_name_entities1`
    FOREIGN KEY (`entity_id` )
    REFERENCES `tbl_name_entities` (`entity_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_tbl_name_appliesTo_tbl_name_references1`
    FOREIGN KEY (`reference_id` )
    REFERENCES `tbl_name_references` (`reference_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_tbl_name_appliesTo_tbl_name_names1`
    FOREIGN KEY (`name_id` )
    REFERENCES `tbl_name_names` (`name_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_tbl_name_applies_to_tbl_geonames_cache1`
    FOREIGN KEY (`geonameId` )
    REFERENCES `tbl_geonames_cache` (`geonameId` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `tbl_name_commons`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tbl_name_commons` (
  `common_id` INT(11) NOT NULL ,
  `common_name` VARCHAR(255) NOT NULL ,
  PRIMARY KEY (`common_id`) ,
  UNIQUE INDEX `common_name_UNIQUE` (`common_name` ASC) ,
  INDEX `fk_tbl_names_common_tbl_names_name` (`common_id` ASC) ,
  CONSTRAINT `fk_tbl_names_common_tbl_names_name`
    FOREIGN KEY (`common_id` )
    REFERENCES `tbl_name_names` (`name_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `tbl_name_literature`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tbl_name_literature` (
  `literature_id` INT(11) NOT NULL ,
  `citationID` INT(11) NOT NULL ,
  PRIMARY KEY (`literature_id`) ,
  UNIQUE INDEX `citationID_UNIQUE` (`citationID` ASC) ,
  CONSTRAINT `fk_tbl_name_literature_tbl_name_references1`
    FOREIGN KEY (`literature_id` )
    REFERENCES `tbl_name_references` (`reference_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `tbl_name_taxa`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tbl_name_taxa` (
  `taxon_id` INT(11) NOT NULL ,
  `taxonID` INT(11) NOT NULL ,
  PRIMARY KEY (`taxon_id`) ,
  UNIQUE INDEX `taxonID_UNIQUE` (`taxonID` ASC) ,
  INDEX `fk_tbl_name_taxon_tbl_name_entities1` (`taxon_id` ASC) ,
  CONSTRAINT `fk_tbl_name_taxon_tbl_name_entities1`
    FOREIGN KEY (`taxon_id` )
    REFERENCES `tbl_name_entities` (`entity_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `tbl_search_cache`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tbl_search_cache` (
  `search_val` VARCHAR(20) NOT NULL ,
  `search_group` INT(2) NOT NULL ,
  `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
  `result` TEXT NULL DEFAULT NULL ,
  PRIMARY KEY (`search_val`) )
ENGINE = MyISAM
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `tbl_name_persons`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tbl_name_persons` (
  `person_id` INT(11) NOT NULL ,
  `person_ID` INT(11) NOT NULL COMMENT 'Pointer to tbl_person' ,
  PRIMARY KEY (`person_id`) ,
  UNIQUE INDEX `person_ID_UNIQUE` (`person_ID` ASC) ,
  CONSTRAINT `fk_tbl_name_person_tbl_name_references1`
    FOREIGN KEY (`person_id` )
    REFERENCES `tbl_name_references` (`reference_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `tbl_name_webservices`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tbl_name_webservices` (
  `webservice_id` INT(11) NOT NULL ,
  `serviceID` INT(11) NOT NULL COMMENT 'Pointer to tbl_nom_service' ,
  PRIMARY KEY (`webservice_id`) ,
  UNIQUE INDEX `serviceID_UNIQUE` (`serviceID` ASC) ,
  CONSTRAINT `fk_tbl_name_services_tbl_name_references1`
    FOREIGN KEY (`webservice_id` )
    REFERENCES `tbl_name_references` (`reference_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;



SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
