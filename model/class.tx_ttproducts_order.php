<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2009 Franz Holzinger (franz@ttproducts.de)
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
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;




class tx_ttproducts_order extends tx_ttproducts_table_base {


	// **************************
	// ORDER related functions
	// **************************
	/**
	 * Create a new order record
	 *
	 * This creates a new order-record on the page with pid PID_sys_products_orders. That page must exist!
	 * Should be called only internally by eg. $order->getBlankUid, that first checks if a blank record is already created.
	 */
	public function create ()	{
		$newId = 0;
		$pid = intval($this->conf['PID_sys_products_orders']);

		if (!$pid)	{
			$pid = intval($GLOBALS['TSFE']->id);
		}

		if ($GLOBALS['TSFE']->sys_page->getPage_noCheck($pid))	{
			$advanceUid = 0;
			if ($this->conf['advanceOrderNumberWithInteger'] || $this->conf['alwaysAdvanceOrderNumber'])	{
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'sys_products_orders', '', '', 'uid DESC', '1');
				list($prevUid) = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
				$GLOBALS['TYPO3_DB']->sql_free_result($res);

				if ($this->conf['advanceOrderNumberWithInteger']) {
					$rndParts = explode(',',$this->conf['advanceOrderNumberWithInteger']);
					$randomValue = rand(intval($rndParts[0]), intval($rndParts[1]));
					$advanceUid = $prevUid + tx_div2007_core::intInRange($randomValue, 1);
				} else {
					$advanceUid = $prevUid + 1;
				}
			}

			$time = time();
			$insertFields = array (
				'pid' => intval($pid),
				'tstamp' => $time,
				'crdate' => $time,
				'deleted' => 0,
				'hidden' => 1
			);
			if ($advanceUid > 0)	{
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
				$rowArray = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid', 'sys_products_orders', 'uid=LAST_INSERT_ID()', '');
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
	public function getBlankUid ()	{
	// an new orderUid has been created always because also payment systems can be used which do not accept a duplicate order id
		$basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');
		$orderUid = 0;

		if (isset($basketObj->order['orderUid'])) {
			$orderUid = intval($basketObj->order['orderUid']);
		}

		if ($orderUid) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'sys_products_orders', 'uid='.intval($orderUid).' AND hidden AND NOT status');	// Checks if record exists, is marked hidden (all blank orders are hidden by default) and is not finished.
		}

		if (!$orderUid || !$GLOBALS['TYPO3_DB']->sql_num_rows($res) || $this->conf['alwaysAdvanceOrderNumber'])	{
			$orderUid = $this->create();
			$basketObj->order['orderUid'] = $orderUid;
			$basketObj->order['orderDate'] = time();
			$basketObj->order['orderTrackingNo'] = $this->getNumber($orderUid).'-'.strtolower(substr(md5(uniqid(time())),0,6));

			$GLOBALS['TSFE']->fe_user->setKey('ses','order',$basketObj->order);
		}

		if ($res) {
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
		}

		return $orderUid;
	} // getBlankUid


	public function getUid ()	{
		$basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');
		$rc = $basketObj->order['orderUid'];
		return $rc;
	}


	function clearUid ()	{
		$this->basket->order['orderUid'] = '';
		$GLOBALS['TSFE']->fe_user->setKey('ses','order',array());
	}


	/**
	 * Returns the order record if $orderUid.
	 * If $tracking is set, then the order with the tracking number is fetched instead.
	 */
	public function getRecord ($orderUid, $tracking='')	{
		if (
			empty($tracking) &&
			!$orderUid
		) {
			return false;
		}

		$where = ($tracking ? 'tracking_code=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($tracking, 'sys_products_orders') : 'uid=' . intval($orderUid));

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			'sys_products_orders',
			$where . $this->cObj->enableFields('sys_products_orders')
		);
		$rc = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
		return $rc;
	} //getRecord


