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
use JambageCom\TtProducts\Model\Field\FieldInterface;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

abstract class tx_ttproducts_table_base_view implements SingletonInterface
{
    private bool $bHasBeenInitialised = false;
    public $piVar;
    public $modelObj;
    public $marker;		// can be overridden
    public $tablesWithoutView = ['tt_products_emails'];

    public function init($modelObj): bool
    {
        $this->modelObj = $modelObj;
        $this->bHasBeenInitialised = true;

        return true;
    }

    public function needsInit()
    {
        return !$this->bHasBeenInitialised;
    }

    public function destruct(): void
    {
        $this->bHasBeenInitialised = false;
    }

    public function getModelObj()
    {
        return $this->modelObj;
    }

    public function getFieldObj($field)
    {
        $classname = $this->getFieldClass($field);
        if (
            $classname
        ) {
            $result = $this->getObj($classname);
        }

        return $result;
    }

    public function getPivar()
    {
        return $this->piVar;
    }

    public function setPivar($piVar): void
    {
        $this->piVar = $piVar;
    }

    public function setMarker($marker): void
    {
        $this->marker = $marker;
    }

    public function getMarker()
    {
        return $this->marker;
    }

    public function getOuterSubpartMarker()
    {
        $marker = $this->getMarker();

        return '###' . $marker . '_ITEMS###';
    }

    public function getInnerSubpartMarker()
    {
        $marker = $this->getMarker();

        return '###ITEM_' . $marker . '###';
    }

    public function getObj($className)
    {
        $classNameView = $className . '_view';
        $fieldViewObj = GeneralUtility::makeInstance('' . $classNameView);	// fetch and store it as persistent object
        if (!is_object($fieldViewObj)) {
            throw new RuntimeException('Error in tt_products: The class "' . $classNameView . '" is not found.', 50001);
        }

        if ($fieldViewObj->needsInit()) {
            $fieldObj = GeneralUtility::makeInstance('' . $className);	// fetch and store it as persistent object

            if (!is_object($fieldObj)) {
                throw new RuntimeException('Error in tt_products: The class "' . $className . '" is not found.', 50002);
            }

            if (
                method_exists($fieldObj, 'needsInit') &&
                $fieldObj->needsInit()
            ) {
                $fieldObj->init();
            }
            $fieldViewObj->init($fieldObj);
        }

        return $fieldViewObj;
    }

    public function getFieldClass($fieldname)
    {
        $result = $this->getModelObj()->getFieldClass($fieldname);

        return $result;
    }

    public function getTagMarkerArray(
        $tagArray,
        $parentMarker
    ) {
        $resultArray = [];
        $search = $parentMarker . '_' . $this->getMarker() . '_';
        $searchLen = strlen($search);
        foreach ($tagArray as $marker => $k) {
            if (substr($marker, 0, $searchLen) == $search) {
                $tmp = substr($marker, $searchLen, strlen($marker) - $searchLen);
                $resultArray[] = $tmp;
            }
        }

        return $resultArray;
    }

    public function setMarkersEmpty(
        $tagArray,
        $emptyMarkerArray,
        &$resultMarkerArray
    ): void {
        if (isset($tagArray) && is_array($tagArray)) {
            foreach ($tagArray as $theTag => $v) {
                foreach ($emptyMarkerArray as $theMarker) {
                    if (
                        strpos($theTag, (string)$theMarker) === 0 &&
                        !isset($resultMarkerArray['###' . $theTag . '###'])
                    ) {
                        $resultMarkerArray['###' . $theTag . '###'] = '';
                    }
                }
            }
        }
    }

