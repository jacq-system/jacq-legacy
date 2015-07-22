<?php
// credentials for database access
$host = "localhost";        // hostname
$user = "";                 // username
$pass = "";                 // password
$db   = "herbarinput";      // source database
$dbt  = "gbif_pilot";       // target database

// which tables are to be filled (table has to exist already)
$tbls = array(array('name' => "tbl_prj_gbif_pilot_wu",   'source_id' =>  '1'),
              array('name' => "tbl_prj_gbif_pilot_gzu",  'source_id' =>  '4'),
              array('name' => "tbl_prj_gbif_pilot_tbi",  'source_id' => '48')
             );
