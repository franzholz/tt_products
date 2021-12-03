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

if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('voucher')) {

	$temporaryColumns = array (
		'gained_voucher' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:sys_products_orders.gained_voucher',
			'config' => array (
				'type' => 'inline',
				'appearance' => array (
					'collapseAll' => true,
					'newRecordLinkAddTitle' => true,
					'useCombination' => true
				),
				'foreign_table' => 'sys_products_orders_mm_gained_voucher_codes',
				'foreign_field' => 'uid_local',
				'foreign_sortby' => 'sorting',
				'foreign_label' => 'uid_foreign',
				'foreign_selector' => 'uid_foreign',
				'foreign_unique' => 'uid_foreign',
				'maxitems' => 100
			),
		),
	);

	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
		'sys_products_orders',
		$temporaryColumns
	);
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
		'sys_products_orders',
		'gained_voucher',
		'',
		'after:gained_uid'
	);
}

if (
    defined('TYPO3_version') &&
    version_compare(TYPO3_version, '10.0.0', '<')
) {
    $GLOBALS['TCA'][$table]['columns']['orderHtml'] = [
        'exclude' => 1,
        'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:sys_products_orders.orderHtml',
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
        'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf:sys_products_orders.ordered_products',
        'config' => [
            'type' => 'user',
            'userFunc' => 'JambageCom\\TtProducts\\Hooks\\OrderBackend->tceSingleOrder',
            'db' => 'passthrough',
            'default' => ''
        ]
    ];
}


if (
    defined('TYPO3_version') &&
    version_compare(TYPO3_version, '11.0.0', '<')
) {
    $GLOBALS['TCA'][$table]['columns']['sys_language_uid'] = [
        'exclude' => 1,
        'label' => DIV2007_LANGUAGE_LGL . 'language',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'foreign_table' => 'sys_language',
            'foreign_table_where' => 'ORDER BY sys_language.title',
            'items' => [
                [DIV2007_LANGUAGE_LGL . 'allLanguages', -1],
                [DIV2007_LANGUAGE_LGL . 'default_value', 0]
            ],
            'default' => 0
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
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               
