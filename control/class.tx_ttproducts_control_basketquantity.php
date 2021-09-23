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
 * deprecated
 *
 * control function for the basket quantity for DAM products.
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;


class tx_ttproducts_control_basketquantity implements \TYPO3\CMS\Core\SingletonInterface {

	public function getQuantityMarker ( // deprecated. used only for DAM
		$marker,
		$prodUid,
		$uid
	)	{
		if ($marker != '' && $uid) {
			$rc = 'FIELD_QTY_' . $prodUid . '_' . $marker . '_' . $uid;
		} else {
			$rc = 'FIELD_QTY';
		}
		return $rc;
	}


	public function getQuantityMarkerArray ( // deprecated. used only for DAM
		$relatedIds,
		$rowArray,
		&$markerArray
	) {
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
		$prodViewObj = $tablesObj->get('tt_products',true);

		$basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');
		$quantityArray = $basketObj->getQuantityArray($relatedIds, $rowArray);

		foreach ($rowArray as $functablename => $functableRowArray) {

			$viewObj = $tablesObj->get($functablename, true);
			$modelObj = $viewObj->getModelObj();
			$marker = $viewObj->getMarker();

			foreach ($relatedIds as $uid) {
				foreach ($functableRowArray as $subRow) {
					$subuid = $subRow['uid'];
					$quantityMarker = self::getQuantityMarker($marker, $uid, $subuid);

					if (
						isset($quantityArray[$uid]) &&
						is_array($quantityArray[$uid]) &&
						isset($quantityArray[$uid][$functablename]) &&
						is_array($quantityArray[$uid][$functablename])
					) {
						$count = strval($quantityArray[$uid][$functablename][$subuid]);
						if (!isset($count)) {
							$count = '';
						}
					} else {
						$count = '';
					}
					$markerArray['###' . $quantityMarker . '###'] = $count;
				}
			}
		}
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/control/class.tx_ttproducts_control_basketquantity.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/control/class.tx_ttproducts_control_basketquantity.php']);
}


