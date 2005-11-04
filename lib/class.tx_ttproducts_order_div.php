<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2005 Franz Holzinger <kontakt@fholzinger.com>
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
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
 * Part of the tt_products (Shopping System) extension.
 *
 * div functions
 *
 * $Id$
 *
 * @author	Franz Holzinger <kontakt@fholzinger.com>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

require_once (PATH_BE_ttproducts.'lib/class.tx_ttproducts_view_div.php');


class tx_ttproducts_order_div {


	// **************************
	// ORDER related functions
	// **************************

	/**
	 * Create a new order record
	 *
	 * This creates a new order-record on the page with pid, .PID_sys_products_orders. That page must exist!
	 * Should be called only internally by eg. getBlankOrderUid, that first checks if a blank record is already created.
	 */
	function createOrder()	{
		global $TSFE;

		$newId = 0;
		$pid = intval($this->conf['PID_sys_products_orders']);
		if (!$pid)	$pid = intval($TSFE->id);

		if ($TSFE->sys_page->getPage_noCheck ($pid))	{
			$advanceUid = 0;
			if ($this->conf['advanceOrderNumberWithInteger'] || $this->conf['alwaysAdvanceOrderNumber'])	{
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'sys_products_orders', '', '', 'uid DESC', '1');
				list($prevUid) = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);

				if ($this->conf['advanceOrderNumberWithInteger']) {
					$rndParts = explode(',',$this->conf['advanceOrderNumberWithInteger']);
					$advanceUid = $prevUid+t3lib_div::intInRange(rand(intval($rndParts[0]),intval($rndParts[1])),1);
				} else {
					$advanceUid = $prevUid + 1;
				}
			}

			$time = time();
			$insertFields = array(
				'pid' => $pid,
				'tstamp' => $time,
				'crdate' => $time,
				'deleted' => 1
			);
			if ($advanceUid > 0)	{
				$insertFields['uid'] = $advanceUid;
			}

			$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_products_orders', $insertFields);

