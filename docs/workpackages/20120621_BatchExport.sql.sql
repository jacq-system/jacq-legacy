CREATE DEFINER=`root`@`localhost` FUNCTION `GetPictureName`(`p_specimen_ID` INT) RETURNS text CHARSET utf8
    NO SQL
BEGIN
	DECLARE v_coll_short_prj varchar(12) DEFAULT '';
        DECLARE v_HerbNummer varchar(25) DEFAULT '';
        DECLARE v_HerbNummerNrDigits tinyint(4) DEFAULT 0;
        DECLARE v_filename varchar(255) DEFAULT NULL;
        
        SELECT
        s.`HerbNummer`, mc.`coll_short_prj`, id.`HerbNummerNrDigits`
        INTO
        v_HerbNummer, v_coll_short_prj, v_HerbNummerNrDigits
        FROM
        `tbl_specimens` s
        LEFT JOIN
        `tbl_management_collections` mc
        ON
        mc.`collectionID` = s.`collectionID`
        LEFT JOIN
        `tbl_img_definition` id
        ON
        id.`source_id_fk` = mc.`source_id`
        WHERE
        s.`specimen_ID` = p_specimen_ID;
        
        SET v_HerbNummer = REPLACE(v_HerbNummer, '-', '');
        SET v_filename = CONCAT(v_coll_short_prj, '_', LPAD( v_HerbNummer, v_HerbNummerNrDigits, '0' ));
        
        RETURN v_filename;
END
