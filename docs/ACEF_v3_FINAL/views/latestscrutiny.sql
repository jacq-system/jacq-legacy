-- scrutiny
SELECT
 syn.taxonID,
 syn.acc_taxon_ID,
 syn.ref_date,
 source_citationID

FROM
 herbarinput.tbl_tax_synonymy syn

WHERE
 IFNULL(syn.ref_date,0)=(
  SELECT
   IFNULL(MAX(syn2.ref_date),0) 
  FROM
   herbarinput.tbl_tax_synonymy syn2
  WHERE
       IFNULL(syn2.taxonID,0)= IFNULL(syn.taxonID,0)
   and IFNULL(syn2.acc_taxon_ID,0)= IFNULL(syn.acc_taxon_ID,0)
 )
 and syn.taxonID IN ('6856','5200')  
GROUP BY
 syn.taxonID, 
 syn.acc_taxon_ID;