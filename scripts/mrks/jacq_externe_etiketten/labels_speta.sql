
-- filtert alle nummernzusätze, die mit einem buchstaben anfangen
UPDATE `speta_maintable` 
    SET `nummernzusatz_neu` = nummernzusatz
    WHERE nummernzusatz != '' 
    AND nummernzusatz_neu IS NULL
    AND nummernzusatz REGEXP '^[a-zA-Z]'
    ;
    
    
SELECT REGEXP_REPLACE(nummernzusatz, '[0-9]' , '') AS repl, * FROM `speta_maintable` 
WHERE `nummernzusatz` IS NOT NULL
AND nummernzusatz_neu IS NULL
GROUP BY REGEXP_REPLACE(nummernzusatz, '[0-9\s]' , '')


SELECT REPLACE(REPLACE(REPLACE(REGEXP_REPLACE(REPLACE(REGEXP_REPLACE(REGEXP_REPLACE(
    nummernzusatz, '[0-9]' , ''),'\s',''),' ',''),'\R',''),' ',''),'+',','),'–','-') AS repl,
    COUNT(REPLACE(REPLACE(REPLACE(REGEXP_REPLACE(REPLACE(REGEXP_REPLACE(REGEXP_REPLACE(
    nummernzusatz, '[0-9]' , ''),'\s',''),' ',''),'\R',''),' ',''),'+',','),'–','-')) AS count_repl,
    tab.* 
FROM `speta_maintable` AS tab
WHERE `nummernzusatz` IS NOT NULL
AND nummernzusatz != ''
AND nummernzusatz_neu IS NULL
GROUP BY 
REPLACE(REPLACE(REPLACE(REGEXP_REPLACE(REPLACE(REGEXP_REPLACE(REGEXP_REPLACE(
nummernzusatz, '[0-9]' , ''),'\s',''),' ',''),'\R',''),' ',''),'+',','),'–','-')
