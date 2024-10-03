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
 * function to add a variant edit field to products
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 */
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

use JambageCom\TtProducts\Api\ParameterApi;

class tx_ttproducts_edit_variant_view implements tx_ttproducts_edit_variant_view_int, SingletonInterface
{
    protected $modelObj;

    public function init($modelObj): void
    {
        $this->modelObj = $modelObj;
    }

    public function getModelObj()
    {
        return $this->modelObj;
    }

    public function getMarkerArray(
        $bEditable,
        $row,
        $funcTablename,
        $theCode,
        $config,
        &$markerArray
    ): void {
        $parameterApi = GeneralUtility::makeInstance(ParameterApi::class);
        if (isset($config) && is_array($config)) {
            $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
            $itemTableView = $tablesObj->get($funcTablename, true);
            $uid = $row['uid'];
            $mainAttributes = '';

            if (isset($config['params'])) {
                $mainAttributes = $config['params'];
            }

            if (isset($config['suffix'])) {
                $suffix = '_' . $config['suffix'];
            } else {
                $suffix = $config['index'];
            }
            $field = 'edit' . $suffix;
            $name = tx_ttproducts_control_basket::getTagName($row['uid'], $field);
            $value = '';
            if (isset($row[$field])) {
                $value = $row[$field];
            }

            if ($bEditable) {
                $basketExtRaw = $parameterApi->getBasketExtRaw();
                if (isset($basketExtRaw) && is_array($basketExtRaw)) {
                    if (isset($basketExtRaw[$uid]) && is_array($basketExtRaw[$uid])) {
                        $value = $basketExtRaw[$uid][$field];
                    }
                }

                $ajaxFunction = tx_ttproducts_control_basket::getAjaxVariantFunction($row, $funcTablename, $theCode);
                $splitArray = preg_split('/ *= */', $mainAttributes);
                $mainAttributesArray = [];

                if (isset($splitArray) && is_array($splitArray)) {
                    $lastKey = 0;
                    $lastAttribute = '';
                    $switch = 'read_key';

                    foreach ($splitArray as $v) {
                        if (
                            ($switch == 'read_value') &&
                            preg_match('/".*"/', $v)
                        ) {
                            $mainAttributesArray[$lastAttribute] = str_replace('"', '', $v);
                            $switch = 'read_key';
                        } else {
                            $lastAttribute = strtolower($v);
                            if ($switch == 'read_value') {
                                $mainAttributesArray[$lastAttribute] = '';
                            }
                            $switch = 'read_value';
                        }
                    }
                }

                if (!isset($mainAttributesArray['onchange'])) {
                    $mainAttributesArray['onchange'] = $ajaxFunction;
                }

                $mainId = $itemTableView->getId($row, '', $theCode);
                $id = $mainId . '-' . str_replace('_', '-', $field);
                $mainAttributesArray['id'] = $id;

                $html = tx_ttproducts_form_div::createTag(
                    'input',
                    $name,
                    $value,
                    '',
                    $mainAttributesArray
                );
            } else {
                $html = '';
                if (isset($row[$field])) {
                    $html = htmlspecialchars($row[$field], $flags);
                }
            }

            $markerArray['###EDIT_VARIANT###'] = $html;
        }
    }

    public function getSubpartMarkerArray(
        $templateCode,
        $funcTablename,
        $row,
        $theCode,
        $bEditable,
        $tagArray,
        &$subpartArray,
        &$wrappedSubpartArray
    ): void {
        // 		###edit_variant1###
        $templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        $editConf = $this->getModelObj()->getValidConfig($row);

        if (isset($editConf) && is_array($editConf)) {
            $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);

            foreach ($editConf as $k => $config) {
                if (isset($config['suffix'])) {
                    $suffix = '_' . $config['suffix'];
                } else {
                    $suffix = $config['index'];
                }
                $marker = 'edit_variant' . $suffix;

                if (isset($tagArray[$marker])) {
                    $subpartMarker = '###' . $marker . '###';
                    // $wrappedSubpartArray[$subpartMarker] = '';
                    $markerArray = [];
                    $this->getMarkerArray(
                        $bEditable,
                        $row,
                        $funcTablename,
                        $theCode,
                        $config,
                        $markerArray
                    );

                    $subpartContent = GeneralUtility::makeInstance(MarkerBasedTemplateService::class)->getSubpart($templateCode, $subpartMarker);
                    $content =
                        $templateService->substituteMarkerArrayCached(
                            $subpartContent,
                            $markerArray
                        );
                    $subpartArray[$subpartMarker] = $content;
                }
            }
        }

        foreach ($tagArray as $tag => $number) {
            if (strpos($tag, 'edit_variant') === 0) {
                if (!isset($subpartArray['###' . $tag . '###'])) {
                    $subpartArray['###' . $tag . '###'] = '';
                }
            }
        }
    }
}
