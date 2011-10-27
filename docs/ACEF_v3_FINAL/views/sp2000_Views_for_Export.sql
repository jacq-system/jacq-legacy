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

 'accepted' AS 'GSDNameStatus',
 'accepted' AS 'Sp2000NameStatus',
 
 'No' AS 'IsFossil',
 'terrestial' AS 'LifeZone',
 '' AS 'AdditionalData',
 
 IFNULL(aut.autor,'') AS 'LTSSpecialist',
 IFNULL(lit.jahr,'') AS 'LTSDate',
 
 CONCAT('http://herbarium.botanik.univie.ac.at/annonaceae/listSynonyms.php?ID=',ts.taxonID) AS 'SpeciesURL',
 
 '' AS 'GSDTaxonGUI',
 ts.taxonID AS 'GSDNameGUI'

FROM
 herbarinput.tbl_tax_species ts
 
 -- left join last synonym entry.
 LEFT JOIN herbarinput.tbl_tax_synonymy syn  ON (
  syn.tax_syn_ID=IFNULL(
  (
   SELECT
    tax_syn_ID
   FROM
    herbarinput.tbl_tax_synonymy syn2
    LEFT JOIN herbarinput.tbl_lit lit2 ON  lit2.citationID=syn2.source_citationID
   WHERE
    syn2.taxonID=ts.taxonID
   ORDER BY
    CASE
     WHEN STR_TO_DATE(lit2.jahr,'%Y-%m-%d') IS NOT NULL THEN CONCAT('a',STR_TO_DATE(lit2.jahr,'%Y-%m-%d')) 
     WHEN STR_TO_DATE(lit2.jahr,'%Y-%m') IS NOT NULL THEN CONCAT('a',STR_TO_DATE(lit2.jahr,'%Y-%m'))
     WHEN STR_TO_DATE(lit2.jahr,'%Y') IS NOT NULL THEN CONCAT('a',STR_TO_DATE(lit2.jahr,'%Y'))
     WHEN lit2.jahr='in prep.' THEN 'b'
     ELSE 'c' 
    END
    DESC
   LIMIT
    1
  ),-1)
 )
 LEFT JOIN herbarinput.tbl_lit lit ON lit.citationID=syn.source_citationID
 LEFT JOIN herbarinput.tbl_lit_authors aut ON aut.autorID=lit.autorID
 
 LEFT JOIN herbarinput.tbl_tax_rank ttr ON ttr.tax_rankID=ts.tax_rankID
 LEFT JOIN herbarinput.tbl_tax_status tts ON tts.statusID=ts.statusID
 
 LEFT JOIN herbarinput.tbl_tax_genera tg ON tg.genID=ts.genID
 LEFT JOIN herbarinput.tbl_tax_families tf ON tf.familyID=tg.familyID
 
 LEFT JOIN herbarinput.tbl_tax_authors ta ON ta.authorID=ts.authorID
 LEFT JOIN herbarinput.tbl_tax_epithets te ON te.epithetID=ts.speciesID
 
WHERE
     ( ts.tax_rankID='1' OR ( ts.tax_rankID='7'  AND ts.speciesID IS NULL ) ) -- ttr.rank='species' or ( rank=genus and species = Null)
 AND ( tg.familyID IN ('30','115','182') ) --  tf.family IN('Annonaceae','Chenopodiaceae','Ebenaceae')
 -- where accepted
 AND ( syn.acc_taxon_ID IS NULL OR syn.acc_taxon_ID=syn.taxonID)
 AND ts.statusID<>2
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
 tf.family AS 'familyPre',
 CONCAT('t',ts.taxonID) AS 'AcceptedTaxonID',
 CONCAT('t',tso.taxonID) AS 'ParentSpeciesID',
 
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
 'accepted' AS 'GSDNameStatus',
 'accepted' AS 'Sp2000NameStatus',

 'No' AS 'IsFossil',
 'terrestial' AS 'LifeZone',
 '' AS 'AdditionalData',

 IFNULL(aut.autor,'') AS 'LTSSpecialist',
 IFNULL(lit.jahr,'') AS 'LTSDate',

 CONCAT('http://herbarium.botanik.univie.ac.at/annonaceae/listSynonyms.php?ID=',ts.taxonID) AS 'InfraSpeciesURL',
 
 'GSDTaxonGUI' AS 'GSDTaxonGUI',
 ts.taxonID AS 'GSDNameGUI'

