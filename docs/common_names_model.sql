SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';


-- -----------------------------------------------------
-- Table `tbl_name_names`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tbl_name_names` (
  `name_id` INT NOT NULL AUTO_INCREMENT ,
  PRIMARY KEY (`name_id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;


-- -----------------------------------------------------
-- Table `tbl_name_commons`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tbl_name_commons` (
  `common_id` INT NOT NULL ,
  `common_name` VARCHAR(255) NOT NULL ,
  PRIMARY KEY (`common_id`) ,
  INDEX `fk_tbl_names_common_tbl_names_name` (`common_id` ASC) ,
  UNIQUE INDEX `common_name_UNIQUE` (`common_name` ASC) ,
  CONSTRAINT `fk_tbl_names_common_tbl_names_name`
    FOREIGN KEY (`common_id` )
    REFERENCES `tbl_name_names` (`name_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;


-- -----------------------------------------------------
-- Table `tbl_name_languages`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tbl_name_languages` (
  `language_id` INT NOT NULL AUTO_INCREMENT ,
  `iso639-6` VARCHAR(4) NOT NULL ,
  PRIMARY KEY (`language_id`) ,
  UNIQUE INDEX `iso639-6_UNIQUE` (`iso639-6` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;


-- -----------------------------------------------------
-- Table `tbl_name_periods`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tbl_name_periods` (
  `period_id` INT NOT NULL AUTO_INCREMENT ,
  `period` VARCHAR(255) NOT NULL ,
  PRIMARY KEY (`period_id`) ,
  UNIQUE INDEX `period_UNIQUE` (`period` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;


-- -----------------------------------------------------
-- Table `tbl_name_entities`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tbl_name_entities` (
  `entity_id` INT NOT NULL ,
  PRIMARY KEY (`entity_id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;


-- -----------------------------------------------------
-- Table `tbl_name_references`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tbl_name_references` (
  `reference_id` INT NOT NULL AUTO_INCREMENT ,
  PRIMARY KEY (`reference_id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;


-- -----------------------------------------------------
-- Table `tbl_geonames_cache`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tbl_geonames_cache` (
  `geonameId` INT NOT NULL ,
  `name` TEXT NULL ,
  UNIQUE INDEX `geonameId_UNIQUE` (`geonameId` ASC) ,
  PRIMARY KEY (`geonameId`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;


-- -----------------------------------------------------
-- Table `tbl_name_applies_to`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tbl_name_applies_to` (
  `geonameId` INT NOT NULL ,
  `language_id` INT NOT NULL ,
  `period_id` INT NOT NULL ,
  `entity_id` INT NOT NULL ,
  `reference_id` INT NOT NULL ,
  `name_id` INT NOT NULL ,
  INDEX `fk_tbl_name_appliesTo_tbl_name_languages1` (`language_id` ASC) ,
  INDEX `fk_tbl_name_appliesTo_tbl_name_periods1` (`period_id` ASC) ,
  INDEX `fk_tbl_name_appliesTo_tbl_name_entities1` (`entity_id` ASC) ,
  INDEX `fk_tbl_name_appliesTo_tbl_name_references1` (`reference_id` ASC) ,
  INDEX `fk_tbl_name_appliesTo_tbl_name_names1` (`name_id` ASC) ,
  UNIQUE INDEX `tbl_name_applies_to_UNIQUE` (`language_id` ASC, `period_id` ASC, `entity_id` ASC, `reference_id` ASC, `name_id` ASC, `geonameId` ASC) ,
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
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;


-- -----------------------------------------------------
-- Table `tbl_name_taxon`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tbl_name_taxon` (
  `taxon_id` INT NOT NULL ,
  `taxonID` INT NOT NULL ,
  INDEX `fk_tbl_name_taxon_tbl_name_entities1` (`taxon_id` ASC) ,
  PRIMARY KEY (`taxon_id`) ,
  UNIQUE INDEX `taxonID_UNIQUE` (`taxonID` ASC) ,
  CONSTRAINT `fk_tbl_name_taxon_tbl_name_entities1`
    FOREIGN KEY (`taxon_id` )
    REFERENCES `tbl_name_entities` (`entity_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;


-- -----------------------------------------------------
-- Table `tbl_name_literature`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tbl_name_literature` (
  `literature_id` INT NOT NULL ,
  `citationID` INT NOT NULL ,
  PRIMARY KEY (`literature_id`) ,
  UNIQUE INDEX `citationID_UNIQUE` (`citationID` ASC) ,
  CONSTRAINT `fk_tbl_name_literature_tbl_name_references1`
    FOREIGN KEY (`literature_id` )
    REFERENCES `tbl_name_references` (`reference_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;



SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
