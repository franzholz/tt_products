<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2011 Franz Holzinger (franz@ttproducts.de)
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
 * base class for all database table classes
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */



abstract class tx_ttproducts_table_base_view  implements t3lib_Singleton {
	private $bHasBeenInitialised = FALSE;
	public $cObj;
	public $conf;
	public $config;
	public $piVar;
	public $modelObj;
	public $langObj;
	public $marker;		// can be overridden
	public $tablesWithoutView = array('tt_products_emails');


	public function init ($langObj, $modelObj)	{
		$this->langObj = $langObj;
		$this->modelObj = $modelObj;
		$this->cObj = $langObj->cObj;
		$this->conf = &$modelObj->conf;
		$this->config = &$modelObj->config;

		$this->bHasBeenInitialised = TRUE;
	}


	public function needsInit ()	{
		return !$this->bHasBeenInitialised;
	}


	public function destruct ()	{
		$this->bHasBeenInitialised = FALSE;
	}


	public function setConf ($conf)	{
		$this->conf = $conf;
	}


	public function getConf ()	{
		return $this->conf;
	}

	public function setCObj ($cObj)	{
		$this->cObj = $cObj;
	}

	public function getCObj ()	{
		return $this->cObj;
	}


	public function getModelObj ()	{
		return $this->modelObj;
	}


	public function getFieldObj ($field)	{
		$classAndPath = $this->getFieldClassAndPath($field);
		if ($classAndPath['class'])	{
			$rc = $this->getObj($classAndPath);
		}
		return $rc;
	}


	public function getPivar ()	{
		return $this->piVar;
	}


	public function setPivar ($piVar)	{
		$this->piVar = $piVar;
	}


	public function setMarker ($marker)	{
		$this->marker = $marker;
	}


	public function getMarker ()	{
		return $this->marker;
	}


	public function getOuterSubpartMarker ()	{
		$marker = $this->getMarker();
		return '###'.$marker.'_ITEMS###';
	}


	public function getInnerSubpartMarker ()	{
		$marker = $this->getMarker();
		return '###ITEM_'.$marker.'###';
	}


	public function getObj ($classArray)	{
		$className = $classArray['class'];
		$classNameView = $className.'_view';
		$path = $classArray['path'];

// 		include_once ($path.'view/field/class.'.$classNameView.'.php');
		$fieldViewObj = t3lib_div::makeInstance(''.$classNameView);	// fetch and store it as persistent object
		if (!is_object($fieldViewObj)) {
			throw new RuntimeException('Error in tt_products: The class "' . $classNameView . '" is not found.', 50001);
		}

		if ($fieldViewObj->needsInit())	{
// 			include_once ($path.'model/field/class.'.$className.'.php');
			$fieldObj = t3lib_div::makeInstance(''.$className);	// fetch and store it as persistent object
			if (!is_object($fieldObj)) {
				throw new RuntimeException('Error in tt_products: The class "' . $className . '" is not found.', 50002);
			}
			if ($fieldObj->needsInit())	{
				$fieldObj->init($this->cObj);
			}
			$fieldViewObj->init($this->langObj, $fieldObj);
		}
		return $fieldViewObj;
	}


	public function getFieldClassAndPath ($fieldname)	{
		$rc = $this->getModelObj()->getFieldClassAndPath($fieldname);
		return $rc;
	}


