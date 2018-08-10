<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2011 Franz Holzinger (franz@ttproducts.de)
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
 * functions for the category
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


class tx_ttproducts_category extends tx_ttproducts_category_base {
	var $tt_products_email;	// object of the type tx_table_db
	var $tableconf;
	protected $tableAlias = 'cat';

	/**
	 * initialization with table object and language table
	 */
	function init ($cObj, $functablename)	{
		global $TYPO3_DB;
		$tablename = ($tablename ? $tablename : $functablename);

		parent::init($pibase, $functablename);

		$cnf = t3lib_div::makeInstance('tx_ttproducts_config');
		$this->tableconf = $cnf->getTableConf($functablename);
		$tableObj = $this->getTableObj();
		$tableObj->addDefaultFieldArray(array('sorting' => 'sorting'));
		$tablename = $this->getTablename();
		$tableObj->setTCAFieldArray($tablename);

		if (t3lib_extMgm::isLoaded('mbi_products_categories')) {

			$extensionInfo = tx_div2007_alpha5::getExtensionInfo_fh003('mbi_products_categories');

			if (version_compare($extensionInfo['version'], '0.5.0', '>=')) {

				$tableDesc = $cnf->getTableDesc($functablename);
				$tablesObj = t3lib_div::makeInstance('tx_ttproducts_tables');
				$functablenameArray = t3lib_div::trimExplode(',',$tableDesc['leafFuncTables']);
				$prodfunctablename = $functablenameArray[0];
				if (!$prodfunctablename)	{
					$prodfunctablename = 'tt_products';
				}
				$prodOb = $tablesObj->get($prodfunctablename, FALSE);
				$prodTableDesc = $cnf->getTableDesc($prodfunctablename);
				$prodtablename = $prodOb->getTablename();
				$categoryField = ($prodTableDesc['category'] ? $prodTableDesc['category'] : 'category');
				$rcArray = tx_div2007_alpha5::getForeignTableInfo_fh003($prodtablename,$categoryField);
				$this->setMMTablename($rcArray['mmtable']);
			}
		}

		if ($functablename == 'tt_products_cat')	{
			$parentField = 'parent_category';
		} else if ($functablename == 'tx_dam_cat')	{
			$parentField = 'parent_id';
		}

		if (t3lib_extMgm::isLoaded('mbi_products_categories')) {
			$this->parentField = $parentField;
			if ($functablename == 'tt_products_cat')	{
				$this->referenceField = 'reference_category';
			}
		}
		if ($this->bUseLanguageTable($this->tableconf) && ($functablename == 'tt_products_cat'))	{
			$this->getTableObj()->setLanguage ($this->config['LLkey']);
			$langTable = 'tt_products_cat_language'; // TODO: DAM alternative language
			$tableObj->setLangName($langTable);
			$tableObj->setTCAFieldArray($this->getTableObj()->langname);
		}

		if ($this->tableconf['language.'] && $this->tableconf['language.']['type'] == 'csv')	{
			$tableObj->initLanguageFile($this->tableconf['language.']['file']);
		}

		if ($this->tableconf['language.'] && is_array($this->tableconf['language.']['marker.']))	{
			$tableObj->initMarkerFile($this->tableconf['language.']['marker.']['file']);
		}
	} // init


	function getRootCat ()	{
		$functablename = $this->getFuncTablename ();
		if ($functablename == 'tt_products_cat')	{
			$rc = $this->conf['rootCategoryID'];
		} else if ($functablename == 'tx_dam_cat')	{
			$rc = $this->conf['rootDAMCategoryID'];
		}

		if ($rc == '') {
			$rc = '0';
		}
		return $rc;
	}

