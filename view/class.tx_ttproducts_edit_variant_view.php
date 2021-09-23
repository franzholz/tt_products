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
 * function to add a variant edit field to products
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;


class tx_ttproducts_edit_variant_view implements tx_ttproducts_edit_variant_view_int, \TYPO3\CMS\Core\SingletonInterface {

	protected $modelObj;

	public function init ($modelObj) {
		$this->modelObj = $modelObj;
	}

	public function getModelObj () {
		return $this->modelObj;
	}

	public function getMarkerArray (
		$bEditable,
		$row,
		$functablename,
		$theCode,
		$config,
		&$markerArray
	) {
		if (isset($config) && is_array($config)) {
			$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
			$itemTableView = $tablesObj->get($functablename, true);
			$uid = $row['uid'];
			$mainAttributes = '';

			if (isset($config['params'])) {
				$mainAttributes = $config['params'];
			}

			if (isset($config['suffix'])) {
				$suffix = '_' . $config['suffix'];
			} else {
				$suffix = $config['index'];
			}
			$field = 'edit' . $suffix;
			$name = tx_ttproducts_control_basket::getTagName($row['uid'], $field);
			$value = '';
			if (isset($row[$field])) {
				$value = $row[$field];
			}

			if ($bEditable) {
				$basketExtRaw = tx_ttproducts_control_basket::getBasketExtRaw();
				if (isset($basketExtRaw) && is_array($basketExtRaw)) {
					if (isset($basketExtRaw[$uid]) && is_array($basketExtRaw[$uid])) {
						$value = $basketExtRaw[$uid][$field];
					}
				}

				$ajaxFunction = tx_ttproducts_control_basket::getAjaxVariantFunction($row, $functablename, $theCode);
				$splitArray = preg_split('/ *= */', $mainAttributes);
				$mainAttributesArray = array();

				if (isset($splitArray) && is_array($splitArray)) {
					$lastKey = 0;
					$lastAttribute = '';
					$switch = 'read_key';

					foreach ($splitArray as $v) {
						if (
							($switch == 'read_value') &&
							preg_match('/".*"/', $v)
						) {
							$mainAttributesArray[$lastAttribute] = str_replace('"', '', $v);
							$switch = 'read_key';
						} else {
							$lastAttribute = strtolower($v);
							if ($switch == 'read_value') {
								$mainAttributesArray[$lastAttribute] = '';
							}
							$switch = 'read_value';
						}
					}
				}

				if (!isset($mainAttributesArray['onchange'])) {
					$mainAttributesArray['onchange'] = $ajaxFunction;
				}

				$mainId = $itemTableView->getId($row, '', $theCode);
				$id = $mainId . '-' . str_replace('_', '-', $field);
				$mainAttributesArray['id'] = $id;

				$html = tx_ttproducts_form_div::createTag(
					'input',
					$name,
					$value,
					'',
					$mainAttributesArray
				);
			} else {
				$html = '';
				if (isset($row[$field])) {
					$html = htmlspecialchars($row[$field], $flags);
				}
			}

			$markerArray['###EDIT_VARIANT###'] = $html;
		}
	}

	public function getSubpartMarkerArray (
		$templateCode,
		$functablename,
		$row,
		$theCode,
		$bEditable,
		$tagArray,
		&$subpartArray,
		&$wrappedSubpartArray
	) {
// 		###edit_variant1###
		$editConf = $this->getModelObj()->getValidConfig($row);

		if (isset($editConf) && is_array($editConf)) {
            $cObj = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);

			foreach ($editConf as $k => $config) {
				if (isset($config['suffix'])) {
					$suffix = '_' . $config['suffix'];
				} else {
					$suffix = $config['index'];
				}
				$marker = 'edit_variant' . $suffix;

				if (isset($tagArray[$marker])) {
					$subpartMarker = '###' . $marker . '###';
					// $wrappedSubpartArray[$subpartMarker] = '';
					$markerArray = array();
					$this->getMarkerArray(
						$bEditable,
						$row,
						$functablename,
						$theCode,
						$config,
						$markerArray
					);

					$subpartContent = $cObj->getSubpart($templateCode, $subpartMarker);
					$content =
						tx_div2007_core::substituteMarkerArrayCached(
							$subpartContent,
							$markerArray
						);
					$subpartArray[$subpartMarker] = $content;
				}
			}
		}

		foreach ($tagArray as $tag => $number) {
			if (strpos($tag, 'edit_variant') === 0) {
				if (!isset($subpartArray['###' . $tag . '###'])) {

					$subpartArray['###' . $tag . '###'] = '';
				}
			}
		}
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_edit_variant_view.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_edit_variant_view.php']);
}

