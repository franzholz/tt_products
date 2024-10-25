<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2016 Kasper Skårhøj <kasperYYYY@typo3.com>
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
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 *
 */

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

use JambageCom\TtProducts\Api\BasketApi;
use JambageCom\TtProducts\Api\ParameterApi;
use JambageCom\TtProducts\Api\PriceApi;
use JambageCom\TtProducts\Model\Field\FieldInterface;

class tx_ttproducts_basket implements SingletonInterface
{
    public $conf;

    // Internal: initBasket():
    public $recs = []; 		// in initBasket this is set to the recs-array of fe_user.
    // 	public $order = []; 	// order data
    public $giftnumber;				// current counter of the gifts

    public $itemArray = [];	// the items in the basket; database row, how many (quantity, count) and the price; this has replaced the former $calculatedBasket

    public $giftServiceRow;
    protected $maxTax;
    protected $categoryQuantity = [];
    protected $categoryArray = [];
    protected $uidArray = []; // uids of the items in the basket

    public $basketExtra; // deprecated. do not use it

    public function __construct()
    {
        $cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
        $this->conf = $cnfObj->conf;
    }

    public function init(
        $pibaseClass,
        $updateMode,
        $bStoreBasket
    ): bool {
        $this->setMaxTax(0.0);

        $formerBasket = tx_ttproducts_control_basket::getRecs();
        $pibaseObj = GeneralUtility::makeInstance('' . $pibaseClass);
        $cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
        $this->recs = $formerBasket;	// Sets it internally
        $parameterApi = GeneralUtility::makeInstance(ParameterApi::class);
        $basketApi = GeneralUtility::makeInstance(BasketApi::class);
        $piVars = $parameterApi->getPiVars();
        $gpVars = $parameterApi->getParameter('tt_products');
        $payment = false;

        if (isset($gpVars)) {
            $payment =
            ($gpVars['products_payment'] ?? false) ||
            ($gpVars['products_payment_x'] ?? false) ||
            !empty($gpVars['activity']['payment']);
        }

        if (    // use AGB checkbox if coming from INFO
            $payment &&
            isset($_REQUEST['recs']) &&
            is_array($_REQUEST['recs']) &&
            isset($_REQUEST['recs']['personinfo']) &&
            is_array($_REQUEST['recs']['personinfo'])
        ) {
            $bAgbSet = $this->recs['personinfo']['agb'] ?? false;
            $this->recs['personinfo']['agb'] = !empty($_REQUEST['recs']['personinfo']['agb']) ?? false;
            if ($bAgbSet != $this->recs['personinfo']['agb']) {
                tx_ttproducts_control_session::writeSession('recs', $this->recs);   // store this change
            }
        }

        $this->setItemArray([]);

        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $funcTablename = tx_ttproducts_control_basket::getFuncTablename();
        $viewTableObj = $tablesObj->get($funcTablename);

        $basketExt = $basketApi->getBasketExt();
        $basketExtRaw = $parameterApi->getBasketExtRaw();
        $basketInputConf = $cnfObj->getBasketConf('view', 'input');

        if (isset($basketInputConf) && is_array($basketInputConf)) {
            foreach ($basketInputConf as $lineNo => $inputConf) {
                if (
                    strpos($lineNo, '.') !== false &&
                    $inputConf['type'] == 'radio' &&
                    $inputConf['where'] &&
                    !empty($inputConf['name'])
                ) {
                    $radioUid = $gpVars[$inputConf['name']] ?? 0;
                    if ($radioUid) {
                        $rowArray = $viewTableObj->get('', 0, false, $inputConf['where']);

                        if (!empty($rowArray)) {
                            foreach ($rowArray as $uid => $row) {
                                if ($uid == $radioUid) {
                                    $basketExtRaw[$uid]['quantity'] = 1;
                                } else {
                                    unset($basketExt[$uid]);
                                }
                            }
                        }
                    }
                }
            }
        }

        if (!is_array($basketExt)) {
            $basketExt = [];
        }
        $basketApi->setBasketExt($basketExt);

        if (isset($this->basketExt['gift']) && is_array($this->basketExt['gift'])) {
            $this->giftnumber = count($this->basketExt['gift']) + 1;
        }
        $newGiftData = $gpVars['ttp_gift'] ?? 0;
        $extVars = $piVars['variants'] ?? '';
        $extVars = ($extVars ?: $gpVars['ttp_extvars'] ?? 0);
        $uid = $piVars['product'] ?? '';
        $uid = ($uid ?: $gpVars['tt_products'] ?? 0);
        $sameGiftData = true;
        $identGiftnumber = 0;

        $addMemo = $piVars['addmemo'] ?? '';
        if ($addMemo) {
            $basketExtRaw = '';
            $newGiftData = '';
        }

        // Call all changeBasket hooks
        if (
            isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['changeBasket']) &&
            is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['changeBasket'])
        ) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['changeBasket'] as $classRef) {
                $hookObj = GeneralUtility::makeInstance($classRef);
                if (method_exists($hookObj, 'changeBasket')) {
                    $hookObj->changeBasket(
                        $this,
                        $basketExt,
                        $basketExtRaw
                    );
                }
            }
        }

        if ($newGiftData) {
            $giftnumber = $gpVars['giftnumber'] ?? 0;
            if ($updateMode) {
                $basketExt['gift'][$giftnumber] = $newGiftData;
                $giftcount = intval($basketExt['gift'][$giftnumber]['item'][$uid][$extVars]);
                if ($giftcount == 0) {
                    $this->removeGift($giftnumber, $uid, $extVars);
                }
                $count = 0;
                foreach ($basketExt['gift'] as $prevgiftnumber => $rec) {
                    $count += $rec['item'][$uid][$extVars];
                }
                // update the general basket entry for this product
                $basketExt[$uid][$extVars] = $count;
            } else {
                if (is_array($basketExt['gift'])) {
                    foreach ($basketExt['gift'] as $prevgiftnumber => $rec) {
                        $sameGiftData = true;
                        foreach ($rec as $field => $value) {
                            // only the 'field' field can be different
                            if (
                                $field != 'item' &&
                                $field != 'note' &&
                                $value != $newGiftData[$field]
                            ) {
                                $sameGiftData = false;
                                break;
                            }
                        }

                        if ($sameGiftData) {
                            $identGiftnumber = $prevgiftnumber;
                            // always use the latest note
                            $basketExt['gift'][$identGiftnumber]['note'] = $newGiftData['note'];
                            break;
                        }
                    }
                } else {
                    $sameGiftData = false;
                }
                if (!$sameGiftData) {
                    $basketExt['gift'][$this->giftnumber] = $newGiftData;
                }
            }
        }

        if (is_array($basketExtRaw)) {
            $damUid = 0;
            if (isset($basketExtRaw['dam'])) {
                $damUid = intval($basketExtRaw['dam']);
            }

            foreach ($basketExtRaw as $uid => $basketItem) {
                if (
                    MathUtility::canBeInterpretedAsInteger($uid)
                ) {
                    if (isset($basketItem['quantity'])) {
                        if (
                            isset($basketExtRaw['dam'])
                        ) {
                            foreach ($basketItem as $damUid => $damBasketItem) {
                                $this->addItem(
                                    $viewTableObj,
                                    $uid,
                                    '',
                                    $damUid,
                                    $damBasketItem,
                                    $updateMode,
                                    $basketExt,
                                    $bStoreBasket,
                                    $newGiftData,
                                    $identGiftnumber,
                                    $sameGiftData
                                );
                            }
                        } else {
                            $this->addItem(
                                $viewTableObj,
                                $uid,
                                '',
                                $damUid,
                                $basketItem,
                                $updateMode,
                                $basketExt,
                                $bStoreBasket,
                                $newGiftData,
                                $identGiftnumber,
                                $sameGiftData
                            );
                        }
                    } else {
                        $addItems = false;
                        foreach ($basketItem as $basketKey => $basketValue) {
                            if (
                                isset($basketValue) &&
                                is_array($basketValue) &&
                                isset($basketValue['quantity'])
                            ) {
                                $addItems = true;
                                $this->addItem(
                                    $viewTableObj,
                                    $uid,
                                    $basketKey,
                                    '',
                                    $basketValue,
                                    $updateMode,
                                    $basketExt,
                                    $bStoreBasket,
                                    $newGiftData,
                                    $identGiftnumber,
                                    $sameGiftData
                                );
                            }
                        }

                        if ($addItems) {
                        } else {
                            $this->addItem(
                                $viewTableObj,
                                $uid,
                                '',
                                '',
                                $basketItem,
                                $updateMode,
                                $basketExt,
                                $bStoreBasket,
                                $newGiftData,
                                $identGiftnumber,
                                $sameGiftData
                            );
                        }
                    }
                }
            }

            // I did not find another possibility to delete elements completely from a multidimensional array
            // than to recreate the array
            $basketExtNew = [];
            foreach ($basketExt as $tmpUid => $tmpSubArr) {
                if (is_array($tmpSubArr) && count($tmpSubArr)) {
                    foreach ($tmpSubArr as $tmpExtVar => $tmpCount) {
                        if (
                            $tmpCount > 0 &&
                            (
                                $this->conf['quantityIsFloat'] ||
                                MathUtility::canBeInterpretedAsInteger($tmpCount)
                            )
                        ) {
                            $basketExtNew[$tmpUid][$tmpExtVar] = $basketExt[$tmpUid][$tmpExtVar];
                            if (
                                isset($basketExt[$tmpUid][$tmpExtVar . '.']) &&
                                is_array($basketExt[$tmpUid][$tmpExtVar . '.'])
                            ) {
                                $basketExtNew[$tmpUid][$tmpExtVar . '.'] = $basketExt[$tmpUid][$tmpExtVar . '.'];
                            }
                        } elseif (is_array($tmpCount)) {
                            $basketExtNew[$tmpUid][$tmpExtVar] = $tmpCount;
                        } else {
                            // nothing
                        }
                    }
                } else {
                    $basketExtNew[$tmpUid] = $tmpSubArr;
                }
            }
            $basketExt = $basketExtNew;

            if ($bStoreBasket) {
                if (is_array($basketExt) && count($basketExt)) {
                    $basketApi->storeBasketExt($basketExt);
                } else {
                    $basketApi->storeBasketExt([]);
                }
            }
        }

        $basketApi->setBasketExt($basketExt);

        return true;
    } // init

    public function setCategoryQuantity($categoryQuantity): void
    {
        $this->categoryQuantity = $categoryQuantity;
    }

    public function getCategoryQuantity()
    {
        return $this->categoryQuantity;
    }

    public function setCategoryArray($categoryArray): void
    {
        $this->categoryArray = $categoryArray;
    }

    public function getCategoryArray()
    {
        return $this->categoryArray;
    }

    public function getRecs()
    {
        return $this->recs;
    }

    public function getAllVariants($funcTablename, $row, $variantRow)
    {
        $cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $itemTable = $tablesObj->get($funcTablename);

        // 		$variant = $itemTable->variant->getVariantFromRawRow($row);
        $variant = $itemTable->variant->getVariantFromProductRow($row, $variantRow, $cnfObj->getUseArticles());

        $editVariant = $itemTable->editVariant->getVariantFromRawRow($row);
        $allVariants = $variant . ($editVariant != '' ? '|editVariant:' . $editVariant : '');

        return $allVariants;
    }

    public function checkEditVariants()
    {
        $funcTablename = tx_ttproducts_control_basket::getFuncTablename();
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $itemTable = $tablesObj->get($funcTablename);
        $result = [];
        $itemArray = $this->getItemArray();

        foreach ($itemArray as $sort => $actItemArray) {
            foreach ($actItemArray as $k1 => $actItem) {
                $row = $actItem['rec'];
                $editConfig = $itemTable->editVariant->getValidConfig($row);
                $validEditVariant = true;

                if (is_array($editConfig)) {
                    $validEditVariant = $itemTable->editVariant->checkValid($editConfig, $row);
                }

                if ($validEditVariant !== true) {
                    $result[$row['uid']] =
                        [
                            'rec' => $row,
                            'index1' => $sort,
                            'index2' => $k1,
                            'error' => $validEditVariant,
                        ];
                }
            }
        }

        if (empty($result)) {
            $result = true;
        }

        return $result;
    }

    public function removeEditVariants($removeArray): void
    {
        $modified = false;
        $itemArray = $this->getItemArray();
        $removeBasketExt = [];

        if (is_array($removeArray)) {
            foreach ($removeArray as $uid => $removePart) {
                if (
                    isset($removePart['index1']) &&
                    isset($removePart['index2']) &&
                    isset($itemArray[$removePart['index1']][$removePart['index2']]) &&
                    isset($removePart['rec'])
                ) {
                    unset($itemArray[$removePart['index1']][$removePart['index2']]);

                    $row = $removePart['rec'];
                    $extArray = $row['ext'] ?? [];
                    $extVarLine = $extArray['extVarLine'];
                    $removeBasketExt[$uid][$extVarLine] = true;
                    $modified = true;
                }
            }
        }

        if ($modified) {
            $this->setItemArray($itemArray);
            tx_ttproducts_control_basket::removeFromBasketExt($removeBasketExt);
        }
    }

    public function getRadioInputArray(
        $row
    ) {
        $cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
        $basketConf = $cnfObj->getBasketConf('view', 'input');
        $result = false;
        if (!empty($basketConf)) {
            foreach ($basketConf as $lineNo => $inputConf) {
                if (strpos($lineNo, '.') !== false) {
                    $bIsValid = tx_ttproducts_sql::isValid($row, $inputConf['where']);
                    if ($bIsValid && $inputConf['type'] == 'radio') {
                        $result = $inputConf;
                    }
                }
            }
        }

        return $result;
    }

    public function setItemArray($itemArray): void
    {
        $this->itemArray = $itemArray;
    }

    public function getItemArray()
    {
        return $this->itemArray;
    }

    public function isEmpty()
    {
        $itemArray = $this->getItemArray();
        $result = empty($itemArray);

        return $result;
    }

    public function addItem(
        $viewTableObj,
        $uid,
        $externalVariant,
        $damUid,
        $item,
        $updateMode,
        &$basketExt,
        $bStoreBasket,
        $newGiftData,
        $identGiftnumber,
        $sameGiftData
    ): void {
        $priceObj = GeneralUtility::makeInstance('tx_ttproducts_field_price');

        // quantities for single values are stored in an array. This is necessary because a HTML checkbox does not send any values if it has been unchecked
        if (
            isset($item) &&
            is_array($item) &&
            isset($item['quantity']) &&
            is_array($item['quantity'])
        ) {
            reset($item['quantity']);
            $item['quantity'] = current($item['quantity']);
        }

        if ($updateMode) {
            foreach ($item as $md5 => $quantity) {
                $quantity = $priceObj->toNumber($this->conf['quantityIsFloat'], $quantity);

                if (
                    isset($basketExt[$uid]) &&
                    is_array($basketExt[$uid]) &&
                    $md5 != 'additional'
                ) {
                    foreach ($basketExt[$uid] as $allVariants => $tmp) {
                        $actMd5 = md5($allVariants);

                        // useArticles if you have different prices and therefore articles for color, size, additional and gradings
                        if ($actMd5 == $md5) {
                            $count = $this->getMaxCount($quantity, $uid);
                            $basketExt[$uid][$allVariants] = $count;

                            if (isset($item['additional']) && is_array($item['additional']) &&
                            isset($item['additional'][$actMd5]['giftservice']) && is_array($item['additional'][$actMd5]['giftservice'])) {
                                if (isset($basketExt[$uid][$allVariants . '.']) && !is_array($basketExt[$uid][$allVariants . '.'])) {
                                    $basketExt[$uid][$allVariants . '.'] = [];
                                }

                                if (isset($basketExt[$uid][$allVariants . '.']['additional']) && !is_array($basketExt[$uid][$allVariants . '.']['additional'])) {
                                    $basketExt[$uid][$allVariants . '.']['additional'] = [];
                                }
                                $bHasGiftService = $item['additional'][$actMd5]['giftservice']['1'];
                                if ($bHasGiftService) {
                                    $basketExt[$uid][$allVariants . '.']['additional']['giftservice'] = '1';
                                } else {
                                    unset($basketExt[$uid][$allVariants . '.']);
                                }
                            }

                            if (
                                isset($basketExt['gift']) &&
                                is_array($basketExt['gift'])
                            ) {
                                $count = count($basketExt['gift']);
                                $giftCount = 0;
                                $restQuantity = $quantity;
                                for ($giftnumber = 1; $giftnumber <= $count; ++$giftnumber) {
                                    if ($restQuantity == 0) {
                                        $this->removeGift($giftnumber, $uid, $allVariants);
                                    } else {
                                        if ($basketExt['gift'][$giftnumber]['item'][$uid][$allVariants] > $restQuantity) {
                                            $basketExt['gift'][$giftnumber]['item'][$uid][$allVariants] = $restQuantity;
                                            $restQuantity = 0;
                                        } elseif ($giftnumber < $count) {
                                            $restQuantity -= $basketExt['gift'][$giftnumber]['item'][$uid][$allVariants];
                                        } else {
                                            $basketExt['gift'][$giftnumber]['item'][$uid][$allVariants] = $restQuantity;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } else {
            if (!isset($item['quantity'])) {
                return;
            }

            $variant = $viewTableObj->variant->getVariantFromRawRow($item);
            $editVariant = $viewTableObj->editVariant->getVariantFromRawRow($item);
            $allVariants =
                $variant .
                    ($editVariant != '' ? '|editVariant:' . $editVariant : '') .
                    ($externalVariant != '' ? '|records:' . $externalVariant : '');

            if ($damUid) {
                $tableVariant = $viewTableObj->variant->getTableUid('tx_dam', $damUid);
                $allVariants .= $tableVariant;
            }
            $oldcount = $basketExt[$uid][$allVariants] ?? 0;
            $quantity = 0;
            $quantity = $priceObj->toNumber($this->conf['quantityIsFloat'], $item['quantity']);
            $count = $this->getMaxCount($quantity, $uid);

            if ($count >= 0 && $bStoreBasket) {
                $newcount = $count;

                if ($newGiftData) {
                    $giftnumber = 0;
                    if ($sameGiftData) {
                        $giftnumber = $identGiftnumber;
                        $oldcount -= $basketExt['gift'][$giftnumber]['item'][$uid][$allVariants];
                    } else {
                        $giftnumber = $this->giftnumber;
                    }
                    $newcount += $oldcount;
                    $basketExt['gift'][$giftnumber]['item'][$uid][$allVariants] = $count;
                    if ($count == 0) {
                        $this->removeGift($giftnumber, $uid, $allVariants);
                    }
                }

                if ($newcount) {
                    if ($this->conf['alwaysUpdateOrderAmount'] == 1) {
                        $basketExt[$uid][$allVariants] = $newcount;
                    } else {
                        $basketExt[$uid][$allVariants] = $oldcount + $newcount;
                    }
                } else {
                    unset($basketExt[$uid][$allVariants]);
                }
            }
        }
    }

    /**
     * Removes a gift from the basket.
     *
     * @param		int		 index of the gift
     * @param 		int			uid of the product
     * @param		string		variant of the product
     */
    public function removeGift($giftnumber, $uid, $variant): void
    {
        $basketApi = GeneralUtility::makeInstance(BasketApi::class);
        $basketExt = $basketApi->getBasketExt();

        if ($basketExt['gift'][$giftnumber]['item'][$uid][$variant] >= 0) {
            unset($basketExt['gift'][$giftnumber]['item'][$uid][$variant]);
            if (!count($basketExt['gift'][$giftnumber]['item'][$uid])) {
                unset($basketExt['gift'][$giftnumber]['item'][$uid]);
            }
            if (!$basketExt['gift'][$giftnumber]['item']) {
                unset($basketExt['gift'][$giftnumber]);
            }
        }

        $basketApi->storeBasketExt($basketExt);
    }

    public function getMaxCount($quantity, $uid = 0)
    {
        $count = 0;

        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $prodTable = $tablesObj->get('tt_products');
        $row = $prodTable->get($uid);

        if ($row['basketmaxquantity'] > 0 && $quantity > $row['basketmaxquantity']) {
            $basketmaxquantity = MathUtility::convertToPositiveInteger($row['basketmaxquantity']);
            $count = MathUtility::forceIntegerInRange($quantity, 0, $basketmaxquantity, 0);
            $quantity = $count; // reduce the quantitiy to the product's maximum allowed quantity
        }

        if (
            $this->conf['basketMaxQuantity'] == 'inStock' &&
            !$this->conf['alwaysInStock'] &&
            !empty($uid)
        ) {
            $count = MathUtility::forceIntegerInRange($quantity, 0, $row['inStock'], 0);
        } elseif ($this->conf['basketMaxQuantity'] == 'creditpoint' && !empty($uid)) {
            $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
            $creditpointsObj = GeneralUtility::makeInstance('tx_ttproducts_field_creditpoints');
            $missingCreditpoints = 0;
            $creditpointsObj->getBasketMissingCreditpoints($row['creditpoints'] * $quantity, $missingCreditpoints, $tmp);

            if ($quantity > 1 && $missingCreditpoints > 0) {
                $reduceQuantity = intval($missingCreditpoints / $row['creditpoints']);
                if ($missingCreditpoints > $reduceQuantity * $row['creditpoints']) {
                    ++$reduceQuantity;
                }
                if ($quantity - $reduceQuantity >= 1) {
                    $count = $quantity - $reduceQuantity;
                } else {
                    $count = 0;
                }
            } else {
                $count = ($missingCreditpoints > 0 ? 0 : $quantity);
            }
        } elseif ($this->conf['quantityIsFloat']) {
            $count = floatval($quantity);
            if ($count < 0) {
                $count = 0;
            }
            if ($count > $this->conf['basketMaxQuantity']) {
                $count = $this->conf['basketMaxQuantity'];
            }
        } else {
            $count = MathUtility::forceIntegerInRange($quantity, 0, (int) $this->conf['basketMaxQuantity'], 0);
        }

        return $count;
    }

    /**
     * Returns a clear 'recs[tt_products]' array - so clears the basket.
     */
    public function getClearBasketRecord()
    {
        // Returns a basket-record cleared of tt_product items
        unset($this->recs['tt_products']);
        unset($this->recs['personinfo']);
        unset($this->recs['delivery']);
        unset($this->recs['creditcard']);
        unset($this->recs['account']);

        return $this->recs;
    } // getClearBasketRecord

    /**
     * Empties the shopping basket!
     */
    public function clearBasket($bForce = false): void
    {
        if (
            $this->conf['debug'] != '1' ||
            $bForce
        ) {
            // TODO: delete only records from relevant pages
            // Empties the shopping basket!
            $basketApi = GeneralUtility::makeInstance(BasketApi::class);

            $basketRecord = $this->getClearBasketRecord();
            tx_ttproducts_control_basket::setRecs($basketRecord);
            tx_ttproducts_control_basket::store('recs', $basketRecord);
            $basketApi->setBasketExt([]);
            tx_ttproducts_control_basket::store('basketExt', []);
            tx_ttproducts_control_basket::store('order', []);
            $this->setItemArray([]);
        }
        tx_ttproducts_control_basket::store('ac', []);
        tx_ttproducts_control_basket::store('cc', []);
        tx_ttproducts_control_basket::store('cp', []);
        tx_ttproducts_control_basket::store('vo', []);
    } // clearBasket

    public function isInBasket($prod_uid)
    {
        $rc = false;
        $itemArray = $this->getItemArray();
        if (count($itemArray)) {
            // loop over all items in the basket indexed by a sort string
            foreach ($itemArray as $sort => $actItemArray) {
                foreach ($actItemArray as $k1 => $actItem) {
                    $row = $actItem['rec'];
                    if ($prod_uid == $row['uid']) {
                        $rc = true;
                        break;
                    }
                }
                if ($rc == true) {
                    break;
                }
            }
        }

        return $rc;
    }

    // get gradutated prices for all products in a list view or a single product in a single view
    // 	public function getGraduatedPrices ($uid)	{
    // 		$graduatedPriceObj = GeneralUtility::makeInstance('tx_ttproducts_graduated_price');
    // 		$this->formulaArray = $graduatedPriceObj->getFormulasByItem($uid);
    // 	}

    public function get($uid, $variant)
    {
        $rc = [];
        $itemArray = $this->getItemArray();
        foreach ($itemArray as $sort => $actItemArray) {
            foreach ($actItemArray as $k1 => $actItem) {
                $row = $actItem['rec'];
                if (
                    $row['uid'] == $uid &&
                    isset($row['ext']) &&
                    is_array($row['ext']) &&
                    isset($row['ext']['tt_products']) &&
                    is_array($row['ext']['tt_products'])
                ) {
                    $extVarArray = $row['ext']['tt_products'][0];
                    if (
                        $extVarArray['uid'] == $uid &&
                        $extVarArray['vars'] == $variant
                    ) {
                        $rc = $row;
                    }
                }
            }
        }

        return $rc;
    }

    public function getUidArray()
    {
        return $this->uidArray;
    }

    public function setUidArray($uidArray): void
    {
        $this->uidArray = $uidArray;
    }

    public function getQuantityArray($uidArray, &$rowArray)
    {
        $rc = [];
        $itemArray = $this->getItemArray();

        if (isset($rowArray) && is_array($rowArray)) {
            // loop over all items in the basket indexed by a sort string
            foreach ($itemArray as $sort => $actItemArray) {
                foreach ($actItemArray as $k1 => $actItem) {
                    $row = $actItem['rec'];
                    $uid = $row['uid'];
                    $count = $actItem['count'];
                    $extArray = $row['ext'] ?? [];

                    if (
                        in_array($uid, $uidArray) &&
                        isset($extArray) &&
                        is_array($extArray) &&
                        isset($extArray) &&
                        is_array($extArray)
                    ) {
                        foreach ($rowArray as $funcTablename => $functableRowArray) {
                            $subExtArray = $extArray[$funcTablename];
                            if (isset($subExtArray) && is_array($subExtArray)) {
                                foreach ($functableRowArray as $subRow) {
                                    $extItem = ['uid' => $subRow['uid']];
                                    if (in_array($extItem, $subExtArray)) {
                                        $rc[$uid][$funcTablename][$subRow['uid']] = $actItem['count'];
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

    public function getItem(// ToDo: Den Preis aus dem Download und der PDF Datei berücksichtigen
        $mergePrices,
        $basketExt,
        $basketExtra,
        $basketRecs,
        array $row, // This comes from the table tt_products, however the price can be have been modified by article rows already
        $fetchMode,
        &$taxInfoArray = [],
        $funcTablename = '',
        $externalRowArray = [], // für Download
        $enableTaxZero = false
    ) {
        $basketApi = GeneralUtility::makeInstance(BasketApi::class);
        $calculationField = FieldInterface::PRICE_CALCULATED;
        $pricetablesCalculator = GeneralUtility::makeInstance('tx_ttproducts_pricetablescalc');

        $prodFuncTablename = 'tt_products';
        $roundFormat = tx_ttproducts_control_basket::getRoundFormat();
        $discountRoundFormat = tx_ttproducts_control_basket::getRoundFormat('discount');

        $priceObj = GeneralUtility::makeInstance('tx_ttproducts_field_price');
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
        $pricetablescalc = GeneralUtility::makeInstance('tx_ttproducts_pricetablescalc');

        $priceRow = $row;
        $recordPrice = 0;
        $articleDiscountPrice = 0;
        $extArray = $row['ext'] ?? [];

        if (!$funcTablename) {
            $funcTablename = tx_ttproducts_control_basket::getFuncTablename();
        }

        $item = [];

        $viewTableObj = $tablesObj->get($prodFuncTablename);
        $variant = '';

        if ($fetchMode == 'useExt') {
            $variant = $viewTableObj->getVariant()->getVariantFromRow($row);
            $priceRow =
                $viewTableObj->getRowFromExt(
                    $prodFuncTablename,
                    $row,
                    $cnfObj->getUseArticles(),
                    false
                );
        } elseif ($fetchMode == 'rawRow') {
            $variant = $viewTableObj->getVariant()->getVariantFromRawRow($row);
        } elseif ($fetchMode == 'firstVariant') {
            $variantRow = $viewTableObj->getVariant()->getVariantRow($row, []);
            $variant =
                $viewTableObj->getVariant()->getVariantFromProductRow(
                    $row,
                    $variantRow,
                    $cnfObj->getUseArticles()
                );
        } else {
            debug($tmp, 'internal error in tt_products method getItem'); // keep this
        }
        $totalDiscountField = FieldInterface::DISCOUNT;
        $viewTableObj->getTotalDiscount($row, tx_ttproducts_control_basket::getPidListObj()->getPidlist());
        if (isset($row[$totalDiscountField])) {
            $priceRow[$totalDiscountField] = $row[$totalDiscountField];
        }

        if (
            $funcTablename != 'tt_products' &&
            isset($externalRowArray) &&
            is_array($externalRowArray) &&
            !empty($externalRowArray)
        ) {
            $newPrice = $priceRow['price'];
            $priceIsModified = $basketApi->getRecordvariantAndPriceFromRows(
                $recordVariant,
                $newPrice,
                $externalUidArray,
                $externalRowArray
            );
            if ($priceIsModified) {
                $recordPrice = $priceRow['price'] = $newPrice;
            }
            $variant .= $recordVariant;
        }

        if (isset($extArray['tx_dam']) && is_array($extArray['tx_dam'])) {
            reset($extArray['tx_dam']);
            $firstDam = current($extArray['tx_dam']);
            $extUid = $firstDam['uid'];
            $tableVariant =
                $viewTableObj->variant->getTableUid(
                    'tx_dam',
                    $extUid
                );
            $variant .= $tableVariant;
        }

        if (isset($extArray['editVariant'])) {
            $variant .= '|' . $extArray['editVariant'];
        }

        $count =
            $basketApi->getBasketCount(
                $row,
                $basketExt,
                $variant,
                $this->conf['quantityIsFloat']
            );

        if (
            !$count &&
            is_array($this->giftServiceRow) &&
            $row['uid'] == $this->giftServiceRow['uid']
        ) {
            $count = 1;
        }

        if (
            $count > $priceRow['inStock'] &&
            !$this->conf['alwaysInStock']
        ) {
            $count = $priceRow['inStock'];
        }

        if (!$this->conf['quantityIsFloat']) {
            $count = intval($count);
        }

        if (
            isset($priceRow['ext']) &&
            isset($priceRow['ext']['tt_products_articles']) &&
            is_array($priceRow['ext']['tt_products_articles'])
        ) {
            $articleRowArray = $priceRow['ext']['tt_products_articles'];
            $articleObj = $tablesObj->get('tt_products_articles', false);
            $graduatedPriceObj = $articleObj->getGraduatedPriceObject();
            $storedPrice = $priceRow['price'];
            $priceRow['price'] = $row['price']; // undo former price additions from articles
            if ($recordPrice) {
                $priceRow['price'] = $recordPrice;
            }
            $previouslyAddedPrice = 0;
            if (
                isset($targetRow['ext']['addedPrice'])
            ) {
                $previouslyAddedPrice = $priceRow['addedPrice'];
            }

            $parentProductCount =
                $basketApi->getBasketCount(
                    $row,
                    $basketExt,
                    $variant,
                    $this->conf['quantityIsFloat'],
                    true
                );
            foreach ($articleRowArray as $articleRow) {
                if (
                    is_object($graduatedPriceObj) &&
                    $graduatedPriceObj->hasDiscountPrice($articleRow)
                ) {
                    $calculationCount = $count;
                    $usesParentProductCount = $articleObj->usesAddParentProductCount($articleRow);
                    if ($usesParentProductCount) {
                        $calculationCount = $parentProductCount;
                    }

                    $calculatedPrice =
                        $pricetablesCalculator->getDiscountPrice(
                            $graduatedPriceObj,
                            $articleRow,
                            $articleRow['price'],
                            $calculationCount
                        );

                    if ($calculatedPrice !== false) {
                        $articleRow[$calculationField] = $calculatedPrice;
                    }
                }

                $bIsAddedPrice = $cnfObj->hasConfig($articleRow, 'isAddedPrice');

                PriceApi::mergeRows(
                    $priceRow,
                    $articleRow,
                    'price',
                    $bIsAddedPrice,
                    $previouslyAddedPrice,
                    $calculationField,
                    false,
                    true
                );
            }

            $articleDiscountPrice = $priceRow['price'];
            $priceRow[$calculationField] = $row[$calculationField] = $articleDiscountPrice;
            $priceRow['price'] = $storedPrice;
        }

        $graduatedPriceObj = $viewTableObj->getGraduatedPriceObject();
        if (
            is_object($graduatedPriceObj) &&
            $graduatedPriceObj->hasDiscountPrice($priceRow)
        ) {
            $discountPrice = $pricetablescalc->getDiscountPrice(
                $graduatedPriceObj,
                $priceRow,
                $priceRow['price'],
                $count
            );

            if (
                $discountPrice !== false &&
                !$articleDiscountPrice
            ) {
                $priceRow[$calculationField] = $row[$calculationField] = $discountPrice;
            }
        }

        $taxInfoArray = '';
        $priceTaxArray = $priceObj->getPriceTaxArray(
            $taxInfoArray,
            $this->conf['discountPriceMode'] ?? '',
            $basketExtra,
            $basketRecs,
            'price',
            $roundFormat,
            $discountRoundFormat,
            $priceRow,
            $totalDiscountField,
            $enableTaxZero
        );

        $price2TaxArray = $priceObj->getPriceTaxArray(
            $taxInfoArray,
            $this->conf['discountPriceMode'] ?? '',
            $basketExtra,
            $basketRecs,
            'price2',
            $roundFormat,
            $discountRoundFormat,
            $priceRow,
            $totalDiscountField,
            $enableTaxZero
        );
        $priceTaxArray =
            array_merge($priceTaxArray, $price2TaxArray);

        $tax = $priceTaxArray['taxperc'];
        $oldPriceTaxArray = $priceObj->convertOldPriceArray($priceTaxArray);
        $depositArray = $priceObj->getPriceTaxArray(
            $taxInfoArray,
            '',
            $basketExtra,
            $basketRecs,
            'deposit',
            '',
            '',
            $priceRow,
            ''
        );

        $priceObj->convertIntoRow($row, $priceTaxArray);
        $item = [
            'count' => $count,
            'weight' => $priceRow['weight'],
            'totalTax' => 0,
            'totalNoTax' => 0,
            'tax' => $tax,
            'rec' => $row,
        ];

        if (
            isset($taxInfoArray) &&
            is_array($taxInfoArray) &&
            !empty($taxInfoArray)
        ) {
            $item['taxInfo'] = $taxInfoArray;
        }

        $item = array_merge($item, $oldPriceTaxArray);	// Todo: remove this line
        $item = array_merge($item, $depositArray);

        if (
            isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['changeBasketItem']) &&
            is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['changeBasketItem'])
        ) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['changeBasketItem'] as $classRef) {
                $hookObj = GeneralUtility::makeInstance($classRef);
                if (method_exists($hookObj, 'changeBasketItem')) {
                    $hookObj->changeBasketItem(
                        $row,
                        $fetchMode,
                        $prodFuncTablename,
                        $item
                    );
                }
            }
        }

        return $item;
    } // getItem

    public function getItemRow(
        array $row,
        $bextVarLine,
        $useArticles,
        $funcTablename,
        $basketExt = [],
        $mergeAttributeFields = true
    ) {
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $viewTableObj = $tablesObj->get($funcTablename);
        $variantSeparator = $viewTableObj->getVariant()->getSplitSeparator();
        $basketApi = GeneralUtility::makeInstance(BasketApi::class);
        $parameterApi = GeneralUtility::makeInstance(ParameterApi::class);

        $uid = $row['uid'];
        $calculationField = FieldInterface::PRICE_CALCULATED;

        $bextVarArray = GeneralUtility::trimExplode('|', $bextVarLine);
        $bextVars = $bextVarArray[0];
        $currRow = $row;

        if ($useArticles != 3) {
            $viewTableObj->variant->modifyRowFromVariant(
                $currRow,
                $bextVars
            );
        }

        $extTable = $funcTablename;
        $extUid = $uid;
        $extArray =
            [
                'uid' => $extUid,
                'vars' => $bextVars,
            ];
        $currRow['ext'][$extTable][] = $extArray;

        foreach ($bextVarArray as $k => $bextVar) {
            if (
                strpos($bextVar, 'tx_dam') === 0 &&
                isset($bextVarArray[$k + 1])
            ) {
                $extTable = 'tx_dam';
                $extUid = intval($bextVarArray[$k + 1]);
                $damObj = $tablesObj->get('tx_dam');
                $damObj->modifyItemRow($currRow, $extUid);
                $currRow['ext'][$extTable][] = ['uid' => $extUid];
            }

            if (strpos($bextVar, 'editVariant:') === 0) {
                $editVariant = str_replace('editVariant:', '', $bextVar);
                $variantArray =
                    preg_split(
                        '/[\h]*' . tx_ttproducts_variant_int::INTERNAL_VARIANT_SEPARATOR . '[\h]*/',
                        $editVariant,
                        -1,
                        PREG_SPLIT_NO_EMPTY
                    );

                $editVariantRow = [];
                foreach ($variantArray as $variant) {
                    $parts = explode('=>', $variant);
                    if (
                        isset($parts) &&
                        is_array($parts) &&
                        count($parts) == 2
                    ) {
                        $editVariantRow[$parts['0']] = $parts['1'];
                    }
                }
                $currRow = array_merge($currRow, $editVariantRow);
                $currRow['ext']['editVariant'] = $bextVar;
            }

            if (($pos = strpos($bextVar, 'records:')) === 0) {
                $recordVariant = substr($bextVar, $pos + strlen('records:'));
                $variantArray = explode(tx_ttproducts_variant_int::EXTERNAL_QUANTITY_SEPARATOR, $recordVariant);
                $recordVariantRow = [];
                foreach ($variantArray as $variant) {
                    $parts = explode('=', $variant);
                    if (isset($parts) && is_array($parts) && count($parts) == 2) {
                        $funcTablename = $parameterApi->getParamsTable($parts['0']);
                        if ($funcTablename !== false) {
                            $localTableObj = $tablesObj->get($funcTablename);
                            $recordRow = $localTableObj->get(intval($parts['1']));
                            if (!empty($recordRow)) {
                                $recordVariantRow[$funcTablename] = $recordRow;
                            }
                        }
                    }
                }
                $currRow['ext']['records'] = $recordVariantRow;
            }
        }
        // $currRow['extVars'] = $bextVars;
        $currRow['ext']['extVarLine'] = $bextVarLine;

        if (
            in_array($useArticles, [1, 3]) &&
            $funcTablename == 'tt_products'
        ) {
            // get the article uid with these colors, sizes and gradings
            $articleRowArray = [];

            if ($useArticles == 1) {
                $articleRow =
                    $viewTableObj->getArticleRow(
                        $currRow,
                        'BASKET',
                        false
                    );

                if ($articleRow) {
                    $articleRowArray[] = $articleRow;
                }
            } elseif ($useArticles == 3) {
                $articleRowArray =
                    $viewTableObj->getArticleRowsFromVariant(
                        $currRow,
                        'BASKET',
                        $bextVars
                    );
            }

            if (
                isset($articleRowArray) &&
                is_array($articleRowArray) &&
                count($articleRowArray)
            ) {
                foreach ($articleRowArray as $articleRow) {
                    // use the fields of the article instead of the product
                    // $viewTableObj->mergeAttributeFields($currRow, $articleRow, false, true); Preis wird sonst doppelt addiert!
                    $currRow['ext']['tt_products_articles'][] = ['uid' => $articleRow['uid']];
                }
            }
        } elseif ($useArticles == 2) {
            $productRow = $viewTableObj->getProductRow($currRow);
            $viewTableObj->mergeAttributeFields(
                $currRow,
                $productRow,
                true,
                false,
                true,
                '',
                false
            );
        }

        if ($useArticles == 3) {
            $viewTableObj->variant->modifyRowFromVariant(
                $currRow,
                $bextVars
            );
        }

        if (
            isset($articleRowArray) &&
            is_array($articleRowArray) &&
            !empty($articleRowArray)
        ) {
            $currRowTmp = $currRow; // this has turned out to be necessary!
            $currRow['ext']['mergeArticles'] = $currRowTmp;

            if ($mergeAttributeFields) {
                $pricetablesCalculator = GeneralUtility::makeInstance('tx_ttproducts_pricetablescalc');

                $count =
                    $basketApi->getBasketCount(
                        $currRow,
                        $basketExt,
                        $bextVarLine,
                        $this->conf['quantityIsFloat']
                    );

                $parentProductCount =
                    $basketApi->getBasketCount(
                        $currRow,
                        $basketExt,
                        $bextVarLine,
                        $this->conf['quantityIsFloat'],
                        true
                    );
                $articleObj = $tablesObj->get('tt_products_articles', false);
                $graduatedPriceObj = $articleObj->getGraduatedPriceObject();

                foreach ($articleRowArray as $articleRow) {
                    if (
                        is_object($graduatedPriceObj) &&
                        $graduatedPriceObj->hasDiscountPrice($articleRow)
                    ) {
                        $calculationCount = $count;
                        $usesParentProductCount = $articleObj->usesAddParentProductCount($articleRow);
                        if ($usesParentProductCount) {
                            $calculationCount = $parentProductCount;
                        }

                        $calculatedPrice =
                            $pricetablesCalculator->getDiscountPrice(
                                $graduatedPriceObj,
                                $articleRow,
                                $articleRow['price'],
                                $calculationCount
                            );

                        if ($calculatedPrice !== false) {
                            $articleRow[$calculationField] = $calculatedPrice;
                        }
                    }
                    $viewTableObj->mergeAttributeFields(
                        $currRow['ext']['mergeArticles'],
                        $articleRow,
                        false,
                        true,
                        true,
                        $calculationField,
                        false
                    );
                }
            }
        }

        return $currRow;
    }

    public function create(
        $theCode,
        $basketExt,
        $basketExtra,
        $basketRecs,
        $useArticles,
        $priceTaxNotVarying,
        $funcTablename
    ): void {
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
        $conf = $cnfObj->conf;
        $itemTableConf = $cnfObj->getTableConf($funcTablename, $theCode);
        $viewTableObj = $tablesObj->get($funcTablename, false);
        $orderBy = $viewTableObj->getTableObj()->transformOrderby($itemTableConf['orderBy']);
        $mergePrices = true;
        $calculObj = GeneralUtility::makeInstance('tx_ttproducts_basket_calculate');
        $calculatedArray = [
            'count' => 0,
            'weight' => 0,
        ];
        $calculObj->setBaseCalculatedArray($calculatedArray);

        $uidArr = [];

        foreach ($basketExt as $uidTmp => $v) {
            if ($uidTmp != 'gift' && !in_array($uidTmp, $uidArr)) {
                $uidArr[] = intval($uidTmp);
            }
        }

        if (count($uidArr) == 0) {
            return;
        }

        $pidListObj = tx_ttproducts_control_basket::getPidListObj();
        $pid_list = $pidListObj->getPidlist();

        $where = 'uid IN (' . implode(',', $uidArr) . ')' . ($pid_list != '' ? ' AND pid IN (' . $pid_list . ')' : '') . $viewTableObj->getTableObj()->enableFields();

        $rcArray = $viewTableObj->getWhere($where, $theCode, $orderBy);

        $productsArray = [];
        $prodCount = 0;
        $bAddGiftService = false;

        foreach ($rcArray as $uid => $row) {
            $viewTableObj->getTableObj()->transformRow($row, TT_PRODUCTS_EXT);
            $pid = $row['pid'];
            $uid = $row['uid'];
            $isValidPage = $pidListObj->getPageArray($pid);

            // only the basket items for the pages belonging to this shop shall be used here
            if ($isValidPage) {
                foreach ($basketExt[$uid] as $bextVarLine => $bRow) {
                    if (substr($bextVarLine, -1) == '.') {
                        // this is an additional array which is no basket item
                        if ($conf['whereGiftService']) {
                            $bAddGiftService = true;
                        }
                        continue 1;
                    }

                    $currRow =
                        $this->getItemRow(
                            $row,
                            $bextVarLine,
                            $useArticles,
                            $funcTablename,
                            $basketExt,
                        );
                    $productsArray[$prodCount] = $currRow;
                    $prodCount++;
                }
            }
        }

        if ($bAddGiftService) {
            $where = $conf['whereGiftService'] . ' AND pid IN (' . $pidListObj->getPidlist() . ')' . $viewTableObj->getTableObj()->enableFields();
            $giftServiceArray = $viewTableObj->getWhere($where);
            if (isset($giftServiceArray) && is_array($giftServiceArray)) {
                reset($giftServiceArray);
                $this->giftServiceRow = current($giftServiceArray);
                if (isset($this->giftServiceRow) && is_array($this->giftServiceRow)) {
                    $productsArray[$prodCount++] = $this->giftServiceRow;
                }
            }
        }
        $itemArray = []; // array of the items in the basket
        $maxTax = 0;
        $taxObj = GeneralUtility::makeInstance('tx_ttproducts_field_tax');
        $uidArray = [];
        $categoryQuantity = [];
        $categoryArray = [];
        $enableTaxZero = true; // Die Produkte im Warenkorb werden sofort umgerechnet, damit sie die korrigierten Steuern und Preise enthalten. Hier ist die Steuer 0 erlaubt und darf später nicht mehr durch TAXpercentage überschrieben werden.

        foreach ($productsArray as $k1 => $row) {
            $uid = $row['uid'];
            $taxInfoArray = [];
            $tax =
                $taxObj->getTax(
                    $taxInfoArray,
                    $row,
                    $basketExtra,
                    $this->getRecs(),
                    false
                );
            $row['tax'] = floatval($tax); // Steuer 0 muss durch TAXpercentage überschrieben werden. Mögliche XCLASS und andere Steuern berücksichtigen.

            $calculatedTax =
                $taxObj->getFieldCalculatedValue(
                    $row['tax'],
                    $basketExtra
                );
            if ($calculatedTax !== false) {
                $tax = $calculatedTax;
            }

            if ($tax > $maxTax) {
                $maxTax = $tax;
            }

            $externalRowArray = [];

            if (
                isset($row['ext']) &&
                isset($row['ext']['records']) &&
                is_array($row['ext']['records'])
            ) {
                $externalRowArray = $row['ext']['records'];
                $funcTablename = array_key_last($externalRowArray);
                $lastRowArray = current($externalRowArray);
                reset($externalRowArray);
            }

            $newTax = floatval($tax);
            if ($row['tax'] != $newTax) {
                if (!$priceTaxNotVarying) {
                    $oldTaxFactor = 1 + $row['tax'] / 100;
                    // we need the net price in order to apply another tax
                    $price = $row['price'] / $oldTaxFactor;
                    $newTaxFactor = 1 + $newTax / 100;
                    $row['price'] = $price * $newTaxFactor;
                }
                $row['tax'] = $newTax; // $row['tax'] ausfüllen, weil der Steuersatz bereits ermittelt worden ist.
            }

            $newItem =
                $this->getItem(
                    $mergePrices,
                    $basketExt,
                    $basketExtra,
                    $basketRecs,
                    $row,
                    'useExt',
                    $funcTablename,
                    $externalRowArray, // new Download
                    $enableTaxZero
                );

            $count = $newItem['count'];

            if ($count > 0) {
                $weight = $newItem['weight'];
                $itemArray[$row[$viewTableObj->fieldArray['itemnumber']]][] = $newItem;
                $calculatedArray['count'] += $count;
                if (empty($calculatedArray['weight'])) {
                    $calculatedArray['weight'] = 0;
                }
                $calculatedArray['weight'] += $weight * $count;

                $currentCategory = $row['category'];
                $categoryArray[$currentCategory] = 1;
                if (!isset($categoryQuantity[$currentCategory])) {
                    $categoryQuantity[$currentCategory] = 0;
                }
                $categoryQuantity[$currentCategory] += $count;
            }
            // if reseller is logged in then take 'price2', default is 'price'
            $uidArray[] = $uid;
        }
        $this->setItemArray($itemArray);
        $this->setCategoryQuantity($categoryQuantity);
        $this->setCategoryArray($categoryArray);
        $this->setMaxTax($maxTax);
        $this->setUidArray($uidArray);
        $calculObj->setBaseCalculatedArray($calculatedArray);
    }

    public function setMaxTax($tax): void
    {
        $this->maxTax = $tax;
    }

    public function getMaxTax()
    {
        return $this->maxTax;
    }

    public function getItemArrayFromRow(
        &$tax, // neu
        &$taxInfoArray, // neu
        array $row,
        $basketExt,
        $basketExtra,
        $basketRecs,
        $funcTablename,
        $fetchMode = 'useExt',
        $externalRowArray = [],
        $enableTaxZero = false
    ) {
        $prodFuncTablename = 'tt_products';
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $viewTableObj = $tablesObj->get($prodFuncTablename);
        $result = false;

        if (isset($row) && is_array($row)) {
            $itemArray = [];

            $newItem =
            $this->getItem(
                true, // $mergePrices
                $basketExt,
                $basketExtra,
                $basketRecs,
                $row,
                $fetchMode,
                $taxInfoArray,
                $funcTablename,
                $externalRowArray,
                $enableTaxZero
            );

            if (!empty($newItem)) {
                $tax = $newItem['tax'];
                $count = $newItem['count'];
                $weight = $newItem['weight'];
                $itemArray[$row[$viewTableObj->fieldArray['itemnumber']]][] = $newItem;
                $result = $itemArray;
            }
        }

        return $result;
    }

    // get a virtual basket
    public function getMergedRowFromItemArray(array $itemArray, $basketExtra)
    {
        $calculationField = FieldInterface::PRICE_CALCULATED;
        $row = false;
        if (count($itemArray)) {
            $row = [];
        }

        foreach ($itemArray as $sort => $actItemArray) {
            foreach ($actItemArray as $k1 => $actItem) {
                $row = $actItem['rec'];
                $extArray = $row['ext'] ?? [];
                if (
                    isset($extArray) &&
                    is_array($extArray)
                ) {
                    if (
                        isset($extArray['mergeArticles']) &&
                        is_array($extArray['mergeArticles'])
                    ) {
                        $mergeRow = $extArray['mergeArticles'];
                        $row = $mergeRow;
                        unset($extArray['mergeArticles']);
                        $row['ext'] = $extArray;
                    }
                }

                if (isset($actItem[$calculationField])) {
                    $row[$calculationField] = $actItem[$calculationField];
                }
                break;
            }
            break;
        }

        return $row;
    }

    public function calculate(
        &$itemArray,
        $basketExt,
        $basketExtra,
        $basketRecs,
        $tax, // neu
        $storeCalculation = true, // neu
        $recalculateItems = false
    ): void {
        $cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
        $useArticles = $cnfObj->getUseArticles();
        $calculObj = GeneralUtility::makeInstance('tx_ttproducts_basket_calculate');
        // Die Berechnungen müssen ein 2. Mal gemacht werden, weil es diverse Staffelpreis Rechennmodelle gibt.
        $calculatedArray = [];
        $calculObj->calculate(
            $itemArray,
            $calculatedArray,
            $basketExt,
            $basketExtra,
            $basketRecs,
            tx_ttproducts_control_basket::getFuncTablename(),
            $useArticles,
            floatval(($tax) > $this->getMaxTax() ? floatval(($tax) : $this->getMaxTax(),
            tx_ttproducts_control_basket::getRoundFormat(),
            $recalculateItems
        );

        if ($storeCalculation) {
            $calculObj->setCalculatedArray($calculatedArray);
        }
    }

    public function calculateSums($feUserRecord): void
    {
        $getShopCountryCode = '';
        $recs = tx_ttproducts_control_basket::getRecs();
        $pricefactor = tx_ttproducts_creditpoints_div::getPriceFactor($this->conf);
        $creditpoints = tx_ttproducts_creditpoints_div::getUsedCreditpoints($feUserRecord, $recs);
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $staticTaxObj = $tablesObj->get('static_taxes', false);
        if (
            is_object($staticTaxObj) &&
            $staticTaxObj->isValid()
        ) {
            $getShopCountryCode = $staticTaxObj->getShopCountryCode();
        }

        $calculObj = GeneralUtility::makeInstance('tx_ttproducts_basket_calculate');
        $calculatedArray = $calculObj->getCalculatedArray();
        $calculObj->calculateSums(
            $calculatedArray,
            tx_ttproducts_control_basket::getRoundFormat(),
            $pricefactor,
            $creditpoints,
            $getShopCountryCode
        );
    }

    public function getCalculatedSums()
    {
        $calculObj = GeneralUtility::makeInstance('tx_ttproducts_basket_calculate');
        $calculatedArray = $calculObj->getCalculatedArray();

        return $calculatedArray;
    }

    public function addVoucherSums(): void
    {
        $calculObj = GeneralUtility::makeInstance('tx_ttproducts_basket_calculate');
        $calculObj->addVoucherSums();
    }

    public function getCalculatedArray()
    {
        $calculObj = GeneralUtility::makeInstance('tx_ttproducts_basket_calculate');
        $rc = $calculObj->getCalculatedArray();

        return $rc;
    }
}
