<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "tt_products".
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
  'title' => 'Shop System',
  'description' => 'Advanced versions at ttproducts.de. Documented in the E-Book "Der TYPO3-Webshop" - Shop with listing in multiple languages, with order tracking, product variants, support for transactor extension, bill, creditpoint and voucher system.',
  'state' => 'stable',
  'version' => '2.14.19',
  'manual' => true,
  'author' => 'Franz Holzinger',
  'author_company' => 'jambage.com',
  'author_email' => 'franz@ttproducts.de',
  'category' => 'plugin',
  'constraints' => 
  [
    'depends' => 
    [
      'div2007' => '1.15.0-1.17.99',
      'filelist' => '10.4.0-12.4.99',
      'table' => '0.11.0-0.0.0',
      'tsparser' => '0.9.0-0.0.0',
      'typo3db_legacy' => '1.0.0-1.2.99',
      'typo3' => '10.4.0-11.5.99',
    ],
    'conflicts' => 
    [
    ],
    'suggests' => 
    [
      'addons_em' => '0.1.0-0.0.0',
      'func' => '',
      'static_info_tables' => '',
      'static_info_tables_taxes' => '0.3.0-0.8.99',
      'taxajax' => ''
    ],
  ]
];
