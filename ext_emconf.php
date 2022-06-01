<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "tt_products".
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
  'title' => 'Shop System',
  'description' => 'Advanced versions at ttproducts.de. Documented in the E-Book "Der TYPO3-Webshop" - Shop with listing in multiple languages, with order tracking, photo gallery, DAM, product variants, credit card payment and bank accounts, bill, creditpoint, voucher system and gift certificates.',
  'state' => 'stable',
  'version' => '2.14.4',
  'manual' => true,
  'author' => 'Franz Holzinger',
  'author_company' => 'jambage.com',
  'author_email' => 'franz@ttproducts.de',
  'category' => 'plugin',
  'constraints' => 
  [
    'depends' => 
    [
      'div2007' => '1.14.0-1.14.99',
      'filelist' => '',
      'php' => '7.4.0-8.1.99',
      'table' => '0.7.5-0.0.0',
      'tsparser' => '0.2.5-0.0.0',
      'typo3db_legacy' => '1.0.0-1.1.99',
      'typo3' => '10.4.0-11.5.99',
    ],
    'conflicts' => 
    [
    ],
    'suggests' => 
    [
      'addons_em' => '0.1.0-0.0.0',
      'func' => '',
      'static_info_tables_taxes' => '0.3.0-0.4.0',
      'taxajax' => ''
    ],
  ]
];

