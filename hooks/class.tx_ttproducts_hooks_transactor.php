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
 * hook functions for the Transactor API extension
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;

class tx_ttproducts_hooks_transactor {

	public function execute (
		$pObj,
		$params
	) {

// Process the ID, type and other parameters
		// After this point we have an array, $page in TSFE, which is the page-record of the current page, $id

		if (
			!isset($params['row']) ||
			!isset($params['row']['ext_key']) ||
			$params['row']['ext_key'] != TT_PRODUCTS_EXT
		) {
			return false;
		}

		tx_div2007_alpha5::initFE();
 		$callingClassName3 = '\\TYPO3\\CMS\\Core\\Core\\Bootstrap';
		$bootStrap = call_user_func($callingClassName3 . '::getInstance');
		$bootStrap->loadExtensionTables(true);
		$GLOBALS['LANG'] = GeneralUtility::makeInstance('language');
		$GLOBALS['LANG']->init('en');

		$transactionRow = $params['row'];
		$testMode = $params['testmode'];
		$referenceId = $transactionRow['reference'];

		$conf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tt_products.'];
		$config = array();
		$config['LLkey'] = '';
		$errorCode = '';

		$cObj = \JambageCom\Div2007\Utility\FrontendUtility::getContentObjectRenderer();
		$cObj->start(array(), 'tt_products');

		$orderUid = 0;
		$orderTablename = 'sys_products_orders';

		if ($testMode) {
			$orderUid = tx_ttproducts_model_control::getPiVarValue($orderTablename); // keep this line for testing purposes
			$orderUid = intval($orderUid);
		} else {
			$orderUid = intval($transactionRow['orderuid']);
		}

		$where_clause = $orderTablename . '.uid=' . intval($orderUid);
		$where_clause .= ' AND ' . $orderTablename . '.deleted=0 AND ' . $orderTablename . '.hidden=1';
		$orderRow = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', $orderTablename, $where_clause);

		$basketRec = \JambageCom\TtProducts\Api\BasketApi::getBasketRec($orderRow);
		$controlCreatorObj = GeneralUtility::makeInstance('tx_ttproducts_control_creator');
		$result =
			$controlCreatorObj->init(
				$conf,
				$config,
				'',
				$cObj,
				'',
				$errorCode,
				array(),
				$basketRec
			);
		if (!$result) {
			return false;
		}

		$modelCreatorObj = GeneralUtility::makeInstance('tx_ttproducts_model_creator');
		$modelCreatorObj->init($conf, $config, $cObj);

		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables'); // init ok
		$orderObj = $tablesObj->get('sys_products_orders');
		$orderUid = 0;

		if ($testMode) {
			$orderUid = tx_ttproducts_model_control::getPiVarValue('sys_products_orders'); // keep this line for testing purposes
			$orderUid = intval($orderUid);
		} else {
			$orderUid = intval($transactionRow['orderuid']);
		}

		if ($orderUid && $referenceId) {

			if (isset($orderRow) && is_array($orderRow) && $orderRow['hidden']) {
				$calculatedArray = array();
				$infoArray = array();

				$itemArray = $orderObj->getItemArray(
					$orderRow,
					$calculatedArray,
					$infoArray
				);

				$infoViewObj = GeneralUtility::makeInstance('tx_ttproducts_info_view');
				$infoViewObj->init2($infoArray);

				$addressObj = $tablesObj->get('address', false);
				$addressArray = $addressObj->fetchAddressArray($itemArray);

				$mainMarkerArray = array();
				$mainMarkerArray['###MESSAGE_PAYMENT_SCRIPT###'] = '';

				$basketExtra = tx_ttproducts_control_basket::getBasketExtras($tablesObj, $basketRec, $conf);

				$templateObj = GeneralUtility::makeInstance('tx_ttproducts_template');
				$templateFile = '';
				$errorCode = '';

				if ($conf['fe']) {
					$templateCode =
						$templateObj->get(
							'FINALIZE',
							$templateFile,
							$errorCode
						);
				}

				if ($templateCode != '' && $errorCode == '') {

					$errorMessage = '';
					$basketView = GeneralUtility::makeInstance('tx_ttproducts_basket_view');
					$basketView->init(
						array(),
						$conf['useArtcles'],
						$errorCode
					);

					if ($errorCode == '') {
						tx_ttproducts_api::finalizeOrder(
							$this,
							$templateCode,
							$mainMarkerArray,
							$functablename = 'tt_products',
							$orderUid,
							$orderRow,
							$itemArray,
							$calculatedArray,
							$addressArray,
							$basketExtra,
							$basketRec,
							'',
							0.0,
							false,
							$errorMessage
						);
					}
				}
			} else {
				echo 'no order found';
			}
		}
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/hooks/class.tx_ttproducts_hooks_transactor.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/hooks/class.tx_ttproducts_hooks_transactor.php']);
}

