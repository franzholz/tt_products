<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2016 Franz Holzinger (franz@ttproducts.de)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
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
 * control function for the basket.
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage tt_products
 */

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

use JambageCom\Div2007\Api\Frontend;
use JambageCom\Div2007\Api\OldStaticInfoTablesApi;
use JambageCom\Div2007\Api\StaticInfoTablesApi;
use JambageCom\Div2007\Security\TransmissionSecurity;
use JambageCom\Div2007\Utility\CompatibilityUtility;

use JambageCom\TtProducts\Api\BasketApi;
use JambageCom\TtProducts\Api\ControlApi;
use JambageCom\TtProducts\Api\CustomerApi;
use JambageCom\TtProducts\Api\PaymentShippingHandling;


abstract class BasketRecsIndex
{
    public const Billing = 'personinfo';
    public const Delivery = 'delivery';
}

class tx_ttproducts_control_basket
{
    protected static $recs = [];
    protected static $basketExt = [];	// "Basket Extension" - holds extended attributes
    protected static $basketExtra = [];	// initBasket() uses this for additional information like the current payment/shipping methods
    protected static $infoArray = [];
    private static ?object $pidListObj = null;
    private static bool $bHasBeenInitialised = false;
    private static $funcTablename;			// tt_products or tt_products_articles

    public static function storeNewRecs($transmissionSecurity = false): void
    {
        $recs = GeneralUtility::_GP('recs');
        if (
            is_array($recs) &&
            $transmissionSecurity
        ) {
            // TODO  transmission security
            $errorCode = [];
            $errorMessage = '';
            $security = GeneralUtility::makeInstance(TransmissionSecurity::class);
            $decryptionResult = $security->decryptIncomingFields(
                $recs,
                $errorCode,
                $errorMessage
            );
        }

        if (
            is_array($recs)
        ) {
            $api = GeneralUtility::makeInstance(Frontend::class);
            // If any record registration is submitted, register the record.
            $api->record_registration(
                $recs,
                0,
                $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['checkCookies']
            );
        }
    }

    public static function init(
        &$conf,
        $tablesObj,
        $pid_list,
        $useArticles,
        array $recs = [],
        array $basketRec = []
    ): void {
        if (!self::$bHasBeenInitialised) {
            self::setRecs($recs);
            $basketApi = GeneralUtility::makeInstance(BasketApi::class);

            if (
                isset($GLOBALS['TSFE']) &&
                $GLOBALS['TSFE'] instanceof TypoScriptFrontendController
            ) {
                $basketApi->setBasketExt(self::getStoredBasketExt());
                $basketExtra = PaymentShippingHandling::getBasketExtras($tablesObj, $recs, $conf);
                $basketApi->setBasketExtra($basketExtra);
            } else {
                self::setRecs($recs);
                $basketApi->setBasketExt([]);
                $basketApi->setBasketExtra([]);
            }

            self::$pidListObj = GeneralUtility::makeInstance('tx_ttproducts_pid_list');
            self::$pidListObj->applyRecursive(
                99,
                $pid_list,
                true
            );

            self::$pidListObj->setPageArray();

            if ($useArticles == 2) {
                $funcTablename = 'tt_products_articles';
            } else {
                $funcTablename = 'tt_products';
            }
            self::setFuncTablename($funcTablename);
            $recs = self::getRecs();
            CustomerApi::init(
                $conf,
                $recs[BasketRecsIndex::Billing] ?? '',
                $recs[BasketRecsIndex::Delivery] ?? '',
                $basketApi->getBasketExtra()
            );

            self::$bHasBeenInitialised = true;
        }
    }


    public static function getCmdArray()
    {
        $result = ['delete'];

        return $result;
    }

    public static function getPidListObj()
    {
        return self::$pidListObj;
    }

    public static function doProcessing(): void
    {
        $piVars = tx_ttproducts_model_control::getPiVars();
        $basketExtModified = false;

        if (isset($piVars) && is_array($piVars)) {
            foreach ($piVars as $piVar => $value) {
                switch ($piVar) {
                    case 'delete':
                        $uid = $value;

                        $basketVar = tx_ttproducts_model_control::getBasketParamVar();

                        if (isset($piVars[$basketVar])) {
                            if (
                                isset(self::$basketExt[$uid]) &&
                                is_array(self::$basketExt[$uid])
                            ) {
                                foreach (self::$basketExt[$uid] as $allVariants => $count) {
                                    if (
                                        md5($allVariants) == $piVars[$basketVar]
                                    ) {
                                        unset(self::$basketExt[$uid][$allVariants]);
                                        $basketExtModified = true;
                                    }
                                }
                            }
                        }
                        break;
                }
            }
        }

        if ($basketExtModified) {
            self::storeBasketExt(self::$basketExt);
        }
    }

