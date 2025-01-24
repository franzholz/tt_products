<?php

declare(strict_types=1);

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
 * functions for the static_taxes table
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 *
 */

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

use JambageCom\StaticInfoTablesTaxes\Api\TaxApi;
use JambageCom\Div2007\Api\StaticInfoTablesApi;
use JambageCom\Div2007\Api\OldStaticInfoTablesApi;
use JambageCom\Div2007\Utility\ExtensionUtility;

use JambageCom\TtProducts\Api\CustomerApi;

class tx_ttproducts_static_tax extends tx_ttproducts_table_base
{
    protected $uidStore;
    protected $setShopCountryCode;
    private array $allTaxesArray = [];
    private ?array $taxArray = null;
    private array $countryArray = [];
    private array $taxIdArray = [];
    private static bool $isInstalled = false;
    private static bool $need4StaticTax = false; // if the usage of static_info_tables_taxes is required due to some circumstances: E.g. if download products inside of the EU are sold

    /**
     * Getting all tt_products_cat categories into internal array.
     */
    public function init($funcTablename): bool
    {
        $result = false;

        if (static::isInstalled()) {
            $result = parent::init($funcTablename);
            if (!$result) {
                return false;
            }
            $tablename = $this->getTablename();
            $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
            $tableconf = $cnf->getTableConf('static_taxes');
            $this->getTableObj()->setDefaultFieldArray(['uid' => 'uid', 'pid' => 'pid']);
            $this->getTableObj()->setTCAFieldArray('static_taxes');

            $requiredFields = 'uid,pid';
            if (!empty($this->tableconf['requiredFields'])) {
                $tmp = $tableconf['requiredFields'];
                $requiredFields = ($tmp ?: $requiredFields);
            }
            $requiredListArray = GeneralUtility::trimExplode(',', $requiredFields);
            $this->getTableObj()->setRequiredFieldArray($requiredListArray);
        }

        return $result;
    } // init

    public function initTaxes(
        $infoArray,
        $conf
    ): void {
        if (
            isset($infoArray) &&
            is_array($infoArray) &&
            isset($infoArray['billing']) &&
            isset($infoArray['billing']['country_code']) &&
            isset($conf['TAXpercentage.'])
        ) {
            $whereArray = [];
            $taxRateArray = [];
            $taxTitleArray = [];

            foreach ($conf['TAXpercentage.'] as $key => $confArray) {
                $position = strpos((string) $key, '.');
                $index = substr($key, 0, $position);
                $whereArray[$index] = $confArray['where.']['static_countries'];
                $taxRateArray[$index] = $confArray['tax.']['tx_rate'];
                $taxTitleArray[$index] = $confArray['tax.']['title'];
            }

            $shippingCountryCode = $infoArray['billing']['country_code'];
            if (
                isset($infoArray['delivery']) &&
                isset($infoArray['delivery']['country_code'])
            ) {
                $shippingCountryCode = $infoArray['delivery']['country_code'];
            }

            TaxApi::init(
                $shopCountryCode = '',
                $shopCountrySubdivisionCode = '',
                $shippingCountryCode,
                $shippingCountrySubdivisionCode = '',
                $billingCountryCode = '',
                $billingCountrySubdivisionCode = '',
                $whereArray,
                $taxRateArray,
                $taxTitleArray
            );
        }
    }

    public static function isInstalled()
    {
        $result = static::$isInstalled;

        if (
            !$result &&
            ExtensionManagementUtility::isLoaded('static_info_tables_taxes')
        ) {
            $eInfo = ExtensionUtility::getExtensionInfo('static_info_tables_taxes');

            if (is_array($eInfo)) {
                $sittVersion = $eInfo['version'];
                if (version_compare($sittVersion, '0.3.2', '>=')) {
                    $result = static::$isInstalled = true;
                }
            }
        }

        return $result;
    }

    public function isValid()
    {
        $result = static::isInstalled() && !$this->needsInit() && $this->getUidStore();

        return $result;
    }

    public function getUidStore()
    {
        return $this->uidStore;
    }

    public function setUidStore($uid): void
    {
        $this->uidStore = $uid;
    }

    public function getShopCountryCode()
    {
        return $this->setShopCountryCode;
    }

    public function setShopCountryCode($setShopCountryCode): void
    {
        $this->setShopCountryCode = $setShopCountryCode;
    }

