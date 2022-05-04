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
*  the Free Software Foundation; either version 2 of the License or
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
 * class for data collection
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */
use TYPO3\CMS\Core\Utility\GeneralUtility;

use JambageCom\Div2007\Utility\ExtensionUtility;


class tx_ttproducts_model_creator implements \TYPO3\CMS\Core\SingletonInterface {

	public function init ($conf, $config, $cObj) {

        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
         if (version_compare(PHP_VERSION, '8.0.0') >= 0) {
            $staticInfoApi = GeneralUtility::makeInstance(\JambageCom\Div2007\Api\StaticInfoTablesApi::class);
        } else {
            $staticInfoApi = GeneralUtility::makeInstance(\JambageCom\Div2007\Api\OldStaticInfoTablesApi::class);
        }

        $useStaticInfoTables = $staticInfoApi->isActive();
		$bUseStaticTaxes = false;

		if (
			$conf['useStaticTaxes'] &&
			$useStaticInfoTables
		) {
			if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('static_info_tables_taxes')) {
				$eInfo2 = ExtensionUtility::getExtensionInfo('static_info_tables_taxes');

				if (is_array($eInfo2)) {
					$sittVersion = $eInfo2['version'];
					if (version_compare($sittVersion, '0.3.0', '>=')) {
						$bUseStaticTaxes = true;
					}
				}
			}
		}

		$UIDstore = 0;

		if (isset($conf['UIDstore'])) {
			$tmpArray = GeneralUtility::trimExplode(',', $conf['UIDstore']);
			$UIDstore = intval($tmpArray['0']);
			if ($UIDstore) {
				$where_clause = 'uid = ' . $UIDstore . \JambageCom\Div2007\Utility\TableUtility::enableFields('fe_users');
				$storeRecord =
					$GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
						'*',
						'fe_users',
						$where_clause
					);
				if ($storeRecord) {
					\JambageCom\TtProducts\Api\PaymentApi::setStoreRecord($storeRecord);
				}
			}
		}
		$infoArray = tx_ttproducts_control_basket::getInfoArray();

		$taxObj = GeneralUtility::makeInstance('tx_ttproducts_field_tax');
		$taxObj->preInit(
			$bUseStaticTaxes,
			$UIDstore,
			$infoArray,
			$conf
		);

			// price
		$priceObj = GeneralUtility::makeInstance('tx_ttproducts_field_price');
		$priceObj->preInit(
			$conf
		);

			// paymentshipping
// 		$paymentshippingObj = GeneralUtility::makeInstance('tx_ttproducts_paymentshipping');
// 		$paymentshippingObj->init($priceObj);
		$paymentPriceObj = clone $priceObj;
		$voucher = GeneralUtility::makeInstance('tx_ttproducts_voucher');
		\JambageCom\TtProducts\Api\PaymentShippingHandling::init($paymentPriceObj, $voucher);

		$basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket'); // TODO: initialization
// 		$basketObj->init (
// 			$pibaseClass,
// 			$updateMode,
// 			$pid_list,
// 			$bStoreBasket
// 		);

		return true;
	}

	public function destruct () {
	}
}

