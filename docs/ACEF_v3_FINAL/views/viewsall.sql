/*
SELECT 
 ReferenceID,

 COUNT(*)

FROM
view_sp2000_references

GROUP BY
 ReferenceID

HAVING
 COUNT(*)>1
 */

-- ===========================================
-- ready
-- view_sp2000_sourcedatabase
--
-- ===========================================
CREATE OR REPLACE
 ALGORITHM = UNDEFINED
 VIEW herbar_view.view_sp2000_sourcedatabase
 AS
 
SELECT
 m.source_name AS 'DatabaseFullName',
 m.source_code AS 'DatabaseShortName',
 m.source_version  AS 'DatabaseVersion',
 m.source_update AS 'ReleaseDate',
 mdb.supplier_person AS 'AuthorsEditors',
 'TaxonomicCoverage' AS 'TaxonomicCoverage',
 m.source_abbr_engl AS 'GroupNameInEnglish',
 mdb.description AS 'Abstract',
 mdb.supplier_organisation AS 'Organisation',
 mdb.supplier_url AS 'HomeURL',
 '' AS 'Coverage',
 '' AS 'Completeness',
 mdb.disclaimer AS 'Confidence',
 mdb.logo_url AS 'LogoFileName',
 mdb.supplier_person  AS 'ContactPerson'
 
FROM
 herbarinput.meta m
 LEFT JOIN  herbarinput.metadb mdb ON mdb.source_id_fk=m.source_id
;

-- ===========================================
-- ready
-- view_sp2000_acceptedspecies
--
-- ===========================================
CREATE OR REPLACE
 ALGORITHM = UNDEFINED
 VIEW herbar_view.view_sp2000_acceptedspecies
 AS

SELECT
 
 CONCAT('t',ts.taxonID) AS 'AcceptedTaxonID',
 
 'Plantae' AS 'Kingdom',
 'Magnoliophyta' AS 'Phylum',
 'Magnoliopsida' AS 'Class',
 'Magnoliales' AS 'Order',
 '' AS 'Superfamily',
 
 tf.family AS 'Family',
 tg.genus AS 'Genus',

 '' AS 'SubGenusName',
 te.epithet AS 'Species',
 
 ta.author AS 'AuthorString',

 '' AS 'GSDNameStatus',
 tts.status_sp2000 AS 'Sp2000NameStatus',
 
 'No' AS 'IsFossil',
 'terrestial' AS 'LifeZone',
 '' AS 'AdditionalData',
 
 sc.author AS 'LTSSpecialist',
 sc.date AS 'LTSDate',
 
 CONCAT('http://herbarium.botanik.univie.ac.at/annonaceae/listSynonyms.php?ID=',ts.taxonID) AS 'SpeciesURL',
 
 'GSDTaxonGUI' AS 'GSDTaxonGUI',
 'GSDNameGUI' AS 'GSDNameGUI'

FROM
 herbarinput.tbl_tax_species ts
 LEFT JOIN herbarinput.tmp_scrutiny_import sc ON sc.taxonID=ts.taxonID
 LEFT JOIN herbarinput.tbl_tax_rank ttr ON ttr.tax_rankID=ts.tax_rankID
 LEFT JOIN herbarinput.tbl_tax_status tts ON tts.statusID=ts.statusID
 
 LEFT JOIN herbarinput.tbl_tax_genera tg ON tg.genID=ts.genID
 LEFT JOIN herbarinput.tbl_tax_families tf ON tf.familyID=tg.familyID
 
 LEFT JOIN herbarinput.tbl_tax_authors ta ON ta.authorID=ts.authorID
 LEFT JOIN herbarinput.tbl_tax_epithets te ON te.epithetID=ts.speciesID
 

WHERE
     tg.familyID='30' --  tf.family='Annonaceae' 
 AND ts.statusID IN (96,93,97,103) -- tts.status_sp2000 IN ('accepted name','provisionally accepted name') -- 
 AND(
   ts.tax_rankID='1' OR ( ts.tax_rankID='7'  AND ts.speciesID IS NULL) -- ttr.rank='species' or ( genus and species = Null)
 )