    public function setStoreData($uidStore): void
    {
        if (static::isInstalled() && $uidStore > 0) {
            $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
            $orderAdressObj = $tablesObj->get('address', false);
            $storeRow = $orderAdressObj->get($uidStore);
            $theCountryCode = '';

            if (!empty($storeRow)) {
                $staticInfoCountryField = $orderAdressObj->getField('static_info_country');

                if (
                    !isset($storeRow[$staticInfoCountryField]) &&
                    isset($storeRow['country'])
                ) {
                    $staticInfoCountryField = 'country';
                }
                $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
                $tableconf = $cnf->getTableConf('address');

                if (
                    $tableconf['countryReference'] == 'uid' &&
                    MathUtility::canBeInterpretedAsInteger($storeRow[$staticInfoCountryField])
                ) {
                    $countryObj = $tablesObj->get('static_countries');
                    if (is_object($countryObj)) {
                        $countryRow = $countryObj->get($storeRow[$staticInfoCountryField]);
                        $theCountryCode = $countryRow['cn_iso_3'];
                    }
                } else {
                    $theCountryCode = $storeRow[$staticInfoCountryField];
                }

                if ($theCountryCode != '') {
                    $zoneField = $orderAdressObj->getField('zone');
                    if ($tableconf['zoneReference'] == 'uid') {
                        $zoneArray =
                        $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
                            'zn_code',
                            'static_country_zones',
                            'uid=' . intval($storeRow[$zoneField])
                        );
                        if (isset($zoneArray) && is_array($zoneArray) && isset($zoneArray[0])) {
                            $theZoneCode = $zoneArray[0]['zn_code'];
                        }
                    } else {
                        $theZoneCode = $storeRow[$zoneField];
                    }
                    $this->countryArray['shop'] = [];
                    $this->countryArray['shipping'] = [];
                    $this->countryArray['billing'] = [];
                    $this->countryArray['shop']['country_code'] = $theCountryCode;
                    $this->countryArray['shop']['zone'] = $theZoneCode;
                    $this->countryArray['shipping']['country_code'] = $theCountryCode;
                    $this->countryArray['shipping']['zone'] = $theZoneCode;
                    $this->countryArray['billing']['country_code'] = '';
                    $this->countryArray['billing']['zone'] = '';
                    $this->setUidStore($uidStore); // this must be done at the end of successful processing
                    $this->setShopCountryCode($theCountryCode);
                }
            }
            /*			$allTaxesArray = [];
             *                        $this->getStaticTax($row,$tax,$allTaxesArray); // call it to set the member variables*/
        }
    }

    protected function didValuesChange(array $countryArray)
    {
        $result = false;
        if (!is_array($countryArray['shipping'])) {
            // nothing
        } elseif (
            is_array($this->countryArray['shipping']) &&
            count($this->countryArray['shipping'])
        ) {
            $result = (
                count(
                    array_diff_assoc($this->countryArray['shipping'], $countryArray['shipping'])
                ) > 0
            );
        } else {
            $result = (count($countryArray['shipping']) > 0);
        }

        return $result;
    }

    public function setAllTaxesArray($taxArray, $taxId = ''): void
    {
        if ($taxId > 0) {
            $this->allTaxesArray[$taxId] = $taxArray;
        } else {
            $this->allTaxesArray = $taxArray;
        }
    }

    public function getAllTaxesArray($taxId = '')
    {
        if ($taxId > 0) {
            $result = $this->allTaxesArray[$taxId];
        } else {
            $result = $this->allTaxesArray;
        }

        return $result;
    }

    public function setTax($tax, $taxId): void
    {
        if ($taxId > 0) {
            $this->taxArray[$taxId] = $tax;
        }
    }

    public function getTaxArray($taxId)
    {
        $result = false;
        if ($taxId > 0) {
            $result = $this->taxArray[$taxId];
        }

        return $result;
    }

    public function setTaxId($taxId): void
    {
        $this->taxIdArray[] = $taxId;
        $this->taxIdArray = array_unique($this->taxIdArray);
    }

    public function storeValues($countryArray): void
    {
        $this->countryArray = $countryArray;
    }

    public function getCategoryArray($uidArray)
    {
        $result = [];
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);

        $isValid = true;
        foreach ($uidArray as $uid) {
            if (!MathUtility::canBeInterpretedAsInteger($uid)) {
                $isValid = false;
                $result = false;
                break;
            }
        }

        if ($isValid) {
            $uids = implode(',', $uidArray);
            $where = 'uid_local IN (' . $uids . ')' . $pageRepository->enableFields('tt_products_products_mm_tax_categories');
            $rowArray =
            $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
                '*',
                'tt_products_products_mm_tax_categories',
                $where
            );
            if (
                is_array($rowArray) &&
                !empty($rowArray)
            ) {
                foreach ($rowArray as $categoryRow) {
                    $result[] = $categoryRow['uid_foreign'];
                }
            }
        }

        return $result;
    }

    public function getTaxInfo(
        &$taxInfoArray,
        &$shopCountryArray,
        &$tax,
        array $uidArray,
        array $basketRecs
    ): bool {
        if (version_compare(PHP_VERSION, '8.0.0') >= 0) {
            $staticInfoApi = GeneralUtility::makeInstance(StaticInfoTablesApi::class);
        } else {
            $staticInfoApi = GeneralUtility::makeInstance(OldStaticInfoTablesApi::class);
        }

        $countryArray = $this->countryArray;

        if (
            !isset($countryArray['shop']) ||
            $countryArray['shop']['country_code'] == ''
        ) {
            return false;
        }

        if (
            isset($basketRecs) &&
            is_array($basketRecs) &&
            !empty($basketRecs)
        ) {
            $personInfo = $basketRecs['personinfo'];
            $deliveryInfo = $basketRecs['delivery'];
        }

        if (
            isset($deliveryInfo) &&
            is_array($deliveryInfo)
        ) {
            $countryArray['shipping']['country_code'] = $deliveryInfo['country_code'];
            $countryArray['shipping']['zone'] = $deliveryInfo['zone'];
        }

        if (
            isset($personInfo) &&
            is_array($personInfo)
        ) {
            $countryArray['billing']['country_code'] = $personInfo['country_code'];
            $countryArray['billing']['zone'] = $personInfo['zone'];
        }
        $categoryArray = [];
        if (!empty($uidArray)) {
            $categoryArray = $this->getCategoryArray($uidArray);
        }

        $countryCode = '';
        $zoneCode = '';

        $taxInfoArray =
        TaxApi::fetchCountryTaxes(
            $countryCode,
            $zoneCode,
            $staticInfoApi,
            $tax, // neu
            $categoryArray,
            -1,
            $countryArray['shop']['country_code'],
            $countryArray['shop']['zone'],
            $countryArray['shipping']['country_code'],
            $countryArray['shipping']['zone'],
            $countryArray['billing']['country_code'],
            $countryArray['billing']['zone'],
            0,
            true
        );
        $shopCountryArray = $countryArray['shop'];

        if (
            isset($taxInfoArray[$countryCode]) &&
            count($taxInfoArray[$countryCode]) == 1
        ) {
            $tax = $taxInfoArray[$countryCode][0]['tx_rate'];
        }

        return true;
    }

    public static function setNeed4StaticTax($value): void
    {
        static::$need4StaticTax = $value;
    }

    public static function getNeed4StaticTax()
    {
        return static::$need4StaticTax;
    }

    public static function need4StaticTax(
        array $row
    ) {
        if (static::getNeed4StaticTax()) {
            return true;
        }

        $result = false;

        $extArray = $row['ext'] ?? [];

        if (
            static::isInstalled() &&
            isset($extArray['records']) &&
            is_array($extArray['records']) &&
            isset($extArray['records']['tt_products_downloads']) &&
            isset($extArray['records']['sys_file_reference'])
        ) {
            $result = true;
            static::setNeed4StaticTax(true);
        }

        return $result;
    }

    public function getStaticTax(
        &$tax,
        &$taxInfoArray,
        $basketRecs,
        array $row
    ): void {
        $extArray = [];
        $categoryArray = [];
        $feUserRecord = CustomerApi::getFeUserRecord();
        if (
            !empty($row) &&
            $this->getUidStore() &&
            static::isInstalled()
        ) {
            if (
                isset($basketRecs) &&
                is_array($basketRecs) &&
                !empty($basketRecs)
            ) {
                $deliveryInfo = $basketRecs['delivery'];
                $personInfo = $basketRecs['personinfo'];
            }

            if (
                (
                    !isset($deliveryInfo) ||
                    !isset($deliveryInfo['country_code'])
                ) &&
                is_array($feUserRecord) &&
                !empty($feUserRecord['static_info_country'])
            ) {
                $deliveryInfo = $feUserRecord;
                $deliveryInfo['country_code'] = $feUserRecord['static_info_country'];
            }

            $taxId = 0;
            if (isset($row['tax_id'])) {
                $taxId = intval($row['tax_id']);
            }
            $taxCatId = 0;
            if (isset($row['taxcat_id'])) {
                $taxCatId = intval($row['taxcat_id']);
            }

            $extArray = $row['ext'] ?? [];

            if (
                isset($extArray['records']) &&
                is_array($extArray['records']) &&
                isset($extArray['records']['tt_products_downloads']) &&
                isset($extArray['records']['sys_file_reference'])
            ) {
                // TODO
                $categoryArray[] = TaxApi::EU_CATEGORY_DIGITAL_MEDIA_EBOOK;
                // EU download media detected. This overwrites the tax class of the product, if available.
            }

            if (
                isset($this->countryArray['shop']['country_code']) &&
                (
                    $taxId > 0 ||
                    $taxCatId > 0 ||
                    count($categoryArray) ||
                    $deliveryInfo['country_code'] != $this->countryArray['shop']['country_code']
                )
            ) {
                $uid = $row['uid'];
                $taxInfoArray = [];
                if (version_compare(PHP_VERSION, '8.0.0') >= 0) {
                    $staticInfoApi = GeneralUtility::makeInstance(StaticInfoTablesApi::class);
                } else {
                    $staticInfoApi = GeneralUtility::makeInstance(OldStaticInfoTablesApi::class);
                }

                $countryArray = $this->countryArray;

                if (isset($personInfo) && is_array($personInfo)) {
                    $countryArray['billing']['country_code'] = $personInfo['country_code'];
                    $countryArray['billing']['zone'] = $personInfo['zone'];
                }

                if (isset($deliveryInfo) && is_array($deliveryInfo)) {
                    $countryArray['shipping']['country_code'] = $deliveryInfo['country_code'];
                    $countryArray['shipping']['zone'] = $deliveryInfo['zone'];
                }

                if ($taxCatId && empty($categoryArray)) {
                    $categoryArray = $this->getCategoryArray([$uid]);
                }

                if (empty($categoryArray) && $taxId) {
                    $taxInfoArray = $this->getAllTaxesArray($taxId);
                }

                if (
                    $this->didValuesChange($countryArray) ||
                    empty($taxInfoArray)
                ) {
                    $countryCode = '';
                    $zoneCode = '';

                    $taxInfoArray =
                    TaxApi::fetchCountryTaxes(
                        $countryCode,
                        $zoneCode,
                        $staticInfoApi,
                        floatval(0), // neu
                        $categoryArray,
                        $taxId,
                        $countryArray['shop']['country_code'],
                        $countryArray['shop']['zone'],
                        $countryArray['shipping']['country_code'],
                        $countryArray['shipping']['zone'],
                        $countryArray['billing']['country_code'],
                        $countryArray['billing']['zone'],
                        0,
                        true
                    );
                    $this->storeValues(
                        $countryArray
                    );

                    $tax = null;

                    if (
                        isset($taxInfoArray) &&
                        is_array($taxInfoArray) &&
                        !empty($taxInfoArray) &&
                        empty($categoryArray) &&
                        $taxId
                    ) {
                        $this->setAllTaxesArray(
                            $taxInfoArray,
                            $taxId
                        );
                        $tax = floatval(0);
                    }

                    if (
                        isset($taxInfoArray) &&
                        is_array($taxInfoArray) &&
                        !empty($taxInfoArray) &&
                        isset($taxInfoArray[$countryCode]) &&
                        is_array($taxInfoArray[$countryCode])
                    ) {
                        if (count($taxInfoArray) == 1) {
                            $taxRow = current($taxInfoArray[$countryCode]);
                            $tax = $taxRow['tx_rate'];
                        } else { // calculate together many taxes and use them as one single tax. (Canada)
                            $priceOne =
                            TaxApi::applyConsumerTaxes(
                                $taxInfoArray,
                                floatval((1)
                            );
                            if ($priceOne !== false) {
                                $tax = ($priceOne - 1) * 100;
                            }
                        }
                    }

                    if (empty($categoryArray) && isset($tax)) {
                        $this->setTax($tax, $taxId);
                    }
                } else {
                    $tax = $this->getTaxArray($taxId);
                }
            } else {
                $tax = null; // keine Steuer mit 0 direkt ´verwenden!
            }
        }
    }
}
