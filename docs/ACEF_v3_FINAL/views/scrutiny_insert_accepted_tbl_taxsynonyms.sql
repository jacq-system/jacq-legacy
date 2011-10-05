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
   herbarinput.tbl_tax_species ts
   LEFT JOIN herbarinput.tbl_tax_genera tg ON tg.genID=ts.genID
   LEFT JOIN sp2000.tmp_scrutiny_import_all scr on scr.taxonID = ts.taxonID
   LEFT JOIN herbarinput.tbl_lit lit ON lit.citationID= scr.citationID
   LEFT JOIN herbarinput.tbl_tax_synonymy sy ON (sy.taxonID=ts.taxonID and sy.acc_taxon_ID=0)
  WHERE
       sy.tax_syn_ID is null
   AND ts.statusID IN (96,93,97,103) -- tts.status_sp2000 IN ('accepted name','provisionally accepted name') -- 
   AND ( ts.tax_rankID='1' OR ( ts.tax_rankID='7'  AND ts.speciesID IS NULL ) ) -- ttr.rank='species' or ( rank=genus and species = Null)
   AND tg.familyID IN ('30','115','182') --  tf.family IN('Annonaceae','Chenopodiaceae','Ebenaceae')
  ;