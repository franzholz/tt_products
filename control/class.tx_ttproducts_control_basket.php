<?php

declare(strict_types=1);

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2020 Franz Holzinger (franz@ttproducts.de)
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
 *
 */
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

use JambageCom\Div2007\Api\Frontend;
use JambageCom\Div2007\Api\StaticInfoTablesApi;
use JambageCom\Div2007\Api\OldStaticInfoTablesApi;
use JambageCom\Div2007\Security\TransmissionSecurity;


use JambageCom\TtProducts\Api\BasketApi;
use JambageCom\TtProducts\Api\ControlApi;
use JambageCom\TtProducts\Api\CustomerApi;
use JambageCom\TtProducts\Api\ParameterApi;
use JambageCom\TtProducts\Api\PaymentShippingHandling;
use JambageCom\TtProducts\SessionHandler\SessionHandler;

abstract class BasketRecsIndex
{
    public const Billing = 'personinfo';
    public const Delivery = 'delivery';
}

class tx_ttproducts_control_basket
{
    protected static $recs = [];
    private static ?object $pidListObj = null;
    private static bool $bHasBeenInitialised = false;
    private static $funcTablename;		// tt_products or tt_products_articles
    protected static $infoArray = [];


    public static function init(
        &$conf,
        $tablesObj,
        $pid_list,
        $useArticles,
        $feUserRecord,
        array $recs = [],
        array $basketRec = []
    ): void {
        $basketApi = GeneralUtility::makeInstance(BasketApi::class);

        if (!static::$bHasBeenInitialised) {
            static::setRecs($recs);

            if (
                isset($GLOBALS['TSFE']) &&
                $GLOBALS['TSFE'] instanceof TypoScriptFrontendController
            ) {
                $baketExt = $basketApi->readBasketExt();
                $basketApi->setBasketExt($baketExt);
                $feUserRecord = CustomerApi::getFeUserRecord();
                $basketExtra =
                PaymentShippingHandling::getBasketExtras(
                    $conf,
                    $tablesObj,
                    $recs, // Korrektur auf $basketRec?,
                    $feUserRecord
                );
                $basketApi->setBasketExtra($basketExtra);
            } else {
                static::setRecs($recs);
                $basketApi->setBasketExt([]);
                $basketApi->setBasketExtra([]);
            }

            static::$pidListObj = GeneralUtility::makeInstance('tx_ttproducts_pid_list');
            static::$pidListObj->applyRecursive(
                99,
                $pid_list,
                true
            );

            static::$pidListObj->setPageArray();

            if ($useArticles == 2) {
                $funcTablename = 'tt_products_articles';
            } else {
                $funcTablename = 'tt_products';
            }
            static::setFuncTablename($funcTablename);
            $recs = static::getRecs();
            CustomerApi::init(
                $conf,
                $feUserRecord,
                $recs[BasketRecsIndex::Billing] ?? '',
                $recs[BasketRecsIndex::Delivery] ?? '',
                $basketApi->getBasketExtra()
            );

            static::$bHasBeenInitialised = true;
        }
    }

