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
 * base class for all database table classes
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage tt_products
 */
use JambageCom\Div2007\Api\Frontend;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

abstract class tx_ttproducts_table_base implements SingletonInterface
{
    protected $bHasBeenInitialised = false;
    public $conf;
    public $config;
    public $tableObj;	// object of the type tx_table_db
    public $defaultFieldArray = ['uid' => 'uid', 'pid' => 'pid']; // fields which must always be read in
    public $relatedFromTableArray = [];
    public $fieldArray = []; // field replacements
    protected $insertRowArray;	// array of stored insert records
    protected $insertKey;		// array for insertion

    protected $tableAlias;	// must be overridden
    protected $dataArray;
    protected $labelfieldname = 'title';
    protected $enable = false;
    private $funcTablename;
    private $tablename;
    private $tableConf;
    private $tableDesc;
    private $theCode;
    private $orderBy;
    private array $fieldClassArray = [
            'ac_uid' => 'tx_ttproducts_field_foreign_table',
            'crdate' => 'tx_ttproducts_field_datetime',
            'creditpoints' => 'tx_ttproducts_field_creditpoints',
            'datasheet' => 'tx_ttproducts_field_datafield',
            'datasheet_uid' => 'tx_ttproducts_field_datafield',
            'delivery' => 'tx_ttproducts_field_delivery',
            'directcost' => 'tx_ttproducts_field_price',
            'endtime' => 'tx_ttproducts_field_datetime',
            'graduated_price_uid' => 'tx_ttproducts_field_graduated_price',
            'image' => 'tx_ttproducts_field_image',
            'image_uid' => 'tx_ttproducts_field_image',
            'smallimage' => 'tx_ttproducts_field_image',
            'smallimage_uid' => 'tx_ttproducts_field_image',
            'itemnumber' => 'tx_ttproducts_field_text',
            'note' => 'tx_ttproducts_field_note',
            'note2' => 'tx_ttproducts_field_note',
            'price' => 'tx_ttproducts_field_price',
            'price2' => 'tx_ttproducts_field_price',
            'sellendtime' => 'tx_ttproducts_field_datetime',
            'sellstarttime' => 'tx_ttproducts_field_datetime',
            'starttime' => 'tx_ttproducts_field_datetime',
            'subtitle' => 'tx_ttproducts_field_text',
            'tax' => 'tx_ttproducts_field_tax',
            'title' => 'tx_ttproducts_field_text',
            'tstamp' => 'tx_ttproducts_field_datetime',
            'usebydate' => 'tx_ttproducts_field_datetime',
        ];

