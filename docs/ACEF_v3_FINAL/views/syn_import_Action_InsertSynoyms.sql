-- DEL FROM herbarinput.tbl_tax_synonymy WHERE annotations LIKE '%import13%'
INSERT INTO herbarinput.tbl_tax_synonymy
(taxonID, acc_taxon_ID, ref_date, preferred_taxonomy, annotations, locked, source, source_citationID,
 source_person_ID, source_serviceID, source_specimenID, userID)
 
 SELECT 
  taxsyns.SynonymID as 'taxonID',
  taxsyns.AcceptedTaxonID as 'acc_taxon_ID',
  lit.jahr as 'ref_date',
  '' AS 'preferred_taxonomy',
	
  'import13' as 'annotations',
  '1' as 'locked',
  lit.citationID as 'source_citationID',
  null as 'source_person_ID',
  null as 'source_serviceID',
  null as 'source_specimenID',
	
  '2' as 'userID'
 FROM
   herbar_view.view_sp2000_tmp_tabl_synonyms_normalized taxsyns
   LEFT JOIN herbar_view.syn_import_tmp_msaccess_scrutiny scr on scr.taxonID = taxsyns.AcceptedTaxonID
   LEFT JOIN herbarinput.tbl_lit lit ON lit.citationID= scr.citationID
   LEFT JOIN herbarinput.tbl_tax_synonymy sy ON (sy.taxonID=taxsyns.SynonymID and sy.acc_taxon_ID=taxsyns.AcceptedTaxonID)
  WHERE
   sy.tax_syn_ID is null;