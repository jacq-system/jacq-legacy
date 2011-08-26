-- ===========================================
-- ready
-- view_commonnames
--
-- ===========================================
CREATE OR REPLACE
 ALGORITHM = UNDEFINED
 VIEW herbar_view.view_commonnames
 AS
 
SELECT
 a.entity_id as 'entity_id',
 tax.taxonID as 'taxonID',

 a.name_id as 'name_id',
 com.common_name as 'common_name',

 a.language_id as 'language_id',
 lan.`iso639-6` as 'iso639-6',
 lan.name as 'language',
 lan.`parent_iso639-6` as 'parent_iso639-6',
 a.geonameId as 'geoname_id',
 geo.name as 'geoname',

 a.period_id as 'period_id',
 per.period as 'period',

 a.reference_id as 'reference_id',

 CASE
  WHEN pers.personID THEN 'person'
  WHEN ser.serviceID THEN 'service'
  ELSE 'literature'
 END as 'source',

 lit.citationID as 'literatureID',
 pers.personID as 'personID',
 ser.serviceID as 'serviceID',
 
 a.geospecification as 'geospecification',
 a.annotation as 'annotation',
 
 a.locked as 'locked'
FROM
 herbar_names.tbl_name_applies_to a
 LEFT JOIN herbar_names.tbl_name_entities ent ON ent.entity_id = a.entity_id
 LEFT JOIN herbar_names.tbl_name_taxa tax ON tax.taxon_id = ent.entity_id

 LEFT JOIN herbar_names.tbl_name_names nam ON nam.name_id = a.name_id
 LEFT JOIN herbar_names.tbl_name_commons com ON com.common_id = nam.name_id

 LEFT JOIN herbar_names.tbl_geonames_cache geo ON geo.geonameId = a.geonameId
 LEFT JOIN herbar_names.tbl_name_languages lan ON  lan.language_id = a.language_id
 LEFT JOIN herbar_names.tbl_name_periods per ON per.period_id= a.period_id

 LEFT JOIN herbar_names.tbl_name_references ref ON ref.reference_id = a.reference_id

 LEFT JOIN herbar_names.tbl_name_persons pers ON pers.person_id = ref.reference_id
 LEFT JOIN herbar_names.tbl_name_literature lit ON lit.literature_id = ref.reference_id
 LEFT JOIN herbar_names.tbl_name_webservices ser ON ser.webservice_id = ref.reference_id
;
