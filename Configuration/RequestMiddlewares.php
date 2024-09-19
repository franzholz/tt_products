<?php

use JambageCom\TtProducts\Middleware\Initialization;
use JambageCom\TtProducts\Middleware\StoreBasket;


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
    'frontend' => [
        'jambagecom/tt-products/preprocessing' => [
            'target' => StoreBasket::class,
            'description' => 'The basket items must be stored into the TYPO3 session at an early position, eben before the TypoScript is existent. A TypoScript condition based on the contents of the basket will then be no step behind. However only one shop is possible if you use a basket TypoScript condition.',
            'after' => [
                'typo3/cms-frontend/tsfe',
            ],
            'before' => [
                'typo3/cms-frontend/prepare-tsfe-rendering',
            ],
        ],
    ],
];
