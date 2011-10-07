-- ===========================================
-- ready
-- tbltaxspecies_acceptedspecies
--
-- ===========================================
CREATE OR REPLACE
 ALGORITHM = UNDEFINED
 VIEW herbar_view.tbltaxspecies_acceptedspecies
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