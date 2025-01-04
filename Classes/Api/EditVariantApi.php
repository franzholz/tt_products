<?php

declare(strict_types=1);

namespace JambageCom\TtProducts\Api;

/*
* This file is part of the TYPO3 CMS project.
*
* It is free software; you can redistribute it and/or modify it under
* the terms of the GNU General Public License, either version 2
* of the License, or any later version.
*
* For the full copyright and license information, please read the
* LICENSE.txt file that was distributed with this source code.
*
* The TYPO3 project - inspiring people to share!
*/
/**
 * Part of the tt_products (Shop System) extension.
 *
 * functions for the edit variants
 * former class tx_ttproducts_edit_variant
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 *
 */
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use JambageCom\TtProducts\Api\ParameterApi;

class EditVariantApi implements SingletonInterface
{
    public function getFieldArray()
    {
        $result = [];
        $cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
        $conf = $cnfObj->getConf();

        if (
            isset($conf['editVariant.']) &&
            is_array($conf['editVariant.'])
        ) {
            $editVariantConfig = $conf['editVariant.'];
            $count = 0;

            foreach ($editVariantConfig as $k => $config) {
                if ($k != 'default.' && (strpos((string) $k, '.') == strlen($k) - 1)) {
                    $count++;
                    if (isset($config['suffix'])) {
                        $suffix = $config['suffix'];
                    } else {
                        $suffix = $count;
                    }
                    $field = 'edit_' . $suffix;
                    $result[] = $field;
                }
            }
        }

        $result = array_unique($result);

        return $result;
    }

    public function getVariantFromRawRow($row)
    {
        $fieldArray = $this->getFieldArray();
        $variantArray = [];

        foreach ($row as $field => $value) {
            if (in_array($field, $fieldArray)) {
                $variantArray[] = $field . '=>' . $value;
            }
        }
        $result = implode(VariantApi::INTERNAL_VARIANT_SEPARATOR, $variantArray);

        return $result;
    }

    /**
     * Returns the variant extVar number from the incoming product row and the index in the variant array.
     *
     * @param	array	the basket raw row
     *
     * @return  string	  variants separated by variantSeparator
     *
     * @access private
     *
     * @see modifyRowFromVariant
     */
    public function getVariantRowFromProductRow($row)
    {
        $variantRow = false;

        if (
            isset($row) &&
            is_array($row) &&
            count($row)
        ) {
            foreach ($row as $field => $value) {
                if (strpos((string) $field, 'edit_') === 0) {
                    $variantRow[$field] = $value;
                }
            }
        }

        return $variantRow;
    }

    public function evalValues($dataValue, $config)
    {
        $result = true;
        $listOfCommands = GeneralUtility::trimExplode(',', $config, true);

        foreach ($listOfCommands as $cmd) {
            $cmdParts = preg_split('/\[|\]/', $cmd); // Point is to enable parameters after each command enclosed in brackets [..]. These will be in position 1 in the array.
            $theCmd = trim($cmdParts[0]);

            switch ($theCmd) {
                case 'required':
                    if (empty($dataValue) && $dataValue !== '0') {
                        $result = false;
                        break;
                    }
                    break;

                case 'wwwURL':
                    if ($dataValue) {
                        $url = 'http://' . $dataValue;
                        $report = [];

                        if (
                            !GeneralUtility::isValidUrl($url) ||
                            !GeneralUtility::getUrl($url) && $report['error'] != 22 // CURLE_HTTP_RETURNED_ERROR
                        ) {
                            $result = false;
                            break;
                        }
                    }
                    break;
            }
        }

        return $result;
    }