	public function getAllChildCats ($pid, $orderBy, $category = 0) {
		$rowArray = array();

		if ($this->parentField != '') {
			$where = $this->parentField . '=' . intval($category);
			$rowArray = $this->get('', $pid, FALSE, $where, '', $orderBy, '', 'uid');
		}

		$resultArray = array();
		$result = '';
		if (isset($rowArray) && is_array($rowArray)) {
			foreach($rowArray as $row) {
				$resultArray[] = $row['uid'];
			}
			$result = implode (',', $resultArray);
		}

		return $result;
	}

	public function getRootline ($rootArray, $uid, $pid) {
		global $TYPO3_DB;

		$bRootfound = FALSE;
		$rc = array();
		$row = array();

		if ($uid) {
			$tableObj = $this->getTableObj();
			$rc = $rowArray = $this->get($uid . ' ', $pid, FALSE);
			$orderBy = $this->tableconf['orderBy'];
			$uidArray = t3lib_div::trimExplode(',', $uid);

			foreach ($uidArray as $actUid) {
				if (!in_array($actUid, $rootArray)) {
					$iCount = 0;
					$row = $rowArray[$actUid];

					while (is_array($row) && ($parent = $row[$this->parentField]) && ($iCount < 100)) {
						$where = 'uid = ' . $parent;
						$where .= ($pid ? ' AND pid IN (' . $pid . ')' : '');
						$where .= $tableObj->enableFields();

						$res = $tableObj->exec_SELECTquery('*', $where, '', $TYPO3_DB->stripOrderBy($orderBy));
						$row = $TYPO3_DB->sql_fetch_assoc($res);
						$TYPO3_DB->sql_free_result($res);
						if ($row) {
							$rc[$parent] = $row;
						}

						if (in_array($parent, $rootArray)) {
							$bRootfound = TRUE;
							break;
						}
						$iCount++;
					}
					if (!$parent || in_array($parent, $rootArray)) {
						$bRootfound = TRUE;
						break;
					}
				} else {
					$bRootfound = TRUE;
				}

				if ($bRootfound) {
					break;
				}
			}
		}

		if (!$bRootfound || !is_array($rc)) {
			$rc = array();
		}
		return $rc;
	}

	public function getRelated (
		$rootUids,
		$currentCat,
		$pid = 0,
		$orderBy = ''
	) {
		global $TYPO3_DB;

		$bUseReference = FALSE;
		$relatedArray = array();
		$uidArray = $rootArray = t3lib_div::trimExplode(',', $rootUids);
		$tableObj = $this->getTableObj();
		$rootLine = $this->getRootline($uidArray, $currentCat, $pid);

		foreach ($rootLine as $k => $row) {
			if (!in_array($k, $uidArray)) {
				$uidArray[] = $k;
			}
		}

		$labelFieldname = 'title';
		foreach ($uidArray as $uid) {
			if (
				tx_div2007_core::testInt($uid)
			) {  // neu
				$row = $this->get($uid, $pid, in_array($uid, $rootArray), '', '', $orderBy);
				if (
					$this->referenceField != '' &&
					$row[$this->referenceField]
				) {
					$bUseReference = TRUE;
				}

				$relatedArray[$uid] = $row;

				if (isset($rootLine[$uid]) || $uid == 0) {
					if ($this->parentField) {
						$where = $this->parentField . '=' . intval($uid);
					} else {
						$where = '1=1';
					}
					$where .= $tableObj->enableFields();

					$res = $tableObj->exec_SELECTquery(
						'*',
						$where,
						'',
						$TYPO3_DB->stripOrderBy($orderBy)
					);

					while($row = $TYPO3_DB->sql_fetch_assoc($res)) {

						if (
							is_array($tableObj->langArray) &&
							$tableObj->langArray[$row[$labelFieldname]]
						) {
							$row[$labelFieldname] = $tableObj->langArray[$row[$labelFieldname]];
						}
						$rc = $this->dataArray[$row['uid']] = $row;
						$relatedArray[$row['uid']] = $row;

						if (
							$this->referenceField != '' &&
							$row[$this->referenceField]
						) {
							$bUseReference = TRUE;
						}
					}
					$TYPO3_DB->sql_free_result($res);
				}
			}
		}
		foreach ($rootLine as $k => $row)	{
			$relatedArray[$k] = $row;
		}

		if ($bUseReference && count($relatedArray)) { // remove copies of the referenced category
			$finalUidKeyArray = array();
			$fixedRelatedArray = array();
			foreach ($relatedArray as $uid => $row) {
				if ($row[$this->referenceField]) {
					$uid = $row[$this->referenceField];
					$row['uid'] = $uid;
					unset($row[$this->referenceField]);
				}
				$finalUidKeyArray[$uid] = 1;
				if (!isset($fixedRelatedArray[$uid])) {
					$fixedRelatedArray[$uid] = $row;
				}
			}
			$relatedArray = $fixedRelatedArray;
		}

		return $relatedArray;
	}

