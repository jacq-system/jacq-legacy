CREATE OR REPLACE
 ALGORITHM = UNDEFINED
 VIEW herbar_view.view_common_names
 AS

SELECT
 a.geonameId	as 'geonameId',
 a.language_id	as 'language_id',
 a.period_id	as 'period_id',
 a.entity_id	as 'entity_id',
 a.reference_id	as 'reference_id',
 a.name_id	as 'name_id',
 a.tribe_id	as 'tribe_id',
 a.geospecification	as 'geospecification',
 a.annotations	as 'annotations',
 a.locked	as 'locked',

 tax.taxon_id	as 'taxon_id',
 tax.taxonID	as 'taxonID',

 nam.transliteration_id	as 'transliteration_id',

 com.common_id	as 'common_id',
 com.common_name	as 'common_name',
 com.locked	as 'common_locked',

 translit.name	as 'transliteration',

 geo.name	as 'geoname',

 lan.`iso639-6`	as 'iso639-6',
 lan.`parent_iso639-6`	as 'parent_iso639-6',
 lan.`iso639-3`	as 'iso639-3',
 lan.name	as 'language',

 per.period	as 'period',

 pers.person_id	as 'person_id',
 pers.personID	as 'personID',

 lit.literature_id	as 'literature_id',
 lit.citationID	as 'citationID',

 ser.webservice_id	as 'webservice_id',
 ser.serviceID	as 'serviceID',

 trib.tribe_name	as 'tribe'
 
 FROM
  herbar_names.tbl_name_applies_to a
  LEFT JOIN herbar_names.tbl_name_entities ent ON ent.entity_id = a.entity_id
  LEFT JOIN herbar_names.tbl_name_taxa tax ON tax.taxon_id = ent.entity_id
 
  LEFT JOIN herbar_names.tbl_name_names nam ON nam.name_id = a.name_id
  LEFT JOIN herbar_names.tbl_name_commons com ON com.common_id = nam.name_id
  LEFT JOIN herbar_names.tbl_name_transliterations translit ON translit.transliteration_id=nam.transliteration_id
  
  
  LEFT JOIN herbar_names.tbl_geonames_cache geo ON geo.geonameId = a.geonameId
  LEFT JOIN herbar_names.tbl_name_languages lan ON lan.language_id = a.language_id
  LEFT JOIN herbar_names.tbl_name_periods per ON per.period_id= a.period_id
 
  LEFT JOIN herbar_names.tbl_name_references ref ON ref.reference_id = a.reference_id
  LEFT JOIN herbar_names.tbl_name_persons pers ON pers.person_id = ref.reference_id
  LEFT JOIN herbar_names.tbl_name_literature lit ON lit.literature_id = ref.reference_id
  LEFT JOIN herbar_names.tbl_name_webservices ser ON ser.webservice_id = ref.reference_id
  
  LEFT JOIN herbar_names.tbl_name_tribes trib ON trib.tribe_id=a.tribe_id