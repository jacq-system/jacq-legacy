DELIMITER $$

CREATE DEFINER=`root`@`localhost` 
    FUNCTION `GetScientificName`( p_taxonID INT(11), `p_bAvoidHybridFormula` TINYINT(1) UNSIGNED) 
   RETURNS text CHARSET utf8
    READS SQL DATA
    COMMENT 'legacy function for compatibility'
BEGIN
  DECLARE v_statusID int(11) default 0;
  DECLARE v_epithetID int(11) default NULL;
  DECLARE v_parent_1_ID int(11) default 0;
  DECLARE v_parent_2_ID int(11) default 0;
  DECLARE v_parent_3_ID int(11) default 0;
  DECLARE v_ScientificNameString TEXT default NULL;

  SELECT
    vt.`statusID`, vt.`epithetID`, tth.`parent_1_ID`, tth.`parent_2_ID`, tth.`parent_3_ID`
  INTO
    v_statusID, v_epithetID, v_parent_1_ID, v_parent_2_ID, v_parent_3_ID
  FROM
    `view_taxon` vt
  LEFT JOIN
    `herbarinput`.`tbl_tax_hybrids` tth
  ON
    tth.`taxon_ID_fk` = vt.`taxonID`
  WHERE
    vt.`taxonID` = p_taxonID
  LIMIT 1;

  -- Hybrid (also check if we always want binomials for hybrids instead of formula)
  -- 1. Check for hybrid
  -- 2. check if we always want the hybrid formula
  -- 3. check if we do not want the formula but the epithed is null
  -- 4. check if we want the hybrid formula but do not know it
  IF ( v_statusID = 1 &&
     (p_bAvoidHybridFormula = 0 ||
     (p_bAvoidHybridFormula = 1 && v_epithetID IS NULL )
     ) &&
     v_parent_1_ID IS NOT NULL
   )
  THEN
    SET v_ScientificNameString = CONCAT( _buildScientificName( v_parent_1_ID ), " x ", _buildScientificName( v_parent_2_ID ) );
    
    -- Check if a third parent exists
    IF( v_parent_3_ID != 0 )
    THEN
      SET v_ScientificNameString = CONCAT( v_ScientificNameString, " x ", _buildScientificName( v_parent_3_ID ) );
    END IF;

  -- Non-Hybrid
  ELSE
    SET v_ScientificNameString = _buildScientificName( p_taxonID );
  END IF;

  RETURN v_ScientificNameString;
END
