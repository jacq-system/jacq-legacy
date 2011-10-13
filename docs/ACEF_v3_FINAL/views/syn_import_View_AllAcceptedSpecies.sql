-- ===========================================
-- ready
-- syn_import_tmp_acceptedspecies
--
-- ===========================================
CREATE OR REPLACE
 ALGORITHM = UNDEFINED
 VIEW herbar_view.syn_import_tmp_acceptedspecies
 AS
  SELECT
   ts.taxonID AS 'taxonID'
  FROM
   herbarinput.tbl_tax_species ts
   LEFT JOIN herbarinput.tbl_tax_genera tg ON tg.genID=ts.genID
 WHERE
       ts.statusID IN (96,93,97,103) -- tts.status_sp2000 IN ('accepted name','provisionally accepted name') -- 
   AND ( ts.tax_rankID='1' OR ( ts.tax_rankID='7'  AND ts.speciesID IS NULL ) ) -- ttr.rank='species' or ( rank=genus and species = Null)
   AND tg.familyID IN ('30','115','182') --  tf.family IN('Annonaceae','Chenopodiaceae','Ebenaceae')
  ;
  
-- ===========================================
-- ready
-- syn_import_tmp_acceptedinfraspecies
--
-- ===========================================
CREATE OR REPLACE
 ALGORITHM = UNDEFINED
 VIEW herbar_view.syn_import_tmp_acceptedinfraspecies
 AS
  SELECT
   ts.taxonID AS 'taxonID'
  FROM
   herbar_view.syn_import_tmp_acceptedspecies acc
   LEFT JOIN  herbarinput.tbl_tax_species tso ON tso.taxonID=acc.taxonID
   LEFT JOIN herbarinput.tbl_tax_genera tgo ON tgo.genID=tso.genID
   INNER JOIN herbarinput.tbl_tax_species ts ON (ts.genID = tso.genID AND ts.speciesID=tso.speciesID)
   
 WHERE
       ts.statusID IN (96,93,97,103) -- tts.status_sp2000 IN ('accepted name','provisionally accepted name') -- 
  AND ts.tax_rankID IN (2,3,4,5,6) --  rank IN ('subspecies','variety','subvariety','forma','subforma')
 ;
 
 -- ===========================================
-- ready
-- syn_import_tmp_acceptedSpeciesAll
--
-- ===========================================
CREATE OR REPLACE
 ALGORITHM = UNDEFINED
 VIEW herbar_view.syn_import_tmp_allacceptedspecies
 AS
  SELECT acc.taxonID AS 'taxonID' FROM herbar_view.syn_import_tmp_acceptedspecies acc
  UNION ALL
  SELECT acc.taxonID AS 'taxonID' FROM herbar_view.syn_import_tmp_acceptedinfraspecies acc
;