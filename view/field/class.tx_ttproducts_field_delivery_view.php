<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2013 Franz Holzinger (franz@ttproducts.de)
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
 * functions for the delivery text and icon
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



class tx_ttproducts_field_delivery_view extends tx_ttproducts_field_base_view {


	/**
	 * Template marker substitution
	 * Fills in the markerArray with data for a product
	 *
	 * @param	string		name of the marker prefix
	 * @param	array		reference to an item array with all the data of the item
	 * 				for the tt_producst record, $row
	 * @access private
	 */
	public function getRowMarkerArray (
		$functablename,
		$fieldname,
		$row,
		$markerKey,
		&$markerArray,
		$fieldMarkerArray,
		$tagArray,
		$theCode,
		$id,
		$basketExtra,
		$basketRecs,
		&$bSkip,
		$bHtml = true,
		$charset = '',
		$prefix = '',
		$suffix = '',
		$imageNum = 0,
		$imageRenderObj = '',
		$linkWrap = false,
		$bEnableTaxZero = false
	) {
		$value = '';
		$result = '';

		if (isset($row[$fieldname])) {

			$imageObj = GeneralUtility::makeInstance('tx_ttproducts_field_image_view');
			$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
			$tableconf = $cnf->getTableConf($functablename, $theCode);
			$domain = $conf['domain'];
			$cObj = FrontendUtility::getContentObjectRenderer();

			if ($domain == '' || strrpos($domain, '###') !== false) {
				$domain = $_SERVER['HTTP_HOST'];
			}

			if (
				isset($tableconf) &&
				is_array($tableconf) &&
				isset($tableconf[$fieldname . '.'])
			) {
				$value = intval($row[$fieldname]);
				$deliveryConf = $tableconf[$fieldname . '.'];

				if (
					isset($deliveryConf) &&
					is_array($deliveryConf) &&
					isset($deliveryConf[$value . '.'])
				) {
					$result = '';
					$valueConf = $deliveryConf[$value . '.'];

					if (isset($valueConf['text.'])) {
						$tmpImgCode = $cObj->cObjGetSingle(
							$valueConf['text'],
							$valueConf['text.'],
							TT_PRODUCTS_EXT
						);
						$result .= $tmpImgCode;
					}

					if (isset($valueConf['image.'])) {
						$tmpImgCode =
							$imageObj->getImageCode(
								$valueConf['image.'],
								$theCode,
								$domain
							);
						$result .= $tmpImgCode;
					}

				}
			}
		}

		return $result;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/field/class.tx_ttproducts_field_datafield_view.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/field/class.tx_ttproducts_field_datafield_view.php']);
}

