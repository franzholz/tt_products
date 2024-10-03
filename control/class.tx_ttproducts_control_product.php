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
 * control functions for a product item object
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 *
 */
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use JambageCom\TtProducts\Api\ParameterApi;
use JambageCom\TtProducts\Api\VariantApi;
use JambageCom\TtProducts\Api\EditVariantApi;

class tx_ttproducts_control_product
{
    public static function getPresetVariantArray(
        $itemTable,
        $row,
        $useArticles
    ) {
        $parameterApi = GeneralUtility::makeInstance(ParameterApi::class);
        $uid = $row['uid'];
        $funcTablename = $itemTable->getFuncTablename();

        $basketVar = $parameterApi->getBasketVar();
        $presetVariantArray = [];
        $basketArray = $parameterApi->getParameter($basketVar);

        if (
            isset($basketArray) && is_array($basketArray) &&
            isset($basketArray[$uid]) && is_array($basketArray[$uid]) &&
            isset($_POST[$basketVar]) && is_array($_POST[$basketVar]) &&
            isset($_POST[$basketVar][$uid])
        ) {
            $presetVariantArray = $_POST[$basketVar][$uid];
        }

        $storedRecs = tx_ttproducts_control_basket::getStoredVariantRecs();
        if (
            isset($storedRecs) &&
            is_array($storedRecs) &&
            isset($storedRecs[$funcTablename]) &&
            is_array($storedRecs[$funcTablename]) &&
            isset($storedRecs[$funcTablename][$uid])
        ) {
            $variantRow = $storedRecs[$funcTablename][$uid];
            $variant =
                $itemTable->variant->getVariantFromProductRow(
                    $row,
                    $variantRow,
                    $useArticles,
                    false
                );

            $presetVariantArray = $variant;
        }

        return $presetVariantArray;
    } // getPresetVariantArray

    public static function getActiveArticleNo()
    {
        $parameterApi = GeneralUtility::makeInstance(ParameterApi::class);
        $result = $parameterApi->getPiVarValue('tt_products_articles');

        return $result;
    }

    public static function addAjax(
        $tablesObj,
        $languageObj,
        $theCode,
        $funcTablename
    ): void {
        if (ExtensionManagementUtility::isLoaded('taxajax')) {
            $itemTable = $tablesObj->get($funcTablename, false);

            $selectableVariantFieldArray = $itemTable->variant->getSelectableFieldArray();
            $editFieldArray = $itemTable->editVariant->getFieldArray();
            $fieldArray = [];

            if (
                isset($selectableVariantFieldArray) &&
                is_array($selectableVariantFieldArray)
            ) {
                $fieldArray = $selectableVariantFieldArray;
            }

            if (isset($editFieldArray) && is_array($editFieldArray)) {
                $fieldArray = array_merge($fieldArray, $editFieldArray);
            }

            $param = [$funcTablename => $fieldArray];
            $bUseColorbox = false;
            $tableConf = $itemTable->getTableConf($theCode);
            if (
                is_array($tableConf) &&
                isset($tableConf['jquery.']) &&
                isset($tableConf['jquery.']['colorbox']) &&
                $tableConf['jquery.']['colorbox']
            ) {
                $bUseColorbox = true;
            }

            $javaScriptObj = GeneralUtility::makeInstance('tx_ttproducts_javascript');
            $javaScriptObj->set(
                $languageObj,
                'fetchdata',
                $param
            );

            if (
                $bUseColorbox
            ) {
                $javaScriptObj->set($languageObj, 'colorbox');
            }
        }
    }

    public static function getAllVariantFields()
    {
        $variantApi = GeneralUtility::makeInstance(VariantApi::class);
        $editVariantApi = GeneralUtility::makeInstance(EditVariantApi::class);
        $funcTablename = 'tt_products';
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $itemTable = $tablesObj->get($funcTablename, false);
        $selectableVariantFieldArray = $variantApi->getSelectableFieldArray();
        $editFieldArray = $editVariantApi->getFieldArray();
        $fieldArray = [];

        if (
            isset($selectableVariantFieldArray) &&
            is_array($selectableVariantFieldArray)
        ) {
            $fieldArray = $selectableVariantFieldArray;
        }

        if (isset($editFieldArray) && is_array($editFieldArray)) {
            $fieldArray = array_merge($fieldArray, $editFieldArray);
        }
        return $fieldArray;
    }

}