			$newId = $GLOBALS['TYPO3_DB']->sql_insert_id();
		}
		return $newId;
	} // createOrder


	/**
	 * Returns a blank order uid. If there was no order id already, a new one is created.
	 *
	 * Blank orders are marked deleted and with status=0 initialy. Blank orders are not necessarily finalized because users may abort instead of buying.
	 * A finalized order is marked 'not deleted' and with status=1.
	 * Returns this uid which is a blank order record uid.
	 */
	function getBlankOrderUid()	{
		global $TSFE;

	// an new orderUid has been created always because also payment systems can be used which do not accept a duplicate order id
		$orderUid = intval($this->recs['tt_products']['orderUid']);
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'sys_products_orders', 'uid='.intval($orderUid).' AND deleted AND NOT status');	// Checks if record exists, is marked deleted (all blank orders are deleted by default) and is not finished.
		if (!$GLOBALS['TYPO3_DB']->sql_num_rows($res) || $this->conf['alwaysAdvanceOrderNumber'])	{
			$orderUid = tx_ttproducts_order_div::createOrder();
			$this->recs['tt_products']['orderUid'] = $orderUid;
			$this->recs['tt_products']['orderDate'] = time();
			$this->recs['tt_products']['orderTrackingNo'] = tx_ttproducts_order_div::getOrderNumber($orderUid).'-'.strtolower(substr(md5(uniqid(time())),0,6));
			$TSFE->fe_user->setKey('ses','recs',$this->recs);
		}
		return $orderUid;
	} // getBlankOrderUid


	/**
	 * Returns the orderRecord if $orderUid.
	 * If $tracking is set, then the order with the tracking number is fetched instead.
	 */
	function getOrderRecord($orderUid,$tracking='')	{
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_products_orders', ($tracking ? 'tracking_code="'.$GLOBALS['TYPO3_DB']->quoteStr($tracking, 'sys_products_orders').'"' : 'uid='.intval($orderUid)).' AND NOT deleted');
		return $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
	} //getOrderRecord


	/**
	 * This returns the order-number (opposed to the order_uid) for display in the shop, confirmation notes and so on.
	 * Basically this prefixes the .orderNumberPrefix, if any
	 */
	function getOrderNumber($orderUid)	{
		$orderNumberPrefix = substr($this->conf['orderNumberPrefix'],0,10);
		if ($orderNumberPrefix[0]=='%')
			$orderNumberPrefix = date(substr($orderNumberPrefix, 1));
		return $orderNumberPrefix.$orderUid;
	} // getOrderNumber


	/**
	 * Saves the orderRecord and returns the result
	 * 
	 */
	function putOrderRecord($orderUid,&$deliveryInfo, $feusers_uid, $email_notify, $payment, $shipping, $amount, &$orderConfirmationHTML)	{
		global $TYPO3_DB;
		
			// Fix delivery address
		tx_ttproducts_view_div::mapPersonIntoToDelivery();	// This maps the billing address into the blank fields of the delivery address
//		$mainMarkerArray['###EXTERNAL_COBJECT###'] = $this->externalCObject.'';

			// Saving order data
		$fieldsArray=array();
		$fieldsArray['note']=$deliveryInfo['note'];
/* Added Els: introduce a field into sys_products_orders containing the uid of the fe_user */
		$fieldsArray['feusers_uid']=$feusers_uid;
		$fieldsArray['name']=$deliveryInfo['name'];
		$fieldsArray['telephone']=$deliveryInfo['telephone'];
		$fieldsArray['fax']=$deliveryInfo['fax'];
		$fieldsArray['email']=$deliveryInfo['email'];
		$fieldsArray['email_notify']= $email_notify;

			// can be changed after order is set.
		$fieldsArray['payment']=$payment;
		$fieldsArray['shipping']=$shipping;
		$fieldsArray['amount']=$amount;
		$fieldsArray['desired_date']=$deliveryInfo['desired_date'];
		$fieldsArray['status']=1;	// This means, "Order confirmed on website, next step: confirm from shop that order is received"

/* Added Els: update fe_user with amount of creditpoints and subtract creditpoints used in order*/
		$fieldsArrayFeUsers = array();
		$uid_voucher = ''; // define it here
		/* example:
  creditpoints {
  10.where =
  10.type = price
  10.prod.1   = 0.02
  10.prod.101 = 0.04
  10.prod.501 = 0.06
}
		 */
		if ($this->conf['creditpoints.']) {
			$creditpoints = tx_ttproducts_creditpoints_div::getCreditPoints($fieldsArray['amount']);
/* Added els4: update fe_user with amount of creditpoints (= exisitng amount - used_creditpoints - spended_creditpoints + saved_creditpoints */
//			$fieldsArrayFeUsers['tt_products_creditpoints'] = $TSFE->fe_user->user['tt_products_creditpoints'] + ($creditpoints * $this->calculatedArray['priceTax']['total']) - $this->recs['tt_products']['creditpoints'];
			$fieldsArrayFeUsers['tt_products_creditpoints'] = $TSFE->fe_user->user['tt_products_creditpoints'] - $this->recs['tt_products']['creditpoints'] - t3lib_div::_GP('creditpoints_spended') + t3lib_div::_GP('creditpoints_saved');
		}

/* Added Els: update fe_user with vouchercode */
		if ($this->recs['tt_products']['vouchercode'] != '') {
			// first check if vouchercode exist and is not their own vouchercode
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'fe_users', 'username="'.$this->recs['tt_products']['vouchercode'].'"');
			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$uid_voucher = $row['uid'];
			}
			if (($uid_voucher != '') && ($this->deliveryInfo['feusers_uid'] != $uid_voucher) ) {
				$fieldsArrayFeUsers['tt_products_vouchercode'] = $this->recs['tt_products']['vouchercode'];
			}
		}

		if ($this->deliveryInfo['feusers_uid']) {
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('fe_users', 'uid='.$this->deliveryInfo['feusers_uid'], $fieldsArrayFeUsers);
	/* Added ELS2: update user from vouchercode with 5 credits */
			tx_ttproducts_creditpoints_div::addCreditPoints($this->recs['tt_products']['vouchercode'], 5);
		}


/*
		//<-- MKL 2004.09.21
		$fieldsArray['forename']=$this->personInfo['forename'];
		$fieldsArray['company']=$this->personInfo['company'];
		$fieldsArray['vat_id']=$this->personInfo['vat_id'];
		$fieldsArray['street']=$this->deliveryInfo['street'];
		$fieldsArray['street_n1']=$this->deliveryInfo['street_n1'];
		$fieldsArray['street_n2']=$this->deliveryInfo['street_n2'];
		$fieldsArray['city']=$this->deliveryInfo['city'];
		$fieldsArray['zip']=$this->deliveryInfo['zip'];
		$fieldsArray['country_code']=$this->personInfo['country_code'];
		$fieldsArray['client_ip']=t3lib_div::getIndpEnv('REMOTE_ADDR');
		//--> MKL 2004.09.21
*/

				// Default status_log entry
		$status_log=array();
		$status_log[] = array(
			'time' => time(),
			'info' => $this->conf['statusCodes.'][$fieldsArray['status']],
			'status' => $fieldsArray['status'],
			'comment' => $this->deliveryInfo['note']
		);
		$fieldsArray['status_log']=serialize($status_log);

			// Order Data serialized
		$fieldsArray['orderData']=serialize(array(
				'html_output' 			=>	$orderConfirmationHTML,
				'deliveryInfo' 			=>	$this->deliveryInfo,
				'personInfo' 			=>	$this->personInfo,
				'itemArray'				=>	$this->itemArray,
				'calculatedArray'		=>	$this->calculatedArray
		));

			// Setting tstamp, deleted and tracking code
		$fieldsArray['tstamp']=time();
		$fieldsArray['deleted']=0;
		$fieldsArray['tracking_code']=$this->recs['tt_products']['orderTrackingNo'];
		$fieldsArray['agb']		= $this->personInfo['agb'];
