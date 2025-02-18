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
 * functions for the basket item view of credit points
 *
 * deprecated for some markers which do not belong to auto credit points
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 *
 */
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CreditpointsViewApi implements SingletonInterface
{
    protected $creditsCategory = -1;

    public function __construct($conf)
    {
        if (isset($conf['creditsCategory'])) {
            $this->creditsCategory = $conf['creditsCategory'];
        }
    }

    public function getItemMarkerSubpartArrays(
        array $itemArray,
        &$markerArray,
        &$subpartArray,
        &$wrappedSubpartArray,
        $conf,
        $priceViewObj,
        $calculatedArray,
        $amountCreditpoints
    ): void {
        $creditpointsObj = GeneralUtility::makeInstance(\tx_ttproducts_field_creditpoints::class);
        $pricefactor = tx_ttproducts_creditpoints_div::getPriceFactor($conf);
        $markerArray['###AMOUNT_CREDITPOINTS###'] = $amountCreditpoints;
        $autoCreditpointsTotal = $amountCreditpoints;

        $sum_pricecredits_total_totunits_no_tax = 0;
        $sum_price_total_totunits_no_tax = 0;
        $sum_pricecreditpoints_total_totunits = 0;
        $creditpoints = 0;

        // loop over all items in the basket indexed by sorting text
        foreach ($itemArray as $sort => $actItemArray) {
            foreach ($actItemArray as $k1 => $actItem) {
                $row = $actItem['rec'];
                if (!$row) {	// avoid bug with missing row
                    continue 1;
                }
                $pricecredits_total_totunits_no_tax = 0;
                $pricecredits_total_totunits_tax = 0;
                if ($row['category'] == $this->creditsCategory) {
                    // creditpoint system start
                    $pricecredits_total_totunits_no_tax = $actItem['totalNoTax'] * ($row['unit_factor'] ?? 0);
                    $pricecredits_total_totunits_tax = $actItem['totalTax'] * ($row['unit_factor'] ?? 0);
                }
                //                 $markerArray['###PRICE_TOTAL_TOTUNITS_NO_TAX###'] = $priceViewObj->priceFormat($pricecredits_total_totunits_no_tax);
                //                 $markerArray['###PRICE_TOTAL_TOTUNITS_TAX###'] = $priceViewObj->priceFormat($pricecredits_total_totunits_tax);
                $sum_pricecredits_total_totunits_no_tax += $pricecredits_total_totunits_no_tax;
                $sum_price_total_totunits_no_tax += $pricecredits_total_totunits_no_tax;
                $sum_pricecreditpoints_total_totunits += $pricecredits_total_totunits_no_tax;
            }
        }

        $creditpoints = $autoCreditpointsTotal + $sum_pricecreditpoints_total_totunits * \tx_ttproducts_creditpoints_div::getCreditPoints($sum_pricecreditpoints_total_totunits, $conf['creditpoints.']);
        $markerArray['###AUTOCREDITPOINTS_TOTAL###'] = number_format($autoCreditpointsTotal, 0);
        $markerArray['###AUTOCREDITPOINTS_PRICE_TOTAL_TAX###'] = $priceViewObj->priceFormat($autoCreditpointsTotal * $pricefactor);
        $remainingCreditpoints = 0;
        $creditpointsObj->getBasketMissingCreditpoints(0, $tmp, $remainingCreditpoints);
        $markerArray['###AUTOCREDITPOINTS_REMAINING###'] = number_format($remainingCreditpoints, 0);

        $markerArray['###CREDITPOINTS_AVAILABLE###'] = $amountCreditpoints;
        $markerArray['###USERCREDITPOINTS_PRICE_TOTAL_TAX###'] = $priceViewObj->priceFormat(($autoCreditpointsTotal < $amountCreditpoints ? $autoCreditpointsTotal : $amountCreditpoints) * $pricefactor);

        // maximum1 amount of creditpoint to change is amount of FE user minus amount already spended in the credit-shop
        $max1_creditpoints = $amountCreditpoints;
            // maximum2 amount of creditpoint to change is amount bought multiplied with creditpointfactor
        $max2_creditpoints = 0;

        if ($pricefactor > 0) {
            $max2_creditpoints = intval(($calculatedArray['priceTax']['total']['ALL'] - $calculatedArray['priceTax']['vouchertotal']['ALL']) / $pricefactor);
        }
        // real maximum amount of creditpoint to change is minimum of both maximums
        $markerArray['###AMOUNT_CREDITPOINTS_MAX###'] = number_format(min($max1_creditpoints, $max2_creditpoints), 0);

        // if quantity is 0 than
        if ($amountCreditpoints == '0') {
            $subpartArray['###SUB_CREDITPOINTS_DISCOUNT###'] = '';
            $wrappedSubpartArray['###SUB_CREDITPOINTS_DISCOUNT_EMPTY###'] = '';
            $subpartArray['###SUB_CREDITPOINTS_AMOUNT_EMPTY###'] = '';
            $subpartArray['###SUB_CREDITPOINTS_AMOUNT###'] = '';
        } else {
            $wrappedSubpartArray['###SUB_CREDITPOINTS_DISCOUNT###'] = '';
            $subpartArray['###SUB_CREDITPOINTS_DISCOUNT_EMPTY###'] = '';
            $wrappedSubpartArray['###SUB_CREDITPOINTS_AMOUNT_EMPTY###'] = '';
            $wrappedSubpartArray['###SUB_CREDITPOINTS_AMOUNT###'] = '';
        }
        $markerArray['###CHANGE_AMOUNT_CREDITPOINTS###'] = 'recs[tt_products][creditpoints]';
        if (empty($recs['tt_products']['creditpoints'])) {
            $markerArray['###AMOUNT_CREDITPOINTS_QTY###'] = 0;
            $subpartArray['###SUB_CREDITPOINTS_DISCOUNT###'] = '';
            // Added Els8: put credit_discount 0 for plain text email
            $markerArray['###CREDIT_DISCOUNT###'] = '0.00';
        } else {
            // quantity chosen can not be larger than the maximum amount, above calculated
            if ($recs['tt_products']['creditpoints'] > min($max1_creditpoints, $max2_creditpoints)) {
                $recs['tt_products']['creditpoints'] = min($max1_creditpoints, $max2_creditpoints);
            }
            $markerArray['###AMOUNT_CREDITPOINTS_QTY###'] = number_format($recs['tt_products']['creditpoints'], 0);
            $subpartArray['###SUB_CREDITPOINTS_DISCOUNT_EMPTY###'] = '';
            $markerArray['###CREDIT_DISCOUNT###'] = $priceViewObj->priceFormat($calculatedArray['priceTax']['creditpoints']);
        }
        // Added els5: CREDITPOINTS_SPENDED: creditpoint needed, check if user has this amount of creditpoints on his FE user data, only if user has logged in
        $markerArray['###CREDITPOINTS_SPENDED###'] = $sum_pricecredits_total_totunits_no_tax;
        if ($sum_pricecredits_total_totunits_no_tax <= $amountCreditpoints) {
            $subpartArray['###SUB_CREDITPOINTS_SPENDED_EMPTY###'] = '';
            $markerArray['###CREDITPOINTS_SPENDED###'] = $sum_pricecredits_total_totunits_no_tax;
            // new saldo: creditpoints
            $markerArray['###AMOUNT_CREDITPOINTS###'] = $amountCreditpoints - $markerArray['###CREDITPOINTS_SPENDED###'];
        } else {
            if (!$markerArray['###FE_USER_UID###']) {
                $subpartArray['###SUB_CREDITPOINTS_SPENDED_EMPTY###'] = '';
            } else {
                $markerArray['###CREDITPOINTS_SPENDED_ERROR###'] = 'Wijzig de artikelen in de kurkenshop: onvoldoende kurken op uw saldo (' . $amountCreditpoints . ') . '; // TODO
                $markerArray['###CREDITPOINTS_SPENDED###'] = '&nbsp;';
            }
        }
        $markerArray['###CREDITPOINTS_SAVED###'] = number_format($creditpoints, 0);
    }
}
