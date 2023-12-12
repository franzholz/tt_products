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
 * view functions for a basket item object
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage tt_products
 */
use JambageCom\Div2007\Utility\FrontendUtility;
use JambageCom\TtProducts\Api\Localization;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Resource\FilePathSanitizer;

class tx_ttproducts_basketitem_view implements SingletonInterface
{
    public function getQuantityName(
        $uid,
        $functablename,
        $externalRow,
        $parentFuncTablename,
        $parentRow,
        $callFunctableArray // deprecated parameter
    ) {
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $basketVar = tx_ttproducts_model_control::getBasketVar();
        $externalQuantity = '';

        if (
            $functablename != 'tt_products' &&
            is_array($externalRow) &&
            isset($externalRow['uid'])
        ) {
            if (
                isset($parentRow) &&
                is_array($parentRow) &&
                isset($parentRow['uid'])
            ) {
                $piVar = tx_ttproducts_model_control::getPiVar($parentFuncTablename);
                if ($piVar !== false) {
                    $externalQuantity = $piVar . '=' . intval($parentRow['uid']) .
                        tx_ttproducts_variant_int::EXTERNAL_QUANTITY_SEPARATOR;
                }
            }

            $piVar = tx_ttproducts_model_control::getPiVar($functablename);

            if ($piVar !== false) {
                $externalQuantity = '[' . $externalQuantity . $piVar . '=' . intval($externalRow['uid']) . ']';
            }
        } elseif (isset($callFunctableArray) && is_array($callFunctableArray)) {
            foreach ($callFunctableArray as $callFunctablename) {
                $funcMarker = $tablesObj->get($callFunctablename, true)->getMarker();
                $externalQuantity .= '[###' . $funcMarker . '_UID###]';
            }
        }

        $basketQuantityName = $basketVar . '[' . $uid . ']' . $externalQuantity . '[quantity]';

        return $basketQuantityName;
    }

    public function getItemMarkerSubpartArrays(
        $templateCode,
        $functablename,
        $row,
        $theCode,
        $tagArray,
        $bEditable,
        $productRowArray,
        &$markerArray,
        &$subpartArray,
        &$wrappedSubpartArray
    ) {
        $productFuncTablename = 'tt_products';

        if (isset($productRowArray) && is_array($productRowArray)) {
            foreach ($productRowArray as $productRow) {
                if ($row['uid'] == $productRow['uid']) {
                    $row = array_merge($row, $productRow);
                    break;
                }
            }
        }

        $cObj = GeneralUtility::makeInstance('TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer');
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $viewTableView = $tablesObj->get($productFuncTablename, true);
        $urlObj = GeneralUtility::makeInstance('tx_ttproducts_url_view');
        $viewTableView->editVariant->getSubpartMarkerArray(
            $templateCode,
            $productFuncTablename,
            $row,
            $theCode,
            $bEditable,
            $tagArray,
            $subpartArray,
            $wrappedSubpartArray
        );

        $addQueryString = [];
        $uid = $row['uid'];
        $pid = $GLOBALS['TSFE']->id;
        $cmdArray = tx_ttproducts_control_basket::getCmdArray();
        $css_current = '';

        foreach ($cmdArray as $cmd) {
            $upperCmd = strtoupper($cmd);

            if (isset($tagArray['LINK_BASKET_' . $upperCmd])) {
                $addQueryString[$cmd] = $uid;
                $basketVar = tx_ttproducts_model_control::getBasketParamVar();
                if (isset($row['ext'])) {
                    $extArray = $row['ext'];
                }

                if (
                    isset($extArray) &&
                    is_array($extArray) &&
                    isset($extArray['extVarLine'])
                ) {
                    $addQueryString[$basketVar] = md5($extArray['extVarLine']);

                    $pageLink = FrontendUtility::getTypoLink_URL(
                        $cObj,
                        $pid,
                        $urlObj->getLinkParams(
                            '',
                            $addQueryString,
                            true,
                            false
                        )
                    );

                    $wrappedSubpartArray['###LINK_BASKET_' . $upperCmd . '###'] =
                        [
                            '<a href="' . htmlspecialchars($pageLink) . '"' . $css_current . '>',
                            '</a>',
                        ];
                    unset($addQueryString[$basketVar]);
                } else {
                    $subpartArray['###LINK_BASKET_' . $upperCmd . '###'] = '';
                }
                unset($addQueryString[$cmd]);
            }
        }
    }

