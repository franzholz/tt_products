<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2010 Kasper Skårhøj <kasperYYYY@typo3.com>
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
 * Creates a list of products for the shopping basket in TYPO3.
 * Also controls basket, searching and payment.
 *
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author	Renè Fritz <r.fritz@colorcube.de>
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @author	Klaus Zierer <zierer@pz-systeme.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 * @see file tt_products/Configuration/TypoScript/PluginSetup/Main/constants.txt
 * @see TSref
 *
 */


class tx_ttproducts_pi1 implements t3lib_Singleton {
	/**
	 * The backReference to the mother cObj object set at call time
	 *
	 * @var tslib_cObj
	 */
	var $cObj;

	/**
	 * Main method. Call this from TypoScript by a USER cObject.
	 */
	public function main ($content,$conf)	{

		$pibaseObj = t3lib_div::makeInstance('tx_ttproducts_pi1_base');
		$pibaseObj->cObj = $this->cObj;

		if ($conf['templateFile'] != '')	{
			$content = $pibaseObj->main($content, $conf);
		} else {
			tx_div2007_alpha5::loadLL_fh002($pibaseObj, 'EXT:' . TT_PRODUCTS_EXT . '/pi1/locallang.xml');
			$content = tx_div2007_alpha5::getLL_fh003($pibaseObj, 'no_template') . ' plugin.tt_products.templateFile';
		}

		return $content;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/pi1/class.tx_ttproducts_pi1.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/pi1/class.tx_ttproducts_pi1.php']);
}

?>