    public static function setFuncTablename($funcTablename): void
    {
        self::$funcTablename = $funcTablename;
    }

    public static function getFuncTablename()
    {
        return self::$funcTablename;
    }

    public static function getRecs()
    {
        return self::$recs;
    }

    public static function setRecs($recs): void
    {
        $newRecs = [];
        $allowedTags = '<br><a><b><td><tr><div>';

        foreach ($recs as $type => $valueArray) {
            if (is_array($valueArray)) {
                foreach ($valueArray as $k => $infoRow) {
                    $newRecs[$type][$k] = strip_tags($infoRow, $allowedTags);
                }
            } else {
                $newRecs[$type] = strip_tags($valueArray, $allowedTags);
            }
        }

        self::$recs = $newRecs;
    }

    public static function getStoredRecs()
    {
        $result = [];

        $recs = tx_ttproducts_control_session::readSession('recs');
        if (!empty($recs)) {
            $result = $recs;
        }

        return $result;
    }

    public static function setStoredRecs($valueArray): void
    {
        self::store('recs', $valueArray);
    }

    public static function getStoredVariantRecs()
    {
        $result = tx_ttproducts_control_session::readSession('variant');

        return $result;
    }

    public static function setStoredVariantRecs($valueArray): void
    {
        self::store('variant', $valueArray);
    }

    public static function store($type, $valueArray): void
    {
        tx_ttproducts_control_session::writeSession($type, $valueArray);
    }

    public static function getBasketExtRaw()
    {
        $basketVar = tx_ttproducts_model_control::getBasketVar();
        $result = GeneralUtility::_GP($basketVar);

        return $result;
    }

    public static function getStoredBasketExt()
    {
        $result = tx_ttproducts_control_session::readSession('basketExt');

        return $result;
    }

    public static function getStoredOrder()
    {
        $result = tx_ttproducts_control_session::readSession('order');

        return $result;
    }

    public static function storeBasketExt($basketExt): void
    {
        $basketApi = GeneralUtility::makeInstance(BasketApi::class);
        self::store('basketExt', $basketExt);
        $basketApi->setBasketExt($basketExt);
    }

    public static function generatedBasketExtFromRow($row, $count)
    {
        $basketExt = [];

        $extArray = $row['ext'] ?? [];
        $extVarLine = $extArray['extVarLine'] ?? '';
        $basketExt[$row['uid']][$extVarLine] = $count;

        return $basketExt;
    }

    public static function removeFromBasketExt($removeBasketExt): void
    {
        $basketExt = self::getStoredBasketExt();
        $bChanged = false;

        if (isset($removeBasketExt) && is_array($removeBasketExt)) {
            foreach ($removeBasketExt as $uid => $removeRow) {
                $allVariants = key($removeRow);
                $bRemove = current($removeRow);

                if (
                    $bRemove &&
                    isset($basketExt[$uid]) &&
                    isset($basketExt[$uid][$allVariants])
                ) {
                    unset($basketExt[$uid][$allVariants]);
                    $bChanged = true;
                }
            }
        }
        if ($bChanged) {
            self::storeBasketExt($basketExt);
        }
    }

    public static function getBasketCount(
        $row,
        $variant,
        $quantityIsFloat,
        $ignoreVariant = false
    ) {
        $count = '';
        $basketApi = GeneralUtility::makeInstance(BasketApi::class);
        $basketExt = $basketApi->getBasketExt();
        $uid = $row['uid'];

        if (isset($basketExt[$uid])) {
            $subArr = $basketExt[$uid];

            if (
                $ignoreVariant &&
                is_array($subArr)
            ) {
                $count = 0;
                foreach ($subArr as $subVariant => $subCount) {
                    $count += $subCount;
                }
            } elseif (
                is_array($subArr) &&
                isset($subArr[$variant])
            ) {
                $tmpCount = $subArr[$variant];

                if (
                    $tmpCount > 0 &&
                    (
                        $quantityIsFloat ||
                        MathUtility::canBeInterpretedAsInteger($tmpCount)
                    )
                ) {
                    $count = $tmpCount;
                }
            }
        }

        return $count;
    }

