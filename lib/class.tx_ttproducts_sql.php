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
 * functions for creating sql queries on arrays
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;


class tx_ttproducts_sql {
	static public $comparatorConversionArray = array(
		'le' => '<=',
		'lt' => '<',
		'ge' => '>=',
		'gt' => '>'
	);


	static public function transformComparator ($comparator) {
		$result = false;

		$convertedComparator = self::$comparatorConversionArray[$comparator];
		if (isset($convertedComparator)) {
			$result = $convertedComparator;
		}

		return $result;
	}


	static public function getWhere4Field($tablename, $field, $comparator, $comparand) {
		global $TCA;
		$result = '';

		if (isset($TCA[$tablename]) && isset($TCA[$tablename]['columns'][$field])) {
			$tcaConf = $TCA[$tablename]['columns'][$field]['config'];
			if ($tcaConf['eval'] == 'date') {
				$valueString = self::convertDate($comparand);
				if ($valueString === false) {
					return false;
				}
			} else {
				$valueString = $GLOBALS['TYPO3_DB']->fullQuoteStr($comparand, $tablename);
			}
			$result = ' AND ' . $field . ' ' . $comparator . ' ' . $valueString;
		}

		return $result;
	}


	static public function convertDate ($date) {
		$result = false;
		$delimiter = '.';

		if (strpos($date, '-') !== false) {
			$delimiter = '-';
		}
		$dateArray = GeneralUtility::trimExplode($delimiter, $date);

		if (
			MathUtility::canBeInterpretedAsInteger($dateArray[0]) &&
			MathUtility::canBeInterpretedAsInteger($dateArray[1]) &&
			MathUtility::canBeInterpretedAsInteger($dateArray[2])
		) {
			$unixTime = mktime(0, 0, 0, $dateArray[1], $dateArray[0], $dateArray[2]);
			if (isset($unixTime)) {
				$result = $unixTime;
			}
		}
		return $result;
	}


	static public function isValid ($row, $where) {
		$whereArray = GeneralUtility::trimExplode ('AND', $where);
		$isValid = true;

		foreach($whereArray as $k3 => $condition) {

			if (strpos($condition, '=') !== false) {
				if ($condition == '1=1' || $condition == '1 = 1') {
					// nothing: $isValid = true;
				} else {
					$args = GeneralUtility::trimExplode ('=', $condition);

					if ($row[$args[0]] != $args[1]) {
						$isValid = false;
					}
				}
			} else if (strpos($condition, 'IN') !== false) {
				$split = 'IN';
				$isValidRow = false;
				if (strpos($condition, 'NOT IN') !== false) {
					$split = 'NOT IN';
					$isValidRow = true;
				}
				$args = GeneralUtility::trimExplode ($split, $condition);
				$leftBracket = strpos($args[1],'(');
				$rightBracket = strpos($args[1],')');
				if ($leftBracket !== false && $rightBracket !== false) {
					$args[1] = substr($args[1], $leftBracket+1, $rightBracket-$leftBracket-1);
					$argArray = GeneralUtility::trimExplode(',', $args[1]);
					if (is_array($argArray)) {
						foreach($argArray as $arg) {
							$leftQuote = strpos($arg,'\'');
							$rightQuote = strrpos($arg,'\'');
							if ($leftQuote !== false && $rightQuote !== false) {
								$arg = substr($arg, $leftQuote+1, $rightQuote-$leftQuote-1);
							}
							if ($row[$args[0]] == $arg) {
								if ($split == 'IN') {
									$isValidRow = true;
									break;
								} else {
									$isValidRow = false;
									break;
								}
							}
						}
					}
					$isValid = $isValidRow;
				}
            } else if (
                strpos($condition, '<') !== false ||
                strpos($condition, '>') !== false ||
                strpos($condition, '=') !== false
            ) {
                $comparator = '';
                $position = false;
                foreach (self::$comparatorConversionArray as $k => $comparator) {
                    $position = strpos($condition, $comparator);
                    if ($position !== false) {
                        break;
                    }
                }
                if ($position !== false) {
                    $args = array();
                    $args['0'] = trim(substr($condition, 0, $position));
                    $args['1'] = trim(substr($condition, $position + strlen($comparator)));
                    $comparand1 = '';
                    $comparand2 = '';

                    if (isset($row[$args['0']])) {
                        $comparand1 = floatval($row[$args['0']]);
                        $comparand2 = floatval($args['1']);
                    }
                    if ($comparand1 != '') {
                        switch ($comparator) {
                            case '<=':
                                $isValid = ($comparand1 <= $comparand2);
                                break;
                            case '<':
                                $isValid = ($comparand1 < $comparand2);
                                break;
                            case '>=':
                                $isValid = ($comparand1 >= $comparand2);
                                break;
                            case '>':
                                $isValid = ($comparand1 > $comparand2);
                                break;
                        }
                    } else {
                        $isValid = false;
                    }
                } else {
                    $isValid = false;
                }
			} else if ($condition != '') {
				$isValid = false;
			}
			if ($isValid == false) {
				break;
			}
		}
		return ($isValid);
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_sql.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_sql.php']);
}


