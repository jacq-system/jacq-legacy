# ===========================================
#
# view_sourcedatabase
#
# ===========================================
CREATE OR REPLACE
 ALGORITHM = UNDEFINED
 VIEW 'view_sourcedatabase'
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
 herbarinput.meta m,
 herbarinput.metadb mdb
WHERE
 m.source_id=mdb.source_id_fk

;

# ===========================================
#
# view_acceptedspecies
#
# ===========================================
CREATE OR REPLACE
 ALGORITHM = UNDEFINED
 VIEW 'view_acceptedspecies'
 AS

SELECT
 ts.taxonID as 'AcceptedTaxonID',
 
 'Plantae' as 'Kingdom',
 'Magnoliophyta' as 'Phylum',
 'Magnoliopsida' as 'Class',
 'Magnoliales' as 'Order',
 '' as 'Superfamily',
 
 tf.family as 'Family',
 tg.genus as 'Genus',

 '' as 'SubGenusName',
 te.epithet as 'Species',

 CONCAT(
  IF(te.epithetID ,CONCAT(' ',         te.epithet ,' ',ta.author ),'')
 ) as 'AuthorString',

 '' as 'GSDNameStatus',
 tts.status_sp2000 as 'Sp2000NameStatus',
 
 'No' as 'IsFossil',
 'terrestial' as 'LifeZone',
 '' as 'AdditionalData',
 
 'LTSSpecialist' as 'LTSSpecialist',
 'LTSDate' as 'LTSDate',
 
 CONCAT('http://herbarium.botanik.univie.ac.at/annonaceae/listSynonyms.php?ID=',ts.taxonID) as 'SpeciesURL',
 
 'GSDTaxonGUI' as 'GSDTaxonGUI',
 'GSDNameGUI' as 'GSDNameGUI'

FROM (
  herbarinput.tbl_tax_species ts,
  herbarinput.tbl_tax_rank ttr
 )
 LEFT JOIN herbarinput.tbl_tax_authors ta ON ta.authorID=ts.authorID
 LEFT JOIN herbarinput.tbl_tax_epithets te ON te.epithetID=ts.speciesID
 
 LEFT JOIN herbarinput.tbl_tax_genera tg ON tg.genID=ts.genID
 LEFT JOIN herbarinput.tbl_tax_families tf ON tf.familyID=tg.familyID
 LEFT JOIN herbarinput.tbl_tax_status tts ON tts.statusID=ts.statusID

WHERE
 ts.tax_rankID=ttr.tax_rankID
 AND tf.familyID='30'
 AND ts.statusID!='2'
 AND ttr.tax_rankID!='24'
 AND ttr.tax_rankID!='8'
;

# ===========================================
#
# view_acceptedinfraspecifictaxa
#
# ===========================================
CREATE OR REPLACE
 ALGORITHM = UNDEFINED
 VIEW 'view_acceptedinfraspecifictaxa'
 AS

SELECT
 '' as 'AcceptedTaxonID',
 ts.taxonID as 'ParentSpeciesID',
 te.epithet as 'InfraSpeciesEpithet',

 CONCAT('',  te.epithet,' ',ta.author) as 'InfraSpecificAuthorString',
 
 ttr.rank_abbr as 'InfraSpecificMarker',
 '' as 'GSDNameStatus',
 tts.status_sp2000 as 'Sp2000NameStatus',

 'No' as 'IsFossil',
 'terrestial' as 'LifeZone',
 '' as 'AdditionalData',

 'LTSSpecialist' as 'LTSSpecialist',
 'LTSDate' as 'LTSDate',

 CONCAT('http://herbarium.botanik.univie.ac.at/annonaceae/listSynonyms.php?ID=',ts.taxonID) as 'InfraSpeciesURL',
 
 'GSDTaxonGUI' as 'GSDTaxonGUI',
 'GSDNameGUI' as 'GSDNameGUI'

FROM (
  sp2000views.view_acceptedspecies acc,
  herbarinput.tbl_tax_species ts,
  herbarinput.tbl_tax_epithets te,
  herbarinput.tbl_tax_authors ta
 )
 LEFT JOIN herbarinput.tbl_tax_rank ttr ON ttr.tax_rankID=ts.tax_rankID
 LEFT JOIN herbarinput.tbl_tax_status tts ON tts.statusID=ts.statusID

 
WHERE
 ts.taxonID=acc.AcceptedTaxonID
 and te.epithetID=ts.subspeciesID
 and (ts.subspecies_authorID=NULL or ta.authorID=ts.subspecies_authorID)

;

# ===========================================
#
# view_synonyms
#
# ===========================================
CREATE OR REPLACE
 ALGORITHM = UNDEFINED
 VIEW 'view_synonyms'
 AS

