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

use TYPO3\CMS\Core\Utility\GeneralUtility;


class tx_ttproducts_control_creator implements \TYPO3\CMS\Core\SingletonInterface {

	public function init (
		&$conf,
		&$config,
		$pObj,
		$cObj,
		&$errorCode,
		array $recs = array(),
		array $basketRec = array()
	) {
        $staticInfoApi = GeneralUtility::makeInstance(\JambageCom\Div2007\Api\StaticInfoTablesApi::class);
        $useStaticInfoTables = $staticInfoApi->init();

		if ($conf['errorLog'] == '{$plugin.tt_products.file.errorLog}') {
			$conf['errorLog'] = '';
		} else if ($conf['errorLog']) {
			$conf['errorLog'] = GeneralUtility::resolveBackPath(PATH_typo3conf . '../' . $conf['errorLog']);
		}

		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
		$tablesObj->init();

		if (is_object($pObj))	{
			$pLangObj = &$pObj;
		} else {
			$pLangObj = &$this;
		}
		$languageObj = static::getLanguageObj($pLangObj, $cObj, $conf);
 		$config['LLkey'] = $languageObj->getLocalLangKey(); /* $pibaseObj->LLkey; */

		$markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
		$res = $markerObj->init(
			$cObj,
			$pObj->piVars,
			$conf,
			$config
		);

		if ($res == false)	{
			$errorCode = $markerObj->getErrorCode();
			return false;
		}

		tx_ttproducts_control_basket::init(
			$conf,
			$tablesObj,
			$recs,
			$basketRec
		);

		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$cnf->init(
			$conf,
			$config
		);
		\JambageCom\TtProducts\Api\ControlApi::init($conf, $cObj);

			// Call all init hooks
		if (
			isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['init']) &&
			is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['init'])
		) {
			$tableClassArray = $tablesObj->getTableClassArray();

			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['init'] as $classRef) {
				$hookObj= GeneralUtility::makeInstance($classRef);
				if (method_exists($hookObj, 'init')) {
					$hookObj->init($languageObj, $tableClassArray);
				}
			}
			$tablesObj->setTableClassArray($tableClassArray);
		}
	}

    static public function getLanguageObj ($pLangObj, $cObj, $conf) {

        $languageObj = GeneralUtility::makeInstance(\JambageCom\TtProducts\Api\Localization::class);
        $confLocalLang = array();
        if (isset($conf['_LOCAL_LANG.'])) {
            $confLocalLang = $conf['_LOCAL_LANG.'];
        }
        if (isset($conf['marks.'])) {
            $confLocalLang = array_merge($confLocalLang, $conf['marks.']);
        }
        $languageObj->init(
            TT_PRODUCTS_EXT,
            $confLocalLang,
            DIV2007_LANGUAGE_SUBPATH
        );

        $languageObj->loadLocalLang(
            'EXT:' . TT_PRODUCTS_EXT . '/locallang_db.xlf',
            false
        );
        $languageObj->loadLocalLang(
            'EXT:' . TT_PRODUCTS_EXT . '/pi_search/locallang_db.xlf',
            false
        );
        $languageObj->loadLocalLang(
            'EXT:' . TT_PRODUCTS_EXT . '/pi1/locallang.xlf',
            false
        );

        return $languageObj;
    }

	public function destruct () {
		tx_ttproducts_control_basket::destruct();
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/control/class.tx_ttproducts_control_creator.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/control/class.tx_ttproducts_control_creator.php']);
}
