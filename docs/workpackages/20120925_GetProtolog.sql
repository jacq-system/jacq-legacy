CREATE DEFINER=`root`@`localhost` FUNCTION `GetProtolog`(`p_citationID` INT) RETURNS text CHARSET utf8
    NO SQL
BEGIN
  DECLARE v_protolog text;
  DECLARE v_subtitle varchar(250);
  DECLARE v_editor varchar(150);
  DECLARE v_author varchar(150);
  DECLARE v_periodical varchar(250);
  DECLARE v_vol varchar(20);
  DECLARE v_part varchar(50);
  DECLARE v_year varchar(50);
  DECLARE v_pp varchar(150);
  
  SELECT
    l.`suptitel`, le.`autor`, la.`autor`, lp.`periodical`,
    l.`vol`, l.`part`, l.`jahr`, l.`pp`
  INTO
    v_subtitle, v_editor, v_author, v_periodical,
    v_vol, v_part, v_year, v_pp
  FROM
    `herbarinput`.`tbl_lit` l
  LEFT JOIN
    `herbarinput`.`tbl_lit_authors` le ON le.`autorID` = l.`editorsID`
  LEFT JOIN
    `herbarinput`.`tbl_lit_authors` la ON la.`autorID` = l.`autorID`
  LEFT JOIN
    `herbarinput`.`tbl_lit_periodicals` lp ON lp.`periodicalID` = l.`periodicalID`
  WHERE
    l.`citationID` = p_citationID;
  
  SET v_protolog = CONCAT(v_author, " (", SUBSTRING(v_year, 1, 4), ")");
  IF LENGTH(v_subtitle) > 0 THEN
    SET v_protolog = CONCAT(v_protolog, " in ", v_editor, ": ", v_subtitle);
  END IF;
  IF LENGTH(v_periodical) > 0 THEN
    SET v_protolog = CONCAT(v_protolog, " ", v_periodical);
  END IF;
  IF LENGTH(v_vol) > 0 THEN
    SET v_protolog = CONCAT(v_protolog, " ", v_vol);
  END IF;
  IF LENGTH(v_part) > 0 THEN
    SET v_protolog = CONCAT(v_protolog, " (", v_part, ")");
  END IF;
  SET v_protolog = CONCAT(v_protolog, ": ", v_pp, ".");
  
  RETURN v_protolog;
END