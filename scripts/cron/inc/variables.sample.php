<?php
// credentials for database access
$_CONFIG = [
    'DATABASE' => [
        // Main database containing the data
        'INPUT' => [
            'host' => 'localhost',
            'name' => 'herbarinput',
            'user' => '',
            'pass' => ''
        ],
        // target database for gbif_pilot-tables
        'GBIF_PILOT' => [
            'host' => 'localhost',
            'name' => 'gbif_pilot',
            'user' => '',
            'pass' => ''
        ],
        // target database for gbif_cache-tables
        'GBIF_CACHE' => [
            'host' => 'localhost',
            'name' => 'gbif_cache',
            'user' => '',
            'pass' => ''
        ],
    ],

    'EUROPEANA_DIR' => "", // directory of europeana images

    // which tables are to be filled (table has to exist already)
    // europeana_cache: use images in $europeana_dir for europeana
    // europeana_get:   include this source in europeana-checks
    'GBIF_TABLES' => [
        ['name' => "tbl_prj_gbif_pilot_wu",   'source_id' =>  '1', 'europeana_cache' => 1, 'europeana_get' => 1],
        ['name' => "tbl_prj_gbif_pilot_gzu",  'source_id' =>  '4', 'europeana_cache' => 1, 'europeana_get' => 1],
    ],
];
