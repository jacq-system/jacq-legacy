-- ===========================================
-- CreateLog
-- ===========================================

DELIMITER $$

CREATE PROCEDURE herbar_names.FollowLog(tabledb VARCHAR(40),tablename VARCHAR(40), IDS VARCHAR(40))
BEGIN
 DECLARE done, searchedfordelete BOOLEAN DEFAULT 0;
 DECLARE counter INTEGER DEFAULT 1;
 DECLARE sql1,sql2,sql3 ,sql4,logids_res TEXT DEFAULT "";
 DECLARE IDS2  VARCHAR(40);
 DECLARE keyval VARCHAR(20) DEFAULT "";
 DECLARE c_field, c_key VARCHAR(20) DEFAULT "";
 
 DECLARE tablename_log VARCHAR(40) DEFAULT "_log";
 DECLARE logdb VARCHAR(40) DEFAULT "herbar_names";
 
 DECLARE cur_tablecol CURSOR FOR 
  SELECT
   COLUMN_NAME, COLUMN_KEY
  FROM
   information_schema.COLUMNS
  WHERE
       TABLE_SCHEMA=tabledb
   AND TABLE_NAME=tablename;
 
 DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
  
 SET tablename_log=CONCAT(tablename,tablename_log);
 
  -- start main loop 
 WHILE IDS IS NOT NULL AND IDS<>"" DO
  SET IDS2=IDS;
  SET sql1="";
  SET sql2="";
  SET sql3="";
  SET sql4="";
 
  OPEN cur_tablecol;
  loop_tablecols: LOOP

   FETCH cur_tablecol INTO c_field, c_key;
 
   IF done THEN
    LEAVE loop_tablecols;
   END IF;
 
   IF c_key ='PRI' THEN
    SET keyval=SUBSTRING_INDEX(IDS2,',', 1);
    IF keyval<>"" THEN
     -- first search is for the deletion...
     IF searchedfordelete =0 THEN
      
      SET sql1=CONCAT(sql1, " and `old_",c_field,"`='",keyval,"' and `new_",c_field,"` is null");
      SET sql2=",`LOGID`"; -- dummy
      SET sql3=",',',''"; -- dummy
      
     ELSE
      SET sql1=CONCAT(sql1, " and `new_",c_field,"`='",keyval,"'");
      SET sql2=CONCAT(sql2, ",`new_",c_field,"`");
      SET sql3=CONCAT(sql3, ",',',`old_",c_field,"`");
     END IF;   
     SET IDS2=SUBSTRING(IDS2,CHAR_LENGTH(keyval)+2);
    END IF;  
   END IF;
   
  END LOOP loop_tablecols;
  CLOSE cur_tablecol;
  
  SET @logids="";
  SET @nextids="";
  SET sql1=SUBSTRING(sql1,5);
  SET sql2=SUBSTRING(sql2,2);
  SET sql3=SUBSTRING(sql3,6); 
  SET sql4=CONCAT( " SELECT GROUP_CONCAT(LOGID), CONCAT(",sql3,")  into @logids, @nextids FROM `",logdb,"`.`",tablename_log,"` WHERE ",sql1," GROUP BY ",sql2);
  
  -- INSERT into herbar_names.sqltest set `sql`=QUOTE(sql4); -- debug message
  SET @sqlstatement1a=sql4;
 
  PREPARE statement FROM @sqlstatement1a;
  EXECUTE statement;
  DEALLOCATE PREPARE statement; 

  SET logids_res=CONCAT(logids_res,',', @logids);
  
  IF searchedfordelete=0 THEN
   SET searchedfordelete=1;
   SET IDS=IDS;
  ELSE
   SET IDS=@nextids; 
  END IF;
   
  SET done = 0;
  
 END WHILE;

 SET sql1=SUBSTRING(logids_res,2);
 SET @sqlstatement1a=CONCAT("SELECT * FROM  `",logdb,"`.`",tablename_log,"` WHERE FIND_IN_SET(LOGID,'",logids_res,"') ORDER BY LOGID DESC;");
 
 PREPARE statement FROM @sqlstatement1a;
 EXECUTE statement;
 DEALLOCATE PREPARE statement; 
 
END$$

DROP PROCEDURE IF EXISTS herbar_names.FollowLog$$

DELIMITER ;

-- ===========================================
-- CALL
-- ===========================================

CALL herbar_names.FollowLog('herbar_names','tbl_name_applies_to','3382998,0,1,5,8,13,0');
