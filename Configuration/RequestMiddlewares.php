<?php

use JambageCom\TtProducts\Middleware\Initialization;


return [
    'backend' => [
        'jambagecom/tt-products/initialization' => [
            'target' => Initialization::class,
            'description' => 'The shop needs some initialization.',
            'after' => [
                'typo3/cms-backend/site-resolver',
            ],
        ],
    ],
];
