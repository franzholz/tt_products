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
 * functions for the category
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

 
use TYPO3\CMS\Core\Utility\GeneralUtility;


class tx_ttproducts_email extends tx_ttproducts_table_base {
	public $emailArray;	// array of read in emails
	var $table;		 // object of the type tx_table_db

	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	function init($functablename)  {
		$result = parent::init($functablename);

		if ($result) {
			$tablename = $this->getTablename();
			$this->getTableObj()->addDefaultFieldArray(array('sorting' => 'sorting'));
			$this->getTableObj()->setTCAFieldArray('tt_products_emails');
		}

		return $result;
	} // init


	function getEmail ($uid) {
		$rc = $this->emailArray[$uid] ?? '';
		if ($uid && !$rc) {
			$sql = GeneralUtility::makeInstance('tx_table_db_access');
			$sql->prepareFields($this->getTableObj(), 'select', '*');
			$sql->prepareWhereFields ($this->getTableObj(), 'uid', '=', intval($uid));
			$sql->prepareEnableFields ($this->getTableObj());
			//$this->getTableObj()->enableFields();
			// Fetching the email
			$res = $sql->exec_SELECTquery();
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			$rc = $this->emailArray[$row['uid']] = $row;
		}
		return $rc;
	}
}