	function getRowFromTitle ($title)	{
		$rc = $this->titleArray[$title];
		if (is_array($rc))	{
			$tableObj = $this->getTableObj();

			$where = '1=1 '.$tableObj->enableFields();
			$where .= ' AND title='.$TYPO3_DB->fullQuoteStr($title,$tableObj->name);
			$res = $tableObj->exec_SELECTquery('*',$where);
			$row = $TYPO3_DB->sql_fetch_assoc($res);
			$TYPO3_DB->sql_free_result($res);
			$rc = $this->titleArray[$title] = $row;
		}
		return $rc;
	}

	public function getParent (
		$uid = '0',
		$pid = 0,
		$bStore = TRUE,
		$where_clause = '',
		$groupBy = '',
		$orderBy = '',
		$limit = '',
		$fields = '',
		$bCount = FALSE,
		$aliasPostfix = ''
	) {
		$result = FALSE;

		$row = $this->get(
			$uid,
			$pid,
			$bStore,
			$where_clause,
			$groupBy,
			$orderBy,
			$limit,
			$fields,
			$bCount,
			$aliasPostfix
		);
		if (
			isset($row) &&
			is_array($row) &&
			isset($row[$this->parentField])
		) {
			$result =
				$this->get(
					$row[$this->parentField],
					$pid,
					$bStore,
					$where_clause,
					$groupBy,
					$orderBy,
					$limit,
					$fields,
					$bCount,
					$aliasPostfix
				);
		}
		return $result;
	}

	function getRowCategory ($row) {
		$rc = $row['category'];
		return $rc;
	}

	function getRowPid ($row) {
		$rc = $row['pid'];
		return $rc;
	}

	function getParamDefault ($theCode, $cat)	{

		if (!$cat)	{
			$cnf = t3lib_div::makeInstance('tx_ttproducts_config');

			if ($this->getFuncTablename() == 'tt_products_cat')	{
				$cat = $cnf->conf['defaultCategoryID'];
				$catConfig = $cnf->config['defaultCategoryID'];

				if (strlen($catConfig)) {
					if (strlen($cat)) {
						$cat .= ',' . $catConfig;
					} else {
						$cat = $catConfig;
					}
				}
			}

			if ($this->getFuncTablename() == 'tx_dam_cat')	{
				$cat = $cnf->conf['defaultDAMCategoryID'];
			}
		}

		if ($cat)	{
			$tableConf = $this->getTableConf($theCode);
			$catArray = t3lib_div::intExplode(',', $cat);
			$catArray = array_unique($catArray);

			if (
				is_array($tableConf['special.']) &&
				(
					tx_div2007_core::testInt($tableConf['special.']['all']) &&
					in_array($tableConf['special.']['all'], $catArray) ||
					$tableConf['special.']['all'] == 'all'
				)
			) 	{
				$cat = '';	// no filter shall be used
			} else if (
				is_array($tableConf['special.']) &&
				tx_div2007_core::testInt($tableConf['special.']['no']) &&
				in_array($tableConf['special.']['no'], $catArray)
			) {
				$cat = '0';	// no products shall be shown
			} else {
				$cat = implode(',',$catArray);
			}
		}

		return $cat;
	}

