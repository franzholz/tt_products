<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Kasper Skårhøj <kasperYYYY@typo3.com>
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
 * basket functions for a basket object
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author	René Fritz <r.fritz@colorcube.de>
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @author	Klaus Zierer <zierer@pz-systeme.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;


class tx_ttproducts_basket implements \TYPO3\CMS\Core\SingletonInterface {
	public $conf;
	public $config;

		// Internal: initBasket():
	public $basket=array();			// initBasket() sets this array based on the registered items
	public $recs = array(); 		// in initBasket this is set to the recs-array of fe_user.
	public $basketExt=array();		// "Basket Extension" - holds extended attributes
	public $order = array(); 		// order data
	public $giftnumber;			// current counter of the gifts

	public $itemArray = array();		// the items in the basket; database row, how many (quantity, count) and the price; this has replaced the former $calculatedBasket

	public $funcTablename;			// tt_products or tt_products_articles
	protected $pidListObj;
	public $formulaArray;
	public $giftServiceRow;
	protected $itemObj;
	protected $maxTax;
	protected $categoryQuantity = array();
	protected $categoryArray = array();

	public function init (
		$pibaseClass,
		$updateMode,
		$pid_list,
		$bStoreBasket
	)
	{
		$formerBasket = tx_ttproducts_control_basket::getRecs();
		$pibaseObj = GeneralUtility::makeInstance('' . $pibaseClass);
		$cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
		$this->conf = &$cnfObj->conf;
		$this->config = &$cnfObj->config;
		$this->recs = $formerBasket;	// Sets it internally
		$this->itemObj = GeneralUtility::makeInstance('tx_ttproducts_basketitem');

		if (isset($pibaseObj->piVars) && is_array($pibaseObj->piVars) && isset($pibaseObj->piVars['type']) && is_array($pibaseObj->piVars['type']))	{
			$typeArray = $pibaseObj->piVars['type'];
		}

        // neu Anfang
        $gpVars = GeneralUtility::_GP(TT_PRODUCTS_EXT);
        $payment =
            GeneralUtility::_POST('products_payment') ||
            GeneralUtility::_POST('products_payment_x') ||
            isset($gpVars['activity']) &&
            is_array($gpVars['activity']) &&
            $gpVars['activity']['payment'];

        if (    // use AGB checkbox if coming from INFO
            $payment &&
            isset($_REQUEST['recs']) &&
            is_array($_REQUEST['recs']) &&
            isset($_REQUEST['recs']['personinfo']) &&
            is_array($_REQUEST['recs']['personinfo'])
        ) {
            $bAgbSet = $this->recs['personinfo']['agb'];
            $this->recs['personinfo']['agb'] = (boolean) $_REQUEST['recs']['personinfo']['agb'];
            if ($bAgbSet != $this->recs['personinfo']['agb'])   {
                tx_ttproducts_control_session::writeSession('recs', $this->recs); // store this change
            }
        }

		$this->basket = array();
		$this->itemArray = array();
		$paymentshippingObj = GeneralUtility::makeInstance('tx_ttproducts_paymentshipping');
		$this->pidListObj = GeneralUtility::makeInstance('tx_ttproducts_pid_list');
		$this->pidListObj->init($pibaseObj->cObj);
		$this->pidListObj->applyRecursive(99, $pid_list, true);
		$this->pidListObj->setPageArray();

		if ($cnfObj->getUseArticles() == 2)	{
			$funcTablename = 'tt_products_articles';
		} else {
			$funcTablename = 'tt_products';
		}

		$this->setFuncTablename($funcTablename);
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
		$viewTableObj = $tablesObj->get($funcTablename);
		$tmpBasketExt = $GLOBALS['TSFE']->fe_user->getKey('ses','basketExt');

		$order = $GLOBALS['TSFE']->fe_user->getKey('ses','order');
		$this->setOrder($order);
		$basketExtRaw = GeneralUtility::_GP('ttp_basket');
		$basketInputConf = &$cnfObj->getBasketConf('view','input');

		if (isset($basketInputConf) && is_array($basketInputConf))	{
			foreach ($basketInputConf as $lineNo => $inputConf)	{
				if (strpos($lineNo,'.') !== false && $inputConf['type'] == 'radio' && $inputConf['where'] && $inputConf['name'] != '')	{
					$radioUid = GeneralUtility::_GP($inputConf['name']);
					if ($radioUid)	{
						$rowArray = $viewTableObj->get('',0,false,$inputConf['where']);

						if (!empty($rowArray))	{
							foreach($rowArray as $uid => $row)	{
								if ($uid == $radioUid)	{
									$basketExtRaw[$uid]['quantity'] = 1;
								} else {
									unset($tmpBasketExt[$uid]);
								}
							}
						}
					}
				}
			}
		}

		if (is_array($tmpBasketExt)) {
			$this->basketExt = $tmpBasketExt;
		} else {
			$this->basketExt = array();
		}

        if (isset($this->basketExt['gift'])) {
            $this->giftnumber = count($this->basketExt['gift']) + 1;
        }
		$newGiftData = GeneralUtility::_GP('ttp_gift');
		$extVars = $pibaseObj->piVars['variants'];
		$extVars = ($extVars ? $extVars : GeneralUtility::_GP('ttp_extvars'));
		$paramProduct = strtolower($viewTableObj->marker);
		$uid = $pibaseObj->piVars[$paramProduct];
		$uid = ($uid ? $uid : GeneralUtility::_GP('tt_products'));
		$sameGiftData = true;
		$identGiftnumber = 0;

		$addMemo = $pibaseObj->piVars['addmemo'];
		if ($addMemo)	{
			$basketExtRaw = '';
			$newGiftData = '';
		}

			// Call all changeBasket hooks
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['changeBasket'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['changeBasket'] as $classRef) {
				$hookObj= GeneralUtility::makeInstance($classRef);
				if (method_exists($hookObj, 'changeBasket')) {
					$hookObj->changeBasket($this, $basketExtRaw, $extVars, $paramProduct, $uid, $sameGiftData, $identGiftnumber);
				}
			}
		}