    public function init($funcTablename): bool
    {
        $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
        $this->config = $cnf->getConfig();
        $this->conf = $cnf->getConf();

        $this->tableObj = GeneralUtility::makeInstance('tx_table_db');
        $this->insertKey = 0;

        $this->setFuncTablename($funcTablename);
        $tablename = $cnf->getTableName($funcTablename);
        $tablename = ($tablename ?: $funcTablename);

        if (!isset($GLOBALS['TCA'][$tablename])) {
            debug($tablename, 'Table not found in $GLOBALS[\'TCA\']: ' . $tablename . ' in file class.tx_ttproducts_table_base.php'); // keep this
            $errorText = 'ERROR in the setup of "tt_products.table.' . $funcTablename . '": wrong table name "' . $tablename . '".';
            $lastUnderscore = strrpos($tablename, '_');
            $extName = substr($tablename, 0, $lastUnderscore);
            if (strpos($extName, 'tx') === 0) {
                $errorText .= '<br/> Consider to install the extension "' . $extName . '" or change this setup.';
            }
            debug($errorText); // keep this

            return false;
        }

        $this->setTablename($tablename);
        $this->tableDesc = $cnf->getTableDesc($funcTablename);

        $checkDefaultFieldArray =
            [
                'crdate' => 'crdate',
                'deleted' => 'deleted',
                'endtime' => 'endtime',
                'fe_group' => 'fe_group',
                'hidden' => 'hidden',
                'starttime' => 'starttime',
                'tstamp' => 'tstamp',
            ];

        if (isset($GLOBALS['TCA'][$tablename]['ctrl']) && is_array($GLOBALS['TCA'][$tablename]['ctrl'])) {
            foreach ($checkDefaultFieldArray as $theField) {
                if (
                    isset($GLOBALS['TCA'][$tablename]['ctrl'][$theField]) &&
                    isset($GLOBALS['TCA'][$tablename]['columns'][$theField]) &&
                    is_array(
                        $GLOBALS['TCA'][$tablename]['columns'][$theField]
                    ) ||
                    in_array($theField, $GLOBALS['TCA'][$tablename]['ctrl'], true) ||
                    (
                        isset($GLOBALS['TCA'][$tablename]['ctrl']['enablecolumns']) &&
                        is_array($GLOBALS['TCA'][$tablename]['ctrl']['enablecolumns']) &&
                        in_array($theField, $GLOBALS['TCA'][$tablename]['ctrl']['enablecolumns'], true)
                    )
                ) {
                    $this->defaultFieldArray[$theField] = $theField;
                }
            }
        }

        if (isset($this->tableDesc) && is_array($this->tableDesc)) {
            $this->fieldArray = array_merge($this->fieldArray, $this->tableDesc);
        }

        $labelfieldname = $this->getLabelFieldname();

        $this->fieldArray[$labelfieldname] = (!empty($this->tableDesc['name']) && is_array($GLOBALS['TCA'][$this->tableDesc['name']]['ctrl']) ? $this->tableDesc['name'] : ($GLOBALS['TCA'][$tablename]['ctrl']['label'] ?: 'name'));

        $this->defaultFieldArray[$this->fieldArray[$labelfieldname]] = $this->fieldArray[$labelfieldname];

        if (isset($GLOBALS['TCA'][$tablename]['ctrl']['label_userFunc'])) {
            $this->fieldArray[$labelfieldname] = 'userFunc:' . $GLOBALS['TCA'][$tablename]['ctrl']['label_userFunc'];
        }

        if (
            isset($this->defaultFieldArray) &&
            is_array($this->defaultFieldArray) &&
            count($this->defaultFieldArray)
        ) {
            $this->tableObj->setDefaultFieldArray($this->defaultFieldArray);
        }

        $this->tableObj->setName($tablename);
        $this->tableConf = $this->getTableConf('');
        $this->initCodeConf('ALL', $this->tableConf);

        $this->tableObj->setTCAFieldArray($tablename, $this->tableAlias);
        $this->tableObj->setNewFieldArray();
        $this->bHasBeenInitialised = true;
        $this->enable = true;

        return true;
    }

    public function isEnabled()
    {
        return $this->enable;
    }

    public function clear(): void
    {
        $this->dataArray = [];
    }

    public function setLabelFieldname($labelfieldname): void
    {
        $this->labelfieldname = $labelfieldname;
    }

    public function getLabelFieldname()
    {
        return $this->labelfieldname;
    }

    public function getField($theField)
    {
        $result = $theField;
        if (isset($this->fieldArray[$theField])) {
            $result = $this->fieldArray[$theField];
        }

        return $result;
    }

