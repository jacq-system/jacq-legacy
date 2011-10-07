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
 CASE
  WHEN m.source_id=7  THEN 'Annonaceae'
  WHEN m.source_id=25 THEN 'Chenopodiaceae'
  WHEN m.source_id=26 THEN 'Ebenaceae'
  ELSE ''
 END AS 'familyPre',
 
 m.source_name AS 'DatabaseFullName',
 m.source_code AS 'DatabaseShortName',
 m.source_version  AS 'DatabaseVersion',
 m.source_update AS 'ReleaseDate',
 mdb.supplier_person AS 'AuthorsEditors',
 '' AS 'TaxonomicCoverage',
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
WHERE
  m.source_id in('7','25','26')
;
/*
-- ===========================================
-- ready
-- view_sp2000_sourcedatabase ausführlich....
--
-- ===========================================
CREATE OR REPLACE
 ALGORITHM = UNDEFINED
 VIEW herbar_view.view_sp2000_sourcedatabase
 AS
SELECT DISTINCT 
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
 herbar_view.view_sp2000_tmp_AcceptedTaxonID taxonids
 LEFT JOIN herbarinput.tbl_tax_species ts ON ts.taxonID=SUBSTR(taxonids.AcceptedTaxonID,2)

 LEFT JOIN herbarinput.tbl_tax_rank ttr ON ttr.tax_rankID=ts.tax_rankID
 LEFT JOIN herbarinput.tbl_tax_status tts ON tts.statusID=ts.statusID
 
 LEFT JOIN herbarinput.tbl_tax_genera tg ON tg.genID=ts.genID
 LEFT JOIN herbarinput.tbl_tax_families tf ON tf.familyID=tg.familyID

 LEFT JOIN herbarinput.tbl_specimens sp ON sp.taxonID = ts.taxonID
 LEFT JOIN herbarinput.tbl_management_collections mg ON mg.collectionID=sp.collectionID
 
 LEFT JOIN herbarinput.meta m ON m.source_id=mg.source_id
 LEFT JOIN herbarinput.metadb mdb ON mdb.source_id_fk=m.source_id
 
WHERE
     tg.familyID='30' --  tf.family='Annonaceae' 
 AND ts.statusID IN (96,93,97,103) -- tts.status_sp2000 IN ('accepted name','provisionally accepted name') -- 
 AND(
   ts.tax_rankID='1' OR ( ts.tax_rankID='7'  AND ts.speciesID IS NULL) -- ttr.rank='species' or ( genus and species = Null)
 )
;
*/
 
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
 tf.family AS 'familyPre',
 CONCAT('t',ts.taxonID) AS 'AcceptedTaxonID',
 
 'Plantae' AS 'Kingdom',
 'Magnoliophyta' AS 'Phylum',
 'Magnoliopsida' AS 'Class',
 'Magnoliales' AS 'Order',
 '' AS 'Superfamily',
 tax_syn_ID,
 tf.family AS 'Family',
 tg.genus AS 'Genus',

 '' AS 'SubGenusName',
 te.epithet AS 'Species',
 
 ta.author AS 'AuthorString',

 status_description AS 'GSDNameStatus',
 tts.status_sp2000 AS 'Sp2000NameStatus',
 
 'No' AS 'IsFossil',
 'terrestial' AS 'LifeZone',
 '' AS 'AdditionalData',
 
 aut.autor AS 'LTSSpecialist',
 lit.jahr AS 'LTSDate',
 
 CONCAT('http://herbarium.botanik.univie.ac.at/annonaceae/listSynonyms.php?ID=',ts.taxonID) AS 'SpeciesURL',
 
 '' AS 'GSDTaxonGUI',
 ts.taxonID AS 'GSDNameGUI'

