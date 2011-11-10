CREATE OR REPLACE
 ALGORITHM = UNDEFINED
 VIEW herbar_view.view_tbl_specimens
 AS

 SELECT
  sp.*,
  CASE
   WHEN sp.HerbNummer IS NULL THEN ''
   WHEN (NOT ( REPLACE(sp.HerbNummer, '-', '') REGEXP '^[0-9]{0,}(a{0,1}|b{0,1}|c{0,1})$' ) OR ( LOCATE('-',sp.HerbNummer)<>0 AND SUBSTRING_INDEX(sp.HerbNummer, '-', 2)<>sp.HerbNummer ) ) THEN 'error_fomat'
   WHEN LOCATE('-',sp.HerbNummer) THEN CONCAT(mc.coll_short_prj,'_',REPLACE(sp.HerbNummer,'-','')) 
   WHEN sp.collectionID=89 THEN IF( CHAR_LENGTH(sp.HerbNummer)>8,'error_JE', CONCAT(mc.coll_short_prj,'_',RIGHT(CONCAT('00000000',sp.HerbNummer),8)))
   WHEN mc.source_id=4 THEN IF( CHAR_LENGTH(sp.HerbNummer)>9,'error_W', CONCAT(mc.coll_short_prj,'_',RIGHT(CONCAT('000000000',sp.HerbNummer),9)))
   ELSE IF( CHAR_LENGTH(sp.HerbNummer)>7,'error_7', CONCAT(mc.coll_short_prj,'_',RIGHT(CONCAT('00000000',sp.HerbNummer),7)))
  END AS 'filename2'
 
 FROM
  herbarinput.tbl_specimens sp
  LEFT JOIN  herbarinput.tbl_management_collections mc ON mc.collectionID=sp.collectionID
 