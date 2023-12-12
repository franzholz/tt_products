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
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage tt_products
 */
use JambageCom\Div2007\Utility\ExtensionUtility;
use JambageCom\Div2007\Utility\FlexformUtility;
use JambageCom\Div2007\Utility\SystemCategoryUtility;
use JambageCom\Div2007\Utility\TableUtility;
use JambageCom\TtProducts\Model\Field\FieldInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

class tx_ttproducts_product extends tx_ttproducts_article_base
{
    public $marker = 'PRODUCT';
    public $type = 'product';
    public $piVar = 'product';
    public $articleArray = [];
    protected $tableAlias = 'product';
    protected $allowedTypeArray =
            [
                'accessories',
                'articles',
                'all_downloads',
                'complete_downloads',
                'partial_downloads',
                'products',
                'productsbysystemcategory',
            ];

    /**
     * Getting all tt_products_cat categories into internal array.
     */
    public function init(
        $functablename = 'tt_products'
    ) {
        $result = parent::init($functablename);

        if ($result) {
            $cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
            $tableConfig = [];
            $tableConfig['orderBy'] = $cnfObj->conf['orderBy'] ?? '';

            if (!$tableConfig['orderBy']) {
                $tableConfig['orderBy'] = $this->getOrderBy();
            }

            $tableObj = $this->getTableObj();
            $tableObj->setConfig($tableConfig);
            $tableObj->addDefaultFieldArray(['sorting' => 'sorting']);
        }

        return $result;
    } // init

    public function fixTableConf(
        &$tableConf
    ): void {
        if (
            ExtensionManagementUtility::isLoaded('static_info_tables_taxes')
        ) {
            $eInfo = ExtensionUtility::getExtensionInfo('static_info_tables_taxes');

            if (is_array($eInfo)) {
                $sittVersion = $eInfo['version'];
                if (version_compare($sittVersion, '0.3.0', '>=') && !empty($tableConf['requiredFields'])) {
                    $tableConf['requiredFields'] = str_replace(',tax,', ',tax_id,taxcat_id,', $tableConf['requiredFields']);
                }
            }
        }
    }

    public function getArticleRows(
        $uid,
        $whereArticle = '',
        $orderBy = ''
    ) {
        $rowArray = [];
        if (isset($this->articleArray[$uid])) {
            $rowArray = $this->articleArray[$uid];
        }

        if (
            !$rowArray && $uid ||
            $whereArticle != ''
        ) {
            $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
            $articleObj = $tablesObj->get('tt_products_articles');
            $rowArray = $articleObj->getWhereArray($uid, $whereArticle, $orderBy);

            if (!$whereArticle && empty($orderBy)) {
                $this->articleArray[$uid] = $rowArray;
            }
        }

        return $rowArray;
    }

    public function fillVariantsFromArticles(
        &$row
    ): void {
        $articleRowArray = $this->getArticleRows($row['uid']);
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $articleObj = $tablesObj->get('tt_products_articles');

        if (is_array($articleRowArray) && count($articleRowArray)) {
            // $articleObj->sortArticleRowsByUidArray($row['uid'],$articleRowArray);
            $variantRow =
                $this->variant->getVariantValuesByArticle(
                    $articleRowArray,
                    $row,
                    true
                );
            $selectableFieldArray =
                $this->variant->getSelectableFieldArray();

            foreach ($selectableFieldArray as $field) {
                if (
                    $row[$field] == '' &&
                    !empty($variantRow[$field])
                ) {
                    $row[$field] = $variantRow[$field];
                }
            }
        }
    }

    public function getArticleRowsFromVariant(
        $row,
        $theCode,
        $variant
    ) {
        $articleRowArray = $this->getArticleRows(intval($row['uid']));
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $articleObj = $tablesObj->get('tt_products_articles');

        $result = $this->variant->filterArticleRowsByVariant($row, $variant, $articleRowArray, true);

        return $result;
    }