FROM
 herbarinput.tbl_tax_synonymy syn
 LEFT JOIN herbarinput.tbl_tax_species ts ON ts.taxonID=syn.taxonID
 LEFT JOIN herbarinput.tbl_lit lit ON lit.citationID=syn.source_citationID
 LEFT JOIN herbarinput.tbl_lit_authors aut ON aut.autorID=lit.autorID
 
 LEFT JOIN herbarinput.tbl_tax_rank ttr ON ttr.tax_rankID=ts.tax_rankID
 LEFT JOIN herbarinput.tbl_tax_status tts ON tts.statusID=ts.statusID
 
 LEFT JOIN herbarinput.tbl_tax_genera tg ON tg.genID=ts.genID
 LEFT JOIN herbarinput.tbl_tax_families tf ON tf.familyID=tg.familyID
 
 LEFT JOIN herbarinput.tbl_tax_authors ta ON ta.authorID=ts.authorID
 LEFT JOIN herbarinput.tbl_tax_epithets te ON te.epithetID=ts.speciesID
 
WHERE
     ( syn.acc_taxon_ID is null OR syn.acc_taxon_ID=syn.taxonID ) -- accepted
 AND ( IFNULL(syn.source_citationID,0)=
 (
  SELECT
   IFNULL(syn2.source_citationID,0)
  FROM
   herbarinput.tbl_tax_synonymy syn2
   LEFT JOIN herbarinput.tbl_lit lit2 ON  lit2.citationID=syn2.source_citationID
  WHERE
       syn2.taxonID=syn.taxonID
   AND ( syn2.acc_taxon_ID is null or syn2.acc_taxon_ID=syn2.taxonID)
  ORDER BY
   CASE
    WHEN STR_TO_DATE(lit2.jahr,'%Y-%m-%d') is not null THEN CONCAT('a',STR_TO_DATE(jahr,'%Y-%m-%d')) 
    WHEN STR_TO_DATE(lit2.jahr,'%Y-%m') is not null THEN CONCAT('a',STR_TO_DATE(jahr,'%Y-%m'))
    WHEN STR_TO_DATE(lit2.jahr,'%Y') is not null THEN CONCAT('a',STR_TO_DATE(jahr,'%Y'))
    WHEN lit2.jahr='in prep.' THEN 'b'
    ELSE 'c' 
   END 
   DESC
  LIMIT
   1
 )
 )
 AND( ts.tax_rankID='1' OR ( ts.tax_rankID='7'  AND ts.speciesID IS NULL ) ) -- ttr.rank='species' or ( rank=genus and species = Null)
 AND  tg.familyID IN ('30','115','182') --  tf.family IN('Annonaceae','Chenopodiaceae','Ebenaceae')
GROUP BY
 tf.family, ts.taxonID, lit.jahr
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
 acc.family AS 'familyPre',
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
 status_description AS 'GSDNameStatus',
 tts.status_sp2000 AS 'Sp2000NameStatus',

 'No' AS 'IsFossil',
 'terrestial' AS 'LifeZone',
 '' AS 'AdditionalData',

 aut.autor AS 'LTSSpecialist',
 lit.jahr AS 'LTSDate',

 CONCAT('http://herbarium.botanik.univie.ac.at/annonaceae/listSynonyms.php?ID=',ts.taxonID) AS 'InfraSpeciesURL',
 
 'GSDTaxonGUI' AS 'GSDTaxonGUI',
 ts.taxonID AS 'GSDNameGUI'

