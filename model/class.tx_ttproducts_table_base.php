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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;


abstract class tx_ttproducts_table_base implements \TYPO3\CMS\Core\SingletonInterface	{
	public $bHasBeenInitialised = false;
	public $cObj;
	public $conf;
	public $config;
	public $tableObj;	// object of the type tx_table_db
	public $defaultFieldArray=array('uid'=>'uid', 'pid'=>'pid'); // fields which must always be read in
	public $relatedFromTableArray=array();
	protected $insertRowArray;	// array of stored insert records
	protected $insertKey;		// array for insertion
	public $fieldArray = array(); // field replacements

	protected $tableAlias;	// must be overridden
	protected $dataArray;

	private $functablename;
	private $tablename;
	private $tableConf;
	private $tableDesc;
	private $theCode;
	private $orderBy;

	private $fieldClassArray = array (
			'ac_uid' => 'tx_ttproducts_field_foreign_table',
			'crdate' => 'tx_ttproducts_field_datetime',
			'creditpoints' => 'tx_ttproducts_field_creditpoints',
			'datasheet' => 'tx_ttproducts_field_datafield',
			'directcost' => 'tx_ttproducts_field_price',
			'endtime' => 'tx_ttproducts_field_datetime',
			'graduated_price_uid' => 'tx_ttproducts_field_graduated_price',
			'image' => 'tx_ttproducts_field_image',
			'image_uid' => 'tx_ttproducts_field_image',
			'smallimage' => 'tx_ttproducts_field_image',
			'smallimage_uid' => 'tx_ttproducts_field_image',
			'itemnumber' => 'tx_ttproducts_field_text',
			'note' => 'tx_ttproducts_field_note',
			'note2' => 'tx_ttproducts_field_note',
			'price' => 'tx_ttproducts_field_price',
			'price2' => 'tx_ttproducts_field_price',
			'sellendtime' => 'tx_ttproducts_field_datetime',
			'sellstarttime' => 'tx_ttproducts_field_datetime',
			'starttime' => 'tx_ttproducts_field_datetime',
			'subtitle' => 'tx_ttproducts_field_text',
			'tax' => 'tx_ttproducts_field_tax',
			'title' => 'tx_ttproducts_field_text',
			'tstamp' => 'tx_ttproducts_field_datetime',
			'usebydate' => 'tx_ttproducts_field_datetime',
		);

	public function init ($cObj, $functablename)	{

		$this->cObj = $cObj;
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$this->conf = &$cnf->conf;
		$this->config = &$cnf->config;

		$this->tableObj = GeneralUtility::makeInstance('tx_table_db');
		$this->insertKey = 0;

		$this->setFuncTablename($functablename);
		$tablename = $cnf->getTableName($functablename);
		$tablename = ($tablename ? $tablename : $functablename);
		$this->setTablename($tablename);
		$this->tableDesc = $cnf->getTableDesc($functablename);

		$checkDefaultFieldArray = array('tstamp'=>'tstamp', 'crdate'=>'crdate', 'hidden'=>'hidden', 'deleted' => 'deleted');

		if (isset($GLOBALS['TCA'][$tablename]['ctrl']) && is_array($GLOBALS['TCA'][$tablename]['ctrl']))	{
			foreach ($checkDefaultFieldArray as $theField)	{
				if (isset($GLOBALS['TCA'][$tablename]['ctrl'][$theField]) && is_array($GLOBALS['TCA'][$tablename]['columns'][$theField]) ||
					in_array($theField,$GLOBALS['TCA'][$tablename]['ctrl'],true) ||
					isset($GLOBALS['TCA'][$tablename]['ctrl']['enablecolumns']) && is_array($GLOBALS['TCA'][$tablename]['columns']['enablecolumns']) && in_array($theField,$GLOBALS['TCA'][$tablename]['ctrl']['enablecolumns'],true)
				)	{
					$this->defaultFieldArray[$theField] = $theField;
				}
			}
		}

		if (isset($this->tableDesc) && is_array($this->tableDesc))	{
			$this->fieldArray = array_merge($this->fieldArray, $this->tableDesc);
		}

		$this->fieldArray['name'] = ($this->tableDesc['name'] && is_array($GLOBALS['TCA'][$this->tableDesc['name']]['ctrl']) ? $this->tableDesc['name'] : ($GLOBALS['TCA'][$tablename]['ctrl']['label'] ? $GLOBALS['TCA'][$tablename]['ctrl']['label'] : 'name'));
		$this->defaultFieldArray[$this->fieldArray['name']] = $this->fieldArray['name'];

		if (isset($this->defaultFieldArray) && is_array($this->defaultFieldArray) && count($this->defaultFieldArray))	{
			$this->tableObj->setDefaultFieldArray($this->defaultFieldArray);
		}

		$this->tableObj->setName($tablename);
		$this->tableObj->setTCAFieldArray($tablename, $this->tableAlias);
		$this->tableObj->setNewFieldArray();
		$this->bHasBeenInitialised = true;
		$this->tableConf = $this->getTableConf('');
		$this->initCodeConf('ALL', $this->tableConf);
	}