	public function getItemSubpartArrays (
		&$templateCode,
		$functablename,
		$row,
		&$subpartArray,
		&$wrappedSubpartArray,
		&$tagArray,
		$theCode='',
		$basketExtra=array(),
		$id=''
	)	{
		global $TCA;

		$tablesObj = t3lib_div::makeInstance('tx_ttproducts_tables');
		$cnf = t3lib_div::makeInstance('tx_ttproducts_config');
		$tableconf = $cnf->getTableConf($functablename, $theCode);

		if (
			isset($row) &&
			is_array($row) &&
			!empty($row)
		) {
			$newRow = $row;
			$addedFieldArray = array();
			foreach ($row as $field => $value)	{

				$classAndPath = $this->getFieldClassAndPath($field);
				if ($classAndPath['class'])	{
					$fieldViewObj = $this->getObj($classAndPath);
					if (method_exists($fieldViewObj, 'modifyItemSubpartRow'))	{
						$newRow = $fieldViewObj->modifyItemSubpartRow($field, $newRow, $addedFieldArray);
					}
				}
			}
			$row = $newRow;
			$comparatorArray = array('EQ' => '==', 'NE' => '!=', 'LT' => '<', 'LE' => '<=', 'GT' => '>', 'GE' => '>=');
			$operatorArray = array('AND', 'OR');
			$functionArray = array('EMPTY' => 'empty');
			$binaryArray = array('NOT' => '!');

			if (is_array($tagArray))	{
				foreach ($tagArray as $tag => $v1)	{
					if (strpos($tag, $this->marker) === 0)	{

						$bCondition = FALSE;
						$tagPartArray = explode('_', $tag);
						$tagCount = count($tagPartArray);
						$bTagProcessing = FALSE;
						$fnKey = array_search('FN', $tagPartArray);

						if ($tagCount > 2 && $fnKey !== FALSE) {
							$bTagProcessing = TRUE;
							$tagPartKey = $fnKey + 1;
							$fieldNameArray = array();
							for ($i = 1; $i < $fnKey; ++$i) {
								$fieldNameArray[] = $tagPartArray[$i];
							}
							$fieldname = strtolower(implode('_', $fieldNameArray));

							$binaryOperator = '';
							$v2 = $binaryArray[$tagPartArray[$tagPartKey]];
							if ($v2 != '') {
								$binaryOperator = $v2;
								$tagPartKey++;
							}
							$v3 = $functionArray[$tagPartArray[$tagPartKey]];
							if ($v3 != '') {
								$functionname = $v3;
								$value = $row[$fieldname];
								$evalString = 'return ' . $binaryOperator . $functionname . '($value);';
								$bCondition = eval($evalString);
							}
						} else if ($tagCount > 2 && isset($comparatorArray[$tagPartArray[$tagCount - 2]]))	{
							$bTagProcessing = TRUE;
							$comparator = $tagPartArray[$tagCount - 2];
							$comparand = $tagPartArray[$tagCount - 1];
							$fieldname = strtolower($tagPartArray[1]);
							if ($tagCount > 4)	{
								for ($i = 2; $i <= $tagCount - 3; ++$i)	{
									$fieldname .= '_' . strtolower($tagPartArray[$i]);
								}
							}
							if (!isset($row[$fieldname]))	{
								$upperFieldname = strtoupper($fieldname);
								$foundDifferentCase = FALSE;
								foreach ($row as $field => $v2)	{
									if (strtoupper($field) == $upperFieldname)	{
										$foundDifferentCase = TRUE;
										$fieldname = $field;
										break;
									}
								}
								if (!$foundDifferentCase)	{
									continue;
								}
							}

							$fieldArray = array($fieldname => array($comparator, intval($comparand)));

							foreach ($fieldArray as $field => $fieldCondition)	{
								$comparator = $comparatorArray[$fieldCondition[0]];

								if (isset($row[$field]) && $comparator != '')	{
									$evalString = "return $row[$field]$comparator$fieldCondition[1];";

									$bCondition = eval($evalString);
									// eval("return ".$row[$field].$comparator.$fieldArray[1].";");
								}
							}
						}

						if ($bTagProcessing) {
							if ($bCondition == TRUE)	{
								$wrappedSubpartArray['###' . $tag . '###'] = '';
							} else {
								$subpartArray['###' . $tag . '###'] = '';
							}
						}
					}
				}
			}
			$itemTableObj = $tablesObj->get($functablename, FALSE);
			$tablename = $itemTableObj->getTablename();

			foreach ($row as $field => $value)	{
				$upperField = strtoupper($field);

				if (isset ($TCA[$tablename]['columns'][$field]) && is_array($TCA[$tablename]['columns'][$field]) &&
				$TCA[$tablename]['columns'][$field]['config']['type'] == 'group')	{
					$markerKey = $this->marker.'_HAS_'.$upperField;
					$valueArray = t3lib_div::trimExplode(',', $value);
					foreach ($valueArray as $k => $partValue)	{
						$partMarkerKey = $markerKey.($k+1);
						if (isset($tagArray[$partMarkerKey]))	{
							if ($partValue)	{
								$wrappedSubpartArray['###' . $partMarkerKey . '###'] = array('','');
							} else {
								$subpartArray['###' . $partMarkerKey . '###'] = '';
							}
						}
					}
					for ($i=count($valueArray); $i<100; ++$i)	{
						$partMarkerKey = $markerKey.($i);
						if (isset($tagArray[$partMarkerKey]) && !isset($wrappedSubpartArray['###' . $partMarkerKey . '###']))	{
							$subpartArray['###' . $partMarkerKey . '###'] = '';
						}
					}
				}

				$classAndPath = $this->getFieldClassAndPath($field);
				if ($classAndPath['class'])	{
					$fieldViewObj = $this->getObj($classAndPath);
					if (method_exists($fieldViewObj, 'getItemSubpartArrays'))	{
						$itemSubpartArray = array();
						$fieldViewObj->getItemSubpartArrays(
							$templateCode,
							$this->marker,
							$functablename,
							$row,
							$field,
							$tableconf,
							$itemSubpartArray,
							$wrappedSubpartArray,
							$tagArray,
							$theCode,
							$basketExtra,
							$id
						);
						$subpartArray = array_merge($subpartArray, $itemSubpartArray);
					}
				}
			}
			$markerKey = $this->marker.'_NOT_EMPTY';
			if (isset($tagArray[$markerKey]))	{
				$wrappedSubpartArray['###' . $markerKey . '###'] = '';
			}
		} else { // if !empty($row)
			$itemTableObj = $tablesObj->get($functablename, FALSE);
			$tablename = $itemTableObj->getTablename();
			$markerKey = $this->marker . '_NOT_EMPTY';
			if (isset($tagArray[$markerKey]))	{
				$subpartArray['###' . $markerKey . '###'] = '';
			}
		}
	}


