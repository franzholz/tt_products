<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2016 Franz Holzinger (franz@ttproducts.de)
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
 * article functions without object instance
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */

 
use TYPO3\CMS\Core\Utility\GeneralUtility;


class tx_ttproducts_variant implements tx_ttproducts_variant_int, \TYPO3\CMS\Core\SingletonInterface {
	public $conf;	// reduced local conf
	public $itemTable;
	private $useArticles;
	private $selectableArray;
	public $fieldArray = array();	// array of fields which are variants with ';' or by other characters separated values
	private $selectableFieldArray = array();
	public $firstVariantRow = '';
	public $additionalKey;
	public $additionalField = 'additional';
	private $separator = ';';
	private $splitSeparator = ';';
	private $implodeSeparator = ';';


	/**
	 * setting the local variables
	 */
	public function init (
		$itemTable,
		$tablename,
		$useArticles
	) {
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');

		$tmpArray = $cnf->getTableDesc($tablename);
		$this->conf = (is_array($tmpArray) && is_array($tmpArray['variant.']) ? $tmpArray['variant.'] : array());
		$this->itemTable = $itemTable;
		$this->useArticles = $useArticles;
		$this->selectableArray = array();
		$firstVariantArray = array();
		$fieldArray = $this->conf;
		$additionalKey = '';

		foreach ($fieldArray as $k => $field) {
			if ($field == $this->additionalField) {
				$additionalKey = $k;
			} else if (intval($cnf->conf['select' . ucfirst($field)])) {
				$this->selectableArray[$k] =
					intval($cnf->conf[$this->getSelectConfKey($field)]);
				$this->selectableFieldArray[$k] = $field;
				$firstVariantArray[$k] = 0;
			} else {
				$firstVariantArray[$k] = '';
			}
		}

		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['variantSeparator'] != '') {
			$this->setSeparator($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['variantSeparator']);
		}

		$variantSeparator = $this->getSeparator();
		$splitSeparator = $variantSeparator;
		$implodeSeparator = $variantSeparator;

		if (
			strpos($splitSeparator, '\\n') !== false ||
			strpos($splitSeparator, '\\r') !== false
		) {
			$separator = str_replace('\\r\\n', '\\n', $splitSeparator);
			$separator = str_replace('\\r', '\\n', $separator);
			$splitSeparator = str_replace('\\n', '(\\r\\n|\\n|\\r)', $separator);
			$implodeSeparator = str_replace('\\n', PHP_EOL, $separator);
		}
		$this->setSplitSeparator($splitSeparator);
		$this->setImplodeSeparator($implodeSeparator);

		$this->firstVariantRow = implode($splitSeparator, $firstVariantArray);
		if (isset($additionalKey)) {
			unset($fieldArray[$additionalKey]);
		}
		$this->fieldArray = $fieldArray;
		$this->additionalKey = $additionalKey;

