<?php

namespace JambageCom\TtProducts\Api;

/***************************************************************
*  Copyright notice
*
*  (c) 2016 Franz Holzinger <franz@ttproducts.de>
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
 * @package TYPO3
 * @subpackage tt_products
 */
use JambageCom\Div2007\Utility\TableUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class BasketApi
{
    public static function getQuantity($content, $basketConf)
    {
        $count = '';
        $basketExt = \tx_ttproducts_control_basket::getStoredBasketExt();

        if (
            isset($basketExt) &&
            is_array($basketExt) &&
            !empty($basketExt) &&
            isset($basketConf['ref']) &&
            isset($basketConf['row'])
        ) {
            $conf = $GLOBALS['TSFE']->tmpl->setup['plugin.'][TT_PRODUCTS_EXT . '.'];
            $cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
            $cnfObj->init(
                $conf,
                []
            );

            $cObj = GeneralUtility::makeInstance('TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer');	// Local cObj.
            $cObj->start([]);
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
                $funcTablename = 'tt_products';
                $productTable = $tablesObj->get($funcTablename);
                $variantRow =
                    $productTable->variant->getVariantRow($row, []);
                $variant =
                    $productTable->variant->getVariantFromProductRow(
                        $row,
                        $variantRow,
                        $useArticles
                    );
            }

            \tx_ttproducts_control_basket::init(
                $conf,
                $tablesObj,
                $conf['pid_list'],
                $useArticles
            );

            $count =
                \tx_ttproducts_control_basket::getBasketCount(
                    $row,
                    $variant,
                    $conf['quantityIsFloat']
                );
            \tx_ttproducts_control_basket::destruct();
        }

        return $count;
    }

    /**
     * get the product rows contained in the basket.
     */
    public static function getRecords($conf, $where = '1=1')
    {
        $result = false;

        $funcTablename = 'tt_products';
        $recs = \tx_ttproducts_control_basket::getRecs();
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');

        $pid_list = $conf['pid_list'];
        \tx_ttproducts_control_basket::init($conf, $tablesObj, $pid_list, $conf['useArticles'], $recs);
        $basketExt = \tx_ttproducts_control_basket::getBasketExt();

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
            //             $where .= ' AND uid IN (' . implode(',', $uidArr) . ')';
            $where .= ' AND uid IN (' . implode(',', $uidArr) . ')' . ($pid_list != '' ? ' AND pid IN (' . $pid_list . ')' : '') . TableUtility::enableFields($funcTablename);

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
                foreach ($rows as $k => $row) {
                    $count =
                        \tx_ttproducts_control_basket::getBasketCount(
                            $row,
                            $variant,
                            $conf['quantityIsFloat'],
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

    public static function getRecordvariantAndPriceFromRows(
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
                        $variant .= '|records:dl=' . $externalUid;
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
     * get basket record for tracking, billing and delivery data row.
     */
    public static function getBasketRec(
        $row,
        $typeArray = [
            'payment',
            'shipping',
            'handling',
        ]
    ) {
        $extraArray = [];
        foreach ($typeArray as $type) {
            $tmpArray = GeneralUtility::trimExplode(':', $row[$type]);
            $extraArray[$type] = $tmpArray['0'];
        }

        $basketRec = ['tt_products' => $extraArray];

        return $basketRec;
    }
}
