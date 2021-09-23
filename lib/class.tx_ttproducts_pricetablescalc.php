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
 * basket price calculation functions using the price tables
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;


class tx_ttproducts_pricetablescalc extends tx_ttproducts_pricecalc_base {



	public function calculateValue ($formula, $row) {
		$result = false;
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
		$conf = $cnf->getConf();
		$tagArray = $markerObj->getAllMarkers($formula);
		$markerArray = array();

		if (isset($conf['graduate.']) && is_array($conf['graduate.'])) {
			$bIsValid = false;

			foreach ($conf['graduate.'] as $graduateConf) {
				$bIsValid = true;
				if (isset($graduateConf['sql.']) && is_array($graduateConf['sql.'])) {
					$bIsValid = tx_ttproducts_sql::isValid($row, $graduateConf['sql.']['where']);
				}

				if ($bIsValid) {
					if (isset($graduateConf['marks.']) && is_array($graduateConf['marks.'])) {
						foreach ($graduateConf['marks.'] as $tag => $value) {
							$marker = '###' . strtoupper($tag) . '###';
							$markerArray[$marker] = $value;
						}
					}
				}
			}
		}

		if (isset($tagArray) && is_array($tagArray)) {
			foreach ($tagArray as $tag => $value) {
				$marker = '###' . $tag . '###';
				if (!isset($markerArray[$marker])) {
					$markerArray[$marker] = '0';
				}
			}
		}

		$formula = tx_div2007_core::substituteMarkerArrayCached($formula, $markerArray);
		$formula = trim($formula);
		$len = strlen($formula);
		$lastChar = substr($formula, -1, 1);

		if (
			$lastChar != '' &&
			(
				!MathUtility::canBeInterpretedAsInteger($lastChar)
			)
		) {
			$formula = substr($formula, 0, strlen($formula) - 1);

			switch ($lastChar) {
				case '%':
					if ($formula > 100) {
						$formula = 100;
					}
					$priceProduct = $priceProduct * (1 - $formula/100);
					$result = $priceProduct;
					break;
			}
		} else {
			$phpCode = '$result = ' . $formula . ';';

			try {	// take care of divisions by zero
				if (
					!\JambageCom\Div2007\Utility\PhpUtility::php_is_secure($phpCode)
				) {
					throw new RuntimeException('Error in tt_products: The graduated price for "' . htmlspecialchars($formula) . '" is unsecure.', 50003);
				}

				$syntaxCheck =
					\JambageCom\Div2007\Utility\PhpUtility::php_syntax_error($phpCode);
				if (
					is_array($syntaxCheck)
				) {
					throw new RuntimeException('Error in tt_products: The syntax check for "' . htmlspecialchars($formula) . '" went wrong.', 50004);
				} else {
					eval($phpCode);
// 					eval("\$result = " . $formula . ";" );
				}
			}
			catch(RuntimeException $e) {
				debug ($e, 'calculateValue $e'); // keep this
			}
			catch(Exception $e) {
				debug ($e, 'calculateValue $e'); // keep this
				throw new RuntimeException('Error in tt_products: Devision by zero error with "' . htmlspecialchars($formula) . '" .', 50005);
			}
		}

		if ($row['graduated_price_round'] != '') {

			$result = tx_ttproducts_api::roundPrice($result, $row['graduated_price_round']);
		}

		return $result;
	}


	public function getDiscountPrice ($graduatedPriceObj, $row, $priceProduct, $count) {

		$result = false;
		$uid = $row['uid'];
		$priceFormulaArray =
			$graduatedPriceObj->getFormulasByItem($uid);

		if (
			isset($priceFormulaArray) &&
			is_array($priceFormulaArray) &&
			count($priceFormulaArray)
		) {
			$maxStartAmount = '0';
			$thePriceFormula = false;

			foreach ($priceFormulaArray as $k => $priceFormula) {
				if ($count >= floatval($priceFormula['startamount'])) {
					if (
						floatval($priceFormula['startamount']) > $maxStartAmount
					) {
						$maxStartAmount = floatval($priceFormula['startamount']);
						$thePriceFormula = $priceFormula;
					}
				}
			}

			if ($thePriceFormula) {
				$calculatedValue =
					$this->calculateValue(
						$thePriceFormula['formula'],
						$row
					);

				$result = $calculatedValue;

				// Todo: if ($priceProduct > $calculatedValue)
			}
		}

		return $result;
	}

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
		if (!$itemArray || !count($itemArray)) {
			return false;
		}

		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
		$useArticles = $cnf->getUseArticles();
		$productTable = $tablesObj->get('tt_products', false);
		$graduatedPriceObj = $productTable->getGraduatedPriceObject();

		if (
			$bUseArticles &&
			(
				$useArticles == 1 ||
				$useArticles == 3
			)
		) {
			$articleTable = $tablesObj->get('tt_products_articles', false);
		}

		$prodArray = array();
		// loop over all items in the basket indexed by sort string
		foreach ($itemArray as $sort => $actItemArray) {

			foreach ($actItemArray as $k2 => $actItem) {
				$row = $actItem['rec'];
				$actItem['sort'] = $sort;
				$actItem['k2'] = $k2;
				$prodArray[$row['uid']][] = $actItem;
			}
		}

		// loop over all items in the basket indexed by product uid
		foreach ($prodArray as $uid => $actItemArray) {
			$row1 = $actItemArray['0']['rec'];

			if ($graduatedPriceObj->hasDiscountPrice($row1)) {
				$count = 0;
				if ($taxIncluded) {
					$priceProduct = $row1['pricetax'];
				} else {
					$priceProduct = $row1['pricenotax'];
				}
				foreach($actItemArray as $actItem) {
					$count += floatval($actItem['count']);
				}
				$newPriceProduct =
					$this->getDiscountPrice(
						$graduatedPriceObj,
						$row1,
						$priceProduct,
						$count
					);

				if ($newPriceProduct !== false) {
					$priceProduct = $newPriceProduct;

					foreach($actItemArray as $actItem) {

						$row = $actItem['rec'];
						$count = floatval($actItem['count']);
						$sort = $actItem['sort'];
						$k2 = $actItem['k2'];
						$actPrice = $priceProduct;

						if (isset($articleTable) && is_object($articleTable)) {
							$extArray = $row['ext'];

							if (
								isset($extArray['tt_products_articles']) && is_array($extArray['tt_products_articles'])
							) {
								$articleUid = $extArray['tt_products_articles']['0']['uid'];

								if (
									MathUtility::canBeInterpretedAsInteger($articleUid)
								) {
									$articleRow = $articleTable->get($articleUid);
									$bIsAddedPrice = $cnf->hasConfig($articleRow, 'isAddedPrice');

									if ($bIsAddedPrice) {
										$actPrice = $priceProduct + $articleRow['price'];
									}
								}
							}
						}

						$itemArray[$sort][$k2]['rec'][$type] = $itemArray[$sort][$k2][$type] = $actPrice;
					}
					$priceReduction[$uid] = 1;
				}
			}
		}

	} // getCalculatedData
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_pricetablescalc.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_pricetablescalc.php']);
}

