<?php

declare(strict_types=1);

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
 * functions for the title field
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 *
 */

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use JambageCom\TtProducts\Api\CustomerApi;

class tx_ttproducts_field_creditpoints extends tx_ttproducts_field_base
{
    public function getBasketTotal()
    {
        $rc = 0;
        $basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');
        $itemArray = $basketObj->getItemArray();

        if (count($itemArray)) {
            $pricefactor = 0;
            $mode = 'normal';
            if (
                isset($this->conf['creditpoints.']) &&
                isset($this->conf['creditpoints.']['mode'])
            ) {
                $mode = $this->conf['creditpoints.']['mode'];
                $pricefactor = tx_ttproducts_creditpoints_div::getPriceFactor($this->conf);
            }

            $creditpointsTotal = 0;
            // loop over all items in the basket indexed by a sort string
            foreach ($itemArray as $sort => $actItemArray) {
                foreach ($actItemArray as $k1 => $actItem) {
                    $row = $actItem['rec'];
                    $count = $actItem['count'];

                    if ($row['creditpoints'] > 0) {
                        $creditpointsTotal += $row['creditpoints'] * $count;
                    } elseif ($mode == 'auto' && $pricefactor) {
                        $creditpointsTotal += ($actItem['priceTax'] * $count) / $pricefactor;
                    }
                }
            }
            $rc = $creditpointsTotal;
        }

        return $rc;
    }

    public function getBasketMissingCreditpoints($addCreditpoints, &$missing, &$remaining): void
    {
        $feUserRecord = CustomerApi::getFeUserRecord();
        $feuserCreditpoints = tx_ttproducts_creditpoints_div::getCreditPointsFeuser($feUserRecord);
        $creditpointsTotal = $this->getBasketTotal() + $addCreditpoints;
        $missing = $creditpointsTotal - $feuserCreditpoints;
        $missing = ($missing > 0 ? $missing : 0);
        $remaining = $feuserCreditpoints - $creditpointsTotal;
    }

    public function getMissingCreditpoints($fieldname, $row, &$missing, &$remaining): void
    {
        $feUserRecord = CustomerApi::getFeUserRecord();
        $creditpointsTotal = $this->getBasketTotal();
        $feuserCreditpoints = tx_ttproducts_creditpoints_div::getCreditPointsFeuser($feUserRecord);
        $missing = $creditpointsTotal + $row[$fieldname] - $feuserCreditpoints;
        $missing = ($missing > 0 ? $missing : 0);
        $remaining = $feuserCreditpoints - $creditpointsTotal - $row[$fieldname];
    }

    // reduces the amount of creditpoints of the FE user by the total amount of creditpoints from the products.
    // It returns the number of creditpoints by which the account of the FE user has been reduced. false is if no FE user is logged in.
    public function pay()
    {
        $rc = false;
        $feUserRecord = CustomerApi::getFeUserRecord();
        $context = GeneralUtility::makeInstance(Context::class);

        if ($context->getPropertyFromAspect('frontend.user', 'isLoggedIn')) {
            $creditpointsTotal = $this->getBasketTotal();

            if ($creditpointsTotal) {
                $fieldsArrayFeUsers = [];
                $fieldsArrayFeUsers['tt_products_creditpoints'] = $feUserRecord['tt_products_creditpoints'] - $creditpointsTotal;
                if ($fieldsArrayFeUsers['tt_products_creditpoints'] < 0) {
                    $fieldsArrayFeUsers['tt_products_creditpoints'] = 0;
                    $rc = $feUserRecord['tt_products_creditpoints'];
                }
                if ($feUserRecord['tt_products_creditpoints'] != $fieldsArrayFeUsers['tt_products_creditpoints']) {
                    $feUserRecord['tt_products_creditpoints'] = $fieldsArrayFeUsers['tt_products_creditpoints']; // store it also for the global FE user data
                    $GLOBALS['TYPO3_DB']->exec_UPDATEquery('fe_users', 'uid=' . intval($feUserRecord['uid']), $fieldsArrayFeUsers);
                    $rc = $creditpointsTotal;
                }
            }
        }

        return $rc;
    }
}
