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
 * functions for the TypoScript conditions
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


class tx_ttproducts_match_condition implements t3lib_Singleton {

	public function checkShipping (
		$params
	) {
		global $TSFE;

		$result = FALSE;

		if (isset($params) && is_array($params)) {
			tx_ttproducts_control_basket::init();
			$infoArray = tx_ttproducts_control_basket::getInfoArray();

			tx_ttproducts_control_basket::fixCountries($infoArray);
			$type = $params['0'];
			$field = $params['1'];
			$operator = '=';

			$value = '';
			if (strpos($params['2'], $operator) !== FALSE) {
				$value = ltrim($params['2'], ' =');
			} else if (strpos($params['2'], 'IN') !== FALSE) {
				$operator = 'IN';
				if (strpos($params['2'], 'NOT IN') !== FALSE) {
					$operator = 'NOT IN';
				}
				$value = trim($params['3']);
			}

			if ($operator == '=') {
				$result = ($infoArray[$type][$field] == $value);
			} else if ($operator == 'IN') {
				$valueArray = t3lib_div::trimExplode(',', $value);
				$result = in_array($infoArray[$type][$field], $valueArray);
			} else if ($operator == 'NOT IN') {
				$valueArray = t3lib_div::trimExplode(',', $value);
				$result = !in_array($infoArray[$type][$field], $valueArray);
			}

			tx_ttproducts_control_basket::destruct();

			if (
 				!$result &&
				(
					!is_array($infoArray) ||
					!is_array($infoArray[$type]) ||
					!isset($infoArray[$type][$field])
				) &&
 				$TSFE->loginUser
 			) {
				if ($field == 'country_code') {
					$field = 'static_info_country';
				}
				$value = str_replace('\'', '', $value);

				if ($operator == '=') {
					$result = ($TSFE->fe_user->user[$field] == $value);
				} else if ($operator == 'IN') {
					$valueArray = t3lib_div::trimExplode(',', $value);
					$result = in_array($TSFE->fe_user->user[$field], $valueArray);
				} else if ($operator == 'NOT IN') {
					$valueArray = t3lib_div::trimExplode(',', $value);
					$result = !in_array($TSFE->fe_user->user[$field], $valueArray);
				}
			}
		}

		return $result;
	}


	public function hasBulkilyItem ($where) {
		global $TYPO3_DB;

		tx_ttproducts_control_basket::init();
		$recs = tx_ttproducts_control_basket::getRecs();
		$cObj = t3lib_div::makeInstance('tslib_cObj');
		$basketExt = tx_ttproducts_control_basket::getBasketExt();

		if (isset($basketExt) && is_array($basketExt)) {

			$uidArr = array();

			foreach($basketExt as $uidTmp => $tmp) {
				if ($uidTmp != 'gift' && !in_array($uidTmp, $uidArr)) {
					$uidArr[] = intval($uidTmp);
				}
			}

			if (count($uidArr) == 0) {
				return FALSE;
			}
			$where .= ' AND uid IN ('.implode(',',$uidArr).')';
		}

		$where .= $cObj->enableFields('tt_products');

		$rcArray = $TYPO3_DB->exec_SELECTgetRows('*', 'tt_products', $where);
		$bBukily = FALSE;
		foreach ($rcArray as $uid => $row) {
			if ($row['bulkily']) {
				$bBukily = TRUE;
				break;
			}
		}

		tx_ttproducts_control_basket::destruct();
		return ($bBukily);
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/hooks/class.tx_ttproducts_match_condition.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/hooks/class.tx_ttproducts_match_condition.php']);
}


?>