		if ($newGiftData) {
			$giftnumber = GeneralUtility::_GP('giftnumber');
			if ($updateMode) {
				$this->basketExt['gift'][$giftnumber] = $newGiftData;
				$giftcount = intval($this->basketExt['gift'][$giftnumber]['item'][$uid][$extVars]);
				if ($giftcount == 0) {
					$this->removeGift($giftnumber, $uid, $extVars);
				}
				$count = 0;
				foreach ($this->basketExt['gift'] as $prevgiftnumber => $rec) {
					$count += $rec['item'][$uid][$extVars];
				}
				// update the general basket entry for this product
				$this->basketExt[$uid][$extVars] = $count;
			} else {
				if (is_array($this->basketExt['gift'])) {
					foreach ($this->basketExt['gift'] as $prevgiftnumber => $rec) {
						$sameGiftData = true;
						foreach ($rec as $field => $value) {
							// only the 'field' field can be different
							if ($field != 'item' && $field != 'note' && $value != $newGiftData[$field]) {
								$sameGiftData = false;
								break;
							}
						}
						if ($sameGiftData) {
							$identGiftnumber = $prevgiftnumber;
							// always use the latest note
							$this->basketExt['gift'][$identGiftnumber]['note'] = $newGiftData['note'];
							break;
						}
					}
				} else {
					$sameGiftData = false;
				}
				if (!$sameGiftData) {
					$this->basketExt['gift'][$this->giftnumber] = $newGiftData;
				}
			}
		}

