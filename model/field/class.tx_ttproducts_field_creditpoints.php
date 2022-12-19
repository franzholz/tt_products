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
 * functions for the title field
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;


class tx_ttproducts_field_creditpoints extends tx_ttproducts_field_base {


	public function getBasketTotal () {
		$rc = 0;
		$basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');
		$itemArray = $basketObj->getItemArray();

		if (count($itemArray)) {
			$pricefactor = 0;
			$mode = 'normal';
			if (
				isset($this->conf['creditpoints.']) &&
				isset($this->conf['creditpoints.']['mode'])
			) {
				$mode = $this->conf['creditpoints.']['mode'];
				$pricefactor = tx_ttproducts_creditpoints_div::getPriceFactor($this->conf);
			}

			$creditpointsTotal = 0;
			// loop over all items in the basket indexed by a sort string
			foreach ($itemArray as $sort => $actItemArray) {
				foreach ($actItemArray as $k1 => $actItem) {
					$row = $actItem['rec'];
					$count = $actItem['count'];

					if ($row['creditpoints'] > 0) {

						$creditpointsTotal += $row['creditpoints'] * $count;
					} else if ($mode == 'auto' && $pricefactor) {
						$creditpointsTotal += ($actItem['priceTax'] * $count) / $pricefactor;
					}
				}
			}
			$rc = $creditpointsTotal;
		}
		return $rc;
	}


	public function getBasketMissingCreditpoints ($addCreditpoints, &$missing, &$remaining) {
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
		$feuserTable = $tablesObj->get('fe_users', false);

		$feuserCreditpoints = $feuserTable->getCreditpoints();
		$creditpointsTotal = $this->getBasketTotal() + $addCreditpoints;
		$missing = $creditpointsTotal - $feuserCreditpoints;
		$missing = ($missing > 0 ? $missing : 0);
		$remaining = $feuserCreditpoints - $creditpointsTotal;
	}


	public function getMissingCreditpoints ($fieldname, $row, &$missing, &$remaining) {

		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
		$feuserTable = $tablesObj->get('fe_users', false);

		$creditpointsTotal = $this->getBasketTotal();
		$feuserCreditpoints = $feuserTable->getCreditpoints();
		$missing = $creditpointsTotal + $row[$fieldname] - $feuserCreditpoints;
		$missing = ($missing > 0 ? $missing : 0);
		$remaining = $feuserCreditpoints - $creditpointsTotal - $row[$fieldname];
	}


	/* reduces the amount of creditpoints of the FE user by the total amount of creditpoints from the products. */
	/* It returns the number of creditpoints by which the account of the FE user has been reduced. false is if no FE user is logged in. */
	public function pay () {
		$rc = false;
		if (\JambageCom\Div2007\Utility\CompatibilityUtility::isLoggedIn()) {
//			$whereGeneral = '(fe_users_uid="'.$GLOBALS['TSFE']->fe_user->user['uid'].'" OR fe_users_uid=0) ';

			$creditpointsTotal = $this->getBasketTotal();

			if ($creditpointsTotal) {
				$fieldsArrayFeUsers = [];
				$fieldsArrayFeUsers['tt_products_creditpoints'] = $GLOBALS['TSFE']->fe_user->user['tt_products_creditpoints'] - $creditpointsTotal;
				if ($fieldsArrayFeUsers['tt_products_creditpoints'] < 0) {
					$fieldsArrayFeUsers['tt_products_creditpoints'] = 0;
					$rc = $GLOBALS['TSFE']->fe_user->user['tt_products_creditpoints'];
				}
				if ($GLOBALS['TSFE']->fe_user->user['tt_products_creditpoints'] != $fieldsArrayFeUsers['tt_products_creditpoints']) {
					$GLOBALS['TSFE']->fe_user->user['tt_products_creditpoints'] = $fieldsArrayFeUsers['tt_products_creditpoints']; // store it also for the global FE user data
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery('fe_users', 'uid=' . intval($GLOBALS['TSFE']->fe_user->user['uid']), $fieldsArrayFeUsers);
					$rc = $creditpointsTotal;
				}
			}
		}
		return $rc;
	}
}