	/**
	 * This returns the order-number (opposed to the order_uid) for display in the shop, confirmation notes and so on.
	 * Basically this prefixes the .orderNumberPrefix, if any
	 */
    public function getNumber ($orderUid)   {
        $orderNumberPrefix = substr($this->conf['orderNumberPrefix'], 0, 30);
        if (($position = strpos($orderNumberPrefix, '%')) !== false)    {
            $orderDate = date(substr($orderNumberPrefix, $position + 1));
            $orderNumberPrefix = substr($orderNumberPrefix, 0, $position) . $orderDate;
        }

        $result = $orderNumberPrefix . $orderUid;
        return $result;
    } // getNumber

	/**
	 * Saves the order record and returns the result
	 *
	 */
	public function putRecord (
		$orderUid,
		$cardUid,
		$accountUid,
		$email_notify,
		$payment,
		$shipping,
		$amount,
		&$orderConfirmationHTML,
		&$address,
		$status,
		$basketExtra
	)	{
		$basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');
		$calculObj = GeneralUtility::makeInstance('tx_ttproducts_basket_calculate');
        $tablename = $this->getTablename();
        $feusers_uid = 0;

		if (isset($GLOBALS['TSFE']->fe_user->user) && is_array($GLOBALS['TSFE']->fe_user->user) && $GLOBALS['TSFE']->fe_user->user['uid'])	{
			$feusers_uid = $GLOBALS['TSFE']->fe_user->user['uid'];
		}

		$deliveryInfo = $address->infoArray['delivery'];
		$billingInfo = $address->infoArray['billing'];

		if (!isset($deliveryInfo)) {
			$deliveryInfo = $billingInfo;
		}

        if (is_array($billingInfo) && isset($billingInfo['feusers_uid'])) {
            $feusers_uid = $billingInfo['feusers_uid'];
		}

		if ($deliveryInfo['name'] == '') {
			$deliveryInfo['name'] = $deliveryInfo['last_name'] . ' ' . $deliveryInfo['first_name'];
		}

		if ($deliveryInfo['date_of_birth'])	{
			$dateArray = GeneralUtility::trimExplode ('-', $deliveryInfo['date_of_birth']);

			if (
				tx_div2007_core::testInt($dateArray[0]) &&
				tx_div2007_core::testInt($dateArray[1]) &&
				tx_div2007_core::testInt($dateArray[2])
			) {
				$dateBirth = mktime(0,0,0,$dateArray[1],$dateArray[0],$dateArray[2]);
			}
		}

			// Saving order data
		$fieldsArray = array();
		$addressFields = array('name', 'first_name', 'last_name', 'company', 'salutation', 'address', 'house_no', 'zip', 'city', 'country', 'telephone', 'fax', 'email', 'email_notify', 'business_partner', 'organisation_form');


        $excludeArray =  
            (version_compare(TYPO3_version, '10.0.0', '>=') ? 
                $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['exclude'] :
                $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['exclude.']
            );
        $excludeFieldArray = array();

        if (
            isset($excludeArray) &&
            is_array($excludeArray) &&
            isset($excludeArray[$tablename])
        ) {
            $excludeFieldArray = GeneralUtility::trimExplode(',', $excludeArray[$tablename]);
        }

		if (isset($deliveryInfo) && is_array($deliveryInfo)) {
			foreach ($billingInfo as $field => $value) {
				if (
					$value &&
					!in_array($field, $addressFields) &&
					isset($GLOBALS['TCA']['sys_products_orders']['columns'][$field])
				) {
					$fieldsArray[$field] = $value;
				}
			}

			foreach ($deliveryInfo as $field => $value) {
				if ($value && isset($GLOBALS['TCA']['sys_products_orders']['columns'][$field])) {
					$fieldsArray[$field] = $value;
				}
			}
		}

		$fieldsArray['feusers_uid'] = intval($feusers_uid);

			// can be changed after order is set.
		$fieldsArray['payment'] = $payment;
		$fieldsArray['shipping'] = $shipping;
		$fieldsArray['amount'] = $amount;
		$fieldsArray['status'] = $status;	// If 1, then this means, "Order confirmed on website, next step: confirm from shop that order is received"
		if ($status == 1)	{
			$fieldsArray['hidden'] = 0;
		}

		$fieldsArray['date_of_birth'] = $dateBirth;

		$giftServiceArticleArray = array();
		$basketExt = $basketObj->getBasketExt();
		if (isset($basketExt) && is_array($basketExt)) {
			foreach ($basketExt as $tmpUid => $tmpSubArr)	{
				if (is_array($tmpSubArr))	{
					foreach ($tmpSubArr as $tmpKey => $tmpSubSubArr)	{
						if (
							substr($tmpKey,-1) == '.' &&
							isset($tmpSubSubArr['additional']) &&
							is_array($tmpSubSubArr['additional'])
						)	{
							$variant = substr($tmpKey,0,-1);
							$row = $basketObj->get($tmpUid, $variant);
							if ($tmpSubSubArr['additional']['giftservice'] == 1)	{
								$giftServiceArticleArray[] = $row['title'];
							}
						}
					}
				}
			}
		}
		$fieldsArray['giftservice'] = $deliveryInfo['giftservice'] . '||' . implode(',',$giftServiceArticleArray);

		$fieldsArray['client_ip'] = GeneralUtility::getIndpEnv('REMOTE_ADDR');
		$fieldsArray['cc_uid'] = $cardUid;
		$fieldsArray['ac_uid'] = $accountUid;
		$fieldsArray['giftcode'] = $basketObj->recs['tt_products']['giftcode'];
        $api =
            GeneralUtility::makeInstance(\JambageCom\Div2007\Api\Frontend::class);
        $sys_language_uid = $api->getLanguageId();
        $fieldsArray['sys_language_uid'] = $sys_language_uid;

		if ($billingInfo['tt_products_vat'] != '')	{
			$fieldsArray['vat_id'] = $billingInfo['tt_products_vat'];

			$paymentshippingObj = GeneralUtility::makeInstance('tx_ttproducts_paymentshipping');
			$taxPercentage = $paymentshippingObj->getReplaceTaxPercentage($basketExtra);
			if (doubleval($taxPercentage) == 0)	{
				$fieldsArray['tax_mode'] = 1;
			}
		}

/* Added Els: update fe_user with amount of creditpoints and subtract creditpoints used in order*/
		$fieldsArrayFeUsers = array();
		$uid_voucher = ''; // define it here
		$cpArray = $GLOBALS['TSFE']->fe_user->getKey('ses','cp');

		if ($deliveryInfo['date_of_birth'])	{
			$fieldsArrayFeUsers['date_of_birth'] = $dateBirth;
		}

		$usedCreditpoints = 0;
		if (isset($_REQUEST['recs']) && is_array($_REQUEST['recs']) && isset($_REQUEST['recs']['tt_products']) && is_array($_REQUEST['recs']['tt_products'])) {
			$usedCreditpoints = floatval($_REQUEST['recs']['tt_products']['creditpoints']);
		}

		if ($status == 1 && $this->conf['creditpoints.'] && $usedCreditpoints) {

/* Added Els: update fe_user with amount of creditpoints (= exisitng amount - used_creditpoints - spended_creditpoints + saved_creditpoints */
			$fieldsArrayFeUsers['tt_products_creditpoints'] =
				floatval($GLOBALS['TSFE']->fe_user->user['tt_products_creditpoints'] -
					$usedCreditpoints
					/*+ GeneralUtility::_GP('creditpoints_saved')*/);
			if ($fieldsArrayFeUsers['tt_products_creditpoints'] < 0) {
				$fieldsArrayFeUsers['tt_products_creditpoints'] = 0;
			}
		}

/* Added Els: update fe_user with vouchercode */
		if ($status == 1 && $basketObj->recs['tt_products']['vouchercode'] != '') {
			// first check if vouchercode exist and is not their own vouchercode
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'fe_users', 'username=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($basketObj->recs['tt_products']['vouchercode'], 'fe_users')
			);

			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$uid_voucher = $row['uid'];
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
			if (($uid_voucher != '') && ($deliveryInfo['feusers_uid'] > 0) && ($deliveryInfo['feusers_uid'] != $uid_voucher) ) {
				$fieldsArrayFeUsers['tt_products_vouchercode'] = $basketObj->recs['tt_products']['vouchercode'];
			}
		}

