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
 * graduated price view functions
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;

class tx_ttproducts_field_graduated_price_view extends tx_ttproducts_field_base_view
{
    public function getItemSubpartArrays(
        $templateCode,
        $markerKey,
        $funcTablename,
        $row,
        $fieldname,
        $tableConf,
        &$subpartArray,
        &$wrappedSubpartArray,
        $tagArray,
        $theCode = '',
        $basketExtra = [],
        $basketRecs = [],
        $id = '1'
    ): void {
        $bTaxIncluded = $this->conf['TAXincluded'];
        $bEnableTaxZero = 0;
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $viewItemTableObj = $tablesObj->get($funcTablename, true);
        $priceFormulaArray = $viewItemTableObj->getModelObj()->getGraduatedPriceObject()->getFormulasByItem($row['uid']);

        $priceTablesViewObj = $viewItemTableObj->getGraduatedPriceObject();
        $priceTablesViewObj->getPriceSubpartArrays(
            $templateCode,
            $row,
            $fieldname,
            $bTaxIncluded,
            $bEnableTaxZero,
            $priceFormulaArray,
            $subpartArray,
            $wrappedSubpartArray,
            $tagArray,
            $theCode,
            $basketExtra,
            $basketRecs,
            $id
        );
    }

    public function getRowMarkerArray(
        $funcTablename,
        $fieldname,
        $row,
        $markerKey,
        &$markerArray,
        $fieldMarkerArray,
        $tagArray,
        $theCode,
        $id,
        $basketExtra,
        $basketRecs,
        &$bSkip,
        $bHtml = true,
        $charset = '',
        $prefix = '',
        $suffix = '',
        $imageNum = 0,
        $imageRenderObj = '',
        $linkWrap = false,
        $bEnableTaxZero = false
    ): void {
        $bTaxIncluded = $this->conf['TAXincluded'];
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $viewItemTableObj = $tablesObj->get($funcTablename, true);

        $priceTablesViewObj = $viewItemTableObj->getGraduatedPriceObject();
        $priceTablesViewObj->getPriceMarkerArray(
            $row,
            $bTaxIncluded,
            $bEnableTaxZero,
            $basketExtra,
            $basketRecs,
            $markerArray,
            $tagArray
        );
    }
}
