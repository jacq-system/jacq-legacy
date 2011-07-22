DROP TABLE IF EXISTS `fuzzy_fastsearch_scientific_name_element1`;
CREATE TABLE IF NOT EXISTS `fuzzy_fastsearch_scientific_name_element1` (
  `rank` varchar(100) NOT NULL COMMENT 'Basic element of a scientific name; e.g. the epithet argentatus as used in Larus argentatus argenteus',
  `name_element` varchar(20) NOT NULL,
  `ids` varchar(50) NOT NULL,
  UNIQUE KEY `ids` (`ids`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Individual elements used to generate a scientific name';

DROP TABLE IF EXISTS `fuzzy_fastsearch_scientific_name_element2`;
CREATE TABLE `fuzzy_fastsearch_scientific_name_element2` (
  `genus_name` varchar(100) NOT NULL COMMENT 'Basic element of a scientific name; e.g. the epithet argentatus as used in Larus argentatus argenteus',
  `genusids` text NOT NULL,
  PRIMARY KEY  (`genus_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Individual elements used to generate a scientific name';

ALTER TABLE `_species_details` ADD INDEX ( `genus_id` );

INSERT INTO
 fuzzy_fastsearch_scientific_name_element1 (rank,name_element,ids)
 
SELECT
 tr.rank as rank,
 sn.name_element as 'genus_name',
 t.id as 'ids'
FROM
 scientific_name_element sn
 LEFT JOIN taxon_name_element tne ON tne.scientific_name_element_id=sn.id
 LEFT JOIN taxon t ON t.id=tne.taxon_id
 LEFT JOIN taxonomic_rank tr ON tr.id=t.taxonomic_rank_id
WHERE
 tr.rank in ('kingdom','phylum','class','order','superfamily','family');
 

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
 tr.rank in ('genus')
ON DUPLICATE KEY UPDATE
 genusids=concat(VALUES(genusids),',',genusids);