    // FHO neu Anfang
    public static function storeNewRecs($transmissionSecurity = false): void
    {
        $parameterApi = GeneralUtility::makeInstance(ParameterApi::class);
        $recs = $parameterApi->getParameter('recs') ?? '';

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

    public static function getCmdArray()
    {
        $result = ['delete'];

        return $result;
    }

    public static function getPidListObj()
    {
        return static::$pidListObj;
    }

    public static function doProcessing(): void
    {
        $parameterApi = GeneralUtility::makeInstance(ParameterApi::class);
        $basketApi = GeneralUtility::makeInstance(BasketApi::class);
        $piVars = $parameterApi->getPiVars();
        $basketExtModified = false;
        $basketExt = $basketApi->getBasketExt();

        if (isset($piVars) && is_array($piVars)) {
            foreach ($piVars as $piVar => $value) {
                switch ($piVar) {
                    case 'delete':
                        $uid = $value;
                        $basketVar = $parameterApi->getBasketParamVar();

                        if (isset($piVars[$basketVar])) {
                            if (
                                isset($basketExt[$uid]) &&
                                is_array($basketExt[$uid])
                            ) {
                                foreach ($basketExt[$uid] as $allVariants => $count) {
                                    if (
                                        md5($allVariants) == $piVars[$basketVar]
                                    ) {
                                        unset($basketExt[$uid][$allVariants]);
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
            $basketApi->storeBasketExt($basketExt);
        }
    }

    public static function setFuncTablename($funcTablename): void
    {
        static::$funcTablename = $funcTablename;
    }

    public static function getFuncTablename()
    {
        return static::$funcTablename;
    }

    public static function getRecs()
    {
        return static::$recs;
    }

    public static function setRecs(array $recs): void
    {
        $newRecs = [];
        $allowedTags = '<br><a><b><td><tr><div>';

        foreach ($recs as $type => $valueArray) {
            if (is_array($valueArray)) {
                foreach ($valueArray as $k => $infoRow) {
                    $newRecs[$type][$k] = strip_tags((string) $infoRow, $allowedTags);
                }
            } else {
                $newRecs[$type] = strip_tags($valueArray, $allowedTags);
            }
        }

        static::$recs = $newRecs;
    }

    public static function getStoredRecs()
    {
        $result = [];
        $recs = [];
        $parameterApi = GeneralUtility::makeInstance(ParameterApi::class);
        $frontendUser = $parameterApi->getRequest()->getAttribute('frontend.user');

        if (
            isset($frontendUser) &&
            $frontendUser instanceof FrontendUserAuthentication
        ) {
            $recs = $frontendUser->getKey('ses', 'recs');
        }

        if (!empty($recs)) {
            $result = $recs;
        }

        return $result;
    }

    public static function setStoredRecs($valueArray): void
    {
        static::store('recs', $valueArray);
    }

    public static function getStoredVariantRecs()
    {
        $result = SessionHandler::readSession('variant');
        return $result;
    }

    public static function setStoredVariantRecs($valueArray): void
    {
        static::store('variant', $valueArray);
    }

    public static function store($type, $valueArray): void
    {
        SessionHandler::storeSession($type, $valueArray);
    }

    public static function getStoredOrder()
    {
        $result = SessionHandler::readSession('order');

        return $result;
    }

    public static function generatedBasketExtFromRow($row, $count)
    {
        $basketExt = [];

        $extArray = $row['ext'] ?? [];
        $extVarLine = $extArray['extVarLine'] ?? '';
        $basketExt[$row['uid']][$extVarLine] = $count;

        return $basketExt;
    }

    public static function getStoredInfoArray()
    {
        $formerBasket = static::getRecs();
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
        static::$infoArray = $infoArray;
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
        return static::$infoArray;
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

    public static function uncheckAgb(&$infoArray, $isPaymentActivity): void
    {
        if (
            $isPaymentActivity &&
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
                empty($infoArray['delivery']['zip']) ||
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
        $useStaticInfoCountry,
        $feUserRecord
    ): void {
        $context = GeneralUtility::makeInstance(Context::class);
        if (
            $context->getPropertyFromAspect('frontend.user', 'isLoggedIn')
        ) {
            $infoArray['billing']['feusers_uid'] =
            $feUserRecord['uid'];
        }

        if (
            $context->getPropertyFromAspect('frontend.user', 'isLoggedIn') &&
            ControlApi::isOverwriteMode($infoArray)
        ) {
            $address = '';

            if (
                $useStaticInfoCountry &&
                empty($infoArray['billing']['country_code'])
            ) {
                $infoArray['billing']['country_code'] =
                $feUserRecord['static_info_country'];
            }

            if ($loginUserInfoAddress) {
                $address = implode(
                    chr(10),
                                   GeneralUtility::trimExplode(
                                       chr(10),
                                                               $feUserRecord['address'] . chr(10) .
                                                               (
                                                                   $feUserRecord['house_no'] != '' ?
                                                                   $feUserRecord['house_no'] . chr(10) :
                                                                   ''
                                                               ) .
                                                               $feUserRecord['zip'] . ' ' . $feUserRecord['city'] . chr(10) .
                                                               (
                                                                   $useStaticInfoCountry ?
                                                                   $feUserRecord['static_info_country'] :
                                                                   $feUserRecord['country']
                                                               )
                                   )
                );
            } else {
                $address = $feUserRecord['address'];
            }
            $infoArray['billing']['address'] = $address;
            $fields = CustomerApi::getFields() . ',' . CustomerApi::getCreditPointFields();

            $fieldArray = GeneralUtility::trimExplode(',', $fields);
            foreach ($fieldArray as $k => $field) {
                if (
                    empty($infoArray['billing'][$field]) &&
                    isset($feUserRecord[$field])
                ) {
                    $infoArray['billing'][$field] = $feUserRecord[$field];
                }
            }

            // neu Anfang
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
            } // foreach
            // neu Ende

            $infoArray['billing']['agb'] ??= $feUserRecord['agb'] ?? '';

            $dateBirth = $infoArray['billing']['date_of_birth'] ?? '';
            $tmpPos = strpos((string) $dateBirth, '-');

            if (
                !$dateBirth ||
                $tmpPos === false ||
                $tmpPos == 0
            ) {
                if (isset($feUserRecord['date_of_birth'])) {
                    $infoArray['billing']['date_of_birth'] =
                    date('d-m-Y', $feUserRecord['date_of_birth'] ?? 0);
                }
            }
            unset($infoArray['billing']['error']);
        } // if isLoggedIn
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
        static::$bHasBeenInitialised = false;
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
        $ctrlArray = SessionHandler::readSession('ctrl');

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
        static::store('ctrl', $valArray);
    }
}
