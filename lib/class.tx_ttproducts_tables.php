<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2018 Franz Holzinger (franz@ttproducts.de)
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
 * table class for creation of database table classes and table view classes
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;

class tx_ttproducts_tables implements \TYPO3\CMS\Core\SingletonInterface {
	protected $tableClassArray = [
		'address' => 'tx_ttproducts_address',
		'fe_users' => 'tx_ttproducts_orderaddress',
		'pages' => 'tx_ttproducts_page',
		'static_banks_de' => 'tx_ttproducts_bank_de',
		'static_countries' => 'tx_ttproducts_country',
		'static_taxes' => 'tx_ttproducts_static_tax',
		'sys_file_reference' => 'tx_ttproducts_fal',
		'sys_products_orders' => 'tx_ttproducts_order',
		'sys_products_accounts' => 'tx_ttproducts_account',
		'sys_products_cards' => 'tx_ttproducts_card',
		'tt_content' => 'tx_ttproducts_content',
		'tt_products' => 'tx_ttproducts_product',
		'tt_products_articles' => 'tx_ttproducts_article',
		'tt_products_cat' => 'tx_ttproducts_category',
		'tt_products_downloads' => 'tx_ttproducts_download',
		'tt_products_emails' => 'tx_ttproducts_email',
		'tt_products_texts' => 'tx_ttproducts_text',
		'tx_dam' => 'tx_ttproducts_dam',
		'tx_dam_cat' => 'tx_ttproducts_damcategory',
		'voucher' => 'tx_ttproducts_voucher',
	];
	protected $needExtensionArray = [
		'static_banks_de' => 'static_info_tables_banks_de',
		'static_countries' => 'static_info_tables',
		'sys_file_reference' => 'filelist',
		'tx_dam' => 'dam',
		'tx_dam_cat' => 'dam'
	];
	protected $usedObjectArray = [];


	public function getTableClassArray () {
		return $this->tableClassArray;
	}

	public function setTableClassArray ($tableClassArray) {
		$this->tableClassArray = $tableClassArray;
	}

	public function getTableClass ($functablename, $bView = false) {

		$rc = '';
		if ($functablename) {
            $neededExtension = '';
            if (isset($this->needExtensionArray[$functablename])) {
                $neededExtension = $this->needExtensionArray[$functablename];
			}
			if (empty($neededExtension) || \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($neededExtension)) {
				$rc = $this->tableClassArray[$functablename] . ($bView ? '_view' : '');
			} else {
				$rc = 'skip';
			}
		}
		return $rc;
	}

	/* set the $bView to true if you want to get the view class */
	public function get ($functablename, $bView = false, $bInit = true) {
		$classNameArray = [];
		$tableObjArray = [];
		$resultInit = true;

		$classNameArray['model'] = $this->getTableClass($functablename, false);

		if ($bView) {
			$classNameArray['view'] = $this->getTableClass($functablename, true);
		}

		if (!$classNameArray['model'] || $bView && !$classNameArray['view']) {
			debug ('Error in '.TT_PRODUCTS_EXT.'. No class found after calling function tx_ttproducts_tables::get with parameters "' . $functablename . '", ' . $bView . ' . ','internal error'); // keep this
			return false;
		}

		foreach ($classNameArray as $k => $className) {
			if ($className != 'skip') {
				if (strpos($className, ':') === false) {
					$path = PATH_BE_TTPRODUCTS;
				} else {
					list($extKey, $className) = GeneralUtility::trimExplode(':', $className, true);

					if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($extKey)) {
						debug ('Error in '.TT_PRODUCTS_EXT.'. No extension "' . $extKey . '" has been activated to use class class.' . $className . '.','internal error');
						continue;
					}
					$path = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($extKey);
				}
				$classRef = 'class.' . $className;
				$classFile = $path . $k . '/' . $classRef . '.php';

				if (file_exists($classFile)) {
					$classRef = $classFile . ':' . $className;
					$tableObj[$k] = GeneralUtility::makeInstance($className);	// fetch and store it as persistent object
					$this->usedObjectArray[$className] = true;
				} else {
					debug ($classFile, 'File not found: ' . $classFile . ' in file class.tx_ttproducts_tables.php'); // keep this
				}
			}
		}

