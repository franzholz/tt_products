<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Franz Holzinger (franz@ttproducts.de)
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
		$ajax,
		&$errorCode,
		array $recs = array(),
		array $basketRec = array()
	) {
        if (version_compare(PHP_VERSION, '8.0.0') >= 0) {
            $staticInfoApi = GeneralUtility::makeInstance(\JambageCom\Div2007\Api\StaticInfoTablesApi::class);
        } else {
            $staticInfoApi = GeneralUtility::makeInstance(\JambageCom\Div2007\Api\OldStaticInfoTablesApi::class);
        }

        $useStaticInfoTables = $staticInfoApi->init();

		if (!empty($conf['PIDstoreRoot'])) {
			$config['storeRootPid'] = $conf['PIDstoreRoot'];
		} else if (TYPO3_MODE == 'FE') {
			foreach ($GLOBALS['TSFE']->tmpl->rootLine as $k => $row) {
				if ($row['doktype'] == 1) {
					$config['storeRootPid'] = $row['uid'];
					break;
				}
			}
		}

		if ($conf['pid_list'] == '{$plugin.tt_products.pid_list}') {
			$conf['pid_list'] = '';
		}

		if ($conf['errorLog'] == '{$plugin.tt_products.file.errorLog}') {
			$conf['errorLog'] = '';
		} else if ($conf['errorLog']) {
			$conf['errorLog'] = GeneralUtility::resolveBackPath(PATH_typo3conf . '../' . $conf['errorLog']);
		}

		$tmp = $cObj->stdWrap($conf['pid_list'], $conf['pid_list.'] ?? '');
		$pid_list = ($cObj->data['pages'] ? $cObj->data['pages'] : (!empty($conf['pid_list.']) ? trim($tmp) : ''));
		$pid_list = ($pid_list ? $pid_list : $conf['pid_list']);
		$config['pid_list'] = (isset($pid_list) ? $pid_list : $config['storeRootPid']);

		$recursive = ($cObj->data['recursive'] ? $cObj->data['recursive']: $conf['recursive']);
		$config['recursive'] = tx_div2007_core::intInRange($recursive, 0, 100);

		if (is_object($pObj)) {
			$pLangObj = $pObj;
		} else {
			$pLangObj = $this;
		}
        $languageObj = static::getLanguageObj($pLangObj, $cObj, $conf);
 		$config['LLkey'] = $languageObj->getLocalLangKey(); /* $pibaseObj->LLkey; */

        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
		$markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
		$result = $markerObj->init(
			$conf,
			tx_ttproducts_model_control::getPiVars()
		);

		if ($result == false) {
			$errorCode = $markerObj->getErrorCode();
			return false;
		}
		tx_ttproducts_control_basket::init(
			$conf,
			$tablesObj,
			$config['pid_list'],
			$conf['useArticles'],
			$recs,
			$basketRec
		);

		// corrections in the Setup:
		if (
			\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('voucher') &&
			isset($conf['gift.']) &&
			$conf['gift.']['type'] == 'voucher'
		) {
			$conf['table.']['voucher'] = 'tx_voucher_codes';
		}


		$cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
		$cnfObj->init(
			$conf,
			$config
		);

		\JambageCom\TtProducts\Api\ControlApi::init($conf, $cObj);
		$infoArray = tx_ttproducts_control_basket::getStoredInfoArray();
		if ($conf['useStaticInfoCountry']) {
			tx_ttproducts_control_basket::setCountry(
				$infoArray,
				tx_ttproducts_control_basket::getBasketExtra()
			);
		}

		tx_ttproducts_control_basket::addLoginData(
			$infoArray,
			$conf['loginUserInfoAddress'],
			$conf['useStaticInfoCountry']
		);

		tx_ttproducts_control_basket::setInfoArray($infoArray);

			// price
		$priceObj = GeneralUtility::makeInstance('tx_ttproducts_field_price');
		$priceObj->init(
			$cObj,
			$conf
		);
		$priceViewObj = GeneralUtility::makeInstance('tx_ttproducts_field_price_view');
		$priceViewObj->init(
			$priceObj
		);

			// image
		$imageObj = GeneralUtility::makeInstance('tx_ttproducts_field_image');
		$imageObj->init($cObj);

			// image view
		$imageViewObj = GeneralUtility::makeInstance('tx_ttproducts_field_image_view');
		$imageViewObj->init($imageObj);

		$cssObj = GeneralUtility::makeInstance('tx_ttproducts_css');
		$cssObj->init();

		$javaScriptObj = GeneralUtility::makeInstance('tx_ttproducts_javascript');
			// JavaScript
		$javaScriptObj->init(
			$ajax
		);

		$templateObj = GeneralUtility::makeInstance('tx_ttproducts_template');
		$templateObj->setTemplateSuffix($config['templateSuffix']);

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

		return true;
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
            'EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'locallang_db.xlf',
            false
        );
        $languageObj->loadLocalLang(
            'EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'PiSearch/locallang_db.xlf',
            false
        );
        $languageObj->loadLocalLang(
            'EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'Pi1/locallang.xlf',
            false
        );

        return $languageObj;
    }

	public function destruct () {
		tx_ttproducts_control_basket::destruct();
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/control/class.tx_ttproducts_control_creator.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/x']);
}

