INSERT INTO herbarinput.tbl_tax_synonymy
(taxonID, acc_taxon_ID, ref_date, preferred_taxonomy, annotations, locked, source, source_citationID,
 source_person_ID, source_serviceID, source_specimenID, userID)
 
 SELECT 
  SUBSTR(taxonids.AcceptedTaxonID,2) as 'taxonID',
  '0' as 'acc_taxon_ID',
  lit.jahr as 'ref_date',
  '' AS 'preferred_taxonomy',
	
  'import12' as 'annotations',
  '1' as 'locked',
  'literature' as 'source',
  lit.citationID as 'source_citationID',
  '' as 'source_person_ID',
  '' as 'source_serviceID',
  '' as 'source_specimenID',
	
  '2' as 'userID'
 FROM
   herbar_view.view_sp2000_tmp_AcceptedTaxonID taxonids
   LEFT JOIN sp2000.tmp_scrutiny_import_all scr on scr.taxonID = SUBSTR(taxonids.AcceptedTaxonID,2)
   LEFT JOIN herbarinput.tbl_lit lit ON lit.citationID= scr.citationID
   LEFT JOIN herbarinput.tbl_tax_synonymy sy ON (sy.taxonID=SUBSTR(taxonids.AcceptedTaxonID,2) and sy.acc_taxon_ID=0)
  WHERE
   sy.tax_syn_ID is null;