;
 
-- ===========================================
-- ready
-- view_sp2000_acceptedinfraspecifictaxa
--
-- ===========================================
CREATE OR REPLACE
 ALGORITHM = UNDEFINED
 VIEW herbar_view.view_sp2000_acceptedinfraspecifictaxa
 AS

SELECT 
 CONCAT('t',ts.taxonID) AS 'AcceptedTaxonID',
 acc.AcceptedTaxonID AS 'ParentSpeciesID',

 CASE ts.tax_rankID
  WHEN 2 THEN te1.epithet
  WHEN 3 THEN te2.epithet
  WHEN 4 THEN te3.epithet
  WHEN 5 THEN te4.epithet
  ELSE te5.epithet
 END AS 'InfraSpeciesEpithet',
 
 CASE ts.tax_rankID
  WHEN 2 THEN ta1.author
  WHEN 3 THEN ta2.author
  WHEN 4 THEN ta3.author
  WHEN 5 THEN ta4.author
  ELSE ta5.author
 END AS 'InfraSpecificAuthorString',
 
 ttr.rank_abbr AS 'InfraSpecificMarker',
 '' AS 'GSDNameStatus',
 tts.status_sp2000 AS 'Sp2000NameStatus',

 'No' AS 'IsFossil',
 'terrestial' AS 'LifeZone',
 '' AS 'AdditionalData',

 sc.author AS 'LTSSpecialist',
 sc.date AS 'LTSDate',

 CONCAT('http://herbarium.botanik.univie.ac.at/annonaceae/listSynonyms.php?ID=',ts.taxonID) AS 'InfraSpeciesURL',
 
 'GSDTaxonGUI' AS 'GSDTaxonGUI',
 'GSDNameGUI' AS 'GSDNameGUI'

FROM
 herbar_view.view_sp2000_acceptedspecies acc
 LEFT JOIN herbarinput.tmp_scrutiny_import sc ON sc.taxonID=SUBSTR(acc.AcceptedTaxonID,2)
 LEFT JOIN herbarinput.tbl_tax_species tso ON tso.taxonID=sc.taxonID
 LEFT JOIN herbarinput.tbl_tax_species ts ON (ts.genID = tso.genID AND ts.speciesID=tso.speciesID)
 
 LEFT JOIN herbarinput.tbl_tax_rank ttr ON ttr.tax_rankID=ts.tax_rankID
 LEFT JOIN herbarinput.tbl_tax_status tts ON tts.statusID=ts.statusID
 
 LEFT JOIN herbarinput.tbl_tax_authors ta1 ON ta1.authorID=ts.subspecies_authorID
 LEFT JOIN herbarinput.tbl_tax_authors ta2 ON ta2.authorID=ts.variety_authorID
 LEFT JOIN herbarinput.tbl_tax_authors ta3 ON ta3.authorID=ts.subvariety_authorID
 LEFT JOIN herbarinput.tbl_tax_authors ta4 ON ta4.authorID=ts.forma_authorID
 LEFT JOIN herbarinput.tbl_tax_authors ta5 ON ta5.authorID=ts.subforma_authorID
 
 LEFT JOIN herbarinput.tbl_tax_epithets te1 ON te1.epithetID=ts.subspeciesID
 LEFT JOIN herbarinput.tbl_tax_epithets te2 ON te2.epithetID=ts.varietyID
 LEFT JOIN herbarinput.tbl_tax_epithets te3 ON te3.epithetID=ts.subvarietyID
 LEFT JOIN herbarinput.tbl_tax_epithets te4 ON te4.epithetID=ts.formaID
 LEFT JOIN herbarinput.tbl_tax_epithets te5 ON te5.epithetID=ts.subformaID
 
WHERE
     ts.statusID IN (96,93,97,103) -- tts.status_sp2000 IN ('accepted name','provisionally accepted name') -- 
 AND ts.tax_rankID IN (2,3,4,5,6) -- ttr.rank IN ('subspecies','variety','subvariety','forma','subforma')
 ;
 
