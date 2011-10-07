-- SELECT * FROM
UPDATE
 herbar_view.syn_import_tmp_msaccess_scrutiny scr
 INNER JOIN herbarinput.tbl_tax_synonymy syn ON (syn.taxonID=scr.taxonID )
 INNER JOIN herbarinput.tbl_lit lit ON lit.citationID= scr.citationID
SET
 syn.source_citationID=scr.citationID,
 syn.source='literature',
 syn.annotations='update12',
 syn.userID='2'
WHERE
     scr.citationID is not null
 -- AND syn.acc_taxon_ID is null
 -- AND ( syn.acc_taxon_ID is null or syn.acc_taxon_ID=0)
 -- AND syn.source_citationID is null
 AND (syn.source_citationID is null or syn.source_citationID =0)
 