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
 * model functions for a basket item object
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;


class tx_ttproducts_basketitem implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * gets the quantity of an item
	 *
	 * @param	array		reference to an item array with all the data of the item
	 * @param	string		overwrite amount or 'basket'
	 * @return	array
	 * @access private
	 */
	public function getQuantity (
		&$item,
		$overwriteAmount = ''
	) {
		$result = $item['count'];
		if (
			$overwriteAmount != 'basket' &&
			\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($overwriteAmount)
		) {
			$result = intval($overwriteAmount);
		}
		return $result;
	}


	/**
	 * gets the minimum necessary and maximum possible quantity of an item
	 *
	 * @param	array		reference to an item array with all the data of the item
	 * @param	string		overwrite amount or 'basket'
	 * @return	array
	 * @access private
	 */
	public function getMinMaxQuantity (
		$item,
		&$minQuantity,
		&$maxQuantity
	) {
		$row = $item['rec'];
		$minQuantity = $row['basketminquantity'];
		$maxQuantity = $row['basketmaxquantity'];
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
		$prodTable = $tablesObj->get('tt_products', false);
		$articleRow = $prodTable->getArticleRowFromExt($row);

		if (is_array($articleRow) && count($articleRow)) {
			$minQuantity = ($articleRow['basketminquantity'] != '0.00' ? $articleRow['basketminquantity'] : $minQuantity);
			$maxQuantity = ($articleRow['basketmaxquantity'] != '0.00' ? $articleRow['basketmaxquantity'] : $maxQuantity);
		}
	}
}


