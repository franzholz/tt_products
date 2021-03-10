<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2009 Franz Holzinger (franz@ttproducts.de)
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
 * functions for the taxes
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;


class tx_ttproducts_field_tax extends tx_ttproducts_field_base {

	/**
	 *
	 */
	public function preInit ($cObj, $uidStore) {

		parent::init($cObj);
	} // init

	public function getTax ($basketExtra, &$row) {
		$rc = $this->getFieldValue ($basketExtra, $row, 'tax');
		return $rc;
	}

	public function getFieldValue ($basketExtra, $row, $fieldname) {
		$newTax = '';
		$fieldValue = '';
		$taxFromShipping = '';

		if (is_numeric($newTax)) {
			$fieldValue = $newTax;
		} else {
			$fieldValue = parent::getFieldValue($basketExtra, $row, $fieldname);
			$paymentshippingObj = GeneralUtility::makeInstance('tx_ttproducts_paymentshipping');
			if (isset($paymentshippingObj) && is_object($paymentshippingObj)) {
				$taxFromShipping =
					$paymentshippingObj->getReplaceTaxPercentage(
						$basketExtra,
						'shipping',
						$row['tax']
					);	// if set then this has a tax which will override the tax of the products

				if (is_numeric($taxFromShipping)) {
					$fieldValue = $taxFromShipping;
				}
			}

			if (!is_numeric($taxFromShipping) && $fieldValue == 0)	{
				if ($this->conf['TAXpercentage'])	{
					$fieldValue = floatval($this->conf['TAXpercentage']);
				} else {
					$fieldValue = 0.0;
				}
			}
		}
		return $fieldValue;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/field/class.tx_ttproducts_field_tax.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/field/class.tx_ttproducts_field_tax.php']);
}