-- ===========================================
-- 
-- ready
-- view_sp2000_tmp_AcceptedTaxonID
--
-- ===========================================
CREATE OR REPLACE
 ALGORITHM = UNDEFINED
 VIEW herbar_view.view_sp2000_tmp_AcceptedTaxonID
 AS

SELECT AcceptedTaxonID AS 'AcceptedTaxonID' FROM herbar_view.view_sp2000_acceptedspecies acc 
UNION ALL
SELECT AcceptedTaxonID AS 'AcceptedTaxonID' FROM herbar_view.view_sp2000_acceptedinfraspecifictaxa acc 
;

-- ===========================================
-- ready
-- view_sp2000_tmp_tabl_synonyms_normalized
--
-- ===========================================
DROP TABLE IF EXISTS herbar_view.view_sp2000_tmp_tabl_synonyms_normalized;
CREATE TABLE herbar_view.view_sp2000_tmp_tabl_synonyms_normalized(
AcceptedTaxonID INT NOT NULL ,
SynonymID INT NOT NULL 
) ENGINE = MYISAM ;

-- ===========================================
-- ready
-- view_sp2000_synonyms
--
-- ===========================================
CREATE OR REPLACE
 ALGORITHM = UNDEFINED
 VIEW herbar_view.view_sp2000_synonyms
 AS
SELECT 
 CONCAT('s',tsn.SynonymID) AS 'ID',
 CONCAT('t',tsn.AcceptedTaxonID) AS 'AcceptedTaxonID',
 tg.genus AS 'Genus',
 '' AS 'SubGenusName',
 te.epithet AS 'Species',
 
 ta.author AS 'AuthorString',
 
 CASE ts.tax_rankID
  WHEN 2 THEN te1.epithet
  WHEN 3 THEN te2.epithet
  WHEN 4 THEN te3.epithet
  WHEN 5 THEN te4.epithet
  ELSE te5.epithet
 END AS 'InfraSpecies',
 
 ttr.rank_abbr AS 'InfraSpecificMarker',
 
 CASE ts.tax_rankID
  WHEN 2 THEN ta1.author
  WHEN 3 THEN ta2.author
  WHEN 4 THEN ta3.author
  WHEN 5 THEN ta4.author
  ELSE ta5.author
 END AS 'InfraSpecificAuthorString',

 '' AS 'GSDNameStatus',
 tts.status_sp2000 AS 'Sp2000NameStatus',

 '' AS 'GSDNameGUI'
FROM
 herbar_view.view_sp2000_tmp_tabl_synonyms_normalized tsn
 LEFT JOIN herbarinput.tbl_tax_species ts ON ts.taxonID=tsn.AcceptedTaxonID
 LEFT JOIN herbarinput.tbl_tax_species tss ON tss.taxonID=tsn.SynonymID

 -- status, rank
 LEFT JOIN herbarinput.tbl_tax_status tts ON tts.statusID=tss.statusID
 LEFT JOIN herbarinput.tbl_tax_rank ttr ON ttr.tax_rankID=tss.tax_rankID
 
 -- genus
 LEFT JOIN herbarinput.tbl_tax_genera tg ON tg.genID=tss.genID
 LEFT JOIN herbarinput.tbl_tax_authors ta ON ta.authorID=tss.authorID
 LEFT JOIN herbarinput.tbl_tax_epithets te ON te.epithetID=tss.speciesID
  
 -- infraspecific
 LEFT JOIN herbarinput.tbl_tax_authors ta1 ON ta1.authorID=tss.subspecies_authorID
 LEFT JOIN herbarinput.tbl_tax_authors ta2 ON ta2.authorID=tss.variety_authorID
 LEFT JOIN herbarinput.tbl_tax_authors ta3 ON ta3.authorID=tss.subvariety_authorID
 LEFT JOIN herbarinput.tbl_tax_authors ta4 ON ta4.authorID=tss.forma_authorID
 LEFT JOIN herbarinput.tbl_tax_authors ta5 ON ta5.authorID=tss.subforma_authorID
 
 LEFT JOIN herbarinput.tbl_tax_epithets te1 ON te1.epithetID=tss.subspeciesID
 LEFT JOIN herbarinput.tbl_tax_epithets te2 ON te2.epithetID=tss.varietyID
 LEFT JOIN herbarinput.tbl_tax_epithets te3 ON te3.epithetID=tss.subvarietyID
 LEFT JOIN herbarinput.tbl_tax_epithets te4 ON te4.epithetID=tss.formaID
 LEFT JOIN herbarinput.tbl_tax_epithets te5 ON te5.epithetID=tss.subformaID