FROM
 herbarinput.tbl_tax_species ts
 LEFT JOIN herbarinput.tbl_tax_species tso ON (
  tso.genID = ts.genID AND tso.speciesID=ts.speciesID
  AND tso.subspeciesID IS NULL AND tso.varietyID IS NULL
  AND tso.subvarietyID IS NULL AND tso.formaID IS NULL AND tso.subformaID IS NULL
 )
 LEFT JOIN herbarinput.tbl_tax_genera tgo ON tgo.genID=tso.genID

 -- left join last synonym entry.
 LEFT JOIN herbarinput.tbl_tax_synonymy syn  ON (
  syn.tax_syn_ID=IFNULL(
  (
   SELECT
    syn2.tax_syn_ID
   FROM
    herbarinput.tbl_tax_synonymy syn2
    LEFT JOIN herbarinput.tbl_lit lit2 ON  lit2.citationID=syn2.source_citationID
   WHERE
    syn2.taxonID=ts.taxonID
   ORDER BY
    CASE
     WHEN STR_TO_DATE(lit2.jahr,'%Y-%m-%d') IS NOT NULL THEN CONCAT('a',STR_TO_DATE(lit2.jahr,'%Y-%m-%d')) 
     WHEN STR_TO_DATE(lit2.jahr,'%Y-%m') IS NOT NULL THEN CONCAT('a',STR_TO_DATE(lit2.jahr,'%Y-%m'))
     WHEN STR_TO_DATE(lit2.jahr,'%Y') IS NOT NULL THEN CONCAT('a',STR_TO_DATE(lit2.jahr,'%Y'))
     WHEN lit2.jahr='in prep.' THEN 'b'
     ELSE 'c' 
    END
    DESC
   LIMIT
    1
  ),-1)
 )
 LEFT JOIN herbarinput.tbl_tax_genera tg ON tg.genID=ts.genID
 LEFT JOIN herbarinput.tbl_tax_families tf ON tf.familyID=tg.familyID
 
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
 -- next higher taxon is what we want
     ( tso.tax_rankID='1' OR ( tso.tax_rankID='7'  AND tso.speciesID IS NULL ) ) -- ttro.rank='species' or ( ttro.rank=genus and tso.speciesID = Null)
 AND ( tgo.familyID IN ('30','115','182') ) --  tfo.family IN('Annonaceae','Chenopodiaceae','Ebenaceae')
 -- and wanted infraspecies rank...
 AND ts.tax_rankID IN (2,3,4,5,6) -- ttr.rank IN ('subspecies','variety','subvariety','forma','subforma')
 -- and accepteds
 AND ( syn.acc_taxon_ID IS NULL OR syn.acc_taxon_ID=syn.taxonID)
 AND ts.statusID<>2
GROUP BY
 tf.family,ts.taxonID, lit.jahr
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
--  synonym for species
SELECT
 tfs.family 'familyPre',
 CONCAT('s',tss.taxonID) AS 'ID',
 CONCAT('t',syn.acc_taxon_ID) AS 'AcceptedTaxonID',
 tgs.genus AS 'Genus',
 '' AS 'SubGenusName',
 tes.epithet AS 'Species',
 
 tas.author AS 'AuthorString',
 
 CASE tss.tax_rankID
  WHEN 2 THEN tes1.epithet
  WHEN 3 THEN tes2.epithet
  WHEN 4 THEN tes3.epithet
  WHEN 5 THEN tes4.epithet
  ELSE tes5.epithet
 END AS 'InfraSpecies',
 
 CASE tss.tax_rankID
  WHEN 1 THEN NULL
  ELSE  ttrs.rank_abbr
 END AS 'InfraSpecificMarker',
 
 CASE tss.tax_rankID
  WHEN 2 THEN tas1.author
  WHEN 3 THEN tas2.author
  WHEN 4 THEN tas3.author
  WHEN 5 THEN tas4.author
  ELSE tas5.author
 END AS 'InfraSpecificAuthorString',

 'synonym' AS 'GSDNameStatus',
 'synonym' AS 'Sp2000NameStatus',

 tss.taxonID AS 'GSDNameGUI'