    // uid can be a string. Add a blank character to your uid integer if you want to have muliple rows as a result
    public function get(
        $uid = '0',
        $pid = 0,
        $bStore = true,
        $where_clause = '',
        $groupBy = '',
        $orderBy = '',
        $limit = '',
        $fields = '',
        $bCount = false,
        $aliasPostfix = '',
        $bUseEnableFields = true,
        $fallback = false
    ) {
        $rc = false;
        if (!$this->isEnabled()) {
            return false;
        }

        $tableObj = $this->getTableObj();
        $alias = $this->getAlias() . $aliasPostfix;

        if (
            MathUtility::canBeInterpretedAsInteger($uid) &&
            isset($this->dataArray[$uid]) &&
            is_array($this->dataArray[$uid]) &&
            !$where_clause &&
            !$fields
        ) {
            if (!$pid || ($pid && $this->dataArray[$uid]['pid'] == $pid)) {
                $rc = $this->dataArray[$uid];
            } else {
                $rc = [];
            }
        }

        if (!$rc) {
            $enableFields = $tableObj->enableFields($aliasPostfix);
            $where = '1=1';

            if (is_int($uid)) {
                $where .= ' AND ' . $alias . '.uid = ' . intval($uid);
            } elseif ($uid) {
                $uidArray = GeneralUtility::trimExplode(',', $uid);
                foreach ($uidArray as $k => $v) {
                    $uidArray[$k] = intval($v);
                }
                $where .= ' AND ' . $alias . '.uid IN (' . implode(',', $uidArray) . ')';
            }

            if ($pid) {
                $pidArray = GeneralUtility::trimExplode(',', $pid);
                foreach ($pidArray as $k => $v) {
                    $pidArray[$k] = intval($v);
                }
                $where .= ' AND ' . $alias . '.pid IN (' . implode(',', $pidArray) . ')';
            }

            if ($where_clause) {
                if (strpos($where_clause, (string)$enableFields) !== false) {
                    $bUseEnableFields = false;
                }
                $where .= ' AND ( ' . $where_clause . ' )';
            }

            if ($bUseEnableFields) {
                $where .= $enableFields;
            }

            if (!$fields) {
                if ($bCount) {
                    $fields = 'count(*)';
                } else {
                    $fields = '*';
                }
            }

            // Fetching the records
            $res =
                $tableObj->exec_SELECTquery(
                    $fields,
                    $where,
                    $groupBy,
                    $orderBy,
                    $limit,
                    '',
                    $aliasPostfix,
                    $fallback
                );

            if ($res !== false) {
                $rc = [];

                while ($dbRow = $GLOBALS['TYPO3_DB']->sql_fetch_row($res)) {
                    $row = [];
                    foreach ($dbRow as $index => $value) {
                        if ($res instanceof mysqli_result) {
                            $fieldObject = mysqli_fetch_field_direct($res, $index);
                            $field = $fieldObject->name;
                        } else {
                            $field = mysql_field_name($res, $index);
                        }

                        if (!isset($row[$field]) || !empty($value)) {
                            $row[$field] = $value;
                        }
                    }

                    if (
                        !$fallback &&
                        is_array($tableObj->langArray) &&
                        isset($row['title']) &&
                        !empty($tableObj->langArray[$row['title']])
                    ) {
                        $row['title'] = $tableObj->langArray[$row['title']];
                    }

                    if ($row && !empty($row['uid'])) {
                        $rc[$row['uid']] = $row;
                        if ($bStore && $fields == '*') {
                            $this->dataArray[$row['uid']] = $row;
                        }
                    } else {
                        break;
                    }
                }

                $GLOBALS['TYPO3_DB']->sql_free_result($res);
                if (
                    MathUtility::canBeInterpretedAsInteger($uid)
                ) {
                    reset($rc);
                    $rc = current($rc);
                }

                if ($bCount && is_array($rc[0])) {
                    reset($rc[0]);
                    $rc = intval(current($rc[0]));
                }

                if (!$rc) {
                    $rc = [];
                }
            } else {
                $rc = false;
            }
        }

        return $rc;
    }

    /**
     * Returns the label of the record, Usage in the following format:
     *
     * @return	string		Label of the record
     */
    public function getLabel($row)
    {
        return $row['title'];
    }

    public function needsInit()
    {
        return !$this->bHasBeenInitialised;
    }

    public function destruct(): void
    {
        $this->bHasBeenInitialised = false;
    }

    public function getDefaultFieldArray()
    {
        return $this->defaultFieldArray;
    }

