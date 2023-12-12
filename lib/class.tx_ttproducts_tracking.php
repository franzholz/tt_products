<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2015 Franz Holzinger (franz@ttproducts.de)
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
 * tracking functions
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage tt_products
 */
use JambageCom\Div2007\Utility\TableUtility;
use JambageCom\TtProducts\Api\BasketApi;
use JambageCom\TtProducts\Api\Localization;
use JambageCom\TtProducts\Api\PaymentApi;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

class tx_ttproducts_tracking implements SingletonInterface
{
    public $cObj;
    public $conf;		  // original configuration
    private $statusCodeArray;

    /**
     * $basket is the TYPO3 default shopping basket array from ses-data.
     */
    public function init($cObj)
    {
        $this->cObj = $cObj;
        $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
        $this->conf = $cnf->conf;

        if (
            isset($this->conf['statusCodes.']) &&
            is_array($this->conf['statusCodes.'])
        ) {
            foreach ($this->conf['statusCodes.'] as $k => $v) {
                if (
                    MathUtility::canBeInterpretedAsInteger($k)
                ) {
                    $statusCodeArray[$k] = $v;
                }
            }
        } elseif ($this->conf['statusCodesSource']) {
            switch ($this->conf['statusCodesSource']) {
                case 'marker_locallang':
                    $markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
                    $langArray = $markerObj->getLangArray();

                    if (is_array($langArray)) {
                        $statusMessage = 'tracking_status_message_';
                        $len = strlen($statusMessage);
                        foreach ($langArray as $k => $v) {
                            if (($pos = strpos($k, $statusMessage)) === 0) {
                                $rest = substr($k, $len);
                                if (
                                    MathUtility::canBeInterpretedAsInteger($rest)
                                ) {
                                    $statusCodeArray[$rest] = $v;
                                }
                            }
                        }
                    }
                    break;
            }
        }

        $this->setStatusCodeArray($statusCodeArray);
    }

    public function setStatusCodeArray(&$statusCodeArray)
    {
        $this->statusCodeArray = $statusCodeArray;
    }

    public function getStatusCodeArray()
    {
        return $this->statusCodeArray;
    }

    protected function getDate($newData)
    {
        $date = '';
        if ($newData) {
            $dateArray = GeneralUtility::trimExplode('-', $newData);
            $date = mktime(0, 0, 0, $dateArray[1], $dateArray[0], $dateArray[2]);
            if (!empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['serverTimeZone'])) {
                $date += ($GLOBALS['TYPO3_CONF_VARS']['SYS']['serverTimeZone'] * 3600);
            }
        } else {
            $date = time();
        }

