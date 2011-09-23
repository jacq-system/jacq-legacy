-- ===========================================
-- ready
-- view_sp2000_tmp_tabl_synonyms_normalized
-- old: for tbl_tax_specimens
-- ===========================================
DROP TABLE IF EXISTS herbar_view.view_sp2000_tmp_tabl_synonyms_normalized;
CREATE TABLE herbar_view.view_sp2000_tmp_tabl_synonyms_normalized(
AcceptedTaxonID INT NOT NULL ,
SynonymID INT NOT NULL 
) ENGINE = MYISAM ;

-- ===========================================
-- ready
-- do_synonym_normalizing
--
-- ===========================================
DROP PROCEDURE IF EXISTS do_synonym_normalizing;
DELIMITER $$

CREATE PROCEDURE do_synonym_normalizing()
BEGIN
 
 DECLARE taxon_search INT DEFAULT 48465 ; 
 DECLARE xx INT DEFAULT 0 ; 
 DECLARE AcceptedTaxonID,NEXTSYNID,SYNONYMID INT;
 DECLARE done BOOLEAN DEFAULT 0;
 
 DECLARE cur_taxsyn CURSOR FOR
  SELECT
   ts.taxonID AS 'AcceptedTaxonID',
   ts.synID AS 'NEXTSYNID',
   ts3.taxonID AS 'SYNONYMID'

  FROM
   herbarinput.tbl_tax_species ts
   CROSS JOIN herbarinput.tbl_tax_species ts2
   CROSS JOIN herbarinput.tbl_tax_species ts3
  WHERE
   -- Umsetzung aus lists2ynonyms.php rev 51
   ts.taxonID=taxon_search -- line 284
   AND(
    ts2.synID=ts.taxonID -- line 324 
    AND(
         ( IF(ts.basID IS NULL, (ts2.basID=ts.taxonID), ( (ts2.basID IS NULL OR ts2.basID=ts.taxonID) AND ts2.taxonID= ts.basID ) ) ) -- query 325-329
      OR ( IF(ts.basID IS NULL, (ts2.basID IS NULL),    ( (ts2.basID IS NULL OR ts2.basID=ts.taxonID) AND ts2.taxonID<>ts.basID ) ) ) -- query 343-347
    )
   )
   AND(
       ( ts3.synID=ts.taxonID AND ts3.basID=ts2.taxonID ) -- echo: 336-358/338-340  (query: 324/336/354)
    OR ( ts3.taxonID=ts2.taxonID ) -- echo: 332-335/350-353
   );
   
 DECLARE cur_taxonids CURSOR FOR 
  SELECT SUBSTR(taxonids.AcceptedTaxonID,2) AS 'AcceptedTaxonID' FROM  herbar_view.view_sp2000_tmp_AcceptedTaxonID taxonids;
 
 DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

 OPEN cur_taxonids;
 loop_taxonids: LOOP
  
  FETCH cur_taxonids INTO taxon_search;
  IF done THEN
   LEAVE loop_taxonids;
  END IF;
  
  loop_taxsynloop: LOOP
   
   OPEN cur_taxsyn;
   loop_taxsyn: LOOP
    
    FETCH cur_taxsyn INTO AcceptedTaxonID,NEXTSYNID,SYNONYMID;
    
    IF done THEN
     LEAVE loop_taxsyn;
    END IF;

    INSERT INTO herbar_view.view_sp2000_tmp_tabl_synonyms_normalized (AcceptedTaxonID,SynonymID)
     VALUES (AcceptedTaxonID,SYNONYMID);
   
   END LOOP loop_taxsyn;
   CLOSE cur_taxsyn;
  
   IF NEXTSYNID IS NOT NULL THEN
    SET taxon_search=NEXTSYNID;
   ELSE
    LEAVE loop_taxsynloop;
   END IF;
   
   SET done = 0; 
  END LOOP loop_taxsynloop; 
  
  SET done = 0;
 END LOOP loop_taxonids;
 CLOSE cur_taxonids;
 
END$$

DELIMITER ;

-- ===========================================
-- CALL
-- do_synonym_normalizing
--
-- ===========================================
CALL do_synonym_normalizing;