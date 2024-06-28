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
 * functions for the info addresses view
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage tt_products
 */

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use JambageCom\Div2007\Api\OldStaticInfoTablesApi;
use JambageCom\Div2007\Api\StaticInfoTablesApi;

use JambageCom\TtProducts\Api\PaymentShippingHandling;
use JambageCom\TtProducts\Api\CustomerApi;

class tx_ttproducts_info implements SingletonInterface
{
    protected $infoArray = []; // elements: 'billing' and 'delivery' addresses
    // contains former basket $personInfo and $deliveryInfo
    protected $pdfInfoFields = '';

    public function init(array $infoArray, $pdfInfoFields = ''): bool
    {
        $this->setInfoArray($infoArray);
        $this->setPdfInfoFields($pdfInfoFields);
        return true;
    }

    public function setInfoArray($infoArray): void
    {
        $this->infoArray = $infoArray;
    }

    public function getInfoArray()
    {
        return $this->infoArray;
    }

    public function setPdfInfoFields($pdfInfoFields): void
    {
        $this->pdfInfoFields = $pdfInfoFields;
    }

    public function getPdfInfoFields()
    {
        return $this->pdfInfoFields;
    }

    public function getFields()
    {
        return CustomerApi::getFields();
    }

    /**
     * Checks if required fields are filled in.
     */
    public function checkRequired(
        $type,
        $basketExtra,
        $systemLoginUser
    ) {
        $result = '';
        $infoArray = $this->getInfoArray();

        if (
            $type == 'billing' ||
            tx_ttproducts_control_basket::needsDeliveryAddresss($basketExtra)
        ) {
            if (
                $systemLoginUser &&
                !empty($infoArray[$type]['cnum'])
            ) {
                // nothing
            } else {
                $requiredInfoFields = CustomerApi::getRequiredInfoFields($type);

                if ($requiredInfoFields) {
                    $infoFields = GeneralUtility::trimExplode(',', $requiredInfoFields);

                    foreach ($infoFields as $fName) {
                        if (
                            !isset($infoArray[$type]) ||
                            !isset($infoArray[$type][$fName]) ||
                            trim($infoArray[$type][$fName]) == ''
                        ) {
                            $result = $fName;
                            break;
                        }
                    }
                }

                // RegEx-Check
                $checkFieldsExpr = $this->getFieldChecks($type);
                if ($checkFieldsExpr && is_array($checkFieldsExpr)) {
                    foreach ($checkFieldsExpr as $fName => $checkExpr) {
                        if (isset($infoArray[$type][$fName]) && trim($infoArray[$type][$fName]) != '') {
                            if (preg_match('/' . $checkExpr . '/', $this->infoArray[$type][$fName]) == 0) {
                                $result = $fName;
                                break;
                            }
                        }
                    }
                }
            }
        }

        return $result;
    } // checkRequired

    /**
     * Checks if the filled in fields are allowed.
     */
    public function checkAllowed($basketExtra)
    {
        $result = '';
        $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
        $conf = $cnf->getConf();
        $infoArray = $this->getInfoArray();
        if (version_compare(PHP_VERSION, '8.0.0') >= 0) {
            $staticInfoApi = GeneralUtility::makeInstance(StaticInfoTablesApi::class);
        } else {
            $staticInfoApi = GeneralUtility::makeInstance(OldStaticInfoTablesApi::class);
        }
        $where = $this->getWhereAllowedCountries($basketExtra);

        if (
            $where &&
            !empty($conf['useStaticInfoCountry']) &&
            $staticInfoApi->isActive()
        ) {
            $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
            $countryObj = $tablesObj->get('static_countries');
            if (is_object($countryObj)) {
                $type = (
                    !tx_ttproducts_control_basket::needsDeliveryAddresss($basketExtra) ?
                        'billing' :
                        'delivery'
                );
                $row = $countryObj->isoGet($infoArray[$type]['country_code'] ?? '', $where);
                if (!$row) {
                    $result = 'country';
                }
            }
        }

        return $result;
    } // checkAllowed

