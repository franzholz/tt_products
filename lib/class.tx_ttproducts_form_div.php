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
 * functions for the display of forms
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;


class tx_ttproducts_form_div {

	static public function createSelect (
		$languageObj,
		$valueArray,
		$name,
		$selectedKey,
		$bSelectTags = true,
		$bTranslateText = true,
		$allowedArray = [],
		$type = 'select',
		$mainAttributeArray = [],
		$header = '',
		$layout = '',
		$imageFileArray = '',
		$keyMarkerArray = ''
	) {
		$result = false;
		$templateService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Service\MarkerBasedTemplateService::class);
		$useXHTML = !empty($GLOBALS['TSFE']->config['config']['xhtmlDoctype']);
		$flags = ENT_QUOTES;

		if (is_array($valueArray)) {
			$totaltext = '';
			if ($header != '') {
				$newValueArray = [];
				$newValueArray['-1'] = $header;
				foreach ($valueArray as $k => $v) {
					$newValueArray[$k] = $v;
				}
				$valueArray = $newValueArray;
			}

			foreach ($valueArray as $key => $parts) {

				if (is_array($parts)) {
					$selectKey = $parts['1'];
					$selectValue = $parts['0'];
				} else {
					$selectKey = $key;
					$selectValue = $parts;
				}

				if ($bTranslateText) {
					$tmp = $languageObj->splitLabel($selectValue);
					$text = $languageObj->getLabel($tmp);
				} else {
					$text = '';
				}
				if ($text == '') {
					if (strpos($selectValue, 'LLL:EXT') === 0) {
						continue;
					}
					$text = $selectValue;
				}

				if (empty($allowedArray) || in_array($selectKey, $allowedArray)) {
					$nameText = trim($text);
					$valueText = $selectKey;
					$selectedText = '';
					$paramArray = [];
					$preParamArray = [];

					if ($key == -1) {
						$selectedText = ($useXHTML ? ' disabled="disabled"' : ' disabled');
					} else if (strcmp($selectKey, $selectedKey) == 0) {
						switch ($type) {
							case 'select':
								$selectedText = ($useXHTML ? ' selected="selected"' : ' selected');
								$paramArray['selected'] = 'selected';
								break;
							case 'checkbox':
							case 'radio':
								$selectedText = ($useXHTML ? ' checked="checked"' : ' checked');
								$paramArray['checked'] = 'checked';
								break;
							default:
								debug ($type, 'ERROR: unknown type'); // keep this
								return false;
								break;
						}
					}

					switch ($type) {
						case 'select':
							$inputTextArray = ['<option value="' . htmlspecialchars($valueText, $flags) . '"' . $selectedText . '>', '</option>'];
							break;
						case 'checkbox':
						case 'radio':
							$preParamArray['type'] = $type;
							$inputText = self::createTag('input', $name, $valueText, $preParamArray, $paramArray);

							if ($layout == '') {
								$inputText .=  ' ' . $nameText . '<br ' . ($useXHTML ? '/' : '') . '>';
							}
							$inputTextArray = [$inputText];
							break;
						default:
							return false;
							break;
					}

					if ($layout == '') {
						$totaltext .= ($inputTextArray[0] ?? '') . ($type == 'select' ? $nameText : '') . ($inputTextArray[1] ?? '');
					} else {
						// $tmpText = str_replace('###INPUT###', $inputText, $layout);
						$tmpText = $templateService->substituteSubpart($layout, '###INPUT###', $inputTextArray);

						if (is_array($imageFileArray) && isset($imageFileArray[$key])) {
							$tmpText = str_replace('###IMAGE###', $imageFileArray[$key], $tmpText);
						}
						if (is_array($keyMarkerArray) && isset($keyMarkerArray[$key])) {
							$tmpText = $templateService->substituteMarkerArray(
								$tmpText,
								$keyMarkerArray[$key]
							);
						}
						$totaltext .= $tmpText;
					}
				}
			} // foreach ($valueArray as $key => $parts) {

			if ($bSelectTags && $type == 'select' && $name != '') {
				$mainAttributes = '';
				if (isset($mainAttributeArray) && is_array($mainAttributeArray)) {
					$mainAttributes = self::getAttributeString($mainAttributeArray);
				}
				$result = '<select name="' . $name . '" ' . $mainAttributes . '>' . $totaltext . '</select>';
			} else {
				$result = $totaltext;
			}
		} else {
			$result = false;
		}

		return $result;
	}


	// fetches the valueArray needed for the functions of this class from a valueArray setup
	static public function fetchValueArray ($confArray) {
		$resultArray = [];
		if (is_array($confArray)) {
			foreach ($confArray as $k => $vArray) {
				$resultArray[] =
					[
						0 => $vArray['label'],
						1 => $vArray['value']
					];
			}
		}
		return $resultArray;
	}


	static public function getKeyValueArray ($valueArray) {
		$resultArray = [];

		foreach ($valueArray as $k => $row) {
			$resultArray[$row[1]] = $row[0];
		}
		return $resultArray;
	}

	static protected function getAttributeString ($mainAttributeArray) {
		$useXHTML = !empty($GLOBALS['TSFE']->config['config']['xhtmlDoctype']);
		$resultArray = [];

		if (is_array($mainAttributeArray) && count($mainAttributeArray)) {

			foreach ($mainAttributeArray as $attribute => $value) {
				if (
					$useXHTML ||
					$attribute != 'checked' && $attribute != 'selected' && $attribute != 'disabled'
				) {
					if ($value != '') {
						$resultArray[] = $attribute . '="' . $value . '"';
					}
				} else {
					$resultArray[] = $attribute;
				}
			}
		}
		$result = implode(' ', $resultArray);
		return $result;
	}

	static public function createTag (
		$tag,
		$name,
		$value,
		$preMainAttributes = '',
		$mainAttributes = ''
	) {
		$useXHTML = !empty($GLOBALS['TSFE']->config['config']['xhtmlDoctype']);
		$attributeTextArray = [];
		$attributeArray = [];
		$attributeArray['pre'] = $preMainAttributes;
		$attributeArray['post'] = $mainAttributes;
		$spaceArray = [];
		$spaceArray['pre'] = ($preMainAttributes != '' ? ' ' : '');
		$spaceArray['post'] = ($mainAttributes != '' ? ' ' : '');

		foreach ($attributeArray as $k => $attributes) {
			if (isset($attributes) && is_array($attributes)) {
				$attributeTextArray[$k] = self::getAttributeString($attributes);
			} else {
				if ($attributes != '' && substr($attributes, 0, 1) != ' ') {
					$attributeTextArray[$k] = ' ' . $attributes;
				}
			}
		}

		$flags = ENT_QUOTES;
		$result = '<' . $tag . $spaceArray['pre'] . $attributeTextArray['pre'] . ' name="' . $name . '" value="' . htmlspecialchars($value, $flags) . '"' . $spaceArray['post'] . $attributeTextArray['post'] . ' ' . ($useXHTML ? '/' : '') . '>';

		return $result;
	}
}

