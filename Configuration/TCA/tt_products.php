<?php

defined('TYPO3') || die('Access denied.');

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

$extensionKey = 'tt_products';
$table = 'tt_products';
$palleteAddition = '';
$languageSubpath = '/Resources/Private/Language/';
$languageLglPath = 'LLL:EXT:core' . $languageSubpath . 'locallang_general.xlf:LGL.';

$whereCategory =
    (
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['where']['category']
        ?? ''
    );

return [
    'ctrl' => [
        'title' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:tt_products',
        'label' => 'title',
        'label_alt' => 'subtitle',
        'default_sortby' => 'ORDER BY title',
        'tstamp' => 'tstamp',
        'prependAtCopy' => $languageLglPath . 'prependAtCopy',
        'crdate' => 'crdate',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
            'fe_group' => 'fe_group',
        ],
        'useColumnsForDefaultValues' => 'category',
        'mainpalette' => 1,
        'iconfile' => 'EXT:' . $extensionKey . '/Resources/Public/Icons/tt_products.gif',
        'searchFields' => 'uid,title,subtitle,itemnumber,ean,note,note2,www',
        'typeicon_classes' => [
            'default' => 'tt-products-product'
        ],
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'languageField' => 'sys_language_uid',
        'translationSource' => 'l10n_source',
    ],
    'columns' => [
        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language',
            ],
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => '',
                        'value' => 0,
                    ],
                ],
                'foreign_table' => 'tt_products',
                'foreign_table_where' =>
                    'AND {#tt_products}.{#pid}=###CURRENT_PID###'
                    . ' AND {#tt_products}.{#sys_language_uid} IN (-1,0)',
                'default' => 0,
            ],
        ],
        'l10n_source' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
                'default' => '',
            ],
        ],
        'hidden' => [
            'exclude' => 1,
            'label' => $languageLglPath . 'hidden',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'starttime' => [
            'exclude' => 1,
            'label' => $languageLglPath . 'starttime',
            'config' => [
                'type' => 'datetime',
                'size' => '8',
                'checkbox' => '0',
                'default' => 0,
                'format' => 'date',
            ],
        ],
        'endtime' => [
            'exclude' => 1,
            'label' => $languageLglPath . 'endtime',
            'config' => [
                'type' => 'datetime',
                'size' => '8',
                'checkbox' => '0',
                'default' => 0,
                'range' => [
                    'upper' => mktime(0, 0, 0, 12, 31, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['endtimeYear']),
                    'lower' => mktime(0, 0, 0, date('n') - 1, date('d'), date('Y')),
                ],
                'format' => 'date',
            ],
        ],
        'fe_group' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => $languageLglPath . 'fe_group',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 7,
                'maxitems' => 20,
                'items' => [
                    [
                        'label' => $languageLglPath . 'hide_at_login',
                        'value' => -1,
                    ],
                    [
                        'label' => $languageLglPath . 'any_login',
                        'value' => -2,
                    ],
                    [
                        'label' => $languageLglPath . 'usergroups',
                        'value' => '--div--',
                    ],
                ],
                'exclusiveKeys' => '-1,-2',
                'foreign_table' => 'fe_groups',
                'foreign_table_where' => 'ORDER BY fe_groups.title',
                'default' => 0,
            ],
        ],
        'title' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.title',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'eval' => 'trim',
                'max' => '256',
                'default' => null,
            ],
        ],
        'subtitle' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.subtitle',
            'config' => [
                'type' => 'text',
                'rows' => '3',
                'cols' => '20',
                'max' => '512',
                'default' => null,
                'nullable' => true,
            ],
        ],
        'slug' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.slug',
            'config' => [
                'type' => 'slug',
                'size' => 50,
                'generatorOptions' => [
                    'fields' => ['title', 'itemnumber'],
                    'fieldSeparator' => '_',
                    'replacements' => [
                        '/' => '-',
                    ],
                ],
                'fallbackCharacter' => '-',
                'default' => null,
            ],
        ],
        'keyword' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.keyword',
            'config' => [
                'type' => 'text',
                'rows' => '5',
                'cols' => '20',
                'max' => '512',
                'default' => null,
                'nullable' => true,
            ],
        ],
        'prod_uid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.prod_uid',
            'config' => [
                'type' => 'group',
                'allowed' => 'tt_products',
                'foreign_table' => 'tt_products',
                'foreign_table_where' => ' ORDER BY tt_products.uid',
                'size' => 3,
                'minitems' => 0,
                'maxitems' => 3,
                'default' => 0,
            ],
        ],
        'itemnumber' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.itemnumber',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'eval' => 'trim',
                'max' => '120',
                'default' => null,
            ],
        ],
        'ean' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.ean',
            'config' => [
                'type' => 'input',
                'size' => '48',
                'eval' => 'trim',
                'max' => '48',
                'default' => null,
            ],
        ],
        'shipping_point' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.shipping_point',
            'config' => [
                'type' => 'input',
                'size' => '24',
                'eval' => 'trim',
                'max' => '24',
                'default' => null,
            ],
        ],
        'price' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.price',
            'config' => [
                'type' => 'number',
                'size' => '20',
                'eval' => 'trim',
                'max' => '20',
                'default' => 0,
                'format' => 'decimal',
            ],
        ],
        'price2' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.price2',
            'config' => [
                'type' => 'number',
                'size' => '20',
                'eval' => 'trim',
                'max' => '20',
                'default' => 0,
                'format' => 'decimal',
            ],
        ],
        'discount' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.discount',
            'config' => [
                'type' => 'number',
                'size' => '4',
                'max' => '8',
                'eval' => 'trim',
                'range' => [
                    'upper' => '1000',
                    'lower' => '0',
                ],
                'default' => 0,
                'format' => 'decimal',
            ],
        ],
        'discount_disable' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.discount_disable',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'tax' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.tax',
            'config' => [
                'type' => 'number',
                'size' => '12',
                'max' => '19',
                'eval' => 'trim',
                'default' => 0,
                'format' => 'decimal',
            ],
        ],
        'creditpoints' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.creditpoints',
            'config' => [
                'type' => 'number',
                'size' => '12',
                'max' => '12',
                'default' => 0,
            ],
        ],
        'deposit' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.deposit',
            'config' => [
                'type' => 'number',
                'size' => '20',
                'eval' => 'trim',
                'max' => '20',
                'default' => 0,
                'format' => 'decimal',
            ],
        ],
        'graduated_price_enable' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.graduated_price_enable',
            'config' => [
                'type' => 'check',
                'default' => '1',
            ],
        ],
        'graduated_price_round' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.graduated_price_round',
            'config' => [
                'type' => 'input',
                'size' => '20',
                'max' => '20',
                'default' => null,
                'nullable' => true,
            ],
        ],
        'graduated_price_uid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.graduated_price_uid',
            'config' => [
                'type' => 'inline',
                'appearance' => [
                    'collapseAll' => true,
                    'newRecordLinkAddTitle' => true,
                    'useCombination' => true,
                ],
                'foreign_table' => 'tt_products_graduated_price',
                'foreign_field' => 'parentuid',
                'foreign_table_field' => 'parenttable',
                'maxitems' => 12,
                'default' => 0,
            ],
        ],
        'article_uid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.article_uid',
            'config' => [
                'type' => 'inline',
                'appearance' => [
                    'collapseAll' => true,
                    'newRecordLinkAddTitle' => true,
                    'useCombination' => true,
                ],
                'foreign_table' => 'tt_products_articles',
                'foreign_field' => 'uid_product',
                'maxitems' => 1000,
                'default' => 0,
            ],
        ],
        'note' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.note',
            'config' => [
                'type' => 'text',
                'cols' => '48',
                'rows' => '5',
                'default' => null,
            ],
        ],
        'note2' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.note2',
            'config' => [
                'type' => 'text',
                'cols' => '48',
                'rows' => '2',
                'default' => null,
            ],
        ],
        'note_uid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.note_uid',
            'config' => [
                'type' => 'group',
                'allowed' => 'pages',
                'MM' => 'tt_products_products_note_pages_mm',
                'MM_hasUidField' => true,
                'size' => '2',
                'autoSizeMax' => '12',
                'minitems' => '0',
                'maxitems' => '30',
                'default' => 0,
            ],
        ],
        'text_uid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.text_uid',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tt_products_texts',
                'foreign_field' => 'parentid',
                'foreign_table_field' => 'parenttable',
                'maxitems' => 20,
                'default' => 0,
            ],
        ],
        'download_type' => [
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.download_type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => '', 'value' => ''],
                ],
                'default' => '',
                'authMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode'] ?? 'explicitAllow',
            ],
        ],
        'download_info' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.download_info',
            'config' => [
                'type' => 'flex',
                'ds_pointerField' => 'download_type',
                'ds' => [
                    'default' => '
						<T3DataStructure>
							<ROOT>
								<type>array</type>
								<el>
								<limitedToDomain>
									<TCEforms>
										<label>LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.download_info.limitedToDomain</label>
										<config>
											<type>check</type>
										</config>
									</TCEforms>
								</limitedToDomain>
								</el>
							</ROOT>
							<meta>
								<langDisable>1</langDisable>
							</meta>
						</T3DataStructure>
						',
                ],
                'nullable' => true,
            ],
        ],
        'download_uid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.download_uid',
            'config' => [
                'type' => 'group',
                'allowed' => 'tt_products_downloads',
                'MM' => 'tt_products_products_mm_downloads',
                'MM_hasUidField' => true,
                'foreign_table' => 'tt_products_downloads',
                'foreign_table_where' => ' ORDER BY tt_products_downloads.title',
                'size' => 10,
                'minitems' => 0,
                'maxitems' => 1000,
                'default' => 0,
            ],
        ],
        'unit_factor' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.unit_factor',
            'config' => [
                'type' => 'input',
                'size' => '10',
                'eval' => 'double',
                'default' => 1,
                'max' => '6',
            ],
        ],
        'unit' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.unit',
            'config' => [
                'type' => 'input',
                'size' => '20',
                'eval' => 'trim',
                'max' => '20',
                'default' => null,
            ],
        ],
        'www' => [
            'exclude' => 1,
            'label' => $languageLglPath . 'www',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
                'size' => '30',
                'max' => '160',
                'default' => null,
            ],
        ],
        'category' => [
            'exclude' => 1,
            'label' => $languageLglPath . 'category',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => '', 'value' => 0],
                ],
                'foreign_table' => 'tt_products_cat',
                'foreign_table_where' => $whereCategory,
                'default' => 0,
            ],
        ],
        'inStock' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.inStock',
            'config' => [
                'type' => 'number',
                'size' => '6',
                'max' => '6',
                'default' => 1,
            ],
        ],
        'basketminquantity' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.basketminquantity',
            'config' => [
                'type' => 'number',
                'size' => '10',
                'eval' => 'trim',
                'max' => '10',
                'default' => 0,
                'format' => 'decimal',
            ],
        ],
        'basketmaxquantity' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.basketmaxquantity',
            'config' => [
                'type' => 'number',
                'size' => '10',
                'eval' => 'trim',
                'max' => '10',
                'default' => 0,
                'format' => 'decimal',
            ],
        ],
        'image_uid' => [
            'exclude' => 1,
            'label' => $languageLglPath . 'image',
            'config' => [
                ### !!! Watch out for fieldName different from columnName
                'type' => 'file',
                'allowed' => 'common-image-types',
                'appearance' => [
                    'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference',
                    'collapseAll' => true,
                ],
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
        'smallimage_uid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.smallimage',
            'config' => [
                ### !!! Watch out for fieldName different from columnName
                'type' => 'file',
                'allowed' => 'common-image-types',
                'appearance' => [
                    'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference',
                    'collapseAll' => true,
                ],
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
        'datasheet_uid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.datasheet',
            'config' => [
                ### !!! Watch out for fieldName different from columnName
                'type' => 'file',
                'allowed' => 'common-image-types',
                'appearance' => [
                    'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference',
                    'collapseAll' => true,
                ],
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
        'weight' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.weight',
            'config' => [
                'type' => 'input',
                'size' => '10',
                'max' => '20',
                'eval' => 'trim,JambageCom\\Div2007\\Hooks\\Evaluation\\Double6',
                'default' => 0,
            ],
        ],
        'usebydate' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.usebydate',
            'config' => [
                'type' => 'datetime',
                'size' => '8',
                'default' => 0,
                'format' => 'date',
            ],
        ],
        'bulkily' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.bulkily',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'offer' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.offer',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'highlight' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.highlight',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'bargain' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.bargain',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'directcost' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.directcost',
            'config' => [
                'type' => 'number',
                'size' => '12',
                'eval' => 'trim',
                'max' => '20',
                'default' => 0,
                'format' => 'decimal',
            ],
        ],
        'accessory_uid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.accessory_uid',
            'config' => [
                'type' => 'group',
                'allowed' => 'tt_products',
                'MM' => 'tt_products_accessory_products_products_mm',
                'MM_hasUidField' => true,
                'foreign_table' => 'tt_products',
                'foreign_table_where' => ' ORDER BY tt_products.uid',
                'size' => 10,
                'minitems' => 0,
                'maxitems' => 12,
                'default' => 0,
            ],
        ],
        'related_uid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.related_uid',
            'config' => [
                'type' => 'group',
                'allowed' => 'tt_products',
                'MM' => 'tt_products_related_products_products_mm',
                'MM_hasUidField' => true,
                'foreign_table' => 'tt_products',
                'foreign_table_where' => ' ORDER BY tt_products.uid',
                'size' => 10,
                'minitems' => 0,
                'maxitems' => 50,
                'default' => 0,
            ],
        ],
        'color' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.color',
            'config' => [
                'type' => 'text',
                'cols' => '46',
                'rows' => '5',
                'default' => null,
                'nullable' => true,
            ],
        ],
        'color2' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.color2',
            'config' => [
                'type' => 'text',
                'cols' => '46',
                'rows' => '5',
                'default' => null,
                'nullable' => true,
            ],
        ],
        'color3' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.color3',
            'config' => [
                'type' => 'text',
                'cols' => '46',
                'rows' => '5',
                'default' => null,
                'nullable' => true,
            ],
        ],
        'size' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.size',
            'config' => [
                'type' => 'text',
                'cols' => '46',
                'rows' => '5',
                'default' => null,
                'nullable' => true,
            ],
        ],
        'size2' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.size2',
            'config' => [
                'type' => 'text',
                'cols' => '46',
                'rows' => '5',
                'default' => null,
                'nullable' => true,
            ],
        ],
        'size3' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.size3',
            'config' => [
                'type' => 'text',
                'cols' => '46',
                'rows' => '5',
                'default' => null,
                'nullable' => true,
            ],
        ],
        'description' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.description',
            'config' => [
                'type' => 'text',
                'cols' => '46',
                'rows' => '5',
                'default' => null,
                'nullable' => true,
            ],
        ],
        'gradings' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.gradings',
            'config' => [
                'type' => 'text',
                'cols' => '46',
                'rows' => '5',
                'default' => null,
                'nullable' => true,
            ],
        ],
        'material' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.material',
            'config' => [
                'type' => 'text',
                'cols' => '46',
                'rows' => '5',
                'default' => null,
                'nullable' => true,
            ],
        ],
        'quality' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.quality',
            'config' => [
                'type' => 'text',
                'cols' => '46',
                'rows' => '5',
                'default' => null,
                'nullable' => true,
            ],
        ],
        'additional_type' => [
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.additional_type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => '', 'value' => ''],
                ],
                'default' => '',
                'authMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode'] ?? 'explicitAllow',
            ],
        ],
        'additional' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.additional',
            'config' => [
                'type' => 'flex',
                'ds_pointerField' => 'additional_type',
                'ds' => [
                    'default' => '
						<T3DataStructure>
							<ROOT>
								<type>array</type>
								<el>
								<isSingle>
									<TCEforms>
										<label>LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.additional.isSingle</label>
										<config>
											<type>check</type>
										</config>
									</TCEforms>
								</isSingle>
								<isImage>
									<TCEforms>
										<label>LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.additional.isImage</label>
										<config>
											<type>check</type>
										</config>
									</TCEforms>
								</isImage>
								<alwaysInStock>
									<TCEforms>
										<label>LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.additional.alwaysInStock</label>
										<config>
											<type>check</type>
										</config>
									</TCEforms>
								</alwaysInStock>
								<noMinPrice>
									<TCEforms>
										<label>LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.additional.noMinPrice</label>
										<config>
											<type>check</type>
										</config>
									</TCEforms>
								</noMinPrice>
								<noMaxPrice>
									<TCEforms>
										<label>LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.additional.noMaxPrice</label>
										<config>
											<type>check</type>
										</config>
									</TCEforms>
								</noMaxPrice>
								<noGiftService>
									<TCEforms>
										<label>LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.additional.noGiftService</label>
										<config>
											<type>check</type>
										</config>
									</TCEforms>
								</noGiftService>
								</el>
							</ROOT>
							<meta>
								<langDisable>1</langDisable>
							</meta>
						</T3DataStructure>
						',
                ],
                'nullable' => true,
            ],
        ],
        'special_preparation' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.special_preparation',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'shipping' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.shipping',
            'config' => [
                'type' => 'number',
                'size' => '10',
                'max' => '20',
                'eval' => 'trim',
                'default' => 0,
                'format' => 'decimal',
            ],
        ],
        'shipping2' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.shipping2',
            'config' => [
                'type' => 'number',
                'size' => '10',
                'max' => '20',
                'eval' => 'trim',
                'default' => 0,
                'format' => 'decimal',
            ],
        ],
        'handling' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.handling',
            'config' => [
                'type' => 'number',
                'size' => '10',
                'max' => '20',
                'eval' => 'trim',
                'default' => 0,
                'format' => 'decimal',
            ],
        ],
        'delivery' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.delivery',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.delivery.availableNot', 'value' => '-1'],
                    ['label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.delivery.availableDemand', 'value' => '0'],
                    ['label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.delivery.availableImmediate', 'value' => '1'],
                    ['label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.delivery.availableShort', 'value' => '2'],
                ],
                'size' => '6',
                'minitems' => 0,
                'maxitems' => 1,
                'default' => 0,
            ],
        ],
        'sellstarttime' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.sellstarttime',
            'config' => [
                'type' => 'datetime',
                'size' => '8',
                'default' => 0,
                'format' => 'date',
            ],
        ],
        'sellendtime' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.sellendtime',
            'config' => [
                'type' => 'datetime',
                'size' => '8',
                'default' => 0,
                'range' => [
                    'upper' => mktime(0, 0, 0, 12, 31, 2300),
                    'lower' => mktime(0, 0, 0, date('n') - 1, date('d'), date('Y')),
                ],
                'format' => 'date',
            ],
        ],
    ],
    'types' => [
        '0' => [
            'columnsOverrides' => [
                'note' => [
                    'config' => [
                        'enableRichtext' => '1',
                    ],
                ],
                'note2' => [
                    'config' => [
                        'enableRichtext' => '1',
                    ],
                ],
            ],

            'showitem' => 'title,--palette--;;7, itemnumber,--palette--;;2, slug, price,--palette--;;3, tax,--palette--;;4, deposit,--palette--;;5,offer,--palette--;;6,weight,--palette--;;8, hidden,
                --palette--;;1,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.access,
                    --palette--;;access,' .
                '--div--;LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.descriptions,note, note2,note_uid,text_uid,image_uid,smallimage_uid,datasheet_uid,' .
                '--div--;LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.categorydiv, category, syscat,' .
                '--div--;LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.variants,color,color2,--palette--;;9,size,size2,--palette--;;10,description,gradings,material,quality,--palette--;;,additional,--palette--;;11,' .
                '--div--;LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.graduated,graduated_price_enable,graduated_price_round,graduated_price_uid,' .
                '--div--;LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.relations,article_uid,related_uid,accessory_uid,download_info,download_uid,' .
                '--div--;LLL:EXT:' . $extensionKey . $languageSubpath . 'locallang_db.xlf:' . $table . '.shippingdiv,shipping_point,shipping,shipping2,handling,delivery,' .
            '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,--palette--;;language,'
        ],
    ],
    'palettes' => [
        '1' => ['showitem' => 'sellstarttime,sellendtime'],
        '2' => ['showitem' => 'inStock,basketminquantity,basketmaxquantity,ean'],
        '3' => ['showitem' => 'price2,discount,discount_disable,directcost'],
        '4' => ['showitem' => 'tax_dummy'],
        '5' => ['showitem' => 'creditpoints'],
        '6' => ['showitem' => 'highlight,bargain'],
        '7' => ['showitem' => 'subtitle,keyword,www'],
        '8' => ['showitem' => 'bulkily,special_preparation,unit,unit_factor'],
        '9' => ['showitem' => 'color3'],
        '10' => ['showitem' => 'size3'],
        '11' => ['showitem' => 'usebydate'],
        'language' => [
            'showitem' => '
                sys_language_uid,l10n_parent,
            ',
        ],
        'access' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.access',
            'showitem' => 'starttime;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.starttime_formlabel, endtime;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.endtime_formlabel, --linebreak--, fe_group;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.fe_group_formlabel, --linebreak--',
        ],
    ],
];
