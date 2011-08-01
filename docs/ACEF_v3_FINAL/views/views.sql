-- ===========================================
-- ready
-- view_sourcedatabase
--
-- ===========================================
CREATE OR REPLACE
 ALGORITHM = UNDEFINED
 VIEW `view_sourcedatabase`
 AS
 
SELECT
 m.source_name as 'DatabaseFullName',
 m.source_code as 'DatabaseShortName',
 m.source_version  as 'DatabaseVersion',
 m.source_update as 'ReleaseDate',
 mdb.supplier_person as 'AuthorsEditors',
 'TaxonomicCoverage' as 'TaxonomicCoverage',
 m.source_abbr_engl as 'GroupNameInEnglish',
 mdb.description as 'Abstract',
 mdb.supplier_organisation as 'Organisation',
 mdb.supplier_url as 'HomeURL',
 '' as 'Coverage',
 '' as 'Completeness',
 mdb.disclaimer as 'Confidence',
 mdb.logo_url as 'LogoFileName',
 mdb.supplier_person  as 'ContactPerson'
 
FROM
 herbarinput.meta m
 LEFT JOIN  herbarinput.metadb mdb on mdb.source_id_fk=m.source_id
;

-- ===========================================
-- ready
-- view_acceptedspecies
--
-- ===========================================
CREATE OR REPLACE
 ALGORITHM = UNDEFINED
 VIEW `view_acceptedspecies`
 AS

SELECT
 
 ts.taxonID AS 'AcceptedTaxonID',
 
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
 
 'LTSSpecialist' AS 'LTSSpecialist',
 'LTSDate' AS 'LTSDate',
 
 CONCAT('http://herbarium.botanik.univie.ac.at/annonaceae/listSynonyms.php?ID=',ts.taxonID) AS 'SpeciesURL',
 
 'GSDTaxonGUI' AS 'GSDTaxonGUI',
 'GSDNameGUI' AS 'GSDNameGUI'

FROM
 herbarinput.tbl_tax_species ts
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
   ts.tax_rankID='1' OR ( ts.tax_rankID='7'  and ts.speciesID is null) -- ttr.rank='species' or ( genus and species = Null)
 )

 
-- ===========================================
-- ready
-- view_acceptedinfraspecifictaxa
--
-- ===========================================
CREATE OR REPLACE
 ALGORITHM = UNDEFINED
 VIEW `view_acceptedinfraspecifictaxa`
 AS

SELECT 
 ts.taxonID AS 'AcceptedTaxonID',
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

 'LTSSpecialist' AS 'LTSSpecialist',
 'LTSDate' AS 'LTSDate',

 CONCAT('http://herbarium.botanik.univie.ac.at/annonaceae/listSynonyms.php?ID=',ts.taxonID) AS 'InfraSpeciesURL',
 
 'GSDTaxonGUI' AS 'GSDTaxonGUI',
 'GSDNameGUI' AS 'GSDNameGUI'

FROM
 herbarinput.view_acceptedspecies acc
 LEFT JOIN herbarinput.tbl_tax_species tso ON tso.taxonID=acc.AcceptedTaxonID
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
-- almost ready
-- view_synonyms
--
-- ===========================================
CREATE OR REPLACE
 ALGORITHM = UNDEFINED
 VIEW `view_synonyms`
 AS
SELECT 
 '' AS 'ID',
 taxonids.AcceptedTaxonID AS 'AcceptedTaxonID',
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
 
 '' AS 'InfraSpecificMarker',
 
 CASE ts.tax_rankID
  WHEN 2 THEN ta1.author
  WHEN 3 THEN ta2.author
  WHEN 4 THEN ta3.author
  WHEN 5 THEN ta4.author
  ELSE ta5.author
 END AS 'InfraSpecificAuthorString',

 '' AS 'GSDNameStatus',
 tts.status_sp2000 AS 'Sp2000NameStatus',

 'GSDNameGUI' AS 'GSDNameGUI'
