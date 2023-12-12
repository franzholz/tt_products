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
 * creditpoints functions
 *
 * @author  Els Verberne <verberne@bendoo.nl>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage tt_products
 */
use JambageCom\Div2007\Utility\CompatibilityUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class tx_ttproducts_creditpoints_div
{
    public static function getCreditPointsFeuser()
    {
        $result = 0;

        if (
            CompatibilityUtility::isLoggedIn() &&
            isset($GLOBALS['TSFE']->fe_user) &&
            is_array($GLOBALS['TSFE']->fe_user->user)
        ) {
            $result = $GLOBALS['TSFE']->fe_user->user['tt_products_creditpoints'];
        }

        return $result;
    }

    public static function getPriceFactor($conf)
    {
        $pricefactor = 0;

        if (isset($conf['creditpoints.'])) {
            if (isset($conf['creditpoints.']['priceprod'])) {
                $pricefactor = $conf['creditpoints.']['priceprod'];
            }
            if (isset($conf['creditpoints.']['pricefactor'])) {
                $pricefactor = $conf['creditpoints.']['pricefactor'];
            }
            $pricefactor = doubleval($pricefactor);
        }

        return $pricefactor;
    }

    public static function getUsedCreditpoints($recs)
    {
        $creditpoints = 0;
        $creditpointsObj = GeneralUtility::makeInstance('tx_ttproducts_field_creditpoints');

        $autoCreditpointsTotal = $creditpointsObj->getBasketTotal();

        if ($autoCreditpointsTotal > 0) {
            $creditpoints = $autoCreditpointsTotal;
        } elseif (
            isset($recs) && is_array($recs) &&
            isset($recs['tt_products']) && is_array($recs['tt_products']) &&
            isset($recs['tt_products']['creditpoints']) &&
            $recs['tt_products']['creditpoints'] > 0
        ) {
            $creditpoints = $recs['tt_products']['creditpoints'];
        }

        $userCreditpoints = tx_ttproducts_creditpoints_div::getCreditPointsFeuser();

        if ($creditpoints > $userCreditpoints) {
            $creditpoints = $userCreditpoints;
        }

        $creditpoints = doubleval($creditpoints);

        return $creditpoints;
    }

    /**
     * Returns the number of creditpoints for the frontend user.
     */
    public static function getCreditPoints($amount, $creditpointsConf)
    {
        $type = '';
        $creditpoints = 0;
        if (is_array($creditpointsConf)) {
            foreach ($creditpointsConf as $k1 => $priceCalcTemp) {
                if (is_array($priceCalcTemp)) {
                    foreach ($priceCalcTemp as $k2 => $v2) {
                        if (!is_array($v2)) {
                            switch ($k2) {
                                case 'type':
                                    $type = $v2;
                                    break;
                            }
                        }
                    }
                    $dumCount = 0;
                    $creditpoints = doubleval($priceCalcTemp['prod.']['1']);

                    if ($type != 'price') {
                        break;
                    }
                    krsort($priceCalcTemp['prod.']);
                    reset($priceCalcTemp['prod.']);

                    foreach ($priceCalcTemp['prod.'] as $k2 => $points) {
                        if ($amount >= intval($k2)) { // only the highest value for this count will be used; 1 should never be reached, this would not be logical
                            $creditpoints = $points;
                            break; // finish
                        }
                    }
                }
            }
        }

        return $creditpoints;
    } // getCreditPoints

    /**
     * adds the number of creditpoints for the frontend user.
     */
    public static function addCreditPoints($username, $creditpoints): void
    {
        if ($username) {
            $uid_voucher = '';
            // get the "old" creditpoints for the user
            $res1 = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid, tt_products_creditpoints', 'fe_users', 'username=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($username, 'tt_products_creditpoints'));
            if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res1)) {
                $ttproductscreditpoints = $row['tt_products_creditpoints'];
                $uid_voucher = $row['uid'];
            }
            if ($uid_voucher) {
                $fieldsArrayFeUserCredit = [];
                $fieldsArrayFeUserCredit['tt_products_creditpoints'] = $ttproductscreditpoints + $creditpoints;

                $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
                    'fe_users',
                    'uid=' . intval($uid_voucher),
                    $fieldsArrayFeUserCredit
                );
            }
        }
    }
}
