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
 * basket price calculation functions using the price tables
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 */
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class tx_ttproducts_graduated_price_view extends tx_ttproducts_table_base_view
{
    public $marker = 'GRADPRICE';

    private function getFormulaMarkerArray(
        $basketExtra,
        $basketRecs,
        $row,
        $priceFormula,
        $bTaxIncluded,
        $bEnableTaxZero,
        &$markerArray,
        $suffix = ''
    ): void {
        if (isset($priceFormula) && is_array($priceFormula)) {
            $marker = $this->getMarker();
            $cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
            $conf = $cnfObj->getConf();

            $priceObj = GeneralUtility::makeInstance('tx_ttproducts_field_price');
            $priceViewObj = GeneralUtility::makeInstance('tx_ttproducts_field_price_view');
            foreach ($priceFormula as $field => $value) {
                $keyMarker = '###' . $marker . '_' . strtoupper($field) . $suffix . '###';
                if (
                    !isset($GLOBALS['TCA'][$this->getModelObj()->getTablename()]['columns'][$field])
                ) {
                    $value = '';
                }
                $markerArray[$keyMarker] = $value;
            }

            $priceNoTax =
                $priceObj->getPrice(
                    $basketExtra,
                    $basketRecs,
                    $priceFormula['formula'],
                    false,
                    $row,
                    $bTaxIncluded,
                    $bEnableTaxZero
                );
            $priceTax =
                $priceObj->getPrice(
                    $basketExtra,
                    $basketRecs,
                    $priceFormula['formula'],
                    true,
                    $row,
                    $bTaxIncluded,
                    $bEnableTaxZero
                );
            $keyMarker = '###' . $marker . '_PRICE_TAX' . $suffix . '###';
            $markerArray[$keyMarker] = $priceViewObj->priceFormat($priceTax);
            $keyMarker = '###' . $marker . '_PRICE_NO_TAX' . $suffix . '###';
            $markerArray[$keyMarker] = $priceViewObj->priceFormat($priceNoTax);

            $basePriceTax = $priceObj->getResellerPrice($basketExtra, $basketRecs, $row, 1);
            $basePriceNoTax = $priceObj->getResellerPrice($basketExtra, $basketRecs, $row, 0);

            if ($basePriceTax) {
                $skontoTax = ($basePriceTax - $priceTax);
                $tmpPercentTax = number_format(($skontoTax / $basePriceTax) * 100, $conf['percentDec']);
                $skontoNoTax = ($basePriceNoTax - $priceNoTax);
                $tmpPercentNoTax = number_format(($skontoNoTax / $basePriceNoTax) * 100, $conf['percentDec']);
            } else {
                $skontoTax = 'total';
                $skontoNoTax = 'total';
                $tmpPercentTax = 'infinite';
                $tmpPercentNoTax = 'infinite';
            }

            $keyMarker = '###' . $marker . '_PRICE_TAX_DISCOUNT' . $suffix . '###';
            $markerArray[$keyMarker] = $priceViewObj->priceFormat($skontoTax);
            $keyMarker = '###' . $marker . '_PRICE_NO_TAX_DISCOUNT' . $suffix . '###';
            $markerArray[$keyMarker] = $priceViewObj->priceFormat($skontoNoTax);
            $keyMarker = '###' . $marker . '_PRICE_TAX_DISCOUNT_PERCENT' . $suffix . '###';
            $markerArray[$keyMarker] = $priceViewObj->priceFormat($tmpPercentTax);
            $keyMarker = '###' . $marker . '_PRICE_NO_TAX_DISCOUNT_PERCENT' . $suffix . '###';
            $markerArray[$keyMarker] = $priceViewObj->priceFormat($tmpPercentNoTax);
        }
    }

    public function getPriceSubpartArrays(
        $templateCode,
        $row,
        $fieldname,
        $bTaxIncluded,
        $bEnableTaxZero,
        $priceFormulaArray,
        &$subpartArray,
        &$wrappedSubpartArray,
        &$tagArray,
        $theCode = '',
        $basketExtra = [],
        $basketRecs = [],
        $id = '1'
    ): void {
        $templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        $subpartmarkerObj = GeneralUtility::makeInstance('tx_ttproducts_subpartmarker');
        $local_cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);

        $t = [];
        $t['listFrameWork'] = $templateService->getSubpart($templateCode, '###GRADPRICE_FORMULA_ITEMS###');
        $t['itemFrameWork'] = $templateService->getSubpart($t['listFrameWork'], '###ITEM_FORMULA###');

        if (is_array($priceFormulaArray) && count($priceFormulaArray)) {
            $content = '';
            foreach ($priceFormulaArray as $k => $priceFormula) {
                if (isset($priceFormula) && is_array($priceFormula)) {
                    $itemMarkerArray = [];
                    $this->getFormulaMarkerArray(
                        $basketExtra,
                        $basketRecs,
                        $row,
                        $priceFormula,
                        $bTaxIncluded,
                        $bEnableTaxZero,
                        $itemMarkerArray
                    );

                    $formulaContent = $templateService->substituteMarkerArray($t['itemFrameWork'], $itemMarkerArray);
                    $content .= $templateService->substituteSubpart($t['listFrameWork'], '###ITEM_FORMULA###', $formulaContent);
                }
            }
            $subpartArray['###GRADPRICE_FORMULA_ITEMS###'] = $content;
        } else {
            $subpartArray['###GRADPRICE_FORMULA_ITEMS###'] = '';
        }

        //  ###GRADPRICE_PRICE_TAX###. ###GRADPRICE_PRICE_NO_TAX### ###GRADPRICE_PRICE_ONLY_TAX###
        //  ###GRADPRICE_FORMULA1_PRICE_NO_TAX###  ###GRADPRICE_FORMULA1_PRICE_TAX###
    }

    /**
     * Template marker substitution
     * Fills in the markerArray with data for a product.
     *
     * @param	array		reference to an item array with all the data of the item
     * @param	string		title of the category
     * @param	int		number of images to be shown
     * @param	object		the image cObj to be used
     * @param	array		information about the parent HTML form
     *
     * @return	array
     *
     * @access private
     */
    public function getPriceMarkerArray(
        $row,
        $bTaxIncluded,
        $bEnableTaxZero,
        $basketExtra,
        $basketRecs,
        &$markerArray,
        $tagArray
    ): void {
        if ($this->getModelObj()->hasDiscountPrice($row)) {
            $priceFormulaArray = $this->getModelObj()->getFormulasByItem($row['uid']);
            foreach ($priceFormulaArray as $k => $priceFormula) {
                if (isset($priceFormula) && is_array($priceFormula)) {
                    $this->getFormulaMarkerArray(
                        $basketExtra,
                        $basketRecs,
                        $row,
                        $priceFormula,
                        $bTaxIncluded,
                        $bEnableTaxZero,
                        $markerArray,
                        $k + 1
                    );
                }
            }
        }

        $marker = $this->getMarker();

        // empty all fields with no available entry
        foreach ($tagArray as $value => $k1) {
            $keyMarker = '###' . $value . '###';
            if (
                strpos($value, $marker . '_') &&
                !$markerArray[$keyMarker] &&
                $value != 'GRADPRICE_FORMULA_ITEMS'
            ) {
                $markerArray[$keyMarker] = '';
            }
        }
    }
}
