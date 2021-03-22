<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2010 Franz Holzinger (franz@ttproducts.de)
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


class tx_ttproducts_order_view extends tx_ttproducts_table_base_view {
	public $marker='ORDER';

	/** add the markers for uid, date and the tracking number which is stored in the basket recs */
	public function getBasketRecsMarkerArray (&$markerArray, $orderArray)	{
        $local_cObj = \JambageCom\Div2007\Utility\FrontendUtility::getContentObjectRenderer();

			// order
		if (
			isset($orderArray) &&
			is_array($orderArray) &&
			isset($orderArray['orderUid']) &&
			isset($orderArray['orderDate']) &&
			isset($orderArray['orderTrackingNo'])
		) {
			$orderObj = $this->getModelObj();
			$markerArray['###ORDER_UID###'] = $orderArray['orderUid'];

				// Order:	NOTE: Data exist only if the order->getBlankUid() has been called. Therefore this field in the template should be used only when an order has been established
			$markerArray['###ORDER_ORDER_NO###'] = $orderObj->getNumber($orderArray['orderUid']);

			$markerArray['###ORDER_DATE###'] = $local_cObj->stdWrap($orderArray['orderDate'], $this->conf['orderDate_stdWrap.']);
			$markerArray['###ORDER_TRACKING_NO###'] = $orderArray['orderTrackingNo'];
		} else {
			$markerArray['###ORDER_UID###'] = $markerArray['###ORDER_ORDER_NO###'] = '';
			$markerArray['###ORDER_DATE###'] = '';
			$markerArray['###ORDER_TRACKING_NO###'] = '';
		}
	}

