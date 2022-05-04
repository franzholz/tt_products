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
 * hook functions for the pool extension
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;


class tx_ttproducts_hooks_pool extends tx_pool_hooks_base {
	public $extKey = TT_PRODUCTS_EXT;
	public $prefixId = 'tx_ttproducts_hooks_pool';	// Same as class name
	public $LLFileArray =
		array (
			'hooks/locallang_pool.xml',
			DIV2007_LANGUAGE_PATH . 'locallang_mod_web_list.xlf'
		);
	public $modMenu = array('function' => array('search'));
	public $headerText = 'header_search';

	public function getViewData (
		&$content,
		&$header,
		&$docHeaderButtons,
		&$markerArray,
		$pOb
	) {
		$content = '<b>Suche &uuml;ber tt_products</b><br/>';

		if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('searchbox')) {

// 			GeneralUtility::requireOnce(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('searchbox') . 'view/class.tx_searchbox_view.php');
			$searchBoxObj = GeneralUtility::makeInstance('tx_searchbox_view');

			$content .= $searchBoxObj->getContent(
				$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['templateFile'],
				'ITEM_SEARCH',
				$this->prefixId,
				$this->vars,
				'index.php'
			);
			$dbListConf = $searchBoxObj->getDblistConf($this->prefixId);
		}

		if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('db_list')) {
// 			GeneralUtility::requireOnce(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('db_list') . 'class.tx_dblist_script.php');
			$dbListObj = GeneralUtility::makeInstance('tx_dblist_script');
			$dbListObj->init($this->vars, $dbListConf);

			$dbListObj->clearCache();
			$dbListObj->main($docHeaderButtons, $markerArray, $pOb);
			$content .= $dbListObj->getContent();
		}
		parent::getViewData($content, $header, $docHeaderButtons, $markerArray, $pOb);
	}
}



