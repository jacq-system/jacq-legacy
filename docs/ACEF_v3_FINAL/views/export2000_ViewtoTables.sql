#<pre>
# ===========================================
# http://dev.mysql.com/doc/refman/5.0/en/stored-program-restrictions.html
# no stored procedure for this operation possible in mysql.
# => MYSQL Script to generate...
# ===========================================	
#
# tg.familyID IN ('30','115','182') -- tf.family IN('Annonaceae','Chenopodiaceae','Ebenaceae')
# (  tg.familyID IN('30','115','182')   )
#




# ===========================================
# Dataexport for: Annonaceae
# ===========================================


	DROP TABLE IF EXISTS `herbar_view`.`exp2000_Annonaceae_AcceptedInfraSpecificTaxa`;
DROP TABLE IF EXISTS `herbar_view`.`exp2000_Annonaceae_AcceptedSpecies`;
DROP TABLE IF EXISTS `herbar_view`.`exp2000_Annonaceae_CommonNames`;
DROP TABLE IF EXISTS `herbar_view`.`exp2000_Annonaceae_Distribution`;
DROP TABLE IF EXISTS `herbar_view`.`exp2000_Annonaceae_NameReferencesLinks`;
DROP TABLE IF EXISTS `herbar_view`.`exp2000_Annonaceae_References`;
DROP TABLE IF EXISTS `herbar_view`.`exp2000_Annonaceae_SourceDatabase`;
DROP TABLE IF EXISTS `herbar_view`.`exp2000_Annonaceae_Synonyms`;