    public function getItemSubpartArrays(
        &$templateCode,
        $funcTablename,
        $row,
        &$subpartArray,
        &$wrappedSubpartArray,
        $tagArray,
        $theCode = '',
        $basketExtra = [],
        $basketRecs = [],
        $id = '',
        $checkPriceZero = false
    ): void {
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
        $tableconf = $cnf->getTableConf($funcTablename, $theCode);

        if (
            isset($row) &&
            is_array($row) &&
            !empty($row)
        ) {
            $newRow = $row;
            $addedFieldArray = [];
            foreach ($row as $field => $value) {
                $classname = $this->getFieldClass($field);
                if (
                    $classname
                ) {
                    $fieldViewObj = $this->getObj($classname);
                    if (method_exists($fieldViewObj, 'modifyItemSubpartRow')) {
                        $newRow =
                            $fieldViewObj->modifyItemSubpartRow(
                                $field,
                                $newRow,
                                $addedFieldArray
                            );
                    }
                }
            }
            $row = $newRow;
            $comparatorArray =
                ['EQ' => '==', 'NE' => '!=', 'LT' => '<', 'LE' => '<=', 'GT' => '>', 'GE' => '>='];
            $operatorArray = ['AND', 'OR'];
            $functionArray = ['EMPTY' => 'empty'];
            $binaryArray = ['NOT' => '!'];

            // $markerKey = $this->marker.'_'.$upperField.'_';
            if (is_array($tagArray)) {
                foreach ($tagArray as $tag => $v1) {
                    if (strpos($tag, (string)$this->marker) === 0) {
                        $bCondition = false;
                        $tagPartArray = explode('_', $tag);
                        $tagCount = count($tagPartArray);
                        $bTagProcessing = false;
                        $fnKey = array_search('FN', $tagPartArray);

                        if ($tagCount > 2 && $fnKey !== false) {
                            $bTagProcessing = true;
                            $tagPartKey = $fnKey + 1;
                            $fieldNameArray = [];
                            for ($i = 1; $i < $fnKey; ++$i) {
                                $fieldNameArray[] = $tagPartArray[$i];
                            }
                            $fieldname = strtolower(implode('_', $fieldNameArray));
                            $binaryOperator = '';
                            $v2 = $binaryArray[$tagPartArray[$tagPartKey]];

                            if ($v2 != '') {
                                $binaryOperator = $v2;
                                $tagPartKey++;
                            }
                            $v3 = $functionArray[$tagPartArray[$tagPartKey]];

                            if ($v3 != '') {
                                $functionname = $v3;
                                $value = $row[$fieldname];
                                $evalString = 'return ' . $binaryOperator . $functionname . '($value);';

                                $bCondition = eval($evalString);
                            }
                        } elseif ($tagCount > 2 && isset($comparatorArray[$tagPartArray[$tagCount - 2]])) {
                            $bTagProcessing = true;
                            $comparator = $tagPartArray[$tagCount - 2];
                            $comparand = $tagPartArray[$tagCount - 1];
                            $fieldname = strtolower($tagPartArray[1]);
                            if ($tagCount > 4) {
                                for ($i = 2; $i <= $tagCount - 3; ++$i) {
                                    $fieldname .= '_' . strtolower($tagPartArray[$i]);
                                }
                            }
                            if (!isset($row[$fieldname])) {
                                $upperFieldname = strtoupper($fieldname);
                                $foundDifferentCase = false;
                                foreach ($row as $field => $v2) {
                                    if (strtoupper($field) == $upperFieldname) {
                                        $foundDifferentCase = true;
                                        $fieldname = $field;
                                        break;
                                    }
                                }
                                if (!$foundDifferentCase) {
                                    continue;
                                }
                            }

                            $fieldArray = [$fieldname => [$comparator, intval($comparand)]];

                            foreach ($fieldArray as $field => $fieldCondition) {
                                $comparator = $comparatorArray[$fieldCondition['0']];

                                if (isset($row[$field]) && $comparator != '') {
                                    $evalString = "return $row[$field]$comparator$fieldCondition[1];";

                                    $bCondition = eval($evalString);
                                }
                            }
                        }

                        if ($bTagProcessing) {
                            if ($bCondition == true) {
                                $wrappedSubpartArray['###' . $tag . '###'] = '';
                            } else {
                                $subpartArray['###' . $tag . '###'] = '';
                            }
                        }
                    }
                }
            }

            $itemTableObj = $tablesObj->get($funcTablename, false);
            $tablename = $itemTableObj->getTablename();

            foreach ($row as $field => $value) {
                $upperField = strtoupper($field);

                if (
                    isset($GLOBALS['TCA'][$tablename]['columns'][$field]) &&
                    is_array($GLOBALS['TCA'][$tablename]['columns'][$field]) &&
                    in_array(
                        $GLOBALS['TCA'][$tablename]['columns'][$field]['config']['type'],
                        ['group', 'inline', 'select']
                    )
                ) {
                    $markerKey = $this->marker . '_HAS_' . $upperField;
                    $markerKeyNot = $this->marker . '_HAS_NO_' . $upperField;
                    if (
                        $tablename != 'tt_products_cat' &&
                        isset($GLOBALS['TCA'][$tablename]['columns'][$field]['foreign_table'])
                    ) {
                        $valueArray = [];
                        if ($value > 50) {
                            $value = 50; // do not allow more subpart markers
                        }
                        for ($i = 1; $i <= $value; ++$i) {
                            $valueArray[] = $i;
                        }
                    } else {
                        $valueArray = GeneralUtility::trimExplode(',', $value);
                    }

                    if (
                        empty($valueArray) ||
                        $valueArray['0'] == 0
                    ) {
                        if (isset($tagArray[$markerKeyNot])) {
                            $wrappedSubpartArray['###' . $markerKeyNot . '###'] = ['', ''];
                        }
                    } else {
                        foreach ($valueArray as $k => $partValue) {
                            $partMarkerKey = $markerKey . ($k + 1);

                            if (isset($tagArray[$partMarkerKey])) {
                                if ($partValue) {
                                    $wrappedSubpartArray['###' . $partMarkerKey . '###'] = ['', ''];
                                } else {
                                    $subpartArray['###' . $partMarkerKey . '###'] = '';
                                }
                            }
                        }

                        if (isset($tagArray[$markerKeyNot])) {
                            $subpartArray['###' . $markerKeyNot . '###'] = '';
                        }
                    }

                    for ($i = count($valueArray); $i < 100; ++$i) {
                        $partMarkerKey = $markerKey . $i;
                        if (
                            isset($tagArray[$partMarkerKey]) &&
                            !isset($wrappedSubpartArray['###' . $partMarkerKey . '###'])
                        ) {
                            $subpartArray['###' . $partMarkerKey . '###'] = '';
                        }
                    }
                }

                $markerKey = $this->marker . '_' . $upperField . '_EMPTY';

                if (isset($tagArray[$markerKey])) {
                    if ($value == 0) {
                        $wrappedSubpartArray['###' . $markerKey . '###'] = ['', ''];
                    } else {
                        $subpartArray['###' . $markerKey . '###'] = '';
                    }
                }
                $markerKeyNot = $this->marker . '_' . $upperField . '_NOT_EMPTY';

                if (isset($tagArray[$markerKeyNot])) {
                    if ($value == 0) {
                        $subpartArray['###' . $markerKeyNot . '###'] = '';
                    } else {
                        $wrappedSubpartArray['###' . $markerKeyNot . '###'] = ['', ''];
                    }
                }

                $classname = $this->getFieldClass($field);
                if (
                    $classname
                ) {
                    $fieldViewObj = $this->getObj($classname);
                    if (method_exists($fieldViewObj, 'getItemSubpartArrays')) {
                        $itemSubpartArray = [];
                        $fieldViewObj->getItemSubpartArrays(
                            $templateCode,
                            $this->marker,
                            $funcTablename,
                            $row,
                            $field,
                            $tableconf,
                            $itemSubpartArray,
                            $wrappedSubpartArray,
                            $tagArray,
                            $theCode,
                            $basketExtra,
                            $basketRecs,
                            $id
                        );
                        $subpartArray = array_merge($subpartArray, $itemSubpartArray);
                    }
                }
            }

            $markerKey = $this->marker . '_NOT_EMPTY';
            if (isset($tagArray[$markerKey])) {
                $wrappedSubpartArray['###' . $markerKey . '###'] = '';
            }
        } else { // if !empty($row)
            $itemTableObj = $tablesObj->get($funcTablename, false);
            $tablename = $itemTableObj->getTablename();
            $markerKey = $this->marker . '_NOT_EMPTY';
            if (isset($tagArray[$markerKey])) {
                $subpartArray['###' . $markerKey . '###'] = '';
            }
            if (is_array($tagArray)) {
                foreach ($tagArray as $tag => $v1) {
                    if (strpos($tag, (string)$this->marker) === 0) {
                        $subpartArray['###' . $tag . '###'] = '';
                    }
                }
            }
        }
    }