FROM
 herbarinput.tbl_tax_species tss
 LEFT JOIN herbarinput.tbl_tax_genera tgs ON tgs.genID=tss.genID
 LEFT JOIN herbarinput.tbl_tax_families tfs ON tfs.familyID=tgs.familyID
 
 -- left join last synonym entry.
 LEFT JOIN herbarinput.tbl_tax_synonymy syn  ON (
  -- synonym
 syn.tax_syn_ID=IFNULL(
  (
   SELECT
    tax_syn_ID
   FROM
    herbarinput.tbl_tax_synonymy syn2
    LEFT JOIN herbarinput.tbl_lit lit2 ON  lit2.citationID=syn2.source_citationID
   WHERE
    syn2.taxonID=tss.taxonID
   ORDER BY
    CASE
     WHEN STR_TO_DATE(lit2.jahr,'%Y-%m-%d') IS NOT NULL THEN CONCAT('a',STR_TO_DATE(lit2.jahr,'%Y-%m-%d')) 
     WHEN STR_TO_DATE(lit2.jahr,'%Y-%m') IS NOT NULL THEN CONCAT('a',STR_TO_DATE(lit2.jahr,'%Y-%m'))
     WHEN STR_TO_DATE(lit2.jahr,'%Y') IS NOT NULL THEN CONCAT('a',STR_TO_DATE(lit2.jahr,'%Y'))
     WHEN lit2.jahr='in prep.' THEN 'b'
     ELSE 'c' 
    END
    DESC
   LIMIT
    1
  ),-1)
 )

 -- status, rank
 LEFT JOIN herbarinput.tbl_tax_status tts ON tts.statusID=tss.statusID
 LEFT JOIN herbarinput.tbl_tax_rank ttrs ON ttrs.tax_rankID=tss.tax_rankID
 LEFT JOIN herbarinput.tbl_tax_authors tas ON tas.authorID=tss.authorID
 LEFT JOIN herbarinput.tbl_tax_epithets tes ON tes.epithetID=tss.speciesID
  
 -- infraspecific
 LEFT JOIN herbarinput.tbl_tax_authors tas1 ON tas1.authorID=tss.subspecies_authorID
 LEFT JOIN herbarinput.tbl_tax_authors tas2 ON tas2.authorID=tss.variety_authorID
 LEFT JOIN herbarinput.tbl_tax_authors tas3 ON tas3.authorID=tss.subvariety_authorID
 LEFT JOIN herbarinput.tbl_tax_authors tas4 ON tas4.authorID=tss.forma_authorID
 LEFT JOIN herbarinput.tbl_tax_authors tas5 ON tas5.authorID=tss.subforma_authorID
 
 LEFT JOIN herbarinput.tbl_tax_epithets tes1 ON tes1.epithetID=tss.subspeciesID
 LEFT JOIN herbarinput.tbl_tax_epithets tes2 ON tes2.epithetID=tss.varietyID
 LEFT JOIN herbarinput.tbl_tax_epithets tes3 ON tes3.epithetID=tss.subvarietyID
 LEFT JOIN herbarinput.tbl_tax_epithets tes4 ON tes4.epithetID=tss.formaID
 LEFT JOIN herbarinput.tbl_tax_epithets tes5 ON tes5.epithetID=tss.subformaID
WHERE
   
     ( tss.tax_rankID='1' OR ( tss.tax_rankID='7'  AND tss.speciesID IS NULL ) ) -- ttrs.rank='species' or ( rank=genus and species = Null)
 AND ( tgs.familyID IN ('30','115','182') ) --  tf.family IN('Annonaceae','Chenopodiaceae','Ebenaceae')
 AND ( syn.acc_taxon_ID IS NOT NULL AND syn.acc_taxon_ID<>0 AND syn.acc_taxon_ID<>syn.taxonID  )
 AND tss.statusID<>2
-- synonym infraspecies  
UNION ALL
SELECT
 tfs.family 'familyPre',
 CONCAT('s',tss.taxonID) AS 'ID',
 CONCAT('t',syn.acc_taxon_ID) AS 'AcceptedTaxonID',
 tgs.genus AS 'Genus',
 '' AS 'SubGenusName',
 tes.epithet AS 'Species',
 
 tas.author AS 'AuthorString',
 
 CASE tss.tax_rankID
  WHEN 2 THEN tes1.epithet
  WHEN 3 THEN tes2.epithet
  WHEN 4 THEN tes3.epithet
  WHEN 5 THEN tes4.epithet
  ELSE tes5.epithet
 END AS 'InfraSpecies',
 
 CASE tss.tax_rankID
  WHEN 1 THEN NULL
  ELSE  ttrs.rank_abbr
 END AS 'InfraSpecificMarker',
 
 CASE tss.tax_rankID
  WHEN 2 THEN tas1.author
  WHEN 3 THEN tas2.author
  WHEN 4 THEN tas3.author
  WHEN 5 THEN tas4.author
  ELSE tas5.author
 END AS 'InfraSpecificAuthorString',

 'synonym' AS 'GSDNameStatus',
 'synonym' AS 'Sp2000NameStatus',

 tss.taxonID AS 'GSDNameGUI'
