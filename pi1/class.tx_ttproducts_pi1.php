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
 * Creates a list of products for the shopping basket in TYPO3.
 * Also controls basket, searching and payment.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author	Renè Fritz <r.fritz@colorcube.de>
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @author	Klaus Zierer <zierer@pz-systeme.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 * @see file tt_products/static/old_style/constants.txt
 * @see TSref
 *
 */


use TYPO3\CMS\Core\Utility\GeneralUtility;



class tx_ttproducts_pi1 implements \TYPO3\CMS\Core\SingletonInterface {
	/**
	 * The backReference to the mother cObj object set at call time
	 *
	 * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
	 */
	public $cObj;


	/**
	 * Main method. Call this from TypoScript by a USER cObject.
	 */
	public function main ($content, $conf) {

		$pibaseObj = GeneralUtility::makeInstance('tx_ttproducts_pi1_base');
		$pibaseObj->cObj = $this->cObj;

		if ($conf['templateFile'] != '' || $conf['templateFile.'] != '') {
			$content = $pibaseObj->main($content, $conf);
		} else {
            $errorText = $GLOBALS['TSFE']->sL(
                'LLL:EXT:' . TT_PRODUCTS_EXT . DIV2007_LANGUAGE_SUBPATH . 'Pi1/locallang.xlf:no_template'
            );
			$content = str_replace('|', 'plugin.tt_products.templateFile', $errorText);
            $content = '<p><strong>' . $content . '</strong></p>';
		}

		return $content;
	}

	/**
	 * Main method for the cached object. Call this from TypoScript by a USER or COBJ cObject.
	 */
	public function getUserFunc ($content, $conf) {
		$conf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tt_products.'];

		if (
            isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['getUserFunc']) &&
            is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['getUserFunc'])
		) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['getUserFunc'] as $classRef) {-
				$hookObj = GeneralUtility::makeInstance($classRef);
				if (method_exists($hookObj, 'getUserFunc')) {
					$hookObj->cObj = $this->cObj;
					$content .= $hookObj->getUserFunc($content, $conf);
				}
			}
		}
		return $content;
	}
}