    public function getMarkerKey($markerKey)
    {
        if ($markerKey != '') {
            $marker = $markerKey;
        } else {
            if ($this->marker) {
                $marker = $this->marker;
            } else {
                $funcTablename = $this->getModelObj()->getFuncTablename();
                $marker = strtoupper($funcTablename);
            }
        }

        return $marker;
    }

    public function getId($row, $midId, $theCode)
    {
        $funcTablename = $this->getModelObj()->getFuncTablename();
        $extTableName = str_replace('_', '-', $funcTablename);
        $preId = $extTableName;

        if ($midId) {
            $preId .= '-' . $midId;
        }
        $rc = $preId . '-' . str_replace('_', '-', strtolower($theCode)) . '-' . intval($row['uid']);

        return $rc;
    }

    // This can also add additional fields to the row.
    public function modifyFieldObject(
        array $origRow,
        array $row,
        array $tableconf,
        $markerPrefix,
        $suffix,
        array $fieldMarkerArray,
        array $markerArray,
        &$theMarkerArray
    ) {
        $templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        $local_cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);

        $newRow = [];
        foreach ($row as $field => $value) {
            if (isset($tableconf['field.'][$field . '.'])) {
                if (!empty($tableconf['field.'][$field . '.']['untouched'])) {
                    $value = $origRow[$field];
                }
                $tableconf['field.'][$field . '.']['value'] = $value;
                $fieldContent = $local_cObj->cObjGetSingle(
                    $tableconf['field.'][$field],
                    $tableconf['field.'][$field . '.'],
                    TT_PRODUCTS_EXT
                );

                $value =
                    $templateService->substituteMarkerArray(
                        $fieldContent,
                        $fieldMarkerArray
                    );
            }
            $newRow[$field] = $value;
            $markerKey = $markerPrefix . strtoupper($field . $suffix);

            if (!isset($markerArray['###' . $markerKey . '###'])) {
                $theMarkerArray['###' . $markerKey . '###'] = $value;
            }
        }
        $marker = $this->getMarker();

