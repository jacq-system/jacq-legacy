
CREATE TABLE IF NOT EXISTS `fuzzy_fastsearch_scientific_name_element` (
  `genus_name` varchar(100) NOT NULL COMMENT 'Basic element of a scientific name; e.g. the epithet argentatus as used in Larus argentatus argenteus',
  `rank` varchar(50) default NULL,
  `genusids` text NOT NULL,
  KEY `rank` (`rank`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Individual elements used to generate a scientific name';


ALTER TABLE `_search_scientific` ADD PRIMARY KEY ( `id` );


INSERT INTO fuzzy_fastsearch_scientific_name_element(genus_name,rank,genusids)
SELECT name_element, rank, GROUP_CONCAT(id) FROM _search_all GROUP BY name_element,rank;