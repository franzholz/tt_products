<?php
defined('TYPO3_MODE') || die('Access denied.');

$table = 'sys_products_orders';
$configuration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\JambageCom\TtProducts\Domain\Model\Dto\EmConfiguration::class);


if (
    version_compare(TYPO3_version, '8.7.0', '<')
) {
    $fieldArray = array('tstamp', 'crdate', 'date_of_birth', 'date_of_payment', 'date_of_delivery');

    foreach ($fieldArray as $field) {
        unset($GLOBALS['TCA'][$table]['columns'][$field]['config']['renderType']);
        $GLOBALS['TCA'][$table]['columns'][$field]['config']['max'] = '20';
    }
}


$orderBySortingTablesArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['orderBySortingTables']);
if (
    !empty($orderBySortingTablesArray) &&
    in_array($table, $orderBySortingTablesArray)
) {
    $GLOBALS['TCA'][$table]['ctrl']['sortby'] = 'sorting';
}

if (
    defined('TYPO3_version') &&
    version_compare(TYPO3_version, '10.0.0', '<')
) {
    $GLOBALS['TCA'][$table]['columns']['orderHtml'] = [
        'exclude' => 1,
        'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xlf:sys_products_orders.orderHtml',
        'config' => [
            'type' => 'user',
            'size' => '30',
            'db' => 'passthrough',
            'userFunc' => 'JambageCom\\TtProducts\\Hooks\\OrderBackend->displayOrderHtml',
            'parameters' => [
                'format' => 'html'
            ],
            'default' => ''
        ]
    ];

    $GLOBALS['TCA'][$table]['columns']['ordered_products'] = [
        'exclude' => 1,
        'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xlf:sys_products_orders.ordered_products',
        'config' => [
            'type' => 'user',
            'userFunc' => 'JambageCom\\TtProducts\\Hooks\\OrderBackend->tceSingleOrder',
            'db' => 'passthrough',
            'default' => ''
        ]
    ];
}

$excludeArray =  
    (version_compare(TYPO3_version, '10.0.0', '>=') ? 
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['exclude'] :
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['exclude.']
    );

if (
    defined('TYPO3_version') &&
    version_compare(TYPO3_version, '9.0.0', '<')
) {
    $excludeArray[$table] .= ',slug';
} else {
    $GLOBALS['TCA'][$table]['columns']['slug']['config']['eval'] = $configuration->getSlugBehaviour();    
}

if (
    isset($excludeArray) &&
    is_array($excludeArray) &&
    isset($excludeArray[$table])
) {
    \JambageCom\Div2007\Utility\TcaUtility::removeField(
        $GLOBALS['TCA'][$table],
        $excludeArray[$table]
    );
}

