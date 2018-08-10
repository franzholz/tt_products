<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2008 Franz Holzinger (franz@ttproducts.de)
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
 * functions for the static_info_countries table
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */



class tx_ttproducts_country extends tx_ttproducts_table_base {
	var $dataArray; // array of read in contents
	var $table;	// object of the type tx_table_db
	public $marker = 'STATICCOUNTRIES';

//	var $defaultFieldArray = array('uid'=>'uid', 'pid'=>'pid'); // TYPO3 default fields

	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	function init ($cObj, $functablename)	{
		parent::init($cObj, $functablename);
		$tablename = $this->getTablename();
		$cnf = t3lib_div::makeInstance('tx_ttproducts_config');
		$this->tableconf = $cnf->getTableConf('static_countries');
		$this->getTableObj()->setDefaultFieldArray(array('uid'=>'uid', 'pid'=>'pid'));
		$this->getTableObj()->setTCAFieldArray('static_countries');

		$requiredFields = 'uid,pid';
		if ($this->tableconf['requiredFields'])	{
			$tmp = $this->tableconf['requiredFields'];
			$requiredFields = ($tmp ? $tmp : $requiredFields);
		}
		$requiredListArray = t3lib_div::trimExplode(',', $requiredFields);
		$this->getTableObj()->setRequiredFieldArray($requiredListArray);

		if (is_array($this->tableconf['generatePath.']) &&
			$this->tableconf['generatePath.']['type'] == 'tablefields' &&
			is_array($this->tableconf['generatePath.']['field.'])
			)	{
			$addRequiredFields = array();
			foreach ($this->tableconf['generatePath.']['field.'] as $field => $value)	{
				$addRequiredFields[] = $field;
			}
			$this->getTableObj()->addRequiredFieldArray ($addRequiredFields);
		}
	} // init


	function isoGet ($country_code, $where, $fields='') {

		global $TYPO3_DB, $TCA;

		if (!$fields)	{
			$rc = $this->dataArray[$country_code];
		}
		if (!$rc || $where) {
			if ($country_code)	{
				$whereString = 'cn_iso_3 = '.$TYPO3_DB->fullQuoteStr($country_code, $this->getTableObj()->name);
			} else {
				$whereString = '1=1';
			}
			if ($where)	{
				$whereString .= ' AND '.$where;
			}

			$whereString .= ' '.$this->getTableObj()->enableFields();
			$fields = ($fields ? $fields : '*');
			// Fetching the products

			$res = $this->getTableObj()->exec_SELECTquery($fields, $whereString);
			if ($country_code)	{
				$row = $TYPO3_DB->sql_fetch_assoc($res);
			 	$rc = $row;
			 	if ($row)	{
			 		$this->dataArray[$row['cn_iso_3']] = $row;
			 	}
			} else {
				while ($row = $TYPO3_DB->sql_fetch_assoc($res))	{
					$rc []= $this->dataArray[$row['uid']] = $row;
				}
			}
			$TYPO3_DB->sql_free_result($res);
		}
		return $rc;
	}

}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_country.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_country.php']);
}


?>
