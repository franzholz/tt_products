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
 * functions for the static_info_countries table
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 *
 */
use JambageCom\Div2007\Utility\CompatibilityUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class tx_ttproducts_country extends tx_ttproducts_table_base
{
    public $dataArray; // array of read in contents
    public $table;	// object of the type tx_table_db
    public $marker = 'STATICCOUNTRIES';

    /**
     * Getting all tt_products_cat categories into internal array.
     */
    public function init($funcTablename): bool
    {
        $result = parent::init($funcTablename);

        if ($result) {
            $tablename = $this->getTablename();
            $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
            $tableConf = $cnf->getTableConf('static_countries');
            $this->getTableObj()->setDefaultFieldArray(['uid' => 'uid', 'pid' => 'pid']);
            $this->getTableObj()->setTCAFieldArray('static_countries');

            $requiredFields = 'uid,pid';
            if (!empty($tableConf['requiredFields'])) {
                $tmp = $tableConf['requiredFields'];
                $requiredFields = ($tmp ?: $requiredFields);
            }
            $requiredListArray = GeneralUtility::trimExplode(',', $requiredFields);
            $this->getTableObj()->setRequiredFieldArray($requiredListArray);

            if (!empty($tableConf['generatePath.']) &&
                $tableConf['generatePath.']['type'] == 'tablefields' &&
                !empty($tableConf['generatePath.']['field.'])
            ) {
                $addRequiredFields = [];
                foreach ($tableConf['generatePath.']['field.'] as $field => $value) {
                    $addRequiredFields[] = $field;
                }
                $this->getTableObj()->addRequiredFieldArray($addRequiredFields);
            }
        }

        return $result;
    } // init

    public function isoGet($country_code, $where = '', $fields = '')
    {
        $rc = [];
        if (!$fields && isset($this->dataArray[$country_code])) {
            $rc = $this->dataArray[$country_code];
        }
        if (!$rc || $where) {
            $pageRepository = CompatibilityUtility::getPageRepository();

            if (!empty($country_code)) {
                $whereString = 'cn_iso_3 = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($country_code, $this->getTableObj()->name);
            } else {
                $whereString = '1=1';
            }
            if ($where) {
                $whereString .= ' AND ' . $where;
            }

            $whereString .= ' ' . $pageRepository->enableFields($this->getTablename());
            $fields = ($fields ?: '*');
            // Fetching the products

            $res = $this->getTableObj()->exec_SELECTquery($fields, $whereString);
            if ($country_code) {
                $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
                $rc = $row;
                if ($row) {
                    $this->dataArray[$row['cn_iso_3']] = $row;
                }
            } else {
                while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
                    $rc[] = $this->dataArray[$row['uid']] = $row;
                }
            }
            $GLOBALS['TYPO3_DB']->sql_free_result($res);
        }

        return $rc;
    }
}
