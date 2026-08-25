<?php

return [
    'lib' => [
        'core' => [
            'debug' => [
                'on' => false,
            ],
            'request' => [
                'uri' => 'ajax/jaxonServer.php',
            ],
            'prefix' => [
                'class' => '',
            ],
        ],
        'js' => [
            'app' => [
                'uri' => 'js',
                'dir' => 'js',
                'file' => 'jacqJaxon',
                'export' => true,
            ],
        ],
    ],
    'app' => [
        'directories' => [
            __DIR__ . '/../classes/Jaxon' => [
                'namespace' => 'Jacq\Jaxon',
                'autoload' => true,
            ],
        ]
    ],
];