		if ($status == 1 && $deliveryInfo['feusers_uid']) {
	/* Added Els: update user from vouchercode with 5 credits */
			tx_ttproducts_creditpoints_div::addCreditPoints($basketObj->recs['tt_products']['vouchercode'], $this->conf['voucher.']['price']);
		}

        foreach ($excludeFieldArray as $field) {
            if (isset($fieldsArrayFeUsers[$field])) {
                unset($fieldsArrayFeUsers[$field]);
            }
        }

		if ($GLOBALS['TSFE']->fe_user->user['uid'] && count($fieldsArrayFeUsers))	{
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('fe_users', 'uid='.intval($GLOBALS['TSFE']->fe_user->user['uid']), $fieldsArrayFeUsers);
		}

			// Default status_log entry
		$status_log = array();
		$status_log[] = array(
			'time' => time(),
			'info' => $this->conf['statusCodes.'][$fieldsArray['status']],
			'status' => $fieldsArray['status'],
			'comment' => $deliveryInfo['note']
		);
		$fieldsArray['status_log'] = serialize($status_log);
		$itemArray = array();
		$itemArray[$basketObj->viewTable->name] = $basketObj->itemArray;

			// Order Data serialized
		$fieldsArray['orderData'] = serialize(array(
			'html_output' 		=>	$orderConfirmationHTML,
			'delivery' 		=>	$deliveryInfo,
			'billing' 		=>	$billingInfo,
			'itemArray'		=>	$itemArray,
			'calculatedArray'	=>	$calculObj->getCalculatedArray(),
			'version'		=>	$this->config['version']
		));

