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
 * functions for additional texts
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */

use JambageCom\Div2007\Utility\FrontendUtility;


class tx_ttproducts_text_view extends tx_ttproducts_table_base_view {
	public $marker = 'TEXT';


	/**
	 * Template marker substitution
	 * Fills in the markerArray with data for a product
	 *
	 * @param	array		reference to an item array with all the data of the item
	 * @param	array		Returns a markerArray ready for substitution with information
	 * @access private
	 */
	public function getRowsMarkerArray (
		$rowArray,
		&$markerArray,
		$parentMarker,
		$tagArray
	) {
		$bFoundTagArray = array();
        $cObj = \JambageCom\TtProducts\Api\ControlApi::getCObj();
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$conf = $cnf->getConf();
		$config = $cnf->getConfig();

        if (isset($rowArray) && is_array($rowArray) && count($rowArray)) {
			foreach ($rowArray as $k => $row) {
				$tag = strtoupper($row['marker']);
				$bFoundTagArray[$tag] = true;
				$marker = $parentMarker . '_' . $this->getMarker() . '_' . $tag;
				$value = $row['note'];
				$value = ($conf['nl2brNote'] ? nl2br($value) : $value);

                if (FrontendUtility::hasRTEparser()) {
                    $value = FrontendUtility::RTEcssText($cObj, $value);
				} else if (is_array($conf['parseFunc.'])) {
					$value = $cObj->parseFunc($value, $conf['parseFunc.']);
				}
				$markerArray['###' . $marker . '###'] = $value;
				$markerTitle = $marker . '_' . strtoupper('title');
				$markerArray['###' . $markerTitle . '###'] = $row['title'];
			}
		}


		if (isset($tagArray) && is_array($tagArray)) {
			foreach ($tagArray as $tag) {
				if (!$bFoundTagArray[$tag]) {
					$marker = $parentMarker . '_' . $this->getMarker() . '_' . $tag;
					$markerArray['###' . $marker . '###'] = '';
				}
			}
		}
	}
}

