DROP TABLE IF EXISTS `fuzzy_fastsearch_scientific_name_element1`;
CREATE TABLE `fuzzy_fastsearch_scientific_name_element1` (
  `name_element` varchar(100) NOT NULL COMMENT 'Basic element of a scientific name; e.g. the epithet argentatus as used in Larus argentatus argenteus',
  `rank` varchar(20) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Individual elements used to generate a scientific name';

DROP TABLE IF EXISTS `fuzzy_fastsearch_scientific_name_element2`;
CREATE TABLE `fuzzy_fastsearch_scientific_name_element2` (
  `genus_name` varchar(100) NOT NULL COMMENT 'Basic element of a scientific name; e.g. the epithet argentatus as used in Larus argentatus argenteus',
  `genusids` text NOT NULL,
  PRIMARY KEY  (`genus_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Individual elements used to generate a scientific name';


INSERT INTO
 fuzzy_fastsearch_scientific_name_element1 (name_element,rank)
SELECT
 t.rank as rank,
 t.name_element as name_element
FROM (
           SELECT distinct 'kingdom' as 'rank',  kingdom_name as 'name_element' FROM `_species_details`
 UNION ALL SELECT distinct 'phylum_name' as 'rank',  phylum_name as 'name_element' FROM `_species_details`
 UNION ALL SELECT distinct 'class_name' as 'rank',  class_name as 'name_element' FROM `_species_details`
 UNION ALL SELECT distinct 'order_name' as 'rank',  order_name as 'name_element' FROM `_species_details`
 UNION ALL SELECT distinct 'superfamily_name' as 'rank',  superfamily_name as 'name_element' FROM `_species_details`
 UNION ALL SELECT distinct 'family_name' as 'rank',  family_name as 'name_element' FROM `_species_details`

) t;

INSERT INTO
 fuzzy_fastsearch_scientific_name_element2 (genus_name,genusids)

SELECT
 sn.name_element as 'genus_name',
 tne.taxon_id as 'genusids'
FROM
 scientific_name_element sn
 LEFT JOIN taxon_name_element tne ON tne.scientific_name_element_id=sn.id
 LEFT JOIN taxon t ON t.id=tne.taxon_id
 LEFT JOIN taxonomic_rank tr ON tr.id=t.taxonomic_rank_id
WHERE
 t.taxonomic_rank_id='20'
ON DUPLICATE KEY UPDATE
 genusids=concat(VALUES(genusids),',',genusids);