		if (isset($tableObj['model']) && is_object($tableObj['model'])) {
			if ($bInit && $tableObj['model']->needsInit()) {
				$resultInit = $tableObj['model']->init(
					$functablename
				);
			}
		} else {
			if ($classNameArray['model'] == 'skip') {
                if (isset($this->needExtensionArray[$functablename])) {
                    debug ('The extension \'' . $this->needExtensionArray[$functablename] . '\' needed for table \'' . $functablename . '\' has not been installed.', 'internal error in ' . TT_PRODUCTS_EXT); // keep this
                } else {
                    debug ('Table \'' . $functablename . '\' is not configured.', 'internal error in ' . TT_PRODUCTS_EXT); // keep this
                }
			} else {
				debug ('Object for \'' . $functablename . '\' has not been found.', 'internal error in ' . TT_PRODUCTS_EXT); // keep this
			}
		}

		if (
			$resultInit &&
			isset($tableObj['view']) &&
			is_object($tableObj['view']) &&
			isset($tableObj['model']) &&
			is_object($tableObj['model'])
		) {
			if ($bInit && $tableObj['view']->needsInit()) {

				$resultInit = $tableObj['view']->init(
					$tableObj['model']
				);
			}
		}

		$result = false;
		if ($resultInit) {
			$result = ($bView ? $tableObj['view'] : $tableObj['model'] ?? false);
		}
		return $result;
	}

	public function getMM ($functablename) {

		$tableObj = GeneralUtility::makeInstance('tx_ttproducts_mm_table');

		if (isset($tableObj) && is_object($tableObj)) {
			if ($tableObj->needsInit() || $tableObj->getFuncTablename() != $functablename) {
				$tableObj->init(
					$functablename
				);
			}
		} else {
			debug ('Object for \'' . $functablename . '\' has not been found.', 'internal error in ' . TT_PRODUCTS_EXT); // keep this
		}
		return $tableObj;
	}

	/**
	 * Returns informations about the table and foreign table
	 * This is used by various tables.
	 *
	 * @param	string		name of the table
	 * @param	string		field of the table
	 *
	 * @return	array		infos about the table and foreign table:
					table         ... name of the table
					foreign_table ... name of the foreign table
					mmtable       ... name of the mm table
					foreign_field ... name of the field in the mm table which joins with
					                  the foreign table
	 * @access	public
	 *
	 */
	public function getForeignTableInfo ($functablename, $fieldname) {
		$rc = [];
		if ($fieldname != '') {
			$tableObj = $this->get($functablename, false);
			$tablename = $tableObj->getTableName($functablename);
			$rc = \JambageCom\Div2007\Utility\TableUtility::getForeignTableInfo($tablename, $fieldname);
		}
		return $rc;
	}

	public function prepareSQL (
		$foreignTableInfoArray,
		$tableAliasArray,
		$aliasPostfix,
		&$sqlArray
	) {
		if (
            empty($foreignTableInfoArray['mmtable']) && 
            !empty($foreignTableInfoArray['foreign_table'])
        ) {
            $fieldname = $foreignTableInfoArray['table_field'];
            $tablename = $foreignTableInfoArray['table'];
			if (isset($tableAliasArray[$tablename])) {
				$tablealiasname = $tableAliasArray[$tablename];
			} else {
				$tablealiasname = $tablename;
			}

			$foreigntablename = $foreignTableInfoArray['foreign_table'];
			if (isset($tableAliasArray[$foreigntablename])) {
				$foreigntablealiasname = $tableAliasArray[$foreigntablename];
			} else {
				$foreigntablealiasname = $foreigntablename;
			}

			$sqlArray['local'] = $tablename;
			$sqlArray['from'] = $tablename.' '.$tablealiasname.$aliasPostfix.' INNER JOIN '.$foreigntablename.' '.$foreigntablealiasname.$aliasPostfix.' ON '.$tablealiasname.$aliasPostfix.'.'.$fieldname.'='.$foreigntablealiasname.$aliasPostfix.'.uid';
			$sqlArray['where'] = $tablealiasname.'.uid='.$tablealiasname.$aliasPostfix.'.uid';
		}
	}

	public function destruct() {
		foreach ($this->usedObjectArray as $className => $bFreeMemory) {
			if ($bFreeMemory) {
				$object = GeneralUtility::makeInstance($className);
				$object->destruct();
			}
		}
	}
}



