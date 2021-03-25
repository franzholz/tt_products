<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2008 Franz Holzinger (franz@ttproducts.de)
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

use TYPO3\CMS\Core\Utility\GeneralUtility;


class tx_ttproducts_graduated_price implements \TYPO3\CMS\Core\SingletonInterface {
	public $mmArray = array();
	public $dataArray = array(); // array of read in products
	public $tableObj;	// object of the type tx_table_db
	public $conf;
	public $config;
	public $tableconf;
	public $tabledesc;
	public $functablename = 'tt_products_graduated_price';
	public $mm_table = ''; // mm table

	/**
	 * Getting the price formulas for graduated prices
	 */
	public function init ($tablename, $mmtablename)  {
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$this->conf = &$cnf->conf;
		$this->config = &$cnf->config;

		if ($tablename=='')	{
			$tablename = 'tt_products_graduated_price';
			$mmtablename = 'tt_products_mm_graduated_price';
		}
		$this->functablename = $tablename;
		$this->mm_table = $mmtablename;
		$this->tableconf = $cnf->getTableConf($tablename);
		$this->tabledesc = $cnf->getTableDesc($tablename);

		$this->tableObj = GeneralUtility::makeInstance('tx_table_db');
		$this->tableObj->setTCAFieldArray($tablename, 'tt_products_graduated_price');
	} // init


	public function getFormulasByProduct ($uid=0,$where_clause='') {
		if ($uid && !is_array($uid) && isset($this->mmArray[$uid]) && is_array($this->mmArray[$uid]))	{
			$rc = array();
			foreach ($this->mmArray[$uid] as $v)	{
				$rc[] = $this->dataArray[$v];
			}
		}
		if (!$rc) {
			$where = '1=1 '.$this->tableObj->enableFields();
			$mmWhere = $this->tableObj->enableFields('',-1,array(),$this->mm_table);
			if ($uid)	{
				$uidWhere = $this->mm_table.'.uid_local ';
				if (is_array($uid))	{
					foreach ($uid as $v)	{
						if (
							!tx_div2007_core::testInt($v)
						) {
							return 'ERROR: not integer '.$v;
						}
					}
					$uidWhere .= 'IN ('.implode(',',$uid).')';
				} else {
					$uidWhere .= '='.intval($uid);
				}
				$where .= ' AND '.$uidWhere;
			}
			if ($where_clause)	{
				$where .= ' '.$where_clause;
			}
			if ($mmWhere)	{
				$where .= ' '.$mmWhere;
			}

			// SELECT *
			// FROM tt_products_graduated_price
			// INNER JOIN tt_products_mm_graduated_price ON tt_products_graduated_price.uid = tt_products_mm_graduated_price.uid_foreign

			$from = $this->tableObj->name.' INNER JOIN '.$this->mm_table.' ON '.$this->tableObj->name.'.uid='.$this->mm_table.'.uid_foreign';

			// Fetching the products
			$res = $this->tableObj->exec_SELECTquery('*', $where, $groupBy, $orderBy, $limit, $from);
			$rc = array();
			$newDataArray = array();

			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
				$rc[] = $this->dataArray[$row['uid']] = $newDataArray[$row['uid']] = $row;
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);

			if (is_array($uid))	{
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $this->mm_table, $uidWhere, '', '', $limit);
				unset($this->mmArray[$row['uid_local']]);
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
					$this->mmArray[$row['uid_local']][] = $row['uid_foreign'];
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
			} else {
				unset($this->mmArray[$uid]);
				foreach ($newDataArray as $k => $v)	{
					$this->mmArray[$uid][] = $k;
				}
			}
		}
		return $rc;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_graduated_price.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_graduated_price.php']);
}


