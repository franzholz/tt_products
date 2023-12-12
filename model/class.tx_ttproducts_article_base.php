<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2016 Franz Holzinger (franz@ttproducts.de)
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
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage tt_products
 */
use JambageCom\TtProducts\Api\PriceApi;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

abstract class tx_ttproducts_article_base extends tx_ttproducts_table_base
{
    public $table;	 // object of the type tx_table_db
    public $conf;
    public $config;

    public $marker;	// marker prefix in the template file. must be overridden
    public $type; 	// the type of table 'article' or 'product'
    // this gets in lower case also used for the URL parameter
    public $variant;       // object for the product variant attributes, must initialized in the init function
    public $editVariant; 	// object for the product editable variant attributes, must initialized in the init function
    public $mm_table = ''; // only set if a mm table is used
    protected $graduatedPriceObj = false;

    /**
     * Getting all tt_products_cat categories into internal array.
     */
    public function init($functablename)
    {
        $result = parent::init($functablename);

        if ($result) {
            $type = $this->getType();
            $tablename = $this->getTablename();
            $useArticles = $this->conf['useArticles'] ?? 0;
            $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
            $conf = $cnf->getConf();

            if ($type == 'product') {
                $this->variant = GeneralUtility::makeInstance('tx_ttproducts_variant');
                $this->variant->init($this, $tablename, $useArticles);
                $this->editVariant = GeneralUtility::makeInstance('tx_ttproducts_edit_variant');
                $this->editVariant->init($this);
            } else {
                $this->variant = GeneralUtility::makeInstance('tx_ttproducts_variant_dummy');
                $this->editVariant = GeneralUtility::makeInstance('tx_ttproducts_edit_variant_dummy');
            }
            $tableDesc = $this->getTableDesc();

            $this->fieldArray['address'] = (!empty($tableDesc['address']) ? $tableDesc['address'] : 'address');
            $this->fieldArray['itemnumber'] = (!empty($tableDesc['itemnumber']) ? $tableDesc['itemnumber'] : 'itemnumber');

            if (
                $type == 'product' ||
                $type == 'article'
            ) {
                $graduatedPriceObj = GeneralUtility::makeInstance('tx_ttproducts_graduated_price');

                if ($type == 'product') {
                    $graduatedPriceObj->init(
                        $this,
                        'graduated_price_uid'
                    );
                } else {
                    $graduatedPriceObj->init(
                        $this,
                        'graduated_price_uid'
                    );
                }
                $this->setGraduatedPriceObject($graduatedPriceObj);
            }
        }

        return $result;
    } // init

    public function setGraduatedPriceObject($value): void
    {
        $this->graduatedPriceObject = $value;
    }

    public function getGraduatedPriceObject()
    {
        return $this->graduatedPriceObject;
    }

    /**
     * Reduces the instock value of the orderRecord with the amount and returns the result.
     */
    public function reduceInStock($uid, $count): void
    {
        $tableDesc = $this->getTableDesc();
        $instockField = $tableDesc['inStock'];
        $instockField = ($instockField ?: 'inStock');

        if (is_array($this->getTableObj()->tableFieldArray[$instockField])) {
            $uid = intval($uid);
            $fieldsArray = [];
            $fieldsArray[$instockField] = $instockField . '-' . $count;
            $res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery($this->getTableObj()->name, 'uid=\'' . $uid . '\'', $fieldsArray, $instockField);
        }
    }

    /**
     * Reduces the instock value of the orderRecords with the sold items and returns the result.
     */
    public function reduceInStockItems($itemArray, $useArticles): void
    {
    }

    public function getRelated(
        &$parentFuncTablename,
        &$parentRows,
        $multiOrderArray,
        $uid,
        $type,
        $orderBy = ''
    ): void {
    }

    public function getType()
    {
        return $this->type;
    }

    public function getEditVariant()
    {
        return $this->editVariant;
    }

    public function getVariant()
    {
        return $this->variant;
    }

    public function getFlexQuery($type, $val = 1)
    {
        $spacectrl = '[[:space:][:cntrl:]]*';

        $rc = '<field index="' . $type . '">' . $spacectrl . '<value index="vDEF">' . ($val ? '1' : '0') . '</value>' . $spacectrl . '</field>' . $spacectrl;

        return $rc;
    }

