<?php
// credentials for database access
$host = "localhost";        // hostname
$user = "";                 // username
$pass = "";                 // password
$db   = "herbarinput";      // source database
$dbt  = "gbif_pilot";       // target database

$europeana_dir = "";        // directory of europeana images

// which tables are to be filled (table has to exist already)
// europeana_cache: use images in $europeana_dir for europeana
// europeana_get:   include this source in europeana-checks
$tbls = array(array('name' => "tbl_prj_gbif_pilot_wu",   'source_id' =>  '1', 'europeana_cache' => 0, 'europeana_get' => 1),
              array('name' => "tbl_prj_gbif_pilot_gzu",  'source_id' =>  '4', 'europeana_cache' => 0, 'europeana_get' => 1),
              array('name' => "tbl_prj_gbif_pilot_tbi",  'source_id' => '48', 'europeana_cache' => 0, 'europeana_get' => 1)
             );