/* Added Els: write creditpointvalue into sys_products_order */
		$fieldsArray['creditpoints'] = $this->recs['tt_products']['creditpoints'];
/* Added Els4: write creditpoint_spended and saved value into sys_products_order */
		$fieldsArray['creditpoints_spended'] = t3lib_div::_GP('creditpoints_spended');
		$fieldsArray['creditpoints_saved'] = t3lib_div::_GP('creditpoints_saved');
/* Added Els6: write creditpoint_gifts value into sys_products_order */
		$fieldsArray['creditpoints_gifts'] = t3lib_div::_GP('creditpoints_gifts');

			// Saving the order record
		$TYPO3_DB->exec_UPDATEquery('sys_products_orders', 'uid='.intval($orderUid), $fieldsArray);
		
	} //putOrderRecord




	/**
	 * Creates M-M relations for the products with tt_products table. Isn't really used yet, but later will be used to display stock-status by looking up how many items are already ordered.
	 * 
	 */
	function createMM($orderUid, &$itemArray)	{
		global $TYPO3_DB;

			// First: delete any existing. Shouldn't be any
		$where='sys_products_orders_uid='.$orderUid;
		$res = $TYPO3_DB->exec_DELETEquery('sys_products_orders_mm_tt_products',$where);

			// Second: Insert a new relation for each ordered item
		reset($itemArray);

		// loop over all items in the basket indexed by page and itemnumber
		foreach ($itemArray as $pid=>$pidItem) {
			foreach ($pidItem as $itemnumber=>$actItemArray) {
				if ($this->conf['useArticles']) {
					foreach ($actItemArray as $k1=>$actItem) {
						// get the article uid with these colors, sizes and gradings
						$query='uid_product=\''.intval($actItem['rec']['uid']).'\' AND color=\''.$actItem['rec']['color'].'\' AND size=\''.$actItem['rec']['size'].'\' AND gradings=\''.$actItem['rec']['gradings'].'\'';

						$res = $TYPO3_DB->exec_SELECTquery('uid', 'tt_products_articles', $query);

						if ($row = $TYPO3_DB->sql_fetch_assoc($res)) {
							$insertFields = array (
								'sys_products_orders_uid' => $orderUid,
								'sys_products_orders_qty' => intval($actItem['count']),
								'tt_products_uid' => intval($actItem['rec']['uid']),
								'tt_products_articles_uid' => intval($row['uid'])
							);
							$TYPO3_DB->exec_INSERTquery('sys_products_orders_mm_tt_products', $insertFields);
						}
					}
				} else {
					foreach ($actItemArray as $k1=>$actItem) {
						$insertFields = array (
							'sys_products_orders_uid' => $orderUid,
							'sys_products_orders_qty' => intval($actItem['count']),
							'tt_products_uid' => intval($actItem['rec']['uid'])
						);
						$TYPO3_DB->exec_INSERTquery('sys_products_orders_mm_tt_products', $insertFields);
					}
				}
			}
		}
	}


