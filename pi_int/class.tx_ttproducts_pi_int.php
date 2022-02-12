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
 * variable content plugins for the shop system.
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 * @see file tt_products/static/old_style/constants.txt
 * @see TSref
 *
 */

 
use TYPO3\CMS\Core\Utility\GeneralUtility;


class tx_ttproducts_pi_int implements \TYPO3\CMS\Core\SingletonInterface {
	/**
	 * The backReference to the mother cObj object set at call time
	 *
	 * @var TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
	 */
	var $cObj;


	/**
	 * Main method. Call this from TypoScript by a USER cObject.
	 */
	public function main ($content, $conf) {

		$pibaseObj = GeneralUtility::makeInstance('tx_ttproducts_pi_int_base');
		$pibaseObj->cObj = $this->cObj;
		$confMain = $GLOBALS['TSFE']->tmpl->setup['plugin.'][TT_PRODUCTS_EXT . '.'];
// 		$conf = GeneralUtility::array_merge_recursive_overrule($confMain, $conf);
		tx_div2007_core::mergeRecursiveWithOverrule($confMain, $conf);
		$conf = $confMain;

		if ($conf['templateFile'] != '') {

			$content = $pibaseObj->main($content, $conf);
        } else {
            if (count($conf) > 2) {
                $errorText = $GLOBALS['TSFE']->sL(
                    'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'PiInt/locallang.xlf:no_template'
                );
                $content = str_replace('|', 'plugin.tt_products.templateFile', $errorText);
            } else {
                $errorText = $GLOBALS['TSFE']->sL(
                    'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'PiInt/locallang.xlf:no_setup'
                );
            }
        }
		return $content;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/pi1/class.tx_ttproducts_pi_int.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/pi1/class.tx_ttproducts_pi_int.php']);
}