	function getChildUidArray ($uid)	{
		$rcArray = array();
		return $rcArray;
	}

	/**
	 * Getting all sub categories from internal array
	 * This must be overwritten by other classes who support multiple categories
	 * getPrepareCategories must have been called before
	 *
	 */
	function &getSubcategories (&$row)	{
		return array();
	}

	public function getRelationArray ($dataArray, $excludeCats='',$rootUids='',$allowedCats='') {

		$relationArray = array();
		$rootArray = t3lib_div::trimExplode(',', $rootUids);
		$catArray = t3lib_div::trimExplode(',', $allowedCats);
		$excludeArray = t3lib_div::trimExplode (',', $excludeCats);
		foreach ($excludeArray as $cat)	{
			$excludeKey = array_search($cat, $catArray);
			if ($excludeKey !== FALSE)	{
				unset($catArray[$excludeKey]);
			}
		}

		if (is_array($dataArray))	{
			foreach ($dataArray as $row)	{	// separate loop to keep the sorting order
				$relationArray[$row['uid']] = array();
			}

			foreach ($dataArray as $row)	{
				$uid = $row['uid'];
				if (
					(!$uid) ||
					($allowedCats && !in_array($uid, $catArray)) ||
					($excludeCats && in_array($uid, $excludeArray))
				)	{
					continue;
				}
				foreach ($row as $field => $value)	{
					$relationArray[$uid][$field] = $value;
				}

				$parent = $row[$this->parentField];

				if(
					(!$parent) ||
					($allowedCats && !in_array($parent, $catArray)) ||
					($excludeCats && in_array($parent, $excludeArray))
				)	{
					$parent = 0;
				}

				$relationArray [$uid]['parent_category'] = $parent;
				$parentId = $row[$this->parentField];

				if ($parentId && isset($dataArray[$parentId]) && !in_array($uid, $rootArray) && !in_array($parentId,$excludeArray))	{
					if (!isset($relationArray[$parentId]['child_category']))	{
						$relationArray[$parentId]['child_category'] = array();
					}
					$relationArray[$parentId]['child_category'][] = (int) $uid;
				}
			}
		}

		return $relationArray;
	}


	// returns the Path of all categories above, separated by '/'
	function getPath ($uid) {
		$rc = '';

		return $rc;
	}

	public function getFirstDiscount ($discount, $bDiscountDisable, $cat, $pid = 0) {

		$result = 0;

		if (!$bDiscountDisable) {
			if ($discount > 0) {
				$result = $discount;
			} else {
				$rootCat = $this->getRootCat();
				$rootArray = t3lib_div::trimExplode(',', $rootCat);
				$rootLine = $this->getRootline($rootArray, $cat, $pid);

				if (is_array($rootLine) && count($rootLine)) {

					foreach ($rootLine as $catFromLine => $catRow) {
						if ($catRow['discount_disable']) {
							$result = 0;
							break;
						}

						if ($catRow['discount'] > 0) {
							$result = $catRow['discount'];
							break;
						}
					}
				}
			}
		}

		return $result;
	}

	public function getMaxDiscount ($discount, $bDiscountDisable, $catArray, $pid = 0) {
		$maxDiscount = 0;

		if (!$bDiscountDisable) {
			$maxDiscount = $discount;
			$rootCat = $this->getRootCat();
			$rootArray = t3lib_div::trimExplode(',', $rootCat);

			foreach ($catArray as $cat) {

				$rootLine = $this->getRootline($rootArray, $cat, $pid);

				if (is_array($rootLine) && count($rootLine)) {

					foreach ($rootLine as $catFromLine => $catRow) {
						if ($maxDiscount < $catRow['discount']) {
							$maxDiscount = $catRow['discount'];
						}
					}
				}
			}
		}

		return $maxDiscount;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_category.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_category.php']);
}


?>