    public function checkValid(array $config, array $row)
    {
        $result = true;
        $checkedArray = [];
        $rowConfig = [];
        $resultArray = [];

        foreach ($config as $k => $rowConfig) {
            if ($rowConfig['suffix']) {
                $evalArray = '';
                $rangeArray = '';
                $theField = 'edit_' . $rowConfig['suffix'];

                if (isset($rowConfig['range.'])) {
                    $rangeArray = $rowConfig['range.'];
                }
                if (isset($rowConfig['evalValues.'])) {
                    $evalArray = $rowConfig['evalValues.'];
                }
                $bValidData = true;

                if (
                    !$checkedArray[$theField] &&
                    isset($row[$theField])
                ) {
                    $value = $row[$theField];

                    if (
                        isset($rangeArray) &&
                        is_array($rangeArray) &&
                        count($rangeArray)
                    ) {
                        $bValidData = false;

                        foreach ($rangeArray as $range) {
                            $rangeValueArray = GeneralUtility::trimExplode('-', $range);

                            if (
                                $rangeValueArray['0'] <= $value &&
                                $value <= $rangeValueArray['1']
                            ) {
                                $bValidData = true;
                                $checkedArray[$theField] = true;
                                break;
                            }
                        }
                    } elseif (
                        isset($evalArray) &&
                        is_array($evalArray) &&
                        count($evalArray)
                    ) {
                        foreach ($evalArray as $evalValues) {
                            $bValidData = $this->evalValues($value, $evalValues);
                            if ($bValidData) {
                                $checkedArray[$theField] = true;
                                break;
                            }
                        }
                    }
                }

                if (!$bValidData) {
                    if (isset($rowConfig['error'])) {
                        $resultArray[$theField] = $rowConfig['error'];
                    } else {
                        $resultArray[$theField] = 'Invalid value: ' . $theField;
                    }
                }
            }
        }

        if (is_array($resultArray) && count($resultArray)) {
            $result = $resultArray;
        }

        return $result;
    }

    public function getValidConfig($row)
    {
        $result = false;

        $cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
        $conf = $cnfObj->getConf();

        if (isset($conf['editVariant.'])) {
            $editVariantConfig = $conf['editVariant.'];
            $defaultConfig = $editVariantConfig['default.'];
            $count = 0;

            foreach ($editVariantConfig as $k => $config) {
                if ($k == 'default.') {
                    // nothing
                } elseif (strpos((string) $k, '.') == strlen($k) - 1) {
                    $count++;
                    $bIsValid = true;
                    if (isset($config['sql.']) && isset($config['sql.'])) {
                        $bIsValid = \tx_ttproducts_sql::isValid($row, $config['sql.']['where']);
                    }

                    if ($bIsValid) {
                        $mergeKeyArray = ['params'];
                        if (isset($defaultConfig) && is_array($defaultConfig)) {
                            foreach ($defaultConfig as $k2 => $config2) {
                                if (in_array($k2, $mergeKeyArray)) {
                                    // merge the configuration with the defaults
                                    if (isset($config2) && is_array($config2)) {
                                        if (isset($config[$k2]) && is_array($config[$k2])) {
                                            $config[$k2] = array_merge($config2, $config[$k2]);
                                        }
                                    } else {
                                        $config[$k2] = $config2 . ' ' . $config[$k2];
                                    }
                                }
                            }
                        }
                        $config['index'] = $count;
                        $result[$k] = $config;
                    }
                }
            }
        }

        return $result;
    }

    public function getVariables(array $editVariantConfig, array $row)
    {
        $result = [];

        foreach ($editVariantConfig as $k => $config) {
            if ($k == 'default.') {
                // nothing
            } elseif (strpos((string) $k, '.') == strlen($k) - 1) {
                if (
                    isset($config['setVariables.']) &&
                    isset($config['suffix'])
                ) {
                    foreach ($config['setVariables.'] as $variable => $value) {
                        if (strpos((string) $variable, '.') !== false) {
                            continue 1;
                        }
                        $isActive = true;
                        if (
                            isset($config['setVariables.'][$variable . '.']) &&
                            isset($config['setVariables.'][$variable . '.']['if']) &&
                            $config['setVariables.'][$variable . '.']['if'] == 'isset'
                        ) {
                            $isActive = false;
                            $editField = 'edit_' . $config['suffix'];
                            if (
                                isset($row[$editField]) &&
                                $row[$editField] != ''
                            ) {
                                $isActive = true;
                            }
                        }

                        if ($isActive) {
                            $result[$variable] = $value;
                        }
                    }
                }
            }
        }

        return $result;
    }