	public function clear ()	{
		$this->dataArray = array();
	}


	public function getField ($theField)	{
		$rc = $theField;
		if (isset($this->fieldArray[$theField]))	{
			$rc = $this->fieldArray[$theField];
		}
		return $rc;
	}


	/* uid can be a string. Add a blank character to your uid integer if you want to have muliple rows as a result
	*/
	public function get ($uid='0', $pid=0, $bStore=true, $where_clause='', $groupBy='', $orderBy='', $limit='', $fields='', $bCount=false, $aliasPostfix='', $fallback = false) {
		$rc = false;
		$tableObj = $this->getTableObj();
		$alias = $this->getAlias() . $aliasPostfix;
		if (
			tx_div2007_core::testInt($uid) &&
			isset($this->dataArray[$uid]) &&
			is_array($this->dataArray[$uid]) &&
			!$where_clause &&
			!$fields
		) {
			if (!$pid || ($pid && $this->dataArray[$uid]['pid'] == $pid))	{
				$rc = $this->dataArray[$uid];
			} else {
				$rc = array();
			}
		}

		if (!$rc) {
			$needsEnableFields = true;
			$enableFields = $tableObj->enableFields($aliasPostfix);
			$where = '1=1';

			if (is_int($uid))	{
				$where .= ' AND ' . $alias . '.uid = ' . intval($uid);
			} else if($uid)	{
				$uidArray = GeneralUtility::trimExplode(',',$uid);
				foreach ($uidArray as $k => $v)	{
					$uidArray[$k] = intval($v);
				}
				$where .= ' AND ' . $alias . '.uid IN (' . implode(',', $uidArray) . ')';
			}
			if ($pid)	{
				$pidArray = GeneralUtility::trimExplode(',', $pid);
				foreach ($pidArray as $k => $v)	{
					$pidArray[$k] = intval($v);
				}
				$where .= ' AND ' . $alias . '.pid IN (' . implode(',', $pidArray) . ')';
			}
			if ($where_clause)	{
				if (strpos($where_clause, $enableFields) !== false) {
					$needsEnableFields = false;
				}
				$where .= ' AND ( '.$where_clause.' )';
			}
			if ($needsEnableFields) {
				$where .= $enableFields;
			}

			if (!$fields)	{
				if ($bCount)	{
					$fields = 'count(*)';
				} else {
					$fields = '*';
				}
			}

			// Fetching the records
			$res = $tableObj->exec_SELECTquery($fields, $where, $groupBy, $orderBy, $limit, '', $aliasPostfix, $fallback);

			if ($res !== false)	{

				$rc = array();

				while ($dbRow = $GLOBALS['TYPO3_DB']->sql_fetch_row($res)) {
					$row = array();
					foreach ($dbRow as $index => $value) {
						if ($res instanceof mysqli_result) {
							$fieldObject = mysqli_fetch_field_direct($res, $index);
							$field = $fieldObject->name;
						} else {
							$field = mysql_field_name($res, $index);
						}

						if (!isset($row[$field]) || !empty($value)) {
							$row[$field] = $value;
						}
					}

					if (!$fallback && is_array($tableObj->langArray) && $tableObj->langArray[$row['title']]) {
						$row['title'] = $tableObj->langArray[$row['title']];
					}

					if ($row)	{
						$rc[$row['uid']] = $row;
						if($bStore && $fields == '*')	{
							$this->dataArray[$row['uid']] = $row;
						}
					} else {
						break;
					}
				}

				$GLOBALS['TYPO3_DB']->sql_free_result($res);
				if (
					tx_div2007_core::testInt($uid)
				) {
					reset($rc);
					$rc = current ($rc);
				}

				if ($bCount && is_array($rc[0])) {
					reset($rc[0]);
					$rc = intval(current($rc[0]));
				}

				if (!$rc) {
					$rc = array();
				}
			} else {
				$rc = false;
			}
		}

		return $rc;
	}