    public function getMatchingArticleRows(
        $productRow,
        $articleRows
    ) {
        $fieldArray = [];

        $variant = $this->getVariant();
        $variantSeparator = $variant->getSplitSeparator();

        foreach ($variant->conf as $k => $field) {
            if (
                isset($productRow[$field]) &&
                strlen($productRow[$field]) &&
                $field != $variant->additionalField
            ) {
                // 			[\h]+
                $fieldArray[$field] =
                    preg_split(
                        '/[\h]*' . $variantSeparator . '[\h]*/',
                        $productRow[$field],
                        -1,
                        PREG_SPLIT_NO_EMPTY
                    );
            }
        }

        $articleRow = [];

        if (count($fieldArray)) {
            $bFitArticleRowArray = [];
            foreach ($articleRows as $k => $row) {
                $bFits = true;
                foreach ($fieldArray as $field => $valueArray) {
                    $rowFieldArray = [];
                    if (isset($row[$field]) && strlen($row[$field])) {
                        $rowFieldArray =
                            preg_split(
                                '/[\h]*' . $variantSeparator . '[\h]*/',
                                $row[$field],
                                -1,
                                PREG_SPLIT_NO_EMPTY
                            );
                    }

                    $intersectArray = array_intersect($valueArray, $rowFieldArray);
                    if (
                        isset($row[$field]) &&
                        strlen($row[$field]) &&
                        !count($intersectArray) &&
                        $field != 'additional'
                    ) {
                        $bFits = false;
                        break;
                    }
                }
                if ($bFits) {
                    $bFitArticleRowArray[] = $row;
                }
            }
            $articleCount = count($bFitArticleRowArray);
            $articleRow = $bFitArticleRowArray[0];

            if ($articleCount > 1) {
                // many articles fit here. So lets generated a merged article.
                for ($i = 1; $i < $articleCount; $i++) {
                    $this->mergeAttributeFields(
                        $articleRow,
                        $bFitArticleRowArray[$i],
                        false,
                        true,
                        true,
                        '',
                        true
                    );
                }

                if (isset($articleRow['ext'])) {
                    unset($articleRow['ext']);
                }
            }
        }

        return $articleRow;
    }

    public function getArticleRow(
        $row,
        $theCode,
        $bUsePreset = true
    ) {
        $cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
        $fieldArray = $this->variant->getSelectableFieldArray();
        $useArticles = $cnfObj->getUseArticles();

        $articleNo = false;
        $articleRow = [];
        $variantSeparator = $this->getVariant()->getSeparator();
        $regexpDelimiter = tx_ttproducts_model_control::determineRegExpDelimiter($variantSeparator);

        if ($bUsePreset) {
            $presetVariantArray =
                tx_ttproducts_control_product::getPresetVariantArray(
                    $this,
                    $row,
                    $useArticles
                );

            if (empty($presetVariantArray)) {
                $articleNo = tx_ttproducts_control_product::getActiveArticleNo();
            }
        } else {
            $presetVariantArray = [];
        }

        if ($articleNo === false) {
            if (empty($presetVariantArray)) {
                $currentRow = $this->getVariant()->getVariantRow($row);
            } else {
                $currentRow = $this->getVariant()->getVariantRow($row, $presetVariantArray);
            }
        } else {
            $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
            $articleObj = $tablesObj->get('tt_products_articles');

            $articleRow = $articleObj->get($articleNo);
            $variantRow =
                $this->getVariant()->getVariantValuesByArticle(
                    [$articleRow],
                    $row,
                    true
                );
            $currentRow = array_merge($row, $variantRow);
        }

        $whereArray = [];
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $articleObj = $tablesObj->get('tt_products_articles');

        foreach ($fieldArray as $k => $field) {
            $value = trim($currentRow[$field]);

            $regexpValue =
                $GLOBALS['TYPO3_DB']->quoteStr(
                    preg_quote($value),
                    $articleObj->getTablename()
                );

            $regexpValue = preg_replace('/' . preg_quote($variantSeparator) . '/', '|', $regexpValue);

            if ($regexpValue != '') {
                $whereClause =
                    $field . ' REGEXP \'^[[:blank:]]*(' . $regexpValue . ')[[:blank:]]*$\'' .
                    ' OR ' . $field . ' REGEXP \'^[[:blank:]]*(' . $regexpValue . ')[[:blank:]]*[' . $regexpDelimiter . ']\'' .
                    ' OR ' . $field . ' REGEXP \'[' . $regexpDelimiter . '][[:blank:]]*(' . $regexpValue . ')[[:blank:]]*$\'';
                $whereArray[] = $whereClause;
            } elseif ($useArticles == 1) {
                $whereClause = $field . '=\'\'';
                $whereArray[] = $whereClause;
            }
        }

        if (count($whereArray)) {
            $where = '(' . implode($useArticles == '3' ? ' OR ' : ' AND ', $whereArray) . ')';
        } else {
            $where = '';
        }
        $articleRows = $this->getArticleRows(intval($row['uid']), $where);

        if (is_array($articleRows) && count($articleRows)) {
            $articleRow = $this->getMatchingArticleRows($currentRow, $articleRows);
            $articleConf = $cnfObj->getTableConf('tt_products_articles', $theCode);

            if (
                $theCode &&
                isset($articleConf['fieldIndex.']) &&
                is_array($articleConf['fieldIndex.']) &&
                isset($articleConf['fieldIndex.']['image.']) &&
                is_array($articleConf['fieldIndex.']['image.'])
            ) {
                $prodImageArray = GeneralUtility::trimExplode(',', $row['image']);
                $artImageArray = GeneralUtility::trimExplode(',', $articleRow['image']);
                $tmpDestArray = $prodImageArray;
                foreach ($articleConf['fieldIndex.']['image.'] as $kImage => $vImage) {
                    $tmpDestArray[$vImage - 1] = $artImageArray[$kImage - 1];
                }
                $articleRow['image'] = implode(',', $tmpDestArray);
            }
        }

        return $articleRow;
    }