    public static function getStoredInfoArray()
    {
        $formerBasket = self::getRecs();

        $infoArray = [];

        if (isset($formerBasket) && is_array($formerBasket)) {
            $infoArray['billing'] = $formerBasket['personinfo'] ?? '';
            $infoArray['delivery'] = $formerBasket['delivery'] ?? '';
        }
        if (!$infoArray['billing']) {
            $infoArray['billing'] = [];
        }
        if (!$infoArray['delivery']) {
            $infoArray['delivery'] = [];
        }

        return $infoArray;
    }

    public static function setInfoArray($infoArray): void
    {
        self::$infoArray = $infoArray;

        if (
            isset($infoArray['billing']) &&
            is_array($infoArray['billing'])
        ) {
            CustomerApi::setBillingInfo($infoArray['billing']);
        }

        if (
            isset($infoArray['delivery']) &&
            is_array($infoArray['delivery'])
        ) {
            CustomerApi::setShippingInfo($infoArray['delivery']);
        }
    }

    public static function getInfoArray()
    {
        return self::$infoArray;
    }

    public static function setCountry(&$infoArray, $basketExtra): void
    {
        if (version_compare(PHP_VERSION, '8.0.0') >= 0) {
            $staticInfoApi = GeneralUtility::makeInstance(StaticInfoTablesApi::class);
        } else {
            $staticInfoApi = GeneralUtility::makeInstance(OldStaticInfoTablesApi::class);
        }

        if (
            $staticInfoApi->isActive() &&
            !empty($infoArray['billing']['country_code'])
        ) {
            $infoArray['billing']['country'] =
                $staticInfoApi->getStaticInfoName(
                    $infoArray['billing']['country_code'],
                    'COUNTRIES',
                    '',
                    ''
                );

            if (static::needsDeliveryAddresss($basketExtra)) {
                $infoArray['delivery']['country'] =
                    $staticInfoApi->getStaticInfoName(
                        $infoArray['delivery']['country_code'],
                        'COUNTRIES',
                        '',
                        ''
                    );
            }
        }
    }

    public static function uncheckAgb(&$infoArray, $bProductsPayment): void
    {
        if (
            $bProductsPayment &&
            isset($_REQUEST['recs']) &&
            is_array($_REQUEST['recs']) &&
            isset($_REQUEST['recs']['personinfo']) &&
            is_array($_REQUEST['recs']['personinfo']) &&
            empty($_REQUEST['recs']['personinfo']['agb'])
        ) {
            $infoArray['billing']['agb'] = false;
        }
    }

    // normally the delivery is copied from the bill data. But also another table can be used for it.
    public static function needsDeliveryAddresss($basketExtra)
    {
        $result = true;

        $shippingType = PaymentShippingHandling::get(
            'shipping',
            'type',
            $basketExtra
        );

        if (
            $shippingType == 'pick_store' ||
            $shippingType == 'nocopy'
        ) {
            $result = false;
        }

        return $result;
    }

    public static function fixCountries(&$infoArray)
    {
        $result = false;

        if (
            !empty($infoArray['billing']['country_code']) &&
            (
                !isset($infoArray['delivery']['zip']) ||
                $infoArray['delivery']['zip'] == '' ||
                (
                    $infoArray['delivery']['zip'] == $infoArray['billing']['zip']
                )
            )
        ) {
            // a country change in the select box shall be copied
            $infoArray['delivery']['country_code'] = $infoArray['billing']['country_code'];
            $result = true;
        }

        return $result;
    }

