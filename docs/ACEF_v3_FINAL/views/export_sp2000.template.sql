DROP TABLE IF EXISTS `acs_col`.`exp2000_IFAMILYNAME_AcceptedInfraSpecificTaxa`;
DROP TABLE IF EXISTS `acs_col`.`exp2000_IFAMILYNAME_AcceptedSpecies`;
DROP TABLE IF EXISTS `acs_col`.`exp2000_IFAMILYNAME_CommonNames`;
DROP TABLE IF EXISTS `acs_col`.`exp2000_IFAMILYNAME_Distribution`;
DROP TABLE IF EXISTS `acs_col`.`exp2000_IFAMILYNAME_NameReferencesLinks`;
DROP TABLE IF EXISTS `acs_col`.`exp2000_IFAMILYNAME_References`;
DROP TABLE IF EXISTS `acs_col`.`exp2000_IFAMILYNAME_SourceDatabase`;
DROP TABLE IF EXISTS `acs_col`.`exp2000_IFAMILYNAME_Synonyms`;

#----------------------------
# Table structure for AcceptedInfraSpecificTaxa
#----------------------------
CREATE TABLE `acs_col`.`exp2000_IFAMILYNAME_AcceptedInfraSpecificTaxa` (
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
CREATE TABLE `acs_col`.`exp2000_IFAMILYNAME_AcceptedSpecies` (
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
CREATE TABLE `acs_col`.`exp2000_IFAMILYNAME_CommonNames` (
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
CREATE TABLE `acs_col`.`exp2000_IFAMILYNAME_Distribution` (
`AcceptedTaxonID` varchar(50) NOT NULL,
`Distribution` text NOT NULL,
`StandardInUse` varchar(50) default NULL,
`DistributionStatus` varchar(50) default NULL 
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


#----------------------------
# Table structure for References
#----------------------------
CREATE TABLE `acs_col`.`exp2000_IFAMILYNAME_References` (
`ReferenceID` varchar(50) PRIMARY KEY,
`Authors` varchar(255) default NULL,
`Year` varchar(255) default NULL,
`Title` varchar(255) NOT NULL,
`Details` varchar(255) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

#----------------------------
# Table structure for NameReferencesLinks
#----------------------------
CREATE TABLE `acs_col`.`exp2000_IFAMILYNAME_NameReferencesLinks` (
`ID` varchar(50) PRIMARY KEY,
/*`ID` integer(10) PRIMARY KEY,*/
`ReferenceType` varchar(50) default NULL,
`ReferenceID` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

#----------------------------
# Table structure for SourceDatabase
#----------------------------
CREATE TABLE `acs_col`.`exp2000_IFAMILYNAME_SourceDatabase` (
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
CREATE TABLE `acs_col`.`exp2000_IFAMILYNAME_Synonyms` (
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

INSERT INTO `acs_col`.`exp2000_IFAMILYNAME_AcceptedInfraSpecificTaxa` SELECT `AcceptedTaxonID`,`ParentSpeciesID`,`InfraSpeciesEpithet`,`InfraSpecificAuthorString`,`InfraSpecificMarker`,`GSDNameStatus`,`Sp2000NameStatus`,`IsFossil`,`LifeZone`,`AdditionalData`,`LTSSpecialist`,`LTSDate`,`InfraSpeciesURL`,`GSDTaxonGUI`,`GSDNameGUI` FROM  `acs_col`.`view_sp2000_acceptedinfraspecifictaxa` WHERE familyPre LIKE '%IFAMILYNAME%';
INSERT INTO `acs_col`.`exp2000_IFAMILYNAME_AcceptedSpecies` SELECT `AcceptedTaxonID`,`Kingdom`,`Phylum`,`Class`,`Order`,`Superfamily`,`Family`,`Genus`,`SubGenusName`,`Species`,`AuthorString`,`GSDNameStatus`,`Sp2000NameStatus`,`IsFossil`,`LifeZone`,`AdditionalData`,`LTSSpecialist`,`LTSDate`,`SpeciesURL`,`GSDTaxonGUI`,`GSDNameGUI` FROM  `acs_col`.`view_sp2000_acceptedspecies` WHERE familyPre LIKE '%IFAMILYNAME%';
INSERT INTO `acs_col`.`exp2000_IFAMILYNAME_CommonNames` SELECT `AcceptedTaxonID`,`CommonName`,`Transliteration`,`Language`,`Country`,`Area`,`ReferenceID` FROM  `acs_col`.`view_sp2000_commonnames` WHERE familyPre LIKE '%IFAMILYNAME%';
INSERT INTO `acs_col`.`exp2000_IFAMILYNAME_Distribution` SELECT `AcceptedTaxonID`,`DistributionElement`,`StandardInUse`,`DistributionStatus` FROM  `acs_col`.`view_sp2000_distribution` WHERE familyPre LIKE '%IFAMILYNAME%';
INSERT INTO `acs_col`.`exp2000_IFAMILYNAME_NameReferencesLinks` SELECT `ID`,`Reference Type`,`ReferenceID` FROM `acs_col`.`view_sp2000_namereferenceslinks` WHERE familyPre LIKE '%IFAMILYNAME%';
INSERT INTO `acs_col`.`exp2000_IFAMILYNAME_References` SELECT `ReferenceID`,`Authors`,`Year`,`Title`,`Details` FROM `acs_col`.`view_sp2000_references` WHERE familyPre LIKE '%IFAMILYNAME%';
INSERT INTO `acs_col`.`exp2000_IFAMILYNAME_SourceDatabase` SELECT `DatabaseFullName`,`DatabaseShortName`,`DatabaseVersion`,`ReleaseDate`,`AuthorsEditors`,`TaxonomicCoverage`,`GroupNameInEnglish`,`Abstract`,`Organisation`,`HomeURL`,`Coverage`,`Completeness`,`Confidence`,`LogoFileName`,`ContactPerson` FROM  `acs_col`.`view_sp2000_sourcedatabase` WHERE familyPre LIKE '%IFAMILYNAME%';
INSERT INTO `acs_col`.`exp2000_IFAMILYNAME_Synonyms` SELECT `ID`,`AcceptedTaxonID`,`Genus`,`SubGenusName`,`Species`,`AuthorString`,`InfraSpecies`,`InfraSpecificMarker`,`InfraSpecificAuthorString`,`GSDNameStatus`,`Sp2000NameStatus`,`GSDNameGUI` FROM  `acs_col`.`view_sp2000_synonyms` WHERE familyPre LIKE '%IFAMILYNAME%';

UPDATE `acs_col`.`exp2000_IFAMILYNAME_AcceptedInfraSpecificTaxa` SET  `LTSDate`=DATE_FORMAT(NOW(),'%Y-%m-%d') WHERE  `LTSDate`='in prep.';
UPDATE `acs_col`.`exp2000_IFAMILYNAME_AcceptedSpecies` SET  `LTSDate`=DATE_FORMAT(NOW(),'%Y-%m-%d') WHERE  `LTSDate`='in prep.';