    public function getFieldClass($fieldname)
    {
        $class = '';
        $result = false;

        $tablename = $this->getTablename();

        if (
            $fieldname &&
            (
                isset($GLOBALS['TCA'][$tablename]['columns'][$fieldname]) &&
                is_array($GLOBALS['TCA'][$tablename]['columns'][$fieldname])
            ) ||
            (
                isset($GLOBALS['TCA'][$tablename]['columns'][$fieldname . '_uid']) &&
                is_array($GLOBALS['TCA'][$tablename]['columns'][$fieldname . '_uid'])
            )
        ) {
            $funcTablename = $this->getFuncTablename();

            if (
                isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['fieldClass']) &&
                is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['fieldClass']) &&
                isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['fieldClass'][$funcTablename]) &&
                is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['fieldClass'][$funcTablename])
            ) {
                foreach (
                    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['fieldClass'][$funcTablename] as $extKey => $hookArray
                ) {
                    if (
                        ExtensionManagementUtility::isLoaded($extKey) &&
                        is_array($hookArray) &&
                        isset($hookArray[$fieldname])
                    ) {
                        $class = $hookArray[$fieldname];
                    }
                }
            }
            if (!$class && isset($this->fieldClassArray[$fieldname])) {
                $class = $this->fieldClassArray[$fieldname];
            }

            $result = $class;
        }

        return $result;
    }

    public function getAlias()
    {
        $tableObj = $this->getTableObj();

        return $tableObj->getAlias();
    }

    public function getFuncTablename()
    {
        return $this->funcTablename;
    }

    private function setFuncTablename($tablename): void
    {
        $this->funcTablename = $tablename;
    }

    public function getTablename()
    {
        return $this->tablename;
    }

    private function setTablename($tablename): void
    {
        $this->tablename = $tablename;
    }

    public function getLangName()
    {
        $tableObj = $this->getTableObj();

        return $tableObj->getLangName();
    }

    public function getCode()
    {
        return $this->theCode;
    }

    public function setCode($theCode): void
    {
        $this->theCode = $theCode;
    }

    public function getOrderBy()
    {
        return $this->orderBy;
    }

    // initalisation for code dependant configuration
    public function initCodeConf(
        $theCode,
        $tableConf
    ): void {
        if ($theCode != $this->getCode()) {
            $this->setCode($theCode);
            if (
                isset($tableConf['orderBy']) &&
                $this->orderBy != $tableConf['orderBy']
            ) {
                $this->orderBy = $tableConf['orderBy'];
                $this->dataArray = [];
            }

            $requiredFields = $this->getRequiredFields($theCode);
            $requiredFieldArray = GeneralUtility::trimExplode(',', $requiredFields);
            $this->getTableObj()->setRequiredFieldArray($requiredFieldArray);

            if (
                isset($tableConf['language.']) &&
                isset($tableConf['language.']['type']) &&
                $tableConf['language.']['type'] == 'field' &&
                isset($tableConf['language.']['field.'])
            ) {
                $addRequiredFields = [];
                $addRequiredFields = $tableConf['language.']['field.'];
                $this->getTableObj()->addRequiredFieldArray($addRequiredFields);
            }
            $tableObj = $this->getTableObj();
            if ($this->bUseLanguageTable($tableConf)) {
                $tableObj->setLanguage($this->config['LLkey']);
                $tableObj->setLangName($tableConf['language.']['table']);
                $tableObj->setTCAFieldArray($tableObj->getLangName(), $tableObj->getAlias() . 'lang', false);
            }
            if (
                isset($tableConf['language.']) &&
                isset($tableConf['language.']['type']) &&
                $tableConf['language.']['type'] == 'csv'
            ) {
                $tableObj->initLanguageFile($tableConf['language.']['file']);
            }

            if (!empty($tableConf['language.']['marker.']['file'])) {
                $tableObj->initMarkerFile($tableConf['language.']['marker.']['file']);
            }
        }
    }

    public function translateByFields(&$dataArray, $theCode): void
    {
        $langFieldArray = $this->getLanguageFieldArray($theCode);

        if (is_array($dataArray) && is_array($langFieldArray) && count($langFieldArray)) {
            foreach ($dataArray as $uid => $row) {
                foreach ($row as $field => $value) {
                    $realField = $langFieldArray[$field];

                    if (isset($realField) && $realField != $field) {
                        $newValue = $dataArray[$uid][$realField];
                        if ($newValue != '') {
                            $dataArray[$uid][$field] = $newValue;
                        }
                    }
                }
            }
        }
    }

    public function bUseLanguageTable($tableConf)
    {
        $rc = false;
        $api =
            GeneralUtility::makeInstance(Frontend::class);
        $sys_language_uid = $api->getLanguageId();

        if (is_numeric($sys_language_uid)) {
            if (
                isset($tableConf['language.']) &&
                isset($tableConf['language.']['type']) &&
                $tableConf['language.']['type'] == 'table' &&
                $sys_language_uid > 0
            ) {
                $rc = true;
            }
        }

        return $rc;
    }

    public function fixTableConf(&$tableConf): void
    {
        // nothing. Override this for your table if needed
    }

    public function getTableConf($theCode = '')
    {
        if ($theCode == '' && $this->getCode() != '') {
            $result = $this->tableConf;
        } else {
            $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
            $result = $cnf->getTableConf($this->getFuncTablename(), $theCode);
        }
        $this->fixTableConf($result);

        return $result;
    }

    public function setTableConf($tableConf): void
    {
        $this->tableConf = $tableConf;
    }

    public function getTableDesc()
    {
        return $this->tableDesc;
    }

    public function setTableDesc($tableDesc): void
    {
        $this->tableDesc = tableDesc;
    }

    public function getKeyFieldArray($theCode = '')
    {
        $tableConf = $this->getTableConf($theCode);
        $rc = [];
        if (isset($tableConf['keyfield.']) && is_array($tableConf['keyfield.'])) {
            $rc = $tableConf['keyfield.'];
        }

        return $rc;
    }

    public function getRequiredFields($theCode = '')
    {
        $tableObj = $this->getTableObj();
        $tablename = $this->getTablename();
        $tableConf = $this->getTableConf($theCode);
        $fields = '';
        if (!empty($tableConf['requiredFields'])) {
            $fields = $tableConf['requiredFields'];
        } else {
            $fields = 'uid,pid';
        }

        $requiredFieldArray = [];
        $defaultFieldArray = $tableObj->getDefaultFieldArray();
        $noTcaFieldArray = $tableObj->getNoTcaFieldArray();

        $fieldArray = GeneralUtility::trimExplode(',', $fields);

        if (is_array($fieldArray)) {
            foreach ($fieldArray as $field) {
                if (
                    in_array($field, $defaultFieldArray) ||
                    in_array($field, $noTcaFieldArray) ||
                    isset($GLOBALS['TCA'][$tablename]['columns'][$field]) &&
                    is_array($GLOBALS['TCA'][$tablename]['columns'][$field])
                ) {
                    $requiredFieldArray[] = $field;
                }
            }
        }

        $result = implode(',', $requiredFieldArray);

        return $result;
    }

    public function getLanguageFieldArray($theCode = '')
    {
        $tableConf = $this->getTableConf($theCode);
        if (is_array($tableConf['language.']) &&
            $tableConf['language.']['type'] == 'field' &&
            is_array($tableConf['language.']['field.'])
        ) {
            $rc = $tableConf['language.']['field.'];
        } else {
            $rc = [];
        }

        return $rc;
    }

    public function getTableObj()
    {
        return $this->tableObj;
    }

    public function reset(): void
    {
        $this->insertRowArray = [];
        $this->setInsertKey(0);
    }

    public function setInsertKey($k): void
    {
        $this->insertKey = $k;
    }

    public function getInsertKey()
    {
        return $this->insertKey;
    }

    public function addInsertRow(
        $row,
        &$k = ''
    ): void {
        $bUseInsertKey = false;

        if ($k == '') {
            $k = $this->getInsertKey();
            $bUseInsertKey = true;
        }
        $this->insertRowArray[$k++] = $row;
        if ($bUseInsertKey) {
            $this->setInsertKey($k);
        }
    }

    public function getInsertRowArray()
    {
        return $this->insertRowArray;
    }
}
