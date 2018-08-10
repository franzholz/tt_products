<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

$whereCategory = '';

if (
	isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['where.']) &&
	is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['where.']) &&
	isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['where.']['category'])
) {
	$whereCategory = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['where.']['category'];
}

$imageFolder = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['imageFolder'];
if (!$imageFolder) {
	$imageFolder = 'uploads/pics';
}


$result = array(
	'ctrl' => array(
		'title' =>'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products',
		'label' => 'title',
		'label_alt' => 'subtitle',
		'default_sortby' => 'ORDER BY title',
		'tstamp' => 'tstamp',
		'prependAtCopy' => DIV2007_LANGUAGE_LGL . 'prependAtCopy',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'delete' => 'deleted',
		'enablecolumns' => Array (
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
			'fe_group' => 'fe_group',
		),
		'thumbnail' => 'image',
		'useColumnsForDefaultValues' => 'category',
		'mainpalette' => 1,
		'iconfile' => PATH_TTPRODUCTS_ICON_TABLE_REL . 'tt_products.gif',
		'dividers2tabs' => '1',
		'searchFields' => 'uid,title,subtitle,itemnumber,ean,note,note2,www',
	),
	'interface' => array (
		'showRecordFieldList' => 'hidden,starttime,endtime,fe_group,title,subtitle,keyword,accessory_uid,related_uid,itemnumber,ean,shipping_point,price,price2,discount,discount_disable,tax,creditpoints,graduated_price_uid,article_uid,note,note2,note_uid,text_uid,category,address,inStock,basketminquantity,weight,usebydate,bulkily,offer,highlight,bargain,directcost,color,color2,color3,size,size2,size3,description,gradings,material,quality,additional,unit,unit_factor,www,datasheet,special_preparation,image,smallimage,sellstarttime,sellendtime,shipping,shipping2,handling'
	),
	'columns' => array (
		't3ver_label' => array (
			'label'  => 'LLL:EXT:lang/locallang_general.xml:LGL.versionLabel',
			'config' => array (
				'type' => 'input',
				'size' => '30',
				'max'  => '30',
			)
		),
		'sorting' => Array (
			'config' => Array (
				'type' => 'passthrough',
			)
		),
		'hidden' => array (
			'exclude' => 1,
			'label' => DIV2007_LANGUAGE_LGL . 'hidden',
			'config' => array (
				'type' => 'check'
			)
		),
		'tstamp' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tstamp',
			'config' => array (
				'type' => 'input',
				'size' => '8',
				'eval' => 'date',
                'renderType' => 'inputDateTime',
				'default' => '0'
			)
		),
		'crdate' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:crdate',
			'config' => array (
				'type' => 'input',
				'size' => '8',
				'eval' => 'date',
                'renderType' => 'inputDateTime',
				'default' => '0'
			)
		),
		'starttime' => array (
			'exclude' => 1,
			'label' => DIV2007_LANGUAGE_LGL . 'starttime',
			'config' => array (
				'type' => 'input',
				'size' => '8',
				'eval' => 'date',
                'renderType' => 'inputDateTime',
				'default' => '0'
			)
		),
		'endtime' => array (
			'exclude' => 1,
			'label' => DIV2007_LANGUAGE_LGL . 'endtime',
			'config' => array (
				'type' => 'input',
				'size' => '8',
				'eval' => 'date',
                'renderType' => 'inputDateTime',
				'default' => '0',
				'range' => array (
					'upper' => mktime(0, 0, 0, 12, 31, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['endtimeYear']),
					'lower' => mktime(0, 0, 0, date('n') - 1, date('d'), date('Y'))
				)
			)
		),
		'fe_group' => array (
			'exclude' => 1,
			'label' => DIV2007_LANGUAGE_LGL . 'fe_group',
			'config' => array (
				'type' => 'select',
				'renderType' => 'selectSingle',
				'items' => array (
					array('', 0),
					array(DIV2007_LANGUAGE_LGL . 'hide_at_login', -1),
					array(DIV2007_LANGUAGE_LGL . 'any_login', -2),
					array(DIV2007_LANGUAGE_LGL . 'usergroups', '--div--')
				),
				'foreign_table' => 'fe_groups'
			)
		),
		'title' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.title',
			'config' => array (
				'type' => 'input',
				'size' => '40',
				'max' => '256',
				'default' => ' ',
			)
		),
		'subtitle' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.subtitle',
			'config' => array (
				'type' => 'text',
				'rows' => '3',
				'cols' => '20',
				'max' => '512',
				'default' => NULL,
			)
		),
		'keyword' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.keyword',
			'config' => array (
				'type' => 'text',
				'rows' => '5',
				'cols' => '20',
				'max' => '512',
				'eval' => 'null',
				'default' => NULL,
			)
		),
		'prod_uid' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.prod_uid',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tt_products',
				'foreign_table' => 'tt_products',
				'foreign_table_where' => ' ORDER BY tt_products.uid',
				'size' => 3,
				'minitems' => 0,
				'maxitems' => 3,
			)
		),
		'itemnumber' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.itemnumber',
			'config' => array (
				'type' => 'input',
				'size' => '40',
				'eval' => 'trim',
				'max' => '120'
			)
		),
		'ean' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.ean',
			'config' => array (
				'type' => 'input',
				'size' => '48',
				'eval' => 'trim',
				'max' => '48'
			)
		),
		'shipping_point' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.shipping_point',
			'config' => array (
				'type' => 'input',
				'size' => '24',
				'eval' => 'trim',
				'max' => '24'
			)
		),
		'price' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.price',
			'config' => array (
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim,double2',
				'max' => '20'
			)
		),
		'price2' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.price2',
			'config' => array (
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim,double2',
				'max' => '20'
			)
		),
		'discount' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.discount',
			'config' => array (
				'type' => 'input',
				'size' => '4',
				'max' => '8',
				'eval' => 'trim,double2',
				'range' => array (
					'upper' => '1000',
					'lower' => '0'
				),
				'default' => 0
			)
		),
		'discount_disable' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.discount_disable',
			'config' => array (
				'type' => 'check',
			)
		),
		'tax' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.tax',
			'config' => array (
				'type' => 'input',
				'size' => '12',
				'max' => '19',
				'eval' => 'trim,double2'
			)
		),
		'creditpoints' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.creditpoints',
			'config' => array (
				'type' => 'input',
				'size' => '12',
				'eval' => 'int',
				'max' => '12'
			)
		),
		'graduated_price_uid' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.graduated_price_uid',
			'config' => array (
				'type' => 'inline',
				'appearance' => array ('collapseAll' => TRUE, 'newRecordLinkAddTitle' => TRUE, 'useCombination' => TRUE),
				'foreign_table' => 'tt_products_mm_graduated_price',
				'foreign_field' => 'product_uid',
				'foreign_sortby' => 'productsort',
				'foreign_label' => 'graduated_price_uid',
				'foreign_selector' => 'graduated_price_uid',
				'foreign_unique' => 'graduated_price_uid',
				'maxitems' => 10
			),
		),
		'article_uid' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.article_uid',
			'config' => array (
				'type' => 'inline',
				'appearance' => array ('collapseAll' => TRUE, 'newRecordLinkAddTitle' => TRUE, 'useCombination' => TRUE),
				'foreign_table' => 'tt_products_products_mm_articles',
				'foreign_field' => 'uid_local',
				'foreign_sortby' => 'sorting',
				'foreign_label' => 'uid_foreign',
				'foreign_selector' => 'uid_foreign',
				'foreign_unique' => 'uid_foreign',
				'maxitems' => 1000
			),
		),
		'note' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.note',
			'config' => array (
				'type' => 'text',
				'cols' => '48',
				'rows' => '5',
				'default' => NULL,
			)
		),
		'note2' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.note2',
			'config' => array (
				'type' => 'text',
				'cols' => '48',
				'rows' => '2',
				'default' => NULL,
			)
		),
		'note_uid' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.note_uid',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'pages',
				'MM' => 'tt_products_products_note_pages_mm',
				'size' => '2',
				'autoSizeMax' => '12',
				'minitems' => '0',
				'maxitems' => '30',
				'show_thumbs' => '1',
			),
		),
		'text_uid' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.text_uid',
			'config' => array (
				'type' => 'inline',
				'foreign_table' => 'tt_products_texts',
				'foreign_field' => 'parentid',
				'foreign_table_field' => 'parenttable',
				'maxitems' => 20
			),
		),
		'unit_factor' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.unit_factor',
			'config' => array (
				'type' => 'input',
				'size' => '10',
				'eval' => 'double',
				'default' => '1',
				'max' => '6'
			)
		),
		'unit' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.unit',
			'config' => array (
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '20'
			)
		),
		'www' => array (
			'exclude' => 1,
			'label' => DIV2007_LANGUAGE_LGL . 'www',
			'config' => array (
				'type' => 'input',
				'eval' => 'trim',
				'size' => '30',
				'max' => '160'
			)
		),
		'category' => array (
			'exclude' => 1,
			'label' => DIV2007_LANGUAGE_LGL . 'category',
			'config' => array (
				'type' => 'select',
				'renderType' => 'selectSingle',
				'items' => array (
					array('', 0)
				),
				'foreign_table' => 'tt_products_cat',
				'foreign_table_where' => $whereCategory
			)
		),
		'inStock' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.inStock',
			'config' => array (
				'type' => 'input',
				'size' => '6',
				'max' => '6',
				'eval' => 'int',
				'default' => '1'
			)
		),
		'basketminquantity' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.basketminquantity',
			'config' => array (
				'type' => 'input',
				'size' => '10',
				'eval' => 'trim,double2',
				'max' => '10'
			)
		),
		'datasheet' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.datasheet',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => 'doc,htm,html,pdf,sxw,txt,xls,gif,jpg,png',
				'max_size' => $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],
				'uploadfolder' => 'uploads/tx_ttproducts/datasheet',
				'show_thumbs' => '1',
				'size' => '3',
				'maxitems' => '20',
				'minitems' => '0',
				'eval' => 'null',
				'default' => NULL,
			)
		),
		'weight' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.weight',
			'config' => array (
				'type' => 'input',
				'size' => '10',
				'max' => '20',
				'eval' => 'trim,tx_double6',
			)
		),
		'usebydate' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.usebydate',
			'config' => array (
				'type' => 'input',
				'size' => '8',
				'eval' => 'date',
                'renderType' => 'inputDateTime',
				'default' => '0'
			)
		),
		'bulkily' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.bulkily',
			'config' => array (
				'type' => 'check',
			)
		),
		'offer' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.offer',
			'config' => array (
				'type' => 'check',
			)
		),
		'highlight' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.highlight',
			'config' => array (
				'type' => 'check',
			)
		),
		'bargain' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.bargain',
			'config' => array (
				'type' => 'check',
			)
		),
		'directcost' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.directcost',
			'config' => array (
				'type' => 'input',
				'size' => '12',
				'eval' => 'trim,double2',
				'max' => '20'
			)
		),
		'accessory_uid' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.accessory_uid',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tt_products',
				'MM' => 'tt_products_accessory_products_products_mm',
				'foreign_table' => 'tt_products',
				'foreign_table_where' => ' ORDER BY tt_products.uid',
				'size' => 10,
				'minitems' => 0,
				'maxitems' => 12,
			),
		),
		'related_uid' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.related_uid',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tt_products',
				'MM' => 'tt_products_related_products_products_mm',
				'foreign_table' => 'tt_products',
				'foreign_table_where' => ' ORDER BY tt_products.uid',
				'size' => 10,
				'minitems' => 0,
				'maxitems' => 50,
			),
		),
		'color' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.color',
			'config' => array (
				'type' => 'text',
				'cols' => '46',
				'rows' => '5',
				'eval' => 'null',
				'default' => NULL,
			)
		),
		'color2' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.color2',
			'config' => array (
				'type' => 'text',
				'cols' => '46',
				'rows' => '5',
				'eval' => 'null',
				'default' => NULL,
			)
		),
		'color3' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.color3',
			'config' => array (
				'type' => 'text',
				'cols' => '46',
				'rows' => '5',
				'eval' => 'null',
				'default' => NULL,
			)
		),
		'size' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.size',
			'config' => array (
				'type' => 'text',
				'cols' => '46',
				'rows' => '5',
				'eval' => 'null',
				'default' => NULL,
			)
		),
		'size2' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.size2',
			'config' => array (
				'type' => 'text',
				'cols' => '46',
				'rows' => '5',
				'eval' => 'null',
				'default' => NULL,
			)
		),
		'size3' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.size3',
			'config' => array (
				'type' => 'text',
				'cols' => '46',
				'rows' => '5',
				'eval' => 'null',
				'default' => NULL,
			)
		),
		'description' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.description',
			'config' => array (
				'type' => 'text',
				'cols' => '46',
				'rows' => '5',
				'eval' => 'null',
				'default' => NULL,
			)
		),
		'gradings' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.gradings',
			'config' => array (
				'type' => 'text',
				'cols' => '46',
				'rows' => '5',
				'eval' => 'null',
				'default' => NULL,
			)
		),
		'material' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.material',
			'config' => array (
				'type' => 'text',
				'cols' => '46',
				'rows' => '5',
				'eval' => 'null',
				'default' => NULL,
			)
		),
		'quality' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.quality',
			'config' => array (
				'type' => 'text',
				'cols' => '46',
				'rows' => '5',
				'eval' => 'null',
				'default' => NULL,
			)
		),
		'additional_type' => array (
			'label' => 'LLL:EXT:cms/locallang_ttc.php:list_type',
			'config' => array (
				'type' => 'select',
				'renderType' => 'selectSingle',
				'items' => array (
					array('', '')
				),
				'default' => '',
				'authMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode'],
			)
		),
		'additional' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.additional',
			'config' => array (
				'type' => 'flex',
				'ds_pointerField' => 'additional_type',
				'ds' => array (
					'default' => '
						<T3DataStructure>
							<ROOT>
								<type>array</type>
								<el>
								<isSingle>
									<TCEforms>
										<label>LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.additional.isSingle</label>
										<config>
											<type>check</type>
										</config>
									</TCEforms>
								</isSingle>
								<isImage>
									<TCEforms>
										<label>LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.additional.isImage</label>
										<config>
											<type>check</type>
										</config>
									</TCEforms>
								</isImage>
								<alwaysInStock>
									<TCEforms>
										<label>LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.additional.alwaysInStock</label>
										<config>
											<type>check</type>
										</config>
									</TCEforms>
								</alwaysInStock>
								<noMinPrice>
									<TCEforms>
										<label>LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.additional.noMinPrice</label>
										<config>
											<type>check</type>
										</config>
									</TCEforms>
								</noMinPrice>
								<noMaxPrice>
									<TCEforms>
										<label>LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.additional.noMaxPrice</label>
										<config>
											<type>check</type>
										</config>
									</TCEforms>
								</noMaxPrice>
								<noGiftService>
									<TCEforms>
										<label>LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.additional.noGiftService</label>
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
				),
				'eval' => 'null',
			)
		),
		'special_preparation' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.special_preparation',
			'config' => array (
				'type' => 'check'
			)
		),
		'image' => array (
			'exclude' => 1,
			'label' => DIV2007_LANGUAGE_LGL . 'image',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
				'max_size' => $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],
				'uploadfolder' => $imageFolder,
				'size' => '5',
				'maxitems' => '30',
				'minitems' => '0',
				'default' => NULL,
			)
		),
		'smallimage' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.smallimage',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
				'max_size' => $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],
				'uploadfolder' => $imageFolder,
				'size' => '5',
				'maxitems' => '30',
				'minitems' => '0',
				'eval' => 'null',
				'default' => NULL,
			)
		),
		'shipping' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.shipping',
			'config' => array (
				'type' => 'input',
				'size' => '10',
				'max' => '20',
				'eval' => 'trim,double2',
			)
		),
		'shipping2' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.shipping2',
			'config' => array (
				'type' => 'input',
				'size' => '10',
				'max' => '20',
				'eval' => 'trim,double2',
			)
		),
		'handling' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.handling',
			'config' => array (
				'type' => 'input',
				'size' => '10',
				'max' => '20',
				'eval' => 'trim,double2',
			)
		),
		'delivery' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.delivery',
			'config' => array (
				'type' => 'select',
				'renderType' => 'selectSingle',
				'items' => array (
					array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.delivery.availableNot', '-1'),
					array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.delivery.availableDemand', '0'),
					array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.delivery.availableImmediate', '1'),
					array ('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.delivery.availableShort', '2')
				),
				'size' => '6',
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'sellstarttime' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.sellstarttime',
			'config' => array (
				'type' => 'input',
				'size' => '8',
				'eval' => 'date',
                'renderType' => 'inputDateTime',
				'default' => '0'
			)
		),
		'sellendtime' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.sellendtime',
			'config' => array (
				'type' => 'input',
				'size' => '8',
				'eval' => 'date',
                'renderType' => 'inputDateTime',
				'default' => '0',
				'range' => array (
					'upper' => mktime(0, 0, 0, 12, 31, 2300),
					'lower' => mktime(0, 0, 0, date('n') - 1, date('d'), date('Y'))
				)
			)
		),
	),
	'types' => array (
		'0' =>
            array(
                'columnsOverrides' => array(
                    'note' => array(
                        'config' => array(
                            'enableRichtext' => '1'
                        )
                    ),
                    'note2' => array(
                        'config' => array(
                            'enableRichtext' => '1'
                        )
                    )
                ),

                'showitem' => 'title,--palette--;;7, itemnumber,--palette--;;2, category, price,--palette--;;3, tax;;4, offer,--palette--;;6,weight,--palette--;;8,creditpoints,hidden,--palette--;;1,' .
			'--div--;LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.descriptions,note, note2,note_uid,text_uid,image,smallimage,datasheet,'.
			'--div--;LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.variants,color,color2,--palette--;;9,size,size2,--palette--;;10,description,gradings,material,quality,--palette--;;,additional,--palette--;;11,'.
			'--div--;LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.graduated,graduated_price_uid,'.
			'--div--;LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.relations,article_uid,related_uid,accessory_uid,download_info,download_uid,'.
			'--div--;LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml:tt_products.shippingdiv,shipping_point,shipping,shipping2,handling,delivery,'
        )
	),
	'palettes' => array (
		'1' =>
			array('showitem' => 'sellstarttime,sellendtime,starttime,endtime,fe_group'),
		'2' =>
			array('showitem' => 'inStock,basketminquantity,ean'),
		'3' =>
			array('showitem' => 'price2,discount,discount_disable,directcost'),
		'4' =>
			array('showitem' => 'tax_dummy'),
		'6' =>
			array('showitem' => 'highlight,bargain'),
		'7' =>
			array('showitem' => 'subtitle,keyword,www'),
		'8' =>
			array('showitem' => 'bulkily,special_preparation,unit,unit_factor'),
		'9' =>
			array('showitem' => 'color3'),
		'10' =>
			array('showitem' => 'size3'),
		'11' =>
			array('showitem' => 'usebydate')
	)
);


if (
    version_compare(TYPO3_version, '8.5.0', '<')
) {
    $result['types']['0']['showitem'] =
        preg_replace(
            '/(^|,)\s*note\s*(,|$)/', '$1 note;;;richtext[]:rte_transform[mode=ts_css|imgpath=uploads/tx_ttproducts/rte/] $2',
            $result['types']['0']['showitem']
        );

    $result['types']['0']['showitem'] =
        preg_replace(
            '/(^|,)\s*note2\s*(,|$)/', '$1 note2;;;richtext[]:rte_transform[mode=ts_css|imgpath=uploads/tx_ttproducts/rte/] $2',
            $result['types']['0']['showitem']
        );
}



return $result;