		if (is_array($basketExtRaw)) {
            $damUid = 0;
			foreach ($basketExtRaw as $uid => $basketItem)	{
				if (
					tx_div2007_core::testInt($uid)
				) {
					if (isset($typeArray) && is_array($typeArray) && $typeArray[0] == 'product' && $typeArray[1] != '')	{

						foreach ($basketItem as $damUid => $damBasketItem)	{
							$this->addItem($viewTableObj, $uid, $damUid, $damBasketItem, $updateMode, $bStoreBasket,$newGiftData,$identGiftnumber,$sameGiftData);
						}
					} else {
						$this->addItem($viewTableObj, $uid, $damUid, $basketItem, $updateMode, $bStoreBasket,$newGiftData,$identGiftnumber,$sameGiftData);
					}
				}
			}
			// I did not find another possibility to delete elements completely from a multidimensional array
			// than to recreate the array
			$basketExtNew = array();
			foreach($this->basketExt as $tmpUid => $tmpSubArr) {
				if (is_array($tmpSubArr) && count($tmpSubArr))	{
					foreach($tmpSubArr as $tmpExtVar => $tmpCount) {

						if (
							$tmpCount > 0 &&
							(
								$this->conf['quantityIsFloat'] ||
								tx_div2007_core::testInt($tmpCount)
							)
						) {
							$basketExtNew[$tmpUid][$tmpExtVar] = $this->basketExt[$tmpUid][$tmpExtVar];
							if (isset($this->basketExt[$tmpUid][$tmpExtVar.'.']) && is_array($this->basketExt[$tmpUid][$tmpExtVar.'.']))	{
								$basketExtNew[$tmpUid][$tmpExtVar.'.'] = $this->basketExt[$tmpUid][$tmpExtVar.'.'];
							}
						} else if (is_array($tmpCount))	{
							$basketExtNew[$tmpUid][$tmpExtVar] = $tmpCount;
						} else {
							// nothing
						}
					}
				} else {
					$basketExtNew[$tmpUid] = $tmpSubArr;
				}
			}
			$this->basketExt = $basketExtNew;



			if ($bStoreBasket)	{

				if (
                    is_array($this->basketExt) &&
                    count($this->basketExt)
                ) {
					$GLOBALS['TSFE']->fe_user->setKey('ses','basketExt',$this->basketExt);
				} else {
					$GLOBALS['TSFE']->fe_user->setKey('ses','basketExt',array());
				}
				$GLOBALS['TSFE']->fe_user->storeSessionData(); // The basket shall not get lost when coming back from external scripts
			}
		}
	} // init


	public function setOrder ($order)
	{
		$this->order = $order;
	}


	public function setCategoryQuantity ($categoryQuantity)
	{
		$this->categoryQuantity = $categoryQuantity;
	}


	public function getCategoryQuantity ()
	{
		return $this->categoryQuantity;
	}


	public function setCategoryArray ($categoryArray)
	{
		$this->categoryArray = $categoryArray;
	}


	public function getCategoryArray ()
	{
		return $this->categoryArray;
	}


	public function getRadioInputArray (
		$row
	)
	{
		$cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
		$basketConf = $cnfObj->getBasketConf('view', 'input');
		$result = false;
		if (!empty($basketConf)) {
			foreach ($basketConf as $lineNo => $inputConf) {
				if (strpos($lineNo, '.') !== false)	{
					$bIsValid = tx_ttproducts_sql::isValid($row, $inputConf['where']);
					if ($bIsValid && $inputConf['type'] == 'radio') {
						$result = $inputConf;
					}
				}
			}
		}
		return $result;
	}


	public function getItemObj ()
	{
		return $this->itemObj;
	}


	public function setItemArray ($itemArray)
	{
		$this->itemArray = $itemArray;
	}

	public function getItemArray ()
	{
		return $this->itemArray;
	}


	public function addItem ($viewTableObj, $uid, $damUid, $item, $updateMode, $bStoreBasket, $newGiftData = '', $identGiftnumber = 0, $sameGiftData = false)
	{
		if ($this->conf['errorLog'] && isset($item['quantity']) && $item['quantity'] != '') {
			error_log('addItem $item = ' . print_r($item, true) . chr(13), 3, $this->conf['errorLog']);
		}
		$priceObj = GeneralUtility::makeInstance('tx_ttproducts_field_price');

		// quantities for single values are stored in an array. This is necessary because a HTML checkbox does not send any values if it has been unchecked
		if (isset($item['quantity']) && is_array($item['quantity']))	{
			reset($item['quantity']);
			$item['quantity'] = current($item['quantity']);
		}

		if (!$updateMode) {
			if (!isset($item['quantity']))	{
				return;
			}
			$variant = $viewTableObj->variant->getVariantFromRawRow($item);
			$oldcount = $this->basketExt[$uid][$variant];
			$quantity = 0;
			$quantity = $priceObj->toNumber($this->conf['quantityIsFloat'],$item['quantity']);
			$count = $this->getMaxCount($quantity, $uid);

			if ($count >= 0 && $bStoreBasket) {
				$newcount = $count;
				if ($newGiftData) {
					$giftnumber = 0;
					if ($sameGiftData) {
						$giftnumber = $identGiftnumber;
						$oldcount -= $this->basketExt['gift'][$giftnumber]['item'][$uid][$variant];
					} else {
						$giftnumber = $this->giftnumber;
					}
					$newcount += $oldcount;
					$this->basketExt['gift'][$giftnumber]['item'][$uid][$variant] = $count;
					if ($count == 0) {
						$this->removeGift($giftnumber, $uid, $variant);
					}
				}

				if ($newcount)	{
					if ($this->conf['alwaysUpdateOrderAmount'] == 1)	{
						$this->basketExt[$uid][$variant] = $newcount;
					} else {
						$this->basketExt[$uid][$variant] = $oldcount + $newcount;
					}
				} else {
					unset($this->basketExt[$uid][$variant]);
				}
			}
		} else {
			foreach($item as $md5 => $quantity) {
				$quantity = $priceObj->toNumber($this->conf['quantityIsFloat'],$quantity);

				if (is_array($this->basketExt[$uid]) && $md5 != 'additional')	{
					foreach($this->basketExt[$uid] as $variant => $tmp) {
						$actMd5 = md5($variant);

							// useArticles if you have different prices and therefore articles for color, size, additional and gradings
						if ($actMd5==$md5) {
							$count = $this->getMaxCount($quantity, $uid);
							$this->basketExt[$uid][$variant] = $count;

							if (isset($item['additional']) && is_array($item['additional']) &&
							isset($item['additional'][$actMd5]['giftservice']) && is_array($item['additional'][$actMd5]['giftservice']))	{
								if (isset($this->basketExt[$uid][$variant.'.']) && !is_array($this->basketExt[$uid][$variant.'.']))	{
									$this->basketExt[$uid][$variant.'.'] = array();
								}
								if (isset($this->basketExt[$uid][$variant.'.']['additional']) && !is_array($this->basketExt[$uid][$variant.'.']['additional']))	{
									$this->basketExt[$uid][$variant.'.']['additional'] = array();
								}
								$bHasGiftService = $item['additional'][$actMd5]['giftservice']['1'];
								if ($bHasGiftService)	{
									$this->basketExt[$uid][$variant.'.']['additional']['giftservice'] = '1';
								} else {
									unset ($this->basketExt[$uid][$variant.'.']);
								}
							}

							if (is_array($this->basketExt['gift'])) {
								$count = count($this->basketExt['gift']);
								$giftCount = 0;
								$restQuantity = $quantity;
								for ($giftnumber = 1; $giftnumber <= $count; ++$giftnumber) {
									if ($restQuantity == 0) {
										$this->removeGift($giftnumber, $uid, $variant);
									} else {
										if ($this->basketExt['gift'][$giftnumber]['item'][$uid][$variant] > $restQuantity) {
											$this->basketExt['gift'][$giftnumber]['item'][$uid][$variant] = $restQuantity;
											$restQuantity = 0;
										} else if ($giftnumber < $count) {
											$restQuantity -= $this->basketExt['gift'][$giftnumber]['item'][$uid][$variant];
										} else {
											$this->basketExt['gift'][$giftnumber]['item'][$uid][$variant] = $restQuantity;
										}
									}
								}
							}
						}
					}
				}
			}
		}
	}


	public function getBasketExt ()
	{
		return $this->basketExt;
	}


	public function getPidListObj ()
	{
		return $this->pidListObj;
	}


	public function setFuncTablename ($funcTablename)
	{
		$this->funcTablename = $funcTablename;
	}


	public function getFuncTablename ()
	{
		return $this->funcTablename;
	}


	/**
	 * Removes a gift from the basket
	 *
	 * @param		int		 index of the gift
	 * @param 		int			uid of the product
	 * @param		string		variant of the product
	 * @return	  void
 	 */
	public function removeGift ($giftnumber, $uid, $variant)
	{
		if($this->basketExt['gift'][$giftnumber]['item'][$uid][$variant] >= 0) {
			unset($this->basketExt['gift'][$giftnumber]['item'][$uid][$variant]);
			if (!count($this->basketExt['gift'][$giftnumber]['item'][$uid])) {
				unset($this->basketExt['gift'][$giftnumber]['item'][$uid]);
			}
			if (!($this->basketExt['gift'][$giftnumber]['item'])) {
				unset($this->basketExt['gift'][$giftnumber]);
			}
		}
	}


	public function getMaxCount ($quantity, $uid=0)
	{
		$count = 0;

		if ($this->conf['basketMaxQuantity'] == 'inStock' && !$this->conf['alwaysInStock'] && !empty($uid)) {
			$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
			$viewTableObj = $tablesObj->get('tt_products');
			$row = $viewTableObj->get($uid);
			$count =
                \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange(
                    $quantity,
                    0,
                    $row['inStock'],
                    0
                );
		} elseif ($this->conf['basketMaxQuantity'] == 'creditpoint' && !empty($uid)) {
			$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
			$viewTableObj = $tablesObj->get('tt_products');
			$row = $viewTableObj->get($uid);

			$creditpointsObj = GeneralUtility::makeInstance('tx_ttproducts_field_creditpoints');
			$missingCreditpoints = 0;
			$creditpointsObj->getBasketMissingCreditpoints($row['creditpoints'] * $quantity, $missingCreditpoints, $tmp);

			if ($quantity > 1 && $missingCreditpoints > 0)	{
				$reduceQuantity = intval($missingCreditpoints / $row['creditpoints']);
				if ($missingCreditpoints > $reduceQuantity * $row['creditpoints'])	{
					$reduceQuantity += 1;
				}
				if ($quantity - $reduceQuantity >= 1)	{
					$count = $quantity - $reduceQuantity;
				} else {
					$count = 0;
				}
			} else {
				$count = ($missingCreditpoints > 0 ? 0 : $quantity);
			}
		} elseif ($this->conf['quantityIsFloat'])	{
			$count = (float) $quantity;
			if ($count < 0)	{
				$count = 0;
			}
			if ($count > $this->conf['basketMaxQuantity'])	{
				$count = $this->conf['basketMaxQuantity'];
			}
		} else {
			$count =
				
                \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange(
                    $quantity,
                    0,
                    $this->conf['basketMaxQuantity'],
                    0
                );
		}
		return $count;
	}


	/**
	 * Returns a clear 'recs[tt_products]' array - so clears the basket.
	 */
	public function getClearBasketRecord ()
	{
			// Returns a basket-record cleared of tt_product items
		unset($this->recs['tt_products']);
		unset($this->recs['personinfo']);
		unset($this->recs['delivery']);
		unset($this->recs['creditcard']);
		unset($this->recs['account']);
		return ($this->recs);
	} // getClearBasketRecord


	/**
	 * Empties the shopping basket!
	 */
	public function clearBasket ($bForce=false)
	{
		if ($this->conf['debug'] != '1' || $bForce)	{

			// TODO: delete only records from relevant pages
				// Empties the shopping basket!
			$basketRecord = $this->getClearBasketRecord();
			tx_ttproducts_control_basket::setRecs($basketRecord);
			tx_ttproducts_control_basket::store('recs', $basketRecord);
			tx_ttproducts_control_basket::store('basketExt',array());
			tx_ttproducts_control_basket::store('order',array());
			unset($this->itemArray);
			unset($this->basketExt);
			unset($this->order);
		}
		tx_ttproducts_control_basket::store('ac',array());
		tx_ttproducts_control_basket::store('cc',array());
		tx_ttproducts_control_basket::store('cp',array());
		tx_ttproducts_control_basket::store('vo',array());
	} // clearBasket


	public function isInBasket ($prod_uid)
	{
		$rc = false;
		if (!empty($this->itemArray))	{
			// loop over all items in the basket indexed by a sort string
			foreach ($this->itemArray as $sort => $actItemArray) {
				foreach ($actItemArray as $k1 => $actItem) {
					$row = &$actItem['rec'];
					if ($prod_uid == $row['uid'])	{
						$rc = true;
						break;
					}
				}
				if ($rc == true)	{
					break;
				}
			}
		}
		return $rc;
	}


	// get gradutated prices for all products in a list view or a single product in a single view
	public function getGraduatedPrices ($uid)
	{
		$graduatedPriceObj = GeneralUtility::makeInstance('tx_ttproducts_graduated_price');
		$this->formulaArray = $graduatedPriceObj->getFormulasByProduct($uid);
	}


	public function get ($uid, $variant)
	{
		$rc = array();
		foreach ($this->itemArray as $sort => $actItemArray) {
			foreach ($actItemArray as $k1 => $actItem) {
				$row = &$actItem['rec'];
				if (
					$row['uid'] == $uid &&
					isset($row['ext']) &&
					is_array($row['ext']) &&
					isset($row['ext']['tt_products']) &&
					is_array($row['ext']['tt_products'])
				) 	{
					$extVarArray = $row['ext']['tt_products'][0];
					if ($extVarArray['uid'] == $uid && $extVarArray['vars'] == $variant)	{
						$rc = $row;
					}
				}
			}
		}
		return $rc;
	}


	public function getUidArray ()
	{
		return $this->uidArray;
	}


	public function setUidArray ($uidArray)
	{
		$this->uidArray = $uidArray;
	}


	public function getAddressArray ()
	{
		$rc = array();
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
		$addressObj = $tablesObj->get('address',false);

		foreach ($this->itemArray as $sort => $actItemArray) {
			foreach ($actItemArray as $k1 => $actItem) {
				$row = &$actItem['rec'];
				$addressUid = $row['address'];
				if ($addressUid)	{
					$addressRow = $addressObj->get($addressUid);
					$rc[$addressUid] = $addressRow;
				}
			}
		}
		return $rc;
	}


	public function getQuantityArray ($uidArray, &$rowArray)
	{
		$rc = array();

		if (isset($rowArray) && is_array($rowArray)) {
			// loop over all items in the basket indexed by a sort string
			foreach ($this->itemArray as $sort => $actItemArray) {
				foreach ($actItemArray as $k1 => $actItem) {

					$row = &$actItem['rec'];
					$uid = $row['uid'];
					$count = $actItem['count'];
					$extArray = $row['ext'];

					if (in_array($uid, $uidArray) && isset($extArray) && is_array($extArray) && isset($extArray) && is_array($extArray))	{

						foreach ($rowArray as $functablename => $functableRowArray)	{
							$subExtArray = $extArray[$functablename];
							if (isset($subExtArray) && is_array($subExtArray))	{
								foreach ($functableRowArray as $subRow)	{
									$extItem = array('uid' => $subRow['uid']);
									if (in_array($extItem, $subExtArray))	{
										$rc[$uid][$functablename][$subRow['uid']] = $actItem['count'];
									}
								}
							}
						}
					}
				}
			}
		}
		return $rc;
	}


	public function &getItem ($basketExtra, $row, $fetchMode, $funcTablename='')
	{
		$item = array();
		$priceObj = GeneralUtility::makeInstance('tx_ttproducts_field_price');
		$priceRow = $row;
		$maxDiscount = 0;

		if (!$funcTablename)	{
			$funcTablename = $this->getFuncTablename();
		}

		if ($funcTablename == 'tt_products') {
			$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
			$viewTableObj = $tablesObj->get($funcTablename);
			$cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
			$count = 0;
			$variant = '';

			if ($fetchMode == 'useExt')	{
				$variant = $viewTableObj->variant->getVariantFromRow($row);
				$priceRow = $viewTableObj->getRowFromExt($funcTablename, $row, $cnfObj->getUseArticles());
			} else if ($fetchMode == 'rawRow') {
				$variant = $viewTableObj->variant->getVariantFromRawRow($row);
			} else if ($fetchMode == 'firstVariant') {
				$variant = $viewTableObj->variant->getVariantFromProductRow($row, 0);
			}

			$totalDiscountField = $viewTableObj->getTotalDiscountField();
			$viewTableObj->getTotalDiscount($row, $this->getPidListObj()->getPidlist());
			$priceRow[$totalDiscountField] = $row[$totalDiscountField];
			$priceTaxArray = $priceObj->getPriceTaxArray($this->conf['discountPriceMode'],$basketExtra, 'price', tx_ttproducts_control_basket::getRoundFormat(), tx_ttproducts_control_basket::getRoundFormat('discount'), $priceRow, $totalDiscountField);

			$price2TaxArray = $priceObj->getPriceTaxArray($this->conf['discountPriceMode'],$basketExtra, 'price2', tx_ttproducts_control_basket::getRoundFormat(), tx_ttproducts_control_basket::getRoundFormat('discount'), $priceRow, $totalDiscountField);
			$priceTaxArray = array_merge($priceTaxArray, $price2TaxArray);

			$tax = $priceTaxArray['taxperc'];
			$oldPriceTaxArray = $priceObj->convertOldPriceArray($priceTaxArray);
			$extArray = $row['ext'];

			if (isset($this->basketExt[$row['uid']]) && is_array($this->basketExt[$row['uid']]) && isset($this->basketExt[$row['uid']][$variant]))	{
				$count = $this->basketExt[$row['uid']][$variant];
			}
			if (!$count && is_array($this->giftServiceRow) && $row['uid'] == $this->giftServiceRow['uid'])	{
				$count = 1;
			}
			if ($count > $priceRow['inStock'] && !$this->conf['alwaysInStock'])	{
				$count = $priceRow['inStock'];
			}
			if (!$this->conf['quantityIsFloat'])	{
				$count = intval($count);
			}

			$item = array (
				'count' => $count,
				'weight' => $priceRow['weight'],
				'totalTax' => 0,
				'totalNoTax' => 0,
				'tax' => $tax,
				'rec' => $row,
			);

			$item = array_merge($item, $oldPriceTaxArray);	// Todo: remove this line
		}

		if (
			isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['changeBasketItem']) &&
			is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['changeBasketItem'])
		) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['changeBasketItem'] as $classRef) {
				$hookObj= GeneralUtility::makeInstance($classRef);
				if (method_exists($hookObj, 'changeBasketItem')) {
					$hookObj->changeBasketItem($row, $fetchMode, $funcTablename, $item);
				}
			}
		}

		return $item;
	} // getItem


	public function create ($basketExtra, $useArticles, $funcTablename)
	{
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
		$cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
		$conf = $cnfObj->conf;
		$theCode = 'BASKET';
		$itemTableConf = $cnfObj->getTableConf($funcTablename, $theCode);
		$viewTableObj = $tablesObj->get($funcTablename, false);
		$orderBy = $viewTableObj->getTableObj()->transformOrderby($itemTableConf['orderBy']);

		$uidArr = array();

		foreach($this->basketExt as $uidTmp => $v) {

			if ($uidTmp != 'gift' && !in_array($uidTmp, $uidArr)) {
				$uidArr[] = intval($uidTmp);
			}
		}
		if (count($uidArr) == 0) {
			return;
		}
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
		$viewTableObj = $tablesObj->get($funcTablename);
		$pidListObj = $this->pidListObj;
		$pid_list = $pidListObj->getPidlist();
		$where = 'uid IN (' . implode(',',$uidArr) . ')' . ($pid_list != '' ? ' AND pid IN ('. $pid_list . ')' : '') . $viewTableObj->getTableObj()->enableFields();

		$rcArray = $viewTableObj->getWhere($where, $theCode, $orderBy);
		$productsArray = array();
		$prodCount = 0;
		$bAddGiftService = false;

		foreach ($rcArray as $uid => $row) {

			$viewTableObj->getTableObj()->transformRow($row, TT_PRODUCTS_EXT);
			$pid = $row['pid'];
			$uid = $row['uid'];
			$isValidPage = $pidListObj->getPageArray($pid);

			// only the basket items for the pages belonging to this shop shall be used here
			if ($isValidPage) {

				foreach ($this->basketExt[$uid] as $bextVarLine => $bRow) {

					if (substr($bextVarLine, -1) == '.') {
						// this is an additional array which is no basket item
						if ($conf['whereGiftService']) {
							$bAddGiftService = true;
						}
						continue;
					}

					$bextVarArray = GeneralUtility::trimExplode('|', $bextVarLine);
					$bextVars = $bextVarArray[0];
					$currRow = $row;

					if ($useArticles != 3) {
						$viewTableObj->variant->modifyRowFromVariant($currRow, $bextVars);
					}
					$extTable = $funcTablename;
					$extUid = $uid;
					$extArray = array('uid' => $extUid, 'vars' => $bextVars);
					$currRow['ext'][$extTable][] = $extArray;

					if (in_array($useArticles, array(1, 3)) && $funcTablename == 'tt_products') {
						// get the article uid with these colors, sizes and gradings
						$articleRowArray = array();
						if ($useArticles == 1)  {
							$articleRow = $viewTableObj->getArticleRow($currRow, 'BASKET', false);
							if ($articleRow) {
								$articleRowArray[] = $articleRow;
							}
						} else if ($useArticles == 3) {
							$articleRowArray = $viewTableObj->getArticleRowsFromVariant($currRow, 'BASKET', $bextVars);
						}

						if (!empty($articleRowArray)) {
							foreach ($articleRowArray as $articleRow) {

									// use the fields of the article instead of the product
								// $viewTableObj->mergeAttributeFields($currRow, $articleRow, false, true); Preis wird sonst doppelt addiert!
								$currRow['ext']['tt_products_articles'][] = array('uid' => $articleRow['uid']);
							}
						}
					} else if ($useArticles == 2) {
						$productRow = $viewTableObj->getProductRow($currRow);
						$viewTableObj->mergeAttributeFields($currRow, $productRow, true);
					}

					if (isset($articleRowArray) && is_array($articleRowArray))	{
						$currRowTmp = $currRow; // this has turned out to be necessary!
						$currRow['ext']['mergeArticles'] = $currRowTmp;
						foreach ($articleRowArray as $articleRow) {
							$viewTableObj->mergeAttributeFields($currRow['ext']['mergeArticles'], $articleRow, false, true);
						}
						unset($currRow['ext']['mergeArticles']['ext']);
					}
					$productsArray[$prodCount] = $currRow;
					$prodCount++;
				}
			}
		}

		if ($bAddGiftService) {
			$where = $conf['whereGiftService'].' AND pid IN ('. $pidListObj->getPidlist().')'.$viewTableObj->getTableObj()->enableFields();
			$giftServiceArray = $viewTableObj->getWhere($where);
			if (isset($giftServiceArray) && is_array($giftServiceArray)) {
				reset($giftServiceArray);
				$this->giftServiceRow = current($giftServiceArray);
				if (isset($this->giftServiceRow) && is_array($this->giftServiceRow)) {
					$productsArray[$prodCount++] = $this->giftServiceRow;
				}
			}
		}
		$this->itemArray = array(); // array of the items in the basket
		$maxTax = 0;
		$taxObj = GeneralUtility::makeInstance('tx_ttproducts_field_tax');
		$uidArray = array();
		$calculObj = GeneralUtility::makeInstance('tx_ttproducts_basket_calculate');
		$calculArray = array();
		$categoryQuantity = array();
		$categoryArray = array();

		foreach ($productsArray as $k1 => $row) {

			$uid = $row['uid'];
			$tax = $taxObj->getFieldValue($basketExtra, $row, 'tax');
			if ($tax > $maxTax) {
				$maxTax = $tax;
			}

			// $variant = $viewTableObj->variant->getVariantFromRow($row);
			$newItem = $this->getItem($basketExtra, $row, 'useExt');
			$count = $newItem['count'];

			if($count > 0) {
				$weight = $newItem['weight'];
				$this->itemArray[$row[$viewTableObj->fieldArray['itemnumber']]][] = $newItem;
				$calculArray['count']	+= $count;
				$calculArray['weight']	+= $weight * $count;

				$currentCategory = $row['category'];
				$categoryArray[$currentCategory] = 1;
				$categoryQuantity[$currentCategory] += $count;
			}
			// if reseller is logged in then take 'price2', default is 'price'
			$uidArray[] = $uid;
		}

		$this->setCategoryQuantity($categoryQuantity);
		$this->setCategoryArray($categoryArray);
		$this->maxTax = $maxTax;
		$this->setUidArray($uidArray);
		$calculObj->setCalculatedArray($calculArray);
	}


	public function calculate ($basketExtra)
	{
		$cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
		$useArticles = $cnfObj->getUseArticles();

		$calculObj = GeneralUtility::makeInstance('tx_ttproducts_basket_calculate');
		$calculObj->calculate(
			$this->basketExt,
			$basketExtra,
			$this->getFuncTablename(),
			$useArticles,
			$this->maxTax,
			$this->itemArray
		);
	}


	public function calculateSums ()
	{
		$calculObj = GeneralUtility::makeInstance('tx_ttproducts_basket_calculate');
		$calculObj->calculateSums($this->recs);
	}


	public function getCalculatedSums ()
	{
		$calculObj = GeneralUtility::makeInstance('tx_ttproducts_basket_calculate');
		$calculatedArray = $calculObj->getCalculatedArray();
		return $calculatedArray;
	}


	public function addVoucherSums ()
	{
		$calculObj = GeneralUtility::makeInstance('tx_ttproducts_basket_calculate');
		$calculObj->addVoucherSums();
	}


	public function getCalculatedArray ()
	{
		$calculObj = GeneralUtility::makeInstance('tx_ttproducts_basket_calculate');
		$rc = $calculObj->getCalculatedArray();
		return $rc;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_basket.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_basket.php']);
}

