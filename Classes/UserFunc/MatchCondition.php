<?php
namespace JambageCom\TtProducts\UserFunc;


/***************************************************************
*  Copyright notice
*
*  (c) 2018 Franz Holzinger (franz@ttproducts.de)
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
 * functions for the TypoScript conditions
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;


class MatchCondition {

    public function checkShipping (
        $params
    ) {
        $result = false;

        if (isset($params) && is_array($params)) {
            \tx_ttproducts_control_basket::storeNewRecs();
            $recs = \tx_ttproducts_control_basket::getStoredRecs();
            \tx_ttproducts_control_basket::setRecs($recs);
            $infoArray = \tx_ttproducts_control_basket::getInfoArray();
            \tx_ttproducts_control_basket::fixCountries($infoArray);
            $type = $params['0'];
            $field = $params['1'];
            $operator = '=';
            $value = '';
            if (strpos($params['2'], $operator) !== false) {
                $value = ltrim($params['2'], ' =');
            } else if (strpos($params['2'], 'IN') !== false) {
                $operator = 'IN';
                if (strpos($params['2'], 'NOT IN') !== false) {
                    $operator = 'NOT IN';
                }
                $value = trim($params['3']);
            }

            if ($operator == '=') {
                $result = ($infoArray[$type][$field] == $value);
            } else if ($operator == 'IN') {
                $valueArray = GeneralUtility::trimExplode(',', $value);
                $result = in_array($infoArray[$type][$field], $valueArray);
            } else if ($operator == 'NOT IN') {
                $valueArray = GeneralUtility::trimExplode(',', $value);
                $result = !in_array($infoArray[$type][$field], $valueArray);
            }

            if (
                !$result &&
                (
                    !is_array($infoArray) ||
                    !is_array($infoArray[$type]) ||
                    !isset($infoArray[$type][$field])
                ) &&
                \JambageCom\Div2007\Utility\CompatibilityUtility::isLoggedIn()
            ) {
                if ($field == 'country_code') {
                    $field = 'static_info_country';
                }
                $value = str_replace('\'', '', $value);

                if ($operator == '=') {
                    $result = ($GLOBALS['TSFE']->fe_user->user[$field] == $value);
                } else if ($operator == 'IN') {
                    $valueArray = GeneralUtility::trimExplode(',', $value);
                    $result = in_array($GLOBALS['TSFE']->fe_user->user[$field], $valueArray);
                } else if ($operator == 'NOT IN') {
                    $valueArray = GeneralUtility::trimExplode(',', $value);
                    $result = !in_array($GLOBALS['TSFE']->fe_user->user[$field], $valueArray);
                }
            }
        }

        return $result;
    }

    public function hasBulkilyItem ($where) {
        $bBukily = false;
        $cObj = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);	// Local cObj.
        $cObj->start(array());
        \tx_ttproducts_control_basket::setBasketExt(\tx_ttproducts_control_basket::getStoredBasketExt());
        $basketExt = \tx_ttproducts_control_basket::getBasketExt();

        if (isset($basketExt) && is_array($basketExt)) {

            $uidArr = array();

            foreach($basketExt as $uidTmp => $tmp) {
                if ($uidTmp != 'gift' && !in_array($uidTmp, $uidArr)) {
                    $uidArr[] = intval($uidTmp);
                }
            }

            if (count($uidArr) == 0) {
                return false;
            }
            $where .= ' AND uid IN (' . implode(',', $uidArr) . ')';
            $enableFields = \JambageCom\Div2007\Utility\TableUtility::enableFields('tt_products');
            $where .= $enableFields;

            $rcArray = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'tt_products', $where);
            foreach ($rcArray as $uid => $row) {
                if ($row['bulkily']) {
                    $bBukily = true;
                    break;
                }
            }
        }

        return ($bBukily);
    }
}
