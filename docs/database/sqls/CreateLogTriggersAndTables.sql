-- ===========================================
-- CreateLog
-- ===========================================

DELIMITER $$

-- CREATE PROCEDURE herbar_names.CreateLog(tabledb VARCHAR(40), tablename VARCHAR(40))
CREATE FUNCTION herbar_names.CreateLog (tabledb VARCHAR(40), tablename VARCHAR(40)) RETURNS TEXT DETERMINISTIC READS SQL DATA
BEGIN
 DECLARE done BOOLEAN DEFAULT 0;
 
 DECLARE tablename_log VARCHAR(40) DEFAULT "_log";
 DECLARE logdb VARCHAR(40) DEFAULT "herbar_names";
 
 DECLARE sqlstatement TEXT DEFAULT "";
 
 DECLARE pri_new,pri_old,pri_index,ins_trig,del_trig,upd_trig,fields_new,keys TEXT DEFAULT "";
 DECLARE s_null, s_default VARCHAR(20) DEFAULT "";
 
 DECLARE c_field, c_type, c_null, c_key, c_default, c_extra VARCHAR(20) DEFAULT "";
 DECLARE cur_tablecol CURSOR FOR 
  SELECT
   COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, COLUMN_DEFAULT, EXTRA
  FROM
   information_schema.COLUMNS
  WHERE
       TABLE_SCHEMA=tabledb
   AND TABLE_NAME=tablename;
 
 DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
 
 OPEN cur_tablecol;
 loop_tablecols: LOOP

  FETCH cur_tablecol INTO c_field, c_type, c_null, c_key, c_default, c_extra;
 
  IF done THEN
   LEAVE loop_tablecols;
  END IF;
  
  SET s_null="";
  IF c_null = 'NO' THEN
   SET s_null="NOT NULL";
  END IF;
  
  SET s_default="";
  IF c_default IS NOT NULL THEN
   SET s_default=CONCAT("DEFAULT '",c_default,"'");
  END IF;

  IF c_key ='PRI' THEN
   SET pri_new=CONCAT(pri_new,",\n `new_",c_field,"` ",c_type," ",s_default,"");
   SET pri_old=CONCAT(pri_old,",\n `old_",c_field,"` ",c_type," ",s_default,"");
   SET keys=CONCAT(keys,",\n KEY  `new_",c_field,"` (`",c_field,"`), KEY  `old_",c_field,"` (`",c_field,"`)");

   SET ins_trig=CONCAT(ins_trig,",\n `new_",c_field,"`=new.`",c_field,"`, `old_",c_field,"`=null");
   SET upd_trig=CONCAT(upd_trig,",\n `new_",c_field,"`=new.`",c_field,"`, `old_",c_field,"`=old.`",c_field,"`");
   SET del_trig=CONCAT(del_trig,",\n `new_",c_field,"`=null, `old_",c_field,"`=old.`",c_field,"`");
  ELSE
   SET fields_new=CONCAT(fields_new,",\n `",c_field,"` ",c_type," ",s_null," ",s_default,"");
   
   SET ins_trig=CONCAT(ins_trig,",\n `",c_field,"`=new.`",c_field,"`");
   SET upd_trig=CONCAT(upd_trig,",\n `",c_field,"`=new.`",c_field,"`");
   SET del_trig=CONCAT(del_trig,",\n `",c_field,"`=old.`",c_field,"`");
  END IF;
  
   
 END LOOP loop_tablecols;
 CLOSE cur_tablecol;
 
 SET tablename_log=CONCAT(tablename,tablename_log);
 SET pri_new=SUBSTRING(pri_new,2);
 SET ins_trig=SUBSTRING(ins_trig,2);
 SET del_trig=SUBSTRING(del_trig,2);
 SET upd_trig=SUBSTRING(upd_trig,2);
 SET fields_new=SUBSTRING(fields_new,1);
 -- SET keys=SUBSTRING(keys,1);
  
 SET @sqlstatement1a=CONCAT("DROP TABLE IF EXISTS `",logdb, "`.`",tablename_log,"`;");
 SET @sqlstatement1b=CONCAT("
CREATE TABLE `",logdb, "`.`",tablename_log,"` (
 `LOGID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,",
 pri_new,
 pri_old,
 fields_new,",
 `userid` VARCHAR(30) ,
 `action` TINYINT NOT NULL ,
 `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",
  keys,",
  KEY `timestamp` (`timestamp`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8;
");


 SET @sqlstatement2a=CONCAT("DROP TRIGGER /*!50032 IF EXISTS */  `", tabledb, "`.`",tablename,"_ai`;");
 SET @sqlstatement2b=CONCAT("
CREATE TRIGGER
 `", tabledb, "`.`",tablename,"_ai`
AFTER INSERT ON
 `", tabledb, "`.`",tablename,"`
FOR EACH ROW

BEGIN
INSERT INTO `", logdb, "`.`",tablename_log,"` SET
",
ins_trig,",
 action='0', timestamp=NULL,userid=USER();

END;
");

 SET @sqlstatement3a=CONCAT("DROP TRIGGER /*!50032 IF EXISTS */  `", tabledb, "`.`",tablename,"_au`");
 SET @sqlstatement3b=CONCAT(" 
CREATE TRIGGER
 `", tabledb, "`.`",tablename,"_au`
AFTER UPDATE ON
 `", tabledb, "`.`",tablename,"`
FOR EACH ROW

BEGIN
INSERT INTO `", logdb, "`.`",tablename_log,"` SET
",
upd_trig,",
 action='1', timestamp=null,userid=USER();

END;
");

 SET @sqlstatement4a=CONCAT("DROP TRIGGER /*!50032 IF EXISTS */  `", tabledb, "`.`",tablename,"_ad`");
 SET @sqlstatement4b=CONCAT(" 
CREATE TRIGGER
 `", tabledb, "`.`",tablename,"_ad`
AFTER DELETE ON
 `", tabledb, "`.`",tablename,"`
FOR EACH ROW

BEGIN
INSERT INTO `", logdb, "`.`",tablename_log,"` SET
",
del_trig,",
 action='2', timestamp=NULL,userid=USER();

END;
");
 /*
 PREPARE statement FROM @sqlstatement1a;
 EXECUTE statement;
 DEALLOCATE PREPARE statement; 
 PREPARE statement FROM @sqlstatement1b;
 EXECUTE statement;
 DEALLOCATE PREPARE statement; 
 
 -- http://forge.mysql.com/worklog/task.php?id=2871
 PREPARE statement FROM @sqlstatement2a;
 EXECUTE statement;
 DEALLOCATE PREPARE statement; 
 PREPARE statement FROM @sqlstatement2b;
 EXECUTE statement;
 DEALLOCATE PREPARE statement; 

 PREPARE statement FROM @sqlstatement3a;
 EXECUTE statement;
 DEALLOCATE PREPARE statement; 
 PREPARE statement FROM @sqlstatement3b;
 EXECUTE statement;
 DEALLOCATE PREPARE statement; 
 
 
 PREPARE statement FROM @sqlstatement4a;
 EXECUTE statement;
 DEALLOCATE PREPARE statement; 
 PREPARE statement FROM @sqlstatement4b;
 EXECUTE statement;
 DEALLOCATE PREPARE statement; 
 
 */
 SET @sqlstatement=CONCAT(
 "DELI","MITER $$
",@sqlstatement1a,"
$$
",@sqlstatement1b,"
$$
",@sqlstatement2a,"
$$
",@sqlstatement2b,"
$$
",@sqlstatement3a,"
$$
",@sqlstatement3b,"
$$
",@sqlstatement4a,"
$$
",@sqlstatement4b,"
");

 RETURN @sqlstatement;

END$$

DROP FUNCTION IF EXISTS herbar_names.CreateLog $$

-- DROP PROCEDURE IF EXISTS herbar_names.CreateLog$$
DELIMITER ;

-- ===========================================
-- CALL
-- ===========================================

SELECT herbar_names.CreateLog('herbar_names','tbl_name_applies_to');

-- CALL herbar_names.CreateLog('herbar_names','tbl_name_applies_to');