        if (
            isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT][$marker]) &&
            is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT][$marker])
        ) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT][$marker] as $classRef) {
                $hookObj = GeneralUtility::makeInstance($classRef);
                if (method_exists($hookObj, 'modifyFieldObject')) {
                    $hookObj->modifyFieldObject(
                        $thts,
                        $origRow,
                        $row,
                        $tableconf,
                        $markerPrefix,
                        $suffix,
                        $fieldMarkerArray,
                        $markerArray,
                        $theMarkerArray,
                        $newRow
                    );
                }
            }
        }

        return $newRow;
    }

    public function createFieldMarkerArray($row, $markerPrefix, $suffix)
    {
        $fieldMarkerArray = [];
        foreach ($row as $field => $value) {
            if (is_string($value)) {
                $viewField = $field;
                $markerKey = $markerPrefix . strtoupper($viewField . $suffix);

                $fieldMarkerArray['###' . $markerKey . '###'] = $value;
            }
        }

        return $fieldMarkerArray;
    }

    // This can also add additional fields to the row.
    public function getRowMarkerArray(
        $funcTablename,
        $row,
        $markerKey,
        &$markerArray,
        &$variantFieldArray,
        &$variantMarkerArray,
        $tagArray,
        $theCode,
        $basketExtra,
        $basketRecs,
        $bHtml = true,
        $charset = '',
        $imageNum = 0,
        $imageRenderObj = 'image',
        $id = '',	// id part to be added
        $prefix = '', // if false, then no table marker will be added
        $suffix = '',	// this could be a number to discern between repeated rows
        $linkWrap = '',
        $bEnableTaxZero = false
    ): void {
        $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
        $conf = $cnf->getConf();

        $rowMarkerArray = [];
        if ($prefix === false) {
            $marker = '';
        } else {
            $markerKey = $this->getMarkerKey($markerKey);
            $marker = $prefix . $markerKey;
        }
        $mainId = '';

        if (isset($row) && is_array($row) && !empty($row['uid'])) {
            $newRow = $row;
            $addedFieldArray = [];

            foreach ($row as $field => $value) {
                $classname = $this->getFieldClass($field);
                if (
                    $classname
                ) {
                    $fieldViewObj = $this->getObj($classname);

                    if (method_exists($fieldViewObj, 'modifyItemSubpartRow')) {
                        $newRow =
                            $fieldViewObj->modifyItemSubpartRow(
                                $field,
                                $newRow,
                                $addedFieldArray
                            );
                    }
                }
                if (strpos($field, FieldInterface::EXTERNAL_FIELD_PREFIX) === 0) {
                    $newField = substr($field, strlen(FieldInterface::EXTERNAL_FIELD_PREFIX));
                    $newRow[$newField] = $value;
                    unset($newRow[$field]);
                }
            }
            $row = $newRow;
            $funcTablename = $this->getModelObj()->getFuncTablename();
            $extTableName = str_replace('_', '-', $funcTablename);
            $mainId = $this->getId($row, $id, $theCode);
            $markerPrefix = ($marker != '' ? $marker . '_' : '');
            $rowMarkerArray['###' . $markerPrefix . 'ID###'] = $mainId;
            $rowMarkerArray['###' . $markerPrefix . 'NAME###'] = $extTableName . '-' . $row['uid'];
            $tableconf = $cnf->getTableConf($funcTablename, $theCode);

            $tabledesc = $cnf->getTableDesc($funcTablename);

            $fieldMarkerArray = $this->createFieldMarkerArray($row, $markerPrefix, $suffix);

            foreach ($row as $field => $value) {
                if (
                    in_array($field, $addedFieldArray)
                ) {
                    continue; // do not handle the added fields here. They must be handled with the original field.
                }

                if (gettype($value) == 'NULL') {
                    $value = '';
                }

                $viewField = str_replace('_uid', '', $field);
                $bSkip = false;
                $theMarkerArray = &$rowMarkerArray;
                $fieldId = $mainId . '-' . $viewField;
                $markerKey = $markerPrefix . strtoupper($viewField . $suffix);

                if (isset($tagArray[$markerKey . '_ID'])) {
                    $rowMarkerArray['###' . $markerKey . '_ID###'] = $fieldId;
                }

                $classname = false;

                if (
                    is_array($variantFieldArray) &&
                    is_array($variantMarkerArray) &&
                    in_array($field, $variantFieldArray)
                ) {
                    $classname = 'tx_ttproducts_field_text';
                    $theMarkerArray = &$variantMarkerArray;
                } else {
                    $classname = $this->getFieldClass($field);
                }

                $modifiedRow = [$field => $value];

                if (
                    $classname
                ) {
                    $fieldViewObj = $this->getObj($classname);
                    $modifiedRow =
                        $fieldViewObj->getRowMarkerArray(
                            $funcTablename,
                            $field,
                            $row,
                            $markerKey,
                            $theMarkerArray,
                            $fieldMarkerArray,
                            $tagArray,
                            $theCode,
                            $fieldId,
                            $basketExtra,
                            $basketRecs,
                            $bSkip,
                            $bHtml,
                            $charset,
                            $prefix,
                            $suffix,
                            $imageNum,
                            $imageRenderObj,
                            $linkWrap,
                            $bEnableTaxZero
                        );

                    if (isset($modifiedRow) && !is_array($modifiedRow)) { // if a single value has been returned instead of an array
                        $modifiedRow = [$field => $modifiedRow];
                    } elseif (!isset($modifiedRow)) { // restore former default value
                        $modifiedRow = [$field => $value];
                    }
                } else {
                    switch ($field) {
                        case 'ext':
                            $bSkip = true;
                            break;
                        default:
                            // nothing
                            break;
                    }
                }
                if (!$bSkip) {
                    $this->modifyFieldObject(
                        $row,
                        $modifiedRow,
                        $tableconf,
                        $markerPrefix,
                        $suffix,
                        $fieldMarkerArray,
                        $markerArray,
                        $theMarkerArray
                    );
                }
            }
        } else {
            $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
            $tablename = $cnf->getTableName($funcTablename);
            // 			$tablename = $this->getModelObj()->getTablename();
            $tmpMarkerArray = [];
            $tmpMarkerArray[] = $marker;

            if (isset($GLOBALS['TCA'][$tablename]['columns']) && is_array($GLOBALS['TCA'][$tablename]['columns'])) {
                foreach ($GLOBALS['TCA'][$tablename]['columns'] as $theField => $confArray) {
                    if (
                        $confArray['config']['type'] == 'group' &&
                        isset($confArray['config']['foreign_table'])
                    ) {
                        $foreigntablename = $confArray['config']['foreign_table'];
                        if (
                            $foreigntablename != '' &&
                            !in_array($foreigntablename, $this->tablesWithoutView)
                        ) {
                            $foreignTableViewObj = $tablesObj->get($foreigntablename, true);
                            if (is_object($foreignTableViewObj)) {
                                $foreignMarker = $foreignTableViewObj->getMarker();
                                $tmpMarkerArray[] = $foreignMarker;
                            }
                        }
                    }
                }
            }
            $this->setMarkersEmpty($tagArray, $tmpMarkerArray, $rowMarkerArray);
        }

        $this->getRowMarkerArrayHooks(
            $this,
            $rowMarkerArray,
            $cObjectMarkerArray,
            $funcTablename,
            $row,
            $imageNum,
            $imageRenderObj,
            $forminfoArray,
            $theCode,
            $basketExtra,
            $basketRecs,
            $mainId,
            $linkWrap,
            $bEnableTaxZero
        );
        $markerArray['###CUR_SYM###'] = ' ' . ($bHtml ? htmlentities($conf['currencySymbol'], ENT_QUOTES) : $conf['currencySymbol']);
        $markerArray = array_merge($markerArray, $rowMarkerArray);
    }

    protected function getRowMarkerArrayHooks(
        $pObj,
        &$markerArray,
        &$cObjectMarkerArray,
        $funcTablename,
        $row,
        $imageNum,
        $imageRenderObj,
        &$forminfoArray,
        $theCode,
        $basketExtra,
        $basketRecs,
        $id,
        &$linkWrap,
        $bEnableTaxZero
    ) {
        // Call all getRowMarkerArray hooks at the end of this method
        $marker = $this->getMarker();

        if (
            isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT][$marker]) &&
            is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT][$marker])
        ) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT][$marker] as $classRef) {
                $hookObj = GeneralUtility::makeInstance($classRef);
                if (method_exists($hookObj, 'getRowMarkerArray')) {
                    $hookObj->getRowMarkerArray(
                        $pObj,
                        $markerArray,
                        $cObjectMarkerArray,
                        $funcTablename,
                        $row,
                        $imageNum,
                        $imageRenderObj,
                        $forminfoArray,
                        $theCode,
                        $basketExtra,
                        $basketRecs,
                        $id,
                        $linkWrap,
                        $bEnableTaxZero
                    );
                }
            }
        }
    }
}