SELECT
 sy.tax_syn_ID as 'ID',
 sy.acc_taxon_ID as 'AcceptedTaxonID',
 tg.genus as 'Genus',
 'SubGenusName' as 'SubGenusName',
 te.epithet as 'Species',
 
 CONCAT(
  IF(ate.epithetID ,CONCAT(' ',         ate.epithet ,' ',ata.author ),''),
  IF(ate1.epithetID,CONCAT(' subsp. ',  ate1.epithet,' ',ata1.author),''),
  IF(ate2.epithetID,CONCAT(' var. ',    ate2.epithet,' ',ata2.author),''),
  IF(ate3.epithetID,CONCAT(' subvar. ', ate3.epithet,' ',ata3.author),''),
  IF(ate4.epithetID,CONCAT(' forma ',   ate4.epithet,' ',ata4.author),''),
  IF(ate5.epithetID,CONCAT(' subforma ',ate5.epithet,' ',ata5.author),'')
 ) as 'AuthorString',
 
 '' as 'InfraSpecies',
 '' as 'InfraSpecificMarker',
 
 CONCAT(
  IF(ite.epithetID ,CONCAT(' ',         ite.epithet ,' ',ita.author ),''),
  IF(ite1.epithetID,CONCAT(' subsp. ',  ite1.epithet,' ',ita1.author),''),
  IF(ite2.epithetID,CONCAT(' var. ',    ite2.epithet,' ',ita2.author),''),
  IF(ite3.epithetID,CONCAT(' subvar. ', ite3.epithet,' ',ita3.author),''),
  IF(ite4.epithetID,CONCAT(' forma ',   ite4.epithet,' ',ita4.author),''),
  IF(ite5.epithetID,CONCAT(' subforma ',ite5.epithet,' ',ita5.author),'')
 ) as 'InfraSpecificAuthorString',
 
 '' as 'GSDNameStatus',
 tts.status_sp2000 as 'Sp2000NameStatus',

 'GSDNameGUI' as 'GSDNameGUI'
 
FROM (
  tbl_tax_species ts,
  tbl_tax_rank ttr
 )
 LEFT JOIN tbl_atax_authors ata ON ata.authorID=ts.authorID
 LEFT JOIN tbl_atax_authors ata1 ON ata1.authorID=ts.subspecies_authorID
 LEFT JOIN tbl_atax_authors ata2 ON ata2.authorID=ts.variety_authorID
 LEFT JOIN tbl_atax_authors ata3 ON ata3.authorID=ts.subvariety_authorID
 LEFT JOIN tbl_atax_authors ata4 ON ata4.authorID=ts.forma_authorID
 LEFT JOIN tbl_atax_authors ata5 ON ata5.authorID=ts.subforma_authorID
 
 LEFT JOIN tbl_atax_epithets ate ON ate.epithetID=ts.speciesID
 LEFT JOIN tbl_atax_epithets ate1 ON ate1.epithetID=ts.subspeciesID
 LEFT JOIN tbl_atax_epithets ate2 ON ate2.epithetID=ts.varietyID
 LEFT JOIN tbl_atax_epithets ate3 ON ate3.epithetID=ts.subvarietyID
 LEFT JOIN tbl_atax_epithets ate4 ON ate4.epithetID=ts.formaID
 LEFT JOIN tbl_atax_epithets ate5 ON ate5.epithetID=ts.subformaID
 
 LEFT JOIN tbl_itax_authors ita ON ita.authorID=ts.authorID
 LEFT JOIN tbl_itax_authors ita1 ON ita1.authorID=ts.subspecies_authorID
 LEFT JOIN tbl_itax_authors ita2 ON ita2.authorID=ts.variety_authorID
 LEFT JOIN tbl_itax_authors ita3 ON ita3.authorID=ts.subvariety_authorID
 LEFT JOIN tbl_itax_authors ita4 ON ita4.authorID=ts.forma_authorID
 LEFT JOIN tbl_itax_authors ita5 ON ita5.authorID=ts.subforma_authorID
 
 LEFT JOIN tbl_itax_epithets ite ON ite.epithetID=ts.speciesID
 LEFT JOIN tbl_itax_epithets ite1 ON ite1.epithetID=ts.subspeciesID
 LEFT JOIN tbl_itax_epithets ite2 ON ite2.epithetID=ts.varietyID
 LEFT JOIN tbl_itax_epithets ite3 ON ite3.epithetID=ts.subvarietyID
 LEFT JOIN tbl_itax_epithets ite4 ON ite4.epithetID=ts.formaID
 LEFT JOIN tbl_itax_epithets ite5 ON ite5.epithetID=ts.subformaID
 
 
 LEFT JOIN tbl_tax_genera tg ON tg.genID=ts.genID
 LEFT JOIN tbl_tax_families tf ON tf.familyID=tg.familyID
 LEFT JOIN tbl_tax_status tts ON tts.statusID=ts.statusID

WHERE
 ts.tax_rankID=ttr.tax_rankID
 AND tf.familyID='30'
 AND ts.statusID!='2'
 AND ttr.tax_rankID!='24'
 AND ttr.tax_rankID!='8'

;

# ===========================================
#
# view_commonnames
#
# ===========================================
CREATE OR REPLACE
 ALGORITHM = UNDEFINED
 VIEW 'view_commonnames'
 AS

SELECT
 'AcceptedTaxonID' as 'AcceptedTaxonID',
 'CommonName' as 'CommonName',
 'Transliteration' as 'Transliteration',
 'Language' as 'Language',
 'Country' as 'Country',
 'Area' as 'Area',
 'ReferenceID' as 'ReferenceID'
 
FROM
 tbl_tax_species sp
 
WHERE
 
ORDER BY

;

# ===========================================
#
# view_distribution
#
# ===========================================
CREATE OR REPLACE
 ALGORITHM = UNDEFINED
 VIEW 'view_distribution'
 AS

SELECT
 taxonID... as 'AcceptedTaxonID',
 gn.iso_alpha_2_code as 'DistributionElement',
 'ISO2Alpha' as 'StandardInUse',
 'native' as 'DistributionStatus'
 
FROM
  tbl_geo_nation gn
 
WHERE
 gn.nationID=...

;

# ===========================================
#
# view_namereferenceslinks
#
# ===========================================
CREATE OR REPLACE
 ALGORITHM = UNDEFINED
 VIEW 'view_namereferenceslinks'
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
 
ORDER BY
 
;

# ===========================================
#
# view_references
#
# ===========================================
CREATE OR REPLACE
 ALGORITHM = UNDEFINED
 VIEW 'view_eferences'
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

