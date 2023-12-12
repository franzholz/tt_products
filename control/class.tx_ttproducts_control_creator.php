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
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage tt_products
 */
use JambageCom\Div2007\Api\OldStaticInfoTablesApi;
use JambageCom\Div2007\Api\StaticInfoTablesApi;
use JambageCom\TtProducts\Api\ControlApi;
use JambageCom\TtProducts\Api\Localization;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

class tx_ttproducts_control_creator implements SingletonInterface
{
    public function init(
        &$conf,
        &$config,
        $pObj,
        $cObj,
        $ajax,
        &$errorCode,
        array $recs = [],
        array $basketRec = []
    ) {
        if (version_compare(PHP_VERSION, '8.0.0') >= 0) {
            $staticInfoApi = GeneralUtility::makeInstance(StaticInfoTablesApi::class);
        } else {
            $staticInfoApi = GeneralUtility::makeInstance(OldStaticInfoTablesApi::class);
        }

        $useStaticInfoTables = $staticInfoApi->init();

        if (!empty($conf['PIDstoreRoot'])) {
            $config['storeRootPid'] = $conf['PIDstoreRoot'];
        } elseif (
            defined('TYPO3_MODE') &&
            TYPO3_MODE == 'FE'
        ) {
            foreach ($GLOBALS['TSFE']->tmpl->rootLine as $k => $row) {
                if ($row['doktype'] == 1) {
                    $config['storeRootPid'] = $row['uid'];
                    break;
                }
            }
        }

        if (
            !isset($conf['pid_list']) ||
            $conf['pid_list'] == '{$plugin.tt_products.pid_list}'
        ) {
            $conf['pid_list'] = '';
        }

        if (
            !isset($conf['errorLog']) ||
            $conf['errorLog'] == '{$plugin.tt_products.file.errorLog}'
        ) {
            $conf['errorLog'] = '';
        } elseif ($conf['errorLog']) {
            $conf['errorLog'] = GeneralUtility::resolveBackPath(Environment::getLegacyConfigPath() . '/../' . $conf['errorLog']);
        }

        $tmp = $cObj->stdWrap($conf['pid_list'] ?? '', $conf['pid_list.'] ?? '');
        $pid_list = (!empty($cObj->data['pages']) ? $cObj->data['pages'] : (!empty($conf['pid_list.']) ? trim($tmp) : ''));
        $pid_list = ($pid_list ?: $conf['pid_list'] ?? '');
        $config['pid_list'] = ($pid_list ?? $config['storeRootPid'] ?? 0);

        $recursive = (!empty($cObj->data['recursive']) ? $cObj->data['recursive'] : $conf['recursive'] ?? 99);
        $config['recursive'] = MathUtility::forceIntegerInRange($recursive, 0, 100);

        if (is_object($pObj)) {
            $pLangObj = $pObj;
        } else {
            $pLangObj = $this;
        }
        $languageObj = static::getLanguageObj($pLangObj, $cObj, $conf);
        $config['LLkey'] = $languageObj->getLocalLangKey();

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
            $conf['useArticles'] ?? 3,
            $recs,
            $basketRec
        );

        // corrections in the Setup:
        if (
            ExtensionManagementUtility::isLoaded('voucher') &&
            isset($conf['gift.']['type']) &&
            $conf['gift.']['type'] == 'voucher'
        ) {
            $conf['table.']['voucher'] = 'tx_voucher_codes';
        }

        $cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
        $cnfObj->init(
            $conf,
            $config
        );

        ControlApi::init($conf, $cObj);
        $infoArray = tx_ttproducts_control_basket::getStoredInfoArray();
        if (!empty($conf['useStaticInfoCountry'])) {
            tx_ttproducts_control_basket::setCountry(
                $infoArray,
                tx_ttproducts_control_basket::getBasketExtra()
            );
        }

        tx_ttproducts_control_basket::addLoginData(
            $infoArray,
            $conf['loginUserInfoAddress'] ?? 0,
            $conf['useStaticInfoCountry'] ?? 0
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
        if (isset($config['templateSuffix'])) {
            $templateObj->setTemplateSuffix($config['templateSuffix']);
        }

        // Call all init hooks
        if (
            isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['init']) &&
            is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['init'])
        ) {
            $tableClassArray = $tablesObj->getTableClassArray();

            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['init'] as $classRef) {
                $hookObj = GeneralUtility::makeInstance($classRef);
                if (method_exists($hookObj, 'init')) {
                    $hookObj->init($languageObj, $tableClassArray);
                }
            }
            $tablesObj->setTableClassArray($tableClassArray);
        }

        return true;
    }

    public static function getLanguageObj($pLangObj, $cObj, $conf)
    {
        $languageObj = GeneralUtility::makeInstance(Localization::class);
        $languageSubpath = '/Resources/Private/Language/';

        $confLocalLang = [];
        if (isset($conf['_LOCAL_LANG.'])) {
            $confLocalLang = $conf['_LOCAL_LANG.'];
        }
        if (isset($conf['marks.'])) {
            $confLocalLang = array_merge($confLocalLang, $conf['marks.']);
        }
        $languageObj->init(
            TT_PRODUCTS_EXT,
            $confLocalLang,
            $languageSubpath
        );

        $languageObj->loadLocalLang(
            'EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_db.xlf',
            false
        );
        $languageObj->loadLocalLang(
            'EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'PiSearch/locallang_db.xlf',
            false
        );
        $languageObj->loadLocalLang(
            'EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'Pi1/locallang.xlf',
            false
        );

        return $languageObj;
    }

    public function destruct(): void
    {
        tx_ttproducts_control_basket::destruct();
    }
}