	/**
	 * Returns the label of the record, Usage in the following format:
	 *
	 * @param	array		$row: current record
	 * @return	string		Label of the record
	 */
	public function getLabel ($row) {

		return $row['title'];
	}


	public function getCobj ()	{
		return $this->cObj;
	}


	public function setCobj ($cObj)	{
		$this->cObj = $cObj;
	}


	public function needsInit () {
		return !$this->bHasBeenInitialised;
	}


	public function destruct () {
		$this->bHasBeenInitialised = false;
	}


	public function getDefaultFieldArray ()	{
		return $this->defaultFieldArray;
	}


	public function getFieldClassAndPath ($fieldname)	{
		$class = '';
		$path = '';
		$tablename = $this->getTablename();

        if (
            $fieldname &&
            (
                isset($GLOBALS['TCA'][$tablename]['columns'][$fieldname]) &&
                is_array($GLOBALS['TCA'][$tablename]['columns'][$fieldname])
            ) ||
            (
                isset($GLOBALS['TCA'][$tablename]['columns'][$fieldname . '_uid']) &&
                is_array($GLOBALS['TCA'][$tablename]['columns'][$fieldname . '_uid'])
            )
        ) {
			$funcTablename = $this->getFuncTablename();

			if (
				isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['fieldClass']) &&
				is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['fieldClass']) &&
				isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['fieldClass'][$funcTablename]) &&
				is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['fieldClass'][$funcTablename])
			)	{
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['fieldClass'][$funcTablename] as $extKey => $hookArray)	{
					if (ExtensionManagementUtility::isLoaded($extKey)) {
						$class = $hookArray[$fieldname];
						if ($class)	{
							$path = ExtensionManagementUtility::extPath($extKey);
							break;
						}
					}
				}
			}

			if (!$class)	{
				$class = $this->fieldClassArray[$fieldname];
				if ($class)	{
					$path = PATH_BE_ttproducts;
				}
			}
		}
		$rc = array('class' => $class, 'path' => $path);
		return $rc;
	}


	public function getAlias ()	{
		$tableObj = $this->getTableObj();
		return $tableObj->getAlias();
	}


	public function getFuncTablename ()	{
		return $this->functablename;
	}


	private function setFuncTablename ($tablename)	{
		$this->functablename = $tablename;
	}


	public function getTablename ()	{
		return $this->tablename;
	}


	private function setTablename ($tablename)	{
		$this->tablename = $tablename;
	}


	public function getLangName () {
		$tableObj = $this->getTableObj();
		return $tableObj->getLangName();
	}


	public function getCode ()	{
		return $this->theCode;
	}


	public function setCode ($theCode)	{
		$this->theCode = $theCode;
	}


	public function getOrderBy ()	{
		return $this->orderBy;
	}

	/* initalisation for code dependant configuration */
	public function initCodeConf ($theCode, $tableConf)	{

		if ($theCode != $this->getCode())	{
			$this->setCode($theCode);
			if ($this->orderBy != $tableConf['orderBy'])	{
				$this->orderBy = $tableConf['orderBy'];
				$this->dataArray = array();
			}

			$requiredFields = $this->getRequiredFields($theCode);
			$requiredFieldArray = GeneralUtility::trimExplode(',', $requiredFields);
			$this->getTableObj()->setRequiredFieldArray($requiredFieldArray);

			if (is_array($tableConf['language.']) &&
				$tableConf['language.']['type'] == 'field' &&
				is_array($tableConf['language.']['field.'])
				)	{
				$addRequiredFields = array();
				$addRequiredFields = $tableConf['language.']['field.'];
				$this->getTableObj()->addRequiredFieldArray($addRequiredFields);
			}
			$tableObj = $this->getTableObj();
			if ($this->bUseLanguageTable($tableConf))	{
				$tableObj->setLanguage($this->config['LLkey']);
				$tableObj->setLangName($tableConf['language.']['table']);
				$tableObj->setTCAFieldArray($tableObj->getLangName(), $tableObj->getAlias().'lang', false);
			}
			if ($tableConf['language.'] && $tableConf['language.']['type'] == 'csv')	{
				$tableObj->initLanguageFile($tableConf['language.']['file']);
			}

			if ($tableConf['language.'] && is_array($tableConf['language.']['marker.']))	{
				$tableObj->initMarkerFile($tableConf['language.']['marker.']['file']);
			}
		}
	}

	public function translateByFields (&$dataArray, $theCode) {

		$langFieldArray = $this->getLanguageFieldArray($theCode);

		if (is_array($dataArray) && is_array($langFieldArray) && count($langFieldArray)) {
			foreach ($dataArray as $uid => $row) {
				foreach ($row as $field => $value) {
					$realField = $langFieldArray[$field];

					if (isset($realField) && $realField != $field) {
						$newValue = $dataArray[$uid][$realField];
						if ($newValue != '') {
							$dataArray[$uid][$field] = $newValue;
						}
					}
				}
			}
		}
	}

	public function bUseLanguageTable ($tableConf) 	{
        $rc = false;
        $api =
            GeneralUtility::makeInstance(\JambageCom\Div2007\Api\Frontend::class);
        $sys_language_uid = $api->getLanguageId();

        if (is_numeric($sys_language_uid)) {
            if (
                is_array($tableConf['language.']) &&
                $tableConf['language.']['type'] == 'table' &&
                $sys_language_uid > 0
            ) {
                $result = true;
            }
        }
        return $result;
    }


	public function fixTableConf (&$tableConf)	{
		// nothing. Override this for your table if needed
	}


	public function getTableConf ($theCode='')	{
		if ($theCode=='' && $this->getCode()!='')	{
			$rc = $this->tableConf;
		} else {
			$cnf =GeneralUtility::makeInstance('tx_ttproducts_config');
			$rc = &$cnf->getTableConf($this->getFuncTablename(), $theCode);
		}
		$this->fixTableConf($rc);
		return $rc;
	}


	public function setTableConf ($tableConf)	{
		$this->tableConf = $tableConf;
	}


	public function getTableDesc ()	{
		return $this->tableDesc;
	}


	public function setTableDesc ($tableDesc)	{
		$this->tableDesc = tableDesc;
	}


	public function getKeyFieldArray ($theCode='')	{
		$tableConf = $this->getTableConf($theCod);
		$rc = array();
		if (isset($tableConf['keyfield.']) && is_array($tableConf['keyfield.']))	{
			$rc = $tableConf['keyfield.'];
		}
		return $rc;
	}


	public function getRequiredFields ($theCode='')	{

		$tableObj = $this->getTableObj();
		$tablename = $this->getTablename();
		$tableConf = $this->getTableConf($theCode);
		$fields = '';
		if (isset($tableConf['requiredFields']))	{
			$fields = $tableConf['requiredFields'];
		} else {
			$fields = 'uid,pid';
		}

		$fieldArray = GeneralUtility::trimExplode(',', $fields);
		$requiredFieldArray = array();
		$defaultFieldArray = $tableObj->getDefaultFieldArray();
		$noTcaFieldArray = $tableObj->getNoTcaFieldArray();

		if (is_array($fieldArray)) {
			foreach ($fieldArray as $field) {
				if (
					in_array($field, $defaultFieldArray) ||
					in_array($field, $noTcaFieldArray) ||
					isset($GLOBALS['TCA'][$tablename]['columns'][$field]) &&
					is_array($GLOBALS['TCA'][$tablename]['columns'][$field])
				) {
					$requiredFieldArray[] = $field;
				}
			}
		}

		$result = implode(',', $requiredFieldArray);
		return $result;
	}


	public function getLanguageFieldArray ($theCode='')	{

		$tableConf = $this->getTableConf($theCode);
		if (is_array($tableConf['language.']) &&
			$tableConf['language.']['type'] == 'field' &&
			is_array($tableConf['language.']['field.'])
		)	{
			$rc = $tableConf['language.']['field.'];
		} else {
			$rc = array();
		}
		return $rc;
	}


	public function getTableObj ()	{
		return $this->tableObj;
	}


	public function reset ()	{
		$this->insertRowArray = array();
		$this->setInsertKey(0);
	}


	public function setInsertKey ($k)	{
		$this->insertKey = $k;
	}


	public function getInsertKey ()	{
		return $this->insertKey;
	}


	public function addInsertRow ($row, &$k='')	{
		$bUseInsertKey = false;

		if ($k == '')	{
			$k = $this->getInsertKey();
			$bUseInsertKey = true;
		}
		$this->insertRowArray[$k++] = $row;
		if ($bUseInsertKey)	{
			$this->setInsertKey($k);
		}
	}


	public function getInsertRowArray ()	{
		return $this->insertRowArray;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_table_base.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_table_base.php']);
}


