<?php

declare(strict_types=1);

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
 * main class for eID AJAX function to change the values of records for the
 * variant select box
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage tt_products
 */
use Psr\Http\Message\ServerRequestInterface;

use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

use JambageCom\Div2007\Utility\HtmlUtility;

use JambageCom\TtProducts\Api\Localization;
use JambageCom\TtProducts\Api\BasketApi;
use JambageCom\TtProducts\Api\VariantApi;
use JambageCom\TtProducts\Api\EditVariantApi;
use JambageCom\TtProducts\Model\Field\FieldInterface;
use JambageCom\TtProducts\Api\ParameterApi;
use JambageCom\TtProducts\Api\ControlApi;
use JambageCom\TtProducts\Controller\Base\Creator;

class tx_ttproducts_db implements SingletonInterface
{
    protected $extKey = TT_PRODUCTS_EXT;	// The extension key.
    protected $conf;			// configuration from template
    protected $config;
    protected $ajax;
    protected $LLkey;
    protected $cObj;
    private FileRepository $fileRepository;
    public $LOCAL_LANG = [];		// Local Language content
    public $LOCAL_LANG_charset = [];	// Local Language content charset for individual labels (overriding)
    public $LOCAL_LANG_loaded = 0;		// Flag that tells if the locallang file has been fetch (or tried to be fetched) already.

    public function init(
        array &$conf,
        array &$config,
        $ajaxObj,
        $pObj,
        $cObj,
        &$errorCode
    ): bool {
        $this->conf = $conf;

        if (isset($ajaxObj) && is_object($ajaxObj)) {
            $this->ajax = $ajaxObj;
            $taxajax = $this->ajax->getTaxajax();

            $taxajax->registerFunction([TT_PRODUCTS_EXT . '_fetchRow', $this, 'fetchRow']);
            $taxajax->registerFunction([TT_PRODUCTS_EXT . '_commands', $this, 'commands']);
            $taxajax->registerFunction([TT_PRODUCTS_EXT . '_showArticle', $this, 'showArticle']);
        }

        if (
            is_object($cObj)
        ) {
            $this->cObj = $cObj;
        } else {
            $this->cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class); // Local cObj.
        }

        $controlCreatorObj = GeneralUtility::makeInstance(Creator::class);

        // TODO: $recs befüllen.
        if (
            ($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            &&
            ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend()
        ) {
            \tx_ttproducts_control_basket::storeNewRecs($conf['transmissionSecurity']);
            $recs = tx_ttproducts_control_basket::getStoredRecs();
        }
        if (empty($recs)) {
            $recs = [];
        }

        $result =
            $controlCreatorObj->init(
                $conf,
                $config,
                $pObj,
                $this->cObj,
                $ajaxObj,
                $errorCode,
                $recs
            );

        if (!$result) {
            return false;
        }

        $this->fileRepository = $controlCreatorObj->getFileRepository();

        $modelCreatorObj = GeneralUtility::makeInstance('tx_ttproducts_model_creator');
        $modelCreatorObj->init($conf, $config, $this->cObj);

        return true;
    }

    public function main(): void
    {
    }

    public function printContent(): void
    {
    }