		return true;
	} // init


	public function setSeparator ($separator) {
		$this->separator = $separator;
	}


	public function getSeparator () {
		return $this->separator;
	}


	public function setSplitSeparator ($separator) {
		$this->splitSeparator = $separator;
	}


	public function getSplitSeparator () {
		return $this->splitSeparator;
	}


	public function setImplodeSeparator ($separator) {
		$this->implodeSeparator = $separator;
	}


	public function getImplodeSeparator () {
		return $this->implodeSeparator;
	}


	public function getUseArticles () {
		return $this->useArticles;
	}


	public function getSelectConfKey ($field) {
		$rc = 'select' . ucfirst($field);
		return $rc;
	}


	/**
	 * fills in the row fields from the variant extVar string
	 *
	 * @param	array		the row
	 * @param	string	  variants separated by variantSeparator
	 * @return  void
	 * @access private
	 * @see getVariantFromRow
	 */
	public function modifyRowFromVariant (
		&$row,
		$variant = ''
	) {
		if (!$variant) {
			$variant = $this->getVariantFromRow($row);
		}
		$useArticles = $this->getUseArticles();
		$variantSeparator = $this->getSplitSeparator();

		if (
			$variant != '' &&
			(
				in_array($useArticles, array(1, 3)) ||
				!$useArticles && !empty($this->selectableArray)
			)
		) {
			$variantArray = explode(tx_ttproducts_variant_int::INTERNAL_VARIANT_SEPARATOR, $variant);

			$fieldArray = $this->getFieldArray();
			$count = 0;
			foreach ($fieldArray as $key => $field) {
				if (!empty($this->selectableArray[$key])) {
					if (
						isset($variantArray[$count])
					) {
						$variantValueArray = array();

						if (isset($row[$field])) {
							$theVariant = $row[$field];

							$variantValueArray =
								preg_split(
									'/[\h]*' . $variantSeparator . '[\h]*/',
									$theVariant,
									-1,
									PREG_SPLIT_NO_EMPTY
								);
						}
						$variantIndex = $variantArray[$count];

						if (isset($variantValueArray[$variantIndex])) {
							$row[$field] = $variantValueArray[$variantIndex];
						} else {
							$row[$field] = $variantValueArray['0'] ?? '';
						}
					}
					$count++;
				}
			}
		}
	}


	/**
	 * Returns the variant extVar string from the variant values in the row
	 *
	 * @param	array		the row
	 * @return  string	  variants separated by variantSeparator
	 * @access private
	 * @see modifyRowFromVariant
	 */
	public function getVariantFromRow ($row) {
		$variant = '';

		if (isset($row['ext'])) {
			$extArray = $row['ext'];

			if (
                isset($extArray['tt_products']) &&
				is_array($extArray['tt_products'])
			) {
				reset($extArray['tt_products']);
				$variantRow = current($extArray['tt_products']);
				if (isset($variantRow['vars'])) {
					$variant = $variantRow['vars'];
				}
			}
		}

		return $variant;
	}


	/**
	 * Returns the variant extVar number from the incoming product row and the index in the variant array
	 *
	 * @param	array	the basket raw row
	 * @return  string	  variants separated by internal variantSeparator
	 * @access private
	 * @see modifyRowFromVariant
	 */
	public function getVariantFromProductRow (
		$row,
		$variantRow,
		$useArticles,
		$applySeparator = true
	) {
		$result = false;
		$variantArray = array();
		$variantResultRow = array();
		$variantSeparator = $this->getSplitSeparator();

		if (
			isset($variantRow) &&
			is_array($variantRow) &&
			count($variantRow) &&
			(
				$useArticles == 1 ||
				!empty($this->selectableArray)
			)
		) {
			$fieldArray = $this->getFieldArray();
			$count = 0;

			foreach ($fieldArray as $key => $field) {
				if (!empty($this->selectableArray[$key])) {
					$variantValue = $variantRow[$field] ?? '';

					if ($variantValue != '' && isset($row[$field]) && strlen($row[$field])) {
                        $prodVariantArray =
                            preg_split(
                            '/[\h]*' . $variantSeparator . '[\h]*/',
                            $row[$field],
                            -1,
                            PREG_SPLIT_NO_EMPTY
                        );
						$varantIndex = array_search($variantValue, $prodVariantArray);
						$variantArray[] = $varantIndex;
						$variantResultRow[$field] = $varantIndex;
					} else {
						$variantArray[] = '';
					}
					$count++;
				}
			}
		}

		if ($applySeparator) {
			$result = implode(tx_ttproducts_variant_int::INTERNAL_VARIANT_SEPARATOR, $variantArray);
		} else {
			$result = $variantResultRow;
		}

		return $result;
	}



	/**
	 * Returns the variant extVar number from the incoming raw row into the basket
	 *
	 * @param	array	the basket raw row
	 * @return  string	  variants separated by variantSeparator
	 * @access private
	 * @see modifyRowFromVariant
	 */
	public function getVariantFromRawRow (
		$row,
		$applySeparator = true
	) {
		$result = false;
		$variantArray = array();
		$variantRow = array();
		$useArticles = $this->getUseArticles();
		$selectableArray = $this->getSelectableArray();

		if (
			$useArticles == 1 ||
			!empty($selectableArray)
		) {
			$fieldArray = $this->getFieldArray();
			$count = 0;

			foreach ($fieldArray as $key => $field) {

				if ($selectableArray[$key]) {

					if (isset($row[$field])) {
						$variantValue = $row[$field];
						$variantArray[] = $variantValue;
						$variantRow[$field] = $variantValue;
					} else {
						$variantArray[] = '';
					}
					$count++;
				}
			}
		}

		if ($applySeparator) {
			$result = implode(tx_ttproducts_variant_int::INTERNAL_VARIANT_SEPARATOR, $variantArray);
		} else {
			$result = $variantRow;
		}

		return $result;
	}


	public function getVariantRow ($row = '', $variantArray = array()) {
		$result = '';
		$variantSeparator = $this->getSplitSeparator();

		if (isset($row) && is_array($row)) {
			if (!isset($variantArray)) {
				$variantArray = array();
			}
			$fieldArray = $this->getFieldArray();
			$rcRow = $row;

			foreach ($fieldArray as $field) {
				$variants = $row[$field] ?? '';
				$tmpArray =
					preg_split(
						'/[\h]*' . $variantSeparator . '[\h]*/',
						$variants,
						-1,
						PREG_SPLIT_NO_EMPTY
					);

				$index = (isset($variantArray[$field]) ? $variantArray[$field] : 0);
				$rcRow[$field] = $tmpArray[$index] ?? '';
			}
			$result = $rcRow;
		} else {
			$result = $this->firstVariantRow;
		}
		return $result;
	}


    public function getVariantValuesRow ($row = '') {

        $result = array();

        if (isset($row) && is_array($row)) {
            $fieldArray = $this->getFieldArray();
            foreach ($row as $field => $value) {
                if (in_array($field, $fieldArray)) {
                    $result[$field] = $value;
                }
            }
        }

        return $result;
    }


	public function getTableUid ($table, $uid) {
		$rc = '|' . $table . ':' . $uid;
		return $rc;
	}


	public function getSelectableArray () {
		return $this->selectableArray;
	}


	public function getVariantValuesByArticle (
		$articleRowArray,
		$productRow,
		$withSeparator = false
	) {
		$result = array();

		$selectableFieldArray = $this->getSelectableFieldArray();

		$variantSeparator = $this->getSplitSeparator();
		$variantImplodeSeparator = $this->getImplodeSeparator();

		foreach ($selectableFieldArray as $field) {

			if (
				isset($productRow[$field])
			) {
				$valueArray = array();

				$productValueArray =
					preg_split(
						'/[\h]*' . $variantSeparator . '[\h]*/',
						$productRow[$field],
						-1,
						PREG_SPLIT_NO_EMPTY
					);

				foreach ($articleRowArray as $articleRow) {
					$articleValueArray =
						preg_split(
							'/[\h]*' . $variantSeparator . '[\h]*/',
							$articleRow[$field],
							-1,
							PREG_SPLIT_NO_EMPTY
						);

					if (!empty($articleValueArray['0'])) {
						$valueArray = array_merge($valueArray, $articleValueArray);
					}
				}
				$valueArray = array_values(array_unique($valueArray));

				if (!empty($productValueArray)) {
					$sortedValueArray = array();
					foreach ($productValueArray as $value) {
						if (in_array($value, $valueArray)) {
							$sortedValueArray[] = $value;
						}
					}
					$valueArray = $sortedValueArray;
				}

				if ($withSeparator) {
					$result[$field] = implode($variantImplodeSeparator, $valueArray);
				} else {
					$result[$field] = $valueArray;
				}
			}
		}

		return $result;
	}


	// the article rows must be in the correct order already
	public function filterArticleRowsByVariant (
		$row,
		$variant,
		$articleRowArray,
		$bCombined = false
	) {
		$result = false;

		$variantRowArray = $this->getVariantValuesByArticle($articleRowArray, $row, false);
		$variantSeparator = $this->getSplitSeparator();

		foreach ($variantRowArray as $field => $valueArray) {
			if (isset($row[$field]) && $row[$field] != '') {

				$variantRowArray[$field] =
					preg_split(
						'/[\h]*' . $variantSeparator . '[\h]*/',
						$row[$field],
						-1,
						PREG_SPLIT_NO_EMPTY
					);
			}
		}

		$variantArray =
			preg_split(
				'/[\h]*' . tx_ttproducts_variant_int::INTERNAL_VARIANT_SEPARATOR . '[\h]*/',
				$variant,
				-1
			);

		$selectableFieldArray = $this->getSelectableFieldArray();
		$possibleArticleArray = array();

		if (
			isset($articleRowArray) && is_array($articleRowArray) &&
			isset($this->selectableArray) && is_array($this->selectableArray)
		) {
			$result = array();
		}

		foreach ($articleRowArray as $articleRow) {
			$bMatches = true;
			$vCount = 0;

			foreach ($this->selectableArray as $k => $v) {

				if ($v) {
					$variantIndex = $variantArray[$vCount]; // $k-1
					$field = $selectableFieldArray[$k];

					if (
						!\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($variantIndex) &&
						isset($variantRowArray[$field]) &&
						is_array($variantRowArray[$field])
					) {
						$variantIndex = array_search($variantIndex, $variantRowArray[$field]);
					}

					$value = $articleRow[$field];

					if ($value != '') {

						if ($variantIndex === false) {
							$bMatches = false;
							break;
						} else {
							if (is_array($value)) {
								// nothing
								$valueArray = $value;
							} else {
								$valueArray =
									preg_split(
										'/[\h]*' . $variantSeparator . '[\h]*/',
										$value,
										-1,
										PREG_SPLIT_NO_EMPTY
									);
							}
							$variantValue = $variantRowArray[$field][$variantIndex];

							if (!in_array($variantValue, $valueArray)) {
								$bMatches = false;
								break;
							}
						}
					} else if (!$bCombined) {
						$bMatches = false;
						break;
					}
				}
				$vCount++;
			} // foreach ($this->selectableArray)

			if ($bMatches) {
				$result[] = $articleRow;
			}
		} // foreach ($articleRowArray)

		return $result;
	}


	public function getFieldArray () {

		return $this->fieldArray;
	}


	public function getSelectableFieldArray () {
		return $this->selectableFieldArray;
	}


	public function getAdditionalKey () {
		return $this->additionalKey;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_variant.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_variant.php']);
}