;

-- ===========================================
-- ready
-- view_sp2000_commonnames
--
-- ===========================================
CREATE OR REPLACE
 ALGORITHM = UNDEFINED
 VIEW herbar_view.view_sp2000_commonnames
 AS
SELECT
 taxonids.AcceptedTaxonID AS 'AcceptedTaxonID',
 co.common_name AS 'CommonName',
 '' AS 'Transliteration',
 lan.name AS 'Language',
 -- after second "(" between second and third "," is the country; no "," and "(" are allowed but this normed ones.
 SUBSTRING(geo.name,
   (LOCATE(',',geo.name,LOCATE('(',geo.name, LOCATE('(',geo.name)+1)+1)+2) ,
   (LOCATE(',',geo.name,LOCATE(',',geo.name,LOCATE('(',geo.name, LOCATE('(',geo.name)+1)+1)+1))
  -(LOCATE(',',geo.name,LOCATE('(',geo.name, LOCATE('(',geo.name)+1)+1)+2)
 ) AS 'Country',
 
 -- area is before first ","; no "," and "(" are allowed but this normed ones.
 CONCAT(
  SUBSTRING(geo.name,1,INSTR( geo.name,',')-1) ,
  ' (',geo.geonameId,' geoname.org)'
 ) AS 'Area',
  
 CONCAT('c',ap.reference_id) AS 'ReferenceID'
 
FROM
 herbar_view.view_sp2000_tmp_AcceptedTaxonID taxonids
 LEFT JOIN herbar_names.tbl_name_entities en ON en.entity_id=taxonids.AcceptedTaxonID
 CROSS JOIN herbar_names.tbl_name_applies_to ap ON ap.entity_id=en.entity_id
 
 LEFT JOIN herbar_names.tbl_name_names n ON n.name_id=ap.name_id
 LEFT JOIN herbar_names.tbl_name_commons co ON co.common_id=n.name_id
 LEFT JOIN herbar_names.tbl_name_languages lan ON lan.language_id=ap.language_id
 LEFT JOIN herbar_names.tbl_geonames_cache geo ON geo.geonameId=ap.geonameId
;

-- ===========================================
-- ready
-- view_sp2000_distribution
--
-- ===========================================
CREATE OR REPLACE
 ALGORITHM = UNDEFINED
 VIEW herbar_view.view_sp2000_distribution
 AS

SELECT
 taxonids.AcceptedTaxonID AS 'AcceptedTaxonID',
 gn.iso_alpha_2_code AS 'DistributionElement',
 'ISO2Alpha' AS 'StandardInUse',
 'native' AS 'DistributionStatus'
FROM
 herbar_view.view_sp2000_tmp_AcceptedTaxonID taxonids
 LEFT JOIN herbarinput.tbl_specimens sp ON sp.taxonID=taxonids.AcceptedTaxonID
 LEFT JOIN herbarinput.tbl_geo_nation gn ON gn.NationID = sp.NationID
WHERE
 gn.iso_alpha_2_code IS NOT NULL
;

-- ===========================================
-- ready
-- view_sp2000_tmp_references
--
-- ===========================================
CREATE OR REPLACE
 ALGORITHM = UNDEFINED
 VIEW herbar_view.view_sp2000_tmp_references
 AS
-- accepted TAXA
SELECT
 taxonids.AcceptedTaxonID  AS 'tmp_ID',
 'TaxAccRef'  AS 'tmp_type',
 taxonids.AcceptedTaxonID AS 'ReferenceID',
 ta.autor AS 'Authors',
 lit.jahr AS 'Year',
 lit.titel AS 'Title',
 lit.annotation AS 'Details'
 
