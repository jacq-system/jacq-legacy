INSERT INTO herbarinput.tbl_tax_synonymy
(taxonID, acc_taxon_ID, ref_date, preferred_taxonomy, annotations, locked, source, source_citationID,
 source_person_ID, source_serviceID, source_specimenID, userID)
 
 SELECT 
  ts.taxonID as 'taxonID',
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
   herbar_view.tbltaxspecies_acceptedspecies acc
   LEFT JOIN  herbarinput.tbl_tax_species ts ON ts.taxonID=acc.taxonID
   LEFT JOIN herbarinput.tbl_tax_genera tg ON tg.genID=ts.genID
   LEFT JOIN sp2000.tmp_scrutiny_import_all scr on scr.taxonID = ts.taxonID
   LEFT JOIN herbarinput.tbl_lit lit ON lit.citationID= scr.citationID
   LEFT JOIN herbarinput.tbl_tax_synonymy sy ON (sy.taxonID=ts.taxonID and sy.acc_taxon_ID=0)
  WHERE
   sy.tax_syn_ID is null
  ;