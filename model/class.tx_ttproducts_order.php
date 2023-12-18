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
 * order functions
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage tt_products
 */
use JambageCom\Div2007\Api\Frontend;
use JambageCom\Div2007\Base\TranslationBase;
use JambageCom\Div2007\Utility\CompatibilityUtility;
use JambageCom\Div2007\Utility\SystemUtility;
use JambageCom\Div2007\Utility\TableUtility;
use JambageCom\TtProducts\Api\BasketApi;
use JambageCom\TtProducts\Api\Localization;
use JambageCom\TtProducts\Api\PaymentApi;
use JambageCom\TtProducts\Api\PaymentShippingHandling;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class tx_ttproducts_order extends tx_ttproducts_table_base
{
    private $currentArray = [];

    // **************************
    // ORDER related functions
    // **************************
    /**
     * Create a new order record.
     *
     * This creates a new order-record on the page with pid PID_sys_products_orders. That page must exist!
     * Should be called only internally by eg. $order->getBlankUid, that first checks if a blank record is already created.
     */
    public function create()
    {
        $newId = 0;
        $pid = intval($this->conf['PID_sys_products_orders']);
        if (!$pid) {
            $pid = intval($GLOBALS['TSFE']->id);
        }

        if ($GLOBALS['TSFE']->sys_page->getPage_noCheck($pid)) {
            $advanceUid = 0;

            if (
                $this->conf['advanceOrderNumberWithInteger'] ||
                $this->conf['alwaysAdvanceOrderNumber']
            ) {
                $res =
                    $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                        'uid',
                        'sys_products_orders',
                        '',
                        '',
                        'uid DESC',
                        '1'
                    );
                [$prevUid] = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
                $GLOBALS['TYPO3_DB']->sql_free_result($res);

                if ($this->conf['advanceOrderNumberWithInteger']) {
                    $rndParts = explode(',', $this->conf['advanceOrderNumberWithInteger']);
                    $randomValue = random_int(intval($rndParts[0]), intval($rndParts[1]));
                    $advanceUid = $prevUid + MathUtility::forceIntegerInRange($randomValue, 1);
                } else {
                    $advanceUid = $prevUid + 1;
                }
            }

            $time = time();
            if (!empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['serverTimeZone'])) {
                $time += ($GLOBALS['TYPO3_CONF_VARS']['SYS']['serverTimeZone'] * 3600);
            }
            $insertFields = [
                'pid' => intval($pid),
                'tstamp' => $time,
                'crdate' => $time,
                'deleted' => 0,
                'hidden' => 1,
            ];
            if ($advanceUid > 0) {
                $insertFields['uid'] = intval($advanceUid);
            }

            $GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_products_orders', $insertFields);
            $newId = $GLOBALS['TYPO3_DB']->sql_insert_id();

            if (
                !$newId &&
                (
                    get_class($GLOBALS['TYPO3_DB']->getDatabaseHandle()) == 'mysqli'
                )
            ) {
                $rowArray =
                    $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
                        'uid',
                        'sys_products_orders',
                        'uid=LAST_INSERT_ID()',
                        ''
                    );
                if (
                    isset($rowArray) &&
                    is_array($rowArray) &&
                    isset($rowArray['0']) &&
                    is_array($rowArray['0'])
                ) {
                    $newId = $rowArray['0']['uid'];
                }
            }
        }

        return $newId;
    } // create

    /**
     * Returns a blank order uid. If there was no order id already, a new one is created.
     *
     * Blank orders are marked hidden and with status=0 initialy. Blank orders are not necessarily finalized because users may abort instead of buying.
     * A finalized order is marked 'not hidden' and with status=1.
     * Returns this uid which is a blank order record uid.
     */
    public function getBlankUid(&$orderArray)
    {
        $res = false;
        // an new orderUid has been created always because also payment systems can be used which do not accept a duplicate order id
        $orderArray = tx_ttproducts_control_basket::getStoredOrder();
        $orderUid = 0;

        if (isset($orderArray['uid'])) {
            $orderUid = intval($orderArray['uid']);
        }

        if (
            $orderUid &&
            !$this->conf['alwaysAdvanceOrderNumber']
        ) {
            $res =
                $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                    'uid',
                    'sys_products_orders',
                    'uid=' . $orderUid . ' AND hidden AND NOT status'
                );	// Checks if record exists, is marked hidden (all blank orders are hidden by default) and is not finished.
        }

        if (
            !$orderUid ||
            $this->conf['alwaysAdvanceOrderNumber'] ||
            $res !== false && !$GLOBALS['TYPO3_DB']->sql_num_rows($res)
        ) {
            $orderUid = $this->create();
            $orderArray = [];
            $orderArray['uid'] = $orderUid;
            $orderArray['crdate'] = time();
            if (!empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['serverTimeZone'])) {
                $orderArray['crdate'] += ($GLOBALS['TYPO3_CONF_VARS']['SYS']['serverTimeZone'] * 3600);
            }
            $orderArray['tracking_code'] =
                $this->getNumber($orderUid) . '-' .
                strtolower(substr(md5(uniqid(time())), 0, 6));
            tx_ttproducts_control_basket::store('order', $orderArray);
            $this->currentArray = $orderArray;
        }

        if ($res !== false) {
            $GLOBALS['TYPO3_DB']->sql_free_result($res);
        }

        return $orderUid;
    } // getBlankUid

    public function setCurrentData($key, $value): void
    {
        $this->currentArray[$key] = $value;
    }

    public function getCurrentArray()
    {
        $result = $this->currentArray;

        return $result;
    }

    public function setUid($uid): void
    {
        $this->setCurrentData('uid', $uid);
    }

    public function getUid($orderArray = [])
    {
        if (empty($orderArray)) {
            $orderArray = $this->getCurrentArray();
        }

        $result = $orderArray['uid'] ?? 0;

        return $result;
    }

    public function clearUid(): void
    {
        $this->setCurrentData('uid', '');
        tx_ttproducts_control_basket::store('order', []);
    }

    public function updateRecord($orderUid, array $fieldsArray): void
    {
        // Saving the order record
        $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
            'sys_products_orders',
            'uid=' . intval($orderUid),
            $fieldsArray
        );
    }

    /**
     * Returns the order record if $orderUid.
     * If $tracking is set, then the order with the tracking number is fetched instead.
     */
    public function getRecord($orderUid, $tracking = '')
    {
        if (
            empty($tracking) &&
            !$orderUid
        ) {
            return false;
        }

        $where = ($tracking ? 'tracking_code=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($tracking, 'sys_products_orders') : 'uid=' . intval($orderUid));

        $enableFields = TableUtility::enableFields('sys_products_orders');

        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            '*',
            'sys_products_orders',
            $where . $enableFields
        );
        $result = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
        $GLOBALS['TYPO3_DB']->sql_free_result($res);

        return $result;
    } // getRecord

    /**
     * This returns the order-number (opposed to the order's uid) for display in the shop, confirmation notes and so on.
     * Basically this prefixes the .orderNumberPrefix, if any.
     */
    public function getNumber($orderUid)
    {
        $orderNumberPrefix = substr($this->conf['orderNumberPrefix'], 0, 30);
        if (($position = strpos($orderNumberPrefix, '%')) !== false) {
            $orderDate = date(substr($orderNumberPrefix, $position + 1));
            $orderNumberPrefix = substr($orderNumberPrefix, 0, $position) . $orderDate;
        }

        $result = $orderNumberPrefix . $orderUid;

        return $result;
    } // getNumber

    /**
     * Saves the order record and returns the result.
     */
    public function putRecord(
        $orderUid,
        $orderArray,
        $itemArray,
        $calculatedArray,
        $cardUid,
        $accountUid,
        $email_notify,
        $payment,
        $shipping,
        $amount,
        $orderConfirmationHTML,
        $infoViewOb,
        TranslationBase $languageObj,
        $status,
        $basketExtra,
        $giftcode,
        $giftServiceArticleArray,
        $vouchercode,
        $usedCreditpoints,
        $voucherCount,
        $bOnlyStatusChange
    ): void {
        $billingInfo = $infoViewOb->infoArray['billing'];
        $deliveryInfo = $infoViewOb->infoArray['delivery'];
        $feusers_uid = 0;
        if (is_array($billingInfo) && isset($billingInfo['feusers_uid'])) {
            $feusers_uid = $billingInfo['feusers_uid'];
        }
        $orderRow = [];

        if (
            !isset($deliveryInfo) ||
            count($deliveryInfo) < 2
        ) {
            $deliveryInfo = $billingInfo;
        }

        $fieldsArray = [];

        $tablename = $this->getTablename();
        $fieldsArray['hidden'] = 1;
        $excludeFieldArray = [];

        if (
            isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['exclude']) &&
            is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['exclude']) &&
            isset($excludeArray[$tablename])
        ) {
            $excludeFieldArray = $excludeArray[$tablename];
        }

        if ($status > 0) {
            $fieldsArray['hidden'] = 0;

            if ($bOnlyStatusChange) {
                $orderRow =
                    $this->get(
                        $orderUid,
                        0,
                        false,
                        '',
                        '',
                        '',
                        '',
                        'orderData,hidden,email,tracking_code',
                        false,
                        '',
                        false
                    );

                if (
                    empty($orderRow) ||
                    !is_array($orderRow) ||
                    isset($orderRow['tracking_code']) && $orderRow['tracking_code'] == '' ||
                    isset($orderRow['email']) && $orderRow['email'] == ''
                ) {
                    $bOnlyStatusChange = false;
                }
            }
        } else {
            $bOnlyStatusChange = false;
        }

        if (!$bOnlyStatusChange) {
            if (
                empty($deliveryInfo['name']) &&
                isset($deliveryInfo['first_name']) &&
                isset($deliveryInfo['last_name'])
            ) {
                $deliveryInfo['name'] = $deliveryInfo['last_name'] . ' ' . $deliveryInfo['first_name'];
            }

            $dateBirth = '';
            if (!empty($deliveryInfo['date_of_birth'])) {
                $dateBirth = tx_ttproducts_sql::convertDate($deliveryInfo['date_of_birth']);
            }

            $addressFields = ['name', 'first_name', 'last_name', 'company', 'salutation', 'address', 'house_no', 'zip', 'city', 'country', 'telephone', 'fax', 'email', 'email_notify', 'business_partner', 'organisation_form'];

            if (isset($billingInfo) && is_array($billingInfo)) {
                foreach ($billingInfo as $field => $value) {
                    if (
                        $value &&
                        !in_array($field, $addressFields) &&
                        isset($GLOBALS['TCA']['sys_products_orders']['columns'][$field])
                    ) {
                        $fieldsArray[$field] = $value;
                    }
                }
            }

            if (isset($deliveryInfo) && is_array($deliveryInfo)) {
                foreach ($deliveryInfo as $field => $value) {
                    if (
                        $value &&
                        isset($GLOBALS['TCA']['sys_products_orders']['columns'][$field])
                    ) {
                        $fieldsArray[$field] = $value;
                    }
                }
            }

            $fieldsArray['email_notify'] = $email_notify;

            // can be changed after order is set.
            $fieldsArray['payment'] = $payment;
            $fieldsArray['shipping'] = $shipping;
            $fieldsArray['amount'] = $amount;

            $fieldsArray['note'] = $deliveryInfo['note'] ?? null;
            $fieldsArray['date_of_birth'] = $dateBirth ?? null;
            $fieldsArray['radio1'] = $deliveryInfo['radio1'] ?? null;

            if (
                isset($giftServiceArticleArray) &&
                is_array($giftServiceArticleArray) &&
                !empty($deliveryInfo['giftservice'])
            ) {
                $fieldsArray['giftservice'] = $deliveryInfo['giftservice'] . '||' . implode(',', $giftServiceArticleArray);
            }
            if (!empty($deliveryInfo['foundby'])) {
                $fieldsArray['foundby'] = $deliveryInfo['foundby'];
            }
            $fieldsArray['client_ip'] = GeneralUtility::getIndpEnv('REMOTE_ADDR');
            $fieldsArray['cc_uid'] = intval($cardUid);
            $fieldsArray['ac_uid'] = intval($accountUid);
            $fieldsArray['giftcode'] = $giftcode;

            $api =
                GeneralUtility::makeInstance(Frontend::class);
            $sys_language_uid = $api->getLanguageId();
            $fieldsArray['sys_language_uid'] = $sys_language_uid;

            if (!empty($billingInfo['tt_products_vat'])) {
                $fieldsArray['vat_id'] = $billingInfo['tt_products_vat'];

                $taxPercentage = PaymentShippingHandling::getReplaceTaxPercentage($basketExtra);
                if (doubleval($taxPercentage) == 0) {
                    $fieldsArray['tax_mode'] = 1;
                }
            }

            $fieldsArrayFeUsers = [];
            $uid_voucher = ''; // define it here

            if (!empty($dateBirth)) {
                $fieldsArrayFeUsers['date_of_birth'] = $dateBirth;
            }

            if ($status == 1 && !empty($this->conf['creditpoints.']) && $usedCreditpoints) {
                // Added Els: update fe_user with amount of creditpoints (= exisitng amount - used_creditpoints - spended_creditpoints + saved_creditpoints
                $fieldsArrayFeUsers['tt_products_creditpoints'] =
                    floatval(
                        $GLOBALS['TSFE']->fe_user->user['tt_products_creditpoints'] -
                        $usedCreditpoints
                        // + GeneralUtility::_GP('creditpoints_saved')
                    );
                if ($fieldsArrayFeUsers['tt_products_creditpoints'] < 0) {
                    $fieldsArrayFeUsers['tt_products_creditpoints'] = 0;
                }
            }

            if ($status == 1 && $vouchercode != '') {
                // first check if vouchercode exist and is not their own vouchercode
                $res =
                    $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                        'uid',
                        'fe_users',
                        'username=' .
                            $GLOBALS['TYPO3_DB']->fullQuoteStr($vouchercode, 'fe_users')
                    );

                if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
                    $uid_voucher = $row['uid'];
                }
                $GLOBALS['TYPO3_DB']->sql_free_result($res);

                if (
                    ($uid_voucher != '') &&
                    !empty($deliveryInfo['feusers_uid']) &&
                    ($deliveryInfo['feusers_uid'] != $uid_voucher)
                ) {
                    $fieldsArrayFeUsers['tt_products_vouchercode'] = $vouchercode;
                }
            }

            if ($status == 1 && !empty($deliveryInfo['feusers_uid'])) {
                // Added Els: update user from vouchercode with 5 credits
                tx_ttproducts_creditpoints_div::addCreditPoints(
                    $vouchercode,
                    $this->conf['voucher.']['price']
                );
            }

            foreach ($excludeFieldArray as $field) {
                if (isset($fieldsArrayFeUsers[$field])) {
                    unset($fieldsArrayFeUsers[$field]);
                }
            }

            if (
                CompatibilityUtility::isLoggedIn() &&
                $GLOBALS['TSFE']->fe_user->user['uid'] &&
                count($fieldsArrayFeUsers)
            ) {
                $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
                    'fe_users',
                    'uid=' . intval($GLOBALS['TSFE']->fe_user->user['uid']),
                    $fieldsArrayFeUsers
                );
            }

            $storeItemArray = [];
            $storeItemArray['tt_products'] = $itemArray;

            // Order Data serialized
            $fieldsArray['orderData'] = serialize([
                'html_output' => $orderConfirmationHTML,
                'delivery' => $deliveryInfo,
                'billing' => $billingInfo,
                'itemArray' => $storeItemArray,
                'calculatedArray' => $calculatedArray,
                'version' => $this->config['version'],
            ]);

            // Setting tstamp, deleted and tracking code
            $fieldsArray['tstamp'] = time();
            if (!empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['serverTimeZone'])) {
                $fieldsArray['tstamp'] += ($GLOBALS['TYPO3_CONF_VARS']['SYS']['serverTimeZone'] * 3600);
            }
            $fieldsArray['deleted'] = 0;
            $fieldsArray['tracking_code'] = $orderArray['tracking_code'];
            $fieldsArray['agb'] = intval($billingInfo['agb']);
        }

        if ($status == 1) {
            $payMode =
                PaymentApi::getPayMode(
                    $languageObj,
                    $basketExtra
                );

            if ($payMode) {
                $status = 13;
                $fieldsArray['pay_mode'] = $payMode;
            }

            if ( // save the new order HTML if present for status = 1
                $bOnlyStatusChange &&
                $orderConfirmationHTML != '' &&
                !empty($orderRow)
            ) {
                // Saving the order record
                $orderData = $this->getOrderData($orderRow);
                $orderData['html_output'] = $orderConfirmationHTML;

                $fieldsArray['orderData'] = serialize($orderData);
            }
        }

        if ($voucherCount) {
            $fieldsArray['gained_voucher'] = $voucherCount;
        }

        if (isset($orderArray['bill_no'])) {
            $fieldsArray['bill_no'] = $orderArray['bill_no'];
        }

        $fieldsArray['feusers_uid'] = intval($feusers_uid);
        $fieldsArray['status'] = intval($status);	// If 1, then this means, "Order confirmed on website, next step: confirm from shop that order is received"

        // Default status_log entry
        $status_log = [];
        $status_log['0'] = [
            'time' => time(),
            'info' => $this->conf['statusCodes.'][$status] ?? '',
            'status' => $status,
            'comment' => $deliveryInfo['note'] ?? '',
        ];
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['serverTimeZone'])) {
            $status_log['0']['time'] += ($GLOBALS['TYPO3_CONF_VARS']['SYS']['serverTimeZone'] * 3600);
        }
        $fieldsArray['status_log'] = serialize($status_log);

        foreach ($excludeFieldArray as $field) {
            if (isset($fieldsArray[$field])) {
                unset($fieldsArray[$field]);
            }
        }

        $fieldsArray = array_filter($fieldsArray, function($a) {
            return trim($a) !== '';
        });

        // Saving the order record
        $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
            'sys_products_orders',
            'uid=' . intval($orderUid),
            $fieldsArray
        );
    } // putRecord

    /**
     * Creates M-M relations for the products with tt_products and maybe also the tt_products_articles table.
     * Isn't really used yet, but later will be used to display stock-status by looking up how many items are
     * already ordered.
     */
    public function createMM(
        $orderUid,
        $itemArray,
        $theCode
    ): void {
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $editFieldArray = [];
        $selectableVariantFieldArray = [];
        $orderTablename = 'sys_products_orders';
        $falFieldname = 'fal_uid';
        $variantSeparator = '---';

        if ($this->conf['useArticles'] != 2) {
            $productTable = $tablesObj->get('tt_products', false);
            $productTablename = $productTable->getTablename();
            $editFieldArray = $productTable->editVariant->getFieldArray();
            $selectableVariantFieldArray = $productTable->variant->getSelectableFieldArray();
            $variantSeparator = $productTable->getVariant()->getImplodeSeparator();
        } else {
            $productTablename = '';
        }

        if ($this->conf['useArticles'] > 0) {
            $articleTable = $tablesObj->get('tt_products_articles', false);
            $articleTablename = $articleTable->getTablename();
        } else {
            $articleTablename = '';
        }

        // First: delete any existing. This should never be the case.
        $where = 'uid_local=' . intval($orderUid);
        $GLOBALS['TYPO3_DB']->exec_DELETEquery(
            'sys_products_orders_mm_tt_products',
            $where
        );

        $where = 'uid_foreign=' . intval($orderUid) . ' AND `tablenames`="sys_products_orders"';
        $GLOBALS['TYPO3_DB']->exec_DELETEquery(
            'sys_file_reference',
            $where
        );

        $productCount = 0;
        $falCount = 0;

        if (isset($itemArray) && is_array($itemArray)) {
            // loop over all items in the basket indexed by a sorting text
            foreach ($itemArray as $sort => $actItemArray) {
                foreach ($actItemArray as $k1 => $actItem) {
                    $row = $actItem['rec'];
                    $extArray = $row['ext'];
                    $pid = intval($row['pid']);
                    $externalUidArray = [];

                    if (!tx_ttproducts_control_basket::getPidListObj()->getPageArray($pid)) {
                        // product belongs to another basket
                        continue;
                    }

                    $variantArray = [];
                    $editVariantArray = [];

                    // fill in the variants and edit_variants mediumtext NOT NULL,
                    foreach ($selectableVariantFieldArray as $variant) {
                        if (isset($row[$variant]) && $row[$variant] != '') {
                            $variantArray[] = $variant . ':' . $row[$variant];
                        }
                    }
                    foreach ($editFieldArray as $editVariant) {
                        if (isset($row[$editVariant]) && $row[$editVariant] != '') {
                            $editVariantArray[] = substr($editVariant, strlen('edit_')) . ':' . $row[$editVariant];
                        }
                    }

                    $variants = implode($variantSeparator, $variantArray);
                    $editVariants = implode($variantSeparator, $editVariantArray);
                    $falVariants = '';

                    if (
                        isset($extArray) &&
                        isset($extArray['records']) &&
                        is_array($extArray['records'])
                    ) {
                        $newTitleArray = [];
                        $externalRowArray = $extArray['records'];
                        BasketApi::getRecordvariantAndPriceFromRows(
                            $falVariants,
                            $dummyPrice,
                            $externalUidArray,
                            $externalRowArray
                        );

                        if (($position = strpos($falVariants, '|records:')) === 0) {
                            $falVariants = substr($falVariants, strlen('|records:'));
                        }
                    }

                    $time = time();
                    if (!empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['serverTimeZone'])) {
                        $time += ($GLOBALS['TYPO3_CONF_VARS']['SYS']['serverTimeZone'] * 3600);
                    }

                    $pid = intval($this->conf['PID_sys_products_orders']);
                    if (!$pid) {
                        $pid = intval($GLOBALS['TSFE']->id);
                    }

                    $insertFields = [
                        'pid' => intval($pid),
                        'tstamp' => $time,
                        'crdate' => $time,
                        'uid_local' => intval($orderUid),
                        'sys_products_orders_qty' => intval($actItem['count']),
                        'variants' => $variants,
                        'edit_variants' => $editVariants,
                        'fal_variants' => $falVariants,
                        'uid_foreign' => intval($actItem['rec']['uid']),
                        'tablenames' => $productTablename . ',' . $articleTablename,
                    ];

                    if (
                        $this->conf['useArticles'] == 1 ||
                        $this->conf['useArticles'] == 3
                    ) {
                        // get the article uid with these colors, sizes and gradings
                        $articleRow =
                            $productTable->getArticleRow(
                                $row,
                                $theCode
                            );

                        if ($articleRow) {
                            $insertFields['tt_products_articles_uid'] = intval($articleRow['uid']);
                        }
                    }
                    $GLOBALS['TYPO3_DB']->exec_INSERTquery(
                        'sys_products_orders_mm_tt_products',
                        $insertFields
                    );
                    $productCount++;
                }
            }
        }

        $fieldsArray = [
            'product_uid' => intval($productCount),
            'fal_uid' => intval($falCount),
        ];

        // Saving the order record
        $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
            'sys_products_orders',
            'uid=' . intval($orderUid),
            $fieldsArray
        );
    }

    public function getOrderData($row)
    {
        // initialize order data.
        $orderData = unserialize($row['orderData']);
        if ($orderData === false) {
            $orderData =
                SystemUtility::unserialize(
                    $row['orderData'],
                    false
                );
        }

        return $orderData;
    }

    /**
     * Fetches the basket itemArray from the order's serial data.
     */
    public function getItemArray(
        $row,
        &$calculatedArray,
        &$infoArray
    ) {
        $orderData = $this->getOrderData($row);

        $tmp = $orderData['itemArray'];
        $version = $orderData['version'];

        if (version_compare($version, '2.5.0', '>=') && is_array($tmp)) {
            $tableName = key($tmp);
            $itemArray = current($tmp);
        } else {
            $itemArray = (is_array($tmp) ? $tmp : []);
        }

        $tmp = $orderData['calculatedArray'];
        $calculatedArray = ($tmp ?: []);
        $infoArray = [];
        $infoArray['billing'] = $orderData['billing'];
        $infoArray['delivery'] = $orderData['delivery'];

        // overwrite with the most recent email address
        $infoArray['billing']['email'] = $row['email'];

        return $itemArray;
    }

    /**
     * Sets the user order in dummy order record.
     */
    public function putData(
        $orderUid,
        $orderArray,
        $itemArray,
        $orderHTML,
        $status,
        $basketExtra,
        $calculatedArray,
        $giftcode,
        $giftServiceArticleArray,
        $vouchercode,
        $usedCreditpoints,
        $voucherCount,
        $bOnlyStatusChange
    ) {
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $voucherObj = $tablesObj->get('voucher');

        if (
            is_object($voucherObj) &&
            $status == 1 &&
            $voucherObj->isEnabled()
        ) {
            $voucherObj->delete();
        }

        // get credit card info
        $card = $tablesObj->get('sys_products_cards');
        if (is_object($card)) {
            $cardUid = $card->getUid();
        }

        // get bank account info
        $account = $tablesObj->get('sys_products_accounts');
        if (is_object($account)) {
            $accountUid = $account->getUid();
        }

        $languageObj = GeneralUtility::makeInstance(Localization::class);
        $infoViewOb = GeneralUtility::makeInstance('tx_ttproducts_info_view');
        if (
            $infoViewOb->needsInit() ||
            $languageObj->getExtensionKey() != TT_PRODUCTS_EXT
        ) {
            debug('internal error in tt_products (putData)'); // keep this
            echo 'internal error in tt_products (putData)';

            return;
        }

        $usedCreditpoints = 0;
        if (
            isset($_REQUEST['recs']) &&
            is_array($_REQUEST['recs']) &&
            isset($_REQUEST['recs']['tt_products']) &&
            is_array($_REQUEST['recs']['tt_products'])
        ) {
            $usedCreditpoints = floatval($_REQUEST['recs']['tt_products']['creditpoints']);
        }
        $result = $this->putRecord(
            $orderUid,
            $orderArray,
            $itemArray,
            $calculatedArray,
            $cardUid,
            $accountUid,
            $this->conf['email_notify_default'] ?? '',	// Email notification is set here. Default email address is delivery email contact
            ($basketExtra['payment'][0] ?? '') . ': ' . ($basketExtra['payment.']['title'] ?? ''),
            ($basketExtra['shipping'][0] ?? '') . ': ' . ($basketExtra['shipping.']['title'] ?? ''),
            $calculatedArray['priceTax']['total']['ALL'],
            $orderHTML,
            $infoViewOb,
            $languageObj,
            $status,
            $basketExtra,
            $giftcode,
            $giftServiceArticleArray,
            $vouchercode,
            $usedCreditpoints,
            $voucherCount,
            $bOnlyStatusChange
        );

        return $result;
    }

    public function getPid()
    {
        $result = $this->conf['PID_sys_products_orders'];

        return $result;
    }

    protected function fillVariant(&$row, $variant, $variantSplitSeparator, $prefix = '')
    {
        if (isset($variant)) {
            $variantArray =
                preg_split(
                    '/[\h]*' . $variantSplitSeparator . '[\h]*/',
                    $variant,
                    -1,
                    PREG_SPLIT_NO_EMPTY
                );

            foreach ($variantArray as $variantPart) {
                $parts = explode(':', $variantPart);

                $field = $prefix . $parts['0'];
                $value = '';
                if (isset($parts['1'])) {
                    $value = $parts['1'];
                }
                $row[$field] = $value;
            }
        }
    }

    public static function getFal(
        &$orderedDownloadUid,
        $downloadUid,
        array $orderRow
    ) {
        $result = false;
        $orderedDownloadUid = 0;

        if (
            isset($orderRow['fal_variants']) &&
            $orderRow['fal_variants'] != ''
        ) {
            $source = 'dl=';
            if ($downloadUid) {
                $source .= $downloadUid . tx_ttproducts_variant_int::EXTERNAL_QUANTITY_SEPARATOR;
            }
            $position = strpos($orderRow['fal_variants'], $source);

            if ($position === 0) {
                $positionFal = strpos($orderRow['fal_variants'], tx_ttproducts_variant_int::EXTERNAL_QUANTITY_SEPARATOR . 'fal=');
                $orderedDownloadUid = substr($orderRow['fal_variants'], strlen($source), $positionFal - strlen($source));
                $falUid = substr($orderRow['fal_variants'], $positionFal + strlen(tx_ttproducts_variant_int::EXTERNAL_QUANTITY_SEPARATOR . 'fal='));
                $result = $falUid;
            }
        }

        return $result;
    }

    public static function getUidFromMultiOrderArray(
        &$orderedDownloadUid,
        $falRow,
        $multiOrderArray
    ) {
        $result = 0;
        if (
            isset($multiOrderArray) &&
            is_array($multiOrderArray) &&
            count($multiOrderArray)
        ) {
            $currentOrderUid = 0;
            foreach ($multiOrderArray as $orderRow) {
                $orderedDownloadUid = 0;
                $fal = self::getFal(
                    $orderedDownloadUid,
                    $downloadUid,
                    $orderRow
                );

                if ($fal == $falRow['uid']) {
                    $result = $orderRow['uid'];
                    break;
                }
            }
        }

        return $result;
    }

    public function getOrderedProducts(
        $from,
        $uids,
        $where,
        $orderBy,
        $whereProducts,
        $onlyProductsWithFalOrders,
        $pid_list,
        &$productRowArray,
        &$multiOrderArray
    ) {
        $productRowArray = [];
        $multiOrderArray = [];

        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $productObj = $tablesObj->get('tt_products', false);
        $falObj = $tablesObj->get('sys_file_reference', false);
        $variantSeparator = $productObj->variant->getSplitSeparator();
        $local_cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);

        $tablename = $this->getTablename();

        /*
        SELECT
        po.uid as order_uid,
        po.crdate as order_date,
        pa.sys_products_orders_qty AS quantity,
        p.uid AS product_uid

        FROM sys_products_orders po LEFT JOIN
        sys_products_orders_mm_tt_products pa ON
        po.uid = pa.uid_local INNER JOIN tt_products p ON
        pa.uid_foreign = p.uid
        */

        $alias = $this->getTableObj()->getAlias();
        $productAlias = $productObj->getTableObj()->getAlias();
        $productAliasPostfix1 = '1';
        $productAlias1 = $productAlias . $productAliasPostfix1;

        $selectConf = [];
        if ($uids != '') {
            $selectConf['uidInList'] = $uids;
        }
        if ($pid_list != '') {
            $selectConf['pidInList'] = $pid_list;
        }

        $selectConf['selectFields'] =
            $alias . '.uid as uid, ' . $alias . '.email as email, ' . $alias . '.feusers_uid as feusers_uid, ' .
            $alias . '.crdate as crdate, ' . $alias . '.tracking_code as tracking_code, ' .
            'pa.sys_products_orders_qty AS quantity, pa.variants AS variants, ' .
            'pa.edit_variants AS edit_variants, pa.fal_variants AS fal_variants, ' . $productAlias1 . '.uid AS product_uid';

        if ($whereProducts != '') {
            $where .= ' AND ' .
                $productObj->getTableObj()->transformWhere(
                    $whereProducts,
                    $productAliasPostfix1
                );
        }
        $selectConf['where'] = $where;
        $selectConf['from'] = $from;
        $selectConf['leftjoin'] =
            'sys_products_orders_mm_tt_products pa ON ' . $alias . '.uid = pa.uid_local INNER JOIN tt_products ' . $productAlias1 . ' ON pa.uid_foreign = ' . $productAlias1 . '.uid';
        if ($orderBy != '') {
            $selectConf['orderBy'] = $orderBy;
        }
        $queryParts = $this->getTableObj()->getQueryConf(
            $local_cObj,
            $tablename,
            $selectConf,
            true
        );

        $res = $this->getTableObj()->exec_SELECT_queryArray($queryParts);
        while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
            $multiOrderArray[] = $row;
            $productRow = $productObj->get($row['product_uid']);

            if (!empty($row['edit_variants'])) {
                $this->fillVariant(
                    $productRow,
                    $row['edit_variants'],
                    $variantSeparator,
                    'edit_'
                );
            }

            if (!empty($row['variants'])) {
                $this->fillVariant(
                    $productRow,
                    $row['variants'],
                    $variantSeparator
                );
            }
            $addProduct = true;

            if (
                $onlyProductsWithFalOrders
            ) {
                $fal = self::getFal(
                    $tmp,
                    0,
                    $row
                );
                if (!$fal) {
                    $addProduct = false;
                }
            }

            if ($addProduct) {
                $productRowArray[] = $productRow;
            }
        }

        return $productRowArray;
    }

    public function getGainedProducts(
        $from,
        $where,
        $orderBy,
        $whereProducts,
        $onlyProductsWithFalOrders,
        $pid_list,
        &$productRowArray,
        &$multiOrderArray
    ): void {
        $multiOrderArray = [];
        $productRowArray = [];

        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $productObj = $tablesObj->get('tt_products', false);
        $local_cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $tablename = $this->getTablename();
        $variantSeparator = $productObj->variant->getSplitSeparator();

        $alias = $this->getTableObj()->getAlias();
        $productAlias = $productObj->getTableObj()->getAlias();
        $productAliasPostfix1 = '1';
        $productAlias1 = $productAlias . $productAliasPostfix1;

        $selectConf = [];
        if ($pid_list != '') {
            $selectConf['pidInList'] = $pid_list;
        }
        $selectConf['selectFields'] =
            $alias . '.uid as uid, ' . $alias . '.email as email, ' .
            $alias . '.crdate as crdate, ' . $alias . '.tracking_code as tracking_code, ' .
            'gp.quantity AS quantity, gp.variants AS variants, ' .
            'gp.edit_variants AS edit_variants, ' . $productAlias1 . '.uid AS product_uid';

        // 		$selectConf['selectFields'] =
        // 			$alias . '.uid as order_uid, ' . $alias . '.email as email, ' . $productAlias1 . '.uid AS product_uid';

        if ($whereProducts != '') {
            $where .= ' AND ' . $productObj->getTableObj()->transformWhere($whereProducts, $productAliasPostfix1);
        }
        $selectConf['where'] = $where;
        $selectConf['from'] = $from;
        $selectConf['leftjoin'] =
            'sys_products_orders_mm_gained_tt_products gp ON ' . $alias . '.uid = gp.uid_local INNER JOIN tt_products ' . $productAlias1 . ' ON gp.uid_foreign = ' . $productAlias1 . '.uid';
        if ($orderBy != '') {
            $selectConf['orderBy'] = $orderBy;
        }

        $queryParts = $this->getTableObj()->getQueryConf(
            $local_cObj,
            $tablename,
            $selectConf,
            true
        );

        $res = $this->getTableObj()->exec_SELECT_queryArray($queryParts);
        while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
            // 			$orderArray = [];
            // 			$orderArray['orderTrackingNo'] = $row['orderTrackingNo'];
            // 			$orderArray['orderUid'] = $row['order_uid'];
            // 			$orderArray['orderDate'] = $row['order_date'];
            // 			$multiOrderArray[] = $orderArray;

            $multiOrderArray[] = $row;

            $productRow = [];
            $productRow['uid'] = $row['product_uid'];

            if (!empty($row['edit_variants'])) {
                $this->fillVariant(
                    $productRow,
                    $row['edit_variants'],
                    $variantSeparator,
                    'edit' . tx_ttproducts_variant::$externalTableSeparator
                );
            }

            if (!empty($row['variants'])) {
                $this->fillVariant(
                    $productRow,
                    $row['variants'],
                    $variantSeparator
                );
            }

            $productRowArray[] = $productRow;
        }
    }

    public function getOrderedAndGainedProducts(
        $from,
        $where,
        $orderBy,
        $whereProducts,
        $onlyProductsWithFalOrders,
        $pid_list,
        &$productRowArray,
        &$multiOrderArray
    ): void {
        $productRowArray = [];
        $multiOrderArray = [];

        $this->getOrderedProducts(
            $from,
            '',
            $where,
            $orderBy,
            $whereProducts,
            $onlyProductsWithFalOrders,
            $pid_list,
            $productRowArray,
            $multiOrderArray
        );

        $this->getGainedProducts(
            $from,
            $where,
            $orderBy,
            $whereProducts,
            $onlyProductsWithFalOrders,
            $pid_list,
            $gainedProductRowArray,
            $gainedMultiOrderArray
        );

        if (
            isset($productRowArray) &&
            is_array($productRowArray) &&
            isset($gainedProductRowArray) &&
            is_array($gainedProductRowArray)
        ) {
            $productRowArray = array_merge($productRowArray, $gainedProductRowArray);
            $newProductRowArray = [];
            $productArray = [];
            // remove duplicates
            foreach ($productRowArray as $productRow) {
                $uid = $productRow['uid'];
                if (!isset($productArray[$uid])) {
                    $productArray[$uid] = true;
                    $newProductRowArray[] = $productRow;
                }
            }
            $productRowArray = $newProductRowArray;
            $multiOrderArray = array_merge($multiOrderArray, $gainedMultiOrderArray);
        }
    }

    public function getDownloadWhereClauses(
        $feusers_uid,
        $trackingCode,
        &$whereOrders,
        &$whereProducts
    ): void {
        $whereOrders = '1=1';

        if ($feusers_uid) {
            $rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
                '*',
                'fe_users',
                'uid=' . intval($feusers_uid) . ' AND deleted=0 AND disable=0',
                '',
                '',
                1
            );

            if (
                is_array($rows) &&
                is_array($rows['0'])
            ) {
                $whereOrders =
                    '(' .
                        'feusers_uid=' . intval($feusers_uid) .
                        ' OR email=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($rows['0']['email'], 'fe_users') .
                    ')';
            }
        } elseif ($trackingCode != '') {
            $whereOrders =
                'tracking_code=' .
                $GLOBALS['TYPO3_DB']->fullQuoteStr(
                    $trackingCode,
                    $this->getFuncTablename()
                );
        }

        $whereOrders .= ' AND pay_mode > 0';
        $whereOrders = $this->getTableObj()->transformWhere($whereOrders);
        $whereProducts = 'download_uid > 0';
    }
}