FROM
 herbar_view.view_sp2000_tmp_AcceptedTaxonID taxonids
 LEFT JOIN herbarinput.tbl_tax_index tbli ON tbli.taxonID = SUBSTR(taxonids.AcceptedTaxonID,2) 
 LEFT JOIN herbarinput.tbl_lit lit  ON lit.citationID = tbli.citationID 
 LEFT JOIN herbarinput.tbl_lit_authors ta ON ta.autorID = lit.autorID 

UNION ALL
-- Synonyms
SELECT
 synonymids.ID  AS 'tmp_ID',
 'Nomenclatural reference'  AS 'tmp_type',
 synonymids.ID AS 'ReferenceID',
 ta.autor AS 'Authors',
 lit.jahr AS 'Year',
 lit.titel AS 'Title',
 lit.annotation AS 'Details'
 
FROM
 herbar_view.view_sp2000_synonyms synonymids
 LEFT JOIN herbarinput.tbl_tax_index tbli ON tbli.taxonID = synonymids.ID
 LEFT JOIN herbarinput.tbl_lit lit  ON lit.citationID = tbli.citationID 
 LEFT JOIN herbarinput.tbl_lit_authors ta ON ta.autorID = lit.autorID 

UNION ALL
-- CommonNames
SELECT
 cmnames.AcceptedTaxonID  AS 'tmp_ID',
 'Common Name Reference'  AS 'tmp_type',
 cmnames.ReferenceID AS 'ReferenceID',
 CASE
  WHEN lit.citationID IS NOT NULL THEN ta.autor
  WHEN ser.serviceID  IS NOT NULL THEN '-'
  WHEN per.personID  IS NOT NULL THEN CONCAT(pers.p_firstname,' ',pers.p_familyname)
  ELSE ''
 END AS 'Authors',
 
 CASE
  WHEN lit.citationID IS NOT NULL THEN  lit.jahr
  WHEN ser.serviceID  IS NOT NULL THEN '-'
  WHEN per.personID  IS NOT NULL THEN '-'
  ELSE ''
 END AS 'Year',
 
 CASE
  WHEN lit.citationID IS NOT NULL THEN lit.titel
  WHEN ser.serviceID  IS NOT NULL THEN serv.name
  WHEN per.personID  IS NOT NULL THEN pers.p_abbrev
  ELSE ''
 END AS 'Title',
 
 CASE
  WHEN lit.citationID IS NOT NULL THEN lit.annotation
  WHEN ser.serviceID  IS NOT NULL THEN serv.url_head
  WHEN per.personID  IS NOT NULL THEN CONCAT(pers.p_birthdate,' ',pers.p_birthplace,' ',pers.p_death,' ',pers.p_deathplace)
  ELSE ''
 END AS 'Details'
 
FROM
 herbar_view.view_sp2000_commonnames cmnames
 LEFT JOIN herbar_names.tbl_name_references ref ON ref.reference_id=cmnames.ReferenceID
 LEFT JOIN herbar_names.tbl_name_literature nlit ON nlit.literature_id=ref.reference_id
 LEFT JOIN herbar_names.tbl_name_persons per ON per.person_id=ref.reference_id
 LEFT JOIN herbar_names.tbl_name_webservices ser ON ser.webservice_id=ref.reference_id
 
 LEFT JOIN herbarinput.tbl_tax_index tbli ON tbli.taxonID = nlit.literature_id
 LEFT JOIN herbarinput.tbl_lit lit  ON lit.citationID = tbli.citationID 
 LEFT JOIN herbarinput.tbl_lit_authors ta ON ta.autorID = lit.autorID 
 
 LEFT JOIN herbarinput.tbl_person pers ON pers.person_ID=per.personID
 
 LEFT JOIN herbarinput.tbl_nom_service serv ON serv.serviceID=ser.serviceID
;

-- ===========================================
-- ready
-- view_sp2000_references
--
-- ===========================================
CREATE OR REPLACE
 ALGORITHM = UNDEFINED
 VIEW herbar_view.view_sp2000_references
 AS
SELECT
 ref.ReferenceID,
 ref.Year,
 ref.Title,
 ref.Details
 
FROM
 herbar_view.view_sp2000_tmp_references ref