    public static function addLoginData(
        &$infoArray,
        $loginUserInfoAddress,
        $useStaticInfoCountry
    ): void {
        if (
            isset($GLOBALS['TSFE']) &&
            $GLOBALS['TSFE'] instanceof TypoScriptFrontendController &&
            CompatibilityUtility::isLoggedIn() &&
            ControlApi::isOverwriteMode($infoArray)
        ) {
            $address = '';
            $infoArray['billing']['feusers_uid'] =
                $GLOBALS['TSFE']->fe_user->user['uid'];

            if (
                $useStaticInfoCountry &&
                empty($infoArray['billing']['country_code'])
            ) {
                $infoArray['billing']['country_code'] =
                    $GLOBALS['TSFE']->fe_user->user['static_info_country'];
            }

            if ($loginUserInfoAddress) {
                $address = implode(
                    chr(10),
                    GeneralUtility::trimExplode(
                        chr(10),
                        $GLOBALS['TSFE']->fe_user->user['address'] . chr(10) .
                            (
                                isset($GLOBALS['TSFE']->fe_user->user['house_no']) &&
                                $GLOBALS['TSFE']->fe_user->user['house_no'] != '' ?
                                    $GLOBALS['TSFE']->fe_user->user['house_no'] . chr(10) :
                                    ''
                            ) .
                        $GLOBALS['TSFE']->fe_user->user['zip'] . ' ' . $GLOBALS['TSFE']->fe_user->user['city'] . chr(10) .
                            (
                                $useStaticInfoCountry ?
                                    $GLOBALS['TSFE']->fe_user->user['static_info_country'] :
                                    $GLOBALS['TSFE']->fe_user->user['country']
                            ),
                        1
                    )
                );
            } else {
                $address = $GLOBALS['TSFE']->fe_user->user['address'];
            }
            $infoArray['billing']['address'] = $address;
            $fields = CustomerApi::getFields() . ',' . CustomerApi::getCreditPointFields();

            $fieldArray = GeneralUtility::trimExplode(',', $fields);
            foreach ($fieldArray as $k => $field) {
                if (empty($infoArray['billing'][$field])) {
                    $infoArray['billing'][$field] = $GLOBALS['TSFE']->fe_user->user[$field];
                }
            }

            $typeArray = ['billing', 'delivery'];
            foreach ($typeArray as $type) {
                if (
                    empty($infoArray[$type]['country']) &&
                    (
                        !empty($infoArray[$type]['static_info_country']) ||
                        !empty($infoArray[$type]['country_code'])
                    ) &&
                    $useStaticInfoCountry
                ) {
                    if (version_compare(PHP_VERSION, '8.0.0') >= 0) {
                        $staticInfoApi = GeneralUtility::makeInstance(StaticInfoTablesApi::class);
                    } else {
                        $staticInfoApi = GeneralUtility::makeInstance(OldStaticInfoTablesApi::class);
                    }

                    if (
                        $staticInfoApi->isActive()
                    ) {
                        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
                        $countryObj = $tablesObj->get('static_countries');

                        if (is_object($countryObj)) {
                            if (!empty($infoArray[$type]['static_info_country'])) {
                                $iso3Field = 'static_info_country';
                            } elseif (!empty($infoArray[$type]['country_code'])) {
                                $iso3Field = 'country_code';
                            }

                            $row = $countryObj->isoGet($infoArray[$type][$iso3Field]);
                            if (isset($row['cn_short_de'])) {
                                $infoArray[$type]['country'] = $row['cn_short_de'];
                            }
                        }
                    }
                }
            }

            $infoArray['billing']['agb'] ??= $GLOBALS['TSFE']->fe_user->user['agb'];

            $dateBirth = $infoArray['billing']['date_of_birth'];
            $tmpPos = strpos($dateBirth, '-');

            if (
                !$dateBirth ||
                $tmpPos === false ||
                $tmpPos == 0
            ) {
                $infoArray['billing']['date_of_birth'] =
                    date('d-m-Y', $GLOBALS['TSFE']->fe_user->user['date_of_birth']);
            }
            unset($infoArray['billing']['error']);
        }
    }

    public static function getTagName($uid, $fieldname)
    {
        $result = tx_ttproducts_model_control::getBasketVar() . '[' . $uid . '][' . $fieldname . ']';

        return $result;
    }

    public static function getAjaxVariantFunction($row, $funcTablename, $theCode)
    {
        if (ExtensionManagementUtility::isLoaded('taxajax')) {
            $result = 'doFetchRow(\'' . $funcTablename . '\',\'' . strtolower($theCode) . '\',' . $row['uid'] . ');';
        } else {
            $result = '';
        }

        return $result;
    }

    public static function destruct(): void
    {
        self::$bHasBeenInitialised = false;
    }

    public static function getRoundFormat($type = '')
    {
        $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');

        $result = $cnf->getBasketConf('round', $type); // check the basket rounding format

        if (isset($result) && is_array($result)) {
            $result = '';
        }

        return $result;
    }

    public static function readControl($key = '')
    {
        $result = false;
        $ctrlArray = tx_ttproducts_control_session::readSession('ctrl');

        if (isset($ctrlArray) && is_array($ctrlArray)) {
            if ($key != '' && isset($ctrlArray[$key])) {
                $result = $ctrlArray[$key];
            } else {
                $result = $ctrlArray;
            }
        }

        return $result;
    }

    public static function writeControl($valArray): void
    {
        if (
            !isset($valArray) ||
            !is_array($valArray)
        ) {
            $valArray = [];
        }
        self::store('ctrl', $valArray);
    }
}
