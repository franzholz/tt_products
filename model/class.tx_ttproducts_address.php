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

    /**
     * Getting all address values into internal array
     */
    public function init ($functablename) {
        $result = parent::init($functablename);
        if ($result) {
            $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');

            $tableconf = $cnf->getTableConf($functablename);
            $tabledesc = $cnf->getTableDesc($functablename);

            $tableObj = $this->getTableObj();
            $tablename = $this->getTablename();

            $tableObj->setConfig($tableconf);
            $defaultFieldArray = $this->getDefaultFieldArray();
            $tableObj->setDefaultFieldArray($defaultFieldArray);
            $tableObj->setNewFieldArray();
            $requiredFields = 'uid,pid,title';

            if (!empty($tableconf['requiredFields'])) {
                $tmp = $tableconf['requiredFields'];
                $requiredFields = ($tmp ? $tmp : $requiredFields);
            }

            $requiredListArray = GeneralUtility::trimExplode(',', $requiredFields);
            $tableObj->setRequiredFieldArray($requiredListArray);
            $tableObj->setTCAFieldArray($tablename);

            if (isset($tabledesc) && is_array($tabledesc)) {
                $this->fieldArray = array_merge($this->fieldArray, $tabledesc);
            }
        }
        return $result;
    } // init


    public function getRootCat () {
        $result = $this->conf['rootAddressID'] ?? '';

        if ($result == '') {
            $result = '0';
        }

        return $result;
    }


    public function getRelationArray (
        $dataArray,
        $excludeCats = '',
        $rootUids = '',
        $allowedCats = ''
    ) {
        $relationArray = [];
        $rootArray = GeneralUtility::trimExplode(',', $rootUids);

        if (is_array($dataArray)) {
            foreach ($dataArray as $k => $row) {

                $uid = $row['uid'];
                foreach ($row as $field => $value) {
                    $relationArray[$uid][$field] = $value;
                }

                $labelField = $this->getField($this->getLabelFieldname());
                $label = '';

                if (strpos($labelField, 'userFunc:') !== false) {
                    $pos = strpos($labelField, ':');
                    $labelFunc = substr($labelField, $pos + 1);
                    $params = ['table' => $this->getTablename(), 'row' => $row];
                    $label = GeneralUtility::callUserFunction($labelFunc, $params, $this);
                } else {
                    $label = $row[$labelField];
                }

                $relationArray[$uid][$this->getLabelFieldname()] = $label;
                $relationArray[$uid]['pid'] = $row['pid'];
                $relationArray[$uid]['parent_category'] = '';
            }
        }

        return $relationArray;
    }


    public function fetchAddressArray ($itemArray) {
        $result = [];

        foreach ($itemArray as $sort => $actItemArray) {
            foreach ($actItemArray as $k1 => $actItem) {
                $row = $actItem['rec'];
                $addressUid = $row['address'];

                if ($addressUid) {
                    $addressRow = $this->get($addressUid);
                    $result[$addressUid] = $addressRow;
                }
            }
        }

        return $result;
    }
}


