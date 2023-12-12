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
 * functions for the order addresses
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage tt_products
 */
use JambageCom\Div2007\Utility\CompatibilityUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class tx_ttproducts_orderaddress extends tx_ttproducts_table_base
{
    public $dataArray; // array of read in frontend users
    public $table;		 // object of the type tx_table_db
    public $fields = [];
    public $tableconf;
    public $piVar = 'fe';

    private bool $bCondition = false;
    private bool $bConditionRecord = false;

    /**
     * Getting all tt_products_cat categories into internal array.
     */
    public function init($functablename)
    {
        $result = parent::init($functablename);

        if ($result) {
            $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');

            $this->tableconf = $cnf->getTableConf($functablename);
            $tablename = $this->getTablename();

            // 			// image
            // 		$this->image = GeneralUtility::makeInstance('tx_ttproducts_field_image_view');
            // 		$this->image->init($this->pibase);

            $this->getTableObj()->setTCAFieldArray($tablename);
            $this->fieldArray['payment'] = ($this->tableconf['payment'] ?? '');
            $requiredFields = 'uid,pid,email' . ($this->fieldArray['payment'] ? ',' . $this->fieldArray['payment'] : '');
            if (
                isset($this->tableconf['ALL.']) &&
                is_array($this->tableconf['ALL.'])
            ) {
                $tmp = $this->tableconf['ALL.']['requiredFields'];
                $requiredFields = ($tmp ?: $requiredFields);
            }
            $requiredListArray = GeneralUtility::trimExplode(',', $requiredFields);
            $this->getTableObj()->setRequiredFieldArray($requiredListArray);
        }

        return $result;
    } // init

    public function getSelectInfoFields()
    {
        $result = ['salutation', 'tt_products_business_partner', 'tt_products_organisation_form'];

        return $result;
    }

    public function getTCATableFromField($field)
    {
        $result = 'fe_users';
        if ($field == 'salutation') {
            $result = 'sys_products_orders';
        }

        return $result;
    }

    public function getFieldName($field)
    {
        $rc = $field;
        if (is_array($this->fieldArray) && $this->fieldArray[$field]) {
            $rc = $this->fieldArray[$field];
        }

        return $rc;
    }

    public function isUserInGroup($feuser, $group)
    {
        $groups = explode(',', $feuser['usergroup']);
        foreach ($groups as $singlegroup) {
            if ($singlegroup == $group) {
                return true;
            }
        }

        return false;
    } // isUserInGroup

    public function setCondition($row, $funcTablename)
    {
        $bCondition = false;
        $this->bConditionRecord = false;

        if (isset($this->conf['conf.'][$funcTablename . '.']['ALL.']['fe_users.']['date_of_birth.']['period.']['y'])) {
            $year = $this->conf['conf.'][$funcTablename . '.']['ALL.']['fe_users.']['date_of_birth.']['period.']['y'];
            $infoViewObj = GeneralUtility::makeInstance('tx_ttproducts_info_view');

            if ($infoViewObj->infoArray['billing']['date_of_birth']) {
                $timeTemp = $infoViewObj->infoArray['billing']['date_of_birth'];
                $bAge = true;
            } elseif ($GLOBALS['TSFE']->fe_user->user) {
                $timeTemp = date('d-m-Y', $GLOBALS['TSFE']->fe_user->user['date_of_birth']);
                $bAge = true;
            } else {
                $bAge = false;
            }

            if ($bAge) {
                $feDateArray = GeneralUtility::trimExplode('-', $timeTemp);
                $date = getdate();
                $offset = 0;
                if ($date['mon'] < $feDateArray[1]) {
                    $offset = 1;
                }
                if ($date['year'] - $feDateArray[2] - $offset >= $year) {
                    $bCondition = true;
                }
            }
        } else {
            $bCondition = true;
        }

        $whereConf = $this->conf['conf.'][$funcTablename . '.']['ALL.']['fe_users.']['where'] ?? '';

        if (!empty($whereConf)) {
            $whereArray = GeneralUtility::trimExplode('IN', $whereConf);
            $pos1 = strpos($whereArray[1], '(');
            $pos2 = strpos($whereArray[1], ')');
            $inString = substr($whereArray[1], $pos1 + 1, $pos2 - $pos1 - 1);

            $valueArray = GeneralUtility::trimExplode(',', $inString);
            foreach ($valueArray as $value) {
                if ($row[$whereArray[0]] == $value) {
                    $this->bConditionRecord = true;
                    break;
                }
            }
        }

        if ($bCondition) {
            $this->bCondition = true;
        }
    }

    public function getCondition()
    {
        return $this->bCondition;
    }

    public function getConditionRecord()
    {
        return $this->bConditionRecord;
    }

    public function getCreditpoints()
    {
        $rc = false;
        if (
            CompatibilityUtility::isLoggedIn() &&
            isset($GLOBALS['TSFE']->fe_user->user) &&
            is_array(
                $GLOBALS['TSFE']->fe_user->user
            )) {
            $rc = $GLOBALS['TSFE']->fe_user->user['tt_products_creditpoints'];
        }

        return $rc;
    }

    public function getPid()
    {
        $result = $this->conf['PIDuserFolder'];

        return $result;
    }
}
