<?php

declare(strict_types = 1);

(static function (): void {
    $selectItemArray = new \TYPO3\CMS\Core\Schema\Struct\SelectItem(
        'select',
        'LLL:EXT:tt_products/Resources/Private/Language/Pi1/locallang.xlf:plugin',
        'tx_ttproducts_pi1',
        '',
        'tt_products'
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
        $selectItemArray,
        'CType',
        'tx_ttproducts_pi1'
    );
})();
