<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(function () {
    $table = 'tt_products_language';
    $configuration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\JambageCom\TtProducts\Domain\Model\Dto\EmConfiguration::class);

    if (
        version_compare(TYPO3_version, '8.7.0', '<')
    ) {
        $fieldArray = array('tstamp', 'crdate', 'starttime', 'endtime');

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
        version_compare(TYPO3_version, '7.0.0', '<')
    ) {
        $GLOBALS['TCA'][$table]['columns']['fe_group'] = array (
            'exclude' => 1,
            'label' => DIV2007_LANGUAGE_LGL . 'fe_group',
            'config' => array (
                'type' => 'select',
                'items' => array (
                    array('', 0),
                    array(DIV2007_LANGUAGE_LGL . 'hide_at_login', -1),
                    array(DIV2007_LANGUAGE_LGL . 'any_login', -2),
                    array(DIV2007_LANGUAGE_LGL . 'usergroups', '--div--')
                ),
                'foreign_table' => 'fe_groups',
                'default' => 0
            )
        );
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

    if (
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['fal'] ||
        version_compare(TYPO3_version, '10.4.0', '>=')
    ) {
        $GLOBALS['TCA'][$table]['ctrl']['thumbnail'] = 'image_uid';    

        $GLOBALS['TCA'][$table]['types']['0']['showitem'] = str_replace(',image;', ',image_uid;', $GLOBALS['TCA'][$table]['types']['0']['showitem']);

        unset($GLOBALS['TCA'][$table]['columns']['image']);
    } else {
        $GLOBALS['TCA'][$table]['types']['0']['showitem'] = str_replace(',image;;;;4-4-4,', ',image;;;;4-4-4,image_uid;;;;4-4-4,', $GLOBALS['TCA'][$table]['types']['0']['showitem']);
    }

    if (version_compare(TYPO3_version, '7.6.0', '>=')) {

        unset($GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']);
        unset($GLOBALS['TCA'][$table]['ctrl']['transOrigPointerTable']);
    }

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToInsertRecords($table);
});
