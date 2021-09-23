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
 * functions for the frontend users
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

 
use TYPO3\CMS\Core\Utility\GeneralUtility;


class tx_ttproducts_address_view extends tx_ttproducts_category_base_view {
	public $piVar = 'a';
	public $marker = 'ADDRESS';


	/**
	 * Template marker substitution
	 * Fills in the markerArray with data for the address
	 *
	 * @param	array		reference to an item array with all the data of the item
	 * @param	integer		number of images to be shown
	 * @param	object		the image cObj to be used
	 * @param	array		information about the parent HTML form
	 * @return	array		Returns a markerArray ready for substitution with information
	 * 				for the tt_producst record, $row
	 * @access private
	 */
	public function getMarkerArray (
		&$markerArray,
		$markerKey,
		$category,
		$pid,
		$imageNum = 0,
		$imageRenderObj = 'image',
		&$viewCatTagArray,
		$forminfoArray = array(),
		$pageAsCategory = 0,
		$theCode,
		$basketExtra,
		$basketRecs,
		$id,
		$prefix,
		$linkWrap = ''
	) {
		$titleField = $this->getModelObj()->fieldArray['title'];
		$row = ($category ? $this->getModelObj()->get($category) : array ($titleField => '', 'pid' => $pid));
		$catTitle = '';

		if (($row[$titleField])) {
			$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
			$tableConfig = $cnf->getTableConf('address', $theCode);
			$catTitle .= ($tableConfig['separator'] . $row[$titleField]);
		}
		$this->setMarkerArrayCatTitle($markerArray, $catTitle, $prefix);
		parent::getRowMarkerArray(
			'address',
			$row,
			$markerKey,
			$markerArray,
			$variantFieldArray,
			$variantMarkerArray,
			$viewCatTagArray,
			$theCode,
			$basketExtra,
			$basketRecs,
			$bHtml,
			$charset,
			$imageNum,
			$imageRenderObj,
			$id,
			$prefix,
			'',
			$linkWrap
		);
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_address_view.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_address_view.php']);
}

