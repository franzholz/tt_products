<?php

declare(strict_types=1);

/***************************************************************
*  Copyright notice
*
*  (c) 2018 Franz Holzinger (franz@ttproducts.de)
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
 */
namespace JambageCom\TtProducts\Controller\Base;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

use JambageCom\Div2007\Api\OldStaticInfoTablesApi;
use JambageCom\Div2007\Api\StaticInfoTablesApi;

use JambageCom\TtProducts\Api\BasketApi;
use JambageCom\TtProducts\Api\BasketItemViewApi;
use JambageCom\TtProducts\Api\ControlApi;
use JambageCom\TtProducts\Api\Localization;
use JambageCom\TtProducts\Api\ParameterApi;
use JambageCom\TtProducts\Api\VariantApi;


class Creator implements SingletonInterface
{
    public function __construct(
        private readonly FileRepository $fileRepository,
    ) {}

    public function getFileRepository() {
        return $this->fileRepository;
    }

    public function init(
        array &$conf,
        array &$config,
        $pObj,
        $cObj,
        $ajax,
        &$errorCode,
        array $recs = [],
        array $basketRec = []
    ): bool {
        $basketApi = GeneralUtility::makeInstance(BasketApi::class);
        $variantApi = GeneralUtility::makeInstance(VariantApi::class);
        $parameterApi = GeneralUtility::makeInstance(ParameterApi::class);
        $request = $parameterApi->getRequest();

        if (version_compare(PHP_VERSION, '8.0.0') >= 0) {
            $staticInfoApi = GeneralUtility::makeInstance(StaticInfoTablesApi::class);
        } else {
            $staticInfoApi = GeneralUtility::makeInstance(OldStaticInfoTablesApi::class);
        }

        $useStaticInfoTables = $staticInfoApi->init();

        if (!empty($conf['PIDstoreRoot'])) {
            $config['storeRootPid'] = $conf['PIDstoreRoot'];
        } elseif (
            $request instanceof ServerRequestInterface
            &&
            ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend() &&
            isset($GLOBALS['TSFE']->tmpl->rootLine) &&
            is_array($GLOBALS['TSFE']->tmpl->rootLine)
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
            $conf['errorLog'] = GeneralUtility::resolveBackPath(Environment::getLegacyConfigPath() . '../' . $conf['errorLog']);
        }

        $wrap = $cObj->stdWrap($conf['pid_list'] ?? '', $conf['pid_list.'] ?? '');
        $pid_list = (!empty($cObj->data['pages']) ? $cObj->data['pages'] : (!empty($conf['pid_list.']) ? trim($wrap) : ''));
        $pid_list = ($pid_list ?: $conf['pid_list'] ?? '');
        $config['pid_list'] = ($pid_list ?? $config['storeRootPid'] ?? 0);
        $recursive = (!empty($cObj->data['recursive']) ? $cObj->data['recursive'] : $conf['recursive'] ?? 99);
        $config['recursive'] = MathUtility::forceIntegerInRange($recursive, 0, 100);

        if (is_object($pObj)) {
            $pLangObj = $pObj;
        } else {
            $pLangObj = $this;
        }
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $languageObj = static::getLanguageObj($pLangObj, $request, $cObj, $conf);
        $markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
        $piVars = $parameterApi->getPiVars();
        $result = $markerObj->init(
            $conf,
            $piVars['backPID'] ?? $parameterApi->getParameter('backPID')
        );

        if ($result == false) {
            $errorCode = $markerObj->getErrorCode();

            return false;
        }

        $feUserRecord = $request->getAttribute('frontend.user')->user;

        \tx_ttproducts_control_basket::init(
            $conf,
            $tablesObj,
            $config['pid_list'],
            $conf['useArticles'] ?? 3,
            $feUserRecord,
            $recs,
            $basketRec
        );

        // corrections in the Setup:
        if (
            ExtensionManagementUtility::isLoaded('voucher') &&
            isset($conf['gift.']) &&
            $conf['gift.']['type'] == 'voucher'
        ) {
            $conf['table.']['voucher'] = 'tx_voucher_codes';
        }

        $config['LLkey'] = $languageObj->getLocalLangKey(); // $pibaseObj->LLkey;

        $cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
        $cnfObj->init(
            $conf,
            $config
        );
        $tableDesc = $cnfObj->getTableDesc('tt_products');
        $variantConf = ($tableDesc['variant.'] ?? []);

        $selectableArray = '';
        $selectableFieldArray = [];
        $firstVariantArray = '';
        $variantApi->getParams(
            $selectableArray,
            $selectableFieldArray,
            $firstVariantArray,
            $conf,
            $variantConf
        );

        $variantApi->storeVariantConf($variantConf);
        $variantApi->storeSelectable($selectableArray);
        $variantApi->setSelectableFieldArray($selectableFieldArray);
        $variantApi->storeFirstVariant($firstVariantArray);

        $variantApi->init(
            $variantConf,
            $conf['useArticles'] ?? 3,
            $selectableArray,
            $firstVariantArray
        );

        ControlApi::init($conf, $cObj);
        $infoArray = \tx_ttproducts_control_basket::getStoredInfoArray();
        if (!empty($conf['useStaticInfoCountry'])) {
            \tx_ttproducts_control_basket::setCountry(
                $infoArray,
                $basketApi->getBasketExtra()
            );
        }

        \tx_ttproducts_control_basket::addLoginData(
            $infoArray,
            $conf['loginUserInfoAddress'] ?? 0,
            $conf['useStaticInfoCountry'] ?? 0,
            $feUserRecord
        );

        \tx_ttproducts_control_basket::setInfoArray($infoArray);

        // price
        $priceObj = GeneralUtility::makeInstance('tx_ttproducts_field_price');
        $priceObj->init(
            $conf
        );
        $priceViewObj = GeneralUtility::makeInstance('tx_ttproducts_field_price_view');
        $priceViewObj->init(
            $priceObj
        );

        // image
        $imageObj = GeneralUtility::makeInstance('tx_ttproducts_field_image');
        $imageObj->init($this->fileRepository);

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

        $basketItemViewApi = GeneralUtility::makeInstance(BasketItemViewApi::class, $conf);

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

    public static function getLanguageObj(
        $pLangObj,
        ServerRequestInterface $request = null,
        $cObj,
        $conf
    )
    {
        $languageObj = GeneralUtility::makeInstance(Localization::class);
        $languageSubpath = '/Resources/Private/Language/';

        $confLocalLang = [];
        if (isset($conf['_LOCAL_LANG.'])) {
            $confLocalLang = $conf['_LOCAL_LANG.'];
        }
        if (isset($conf['marks.']['_LOCAL_LANG.'])) {
            $confLocalLang = array_merge_recursive($confLocalLang, $conf['marks.']['_LOCAL_LANG.']);
        }

        $languageObj->init(
            TT_PRODUCTS_EXT,
            $confLocalLang,
            $request
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
        \tx_ttproducts_control_basket::destruct();
    }
}
