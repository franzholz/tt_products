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
use JambageCom\Div2007\Utility\FrontendUtility;
use JambageCom\Div2007\Utility\ObsoleteUtility;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class tx_ttproducts_product_view extends tx_ttproducts_article_base_view
{
    public $relatedArray = []; // array of related products
    public $marker = 'PRODUCT';
    public $type = 'product';
    public $piVar = 'product';
    public $articleArray = [];
    public $datafield;

    public function init(
        $modelObj
    ) {
        $this->variant = GeneralUtility::makeInstance('tx_ttproducts_variant_view');
        $this->editVariant = GeneralUtility::makeInstance('tx_ttproducts_edit_variant_view');

        $result = parent::init($modelObj);

        return $result;
    }

    public function getItemMarkerSubpartArrays(
        $templateCode,
        $functablename,
        $row,
        &$markerArray,
        &$subpartArray,
        &$wrappedSubpartArray,
        $tagArray,
        $multiOrderArray = [],
        $productRowArray = [],
        $theCode = '',
        $basketExtra = [],
        $basketRecs = [],
        $iCount = '',
        $checkPriceZero = false
    ): void {
        $cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
        $conf = $cnfObj->getConf();
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');

        parent::getItemMarkerSubpartArrays(
            $templateCode,
            $functablename,
            $row,
            $markerArray,
            $subpartArray,
            $wrappedSubpartArray,
            $tagArray,
            $multiOrderArray,
            $productRowArray,
            $theCode,
            $basketExtra,
            $basketRecs,
            $iCount,
            $checkPriceZero
        );
        $extArray = [];
        if (isset($row['ext'])) {
            $extArray = $row['ext'];
        }

        if (is_array($extArray) && isset($extArray['tt_products']) && is_array($extArray['tt_products'])) {
            $variant = $extArray['tt_products'][0]['vars'];
        } elseif (is_array($extArray) && isset($extArray['tx_dam'])) {
            $variant = $extArray['tx_dam'][0]['vars'];
        }
        $bGiftService = true;
        if ($this->getModelObj()->hasAdditional($row, 'noGiftService')) {
            $bGiftService = false;
        }

        $datafieldViewObj = $this->getFieldObj('datasheet');
        if (isset($datafieldViewObj) && is_object($datafieldViewObj)) {
            $datafieldViewObj->getItemSubpartArrays(
                $templateCode,
                $this->getMarker(),
                $functablename,
                $row,
                'datasheet_uid',
                $this->getModelObj()->getTableConf($theCode),
                $subpartArray,
                $wrappedSubpartArray,
                $tagArray,
                $theCode,
                $basketExtra,
                $basketRecs
            );
        }

        // download subparts

        $downloadTableView = $tablesObj->get('tt_products_downloads', true);
        $downloadTable = $downloadTableView->getModelObj();
        $downloadTagArray =
            $downloadTableView->getTagMarkerArray(
                $tagArray,
                $this->getMarker()
            );

        $itemArray =
            $downloadTable->getRelatedUidArray(
                $row['uid'],
                $downloadTagArray,
                'tt_products'
            );

        $localRowArray = [];

        foreach ($productRowArray as $productRow) {
            if ($productRow['uid'] == $row['uid']) {
                $localRowArray[] = $productRow;
            }
        }

        $downloadTableView->getDownloadMarkerSubpartArrays(
            $templateCode,
            $conf,
            $itemArray,
            $markerArray,
            $subpartArray,
            $wrappedSubpartArray,
            $this->getMarker(),
            $tagArray,
            $hiddenFields = '',
            $multiOrderArray,
            $localRowArray,
            $checkPriceZero
        );
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
    public function getModelMarkerArray(
        $row,
        $markerParam,
        &$markerArray,
        $catTitle,
        $imageNum = 0,
        $imageRenderObj = 'image',
        $tagArray = [],
        $forminfoArray = [],
        $theCode = '',
        $basketExtra = [],
        $basketRecs = [],
        $id = '',
        $prefix = '',
        $suffix = '',
        $linkWrap = '',
        $bHtml = true,
        $charset = '',
        $hiddenFields = '',
        $multiOrderArray = [],
        $productRowArray = [],
        $bEnableTaxZero = false,
        $notOverwritePriceIfSet = true
    ): void {
        // Returns a markerArray ready for substitution with information for the tt_producst record, $row
        $templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $modelObj = $this->getModelObj();
        $cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
        $conf = $cnfObj->getConf();
        $cObj = FrontendUtility::getContentObjectRenderer();
        parent::getModelMarkerArray(
            $row,
            $markerParam,
            $markerArray,
            $catTitle,
            $imageNum,
            $imageRenderObj,
            $tagArray,
            $forminfoArray,
            $theCode,
            $basketExtra,
            $basketRecs,
            $id,
            $prefix,
            $suffix,
            $linkWrap,
            $bHtml,
            $charset,
            $hiddenFields,
            $multiOrderArray,
            $productRowArray,
            $bEnableTaxZero,
            $notOverwritePriceIfSet
        );

        // Subst. fields
        $markerArray['###' . $this->getMarker() . '_UNIT###'] = $row['unit'] ?? '';
        $markerArray['###' . $this->getMarker() . '_UNIT_FACTOR###'] = $row['unit_factor'] ?? '0';
        $markerArray['###' . $this->getMarker() . '_WWW###'] = $row['www'] ?? '';
        $markerArray['###BULKILY_WARNING###'] = !empty($row['bulkily']) ? $conf['bulkilyWarning'] : '';

        if (!empty($conf['itemMarkerArrayFunc'])) {
            $markerArray =
                ObsoleteUtility::userProcess(
                    $this,
                    $conf,
                    'itemMarkerArrayFunc',
                    $markerArray
                );
        }

        if ($theCode == 'SINGLE') {
            $addressUid = intval($row['address']);
            $addressRow = [];
            $addressViewObj = $tablesObj->get('address', true);

            if (is_object($addressViewObj)) {
                if (
                    ($conf['table.']['address'] != 'tt_address' || ExtensionManagementUtility::isLoaded(TT_ADDRESS_EXT)) &&
                    $addressUid &&
                    $modelObj->fieldArray['address']
                ) {
                    $addressObj = $addressViewObj->getModelObj();
                    $addressRow = $addressObj->get($addressUid);
                }
                $adressMarkerArray = [];
                $tmp = '';
                $addressViewObj->getRowMarkerArray(
                    'address',
                    $addressRow,
                    '',
                    $adressMarkerArray,
                    $tmp,
                    $tmp,
                    $tagArray,
                    $theCode,
                    $basketExtra,
                    $basketRecs,
                    $bHtml,
                    $charset,
                    $imageNum,
                    $imageRenderObj,
                    $id,
                    $prefix,
                    $suffix,
                    $linkWrap,
                    $bEnableTaxZero
                );
                if (is_array($adressMarkerArray)) {
                    $markerArray = array_merge($markerArray, $adressMarkerArray);
                }
            }

            if ($row['note_uid']) {
                $pageObj = $tablesObj->get('pages');

                $notePageArray = $pageObj->getNotes($row['uid']);
                $contentConf = $cnfObj->getTableConf('tt_content', $code);

                foreach ($notePageArray as $k => $pid) {
                    $pageRow = $pageObj->get($pid);
                    $pageMarkerKey = 'PRODUCT_NOTE_UID_' . ($k + 1);
                    $contentArray = $tablesObj->get('tt_content')->getFromPid($pid);
                    $countArray = [];
                    foreach ($contentArray as $k2 => $contentEl) {
                        $cType = $contentEl['CType'];
                        $countArray[$cType] = intval($countArray[$cType]) + 1;
                        $markerKey = $pageMarkerKey . '_' . $countArray[$cType] . '_' . strtoupper($cType);

                        foreach ($tagArray as $index => $v) {
                            $pageFoundPos = strpos($index, $pageMarkerKey);
                            if ($pageFoundPos == 0 && $pageFoundPos !== false) {
                                $fieldName = str_replace($pageMarkerKey . '_', '', $index);
                                if (isset($pageRow[$fieldName])) {
                                    $markerArray['###' . $index . '###'] = $pageRow[$fieldName];
                                }
                            }
                            if (strpos($index, $markerKey) === false) {
                                continue;
                            }
                            $fieldPos = strrpos($index, '_');
                            $fieldName = substr($index, $fieldPos + 1);
                            $markerArray['###' . $index . '###'] = $contentEl[$fieldName];
                            if (
                                isset($contentConf['displayFields.']) &&
                                is_array($contentConf['displayFields.']) &&
                                $contentConf['displayFields.'][$fieldName] == 'RTEcssText'
                            ) {
                                // Extension CSS styled content
                                if (ExtensionManagementUtility::isLoaded('css_styled_content')) {
                                    $markerArray['###' . $index . '###'] =
                                        FrontendUtility::RTEcssText(
                                            $local_cObj,
                                            $contentEl[$fieldName]
                                        );
                                } elseif (is_array($conf['parseFunc.'])) {
                                    $markerArray['###' . $index . '###'] =
                                        $cObj->parseFunc($contentEl[$fieldName], $conf['parseFunc.']);
                                }
                            }
                        }
                    }
                }
            }

            foreach ($tagArray as $key => $val) {
                if (strpos($key, 'PRODUCT_NOTE_UID') !== false) {
                    if (!isset($markerArray['###' . $key . '###'])) {
                        $markerArray['###' . $key . '###'] = '';
                    }
                }
            }

            $extKey = '';

            // check need for rating
            if (
                (
                    isset($tagArray['RATING']) ||
                    isset($tagArray['RATING_STATIC'])
                ) &&
                isset($conf['RATING']) && isset($conf['RATING.'])
            ) {
                $cObjectType = $conf['RATING'];
                $conf1 = $conf['RATING.'];
                $extKey = $conf['RATING.']['extkey'];
                $api = $conf['RATING.']['api'];
            }

            if (
                $extKey != '' &&
                ExtensionManagementUtility::isLoaded($extKey) &&
                $api != '' &&
                class_exists($api)
            ) {
                $apiObj = GeneralUtility::makeInstance($api);
                if (method_exists($apiObj, 'getDefaultConfig')) {
                    $ratingConf = $apiObj->getDefaultConfig();
                    if (isset($ratingConf) && is_array($ratingConf)) {
                        $tmpConf = $conf1;
                        ArrayUtility::mergeRecursiveWithOverrule($tmpConf, $ratingConf);
                        $ratingConf = $tmpConf;
                    } else {
                        $ratingConf = $conf1;
                    }
                } else {
                    $ratingConf = $conf1;
                }
                $ratingConf['ref'] = TT_PRODUCTS_EXT . '_' . $row['uid'];

                $cObj = GeneralUtility::makeInstance('TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer');
                // @var $cObj TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
                $cObj->start([]);
                $markerArray['###RATING###'] = $cObj->cObjGetSingle($cObjectType, $ratingConf);
                $cObj = GeneralUtility::makeInstance('TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer');
                // @var $cObj TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
                $cObj->start([]);
                $ratingConf['mode'] = 'static';
                $markerArray['###RATING_STATIC###'] = $cObj->cObjGetSingle($cObjectType, $ratingConf);
            } else {
                $markerArray['###RATING###'] = '';
                $markerArray['###RATING_STATIC###'] = '';
            }

            $extKey = '';
            // check need for comments
            if (
                isset($tagArray['COMMENT']) &&
                isset($conf['COMMENT']) &&
                isset($conf['COMMENT.'])
            ) {
                $cObjectType = $conf['COMMENT'];
                $conf1 = $conf['COMMENT.'];
                $extKey = $conf['COMMENT.']['extkey'];
                $api = $conf['COMMENT.']['api'];
                $param = $conf['COMMENT.']['param'];
                if ($param == '') {
                    $param = 'list';
                }
            }

            if ($extKey != '' && ExtensionManagementUtility::isLoaded($extKey) && $api != '' && class_exists($api)) {
                $apiObj = GeneralUtility::makeInstance($api);

                if (method_exists($apiObj, 'getDefaultConfig')) {
                    $commentConf = $apiObj->getDefaultConfig($param);
                    if (isset($commentConf) && is_array($commentConf)) {
                        $tmpConf = $conf1;
                        ArrayUtility::mergeRecursiveWithOverrule($tmpConf, $commentConf);
                        $commentConf = $tmpConf;
                    } else {
                        $commentConf = $conf1;
                    }
                } else {
                    $commentConf = $conf1;
                }
                $commentConf['ref'] = TT_PRODUCTS_EXT . '_' . $row['uid'];
                $urlObj = GeneralUtility::makeInstance('tx_ttproducts_url_view');
                $linkParams = $urlObj->getLinkParams(
                    '',
                    [
                        'product' => $row['uid'],
                    ],
                    true,
                    false,
                    0,
                    ''
                );
                $commentConf['linkParams'] = $linkParams;

                $cObj = GeneralUtility::makeInstance('TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer');
                $cObj->start([]);
                $markerArray['###COMMENT###'] = $cObj->cObjGetSingle($cObjectType, $commentConf);
            } else {
                $markerArray['###COMMENT###'] = '';
            }
        }

        if (!empty($row['special_preparation'])) {
            $markerArray['###' . $this->getMarker() . '_SPECIAL_PREP###'] = $templateService->substituteMarkerArray($conf['specialPreparation'], $markerArray);
        } else {
            $markerArray['###' . $this->getMarker() . '_SPECIAL_PREP###'] = '';
        }
    } // getModelMarkerArray
}
