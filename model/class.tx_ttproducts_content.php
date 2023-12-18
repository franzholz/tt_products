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
 * functions for the content
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage tt_products
 */
use JambageCom\Div2007\Api\Frontend;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class tx_ttproducts_content extends tx_ttproducts_table_base
{
    public $dataArray = []; // array of read in contents
    public $dataPageArray = []; // array of read in contents with page id as index
    public $table;		 // object of the type tx_table_db

    /**
     * Getting all tt_products_cat categories into internal array.
     */
    public function init($functablename)
    {
        $result = parent::init($functablename);

        $this->getTableObj()->setDefaultFieldArray(['uid' => 'uid', 'pid' => 'pid', 't3ver_oid' => 't3ver_oid', 'tstamp' => 'tstamp', 'sorting' => 'sorting',
        'deleted' => 'deleted', 'hidden' => 'hidden', 'starttime' => 'starttime', 'endtime' => 'endtime', 'fe_group' => 'fe_group']);
        $this->getTableObj()->setTCAFieldArray('tt_content');

        return $result;
    } // init

    public function getFromPid($pid)
    {
        $rcArray = $this->dataPageArray[$pid];

        if (!is_array($rcArray)) {
            $sql = GeneralUtility::makeInstance('tx_table_db_access');
            $sql->prepareFields($this->getTableObj(), 'select', '*');
            $sql->prepareFields($this->getTableObj(), 'orderBy', 'sorting');
            $sql->prepareWhereFields($this->getTableObj(), 'pid', '=', intval($pid));
            $api =
                GeneralUtility::makeInstance(Frontend::class);
            $sys_language_uid = $api->getLanguageId();

            $sql->prepareWhereFields($this->getTableObj(), 'sys_language_uid', '=', intval($sys_language_uid));

            $enableFields = $this->getTableObj()->enableFields();
            $sql->where_clause .= $enableFields;

            // Fetching the category
            $res = $sql->exec_SELECTquery();
            while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
                $this->dataPageArray[$pid][$row['uid']] = $row;
            }
            $GLOBALS['TYPO3_DB']->sql_free_result($res);
            $tmp = $this->dataPageArray[$pid];
            $rcArray = (is_array($tmp) ? $tmp : []);
        }

        return $rcArray;
    }

    // returns the Path of all categories above, separated by '/'
    public function getCategoryPath($uid)
    {
        $rc = '';

        return $rc;
    }
}
