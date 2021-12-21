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
*  the Free Software Foundation; either version 2 of the License or
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
 * eID compatible AJAX functions
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;

use JambageCom\Div2007\Utility\FrontendUtility;


class tx_ttproducts_ajax implements \TYPO3\CMS\Core\SingletonInterface {
	public $taxajax;	// xajax object
	public $conf; 	// conf coming from JavaScript via Ajax

	public function init()	{
		$this->taxajax = GeneralUtility::makeInstance('tx_taxajax');

			// Encoding of the response to FE charset
		$this->taxajax->setCharEncoding('UTF-8');
	}


	public function setConf(&$conf)	{
		$this->conf = $conf;
	}


	public function &getConf()	{
		return $this->conf;
	}


	public function main (
		$cObj,
		$urlObj,
		$debug,
		$piVarSingle = 'product',
		$piVarCat = 'cat'
	) {
			// Encoding of the response to utf-8.
		// $this->taxajax->setCharEncoding('utf-8');
			// Do you want messages in the status bar?
		// $this->taxajax->statusMessagesOn();

			// Decode form vars from utf8
		// $this->taxajax->decodeUTF8InputOn();

			// Turn only on during testing
		if ($debug) {
			$this->taxajax->debugOn();
		} else	{
			$this->taxajax->debugOff();
		}
		$this->taxajax->setWrapperPrefix('');
        $addQueryString = [];

        if (
            version_compare(TYPO3_version, '9.5.0', '<')
        ) {
            $addQueryString = [
                'eID' => TT_PRODUCTS_EXT
            ];
        } else {
            $addQueryString = [
                'taxajax' => TT_PRODUCTS_EXT
            ];
        }

		$excludeList = '';
		$queryString = $urlObj->getLinkParams(
			$excludeList,
			[],
			true,
			false,
			$piVarSingle,
			$piVarCat
		);
		$queryString = array_merge($queryString, $addQueryString);

		$linkConf = array('useCacheHash' => 0);
		$target = '';
		$reqURI = FrontendUtility::getTypoLink_URL(
			$cObj,
			$GLOBALS['TSFE']->id,
			$queryString,
			$target,
			$linkConf
		);

		$this->taxajax->setRequestURI($reqURI);
	}
}



