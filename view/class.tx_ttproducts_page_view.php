<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2007 Franz Holzinger (franz@ttproducts.de)
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
 * functions for the page
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */




class tx_ttproducts_page_view extends tx_ttproducts_category_base_view {
	var $noteArray = array(); 	// array of pages with notes
	var $piVar = 'pid';
	var $pageAsCategory;		// > 0 if pages are used as categories

	/**
	 * Template marker substitution
	 * Fills in the markerArray with data for a product
	 *
	 * @param	array		reference to an item array with all the data of the item
	 * @param	integer		number of images to be shown
	 * @param	object		the image cObj to be used
	 * @param	array		information about the parent HTML form
	 * @return	array		Returns a markerArray ready for substitution with information
	 * 		 			for the tt_producst record, $row
	 * @access private
	 */
	function getMarkerArray (&$markerArray, $markerKey, $category, $pid, $imageNum=0, $imageRenderObj='image', &$viewCatTagArray, $forminfoArray=array(), $pageAsCategory=0, $theCode, $basketExtra, $id, $prefix,$linkWrap='')	{
		global $TSFE;

		$row = $this->modelObj->get($pid);
		$imageObj = t3lib_div::makeInstance('tx_ttproducts_field_image_view');

			// Get image
		$imageObj->getRowMarkerArrayEnhanced ($this->modelObj->getFuncTablename(), $row, $this->marker, $markerArray, $pid, $imageNum, $imageRenderObj, $viewCatTagArray, $theCode, $id, $prefix, '', $linkWrap);

		$pageCatTitle = htmlentities($row['title'], ENT_QUOTES, 'UTF-8');
		$this->setMarkerArrayCatTitle ($markerArray, $pageCatTitle, $prefix);
		$markerArray['###'.$prefix.$this->marker.'_SUBTITLE###'] = htmlentities($row['subtitle'], ENT_QUOTES, 'UTF-8');

		parent::getRowMarkerArray($row, $markerKey, $markerArray, $variantFieldArray, $variantMarkerArray, $viewCatTagArray, $theCode, $basketExtra, TRUE, '', $imageNum, $imageRenderObj, $id, $prefix);
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_page_view.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_page_view.php']);
}


?>