    public function getRowFromExt(
        $funcTablename,
        $row,
        $useArticles
    ) {
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $priceRow = $row;

        if (
            in_array($useArticles, [1, 3]) &&
            $funcTablename == 'tt_products' &&
            isset($row['ext']['tt_products_articles']) &&
            is_array($row['ext']['tt_products_articles'])
        ) {
            $articleObj = $tablesObj->get('tt_products_articles');
            reset($row['ext']['tt_products_articles']);
            $articleInfo = current($row['ext']['tt_products_articles']);
            $articleRowArray = [];

            foreach ($row['ext']['tt_products_articles'] as $extRow) {
                if (!isset($extRow['uid'])) {
                    throw new RuntimeException('Invalid article row for product uid=' . $row['uid'] . ' .', 50006);
                }
                $articleRow = false;
                $articleUid = $extRow['uid'];

                if (isset($articleUid)) {
                    $articleRow = $articleObj->get($articleUid);

                    $this->mergeAttributeFields(
                        $priceRow,
                        $articleRow,
                        false,
                        true,
                        true,
                        '',
                        false
                    );
                }

                if ($articleRow) {
                    $priceRow['weight'] = (round($articleRow['weight'], 16) ? $articleRow['weight'] : $row['weight']);
                    $priceRow['inStock'] = $articleRow['inStock'];
                    $articleRowArray[] = $articleRow;
                }
            }
            $priceRow['ext']['tt_products_articles'] = $articleRowArray;
        }

        return $priceRow;
    }

    public function getArticleRowFromExt(
        $row
    ) {
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');

        $result = false;
        $extArray = $row['ext'];

        if (
            isset($extArray) &&
            is_array($extArray) &&
            isset($extArray['tt_products_articles']) &&
            is_array($extArray['tt_products_articles']) &&
            isset($extArray['tt_products_articles']['0']) &&
            is_array($extArray['tt_products_articles']['0'])
        ) {
            $articleUid = $extArray['tt_products_articles']['0']['uid'];
            $articleTable = $tablesObj->get('tt_products_articles', false);
            $result = $articleTable->get($articleUid);
        }

        return $result;
    }

    public function getSystemCategories($uid, $orderBy)
    {
        $funcTablename = $this->getFuncTablename();
        $fieldName = 'syscat';

        $systemCategoryTablename = 'sys_category';

        $uidArray = [];
        if (
            MathUtility::canBeInterpretedAsInteger($uid)
        ) {
            $uidArray = [$uid];
        } elseif (is_array($uid)) {
            $uidArray = $uid;
        }

        $categoryUids =
            SystemCategoryUtility::getUids(
                $funcTablename,
                $fieldName,
                $uidArray
            );

        $productUids =
            SystemCategoryUtility::getForeignUids(
                $funcTablename,
                $fieldName,
                $categoryUids,
                $orderBy
            );

        $productUids = array_diff($productUids, $uidArray);

        return $productUids;
    }

