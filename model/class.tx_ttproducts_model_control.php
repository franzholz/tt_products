<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2009 Franz Holzinger (franz@ttproducts.de)
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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;



class tx_ttproducts_model_control {
	static public $controlVar = 'ctrl';

	static public $paramsTableArray = array(
		'a' => 'address',
		'article' => 'tt_products_articles',
		'cat' => 'tt_products_cat',
		'pid' => 'pages',
		'product' => 'tt_products',
	);

	static public $pointerParamsCodeArray = array(
		'pointer' => 'CATLIST',
		'pp' => 'LIST',
	);

	static public $basketVar = 'ttp_basket';
	static public $searchboxVar = 'searchbox';
	static private $prefixId = 'tt_products';
	static private $piVars = array();
	static private $andVars = array();
	static private $basketIntoIdPrefix = 'basket-into-id';
	static private $basketInputErrorIdPrefix = 'basket-input-error-id';


		// neu Anfang variant
	static public function determineRegExpDelimiter ($delimiter) {
		$regexpDelimiter = $delimiter;
		if ($delimiter == ';') {
// 			$regexpDelimiter = '[.semicolon.]';
// Leads to MYSQL ERROR:
// Got error 'POSIX collating elements are not supported at offset 46' from regexp

 			$regexpDelimiter = ';';
		}
		return $regexpDelimiter;
	}
		// neu Ende variant


	static public function getBasketIntoIdPrefix () {
		return self::$basketIntoIdPrefix;
	}


	static public function getBasketInputErrorIdPrefix () {
		return self::$basketInputErrorIdPrefix;
	}


	public static function setPrefixId ($prefixId)	{
		self::$prefixId = $prefixId;
	}


	public static function getPrefixId ()	{
		return self::$prefixId;
	}


	static public function getPiVars () {
		if (
			self::$prefixId &&
			!isset(self::$piVars[self::$prefixId])
		) {
			self::$piVars = GeneralUtility::_GPmerged(self::$prefixId);
		}
		$result = self::$piVars;
		return $result;
	}


	public static function getPiVar ($functablename)	{
		$paramsTableArray = self::getParamsTableArray();
		$rc = array_search($functablename,$paramsTableArray);
		return $rc;
	}


	static public function setAndVar ($k, $v) {
		if (isset(self::$andVars[$k])) {
			self::$andVars[$k] .= ',' . $v;
		} else {
			self::$andVars[$k] = $v;
		}
	}


	static public function getAndVar ($k) {
		$result = false;

		if (isset(self::$andVars[$k])) {
			$result = self::$andVars[$k];
		}

		return $result;
	}


	public static function getParamsTableArray ()	{
		return self::$paramsTableArray;
	}


	public static function getPointerPiVar ($theCode)	{
		$pointerParamsTableArray = self::getPointerParamsCodeArray();
		$rc = array_search($theCode,$pointerParamsTableArray);
		return $rc;
	}


	public static function getPointerParamsCodeArray ()	{
		return self::$pointerParamsCodeArray;
	}


	public static function getBasketVar ()	{
		return self::$basketVar;
	}


	public static function getSearchboxVar ()	{
		return self::$searchboxVar;
	}


	public static function getControlVar ()	{
		return self::$controlVar;
	}


	public static function getControlArray ()	{
		$piVars = self::getPiVars();

		$allValueArray = array();
		$rc = array();
		$controlVar = self::getControlVar();
		if (isset($piVars[$controlVar]) && is_array($piVars[$controlVar]))	{
			$rc = $piVars[$controlVar];
		}

		return $rc;
	}


