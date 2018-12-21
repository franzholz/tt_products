<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2009 Franz Holzinger <franz@ttproducts.de>
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
		global $TSFE;

		$newId = 0;
		$pid = intval($this->conf['PID_sys_products_orders']);
		if (!$pid)	{
			$pid = intval($TSFE->id);
		}

		if ($TSFE->sys_page->getPage_noCheck($pid))	{
			$advanceUid = 0;
			if ($this->conf['advanceOrderNumberWithInteger'] || $this->conf['alwaysAdvanceOrderNumber'])	{
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'sys_products_orders', '', '', 'uid DESC', '1');
				list($prevUid) = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
				$GLOBALS['TYPO3_DB']->sql_free_result($res);

				if ($this->conf['advanceOrderNumberWithInteger']) {
					$rndParts = explode(',',$this->conf['advanceOrderNumberWithInteger']);
					$randomValue = rand(intval($rndParts[0]), intval($rndParts[1]));
					$advanceUid = $prevUid +
						(
							tx_div2007_core::intInRange($randomValue, 1)
						);
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
					version_compare(TYPO3_version, '6.0.0', '<') ||
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
			// insert order data in dummy record
	//		$this->setOrderData($newId);
		}
		return $newId;
	} // create


	/**
	 * Returns a blank order uid. If there was no order id already, a new one is created.
	 *
	 * Blank orders are marked deleted and with status=0 initialy. Blank orders are not necessarily finalized because users may abort instead of buying.
	 * A finalized order is marked 'not deleted' and with status=1.
	 * Returns this uid which is a blank order record uid.
	 */
	public function getBlankUid ()	{
		global $TSFE, $TYPO3_DB;;

	// an new orderUid has been created always because also payment systems can be used which do not accept a duplicate order id
		$basketObj = t3lib_div::makeInstance('tx_ttproducts_basket');
		$orderUid = 0;
		if (isset($basketObj->order['orderUid'])) {
			$orderUid = intval($basketObj->order['orderUid']);
		}

		if ($orderUid) {
			$res = $TYPO3_DB->exec_SELECTquery('uid', 'sys_products_orders', 'uid='.intval($orderUid).' AND hidden AND NOT status');	// Checks if record exists, is marked hidden (all blank orders are hidden by default) and is not finished.
		}

		if (!$orderUid || !$TYPO3_DB->sql_num_rows($res) || $this->conf['alwaysAdvanceOrderNumber'])	{
			$orderUid = $this->create();
			$basketObj->order['orderUid'] = $orderUid;
			$basketObj->order['orderDate'] = time();
			$basketObj->order['orderTrackingNo'] = $this->getNumber($orderUid) . '-' . strtolower(substr(md5(uniqid(time())), 0, 6));
			$TSFE->fe_user->setKey('ses', 'order', $basketObj->order);
		}

		if ($res) {
			$TYPO3_DB->sql_free_result($res);
		}

		return $orderUid;
	} // getBlankUid


	public function getUid ()	{
		$basketObj = t3lib_div::makeInstance('tx_ttproducts_basket');
		$rc = $basketObj->order['orderUid'];
		return $rc;
	}


	function clearUid ()	{
		global $TSFE;

		$this->basket->order['orderUid'] = '';
		$TSFE->fe_user->setKey('ses', 'order', array());
	}


	/**
	 * Returns the order record if $orderUid.
	 * If $tracking is set, then the order with the tracking number is fetched instead.
	 */
	public function getRecord ($orderUid, $tracking = '')	{
		global $TYPO3_DB;

		if (
			empty($tracking) &&
			!$orderUid
		) {
			return FALSE;
		}

		$res = $TYPO3_DB->exec_SELECTquery('*', 'sys_products_orders' , ($tracking ? 'tracking_code=' . $TYPO3_DB->fullQuoteStr($tracking, 'sys_products_orders') : 'uid=' . intval($orderUid)) . ' AND NOT deleted');
		$rc = $TYPO3_DB->sql_fetch_assoc($res);
		$TYPO3_DB->sql_free_result($res);
		return $rc;
	} //getRecord


	/**
	 * This returns the order-number (opposed to the order_uid) for display in the shop, confirmation notes and so on.
	 * Basically this prefixes the .orderNumberPrefix, if any
	 */
	public function getNumber ($orderUid)	{
		$orderNumberPrefix = substr($this->conf['orderNumberPrefix'], 0, 30);
		if (($position = strpos($orderNumberPrefix, '%')) !== FALSE)	{
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
		$status=0
	)	{
		global $TYPO3_DB;
		global $TSFE;

		$basketObj = t3lib_div::makeInstance('tx_ttproducts_basket');


		$billingInfo = $address->infoArray['billing'];
		$deliveryInfo = $address->infoArray['delivery'];

		if (!isset($deliveryInfo)) {
			$deliveryInfo = $billingInfo;
		}

		$feusers_uid = $billingInfo['feusers_uid'];
		if (!$feusers_uid && isset($TSFE->fe_user->user) && is_array($TSFE->fe_user->user) && $TSFE->fe_user->user['uid'])	{
			$feusers_uid = $TSFE->fe_user->user['uid'];
		}

			// Fix delivery address
		if ($deliveryInfo['date_of_birth'])	{
			$dateArray = t3lib_div::trimExplode ('-', $deliveryInfo['date_of_birth']);

			if (
				tx_div2007_core::testInt($dateArray[0]) &&
				tx_div2007_core::testInt($dateArray[1]) &&
				tx_div2007_core::testInt($dateArray[2])
			) {
				$dateBirth = mktime(0,0,0,$dateArray[1], $dateArray[0], $dateArray[2]);
			}
		}

			// Saving order data
		$fieldsArray=array();
        $tablename = $this->getTablename();
        $excludeArray = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['exclude.'];
        $excludeFieldArray = array();
        if (
            isset($excludeArray) &&
            is_array($excludeArray) &&
            isset($excludeArray[$tablename])
        ) {
            $excludeFieldArray = t3lib_div::trimExplode(',', $excludeArray[$tablename]);
        }

		$fieldsArray['feusers_uid'] = $feusers_uid;
		$fieldsArray['first_name'] = $deliveryInfo['first_name'];
		$fieldsArray['last_name'] = $deliveryInfo['last_name'];
		$fieldsArray['company'] = $deliveryInfo['company'];
		$fieldsArray['name'] = ($deliveryInfo['name'] ? $deliveryInfo['name'] : $deliveryInfo['last_name'].' '.$deliveryInfo['first_name']);
		$fieldsArray['salutation'] = $deliveryInfo['salutation'];
		$fieldsArray['address'] = $deliveryInfo['address'];
		$fieldsArray['zip'] = $deliveryInfo['zip'];
		$fieldsArray['city'] = $deliveryInfo['city'];
		$fieldsArray['country'] = $deliveryInfo['country'];
		$fieldsArray['telephone'] = $deliveryInfo['telephone'];
		$fieldsArray['fax'] = $deliveryInfo['fax'];
		$fieldsArray['email'] = $deliveryInfo['email'];
		$fieldsArray['email_notify'] = $email_notify;

		$fieldsArray['business_partner'] = $deliveryInfo['tt_products_business_partner'];
		$fieldsArray['organisation_form'] = $deliveryInfo['tt_products_organisation_form'];

			// can be changed after order is set.
		$fieldsArray['payment'] = $payment;
		$fieldsArray['shipping'] = $shipping;
		$fieldsArray['amount'] = $amount;
		$fieldsArray['desired_date'] = $deliveryInfo['desired_date'];
		$fieldsArray['desired_time'] = $deliveryInfo['desired_time'];
		$fieldsArray['status'] = $status;	// If 1, then this means, "Order confirmed on website, next step: confirm from shop that order is received"
		if ($status == 1)	{
			$fieldsArray['hidden'] = 0;
		}

		$fieldsArray['note'] = $deliveryInfo['note'];
		$fieldsArray['date_of_birth'] = $dateBirth;
		$fieldsArray['radio1'] = $deliveryInfo['radio1'];

		$giftServiceArticleArray = array();

		if (isset($basketObj->basketExt) && is_array($basketObj->basketExt)) {
			foreach ($basketObj->basketExt as $tmpUid => $tmpSubArr)	{
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
		$fieldsArray['foundby'] = $deliveryInfo['foundby'];
		$fieldsArray['client_ip'] = t3lib_div::getIndpEnv('REMOTE_ADDR');
		$fieldsArray['cc_uid'] = $cardUid;
		$fieldsArray['ac_uid'] = $accountUid;
		$fieldsArray['giftcode'] = $basketObj->recs['tt_products']['giftcode'];
		$fieldsArray['sys_language_uid'] = $TSFE->config['config']['sys_language_uid'];

		if ($address->infoArray['billing']['tt_products_vat'] != '')	{
			$fieldsArray['vat_id'] = $address->infoArray['billing']['tt_products_vat'];

			$paymentshippingObj = t3lib_div::makeInstance('tx_ttproducts_paymentshipping');
			$taxPercentage = $paymentshippingObj->getReplaceTaxPercentage();
			if (doubleval($taxPercentage) == 0)	{
				$fieldsArray['tax_mode'] = 1;
			}
		}

/* Added Els: update fe_user with amount of creditpoints and subtract creditpoints used in order*/
		$fieldsArrayFeUsers = array();
		$uid_voucher = ''; // define it here
		$cpArray = $TSFE->fe_user->getKey('ses','cp');

		if ($deliveryInfo['date_of_birth'])	{
			$fieldsArrayFeUsers['date_of_birth'] = $dateBirth;
		}

		$usedCreditpoints = 0;
		if (isset($_REQUEST['recs']) && is_array($_REQUEST['recs']) && isset($_REQUEST['recs']['tt_products']) && is_array($_REQUEST['recs']['tt_products'])) {
			$usedCreditpoints = floatval($_REQUEST['recs']['tt_products']['creditpoints']);
		}

		if ($status == 1 && $this->conf['creditpoints.'] && $usedCreditpoints) {

			$fieldsArrayFeUsers['tt_products_creditpoints'] =
				floatval($TSFE->fe_user->user['tt_products_creditpoints'] -
					$usedCreditpoints
					/*+ t3lib_div::_GP('creditpoints_saved')*/);
			if ($fieldsArrayFeUsers['tt_products_creditpoints'] < 0) {
				$fieldsArrayFeUsers['tt_products_creditpoints'] = 0;
			}
		}

/* Added Els: update fe_user with vouchercode */
		if ($status == 1 && $basketObj->recs['tt_products']['vouchercode'] != '') {
			// first check if vouchercode exist and is not their own vouchercode

			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'fe_users', 'username=' . $TYPO3_DB->fullQuoteStr($basketObj->recs['tt_products']['vouchercode'], 'fe_users'));
			if ($row = $TYPO3_DB->sql_fetch_assoc($res)) {
				$uid_voucher = $row['uid'];
			}
			$TYPO3_DB->sql_free_result($res);
			if (($uid_voucher != '') && ($address->infoArray['delivery']['feusers_uid'] > 0) && ($address->infoArray['delivery']['feusers_uid'] != $uid_voucher) ) {
				$fieldsArrayFeUsers['tt_products_vouchercode'] = $basketObj->recs['tt_products']['vouchercode'];
			}
		}

		if ($status == 1 && $address->infoArray['delivery']['feusers_uid']) {
	/* Added Els: update user from vouchercode with 5 credits */
			tx_ttproducts_creditpoints_div::addCreditPoints($basketObj->recs['tt_products']['vouchercode'], $this->conf['voucher.']['price']);
		}

        foreach ($excludeFieldArray as $field) {
            if (isset($fieldsArrayFeUsers[$field])) {
                unset($fieldsArrayFeUsers[$field]);
            }
        }

		if ($TSFE->fe_user->user['uid'] && count($fieldsArrayFeUsers))	{
			$TYPO3_DB->exec_UPDATEquery('fe_users', 'uid=' . intval($TSFE->fe_user->user['uid']), $fieldsArrayFeUsers);
		}

			// Default status_log entry
		$status_log=array();
		$status_log[] = array(
			'time' => time(),
			'info' => $this->conf['statusCodes.'][$fieldsArray['status']],
			'status' => $fieldsArray['status'],
			'comment' => $address->infoArray['delivery']['note']
		);
		$fieldsArray['status_log']=serialize($status_log);
		$itemArray = array();
		$itemArray[$basketObj->viewTable->name] = $basketObj->itemArray;

			// Order Data serialized
		$fieldsArray['orderData'] = serialize(array(
			'html_output' 		=>	$orderConfirmationHTML,
			'delivery' 		=>	$address->infoArray['delivery'],
			'billing' 		=>	$address->infoArray['billing'],
			'itemArray'		=>	$itemArray,
			'calculatedArray'	=>	$basketObj->calculatedArray,
			'version'		=>	$this->config['version']
		));

			// Setting tstamp, deleted and tracking code
		$fieldsArray['tstamp'] = time();
		$fieldsArray['deleted'] = 0;
		$fieldsArray['tracking_code'] = $basketObj->order['orderTrackingNo'];
		$fieldsArray['agb'] = intval($address->infoArray['billing']['agb']);

		if ($status == 1 && $this->conf['creditpoints.'] && $usedCreditpoints != '') {
			$fieldsArray['creditpoints'] = $usedCreditpoints;
			$fieldsArray['creditpoints_spended'] = t3lib_div::_GP('creditpoints_spended');
			$fieldsArray['creditpoints_saved'] = t3lib_div::_GP('creditpoints_saved');
			$fieldsArray['creditpoints_gifts'] = $cpArray['gift']['amount'];
		}

        foreach ($excludeFieldArray as $field) {
            if (isset($fieldsArray[$field])) {
                unset($fieldsArray[$field]);
            }
        }

			// Saving the order record
		$TYPO3_DB->exec_UPDATEquery(
			'sys_products_orders',
			'uid='.intval($orderUid),
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
		global $TYPO3_DB;

		$basketObj = t3lib_div::makeInstance('tx_ttproducts_basket');
		$tablesObj = t3lib_div::makeInstance('tx_ttproducts_tables');
		if ($this->conf['useArticles'] != 2) {
			$productTable = $tablesObj->get('tt_products', FALSE);
			$productTablename = $productTable->getTablename();
		} else {
			$productTablename = '';
		}

		if ($this->conf['useArticles'] > 0) {
			$articleTable = $tablesObj->get('tt_products_articles', FALSE);
			$articleTablename = $articleTable->getTablename();
		} else {
			$articleTablename = '';
		}

			// First: delete any existing. Shouldn't be any
		$where='sys_products_orders_uid='.intval($orderUid);
		$TYPO3_DB->exec_DELETEquery('sys_products_orders_mm_tt_products', $where);

		if (isset($itemArray) && is_array($itemArray)) {
			// loop over all items in the basket indexed by a sorting text
			foreach ($itemArray as $sort=>$actItemArray) {
				foreach ($actItemArray as $k1=>$actItem) {
					$row = &$actItem['rec'];
					$pid = intval($row['pid']);
					if (!isset($basketObj->getPidListObj()->pageArray[$pid]))	{
						// product belongs to another basket
						continue;
					}

					$insertFields = array (
						'sys_products_orders_uid' => intval($orderUid),
						'sys_products_orders_qty' => intval($actItem['count']),
						'tt_products_uid' => intval($actItem['rec']['uid']),
						'tablenames' => $productTablename.','.$articleTablename
					);

					if ($this->conf['useArticles'] == 1 || $this->conf['useArticles'] == 3) {
						// get the article uid with these colors, sizes and gradings
						$row = $productTable->getArticleRow ($actItem['rec'], $theCode);
						if ($row) {
							$insertFields['tt_products_articles_uid'] = intval($row['uid']);
						}
					}
					$TYPO3_DB->exec_INSERTquery('sys_products_orders_mm_tt_products', $insertFields);
				}
			}
		}
	}


	/**
	 * Sets the user order in dummy order record
	 *
	 * @param	integer		$orderID: uid of dummy record
	 * @return	void
	 */
	function setData ($orderUid, &$orderHTML, $status) {

		$basketObj = t3lib_div::makeInstance('tx_ttproducts_basket');
		$tablesObj = t3lib_div::makeInstance('tx_ttproducts_tables');

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

		$address = t3lib_div::makeInstance('tx_ttproducts_info_view');
		if ($address->needsInit())	{
			echo 'internal error in tt_products (setData)';
			return;
		}

		$rc = $this->putRecord(
			$orderUid,
			$cardUid,
			$accountUid,
			$this->conf['email_notify_default'],	// Email notification is set here. Default email address is delivery email contact
			$basketObj->basketExtra['payment'][0] . ': ' . $basketObj->basketExtra['payment.']['title'],
			$basketObj->basketExtra['shipping'][0] . ': ' . $basketObj->basketExtra['shipping.']['title'],
			$basketObj->calculatedArray['priceTax']['total'],
			$orderHTML,
			$address,
			$status
		);

		$this->createMM($orderUid, $basketObj->itemArray);
	}

}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_order.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_order.php']);
}


?>
