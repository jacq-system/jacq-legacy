CREATE TABLE IF NOT EXISTS `tbl_tax_classification` (
  `classification_id` int(11) NOT NULL AUTO_INCREMENT,
  `tax_syn_ID` int(11) NOT NULL,
  `parent_taxonID` int(11) NOT NULL,
  PRIMARY KEY (`classification_id`),
  UNIQUE KEY `tax_syn_ID` (`tax_syn_ID`,`parent_taxonID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=15 ;
