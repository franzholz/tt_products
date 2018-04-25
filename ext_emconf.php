<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "tt_products".
 ***************************************************************/


$EM_CONF[$_EXTKEY] = array(
	'title' => 'Shop System',
	'description' => 'New versions at ttproducts.de. Documented in E-Book "Der TYPO3-Webshop" - Shop with listing in multiple languages, with order tracking, photo gallery, DAM, product variants, configurable costs, credit card payment and bank accounts, bill, creditpoint, voucher system and gift certificates..',
	'category' => 'plugin',
	'shy' => 0,
	'dependencies' => 'cms,div2007,table,tsparser',
	'conflicts' => 'ast_rteproducts,onet_ttproducts_rte,c3bi_cookie_at_login',
	'suggests' => array(
	),
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => 0,
	'uploadfolder' => 0,
	'createDirs' => 'uploads/tx_ttproducts/datasheet,uploads/tx_ttproducts/rte,fileadmin/data/bill,fileadmin/data/delivery,fileadmin/img',
	'modify_tables' => '',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'author' => 'Franz Holzinger',
	'author_email' => 'franz@ttproducts.de',
	'author_company' => 'jambage.com',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'version' => '2.7.33',
	'constraints' => array(
		'depends' => array(
			'php' => '5.1.2-7.99.99',
			'typo3' => '4.5.0-7.99.99',
			'div2007' => '1.7.7-0.0.0',
			'table' => '0.3.0-0.0.0',
			'tsparser' => '',
		),
		'conflicts' => array(
			'ast_rteproducts' => '',
			'onet_ttproducts_rte' => '',
			'c3bi_cookie_at_login' => '',
		),
		'suggests' => array(
			'addons_em' => '',
		),
	),
);

?>