FROM
 (
  SELECT AcceptedTaxonID FROM herbarinput.view_acceptedspecies acc 
  UNION ALL
  SELECT AcceptedTaxonID FROM herbarinput.view_acceptedinfraspecifictaxa acc 
 ) AS taxonids 
 LEFT JOIN herbarinput.tbl_tax_species tso ON tso.taxonID=taxonids.AcceptedTaxonID
 CROSS JOIN herbarinput.tbl_tax_species ts
 CROSS JOIN herbarinput.tbl_tax_species ts2

 -- status, rank
 LEFT JOIN herbarinput.tbl_tax_status tts ON tts.statusID=ts2.statusID
 LEFT JOIN herbarinput.tbl_tax_rank ttr ON ttr.tax_rankID=ts2.tax_rankID
 
 -- genus
 LEFT JOIN herbarinput.tbl_tax_genera tg ON tg.genID=ts2.genID
 LEFT JOIN herbarinput.tbl_tax_authors ta ON ta.authorID=ts2.authorID
 LEFT JOIN herbarinput.tbl_tax_epithets te ON te.epithetID=ts2.speciesID
  
 -- infraspecific
 LEFT JOIN herbarinput.tbl_tax_authors ta1 ON ta1.authorID=ts2.subspecies_authorID
 LEFT JOIN herbarinput.tbl_tax_authors ta2 ON ta2.authorID=ts2.variety_authorID
 LEFT JOIN herbarinput.tbl_tax_authors ta3 ON ta3.authorID=ts2.subvariety_authorID
 LEFT JOIN herbarinput.tbl_tax_authors ta4 ON ta4.authorID=ts2.forma_authorID
 LEFT JOIN herbarinput.tbl_tax_authors ta5 ON ta5.authorID=ts2.subforma_authorID
 
 LEFT JOIN herbarinput.tbl_tax_epithets te1 ON te1.epithetID=ts2.subspeciesID
 LEFT JOIN herbarinput.tbl_tax_epithets te2 ON te2.epithetID=ts2.varietyID
 LEFT JOIN herbarinput.tbl_tax_epithets te3 ON te3.epithetID=ts2.subvarietyID
 LEFT JOIN herbarinput.tbl_tax_epithets te4 ON te4.epithetID=ts2.formaID
 LEFT JOIN herbarinput.tbl_tax_epithets te5 ON te5.epithetID=ts2.subformaID
 
WHERE

-- Umsetzung aus listsynonyms.php
(
 ts.synID=tso.taxonID
 AND(
      ( IF(tso.basID IS NULL, (ts.basID=tso.taxonID), ( (ts.basID IS NULL OR ts.basID=tso.taxonID) AND ts.taxonID= tso.basID ) ) )
   OR ( IF(tso.basID IS NULL, (ts.basID IS NULL),     ( (ts.basID IS NULL OR ts.basID=tso.taxonID) AND ts.taxonID<>tso.basID ) ) )
 )
)
AND(
    ( ts2.synID=tso.taxonID AND ts2.basID=ts.taxonID )
 OR ( ts2.taxonID=ts.taxonID )
)

AND tso.taxonID=11329

-- ts2 has correct data!
-- todo: enable recursion to this Rows, View can do loops, next tso.taxonID = ts.synID#
-- unique id??
;

-- ===========================================
-- ready
-- view_commonnames
--
-- ===========================================
CREATE OR REPLACE
 ALGORITHM = UNDEFINED
 VIEW `view_commonnames`
 AS
SELECT
 taxonids.AcceptedTaxonID AS 'AcceptedTaxonID',
 co.common_name AS 'CommonName',
 '' AS 'Transliteration',
 lan.name AS 'Language',
 '' AS 'Country',
 geo.name AS 'Area',
 ap.reference_id AS 'ReferenceID'
/*
 CASE
  WHEN lit.citationID is NOT NULL THEN CONCAT('l:',lit.citationID)
  WHEN ser.serviceID  is not null THEN CONCAT('s:',ser.serviceID)
  WHEN pers.personID  is not null THEN CONCAT('p:',pers.personID)
  ELSE ''
 END as 'ReferenceID'
 */
FROM
 (
  SELECT AcceptedTaxonID FROM herbarinput.view_acceptedspecies acc 
  UNION ALL
  SELECT AcceptedTaxonID FROM herbarinput.view_acceptedinfraspecifictaxa acc 
 ) AS taxonids 
 CROSS JOIN names.tbl_name_applies_to ap ON ap.entity_id=taxonids.AcceptedTaxonID
 
 LEFT JOIN names.tbl_name_commons co ON co.common_id=ap.name_id
 LEFT JOIN names.tbl_name_languages lan ON lan.language_id = ap.language_id
 LEFT JOIN names.tbl_geonames_cache geo ON geo.geonameId= ap.geonameId

 /*
 LEFT JOIN names.tbl_name_literature lit ON lit.literature_id=ap.reference_id
 LEFT JOIN names.tbl_name_person pers ON pers.person_id=ap.reference_id
 LEFT JOIN names.tbl_name_webservice ser ON ser.webservice_id=ap.reference_id
*/