    /**
     * gets the WHERE clause for the allowed static_countries.
     */
    public function getWhereAllowedCountries($basketExtra)
    {
        if (version_compare(PHP_VERSION, '8.0.0') >= 0) {
            $staticInfoApi = GeneralUtility::makeInstance(StaticInfoTablesApi::class);
        } else {
            $staticInfoApi = GeneralUtility::makeInstance(OldStaticInfoTablesApi::class);
        }
        $where = '';

        if ($staticInfoApi->isActive()) {
            $where = PaymentShippingHandling::getWhere($basketExtra, 'static_countries');
        }

        return $where;
    } // getWhereAllowedCountries

    /**
     * Gets regular Expressions for Field-Checks.
     */
    public function getFieldChecks($type)
    {
        $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
        $conf = $cnf->getConf();

        $rc = '';
        $fieldCheckArray = $conf['regExCheck.'];

        $fieldChecks = [];
        if (isset($fieldCheckArray) && is_array($fieldCheckArray)) {
            // Array komplett durchlaufen
            foreach ($fieldCheckArray as $key => $value) {
                if (isset($value) && is_array($value)) {
                    // spezifischer TS-Eintrag
                    if ($key == $type . '.') {
                        foreach ($value as $key2 => $value2) {
                            $fieldChecks[$key2] = $value2;
                        }
                    }
                } else {
                    // unspezifischer TS-Eintrag
                    $fieldChecks[$key] = $value;
                }
            }
        }

        return $fieldChecks;
    }

    public function billingEqualsShipping()
    {
        $result = true;

        $infoArray = $this->getInfoArray();
        $fields = CustomerApi::getFields();
        $fieldArray = GeneralUtility::trimExplode(',', $fields . ',feusers_uid');
        foreach ($fieldArray as $k => $fName) {
            if (
                $fName != 'country' &&
                isset($infoArray['billing'][$fName]) &&
                (
                    !empty($infoArray['delivery'][$fName]) &&
                    $infoArray['delivery'][$fName] != $infoArray['billing'][$fName]
                ) ||
                !isset($infoArray['billing'][$fName]) &&
                !empty($infoArray['delivery'][$fName])
            ) {
                $result = false;
                break;
            }
        }

        return $result;
    }