	public function getMarkerKey ($markerKey)	{
		if ($markerKey != '')	{
			$marker = $markerKey;
		} else {
			if ($this->marker)	{
				$marker = $this->marker;
			} else {
				$functablename = $this->getModelObj()->getFuncTablename();
				$marker = strtoupper($functablename);
			}
		}
		return $marker;
	}


	public function getId (&$row, $midId, $theCode)	{
		$functablename = $this->getModelObj()->getFuncTablename();
		$extTableName = str_replace('_','-',$functablename);
		$preId = $extTableName;
		if ($midId)	{
			$preId .= '-'.$midId;
		}
		$rc = $preId.'-'.str_replace('_','-',strtolower($theCode)).'-'.intval($row['uid']);
		return $rc;
	}


	// This can also add additional fields to the row.
	public function getRowMarkerArray (
		$row,
		$markerKey,
		&$markerArray,
		&$variantFieldArray,
		&$variantMarkerArray,
		&$tagArray,
		$theCode,
		$basketExtra,
		$bHtml=TRUE,
		$charset='',
		$imageNum=0,
		$imageRenderObj='image',
		$id='',	// id part to be added
		$prefix='', // if FALSE, then no table marker will be added
		$suffix='',	// this could be a number to discern between repeated rows
		$linkWrap=''
	)	{
		global $TSFE,$TCA;

		$rowMarkerArray = array();
		if ($prefix === FALSE)	{
			$marker = '';
		} else {
			$markerKey = $this->getMarkerKey($markerKey);
			$marker = $prefix.$markerKey;
		}


		if (is_array($row) && $row['uid'])	{

			$newRow = $row;
			$addedFieldArray = array();
			foreach ($row as $field => $value)	{

				$classAndPath = $this->getFieldClassAndPath($field);
				if ($classAndPath['class'])	{
					$fieldViewObj = $this->getObj($classAndPath);
					if (method_exists($fieldViewObj, 'modifyItemSubpartRow'))	{
						$newRow = $fieldViewObj->modifyItemSubpartRow($field, $newRow, $addedFieldArray);
					}
				}
			}
			$row = $newRow;

			$functablename = $this->getModelObj()->getFuncTablename();
			$extTableName = str_replace('_','-',$functablename);
			$mainId = $this->getId($row, $id, $theCode);
			$markerPrefix = ($marker != '' ? $marker.'_' : '');
			$rowMarkerArray['###' . $markerPrefix . 'ID###'] = $mainId;

			$rowMarkerArray['###' . $markerPrefix . 'NAME###'] = $extTableName . '-' . $row['uid'];
			$cnf = t3lib_div::makeInstance('tx_ttproducts_config');
			$tableconf = $cnf->getTableConf($functablename,$theCode);
			$tabledesc = $cnf->getTableDesc($functablename);

			$fieldMarkerArray = array();

			foreach ($row as $field => $value)	{
				$viewField = $field;
				$markerKey = $markerPrefix . strtoupper($viewField . $suffix);
				$fieldMarkerArray['###' . $markerKey . '###'] = $value;
			}

			foreach ($row as $field => $value)	{
				if (in_array($field, $addedFieldArray))	{
					continue; // do not handle the added fields here. They must be handled with the original field.
				}
				$viewField = $field;
				$bSkip = FALSE;
				$theMarkerArray = &$rowMarkerArray;
				$fieldId = $mainId . '-' . $viewField;
				$markerKey = $markerPrefix . strtoupper($viewField . $suffix);
				if (isset($tagArray[$markerKey . '_ID']))	{
					$rowMarkerArray['###' . $markerKey . '_ID###'] = $fieldId;
				}

				if (is_array($variantFieldArray) && is_array($variantMarkerArray) && in_array($field, $variantFieldArray))	{
					$className = 'tx_ttproducts_field_text';
					$theMarkerArray = &$variantMarkerArray;
					$classAndPath = array();
				} else {
					$classAndPath = $this->getFieldClassAndPath($field);
				}
				$modifiedRow = array($field => $value);

				if ($classAndPath['class'])	{

					$fieldViewObj = $this->getObj($classAndPath);
					$modifiedRow =
						$fieldViewObj->getRowMarkerArray(
							$functablename,
							$field,
							$row,
							$markerKey,
							$theMarkerArray,
							$tagArray,
							$theCode,
							$fieldId,
							$basketExtra,
							$bSkip,
							$bHtml,
							$charset,
							$prefix,
							$suffix,
							$imageRenderObj
						);

					if (isset($modifiedRow) && !is_array($modifiedRow))	{ // if a single value has been returned instead of an array
						$modifiedRow = array($field => $modifiedRow);
					} else if (!isset($modifiedRow))	{ // restore former default value
						$modifiedRow = array($field => $value);
					}
				} else {
					switch ($field)	{
						case 'ext':
							$bSkip = TRUE;
							break;
						default:
							// nothing
							break;
					}
				}

				if (!$bSkip)	{
					$tableName = $this->conf['table.'][$functablename];

					foreach ($modifiedRow as $modField => $modValue)	{
						if (is_array($tableconf['field.'][$modField . '.']))	{
							if ($tableconf['field.'][$modField . '.']['untouched']) {
								$modValue = $row[$modField];
							}
							$tableconf['field.'][$modField . '.']['value'] = $modValue;

							$fieldContent = $this->cObj->cObjGetSingle(
								$tableconf['field.'][$modField],
								$tableconf['field.'][$modField . '.'],
								TT_PRODUCTS_EXT
							);
							$modValue = $this->cObj->substituteMarkerArray($fieldContent,$fieldMarkerArray);
						}
						$markerKey = $markerPrefix . strtoupper($modField . $suffix);


						if (!isset($markerArray['###' . $markerKey . '###'])) {

							$theMarkerArray['###' . $markerKey . '###'] = $modValue;
						}
					}
				}
			}
		} else {
			$tablesObj = t3lib_div::makeInstance('tx_ttproducts_tables');
			$tablename = $this->getModelObj()->getTablename();
			$tmpMarkerArray = array();
			$tmpMarkerArray[] = $marker;

			if (isset($TCA[$tablename]['columns']) && is_array($TCA[$tablename]['columns']))	{
				foreach ($TCA[$tablename]['columns'] as $theField => $confArray)	{

					if ($confArray['config']['type'] == 'group')	{
						$foreigntablename = $confArray['config']['foreign_table'];
                        if (
                            $foreigntablename != '' &&
                            !in_array($foreigntablename, $this->tablesWithoutView)
                        ) {
							$foreignTableViewObj = $tablesObj->get($foreigntablename, TRUE);
							if (is_object($foreignTableViewObj))	{
								$foreignMarker = $foreignTableViewObj->getMarker();
								$tmpMarkerArray[] = $foreignMarker;
							}
						}
					}
				}
			}
			if (isset($tagArray) && is_array($tagArray))	{
				foreach ($tagArray as $theTag => $v)	{
					foreach ($tmpMarkerArray as $theMarker)	{
						if (strpos($theTag,$theMarker) === 0)	{
							$rowMarkerArray['###' . $theTag . '###'] = '';
						}
					}
				}
			}
		}

		$this->getRowMarkerArrayHooks(
			$this,
			$rowMarkerArray,
			$cObjectMarkerArray,
			$row,
			$imageNum,
			$imageRenderObj,
			$forminfoArray,
			$theCode,
			$basketExtra,
			$mainId,
			$linkWrap
		);
		$markerArray = array_merge($markerArray, $rowMarkerArray);
	}


	protected function getRowMarkerArrayHooks ($pObj, &$markerArray, &$cObjectMarkerArray, $row, $imageNum, $imageRenderObj, &$forminfoArray, $theCode, $basketExtra, $id, &$linkWrap)	{

			// Call all getRowMarkerArray hooks at the end of this method
		$marker = $this->getMarker();

		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT][$marker])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT][$marker] as $classRef) {
				$hookObj = t3lib_div::makeInstance($classRef);

				if (method_exists($hookObj, 'getRowMarkerArray')) {
					$hookObj->getRowMarkerArray($pObj, $markerArray, $cObjectMarkerArray, $row, $imageNum, $imageRenderObj, $forminfoArray, $theCode, $basketExtra, $id, $linkWrap);
				}
			}
		}
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_table_base_view.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_table_base_view.php']);
}

?>