    public function addWhereCat($catObject, $theCode, $cat, $categoryAnd, $pid_list)
    {
        $where = '';

        return $where;
    }

    public function addselectConfCat($catObject, $cat, &$selectConf): void
    {
    }

    public function getPageUidsCat($cat)
    {
        $uids = '';

        return $uids;
    }

    public function getProductField(&$row, $field)
    {
        return '';
    }

    /**
     * Returns true if the item has the $check value checked.
     */
    public function hasAdditional(&$row, $check)
    {
        $hasAdditional = false;

        return $hasAdditional;
    }

    public function getWhere($where, $theCode = '', $orderBy = '')
    {
        $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
        $tableconf = $cnf->getTableConf($this->getFuncTablename(), $theCode);
        $rc = [];
        $where = ($where ?: '1=1 ') . $this->getTableObj()->enableFields();

        // Fetching the products
        $res = $this->getTableObj()->exec_SELECTquery('*', $where, '', $GLOBALS['TYPO3_DB']->stripOrderBy($orderBy));
        $translateFields = $cnf->getTranslationFields($tableconf);

        while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
            foreach ($translateFields as $field => $transfield) {
                $row[$field] = $row[$transfield];
            }
            $rc[$row['uid']] = $this->dataArray[$row['uid']] = $row;
        }
        $GLOBALS['TYPO3_DB']->sql_free_result($res);

