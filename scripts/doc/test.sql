SELECT
 l1.language_id as 'language_id_1',
 l1.`iso639-6` as 'iso639-6',
 l1.name as 'name_1',

 l2.language_id as 'language_id_2',
 l2.`iso639-3` as 'iso639-3',
 l2.name as 'name_2',
 mdld(l1.name ,l2.name ,2,4) as mdld

FROM
 tbl_name_languages l1
 INNER JOIN tbl_name_languages l2  ON  ( l2.`iso639-3`=l1.`iso639-6` and l2.name<>l1.name)
order by
 mdld, l1.`iso639-6`, l2.`iso639-3`
