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
 */

/*
	editVariant {
		default {
			params = onchange = "submit();"
		}
		10 {
			sql.where = uid = 1
			suffix = height
		}
	}

note: the price calculation shall not been implemented because it does not make sense to make calculation only on a height. If you have a 3D object, then the surface must be calculated to determine a price. This will be a multiplication of many edit fields.

*/


use TYPO3\CMS\Core\Utility\GeneralUtility;


class tx_ttproducts_edit_variant implements \TYPO3\CMS\Core\SingletonInterface {
	protected $itemTable;

	/**
	 * setting the local variables
	 */
	public function init (&$itemTable) {
		$this->itemTable = $itemTable;
	}

	public function getFieldArray () {

		$result = array();
		$cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
		$conf = $cnfObj->conf;

		if (
			isset($conf['editVariant.']) &&
			is_array($conf['editVariant.'])
		) {
			$editVariantConfig = $conf['editVariant.'];
			$count = 0;

			foreach ($editVariantConfig as $k => $config) {
				if ($k != 'default.' && (strpos($k, '.') == strlen($k) - 1)) {
					$count++;
					if (isset($config['suffix'])) {
						$suffix = $config['suffix'];
					} else {
						$suffix = $count;
					}
					$field = 'edit_' . $suffix;
					$result[] = $field;
				}
			}
		}

        $result = array_unique($result);
		return $result;
	}

	public function getVariantFromRawRow ($row) {
		$fieldArray = $this->getFieldArray();
		$variantArray = array();

		foreach ($row as $field => $value) {
			if (in_array($field, $fieldArray)) {
				$variantArray[] = $field . '=>' . $value;
			}
		}
		$result = implode(tx_ttproducts_variant_int::INTERNAL_VARIANT_SEPARATOR, $variantArray);

		return $result;
	}

	/**
	 * Returns the variant extVar number from the incoming product row and the index in the variant array
	 *
	 * @param	array	the basket raw row
	 * @return  string	  variants separated by variantSeparator
	 * @access private
	 * @see modifyRowFromVariant
	 */
	public function getVariantRowFromProductRow ($row) {
		$variantRow = false;

		if (
			isset($row) &&
			count($row)
		) {
			foreach ($row as $field => $value) {
				if (strpos($field, 'edit_') === 0) {
					$variantRow[$field] = $value;
				}
			}
		}
		return $variantRow;
	}

	public function evalValues ($dataValue, $config) {

		$result = true;
		$listOfCommands = GeneralUtility::trimExplode(',', $config, 1);

		foreach($listOfCommands as $cmd) {
			$cmdParts = preg_split('/\[|\]/', $cmd); // Point is to enable parameters after each command enclosed in brackets [..]. These will be in position 1 in the array.
			$theCmd = trim($cmdParts[0]);

			switch($theCmd) {

				case 'required':
					if (empty($dataValue) && $dataValue !== '0') {
						$result = false;
						break;
					}
				break;

				case 'wwwURL':
					if ($dataValue) {
						$url = 'http://' . $dataValue;
						$report = array();

						if (
							!GeneralUtility::isValidUrl($url) ||
							!GeneralUtility::getUrl($url, 0, false, $report) && $report['error'] != 22 /* CURLE_HTTP_RETURNED_ERROR */
						) {
							$result = false;
							break;
						}
					}
				break;

			}
		}

		return $result;
	}

	public function checkValid (array $config, array $row) {

		$result = true;
		$checkedArray = array();
		$rowConfig = array();
		$resultArray = array();

		foreach ($config as $k => $rowConfig) {

			if ($rowConfig['suffix']) {
				$evalArray = '';
				$rangeArray = '';

				$theField = 'edit_' . $rowConfig['suffix'];

				if (isset($rowConfig['range.'])) {
					$rangeArray = $rowConfig['range.'];
				}
				if (isset($rowConfig['evalValues.'])) {
					$evalArray = $rowConfig['evalValues.'];
				}
				$bValidData = true;

				if (
					!$checkedArray[$theField] &&
					isset($row[$theField])
				) {
					$value = $row[$theField];

					if (
						isset($rangeArray) &&
						is_array($rangeArray) &&
						count($rangeArray)
					) {
						$bValidData = false;

						foreach ($rangeArray as $range) {
							$rangeValueArray = GeneralUtility::trimExplode('-', $range);

							if (
								$rangeValueArray['0'] <= $value &&
								$value <= $rangeValueArray['1']
							) {
								$bValidData = true;
								$checkedArray[$theField] = true;
								break;
							}
						}
					} else if (
						isset($evalArray) &&
						is_array($evalArray) &&
						count($evalArray)
					) {
						foreach ($evalArray as $evalValues) {
							$bValidData = $this->evalValues($value, $evalValues);
							if ($bValidData) {
								$checkedArray[$theField] = true;
								break;
							}
						}
					}
				}

				if (!$bValidData) {
					if (isset($rowConfig['error'])) {
						$resultArray[$theField] = $rowConfig['error'];
					} else {
						$resultArray[$theField] = 'Invalid value: ' . $theField;
					}
				}
			}
		}


		if (is_array($resultArray) && ($resultArray)) {
			$result = $resultArray;
		}

		return $result;
	}

	public function getValidConfig ($row) {

		$result = false;

		$cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
		$conf = $cnfObj->getConf();

		if (isset($conf['editVariant.'])) {
			$editVariantConfig = $conf['editVariant.'];
			$defaultConfig = $editVariantConfig['default.'];
			$count = 0;

			foreach ($editVariantConfig as $k => $config) {
				if ($k == 'default.') {
					// nothing
				} else if (strpos($k, '.') == strlen($k) - 1) {
					$count++;
					$bIsValid = true;
					if (isset($config['sql.']) && isset($config['sql.'])) {
						$bIsValid = tx_ttproducts_sql::isValid($row, $config['sql.']['where']);
					}

					if ($bIsValid) {
						$mergeKeyArray = array('params');
						if (isset($defaultConfig) && is_array($defaultConfig)) {
							foreach ($defaultConfig as $k2 => $config2) {
								if (in_array($k2, $mergeKeyArray)) {
									// merge the configuration with the defaults
									if (isset($config2) && is_array($config2)) {
										if (isset($config[$k2]) && is_array($config[$k2])) {
											$config[$k2] = array_merge($config2, $config[$k2]);
										}
									} else {
										$config[$k2] = $config2 . ' ' . $config[$k2];
									}
								}
							}
						}
						$config['index'] = $count;
						$result[$k] = $config;
					}
				}
			}
		}

		return $result;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_edit_variant.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_edit_variant.php']);
}