    /**
     * Template marker substitution
     * Fills in the markerArray with data for a product.
     *
     * @param	array		reference to an item array with all the data of the item
     * @param	string		title of the category
     * @param	int		number of images to be shown
     * @param	object		the image cObj to be used
     * @param	array		information about the parent HTML form
     *
     * @return	array
     *
     * @access private
     */
    public function getItemMarkerArray(
        $functablename,
        $useCache,
        $item,
        &$markerArray,
        $tagArray,
        &$hiddenText,
        $theCode = '',
        $bInputDisabled = false,
        $id = '1',
        $bSelect = true,
        $charset = '',
        $externalRow = [],
        $parentFuncTablename = '',
        $parentRow = [],
        $callFunctableArray = [],  // deprecated
        $filterRowArray = []
    ) {
        $productFuncTablename = 'tt_products';
        $basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $languageObj = GeneralUtility::makeInstance(Localization::class);
        $cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
        $itemObj = GeneralUtility::makeInstance('tx_ttproducts_basketitem');

        $conf = $cnfObj->getConf();
        $basketVar = tx_ttproducts_model_control::getBasketVar();
        $viewTableView = $tablesObj->get($productFuncTablename, true);
        $viewTable = $viewTableView->getModelObj();
        $fieldArray = $viewTable->variant->getFieldArray();
        $keyAdditional = $viewTable->variant->getAdditionalKey();
        $selectableArray = $viewTable->variant->getSelectableArray();
        $basketExt = tx_ttproducts_control_basket::getBasketExt();
        $bUseXHTML = empty($GLOBALS['TSFE']->config['config']['xhtmlDoctype']);
        $imageObj = GeneralUtility::makeInstance('tx_ttproducts_field_image_view');

        $variantSeparator = $viewTable->getVariant()->getSplitSeparator();
        $row = $item['rec'];
        $uid = $row['uid'];
        $presetVariantArray =
            tx_ttproducts_control_product::getPresetVariantArray(
                $viewTable,
                $row,
                $cnfObj->getUseArticles()
            );

        if (
            $theCode == 'SINGLE' &&
            empty($presetVariantArray) &&
            $bSelect &&
            $functablename == 'tt_products'
        ) {
            $articleNo = tx_ttproducts_control_product::getActiveArticleNo();

            if ($articleNo !== false) {
                $articleObj = $tablesObj->get('tt_products_articles');
                $articleRow = $articleObj->get($articleNo);

                if (isset($fieldArray) && is_array($fieldArray)) {
                    foreach ($fieldArray as $k => $field) {
                        $variantValue = $row[$field] ?? '';
                        $prodTmpRow =
                            preg_split(
                                '/[\h]*' . $variantSeparator . '[\h]*/',
                                $variantValue,
                                -1,
                                PREG_SPLIT_NO_EMPTY
                            );

                        $imageFileArray = [];

                        if ($variantValue && $prodTmpRow[0]) {
                            $key = array_search(trim($articleRow[$field]), $prodTmpRow, true);
                            if ($key !== false) {
                                $presetVariantArray[$field] = $key;
                            }
                        }
                    }
                }
            }
        }

        // Returns a markerArray ready for substitution with information for the tt_producst record, $row

        $extArray = '';
        if (isset($row['ext'])) {
            $extArray = $row['ext'];
        }
        $variant = '';

        if (is_array($extArray)) {
            if (isset($extArray['extVarLine'])) {
                $variant = $extArray['extVarLine'];
            } elseif (
                isset($extArray['tt_products']) &&
                is_array($extArray['tt_products'])
            ) {
                $variant = $viewTable->variant->getVariantFromRow($row);
            } elseif (isset($extArray['tx_dam'])) {
                $variant = $extArray['tx_dam'][0]['vars'];
            }
        }
        $hiddenText = '';
        $quantity = $item['count'];
        $showAmount = ($theCode == 'BASKET' ? 'basket' : $cnfObj->getBasketConf('view', 'showAmount'));
        $quantity = $itemObj->getQuantity($item, $showAmount);
        $radioInputArray = $basketObj->getRadioInputArray($row);
        $bUseRadioBox =
            is_array($radioInputArray) &&
            count($radioInputArray) > 0 &&
            !empty($radioInputArray['name']);

        $jsTableName = str_replace('_', '-', $productFuncTablename);
        $basketQuantityName =
            $this->getQuantityName(
                $row['uid'],
                $functablename,
                $externalRow,
                $parentFuncTablename,
                $parentRow,
                $callFunctableArray
            );

        $attributeFieldName = ($bUseRadioBox && !empty($radioInputArray['name']) ? $radioInputArray['name'] : $basketQuantityName);
        $markerArray['###FIELD_NAME###'] = htmlspecialchars($attributeFieldName);

        // check need for comments
        if (
            $useCache &&
            $showAmount == 'basket' &&
            isset($conf['BASKETQTY']) &&
            isset($conf['BASKETQTY.']) &&
            isset($conf['BASKETQTY.']['userFunc'])
        ) {
            $cObjectType = $conf['BASKETQTY'];
            $basketConf = [];
            $basketConf['ref'] = $uid;
            $basketConf['row'] = $row;
            $basketConf['variant'] = $variant;
            $basketConf['userFunc'] = $conf['BASKETQTY.']['userFunc'];
            $cObjectType = $conf['BASKETQTY'];

            $cObj = GeneralUtility::makeInstance('TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer');
            $cObj->start([]);
            $markerArray['###FIELD_QTY###'] = $cObj->cObjGetSingle($cObjectType, $basketConf);
        } else {
            if (
                isset($callFunctableArray) &&
                is_array($callFunctableArray) &&
                count($callFunctableArray)
            ) {
                $quantityMarker = '###';
                foreach ($callFunctableArray as $marker => $callFunctablename) {
                    $quantityMarker .=
                        (new tx_ttproducts_control_basketquantity())->getQuantityMarker($marker, $uid, '###' . $marker . '_UID###');
                }
                $quantityMarker .= '###';
            } elseif (
                isset($filterRowArray) &&
                is_array($filterRowArray) &&
                count($filterRowArray)
            ) {
                $filterQuantity = 0;
                foreach ($filterRowArray as $filterRow) {
                    if ($filterRow['product_uid'] == $uid && isset($filterRow['quantity'])) {
                        $filterQuantity += $filterRow['quantity'];
                    }
                }
                $quantityMarker = $filterQuantity;
            } else {
                $quantityMarker = $quantity ?: '';
            }

            $markerArray['###FIELD_QTY###'] = $quantityMarker;
        }

        $markerArray['###FIELD_ID###'] = $jsTableName . '-' . strtolower($theCode) . '-id-' . $id;

        $markerArray['###BASKET_ID###'] = $id;
        $markerArray['###BASKET_INPUT###'] = '';
        $markerArray['###BASKET_INTO_ID###'] = tx_ttproducts_model_control::getBasketIntoIdPrefix() . '-' . $row['uid'];
        $markerArray['###BASKET_INPUT_ERROR_ID###'] = tx_ttproducts_model_control::getBasketInputErrorIdPrefix() . '-' . $row['uid'];

        $markerArray['###DISABLED###'] = ($bInputDisabled ? ($bUseXHTML ? 'disabled="disabled"' : 'disabled') : '');

        $markerArray['###IN_STOCK_ID###'] = 'in-stock-id-' . $row['uid'];

        $markerArray['###BASKET_IN_STOCK###'] = $languageObj->getLabel($row['inStock'] > 0 ? 'in_stock' : 'not_in_stock');

        $fileName = $conf['basketPic'];
        $basketFile = '';
        $sanitizer = GeneralUtility::makeInstance(FilePathSanitizer::class);
        $basketFile = $sanitizer->sanitize($fileName);

        $markerArray['###IMAGE_BASKET_SRC###'] = $basketFile;
        $fileresource = FrontendUtility::fileResource($basketFile);
        $markerArray['###IMAGE_BASKET###'] = $fileresource;

        if (isset($fieldArray) && is_array($fieldArray)) {
            $formConf = $cnfObj->getFormConf($theCode);
            $tablename = $viewTable->getTablename();

            foreach ($fieldArray as $k => $field) {
                if (!isset($selectableArray[$k])) { // additional
                    continue;
                }
                $fieldConf = $GLOBALS['TCA'][$tablename]['columns'][$field];
                $fieldMarker = strtoupper($field);

                if (isset($fieldConf) && is_array($fieldConf)) {
                    $text = '';
                    $variantValue = $row[$field] ?? '';
                    $prodTmpRow = preg_split('/[\h]*' . $variantSeparator . '[\h]*/', $variantValue, -1, PREG_SPLIT_NO_EMPTY);

                    $imageFileArray = [];

                    if ($bSelect && $variantValue && $prodTmpRow[0]) {
                        $selectConfKey = $viewTable->variant->getSelectConfKey($field);

                        if (
                            is_array($formConf) &&
                            isset($formConf[$selectConfKey . '.'])
                        ) {
                            $theFormConf = $formConf[$selectConfKey . '.'];

                            if (
                                isset($theFormConf['image.']) &&
                                is_array($theFormConf['image.']) &&
                                isset($theFormConf['imageImport.']) &&
                                is_array($theFormConf['imageImport.']) &&
                                isset($theFormConf['layout'])
                            ) {
                                $imageConf = $theFormConf['image.'];
                                $imageFileArray = [];
                                foreach ($prodTmpRow as $k2 => $variantVal) {
                                    $tmpImgCode = '';
                                    foreach ($theFormConf['imageImport.'] as $k3 => $imageImport) {
                                        if (is_array($imageImport['prod.'])) {
                                            if (isset($imageImport['sql.'])) {
                                                $bIsValid =
                                                    tx_ttproducts_sql::isValid(
                                                        $row,
                                                        $imageImport['sql.']['where']
                                                    );
                                                if (!$bIsValid) {
                                                    continue;
                                                }
                                            }
                                            $imageFile = $imageImport['prod.'][$k2];
                                            $imagePath = $imageImport['path'];

                                            if ($imageFile != '') {
                                                $imageConf['file'] = $imagePath . $imageFile;
                                                $tmpImgCode =
                                                    $imageObj->getImageCode(
                                                        $imageConf,
                                                        $theCode
                                                    );
                                            }
                                        }
                                    }
                                    $imageFileArray[] = $tmpImgCode;
                                }
                            }
                        } else {
                            $theFormConf = '';
                        }
                        $prodTranslatedRow = $prodTmpRow;
                        $type = '';
                        $selectedKey = '0';
                        switch ($selectableArray[$k]) {
                            case 1:
                                $type = 'select';
                                break;
                            case 2:
                                $type = 'radio';
                                break;
                            case 3:
                                $type = 'checkbox';
                                $selectedKey = '';
                                if ($quantity > 0) {
                                    $selectedKey = $variant;
                                }
                                break;
                        }

                        if (isset($presetVariantArray[$field])) {
                            $selectedKey = $presetVariantArray[$field];
                        }
                        $viewTable->getTableObj()->substituteMarkerArray($prodTranslatedRow);
                        $dataArray = [];
                        $layout = '';
                        $header = '';

                        if (isset($theFormConf) && is_array($theFormConf)) {
                            if (isset($theFormConf['header.'])) {
                                $header = $theFormConf['header.']['label'];
                            }
                            if (isset($theFormConf['layout'])) {
                                $layout = $theFormConf['layout'];
                            }
                            if (isset($theFormConf['dataArray.'])) {
                                $dataArray = $theFormConf['dataArray.'];
                            }
                        }

                        if ($type != '') {
                            $text = tx_ttproducts_form_div::createSelect(
                                $languageObj,
                                $prodTranslatedRow,
                                tx_ttproducts_control_basket::getTagName($row['uid'], $field),
                                $selectedKey,
                                false,
                                false,
                                [],
                                $type,
                                $dataArray,
                                $header,
                                $layout,
                                $imageFileArray
                            );
                        } else {
                            $text = $variantValue;
                        }
                    } else {
                        $prodTmpRow = $row;
                        $viewTable->variant->modifyRowFromVariant($prodTmpRow, $variant);
                        $text = $prodTmpRow[$field] ?? ''; // $prodTmpRow[0];
                    }

                    $markerArray['###FIELD_' . $fieldMarker . '_NAME###'] = $basketVar . '[' . $row['uid'] . '][' . $field . ']';
                    $markerArray['###FIELD_' . $fieldMarker . '_VALUE###'] = $row[$field] ?? '';
                    $markerArray['###FIELD_' . $fieldMarker . '_ONCHANGE'] = ''; // TODO:  use $forminfoArray['###FORM_NAME###' in something like onChange="Go(this.form.Auswahl.options[this.form.Auswahl.options.selectedIndex].value)"

                    $markerKey = '###' . $viewTableView->getMarker() . '_' . $fieldMarker . '###';
                    $markerArray[$markerKey] = $text;
                    $markerKey = '###' . $viewTableView->getMarker() . '_' . $fieldMarker . '_FUNCTION1###';

                    $markerArray[$markerKey] = tx_ttproducts_control_basket::getAjaxVariantFunction($row, $productFuncTablename, $theCode);
                }
            }
        }

        $prodAdditionalText['single'] = '';

        if ($bUseRadioBox) {
            $params = 'type="' . $radioInputArray['type'] . '"';
            $params .= (!empty($radioInputArray['params']) ? ' ' . $radioInputArray['params'] : '');
            if ($radioInputArray['checked'] == $uid) {
                $params .= ' ' . ($bUseXHTML ? 'checked="checked"' : 'checked');
            }
            $markerArray['###BASKET_INPUT###'] = tx_ttproducts_form_div::createTag('input', $radioInputArray['name'], $uid, $params);
        }

        if ($keyAdditional !== false) {
            $isSingleProduct = $viewTable->hasAdditional($row, 'isSingle');
            if ($isSingleProduct) {
                $message = $languageObj->getLabel('additional_single');
                $prodAdditionalText['single'] = $message . '<input type="checkbox" name="' . $basketQuantityName . '" ' . ($quantity ? 'checked="checked"' : '') . 'onchange = "this.form[this.name+\'[1]\'].value=(this.checked ? 1 : 0);" value="1">';
                $hiddenText .= '<input type="hidden" name="' . $basketQuantityName . '[1]" value="' . ($quantity ? '1' : '0') . '">';
            }

            $isImageProduct = $viewTable->hasAdditional($row, 'isImage');
            $damParam = tx_ttproducts_model_control::getPiVarValue('tx_dam');

            if (
                $functablename == 'tt_products' &&
                is_array($extArray) &&
                isset($extArray['tx_dam'])
            ) {
                reset($extArray['tx_dam']);
                $damext = current($extArray['tx_dam']);
                $damUid = $damext['uid'];
            } elseif (
                $isImageProduct &&
                isset($damParam)
            ) {
                $damUid = tx_ttproducts_model_control::getPiVarValue('tx_dam');
            }

            if (isset($damUid)) {
                $tableVariant = $viewTable->variant->getTableUid('tx_dam', $damUid);
                $variant .= $tableVariant;
                $markerArray['###DAM_UID###'] = $damUid;
            }
            $giftService = !$viewTable->hasAdditional($row, 'noGiftService');
        }

        if ($giftService) {
            $basketAdditionalName = $basketVar . '[' . $row['uid'] . '][additional][' . md5($variant) . ']';
            $bGiftService = false;
            if (
                isset($basketExt[$row['uid']]) &&
                isset($basketExt[$row['uid']][$variant . '.']) &&
                isset($basketExt[$row['uid']][$variant . '.']['additional']) &&
                is_array($basketExt[$row['uid']][$variant . '.']['additional'])
            ) {
                $bGiftService = $basketExt[$row['uid']][$variant . '.']['additional']['giftservice'];
            }
            $giftServicePostfix = '[giftservice]';
            $message = $languageObj->getLabel('additional_gift_service');
            $value = ($bGiftService ? '1' : '0');
            $prodAdditionalText['giftService'] = $message . '<input type="checkbox" name="' . $basketAdditionalName . $giftServicePostfix . '" ' . ($value ? 'checked="checked"' : '') . 'onchange = "this.form[this.name+\'[1]\'].value=(this.checked ? 1 : 0);" value="' . $value . '">';
            $hiddenText .= '<input type="hidden" name="' . $basketAdditionalName . $giftServicePostfix . '[1]" value="' . $value . '">';
        } else {
            $prodAdditionalText['giftService'] = '';
        }

        $markerArray['###FIELD_NAME_BASKET###'] = $basketVar . '[' . $row['uid'] . '][' . md5($variant) . ']';
        $markerArray['###PRODUCT_ADDITIONAL_SINGLE###'] = $prodAdditionalText['single'];
        $markerArray['###PRODUCT_ADDITIONAL_GIFT_SERVICE###'] = $prodAdditionalText['giftService'];
        $markerArray['###PRODUCT_ADDITIONAL_GIFT_SERVICE_DISPLAY###'] = ($value ? '1' : '');
        if (isset($tagArray['PRODUCT_HIDDEN_TEXT'])) {
            $markerArray['###PRODUCT_HIDDEN_TEXT###'] = $hiddenText;
            $hiddenText = '';
        }
    } // getItemMarkerArray
}