;
 
 -- ===========================================
-- ready
-- view_sp2000_namereferenceslinks
--
-- ===========================================
CREATE OR REPLACE
 ALGORITHM = UNDEFINED
 VIEW herbar_view.view_sp2000_namereferenceslinks
 AS

SELECT
 ref.tmp_ID AS 'ID',
 ref.tmp_type AS 'Reference Type',
 ref.ReferenceID AS 'ReferenceID'
FROM
 herbar_view.view_sp2000_tmp_references ref
;

-- ===========================================
-- ready
-- do_synonym_normalizing
--
-- ===========================================
DROP PROCEDURE IF EXISTS do_synonym_normalizing;
DELIMITER $$

CREATE PROCEDURE do_synonym_normalizing()
BEGIN
 
 DECLARE taxon_search INT DEFAULT 48465 ; 
 DECLARE xx INT DEFAULT 0 ; 
 DECLARE AcceptedTaxonID,NEXTSYNID,SYNONYMID INT;
 DECLARE done BOOLEAN DEFAULT 0;
 
 DECLARE cur_taxsyn CURSOR FOR
  SELECT
   ts.taxonID AS 'AcceptedTaxonID',
   ts.synID AS 'NEXTSYNID',
   ts3.taxonID AS 'SYNONYMID'

  FROM
   herbarinput.tbl_tax_species ts
   CROSS JOIN herbarinput.tbl_tax_species ts2
   CROSS JOIN herbarinput.tbl_tax_species ts3
  WHERE
   -- Umsetzung aus lists2ynonyms.php rev 51
   ts.taxonID=taxon_search -- line 284
   AND(
    ts2.synID=ts.taxonID -- line 324 
    AND(
         ( IF(ts.basID IS NULL, (ts2.basID=ts.taxonID), ( (ts2.basID IS NULL OR ts2.basID=ts.taxonID) AND ts2.taxonID= ts.basID ) ) ) -- query 325-329
      OR ( IF(ts.basID IS NULL, (ts2.basID IS NULL),    ( (ts2.basID IS NULL OR ts2.basID=ts.taxonID) AND ts2.taxonID<>ts.basID ) ) ) -- query 343-347
    )
   )
   AND(
       ( ts3.synID=ts.taxonID AND ts3.basID=ts2.taxonID ) -- echo: 336-358/338-340  (query: 324/336/354)
    OR ( ts3.taxonID=ts2.taxonID ) -- echo: 332-335/350-353
   );
   
 DECLARE cur_taxonids CURSOR FOR 
  SELECT SUBSTR(taxonids.AcceptedTaxonID,2) AS 'AcceptedTaxonID' FROM  herbar_view.view_sp2000_tmp_AcceptedTaxonID taxonids;
 
 DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

 OPEN cur_taxonids;
 loop_taxonids: LOOP
  
  FETCH cur_taxonids INTO taxon_search;
  IF done THEN
   LEAVE loop_taxonids;
  END IF;
  
  loop_taxsynloop: LOOP
   
   OPEN cur_taxsyn;
   loop_taxsyn: LOOP
    
    FETCH cur_taxsyn INTO AcceptedTaxonID,NEXTSYNID,SYNONYMID;
    
    IF done THEN
     LEAVE loop_taxsyn;
    END IF;

    INSERT INTO herbar_view.view_sp2000_tmp_tabl_synonyms_normalized (AcceptedTaxonID,SynonymID)
     VALUES (AcceptedTaxonID,SYNONYMID);
   
   END LOOP loop_taxsyn;
   CLOSE cur_taxsyn;
  
   IF NEXTSYNID IS NOT NULL THEN
    SET taxon_search=NEXTSYNID;
   ELSE
    LEAVE loop_taxsynloop;
   END IF;
   
   SET done = 0; 
  END LOOP loop_taxsynloop; 
  
  SET done = 0;
 END LOOP loop_taxonids;
 CLOSE cur_taxonids;
 
END$$

DELIMITER ;

-- ===========================================
-- CALL
-- do_synonym_normalizing
--
-- ===========================================
CALL do_synonym_normalizing;
