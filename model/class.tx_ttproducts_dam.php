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
 * functions for the DAM images
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage tt_products
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;

class tx_ttproducts_dam extends tx_ttproducts_article_base
{
    public $dataArray; // array of read in categories
    public $tableArray;

    public $marker = 'DAM';
    public $type = 'dam';
    public $piVar = 'dam';

    public $mm_table = 'tx_dam_mm_cat';
    public $image;
    public $variant; // object for the product variant attributes, must initialized in the init function

    /**
     * DAM elements.
     */
    public function init($functablename)
    {
        $result = parent::init($functablename);

        if ($result) {
            $this->tableArray = $tableArray;
            $tableObj = $this->getTableObj();
            $tableObj->addDefaultFieldArray(['sorting' => 'sorting']);

            $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
            $tablename = $cnf->getTableName($functablename);
            $tableObj->setTCAFieldArray($tablename, 'dam');
        }

        return $result;
    } // init

    public function getRelated(
        &$parentFuncTablename,
        &$parentRows,
        $multiOrderArray,
        $uid,
        $type,
        $orderBy = ''
    ) {
        $rcArray = [];
        if ($type == 'products') {
            $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
            $productTable = $tablesObj->get('tt_products', false);
            $additional = $productTable->getFlexQuery('isImage', 1);
            $rowArray =
                $productTable->getWhere('additional REGEXP ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($additional, $productTable->getTablename)); // quotemeta
            $rcArray = array_keys($rowArray);
        }

        return $rcArray;
    }

    /**
     * Returns true if the item has the $check value checked.
     */
    public function hasAdditional(&$row, $check)
    {
        $hasAdditional = false;

        return $hasAdditional;
    }

    /**
     * Sets the markers for DAM specific FORM fields.
     */
    public function setFormMarkerArray($uid, &$markerArray): void
    {
        $markerArray['###DAM_FIELD_NAME###'] = 'ttp_basket[dam]';
        $markerArray['###DAM_UID###'] = intval($uid);
    }

    /**
     * fills in the row fields from a DAM record.
     *
     * @param	array		the row
     * @param	string	  variants separated by variantSeparator
     *
     * @access private
     *
     * @see getVariantFromRow
     */
    public function modifyItemRow(&$row, $uid): void
    {
        $damRow = $this->get($uid);

        if ($damRow) {
            // 			$damRow['damdescription'] = $damRow['description'];
            // 			unset($damRow['description']);
            // 			foreach ($damRow as $field => $value)	{
            // 				if (isset($row[$field]) && !$row[$field])	{
            // 					$row[$field] = $value;
            // 				}
            // 			}
            if ($damRow['file_mime_type'] == 'image' && !$row['image']) {
                $row['image'] = $damRow['file_name'];
                $row['file_mime_type'] = 'image';
                $row['file_path'] = $damRow['file_path'];
            }
        }
    }

    public function addWhereCat(
        $catObject,
        $theCode,
        $cat,
        $categoryAnd,
        $pid_list,
        $bLeadingOperator = true
    ) {
        $bOpenBracket = false;
        $where = '';

        // Call all addWhereCat hooks for DAM categories at the end of this method
        if (
            isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['DAMCategory']) &&
            is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['DAMCategory'])
        ) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['DAMCategory'] as $classRef) {
                $hookObj = GeneralUtility::makeInstance($classRef);
                if (method_exists($hookObj, 'addWhereCat')) {
                    $whereNew =
                        $hookObj->addWhereCat(
                            $this,
                            $catObject,
                            $cat,
                            $where,
                            $operator,
                            $pid_list,
                            $catObject->getDepth($theCode),
                            $categoryAnd
                        );
                    if ($bLeadingOperator) {
                        $operator = ($operator ?: 'OR');
                        $where .= ($whereNew ? ' ' . $operator . ' ' . $whereNew : '');
                    } else {
                        $where .= $whereNew;
                    }
                }
            }
        } elseif ($cat || $cat == '0') {
            $cat = implode(',', GeneralUtility::intExplode(',', $cat));
            $where = 'category IN (' . $cat . ')';
            if ($bLeadingOperator) {
                $where = ' AND ( ' . $where . ')';
            }
        }

        return $where;
    }

    public function addConfCat($catObject, &$selectConf, $aliasArray): string
    {
        $tableNameArray = [];

        // Call all addWhere hooks for DAM categories at the end of this method
        if (
            isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['DAMCategory']) &&
            is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['DAMCategory'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['DAMCategory'] as $classRef) {
                $hookObj = GeneralUtility::makeInstance($classRef);
                if (method_exists($hookObj, 'addConfCatProduct')) {
                    $newTablenames = $hookObj->addConfCatProduct($this, $catObject, $selectConf, $aliasArray);

                    if ($newTablenames != '') {
                        $tableNameArray[] = $newTablenames;
                    }
                }
            }
        }

        return implode(',', $tableNameArray);
    }

    public function addselectConfCat($catObject, $cat, &$selectConf)
    {
        $tableNameArray = [];

        // Call all addWhere hooks for DAM categories at the end of this method
        if (
            isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['DAMCategory']) &&
            is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['DAMCategory'])
        ) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['DAMCategory'] as $classRef) {
                $hookObj = GeneralUtility::makeInstance($classRef);
                if (method_exists($hookObj, 'addselectConfCat')) {
                    $newTablenames = $hookObj->addselectConfCat($this, $catObject, $cat, $selectConf, $catObject->getDepth());
                    if ($newTablenames != '') {
                        $tableNameArray[] = $newTablenames;
                    }
                }
            }
        }

        return implode(',', $tableNameArray);
    }

    public function getPageUidsCat($cat): string
    {
        $uidArray = [];

        // Call all addWhere hooks for DAM categories at the end of this method
        if (
            isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['DAMCategory']) &&
            is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['DAMCategory'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['DAMCategory'] as $classRef) {
                $hookObj = GeneralUtility::makeInstance($classRef);
                if (method_exists($hookObj, 'getPageUidsCat')) {
                    $hookObj->getPageUidsCat($this, $cat, $uidArray);
                }
            }
        }
        $uidArray = array_unique($uidArray);

        return implode(',', $uidArray);
    }

    public function getRequiredFields($theCode = '')
    {
        $tableConf = $this->getTableConf($theCode);
        $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');

        if (!empty($tableConf['requiredFields'])) {
            $requiredFields = $tableConf['requiredFields'];
        } else {
            $requiredFields = 'uid,pid,parent_id,category,file_mime_type,file_name,file_path';
        }

        $rc = $requiredFields;

        return $rc;
    }
}
