<?php

namespace JambageCom\TtProducts\Api;

/***************************************************************
*  Copyright notice
*
*  (c) 2016 Franz Holzinger <franz@ttproducts.de>
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
 * Part of the tt_products (Shop System) extension.
 *
 * functions for the payment
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

class PaymentApi {
	static private $storeRecord;
	static private $storeIso3;

	static public function setStoreRecord ($value) {
		self::$storeRecord = $value;
		if (
			isset($value) &&
			is_array($value) &&
			isset($value['static_info_country'])
		) {
			self::setStoreIso3($value['static_info_country']);
		}
	}

	static public function getStoreRecord () {
		return self::$storeRecord;
	}

	static public function setStoreIso3 ($value) {
		self::$storeIso3 = $value;
	}

	static public function getStoreIso3 ($defaultValue = '') {
		$result = '';

		if (
			self::$storeIso3 != ''
		) {
			$result = self::$storeIso3;
		} else if ($defaultValue != '') {
			$result = $defaultValue;
		}

		return $result;
	}

    static public function getPayMode (
        \JambageCom\Div2007\Base\TranslationBase $languageObj,
        $basketExtra
    ) {
		$result = 0;

		if (
			isset($basketExtra) &&
			is_array($basketExtra) &&
			isset($basketExtra['payment.']) &&
			isset($basketExtra['payment.']['mode'])
		) {
			$modeText = $basketExtra['payment.']['mode'];
			$theTable = 'sys_products_orders';

			$colName = 'pay_mode';
			$textSchema = $theTable . '.' . $colName . '.I.';
			$i = 0;
			do {
				$text = $languageObj->getLabel(
					$textSchema . $i,
					$usedLang = 'default'
				);

				$text = str_replace(' ', '_', $text);
				if ($text == $modeText) {
					$result = $i;
					break;
				}
				$i++;
			} while ($text != '' && $i < 99);
		}

		return $result;
	}

}