        return $date;
    }

    // search the order status for paid and closed
    public function searchOrderStatus(
        $status_log,
        &$orderPaid,
        &$orderClosed
    ) {
        $orderPaid = false;
        $orderClosed = false;
        if (isset($status_log) && is_array($status_log)) {
            foreach ($status_log as $key => $val) {
                if ($val['status'] == 13) {// Numbers 13 means order has been payed
                    $orderPaid = true;
                }
                if ($val['status'] >= 100) {// Numbers 13 means order has been payed
                    $orderClosed = true;
                    break;
                }
            }
        }
    }

    /*
        Tracking information display and maintenance.

        status-values are
            0:  Blank order
        1-1 Incoming orders
            1:  Order confirmed at website
        2-49: Useable by the shop admin
            2 = Order is received and accepted by store
            10 = Shop is awaiting goods from third-party
            11 = Shop is awaiting customer payment
            12 = Shop is awaiting material from customer
            13 = Order has been payed
            20 = Goods shipped to customer
            21 = Gift certificates shipped to customer
            30 = Other message from store
            ...
        50-99:  Useable by the customer
        50-59: General user messages, may be updated by the ordinary users.
            50 = Customer request for cancelling
            51 = Message from customer to shop
        60-69:  Special user messages by the customer
            60 = Send gift certificate message to receiver

        100-299:  Order finalized.
            100 = Order shipped and closed
            101 = Order closed
            200 = Order cancelled

        All status values can be altered only if you're logged in as a BE-user and if you know the correct code (setup as .update_code in TypoScript config)
    */
    public function getTrackingInformation(
        $orderRow,
        $templateCode,
        $trackingCode,
        $updateCode,
        &$orderRecord,
        $bValidUpdateCode
    ) {
        $templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        $bUseXHTML = !empty($GLOBALS['TSFE']->config['config']['xhtmlDoctype']);
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $orderObj = $tablesObj->get('sys_products_orders');
        $markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
        $pibaseObj = GeneralUtility::makeInstance('tx_ttproducts_pi1_base');
        $languageObj = GeneralUtility::makeInstance(Localization::class);
        $basketView = GeneralUtility::makeInstance('tx_ttproducts_basket_view');
        $infoViewObj = GeneralUtility::makeInstance('tx_ttproducts_info_view');
        // 		$paymentshippingObj = GeneralUtility::makeInstance('tx_ttproducts_paymentshipping');
        $theTable = 'sys_products_orders';
        $piVars = tx_ttproducts_model_control::getPiVars();

        $orderData = $orderObj->getOrderData($orderRow);
        $statusCodeArray = [];

        $allowUpdateFields = ['email', 'email_notify', 'status', 'status_log'];
        $newData = $piVars['data'];
        $bStatusValid = false;
        $basketRec = [];
        $basketExtra = [];

        if (
            isset($orderRow) &&
            is_array($orderRow) &&
            $orderRow['uid']
        ) {
            $basketRec = BasketApi::getBasketRec($orderRow);
            $basketExtra =
                tx_ttproducts_control_basket::getBasketExtras(
                    $tablesObj,
                    $basketRec,
                    $this->conf
                );

            $statusCodeArray = $this->getStatusCodeArray();
            $pageTitle = $orderRow['uid'] . ' (' . $orderRow['bill_no'] . '): ' . $orderRow['name'] . '-' . $orderRow['zip'] . '-' . $orderRow['city'] . '-' . $orderRow['country'];
            $GLOBALS['TSFE']->page['title'] = $pageTitle;
            $GLOBALS['TSFE']->indexedDocTitle = $pageTitle;

            // Initialize update of status...
            $fieldsArray = [];
            if (isset($orderRecord['email_notify'])) {
                $fieldsArray['email_notify'] = $orderRecord['email_notify'];
                $orderRow['email_notify'] = $orderRecord['email_notify'];
            }
            if (isset($orderRecord['email'])) {
                $fieldsArray['email'] = $orderRecord['email'];
                $orderRow['email'] = $orderRecord['email'];
            }

            if (
                is_array($orderRecord['status']) &&
                isset($statusCodeArray) &&
                is_array($statusCodeArray)
            ) {
                $bStatusValid = true;
                $status_log = unserialize($orderRow['status_log']);
                $update = 0;
                $count = 0;

                foreach ($orderRecord['status'] as $val) {
                    if (!isset($statusCodeArray[$val])) {
                        $bStatusValid = false;
                        break;
                    }
                    $internalComment = '';

                    if ($bValidUpdateCode) {
                        if ($val >= 31 && $val <= 32) {// Numbers 31,32 are for storing of bill no. of external software
                            if ($newData) {
                                $fieldsArray['bill_no'] = $newData;
                            }
                        }

                        if ($val == 13) {// Number 13 is that order has been paid. The date muss be entered in format dd-mm-yyyy
                            $date = $this->getDate($newData);

                            if (
                                isset($orderRow) &&
                                is_array($orderRow) &&
                                $orderRow['uid']
                            ) {
                                $basketRec = BasketApi::getBasketRec($orderRow);
                                $basketExtra =
                                    tx_ttproducts_control_basket::getBasketExtras(
                                        $tablesObj,
                                        $basketRec,
                                        $this->conf
                                    );

                                $whereGift = $this->conf['whereGift'];
                                $voucherUidArray = [];

                                if (
                                    $whereGift != '' &&
                                    ExtensionManagementUtility::isLoaded('voucher')
                                ) {
                                    $hidden = tx_ttproducts_voucher::unhideGifts(
                                        $voucherUidArray,
                                        $orderRow,
                                        $whereGift
                                    );

                                    if (
                                        $hidden &&
                                        isset($voucherUidArray) &&
                                        is_array($voucherUidArray) &&
                                        !empty($voucherUidArray)
                                    ) {
                                        $voucherObj = $tablesObj->get('voucher');
                                        $voucherRows = $voucherObj->get(implode(',', $voucherUidArray));
                                        $voucherCodeArray = [];

                                        if (
                                            isset($voucherRows) &&
                                            is_array($voucherRows) &&
                                            !empty($voucherRows)
                                        ) {
                                            if (count($voucherUidArray) > 1) {
                                                foreach ($voucherRows as $voucherRow) {
                                                    $voucherCodeArray[] = $voucherRow['code'];
                                                }
                                            } else {
                                                $voucherCodeArray[] = $voucherRows['code'];
                                            }
                                        }
                                        $text = $languageObj->getLabel(
                                            'voucher_gained',
                                            $usedLang = 'default'
                                        );
                                        $internalComment =
                                            $text . ': ' . chr(13) . implode(chr(13), $voucherCodeArray);
                                    }
                                }
                            }

                            $payMode = PaymentApi::getPayMode($languageObj, $basketExtra);

                            $fieldsArray['date_of_payment'] = $date;
                            if (!$payMode) {
                                $payMode = '1';
                            }
                            $fieldsArray['pay_mode'] = $payMode;
                        }

                        if ($val == 20) {// Number 20 is that items have been shipped. The date muss be entered in format dd-mm-yyyy
                            $date = $this->getDate($newData);
                            $fieldsArray['date_of_delivery'] = $date;
                        }
                    }

                    $status_log_element = [
                        'time' => time(),
                        'info' => $statusCodeArray[$val],
                        'status' => $val,
                        'comment' => ($count == 0 ? $orderRecord['status_comment'] . ($internalComment != '' ? $internalComment : '') . ($newData != '' ? '|' . $newData : '') : ''), // comment is inserted only to the first status
                    ];

                    if ($bValidUpdateCode || ($val >= 50 && $val < 59)) {// Numbers 50-59 are usermessages.
                        $recipient = $this->conf['orderEmail_to'];
                        if (!empty($orderRow['email']) && !empty($orderRow['email_notify'])) {
                            $recipient .= ',' . $orderRow['email'];
                        }
                        $templateMarker = 'TRACKING_EMAILNOTIFY_TEMPLATE';
                        tx_ttproducts_email_div::sendNotifyEmail(
                            $this->cObj,
                            $this->conf,
                            $this->config,
                            'fe_users',
                            $orderObj->getNumber($orderRow['uid']),
                            $recipient,
                            $status_log_element,
                            $statusCodeArray,
                            GeneralUtility::_GP('tracking'),
                            $orderRow,
                            $orderData,
                            $templateCode,
                            $templateMarker,
                            $basketExtra,
                            $basketRecs
                        );
                        $status_log[] = $status_log_element;
                        $update = 1;
                    }

                    if ($val >= 60 && $val < 69) { //  60 -69 are special messages
                        $templateMarker = 'TRACKING_EMAIL_GIFTNOTIFY_TEMPLATE';
                        $query = 'ordernumber=\'' . intval($orderRow['uid']) . '\'';
                        $giftRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_products_gifts', $query);
                        while ($giftRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($giftRes)) {
                            $recipient = $giftRow['deliveryemail'] . ',' . $giftRow['personemail'];
                            tx_ttproducts_email_div::sendGiftEmail(
                                $this->cObj,
                                $this->conf,
                                $recipient,
                                $orderRecord['status_comment'],
                                $giftRow,
                                $templateCode,
                                $templateMarker,
                                $this->conf['orderEmail_htmlmail']
                            );
                        }
                        $GLOBALS['TYPO3_DB']->sql_free_result($giftRes);

                        if (!$update) {
                            $status_log[] = $status_log_element;
                            $update = 1;
                        }
                    }
                    $count++;
                }
                if ($update) {
                    $fieldsArray['status_log'] = serialize($status_log);
                    $fieldsArray['status'] = intval($status_log_element['status']);
                }
            }

            if (is_array($fieldsArray) && count($fieldsArray)) {		// If any items in the field array, save them
                if (!$bValidUpdateCode) {	// only these fields may be updated in an already stored order
                    $fieldsArray = array_intersect_key($fieldsArray, array_flip($allowUpdateFields));
                }
                if (count($fieldsArray)) {
                    $fieldsArray['tstamp'] = time();
                    $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
                        'sys_products_orders',
                        'uid=' . intval($orderRow['uid']),
                        $fieldsArray
                    );
                    $orderRow = $orderObj->getRecord($orderRow['uid']);
                }
            }
            $status_log = unserialize($orderRow['status_log']);
        }

        // Getting the template stuff and initialize order data.
        $template = $templateService->getSubpart($templateCode, '###TRACKING_DISPLAY_INFO###');
        $this->searchOrderStatus($status_log, $orderPaid, $orderClosed);

        $globalMarkerArray = $markerObj->getGlobalMarkerArray();

        // making status code 60 disappear if the order has not been payed yet
        if (!$orderPaid || $orderClosed) {
            // Fill marker arrays
            $markerArray = $globalMarkerArray;
            $subpartArray = [];
            $subpartArray['###STATUS_CODE_60###'] = '';

            $template = $templateService->substituteMarkerArrayCached($template, $markerArray, $subpartArray);
        }

        // Status:
        $statusItemOut = $templateService->getSubpart($template, '###STATUS_ITEM###');
        $statusItemOut_c = '';

        if (is_array($status_log)) {
            foreach ($status_log as $k => $v) {
                $markerArray = [];
                $markerArray['###ORDER_STATUS_TIME###'] = $this->cObj->stdWrap($v['time'], $this->conf['statusDate_stdWrap.']);
                $markerArray['###ORDER_STATUS###'] = $v['status'];

                $info = $statusCodeArray[$v['status']];
                $markerArray['###ORDER_STATUS_INFO###'] = ($info ? $info : $v['info']);
                $markerArray['###ORDER_STATUS_COMMENT###'] = nl2br($v['comment']);
                $statusItemOut_c .= $templateService->substituteMarkerArrayCached($statusItemOut, $markerArray);
            }
        }

        $markerArray = $globalMarkerArray;
        $subpartArray = [];
        $wrappedSubpartArray = [];
        $markerArray['###OTHER_ORDERS_OPTIONS###'] = '';
        $markerArray['###STATUS_OPTIONS###'] = '';
        $subpartArray['###STATUS_ITEM###'] = $statusItemOut_c;

        $bBEAdmin = ($this->conf['shopAdmin'] == 'BE');
        tx_ttproducts_admin_control_view::getSubpartArrays(
            tx_ttproducts_control_access::isAllowed($bBEAdmin),
            $bValidUpdateCode,
            $subpartArray,
            $wrappedSubpartArray
        );

        $tableName = 'sys_products_orders';
        $markerFieldArray = [];
        $orderView = $tablesObj->get($tableName, true);
        $orderObj = $orderView->getModelObj();
        $orderMarkerArray = $globalMarkerArray;
        $viewTagArray = [];
        $parentArray = [];
        $t = [];
        $t['orderFrameWork'] = $templateService->getSubpart($template, '###ORDER_ITEM###');
        $fieldsArray = $markerObj->getMarkerFields(
            $t['orderFrameWork'],
            $orderObj->getTableObj()->tableFieldArray,
            $orderObj->getTableObj()->requiredFieldArray,
            $markerFieldArray,
            $orderView->getMarker(),
            $viewTagArray,
            $parentArray
        );

        if ($orderRow) {
            $tmp = [];
            $orderView->getRowMarkerArray(
                $tableName,
                $orderRow,
                '',
                $orderMarkerArray,
                $tmp,
                $tmp,
                $viewTagArray,
                'TRACKING',
                $basketExtra,
                $basketRecs
            );

            $subpartArray['###ORDER_ITEM###'] =
                $templateService->substituteMarkerArrayCached(
                    $t['orderFrameWork'],
                    $orderMarkerArray
                );
        } else {
            $subpartArray['###ORDER_ITEM###'] = '';
        }

        if ($bValidUpdateCode) {
            // Status admin:
            if (isset($statusCodeArray) && is_array($statusCodeArray)) {
                foreach ($statusCodeArray as $k => $v) {
                    if ($k != 1) {
                        $markerArray['###STATUS_OPTIONS###'] .= '<option value="' . $k . '">' . htmlspecialchars($k . ': ' . $v) . '</option>';
                    }
                }
            }
            $priceViewObj = GeneralUtility::makeInstance('tx_ttproducts_field_price_view');
            if (isset($this->conf['tracking.']) && isset($this->conf['tracking.']['fields'])) {
                $fields = $this->conf['tracking.']['fields'];
            } else {
                $fields = 'uid';
            }
            $fields .= ',crdate,tracking_code,status,status_log,bill_no,name,amount,feusers_uid';
            $fields = GeneralUtility::uniqueList($fields);
            $history = [];
            $fieldMarkerArray = [];
            $oldMode = preg_match('/###OTHER_ORDERS_OPTIONS###\s*<\/select>/i', $templateCode);
            $where = 'status!=0 AND status<100';
            $orderBy = 'crdate';

            if (
                isset($this->conf['tracking.']) &&
                isset($this->conf['tracking.']['sql.'])
            ) {
                if (isset($this->conf['tracking.']['sql.']['where'])) {
                    $where = $this->conf['tracking.']['sql.']['where'];
                }
                if (isset($this->conf['tracking.']['sql.']['orderBy'])) {
                    $orderBy = $this->conf['tracking.']['sql.']['orderBy'];
                }
            }
            $bUseHistoryMarkers = (strpos($orderBy, 'crdate') !== false);
            $bInverseHistory = (strpos($orderBy, 'crdate desc') !== false);

            if ($bInverseHistory) {
                $orderBy = 'crdate'; // Todo: all order by fields must be reversed to keep the history program logic
            }

            $enableFields = TableUtility::enableFields('sys_products_orders');

            // Get unprocessed orders.
            $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                $fields,
                'sys_products_orders',
                $where . $enableFields,
                '',
                $orderBy
            );

            $valueArray = [];
            $keyMarkerArray = [];

            while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
                $tmpStatuslog = unserialize($row['status_log']);
                $classPrefix = str_replace('_', '-', $pibaseObj->prefixId);
                $this->searchOrderStatus($tmpStatuslog, $tmpPaid, $tmpClosed);
                $class = ($tmpPaid ? $classPrefix . '-paid' : '');
                $class = ($class ? $class . ' ' : '') . ($tmpClosed ? $classPrefix . '-closed' : '');
                $class = ($class ? ' class="' . $class . '"' : '');

                $fieldMarkerArray['###OPTION_CLASS###'] = $class;

                if ($oldMode) {
                    $markerArray['###OTHER_ORDERS_OPTIONS###'] .=
                        '<option ' . $class . ' value="' . $row['tracking_code'] . '"' . ($row['uid'] == $orderRow['uid'] ? 'selected="selected"' : '') . '>' .
                            htmlspecialchars(
                                $row['uid'] . ' (' . $row['bill_no'] . '): ' .
                                $row['name'] . ' (' . $priceViewObj->priceFormat($row['amount']) . ' ' . $this->conf['currencySymbol'] . ') /' . $row['status']
                            ) .
                        '</option>';
                } else {
                    if (isset($row['feusers_uid']) && $bUseHistoryMarkers) {
                        if (!$row['feusers_uid'] || !isset($history[$row['feusers_uid']])) {
                            $history[$row['feusers_uid']] = [
                                'out' => '',
                                'count' => 0,
                            ];
                        }
                        ++$history[$row['feusers_uid']]['count'];
                        $last_order = $history[$row['feusers_uid']];

                        if ($last_order['count'] == 1) {
                            $fieldMarkerArray['###LAST_ORDER_TYPE###'] = $languageObj->getLabel('first_order');
                            $fieldMarkerArray['###LAST_ORDER_COUNT###'] = '-';
                        } else {
                            $fieldMarkerArray['###LAST_ORDER_TYPE###'] = $last_order['out'];
                            $fieldMarkerArray['###LAST_ORDER_COUNT###'] = $last_order['count'];
                        }
                        if ($row['feusers_uid'] == 0) {
                            $fieldMarkerArray['###LAST_ORDER_TYPE###'] = $languageObj->getLabel('unregistered');
                            $fieldMarkerArray['###LAST_ORDER_COUNT###'] = '-';
                        }
                        if ($row['company'] == '') {
                            $row['company'] = $languageObj->getLabel('undeclared');
                        }
                        $history[$row['feusers_uid']]['out'] = date('d.m.Y - H:i', $row['crdate']);
                    }

                    $fieldMarkerArray['###OPTION_SELECTED###'] = ($row['uid'] == $orderRow['uid'] ? 'selected="selected"' : '');
                    foreach ($row as $field => $value) {
                        switch ($field) {
                            case 'amount':
                                $value = $priceViewObj->priceFormat($value);
                                break;
                            case 'crdate':
                                $value = date('d.m.Y - H:i', $value);
                                break;
                            default:
                                $value = htmlspecialchars($value);
                                break;
                        }
                        $fieldMarkerArray['###ORDER_' . strtoupper($field) . '###'] = $value;
                    }
                    $fieldMarkerArray['###ORDER_ORDER_NO###'] = $fieldMarkerArray['###ORDER_UID###'];
                    $fieldMarkerArray['###CUR_SYM###'] = $this->conf['currencySymbol'];
                    $valueArray[$row['tracking_code']] = $row['uid'];
                    $keyMarkerArray[$row['tracking_code']] = $fieldMarkerArray;
                }
            }
            $GLOBALS['TYPO3_DB']->sql_free_result($res);

            if (!$oldMode) {
                if ($bInverseHistory) {
                    $valueArray = array_reverse($valueArray);
                }
                if (isset($this->conf['tracking.'])) {
                    $type = $this->conf['tracking.']['recordType'];
                    $recordLine = $this->conf['tracking.']['recordLine'];
                }
                if ($type == '') {
                    $type = 'select';
                }
                if ($recordLine == '') {
                    $recordLine = '<!-- ###INPUT### begin -->###ORDER_ORDER_NO### (###ORDER_BILL_NO###): ###ORDER_NAME### (###ORDER_AMOUNT### ###CUR_SYM###) / ###ORDER_STATUS###) ###ORDER_CRDATE### ###LAST_ORDER_TYPE### ###LAST_ORDER_COUNT###<!-- ###INPUT### end -->';
                }

                $out = tx_ttproducts_form_div::createSelect(
                    $languageObj,
                    $valueArray,
                    'tracking',
                    $orderRow['tracking_code'],
                    false,
                    false,
                    [],
                    $type,
                    [],
                    '',
                    $recordLine,
                    '',
                    $keyMarkerArray
                );

                if (isset($this->conf['tracking.']) && isset($this->conf['tracking.']['recordBox.'])) {
                    $out = $this->cObj->stdWrap($out, $this->conf['tracking.']['recordBox.']);
                }
                $markerArray['###OTHER_ORDERS_OPTIONS###'] .= $out;
            }
        }

        $bHasTrackingTemplate = preg_match('/###TRACKING_TEMPLATE###/i', $templateCode);

        // Final things
        if (!$bHasTrackingTemplate) {
            $markerArray['###ORDER_HTML_OUTPUT###'] = $orderData['html_output'];	// The save order-information in HTML-format
        } elseif (isset($orderRow) && is_array($orderRow) && $orderRow['uid']) {
            $itemArray = $orderObj->getItemArray($orderRow, $calculatedArray, $infoArray);
            $infoViewObj->init2($infoArray);
            $productRowArray = []; // Todo: make this a parameter

            if ($orderRow['ac_uid']) {
                // get bank account info
                $accountViewObj = $tablesObj->get('sys_products_accounts', true, false);
                $accountObj = $tablesObj->get('sys_products_accounts', false, false);
                $accountRow = $accountObj->getRow($orderRow['ac_uid']);
                $accountViewObj->getMarkerArray($accountRow, $globalMarkerArray, true);
            }

            if ($orderRow['cc_uid']) {
                $cardViewObj = $tablesObj->get('sys_products_cards', true, false);
                $cardObj = $tablesObj->get('sys_products_cards', false, false);
                $cardRow = $cardObj->getRow($orderRow['cc_uid']);
                $cardViewObj->setCObj($this->cObj);
                $cardViewObj->setConf($this->conf);
                $cardViewObj->getMarkerArray($cardRow, $globalMarkerArray, []);
            }
            $customerEmail = $orderRow['email']; // $infoViewObj->getCustomerEmail();
            $globalMarkerArray['###CUSTOMER_RECIPIENTS_EMAIL###'] = $customerEmail;
            $markerArray['###ORDER_HTML_OUTPUT###'] =
                $basketView->getView(
                    $errorCode,
                    $templateCode,
                    'TRACKING',
                    $infoViewObj,
                    false,
                    false,
                    $calculatedArray,
                    true,
                    'TRACKING_TEMPLATE',
                    $globalMarkerArray,
                    '',
                    $itemArray,
                    $notOverwritePriceIfSet = false,
                    ['0' => $orderRow],
                    $productRowArray,
                    $basketExtra,
                    $basketRec
                );
        } else {
            $markerArray['###ORDER_HTML_OUTPUT###'] = '';
        }

        if (isset($orderData) && is_array($orderData)) {
            $markerArray['###ORDERCONFIRMATION_HTML_OUTPUT###'] = $orderData['html_output'];	// The save order-information in HTML-format
        } else {
            $markerArray['###ORDERCONFIRMATION_HTML_OUTPUT###'] = '';
        }

        $checkedHTML = ($bUseXHTML ? 'checked="checked"' : 'checked');
        $markerArray['###FIELD_EMAIL_NOTIFY###'] = $orderRow['email_notify'] ? ' ' . $checkedHTML : '';

        $markerArray['###FIELD_EMAIL###'] = $orderRow['email'];
        $markerArray['###ORDER_UID###'] = $markerArray['###ORDER_ORDER_NO###'] = $orderObj->getNumber($orderRow['uid']);
        $markerArray['###ORDER_DATE###'] = $this->cObj->stdWrap($orderRow['crdate'], $this->conf['orderDate_stdWrap.'] ?? '');
        $markerArray['###TRACKING_NUMBER###'] = $trackingCode;
        $markerArray['###UPDATE_CODE###'] = $updateCode;
        $markerArray['###TRACKING_DATA_NAME###'] = $pibaseObj->prefixId . '[data]';
        $markerArray['###TRACKING_DATA_VALUE###'] = ($bStatusValid ? '' : $newData);
        $markerArray['###TRACKING_STATUS_COMMENT_NAME###'] = 'orderRecord[status_comment]';
        $markerArray['###TRACKING_STATUS_COMMENT_VALUE###'] = ($bStatusValid ? '' : $orderRecord['status_comment']);

        if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['tracking']) && is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['tracking'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['tracking'] as $classRef) {
                $hookObj = GeneralUtility::makeInstance($classRef);
                if (method_exists($hookObj, 'getTrackingInformation')) {
                    $hookObj->getTrackingInformation(
                        $this,
                        $orderRow,
                        $templateCode,
                        $trackingCode,
                        $updateCode,
                        $orderRecord,
                        $bValidUpdateCode,
                        $template,
                        $markerArray,
                        $subpartArray
                    );
                }
            }
        }

        $content = $templateService->substituteMarkerArrayCached($template, $markerArray, $subpartArray, $wrappedSubpartArray);

        return $content;
    } // getTrackingInformation
}