    /* types:
        'accessories' ... accessory products
        'articles' ... related articles
        'products' ... related products
        returns the uids of the related products or articles
    */
    public function getRelated(
        &$parentFuncTablename,
        &$parentRows,
        $multiOrderArray,
        $uid,
        $type,
        $orderBy = ''
    ) {
        $rcArray = [];
        $rowArray = [];
        $parentFuncTablename = '';

        if (
            in_array($type, $this->allowedTypeArray)
        ) {
            if ($type == 'articles') {
                $relatedArticles = $this->getArticleRows($uid, '', $orderBy);

                if (is_array($relatedArticles) && $relatedArticles) {
                    $rowArray = [];
                    foreach ($relatedArticles as $k => $articleRow) {
                        $rcArray[] = $articleRow['uid'];
                    }
                }
            } else {
                if ($uid) {
                    if ($type == 'productsbysystemcategory') {
                        $rcArray = $this->getSystemCategories($uid, $orderBy);
                    } else {
                        $mmTable = [
                            'accessories' => ['table' => 'tt_products_accessory_products_products_mm'],
                            'all_downloads' => ['table' => 'tt_products_products_mm_downloads'],
                            'complete_downloads' => ['table' => 'tt_products_products_mm_downloads'],
                            'partial_downloads' => ['table' => 'tt_products_products_mm_downloads'],
                            'products' => ['table' => 'tt_products_related_products_products_mm'],
                        ];

                        $where_clause = '';
                        if (
                            MathUtility::canBeInterpretedAsInteger($uid)
                        ) {
                            $where_clause = 'uid_local = ' . intval($uid);
                        } elseif (is_array($uid)) {
                            $where_clause = 'uid_local IN (' . implode(',', $uid) . ')';
                        }

                        $falUidArray = [];
                        $downloadUidArray = [];
                        if (
                            isset($multiOrderArray) &&
                            is_array($multiOrderArray) &&
                            count($multiOrderArray) &&
                            !empty($multiOrderArray[0])
                        ) {
                            $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
                            $orderObj = $tablesObj->get('sys_products_orders');
                            $currentOrderUid = 0;
                            foreach ($multiOrderArray as $orderRow) {
                                if ($orderRow['product_uid'] == $uid) {
                                    $falUid = $orderObj->getFal(
                                        $orderedDownloadUid,
                                        0,
                                        $orderRow
                                    );

                                    $falUidArray[] = $falUid;
                                    $downloadUidArray[] = $orderedDownloadUid;
                                }
                            }
                        }
                        $downloadUidArray = array_unique($downloadUidArray);
                        $falUidArray = array_unique($falUidArray);
                        $downloadUidArray = $GLOBALS['TYPO3_DB']->cleanIntArray($downloadUidArray);
                        $falUidArray = $GLOBALS['TYPO3_DB']->cleanIntArray($falUidArray);

                        if (is_array($downloadUidArray) && count($downloadUidArray)) {
                            $where_clause .= ' AND uid_foreign IN(' . implode(',', $downloadUidArray) . ')';
                        }

                        $rowArray =
                            $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
                                '*',
                                $mmTable[$type]['table'],
                                $where_clause,
                                '',
                                'sorting_foreign'
                            );
                    }
                }

                if (
                    isset($rowArray) &&
                    is_array($rowArray) &&
                    !empty($rowArray)
                ) {
                    if (
                        $type == 'all_downloads' ||
                        $type == 'complete_downloads' ||
                        $type == 'partial_downloads'
                    ) {
                        $uidArray = [];
                        foreach ($rowArray as $k => $row) {
                            $uidArray[] = $row['uid_foreign'];
                        }
                        $where_clause = 'uid IN (' . implode(',', $uidArray) . ')';

                        if (
                            $type == 'complete_downloads'
                        ) {
                            $where_clause .= 'AND edition=0';
                        } elseif (
                            $type == 'partial_downloads'
                        ) {
                            $where_clause .= 'AND edition=1';
                        }

                        $parentFuncTablename = $tablename = 'tt_products_downloads';
                        $where_clause .=
                            TableUtility::enableFields(
                                $tablename
                            );

                        $downloadRowArray =
                            $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
                                '*',
                                $tablename,
                                $where_clause,
                                '',
                                $orderBy
                            );
                        $fileTablename = 'sys_file_reference';
                        $fileField = 'file_uid';

                        $enable_where_clause = TableUtility::enableFields($fileTablename);

                        if (
                            isset($downloadRowArray) &&
                            is_array($downloadRowArray)
                        ) {
                            $whereUid = '';
                            if (
                                isset($falUidArray) &&
                                is_array($falUidArray) &&
                                count($falUidArray)
                            ) {
                                $whereUid = ' AND uid IN(' . implode(',', $falUidArray) . ')';
                            }

                            foreach ($downloadRowArray as $downloadRow) {
                                $uid = $downloadRow['uid'];
                                if ($downloadRow[$fileField]) {
                                    $where_clause = 'uid_foreign=' . intval($uid) . ' AND tablenames="' . $tablename . '" AND fieldname="' . $fileField . '"';

                                    $where_clause .= $enable_where_clause . $whereUid;
                                    $fileRowArray =
                                        $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
                                            'uid,uid_local,uid_foreign',
                                            $fileTablename,
                                            $where_clause,
                                            '',
                                            'sorting_foreign', // 'sorting'
                                            '',
                                            'uid_local'
                                        );

                                    if (
                                        isset($fileRowArray) &&
                                        is_array($fileRowArray)
                                    ) {
                                        $downloadRow['childs'] = [];
                                        foreach ($fileRowArray as $fileRow) {
                                            $childUid = $fileRow['uid'];
                                            $downloadRow['childs'][] = $childUid;
                                            $rcArray[] = $childUid;
                                        }
                                    }
                                    $parentRows[$uid] = $downloadRow;
                                }
                            }
                        }
                    } else {
                        foreach ($rowArray as $k => $row) {
                            $rcArray[] = $row['uid_foreign'];
                        }
                    }
                }
            }
        }

        return $rcArray;
    }

    // returns the Path of all categories above, separated by '/'
    public function getPath($uid)
    {
        $rc = '';

        return $rc;
    }

    /**
     * Reduces the instock value of the orderRecord with the sold items and returns the result.
     */
    public function reduceInStockItems(
        $itemArray,
        $useArticles
    ) {
        $instockTableArray = [];
        $cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');

        $instockField = $cnfObj->getTableDesc($this->getTableObj()->name, 'inStock');
        $instockField = ($instockField ?: 'inStock');
        if (
            $this->getTableObj()->name == 'tt_products' ||
            is_array($GLOBALS['TCA'][$this->getTableObj()->name]['columns']['inStock'])
        ) {
            // Reduce inStock
            if ($useArticles == 1) {
                // loop over all items in the basket indexed by a sorting text
                foreach ($itemArray as $sort => $actItemArray) {
                    foreach ($actItemArray as $k1 => $actItem) {
                        $row = $this->getArticleRow($actItem['rec'], $theCode);
                        if ($row) {
                            $tt_products_articles = $tablesObj->get('tt_products_articles');
                            $tt_products_articles->reduceInStock($row['uid'], $actItem['count']);
                            $instockTableArray['tt_products_articles'][$row['uid'] . ',' . $row['itemnumber'] . ',' . $row['title']] = intval($row[$instockField] - $actItem['count']);
                        }
                    }
                }
            }
            // loop over all items in the basket indexed by a sorting text
            foreach ($itemArray as $sort => $actItemArray) {
                foreach ($actItemArray as $k1 => $actItem) {
                    $row = $actItem['rec'];
                    if (!$this->hasAdditional($row, 'alwaysInStock')) {
                        $this->reduceInStock($row['uid'], $actItem['count']);
                        $instockTableArray['tt_products'][$row['uid'] . ',' . $row['itemnumber'] . ',' . $row['title']] = intval($row[$instockField] - $actItem['count']);
                    }
                }
            }
        }

        return $instockTableArray;
    }

    /**
     * Returns true if the item has the $check value checked.
     */
    public function hasAdditional(&$row, $check)
    {
        $hasAdditional = false;
        if (isset($row['additional'])) {
            $additional = GeneralUtility::xml2array($row['additional']);
            $hasAdditional = FlexformUtility::get($additional, $check);
        }

        return $hasAdditional;
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

        // Call all addWhere hooks for categories at the end of this method
        if (
            isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['prodCategory']) &&
            is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['prodCategory'])
        ) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['prodCategory'] as $classRef) {
                $hookObj = GeneralUtility::makeInstance($classRef);
                if (method_exists($hookObj, 'addWhereCat')) {
                    $operator = '';
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
        } else {
            $catArray = [];
            $categoryAndArray = [];

            if ($cat || $cat == '0') {
                $catArray = GeneralUtility::intExplode(',', $cat);
            }
            if ($categoryAnd != '') {
                $categoryAndArray = GeneralUtility::intExplode(',', $categoryAnd);
            }
            $newcatArray = array_merge($categoryAndArray, $catArray);
            $newcatArray = array_unique($newcatArray);

            if (count($newcatArray)) {
                // 				$newcats = $newcatArray['0']; // only one category can be searched for
                $newcats = implode(',', $newcatArray);
                $where = 'category IN (' . $newcats . ')';

                if ($bLeadingOperator) {
                    $where = ' AND ( ' . $where . ')';
                }
            }
        }

        return $where;
    }

    public function addConfCat(
        $catObject,
        &$selectConf,
        $aliasArray
    ) {
        $tableNameArray = [];

        // Call all addWhere hooks for categories at the end of this method
        if (
            isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['prodCategory']) &&
            is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['prodCategory'])
        ) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['prodCategory'] as $classRef) {
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

    public function addselectConfCat(
        $catObject,
        $cat,
        &$selectConf
    ) {
        $tableNameArray = [];

        // Call all addWhere hooks for categories at the end of this method
        if (
            isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['prodCategory']) &&
            is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['prodCategory'])
        ) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['prodCategory'] as $classRef) {
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

    public function getPageUidsCat($cat)
    {
        $uidArray = [];

        // Call all addWhere hooks for categories at the end of this method
        if (
            isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['prodCategory']) &&
            is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['prodCategory'])
        ) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['prodCategory'] as $classRef) {
                $hookObj = GeneralUtility::makeInstance($classRef);
                if (method_exists($hookObj, 'getPageUidsCat')) {
                    $hookObj->getPageUidsCat($this, $cat, $uidArray);
                }
            }
        }
        $uidArray = array_unique($uidArray);

        return implode(',', $uidArray);
    }

    public function getProductField(
        &$row,
        $field
    ) {
        return $row[$field];
    }

    public function getRequiredFields(
        $theCode = ''
    ) {
        $tableConf = $this->getTableConf($theCode);
        $cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
        $conf = $cnfObj->getConf();

        if (!empty($tableConf['requiredFields'])) {
            $requiredFields = $tableConf['requiredFields'];
        } else {
            $requiredFields = 'uid,pid,category,price,price2,discount,discount_disable,directcost,tax,deposit';
        }
        $instockField = $cnfObj->getTableDesc($this->getFuncTablename(), 'inStock');
        if ($instockField && empty($conf['alwaysInStock'])) {
            $requiredFields = $requiredFields . ',' . $instockField;
        }
        $rc = $requiredFields;

        return $rc;
    }

    public function getTotalDiscount(
        &$row,
        $pid = 0
    ) {
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
        $conf = $cnfObj->getConf();

        if (
            $this->getFuncTablename() == 'tt_products' &&
            isset($this->conf['discountFieldMode']) &&
            in_array(
                $conf['discountFieldMode'],
                ['1', '2']
            )
        ) {
            $categoryfunctablename = 'tt_products_cat';
            $categoryTable = $tablesObj->get($categoryfunctablename, false);
            $discount = 0;

            switch ($conf['discountFieldMode']) {
                case '1':
                    $catArray = $categoryTable->getCategoryArray($row, 'sorting');
                    $discount = $categoryTable->getMaxDiscount(
                        $row['discount'],
                        $row['discount_disable'],
                        $catArray,
                        $pid
                    );
                    break;
                case '2':
                    $discount = $categoryTable->getFirstDiscount(
                        $row['discount'],
                        $row['discount_disable'],
                        $row['category'],
                        $pid
                    );
                    break;
            }

            $discountField = FieldInterface::DISCOUNT;
            $row[$discountField] = $discount;
        }
    }
}