FROM
 herbarinput.tbl_tax_species tss
 LEFT JOIN herbarinput.tbl_tax_species tsso ON (
  tsso.genID = tss.genID AND tsso.speciesID=tss.speciesID
  AND tsso.subspeciesID IS NULL AND tsso.varietyID IS NULL
  AND tsso.subvarietyID IS NULL AND tsso.formaID IS NULL AND tsso.subformaID IS NULL
 )
 LEFT JOIN herbarinput.tbl_tax_genera tgs ON tgs.genID=tss.genID
 LEFT JOIN herbarinput.tbl_tax_genera tgso ON tgso.genID=tsso.genID
 LEFT JOIN herbarinput.tbl_tax_families tfs ON tfs.familyID=tgso.familyID
 
 -- left join last synonym entry.
 LEFT JOIN herbarinput.tbl_tax_synonymy syn  ON (
  syn.tax_syn_ID=IFNULL(
  (
   SELECT
    syn2.tax_syn_ID
   FROM
    herbarinput.tbl_tax_synonymy syn2
    LEFT JOIN herbarinput.tbl_lit lit2 ON  lit2.citationID=syn2.source_citationID
   WHERE
    syn2.taxonID=tss.taxonID
   ORDER BY
    CASE
     WHEN STR_TO_DATE(lit2.jahr,'%Y-%m-%d') IS NOT NULL THEN CONCAT('a',STR_TO_DATE(lit2.jahr,'%Y-%m-%d')) 
     WHEN STR_TO_DATE(lit2.jahr,'%Y-%m') IS NOT NULL THEN CONCAT('a',STR_TO_DATE(lit2.jahr,'%Y-%m'))
     WHEN STR_TO_DATE(lit2.jahr,'%Y') IS NOT NULL THEN CONCAT('a',STR_TO_DATE(lit2.jahr,'%Y'))
     WHEN lit2.jahr='in prep.' THEN 'b'
     ELSE 'c' 
    END
    DESC
   LIMIT
    1
  ),-1)
 )
  
 LEFT JOIN herbarinput.tbl_lit lit ON lit.citationID=syn.source_citationID
 LEFT JOIN herbarinput.tbl_lit_authors aut ON aut.autorID=lit.autorID
 
 -- status, rank
 LEFT JOIN herbarinput.tbl_tax_status tts ON tts.statusID=tss.statusID
 LEFT JOIN herbarinput.tbl_tax_rank ttrs ON ttrs.tax_rankID=tss.tax_rankID
 LEFT JOIN herbarinput.tbl_tax_authors tas ON tas.authorID=tss.authorID
 LEFT JOIN herbarinput.tbl_tax_epithets tes ON tes.epithetID=tss.speciesID
 
 
 LEFT JOIN herbarinput.tbl_tax_authors tas1 ON tas1.authorID=tss.subspecies_authorID
 LEFT JOIN herbarinput.tbl_tax_authors tas2 ON tas2.authorID=tss.variety_authorID
 LEFT JOIN herbarinput.tbl_tax_authors tas3 ON tas3.authorID=tss.subvariety_authorID
 LEFT JOIN herbarinput.tbl_tax_authors tas4 ON tas4.authorID=tss.forma_authorID
 LEFT JOIN herbarinput.tbl_tax_authors tas5 ON tas5.authorID=tss.subforma_authorID
 
 LEFT JOIN herbarinput.tbl_tax_epithets tes1 ON tes1.epithetID=tss.subspeciesID
 LEFT JOIN herbarinput.tbl_tax_epithets tes2 ON tes2.epithetID=tss.varietyID
 LEFT JOIN herbarinput.tbl_tax_epithets tes3 ON tes3.epithetID=tss.subvarietyID
 LEFT JOIN herbarinput.tbl_tax_epithets tes4 ON tes4.epithetID=tss.formaID
 LEFT JOIN herbarinput.tbl_tax_epithets tes5 ON tes5.epithetID=tss.subformaID

WHERE
 -- next higher taxon is what we want
     ( tsso.tax_rankID='1' OR ( tsso.tax_rankID='7'  AND tsso.speciesID IS NULL ) ) -- ttro.rank='species' or ( ttro.rank=genus and tso.speciesID = Null)
 AND ( tgso.familyID IN ('30','115','182') ) --  tfo.family IN('Annonaceae','Chenopodiaceae','Ebenaceae')
 -- and wanted infraspecies rank...
 AND tss.tax_rankID IN (2,3,4,5,6) -- ttr.rank IN ('subspecies','variety','subvariety','forma','subforma')
 -- synonym
 AND ( syn.acc_taxon_ID IS NOT NULL AND syn.acc_taxon_ID<>0 AND syn.acc_taxon_ID<>syn.taxonID  )
 AND tss.statusID<>2
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
  
 ap.reference_id AS 'ReferenceID'
 
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
 CONCAT('c',cmnames.ReferenceID) AS 'ReferenceID',
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
 
 LEFT JOIN herbarinput.tbl_lit lit  ON lit.citationID = nlit.citationID 
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
 IFNULL(ref.Authors,'') as 'Authors',
 IFNULL(ref.Year,'') as 'Year',
 IFNULL(ref.Title,'') as 'Title',
 IFNULL(ref.Details,'') as 'Details'
 
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