-- ===========================================
-- ready
-- view_distribution
--
-- ===========================================
CREATE OR REPLACE
 ALGORITHM = UNDEFINED
 VIEW `view_distribution`
 AS

SELECT
 taxonids.AcceptedTaxonID AS 'AcceptedTaxonID',
 gn.iso_alpha_2_code AS 'DistributionElement',
 'ISO2Alpha' AS 'StandardInUse',
 'native' AS 'DistributionStatus'
FROM
 (
  SELECT AcceptedTaxonID FROM herbarinput.view_acceptedspecies acc 
  UNION ALL
  SELECT AcceptedTaxonID FROM herbarinput.view_acceptedinfraspecifictaxa
 ) AS taxonids
 LEFT JOIN tbl_specimens sp ON sp.taxonID=taxonids.AcceptedTaxonID
 LEFT JOIN tbl_geo_nation gn ON gn.NationID = sp.NationID
WHERE
 gn.iso_alpha_2_code IS NOT NULL

-- ===========================================
-- todo
-- view_namereferenceslinks
--
-- ===========================================
CREATE OR REPLACE
 ALGORITHM = UNDEFINED
 VIEW `view_namereferenceslinks`
 AS

SELECT
 as.AcceptedTaxonID as 'ID',
 'TaxAccRef' as 'Reference Type',
 'ReferenceID' as 'ReferenceID'
 
FROM
 view_acceptedspecies as,
 tbl_tax_index ti,
 tbl_lit lit
 
WHERE
     ti.citationID=as.taxonID
 AND lit.citationID=ti.citationID

 
WHERE
UNION ALL
SELECT
 .AcceptedTaxonID as 'ID',
 'Reference Type' as 'Reference Type',
 'ReferenceID' as 'ReferenceID'
 
FROM
 tbl_tax_species sp
 
WHERE
 
 
;

-- ===========================================
-- todo
-- view_references
--
-- ===========================================
CREATE OR REPLACE
 ALGORITHM = UNDEFINED
 VIEW `view_eferences`
 AS

SELECT
 tl.citationID as 'ReferenceID',
 ta.autor as 'Authors',
 tl.jahr as 'Year',
 tl.titel as 'Title',
 tl.annotation as 'Details'
 
FROM
 tbl_lit tl
 LEFT JOIN tbl_lit_authors ta ON ta.autorID = tl.autorID 
 
 ;

 
 -- ===========================================
-- todo
-- recursion via stored procedure??
--
-- ===========================================
 CREATE PROCEDURE synonymids()
BEGIN
 DECLARE done BOOLEAN DEFAULT 0;
 DECLARE CONTINUE HANDLER FOR SQLSTATE '02000' SET done=1;
   
 DECLARE taxon_search INT DEFAULT 48465 ; 
 DECLARE taxonID,synID INT;

 DECLARE taxsyn_cursor CURSOR
  FOR
SELECT 
 ts2.taxonID,
 ts2.synID
FROM
  herbarinput.tbl_tax_species tso
 CROSS JOIN herbarinput.tbl_tax_species ts
 CROSS JOIN herbarinput.tbl_tax_species ts2
WHERE

-- Umsetzung aus listsynonyms.php
(
 ts.synID=tso.taxonID
 AND(
      ( IF(tso.basID IS NULL, (ts.basID=tso.taxonID), ( (ts.basID IS NULL OR ts.basID=tso.taxonID) AND ts.taxonID= tso.basID ) ) )
   OR ( IF(tso.basID IS NULL, (ts.basID IS NULL),     ( (ts.basID IS NULL OR ts.basID=tso.taxonID) AND ts.taxonID<>tso.basID ) ) )
 )
)
AND(
    ( ts2.synID=tso.taxonID AND ts2.basID=ts.taxonID )
 OR ( ts2.taxonID=ts.taxonID )
)
AND tso.taxonID=taxon_search;

/*IF ts2.synID IS NOT NULL THEN
   taxon_search=ts2.synID;
  ELSE
   LEAVE taxsyn_loop;
  END IF;*/
  
 OPEN taxsyn_cursor;

 taxsyn_loop: LOOP
  FETCH taxsyn_cursor INTO taxonID,synID;
  IF done THEN
   LEAVE taxsyn_loop;
  END IF;
  
  
 END LOOP taxsyn_loop;

 CLOSE taxsyn_cursor;
 
END;