    public function fetchRow($data)
    {
        $result = '';
        $view = '';
        $rowArray = [];
        $variantArray = [];
        $theCode = 'ALL';
        $cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
        $languageObj = GeneralUtility::makeInstance(Localization::class);
        $roundFormat = tx_ttproducts_control_basket::getRoundFormat();
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');
        $basketApi = GeneralUtility::makeInstance(BasketApi::class);

        $basketExtra = $basketApi->getBasketExtra();
        $basketRecs = tx_ttproducts_control_basket::getRecs();
        $funcTablename = tx_ttproducts_control_basket::getFuncTablename();
        $useFal = true;

        // price
        $priceObj = GeneralUtility::makeInstance('tx_ttproducts_field_price');
        $priceObj->init(
            $this->cObj,
            $this->conf
        );
        $priceViewObj = GeneralUtility::makeInstance('tx_ttproducts_field_price_view');
        $priceViewObj->init(
            $priceObj
        );

        // We put our incomming data to the regular piVars
        $itemTable = $tablesObj->get('tt_products', false);
        $externalRowArray = [];
        $variantApi = GeneralUtility::makeInstance(VariantApi::class);
        $editVariantApi = GeneralUtility::makeInstance(EditVariantApi::class);
        $variantSeparator = $variantApi->getSplitSeparator();

        if (is_array($data)) {
            $validEditVariant = true;
            $useArticles = $cnfObj->getUseArticles();
            $variantFieldArray = $variantApi->getSelectableFieldArray();

            foreach ($data as $k => $dataRow) {
                if ($k == 'view') {
                    $view = $dataRow;
                    $theCode = strtoupper($view);
                } elseif (is_array($dataRow)) {
                    $table = $k;
                    $uid = intval($dataRow['uid']);

                    if ($uid) {
                        $row = $itemTable->get($uid);

                        if ($row) {
                            if ($useArticles == 3) {
                                $itemTable->fillVariantsFromArticles($row);
                                $articleRows = $itemTable->getArticleRows(intval($row['uid']));
                            }
                            $rowArray[$table] = $row;

                            foreach ($dataRow as $field => $v) {
                                $field = str_replace('-', '_', $field);

                                if (isset($row[$field]) && strlen($row[$field])) {
                                    if ($field != 'uid') {
                                        $variantArray[] = $field;
                                        $variantValues =
                                            preg_split(
                                                '/[\h]*' . $variantSeparator . '[\h]*/',
                                                $row[$field],
                                                -1,
                                                PREG_SPLIT_NO_EMPTY
                                            );
                                        $theValue = $variantValues[$v];
                                        $rowArray[$table][$field] = $theValue;
                                    }
                                } elseif (str_starts_with($field, 'edit_')) {
                                    $rowArray[$table][$field] = $v;
                                }
                            }
                            $modifiedRow = $rowArray[$table];
                            $editConfig = $editVariantApi->getValidConfig($modifiedRow);
                            $editVariantFieldArray = $editVariantApi->getFieldArray();

                            if (
                                isset($editVariantFieldArray) && is_array($editVariantFieldArray)
                            ) {
                                $storeRowArray = [];
                                $storedRecs = $this->ajax->getStoredRecs();
                                if (isset($storedRecs) && is_array($storedRecs)) {
                                    $storeRowArray = $storedRecs;
                                }

                                foreach ($editVariantFieldArray as $field) {
                                    if (isset($rowArray[$table][$field])) {
                                        $row[$field] = $storeRowArray[$table][$uid][$field] = $rowArray[$table][$field];
                                    }
                                }

                                if (is_array($storeRowArray) && count($storeRowArray)) {
                                    $this->ajax->setStoredRecs($storeRowArray);
                                }
                            }

                            if ($editConfig && is_array($editConfig)) {
                                $validEditVariant =
                                    $editVariantApi->checkValid(
                                        $editConfig,
                                        $modifiedRow
                                    );
                            }

                            if ($validEditVariant !== true) {
                                break;
                            }
                            $storeRowArray = [];
                            $storedRecs = tx_ttproducts_control_basket::getStoredVariantRecs();
                            if (isset($storedRecs) && is_array($storedRecs)) {
                                $storeRowArray = $storedRecs;
                            }

                            foreach ($variantFieldArray as $field) {
                                if (isset($rowArray[$table][$field])) {
                                    $storeRowArray[$table][$uid][$field] = $rowArray[$table][$field];
                                }
                            }
                            tx_ttproducts_control_basket::setStoredVariantRecs($storeRowArray);

                            $allVariants =
                                $basketObj->getAllVariants(
                                    $funcTablename,
                                    $row,
                                    $modifiedRow
                                );
                            $currRow =
                                $basketObj->getItemRow(
                                    $row,
                                    $allVariants,
                                    $useArticles,
                                    $funcTablename,
                                    [],
                                    true
                                );
                            $basketExt1 = tx_ttproducts_control_basket::generatedBasketExtFromRow(
                                $currRow,
                                '1'
                            );
                            $taxInfoArray = [];
                            $tax = 0.0;

                            $itemArray =
                                $basketObj->getItemArrayFromRow(
                                    $tax,
                                    $taxInfoArray,
                                    $currRow,
                                    $basketExt1,
                                    $basketExtra,
                                    $basketRecs,
                                    $funcTablename,
                                    'useExt',
                                    $externalRowArray
                                );
                            $basketObj->setMaxTax($modifiedRow['tax']);
                            $recalculateItems = true;
                            $calculatedArray = [];
                            $basketObj->calculate(
                                $itemArray,
                                $calculatedArray,
                                $basketExt1,
                                $basketExtra,
                                $basketRecs,
                                $funcTablename,
                                $useArticles,
                                $tax,
                                $roundFormat,
                                $recalculateItems
                            ); // get the calculated arrays

                            $modifiedRow =
                                $basketObj->getMergedRowFromItemArray(
                                    $itemArray,
                                    $basketExtra
                                );

                            $totalDiscountField = FieldInterface::DISCOUNT;
                            $itemTable->getTotalDiscount($modifiedRow);

                            if ($useArticles == 1) {
                                $rowArticle =
                                    $itemTable->getArticleRow(
                                        $modifiedRow, // $rowArray[$table],
                                        $theCode
                                    );
                            } elseif ($useArticles == 3) {
                                $rowArticle =
                                    $itemTable->getMatchingArticleRows(
                                        $modifiedRow,
                                        $articleRows
                                    );
                            }

                            if (
                                !$useFal &&
                                isset($rowArticle) &&
                                is_array($rowArticle)
                            ) {
                                if (
                                    isset($rowArticle['image']) &&
                                    !$rowArticle['image'] &&
                                    isset($rowArray[$table]['image'])
                                ) {
                                    $rowArticle['image'] = $rowArray[$table]['image'];
                                    $modifiedRow['image'] = $rowArticle['image'];
                                }

                                $articleConf =
                                    $cnfObj->getTableConf('tt_products_articles', $theCode);

                                if (
                                    isset($articleConf['fieldIndex.']) && is_array($articleConf['fieldIndex.']) &&
                                    isset($articleConf['fieldIndex.']['image.']) && is_array($articleConf['fieldIndex.']['image.'])
                                ) {
                                    $prodImageArray =
                                        GeneralUtility::trimExplode(',', $rowArray[$table]['image']);
                                    $artImageArray = GeneralUtility::trimExplode(',', $rowArticle['image']);
                                    $tmpDestArray = $prodImageArray;
                                    foreach ($articleConf['fieldIndex.']['image.'] as $kImage => $vImage) {
                                        $tmpDestArray[$vImage - 1] = $artImageArray[$kImage - 1];
                                    }
                                    $modifiedRow['image'] = implode(',', $tmpDestArray);
                                }
                            }
                            $itemTable->getTableObj()->substituteMarkerArray(
                                $modifiedRow
                            );
                            $rowArray[$table] = $modifiedRow;
                        } // if ($row ...)
                    }
                } // foreach ($data
                if ($validEditVariant !== true) {
                    break;
                }
            }

            $this->ajax->setConf($data['conf'] ?? []);
        }

        if ($validEditVariant === true) {
            $result = $this->generateResponse($view, $rowArray, $rowArticle, $variantArray);
        } else {
            $result = $this->generateErrorResponse($uid, $validEditVariant);
        }

        return $result;
    }

