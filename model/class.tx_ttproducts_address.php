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
 * functions for the frontend users addresses
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


use TYPO3\CMS\Core\Utility\GeneralUtility;


class tx_ttproducts_address extends tx_ttproducts_category_base {
	public $dataArray = array(); // array of read in categories
	public $pibase; // reference to object of pibase
	public $conf;
	public $config;
	public $piVar = 'a';
	public $marker = 'ADDRESS';

	public $tableObj;	// object of the type tx_table_db

	/**
	 * Getting all address values into internal array
	 */
	public function init($cObj, $functablename)	{
		parent::init($cObj, $functablename);
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');

		$tableconf = $cnf->getTableConf('address');
		$tabledesc = $cnf->getTableDesc('address');

		$tableObj = $this->getTableObj();
		$tablename = $this->getTablename();

		$tableObj->setConfig($tableconf);
		$defaultFieldArray = $this->getDefaultFieldArray();
		$tableObj->setDefaultFieldArray($defaultFieldArray);
		$tableObj->setNewFieldArray();
		$requiredFields = 'uid,pid,title';
		$tableconf = $cnf->getTableConf($functablename);
		if ($tableconf['requiredFields'])	{
			$tmp = $tableconf['requiredFields'];
			$requiredFields = ($tmp ? $tmp : $requiredFields);
		}

		$requiredListArray = GeneralUtility::trimExplode(',', $requiredFields);
		$tableObj->setRequiredFieldArray($requiredListArray);
		$tableObj->setTCAFieldArray($tablename);

		if (isset($tabledesc) && is_array($tabledesc))	{
			$this->fieldArray = array_merge($this->fieldArray, $tabledesc);
		}
	} // init


	public function getRootCat()	{
		$rc = $this->conf['rootAddressID'];
		return $rc;
	}


	public function getRelationArray ($dataArray, $excludeCats = '', $rootUids = '', $allowedCats = '') {
		$relationArray = array();
		$rootArray = GeneralUtility::trimExplode(',', $rootUids);

		if (is_array($dataArray))	{
			foreach ($dataArray as $k => $row)	{
				$uid = $row['uid'];
				foreach ($row as $field => $value) {
					$relationArray[$uid][$field] = $value;
				}

				$title = $row[$this->getField('name')];
				$relationArray[$uid]['title'] = $title;
				$relationArray[$uid]['pid'] = $row['pid'];
				$relationArray[$uid]['parent_category'] = '';
			}
		}

		return $relationArray;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_address.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_address.php']);
}