    public function getMarkerArray(
        $bEditable,
        $row,
        $funcTablename,
        $theCode,
        $config,
        &$markerArray
    ): void {
        $flags = null;
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
            $name = $parameterApi->getTagName($row['uid'], $field);
            $value = '';
            if (isset($row[$field])) {
                $value = $row[$field];
            }

            if ($bEditable) {
                $basketExtRaw = $parameterApi->getBasketExtRaw();
                if (
                    !(isset($config['empty']) || !$config['empty']) &&
                    isset($basketExtRaw) &&
                    is_array($basketExtRaw)
                ) {
                    if (isset($basketExtRaw[$uid]) && is_array($basketExtRaw[$uid])) {
                        $value = $basketExtRaw[$uid][$field];
                    }
                }

                $ajaxFunction = $parameterApi->getAjaxVariantFunction(
                    $row,
                    $funcTablename,
                    $theCode
                );
                $pat_attributes = '(\S+)=(("|\')(.| )*("|\')|(.* ))';
                preg_match_all("@$pat_attributes@isU", (string)$mainAttributes, $matches);
                $mainAttributesArray = [];
                $matchArray = [];
                if (is_array($matches)) {
                    $matchArray = $matches['0'];
                }

                if (isset($matchArray) && is_array($matchArray)) {
                    $lastKey = 0;
                    $lastAttribute = '';

                    foreach ($matchArray as $splitItem) {
                        $splitItemArray = explode('=', (string)$splitItem);
                        $parameterKey = strtolower($splitItemArray['0']);
                        $parameterValue = '';
                        if (isset($splitItemArray['1'])) {
                            $parameterValue = str_replace('"', '', $splitItemArray['1']);
                        }
                        $mainAttributesArray[$parameterKey] = $parameterValue;
                    }
                }

                if (
                    !isset($mainAttributesArray['onchange']) &&
                    $ajaxFunction != ''
                ) {
                    $mainAttributesArray['onchange'] = $ajaxFunction;
                }

                $mainId = $itemTableView->getId($row, '', $theCode);
                $id = $mainId . '-' . str_replace('_', '-', $field);
                $mainAttributesArray['id'] = $id;

                $html = \tx_ttproducts_form_div::createTag(
                    'input',
                    $name,
                    $value,
                    '',
                    $mainAttributesArray
                );
            } else {
                $html = '';
                if (isset($row[$field])) {
                    $html = htmlspecialchars((string) $row[$field], $flags);
                }
            }

            $markerArray['###EDIT_VARIANT###'] = $html;
        }
    }

    public function getSubpartMarkerArray(
        $templateCode,
        $funcTablename,
        array $row,
        $theCode,
        $bEditable,
        array $tagArray,
        array &$subpartArray,
        array &$wrappedSubpartArray
    ): void {
        // 		###edit_variant1###
        $templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        $editConf = $this->getValidConfig($row);

        if (isset($editConf) && is_array($editConf)) {
            $editVariables = $this->getVariables($editConf, $row);

            if (!empty($editVariables)) {
                $suffix = '_eq_1';
                $prefix = 'edit_variant_variable_';
                foreach ($editVariables as $variable => $value) {
                    if (
                        $value == 1
                    ) {
                        $marker = $prefix . $variable . $suffix;
                        $subpartMarker = '###' . $marker . '###';
                        $wrappedSubpartArray[$subpartMarker] = ['', ''];
                    }
                }
            }
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

                    $subpartContent = $templateService->getSubpart($templateCode, $subpartMarker);
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
            if (str_starts_with($tag, 'edit_variant')) {
                if (
                    !isset($subpartArray['###' . $tag . '###']) &&
                    !isset($wrappedSubpartArray['###' . $tag . '###'])
                ) {
                    $subpartArray['###' . $tag . '###'] = '';
                }
            }
        }
    }
}
