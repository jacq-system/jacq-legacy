USE herbar_view

DELIMITER $$

CREATE DEFINER=`root`@`localhost` FUNCTION `_buildScientificName`(`p_taxonID` INT(11)) RETURNS text CHARSET utf8
    READS SQL DATA
BEGIN
  DECLARE v_scientificName TEXT default NULL;
  DECLARE v_author TEXT default NULL;
  
  CALL _buildScientificNameComponents(p_taxonID, v_scientificName, v_author);
  
  RETURN CONCAT_WS( ' ', v_scientificName, v_author );
END