    protected function generateErrorResponse($uid, $editErrorArray)
    {
        $parameterApi = GeneralUtility::makeInstance(ParameterApi::class);
        $useXHTML = HtmlUtility::useXHTML();

        // Instantiate the tx_xajax_response object
        $objResponse = new tx_taxajax_response($this->ajax->taxajax->getCharEncoding(), true);

        $editVariant = key($editErrorArray);
        $errorText = current($editErrorArray);
        $fieldname = str_replace('edit_', '', $editVariant);
        $errorId = $parameterApi->getBasketInputErrorIdPrefix() . '-' . $uid;
        $objResponse->addAssign($errorId, 'innerHTML', $errorText);
        $basketIntoId = $parameterApi->getBasketIntoIdPrefix() . '-' . $uid;
        $disabledText = ($useXHTML ? 'disabled' : '');
        $objResponse->addPrepend($basketIntoId, 'disabled', $disabledText);
        $result = $objResponse->getXML();

        // return the XML response generated by the tx_taxajax_response object
        return $result;
    }

    protected function generateResponse(
        $view,
        $rowArray,
        $rowArticle,
        $variantArray
    ) {
        $cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
        $config = $cnfObj->getConfig();
        $conf = $cnfObj->getConf();
        $parameterApi = GeneralUtility::makeInstance(ParameterApi::class);

        $useXHTML = HtmlUtility::useXHTML();
        $useFal = true;
        $basketApi = GeneralUtility::makeInstance(BasketApi::class);
        $theCode = strtoupper($view);
        $languageObj = GeneralUtility::makeInstance(Localization::class);
        $imageObj = GeneralUtility::makeInstance('tx_ttproducts_field_image');
        $imageViewObj = GeneralUtility::makeInstance('tx_ttproducts_field_image_view');

        $imageObj->init($this->fileRepository);
        $imageViewObj->init($imageObj);

        $priceObj = GeneralUtility::makeInstance('tx_ttproducts_field_price');
        // price
        $priceViewObj = GeneralUtility::makeInstance('tx_ttproducts_field_price_view');
        $priceFieldArray = $priceViewObj->getConvertedPriceFieldArray('price');

        $tableObjArray = [];
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');

        // Instantiate the tx_xajax_response object
        $objResponse = new tx_taxajax_response($this->ajax->getTaxajax()->getCharEncoding(), true);
        $articleTcaColumns = $GLOBALS['TCA']['tt_products_articles']['columns'];

        foreach ($rowArray as $funcTablename => $row) { // tt-products-list-1-size
            if (
                !isset($tableObjArray[$funcTablename]) ||
                !is_object($tableObjArray[$funcTablename])
            ) {
                $suffix = '-from-tt-products-articles';
            } else {
                $suffix = '';
            }

            $itemTableView = $tablesObj->get($funcTablename, true);
            $itemTable = $itemTableView->getModelObj();
            $tablename = $itemTable->getTablename();
            $tableconf = $itemTable->getTableConf($theCode);

            $useCategories = true;

            if ($useCategories) {
                $pidListObj = GeneralUtility::makeInstance('tx_ttproducts_pid_list');
                $pidListObj->applyRecursive($config['recursive'], $config['pid_list'], true);

                $categoryFuncTablename = 'tt_products_cat';
                $categoryTableView = $tablesObj->get($categoryFuncTablename, true);
                $categoryTable = $categoryTableView->getModelObj();
                $piVars = $parameterApi->getPiVars();
                $categoryPivar = $parameterApi->getPiVar($categoryFuncTablename);

                $currentCat =
                    $categoryTable->getParamDefault(
                        $theCode,
                        $piVars[$categoryPivar] ?? ''
                    );
                $rootCat = $categoryTable->getRootCat();
                $relatedArray =
                    $categoryTable->getRelated(
                        $rootCat,
                        $currentCat,
                        $pidListObj->getPidlist()
                    );	// read only related categories;
                $excludeCat = 0;
                $categoryArray =
                    $categoryTable->getRelationArray(
                        $relatedArray,
                        $excludeCat,
                        $rootCat,
                        implode(',', array_keys($relatedArray))
                    );
            }

            $jsTableNamesId = str_replace('_', '-', $funcTablename) . $suffix;
            $uid = $row['uid'];
            $errorId = $parameterApi->getBasketInputErrorIdPrefix() . '-' . $uid;
            $objResponse->addAssign($errorId, 'innerHTML', '');
            $basketIntoId = $parameterApi->getBasketIntoIdPrefix() . '-' . $uid;
            $objResponse->addClear($basketIntoId, 'disabled');

            $markerKey = $itemTableView->getMarkerKey('');
            $markerPrefix = $markerKey . '_';
            $suffix = '';

            $fieldMarkerArray =
                $itemTableView->createFieldMarkerArray(
                    $row,
                    $markerPrefix,
                    $suffix
                );

            $modifiedRow = [];

            foreach ($row as $field => $v) {
                if (
                    empty($field) ||
                    (
                        !isset($articleTcaColumns[$field]) &&
                        !in_array($field, $priceFieldArray)
                    ) ||
                    $field == 'additional'
                ) {
                    continue 2;
                }
                $fieldId = $field;
                $bSkip = false;

                if (
                    ($field == 'title') ||
                    ($field == 'subtitle') ||
                    ($field == 'note') ||
                    ($field == 'note2')
                ) {
                    if (
                        ($field == 'note') ||
                        ($field == 'note2')
                    ) {
                        $noteObj = GeneralUtility::makeInstance('tx_ttproducts_field_note_view');
                        $class = $itemTable->getFieldClass($field);

                        if (
                            $class
                        ) {
                            $tmpArray = [];
                            $tmp = '';
                            $fieldViewObj = $itemTableView->getObj($class);
                            $linkWrap = false;
                            $modifiedValue =
                                $fieldViewObj->getRowMarkerArray(
                                    $funcTablename,
                                    $field,
                                    $row,
                                    $tmp,
                                    $tmpArray,
                                    $tmpArray,
                                    $tmpArray,
                                    $theCode,
                                    '',
                                    $basketApi->getBasketExtra(),
                                    tx_ttproducts_control_basket::getRecs(),
                                    $bSkip,
                                    true,
                                    '',
                                    '',
                                    '',
                                    0,
                                    '',
                                    $linkWrap,
                                    false
                                );
                            $v = $modifiedValue;
                        }
                    }
                }

                if (!in_array($field, $variantArray)) {
                    if (($position = strpos($field, '_uid')) !== false) {
                        if (!$useFal) {
                            continue 2;
                        }
                        $fieldId = substr($field, 0, $position);
                    } else {
                        if (
                            in_array($field, ['image', 'smallimage']) &&
                            $useFal
                        ) {
                            continue 2;
                        }
                    }

                    $tagId = $jsTableNamesId . '-' . $view . '-' . $uid . '-' . $field;
                    switch ($field) {
                        case 'image_uid':
                        case 'smallimage_uid':
                            $imageRow = $row;
                            $imageTablename = $tablename;
                            if (
                                isset($rowArticle) &&
                                is_array($rowArticle) &&
                                isset($rowArticle[$field]) &&
                                $rowArticle[$field]
                            ) {
                                $imageTablename = 'tt_products_articles';
                                $imageRow[$field] = $rowArticle[$field];
                                $imageRow['uid'] = $rowArticle['uid'];
                                $imageRow['pid'] = $rowArticle['pid'];
                            }

                            $imageRenderObj = 'image';
                            if ($theCode == 'LIST' || $theCode == 'SEARCH') {
                                $imageRenderObj = 'listImage';

                                if (
                                    isset($categoryArray) &&
                                    is_array($categoryArray) &&
                                    !isset($categoryArray[$currentCat]) &&
                                    isset($conf['listImageRoot.'])
                                ) {
                                    $imageRenderObj = 'listImageRoot';
                                }
                            } elseif (
                                $theCode == 'SINGLE' &&
                                str_contains($field, 'smallimage')
                            ) {
                                $imageRenderObj = 'smallImage';
                            }
                            $imageArray =
                                $imageObj->getFileArray(
                                    $imageTablename,
                                    $imageRow,
                                    $field
                                );

                            if (
                                is_array($imageArray) &&
                                count($imageArray)
                            ) {
                                $dirname = '';
                                if ($fieldId == $field) {
                                    $dirname = $imageObj->getDirname($imageRow);
                                }
                                $theImgDAM = [];
                                $markerArray = [];
                                $linkWrap = '';

                                $mediaNum = $imageObj->getMediaNum(
                                    'tt_products_articles',
                                    'image',
                                    $theCode
                                );
                                $specialConf = [];
                                $imgCodeArray = $imageViewObj->getCodeMarkerArray(
                                    'tt_products_articles',
                                    'ARTICLE_IMAGE',
                                    $theCode,
                                    $imageRow,
                                    $imageArray,
                                    $fieldMarkerArray,
                                    $dirname,
                                    $imageRenderObj,
                                    $linkWrap,
                                    $markerArray,
                                    $theImgDAM,
                                    $specialConf,
                                    $mediaNum
                                );

                                $v = $imgCodeArray;
                            } else {
                                $v = '';
                                continue 2; // do not delete the current image
                            }
                            break;

                        case 'inStock':
                            $basketIntoPrefix = $parameterApi->getBasketIntoIdPrefix();
                            if ($v > 0) {
                                $objResponse->addClear(
                                    $basketIntoPrefix . '-' . $uid,
                                    'disabled'
                                );
                            } else {
                                $objResponse->addAssign(
                                    $basketIntoPrefix . '-' . $uid,
                                    'disabled',
                                    'disabled'
                                );
                            }
                            $objResponse->addAssign(
                                'in-stock-id-' . $uid,
                                'innerHTML',
                                $languageObj->getLabel(
                                    $v > 0 ? 'in_stock' : 'not_in_stock'
                                )
                            );
                            break;

                        default:
                            // nothing
                            break;
                    }

                    if (in_array($field, $priceFieldArray)) {
                        $v = $priceViewObj->priceFormat($v);
                    }

                    $modifiedRow[$fieldId] = $v;
                }
            }

            $markerArray = [];
            $theMarkerArray = [];
            $newRow = $itemTableView->modifyFieldObject(
                $theMarkerArray,
                $row,
                $modifiedRow,
                $tableconf,
                $markerPrefix,
                $suffix,
                $fieldMarkerArray,
                $markerArray
            );

            foreach ($newRow as $field => $v) {
                $tagId = ControlApi::getTagId(
                    $jsTableNamesId,
                    $view,
                    $uid,
                    $field
                );

                if (is_array($v)) {
                    reset($v);
                    $vFirst = current($v);
                    $objResponse->addAssign($tagId, 'innerHTML', $vFirst);
                    $c = 0;
                    foreach ($v as $k => $v2) {
                        $c++;
                        $tagId2 = $tagId . '-' . $c;
                        $objResponse->addAssign($tagId2, 'innerHTML', $v2);
                    }
                } else {
                    $objResponse->addAssign($tagId, 'innerHTML', $v);
                }
            }
        }

        $result = $objResponse->getXML();

        // return the XML response generated by the tx_taxajax_response object
        return $result;
    }

