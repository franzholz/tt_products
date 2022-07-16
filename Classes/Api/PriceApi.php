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

class PriceApi {

	static public function mergeRows (
		array &$targetRow,
		array $sourceRow,
		$field,
		$bIsAddedPrice,
		$previouslyAddedPrice, // deprecated
		$calculationField = '',
		$bKeepNotEmpty = true,
		$bUseExt = false
	) {
		$calculatedValue = 0;
		$value = 0;
		if (isset($sourceRow[$field])) {
            $value = $sourceRow[$field];
        }
		if (
			$field == 'price' &&
			$calculationField != ''
		) { // check for a graduated price
			if (
				isset($sourceRow[$calculationField])
			) {
				$value = $sourceRow[$calculationField];
				$calculatedValue = $value;
			}
		}

		$priceNumber = '';
		if (strpos($field, 'price') === 0) {
			$priceNumber = str_replace('price', '', $field);
			if (!isset($targetRow['surcharge' . $priceNumber])) {
				$targetRow['surcharge' . $priceNumber] = 0;
			}
		}

		if ($bIsAddedPrice) {
			if (strpos($field, 'price') === 0) {
                if (!isset($targetRow['surcharge' . $priceNumber])) {
                    $targetRow['surcharge' . $priceNumber] = 0;
                }
				$targetRow['surcharge' . $priceNumber] += $value;
			}

			$value += $targetRow[$field];

			if ($bUseExt) {
				if (!isset($targetRow['ext'])) {
					$targetRow['ext'] = array();
				}
				if (!isset($targetRow['ext']['addedPrice'])) {
                    $targetRow['ext']['addedPrice'] = 0;
                }
				$targetRow['ext']['addedPrice'] += $targetRow[$field];
			}
        }

		if($bKeepNotEmpty) {
			if (
                (
                    !isset($targetRow[$field]) ||
                    !round($targetRow[$field], 16)
				) &&
				round($value, 16)
			) {
				$targetRow[$field] = $value;
			}
		} else { // $bKeepNotEmpty == false
			$targetRow[$field] = $value;
		}
	}
}

