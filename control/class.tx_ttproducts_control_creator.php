<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2008 Franz Holzinger (franz@ttproducts.de)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Part of the tt_products (Shop System) extension.
 *
 * class for control initialization
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


class tx_ttproducts_control_creator implements t3lib_Singleton {

	public function init (&$conf, &$config, $pObj, $cObj)  {

		if ($conf['errorLog'] == '{$plugin.tt_products.file.errorLog}') {
			$conf['errorLog'] = '';
		} else if ($conf['errorLog']) {
			$conf['errorLog'] = t3lib_div::resolveBackPath(PATH_typo3conf . '../' . $conf['errorLog']);
		}

		$cnf = t3lib_div::makeInstance('tx_ttproducts_config');
		$cnf->init(
			$conf,
			$config
		);

		$langObj = t3lib_div::makeInstance('tx_ttproducts_language');
		if (is_object($pObj))	{
			$pLangObj = &$pObj;
		} else {
			$pLangObj = &$this;
		}
		$langObj->init1($pLangObj, $cObj, $conf, 'pi1/class.tx_ttproducts_pi1.php');

		tx_div2007_alpha5::loadLL_fh002($langObj, 'EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xml');
		tx_div2007_alpha5::loadLL_fh002($langObj, 'EXT:' . TT_PRODUCTS_EXT . '/pi_search/locallang_db.xml');
		tx_div2007_alpha5::loadLL_fh002($langObj, 'EXT:' . TT_PRODUCTS_EXT . '/pi1/locallang.xml');

		$tablesObj = t3lib_div::makeInstance('tx_ttproducts_tables');
		$tablesObj->init($langObj);
			// Call all init hooks
		if (
			isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['init']) &&
			is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['init'])
		) {
			$tableClassArray = $tablesObj->getTableClassArray();

			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['init'] as $classRef) {
				$hookObj= t3lib_div::makeInstance($classRef);
				if (method_exists($hookObj, 'init')) {
					$hookObj->init($langObj, $tableClassArray);
				}
			}
			$tablesObj->setTableClassArray($tableClassArray);
		}
	}

	public function destruct () {
		tx_ttproducts_control_basket::destruct();
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/control/class.tx_ttproducts_control_creator.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/control/class.tx_ttproducts_control_creator.php']);
}


?>