        return $rc;
    }

    public function searchWhere($searchFieldList, $sw, $theCode)
    {
        $where = '';

        $tableConf = $this->getTableConf($theCode);
        $replaceConf = [];

        $bUseLanguageTable = $this->bUseLanguageTable($tableConf);
        $charRegExp = $this->getCharRegExp($tableConf, $replaceConf);

        if ($bUseLanguageTable) {
            $where =
                $this->getTableObj()->searchWhere(
                    $sw,
                    $searchFieldList,
                    true,
                    $charRegExp,
                    $replaceConf
                );
        } else {
            $where =
                $this->getTableObj()->searchWhere(
                    $sw,
                    $searchFieldList,
                    false,
                    $charRegExp,
                    $replaceConf
                );
        }

        return $where;
    } // searchWhere

    public function getCharRegExp($tableConf, &$replaceConf)
    {
        $result = '';
        $replaceConf = [];

        if (isset($tableConf['charRegExp'])) {
            $result = $tableConf['charRegExp'];
            if (
                isset($tableConf['charRegExp.']) &&
                isset($tableConf['charRegExp.']['replace.']) &&
                $tableConf['charRegExp.']['replace.']['type'] == 'bothsided'
            ) {
                foreach ($tableConf['charRegExp.']['replace.'] as $k => $lineReplaceConf) {
                    if (
                        $k != 'type' &&
                        strpos($k, '.') == strlen($k) - 1
                    ) {
                        $k1 = substr($k, 0, strlen($k) - 1);
                        if (MathUtility::canBeInterpretedAsInteger($k1)) {
                            $replaceConf = array_merge($replaceConf, $lineReplaceConf);
                        }
                    }
                }
            }
        }

        return $result;
    }

    public function getNeededUrlParams($functablename, $theCode)
    {
        $rc = '';
        $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
        $tableconf = $cnf->getTableConf($functablename, $theCode);
        if (is_array($tableconf) && isset($tableconf['urlparams'])) {
            $rc = $tableconf['urlparams'];
        }

        return $rc;
    }

    public function clearSelectableVariantFields(
        &$targetRow
    ): void {
        $fieldArray = $this->getVariant()->getFieldArray();
        $selectableArray = $this->getVariant()->getSelectableArray();
        $count = 0;

        foreach ($fieldArray as $key => $field) {
            if ($selectableArray[$key]) {
                $targetRow[$field] = '';
            }
        }
    }

    public function mergeVariantFields(
        &$targetRow,
        $sourceRow,
        $bKeepNotEmpty = true
    ): void {
        $variantFieldArray = $this->getVariant()->getFieldArray();

        if (isset($variantFieldArray) && is_array($variantFieldArray)) {
            foreach ($variantFieldArray as $field) {
                if (isset($sourceRow[$field])) {
                    $value = $sourceRow[$field];

                    if ($bKeepNotEmpty) {
                        if (!$targetRow[$field]) {
                            $targetRow[$field] = $value;
                        }
                    } else {
                        $targetRow[$field] = $value;
                    }
                }
            }
        }
    }

    public function mergeAttributeFields(
        array &$targetRow,
        array $sourceRow,
        $bKeepNotEmpty = true,
        $bAddValues = false,
        $bMergeVariants = true,
        $calculationField = '',
        $bUseExt = false,
        $mergePrices = true
    ): bool {
        $fieldArray = [];
        $fieldArray['data'] = ['itemnumber', 'image', 'smallimage'];
        $fieldArray['number'] = ['weight', 'inStock'];
        $fieldArray['price'] = ['price', 'price2', 'deposit', 'directcost'];
        if (
            $calculationField != '' &&
            isset($sourceRow[$calculationField])
        ) {
            $fieldArray['price'][] = $calculationField;
        }
        $bIsAddedPrice = false;
        $cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
        $tableDesc = $this->getTableDesc();

        if (
            isset($tableDesc['conf.']) &&
            is_array($tableDesc['conf.']) &&
            isset($tableDesc['conf.']['mergeAppendFields'])
        ) {
            $mergeAppendArray = GeneralUtility::trimExplode(',', $tableDesc['conf.']['mergeAppendFields']);
            $fieldArray['text'] = $mergeAppendArray;
        } else {
            $mergeAppendArray = [];
        }

        $fieldArray['text'][] = 'title';
        $fieldArray['text'][] = 'subtitle';
        $fieldArray['text'][] = 'note';
        $fieldArray['text'][] = 'note2';
        $fieldArray['text'] = array_unique($fieldArray['text']);

        $previouslyAddedPrice = 0;

        if (
            $bUseExt &&
            $mergePrices &&
            isset($targetRow['ext']) &&
            is_array($targetRow['ext']) &&
            isset($targetRow['ext']['addedPrice'])
        ) {
            $previouslyAddedPrice = $targetRow['addedPrice'];
        }

        $bIsAddedPrice = $cnfObj->hasConfig($sourceRow, 'isAddedPrice');

        foreach ($fieldArray as $type => $fieldTypeArray) {
            foreach ($fieldTypeArray as $k => $field) {
                if (isset($sourceRow[$field])) {
                    $value = $sourceRow[$field];

                    if ($type == 'price') {
                        if ($mergePrices) {
                            PriceApi::mergeRows(
                                $targetRow,
                                $sourceRow,
                                $field,
                                $bIsAddedPrice,
                                $previouslyAddedPrice,
                                $calculationField,
                                $bKeepNotEmpty .
                                $bUseExt
                            );
                        }
                    } elseif (
                        ($type == 'text') ||
                        ($type == 'data') ||
                        ($type == 'number')
                    ) {
                        if ($bKeepNotEmpty) {
                            if (
                                $type == 'number' && !round($targetRow[$field], 16) ||
                                $type != 'number' && empty($targetRow[$field])
                            ) {
                                $targetRow[$field] = $value;
                            }
                        } else { // $bKeepNotEmpty == false
                            if (
                                $type == 'number' &&
                                (
                                    !round($targetRow[$field], 16) ||
                                    round($value, 16) ||
                                    ($field == 'inStock')
                                ) ||
                                $type != 'number' &&
                                (empty($targetRow[$field]) || !empty($value))
                            ) {
                                if (
                                    ($bAddValues == true) &&
                                    in_array($field, $mergeAppendArray)
                                ) {
                                    if (!isset($targetRow[$field])) {
                                        $targetRow[$field] = '';
                                    }
                                    $targetRow[$field] .= ' ' . $value;
                                } else {
                                    $targetRow[$field] = $value;
                                }
                            }
                        }
                    }
                }
            }
        }
        // copy the normal fields

        if ($bMergeVariants) {
            $this->mergeVariantFields(
                $targetRow,
                $sourceRow,
                $bKeepNotEmpty
            );
        }

        return true;
    } // mergeAttributeFields

    public function mergeGraduatedPrice(
        &$targetRow,
        $count
    ): void {
    }

    public function getTotalDiscount(&$row, $pid = 0): void
    {
    }
}
