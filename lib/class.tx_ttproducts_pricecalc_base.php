<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2009 Franz Holzinger (franz@ttproducts.de)
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
 * base class for all price calculation functions
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */



class tx_ttproducts_pricecalc_base implements \TYPO3\CMS\Core\SingletonInterface {

	function getPrice (&$conf, $offset, $num='1')	{
		$rc = 0;
		$priceCalcTemp = $conf[$offset];
		if (is_array($priceCalcTemp))	{
			$rc = doubleval($priceCalcTemp['prod.'][$num]);
		}
		return $rc;
	}

	function getCalculatedData (
		&$itemArray,
		&$conf,
		$type,
		&$priceReduction,
		&$discountArray,
		$priceTotalTax,
		$bUseArticles,
		$bMergeArticles = true
	) {
	} // getCalculatedData

}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_pricecalc_base.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_pricecalc_base.php']);
}

