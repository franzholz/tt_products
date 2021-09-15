<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "tt_products".
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Shop System',
	'description' => 'New versions at ttproducts.de. Documented in E-Book "Der TYPO3-Webshop" - Shop with listing in multiple languages, with order tracking, product variants, credit card payment and bank accounts, bill, creditpoint, voucher system and gift certificates. Latest updates at ttproducts.de.',
	'category' => 'plugin',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => 'uploads/tx_ttproducts/datasheet,fileadmin/data/bill,fileadmin/data/delivery,fileadmin/img',
	'clearCacheOnLoad' => 1,
	'author' => 'Franz Holzinger',
	'author_email' => 'franz@ttproducts.de',
	'author_company' => 'jambage.com',
	'version' => '2.9.19',
	'constraints' => array(
		'depends' => array(
			'div2007' => '1.11.5-0.0.0',
			'php' => '5.5.0-7.4.99',
			'table' => '0.7.7-0.0.0',
			'tsparser' => '0.2.5-0.0.0',
			'typo3' => '7.6.0-10.4.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
			'func' => '',
			'addons_em' => '0.2.1-0.0.0',
            'typo3db_legacy' => '1.0.0-1.1.99',
			'taxajax' => '',
		)
	)
);

