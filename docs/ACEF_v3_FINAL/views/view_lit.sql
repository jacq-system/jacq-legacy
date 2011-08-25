CREATE
 ALGORITHM=UNDEFINED
VIEW 
 view_lit AS 
SELECT
 ti.taxindID,
 ti.taxonID,
 ti.citationID,
 l.code,
 l.titel,
 l.suptitel,
 l.vol,
 l.part,
 l.jahr,
 l.pp,
 l.verlagsort,
 l.lit_url,
 lp.periodical,
 l.periodicalID,
 le.autor as editor,
 le.autorID as editorID,
 la.autor,
 la.autorID,
 ti.paginae,
 ti.figures,
 ti.annotations,
 l.publisherID,
 pb.publisher
   
FROM 
 herbarinput.tbl_tax_index ti
 LEFT JOIN herbarinput.tbl_lit l ON l.citationID = ti.citationID
 LEFT JOIN herbarinput.tbl_lit_periodicals lp ON lp.periodicalID = l.periodicalID
 LEFT JOIN herbarinput.tbl_lit_authors le ON le.autorID = l.editorsID
 LEFT JOIN herbarinput.tbl_lit_authors la ON la.autorID = l.autorID
 LEFT JOIN herbarinput.tbl_lit_publishers pb ON pb.publisherID = l.publisherID