#----------------------------
# Table structure for AcceptedInfraSpecificTaxa
#----------------------------
CREATE TABLE `herbar_view`.`exp2000_Annonaceae_AcceptedInfraSpecificTaxa` (
`AcceptedTaxonID` varchar(50) PRIMARY KEY,
`ParentSpeciesID` varchar(50) default NULL,
`InfraSpeciesEpithet` varchar(50) default NULL,
`InfraSpecificAuthorString` varchar(50) default NULL,
`InfraSpecificMarker` varchar(10) default NULL,
`GSDNameStatus` varchar(50) default NULL,
`Sp2000NameStatus` varchar(50) default NULL,
`IsFossil` tinyint(1) default NULL,
`LifeZone` varchar(50) default NULL,
`AdditionalData` varchar(255) default NULL,
`LTSSpecialist` varchar(255) default NULL,
`LTSDate` varchar(50) default NULL,
`InfraSpeciesURL` varchar(255) default NULL,
`GSDTaxonGUI` varchar(255) default NULL,
`GSDNameGUI`  varchar(255) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

#----------------------------
# Table structure for AcceptedSpecies
#----------------------------
CREATE TABLE `herbar_view`.`exp2000_Annonaceae_AcceptedSpecies` (
`AcceptedTaxonID` varchar(50) PRIMARY KEY,
`Kingdom` varchar(50) default NULL,
`Phylum` varchar(50) default NULL,
`Class` varchar(50) default NULL,
`Order` varchar(50) default NULL,
`Superfamily` varchar(50) default NULL,
`Family` varchar(50) default NULL,
`Genus` varchar(50) default NULL,
`SubGenusName` varchar(50) default NULL,
`Species` varchar(50) default NULL,
`AuthorString` varchar(50) default NULL,
`GSDNameStatus` varchar(50) default NULL,
`Sp2000NameStatus` varchar(50) default NULL,
`IsFossil` tinyint(1) default NULL,
`LifeZone` varchar(50) default NULL,
`AdditionalData` varchar(255) default NULL,
`LTSSpecialist` varchar(255) default NULL,
`LTSDate` varchar(50) default NULL,
`SpeciesURL` varchar(255) default NULL,
`GSDTaxonGUI` varchar(255) default NULL,
`GSDNameGUI` varchar(255) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

#----------------------------
# Table structure for CommonNames
#----------------------------
CREATE TABLE `herbar_view`.`exp2000_Annonaceae_CommonNames` (
`AcceptedTaxonID` varchar(50) NOT NULL,
`CommonName` varchar(255) NOT NULL,
`Transliteration` varchar(255) default NULL,
`Language` varchar(50) default NULL,
`Country`  varchar(255) default NULL,
`Area`  varchar(255) default NULL,
`ReferenceID` varchar(50) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

#----------------------------
# Table structure for Distribution
#----------------------------
CREATE TABLE `herbar_view`.`exp2000_Annonaceae_Distribution` (
`AcceptedTaxonID` varchar(50) NOT NULL,
`Distribution` text NOT NULL,
`StandardInUse` varchar(50) default NULL,
`DistributionStatus` varchar(50) default NULL 
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


#----------------------------
# Table structure for References
#----------------------------
CREATE TABLE `herbar_view`.`exp2000_Annonaceae_References` (
`ReferenceID` varchar(50) PRIMARY KEY,
`Authors` varchar(255) default NULL,
`Year` varchar(255) default NULL,
`Title` varchar(255) NOT NULL,
`Details` varchar(255) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

#----------------------------
# Table structure for NameReferencesLinks
#----------------------------
CREATE TABLE `herbar_view`.`exp2000_Annonaceae_NameReferencesLinks` (
`ID` varchar(50) PRIMARY KEY,
/*`ID` integer(10) PRIMARY KEY,*/
`ReferenceType` varchar(50) default NULL,
`ReferenceID` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

#----------------------------
# Table structure for SourceDatabase
#----------------------------
CREATE TABLE `herbar_view`.`exp2000_Annonaceae_SourceDatabase` (
`DatabaseFullName` varchar(255) NOT NULL,
`DatabaseShortName` varchar(50) NOT NULL,
`DatabaseVersion` varchar(5) NOT NULL,
`ReleaseDate` varchar(50) NOT NULL,
`AuthorsEditors` varchar(255) NOT NULL,
`TaxonomicCoverage` varchar(255) NOT NULL,
`GroupNameInEnglish` varchar(255) NOT NULL,
`Abstract` text NOT NULL,
`Organisation` varchar(255) NOT NULL,
`HomeURL` varchar(255) default NULL,
`Coverage` varchar(50) default NULL,
`Completeness` integer(3) default NULL,
`Confidence` integer(1) default NULL,
`LogoFileName`  varchar(255) default NULL,
`ContactPerson` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

#----------------------------
# Table structure for Synonyms
#----------------------------
CREATE TABLE `herbar_view`.`exp2000_Annonaceae_Synonyms` (
`ID` varchar(50) PRIMARY KEY,
/*`ID` integer(10) PRIMARY KEY,*/
`AcceptedTaxaID` varchar(50) NOT NULL,
`Genus` varchar(50) NOT NULL,
`SubGenusName` varchar(50) default NULL,
`Species` varchar(50) NOT NULL,
`AuthorString` varchar(50) default NULL,
`InfraSpecies` varchar(50) default NULL,
`InfraSpecificMarker` varchar(50) default NULL,
`InfraSpecificAuthorString`  varchar(50) default NULL,
`GSDNameStatus` varchar(50) default NULL,
`Sp2000NameStatus` varchar(50) default NULL,
`GSDNameGUI` varchar(255) default NULL 
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*
TRUNCATE `herbar_view`.`exp2000_Annonaceae_AcceptedInfraSpecificTaxa`;
TRUNCATE `herbar_view`.`exp2000_Annonaceae_AcceptedSpecies`;
TRUNCATE `herbar_view`.`exp2000_Annonaceae_CommonNames`;
TRUNCATE `herbar_view`.`exp2000_Annonaceae_Distribution`;
TRUNCATE `herbar_view`.`exp2000_Annonaceae_NameReferencesLinks`;
TRUNCATE `herbar_view`.`exp2000_Annonaceae_References`;
TRUNCATE `herbar_view`.`exp2000_Annonaceae_SourceDatabase`;
TRUNCATE `herbar_view`.`exp2000_Annonaceae_Synonyms`;
*/


INSERT INTO `herbar_view`.`exp2000_Annonaceae_AcceptedInfraSpecificTaxa` SELECT `AcceptedTaxonID`,`ParentSpeciesID`,`InfraSpeciesEpithet`,`InfraSpecificAuthorString`,`InfraSpecificMarker`,`GSDNameStatus`,`Sp2000NameStatus`,`IsFossil`,`LifeZone`,`AdditionalData`,`LTSSpecialist`,`LTSDate`,`InfraSpeciesURL`,`GSDTaxonGUI`,`GSDNameGUI` FROM  `herbar_view`.`view_sp2000_acceptedinfraspecifictaxa` WHERE familyPre='Annonaceae';
INSERT INTO `herbar_view`.`exp2000_Annonaceae_AcceptedSpecies` SELECT `AcceptedTaxonID`,`Kingdom`,`Phylum`,`Class`,`Order`,`Superfamily`,`Family`,`Genus`,`SubGenusName`,`Species`,`AuthorString`,`GSDNameStatus`,`Sp2000NameStatus`,`IsFossil`,`LifeZone`,`AdditionalData`,`LTSSpecialist`,`LTSDate`,`SpeciesURL`,`GSDTaxonGUI`,`GSDNameGUI` FROM  `herbar_view`.`view_sp2000_acceptedspecies` WHERE familyPre='Annonaceae';
INSERT INTO `herbar_view`.`exp2000_Annonaceae_CommonNames` SELECT `AcceptedTaxonID`,`CommonName`,`Transliteration`,`Language`,`Country`,`Area`,`ReferenceID` FROM  `herbar_view`.`view_sp2000_commonnames` WHERE familyPre='Annonaceae';
INSERT INTO `herbar_view`.`exp2000_Annonaceae_Distribution` SELECT `AcceptedTaxonID`,`DistributionElement`,`StandardInUse`,`DistributionStatus` FROM  `herbar_view`.`view_sp2000_distribution` WHERE familyPre='Annonaceae';
INSERT INTO `herbar_view`.`exp2000_Annonaceae_NameReferencesLinks` SELECT `ID`,`Reference Type`,`ReferenceID` FROM `herbar_view`.`view_sp2000_namereferenceslinks` WHERE familyPre='Annonaceae';
INSERT INTO `herbar_view`.`exp2000_Annonaceae_References` SELECT `ReferenceID`,`Authors`,`Year`,`Title`,`Details` FROM `herbar_view`.`view_sp2000_references` WHERE familyPre='Annonaceae';
INSERT INTO `herbar_view`.`exp2000_Annonaceae_SourceDatabase` SELECT `DatabaseFullName`,`DatabaseShortName`,`DatabaseVersion`,`ReleaseDate`,`AuthorsEditors`,`TaxonomicCoverage`,`GroupNameInEnglish`,`Abstract`,`Organisation`,`HomeURL`,`Coverage`,`Completeness`,`Confidence`,`LogoFileName`,`ContactPerson` FROM  `herbar_view`.`view_sp2000_sourcedatabase` WHERE familyPre='Annonaceae';
INSERT INTO `herbar_view`.`exp2000_Annonaceae_Synonyms` SELECT `ID`,`AcceptedTaxonID`,`Genus`,`SubGenusName`,`Species`,`AuthorString`,`InfraSpecies`,`InfraSpecificMarker`,`InfraSpecificAuthorString`,`GSDNameStatus`,`Sp2000NameStatus`,`GSDNameGUI` FROM  `herbar_view`.`view_sp2000_synonyms` WHERE familyPre='Annonaceae';

UPDATE `herbar_view`.`exp2000_Annonaceae_AcceptedInfraSpecificTaxa` SET  `LTSDate`=DATE_FORMAT(NOW(),'%Y-%m-%d') WHERE  `LTSDate`='in prep.';
UPDATE `herbar_view`.`exp2000_Annonaceae_AcceptedSpecies` SET  `LTSDate`=DATE_FORMAT(NOW(),'%Y-%m-%d') WHERE  `LTSDate`='in prep.';




# ===========================================
# Dataexport for: Chenopodiaceae
# ===========================================


	DROP TABLE IF EXISTS `herbar_view`.`exp2000_Chenopodiaceae_AcceptedInfraSpecificTaxa`;
DROP TABLE IF EXISTS `herbar_view`.`exp2000_Chenopodiaceae_AcceptedSpecies`;
DROP TABLE IF EXISTS `herbar_view`.`exp2000_Chenopodiaceae_CommonNames`;
DROP TABLE IF EXISTS `herbar_view`.`exp2000_Chenopodiaceae_Distribution`;
DROP TABLE IF EXISTS `herbar_view`.`exp2000_Chenopodiaceae_NameReferencesLinks`;
DROP TABLE IF EXISTS `herbar_view`.`exp2000_Chenopodiaceae_References`;
DROP TABLE IF EXISTS `herbar_view`.`exp2000_Chenopodiaceae_SourceDatabase`;
DROP TABLE IF EXISTS `herbar_view`.`exp2000_Chenopodiaceae_Synonyms`;

#----------------------------
# Table structure for AcceptedInfraSpecificTaxa
#----------------------------
CREATE TABLE `herbar_view`.`exp2000_Chenopodiaceae_AcceptedInfraSpecificTaxa` (
`AcceptedTaxonID` varchar(50) PRIMARY KEY,
`ParentSpeciesID` varchar(50) default NULL,
`InfraSpeciesEpithet` varchar(50) default NULL,
`InfraSpecificAuthorString` varchar(50) default NULL,
`InfraSpecificMarker` varchar(10) default NULL,
`GSDNameStatus` varchar(50) default NULL,
`Sp2000NameStatus` varchar(50) default NULL,
`IsFossil` tinyint(1) default NULL,
`LifeZone` varchar(50) default NULL,
`AdditionalData` varchar(255) default NULL,
`LTSSpecialist` varchar(255) default NULL,
`LTSDate` varchar(50) default NULL,
`InfraSpeciesURL` varchar(255) default NULL,
`GSDTaxonGUI` varchar(255) default NULL,
`GSDNameGUI`  varchar(255) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

#----------------------------
# Table structure for AcceptedSpecies
#----------------------------
CREATE TABLE `herbar_view`.`exp2000_Chenopodiaceae_AcceptedSpecies` (
`AcceptedTaxonID` varchar(50) PRIMARY KEY,
`Kingdom` varchar(50) default NULL,
`Phylum` varchar(50) default NULL,
`Class` varchar(50) default NULL,
`Order` varchar(50) default NULL,
`Superfamily` varchar(50) default NULL,
`Family` varchar(50) default NULL,
`Genus` varchar(50) default NULL,
`SubGenusName` varchar(50) default NULL,
`Species` varchar(50) default NULL,
`AuthorString` varchar(50) default NULL,
`GSDNameStatus` varchar(50) default NULL,
`Sp2000NameStatus` varchar(50) default NULL,
`IsFossil` tinyint(1) default NULL,
`LifeZone` varchar(50) default NULL,
`AdditionalData` varchar(255) default NULL,
`LTSSpecialist` varchar(255) default NULL,
`LTSDate` varchar(50) default NULL,
`SpeciesURL` varchar(255) default NULL,
`GSDTaxonGUI` varchar(255) default NULL,
`GSDNameGUI` varchar(255) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

#----------------------------
# Table structure for CommonNames
#----------------------------
CREATE TABLE `herbar_view`.`exp2000_Chenopodiaceae_CommonNames` (
`AcceptedTaxonID` varchar(50) NOT NULL,
`CommonName` varchar(255) NOT NULL,
`Transliteration` varchar(255) default NULL,
`Language` varchar(50) default NULL,
`Country`  varchar(255) default NULL,
`Area`  varchar(255) default NULL,
`ReferenceID` varchar(50) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

#----------------------------
# Table structure for Distribution
#----------------------------
CREATE TABLE `herbar_view`.`exp2000_Chenopodiaceae_Distribution` (
`AcceptedTaxonID` varchar(50) NOT NULL,
`Distribution` text NOT NULL,
`StandardInUse` varchar(50) default NULL,
`DistributionStatus` varchar(50) default NULL 
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


#----------------------------
# Table structure for References
#----------------------------
CREATE TABLE `herbar_view`.`exp2000_Chenopodiaceae_References` (
`ReferenceID` varchar(50) PRIMARY KEY,
`Authors` varchar(255) default NULL,
`Year` varchar(255) default NULL,
`Title` varchar(255) NOT NULL,
`Details` varchar(255) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

#----------------------------
# Table structure for NameReferencesLinks
#----------------------------
CREATE TABLE `herbar_view`.`exp2000_Chenopodiaceae_NameReferencesLinks` (
`ID` varchar(50) PRIMARY KEY,
/*`ID` integer(10) PRIMARY KEY,*/
`ReferenceType` varchar(50) default NULL,
`ReferenceID` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

#----------------------------
# Table structure for SourceDatabase
#----------------------------
CREATE TABLE `herbar_view`.`exp2000_Chenopodiaceae_SourceDatabase` (
`DatabaseFullName` varchar(255) NOT NULL,
`DatabaseShortName` varchar(50) NOT NULL,
`DatabaseVersion` varchar(5) NOT NULL,
`ReleaseDate` varchar(50) NOT NULL,
`AuthorsEditors` varchar(255) NOT NULL,
`TaxonomicCoverage` varchar(255) NOT NULL,
`GroupNameInEnglish` varchar(255) NOT NULL,
`Abstract` text NOT NULL,
`Organisation` varchar(255) NOT NULL,
`HomeURL` varchar(255) default NULL,
`Coverage` varchar(50) default NULL,
`Completeness` integer(3) default NULL,
`Confidence` integer(1) default NULL,
`LogoFileName`  varchar(255) default NULL,
`ContactPerson` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

#----------------------------
# Table structure for Synonyms
#----------------------------
CREATE TABLE `herbar_view`.`exp2000_Chenopodiaceae_Synonyms` (
`ID` varchar(50) PRIMARY KEY,
/*`ID` integer(10) PRIMARY KEY,*/
`AcceptedTaxaID` varchar(50) NOT NULL,
`Genus` varchar(50) NOT NULL,
`SubGenusName` varchar(50) default NULL,
`Species` varchar(50) NOT NULL,
`AuthorString` varchar(50) default NULL,
`InfraSpecies` varchar(50) default NULL,
`InfraSpecificMarker` varchar(50) default NULL,
`InfraSpecificAuthorString`  varchar(50) default NULL,
`GSDNameStatus` varchar(50) default NULL,
`Sp2000NameStatus` varchar(50) default NULL,
`GSDNameGUI` varchar(255) default NULL 
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*
TRUNCATE `herbar_view`.`exp2000_Chenopodiaceae_AcceptedInfraSpecificTaxa`;
TRUNCATE `herbar_view`.`exp2000_Chenopodiaceae_AcceptedSpecies`;
TRUNCATE `herbar_view`.`exp2000_Chenopodiaceae_CommonNames`;
TRUNCATE `herbar_view`.`exp2000_Chenopodiaceae_Distribution`;
TRUNCATE `herbar_view`.`exp2000_Chenopodiaceae_NameReferencesLinks`;
TRUNCATE `herbar_view`.`exp2000_Chenopodiaceae_References`;
TRUNCATE `herbar_view`.`exp2000_Chenopodiaceae_SourceDatabase`;
TRUNCATE `herbar_view`.`exp2000_Chenopodiaceae_Synonyms`;
*/


INSERT INTO `herbar_view`.`exp2000_Chenopodiaceae_AcceptedInfraSpecificTaxa` SELECT `AcceptedTaxonID`,`ParentSpeciesID`,`InfraSpeciesEpithet`,`InfraSpecificAuthorString`,`InfraSpecificMarker`,`GSDNameStatus`,`Sp2000NameStatus`,`IsFossil`,`LifeZone`,`AdditionalData`,`LTSSpecialist`,`LTSDate`,`InfraSpeciesURL`,`GSDTaxonGUI`,`GSDNameGUI` FROM  `herbar_view`.`view_sp2000_acceptedinfraspecifictaxa` WHERE familyPre='Chenopodiaceae';
INSERT INTO `herbar_view`.`exp2000_Chenopodiaceae_AcceptedSpecies` SELECT `AcceptedTaxonID`,`Kingdom`,`Phylum`,`Class`,`Order`,`Superfamily`,`Family`,`Genus`,`SubGenusName`,`Species`,`AuthorString`,`GSDNameStatus`,`Sp2000NameStatus`,`IsFossil`,`LifeZone`,`AdditionalData`,`LTSSpecialist`,`LTSDate`,`SpeciesURL`,`GSDTaxonGUI`,`GSDNameGUI` FROM  `herbar_view`.`view_sp2000_acceptedspecies` WHERE familyPre='Chenopodiaceae';
INSERT INTO `herbar_view`.`exp2000_Chenopodiaceae_CommonNames` SELECT `AcceptedTaxonID`,`CommonName`,`Transliteration`,`Language`,`Country`,`Area`,`ReferenceID` FROM  `herbar_view`.`view_sp2000_commonnames` WHERE familyPre='Chenopodiaceae';
INSERT INTO `herbar_view`.`exp2000_Chenopodiaceae_Distribution` SELECT `AcceptedTaxonID`,`DistributionElement`,`StandardInUse`,`DistributionStatus` FROM  `herbar_view`.`view_sp2000_distribution` WHERE familyPre='Chenopodiaceae';
INSERT INTO `herbar_view`.`exp2000_Chenopodiaceae_NameReferencesLinks` SELECT `ID`,`Reference Type`,`ReferenceID` FROM `herbar_view`.`view_sp2000_namereferenceslinks` WHERE familyPre='Chenopodiaceae';
INSERT INTO `herbar_view`.`exp2000_Chenopodiaceae_References` SELECT `ReferenceID`,`Authors`,`Year`,`Title`,`Details` FROM `herbar_view`.`view_sp2000_references` WHERE familyPre='Chenopodiaceae';
INSERT INTO `herbar_view`.`exp2000_Chenopodiaceae_SourceDatabase` SELECT `DatabaseFullName`,`DatabaseShortName`,`DatabaseVersion`,`ReleaseDate`,`AuthorsEditors`,`TaxonomicCoverage`,`GroupNameInEnglish`,`Abstract`,`Organisation`,`HomeURL`,`Coverage`,`Completeness`,`Confidence`,`LogoFileName`,`ContactPerson` FROM  `herbar_view`.`view_sp2000_sourcedatabase` WHERE familyPre='Chenopodiaceae';
INSERT INTO `herbar_view`.`exp2000_Chenopodiaceae_Synonyms` SELECT `ID`,`AcceptedTaxonID`,`Genus`,`SubGenusName`,`Species`,`AuthorString`,`InfraSpecies`,`InfraSpecificMarker`,`InfraSpecificAuthorString`,`GSDNameStatus`,`Sp2000NameStatus`,`GSDNameGUI` FROM  `herbar_view`.`view_sp2000_synonyms` WHERE familyPre='Chenopodiaceae';

UPDATE `herbar_view`.`exp2000_Chenopodiaceae_AcceptedInfraSpecificTaxa` SET  `LTSDate`=DATE_FORMAT(NOW(),'%Y-%m-%d') WHERE  `LTSDate`='in prep.';
UPDATE `herbar_view`.`exp2000_Chenopodiaceae_AcceptedSpecies` SET  `LTSDate`=DATE_FORMAT(NOW(),'%Y-%m-%d') WHERE  `LTSDate`='in prep.';




# ===========================================
# Dataexport for: Ebenaceae
# ===========================================


	DROP TABLE IF EXISTS `herbar_view`.`exp2000_Ebenaceae_AcceptedInfraSpecificTaxa`;
DROP TABLE IF EXISTS `herbar_view`.`exp2000_Ebenaceae_AcceptedSpecies`;
DROP TABLE IF EXISTS `herbar_view`.`exp2000_Ebenaceae_CommonNames`;
DROP TABLE IF EXISTS `herbar_view`.`exp2000_Ebenaceae_Distribution`;
DROP TABLE IF EXISTS `herbar_view`.`exp2000_Ebenaceae_NameReferencesLinks`;
DROP TABLE IF EXISTS `herbar_view`.`exp2000_Ebenaceae_References`;
DROP TABLE IF EXISTS `herbar_view`.`exp2000_Ebenaceae_SourceDatabase`;
DROP TABLE IF EXISTS `herbar_view`.`exp2000_Ebenaceae_Synonyms`;

#----------------------------
# Table structure for AcceptedInfraSpecificTaxa
#----------------------------
CREATE TABLE `herbar_view`.`exp2000_Ebenaceae_AcceptedInfraSpecificTaxa` (
`AcceptedTaxonID` varchar(50) PRIMARY KEY,
`ParentSpeciesID` varchar(50) default NULL,
`InfraSpeciesEpithet` varchar(50) default NULL,
`InfraSpecificAuthorString` varchar(50) default NULL,
`InfraSpecificMarker` varchar(10) default NULL,
`GSDNameStatus` varchar(50) default NULL,
`Sp2000NameStatus` varchar(50) default NULL,
`IsFossil` tinyint(1) default NULL,
`LifeZone` varchar(50) default NULL,
`AdditionalData` varchar(255) default NULL,
`LTSSpecialist` varchar(255) default NULL,
`LTSDate` varchar(50) default NULL,
`InfraSpeciesURL` varchar(255) default NULL,
`GSDTaxonGUI` varchar(255) default NULL,
`GSDNameGUI`  varchar(255) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

#----------------------------
# Table structure for AcceptedSpecies
#----------------------------
CREATE TABLE `herbar_view`.`exp2000_Ebenaceae_AcceptedSpecies` (
`AcceptedTaxonID` varchar(50) PRIMARY KEY,
`Kingdom` varchar(50) default NULL,
`Phylum` varchar(50) default NULL,
`Class` varchar(50) default NULL,
`Order` varchar(50) default NULL,
`Superfamily` varchar(50) default NULL,
`Family` varchar(50) default NULL,
`Genus` varchar(50) default NULL,
`SubGenusName` varchar(50) default NULL,
`Species` varchar(50) default NULL,
`AuthorString` varchar(50) default NULL,
`GSDNameStatus` varchar(50) default NULL,
`Sp2000NameStatus` varchar(50) default NULL,
`IsFossil` tinyint(1) default NULL,
`LifeZone` varchar(50) default NULL,
`AdditionalData` varchar(255) default NULL,
`LTSSpecialist` varchar(255) default NULL,
`LTSDate` varchar(50) default NULL,
`SpeciesURL` varchar(255) default NULL,
`GSDTaxonGUI` varchar(255) default NULL,
`GSDNameGUI` varchar(255) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

#----------------------------
# Table structure for CommonNames
#----------------------------
CREATE TABLE `herbar_view`.`exp2000_Ebenaceae_CommonNames` (
`AcceptedTaxonID` varchar(50) NOT NULL,
`CommonName` varchar(255) NOT NULL,
`Transliteration` varchar(255) default NULL,
`Language` varchar(50) default NULL,
`Country`  varchar(255) default NULL,
`Area`  varchar(255) default NULL,
`ReferenceID` varchar(50) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

#----------------------------
# Table structure for Distribution
#----------------------------
CREATE TABLE `herbar_view`.`exp2000_Ebenaceae_Distribution` (
`AcceptedTaxonID` varchar(50) NOT NULL,
`Distribution` text NOT NULL,
`StandardInUse` varchar(50) default NULL,
`DistributionStatus` varchar(50) default NULL 
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


#----------------------------
# Table structure for References
#----------------------------
CREATE TABLE `herbar_view`.`exp2000_Ebenaceae_References` (
`ReferenceID` varchar(50) PRIMARY KEY,
`Authors` varchar(255) default NULL,
`Year` varchar(255) default NULL,
`Title` varchar(255) NOT NULL,
`Details` varchar(255) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

#----------------------------
# Table structure for NameReferencesLinks
#----------------------------
CREATE TABLE `herbar_view`.`exp2000_Ebenaceae_NameReferencesLinks` (
`ID` varchar(50) PRIMARY KEY,
/*`ID` integer(10) PRIMARY KEY,*/
`ReferenceType` varchar(50) default NULL,
`ReferenceID` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

#----------------------------
# Table structure for SourceDatabase
#----------------------------
CREATE TABLE `herbar_view`.`exp2000_Ebenaceae_SourceDatabase` (
`DatabaseFullName` varchar(255) NOT NULL,
`DatabaseShortName` varchar(50) NOT NULL,
`DatabaseVersion` varchar(5) NOT NULL,
`ReleaseDate` varchar(50) NOT NULL,
`AuthorsEditors` varchar(255) NOT NULL,
`TaxonomicCoverage` varchar(255) NOT NULL,
`GroupNameInEnglish` varchar(255) NOT NULL,
`Abstract` text NOT NULL,
`Organisation` varchar(255) NOT NULL,
`HomeURL` varchar(255) default NULL,
`Coverage` varchar(50) default NULL,
`Completeness` integer(3) default NULL,
`Confidence` integer(1) default NULL,
`LogoFileName`  varchar(255) default NULL,
`ContactPerson` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

#----------------------------
# Table structure for Synonyms
#----------------------------
CREATE TABLE `herbar_view`.`exp2000_Ebenaceae_Synonyms` (
`ID` varchar(50) PRIMARY KEY,
/*`ID` integer(10) PRIMARY KEY,*/
`AcceptedTaxaID` varchar(50) NOT NULL,
`Genus` varchar(50) NOT NULL,
`SubGenusName` varchar(50) default NULL,
`Species` varchar(50) NOT NULL,
`AuthorString` varchar(50) default NULL,
`InfraSpecies` varchar(50) default NULL,
`InfraSpecificMarker` varchar(50) default NULL,
`InfraSpecificAuthorString`  varchar(50) default NULL,
`GSDNameStatus` varchar(50) default NULL,
`Sp2000NameStatus` varchar(50) default NULL,
`GSDNameGUI` varchar(255) default NULL 
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*
TRUNCATE `herbar_view`.`exp2000_Ebenaceae_AcceptedInfraSpecificTaxa`;
TRUNCATE `herbar_view`.`exp2000_Ebenaceae_AcceptedSpecies`;
TRUNCATE `herbar_view`.`exp2000_Ebenaceae_CommonNames`;
TRUNCATE `herbar_view`.`exp2000_Ebenaceae_Distribution`;
TRUNCATE `herbar_view`.`exp2000_Ebenaceae_NameReferencesLinks`;
TRUNCATE `herbar_view`.`exp2000_Ebenaceae_References`;
TRUNCATE `herbar_view`.`exp2000_Ebenaceae_SourceDatabase`;
TRUNCATE `herbar_view`.`exp2000_Ebenaceae_Synonyms`;
*/


INSERT INTO `herbar_view`.`exp2000_Ebenaceae_AcceptedInfraSpecificTaxa` SELECT `AcceptedTaxonID`,`ParentSpeciesID`,`InfraSpeciesEpithet`,`InfraSpecificAuthorString`,`InfraSpecificMarker`,`GSDNameStatus`,`Sp2000NameStatus`,`IsFossil`,`LifeZone`,`AdditionalData`,`LTSSpecialist`,`LTSDate`,`InfraSpeciesURL`,`GSDTaxonGUI`,`GSDNameGUI` FROM  `herbar_view`.`view_sp2000_acceptedinfraspecifictaxa` WHERE familyPre='Ebenaceae';
INSERT INTO `herbar_view`.`exp2000_Ebenaceae_AcceptedSpecies` SELECT `AcceptedTaxonID`,`Kingdom`,`Phylum`,`Class`,`Order`,`Superfamily`,`Family`,`Genus`,`SubGenusName`,`Species`,`AuthorString`,`GSDNameStatus`,`Sp2000NameStatus`,`IsFossil`,`LifeZone`,`AdditionalData`,`LTSSpecialist`,`LTSDate`,`SpeciesURL`,`GSDTaxonGUI`,`GSDNameGUI` FROM  `herbar_view`.`view_sp2000_acceptedspecies` WHERE familyPre='Ebenaceae';
INSERT INTO `herbar_view`.`exp2000_Ebenaceae_CommonNames` SELECT `AcceptedTaxonID`,`CommonName`,`Transliteration`,`Language`,`Country`,`Area`,`ReferenceID` FROM  `herbar_view`.`view_sp2000_commonnames` WHERE familyPre='Ebenaceae';
INSERT INTO `herbar_view`.`exp2000_Ebenaceae_Distribution` SELECT `AcceptedTaxonID`,`DistributionElement`,`StandardInUse`,`DistributionStatus` FROM  `herbar_view`.`view_sp2000_distribution` WHERE familyPre='Ebenaceae';
INSERT INTO `herbar_view`.`exp2000_Ebenaceae_NameReferencesLinks` SELECT `ID`,`Reference Type`,`ReferenceID` FROM `herbar_view`.`view_sp2000_namereferenceslinks` WHERE familyPre='Ebenaceae';
INSERT INTO `herbar_view`.`exp2000_Ebenaceae_References` SELECT `ReferenceID`,`Authors`,`Year`,`Title`,`Details` FROM `herbar_view`.`view_sp2000_references` WHERE familyPre='Ebenaceae';
INSERT INTO `herbar_view`.`exp2000_Ebenaceae_SourceDatabase` SELECT `DatabaseFullName`,`DatabaseShortName`,`DatabaseVersion`,`ReleaseDate`,`AuthorsEditors`,`TaxonomicCoverage`,`GroupNameInEnglish`,`Abstract`,`Organisation`,`HomeURL`,`Coverage`,`Completeness`,`Confidence`,`LogoFileName`,`ContactPerson` FROM  `herbar_view`.`view_sp2000_sourcedatabase` WHERE familyPre='Ebenaceae';
INSERT INTO `herbar_view`.`exp2000_Ebenaceae_Synonyms` SELECT `ID`,`AcceptedTaxonID`,`Genus`,`SubGenusName`,`Species`,`AuthorString`,`InfraSpecies`,`InfraSpecificMarker`,`InfraSpecificAuthorString`,`GSDNameStatus`,`Sp2000NameStatus`,`GSDNameGUI` FROM  `herbar_view`.`view_sp2000_synonyms` WHERE familyPre='Ebenaceae';

UPDATE `herbar_view`.`exp2000_Ebenaceae_AcceptedInfraSpecificTaxa` SET  `LTSDate`=DATE_FORMAT(NOW(),'%Y-%m-%d') WHERE  `LTSDate`='in prep.';
UPDATE `herbar_view`.`exp2000_Ebenaceae_AcceptedSpecies` SET  `LTSDate`=DATE_FORMAT(NOW(),'%Y-%m-%d') WHERE  `LTSDate`='in prep.';



#</pre>