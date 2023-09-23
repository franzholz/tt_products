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
 * functions for the product
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


use TYPO3\CMS\Core\Utility\GeneralUtility;


class tx_ttproducts_article extends tx_ttproducts_article_base {
	public $fields = [];
	public $tt_products; // element of class tx_table_db to get the parent product
	public $marker = 'ARTICLE';
	public $type = 'article';
	public $tableArray;
	protected $tableAlias = 'article';


	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	public function init ($functablename) {
		$result = parent::init($functablename);

		if ($result) {
			$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
			$tableConfig = [];
			$tableConfig['orderBy'] = $cnf->conf['orderBy'] ?? '';

			if (!$tableConfig['orderBy']) {
				$tableConfig['orderBy'] = $this->getOrderBy ();
			}

			$tableObj = $this->getTableObj();
			$tableObj->setConfig($tableConfig);
			$tableObj->addDefaultFieldArray(['sorting' => 'sorting']);
		}

		return $result;
	} // init


	public function &getWhereArray ($prodUid, $where, $orderBy = '') { // Todo: consider the $orderBy
		$rowArray = [];
		$enableWhere = $this->getTableObj()->enableFields();
		$where = ($where ? $where . ' ' . $enableWhere : '1=1 ' . $enableWhere);
		$alias = $this->getAlias();
		$fromJoin = '';

		if (in_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['articleMode'], [1, 2])) {

			$finalWhere = 'tt_products_products_mm_articles.uid_local=' . intval($prodUid) . ' AND tt_products_products_mm_articles.deleted=0 AND tt_products_products_mm_articles.hidden=0' . ($where!='' ? ' AND '.$where : '');
			$mmTable = 'tt_products_products_mm_articles';
			$uidForeignArticle = 'uid_foreign';
			$fromJoin = 'tt_products_articles ' . $alias . ' JOIN ' . $mmTable . ' ON ' . $alias . '.uid=' . $mmTable . '.' . $uidForeignArticle;
			$finalOrderBy = $mmTable . '.sorting DESC';
		} else {
		//	$fromJoin = 'tt_products_articles ' . $alias;
			$finalWhere = $alias . '.uid_product=' . intval($prodUid) . ($where ? ' AND ' . $where : '');
			$finalOrderBy = '';
		}
		$res = $this->getTableObj()->exec_SELECTquery('*',$finalWhere,'',$finalOrderBy,'',$fromJoin);
// 		$variantFieldArray = $this->variant->getFieldArray();

		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$uid = intval($row['uid']);
// 			$this->getTableObj()->substituteMarkerArray($row, $variantFieldArray);
			$this->dataArray[$uid] = $row;	// remember for later queries
			$uidArray[] = $uid;
			$rowArray[] = $row;
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
		return $rowArray;
	}


	public function getProductField (&$row, $field) {
		$rc = '';
		if ($field != 'uid') {
			$rowProducts = $this->tt_products->get($row['uid_product']);
			$rc = $rowProducts[$field];
		} else {
			$rc = $row['uid_product'];
		}
		return $rc;
	}


	public function getProductRow ($row) {
		$result = $this->tt_products->get($row['uid_product']);
		return $result;
	}


	public function getRequiredFieldArray ($theCode = '') {
		$tableConf = $this->getTableConf($theCode);
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$rc = [];
		if (!empty($tableConf['requiredFields'])) {
			$requiredFields = $tableConf['requiredFields'];
		} else {
			$requiredFields = 'uid,pid,category,price,price2,directcost';
		}
		$instockField = $cnf->getTableDesc($functablename,'inStock');
		if ($instockField && !$this->conf['alwaysInStock']) {
			$requiredFields = $requiredFields . ',' . $instockField;
		}

		$rc = $requiredFields;
		return $rc;
	}

	public function usesAddParentProductCount ($row) {
		$cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');

		$result = $cnfObj->hasConfig($row, 'addParentProductCount', 'graduated_config');
		return $result;
	}

}

