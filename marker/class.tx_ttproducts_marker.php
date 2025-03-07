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
 * marker functions
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 *
 */
use JambageCom\Div2007\Utility\FrontendUtility;
use JambageCom\TtProducts\Api\Localization;
use JambageCom\TtProducts\Model\Field\FieldInterface;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class tx_ttproducts_marker implements SingletonInterface
{
    public $markerArray;
    public $globalMarkerArray;
    public $urlArray;
    private $langArray;
    private $errorCode = [];
    private array $specialArray = ['eq', 'ne', 'lt', 'le', 'gt', 'ge', 'id', 'fn'];

    /**
     * Initialized the marker object
     * $basket is the TYPO3 default shopping basket array from ses-data.
     *
     * @param	array		array urls which should be overridden with marker key as index
     */
    public function init($conf, $backPID)
    {
        $this->markerArray = ['CATEGORY', 'PRODUCT', 'ARTICLE'];
        $markerFile = $conf['markerFile'] ?? '';
        $languageObj = GeneralUtility::makeInstance(Localization::class);
        $language = $languageObj->getTypo3LanguageKey();
        $languageSubpath = '/Resources/Private/Language/';
        $defaultMarkerFile = 'EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'locallang_marker.xlf';
        $languageObj->loadLocalLang($defaultMarkerFile);

        if ($language == '' || $language == 'default' || $language == 'en') {
            if (!$markerFile) {
                $markerFile = $defaultMarkerFile;
            }
            if ($markerFile) {
                $languageObj->loadLocalLang($markerFile);
            }
        } else {
            if (!$markerFile || $markerFile == '{$plugin.tt_products.file.markerFile}') {
                $markerFile = $defaultMarkerFile;
            } elseif (substr($markerFile, 0, 4) == 'EXT:') {	// extension
                [$extKey, $local] = explode('/', substr($markerFile, 4), 2);
                $filename = '';
                if (
                    strcmp($extKey, '') &&
                    !ExtensionManagementUtility::isLoaded($extKey) &&
                    strcmp($local, '')
                ) {
                    $errorCode = [];
                    $errorCode[0] = 'extension_missing';
                    $errorCode[1] = $extKey;
                    $errorCode[2] = $markerFile;
                    $this->setErrorCode($errorCode);
                }
            }

            if (!$markerFile) {
                throw new \RuntimeException('Error in tt_products: No marker file for language "' . $language . '" set.', 50011);
            }
            $languageObj->loadLocalLang($markerFile);
        }
        $locallang = $languageObj->getLocallang();
        $LLkey = $languageObj->getLocalLangKey();
        $this->setGlobalMarkerArray($conf, $backPID, $locallang, $LLkey);
        $errorCode = $this->getErrorCode();

        return !is_array($errorCode) || (count($errorCode) == 0) ? true : false;
    }

    public function getErrorCode()
    {
        return $this->errorCode;
    }

    public function setErrorCode($errorCode): void
    {
        $this->errorCode = $errorCode;
    }

    public function setLangArray(&$langArray): void
    {
        $this->langArray = $langArray;
    }

    public function getLangArray()
    {
        return $this->langArray;
    }

    public function getGlobalMarkerArray()
    {
        return $this->globalMarkerArray;
    }

    public function replaceGlobalMarkers(&$content, $markerArray = [])
    {
        $templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        $globalMarkerArray = $this->getGlobalMarkerArray();
        $markerArray = array_merge($globalMarkerArray, $markerArray);
        $result = $templateService->substituteMarkerArrayCached($content, $markerArray);

        return $result;
    }

    /**
     * getting the global markers.
     */
    public function setGlobalMarkerArray($conf, $backPID, $locallang, $LLkey): void
    {
        $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $markerArray = [];
        $language = $GLOBALS['TSFE']->config['config']['language'] ?? '';
        if ($language == '') {
            $language = 'default';
        }

        // globally substituted markers, fonts and colors.
        $splitMark = md5(microtime());
        [$markerArray['###GW1B###'], $markerArray['###GW1E###']] = explode($splitMark, $cObj->stdWrap($splitMark, $conf['wrap1.'] ?? ''));
        [$markerArray['###GW2B###'], $markerArray['###GW2E###']] = explode($splitMark, $cObj->stdWrap($splitMark, $conf['wrap2.'] ?? ''));
        [$markerArray['###GW3B###'], $markerArray['###GW3E###']] = explode($splitMark, $cObj->stdWrap($splitMark, $conf['wrap3.'] ?? ''));
        $markerArray['###GC1###'] = $cObj->stdWrap($conf['color1'] ?? '', $conf['color1.'] ?? '');
        $markerArray['###GC2###'] = $cObj->stdWrap($conf['color2'] ?? '', $conf['color2.'] ?? '');
        $markerArray['###GC3###'] = $cObj->stdWrap($conf['color3'] ?? '', $conf['color3.'] ?? '');
        $markerArray['###DOMAIN###'] = $conf['domain'] ?? '';
        $path = ExtensionManagementUtility::extPath('tt_products');

        if (ExtensionManagementUtility::isLoaded('addons_tt_products')) {
            $path = ExtensionManagementUtility::extPath('addons_tt_products');
        }
        $patchFe = PathUtility::getAbsoluteWebPath($path);
        $markerArray['###PATH_FE_REL###'] = $patchFe;
        $markerArray['###PATH_FE_ICONS###'] = $patchFe . 'Resources/Public/Images/';
        $pidMarkerArray = [
            'agb', 'basket', 'billing', 'delivery', 'finalize', 'info', 'itemDisplay',
            'listDisplay', 'payment', 'revocation',
            'search', 'storeRoot', 'thanks', 'tracking',
            'memo',
        ];
        foreach ($pidMarkerArray as $k => $function) {
            $markerArray['###PID_' . strtoupper($function) . '###'] = intval($conf['PID' . $function] ?? 0);
        }
        $markerArray['###SHOPADMIN_EMAIL###'] = $conf['orderEmail_from'] ?? 'undefined';
        $markerArray['###LANG###'] = '';
        $markerArray['###LANGUAGE###'] = $GLOBALS['TSFE']->config['config']['language'] ?? '';
        $markerArray['###LOCALE_ALL###'] = $GLOBALS['TSFE']->config['config']['locale_all'] ?? '';

        $backPID = ($backPID ?: $conf['PIDlistDisplay'] ?? ($GLOBALS['TSFE']->id ?? 0));
        $markerArray['###BACK_PID###'] = $backPID;

        // Call all addGlobalMarkers hooks at the end of this method
        if (
            isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['addGlobalMarkers']) &&
            is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['addGlobalMarkers'])
        ) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['addGlobalMarkers'] as $classRef) {
                $hookObj = GeneralUtility::makeInstance($classRef);
                if (method_exists($hookObj, 'addGlobalMarkers')) {
                    $hookObj->addGlobalMarkers($markerArray);
                }
            }
        }

        if (isset($locallang[$LLkey])) {
            if (isset($locallang['default']) && is_array($locallang['default'])) {
                $langArray = array_merge($locallang['default'], $locallang[$LLkey]);
            } else {
                $langArray = $locallang[$LLkey];
            }
        } else {
            $langArray = $locallang['default'];
        }

        if (isset($langArray) && is_array($langArray)) {
            foreach ($langArray as $key => $value) {
                if (
                    is_array($value) &&
                    isset($value[0])
                ) {
                    if (!empty($value[0]['target'])) {
                        $value = $value[0]['target'];
                    } else {
                        $value = $value[0]['source'];
                    }
                } else {
                    $value = '';
                }

                $langArray[$key] = $value;
                $markerArray['###' . strtoupper($key) . '###'] = $value;
            }
        } else {
            $langArray = [];
        }

        if (isset($conf['marks.'])) {
            // Substitute Marker Array from TypoScript Setup
            foreach ($conf['marks.'] as $key => $value) {
                if (is_array($value)) {
                    switch ($key) {
                        case 'image.':
                            foreach ($value as $k2 => $v2) {
                                $fileresource = FrontendUtility::fileResource($v2);
                                $markerArray['###IMAGE' . strtoupper($k2) . '###'] = $fileresource;
                            }
                            break;
                        case '_LOCAL_LANG.':
                            if (isset($value[$language . '.'])) {
                                foreach ($value[$language . '.'] as $key2 => $value2) {
                                    $markerArray['###' . strtoupper($key2) . '###'] = $value2;
                                }
                            }
                            break;
                    }
                } else {
                    if (
                        isset($conf['marks.'][$key . '.']) &&
                        is_array($conf['marks.'][$key . '.'])
                    ) {
                        $out = $cObj->cObjGetSingle(
                            $conf['marks.'][$key],
                            $conf['marks.'][$key . '.'],
                            $key
                        );
                    } else {
                        $langArray[$key] = $value;
                        $out = $value;
                    }
                    $markerArray['###' . strtoupper($key) . '###'] = $out;
                }
            }
        }

        $this->globalMarkerArray = $markerArray;
        $this->setLangArray($langArray);
    } // setGlobalMarkerArray

    public function reduceMarkerArray($templateCode, $markerArray)
    {
        $result = [];

        $tagArray = $this->getAllMarkers($templateCode);

        foreach ($tagArray as $tag => $v) {
            $marker = '###' . $tag . '###';
            if (isset($markerArray[$marker])) {
                $result[$marker] = $markerArray[$marker];
            }
        }

        return $result;
    }

    public function getAllMarkers($templateCode)
    {
        $treffer = [];
        $tagArray = false;

        preg_match_all('/###([\w:-]+)###/', $templateCode, $treffer);
        if (
            isset($treffer) &&
            is_array($treffer) &&
            isset($treffer['1'])
        ) {
            $tagArray = array_unique($treffer['1']);
        }

        if (is_array($tagArray)) {
            $tagArray = array_flip($tagArray);
        }

        return $tagArray;
    }

    /**
     * finds all the markers for a product
     * This helps to reduce the data transfer from the database.
     *
     * @access private
     */
    public function getMarkerFields(
        $templateCode,
        &$tableFieldArray,
        &$requiredFieldArray,
        &$addCheckArray,
        $prefixParam,
        &$tagArray,
        &$parentArray
    ) {
        $retArray = (count($requiredFieldArray) ? $requiredFieldArray : []);
        // obligatory fields uid and pid

        $prefix = $prefixParam . '_';
        $prefixLen = strlen($prefix);

        $bFieldaddedArray = [];

        $tagArray = $this->getAllMarkers($templateCode);

        if (is_array($tagArray)) {
            $retTagArray = $tagArray;
            foreach ($tagArray as $tag => $v1) {
                $prefixFound = strpos((string) $tag, $prefix);

                if ($prefixFound !== false) {
                    $fieldTmp = substr($tag, $prefixFound + $prefixLen);
                    $fieldTmp = strtolower($fieldTmp);
                    $fieldTmp = preg_replace('/[0-9]$/', '', $fieldTmp); // remove trailing numbers

                    $field = $fieldTmp;

                    if (isset($tableFieldArray[FieldInterface::EXTERNAL_FIELD_PREFIX . $field])) {
                        $field = FieldInterface::EXTERNAL_FIELD_PREFIX . $field;
                    }

                    if (!isset($tableFieldArray[$field])) {
                        $fieldPartArray = GeneralUtility::trimExplode('_', $fieldTmp);
                        $fieldTmp = $fieldPartArray[0];
                        $subFieldPartArray = GeneralUtility::trimExplode(':', $fieldTmp);
                        $colon = (count($subFieldPartArray) > 1);
                        $field = $subFieldPartArray[0];

                        if (
                            !isset($tableFieldArray[$field]) ||
                            isset($tableFieldArray[$field . '_uid'])
                        ) { // wird für ###PRODUCT_IMAGE1:M### benötigt
                            $field = preg_replace('/[0-9]$/', '', $field); // remove trailing numbers
                            if (isset($tableFieldArray[$field . '_uid'])) {
                                $field = $field . '_uid';

                                if (
                                    isset($tableFieldArray[$field]) &&
                                    is_array($tableFieldArray[$field])
                                ) {
                                    $retArray[] = $field;
                                    $bFieldaddedArray[$field] = true;
                                }
                            }
                        }

                        if (
                            !$colon &&
                            !isset($tableFieldArray[$field])
                        ) {
                            $newFieldPartArray = [];
                            foreach ($fieldPartArray as $k => $v) {
                                if (in_array($v, $this->specialArray)) {
                                    break;
                                } else {
                                    $newFieldPartArray[] = $v;
                                }
                            }
                            $field = implode('_', $newFieldPartArray);
                        }

                        if (
                            !$colon &&
                            (
                                !isset($tableFieldArray[$field]) ||
                                !is_array($tableFieldArray[$field])
                            )
                        ) {	// find similar field names with letters in other cases
                            $upperField = strtoupper($field);
                            foreach ($tableFieldArray as $k => $v) {
                                if (strtoupper($k) == $upperField) {
                                    $field = $k;
                                    break;
                                }
                            }
                        }
                    }
                    $field = strtolower($field);
                    if (
                        isset($tableFieldArray[$field]) &&
                        is_array($tableFieldArray[$field])
                    ) {
                        $retArray[] = $field;
                        $bFieldaddedArray[$field] = true;
                    }
                    $parentFound = strpos((string) $tag, 'PARENT');

                    if ($parentFound !== false) {
                        $parentEnd = strpos((string) $tag, '_');
                        $parentLen = strlen('PARENT');
                        $temp = substr($tag, $parentLen, ($parentEnd - $parentFound) - $parentLen);
                        $parentArray[] = $temp;
                    }
                } else {
                    // unset the tags of different tables

                    foreach ($this->markerArray as $k => $marker) {
                        if ($marker != $prefixParam) {
                            $bMarkerFound = strpos((string) $tag, (string)$marker);
                            if ($bMarkerFound == 0 && $bMarkerFound !== false) {
                                unset($retTagArray[$tag]);
                            }
                        }
                    }
                }
            }
            $tagArray = $retTagArray;
        } else {
            $tagArray = [];
        }

        $parentArray = array_unique($parentArray);
        sort($parentArray);

        if (is_array($addCheckArray)) {
            foreach ($addCheckArray as $marker => $field) {
                if (empty($bFieldaddedArray[$field]) && isset($tableFieldArray[$field])) { 	// TODO: check also if the marker is in the $tagArray
                    $retArray[] = $field;
                }
            }
        }

        if (is_array($retArray)) {
            $retArray = array_unique($retArray);
        }

        return $retArray;
    }
}
