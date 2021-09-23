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
 * basket price calculation functions using the price tables
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

use JambageCom\Div2007\Utility\TableUtility;


class tx_ttproducts_graduated_price {
	protected $bHasBeenInitialised = false;
	public $mmArray = array();
	public $dataArray = array(); // array of read in products
	public $functablename = 'tt_products_graduated_price';
	public $mm_table = ''; // mm table
	protected $parentObject = false;
	protected $foreignConfig = array();



	public function setParent ($parentObject) {
		$this->parentObject = $parentObject;
	}


	public function getParent () {
		return $this->parentObject;
	}


	public function getTablename () {
		return $this->functablename;
	}


	public function getMMTablename () {
		return $this->mm_table;
	}

	public function needsInit () {
		return !$this->bHasBeenInitialised;
	}

	public function destruct () {
		$this->bHasBeenInitialised = false;
	}

	/**
	 * Getting the price formulas for graduated prices
	 */
	public function init ($parentObject, $fieldname) {
		$result = false;

		$this->setParent($parentObject);

		$foreignConfig =
			\JambageCom\Div2007\Utility\TableUtility::getForeignTableInfo(
				$parentObject->getTablename(),
				$fieldname
			);

		if ($foreignConfig) {
			$this->foreignConfig = $foreignConfig;
			$this->functablename = $foreignConfig['foreign_table'];
			$this->mm_table = $foreignConfig['mmtable'];
			$result = true;
		}

		$this->bHasBeenInitialised = $result;

		return $result;
	} // init



	public function hasDiscountPrice ($row) {
		$result = false;

		if ($row['graduated_price_uid'] && $row['graduated_price_enable']) {
			$result = true;
		}
		return $result;
	}


	public function getFormulasByItem ($uid = 0, $where_clause = '') {
		if ($this->needsInit()) {
			return false;
		}

		if (
			$uid &&
			!is_array($uid) &&
			isset($this->mmArray[$uid]) &&
			is_array($this->mmArray[$uid])
		) {
			$result = array();
			foreach ($this->mmArray[$uid] as $v) {
				$result[] = $this->dataArray[$v];
			}
		}

		if (!$result) {

			$foreignConfig = $this->foreignConfig;

			$tablename = $this->getTablename();
			$where = '1=1 ' . TableUtility::enableFields($tablename);
			$mmWhere = TableUtility::enableFields($this->getMMTablename());
			if ($uid) {
				$uidWhere = $foreignConfig['mmtable'] . '.' . $foreignConfig['local_field'] . ' ';
				if (is_array($uid)) {
					foreach ($uid as $v) {
						if (
							!\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($v)
						) {
							return 'ERROR: not integer ' . $v;
						}
					}
					$uidWhere .= 'IN (' . implode(',', $uid) . ')';
				} else {
					$uidWhere .= '=' . intval($uid);
				}
				$where .= ' AND ' . $uidWhere;
			}
			if ($where_clause) {
				$where .= ' ' . $where_clause;
			}
			if ($mmWhere) {
				$where .= ' ' . $mmWhere;
			}

			// SELECT *
			// FROM tt_products_graduated_price
			// INNER JOIN tt_products_mm_graduated_price ON tt_products_graduated_price.uid = tt_products_mm_graduated_price.graduated_price_uid

			$from = $tablename . ' INNER JOIN ' . $foreignConfig['mmtable'] . ' ON ' . $tablename . '.uid=' . $foreignConfig['mmtable'] . '.' .  $foreignConfig['foreign_field'];

			// Fetching the products
			$res =
				$GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'*',
					$from,
					$where,
					$groupBy,
					$orderBy,
					$limit
				);

			$result = array();
			$newDataArray = array();

			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$result[] = $this->dataArray[$row['uid']] = $newDataArray[$row['uid']] = $row;
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);

			if (is_array($uid)) {
				$res =
					$GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'*',
						$foreignConfig['mmtable'],
						$uidWhere,
						'',
						'',
						$limit
					);
				unset($this->mmArray[$row['product_uid']]);
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					$this->mmArray[$row['product_uid']][] = $row['graduated_price_uid'];
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
			} else {
				unset($this->mmArray[$uid]);
				foreach ($newDataArray as $k => $v) {
					$this->mmArray[$uid][] = $k;
				}
			}
		}

		return $result;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_graduated_price.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_graduated_price.php']);
}