	public static function getTableConfArrays (
		$cObj,
		array $functableArray,
		$theCode,
		array &$tableConfArray,
		array &$viewConfArray
	)	{
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');

		foreach ($functableArray as $ft)	{
			$tableObj = $tablesObj->get($ft,0);
			if (!isset($tableConfArray[$ft])) {
                $tableConfArray[$ft] = $tableObj->getTableConf($theCode);
            }
			if (isset($tableConfArray[$ft]['view.']))	{
				$viewConfArray[$ft] = $tableConfArray[$ft]['view.'];
			}
		}

		if (isset($viewConfArray) && is_array($viewConfArray))	{

			$controlArray = self::getControlArray();
			$typeArray = array('sortSelect', 'filterSelect', 'filterInput');
			$typeSelectArray = array('sortSelect', 'filterSelect');

			foreach ($viewConfArray as $ftname => $funcViewConfArray)	{

				foreach ($typeArray as $type)	{
					if (isset($controlArray[$type]) && is_array($controlArray[$type]))	{
						if (in_array($type, $typeSelectArray))	{
							$fitArray = array();
							foreach ($controlArray[$type] as $k => $v)	{

								if ($v != '')	{
									$valueArray = $funcViewConfArray[$type.'.'][$k.'.']['valueArray.'];
									$bFitValueArray = array();
									foreach ($valueArray as $valueConf)	{
										if (isset($valueConf['field']) && $valueConf['value'] == $v && !$bFitValueArray[$v])	{
											$fitArray[] = array(
												'delimiter' => $valueConf['delimiter'],
												'field' => $valueConf['field'],
												'key' => $valueConf['key'],
												'key.' => $valueConf['key.'],
											);
											$bFitValueArray[$v] = true;
										}
									}
								}
							}
							switch ($type)	{
								case 'sortSelect':
									if (is_array($fitArray) && count($fitArray))	{
										$fieldArray = array();
										foreach ($fitArray as $fitRow)	{
											$fieldArray[] = $fitRow['field'];
										}
										$tableConfArray[$ftname]['orderBy'] = implode(',', $fieldArray);
									}
								break;
								case 'filterSelect':
									foreach ($fitArray as $fitRow)	{
										$field = $fitRow['field'];
										if ($field != '' && isset($fitRow['key']))	{
											if (isset($tableConfArray[$ftname]['filter.']['where.']['field.'][$field]))	{
												$preFilter = '(' . $tableConfArray[$ftname]['filter.']['where.']['field.'][$field] . ') AND (';
											} else {
												$preFilter = '';
											}
											if (isset($fitRow['key.']))	{
												$key = $cObj->stdWrap($fitRow['key'],$fitRow['key.']);
											} else {
												$key = $fitRow['key'];
											}

											$tableConfArray[$ftname]['filter.']['where.']['field.'][$field] = $preFilter . $key . ($preFilter != '' ? ')' : '');
											if ($fitRow['delimiter'] != '')	{
												$tableConfArray[$ftname]['filter.']['delimiter.']['field.'][$field] = $fitRow['delimiter'];
											}
										}
									}
								break;
							}
						} else if ($type == 'filterInput')	{
							foreach ($controlArray[$type] as $k => $v)	{
								if ($v != '')	{
									$fitRow = $funcViewConfArray[$type.'.'][$k.'.'];
									$field = $fitRow['field'];
									$tableConfArray[$ftname]['filter.']['where.']['field.'][$field] = $v;
									if ($fitRow['delimiter'] != '')	{
										$tableConfArray[$ftname]['filter.']['delimiter.']['field.'][$field] = $fitRow['delimiter'];
									}
								}
							}
						}
					}
				}
			}
		}
	}


	public static function getTableVars ($searchFunctablename, &$searchTablename, &$searchAlias, &$tableAliasArray, &$bUseSearchboxArray, &$enableFieldArray)	{

		if ($searchFunctablename != '')	{
			$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');

			$tableObj = $tablesObj->get($searchFunctablename, false);
			$searchTablename = $tableObj->getTablename();
			$searchAlias = $tableObj->getAlias();

			if ($searchTablename != '' && $searchAlias != '')	{
				$tableAliasArray[$searchTablename] = $searchAlias;
				$bUseSearchboxArray[$searchFunctablename] = true;
			}
			$enableFieldArray = $tableObj->getTableObj()->getEnableFieldArray();
		}
	}


	public static function getWhereByFields ($tablename, $alias, $aliasPostfix, $fields, $sword, $delimiter)	{
		$rc = false;
		$fieldArray = GeneralUtility::trimExplode(',',$fields);
		if (isset($fieldArray) && is_array($fieldArray))	{
			$rcArray = array();
			$regexpDelimiter = self::determineRegExpDelimiter($delimiter);

			foreach ($fieldArray as $field)	{
				$rcArray[] = $alias . $aliasPostfix . '.' . $field . ' REGEXP ' . $GLOBALS['TYPO3_DB']->fullQuoteStr('^([[:print:]]*[' . $regexpDelimiter . '])*' . '(' . $sword . ')([[:print:]]*[[:blank:]]*)*([' . $regexpDelimiter . '][[:print:]]*)*$', $tablename);
			}
			$rc = implode(' OR ',$rcArray);
		}
		return $rc;
	}