FROM
 herbar_view.view_sp2000_acceptedspecies acc  -- only from accepted taxons
 LEFT JOIN herbarinput.tbl_tax_species tso ON tso.taxonID=SUBSTR(acc.AcceptedTaxonID,2)  -- accepted
 INNER JOIN herbarinput.tbl_tax_species ts ON (ts.genID = tso.genID AND ts.speciesID=tso.speciesID)
 INNER JOIN herbarinput.tbl_tax_synonymy syn ON ( syn.taxonID=ts.taxonID and (syn.acc_taxon_ID=0 OR syn.acc_taxon_ID=syn.taxonID) )
 
 LEFT JOIN herbarinput.tbl_lit lit ON lit.citationID=syn.source_citationID
 LEFT JOIN herbarinput.tbl_lit_authors aut ON aut.autorID=lit.autorID
 
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
 IFNULL(syn.source_citationID,0)=
 (
  SELECT
   IFNULL(syn2.source_citationID,0)
  FROM
   herbarinput.tbl_tax_synonymy syn2
   LEFT JOIN herbarinput.tbl_lit lit2 ON  lit2.citationID=syn2.source_citationID
  WHERE
       syn2.taxonID=syn.taxonID
   AND ( syn2.acc_taxon_ID is null or syn2.acc_taxon_ID=syn2.taxonID)
  ORDER BY
   CASE
    WHEN STR_TO_DATE(lit2.jahr,'%Y-%m-%d') is not null THEN CONCAT('a',STR_TO_DATE(jahr,'%Y-%m-%d')) 
    WHEN STR_TO_DATE(lit2.jahr,'%Y-%m') is not null THEN CONCAT('a',STR_TO_DATE(jahr,'%Y-%m'))
    WHEN STR_TO_DATE(lit2.jahr,'%Y') is not null THEN CONCAT('a',STR_TO_DATE(jahr,'%Y'))
    WHEN lit2.jahr='in prep.' THEN 'b'
    ELSE 'c' 
   END 
   DESC
  LIMIT
   1
 )
 AND ts.tax_rankID IN (2,3,4,5,6) -- ttr.rank IN ('subspecies','variety','subvariety','forma','subforma')
GROUP BY
 acc.family,ts.taxonID, lit.jahr
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

SELECT acc.familyPre AS "familyPre", acc.AcceptedTaxonID AS 'AcceptedTaxonID' FROM herbar_view.view_sp2000_acceptedspecies acc 
UNION ALL
SELECT acc.familyPre AS "familyPre", acc.AcceptedTaxonID AS 'AcceptedTaxonID' FROM herbar_view.view_sp2000_acceptedinfraspecifictaxa acc 
;


-- ===========================================
-- ready
-- view_sp2000_synonyms
-- new: for tbl_tax_synonymy
-- ===========================================
CREATE OR REPLACE
 ALGORITHM = UNDEFINED
 VIEW herbar_view.view_sp2000_synonyms
 AS
SELECT
 taxonids.familyPre AS 'familyPre',
 CONCAT('s',tss.taxonID) AS 'ID',
 CONCAT('t',ts.taxonID) AS 'AcceptedTaxonID',
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

 ts.taxonID AS 'GSDNameGUI'
FROM
 herbar_view.view_sp2000_tmp_AcceptedTaxonID taxonids
 INNER JOIN herbarinput.tbl_tax_synonymy syn ON syn.acc_taxon_ID=SUBSTR(taxonids.AcceptedTaxonID,2)
 LEFT JOIN herbarinput.tbl_tax_species tss ON tss.taxonID=syn.taxonID

 LEFT JOIN herbarinput.tbl_tax_species ts ON ts.taxonID=syn.acc_taxon_ID

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
WHERE
 IFNULL(syn.source_citationID,0)=
 (
  SELECT
   IFNULL(syn2.source_citationID,0)
  FROM
   herbarinput.tbl_tax_synonymy syn2
   LEFT JOIN herbarinput.tbl_lit lit2 ON  lit2.citationID=syn2.source_citationID
  WHERE
       syn2.taxonID=syn.taxonID
   AND syn2.acc_taxon_ID=syn.acc_taxon_ID
  ORDER BY
   CASE
    WHEN STR_TO_DATE(lit2.jahr,'%Y-%m-%d') is not null THEN CONCAT('a',STR_TO_DATE(jahr,'%Y-%m-%d')) 
    WHEN STR_TO_DATE(lit2.jahr,'%Y-%m') is not null THEN CONCAT('a',STR_TO_DATE(jahr,'%Y-%m'))
    WHEN STR_TO_DATE(lit2.jahr,'%Y') is not null THEN CONCAT('a',STR_TO_DATE(jahr,'%Y'))
    WHEN lit2.jahr='in prep.' THEN 'b'
    ELSE 'c' 
   END 
   DESC
  LIMIT
   1
 )