			// Setting tstamp, deleted and tracking code
		$fieldsArray['tstamp'] = time();
		$fieldsArray['deleted'] = 0;
		$fieldsArray['tracking_code'] = $basketObj->order['orderTrackingNo'];
		$fieldsArray['agb'] = intval($billingInfo['agb']);

		if ($status == 1 && $this->conf['creditpoints.'] && $usedCreditpoints != '') {

			$fieldsArray['creditpoints'] = $usedCreditpoints;
			$fieldsArray['creditpoints_spended'] = intval(GeneralUtility::_GP('creditpoints_spended'));
			$fieldsArray['creditpoints_saved'] = intval(GeneralUtility::_GP('creditpoints_saved'));
			$fieldsArray['creditpoints_gifts'] = $cpArray['gift']['amount'];
		}

		if ($this->conf['errorLog']) {
			error_log('putRecord $fieldsArray = ' . print_r($fieldsArray, true) . chr(13), 3, $this->conf['errorLog']);
		}

        foreach ($excludeFieldArray as $field) {
            if (isset($fieldsArray[$field])) {
                unset($fieldsArray[$field]);
            }
        }

            // Saving the order record
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			'sys_products_orders',
			'uid=' . intval($orderUid),
			$fieldsArray
		);
	} //putRecord


	/**
	 * Creates M-M relations for the products with tt_products and maybe also the tt_products_articles table.
	 * Isn't really used yet, but later will be used to display stock-status by looking up how many items are
	 * already ordered.
	 *
	 */
	public function createMM ($orderUid, &$itemArray)	{
		$basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');

		if ($this->conf['useArticles'] != 2) {
			$productTable = $tablesObj->get('tt_products', false);
			$productTablename = $productTable->getTablename();
		} else {
			$productTablename = '';
		}

		if ($this->conf['useArticles'] > 0) {
			$articleTable = $tablesObj->get('tt_products_articles', false);
			$articleTablename = $articleTable->getTablename();
		} else {
			$articleTablename = '';
		}

			// First: delete any existing. Shouldn't be any
		$where = 'uid_local=' . intval($orderUid);
		$GLOBALS['TYPO3_DB']->exec_DELETEquery('sys_products_orders_mm_tt_products',$where);

		if (isset($itemArray) && is_array($itemArray)) {
			// loop over all items in the basket indexed by{ a sorting text
			foreach ($itemArray as $sort=>$actItemArray) {
				foreach ($actItemArray as $k1=>$actItem) {
					$row = &$actItem['rec'];
					$pid = intval($row['pid']);
					if (!isset($basketObj->getPidListObj()->pageArray[$pid]))	{
						// product belongs to another basket
						continue;
					}

					$insertFields = array (
						'uid_local' => intval($orderUid),
						'sys_products_orders_qty' => intval($actItem['count']),
						'uid_foreign' => intval($actItem['rec']['uid']),
						'tablenames' => $productTablename.','.$articleTablename
					);

					if ($this->conf['useArticles'] == 1 || $this->conf['useArticles'] == 3) {
						// get the article uid with these colors, sizes and gradings
						$row = $productTable->getArticleRow($actItem['rec'], $theCode);
						if ($row) {
							$insertFields['tt_products_articles_uid'] = intval($row['uid']);
						}
					}
					$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_products_orders_mm_tt_products', $insertFields);
				}
			}
		}
	}

	/**
	 * Fetches the basket itemArray from the order's serial data
	 */
	public function getItemArray ($row, &$calculatedArray, &$infoArray)	{
			// initialize order data.
		$orderData = unserialize($row['orderData']);
		$tmp = $orderData['itemArray'];
		$version = $orderData['version'];

		if (version_compare($version, '2.5.0', '>=') && is_array($tmp))	{
			$tableName = key($tmp);
			$itemArray = current($tmp);
		} else {
			$itemArray = (is_array($tmp) ? $tmp : array());
		}

		$tmp = $orderData['calculatedArray'];
		$calculatedArray = ($tmp ? $tmp : array());
		$infoArray = array();
		$infoArray['billing'] = $orderData['billing'];
		$infoArray['delivery'] = $orderData['delivery'];

		// overwrite with the most recent email address
		$infoArray['billing']['email'] = $row['email'];

 		return $itemArray;
	}

	/**
	 * Sets the user order in dummy order record
	 *
	 * @param	integer		$orderID: uid of dummy record
	 * @return	void
	 */
	public function setData ($orderUid, &$orderHTML, $status, $basketExtra) {

		$basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');

		$voucherObj = $tablesObj->get('voucher');
		if ($status == 1)	{
			$voucherObj->delete();
		}

		// get credit card info
		$card = $tablesObj->get('sys_products_cards');
		$cardUid = $card->getUid();

		// get bank account info
		$account = $tablesObj->get('sys_products_accounts');
		$accountUid = $account->getUid();

		$address = GeneralUtility::makeInstance('tx_ttproducts_info_view');
		if ($address->needsInit())	{
			echo 'internal error in tt_products (setData)';
			return;
		}

		$calculatedArray = $basketObj->getCalculatedArray();
		$rc = $this->putRecord(
			$orderUid,
			$cardUid,
			$accountUid,
			$this->conf['email_notify_default'],	// Email notification is set here. Default email address is delivery email contact
			$basketExtra['payment'][0] . ': ' . $basketExtra['payment.']['title'],
			$basketExtra['shipping'][0] . ': ' . $basketExtra['shipping.']['title'],
			$calculatedArray['priceTax']['total'],
			$orderHTML,
			$address,
			$status,
			$basketExtra
		);

		$this->createMM($orderUid, $basketObj->itemArray);
	}

	public function getOrderedProducts (
		$from,
		$uids,
		$where,
		$orderBy,
		$whereProducts,
		$pid_list,
		&$productRowArray,
		&$multiOrderArray
	) {
		$productRowArray = array();
		$multiOrderArray = array();

		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
		$productObj = $tablesObj->get('tt_products', false);
		$variantSeparator = ';';
		$local_cObj = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);

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

		$selectConf = array();
		if ($uids != '') {
            $selectConf['uidInList'] = $uids;
        }
		if ($pid_list != '') {
			$selectConf['pidInList'] = $pid_list;
		}

		$selectConf['selectFields'] =
			$alias . '.uid as uid, ' . $alias . '.email as email, ' . $alias . '.feusers_uid as feusers_uid, ' .
			$alias . '.crdate as crdate, ' . $alias . '.tracking_code as tracking_code, ' .
			'pa.sys_products_orders_qty AS quantity, ' . $productAlias1 . '.uid AS product_uid';

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
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$multiOrderArray[] = $row;

			$productRow = array();
			$productRow = $productObj->get($row['product_uid']);

			$addProduct = true;
            if ($addProduct) {
                $productRowArray[] = $productRow;
			}
		}
		return $productRowArray;
	}

}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_order.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_order.php']);
}

