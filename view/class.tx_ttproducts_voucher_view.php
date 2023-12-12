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
 * functions for the voucher system
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage tt_products
 */
use JambageCom\TtProducts\Api\Localization;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class tx_ttproducts_voucher_view extends tx_ttproducts_table_base_view
{
    public $amount;
    public $code;
    public $bValid;
    public $marker = 'VOUCHER';
    public $usedCodeArray = [];

    /**
     * Template marker substitution
     * Fills in the markerArray with data for the voucher.
     *
     * @access private
     */
    public function getSubpartMarkerArray(
        &$subpartArray,
        &$wrappedSubpartArray,
        $charset = ''
    ): void {
        $modelObj = $this->getModelObj();
        $languageObj = GeneralUtility::makeInstance(Localization::class);
        $subpartArray['###SUB_VOUCHERCODE###'] = '';
        $code = $modelObj->getVoucherCode();
        $wrappedSubpartArray['###SUB_VOUCHERCODE_START###'] = [];

        if (
            $modelObj->getValid() &&
            $code != ''
        ) {
            $subpartArray['###SUB_VOUCHERCODE_DISCOUNTWRONG###'] = '';
            $wrappedSubpartArray['###SUB_VOUCHERCODE_DISCOUNT###'] = [];
        } else {
            if (!empty($code)) {
                $tmp = $languageObj->getLabel('voucher_invalid');
                $tmpArray = explode('|', $tmp);
                $subpartArray['###SUB_VOUCHERCODE_DISCOUNT###'] = $tmpArray[0] . htmlspecialchars($modelObj->getVoucherCode()) . $tmpArray[1];
                $wrappedSubpartArray['###SUB_VOUCHERCODE_DISCOUNTWRONG###'] = [];
            } else {
                $subpartArray['###SUB_VOUCHERCODE_DISCOUNT###'] = '';
                $subpartArray['###SUB_VOUCHERCODE_DISCOUNTWRONG###'] = '';
            }
        }
    }

    /**
     * Template marker substitution
     * Fills in the markerArray with data for the voucher.
     *
     * @access private
     */
    public function getMarkerArray(
        &$markerArray
    ): void {
        $priceViewObj = GeneralUtility::makeInstance('tx_ttproducts_field_price_view');
        $modelObj = $this->getModelObj();
        $markerArray['###INSERT_VOUCHERCODE###'] = 'recs[tt_products][vouchercode]';

        $voucherCode = $modelObj->getVoucherCode();
        if (!$voucherCode) {
            $voucherCode = $modelObj->getLastVoucherCodeUsed();
        }

        $amount = $modelObj->getRebateAmount();
        $markerArray['###VALUE_VOUCHERCODE###'] = htmlspecialchars($voucherCode);
        $markerArray['###VOUCHER_DISCOUNT###'] = $priceViewObj->priceFormat(abs($amount));
    } // getMarkerArray
}
