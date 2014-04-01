CREATE DEFINER=`root`@`localhost` FUNCTION `GetScientificName`(`p_taxonID` INT(11), `p_bAvoidHybridFormula` TINYINT(1) UNSIGNED) RETURNS varchar(255) CHARSET utf8
    READS SQL DATA
BEGIN
  RETURN GetScientificNameString( p_taxonID, p_bAvoidHybridFormula, 0 );
END
