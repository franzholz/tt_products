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
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

 
use TYPO3\CMS\Core\Utility\GeneralUtility;



class tx_ttproducts_control_product {

	/**
	 */
	static public function getPresetVariantArray (
		$itemTable,
		$row,
		$useArticles
	) {
		$uid = $row['uid'];
		$functablename = $itemTable->getFuncTablename();;
		$basketVar = tx_ttproducts_model_control::getBasketVar();
		$presetVariantArray = [];
		$basketArray = GeneralUtility::_GP($basketVar);

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
			isset($storedRecs[$functablename]) &&
			is_array($storedRecs[$functablename]) &&
			isset($storedRecs[$functablename][$uid])
		) {
			$variantRow = $storedRecs[$functablename][$uid];
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


	static public function getActiveArticleNo () {
		$result = tx_ttproducts_model_control::getPiVarValue('tt_products_articles');
		return $result;
	}


	static public function addAjax (
		$tablesObj,
		$languageObj,
		$theCode,
		$functablename
	) {
		if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('taxajax')) {

			$itemTable = $tablesObj->get($functablename, false);

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

			$param = [$functablename => $fieldArray];
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
}