GROUP BY
 taxonids.familyPre, tss.taxonID
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
 taxonids.familyPre AS 'familyPre',
 taxonids.AcceptedTaxonID AS 'AcceptedTaxonID',
 co.common_name AS 'CommonName',
 '' AS 'Transliteration',
 lan.name AS 'Language',
 -- after second "(" between second and third "," is the country; no "," and "(" are allowed but this normed ones.
 SUBSTRING(geo.name,
   (LOCATE(',',geo.name,LOCATE(',',geo.name,LOCATE('(',geo.name, LOCATE('(',geo.name)+1)+1)+1)+1) ,
   (LOCATE(',',geo.name,LOCATE(',',geo.name,LOCATE(',',geo.name,LOCATE('(',geo.name, LOCATE('(',geo.name)+1)+1)+1)+1))
  -(LOCATE(',',geo.name,LOCATE(',',geo.name,LOCATE('(',geo.name, LOCATE('(',geo.name)+1)+1)+1)+1)
 ) AS 'Country',
 
 -- area is before first ","; no "," and "(" are allowed but this normed ones.
 CONCAT(
  SUBSTRING(geo.name,1,INSTR( geo.name,',')-1) ,
  ' (',geo.geonameId,' geoname.org)'
 ) AS 'Area',
  
 CONCAT('c',ap.reference_id) AS 'ReferenceID'
 
FROM
 herbar_view.view_sp2000_tmp_AcceptedTaxonID taxonids
 LEFT JOIN herbar_names.tbl_name_taxa tax ON tax.taxonID=SUBSTR(taxonids.AcceptedTaxonID,2)
 LEFT JOIN herbar_names.tbl_name_entities en ON en.entity_id=tax.taxon_id
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
 taxonids.familyPre AS 'familyPre',
 taxonids.AcceptedTaxonID AS 'AcceptedTaxonID',
 gn.iso_alpha_2_code AS 'DistributionElement',
 'ISO2Alpha' AS 'StandardInUse',
 'native' AS 'DistributionStatus'
FROM
 herbar_view.view_sp2000_tmp_AcceptedTaxonID taxonids
 LEFT JOIN herbarinput.tbl_specimens sp ON sp.taxonID=SUBSTR(taxonids.AcceptedTaxonID,2)
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
-- accepted TAXA (no shared references)
SELECT
 taxonids.familyPre AS 'familyPre',
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
GROUP BY taxonids.familyPre,taxonids.AcceptedTaxonID -- shouldn be needed, because tmp_ID is unique in taxonids. need to be checked: tbli, lit, ta

UNION ALL
-- Synonyms (no shared references)
SELECT
 synonymids.familyPre AS 'familyPre',
 synonymids.ID  AS 'tmp_ID',
 'Nomenclatural reference'  AS 'tmp_type',
 synonymids.ID AS 'ReferenceID',
 ta.autor AS 'Authors',
 lit.jahr AS 'Year',
 lit.titel AS 'Title',
 lit.annotation AS 'Details'
 
FROM
 herbar_view.view_sp2000_synonyms synonymids
 LEFT JOIN herbarinput.tbl_tax_index tbli ON tbli.taxonID = SUBSTR(synonymids.ID,2)  
 LEFT JOIN herbarinput.tbl_lit lit  ON lit.citationID = tbli.citationID 
 LEFT JOIN herbarinput.tbl_lit_authors ta ON ta.autorID = lit.autorID 
GROUP BY synonymids.familyPre, synonymids.ID -- shouldn be needed, because tmp_ID is unique in taxonids. need to be checked: tbli, lit, ta
 
UNION ALL
-- CommonNames (shared references)
SELECT
 cmnames.familyPre AS 'familyPre',
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
GROUP BY cmnames.familyPre, cmnames.ReferenceID -- is needed, because we are interested in shared references only.
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
 ref.familyPre AS 'familyPre',
 ref.ReferenceID as 'ReferenceID',
 ref.Authors as 'Authors',
 ref.Year as 'Year',
 ref.Title as 'Title',
 ref.Details as 'Details'
 
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
 ref.familyPre AS 'familyPre',
 ref.tmp_ID AS 'ID',
 ref.tmp_type AS 'Reference Type',
 ref.ReferenceID AS 'ReferenceID'
FROM
 herbar_view.view_sp2000_tmp_references ref
WHERE
 tmp_type in ('TaxAccRef','Nomenclatural reference')
;
