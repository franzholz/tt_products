<?php

use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;

return [
    'tt-products-wizard' => [
        'provider' => BitmapIconProvider::class,
        // The source bitmap file
        'source' => 'EXT:tt_products/Resources/Public/Icons/PluginWizard.png',
    ],
    'tt-products-product' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:tt_products/Resources/Public/Icons/tt_products.gif',
    ]
];
