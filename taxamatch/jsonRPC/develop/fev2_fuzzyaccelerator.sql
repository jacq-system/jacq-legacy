
-- Make Search Tables....
DROP TABLE IF EXISTS `fuzzy_fastsearch_name_element1`;
CREATE TABLE `fuzzy_fastsearch_name_element1` (
  `genus_name` varchar(100) NOT NULL COMMENT 'Basic element of a scientific name; e.g. the epithet argentatus as used in Larus argentatus argenteus',
  `taxonids` text NOT NULL,
  `familyids` text NOT NULL,
  PRIMARY KEY  (`genus_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Individual elements used to generate a scientific name';


DROP TABLE IF EXISTS `fuzzy_fastsearch_name_element2`;
CREATE TABLE `fuzzy_fastsearch_name_element2` (
  `subgenus_name` varchar(100) NOT NULL COMMENT 'Basic element of a scientific name; e.g. the epithet argentatus as used in Larus argentatus argenteus',
  `taxonids` text NOT NULL,
  `familyids` text,
  PRIMARY KEY  (`subgenus_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Individual elements used to generate a scientific name';

--Add an unique ID

ALTER TABLE `Taxon_FaEu_v2` ADD `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY

-- Insert Genus
INSERT INTO
 fuzzy_fastsearch_name_element1 (genus_name,taxonids,familyids)

SELECT
 GENUS_NAME as 'genus_name',
 id as 'taxonids',
 IF( TAXON_ID_FAMILY='(null)','',TAXON_ID_FAMILY) as 'familyids'
FROM
 Taxon_FaEu_v2
ON DUPLICATE KEY UPDATE
 taxonids=IF( LOCATE(VALUES(taxonids),taxonids,1)<>0,taxonids, concat(taxonids, IF(taxonids<>'',',','') ,VALUES(taxonids)) ),
 familyids=IF( LOCATE(VALUES(familyids),familyids,1)<>0 or VALUES(familyids)='',  familyids, concat(familyids, IF(familyids<>'',',','') ,VALUES(familyids)) )
;

-- Insert SubGenus
INSERT INTO
 fuzzy_fastsearch_name_element2 (subgenus_name,taxonids,familyids)

SELECT
 INFRAGENUS_NAME as 'subgenus_name',
 id as 'taxonids',
 IF( TAXON_ID_FAMILY='(null)','',TAXON_ID_FAMILY) as 'familyids'
FROM
 Taxon_FaEu_v2
WHERE
 INFRAGENUS_NAME<>'(null)'
ON DUPLICATE KEY UPDATE
 taxonids=IF( LOCATE(VALUES(taxonids),taxonids,1)<>0,taxonids, concat(taxonids, IF(taxonids<>'',',','') ,VALUES(taxonids)) ),
 familyids=IF( LOCATE(VALUES(familyids),familyids,1)<>0 or VALUES(familyids)='',  familyids, concat(familyids, IF(familyids<>'',',','') ,VALUES(familyids)) )
;