<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Franz Holzinger (franz@ttproducts.de)
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
 * basket discount price calculation functions
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


use TYPO3\CMS\Core\Utility\GeneralUtility;



class tx_ttproducts_discountprice extends tx_ttproducts_pricecalc_base {
	public $conf;

	protected function handlePriceItems (
		array $priceItems,
		&$itemArray,
		&$priceReduction,
		&$discountArray,
		$type,
		$bNeedsDivision
	)	{
		$priceItemsCount = count($priceItems);

		foreach ($priceItems as $priceItem)	{
			$k2 = $priceItem['item'];
			$sort = $priceItem['sort'];
			$prodValue = $priceItem['price'];
			if ($bNeedsDivision) {
				$price = $prodValue / $priceItemsCount;
			} else {
				$price = $prodValue;
			}
			$itemArray[$sort][$k2][$type] = $price;
			$row = $itemArray[$sort][$k2]['rec'];
			$discountArray[$row['uid']] += $prodValue;
			$priceReduction[$row['uid']] = 1; // remember the reduction in order not to calculate another price with $priceCalc later
		}
	}


	public function getCalculatedData (
		&$itemArray,
		&$conf,
		$type,
		&$priceReduction,
		&$discountArray,
		$priceTotalTax,
		$bUseArticles,
		$bMergeArticles=true
	) {
		if (!$conf || !$itemArray || !count($itemArray)) {
			return;
		}
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$this->conf = $cnf->conf;

		$additive = 0;
		$countTotal = 0;
		$countedItems = array();
		$newPriceItems = array();

		ksort($conf);
		$calctype = '';
		$field = '';

		foreach ($conf as $k1 => $priceCalcTemp) {

			if (is_array($priceCalcTemp))	{

				$field = 'price';
				foreach ($priceCalcTemp as $k2 => $v2) {
					//=>	catch the values of discountprice
					if (!is_array($k2)) {
						switch ($k2) {
							case 'uid':
								$uid = $v2;
								break;
							case 'type':
								$calctype = $v2;
								break;
							case 'field':
								$field = $v2;
								break;
							case 'additive':
								$additive = $v2;
								break;
						}
						continue;
					}
				}
				if (isset($priceCalcTemp['prod.']) && is_array($priceCalcTemp['prod.']))	{
					ksort($priceCalcTemp['prod.'], SORT_NUMERIC);
				}
			} else {
				switch ($k1) {
					case 'additive':
						$additive = $priceCalcTemp;
						break;
				}
			}

			// array of all normal prices out of the discount price array
			$priceCalcCount = 0;

			if ($calctype == 'count')	{
				$pricefor1 = $this->getPrice($conf, $k1);
			}

			// loop over all items in the basket indexed by a sorting text
			foreach ($itemArray as $sort => $actItemArray) {

				// $actItemArray = all items array
				foreach ($actItemArray as $k2 => $actItem) {
					$bConditionActive = false;
					$lastprodValue = '';
					$prodValue = 0;

					$row = $actItem['rec'];

					if ($bMergeArticles)	{
						$extArray = $row['ext'];
						if (is_array($extArray) && is_array($extArray['mergeArticles']))	{
							$row = $extArray['mergeArticles'];
						}
					}

					if (is_array($priceCalcTemp['sql.']))	{
						if (!($bIsValid = tx_ttproducts_sql::isValid($row, $priceCalcTemp['sql.']['where'])))	{
							continue;
						}
					}

					$pid = intval($row['pid']);
					// count all items which will apply to the discount price
					$count2 = $actItem['count'];

					$prodConf = $priceCalcTemp['prod.'];

					switch ($calctype)	{
						case 'count':
							// amount of items
							$priceCalcCount += $count2;

							if (is_array($priceCalcTemp['sql.']) && $priceCalcTemp['sql.']['where'] != '')	{
								$prodType = '';

								if (is_array($prodConf))	{
									$prodType = $prodConf['type'];
									if (!$prodType) {
										$prodType = 'count';
									}
									$bActivateImmediately = true;
									if ($prodType == 'count') {
										$bActivateImmediately = false;
									}

									$prodConfArray = $prodConf;
									krsort($prodConfArray);
									$countedItems[$k1][] = array('sort' => $sort, 'item' => $k2, 'active' => false, 'price' => '');	// collect the not yet active items

									foreach ($prodConfArray as $k3 => $v3)	{
										if ($k3 === 'type')	{
											// nothing
										} else if (
											tx_div2007_core::testInt($k3)
										) {
											$count3 = intval($k3);

											if ($priceCalcCount >= $count3)	{

												switch ($prodType) 	{
													case 'percent':

														foreach ($countedItems[$k1] as $k4 => $countedItemsRow)	{
															$item = $itemArray[$countedItemsRow['sort']][$countedItemsRow['item']];
															$prodRow = $item['rec'];
															$prodValue = $prodRow[$field] * (1 - $v3/100);

															if (!isset($countedItems[$k1][$k4]) || !$countedItems[$k1][$k4]['active']) {
																$countedItems[$k1][$k4]['active'] = true;
																$countedItems[$k1][$k4]['price'] = $prodValue;
															}
														}
														break;
													case 'price':
													default:
														$prodValue = $v3;
														if (
															!tx_div2007_core::testInt($lastprodValue) ||
															$lastprodValue != $prodValue
														) {
															if (!$bConditionActive)	{
																foreach ($countedItems[$k1] as $k4 => $countItemArray)	{
																	$countedItems[$k1][$k4]['active'] = $bActivateImmediately;
																	$countedItems[$k1][$k4]['price'] = $prodValue;
																}
															}
															$bConditionActive = true;
														}
														$lastprodValue = $prodValue;
														break;
												}
											}
										}
									} // foreach ($prodConfArray as $k3 => $v3)
								}
							} else if (($count2 > 0) && isset($row[$field]) && ($row[$field] == $pricefor1) && (!$uid || $row['uid'] == $uid)) {
								$countedItems[$k1][] = array('sort' => $sort, 'item' => $k2, 'active' => false, 'price' => '');
							}
						break;
						case 'price':
							if (is_array($prodConf))	{
								$prodType = '';
								ksort($prodConf);
								$prodValue = 0;

								foreach($prodConf as $k3 => $prodv)	{

									if (
										tx_div2007_core::testInt($k3)
									) {
										if ($priceTotalTax >= $k3 - 0.000001)	{

											$prodValue = $prodv;
										}
									} else {
										if ($k3 === 'type')	{
											$prodType = $prodv;
										}
									}
								}

								if ($prodType == 'percent') {
									$prodValue = ($this->conf['TAXincluded'] ? $actItem['priceTax'] : $actItem['priceNoTax']) * (1 - $prodValue / 100);
								}
								$newPriceItems[$k1][] = array('sort' => $sort, 'item' => $k2, 'price' => $prodValue);
							}
						break;
					}
					//=> actItem uid = catched uid
				}
			} // foreach ($itemArray as $sort=>$actItemArray)
			$countTotal += $priceCalcCount;

			if ($additive == 0) {

				switch ($calctype)	{
					case 'count':

						if (is_array($countedItems[$k1]))	{
							$additionalCount = 0;
							$activateArray = array();

							foreach ($countedItems[$k1] as $k2 => $countedItemsRow)	{
								if ($countedItemsRow['active'] === false) {
									$tmpArray = $itemArray[$countedItemsRow['sort']][$countedItemsRow['item']];
									$additionalCount += $tmpArray['count'];
									$activateArray[] = $k2;
								}
							}

							if ($additionalCount > 0)	{
								$prodType = $prodConf['type'];
								$prodConfArray = $prodConf;
								krsort($prodConfArray);

								foreach ($prodConfArray as $k2 => $prodv)	{
									if ($additionalCount >= $k2)	{
										$activatePrice = $prodv / count($activateArray);

										foreach ($activateArray as $k3)	{
											$countedItems[$k1][$k3]['active'] = true;
											$countedItems[$k1][$k3]['price'] = $activatePrice;
										}
										break;
									}
								}
							}

							foreach ($countedItems[$k1] as $k2 => $countedItemsRow)	{

								if ($countedItemsRow['active'] === true)	{

									$item = &$itemArray[$countedItemsRow['sort']][$countedItemsRow['item']];
									$row2 = &$item['rec'];
									$row2[$type] = $countedItemsRow['price']; // direct write into $row and into $itemArray
									$item[$type] = $countedItemsRow['price'];

									$discountArray[$row['uid']] += $countedItemsRow['price'];
									$priceReduction[$row['uid']] = 1; // remember the reduction in order not to calculate another price with $priceCalc
								}
							}

							if (isset($item)) {
								unset($item);
							}
							if (isset($row2)) {
								unset($row2);
							}
						}
					break;
					case 'price':
						if (isset($newPriceItems[$k1]) && is_array($newPriceItems[$k1]))	{
							$this->handlePriceItems(
								$newPriceItems[$k1],
								$itemArray,
								$priceReduction,
								$discountArray,
								$type,
								false
							);
						}
					break;
				}
			}
		} // foreach ($conf as $k1 => $priceCalcTemp)

		if ($additive == 1) {

			switch ($calctype)	{
				case 'count':
					foreach ($conf as $k1 => $priceCalcTemp) {

						if (!is_array($priceCalcTemp)) {
							continue;
						}

						if ($countedItems[$k1] == NULL) {
							continue;
						}

						krsort($priceCalcTemp['prod.']);
						foreach ($priceCalcTemp['prod.'] as $k2 => $price2)	{
							if ($countTotal >= (float) $k2) { // search the price from the total count
								if ((float) $k2 > 1) {
									// store the discount price in all calculated items from before
									if (is_array ($countedItems[$k1]))	{
										foreach ($countedItems[$k1] as $k3 => $v3) {
											if ($v3['active'] == false) {
												continue;
											}

											foreach ($itemArray[$v3['sort']] as $k1=>$actItem) {
												$row = $actItem['rec'];
												if ($type == 'calc')	{
													$itemArray[$v3['sort']][$k1][$type] = $price2;
												}
												$discountArray[$row['uid']] += $price2;
												$priceReduction[$row['uid']] = 1; // remember the reduction in order not to calculate another price with $priceCalc later
											}
										}
									}
								}
								break; // finish
							}
						}
					}
				break;
				case 'price':
					foreach ($conf as $k1 => $priceCalcTemp) {
						if (!is_array($priceCalcTemp)) {
							continue;
						}

						if (isset($newPriceItems[$k1]) && is_array($newPriceItems[$k1]))	{
							$this->handlePriceItems(
								$newPriceItems[$k1],
								$itemArray,
								$priceReduction,
								$discountArray,
								$type,
								true
							);
						}
					}
				break;
			}
		} else	{	// nothing
		}

	} // getCalculatedData
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_discountprice.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_discountprice.php']);
}


