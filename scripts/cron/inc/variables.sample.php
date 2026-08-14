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
];
