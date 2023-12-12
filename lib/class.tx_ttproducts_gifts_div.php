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
 * view functions
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage tt_products
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;

class tx_ttproducts_gifts_div
{
    /**
     * returns if the product has been put into the basket as a gift.
     *
     * @param	int	 uid of the product
     * @param	int	 variant of the product only size is used now --> TODO
     *
     * @return  array	all gift numbers for this product
     */
    public static function getGiftNumbers($uid, $variant, $basketExt)
    {
        $giftArray = [];

        if ($basketExt['gift']) {
            foreach ($basketExt['gift'] as $giftnumber => $giftItem) {
                if ($giftItem['item'][$uid][$variant]) {
                    $giftArray[] = $giftnumber;
                }
            }
        }

        return $giftArray;
    }

    /**
     * Adds gift markers to a markerArray.
     */
    public static function addGiftMarkers($markerArray, $giftnumber, $code = 'LISTGIFTS', $id = '1')
    {
        $basketExt = tx_ttproducts_control_basket::getBasketExt();

        $markerArray['###GIFTNO###'] = $giftnumber;
        $markerArray['###GIFT_PERSON_NAME###'] = $basketExt['gift'][$giftnumber]['personname'];
        $markerArray['###GIFT_PERSON_EMAIL###'] = $basketExt['gift'][$giftnumber]['personemail'];
        $markerArray['###GIFT_DELIVERY_NAME###'] = $basketExt['gift'][$giftnumber]['deliveryname'];
        $markerArray['###GIFT_DELIVERY_EMAIL###'] = $basketExt['gift'][$giftnumber]['deliveryemail'];
        $markerArray['###GIFT_NOTE###'] = $basketExt['gift'][$giftnumber]['note'];

        $markerArray['###FIELD_ID###'] = TT_PRODUCTS_EXT . '_' . strtolower($code) . '_id_' . $id;
        // here again, because this is here in ITEM_LIST view
        //	  $markerArray['###FIELD_QTY###'] =  '';

        $markerArray['###FIELD_NAME_PERSON_NAME###'] = 'ttp_gift[personname]';
        $markerArray['###FIELD_NAME_PERSON_EMAIL###'] = 'ttp_gift[personemail]';
        $markerArray['###FIELD_NAME_DELIVERY_NAME###'] = 'ttp_gift[deliveryname]';
        $markerArray['###FIELD_NAME_DELIVERY_EMAIL###'] = 'ttp_gift[deliveryemail]';
        $markerArray['###FIELD_NAME_GIFT_NOTE###'] = 'ttp_gift[note]';

        return $markerArray;
    } // addGiftMarkers

    /**
     * Saves the orderRecord and returns the result.
     */
    public static function saveOrderRecord($orderUid, $pid, &$giftBasket)
    {
        $rc = '';

        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $productObj = $tablesObj->get('tt_products');
        foreach ($giftBasket as $giftnumber => $rec) {
            $amount = 0;
            foreach ($rec['item'] as $productid => $product) {
                $row = $productObj->get($productid);
                $articleRows = $productObj->getArticleRows($productid);
                foreach ($product as $variant => $count) {
                    $productObj->variant->modifyRowFromVariant($row, $variant);
                    $articleRow = $productObj->getArticleRow($row, $theCode);
                    if (count($articleRow)) {
                        $amount += intval($articleRow['price']) * $count;
                    } else {
                        $amount += intval($row['price']) * $count;
                    }
                }
            }

            // Saving gift order data
            $insertFields = [
                'pid' => intval($pid),
                'tstamp' => time(),
                'crdate' => time(),
                'deleted' => 0,

                'ordernumber' => $orderUid,
                'personname' => $rec['personname'],
                'personemail' => $rec['personemail'],
                'deliveryname' => $rec['deliveryname'],
                'deliveryemail' => $rec['deliveryemail'],
                'note' => $rec['note'],
                'amount' => $amount,
            ];
            // Saving the gifts order record

            $GLOBALS['TYPO3_DB']->exec_INSERTquery('tt_products_gifts', $insertFields);
            $newId = $GLOBALS['TYPO3_DB']->sql_insert_id();
            $insertFields = [];
            $insertFields['uid_local'] = $newId;
            $variantFields = $productObj->variant->getFieldArray();

            foreach ($rec['item'] as $productid => $product) {
                foreach ($product as $variant => $count) {
                    $row = [];
                    $productObj->variant->modifyRowFromVariant($row, $variant);

                    $query = 'uid_product=\'' . intval($productid) . '\'';
                    foreach ($variantFields as $k => $field) {
                        if ($row[$field]) {
                            $query .= ' AND ' . $field . '=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($row[$field], 'tt_products_articles');
                        }
                    }
                    $articleRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tt_products_articles', $query);

                    if ($articleRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($articleRes)) {
                        $insertFields['uid_foreign'] = $articleRow['uid'];
                        $insertFields['count'] = $count;
                        // Saving the gifts mm order record
                        $GLOBALS['TYPO3_DB']->exec_INSERTquery('tt_products_gifts_articles_mm', $insertFields);
                    }
                }
            }
        }

        return $rc;
    }

    public static function checkRequired($basketExt, $infoViewObj, &$wrongGiftNumber)
    {
        $result = '';
        $wrongGiftNumber = 0;

        if (
            isset($basketExt) &&
            is_array($basketExt) &&
            isset($basketExt['gift']) &&
            is_array($basketExt['gift'])
        ) {
            // RegEx-Check
            $checkFieldsExpr = $infoViewObj->getFieldChecks('gift');

            if ($checkFieldsExpr && is_array($checkFieldsExpr)) {
                foreach ($checkFieldsExpr as $fName => $checkExpr) {
                    foreach ($basketExt['gift'] as $giftnumber => $giftRow) {
                        foreach ($giftRow as $field => $value) {
                            if (
                                strpos($field, $fName) !== false &&
                                preg_match('/' . $checkExpr . '/', $value) == 0
                            ) {
                                $wrongGiftNumber = $giftnumber;
                                $result = $fName;
                                break;
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }

    public static function deleteGiftNumber($giftnumber)
    {
        $giftArray = [];
        $basketExt = tx_ttproducts_control_basket::getBasketExt();

        if (
            is_array($basketExt) &&
            isset($basketExt['gift']) &&
            is_array($basketExt['gift']) &&
            isset($basketExt['gift'][$giftnumber])
        ) {
            $count = count($basketExt['gift']);
            $itemArray = $basketExt['gift'][$giftnumber]['item'];

            foreach ($itemArray as $uid => $nextData) {
                foreach ($nextData as $allVariants => $count) {
                    unset($basketExt[$uid][$allVariants]);
                    if (!$basketExt[$uid]) {
                        unset($basketExt[$uid]);
                    }
                }
            }

            unset($basketExt['gift'][$giftnumber]);
            if ($count == 1) {
                unset($basketExt['gift']);
            }
            tx_ttproducts_control_basket::storeBasketExt($basketExt);
        }
    }

    public static function isGift($row, $whereGift)
    {
        $result = false;

        if (strlen($whereGift)) {
            $result = tx_ttproducts_sql::isValid($row, $whereGift);
        }

        return $result;
    }

    public static function useTaxZero($row, $giftConf, $whereGift)
    {
        $result = false;
        if (
            self::isGift($row, $whereGift) &&
            isset($giftConf) &&
            is_array($giftConf) &&
            isset($giftConf['TAXpercentage']) &&
            doubleval($giftConf['TAXpercentage']) == '0'
        ) {
            $result = true;
        }

        return $result;
    }
}
