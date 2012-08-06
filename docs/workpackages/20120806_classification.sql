ALTER TABLE `tbl_lit_container` ADD INDEX ( `citation_child_ID` );

ALTER TABLE `tbl_tax_synonymy` ADD INDEX( `acc_taxon_ID`, `source_citationID`);
