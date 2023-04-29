<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2015 Franz Holzinger (franz@ttproducts.de)
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
 * @maintainer	Franz Holzinger <franz@ttproduct.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;



abstract class tx_ttproducts_category_base extends tx_ttproducts_table_base {
	protected $titleArray; // associative array of read in categories with title as index
	protected $mm_table = ''; // only set if a mm table is used
	public $parentField; // field name for parent
	public $referenceField; // field name for reference element
	public $categoryField = 'category';

	public function getFromTitle ($title) {
		$rc = [];
		return $rc;
	}

	public function getParent ($uid=0) {
		$rc = [];
		return $rc;
	}

	public function getRootCat () {
		$rc = 0;
		return $rc;
	}

	public function getRowCategory ($row) {
		$rc = '';
		return $rc;
	}

	public function setMMTablename ($mm_table) {
		$this->mm_table = $mm_table;
	}

	public function getMMTablename () {
		return $this->mm_table;
	}

	public function hasSpecialConf ($cat, $theCode, $type) {
		$rc = false;
		$conf = $this->getTableConf($theCode);

		if (is_array($conf['special.']) && isset($conf['special.'][$type])) {
			$specialArray = GeneralUtility::trimExplode(',', $conf['special.'][$type]);
			if (in_array($cat, $specialArray)) {
				$rc = true;
			}
		}

		return $rc;
	}

	public function getRowPid ($row) {
		$rc = '';
		return $rc;
	}

	public function getParamDefault ($theCode, $piVars) {
		$rc = '';
		return $rc;
	}

	public function getChildUidArray ($uid) {
		$rcArray = [];
		return $rcArray;
	}

	public function getRelated ($rootUids, $currentCat, $pid = 0, $orderBy = '') {
		$rcArray = [];
		return $rcArray;
	}

	public function getCategoryArray ($productRow, $orderBy = '') {
		$catArray = [];
		$uid = 0;
		if (is_array($productRow)) {
			$uid = $productRow['uid'];
		}

		if($this->getMMTablename()) {
			$hookVar = '';
			$functablename = $this->getFuncTablename();
			if ($functablename == 'tt_products_cat') {
				$hookVar = 'prodCategory';
			} else if($functablename == 'tx_dam_cat') {
				$hookVar = 'DAMCategory';
			}
				// Call all addWhere hooks for categories at the end of this method
			if (
				$hookVar &&
				isset ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT][$hookVar]) &&
				is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT][$hookVar])
			) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT][$hookVar] as $classRef) {
					$hookObj= GeneralUtility::makeInstance($classRef);
					if (method_exists($hookObj, 'init')) {
						$hookObj->init($this->parentField);
					}
					if (method_exists($hookObj, 'getCategories')) {
						$retArray = $hookObj->getCategories($this, $uid, $this->mm_table, $orderBy);

						if (isset($retArray) && is_array($retArray)) {
							foreach ($retArray as $k => $row) {
								$catArray[] = $row['cat'];
							}
						}
					}
				}
			}
		} else if ($uid && isset($productRow[$this->categoryField])) {
			$catArray = [$productRow[$this->categoryField]];
		}
		return $catArray;
	}

	public function getDepth ($theCode) {
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$functablename = $this->getFuncTablename();
		$tableconf = $cnf->getTableConf($functablename, $theCode);
		$result = $tableconf['hierarchytiers'];
		if (!isset($result)) {
			$result = 1;
		}
		return $result;
	}

	public function getLineArray ($start, $endArray) {
		$catArray = [];
		$hookVar = '';
		$functablename = $this->getFuncTablename ();
		if ($functablename == 'tt_products_cat') {
			$hookVar = 'prodCategory';
		} else if($functablename == 'tx_dam_cat') {
			$hookVar = 'DAMCategory';
		}

		$tmpArray = [];
			// Call all addWhere hooks for categories at the end of this method
		if ($hookVar && is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT][$hookVar])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT][$hookVar] as $classRef) {
				$hookObj= GeneralUtility::makeInstance($classRef);
				if (method_exists($hookObj, 'init')) {
					$hookObj->init($this->parentField);
				}
				if (method_exists($hookObj, 'getLineCategories')) {
					$catArray =
						$hookObj->getLineCategories(
							$this,
							$start,
							$endArray,
							$this->getTableObj()->enableFields()
						);
				}
			}
		}
		return $catArray;
	}

	public function getHookVar () {
		$funcTablename = $this->getFuncTablename();
		if ($funcTablename == 'tt_products_cat') {
			$rc = 'prodCategory';
		} else if ($funcTablename == 'tx_dam_cat') {
			$rc = 'DAMCategory';
		}
		return $rc;
	}

	public function getChildCategoryArray ($cat) {

		$catArray = [];
		$hookVar = $this->getHookVar();

		$tmpArray = [];
			// Call all addWhere hooks for categories at the end of this method
		if ($hookVar && is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT][$hookVar])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT][$hookVar] as $classRef) {
				$hookObj= GeneralUtility::makeInstance($classRef);
				if (method_exists($hookObj, 'init')) {
					$hookObj->init($this->parentField);
				}
				if (method_exists($hookObj, 'getChildCategories')) {
					$tmpArray = $hookObj->getChildCategories($this, $cat);
				}
			}
		}
		if (is_array($tmpArray)) {
			foreach ($tmpArray as $k => $row) {
				$catArray[] = $row['cat'];
			}
		}

		return $catArray;
	}

	public function getRootArray ($rootCat, $categoryArray, $autoRoot = true) {
		$rootArray = [];
		$rootCatArray = GeneralUtility::trimExplode(',', $rootCat);
		foreach ($categoryArray as $uid => $row) {
			if (
				(
					MathUtility::canBeInterpretedAsInteger($uid)
				) &&
				(
					in_array($uid, $rootCatArray) ||
					(
					  $autoRoot &&
					  (
					    $this->parentField == '' ||
					    (
						    !$row[$this->parentField] ||
						    !isset($categoryArray[$row[$this->parentField]]) // It is also a root if the parent is outside of the allowed pages
					    )
					  )
					)
				)
			) {
				$rootArray[] = $uid;
			}
		}
		return $rootArray;
	}

	public function getRootpathArray (&$relationArray, $rootCat, $currentCat) {
		$rootpathArray = [];
		$rootCatArray = GeneralUtility::trimExplode(',', $rootCat);
		$uid = $currentCat;
		if (!empty($uid)) {
			$count = 0;
			do {
				$count++;
				$row = $relationArray[$uid] ?? [];
				if ($row) {
					$rootpathArray[] = $row;
					$lastUid = $uid;
					$uid = $row['parent_category'] ?? '';
				}
			} while (
				$row &&
				!in_array($lastUid,$rootCatArray) &&
				!empty($uid) &&
				$count < 199
			);
		}
		return $rootpathArray;
	}

	public function getRelationArray (
		$dataArray,
		$excludeCats = '',
		$rootUids = '',
		$allowedCats = ''
	) {
		$relationArray = [];
		return $relationArray;
	}
}