    public function commands($cmd, $param1 = '', $param2 = '', $param3 = '')
    {
        $objResponse = new tx_taxajax_response($this->ajax->getTaxAjax()->getCharEncoding());

        switch ($cmd) {
            default:
                $hookVar = 'ajaxCommands';
                if ($hookVar && is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT][$hookVar])) {
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT][$hookVar] as $classRef) {
                        $hookObj = GeneralUtility::makeInstance($classRef);
                        if (method_exists($hookObj, 'init')) {
                            $hookObj->init($this);
                        }
                        if (method_exists($hookObj, 'commands')) {
                            $tmpArray =
                                $hookObj->commands(
                                    $cmd,
                                    $param1,
                                    $param2,
                                    $param3,
                                    $objResponse
                                );
                        }
                    }
                }
                break;
        }

        return $objResponse->getXML();
    }

    public function showArticle($data)
    {
        if (
            isset($data[TT_PRODUCTS_EXT]) &&
            is_array($data[TT_PRODUCTS_EXT])
        ) {
            $data = $data[TT_PRODUCTS_EXT];
        } else {
            return false;
        }

        if (
            isset($data['content']) &&
            $data['content'] != ''
        ) {
            $contentRow =
                $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
                    '*',
                    'tt_content',
                    'uid = ' . intval($data['content'])
                );
            $this->cObj->start($contentRow, 'tt_content');
        } else {
            return false;
        }

        $cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
        $result = '';
        $pibaseObj = GeneralUtility::makeInstance('tx_ttproducts_pi1_base');
        $mainObj = GeneralUtility::makeInstance('tx_ttproducts_main');
        $parameterApi = GeneralUtility::makeInstance(ParameterApi::class);
        $pibaseObj->setContentObjectRenderer($this->cObj);

        if (
            isset($data) &&
            is_array($data) &&
            isset($data[$pibaseObj->prefixId]) &&
            is_array($data[$pibaseObj->prefixId])
        ) {
            foreach ($data[$pibaseObj->prefixId] as $k => $v) {
                $parameterApi->setAndVar($k, $v);
            }
        }

        $this->ajax->setConf($data['conf']);
        $objResponse = new tx_taxajax_response($this->ajax->getTaxAjax()->getCharEncoding());

        $content = '';
        $bDoProcessing =
            $mainObj->init(
                $cnfObj->getConf(),
                $cnfObj->getConfig(),
                $this->getRequest(),
                $this->cObj,
                'tx_ttproducts_pi1_base',
                $errorCode,
                true
            );
        if (
            isset($contentRow) &&
            is_array($contentRow) &&
            isset($contentRow['uid'])
        ) {
            $code = 'LIST';
            $mainObj->codeArray = [$code];
            $tagId = 'tt-products-' . strtolower($code) . '-' . $contentRow['uid'];
        } else {
            $content = 'Missing content data row';
            $bDoProcessing = false;
        }

        if ($bDoProcessing || !empty($errorCode)) {
            $content =
                $mainObj->run(
                    $this->cObj,
                    'tx_ttproducts_pi1_base',
                    $errorCode,
                    $content,
                    true
                );
        }

        $objResponse->addAssign($tagId, 'innerHTML', $content);
        $result = $objResponse->getXML();

        return $result;
    }

    // XAJAX functions
    public function showList($data)
    {
        $tagId = '';
        $result = '';
        $cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
        $pibaseObj = GeneralUtility::makeInstance('tx_ttproducts_pi1_base');
        $mainObj = GeneralUtility::makeInstance('tx_ttproducts_main');
        $parameterApi = GeneralUtility::makeInstance(ParameterApi::class);

        $piVars = $parameterApi->getPiVars();
        $prefixId = $parameterApi->getPrefixId();

        if (isset($piVars) && is_array($piVars)) {
            if (
                isset($data) &&
                is_array($data) &&
                isset($data[$prefixId]) &&
                is_array($data[$prefixId])
            ) {
                foreach ($data[$prefixId] as $k => $v) {
                    if (isset($piVars[$k])) {
                        $piVars[$k] .= ',' . $v;
                    } else {
                        $piVars[$k] = $v;
                    }
                }
            }
        }

        // We put our incomming data to the regular piVars
        $parameterApi = GeneralUtility::makeInstance(ParameterApi::class);
        $parameterApi->setPiVars($piVars);
        // 		$pibaseObj->piVars = $piVars;

        $pibaseObj->setContentObjectRenderer($this->cObj);

        // Instantiate the tx_xajax_response object
        $objResponse = new tx_xajax_response();

        $bDoProcessing =
            $mainObj->init(
                $cnfObj->getConf(),
                $cnfObj->getConfig(),
                $this->getRequest(),
                $this->cObj,
                'tx_ttproducts_pi1_base',
                $errorCode,
                true
            );

        if (count($this->codeArray)) {
            foreach ($this->codeArray as $k => $code) {
                if ($code != 'LISTARTICLES') {
                    unset($this->codeArray[$k]);
                } else {
                    $tagId = 'tx-ttproducts-pi1-' . strtolower($code);
                }
            }
        }

        if ($tagId != '') {
            if ($bDoProcessing || !empty($errorCode)) {
                $content =
                    $mainObj->run(
                        $this->cObj,
                        'tx_ttproducts_pi1_base',
                        $errorCode,
                        $content,
                        true
                    );

                $objResponse->addAssign(
                    $tagId,
                    'innerHTML',
                    $content
                );

                // return the XML response generated by the tx_xajax_response object
                $result = $objResponse->getXML();
            }
        }

        return $result;
    }

    public function destruct(): void
    {
        $controlCreatorObj = GeneralUtility::makeInstance(Creator::class);
        $controlCreatorObj->destruct();

        $modelCreatorObj = GeneralUtility::makeInstance('tx_ttproducts_model_creator');
        $modelCreatorObj->destruct();

        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $tablesObj->destruct();
    }

    private function getRequest(): ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'];
    }
}
