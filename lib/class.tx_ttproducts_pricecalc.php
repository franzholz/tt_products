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
 * basket price calculation functions
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


use TYPO3\CMS\Core\Utility\GeneralUtility;


class tx_ttproducts_pricecalc extends tx_ttproducts_pricecalc_base {

    public function getCalculatedData (
        &$itemArray,
        $conf,
        $type,
        &$priceReduction,
        &$discountArray,
        $priceTotalTax,
        $bUseArticles,
        $taxIncluded,
        $bMergeArticles = true,
        $uid = 0
    ) {
        $sql = GeneralUtility::makeInstance('tx_ttproducts_sql');

        if (!$itemArray || !count($itemArray)) {
            return;
        }
        ksort($conf);

        foreach ($conf as $k1 => $priceCalcTemp) {

            if (!is_array($priceCalcTemp)) {
                continue;
            }
            $countedItems = [];
            $pricefor1 = doubleval($priceCalcTemp['prod.']['1']);
            $dumCount = 0;

            // loop over all items in the basket indexed by sort string
            foreach ($itemArray as $sort => $actItemArray) {
                foreach ($actItemArray as $k2 => $actItem) {

                    $row = $actItem['rec'];

                    if (is_array($priceCalcTemp['sql.']))    {
                        if (!($bIsValid = $sql->isValid($row, $priceCalcTemp['sql.']['where'])))    {
                            continue;
                        }
                    }

                    // has a price reduction already been calculated before ?
                    if ($priceReduction[$row['uid']] == 1) {
                        continue;
                    }

                    // count all items which will apply to the discount price
                    $count2 = $actItem['count'];

                    if (((float) $count2 > 0) && ($row['price'] == $pricefor1)) {
                        $countedItems[$k1][] = array ('sort' => $sort);
                        $dumCount += $count2;
                    }
                }
            }

                // nothing found?
            if ($dumCount == 0) {
                continue;
            }

            $priceTotalTemp = 0;
            $countTemp = $dumCount;
            krsort($priceCalcTemp['prod.']);
            foreach ($priceCalcTemp['prod.'] as $k2 => $price2) {

                if ((float) $k2 > 0) {
                    while ($countTemp >= (float) $k2) {
                        $countTemp -= (float) $k2;
                        $priceTotalTemp += doubleval($price2);
                    }
                }
            }
            $priceProduct = ((float) $dumCount > 0 ? ($priceTotalTemp / $dumCount) : 0);

            foreach ($countedItems[$k1] as $k3 => $v3) {
                foreach ($itemArray[$v3['sort']] as $k4=>$actItem) {
                    $itemArray[$v3['sort']][$k4][$type] = $priceProduct;
                }
            }
        }
    } // getCalculatedData
}