/* Added Els2: Displays and manages the orders */
/* Added Els4: message if no orders available and complete change */
/* Added Els5: minor modifications */
/* Added Els6: minor modifications */
   function orders_display($theCode) {
       global $TSFE;

       $feusers_uid = $TSFE->fe_user->user['uid'];

       if (!$feusers_uid)
           return $this->cObj->getSubpart($this->templateCode,tx_ttproducts_view_div::spMarker('###MEMO_NOT_LOGGED_IN###'));

       $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_products_orders', 'feusers_uid='.$feusers_uid.' AND NOT deleted');

       $content=$this->cObj->getSubpart($this->templateCode,tx_ttproducts_view_div::spMarker('###ORDERS_LIST_TEMPLATE###'));
       if (!$GLOBALS['TYPO3_DB']->sql_num_rows($res)) {

           $content .= "<p style='margin-left=40;'><br>U heeft nog geen bestellingen gedaan.</p>";
	   } else {

           $content .= "

		      <h3 class='groupheading'>Klantnummer: $feusers_uid</h3>
			  <table width='91%' border='0' cellpadding='0' cellspacing='0'>
              <tr>
                <td width='24%' class='tableheading'>Datum</td>
                <td width='54%' class='tableheading'>Factuurnummer</td>
                <td width='13%' class='tableheading-rightalign'>Kurken</td>
                <td width='4%'  class='recycle-bin'>&nbsp;</td>
                <td width='5%'  class='recycle-bin'>&nbsp;</td>
              </tr>";

           $tot_creditpoints_saved = 0;
           $tot_creditpoints_spended= 0;
           $tot_creditpoints_gifts= 0;
           $this->orders = array();
           while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
               $this->orders[$row['uid']] = $row['tracking_code'];
               $content .= "<tr><td>".$this->cObj->stdWrap($row['crdate'],$this->conf['orderDate_stdWrap.'])."</td>";
/* Added Els6: ordernummer dynamically */
               $content .= "<td>".tx_ttproducts_order_div::getOrderNumber($row['uid'])." | <a href=index.php?id=215&tracking=".$row['tracking_code'].">bekijk deze factuur</a> &raquo;</td>";
               $content .= "<td class='rowtotal'>".($row['creditpoints_saved'] + $row['creditpoints_gifts'] - $row['creditpoints_spended'] - $row['creditpoints'])."</td>
                 <td class='recycle-bin'><img src='fileadmin/html/img/bullets/kurk.gif' width='17' height='17'></td>
                 <td class='recycle-bin'>&nbsp;</td>";
               // total amount of saved creditpoints
               $tot_creditpoints_saved += $row['creditpoints_saved'];
               // total amount of spended creditpoints
               $tot_creditpoints_spended+= $row['creditpoints_spended'];
               // total amount of creditpoints from gifts
               $tot_creditpoints_gifts+= $row['creditpoints_gifts'];
           }

           $res1 = $GLOBALS['TYPO3_DB']->exec_SELECTquery('username ', 'fe_users', 'uid="'.$feusers_uid.'"');
           if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res1)) {
               $username = $row['username'];
           }

           $content .= "
     </tr>
     <tr>
       <td class='noborder' colspan='5'><br></td>
     </tr>
     <tr>
       <td class='noborder'></td>
       <td><span class='noborder'>Gespaarde kurken</span></td>
       <td class='rowtotal'>".number_format($tot_creditpoints_saved,0)."</td>
       <td class='recycle-bin'><img src='fileadmin/html/img/bullets/kurk.gif' width='17' height='17'></td>
       <td class='recycle-bin'>&nbsp;</td>
     </tr>
     <tr>
       <td class='noborder'></td>
       <td><span class='noborder'>Besteedde kurken</span></td>
       <td class='rowtotal'>- ".number_format($tot_creditpoints_spended,0)."</td>
       <td class='recycle-bin'><img src='fileadmin/html/img/bullets/kurk.gif' width='17' height='17'></td>
       <td class='recycle-bin'>&nbsp;</td>
     </tr>
     <tr>
       <td class='noborder'></td>
       <td><span class='noborder'>Verdiende kurken met cadeaubonnnen</span></td>
       <td class='rowtotal'>".$tot_creditpoints_gifts."</td>
       <td class='recycle-bin'><img src='fileadmin/html/img/bullets/kurk.gif' width='17' height='17'></td>
       <td class='recycle-bin'>&nbsp;</td>
     </tr>
     <tr>
       <td class='noborder'></td>
       <td class='noborder'>Verdiende kurken met uw vouchercode <i>".$row['username']."</i></td>";

           $res2 = $GLOBALS['TYPO3_DB']->exec_SELECTquery('username', 'fe_users', 'tt_products_vouchercode="'.$username.'"');
           $num_rows = $GLOBALS['TYPO3_DB']->sql_num_rows($res2);

           $content .= "<td class='lastrow'>".($num_rows * 5).'</td>';

           $content .= "
       <td class='recycle-bin'><img src='fileadmin/html/img/bullets/kurk.gif' width='17' height='17'></td>
       <td class='recycle-bin'>&nbsp;</td>
     </tr>
     <tr>
       <td class='noborder'></td>
       <td class='subtotaal'>Uw kurkensaldo (per ".date('d M Y').")</td>";

           $res3 = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tt_products_creditpoints ', 'fe_users', 'uid='.$feusers_uid.' AND NOT deleted');
           $this->creditpoints = array();
           while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res3)) {
               $this->creditpoints[$row['uid']] = $row['tt_products_creditpoints'];
               $content .= "<td class='prijssubtotal'>";
               $content .= $row['tt_products_creditpoints'];
               $content .= '</td>';
           }
           $content .= "
       <td class='recycle-bin'><img src='fileadmin/html/img/bullets/kurk.gif' width='17' height='17'></td>
       <td class='recycle-bin'>&nbsp;</td>
     </tr>
    </table>
";
	   }

       return $content;

   }


}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_order_div.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_order_div.php']);
}


?>