	public static function getSearchInfo ($cObj, $searchVars,$functablename,$tablename,&$searchboxWhere,&$bUseSearchboxArray, &$sqlTableArray,&$sqlTableIndex,&$latest)	{
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');

		$paramsTableArray = self::getParamsTableArray();
		$searchParamArray = array();
		$searchFieldArray = array();
		$tableAliasArray = array();
		$enableFieldArray = array();
		if (isset($searchVars['latest']))	{
			$latest = $searchVars['latest'];
		}

		$aliasPostfix = '';
		if ($sqlTableIndex) {
			$aliasPostfix = ($sqlTableIndex+1);
		}

		if (isset($searchVars['uid']))	{
			$contentObj = $tablesObj->get('tt_content',false);
			$contentRow = $contentObj->get($searchVars['uid']);

			if($contentRow['pi_flexform']!='')	{

				$contentRow['pi_flexform'] = GeneralUtility::xml2array($contentRow['pi_flexform']);
				$searchObj = GeneralUtility::makeInstance('tx_ttproducts_control_search');	// fetch and store it as persistent object
				$controlConfig = $searchObj->getControlConfig($cObj, $cnf->conf, $contentRow);

				self::getTableVars(
					$controlConfig['local_table'],
					$searchTablename,
					$searchAlias,
					$tableAliasArray,
					$bUseSearchboxArray,
					$enableFieldArray
				);

				$delimiter = '';
				$searchboxWhere =
					self::getWhereByFields(
						$searchTablename,
						$searchAlias,
						$aliasPostfix,
						$controlConfig['fields'],
						$searchVars['sword'],
						$delimiter
					);
			}
		}

		$tmpArray[0] = (is_array($searchVars['local']) ? key($searchVars['local']) : $searchVars['local']);
		if (is_array($searchVars['local']))	{
			$tmpArray[0] = key($searchVars['local']);
			$localParam = current($searchVars['local']);
			if (is_array($localParam))	{
				$tmpArray[1] = key($localParam);
			} else {
				$tmpArray[1] = $localParam;
			}
		} else {
			$tmpArray[0] = $searchVars['local'];
		}
		$searchParamArray['local'] = $tmpArray[0];
		$searchParamArray['foreign'] = $searchVars['foreign'];
		$searchFieldArray['local'] = $tmpArray[1];
		$searchFieldArray['foreign'] = '';

		if (self::getPiVar($functablename) == $searchParamArray['local'])	{
			$sqlTableArray['from'] = array();
			$sqlTableArray['join'] = array();
			$sqlTableArray['local'] = array();
			$sqlTableArray['where'] = array();

			$loopArray = array('local', 'foreign');
			$bUseSearchboxCat = false;
			$theTable = $cnf->getTableName($paramsTableArray[$searchParamArray['local']]);

			foreach ($loopArray as $position)	{
				$positionSearchVars = array();
				$foundKey = 0;

				if ($position == 'local' && isset($keyFieldArray[$searchFieldArray['local']]) && ExtensionManagementUtility::isLoaded('searchbox'))	{	// Todo

					$modelObj = GeneralUtility::makeInstance('tx_searchbox_model');

					$fullKeyFieldArray = $modelObj->getKeyFieldArray($tablename, '', '-', $searchFieldArray['local'], '1', $tmpCount);
				} else if (isset($fullKeyFieldArray)) {
					unset($fullKeyFieldArray);
				}

				foreach ($searchVars as $k => $v)	{
					$searchKey = $k;
					$searchValue = $v;
					if (is_array($v))	{
						$tmpK = key($v);
						$tmpArray = current($v);
						$searchKey .= '|' . $tmpK;
						if (is_array($tmpArray))	{
							$tmpK = key($tmpArray);
							$tmpArray = current($tmpArray);
							$searchKey .= '|' . $tmpK;
						}
						$searchValue = $tmpArray;
					}

					if ($searchKey == $positionSearchVars[$position] || (is_array($searchParamArray[$position]) && key($searchParamArray[$position]) == $k || !is_array($searchParamArray[$position]) && $searchParamArray[$position] == $k))	{
                        if (substr($searchValue, 0, 1) == '\'' && substr($searchValue, -1) == '\'') {
							$searchValue = substr($searchValue,1,strlen($searchValue)-2);
						}
						if (isset($fullKeyFieldArray) && is_array($fullKeyFieldArray))	{
							$tmpArray = GeneralUtility::trimExplode('|',$searchKey);

							if ($tmpArray[1] == $searchFieldArray[$position] && isset($fullKeyFieldArray[$searchValue]))	{
								$searchValue = $fullKeyFieldArray[$searchValue];
							}
						}
						$positionSearchVars[$searchKey] = $searchValue;
						if (!$foundKey)	{
							$foundKey = $k;
						}
					}
				}

				if (isset($searchVars[$position]) && isset($positionSearchVars) && is_array($positionSearchVars) && count($positionSearchVars) && $searchVars[$foundKey] != 'all')	{

					$positionSearchKey = key($positionSearchVars);
					$positionSearchValue = current($positionSearchVars);
 					$partArray = GeneralUtility::trimExplode('|',$positionSearchKey);
 					$delimiter = ($partArray[2] ? $partArray[2] : '');
					$searchTablename = '';
					$searchParam = $partArray[0];
					$searchField = $partArray[1];
					self::getTableVars(
						$paramsTableArray[$searchParam],
						$searchTablename,
						$searchAlias,
						$tableAliasArray,
						$bUseSearchboxArray,
						$enableFieldArray
					);

					if ($searchTablename != '')	{

						$field = ($searchField!='' && isset($GLOBALS['TCA'][$searchTablename]['columns'][$searchField]) ? $searchField : 'title');
						$configArray = $GLOBALS['TCA'][$searchTablename]['columns'][$field]['config'];

						if (isset($configArray) && is_array($configArray) || in_array($field,$enableFieldArray))	{
							if ($configArray['eval'] == 'date')	{
								$searchboxWhere = 'YEAR('.$searchAlias.$aliasPostfix.'.'.$field.')='.$GLOBALS['TYPO3_DB']->fullQuoteStr($positionSearchValue, $searchTablename);
							} else {
								if ($delimiter != '')	{
									if ($searchVars['query'] == 'IN')	{
										$valueArray = array();
										$tmpParamArray = GeneralUtility::trimExplode(',',$positionSearchValue);
										foreach ($tmpParamArray as $param => $v)	{
											if ($v != '')	{
												$valueArray[] = $GLOBALS['TYPO3_DB']->fullQuoteStr($v, $searchTablename);;
											}
										}
										$searchboxWhereArray=array();
										foreach ($valueArray as $v)	{
											$searchboxWhereArray[] = $searchAlias.$aliasPostfix.'.'.$field.' REGEXP '.$GLOBALS['TYPO3_DB']->fullQuoteStr('.*['.$delimiter.']*'.$positionSearchValue.'['.$delimiter.']*.*', $searchTablename);
										}
										$searchboxWhere = implode(' OR ',$searchboxWhereArray);
									} else {
										$searchboxWhere =
											self::getWhereByFields(
												$searchTablename,
												$searchAlias,
												$aliasPostfix,
												$field,
												$positionSearchValue,
												$delimiter
											);
									}
								} else {
									if ($searchVars['query'] == 'IN')	{
										$valueArray = array();
										$tmpParamArray = GeneralUtility::trimExplode(',',$positionSearchValue);
										foreach ($tmpParamArray as $param => $v)	{
											if ($v != '')	{
												$valueArray[] = $v;
											}
										}
										$searchboxWhereArray=array();
										foreach ($valueArray as $v)	{
											$searchboxWhereArray[] = $searchAlias.$aliasPostfix.'.'.$field.' LIKE '.$GLOBALS['TYPO3_DB']->fullQuoteStr($v.'%', $searchTablename);
										}
										$searchboxWhere = '('.implode(' OR ',$searchboxWhereArray).')';
									} else {
										$searchboxWhere = $searchAlias.$aliasPostfix.'.'.$field.' LIKE '.$GLOBALS['TYPO3_DB']->fullQuoteStr($positionSearchValue.'%', $searchTablename);
									}
								}
							}
							$newSqlTableArray = array();
							if ($position == 'foreign')	{
								$foreignTableInfo = $tablesObj->getForeignTableInfo($theTable,$searchFieldArray['local']);
								$foreignTableInfo['table_field'] = $searchFieldArray['local'];
								$tablesObj->prepareSQL($foreignTableInfo,$tableAliasArray,$aliasPostfix,$newSqlTableArray);
							}
							$sqlTableArray[$position][$sqlTableIndex] = $cnf->getTableName($paramsTableArray[$searchParam]);
							if ($foreignTableInfo['where'] != '')	{
								$sqlTableArray['where'][$sqlTableIndex] = $foreignTableInfo['where'];
							}
							if (isset($newSqlTableArray) && is_array($newSqlTableArray))	{
								foreach ($sqlTableArray as $k => $tmpArray)	{
									if (isset($newSqlTableArray[$k]))	{
										$sqlTableArray[$k][$sqlTableIndex] = $newSqlTableArray[$k];
									}
								}
							}
							$sqlTableIndex++;
						}
					}
				}
			}
		}
	}
}



