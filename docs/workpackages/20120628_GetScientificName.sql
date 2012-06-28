CREATE DEFINER=`root`@`localhost` FUNCTION `_buildScientificName`(`p_taxonID` INT(11)) RETURNS varchar(255) CHARSET utf8
    READS SQL DATA
BEGIN
  DECLARE v_genus varchar(100);
  DECLARE v_DallaTorreIDs int(11);
  DECLARE v_family varchar(50);
  DECLARE v_category varchar(2);
  DECLARE v_author_g varchar(255);
  DECLARE v_epithet varchar(50);
  DECLARE v_author varchar(50);
  DECLARE v_epithet1 varchar(50);
  DECLARE v_author1 varchar(50);
  DECLARE v_epithet2 varchar(50);
  DECLARE v_author2 varchar(50);
  DECLARE v_epithet3 varchar(50);
  DECLARE v_author3 varchar(50);
  DECLARE v_epithet4 varchar(50);
  DECLARE v_author4 varchar(50);
  DECLARE v_epithet5 varchar(50);
  DECLARE v_author5 varchar(50);
  DECLARE v_rank_abbr varchar(255);
  DECLARE v_TaxonNameString varchar(255) default NULL;

  SELECT
    vt.`genus`, vt.`DallaTorreIDs`, vt.`family`, vt.`author_g`, vt.`epithet`, vt.`author`,
    vt.`epithet1`, vt.`author1`,vt.`epithet2`, vt.`author2`, vt.`epithet3`, vt.`author3`,
    vt.`epithet4`, vt.`author4`, vt.`epithet5`, vt.`author5`, vt.`rank_abbr`
  INTO
    v_genus, v_DallaTorreIDs, v_family, v_author_g, v_epithet, v_author, v_epithet1, v_author1,
    v_epithet2, v_author2, v_epithet3, v_author3, v_epithet4, v_author4, v_epithet5, v_author5,
    v_rank_abbr
  FROM
    `view_taxon` vt
  WHERE
    vt.`taxonID` = p_taxonID
  LIMIT 1;

  -- Genus only
  IF
    ( v_epithet IS NULL AND v_epithet1 IS NULL AND v_epithet2 IS NULL
    AND v_epithet3 IS NULL AND v_epithet4 IS NULL AND v_epithet5 IS NULL )
  THEN
    SET v_TaxonNameString = CONCAT( v_genus, " ", v_author_g );
  ELSE
    -- Create taxon name construct
    IF v_epithet IS NOT NULL THEN
      SET v_TaxonNameString = CONCAT( v_genus, " ", v_epithet, " ", v_author );
    END IF;
    IF v_epithet1 IS NOT NULL THEN
      IF v_author1 IS NULL AND v_epithet1 = v_epithet THEN
        SET v_author1 = v_author;
      END IF;
      SET v_TaxonNameString = CONCAT( v_genus, " ", v_epithet, " ", v_rank_abbr, " ", v_epithet1, " ", v_author1 );
    END IF;
    IF v_epithet2 IS NOT NULL THEN
      IF v_author2 IS NULL AND v_epithet2 = v_epithet THEN
        SET v_author2 = v_author;
      END IF;
      SET v_TaxonNameString = CONCAT( v_genus, " ", v_epithet, " ", v_rank_abbr, " ", v_epithet2, " ", v_author2 );
    END IF;
    IF v_epithet3 IS NOT NULL THEN
      IF v_author3 IS NULL AND v_epithet3 = v_epithet THEN
        SET v_author3 = v_author;
      END IF;
      SET v_TaxonNameString = CONCAT( v_genus, " ", v_epithet, " ", v_rank_abbr, " ", v_epithet3, " ", v_author3 );
    END IF;
    IF v_epithet4 IS NOT NULL THEN
      IF v_author4 IS NULL AND v_epithet4 = v_epithet THEN
        SET v_author4 = v_author;
      END IF;
      SET v_TaxonNameString = CONCAT( v_genus, " ", v_epithet, " ", v_rank_abbr, " ", v_epithet4, " ", v_author4 );
    END IF;
    IF v_epithet5 IS NOT NULL THEN
      IF v_author5 IS NULL AND v_epithet5 = v_epithet THEN
        SET v_author5 = v_author;
      END IF;
      SET v_TaxonNameString = CONCAT( v_genus, " ", v_epithet, " ", v_rank_abbr, " ", v_epithet5, " ", v_author5 );
    END IF;
  END IF;

  RETURN v_TaxonNameString;
END