	public function printView (&$templateCode, &$errorCode)	 {
        $local_cObj = \JambageCom\Div2007\Utility\FrontendUtility::getContentObjectRenderer();
		$parser = $local_cObj;
        if (
            defined('TYPO3_version') &&
            version_compare(TYPO3_version, '7.0.0', '>=')
        ) {
            $parser = tx_div2007_core::newHtmlParser(false);
        }

		$feusers_uid = $GLOBALS['TSFE']->fe_user->user['uid'];
		$priceViewObj = GeneralUtility::makeInstance('tx_ttproducts_field_price_view');
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
		$subpartmarkerObj = GeneralUtility::makeInstance('tx_ttproducts_subpartmarker');
		$markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
		$globalMarkerArray = $markerObj->getGlobalMarkerArray();
		$functablename = 'sys_products_orders';
		$orderObj = $tablesObj->get($functablename); // order

			// order
		$orderObj = $tablesObj->get('sys_products_orders');
		if (!$feusers_uid)	{
			$frameWork = tx_div2007_core::getSubpart($templateCode, $subpartmarkerObj->spMarker('###MEMO_NOT_LOGGED_IN###'));
			$content = $parser->substituteMarkerArray($frameWork, $globalMarkerArray);
			return $content;
		}

		$where = 'feusers_uid = ' . intval($feusers_uid) . $orderObj->getTableObj()->enableFields() . ' ORDER BY crdate';
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_products_orders', $where);
		$templateArea = 'ORDERS_LIST_TEMPLATE';

		$frameWork = tx_div2007_core::getSubpart($templateCode, $subpartmarkerObj->spMarker('###' . $templateArea . '###'));

		if (!$frameWork) {
			$templateObj = GeneralUtility::makeInstance('tx_ttproducts_template');
			$errorCode[0] = 'no_subtemplate';
			$errorCode[1] = '###'.$templateArea.'###';
			$errorCode[2] = $templateObj->getTemplateFile();
			return '';
		}

		$content = $parser->substituteMarkerArray($frameWork, $globalMarkerArray);
		$orderitem = tx_div2007_core::getSubpart($content, '###ORDER_ITEM###');
		$count = $GLOBALS['TYPO3_DB']->sql_num_rows($res);

		if ($count) {
			// Fill marker arrays
			$markerArray=array();
			$subpartArray=array();
			$tot_creditpoints_saved = 0;
			$tot_creditpoints_changed = 0;
			$tot_creditpoints_spended = 0;
			$tot_creditpoints_gifts = 0;
			$this->orders = array();
			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$markerArray['###TRACKING_CODE###'] = $row['tracking_code'];
				$markerArray['###ORDER_DATE###'] = $local_cObj->stdWrap($row['crdate'],$this->conf['orderDate_stdWrap.']);
				$markerArray['###ORDER_NUMBER###'] = $orderObj->getNumber($row['uid']);
				//$rt= $row['creditpoints_saved'] + $row['creditpoints_gifts'] - $row['creditpoints_spended'] - $row['creditpoints'];
				$markerArray['###ORDER_CREDITS###'] = $row['creditpoints_saved'] + $row['creditpoints_gifts'] - $row['creditpoints_spended'] - $row['creditpoints'];
				$markerArray['###ORDER_AMOUNT###'] = $priceViewObj->printPrice($priceViewObj->priceFormat($row['amount']));

				// total amount of saved creditpoints
				$tot_creditpoints_saved += $row['creditpoints_saved'];

				// total amount of changed creditpoints
				$tot_creditpoints_changed += $row['creditpoints'];

				// total amount of spended creditpoints
				$tot_creditpoints_spended += $row['creditpoints_spended'];

				// total amount of creditpoints from gifts
				$tot_creditpoints_gifts += $row['creditpoints_gifts'];
				$orderlistc .= $parser->substituteMarkerArray($orderitem, $markerArray);
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);

			$res1 = $GLOBALS['TYPO3_DB']->exec_SELECTquery('username ', 'fe_users', 'uid="'.intval($feusers_uid).'"');
			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res1)) {
				$username = $row['username'];
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res1);

			$res2 = $GLOBALS['TYPO3_DB']->exec_SELECTquery('username', 'fe_users', 'tt_products_vouchercode='.$GLOBALS['TYPO3_DB']->fullQuoteStr($username, 'fe_users'));
			$num_rows = $GLOBALS['TYPO3_DB']->sql_num_rows($res2) * 5;
			$GLOBALS['TYPO3_DB']->sql_free_result($res2);

			$res3 = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tt_products_creditpoints ', 'fe_users', 'uid='.intval($feusers_uid).' AND NOT deleted');
			$this->creditpoints = array();
			if ($res3 !== false)	{
				while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res3)) {
					$this->creditpoints[$row['uid']] = $row['tt_products_creditpoints'];
					$totalcreditpoints= $row['tt_products_creditpoints'];
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res3);
			}
			$markerArray = array();
			$subpartArray = array();
			$markerArray['###CLIENT_NUMBER###'] = $feusers_uid;
			$markerArray['###CLIENT_NAME###'] = $username;
			$markerArray['###CREDIT_POINTS_SAVED###'] = number_format($tot_creditpoints_saved,0);
			$markerArray['###CREDIT_POINTS_SPENT###'] = number_format($tot_creditpoints_spended,0);
			$markerArray['###CREDIT_POINTS_CHANGED###'] = number_format($tot_creditpoints_changed,0);
			$markerArray['###CREDIT_POINTS_USED###'] = number_format($tot_creditpoints_spended,0) + number_format($tot_creditpoints_changed,0);
			$markerArray['###CREDIT_POINTS_GIFTS###'] = number_format($tot_creditpoints_gifts,0);
			$markerArray['###CREDIT_POINTS_TOTAL###'] = number_format($totalcreditpoints,0);
			$markerArray['###CREDIT_POINTS_VOUCHER###'] = $num_rows;
			$markerArray['###CALC_DATE###'] = date('d M Y');
			$subpartArray['###ORDER_LIST###'] = $orderlistc;
			$subpartArray['###ORDER_NOROWS###'] = '';
			$content = tx_div2007_core::substituteMarkerArrayCached($content, $markerArray, $subpartArray);
		} else {
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
			$norows = tx_div2007_core::getSubpart($content, '###ORDER_NOROWS###');
			$content = $norows;
		} // else of if ($GLOBALS['TYPO3_DB']->sql_num_rows($res))

		return $content;
	}

    public function getSingleOrder ($row) {
		$from = '';
		$where = '';
		$uids = $row['uid'];
		$orderBy = '';
		$pid_list = '';
		$productRowArray = array();
		$multiOrderArray = array();
		$result = '';

		$this->getModelObj()->getOrderedProducts(
			$from,
			$uids,
			$where,
			$orderBy,
			$whereProducts,
			$pid_list,
			$productRowArray,
			$multiOrderArray
		);

        foreach ($productRowArray as $key => $productRow) {
            $result .= '<br>' . $productRow['uid'] . ': ' . $productRow['title'] . ' - ' . $productRow['subtitle'] . ' n. ' . $productRow['itemnumber'] . ' -> ' . $multiOrderArray[$key]['quantity'];
        }
        return $result;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_order_view.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_order_view.php']);
}