    /**
     * Fills in all empty fields in the delivery info array.
     */
    public function mapPersonIntoDelivery(
        $basketExtra,
        $overwrite = true,
        $needsDeliveryAddress = true
    ): void {
        $infoArray = $this->getInfoArray();

        // all of the delivery address will be overwritten when no address and no email address have been filled in for it
        if (
            (
                empty($infoArray['delivery']['address']) &&
                empty($infoArray['delivery']['email'])
                    ||
                $overwrite
            ) &&
            $needsDeliveryAddress
        ) {
            $hasAddress = !empty($infoArray['delivery']['address']);

            $fields = CustomerApi::getFields();
            $fieldArray = GeneralUtility::trimExplode(',', $fields . ',feusers_uid');

            foreach ($fieldArray as $k => $fName) {
                if (
                    isset($infoArray['billing'][$fName]) &&
                    (
                        !isset($infoArray['delivery'][$fName]) ||
                        $infoArray['delivery'][$fName] == '' ||
                        (
                            $infoArray['delivery'][$fName] == '0' && !$hasAddress
                        ) ||
                        in_array($fName, ['country', 'country_code', 'zone'])
                    ) // FHO neu: jetzt auch country_code
                ) {
                    $infoArray['delivery'][$fName] = $infoArray['billing'][$fName];
                }
            }
        }

        if (
            isset($infoArray['delivery']) &&
            !isset($infoArray['delivery']['name']) &&
            !isset($infoArray['delivery']['last_name']) &&
            !isset($infoArray['delivery']['company'])
        ) {
            unset($infoArray['delivery']['salutation']);
            if (
                count($infoArray['delivery']) < 3 &&
                !isset($infoArray['delivery']['email']) // KORR Neu FHO
            ) {
                unset($infoArray['delivery']);
            }
        }

        // Call info hooks
        if (
            isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['info']) &&
            is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['info'])
        ) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['info'] as $classRef) {
                $hookObj = GeneralUtility::makeInstance($classRef);
                if (method_exists($hookObj, 'mapPersonIntoDelivery')) {
                    $hookObj->mapPersonIntoDelivery($this, $infoArray);
                }
            }
        }

        $this->setInfoArray($infoArray);
    } // mapPersonIntoDelivery

    public function getCustomerEmail()
    {
        $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
        $conf = $cnf->getConf();

        $infoArray = $this->getInfoArray();

        $result = (
            (
                $conf['orderEmail_toDelivery'] && $infoArray['delivery']['email'] ||
                !$infoArray['billing']['email']
            ) ?
                $infoArray['delivery']['email'] :
                $infoArray['billing']['email']
        ); // former: deliveryInfo

        return $result;
    }

    public function getFromArray($customerEmail, $useLoginEmail)
    {
        $infoArray = $this->getInfoArray();
        $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
        $conf = $cnf->getConf();
        $context = GeneralUtility::makeInstance(Context::class);

        $resultArray = [];
        $resultArray['shop'] = [
            'email' => $conf['orderEmail_from'] ?? '',
            'name' => $conf['orderEmail_fromName'] ?? '',
        ];

        if (
            $customerEmail != ''
        ) {
            $name = '';
            if (
                isset($infoArray['billing']['name'])
            ) {
                $name = $infoArray['billing']['name'];
            }
            $resultArray['customer'] = [
                'email' => $customerEmail,
                'name' => $name,
            ];
        }

        if (
            $useLoginEmail != '' &&
            $context->getPropertyFromAspect('frontend.user', 'isLoggedIn') &&
            isset($GLOBALS['TSFE']->fe_user) &&
            isset($GLOBALS['TSFE']->fe_user->user) &&
            !empty($GLOBALS['TSFE']->fe_user->user['username'])
        ) {
            $name = $GLOBALS['TSFE']->fe_user->user['name'];
            if (
                isset($GLOBALS['TSFE']->fe_user->user['first_name']) &&
                isset($GLOBALS['TSFE']->fe_user->user['last_name'])
            ) {
                $name = $GLOBALS['TSFE']->fe_user->user['first_name'] . ' ';
                if (!empty($GLOBALS['TSFE']->fe_user->user['middle_name'])) {
                    $name .= $GLOBALS['TSFE']->fe_user->user['middle_name'] . ' ';
                }
                $name .= $GLOBALS['TSFE']->fe_user->user['last_name'];
            }
            $resultArray['login'] = [
                'email' => $GLOBALS['TSFE']->fe_user->user['email'],
                'name' => $name,
            ];
        }

        return $resultArray;
    }

    public function isNewUser($type) // billing or delivery
    {
        $result = false;
        $infoArray = $this->getInfoArray();
        $requiredInfoFields = CustomerApi::getRequiredInfoFields($type);
        $context = GeneralUtility::makeInstance(Context::class);

        if (
            $requiredInfoFields &&
            in_array($type, ['billing', 'delivery']) &&
            isset($infoArray[$type])
        ) {
            $infoFields = GeneralUtility::trimExplode(',', $requiredInfoFields);
            $result = true;

            foreach ($infoFields as $fName) {
                if (
                    !isset($infoArray[$type]) ||
                    !isset($infoArray[$type][$fName]) ||
                    trim($infoArray[$type][$fName]) == ''
                ) {
                    $result = false;
                    break;
                } elseif (
                    $context->getPropertyFromAspect('frontend.user', 'isLoggedIn') &&
                    isset($GLOBALS['TSFE']->fe_user) &&
                    isset($GLOBALS['TSFE']->fe_user->user) &&
                    is_array($GLOBALS['TSFE']->fe_user->user) &&
                    $GLOBALS['TSFE']->fe_user->user['username'] != '' &&
                    $infoArray[$type][$fName] != $GLOBALS['TSFE']->fe_user->user[$fName]
                ) {
                    break;
                }
            }
        }

        return $result;
    }
}
