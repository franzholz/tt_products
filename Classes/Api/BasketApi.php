<?php

declare(strict_types=1);

namespace JambageCom\TtProducts\Api;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2020 Franz Holzinger <franz@ttproducts.de>
 *  All rights reserved
 *
 *  This script is part of the Typo3 project. The Typo3 project is
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
 * functions for the basket
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 *
 */
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

use JambageCom\TtProducts\SessionHandler\SessionHandler;

class BasketApi implements SingletonInterface
{
    protected $basketExt = [];   // "Basket Extension" - holds extended attributes
    protected $basketExtra = []; // initBasket() uses this for additional information like the current payment/shipping methods

    public function getBasketCount(
        $row,
        $basketExt,
        $variant,
        $quantityIsFloat,
        $ignoreVariant = false
    ) {
        $count = '';
        $uid = $row['uid'];

        if (
            isset($basketExt) &&
            is_array($basketExt) &&
            isset($basketExt[$uid]) &&
            is_array($basketExt[$uid])
        ) {
            $subArr = $basketExt[$uid];
            if (
                $ignoreVariant
            ) {
                $count = 0;
                foreach ($subArr as $subVariant => $subCount) {
                    $count += $subCount;
                }
            } elseif (
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

    public function getQuantity($content, $basketConf)
    {
        $count = '';
        $basketExt = $this->readBasketExt();

        if (
            isset($basketExt) &&
            is_array($basketExt) &&
            !empty($basketExt) &&
            isset($basketConf['ref']) &&
            isset($basketConf['row'])
        ) {
            $typo3VersionArray =
            VersionNumberUtility::convertVersionStringToArray(VersionNumberUtility::getCurrentTypo3Version());
            $typo3VersionMain = $typo3VersionArray['version_main'];
            $conf = [];
            if ($typo3VersionMain < 12) {
                $conf = $GLOBALS['TSFE']->tmpl->setup['plugin.'][TT_PRODUCTS_EXT . '.'] ?? [];
            } else {
                $conf = $GLOBALS['TYPO3_REQUEST']->getAttribute('frontend.typoscript')->getSetupArray()['plugin.'][TT_PRODUCTS_EXT . '.'] ?? [];
            }
            $cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
            $cnfObj->init(
                $conf,
                []
            );

            $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
            $uid = intval($basketConf['ref']);
            $row = $basketConf['row'];
            $variant = '';
            $useArticles = $cnfObj->getUseArticles();

            if (
                isset($basketConf['variant']) &&
                !empty($basketConf['variant'])
            ) {
                $variant = $basketConf['variant'];
            } else {
                $variantApi = GeneralUtility::makeInstance(VariantApi::class);
                $variantRow =
                $variantApi->getVariantRow($row, []);
                $variant =
                $variantApi->getVariantFromProductRow(
                    $row,
                    $variantRow,
                    $useArticles
                );
            }

            $count =
            $this->getBasketCount(
                $row,
                $basketExt,
                $variant,
                $conf['quantityIsFloat']
            );
        }

        return $count;
    }

    public function getTotalQuantity()
    {
        $count = 0;
        $basketExt = $this->readBasketExt();

        if (
            isset($basketExt) &&
            is_array($basketExt)
        ) {
            foreach ($basketExt as $tmpUid => $tmpSubArr) {
                if (is_array($tmpSubArr) && count($tmpSubArr)) {
                    foreach ($tmpSubArr as $tmpExtVar => $tmpCount) {
                        if (
                            $tmpCount > 0 &&
                            is_numeric($tmpCount)
                        ) {
                            $count += $tmpCount;
                        }
                    }
                }
            }
        }

        return $count;
    }

    public function getRecordvariantAndPriceFromRows(
        &$variant,
        &$price,
        &$externalUidArray,
        $externalRowArray
    ) {
        $result = false;
        $variant = '';
        $price = 0;
        $downloadUid = 0;
        foreach ($externalRowArray as $tablename => $externalRow) {
            switch ($tablename) {
                case 'tt_products_downloads':
                    $externalUid = $externalRow['uid'];
                    if (
                        isset($externalRow['price_enable']) &&
                        $externalRow['price_enable'] &&
                        isset($externalRow['price'])
                    ) {
                        $price = $externalRow['price'];
                    }

                    if ($externalUid) {
                        $variant .= \tx_ttproducts_variant_int::EXTERNAL_RECORD_SEPARATOR
                        /* '|records: */ . 'dl=' . $externalUid;
                    }
                    $externalUidArray[$tablename] = $externalUid;
                    break;
                case 'sys_file_reference':
                    if (
                        isset($externalRow['tx_ttproducts_price_enable']) &&
                        $externalRow['tx_ttproducts_price_enable'] &&
                        isset($externalRow['tx_ttproducts_price'])
                    ) {
                        $price = $externalRow['tx_ttproducts_price'];
                    }

                    if (
                        isset($externalUidArray['tt_products_downloads']) &&
                        $externalUidArray['tt_products_downloads'] > 0
                    ) {
                        $variant .= \tx_ttproducts_variant_int::EXTERNAL_QUANTITY_SEPARATOR . 'fal=' . $externalRow['uid'];
                    }
                    $externalUidArray[$tablename] = $externalRow['uid'];
                    break;
            }
        }

        if ($variant != '') {
            $result = true;
        }

        return $result;
    }

    /**
     * get the product rows contained in the basket.
     */
    public function getRecords($where = '1=1')
    {
        $result = false;

        $funcTablename = 'tt_products';
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        $pid_list = '';
        $basketExt = $this->readBasketExt();

        if (isset($basketExt) && is_array($basketExt)) {
            $uidArr = [];

            foreach ($basketExt as $uidTmp => $tmp) {
                if ($uidTmp != 'gift' && !in_array($uidTmp, $uidArr)) {
                    $uidArr[] = intval($uidTmp);
                }
            }

            if (count($uidArr) == 0) {
                return false;
            }
            $where .= ' AND uid IN (' . implode(',', $uidArr) . ')' . ($pid_list != '' ? ' AND pid IN (' . $pid_list . ')' : '') .
            $pageRepository->enableFields($funcTablename);

            $rows =
            $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
                '*',
                'tt_products',
                $where
            );

            if (
                is_array($rows)
            ) {
                $variant = '';
                $quantityIsFloat = false;
                foreach ($rows as $k => $row) {
                    $count =
                    $this->getBasketCount(
                        $row,
                        $basketExt,
                        $variant,
                        $quantityIsFloat,
                        true
                    );
                    if ($count) {
                        $rows[$k]['count'] = $count;
                    }
                }
            }
            $result = $rows;
        }

        return $result;
    }

    public function getWeight($rows)
    {
        $totalWeight = 0;
        if (is_array($rows)) {
            foreach ($rows as $k => $row) {
                $weight = floatval($row['weight']) * floatval($row['count']);
                $totalWeight += $weight;
            }
        }

        return $totalWeight;
    }

    /**
     * get basket record for tracking, billing and delivery data row.
     */
    public function getBasketRec(
        array $row,
        $typeArray = [
            'payment',
            'shipping',
            'handling',
        ]
    ) {
        $extraArray = [];
        if (!empty($row)) {
            foreach ($typeArray as $type) {
                $tmpArray = GeneralUtility::trimExplode(':', $row[$type]);
                $extraArray[$type] = $tmpArray['0'];
            }
        }
        $basketRec = ['tt_products' => $extraArray];

        return $basketRec;
    }

    public function storeItemArray(array $itemArray): void
    {
        SessionHandler::storeSession('itemArray', $itemArray);
    }

    public function readItemArray()
    {
        $result = SessionHandler::readSession('itemArray');

        return $result;
    }

    public function storeCalculatedArray(array $calculatedArray): void
    {
        SessionHandler::storeSession('calculatedArray', $calculatedArray);
    }

    public function readCalculatedArray()
    {
        $result = SessionHandler::readSession('calculatedArray');

        return $result;
    }

    public function readBasketExtra()
    {
        $result = SessionHandler::readSession('basketExtra');

        return $result;
    }

    public function setBasketExt($basketExt): void
    {
        $this->basketExt = $basketExt;
    }

    public function getBasketExt()
    {
        return $this->basketExt;
    }

    public function storeBasketExt(array $basketExt): void
    {
        SessionHandler::storeSession('basketExt', $basketExt);
        $this->setBasketExt($basketExt);
    }

    public function readBasketExt()
    {
        $result = SessionHandler::readSession('basketExt');

        return $result;
    }

    public function removeFromBasketExt($removeBasketExt): void
    {
        $basketExt = $this->readBasketExt();
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
            $this->storeBasketExt($basketExt);
        }
    }

    public function setBasketExtra($basketExtra): void
    {
        $this->basketExtra = $basketExtra;
    }

    public function getBasketExtra()
    {
        return $this->basketExtra;
    }

    public function storeBasketExtra(array $basketExtra): void
    {
        SessionHandler::storeSession('basketExtra', $basketExtra);
    }

    public function storeBasketSetup(array $basketSetup): void
    {
        SessionHandler::storeSession('basketSetup', $basketSetup);
    }

    // Use this only from Middleware. In all cases you should use $conf directly
    public function readBasketSetup()
    {
        $result = SessionHandler::readSession('basketSetup');

        return $result;
    }

    public function addItem(
        &$basketExt,
        $uid,
        $externalVariant,
        $damUid,
        $item,
        $updateMode,
        $bStoreBasket,
        $basketMaxQuantity,
        $alwaysInStock,
        $alwaysUpdateOrderAmount,
        $quantityIsFloat,
        $useArticles
    ): void {
        $variantApi = GeneralUtility::makeInstance(VariantApi::class);
        $editVariantApi = GeneralUtility::makeInstance(EditVariantApi::class);
        $priceObj = GeneralUtility::makeInstance('tx_ttproducts_field_price');

        // quantities for single values are stored in an array. This is necessary because a HTML checkbox does not send any values if it has been unchecked
        if (
            isset($item) &&
            is_array($item) &&
            isset($item['quantity']) &&
            is_array($item['quantity'])
        ) {
            reset($item['quantity']);
            $item['quantity'] = current($item['quantity']);
        }

        if ($updateMode) {
            foreach ($item as $md5 => $quantity) {
                $quantity = $priceObj->toNumber($quantityIsFloat, $quantity);

                if (
                    isset($basketExt[$uid]) &&
                    is_array($basketExt[$uid]) &&
                    $md5 != 'additional'
                ) {
                    foreach ($basketExt[$uid] as $allVariants => $tmp) {
                        $actMd5 = md5($allVariants);

                        // useArticles if you have different prices and therefore articles for color, size, additional and gradings
                        if ($actMd5 == $md5) {
                            $count = $this->getMaxCount($quantity, $basketMaxQuantity, $alwaysInStock, $quantityIsFloat, $uid);
                            $basketExt[$uid][$allVariants] = $count;

                            if (isset($item['additional']) && is_array($item['additional']) &&
                                isset($item['additional'][$actMd5]['giftservice']) && is_array($item['additional'][$actMd5]['giftservice'])) {
                                if (isset($basketExt[$uid][$allVariants . '.']) && !is_array($basketExt[$uid][$allVariants . '.'])) {
                                    $basketExt[$uid][$allVariants . '.'] = [];
                                }

                                if (isset($basketExt[$uid][$allVariants . '.']['additional']) && !is_array($basketExt[$uid][$allVariants . '.']['additional'])) {
                                    $basketExt[$uid][$allVariants . '.']['additional'] = [];
                                }
                                $bHasGiftService = $item['additional'][$actMd5]['giftservice']['1'];
                            if ($bHasGiftService) {
                                $basketExt[$uid][$allVariants . '.']['additional']['giftservice'] = '1';
                            } else {
                                unset($basketExt[$uid][$allVariants . '.']);
                            }
                                }
                        }
                    }
                }
            }
        } else {
            if (!isset($item['quantity'])) {
                return;
            }

            $variant = $variantApi->getVariantFromRawRow($item, $useArticles);
            $editVariant = $editVariantApi->getVariantFromRawRow($item);
            $allVariants =
            $variant .
            ($editVariant != '' ? '|editVariant:' . $editVariant : '') .
            ($externalVariant != '' ? '|records:' . $externalVariant : '');

            if ($damUid) {
                $tableVariant = $variantApi->getTableUid('tx_dam', $damUid);
                $allVariants .= $tableVariant;
            }
            $oldcount = $basketExt[$uid][$allVariants] ?? 0;
            $quantity = 0;
            $quantity = $priceObj->toNumber($quantityIsFloat, $item['quantity']);
            $count = $this->getMaxCount($quantity, $basketMaxQuantity, $alwaysInStock, $quantityIsFloat, $uid);

            if ($count >= 0 && $bStoreBasket) {
                $newcount = $count;

                if ($newcount) {
                    if ($alwaysUpdateOrderAmount == 1) {
                        $basketExt[$uid][$allVariants] = $newcount;
                    } else {
                        $basketExt[$uid][$allVariants] = $oldcount + $newcount;
                    }
                } else {
                    unset($basketExt[$uid][$allVariants]);
                }
            }
        }
    }

    public function getMaxCount($quantity, $basketMaxQuantity, $alwaysInStock, $quantityIsFloat, $uid = 0)
    {
        $count = 0;
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $prodTable = $tablesObj->get('tt_products');
        $row = $prodTable->get($uid);

        if (
            $row['basketmaxquantity'] > 0 &&
            $quantity > $row['basketmaxquantity']
        ) {
            $basketmaxrowquantity = MathUtility::convertToPositiveInteger($row['basketmaxquantity']);
            $count = MathUtility::forceIntegerInRange($quantity, 0, (int) $basketmaxrowquantity, 0);
            $quantity = $count; // reduce the quantitiy to the product's maximum allowed quantity
        }

        if (
            $basketMaxQuantity == 'inStock' &&
            !$alwaysInStock &&
            !empty($uid)
        ) {
            $count = MathUtility::forceIntegerInRange(
                $quantity,
                0,
                (int) $row['inStock'],
                                                      0
            );
        } elseif (
            $basketMaxQuantity == 'creditpoint' &&
            !empty($uid)
        ) {
            $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
            $creditpointsObj = GeneralUtility::makeInstance('tx_ttproducts_field_creditpoints');
            $missingCreditpoints = 0;
            $creditpointsObj->getBasketMissingCreditpoints($row['creditpoints'] * $quantity, $missingCreditpoints, $tmp);

            if ($quantity > 1 && $missingCreditpoints > 0) {
                $reduceQuantity = intval($missingCreditpoints / $row['creditpoints']);
                if ($missingCreditpoints > $reduceQuantity * $row['creditpoints']) {
                    ++$reduceQuantity;
                }
                if ($quantity - $reduceQuantity >= 1) {
                    $count = $quantity - $reduceQuantity;
                } else {
                    $count = 0;
                }
            } else {
                $count = ($missingCreditpoints > 0 ? 0 : $quantity);
            }
        } elseif ($quantityIsFloat) {
            $count = floatval($quantity);
            if ($count < 0) {
                $count = 0;
            }
            if ($count > $basketMaxQuantity) {
                $count = $basketMaxQuantity;
            }
        } else {
            $count = MathUtility::forceIntegerInRange($quantity, 0, (int) $basketMaxQuantity, 0);
        }

        return $count;
    }

    public function process(
        array &$basketExt,
        array $basketExtRaw,
        $updateMode,
        $bStoreBasket,
        $basketMaxQuantity,
        $alwaysInStock,
        $alwaysUpdateOrderAmount,
        $quantityIsFloat,
        $useArticles
    ): void {
        $damUid = 0;
        if (isset($basketExtRaw['dam'])) {
            $damUid = intval($basketExtRaw['dam']);
        }

        foreach ($basketExtRaw as $uid => $basketItem) {
            if (
                MathUtility::canBeInterpretedAsInteger($uid)
            ) {
                if (isset($basketItem['quantity'])) {
                    if (
                        isset($basketExtRaw['dam'])
                    ) {
                        foreach ($basketItem as $damUid => $damBasketItem) {
                            $this->addItem(
                                $basketExt,
                                $uid,
                                '',
                                $damUid,
                                $damBasketItem,
                                $updateMode,
                                $bStoreBasket,
                                $basketMaxQuantity,
                                $alwaysInStock,
                                $alwaysUpdateOrderAmount,
                                $quantityIsFloat,
                                $useArticles
                            );
                        }
                    } else {
                        $this->addItem(
                            $basketExt,
                            $uid,
                            '',
                            $damUid,
                            $basketItem,
                            $updateMode,
                            $bStoreBasket,
                            $basketMaxQuantity,
                            $alwaysInStock,
                            $alwaysUpdateOrderAmount,
                            $quantityIsFloat,
                            $useArticles
                        );
                    }
                } else {
                    $addItems = false;
                    foreach ($basketItem as $basketKey => $basketValue) {
                        if (
                            isset($basketValue) &&
                            is_array($basketValue) &&
                            isset($basketValue['quantity'])
                        ) {
                            $addItems = true;
                            $this->addItem(
                                $basketExt,
                                $uid,
                                $basketKey,
                                '',
                                $basketValue,
                                $updateMode,
                                $bStoreBasket,
                                $basketMaxQuantity,
                                $alwaysInStock,
                                $alwaysUpdateOrderAmount,
                                $quantityIsFloat,
                                $useArticles
                            );
                        }
                    }

                    if ($addItems) {
                    } else {
                        $this->addItem(
                            $basketExt,
                            $uid,
                            '',
                            '',
                            $basketItem,
                            $updateMode,
                            $bStoreBasket,
                            $basketMaxQuantity,
                            $alwaysInStock,
                            $alwaysUpdateOrderAmount,
                            $quantityIsFloat,
                            $useArticles
                        );
                    }
                }
            }
        }

        // I did not find another possibility to delete elements completely from a multidimensional array
        // than to recreate the array
        $basketExtNew = [];
        foreach ($basketExt as $tmpUid => $tmpSubArr) {
            if (is_array($tmpSubArr) && count($tmpSubArr)) {
                foreach ($tmpSubArr as $tmpExtVar => $tmpCount) {
                    if (
                        $tmpCount > 0 &&
                        (
                            $quantityIsFloat ||
                            MathUtility::canBeInterpretedAsInteger($tmpCount)
                        )
                    ) {
                        $basketExtNew[$tmpUid][$tmpExtVar] = $basketExt[$tmpUid][$tmpExtVar];
                        if (
                            isset($basketExt[$tmpUid][$tmpExtVar . '.']) &&
                            is_array($basketExt[$tmpUid][$tmpExtVar . '.'])
                        ) {
                            $basketExtNew[$tmpUid][$tmpExtVar . '.'] = $basketExt[$tmpUid][$tmpExtVar . '.'];
                        }
                    } elseif (is_array($tmpCount)) {
                        $basketExtNew[$tmpUid][$tmpExtVar] = $tmpCount;
                    } else {
                        // nothing
                    }
                }
            } else {
                $basketExtNew[$tmpUid] = $tmpSubArr;
            }
        }
        $basketExt = $basketExtNew;

        if ($bStoreBasket) {
            if (is_array($basketExt) && count($basketExt)) {
                $this->storeBasketExt($basketExt);
            } else {
                $this->storeBasketExt([]);
            }
        }
    }
}
